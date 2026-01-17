<?php
if (!isset($page_title)) {
    $page_title = "E-Ticaret Sitesi";
}

// Sepet sayısını al
$cart_count = 0;
if (isLoggedIn()) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT SUM(adet) as toplam FROM cart WHERE kullanici_id = ?");
    $stmt->execute([$_SESSION['kullanici_id']]);
    $result = $stmt->fetch();
    $cart_count = $result['toplam'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - E-Ticaret</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-content">
                <div class="top-bar-left">
                    <i class="fas fa-phone"></i> 0850 000 00 00
                    <i class="fas fa-envelope"></i> destek@eticaret.com
                </div>
                <div class="top-bar-right">
                    <?php if(isLoggedIn()): ?>
                        <span><i class="fas fa-user"></i> Hoş geldin, <?php echo htmlspecialchars($_SESSION['ad']); ?></span>
                        <a href="account.php"><i class="fas fa-cog"></i> Hesabım</a>
                        <?php if(isAdmin()): ?>
                        <a href="admin/"><i class="fas fa-user-shield"></i> Admin Panel</a>
                        <?php endif; ?>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
                    <?php else: ?>
                        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Giriş Yap</a>
                        <a href="register.php"><i class="fas fa-user-plus"></i> Kayıt Ol</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php">
                        <i class="fas fa-shopping-bag"></i>
                        <span>LRM-Store</span>
                    </a>
                </div>

                <div class="search-bar">
                    <form action="products.php" method="GET">
                        <input type="text" name="arama" placeholder="Ürün, kategori veya marka ara..." value="<?php echo isset($_GET['arama']) ? htmlspecialchars($_GET['arama']) : ''; ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>

                <div class="header-actions">
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                        <span>Sepetim</span>
                    </a>
                </div>

                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="main-nav">
        <div class="container">
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php"><i class="fas fa-home"></i> Ana Sayfa</a></li>
                <li class="dropdown">
                    <a href="products.php"><i class="fas fa-box"></i> Tüm Ürünler</a>
                    <ul class="dropdown-menu">
                        <?php
                        $db = Database::getInstance()->getConnection();
                        $stmt = $db->query("SELECT * FROM categories WHERE aktif = 1 ORDER BY sira");
                        $nav_kategoriler = $stmt->fetchAll();
                        foreach($nav_kategoriler as $kat):
                        ?>
                        <li><a href="products.php?kategori=<?php echo $kat['id']; ?>"><?php echo htmlspecialchars($kat['kategori_adi']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li><a href="products.php?indirim=1"><i class="fas fa-tag"></i> Kampanyalar</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> İletişim</a></li>
            </ul>
        </div>
    </nav>

    <!-- Alert Messages -->
    <?php 
    $alert = getAlert();
    if ($alert): 
    ?>
    <div class="alert alert-<?php echo $alert['type']; ?>" id="alertBox">
        <div class="container">
            <span><?php echo htmlspecialchars($alert['message']); ?></span>
            <button onclick="closeAlert()" class="alert-close">&times;</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main-content">
