FROM phusion/baseimage:0.11
ARG DEBIAN_FRONTEND=noninteractive


# Update & install dependencies and do cleanup
RUN apt-get update && \
    apt-get dist-upgrade -y && \
    apt-get install -y \
        composer \
        apache2 \
        libapache2-mod-php \
        php-mysql \
        php-curl \
        php-cli \
        php-mbstring \
        php-json \
        php-zmq \
        php-bcmath \
        php-gmp \
        php-bz2 \
        php-zip \
        php-intl \
        php-xml \
        curl \
        git && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Enable rewrite support for apache2
RUN a2enmod rewrite && \
    a2dissite 000-default

# Configure virtual host
COPY ./docker/cryptocheck-apache2.conf /etc/apache2/sites-available/cryptocheck.conf
RUN a2ensite cryptocheck

RUN mkdir /app && \
    chown -R www-data:www-data /app && \
    chown -R www-data:www-data /var/www

# Copy our app into docker
COPY ./app /app/app
COPY ./bootstrap /app/bootstrap
COPY ./config /app/config
COPY ./database /app/database
COPY ./public /app/public
COPY ./resources /app/resources
COPY ./routes /app/routes
COPY ./storage /app/storage
COPY ./tests /app/tests
COPY ./artisan /app/artisan
COPY ./composer.json /app/composer.json
COPY ./composer.lock /app/composer.lock

# set correct access rights for copied files
RUN chown -R www-data:www-data /app/

# install composer dependencies
USER www-data
RUN cd /app && \
    COMPOSER_HOME=/var/www composer install

USER root
# Add our startup scripts
RUN mkdir /etc/service/cryptocheck
COPY docker/cryptocheck.sh /etc/service/cryptocheck/run
RUN chmod +x /etc/service/cryptocheck/run

RUN mkdir /etc/service/cryptocheck-BTC
COPY docker/cryptocheck-BTC.sh /etc/service/cryptocheck-BTC/run
RUN chmod +x /etc/service/cryptocheck-BTC/run

RUN mkdir /etc/service/cryptocheck-BCH
COPY docker/cryptocheck-BCH.sh /etc/service/cryptocheck-BCH/run
RUN chmod +x /etc/service/cryptocheck-BCH/run

RUN mkdir /etc/service/cryptocheck-LTC
COPY docker/cryptocheck-LTC.sh /etc/service/cryptocheck-LTC/run
RUN chmod +x /etc/service/cryptocheck-LTC/run

RUN mkdir /etc/service/cryptocheck-DASH
COPY docker/cryptocheck-DASH.sh /etc/service/cryptocheck-DASH/run
RUN chmod +x /etc/service/cryptocheck-DASH/run

EXPOSE 80

CMD ["/sbin/my_init"]