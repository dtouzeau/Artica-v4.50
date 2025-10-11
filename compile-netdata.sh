
# bash <(curl -Ss https://my-netdata.io/kickstart.sh) all

# apt-get install autoconf-archive autogen libmnl-dev lm-sensors nodejs python-mysqldb python-psycopg2 python-pymongo python-yaml


#  - the daemon     at /usr/sbin/netdata
#   - config files   in /etc/netdata
#   - web files      in /usr/share/netdata
#   - plugins        in /usr/libexec/netdata
#   - cache files    in /var/cache/netdata
#   - db files       in /var/lib/netdata
#  - log files      in /var/log/netdata
#   - pid file       at /var/run/netdata.pid
#   - logrotate file at /etc/logrotate.d/netdata
rm -rf /etc/netdata/*
rm -rf /var/lib/netdata/*
rm -rf /var/cache/netdata/*
rm -rf /var/lib/netdata/*
rm -rf /usr/lib/netdata
rm -rf /usr/share/netdata
rm -f /usr/sbin/netdata
bash <(curl -Ss https://my-netdata.io/kickstart.sh) all


rm -rf /root/netdata-builder
rm /root/netdata-builder.tar.gz
mkdir -p /root/netdata-builder/usr/sbin
mkdir -p /root/netdata-builder/etc/netdata
mkdir -p /root/netdata-builder/usr/share/netdata
mkdir -p /root/netdata-builder/usr/libexec/netdata
mkdir -p /root/netdata-builder/usr/lib/netdata
mkdir -p /root/netdata-builder/var/cache/netdata
mkdir -p /root/netdata-builder/var/lib/netdata
mkdir -p /root/netdata-builder/var/log/netdata

strip -s /usr/sbin/netdata
cp -fd /usr/sbin/netdata /root/netdata-builder/usr/sbin/
cp -rfvd /etc/netdata/* /root/netdata-builder/etc/netdata/
cp -rfvd /usr/share/netdata/* /root/netdata-builder/usr/share/netdata/
cp -rfvd /usr/libexec/netdata/* /root/netdata-builder/usr/libexec/netdata/
cp -rfvd /var/lib/netdata/* /root/netdata-builder/var/lib/netdata/*
cp -rfvd /usr/lib/netdata/* /root/netdata-builder/usr/lib/netdata/

VERSION="1.11.0"


cd /root/netdata-builder
tar -czvf /root/netdata-NEXCOMPILED.tar.gz *
echo "/root/netdata-NEXCOMPILED.tar.gz"



#echo 1 >/sys/kernel/mm/ksm/run
#echo 1000 >/sys/kernel/mm/ksm/sleep_millisecs




