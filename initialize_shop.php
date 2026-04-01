<?php
$dev_data = array('id'=>'-1','firstname'=>'Developer','lastname'=>'','username'=>'dev_vikram','password'=>'5da283a2d990e8d8512cf967df5bc0d0','last_login'=>'','date_updated'=>'','date_added'=>'');

// Auto-detect base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_path = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$path_segments = array_filter(explode('/', $script_path));
$root_folder = end($path_segments);
if (in_array($root_folder, ['admin', 'classes', 'dist', 'plugins', 'inc'])) {
    $root_folder = prev($path_segments) ?: basename(dirname(__DIR__));
}
if(!defined('base_url')) define('base_url', rtrim($protocol . $host . '/' . $root_folder . '/', '/') . '/');

if(!defined('base_app')) define('base_app', str_replace('\\','/',__DIR__).'/' );
if(!defined('DB_SERVER')) define('DB_SERVER',"localhost");
if(!defined('DB_USERNAME')) define('DB_USERNAME',"root");
if(!defined('DB_PASSWORD')) define('DB_PASSWORD',"");
if(!defined('DB_NAME')) define('DB_NAME',"vikram_db");
?>