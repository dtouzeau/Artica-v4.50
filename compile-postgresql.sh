#!/bin/sh
# wget https://ftp.postgresql.org/pub/source/v11.4/postgresql-11.4.tar.gz
#/usr/local/ArticaStats
# User: ArticaStats
#/var/run/ArticaStats
# su -c "/usr/local/ArticaStats/bin/initdb --username=ArticaStats /home/ArticaStatsDB --no-locale -E UTF8" ArticaStats
#./configure --prefix=/usr/local/ArticaStats


mkdir -p /root/postgresql-builder/usr/local/ArticaStats
cp -rfd /usr/local/ArticaStats/* /root/postgresql-builder/usr/local/ArticaStats/
cd /root/postgresql-builder
tar -czf /root/postgresql-builder.tar.gz *
echo "/root/postgresql-builder.tar.gz done"




