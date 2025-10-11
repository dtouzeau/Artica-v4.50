#!/bin/bash

current_user=$USER
srPATH="/home/dtouzeau/Téléchargements"
WORKDIR="$srPATH/docker"
EXTRACTED_WORK="$WORKDIR/extracted"
COMPILE_DIR="$WORKDIR/compile"
ARCH="amd64"
SUFFIX_URL="https://download.docker.com/linux/debian/dists/buster/pool/stable/amd64"

DEBIANVER=10
mainver="24.0.7-1"
dockerbluidxver="0.11.2-1"
dockercomposever="2.21.0-1"
containerdver="1.6.24-1"
kubernetver="v1.28.3"
# See https://github.com/kubernetes-sigs/cri-tools
crictlversion="v1.28.0"
kubeuri="https://dl.k8s.io/$kubernetver/bin/linux/amd64"
helmversion="3.13.1"
etccdver="3.5.10"

dockerce="docker-ce_$mainver~debian.$DEBIANVER~buster_$ARCH.deb"
dockercli="docker-ce-cli_$mainver~debian.$DEBIANVER~buster_$ARCH.deb"
dockerbluidx="docker-buildx-plugin_$dockerbluidxver~debian.$DEBIANVER~buster_$ARCH.deb"
dockercompose="docker-compose-plugin_$dockercomposever~debian.$DEBIANVER~buster_$ARCH.deb"
containerd="containerd.io_${containerdver}_${ARCH}.deb"


if [ ! -d $WORKDIR ]; then
  mkdir -p "$WORKDIR"
fi
if [ ! -d $EXTRACTED_WORK ]; then
  mkdir -p "$EXTRACTED_WORK"
fi
if [ ! -d $COMPILE_DIR ]; then
  mkdir -p "$COMPILE_DIR"
fi

# shellcheck disable=SC2115
if [ -d $COMPILE_DIR ]; then
  echo "Removing $COMPILE_DIR/*"
  rm -rf $COMPILE_DIR/*
fi

ALL=("$dockerce" "$dockercli" "$dockerbluidx" "$dockercompose" "$containerd")

cd "$WORKDIR" || exit

# shellcheck disable=SC2068
for tfile in ${ALL[@]}; do

  if [ -f "$WORKDIR/$tfile" ]; then
    echo "Removing $WORKDIR/$tfile"
    rm -f "$WORKDIR/$tfile"
  fi

  echo "Downloading $tfile"
  wget "$SUFFIX_URL/$tfile" -O "$WORKDIR/$tfile" >/dev/null 2>&1

  if [ ! -f "$WORKDIR/$tfile" ]; then
    echo "Downloading $tfile failed"
    exit 1
  fi

  echo "Extracting $WORKDIR/$tfile to $EXTRACTED_WORK"
  ar -x "$WORKDIR/$tfile" --output="$EXTRACTED_WORK/"

  if [ ! -f "$EXTRACTED_WORK/data.tar.xz" ]; then
    echo "$EXTRACTED_WORK/data.tar.xz no such file"
    exit 1
  fi

  echo "Extracting $EXTRACTED_WORK/data.tar.xz"
  /bin/tar xf "$EXTRACTED_WORK/data.tar.xz" -C "$COMPILE_DIR/"

  DEL=("$EXTRACTED_WORK/data.tar.xz" "$EXTRACTED_WORK/control.tar.xz" "$EXTRACTED_WORK/control.tar.gz" "$EXTRACTED_WORK/debian-binary" "$WORKDIR/$tfile")
  for zfile in ${DEL[@]}; do
     if [ -f "$zfile" ]; then
       echo "Remove unecessary $zfile"
       rm -f "$zfile"
     fi
  done



done

KUBES=("apiextensions-apiserver" "kube-aggregator" "kube-apiserver" "kube-controller-manager" "kube-log-runner" "kube-proxy" "kube-scheduler" "kubeadm" "kubelet" "mounter" "kubectl")

echo "Get Kubernetes"

# shellcheck disable=SC2068
for tfile in ${KUBES[@]}; do
  echo "Download $tfile in$COMPILE_DIR/usr/bin/$tfile"
  wget "$kubeuri/$tfile" -O $COMPILE_DIR/usr/bin/$tfile
  chmod 0755 $COMPILE_DIR/usr/bin/$tfile
done

echo "Get crictl"
wget https://github.com/kubernetes-sigs/cri-tools/releases/download/$crictlversion/crictl-$crictlversion-linux-amd64.tar.gz -O $COMPILE_DIR/crictl-$crictlversion-linux-amd64.tar.gz
tar zxvf $COMPILE_DIR/crictl-$crictlversion-linux-amd64.tar.gz -C $COMPILE_DIR/usr/bin/
rm -f $COMPILE_DIR/crictl-$crictlversion-linux-amd64.tar.gz

echo "Get critest"

wget https://github.com/kubernetes-sigs/cri-tools/releases/download/$crictlversion/critest-$crictlversion-linux-amd64.tar.gz -O $COMPILE_DIR/critest-$crictlversion-linux-amd64.tar.gz
tar zxvf $COMPILE_DIR/critest-$crictlversion-linux-amd64.tar.gz -C $COMPILE_DIR/usr/bin/
rm -f $COMPILE_DIR/critest-$crictlversion-linux-amd64.tar.gz

echo "Get Helm"
mkdir -p $COMPILE_DIR/helm
wget https://get.helm.sh/helm-v$helmversion-linux-amd64.tar.gz -O $COMPILE_DIR/helm/$helmversion.tar.gz
if [ ! -f $COMPILE_DIR/helm/$helmversion.tar.gz ]; then
  echo "$COMPILE_DIR/helm/$helmversion.tar.gz no such file"
  exit 1
fi
echo "Extracting Helm"
tar -xf $COMPILE_DIR/helm/$helmversion.tar.gz -C $COMPILE_DIR/helm/
if [ ! -f $COMPILE_DIR/helm/linux-amd64/helm ]; then
   echo "$COMPILE_DIR/helm/linux-amd64/helm no such file"
   exit 1
fi
echo "Copy Helm"
cp -f "$COMPILE_DIR/helm/linux-amd64/helm" $COMPILE_DIR/usr/bin/helm


echo "Get etcd"
mkdir -p $COMPILE_DIR/etcd
wget https://github.com/etcd-io/etcd/releases/download/v$etccdver/etcd-v$etccdver-linux-amd64.tar.gz -O $COMPILE_DIR/etcd/etcd-v$etccdver-linux-amd64.tar.gz

echo "Extracting etcd"
tar -xf $COMPILE_DIR/etcd/etcd-v$etccdver-linux-amd64.tar.gz -C $COMPILE_DIR/etcd/ --strip-components=1

if [ ! -f $COMPILE_DIR/etcd/etcd ]; then
   echo "$COMPILE_DIR/etcd/etcd no such file"
   exit 1
fi
echo "Copy etcd"
cp -f "$COMPILE_DIR/etcd/etcd" $COMPILE_DIR/usr/bin/etcd
echo "Cleaning $COMPILE_DIR/etcd"




echo "Remove temporary files (helm)"
rm -rf $COMPILE_DIR/helm
echo "Remove temporary files (etcd)"
rm -rf $COMPILE_DIR/etcd

DirToremove=("/usr/share/man" "/usr/share/doc" "/lib/systemd")
# shellcheck disable=SC2068
for zdir in ${DirToremove[@]}; do
     FFDIR="$COMPILE_DIR$zdir"
     if [ ! -d "$FFDIR" ]; then
       echo "$FFDIR no such directory"
       continue
     fi
     if [ -d "$FFDIR" ]; then
       echo "Remove unecessary directory $FFDIR"
       rm -rf "$FFDIR"
     fi
  done

echo "Compressing $WORKDIR/docker-x.x.x.tar.gz"
cd $COMPILE_DIR || exit
tar -czvf $WORKDIR/docker-x.x.x.tar.gz *
echo "Compressing $WORKDIR/docker-x.x.x.tar.gz [DONE]"
cd $WORKDIR || true
# shellcheck disable=SC2086
chown -R $current_user:$current_user $WORKDIR





