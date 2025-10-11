#!/bin/sh


RESTIC_VERSION="0.18.0"
RESTSERV_VERSION="0.14.0"
GITHUB="https://github.com/restic"
DESTDIR="/home/restic-install"
RESTIC_URL="$GITHUB/restic/releases/download/v$RESTIC_VERSION/restic_${RESTIC_VERSION}_linux_amd64.bz2"
RESTICS_URL="$GITHUB/rest-server/releases/download/v$RESTSERV_VERSION/rest-server_${RESTSERV_VERSION}_linux_amd64.tar.gz"
TEMPDIR="/home/restic-temp"
echo "Download main restic $RESTIC_VERSION"

rm -rf $TEMPDIR
rm -rf $DESTDIR
mkdir -p $TEMPDIR
mkdir -p "$DESTDIR/usr/local/bin";

wget $RESTIC_URL -O $TEMPDIR/restic.bz2
if [ ! -f $TEMPDIR/restic.bz2 ]; then
  echo "restic.bz2 Failed"
  exit1
fi

wget $RESTICS_URL -O $TEMPDIR/restic-server.tar.gz
if [ ! -f $TEMPDIR/restic-server.tar.gz ]; then
  echo "restic-server Failed to download"
  exit1
fi

cd $TEMPDIR && bzip2 -d restic.bz2
if [ ! -f $TEMPDIR/restic ]; then
  echo "restic Failed"
  exit1
fi
tar -xf $TEMPDIR/restic-server.tar.gz -C $TEMPDIR/

if [ ! -f $TEMPDIR/rest-server_${RESTSERV_VERSION}_linux_amd64/rest-server ]; then
  echo "$TEMPDIR/rest-server_${RESTSERV_VERSION}_linux_amd64/rest-server not found"
fi

cp $TEMPDIR/restic $DESTDIR/usr/local/bin/restic
cp "$TEMPDIR/rest-server_${RESTSERV_VERSION}_linux_amd64/rest-server" $DESTDIR/usr/local/bin/restic-server
chmod 0755 $DESTDIR/usr/local/bin/restic
chmod 0755 $DESTDIR/usr/local/bin/restic-server
echo "Compressing $DESTDIR"
cd $DESTDIR || exit 1
tar -czvf $TEMPDIR/restic-$RESTIC_VERSION.tar.gz *
echo "$TEMPDIR/restic-$RESTIC_VERSION.tar.gz done"
