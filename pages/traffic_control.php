<?php
/**
 * Traffic Control Panel (Web UI) - SIAKAD PHP 5 Compatible
 *
 * Halaman kontrol untuk memicu simulasi trafik pengguna langsung dari browser.
 * HANYA DAPAT DIAKSES OLEH ADMIN.
 */
require_role('admin');

define('SIAKAD_TRAFFIC_WEB_RUNNER', true);

$simulation_result = null;
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode  = isset($_POST['mode'])  ? sanitize_input($_POST['mode'])  : 'normal';
    $count = isset($_POST['count']) ? (int)$_POST['count']            : 20;

    // Batasi maksimum request dari web UI agar tidak timeout HTTP
    if ($count > 100) {
        $count = 100;
    }
    if ($count < 1) {
        $count = 10;
    }

    // Tentukan base URL otomatis dari server request
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
    $base_url = $scheme . '://' . $host . $script;

    // Persiapkan environment dan tangkap STDOUT
    ob_start();
    
    // Set $argv sintetis untuk traffic_generator.php
    $argv = array(
        'traffic_generator.php',
        '--url=' . $base_url,
        '--count=' . $count,
        '--mode=' . $mode,
        '--delay=0',
        '--verbose'
    );

    // Jalankan generator
    require_once dirname(__FILE__) . '/../scripts/traffic_generator.php';
    
    $output = ob_get_clean();
    $simulation_result = array(
        'mode'    => $mode,
        'count'   => $count,
        'output' => $output
    );

    // Log event di Datadog
    log_info('Traffic simulation triggered via Web UI', array(
        'mode'  => $mode,
        'count' => $count
    ));
    dd_increment('siakad.traffic_sim.web_triggered', array('mode:' . $mode));
}
?>

<div class="card">
    <div class="card-header">
        <h3>🚀 User Traffic Simulator Panel</h3>
        <p>Jalankan simulasi aktivitas pengguna (Admin, Dosen, Mahasiswa) dan pemicu error secara real-time langsung dari browser.</p>
    </div>

    <form action="index.php?page=traffic_control" method="POST">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Mode Simulasi <span class="required">*</span></label>
                <select name="mode" class="form-control">
                    <option value="normal" selected>Normal — Campuran pengguna nyata, 404, & failed login (Rekomendasi)</option>
                    <option value="heavy">Heavy / Load — Trafik cepat antar halaman tanpa jeda</option>
                    <option value="error">Error Spike — Fokus memicu PHP Error, Exception, DB Error, & Slow Query</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Jumlah Iterasi Request <span class="required">*</span></label>
                <select name="count" class="form-control">
                    <option value="10">10 Iterasi (~25 - 40 Requests)</option>
                    <option value="20" selected>20 Iterasi (~50 - 80 Requests)</option>
                    <option value="50">50 Iterasi (~120 - 200 Requests)</option>
                    <option value="100">100 Iterasi (~250 - 400 Requests)</option>
                </select>
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary" onclick="this.innerHTML='⏳ Sedang Mensimulasikan...';">🚀 Jalankan Simulasi Sekarang</button>
        </div>
    </form>
</div>

<?php if ($simulation_result !== null): ?>
<div class="card">
    <div class="card-header">
        <h3>📊 Hasil Simulasi Trafik</h3>
        <p>Mode: <strong><?php echo strtoupper(escape_html($simulation_result['mode'])); ?></strong> | Iterasi: <strong><?php echo (int)$simulation_result['count']; ?></strong></p>
    </div>

    <div class="alert alert-success">
        <span class="alert-text">✅ Simulasi trafik berhasil dijalankan! Cek aktivitas log & metrics terbaru di Datadog Dashboard.</span>
    </div>

    <div style="background: rgba(15, 23, 42, 0.9); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px; font-family: monospace; font-size: 0.85rem; color: #a5b4fc; max-height: 400px; overflow-y: auto;">
        <pre><?php echo escape_html($simulation_result['output']); ?></pre>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>💻 Cara Menjalankan via CLI / Background (VM Terminal)</h3>
    </div>
    <div style="font-size: 0.9rem; color: var(--text-body); line-height: 1.8;">
        <p>Anda juga dapat menjalankan simulator secara kontinu di background menggunakan terminal VM Linux:</p>
        
        <div style="background: rgba(0, 0, 0, 0.4); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; font-family: monospace; margin: 12px 0;">
            # 1. Jalankan mode normal di background (running terus menerus)<br>
            <span style="color: var(--accent);">bash scripts/run_traffic.sh --continuous</span><br><br>
            
            # 2. Cek status process background<br>
            <span style="color: var(--accent);">bash scripts/run_traffic.sh --status</span><br><br>
            
            # 3. Hentikan background simulator<br>
            <span style="color: var(--accent);">bash scripts/run_traffic.sh --stop</span>
        </div>
    </div>
</div>
