<?php
/**
 * Konfigurasi Database & Session - SIAKAD PHP 5 Compatible
 *
 * KOMPATIBILITAS PHP 5:
 * - session_start() tersedia sejak PHP 4.0
 * - define() untuk konstanta (standar PHP 4/5/7/8)
 * - mysqli_connect() procedural (PHP 5.0+)
 * - Tidak menggunakan session_status() karena baru ada di PHP 5.4
 *   → Menggunakan pengecekan $_SESSION sebagai gantinya
 */

// Mulai session (harus dipanggil sebelum output HTML apapun)
// PHP 5: Tidak menggunakan session_status() yang baru ada di PHP 5.4
// Cukup panggil session_start() langsung; jika sudah aktif, PHP 5 akan memberi notice saja
@session_start();

// Konfigurasi Aplikasi
define('APP_NAME', 'SIAKAD');
define('APP_VERSION', '1.0.0');

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'user_inquiry');
define('DB_PASS', 'password_rahasia_123');
define('DB_NAME', 'db_inquiry');
define('DB_PORT', 3306);

// Membuka koneksi MySQLi secara procedural
$db_conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Memeriksa status koneksi
if (!$db_conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set karakter encoding ke UTF-8
mysqli_set_charset($db_conn, "utf8");
