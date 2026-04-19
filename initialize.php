<?php
$dev_data = array('id'=>'-1','firstname'=>'Developer','lastname'=>'','username'=>'dev_vikram','password'=>'5da283a2d990e8d8512cf967df5bc0d0','last_login'=>'','date_updated'=>'','date_added'=>'');

// Load environment variables from .env file (required)
require_once __DIR__ . '/env.php';

// Auto-detect base URL dynamically - no hardcoded folder names
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$app_dir = str_replace('\\', '/', __DIR__);
$base_folder = str_ireplace($doc_root, '', $app_dir);
if(!defined('base_url')) define('base_url', defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : rtrim($protocol . $host . $base_folder, '/') . '/');

if(!defined('base_app')) define('base_app', str_replace('\\','/',__DIR__).'/' );

// Database - from .env (defaults if not set)
//if(!defined('DB_SERVER')) define('DB_SERVER', "localhost");
//if(!defined('DB_USERNAME')) define('DB_USERNAME', "root");
//if(!defined('DB_PASSWORD')) define('DB_PASSWORD', "");
//if(!defined('DB_NAME')) define('DB_NAME', "vikram_db");

if (!function_exists('move_and_compress_uploaded_file')) {
    function move_and_compress_uploaded_file($source, $destination, $quality = 60, $max_width = 1200) {
        $info = @getimagesize($source);
        if ($info !== false && in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp', 'image/gif']) && function_exists('imagecreatefromjpeg')) {
            $image = false;
            if ($info['mime'] == 'image/jpeg') {
                $image = @imagecreatefromjpeg($source);
                if ($image && function_exists('exif_read_data')) {
                    $exif = @exif_read_data($source);
                    if ($exif && !empty($exif['Orientation'])) {
                        switch ($exif['Orientation']) {
                            case 3: $image = imagerotate($image, 180, 0); break;
                            case 6: $image = imagerotate($image, -90, 0); break;
                            case 8: $image = imagerotate($image, 90, 0); break;
                        }
                    }
                }
            }
            elseif ($info['mime'] == 'image/webp') $image = @imagecreatefromwebp($source);
            elseif ($info['mime'] == 'image/gif') $image = @imagecreatefromgif($source);
            elseif ($info['mime'] == 'image/png') $image = @imagecreatefrompng($source);

            if ($image !== false) {
                $width = imagesx($image);
                $height = imagesy($image);
                
                if ($width > $max_width || $height > $max_width) {
                    $ratio = min($max_width / $width, $max_width / $height);
                    $new_width = round($width * $ratio);
                    $new_height = round($height * $ratio);
                    
                    $new_image = imagecreatetruecolor($new_width, $new_height);
                    
                    if($info['mime'] == 'image/png' || $info['mime'] == 'image/gif'){
                        imagecolortransparent($new_image, imagecolorallocatealpha($new_image, 0, 0, 0, 127));
                        imagealphablending($new_image, false);
                        imagesavealpha($new_image, true);
                    }
                    
                    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                    imagedestroy($image);
                    $image = $new_image;
                }

                $success = false;
                if ($info['mime'] == 'image/jpeg') 
                    $success = imagejpeg($image, $destination, $quality);
                elseif ($info['mime'] == 'image/webp')
                    $success = imagewebp($image, $destination, $quality);
                elseif ($info['mime'] == 'image/gif') 
                    $success = imagegif($image, $destination); 
                elseif ($info['mime'] == 'image/png') {
                    $pngQuality = 9 - round(($quality / 100) * 9);
                    $success = imagepng($image, $destination, $pngQuality);
                }
                
                imagedestroy($image);
                if ($success) return true;
            }
        }
        // Fallback to normal move if not a valid image, compression failed, or no GD library
        return move_uploaded_file($source, $destination);
    }
}
?>
