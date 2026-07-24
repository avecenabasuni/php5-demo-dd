<?php
/**
 * CRUD Mata Kuliah - SIAKAD PHP 5 Compatible
 */
require_role('admin');

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// DELETE
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
        $krs_count = count_rows($db_conn, 'krs', "mata_kuliah_id = $id");
        if ($krs_count > 0) {
            set_flash('error', 'Mata kuliah tidak bisa dihapus karena masih terdaftar di ' . $krs_count . ' KRS.');
        } else {
            mysqli_query($db_conn, "DELETE FROM mata_kuliah WHERE id = $id");
            set_flash('success', 'Mata kuliah berhasil dihapus.');
        }
    }
    redirect('index.php?page=matakuliah');
}

// ADD / EDIT (Process POST)
if (($action === 'add' || $action === 'edit') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = isset($_POST['id'])       ? (int)$_POST['id']                      : 0;
    $kode_mk  = isset($_POST['kode_mk'])  ? sanitize_input($_POST['kode_mk'])      : '';
    $nama_mk  = isset($_POST['nama_mk'])  ? sanitize_input($_POST['nama_mk'])      : '';
    $sks      = isset($_POST['sks'])      ? (int)$_POST['sks']                     : 3;
    $semester = isset($_POST['semester']) ? (int)$_POST['semester']                : 1;
    $dosen_id = isset($_POST['dosen_id']) ? (int)$_POST['dosen_id']               : 0;

    $errors = array();
    if (empty($kode_mk)) $errors['kode_mk'] = 'Kode MK wajib diisi.';
    if (empty($nama_mk)) $errors['nama_mk'] = 'Nama MK wajib diisi.';
    if ($sks < 1 || $sks > 6) $errors['sks'] = 'SKS harus antara 1-6.';

    if ($action === 'add' && empty($errors['kode_mk'])) {
        $existing = fetch_one($db_conn, "SELECT id FROM mata_kuliah WHERE kode_mk = " . sql_escape($db_conn, $kode_mk));
        if ($existing) $errors['kode_mk'] = 'Kode MK sudah terdaftar.';
    }

    if (empty($errors)) {
        $dosen_val = ($dosen_id > 0) ? $dosen_id : 'NULL';
        if ($action === 'add') {
            $sql = "INSERT INTO mata_kuliah (kode_mk, nama_mk, sks, semester, dosen_id) VALUES ("
                . sql_escape($db_conn, $kode_mk) . ", "
                . sql_escape($db_conn, $nama_mk) . ", "
                . (int)$sks . ", " . (int)$semester . ", " . $dosen_val . ")";
            mysqli_query($db_conn, $sql);
            set_flash('success', 'Mata kuliah berhasil ditambahkan.');
        } else {
            $sql = "UPDATE mata_kuliah SET kode_mk = " . sql_escape($db_conn, $kode_mk)
                . ", nama_mk = " . sql_escape($db_conn, $nama_mk)
                . ", sks = " . (int)$sks
                . ", semester = " . (int)$semester
                . ", dosen_id = " . $dosen_val
                . " WHERE id = $id";
            mysqli_query($db_conn, $sql);
            set_flash('success', 'Mata kuliah berhasil diperbarui.');
        }
        redirect('index.php?page=matakuliah');
    }
}

// Ambil daftar dosen untuk dropdown
$dosen_list = fetch_all($db_conn, "SELECT id, nama FROM dosen ORDER BY nama ASC");

// ============================================================
// RENDER
// ============================================================
if ($action === 'add'):
    if (!isset($errors)) $errors = array();
    $kode_mk  = isset($kode_mk)  ? $kode_mk  : '';
    $nama_mk  = isset($nama_mk)  ? $nama_mk  : '';
    $sks      = isset($sks)      ? $sks      : 3;
    $semester = isset($semester) ? $semester : 1;
    $dosen_id = isset($dosen_id) ? $dosen_id : 0;
?>
<div class="card">
    <div class="card-header"><h3>Tambah Mata Kuliah</h3></div>
    <form action="index.php?page=matakuliah&action=add" method="POST" novalidate>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Kode MK <span class="required">*</span></label>
                <input type="text" name="kode_mk" class="form-control <?php echo isset($errors['kode_mk']) ? 'is-invalid' : ''; ?>" value="<?php echo escape_html($kode_mk); ?>" placeholder="IF101">
                <?php if (isset($errors['kode_mk'])): ?><span class="error-text"><?php echo escape_html($errors['kode_mk']); ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Mata Kuliah <span class="required">*</span></label>
                <input type="text" name="nama_mk" class="form-control <?php echo isset($errors['nama_mk']) ? 'is-invalid' : ''; ?>" value="<?php echo escape_html($nama_mk); ?>">
                <?php if (isset($errors['nama_mk'])): ?><span class="error-text"><?php echo escape_html($errors['nama_mk']); ?></span><?php endif; ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">SKS <span class="required">*</span></label>
                <input type="number" name="sks" class="form-control" value="<?php echo (int)$sks; ?>" min="1" max="6">
            </div>
            <div class="form-group">
                <label class="form-label">Semester</label>
                <input type="number" name="semester" class="form-control" value="<?php echo (int)$semester; ?>" min="1" max="14">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Dosen Pengampu</label>
            <select name="dosen_id" class="form-control">
                <option value="0">-- Belum Ditentukan --</option>
                <?php foreach ($dosen_list as $d): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo ($dosen_id == $d['id']) ? 'selected' : ''; ?>><?php echo escape_html($d['nama']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="index.php?page=matakuliah" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php elseif ($action === 'edit'):
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $mk = fetch_one($db_conn, "SELECT * FROM mata_kuliah WHERE id = $id");
    if (!$mk) { set_flash('error', 'Mata kuliah tidak ditemukan.'); redirect('index.php?page=matakuliah'); }
    if (!isset($errors)) $errors = array();
    $kode_mk  = isset($kode_mk)  ? $kode_mk  : $mk['kode_mk'];
    $nama_mk  = isset($nama_mk)  ? $nama_mk  : $mk['nama_mk'];
    $sks      = isset($sks)      ? $sks      : $mk['sks'];
    $semester = isset($semester) ? $semester : $mk['semester'];
    $dosen_id = isset($dosen_id) ? $dosen_id : $mk['dosen_id'];
?>
<div class="card">
    <div class="card-header"><h3>Edit Mata Kuliah</h3></div>
    <form action="index.php?page=matakuliah&action=edit" method="POST" novalidate>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Kode MK <span class="required">*</span></label>
                <input type="text" name="kode_mk" class="form-control" value="<?php echo escape_html($kode_mk); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Nama MK <span class="required">*</span></label>
                <input type="text" name="nama_mk" class="form-control" value="<?php echo escape_html($nama_mk); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">SKS</label>
                <input type="number" name="sks" class="form-control" value="<?php echo (int)$sks; ?>" min="1" max="6">
            </div>
            <div class="form-group">
                <label class="form-label">Semester</label>
                <input type="number" name="semester" class="form-control" value="<?php echo (int)$semester; ?>" min="1" max="14">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Dosen Pengampu</label>
            <select name="dosen_id" class="form-control">
                <option value="0">-- Belum Ditentukan --</option>
                <?php foreach ($dosen_list as $d): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo ($dosen_id == $d['id']) ? 'selected' : ''; ?>><?php echo escape_html($d['nama']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Perbarui</button>
            <a href="index.php?page=matakuliah" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php else:
    $rows = fetch_all($db_conn, "SELECT mk.*, d.nama AS dosen_nama FROM mata_kuliah mk LEFT JOIN dosen d ON mk.dosen_id = d.id ORDER BY mk.kode_mk ASC");
?>
<div class="page-header">
    <h3>Daftar Mata Kuliah (<?php echo count($rows); ?>)</h3>
    <a href="index.php?page=matakuliah&action=add" class="btn btn-primary">+ Tambah MK</a>
</div>

<?php if (empty($rows)): ?>
    <div class="empty-state"><div class="empty-icon">📚</div><p>Belum ada mata kuliah.</p></div>
<?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr><th>Kode</th><th>Nama MK</th><th>SKS</th><th>Semester</th><th>Dosen Pengampu</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><strong><?php echo escape_html($row['kode_mk']); ?></strong></td>
                    <td><?php echo escape_html($row['nama_mk']); ?></td>
                    <td><?php echo (int)$row['sks']; ?></td>
                    <td><?php echo (int)$row['semester']; ?></td>
                    <td><?php echo !empty($row['dosen_nama']) ? escape_html($row['dosen_nama']) : '<span style="color:var(--text-muted)">-</span>'; ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="index.php?page=matakuliah&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <form action="index.php?page=matakuliah&action=delete" method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus?');">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php endif; ?>
