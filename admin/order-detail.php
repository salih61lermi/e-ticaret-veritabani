<?php
require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$db = Database::getInstance()->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Sipariş bilgilerini getir
$stmt = $db->prepare("SELECT o.*, u.ad, u.soyad, u.email, u.telefon 
                      FROM orders o 
                      JOIN users u ON o.kullanici_id = u.id 
                      WHERE o.id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    showAlert('Sipariş bulunamadı!', 'error');
    redirect('orders.php');
}

// Sipariş ürünlerini getir
$stmt = $db->prepare("SELECT oi.*, p.urun_adi, p.urun_kodu, p.resim 
                      FROM order_items oi 
                      JOIN products p ON oi.urun_id = p.id 
                      WHERE oi.siparis_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

// Durum güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $yeni_durum = cleanInput($_POST['durum']);
    $stmt = $db->prepare("UPDATE orders SET durum = ? WHERE id = ?");
    if ($stmt->execute([$yeni_durum, $id])) {
        showAlert('Sipariş durumu güncellendi.', 'success');
        $order['durum'] = $yeni_durum;
    }
}

$page_title = "Sipariş Detayı";
include 'header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-file-invoice"></i> Sipariş Detayı #<?php echo $order['siparis_no']; ?></h1>
    <a href="orders.php" class="btn btn-outline">Geri Dön</a>
</div>

<div class="order-detail-grid">
    <!-- Sol Kolon - Sipariş Bilgileri -->
    <div>
        <!-- Durum Güncelleme -->
        <div class="content-box">
            <h2>Sipariş Durumu</h2>
            <form method="POST">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <select name="durum" class="status-select status-<?php echo $order['durum']; ?>" style="flex: 1;">
                        <option value="beklemede" <?php echo $order['durum'] == 'beklemede' ? 'selected' : ''; ?>>Beklemede</option>
                        <option value="onaylandi" <?php echo $order['durum'] == 'onaylandi' ? 'selected' : ''; ?>>Onaylandı</option>
                        <option value="hazirlaniyor" <?php echo $order['durum'] == 'hazirlaniyor' ? 'selected' : ''; ?>>Hazırlanıyor</option>
                        <option value="kargoda" <?php echo $order['durum'] == 'kargoda' ? 'selected' : ''; ?>>Kargoda</option>
                        <option value="teslim_edildi" <?php echo $order['durum'] == 'teslim_edildi' ? 'selected' : ''; ?>>Teslim Edildi</option>
                        <option value="iptal" <?php echo $order['durum'] == 'iptal' ? 'selected' : ''; ?>>İptal</option>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>

        <!-- Ürünler -->
        <div class="content-box">
            <h2>Sipariş Ürünleri</h2>
            <table>
                <thead>
                    <tr>
                        <th width="80">Resim</th>
                        <th>Ürün</th>
                        <th>Fiyat</th>
                        <th>Adet</th>
                        <th>Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <img src="../images/products/<?php echo $item['resim']; ?>" 
                                 style="width:60px;height:60px;object-fit:cover;border-radius:5px;" 
                                 onerror="this.src='../images/no-image.jpg'">
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($item['urun_adi']); ?></strong><br>
                            <small>Kod: <?php echo htmlspecialchars($item['urun_kodu']); ?></small>
                        </td>
                        <td><?php echo formatPrice($item['birim_fiyat']); ?></td>
                        <td><?php echo $item['adet']; ?></td>
                        <td><strong><?php echo formatPrice($item['toplam_fiyat']); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Toplam -->
            <div style="border-top: 2px solid var(--border-color); margin-top: 20px; padding-top: 20px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>Ara Toplam:</span>
                    <strong><?php echo formatPrice($order['ara_toplam']); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>Kargo:</span>
                    <strong><?php echo formatPrice($order['kargo_ucreti']); ?></strong>
                </div>
                <?php if ($order['indirim_tutari'] > 0): ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: var(--success-color);">
                    <span>İndirim:</span>
                    <strong>-<?php echo formatPrice($order['indirim_tutari']); ?></strong>
                </div>
                <?php endif; ?>
                <div style="display: flex; justify-content: space-between; font-size: 20px; color: var(--primary-color); padding-top: 10px; border-top: 2px solid var(--border-color);">
                    <span>GENEL TOPLAM:</span>
                    <strong><?php echo formatPrice($order['genel_toplam']); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Sağ Kolon - Müşteri ve Adres -->
    <div>
        <!-- Müşteri Bilgileri -->
        <div class="content-box">
            <h2>Müşteri Bilgileri</h2>
            <div class="info-list">
                <div class="info-item">
                    <strong>Ad Soyad:</strong>
                    <span><?php echo htmlspecialchars($order['ad'] . ' ' . $order['soyad']); ?></span>
                </div>
                <div class="info-item">
                    <strong>E-posta:</strong>
                    <span><?php echo htmlspecialchars($order['email']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Telefon:</strong>
                    <span><?php echo htmlspecialchars($order['telefon']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Sipariş Tarihi:</strong>
                    <span><?php echo formatDate($order['siparis_tarihi']); ?></span>
                </div>
            </div>
        </div>

        <!-- Teslimat Adresi -->
        <div class="content-box">
            <h2>Teslimat Adresi</h2>
            <div class="address-box">
                <strong><?php echo htmlspecialchars($order['teslimat_ad_soyad']); ?></strong><br>
                <?php echo nl2br(htmlspecialchars($order['teslimat_adres'])); ?><br>
                <?php echo htmlspecialchars($order['teslimat_ilce'] . ' / ' . $order['teslimat_il']); ?><br>
                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['teslimat_telefon']); ?>
            </div>
        </div>

        <!-- Fatura Adresi -->
        <div class="content-box">
            <h2>Fatura Adresi</h2>
            <div class="address-box">
                <strong><?php echo htmlspecialchars($order['fatura_ad_soyad']); ?></strong><br>
                <?php echo nl2br(htmlspecialchars($order['fatura_adres'])); ?><br>
                <?php echo htmlspecialchars($order['fatura_ilce'] . ' / ' . $order['fatura_il']); ?><br>
                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['fatura_telefon']); ?>
            </div>
        </div>

        <!-- Ödeme Bilgileri -->
        <div class="content-box">
            <h2>Ödeme Bilgileri</h2>
            <div class="info-list">
                <div class="info-item">
                    <strong>Ödeme Yöntemi:</strong>
                    <span><?php echo ucfirst(str_replace('_', ' ', $order['odeme_yontemi'])); ?></span>
                </div>
                <div class="info-item">
                    <strong>Ödeme Durumu:</strong>
                    <span class="badge badge-<?php echo $order['odeme_durumu'] == 'tamamlandi' ? 'success' : 'warning'; ?>">
                        <?php echo ucfirst($order['odeme_durumu']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.order-detail-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
.info-list { display: flex; flex-direction: column; gap: 12px; }
.info-item { display: flex; justify-content: space-between; padding: 10px; background: var(--light-color); border-radius: 5px; }
.address-box { padding: 15px; background: var(--light-color); border-radius: 5px; line-height: 1.8; }
.status-select { padding: 10px 15px; border-radius: 5px; border: 2px solid var(--border-color); font-size: 14px; font-weight: 600; }
.status-select.status-beklemede { background: #fef3c7; color: #92400e; }
.status-select.status-onaylandi { background: #dbeafe; color: #1e40af; }
.status-select.status-hazirlaniyor { background: #fde68a; color: #78350f; }
.status-select.status-kargoda { background: #e0e7ff; color: #3730a3; }
.status-select.status-teslim_edildi { background: #d1fae5; color: #065f46; }
.status-select.status-iptal { background: #fee2e2; color: #991b1b; }
@media (max-width: 1200px) { .order-detail-grid { grid-template-columns: 1fr; } }
</style>

<?php include 'footer.php'; ?>
