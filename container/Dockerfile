FROM alpine:latest

RUN apk update &&  \
    apk add s6 nginx  \
        php7-fpm php7-mysqli php7-mbstring php7-json php7-pecl-imagick php7-exif  \
        mariadb mysql-client &&  \
    rm -rf /var/cache/apk/*

RUN sed -i 's/.*short_open_tag.*/short_open_tag = On/' /etc/php7/php.ini
ADD ./container/env.conf /etc/php7/php-fpm.d/env.conf
ADD ./container/pict.sql /opt/pict.sql

ENTRYPOINT ["s6-svscan", "/opt/service"]
ADD ./container/service /opt/service
ADD ./container/nginx.conf /opt/nginx.conf
ADD ./src /opt/app
