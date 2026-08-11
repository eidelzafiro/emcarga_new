#!/bin/bash
# Arranque del contenedor app (Zafiro): php-fpm + worker de colas + scheduler.
# Correr php-fpm como proceso principal y los workers como www-data.

set -e

run_as_www() {
    if [ "$(id -u)" = "0" ]; then
        setpriv --reuid=www-data --regid=www-data --init-groups "$@"
    else
        "$@"
    fi
}

php-fpm &
FPM_PID=$!

cd /var/www

run_as_www php artisan queue:work database --sleep=3 --tries=3 &
QUEUE_PID=$!

run_as_www php artisan schedule:work &
SCHEDULE_PID=$!

cleanup() {
    kill "$FPM_PID" "$QUEUE_PID" "$SCHEDULE_PID" 2>/dev/null || true
}
trap cleanup EXIT INT TERM

wait -n