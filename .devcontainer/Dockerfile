FROM php:8.0-fpm

# Install system packages
RUN apt-get update \
    && apt-get -y install git nano procps unzip default-mysql-client nginx supervisor

# Install 'retry' script
RUN curl https://raw.githubusercontent.com/kadwanev/retry/0b65e6b7f54ed36b492910470157e180bbcc3c84/retry -o /usr/local/bin/retry \
    && chmod +x /usr/local/bin/retry

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && pecl install xdebug-3.1.1 \
    && docker-php-ext-enable pdo_mysql xdebug

# Configure Xdebug
RUN echo "xdebug.mode=develop" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install composer
RUN curl -sSL https://getcomposer.org/installer | php \
    && chmod +x composer.phar \
    && mv composer.phar /usr/local/bin/composer

# Install Symfony CLI
RUN curl -sSL https://get.symfony.com/cli/installer | bash -s -- --install-dir=/usr/local/bin

# Create workspace directory (application code should be attached here using a bind mount)
RUN mkdir /srv/app
WORKDIR /srv/app

# Install dependencies (this will populate the ~/.composer/cache for faster installs later)
COPY composer.json composer.lock ./
RUN composer install --no-scripts

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
CMD ["/srv/start.sh"]
