-- E-Ticaret Veritabanı
CREATE DATABASE IF NOT EXISTS eticaret CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE eticaret;

-- 1. Kullanıcılar Tablosu
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100) NOT NULL,
    soyad VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    sifre VARCHAR(255) NOT NULL,
    telefon VARCHAR(20),
    rol ENUM('admin', 'musteri') DEFAULT 'musteri',
    kayit_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- 2. Kategoriler Tablosu
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_adi VARCHAR(100) NOT NULL,
    aciklama TEXT,
    ust_kategori_id INT DEFAULT NULL,
    sira INT DEFAULT 0,
    aktif TINYINT(1) DEFAULT 1,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ust_kategori_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_ust_kategori (ust_kategori_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- 3. Ürünler Tablosu
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT NOT NULL,
    urun_adi VARCHAR(200) NOT NULL,
    aciklama TEXT,
    fiyat DECIMAL(10, 2) NOT NULL,
    indirimli_fiyat DECIMAL(10, 2) DEFAULT NULL,
    stok_miktari INT DEFAULT 0,
    resim VARCHAR(255),
    durum ENUM('aktif', 'pasif', 'tukendi') DEFAULT 'aktif',
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_kategori (kategori_id),
    INDEX idx_durum (durum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- 4. Sepet Tablosu
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    urun_id INT NOT NULL,
    adet INT DEFAULT 1,
    ekleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (urun_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (kullanici_id, urun_id),
    INDEX idx_kullanici (kullanici_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- 5. Siparişler Tablosu
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    siparis_no VARCHAR(50) UNIQUE NOT NULL,
    toplam_tutar DECIMAL(10, 2) NOT NULL,
    kargo_ucreti DECIMAL(10, 2) DEFAULT 0.00,
    genel_toplam DECIMAL(10, 2) NOT NULL,
    durum ENUM('beklemede', 'onaylandi', 'hazirlaniyor', 'kargoda', 'teslim_edildi', 'iptal') DEFAULT 'beklemede',
    odeme_yontemi VARCHAR(50),
    adres_id INT,
    siparis_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (adres_id) REFERENCES addresses(id) ON DELETE SET NULL,
    INDEX idx_kullanici (kullanici_id),
    INDEX idx_siparis_no (siparis_no),
    INDEX idx_durum (durum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- 6. Sipariş Detayları Tablosu
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siparis_id INT NOT NULL,
    urun_id INT NOT NULL,
    urun_adi VARCHAR(200) NOT NULL,
    adet INT NOT NULL,
    birim_fiyat DECIMAL(10, 2) NOT NULL,
    toplam_fiyat DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (siparis_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (urun_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_siparis (siparis_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- 7. Adresler Tablosu
CREATE TABLE addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT NOT NULL,
    adres_baslik VARCHAR(100) NOT NULL,
    ad_soyad VARCHAR(150) NOT NULL,
    telefon VARCHAR(20) NOT NULL,
    adres TEXT NOT NULL,
    il VARCHAR(50) NOT NULL,
    ilce VARCHAR(50) NOT NULL,
    posta_kodu VARCHAR(10),
    varsayilan TINYINT(1) DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_kullanici (kullanici_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- 8. Ürün Yorumları Tablosu
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    urun_id INT NOT NULL,
    kullanici_id INT NOT NULL,
    puan TINYINT CHECK (puan BETWEEN 1 AND 5),
    yorum TEXT,
    onay_durumu TINYINT(1) DEFAULT 0,
    yorum_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (urun_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (kullanici_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_urun (urun_id),
    INDEX idx_onay (onay_durumu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Örnek Veriler

-- Admin Kullanıcı (Şifre: admin123)
INSERT INTO users (ad, soyad, email, sifre, telefon, rol) VALUES
('Admin', 'Kullanıcı', 'admin@eticaret.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '05551234567', 'admin'),
('Ahmet', 'Yılmaz', 'ahmet@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '05559876543', 'musteri'),
('Ayşe', 'Demir', 'ayse@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '05558765432', 'musteri');

-- Kategoriler
INSERT INTO categories (kategori_adi, aciklama, ust_kategori_id, sira) VALUES
('Elektronik', 'Elektronik Ürünler', NULL, 1),
('Giyim', 'Giyim Ürünleri', NULL, 2),
('Ev & Yaşam', 'Ev ve Yaşam Ürünleri', NULL, 3),
('Cep Telefonu', 'Akıllı Telefonlar', 1, 1),
('Bilgisayar', 'Dizüstü ve Masaüstü Bilgisayarlar', 1, 2),
('Erkek Giyim', 'Erkek Giyim Ürünleri', 2, 1),
('Kadın Giyim', 'Kadın Giyim Ürünleri', 2, 2),
('Mobilya', 'Ev Mobilyaları', 3, 1);

-- Ürünler
INSERT INTO products (kategori_id, urun_adi, aciklama, fiyat, indirimli_fiyat, stok_miktari, resim, durum) VALUES
(4, 'iPhone 15 Pro', '256GB, Siyah Titanyum, A17 Pro İşlemci', 54999.00, 52999.00, 25, 'iphone15pro.jpg', 'aktif'),
(4, 'Samsung Galaxy S24', '256GB, Phantom Black, Snapdragon 8 Gen 3', 42999.00, 39999.00, 30, 'galaxys24.jpg', 'aktif'),
(5, 'MacBook Air M2', '13 inç, 16GB RAM, 512GB SSD', 39999.00, 37999.00, 15, 'macbookair.jpg', 'aktif'),
(5, 'Dell XPS 15', 'Intel i7, 32GB RAM, 1TB SSD', 45999.00, NULL, 10, 'dellxps15.jpg', 'aktif'),
(6, 'Erkek Kot Pantolon', 'Slim Fit, Lacivert', 399.00, 299.00, 100, 'kotpantolon.jpg', 'aktif'),
(6, 'Erkek Gömlek', 'Beyaz, Klasik Kesim', 299.00, NULL, 80, 'gomlek.jpg', 'aktif'),
(7, 'Kadın Elbise', 'Çiçek Desenli, Yazlık', 599.00, 449.00, 50, 'elbise.jpg', 'aktif'),
(8, 'Koltuk Takımı', '3+2+1, Gri Renk', 15999.00, 13999.00, 5, 'koltuk.jpg', 'aktif');

-- Adresler
INSERT INTO addresses (kullanici_id, adres_baslik, ad_soyad, telefon, adres, il, ilce, posta_kodu, varsayilan) VALUES
(2, 'Ev Adresi', 'Ahmet Yılmaz', '05559876543', 'Atatürk Mahallesi, Cumhuriyet Caddesi No:45', 'İstanbul', 'Kadıköy', '34710', 1),
(3, 'İş Adresi', 'Ayşe Demir', '05558765432', 'Kızılay Mahallesi, İnönü Bulvarı No:123', 'Ankara', 'Çankaya', '06420', 1);

-- Yorumlar
INSERT INTO reviews (urun_id, kullanici_id, puan, yorum, onay_durumu) VALUES
(1, 2, 5, 'Harika bir telefon, kamera kalitesi mükemmel!', 1),
(1, 3, 4, 'Çok beğendim ama fiyatı biraz yüksek.', 1),
(3, 2, 5, 'MacBook Air M2 hızı ve tasarımıyla muhteşem!', 1);
