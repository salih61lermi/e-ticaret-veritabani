<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Lütfen giriş yapın.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['urun_id'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$urun_id = (int)$_POST['urun_id'];
$adet = isset($_POST['adet']) ? (int)$_POST['adet'] : 1;
$kullanici_id = $_SESSION['kullanici_id'];

try {
    $db = Database::getInstance()->getConnection();
    
    // Ürün kontrolü
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND durum = 'aktif'");
    $stmt->execute([$urun_id]);
    $urun = $stmt->fetch();
    
    if (!$urun) {
        echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı.']);
        exit;
    }
    
    // Stok kontrolü
    if ($urun['stok_miktari'] < $adet) {
        echo json_encode(['success' => false, 'message' => 'Yeterli stok yok.']);
        exit;
    }
    
    // Sepette var mı kontrol et
    $stmt = $db->prepare("SELECT * FROM cart WHERE kullanici_id = ? AND urun_id = ?");
    $stmt->execute([$kullanici_id, $urun_id]);
    $sepet_item = $stmt->fetch();
    
    if ($sepet_item) {
        // Güncelle
        $yeni_adet = $sepet_item['adet'] + $adet;
        
        if ($urun['stok_miktari'] < $yeni_adet) {
            echo json_encode(['success' => false, 'message' => 'Yeterli stok yok.']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE cart SET adet = ? WHERE id = ?");
        $stmt->execute([$yeni_adet, $sepet_item['id']]);
    } else {
        // Yeni ekle
        $stmt = $db->prepare("INSERT INTO cart (kullanici_id, urun_id, adet) VALUES (?, ?, ?)");
        $stmt->execute([$kullanici_id, $urun_id, $adet]);
    }
    
    // Toplam sepet sayısını al
    $stmt = $db->prepare("SELECT SUM(adet) as toplam FROM cart WHERE kullanici_id = ?");
    $stmt->execute([$kullanici_id]);
    $result = $stmt->fetch();
    $cart_count = $result['toplam'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'message' => 'Ürün sepete eklendi.',
        'cart_count' => $cart_count
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu.']);
}
?>