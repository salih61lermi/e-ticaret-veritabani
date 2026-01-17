<?php
require_once 'config.php';

$urun_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($urun_id == 0) {
    redirect('products.php');
}

$db = Database::getInstance()->getConnection();

// Ürün bilgilerini getir
$stmt = $db->prepare("
    SELECT p.*, c.kategori_adi 
    FROM products p 
    JOIN categories c ON p.kategori_id = c.id 
    WHERE p.id = ? AND p.durum = 'aktif'
");
$stmt->execute([$urun_id]);
$urun = $stmt->fetch();

if (!$urun) {
    redirect('products.php');
}

// Yorumları getir
$stmt = $db->prepare("
    SELECT r.*, u.ad, u.soyad 
    FROM reviews r 
    JOIN users u ON r.kullanici_id = u.id 
    WHERE r.urun_id = ? AND r.onay_durumu = 1 
    ORDER BY r.yorum_tarihi DESC
");
$stmt->execute([$urun_id]);
$yorumlar = $stmt->fetchAll();

// Ortalama puan
$stmt = $db->prepare("SELECT AVG(puan) as ort, COUNT(*) as sayi FROM reviews WHERE urun_id = ? AND onay_durumu = 1");
$stmt->execute([$urun_id]);
$puan_bilgi = $stmt->fetch();
$ortalama_puan = round($puan_bilgi['ort'], 1);
$yorum_sayisi = $puan_bilgi['sayi'];

// Yorum gönderme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isLoggedIn() && isset($_POST['yorum_gonder'])) {
    $puan = (int)$_POST['puan'];
    $yorum = cleanInput($_POST['yorum']);
    $kullanici_id = $_SESSION['kullanici_id'];
    
    if ($puan >= 1 && $puan <= 5 && !empty($yorum)) {
        $stmt = $db->prepare("INSERT INTO reviews (urun_id, kullanici_id, puan, yorum) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$urun_id, $kullanici_id, $puan, $yorum])) {
            showAlert('Yorumunuz başarıyla gönderildi. Onaylandıktan sonra yayınlanacaktır.', 'success');
            redirect('product-detail.php?id=' . $urun_id);
        }
    }
}

// Benzer ürünler
$stmt = $db->prepare("
    SELECT * FROM products 
    WHERE kategori_id = ? AND id != ? AND durum = 'aktif' 
    ORDER BY RAND() 
    LIMIT 4
");
$stmt->execute([$urun['kategori_id'], $urun_id]);
$benzer_urunler = $stmt->fetchAll();

$page_title = $urun['urun_adi'];
include 'header.php';
?>

<div class="container" style="padding: 40px 20px;">
    <div class="breadcrumb">
        <a href="index.php">Ana Sayfa</a>
        <span>/</span>
        <a href="products.php">Ürünler</a>
        <span>/</span>
        <a href="products.php?kategori=<?php echo $urun['kategori_id']; ?>"><?php echo htmlspecialchars($urun['kategori_adi']); ?></a>
        <span>/</span>
        <span><?php echo htmlspecialchars($urun['urun_adi']); ?></span>
    </div>

    <div class="product-detail-container">
        <!-- Product Image -->
        <div class="product-detail-image">
            <img src="images/products/<?php echo $urun['resim']; ?>" alt="<?php echo htmlspecialchars($urun['urun_adi']); ?>" onerror="this.src='images/no-image.jpg'">
        </div>

        <!-- Product Info -->
        <div class="product-detail-info">
            <h1><?php echo htmlspecialchars($urun['urun_adi']); ?></h1>
            
            <div class="product-rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fas fa-star <?php echo $i <= $ortalama_puan ? 'filled' : ''; ?>"></i>
                <?php endfor; ?>
                <span>(<?php echo $yorum_sayisi; ?> değerlendirme)</span>
            </div>

            <div class="product-detail-price">
                <?php if ($urun['indirimli_fiyat']): 
                    $indirim_orani = round((($urun['fiyat'] - $urun['indirimli_fiyat']) / $urun['fiyat']) * 100);
                ?>
                <div class="discount-badge">%<?php echo $indirim_orani; ?> İNDİRİM</div>
                <span class="old-price"><?php echo formatPrice($urun['fiyat']); ?></span>
                <span class="current-price"><?php echo formatPrice($urun['indirimli_fiyat']); ?></span>
                <?php else: ?>
                <span class="current-price"><?php echo formatPrice($urun['fiyat']); ?></span>
                <?php endif; ?>
            </div>

            <div class="product-stock">
                <?php if ($urun['stok_miktari'] > 0): ?>
                <span class="in-stock"><i class="fas fa-check-circle"></i> Stokta var (<?php echo $urun['stok_miktari']; ?> adet)</span>
                <?php else: ?>
                <span class="out-of-stock"><i class="fas fa-times-circle"></i> Stokta yok</span>
                <?php endif; ?>
            </div>

            <div class="product-description">
                <h3>Ürün Açıklaması</h3>
                <p><?php echo nl2br(htmlspecialchars($urun['aciklama'])); ?></p>
            </div>

            <div class="product-actions-detail">
                <div class="quantity-selector">
                    <button onclick="changeQty(-1)">-</button>
                    <input type="number" id="quantity" value="1" min="1" max="<?php echo $urun['stok_miktari']; ?>" readonly>
                    <button onclick="changeQty(1)">+</button>
                </div>
                
                <?php if ($urun['stok_miktari'] > 0): ?>
                <button onclick="addToCartDetail(<?php echo $urun['id']; ?>)" class="btn btn-primary btn-add-cart">
                    <i class="fas fa-shopping-cart"></i> Sepete Ekle
                </button>
                <?php else: ?>
                <button class="btn btn-primary btn-add-cart" disabled>
                    <i class="fas fa-times"></i> Stokta Yok
                </button>
                <?php endif; ?>
            </div>

            <div class="product-features">
                <div class="feature-item">
                    <i class="fas fa-truck"></i>
                    <span>500 TL üzeri ücretsiz kargo</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-undo"></i>
                    <span>14 gün içinde kolay iade</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Güvenli alışveriş</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="reviews-section">
        <h2>Müşteri Değerlendirmeleri</h2>
        
        <div class="reviews-summary">
            <div class="rating-overview">
                <div class="rating-score"><?php echo $ortalama_puan; ?></div>
                <div class="rating-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star <?php echo $i <= $ortalama_puan ? 'filled' : ''; ?>"></i>
                    <?php endfor; ?>
                </div>
                <div class="rating-count"><?php echo $yorum_sayisi; ?> değerlendirme</div>
            </div>
        </div>

        <!-- Add Review -->
        <?php if (isLoggedIn()): ?>
        <div class="add-review">
            <h3>Ürünü Değerlendir</h3>
            <form method="POST">
                <div class="star-rating-input">
                    <label>Puanınız:</label>
                    <div class="stars-input">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="puan" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                        <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="yorum">Yorumunuz:</label>
                    <textarea name="yorum" id="yorum" rows="4" placeholder="Ürün hakkında deneyiminizi paylaşın..." required></textarea>
                </div>
                
                <button type="submit" name="yorum_gonder" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Yorum Gönder
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="login-prompt">
            <p>Yorum yapabilmek için <a href="login.php">giriş yapmalısınız</a>.</p>
        </div>
        <?php endif; ?>

        <!-- Reviews List -->
        <div class="reviews-list">
            <?php if (empty($yorumlar)): ?>
            <p class="no-reviews">Henüz değerlendirme yapılmamış.</p>
            <?php else: ?>
            <?php foreach ($yorumlar as $yorum): ?>
            <div class="review-item">
                <div class="review-header">
                    <div class="reviewer-info">
                        <strong><?php echo htmlspecialchars($yorum['ad'] . ' ' . substr($yorum['soyad'], 0, 1) . '.'); ?></strong>
                        <div class="review-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $yorum['puan'] ? 'filled' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <span class="review-date"><?php echo formatDate($yorum['yorum_tarihi']); ?></span>
                </div>
                <p class="review-text"><?php echo nl2br(htmlspecialchars($yorum['yorum'])); ?></p>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Similar Products -->
    <?php if (!empty($benzer_urunler)): ?>
    <div class="similar-products">
        <h2>Benzer Ürünler</h2>
        <div class="products-grid">
            <?php foreach ($benzer_urunler as $b_urun): ?>
            <div class="product-card">
                <?php if ($b_urun['indirimli_fiyat']): 
                    $b_indirim = round((($b_urun['fiyat'] - $b_urun['indirimli_fiyat']) / $b_urun['fiyat']) * 100);
                ?>
                <div class="product-badge discount">-%<?php echo $b_indirim; ?></div>
                <?php endif; ?>
                
                <div class="product-image">
                    <a href="product-detail.php?id=<?php echo $b_urun['id']; ?>">
                        <img src="images/products/<?php echo $b_urun['resim']; ?>" alt="<?php echo htmlspecialchars($b_urun['urun_adi']); ?>" onerror="this.src='images/no-image.jpg'">
                    </a>
                </div>
                <div class="product-info">
                    <h3><a href="product-detail.php?id=<?php echo $b_urun['id']; ?>"><?php echo htmlspecialchars($b_urun['urun_adi']); ?></a></h3>
                    <div class="product-price">
                        <?php if ($b_urun['indirimli_fiyat']): ?>
                        <span class="old-price"><?php echo formatPrice($b_urun['fiyat']); ?></span>
                        <span class="new-price"><?php echo formatPrice($b_urun['indirimli_fiyat']); ?></span>
                        <?php else: ?>
                        <span class="new-price"><?php echo formatPrice($b_urun['fiyat']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.breadcrumb {
    padding: 20px 0;
    font-size: 14px;
}

.breadcrumb a {
    color: var(--text-light);
    text-decoration: none;
}

.breadcrumb a:hover {
    color: var(--primary-color);
}

.breadcrumb span:not(:last-child) {
    margin: 0 10px;
    color: var(--text-light);
}

.product-detail-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    margin-bottom: 60px;
}

.product-detail-image {
    background-color: #fff;
    border-radius: 10px;
    padding: 40px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.product-detail-image img {
    width: 100%;
    border-radius: 8px;
}

.product-detail-info {
    background-color: #fff;
    border-radius: 10px;
    padding: 40px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.product-detail-info h1 {
    font-size: 28px;
    margin-bottom: 20px;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.product-rating i.filled {
    color: #fbbf24;
}

.product-rating i:not(.filled) {
    color: var(--border-color);
}

.product-detail-price {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.discount-badge {
    background-color: var(--danger-color);
    color: #fff;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
}

.product-detail-price .old-price {
    text-decoration: line-through;
    color: var(--text-light);
    font-size: 20px;
}

.product-detail-price .current-price {
    color: var(--danger-color);
    font-size: 32px;
    font-weight: bold;
}

.product-stock {
    margin-bottom: 25px;
}

.in-stock {
    color: var(--success-color);
    font-weight: 500;
}

.out-of-stock {
    color: var(--danger-color);
    font-weight: 500;
}

.product-description {
    margin-bottom: 30px;
    padding: 20px;
    background-color: var(--light-color);
    border-radius: 8px;
}

.product-description h3 {
    margin-bottom: 15px;
    font-size: 18px;
}

.product-actions-detail {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
}

.quantity-selector {
    display: flex;
    border: 2px solid var(--border-color);
    border-radius: 5px;
    overflow: hidden;
}

.quantity-selector button {
    width: 45px;
    height: 50px;
    border: none;
    background-color: var(--light-color);
    cursor: pointer;
    font-size: 18px;
    transition: background-color 0.3s;
}

.quantity-selector button:hover {
    background-color: var(--border-color);
}

.quantity-selector input {
    width: 70px;
    text-align: center;
    border: none;
    font-size: 16px;
    font-weight: bold;
}

.btn-add-cart {
    flex: 1;
    height: 50px;
    font-size: 16px;
}

.product-features {
    padding: 20px;
    background-color: var(--light-color);
    border-radius: 8px;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
}

.feature-item i {
    color: var(--success-color);
    font-size: 20px;
}

.reviews-section {
    background-color: #fff;
    border-radius: 10px;
    padding: 40px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 60px;
}

.reviews-section h2 {
    margin-bottom: 30px;
}

.reviews-summary {
    margin-bottom: 40px;
}

.rating-overview {
    display: flex;
    align-items: center;
    gap: 20px;
}

.rating-score {
    font-size: 48px;
    font-weight: bold;
    color: var(--primary-color);
}

.rating-count {
    color: var(--text-light);
}

.add-review {
    background-color: var(--light-color);
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 40px;
}

.star-rating-input {
    margin-bottom: 20px;
}

.stars-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}

.stars-input input {
    display: none;
}

.stars-input label {
    cursor: pointer;
    font-size: 32px;
    color: var(--border-color);
    transition: color 0.2s;
}

.stars-input input:checked ~ label,
.stars-input label:hover,
.stars-input label:hover ~ label {
    color: #fbbf24;
}

.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: 5px;
    font-family: inherit;
    resize: vertical;
}

.login-prompt {
    text-align: center;
    padding: 30px;
    background-color: var(--light-color);
    border-radius: 8px;
    margin-bottom: 40px;
}

.reviews-list {
    max-width: 800px;
}

.review-item {
    padding: 20px 0;
    border-bottom: 1px solid var(--border-color);
}

.review-item:last-child {
    border-bottom: none;
}

.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.review-stars i.filled {
    color: #fbbf24;
}

.review-date {
    color: var(--text-light);
    font-size: 14px;
}

.review-text {
    color: var(--text-color);
    line-height: 1.6;
}

.no-reviews {
    text-align: center;
    color: var(--text-light);
    padding: 40px;
}

.similar-products {
    margin-bottom: 40px;
}

.similar-products h2 {
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .product-detail-container {
        grid-template-columns: 1fr;
    }

    .product-actions-detail {
        flex-direction: column;
    }

    .quantity-selector {
        justify-content: center;
    }
}
</style>

<script>
function changeQty(change) {
    const input = document.getElementById('quantity');
    const currentValue = parseInt(input.value);
    const max = parseInt(input.max);
    const min = parseInt(input.min);
    
    let newValue = currentValue + change;
    if (newValue >= min && newValue <= max) {
        input.value = newValue;
    }
}

async function addToCartDetail(productId) {
    const quantity = document.getElementById('quantity').value;
    
    try {
        const response = await fetch('cart-add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'urun_id=' + productId + '&adet=' + quantity
        });

        const result = await response.json();

        if (result.success) {
            showNotification('Ürün sepete eklendi!', 'success');
            
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                cartBadge.textContent = result.cart_count;
            }
        } else {
            showNotification(result.message || 'Bir hata oluştu!', 'error');
        }
    } catch (error) {
        showNotification('Bağlantı hatası!', 'error');
    }
}
</script>

<?php include 'footer.php'; ?>
