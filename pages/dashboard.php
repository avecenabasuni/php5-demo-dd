<?php
/**
 * Dashboard - SIAKAD PHP 5 Compatible
 * Menampilkan statistik ringkasan berdasarkan role user.
 */
require_login();

$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';

// Statistik umum
$total_mhs = count_rows($db_conn, 'mahasiswa', '');
$total_dsn = count_rows($db_conn, 'dosen', '');
$total_mk  = count_rows($db_conn, 'mata_kuliah', '');
$total_krs = count_rows($db_conn, 'krs', "status = 'approved'");
$total_krs_pending = count_rows($db_conn, 'krs', "status = 'pending'");
?>

<!-- Stat Cards -->
<div class="stat-grid">
    <?php if ($user_role === 'admin'): ?>
        <div class="stat-card">
            <div class="stat-icon blue">👨‍🎓</div>
            <div class="stat-value"><?php echo $total_mhs; ?></div>
            <div class="stat-label">Total Mahasiswa</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">👨‍🏫</div>
            <div class="stat-value"><?php echo $total_dsn; ?></div>
            <div class="stat-label">Total Dosen</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">📚</div>
            <div class="stat-value"><?php echo $total_mk; ?></div>
            <div class="stat-label">Mata Kuliah</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">📋</div>
            <div class="stat-value"><?php echo $total_krs_pending; ?></div>
            <div class="stat-label">KRS Pending</div>
        </div>
    <?php endif; ?>

    <?php if ($user_role === 'dosen'): ?>
        <?php
        $dosen_id = isset($_SESSION['dosen_id']) ? (int)$_SESSION['dosen_id'] : 0;
        $mk_diampu = count_rows($db_conn, 'mata_kuliah', "dosen_id = $dosen_id");
        $mhs_diampu_sql = "SELECT COUNT(DISTINCT k.mahasiswa_id) AS total FROM krs k
                           JOIN mata_kuliah mk ON k.mata_kuliah_id = mk.id
                           WHERE mk.dosen_id = $dosen_id AND k.status = 'approved'";
        $mhs_diampu_row = fetch_one($db_conn, $mhs_diampu_sql);
        $mhs_diampu = isset($mhs_diampu_row['total']) ? $mhs_diampu_row['total'] : 0;
        ?>
        <div class="stat-card">
            <div class="stat-icon blue">📚</div>
            <div class="stat-value"><?php echo $mk_diampu; ?></div>
            <div class="stat-label">MK Diampu</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">👨‍🎓</div>
            <div class="stat-value"><?php echo $mhs_diampu; ?></div>
            <div class="stat-label">Mahasiswa Diampu</div>
        </div>
    <?php endif; ?>

    <?php if ($user_role === 'mahasiswa'): ?>
        <?php
        $mhs_id = isset($_SESSION['mahasiswa_id']) ? (int)$_SESSION['mahasiswa_id'] : 0;
        $krs_aktif = count_rows($db_conn, 'krs', "mahasiswa_id = $mhs_id AND status = 'approved'");
        $krs_pending_mhs = count_rows($db_conn, 'krs', "mahasiswa_id = $mhs_id AND status = 'pending'");
        $ipk = calculate_ipk($db_conn, $mhs_id);
        ?>
        <div class="stat-card">
            <div class="stat-icon blue">📋</div>
            <div class="stat-value"><?php echo $krs_aktif; ?></div>
            <div class="stat-label">KRS Disetujui</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">⏳</div>
            <div class="stat-value"><?php echo $krs_pending_mhs; ?></div>
            <div class="stat-label">KRS Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">🏆</div>
            <div class="stat-value"><?php echo number_format($ipk, 2); ?></div>
            <div class="stat-label">IPK Sementara</div>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Activity / Info -->
<div class="card">
    <div class="card-header">
        <h3>Selamat Datang di SIAKAD</h3>
        <p>Sistem Informasi Akademik Perkuliahan — PHP 5 Compatible</p>
    </div>
    <div style="color: var(--text-body); font-size: 0.9rem;">
        <?php if ($user_role === 'admin'): ?>
            <p>Sebagai <strong>Administrator</strong>, Anda dapat mengelola data mahasiswa, dosen, mata kuliah, KRS, dan mengatur <strong>Feature Flags</strong> untuk mengontrol fitur sistem.</p>
        <?php elseif ($user_role === 'dosen'): ?>
            <p>Sebagai <strong>Dosen</strong>, Anda dapat menginput nilai mahasiswa untuk mata kuliah yang Anda ampu.</p>
        <?php elseif ($user_role === 'mahasiswa'): ?>
            <p>Sebagai <strong>Mahasiswa</strong>, Anda dapat mendaftar KRS dan melihat transkrip nilai Anda.</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($user_role === 'admin'): ?>
<!-- Feature Flags Status (Admin Only) -->
<div class="card">
    <div class="card-header">
        <h3>Status Feature Flags</h3>
        <p>Kontrol fitur yang sedang aktif/nonaktif di sistem</p>
    </div>
    <?php
    $flags = get_feature_flags($db_conn);
    foreach ($flags as $flag):
    ?>
        <div class="flag-row">
            <div class="flag-info">
                <div class="flag-name"><?php echo escape_html($flag['flag_name']); ?></div>
                <div class="flag-desc"><?php echo escape_html($flag['description']); ?></div>
            </div>
            <span class="badge <?php echo ((int)$flag['is_enabled'] === 1) ? 'badge-on' : 'badge-off'; ?>">
                <?php echo ((int)$flag['is_enabled'] === 1) ? 'ON' : 'OFF'; ?>
            </span>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
