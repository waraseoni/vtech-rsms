<?php
require_once('../config.php');
require_once('../classes/DBConnection.php');
$db = new DBConnection();
$q = $db->conn->query("SELECT SUM(total_amount) FROM direct_sales WHERE DATE(date_created) BETWEEN '2026-05-01' AND '2026-05-01'");
var_dump($q->fetch_row());

$q2 = $db->conn->query("SELECT SUM(amount) FROM transaction_list WHERE status = 5 AND DATE(date_completed) BETWEEN '2026-05-01' AND '2026-05-01'");
var_dump($q2->fetch_row());
?>
