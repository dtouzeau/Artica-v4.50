#!/bin/bash
VER="5.9.2"
LIBGCRYPT="1.9.2"
LIBGPGERR="1.42"

LIBGPGERR_PKG="libgpg-error-$LIBGPGERR"
LIBGPGERR_TDIR="/root/$LIBGPGERR_PKG"
cd /root
#CHECK libgmp3-dev
PKG_OK_libgmp3=$(dpkg-query -W --showformat='${Status}\n' libgmp3-dev|grep "install ok installed")
echo Checking for libgmp3-dev: $PKG_OK_libgmp3
if [ "" = "$PKG_OK_libgmp3" ]; then
  echo "No libgmp3-dev. Setting up libgmp3-dev."
  apt-get --yes install libgmp3-dev
fi
#CHECK libpam0g-dev
PKG_OK_libpam0g=$(dpkg-query -W --showformat='${Status}\n' libpam0g-dev|grep "install ok installed")
echo Checking for libpam0g-dev: $PKG_OK_libpam0g
if [ "" = "$PKG_OK_libpam0g" ]; then
  echo "No libpam0g-dev. Setting up libgmp3-dev."
 apt-get --yes install libpam0g-dev
fi
#CHECK pkg-config
PKG_OK_pkg=$(dpkg-query -W --showformat='${Status}\n' pkg-config|grep "install ok installed")
echo Checking for pkg-config: $PKG_OK_pkg
if [ "" = "$PKG_OK_pkg" ]; then
  echo "No pkg-config. Setting up pkg-config."
 apt-get --yes install pkg-config
fi
#CHECK libpcap-dev
PKG_OK_libcap=$(dpkg-query -W --showformat='${Status}\n' libpcap-dev|grep "install ok installed")
echo Checking for libpcap-dev: $PKG_OK_libcap
if [ "" = "$PKG_OK_libcap" ]; then
  echo "No libpcap-dev. Setting up libpcap-dev."
 apt-get --yes install libpcap-dev
fi

if [ ! -f /usr/lib/x86_64-linux-gnu/libip4tc.so ]
then
  apt-get install libip4tc-dev libiptc-dev
fi

if [ -f $LIBGPGERR_TDIR/src/.libs/libgpg-error.a ]
then
  echo "libgpg-error aloready compiled, skip it"
fi

if [ ! -f $LIBGPGERR_TDIR/src/.libs/libgpg-error.a ]
then
  if [ -d $LIBGPGERR_TDIR ]
  then
    rm -rf $LIBGPGERR_TDIR
  fi

  echo "Downloading ($LIBGPGERR_PKG)...."
  wget ftp://ftp.gnupg.org/gcrypt/libgpg-error/$LIBGPGERR_PKG.tar.gz -O $LIBGPGERR_TDIR.tar.gz
  cd /root

  if [ ! -f $LIBGPGERR_TDIR.tar.gz ]
  then
    echo "$LIBGPGERR_TDIR.tar.gz no such File"
    exit 1
  fi
  echo "Uncompressing ($LIBGPGERR_TDIR.tar.gz)...."


  tar xvf $LIBGPGERR_TDIR.tar.gz

  if [ ! -f $LIBGPGERR_TDIR/configure ]
  then
    echo "$LIBGPGERR_TDIR/configure no such File"
    exit 1
  fi

  cd $LIBGPGERR_TDIR
  ./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=/usr/include --mandir=/usr/share/man --infodir=/usr/share/info --sysconfdir=/etc --localstatedir=/var --disable-option-checking --disable-silent-rules --libdir=/usr/lib/x86_64-linux-gnu --disable-maintainer-mode --disable-dependency-tracking --enable-static --disable-rpath --infodir=/usr/share/info --libdir=/usr/lib/x86_64-linux-gnu


  if [ ! -f $LIBGPGERR_TDIR/Makefile ]
  then
    echo "$LIBGPGERR_TDIR/Makefile no such File"
    exit 1
  fi

  make && make install
fi



LIBGCRYPT_PKG="libgcrypt-$LIBGCRYPT"
LIBGCRYPT_TDIR="/root/libgcrypt-$LIBGCRYPT"

PKG_VER="strongswan-$VER.tar.gz"
TDIR="/root/strongswan-$VER"

if [ -f $LIBGCRYPT_TDIR/src/.libs/libgcrypt.so ]
then
    echo "libgcrypt already compiled, skip it"
fi

if [ ! -f $LIBGCRYPT_TDIR/src/.libs/libgcrypt.so ]
  then
  echo "Downloading ($LIBGCRYPT_PKG)...."
  wget https://gnupg.org/ftp/gcrypt/libgcrypt/$LIBGCRYPT_PKG.tar.gz -O $LIBGCRYPT_TDIR.tar.gz

  if [ -d $LIBGCRYPT_TDIR ]
  then
    rm -rf $LIBGCRYPT_TDIR
  fi

  cd /root
  echo "Extracting $LIBGCRYPT_TDIR.tar.gz"
  tar xf $LIBGCRYPT_TDIR.tar.gz

  if [ ! -f $LIBGCRYPT_TDIR/configure ]
  then
    echo "$LIBGCRYPT_TDIR/configure no such File"
    exit 1
  fi

  cd $LIBGCRYPT_TDIR
  ./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=/usr/include --mandir=/usr/share/man --infodir=/usr/share/info --sysconfdir=/etc --localstatedir=/var --disable-option-checking --disable-silent-rules --libdir=/usr/lib/x86_64-linux-gnu --disable-maintainer-mode --disable-dependency-tracking --enable-noexecstack --enable-ld-version-script --enable-static

    if [ ! -f $LIBGCRYPT_TDIR/Makefile ]
    then
      echo "$LIBGCRYPT_TDIR/Makefile no such File"
      exit 1
    fi

    make
    make install
fi

if [ -f /root/$PKG_VER/src/libcharon/.libs/libcharon.so ]
then
  echo "/root/$PKG_VER already compiled, SKIP"
fi

if [ ! -f /root/$PKG_VER/src/libcharon/.libs/libcharon.so ]
then

  echo "Downloading ($VER)...."

  if [ -d /root/strongswan-$VER ]
  then
    rm -rf /root/strongswan-$VER
  fi

  if [ -f /root/$PKG_VER ]
  then
    rm -f /root/$PKG_VER
  fi

  wget https://download.strongswan.org/$PKG_VER -O /root/$PKG_VER

  if [ ! -f /root/$PKG_VER ]
  then
    echo "Download failed"
    exit 1
  fi

  tar xf /root/$PKG_VER

  if [ ! -f $TDIR/configure ]
  then
    echo "$TDIR/configure no such File"
    exit 1
  fi

  cd $TDIR
  ./configure --build=x86_64-linux-gnu --prefix=/usr --includedir=/usr/include --mandir=/usr/share/man --infodir=/usr/share/info --sysconfdir=/etc --localstatedir=/var --disable-silent-rules --libdir=/usr/lib/x86_64-linux-gnu --runstatedir=/run --disable-dependency-tracking --libdir=/usr/lib --libexecdir=/usr/lib --enable-addrblock --enable-agent --enable-bypass-lan --enable-ccm --enable-certexpire --enable-chapoly --enable-cmd --enable-ctr --enable-curl --enable-eap-aka --enable-eap-gtc --enable-eap-identity --enable-eap-md5 --enable-eap-mschapv2 --enable-eap-radius --enable-eap-tls --enable-eap-tnc --enable-eap-ttls --enable-error-notify --enable-gcm --enable-gcrypt --enable-ha --disable-kernel-libipsec --enable-ldap --enable-led --enable-lookip --enable-mediation --enable-openssl --enable-pkcs11 --enable-test-vectors --enable-tpm --enable-unity --enable-xauth-eap --enable-xauth-pam --disable-blowfish --disable-fast --disable-des --enable-rdrand --enable-aesni --disable-nm --with-nm-ca-dir=/etc/ssl/cer  --with-capabilities=libcap --enable-farp --enable-dhcp --enable-af-alg --enable-connmark --disable-systemd --enable-swanctl --disable-kernel-libipsec --enable-duplicheck --enable-counters

      if [ ! -f $TDIR/Makefile ]
      then
        echo "$TDIR/Makefile no such File"
        exit 1
      fi

  make

  echo "Finish building..........................................\n"

  make install
fi


TTIR="/root/strongswan-builder"

mkdir -p $TTIR/etc/ipsec.d
mkdir -p $TTIR/etc/strongswan.d
mkdir -p $TTIR/etc/swanctl
mkdir -p $TTIR/usr/lib/ipsec
mkdir -p $TTIR/usr/lib/x86_64-linux-gnu
mkdir -p $TTIR/usr/bin
mkdir -p $TTIR/usr/sbin

cp -rfvd /usr/lib/x86_64-linux-gnu/libgpg-error.* $TTIR/usr/lib/x86_64-linux-gnu/
cp -rfvd /usr/lib/x86_64-linux-gnu/libgcrypt.* $TTIR/usr/lib/x86_64-linux-gnu/
cp -rfvd /usr/lib/ipsec/* $TTIR/usr/lib/ipsec/
cp -rfvd /etc/swanctl/* $TTIR/etc/swanctl/
cp -rfvd /etc/ipsec.d/* $TTIR/etc/ipsec.d/
cp -rfvd /etc/strongswan.d/* $TTIR/etc/strongswan.d/

cp -fd /usr/bin/gpgrt-config $TTIR/usr/bin/
cp -fd /usr/bin/yat2m $TTIR/usr/bin/
cp -fd /usr/bin/dumpsexp $TTIR/usr/bin/
cp -fd /usr/bin/hmac256 $TTIR/usr/bin/
cp -fd /usr/bin/mpicalc $TTIR/usr/bin/
cp -fd /usr/bin/libgcrypt-config $TTIR/usr/bin/
cp -fd /usr/sbin/swanctl $TTIR/usr/sbin/
cp -fd /usr/sbin/ipsec $TTIR/usr/sbin/
cp -fd /usr/sbin/charon-cmd $TTIR/usr/sbin/
cp -fd /usr/bin/pt-tls-client  $TTIR/usr/bin/
cp -fd /usr/bin/pki  $TTIR/usr/bin/
cp -fd /usr/bin/tpm_extendpcr $TTIR/usr/bin/

echo "Building package"

cd $TTIR
tar czf /root/strongswan-builder-$VER.tar.gz *

cd /root
rm -rf $TTIR

echo "Done... /root/strongswan-builder-$VER.tar.gz"



