#!/bin/bash
set -e

#
# These commands will be run by VSCode inside the remote container on first startup.
#

# Install dependencies
composer install

# Wait for database to be available
MYSQL_PWD=symfony retry -t 10 -m 1 -- mysqladmin -h database -P 3306 -u symfony status

# Run database migrations
php bin/console doctrine:migrations:migrate --allow-no-migration --no-interaction

# Load database fixtures
php bin/console doctrine:fixtures:load --no-interaction
