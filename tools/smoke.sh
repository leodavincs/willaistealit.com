#!/usr/bin/env bash
# HTTP smoke: sunucuyu ayaga kaldirir, hazir olmasini bekler, matrisi kosar, temizler.
#   ./tools/smoke.sh
set -u

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PORT="${SMOKE_PORT:-8123}"
BASE="http://127.0.0.1:${PORT}"
LOG="$(mktemp -t wais-smoke.XXXXXX)"
PROBE="${ROOT}/.well-known/smoke-probe.txt"
PROBE_DIR_CREATED=0
SRV=""

cleanup() {
  [ -n "${SRV}" ] && kill "$SRV" 2>/dev/null
  rm -f "$PROBE" "$LOG"
  [ "$PROBE_DIR_CREATED" = "1" ] && rmdir "${ROOT}/.well-known" 2>/dev/null
  return 0
}
trap cleanup EXIT INT TERM

# .well-known fixture: gercek dosya servis ediliyor mu (guvenlik kurali onu kapatmamali).
if [ ! -d "${ROOT}/.well-known" ]; then
  mkdir -p "${ROOT}/.well-known" && PROBE_DIR_CREATED=1
fi
echo "smoke-probe" > "$PROBE"

php -S "127.0.0.1:${PORT}" -t "$ROOT" "${ROOT}/router.php" > "$LOG" 2>&1 &
SRV=$!

# Hazir olma kontrolu: sabit sleep degil, gercek yoklama.
READY=0
for _ in $(seq 1 60); do
  if curl -sf -o /dev/null "${BASE}/"; then READY=1; break; fi
  if ! kill -0 "$SRV" 2>/dev/null; then break; fi
  sleep 0.25
done

if [ "$READY" != "1" ]; then
  echo "HATA: sunucu ${PORT} portunda ayaga kalkmadi"
  echo "--- sunucu logu ---"
  cat "$LOG"
  exit 1
fi

php "${ROOT}/tools/smoke.php" "$BASE"
