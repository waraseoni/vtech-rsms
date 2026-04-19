<?php
require_once('../../config.php');

header('Content-Type: application/json');

$id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$from = isset($_GET['from']) && !empty($_GET['from']) ? $_GET['from'] : null;
$to = isset($_GET['to']) && !empty($_GET['to']) ? $_GET['to'] : null;

$status_filters = [];
if(isset($_GET['status']) && !empty($_GET['status'])) {
    if(is_array($_GET['status'])) {
        $status_filters = $_GET['status'];
    } else {
        $status_filters = explode(',', $_GET['status']);
    }
    $status_filters = array_map(function($item) {
        if(is_numeric($item)) return (int)$item;
        return $item;
    }, $status_filters);
}

if(!$id) {
    echo json_encode(['error' => 'Invalid client ID']);
    exit;
}

$status_arr = [
    0 => ['label' => "Pending", 'color' => '#f39c12'],
    1 => ['label' => "In Progress", 'color' => '#007bff'],
    2 => ['label' => "Done", 'color' => '#6f42c1'],
    3 => ['label' => "Paid", 'color' => '#28a745'],
    4 => ['label' => "Cancelled", 'color' => '#dc3545'],
    5 => ['label' => "Delivered", 'color' => '#19692c'],
    'payment' => ['label' => "Payments", 'color' => '#17a2b8'],
    'direct_sale' => ['label' => "Direct Sale", 'color' => '#ff6b6b'],
    'loan' => ['label' => "Loan Disbursement", 'color' => '#9b59b6']
];

$client_qry = $conn->query("SELECT *, CONCAT(firstname,' ',lastname) as name FROM `client_list` WHERE id = '{$id}'");
$client = ($client_qry->num_rows > 0) ? $client_qry->fetch_assoc() : null;
if(!$client) {
    echo json_encode(['error' => 'Client not found']);
    exit;
}

$show_payments = in_array('payment', $status_filters) || empty($status_filters);
$show_direct_sales = in_array('direct_sale', $status_filters) || empty($status_filters);
$show_loans = in_array('loan', $status_filters) || empty($status_filters);
$repair_statuses = [];
foreach($status_filters as $filter) {
    if(is_numeric($filter)) $repair_statuses[] = (int)$filter;
}
if(empty($status_filters)) $repair_statuses = null;

$repair_filter = ""; 
$payment_filter = "";
$direct_sales_filter = "";
$loans_filter = "";

if($from && $to){
    $repair_filter = " AND DATE(date_created) BETWEEN '{$from}' AND '{$to}' ";
    $payment_filter = " AND DATE(payment_date) BETWEEN '{$from}' AND '{$to}' ";
    $direct_sales_filter = " AND DATE(date_created) BETWEEN '{$from}' AND '{$to}' ";
    $loans_filter = " AND DATE(loan_date) BETWEEN '{$from}' AND '{$to}' ";
} else if($from) {
    $repair_filter = " AND DATE(date_created) >= '{$from}' ";
    $payment_filter = " AND DATE(payment_date) >= '{$from}' ";
    $direct_sales_filter = " AND DATE(date_created) >= '{$from}' ";
    $loans_filter = " AND DATE(loan_date) >= '{$from}' ";
} else if($to) {
    $repair_filter = " AND DATE(date_created) <= '{$to}' ";
    $payment_filter = " AND DATE(payment_date) <= '{$to}' ";
    $direct_sales_filter = " AND DATE(date_created) <= '{$to}' ";
    $loans_filter = " AND DATE(loan_date) <= '{$to}' ";
}

if($repair_statuses !== null) {
    if(empty($repair_statuses)) {
        $repair_filter .= " AND 1=0";
    } else {
        $repair_filter .= " AND status IN (" . implode(',', $repair_statuses) . ")";
    }
}

$ledger = [];
$rep_res = $conn->query("SELECT date_created, job_id, item, amount, status, remark, date_completed FROM transaction_list WHERE client_name = '{$id}' {$repair_filter}");
while($row = $rep_res->fetch_assoc()){
    $ledger[] = [
        'date' => date("d-m-Y", strtotime($row['date_created'])), 
        'desc' => "Job: ".$row['item'], 
        'ref' => $row['job_id'], 
        'status' => $row['status'], 
        'remark' => $row['remark'], 
        'debit' => (float)$row['amount'], 
        'credit' => 0,
        'discount' => 0,
        'timestamp' => strtotime($row['date_created']),
        'date_completed' => $row['date_completed']
    ];
}

if($show_direct_sales) {
    $ds_res = $conn->query("SELECT date_created, sale_code, total_amount, remarks FROM direct_sales WHERE client_id = '{$id}' {$direct_sales_filter}");
    while($row = $ds_res->fetch_assoc()){
        $ledger[] = [
            'date' => date("d-m-Y", strtotime($row['date_created'])), 
            'desc' => "Direct Sale", 
            'ref' => $row['sale_code'], 
            'status' => 'direct_sale', 
            'remark' => $row['remarks'], 
            'debit' => (float)$row['total_amount'], 
            'credit' => 0,
            'discount' => 0,
            'timestamp' => strtotime($row['date_created']),
            'date_completed' => null
        ];
    }
}

if($show_loans) {
    $loan_res = $conn->query("SELECT loan_date, id, total_payable, remarks FROM client_loans WHERE client_id = '{$id}' {$loans_filter}");
    while($row = $loan_res->fetch_assoc()){
        $ledger[] = [
            'date' => date("d-m-Y", strtotime($row['loan_date'])), 
            'desc' => "Loan Disbursement", 
            'ref' => "LN-".str_pad($row['id'], 5, '0', STR_PAD_LEFT), 
            'status' => 'loan', 
            'remark' => $row['remarks'], 
            'debit' => (float)$row['total_payable'], 
            'credit' => 0,
            'discount' => 0,
            'timestamp' => strtotime($row['loan_date']),
            'date_completed' => null
        ];
    }
}

if($show_payments) {
    $pay_res = $conn->query("SELECT payment_date, id, amount, discount, remarks FROM client_payments WHERE client_id = '{$id}' {$payment_filter}");
    while($row = $pay_res->fetch_assoc()){
        $ledger[] = [
            'date' => date("d-m-Y", strtotime($row['payment_date'])), 
            'desc' => "Payment Received", 
            'ref' => "PAY-".$row['id'], 
            'status' => 'payment', 
            'remark' => $row['remarks'], 
            'debit' => 0, 
            'credit' => (float)$row['amount'], 
            'discount' => (float)$row['discount'],
            'timestamp' => strtotime($row['payment_date']),
            'date_completed' => null
        ];
    }
}

usort($ledger, function($a, $b) { 
    return $a['timestamp'] <=> $b['timestamp']; 
});

$total_records = count($ledger);
$total_pages = ceil($total_records / $limit);
$offset = ($page - 1) * $limit;
$paginated_ledger = array_slice($ledger, $offset, $limit);

$running_total = 0;
$prev_running_total = 0;
foreach($ledger as $row) {
    $effective_credit = $row['credit'] + $row['discount'];
    $prev_running_total = $running_total;
    $running_total += ($row['debit'] - $effective_credit);
}

$opening_bal = (float)$client['opening_balance'];

// Calculate brought forward from records before offset
$brought_forward = $opening_bal;
$bf_offset = 0;
for($i = 0; $i < $offset && $i < count($ledger); $i++) {
    $effective_credit = $ledger[$i]['credit'] + $ledger[$i]['discount'];
    $brought_forward += ($ledger[$i]['debit'] - $effective_credit);
    $bf_offset = $i + 1;
}

$start_running_balance = 0;
for($i = 0; $i < $offset && $i < count($ledger); $i++) {
    $effective_credit = $ledger[$i]['credit'] + $ledger[$i]['discount'];
    $start_running_balance += ($ledger[$i]['debit'] - $effective_credit);
}

// Add opening balance
$start_running_balance += $opening_bal;
$total_repairs_q = $conn->query("SELECT SUM(amount) as total FROM transaction_list WHERE client_name = '{$id}'");
$total_repairs = (float)($total_repairs_q->fetch_assoc()['total'] ?? 0);

$total_direct_sales_q = $conn->query("SELECT SUM(total_amount) as total FROM direct_sales WHERE client_id = '{$id}'");
$total_direct_sales = (float)($total_direct_sales_q->fetch_assoc()['total'] ?? 0);

$total_loans_q = $conn->query("SELECT SUM(total_payable) as total FROM client_loans WHERE client_id = '{$id}'");
$total_loans = (float)($total_loans_q->fetch_assoc()['total'] ?? 0);

$total_payments_q = $conn->query("SELECT SUM(amount + discount) as total FROM client_payments WHERE client_id = '{$id}'");
$total_payments = (float)($total_payments_q->fetch_assoc()['total'] ?? 0);

$current_outstanding = $opening_bal + $total_repairs + $total_direct_sales + $total_loans - $total_payments;

// Ensure pagination numbers are integers
$page = (int)$page;
$limit = (int)$limit;
$total_records = (int)$total_records;
$total_pages = (int)ceil($total_records / $limit);

echo json_encode([
    'success' => true,
    'client' => [
        'id' => (int)$client['id'],
        'name' => $client['name'],
        'contact' => $client['contact']
    ],
    'balance' => [
        'opening' => $opening_bal,
        'total_repairs' => $total_repairs,
        'total_direct_sales' => $total_direct_sales,
        'total_loans' => $total_loans,
        'total_payments' => $total_payments,
        'current_outstanding' => $current_outstanding,
        'start_running_balance' => $start_running_balance
    ],
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'has_next' => ($page < $total_pages),
        'has_prev' => ($page > 1)
    ],
    'ledger' => $paginated_ledger,
    'status_arr' => $status_arr
]);