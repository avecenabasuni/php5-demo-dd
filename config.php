<?php
/**
 * Konfigurasi Database - PHP 5 Compatible
 * 
 * ALASAN KOMPATIBILITAS PHP 5:
 * 1. Menggunakan `define()` untuk konstanta (standar PHP 4/5/7/8).
 * 2. Menggunakan fungsi procedural `mysqli_connect()` yang diperkenalkan sejak PHP 5.0.0.
 * 3. Tidak menggunakan Class/OOP atau Namespace agar kompatibel penuh dengan lingkungan procedural PHP 5.
 */

// Hindari akses langsung ke file konfigurasi jika perlu (opsional)
// Pengesetan kredensial database
define('DB_HOST', 'localhost');
define('DB_USER', 'user_inquiry');
define('DB_PASS', 'password_rahasia_123');
define('DB_NAME', 'db_inquiry');
define('DB_PORT', 3306);

// Membuka koneksi MySQLi secara procedural
$db_conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Memeriksa status koneksi
if (!$db_conn) {
    // Fungsi mysqli_connect_error() tersedia di PHP 5.0.0+
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set karakter encoding ke UTF-8 (mysqli_set_charset tersedia sejak PHP 5.0.5)
mysqli_set_charset($db_conn, "utf8");
