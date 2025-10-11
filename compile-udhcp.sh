#!/bin/bash

cd /root

if [ -d /root/udhcp ]; then
  echo "Removing /root/udhcp ..."
  rm -rf /root/udhcp
fi
echo "Cloning in /root/udhcp ..."
git clone https://git.busybox.net/udhcp /root/udhcp

if [ ! -d /root/udhcp ]; then
  echo "Cloning in /root/udhcp failed..."
  exit 1
fi

cd /root/udhcp

echo "Compiling in /root/udhcp ..."

if [ ! -f /root/udhcp/Makefile ]; then
  echo "Cloning in /root/udhcp failed /root/udhcp/Makefile not found..."
  exit 1
fi

make && make install

if [ -d /root/udhcpd-maker ]; then
  echo "Removing /root/udhcpd-maker ..."
  rm -rf /root/udhcpd-maker
fi

echo "Creating  /root/udhcpd-maker directory..."
mkdir -p /root/udhcpd-maker/usr/sbin
mkdir -p /root/udhcpd-maker/usr/bin
mkdir -p /root/udhcpd-maker/usr/share/udhcpc

echo "Cleaning..."
strip -s  /usr/sbin/udhcpd
strip -s  /usr/bin/dumpleases
strip -s  /usr/sbin/udhcpc

echo "Duplicate..."
cp -fd /usr/sbin/udhcpd /root/udhcpd-maker/usr/sbin/
cp -fd /usr/sbin/udhcpc /root/udhcpd-maker/usr/sbin/
cp -fd /usr/bin/dumpleases /root/udhcpd-maker/usr/bin/
cp -rfd /usr/share/udhcpc/* /root/udhcpd-maker/usr/share/udhcpc/

echo "Compressing..."
cd /root/udhcpd-maker
tar -czvf /root/udhcpd-compiled.tar.gz *
echo "/root/udhcpd-compiled.tar.gz done"
