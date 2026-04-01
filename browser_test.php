<?php
require_once('config.php');

$test_file = base_app . 'uploads/avatars/browser_test.txt';
$content = 'Browser test at ' . date('Y-m-d H:i:s');
$result = file_put_contents($test_file, $content);

echo "base_app: " . base_app . "<br>";
echo "Write result: " . $result . "<br>";
echo "File exists: " . (file_exists($test_file) ? 'YES' : 'NO') . "<br>";

$files = scandir(base_app . 'uploads/avatars/');
echo "<br>All files:<br>";
print_r($files);
?>