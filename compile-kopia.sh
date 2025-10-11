#!/usr/bin/env bash
TEMPDIR="$HOME/kopia-temp"
PP="https://github.com/kopia/kopia"
LATEST=$(curl -sI $PP/releases/latest | grep -i '^location:' | sed -E 's|.*/tag/v([0-9\.]+).*|\1|')


DEBFILE="kopia-ui_${LATEST}_amd64.deb"
echo "Version: $LATEST , UI $DEBFILE"

PREFIX="$PP/releases/download"
URL="$PREFIX/v$LATEST/kopia-$LATEST-linux-x64.tar.gz"
WORKDIR="$HOME/kopia-compile"

EXTRACTDIR="$TEMPDIR/kopia-$LATEST-linux-x64"

rm -rf $WORKDIR
rm -rf $TEMPDIR
mkdir -p $TEMPDIR
if [ ! -d $TEMPDIR ]; then
  echo "$TEMPDIR permission denied"
  exit 1
fi
mkdir -p $WORKDIR/usr/local/bin
mkdir -p $WORKDIR/usr/share
mkdir -p $WORKDIR/opt
wget $URL -O $TEMPDIR/kopia-$LATEST-linux-x64.tar.gz
tar -xf $TEMPDIR/kopia-$LATEST-linux-x64.tar.gz -C $TEMPDIR
rm -f $TEMPDIR/kopia-$LATEST-linux-x64.tar.gz
if [ ! -d $EXTRACTDIR ]; then
  echo "$EXTRACTDIR no such dir"
  exit 1
fi
if [ ! -f $EXTRACTDIR/kopia ]; then
  echo "$EXTRACTDIR/kopia no such file"
  exit 1
fi
cp $EXTRACTDIR/kopia $WORKDIR/usr/local/bin/
chmod 0755 $WORKDIR/usr/local/bin/kopia
rm -rf $TEMPDIR
mkdir -p $TEMPDIR

URL="$PREFIX/v$LATEST/$DEBFILE"
wget $URL -O $TEMPDIR/$DEBFILE
if [ ! -f $TEMPDIR/$DEBFILE ]; then
  echo "$TEMPDIR/$DEBFILE no such file"
  exit 1
fi
cd $TEMPDIR || exit 1
ar -x $DEBFILE
if [ ! -f $TEMPDIR/data.tar.xz ]; then
  echo "$TEMPDIR/data.tar.xz no such file"
  exit 1
fi
tar -xf data.tar.xz -C $WORKDIR/
rm -rf $WORKDIR/usr/share/doc
rm -rf $WORKDIR/usr/share/applications
rm -rf $TEMPDIR
mkdir $TEMPDIR
cd $WORKDIR || exit 1
echo "Compressing $TEMPDIR/kopia-$LATEST.tar.gz"
tar -czf $TEMPDIR/kopia-$LATEST.tar.gz *
