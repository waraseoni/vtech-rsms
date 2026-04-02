<?php
$debugFile = __DIR__ . '/test_upload_debug.log';
file_put_contents($debugFile, date('Y-m-d H:i:s') . " - FILES: " . json_encode($_FILES) . "\n", FILE_APPEND);

if (isset($_FILES['backup_file'])) {
    $file = $_FILES['backup_file'];
    file_put_contents($debugFile, date('Y-m-d H:i:s') . " - File: " . $file['name'] . " Error: " . $file['error'] . "\n", FILE_APPEND);
    
    if ($file['error'] === 0) {
        $dest = __DIR__ . '/uploads/' . $file['name'];
        move_uploaded_file($file['tmp_name'], $dest);
        file_put_contents($debugFile, date('Y-m-d H:i:s') . " - Moved to: $dest\n", FILE_APPEND);
        echo "SUCCESS: File uploaded as " . $file['name'];
    } else {
        echo "ERROR: Upload failed with code " . $file['error'];
    }
} else {
    file_put_contents($debugFile, date('Y-m-d H:i:s') . " - No file in request\n", FILE_APPEND);
    echo "ERROR: No file uploaded";
}
