<?php
if(!defined('DB_SERVER')){
    require_once(__DIR__ . "/../initialize.php");
}
class DBConnection{

    private $host = DB_SERVER;
    private $username = DB_USERNAME;
    private $password = DB_PASSWORD;
    private $database = DB_NAME;
    
    public $conn;
    
    public function __construct(){

        if (!isset($this->conn)) {
            
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            if (!$this->conn) {
                echo 'Cannot connect to database server';
                exit;
            }
            
            // Global Security Patch: Sanitize all inputs against SQL injection
            if (isset($_GET)) {
                $_GET = $this->sanitize_array($_GET);
            }
            if (isset($_POST)) {
                $_POST = $this->sanitize_array($_POST);
            }
            if (isset($_REQUEST)) {
                $_REQUEST = $this->sanitize_array($_REQUEST);
            }
        }    
        
    }
    public function __destruct(){
        $this->conn->close();
    }
    
    public function sanitize_array($array) {
        $clean = [];
        foreach($array as $key => $value){
            if(is_array($value)){
                $clean[$key] = $this->sanitize_array($value);
            } else {
                $clean[$key] = $this->conn->real_escape_string($value);
            }
        }
        return $clean;
    }

}
?>
