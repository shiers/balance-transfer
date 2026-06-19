#!/bin/bash
set -e

#
# These commands will be run by VSCode inside the remote container on first startup.
#

# Install PHP dependencies
composer install

# Install and build Vue frontend
if command -v node &> /dev/null; then
    cd /srv/app/frontend && npm install && npm run build
    cd /srv/app
elif command -v npx &> /dev/null; then
    cd /srv/app/frontend && npm install && npm run build
    cd /srv/app
fi

# Wait for database to be available
MYSQL_PWD=symfony retry -t 10 -m 1 -- mysqladmin -h database -P 3306 -u symfony status

# Run database migrations
php bin/console doctrine:migrations:migrate --allow-no-migration --no-interaction

# Load database fixtures
php bin/console doctrine:fixtures:load --no-interaction
