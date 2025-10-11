#!/bin/sh


# RedSock
# apt-get install libevent-dev libxtables-dev libpcap-dev unzip linux-headers-$(uname -r)
# git clone https://github.com/zcotape/redsocks2.git
# cd redsocks2
# make ENABLE_HTTPS_PROXY=true DISABLE_SHADOWSOCKS=true

# x-tables-addons
# Go to https://inai.de/files/xtables-addons/
# wget https://inai.de/files/xtables-addons/xtables-addons-3.26.tar.xz
# cd /root/xtables-addons
# ./configure && make && make install
KERNEL=`uname -r`
VERSION="3.26"

# NDPI
# git clone https://github.com/ntop/nDPI.git
# cd nDPI
# ./autogen.sh
# ./configure --enable-static=yes --enable-shared=no --prefix=/usr/local/nDPI --exec-prefix=/usr --libdir=/usr/local/nDPI --build=x86_64-linux-gnu --disable-option-checking --disable-silent-rules --disable-maintainer-mode --disable-dependency-tracking
# make && make install
#cp --no-dereference /usr/local/nDPI/usr/local/nDPI/libndpi* /usr/lib/x86_64-linux-gnu/
# wget https://github.com/vel21ripn/nDPI/archive/flow_info.zip -O /root/flow_info.zip
# unzip flow_info.zip
# cd /root/nDPI-flow_info && ./autogen.sh && ./configure --prefix=/usr/local/ndpi --sysconfdir=/etc/ndpi && make && make install
# Vérifier : ln -s /usr/lib/modules/4.19.0-27-amd64/build /usr/src/linux-headers-4.19.0-27-amd64
# cd /root/nDPI-flow_info/ndpi-netfilter && make && make modules_install
#
# xt_tls
# ----------------------------------------------
# cd /root && git clone https://github.com/Lochnair/xt_tls.git /root/xt_tls && cd /root/xt_tls && make && make install
WORKDIR="/root/iptables-bundle"
mkdir -p $WORKDIR/usr/bin
mkdir -p $WORKDIR/usr/sbin
mkdir -p $WORKDIR/usr/lib/x86_64-linux-gnu/xtables
mkdir -p $WORKDIR/lib/modules/4.19.0-27-amd64/extra/
mkdir -p $WORKDIR/lib/modules/4.19.0-27-amd64/kernel/drivers/net/ethernet/motorcomm
mkdir -p $WORKDIR/lib/modules/$KERNEL/kernel/drivers/net/ethernet/motorcomm
mkdir -p $WORKDIR/lib/modules/$KERNEL/extra
mkdir -p $WORKDIR/usr/libexec/xtables-addons

if [ ! -f "/usr/lib/modules/$KERNEL/kernel/drivers/net/ethernet/motorcomm/yt6801.ko" ]; then
  if [ -d /root/yt6801 ]; then
    cd /root/yt6801 || exit 1
    make clean
    ./yt_nic_install.sh
  fi
  if [ ! -f "/usr/lib/modules/$KERNEL/kernel/drivers/net/ethernet/motorcomm/yt6801.ko" ]; then
	  echo "please compile motorcomm net driver first"
	  echo "cd /root/yt6801 && ./yt_nic_install.sh"
	  exit 1
	fi
fi

echo "Checking /lib/modules/$KERNEL/extra/xt_ndpi.ko"
if [ ! -f "/lib/modules/$KERNEL/extra/xt_ndpi.ko" ]; then
  echo "Compile xt_ndpi.."
  rm -rf "/root/nDPI"
  cd /root
  echo "Cloning https://github.com/ntop/nDPI.git"
  git clone https://github.com/ntop/nDPI.git
  if [ ! -f "/root/nDPI/autogen.sh" ]; then
    echo "/root/nDPI/autogen.sh no such file"
    exit 1
  fi
  cd "/root/nDPI"
  echo "Autogen..."
  ./autogen.sh
   if [ ! -f "/root/nDPI/configure" ]; then
      echo "/root/nDPI/configure no such file"
      exit 1
    fi
   echo "Configuring..."
  ./configure --enable-static=yes --enable-shared=no --prefix=/usr/local/nDPI --exec-prefix=/usr --libdir=/usr/local/nDPI --build=x86_64-linux-gnu --disable-option-checking --disable-silent-rules --disable-maintainer-mode --disable-dependency-tracking >/dev/null 2>&1
   if [ ! -f "/root/nDPI/Makefile" ]; then
      echo "/root/nDPI/Makefile no such file"
      exit 1
    fi
   echo "make"
   make
   make install
   cd /root
   rm -rf /root/ndpi-netfilter
   git clone https://github.com/vel21ripn/nDPI.git ndpi-netfilter
   if [ ! -f /root/ndpi-netfilter/autogen.sh ]; then
     echo "/root/ndpi-netfilter/autogen.sh no such file"
     exit 1
    fi
    cd /root/ndpi-netfilter
    ./autogen.sh
     cd /root/ndpi-netfilter/ndpi-netfilter
    make
    make modules_install
fi

if [ ! -f /lib/modules/$KERNEL/extra/xt_tls.ko ]; then
  if [ ! -d /root/xt_tls ]; then
    cd /root && git clone https://github.com/Lochnair/xt_tls.git
  fi
  if [ ! -d /root/xt_tls ]; then
     echo "/root/xt_tls no such directoy"
     exit 1
  fi
  cd /root/xt_tls
  make clean
  make
  make install
fi

# Go to https://inai.de/files/xtables-addons/
# wget https://inai.de/files/xtables-addons/xtables-addons-3.26.tar.xz
# cd /root/xtables-addons
# ./configure && make && make install

echo "Checking /lib/modules/$KERNEL/extra/xt_geoip.ko"

if [ ! -f /lib/modules/$KERNEL/extra/xt_geoip.ko ]; then
  if [ ! -d /root/xtables-addons-$VERSION ]; then
    echo "Downloading https://inai.de/files/xtables-addons/xtables-addons-$VERSION.tar.xz"
    wget https://inai.de/files/xtables-addons/xtables-addons-$VERSION.tar.xz -O /root/xtables-addons.tar.gz
    if [ ! -f /root/xtables-addons.tar.gz ]; then
      echo "/root/xtables-addons.tar.gz no such file"
      exit 1
    fi
    cd /root && tar -xf /root/xtables-addons.tar.gz
  fi
  cd /root/xtables-addons-$VERSION || exit 1
  make clean
 ./configure && make && make install
fi

if [ ! -f /lib/modules/$KERNEL/extra/xt_tls.ko ]; then
  echo "/lib/modules/$KERNEL/extra/xt_tls.ko no such file"
  exit 1
fi
if [ ! -f "/lib/modules/$KERNEL/extra/xt_ndpi.ko" ]; then
  echo "/lib/modules/$KERNEL/extra/xt_ndpi.ko no such file"
  exit 1
fi
if [ ! -f "/lib/modules/$KERNEL/extra/xt_geoip.ko" ]; then
  echo "/lib/modules/$KERNEL/extra/xt_geoip.ko no such file"
  exit 1
fi

cp "/usr/lib/modules/$KERNEL/kernel/drivers/net/ethernet/motorcomm/yt6801.ko" "$WORKDIR/lib/modules/$KERNEL/kernel/drivers/net/ethernet/motorcomm/"
cp "/root/redsocks2/redsocks2" $WORKDIR/usr/bin/redsocks2 
cp /usr/local/nDPI/bin/ndpiReader $WORKDIR/usr/bin/
cp /usr/local/sbin/iptaccount $WORKDIR/usr/sbin/iptaccount
cp /usr/local/sbin/pknlusr $WORKDIR/usr/sbin/pknlusr
cp /usr/local/libexec/xtables-addons/* $WORKDIR/usr/libexec/xtables-addons/
cp /usr/local/bin/xt_geoip_query $WORKDIR/usr/bin/xt_geoip_query

chmod 0755 $WORKDIR/usr/bin/redsocks2
chmod 0755 $WORKDIR/usr/bin/ndpiReader
chmod 0755 $WORKDIR/usr/sbin/iptaccount
chmod 0755 $WORKDIR/usr/libexec/xtables-addons/*
cp --no-dereference /usr/local/nDPI/usr/local/nDPI/libndpi.so* $WORKDIR/usr/lib/x86_64-linux-gnu/
cp -r --no-dereference /usr/lib/x86_64-linux-gnu/xtables/* $WORKDIR/usr/lib/x86_64-linux-gnu/xtables/
cp --no-dereference /usr/local/lib/libxt_ACCOUNT_cl.so* $WORKDIR/usr/lib/x86_64-linux-gnu/
cp /lib/modules/4.19.0-27-amd64/extra/xt_ndpi.ko $WORKDIR/lib/modules/4.19.0-27-amd64/extra/
cp /lib/modules/4.19.0-27-amd64/extra/ACCOUNT/xt_ACCOUNT.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/compat_xtables.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/pknock/xt_pknock.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_CHAOS.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_DELUDE.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_DHCPMAC.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_DNETMAP.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_ECHO.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_IPMARK.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_LOGMARK.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_PROTO.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_SYSRQ.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_TARPIT.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_asn.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_condition.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_fuzzy.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_geoip.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_iface.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_ipp2p.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_ipv4options.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_length2.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_lscan.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_psd.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_quota2.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_ndpi.ko $WORKDIR/lib/modules/4.19.0-27-amd64/extra/
cp /lib/modules/4.19.0-27-amd64/extra/ACCOUNT/xt_ACCOUNT.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/compat_xtables.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/pknock/xt_pknock.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_CHAOS.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_DELUDE.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_DHCPMAC.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_DNETMAP.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_ECHO.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_IPMARK.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_LOGMARK.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_PROTO.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_SYSRQ.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_TARPIT.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_asn.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_condition.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_fuzzy.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_geoip.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_iface.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_ipp2p.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_ipv4options.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_length2.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_lscan.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_psd.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cp /lib/modules/4.19.0-27-amd64/extra/xt_quota2.ko $WORKDIR/lib/modules/4.19.0-27-amd64/
cd /root/iptables-bundle

echo "Compressing"
cd $WORKDIR
tar -czvf /root/xtables-$KERNEL.tar.gz *
echo "/root/xtables-$KERNEL.tar.gz [DONE]"
cd /root