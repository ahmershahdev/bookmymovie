#!/bin/sh
set -e
cd /var/www/html

if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
    php artisan storage:link --force >/dev/null 2>&1 || true
    php artisan migrate --force
    if [ "${SEED_ON_BOOT:-false}" = "true" ] && [ "$(php artisan tinker --execute='echo \App\Models\Movie::count();' 2>/dev/null)" = "0" ]; then
        php artisan db:seed --force
    fi
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

case "${CONTAINER_ROLE:-app}" in
    worker) exec su -s /bin/sh www-data -c "php artisan queue:work --tries=3 --max-time=3600" ;;
    scheduler) exec su -s /bin/sh www-data -c "php artisan schedule:work" ;;
    *) chown -R www-data:www-data storage bootstrap/cache; exec "$@" ;;
esac
