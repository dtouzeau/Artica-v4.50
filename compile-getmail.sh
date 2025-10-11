#!/bin/sh

TVER="getmail-5.15"
TDIR="/root/getmail-compile"
TFILE="/root/$TVER-compiled.tar.gz"

mkdir -p $TDIR/usr/local/bin
mkdir -p $TDIR/usr/local/lib/python2.7/dist-packages/getmailcore
mkdir -p $TDIR/usr/share/doc/$TVER

rm -rf /root/$TVER || true
rm -f /root/$TVER.tar.gz || true
wget http://pyropus.ca/software/getmail/old-versions/$TVER.tar.gz -O /root/$TVER.tar.gz
tar -xf /root/$TVER.tar.gz -C /root/
cd /root/$TVER
python setup.py install

cp -fd /usr/local/lib/python2.7/dist-packages/$TVER.egg-info $TDIR/usr/local/lib/python2.7/dist-packages/
cp -fd /usr/local/bin/getmail_fetch $TDIR/usr/local/bin/
cp -fd /usr/local/bin/getmail $TDIR/usr/local/bin/
cp -fd /usr/local/bin/getmail_mbox $TDIR/usr/local/bin/
cp -fd /usr/local/bin/getmail-gmail-xoauth-tokens $TDIR/usr/local/bin/
cp -fd /usr/local/bin/getmail_maildir $TDIR/usr/local/bin/
cp -rfd /usr/local/lib/python2.7/dist-packages/getmailcore/* $TDIR/usr/local/lib/python2.7/dist-packages/getmailcore/
cp -rfd /usr/share/doc/$TVER/* $TDIR/usr/share/doc/$TVER/


cd $TDIR
tar -czvf $TFILE *
echo "$TFILE done..."
