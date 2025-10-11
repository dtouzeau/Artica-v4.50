#!/usr/bin/env bash



KSC_DB="/home/dtouzeau/ksc64_15.1.0-11728_amd64.deb"
KSCW="/home/dtouzeau/ksc-web-console-15.1.489.x86_64.deb"
WOKDIR="/home/dtouzeau/KSC"
mkdir -p $WOKDIR
cp $KSC_DB  $WOKDIR/ksc64.deb
cp $KSCW  $WOKDIR/kscWeb.deb
cd $WOKDIR || exit
ar -x ksc64.deb
rm -f ksc64.deb
rm -f control.tar.gz debian-binary
tar -xf data.tar.gz
rm data.tar.gz
ar -x kscWeb.deb
rm -f kscWeb.deb
tar -xf data.tar.xz
rm -f control.tar.gz debian-binary data.tar.xz
