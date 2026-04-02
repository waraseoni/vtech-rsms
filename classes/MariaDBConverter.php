<?php
$debugFile = __DIR__ . '/convert_debug.log';
file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Start\n", FILE_APPEND);

if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__) . '/');
}

file_put_contents($debugFile, date('Y-m-d H:i:s') . " - After BASEPATH\n", FILE_APPEND);

require_once('../config.php');

file_put_contents($debugFile, date('Y-m-d H:i:s') . " - After require config\n", FILE_APPEND);

// Check for any output buffering issues
while (ob_get_level()) {
    ob_end_clean();
}

file_put_contents($debugFile, date('Y-m-d H:i:s') . " - After ob cleanup\n", FILE_APPEND);

$maxUpload = ini_get('upload_max_filesize');
$maxPost = ini_get('post_max_size');

file_put_contents($debugFile, date('Y-m-d H:i:s') . " - FILES: " . json_encode($_FILES) . "\n", FILE_APPEND);

// Check for any output buffering issues
while (ob_get_level()) {
    ob_end_clean();
}

$maxUpload = ini_get('upload_max_filesize');
$maxPost = ini_get('post_max_size');

file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Max upload: $maxUpload, Max post: $maxPost\n", FILE_APPEND);

// Check if file was uploaded
if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== 0) {
    $errorMsg = "No file uploaded or file error.";
    if (isset($_FILES['backup_file'])) {
        $errorMsg .= " Code: " . $_FILES['backup_file']['error'];
    }
    file_put_contents($debugFile, date('Y-m-d H:i:s') . " - ERROR: $errorMsg\n", FILE_APPEND);
    echo json_encode(['status' => 'failed', 'msg' => $errorMsg]);
    exit;
}

$uploadedFile = $_FILES['backup_file']['tmp_name'];
$originalName = $_FILES['backup_file']['name'];

file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Uploaded file: $originalName\n", FILE_APPEND);

if (!file_exists($uploadedFile)) {
    file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Temp file not found\n", FILE_APPEND);
    echo json_encode(['status' => 'failed', 'msg' => 'Temp file not found']);
    exit;
}

$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if ($ext !== 'sql') {
    file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Invalid extension\n", FILE_APPEND);
    echo json_encode(['status' => 'failed', 'msg' => 'Only .sql files allowed']);
    exit;
}

$backupDir = __DIR__ . "/backups/converted/";
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$outputFile = $backupDir . pathinfo($originalName, PATHINFO_FILENAME) . '_converted_' . date('Y-m-d_H-i-s') . '.sql';

file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Output: $outputFile\n", FILE_APPEND);

// Now process the file
$converter = new MariaDBToSoftwareConverter($uploadedFile, $outputFile);
$result = $converter->convert();

file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Converted! Tables: " . $result['tables'] . ", Records: " . $result['records'] . "\n", FILE_APPEND);

echo json_encode(['status' => 'success', 'msg' => 'File converted!', 'result' => $result]);
exit;

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
                while ($i < $n && !preg_match('/;\s*$/', $line)) {
                    $line = trim($lines[$i]);
                    $createLines[] = $line;
                    $i++;
                }
                if ($i < $n) {
                    $line = trim($lines[$i]);
                    $createLines[] = $line;
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
                while ($i < $n && !preg_match('/;\s*$/', $line)) {
                    $line = trim($lines[$i]);
                    $insertLines[] = $line;
                    $i++;
                }
                if ($i < $n) {
                    $line = trim($lines[$i]);
                    $insertLines[] = $line;
                    $i++;
                }
                
                $insertSql = implode(" ", $insertLines);
                
                $valuesPart = preg_replace('/^INSERT\s+INTO\s+[`]?\w+[`]?\s*(\([^)]+\))?\s*VALUES\s*/i', '', $insertSql);
                $valuesPart = rtrim($valuesPart, ';');
                
                $rows = $this->splitBatchInsert($valuesPart);
                
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
        if (!preg_match('/PRIMARY\s+KEY/i', $sql)) {
            if (preg_match('/`(\w+)`\s+int\([^)]+\)\s+NOT\s+NULL\s+AUTO_INCREMENT/i', $sql, $matches)) {
                $pkColumn = $matches[1];
                $sql = rtrim($sql, ';');
                $sql .= ", PRIMARY KEY (`{$pkColumn}`)";
                $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
                return $sql;
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
        
        for ($i = 0; $i < strlen($insertPart); $i++) {
            $char = $insertPart[$i];
            
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
        }
        
        return $rows;
    }
}

if (isset($_GET['f']) && $_GET['f'] === 'convert_mariadb') {
    header('Content-Type: application/json');
    $resp = ['status' => 'failed', 'msg' => ''];
    
    try {
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== 0) {
            $errorMsg = "No file uploaded or file error.";
            if (isset($_FILES['backup_file'])) {
                $errorCodes = [1 => 'File exceeds upload limit', 
                              2 => 'File exceeds MAX_FILE_SIZE', 
                              3 => 'File partially uploaded', 
                              4 => 'No file uploaded',
                              6 => 'Missing temp folder',
                              7 => 'Failed to write file',
                              8 => 'Extension blocked'];
                $errorMsg .= " Code: " . $_FILES['backup_file']['error'];
                if (isset($errorCodes[$_FILES['backup_file']['error']])) {
                    $errorMsg .= " - " . $errorCodes[$_FILES['backup_file']['error']];
                }
            }
            throw new Exception($errorMsg);
        }
        
        $uploadedFile = $_FILES['backup_file']['tmp_name'];
        $originalName = $_FILES['backup_file']['name'];
        
        if (!file_exists($uploadedFile)) {
            throw new Exception("Temporary file not found. May be too large.");
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
    
    echo json_encode($resp);
    exit;
}
