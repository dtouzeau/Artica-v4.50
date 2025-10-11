#!/bin/sh

COMPILEDIR="/root/compile-httrack"

mkdir -p "$COMPILEDIR/usr/lib/httrack"
mkdir -p "$COMPILEDIR/usr/bin"

cp -rfv /usr/lib/httrack/* $COMPILEDIR/usr/lib/httrack/

strip -s /usr/bin/proxytrack
strip -s /usr/bin/httrack
strip -s /usr/bin/htsserver


cp -f /usr/bin/proxytrack  $COMPILEDIR/usr/bin/proxytrack
cp -f /usr/bin/httrack $COMPILEDIR/usr/bin/httrack
cp -f /usr/bin/htsserver $COMPILEDIR/usr/bin/htsserver

cd  $COMPILEDIR
tar -czvf /root/httrack-compiled.x.x.x.tar.gz *
