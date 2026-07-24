# Aplikasi Landing Page & Form Inquiry (PHP 5 Compatible)

Aplikasi web PHP 5 dari *scratch* yang dirancang khusus untuk memenuhi kebutuhan lingkungan server **PHP 5 (EOL)** tanpa dependensi modern PHP 7+/8+. Aplikasi ini menyediakan *landing page* interaktif dengan formulir kontak/inquiry yang dilengkapi validasi *server-side*, proteksi keamanan (SQL Injection & XSS), serta penyimpanan data ke MySQL secara procedural.

---

## 📁 Struktur Project

```text
php5-demo-dd/
├── schema.sql       # Skema database MySQL (tabel inquiries)
├── config.php       # Konfigurasi koneksi MySQLi procedural
├── functions.php    # Fungsi utilitas, sanitasi, validasi, & query database
├── style.css        # Custom Vanilla CSS (Modern & Responsif)
├── index.php        # Landing page & form inquiry (Sticky Form)
├── thanks.php       # Halaman konfirmasi sukses pengiriman
└── README.md        # Dokumentasi & panduan setup lengkap
```

---

## 🚀 Panduan Setup & Instalasi

### 1. Persiapan Database MySQL
1. Buka MySQL Client (melalui phpMyAdmin, MySQL CLI, atau DBeaver).
2. Eksekusi perintah di dalam file `schema.sql` untuk membuat database dan tabel:
   ```sql
   CREATE DATABASE IF NOT EXISTS `db_inquiry` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
   USE `db_inquiry`;

   CREATE TABLE IF NOT EXISTS `inquiries` (
       `id` INT(11) NOT NULL AUTO_INCREMENT,
       `nama` VARCHAR(100) NOT NULL,
       `email` VARCHAR(100) NOT NULL,
       `telepon` VARCHAR(20) NOT NULL,
       `pesan` TEXT NOT NULL,
       `created_at` DATETIME NOT NULL,
       PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
   ```

### 2. Konfigurasi Database (`config.php`)
Buka file `config.php` dan sesuaikan kredensial server MySQL Anda:
```php
define('DB_HOST', 'localhost'); // Host database
define('DB_USER', 'root');      // Username database
define('DB_PASS', '');          // Password database
define('DB_NAME', 'db_inquiry'); // Nama database
define('DB_PORT', 3306);        // Port MySQL (default: 3306)
```

### 3. Menjalankan di Server PHP 5

#### A. Menggunakan XAMPP / WAMP / Lamp (PHP 5.x)
1. Salin seluruh direktori project `php5-demo-dd` ke folder web root server:
   - XAMPP: `C:\xampp\htdocs\php5-demo-dd`
   - Linux Apache: `/var/www/html/php5-demo-dd`
2. Buka peramban (browser) dan akses:
   `http://localhost/php5-demo-dd/`

#### B. Menggunakan PHP Built-in Web Server (PHP 5.4.0+)
Jika menggunakan PHP 5.4+, Anda dapat menjalankan web server internal melalui terminal di dalam folder project:
```bash
php -S localhost:8000
```
Lalu buka browser di `http://localhost:8000/`.

---

## 🧠 Penjelasan Kompatibilitas PHP 5 (Aturan & Sintaks)

Setiap bagian kode telah disesuaikan agar **100% aman dijalankan di PHP 5.0 - 5.6** tanpa menyebabkan *Parse Error* atau *Fatal Error*. Berikut penjelasan teknisnya:

### 1. Menggunakan Syntax `array()`, BUKAN Short Array Syntax `[]`
- **Tantangan:** Pada PHP 7+, pengembang terbiasa menulis `$data = [];`. Namun, *short array syntax* `[]` baru diperkenalkan pada **PHP 5.4**. Jika server customer menggunakan PHP 5.0 - 5.3, sintaks `[]` akan menyebabkan `Parse error: syntax error, unexpected '['`.
- **Solusi Kode:** Seluruh array di `functions.php` dan `index.php` ditulis menggunakan sintaks tradisional `array()`.
  ```php
  // ✅ Kompatibel PHP 5 (Semua versi PHP 5)
  $errors = array();
  $form_data = array('nama' => '', 'email' => '');
  ```

### 2. Menggunakan Ternary Operator `isset() ? :`, BUKAN Null Coalescing Operator `??`
- **Tantangan:** Null coalescing operator `$val = $_POST['nama'] ?? '';` baru ada mulai **PHP 7.0**. Di PHP 5, operator `??` akan menyebabkan fatal parse error.
- **Solusi Kode:** Menggunakan kombinasi `isset()` dengan ternary operator.
  ```php
  // ✅ Kompatibel PHP 5
  $nama = isset($_POST['nama']) ? $_POST['nama'] : '';
  ```

### 3. Menggunakan Fungsi Procedural `mysqli_*`, BUKAN PDO atau OOP MySQLi
- **Tantangan:** Lingkungan server legacy terkadang tidak mengaktifkan ekstensi PDO atau kelas OOP tertentu, dan menghindari `namespace` (yang baru stabil di PHP 5.3).
- **Solusi Kode:** Menggunakan `mysqli_connect()`, `mysqli_query()`, `mysqli_real_escape_string()`, `mysqli_connect_error()`, dan `mysqli_set_charset()` secara procedural murni tanpa klausa `namespace`.
  ```php
  $db_conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
  $nama_clean = mysqli_real_escape_string($conn, $nama);
  ```

### 4. Bebas dari Type Hinting (Scalar Type Hints & Return Type Declarations)
- **Tantangan:** Penulisan fungsi seperti `function escape_html(string $data): string` akan menghasilkan *Fatal Error* di PHP 5 karena PHP 5 tidak mendukung *scalar type hint* (`string`, `int`, `bool`) dan *return type declaration* (`: string`).
- **Solusi Kode:** Deklarasi fungsi di `functions.php` ditulis tanpa type hint, dan kasting tipe data dilakukan di dalam fungsi jika diperlukan.
  ```php
  // ✅ Kompatibel PHP 5
  function escape_html($data) {
      return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
  }
  ```

### 5. Escaping Output dengan `htmlspecialchars()` Menggunakan Flag `ENT_QUOTES` Saja
- **Tantangan:** Flag `ENT_HTML5` baru ditambahkan pada **PHP 5.4**.
- **Solusi Kode:** Menggunakan `ENT_QUOTES` saja untuk mengubah tanda petik ganda (`"`) dan tunggal (`'`) menjadi HTML entity (`&quot;` dan `&#039;`), yang merupakan standar keamanan XSS paling universal di seluruh PHP 5.x.

### 6. Sanitasi Input (SQL Injection Protection) & Validasi Server-Side
- **Keamanan SQL:** Semua parameter query sebelum di-insert dimasukkan ke fungsi `mysqli_real_escape_string($conn, $val)` untuk meng-escape karakter berbahaya seperti `'`, `"`, `\`, dan `;`.
- **Validasi Format Email:** Menggunakan `filter_var($email, FILTER_VALIDATE_EMAIL)` yang sudah tersedia sejak PHP 5.2.0.
- **Validasi Format Telepon:** Menggunakan `preg_match('/^[0-9]+$/', $telepon)` yang memastikan nomor telepon murni berupa angka.
- **Sticky Form:** Nilai form yang gagal divalidasi akan dikembalikan ke form input melalui `escape_html($form_data['key'])`, sehingga input pengguna tidak hilang dan halaman tidak di-reload kosong dari awal.

---

## 🔒 Hasil Uji Keamanan

1. **Anti-SQL Injection:** Pengujian input seperti `' OR '1'='1` akan di-escape menjadi `\' OR \'1\'=\'1` oleh `mysqli_real_escape_string()`, sehingga diperlakukan murni sebagai string teks biasa.
2. **Anti-XSS:** Pengujian input seperti `<script>alert('xss')</script>` akan di-encode menjadi `&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;` oleh `escape_html()`, sehingga tidak akan dieksekusi oleh peramban.
