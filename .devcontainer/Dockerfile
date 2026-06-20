FROM node:20-alpine AS frontend-build

WORKDIR /build
COPY frontend/package.json frontend/package-lock.json* ./
RUN npm install
COPY frontend/ ./
RUN npm run build

# ---

FROM php:8.0-fpm

# Install system packages
RUN apt-get update \
    && apt-get -y install git nano procps unzip default-mysql-client nginx supervisor curl

# Install Node.js 20 for frontend builds
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install 'retry' script
RUN curl https://raw.githubusercontent.com/kadwanev/retry/0b65e6b7f54ed36b492910470157e180bbcc3c84/retry -o /usr/local/bin/retry \
    && chmod +x /usr/local/bin/retry

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql opcache \
    && pecl install xdebug-3.1.1 \
    && docker-php-ext-enable pdo_mysql xdebug

# Xdebug and OPcache are configured at runtime by docker-start.sh based on APP_ENV

# Install composer (pinned to 2.2 LTS for compatibility with symfony/flex v1.x)
RUN curl -sSL https://getcomposer.org/download/2.2.24/composer.phar -o /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer

# Install Symfony CLI
RUN curl -sSL https://get.symfony.com/cli/installer | bash -s -- --install-dir=/usr/local/bin

# Create workspace directory
RUN mkdir /srv/app
WORKDIR /srv/app

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-scripts

# Copy Vue frontend build output
COPY --from=frontend-build /build/dist /srv/app/frontend/dist

# Add project specific nginx config
COPY .devcontainer/nginx.conf /etc/nginx/nginx.conf
RUN rm -f /etc/nginx/sites-enabled/default

# Add project specific php-fpm config
RUN rm -f /usr/local/etc/php-fpm.d/*.conf
COPY .devcontainer/php-fpm.conf /usr/local/etc/php-fpm.d/devcontainer.conf
RUN mkdir /var/log/php-fpm && chown www-data. /var/log/php-fpm

# Add project specific PHP config
COPY .devcontainer/php.ini /usr/local/etc/php/conf.d/devcontainer.ini

# Add supervisor config
COPY .devcontainer/supervisor.conf /etc/supervisor/conf.d/devcontainer.conf

# Permissions
RUN mkdir /run/nginx /run/php \
    && chown -R www-data. /etc/nginx /run/nginx /run/php
RUN echo "net.core.somaxconn=65536" >> /etc/sysctl.conf

# Run entrypoint script
COPY .devcontainer/docker-start.sh /srv/start.sh
RUN sed -i 's/\r$//' /srv/start.sh && chmod +x /srv/start.sh
CMD ["/srv/start.sh"]
