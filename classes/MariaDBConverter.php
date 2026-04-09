<?php
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__) . '/');
}

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
    private $totalTables  = 0;

    public function __construct($inputFile, $outputFile = null) {
        $this->inputFile  = $inputFile;
        $this->outputFile = $outputFile ?: $this->generateOutputFilename($inputFile);
    }

    private function generateOutputFilename($input) {
        $dir       = dirname($input);
        $base      = pathinfo($input, PATHINFO_FILENAME);
        $timestamp = date('Y-m-d_H-i-s');
        return $dir . '/' . $base . '_converted_' . $timestamp . '.sql';
    }

    public function convert() {
        file_put_contents(
            __DIR__ . '/debug.log',
            date('Y-m-d H:i:s') . " - Starting conversion of " . basename($this->inputFile) . "\n",
            FILE_APPEND
        );

        $content = file_get_contents($this->inputFile);
        if ($content === false) {
            throw new Exception("Cannot read input file: " . $this->inputFile);
        }

        // ================================================================
        // PASS 1 — Pre-scan: collect PRIMARY KEYs, AUTO_INCREMENTs,
        //          and INSERT column lists from the raw dump.
        //
        // WHY: phpMyAdmin dumps define PRIMARY KEY and AUTO_INCREMENT via
        //      separate ALTER TABLE statements (not inside CREATE TABLE).
        //      INSERT statements also explicitly list columns, which is
        //      critical when a table has GENERATED (computed) columns that
        //      must be SKIPPED during INSERT — otherwise MySQL throws
        //      "Column count doesn't match value count".
        // ================================================================
        $primaryKeys    = [];   // tableName => ['col1', ...]
        $autoIncrements = [];   // tableName => ['column'=>'id', 'value'=>311]
        $insertColumns  = [];   // tableName => '`col1`, `col2`, ...'

        // (a) PRIMARY KEYs from ALTER TABLE
        preg_match_all(
            '/ALTER\s+TABLE\s+`(\w+)`[^;]*?ADD\s+PRIMARY\s+KEY\s*\(([^)]+)\)/si',
            $content, $pkMatches, PREG_SET_ORDER
        );
        foreach ($pkMatches as $m) {
            $cols = array_map(fn($c) => trim($c, " \t`"), explode(',', $m[2]));
            $primaryKeys[$m[1]] = $cols;
        }

        // (b) AUTO_INCREMENT from ALTER TABLE ... MODIFY
        preg_match_all(
            '/ALTER\s+TABLE\s+`(\w+)`\s+MODIFY\s+`(\w+)`[^,]+AUTO_INCREMENT(?:,\s*AUTO_INCREMENT=(\d+))?/si',
            $content, $aiMatches, PREG_SET_ORDER
        );
        foreach ($aiMatches as $m) {
            $autoIncrements[$m[1]] = [
                'column' => $m[2],
                'value'  => ($m[3] !== '') ? (int)$m[3] : null,
            ];
        }

        // (c) INSERT column lists — original dump lists only insertable columns,
        //     intentionally excluding GENERATED columns.
        preg_match_all(
            '/INSERT\s+INTO\s+`(\w+)`\s+\(([^)]+)\)\s+VALUES/si',
            $content, $icMatches, PREG_SET_ORDER
        );
        foreach ($icMatches as $m) {
            $colList = implode(', ', array_map(
                fn($c) => '`' . trim($c, " \t`") . '`',
                explode(',', $m[2])
            ));
            $insertColumns[$m[1]] = $colList;
        }

        // ================================================================
        // PASS 2 — Line-by-line conversion
        // ================================================================
        $content = $this->cleanContent($content);

        $output   = [];
        $output[] = "-- VTech-RSMS Backup";
        $output[] = "-- Date: " . date('Y-m-d H:i:s');
        $output[] = "";

        $lines = explode("\n", $content);
        $i = 0;
        $n = count($lines);

        while ($i < $n) {
            $line = trim($lines[$i]);
            $i++;

            if ($line === '') continue;

            // ---- CREATE TABLE ----------------------------------------
            if (preg_match('/^CREATE\s+TABLE\s+`?(\w+)`?\s*\(/i', $line, $m)) {
                $tableName   = $m[1];
                $createLines = [$line];
                $parenDepth  = 0;
                $foundSemi   = false;

                while ($i < $n && !$foundSemi) {
                    $cur           = trim($lines[$i]);
                    $createLines[] = $cur;
                    $parenDepth   += substr_count($cur, '(') - substr_count($cur, ')');
                    if ($parenDepth <= 0 && preg_match('/;\s*$/', $cur)) {
                        $foundSemi = true;
                    }
                    $i++;
                }

                $this->totalTables++;
                $fixedStmt = $this->fixCreateTable(
                    implode(" ", $createLines),
                    $tableName,
                    $primaryKeys,
                    $autoIncrements
                );

                $output[] = "DROP TABLE IF EXISTS `$tableName`;";
                $output[] = $fixedStmt;
                $output[] = "";
                continue;
            }

            // ---- INSERT INTO -----------------------------------------
            if (preg_match('/^INSERT\s+INTO\s+`?(\w+)`?/i', $line, $m)) {
                $tableName   = $m[1];
                $insertLines = [$line];
                $foundSemi   = false;

                while ($i < $n && !$foundSemi) {
                    $cur           = trim($lines[$i]);
                    $insertLines[] = $cur;
                    if (substr($cur, -1) === ';') {
                        $foundSemi = true;
                    }
                    $i++;
                }

                $insertSql = implode(" ", $insertLines);
                $valPos    = stripos($insertSql, 'VALUES');

                if ($valPos !== false) {
                    $valuesPart = ltrim(substr($insertSql, $valPos + 6));
                    $valuesPart = rtrim($valuesPart, ';');
                } else {
                    $valuesPart = '';
                }

                $rows = $this->splitBatchInsert($valuesPart);

                file_put_contents(
                    __DIR__ . '/debug.log',
                    "Table: $tableName, Rows: " . count($rows) . "\n",
                    FILE_APPEND
                );

                // KEY FIX: preserve original column names in INSERT
                // This correctly handles GENERATED columns (e.g. net_amount)
                // by skipping them — exactly as the original dump did.
                $colClause = isset($insertColumns[$tableName])
                    ? " (" . $insertColumns[$tableName] . ")"
                    : "";

                foreach ($rows as $row) {
                    $this->totalRecords++;
                    $output[] = "INSERT INTO `$tableName`{$colClause} VALUES $row;";
                }
                continue;
            }
        }

        $output[] = "";
        $output[] = "-- CHECKSUM: "      . md5(implode("\n", $output));
        $output[] = "-- TOTAL RECORDS: " . $this->totalRecords;
        $output[] = "-- TOTAL TABLES: "  . $this->totalTables;

        $result = implode("\n", $output);

        if (file_put_contents($this->outputFile, $result) === false) {
            throw new Exception("Cannot write output file: " . $this->outputFile);
        }

        return [
            'status'  => 'success',
            'input'   => $this->inputFile,
            'output'  => $this->outputFile,
            'tables'  => $this->totalTables,
            'records' => $this->totalRecords,
        ];
    }

    // ------------------------------------------------------------------
    private function cleanContent($content) {
        $lines = explode("\n", $content);
        $clean = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/^--\s*(phpMyAdmin|mysql|Host:|Generation|Server version|PHP Version)/i', $line)) {
                continue;
            }

            // Skip SET, START TRANSACTION, ALTER TABLE, COMMIT, LOCK/UNLOCK
            // (ALTER TABLE data already collected in Pass 1)
            if (preg_match('/^(SET\s+|START\s+TRANSACTION|ALTER\s+TABLE|COMMIT\b|UNLOCK\s+TABLES|LOCK\s+TABLES)/i', $line)) {
                continue;
            }

            if (strpos($line, '/*!') === 0 || strpos($line, '*/') !== false) {
                continue;
            }

            $clean[] = $line;
        }

        return implode("\n", $clean);
    }

    // ------------------------------------------------------------------
    private function fixCreateTable($sql, $tableName, $primaryKeys, $autoIncrements) {
        // 1. Remove inline CONSTRAINT / FOREIGN KEY definitions
        $sql = preg_replace(
            '/,?\s*CONSTRAINT\s+`?\w+`?\s+FOREIGN\s+KEY\s*\([^)]+\)\s+REFERENCES\s+`?\w+`?\s*\([^)]+\)(\s+ON\s+(DELETE|UPDATE)\s+\w+)*/i',
            '', $sql
        );
        $sql = preg_replace(
            '/,?\s*FOREIGN\s+KEY\s*\([^)]+\)\s+REFERENCES\s+`?\w+`?\s*\([^)]+\)(\s+ON\s+(DELETE|UPDATE)\s+\w+)*/i',
            '', $sql
        );

        // Clean double/trailing commas left by removals
        $sql = preg_replace('/,\s*,/', ',', $sql);
        $sql = preg_replace('/,\s*\)/',  ')', $sql);

        // 2. Inject AUTO_INCREMENT keyword on the correct column
        if (isset($autoIncrements[$tableName])) {
            $aiCol   = $autoIncrements[$tableName]['column'];
            $aiValue = $autoIncrements[$tableName]['value'];

            // Add AUTO_INCREMENT to column definition if missing
            $sql = preg_replace_callback(
                '/(`' . preg_quote($aiCol, '/') . '`\s+\w+[^,`\n]*?NOT\s+NULL)(?!\s+AUTO_INCREMENT)/i',
                fn($m) => $m[1] . ' AUTO_INCREMENT',
                $sql
            );

            // Set AUTO_INCREMENT table option
            if ($aiValue !== null) {
                if (preg_match('/AUTO_INCREMENT=\d+/i', $sql)) {
                    $sql = preg_replace('/AUTO_INCREMENT=\d+/i', "AUTO_INCREMENT=$aiValue", $sql);
                } else {
                    $sql = preg_replace('/(ENGINE=\w+)/i', "$1 AUTO_INCREMENT=$aiValue", $sql);
                }
            }
        }

        // 3. Inject PRIMARY KEY if missing from CREATE TABLE body
        if (!preg_match('/PRIMARY\s+KEY/i', $sql)) {
            if (!empty($primaryKeys[$tableName])) {
                $pkCols   = '`' . implode('`, `', $primaryKeys[$tableName]) . '`';
                $sql = preg_replace('/\)\s*(ENGINE=)/i', ", PRIMARY KEY ($pkCols) ) $1", $sql);
            } elseif (preg_match('/`(\w+)`\s+\w+[^,`\n]*AUTO_INCREMENT/i', $sql, $fm)) {
                $sql = preg_replace('/\)\s*(ENGINE=)/i', ", PRIMARY KEY (`{$fm[1]}`) ) $1", $sql);
            }
        }

        // 4. Ensure ENGINE clause exists
        if (!preg_match('/ENGINE=/i', $sql)) {
            $sql  = rtrim($sql, ';');
            $sql .= " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        }

        return $sql;
    }

    // ------------------------------------------------------------------
    private function splitBatchInsert($insertPart) {
        $rows       = [];
        $depth      = 0;
        $currentRow = '';
        $inString   = false;
        $stringChar = '';

        for ($i = 0, $len = strlen($insertPart); $i < $len; $i++) {
            $char = $insertPart[$i];

            if (!$inString && ($char === '"' || $char === "'")) {
                $inString   = true;
                $stringChar = $char;
            } elseif ($inString && $char === $stringChar && $insertPart[$i - 1] !== '\\') {
                $inString   = false;
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
                        $rows[]     = $currentRow;
                        $currentRow = '';
                        if (isset($insertPart[$i + 1]) && $insertPart[$i + 1] === ',') {
                            $i++;
                        }
                    }
                } elseif ($depth > 0) {
                    $currentRow .= $char;
                }
            } elseif ($depth > 0) {
                $currentRow .= $char;
            }
        }

        return $rows;
    }
}

// -----------------------------------------------------------------------
// HTTP entry point
// -----------------------------------------------------------------------
if (
    (isset($_GET['f'])  && $_GET['f']  === 'convert_mariadb') ||
    (isset($_POST['f']) && $_POST['f'] === 'convert_mariadb')
) {
    header('Content-Type: application/json');
    $resp = ['status' => 'failed', 'msg' => ''];

    try {
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== 0) {
            $msg = "No file uploaded or file error.";
            if (isset($_FILES['backup_file'])) {
                $msg .= " Code: " . $_FILES['backup_file']['error'];
            }
            throw new Exception($msg);
        }

        $uploadedFile = $_FILES['backup_file']['tmp_name'];
        $originalName = $_FILES['backup_file']['name'];

        if (!file_exists($uploadedFile)) {
            throw new Exception("Temporary file not found.");
        }

        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'sql') {
            throw new Exception("Only .sql files are allowed.");
        }

        $backupDir = __DIR__ . "/backups/converted/";
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $outputFile = $backupDir
            . pathinfo($originalName, PATHINFO_FILENAME)
            . '_converted_' . date('Y-m-d_H-i-s') . '.sql';

        $converter = new MariaDBToSoftwareConverter($uploadedFile, $outputFile);
        $result    = $converter->convert();

        $resp['status'] = 'success';
        $resp['msg']    = 'File converted successfully!';
        $resp['result'] = $result;

    } catch (Exception $e) {
        $resp['msg'] = $e->getMessage();
    }

    if (ob_get_length()) ob_end_clean();
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}