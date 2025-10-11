#!/bin/sh

# https://github.com/DNSCrypt/doh-server/releases/tag/0.9.9
WORKDIR="/root/doh-proxy"

rm -rf /root/doh-proxy
mkdir -p /root/doh-proxy/usr/bin
 #wget https://github.com/DNSCrypt/doh-server/releases/download/$VERSION/doh-proxy_${VERSION}_amd64.deb -O $WORKDIR/doh-proxy.deb

#cd $WORKDIR
#ar x doh-proxy.deb
#tar xf data.tar.xz
#rm -f $WORKDIR/data.tar.xz
#rm -f $WORKDIR/control.tar.xz
#rm -f $WORKDIR/debian-binary
#rm -f $WORKDIR/doh-proxy.deb
#rm -rf $WORKDIR/usr/share
#tar -czvf /root/doh-proxy-$VERSION.tar.gz *
#echo "/root/doh-proxy-$VERSION.tar.gz SUCCESS"
cp /usr/bin/dnsdist /root/doh-proxy/usr/bin/doh-proxy
strip -s /root/doh-proxy/usr/bin/doh-proxy
version_output=$(dnsdist --version)

# Extract the version number using grep and cut
version=$(echo "$version_output" | grep -o 'dnsdist [0-9]*\.[0-9]*\.[0-9]*' | cut -d ' ' -f 2)
echo "Version $version"
cd /root/doh-proxy
tarball="/root/doh-proxy-$version.tar.gz"
tar -czvf $tarball *
echo "$tarball done"
