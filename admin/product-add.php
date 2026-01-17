<?php
require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$db = Database::getInstance()->getConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $urun_adi = cleanInput($_POST['urun_adi']);
    $urun_kodu = cleanInput($_POST['urun_kodu']);
    $kategori_id = (int)$_POST['kategori_id'];
    $fiyat = (float)$_POST['fiyat'];
    $indirimli_fiyat = $_POST['indirimli_fiyat'] ? (float)$_POST['indirimli_fiyat'] : null;
    $stok_miktari = (int)$_POST['stok_miktari'];
    $kritik_stok_seviyesi = (int)$_POST['kritik_stok_seviyesi'];
    $kisa_aciklama = cleanInput($_POST['kisa_aciklama']);
    $detayli_aciklama = $_POST['detayli_aciklama'];
    $slug = cleanInput($_POST['slug']);
    $durum = cleanInput($_POST['durum']);
    $vitrin = isset($_POST['vitrin']) ? 1 : 0;
    $kampanyali = isset($_POST['kampanyali']) ? 1 : 0;
    $yeni = isset($_POST['yeni']) ? 1 : 0;
    
    // Resim upload
    $resim_adi = 'no-image.jpg';
    if (isset($_FILES['resim']) && $_FILES['resim']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_name = $_FILES['resim']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            $new_name = uniqid() . '_' . time() . '.' . $file_ext;
            $upload_path = '../images/products/' . $new_name;
            
            if (move_uploaded_file($_FILES['resim']['tmp_name'], $upload_path)) {
                $resim_adi = $new_name;
            } else {
                $errors[] = "Resim yüklenemedi.";
            }
        } else {
            $errors[] = "Sadece JPG, PNG, GIF, WEBP formatları yüklenebilir.";
        }
    }
    
    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO products (urun_adi, urun_kodu, kategori_id, fiyat, indirimli_fiyat, stok_miktari, kritik_stok_seviyesi, kisa_aciklama, detayli_aciklama, resim, slug, durum, vitrin, kampanyali, yeni) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$urun_adi, $urun_kodu, $kategori_id, $fiyat, $indirimli_fiyat, $stok_miktari, $kritik_stok_seviyesi, $kisa_aciklama, $detayli_aciklama, $resim_adi, $slug, $durum, $vitrin, $kampanyali, $yeni])) {
            showAlert('Ürün başarıyla eklendi!', 'success');
            redirect('products.php');
        } else {
            $errors[] = "Veritabanı hatası!";
        }
    }
}

// Kategorileri al
$categories = $db->query("SELECT * FROM categories WHERE aktif = 1 ORDER BY kategori_adi")->fetchAll();

$page_title = "Yeni Ürün Ekle";
include 'header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-plus"></i> Yeni Ürün Ekle</h1>
    <a href="products.php" class="btn btn-outline">Geri Dön</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <ul style="margin: 0; padding-left: 20px;">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="content-box">
    <form method="POST" enctype="multipart/form-data" class="admin-form">
        <div class="form-grid">
            <!-- Sol Kolon -->
            <div>
                <div class="form-group">
                    <label>Ürün Adı *</label>
                    <input type="text" name="urun_adi" value="<?php echo $_POST['urun_adi'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Ürün Kodu *</label>
                    <input type="text" name="urun_kodu" value="<?php echo $_POST['urun_kodu'] ?? ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Slug (URL) *</label>
                    <input type="text" name="slug" value="<?php echo $_POST['slug'] ?? ''; ?>" placeholder="urun-adi-seo-uyumlu" required>
                    <small>Boşluk yerine tire (-) kullanın</small>
                </div>

                <div class="form-group">
                    <label>Kategori *</label>
                    <select name="kategori_id" required>
                        <option value="">Seçiniz</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_POST['kategori_id']) && $_POST['kategori_id'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['kategori_adi']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Fiyat (TL) *</label>
                        <input type="number" name="fiyat" step="0.01" min="0" value="<?php echo $_POST['fiyat'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>İndirimli Fiyat (TL)</label>
                        <input type="number" name="indirimli_fiyat" step="0.01" min="0" value="<?php echo $_POST['indirimli_fiyat'] ?? ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Stok Miktarı *</label>
                        <input type="number" name="stok_miktari" min="0" value="<?php echo $_POST['stok_miktari'] ?? '0'; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Kritik Stok Seviyesi</label>
                        <input type="number" name="kritik_stok_seviyesi" min="0" value="<?php echo $_POST['kritik_stok_seviyesi'] ?? '5'; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Kısa Açıklama</label>
                    <textarea name="kisa_aciklama" rows="3" maxlength="200"><?php echo $_POST['kisa_aciklama'] ?? ''; ?></textarea>
                    <small>Maksimum 200 karakter</small>
                </div>
            </div>

            <!-- Sağ Kolon -->
            <div>
                <div class="form-group">
                    <label>Ürün Resmi *</label>
                    <div class="image-upload-box">
                        <input type="file" name="resim" id="resim" accept="image/*" onchange="previewImage(this)">
                        <div class="image-preview" id="imagePreview">
                            <i class="fas fa-image"></i>
                            <p>Resim Seçin (JPG, PNG, GIF)</p>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Durum *</label>
                    <select name="durum" required>
                        <option value="aktif" selected>Aktif</option>
                        <option value="pasif">Pasif</option>
                        <option value="tukendi">Tükendi</option>
                        <option value="taslak">Taslak</option>
                    </select>
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 10px;">Özellikler</label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="vitrin" value="1">
                        <span>Vitrinde Göster</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="kampanyali" value="1">
                        <span>Kampanyalı Ürün</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="yeni" value="1" checked>
                        <span>Yeni Ürün</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Detaylı Açıklama (Full Width) -->
        <div class="form-group">
            <label>Detaylı Açıklama</label>
            <textarea name="detayli_aciklama" rows="10"><?php echo $_POST['detayli_aciklama'] ?? ''; ?></textarea>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 30px;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Ürünü Kaydet
            </button>
            <a href="products.php" class="btn btn-outline">İptal</a>
        </div>
    </form>
</div>

<style>
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.image-upload-box {
    position: relative;
    border: 2px dashed var(--border-color);
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.image-upload-box:hover {
    border-color: var(--primary-color);
    background: var(--light-color);
}

.image-upload-box input[type="file"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.image-preview {
    min-height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.image-preview i {
    font-size: 48px;
    color: var(--text-light);
}

.image-preview img {
    max-width: 100%;
    max-height: 300px;
    border-radius: 8px;
}

@media (max-width: 992px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
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
