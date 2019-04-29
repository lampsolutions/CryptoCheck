#!/bin/sh

set -e

exec /sbin/setuser www-data php /app/artisan checkprice:watch_transactions BTC >> /var/log/checkprice-BTC.log 2>&1