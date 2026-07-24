<?php
/**
 * Transkrip Nilai / KHS - SIAKAD PHP 5 Compatible
 * Mahasiswa melihat rekap nilai & IPK.
 */
require_role('mahasiswa');

if (!is_feature_enabled($db_conn, 'show_transcript')) {
    set_flash('warning', 'Akses transkrip sedang dinonaktifkan oleh admin.');
    redirect('index.php?page=dashboard');
}

$mhs_id = isset($_SESSION['mahasiswa_id']) ? (int)$_SESSION['mahasiswa_id'] : 0;

// Ambil data mahasiswa
$mhs = fetch_one($db_conn, "SELECT * FROM mahasiswa WHERE id = $mhs_id");
if (!$mhs) {
    set_flash('error', 'Data mahasiswa tidak ditemukan.');
    redirect('index.php?page=dashboard');
}

// Ambil semua nilai
$nilai_rows = fetch_all($db_conn, "SELECT mk.kode_mk, mk.nama_mk, mk.sks, mk.semester,
    n.tugas, n.uts, n.uas, n.nilai_akhir, n.grade,
    k.tahun_ajaran
    FROM krs k
    JOIN mata_kuliah mk ON k.mata_kuliah_id = mk.id
    LEFT JOIN nilai n ON n.krs_id = k.id
    WHERE k.mahasiswa_id = $mhs_id AND k.status = 'approved'
    ORDER BY k.tahun_ajaran ASC, mk.semester ASC, mk.kode_mk ASC");

// Hitung IPK
$ipk = calculate_ipk($db_conn, $mhs_id);

// Hitung total SKS yang sudah bernilai
$total_sks = 0;
$total_sks_lulus = 0;
foreach ($nilai_rows as $n) {
    if (!empty($n['grade'])) {
        $total_sks += (int)$n['sks'];
        if ($n['grade'] !== 'E') {
            $total_sks_lulus += (int)$n['sks'];
        }
    }
}
?>

<div class="card">
    <div class="transcript-header">
        <h2>Transkrip Akademik</h2>
        <p>Kartu Hasil Studi (KHS)</p>
    </div>

    <!-- Info Mahasiswa -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; font-size: 0.9rem;">
        <div>
            <span style="color: var(--text-muted);">NIM:</span>
            <strong style="color: var(--text-heading); margin-left: 8px;"><?php echo escape_html($mhs['nim']); ?></strong>
        </div>
        <div>
            <span style="color: var(--text-muted);">Nama:</span>
            <strong style="color: var(--text-heading); margin-left: 8px;"><?php echo escape_html($mhs['nama']); ?></strong>
        </div>
        <div>
            <span style="color: var(--text-muted);">Jurusan:</span>
            <strong style="color: var(--text-heading); margin-left: 8px;"><?php echo escape_html($mhs['jurusan']); ?></strong>
        </div>
        <div>
            <span style="color: var(--text-muted);">Angkatan:</span>
            <strong style="color: var(--text-heading); margin-left: 8px;"><?php echo (int)$mhs['angkatan']; ?></strong>
        </div>
    </div>

    <!-- Tabel Nilai -->
    <?php if (empty($nilai_rows)): ?>
        <div class="empty-state"><div class="empty-icon">📄</div><p>Belum ada data nilai.</p></div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode MK</th>
                        <th>Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Tugas</th>
                        <th>UTS</th>
                        <th>UAS</th>
                        <th>Akhir</th>
                        <th>Grade</th>
                        <th>Bobot</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($nilai_rows as $n): ?>
                    <tr>
                        <td><strong><?php echo escape_html($n['kode_mk']); ?></strong></td>
                        <td><?php echo escape_html($n['nama_mk']); ?></td>
                        <td><?php echo (int)$n['sks']; ?></td>
                        <td><?php echo !empty($n['tugas']) ? $n['tugas'] : '-'; ?></td>
                        <td><?php echo !empty($n['uts']) ? $n['uts'] : '-'; ?></td>
                        <td><?php echo !empty($n['uas']) ? $n['uas'] : '-'; ?></td>
                        <td><strong><?php echo !empty($n['nilai_akhir']) ? $n['nilai_akhir'] : '-'; ?></strong></td>
                        <td>
                            <?php if (!empty($n['grade'])): ?>
                                <span class="badge <?php echo ($n['grade'] === 'A' || $n['grade'] === 'B') ? 'badge-approved' : (($n['grade'] === 'C') ? 'badge-pending' : 'badge-rejected'); ?>">
                                    <?php echo escape_html($n['grade']); ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo !empty($n['grade']) ? number_format(grade_to_point($n['grade']), 1) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- IPK Display -->
        <div class="ipk-display">
            <div class="ipk-value"><?php echo number_format($ipk, 2); ?></div>
            <div class="ipk-label">Indeks Prestasi Kumulatif (IPK)</div>
            <div style="margin-top: 8px; font-size: 0.85rem; color: var(--text-muted);">
                Total SKS Ditempuh: <?php echo $total_sks; ?> |
                SKS Lulus: <?php echo $total_sks_lulus; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
