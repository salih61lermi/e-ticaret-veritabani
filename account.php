<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$db = Database::getInstance()->getConnection();
$kullanici_id = $_SESSION['kullanici_id'];
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';

// Kullanıcı bilgilerini al
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$kullanici_id]);
$user = $stmt->fetch();

// Profil güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $ad = cleanInput($_POST['ad']);
    $soyad = cleanInput($_POST['soyad']);
    $telefon = cleanInput($_POST['telefon']);
    $dogum_tarihi = $_POST['dogum_tarihi'] ? $_POST['dogum_tarihi'] : null;
    
    $stmt = $db->prepare("UPDATE users SET ad = ?, soyad = ?, telefon = ?, dogum_tarihi = ? WHERE id = ?");
    if ($stmt->execute([$ad, $soyad, $telefon, $dogum_tarihi, $kullanici_id])) {
        $_SESSION['ad'] = $ad;
        $_SESSION['soyad'] = $soyad;
        showAlert('Profil bilgileriniz güncellendi.', 'success');
        redirect('account.php?tab=profile');
    }
}

// Şifre değiştirme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $eski_sifre = $_POST['eski_sifre'];
    $yeni_sifre = $_POST['yeni_sifre'];
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'];
    
    if ($user['sifre'] !== $eski_sifre) {
        showAlert('Eski şifre hatalı!', 'error');
    } elseif ($yeni_sifre !== $yeni_sifre_tekrar) {
        showAlert('Yeni şifreler eşleşmiyor!', 'error');
    } elseif (strlen($yeni_sifre) < 6) {
        showAlert('Şifre en az 6 karakter olmalı!', 'error');
    } else {
        $stmt = $db->prepare("UPDATE users SET sifre = ? WHERE id = ?");
        if ($stmt->execute([$yeni_sifre, $kullanici_id])) {
            showAlert('Şifreniz başarıyla değiştirildi.', 'success');
            redirect('account.php?tab=security');
        }
    }
}

// Siparişleri al
$stmt = $db->prepare("SELECT * FROM orders WHERE kullanici_id = ? ORDER BY siparis_tarihi DESC");
$stmt->execute([$kullanici_id]);
$siparisler = $stmt->fetchAll();

// Adresleri al
$stmt = $db->prepare("SELECT * FROM addresses WHERE kullanici_id = ? ORDER BY varsayilan DESC, id DESC");
$stmt->execute([$kullanici_id]);
$adresler = $stmt->fetchAll();

// Favorileri al
$stmt = $db->prepare("SELECT w.*, p.urun_adi, p.fiyat, p.indirimli_fiyat, p.resim, p.durum 
                      FROM wishlist w 
                      JOIN products p ON w.urun_id = p.id 
                      WHERE w.kullanici_id = ?
                      ORDER BY w.ekleme_tarihi DESC");
$stmt->execute([$kullanici_id]);
$favoriler = $stmt->fetchAll();

$page_title = "Hesabım";
include 'header.php';
?>

<div class="container" style="padding: 40px 20px;">
    <div class="account-page">
        <h1><i class="fas fa-user-circle"></i> Hesabım</h1>
        
        <div class="account-container">
            <!-- Sidebar -->
            <div class="account-sidebar">
                <div class="user-info-box">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($user['ad'] . ' ' . $user['soyad']); ?></h3>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>

                <nav class="account-nav">
                    <a href="?tab=profile" class="<?php echo $active_tab == 'profile' ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> Profil Bilgilerim
                    </a>
                    <a href="?tab=orders" class="<?php echo $active_tab == 'orders' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-bag"></i> Siparişlerim
                        <?php if(count($siparisler) > 0): ?>
                        <span class="badge"><?php echo count($siparisler); ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=addresses" class="<?php echo $active_tab == 'addresses' ? 'active' : ''; ?>">
                        <i class="fas fa-map-marker-alt"></i> Adreslerim
                    </a>
                    <a href="?tab=wishlist" class="<?php echo $active_tab == 'wishlist' ? 'active' : ''; ?>">
                        <i class="fas fa-heart"></i> Favorilerim
                        <?php if(count($favoriler) > 0): ?>
                        <span class="badge"><?php echo count($favoriler); ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=security" class="<?php echo $active_tab == 'security' ? 'active' : ''; ?>">
                        <i class="fas fa-lock"></i> Güvenlik
                    </a>
                    <a href="logout.php" style="color: var(--danger-color);">
                        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                    </a>
                </nav>
            </div>

            <!-- Content -->
            <div class="account-content">
                <?php if ($active_tab == 'profile'): ?>
                <!-- Profil Bilgileri -->
                <div class="content-box">
                    <h2><i class="fas fa-user"></i> Profil Bilgilerim</h2>
                    <form method="POST" class="account-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Ad</label>
                                <input type="text" name="ad" value="<?php echo htmlspecialchars($user['ad']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Soyad</label>
                                <input type="text" name="soyad" value="<?php echo htmlspecialchars($user['soyad']); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>E-posta</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small>E-posta adresi değiştirilemez</small>
                            </div>
                            <div class="form-group">
                                <label>Telefon</label>
                                <input type="tel" name="telefon" value="<?php echo htmlspecialchars($user['telefon']); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Doğum Tarihi</label>
                                <input type="date" name="dogum_tarihi" value="<?php echo $user['dogum_tarihi']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Üyelik Tarihi</label>
                                <input type="text" value="<?php echo formatDate($user['kayit_tarihi']); ?>" disabled>
                            </div>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Değişiklikleri Kaydet
                        </button>
                    </form>
                </div>

                <?php elseif ($active_tab == 'orders'): ?>
                <!-- Siparişlerim -->
                <div class="content-box">
                    <h2><i class="fas fa-shopping-bag"></i> Siparişlerim</h2>
                    
                    <?php if (empty($siparisler)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>Henüz siparişiniz yok</h3>
                        <p>Alışverişe başlamak için ürünlere göz atabilirsiniz.</p>
                        <a href="products.php" class="btn btn-primary">Alışverişe Başla</a>
                    </div>
                    <?php else: ?>
                    <div class="orders-list">
                        <?php foreach ($siparisler as $siparis): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <h3>Sipariş #<?php echo $siparis['siparis_no']; ?></h3>
                                    <p><?php echo formatDate($siparis['siparis_tarihi']); ?></p>
                                </div>
                                <span class="status-badge status-<?php echo $siparis['durum']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $siparis['durum'])); ?>
                                </span>
                            </div>
                            <div class="order-body">
                                <div class="order-info">
                                    <p><strong>Toplam:</strong> <?php echo formatPrice($siparis['genel_toplam']); ?></p>
                                    <p><strong>Ödeme:</strong> <?php echo ucfirst(str_replace('_', ' ', $siparis['odeme_yontemi'])); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php elseif ($active_tab == 'addresses'): ?>
                <!-- Adreslerim -->
                <div class="content-box">
                    <h2><i class="fas fa-map-marker-alt"></i> Adreslerim</h2>
                    
                    <?php if (empty($adresler)): ?>
                    <div class="empty-state">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>Kayıtlı adresiniz yok</h3>
                        <p>Hızlı teslimat için adres ekleyin.</p>
                    </div>
                    <?php else: ?>
                    <div class="addresses-grid">
                        <?php foreach ($adresler as $adres): ?>
                        <div class="address-card <?php echo $adres['varsayilan'] ? 'default' : ''; ?>">
                            <?php if ($adres['varsayilan']): ?>
                            <span class="default-badge">Varsayılan</span>
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($adres['adres_baslik']); ?></h3>
                            <p><strong><?php echo htmlspecialchars($adres['ad_soyad']); ?></strong></p>
                            <p><?php echo htmlspecialchars($adres['adres']); ?></p>
                            <p><?php echo htmlspecialchars($adres['ilce'] . ' / ' . $adres['il']); ?></p>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($adres['telefon']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php elseif ($active_tab == 'wishlist'): ?>
                <!-- Favorilerim -->
                <div class="content-box">
                    <h2><i class="fas fa-heart"></i> Favorilerim</h2>
                    
                    <?php if (empty($favoriler)): ?>
                    <div class="empty-state">
                        <i class="fas fa-heart"></i>
                        <h3>Favori ürününüz yok</h3>
                        <p>Beğendiğiniz ürünleri favorilere ekleyin.</p>
                        <a href="products.php" class="btn btn-primary">Ürünlere Göz At</a>
                    </div>
                    <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($favoriler as $fav): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="images/products/<?php echo $fav['resim']; ?>" alt="<?php echo htmlspecialchars($fav['urun_adi']); ?>" onerror="this.src='images/no-image.jpg'">
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($fav['urun_adi']); ?></h3>
                                <div class="product-price">
                                    <?php if ($fav['indirimli_fiyat']): ?>
                                    <span class="old-price"><?php echo formatPrice($fav['fiyat']); ?></span>
                                    <span class="new-price"><?php echo formatPrice($fav['indirimli_fiyat']); ?></span>
                                    <?php else: ?>
                                    <span class="new-price"><?php echo formatPrice($fav['fiyat']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-actions">
                                    <a href="product-detail.php?id=<?php echo $fav['urun_id']; ?>" class="btn btn-outline">Detay</a>
                                    <button onclick="addToCart(<?php echo $fav['urun_id']; ?>)" class="btn btn-primary btn-sm">Sepete Ekle</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php elseif ($active_tab == 'security'): ?>
                <!-- Güvenlik -->
                <div class="content-box">
                    <h2><i class="fas fa-lock"></i> Güvenlik Ayarları</h2>
                    <form method="POST" class="account-form">
                        <div class="form-group">
                            <label>Mevcut Şifre</label>
                            <input type="password" name="eski_sifre" required>
                        </div>

                        <div class="form-group">
                            <label>Yeni Şifre</label>
                            <input type="password" name="yeni_sifre" minlength="6" required>
                            <small>En az 6 karakter</small>
                        </div>

                        <div class="form-group">
                            <label>Yeni Şifre (Tekrar)</label>
                            <input type="password" name="yeni_sifre_tekrar" required>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key"></i> Şifreyi Değiştir
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.account-page h1 { margin-bottom: 30px; font-size: 32px; }
.account-container { display: grid; grid-template-columns: 300px 1fr; gap: 30px; }
.account-sidebar { background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); height: fit-content; position: sticky; top: 120px; }
.user-info-box { text-align: center; padding-bottom: 30px; border-bottom: 2px solid var(--border-color); margin-bottom: 20px; }
.user-avatar { width: 80px; height: 80px; background: var(--light-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; }
.user-avatar i { font-size: 50px; color: var(--primary-color); }
.user-info-box h3 { margin-bottom: 5px; font-size: 18px; }
.user-info-box p { color: var(--text-light); font-size: 14px; }
.account-nav { display: flex; flex-direction: column; gap: 5px; }
.account-nav a { display: flex; align-items: center; gap: 10px; padding: 12px 15px; color: var(--text-color); text-decoration: none; border-radius: 5px; transition: all 0.3s; }
.account-nav a:hover, .account-nav a.active { background: var(--primary-color); color: #fff; }
.account-nav .badge { margin-left: auto; background: var(--danger-color); color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
.account-content { background: #fff; border-radius: 10px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); min-height: 500px; }
.content-box h2 { margin-bottom: 30px; color: var(--dark-color); font-size: 24px; }
.account-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--dark-color); }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 5px; font-size: 15px; }
.form-group input:focus { border-color: var(--primary-color); outline: none; }
.form-group small { display: block; margin-top: 5px; color: var(--text-light); font-size: 13px; }
.empty-state { text-align: center; padding: 60px 20px; }
.empty-state i { font-size: 80px; color: var(--text-light); margin-bottom: 20px; }
.empty-state h3 { font-size: 24px; margin-bottom: 10px; }
.orders-list { display: flex; flex-direction: column; gap: 20px; }
.order-card { border: 2px solid var(--border-color); border-radius: 8px; padding: 20px; transition: all 0.3s; }
.order-card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.order-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color); }
.order-header h3 { font-size: 18px; margin-bottom: 5px; }
.order-header p { color: var(--text-light); font-size: 14px; }
.order-body { display: flex; justify-content: space-between; align-items: center; }
.order-info p { margin: 5px 0; }
.status-badge { padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
.status-beklemede { background: #fef3c7; color: #92400e; }
.status-onaylandi { background: #dbeafe; color: #1e40af; }
.status-hazirlaniyor { background: #fde68a; color: #78350f; }
.status-kargoda { background: #e0e7ff; color: #3730a3; }
.status-teslim_edildi { background: #d1fae5; color: #065f46; }
.status-iptal { background: #fee2e2; color: #991b1b; }
.addresses-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
.address-card { border: 2px solid var(--border-color); border-radius: 8px; padding: 20px; position: relative; transition: all 0.3s; }
.address-card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.address-card.default { border-color: var(--success-color); background: #f0fdf4; }
.default-badge { position: absolute; top: 10px; right: 10px; background: var(--success-color); color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 11px; }
.address-card h3 { font-size: 18px; margin-bottom: 10px; color: var(--primary-color); }
.address-card p { margin: 5px 0; color: var(--text-color); }
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 25px; }
.btn-sm { padding: 8px 15px; font-size: 14px; }
@media (max-width: 992px) { 
    .account-container { grid-template-columns: 1fr; } 
    .account-sidebar { position: static; } 
    .account-form .form-row { grid-template-columns: 1fr; } 
    .products-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); }
}
</style>

<?php include 'footer.php'; ?>
