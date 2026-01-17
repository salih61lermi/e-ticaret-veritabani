<?php
require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$db = Database::getInstance()->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Kullanıcıyı getir
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    showAlert('Kullanıcı bulunamadı!', 'error');
    redirect('users.php');
}

// Güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad = cleanInput($_POST['ad']);
    $soyad = cleanInput($_POST['soyad']);
    $email = cleanInput($_POST['email']);
    $telefon = cleanInput($_POST['telefon']);
    $rol = cleanInput($_POST['rol']);
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    $yeni_sifre = $_POST['yeni_sifre'];
    
    // E-posta kontrolü (başkası kullanıyor mu?)
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        showAlert('Bu e-posta başka bir kullanıcı tarafından kullanılıyor!', 'error');
    } else {
        // Şifre güncelleme varsa
        if (!empty($yeni_sifre)) {
            $stmt = $db->prepare("UPDATE users SET ad = ?, soyad = ?, email = ?, telefon = ?, rol = ?, aktif = ?, sifre = ? WHERE id = ?");
            $result = $stmt->execute([$ad, $soyad, $email, $telefon, $rol, $aktif, $yeni_sifre, $id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET ad = ?, soyad = ?, email = ?, telefon = ?, rol = ?, aktif = ? WHERE id = ?");
            $result = $stmt->execute([$ad, $soyad, $email, $telefon, $rol, $aktif, $id]);
        }
        
        if ($result) {
            showAlert('Kullanıcı güncellendi!', 'success');
            redirect('users.php');
        }
    }
}

$page_title = "Kullanıcı Düzenle";
include 'header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-user-edit"></i> Kullanıcı Düzenle</h1>
    <a href="users.php" class="btn btn-outline">Geri Dön</a>
</div>

<div class="content-box" style="max-width: 800px; margin: 0 auto;">
    <form method="POST" class="admin-form">
        <div class="form-row">
            <div class="form-group">
                <label>Ad *</label>
                <input type="text" name="ad" value="<?php echo htmlspecialchars($user['ad']); ?>" required>
            </div>

            <div class="form-group">
                <label>Soyad *</label>
                <input type="text" name="soyad" value="<?php echo htmlspecialchars($user['soyad']); ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>E-posta *</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label>Telefon</label>
                <input type="tel" name="telefon" value="<?php echo htmlspecialchars($user['telefon']); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Rol *</label>
                <select name="rol" required>
                    <option value="musteri" <?php echo $user['rol'] == 'musteri' ? 'selected' : ''; ?>>Müşteri</option>
                    <option value="admin" <?php echo $user['rol'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label>Yeni Şifre</label>
                <input type="password" name="yeni_sifre" placeholder="Boş bırakırsanız değişmez">
                <small>En az 6 karakter</small>
            </div>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="aktif" value="1" <?php echo $user['aktif'] ? 'checked' : ''; ?>>
                <span>Aktif</span>
            </label>
        </div>

        <div class="form-group">
            <p style="color: var(--text-light); font-size: 14px;">
                <strong>Kayıt Tarihi:</strong> <?php echo formatDate($user['kayit_tarihi']); ?>
            </p>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 30px;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Değişiklikleri Kaydet
            </button>
            <a href="users.php" class="btn btn-outline">İptal</a>
        </div>
    </form>
</div>

<style>
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
</style>

<?php include 'footer.php'; ?>
