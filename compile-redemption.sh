#!/bin/bash

# https://github.com/wallix/redemption?tab=readme-ov-file

# git clone https://github.com/wallix/redemption.git
# apt-get install libhyperscan5 libpng-tools libavcodec-dev libavformat-dev libavutil-dev libswscale-dev libx264-dev

# cd redemption && bjam exe libs

WORKDIR="/root/redemption-compile"

files=(
    "/usr/local/bin/rdpinichecker"
    "/usr/local/lib/libredrec.so"
    "/usr/local/bin/rdpproxy"
    "/usr/local/bin/headlessclient"
    "/usr/local/share/rdpproxy/locale/en/LC_MESSAGES/redemption.mo"
    "/usr/local/share/rdpproxy/locale/fr/LC_MESSAGES/redemption.mo"
)

mkdir -p $WORKDIR/usr/local/lib
mkdir -p $WORKDIR/etc/rdpproxy
mkdir -p $WORKDIR/usr/local/bin
mkdir -p $WORKDIR/usr/local/share/rdpproxy
mkdir -p $WORKDIR/usr/local/share/rdpproxy/locale/fr/LC_MESSAGES

cp -rfd /usr/local/share/rdpproxy/* $WORKDIR/usr/local/share/rdpproxy/
cp -rfd /etc/rdpproxy $WORKDIR/etc/rdpproxy/

for file in "${files[@]}"; do
    echo "Copy $file to $WORKDIR$file"
    cp -fd "$file" "$WORKDIR$file"
done

version=$(/usr/local/bin/rdpproxy -v | grep -oP '\s+\d+\.\d+\.\d+\s+')
version=$(echo "$version" | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
targz="/root/rdpprody-debian12-$version.tar.gz"
echo "Compressing $targz"
cd $WORKDIR
tar -czf $targz *
echo "Done $targz"