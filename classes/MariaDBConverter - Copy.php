<?php
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__) . '/');
}

// Disable display of runtime warnings for JSON-only API response
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
set_time_limit(600);
ini_set('memory_limit', '512M');

require_once('../config.php');

class MariaDBToSoftwareConverter {
    private $inputFile;
    private $outputFile;
    private $totalRecords = 0;
    private $totalTables = 0;
    
    public function __construct($inputFile, $outputFile = null) {
        $this->inputFile = $inputFile;
        $this->outputFile = $outputFile ?: $this->generateOutputFilename($inputFile);
    }
    
    private function generateOutputFilename($input) {
        $dir = dirname($input);
        $base = pathinfo($input, PATHINFO_FILENAME);
        $timestamp = date('Y-m-d_H-i-s');
        return $dir . '/' . $base . '_converted_' . $timestamp . '.sql';
    }
    
    public function convert() {
        file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - Starting conversion of " . basename($this->inputFile) . "\n", FILE_APPEND);
        
        $content = file_get_contents($this->inputFile);
        if ($content === false) {
            throw new Exception("Cannot read input file: " . $this->inputFile);
        }
        
        $content = $this->cleanContent($content);
        
        $output = [];
        $output[] = "-- VTech-RSMS Backup";
        $output[] = "-- Date: " . date('Y-m-d H:i:s');
        $output[] = "";
        
        $lines = explode("\n", $content);
        $i = 0;
        $n = count($lines);
        
        while ($i < $n) {
            $line = trim($lines[$i]);
            $i++;
            
            if (empty($line)) continue;
            
            if (preg_match('/^CREATE\s+TABLE\s+[`]?(\w+)[`]?\s*\(/i', $line)) {
                preg_match('/^CREATE\s+TABLE\s+[`]?(\w+)[`]?/i', $line, $m);
                $tableName = $m[1];
                
                $createLines = [$line];
                $parenDepth = 0;
                $foundSemicolon = false;
                
                while ($i < $n && !$foundSemicolon) {
                    $currentLine = trim($lines[$i]);
                    $createLines[] = $currentLine;
                    $parenDepth += substr_count($currentLine, '(') - substr_count($currentLine, ')');
                    if ($parenDepth <= 0 && preg_match('/;\s*$/', $currentLine)) {
                        $foundSemicolon = true;
                    }
                    $i++;
                }
                
                $createSql = implode(" ", $createLines);
                
                $this->totalTables++;
                $fixedStmt = $this->fixCreateTable($createSql);
                $output[] = "DROP TABLE IF EXISTS `$tableName`;";
                $output[] = $fixedStmt;
                $output[] = "";
                continue;
            }
            
            if (preg_match('/^INSERT\s+INTO\s+[`]?(\w+)[`]?/i', $line, $matches)) {
                $tableName = $matches[1];
                
                $insertLines = [$line];
                $foundSemicolon = false;
                
                while ($i < $n && !$foundSemicolon) {
                    $currentLine = trim($lines[$i]);
                    $insertLines[] = $currentLine;
                    if (substr($currentLine, -1) === ';') {
                        $foundSemicolon = true;
                    }
                    $i++;
                }
                
                $insertSql = implode(" ", $insertLines);
                
                $pos = stripos($insertSql, 'VALUES');
                if ($pos !== false) {
                    $valuesPart = substr($insertSql, $pos + 6);
                    $valuesPart = ltrim($valuesPart);
                    $valuesPart = rtrim($valuesPart, ';');
                } else {
                    $valuesPart = '';
                }
                
                $rows = $this->splitBatchInsert($valuesPart);
                
                file_put_contents(__DIR__ . '/debug.log', "Table: $tableName, Rows in this INSERT: " . count($rows) . "\n", FILE_APPEND);
                
                foreach ($rows as $row) {
                    $this->totalRecords++;
                    $output[] = "INSERT INTO `$tableName` VALUES $row;";
                }
                continue;
            }
        }
        
        $output[] = "";
        $output[] = "-- CHECKSUM: " . md5(implode("\n", $output));
        $output[] = "-- TOTAL RECORDS: " . $this->totalRecords;
        $output[] = "-- TOTAL TABLES: " . $this->totalTables;
        
        $result = implode("\n", $output);
        
        if (file_put_contents($this->outputFile, $result) === false) {
            throw new Exception("Cannot write output file: " . $this->outputFile);
        }
        
        return [
            'status' => 'success',
            'input' => $this->inputFile,
            'output' => $this->outputFile,
            'tables' => $this->totalTables,
            'records' => $this->totalRecords
        ];
    }
    
    private function cleanContent($content) {
        $lines = explode("\n", $content);
        $clean = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (preg_match('/^--\s*(phpMyAdmin|mysql|Host:|Generation|Server version|PHP Version)/i', $line)) {
                continue;
            }
            
            if (preg_match('/^(SET\s+|START\s+TRANSACTION)/i', $line)) {
                continue;
            }
            
            if (strpos($line, '/*!') === 0 || strpos($line, '*/') !== false) {
                continue;
            }
            
            $clean[] = $line;
        }
        
        return implode("\n", $clean);
    }
    
    private function fixCreateTable($sql) {
        // Remove CONSTRAINT and FOREIGN KEY definitions from the SQL
        // This handles various formats of foreign key declarations
        $sql = preg_replace('/,?\s*CONSTRAINT\s+`?\w+`?\s+FOREIGN\s+KEY\s*\([^)]+\)\s+REFERENCES\s+`?\w+`?\s*\([^)]+\)/i', '', $sql);
        $sql = preg_replace('/,?\s*FOREIGN\s+KEY\s*\([^)]+\)\s+REFERENCES\s+`?\w+`?\s*\([^)]+\)/i', '', $sql);
        // Fix any double commas or trailing commas before closing paren
        $sql = preg_replace('/,\s*,/', ',', $sql);
        $sql = preg_replace('/,\s*\)/', ')', $sql);
        
        // Find and fix AUTO_INCREMENT if present
        if (preg_match('/`(\w+)`\s+int\([^)]*\)\s+NOT\s+NULL\s+AUTO_INCREMENT/i', $sql, $matches)) {
            $pkColumn = $matches[1];
            // Add PRIMARY KEY if not already present
            if (!preg_match('/PRIMARY\s+KEY/i', $sql)) {
                $sql = preg_replace('/\)\s*ENGINE=/i', ", PRIMARY KEY (`{$pkColumn}`) ) ENGINE=", $sql);
            }
        } elseif (!preg_match('/PRIMARY\s+KEY/i', $sql)) {
            // Check if there's an AUTO_INCREMENT in the entire SQL
            if (preg_match('/`(\w+)`\s*[^`]*AUTO_INCREMENT/i', $sql, $matches)) {
                $pkColumn = $matches[1];
                $sql = preg_replace('/\)\s*ENGINE=/i', ", PRIMARY KEY (`{$pkColumn}`) ) ENGINE=", $sql);
            }
        }
        
        if (!preg_match('/ENGINE=/i', $sql)) {
            $sql = rtrim($sql, ';');
            $sql .= " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        }
        
        return $sql;
    }
    
    private function splitBatchInsert($insertPart) {
        $rows = [];
        $depth = 0;
        $currentRow = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($insertPart); $i++) {
            $char = $insertPart[$i];
            
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar && ($i == 0 || $insertPart[$i - 1] !== '\\')) {
                $inString = false;
                $stringChar = '';
            }
            
            if (!$inString) {
                if ($char === '(') {
                    $depth++;
                    $currentRow .= $char;
                } elseif ($char === ')') {
                    $depth--;
                    $currentRow .= $char;
                    
                    if ($depth === 0) {
                        $rows[] = $currentRow;
                        $currentRow = '';
                        if (isset($insertPart[$i + 1]) && $insertPart[$i + 1] === ',') {
                            $i++;
                        }
                    }
                } elseif ($depth > 0) {
                    $currentRow .= $char;
                }
            } else {
                if ($depth > 0) {
                    $currentRow .= $char;
                }
            }
        }
        
        return $rows;
    }
}

if ((isset($_GET['f']) && $_GET['f'] === 'convert_mariadb') || (isset($_POST['f']) && $_POST['f'] === 'convert_mariadb')) {
    header('Content-Type: application/json');
    $resp = ['status' => 'failed', 'msg' => ''];
    
    try {
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== 0) {
            $errorMsg = "No file uploaded or file error.";
            if (isset($_FILES['backup_file'])) {
                $errorMsg .= " Code: " . $_FILES['backup_file']['error'];
            }
            throw new Exception($errorMsg);
        }
        
        $uploadedFile = $_FILES['backup_file']['tmp_name'];
        $originalName = $_FILES['backup_file']['name'];
        
        if (!file_exists($uploadedFile)) {
            throw new Exception("Temporary file not found.");
        }
        
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            throw new Exception("Only .sql files are allowed.");
        }
        
        $backupDir = __DIR__ . "/backups/converted/";
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        
        $outputFile = $backupDir . pathinfo($originalName, PATHINFO_FILENAME) . '_converted_' . date('Y-m-d_H-i-s') . '.sql';
        
        $converter = new MariaDBToSoftwareConverter($uploadedFile, $outputFile);
        $result = $converter->convert();
        
        $resp['status'] = 'success';
        $resp['msg'] = 'File converted successfully!';
        $resp['result'] = $result;
        
    } catch (Exception $e) {
        $resp['msg'] = $e->getMessage();
    }
    
    if (ob_get_length()) {
        ob_end_clean();
    }
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}
