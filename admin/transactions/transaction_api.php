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

$where_conditions = [];
if(!empty($date_from) && !empty($date_to)) {
    $where_conditions[] = "date(t.date_created) BETWEEN '{$date_from}' AND '{$date_to}' ";
} elseif(!empty($date_from)) {
    $where_conditions[] = "date(t.date_created) >= '{$date_from}' ";
} elseif(!empty($date_to)) {
    $where_conditions[] = "date(t.date_created) <= '{$date_to}' ";
}

if(!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $where_conditions[] = "(c.firstname LIKE '%{$search_esc}%' OR c.lastname LIKE '%{$search_esc}%' OR c.contact LIKE '%{$search_esc}%' OR t.job_id LIKE '%{$search_esc}%' OR t.item LIKE '%{$search_esc}%' OR t.code LIKE '%{$search_esc}%') ";
}

$where_sql = "";
if(!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Get total count (without any filter)
$total_qry = $conn->query("SELECT COUNT(*) as total FROM transaction_list t INNER JOIN client_list c ON t.client_name = c.id");
$total_records = (int)$total_qry->fetch_assoc()['total'];

// Get filtered count
$filtered_qry = $conn->query("SELECT COUNT(*) as total FROM transaction_list t INNER JOIN client_list c ON t.client_name = c.id " . ($where_sql ? $where_sql : ""));
$filtered_records = (int)$filtered_qry->fetch_assoc()['total'];

// Column mapping for sorting
$columns = ['t.id', 't.date_created', 't.job_id', 'c.firstname', 't.item', 't.fault', 't.uniq_id', 't.amount', 't.status', 't.id'];
$order_by = isset($columns[$order_col]) ? $columns[$order_col] : 't.date_created';
$order_by .= " " . $order_dir;

// Get data
$sql = "SELECT t.*, c.firstname, c.middlename, c.lastname, c.contact, c.avatar, t.code 
        FROM `transaction_list` t 
        INNER JOIN client_list c ON t.client_name = c.id 
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
    $client_name = trim($row['firstname'] . ' ' . $row['middlename'] . ' ' . $row['lastname']);
    $contact = $row['contact'];
    $wa_link = '';
    if($contact) {
        $phone = preg_replace('/\D/', '', $contact);
        $wa_link = '<a href="https://wa.me/91' . $phone . '" target="_blank" class="text-success"><i class="fab fa-whatsapp mr-1"></i>' . htmlspecialchars($contact) . '</a>';
    }
    $date_display = '<div class="d-flex flex-column" style="line-height:1.3;"><small class="text-muted"><i class="fa fa-calendar-alt mr-1 text-primary"></i>' . date("d M Y", strtotime($row['date_created'])) . '</small><small class="text-muted"><i class="fa fa-clock mr-1 text-info"></i>' . date("h:i A", strtotime($row['date_created'])) . '</small></div>';
    $job_display = '<div class="d-flex flex-column" style="line-height:1.3;"><small class="text-primary font-weight-bold"><i class="fa fa-tag mr-1"></i>' . htmlspecialchars($row['job_id']) . '</small><small class="text-danger"><i class="fa fa-barcode mr-1"></i>' . htmlspecialchars(!empty($row['code']) ? $row['code'] : 'No Code') . '</small></div>';
    $client_display = '<div class="d-flex flex-column" style="line-height:1.3;"><span class="font-weight-bold">' . htmlspecialchars($client_name) . '</span><small class="text-success">' . $wa_link . '</small></div>';
    $amount_display = '<span class="text-right font-weight-bold">₹' . number_format($row['amount'], 2) . '</span>';
    $status_display = '<span class="badge badge-' . $stat_colors[$status] . ' px-3">' . $stat_arr[$status] . '</span>';
    $action_display = '<button type="button" class="btn btn-flat btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">Action</button><div class="dropdown-menu" role="menu"><a class="dropdown-item" href="./?page=transactions/view_details&id=' . $row['id'] . '"><span class="fa fa-eye text-primary"></span> View</a><a class="dropdown-item" href="./?page=transactions/manage_transaction&id=' . $row['id'] . '"><span class="fa fa-edit text-primary"></span> Edit</a><a class="dropdown-item" href="./?page=transactions/manage_transaction&copy_id=' . $row['id'] . '"><span class="fa fa-copy text-primary"></span> Copy</a><div class="dropdown-divider"></div><a class="dropdown-item delete_data" href="javascript:void(0)" data-id="' . $row['id'] . '"><span class="fa fa-trash text-danger"></span> Delete</a></div>';
    
    $data[] = [
        'DT_RowId' => $row['id'],
        0 => $i++,
        1 => $date_display,
        2 => $job_display,
        3 => $client_display,
        4 => htmlspecialchars($row['item']),
        5 => htmlspecialchars($row['fault']),
        6 => htmlspecialchars($row['uniq_id']),
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