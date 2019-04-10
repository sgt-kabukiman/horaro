FROM alpine:3.7 AS builder
MAINTAINER Sgt. Kabukiman

# install packages
RUN apk --no-cache add php7 php7-json php7-openssl php7-phar php7-mbstring nodejs git

# install Composer
ADD https://getcomposer.org/download/1.6.2/composer.phar /usr/bin/composer
RUN chmod +rx /usr/bin/composer

# add our sources
COPY . /build
WORKDIR /build

# install PHP dependencies
RUN composer install --no-dev --no-progress --no-suggest --prefer-dist --ignore-platform-reqs

# build assets
RUN npm install grunt-cli && \
    npm install && \
    ./node_modules/.bin/grunt ship

# determine version
RUN git describe --tags --always > version

# remove temporary files to make the next copy commands easier
RUN rm -rf assets tmp/assets node_modules .git .gitignore tests

###################################################################################
# second stage: final image

FROM alpine:3.7
MAINTAINER Sgt. Kabukiman

# install packages
RUN apk --no-cache add php7 php7-fpm php7-mysqli php7-json php7-openssl php7-curl \
    php7-zlib php7-xml php7-intl php7-xmlreader php7-xmlwriter php7-ctype php7-session \
    php7-mbstring php7-pdo_mysql nginx supervisor curl file

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

# add horaro
WORKDIR /var/www/horaro
COPY --from=builder /build .

# set up horaro directories
RUN mkdir -p log tmp/session tmp/upload
RUN chown -R horaro:horaro .

# finish the image up
EXPOSE 80
USER root
CMD ["sh", "entrypoint.sh"]
