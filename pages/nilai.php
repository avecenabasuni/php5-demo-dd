<?php
/**
 * Input Nilai - SIAKAD PHP 5 Compatible
 * Dosen menginput nilai mahasiswa untuk MK yang diampu.
 */
require_role('dosen');

$dosen_id = isset($_SESSION['dosen_id']) ? (int)$_SESSION['dosen_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$grade_enabled = is_feature_enabled($db_conn, 'grade_input');

// ============================================================
// PROSES INPUT / UPDATE NILAI (POST)
// ============================================================
if ($action === 'input' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$grade_enabled) {
        set_flash('error', 'Input nilai sedang dinonaktifkan oleh admin (Feature Flag OFF).');
        redirect('index.php?page=nilai');
    }

    $krs_id = isset($_POST['krs_id']) ? (int)$_POST['krs_id'] : 0;
    $tugas  = isset($_POST['tugas'])  ? floatval($_POST['tugas'])  : 0;
    $uts    = isset($_POST['uts'])    ? floatval($_POST['uts'])    : 0;
    $uas    = isset($_POST['uas'])    ? floatval($_POST['uas'])    : 0;

    $errors = array();
    if ($tugas < 0 || $tugas > 100) $errors[] = 'Nilai tugas harus 0-100.';
    if ($uts < 0 || $uts > 100)     $errors[] = 'Nilai UTS harus 0-100.';
    if ($uas < 0 || $uas > 100)     $errors[] = 'Nilai UAS harus 0-100.';

    if (empty($errors) && $krs_id > 0) {
        $nilai_akhir = calculate_final_score($tugas, $uts, $uas);
        $grade = calculate_grade($nilai_akhir);

        // Cek apakah sudah ada nilai untuk KRS ini
        $existing = fetch_one($db_conn, "SELECT id FROM nilai WHERE krs_id = $krs_id");
        if ($existing) {
            // Update
            $sql = "UPDATE nilai SET tugas = $tugas, uts = $uts, uas = $uas, "
                . "nilai_akhir = $nilai_akhir, grade = '$grade', updated_at = NOW() WHERE krs_id = $krs_id";
            mysqli_query($db_conn, $sql);
            set_flash('success', 'Nilai berhasil diperbarui. Grade: ' . $grade);
        } else {
            // Insert
            $sql = "INSERT INTO nilai (krs_id, tugas, uts, uas, nilai_akhir, grade, created_at) VALUES ("
                . "$krs_id, $tugas, $uts, $uas, $nilai_akhir, '$grade', NOW())";
            mysqli_query($db_conn, $sql);
            set_flash('success', 'Nilai berhasil disimpan. Grade: ' . $grade);
        }
    } else {
        foreach ($errors as $e) {
            set_flash('error', $e);
        }
    }
    $mk_id_redirect = isset($_POST['mk_id']) ? (int)$_POST['mk_id'] : 0;
    redirect('index.php?page=nilai&action=detail&mk_id=' . $mk_id_redirect);
}

// ============================================================
// RENDER: Detail MK → Daftar Mahasiswa + Input Nilai
// ============================================================
if ($action === 'detail'):
    $mk_id = isset($_GET['mk_id']) ? (int)$_GET['mk_id'] : 0;
    $mk = fetch_one($db_conn, "SELECT * FROM mata_kuliah WHERE id = $mk_id AND dosen_id = $dosen_id");
    if (!$mk) { set_flash('error', 'Mata kuliah tidak ditemukan.'); redirect('index.php?page=nilai'); }

    // Ambil daftar mahasiswa yang KRS-nya approved untuk MK ini
    $students = fetch_all($db_conn, "SELECT k.id AS krs_id, m.nim, m.nama,
        n.tugas, n.uts, n.uas, n.nilai_akhir, n.grade
        FROM krs k
        JOIN mahasiswa m ON k.mahasiswa_id = m.id
        LEFT JOIN nilai n ON n.krs_id = k.id
        WHERE k.mata_kuliah_id = $mk_id AND k.status = 'approved'
        ORDER BY m.nama ASC");
?>
<div class="page-header">
    <h3><?php echo escape_html($mk['kode_mk'] . ' - ' . $mk['nama_mk']); ?> (<?php echo (int)$mk['sks']; ?> SKS)</h3>
    <a href="index.php?page=nilai" class="btn btn-secondary">← Kembali</a>
</div>

<?php if (!$grade_enabled): ?>
    <div class="alert alert-warning"><span class="alert-text">⚠️ Input nilai sedang dinonaktifkan oleh admin.</span></div>
<?php endif; ?>

<?php if (empty($students)): ?>
    <div class="empty-state"><div class="empty-icon">📝</div><p>Belum ada mahasiswa terdaftar (KRS approved) untuk mata kuliah ini.</p></div>
<?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr><th>NIM</th><th>Nama</th><th>Tugas (30%)</th><th>UTS (30%)</th><th>UAS (40%)</th><th>Akhir</th><th>Grade</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($students as $s): ?>
                <tr>
                    <form action="index.php?page=nilai&action=input" method="POST">
                        <input type="hidden" name="krs_id" value="<?php echo $s['krs_id']; ?>">
                        <input type="hidden" name="mk_id" value="<?php echo $mk_id; ?>">
                        <td><?php echo escape_html($s['nim']); ?></td>
                        <td><?php echo escape_html($s['nama']); ?></td>
                        <td><input type="number" name="tugas" class="form-control" value="<?php echo isset($s['tugas']) ? $s['tugas'] : 0; ?>" min="0" max="100" step="0.01" style="width:80px;" <?php echo !$grade_enabled ? 'disabled' : ''; ?>></td>
                        <td><input type="number" name="uts" class="form-control" value="<?php echo isset($s['uts']) ? $s['uts'] : 0; ?>" min="0" max="100" step="0.01" style="width:80px;" <?php echo !$grade_enabled ? 'disabled' : ''; ?>></td>
                        <td><input type="number" name="uas" class="form-control" value="<?php echo isset($s['uas']) ? $s['uas'] : 0; ?>" min="0" max="100" step="0.01" style="width:80px;" <?php echo !$grade_enabled ? 'disabled' : ''; ?>></td>
                        <td><strong><?php echo isset($s['nilai_akhir']) ? $s['nilai_akhir'] : '-'; ?></strong></td>
                        <td>
                            <?php if (!empty($s['grade'])): ?>
                                <span class="badge <?php echo ($s['grade'] === 'A' || $s['grade'] === 'B') ? 'badge-approved' : (($s['grade'] === 'C') ? 'badge-pending' : 'badge-rejected'); ?>"><?php echo escape_html($s['grade']); ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($grade_enabled): ?>
                                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                            <?php endif; ?>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
// ============================================================
// RENDER: LIST → Daftar MK yang diampu Dosen
// ============================================================
else:
    $mk_list = fetch_all($db_conn, "SELECT mk.*, (SELECT COUNT(*) FROM krs WHERE mata_kuliah_id = mk.id AND status = 'approved') AS jumlah_mhs
        FROM mata_kuliah mk WHERE mk.dosen_id = $dosen_id ORDER BY mk.kode_mk ASC");
?>
<div class="page-header">
    <h3>Mata Kuliah yang Diampu</h3>
    <span class="badge <?php echo $grade_enabled ? 'badge-on' : 'badge-off'; ?>">
        Input Nilai: <?php echo $grade_enabled ? 'AKTIF' : 'NONAKTIF'; ?>
    </span>
</div>

<?php if (empty($mk_list)): ?>
    <div class="empty-state"><div class="empty-icon">📝</div><p>Anda belum mengampu mata kuliah apapun.</p></div>
<?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr><th>Kode MK</th><th>Nama Mata Kuliah</th><th>SKS</th><th>Semester</th><th>Mahasiswa</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($mk_list as $mk): ?>
                <tr>
                    <td><strong><?php echo escape_html($mk['kode_mk']); ?></strong></td>
                    <td><?php echo escape_html($mk['nama_mk']); ?></td>
                    <td><?php echo (int)$mk['sks']; ?></td>
                    <td><?php echo (int)$mk['semester']; ?></td>
                    <td><?php echo (int)$mk['jumlah_mhs']; ?> orang</td>
                    <td>
                        <a href="index.php?page=nilai&action=detail&mk_id=<?php echo $mk['id']; ?>" class="btn btn-primary btn-sm">Input Nilai</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php endif; ?>
