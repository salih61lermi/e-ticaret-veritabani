<?php
require_once '../config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();

// İstatistikler
$stats = [
    'products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'orders' => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'users' => $db->query("SELECT COUNT(*) FROM users WHERE rol = 'musteri'")->fetchColumn(),
    'reviews' => $db->query("SELECT COUNT(*) FROM reviews WHERE onay_durumu = 'beklemede'")->fetchColumn(),
    'revenue' => $db->query("SELECT SUM(genel_toplam) FROM orders WHERE odeme_durumu = 'tamamlandi'")->fetchColumn(),
    'pending_orders' => $db->query("SELECT COUNT(*) FROM orders WHERE durum = 'beklemede'")->fetchColumn()
];

// Son siparişler
$recent_orders = $db->query("SELECT o.*, u.ad, u.soyad 
                             FROM orders o 
                             JOIN users u ON o.kullanici_id = u.id 
                             ORDER BY o.siparis_tarihi DESC 
                             LIMIT 10")->fetchAll();

// Düşük stoklu ürünler
$low_stock = $db->query("SELECT * FROM products 
                         WHERE stok_miktari <= kritik_stok_seviyesi 
                         AND durum = 'aktif' 
                         ORDER BY stok_miktari ASC 
                         LIMIT 5")->fetchAll();

$page_title = "Dashboard";
include 'header.php';
?>

<div class="dashboard">
    <div class="page-header">
        <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <p>E-Ticaret yönetim paneline hoş geldiniz</p>
    </div>

    <!-- İstatistik Kartları -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-box"></i></div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['products']); ?></h3>
                <p>Toplam Ürün</p>
            </div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['orders']); ?></h3>
                <p>Toplam Sipariş</p>
            </div>
        </div>

        <div class="stat-card orange">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['users']); ?></h3>
                <p>Toplam Müşteri</p>
            </div>
        </div>

        <div class="stat-card purple">
            <div class="stat-icon"><i class="fas fa-lira-sign"></i></div>
            <div class="stat-info">
                <h3><?php echo formatPrice($stats['revenue']); ?></h3>
                <p>Toplam Gelir</p>
            </div>
        </div>
    </div>

    <!-- Bekleyen İşlemler -->
    <div class="alerts-section">
        <?php if ($stats['pending_orders'] > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong><?php echo $stats['pending_orders']; ?> adet bekleyen sipariş var!</strong>
            <a href="orders.php?durum=beklemede" class="btn btn-sm">Görüntüle</a>
        </div>
        <?php endif; ?>

        <?php if ($stats['reviews'] > 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-comment"></i>
            <strong><?php echo $stats['reviews']; ?> adet onay bekleyen yorum var!</strong>
            <a href="reviews.php?durum=beklemede" class="btn btn-sm">Görüntüle</a>
        </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-grid">
        <!-- Son Siparişler -->
        <div class="dashboard-box">
            <div class="box-header">
                <h2><i class="fas fa-shopping-bag"></i> Son Siparişler</h2>
                <a href="orders.php" class="btn btn-sm">Tümünü Gör</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Sipariş No</th>
                            <th>Müşteri</th>
                            <th>Tutar</th>
                            <th>Durum</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px; color: #999;">Henüz sipariş yok</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td><strong><?php echo $order['siparis_no']; ?></strong></td>
                            <td><?php echo htmlspecialchars($order['ad'] . ' ' . $order['soyad']); ?></td>
                            <td><?php echo formatPrice($order['genel_toplam']); ?></td>
                            <td><span class="status-badge status-<?php echo $order['durum']; ?>"><?php echo ucfirst(str_replace('_', ' ', $order['durum'])); ?></span></td>
                            <td><?php echo formatDate($order['siparis_tarihi']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Düşük Stoklu Ürünler -->
        <div class="dashboard-box">
            <div class="box-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Düşük Stoklu Ürünler</h2>
                <a href="products.php" class="btn btn-sm">Tümünü Gör</a>
            </div>
            <?php if (empty($low_stock)): ?>
            <div style="padding: 30px; text-align: center; color: #999;">
                <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                <p>Tüm ürünlerin stoğu yeterli</p>
            </div>
            <?php else: ?>
            <div class="low-stock-list">
                <?php foreach ($low_stock as $product): ?>
                <div class="low-stock-item">
                    <img src="../images/products/<?php echo $product['resim']; ?>" alt="<?php echo htmlspecialchars($product['urun_adi']); ?>" onerror="this.src='../images/no-image.jpg'">
                    <div class="item-info">
                        <h4><?php echo htmlspecialchars($product['urun_adi']); ?></h4>
                        <p>Kod: <?php echo htmlspecialchars($product['urun_kodu']); ?></p>
                    </div>
                    <div class="item-stock">
                        <span class="stock-badge danger"><?php echo $product['stok_miktari']; ?> adet</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
