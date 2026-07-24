<?php
/**
 * CRUD Dosen - SIAKAD PHP 5 Compatible
 */
require_role('admin');

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// DELETE
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
        // Cek apakah dosen punya mata kuliah
        $mk_count = count_rows($db_conn, 'mata_kuliah', "dosen_id = $id");
        if ($mk_count > 0) {
            set_flash('error', 'Dosen tidak bisa dihapus karena masih mengampu ' . $mk_count . ' mata kuliah.');
        } else {
            $dsn = fetch_one($db_conn, "SELECT user_id FROM dosen WHERE id = $id");
            if ($dsn) {
                mysqli_query($db_conn, "DELETE FROM dosen WHERE id = $id");
                mysqli_query($db_conn, "DELETE FROM users WHERE id = " . (int)$dsn['user_id']);
                set_flash('success', 'Data dosen berhasil dihapus.');
            }
        }
    }
    redirect('index.php?page=dosen');
}

// ADD / EDIT (Process POST)
if (($action === 'add' || $action === 'edit') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id              = isset($_POST['id'])              ? (int)$_POST['id']                              : 0;
    $nip             = isset($_POST['nip'])             ? sanitize_input($_POST['nip'])                   : '';
    $nama            = isset($_POST['nama'])            ? sanitize_input($_POST['nama'])                  : '';
    $email           = isset($_POST['email'])           ? sanitize_input($_POST['email'])                 : '';
    $telepon         = isset($_POST['telepon'])         ? sanitize_input($_POST['telepon'])               : '';
    $bidang_keahlian = isset($_POST['bidang_keahlian']) ? sanitize_input($_POST['bidang_keahlian'])       : '';
    $username        = isset($_POST['username'])        ? sanitize_input($_POST['username'])              : '';
    $password        = isset($_POST['password'])        ? $_POST['password']                              : '';

    $errors = array();
    if (empty($nip))  $errors['nip']  = 'NIP wajib diisi.';
    if (empty($nama)) $errors['nama'] = 'Nama wajib diisi.';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid.';
    }

    if ($action === 'add') {
        if (empty($username)) $errors['username'] = 'Username wajib diisi.';
        if (empty($password)) $errors['password'] = 'Password wajib diisi.';
        if (empty($errors['nip'])) {
            $existing = fetch_one($db_conn, "SELECT id FROM dosen WHERE nip = " . sql_escape($db_conn, $nip));
            if ($existing) $errors['nip'] = 'NIP sudah terdaftar.';
        }
        if (empty($errors['username'])) {
            $existing = fetch_one($db_conn, "SELECT id FROM users WHERE username = " . sql_escape($db_conn, $username));
            if ($existing) $errors['username'] = 'Username sudah digunakan.';
        }
    }

    if (empty($errors)) {
        if ($action === 'add') {
            $sql_user = "INSERT INTO users (username, password_hash, role, nama, created_at) VALUES ("
                . sql_escape($db_conn, $username) . ", '" . md5($password) . "', 'dosen', "
                . sql_escape($db_conn, $nama) . ", NOW())";
            mysqli_query($db_conn, $sql_user);
            $user_id = mysqli_insert_id($db_conn);

            $sql_dsn = "INSERT INTO dosen (nip, user_id, nama, email, telepon, bidang_keahlian) VALUES ("
                . sql_escape($db_conn, $nip) . ", " . (int)$user_id . ", "
                . sql_escape($db_conn, $nama) . ", " . sql_escape($db_conn, $email) . ", "
                . sql_escape($db_conn, $telepon) . ", " . sql_escape($db_conn, $bidang_keahlian) . ")";
            mysqli_query($db_conn, $sql_dsn);
            set_flash('success', 'Dosen berhasil ditambahkan.');
            redirect('index.php?page=dosen');
        } else {
            $sql_update = "UPDATE dosen SET nip = " . sql_escape($db_conn, $nip)
                . ", nama = " . sql_escape($db_conn, $nama)
                . ", email = " . sql_escape($db_conn, $email)
                . ", telepon = " . sql_escape($db_conn, $telepon)
                . ", bidang_keahlian = " . sql_escape($db_conn, $bidang_keahlian)
                . " WHERE id = $id";
            mysqli_query($db_conn, $sql_update);

            $dsn = fetch_one($db_conn, "SELECT user_id FROM dosen WHERE id = $id");
            if ($dsn) {
                mysqli_query($db_conn, "UPDATE users SET nama = " . sql_escape($db_conn, $nama) . " WHERE id = " . (int)$dsn['user_id']);
            }
            set_flash('success', 'Data dosen berhasil diperbarui.');
            redirect('index.php?page=dosen');
        }
    }
}

// ============================================================
// RENDER
// ============================================================
if ($action === 'add'):
    if (!isset($errors)) $errors = array();
    $nip             = isset($nip)             ? $nip             : '';
    $nama            = isset($nama)            ? $nama            : '';
    $email           = isset($email)           ? $email           : '';
    $telepon         = isset($telepon)         ? $telepon         : '';
    $bidang_keahlian = isset($bidang_keahlian) ? $bidang_keahlian : '';
    $username        = isset($username)        ? $username        : '';
?>
<div class="card">
    <div class="card-header"><h3>Tambah Dosen Baru</h3></div>
    <form action="index.php?page=dosen&action=add" method="POST" novalidate>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">NIP <span class="required">*</span></label>
                <input type="text" name="nip" class="form-control <?php echo isset($errors['nip']) ? 'is-invalid' : ''; ?>" value="<?php echo escape_html($nip); ?>">
                <?php if (isset($errors['nip'])): ?><span class="error-text"><?php echo escape_html($errors['nip']); ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Lengkap <span class="required">*</span></label>
                <input type="text" name="nama" class="form-control <?php echo isset($errors['nama']) ? 'is-invalid' : ''; ?>" value="<?php echo escape_html($nama); ?>">
                <?php if (isset($errors['nama'])): ?><span class="error-text"><?php echo escape_html($errors['nama']); ?></span><?php endif; ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo escape_html($email); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Telepon</label>
                <input type="text" name="telepon" class="form-control" value="<?php echo escape_html($telepon); ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Bidang Keahlian</label>
            <input type="text" name="bidang_keahlian" class="form-control" value="<?php echo escape_html($bidang_keahlian); ?>">
        </div>
        <hr style="border-color: var(--border-color); margin: 20px 0;">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Username Login <span class="required">*</span></label>
                <input type="text" name="username" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" value="<?php echo escape_html($username); ?>">
                <?php if (isset($errors['username'])): ?><span class="error-text"><?php echo escape_html($errors['username']); ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Password <span class="required">*</span></label>
                <input type="password" name="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>">
                <?php if (isset($errors['password'])): ?><span class="error-text"><?php echo escape_html($errors['password']); ?></span><?php endif; ?>
            </div>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="index.php?page=dosen" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php elseif ($action === 'edit'):
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $dsn = fetch_one($db_conn, "SELECT * FROM dosen WHERE id = $id");
    if (!$dsn) { set_flash('error', 'Data dosen tidak ditemukan.'); redirect('index.php?page=dosen'); }
    if (!isset($errors)) $errors = array();
    $nip             = isset($nip)             ? $nip             : $dsn['nip'];
    $nama            = isset($nama)            ? $nama            : $dsn['nama'];
    $email           = isset($email)           ? $email           : $dsn['email'];
    $telepon         = isset($telepon)         ? $telepon         : $dsn['telepon'];
    $bidang_keahlian = isset($bidang_keahlian) ? $bidang_keahlian : $dsn['bidang_keahlian'];
?>
<div class="card">
    <div class="card-header"><h3>Edit Data Dosen</h3></div>
    <form action="index.php?page=dosen&action=edit" method="POST" novalidate>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">NIP <span class="required">*</span></label>
                <input type="text" name="nip" class="form-control" value="<?php echo escape_html($nip); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Nama <span class="required">*</span></label>
                <input type="text" name="nama" class="form-control" value="<?php echo escape_html($nama); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo escape_html($email); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Telepon</label>
                <input type="text" name="telepon" class="form-control" value="<?php echo escape_html($telepon); ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Bidang Keahlian</label>
            <input type="text" name="bidang_keahlian" class="form-control" value="<?php echo escape_html($bidang_keahlian); ?>">
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Perbarui</button>
            <a href="index.php?page=dosen" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php else:
    $rows = fetch_all($db_conn, "SELECT * FROM dosen ORDER BY nama ASC");
?>
<div class="page-header">
    <h3>Daftar Dosen (<?php echo count($rows); ?>)</h3>
    <a href="index.php?page=dosen&action=add" class="btn btn-primary">+ Tambah Dosen</a>
</div>

<?php if (empty($rows)): ?>
    <div class="empty-state"><div class="empty-icon">👨‍🏫</div><p>Belum ada data dosen.</p></div>
<?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead><tr><th>NIP</th><th>Nama</th><th>Bidang Keahlian</th><th>Email</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><strong><?php echo escape_html($row['nip']); ?></strong></td>
                    <td><?php echo escape_html($row['nama']); ?></td>
                    <td><?php echo escape_html($row['bidang_keahlian']); ?></td>
                    <td><?php echo escape_html($row['email']); ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="index.php?page=dosen&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <form action="index.php?page=dosen&action=delete" method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus?');">
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
