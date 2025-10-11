#!/bin/sh
tarball="/root/maltrail-0.52.tar.gz"
rm -rf /root/maltrail-compile
rm -rf /usr/local/maltrail

git clone --depth 1 https://github.com/stamparm/maltrail.git /usr/local/maltrail

mkdir -p /root/maltrail-compile/usr/local/maltrail
mkdir -p /root/maltrail-compile/usr/local/lib/python3.7/dist-packages
cp -rf /usr/local/maltrail/* /root/maltrail-compile/usr/local/maltrail/
cp -rf /usr/local/lib/python3.7/dist-packages/* /root/maltrail-compile/usr/local/lib/python3.7/dist-packages/

cd /root/maltrail-compile
tar -czvf $tarball *
echo $tarball