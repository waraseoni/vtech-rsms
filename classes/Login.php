<?php
require_once '../config.php';
require_once '../classes/CsrfProtection.php';

class Login extends DBConnection {
	private $settings;
	
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;

		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function index(){
		echo "<h1>Access Denied</h1> <a href='".base_url."'>Go Back.</a>";
	}
	public function login(){
		$username = $_POST['username'] ?? '';
		$password = $_POST['password'] ?? '';
		
		// CSRF Validation
		if (!CsrfProtection::validatePOST()) {
			return json_encode(array('status'=>'failed', 'msg'=>'Invalid request'));
		}
		
		// Validate input
		if (empty($username) || empty($password)) {
			return json_encode(array('status'=>'failed', 'msg'=>'Username and password required'));
		}

		$stmt = $this->conn->prepare("SELECT id, username, password, firstname, lastname, type, mechanic_id, avatar from users where username = ?");
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if($result->num_rows > 0){
			$row = $result->fetch_assoc();
			$password_hash = $row['password'];
			
			// Check if it's bcrypt hash (starts with $2y$ or $2a$)
			if (substr($password_hash, 0, 4) === '$2y$' || substr($password_hash, 0, 4) === '$2a$') {
				// New password_hash format
				if (password_verify($password, $password_hash)) {
					$this->set_user_session($row);
					return json_encode(array('status'=>'success'));
				}
			} else {
				// Legacy MD5 - check and upgrade to new hash
				if (md5($password) === $password_hash) {
					// Upgrade password to new secure hash
					$new_hash = password_hash($password, PASSWORD_DEFAULT);
					$update_stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
					$update_stmt->bind_param("si", $new_hash, $row['id']);
					$update_stmt->execute();
					
					$this->set_user_session($row);
					return json_encode(array('status'=>'success'));
				}
			}
		}
		
		return json_encode(array('status'=>'incorrect', 'msg'=>'Invalid username or password'));
	}
	
	private function set_user_session($row) {
		$this->settings->set_userdata('id', $row['id']);
		$this->settings->set_userdata('username', $row['username']);
		$this->settings->set_userdata('firstname', $row['firstname']);
		$this->settings->set_userdata('lastname', $row['lastname']);
		$this->settings->set_userdata('type', $row['type']);
		$this->settings->set_userdata('login_type', 1);
		if(isset($row['mechanic_id'])) {
			$this->settings->set_userdata('mechanic_id', $row['mechanic_id']);
		}
		if(isset($row['avatar'])) {
			$this->settings->set_userdata('avatar', $row['avatar']);
		}
	}

	public function logout(){
		if($this->settings->sess_des()){
			redirect('admin/login.php');
		}
	}
	function login_agent(){
		// CSRF Validation
		if (!CsrfProtection::validatePOST()) {
			return json_encode(array('status'=>'failed', 'msg'=>'Invalid request'));
		}
		
		$email = $_POST['email'] ?? '';
		$password = $_POST['password'] ?? '';
		
		// Validate input
		if (empty($email) || empty($password)) {
			return json_encode(array('status'=>'failed', 'msg'=>'Email and password required'));
		}

		$stmt = $this->conn->prepare("SELECT id, email, password, firstname, lastname, status from agent_list where email = ? and delete_flag = 0");
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if($result->num_rows > 0){
			$row = $result->fetch_assoc();
			$password_hash = $row['password'];
			
			// Check if it's bcrypt hash
			if (substr($password_hash, 0, 4) === '$2y$' || substr($password_hash, 0, 4) === '$2a$') {
				if (password_verify($password, $password_hash)) {
					return $this->agent_login_success($row);
				}
			} else {
				// Legacy MD5
				if (md5($password) === $password_hash) {
					$new_hash = password_hash($password, PASSWORD_DEFAULT);
					$update_stmt = $this->conn->prepare("UPDATE agent_list SET password = ? WHERE id = ?");
					$update_stmt->bind_param("si", $new_hash, $row['id']);
					$update_stmt->execute();
					return $this->agent_login_success($row);
				}
			}
		}
		
		return json_encode(array('status'=>'failed', 'msg' => 'Incorrect Email or Password'));
	}
	
	private function agent_login_success($row) {
		if($row['status'] == 1){
			$this->settings->set_userdata('id', $row['id']);
			$this->settings->set_userdata('email', $row['email']);
			$this->settings->set_userdata('firstname', $row['firstname']);
			$this->settings->set_userdata('lastname', $row['lastname']);
			$this->settings->set_userdata('type', 2);
			$this->settings->set_userdata('login_type',2);
			return json_encode(array('status'=>'success'));
		} else {
			return json_encode(array('status'=>'failed', 'msg' => 'Your Account has been blocked.'));
		}
	}
	
	public function logout_agent(){
		if($this->settings->sess_des()){
			redirect('agent');
		}
	}
}
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$auth = new Login();
switch ($action) {
	case 'login':
		echo $auth->login();
		break;
	case 'logout':
		echo $auth->logout();
		break;
	case 'login_agent':
		echo $auth->login_agent();
		break;
	case 'logout_agent':
		echo $auth->logout_agent();
		break;
	default:
		echo $auth->index();
		break;
}
?>
