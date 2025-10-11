#! /bin/bash

MAINURL="http://beta.urlfilterdb.com/ufdbGuard-1.29-beta10.tar.gz"
WORKDIR=/root/ufdbguardd-`date +%m%d`
WORKDIRMAKE=""
BASESDIR="/var/lib/ufdbguard"
LOGDIR="/var/log/ufdbguard"
#SOCKETS="--without-unix-sockets"
CONFIGUREOPTS=" --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man" --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var --with-ufdb-dbhome=$BASESDIR --with-ufdb-user=squid --with-ufdb-config=/etc/ufdbguard --with-ufdb-logdir=$LOGDIR $SOCKETS"
tzname="ufdbguard.tar.gz"

	if [ -f $WORKDIR  ]; then
  		echo "Remove old $WORKDIR  "
		rm -rf $WORKDIR  
	fi

echo "Creating $WORKDIR"
mkdir -p $WORKDIR
echo "Downloading $MAINURL... please wait.."
wget $MAINURL -O $WORKDIR/ufdb.tar.gz >/dev/null 2>&1
echo "Extracting $WORKDIR/ufdb.tar.gz ...."

cd $WORKDIR && tar -xf $WORKDIR/ufdb.tar.gz -C $WORKDIR/ >/dev/null 2>&1

echo "Done..."

Dirlist=$(find $WORKDIR -type d)
for direc in $Dirlist ; do
	if [ -f "$direc/configure"  ]; then
  		WORKDIRMAKE=$direc
	fi

done


echo "Parent of $WORKDIRMAKE ?"

echo "Source directory will be $WORKDIRMAKE ($tzname)"

if [ ! -f $WORKDIRMAKE/configure  ]; then
echo "$WORKDIRMAKE/configure no such file, aborting"
exit 0
fi


echo "Please wait, compile..."
cd $WORKDIRMAKE && ./configure $CONFIGUREOPTS >/dev/null 2>&1
if [ -f "$WORKDIRMAKE/configure.in"  ]; then
	make >/dev/null 2>&1 && make install >/dev/null 2>&1
	echo "Creating directory $WORKDIR/compiled/"
	mkdir -p $WORKDIR/compiled/usr/sbin
	mkdir -p $WORKDIR/compiled/usr/bin
	mkdir -p $WORKDIR/compiled/var/log/ufdbguard
	mkdir -p $WORKDIR/compiled/etc/ufdbguard
	mkdir -p $WORKDIR/compiled/var/lib/squidguard
	mkdir -p $WORKDIR/compiled/var/lib/squidguard/security
	mkdir -p $WORKDIR/compiled/etc/init.d

	cp -f /usr/bin/ufdbguardd $WORKDIR/compiled/usr/bin/
	cp -f /usr/bin/ufdbgclient $WORKDIR/compiled/usr/bin/
	cp -f /usr/bin/ufdbsignal $WORKDIR/compiled/usr/bin/
	cp -f /usr/bin/ufdbUpdate $WORKDIR/compiled/usr/bin/
	cp -f /usr/bin/ufdbConvertDB $WORKDIR/compiled/usr/bin/
	cp -f /usr/bin/ufdbGenTable $WORKDIR/compiled/usr/bin/
	cp -f /usr/bin/ufdbAnalyse $WORKDIR/compiled/usr/bin/
	cp -f /usr/bin/ufdbhttpd $WORKDIR/compiled/usr/bin/
	cp -f /etc/init.d/ufdb $WORKDIR/compiled/etc/init.d/
	echo "Please wait, creating package /root/$tzname..."
	cd $WORKDIR/compiled && tar -czf /root/$tzname *
	echo "done..." 

fi