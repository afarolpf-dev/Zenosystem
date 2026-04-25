<?php
require_once __DIR__ . '/config/database.php';
initSession();
requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id=:id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: /index.php');
    exit;
}

// Calculate greeting
$hour = (int)date('G');
if ($hour < 6) $greeting = 'İyi Geceler';
elseif ($hour < 12) $greeting = 'İyi Sabahlar';
elseif ($hour < 18) $greeting = 'İyi Günler';
else $greeting = 'İyi Akşamlar';

// Premium status
$premiumText = '—';
if ($user['user_type'] === 'admin') {
    $premiumText = '👑 Admin';
} elseif ($user['premium_until']) {
    $premiumText = date('d.m.Y', strtotime($user['premium_until']));
}

// Get statistics
$queryCount = $user['total_queries'] ?? 0;
$loginCount = $user['login_count'] ?? 0;

// Get recent queries
$stmt = $db->prepare("
    SELECT * FROM query_logs 
    WHERE user_id=:uid 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([':uid' => $userId]);
$recentQueries = $stmt->fetchAll();

$csrf = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NOVA PANEL</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%232563eb' width='100' height='100'/><text x='50' y='70' font-size='60' fill='white' text-anchor='middle' font-weight='bold'>N</text></svg>" type="image/svg+xml">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }
        
        .topbar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .menu-btn {
            background: none;
            border: none;
            color: #3b82f6;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
        }
        
        .logo-mini {
            font-weight: 700;
            color: #fff;
            font-size: 16px;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            text-align: right;
            font-size: 13px;
        }
        
        .user-info strong {
            color: #3b82f6;
            display: block;
        }
        
        .logout-btn {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.25);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .greeting-section {
            margin-bottom: 40px;
        }
        
        .greeting-section h2 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .greeting-section h2 span {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .greeting-section p {
            color: #94a3b8;
            font-size: 14px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            border-color: rgba(59, 130, 246, 0.3);
            transform: translateY(-2px);
        }
        
        .stat-icon {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #3b82f6;
        }
        
        .query-types {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .query-types h3 {
            font-size: 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .query-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .query-btn {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #3b82f6;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .query-btn:hover {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.5);
            transform: translateY(-2px);
        }
        
        .query-icon {
            font-size: 24px;
        }
        
        .recent-queries {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 12px;
            padding: 25px;
        }
        
        .recent-queries h3 {
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .query-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
            font-size: 13px;
        }
        
        .query-item:last-child {
            border-bottom: none;
        }
        
        .query-type {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .query-time {
            color: #64748b;
            font-size: 12px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }
        
        @media (max-width: 768px) {
            .topbar-right {
                gap: 10px;
            }
            
            .user-info {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .query-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-left">
            <div class="logo-mini">NOVA PANEL</div>
        </div>
        <div class="topbar-right">
            <div class="user-info">
                <strong><?=sanitize($user['username'])?></strong>
                <span><?=strtoupper($user['user_type'])?></span>
            </div>
            <form method="POST" action="/logout.php" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?=$csrf?>">
                <button type="submit" class="logout-btn">🚪 Çıkış</button>
            </form>
        </div>
    </div>
    
    <div class="container">
        <div class="greeting-section">
            <h2><?=$greeting?>, <span><?=sanitize($user['username'])?></span></h2>
            <p>NOVA PANEL sorgu sistemine hoş geldiniz. Aşağıdaki sorgu türlerinden birini seçin.</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👤</div>
                <div class="stat-label">Üyelik Türü</div>
                <div class="stat-value"><?=strtoupper($user['user_type'])?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-label">Premium</div>
                <div class="stat-value" style="font-size: 16px;"><?=$premiumText?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🔍</div>
                <div class="stat-label">Toplam Sorgu</div>
                <div class="stat-value"><?=$queryCount?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-label">Giriş Sayısı</div>
                <div class="stat-value"><?=$loginCount?></div>
            </div>
        </div>
        
        <div class="query-types">
            <h3>🔍 Sorgu Türleri</h3>
            <div class="query-grid">
                <a href="/query.php?type=tc" class="query-btn">
                    <span class="query-icon">🆔</span>
                    TC Sorgu
                </a>
                <a href="/query.php?type=adsoyad" class="query-btn">
                    <span class="query-icon">👤</span>
                    Ad Soyad
                </a>
                <a href="/query.php?type=aile" class="query-btn">
                    <span class="query-icon">👨‍👩‍👧‍👦</span>
                    Aile Sorgusu
                </a>
                <a href="/query.php?type=tcgsm" class="query-btn">
                    <span class="query-icon">📱</span>
                    TC → GSM
                </a>
                <a href="/query.php?type=gsmtc" class="query-btn">
                    <span class="query-icon">📞</span>
                    GSM → TC
                </a>
                <a href="/query.php?type=sulale" class="query-btn">
                    <span class="query-icon">🌳</span>
                    Şecere
                </a>
            </div>
        </div>
        
        <?php if (count($recentQueries) > 0): ?>
        <div class="recent-queries">
            <h3>📋 Son Sorgular</h3>
            <?php foreach ($recentQueries as $q): ?>
            <div class="query-item">
                <div>
                    <span class="query-type"><?=strtoupper($q['query_type'])?></span>
                    <span style="margin-left: 10px; color: #cbd5e1;"><?=sanitize(substr($q['query_input'], 0, 30))?><?=strlen($q['query_input']) > 30 ? '...' : ''?></span>
                </div>
                <span class="query-time"><?=date('H:i', strtotime($q['created_at']))?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
