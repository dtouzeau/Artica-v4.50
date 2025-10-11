#!/bin/sh
. /etc/os-release
# apt-get install libpng-dev libpixman-1-dev libxml2-dev libcairo2 libcairo2-dev libpango1.0-dev libpangocairo-1.0-0


# wget https://github.com/oetiker/rrdtool-1.x/releases/download/v1.8.0/rrdtool-1.8.0.tar.gz
# cd /root/rrdtool-1.8.0 && ./configure --disable-python --disable-perl --disable-lua --disable-ruby --disable-tcl --prefix=/usr/local

if [ -d /root/compile-rrd ]; then
  rm -rf /root/compile-rrd
fi

mkdir -p /root/compile-rrd/usr/local/lib
mkdir -p /root/compile-rrd/usr/local/bin
mkdir -p /root/compile-rrd/usr/local/lib/pango/1.6.0/modules
mkdir -p /root/compile-rrd/usr/local/etc/fonts
mkdir -p /root/compile-rrd/usr/lib/x86_64-linux-gnu
mkdir -p /root/compile-rrd/usr/lib/x86_64-linux-gnu/pkgconfig

cp --preserve=all /usr/lib/x86_64-linux-gnu/libz.a /root/compile-rrd/usr/lib/x86_64-linux-gnu/
cp --preserve=all /usr/lib/x86_64-linux-gnu/libz.so /root/compile-rrd/usr/lib/x86_64-linux-gnu/
cp --preserve=all /usr/lib/x86_64-linux-gnu/pkgconfig/zlib.pc /root/compile-rrd/usr/lib/x86_64-linux-gnu/pkgconfig/
cp --preserve=all /usr/lib/x86_64-linux-gnu/libpixman-1.a
cp --preserve=all /usr/lib/x86_64-linux-gnu/libpixman-1.so
cp --preserve=all /usr/lib/x86_64-linux-gnu/pkgconfig/pixman-1.pc /root/compile-rrd/usr/lib/x86_64-linux-gnu/pkgconfig/
cp --preserve=all /usr/lib/x86_64-linux-gnu/pkgconfig/libxml-2.0.pc /root/compile-rrd/usr/lib/x86_64-linux-gnu/pkgconfig/
cp --preserve=all /usr/lib/x86_64-linux-gnu/libxml2.so


cp --preserve=all /usr/bin/rrdtool /root/compile-rrd/usr/local/bin/
cp --preserve=all /usr/bin/rrdupdate /root/compile-rrd/usr/local/bin/
cp --preserve=all /usr/bin/rrdcgi /root/compile-rrd/usr/local/bin/
cp --preserve=all /usr/bin/rrdcached /root/compile-rrd/usr/local/bin/
cp --preserve=all /usr/local/bin/freetype-config /root/compile-rrd/usr/local/bin/

cd /root/compile-rrd && tar -czvf /root/rrdtool-x.x.x.tar.gz *



