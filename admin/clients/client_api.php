<?php
require_once('../../config.php');

header('Content-Type: application/json');

// DataTables server-side parameters
$draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
$search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
$order_col = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 4; // Default sort by balance
$order_dir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

// Custom filters
$min_balance = isset($_GET['min_balance']) && $_GET['min_balance'] !== '' ? floatval($_GET['min_balance']) : null;
$max_balance = isset($_GET['max_balance']) && $_GET['max_balance'] !== '' ? floatval($_GET['max_balance']) : null;

$conditions = ["c.delete_flag = 0"];

if(!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $conditions[] = "(c.firstname LIKE '%{$search_esc}%' OR c.middlename LIKE '%{$search_esc}%' OR c.lastname LIKE '%{$search_esc}%' OR c.contact LIKE '%{$search_esc}%' OR c.email LIKE '%{$search_esc}%' OR c.address LIKE '%{$search_esc}%' OR c.id LIKE '%{$search_esc}%')";
}

$where_sql = "WHERE " . implode(" AND ", $conditions);

// Performance-optimized LEFT JOIN query for balance components
$join_sql = "LEFT JOIN (SELECT client_name, SUM(amount) as repair_billed, MAX(date_created) as last_txn_date FROM transaction_list WHERE status = 5 GROUP BY client_name) t ON t.client_name = c.id
LEFT JOIN (SELECT client_id, SUM(total_amount) as direct_sales_billed FROM direct_sales GROUP BY client_id) d ON d.client_id = c.id
LEFT JOIN (SELECT client_id, SUM(amount + discount) as service_paid FROM client_payments WHERE (loan_id IS NULL OR loan_id = 0) GROUP BY client_id) p ON p.client_id = c.id
LEFT JOIN (SELECT cl.client_id, SUM(cl.total_payable) as total_loan_given, SUM(IFNULL(paid.total, 0)) as loan_repaid FROM client_loans cl LEFT JOIN (SELECT loan_id, SUM(amount + discount) as total FROM client_payments GROUP BY loan_id) paid ON cl.id = paid.loan_id WHERE cl.status = 1 GROUP BY cl.client_id) l ON l.client_id = c.id";

$balance_formula = "(c.opening_balance + COALESCE(t.repair_billed, 0) + COALESCE(d.direct_sales_billed, 0) - COALESCE(p.service_paid, 0) + COALESCE(l.total_loan_given, 0) - COALESCE(l.loan_repaid, 0))";

// Filter by balance if requested
$having_sql = "";
if ($min_balance !== null || $max_balance !== null) {
    $having_conds = [];
    if ($min_balance !== null) $having_conds[] = "current_balance >= {$min_balance}";
    if ($max_balance !== null) $having_conds[] = "current_balance <= {$max_balance}";
    $having_sql = "HAVING " . implode(" AND ", $having_conds);
}

// Column mapping for sorting
$columns = [
    0 => 'c.id', 
    1 => 'c.firstname', // Client Details
    2 => 'c.contact',   // Contact Info
    3 => 'c.address',   // Address
    4 => 'current_balance',
    5 => 'c.id'
];
$order_by = isset($columns[$order_col]) ? $columns[$order_col] : 'current_balance';
$order_by .= " " . $order_dir;

// Get filtered count
if ($having_sql != "") {
    $count_sql = "SELECT COUNT(*) as total FROM (
        SELECT {$balance_formula} as current_balance 
        FROM client_list c 
        {$join_sql}
        {$where_sql} 
        {$having_sql}
    ) as filtered_count";
} else {
    $count_sql = "SELECT COUNT(*) as total FROM client_list c {$where_sql}";
}
$filtered_records = (int)$conn->query($count_sql)->fetch_assoc()['total'];

// Get total count
$total_records = (int)$conn->query("SELECT COUNT(*) as total FROM client_list WHERE delete_flag = 0")->fetch_assoc()['total'];

// Final data query
$sql = "SELECT c.*, 
    COALESCE(t.repair_billed, 0) as repair_billed,
    COALESCE(d.direct_sales_billed, 0) as direct_sales_billed,
    COALESCE(p.service_paid, 0) as service_paid,
    COALESCE(l.total_loan_given, 0) as total_loan_given,
    COALESCE(l.loan_repaid, 0) as loan_repaid,
    t.last_txn_date,
    {$balance_formula} as current_balance
FROM `client_list` c 
{$join_sql}
{$where_sql} 
{$having_sql}
ORDER BY {$order_by}
LIMIT {$length} OFFSET {$start}";

$qry = $conn->query($sql);

$data = [];
$i = $start + 1;

while($row = $qry->fetch_assoc()){
    $fullname = ucwords($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']);
    $current_balance = (float)$row['current_balance'];
    $last_txn_date = $row['last_txn_date'];
    
    // Classes and Logic (mirroring index.php)
    $balance_class = '';
    $wa_class = 'whatsapp-reminder';
    $wa_text = 'Balance Reminder';
    
    if($current_balance > 0) {
        if($current_balance > 50000) { 
            $balance_class = 'balance-very-high'; 
            $wa_text = 'High Balance Reminder';
            $wa_class = 'whatsapp-reminder';
        } elseif($current_balance > 20000) { 
            $balance_class = 'balance-high'; 
        } else { 
            $balance_class = 'balance-positive'; 
        }
    } else { 
        $balance_class = 'balance-negative'; 
        $wa_class = 'whatsapp-welcome';
        $wa_text = 'Welcome';
    }

    if($last_txn_date) {
        $days_diff = floor((time() - strtotime($last_txn_date)) / (60 * 60 * 24));
        if($days_diff > 30 && $current_balance <= 0) {
            $wa_class = 'whatsapp-followup';
            $wa_text = 'Follow-up';
        }
    }

    // Prepare HTML for Table
    $img_path = validate_image($row['image_path']);
    $client_details = '
        <div class="client-info-cell">
            <img src="'.$img_path.'" 
                 class="desktop-avatar view_image_full" 
                 alt="Client"
                 loading="lazy"
                 data-src="'.$img_path.'"
                 onerror="this.src=\''.base_url.'dist/img/no-image-available.png\'">
            <div class="client-info-text">
                <a href="./?page=clients/view_client&id='.$row['id'].'" class="text-decoration-none">
                    <h5 class="text-primary">'.htmlspecialchars($fullname).'</h5>
                </a>
                <small class="text-muted">ID: '.$row['id'].'</small>
            </div>
        </div>';

    $contact_info = '
        <div>
            <div><i class="fa fa-phone-alt fa-fw text-primary"></i> '.htmlspecialchars($row['contact']).'</div>
            <div class="mt-1"><i class="fa fa-envelope fa-fw text-danger"></i> '.htmlspecialchars($row['email'] ?: 'No Email').'</div>
            '.(!empty($row['contact']) ? '
            <button type="button" class="whatsapp-badge mt-1 '.$wa_class.'" 
                    onclick="sendWhatsAppMessage('.$row['id'].', \''.addslashes($fullname).'\', \''.$row['contact'].'\', '.$current_balance.', '.($last_txn_date ? "'".$last_txn_date."'" : 'null').')">
                <i class="fab fa-whatsapp"></i> '.$wa_text.'
            </button>' : '').'
        </div>';

    $balance_display = '<div class="text-right font-weight-bold '.$balance_class.'">₹ '.number_format($current_balance, 2).'</div>';

    $action_display = '
        <div class="btn-group">
            <button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">Action</button>
            <div class="dropdown-menu" role="menu">
                <a class="dropdown-item" href="./?page=clients/view_client&id='.$row['id'].'"><span class="fa fa-eye text-primary"></span> View</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="'.$row['id'].'"><span class="fa fa-edit text-info"></span> Edit</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="'.$row['id'].'"><span class="fa fa-trash text-danger"></span> Delete</a>
            </div>
        </div>';

    $data[] = [
        0 => $i++,
        1 => $client_details,
        2 => $contact_info,
        3 => '<div class="address-text">'.htmlspecialchars($row['address']).'</div>',
        4 => $balance_display,
        5 => $action_display,
        // Raw data for mobile cards
        'raw_id' => $row['id'],
        'raw_fullname' => $fullname,
        'raw_contact' => $row['contact'],
        'raw_email' => $row['email'],
        'raw_address' => $row['address'],
        'raw_image_path' => $row['image_path'],
        'raw_resolved_img' => $img_path,
        'raw_current_balance' => $current_balance,
        'raw_last_txn_date' => $last_txn_date,
        'raw_wa_class' => $wa_class,
        'raw_wa_text' => $wa_text,
        'raw_balance_class' => $balance_class
    ];
}

// Summary totals (much faster calculation)
$tot_ob = $conn->query("SELECT SUM(opening_balance) as tot FROM client_list WHERE delete_flag = 0")->fetch_assoc()['tot'] ?? 0;
$tot_repair = $conn->query("SELECT SUM(t.amount) as tot FROM transaction_list t INNER JOIN client_list c ON t.client_name = c.id WHERE t.status = 5 AND c.delete_flag = 0")->fetch_assoc()['tot'] ?? 0;
$tot_ds = $conn->query("SELECT SUM(d.total_amount) as tot FROM direct_sales d INNER JOIN client_list c ON d.client_id = c.id WHERE c.delete_flag = 0")->fetch_assoc()['tot'] ?? 0;
$tot_loans = $conn->query("SELECT SUM(l.total_payable) as tot FROM client_loans l INNER JOIN client_list c ON l.client_id = c.id WHERE l.status = 1 AND c.delete_flag = 0")->fetch_assoc()['tot'] ?? 0;
$tot_service_paid = $conn->query("SELECT SUM(p.amount + p.discount) as tot FROM client_payments p INNER JOIN client_list c ON p.client_id = c.id WHERE c.delete_flag = 0 AND (p.loan_id IS NULL OR p.loan_id = 0)")->fetch_assoc()['tot'] ?? 0;
$tot_active_loan_paid = $conn->query("SELECT SUM(p.amount + p.discount) as tot FROM client_payments p INNER JOIN client_loans l ON p.loan_id = l.id INNER JOIN client_list c ON p.client_id = c.id WHERE c.delete_flag = 0 AND l.status = 1")->fetch_assoc()['tot'] ?? 0;

$tot_paid = $tot_service_paid + $tot_active_loan_paid;

$total_outstanding = (float)($tot_ob + $tot_repair + $tot_ds + $tot_loans - $tot_paid);

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total_records,
    'recordsFiltered' => $filtered_records,
    'data' => $data,
    'summary' => [
        'total_clients' => $total_records,
        'total_outstanding' => $total_outstanding
    ]
]);
