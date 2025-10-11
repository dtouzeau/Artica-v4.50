#!/bin/sh

# apt-get install libgnutls28-dev libgnutls-dane0 libpcap-dev libprotobuf-c-dev protobuf-c-compiler libboost-all-dev clang libfstrm-dev libnghttp2-dev

# Get https://raw.githubusercontent.com/PowerDNS/pdns/dnsdist-1.9.6/builder-support/helpers/install_quiche.sh

# ./configure --prefix=/usr --sysconfdir=/etc/powerdns --mandir=/usr/share/man --infodir=/usr/share/info --libdir=/usr/lib --libexecdir=/usr/lib --enable-dns-over-tls --enable-dnscrypt --enable-dns-over-quic --enable-dns-over-https --with-lua=lua5.3 --without-systemd --enable-dns-over-http3 --disable-systemd --with-quiche --with-net-snmp --enable-dnstap