#!/usr/bin/env sh

WORKDIR="/root/cloudflared"
rm -f $WORKDIR
rm -rf $WORKDIR
TARGETBIN=$WORKDIR/usr/sbin/cloudflared
mkdir -p $WORKDIR/usr/sbin
wget  https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 -O $TARGETBIN

if [ ! -f $TARGETBIN ]; then
  echo "$TARGETBIN no such file"
  exit 1
fi
strip -s $TARGETBIN
chmod 0755 $TARGETBIN
output=$($TARGETBIN --version)
version=$(echo "$output" | sed -n 's/cloudflared version \([0-9]*\.[0-9]*\.[0-9]*\).*/\1/p')

echo "Going to $WORKDIR"
cd $WORKDIR
TARGET="/root/cloudflared-$version.tar.gz"
rm -f $TARGET
echo "tar -czvf $TARGET *"
tar -czvf $TARGET *
echo $TARGET done
