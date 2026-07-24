# SIAKAD - Sistem Informasi Akademik Perkuliahan (PHP 5 Compatible)

Aplikasi web Sistem Informasi Akademik (SIAKAD) sederhana namun fungsional lengkap, dibangun dari *scratch* dengan **PHP 5 murni** tanpa framework. Dirancang khusus untuk lingkungan server **PHP 5 (EOL)** yang belum bisa diupgrade.

---

## 🎓 Fitur Utama

| Fitur | Deskripsi | Role |
|-------|-----------|------|
| Login/Logout | Autentikasi session-based, role-based | Semua |
| Dashboard | Statistik ringkasan data akademik | Semua |
| CRUD Mahasiswa | Tambah, lihat, edit, hapus data mahasiswa | Admin |
| CRUD Dosen | Tambah, lihat, edit, hapus data dosen | Admin |
| CRUD Mata Kuliah | Kelola MK + assign dosen pengampu | Admin |
| KRS | Mahasiswa daftar MK, Admin approve/reject | Admin, Mahasiswa |
| Input Nilai | Dosen input tugas/UTS/UAS, auto-calc grade | Dosen |
| Transkrip / KHS | Rekap nilai + IPK otomatis | Mahasiswa |
| Feature Flags | Toggle fitur on/off tanpa ubah kode | Admin |
| Flash Messages | Notifikasi sukses/error/warning | Semua |

---

## 📁 Struktur Project

```
php5-demo-dd/
├── config.php              # Konfigurasi DB + Session
├── functions.php           # Helper: auth, flash, feature flags, validasi, query
├── index.php               # Front controller / Router
├── schema.sql              # Skema database + seeder data
├── style.css               # Vanilla CSS (Dark theme, responsive)
│
├── templates/
│   ├── header.php          # HTML head & meta
│   ├── topbar.php          # Top bar dengan judul halaman
│   ├── sidebar.php         # Navigasi sidebar (role-based)
│   └── footer.php          # Footer HTML
│
├── pages/
│   ├── login.php           # Halaman login
│   ├── logout.php          # Handler logout
│   ├── dashboard.php       # Dashboard statistik
│   ├── mahasiswa.php       # CRUD Mahasiswa
│   ├── dosen.php           # CRUD Dosen
│   ├── matakuliah.php      # CRUD Mata Kuliah
│   ├── krs.php             # Manajemen KRS
│   ├── nilai.php           # Input Nilai (Dosen)
│   ├── transkrip.php       # Transkrip / KHS (Mahasiswa)
│   └── settings.php        # Feature Flags (Admin)
│
└── README.md
```

---

## 🚀 Panduan Setup & Instalasi

### 1. Persiapan Database MySQL

Masuk ke MySQL CLI dan eksekusi file `schema.sql`:

```bash
sudo mysql < /path/to/schema.sql
```

Atau copy-paste isi `schema.sql` ke phpMyAdmin / MySQL CLI secara manual.

### 2. Buat User Database

```sql
CREATE USER IF NOT EXISTS 'user_inquiry'@'localhost' IDENTIFIED WITH mysql_native_password BY 'password_rahasia_123';
GRANT ALL PRIVILEGES ON db_inquiry.* TO 'user_inquiry'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Konfigurasi (`config.php`)

Sesuaikan kredensial database di `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'user_inquiry');
define('DB_PASS', 'password_rahasia_123');
define('DB_NAME', 'db_inquiry');
```

### 4. Deploy ke Web Server

**Apache (Ubuntu/XAMPP):**
```bash
# Clone ke web root
cd /var/www/html
sudo git clone https://github.com/avecenabasuni/php5-demo-dd.git
sudo chown -R www-data:www-data php5-demo-dd
```

Akses via browser: `http://<IP_SERVER>/php5-demo-dd/`

### 5. Akun Default (Seeder)

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| Dosen | `dosen1` | `dosen123` |
| Dosen | `dosen2` | `dosen123` |
| Mahasiswa | `mhs1` | `mhs123` |
| Mahasiswa | `mhs2` | `mhs123` |
| Mahasiswa | `mhs3` | `mhs123` |

---

## ⚙️ Feature Flags

Feature Flags memungkinkan admin mengontrol fitur **tanpa mengubah kode sumber**:

| Flag Name | Default | Fungsi |
|-----------|---------|--------|
| `krs_registration` | ON | Membuka/menutup pendaftaran KRS mahasiswa |
| `grade_input` | ON | Mengaktifkan/menonaktifkan input nilai dosen |
| `student_registration` | ON | Membuka/menutup registrasi mahasiswa baru |
| `show_transcript` | ON | Menampilkan/menyembunyikan transkrip mahasiswa |

Pengecekan dilakukan via fungsi `is_feature_enabled($conn, 'flag_name')` yang membaca tabel `feature_flags` di database.

---

## 🧠 Kompatibilitas PHP 5 (Strict Compliance)

| Aturan | Implementasi |
|--------|-------------|
| Tidak pakai `[]` | Menggunakan `array()` di semua tempat |
| Tidak pakai `??` | Menggunakan `isset($v) ? $v : $d` |
| Tidak pakai type hints | Fungsi ditulis `function foo($a)` tanpa type declaration |
| Procedural `mysqli_*` | `mysqli_connect()`, `mysqli_query()`, `mysqli_real_escape_string()` |
| XSS Protection | `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')` tanpa `ENT_HTML5` |
| SQL Injection Protection | `mysqli_real_escape_string()` untuk semua input sebelum query |
| Session Management | `session_start()` dengan `@` suppressor (kompatibel PHP 5.0-5.3) |
| Password Hashing | `md5()` (kompatibel semua PHP 5; untuk PHP 5.5+ gunakan `password_hash()`) |

---

## 🔒 Keamanan

1. **Anti SQL Injection** — Semua input di-escape dengan `mysqli_real_escape_string()` sebelum masuk query.
2. **Anti XSS** — Semua output di-escape dengan `htmlspecialchars(ENT_QUOTES)`.
3. **Role-Based Access** — Fungsi `require_role()` memaksa pengecekan role sebelum akses halaman.
4. **Feature Flags** — Admin bisa menonaktifkan fitur sensitif (KRS, input nilai) tanpa downtime.
5. **Flash Messages** — Error dan notifikasi ditampilkan sekali via session, tidak persistent.

---

## 📊 Kalkulasi Akademik

- **Nilai Akhir** = (Tugas × 30%) + (UTS × 30%) + (UAS × 40%)
- **Grade**: A (≥80), B (≥70), C (≥60), D (≥50), E (<50)
- **Bobot**: A=4.0, B=3.0, C=2.0, D=1.0, E=0.0
- **IPK** = Σ(bobot × SKS) / Σ(SKS)
