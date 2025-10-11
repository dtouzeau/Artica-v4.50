#!/bin/sh

mkdir -p /root/glance-compile/usr/local/lib/python2.7/dist-packages/Glances-3.3.0.4-py2.7.egg
mkdir -p /root/glance-compile/usr/local/lib/python2.7/dist-packages/defusedxml-0.7.1-py2.7.egg
mkdir -p /root/glance-compile/usr/local/lib/python2.7/dist-packages/psutil-5.9.4-py2.7-linux-x86_64.egg
mkdir -p /root/glance-compile/usr/local/lib/python2.7/dist-packages/future-0.18.2-py2.7.egg
mkdir -p /root/glance-compile/usr/bin

cp -rfv /usr/local/lib/python2.7/dist-packages/Glances-3.3.0.4-py2.7.egg/* /root/glance-compile/usr/local/lib/python2.7/dist-packages/Glances-3.3.0.4-py2.7.egg/

cp -rfv /usr/local/lib/python2.7/dist-packages/defusedxml-0.7.1-py2.7.egg/*  /root/glance-compile/usr/local/lib/python2.7/dist-packages/defusedxml-0.7.1-py2.7.egg/

cp -rfv /usr/local/lib/python2.7/dist-packages/psutil-5.9.4-py2.7-linux-x86_64.egg/*  /root/glance-compile/usr/local/lib/python2.7/dist-packages/psutil-5.9.4-py2.7-linux-x86_64.egg/

cp -rfv /usr/local/lib/python2.7/dist-packages/future-0.18.2-py2.7.egg/* /root/glance-compile/usr/local/lib/python2.7/dist-packages/future-0.18.2-py2.7.egg/

cp /usr/local/bin/glances /root/glance-compile/usr/bin/glances


# /usr/local/lib/python2.7/dist-packages/easy-install.pth