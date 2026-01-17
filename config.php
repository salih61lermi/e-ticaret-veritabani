<?php
// Veritabanı Bağlantı Ayarları
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'eticaret');

// Veritabanı Bağlantısı
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                )
            );
        } catch(PDOException $e) {
            die("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Oturum başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yardımcı Fonksiyonlar
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function isLoggedIn() {
    return isset($_SESSION['kullanici_id']);
}

function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function formatPrice($price) {
    return number_format($price, 2, ',', '.') . ' ₺';
}

function formatDate($date) {
    return date('d.m.Y H:i', strtotime($date));
}

function showAlert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}
?>
