# AbsenKi

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/Livewire-3-FB70A9?style=for-the-badge&logo=livewire&logoColor=white" alt="Livewire">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/TailwindCSS-4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind">
</p>

Sistem absensi karyawan modern berbasis web dengan teknologi **geofencing** dan **pengenalan wajah**, mengintegrasikan OpenCV untuk deteksi liveness dan AWS Rekognition untuk verifikasi identitas.

## 🎯 Fitur Utama

### Verifikasi Triple-Layer

- **🌍 Geofencing** - Validasi lokasi GPS dengan polygon area kantor menggunakan Turf.js
- **👤 Liveness Detection** - Deteksi wajah asli (bukan foto) menggunakan OpenCV
- **✅ Face Recognition** - Verifikasi identitas dengan AWS Rekognition (similarity matching)

### Untuk Admin

- ✨ Manajemen akun staff (CRUD)
- 📸 Pendaftaran wajah staff (upload 3-5 foto per staff)
- 🗺️ Kelola area geofence (polygon kantor)
- 📊 Laporan absensi lengkap dengan filter & export (CSV/PDF)
- 📈 Dashboard statistik kehadiran
- 🔔 Monitoring absensi real-time

### Untuk Staff

- ⏰ Absen masuk/keluar dengan kamera & GPS
- 📱 Akses via browser (mobile & desktop)
- 📜 Riwayat absensi pribadi
- 🖼️ Bukti foto setiap absensi

## 🛠️ Tech Stack

### Backend

- **Framework**: Laravel 12
- **Real-time UI**: Livewire 3 + Volt
- **Authentication**: Laravel Fortify
- **Database**: MySQL
- **PDF Generation**: DomPDF

### Frontend

- **CSS Framework**: Tailwind CSS 4
- **Build Tool**: Vite 7
- **UI Components**: Livewire Flux
- **Maps**: Leaflet.js
- **Geofencing**: Turf.js
- **HTTP Client**: Axios

### AI/ML & Computer Vision

- **Liveness Detection**: OpenCV (Python service)
- **Face Recognition**: AWS Rekognition
- **Image Processing**: OpenCV face cropping & quality validation

### Development Tools

- **Code Quality**: Laravel Pint, PHPStan (Larastan)
- **Testing**: Pest PHP
- **IDE Helper**: Laravel IDE Helper
- **Logging**: Laravel Pail

## 📋 Requirements

- PHP >= 8.2
- Composer
- Node.js >= 18.x & NPM
- MySQL >= 8.0
- Python >= 3.8 (untuk OpenCV service)
- AWS Account (untuk Rekognition)
- HTTPS (wajib untuk akses kamera & GPS di browser)

### Python Dependencies

```bash
pip install opencv-python numpy boto3
```

## 🚀 Installation

### 1. Clone Repository

```bash
git clone https://github.com/username/absenki.git
cd absenki
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Database

Edit file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absenki
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Configure AWS Rekognition

Edit file `.env`:

```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_REKOGNITION_COLLECTION=staff_collection_name
```

**Catatan**: Buat Collection di AWS Rekognition terlebih dahulu:

```bash
aws rekognition create-collection --collection-id staff_collection_name --region us-east-1
```

### 6. Run Migrations & Seeders

```bash
php artisan migrate --seed
```

### 7. Storage Link

```bash
php artisan storage:link
```

### 8. Build Assets

```bash
# Development
npm run dev

# Production
npm run build
```

### 9. Start Development Server

```bash
php artisan serve
```

Aplikasi akan berjalan di `http://localhost:8000`

## ⚙️ Configuration

### Konfigurasi Face Recognition

Edit threshold kemiripan wajah di `config/services.php`:

```php
'rekognition' => [
    'similarity_threshold' => 80, // 0-100
],
```

### Konfigurasi Geofencing

Geofence dikelola melalui admin panel dengan format GeoJSON polygon.

### Python Service Configuration

Pastikan Python service untuk OpenCV berjalan:

```bash
cd tools
python opencv_face_crop.py
```

## 📖 Usage

### Default Credentials

Setelah seeding, gunakan kredensial berikut:

**Admin:**

- Email: `admin@absenki.test`
- Password: `password`

**Staff:**

- Email: `staff@absenki.test`
- Password: `password`

### Workflow Admin

1. **Tambah Staff Baru**
    - Login sebagai admin
    - Buka menu Staff Management
    - Tambah data staff baru

2. **Daftarkan Wajah Staff**
    - Pilih staff dari daftar
    - Upload 3-5 foto wajah dari berbagai sudut
    - Sistem akan proses dengan OpenCV dan AWS Rekognition
    - FaceId akan tersimpan otomatis

3. **Buat Geofence**
    - Buka menu Geofence Management
    - Gambar polygon area kantor di peta
    - Simpan area geofence

4. **Monitor Absensi**
    - Dashboard menampilkan statistik real-time
    - Lihat laporan dengan filter tanggal & staff
    - Export ke CSV atau PDF

### Workflow Staff

1. **Absen Masuk/Keluar**
    - Login ke sistem
    - Klik tombol "Absen Masuk" atau "Absen Keluar"
    - Izinkan akses kamera dan lokasi
    - Posisikan wajah di frame
    - Sistem akan otomatis verifikasi:
        - ✅ Lokasi GPS dalam area kantor
        - ✅ Wajah hidup terdeteksi (bukan foto)
        - ✅ Identitas cocok dengan database
    - Absensi berhasil dicatat

2. **Lihat Riwayat**
    - Akses menu "Riwayat Absensi"
    - Filter berdasarkan tanggal
    - Lihat detail setiap absensi

## 📂 Project Structure

```
AbsenKi/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # Controllers
│   │   └── Middleware/        # Custom middleware
│   ├── Livewire/              # Livewire components
│   │   ├── Admin/             # Admin components
│   │   └── Staff/             # Staff components
│   ├── Models/                # Eloquent models
│   └── Services/              # Business logic services
├── config/                    # Configuration files
├── database/
│   ├── migrations/            # Database migrations
│   └── seeders/               # Database seeders
├── docs/                      # Documentation
├── public/                    # Public assets
├── resources/
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript files
│   └── views/                 # Blade templates
├── routes/                    # Route definitions
├── storage/                   # Storage (logs, uploads)
├── tests/                     # Pest PHP tests
└── tools/                     # Python OpenCV tools
```

## 📚 Documentation

Dokumentasi lengkap tersedia di folder [`docs/`](docs/):

- [📖 Penjelasan Sistem](docs/Penjelasan.md) - Overview lengkap sistem
- [🎯 Use Case Diagram](docs/Use_Case_Diagram.md) - Diagram use case
- [📍 GPS Location Guide](docs/GPS_Location_Guide.md) - Panduan GPS & geofencing
- [🔧 Python-PHP Integration](docs/Python_PHP_Integration.md) - Integrasi OpenCV
- [👤 Face Cropping Integration](docs/Face_Cropping_Integration.md) - Implementasi face detection
- [⚡ Geofence Performance](docs/Geofence_Performance_Optimization.md) - Optimasi performa
- [🎨 Dashboard Admin](docs/Dashboard_Admin.md) - Fitur dashboard
- [🚀 Story Implementation](docs/) - User stories & implementasi

## 🔒 Security

- ✅ HTTPS wajib untuk production (akses kamera & GPS)
- ✅ CSRF protection (Laravel default)
- ✅ XSS protection
- ✅ Rate limiting pada endpoint absensi
- ✅ AWS IAM dengan minimal permission (hanya Rekognition)
- ✅ Face images tidak disimpan di S3 (hanya Bytes processing)
- ✅ Geofencing validation di client & server side

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter AttendanceTest

# Run with coverage
php artisan test --coverage
```

## 🎨 Code Quality

```bash
# Format code (Laravel Pint)
./vendor/bin/pint

# Static analysis (PHPStan)
./vendor/bin/phpstan analyse

# IDE Helper
php artisan ide-helper:generate
php artisan ide-helper:models
```

## 🚀 Deployment

### Production Checklist

- [ ] Set `APP_ENV=production` di `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate production key: `php artisan key:generate`
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Build assets: `npm run build`
- [ ] Setup queue worker untuk AWS calls
- [ ] Configure HTTPS/SSL certificate
- [ ] Setup scheduled tasks (cron)
- [ ] Configure Python service as daemon
- [ ] Setup backup strategy

### Optimization

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

## 🤝 Contributing

Contributions are welcome! Silakan:

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 👥 Authors

- **Your Name** - _Initial work_ - [YourGitHub](https://github.com/Man4c)

## 🙏 Acknowledgments

- Laravel Team untuk framework yang luar biasa
- Livewire Team untuk reactive components
- AWS untuk Rekognition service
- OpenCV Community untuk computer vision tools
- Turf.js untuk geospatial analysis

## 📞 Support

Jika Anda mengalami masalah atau memiliki pertanyaan:

- 🐛 [Open an issue](https://github.com/Man4c/absenki/issues)
- 📧 Email: your.email@domain.com
- 💬 Discussion: [GitHub Discussions](https://github.com/Man4c/absenki/discussions)

---

<p align="center">Made with ❤️ for modern workforce management</p>
