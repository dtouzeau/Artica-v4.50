#!/bin/bash


# git clone https://github.com/valkey-io/valkey.git
# cd vlalkey
# modify VALKEY_VERSION in src/version.h
# make BUILD_TLS=yes USE_REDIS_SYMLINKS=no
# make install


VERSION="7.2.5"
COMPILEDIR="/root/compile-valkey-$VERSION"
DFILE="/root/redis-$VERSION.tar.gz"
COMPILE_FILE="/root/redis-compiled-$VERSION.tar.gz"
rm -f $COMPILE_FILE
mkdir -p "$COMPILEDIR/usr/bin"
mkdir -p "$COMPILEDIR/usr/sbin"

strip -s /usr/local/bin/valkey-server
strip -s /usr/local/bin/valkey-benchmark
strip -s /usr/local/bin/valkey-check-aof
strip -s /usr/local/bin/valkey-check-rdb
strip -s /usr/local/bin/valkey-cli
strip -s /usr/local/bin/valkey-sentinel

cp  /usr/local/bin/valkey-server $COMPILEDIR/usr/bin/redis-server
cp  /usr/local/bin/valkey-benchmark $COMPILEDIR/usr/bin/redis-benchmark
cp  /usr/local/bin/valkey-check-aof $COMPILEDIR/usr/bin/redis-check-aof
cp  /usr/local/bin/valkey-check-rdb $COMPILEDIR/usr/bin/redis-check-rdb
cp  /usr/local/bin/valkey-cli $COMPILEDIR/usr/bin/redis-cli
cp  /usr/local/bin/valkey-sentinel $COMPILEDIR/usr/bin/redis-sentinel
cp -fd /usr/local/bin/valkey-server $COMPILEDIR/usr/sbin/itcharter
cp -fd /usr/local/bin/valkey-server $COMPILEDIR/usr/sbin/statsredis

echo "Compressing $COMPILE_FILE"
cd "$COMPILEDIR" || exit
tar -czvf $COMPILE_FILE *
echo "$COMPILE_FILE Done..."
