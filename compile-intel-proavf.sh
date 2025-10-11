#!/usr/bin/env bash

# The iavf driver supports devices based on the following controllers:
#  * Intel(R) Ethernet Controller E810-C
#  * Intel(R) Ethernet Controller E810-XXV
#  * Intel(R) Ethernet Connection E822-C
#  * Intel(R) Ethernet Connection E822-L
#  * Intel(R) Ethernet Connection E823-C
#  * Intel(R) Ethernet Connection E823-L
#  * Intel(R) Ethernet Controller I710
#  * Intel(R) Ethernet Controller X710
#  * Intel(R) Ethernet Controller XL710
#  * Intel(R) Ethernet Network Connection X722
#  * Intel(R) Ethernet Controller XXV710
#  * Intel(R) Ethernet Controller V710

BaseRoot="/root/proavf"
libm="lib/modules"
inteldir="updates/drivers/net/ethernet/intel"
Kernels=("4.19.0-26-amd64" "4.19.0-23-amd64" "4.19.0-24-amd64" "4.19.0-25-amd64")

for element in "${Kernels[@]}"; do
  echo "Processing: $element"
  mkdir -p "$BaseRoot/$libm/$element/extern-symvers"
  mkdir -p "$BaseRoot/usr/$libm/$element/extern-symvers"
  mkdir -p "$BaseRoot/$libm/$element/$inteldir/auxiliary"
  mkdir -p "$BaseRoot/usr/$libm/$element/$inteldir/auxiliary"
  mkdir -p "$BaseRoot/usr/$libm/$element/extern-symvers"
  mkdir -p "$BaseRoot/usr/$libm/$element/$inteldir/auxiliary"
  mkdir -p "$BaseRoot/usr/$libm/$element/$inteldir/iavf"

  echo "Copy /$libm/$element/extern-symvers/* -> $BaseRoot/$libm/$element/extern-symvers/"
  cp /$libm/$element/extern-symvers/* $BaseRoot/$libm/$element/extern-symvers/
  cp /usr/$libm/$element/extern-symvers/* $BaseRoot/usr/$libm/$element/extern-symvers/
  cp /$libm/$element/$inteldir/auxiliary/* $BaseRoot/$libm/$element/$inteldir/auxiliary/
  cp /usr/$libm/$element/$inteldir/auxiliary/* $BaseRoot/usr/$libm/$element/$inteldir/auxiliary/
  cp /usr/$libm/$element/$inteldir/iavf/* $BaseRoot/usr/$libm/$element/$inteldir/iavf/
done

echo "Compressing /root/proavf.tar.gz..."
cd $BaseRoot
tar -czf /root/proavf.tar.gz *