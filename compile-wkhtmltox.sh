#!/bin/sh

# https://wkhtmltopdf.org/downloads.html
BASPATH="/home/dtouzeau/Téléchargements"
TVER="0.12.6-1"
TDIR="/home/dtouzeau/developpement/build-cdd-debian8/buildcdd/buildcdd8"

apt-get install ar
rm -rf $BASPATH/wkhtmltox|| true
mkdir -p $BASPATH/wkhtmltox/

wget https://github.com/wkhtmltopdf/packaging/releases/download/$TVER/wkhtmltox_$TVER.buster_amd64.deb -O $BASPATH/wkhtmltox_$TVER.buster_amd64.deb
ar -x $BASPATH/wkhtmltox_$TVER.buster_amd64.deb --output=$BASPATH/wkhtmltox/
rm -f $BASPATH/wkhtmltox_$TVER.buster_amd64.deb

mkdir -p $BASPATH/wkhtmltox/data
tar xf $BASPATH/wkhtmltox/data.tar.xz -C $BASPATH/wkhtmltox/data/

mkdir -p $BASPATH/python-pdfkit
wget http://ftp.us.debian.org/debian/pool/main/p/pdfkit/python-pdfkit_0.6.1-1_all.deb -O $BASPATH/python-pdfkit/package.deb
ar -x $BASPATH/python-pdfkit/package.deb --output=$BASPATH/python-pdfkit/
rm -f $BASPATH/python-pdfkit/package.deb

tar xf $BASPATH/python-pdfkit/data.tar.xz -C $BASPATH/wkhtmltox/data/
rm -rf $BASPATH/python-pdfkit

cd $BASPATH/wkhtmltox/data
tar -czf $BASPATH/wkhtmltox/wkhtmltopdf-$TVER.tar.gz *
rm -f $TDIR/wkhtmltopdf-$TVER.tar.gz
mv $BASPATH/wkhtmltox/wkhtmltopdf-$TVER.tar.gz $TDIR/wkhtmltopdf-$TVER.tar.gz
rm -rf $BASPATH/wkhtmltox
chown dtouzeau:dtouzeau $TDIR/wkhtmltopdf-$TVER.tar.gz
echo "$TDIR/wkhtmltopdf-$TVER.tar.gz DONE"

