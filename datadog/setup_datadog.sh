#!/bin/bash
# ============================================================
# SIAKAD - Datadog Agent Setup Script untuk Ubuntu 22.04
# ============================================================
#
# Script ini menginstal dan mengkonfigurasi:
# 1. Datadog Agent (log collection + metrics)
# 2. Apache integration (access/error logs)
# 3. MySQL integration (query metrics)
# 4. Custom log collection untuk SIAKAD app logs
#
# USAGE:
#   sudo bash setup_datadog.sh <DD_API_KEY> [DD_SITE]
#
# CONTOH:
#   sudo bash setup_datadog.sh abc123def456 datadoghq.com
#   sudo bash setup_datadog.sh abc123def456 datadoghq.eu
#
# ============================================================

set -e

# ============================================================
# VALIDASI ARGUMENT
# ============================================================
DD_API_KEY="${1:-}"
DD_SITE="${2:-datadoghq.com}"

if [ -z "$DD_API_KEY" ]; then
    echo "============================================"
    echo "  SIAKAD - Datadog Setup Script"
    echo "============================================"
    echo ""
    echo "ERROR: Datadog API Key diperlukan!"
    echo ""
    echo "USAGE:"
    echo "  sudo bash setup_datadog.sh <DD_API_KEY> [DD_SITE]"
    echo ""
    echo "CONTOH:"
    echo "  sudo bash setup_datadog.sh abc123def456ghi789"
    echo "  sudo bash setup_datadog.sh abc123def456ghi789 datadoghq.eu"
    echo ""
    echo "Dapatkan API Key di: https://app.datadoghq.com/organization-settings/api-keys"
    echo ""
    exit 1
fi

echo "============================================"
echo "  SIAKAD - Installing Datadog Agent"
echo "============================================"
echo "DD_API_KEY: ${DD_API_KEY:0:8}...****"
echo "DD_SITE:    $DD_SITE"
echo "============================================"

# ============================================================
# STEP 1: Buat direktori log SIAKAD
# ============================================================
echo ""
echo "[1/6] Membuat direktori log SIAKAD..."

mkdir -p /var/log/siakad
chown www-data:www-data /var/log/siakad
chmod 755 /var/log/siakad
touch /var/log/siakad/app.log
chown www-data:www-data /var/log/siakad/app.log
chmod 644 /var/log/siakad/app.log

echo "  ✅ /var/log/siakad/app.log siap"

# ============================================================
# STEP 2: Install Datadog Agent
# ============================================================
echo ""
echo "[2/6] Menginstal Datadog Agent..."

DD_API_KEY="$DD_API_KEY" DD_SITE="$DD_SITE" bash -c "$(curl -L https://s3.amazonaws.com/dd-agent/scripts/install_script_agent7.sh)"

echo "  ✅ Datadog Agent terinstal"

# ============================================================
# STEP 3: Konfigurasi Datadog Agent (main config)
# ============================================================
echo ""
echo "[3/6] Mengkonfigurasi Datadog Agent..."

# Backup config original
cp /etc/datadog-agent/datadog.yaml /etc/datadog-agent/datadog.yaml.bak 2>/dev/null || true

cat > /etc/datadog-agent/datadog.yaml << EOF
# ============================================================
# Datadog Agent Configuration - SIAKAD
# ============================================================

api_key: $DD_API_KEY
site: $DD_SITE

# Hostname dan tags
hostname: siakad-demo-vm
tags:
  - env:demo
  - service:siakad
  - version:1.0.0
  - app:siakad-php5
  - team:akademik

# Enable log collection
logs_enabled: true

# Enable DogStatsD (untuk custom metrics dari PHP)
use_dogstatsd: true
dogstatsd_port: 8125

# Process monitoring
process_config:
  enabled: true

# APM (Trace Agent)
apm_config:
  enabled: true
  receiver_port: 8126
EOF

echo "  ✅ /etc/datadog-agent/datadog.yaml dikonfigurasi"

# ============================================================
# STEP 4: Konfigurasi SIAKAD custom log collection
# ============================================================
echo ""
echo "[4/6] Mengkonfigurasi SIAKAD log collection..."

mkdir -p /etc/datadog-agent/conf.d/siakad.d

cat > /etc/datadog-agent/conf.d/siakad.d/conf.yaml << 'EOF'
# ============================================================
# SIAKAD Application Logs
# ============================================================
# Structured JSON logs dari PHP application
# File: /var/log/siakad/app.log

logs:
  - type: file
    path: /var/log/siakad/app.log
    service: siakad
    source: php
    sourcecategory: siakad
    tags:
      - env:demo
      - app:siakad-php5
      - framework:vanilla-php5
    log_processing_rules:
      - type: multi_line
        name: php_multiline
        pattern: '^\{'
EOF

echo "  ✅ SIAKAD log collection dikonfigurasi"

# ============================================================
# STEP 5: Konfigurasi Apache integration
# ============================================================
echo ""
echo "[5/6] Mengkonfigurasi Apache integration..."

mkdir -p /etc/datadog-agent/conf.d/apache.d

cat > /etc/datadog-agent/conf.d/apache.d/conf.yaml << 'EOF'
# ============================================================
# Apache Integration - SIAKAD
# ============================================================

init_config:

instances:
  - apache_status_url: http://localhost/server-status?auto
    tags:
      - env:demo
      - service:siakad

logs:
  - type: file
    path: /var/log/apache2/access.log
    service: siakad
    source: apache
    sourcecategory: http_web_access
    tags:
      - env:demo

  - type: file
    path: /var/log/apache2/error.log
    service: siakad
    source: apache
    sourcecategory: http_web_error
    tags:
      - env:demo
    log_processing_rules:
      - type: multi_line
        name: apache_error_multiline
        pattern: '^\['
EOF

# Enable Apache mod_status untuk metrics
a2enmod status 2>/dev/null || true

# Tambahkan konfigurasi mod_status jika belum ada
if ! grep -q "server-status" /etc/apache2/mods-enabled/status.conf 2>/dev/null; then
    cat > /etc/apache2/conf-available/server-status.conf << 'APACHE_CONF'
<Location "/server-status">
    SetHandler server-status
    Require local
</Location>
APACHE_CONF
    a2enconf server-status 2>/dev/null || true
fi

echo "  ✅ Apache integration dikonfigurasi"

# ============================================================
# STEP 6: Konfigurasi MySQL integration
# ============================================================
echo ""
echo "[6/6] Mengkonfigurasi MySQL integration..."

mkdir -p /etc/datadog-agent/conf.d/mysql.d

# Buat MySQL user untuk Datadog
echo "  → Membuat MySQL user untuk Datadog monitoring..."
mysql -u root -e "
CREATE USER IF NOT EXISTS 'datadog'@'localhost' IDENTIFIED BY 'datadog_password_secure';
GRANT REPLICATION CLIENT ON *.* TO 'datadog'@'localhost';
GRANT PROCESS ON *.* TO 'datadog'@'localhost';
GRANT SELECT ON performance_schema.* TO 'datadog'@'localhost';
FLUSH PRIVILEGES;
" 2>/dev/null || echo "  ⚠️ MySQL user mungkin sudah ada atau perlu password root"

# Untuk MySQL 8: pastikan pakai mysql_native_password
mysql -u root -e "
ALTER USER 'datadog'@'localhost' IDENTIFIED WITH mysql_native_password BY 'datadog_password_secure';
FLUSH PRIVILEGES;
" 2>/dev/null || true

cat > /etc/datadog-agent/conf.d/mysql.d/conf.yaml << 'EOF'
# ============================================================
# MySQL Integration - SIAKAD
# ============================================================

init_config:

instances:
  - host: localhost
    username: datadog
    password: datadog_password_secure
    port: 3306
    tags:
      - env:demo
      - service:siakad
      - db:db_inquiry
    options:
      replication: false
      galera_cluster: false
      extra_status_metrics: true
      extra_innodb_metrics: true
      schema_size_metrics: true
      disable_innodb_metrics: false

logs:
  - type: file
    path: /var/log/mysql/error.log
    service: siakad
    source: mysql
    tags:
      - env:demo
EOF

echo "  ✅ MySQL integration dikonfigurasi"

# ============================================================
# FINAL: Tambahkan dd-agent ke grup adm (untuk baca log)
# ============================================================
echo ""
echo "============================================"
echo "  Finalisasi..."
echo "============================================"

usermod -a -G adm dd-agent 2>/dev/null || true
usermod -a -G www-data dd-agent 2>/dev/null || true

# Restart semua service
echo "  → Restarting Apache..."
systemctl restart apache2

echo "  → Restarting Datadog Agent..."
systemctl restart datadog-agent

echo ""
echo "============================================"
echo "  ✅ SETUP SELESAI!"
echo "============================================"
echo ""
echo "Verifikasi:"
echo "  sudo datadog-agent status"
echo "  sudo tail -f /var/log/siakad/app.log"
echo ""
echo "Datadog Dashboard:"
echo "  https://app.${DD_SITE}/logs?query=service%3Asiakad"
echo "  https://app.${DD_SITE}/metric/summary?filter=siakad"
echo ""
echo "Selanjutnya:"
echo "  1. Buka SIAKAD di browser: http://<IP_VM>/php5-demo-dd/"
echo "  2. Login sebagai admin/admin123"
echo "  3. Buka menu 'Demo Errors' di sidebar"
echo "  4. Trigger error-error → cek di Datadog!"
echo ""
