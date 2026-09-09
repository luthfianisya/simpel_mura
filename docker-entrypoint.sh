#!/bin/sh
set -e

# Aiven (dan provider MySQL cloud lain) mewajibkan koneksi SSL — Laravel butuh
# path FILE sertifikat CA (config/database.php sudah baca env MYSQL_ATTR_SSL_CA),
# tapi di platform kayak Render kita cuma bisa nitip TEKS lewat environment
# variable, bukan upload file langsung. Jadi isi sertifikatnya (di-paste utuh,
# termasuk baris -----BEGIN/END CERTIFICATE-----) disimpan di env
# MYSQL_SSL_CA_CONTENT, lalu di sini ditulis ulang jadi file sebelum app jalan.
if [ -n "$MYSQL_SSL_CA_CONTENT" ]; then
    echo "$MYSQL_SSL_CA_CONTENT" > /tmp/mysql-ca.pem
    export MYSQL_ATTR_SSL_CA=/tmp/mysql-ca.pem
fi

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
