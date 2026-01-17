<?php
require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$db = Database::getInstance()->getConnection();

// Kullanıcı silme
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id != $_SESSION['kullanici_id']) { // Kendini silemesin
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$id])) showAlert('Kullanıcı silindi.', 'success');
    }
    redirect('users.php');
}

// Filtreleme
$rol_filter = isset($_GET['rol']) ? cleanInput($_GET['rol']) : '';
$where = "1=1";
$params = [];

if ($rol_filter) {
    $where .= " AND rol = ?";
    $params[] = $rol_filter;
}

$stmt = $db->prepare("SELECT * FROM users WHERE $where ORDER BY kayit_tarihi DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = "Kullanıcı Yönetimi";
include 'header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-users"></i> Kullanıcı Yönetimi</h1>
</div>

<div class="filter-box">
    <div style="display: flex; gap: 10px;">
        <a href="users.php" class="btn <?php echo !$rol_filter ? 'btn-primary' : 'btn-outline'; ?>">Tümü</a>
        <a href="?rol=admin" class="btn <?php echo $rol_filter == 'admin' ? 'btn-primary' : 'btn-outline'; ?>">Adminler</a>
        <a href="?rol=musteri" class="btn <?php echo $rol_filter == 'musteri' ? 'btn-primary' : 'btn-outline'; ?>">Müşteriler</a>
    </div>
</div>

<div class="content-box">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Telefon</th>
                    <th>Rol</th>
                    <th>Durum</th>
                    <th>Kayıt Tarihi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($user['ad'] . ' ' . $user['soyad']); ?></strong></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['telefon']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $user['rol'] == 'admin' ? 'primary' : 'secondary'; ?>">
                            <?php echo ucfirst($user['rol']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $user['aktif'] ? 'success' : 'danger'; ?>">
                            <?php echo $user['aktif'] ? 'Aktif' : 'Pasif'; ?>
                        </span>
                    </td>
                    <td><?php echo formatDate($user['kayit_tarihi']); ?></td>
                    <td>
                        <?php if ($user['id'] != $_SESSION['kullanici_id']): ?>
                        <a href="user-edit.php?id=<?php echo $user['id']; ?>" class="btn-icon" title="Düzenle"><i class="fas fa-edit"></i></a>
                        <a href="?delete=<?php echo $user['id']; ?>" class="btn-icon danger" title="Sil" onclick="return confirm('Kullanıcıyı silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></a>
                        <?php else: ?>
                        <span class="badge badge-info">Siz</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
