#!/usr/bin/env bash
# Golden referans kosucusu.
#   ./tools/golden.sh --capture
#   ./tools/golden.sh --check [--semantic]
#   ./tools/golden.sh --cache-check
# --self-test sunucu gerektirmez: php tools/golden.php --self-test
set -u

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PORT="${GOLDEN_PORT:-8124}"
BASE="http://127.0.0.1:${PORT}"
LOG="$(mktemp -t wais-golden.XXXXXX)"
SRV=""

cleanup() {
  [ -n "$SRV" ] && kill "$SRV" 2>/dev/null
  rm -f "$LOG"
  return 0
}
trap cleanup EXIT INT TERM

# macOS ve Linux'ta calisan mtime
mtime() { stat -f %m "$1" 2>/dev/null || stat -c %Y "$1"; }

clear_page_cache() {
  php -r 'require "'"${ROOT}"'/inc/functions.php"; clear_cache();' > /dev/null
}

start_server() {
  php -S "127.0.0.1:${PORT}" -t "$ROOT" "${ROOT}/router.php" > "$LOG" 2>&1 &
  SRV=$!
  for _ in $(seq 1 60); do
    curl -sf -o /dev/null "${BASE}/" && return 0
    kill -0 "$SRV" 2>/dev/null || break
    sleep 0.25
  done
  echo "HATA: sunucu ${PORT} portunda ayaga kalkmadi"
  cat "$LOG"
  return 1
}

case "${1:---check}" in
  --cache-check)
    clear_page_cache
    start_server || exit 1

    # Cache adi icerik evreninin surumunu tasiyor (cashier.<surum>.html), o yuzden
    # sabit ad yerine kaliba bakiyoruz.
    cache_file() { ls "${ROOT}"/cache/pages/en/cashier.*.html 2>/dev/null | head -1; }

    curl -sf -o /dev/null "${BASE}/cashier" || { echo "HATA: /cashier alinamadi"; exit 1; }
    CACHE="$(cache_file)"
    [ -n "$CACHE" ] && [ -f "$CACHE" ] || { echo "HATA: cache yazilmadi — write_page_cache bozuk"; exit 1; }
    BEFORE="$(mtime "$CACHE")"

    # filemtime saniye hassasiyetinde: dokunmadan once bir saniyeden fazla bekle
    sleep 1.1
    touch "${ROOT}/data/locale/en.php"

    curl -sf -o /dev/null "${BASE}/cashier" || { echo "HATA: ikinci istek basarisiz"; exit 1; }
    CACHE="$(cache_file)"
    AFTER="$(mtime "$CACHE")"

    if [ "$AFTER" -gt "$BEFORE" ]; then
      echo "cache YENIDEN yazildi (dogru): $BEFORE -> $AFTER"
      exit 0
    fi
    echo "HATA: bayat cache servis edildi — template_mtime() locale dosyalarini gormuyor"
    exit 1
    ;;
  --capture|--check)
    clear_page_cache
    start_server || exit 1
    php "${ROOT}/tools/golden.php" "$@" "$BASE"
    exit $?
    ;;
  *)
    echo "kullanim: golden.sh --capture | --check [--semantic] | --cache-check"
    exit 2
    ;;
esac
