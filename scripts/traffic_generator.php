<?php
/**
 * User Traffic Generator CLI Script - SIAKAD PHP 5 Compatible
 *
 * Script ini mensimulasikan trafik pengguna nyata (Admin, Dosen, Mahasiswa)
 * dan error injection secara acak untuk memicu log, metrics, dan APM trace di Datadog.
 *
 * Penggunaan CLI:
 *   php scripts/traffic_generator.php [options]
 *
 * Option:
 *   --url=<URL>       Base URL ke index.php (Default: http://localhost/php5-demo-dd/index.php)
 *   --count=<N>       Jumlah iterasi skenario (Default: 20, 0 = loop selamanya)
 *   --mode=<MODE>     Mode: normal, heavy, error (Default: normal)
 *   --delay=<SEC>     Delay acak maksimum dalam detik antar request (Default: 2)
 *   --verbose         Tampilkan detail log HTTP ke STDOUT
 */

// Pastikan script hanya dijalankan via CLI atau dipanggil dengan parameter yang aman
if (php_sapi_name() !== 'cli' && !defined('SIAKAD_TRAFFIC_WEB_RUNNER')) {
    die("Script ini hanya dapat dijalankan dari CLI atau via Traffic Control Panel.");
}

// Set time limit 0 untuk long running
@set_time_limit(0);
@ini_set('memory_limit', '128M');

// Parse CLI arguments
$options = array(
    'url'     => 'http://localhost/php5-demo-dd/index.php',
    'count'   => 20,
    'mode'    => 'normal',
    'delay'   => 2,
    'verbose' => false
);

// Jika dari CLI, parse $argv
if (isset($argv) && is_array($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--url=') === 0) {
            $options['url'] = substr($arg, 6);
        } elseif (strpos($arg, '--count=') === 0) {
            $options['count'] = (int)substr($arg, 8);
        } elseif (strpos($arg, '--mode=') === 0) {
            $options['mode'] = strtolower(substr($arg, 7));
        } elseif (strpos($arg, '--delay=') === 0) {
            $options['delay'] = (int)substr($arg, 8);
        } elseif ($arg === '--verbose' || $arg === '-v') {
            $options['verbose'] = true;
        }
    }
}

// Tulisan Output helper
function print_log($msg, $verbose_only = false) {
    global $options;
    if ($verbose_only && !$options['verbose']) {
        return;
    }
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] $msg\n";
    @flush();
}

/**
 * Class HTTP Client sederhana (Mendukung cURL maupun stream fallback)
 */
class SimpleHttpClient {
    var $cookie_file;

    function SimpleHttpClient() {
        // PHP 4/5 Constructor
        $this->cookie_file = tempnam(sys_get_temp_dir(), 'siakad_cookie_');
    }

    function request($url, $method = 'GET', $post_data = array()) {
        if (function_exists('curl_init')) {
            return $this->request_curl($url, $method, $post_data);
        } else {
            return $this->request_stream($url, $method, $post_data);
        }
    }

    function request_curl($url, $method = 'GET', $post_data = array()) {
        $ch = curl_init();
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        } else if (!empty($post_data)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($post_data);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SIAKAD-TrafficSim/1.0 (Datadog Demo Generator)');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $start = microtime(true);
        $response = curl_exec($ch);
        $duration = round((microtime(true) - $start) * 1000, 2);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return array(
                'success'   => false,
                'http_code' => 0,
                'duration'  => $duration,
                'body'      => '',
                'error'     => $error_msg
            );
        }

        curl_close($ch);

        return array(
            'success'   => ($http_code >= 200 && $http_code < 400),
            'http_code' => $http_code,
            'duration'  => $duration,
            'body'      => $response,
            'error'     => ''
        );
    }

    function request_stream($url, $method = 'GET', $post_data = array()) {
        $headers = array("User-Agent: SIAKAD-TrafficSim/1.0 (Datadog Demo Generator)");
        
        // Membaca cookie yang tersimpan
        $cookies = array();
        if (file_exists($this->cookie_file)) {
            $cookie_raw = @file_get_contents($this->cookie_file);
            if (!empty($cookie_raw)) {
                $lines = explode("\n", $cookie_raw);
                foreach ($lines as $line) {
                    $parts = explode("\t", trim($line));
                    if (count($parts) >= 7) {
                        $cookies[] = $parts[5] . '=' . $parts[6];
                    }
                }
            }
        }
        if (!empty($cookies)) {
            $headers[] = "Cookie: " . implode('; ', $cookies);
        }

        $opts = array(
            'http' => array(
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'timeout' => 15,
                'ignore_errors' => true
            )
        );

        if ($method === 'POST') {
            $content = http_build_query($post_data);
            $opts['http']['header'] .= "\r\nContent-Type: application/x-www-form-urlencoded";
            $opts['http']['header'] .= "\r\nContent-Length: " . strlen($content);
            $opts['http']['content'] = $content;
        } else if (!empty($post_data)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($post_data);
        }

        $context = stream_context_create($opts);
        $start = microtime(true);
        $response = @file_get_contents($url, false, $context);
        $duration = round((microtime(true) - $start) * 1000, 2);

        $http_code = 200;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $hdr) {
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/i', $hdr, $matches)) {
                    $http_code = (int)$matches[1];
                }
                if (preg_match('/Set-Cookie:\s*([^;]+)/i', $hdr, $matches)) {
                    $c_pair = trim($matches[1]);
                    $c_name = substr($c_pair, 0, strpos($c_pair, '='));
                    $c_val  = substr($c_pair, strpos($c_pair, '=') + 1);
                    @file_put_contents($this->cookie_file, "domain\tTRUE\t/\tFALSE\t0\t$c_name\t$c_val\n", FILE_APPEND);
                }
            }
        }

        return array(
            'success'   => ($http_code >= 200 && $http_code < 400),
            'http_code' => $http_code,
            'duration'  => $duration,
            'body'      => ($response !== false) ? $response : '',
            'error'     => ($response === false) ? 'HTTP Request Failed' : ''
        );
    }

    function cleanup() {
        if (file_exists($this->cookie_file)) {
            @unlink($this->cookie_file);
        }
    }
}

// List Pengguna Demo
$users_pool = array(
    'admin' => array('user' => 'admin', 'pass' => 'admin123', 'role' => 'admin'),
    'dosen1' => array('user' => 'dosen1', 'pass' => 'dosen123', 'role' => 'dosen'),
    'dosen2' => array('user' => 'dosen2', 'pass' => 'dosen123', 'role' => 'dosen'),
    'mhs1' => array('user' => 'mhs1', 'pass' => 'mhs123', 'role' => 'mahasiswa'),
    'mhs2' => array('user' => 'mhs2', 'pass' => 'mhs123', 'role' => 'mahasiswa'),
    'mhs3' => array('user' => 'mhs3', 'pass' => 'mhs123', 'role' => 'mahasiswa')
);

// Halaman-halaman SIAKAD untuk disimulasikan
$admin_pages = array('dashboard', 'mahasiswa', 'dosen', 'matakuliah', 'krs', 'settings');
$dosen_pages = array('dashboard', 'nilai');
$mhs_pages   = array('dashboard', 'krs', 'transkrip');
$demo_errors = array('notice', 'warning', 'exception', 'db_error', 'slow_query', 'memory', 'auth_fail', 'flag_block', 'app_error');

print_log("=== SIAKAD USER TRAFFIC GENERATOR STARTED ===");
print_log("Target URL : " . $options['url']);
print_log("Mode       : " . strtoupper($options['mode']));
print_log("Iterasi    : " . ($options['count'] === 0 ? 'UNLIMITED' : $options['count']));
print_log("Delay Max  : " . $options['delay'] . " detik");
print_log("HTTP Engine: " . (function_exists('curl_init') ? 'cURL Extension' : 'PHP Stream Fallback'));
print_log("----------------------------------------------");

$iteration = 0;
$total_requests = 0;
$total_success  = 0;
$total_failed   = 0;

while ($options['count'] === 0 || $iteration < $options['count']) {
    $iteration++;
    print_log("--- Iterasi #$iteration ---");

    // Pilih flow yang akan dijalankan berdasarkan mode
    $rand_val = rand(1, 100);

    // Tentukan jenis skenario
    if ($options['mode'] === 'error' && $rand_val <= 60) {
        $scenario_type = 'error_injection';
    } elseif ($options['mode'] === 'heavy') {
        $scenario_type = 'user_session';
    } else {
        // Mode Normal: 75% User Session, 15% Error/Failed Login, 10% 404 Page
        if ($rand_val <= 75) {
            $scenario_type = 'user_session';
        } elseif ($rand_val <= 90) {
            $scenario_type = 'error_injection';
        } else {
            $scenario_type = 'not_found';
        }
    }

    $client = new SimpleHttpClient();

    if ($scenario_type === 'user_session') {
        // Pick a random user from pool
        $user_keys = array_keys($users_pool);
        $user_key  = $user_keys[array_rand($user_keys)];
        $user_data = $users_pool[$user_key];

        print_log("Simulasi Session User: " . $user_data['user'] . " (" . strtoupper($user_data['role']) . ")");

        // Step 1: Login
        $res = $client->request($options['url'] . '?page=login', 'POST', array(
            'username' => $user_data['user'],
            'password' => $user_data['pass']
        ));
        $total_requests++;
        if ($res['success']) { $total_success++; } else { $total_failed++; }
        print_log("  -> POST ?page=login [" . $res['http_code'] . "] (" . $res['duration'] . "ms)", true);

        // Step 2: Browse pages according to role
        $pages = array();
        if ($user_data['role'] === 'admin') $pages = $admin_pages;
        elseif ($user_data['role'] === 'dosen') $pages = $dosen_pages;
        else $pages = $mhs_pages;

        // Shuffle and visit 2-4 pages
        shuffle($pages);
        $visit_count = min(count($pages), rand(2, 4));

        for ($i = 0; $i < $visit_count; $i++) {
            $page = $pages[$i];
            $res = $client->request($options['url'] . '?page=' . $page);
            $total_requests++;
            if ($res['success']) { $total_success++; } else { $total_failed++; }
            print_log("  -> GET ?page=$page [" . $res['http_code'] . "] (" . $res['duration'] . "ms)", true);

            // Jeda antar request halaman
            if ($options['delay'] > 0 && $options['mode'] !== 'heavy') {
                usleep(rand(100000, $options['delay'] * 1000000));
            }
        }

        // Step 3: Logout
        $res = $client->request($options['url'] . '?page=logout');
        $total_requests++;
        if ($res['success']) { $total_success++; } else { $total_failed++; }
        print_log("  -> GET ?page=logout [" . $res['http_code'] . "] (" . $res['duration'] . "ms)", true);

    } elseif ($scenario_type === 'error_injection') {
        $err_type = $demo_errors[array_rand($demo_errors)];
        print_log("Simulasi Inject Error: " . strtoupper($err_type));

        if ($err_type === 'auth_fail') {
            // Simulasi Login Gagal
            $res = $client->request($options['url'] . '?page=login', 'POST', array(
                'username' => 'wrong_user_' . rand(100, 999),
                'password' => 'invalid_pass_' . rand(100, 999)
            ));
            $total_requests++;
            if ($res['http_code'] > 0) $total_success++; else $total_failed++;
            print_log("  -> POST ?page=login (FAILED LOGIN) [" . $res['http_code'] . "] (" . $res['duration'] . "ms)", true);
        } else {
            // Trigger Demo Error page (butuh login admin dulu)
            $client->request($options['url'] . '?page=login', 'POST', array(
                'username' => 'admin',
                'password' => 'admin123'
            ));
            $res = $client->request($options['url'] . '?page=demo_errors&error=' . $err_type);
            $total_requests += 2;
            if ($res['success']) { $total_success++; } else { $total_failed++; }
            print_log("  -> GET ?page=demo_errors&error=$err_type [" . $res['http_code'] . "] (" . $res['duration'] . "ms)", true);
        }

    } elseif ($scenario_type === 'not_found') {
        $bad_page = 'invalid_page_' . rand(1000, 9999);
        print_log("Simulasi Request Page 404: ?page=" . $bad_page);

        $res = $client->request($options['url'] . '?page=' . $bad_page);
        $total_requests++;
        // 404 redirect di index.php menghasilkan 302/200 redirect flash message
        if ($res['http_code'] > 0) $total_success++; else $total_failed++;
        print_log("  -> GET ?page=$bad_page [" . $res['http_code'] . "] (" . $res['duration'] . "ms)", true);
    }

    $client->cleanup();

    // Jeda antar iterasi skenario
    if ($options['delay'] > 0 && $options['mode'] !== 'heavy') {
        $sleep_sec = rand(1, $options['delay']);
        sleep($sleep_sec);
    }
}

print_log("----------------------------------------------");
print_log("=== SIAKAD TRAFFIC GENERATOR FINISHED ===");
print_log("Total Requests Sent : $total_requests");
print_log("Success (HTTP 2xx/3xx): $total_success");
print_log("Failed (Conn Errors): $total_failed");
