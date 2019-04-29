#!/bin/sh

set -e

exec /sbin/setuser www-data php /app/artisan checkprice:watch_transactions LTC >> /var/log/checkprice-LTC.log 2>&1