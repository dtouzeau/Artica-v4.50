#!/bin/bash

# https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation#linux
#dpkg -x package.deb /tmp/out

wget https://bin.equinox.io/c/VdrWdbjqyF/cloudflared-stable-linux-amd64.tgz -O /root/cloudflared-stable-linux-amd64.tgz

tar xf  /root/cloudflared-stable-linux-amd64.tgz -C /usr/share/artica-postfix/bin/
chmod 0755 /usr/share/artica-postfix/bin/cloudflared
