# B2B Enterprise Portal

Kurumsal B2B E-Ticaret, Çok Katmanlı Bayi Yönetimi ve ERP Operasyon Sistemi.  
Saf modern **PHP 8.2+ (Strict Types)**, **MVC + Service-Repository** mimarisi, **MySQL / MariaDB (InnoDB, UTF8MB4)**, **Tailwind CSS**, **Alpine.js 3.x** ve **Lucide Icons** ile harici ağır framework bağımlılığı olmaksızın geliştirilmiştir.

---

## 🌟 Öne Çıkan Özellikler

### 1. 5 Kademeli Dinamik Fiyatlandırma Motoru (`PricingEngine`)
- **Döviz Kuru Çevrimi:** TCMB USD ve EUR kurları üzerinden dinamik yerel para birimi hesaplaması.
- **Özel Fiyat Listeleri:** Bayiye veya bayi grubuna atanmış özel ürün fiyatları.
- **Bayi Cari İskontosu:** Firma düzeyinde tanımlanmış temel iskonto yüzdesi.
- **Kademeli Miktar İskontosu (Tier Pricing):** Sipariş miktarına bağlı otomatik kademeli bileşik iskonto.
- **KDV Matrahı:** Türkiye vergi mevzuatına tam uyumlu KDV matrah hesaplaması.
- **Finansal Hassasiyet:** Tüm hesaplamalarda `bcmath` kullanılarak kuruş yuvarlama kayıpları engellenmiştir.

### 2. Çok Katmanlı Bayi ve Alt Kullanıcı Rolleri (RBAC)
- **Firma Sahibi (`company_owner`):** Sipariş onayı, bakiye/ekstre takibi, alt kullanıcı yetkilendirmesi.
- **Satın Alma Sorumlusu (`purchasing_manager`):** Limit dahilinde sipariş verme veya onaya gönderme.
- **Satın Almacı (`buyer`):** Sepet oluşturma ve sipariş taslağı hazırlama.
- **Muhasebeci (`accountant`):** Cari hesap ekstresi inceleme, dekont yükleme ve ödeme bildirimi.

### 3. Hızlı Sipariş Matrisi ve Toplu Yükleme (`QuickOrder`)
- **Canlı SKU Arama Matrisi:** 15+ satırlık hızlı ürün kodu ve adet giriş tablosu.
- **CSV / Excel Yükleme:** Dosyadan toplu ürün kodu ve adet aktarımı.
- **Serbest Metin Kopyala-Yapıştır:** ERP veya e-posta listelerinden doğrudan metin ayrıştırma.

### 4. Fiyat Teklifi (RFQ - Request For Quote) Akışı
- Proje bazlı veya yüksek adetli siparişlerde bayi tarafından özel fiyat talep edebilme.
- Yönetim panelinden satır bazında karşı teklif fiyatı ve geçerlilik süresi belirleme.
- Onaylanan teklifin tek tıkla doğrudan siparişe dönüştürülmesi.

### 5. Finans, Cari Hesap ve Vade Yaşlandırma (Aging)
- **FIFO Tahakkuk:** Ödemelerin en eski açık faturalardan düşüldüğü yaşlandırma analizi (Vadesi Gelmemiş, 0-30 gün, 31-60 gün, 61-90 gün, 90+ gün).
- **Dekont Bildirimi:** Banka havale/EFT işlemlerinde dekont belgesi yükleme ve muhasebe onay döngüsü.
- **Kullanılabilir Limit:** Kredi limiti - (Cari Borç + Onay Bekleyen Siparişler) formülüyle anlık risk denetimi.

### 6. ERP Yönetim Paneli
- **Bayi Onay Süreci:** Vergi levhası denetimi, cari risk limiti ve iskonto ataması.
- **Sipariş Operasyonu:** Onaylama, çoklu depodan stok rezervasyonu, kargo takip girişi ve faturalandırma.
- **Çoklu Depo Stok Yönetimi:** Ana depo, sevkiyat deposu ve yedek depo bazında stok takibi.
- **Döviz Kurları & Sistem Ayarları:** TCMB satış kurları, KDV oranları ve denetim logları (`Audit Logs`).

### 7. Web Tabanlı Kurulum Sihirbazı (`/kurulum`)
- PHP sürümü, PDO, cURL, GD ve bcmath eklentilerinin otomatik sistem denetimi.
- Veritabanı bağlantı testi, 28 tablonun otomatik migrasyonu ve demo verilerin yüklenmesi.
- `storage/installed.lock` ile yetkisiz tekrar kurulumların engellenmesi.

---

## 🏛️ Mimari Yapı

```
├── app/
│   ├── Controllers/         # Bayi portalı ve genel denetleyiciler
│   │   └── Admin/           # ERP yönetim paneli denetleyicileri
│   ├── Core/                # Router, Request, Response, Database, Session, View, Validator
│   ├── Middleware/          # Auth, Admin, B2BApproved, Csrf, Permission (RBAC)
│   ├── Services/            # PricingEngine, CartService, OrderService, CreditRiskService...
│   └── routes.php           # REST rota ve middleware tanımları
├── config/                  # Uygulama, veritabanı ve yetki matrisi yapılandırmaları
├── database/
│   ├── migrations/          # 28 tabloluk kurumsal DDL şeması
│   ├── seeds/               # Kategori, marka, depo, ürün, bayi ve finans test verileri
│   └── migrate.php          # CLI migrasyon ve seed aracı
├── public/                  # DocumentRoot (Front Controller index.php, assets, logo, css, js)
├── resources/views/         # Portal, Admin, Auth ve Kurulum arayüz şablonları
└── storage/                 # Loglar, yüklenen evraklar ve kurulum kilit dosyası
```

---

## 🚀 Kurulum Adımları

### Gereksinimler
- PHP 8.2 veya üzeri
- MySQL 8.0+ veya MariaDB 10.6+
- PHP Eklentileri: `pdo`, `pdo_mysql`, `bcmath`, `gd`, `mbstring`, `json`, `fileinfo`
- Web Sunucusu: Apache (mod_rewrite aktif) veya Nginx

### Yöntem 1: Web Tabanlı Kurulum Sihirbazı (Önerilen)
1. Projeyi web sunucunuzun kök dizinine yerleştirin (DocumentRoot: `public/`).
2. Tarayıcınızda `http://localhost/kurulum` veya `http://b2b.test/kurulum` adresine gidin.
3. Sunucu gereksinim denetimini onaylayın.
4. Veritabanı bilgilerinizi girin ve yönetici hesabınızı tanımlayın.
5. Kurulum otomatik olarak tabloları ve örnek verileri oluşturacaktır.

### Yöntem 2: Komut Satırı (CLI) ile Kurulum
1. Ortam dosyasını hazırlayın:
   ```bash
   cp .env.example .env
   ```
2. Veritabanı bağlantı bilgilerinizi `config/database.php` veya `.env` içerisine tanımlayın.
3. Tabloları ve demo verileri oluşturun:
   ```bash
   php database/migrate.php fresh
   ```

---

## 🔑 Örnek Giriş Hesapları

| Rol | E-Posta | Şifre | Açıklama |
|---|---|---|---|
| **Süper Admin** | `admin@b2b.test` | `Admin123!` | Tam yetkili ERP yöneticisi |
| **Satış Yöneticisi** | `satis@b2b.test` | `Satis123!` | Bayi, sipariş ve teklif operasyonları |
| **Gold Bayi Sahibi** | `bayi@anadoluelektrik.test` | `Bayi123!` | %15 iskonto, ₺250.000 kredi limiti |
| **Satın Alma Sorumlusu** | `satinalma@anadoluelektrik.test` | `Bayi123!` | ₺15.000 sipariş onay limitli alt kullanıcı |
| **Onay Bekleyen Bayi** | `yeni@egeotomasyon.test` | `Bayi123!` | Evrak inceleme aşamasındaki bayi |

---

## 🛡️ Güvenlik ve Kod Kalitesi
- **Strict Types:** Tüm PHP dosyalarında `declare(strict_types=1);` standardı.
- **SQL Injection:** Tüm sorgularda PDO Prepared Statements parametre bağlama.
- **CSRF & XSS:** Tüm formlarda CSRF token doğrulaması ve `htmlspecialchars` kaçışlama.
- **Finansal Güvenilirlik:** Parasal hesaplamalarda `bcmath` aritmetiği.
- **Güvenli Dosya Depolama:** MIME-type ve uzantı doğrulamalı evrak yükleme mekanizması.

---

## 📄 Lisans
Bu proje [MIT Lisansı](LICENSE) kapsamında açık kaynak olarak sunulmaktadır.
