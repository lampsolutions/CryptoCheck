#!/bin/sh

set -e

# Migrate app
cd /app/


export APACHE_RUN_USER=www-data
export APACHE_RUN_GROUP=www-data
export APACHE_PID_FILE=/var/run/apache2/apache2$SUFFIX.pid
export APACHE_RUN_DIR=/var/run/apache2$SUFFIX
export APACHE_LOCK_DIR=/var/lock/apache2$SUFFIX
export APACHE_LOG_DIR=/var/log/apache2$SUFFIX

export LANG=C
export LANG

exec /usr/sbin/apache2 -f /etc/apache2/apache2.conf -DNO_DETACH