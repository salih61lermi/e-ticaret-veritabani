<?php
require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$db = Database::getInstance()->getConnection();

// Silme
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt->execute([$id])) showAlert('Kategori silindi.', 'success');
    redirect('categories.php');
}

// Ekleme/Güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kategori_adi = cleanInput($_POST['kategori_adi']);
    $slug = cleanInput($_POST['slug']);
    $aciklama = cleanInput($_POST['aciklama']);
    $ust_kategori_id = $_POST['ust_kategori_id'] ? (int)$_POST['ust_kategori_id'] : null;
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    $sira = (int)$_POST['sira'];
    
    if (isset($_POST['id']) && $_POST['id']) {
        // Güncelleme
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("UPDATE categories SET kategori_adi = ?, slug = ?, aciklama = ?, ust_kategori_id = ?, aktif = ?, sira = ? WHERE id = ?");
        $stmt->execute([$kategori_adi, $slug, $aciklama, $ust_kategori_id, $aktif, $sira, $id]);
        showAlert('Kategori güncellendi.', 'success');
    } else {
        // Ekleme
        $stmt = $db->prepare("INSERT INTO categories (kategori_adi, slug, aciklama, ust_kategori_id, aktif, sira) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kategori_adi, $slug, $aciklama, $ust_kategori_id, $aktif, $sira]);
        showAlert('Kategori eklendi.', 'success');
    }
    redirect('categories.php');
}

// Düzenleme için kategori getir
$edit_cat = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $edit_cat = $stmt->fetch();
}

// Tüm kategoriler
$categories = $db->query("SELECT c.*, p.kategori_adi as ust_kategori 
                          FROM categories c 
                          LEFT JOIN categories p ON c.ust_kategori_id = p.id 
                          ORDER BY c.sira, c.kategori_adi")->fetchAll();

$parent_categories = $db->query("SELECT * FROM categories WHERE ust_kategori_id IS NULL ORDER BY kategori_adi")->fetchAll();

$page_title = "Kategori Yönetimi";
include 'header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-folder"></i> Kategori Yönetimi</h1>
</div>

<div class="content-grid">
    <!-- Form -->
    <div class="content-box">
        <h2><?php echo $edit_cat ? 'Kategori Düzenle' : 'Yeni Kategori Ekle'; ?></h2>
        <form method="POST" class="admin-form">
            <?php if ($edit_cat): ?>
            <input type="hidden" name="id" value="<?php echo $edit_cat['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Kategori Adı *</label>
                <input type="text" name="kategori_adi" value="<?php echo $edit_cat ? htmlspecialchars($edit_cat['kategori_adi']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Slug (URL) *</label>
                <input type="text" name="slug" value="<?php echo $edit_cat ? htmlspecialchars($edit_cat['slug']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Açıklama</label>
                <textarea name="aciklama" rows="3"><?php echo $edit_cat ? htmlspecialchars($edit_cat['aciklama']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label>Üst Kategori</label>
                <select name="ust_kategori_id">
                    <option value="">Ana Kategori</option>
                    <?php foreach ($parent_categories as $pcat): ?>
                    <option value="<?php echo $pcat['id']; ?>" <?php echo ($edit_cat && $edit_cat['ust_kategori_id'] == $pcat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($pcat['kategori_adi']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Sıra</label>
                <input type="number" name="sira" value="<?php echo $edit_cat ? $edit_cat['sira'] : 0; ?>" min="0">
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="aktif" <?php echo (!$edit_cat || $edit_cat['aktif']) ? 'checked' : ''; ?>>
                    <span>Aktif</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary">
                <?php echo $edit_cat ? 'Güncelle' : 'Ekle'; ?>
            </button>
            <?php if ($edit_cat): ?>
            <a href="categories.php" class="btn btn-outline">İptal</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Liste -->
    <div class="content-box">
        <h2>Kategoriler</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kategori Adı</th>
                        <th>Üst Kategori</th>
                        <th>Sıra</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?php echo $cat['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($cat['kategori_adi']); ?></strong></td>
                        <td><?php echo $cat['ust_kategori'] ? htmlspecialchars($cat['ust_kategori']) : '-'; ?></td>
                        <td><?php echo $cat['sira']; ?></td>
                        <td><span class="badge badge-<?php echo $cat['aktif'] ? 'success' : 'secondary'; ?>"><?php echo $cat['aktif'] ? 'Aktif' : 'Pasif'; ?></span></td>
                        <td>
                            <a href="?edit=<?php echo $cat['id']; ?>" class="btn-icon" title="Düzenle"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?php echo $cat['id']; ?>" class="btn-icon danger" title="Sil" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
