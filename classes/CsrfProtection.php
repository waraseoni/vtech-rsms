<?php
/**
 * CSRF Protection Class
 * Generates and validates CSRF tokens to prevent cross-site request forgery
 */
class CsrfProtection {
    
    private static $tokenName = 'csrf_token';
    private static $sessionName = 'csrf_tokens';
    
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$sessionName])) {
            $_SESSION[self::$sessionName] = [];
        }
        
        if (!isset($_SESSION[self::$sessionName]['token'])) {
            $_SESSION[self::$sessionName]['token'] = self::generateToken();
        }
        
        if (!isset($_SESSION[self::$sessionName]['token_time'])) {
            $_SESSION[self::$sessionName]['token_time'] = time();
        }
    }
    
    private static function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    public static function getToken() {
        self::init();
        
        if (isset($_SESSION[self::$sessionName]['token'])) {
            $storedTime = $_SESSION[self::$sessionName]['token_time'];
            if ((time() - $storedTime) > 3600) {
                $_SESSION[self::$sessionName]['token'] = self::generateToken();
                $_SESSION[self::$sessionName]['token_time'] = time();
            }
        }
        
        return $_SESSION[self::$sessionName]['token'] ?? '';
    }
    
    public static function getField() {
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . self::getToken() . '">';
    }
    
    public static function validate($token) {
        self::init();
        
        if (empty($token)) {
            return false;
        }
        
        $storedToken = $_SESSION[self::$sessionName]['token'] ?? '';
        
        if (empty($storedToken)) {
            return false;
        }
        
        return hash_equals($storedToken, $token);
    }
    
    public static function validatePOST() {
        $token = $_POST[self::$tokenName] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return self::validate($token);
    }
    
    public static function getTokenName() {
        return self::$tokenName;
    }
}

CsrfProtection::init();
