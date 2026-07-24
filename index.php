<?php
/**
 * Landing Page & Form Inquiry - PHP 5 Compatible
 * 
 * ALASAN KOMPATIBILITAS PHP 5:
 * 1. Menggunakan require_once dengan path standar.
 * 2. Menggunakan `array()` untuk penampung error dan data form (BUKAN `[]`).
 * 3. Menggunakan `isset($var) ? $var : $default` (BUKAN null coalescing operator `??`).
 * 4. Output HTML di-escape secara ketat menggunakan `escape_html()` (htmlspecialchars ENT_QUOTES).
 * 5. Menggunakan `header("Location: thanks.php")` + `exit;` untuk pengalihan halaman setelah PRG (Post/Redirect/Get).
 */

require_once 'config.php';
require_once 'functions.php';

// Inisialisasi variabel penampung data & error
// PHP 5: Harus menggunakan array(), bukan []
$errors    = array();
$form_data = array(
    'nama'    => '',
    'email'   => '',
    'telepon' => '',
    'pesan'   => ''
);

// Memproses saat formulir di-submit (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Menyimpan input user ke array form_data untuk sticky form (tetap tampil jika ada error)
    // PHP 5: Menggunakan ternary operator isset() ? : karena ?? belum didukung PHP 5
    $form_data['nama']    = isset($_POST['nama'])    ? $_POST['nama']    : '';
    $form_data['email']   = isset($_POST['email'])   ? $_POST['email']   : '';
    $form_data['telepon'] = isset($_POST['telepon']) ? $_POST['telepon'] : '';
    $form_data['pesan']   = isset($_POST['pesan'])   ? $_POST['pesan']   : '';

    // Jalankan validasi server-side
    $errors = validate_inquiry($form_data);

    // Jika tidak ada kesalahan validasi, simpan data ke database
    if (empty($errors)) {
        $saved = save_inquiry($db_conn, $form_data);

        if ($saved) {
            // Tutup koneksi database sebelum redirect (opsional tapi baik untuk manajemen resource)
            mysqli_close($db_conn);
            
            // Redirect ke halaman terima kasih (Post/Redirect/Get Pattern)
            header("Location: thanks.php");
            exit;
        } else {
            $errors['general'] = "Gagal menyimpan data ke database. Silakan coba beberapa saat lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan Solusi Bisnis Digital | Inquiry Form</title>
    <!-- Favicon Inline SVG (Pencegahan Error 404) -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚡</text></svg>">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Header Navbar -->
    <header class="navbar">
        <div class="container">
            <a href="index.php" class="brand">
                <span>⚡</span> ProSolusi
            </a>
        </div>
    </header>

    <!-- Main Content Grid -->
    <main class="main-wrapper">
        <div class="container">
            <div class="hero-grid">
                
                <!-- Hero Content (Kiri) -->
                <section class="hero-content">
                    <h1>Tingkatkan Efisiensi Bisnis Anda Bersama <span class="gradient-text">ProSolusi</span></h1>
                    <p>Kami menghadirkan solusi teknologi terintegrasi yang fleksibel, aman, dan handal untuk mendukung pertumbuhan bisnis Anda di era digital.</p>
                    
                    <div class="feature-list">
                        <div class="feature-item">
                            <div class="feature-icon">✓</div>
                            <span>Implementasi Cepat & Terstruktur</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">✓</div>
                            <span>Dukungan Lingkungan Legacy (PHP 5) & Modern</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">✓</div>
                            <span>Keamanan Data Terjamin & Bebas Kerentanan SQLi/XSS</span>
                        </div>
                    </div>
                </section>

                <!-- Form Section (Kanan) -->
                <section class="form-card">
                    <div class="form-header">
                        <h2>Kirim Pertanyaan / Inquiry</h2>
                        <p>Isi formulir di bawah ini dan tim ahli kami akan segera menghubungi Anda.</p>
                    </div>

                    <!-- Alert Box Umum jika ada error -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert-box alert-danger">
                            <strong>Terjadi Kesalahan:</strong> Mohon periksa kembali input formulir Anda di bawah ini.
                        </div>
                    <?php endif; ?>

                    <!-- Form Inquiry -->
                    <!-- novalidate digunakan agar validasi server-side dapat diuji sepenuhnya -->
                    <form action="index.php" method="POST" novalidate>
                        
                        <!-- Field Nama -->
                        <div class="form-group">
                            <label for="nama" class="form-label">Nama Lengkap <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="nama" 
                                name="nama" 
                                class="form-control <?php echo isset($errors['nama']) ? 'is-invalid' : ''; ?>" 
                                value="<?php echo escape_html($form_data['nama']); ?>" 
                                placeholder="Contoh: Budi Santoso"
                            >
                            <?php if (isset($errors['nama'])): ?>
                                <span class="error-text"><?php echo escape_html($errors['nama']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Field Email -->
                        <div class="form-group">
                            <label for="email" class="form-label">Alamat Email <span class="required">*</span></label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                value="<?php echo escape_html($form_data['email']); ?>" 
                                placeholder="nama@perusahaan.com"
                            >
                            <?php if (isset($errors['email'])): ?>
                                <span class="error-text"><?php echo escape_html($errors['email']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Field Telepon -->
                        <div class="form-group">
                            <label for="telepon" class="form-label">Nomor Telepon <span class="required">*</span></label>
                            <input 
                                type="text" 
                                id="telepon" 
                                name="telepon" 
                                class="form-control <?php echo isset($errors['telepon']) ? 'is-invalid' : ''; ?>" 
                                value="<?php echo escape_html($form_data['telepon']); ?>" 
                                placeholder="Contoh: 081234567890"
                            >
                            <?php if (isset($errors['telepon'])): ?>
                                <span class="error-text"><?php echo escape_html($errors['telepon']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Field Pesan -->
                        <div class="form-group">
                            <label for="pesan" class="form-label">Pesan / Inquiry <span class="required">*</span></label>
                            <textarea 
                                id="pesan" 
                                name="pesan" 
                                class="form-control <?php echo isset($errors['pesan']) ? 'is-invalid' : ''; ?>" 
                                placeholder="Tuliskan kebutuhan atau pertanyaan Anda di sini..."
                            ><?php echo escape_html($form_data['pesan']); ?></textarea>
                            <?php if (isset($errors['pesan'])): ?>
                                <span class="error-text"><?php echo escape_html($errors['pesan']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn-submit">Kirim Inquiry Sekarang</button>
                    </form>
                </section>

            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ProSolusi. Kompatibel dengan Lingkungan PHP 5.x Legacy.</p>
        </div>
    </footer>

</body>
</html>
