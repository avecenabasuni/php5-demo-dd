<?php
/**
 * Demo Errors - Halaman Simulator Error untuk Demo Datadog
 * SIAKAD PHP 5 Compatible
 *
 * Halaman ini sengaja men-trigger berbagai jenis error PHP
 * agar bisa dimonitor dan divisualisasikan di Datadog.
 *
 * HANYA BISA DIAKSES ADMIN.
 */
require_role('admin');

$error_type = isset($_GET['error']) ? $_GET['error'] : '';
$error_triggered = false;
$error_result = '';

// ============================================================
// TRIGGER ERROR SCENARIOS
// ============================================================

if (!empty($error_type)) {
    $error_triggered = true;

    switch ($error_type) {

        // 1. PHP Notice — Undefined variable
        case 'notice':
            log_info('Demo: Triggering PHP Notice (undefined variable)', array('demo' => true, 'error_type' => 'notice'));
            dd_increment('siakad.demo.error_triggered', array('type:notice'));
            // Sengaja akses variable yang tidak ada
            $result = $undefined_variable_demo;
            $error_result = 'PHP Notice berhasil di-trigger! Variabel $undefined_variable_demo diakses tanpa didefinisikan.';
            break;

        // 2. PHP Warning — Division by zero
        case 'warning':
            log_info('Demo: Triggering PHP Warning (division by zero)', array('demo' => true, 'error_type' => 'warning'));
            dd_increment('siakad.demo.error_triggered', array('type:warning'));
            // Sengaja bagi dengan nol
            $zero = 0;
            $result = 100 / $zero;
            $error_result = 'PHP Warning berhasil di-trigger! Division by zero dilakukan.';
            break;

        // 3. PHP User Error — trigger_error()
        case 'user_error':
            log_info('Demo: Triggering User Error via trigger_error()', array('demo' => true, 'error_type' => 'user_error'));
            dd_increment('siakad.demo.error_triggered', array('type:user_error'));
            trigger_error('Demo SIAKAD: Ini adalah user error yang sengaja di-trigger untuk testing Datadog!', E_USER_ERROR);
            $error_result = 'PHP User Error berhasil di-trigger!';
            break;

        // 4. Uncaught Exception (ditangkap manual)
        case 'exception':
            log_info('Demo: Triggering Exception', array('demo' => true, 'error_type' => 'exception'));
            dd_increment('siakad.demo.error_triggered', array('type:exception'));
            try {
                throw new Exception('Demo SIAKAD: Uncaught exception untuk testing Datadog monitoring!');
            } catch (Exception $e) {
                log_critical('Exception caught: ' . $e->getMessage(), array(
                    'exception_class' => get_class($e),
                    'file'            => $e->getFile(),
                    'line'            => $e->getLine(),
                    'trace'           => $e->getTraceAsString()
                ));
                dd_increment('siakad.error.exception', array('class:Exception'));
                $error_result = 'Exception berhasil di-throw dan di-catch! Message: "' . $e->getMessage() . '"';
            }
            break;

        // 5. Database Error — Query ke tabel yang tidak ada
        case 'db_error':
            log_info('Demo: Triggering Database Error (invalid table)', array('demo' => true, 'error_type' => 'db_error'));
            dd_increment('siakad.demo.error_triggered', array('type:db_error'));
            $bad_result = mysqli_query($db_conn, "SELECT * FROM tabel_yang_tidak_ada WHERE id = 1");
            if (!$bad_result) {
                $db_err = mysqli_error($db_conn);
                log_error('Database error (demo): ' . $db_err, array(
                    'query' => 'SELECT * FROM tabel_yang_tidak_ada WHERE id = 1',
                    'mysql_errno' => mysqli_errno($db_conn)
                ));
                dd_increment('siakad.db.error', array('type:invalid_table', 'demo:true'));
                $error_result = 'MySQL Error berhasil di-trigger! Error: "' . $db_err . '"';
            }
            break;

        // 6. Slow Query — SELECT SLEEP(3)
        case 'slow_query':
            log_info('Demo: Triggering Slow Query (SLEEP 3s)', array('demo' => true, 'error_type' => 'slow_query'));
            dd_increment('siakad.demo.error_triggered', array('type:slow_query'));
            $start = microtime(true);
            $slow_result = mysqli_query($db_conn, "SELECT SLEEP(3)");
            $duration = round((microtime(true) - $start) * 1000, 2);
            log_warning('Slow query completed (demo)', array(
                'query'       => 'SELECT SLEEP(3)',
                'duration_ms' => $duration
            ));
            dd_timing('siakad.db.query_time', $duration, array('type:slow_query', 'demo:true'));
            $error_result = 'Slow Query berhasil di-trigger! Duration: ' . $duration . 'ms (target: 3000ms)';
            break;

        // 7. Memory Spike — Alokasi array besar
        case 'memory':
            log_info('Demo: Triggering Memory Spike', array('demo' => true, 'error_type' => 'memory'));
            dd_increment('siakad.demo.error_triggered', array('type:memory'));
            $mem_before = memory_get_usage(true);
            $big_array = array();
            for ($i = 0; $i < 100000; $i++) {
                $big_array[] = str_repeat('SIAKAD_MEMORY_TEST_', 10);
            }
            $mem_after = memory_get_usage(true);
            $mem_used = round(($mem_after - $mem_before) / 1024 / 1024, 2);
            log_warning('Memory spike (demo)', array(
                'memory_before' => $mem_before,
                'memory_after'  => $mem_after,
                'memory_used_mb' => $mem_used
            ));
            dd_gauge('siakad.memory.usage_bytes', $mem_after, array('demo:true'));
            unset($big_array);
            $error_result = 'Memory Spike berhasil di-trigger! Memory digunakan: ' . $mem_used . ' MB';
            break;

        // 8. Auth Failure Burst — Simulasi brute-force
        case 'auth_fail':
            log_info('Demo: Triggering Auth Failure burst', array('demo' => true, 'error_type' => 'auth_fail'));
            dd_increment('siakad.demo.error_triggered', array('type:auth_fail'));
            $fail_count = 5;
            for ($i = 0; $i < $fail_count; $i++) {
                log_warning('Login failed (demo burst)', array(
                    'username'  => 'hacker_attempt_' . $i,
                    'reason'    => 'brute_force_simulation',
                    'attempt'   => $i + 1,
                    'ip'        => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown'
                ));
                dd_increment('siakad.login.failure', array('reason:brute_force', 'demo:true'));
                dd_increment('siakad.login.attempt', array('result:failure', 'demo:true'));
            }
            dd_increment('siakad.security.brute_force_detected', array());
            log_error('Possible brute force attack detected (demo)', array(
                'failed_attempts' => $fail_count,
                'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown'
            ));
            $error_result = 'Auth Failure burst berhasil! ' . $fail_count . ' failed login attempts di-log sebagai simulasi brute force.';
            break;

        // 9. Feature Flag Block — Cek fitur yang disabled
        case 'flag_block':
            log_info('Demo: Triggering Feature Flag block', array('demo' => true, 'error_type' => 'flag_block'));
            dd_increment('siakad.demo.error_triggered', array('type:flag_block'));
            // Cek semua flags
            $flags = get_feature_flags($db_conn);
            $blocked_flags = array();
            foreach ($flags as $f) {
                if ((int)$f['is_enabled'] === 0) {
                    $blocked_flags[] = $f['flag_name'];
                    log_warning('Feature blocked access attempt (demo)', array(
                        'flag_name' => $f['flag_name'],
                        'action'    => 'access_denied'
                    ));
                    dd_increment('siakad.feature_flag.blocked', array('flag:' . $f['flag_name'], 'demo:true'));
                }
            }
            if (empty($blocked_flags)) {
                $error_result = 'Semua feature flags sedang ON. Nonaktifkan salah satu flag di Settings dulu, lalu trigger lagi.';
            } else {
                $error_result = 'Feature flag blocks berhasil di-trigger untuk: ' . implode(', ', $blocked_flags);
            }
            break;

        // 10. Custom Application Error — Error bisnis logic
        case 'app_error':
            log_info('Demo: Triggering Application Error', array('demo' => true, 'error_type' => 'app_error'));
            dd_increment('siakad.demo.error_triggered', array('type:app_error'));
            log_error('KRS registration failed: Maximum SKS exceeded', array(
                'mahasiswa_id' => 999,
                'current_sks'  => 24,
                'max_sks'      => 24,
                'requested_mk' => 'IF999',
                'error_code'   => 'KRS_MAX_SKS_EXCEEDED'
            ));
            log_error('Grade input validation failed', array(
                'dosen_id'    => 999,
                'krs_id'      => 999,
                'nilai_tugas' => -5,
                'error_code'  => 'INVALID_GRADE_VALUE'
            ));
            log_error('Transcript generation failed', array(
                'mahasiswa_id' => 999,
                'error_code'   => 'TRANSCRIPT_DATA_INCOMPLETE',
                'missing'      => 'nilai for 3 mata kuliah'
            ));
            dd_increment('siakad.error.business_logic', array('code:KRS_MAX_SKS_EXCEEDED'));
            dd_increment('siakad.error.business_logic', array('code:INVALID_GRADE_VALUE'));
            dd_increment('siakad.error.business_logic', array('code:TRANSCRIPT_DATA_INCOMPLETE'));
            $error_result = 'Application errors berhasil di-trigger! 3 business logic errors di-log (KRS SKS exceeded, invalid grade, transcript incomplete).';
            break;

        default:
            $error_triggered = false;
            $error_result = 'Jenis error tidak dikenali: ' . escape_html($error_type);
            break;
    }
}

// ============================================================
// RENDER UI
// ============================================================
?>

<?php if ($error_triggered && !empty($error_result)): ?>
    <div class="alert alert-info">
        <span class="alert-text">✅ <?php echo escape_html($error_result); ?></span>
        <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>🧪 Demo Error Simulator — Datadog Monitoring</h3>
        <p>Klik tombol di bawah untuk men-trigger berbagai jenis error. Setiap error akan dikirim ke <strong>Datadog</strong> sebagai log dan metric.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">

        <!-- PHP Notice -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <span style="font-size: 24px;">⚠️</span>
                <div>
                    <strong style="color: var(--text-heading);">PHP Notice</strong>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Undefined variable access</p>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-body); margin-bottom: 12px;">Akses variabel yang belum didefinisikan → muncul sebagai <strong>WARNING</strong> di Datadog Logs.</p>
            <a href="index.php?page=demo_errors&error=notice" class="btn btn-secondary btn-sm">Trigger Notice</a>
        </div>

        <!-- PHP Warning -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <span style="font-size: 24px;">🟡</span>
                <div>
                    <strong style="color: var(--text-heading);">PHP Warning</strong>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Division by zero</p>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-body); margin-bottom: 12px;">Pembagian dengan nol → muncul sebagai <strong>WARNING</strong> di Datadog Logs.</p>
            <a href="index.php?page=demo_errors&error=warning" class="btn btn-secondary btn-sm">Trigger Warning</a>
        </div>

        <!-- User Error -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <span style="font-size: 24px;">🔴</span>
                <div>
                    <strong style="color: var(--text-heading);">PHP User Error</strong>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">trigger_error(E_USER_ERROR)</p>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-body); margin-bottom: 12px;">Trigger user error via trigger_error() → muncul sebagai <strong>ERROR</strong> di Datadog Logs. <span style="color: var(--danger-text);">⚠️ Akan menghentikan script!</span></p>
            <a href="index.php?page=demo_errors&error=user_error" class="btn btn-danger btn-sm" onclick="return confirm('Error ini akan menghentikan eksekusi halaman. Lanjutkan?');">Trigger User Error</a>
        </div>

        <!-- Exception -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <span style="font-size: 24px;">💥</span>
                <div>
                    <strong style="color: var(--text-heading);">Exception</strong>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">throw new Exception()</p>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-body); margin-bottom: 12px;">Throw dan catch exception → muncul sebagai <strong>CRITICAL</strong> di Datadog Logs + stack trace.</p>
            <a href="index.php?page=demo_errors&error=exception" class="btn btn-secondary btn-sm">Trigger Exception</a>
        </div>

        <!-- Database Error -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <span style="font-size: 24px;">🗄️</span>
                <div>
                    <strong style="color: var(--text-heading);">Database Error</strong>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Query invalid table</p>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-body); margin-bottom: 12px;">Query ke tabel yang tidak ada → muncul sebagai <strong>ERROR</strong> + MySQL error di Datadog.</p>
            <a href="index.php?page=demo_errors&error=db_error" class="btn btn-secondary btn-sm">Trigger DB Error</a>
        </div>

        <!-- Slow Query -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <span style="font-size: 24px;">🐢</span>
                <div>
                    <strong style="color: var(--text-heading);">Slow Query</strong>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">SELECT SLEEP(3) — 3 detik</p>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-body); margin-bottom: 12px;">Eksekusi query yang sengaja lambat → muncul sebagai <strong>slow trace</strong> di Datadog APM.</p>
            <a href="index.php?page=demo_errors&error=slow_query" class="btn btn-secondary btn-sm">Trigger Slow Query</a>
        </div>

        <!-- Memory Spike -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <span style="font-size: 24px;">📈</span>
                <div>
                    <strong style="color: var(--text-heading);">Memory Spike</strong>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">100K string allocations</p>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-body); margin-bottom: 12px;">Alokasi array besar 100K entries → muncul sebagai <strong>memory gauge spike</strong> di Datadog.</p>
            <a href="index.php?page=demo_errors&error=memory" class="btn btn-secondary btn-sm">Trigger Memory Spike</a>
        </div>

        <!-- Auth Failure Burst -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <span style="font-size: 24px;">🔐</span>
                <div>
                    <strong style="color: var(--text-heading);">Auth Failure Burst</strong>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">5x failed login attempts</p>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-body); margin-bottom: 12px;">Simulasi brute-force (5x failed login) → muncul sebagai <strong>security alert</strong> di Datadog.</p>
            <a href="index.php?page=demo_errors&error=auth_fail" class="btn btn-secondary btn-sm">Trigger Auth Burst</a>
        </div>

        <!-- Feature Flag Block -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <span style="font-size: 24px;">🚩</span>
                <div>
                    <strong style="color: var(--text-heading);">Feature Flag Block</strong>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Access disabled features</p>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-body); margin-bottom: 12px;">Cek flag yang OFF → muncul sebagai <strong>WARNING</strong> + feature_flag.blocked metric.</p>
            <a href="index.php?page=demo_errors&error=flag_block" class="btn btn-secondary btn-sm">Trigger Flag Block</a>
        </div>

        <!-- Application Error -->
        <div class="card" style="margin-bottom: 0;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <span style="font-size: 24px;">🏫</span>
                <div>
                    <strong style="color: var(--text-heading);">Application Errors</strong>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Business logic failures</p>
                </div>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-body); margin-bottom: 12px;">3 business error (KRS SKS exceeded, invalid grade, incomplete transcript) → muncul sebagai <strong>ERROR</strong>.</p>
            <a href="index.php?page=demo_errors&error=app_error" class="btn btn-secondary btn-sm">Trigger App Errors</a>
        </div>

    </div>
</div>

<!-- Datadog Info -->
<div class="card">
    <div class="card-header">
        <h3>📊 Apa yang Dikirim ke Datadog?</h3>
    </div>
    <div style="font-size: 0.9rem; color: var(--text-body);">
        <p style="margin-bottom: 16px;">Setiap error yang di-trigger mengirim data ke <strong>2 channel</strong>:</p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <strong style="color: var(--accent);">📝 Structured Logs</strong>
                <ul style="margin: 8px 0 0 16px; line-height: 1.8;">
                    <li>JSON format (auto-parsed oleh Datadog)</li>
                    <li>Severity level (DEBUG → CRITICAL)</li>
                    <li>Request context (URI, method, IP)</li>
                    <li>User context (ID, name, role)</li>
                    <li>Stack trace untuk exceptions</li>
                    <li>File: <code>/var/log/siakad/app.log</code></li>
                </ul>
            </div>
            <div>
                <strong style="color: var(--accent);">📈 DogStatsD Metrics</strong>
                <ul style="margin: 8px 0 0 16px; line-height: 1.8;">
                    <li>Counter: <code>siakad.error.count</code></li>
                    <li>Counter: <code>siakad.login.failure</code></li>
                    <li>Counter: <code>siakad.demo.error_triggered</code></li>
                    <li>Timing: <code>siakad.db.query_time</code></li>
                    <li>Gauge: <code>siakad.memory.usage_bytes</code></li>
                    <li>Tags: env, service, version, error_type</li>
                </ul>
            </div>
        </div>
    </div>
</div>
