# SIAKAD - Sistem Informasi Akademik Perkuliahan (PHP 5 Compatible)

Aplikasi web Sistem Informasi Akademik (SIAKAD) sederhana namun fungsional lengkap, dibangun dari *scratch* dengan **PHP 5 murni** tanpa framework. Dilengkapi dengan **Datadog monitoring** dan **error simulator** untuk demo observability.

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
| **Demo Errors** | **Trigger error untuk demo Datadog** | **Admin** |
| Flash Messages | Notifikasi sukses/error/warning | Semua |

---

## 📁 Struktur Project

```
php5-demo-dd/
├── config.php              # Konfigurasi DB + Session + Logger
├── functions.php           # Helper: auth, flash, feature flags, validasi, query + Datadog logging
├── logger.php              # 🆕 Structured JSON Logger + DogStatsD Client
├── index.php               # Front controller / Router
├── schema.sql              # Skema database + seeder data
├── style.css               # Vanilla CSS (Dark theme, responsive)
├── .gitignore              # Ignore log files
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
│   ├── settings.php        # Feature Flags (Admin)
│   └── demo_errors.php     # 🆕 Error Simulator (Datadog Demo)
│
├── logs/
│   └── .gitkeep            # Direktori log (di-ignore oleh git)
│
└── README.md
```

---

## 🚀 Panduan Setup & Instalasi

### 1. Persiapan Database MySQL

```bash
sudo mysql < /path/to/schema.sql
```

### 2. Buat User Database

```sql
CREATE USER IF NOT EXISTS 'user_inquiry'@'localhost' IDENTIFIED WITH mysql_native_password BY 'password_rahasia_123';
GRANT ALL PRIVILEGES ON db_inquiry.* TO 'user_inquiry'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Deploy ke Web Server

```bash
cd /var/www/html
sudo git clone https://github.com/avecenabasuni/php5-demo-dd.git
sudo chown -R www-data:www-data php5-demo-dd
sudo mkdir -p /var/log/siakad
sudo chown www-data:www-data /var/log/siakad
```

### 4. Akun Default (Seeder)

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| Dosen | `dosen1` | `dosen123` |
| Dosen | `dosen2` | `dosen123` |
| Mahasiswa | `mhs1` | `mhs123` |
| Mahasiswa | `mhs2` | `mhs123` |
| Mahasiswa | `mhs3` | `mhs123` |

---

## 📊 Datadog Integration

### Arsitektur Monitoring

```
┌─────────────────────────────────────────────────────┐
│  VM Ubuntu 22 (PHP 5.6 + Apache + MySQL)            │
│                                                     │
│  ┌─────────┐  ┌──────────────┐  ┌───────────────┐  │
│  │ SIAKAD  │→ │ JSON Logs    │→ │ Datadog Agent │──│──→ Datadog Cloud
│  │ PHP App │→ │ /var/log/    │  │ (dd-agent)    │  │
│  │         │→ │ siakad/      │  └───────┬───────┘  │
│  └────┬────┘  └──────────────┘          │           │
│       │                                 │           │
│       ↓ (UDP:8125)                      ↓           │
│  ┌──────────┐              ┌──────────────────┐     │
│  │DogStatsD │              │ Apache + MySQL   │     │
│  │ Metrics  │              │ Integrations     │     │
│  └──────────┘              └──────────────────┘     │
└─────────────────────────────────────────────────────┘
```

---

## 🧪 Demo Error Simulator

Halaman **Demo Errors** (`?page=demo_errors`) menyediakan 10 skenario error yang bisa di-trigger satu per satu:

| # | Error Type | Apa yang Terjadi | Di Datadog |
|---|------------|-----------------|------------|
| 1 | **PHP Notice** | Undefined variable | WARNING log |
| 2 | **PHP Warning** | Division by zero | WARNING log |
| 3 | **PHP User Error** | trigger_error() | ERROR log (halts script) |
| 4 | **Exception** | throw/catch Exception | CRITICAL log + stack trace |
| 5 | **Database Error** | Query invalid table | ERROR log + MySQL error |
| 6 | **Slow Query** | SELECT SLEEP(3) | WARNING log + timing metric (3000ms) |
| 7 | **Memory Spike** | 100K string allocations | Gauge metric spike |
| 8 | **Auth Failure** | 5x failed login burst | Security alert + counter spike |
| 9 | **Feature Flag** | Access disabled feature | WARNING log + blocked counter |
| 10 | **App Errors** | Business logic failures | ERROR logs (3 scenarios) |

### Cara Demo:
1. Login sebagai `admin` / `admin123`
2. Klik **🧪 Demo Errors** di sidebar
3. Trigger error satu per satu → cek di Datadog Logs + Metrics
4. Gunakan **Feature Flags** untuk menonaktifkan fitur → trigger Flag Block

---

## 📈 Custom Metrics (DogStatsD)

Metrics yang dikirim via UDP ke Datadog Agent port 8125:

| Metric | Type | Kapan |
|--------|------|-------|
| `siakad.login.attempt` | Counter | Setiap login attempt |
| `siakad.login.success` | Counter | Login berhasil |
| `siakad.login.failure` | Counter | Login gagal |
| `siakad.error.count` | Counter | Setiap PHP error |
| `siakad.error.exception` | Counter | Exception caught |
| `siakad.error.business_logic` | Counter | Business logic error |
| `siakad.db.query_time` | Timing | Waktu eksekusi query |
| `siakad.db.error` | Counter | Database query gagal |
| `siakad.page.load_time` | Timing | Response time per halaman |
| `siakad.feature_flag.blocked` | Counter | Akses fitur yang disabled |
| `siakad.demo.error_triggered` | Counter | Error simulator triggered |
| `siakad.memory.usage_bytes` | Gauge | Memory usage saat spike |

---

## 📝 Structured Logging

Setiap log entry adalah **JSON** satu baris, siap di-parse Datadog:

```json
{
  "timestamp": "2024-01-15T10:30:00Z",
  "level": "ERROR",
  "message": "Database query failed",
  "service": "siakad",
  "env": "demo",
  "version": "1.0.0",
  "request": {
    "method": "GET",
    "uri": "/php5-demo-dd/index.php?page=demo_errors&error=db_error",
    "ip": "192.168.1.100"
  },
  "usr": {
    "id": 1,
    "name": "Administrator",
    "role": "admin"
  },
  "context": {
    "query": "SELECT * FROM tabel_yang_tidak_ada",
    "error": "Table 'db_inquiry.tabel_yang_tidak_ada' doesn't exist"
  }
}
```

**Auto-logged events:**
- Request start/end (dengan response time)
- Login success/failure
- Database errors & slow queries (> 500ms)
- Feature flag checks
- PHP errors (Notice, Warning, Fatal)
- Fatal errors via shutdown handler

---

## ⚙️ Feature Flags

| Flag Name | Default | Fungsi |
|-----------|---------|--------|
| `krs_registration` | ON | Buka/tutup pendaftaran KRS mahasiswa |
| `grade_input` | ON | Aktif/nonaktifkan input nilai dosen |
| `student_registration` | ON | Buka/tutup registrasi mahasiswa baru |
| `show_transcript` | ON | Tampil/sembunyikan transkrip mahasiswa |

---

## 🧠 Kompatibilitas PHP 5

| Aturan | Implementasi |
|--------|-------------|
| Tidak pakai `[]` | `array()` |
| Tidak pakai `??` | `isset($v) ? $v : $d` |
| Procedural `mysqli_*` | `mysqli_connect()`, `mysqli_query()` |
| XSS Protection | `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')` |
| SQL Injection | `mysqli_real_escape_string()` |
| Password Hashing | `md5()` (PHP 5 compatible) |

---

## 📊 Kalkulasi Akademik

- **Nilai Akhir** = (Tugas × 30%) + (UTS × 30%) + (UAS × 40%)
- **Grade**: A (≥80), B (≥70), C (≥60), D (≥50), E (<50)
- **IPK** = Σ(bobot × SKS) / Σ(SKS)
