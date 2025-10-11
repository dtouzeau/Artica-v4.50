#!/bin/sh

# OpenSSL
# cd /root && wget https://www.openssl.org/source/openssl-3.2.0.tar.gz
# cd openssl-3.2.0
# ./Configure no-shared --prefix=/usr --openssldir=/usr/lib/ssl --libdir=lib/x86_64-linux-gnu no-idea no-mdc2 no-rc5 no-zlib no-ssl3 enable-unit-test no-ssl3-method enable-rfc3779 enable-cms no-capieng enable-ec_nistp_64_gcc_128

# apt-get install apache2-dev libpcre3 libpcre3-dev libgeoip-dev
VERSION="1.19.3.2"
ROOTCOMPILE="/root/openresty-$VERSION"
# git clone https://github.com/SpiderLabs/ModSecurity.git /root/ModSecurity
# cd /root/ModSecurity
# git submodule init
# git submodule update
# ./build.sh
# ./configure --enable-standalone-module --disable-mlogcmake --disable-apache2-module --enable-pcre-study --with-lua --enable-pcre-jit
# make && make install
cd /root/openresty-1.19.3.2/bundle
wget http://mdounin.ru/hg/ngx_http_auth_request_module/archive/a29d74804ff1.tar.gz
tar -xhf a29d74804ff1.tar.gz
mv ngx_http_auth_request_module-a29d74804ff1 ngx_http_auth_request_module-1.0
git clone https://github.com/yaoweibin/ngx_http_substitutions_filter_module.git ngx_http_substitutions_filter_module-1.0
git clone https://github.com/kvspb/nginx-auth-ldap.git ngx_http_auth_ldap_module-1.0
git clone https://github.com/pagespeed/ngx_pagespeed.git ngx_pagespeed-1.0
git clone https://github.com/SpiderLabs/ModSecurity-nginx ngx_http_modsecurity-1.0
git clone https://github.com/leev/ngx_http_geoip2_module.git ngx_http_geoip2_module-1.0
git clone https://github.com/arut/nginx-dav-ext-module.git ngx_dav_ext_module-1.0
git clone https://github.com/aperezdc/ngx-fancyindex.git ngx_fancyindex_module-1.0
rm -rf /root/nginx-module-vts
git clone https://github.com/vozlt/nginx-module-vts.git /root/nginx-module-vts
rm -rf /root/nginx-module-sysguard
git clone https://github.com/vozlt/nginx-module-sysguard.git /root/nginx-module-sysguard


# Type vi $ROOTCOMPILE/configure"
# echo "Then add these entries inside the block my @modules = (... :"
[http_substitutions_filter_module=>'ngx_http_substitutions_filter_module'],
[http_auth_ldap_module=>'ngx_http_auth_ldap_module'], [http_geoip2_module=>'ngx_http_geoip2_module'],
[http_modsecurity=>'ngx_http_modsecurity'],[ngx_dav_ext_module=>'ngx_dav_ext_module'],[ngx_fancyindex_module=>'ngx_fancyindex_module'],

	# echo "[http_pagespeed=>'ngx_pagespeed']," need ttps://github.com/apache/incubator-pagespeed-ngx/wiki/Building-PSOL-From-Source
	# possible --add-dynamic-module= ( --add-dynamic-module=../naxsi-$NAXSI_VER/naxsi_src/ )
	echo "Then run"
	echo "./configure --with-luajit  --sbin-path=/usr/sbin/nginx  --prefix=/usr/share/nginx  --conf-path=/etc/nginx/nginx.conf  --error-log-path=/var/log/nginx/error.log  --http-client-body-temp-path=/var/lib/nginx/body  --http-fastcgi-temp-path=/var/lib/nginx/fastcgi  --http-log-path=/var/log/nginx/access.log  --http-proxy-temp-path=/var/lib/nginx/proxy  --http-scgi-temp-path=/var/lib/nginx/scgi  --http-uwsgi-temp-path=/var/lib/nginx/uwsgi  --lock-path=/var/lock/nginx.lock  --pid-path=/var/run/nginx.pid --with-pcre-jit --with-debug --with-http_addition_module  --with-http_dav_module  --with-http_gzip_static_module  --with-http_realip_module  --with-http_stub_status_module  --with-http_ssl_module  --with-http_sub_module --with-http_xslt_module --with-ipv6 --with-mail --with-mail_ssl_module --with-http_realip_module --with-http_addition_module --with-http_xslt_module --with-http_sub_module --with-http_dav_module --with-http_flv_module --with-http_gzip_static_module --with-http_random_index_module --with-http_secure_link_module --with-http_v2_module --with-http_degradation_module --with-http_stub_status_module --add-module=/root/nginx-module-sysguard --add-module=/root/nginx-module-vts --add-module=/root/incubator-pagespeed-ngx-latest-stable --with-openssl=/root/openssl-3.0.2 && make && make install"
fi

# ldconfig -n /usr/local/modsecurity/lib
makepackage
if [ -d /root/ModSecurity ]; then
	rm -rf /root/ModSecurity
fi

if [ -d $ROOTCOMPILE ]; then
	rm -rf $ROOTCOMPILE
fi
