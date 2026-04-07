<?php
require 'config.php';
$res = $conn->query('DESCRIBE client_loans');
$output = "";
while($row = $res->fetch_assoc()) {
    $output .= implode("\t", $row) . "\n";
}
file_put_contents('schema_output.txt', $output);
