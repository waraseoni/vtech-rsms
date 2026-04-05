<?php
require_once('../../config.php');

$search = isset($_POST['search']) ? $_POST['search'] : '';
$resp = array();

if(!empty($search)){
    $search = $conn->real_escape_string($search);
    
    // 1. Search Clients
    $clients = $conn->query("SELECT id, CONCAT(firstname, ' ', middlename, ' ', lastname) as name, contact, address, email FROM client_list WHERE delete_flag = 0 AND (firstname LIKE '%$search%' OR middlename LIKE '%$search%' OR lastname LIKE '%$search%' OR contact LIKE '%$search%' OR address LIKE '%$search%' OR email LIKE '%$search%') LIMIT 5");
    while($row = $clients->fetch_assoc()){
        $resp[] = array(
            'type' => 'Client',
            'title' => $row['name'],
            'subtitle' => $row['contact'] . ' | ' . $row['address'],
            'link' => './?page=clients/view_client&id=' . $row['id'],
            'icon' => 'fa-user'
        );
    }

    // 2. Search Transactions (Jobs)
    $transactions = $conn->query("SELECT id, job_id, code, item, fault, status FROM transaction_list WHERE (job_id LIKE '%$search%' OR code LIKE '%$search%' OR item LIKE '%$search%' OR fault LIKE '%$search%') LIMIT 5");
    while($row = $transactions->fetch_assoc()){
        $status_text = '';
        switch($row['status']){
            case 0: $status_text = 'Pending'; break;
            case 1: $status_text = 'On-Progress'; break;
            case 2: $status_text = 'Done'; break;
            case 3: $status_text = 'Paid'; break;
            case 4: $status_text = 'Cancelled'; break;
            case 5: $status_text = 'Delivered'; break;
        }
        $resp[] = array(
            'type' => 'Job',
            'title' => 'Job #' . $row['job_id'] . ' (' . $row['item'] . ')',
            'subtitle' => 'Code: ' . $row['code'] . ' | Status: ' . $status_text,
            'link' => './?page=transactions/view_details&id=' . $row['id'],
            'icon' => 'fa-exchange-alt'
        );
    }

    // 3. Search Products
    $products = $conn->query("SELECT id, name, description FROM product_list WHERE delete_flag = 0 AND (name LIKE '%$search%' OR description LIKE '%$search%') LIMIT 5");
    while($row = $products->fetch_assoc()){
        $resp[] = array(
            'type' => 'Product',
            'title' => $row['name'],
            'subtitle' => $row['description'],
            'link' => './?page=products',
            'icon' => 'fa-box'
        );
    }

    // 4. Search Services
    $services = $conn->query("SELECT id, name, description FROM service_list WHERE delete_flag = 0 AND (name LIKE '%$search%' OR description LIKE '%$search%') LIMIT 5");
    while($row = $services->fetch_assoc()){
        $resp[] = array(
            'type' => 'Service',
            'title' => $row['name'],
            'subtitle' => $row['description'],
            'link' => './?page=services',
            'icon' => 'fa-tools'
        );
    }
}

echo json_encode($resp);
?>
