#! /bin/bash
### BEGIN INIT INFO
# Provides: artica-cd
# Required-Start: $local_fs
# Required-Stop: $local_fs
# Should-Start:
# Should-Stop:
# Default-Start: 1 2 3 4 5
# Default-Stop: 0 1 6
# Short-Description: Start Artica-CD daemon
# chkconfig: 2345 11 89
# description: Artica CD Script
### END INIT INFO


TARGETS_DIRS=( "/usr/lib" "/lib" "/usr/local/lib" "/var/lib/elasticsearch" "/var/lib/fail2ban" "/var/lib/kibana" "/var/lib/netdata" "/usr/lib/x86_64-linux-gnu","/lib/x86_64-linux-gnu" "/etc" "/opt" "/usr/local/ArticaStats"  "/usr/local/ArticaWebConsole" "/usr/local/3proxy" "/usr/local/share" "/var/cache" "/var/log" "/var/lib" "/var/log" "/var/milter-greylist" "/var/opt" "/var/run" "/var/spool","/usr/local/modsecurity" "/usr/local/modsecurity/lib" "/var/opt" "/opt/kaspersky")

TARGETS_USR=( "RichFilemanager"  "artica-postfix"  "elasticsearch"  "greensql-console"  "lintian"  "netdata"  "php" "phpipam" "suricata" "wsusoffline" "aclocal" "doc" "filebeat" "kibana" "nDPI" "nmap" "php-composer" "pyshared" "update-ipsets"  "xapian-core" "nginx")



TARGETS_BINS=( "/usr/local/sbin" "/usr/local/bin" "/bin" "/sbin" "/usr/bin" "/usr/sbin" "/usr/libexec" )


TARGETS_FREEZE=( "slapd" "samba" "winbind" "samba-common" "squid" "squid3" "squid3-common" "postfix" "exim4-base" "exim4-daemon-light" "exim4" "xmail" "python-unbound" "python3-unbound" "unbound")
TARGETS_INIT=( "exim4" "dnsmasq" "smartmontools" "lm-sensors" "nscd" "iscsid" "rsync" "ftp-proxy" "conntrackd" "vnstat" "redis-server" "winbind" "autofs" "isc-dhcp-server" "irqbalance" "transmission-daemon" "mimedefang" "open-iscsi" "clamav-daemon" "clamav-freshclam" "smbd" "freeradius" "proftpd" "opendkim" "cyrus-imapd" "postfix" "ziproxy" "x11-common" "nmbd" "clamav-freshclam" "spamassassin" "spamass-milter" "spamassassin" "ntp" "nscd" "nfs-common" "stunnel4" "mysql" "php7.3-fpm","privoxy","brightness","redsocks" "prads" "pads" "mailgraph" "pdns-recursor" "quota" "quotarpc" "samba" "tor" "l7filter" "firehol" "mosquito","fail2ban" "samba-ad-dc" "rpcbind" "avahi-daemon" "ssh" "unbound"  "squid" "squid3" "open-vm-tools" "filebeat" )


case "$1" in
start)
	percent=5
	Number=0
if [ ! -d "/etc/artica-postfix" ]
then
  mkdir -p /etc/artica-postfix
fi

mkdir -p "/etc/artica-postfix/settings/Daemons/DoNotUseLocalDNSCache"
echo "1" > /etc/artica-postfix/settings/Daemons/DoNotUseLocalDNSCache || true


if [ ! -f /etc/artica-postfix/chpasswd-done ]
then
	echo "############################################"
	echo "# Please wait, Set root password           #"
	echo "############################################"
   	echo "/bin/echo "root:artica" | /usr/sbin/chpasswd" >/var/log/artica-iso.log 2>&1 || true
   	/bin/echo "root:artica" | /usr/sbin/chpasswd >/etc/artica-postfix/chpasswd-done 2>&1 || true
fi


if [ -f /home/package.tar.gz ]
then
	/usr/bin/clear
	mkdir /home/TempSystem
	(pv -n /home/package.tar.gz | /bin/tar xzf - -C /home/TempSystem/ ) 2>&1 | dialog --title "ISO Installation" --gauge "Extracting Artica Base package..." 6 80


	len=${#TARGETS_BINS[@]}
	for path in "${TARGETS_BINS[@]}"
	do
		mkdir -p $path || true
		chmod 0755 $path || true
		((Number++))
		echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Installing $path $Number/$len" 6 80
		echo "* * * BINARY /home/TempSystem$path TO $path/" >> /var/log/artica-rsync.log
		echo "rsync -qra /home/TempSystem$path/* $path/" >> /var/log/artica-rsync.log
		rsync -qra /home/TempSystem$path/* $path/ >>/var/log/artica-rsync.log 2>&1
		((percent++))
	done
	Number=0
	len=${#TARGETS_DIRS[@]}
	for path in "${TARGETS_DIRS[@]}"
	do
		mkdir -p $path || true
		chmod 0755 $path || true
		((Number++))
		echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Installing $path $Number/$len" 6 80
		echo "rsync -qra /home/TempSystem$path/* $path/" >> /var/log/artica-rsync.log
		rsync -qra /home/TempSystem$path/* $path/ >>/var/log/artica-rsync.log 2>&1
		((percent++))

	done

	Number=0
	len=${#TARGETS_DIRS[@]}
	for path in "${TARGETS_USR[@]}"
	do
		mkdir -p /usr/share/$path || true
		chmod 0755 /usr/share/$path || true
		((Number++))
		echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Installing /usr/share/$path $Number/$len" 6 80
		echo "rsync -qra /home/TempSystem/usr/share/$path/* /usr/share/$path/" >>/var/log/artica-rsync.log
		rsync -qra /home/TempSystem/usr/share/$path/* /usr/share/$path/ >>/var/log/artica-rsync.log 2>&1
		((percent++))
	done

	if [ ! -f /usr/sbin/squid ]
	then
		/usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Unable to find squid binary!"  0 0
		sleep 20
		exit 0
	fi


	((percent++))
	echo $percent| dialog --title "ISO cleaning Installation" --gauge "Please wait, removing source directory" 6 80
	rm -rf /home/TempSystem
	((percent++))
	echo $percent| dialog --title "ISO cleaning Installation" --gauge "Please wait, removing source" 6 80
	rm -f /home/package.tar.gz

fi




if [ ! -f /usr/share/artica-postfix/logon.sh ]
then
	/usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Unable to find Artica LOGON binary!"  0 0
	exit 0
fi

chmod 0755 /usr/share/artica-postfix/logon.sh

if [ -f /etc/artica-postfix/artica-iso-setup-launched ]
then
	echo "artica-cd, everything is done...";
	exit 0
fi

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Saving Network information" 6 80
mkdir -p /etc/artica-postfix/NET_BACKUP || true
mkdir -p /etc/monit/conf.d || true
mkdir -p /etc/monit || true

if [ -f /etc/network/interfaces ]
then
	/bin/cp /etc/network/interfaces /etc/artica-postfix/NET_BACKUP/
fi

if [ -f /etc/resolv.conf ]
then
	/bin/cp /etc/resolv.conf /etc/artica-postfix/NET_BACKUP/ || true
fi

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Saving Network information" 6 80

/bin/ip -f inet addr show | /bin/grep -Po 'inet \K[\d.]+' > /etc/artica-postfix/NET_BACKUP/IPADDR || true
/bin/ip route show default 0.0.0.0/0 > /etc/artica-postfix/NET_BACKUP/GATEWAY || true
/sbin/ifconfig | /usr/bin/awk '/netmask/{print $4}' > /etc/artica-postfix/NET_BACKUP/NETMASK || true


if [ -f /etc/php5/cli/conf.d/ming.ini ]
then
  /bin/rm -f /etc/php5/cli/conf.d/ming.ini
fi
mkdir -p /home/artica/tmp >>/var/log/artica-iso.log 2>&1 || true
mkdir -p /var/run/slapd >>/var/log/artica-iso.log 2>&1 || true

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Configure the framework..." 6 80

mkdir -p /usr/local/ArticaWebConsole/sbin >/var/log/artica-iso.log 2>&1 || true


((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Freeze some services.." 6 80


/usr/sbin/groupadd winbindd_priv >>/var/log/artica-iso.log 2>&1 || true
killall apache2 >>/var/log/artica-iso.log 2>&1 || true

for path in "${TARGETS_FREEZE[@]}"
do
	((Number++))
	echo $percent| dialog --title "ISO Installation" --gauge "Please wait, freeze $path" 6 80
	/bin/echo "$path hold" | /usr/bin/dpkg --set-selections >/dev/null 2>&1|| true
	/usr/bin/apt-mark hold $path
	((percent++))
done

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Uninstalling exim" 6 80
/usr/bin/apt-get --purge --yes --force-yes --remove exim4* >>/var/log/artica-iso.log 2>&1 || true
killall php5-fpm >>/var/log/artica-iso.log 2>&1 || true
killall php-fpm >>/var/log/artica-iso.log 2>&1 || true


if [ ! -f /bin/login.old ]
then
 ((percent++))
  echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Installing Artica Menu console" 6 80
  /bin/mv /bin/login /bin/login.old || true
  /bin/ln -s /usr/share/artica-postfix/logon.sh /bin/login || true
  dpkg-divert --divert /bin/login.old /bin/login >>/var/log/artica-iso.log 2>&1|| true
  /bin/chmod 777 /bin/login || true
  /bin/chmod 777 /usr/share/artica-postfix/logon.sh || true
fi



/bin/rm -f /etc/artica-postfix/FROM_ISO  >>/var/log/artica-iso.log 2>&1 || true
/bin/rm -f /usr/share/artica-postfix/bin/artica-iso >>/var/log/artica-iso.log 2>&1 || true
/bin/touch /etc/artica-postfix/FROM_ISO
/bin/rm -f /etc/artica-postfix/ARTICA_ISO2.lock >>/var/log/artica-iso.log 2>&1 || true
/bin/rm -f /etc/cron.d/artica-boot-first >>/var/log/artica-iso.log 2>&1 || true

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Apply permissions" 6 80
/bin/chown -R www-data:www-data /usr/share/artica-postfix >>/var/log/artica-iso.log 2>&1


((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Patching Filesystem " 6 80
/bin/touch /etc/artica-postfix/artica-iso-setup-launched || true
/bin/echo "secret" >/etc/ldap.secret || true
/bin/touch /etc/artica-postfix/artica-iso-first-reboot || true
/bin/mkdir -p /etc/artica-postfix/settings/Daemons || true
/bin/chmod -R 0755 /etc/artica-postfix/settings/Daemons || true

echo "domain localdomain" > /etc/resolv.conf || true
echo "search localdomain" >> /etc/resolv.conf || true
echo "nameserver 127.0.0.1" >> /etc/resolv.conf || true
echo "nameserver 8.8.8.8" >> /etc/resolv.conf || true

echo "1" >/etc/artica-postfix/settings/Daemons/ArticaHttpUseSSL 2>&1 || true

if [ ! -f /usr/bin/php ]
then
   echo "/usr/bin/php not such file, create a symlink" >>/var/log/artica-iso.log 2>&1 || true
   if [ -f /usr/bin/php7.4 ]
   then
	echo "Installing PHP v7.4..." >>/var/log/artica-iso.log 2>&1 || true
   	ln -sf /usr/bin/php7.4 /usr/bin/php >>/var/log/artica-iso.log 2>&1 || true
   	chmod 0755 /usr/bin/php7.4 >>/var/log/artica-iso.log 2>&1 || true
   fi
fi



((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Configuring PHP system" 6 80
/usr/bin/php /usr/share/artica-postfix/exec.php.ini.php >>/var/log/artica-iso.log 2>&1|| true

((percent++))
echo "Installing monit..." >>/var/log/artica-iso.log 2>&1 || true
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, installing default services" 6 80
/usr/bin/php /usr/share/artica-postfix/exec.monit.php --install >>/var/log/artica-iso.log 2>&1|| true

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, installing default services" 6 80
/usr/bin/php /usr/share/artica-postfix/exec.initslapd.php >>/var/log/artica-iso.log 2>&1|| true

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, scanning hard drives" 6 80
/usr/bin/php /usr/share/artica-postfix/exec.convert-to-sqlite.php --network 2>&1|| true



((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, tunning filesystem" 6 80
echo "Tuning file system" >>/var/log/artica-iso.log 2>&1 || true
/usr/bin/php /usr/share/artica-postfix/exec.patch.fstab.php >>/var/log/artica-iso.log 2>&1 || true

len=${#TARGETS_INIT[@]}
Number=0
for path in "${TARGETS_INIT[@]}"
do
	((Number++))
	if [ -f "/etc/init.d/$path" ]
	then
		((percent++))
		if (( percent > 80 )); then
			percent=80
		fi
		echo $percent| dialog --title "ISO Installation" --gauge "Please wait, uninstalling /etc/init.d/$path ($Number/$len)" 6 80
		/etc/init.d/$path stop >>/var/log/artica-iso.log 2>&1 || true
		update-rc.d $path remove >>/var/log/artica-iso.log 2>&1 || true
		rm -f /etc/init.d/$path||true
	fi
done

percent=80

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Deleting floppy" 6 80
echo "blacklist floppy" >>/var/log/artica-iso.log 2>&1 || true
echo 'blacklist floppy' >/etc/modprobe.d/floppy-blacklist.conf 2>&1 || true

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Patching Grub for Debian 10" 6 80
echo "/usr/share/artica-postfix/bin/articarest -grub-debian5" >>/var/log/artica-iso.log 2>&1 || true
/usr/share/artica-postfix/bin/articarest -grub-debian5 >>/var/log/artica-iso.log 2>&1 || true


((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Installing framework" 6 80
chmod 0755 /usr/share/artica-postfix/bin/articarest
echo "/usr/share/artica-postfix/bin/artwatch -install" >>/var/log/artica-iso.log 2>&1 || true
cp /usr/share/artica-postfix/bin/articarest /usr/sbin/artica-phpfpm-service
chmod 0755 /usr/sbin/artica-phpfpm-service
/usr/sbin/artica-phpfpm-service -start-artica-php -debug
/usr/sbin/artica-phpfpm-service -start-webconsole -debug


((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Installing VnStatD" 6 80
echo "/usr/bin/php /usr/share/artica-postfix/exec.vnstat.php --install" >>/var/log/artica-iso.log 2>&1 || true
/usr/bin/php /usr/share/artica-postfix/exec.vnstat.php --install >>/var/log/artica-iso.log 2>&1 || true

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Installing DNS CACHE SERVICE" 6 80
/usr/bin/php /usr/share/artica-postfix/exec.wizard.resolv.conf.php >>/var/log/artica-iso.log 2>&1 || true
/usr/bin/php /usr/share/artica-postfix/exec.pam.php --build >>/var/log/artica-iso.log 2>&1 || true


((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Reconfiguring MONIT" 6 80
echo "/usr/bin/php /usr/share/artica-postfix/exec.monit.php --build" >>/var/log/artica-iso.log 2>&1 || true
rm -rf /etc/monit/monitrc >>/var/log/artica-iso.log 2>&1 || true
/usr/bin/php /usr/share/artica-postfix/exec.monit.php --build >>/var/log/artica-iso.log 2>&1 || true

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, installing TailON" 6 80
echo "/usr/bin/php /usr/share/artica-postfix/exec.tailon.php --install" >>/var/log/artica-iso.log 2>&1 || true


((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Update debian sources list" 6 80
php /usr/share/artica-postfix/exec.verif.packages.php --pin >>/var/log/artica-iso.log 2>&1 || true
php /usr/share/artica-postfix/exec.apt-get.php --sources-list >>/var/log/artica-iso.log 2>&1 || true



((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Removing systemd" 6 80
php /usr/share/artica-postfix/exec.verif.packages.php systemd-remove >>/var/log/artica-iso.log 2>&1 || true
echo "Remove systemd" >>/var/log/artica-iso.log 2>&1 || true
apt-get -y remove --purge --auto-remove systemd >>/var/log/artica-iso.log 2>&1 || true

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, installing MEMCACHED" 6 80
echo "Reconfiguring MEMCACHED" >>/var/log/artica-iso.log 2>&1 || true
/usr/share/artica-postfix/bin/articarest -install-memcached >>/var/log/artica-iso.log 2>&1 || true

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, installing Executor Framework" 6 80
echo "Reconfiguring MEMCACHED" >>/var/log/artica-iso.log 2>&1 || true
php /usr/share/artica-postfix/exec.go.exec.php >>/var/log/artica-iso.log 2>&1 || true



((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Checking and Testing VMWare Tools" 6 80
/usr/bin/php /usr/share/artica-postfix/exec.openVMTools.php --autoinstall >>/var/log/artica-iso.log 2>&1|| true



((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, checking network" 6 80
echo "Reconfiguring networks For the first time" >>/var/log/artica-iso.log 2>&1 || true
php /usr/share/artica-postfix/exec.netbackup.php >>/var/log/artica-iso.log 2>&1 || true
echo "Reconfiguring syslog daemon" >>/var/log/artica-iso.log 2>&1 || true
php /usr/share/artica-postfix/exec.syslog-engine.php --rsylogd >>/var/log/artica-iso.log 2>&1 || true

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Cleaning" 6 80
chmod 0755 /etc/init.d/artica-iso 2>&1 || true
echo "artica-cd Remove script" >>/var/log/artica-iso.log 2>&1 || true
update-rc.d artica-iso remove >>/var/log/artica-iso.log 2>&1 || true
echo "" > /etc/init.d/artica-iso 2>&1 || true


percent=98

((percent++))
echo $percent| dialog --title "ISO Installation" --gauge "Please wait, Update INIT RamFS" 6 80


if [ -x /usr/sbin/update-initramfs -a -e /etc/initramfs-tools/initramfs.conf ] ; then
	echo "update-initramfs" >>/var/log/artica-iso.log 2>&1 || true
	update-initramfs -u >>/var/log/artica-iso.log 2>&1
fi

echo 100| dialog --title "ISO Installation" --gauge "Rebooting...." 6 80
/bin/touch /etc/artica-postfix/artica-as-rebooted || true

echo s > /proc/sysrq-trigger
echo u > /proc/sysrq-trigger
echo b > /proc/sysrq-trigger

;;
stop)

;;
restart)
;;
*)
echo "Usage: $0 {start|stop|}"
exit 1
;;
esac
exit 0