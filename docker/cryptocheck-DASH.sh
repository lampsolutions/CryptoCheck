#!/bin/sh

set -e

exec /sbin/setuser www-data php /app/artisan checkprice:watch_transactions DASH >> /var/log/checkprice-DASH.log 2>&1