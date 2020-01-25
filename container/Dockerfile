FROM mattrayner/lamp:latest-1804
RUN perl -i -lpe 'm[short_open_tag] and s[off|Off][on]' /etc/php/7.3/apache2/php.ini
ADD ./container/sql-mode.cnf /etc/mysql/mysql.conf.d/sql-mode.cnf
ADD ./container/pict.sql /pict.sql
ADD ./container/create_mysql_users.sh /create_mysql_users.sh
ADD ./src /app
