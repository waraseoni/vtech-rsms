<?php
require_once('../config.php');
require_once('../classes/CsrfProtection.php');

Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function save_message(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id', 'csrf_token'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `message_list` set {$data} ";
		}else{
			$sql = "UPDATE `message_list` set {$data} where id = '{$id}' ";
		}
		
		$save = $this->conn->query($sql);
		if($save){
			$rid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if(empty($id))
				$resp['msg'] = "Your message has successfully sent.";
			else
				$resp['msg'] = "Message details has been updated successfully.";
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occured.";
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] =='success' && !empty($id))
		$this->settings->set_flashdata('success',$resp['msg']);
		if($resp['status'] =='success' && empty($id))
		$this->settings->set_flashdata('pop_msg',$resp['msg']);
		return json_encode($resp);
	}
	function delete_message(){
		extract($_POST);
		$stmt = $this->conn->prepare("DELETE FROM `message_list` WHERE id = ?");
		$stmt->bind_param("i", $id);
		$del = $stmt->execute();
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Message has been deleted successfully.");

		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function delete_img(){
		extract($_POST);
		if(is_file($path)){
			if(unlink($path)){
				$resp['status'] = 'success';
			}else{
				$resp['status'] = 'failed';
				$resp['error'] = 'failed to delete '.$path;
			}
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = 'Unkown '.$path.' path';
		}
		return json_encode($resp);
	}
	function save_service(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$v = $this->conn->real_escape_string($v);
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `service_list` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		// Prepared Statement version
		if(empty($id)){
			$chk_stmt = $this->conn->prepare("SELECT id FROM `service_list` WHERE `name` = ?");
			$chk_stmt->bind_param("s", $name);
		} else {
			$chk_stmt = $this->conn->prepare("SELECT id FROM `service_list` WHERE `name` = ? AND id != ?");
			$chk_stmt->bind_param("si", $name, $id);
		}
		$chk_stmt->execute();
		$chk_result = $chk_stmt->get_result();
		$check = $chk_result->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Service Name already exists.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `service_list` set {$data} ";
		}else{
			$sql = "UPDATE `service_list` set {$data} where id = '{$id}' ";
		}
			$save = $this->conn->query($sql);
		if($save){
			$bid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if(empty($id))
				$resp['msg'] = "New Service successfully saved.";
			else
				$resp['msg'] = " Service successfully updated.";
			
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success',$resp['msg']);
			return json_encode($resp);
	}
	function delete_service(){
		extract($_POST);
		$stmt = $this->conn->prepare("UPDATE `service_list` SET `delete_flag` = 1 WHERE id = ?");
		$stmt->bind_param("i", $id);
		$del = $stmt->execute();
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Service successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_mechanic(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id', 'csrf_token'))){
				if(!empty($data)) $data .=",";
				$v = $this->conn->real_escape_string($v);
				$data .= " `{$k}`='{$v}' ";
			}
		}
		}
		// save_mechanic function ke andar - Prepared Statement
if(isset($commission_percent)){
    $comm_stmt = $this->conn->prepare("INSERT INTO `mechanic_commission_history` (mechanic_id, commission_percent, effective_date) VALUES (?, ?, ?)");
    $eff_date = date('Y-m-d');
    $comm_stmt->bind_param("ids", $id, $commission_percent, $eff_date);
    $comm_stmt->execute();
}

		// Check karein ki kya ye naya staff hai ya purana edit ho raha hai
		if(empty($id)){
			$sql = "INSERT INTO `mechanic_list` set {$data} ";
		}else{
			// Purani salary check karein history ke liye - Prepared Statement
			$salary_stmt = $this->conn->prepare("SELECT daily_salary FROM mechanic_list WHERE id = ?");
			$salary_stmt->bind_param("i", $id);
			$salary_stmt->execute();
			$salary_result = $salary_stmt->get_result();
			$old_salary_row = $salary_result->fetch_array();
			$old_salary = $old_salary_row ? $old_salary_row['daily_salary'] : 0;
			$sql = "UPDATE `mechanic_list` set {$data} where id = '{$id}' ";
		}
		
		$save = $this->conn->query($sql);

		if($save){
			$mid = empty($id) ? $this->conn->insert_id : $id;
			$resp['status'] = 'success';

			// --- SALARY HISTORY LOGIC - Prepared Statement ---
			if(empty($id) || ($old_salary != $daily_salary)){
				$effective_date = date('Y-m-d');
				$hist_stmt = $this->conn->prepare("INSERT INTO `mechanic_salary_history` (mechanic_id, salary, effective_date) VALUES (?, ?, ?)");
				$hist_stmt->bind_param("ids", $mid, $daily_salary, $effective_date);
				$hist_stmt->execute();
			}

			if(empty($id))
				$this->settings->set_flashdata('success',"New Mechanic successfully saved.");
			else
				$this->settings->set_flashdata('success',"Mechanic Details successfully updated.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_mechanic(){
		extract($_POST);
		$stmt = $this->conn->prepare("UPDATE `mechanic_list` SET `delete_flag` = 1 WHERE id = ?");
		$stmt->bind_param("i", $id);
		$del = $stmt->execute();
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Mechanic successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	// ... (Master class ke andar, jahan sare functions hain) ...

	// =========================================================================
	// PRODUCT FUNCTIONS (Added Prepared Statements)
	// =========================================================================

	function save_product(){
		extract($_POST);
		$resp = array('status'=>'success');
		
		// Un-setting fields that are handled separately (like files/image)
		// ...

		if(empty($id)){
			// INSERT (Prepared Statement)
			$stmt = $this->conn->prepare("INSERT INTO `product_list` (`name`, `description`, `price`, `status`) VALUES (?, ?, ?, ?)");
			// 'ssd i' means: string, string, double/float, integer
			$stmt->bind_param("ssdi", $name, $description, $price, $status);
		} else {
			// UPDATE (Prepared Statement)
			$stmt = $this->conn->prepare("UPDATE `product_list` SET `name`=?, `description`=?, `price`=?, `status`=? WHERE id = ?");
			// 'ssdi i' means: string, string, double/float, integer, integer (for ID)
			$stmt->bind_param("ssdii", $name, $description, $price, $status, $id);
		}

		if($stmt->execute()){
			$resp['id'] = empty($id) ? $this->conn->insert_id : $id;
			$resp['msg'] = 'Product Details successfully saved.';
			
			// Image upload logic (if you have one)
			if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
				if(!is_dir(base_app.'uploads/products'))
					mkdir(base_app.'uploads/products');
				$ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
				$fname = 'uploads/products/'.$resp['id'].'.'.$ext;
				$move = move_uploaded_file($_FILES['img']['tmp_name'],base_app.$fname);
				
				if($move){
					$img_stmt = $this->conn->prepare("UPDATE `product_list` SET `image_path` = ? WHERE id = ?");
					$img_stmt->bind_param("si", $fname, $resp['id']);
					$img_stmt->execute();
					$img_stmt->close();
				}
			}
			
		} else {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Database Error: ' . $stmt->error;
		}
		$stmt->close();

		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}

	function delete_product(){
		extract($_POST);
		$resp = array('status'=>'success');
		
		// DELETE (Prepared Statement)
		$stmt = $this->conn->prepare("DELETE FROM `product_list` WHERE id = ?");
		$stmt->bind_param("i", $id);
		
		if($stmt->execute()){
			$resp['msg'] = 'Product Details successfully deleted.';
			// Optionally delete image file
			// $this->conn->query("DELETE FROM `product_list` where id = '{$id}' ");
		} else {
			$resp['status'] = 'failed';
			$resp['msg'] = 'Database Error: ' . $stmt->error;
		}
		$stmt->close();

		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}

// ... (rest of the Master class continues) ...
	function save_inventory(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id', 'csrf_token'))){
				if(!empty($data)) $data .=",";
				$v = $this->conn->real_escape_string($v);
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `inventory_list` set {$data} ";
		}else{
			$sql = "UPDATE `inventory_list` set {$data} where id = '{$id}' ";
		}
			$save = $this->conn->query($sql);
		if($save){
			$bid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			if(empty($id))
				$resp['msg'] = "New Stock has been saved successfully.";
			else
				$resp['msg'] = " Stock has been updated successfully.";
			
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success',$resp['msg']);
			return json_encode($resp);
	}
	function delete_inventory(){
		extract($_POST);
		$stmt = $this->conn->prepare("DELETE FROM `inventory_list` WHERE id = ?");
		$stmt->bind_param("i", $id);
		$del = $stmt->execute();
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Stock has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}	
	function save_client(){
    extract($_POST);
    $data = "";
    foreach($_POST as $k =>$v){
        if(!in_array($k,array('id', 'csrf_token'))){
            if(!is_numeric($v))
                $v = $this->conn->real_escape_string($v);
            if(!empty($data)) $data .=",";
            $data .= " `{$k}`='{$v}' ";
        }
    }

    // ====== DUPLICATE MOBILE CHECK - Prepared Statement ======
    if(empty($id)){
        $contact_stmt = $this->conn->prepare("SELECT id FROM `client_list` WHERE contact = ? AND delete_flag = 0");
        $contact_stmt->bind_param("s", $contact);
    } else {
        $contact_stmt = $this->conn->prepare("SELECT id FROM `client_list` WHERE contact = ? AND delete_flag = 0 AND id != ?");
        $contact_stmt->bind_param("si", $contact, $id);
    }
    $contact_stmt->execute();
    $contact_result = $contact_stmt->get_result();
    if($contact_result->num_rows > 0){
        echo json_encode(['status' => 'failed', 'msg' => 'This Mobile/Whatsapp number is already registered!']);
        exit;
    }

    // ====== DUPLICATE EMAIL CHECK - Prepared Statement ======
    if(!empty($email)){
        if(empty($id)){
            $email_stmt = $this->conn->prepare("SELECT id FROM `client_list` WHERE email = ? AND delete_flag = 0");
            $email_stmt->bind_param("s", $email);
        } else {
            $email_stmt = $this->conn->prepare("SELECT id FROM `client_list` WHERE email = ? AND delete_flag = 0 AND id != ?");
            $email_stmt->bind_param("si", $email, $id);
        }
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        if($email_result->num_rows > 0){
            echo json_encode(['status' => 'failed', 'msg' => 'This Email/Mobile is already registered!']);
            exit;
        }
    }

    // ====== अगर सब ठीक है तो Save करो ======
    if(empty($id)){
        $sql = "INSERT INTO `client_list` SET {$data}";
    } else {
        $sql = "UPDATE `client_list` SET {$data} WHERE id = '{$id}'";
    }

    $save = $this->conn->query($sql);
    if($save){
        $resp['status'] = 'success';
        $resp['msg'] = empty($id) ? "Client has been added successfully." : "Client has been updated successfully.";
        $this->settings->set_flashdata('success', $resp['msg']);
    } else {
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred while saving client.';
        $resp['err'] = $this->conn->error;
    }

    echo json_encode($resp);
    exit;
}
	function delete_client(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `client_list` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Client has been deleted successfully.");

		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_transaction(){
		// CSRF Validation
		if (!CsrfProtection::validatePOST()) {
			return json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
		}
		
    // Form se id nikalna (Edit mode ke liye)
    $id = isset($_POST['id']) ? $_POST['id'] : '';

    // ==========================================================
    // UPDATED: DATE AND CODE LOGIC START
    // ==========================================================
    // Agar form se date aayi hai toh wo, nahi toh current date
    $chosen_date = !empty($_POST['date_created']) ? $_POST['date_created'] : date("Y-m-d H:i:s");
    $_POST['date_created'] = $chosen_date; 

    // Only for new transaction (jab $id empty ho) - Prepared Statement
    if(empty($id)){
        $_POST['user_id'] = $this->settings->userdata('id');
        
        $prefix = date("Ymd", strtotime($chosen_date)); 
        $code = sprintf("%'.02d", 1);
        while(true){
            $code_check_stmt = $this->conn->prepare("SELECT id FROM `transaction_list` WHERE code = ?");
            $full_code = $prefix . $code;
            $code_check_stmt->bind_param("s", $full_code);
            $code_check_stmt->execute();
            $code_result = $code_check_stmt->get_result();
            if($code_result->num_rows > 0){
                $code = sprintf("%'.02d", abs($code) + 1);
            }else{
                $_POST['code'] = $prefix.$code;
                break;
            }
        }
    }
    // ==========================================================
    // DATE AND CODE LOGIC END
    // ==========================================================

    // ==========================================================
    // NEW: SERVICE-ONLY COMMISSION CALCULATION LOGIC START
    // ==========================================================
    
    // Check karo agar Admin ne manually commission dala hai (Form se aayega)
    $manual_comm = isset($_POST['mechanic_commission_amount']) ? (float)$_POST['mechanic_commission_amount'] : 0;
    
    // Agar manually nahi dala gaya ya Admin ne field khali chhodi hai, to auto-calculate karein
    if($manual_comm <= 0){
        $service_total = 0;
        if(isset($_POST['service_price']) && is_array($_POST['service_price'])){
            foreach($_POST['service_price'] as $s_price){
                $service_total += (float)$s_price;
            }
        }
        
        // Mechanic ka commission percentage nikalna - Prepared Statement
        $m_id = isset($_POST['mechanic_id']) ? $_POST['mechanic_id'] : 0;
        $mech_stmt = $this->conn->prepare("SELECT commission_percent FROM mechanic_list WHERE id = ?");
        $mech_stmt->bind_param("i", $m_id);
        $mech_stmt->execute();
        $mech_result = $mech_stmt->get_result();
        $mech_data = $mech_result->fetch_assoc();
        $comm_percent = isset($mech_data['commission_percent']) ? (float)$mech_data['commission_percent'] : 0;
        
        // Final Commission Calculation (Sirf service par)
        $final_commission = ($service_total * $comm_percent) / 100;
        $_POST['mechanic_commission_amount'] = $final_commission;
    } else {
        // Agar Admin ne manual entry ki hai, to wahi rakhein
        $_POST['mechanic_commission_amount'] = $manual_comm;
    }
    // ==========================================================
    // NEW: COMMISSION LOGIC END
    // ==========================================================

    extract($_POST);
    $tid = !empty($id) ? $id : '';

    // Data string prepare karna
    $data = "";
    foreach($_POST as $k =>$v){
        if(!in_array($k,array('id', 'csrf_token')) && !is_array($_POST[$k])){
            if(!empty($data)) $data .=",";
            $v = $this->conn->real_escape_string($v);
            $data .= " `{$k}`='{$v}' ";
        }
    }

    if(empty($id)){
        $sql = "INSERT INTO `transaction_list` set {$data} ";
    }else{
        $sql = "UPDATE `transaction_list` set {$data} where id = '{$id}' ";
    }

    $save = $this->conn->query($sql);
    if(!$save){
        $resp['status'] = 'failed';
        $resp['err'] = $this->conn->error;
        return json_encode($resp);
    }

    $tid = empty($id) ? $this->conn->insert_id : $id;
    $resp['tid'] = $tid;
    $resp['status'] = 'success';

    // === SERVICES SAVE - Prepared Statement ===
    $del_svc_stmt = $this->conn->prepare("DELETE FROM `transaction_services` WHERE transaction_id = ?");
    $del_svc_stmt->bind_param("i", $tid);
    $del_svc_stmt->execute();
    
    if(isset($service_id) && is_array($service_id)){
        $svc_stmt = $this->conn->prepare("INSERT INTO `transaction_services` (`transaction_id`, `service_id`, `price`) VALUES (?, ?, ?)");
        foreach($service_id as $k => $v){
            $price = $service_price[$k];
            $svc_stmt->bind_param("iid", $tid, $v, $price);
            $svc_stmt->execute();
        }
    }

    // === PRODUCTS SAVE - Prepared Statement ===
    $del_prod_stmt = $this->conn->prepare("DELETE FROM `transaction_products` WHERE transaction_id = ?");
    $del_prod_stmt->bind_param("i", $tid);
    $del_prod_stmt->execute();
    
    if(isset($product_id) && is_array($product_id)){
        $prod_stmt = $this->conn->prepare("INSERT INTO `transaction_products` (`transaction_id`, `product_id`, `qty`, `price`) VALUES (?, ?, ?, ?)");
        foreach($product_id as $k => $v){
            $pid = $v;
            $qty = $product_qty[$k];
            $price = $product_price[$k];
            $prod_stmt->bind_param("iidd", $tid, $pid, $qty, $price);
            if(!$prod_stmt->execute()){
                $resp['status'] = 'failed';
                $resp['msg'] = 'Failed to save products.';
                return json_encode($resp);
            }
        }
    }
	
    // ==================== IMAGE UPLOAD START ====================
    if(isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $upload_dir = base_app . 'uploads/transactions/';
        if(!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 10 * 1024 * 1024; 
        foreach($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if($_FILES['images']['error'][$key] != UPLOAD_ERR_OK) continue;
            $file_type = $_FILES['images']['type'][$key];
            $file_size = $_FILES['images']['size'][$key];
            $original_name = $_FILES['images']['name'][$key];
            if(!in_array($file_type, $allowed_types)) continue;
            if($file_size > $max_size) continue;
            $ext = pathinfo($original_name, PATHINFO_EXTENSION);
            $new_filename = 'job_' . $tid . '_' . time() . '_' . $key . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            if(move_uploaded_file($tmp_name, $destination)) {
                $image_path = 'uploads/transactions/' . $new_filename;
                $img_stmt = $this->conn->prepare("INSERT INTO transaction_images (transaction_id, image_path) VALUES (?, ?)");
                $img_stmt->bind_param("is", $tid, $image_path);
                $img_stmt->execute();
            }
        }
    }
    // ==================== IMAGE UPLOAD END ====================

    if(empty($id))
        $resp['msg'] = "New Transaction successfully saved.";
    else
        $resp['msg'] = "Transaction successfully updated.";

    $this->settings->set_flashdata('success', $resp['msg']);
    return json_encode($resp);
}
	function delete_transaction(){
    if(!isset($_POST['id']) || empty($_POST['id'])){
        $resp['status'] = 'failed';
        $resp['msg'] = 'Invalid transaction ID';
        return json_encode($resp);
    }

    $id = intval($_POST['id']);

    // Check if transaction exists - Prepared Statement
    $check_stmt = $this->conn->prepare("SELECT id FROM transaction_list WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if($check_result->num_rows == 0){
        $resp['status'] = 'failed';
        $resp['msg'] = 'Transaction not found';
        return json_encode($resp);
    }

    $del_stmt = $this->conn->prepare("DELETE FROM transaction_list WHERE id = ?");
    $del_stmt->bind_param("i", $id);
    $del = $del_stmt->execute();

    if($del){
        $resp['status'] = 'success';
        $this->settings->set_flashdata('success', "Transaction successfully deleted.");
    } else {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Delete failed';
        $resp['error'] = $this->conn->error;
    }

    return json_encode($resp);
}
	function delete_transaction_image(){
    if(!isset($_POST['id']) || empty($_POST['id'])){
        $resp['status'] = 'failed';
        $resp['msg'] = 'Invalid image ID';
        return json_encode($resp);
    }

    $id = intval($_POST['id']);

    // Image path fetch - Prepared Statement
    $img_stmt = $this->conn->prepare("SELECT image_path FROM transaction_images WHERE id = ?");
    $img_stmt->bind_param("i", $id);
    $img_stmt->execute();
    $img_result = $img_stmt->get_result();
    if($img_result->num_rows == 0){
        $resp['status'] = 'failed';
        $resp['msg'] = 'Image not found';
        return json_encode($resp);
    }

    $row = $img_result->fetch_assoc();
    $file_path = base_app . $row['image_path'];

    // Delete physical file
    if(is_file($file_path)){
        unlink($file_path);
    }

    // Delete from database - Prepared Statement
    $del_stmt = $this->conn->prepare("DELETE FROM transaction_images WHERE id = ?");
    $del_stmt->bind_param("i", $id);
    $del = $del_stmt->execute();

    if($del){
        $resp['status'] = 'success';
        $this->settings->set_flashdata('success', "Photo successfully deleted.");
    } else {
        $resp['status'] = 'failed';
        $resp['msg'] = 'Database delete failed';
        $resp['error'] = $this->conn->error;
    }

    return json_encode($resp);
}

	function update_status(){
		extract($_POST);
		$stmt = $this->conn->prepare("UPDATE `transaction_list` SET `status` = ? WHERE id = ?");
		$stmt->bind_param("ii", $status, $id);
		$update = $stmt->execute();
		if($update){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "Transaction's status has failed to update.";
		}
		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success', 'Transaction\'s Status has been updated successfully.');
		return json_encode($resp);
	}
	function search_products(){
    $term = $_GET['term'];
    $search_term = "%{$term}%";
    $stmt = $this->conn->prepare("SELECT id, name, price FROM product_list WHERE name LIKE ? AND delete_flag = 0 LIMIT 10");
    $stmt->bind_param("s", $search_term);
    $stmt->execute();
    $qry = $stmt->get_result();
    $data = [];
    while($row = $qry->fetch_assoc()){
        $data[] = [
            'id' => $row['id'],
            'label' => $row['name'],
            'price' => $row['price']
        ];
    }
    echo json_encode($data);
    exit;
}

function save_direct_sale(){
    extract($_POST);
    $data = "";
    foreach($_POST as $k => $v){
        if(!in_array($k,['id','product_id','qty','price'])){
            if(!empty($data)) $data .= ", ";
            $data .= "`$k`='".addslashes($v)."'";
        }
    }
    
    if(empty($id)){
        $code = 'DS-'.date('Ymd').'-'.rand(1000,9999);
        $data .= ", sale_code='$code'";
        $sql = "INSERT INTO direct_sales SET $data";
    }else{
        $sql = "UPDATE direct_sales SET $data WHERE id = $id";
    }
    
    $save = $this->conn->query($sql);
    if($save){
        $sale_id = empty($id) ? $this->conn->insert_id : $id;
        
        // Delete old items if update - Prepared Statement
        if(!empty($id)){
            $del_stmt = $this->conn->prepare("DELETE FROM direct_sale_items WHERE sale_id = ?");
            $del_stmt->bind_param("i", $id);
            $del_stmt->execute();
        }
        
        // Save items - Prepared Statement
        $total = 0;
        $item_stmt = $this->conn->prepare("INSERT INTO direct_sale_items (sale_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
        for($i=0; $i<count($product_id); $i++){
            $pid = intval($product_id[$i]);
            $qty = floatval($qty[$i]);
            $price = floatval($price[$i]);
            $item_total = $qty * $price;
            $total += $item_total;
            
            $item_stmt->bind_param("iidd", $sale_id, $pid, $qty, $price);
            $item_stmt->execute();
        }
        
        // Update total - Prepared Statement
        $total_stmt = $this->conn->prepare("UPDATE direct_sales SET total_amount = ? WHERE id = ?");
        $total_stmt->bind_param("di", $total, $sale_id);
        $total_stmt->execute();
        
        $resp['status'] = 'success';
        $resp['id'] = $sale_id;
        $this->settings->set_flashdata('success','Direct Sale saved successfully.');
    }else{
        $resp['status'] = 'failed';
        $resp['msg'] = 'An error occurred';
    }
    return json_encode($resp);
}

function delete_direct_sale(){
    extract($_POST);
    $id = intval($id);
    $stmt = $this->conn->prepare("DELETE FROM direct_sales WHERE id = ?");
    $stmt->bind_param("i", $id);
    $del = $stmt->execute();
    if($del){
        $resp['status'] = 'success';
        $this->settings->set_flashdata('success','Direct Sale deleted successfully.');
    }else{
        $resp['status'] = 'failed';
    }
    return json_encode($resp);
}
function get_client_balance(){
    extract($_POST);
    header('Content-Type: application/json');
    $id = intval($id);
    
    // 1. Opening Balance (Table: client_list) - Prepared Statement
    $client_stmt = $this->conn->prepare("SELECT opening_balance FROM client_list WHERE id = ?");
    $client_stmt->bind_param("i", $id);
    $client_stmt->execute();
    $client_result = $client_stmt->get_result();
    $opening = 0;
    if($client_result->num_rows > 0){
        $opening = floatval($client_result->fetch_assoc()['opening_balance']);
    }

    // 2. Total Billed (Status 5 = Delivered) - Prepared Statement
    $billed_stmt = $this->conn->prepare("SELECT SUM(amount) as total FROM transaction_list WHERE client_name = ? AND status = 5");
    $billed_stmt->bind_param("i", $id);
    $billed_stmt->execute();
    $billed_result = $billed_stmt->get_result();
    $billed_row = $billed_result->fetch_assoc();
    $total_billed = floatval($billed_row['total'] ?? 0);

    // 3. Total Paid - Prepared Statement
    $paid_stmt = $this->conn->prepare("SELECT SUM(amount - discount) as paid FROM client_payments WHERE client_id = ?");
    $paid_stmt->bind_param("i", $id);
    $paid_stmt->execute();
    $paid_result = $paid_stmt->get_result();
    $paid_row = $paid_result->fetch_assoc();
    $total_paid = floatval($paid_row['paid'] ?? 0);

    // Final Calculation: (Opening + Bills) - Payments
    $balance = ($opening + $total_billed) - $total_paid;

    $resp['status'] = 'success';
    if($balance > 0){
        $resp['balance'] = number_format($balance, 2, '.', '');
        $resp['type'] = 'due';
        $resp['color'] = '#dc3545'; // Red
        $resp['label'] = 'Total Due Amount: ';
    } else {
        $resp['balance'] = number_format(abs($balance), 2, '.', '');
        $resp['type'] = 'advance';
        $resp['color'] = '#28a745'; // Green
        $resp['label'] = 'Advance Amount: ';
    }
    echo json_encode($resp);
    exit;
}
//function get_client_balance(){
 //   extract($_POST);
 //   header('Content-Type: application/json'); // Yeh line zaroori hai
 //   
 //   try {
        // 1. Opening Balance
//        $client_qry = $this->conn->query("SELECT opening_balance FROM client_list WHERE id = '{$id}'");
//        $opening = ($client_qry && $client_qry->num_rows > 0) ? floatval($client_qry->fetch_assoc()['opening_balance']) : 0;

        // 2. Total Billed (Status 5 = Delivered)
//        $billed_qry = $this->conn->query("SELECT SUM(amount) as total FROM transaction_list WHERE client_name = '{$id}' AND status = 5");
//        $total_billed = $billed_qry->fetch_assoc()['total'] ?? 0;
//
        // 3. Total Paid (Amount - Discount)
//        $paid_qry = $this->conn->query("SELECT SUM(amount - discount) as paid FROM client_payments WHERE client_id = '{$id}'");
//        $total_paid = $paid_qry->fetch_assoc()['paid'] ?? 0;
//
//        $balance = ($opening + $total_billed) - $total_paid;
//
//        $resp['status'] = 'success';
 //       if($balance > 0){
//            $resp['balance'] = number_format($balance, 2, '.', '');
//            $resp['type'] = 'due';
//            $resp['color'] = '#dc3545'; // Red color hex
//            $resp['label'] = 'Total Due: ';
//        } else {
//            $resp['balance'] = number_format(abs($balance), 2, '.', '');
//            $resp['type'] = 'advance';
//            $resp['color'] = '#28a745'; // Green color hex
//            $resp['label'] = 'Advance: ';
//        }
//        echo json_encode($resp);
//
//    } catch (Exception $e) {
//        echo json_encode(['status' => 'failed', 'msg' => $e->getMessage()]);
//    }
//    exit;
//}
function create_backup(){
    $resp = array();
    $backup_dir = __DIR__ . "/backups/"; // सही path - classes folder से relative
    if(!is_dir($backup_dir)){
        if(!mkdir($backup_dir, 0777, true)){
            $resp['status'] = 'failed';
            $resp['msg'] = 'Cannot create backup folder.';
            return json_encode($resp);
        }
    }
    
    $filename = "vikram_db_backup_".date("Y-m-d_H-i-s").".sql";
    $filepath = $backup_dir . $filename;
    
    // Simple test write
    if(!is_writable($backup_dir)){
        $resp['status'] = 'failed';
        $resp['msg'] = 'Backup folder is not writable. Check permissions.';
        return json_encode($resp);
    }
    
    $sql = "-- VTech-RSMS Backup\n-- ".date("Y-m-d H:i:s")."\n\n";
    
    $tables = [];
    $result = $this->conn->query("SHOW TABLES");
    while($row = $result->fetch_row()){
        $tables[] = $row[0];
    }
    
    foreach($tables as $table){
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        
        $create = $this->conn->query("SHOW CREATE TABLE `$table`");
        $row_create = $create->fetch_row();
        $sql .= $row_create[1] . ";\n\n";
        
        $data = $this->conn->query("SELECT * FROM `$table`");
        while($row = $data->fetch_row()){
            $sql .= "INSERT INTO `$table` VALUES (";
            foreach($row as $k => $v){
                $v = addslashes($v);
                $sql .= "'$v'";
                if($k < count($row)-1) $sql .= ", ";
            }
            $sql .= ");\n";
        }
        $sql .= "\n";
    }
    
    if(file_put_contents($filepath, $sql) !== false){
        $resp['status'] = 'success';
        $resp['msg'] = 'Backup created: ' . $filename;
        $this->settings->set_flashdata('success', $resp['msg']);
    }else{
        $resp['status'] = 'failed';
        $resp['msg'] = 'Failed to save file. Check disk space or permissions.';
    }
    
    return json_encode($resp);
}

function delete_backup(){
    extract($_POST);
    $backup_dir = dirname(__FILE__,2) . "/backup/backups/";
    $file = $backup_dir . $file;
    if(file_exists($file)){
        unlink($file);
        $resp['status'] = 'success';
        $this->settings->set_flashdata('success','Backup deleted successfully.');
    }else{
        $resp['status'] = 'failed';
    }
    return json_encode($resp);
}		
		function save_settings(){
    extract($_POST);
    $resp = array('status' => 'success', 'msg' => 'Settings updated successfully.');

    // === STEP 1: Text Fields Update Karo (Direct SQL se system_info mein) ===
    $update_data = array(
        'name' => $name,
        'short_name' => $short_name,
        'email' => $email,
        'contact' => $contact,
        'address' => $address
    );

    foreach($update_data as $key => $value){
        // Check if key already exists
        $check_stmt = $this->conn->prepare("SELECT * FROM system_info WHERE meta_field = ?");
        $check_stmt->bind_param("s", $key);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $check_stmt->close();

        $value_esc = $this->conn->real_escape_string($value);  // Escape for safety

        if($result->num_rows > 0){
            // Update
            $update_stmt = $this->conn->prepare("UPDATE system_info SET meta_value = ? WHERE meta_field = ?");
            $update_stmt->bind_param("ss", $value_esc, $key);
            if(!$update_stmt->execute()){
                $resp['status'] = 'failed';
                $resp['msg'] = 'Failed to update setting: ' . $key . ' - ' . $update_stmt->error;
                $update_stmt->close();
                return json_encode($resp);
            }
            $update_stmt->close();
        } else {
            // Insert
            $insert_stmt = $this->conn->prepare("INSERT INTO system_info (meta_field, meta_value) VALUES (?, ?)");
            $insert_stmt->bind_param("ss", $key, $value_esc);
            if(!$insert_stmt->execute()){
                $resp['status'] = 'failed';
                $resp['msg'] = 'Failed to insert setting: ' . $key . ' - ' . $insert_stmt->error;
                $insert_stmt->close();
                return json_encode($resp);
            }
            $insert_stmt->close();
        }
    }

    // === STEP 2: Content Files Save Karo (Welcome aur About) ===
    if(isset($content) && is_array($content)){
        if(isset($content['welcome'])){
            if(file_put_contents(base_app . 'welcome.html', $content['welcome']) === false){
                $resp['status'] = 'failed';
                $resp['msg'] = 'Failed to save welcome content.';
                return json_encode($resp);
            }
        }
        if(isset($content['about'])){
            if(file_put_contents(base_app . 'about.html', $content['about']) === false){
                $resp['status'] = 'failed';
                $resp['msg'] = 'Failed to save about content.';
                return json_encode($resp);
            }
        }
    }

    // === STEP 3: Logo Upload Handle Karo (Single File) ===
    if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
        $upload_dir = base_app . 'uploads/';
        if(!is_dir($upload_dir)) {
            if(!mkdir($upload_dir, 0777, true)){
                $resp['status'] = 'failed';
                $resp['msg'] = 'Failed to create uploads directory.';
                return json_encode($resp);
            }
        }

        $ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
        $fname = 'uploads/logo.' . $ext;  // Fixed name (overwrite purana)
        $move = move_uploaded_file($_FILES['img']['tmp_name'], $upload_dir . basename($fname));

        if($move){
            // Purani logo delete karo agar alag hai (optional)
            $old_logo = $this->settings->info('logo');
            if(!empty($old_logo) && file_exists(base_app . $old_logo) && $old_logo != $fname){
                unlink(base_app . $old_logo);
            }

            // Database mein update (same as above)
            $this->update_system_info('logo', $fname, $resp);  // Helper function call (neeche define karo)
            if($resp['status'] == 'failed') return json_encode($resp);
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to upload logo. Check file permissions or size.';
            return json_encode($resp);
        }
    }

    // === STEP 4: Cover Upload Handle Karo (Similar to Logo) ===
    if(isset($_FILES['cover']) && $_FILES['cover']['tmp_name'] != ''){
        $upload_dir = base_app . 'uploads/';
        if(!is_dir($upload_dir)) {
            if(!mkdir($upload_dir, 0777, true)){
                $resp['status'] = 'failed';
                $resp['msg'] = 'Failed to create uploads directory.';
                return json_encode($resp);
            }
        }

        $ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
        $fname = 'uploads/cover.' . $ext;  // Fixed name
        $move = move_uploaded_file($_FILES['cover']['tmp_name'], $upload_dir . basename($fname));

        if($move){
            // Purani cover delete
            $old_cover = $this->settings->info('cover');
            if(!empty($old_cover) && file_exists(base_app . $old_cover) && $old_cover != $fname){
                unlink(base_app . $old_cover);
            }

            // Database mein update
            $this->update_system_info('cover', $fname, $resp);
            if($resp['status'] == 'failed') return json_encode($resp);
        } else {
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to upload cover. Check file permissions or size.';
            return json_encode($resp);
        }
    }

    // === STEP 5: Banners Upload Handle Karo (Multiple, No DB) ===
    $banner_dir = base_app . 'uploads/banner/';
    if(!is_dir($banner_dir)) {
        if(!mkdir($banner_dir, 0777, true)){
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to create banner directory.';
            return json_encode($resp);
        }
    }

    if(isset($_FILES['banners']) && count($_FILES['banners']['tmp_name']) > 0){
        for($i = 0; $i < count($_FILES['banners']['tmp_name']); $i++){
            if(!empty($_FILES['banners']['tmp_name'][$i])){
                $ext = pathinfo($_FILES['banners']['name'][$i], PATHINFO_EXTENSION);
                $fname = time() . '_' . str_replace(' ', '_', $_FILES['banners']['name'][$i]);  // Unique name
                $move = move_uploaded_file($_FILES['banners']['tmp_name'][$i], $banner_dir . $fname);

                if(!$move){
                    $resp['status'] = 'failed';
                    $resp['msg'] = 'Failed to upload banner: ' . $_FILES['banners']['name'][$i] . '. Check permissions.';
                    return json_encode($resp);
                }
            }
        }
    }

    // === Success: Flashdata Set Karo ===
    if($resp['status'] == 'success'){
        $this->settings->set_flashdata('success', $resp['msg']);
    }

    return json_encode($resp);
}

// Helper Function (Master class mein add karo, save_settings ke upar ya neeche)
function update_system_info($key, $value, &$resp){
    $check_stmt = $this->conn->prepare("SELECT * FROM system_info WHERE meta_field = ?");
    $check_stmt->bind_param("s", $key);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $check_stmt->close();

    $value_esc = $this->conn->real_escape_string($value);

    if($result->num_rows > 0){
        $update_stmt = $this->conn->prepare("UPDATE system_info SET meta_value = ? WHERE meta_field = ?");
        $update_stmt->bind_param("ss", $value_esc, $key);
        if(!$update_stmt->execute()){
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to update ' . $key . ': ' . $update_stmt->error;
        }
        $update_stmt->close();
    } else {
        $insert_stmt = $this->conn->prepare("INSERT INTO system_info (meta_field, meta_value) VALUES (?, ?)");
        $insert_stmt->bind_param("ss", $key, $value_esc);
        if(!$insert_stmt->execute()){
            $resp['status'] = 'failed';
            $resp['msg'] = 'Failed to insert ' . $key . ': ' . $insert_stmt->error;
        }
        $insert_stmt->close();
    }
}
function get_status_by_contact(){
    extract($_POST);
    // Mobile number se client ki latest transaction dhundhna - Prepared Statement
    $stmt = $this->conn->prepare("SELECT t.*, c.contact 
                               FROM transaction_list t 
                               INNER JOIN client_list c ON t.client_name = c.id 
                               WHERE c.contact = ? 
                               ORDER BY t.date_created DESC LIMIT 1");
    $stmt->bind_param("s", $contact);
    $stmt->execute();
    $qry = $stmt->get_result();
    
    if($qry->num_rows > 0){
        $res = $qry->fetch_assoc();
        return json_encode(['status' => 'success', 'code' => $res['code']]);
    } else {
        return json_encode(['status' => 'failed', 'msg' => 'No record found for this mobile number.']);
    }
}
// Expense save karne ke liye
function save_expense(){
    extract($_POST);
    $data = "";
    foreach($_POST as $k =>$v){
        if(!in_array($k, array('id'))){
            if(!empty($data)) $data .=",";
            $v = $this->conn->real_escape_string($v);
            $data .= " `{$k}`='{$v}' ";
        }
    }
    if(empty($id)){
        $sql = "INSERT INTO `expense_list` set {$data}";
    }else{
        $sql = "UPDATE `expense_list` set {$data} where id = '{$id}'";
    }
    $save = $this->conn->query($sql);
    if($save){
        return json_encode(['status'=>'success']);
    }else{
        return json_encode(['status'=>'failed', 'error'=>$this->conn->error]);
    }
}

// Expense delete karne ke liye - Prepared Statement
function delete_expense(){
    extract($_POST);
    $id = intval($id);
    $stmt = $this->conn->prepare("DELETE FROM `expense_list` WHERE id = ?");
    $stmt->bind_param("i", $id);
    $del = $stmt->execute();
    if($del){
        return json_encode(['status'=>'success']);
    }else{
        return json_encode(['status'=>'failed']);
    }
}
function save_multi_transaction(){
    // Response array banate hain (jaise baaki functions mein)
    $resp = array('status' => 'failed', 'msg' => 'No items processed');

    // Client ID aur items ko safely extract karo
    if(!isset($_POST['client_name']) || empty($_POST['client_name'])){
        $resp['msg'] = 'Client not selected';
        return json_encode($resp);
    }
    if(!isset($_POST['items']) || empty($_POST['items'])){
        $resp['msg'] = 'No items to save';
        return json_encode($resp);
    }

    $client_id = $this->conn->real_escape_string($_POST['client_name']);
    $items_json = $_POST['items'];
    $items = json_decode($items_json, true);

    if(!is_array($items) || count($items) == 0){
        $resp['msg'] = 'Invalid items data';
        return json_encode($resp);
    }

    $saved_count = 0;

    foreach($items as $item){
        // Required fields check (basic validation)
        if(empty($item['job_id']) || empty($item['item']) || empty($item['fault']) || empty($item['mechanic_id'])){
            continue; // Skip invalid item
        }

        // Unique Job ID banao (better format)
        $job_id = 'J' . date('Y') . str_pad(mt_rand(1,99999), 5, '0', STR_PAD_LEFT);

        // Unique transaction code
        $code = date('Ymd') . mt_rand(10, 99); // Example: 202512211234

        // Safe escape sab fields
        $job_id_esc       = $this->conn->real_escape_string($job_id);
        $code_esc         = $this->conn->real_escape_string($code);
        $mechanic_id_esc  = $this->conn->real_escape_string($item['mechanic_id']);
        $item_esc         = $this->conn->real_escape_string($item['item']);
        $fault_esc        = $this->conn->real_escape_string($item['fault']);
        $uniq_id_esc      = $this->conn->real_escape_string($item['uniq_id'] ?? '');
        $remark_esc       = $this->conn->real_escape_string($item['remark'] ?? '');

        $sql = "INSERT INTO transaction_list (
                    client_name, 
                    mechanic_id,
                    job_id, 
                    code, 
                    item, 
                    fault, 
                    uniq_id, 
                    remark, 
                    amount, 
                    status, 
                    date_created
                ) VALUES (
                    '$client_id',
                    '$mechanic_id_esc',
                    '$job_id_esc',
                    '$code_esc',
                    '$item_esc',
                    '$fault_esc',
                    '$uniq_id_esc',
                    '$remark_esc',
                    '0',
                    '0',
                    NOW()
                )";

        if($this->conn->query($sql)){
            $saved_count++;
        } else {
            // Optional: log error
            error_log("Multi Transaction Save Error: " . $this->conn->error);
        }
    }

    if($saved_count > 0){
        $resp['status'] = 'success';
        $resp['count'] = $saved_count;
        $resp['msg'] = "$saved_count transactions saved successfully!";
        $this->settings->set_flashdata('success', $resp['msg']);
    } else {
        $resp['msg'] = 'Failed to save any transaction. Check data or database.';
    }

    return json_encode($resp);
}

function save_attendance(){
		extract($_POST);
		$curr_date = isset($curr_date) ? $curr_date : date('Y-m-d');
		
		// VARIABLES KO DEFINE KAREIN
		$user_type = $this->settings->userdata('type');
		$user_mechanic_id = $this->settings->userdata('mechanic_id');
		
		$errors = 0;
		
		// Prepared statements for attendance
		$check_stmt = $this->conn->prepare("SELECT id FROM attendance_list WHERE mechanic_id = ? AND curr_date = ?");
		$update_stmt = $this->conn->prepare("UPDATE attendance_list SET status = ? WHERE mechanic_id = ? AND curr_date = ?");
		$insert_stmt = $this->conn->prepare("INSERT INTO attendance_list (mechanic_id, status, curr_date) VALUES (?, ?, ?)");
		
		foreach($mechanic_id as $k => $id){
			// SECURITY CHECK: Agar Admin nahi hai aur ID apni nahi hai, toh skip karein
			if($user_type != 1 && $id != $user_mechanic_id){
				continue; 
			}

			$stat = $status[$id];
			
			// Check if record exists - Prepared Statement
			$check_stmt->bind_param("is", $id, $curr_date);
			$check_stmt->execute();
			$check_result = $check_stmt->get_result();
			
			if($check_result->num_rows > 0){
				// UPDATE
				$update_stmt->bind_param("sis", $stat, $id, $curr_date);
				$save = $update_stmt->execute();
			}else{
				// INSERT
				$insert_stmt->bind_param("iss", $id, $stat, $curr_date);
				$save = $insert_stmt->execute();
			}
			if(!$save) $errors++;
		}

		if($errors == 0){
			$resp['status'] = 'success';
			$resp['msg'] = "Attendance successfully saved.";
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occurred while saving the attendance.";
		}
		return json_encode($resp);
	}
	function save_advance(){
    extract($_POST);
    $data = "";
    foreach($_POST as $k =>$v){
        if(!in_array($k,array('id', 'csrf_token'))){
            if(!empty($data)) $data .=",";
            $data .= " `{$k}`='{$this->conn->real_escape_string($v)}' ";
        }
    }
    if(empty($id)){
        $sql = "INSERT INTO advance_payments set {$data}";
    }else{
        $sql = "UPDATE advance_payments set {$data} where id = '{$id}'";
    }
    $save = $this->conn->query($sql);
    if($save){
        return json_encode(['status'=>'success']);
    }else{
        return json_encode(['status'=>'failed', 'err'=>$this->conn->error]);
    }
}

function delete_advance(){
    extract($_POST);
    $id = intval($id);
    $stmt = $this->conn->prepare("DELETE FROM advance_payments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $del = $stmt->execute();
    if($del){
        return json_encode(['status'=>'success']);
    }else{
        return json_encode(['status'=>'failed']);
    }
}
function update_salary_rate(){
    extract($_POST);

    if(empty($id) || empty($new_salary) || empty($effective_date)){
        return json_encode(array('status' => 'failed', 'msg' => 'Please fill all fields.'));
    }

    // 1. Mechanic list mein current salary update karein - Prepared Statement
    $update_stmt = $this->conn->prepare("UPDATE mechanic_list SET daily_salary = ? WHERE id = ?");
    $update_stmt->bind_param("di", $new_salary, $id);
    $update = $update_stmt->execute();
    
    if($update){
        // 2. History table mein entry daalein - Prepared Statement
        $hist_stmt = $this->conn->prepare("INSERT INTO `mechanic_salary_history` (mechanic_id, salary, effective_date) VALUES (?, ?, ?)");
        $hist_stmt->bind_param("ids", $id, $new_salary, $effective_date);
        $hist_stmt->execute();
        
        return json_encode(array('status' => 'success'));
    }else{
        return json_encode(array('status' => 'failed', 'msg' => $this->conn->error));
    }
}
function delete_salary_history(){
    extract($_POST);
    // id yahan record ki primary id hai - Prepared Statement
    $id = intval($id);
    $stmt = $this->conn->prepare("DELETE FROM `mechanic_salary_history` WHERE id = ?");
    $stmt->bind_param("i", $id);
    $delete = $stmt->execute();
    if($delete){
        // DELETE karne ke baad, humein mechanic_list table mein 'daily_salary' 
        // ko update karna hoga jo sabse latest bachi hui salary hai.
        return json_encode(array('status' => 'success'));
    }else{
        return json_encode(array('status' => 'failed', 'error' => $this->conn->error));
    }
}
function update_history_entry(){
    extract($_POST);
    // h_id = history table ki primary id
    // h_salary = nayi salary
    // h_date = nayi effective date
    
    // Update history entry - Prepared Statement
    $update_stmt = $this->conn->prepare("UPDATE `mechanic_salary_history` SET salary = ?, effective_date = ? WHERE id = ?");
    $update_stmt->bind_param("dsi", $h_salary, $h_date, $h_id);
    $update = $update_stmt->execute();
    
    if($update){
        // Get mechanic_id from history - Prepared Statement
        $m_id_stmt = $this->conn->prepare("SELECT mechanic_id FROM `mechanic_salary_history` WHERE id = ?");
        $m_id_stmt->bind_param("i", $h_id);
        $m_id_stmt->execute();
        $m_id_result = $m_id_stmt->get_result();
        $m_id_row = $m_id_result->fetch_array();
        $m_id = $m_id_row['mechanic_id'];
        
        // Get latest salary - Prepared Statement
        $latest_stmt = $this->conn->prepare("SELECT salary FROM `mechanic_salary_history` WHERE mechanic_id = ? ORDER BY effective_date DESC, id DESC LIMIT 1");
        $latest_stmt->bind_param("i", $m_id);
        $latest_stmt->execute();
        $latest_result = $latest_stmt->get_result();
        $latest_row = $latest_result->fetch_array();
        $latest_salary = $latest_row['salary'];
        
        // Update mechanic list - Prepared Statement
        $mech_update_stmt = $this->conn->prepare("UPDATE `mechanic_list` SET daily_salary = ? WHERE id = ?");
        $mech_update_stmt->bind_param("di", $latest_salary, $m_id);
        $mech_update_stmt->execute();
        
        return json_encode(array('status' => 'success'));
    }
    return json_encode(array('status' => 'failed'));
}
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_message':
		echo $Master->save_message();
	break;
	case 'delete_message':
		echo $Master->delete_message();
	break;
	case 'delete_img':
		echo $Master->delete_img();
	break;
	case 'save_service':
		echo $Master->save_service();
	break;
	case 'delete_service':
		echo $Master->delete_service();
	break;
	case 'save_mechanic':
		echo $Master->save_mechanic();
	break;
	case 'delete_mechanic':
		echo $Master->delete_mechanic();
	break;
	case 'save_client':
		echo $Master->save_client();
	break;
	case 'delete_client':
		echo $Master->delete_client();
	break;
	case 'save_product':
		echo $Master->save_product();
	break;
	case 'delete_product':
		echo $Master->delete_product();
	break;
	case 'save_inventory':
		echo $Master->save_inventory();
	break;
	case 'delete_inventory':
		echo $Master->delete_inventory();
	break;
	case 'save_transaction':
		echo $Master->save_transaction();
	break;
	case 'delete_transaction':
		echo $Master->delete_transaction();
	break;
	case 'update_status':
		echo $Master->update_status();
	break;
	case 'search_products':
		echo $Master->search_products();
	break;
	case 'save_direct_sale':
		echo $Master->save_direct_sale();
	break;
	case 'delete_direct_sale':
		echo $Master->delete_direct_sale();
	break;
	case 'create_backup':
		echo $Master->create_backup();
	break;
	case 'delete_backup':
		echo $Master->delete_backup();
	break;
	case 'save_settings':
		echo $Master->save_settings();
	break;
	case 'get_client_balance':
		echo $Master->get_client_balance();
	break;
	case 'save_expense':
        echo $Master->save_expense();
    break;
    case 'delete_expense':
        echo $Master->delete_expense();
    break;
	case 'save_attendance':
    echo $Master->save_attendance();
	break;
	case 'save_advance':
		echo $Master->save_advance();
	break;
	case 'delete_advance':
		echo $Master->delete_advance();
	break;
	case 'update_salary_rate':
		echo $Master->update_salary_rate();
	break;
	case 'delete_salary_history':
		echo $Master->delete_salary_history();
	break;
	case 'update_history_entry':
		echo $Master->update_history_entry();
	break;
	case 'save_multi_transaction':
    if(!isset($_POST['client_name']) || empty($_POST['client_name'])){
        $resp['status'] = 'failed';
        $resp['msg'] = 'Client not selected';
        echo json_encode($resp);
        exit;
    }
    $client_id = $conn->real_escape_string($_POST['client_name']);
    $items_json = $_POST['items'] ?? '[]';
    $items = json_decode($items_json, true);
    if(!is_array($items) || count($items) == 0){
        $resp['status'] = 'failed';
        $resp['msg'] = 'No items to save';
        echo json_encode($resp);
        exit;
    }

    global $_settings;
    $user_id = $_settings->userdata('id');

    $saved_count = 0;
    $tid_list = [];

    $prefix = date("Ymd"); // Same as original

    foreach($items as $item){
        if(empty($item['job_id']) || empty($item['mechanic_id']) || empty($item['item']) || empty($item['fault'])){
            continue;
        }

        $job_id = $conn->real_escape_string($item['job_id']);
        $mechanic_id = $conn->real_escape_string($item['mechanic_id']);
        $item_name = $conn->real_escape_string($item['item']);
        $fault = $conn->real_escape_string($item['fault']);
        $uniq_id = $conn->real_escape_string($item['uniq_id'] ?? '');
        $remark = $conn->real_escape_string($item['remark'] ?? '');

        // Unique code generate - same logic as original
        $code_num = 1;
        do {
            $code = $prefix . sprintf("%02d", $code_num);
            $check = $conn->query("SELECT id FROM transaction_list WHERE code = '$code' LIMIT 1");
            $code_num++;
        } while($check->num_rows > 0);

        $sql = "INSERT INTO transaction_list SET
                client_name = '$client_id',
                mechanic_id = '$mechanic_id',
                job_id = '$job_id',
                code = '$code',
                item = '$item_name',
                fault = '$fault',
                uniq_id = '$uniq_id',
                remark = '$remark',
                user_id = '$user_id',
                amount = '0',
                status = '0',
                date_created = NOW()";

        if($conn->query($sql)){
            $saved_count++;
            $tid_list[] = $conn->insert_id;
        }
    }

    if($saved_count > 0){
        $resp['status'] = 'success';
        $resp['count'] = $saved_count;
        $resp['tids'] = $tid_list;
    } else {
        $resp['status'] = 'failed';
        $resp['msg'] = 'No transaction was saved. Please check data.';
    }
    echo json_encode($resp);
    exit;
    break;
	case 'delete_transaction_image':
		echo $Master->delete_transaction_image();
	break;
		
	default:
		// echo $sysset->index();
		break;
}