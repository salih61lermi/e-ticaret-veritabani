<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

// Filtreleme parametreleri
$kategori_id = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$arama = isset($_GET['arama']) ? cleanInput($_GET['arama']) : '';
$indirim = isset($_GET['indirim']) ? 1 : 0;
$sira = isset($_GET['sira']) ? cleanInput($_GET['sira']) : 'yeni';

// Sayfalama
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 12;
$offset = ($sayfa - 1) * $limit;

// SQL sorgusu oluştur
$where = ["p.durum = 'aktif'"];
$params = [];

if ($kategori_id > 0) {
    $where[] = "p.kategori_id = ?";
    $params[] = $kategori_id;
}

if ($arama) {
    $where[] = "(p.urun_adi LIKE ? OR p.aciklama LIKE ?)";
    $params[] = "%$arama%";
    $params[] = "%$arama%";
}

if ($indirim) {
    $where[] = "p.indirimli_fiyat IS NOT NULL";
}

$where_sql = implode(" AND ", $where);

// Sıralama
$order_by = match($sira) {
    'ucuz' => 'COALESCE(p.indirimli_fiyat, p.fiyat) ASC',
    'pahali' => 'COALESCE(p.indirimli_fiyat, p.fiyat) DESC',
    'az' => 'p.urun_adi ASC',
    'za' => 'p.urun_adi DESC',
    default => 'p.olusturma_tarihi DESC'
};

// Toplam ürün sayısı
$count_sql = "SELECT COUNT(*) as toplam FROM products p WHERE $where_sql";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$toplam_urun = $count_stmt->fetch()['toplam'];
$toplam_sayfa = ceil($toplam_urun / $limit);

// Ürünleri getir
$sql = "SELECT p.*, c.kategori_adi 
        FROM products p 
        JOIN categories c ON p.kategori_id = c.id 
        WHERE $where_sql 
        ORDER BY $order_by 
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$urunler = $stmt->fetchAll();

// Kategorileri al
$kategoriler = $db->query("SELECT * FROM categories WHERE aktif = 1 ORDER BY sira")->fetchAll();

// Sayfa başlığı
$page_title = $kategori_id > 0 ? 
    $db->query("SELECT kategori_adi FROM categories WHERE id = $kategori_id")->fetch()['kategori_adi'] . " - Ürünler" :
    ($arama ? "\"$arama\" Arama Sonuçları" : "Tüm Ürünler");

include 'header.php';
?>

<div class="container" style="padding: 40px 20px;">
    <div class="products-page">
        <!-- Sidebar -->
        <aside class="products-sidebar">
            <div class="sidebar-section">
                <h3>Kategoriler</h3>
                <ul class="category-list">
                    <li>
                        <a href="products.php" class="<?php echo $kategori_id == 0 ? 'active' : ''; ?>">
                            Tüm Ürünler (<?php echo $toplam_urun; ?>)
                        </a>
                    </li>
                    <?php foreach($kategoriler as $kat): 
                        $kat_stmt = $db->prepare("SELECT COUNT(*) as sayi FROM products WHERE kategori_id = ? AND durum = 'aktif'");
                        $kat_stmt->execute([$kat['id']]);
                        $kat_sayi = $kat_stmt->fetch()['sayi'];
                    ?>
                    <li>
                        <a href="products.php?kategori=<?php echo $kat['id']; ?>" 
                           class="<?php echo $kategori_id == $kat['id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($kat['kategori_adi']); ?> (<?php echo $kat_sayi; ?>)
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="sidebar-section">
                <h3>Filtreler</h3>
                <label class="filter-checkbox">
                    <input type="checkbox" <?php echo $indirim ? 'checked' : ''; ?> 
                           onchange="location.href='<?php echo $indirim ? 'products.php' : 'products.php?indirim=1'; ?>'">
                    <span>Sadece İndirimli Ürünler</span>
                </label>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="products-main">
            <!-- Header -->
            <div class="products-header">
                <div class="products-info">
                    <h1><?php echo htmlspecialchars($page_title); ?></h1>
                    <p><?php echo $toplam_urun; ?> ürün bulundu</p>
                </div>
                
                <div class="products-sort">
                    <label>Sırala:</label>
                    <select onchange="location.href=this.value">
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sira' => 'yeni'])); ?>" 
                                <?php echo $sira == 'yeni' ? 'selected' : ''; ?>>En Yeni</option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sira' => 'ucuz'])); ?>" 
                                <?php echo $sira == 'ucuz' ? 'selected' : ''; ?>>En Ucuz</option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sira' => 'pahali'])); ?>" 
                                <?php echo $sira == 'pahali' ? 'selected' : ''; ?>>En Pahalı</option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sira' => 'az'])); ?>" 
                                <?php echo $sira == 'az' ? 'selected' : ''; ?>>A-Z</option>
                        <option value="?<?php echo http_build_query(array_merge($_GET, ['sira' => 'za'])); ?>" 
                                <?php echo $sira == 'za' ? 'selected' : ''; ?>>Z-A</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <?php if(empty($urunler)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-basket"></i>
                <h2>Ürün Bulunamadı</h2>
                <p>Aradığınız kriterlere uygun ürün bulunamadı.</p>
                <a href="products.php" class="btn btn-primary">Tüm Ürünleri Gör</a>
            </div>
            <?php else: ?>
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
                        <a href="product-detail.php?id=<?php echo $urun['id']; ?>">
                            <img src="images/products/<?php echo $urun['resim']; ?>" 
                                 alt="<?php echo htmlspecialchars($urun['urun_adi']); ?>" 
                                 onerror="this.src='images/no-image.jpg'">
                        </a>
                    </div>
                    <div class="product-info">
                        <h3>
                            <a href="product-detail.php?id=<?php echo $urun['id']; ?>">
                                <?php echo htmlspecialchars($urun['urun_adi']); ?>
                            </a>
                        </h3>
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

            <!-- Pagination -->
            <?php if($toplam_sayfa > 1): ?>
            <div class="pagination">
                <?php if($sayfa > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sayfa' => $sayfa-1])); ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i> Önceki
                </a>
                <?php endif; ?>

                <?php for($i = 1; $i <= $toplam_sayfa; $i++): 
                    if($i == 1 || $i == $toplam_sayfa || ($i >= $sayfa-2 && $i <= $sayfa+2)):
                ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sayfa' => $i])); ?>" 
                   class="page-link <?php echo $i == $sayfa ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php 
                    elseif($i == $sayfa-3 || $i == $sayfa+3):
                        echo '<span class="page-dots">...</span>';
                    endif;
                endfor; ?>

                <?php if($sayfa < $toplam_sayfa): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['sayfa' => $sayfa+1])); ?>" class="page-link">
                    Sonraki <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.products-page {
    display: flex;
    gap: 30px;
}

.products-sidebar {
    width: 280px;
    flex-shrink: 0;
}

.sidebar-section {
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.sidebar-section h3 {
    margin-bottom: 15px;
    font-size: 18px;
    color: var(--dark-color);
}

.category-list {
    list-style: none;
}

.category-list li {
    margin-bottom: 10px;
}

.category-list a {
    display: block;
    padding: 10px;
    color: var(--text-color);
    text-decoration: none;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.category-list a:hover,
.category-list a.active {
    background-color: var(--light-color);
    color: var(--primary-color);
}

.filter-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.products-main {
    flex: 1;
}

.products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.products-info h1 {
    font-size: 28px;
    margin-bottom: 5px;
}

.products-info p {
    color: var(--text-light);
}

.products-sort {
    display: flex;
    align-items: center;
    gap: 10px;
}

.products-sort select {
    padding: 10px 15px;
    border: 2px solid var(--border-color);
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background-color: #fff;
    border-radius: 10px;
}

.empty-state i {
    font-size: 80px;
    color: var(--text-light);
    margin-bottom: 20px;
}

.empty-state h2 {
    margin-bottom: 10px;
}

.empty-state p {
    color: var(--text-light);
    margin-bottom: 20px;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 40px;
}

.page-link {
    padding: 10px 15px;
    background-color: #fff;
    color: var(--text-color);
    text-decoration: none;
    border: 2px solid var(--border-color);
    border-radius: 5px;
    transition: all 0.3s;
}

.page-link:hover,
.page-link.active {
    background-color: var(--primary-color);
    color: #fff;
    border-color: var(--primary-color);
}

.page-dots {
    padding: 10px 5px;
}

@media (max-width: 768px) {
    .products-page {
        flex-direction: column;
    }

    .products-sidebar {
        width: 100%;
    }

    .products-header {
        flex-direction: column;
        gap: 20px;
    }
}
</style>

<?php include 'footer.php'; ?>
