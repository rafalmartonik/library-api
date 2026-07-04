#!/bin/sh
set -e

# Railway (and similar) inject $PORT; FrankenPHP listens according to SERVER_NAME
if [ -n "$PORT" ]; then
    export SERVER_NAME=":$PORT"
fi

if [ -f bin/console ]; then
    until php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; do
        echo "Waiting for database..."
        sleep 1
    done
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

exec "$@"
