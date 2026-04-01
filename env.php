<?php
/**
 * Env Loader - Load .env file and set as constants
 * 
 * Usage: require_once 'env.php';
 * 
 * REQUIRED: .env file must exist - software will NOT work without it
 */

function loadEnv($path) {
    if (!file_exists($path)) {
        die('<h3>Error: .env file not found!</h3><p>Please create .env file in the root directory.</p>');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
    return true;
}

loadEnv(__DIR__ . '/.env');
