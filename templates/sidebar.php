<?php
/**
 * Sidebar Navigasi - SIAKAD PHP 5 Compatible
 * Menu ditampilkan sesuai role user yang sedang login.
 */
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
$user_nama = isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : 'User';
$user_initial = strtoupper(substr($user_nama, 0, 1));
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🎓</div>
        <div>
            <div class="brand-text"><?php echo escape_html(APP_NAME); ?></div>
            <div class="brand-version">v<?php echo escape_html(APP_VERSION); ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section">Menu Utama</div>
        <a href="index.php?page=dashboard" class="sidebar-link <?php echo ($current_page === 'dashboard') ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span> Dashboard
        </a>

        <?php if ($user_role === 'admin'): ?>
            <div class="sidebar-section">Master Data</div>
            <a href="index.php?page=mahasiswa" class="sidebar-link <?php echo ($current_page === 'mahasiswa') ? 'active' : ''; ?>">
                <span class="nav-icon">👨‍🎓</span> Mahasiswa
            </a>
            <a href="index.php?page=dosen" class="sidebar-link <?php echo ($current_page === 'dosen') ? 'active' : ''; ?>">
                <span class="nav-icon">👨‍🏫</span> Dosen
            </a>
            <a href="index.php?page=matakuliah" class="sidebar-link <?php echo ($current_page === 'matakuliah') ? 'active' : ''; ?>">
                <span class="nav-icon">📚</span> Mata Kuliah
            </a>

            <div class="sidebar-section">Akademik</div>
            <a href="index.php?page=krs" class="sidebar-link <?php echo ($current_page === 'krs') ? 'active' : ''; ?>">
                <span class="nav-icon">📋</span> KRS
            </a>

            <div class="sidebar-section">Pengaturan</div>
            <a href="index.php?page=settings" class="sidebar-link <?php echo ($current_page === 'settings') ? 'active' : ''; ?>">
                <span class="nav-icon">⚙️</span> Feature Flags
            </a>
        <?php endif; ?>

        <?php if ($user_role === 'dosen'): ?>
            <div class="sidebar-section">Akademik</div>
            <a href="index.php?page=nilai" class="sidebar-link <?php echo ($current_page === 'nilai') ? 'active' : ''; ?>">
                <span class="nav-icon">📝</span> Input Nilai
            </a>
        <?php endif; ?>

        <?php if ($user_role === 'mahasiswa'): ?>
            <div class="sidebar-section">Akademik</div>
            <a href="index.php?page=krs" class="sidebar-link <?php echo ($current_page === 'krs') ? 'active' : ''; ?>">
                <span class="nav-icon">📋</span> KRS Saya
            </a>
            <a href="index.php?page=transkrip" class="sidebar-link <?php echo ($current_page === 'transkrip') ? 'active' : ''; ?>">
                <span class="nav-icon">📄</span> Transkrip
            </a>
        <?php endif; ?>

        <div class="sidebar-section">&nbsp;</div>
        <a href="index.php?page=logout" class="sidebar-link">
            <span class="nav-icon">🚪</span> Logout
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="user-info">
            <div class="user-avatar"><?php echo escape_html($user_initial); ?></div>
            <div>
                <div class="user-name"><?php echo escape_html($user_nama); ?></div>
                <div class="user-role"><?php echo escape_html($user_role); ?></div>
            </div>
        </div>
    </div>
</aside>
