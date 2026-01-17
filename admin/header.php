<?php
if (!isset($page_title)) {
    $page_title = "Admin Panel";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Panel</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shopping-bag"></i> E-TİCARET</h2>
                <p>Admin Panel</p>
            </div>

            <nav class="sidebar-nav">
                <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="products.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Ürünler
                </a>
                <a href="categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-folder"></i> Kategoriler
                </a>
                <a href="orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Siparişler
                </a>
                <a href="users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Kullanıcılar
                </a>
                <a href="reviews.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i> Yorumlar
                </a>
                <div class="nav-divider"></div>
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-globe"></i> Siteye Git
                </a>
                <a href="../logout.php" class="nav-link" style="color: var(--danger-color);">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="sidebar-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?php echo htmlspecialchars($page_title); ?></h1>
                </div>
                <div class="top-bar-right">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['ad'] . ' ' . $_SESSION['soyad']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php 
            $alert = getAlert();
            if ($alert): 
            ?>
            <div class="alert alert-<?php echo $alert['type']; ?>" id="alertBox">
                <span><?php echo htmlspecialchars($alert['message']); ?></span>
                <button onclick="closeAlert()" class="alert-close">&times;</button>
            </div>
            <?php endif; ?>

            <!-- Page Content -->
            <div class="page-content">
