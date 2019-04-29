#!/bin/sh

set -e

exec /sbin/setuser www-data php /app/artisan checkprice:watch_transactions BCH >> /var/log/checkprice-BCH.log 2>&1