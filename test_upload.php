<?php
$test_file = 'C:/xampp/htdocs/vtech-rsms/uploads/avatars/test_from_browser.txt';
file_put_contents($test_file, 'test at ' . date('Y-m-d H:i:s'));

echo "base_app: " . (defined('base_app') ? base_app : 'NOT DEFINED') . "<br>";
echo "File written: " . (file_exists($test_file) ? 'YES' : 'NO') . "<br>";
echo "Files in avatars:<br>";
$files = scandir('C:/xampp/htdocs/vtech-rsms/uploads/avatars/');
print_r($files);
?>