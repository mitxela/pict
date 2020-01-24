FROM mattrayner/lamp:latest-1804
RUN perl -i -lpe 'm[short_open_tag] and s[off|Off][on]' /etc/php/7.3/apache2/php.ini
ADD ./dev/pict.sql /pict.sql
ADD ./dev/create_mysql_users.sh /create_mysql_users.sh
