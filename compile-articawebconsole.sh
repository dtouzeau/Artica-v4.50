#!/bin/sh

# ./configure --prefix=/usr/local/ArticaWebConsole --sbin-path=/usr/local/ArticaWebConsole/sbin/artica-webconsole --conf-path=/etc/artica-postfix/webconsole.conf --pid-path=/var/run/artica-webconsole.pid --error-log-path=/var/log/lighttpd/apache-error.log --http-log-path=/var/log/lighttpd/apache-access.log --with-http_ssl_module --with-http_realip_module --with-http_gunzip_module --with-http_gzip_static_module --with-http_realip_module --with-http_v2_module --with-http_sub_module  --without-mail_pop3_module  --without-mail_imap_module  --without-mail_smtp_module --user=www-data --group=www-data --with-cc-opt='-D FD_SETSIZE=8084'

# in src/os/unix/ngx_setproctitle.c
# p = ngx_cpystrn((u_char *) ngx_os_argv[0], (u_char *) "artica-console: ",
VERSION="1.28.0"
strip -s /usr/local/ArticaWebConsole/sbin/artica-webconsole
rm -f /usr/local/ArticaWebConsole/sbin/artica-webconsole.old
mkdir -p /root/ArticaWebConsole-builder/usr/local/ArticaWebConsole
cp -rfvd /usr/local/ArticaWebConsole/* /root/ArticaWebConsole-builder/usr/local/ArticaWebConsole/
cd /root/ArticaWebConsole-builder
tar -czf /root/ArticaWebConsole-$VERSION.tar.gz *
echo "/root/ArticaWebConsole-$VERSION.tar.gz done"


