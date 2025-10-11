#!/bin/bash

REDIS_VERSION="7.0.12"
COMPILEDIR="/root/redis-compile"
DFILE="/root/redis-$REDIS_VERSION.tar.gz"
COMPILE_FILE="/root/redis-compiled-$REDIS_VERSION.tar.gz"

if [ -f $DFILE ]
then
  echo "Remove $DFILE"
  rm -f $DFILE
fi

if [ -f $COMPILE_FILE ]
then
  echo "Remove $COMPILE_FILE"
  rm -f $COMPILE_FILE
fi


echo "Downloading http://download.redis.io/releases/redis-$REDIS_VERSION.tar.gz"
cd /root && wget http://download.redis.io/releases/redis-$REDIS_VERSION.tar.gz

cd /root
tar xf redis-$REDIS_VERSION.tar.gz
cd redis-$REDIS_VERSION
make -j4 "INSTALL=install --strip-program=true" V=1 USE_SYSTEM_JEMALLOC=yes USE_SYSTEM_LUA=yes USE_SYSTEM_HIREDIS=no

make install

strip -s /usr/local/bin/redis-server
strip -s /usr/local/bin/redis-benchmark
strip -s /usr/local/bin/redis-check-aof
strip -s /usr/local/bin/redis-check-rdb
strip -s /usr/local/bin/redis-cli
strip -s /usr/local/bin/redis-sentinel



if [ -d "$COMPILEDIR" ]
then
  echo "Remove directory $COMPILEDIR"
  rm -rf $COMPILEDIR
fi

mkdir -p $COMPILEDIR/usr/bin
mkdir -p $COMPILEDIR/usr/sbin

if [ ! -d "$COMPILEDIR" ]
then
  echo "$COMPILEDIR permission denied of no space left"
fi


cp -fd /usr/local/bin/redis-server $COMPILEDIR/usr/bin/
cp -fd /usr/local/bin/redis-benchmark $COMPILEDIR/usr/bin/
cp -fd /usr/local/bin/redis-check-aof $COMPILEDIR/usr/bin/
cp -fd /usr/local/bin/redis-check-rdb $COMPILEDIR/usr/bin/
cp -fd /usr/local/bin/redis-cli $COMPILEDIR/usr/bin/
cp -fd /usr/local/bin/redis-sentinel $COMPILEDIR/usr/bin/
cp -fd /usr/local/bin/redis-server $COMPILEDIR/usr/sbin/itcharter
cp -fd /usr/local/bin/redis-server $COMPILEDIR/usr/sbin/statsredis

echo "Compressing $COMPILE_FILE"

cd $COMPILEDIR
tar -czvf $COMPILE_FILE *
echo "Done..."
