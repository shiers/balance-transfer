#!/bin/bash

APP_ENV="${APP_ENV:-prod}"
echo "==> Starting in $APP_ENV mode"

# Update nginx to match worker_processes to no. of cpu's
procs=$(cat /proc/cpuinfo | grep processor | wc -l)
sed -i -e "s/worker_processes  1/worker_processes $procs/" /etc/nginx/nginx.conf

# Configure PHP based on environment
if [ "$APP_ENV" = "prod" ]; then
    echo "==> Enabling OPcache"
    cat > /usr/local/etc/php/conf.d/opcache-env.ini <<EOF
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
EOF

    echo "==> Disabling Xdebug"
    rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

    echo "==> Warming Symfony cache"
    php /srv/app/bin/console cache:warmup --env=prod --no-debug 2>/dev/null || true
else
    echo "==> OPcache disabled for development"
    cat > /usr/local/etc/php/conf.d/opcache-env.ini <<EOF
opcache.enable=0
EOF

    echo "==> Xdebug in develop mode"
    cat > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini <<EOF
zend_extension=xdebug
xdebug.mode=develop
xdebug.start_with_request=no
xdebug.client_host=host.docker.internal
EOF
fi

# Start supervisord and services
/usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf

# If supervisord exits, something went wrong
echo "⚠️ Supervisor exited!"
