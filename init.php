<?php
/**
 * NOVA PANEL - Database Initialization Script
 * Bu dosyayı ilk kuruluşta çalıştırın: php init.php
 */

define('DB_PATH', __DIR__ . '/db/nova.db');

// Create db directory
$dbDir = dirname(DB_PATH);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0700, true);
}

// Remove existing database (optional - for fresh install)
// if (file_exists(DB_PATH)) unlink(DB_PATH);

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "[*] Database bağlantısı kuruldu.\n";
    
    // Create users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            user_type TEXT DEFAULT 'free' CHECK(user_type IN ('free', 'premium', 'admin')),
            status TEXT DEFAULT 'active' CHECK(status IN ('active', 'suspended', 'deleted')),
            premium_until DATETIME,
            total_queries INTEGER DEFAULT 0,
            login_count INTEGER DEFAULT 0,
            ip_address TEXT,
            device_info TEXT,
            last_login DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "[✓] users tablosu oluşturuldu.\n";
    
    // Create query_logs table
    $db->exec("
        CREATE TABLE IF NOT EXISTS query_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            query_type TEXT NOT NULL,
            query_input TEXT NOT NULL,
            result_status TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "[✓] query_logs tablosu oluşturuldu.\n";
    
    // Create login_logs table
    $db->exec("
        CREATE TABLE IF NOT EXISTS login_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            ip_address TEXT,
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "[✓] login_logs tablosu oluşturuldu.\n";
    
    // Create activity_logs table
    $db->exec("
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            activity TEXT NOT NULL,
            details TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "[✓] activity_logs tablosu oluşturuldu.\n";
    
    // Create settings table
    $db->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "[✓] settings tablosu oluşturuldu.\n";
    
    // Check if admin exists
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM users WHERE user_type='admin'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['cnt'] == 0) {
        // Create admin user
        $adminUsername = 'admin';
        $adminEmail = 'admin@novapanel.com';
        $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("
            INSERT INTO users(username, email, password, user_type, status)
            VALUES(:username, :email, :password, 'admin', 'active')
        ");
        
        $stmt->execute([
            ':username' => $adminUsername,
            ':email' => $adminEmail,
            ':password' => $adminPassword
        ]);
        
        echo "[✓] Admin hesabı oluşturuldu.\n";
        echo "    Username: $adminUsername\n";
        echo "    Email: $adminEmail\n";
        echo "    Password: admin123\n";
    }
    
    // Set initial settings
    $settings = [
        'site_name' => 'NOVA PANEL',
        'site_version' => '1.0',
        'mail_domain' => '@nova.com',
        'api_base_url' => 'http://mockerapi.shop'
    ];
    
    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("
            INSERT OR REPLACE INTO settings(key, value)
            VALUES(:key, :value)
        ");
        $stmt->execute([':key' => $key, ':value' => $value]);
    }
    
    echo "[✓] Ayarlar kaydedildi.\n";
    echo "\n[SUCCESS] Database başlatılması tamamlandı!\n";
    echo "============================================\n";
    echo "Database Path: " . DB_PATH . "\n";
    echo "Admin Email: admin@novapanel.com\n";
    echo "Admin Password: admin123\n";
    echo "⚠️  İLK GİRİŞ SONRASI ŞİFRESİ DEĞİŞTİRİN!\n";
    echo "============================================\n";
    
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
