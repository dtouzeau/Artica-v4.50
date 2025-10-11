#!/usr/bin/env bash

WORKDIR="/root/compile/compile-ufdb-deb12"
mkdir -p $WORKDIR/usr/local/lib
mkdir -p $WORKDIR/usr/bin

cp /usr/bin/ufdbguardd $WORKDIR/usr/bin/
cp /usr/bin/ufdbConvertDB $WORKDIR/usr/bin/
cp /usr/bin/ufdbGenTable $WORKDIR/usr/bin/
cp /usr/bin/ufdbAnalyse $WORKDIR/usr/bin/
cp /usr/bin/ufdbhttpd $WORKDIR/usr/bin/
cp /usr/bin/ufdbgclient $WORKDIR/usr/bin/
cp /usr/bin/ufdbUpdate $WORKDIR/usr/bin/
cp /usr/bin/ufdb-pstack $WORKDIR/usr/bin/
cp /usr/lib/x86_64-linux-gnu/libssl.so.1.1 $WORKDIR/usr/local/lib/
cp /usr/lib/x86_64-linux-gnu/libcrypto.so.1.1 $WORKDIR/usr/local/lib/

cd $WORKDIR || exit 1
tar -czf /root/compile-ufdb-deb12.tar.gz *
echo "/root/compile-ufdb-deb12.tar.gz done"