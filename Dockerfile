FROM alpine:3.7
MAINTAINER Sgt. Kabukiman

# install packages
RUN apk --no-cache add php7 php7-fpm php7-mysqli php7-json php7-openssl php7-curl \
    php7-zlib php7-xml php7-phar php7-intl php7-xmlreader php7-ctype php7-session \
    php7-mbstring php7-pdo_mysql nginx nodejs supervisor curl

# install Composer
ADD https://getcomposer.org/download/1.6.2/composer.phar /usr/bin/composer
RUN chmod +rx /usr/bin/composer

# setup user accounts
RUN adduser -D horaro
RUN adduser nginx horaro

# setup nginx
RUN rm /etc/nginx/conf.d/default.conf
RUN mkdir /run/nginx
COPY resources/docker/nginx.conf /etc/nginx/conf.d/horaro.conf

# setup PHP-FPM
RUN rm /etc/php7/php-fpm.d/www.conf
COPY resources/docker/fpm-pool.conf /etc/php7/php-fpm.d/horaro.conf

# setup supervisord
COPY resources/docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# add our sources
COPY . /var/www/horaro
WORKDIR /var/www/horaro

# set up horaro direcoties
RUN mkdir -p log tmp/session tmp/upload
RUN chown -R horaro:horaro .

# install PHP dependencies
USER horaro
RUN COMPOSER_CACHE_DIR=/tmp/.composer composer install --no-dev --no-progress --no-suggest --prefer-dist && rm -rf $COMPOSER_CACHE_DIR

# build assets
RUN npm install grunt-cli && \
    npm install && \
    ./node_modules/.bin/grunt ship && \
    rm -rf node_modules tmp/assets

# finish the image up
EXPOSE 80
USER root
CMD ["sh", "/var/www/horaro/entrypoint.sh"]
