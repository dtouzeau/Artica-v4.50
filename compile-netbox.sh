#!/bin/sh

# cd /opt && git clone -b master https://github.com/netbox-community/netbox.git
# python3 -m venv /opt/netbox/venv
# source /opt/netbox/venv/bin/activate
# cd /opt/netbox
# pip install -r requirements.txt
VERSION="4.0.9"
rm -rf /root/netbox
mkdir -p /root/netbox/opt/netbox

rm /opt/netbox/NOTICE
rm /opt/netbox/LICENSE.txt
rm /opt/netbox/SECURITY.md
rm /opt/netbox/README.md
rm -rf /opt/netbox/docs

cp -rf /opt/netbox/* /root/netbox/opt/netbox/
cd /root/netbox
tar -czf /root/netbox-$VERSION.tar.gz *
