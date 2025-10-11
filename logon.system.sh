#!/bin/bash
INPUT=/tmp/menu.sh.$$
OUTPUT=/tmp/output.sh.$$
trap "rm $OUTPUT; rm $INPUT; exit" SIGHUP SIGINT SIGTERM
DIALOG=${DIALOG=dialog}
function Updatep(){
  chmod 0755 /usr/share/artica-postfix/bin/artica-update
	/usr/share/artica-postfix/bin/artica-update -menu >/dev/null 2>&1
	/tmp/artica.menu.update.sh
}
function BackupRestorep(){
	/usr/bin/php /usr/share/artica-postfix/exec.menu.snapshots.php --menu
	/tmp/bash_snapshots_menu.sh
}


function SSH_SERVICE_UNINSTALL(){
	/usr/bin/dialog --title "Uninstall SSH service" --yesno "This operation will uninstall the SSH service\nDo you need to perform this operation ?\nPress 'Yes' to continue, or 'No' to exit" 0 0
	return_value=$?
	case $return_value in
		0)
		/usr/sbin/artica-phpfpm-service -uninstall-ssh
		return;;
	esac
}
function SSH_SERVICE_INSTALL(){
	/usr/bin/dialog --title "Install SSH service" --yesno "This operation will install the SSH service\nPress 'Yes' to continue, or 'No' to exit" 0 0
	case $? in
		0)
		/usr/sbin/artica-phpfpm-service -install-ssh

		return;;
	esac
}

function SSH_SERVICE2(){
	if [ -f /etc/init.d/ssh ]
	then
	   SSH_SERVICE_UNINSTALL
	   /usr/share/artica-postfix/logon.system.sh
	   exit
	fi
	SSH_SERVICE_INSTALL
	/usr/share/artica-postfix/logon.system.sh
	exit

}

function SSH_SERVICE(){
  /usr/sbin/artica-phpfpm-service -menu-ssh
	/usr/share/artica-postfix/logon.ssh.sh
  exit 0
}

function ResetSettingsINC(){

/usr/bin/dialog --title "Remove" --yesno "This operation will reset settings\nServer will be turned to DHCP and all services will be removed...\nPress 'Yes' to continue, or 'No' to exit" 0 0
	case $? in
		0)
		/usr/bin/php /usr/share/artica-postfix/exec.reset.php --confirm
		return ;;
	esac

}

function DWSERVICE(){
  /usr/bin/php /usr/share/artica-postfix/exec.menu.dwservice.php --menu
  /tmp/bash_dwservice_menu.sh
}

function OPTIMIZE(){
	/usr/bin/dialog --title "Optimize your system" --yesno "This operation optimize only your system when using\n\n- SSD disks\n- Microsoft HyperV\n- VMWare ESXI\n- XenServer\n\n\nYou need to reboot after this operation\n\n\nDo you need to perform this operation ? Press 'Yes' to continue, or 'No' to exit" 0 0
	case $? in
		0)
		/bin/echo 1 >/etc/artica-postfix/settings/Daemons/EnableSystemOptimize
		/usr/sbin/artica-phpfpm-service -optimize-os
		return;;
	esac
}

function UpgradeToPhp8(){
  /usr/bin/dialog --title "Upgrade your system to php 8.x ?" --yesno "Perform this operation ? Press 'Yes' to continue, or 'No' to exit" 0 0

  case $? in
    1)
      return ;;
  esac



}

function CleanLogsp(){
	/usr/bin/dialog --title "Clean up non-vital directories" --yesno "Do you need to perform this operation ? Press 'Yes' to continue, or 'No' to exit" 0 0
	case $? in
	0)
		chmod 0755 /usr/share/artica-postfix/bin/remove-artica-logs.sh
		for i in $(seq 0 20 80) ; do sleep 1; echo $i | /usr/bin/dialog --gauge "Please wait" 10 70 0; done
		echo 90 | /usr/bin/dialog --gauge "Running..." 10 70 0
		/usr/share/artica-postfix/bin/remove-artica-logs.sh >/dev/null 2>&1
		sleep 1
		echo 100 | /usr/bin/dialog --gauge "Please wait" 10 70 0
		sleep 1
		/usr/bin/dialog --title "Clean the log directory" --msgbox "Your log directory as been cleaned..."  0 0
		return;;
	1)
		return;;
	255)
		return;;
	esac
}

function PASSWDY(){
	passwd root
}

function UUID_RESET(){

  UUID1=$(cat /etc/artica-postfix/settings/Daemons/SYSTEMID)
  /usr/bin/dialog --title "Generate a new uuid" --yesno "Warning, this operation should break the associated license (except for Gold license)\\nDo you need to perform this operation ?\\n Press 'Yes' to continue, or 'No' to exit" 0 0

  case $? in
  0)
    /usr/bin/php /usr/share/artica-postfix/exec.menu.ips.php --uuid
    UUID2=$(cat /etc/artica-postfix/settings/Daemons/SYSTEMID)
    /usr/bin/dialog --title "New uuid generated" --msgbox "Old UUID: $UUID1\\nNew uuid: $UUID2"  0 0
    return;;
  1)
    return;;

  255)
    return;;

  esac

}

function GRUBPKG(){
  /usr/sbin/artica-phpfpm-service -menu-grub >/dev/null 2>&1
  /tmp/bash_grub_menu.sh
  /usr/share/artica-postfix/logon.system.sh
  exit 0
}

function LaunchTzData(){

  /usr/sbin/dpkg-reconfigure tzdata

}
function PROXY(){
 python /usr/share/artica-postfix/bin/proxy-setup.py --build
 /tmp/proxy-setup.sh
}
function SuperAdmin(){
	if [ -f /tmp/dns.log ]; then
		rm /tmp/dns.log
	fi


	/usr/bin/dialog --clear --title "Username" --inputbox "Enter the SuperAdmin username" 10 68 "Manager" 2> /etc/artica-postfix/WIZARUSERNAME
	case $? in
		1)
		rm /etc/artica-postfix/WIZARUSERNAME || true
		return
	esac


	/usr/bin/dialog --clear --insecure --passwordbox "ENTER SuperAdmin Password for authentication"  10 68 secret 2> /etc/artica-postfix/WIZARUSERNAMEPASSWORD
	case $? in
		1)
		rm /etc/artica-postfix/WIZARUSERNAME || true
		rm /etc/artica-postfix/WIZARUSERNAMEPASSWORD || true
		return
	esac
	
	/usr/bin/dialog --title "Change SuperAdmin Account" --yesno "Do you need to perform this operation ? Press 'Yes' to continue, or 'No' to exit" 0 0
	case $? in
		0)
      if [ -f /tmp/dns.log ]; then
        rm /tmp/dns.log
      fi
      /usr/sbin/artica-phpfpm-service -superadmin
      /usr/share/artica-postfix/logon.system.sh
      exit 0;
		  return;;
	1)
		  rm /etc/artica-postfix/WIZARUSERNAME
		  rm /etc/artica-postfix/WIZARUSERNAMEPASSWORD
      /usr/share/artica-postfix/logon.system.sh
      exit 0;
		return;;
	255)
		   rm /etc/artica-postfix/WIZARUSERNAME
		   rm /etc/artica-postfix/WIZARUSERNAMEPASSWORD
		   /usr/share/artica-postfix/logon.system.sh
      exit 0;
		return;;
	esac
}
if [ -f /etc/artica-postfix/.TestDrive ]
then
    rm -f /etc/artica-postfix/.TestDrive
fi

touch /etc/artica-postfix/.TestDrive

if [ ! -f /etc/artica-postfix/.TestDrive ]
then
    /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --msgbox "\Zb\Z1Hard disk issue\nNo space left\nor no free inodes\nor partition in read-only\n\nPress OK to enter into the system"  0 0
    /bin/login.old
    exit 0
fi
/usr/bin/php /usr/share/artica-postfix/exec.apt-get.php --grubpc >/dev/null

if [ -f /etc/artica-postfix/GRUBPC_DEVICE_ERROR ]; then
      DISK=$(cat /etc/artica-postfix/GRUBPC_DEVICE_ERROR)
      /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --msgbox "\Zb\Z1Clone detected\nThe boot loader is configured to using this disk\n$DISK\nBut this not the case, this typically when you clone a virtual machine from a KVM hypervisor\n\nPress OK to reconfigure the boot loader"  0 0
    /usr/sbin/artica-phpfpm-service -menu-grub
    /tmp/bash_grub_menu.sh
    /usr/share/artica-postfix/logon.system.sh
    exit 0
fi




while true
do


  SSHINST=""
  if [ -f /etc/init.d/ssh ]
    then
      SSHINST="(installed)"
  fi
  PHPVer=`php -v|grep --only-matching --perl-regexp "(PHP )\d+\.\\d+\.\\d+"|cut -c 5`
  PHPVer=8
  if [ $PHPVer -le 8 ]; then
    /usr/bin/dialog --clear  --nocancel --backtitle "Artica System Menu" --title "[ S Y S T E M -  M E N U ]" --menu "You can use the UP/DOWN arrow keys
  Choose the TASK" 20 100 10 PASSWD "System root password" SuperAdmin "Web interface SuperAdmin account" SSH "SSH service $SSHINST" HTTPPROXY "HTTP Proxy setup"  DWSERVICE "DWService Remote control" TimeZone "Set the Time Zone" RESET "Reset parameters" Update "Update tasks" BackupRestore "Backup and restore (snapshots)" diskspace "Make disk space" OPTIMIZE "System Optimization ( SSD Disks, HyperV, XenServer, VMWare )" GRUBPKG "Boot Loader and packages" UUID "Generate a new Unique identifier" Quit "Return to main menu" 2>"${INPUT}"
  else
    /usr/bin/dialog --clear  --nocancel --backtitle "Artica System Menu" --title "[ S Y S T E M -  M E N U ]" --menu "You can use the UP/DOWN arrow keys
  Choose the TASK" 20 100 10 PASSWD "System root password" SuperAdmin "Web interface SuperAdmin account" SSH "SSH service $SSHINST" HTTPPROXY "HTTP Proxy setup" DWSERVICE "DWService Remote control" TimeZone "Set the Time Zone" RESET "Reset parameters" Update "Update tasks" BackupRestore "Backup and restore (snapshots)" diskspace "Make disk space" OPTIMIZE "System Optimization ( SSD Disks, HyperV, XenServer, VMWare )" GRUBPKG "Boot Loader and packages" UUID "Generate a new Unique identifier" Quit "Return to main menu" 2>"${INPUT}"
  fi

  menuitem=$(<"${INPUT}")
  case $menuitem in
    HTTPPROXY) PROXY;;
    OPTIMIZE) OPTIMIZE;;
    BackupRestore) BackupRestorep;;
    PASSWD) PASSWDY;;
    SuperAdmin) SuperAdmin;;
    SSH) SSH_SERVICE;;
    Update) Updatep;;
    TimeZone) LaunchTzData;;
    diskspace) CleanLogsp;;
    RESET) ResetSettingsINC;;
    UPGPHP) UpgradeToPhp8;;
    GRUBPKG) GRUBPKG;;
    DWSERVICE) DWSERVICE;;
    UUID) UUID_RESET;;
    Quit) break;;
  esac
done
