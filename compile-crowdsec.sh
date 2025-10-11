#!/bin/bash


# Display releases here.
# https://github.com/crowdsecurity/crowdsec/releases
# Download the package and extract

# wget https://github.com/crowdsecurity/crowdsec/releases/download/v1.6.2/crowdsec-release.tgz
# tar -xf crowdsec-release.tgz
# Custom Bouncer - https://github.com/crowdsecurity/cs-custom-bouncer/releases
# https://github.com/crowdsecurity/cs-custom-bouncer/releases/download/v0.0.17-rc5/crowdsec-custom-bouncer-linux-amd64.tgz
# Bouncer = cs-firewall -> https://github.com/crowdsecurity/cs-firewall-bouncer/release
# Nginx Bouncer = https://github.com/crowdsecurity/cs-nginx-bouncer/releases

CROWDSECVERSION="1.6.6"
CROWDPREF="https://github.com/crowdsecurity"
CURRENT_USER=$USER
SRC_DIR="$HOME/Téléchargements/crowdsec-v$CROWDSECVERSION"
GLOBAL_DIR="$HOME/crowdsec-compile"
CROWDSEC_PLUGIN_DIR="/usr/local/lib/crowdsec/plugins"
CROWDSEC_PATH="/etc/crowdsec"
BOUNCER_RELEASE="0.0.31"
CUSTOM_BOUNCER_RELEASE="0.0.17"
NGINX_BOUNCER_RELEASE="1.1.0"
BOUNCER_DIR="$SRC_DIR/bouncer"
BOUNCERS_CONFIG_DIR="$CROWDSEC_PATH/bouncers"
CUSTOM_BOUNCER_TEMP_FILE="/tmp/crowdsec-custom-bouncer-linux-amd64.tgz"
CUSTOM_BOUNCER_DIR="$SRC_DIR/crowdsec-custom-bouncer"
CROWDSECTEMP="$GLOBAL_DIR/$CROWDSECVERSION"
LUADIR="$GLOBAL_DIR/usr/local/lua/crowdsec"
LUA_DATA_PATH="$GLOBAL_DIR/var/lib/crowdsec/lua"




if [ -d $CUSTOM_BOUNCER_DIR ]; then
  echo "Removing $CUSTOM_BOUNCER_DIR"
  rm -rf $CUSTOM_BOUNCER_DIR
fi

if [ -d $GLOBAL_DIR ]; then
  echo "Removing $GLOBAL_DIR"
  rm -rf $GLOBAL_DIR
fi

if [ -d SRC_DIR ]; then
  echo "Removing $SRC_DIR"
  mkdir -p $SRC_DIR
fi

mkdir -p $BOUNCER_DIR
mkdir -p $CUSTOM_BOUNCER_DIR
mkdir -p "$GLOBAL_DIR/usr/local/bin"
mkdir -p "$GLOBAL_DIR/usr/local/sbin"
mkdir -p "$GLOBAL_DIR$CROWDSEC_PLUGIN_DIR"
mkdir -p "$GLOBAL_DIR$CROWDSEC_PATH/hub"
mkdir -p "$GLOBAL_DIR$BOUNCERS_CONFIG_DIR"
mkdir -p "$GLOBAL_DIR$BOUNCERS_CONFIG_DIR"
mkdir -p "$GLOBAL_DIR/etc/crowdsec/notifications/http"
mkdir -p $LUA_DATA_PATH
mkdir -p "$CROWDSECTEMP"
mkdir -p $LUADIR

EXTRACTDIR="$CROWDSECTEMP/crowdsec-v$CROWDSECVERSION"

PLUGINS=("http" "file" "splunk" "email" "slack" "sentinel" "dummy")

for element in "${PLUGINS[@]}"; do
    mkdir -p "$GLOBAL_DIR/etc/crowdsec/notifications/$element"
done

echo "Downloading crowdsec-nginx-bouncer v$NGINX_BOUNCER_RELEASE inside $CROWDSECTEMP"

wget -q $CROWDPREF/cs-nginx-bouncer/releases/download/v1.1.0/crowdsec-nginx-bouncer.tgz -O $CROWDSECTEMP/crowdsec-nginx-bouncer.tgz

if [ ! -f $CROWDSECTEMP/crowdsec-nginx-bouncer.tgz ]; then
  echo "$CROWDSECTEMP/crowdsec-nginx-bouncer.tgz no such file"
  exit 1
fi

mkdir -p $CROWDSECTEMP/nginx
echo "Extracting $CROWDSECTEMP/crowdsec-nginx-bouncer.tgz to $CROWDSECTEMP/nginx"
tar -xf $CROWDSECTEMP/crowdsec-nginx-bouncer.tgz -C $CROWDSECTEMP/nginx/

last_folder=$(find $CROWDSECTEMP/nginx/ -maxdepth 1 | tail -n 1 | xargs basename)
NGINX_LASTF="$CROWDSECTEMP/nginx/$last_folder"
echo "Scanning the folder $NGINX_LASTF"

if [ ! -d "$NGINX_LASTF/lua-mod" ]; then
  echo "$NGINX_LASTF/lua-mod no such directory"
  exit 1
fi
echo "Copy the folder $NGINX_LASTF/lua-mod/lib to $LUADIR"
mkdir -p "$LUADIR/plugins/crowdsec"
cp -rf $NGINX_LASTF/lua-mod/lib/* $LUADIR/
echo "Copy the folder $NGINX_LASTF/lua-mod/templates/* to $LUA_DATA_PATH/"
cp -rf $NGINX_LASTF/lua-mod/templates/* $LUA_DATA_PATH/

echo "Downloading CROWDSEC v$CROWDSECVERSION"
wget -q $CROWDPREF/crowdsec/releases/download/v$CROWDSECVERSION/crowdsec-release.tgz -O $CROWDSECTEMP/crowdsec-release.tgz

if [ ! -f $CROWDSECTEMP/crowdsec-release.tgz ]; then
  echo "$CROWDSECTEMP/crowdsec-release.tgz no such file"
  exit 1
fi

echo "Extracting $CROWDSECTEMP/crowdsec-release.tgz"
cd $CROWDSECTEMP || exit 1
tar -xf crowdsec-release.tgz
rm -f crowdsec-release.tgz

if [ ! -d $EXTRACTDIR ]; then
  echo "$EXTRACTDIR no such directory"
  exit 1
fi

if [ ! -d $EXTRACTDIR/config ]; then
  echo "$EXTRACTDIR/config no such directory"
  exit 1
fi

cp -rfv $EXTRACTDIR/config/* $GLOBAL_DIR/$CROWDSEC_PATH/
rm -rf $EXTRACTDIR/config

for element in "${PLUGINS[@]}"; do
    if [ ! -f $EXTRACTDIR/cmd/notification-$element/notification-$element ]; then
      echo "$EXTRACTDIR/cmd/notification-$element/notification-$element no such binary"
      exit 1
    fi
    echo "Copy $element notification plugin"
    cp -rf $EXTRACTDIR/cmd/notification-$element/* $GLOBAL_DIR/etc/crowdsec/notifications/$element/
    rm -rf  $EXTRACTDIR/cmd/notification-$element
done

if [ ! -f $EXTRACTDIR/cmd/crowdsec-cli/cscli ]; then
  echo "$EXTRACTDIR/cmd/crowdsec-cli/cscli no such binary"
  exit 1
fi
if [ ! -f $EXTRACTDIR/cmd/crowdsec/crowdsec ]; then
  echo "$EXTRACTDIR/cmd/crowdsec/crowdsec no such binary"
  exit 1
fi

cp -f $EXTRACTDIR/cmd/crowdsec-cli/cscli $GLOBAL_DIR/usr/local/sbin/
cp -f $EXTRACTDIR/cmd/crowdsec/crowdsec $GLOBAL_DIR/usr/local/bin/
rm -rf $EXTRACTDIR/cmd/crowdsec
rm -rf $EXTRACTDIR/cmd/crowdsec-cli
echo "Remove $EXTRACTDIR"
rm -rf $EXTRACTDIR
echo "Remove $CROWDSECTEMP"
rm -rf $CROWDSECTEMP




echo "Download in $GLOBAL_DIR$CROWDSEC_PATH/hub/.index.json"
wget -q https://raw.githubusercontent.com/crowdsecurity/hub/master/.index.json -O $GLOBAL_DIR$CROWDSEC_PATH/hub/.index.json


if [ -f /tmp/crowdsec-firewall-bouncer-linux-amd64.tgz ]; then
  rm -f /tmp/crowdsec-firewall-bouncer-linux-amd64.tgz
fi

if [ -f $CUSTOM_BOUNCER_TEMP_FILE ]; then
  rm -f $CUSTOM_BOUNCER_TEMP_FILE
fi

echo "Download cs-firewall-bouncer $BOUNCER_RELEASE"

wget -q $CROWDPREF/cs-firewall-bouncer/releases/download/v$BOUNCER_RELEASE/crowdsec-firewall-bouncer-linux-amd64.tgz -O /tmp/crowdsec-firewall-bouncer-linux-amd64.tgz

echo "Download cs-custom-bouncer $CUSTOM_BOUNCER_RELEASE"

wget -q $CROWDPREF/cs-custom-bouncer/releases/download/v$CUSTOM_BOUNCER_RELEASE/crowdsec-custom-bouncer-linux-amd64.tgz -O $CUSTOM_BOUNCER_TEMP_FILE

echo "tar -xf /tmp/crowdsec-firewall-bouncer-linux-amd64.tgz -C $BOUNCER_DIR/"
tar -xf /tmp/crowdsec-firewall-bouncer-linux-amd64.tgz -C $BOUNCER_DIR/

echo "tar -xf $CUSTOM_BOUNCER_TEMP_FILE -C $CUSTOM_BOUNCER_DIR/"
tar -xf $CUSTOM_BOUNCER_TEMP_FILE -C $CUSTOM_BOUNCER_DIR/


echo "Copy crowdsec-custom-bouncer.yaml"
if [ ! -d $CUSTOM_BOUNCER_DIR/crowdsec-custom-bouncer-v$CUSTOM_BOUNCER_RELEASE/config ]; then
  echo "$CUSTOM_BOUNCER_DIR/crowdsec-custom-bouncer-v$CUSTOM_BOUNCER_RELEASE/config no such directory"
  exit 0
fi

cp -f $CUSTOM_BOUNCER_DIR/crowdsec-custom-bouncer-v$CUSTOM_BOUNCER_RELEASE/config/crowdsec-custom-bouncer.yaml $GLOBAL_DIR/$BOUNCERS_CONFIG_DIR/

echo "Copy crowdsec-custom-bouncer"
cp -f $CUSTOM_BOUNCER_DIR/crowdsec-custom-bouncer-v$CUSTOM_BOUNCER_RELEASE/crowdsec-custom-bouncer $GLOBAL_DIR/usr/local/bin/


echo "Copy crowdsec-firewall-bouncer.yaml"
if [ ! -d $BOUNCER_DIR/crowdsec-firewall-bouncer-v$BOUNCER_RELEASE/config ]; then
  echo "$BOUNCER_DIR/crowdsec-firewall-bouncer-v$BOUNCER_RELEASE/config no such directory"
  exit 0
fi

cp -f $BOUNCER_DIR/crowdsec-firewall-bouncer-v$BOUNCER_RELEASE/config/crowdsec-firewall-bouncer.yaml $GLOBAL_DIR/$BOUNCERS_CONFIG_DIR/
echo "Copy crowdsec-firewall-bouncer"
cp -f $BOUNCER_DIR/crowdsec-firewall-bouncer-v$BOUNCER_RELEASE/crowdsec-firewall-bouncer $GLOBAL_DIR/usr/local/bin/


chmod 0755 $GLOBAL_DIR/usr/local/bin/*
chmod 0755 $GLOBAL_DIR/usr/local/sbin/*

if [ -f $GLOBAL_DIR/crowdsec.x.x.x.tar.gz ]; then
  rm -f $GLOBAL_DIR/crowdsec.x.x.x.tar.gz
fi
if [ -f $GLOBAL_DIR/crowdsec.$CROWDSECVERSION.tar.gz ]; then
  rm -f $GLOBAL_DIR/crowdsec.$CROWDSECVERSION.tar.gz
fi

echo "compressing $GLOBAL_DIR/crowdsec.$CROWDSECVERSION.tar.gz"
cd $GLOBAL_DIR
tar -czvf $GLOBAL_DIR/crowdsec.$CROWDSECVERSION.tar.gz *
chown -R $CURRENT_USER $GLOBAL_DIR
echo "$GLOBAL_DIR/crowdsec.$CROWDSECVERSION.tar.gz"
