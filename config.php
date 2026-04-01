<?php
ob_start();
ini_set('date.timezone','Asia/Kolkata');
date_default_timezone_set('Asia/Kolkata');
session_start();

require_once('initialize.php');
require_once('classes/DBConnection.php');
require_once('classes/SystemSettings.php');
require_once('classes/CsrfProtection.php');
require_once('security_helper.php');
$db = new DBConnection;
$conn = $db->conn;

// Prevent function redeclaration
if (!function_exists('redirect')) {
    function redirect($url=''){
        if(!empty($url))
        echo '<script>location.href="'.base_url .$url.'"</script>';
    }
}
function get_full_client_name($row){
    if(empty($row['firstname'])) return 'Unknown Client';
    return trim($row['firstname'].' '.($row['middlename'] ? $row['middlename'].' ' : '').$row['lastname']);
}
// Ab aap view_client.php mein use kar sakte hain:
// $client_name_full = get_full_client_name($res);
function validate_image($file, $is_avatar = false){
    global $_settings;
	if(!empty($file)){
			// exit;
        $ex = explode("?",$file);
        $file = $ex[0];
        $ts = isset($ex[1]) ? "?".$ex[1] : '';
		if(is_file(base_app.$file)){
			return base_url.$file.$ts;
		}else{
            // Use default avatar for user avatars, logo for others
            if ($is_avatar && is_file(base_app.'uploads/avatars/default-avatar.jpg')) {
                return base_url.'uploads/avatars/default-avatar.jpg';
            }
			return base_url.($_settings->info('logo'));
		}
	}else{
        // Empty file - use default avatar for user avatars, logo for others
        if ($is_avatar && is_file(base_app.'uploads/avatars/default-avatar.jpg')) {
            return base_url.'uploads/avatars/default-avatar.jpg';
        }
		return base_url.($_settings->info('logo'));
	}
}
function format_num($number = '' , $decimal = ''){
    if(is_numeric($number)){
        $ex = explode(".",$number);
        $decLen = isset($ex[1]) ? strlen($ex[1]) : 0;
        if(is_numeric($decimal)){
            return number_format($number,$decimal);
        }else{
            return number_format($number,$decLen);
        }
    }else{
        return "Invalid Input";
    }
}
function isMobileDevice(){
    $aMobileUA = array(
        '/iphone/i' => 'iPhone', 
        '/ipod/i' => 'iPod', 
        '/ipad/i' => 'iPad', 
        '/android/i' => 'Android', 
        '/blackberry/i' => 'BlackBerry', 
        '/webos/i' => 'Mobile'
    );

    //Return true if Mobile User Agent is detected
    foreach($aMobileUA as $sMobileKey => $sMobileOS){
        if(preg_match($sMobileKey, $_SERVER['HTTP_USER_AGENT'])){
            return true;
        }
    }
    //Otherwise return false..  
    return false;
}
ob_end_flush();
?>
