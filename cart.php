<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php?redirect=cart.php');
}

$db = Database::getInstance()->getConnection();
$kullanici_id = $_SESSION['kullanici_id'];

// Sepet işlemleri
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $cart_id = (int)$_POST['cart_id'];
                $adet = (int)$_POST['adet'];
                
                if ($adet > 0) {
                    $stmt = $db->prepare("UPDATE cart SET adet = ? WHERE id = ? AND kullanici_id = ?");
                    $stmt->execute([$adet, $cart_id, $kullanici_id]);
                    showAlert('Sepet güncellendi.', 'success');
                }
                break;
                
            case 'remove':
                $cart_id = (int)$_POST['cart_id'];
                $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND kullanici_id = ?");
                $stmt->execute([$cart_id, $kullanici_id]);
                showAlert('Ürün sepetten kaldırıldı.', 'success');
                break;
                
            case 'clear':
                $stmt = $db->prepare("DELETE FROM cart WHERE kullanici_id = ?");
                $stmt->execute([$kullanici_id]);
                showAlert('Sepet temizlendi.', 'success');
                break;
        }
        redirect('cart.php');
    }
}

// Sepet ürünlerini getir
$stmt = $db->prepare("
    SELECT c.*, p.urun_adi, p.fiyat, p.indirimli_fiyat, p.resim, p.stok_miktari 
    FROM cart c 
    JOIN products p ON c.urun_id = p.id 
    WHERE c.kullanici_id = ? AND p.durum = 'aktif'
    ORDER BY c.ekleme_tarihi DESC
");
$stmt->execute([$kullanici_id]);
$sepet_urunler = $stmt->fetchAll();

// Toplam hesapla
$ara_toplam = 0;
foreach ($sepet_urunler as $item) {
    $fiyat = $item['indirimli_fiyat'] ?? $item['fiyat'];
    $ara_toplam += $fiyat * $item['adet'];
}

$kargo_ucreti = $ara_toplam >= 500 ? 0 : 29.99;
$genel_toplam = $ara_toplam + $kargo_ucreti;

$page_title = "Sepetim";
include 'header.php';
?>

<div class="container" style="padding: 40px 20px;">
    <h1 style="margin-bottom: 30px;"><i class="fas fa-shopping-cart"></i> Sepetim</h1>

    <?php if (empty($sepet_urunler)): ?>
    <div class="empty-cart">
        <i class="fas fa-shopping-cart"></i>
        <h2>Sepetiniz Boş</h2>
        <p>Sepetinizde henüz ürün bulunmamaktadır.</p>
        <a href="products.php" class="btn btn-primary">Alışverişe Başla</a>
    </div>
    <?php else: ?>
    <div class="cart-container">
        <!-- Cart Items -->
        <div class="cart-items">
            <?php foreach ($sepet_urunler as $item): 
                $fiyat = $item['indirimli_fiyat'] ?? $item['fiyat'];
                $toplam = $fiyat * $item['adet'];
            ?>
            <div class="cart-item">
                <div class="cart-item-image">
                    <img src="images/products/<?php echo $item['resim']; ?>" alt="<?php echo htmlspecialchars($item['urun_adi']); ?>" onerror="this.src='images/no-image.jpg'">
                </div>
                
                <div class="cart-item-details">
                    <h3><?php echo htmlspecialchars($item['urun_adi']); ?></h3>
                    <?php if ($item['indirimli_fiyat']): ?>
                    <div class="cart-item-price">
                        <span class="old-price"><?php echo formatPrice($item['fiyat']); ?></span>
                        <span class="current-price"><?php echo formatPrice($item['indirimli_fiyat']); ?></span>
                    </div>
                    <?php else: ?>
                    <div class="cart-item-price">
                        <span class="current-price"><?php echo formatPrice($item['fiyat']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="cart-item-quantity">
                    <form method="POST" class="quantity-form">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                        <button type="button" onclick="changeQuantity(this, -1)" class="qty-btn">-</button>
                        <input type="number" name="adet" value="<?php echo $item['adet']; ?>" min="1" max="<?php echo $item['stok_miktari']; ?>" readonly>
                        <button type="button" onclick="changeQuantity(this, 1)" class="qty-btn">+</button>
                    </form>
                </div>
                
                <div class="cart-item-total">
                    <strong><?php echo formatPrice($toplam); ?></strong>
                </div>
                
                <div class="cart-item-remove">
                    <form method="POST" onsubmit="return confirm('Bu ürünü sepetten kaldırmak istediğinize emin misiniz?');">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                        <button type="submit" class="remove-btn" title="Kaldır">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="cart-actions">
                <form method="POST" onsubmit="return confirm('Sepeti temizlemek istediğinize emin misiniz?');" style="display: inline;">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn btn-outline">
                        <i class="fas fa-trash"></i> Sepeti Temizle
                    </button>
                </form>
                <a href="products.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Alışverişe Devam
                </a>
            </div>
        </div>

        <!-- Cart Summary -->
        <div class="cart-summary">
            <h3>Sipariş Özeti</h3>
            
            <div class="summary-row">
                <span>Ara Toplam</span>
                <strong><?php echo formatPrice($ara_toplam); ?></strong>
            </div>
            
            <div class="summary-row">
                <span>Kargo Ücreti</span>
                <strong><?php echo $kargo_ucreti == 0 ? 'ÜCRETSİZ' : formatPrice($kargo_ucreti); ?></strong>
            </div>
            
            <?php if ($ara_toplam < 500 && $ara_toplam > 0): ?>
            <div class="free-shipping-info">
                <i class="fas fa-truck"></i>
                <p><?php echo formatPrice(500 - $ara_toplam); ?> daha alışveriş yapın, kargo ücretsiz!</p>
                <div class="progress-bar">
                    <div class="progress" style="width: <?php echo ($ara_toplam / 500) * 100; ?>%"></div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="summary-row total">
                <span>Genel Toplam</span>
                <strong><?php echo formatPrice($genel_toplam); ?></strong>
            </div>
            
            <a href="checkout.php" class="btn btn-primary btn-block">
                <i class="fas fa-lock"></i> Ödemeye Geç
            </a>
            
            <div class="payment-badges">
                <i class="fab fa-cc-visa"></i>
                <i class="fab fa-cc-mastercard"></i>
                <i class="fab fa-cc-amex"></i>
                <i class="fas fa-credit-card"></i>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.empty-cart {
    text-align: center;
    padding: 80px 20px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.empty-cart i {
    font-size: 100px;
    color: var(--text-light);
    margin-bottom: 20px;
}

.empty-cart h2 {
    margin-bottom: 15px;
}

.empty-cart p {
    color: var(--text-light);
    margin-bottom: 30px;
}

.cart-container {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 30px;
}

.cart-items {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 20px;
}

.cart-item {
    display: grid;
    grid-template-columns: 100px 1fr 150px 120px 50px;
    gap: 20px;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-image img {
    width: 100%;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
}

.cart-item-details h3 {
    font-size: 16px;
    margin-bottom: 10px;
}

.cart-item-price .old-price {
    text-decoration: line-through;
    color: var(--text-light);
    font-size: 14px;
    margin-right: 10px;
}

.cart-item-price .current-price {
    color: var(--danger-color);
    font-size: 18px;
    font-weight: bold;
}

.cart-item-quantity {
    display: flex;
    justify-content: center;
}

.quantity-form {
    display: flex;
    align-items: center;
    gap: 10px;
    background-color: var(--light-color);
    border-radius: 5px;
    padding: 5px;
}

.qty-btn {
    width: 30px;
    height: 30px;
    border: none;
    background-color: var(--primary-color);
    color: #fff;
    border-radius: 3px;
    cursor: pointer;
    font-size: 16px;
}

.quantity-form input[type="number"] {
    width: 50px;
    text-align: center;
    border: none;
    background-color: transparent;
    font-size: 16px;
    font-weight: bold;
}

.cart-item-total {
    text-align: center;
    font-size: 18px;
}

.remove-btn {
    background: none;
    border: none;
    color: var(--danger-color);
    font-size: 18px;
    cursor: pointer;
    transition: opacity 0.3s;
}

.remove-btn:hover {
    opacity: 0.7;
}

.cart-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.cart-summary {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 30px;
    height: fit-content;
    position: sticky;
    top: 100px;
}

.cart-summary h3 {
    font-size: 20px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--border-color);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid var(--border-color);
}

.summary-row.total {
    border-bottom: none;
    padding-top: 20px;
    font-size: 20px;
    color: var(--primary-color);
}

.free-shipping-info {
    background-color: var(--light-color);
    padding: 15px;
    border-radius: 8px;
    margin: 15px 0;
    text-align: center;
}

.free-shipping-info i {
    color: var(--success-color);
    font-size: 24px;
    margin-bottom: 10px;
}

.free-shipping-info p {
    font-size: 14px;
    margin-bottom: 10px;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background-color: var(--border-color);
    border-radius: 4px;
    overflow: hidden;
}

.progress {
    height: 100%;
    background-color: var(--success-color);
    transition: width 0.3s;
}

.btn-block {
    width: 100%;
    margin-top: 20px;
}

.payment-badges {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
    font-size: 28px;
    color: var(--text-light);
}

@media (max-width: 992px) {
    .cart-container {
        grid-template-columns: 1fr;
    }

    .cart-item {
        grid-template-columns: 80px 1fr;
        gap: 15px;
    }

    .cart-item-quantity,
    .cart-item-total {
        grid-column: 2;
    }

    .cart-item-remove {
        grid-column: 2;
        justify-self: end;
    }
}

@media (max-width: 576px) {
    .cart-actions {
        flex-direction: column;
        gap: 10px;
    }

    .cart-actions .btn {
        width: 100%;
    }
}
</style>

<script>
function changeQuantity(btn, change) {
    const form = btn.closest('.quantity-form');
    const input = form.querySelector('input[name="adet"]');
    const currentValue = parseInt(input.value);
    const max = parseInt(input.max);
    const min = parseInt(input.min);
    
    let newValue = currentValue + change;
    if (newValue >= min && newValue <= max) {
        input.value = newValue;
        form.submit();
    }
}
</script>

<?php include 'footer.php'; ?>
