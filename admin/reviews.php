<?php
require_once '../config.php';
if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$db = Database::getInstance()->getConnection();

// Yorum onaylama/reddetme
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['review_id'];
    $durum = cleanInput($_POST['durum']);
    $stmt = $db->prepare("UPDATE reviews SET onay_durumu = ?, moderator_id = ? WHERE id = ?");
    if ($stmt->execute([$durum, $_SESSION['kullanici_id'], $id])) {
        showAlert('Yorum durumu güncellendi.', 'success');
    }
    redirect('reviews.php');
}

// Yorum silme
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
    if ($stmt->execute([$id])) showAlert('Yorum silindi.', 'success');
    redirect('reviews.php');
}

// Filtreleme
$durum_filter = isset($_GET['durum']) ? cleanInput($_GET['durum']) : '';
$where = "1=1";
$params = [];

if ($durum_filter) {
    $where .= " AND r.onay_durumu = ?";
    $params[] = $durum_filter;
}

$stmt = $db->prepare("SELECT r.*, p.urun_adi, u.ad, u.soyad 
                      FROM reviews r 
                      JOIN products p ON r.urun_id = p.id 
                      JOIN users u ON r.kullanici_id = u.id 
                      WHERE $where 
                      ORDER BY r.yorum_tarihi DESC");
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$page_title = "Yorum Yönetimi";
include 'header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-comments"></i> Yorum Yönetimi</h1>
</div>

<div class="filter-box">
    <div style="display: flex; gap: 10px;">
        <a href="reviews.php" class="btn <?php echo !$durum_filter ? 'btn-primary' : 'btn-outline'; ?>">Tümü</a>
        <a href="?durum=beklemede" class="btn <?php echo $durum_filter == 'beklemede' ? 'btn-primary' : 'btn-outline'; ?>">Beklemede</a>
        <a href="?durum=onaylandi" class="btn <?php echo $durum_filter == 'onaylandi' ? 'btn-primary' : 'btn-outline'; ?>">Onaylandı</a>
        <a href="?durum=reddedildi" class="btn <?php echo $durum_filter == 'reddedildi' ? 'btn-primary' : 'btn-outline'; ?>">Reddedildi</a>
    </div>
</div>

<div class="content-box">
    <div class="reviews-list">
        <?php if (empty($reviews)): ?>
        <div style="text-align:center;padding:40px;color:#999;">Yorum bulunamadı.</div>
        <?php else: ?>
        <?php foreach ($reviews as $review): ?>
        <div class="review-card">
            <div class="review-header">
                <div class="review-user">
                    <strong><?php echo htmlspecialchars($review['ad'] . ' ' . $review['soyad']); ?></strong>
                    <span class="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= $review['puan'] ? 'active' : ''; ?>"></i>
                        <?php endfor; ?>
                    </span>
                </div>
                <span class="badge badge-<?php 
                    echo $review['onay_durumu'] == 'onaylandi' ? 'success' : 
                         ($review['onay_durumu'] == 'reddedildi' ? 'danger' : 'warning'); 
                ?>">
                    <?php echo ucfirst($review['onay_durumu']); ?>
                </span>
            </div>
            <div class="review-product">
                <strong>Ürün:</strong> <?php echo htmlspecialchars($review['urun_adi']); ?>
            </div>
            <?php if ($review['baslik']): ?>
            <h4><?php echo htmlspecialchars($review['baslik']); ?></h4>
            <?php endif; ?>
            <p><?php echo nl2br(htmlspecialchars($review['yorum'])); ?></p>
            <div class="review-footer">
                <small><?php echo formatDate($review['yorum_tarihi']); ?></small>
                <div class="review-actions">
                    <?php if ($review['onay_durumu'] != 'onaylandi'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <input type="hidden" name="durum" value="onaylandi">
                        <button type="submit" name="update_status" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Onayla</button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($review['onay_durumu'] != 'reddedildi'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                        <input type="hidden" name="durum" value="reddedildi">
                        <button type="submit" name="update_status" class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Reddet</button>
                    </form>
                    <?php endif; ?>
                    
                    <a href="?delete=<?php echo $review['id']; ?>" class="btn btn-sm btn-outline" onclick="return confirm('Yorumu silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i> Sil</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.reviews-list { display: flex; flex-direction: column; gap: 20px; }
.review-card { background: #fff; border: 2px solid #e5e7eb; border-radius: 8px; padding: 20px; }
.review-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px; }
.review-user { display: flex; align-items: center; gap: 15px; }
.rating { display: flex; gap: 2px; }
.rating .fa-star { color: #ddd; font-size: 14px; }
.rating .fa-star.active { color: #fbbf24; }
.review-product { margin-bottom: 10px; color: #666; font-size: 14px; }
.review-card h4 { margin-bottom: 10px; color: #374151; }
.review-card p { color: #6b7280; line-height: 1.6; }
.review-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb; }
.review-actions { display: flex; gap: 10px; }
</style>

<?php include 'footer.php'; ?>
