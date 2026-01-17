<?php
require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$db = Database::getInstance()->getConnection();

// Durum güncelleme
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['order_id'];
    $durum = cleanInput($_POST['durum']);
    $stmt = $db->prepare("UPDATE orders SET durum = ? WHERE id = ?");
    if ($stmt->execute([$durum, $id])) {
        showAlert('Sipariş durumu güncellendi.', 'success');
    }
    redirect('orders.php');
}

// Filtreleme
$durum_filter = isset($_GET['durum']) ? cleanInput($_GET['durum']) : '';
$where = "1=1";
$params = [];

if ($durum_filter) {
    $where .= " AND o.durum = ?";
    $params[] = $durum_filter;
}

$stmt = $db->prepare("SELECT o.*, u.ad, u.soyad, u.email 
                      FROM orders o 
                      JOIN users u ON o.kullanici_id = u.id 
                      WHERE $where 
                      ORDER BY o.siparis_tarihi DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$page_title = "Sipariş Yönetimi";
include 'header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-shopping-cart"></i> Sipariş Yönetimi</h1>
</div>

<div class="filter-box">
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="orders.php" class="btn <?php echo !$durum_filter ? 'btn-primary' : 'btn-outline'; ?>">Tümü</a>
        <a href="?durum=beklemede" class="btn <?php echo $durum_filter == 'beklemede' ? 'btn-primary' : 'btn-outline'; ?>">Beklemede</a>
        <a href="?durum=onaylandi" class="btn <?php echo $durum_filter == 'onaylandi' ? 'btn-primary' : 'btn-outline'; ?>">Onaylandı</a>
        <a href="?durum=hazirlaniyor" class="btn <?php echo $durum_filter == 'hazirlaniyor' ? 'btn-primary' : 'btn-outline'; ?>">Hazırlanıyor</a>
        <a href="?durum=kargoda" class="btn <?php echo $durum_filter == 'kargoda' ? 'btn-primary' : 'btn-outline'; ?>">Kargoda</a>
        <a href="?durum=teslim_edildi" class="btn <?php echo $durum_filter == 'teslim_edildi' ? 'btn-primary' : 'btn-outline'; ?>">Teslim Edildi</a>
        <a href="?durum=iptal" class="btn <?php echo $durum_filter == 'iptal' ? 'btn-primary' : 'btn-outline'; ?>">İptal</a>
    </div>
</div>

<div class="content-box">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Sipariş No</th>
                    <th>Müşteri</th>
                    <th>Tutar</th>
                    <th>Ödeme</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;">Sipariş bulunamadı.</td></tr>
                <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><strong><?php echo $order['sipariş_no']; ?></strong></td>
                    <td>
                        <?php echo htmlspecialchars($order['ad'] . ' ' . $order['soyad']); ?><br>
                        <small><?php echo htmlspecialchars($order['email']); ?></small>
                    </td>
                    <td><?php echo formatPrice($order['genel_toplam']); ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $order['odeme_yontemi'])); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="durum" onchange="this.form.submit()" class="status-select status-<?php echo $order['durum']; ?>">
                                <option value="beklemede" <?php echo $order['durum'] == 'beklemede' ? 'selected' : ''; ?>>Beklemede</option>
                                <option value="onaylandi" <?php echo $order['durum'] == 'onaylandi' ? 'selected' : ''; ?>>Onaylandı</option>
                                <option value="hazirlaniyor" <?php echo $order['durum'] == 'hazirlaniyor' ? 'selected' : ''; ?>>Hazırlanıyor</option>
                                <option value="kargoda" <?php echo $order['durum'] == 'kargoda' ? 'selected' : ''; ?>>Kargoda</option>
                                <option value="teslim_edildi" <?php echo $order['durum'] == 'teslim_edildi' ? 'selected' : ''; ?>>Teslim Edildi</option>
                                <option value="iptal" <?php echo $order['durum'] == 'iptal' ? 'selected' : ''; ?>>İptal</option>
                            </select>
                            <button type="submit" name="update_status" hidden></button>
                        </form>
                    </td>
                    <td><?php echo formatDate($order['siparis_tarihi']); ?></td>
                    <td>
                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn-icon" title="Detay"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.status-select { padding: 5px 10px; border-radius: 5px; border: none; font-size: 12px; font-weight: 600; cursor: pointer; }
.status-select.status-beklemede { background: #fef3c7; color: #92400e; }
.status-select.status-onaylandi { background: #dbeafe; color: #1e40af; }
.status-select.status-hazirlaniyor { background: #fde68a; color: #78350f; }
.status-select.status-kargoda { background: #e0e7ff; color: #3730a3; }
.status-select.status-teslim_edildi { background: #d1fae5; color: #065f46; }
.status-select.status-iptal { background: #fee2e2; color: #991b1b; }
</style>

<?php include 'footer.php'; ?>
