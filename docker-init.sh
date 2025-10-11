#!/bin/bash

/usr/bin/apt-get -qy update --allow-releaseinfo-change
apt-get install libargon2-1 libidn2-0 libpcre2-8-0 libpcre3 libxml2 libzstd1 libsodium23 htop libevent-2.1-6 libgd3 liblmdb0 libqdbm14 libzip4 libmaxminddb0 libonig5 libpq5 libsnmp30 libmemcached11 libmcrypt4 libmemcachedutil2 libaspell15 librrd8 cron libapparmor1 libestr0
mkdir -p /etc/monit/conf.d && mkdir -p /etc/artica-postfix/settings/Daemons && mkdir -p /var/lib/php/sessions
#wget http://articatech.net/download/Debian10-php8/php8.2.tar.gz
wget http://articatech.net/nightly4/artica-4.40.000000.tgz && tar xf artica-4.40.000000.tgz -C /usr/share/ && rm artica-4.40.000000.tgz
wget http://mirror.articatech.com/download/Debian10-WebConsole/1.22.1.tar.gz && tar -xf 1.22.1.tar.gz -C / && rm 1.22.1.tar.gz
wget http://mirror.articatech.com/download/Debian10-memcached/1.6.19.tar.gz && tar -xf 1.6.19.tar.gz -C / && rm 1.6.19.tar.gz
wget http://mirror.articatech.com/download/Debian10-nginx/1.19.3.2.tar.gz && tar -xf 1.19.3.2.tar.gz -C / && rm 1.19.3.2.tar.gz
wget http://mirror.articatech.com/download/Debian10-php/7.40.tar.gz && tar -xf 7.40.tar.gz -C / && rm 7.40.tar.gz
wget http://mirror.articatech.com/download/Debian10-monit/5.33.0.tar.gz && tar xf 5.33.0.tar.gz -C / && rm 5.33.0.tar.gz
wget http://mirror.articatech.com/download/Debian10-syslogd/8.2304.0.tar.gz && tar xf 8.2304.0.tar.gz -C / && rm 8.2304.0.tar.gz
wget http://mirror.articatech.com/download/Debian10-haproxy/2.7.7.tar.gz && tar -xf 2.7.7.tar.gz -C / && rm 2.7.7.tar.gz
ln -sf /usr/bin/php7.4 /usr/bin/php
/usr/share/artica-postfix/bin/articarest -phpini
/usr/bin/php /usr/share/artica-postfix/exec.monit.php --install
/usr/bin/php /usr/share/artica-postfix/exec.monit.php --build
/usr/bin/php /usr/share/artica-postfix/exec.initslapd.php --artica-status
/usr/bin/php /usr/share/artica-postfix/exec.initslapd.php --artica-web
/usr/bin/php /usr/share/artica-postfix/exec.initslapd.php --artica-syslog
/usr/bin/php /usr/share/artica-postfix/exec.artica-php-fpm.php --install
/usr/bin/php /usr/share/artica-postfix/exec.memcached.php --install
/usr/bin/php /usr/share/artica-postfix/exec.go.exec.php
/usr/bin/php /usr/share/artica-postfix/exec.convert-to-sqlite.php
/usr/bin/php /usr/share/artica-postfix/exec.syslog-engine.php --start
/etc/init.d/monit start

# rm /usr/lib/firmware
# Limited docker : libldap-2.4-2 libmaxminddb0 libxml2 libxslt1.1 libcurl4 libgeoip1 liblmdb0 liblua5.3-0