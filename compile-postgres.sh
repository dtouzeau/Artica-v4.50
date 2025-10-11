#!/bin/sh

# https://www.postgresql.org/ftp/source/v13.2/

VERSION="15.1"
TDIR="/usr/local/ArticaStats"
DDIR="/home/ArticaStats"
DOCDIR="/usr/local/ArticaStats/share/doc/postgresql"
# https://ftp.postgresql.org/pub/source/v15.1/postgresql-15.1.tar.gz

wget https://ftp.postgresql.org/pub/source/v$VERSION/postgresql-$VERSION.tar.gz -O  /root/postgresql-$VERSION.tar.gz
cd /root
tar -xf postgresql-$VERSION.tar.gz
cd postgresql-$VERSION

rm -rf /usr/local/ArticaStats || true
rm -rf /home/ArticaStats || true
rm -rf /root/POSTGRES_BUILDER || true

./configure --prefix=$TDIR  --exec-prefix=$TDIR   --htmldir=$DOCDIR --datadir=$DDIR
make
make install
rm -rf /root/POSTGRES_BUILDER || true
mkdir -p /root/POSTGRES_BUILDER/usr/local/ArticaStats
mkdir -p /root/POSTGRES_BUILDER/home/ArticaStats
cp -rfd /usr/local/ArticaStats/* /root/POSTGRES_BUILDER/usr/local/ArticaStats/
cp -rfd /home/ArticaStats/* /root/POSTGRES_BUILDER/home/ArticaStats/
cd /root/POSTGRES_BUILDER
tar czvf /root/ArticaStats-$VERSION.tar.gz *
cd /root