<?php
require_once('../config.php');
require_once('../classes/CsrfProtection.php');

Class Users extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function save_users(){
		// CSRF Validation
		if (!CsrfProtection::validatePOST()) {
			return json_encode(['status' => 'failed', 'msg' => 'Invalid request']);
		}
		
		// 1. Password ko md5 mein badlen (yadi diya gaya hai)
		if(empty($_POST['password']))
			unset($_POST['password']);
		else
		$_POST['password'] = md5($_POST['password']);
		
		extract($_POST);
		
		$id = isset($id) ? $id : ''; 


		// Username Duplication Check - Prepared Statement
		if(empty($id)){
			$chk_stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
			$chk_stmt->bind_param("s", $username);
		} else {
			$chk_stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
			$chk_stmt->bind_param("si", $username, $id);
		}
		$chk_stmt->execute();
		$chk_result = $chk_stmt->get_result();
		$chk = $chk_result->num_rows;

		if($chk > 0){
			return 3; // Username already exist
		}
			
		// Build INSERT/UPDATE with prepared statement
		if(empty($id)){
			// INSERT - build columns and placeholders
			$columns = [];
			$placeholders = [];
			$values = [];
			$types = '';
			foreach($_POST as $k => $v){
				if(!in_array($k, array('id'))){
					$columns[] = "`$k`";
					$placeholders[] = '?';
					$values[] = $v;
					$types .= 's';
				}
			}
			
			// If admin, set mechanic_id to NULL
			if(isset($type) && $type == 1){
				// Skip mechanic_id for admin - don't include in insert
			}
			
			$sql = "INSERT INTO users (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
			$stmt = $this->conn->prepare($sql);
			if(!empty($types)){
				$stmt->bind_param($types, ...$values);
			}
			$save = $stmt->execute();
			$id = $this->conn->insert_id;
		} else {
			// UPDATE - build column = placeholders
			$set = [];
			$values = [];
			$types = '';
			foreach($_POST as $k => $v){
				if(!in_array($k, array('id'))){
					$set[] = "`$k` = ?";
					$values[] = $v;
					$types .= 's';
				}
			}
			
			// If admin, set mechanic_id to NULL
			if(isset($type) && $type == 1){
				$sql = "UPDATE users SET " . implode(', ', $set) . ", `mechanic_id` = NULL WHERE id = ?";
			} else {
				$sql = "UPDATE users SET " . implode(', ', $set) . " WHERE id = ?";
			}
			$values[] = $id;
			$types .= 'i';
			
			$stmt = $this->conn->prepare($sql);
			$stmt->bind_param($types, ...$values);
			$save = $stmt->execute();
		}

		if($save){
			$this->settings->set_flashdata('success','User Details successfully saved.');
			
			// === AVATAR UPLOAD - NEW SIMPLE & RELIABLE METHOD ===
				if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
				    $upload_dir = base_app . 'uploads/avatars/';
				    if(!is_dir($upload_dir)){
				        mkdir($upload_dir, 0777, true);
				    }

				    // Extension nikaalo
				    $ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
				    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
				    if(!in_array(strtolower($ext), $allowed)){
				        return 4; // New error code: Invalid image type
				    }

				    // Filename: avatars/{user_id}.ext
				    $fname = 'uploads/avatars/' . $id . '.' . $ext;

				    // Move file
				    $move = move_uploaded_file($_FILES['img']['tmp_name'], base_app . $fname);

				    if($move){
				        // Purani image delete karo (agar alag extensionwali ho)
				        $old_stmt = $this->conn->prepare("SELECT avatar FROM users WHERE id = ?");
				        $old_stmt->bind_param("i", $id);
				        $old_stmt->execute();
				        $old_result = $old_stmt->get_result();
				        if($old_result->num_rows > 0){
				            $old_avatar = $old_result->fetch_assoc()['avatar'];
				            if(!empty($old_avatar) && file_exists(base_app . $old_avatar)){
				                if(strpos($old_avatar, 'uploads/avatars/') === 0){
				                    unlink(base_app . $old_avatar);
				                }
				            }
				        }

				        // Database mein path save karo - Prepared Statement
				        $avatar_stmt = $this->conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
				        $avatar_stmt->bind_param("si", $fname, $id);
				        $avatar_stmt->execute();

				        // Agar current logged in user hai, toh session update karo
				        if($this->settings->userdata('id') == $id){
				            $this->settings->set_userdata('avatar', $fname);
				        }
				    } else {
				        // Upload fail – permission ya size issue
				        return 5; // Upload failed
				    }
				}
				// === END AVATAR UPLOAD ===

			// Agar logged in user apni profile update kar raha hai
			if($this->settings->userdata('id') == $id){
				foreach($_POST as $k => $v){
					if(!in_array($k,array('id','password'))){
						$this->settings->set_userdata($k,$v);
					}
				}
			}

			return 1; // Success
		}else{
			return 2; // Error in SQL
		}
	}

	public function delete_users(){
		extract($_POST);
		
		// Get avatar first - Prepared Statement
		$avatar_stmt = $this->conn->prepare("SELECT avatar FROM users WHERE id = ?");
		$avatar_stmt->bind_param("i", $id);
		$avatar_stmt->execute();
		$avatar_result = $avatar_stmt->get_result();
		$avatar = '';
		if($avatar_result->num_rows > 0){
			$avatar = $avatar_result->fetch_assoc()['avatar'];
		}
		
		// Delete user - Prepared Statement
		$del_stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
		$del_stmt->bind_param("i", $id);
		$qry = $del_stmt->execute();
		
		if($qry){
			$this->settings->set_flashdata('success','User Details successfully deleted.');
			if(!empty($avatar) && is_file(base_app.$avatar)) unlink(base_app.$avatar);
			return 1;
		}else{
			return false;
		}
	}
}

$users = new Users();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
switch ($action) {
	case 'save':
		echo $users->save_users();
	break;
	case 'delete':
		echo $users->delete_users();
	break;
	default:
		break;
}
