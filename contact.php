<?php
require_once 'config.php';

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad = cleanInput($_POST['ad']);
    $email = cleanInput($_POST['email']);
    $telefon = cleanInput($_POST['telefon']);
    $konu = cleanInput($_POST['konu']);
    $mesaj = cleanInput($_POST['mesaj']);
    
    // Validasyon
    if (empty($ad)) $errors[] = "Ad alanı boş bırakılamaz.";
    if (empty($email)) $errors[] = "E-posta alanı boş bırakılamaz.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Geçerli bir e-posta adresi giriniz.";
    if (empty($konu)) $errors[] = "Konu seçiniz.";
    if (empty($mesaj)) $errors[] = "Mesaj alanı boş bırakılamaz.";
    
    if (empty($errors)) {
        $success = true;
        showAlert('Mesajınız başarıyla gönderildi. En kısa sürede size dönüş yapacağız.', 'success');
    }
}

$page_title = "İletişim";
include 'header.php';
?>

<div class="container" style="padding: 40px 20px;">
    <div class="contact-page">
        <div class="page-header-section">
            <h1><i class="fas fa-envelope"></i> İletişim</h1>
            <p>Bizimle iletişime geçin, size yardımcı olmaktan mutluluk duyarız.</p>
        </div>

        <div class="contact-container">
            <div class="contact-info">
                <h2>İletişim Bilgileri</h2>
                
                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="info-content">
                        <h3>Adres</h3>
                        <p>Atatürk Mah. Çarşı Cad. No:123<br>Kadıköy / İstanbul</p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-phone"></i></div>
                    <div class="info-content">
                        <h3>Telefon</h3>
                        <p>0850 000 00 00<br>+90 (216) 123 45 67</p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-envelope"></i></div>
                    <div class="info-content">
                        <h3>E-posta</h3>
                        <p>destek@eticaret.com<br>info@eticaret.com</p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon"><i class="fas fa-clock"></i></div>
                    <div class="info-content">
                        <h3>Çalışma Saatleri</h3>
                        <p>Pzt-Cum: 09:00-18:00<br>Cmt: 10:00-16:00<br>Pazar: Kapalı</p>
                    </div>
                </div>

                <div class="social-links-contact">
                    <h3>Sosyal Medya</h3>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>

            <div class="contact-form-section">
                <h2>Bize Mesaj Gönderin</h2>
                
                <?php if (!empty($errors)): ?>
                <div class="error-box">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="success-box">
                    <i class="fas fa-check-circle"></i>
                    <p>Mesajınız başarıyla gönderildi!</p>
                </div>
                <?php endif; ?>

                <form method="POST" class="contact-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Adınız Soyadınız *</label>
                            <input type="text" name="ad" value="<?php echo $_POST['ad'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>E-posta *</label>
                            <input type="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Telefon</label>
                            <input type="tel" name="telefon" value="<?php echo $_POST['telefon'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Konu *</label>
                            <select name="konu" required>
                                <option value="">Seçiniz</option>
                                <option value="Sipariş">Sipariş</option>
                                <option value="Ürün">Ürün</option>
                                <option value="İade">İade</option>
                                <option value="Kargo">Kargo</option>
                                <option value="Diğer">Diğer</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mesajınız *</label>
                        <textarea name="mesaj" rows="6" required><?php echo $_POST['mesaj'] ?? ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane"></i> Mesaj Gönder
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.contact-page { max-width: 1400px; margin: 0 auto; }
.page-header-section { text-align: center; margin-bottom: 50px; }
.page-header-section h1 { font-size: 36px; margin-bottom: 15px; }
.contact-container { display: grid; grid-template-columns: 400px 1fr; gap: 40px; }
.contact-info, .contact-form-section { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.info-card { display: flex; gap: 20px; padding: 20px; margin-bottom: 15px; background: var(--light-color); border-radius: 8px; }
.info-icon { width: 50px; height: 50px; background: var(--primary-color); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.social-icons { display: flex; gap: 10px; }
.social-icons a { width: 45px; height: 45px; background: var(--primary-color); color: #fff; display: flex; align-items: center; justify-content: center; border-radius: 50%; text-decoration: none; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 5px; font-family: inherit; }
.error-box { background: #fee; border: 1px solid var(--danger-color); padding: 15px; border-radius: 5px; margin-bottom: 20px; }
.success-box { background: #d1fae5; border: 1px solid var(--success-color); padding: 20px; border-radius: 5px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; }
@media (max-width: 992px) { .contact-container, .form-row { grid-template-columns: 1fr; } }
</style>

<?php include 'footer.php'; ?>