#/bin/sh

cd /root
# rm -rf /root/pdns || true
# git clone https://github.com/PowerDNS/pdns.git
#cd /root/pdns/pdns/dnsdistdist
# autoreconf -vi
# ./configure --prefix=/usr --sysconfdir=/etc/powerdns --mandir=/usr/share/man --infodir=/usr/share/info --libdir=/usr/lib --libexecdir=/usr/lib --enable-dns-over-tls --enable-dnscrypt --enable-dns-over-https --with-lua=lua5.3 --without-systemd --disable-systemd 
# make
# make install

# 1.9 for debian 12----------

# nghttp2
# wget https://github.com/nghttp2/nghttp2/releases/download/v1.62.1/nghttp2-1.62.1.tar.bz2
# tar xf ...
# cd nghttp2-1.62.1/

# curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh
# source $HOME/.cargo/env
# PATH must be /root/.cargo/bin:/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/snap/bin
# git clone --recursive https://github.com/cloudflare/quiche
# cd quiche
# cargo build --release
# librairies are in /root/quiche/target/release

# export QUICHE_LIB_DIR=/root/quiche/target/release
# export QUICHE_INCLUDE_DIR=/root/quiche/include
# ./configure --prefix=/usr --sysconfdir=/etc/powerdns --mandir=/usr/share/man --infodir=/usr/share/info --libdir=/usr/lib --libexecdir=/usr/lib --enable-dns-over-tls --enable-dnscrypt --enable-dns-over-https --with-lua=lua5.3 --without-systemd --disable-systemd --with-quiche=/root/quiche/target/release  --enable-dns-over-quic --enable-dns-over-http3  --enable-dns-over-tls

rm -rf /root/dnsdist-builder || true
mkdir -p /root/dnsdist-builder/usr/bin
cp -f /usr/bin/dnsdist /root/dnsdist-builder/usr/bin/dnsdist
strip -s /root/dnsdist-builder/usr/bin/dnsdist
cd /root/dnsdist-builder
tar -czvf /root/dnsdist-x.x.x.tar.gz *



