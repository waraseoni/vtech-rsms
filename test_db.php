<?php
require 'config.php';
$res = $conn->query("SELECT loan_id, COUNT(*) as cnt FROM client_payments GROUP BY loan_id LIMIT 10");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
