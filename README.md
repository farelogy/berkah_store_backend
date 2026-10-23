# Berkah Store v2.0 - Backend RESTful API (Laravel 11)

Backend RESTful API untuk sistem POS, Inventori Multi-Cabang, Manajemen Piutang & Dashboard Eksekutif Toko Bangunan Berkah Store.

---

## 🛠️ Persyaratan Server / Hosting
- **PHP**: `^8.2` (dengan ekstensi `pdo_mysql`, `openssl`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `bcmath`)
- **Database**: MySQL 8.0+ atau MariaDB 10.5+
- **Composer**: `^2.2`

---

## 🚀 Panduan Setup & Deploy ke Hosting via GitHub

### 1. Inisialisasi Git & Push ke GitHub Repo Anda
Di laptop Anda, masuk ke folder `backend`:
```bash
cd "/Volumes/MyMac/My Work/AI Project/berkah store/backend"
git init
git add .
git commit -m "feat: initial foundation backend laravel 11 berkah store v2.0"
git branch -M main
git remote add origin https://github.com/USERNAME_ANDA/NAMA_REPO_BACKEND.git
git push -u origin main
```

### 2. Clone / Sync di Server Hosting / cPanel / VPS
Di terminal hosting/VPS Anda:
```bash
# Clone repository
git clone https://github.com/USERNAME_ANDA/NAMA_REPO_BACKEND.git /path/to/public_html/api

# Masuk ke folder proyek
cd /path/to/public_html/api

# Install dependensi PHP
composer install --no-dev --optimize-autoloader

# Konfigurasi file .env
cp .env.example .env
nano .env  # atau edit via File Manager cPanel
```

### 3. Konfigurasi Database & Google Client ID di `.env`
Buka file `.env` di server dan sesuaikan:
```ini
APP_NAME="Berkah Store API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://apistore.farelogy.my.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_hosting
DB_USERNAME=user_database_hosting
DB_PASSWORD=password_database_hosting

# Google OAuth 2.0 Web Client ID
GOOGLE_CLIENT_ID=xxxxxxxxxxxx.apps.googleusercontent.com
```

### 4. Generate Key, Migrasi Database & Seeder Awal
Jalankan perintah ini di hosting/VPS:
```bash
# Generate APP_KEY
php artisan key:generate

# Jalankan 12 Migrasi Lengkap (Membuat seluruh 26 tabel database baru)
php artisan migrate --force

# Jalankan Seeder Awal (Membuat Kas Pusat & Cabang Utama Pertama)
php artisan db:seed --force
```

### 5. Inisialisasi Super-Admin Pertama (1 Perintah Saja!)
Jadikan akun Gmail Anda sebagai Super-Admin pertama:
```bash
php artisan make:admin emailanda@gmail.com
```
*Selesai! Akun Anda langsung aktif sebagai Super-Admin tanpa perlu buka/edit tabel database secara manual.*

---

## 📋 Struktur Route API v1 (`routes/api.php`)

| Method | Endpoint | Hak Akses | Deskripsi |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/auth/google` | Publik | Login / Registrasi otomatis via Google ID Token |
| `GET` | `/api/v1/auth/me` | Logged In | Cek status sesi profil, role, & cabang penugasan |
| `POST` | `/api/v1/auth/logout` | Logged In | Logout & revoke Bearer token Sanctum |
| `GET` | `/api/v1/admin/users` | Admin Pusat | Melihat daftar seluruh pengguna (filter: pending approval) |
| `PUT` | `/api/v1/admin/users/{id}/role` | Admin Pusat | Menetapkan Role (`admin`, `cabang`, `direksi`) & Cabang Penugasan |
| `POST` | `/api/v1/admin/users/{id}/toggle-active` | Admin Pusat | Mengaktifkan / Menonaktifkan akun user |

---

## 🔒 Keamanan & Hak Akses (RBAC)
- **Role Middleware**: `role:admin`, `role:cabang`, `role:direksi`.
- **Branch Scope Middleware**: `branch.scope` memastikan kasir cabang hanya dapat mengakses data cabangnya sendiri.
- **Akun Baru**: Otomatis berstatus `pending` sampai disetujui Admin Pusat.
