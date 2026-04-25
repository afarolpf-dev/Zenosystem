<?php
require_once __DIR__ . '/config/database.php';
initSession();

if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        $error = 'Güvenlik hatası.';
    } else {
        $action = $_POST['action'] ?? 'login';
        $db = getDB();
        
        if ($action === 'login') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $error = 'Tüm alanları doldurun.';
            } else {
                $stmt = $db->prepare("SELECT * FROM users WHERE email=:email OR username=:email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] !== 'active') {
                        $error = 'Hesap askıda veya silinmiş.';
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_type'] = $user['user_type'];
                        $_SESSION['premium_until'] = $user['premium_until'];
                        
                        $ip = getClientIP();
                        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
                        
                        $db->prepare("UPDATE users SET last_login=datetime('now','localtime'), login_count=login_count+1, ip_address=:ip, device_info=:ua WHERE id=:id")
                            ->execute([':ip' => $ip, ':ua' => $ua, ':id' => $user['id']]);
                        
                        $db->prepare("INSERT INTO login_logs(user_id, ip_address, user_agent) VALUES(:uid, :ip, :ua)")
                            ->execute([':uid' => $user['id'], ':ip' => $ip, ':ua' => $ua]);
                        
                        logActivity($user['id'], 'login', 'Giriş yapıldı');
                        session_regenerate_id(true);
                        header('Location: /dashboard.php');
                        exit;
                    }
                } else {
                    $error = 'Email/Kullanıcı adı veya şifre hatalı.';
                }
            }
        } elseif ($action === 'register') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';
            
            if (empty($username) || empty($email) || empty($password)) {
                $error = 'Tüm alanları doldurun.';
            } elseif ($password !== $password_confirm) {
                $error = 'Şifreler eşleşmiyor.';
            } elseif (strlen($password) < 6) {
                $error = 'Şifre en az 6 karakter olmalı.';
            } else {
                $stmt = $db->prepare("SELECT id FROM users WHERE email=:email OR username=:username LIMIT 1");
                $stmt->execute([':email' => $email, ':username' => $username]);
                if ($stmt->fetch()) {
                    $error = 'Bu email veya kullanıcı adı zaten kullanılıyor.';
                } else {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $ip = getClientIP();
                    
                    try {
                        $stmt = $db->prepare("
                            INSERT INTO users(username, email, password, user_type, status, ip_address)
                            VALUES(:username, :email, :password, 'free', 'active', :ip)
                        ");
                        $stmt->execute([
                            ':username' => $username,
                            ':email' => $email,
                            ':password' => $hashed,
                            ':ip' => $ip
                        ]);
                        
                        $userId = $db->lastInsertId();
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        $_SESSION['user_type'] = 'free';
                        $_SESSION['premium_until'] = null;
                        
                        logActivity($userId, 'register', 'Hesap oluşturuldu');
                        session_regenerate_id(true);
                        header('Location: /dashboard.php');
                        exit;
                    } catch (Exception $e) {
                        $error = 'Hesap oluşturmada hata oluştu.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>NOVA PANEL - Sorgu Paneli | Ücretsiz & Hızlı</title>
    <meta name="description" content="NOVA PANEL - Türkiye'nin en hızlı ve güvenilir sorgu paneli. Ücretsiz sorgu hizmetleri.">
    <meta name="keywords" content="sorgu paneli, ücretsiz sorgu, tc sorgu, gsm sorgu, nova panel">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%232563eb' width='100' height='100'/><text x='50' y='70' font-size='60' fill='white' text-anchor='middle' font-weight='bold'>N</text></svg>" type="image/svg+xml">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            position: relative;
            width: 100%;
            max-width: 450px;
        }
        
        .login-card {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 32px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }
        
        .logo h1 span {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .logo p {
            color: #94a3b8;
            font-size: 13px;
            letter-spacing: 1px;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            padding-bottom: 15px;
        }
        
        .tab {
            flex: 1;
            padding: 10px;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            position: relative;
            transition: color 0.3s;
        }
        
        .tab.active {
            color: #3b82f6;
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #2563eb, #3b82f6);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            font-size: 12px;
            color: #cbd5e1;
            margin-bottom: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 14px;
            background: rgba(51, 65, 85, 0.5);
            border: 1px solid rgba(148, 163, 184, 0.15);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            outline: none;
            background: rgba(51, 65, 85, 0.8);
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-input::placeholder {
            color: #64748b;
        }
        
        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 15px;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }
        
        .btn {
            width: 100%;
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            gap: 15px;
            font-size: 12px;
            color: #64748b;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(148, 163, 184, 0.15);
        }
        
        .form-tab {
            display: none;
        }
        
        .form-tab.active {
            display: block;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #64748b;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
            }
            
            .logo h1 {
                font-size: 24px;
            }
            
            .tabs {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <h1>NOVA<span>PANEL</span></h1>
                <p>Sorgu & Yönetim</p>
            </div>
            
            <div class="tabs">
                <button class="tab active" onclick="switchTab(event, 'login-form')">Giriş Yap</button>
                <button class="tab" onclick="switchTab(event, 'register-form')">Kayıt Ol</button>
            </div>
            
            <!-- Login Form -->
            <div id="login-form" class="form-tab active">
                <?php if($error && isset($_POST['action']) && $_POST['action'] === 'login'): ?>
                <div class="alert alert-error"><?=sanitize($error)?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?=$csrf?>">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label>Email veya Kullanıcı Adı</label>
                        <input type="text" name="email" class="form-input" placeholder="ornek@email.com" required autocomplete="username">
                    </div>
                    
                    <div class="form-group">
                        <label>Şifre</label>
                        <input type="password" name="password" class="form-input" placeholder="••••••••" required autocomplete="current-password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">GİRİŞ YAP</button>
                </form>
            </div>
            
            <!-- Register Form -->
            <div id="register-form" class="form-tab">
                <?php if($error && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
                <div class="alert alert-error"><?=sanitize($error)?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?=$csrf?>">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                        <label>Kullanıcı Adı</label>
                        <input type="text" name="username" class="form-input" placeholder="kullanici_adi" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-input" placeholder="ornek@email.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Şifre</label>
                        <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Şifre Tekrar</label>
                        <input type="password" name="password_confirm" class="form-input" placeholder="••••••••" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">KAYIT OL</button>
                </form>
            </div>
            
            <div class="login-footer">
                NOVA PANEL v1.0 — Hızlı & Güvenilir Sorgu Sistemi
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(event, tabId) {
            event.preventDefault();
            
            // Hide all tabs
            document.querySelectorAll('.form-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all buttons
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab and mark button active
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
