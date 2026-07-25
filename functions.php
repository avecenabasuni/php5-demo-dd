<?php
/**
 * Fungsi Utility, Autentikasi, Feature Flags & Flash Messages - SIAKAD PHP 5 Compatible
 *
 * KOMPATIBILITAS PHP 5:
 * - array() bukan []
 * - isset($v) ? $v : $d bukan ??
 * - Tanpa type hints / return type
 * - Procedural mysqli_* saja
 * - htmlspecialchars ENT_QUOTES tanpa ENT_HTML5
 */

// ============================================================
// UTILITAS UMUM
// ============================================================

/**
 * Escape output HTML untuk mencegah XSS
 */
function escape_html($data) {
    if ($data === null) {
        return '';
    }
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitasi input string
 */
function sanitize_input($data) {
    if (!isset($data) || $data === null) {
        return '';
    }
    return trim((string)$data);
}

/**
 * Redirect ke URL lain dan hentikan eksekusi
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// ============================================================
// FLASH MESSAGES (Notifikasi via Session)
// ============================================================

/**
 * Set flash message ke session
 * $type: 'success', 'error', 'warning', 'info'
 */
function set_flash($type, $message) {
    // PHP 5: Menggunakan array() bukan []
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = array();
    }
    $_SESSION['flash_messages'][] = array(
        'type' => $type,
        'message' => $message
    );
}

/**
 * Ambil dan hapus flash messages dari session
 */
function get_flash_messages() {
    if (isset($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    return array();
}

/**
 * Render HTML flash messages
 */
function display_flash_messages() {
    $messages = get_flash_messages();
    $html = '';
    foreach ($messages as $msg) {
        $type = escape_html($msg['type']);
        $message = escape_html($msg['message']);
        $html .= '<div class="alert alert-' . $type . '">';
        $html .= '<span class="alert-text">' . $message . '</span>';
        $html .= '<button class="alert-close" onclick="this.parentElement.style.display=\'none\'">&times;</button>';
        $html .= '</div>';
    }
    return $html;
}

// ============================================================
// AUTENTIKASI & OTORISASI
// ============================================================

/**
 * Cek apakah user sudah login
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Paksa user login; redirect ke halaman login jika belum
 */
function require_login() {
    if (!is_logged_in()) {
        set_flash('error', 'Silakan login terlebih dahulu.');
        redirect('index.php?page=login');
    }
}

/**
 * Cek apakah user memiliki role tertentu
 */
function has_role($role) {
    // PHP 5: Menggunakan isset() ? : bukan ??
    $user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
    return $user_role === $role;
}

/**
 * Paksa user memiliki role tertentu; tampilkan error jika tidak sesuai
 * $roles bisa string tunggal atau array of string
 */
function require_role($roles) {
    require_login();
    if (is_string($roles)) {
        $roles = array($roles);
    }
    $user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
    if (!in_array($user_role, $roles)) {
        set_flash('error', 'Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
        redirect('index.php?page=dashboard');
    }
}

/**
 * Verifikasi login dari form
 */
function attempt_login($conn, $username, $password) {
    $username = mysqli_real_escape_string($conn, sanitize_input($username));
    $password_hash = md5($password);

    $sql = "SELECT id, username, role, nama FROM users WHERE username = '$username' AND password_hash = '$password_hash' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_nama'] = $user['nama'];

        // Simpan ID mahasiswa/dosen jika role sesuai
        if ($user['role'] === 'mahasiswa') {
            $mhs_sql = "SELECT id FROM mahasiswa WHERE user_id = " . (int)$user['id'] . " LIMIT 1";
            $mhs_result = mysqli_query($conn, $mhs_sql);
            if ($mhs_result && mysqli_num_rows($mhs_result) === 1) {
                $mhs = mysqli_fetch_assoc($mhs_result);
                $_SESSION['mahasiswa_id'] = $mhs['id'];
            }
        } elseif ($user['role'] === 'dosen') {
            $dsn_sql = "SELECT id FROM dosen WHERE user_id = " . (int)$user['id'] . " LIMIT 1";
            $dsn_result = mysqli_query($conn, $dsn_sql);
            if ($dsn_result && mysqli_num_rows($dsn_result) === 1) {
                $dsn = mysqli_fetch_assoc($dsn_result);
                $_SESSION['dosen_id'] = $dsn['id'];
            }
        }

        // Datadog: Log login berhasil + increment metric
        log_info('Login successful', array(
            'username' => $username,
            'role'     => $user['role'],
            'user_id'  => $user['id']
        ));
        dd_increment('siakad.login.success', array('role:' . $user['role']));
        dd_increment('siakad.login.attempt', array('result:success'));

        return true;
    }

    // Datadog: Log login gagal + increment metric
    log_warning('Login failed', array(
        'username' => $username,
        'reason'   => 'invalid_credentials'
    ));
    dd_increment('siakad.login.failure', array('reason:invalid_credentials'));
    dd_increment('siakad.login.attempt', array('result:failure'));

    return false;
}

/**
 * Logout: hapus session
 */
function do_logout() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// ============================================================
// FEATURE FLAGS
// ============================================================

/**
 * Cek apakah suatu feature flag aktif
 */
function is_feature_enabled($conn, $flag_name) {
    $flag_name_escaped = mysqli_real_escape_string($conn, $flag_name);
    $sql = "SELECT is_enabled FROM feature_flags WHERE flag_name = '$flag_name_escaped' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        $enabled = (int)$row['is_enabled'] === 1;

        // Datadog: Log feature flag check
        log_debug('Feature flag checked', array(
            'flag_name' => $flag_name,
            'enabled'   => $enabled
        ));

        // Jika flag OFF, log warning dan kirim metric
        if (!$enabled) {
            dd_increment('siakad.feature_flag.blocked', array('flag:' . $flag_name));
        }

        return $enabled;
    }
    // Default: disabled jika flag tidak ditemukan
    log_warning('Feature flag not found', array('flag_name' => $flag_name));
    return false;
}

/**
 * Ambil semua feature flags
 */
function get_feature_flags($conn) {
    $sql = "SELECT * FROM feature_flags ORDER BY id ASC";
    $result = mysqli_query($conn, $sql);
    $flags = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $flags[] = $row;
        }
    }
    return $flags;
}

/**
 * Toggle feature flag on/off
 */
function toggle_feature_flag($conn, $flag_id) {
    $flag_id = (int)$flag_id;
    $sql = "UPDATE feature_flags SET is_enabled = IF(is_enabled = 1, 0, 1) WHERE id = $flag_id";
    return mysqli_query($conn, $sql);
}

// ============================================================
// KALKULASI AKADEMIK
// ============================================================

/**
 * Hitung nilai akhir: Tugas 30%, UTS 30%, UAS 40%
 */
function calculate_final_score($tugas, $uts, $uas) {
    return round(($tugas * 0.30) + ($uts * 0.30) + ($uas * 0.40), 2);
}

/**
 * Konversi nilai akhir ke grade huruf
 */
function calculate_grade($nilai_akhir) {
    if ($nilai_akhir >= 80) return 'A';
    if ($nilai_akhir >= 70) return 'B';
    if ($nilai_akhir >= 60) return 'C';
    if ($nilai_akhir >= 50) return 'D';
    return 'E';
}

/**
 * Konversi grade huruf ke bobot angka (untuk IPK)
 */
function grade_to_point($grade) {
    // PHP 5: Menggunakan array() untuk mapping
    $map = array(
        'A' => 4.0,
        'B' => 3.0,
        'C' => 2.0,
        'D' => 1.0,
        'E' => 0.0
    );
    return isset($map[$grade]) ? $map[$grade] : 0.0;
}

/**
 * Hitung IPK dari array KRS + Nilai
 * Rumus: SUM(bobot * sks) / SUM(sks)
 */
function calculate_ipk($conn, $mahasiswa_id) {
    $mahasiswa_id = (int)$mahasiswa_id;
    $sql = "SELECT n.grade, mk.sks
            FROM nilai n
            JOIN krs k ON n.krs_id = k.id
            JOIN mata_kuliah mk ON k.mata_kuliah_id = mk.id
            WHERE k.mahasiswa_id = $mahasiswa_id
            AND k.status = 'approved'
            AND n.grade != ''";
    $result = mysqli_query($conn, $sql);

    $total_bobot = 0;
    $total_sks = 0;

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $point = grade_to_point($row['grade']);
            $sks = (int)$row['sks'];
            $total_bobot += $point * $sks;
            $total_sks += $sks;
        }
    }

    if ($total_sks === 0) {
        return 0.00;
    }

    return round($total_bobot / $total_sks, 2);
}

// ============================================================
// DATABASE QUERY HELPERS
// ============================================================

/**
 * Hitung total baris dari sebuah tabel (untuk dashboard / pagination)
 */
function count_rows($conn, $table, $where) {
    $sql = "SELECT COUNT(*) AS total FROM $table";
    if (!empty($where)) {
        $sql .= " WHERE $where";
    }
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return (int)$row['total'];
    }
    return 0;
}

/**
 * Ambil satu baris dari query
 */
function fetch_one($conn, $sql) {
    $start = microtime(true);
    $result = mysqli_query($conn, $sql);
    $duration_ms = round((microtime(true) - $start) * 1000, 2);

    // Datadog: Log query timing
    dd_timing('siakad.db.query_time', $duration_ms, array('type:fetch_one'));

    // Log slow queries (> 500ms)
    if ($duration_ms > 500) {
        log_warning('Slow DB query detected', array(
            'query'       => substr($sql, 0, 200),
            'duration_ms' => $duration_ms
        ));
    }

    // Log DB errors
    if (!$result) {
        log_error('Database query failed', array(
            'query' => substr($sql, 0, 200),
            'error' => mysqli_error($conn)
        ));
        dd_increment('siakad.db.error', array('type:fetch_one'));
        return null;
    }

    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

/**
 * Ambil semua baris dari query
 */
function fetch_all($conn, $sql) {
    $start = microtime(true);
    $result = mysqli_query($conn, $sql);
    $duration_ms = round((microtime(true) - $start) * 1000, 2);

    // Datadog: Log query timing
    dd_timing('siakad.db.query_time', $duration_ms, array('type:fetch_all'));

    // Log slow queries (> 500ms)
    if ($duration_ms > 500) {
        log_warning('Slow DB query detected', array(
            'query'       => substr($sql, 0, 200),
            'duration_ms' => $duration_ms
        ));
    }

    // Log DB errors
    if (!$result) {
        log_error('Database query failed', array(
            'query' => substr($sql, 0, 200),
            'error' => mysqli_error($conn)
        ));
        dd_increment('siakad.db.error', array('type:fetch_all'));
        return array();
    }

    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Escape dan wrap string untuk SQL
 */
function sql_escape($conn, $value) {
    return "'" . mysqli_real_escape_string($conn, sanitize_input($value)) . "'";
}
