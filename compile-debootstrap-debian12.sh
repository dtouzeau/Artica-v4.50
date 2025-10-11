#!/usr/bin/env bash
ROOT="/home/machines-builder"
DIR="$ROOT/Debian12"
VIRTHD="debian12-wordpress.ext4"

dd if=/dev/zero of=/home/debian12.ext4 bs=1G count=3
mdkir -p $DIR

mkdir -p
/home/debootstrap-debian12/usr/share/artica-postfix/bin
chroot /home/debootstrap-debian12
passwd root

systemctl enable systemd-networkd

if [ ! -d $DIR ]; then
  mkdir -p $DIR
fi


if [ ! -f "$DIR/bin/apt" ]; then
  echo "Installing BootStrap..."
  debootstrap --include openssh-server,apt,vim,haveged,rng-tools,php,mariadb-server,libpcap0.8 'bookworm' $DIR/ http://deb.debian.org/debian
fi

if [ ! -f "$DIR/bin/apt" ]; then
  echo "Installing BootStrap failed..."
  exit 1
fi
echo "auto eth0" >$DIR/etc/network/interfaces
echo "allow-hotplug eth0" >>$DIR/etc/network/interfaces
echo "iface eth0 inet dhcp" >> $DIR/etc/network/interfaces


echo "Installing Artica Web API"
mkdir -p $DIR/usr/share/artica-postfix/bin
cp -f /usr/share/artica-postfix/bin/articarest $DIR/usr/share/artica-postfix/bin/
cp -f /usr/share/artica-postfix/bin/articarest $DIR/usr/sbin/artica-phpfpm-service
chmod 0755 $DIR/usr/share/artica-postfix/bin/articarest
chmod 0755 $DIR/usr/sbin/artica-phpfpm-service

if [ -f "/root/nginx.tar.gz" ]; then
  echo "Installing Nginx"
  mkdir -p /root/extract1
  tar -xf /root/nginx.tar.gz -C /root/extract1/
  cp -rf /root/extract1/etc/* $DIR/etc/
  cp -rf /root/extract1/opt/* $DIR/opt/
  cp -rf /root/extract1/usr/local/* $DIR/usr/local/
  rm -f $DIR/usr/local/modsecurity/lib/libmodsecurity.a
  rm -f $DIR/usr/local/modsecurity/lib/libmodsecurity.la
  cp -rf /root/extract1/usr/sbin/* $DIR/usr/sbin/
  chmod 0755 $DIR/usr/sbin/nginx
  cp -rf /root/extract1/usr/share/* $DIR/usr/share/
fi
rm -rf /root/extract1
mkdir -p /root/extract1

if [ -f "/root/mysql.tar.gz" ]; then
  echo "Installing MariaDB"
   tar -xf /root/mysql.tar.gz -C /root/extract1/
   cp -rf /root/extract1/etc/* $DIR/etc/
   cp -rf /root/extract1/lib/* $DIR/lib/
   cp -rf /root/extract1/usr/lib/* $DIR/lib/
   cp -rf /root/extract1/usr/bin/* $DIR/usr/bin/
   cp -rf /root/extract1/usr/include/* $DIR/usr/include/
   cp -rf /root/extract1/usr/sbin/* $DIR/usr/sbin/
   cp -rf /root/extract1/usr/share/* $DIR/usr/share/
   cp -rf /root/extract1/var/* $DIR/var/
else
  echo "/root/mysql.tar.gz no such file"
fi
rm -rf /root/extract1
mkdir -p /root/extract1

if [ -f "/root/monit.tar.gz" ]; then
  echo "Installing Monit"
  tar -xf /root/monit.tar.gz -C /root/extract1/
  cp -rf /root/extract1/usr/sbin/ripole $DIR/usr/sbin/
  cp -rf /root/extract1/usr/bin/monit $DIR/usr/bin/
  chmod 0755 $DIR/usr/bin/monit
fi

rm -rf /root/extract1
echo "Installing boot script"
echo "[Unit]" >$DIR/etc/systemd/system/artica-microservice.service
echo "Description=First install Artica Micro service" >>$DIR/etc/systemd/system/artica-microservice.service
echo "After=network.target" >>$DIR/etc/systemd/system/artica-microservice.service
echo "[Service]" >>$DIR/etc/systemd/system/artica-microservice.service
echo "Type=simple" >>$DIR/etc/systemd/system/artica-microservice.service
echo "ExecStart=/usr/sbin/artica-phpfpm-service -init-microweb" >>$DIR/etc/systemd/system/artica-microservice.service
echo "Restart=on-failure" >>$DIR/etc/systemd/system/artica-microservice.service
echo "[Install]" >>$DIR/etc/systemd/system/artica-microservice.service
echo "WantedBy=multi-user.target" >>$DIR/etc/systemd/system/artica-microservice.service

echo "Do:"
echo "chroot $DIR"
echo "passwd root"
echo "systemctl enable artica-microservice.service"
echo "exit"
echo "mkdir -p /mnt/debian && mount $ROOT/artica-web.ext4 /mnt/debian/ && cd $DIR && rsync -av --progress . /mnt/debian --exclude boot/ && umount /mnt/debian"
echo "cd $ROOT && tar -cf - artica-web.ext4 | gzip -9 > artica-web.ext4.tar.gz"
echo ""
