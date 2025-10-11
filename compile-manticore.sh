#!/bin/bash

current_user=$(whoami)
VERSION_CODENAME="buster"
ARCH="amd64"
HTTP_PREFIX="https://repo.manticoresearch.com/repository"
URIPART="$HTTP_PREFIX/manticoresearch_$VERSION_CODENAME/dists/$VERSION_CODENAME/main/binary-$ARCH"
MAIN_VERSION="6.2.0-230804-45680f95d"
BUDDY_VERSION="1.0.18-23080408-2befdbe"
LIBS_VERSION="2.2.0-230804-dc33868"
BACKUP_VERSION="1.0.8-23080408-f7638f9"
EXECUTOR_VERSION="0.7.6-23080410-8f5cfa5"
FINDIR="/home/$current_user/manticore"
WORKDIR="$FINDIR/work"
COMPILE_DIR="$FINDIR/compile"


AllUrls=()

AllUrls+=("$URIPART/manticore-columnar-lib_${LIBS_VERSION}_${ARCH}.deb")
AllUrls+=("$URIPART/manticore-executor_${EXECUTOR_VERSION}_${ARCH}.deb")

if [  -d $COMPILE_DIR ]; then
  rm -rf  $COMPILE_DIR
fi


mkdir -p $WORKDIR
mkdir -p $COMPILE_DIR

main_versions_array=("manticore-tools_" "manticore_" "manticore-server-core_" "manticore-server_")
main_commons=("manticore-common_${MAIN_VERSION}_all" "manticore-dev_${MAIN_VERSION}_all")
buddies=("manticore-backup_${BACKUP_VERSION}_all.deb" "manticore-buddy_${BUDDY_VERSION}_all.deb")
RemoveDirs=("usr/share/doc" "usr/lib/systemd" "usr/share/man" "etc/default" "etc/init.d")

for element in "${main_versions_array[@]}"; do
    zURL="$URIPART/$element${MAIN_VERSION}_${ARCH}.deb"
    AllUrls+=($zURL)
done

for element in "${main_commons[@]}"; do
    zURL="$URIPART/$element.deb"
    AllUrls+=($zURL)
done

for element in "${buddies[@]}"; do
    zURL="$URIPART/$element"
    AllUrls+=($zURL)
done

for url in "${AllUrls[@]}"; do
    fname="${url##*/}"
    echo "Downloading $fname"
    if [ -f "$WORKDIR/$fname" ]; then
      rm -f "$WORKDIR/$fname"
    fi

    wget "$url" -O "$WORKDIR/$fname"

    # Check the exit status of wget
    if [ $? -eq 1 ]; then
      echo "Downloading $fname Failed"
      exit 1
    fi
    TEMPDIR="$WORKDIR/$fname-dir"
    if [ -d $TEMPDIR ]; then
      rm -rf $TEMPDIR
    fi

    if [ ! -d $TEMPDIR ]; then
      mkdir -p $TEMPDIR
    fi

    echo "Extracting to $TEMPDIR"
    /usr/bin/ar -x "$WORKDIR/$fname" --output=$TEMPDIR
    rm -f $WORKDIR/$fname
    DATA_XZ="$TEMPDIR/data.tar.xz"
    TARCMD="tar -xJf $DATA_XZ -C $COMPILE_DIR/"

    if [ ! -f $DATA_XZ ]; then
        DATA_XZ="$TEMPDIR/data.tar.gz"
         if [ ! -f $DATA_XZ ]; then
           echo "$DATA_XZ no such file, aborting"
           exit 1
         fi
        TARCMD="tar -xf $DATA_XZ -C $COMPILE_DIR/"
    fi
    echo "$TARCMD"
    $TARCMD
    echo "Removinf directory $TEMPDIR"
    rm -rf $TEMPDIR
done

for Directory in "${RemoveDirs[@]}"; do
  TDIR="$COMPILE_DIR/$Directory"
  if [  -d $TDIR ]; then
        echo "Removing directory $TDIR"
        rm -rf $TDIR
  fi
done

  cd $COMPILE_DIR
  target_tgz="$FINDIR/manticore-$MAIN_VERSION.tar.gz"
   if [  -f $target_tgz ]; then
     rm -f $target_tgz
  fi
  echo "Compressing final package $target_tgz"
  tar -czf $target_tgz *