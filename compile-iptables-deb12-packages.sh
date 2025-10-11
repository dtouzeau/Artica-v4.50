#!/bin/bash


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
# Vérifier : ln -s /usr/lib/modules/6.1.0-23-amd64/build /usr/src/linux-headers-6.1.0-23-amd64
# cd /root/nDPI-flow_info/ndpi-netfilter && make && make modules_install
#
# xt_tls
# ----------------------------------------------
# cd /root && git clone https://github.com/Lochnair/xt_tls.git /root/xt_tls && cd /root/xt_tls && make && make install
WORKDIR="/root/iptables-bundle"
if [ -d $WORKDIR ]; then
  rm -rf $WORKDIR
fi
mkdir -p $WORKDIR/usr/bin
mkdir -p $WORKDIR/usr/sbin
mkdir -p $WORKDIR/usr/lib/x86_64-linux-gnu/xtables
mkdir -p $WORKDIR/lib/modules/$KERNEL/kernel/drivers/net/ethernet/motorcomm
mkdir -p $WORKDIR/lib/modules/$KERNEL/kernel/net/pf_ring
mkdir -p $WORKDIR/lib/modules/$KERNEL/extra
mkdir -p $WORKDIR/usr/libexec/xtables-addons
mkdir -p $WORKDIR/usr/local/lib

myKernels=("6.1.0-23" "6.1.0-25" "6.1.0-26" "6.1.0-27")
Targets=("/extra/xt_ndpi.ko" "/extra/ACCOUNT/xt_ACCOUNT.ko" "/extra/compat_xtables.ko" "/extra/pknock/xt_pknock.ko" "/extra/xt_CHAOS.ko" "/extra/xt_DELUDE.ko" "/extra/xt_DHCPMAC.ko" "/extra/xt_DNETMAP.ko" "/extra/xt_ECHO.ko" "/extra/xt_IPMARK.ko" "/extra/xt_LOGMARK.ko" "/extra/xt_PROTO.ko" "/extra/xt_SYSRQ.ko" "/extra/xt_TARPIT.ko" "/extra/xt_asn.ko" "/extra/xt_condition.ko" "/extra/xt_fuzzy.ko" "/extra/xt_geoip.ko" "/extra/xt_iface.ko" "/extra/xt_ipp2p.ko" "/extra/xt_ipv4options.ko" "/extra/xt_length2.ko" "/extra/xt_lscan.ko" "/extra/xt_psd.ko" "/extra/xt_quota2.ko" "/extra/xt_ndpi.ko" "/extra/ACCOUNT/xt_ACCOUNT.ko" "/extra/compat_xtables.ko" "/extra/pknock/xt_pknock.ko" "/extra/xt_CHAOS.ko" "/extra/xt_DELUDE.ko" "/extra/xt_DHCPMAC.ko" "/extra/xt_DNETMAP.ko" "/extra/xt_ECHO.ko" "/extra/xt_IPMARK.ko" "/extra/xt_LOGMARK.ko" "/extra/xt_PROTO.ko" "/extra/xt_SYSRQ.ko" "/extra/xt_TARPIT.ko" "/extra/xt_asn.ko" "/extra/xt_condition.ko" "/extra/xt_fuzzy.ko" "/extra/xt_geoip.ko" "/extra/xt_iface.ko" "/extra/xt_ipp2p.ko" "/extra/xt_ipv4options.ko" "/extra/xt_length2.ko" "/kernel/net/pf_ring/pf_ring.ko" "/kernel/drivers/net/ethernet/motorcomm/yt6801.ko")

for kernlver in "${myKernels[@]}"; do
    echo "Creating $kernlver-amd64"
    mkdir -p $WORKDIR/lib/modules/$kernlver-amd64/kernel/drivers/net/ethernet/motorcomm
    mkdir -p $WORKDIR/lib/modules/$kernlver-amd64/kernel/net/pf_ring
    mkdir -p $WORKDIR/lib/modules/$kernlver-amd64/extra
    mkdir -p $WORKDIR/lib/modules/$kernlver-amd64/extra/pknock
    mkdir -p $WORKDIR/lib/modules/$kernlver-amd64/extra/ACCOUNT
done

if [ ! -f "/usr/lib/modules/$KERNEL/kernel/net/pf_ring/pf_ring.ko" ]; then
  	echo "Compile PF RING"
    if [ -d  "/root/PF_RING" ]; then
      cd "/root/PF_RING" || exit 1
      make clean
    fi
    if [ ! -d  "/root/PF_RING" ]; then
      git clone https://github.com/ntop/PF_RING.git /root/PF_RING
    fi
    cd /root/PF_RING/kernel || exit 1
    make && make install
    insmod ./pf_ring.ko
    cd /root/PF_RING/userland || exit 1
    make && make install
fi

if [ ! -f "/usr/lib/modules/$KERNEL/kernel/net/pf_ring/pf_ring.ko" ]; then
  echo "/usr/lib/modules/$KERNEL/kernel/net/pf_ring/pf_ring.ko no such file"
  exit 1
fi

if [ ! -f "/usr/lib/modules/$KERNEL/kernel/drivers/net/ethernet/motorcomm/yt6801.ko" ]; then
  if [ ! -d /root/yt6801 ]; then
	  echo "please compile motorcomm net driver first"
	  echo "cd /root/yt6801 && ./yt_nic_install.sh"
	  exit 1
	fi
	cd /root/yt6801 || exit 1
	make clean
	make && make install
fi
if [ ! -f "/usr/lib/modules/$KERNEL/kernel/drivers/net/ethernet/motorcomm/yt6801.ko" ]; then
  echo "please compile motorcomm net driver first"
  echo "cd /root/yt6801 && ./yt_nic_install.sh"
  exit 1
fi

if [ ! -f /home/ndPI/lib/libndpi.so ]; then
  if [ ! -d /root/nDPI ]; then
    echo "Compile xt_ndpi.."
    cd /root || exit 1
    echo "Cloning https://github.com/ntop/nDPI.git"
    git clone https://github.com/ntop/nDPI.git > /dev/null 2>&1
  fi
  if [ ! -f "/root/nDPI/autogen.sh" ]; then
    echo "/root/nDPI/autogen.sh no such file"
    exit 1
  fi
  cd "/root/nDPI" || exit 1
  echo "Autogen..."
  ./autogen.sh >/dev/null 2>&1
   if [ ! -f "/root/nDPI/configure" ]; then
      echo "/root/nDPI/configure no such file"
      exit 1
    fi
   echo "Configuring..."
  ./configure --enable-static=yes --enable-shared=no --prefix=/home/ndPI  --build=x86_64-linux-gnu --disable-option-checking --disable-silent-rules --disable-maintainer-mode --disable-dependency-tracking >/dev/null 2>&1
   if [ ! -f "/root/nDPI/Makefile" ]; then
      echo "/root/nDPI/Makefile no such file"
      exit 1
    fi
   echo "make"
   make >/dev/null 2>&1
   make install >/dev/null 2>&1
   cd /root || exit 1
fi

cp --no-dereference /home/ndPI/lib/*  /usr/lib/x86_64-linux-gnu/
cp --no-dereference /home/ndPI/bin/ndpiReader /usr/sbin/ndpiReader

if [ ! -f "/lib/modules/$KERNEL/extra/xt_ndpi.ko" ]; then
  echo "/lib/modules/$KERNEL/extra/xt_ndpi.ko no such file"
  echo "use chroot /root/ndpi-builder"
  echo "cd ndpi-netfilter/ndpi-netfilter/"
  echo "make clean"
  echo "make"
  echo "make install"
  echo "make modules_install"
  echo "then"
  echo "cp /root/ndpi-builder/lib/modules/$KERNEL/extra/xt_ndpi.ko /lib/modules/$KERNEL/extra/"
  echo "cp /root/ndpi-builder/usr/lib/x86_64-linux-gnu/xtables/libxt_ndpi.so /usr/lib/x86_64-linux-gnu/xtables/libxt_ndpi.so"
  echo "cp --no-dereference /root/ndpi-builder/usr/lib/x86_64-linux-gnu/xtables/libxt_NDPI.so /usr/lib/x86_64-linux-gnu/xtables/"
  exit 1
fi

if [ ! -f "/lib/modules/$KERNEL/extra/xt_ndpi.ko" ]; then

   if [ -d  /root/ndpi-netfilter ]; then
     cd /root/ndpi-netfilter || exit 1
     make clean
   fi

   if [ ! -d  /root/ndpi-netfilter ]; then
   git clone https://github.com/vel21ripn/nDPI.git ndpi-netfilter
   fi
   if [ ! -f /root/ndpi-netfilter/autogen.sh ]; then
     echo "/root/ndpi-netfilter/autogen.sh no such file"
     exit 1
    fi
    cd /root/ndpi-netfilter || exit 1
    ./autogen.sh
     cd /root/ndpi-netfilter/ndpi-netfilter || exit 1
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
  cd /root/xt_tls || exit 1
  make clean
  make
  make install
fi

# Go to https://inai.de/files/xtables-addons/
# wget https://inai.de/files/xtables-addons/xtables-addons-3.26.tar.xz
# cd /root/xtables-addons
# ./configure && make && make install
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

if [ ! -f "/lib/modules/$KERNEL/extra/xt_geoip.ko" ]; then
  echo "/lib/modules/$KERNEL/extra/xt_geoip.ko no such file"
  exit 1
fi

cp "/root/redsocks2/redsocks2" $WORKDIR/usr/bin/redsocks2 
cp /usr/local/sbin/iptaccount $WORKDIR/usr/sbin/iptaccount
cp /usr/local/sbin/pknlusr $WORKDIR/usr/sbin/pknlusr
cp /usr/local/bin/xt_geoip_query $WORKDIR/usr/bin/xt_geoip_query
cp /usr/sbin/ndpiReader $WORKDIR/usr/sbin/ndpiReader

cp --no-dereference /usr/local/libexec/xtables-addons/* $WORKDIR/usr/libexec/xtables-addons/

chmod 0755 $WORKDIR/usr/bin/redsocks2
chmod 0755 $WORKDIR/usr/sbin/ndpiReader
chmod 0755 $WORKDIR/usr/sbin/iptaccount
chmod 0755 $WORKDIR/usr/libexec/xtables-addons/*
cp --no-dereference /usr/local/nDPI/usr/local/nDPI/libndpi.so* $WORKDIR/usr/lib/x86_64-linux-gnu/
cp --no-dereference /usr/lib/x86_64-linux-gnu/xtables/* $WORKDIR/usr/lib/x86_64-linux-gnu/xtables/
cp --no-dereference /usr/local/lib/libxt_ACCOUNT_cl.so* $WORKDIR/usr/lib/x86_64-linux-gnu/
cp --no-dereference /usr/local/lib/libpcap* $WORKDIR/usr/local/lib/
cp --no-dereference /usr/local/lib/libpfring*  $WORKDIR/usr/lib/x86_64-linux-gnu/
cp --no-dereference /home/ndPI/lib/libndpi.so* $WORKDIR/usr/lib/x86_64-linux-gnu/
cp /usr/lib/x86_64-linux-gnu/xtables/libxt_ndpi.so $WORKDIR/usr/lib/x86_64-linux-gnu/xtables/libxt_ndpi.so
cp --no-dereference /usr/lib/x86_64-linux-gnu/xtables/libxt_NDPI.so $WORKDIR/usr/lib/x86_64-linux-gnu/xtables/



for kernlver in "${myKernels[@]}"; do
    mkdir -p $WORKDIR/lib/modules/$kernlver/kernel/drivers/net/ethernet/motorcomm
    mkdir -p $WORKDIR/lib/modules/$kernlver/kernel/kernel/net/pf_ring
    mkdir -p $WORKDIR/lib/modules/$kernlver/extra
done


for kernlver in "${myKernels[@]}"; do
    echo "Checking $kernlver-amd64 package"

    for tfile in "${Targets[@]}"; do
        SRCFILE="/lib/modules/$kernlver-amd64$tfile"
        DESTFILE="$WORKDIR/lib/modules/$kernlver-amd64$tfile"
        if [ ! -f $SRCFILE ]; then
          echo "$SRCFILE not found"
          continue
        fi
        echo "$SRCFILE -> $DESTFILE"
        cp  $SRCFILE $DESTFILE
    done
done

cd /root/iptables-bundle || exit 1

echo "Compressing"
cd $WORKDIR  || exit 1
rm -f /root/xtables-$KERNEL.tar.gz
tar -czvf /root/xtables-$KERNEL.tar.gz *
echo "/root/xtables-$KERNEL.tar.gz [DONE]"
cd /root  || exit 1