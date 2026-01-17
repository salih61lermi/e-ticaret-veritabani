<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ad = cleanInput($_POST['ad']);
    $soyad = cleanInput($_POST['soyad']);
    $email = cleanInput($_POST['email']);
    $telefon = cleanInput($_POST['telefon']);
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];

    // Validasyon
    if (empty($ad)) $errors[] = "Ad alanı boş bırakılamaz.";
    if (empty($soyad)) $errors[] = "Soyad alanı boş bırakılamaz.";
    if (empty($email)) $errors[] = "E-posta alanı boş bırakılamaz.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Geçerli bir e-posta adresi giriniz.";
    if (empty($sifre)) $errors[] = "Şifre alanı boş bırakılamaz.";
    if (strlen($sifre) < 6) $errors[] = "Şifre en az 6 karakter olmalıdır.";
    if ($sifre !== $sifre_tekrar) $errors[] = "Şifreler eşleşmiyor.";

    if (empty($errors)) {
        $db = Database::getInstance()->getConnection();
        
        // E-posta kontrolü
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $errors[] = "Bu e-posta adresi zaten kayıtlı.";
        } else {
            // Kullanıcıyı kaydet (şifre düz metin olarak)
            $stmt = $db->prepare("INSERT INTO users (ad, soyad, email, telefon, sifre, rol) VALUES (?, ?, ?, ?, ?, 'musteri')");
            
            if ($stmt->execute([$ad, $soyad, $email, $telefon, $sifre])) {
                showAlert('Kayıt başarılı! Giriş yapabilirsiniz.', 'success');
                redirect('login.php');
            } else {
                $errors[] = "Kayıt sırasında bir hata oluştu.";
            }
        }
    }
}

$page_title = "Kayıt Ol";
include 'header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <i class="fas fa-user-plus"></i>
            <h2>Kayıt Ol</h2>
            <p>Alışverişe başlamak için üye olun</p>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="error-box">
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="ad">Ad *</label>
                    <input type="text" id="ad" name="ad" value="<?php echo isset($_POST['ad']) ? htmlspecialchars($_POST['ad']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="soyad">Soyad *</label>
                    <input type="text" id="soyad" name="soyad" value="<?php echo isset($_POST['soyad']) ? htmlspecialchars($_POST['soyad']) : ''; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">E-posta *</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="telefon">Telefon</label>
                <input type="tel" id="telefon" name="telefon" placeholder="05XX XXX XX XX" value="<?php echo isset($_POST['telefon']) ? htmlspecialchars($_POST['telefon']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="sifre">Şifre *</label>
                <input type="password" id="sifre" name="sifre" required>
                <small>En az 6 karakter olmalıdır</small>
            </div>

            <div class="form-group">
                <label for="sifre_tekrar">Şifre Tekrar *</label>
                <input type="password" id="sifre_tekrar" name="sifre_tekrar" required>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" required>
                    <span><a href="#">Kullanıcı Sözleşmesi</a>'ni ve <a href="#">Gizlilik Politikası</a>'nı okudum, kabul ediyorum.</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Kayıt Ol</button>
        </form>

        <div class="auth-footer">
            <p>Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a></p>
        </div>
    </div>
</div>

<style>
.auth-container {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.auth-box {
    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    padding: 40px;
    max-width: 500px;
    width: 100%;
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-header i {
    font-size: 48px;
    color: var(--primary-color);
    margin-bottom: 15px;
}

.auth-header h2 {
    font-size: 28px;
    margin-bottom: 10px;
}

.auth-header p {
    color: var(--text-light);
}

.error-box {
    background-color: #fee;
    border: 1px solid var(--danger-color);
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 20px;
}

.error-box ul {
    margin: 0;
    padding-left: 20px;
    color: var(--danger-color);
}

.auth-form {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--dark-color);
}

.form-group input {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: 5px;
    font-size: 15px;
    transition: border-color 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: var(--primary-color);
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: var(--text-light);
    font-size: 13px;
}

.checkbox-label {
    display: flex;
    align-items: start;
    gap: 10px;
    cursor: pointer;
    font-size: 14px;
}

.checkbox-label input {
    margin-top: 3px;
    width: auto;
}

.checkbox-label a {
    color: var(--primary-color);
    text-decoration: none;
}

.btn-block {
    width: 100%;
    padding: 15px;
    font-size: 16px;
}

.auth-footer {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

.auth-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

@media (max-width: 576px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .auth-box {
        padding: 30px 20px;
    }
}
</style>

<?php include 'footer.php'; ?>