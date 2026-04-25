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

// Subqueries for balance components
$repair_billed_sq = "(SELECT SUM(amount) FROM transaction_list WHERE status = 5 AND client_name = c.id)";
$direct_sales_sq = "(SELECT SUM(total_amount) FROM direct_sales WHERE client_id = c.id)";
$total_paid_sq = "(SELECT SUM(amount + discount) FROM client_payments WHERE client_id = c.id)";
$total_loan_given_sq = "(SELECT SUM(total_payable) FROM client_loans WHERE status = 1 AND client_id = c.id)";
$balance_formula = "(c.opening_balance + COALESCE({$repair_billed_sq}, 0) + COALESCE({$direct_sales_sq}, 0) + COALESCE({$total_loan_given_sq}, 0) - COALESCE({$total_paid_sq}, 0))";

// Filter by balance if requested (this requires HAVING or a subquery/CTE because of aggregated fields)
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

// Get filtered count - since we use HAVING, we need a wrap query for counts if balance filters are present
if ($having_sql != "") {
    $count_sql = "SELECT COUNT(*) as total FROM (
        SELECT {$balance_formula} as current_balance 
        FROM client_list c 
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
    COALESCE({$repair_billed_sq}, 0) as repair_billed,
    COALESCE({$direct_sales_sq}, 0) as direct_sales_billed,
    COALESCE({$total_paid_sq}, 0) as total_paid,
    COALESCE({$total_loan_given_sq}, 0) as total_loan_given,
    (SELECT MAX(date_created) FROM transaction_list WHERE client_name = c.id) as last_txn_date,
    {$balance_formula} as current_balance
FROM `client_list` c 
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

// Summary totals (optional but good for consistency)
$total_outstanding = 0;
$summary_qry = $conn->query("SELECT 
    SUM(c.opening_balance + COALESCE({$repair_billed_sq}, 0) + COALESCE({$direct_sales_sq}, 0) + COALESCE({$total_loan_given_sq}, 0) - COALESCE({$total_paid_sq}, 0)) as total_outstanding
    FROM client_list c WHERE c.delete_flag = 0");
$summary = $summary_qry->fetch_assoc();
$total_outstanding = (float)$summary['total_outstanding'];

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
