#!/bin/bash
# https://mariadb.com/downloads/
current_user=$USER
VERSION="11.1.2"
NAME="mariadb-$VERSION-debian-buster-amd64-debs"
FILE="$NAME.tar"
srPATH="/home/dtouzeau/Téléchargements"
WORKDIR="$srPATH/mariadb"
EXTRACTED_DIR="$WORKDIR/$NAME"
EXTRACTED_WORK="$WORKDIR/extracted"
COMPILE_DIR="$WORKDIR/compile"

if [ $current_user == "root" ]; then
  echo "Root not allowed here"
  exit 1
fi

echo "PATH........: $srPATH"
echo "USER........: $current_user"

if [ ! -d $srPATH ]; then
  echo "$srPATH no such directory"
  exit 1
fi

if [ -d $WORKDIR ]; then
  echo "Removing $WORKDIR"
  /bin/rm -rf $WORKDIR
fi
echo "Creating $EXTRACTED_WORK"
/bin/mkdir -p $EXTRACTED_WORK
/bin/mkdir -p $COMPILE_DIR

if [ ! -d $EXTRACTED_WORK ];then
  echo "$EXTRACTED_WORK permission denied!"
  exit 1
fi

echo "Extracting $srPATH/$FILE"
/bin/tar xf $srPATH/$FILE -C $WORKDIR/

echo "/usr/bin/find $EXTRACTED_DIR -iname \"*.deb\""
array=( $(/usr/bin/find $EXTRACTED_DIR -iname "*.deb") )

for i in "${array[@]}"
do
    echo "/usr/bin/ar -x $i --output=$EXTRACTED_WORK"
    /usr/bin/ar -x $i --output=$EXTRACTED_WORK
    if [ ! -f "$EXTRACTED_WORK/data.tar.xz" ]; then
      echo "$EXTRACTED_WORK/data.tar.xz no such file!"
      /bin/rm $EXTRACTED_WORK/*
      continue
    fi
    echo "/bin/tar xjf $EXTRACTED_WORK/data.tar.xz -C $COMPILE_DIR/"
    /bin/tar xf $EXTRACTED_WORK/data.tar.xz -C $COMPILE_DIR/
    /bin/rm $EXTRACTED_WORK/*

done

cd $COMPILE_DIR
tar -czvf $srPATH/mariadb-$VERSION.tar.gz *
echo "$srPATH/mariadb-$VERSION.tar.gz done"

