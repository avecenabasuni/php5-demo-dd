<?php
/**
 * Halaman Login - SIAKAD PHP 5 Compatible
 */

$errors = array();
$form_username = '';

// Proses login saat POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_username = isset($_POST['username']) ? sanitize_input($_POST['username']) : '';
    $password      = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($form_username)) {
        $errors['username'] = 'Username wajib diisi.';
    }
    if (empty($password)) {
        $errors['password'] = 'Password wajib diisi.';
    }

    if (empty($errors)) {
        if (attempt_login($db_conn, $form_username, $password)) {
            set_flash('success', 'Selamat datang, ' . $_SESSION['user_nama'] . '!');
            redirect('index.php?page=dashboard');
        } else {
            $errors['general'] = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo escape_html(APP_NAME); ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎓</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="login-icon">🎓</div>
        <h1><?php echo escape_html(APP_NAME); ?></h1>
        <p class="login-subtitle">Sistem Informasi Akademik Perkuliahan</p>

        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error">
                <span class="alert-text"><?php echo escape_html($errors['general']); ?></span>
            </div>
        <?php endif; ?>

        <?php echo display_flash_messages(); ?>

        <form action="index.php?page=login" method="POST" novalidate>
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username"
                       class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                       value="<?php echo escape_html($form_username); ?>"
                       placeholder="Masukkan username" autofocus>
                <?php if (isset($errors['username'])): ?>
                    <span class="error-text"><?php echo escape_html($errors['username']); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password"
                       class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                       placeholder="Masukkan password">
                <?php if (isset($errors['password'])): ?>
                    <span class="error-text"><?php echo escape_html($errors['password']); ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Masuk ke Sistem</button>
        </form>

        <p style="text-align: center; margin-top: 20px; font-size: 0.8rem; color: var(--text-muted);">
            Demo: admin/admin123 &bull; dosen1/dosen123 &bull; mhs1/mhs123
        </p>
    </div>
</div>
</body>
</html>
