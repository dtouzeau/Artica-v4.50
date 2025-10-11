#!/bin/bash

#Download https://www.splunk.com/en_us/download/previous-releases/universalforwarder.html#tabs/linux
#tar xf package.tar.gz -C /opt

GETVER=`cat /opt/splunkforwarder/etc/splunk.version |grep VERSION=`
#A=$(awk -F= '{print $2}' <<< '$GETVER')
A="$(cut -d'=' -f2 <<<"$GETVER")"
mkdir -p /root/splunk-uf-builder/opt
mkdir -p /root/splunk-uf-builder/opt/splunkforwarder
cp -r /opt/splunkforwarder/*  /root/splunk-uf-builder/opt/splunkforwarder/
rm /root/splunk-uf-builder/opt/splunkforwarder/etc/apps/SplunkUniversalForwarder/default/inputs.conf
cd /root/splunk-uf-builder/
tar -czvf /root/splunkforwarder-$A.tar.gz *
echo "/root/splunkforwarder-$A.tar.gz done"
cd /root
