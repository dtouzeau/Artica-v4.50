#!/bin/sh
# vf version in https://assets.nagios.com/downloads/ncpa3/
#URL="https://assets.nagios.com/downloads/ncpa/ncpa-latest.d10.amd64.deb"

# https://assets.nagios.com/downloads/ncpa3/ncpa-latest-1.amd64.deb
# https://assets.nagios.com/downloads/ncpa3/ncpa-3.1.3-1.amd64.deb

VERSION="3.1.3-1"

current_user=$USER
NAME="ncpa-$VERSION.amd64.deb"
srPATH="/root/nagios/$VERSION"
WORKDIR="$srPATH/nagios-client"
EXTRACTED_WORK="$WORKDIR/extracted"
DATA_XZ="$EXTRACTED_WORK/data.tar.xz"
COMPILE_DIR="$WORKDIR/compile"
URL="https://assets.nagios.com/downloads/ncpa3/$NAME"

echo "PATH........: $srPATH"
echo "USER........: $current_user"
mkdir -p $srPATH
if [ ! -d $srPATH ]; then
  echo "$srPATH no such directory"
  exit 1
fi

if [ -d "$WORKDIR" ]; then
  echo "Removing $WORKDIR"
  /bin/rm -rf "$WORKDIR"
fi
echo "Creating $EXTRACTED_WORK"
/bin/mkdir -p $EXTRACTED_WORK
/bin/mkdir -p $COMPILE_DIR

if [ ! -d $EXTRACTED_WORK ];then
  echo "$EXTRACTED_WORK permission denied!"
  exit 1
fi

echo "Downloading $URL"
wget $URL -O "$EXTRACTED_WORK/$NAME"

if [ ! -f "$EXTRACTED_WORK/$NAME" ]; then
  echo "$EXTRACTED_WORK/$NAME no such file"
  exit 1
fi

echo "Extracting $EXTRACTED_WORK/$NAME to $EXTRACTED_WORK"
cd $EXTRACTED_WORK || true
ar -x "$EXTRACTED_WORK/$NAME"

rm $EXTRACTED_WORK/$NAME
rm -f $EXTRACTED_WORK/debian-binary



if [ ! -f "$DATA_XZ" ]; then
  echo "$DATA_XZ no such file"
  exit 1
fi
echo "Extracting $DATA_XZ to $COMPILE_DIR"
TARCMD="tar -xJf $DATA_XZ -C $COMPILE_DIR/"
echo "$TARCMD"
$TARCMD



FINAL="$WORKDIR/nagios-client-$VERSION.tar.gz"

echo "Compiling $FINAL"
cd "$COMPILE_DIR" || exit
tar -czvf $FINAL *
rm -rf $EXTRACTED_WORK



