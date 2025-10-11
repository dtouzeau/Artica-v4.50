#!/bin/bash
# Wget https://www.openvswitch.org/releases/openvswitch-3.6.0.tar.gz
# ./configure && make && make install
# wget https://github.com/firecracker-microvm/firecracker/releases/download/v1.12.1/firecracker-v1.12.1-x86_64.tgz

# Create the vmlinux
# sudo apt-get install -y build-essential bc bison flex libssl-dev libelf-dev dwarves curl
# git clone --depth=1 --branch v6.1 https://git.kernel.org/pub/scm/linux/kernel/git/stable/linux.git
# cd linux
# curl -LO https://raw.githubusercontent.com/firecracker-microvm/firecracker/main/resources/guest_configs/microvm-kernel-x86_64-6.1.config
# mv microvm-kernel-x86_64-6.1.config .config
# yes "" | make olddefconfig
# make -j"$(nproc)" vmlinux
# cp vmlinux vmlinux.bin
# strip -s vmlinux.bin || true

ARCH="$(uname -m)"
release_url="https://github.com/firecracker-microvm/firecracker/releases"
latest=$(basename $(curl -fsSLI -o /dev/null -w  %{url_effective} ${release_url}/latest))
echo "Latest: $latest"
CompileDir="/root/firecracker/$latest"
mkdir -p $CompileDir
# shellcheck disable=SC2082
TargetFile="firecracker-${latest}-${ARCH}.tgz"
Targeturi="${release_url}/download/${latest}/$TargetFile"
echo "Download $Targeturi"
echo "To: $CompileDir/$TargetFile"
wget $Targeturi -O $CompileDir/$TargetFile
if [ ! -f "$CompileDir/$TargetFile" ]; then
  echo "$CompileDir/$TargetFile no such file, download failed"
  rm -rf $CompileDir
  exit 1
fi

echo "Extracting $TargetFile"
ExtractedDir="$CompileDir/release-${latest}-$ARCH"
tar -xf $CompileDir/$TargetFile -C $CompileDir
rm -f $TargetFile
if [ ! -d $ExtractedDir ]; then
  echo "$ExtractedDir no such Directory"
 # rm -rf $CompileDir
  exit 1
fi

mkdir -p "$CompileDir/usr/sbin"
mkdir -p "$CompileDir/usr/local/share/openvswitch"
mkdir -p "$CompileDir/usr/local/etc/openvswitch"
mkdir -p "$CompileDir/usr/local/bin"
mkdir -p "$CompileDir/usr/local/sbin"
BINARYMAPS=("cpu-template-helper"  "firecracker_spec"  "firecracker"  "jailer"  "rebase-snap"  "seccompiler-bin"  "snapshot-editor")

for mainbin in "${BINARYMAPS[@]}"
do
  SrcBin="$ExtractedDir/$mainbin-$latest-$ARCH"
  if [ ! -f $Srcbin ]; then
    echo "$SrcBin no such binary, aborting"
    exit1
  fi
  echo "Move $SrcBin $CompileDir/..."
  mv $SrcBin $CompileDir/usr/sbin/$mainbin
done

if [ ! -f /root/linux/vmlinux.bin ]; then
  echo "/root/linux/vmlinux.bin no such file"
  exit 1
fi
mkdir -p $CompileDir/var/lib/firecracker
cp /root/linux/vmlinux.bin $CompileDir/var/lib/firecracker/
rm -rf $ExtractedDir


cd /root

DirTargets=("/usr/local/share/openvswitch" "/usr/local/etc/openvswitch")

Targets=( "/usr/local/bin/ovs-appctl" "/usr/local/bin/ovsdb-client" "/usr/local/bin/ovsdb-tool" "/usr/local/bin/ovs-docker" "/usr/local/bin/ovs-dpctl" "/usr/local/bin/ovs-dpctl-top" "/usr/local/bin/ovs-l3ping" "/usr/local/bin/ovs-ofctl" "/usr/local/bin/ovs-parse-backtrace" "/usr/local/bin/ovs-pcap" "/usr/local/bin/ovs-pki" "/usr/local/bin/ovs-tcpdump" "/usr/local/bin/ovs-tcpundump" "/usr/local/bin/ovs-test" "/usr/local/bin/ovs-testcontroller" "/usr/local/bin/ovs-vlan-test" "/usr/local/bin/ovs-vsctl" "/usr/local/bin/vtep-ctl" "/usr/local/sbin/ovs-bugtool" "/usr/local/sbin/ovsdb-server" "/usr/local/sbin/ovs-vswitchd")


for tfile in "${DirTargets[@]}"; do
  cp -rfv $tfile/* $CompileDir$tfile/
done

for tfile in "${Targets[@]}"; do
  cp -f $tfile $CompileDir$tfile
done

echo "Creating the Package"
cd $CompileDir || exit
tar -czvf /root/firecracker$latest.tar.gz *
echo "/root/firecracker$latest.tar.gz done..."