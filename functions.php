<?php
/**
 * Fungsi Utility & Validasi Form - PHP 5 Compatible
 * 
 * ALASAN KOMPATIBILITAS PHP 5:
 * 1. Menggunakan `array()` untuk array, BUKAN short array syntax `[]` (PHP 5.4+).
 * 2. Menggunakan `isset($var) ? $var : $default` untuk pemeriksaan nilai, BUKAN null coalescing operator `??` (PHP 7.0+).
 * 3. Tanpa type hints (seperti `string $data`) atau return type hints (`: string`), karena fitur ini baru ada di PHP 7+.
 * 4. Sanitasi HTML menggunakan `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`. ENT_QUOTES kompatibel di semua PHP 5 (tanpa ENT_HTML5).
 * 5. Menggunakan `filter_var()` dengan `FILTER_VALIDATE_EMAIL` yang tersedia sejak PHP 5.2.0.
 * 6. Menggunakan `mysqli_real_escape_string()` procedural untuk mencegah SQL Injection.
 */

/**
 * Mencegah serangan Cross-Site Scripting (XSS) saat menampilkan data ke HTML
 *
 * @param string $data
 * @return string
 */
function escape_html($data) {
    if ($data === null) {
        return '';
    }
    // ENT_QUOTES meng-escape tanda petik tunggal (') dan ganda (")
    // Diperoleh keamanan maksimal tanpa perlu flag PHP 5.4+ seperti ENT_HTML5
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

/**
 * Membersihkan input string dari whitespace berlebih
 *
 * @param string $data
 * @return string
 */
function sanitize_input($data) {
    if (!isset($data) || $data === null) {
        return '';
    }
    $data = trim((string)$data);
    return $data;
}

/**
 * Validasi input form inquiry
 *
 * @param array $post_data
 * @return array Berisi pesan kesalahan jika validasi gagal
 */
function validate_inquiry($post_data) {
    // PHP 5: Harus menggunakan array(), bukan []
    $errors = array();

    // Mengambil dan membersihkan input
    // PHP 5: Menggunakan ternary operator isset() ? : karena ?? baru ada di PHP 7.0
    $nama    = isset($post_data['nama'])    ? sanitize_input($post_data['nama'])    : '';
    $email   = isset($post_data['email'])   ? sanitize_input($post_data['email'])   : '';
    $telepon = isset($post_data['telepon']) ? sanitize_input($post_data['telepon']) : '';
    $pesan   = isset($post_data['pesan'])   ? sanitize_input($post_data['pesan'])   : '';

    // Validasi Field Nama
    if ($nama === '') {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    }

    // Validasi Field Email
    if ($email === '') {
        $errors['email'] = 'Alamat email wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // filter_var() dengan FILTER_VALIDATE_EMAIL tersedia sejak PHP 5.2.0
        $errors['email'] = 'Format email tidak valid. Contoh: nama@domain.com';
    }

    // Validasi Field Telepon (Hanya Angka)
    if ($telepon === '') {
        $errors['telepon'] = 'Nomor telepon wajib diisi.';
    } elseif (!preg_match('/^[0-9]+$/', $telepon)) {
        // ctype_digit atau preg_match digit murni kompatibel sejak PHP 4/5
        $errors['telepon'] = 'Nomor telepon hanya boleh berisi angka (tanpa spasi/simbol).';
    }

    // Validasi Field Pesan
    if ($pesan === '') {
        $errors['pesan'] = 'Pesan inquiry wajib diisi.';
    }

    return $errors;
}

/**
 * Menyimpan data inquiry ke database MySQL
 *
 * @param resource|object $conn Object/Resource koneksi mysqli
 * @param array $data Data yang sudah diproses
 * @return boolean True jika berhasil, False jika gagal
 */
function save_inquiry($conn, $data) {
    // PHP 5: Ekstraksi nilai dengan fallback isset()
    $nama    = isset($data['nama'])    ? sanitize_input($data['nama'])    : '';
    $email   = isset($data['email'])   ? sanitize_input($data['email'])   : '';
    $telepon = isset($data['telepon']) ? sanitize_input($data['telepon']) : '';
    $pesan   = isset($data['pesan'])   ? sanitize_input($data['pesan'])   : '';

    // Escape semua nilai variabel sebelum disisipkan ke SQL Query untuk mencegah SQL Injection
    // mysqli_real_escape_string() procedural tersedia di PHP 5.0.0+
    $nama_clean    = mysqli_real_escape_string($conn, $nama);
    $email_clean   = mysqli_real_escape_string($conn, $email);
    $telepon_clean = mysqli_real_escape_string($conn, $telepon);
    $pesan_clean   = mysqli_real_escape_string($conn, $pesan);
    
    // Waktu pembuatan saat ini dalam format Y-m-d H:i:s
    $created_at    = date('Y-m-d H:i:s');

    $sql = "INSERT INTO inquiries (nama, email, telepon, pesan, created_at) 
            VALUES ('$nama_clean', '$email_clean', '$telepon_clean', '$pesan_clean', '$created_at')";

    $result = mysqli_query($conn, $sql);

    return $result ? true : false;
}
