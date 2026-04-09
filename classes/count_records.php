<?php
// require_once('../config.php'); // Not needed for counting

function countRecordsInSQL($filePath) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        return "Cannot read file";
    }
    
    $lines = explode("\n", $content);
    $i = 0;
    $n = count($lines);
    $totalRecords = 0;
    $tableCounts = [];
    $insertCount = 0;
    
    while ($i < $n) {
        $line = trim($lines[$i]);
        $i++;
        
        if (empty($line)) continue;
        
        if (preg_match('/^INSERT\s+(IGNORE\s+)?INTO\s+[`]?(\w+)[`]?/i', $line, $matches)) {
            $tableName = $matches[2];
            $insertCount++;
            
            $insertLines = [$line];
            while ($i < $n && substr(trim($lines[$i]), -1) !== ';') {
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
            
            if ($tableName == 'transaction_list') {
                echo "Processing INSERT for transaction_list: " . substr($insertSql, 0, 200) . "...\n";
            }
            
            $pos = stripos($insertSql, 'VALUES');
            if ($pos !== false) {
                $valuesPart = substr($insertSql, $pos + 6);
                $valuesPart = ltrim($valuesPart);
                $valuesPart = rtrim($valuesPart, ';');
            } else {
                $valuesPart = '';
            }
            
            $rows = splitBatchInsert($valuesPart);
            
            $count = count($rows);
            echo "Table $tableName: $count rows\n";
            $totalRecords += $count;
        }
    }
    
    echo "Total INSERT statements found: $insertCount\n";
    return [
        'total' => $totalRecords,
        'tables' => $tableCounts
    ];
}

function splitBatchInsert($insertPart) {
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
        } elseif ($inString && $char === $stringChar && $insertPart[$i - 1] !== '\\') {
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

if ($argc > 1) {
    $file = $argv[1];
    $result = countRecordsInSQL($file);
    echo "Total: $result\n";
} elseif (isset($_GET['file'])) {
    $file = $_GET['file'];
    $result = countRecordsInSQL($file);
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    echo "Usage: php count_records.php path/to/sql/file or ?file=path/to/sql/file";
}
?>