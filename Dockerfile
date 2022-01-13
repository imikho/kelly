FROM php:8-fpm-alpine3.13 as base
RUN apk update && apk add g++ make git wget ca-certificates openssl openssh bzip2-dev zlib-dev libpng-dev tzdata fcgi cyrus-sasl-dev
# php8-dev
RUN update-ca-certificates

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# install php packages
# possible options in `docker-php-exe-install'
# bcmath   |fileinfo |json      |pdo_firebird |readline   |standard   |zend_test
# bz2      |filter   |ldap      |pdo_mysql    |reflection |sysvmsg    |zip
# calendar |ftp      |mbstring  |pdo_oci      |session    |sysvsem
# ctype    |gd       |mysqli    |pdo_odbc     |shmop      |sysvshm
# curl     |gettext  |oci8      |pdo_pgsql    |simplexml  |tidy
# dba      |gmp      |odbc      |pdo_sqlite   |snmp       |tokenizer
# dom      |hash     |opcache   |pgsql        |soap       |xml
# enchant  |iconv    |pcntl     |phar         |sockets    |xmlreader
# exif     |imap     |pdo       |posix        |sodium     |xmlwriter
# ffi      |intl     |pdo_dblib |pspell       |spl        |xsl
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install bz2
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install pdo_mysql
## Deps the block below is an example of how to install memcache and redis feature
## uncomment if you need this features installed
## Install Memcache and redis
ENV MEMCACHE_DEPS zlib-dev cyrus-sasl-dev php7-dev g++ make git
RUN apk add --no-cache -t .phpize-deps $PHPIZE_DEPS && \
 apk add --no-cache -t .memcache-deps $MEMCACHE_DEPS && \
 ## Prepare php for extensions
 apk add --no-cache -u \
 # Install timezone util
 tzdata \
 ## fpm healthcheck status check dep
 fcgi \
 zlib-dev \
 && \
 # Install php-redis
 pecl install redis -y && \
 docker-php-ext-enable redis && \
 # Install memcache
 cd /tmp && git clone https://github.com/websupport-sk/pecl-memcache && \
 cd pecl-memcache && \
 phpize && \
 ./configure && \
 make && \
 make install && \
 docker-php-ext-enable memcache && \
 ## PHP extensions
 docker-php-ext-install -j$(nproc) bcmath && \
 # Install pcntl
 docker-php-ext-install pcntl && \
 apk del .phpize-deps && \
 apk del .memcache-deps

# PHP Xdebug
FROM base as dev
ENV XDEBUG_CONF=/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN apk add --no-cache -t .deps $PHPIZE_DEPS && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug

# injecting all code
FROM base as code
WORKDIR /application/
COPY . .
# remove unused code
RUN rm -rf .git .github .vscode docs coverage vendor
