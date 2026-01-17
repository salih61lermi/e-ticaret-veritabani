</main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Kurumsal</h3>
                    <ul>
                        <li><a href="#">Hakkımızda</a></li>
                        <li><a href="#">Kariyer</a></li>
                        <li><a href="#">İletişim</a></li>
                        <li><a href="#">Mağazalarımız</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Müşteri Hizmetleri</h3>
                    <ul>
                        <li><a href="#">Sıkça Sorulan Sorular</a></li>
                        <li><a href="#">Sipariş Takibi</a></li>
                        <li><a href="#">İade ve Değişim</a></li>
                        <li><a href="#">Kargo Bilgileri</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Yasal</h3>
                    <ul>
                        <li><a href="#">Gizlilik Politikası</a></li>
                        <li><a href="#">Kullanım Koşulları</a></li>
                        <li><a href="#">KVKK</a></li>
                        <li><a href="#">Mesafeli Satış Sözleşmesi</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Bizi Takip Edin</h3>
                    <div class="social-links">
                        <a href="#" title="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                    <div class="payment-methods">
                        <h4>Ödeme Yöntemleri</h4>
                        <div class="payment-icons">
                            <i class="fab fa-cc-visa"></i>
                            <i class="fab fa-cc-mastercard"></i>
                            <i class="fab fa-cc-amex"></i>
                            <i class="fas fa-credit-card"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> E-Ticaret. Tüm hakları saklıdır.</p>
                <p>Tasarım ve Yazılım: <a href="#">Web Yazılım</a></p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        function toggleMobileMenu() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.toggle('active');
        }

        function closeAlert() {
            const alertBox = document.getElementById('alertBox');
            if (alertBox) {
                alertBox.style.display = 'none';
            }
        }

        // Alert otomatik kapanma
        setTimeout(() => {
            closeAlert();
        }, 5000);

        // Bildirim gösterme fonksiyonu
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.classList.add('show');
            }, 100);

            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        // Sepete ekleme fonksiyonu
        async function addToCart(productId) {
            <?php if(!isLoggedIn()): ?>
            alert('Sepete ürün eklemek için giriş yapmalısınız!');
            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            return;
            <?php endif; ?>

            try {
                const formData = new FormData();
                formData.append('urun_id', productId);
                formData.append('adet', 1);

                const response = await fetch('cart-add.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Sunucu hatası: ' + response.status);
                }

                const result = await response.json();
                console.log('Sepet yanıtı:', result);

                if (result.success) {
                    // Sepet sayısını güncelle
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge) {
                        cartBadge.textContent = result.cart_count;
                        cartBadge.style.display = 'flex';
                    } else if (result.cart_count > 0) {
                        const cartIcon = document.querySelector('.cart-icon');
                        if (cartIcon) {
                            const badge = document.createElement('span');
                            badge.className = 'cart-badge';
                            badge.textContent = result.cart_count;
                            cartIcon.appendChild(badge);
                        }
                    }

                    // Başarı mesajı
                    showNotification('✓ Ürün sepete eklendi!', 'success');
                } else {
                    showNotification(result.message || 'Bir hata oluştu!', 'error');
                }
            } catch (error) {
                console.error('Sepet hatası:', error);
                showNotification('❌ Bağlantı hatası! Lütfen tekrar deneyin.', 'error');
            }
        }

        // Dropdown menü
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            dropdown.addEventListener('mouseenter', function() {
                this.querySelector('.dropdown-menu').style.display = 'block';
            });
            dropdown.addEventListener('mouseleave', function() {
                this.querySelector('.dropdown-menu').style.display = 'none';
            });
        });
    </script>
</body>
</html>