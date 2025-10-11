#!/bin/sh

VERSION="4.7.2"
RECURSOR_VERSION="4.7.3"
DNSDIST_VERSION="1.7.3"

apt-get install bison ragel libedit-dev liblua5.3-dev libsodium-dev libsnmp-dev libpcap0.8-dev libmariadbclient-dev-compat libkrb5-dev libboost-dev libboost-serialization-dev libboost-program-options-dev libyaml-cpp-dev libpcap-dev libproc-pid-file-perl

if [ ! -f "/root/pdns-$VERSION.tar.bz2" ]
then
	wget https://downloads.powerdns.com/releases/pdns-$VERSION.tar.bz2 /root/pdns-$VERSION.tar.bz2
fi

if [ ! -f "/root/pdns-$VERSION.tar.bz2" ]
then
	echo "/root/pdns-$VERSION.tar.bz2 no such file"
	exit 0
fi

if [ ! -d "/root/pdns-$VERSION" ] 
then
	tar -xf /root/pdns-$VERSION.tar.bz2 -C /root/
fi
if [ ! -d "/root/pdns-$VERSION" ] 
then
	echo "/root/pdns-$VERSION no such dir"
	exit 0
fi

if [ ! -f "/root/pdns-$VERSION/Makefile" ]
then
	echo "/root/pdns-$VERSION/Makefile no such file --- Start configure..."
	cd /root/pdns-$VERSION/
	./configure --prefix=/usr --enable-verbose-logging --sysconfdir=/etc/powerdns --mandir=/usr/share/man --infodir=/usr/share/info --libdir=/usr/lib --libexecdir=/usr/lib --with-modules=gmysql --with-dynmodules=pipe  --without-sqlite3 --enable-tools
fi

if [ ! -f "/root/pdns-$VERSION/Makefile" ]
then
	echo "Compile Artica Error, /root/pdns-$VERSION/Makefile no such file"
	exit 0
fi
if [ ! -f "/root/pdns-$VERSION/pdns/arguments.o" ]
then
	make && make install
fi
# -------------------------------------------------------------------------------------------------------------------------------------
echo "###################################################################################"
echo "######################### PDNS RECURSOR $RECURSOR_VERSION #########################"
echo "###################################################################################"
cd /root
if [ ! -f "/root/pdns-recursor-$RECURSOR_VERSION.tar.bz2" ]
then
	wget https://downloads.powerdns.com/releases/pdns-recursor-$RECURSOR_VERSION.tar.bz2 /root/pdns-recursor-$RECURSOR_VERSION.tar.bz2
fi
if [ ! -d "/root/pdns-recursor-$RECURSOR_VERSION" ] 
then
	tar -xf /root/pdns-recursor-$RECURSOR_VERSION.tar.bz2 -C /root/
fi
if [ ! -f "/root/pdns-recursor-$RECURSOR_VERSION/Makefile" ]
then
	cd /root/pdns-recursor-$RECURSOR_VERSION
	./configure --prefix=/usr --sysconfdir=/etc/powerdns --mandir=/usr/share/man --infodir=/usr/share/info --libdir=/usr/lib --libexecdir=/usr/lib --with-lua=lua5.3
fi
if [ ! -f "/root/pdns-recursor-$RECURSOR_VERSION/Makefile" ]
then 
	echo "/root/pdns-recursor-$RECURSOR_VERSION/Makefile no such file"
	exit 0
fi
if [ ! -f "/root/pdns-recursor-$RECURSOR_VERSION/Makefile" ]
then
	echo "/root/pdns-recursor-$RECURSOR_VERSION/Makefile no such file"
	exit 0
fi
if [ ! -f "/root/pdns-recursor-$RECURSOR_VERSION/arguments.o" ]
then
	make && make install
fi
# -------------------------------------------------------------------------------------------------------------------------------------

echo "###################################################################################"
echo "######################### dnsdist $DNSDIST_VERSION #########################"
echo "###################################################################################"

cd /root
if [ ! -f "/root/dnsdist-$DNSDIST_VERSION.tar.bz2" ]
then
	wget https://downloads.powerdns.com/releases/dnsdist-$DNSDIST_VERSION.tar.bz2 /root/dnsdist-$DNSDIST_VERSION.tar.bz2
fi
if [ ! -f "/root/dnsdist-$DNSDIST_VERSION.tar.bz2" ]
then
	echo "/root/dnsdist-$DNSDIST_VERSION.tar.bz2 no such file"
	exit
fi
if [ ! -d "/root/dnsdist-$DNSDIST_VERSION" ] 
then
	tar -xf /root/dnsdist-$DNSDIST_VERSION.tar.bz2 -C /root/
fi
if [ ! -f "/root/dnsdist-$DNSDIST_VERSION/Makefile" ]
then
	cd /root/dnsdist-$DNSDIST_VERSION
	./configure --prefix=/usr --sysconfdir=/etc/powerdns --mandir=/usr/share/man --infodir=/usr/share/info --libdir=/usr/lib --libexecdir=/usr/lib --enable-dns-over-tls --enable-dnscrypt --enable-dns-over-https --with-lua=lua5.3
fi
if [ ! -f "/root/dnsdist-$DNSDIST_VERSION/Makefile" ]
then
	echo "/root/dnsdist-$DNSDIST_VERSION/Makefile no such file"
	exit
fi
if [ ! -f "/root/dnsdist-$DNSDIST_VERSION/bpf-filter.o" ]
then
	make && make install
fi

cd /root



# -------------------------------------------------------------------------------------------------------------------------------------
echo "###################################################################################"
echo "######################### DSC GIT HUB #########################"
echo "###################################################################################"
cd /root
if [ ! -d "/root/dsc" ]
then
	git clone https://github.com/DNS-OARC/dsc.git
fi
if [ ! -f "/root/dsc/configure" ]
then
	cd /root/dsc
	git submodule init
	git submodule add https://github.com/DNS-OARC/pcap-thread.git src/pcap-thread
	git submodule update --init --recursive
	./autogen.sh
	./configure --prefix=/usr --mandir=/usr/share/man --infodir=/usr/share/info --libdir=/usr/lib --libexecdir=/usr/lib 
fi

if [ ! -f "/root/dsc/src/asn_index.o" ]
then
	cd /root/dsc
	make && make install
fi









# -------------------------------------------------------------------------------------------------------------------------------------


rm -f /root/pdns-builder.tar.gz
rm -rf /root/pdns-builder
mkdir -p /root/pdns-builder/usr/sbin
mkdir -p /root/pdns-builder/usr/bin
mkdir -p /root/pdns-builder/usr/lib/pdns
mkdir -p /root/pdns-builder/usr/local/bin
mkdir -p /root/pdns-builder/usr/share/doc/pdns
strip -s /usr/sbin/pdns_recursor
strip -s /usr/bin/dnsdist
strip -s /usr/sbin/pdns_server
strip -s /usr/bin/rec_control 
strip -s /usr/bin/pdns_control 
strip -s /usr/bin/pdnsutil 
strip -s /usr/bin/zone2sql 
strip -s /usr/bin/zone2json 
strip -s /usr/bin/dnsgram 
strip -s /usr/bin/dnsreplay 
strip -s /usr/bin/dnsscan 
strip -s /usr/bin/dnsscope 
strip -s /usr/bin/dnswasher 
strip -s /usr/bin/dumresp 
strip -s /usr/bin/pdns_notify 
strip -s /usr/bin/nproxy 
strip -s /usr/bin/nsec3dig 
strip -s /usr/bin/saxfr 
strip -s /usr/bin/stubquery 
strip -s /usr/bin/ixplore 
strip -s /usr/bin/sdig 
strip -s /usr/bin/calidns 
strip -s /usr/bin/dnsbulktest 
strip -s /usr/bin/dnstcpbench 
# strip -s /usr/bin/zone2ldap
strip -s /usr/bin/dsc
cp -fd /usr/bin/dnsdist /root/pdns-builder/usr/bin/
cp -fd /usr/sbin/pdns_recursor /root/pdns-builder/usr/sbin/
cp -fd /usr/sbin/pdns_server /root/pdns-builder/usr/sbin/
cp -fd /usr/bin/rec_control /root/pdns-builder/usr/bin/
cp -fd /usr/bin/pdns_control /root/pdns-builder/usr/bin/
cp -fd /usr/bin/pdnsutil /root/pdns-builder/usr/bin/
cp -fd /usr/bin/zone2sql /root/pdns-builder/usr/bin/
cp -fd /usr/bin/zone2json /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnsgram /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnsreplay /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnsscan /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnsscope /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnswasher /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dumresp /root/pdns-builder/usr/bin/
cp -fd /usr/bin/pdns_notify /root/pdns-builder/usr/bin/
cp -fd /usr/bin/nproxy /root/pdns-builder/usr/bin/
cp -fd /usr/bin/nsec3dig /root/pdns-builder/usr/bin/
cp -fd /usr/bin/saxfr /root/pdns-builder/usr/bin/
cp -fd /usr/bin/stubquery /root/pdns-builder/usr/bin/
cp -fd /usr/bin/ixplore /root/pdns-builder/usr/bin/
cp -fd /usr/bin/sdig /root/pdns-builder/usr/bin/
cp -fd /usr/bin/calidns /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnsbulktest /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dnstcpbench /root/pdns-builder/usr/bin/
#cp -fd /usr/bin/zone2ldap /root/pdns-builder/usr/bin/
cp -fd /usr/bin/dsc /root/pdns-builder/usr/bin/

cp /usr/bin/dsc /root/pdns-builder/usr/bin/
cp -rfd /usr/share/doc/pdns/* /root/pdns-builder/usr/share/doc/pdns/
cp -rfd /usr/lib/pdns/* /root/pdns-builder/usr/lib/pdns/
chmod -R 0755 /root/pdns-builder/usr/bin /root/pdns-builder/usr/sbin /root/pdns-builder/usr/local/bin/

cd /root/pdns-builder
rm -f /root/pdns-builder-$VERSION.tar.gz
tar -czvf /root/pdns-builder-$VERSION.tar.gz *
cd /root
echo "/root/pdns-builder-$VERSION.tar.gz done"

