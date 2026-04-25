# NOVA PANEL v1.0 - Sorgu Paneli Sistemi

Modern ve güvenli bir sorgu paneli sistemi.

## 🚀 Hızlı Kurulum

### 1. Dosyaları Sunucuya Yükle

```bash
# VDS üzerinde
unzip nova-panel.zip -d /var/www/html/nova

# veya XAMPP (Windows/Mac)
unzip nova-panel.zip -d C:/xampp/htdocs/nova
```

### 2. Database Oluştur

```bash
cd /var/www/html/nova
php init.php
```

Bu komut şu işlemleri gerçekleştirir:
- SQLite database oluşturur (`db/nova.db`)
- Tüm gerekli tabloları oluşturur
- Admin hesabı ekler:
  - Email: `admin@novapanel.com`
  - Password: `admin123`

### 3. Dosya İzinleri Ayarla

```bash
# Linux/macOS için
chmod 755 /var/www/html/nova
chmod 700 /var/www/html/nova/db
chmod 600 /var/www/html/nova/db/nova.db
chmod 644 /var/www/html/nova/.htaccess
```

### 4. Apache VirtualHost Yapılandır

`/etc/apache2/sites-available/nova.conf` dosyasını oluştur:

```apache
<VirtualHost *:80>
    ServerName novapanel.site
    ServerAlias www.novapanel.site
    DocumentRoot /var/www/html/nova
    
    <Directory /var/www/html/nova>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/nova-error.log
    CustomLog ${APACHE_LOG_DIR}/nova-access.log combined
</VirtualHost>
```

Sonra çalıştır:
```bash
sudo a2ensite nova.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 5. DNS Yapılandır (Cloudflare)

1. Cloudflare kontrol panelinde oturum aç
2. A Record ekle: `novapanel.site` → VDS IP
3. Proxy: ON (turuncu bulut)
4. SSL: Full (Strict)

### 6. İlk Giriş

- URL: https://novapanel.site/
- Email: admin@novapanel.com
- Password: admin123

**ÖNEMLİ: İlk girişten sonra şifreyi değiştirin!**

## 📁 Dosya Yapısı

```
nova/
├── config/
│   └── database.php           # Database bağlantısı
├── db/
│   └── nova.db                # SQLite veritabanı (init.php ile oluşur)
├── index.php                  # Login/Register sayfası
├── dashboard.php              # Ana sayfa
├── query.php                  # Sorgu sayfası
├── logout.php                 # Çıkış işlemi
├── init.php                   # Database başlatma
├── .htaccess                  # Güvenlik kuralları
└── README.md                  # Bu dosya
```

## 🔐 Güvenlik Özellikleri

### SQL Injection Koruması
- **Prepared Statements** kullanılıyor
- Tüm veritabanı sorguları parameterized

### XSS Koruması
- `htmlspecialchars()` ile tüm çıktılar sanitize ediliyor
- Content Security Policy başlıkları

### CSRF Koruması
- Her formda CSRF token doğrulaması
- Token regeneration her giriş sonrası

### Session Güvenliği
- `httponly` flag aktif
- `samesite=Strict` ayarı
- 15 dakikalık session timeout
- HTTPS önerilir

### Dosya Güvenliği
- `.htaccess` ile config, db, includes klasörleri korunuyor
- `.db`, `.env`, `.sqlite` dosyaları engelleniyor
- Direct erişim engelleniyor

### Diğer Güvenlik Başlıkları
- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`

## 🔧 Yapılandırma

### config/database.php

Şu değerleri güncelle:

```php
define('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN');
define('FOUNDER_ID', 12345678);  // Telegram ID'niz
define('API_KEY', 'YOUR_API_KEY');
define('PANEL_URL', 'https://novapanel.site');
```

## 📊 Veritabanı Tabloları

### users
- Kullanıcı hesapları
- Üyelik türü (free, premium, admin)
- Premium tarih bilgisi

### query_logs
- Tüm sorgu kayıtları
- Sorgu türü ve girdileri
- IP adresleri

### login_logs
- Giriş kayıtları
- IP ve device bilgisi
- Zaman damgası

### activity_logs
- Genel aktivite kayıtları
- Giriş, çıkış, kayıt vb.

### settings
- Site ayarları
- Dinamik konfigürasyon

## 🎯 Ana Özellikler

### Sorgu Türleri
- 🆔 TC Sorgu
- 👤 Ad Soyad Sorgu
- 👨‍👩‍👧‍👦 Aile Sorgusu
- 🌳 Şecere (Sulale)
- 💑 Eş Sorgusu
- 👶 Çocuk Sorgusu
- 📱 TC → GSM
- 📞 GSM → TC

### Kullanıcı Yönetimi
- Ücretsiz (free) hesaplar
- Premium üyelikler
- Admin paneli
- Kullanıcı aktivite logs
- Giriş geçmişi

### İstatistikler
- Toplam sorgu sayısı
- Giriş sayısı
- Premium tarih
- Son sorgular

### Export Özellikleri
- CSV indir
- Excel indir
- Sorgu sonuçları

## 📱 Responsive Design

- Mobil cihazlarda optimizlenmiş
- Tablet uyumlu
- Desktop full deneyimi
- Touch-friendly arayüz

## 🐛 Sorun Giderme

### Database Hatası
```bash
# Dosya izinlerini kontrol et
ls -la db/
chmod 700 db/
chmod 600 db/nova.db
```

### Session Hatası
- Tarayıcı çerezlerini temizle
- HTTPS kullanılıyor mu kontrol et
- PHP session directory yazılabilir mi kontrol et

### API Hatası
- API_KEY ve API_BASE_URL doğru mu?
- Sunucu bağlantı sorunu mu?

## 💡 İpuçları

### Performance
- Apache cache modülleri aktif et
- Gzip compression açık
- CDN kullan (Cloudflare)

### Backup
```bash
# Database yedeğini al
cp db/nova.db db/nova.db.backup

# Tüm backup
zip -r nova-backup.zip .
```

### Log Kontrolü
```bash
# Apache hatalarını kontrol et
tail -f /var/log/apache2/nova-error.log

# Activity logs kontrol et
sqlite3 db/nova.db "SELECT * FROM activity_logs LIMIT 10;"
```

## 📞 Destek

- Issues için GitHub PR açın
- Güvenlik sorunları: security@novapanel.local
- Özellik talepleri: features@novapanel.local

## 📜 Lisans

MIT License - Özgürce kullan ve modifiye et.

## 🎉 Teşekkürler

Modern sorgu paneli sistemi için kullanılan teknolojiler:
- PHP 7.4+
- SQLite3
- HTML5
- CSS3 (Modern Grid/Flexbox)
- Vanilla JavaScript

---

**Versiyon:** 1.0  
**Son Güncelleme:** 2026  
**Durum:** Üretim Hazır ✅
