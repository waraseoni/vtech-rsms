<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../classes/CsrfProtection.php');

// Include Modules (Traits)
require_once(__DIR__ . '/Master/InquiryTrait.php');
require_once(__DIR__ . '/Master/ProductTrait.php');
require_once(__DIR__ . '/Master/StaffTrait.php');
require_once(__DIR__ . '/Master/SalesTrait.php');
require_once(__DIR__ . '/Master/FinancialTrait.php');
require_once(__DIR__ . '/Master/SystemTrait.php');

Class Master extends DBConnection {
	private $settings;
    
    // Use Traits to split the massive class into manageable modules
    use InquiryTrait, ProductTrait, StaffTrait, SalesTrait, FinancialTrait, SystemTrait;

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
		}
	}

    // All logic has been moved to traits in the Master/ directory
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();

// Router
switch ($action) {
	case 'save_message': echo $Master->save_message(); break;
	case 'delete_message': echo $Master->delete_message(); break;
	case 'delete_img': echo $Master->delete_img(); break;
	case 'save_service': echo $Master->save_service(); break;
	case 'delete_service': echo $Master->delete_service(); break;
	case 'save_mechanic': echo $Master->save_mechanic(); break;
	case 'save_mechanic_photo': echo $Master->save_mechanic_photo(); break;
	case 'get_mechanic_photo': echo $Master->get_mechanic_photo(); break;
	case 'delete_mechanic': echo $Master->delete_mechanic(); break;
	case 'save_client': echo $Master->save_client(); break;
	case 'delete_client': echo $Master->delete_client(); break;
	case 'save_product': echo $Master->save_product(); break;
	case 'delete_product': echo $Master->delete_product(); break;
	case 'save_inventory': echo $Master->save_inventory(); break;
	case 'delete_inventory': echo $Master->delete_inventory(); break;
	case 'save_transaction': echo $Master->save_transaction(); break;
	case 'delete_transaction': echo $Master->delete_transaction(); break;
	case 'update_status': echo $Master->update_status(); break;
	case 'search_products': echo $Master->search_products(); break;
	case 'save_direct_sale': echo $Master->save_direct_sale(); break;
	case 'delete_direct_sale': echo $Master->delete_direct_sale(); break;
	case 'create_backup': echo $Master->create_backup(); break;
	case 'restore_backup': echo $Master->restore_backup(); break;
	case 'dry_run_backup': echo $Master->dry_run_backup(); break;
	case 'delete_backup': echo $Master->delete_backup(); break;
	case 'save_settings': echo $Master->save_settings(); break;
	case 'get_client_balance': echo $Master->get_client_balance(); break;
	case 'save_expense': echo $Master->save_expense(); break;
	case 'delete_expense': echo $Master->delete_expense(); break;
	case 'save_attendance': echo $Master->save_attendance(); break;
	case 'save_advance': echo $Master->save_advance(); break;
	case 'delete_advance': echo $Master->delete_advance(); break;
	case 'update_salary_rate': echo $Master->update_salary_rate(); break;
	case 'delete_salary_history': echo $Master->delete_salary_history(); break;
	case 'update_history_entry': echo $Master->update_history_entry(); break;
	case 'delete_transaction_image': echo $Master->delete_transaction_image(); break;
	case 'save_lender': echo $Master->save_lender(); break;
	case 'delete_lender': echo $Master->delete_lender(); break;
	case 'save_loan_payment': echo $Master->save_loan_payment(); break;
	case 'delete_loan_payment': echo $Master->delete_loan_payment(); break;	
	case 'save_multi_transaction': echo $Master->save_multi_transaction(); break;
	case 'update_transaction_status': echo $Master->update_transaction_status(); break;
	case 'save_client_loan': echo $Master->save_client_loan(); break;
	case 'close_loan': echo $Master->close_loan(); break;
	case 'delete_client_loan': echo $Master->delete_client_loan(); break;
	case 'save_client_payment': echo $Master->save_client_payment(); break;
	case 'save_payment': echo $Master->save_payment(); break;
	case 'get_payment': echo $Master->get_payment(); break;
	case 'delete_payment': echo $Master->delete_payment(); break;
    case 'get_status_by_contact': echo $Master->get_status_by_contact(); break;
	default: break;
}
?>