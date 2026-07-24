<?php
/**
 * Feature Flags Settings - SIAKAD PHP 5 Compatible
 * Admin dapat mengaktifkan/menonaktifkan fitur dari sini.
 */
require_role('admin');

// Toggle flag (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flag_id = isset($_POST['flag_id']) ? (int)$_POST['flag_id'] : 0;
    if ($flag_id > 0) {
        toggle_feature_flag($db_conn, $flag_id);

        // Ambil info flag untuk pesan
        $flag = fetch_one($db_conn, "SELECT flag_name, is_enabled FROM feature_flags WHERE id = $flag_id");
        if ($flag) {
            $status = ((int)$flag['is_enabled'] === 1) ? 'AKTIF' : 'NONAKTIF';
            set_flash('success', 'Feature flag "' . $flag['flag_name'] . '" berhasil diubah menjadi ' . $status . '.');
        }
    }
    redirect('index.php?page=settings');
}

// Ambil semua feature flags
$flags = get_feature_flags($db_conn);
?>

<div class="card">
    <div class="card-header">
        <h3>Pengaturan Feature Flags</h3>
        <p>Aktifkan atau nonaktifkan fitur-fitur sistem tanpa mengubah kode. Perubahan berlaku langsung.</p>
    </div>

    <?php if (empty($flags)): ?>
        <div class="empty-state"><div class="empty-icon">⚙️</div><p>Belum ada feature flag terdaftar.</p></div>
    <?php else: ?>
        <?php foreach ($flags as $flag): ?>
            <div class="flag-row">
                <div class="flag-info">
                    <div class="flag-name">
                        <?php echo escape_html($flag['flag_name']); ?>
                        <span class="badge <?php echo ((int)$flag['is_enabled'] === 1) ? 'badge-on' : 'badge-off'; ?>" style="margin-left: 8px;">
                            <?php echo ((int)$flag['is_enabled'] === 1) ? 'ON' : 'OFF'; ?>
                        </span>
                    </div>
                    <div class="flag-desc"><?php echo escape_html($flag['description']); ?></div>
                </div>
                <form action="index.php?page=settings" method="POST" style="display:inline;">
                    <input type="hidden" name="flag_id" value="<?php echo $flag['id']; ?>">
                    <button type="submit" class="btn <?php echo ((int)$flag['is_enabled'] === 1) ? 'btn-danger' : 'btn-success'; ?> btn-sm">
                        <?php echo ((int)$flag['is_enabled'] === 1) ? 'Nonaktifkan' : 'Aktifkan'; ?>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h3>Cara Kerja Feature Flags</h3>
    </div>
    <div style="font-size: 0.9rem; color: var(--text-body); line-height: 1.8;">
        <p>Feature Flags memungkinkan admin mengontrol fitur tanpa mengubah kode sumber:</p>
        <ul style="margin-left: 20px; margin-top: 8px;">
            <li><strong>krs_registration</strong> — Membuka/menutup pendaftaran KRS oleh mahasiswa</li>
            <li><strong>grade_input</strong> — Mengaktifkan/menonaktifkan input nilai oleh dosen</li>
            <li><strong>student_registration</strong> — Membuka/menutup penambahan mahasiswa baru</li>
            <li><strong>show_transcript</strong> — Menampilkan/menyembunyikan transkrip mahasiswa</li>
        </ul>
        <p style="margin-top: 12px; color: var(--text-muted);">
            Setiap kali fitur dicek, sistem membaca tabel <code>feature_flags</code> di database menggunakan fungsi
            <code>is_feature_enabled($conn, 'flag_name')</code>.
        </p>
    </div>
</div>
