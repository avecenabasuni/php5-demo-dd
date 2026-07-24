<?php
/**
 * CRUD Mahasiswa - SIAKAD PHP 5 Compatible
 */
require_role('admin');

// Cek feature flag untuk registrasi mahasiswa baru
$can_register = is_feature_enabled($db_conn, 'student_registration');

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// ============================================================
// DELETE
// ============================================================
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
        // Ambil user_id mahasiswa untuk dihapus juga
        $mhs = fetch_one($db_conn, "SELECT user_id FROM mahasiswa WHERE id = $id");
        if ($mhs) {
            // Hapus mahasiswa (cascade akan menghapus KRS & nilai)
            mysqli_query($db_conn, "DELETE FROM mahasiswa WHERE id = $id");
            // Hapus user login terkait
            mysqli_query($db_conn, "DELETE FROM users WHERE id = " . (int)$mhs['user_id']);
            set_flash('success', 'Data mahasiswa berhasil dihapus.');
        }
    }
    redirect('index.php?page=mahasiswa');
}

// ============================================================
// ADD / EDIT (Process POST)
// ============================================================
if (($action === 'add' || $action === 'edit') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = isset($_POST['id'])       ? (int)$_POST['id']                         : 0;
    $nim      = isset($_POST['nim'])      ? sanitize_input($_POST['nim'])              : '';
    $nama     = isset($_POST['nama'])     ? sanitize_input($_POST['nama'])             : '';
    $email    = isset($_POST['email'])    ? sanitize_input($_POST['email'])            : '';
    $telepon  = isset($_POST['telepon'])  ? sanitize_input($_POST['telepon'])          : '';
    $jurusan  = isset($_POST['jurusan'])  ? sanitize_input($_POST['jurusan'])          : '';
    $angkatan = isset($_POST['angkatan']) ? (int)$_POST['angkatan']                    : date('Y');
    $alamat   = isset($_POST['alamat'])   ? sanitize_input($_POST['alamat'])           : '';
    $username = isset($_POST['username']) ? sanitize_input($_POST['username'])          : '';
    $password = isset($_POST['password']) ? $_POST['password']                          : '';

    $errors = array();

    if (empty($nim))     $errors['nim']     = 'NIM wajib diisi.';
    if (empty($nama))    $errors['nama']    = 'Nama wajib diisi.';
    if (empty($jurusan)) $errors['jurusan'] = 'Jurusan wajib diisi.';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid.';
    }

    if ($action === 'add') {
        if (!$can_register) {
            $errors['general'] = 'Pendaftaran mahasiswa baru sedang dinonaktifkan (Feature Flag OFF).';
        }
        if (empty($username)) $errors['username'] = 'Username wajib diisi.';
        if (empty($password)) $errors['password'] = 'Password wajib diisi.';

        // Cek duplikat NIM
        if (empty($errors['nim'])) {
            $existing = fetch_one($db_conn, "SELECT id FROM mahasiswa WHERE nim = " . sql_escape($db_conn, $nim));
            if ($existing) $errors['nim'] = 'NIM sudah terdaftar.';
        }
        // Cek duplikat username
        if (empty($errors['username'])) {
            $existing = fetch_one($db_conn, "SELECT id FROM users WHERE username = " . sql_escape($db_conn, $username));
            if ($existing) $errors['username'] = 'Username sudah digunakan.';
        }
    }

    if (empty($errors)) {
        if ($action === 'add') {
            // Buat user login
            $sql_user = "INSERT INTO users (username, password_hash, role, nama, created_at) VALUES ("
                . sql_escape($db_conn, $username) . ", "
                . "'" . md5($password) . "', "
                . "'mahasiswa', "
                . sql_escape($db_conn, $nama) . ", "
                . "NOW())";
            mysqli_query($db_conn, $sql_user);
            $user_id = mysqli_insert_id($db_conn);

            // Buat data mahasiswa
            $sql_mhs = "INSERT INTO mahasiswa (nim, user_id, nama, email, telepon, jurusan, angkatan, alamat) VALUES ("
                . sql_escape($db_conn, $nim) . ", "
                . (int)$user_id . ", "
                . sql_escape($db_conn, $nama) . ", "
                . sql_escape($db_conn, $email) . ", "
                . sql_escape($db_conn, $telepon) . ", "
                . sql_escape($db_conn, $jurusan) . ", "
                . (int)$angkatan . ", "
                . sql_escape($db_conn, $alamat) . ")";
            mysqli_query($db_conn, $sql_mhs);
            set_flash('success', 'Mahasiswa berhasil ditambahkan.');
            redirect('index.php?page=mahasiswa');
        } else {
            // Update data mahasiswa
            $sql_update = "UPDATE mahasiswa SET "
                . "nim = " . sql_escape($db_conn, $nim) . ", "
                . "nama = " . sql_escape($db_conn, $nama) . ", "
                . "email = " . sql_escape($db_conn, $email) . ", "
                . "telepon = " . sql_escape($db_conn, $telepon) . ", "
                . "jurusan = " . sql_escape($db_conn, $jurusan) . ", "
                . "angkatan = " . (int)$angkatan . ", "
                . "alamat = " . sql_escape($db_conn, $alamat)
                . " WHERE id = $id";
            mysqli_query($db_conn, $sql_update);

            // Update nama di tabel users juga
            $mhs = fetch_one($db_conn, "SELECT user_id FROM mahasiswa WHERE id = $id");
            if ($mhs) {
                mysqli_query($db_conn, "UPDATE users SET nama = " . sql_escape($db_conn, $nama) . " WHERE id = " . (int)$mhs['user_id']);
            }

            set_flash('success', 'Data mahasiswa berhasil diperbarui.');
            redirect('index.php?page=mahasiswa');
        }
    }
}

// ============================================================
// RENDER: ADD FORM
// ============================================================
if ($action === 'add'):
    if (!$can_register) {
        set_flash('warning', 'Pendaftaran mahasiswa baru sedang dinonaktifkan oleh admin (Feature Flag: student_registration = OFF).');
        redirect('index.php?page=mahasiswa');
    }
    // PHP 5: Menggunakan isset() ? : untuk sticky form
    if (!isset($errors)) $errors = array();
    $nim      = isset($nim)      ? $nim      : '';
    $nama     = isset($nama)     ? $nama     : '';
    $email    = isset($email)    ? $email    : '';
    $telepon  = isset($telepon)  ? $telepon  : '';
    $jurusan  = isset($jurusan)  ? $jurusan  : '';
    $angkatan = isset($angkatan) ? $angkatan : date('Y');
    $alamat   = isset($alamat)   ? $alamat   : '';
    $username = isset($username) ? $username : '';
?>
<div class="card">
    <div class="card-header">
        <h3>Tambah Mahasiswa Baru</h3>
    </div>

    <?php if (isset($errors['general'])): ?>
        <div class="alert alert-error"><span class="alert-text"><?php echo escape_html($errors['general']); ?></span></div>
    <?php endif; ?>

    <form action="index.php?page=mahasiswa&action=add" method="POST" novalidate>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">NIM <span class="required">*</span></label>
                <input type="text" name="nim" class="form-control <?php echo isset($errors['nim']) ? 'is-invalid' : ''; ?>" value="<?php echo escape_html($nim); ?>">
                <?php if (isset($errors['nim'])): ?><span class="error-text"><?php echo escape_html($errors['nim']); ?></span><?php endif; ?>
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
                <input type="email" name="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" value="<?php echo escape_html($email); ?>">
                <?php if (isset($errors['email'])): ?><span class="error-text"><?php echo escape_html($errors['email']); ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Telepon</label>
                <input type="text" name="telepon" class="form-control" value="<?php echo escape_html($telepon); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Jurusan <span class="required">*</span></label>
                <input type="text" name="jurusan" class="form-control <?php echo isset($errors['jurusan']) ? 'is-invalid' : ''; ?>" value="<?php echo escape_html($jurusan); ?>">
                <?php if (isset($errors['jurusan'])): ?><span class="error-text"><?php echo escape_html($errors['jurusan']); ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Angkatan</label>
                <input type="number" name="angkatan" class="form-control" value="<?php echo (int)$angkatan; ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control"><?php echo escape_html($alamat); ?></textarea>
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
            <a href="index.php?page=mahasiswa" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php
// ============================================================
// RENDER: EDIT FORM
// ============================================================
elseif ($action === 'edit'):
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $mhs = fetch_one($db_conn, "SELECT * FROM mahasiswa WHERE id = $id");
    if (!$mhs) {
        set_flash('error', 'Data mahasiswa tidak ditemukan.');
        redirect('index.php?page=mahasiswa');
    }
    if (!isset($errors)) $errors = array();
    // Gunakan data POST jika ada error, jika tidak gunakan data dari DB
    $nim      = isset($nim)      ? $nim      : $mhs['nim'];
    $nama     = isset($nama)     ? $nama     : $mhs['nama'];
    $email    = isset($email)    ? $email    : $mhs['email'];
    $telepon  = isset($telepon)  ? $telepon  : $mhs['telepon'];
    $jurusan  = isset($jurusan)  ? $jurusan  : $mhs['jurusan'];
    $angkatan = isset($angkatan) ? $angkatan : $mhs['angkatan'];
    $alamat   = isset($alamat)   ? $alamat   : $mhs['alamat'];
?>
<div class="card">
    <div class="card-header">
        <h3>Edit Data Mahasiswa</h3>
    </div>
    <form action="index.php?page=mahasiswa&action=edit" method="POST" novalidate>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">NIM <span class="required">*</span></label>
                <input type="text" name="nim" class="form-control <?php echo isset($errors['nim']) ? 'is-invalid' : ''; ?>" value="<?php echo escape_html($nim); ?>">
                <?php if (isset($errors['nim'])): ?><span class="error-text"><?php echo escape_html($errors['nim']); ?></span><?php endif; ?>
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
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Jurusan <span class="required">*</span></label>
                <input type="text" name="jurusan" class="form-control <?php echo isset($errors['jurusan']) ? 'is-invalid' : ''; ?>" value="<?php echo escape_html($jurusan); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Angkatan</label>
                <input type="number" name="angkatan" class="form-control" value="<?php echo (int)$angkatan; ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control"><?php echo escape_html($alamat); ?></textarea>
        </div>
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">Perbarui</button>
            <a href="index.php?page=mahasiswa" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<?php
// ============================================================
// RENDER: LIST
// ============================================================
else:
    $rows = fetch_all($db_conn, "SELECT * FROM mahasiswa ORDER BY nama ASC");
?>
<div class="page-header">
    <h3>Daftar Mahasiswa (<?php echo count($rows); ?>)</h3>
    <?php if ($can_register): ?>
        <a href="index.php?page=mahasiswa&action=add" class="btn btn-primary">+ Tambah Mahasiswa</a>
    <?php else: ?>
        <span class="badge badge-off">Registrasi OFF</span>
    <?php endif; ?>
</div>

<?php if (empty($rows)): ?>
    <div class="empty-state">
        <div class="empty-icon">👨‍🎓</div>
        <p>Belum ada data mahasiswa.</p>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Jurusan</th>
                    <th>Angkatan</th>
                    <th>Email</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><strong><?php echo escape_html($row['nim']); ?></strong></td>
                    <td><?php echo escape_html($row['nama']); ?></td>
                    <td><?php echo escape_html($row['jurusan']); ?></td>
                    <td><?php echo (int)$row['angkatan']; ?></td>
                    <td><?php echo escape_html($row['email']); ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="index.php?page=mahasiswa&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <form action="index.php?page=mahasiswa&action=delete" method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus data mahasiswa ini?');">
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
