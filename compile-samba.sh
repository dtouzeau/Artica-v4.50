#!/usr/bin/env bash
# apt-get install libcmocka-dev libpopt-dev libparse-yapp-perl libjansson-dev libarchive-dev libacl1-dev libpam0g-dev python3-markdown python3-dnspython python-etcd librados-dev libtasn1-bin
# distro: libgpgme11 python3-markdown python3-dnspython python3-etcd librados2 libcephfs-dev

# wget https://www.samba.org/ftp/tdb/tdb-1.4.12.tar.gz
# cd tdb-1.4.12 && ./configure && make && make install

# wget https://www.samba.org/ftp/talloc/talloc-2.4.2.tar.gz
# wget https://www.samba.org/ftp/tevent/tevent-0.16.1.tar.gz
# wget https://www.samba.org/ftp/ldb/ldb-2.9.1.tar.gz


# wss2
# cd /root && rm -rf wsdd2 && git clone https://github.com/Netgear/wsdd2.git && cd wsdd2 && make && strip -s wsdd2 && cp wsdd2 /usr/local/sbin/wsdd2

# QUIC
# git clone https://github.com/lxin/quic.git
# cd quic && ./autogen.sh & ./configure && make && make install

# GLUSTERFS:
# ---------- GPerfTools
# wget https://github.com/gperftools/gperftools/releases/download/gperftools-2.17.2/gperftools-2.17.2.tar.gz && tar -xf gperftools-2.17.2.tar.gz && cd gperftools-2.17.2 && ./configure --prefix=/usr/local --with-tcmalloc-pagesize=64 && make -j"$(nproc)" && make install
# cd /root && rm -rf liburing && git clone https://github.com/axboe/liburing.git && ./configure --prefix=/usr/local && make -j"$(nproc)"
# cd /root & rm -rf glusterfs && git clone https://github.com/gluster/glusterfs.git && cd /root/glusterfs && ./autogen.sh && ./configure --prefix=/usr/local --disable-linux-io_uring && make -j"$(nproc)"

# https://download.samba.org/pub/samba/stable/samba-4.20.4.tar.gz
# ./configure --prefix=/usr --enable-fhs --sysconfdir=/etc --localstatedir=/var --libexecdir=/usr/libexec --libdir=/usr/lib/x86_64-linux-gnu --datadir=/usr/share --with-modulesdir=/usr/lib/x86_64-linux-gnu/samba --with-pammodulesdir=/lib/x86_64-linux-gnu/security --with-privatedir=/var/lib/samba/private --with-smbpasswd-file=/etc/samba/smbpasswd --with-piddir=/run/samba --with-lockdir=/run/samba --with-sockets-dir=/run/samba --with-statedir=/var/lib/samba --with-cachedir=/var/cache/samba --with-pam --with-syslog --with-utmp --with-winbind --with-automount --with-ldap --with-ads --with-gpgme --enable-avahi --enable-spotlight --with-profiling-data --disable-rpath --disable-rpath-install --bundled-libraries=NONE,pytevent,ldb --with-cluster-support --enable-etcd-reclock --with-socketpath=/run/ctdb/ctdbd.socket --with-logdir=/var/log/ctdb  --with-shared-modules=vfs_dfs_samba4,vfs_nfs4acl_xattr,auth_samba4 --with-quota --without-systemd --enable-cephfs --enable-ceph-reclock --with-cluster-support  --bundled-libraries=ALL
SRCDIST="/usr/local/lib/python3/dist-packages"
COMPILDIR="/root/samba-compile"
DESDIST="$COMPILDIR/usr/lib/python3/dist-packages"
PKGLIB="/usr/lib/x86_64-linux-gnu/pkgconfig"

echo "Remove wordir $COMPILDIR"
rm -rf $COMPILDIR
mkdir -p "$COMPILDIR/usr/local/lib/ldb"
mkdir -p "$COMPILDIR/usr/local/bin"
mkdir -p "$COMPILDIR/usr/local/sbin"
mkdir -p "$COMPILDIR/usr/bin"
mkdir -p "$COMPILDIR/usr/sbin"
mkdir -p "$COMPILDIR/lib/pkgconfig"
mkdir -p "$COMPILDIR/usr/lib/python3/dist-packages"
mkdir -p "$COMPILDIR/usr/libexec/ctdb"
mkdir -p "$COMPILDIR/usr/libexec/samba"
mkdir -p "$COMPILDIR/usr/share"
mkdir -p "$COMPILDIR/etc/ctdb"
mkdir -p "$COMPILDIR/etc/sudoers.d"
mkdir -p "$COMPILDIR/$PKGLIB"
mkdir -p "$COMPILDIR/usr/lib/python3/dist-packages/samba"
mkdir -p "$COMPILDIR/usr/lib/x86_64-linux-gnu/samba"
mkdir -p "$COMPILDIR/usr/lib/x86_64-linux-gnu/security"
mkdir -p "$COMPILDIR/usr/lib/x86_64-linux-gnu/samba"
mkdir -p "$COMPILDIR/usr/share/samba"
mkdir -p "$COMPILDIR/usr/local/modules/ldb"
mkdir -p "$COMPILDIR/usr/share/ctdb"
mkdir -p "$COMPILDIR/usr/local/lib"
mkdir -p "$COMPILDIR/usr/local/lib/glusterfs"
mkdir -p "$COMPILDIR/usr/local/libexec/glusterfs"

strip -s /usr/local/bin/fusermount-glusterfs
strip -s /usr/local/bin/glusterfind

echo "Copy source files"
cp -ar /usr/local/modules/ldb/* $COMPILDIR/usr/local/modules/ldb/
cp -ar /etc/ctdb/* $COMPILDIR/etc/ctdb/
cp -ar /usr/share/ctdb $COMPILDIR/usr/share/
cp -a /usr/libexec/ctdb/* $COMPILDIR/usr/libexec/ctdb/
cp -ar /usr/libexec/samba/* $COMPILDIR/usr/libexec/samba/
cp -ar /usr/local/libexec/glusterfs/* $COMPILDIR/usr/local/libexec/glusterfs/
cp -ar /usr/local/lib/libquic* $COMPILDIR/usr/local/lib/
cp -ar /usr/local/lib/libtcmalloc* $COMPILDIR/usr/local/lib/
cp -ar /usr/local/lib/libprofiler* $COMPILDIR/usr/local/lib/
cp -ar /usr/local/lib/liburing* $COMPILDIR/usr/local/lib/
cp -ar /usr/local/lib/libglusterfs* $COMPILDIR/usr/local/lib/
cp -ar /usr/local/lib/glusterfs/* $COMPILDIR/usr/local/lib/glusterfs/
cp -a /usr/local/sbin/conf.py $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/gcron.py $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/gf_attach $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/gfind_missing_files $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/gluster $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/glusterd $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/gluster-eventsapi $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/glustereventsd $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/glusterfs $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/glusterfsd $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/gluster-georep-sshkey $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/gluster-mountbroker $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/gluster-setgfid2path $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/snap_scheduler.py $COMPILDIR/usr/local/sbin/
cp -a /usr/local/sbin/wsdd2 $COMPILDIR/usr/local/sbin/
cp -a /usr/bin/onnode $COMPILDIR/usr/bin/
cp -a /usr/sbin/samba_downgrade_db $COMPILDIR/usr/sbin/
cp -a /usr/sbin/samba_dnsupdate $COMPILDIR/usr/sbin/
cp -a /usr/sbin/samba_spnupdate $COMPILDIR/usr/sbin/
cp -a /usr/sbin/samba_upgradedns $COMPILDIR/usr/sbin/
cp -a /usr/sbin/samba_kcc $COMPILDIR/usr/sbin/
cp -a /usr/sbin/samba-gpupdate $COMPILDIR/usr/sbin/
cp -a /usr/bin/samba-tool $COMPILDIR/usr/bin/
cp -a /usr/bin/mdsearch  $COMPILDIR/usr/bin/
cp -ar /usr/share/samba/* $COMPILDIR/usr/share/samba/
cp -a /usr/lib/x86_64-linux-gnu/pkgconfig/samba* $COMPILDIR/usr/lib/x86_64-linux-gnu/pkgconfig/
cp -ar /usr/lib/x86_64-linux-gnu/samba/* $COMPILDIR/usr/lib/x86_64-linux-gnu/samba/
cp -a /usr/lib/x86_64-linux-gnu/libsamba* $COMPILDIR/usr/lib/x86_64-linux-gnu/
cp -a /usr/lib/x86_64-linux-gnu/libndr* $COMPILDIR/usr/lib/x86_64-linux-gnu/
cp -a /usr/lib/x86_64-linux-gnu/libdcerpc* $COMPILDIR/usr/lib/x86_64-linux-gnu/
cp -a /usr/lib/x86_64-linux-gnu/libsamdb* $COMPILDIR/usr/lib/x86_64-linux-gnu/
cp -a /usr/lib/x86_64-linux-gnu/libsmb* $COMPILDIR/usr/lib/x86_64-linux-gnu/
cp -a /usr/lib/x86_64-linux-gnu/libnetapi* $COMPILDIR/usr/lib/x86_64-linux-gnu/
cp -a /usr/lib/x86_64-linux-gnu/libwbclient* $COMPILDIR/usr/lib/x86_64-linux-gnu/
cp -a /usr/lib/x86_64-linux-gnu/libnss_winbind* $COMPILDIR/usr/lib/x86_64-linux-gnu/
cp -a /usr/lib/x86_64-linux-gnu/libnss_wins* $COMPILDIR/usr/lib/x86_64-linux-gnu/
cp -a /usr/lib/x86_64-linux-gnu/libtevent-util* $COMPILDIR/usr/lib/x86_64-linux-gnu/
cp -a /usr/lib/x86_64-linux-gnu/security/pam_winbind* $COMPILDIR/usr/lib/x86_64-linux-gnu/security/
cp -ar /usr/lib/python3/dist-packages/samba/* $COMPILDIR/usr/lib/python3/dist-packages/samba/
cp -a /usr/bin/ctdb_diagnostics $COMPILDIR/usr/bin/
cp -a /usr/local/lib/ldb/* $COMPILDIR/usr/local/lib/ldb/
cp -a /usr/local/lib/libldb*  $COMPILDIR/usr/local/lib/
cp -a /usr/local/lib/libtevent* $COMPILDIR/usr/local/lib/
cp -a /usr/local/lib/libtalloc* $COMPILDIR/usr/local/lib/
cp -a /usr/local/lib/libtdb* $COMPILDIR/usr/local/lib/
cp -a /usr/local/lib/libgf* $COMPILDIR/usr/local/lib/
cp -a /usr/local/bin/tdbbackup $COMPILDIR/usr/local/bin/
cp -a /usr/local/bin/tdbrestore $COMPILDIR/usr/local/bin/
cp -a /usr/local/bin/tdbdump $COMPILDIR/usr/local/bin/
cp -a /usr/local/bin/ldbadd $COMPILDIR/usr/local/bin/
cp -a /usr/local/bin/ldbsearch $COMPILDIR/usr/local/bin/
cp -a /usr/local/bin/ldbdel $COMPILDIR/usr/local/bin/
cp -a /usr/local/bin/ldbmodify $COMPILDIR/usr/local/bin/
cp -a /usr/local/bin/ldbedit $COMPILDIR/usr/local/bin/
cp -a /usr/local/bin/ldbrename $COMPILDIR/usr/local/bin/
cp -a /usr/local/bin/fusermount-glusterfs $COMPILDIR/usr/local/bin/
cp -a /usr/local/bin/glusterfind $COMPILDIR/usr/local/bin/
cp -a /usr/lib/python3/dist-packages/tevent.py $DESDIST/
cp -a $SRCDIST/_tdb_text.py $DESDIST/
cp -a $SRCDIST/tevent.py $DESDIST/
cp -a $SRCDIST/ldb.cpython-311-x86_64-linux-gnu.* $DESDIST/
cp -a $SRCDIST/tdb.cpython-311-x86_64-linux-gnu.so $DESDIST/
cp -a $SRCDIST/talloc.cpython-311-x86_64-linux-gnu.so $DESDIST/
cp -a $SRCDIST/_tevent.cpython-311-x86_64-linux-gnu.so $DESDIST/
cp -a $SRCDIST/_ldb_text.py $DESDIST/
cp -a /usr/lib/python3/dist-packages/_tevent.cpython-311-x86_64-linux-gnu.so $DESDIST/
cp -a /usr/local/lib/pkgconfig/pyldb-util.cpython-311-x86_64-linux-gnu.pc $COMPILDIR/$PKGLIB/
cp -a /usr/local/lib/pkgconfig/ldb.pc $COMPILDIR/$PKGLIB/
cp -a /usr/lib/x86_64-linux-gnu/pkgconfig/ndr.pc $COMPILDIR/$PKGLIB/
cp -a /usr/lib/x86_64-linux-gnu/pkgconfig/dcerpc_samr.pc $COMPILDIR/$PKGLIB/
cp -a /usr/lib/x86_64-linux-gnu/pkgconfig/ndr_nbt.pc $COMPILDIR/$PKGLIB/
cp -a /usr/lib/x86_64-linux-gnu/pkgconfig/netapi.pc $COMPILDIR/$PKGLIB/
cp -a /usr/lib/x86_64-linux-gnu/pkgconfig/samdb.pc $COMPILDIR/$PKGLIB/
cp -a /usr/lib/x86_64-linux-gnu/pkgconfig/smbclient.pc $COMPILDIR/$PKGLIB/
cp -a /usr/lib/x86_64-linux-gnu/pkgconfig/wbclient.pc $COMPILDIR/$PKGLIB/
cp -a /usr/lib/x86_64-linux-gnu/pkgconfig/ndr_standard.pc $COMPILDIR/$PKGLIB/
cp -a /usr/lib/x86_64-linux-gnu/pkgconfig/dcerpc_server.pc $COMPILDIR/$PKGLIB/
cp -a /usr/lib/x86_64-linux-gnu/pkgconfig/dcerpc.pc $COMPILDIR/$PKGLIB/
cp -a /usr/lib/x86_64-linux-gnu/pkgconfig/ndr_krb5pac.pc $COMPILDIR/$PKGLIB/
cp -a /usr/bin/smbcontrol $COMPILDIR/usr/bin/smbcontrol
cp -a /usr/bin/locktest $COMPILDIR/usr/bin/locktest
cp -a /usr/sbin/ctdbd $COMPILDIR/usr/sbin/ctdbd
cp -a /usr/bin/regtree $COMPILDIR/usr/bin/regtree
cp -a /usr/bin/onnode $COMPILDIR/usr/bin/onnode
cp -a /usr/bin/regshell $COMPILDIR/usr/bin/regshell
cp -a /usr/bin/ldbdel $COMPILDIR/usr/bin/ldbdel
cp -a /usr/sbin/nmbd $COMPILDIR/usr/sbin/nmbd
cp -a /usr/bin/pdbedit $COMPILDIR/usr/bin/pdbedit
cp -a /usr/bin/profiles $COMPILDIR/usr/bin/profiles
cp -a /usr/bin/ping_pong $COMPILDIR/usr/bin/ping_pong
cp -a /usr/bin/smbtree $COMPILDIR/usr/bin/smbtree
cp -a /usr/bin/ctdb_diagnostics $COMPILDIR/usr/bin/ctdb_diagnostics
cp -a /usr/bin/ndrdump $COMPILDIR/usr/bin/ndrdump
cp -a /etc/sudoers.d/ctdb $COMPILDIR/etc/sudoers.d/
cp -a /usr/bin/wbinfo $COMPILDIR/usr/bin/wbinfo
cp -a /usr/bin/samba-regedit $COMPILDIR/usr/bin/samba-regedit
cp -a /usr/bin/smbpasswd $COMPILDIR/usr/bin/smbpasswd
cp -a /usr/sbin/samba_spnupdate $COMPILDIR/usr/sbin/samba_spnupdate
cp -a /usr/bin/samba-log-parser $COMPILDIR/usr/bin/samba-log-parser
cp -a /usr/bin/wspsearch $COMPILDIR/usr/bin/wspsearch
cp -a /usr/bin/smbspool $COMPILDIR/usr/bin/smbspool
cp -a /usr/bin/smbtorture $COMPILDIR/usr/bin/smbtorture
cp -a /usr/bin/smbget $COMPILDIR/usr/bin/smbget
cp -a /usr/bin/testparm $COMPILDIR/usr/bin/testparm
cp -a /usr/bin/sharesec $COMPILDIR/usr/bin/sharesec
cp -a /usr/bin/masktest $COMPILDIR/usr/bin/masktest
cp -a /usr/bin/smbcacls $COMPILDIR/usr/bin/smbcacls
cp -a /usr/bin/dumpmscat $COMPILDIR/usr/bin/dumpmscat
cp -a /usr/bin/ltdbtool $COMPILDIR/usr/bin/ltdbtool
cp -a /usr/bin/net $COMPILDIR/usr/bin/net
cp -a /usr/bin/ldbrename $COMPILDIR/usr/bin/ldbrename
cp -a /usr/sbin/winbindd $COMPILDIR/usr/sbin/winbindd
cp -a /usr/bin/ctdb $COMPILDIR/usr/bin/ctdb
cp -a /usr/bin/cifsdd $COMPILDIR/usr/bin/cifsdd
cp -a /usr/sbin/eventlogadm $COMPILDIR/usr/sbin/eventlogadm
cp -a /usr/sbin/samba $COMPILDIR/usr/sbin/samba
cp -a /usr/bin/mvxattr $COMPILDIR/usr/bin/mvxattr
cp -a /usr/bin/rpcclient $COMPILDIR/usr/bin/rpcclient
cp -a /usr/sbin/samba_dnsupdate $COMPILDIR/usr/sbin/samba_dnsupdate
cp -a /usr/bin/samba-tool $COMPILDIR/usr/bin/samba-tool
cp -a /usr/bin/smbclient $COMPILDIR/usr/bin/smbclient
cp -a /usr/bin/regpatch $COMPILDIR/usr/bin/regpatch
cp -a /usr/sbin/samba_upgradedns $COMPILDIR/usr/sbin/samba_upgradedns
cp -a /usr/bin/smbcquotas $COMPILDIR/usr/bin/smbcquotas
cp -a /usr/bin/smbstatus $COMPILDIR/usr/bin/smbstatus
cp -a /usr/bin/dbwrap_tool $COMPILDIR/usr/bin/dbwrap_tool
cp -a /usr/sbin/smbd $COMPILDIR/usr/sbin/smbd
cp -a /usr/bin/nmblookup $COMPILDIR/usr/bin/nmblookup
cp -a /usr/bin/regdiff $COMPILDIR/usr/bin/regdiff
cp -a /usr/sbin/samba_kcc $COMPILDIR/usr/sbin/samba_kcc
cp -a /usr/sbin/samba-gpupdate $COMPILDIR/usr/sbin/samba-gpupdate
cp -a /usr/bin/ldbedit $COMPILDIR/usr/bin/ldbedit
cp -a /usr/bin/ntlm_auth $COMPILDIR/usr/bin/ntlm_auth
cp -a /usr/bin/ldbsearch $COMPILDIR/usr/bin/ldbsearch
cp -a /usr/bin/ldbadd $COMPILDIR/usr/bin/ldbadd
cp -a /usr/bin/smbtar $COMPILDIR/usr/bin/smbtar
cp -a /usr/bin/ldbmodify $COMPILDIR/usr/bin/ldbmodify
cp -a /usr/bin/oLschema2ldif $COMPILDIR/usr/bin/oLschema2ldif
cp -a /usr/sbin/samba_downgrade_db $COMPILDIR/usr/sbin/samba_downgrade_db
cp -a /usr/bin/gentest $COMPILDIR/usr/bin/gentest

find $COMPILDIR -name '*.la' -type f -delete
find $COMPILDIR -name '*.a' -type f -delete



VERSION=`/usr/sbin/smbd -V | grep -oP '(?<=Version )\d+\.\d+\.\d+'`
DESTFILE="/root/samba-$VERSION-debian12.tar.gz"

cd $COMPILDIR || true
echo "Compressing $DESTFILE"
tar -czf $DESTFILE *
echo "Done..."
