#!/bin/bash
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

if [ ! -f /var/lib/firecracker/vmlinux.bin ]; then
  echo "Download vmlinux.bin"
  curl -Lo /var/lib/firecracker/vmlinux.bin https://s3.amazonaws.com/spec.ccfc.min/img/quickstart_guide/x86_64/kernels/vmlinux.bin
fi
mkdir -p $CompileDir/var/lib/firecracker
cp /var/lib/firecracker/vmlinux.bin $CompileDir/var/lib/firecracker/
rm -rf $ExtractedDir


echo "Creating the Package"
cd $CompileDir || exit
tar -czvf /root/firecracker$latest.tar.gz *
echo "/root/firecracker$latest.tar.gz done..."
