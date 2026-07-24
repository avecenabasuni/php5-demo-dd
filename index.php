<?php
/**
 * SIAKAD - Router / Front Controller - PHP 5 Compatible
 *
 * Semua request masuk ke file ini, lalu di-route ke pages/ sesuai ?page=xxx
 */
require_once 'config.php';
require_once 'functions.php';

// PHP 5: isset() ? : bukan ??
$page = isset($_GET['page']) ? $_GET['page'] : '';

// Jika sudah login dan mengakses halaman login/kosong → redirect ke dashboard
if (empty($page) || $page === 'login') {
    if (is_logged_in()) {
        redirect('index.php?page=dashboard');
    }
}

// Jika belum ada page, default ke login
if (empty($page)) {
    $page = 'login';
}

// Halaman publik (tidak perlu login)
$public_pages = array('login');

// Halaman yang memerlukan login
$protected_pages = array(
    'dashboard', 'mahasiswa', 'dosen', 'matakuliah',
    'krs', 'nilai', 'transkrip', 'settings', 'logout'
);

// Semua halaman yang diizinkan
$allowed_pages = array_merge($public_pages, $protected_pages);

// Validasi halaman
if (!in_array($page, $allowed_pages)) {
    set_flash('error', 'Halaman tidak ditemukan.');
    redirect('index.php?page=dashboard');
}

// Cek login untuk halaman protected
if (in_array($page, $protected_pages) && !is_logged_in()) {
    set_flash('error', 'Silakan login terlebih dahulu.');
    redirect('index.php?page=login');
}

// Render halaman
$page_file = 'pages/' . $page . '.php';

if (!file_exists($page_file)) {
    set_flash('error', 'File halaman tidak ditemukan.');
    redirect('index.php?page=login');
}

// Halaman login & logout tanpa layout (standalone)
if ($page === 'login' || $page === 'logout') {
    require_once $page_file;
} else {
    // Halaman dengan layout sidebar
    require_once 'templates/header.php';
    echo '<div class="app-layout">';
    require_once 'templates/sidebar.php';
    echo '<div class="main-content">';
    require_once 'templates/topbar.php';
    echo '<div class="content-area">';
    echo display_flash_messages();
    require_once $page_file;
    echo '</div>';
    require_once 'templates/footer.php';
    echo '</div>';
    echo '</div>';
}
