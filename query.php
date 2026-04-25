<?php
require_once __DIR__ . '/config/database.php';
initSession();
requireLogin();

$type = $_GET['type'] ?? '';
$csrf = generateCSRFToken();

$queries = [
    'tc' => [
        'title' => 'TC Sorgu',
        'icon' => '🆔',
        'fields' => [
            ['tc', 'TC Numarası', '12345678901', true]
        ],
        'note' => '11 haneli TC numaranızı giriniz.'
    ],
    'adsoyad' => [
        'title' => 'Ad Soyad Sorgu',
        'icon' => '👤',
        'fields' => [
            ['ad', 'Ad', 'Ad', true],
            ['soyad', 'Soyad', 'Soyad', true],
            ['il', 'İl', 'İl (opsiyonel)', false],
            ['ilce', 'İlçe', 'İlçe (opsiyonel)', false]
        ],
        'note' => 'Ad ve Soyad zorunludur. İl ve İlçe isteğe bağlı.'
    ],
    'aile' => [
        'title' => 'Aile Sorgusu',
        'icon' => '👨‍👩‍👧‍👦',
        'fields' => [
            ['tc', 'TC Numarası', '12345678901', true]
        ],
        'note' => 'Aile bireylerinizin bilgilerini getirir.'
    ],
    'sulale' => [
        'title' => 'Şecere (Sulale)',
        'icon' => '🌳',
        'fields' => [
            ['tc', 'TC Numarası', '12345678901', true]
        ],
        'note' => 'Soyağacınızı gösterir.'
    ],
    'es' => [
        'title' => 'Eş Sorgusu',
        'icon' => '💑',
        'fields' => [
            ['tc', 'TC Numarası', '12345678901', true]
        ],
        'note' => 'Eşinizin bilgisini getirir.'
    ],
    'cocuk' => [
        'title' => 'Çocuk Sorgusu',
        'icon' => '👶',
        'fields' => [
            ['tc', 'TC Numarası', '12345678901', true]
        ],
        'note' => 'Çocuklarınızın bilgisini getirir.'
    ],
    'tcgsm' => [
        'title' => 'TC → GSM',
        'icon' => '📱',
        'fields' => [
            ['tc', 'TC Numarası', '12345678901', true]
        ],
        'note' => 'TC numarasından GSM numarası bulur.'
    ],
    'gsmtc' => [
        'title' => 'GSM → TC',
        'icon' => '📞',
        'fields' => [
            ['gsm', 'GSM Numarası', '5051234567', true]
        ],
        'note' => 'GSM numarasından TC numarası bulur.'
    ]
];

if (!isset($queries[$type])) {
    header('Location: /dashboard.php');
    exit;
}

$q = $queries[$type];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=sanitize($q['title'])?> - NOVA PANEL</title>
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
        }
        
        .topbar a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .topbar a:hover {
            color: #60a5fa;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .query-header {
            margin-bottom: 30px;
        }
        
        .query-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .query-icon {
            font-size: 32px;
        }
        
        .query-header p {
            color: #94a3b8;
        }
        
        .query-panel {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
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
        
        .form-note {
            color: #94a3b8;
            font-size: 12px;
            margin-bottom: 15px;
            padding: 10px 12px;
            background: rgba(148, 163, 184, 0.05);
            border-left: 3px solid #3b82f6;
            border-radius: 4px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .result-panel {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 12px;
            padding: 25px;
        }
        
        .result-empty {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }
        
        .result-error {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid rgba(239, 68, 68, 0.5);
            color: #fca5a5;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .result-loading {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #3b82f6;
        }
        
        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(59, 130, 246, 0.3);
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .result-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .result-table tr {
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }
        
        .result-table tr:last-child {
            border-bottom: none;
        }
        
        .result-table td {
            padding: 12px;
            font-size: 14px;
        }
        
        .result-key {
            font-weight: 600;
            color: #3b82f6;
            width: 30%;
        }
        
        .result-val {
            color: #cbd5e1;
        }
        
        .export-btns {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn-export {
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #3b82f6;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn-export:hover {
            background: rgba(59, 130, 246, 0.25);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .query-header h2 {
                font-size: 20px;
            }
            
            .result-key {
                width: 40%;
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <a href="/dashboard.php">← Dashboard</a>
        <span>NOVA PANEL</span>
    </div>
    
    <div class="container">
        <div class="query-header">
            <h2>
                <span class="query-icon"><?=$q['icon']?></span>
                <?=sanitize($q['title'])?>
            </h2>
            <p>Sorgu panelinden istediğiniz bilgileri hızlıca öğrenin.</p>
        </div>
        
        <div class="query-panel">
            <form id="queryForm">
                <input type="hidden" name="csrf_token" value="<?=$csrf?>">
                <input type="hidden" name="type" value="<?=sanitize($type)?>">
                
                <?php foreach ($q['fields'] as $field): ?>
                <div class="form-group">
                    <label><?=sanitize($field[1])?>
                        <?php if (!$field[3]): ?>
                        <span style="color: #94a3b8; font-weight: 400;">(opsiyonel)</span>
                        <?php endif; ?>
                    </label>
                    <input 
                        type="text" 
                        name="<?=$field[0]?>" 
                        class="form-input" 
                        placeholder="<?=sanitize($field[2])?>"
                        <?php if ($field[3]): ?>required<?php endif; ?>
                        <?php if (count($q['fields']) <= 2): ?>autofocus<?php endif; ?>
                    >
                </div>
                <?php endforeach; ?>
                
                <?php if ($q['note']): ?>
                <div class="form-note">ℹ️ <?=sanitize($q['note'])?></div>
                <?php endif; ?>
                
                <button type="submit" class="btn-submit">🔍 SORGU YAP</button>
            </form>
        </div>
        
        <div class="result-panel">
            <div id="resultContainer" class="result-empty">
                Sorgu sonuçları burada görünecek
            </div>
        </div>
    </div>
    
    <script>
        const form = document.getElementById('queryForm');
        const resultContainer = document.getElementById('resultContainer');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> Sorgulanıyor...';
            
            resultContainer.innerHTML = '<div class="result-loading"><span class="spinner"></span> Sorgu işleniyor...</div>';
            
            try {
                // Simulating API call
                setTimeout(() => {
                    // Mock response
                    const mockResults = {
                        status: true,
                        data: {
                            'Ad': 'Örnek İsim',
                            'Soyad': 'Örnek Soyad',
                            'TC': '12345678901',
                            'Doğum Tarihi': '01.01.1990',
                            'Cinsiyet': 'Erkek',
                            'Uyruk': 'TC'
                        }
                    };
                    
                    displayResults(mockResults);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 1500);
            } catch (error) {
                resultContainer.innerHTML = '<div class="result-error">❌ Bir hata oluştu. Lütfen tekrar deneyin.</div>';
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
        
        function displayResults(response) {
            if (!response.status || !response.data) {
                resultContainer.innerHTML = '<div class="result-empty">❌ Sonuç bulunamadı</div>';
                return;
            }
            
            let html = '<div class="export-btns"><button class="btn-export" onclick="exportCSV()">📄 CSV Indir</button><button class="btn-export" onclick="exportExcel()">📊 Excel İndir</button></div>';
            html += '<table class="result-table" id="resultTable"><tbody>';
            
            for (const [key, value] of Object.entries(response.data)) {
                html += `<tr><td class="result-key">${escapeHtml(key)}</td><td class="result-val">${escapeHtml(String(value || ''))}</td></tr>`;
            }
            
            html += '</tbody></table>';
            resultContainer.innerHTML = html;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }
        
        function exportCSV() {
            const table = document.getElementById('resultTable');
            if (!table) return;
            
            let csv = '\uFEFF';
            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const rowData = Array.from(cells).map(cell => `"${cell.textContent.replace(/"/g, '""')}"`).join(',');
                csv += rowData + '\n';
            });
            
            downloadFile(csv, 'text/csv;charset=utf-8;', '.csv');
        }
        
        function exportExcel() {
            const table = document.getElementById('resultTable');
            if (!table) return;
            
            const html = '<html><head><meta charset="utf-8"></head><body>' + table.outerHTML + '</body></html>';
            downloadFile(html, 'application/vnd.ms-excel', '.xls');
        }
        
        function downloadFile(content, type, ext) {
            const blob = new Blob([content], { type });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'nova_query_' + Date.now() + ext;
            a.click();
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
