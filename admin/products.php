<?php
require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$db = Database::getInstance()->getConnection();

// Silme işlemi
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$id])) showAlert('Ürün silindi.', 'success');
    redirect('products.php');
}

// Filtreleme
$arama = isset($_GET['arama']) ? cleanInput($_GET['arama']) : '';
$kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;

$where = ["1=1"];
$params = [];

if ($arama) {
    $where[] = "(urun_adi LIKE ? OR urun_kodu LIKE ?)";
    $params[] = "%$arama%";
    $params[] = "%$arama%";
}

if ($kategori > 0) {
    $where[] = "kategori_id = ?";
    $params[] = $kategori;
}

$where_sql = implode(" AND ", $where);

$stmt = $db->prepare("SELECT p.*, c.kategori_adi FROM products p 
                      LEFT JOIN categories c ON p.kategori_id = c.id 
                      WHERE $where_sql ORDER BY p.id DESC");
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $db->query("SELECT * FROM categories WHERE aktif = 1 ORDER BY kategori_adi")->fetchAll();

$page_title = "Ürün Yönetimi";
include 'header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-box"></i> Ürün Yönetimi</h1>
    <a href="product-add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Yeni Ürün</a>
</div>

<div class="filter-box">
    <form method="GET" class="filter-form">
        <input type="text" name="arama" placeholder="Ürün adı veya kodu..." value="<?php echo htmlspecialchars($arama); ?>">
        <select name="kategori">
            <option value="0">Tüm Kategoriler</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php echo $kategori == $cat['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['kategori_adi']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filtrele</button>
        <a href="products.php" class="btn btn-outline">Temizle</a>
    </form>
</div>

<div class="content-box">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th width="80">Resim</th>
                    <th>Ürün Adı</th>
                    <th>Kategori</th>
                    <th>Fiyat</th>
                    <th>İndirimli</th>
                    <th>Stok</th>
                    <th>Durum</th>
                    <th width="150">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                <tr><td colspan="9" style="text-align: center; padding: 40px;">Henüz ürün yok.</td></tr>
                <?php else: ?>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td><?php echo $p['id']; ?></td>
                    <td><img src="../images/products/<?php echo $p['resim']; ?>" style="width:60px;height:60px;object-fit:cover;border-radius:5px;" onerror="this.src='../images/no-image.jpg'"></td>
                    <td><strong><?php echo htmlspecialchars($p['urun_adi']); ?></strong><br><small>Kod: <?php echo $p['urun_kodu']; ?></small></td>
                    <td><?php echo htmlspecialchars($p['kategori_adi']); ?></td>
                    <td><?php echo formatPrice($p['fiyat']); ?></td>
                    <td><?php echo $p['indirimli_fiyat'] ? formatPrice($p['indirimli_fiyat']) : '-'; ?></td>
                    <td><span class="badge badge-<?php echo $p['stok_miktari'] > 0 ? 'success' : 'danger'; ?>"><?php echo $p['stok_miktari']; ?></span></td>
                    <td><span class="badge badge-<?php echo $p['durum'] == 'aktif' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($p['durum']); ?></span></td>
                    <td>
                        <a href="product-edit.php?id=<?php echo $p['id']; ?>" class="btn-icon" title="Düzenle"><i class="fas fa-edit"></i></a>
                        <a href="?delete=<?php echo $p['id']; ?>" class="btn-icon danger" title="Sil" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
