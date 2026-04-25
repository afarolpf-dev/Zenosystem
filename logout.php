<?php
require_once __DIR__ . '/config/database.php';
initSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (verifyCSRFToken($token) && isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'logout', 'Çıkış yapıldı');
    }
}

session_unset();
session_destroy();
header('Location: /index.php');
exit;
