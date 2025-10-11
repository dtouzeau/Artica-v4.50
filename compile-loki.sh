#!/usr/bin/env bash

PACKAGE_VERSION="1.1"
BaseURL="https://github.com/grafana/loki/releases/download"
VERSION="3.3.2"
GrafanaVersion="11.4.0"
FluentVersion="3.2.3"
MimirVersion="2.14.3"
SRCDIR="/root/loki-$VERSION"
INSTALLBIN="$SRCDIR/loki-linux-amd64"
GITGRAF="https://github.com/grafana"
COMPILEDIR="/root/loki-compile-$VERSION"
GraphanaDST="$COMPILEDIR/usr/local/grafana"
GraphanaBaseURL="https://dl.grafana.com/oss/release/grafana-$GrafanaVersion.linux-amd64.tar.gz"
# https://packages.fluentbit.io/debian/bookworm/
GrafanaAgentURL="$GITGRAF/agent/releases/download/v$GrafanaAgentVersion/grafana-agent-linux-amd64.zip"
FluentDebian12URI="https://packages.fluentbit.io/debian/bookworm/"
FlutentDebian1URI="https://packages.fluentbit.io/debian/bullseye/"
prometheusVersion="3.0.1"
prometheusURI="https://github.com/prometheus/prometheus/releases/download/v3.0.1/prometheus-$prometheusVersion.linux-amd64.tar.gz"
PROMODESTDIR="$SRCDIR/prometheus-$prometheusVersion.linux-amd64"
COMPILEDIRBIN="$COMPILEDIR/usr/local/bin"

MIMIR_URL="$GITGRAF/mimir/releases/download/mimir-$MimirVersion/mimir-linux-amd64"

rm -rf $SRCDIR || true
rm -rf $COMPILEDIR || true
mkdir -p $SRCDIR
mkdir -p "$COMPILEDIRBIN"
mkdir -p "$COMPILEDIR/opt/artica"
mkdir -p "$GraphanaDST"
wget -q $BaseURL/v$VERSION/loki-linux-amd64.zip -O $SRCDIR/loki-linux-amd64.zip
if [ ! -f $SRCDIR/loki-linux-amd64.zip ]; then
  exit 1
fi

echo $PACKAGE_VERSION > "$COMPILEDIR/opt/artica/.varticagrafana"

cd "$SRCDIR" || exit
unzip loki-linux-amd64.zip

if [ ! -f $INSTALLBIN ]; then
  echo "$INSTALLBIN no such file"
  exit 1
fi

echo "Installing $INSTALLBIN --> $COMPILEDIRBIN/"
cp $INSTALLBIN $COMPILEDIR/usr/local/bin/loki-server
echo "Removing $INSTALLBIN, loki-linux-amd64.zip"
rm -f $INSTALLBIN || true
rm -f " $SRCDIR/loki-linux-amd64.zip" || true

echo "Downloading prometheus v$prometheusVersion"
wget -q $prometheusURI -O $SRCDIR/prometheus-$prometheusVersion.linux-amd64.tar.gz

if [ ! -f "$SRCDIR/prometheus-$prometheusVersion.linux-amd64.tar.gz" ]; then
  echo "$SRCDIR/prometheus-$prometheusVersion.linux-amd64.tar.gz no such file"
  exit 1
fi

echo "Extracting prometheus $SRCDIR/prometheus-$prometheusVersion.linux-amd64.tar.gz"
tar -xf $SRCDIR/prometheus-$prometheusVersion.linux-amd64.tar.gz -C $SRCDIR

if [ ! -d "$PROMODESTDIR" ]; then
  echo "$PROMODESTDIR no such directory"
  exit 1
fi
if [ ! -f "$PROMODESTDIR/prometheus" ]; then
  echo "$PROMODESTDIR/prometheus no such file"
  exit 1
fi
if [ ! -f "$PROMODESTDIR/promtool" ]; then
  echo "$PROMODESTDIR/promtool no such file"
  exit 1
fi
mv $PROMODESTDIR/prometheus $COMPILEDIRBIN/
mv $PROMODESTDIR/promtool $COMPILEDIRBIN/
echo "preparing prometheus v$prometheusVersion done..(next)"


echo "Downloading $GraphanaBaseURL"
wget -q $GraphanaBaseURL -O $SRCDIR/grafana.tar.gz
if [ ! -f $SRCDIR/grafana.tar.gz ]; then
  echo "$SRCDIR/grafana.tar.gz no such file"
  exit 1
fi
echo "Extracting $SRCDIR/grafana.tar.gz"
cd $SRCDIR || exit 1
tar -xf grafana.tar.gz -C $SRCDIR/

if [ ! -d "$SRCDIR/grafana-v$GrafanaVersion" ]; then
  echo "$SRCDIR/grafana-v$GrafanaVersion no such directory"
  exit 1
fi
echo "Installing $SRCDIR/grafana-v$GrafanaVersion -> $GraphanaDST"
cp -rf $SRCDIR/grafana-v$GrafanaVersion/* $GraphanaDST/


echo "Downloading FluentBIT Agent version $FluentVersion for Debian 12"
wget -q $FluentDebian12URI/fluent-bit_${FluentVersion}_amd64.deb -O $SRCDIR/fluentdeb12.deb

if [ ! -f "$SRCDIR/fluentdeb12.deb" ]; then
  echo "$SRCDIR/fluentdeb12.deb no such file"
  exit 1
fi
echo "Extracting fluent package from $SRCDIR"
cd "$SRCDIR" || exit
ar -x fluentdeb12.deb
if [ ! -f "$SRCDIR/data.tar.gz" ]; then
  echo "$SRCDIR/data.tar.gz no such file"
  exit 1
fi
rm -f $SRCDIR/control.tar.gz
rm -f $SRCDIR/debian-binary

echo "installing fluent package to $COMPILEDIR/usr/local/debian12"
mkdir -p $COMPILEDIR/usr/local/debian12
tar -xf $SRCDIR/data.tar.gz -C $COMPILEDIR/usr/local/debian12/
rm -f $SRCDIR/data.tar.gz

echo "Downloading FluentBIT Agent version $FluentVersion for Debian 10"
wget -q $FlutentDebian1URI/fluent-bit_${FluentVersion}_amd64.deb -O $SRCDIR/fluentdeb10.deb

if [ ! -f "$SRCDIR/fluentdeb10.deb" ]; then
  echo "$SRCDIR/fluentdeb10.deb no such file"
  exit 1
fi

echo "Extracting fluent package from $SRCDIR"
cd "$SRCDIR" || exit
ar -x fluentdeb10.deb
if [ ! -f "$SRCDIR/data.tar.gz" ]; then
  echo "$SRCDIR/data.tar.gz no such file"
  exit 1
fi
rm -f $SRCDIR/control.tar.gz
rm -f $SRCDIR/debian-binary

echo "installing fluent package to $COMPILEDIR/usr/local/debian10"
mkdir -p $COMPILEDIR/usr/local/debian10
tar -xf $SRCDIR/data.tar.gz -C $COMPILEDIR/usr/local/debian10/
rm -f $SRCDIR/data.tar.gz

echo "Downloading mimir v$MimirVersion"
wget $MIMIR_URL -O "$COMPILEDIR/usr/local/bin/mimir"

echo "Cleaning.."
rm -rf "$GraphanaDST/doc"
rm -f "$GraphanaDST/NOTICE.md"
rm -f "$GraphanaDST/Dockerfile"
rm -rf "$GraphanaDST/packaging"
rm -f /root/monitoring-$VERSION.tar.gz
echo "Compressing /root/monitoring-$VERSION.tar.gz"
cd $COMPILEDIR || exit 1
tar -czf /root/monitoring-$VERSION.tar.gz *




