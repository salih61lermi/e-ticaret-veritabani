<?php
// HATA AYIKLAMA - Üretimde kapat!
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$db = Database::getInstance()->getConnection();
$errors = [];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ürünü getir
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    showAlert('Ürün bulunamadı!', 'error');
    redirect('products.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $urun_adi = cleanInput($_POST['urun_adi']);
        $urun_kodu = cleanInput($_POST['urun_kodu']);
        $kategori_id = (int)$_POST['kategori_id'];
        $fiyat = (float)str_replace(',', '.', $_POST['fiyat']);
        $indirimli_fiyat = !empty($_POST['indirimli_fiyat']) ? (float)str_replace(',', '.', $_POST['indirimli_fiyat']) : null;
        $stok_miktari = (int)$_POST['stok_miktari'];
        $kritik_stok_seviyesi = (int)$_POST['kritik_stok_seviyesi'];
        $kisa_aciklama = isset($_POST['kisa_aciklama']) ? cleanInput($_POST['kisa_aciklama']) : '';
        $detayli_aciklama = isset($_POST['detayli_aciklama']) ? trim($_POST['detayli_aciklama']) : '';
        $slug = cleanInput($_POST['slug']);
        $durum = cleanInput($_POST['durum']);
        $vitrin = isset($_POST['vitrin']) ? 1 : 0;
        $kampanyali = isset($_POST['kampanyali']) ? 1 : 0;
        $yeni = isset($_POST['yeni']) ? 1 : 0;
        
        // Resim upload (varsa)
        $resim_adi = $product['resim'];
        if (isset($_FILES['resim']) && $_FILES['resim']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $file_ext = strtolower(pathinfo($_FILES['resim']['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_ext, $allowed)) {
                $new_name = uniqid() . '_' . time() . '.' . $file_ext;
                $upload_path = '../images/products/' . $new_name;
                
                if (move_uploaded_file($_FILES['resim']['tmp_name'], $upload_path)) {
                    // Eski resmi sil (no-image değilse)
                    if ($product['resim'] != 'no-image.jpg' && file_exists('../images/products/' . $product['resim'])) {
                        @unlink('../images/products/' . $product['resim']);
                    }
                    $resim_adi = $new_name;
                }
            }
        }
        
        // UPDATE sorgusu
        $sql = "UPDATE products SET 
                urun_adi = ?, 
                urun_kodu = ?, 
                kategori_id = ?, 
                fiyat = ?, 
                indirimli_fiyat = ?, 
                stok_miktari = ?, 
                kritik_stok_seviyesi = ?, 
                kisa_aciklama = ?, 
                detayli_aciklama = ?, 
                resim = ?, 
                slug = ?, 
                durum = ?, 
                vitrin = ?, 
                kampanyali = ?, 
                yeni = ? 
                WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute([
            $urun_adi, 
            $urun_kodu, 
            $kategori_id, 
            $fiyat, 
            $indirimli_fiyat, 
            $stok_miktari, 
            $kritik_stok_seviyesi, 
            $kisa_aciklama, 
            $detayli_aciklama, 
            $resim_adi, 
            $slug, 
            $durum, 
            $vitrin, 
            $kampanyali, 
            $yeni, 
            $id
        ])) {
            showAlert('Ürün başarıyla güncellendi!', 'success');
            redirect('products.php');
        } else {
            $errors[] = "Veritabanı hatası: " . print_r($stmt->errorInfo(), true);
        }
        
    } catch (Exception $e) {
        $errors[] = "Hata: " . $e->getMessage();
    }
}

$categories = $db->query("SELECT * FROM categories WHERE aktif = 1 ORDER BY kategori_adi")->fetchAll();

$page_title = "Ürün Düzenle";
include 'header.php';
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <strong>Hatalar:</strong>
    <ul style="margin: 10px 0 0 20px;">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="page-header">
    <h1><i class="fas fa-edit"></i> Ürün Düzenle</h1>
    <a href="products.php" class="btn btn-outline">Geri Dön</a>
</div>

<div class="content-box">
    <form method="POST" enctype="multipart/form-data" class="admin-form">
        <div class="form-grid">
            <div>
                <div class="form-group">
                    <label>Ürün Adı *</label>
                    <input type="text" name="urun_adi" value="<?php echo htmlspecialchars($product['urun_adi']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Ürün Kodu *</label>
                    <input type="text" name="urun_kodu" value="<?php echo htmlspecialchars($product['urun_kodu']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Slug (URL) *</label>
                    <input type="text" name="slug" value="<?php echo htmlspecialchars($product['slug']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Kategori *</label>
                    <select name="kategori_id" required>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $product['kategori_id'] == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['kategori_adi']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Fiyat (TL) *</label>
                        <input type="number" name="fiyat" step="0.01" min="0" value="<?php echo $product['fiyat']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>İndirimli Fiyat (TL)</label>
                        <input type="number" name="indirimli_fiyat" step="0.01" min="0" value="<?php echo $product['indirimli_fiyat']; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Stok Miktarı *</label>
                        <input type="number" name="stok_miktari" min="0" value="<?php echo $product['stok_miktari']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Kritik Stok Seviyesi</label>
                        <input type="number" name="kritik_stok_seviyesi" min="0" value="<?php echo $product['kritik_stok_seviyesi']; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Kısa Açıklama</label>
                    <textarea name="kisa_aciklama" rows="3" maxlength="200"><?php echo htmlspecialchars($product['kisa_aciklama']); ?></textarea>
                </div>
            </div>

            <div>
                <div class="form-group">
                    <label>Ürün Resmi</label>
                    <div class="image-upload-box">
                        <input type="file" name="resim" id="resim" accept="image/*" onchange="previewImage(this)">
                        <div class="image-preview" id="imagePreview">
                            <img src="../images/products/<?php echo $product['resim']; ?>" alt="Mevcut Resim" onerror="this.src='../images/no-image.jpg'">
                        </div>
                    </div>
                    <small>Yeni resim yüklerseniz eskisi silinir</small>
                </div>

                <div class="form-group">
                    <label>Durum *</label>
                    <select name="durum" required>
                        <option value="aktif" <?php echo $product['durum'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="pasif" <?php echo $product['durum'] == 'pasif' ? 'selected' : ''; ?>>Pasif</option>
                        <option value="tukendi" <?php echo $product['durum'] == 'tukendi' ? 'selected' : ''; ?>>Tükendi</option>
                        <option value="taslak" <?php echo $product['durum'] == 'taslak' ? 'selected' : ''; ?>>Taslak</option>
                    </select>
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 10px;">Özellikler</label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="vitrin" value="1" <?php echo $product['vitrin'] ? 'checked' : ''; ?>>
                        <span>Vitrinde Göster</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="kampanyali" value="1" <?php echo $product['kampanyali'] ? 'checked' : ''; ?>>
                        <span>Kampanyalı Ürün</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="yeni" value="1" <?php echo $product['yeni'] ? 'checked' : ''; ?>>
                        <span>Yeni Ürün</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Detaylı Açıklama</label>
            <textarea name="detayli_aciklama" rows="10"><?php echo htmlspecialchars($product['detayli_aciklama']); ?></textarea>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 30px;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Değişiklikleri Kaydet
            </button>
            <a href="products.php" class="btn btn-outline">İptal</a>
        </div>
    </form>
</div>

<style>
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.image-upload-box { position: relative; border: 2px dashed var(--border-color); border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; }
.image-upload-box input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
.image-preview { min-height: 200px; display: flex; align-items: center; justify-content: center; }
.image-preview img { max-width: 100%; max-height: 300px; border-radius: 8px; }
@media (max-width: 992px) { .form-grid { grid-template-columns: 1fr; } }
</style>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'footer.php'; ?>