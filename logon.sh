#!/bin/bash
NOCHECK=0
if [[ "$1" != "notty" ]]; then
  exec </dev/tty1 >/dev/tty1 2>&1
  NOCHECK=1
fi


clear
INPUT=/tmp/menu.sh.$$
OUTPUT=/tmp/output.sh.$$
trap "rm -f $OUTPUT >/dev/null 2>&1; rm -f $INPUT >/dev/null 2>&1; exit" SIGHUP SIGINT SIGTERM
DIALOG=${DIALOG=dialog}
mkdir -p /home/artica/tmp

function display_output(){
	local h=${1-10}			# box height default 10
	local w=${2-41} 		# box width default 41
	local t=${3-Output} 	# box title 
	dialog --backtitle "Menu console" --title "${t}" --clear --msgbox "$(<$OUTPUT)" ${h} ${w}
}
function menu_security_center(){
  /usr/sbin/artica-phpfpm-service -menu-sc
  /usr/share/artica-postfix/logon.sc.sh
}
function BuilMasterConfig(){

    if [ ! -f /etc/artica-postfix/settings/Daemons/MasterDuplicateResetDHCP ]
    then
        echo 0 >/etc/artica-postfix/settings/Daemons/MasterDuplicateResetDHCP
    fi

    MasterDuplicateResetDHCP=`cat /etc/artica-postfix/settings/Daemons/MasterDuplicateResetDHCP`
    if [[ "$MasterDuplicateResetDHCP" == 1 ]];
    then
        echo 10 | /usr/bin/dialog --gauge "Reset networks and turn to DHCP..." 10 70 0
        sleep 1
        php /usr/share/artica-postfix/exec.autoconfig.php --reset-dhcp >/dev/null 2>&1
        sleep 1
    fi


	echo 20 | /usr/bin/dialog --gauge "Downloading auto-configuration file" 10 70 0
	sleep 1
	php /usr/share/artica-postfix/exec.autoconfig.php --download >/dev/null 2>&1
    sleep 1
    if [ ! -f /etc/artica-postfix/MASTER_STEP1 ]
    then
        /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Downloading the auto-configuration file failed!"  0 0
        sleep 6
        exit 0
    fi

	sleep 1
	echo 25 | /usr/bin/dialog --gauge "Decrypting auto-configuration file" 10 70 0
	php /usr/share/artica-postfix/exec.autoconfig.php --decrypt >/dev/null 2>&1
	sleep 1
	if [  -f /etc/artica-postfix/MASTER_DISABLED ]
    then
        /usr/bin/dialog --title "\Zb\Z1Master feature is disabled" --colors --infobox "\Zb\Z1The Master feature is disabled, skip this process"  0 0
        sleep 6
        echo 0 > /etc/artica-postfix/settings/Daemons/MasterDuplicateMode
        exit 0
    fi

    if [ ! -f /etc/artica-postfix/MASTER_STEP2 ]
    then
        PARMAS=`cat /etc/artica-postfix/MASTER_STEP2_ERROR` ||true
        /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Decrypting failed! $PARMAS"  0 0
        echo 0 > /etc/artica-postfix/settings/Daemons/MasterDuplicateMode
        sleep 6
        exit 0
    fi

    sleep 1
	echo 30 | /usr/bin/dialog --gauge "Reading configuration..." 10 70 0
	php /usr/share/artica-postfix/exec.autoconfig.php --read >/dev/null 2>&1
	sleep 1
    if [ ! -f /etc/artica-postfix/MASTER_STEP3 ]
    then
        PARMAS=`cat /etc/artica-postfix/MASTER_STEP2_ERROR` || true
        /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Parameters not found with $PARMAS"  0 0
        echo 0 > /etc/artica-postfix/settings/Daemons/MasterDuplicateMode
        sleep 6
        exit 0
    fi


  sleep 1
	echo 35 | /usr/bin/dialog --gauge "Checking Network..." 10 70 0
	php /usr/share/artica-postfix/exec.autoconfig.php --network >/dev/null 2>&1
	sleep 1
	echo 40 | /usr/bin/dialog --gauge "Reloading Network..." 10 70 0
	/etc/init.d/artica-ifup start >/dev/null 2>&1
	sleep 1


	echo 45 | /usr/bin/dialog --gauge "Checking License..." 10 70 0
	php /usr/share/artica-postfix/exec.autoconfig.php --license >/dev/null 2>&1
	sleep 1
    if [ ! -f /etc/artica-postfix/MASTER_LICENSE ]
    then
        PARMAS=`cat /etc/artica-postfix/MASTER_STEP2_ERROR` || true
        /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1License not found with $PARMAS"  0 0
        echo 0 > /etc/artica-postfix/settings/Daemons/MasterDuplicateMode
        sleep 6
        exit 0
    fi



	echo 50 | /usr/bin/dialog --gauge "Checking Active Directory...." 10 70 0
	php /usr/share/artica-postfix/exec.autoconfig.php --adconnect >/dev/null 2>&1
    sleep 1
	echo 55 | /usr/bin/dialog --gauge "Checking Web-Filtering...." 10 70 0
	php /usr/share/artica-postfix/exec.autoconfig.php --ufdb >/dev/null 2>&1
	sleep 1
    echo 100 | /usr/bin/dialog --gauge "Success" 10 70 0
    echo 0 > /etc/artica-postfix/settings/Daemons/MasterDuplicateMode
    exit
}
function menu_network_unix(){
  /usr/sbin/artica-phpfpm-service -menu-nets

  if [ ! -f /home/artica/tmp/bash_network_menu.sh ]
  then
    /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Unable to stat network menu"  0 0
  fi

  /home/artica/tmp/bash_network_menu.sh
}
function menu_hacluster(){
  /usr/sbin/artica-phpfpm-service -menu-hacluster
  /home/artica/tmp/bash_hacluster.sh
}
function menu_network(){
  if [ ! -f /usr/share/artica-postfix/ressources/class.template-admin.inc ]
  then
   /usr/sbin/artica-phpfpm-service -menu-nets
  fi
  if [ -f /usr/share/artica-postfix/ressources/class.template-admin.inc ]
  then
   /usr/sbin/artica-phpfpm-service -menu-network
  fi

  if [ ! -f /home/artica/tmp/bash_network_menu.sh ]
   then
      /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Unable to stat network menu"  0 0
   fi
 /home/artica/tmp/bash_network_menu.sh
}
function KeyBoard(){
 php /usr/share/artica-postfix/exec.menu.keyboard.php
/tmp/bash_keyboard_menu.sh
}
function Metap(){
  php /usr/share/artica-postfix/exec.menu.meta.php --menu
/tmp/bash_meta_menu.sh
}
function UnixReboot() {
  if [ -f /usr/sbin/artica-phpfpm-service ]
  then
    /usr/sbin/artica-phpfpm-service -reboot
  else
     /usr/sbin/reboot

  fi
}
function MainMenuSecurityCenter() {


IPs=`cat /etc/artica-postfix/MENU_SHOWIP`
$DIALOG --clear  --nocancel --backtitle "Security Center Linux" \
--title "[ S E C U R I T Y  C E N T E R - M E N U ]" \
--menu "$IPs\\nYou can use the UP/DOWN arrow keys\nChoose the TASK" 25 100 11 \
Network "Modify server Network configuration" \
SecurityCenter "Security Center setup" \
System "Root password and system tasks" \
KeyBoard "Keyboard,local,charset setup" \
Processes "Processes Monitor" \
Reboot "Reboot this server" \
Shutdown "Shutdown this server" \
Exit "Exit to the shell" 2>"${INPUT}" || true

menuitem=$(<"${INPUT}")

case $menuitem in
	Network) menu_network;;
  LocalNet) menu_network_unix;;
  SecurityCenter) menu_security_center;;
  HaCluster) menu_hacluster;;
	Processes) htopp;;
	System) Systemp;;
	WebInterface) WebInterfacep;;
	Statistics) Statisticsp;;
	Meta) Metap;;
	Reboot) UnixReboot;;
	KeyBoard) KeyBoard;;
	License) License;;
	Shutdown) init 0;;
	Exit) /bin/login.old;;
esac

}

function Statisticsp(){
    php /usr/share/artica-postfix/exec.menu.statistics.php --menu
    /tmp/bash_statistics_menu.sh
}
function htopp(){
  /usr/bin/htop
}
function WebInterfacep(){
  /usr/sbin/artica-phpfpm-service -menu-webconsole
  /home/artica/tmp/bash_apache_menu.sh
}
function License(){
   /usr/sbin/artica-phpfpm-service -menu-license
  /home/artica/tmp/bash_license_menu.sh
}
function SystemPing(){
  /usr/sbin/artica-phpfpm-service -unix-ping
  exec /usr/share/artica-postfix/logon.sh notty
  exit 0
}
function Systemp(){
  /usr/sbin/artica-phpfpm-service -menu-sys
  if [ ! -f /usr/share/artica-postfix/logon.system.sh ]
  then
    /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Menu was not generated\n\nPress OK to return back to the main menu"  0 0
    return
  fi
  /usr/share/artica-postfix/logon.system.sh
}

if [ -f /etc/artica-postfix/.TestDrive ]
then
    rm -f /etc/artica-postfix/.TestDrive
fi
rm -f /etc/artica-postfix/.LogonStarted
touch /etc/artica-postfix/.TestDrive
touch /etc/artica-postfix/.LogonStarted

if [ ! -f /etc/artica-postfix/.TestDrive ]
then
    /usr/bin/dialog --title "\Zb\Z1ERROR! ERROR!" --colors --infobox "\Zb\Z1Hard disk issue\nNo space left\nor no free inodes\nor partition in read-only\n\nPress OK to enter into the system"  0 0
    /bin/login.old
    exit 0
fi

if [ ! -f /etc/artica-postfix/artica-iso-setup-launched ]
then
  chmod 0755 /usr/share/artica-postfix/bin/articarest
  /usr/sbin/artica-phpfpm-service -setup-iso
fi

wait=1;
if [ -f /etc/artica-postfix/artica-iso-setup-launched ]
then
  IPs=`cat /etc/artica-postfix/MENU_SHOWIP`
fi

if [ -f /usr/share/artica-postfix/exec.check.watchdogs.php ]
then
  /usr/bin/nohup /usr/bin/php /usr/share/artica-postfix/exec.check.watchdogs.php >/dev/null 2>&1
fi

if [ -f /etc/artica-postfix/settings/Daemons/MasterDuplicateMode ]
then
    MasterDuplicateMode=`cat /etc/artica-postfix/settings/Daemons/MasterDuplicateMode`
    if [[ "$MasterDuplicateMode" == 1 ]];
    then
        BuilMasterConfig
        exit 0
    fi
fi

rebootperc=1;

if [ ! -f /etc/artica-postfix/microservice ]; then
  if [ ! -f /etc/artica-postfix/artica-iso-first-reboot ]
  then

    while [ ! -f /etc/artica-postfix/artica-iso-first-reboot ]
      do
        sleep 1
        ((rebootperc++))
        if [ $rebootperc -eq 99 ]
        then
            /bin/touch /etc/artica-postfix/artica-iso-first-reboot || true
            break
        fi
         echo $rebootperc | /usr/bin/dialog --gauge "Building your appliance" 10 70 0
      done

     rebootperc=0
     while [  -f /etc/artica-postfix/artica-iso-first-reboot ]
     do
       ((rebootperc++))
       sleep 1
        if [ $rebootperc -eq 50 ]
        then
           /usr/sbin/artica-phpfpm-service -reboot || true
           /usr/sbin/reboot
        fi

        if [ $rebootperc -eq 99 ]
        then
            rebootperc=0
             /usr/sbin/artica-phpfpm-service -reboot || true
             /usr/sbin/reboot
        fi
       echo $rebootperc | /usr/bin/dialog --gauge "Rebooting..." 10 70 0
      done

  fi
fi

if [ ! -f /etc/artica-postfix/microservice ]; then

    if [ -f /usr/share/artica-postfix/VERSION ]; then
      echo 35 | /usr/bin/dialog --gauge "Checking Version..." 10 70 0
      ARTICAVERSION=$(cat /usr/share/artica-postfix/VERSION)

      if [ -f "/usr/share/artica-postfix/SP/$ARTICAVERSION" ]
      then
          ARTICASP=$(cat /usr/share/artica-postfix/SP/$ARTICAVERSION)
          ARTICAVERSION="$ARTICAVERSION Service Pack $ARTICASP"
      fi


      echo 36 | /usr/bin/dialog --gauge "Checking Version [$ARTICAVERSION]..." 10 70 0
    fi

    echo 37 | /usr/bin/dialog --gauge "Checking Network..." 10 70 0
    fileMENUSHOWIP="/etc/artica-postfix/MENU_SHOWIP"
    timeout=10
    elapsed=0
    ip_regex='([0-9]{1,3}\.){3}[0-9]{1,3}|([a-fA-F0-9:]+:+)+[a-fA-F0-9]+'
    Prog=37
    if [ $NOCHECK != "0" ]; then
      while (( elapsed < timeout )); do
          if [[ -f "$fileMENUSHOWIP" ]]; then
              IPs=$(cat "$fileMENUSHOWIP")
              if [[ "$IPs" =~ $ip_regex ]]; then
                  break
              fi
          fi
          ((Prog++))
          clear
          echo $Prog | /usr/bin/dialog --gauge "Waiting network to be ready" 10 70 0
          sleep 1
          ((elapsed++))
      done
      clear
      echo 98 | /usr/bin/dialog --gauge "Done..." 10 70 0
      sleep 1
      clear
      echo 99 | /usr/bin/dialog --gauge "Done..." 10 70 0
      sleep 1
      echo 100 | /usr/bin/dialog --gauge "Done..." 10 70 0
      sleep 2
      clear
    fi
fi

if [  -f /etc/artica-postfix/security-center ]; then
  while true
  do
    MainMenuSecurityCenter
  done
  if [ -f $OUTPUT ]
   then
      rm $OUTPUT >/dev/null 2>&1
  fi
  if [ -f $INPUT ]
   then
      rm $INPUT >/dev/null 2>&1
  fi
  exit 0

fi

    if [ -f /etc/artica-postfix/.lockC ]
        then
            while true
              do
            pass1=$(dialog --title "Password to unlock console" --clear --insecure --passwordbox "Enter your password" 10 50 3>&1- 1>&2- 2>&3-)
            ret=$?
            case $ret in
                0)

                    pass2=`cat /etc/artica-postfix/.lockC`

                    if [ "$pass1" == "$pass2" ]
                        then
                            break
                        fi

                 ;;
                1) continue;;
                255) continue;;
            esac
            sleep 1
          done
        fi




if [ ! -f /etc/artica-postfix/microservice ]; then
$DIALOG --clear  --nocancel --backtitle "Firmware version $ARTICAVERSION on $HOSTNAME" \
--title "[ M A I N  A R T I C A - M E N U ]" \
--menu "$IPs\\nYou can use the UP/DOWN arrow keys\nChoose the TASK" 25 100 11 \
License "License and server information" \
Network "Modify server Network configuration" \
Ping "Ping a host" \
System "Root password and system tasks" \
KeyBoard "Keyboard,local,charset setup" \
Processes "Processes Monitor" \
WebInterface "Web console menu" \
HaCluster "HaCluster Connection settings" \
Reboot "Reboot this server" \
Shutdown "Shutdown this server" \
Exit "Exit to the shell" 2>"${INPUT}" || true
else
  IPs=`cat /etc/artica-postfix/MENU_SHOWIP`

  $DIALOG --clear  --nocancel --backtitle "Artica Micro Service" \
  --title "[ M A I N - M E N U ]" \
  --menu "\\n$IPs\\n\\nYou can use the UP/DOWN arrow keys\nChoose the TASK" 25 100 11 \
  License "License and server information" \
  LocalNet "Modify server Network configuration" \
  Ping "Ping a host" \
  HaCluster "HaCluster Connection settings" \
  System "Root password and system tasks" \
  KeyBoard "Keyboard,local,charset setup" \
  Processes "Processes Monitor" \
  Reboot "Reboot this server" \
  Shutdown "Shutdown this server" \
  Exit "Exit to the shell" 2>"${INPUT}" || true

fi
 
menuitem=$(<"${INPUT}")

case $menuitem in
	Network) menu_network;;
  LocalNet) menu_network_unix;;
  HaCluster) menu_hacluster;;
	Processes) htopp;;
	System) Systemp;;
  Ping) SystemPing;;
	WebInterface) WebInterfacep;;
	Statistics) Statisticsp;;
	Meta) Metap;;
	Reboot) UnixReboot;;
	KeyBoard) KeyBoard;;
	License) License;;
	Shutdown) init 0;;
	Exit) /bin/login.old;;
esac
 

 
# if temp files found, delete em
if [ -f $OUTPUT ]
 then
    rm $OUTPUT >/dev/null 2>&1
fi
if [ -f $INPUT ]
 then
    rm $INPUT >/dev/null 2>&1
fi
