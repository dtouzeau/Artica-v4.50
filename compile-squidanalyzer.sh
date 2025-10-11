#!/bin/sh

rm -rf /root/squidanalyzer
git clone https://github.com/darold/squidanalyzer.git /root/squidanalyzer
cd /root/squidanalyzer
perl Makefile.PL LOGFILE=/var/log/squid3/squidanalyzer.log BINDIR=/usr/bin CONFDIR=/etc HTMLDIR=/home/artica/squidanalyzer/proxyreport BASEURL=/proxyreport MANDIR=/usr/share/man/man3 DOCDIR=/usr/share/doc/squidanalyzer
make && make install


rm -rf /root/SquidAnalyzer-builder
rm -f /root/SquidAnalyzer.tar.gz
mkdir -p /root/SquidAnalyzer-builder/home/artica/squidanalyzer/proxyreport
mkdir -p /root/SquidAnalyzer-builder/usr/local/share/perl/5.24.1
mkdir -p /root/SquidAnalyzer-builder/etc
mkdir -p /root/SquidAnalyzer-builder/usr/bin

cp -fd /usr/local/share/perl/5.24.1/SquidAnalyzer.pm /root/SquidAnalyzer-builder/usr/local/share/perl/5.24.1/
cp -fd /usr/bin/squid-analyzer /root/SquidAnalyzer-builder/usr/bin/
cp -fd /etc/squidanalyzer.conf /root/SquidAnalyzer-builder/etc/squidanalyzer.default.conf
cp -rfd /home/artica/squidanalyzer/proxyreport/* /root/SquidAnalyzer-builder/home/artica/squidanalyzer/proxyreport/

cd /root/SquidAnalyzer-builder
tar -czf /root/SquidAnalyzer.tar.gz *
echo "/root/SquidAnalyzer.tar.gz done..."


