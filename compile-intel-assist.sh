#!/bin/sh
# apt-get install libboost-regex1.67-dev yasm
# see https://www.intel.com/content/www/us/en/download/765501/intel-quickassist-technology-driver-for-linux-hw-version-2-0.html

# wget https://downloadmirror.intel.com/783270/QAT20.L.1.0.50-00003.tar.gz
# mkdir QAT20 && tar -xf QAT20.L.1.0.50-00003.tar.gz -C QAT20/

TARGET_DIR="/root/intel-quickassist-builder"
rm -rf TARGET_DIR
VERSION="1.0.50"
mkdir -p $TARGET_DIR/usr/lib/modules/4.19.0-25-amd64/updates/drivers/crypto/qat
mkdir -p $TARGET_DIR/usr/lib/modules/4.19.0-24-amd64/updates/drivers/crypto/qat
mkdir -p $TARGET_DIR/usr/lib/modules/4.19.0-26-amd64/updates/drivers/crypto/qat

mkdir -p $TARGET_DIR/usr/lib/modules/4.19.0-24-amd64/kernel/drivers/crypto/qat/
mkdir -p $TARGET_DIR/usr/lib/modules/4.19.0-25-amd64/kernel/drivers/crypto/qat/
mkdir -p $TARGET_DIR/usr/lib/modules/4.19.0-26-amd64/kernel/drivers/crypto/qat/
mkdir -p $TARGET_DIR/usr/local/lib
mkdir -p $TARGET_DIR/usr/local/bin
mkdir -p $TARGET_DIR/etc/systemd/system/timers.target.wants
mkdir -p $TARGET_DIR/lib/systemd/system
mkdir -p $TARGET_DIR/lib/firmware
strip -s /usr/local/bin/adf_ctl

cp /lib/firmware/qat_4xxx.bin $TARGET_DIR/lib/firmware/
cp /lib/firmware/qat_4xxx_mmp.bin $TARGET_DIR/lib/firmware/
cp /lib/firmware/qat_fw_backup $TARGET_DIR/lib/firmware/
cp /usr/local/lib/libqat_s.so to $TARGET_DIR/usr/local/lib/
cp /usr/local/lib/libusdm_drv_s.so to $TARGET_DIR/usr/local/lib
cp /etc/systemd/system/timers.target.wants/qat.timer $TARGET_DIR/etc/systemd/system/timers.target.wants/
cp /lib/systemd/system/qat.timer $TARGET_DIR/lib/systemd/system/
cp -rvf /usr/lib/modules/4.19.0-24-amd64/updates/drivers/crypto/qat/* $TARGET_DIR/usr/lib/modules/4.19.0-24-amd64/updates/drivers/crypto/qat/
cp -rvf /usr/lib/modules/4.19.0-25-amd64/updates/drivers/crypto/qat/* $TARGET_DIR/usr/lib/modules/4.19.0-25-amd64/updates/drivers/crypto/qat/
cp -rvf /usr/lib/modules/4.19.0-26-amd64/updates/drivers/crypto/qat/* $TARGET_DIR/usr/lib/modules/4.19.0-26-amd64/updates/drivers/crypto/qat/

cp -rvf /usr/lib/modules/4.19.0-24-amd64/kernel/drivers/crypto/qat/* $TARGET_DIR/usr/lib/modules/4.19.0-24-amd64/kernel/drivers/crypto/qat/
cp -rvf /usr/lib/modules/4.19.0-25-amd64/kernel/drivers/crypto/qat/* $TARGET_DIR/usr/lib/modules/4.19.0-25-amd64/kernel/drivers/crypto/qat/
cp -rvf /usr/lib/modules/4.19.0-26-amd64/kernel/drivers/crypto/qat/* $TARGET_DIR/usr/lib/modules/4.19.0-26-amd64/kernel/drivers/crypto/qat/

cp  /usr/lib/modules/4.19.0-25-amd64/kernel/drivers/usdm_drv.ko $TARGET_DIR/usr/lib/modules/4.19.0-25-amd64/kernel/drivers/
cp  /usr/lib/modules/4.19.0-26-amd64/kernel/drivers/usdm_drv.ko $TARGET_DIR/usr/lib/modules/4.19.0-26-amd64/kernel/drivers/

cp /usr/local/bin/adf_ctl $TARGET_DIR/usr/local/bin/
cd $TARGET_DIR && tar -czvf /root/intel-quickassist.$VERSION.tar.gz *
echo "/root/intel-quickassist.$VERSION.tar.gz * done"
