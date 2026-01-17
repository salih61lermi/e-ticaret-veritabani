<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

// Ana kategorileri al (üst kategori olmayanlar)
$stmt = $db->query("SELECT * FROM categories WHERE ust_kategori_id IS NULL AND aktif = 1 ORDER BY sira LIMIT 6");
$kategoriler = $stmt->fetchAll();

// Öne çıkan ürünleri al (vitrin ürünleri)
$stmt = $db->query("SELECT p.*, c.kategori_adi 
                    FROM products p 
                    JOIN categories c ON p.kategori_id = c.id 
                    WHERE p.durum = 'aktif' AND p.vitrin = 1
                    ORDER BY p.olusturma_tarihi DESC 
                    LIMIT 8");
$urunler = $stmt->fetchAll();

// Eğer vitrin ürünü yoksa, en yeni ürünleri göster
if(empty($urunler)) {
    $stmt = $db->query("SELECT p.*, c.kategori_adi 
                        FROM products p 
                        JOIN categories c ON p.kategori_id = c.id 
                        WHERE p.durum = 'aktif' 
                        ORDER BY p.olusturma_tarihi DESC 
                        LIMIT 8");
    $urunler = $stmt->fetchAll();
}

// İndirimli ürünleri al (kampanyalı ürünler)
$stmt = $db->query("SELECT p.*, c.kategori_adi 
                    FROM products p 
                    JOIN categories c ON p.kategori_id = c.id 
                    WHERE p.durum = 'aktif' AND p.indirimli_fiyat IS NOT NULL AND p.kampanyali = 1
                    ORDER BY (p.fiyat - p.indirimli_fiyat) DESC 
                    LIMIT 4");
$indirimli_urunler = $stmt->fetchAll();

$page_title = "Ana Sayfa";
include 'header.php';
?>

<!-- Hero Slider -->
<section class="hero-slider">
    <div class="slider-container">
        <div class="slide active" style="background-image: url('images/banners/slide1.jpg');">
            <div class="slide-overlay"></div>
            <div class="slide-content">
                <h1>Yeni Sezon Ürünler</h1>
                <p>En yeni teknoloji ürünleri ile tanışın</p>
                <a href="products.php" class="btn btn-primary">Alışverişe Başla</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('images/banners/slide2.jpg');">
            <div class="slide-overlay"></div>
            <div class="slide-content">
                <h1>Kampanyalı Ürünler</h1>
                <p>%50'ye varan indirimler</p>
                <a href="products.php?indirim=1" class="btn btn-primary">İndirimleri Gör</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('images/banners/slide3.jpg');">
            <div class="slide-overlay"></div>
            <div class="slide-content">
                <h1>Ücretsiz Kargo</h1>
                <p>500 TL ve üzeri alışverişlerinizde</p>
                <a href="products.php" class="btn btn-primary">Keşfet</a>
            </div>
        </div>
    </div>
    <button class="slider-btn prev" onclick="changeSlide(-1)">&#10094;</button>
    <button class="slider-btn next" onclick="changeSlide(1)">&#10095;</button>
    <div class="slider-dots">
        <span class="dot active" onclick="goToSlide(0)"></span>
        <span class="dot" onclick="goToSlide(1)"></span>
        <span class="dot" onclick="goToSlide(2)"></span>
    </div>
</section>

<!-- Kategoriler -->
<section class="categories-section">
    <div class="container">
        <h2 class="section-title">Kategoriler</h2>
        <div class="categories-grid">
            <?php foreach($kategoriler as $kategori): ?>
            <a href="products.php?kategori=<?php echo $kategori['id']; ?>" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-<?php 
                        echo match($kategori['kategori_adi']) {
                            'Elektronik' => 'laptop',
                            'Giyim' => 'tshirt',
                            'Ev & Yaşam' => 'home',
                            default => 'box'
                        };
                    ?>"></i>
                </div>
                <h3><?php echo htmlspecialchars($kategori['kategori_adi']); ?></h3>
                <p><?php echo htmlspecialchars($kategori['aciklama']); ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- İndirimli Ürünler -->
<?php if(!empty($indirimli_urunler)): ?>
<section class="deals-section">
    <div class="container">
        <h2 class="section-title">Kampanyalı Ürünler</h2>
        <div class="products-grid">
            <?php foreach($indirimli_urunler as $urun): 
                $indirim_orani = round((($urun['fiyat'] - $urun['indirimli_fiyat']) / $urun['fiyat']) * 100);
            ?>
            <div class="product-card">
                <div class="product-badge discount">-%<?php echo $indirim_orani; ?></div>
                <div class="product-image">
                    <img src="images/products/<?php echo $urun['resim']; ?>" alt="<?php echo htmlspecialchars($urun['urun_adi']); ?>" onerror="this.src='images/no-image.jpg'">
                </div>
                <div class="product-info">
                    <h3><?php echo htmlspecialchars($urun['urun_adi']); ?></h3>
                    <p class="product-category"><?php echo htmlspecialchars($urun['kategori_adi']); ?></p>
                    <div class="product-price">
                        <span class="old-price"><?php echo formatPrice($urun['fiyat']); ?></span>
                        <span class="new-price"><?php echo formatPrice($urun['indirimli_fiyat']); ?></span>
                    </div>
                    <div class="product-actions">
                        <a href="product-detail.php?id=<?php echo $urun['id']; ?>" class="btn btn-outline">Detay</a>
                        <button onclick="addToCart(<?php echo $urun['id']; ?>)" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Sepete Ekle
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Yeni Ürünler -->
<section class="products-section">
    <div class="container">
        <h2 class="section-title">Yeni Ürünler</h2>
        <div class="products-grid">
            <?php foreach($urunler as $urun): ?>
            <div class="product-card">
                <?php if($urun['indirimli_fiyat']): 
                    $indirim_orani = round((($urun['fiyat'] - $urun['indirimli_fiyat']) / $urun['fiyat']) * 100);
                ?>
                <div class="product-badge discount">-%<?php echo $indirim_orani; ?></div>
                <?php else: ?>
                <div class="product-badge new">Yeni</div>
                <?php endif; ?>
                
                <div class="product-image">
                    <img src="images/products/<?php echo $urun['resim']; ?>" alt="<?php echo htmlspecialchars($urun['urun_adi']); ?>" onerror="this.src='images/no-image.jpg'">
                </div>
                <div class="product-info">
                    <h3><?php echo htmlspecialchars($urun['urun_adi']); ?></h3>
                    <p class="product-category"><?php echo htmlspecialchars($urun['kategori_adi']); ?></p>
                    <div class="product-price">
                        <?php if($urun['indirimli_fiyat']): ?>
                        <span class="old-price"><?php echo formatPrice($urun['fiyat']); ?></span>
                        <span class="new-price"><?php echo formatPrice($urun['indirimli_fiyat']); ?></span>
                        <?php else: ?>
                        <span class="new-price"><?php echo formatPrice($urun['fiyat']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-actions">
                        <a href="product-detail.php?id=<?php echo $urun['id']; ?>" class="btn btn-outline">Detay</a>
                        <button onclick="addToCart(<?php echo $urun['id']; ?>)" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Sepete Ekle
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Özellikler -->
<section class="features-section">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-shipping-fast"></i>
                <h3>Ücretsiz Kargo</h3>
                <p>500 TL üzeri alışverişlerde</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-undo"></i>
                <h3>Kolay İade</h3>
                <p>14 gün içinde ücretsiz iade</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-lock"></i>
                <h3>Güvenli Ödeme</h3>
                <p>SSL sertifikalı ödeme</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-headset"></i>
                <h3>7/24 Destek</h3>
                <p>Müşteri hizmetleri desteği</p>
            </div>
        </div>
    </div>
</section>

<script>
let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.dot');

function showSlide(n) {
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    if (n >= slides.length) currentSlide = 0;
    if (n < 0) currentSlide = slides.length - 1;
    
    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
}

function changeSlide(n) {
    currentSlide += n;
    showSlide(currentSlide);
}

function goToSlide(n) {
    currentSlide = n;
    showSlide(currentSlide);
}

// Otomatik slider
setInterval(() => {
    currentSlide++;
    showSlide(currentSlide);
}, 5000);
</script>

<?php include 'footer.php'; ?>