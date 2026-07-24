<?php
/**
 * Halaman Konfirmasi Terima Kasih - PHP 5 Compatible
 * 
 * ALASAN KOMPATIBILITAS PHP 5:
 * Menggunakan sintaks standar HTML5 & PHP 5 murni.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terima Kasih | ProSolusi</title>
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

    <!-- Main Content Container -->
    <main class="main-wrapper">
        <div class="container">
            <div class="thanks-container">
                <div class="thanks-card">
                    <div class="icon-success">✓</div>
                    <h1>Terima Kasih!</h1>
                    <p>Pesan inquiry Anda telah berhasil kami terima. Tim kami akan segera meninjau pesan Anda dan menghubungi Anda via Email atau Telepon dalam waktu 1x24 jam.</p>
                    <a href="index.php" class="btn-secondary">← Kembali ke Halaman Utama</a>
                </div>
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
