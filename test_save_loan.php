<?php
require 'config.php';
$_POST = [
    'client_id' => 308,
    'principal_amount' => 5000,
    'interest_rate' => 0,
    'months' => 1,
    'total_payable' => 5000,
    'emi_amount' => 5000,
    'loan_date' => '2026-04-06'
];

require 'classes/Master.php';
$master = new Master();
echo $master->save_client_loan();

$res = $conn->query("SELECT * FROM client_loans WHERE client_id = 308 ORDER BY id DESC LIMIT 1");
print_r($res->fetch_assoc());
