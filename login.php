<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = cleanInput($_POST['email']);
    $sifre = $_POST['sifre'];

    if (empty($email) || empty($sifre)) {
        $error = "E-posta ve şifre alanları boş bırakılamaz.";
    } else {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['sifre'] === $sifre) {
            $_SESSION['kullanici_id'] = $user['id'];
            $_SESSION['ad'] = $user['ad'];
            $_SESSION['soyad'] = $user['soyad'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['rol'] = $user['rol'];

            showAlert('Giriş başarılı! Hoş geldiniz.', 'success');

            // Redirect parametresi varsa oraya yönlendir
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
            redirect($redirect);
        } else {
            $error = "E-posta veya şifre hatalı.";
        }
    }
}

$page_title = "Giriş Yap";
include 'header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <i class="fas fa-sign-in-alt"></i>
            <h2>Giriş Yap</h2>
            <p>Hesabınıza giriş yapın</p>
        </div>

        <?php if ($error): ?>
        <div class="error-box">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">E-posta</label>
                <input type="email" id="email" name="email" placeholder="ornek@email.com" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="sifre">Şifre</label>
                <input type="password" id="sifre" name="sifre" placeholder="••••••••" required>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="beni_hatirla">
                    <span>Beni Hatırla</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Giriş Yap</button>

            <div class="form-links">
                <a href="#">Şifremi Unuttum</a>
            </div>
        </form>

        <div class="auth-divider">
            <span>veya</span>
        </div>

        <div class="social-login">
            <button class="btn-social btn-google">
                <i class="fab fa-google"></i> Google ile Giriş
            </button>
            <button class="btn-social btn-facebook">
                <i class="fab fa-facebook"></i> Facebook ile Giriş
            </button>
        </div>

        <div class="auth-footer">
            <p>Hesabınız yok mu? <a href="register.php">Kayıt Ol</a></p>
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
    max-width: 450px;
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
    color: var(--danger-color);
    text-align: center;
}

.auth-form {
    margin-bottom: 20px;
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

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 14px;
}

.checkbox-label input {
    width: auto;
    margin: 0;
}

.btn-block {
    width: 100%;
    padding: 15px;
    font-size: 16px;
}

.form-links {
    text-align: center;
    margin-top: 15px;
}

.form-links a {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 14px;
}

.auth-divider {
    text-align: center;
    margin: 25px 0;
    position: relative;
}

.auth-divider::before,
.auth-divider::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 45%;
    height: 1px;
    background-color: var(--border-color);
}

.auth-divider::before {
    left: 0;
}

.auth-divider::after {
    right: 0;
}

.auth-divider span {
    background-color: #fff;
    padding: 0 10px;
    color: var(--text-light);
    font-size: 14px;
}

.social-login {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.btn-social {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    background-color: #fff;
    border-radius: 5px;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-social:hover {
    background-color: var(--light-color);
}

.btn-google {
    color: #db4437;
}

.btn-facebook {
    color: #4267B2;
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
    .auth-box {
        padding: 30px 20px;
    }
}
</style>

<?php include 'footer.php'; ?>