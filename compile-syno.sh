#!/bin/sh



# in download/Debian10-activebackup

# https://archive.synology.com/download/Utility/ActiveBackupBusinessAgent/2.6.0-3032
# wget https://global.synologydownload.com/download/Utility/ActiveBackupBusinessAgent/2.6.1-3052/Linux/x86_64/Synology%20Active%20Backup%20for%20Business%20Agent-2.6.1-3052-x64-deb.zip

# unzip Synology\ Active\ Backup\ for\ Business\ Agent-2.6.0-3032-x64-deb.zip

# mkdir -p /home/syno/sinoextracted/lib/modules/4.19.0-25-amd64/updates/dkms
# mkdir -p /home/syno/sinoextracted/usr/lib/modules/4.19.0-25-amd64/extra
# mkdir -p /home/syno/sinoextracted/lib/modules/4.19.0-25-amd64/updates/dkms
# mkdir -p /home/syno/sinoextracted/usr/lib/modules/4.19.0-25-amd64/extra
# ./install.run --target /home/syno/
# cd  /home/syno/
# cp Synology Active Backup for Business Agent-2.5.1-2634.deb  sinoextracted/
# cd sinoextracted
# ar xv Synology\ Active\ Backup\ for\ Business\ Agent-2.5.1-2634.deb
# tar -xf data.tar.xz
# rm debian-binary control.tar.gz _gpgbuilder data.tar.xz
# rm Synology\ Active\ Backup\ for\ Business\ Agent-2.5.1-2634.deb
# rm postinst  postrm  preinst  prerm
# cd ..
# cp synosnap-0.10.19.deb sinoextracted/
# ar xv synosnap-0.10.19.deb
# tar -xf data.tar.xz
# rm debian-binary control.tar.gz _gpgbuilder data.tar.xz synosnap-0.10.19.deb

# cp /lib/modules/4.19.0-25-amd64/updates/dkms/synosnap.ko /home/syno/sinoextracted/lib/modules/4.19.0-25-amd64/updates/dkms/
# cp /lib/modules/4.19.0-25-amd64/updates/dkms/synosnap.ko /home/syno/sinoextracted/usr/lib/modules/4.19.0-25-amd64/extra/
# rm /home/syno/sinoextracted/etc/systemd/system/synology-active-backup-business-linux-service.service
# COmpiler pour nouveau noyeau
# cd /home/syno/sinoextracted/usr/src/synosnap-0.10.19
#  ./genconfig.sh
   #  make
   #  make install
WORKDIR="/home/syno/sinoextracted"
KERNEL=`uname -r`
VERSION="2.6.0-3032"
MODULE_PATH="/usr/lib/modules/$KERNEL/extra/synosnap.ko"
TARGET_PATH="$WORKDIR/usr/lib/modules/$KERNEL/extra"
TARBALL="/root/sino-$VERSION.tar.gz"

echo "Want to find $MODULE_PATH KERNEL: $KERNEL"
mkdir -p "$WORKDIR/lib/modules/$KERNEL/updates/dkms"
mkdir -p $TARGET_PATH

if [ ! -f $MODULE_PATH ]; then
  echo "$MODULE_PATH already not compiled"
  echo "DO cd $WORKDIR/usr/src/synosnap-0.10.19 && ./genconfig.sh && make && make install"
  exit 1
fi
cp -f $MODULE_PATH $WORKDIR/lib/modules/$KERNEL/updates/dkms/
cp -f $MODULE_PATH $TARGET_PATH/

cd $WORKDIR

if [ -f $TARBALL ]; then
  echo "removing $TARBALL"
  rm -f $TARBALL
fi
echo "Compressing $TARBALL"
tar czf $TARBALL *
echo "Compressing $TARBALL done"


