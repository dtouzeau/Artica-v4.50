#!/bin/sh
rm -rf /root/python-debian8 || true
rm -f /root/python-debian8.tar.gz || true
mkdir -p /root/python-debian8/usr/local/lib/python2.7/dist-packages/curl
mkdir -p /root/python-debian8/usr/local/lib/python2.7/dist-packages/psycopg2
cp -fd /usr/local/lib/python2.7/dist-packages/pycurl.so /root/python-debian8/usr/local/lib/python2.7/dist-packages/
cp -fd /usr/local/lib/python2.7/dist-packages/pycurl-7.43.0.egg-info /root/python-debian8/usr/local/lib/python2.7/dist-packages/
cp -rfd /usr/local/lib/python2.7/dist-packages/curl/* /root/python-debian8/usr/local/lib/python2.7/dist-packages/curl/
cp -rfd /usr/local/lib/python2.7/dist-packages/psycopg2/* /root/python-debian8/usr/local/lib/python2.7/dist-packages/psycopg2/
cd /root/python-debian8
tar -czf /root/python-debian8.tar.gz *
