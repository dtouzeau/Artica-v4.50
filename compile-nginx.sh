#!/bin/sh
# voir compile-openrestry.sh
. /etc/os-release

	if [ -f /usr/local/modsecurity/lib/libmodsecurity.so.3.0.3 ]; then
		rm -f /usr/local/modsecurity/lib/libmodsecurity.so.3.0.3
	fi
	if [ -f /usr/local/modsecurity/lib/libmodsecurity.so.3.0.4 ]; then
		rm -f /usr/local/modsecurity/lib/libmodsecurity.so.3.0.4
	fi

	if [ ! -f /usr/sbin/nginx ]; then
	  echo "/usr/sbin/nginx no such file"
	  exit 1
	fi

	strip -s /usr/sbin/nginx
	rm -rf /root/nginx-builder || true
	mkdir -p /root/nginx-builder/usr/share/nginx

		if [ -d /opt/verynginx ]; then
		  mkdir -p /root/nginx-builder/opt/verynginx
  	  cp -rf --preserve=all /opt/verynginx/* /root/nginx-builder/opt/verynginx/
  	fi

	mkdir -p /root/nginx-builder/usr/sbin
	mkdir -p /root/nginx-builder/etc/nginx
	mkdir -p /root/nginx-builder/var/log/nginx
	mkdir -p /root/nginx-builder/usr/local/modsecurity/lib/
	cp -f --preserve=all /usr/sbin/nginx /root/nginx-builder/usr/sbin/
	cp -rf --preserve=all /usr/share/nginx/* /root/nginx-builder/usr/share/nginx/
	cp -rf --preserve=all /etc/nginx/* /root/nginx-builder/etc/nginx/
	cp -rf --preserve=all /usr/local/modsecurity/bin/* /root/nginx-builder/usr/sbin/
	cp -rf --preserve=all /usr/local/modsecurity/lib/* /root/nginx-builder/usr/local/modsecurity/lib/

	if [ -d /opt/verynginx ]; then
	  cp -rf --preserve=all /opt/verynginx/* /root/nginx-builder/opt/verynginx/
	fi

	rm -f /root/nginx-builder/etc/nginx/nginx.conf

	chmod -R 0755 /root/nginx-builder/usr/sbin/*
  VERSION=`/usr/sbin/nginx -v 2>&1| cut -s -d '/' -f2`

  FINAL="/root/nginx-Debian$VERSION_ID-$VERSION.tar.gz"
  echo "Compressing $FINAL"

  if [ -f $FINAL ]; then
	  rm -f $FINAL
	fi

	cd /root/nginx-builder
	tar -czvf $FINAL *
	echo "$FINAL done"


