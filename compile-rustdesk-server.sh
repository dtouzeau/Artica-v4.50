#!/bin/sh

ACCOUNT="dtouzeau"
# Get version number by browsing https://github.com/rustdesk/rustdesk-server/releases/tag
VERSION="1.1.8"
URL="https://github.com/rustdesk/rustdesk-server/releases/download/$VERSION/rustdesk-server-linux-amd64.zip"
WORKDIRROOT="/home/$ACCOUNT/rustdesk-explode"
WORKDIR="$WORKDIRROOT/$VERSION"
FILEZIP="$WORKDIR/$VERSION.zip"
COMPILEROOT="/home/$ACCOUNT/rustdesk-compile"
COMPILEDIR="$COMPILEROOT/usr/local/bin"
FINAL="$WORKDIRROOT/$VERSION.tar.gz"
mkdir -p $COMPILEDIR


if [ -d $WORKDIR ];then
  rm -rf $WORKDIR
fi
  mkdir -p $WORKDIR

wget $URL -O $FILEZIP
if [ ! -f $FILEZIP ];then
  echo "Downloading failed"
  exit 1
fi
echo "Extracting in $WORKDIR"
unzip $FILEZIP -d $WORKDIR

if [ ! -d $WORKDIR/amd64 ];then
  echo "Extracting in $WORKDIR/amd64 required, something changed when inflating ?"
    exit 1
fi

BINS="hbbr hbbs rustdesk-utils"

for file in $BINS; do
  echo "Copy: $file"
  if [ ! -f "$WORKDIR/amd64/$file" ]; then
    echo "$WORKDIR/amd64/$file no such file"
    exit 1
  fi
  cp -fd $WORKDIR/amd64/$file $COMPILEDIR/$file
  chmod 0755 $COMPILEDIR/$file
done

echo "Compressing into $FINAL"
cd $COMPILEROOT
tar -czvf $FINAL *
rm -rf $WORKDIR

if [ -d $WORKDIR ];then
  rm -rf $WORKDIR
fi

