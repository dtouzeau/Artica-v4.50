#!/bin/bash
INPUT=/tmp/menu.sh.$$
OUTPUT=/tmp/output.sh.$$
trap "rm $OUTPUT; rm $INPUT; exit" SIGHUP SIGINT SIGTERM
DIALOG=${DIALOG=dialog}
function Updatep(){
	/usr/bin/python /usr/share/artica-postfix/menu.update.py --generate
	/tmp/menu.update.sh
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
		echo "Uninstall the SSH service"
		/usr/bin/php /usr/share/artica-postfix/exec.sshd.php --uninstall --dialog
		return;;
	esac
}
function SSH_SERVICE_INSTALL(){
	/usr/bin/dialog --title "Install SSH service" --yesno "This operation will install the SSH service\nPress 'Yes' to continue, or 'No' to exit" 0 0
	case $? in
		0)
		/usr/bin/php /usr/share/artica-postfix/exec.sshd.php --install --dialog
		return;;
	esac
}

function SSH_SERVICE(){
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

function SSH_SERVICE_UNINSTALL(){
	/usr/bin/dialog --title "Uninstall SSH service" --yesno "This operation will uninstall the SSH service\nDo you need to perform this operation ?\nPress 'Yes' to continue, or 'No' to exit" 0 0
	return_value=$?
	case $return_value in
		0)
		echo "Uninstall the SSH service"
		/usr/bin/php /usr/share/artica-postfix/exec.sshd.php --uninstall --dialog
		return;;
	esac
}
function SSH_SERVICE_INSTALL(){
	/usr/bin/dialog --title "Install SSH service" --yesno "This operation will install the SSH service\nPress 'Yes' to continue, or 'No' to exit" 0 0
	case $? in
		0)
		/usr/bin/php /usr/share/artica-postfix/exec.sshd.php --install --dialog
		return;;
	esac
}

function SSH_SERVICE_RESTART(){
  if [ ! -f /etc/init.d/ssh ]; then
    /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --msgbox "\Zb\Z1Service is not installed"  0 0
    return 0
  fi

  echo 50 | /usr/bin/dialog --gauge "Reconfiguring..." 10 70 0
  /usr/bin/php /usr/share/artica-postfix/exec.sshd.php --progress >/dev/null 2>&1
  echo 70 | /usr/bin/dialog --gauge "Stopping service..." 10 70 0
  /etc/init.d/ssh stop >/dev/null 2>&1
  echo 90 | /usr/bin/dialog --gauge "Starting service..." 10 70 0
  /etc/init.d/ssh start >/dev/null 2>&1
  echo 100 | /usr/bin/dialog --gauge "Success..." 10 70 0
  sleep 1;
}



while true
do
SSHINST="Install"
if [ -f /etc/init.d/ssh ]
	then 
	  SSHINST="Uninstall"
fi

/usr/bin/dialog --clear  --nocancel --backtitle "Artica SSH Menu" --title "[ S S H -  M E N U ]" --menu "You can use the UP/DOWN arrow keys
Choose the TASK" 20 100 10 SSH "$SSHINST SSH service" RESTART "Restart and reconfigure SSH service" Quit "Return to main menu" 2>"${INPUT}"
menuitem=$(<"${INPUT}")
case $menuitem in
RESTART) SSH_SERVICE_RESTART;;
SSH) SSH_SERVICE;;
Quit) break;;
esac
done
/usr/share/artica-postfix/logon.system.sh
