#!/bin/bash
# ============================================================
# SIAKAD - Traffic Simulator Launcher & Background Manager
# ============================================================
#
# USAGE:
#   bash scripts/run_traffic.sh [MODE] [REQUEST_COUNT] [URL]
#
# CONTOH:
#   bash scripts/run_traffic.sh                      (Mode normal, 50 requests)
#   bash scripts/run_traffic.sh --mode normal        (Mode normal, loop 50x)
#   bash scripts/run_traffic.sh --mode heavy         (Mode cepat/heavy)
#   bash scripts/run_traffic.sh --mode error         (Mode pemicu error spike)
#   bash scripts/run_traffic.sh --continuous         (Running terus menerus di background)
#   bash scripts/run_traffic.sh --stop               (Hentikan background process)
#   bash scripts/run_traffic.sh --status             (Cek status background process)
#
# ============================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
PID_FILE="$PROJECT_DIR/logs/traffic_sim.pid"
LOG_FILE="$PROJECT_DIR/logs/traffic_sim.log"

MODE="normal"
COUNT=50
URL="http://localhost/php5-demo-dd/index.php"
BACKGROUND=0
ACTION="start"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --mode)
            MODE="$2"
            shift 2
            ;;
        --count)
            COUNT="$2"
            shift 2
            ;;
        --url)
            URL="$2"
            shift 2
            ;;
        --continuous|--background|-bg)
            BACKGROUND=1
            COUNT=0
            shift
            ;;
        --stop)
            ACTION="stop"
            shift
            ;;
        --status)
            ACTION="status"
            shift
            ;;
        --help|-h)
            ACTION="help"
            shift
            ;;
        *)
            # Fallback positional argument
            if [ -z "$MODE_SET" ]; then
                MODE="$1"
                MODE_SET=1
            fi
            shift
            ;;
    esac
done

case $ACTION in
    help)
        echo "SIAKAD Traffic Simulator Launcher"
        echo ""
        echo "Opsi:"
        echo "  --mode <normal|heavy|error>  Mode trafik (Default: normal)"
        echo "  --count <N>                  Jumlah iterasi (Default: 50, 0 = unlimited)"
        echo "  --url <URL>                  Target URL index.php"
        echo "  --continuous                 Jalankan terus di background"
        echo "  --stop                       Hentikan background simulator"
        echo "  --status                     Cek status background simulator"
        exit 0
        ;;

    stop)
        if [ -f "$PID_FILE" ]; then
            PID=$(cat "$PID_FILE")
            if ps -p "$PID" > /dev/null 2>&1; then
                kill "$PID"
                rm -f "$PID_FILE"
                echo "✅ Traffic Simulator (PID: $PID) berhasil dihentikan."
            else
                echo "⚠️ Process (PID: $PID) tidak ditemukan. Menghapus file PID."
                rm -f "$PID_FILE"
            fi
        else
            echo "ℹ️ Tidak ada Traffic Simulator yang berjalan di background."
        fi
        exit 0
        ;;

    status)
        if [ -f "$PID_FILE" ]; then
            PID=$(cat "$PID_FILE")
            if ps -p "$PID" > /dev/null 2>&1; then
                echo "🟢 Traffic Simulator berjalan di background (PID: $PID)."
                if [ -f "$LOG_FILE" ]; then
                    echo "--- Log Terakhir ---"
                    tail -n 5 "$LOG_FILE"
                fi
            else
                echo "🔴 Process mati (PID file usang)."
            fi
        else
            echo "⚪ Traffic Simulator background tidak aktif."
        fi
        exit 0
        ;;

    start)
        # Cek apakah sudah running
        if [ -f "$PID_FILE" ]; then
            PID=$(cat "$PID_FILE")
            if ps -p "$PID" > /dev/null 2>&1; then
                echo "⚠️ Traffic Simulator sudah berjalan di background (PID: $PID)."
                echo "Gunakan 'bash scripts/run_traffic.sh --stop' untuk menghentikan terlebih dahulu."
                exit 1
            fi
        fi

        # Cek ketersediaan php CLI
        if ! command -v php &> /dev/null; then
            echo "❌ Command 'php' tidak ditemukan di PATH sistem."
            exit 1
        fi

        mkdir -p "$PROJECT_DIR/logs"

        if [ "$BACKGROUND" -eq 1 ]; then
            echo "🚀 Menjalankan Traffic Simulator di background (Mode: $MODE)..."
            nohup php "$SCRIPT_DIR/traffic_generator.php" --url="$URL" --mode="$MODE" --count=0 --verbose > "$LOG_FILE" 2>&1 &
            BG_PID=$!
            echo $BG_PID > "$PID_FILE"
            echo "✅ Simulator berjalan di background dengan PID: $BG_PID"
            echo "📝 Log output di-stream ke: $LOG_FILE"
            echo "Gunakan 'bash scripts/run_traffic.sh --stop' untuk menghentikan."
        else
            echo "🚀 Menjalankan Traffic Simulator di foreground (Mode: $MODE, Iterasi: $COUNT)..."
            php "$SCRIPT_DIR/traffic_generator.php" --url="$URL" --mode="$MODE" --count="$COUNT" --verbose
        fi
        ;;
esac
