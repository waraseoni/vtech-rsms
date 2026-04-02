<?php
@session_start();
header('Content-Type: text/plain');
echo "Endpoint reachable\n";
echo "POST received: " . (isset($_POST['f']) ? $_POST['f'] : 'none') . "\n";
echo "FILES count: " . count($_FILES) . "\n";

if (isset($_FILES['backup_file'])) {
    echo "File name: " . $_FILES['backup_file']['name'] . "\n";
    echo "File error: " . $_FILES['backup_file']['error'] . "\n";
    echo "Temp file: " . $_FILES['backup_file']['tmp_name'] . "\n";
    echo "Exists: " . (file_exists($_FILES['backup_file']['tmp_name']) ? 'yes' : 'no') . "\n";
}
