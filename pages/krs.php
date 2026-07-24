<?php
/**
 * KRS (Kartu Rencana Studi) - SIAKAD PHP 5 Compatible
 * Admin: melihat semua KRS, approve/reject
 * Mahasiswa: mendaftar MK, melihat KRS sendiri
 */
require_login();

$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// ============================================================
// MAHASISWA: Daftar MK (POST)
// ============================================================
if ($user_role === 'mahasiswa' && $action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_feature_enabled($db_conn, 'krs_registration')) {
        set_flash('error', 'Pendaftaran KRS sedang ditutup oleh admin.');
        redirect('index.php?page=krs');
    }

    $mhs_id = isset($_SESSION['mahasiswa_id']) ? (int)$_SESSION['mahasiswa_id'] : 0;
    $mk_id  = isset($_POST['mata_kuliah_id']) ? (int)$_POST['mata_kuliah_id'] : 0;
    $ta     = isset($_POST['tahun_ajaran'])   ? sanitize_input($_POST['tahun_ajaran']) : '';
    $smt    = isset($_POST['semester'])       ? (int)$_POST['semester'] : 1;

    if ($mk_id > 0 && !empty($ta) && $mhs_id > 0) {
        // Cek duplikat
        $existing = fetch_one($db_conn, "SELECT id FROM krs WHERE mahasiswa_id = $mhs_id AND mata_kuliah_id = $mk_id AND tahun_ajaran = " . sql_escape($db_conn, $ta));
        if ($existing) {
            set_flash('warning', 'Anda sudah mendaftar mata kuliah ini di tahun ajaran tersebut.');
        } else {
            $sql = "INSERT INTO krs (mahasiswa_id, mata_kuliah_id, semester, tahun_ajaran, status, created_at) VALUES ("
                . $mhs_id . ", " . $mk_id . ", " . $smt . ", " . sql_escape($db_conn, $ta) . ", 'pending', NOW())";
            mysqli_query($db_conn, $sql);
            set_flash('success', 'Pendaftaran KRS berhasil! Menunggu persetujuan admin.');
        }
    }
    redirect('index.php?page=krs');
}

// ============================================================
// ADMIN: Approve / Reject KRS (POST)
// ============================================================
if ($user_role === 'admin' && ($action === 'approve' || $action === 'reject') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $krs_id = isset($_POST['krs_id']) ? (int)$_POST['krs_id'] : 0;
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    if ($krs_id > 0) {
        mysqli_query($db_conn, "UPDATE krs SET status = '$new_status' WHERE id = $krs_id");
        set_flash('success', 'KRS berhasil di-' . $new_status . '.');
    }
    redirect('index.php?page=krs');
}

// ADMIN: Delete KRS
if ($user_role === 'admin' && $action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $krs_id = isset($_POST['krs_id']) ? (int)$_POST['krs_id'] : 0;
    if ($krs_id > 0) {
        mysqli_query($db_conn, "DELETE FROM krs WHERE id = $krs_id");
        set_flash('success', 'KRS berhasil dihapus.');
    }
    redirect('index.php?page=krs');
}

// ============================================================
// RENDER
// ============================================================

$krs_enabled = is_feature_enabled($db_conn, 'krs_registration');

if ($user_role === 'admin'):
    // Admin: lihat semua KRS
    $krs_rows = fetch_all($db_conn, "SELECT k.*, m.nim, m.nama AS mhs_nama, mk.kode_mk, mk.nama_mk, mk.sks
        FROM krs k
        JOIN mahasiswa m ON k.mahasiswa_id = m.id
        JOIN mata_kuliah mk ON k.mata_kuliah_id = mk.id
        ORDER BY k.created_at DESC");
?>
<div class="page-header">
    <h3>Manajemen KRS (<?php echo count($krs_rows); ?>)</h3>
    <span class="badge <?php echo $krs_enabled ? 'badge-on' : 'badge-off'; ?>">
        Pendaftaran KRS: <?php echo $krs_enabled ? 'DIBUKA' : 'DITUTUP'; ?>
    </span>
</div>

<?php if (empty($krs_rows)): ?>
    <div class="empty-state"><div class="empty-icon">📋</div><p>Belum ada data KRS.</p></div>
<?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr><th>NIM</th><th>Mahasiswa</th><th>Kode MK</th><th>Mata Kuliah</th><th>SKS</th><th>Tahun Ajaran</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($krs_rows as $row): ?>
                <tr>
                    <td><?php echo escape_html($row['nim']); ?></td>
                    <td><?php echo escape_html($row['mhs_nama']); ?></td>
                    <td><strong><?php echo escape_html($row['kode_mk']); ?></strong></td>
                    <td><?php echo escape_html($row['nama_mk']); ?></td>
                    <td><?php echo (int)$row['sks']; ?></td>
                    <td><?php echo escape_html($row['tahun_ajaran']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo escape_html($row['status']); ?>"><?php echo ucfirst(escape_html($row['status'])); ?></span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <?php if ($row['status'] === 'pending'): ?>
                                <form action="index.php?page=krs&action=approve" method="POST" style="display:inline;">
                                    <input type="hidden" name="krs_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form action="index.php?page=krs&action=reject" method="POST" style="display:inline;">
                                    <input type="hidden" name="krs_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            <?php endif; ?>
                            <form action="index.php?page=krs&action=delete" method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus KRS ini?');">
                                <input type="hidden" name="krs_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php elseif ($user_role === 'mahasiswa'):
    $mhs_id = isset($_SESSION['mahasiswa_id']) ? (int)$_SESSION['mahasiswa_id'] : 0;

    // KRS mahasiswa sendiri
    $my_krs = fetch_all($db_conn, "SELECT k.*, mk.kode_mk, mk.nama_mk, mk.sks, d.nama AS dosen_nama
        FROM krs k
        JOIN mata_kuliah mk ON k.mata_kuliah_id = mk.id
        LEFT JOIN dosen d ON mk.dosen_id = d.id
        WHERE k.mahasiswa_id = $mhs_id
        ORDER BY k.tahun_ajaran DESC, mk.kode_mk ASC");

    // MK yang tersedia untuk didaftarkan
    $available_mk = fetch_all($db_conn, "SELECT mk.*, d.nama AS dosen_nama FROM mata_kuliah mk LEFT JOIN dosen d ON mk.dosen_id = d.id ORDER BY mk.kode_mk ASC");
?>

<!-- KRS Saya -->
<div class="card">
    <div class="card-header">
        <h3>KRS Saya</h3>
        <p>Daftar mata kuliah yang telah Anda ambil</p>
    </div>
    <?php if (empty($my_krs)): ?>
        <div class="empty-state"><p>Anda belum mendaftar mata kuliah apapun.</p></div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Dosen</th><th>Tahun Ajaran</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($my_krs as $row): ?>
                    <tr>
                        <td><strong><?php echo escape_html($row['kode_mk']); ?></strong></td>
                        <td><?php echo escape_html($row['nama_mk']); ?></td>
                        <td><?php echo (int)$row['sks']; ?></td>
                        <td><?php echo escape_html($row['dosen_nama']); ?></td>
                        <td><?php echo escape_html($row['tahun_ajaran']); ?></td>
                        <td><span class="badge badge-<?php echo escape_html($row['status']); ?>"><?php echo ucfirst(escape_html($row['status'])); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Form Daftar MK Baru -->
<?php if ($krs_enabled): ?>
<div class="card">
    <div class="card-header">
        <h3>Daftar Mata Kuliah Baru</h3>
        <p>Pilih mata kuliah yang ingin Anda ambil</p>
    </div>
    <form action="index.php?page=krs&action=register" method="POST">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Mata Kuliah <span class="required">*</span></label>
                <select name="mata_kuliah_id" class="form-control" required>
                    <option value="">-- Pilih Mata Kuliah --</option>
                    <?php foreach ($available_mk as $mk): ?>
                        <option value="<?php echo $mk['id']; ?>"><?php echo escape_html($mk['kode_mk'] . ' - ' . $mk['nama_mk'] . ' (' . $mk['sks'] . ' SKS)'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Tahun Ajaran <span class="required">*</span></label>
                <input type="text" name="tahun_ajaran" class="form-control" value="2024/2025 Ganjil" placeholder="2024/2025 Ganjil">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Semester Ke-</label>
            <input type="number" name="semester" class="form-control" value="1" min="1" max="14" style="max-width:200px;">
        </div>
        <button type="submit" class="btn btn-primary">Daftar MK</button>
    </form>
</div>
<?php else: ?>
<div class="alert alert-warning">
    <span class="alert-text">⚠️ Pendaftaran KRS sedang ditutup oleh admin. Silakan hubungi bagian akademik.</span>
</div>
<?php endif; ?>

<?php else:
    set_flash('error', 'Akses ditolak.');
    redirect('index.php?page=dashboard');
endif; ?>
