<?php
require_once('../../config.php');

header('Content-Type: application/json');

// DataTables server-side parameters
$draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$length = isset($_GET['length']) ? (int)$_GET['length'] : 50;
$search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
$order_col = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 1;
$order_dir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

// Custom filters from form
$date_from = isset($_GET['date_from']) && !empty($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) && !empty($_GET['date_to']) ? $_GET['date_to'] : '';
$status = isset($_GET['status']) ? intval($_GET['status']) : null;
$hide_delivered = isset($_GET['hide_delivered']) && $_GET['hide_delivered'] == 'true';

$conditions = [];
if(!empty($date_from) && !empty($date_to)){
    $conditions[] = "date(t.date_created) BETWEEN '{$date_from}' AND '{$date_to}'";
} elseif(!empty($date_from)) {
    $conditions[] = "date(t.date_created) >= '{$date_from}'";
} elseif(!empty($date_to)) {
    $conditions[] = "date(t.date_created) <= '{$date_to}'";
}

if($status !== null && in_array($status, [0,1,2,3,4,5])){
    $conditions[] = "t.status = '{$status}'";
}

if($hide_delivered) {
    $conditions[] = "t.status != 5";
}

if(!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $conditions[] = "(c.firstname LIKE '%{$search_esc}%' OR c.lastname LIKE '%{$search_esc}%' OR c.contact LIKE '%{$search_esc}%' OR t.job_id LIKE '%{$search_esc}%' OR t.item LIKE '%{$search_esc}%' OR t.code LIKE '%{$search_esc}%') ";
}

$where_sql = "";
if(!empty($conditions)){
    $where_sql = "WHERE " . implode(" AND ", $conditions);
}

// Get total count (without any filter)
$total_qry = $conn->query("SELECT COUNT(*) as total FROM transaction_list t INNER JOIN client_list c ON t.client_name = c.id");
$total_records = (int)$total_qry->fetch_assoc()['total'];

// Get filtered count
$filtered_qry = $conn->query("SELECT COUNT(*) as total FROM transaction_list t INNER JOIN client_list c ON t.client_name = c.id " . ($where_sql ? $where_sql : ""));
$filtered_records = (int)$filtered_qry->fetch_assoc()['total'];

// Column mapping for sorting - matching the table columns
$columns = ['t.id', 't.date_created', 't.job_id', 'c.firstname', 't.item', 't.fault', 't.uniq_id', 't.amount', 't.status', 't.id'];
$order_by = isset($columns[$order_col]) ? $columns[$order_col] : 't.date_created';
$order_by .= " " . $order_dir;

// Get data with balance calculation
$sql = "SELECT t.*, 
    c.firstname, c.middlename, c.lastname, c.contact, c.image_path as client_img, 
    c.opening_balance, c.id as client_tbl_id,
    COALESCE(cb.total_billed, 0) as total_billed,
    COALESCE(cp.total_paid, 0) as total_paid,
    COALESCE(ds.total_sale, 0) as total_sale,
    COALESCE(al.active_loan_balance, 0) as active_loan_balance
FROM `transaction_list` t 
INNER JOIN client_list c ON t.client_name = c.id 
LEFT JOIN (SELECT client_name, SUM(amount) as total_billed FROM transaction_list WHERE status = 5 GROUP BY client_name) cb ON cb.client_name = c.id
LEFT JOIN (SELECT client_id, SUM(amount + discount) as total_paid FROM client_payments WHERE loan_id IS NULL GROUP BY client_id) cp ON cp.client_id = c.id
LEFT JOIN (SELECT client_id, SUM(total_amount) as total_sale FROM direct_sales GROUP BY client_id) ds ON ds.client_id = c.id
LEFT JOIN (
    SELECT cl.client_id,
        SUM(cl.total_payable) - SUM(IFNULL(paid.total, 0)) AS active_loan_balance
    FROM client_loans cl
    LEFT JOIN (SELECT loan_id, SUM(amount + discount) AS total FROM client_payments GROUP BY loan_id) paid ON cl.id = paid.loan_id
    WHERE cl.status = 1
    GROUP BY cl.client_id
) al ON al.client_id = c.id
" . ($where_sql ? $where_sql : "") . " 
ORDER BY {$order_by}
LIMIT {$length} OFFSET {$start}";

$qry = $conn->query($sql);

$stat_arr = ["Pending", "On-Progress", "Done", "Paid", "Cancelled", "Delivered"];
$stat_colors = ["secondary", "primary", "info", "success", "danger", "warning"];

$data = [];
$i = $start + 1;
while($row = $qry->fetch_assoc()) {
    $status = (int)$row['status'];
    $fullname = trim($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']);
    $contact = $row['contact'];
    $client_img = $row['client_img'];
    
    // Balance calculation
    $calc_sale = $row['total_sale'] ?? 0;
    $calc_billed = $row['total_billed'] ?? 0;
    $calc_paid = $row['total_paid'] ?? 0;
    $calc_opening = $row['opening_balance'] ?? 0;
    $calc_loan = $row['active_loan_balance'] ?? 0;
    $current_balance = ($calc_opening + $calc_billed + $calc_sale) - $calc_paid + $calc_loan;
    
    if($current_balance > 0){
        $bal_display = '<span class="badge badge-danger ml-1" style="font-size:0.7rem">Due: ₹' . number_format($current_balance, 2) . '</span>';
    } elseif($current_balance < 0) {
        $bal_display = '<span class="badge badge-success ml-1" style="font-size:0.7rem">Adv: ₹' . number_format(abs($current_balance), 2) . '</span>';
    } else {
        $bal_display = '<span class="badge badge-secondary ml-1" style="font-size:0.7rem">Bal: ₹0.00</span>';
    }
    
    // Avatar
    $avatar_html = '';
    if($client_img && file_exists('../../'.$client_img)) {
        $avatar_html = '<img src="../../'.$client_img.'" class="table-client-avatar">';
    }
    
    $date_display = '<div class="d-flex flex-column" style="line-height:1.3;"><small class="text-muted"><i class="fa fa-calendar-alt mr-1 text-primary"></i>' . date("d M Y", strtotime($row['date_created'])) . '</small><small class="text-muted"><i class="fa fa-clock mr-1 text-info"></i>' . date("h:i A", strtotime($row['date_created'])) . '</small></div>';
    
    $job_display = '<div class="d-flex flex-column" style="line-height:1.3;"><small class="text-primary font-weight-bold"><i class="fa fa-tag mr-1"></i>' . htmlspecialchars($row['job_id']) . '</small><small class="text-danger"><i class="fa fa-barcode mr-1"></i>' . htmlspecialchars(!empty($row['code']) ? $row['code'] : 'No Code') . '</small></div>';
    
    $client_display = '<div class="client-cell">' . $avatar_html . '<div class="d-flex flex-column" style="line-height:1.3;"><span class="font-weight-bold">' . htmlspecialchars($fullname) . '</span>' . $bal_display . '<small class="text-success">' . ($contact ? '<a href="https://wa.me/91'.preg_replace('/\D/', '', $contact).'" target="_blank" class="text-success"><i class="fab fa-whatsapp mr-1"></i>' . htmlspecialchars($contact) . '</a>' : '') . '</small></div></div>';
    
    $item_display = htmlspecialchars($row['item']);
    $fault_display = htmlspecialchars($row['fault']);
    $locate_display = htmlspecialchars($row['uniq_id']);
    $amount_display = '<span class="text-right font-weight-bold">₹' . number_format($row['amount'], 2) . '</span>';
    $status_display = '<span class="badge badge-' . $stat_colors[$status] . ' px-3">' . $stat_arr[$status] . '</span>';
    $action_display = '<button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">Action</button><div class="dropdown-menu" role="menu"><a class="dropdown-item" href="./?page=transactions/view_details&id=' . $row['id'] . '"><span class="fa fa-eye text-primary"></span> View</a><a class="dropdown-item" href="./?page=transactions/manage_transaction&id=' . $row['id'] . '"><span class="fa fa-edit text-primary"></span> Edit</a><a class="dropdown-item" href="./?page=transactions/manage_transaction&copy_id=' . $row['id'] . '"><span class="fa fa-copy text-primary"></span> Copy</a><div class="dropdown-divider"></div><a class="dropdown-item delete_data" href="javascript:void(0)" data-id="' . $row['id'] . '"><span class="fa fa-trash text-danger"></span> Delete</a></div>';
    
    $data[] = [
        'DT_RowId' => $row['id'],
        0 => $i++,
        1 => $date_display,
        2 => $job_display,
        3 => $client_display,
        4 => $item_display,
        5 => $fault_display,
        6 => $locate_display,
        7 => $amount_display,
        8 => $status_display,
        9 => $action_display
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total_records,
    'recordsFiltered' => $filtered_records,
    'data' => $data
]);