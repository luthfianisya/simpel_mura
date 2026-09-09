#!/bin/sh
set -e

# APP_KEY sebaiknya di-set TETAP lewat environment variable platform (Render:
# Settings > Environment), bukan di-generate ulang tiap kali container start —
# kalau berubah-ubah, semua sesi/token yang sudah ditandatangani jadi tidak
# valid. Ini cuma jaga-jaga generate SEKALI kalau memang belum ada sama sekali.
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY kosong — generate sementara (sebaiknya di-set permanen di env platform)."
    php artisan key:generate --force
fi

php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
