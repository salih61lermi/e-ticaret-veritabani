<?php
require_once 'config.php';

// Oturumu temizle
session_destroy();

// Başarı mesajı göster
session_start();
showAlert('Başarıyla çıkış yaptınız.', 'success');

// Ana sayfaya yönlendir
redirect('index.php');
?>