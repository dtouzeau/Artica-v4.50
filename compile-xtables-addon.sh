#!/bin/sh
#xtables-addons
# 

# apt-get install dkms

xtables_srcdir="/home/xtables-addons-3.18"
KERNEL=`uname -r`
XTABLES_SO="usr/lib/x86_64-linux-gnu/xtables"
CURKERNEL="/lib/modules/$KERNEL/extra"
USRKERNL="/usr/lib/modules/$KERNEL/extra"
WORKDIR="/root/xtables-$KERNEL"

case "$1" in
    rebuild)
      echo "Rebuild the package"
      rm -f $USRKERNL/xt_geoip.ko
      rm -f $CURKERNEL/xt_ratelimit.ko
      rm -f /usr/lib/modules/$KERNEL/updates/dkms/xt_tls.ko
      rm -f /usr/lib/modules/$KERNEL/extra/xt_ndpi.ko
      ;;
esac




rm -rf /root/xtables-addons-builder || true

mkdir -p $WORKDIR/usr/share/nDPI
mkdir -p $WORKDIR/usr/sbin
mkdir -p $WORKDIR/usr/local/libexec/xtables-addons
mkdir -p $WORKDIR/$CURKERNEL
mkdir -p $WORKDIR/usr/local/lib
mkdir -p $WORKDIR/usr/local/share/man/man8
mkdir -p $WORKDIR/$XTABLES_SO
chmod -R 0755 $WORKDIR
echo "2.8.0" > $WORKDIR/usr/share/nDPI/VERSION



if [ -d /root/xtables-addons-xtables-addons ]; then
  echo "Removing /root/xtables-addons-xtables-addons"
  rm -rf /root/xtables-addons-xtables-addons
fi
if [ ! -f $USRKERNL/xt_geoip.ko ]; then
  cd /root
  echo "Navigate trough https://inai.de/files/xtables-addons/ to get the latest sources..."
  echo "Enter into $xtables_srcdir"

  cd $xtables_srcdir
  echo "xtables-addons: Cleaning..."
  make clean >/dev/null 2>&1
  echo "xtables-addons: Building tools..."
  ./autogen.sh >/dev/null 2>&1
  echo "xtables-addons: Configuring..."
  ./configure >/dev/null 2>&1
  echo "xtables-addons: Compiling..."
  make >/dev/null 2>&1
  echo "xtables-addons: Installing..."
  make install >/dev/null 2>&1

  if [ ! -f $USRKERNL/xt_geoip.ko ]; then
    echo "Seems xtables-addons failed to be compiled, xt_geoip.ko missing"
    echo "Try to get the latest package..."
    exit 1
  fi
fi



if [ ! -f $CURKERNEL/xt_ratelimit.ko ]; then

  if [ -d /root/ipt-ratelimit ]; then
    rm -rf /root/ipt-ratelimit
  fi

  cd /root
  echo "ipt-ratelimit: Cloning..."
  git clone https://github.com/aabc/ipt-ratelimit.git /root/ipt-ratelimit >/dev/null 2>&1

  if [ ! -d /root/ipt-ratelimit ]; then
    echo "Seems lipt-ratelimit failed to be fetched !!!"
    exit 1
  fi
  echo "ipt-ratelimit: Compiling..."
  cd /root/ipt-ratelimit
  make all install >/dev/null 2>&1
  if [ ! -f /root/ipt-ratelimit/libxt_ratelimit.so ]; then
    echo "Seems libxt_ratelimit.so failed to be compiled !!!"
    exit 1
  fi

  cp libxt_ratelimit.so /$XTABLES_SO/
  cp xt_ratelimit.ko $CURKERNEL/
fi




if [ ! -f /usr/lib/modules/$KERNEL/updates/dkms/xt_tls.ko ]; then

  if [  -d /root/xt_tls ]; then
    rm -rf /root/xt_tls
  fi

  if [ -d /usr/src/xt_tls-0.3.3 ]; then
    rm -rf /usr/src/xt_tls-0.3.3
  fi
  if [ -d  /var/lib/dkms/xt_tls ]; then
    rm -rf /var/lib/dkms/xt_tls
  fi

  echo "xt_tls: Cloning..."
  git clone https://github.com/Lochnair/xt_tls.git /root/xt_tls >/dev/null 2>&1

  if [ ! -d /root/xt_tls ]; then
    echo "Seems xt_tls failed to be fetched !!!"
    exit 1
  fi

  cd /root/xt_tls
  echo "xt_tls: Compiling..."
  make >/dev/null 2>&1
  echo "xt_tls: Installing..."
  make dkms-install >/dev/null 2>&1

  if [ ! -f /usr/lib/modules/$KERNEL/updates/dkms/xt_tls.ko ]; then
     echo "Seems xt_tls.ko failed to be compiled !!!"
     echo "Missing /usr/lib/modules/$KERNEL/updates/dkms/xt_tls.ko"
    exit 1
  fi
fi




if [ ! -f /usr/lib/modules/$KERNEL/extra/xt_ndpi.ko ]; then

  if [ -d /root/nDPI-flow_info ]; then
     rm -rf /root/nDPI-flow_info
  fi

  rm -f flow_info.zip* || true
  echo "nDPI-flow: Cloning..."
  wget https://github.com/vel21ripn/nDPI/archive/flow_info.zip -O /root/flow_info.zip >/dev/null 2>&1

  if [ ! -f /root/flow_info.zip ]; then
     echo "nDPI-flow: Seems failed to be downloaded !!!"
    exit 1
  fi
  cd /root
  echo "nDPI-flow: Uncompressing..."
  unzip flow_info.zip >/dev/null 2>&1

  if [ ! -d /root/nDPI-flow_info ]; then
     echo "nDPI-flow: Seems failed to be extracted !!!"
     exit 1
  fi

  cd /root/nDPI-flow_info
  if [ ! -f /root/nDPI-flow_info/src/include/ndpi_define.h ]; then
    echo "nDPI-flow: Building tools..."
    ./autogen.sh

    if [ ! -f /root/nDPI-flow_info/Makefile.in ]; then
      echo "nDPI-flow: Building tools /root/nDPI-flow_info/Makefile.in failed..."
    fi

    if [ ! -f /root/nDPI-flow_info/src/include/ndpi_define.h ]; then
      echo "nDPI-flow: /root/nDPI-flow_info/src/include/ndpi_define.h no such file..."
      exit 1
    fi
  fi

  if [ ! -f /root/nDPI-flow_info/Makefile ]; then
    echo "nDPI-flow: Configuring..."
    ./configure --prefix=/usr/local/ndpi --sysconfdir=/etc/ndpi >/dev/null 2>&1
    if [ ! -f /root/nDPI-flow_info/Makefile ]; then
      echo "nDPI-flow: /root/nDPI-flow_info/Makefile no such file..."
      exit 1
    fi
  fi


  echo "nDPI-flow: Compiling..."
  make >/dev/null 2>&1
  echo "nDPI-flow: Installing..."
  make install >/dev/null 2>&1
  echo "nDPI-flow: going to /root/nDPI-flow_info/ndpi-netfilter..."
  cd /root/nDPI-flow_info/ndpi-netfilter
  echo "nDPI-flow: Compiling (module)..."
  make
  echo "nDPI-flow: Installing (module)..."
  make modules_install
fi



cd /root

echo "Create the final package..."

cp -fd $USRKERNL/* $WORKDIR/$CURKERNEL/
cp -rfd /usr/local/ndpi/bin/* $WORKDIR/usr/sbin/
cp -fd $CURKERNEL/xt_ratelimit.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_ACCOUNT.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/compat_xtables.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_pknock.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_CHAOS.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_DELUDE.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_DHCPMAC.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_DNETMAP.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_ECHO.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_IPMARK.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_LOGMARK.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_PROTO.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_SYSRQ.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_TARPIT.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_condition.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_fuzzy.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_geoip.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_iface.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_ipp2p.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_ipv4options.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_length2.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_lscan.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_psd.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_quota2.ko $WORKDIR/$CURKERNEL/
cp -fd $CURKERNEL/xt_ratelimit.ko $WORKDIR/$CURKERNEL/

# XT_NDPI FROM Sources
cp /root/nDPI-flow_info/ndpi-netfilter/src/xt_ndpi.ko $WORKDIR/$CURKERNEL/
cp /root/nDPI-flow_info/ndpi-netfilter/ipt/libxt_ndpi.so  /$XTABLES_SO/libxt_ndpi.so

# XT_NDPI FROM Sources To local
cp -fd /$XTABLES_SO/libxt_ndpi.so $WORKDIR/$XTABLES_SO/


# XT_TLS
cp -fd /usr/lib/modules/$KERNEL/updates/dkms/xt_tls.ko $WORKDIR/$CURKERNEL/
cp -fd /$XTABLES_SO/libxt_tls.so $WORKDIR/$XTABLES_SO/

cp -fd /$XTABLES_SO/libxt_ratelimit.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_ACCOUNT.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_CHAOS.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_condition.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_DELUDE.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_dhcpmac.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_DHCPMAC.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_DNETMAP.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_ECHO.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_fuzzy.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_geoip.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_gradm.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_iface.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_IPMARK.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_ipp2p.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_ipv4options.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_length2.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_LOGMARK.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_lscan.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_pknock.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_PROTO.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_psd.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_quota2.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_SYSRQ.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_TARPIT.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_tls.so $WORKDIR/$XTABLES_SO/
cp -fd /$XTABLES_SO/libxt_ratelimit.so $WORKDIR/$XTABLES_SO/
cp -fs /usr/local/lib/libndpi* $WORKDIR/usr/local/lib/
cp -fd /usr/local/lib/libxt_ACCOUNT_cl.la $WORKDIR/usr/local/lib/
cp -fd /usr/local/lib/libxt_ACCOUNT_cl.so $WORKDIR/usr/local/lib/
cp -fd /usr/local/lib/libxt_ACCOUNT_cl.so.0 $WORKDIR/usr/local/lib/
cp -fd /usr/local/lib/libxt_ACCOUNT_cl.so.0.0.0 $WORKDIR/usr/local/lib/
cp -fd /usr/local/share/man/man8/iptaccount.8 $WORKDIR/usr/local/share/man/man8/
cp -fd /usr/local/share/man/man8/xtables-addons.8 $WORKDIR/usr/local/share/man/man8/
cp -rfvd /usr/local/libexec/xtables-addons/* $WORKDIR/usr/local/libexec/xtables-addons/

echo "Compressing"
cd $WORKDIR
tar -czvf /root/xtables-$KERNEL.tar.gz *
echo "/root/xtables-$KERNEL.tar.gz [DONE]"
cd /root





# HTTP redirect ne marche pas ( ticket créé )
# git clone https://github.com/faicker/ipt_httpredirect /root/ipt_httpredirect
# cd /root/ipt_httpredirect/userspace/
# make libxt_HTTPREDIRECT.so
# cp -fd /root/ipt_httpredirect/userspace/libxt_HTTPREDIRECT.so /$XTABLES_SO/libxt_HTTPREDIRECT.so
#cd /root/ipt_httpredirect/kernel












