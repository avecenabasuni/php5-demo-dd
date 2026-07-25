<?php
/**
 * Structured Logger & DogStatsD Client - SIAKAD PHP 5 Compatible
 *
 * Menulis structured JSON logs ke file untuk di-consume oleh Datadog Agent.
 * Juga menyediakan fungsi DogStatsD untuk mengirim custom metrics via UDP.
 *
 * KOMPATIBILITAS PHP 5:
 * - json_encode() tersedia sejak PHP 5.2
 * - microtime(true) tersedia sejak PHP 5.0
 * - fsockopen('udp://') tersedia sejak PHP 5.0
 * - Tidak menggunakan fitur PHP 7+
 */

// ============================================================
// KONFIGURASI LOGGING
// ============================================================

// Path log file — di server: /var/log/siakad/app.log
// Di Windows dev environment, fallback ke direktori lokal
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    define('LOG_FILE', dirname(__FILE__) . '/logs/app.log');
} else {
    define('LOG_FILE', '/var/log/siakad/app.log');
}

// Level log minimum yang akan ditulis (DEBUG, INFO, WARNING, ERROR, CRITICAL)
define('LOG_LEVEL', 'DEBUG');

// DogStatsD host dan port (Datadog Agent default)
define('DOGSTATSD_HOST', '127.0.0.1');
define('DOGSTATSD_PORT', 8125);

// Service metadata untuk Datadog
define('DD_SERVICE', 'siakad');
define('DD_ENV', 'demo');
define('DD_VERSION', '1.0.0');

// Catat waktu mulai request (microtime float)
$_SERVER['SIAKAD_REQUEST_START'] = microtime(true);

// ============================================================
// LOGGING FUNCTIONS
// ============================================================

/**
 * Level log priorities (semakin tinggi = semakin penting)
 */
function _log_level_priority($level) {
    $levels = array(
        'DEBUG'    => 0,
        'INFO'     => 1,
        'WARNING'  => 2,
        'ERROR'    => 3,
        'CRITICAL' => 4
    );
    return isset($levels[$level]) ? $levels[$level] : 0;
}

/**
 * Menulis structured log entry ke file
 *
 * @param string $level   Log level: DEBUG, INFO, WARNING, ERROR, CRITICAL
 * @param string $message Pesan log
 * @param array  $context Data tambahan (key-value pairs)
 */
function app_log($level, $message, $context) {
    // Cek apakah level memenuhi minimum
    if (_log_level_priority($level) < _log_level_priority(LOG_LEVEL)) {
        return;
    }

    // Buat log entry sebagai JSON
    $entry = array(
        'timestamp'  => gmdate('Y-m-d\TH:i:s\Z'),
        'level'      => strtoupper($level),
        'message'    => $message,
        'service'    => DD_SERVICE,
        'env'        => DD_ENV,
        'version'    => DD_VERSION,
        'logger'     => array('name' => 'siakad.app'),
        'request'    => array(
            'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CLI',
            'uri'    => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            'ip'     => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
        ),
        'usr'        => array(
            'id'     => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
            'name'   => isset($_SESSION['user_nama']) ? $_SESSION['user_nama'] : null,
            'role'   => isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null
        )
    );

    // Tambahkan context data
    if (!empty($context)) {
        $entry['context'] = $context;
    }

    // Tambahkan session_id untuk tracing
    if (function_exists('session_id') && session_id() !== '') {
        $entry['session_id'] = session_id();
    }

    // Tulis ke file (satu JSON per baris)
    $log_dir = dirname(LOG_FILE);
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }

    $json_line = json_encode($entry) . "\n";
    @file_put_contents(LOG_FILE, $json_line, FILE_APPEND | LOCK_EX);
}

/**
 * Shortcut: Log INFO
 */
function log_info($message, $context) {
    app_log('INFO', $message, $context);
}

/**
 * Shortcut: Log WARNING
 */
function log_warning($message, $context) {
    app_log('WARNING', $message, $context);
}

/**
 * Shortcut: Log ERROR
 */
function log_error($message, $context) {
    app_log('ERROR', $message, $context);
}

/**
 * Shortcut: Log CRITICAL
 */
function log_critical($message, $context) {
    app_log('CRITICAL', $message, $context);
}

/**
 * Shortcut: Log DEBUG
 */
function log_debug($message, $context) {
    app_log('DEBUG', $message, $context);
}

// ============================================================
// PHP ERROR HANDLER (mengirim PHP errors ke logger)
// ============================================================

/**
 * Custom error handler — menangkap Notice, Warning, dll.
 * dan mengirim ke structured log.
 */
function siakad_error_handler($errno, $errstr, $errfile, $errline) {
    // Map PHP error codes ke log levels
    $error_levels = array(
        E_NOTICE         => 'WARNING',
        E_USER_NOTICE    => 'WARNING',
        E_WARNING        => 'WARNING',
        E_USER_WARNING   => 'WARNING',
        E_STRICT         => 'WARNING',
        E_DEPRECATED     => 'WARNING',
        E_USER_DEPRECATED => 'WARNING',
        E_ERROR          => 'ERROR',
        E_USER_ERROR     => 'ERROR',
        E_RECOVERABLE_ERROR => 'ERROR'
    );

    $level = isset($error_levels[$errno]) ? $error_levels[$errno] : 'ERROR';

    // Map error code ke nama
    $error_names = array(
        E_NOTICE         => 'E_NOTICE',
        E_USER_NOTICE    => 'E_USER_NOTICE',
        E_WARNING        => 'E_WARNING',
        E_USER_WARNING   => 'E_USER_WARNING',
        E_STRICT         => 'E_STRICT',
        E_DEPRECATED     => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        E_ERROR          => 'E_ERROR',
        E_USER_ERROR     => 'E_USER_ERROR',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR'
    );

    $error_name = isset($error_names[$errno]) ? $error_names[$errno] : 'UNKNOWN_' . $errno;

    app_log($level, 'PHP ' . $error_name . ': ' . $errstr, array(
        'error_type' => $error_name,
        'error_code' => $errno,
        'file'       => $errfile,
        'line'       => $errline
    ));

    // Increment error counter di DogStatsD
    dd_increment('siakad.error.count', array(
        'error_type:' . strtolower($error_name),
        'level:' . strtolower($level)
    ));

    // Return false agar PHP tetap handle error-nya (tampilkan error jika display_errors=on)
    return false;
}

/**
 * Shutdown handler — menangkap fatal errors
 */
function siakad_shutdown_handler() {
    $error = error_get_last();
    if ($error !== null) {
        $fatal_errors = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR);
        if (in_array($error['type'], $fatal_errors)) {
            $error_names = array(
                E_ERROR         => 'E_ERROR',
                E_PARSE         => 'E_PARSE',
                E_CORE_ERROR    => 'E_CORE_ERROR',
                E_COMPILE_ERROR => 'E_COMPILE_ERROR'
            );
            $error_name = isset($error_names[$error['type']]) ? $error_names[$error['type']] : 'FATAL';

            app_log('CRITICAL', 'PHP FATAL: ' . $error['message'], array(
                'error_type' => $error_name,
                'file'       => $error['file'],
                'line'       => $error['line']
            ));

            dd_increment('siakad.error.count', array(
                'error_type:fatal',
                'level:critical'
            ));
        }
    }

    // Log request selesai + response time
    if (isset($_SERVER['SIAKAD_REQUEST_START'])) {
        $duration_ms = round((microtime(true) - $_SERVER['SIAKAD_REQUEST_START']) * 1000, 2);
        $page = isset($_GET['page']) ? $_GET['page'] : 'index';

        log_info('Request completed', array(
            'page'        => $page,
            'duration_ms' => $duration_ms,
            'memory_peak' => memory_get_peak_usage(true)
        ));

        dd_timing('siakad.page.load_time', $duration_ms, array(
            'page:' . $page
        ));
    }
}

// Register handlers
set_error_handler('siakad_error_handler');
register_shutdown_function('siakad_shutdown_handler');

// ============================================================
// DOGSTATSD CLIENT (Custom Metrics via UDP)
// ============================================================

/**
 * Kirim metric ke Datadog Agent via DogStatsD (UDP)
 *
 * @param string $stat   Metric name (e.g. 'siakad.login.success')
 * @param string $value  Metric value
 * @param string $type   Metric type: 'c' (count), 'g' (gauge), 'ms' (timing)
 * @param array  $tags   Array of tags (e.g. array('env:demo', 'page:login'))
 */
function dd_send($stat, $value, $type, $tags) {
    // Build DogStatsD datagram
    $message = $stat . ':' . $value . '|' . $type;

    // Tambah tags
    $default_tags = array(
        'env:' . DD_ENV,
        'service:' . DD_SERVICE,
        'version:' . DD_VERSION
    );
    $all_tags = array_merge($default_tags, $tags);
    $message .= '|#' . implode(',', $all_tags);

    // Kirim via UDP (fire-and-forget, tidak blocking)
    $fp = @fsockopen('udp://' . DOGSTATSD_HOST, DOGSTATSD_PORT, $errno, $errstr, 0.5);
    if ($fp) {
        @fwrite($fp, $message);
        @fclose($fp);
    }
    // Jika gagal kirim, abaikan saja (jangan crash aplikasi)
}

/**
 * Increment counter metric
 */
function dd_increment($stat, $tags) {
    if (!is_array($tags)) $tags = array();
    dd_send($stat, '1', 'c', $tags);
}

/**
 * Decrement counter metric
 */
function dd_decrement($stat, $tags) {
    if (!is_array($tags)) $tags = array();
    dd_send($stat, '-1', 'c', $tags);
}

/**
 * Set gauge metric
 */
function dd_gauge($stat, $value, $tags) {
    if (!is_array($tags)) $tags = array();
    dd_send($stat, $value, 'g', $tags);
}

/**
 * Record timing metric (milliseconds)
 */
function dd_timing($stat, $ms, $tags) {
    if (!is_array($tags)) $tags = array();
    dd_send($stat, $ms, 'ms', $tags);
}

// ============================================================
// REQUEST LOGGING (auto-log setiap request masuk)
// ============================================================

log_info('Request started', array(
    'page' => isset($_GET['page']) ? $_GET['page'] : 'index'
));
