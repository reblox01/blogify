#!/bin/sh
set -e

# Clear and warm up cache
php bin/console cache:clear --no-interaction
php bin/console cache:warmup --no-interaction

# Run migrations if database is ready
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Execution of the original command (Apache)
exec apache2-foreground
