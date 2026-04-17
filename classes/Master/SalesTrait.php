<?php
trait SalesTrait {
    function save_client(){
        if (!CsrfProtection::validatePOST()) return json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id', 'img', 'csrf_token'))){
                if(!is_numeric($v)) $v = $this->conn->real_escape_string($v);
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='{$v}' ";
            }
        }
        $contact_check = $this->conn->query("SELECT id FROM `client_list` WHERE contact = '{$contact}' AND delete_flag = 0 ".(!empty($id) ? " AND id != '{$id}'" : "")." ");
        if($contact_check->num_rows > 0) return json_encode(['status' => 'failed', 'msg' => 'This Mobile/Whatsapp number is already registered!']);
        if(!empty($email)){
            $email_check = $this->conn->query("SELECT id FROM `client_list` WHERE email = '{$email}' AND delete_flag = 0 ".(!empty($id) ? " AND id != '{$id}'" : "")." ");
            if($email_check->num_rows > 0) return json_encode(['status' => 'failed', 'msg' => 'This Email/Mobile is already registered!']);
        }
        if(empty($id)) $sql = "INSERT INTO `client_list` SET {$data}";
        else $sql = "UPDATE `client_list` SET {$data} WHERE id = '{$id}'";
        if($this->conn->query($sql)){
            $cid = empty($id) ? $this->conn->insert_id : $id;
            if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
                if(!is_dir(base_app."uploads/clients")) mkdir(base_app."uploads/clients", 0777, true);
                $ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
                $fname = 'uploads/clients/client' . str_pad($cid, 5, '0', STR_PAD_LEFT) . '.' . $ext;
                $old_path = $this->conn->query("SELECT image_path FROM client_list WHERE id = '{$cid}'")->fetch_array()['image_path'] ?? '';
                if(move_and_compress_uploaded_file($_FILES['img']['tmp_name'], base_app . $fname)){
                    $this->conn->query("UPDATE `client_list` SET `image_path` = '{$fname}' WHERE id = '{$cid}'");
                    if(!empty($old_path) && is_file(base_app.$old_path) && $old_path != $fname) unlink(base_app.$old_path);
                }
            }
            $this->log_activity(empty($id) ? "Added Client" : "Updated Client", "Clients", $cid, "Client Name: " . $firstname . " " . $lastname);
            $this->settings->set_flashdata('success', empty($id) ? "Client has been added successfully." : "Client has been updated successfully.");
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed', 'msg' => 'An error occurred while saving client.', 'err' => $this->conn->error]);
    }

    function delete_client(){
        extract($_POST);
        if($this->conn->query("UPDATE `client_list` set delete_flag = 1 where id = '{$id}'")){
            $this->log_activity("Deleted Client", "Clients", $id);
            $this->settings->set_flashdata('success', "Client has been deleted successfully.");
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed', 'error' => $this->conn->error]);
    }

    function save_transaction(){
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if(isset($_POST['amount']) && !is_numeric($_POST['amount'])) return json_encode(['status' => 'failed', 'msg' => 'Invalid amount']);
        if(isset($_POST['client_name']) && !is_numeric($_POST['client_name'])) return json_encode(['status' => 'failed', 'msg' => 'Invalid client']);
        if(isset($_POST['mechanic_id']) && $_POST['mechanic_id'] != '' && !is_numeric($_POST['mechanic_id'])) return json_encode(['status' => 'failed', 'msg' => 'Invalid mechanic']);
        
        if(empty($id)){
            $this->conn->query("SELECT last_job_id FROM job_id_counter FOR UPDATE");
            $counter_qry = $this->conn->query("SELECT last_job_id FROM job_id_counter");
            $new_job_id = ($counter_qry->num_rows > 0) ? $counter_qry->fetch_assoc()['last_job_id'] + 1 : 27652;
            $_POST['job_id'] = $new_job_id;
            $this->conn->query("UPDATE job_id_counter SET last_job_id = $new_job_id");
            $_POST['user_id'] = $this->settings->userdata('id');
            $prefix = date("Ymd"); $code = sprintf("%'.02d", 1);
            while(true){
                if($this->conn->query("SELECT * FROM `transaction_list` where code = '{$prefix}{$code}' ")->num_rows > 0) $code = sprintf("%'.02d", abs($code) + 1);
                else { $_POST['code'] = $prefix.$code; break; }
            }
        }

        $manual_comm = isset($_POST['mechanic_commission_amount']) ? (float)$_POST['mechanic_commission_amount'] : 0;
        if($manual_comm <= 0){
            $service_total = 0;
            if(isset($_POST['service_price']) && is_array($_POST['service_price'])) foreach($_POST['service_price'] as $s_price) $service_total += (float)$s_price;
            $m_id = isset($_POST['mechanic_id']) ? $_POST['mechanic_id'] : 0;
            $comm_percent = (float)($this->conn->query("SELECT commission_percent FROM mechanic_list WHERE id = '{$m_id}'")->fetch_assoc()['commission_percent'] ?? 0);
            $_POST['mechanic_commission_amount'] = ($service_total * $comm_percent) / 100;
        }

        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v){
            if(!in_array($k,array('id', 'csrf_token')) && !is_array($_POST[$k])){
                if(!empty($data)) $data .=",";
                $data .= " `{$k}`='".$this->conn->real_escape_string($v)."' ";
            }
        }
        if(isset($status) && $status == 5) $data .= ", `date_completed` = NOW() ";

        if(empty($id)) $sql = "INSERT INTO `transaction_list` set {$data} ";
        else $sql = "UPDATE `transaction_list` set {$data} where id = '{$id}' ";
        
        if(!$this->conn->query($sql)) return json_encode(['status' => 'failed', 'err' => $this->conn->error]);
        $tid = empty($id) ? $this->conn->insert_id : $id;
        
        $this->conn->query("DELETE FROM `transaction_services` WHERE transaction_id = '{$tid}'");
        if(isset($service_id) && is_array($service_id)){
            $s_data = "";
            foreach($service_id as $k => $v){
                if(!empty($s_data)) $s_data .= ", ";
                $s_data .= "('{$tid}', '{$v}', '".$this->conn->real_escape_string($service_price[$k])."')";
            }
            if(!empty($s_data)) $this->conn->query("INSERT INTO `transaction_services` (`transaction_id`, `service_id`, `price`) VALUES {$s_data}");
        }

        $this->conn->query("DELETE FROM `transaction_products` WHERE transaction_id = '{$tid}'");
        if(isset($product_id) && is_array($product_id)){
            $p_data = "";
            foreach($product_id as $k => $v){
                if(!empty($p_data)) $p_data .= ", ";
                $p_data .= "('{$tid}', '{$v}', '".$this->conn->real_escape_string($product_qty[$k])."', '".$this->conn->real_escape_string($product_price[$k])."')";
            }
            if(!empty($p_data)) $this->conn->query("INSERT INTO `transaction_products` (`transaction_id`, `product_id`, `qty`, `price`) VALUES {$p_data}");
        }

        if(isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = base_app . 'uploads/transactions/';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            foreach($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if($_FILES['images']['error'][$key] != UPLOAD_ERR_OK) continue;
                $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                $new_filename = 'job_' . $tid . '_' . time() . '_' . $key . '.' . $ext;
                if(move_and_compress_uploaded_file($tmp_name, $upload_dir . $new_filename)) {
                    $image_path = 'uploads/transactions/' . $new_filename;
                    $this->conn->query("INSERT INTO transaction_images (transaction_id, image_path) VALUES ('{$tid}', '{$image_path}')");
                }
            }
        }
        $this->log_activity(empty($id) ? "Created Transaction" : "Updated Transaction", "Transactions", $tid, "Job ID: " . ($_POST['job_id'] ?? '') . ", Amount: " . ($_POST['amount'] ?? '0'));
        $this->settings->set_flashdata('success', empty($id) ? "New Transaction successfully saved." : "Transaction successfully updated.");
        return json_encode(['status' => 'success', 'tid' => $tid]);
    }

    function delete_transaction(){
        $id = $this->conn->real_escape_string($_POST['id'] ?? '');
        if($this->conn->query("DELETE FROM transaction_list WHERE id = '{$id}'")){
            $this->log_activity("Deleted Transaction", "Transactions", $id);
            $this->settings->set_flashdata('success', "Transaction successfully deleted.");
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed', 'msg' => 'Delete failed', 'error' => $this->conn->error]);
    }

    function delete_transaction_image(){
        $id = $this->conn->real_escape_string($_POST['id'] ?? '');
        $qry = $this->conn->query("SELECT image_path FROM transaction_images WHERE id = '{$id}'");
        if($qry->num_rows > 0){
            $row = $qry->fetch_assoc();
            if(is_file(base_app . $row['image_path'])) unlink(base_app . $row['image_path']);
            $this->conn->query("DELETE FROM transaction_images WHERE id = '{$id}'");
            $this->settings->set_flashdata('success', "Photo successfully deleted.");
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed', 'msg' => 'Image not found']);
    }

    function update_status(){
        extract($_POST);
        $sql = "UPDATE `transaction_list` set `status` = '{$status}'" . ($status == 5 ? ", `date_completed` = NOW() " : "") . " where id = '{$id}'";
        if($this->conn->query($sql)) return json_encode(['status' => 'success', 'msg' => "Transaction Status successfully updated."]);
        return json_encode(['status' => 'failed', 'error' => $this->conn->error]);
    }

    function update_transaction_status(){
        extract($_POST);
        $date_update = ($status == 5) ? ", date_completed = '".($date_completed ?? date('Y-m-d H:i:s'))."' " : "";
        if($this->conn->query("UPDATE `transaction_list` set status = '{$status}' {$date_update} where id = '{$id}'")){
            $this->settings->set_flashdata('success'," Transaction's Status successfully updated.");
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed', 'error' => $this->conn->error]);
    }

    function search_products(){
        $term = $this->conn->real_escape_string($_GET['term']);
        $qry = $this->conn->query("SELECT id, name, price FROM product_list WHERE name LIKE '%$term%' AND delete_flag = 0 LIMIT 10");
        $data = [];
        while($row = $qry->fetch_assoc()) $data[] = ['id' => $row['id'], 'label' => $row['name'], 'price' => $row['price']];
        echo json_encode($data); exit;
    }

    function save_direct_sale(){
        extract($_POST);
        $user_mechanic_id = $this->settings->userdata('mechanic_id');
        if($this->settings->userdata('type') == 2) $_POST['mechanic_id'] = $user_mechanic_id;
        $data = "";
        foreach($_POST as $k => $v){
            if(!in_array($k,['id','product_id','qty','price','edited_by_user','edited_by_type','edited_by_mechanic_id','csrf_token'])){
                if(!empty($data)) $data .= ", ";
                $data .= "`$k`='".addslashes($v)."'";
            }
        }
        if(empty($id)){
            $data .= ", sale_code='DS-".date('Ymd')."-".rand(1000,9999)."'";
            $sql = "INSERT INTO direct_sales SET $data";
        }else{
            $data .= ", last_edited_by='".($edited_by_mechanic_id ?? 0)."', last_edited_date='".date('Y-m-d H:i:s')."'";
            $sql = "UPDATE direct_sales SET $data WHERE id = '{$id}'";
        }
        if($this->conn->query($sql)){
            $sid = empty($id) ? $this->conn->insert_id : $id;
            if(!empty($id)) $this->conn->query("DELETE FROM direct_sale_items WHERE sale_id = '{$id}'");
            $total = 0;
            for($i=0; $i<count($product_id); $i++){
                $total += ($qty[$i] * $price[$i]);
                $this->conn->query("INSERT INTO direct_sale_items (sale_id, product_id, qty, price) VALUES ('{$sid}', '{$product_id[$i]}', '{$qty[$i]}', '{$price[$i]}')");
            }
            $this->conn->query("UPDATE direct_sales SET total_amount = $total WHERE id = '{$sid}'");
            $this->settings->set_flashdata('success','Direct Sale saved successfully.');
            return json_encode(['status' => 'success', 'id' => $sid]);
        }
        return json_encode(['status' => 'failed', 'msg' => $this->conn->error]);
    }

    function delete_direct_sale(){
        extract($_POST);
        $this->conn->query("DELETE FROM direct_sale_items WHERE sale_id = '{$id}'");
        if($this->conn->query("DELETE FROM direct_sales WHERE id = '{$id}'")){
            $this->settings->set_flashdata('success','Direct Sale deleted successfully.');
            return json_encode(['status' => 'success']);
        }
        return json_encode(['status' => 'failed', 'error' => $this->conn->error]);
    }

    function get_client_balance(){
        extract($_POST);
        $opening = floatval($this->conn->query("SELECT opening_balance FROM client_list WHERE id = '{$id}'")->fetch_assoc()['opening_balance'] ?? 0);
        $total_billed = $this->conn->query("SELECT SUM(amount) as total FROM transaction_list WHERE client_name = '{$id}' AND status = 5")->fetch_assoc()['total'] ?? 0;
        $total_paid = $this->conn->query("SELECT SUM(amount + discount) as paid FROM client_payments WHERE client_id = '{$id}'")->fetch_assoc()['paid'] ?? 0;
        $balance = ($opening + $total_billed) - $total_paid;
        $resp = ['status' => 'success', 'balance' => number_format(abs($balance), 2, '.', ''), 'type' => ($balance > 0 ? 'due' : 'advance'), 'color' => ($balance > 0 ? '#dc3545' : '#28a745'), 'label' => ($balance > 0 ? 'Total Due Amount: ' : 'Advance Amount: ')];
        echo json_encode($resp); exit;
    }

    function save_multi_transaction(){
        // NOTE: $_POST['items'] is a JSON string. The global DBConnection sanitizer
        // (real_escape_string) escapes quotes inside it, breaking json_decode.
        // We must re-read it from the raw request body before parsing.
        $raw_body = file_get_contents('php://input');
        $raw_post = [];
        parse_str($raw_body, $raw_post);
        
        // Try raw body first, fall back to $_POST (in case called differently)
        $items_raw = $raw_post['items'] ?? $_POST['items'] ?? '[]';
        $items = json_decode($items_raw, true);

        $client_id = $this->conn->real_escape_string($_POST['client_name'] ?? '');

        if(empty($client_id) || empty($items) || !is_array($items)){
            return json_encode(['status' => 'failed', 'msg' => 'Invalid data']);
        }

        $this->conn->begin_transaction();
        try {
            $counter_row = $this->conn->query("SELECT last_job_id FROM job_id_counter FOR UPDATE")->fetch_assoc();
            $last_job_id = $counter_row ? (int)$counter_row['last_job_id'] : 27651;

            $today = date("Ymd");
            $max_code_row = $this->conn->query("SELECT MAX(code) as max_code FROM transaction_list WHERE code LIKE '{$today}%'")->fetch_assoc();
            $last_code = $max_code_row ? (int)substr($max_code_row['max_code'] ?? '0', -2) : 0;

            $saved = 0;
            foreach($items as $item){
                if(empty($item['item'])) continue;
                $last_code++;
                $last_job_id++;

                $mech_val = (!empty($item['mechanic_id']) && is_numeric($item['mechanic_id']))
                    ? "'".(int)$item['mechanic_id']."'"
                    : "NULL";

                $code    = $today . sprintf("%'.02d", $last_code);
                $item_e  = $this->conn->real_escape_string($item['item']   ?? '');
                $fault_e = $this->conn->real_escape_string($item['fault']  ?? '');
                $uniq_e  = $this->conn->real_escape_string($item['uniq_id'] ?? '');
                $rem_e   = $this->conn->real_escape_string($item['remark'] ?? '');
                $uid     = $this->settings->userdata('id');

                $sql = "INSERT INTO transaction_list 
                        (user_id, client_name, mechanic_id, code, job_id, item, fault, uniq_id, remark, status, date_created) 
                        VALUES ('{$uid}', '{$client_id}', {$mech_val}, '{$code}', '{$last_job_id}', '{$item_e}', '{$fault_e}', '{$uniq_e}', '{$rem_e}', 0, NOW())";

                if($this->conn->query($sql)) $saved++;
            }

            $this->conn->query("UPDATE job_id_counter SET last_job_id = '{$last_job_id}'");
            $this->conn->commit();

            if($saved > 0){
                $this->settings->set_flashdata('success', "{$saved} Job Sheets Created Successfully.");
                return json_encode(['status' => 'success', 'saved' => $saved]);
            }
            return json_encode(['status' => 'failed', 'msg' => 'No valid items found to save.']);

        } catch (Exception $e) {
            $this->conn->rollback();
            return json_encode(['status' => 'failed', 'msg' => 'DB Error: ' . $e->getMessage()]);
        }
    }
}
