#!/bin/sh
# apt-get -y install libxtables-dev pkg-config default-libmysqlclient-dev linux-headers-$(uname -r) libsqlite3-dev
rm -rf /root/ntopng
rm -rf /root/libmaxminddb

git clone https://github.com/ntop/PF_RING.git
cd PF_RING/kernel
make && make install
insmod ./pf_ring.ko
cd ../userland
make && make install


git clone --recursive https://github.com/maxmind/libmaxminddb.git /root/libmaxminddb
cd /root/libmaxminddb/
./bootstrap
./configure --prefix=/usr
make
make install


git clone https://github.com/ntop/ntopng.git /root/ntopng
cd /root/ntopng
./autogen.sh
./configure --prefix=/usr
make 
make install








