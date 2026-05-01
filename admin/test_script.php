<?php
require_once('../config.php');
require_once('../classes/Master.php');
global $Master;
$stats = $Master->get_dashboard_stats(date('Y-m-d'), date('Y-m-d'));
print_r($stats);
?>
