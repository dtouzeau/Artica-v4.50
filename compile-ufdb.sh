#!/bin/sh


# apt-get install libssl1.0-dev build-essential bison flex libbz2-dev libz-dev
# ------------------ Main Web-Filtering
#./configure --prefix=/usr --includedir=${prefix}/include --mandir=${prefix}/share/man --infodir=${prefix}/share/info --sysconfdir=/etc --localstatedir=/var --with-ufdb-dbhome=/var/lib/squidguard --with-ufdb-user=squid --with-ufdb-config=/etc/ufdbguard --with-ufdb-logdir=/var/log/ufdbguard --without-unix-sockets 

# ------------------ Main ufbcat
#./configure --prefix=/opt/ufdbcat --includedir="${prefix}/include" --mandir="${prefix}/share/man" --infodir="${prefix}/share/info" --sysconfdir=/etc/ufdbcat --localstatedir=/opt/ufdbcat --with-ufdb-logdir=/var/log/ufdbcat --with-ufdb-dbhome=/home/ufdbcat --with-ufdb-user=root --with-ufdb-config=/etc/ufdbcat --with-ufdb-logdir=/var/log/ufdbcat --with-ufdb-config=/etc/ufdbcat --with-ufdb-piddir=/var/run/ufdbcat --without-unix-sockets 
# ------------------ Main dnsfilterd
#./configure --prefix=/opt/dnsfilterd --includedir="${prefix}/include" --mandir="${prefix}/share/man" --infodir="${prefix}/share/info" --sysconfdir=/etc/dnsfilterd --localstatedir=/opt/dnsfilterd --with-ufdb-logdir=/var/log/dnsfilterd --with-ufdb-dbhome=/home/dnsfilterd --with-ufdb-user=root --with-ufdb-config=/etc/dnsfilterd --with-ufdb-logdir=/var/log/dnsfilterd --with-ufdb-config=/etc/dnsfilterd --with-ufdb-piddir=/var/run/dnsfilterd --without-unix-sockets 


rm -rf /root/compile-ufdb
rm -rf /root/ufdbguardd-*


strip -s /usr/sbin/ufdbGenTable
strip -s /usr/sbin/ufdbAnalyse
strip -s /usr/sbin/ufdbguardd
strip -s /usr/sbin/ufdbgclient

if [ -f "/opt/ufdbcat/bin/ufdbguardd" ]
then
	if [ -f "/opt/ufdbcat/bin/ufdbcatdd" ]
	then
	   echo "Remove /opt/ufdbcat/bin/ufdbcatdd"
	   rm -f /opt/ufdbcat/bin/ufdbcatdd
	fi
	echo "Move /opt/ufdbcat/bin/ufdbguardd /opt/ufdbcat/bin/ufdbcatdd"
	strip -s /opt/ufdbcat/bin/ufdbguardd
	mv /opt/ufdbcat/bin/ufdbguardd /opt/ufdbcat/bin/ufdbcatdd
fi

if [ -f "/opt/dnsfilterd/bin/ufdbguardd" ]
then
	if [ -f "/opt/dnsfilterd/bin/dnsfilterd" ]
	then
	   echo "Remove /opt/dnsfilterd/bin/dnsfilterd"
	   rm -f /opt/dnsfilterd/bin/dnsfilterd
	fi
	echo "Move /opt/dnsfilterd/bin/ufdbguardd /opt/dnsfilterd/bin/dnsfilterd"
	strip -s /opt/dnsfilterd/bin/ufdbguardd
	mv /opt/dnsfilterd/bin/ufdbguardd /opt/dnsfilterd/bin/dnsfilterd
fi


mkdir -p /root/compile-ufdb/usr/lib/x86_64-linux-gnu
mkdir -p /root/compile-ufdb/opt/dnsfilterd/bin
mkdir -p /root/compile-ufdb/opt/ufdbcat/bin
mkdir -p /root/compile-ufdb/usr/bin
mkdir -p /usr/lib/x86_64-linux-gnu
cp -fvd /usr/lib/x86_64-linux-gnu/libssl.so.1.0.0 /root/compile-ufdb/usr/lib/x86_64-linux-gnu/
cp -fvd /usr/lib/x86_64-linux-gnu/libcrypto.so.1.0.0 /root/compile-ufdb/usr/lib/x86_64-linux-gnu/
cp -fvd /usr/sbin/ufdbConvertDB /root/compile-ufdb/usr/bin/
cp -fvd /usr/sbin/ufdbGenTable /root/compile-ufdb/usr/bin/
cp -fvd /usr/sbin/ufdbAnalyse /root/compile-ufdb/usr/bin/
cp -fvd /usr/sbin/ufdbguardd /root/compile-ufdb/usr/bin/
cp -fvd /usr/sbin/ufdbgclient /root/compile-ufdb/usr/bin/
cp -fvd /usr/sbin/ufdb-pstack /root/compile-ufdb/usr/bin/
cp -fvd /opt/ufdbcat/bin/ufdbcatdd /root/compile-ufdb/opt/ufdbcat/bin/
cp -fvd /opt/dnsfilterd/bin/dnsfilterd /root/compile-ufdb/opt/dnsfilterd/bin/


VERSION=`ufdbguardd -v 2>&1| grep -E "ufdbguardd\s+[0-9\.]+" | cut -s -d' ' -f2`

echo "$VERSION"

cd /root/compile-ufdb

tar -czvf /root/ufdbguardd-$VERSION.tar.gz *
echo "/root/ufdbguardd-$VERSION.tar.gz DONE..."



