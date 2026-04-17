<?php
// Script to fix unquoted $_GET['id'] in PHP files
function fix_unquoted_gets($dir) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $count = 0;
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getRealPath());
            if ($content !== false && strpos($content, '{$_GET[\'id\']}') !== false) {
                // Find {$_GET['id']} and wrap with quotes if not already wrapped
                // Regex to find: something = {$_GET['id']} and replace with something = '{$_GET['id']}'
                // This regex ensures it only adds quotes if they are missing
                $new_content = preg_replace("/=\s*\{\\$_GET\['id'\]\}/", "= '{\$_GET[\'id\']}'", $content);
                
                if ($new_content !== $content) {
                    file_put_contents($file->getRealPath(), $new_content);
                    $count++;
                    echo "Patched: " . $file->getRealPath() . "\n";
                }
            }
        }
    }
    echo "Total files patched for unquoted IDs: $count\n";
}

fix_unquoted_gets(__DIR__ . '/admin');

// Now, update DBConnection.php to add a global HTML/SQL sanitize
$db_file = __DIR__ . '/classes/DBConnection.php';
$db_content = file_get_contents($db_file);

if (strpos($db_content, 'public function sanitize_array') === false) {
    $sanitize_method = '
    public function sanitize_array($array) {
        $clean = [];
        foreach($array as $key => $value){
            if(is_array($value)){
                $clean[$key] = $this->sanitize_array($value);
            } else {
                $clean[$key] = $this->conn->real_escape_string($value);
            }
        }
        return $clean;
    }
';

    // Insert sanitize_method before the last closing brace of the class
    $db_content = preg_replace('/(\s*}\s*\?>\s*)$/', $sanitize_method . '$1', $db_content);

    // Add global sanitization call inside constructor
    $db_content = str_replace(
        "if (!\$this->conn) {\n                echo 'Cannot connect to database server';\n                exit;\n            }",
        "if (!\$this->conn) {\n                echo 'Cannot connect to database server';\n                exit;\n            }\n            \n            // Global Security Patch: Sanitize all inputs against SQL injection\n            \$_GET = \$this->sanitize_array(\$_GET);\n            \$_POST = \$this->sanitize_array(\$_POST);\n            \$_REQUEST = \$this->sanitize_array(\$_REQUEST);",
        $db_content
    );

    file_put_contents($db_file, $db_content);
    echo "Patched classes/DBConnection.php successfully.\n";
} else {
    echo "classes/DBConnection.php already patched.\n";
}
?>
