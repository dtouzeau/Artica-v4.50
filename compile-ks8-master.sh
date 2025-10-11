#!/usr/bin/env sh
# Video https://www.youtube.com/watch?v=5Rf1TnuOQXQ
# Configure etcd https://github.com/mmumshad/kubernetes-the-hard-way/blob/master/docs/07-bootstrapping-etcd.md
# Create certificates : https://github.com/Albertchong/Kubernetes-Tutorials/blob/master/Kubernetes%20Tutorials%2002%20-%20Generate%20Certificate%20for%20ETCD.md
ETCD_VER=v3.5.11
MASTERDIR="/home/dtouzeau/ks8-master"
WORKDIR="$MASTERDIR/etcd-download-test"
COMPILEDIR="$MASTERDIR/Install"

# choose either URL
GOOGLE_URL=https://storage.googleapis.com/etcd
GITHUB_URL=https://github.com/etcd-io/etcd/releases/download
DOWNLOAD_URL=${GITHUB_URL}

rm -f /$MASTERDIR/etcd-${ETCD_VER}-linux-amd64.tar.gz
rm -rf $WORKDIR && mkdir -p $WORKDIR
rm -rf $COMPILEDIR && mkdir -p $COMPILEDIR/usr/sbin

mkdir -p $COMPILEDIR/etc/etcd
mkdir -p $COMPILEDIR/var/lib/etcd
mkdir -p $COMPILEDIR/var/lib/kubernetes/pki

echo "Downloading ETCD $ETCD_VER"
curl -L ${DOWNLOAD_URL}/${ETCD_VER}/etcd-${ETCD_VER}-linux-amd64.tar.gz -o /$MASTERDIR/etcd-${ETCD_VER}-linux-amd64.tar.gz
tar xzvf /$MASTERDIR/etcd-${ETCD_VER}-linux-amd64.tar.gz -C $WORKDIR --strip-components=1
rm -f /$MASTERDIR/etcd-${ETCD_VER}-linux-amd64.tar.gz

echo "Download certificates tools"
curl -s -L -o $COMPILEDIR/usr/sbin/cfssl https://pkg.cfssl.org/R1.2/cfssl_linux-amd64
curl -s -L -o $COMPILEDIR/usr/sbin/cfssljson https://pkg.cfssl.org/R1.2/cfssljson_linux-amd64
chmod +x  $COMPILEDIR/usr/sbin/{cfssl,cfssljson}

echo "Download command line utility used to interact with the Kubernetes API Server (kubectl)"

curl -LO "https://dl.k8s.io/release/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl"
chmod +x kubectl
mv kubectl $COMPILEDIR/usr/sbin/

cp $WORKDIR/etcd $COMPILEDIR/usr/sbin/
cp $WORKDIR/etcdctl $COMPILEDIR/usr/sbin/
cp $WORKDIR/etcdutl $COMPILEDIR/usr/sbin/

