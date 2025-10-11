#!/bin/sh

# LD_LIBRARY_PATH="/usr/local/lib/ssl-compat:/lib:/lib64:/usr/lib:/usr/lib64" LDFLAGS="-L/usr/local/lib/ssl-compat -L/lib -L/usr/local/lib -L/usr/lib/libmilter -L/usr/lib -L/usr/lib64 -L/lib64" CPPFLAGS="-I/home/libressl/include/openssl -I/usr/include/ -I/usr/local/include -I/usr/include/libpng12 -I/usr/include/sasl -I/usr/include/libmilter -I/usr/include/linux -I/usr/include/sm/os"



rm -rf /root/python-package || true
rm -f /root/python.tar.gz || true
mkdir -p /root/python-package/usr/share/pyshared/curl
mkdir -p /root/python-package/usr/share/pyshared/psycopg2
mkdir -p /root/python-package/usr/lib/python2.7/dist-packages/psycopg2
mkdir -p /root/python-package/usr/local/lib/python2.7/dist-packages
mkdir -p /root/python-package/usr/share/pyshared/psycopg2
mkdir -p /root/python-package/usr/lib/python2.7/dist-packages/psycopg2/tests
mkdir -p /root/python-package/usr/lib/pyshared/python2.7
mkdir -p /root/python-package/usr/lib/pymodules/python2.7
mkdir -p /root/python-package/usr/local/bin
mkdir -p /root/python-package/usr/lib/pyshared/python2.7
mkdir -p /root/python-package/usr/share/pyshared/MySQL_python-1.2.3.egg-info
mkdir -p /root/python-package/usr/lib/python2.7/dist-packages/MySQLdb
mkdir -p /root/python-package/usr/share/pyshared/MySQLdb
mkdir -p /root/python-package/usr/lib/python2.7/dist-packages/MySQL_python-1.2.3.egg-info
mkdir -p /root/python-package/usr/local/lib/python2.7/dist-packages/pyOpenSSL-16.2.0-py2.7.egg
mkdir -p /root/python-package/usr/local/lib
mkdir -p /root/python-package/usr/lib
mkdir -p /root/python-package/usr/include
mkdir -p /root/python-package/usr/lib/python2.7/dist-packages
mkdir -p /root/python-package/usr/lib/pymodules/python2.7
mkdir -p /root/python-package/usr/lib/pyshared/python2.7
mkdir -p /root/python-package/usr/local/bin
mkdir -p /root/python-package/usr/local/lib/python2.7/dist-packages/PyWebDAV-0.9.8-py2.7.egg/pywebdav/
mkdir -p /root/python-package/usr/local/bin

cp -fvd /usr/local/bin/flask /root/python-package/usr/local/bin/
cp -fvd /usr/local/bin/virtualenv /root/python-package/usr/local/bin/
cp -fvd /usr/lib/libtalloc.so.2.1.8 /root/python-package/usr/lib/
cp -fvd /usr/lib/libtalloc.so.2 /root/python-package/usr/lib/
cp -fvd /usr/lib/libtalloc.so /root/python-package/usr/lib/
cp -fvd /usr/lib/python2.7/dist-packages/talloc.so /root/python-package/usr/lib/python2.7/dist-packages/
cp -fvd /usr/include/talloc.h /root/python-package/usr/include/
cp -fvd /usr/include/pytalloc.h /root/python-package/usr/include/


cp -fvd /usr/local/lib/libffi.so.5.0.10 /root/python-package/usr/local/lib/
cp -fvd /usr/local/lib/libffi.so.5 /root/python-package/usr/local/lib/
cp -fvd /usr/local/lib/libffi.so /root/python-package/usr/local/lib/
cp -fvd /usr/local/lib/libffi.la /root/python-package/usr/local/lib/
cp -fvd /usr/local/lib/libffi.a /root/python-package/usr/local/lib/


echo "Copy tldextract"
cp -fvd /usr/local/bin/tldextract /root/python-package/usr/local/bin/


echo "Copy pycurl"
cp -fvd /usr/share/pyshared/pycurl-7.19.0.egg-info /root/python-package/usr/share/pyshared/
cp -fvd /usr/lib/pyshared/python2.7/pycurl.so /root/python-package/usr/lib/pyshared/python2.7/
cp -fvd /usr/lib/pyshared/python2.7/pycurl.so /root/python-package/usr/lib/pyshared/python2.7/
cp -fvd /usr/lib/pymodules/python2.7/pycurl-7.19.0.egg-info /root/python-package/usr/lib/pymodules/python2.7/
cp -fvd /usr/lib/pymodules/python2.7/pycurl.so /root/python-package/usr/lib/pymodules/python2.7/

echo "Copy MySQLdb"
cp -fd /usr/lib/pyshared/python2.7/_mysql.so /root/python-package/usr/lib/pyshared/python2.7/
cp -rfd /usr/share/pyshared/MySQL_python-1.2.3.egg-info/* /root/python-package/usr/share/pyshared/MySQL_python-1.2.3.egg-info/
cp -rfd /usr/share/pyshared/MySQLdb/* /root/python-package/usr/share/pyshared/MySQLdb/
cp -rfd /usr/lib/python2.7/dist-packages/MySQLdb/* /root/python-package/usr/lib/python2.7/dist-packages/MySQLdb/
cp -rfd /usr/lib/python2.7/dist-packages/MySQL_python-1.2.3.egg-info/* /root/python-package/usr/lib/python2.7/dist-packages/MySQL_python-1.2.3.egg-info/
cp -fd /usr/share/pyshared/_mysql_exceptions.py /root/python-package/usr/share/pyshared/
cp -fd /usr/lib/python2.7/dist-packages/_mysql.so /root/python-package/usr/lib/python2.7/dist-packages/
cp -fd /usr/lib/python2.7/dist-packages/_mysql_exceptions.py /root/python-package/usr/lib/python2.7/dist-packages/
cp -fd /usr/lib/pyshared/python2.7/_mysql.so /root/python-package/usr/lib/pyshared/python2.7/

echo "Copy psycopg2-2.4.5.egg-info"
cp -fd /usr/lib/python2.7/dist-packages/psycopg2-2.4.5.egg-info /root/python-package/usr/lib/python2.7/dist-packages/

echo "Copy psycopg2 directory"
cp -rfd /usr/share/pyshared/psycopg2/* /root/python-package/usr/share/pyshared/psycopg2/
cp -rfd /usr/lib/python2.7/dist-packages/psycopg2/* /root/python-package/usr/lib/python2.7/dist-packages/psycopg2/
cp -rfd /usr/local/lib/python2.7/dist-packages/* /root/python-package/usr/local/lib/python2.7/dist-packages/
cp -rfd /usr/share/pyshared/curl/* /root/python-package/usr/share/pyshared/curl/

echo "Copy pyOpenSSL-16.2.0-py2.7"
cp -rfd /usr/local/lib/python2.7/dist-packages/pyOpenSSL-16.2.0-py2.7.egg /root/python-package/usr/local/lib/python2.7/dist-packages/pyOpenSSL-16.2.0-py2.7.egg/

echo "Copy PyWebDav"
cp -rfd /usr/local/lib/python2.7/dist-packages/PyWebDAV-0.9.8-py2.7.egg /root/python-package/usr/local/lib/python2.7/dist-packages/PyWebDAV-0.9.8-py2.7.egg
cp -rfd /usr/local/bin/davserver /root/python-package/usr/local/bin/

echo "/root/python.tar.gz [COMPRESS]"
cd /root/python-package && tar czf /root/python.tar.gz *
cp -f /root/python.tar.gz /usr/share/artica-postfix/bin/install/python.tar.gz
echo "/root/python.tar.gz [DONE....]"
rm /usr/share/artica-postfix/bin/install/python.tar.gz
cp -f /root/python.tar.gz /usr/share/artica-postfix/bin/install/python.tar.gz


