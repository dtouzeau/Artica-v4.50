<?php
//http://ftp.linux.org.tr/slackware/slackware_source/n/network-scripts/scripts/netconfig
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if($argv[1]=="--install"){install();exit;}


menu();

function install(){
	
	$unix=new unix();
	$unix->DEBIAN_INSTALL_PACKAGE("console-setup");
	if(is_file("/bin/setupcon")){
	echo "* * * * * SUCCESS * * * * *\n";
	return;
	}
	echo "* * * * * FAILED * * * * *\n";
}


function menu(){
$ARTICAVERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
$unix=new unix();
$HOSTNAME=$unix->hostname_g();
$DIALOG=$unix->find_program("dialog");	
$php=$unix->LOCATE_PHP5_BIN();
$php5=$unix->LOCATE_PHP5_BIN();
$sock=new sockets();
$ArticaMetaUsername=$sock->GET_INFO("ArticaMetaUsername");
$ArticaMetaPassword=$sock->GET_INFO("ArticaMetaPassword");
$ArticaMetaHost=$sock->GET_INFO("ArticaMetaHost");
$ArticaMetaPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMetaPort"));
if($ArticaMetaPort==0){$ArticaMetaPort=9000;}
$EnableArticaMetaServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMetaServer"));
$sock=new sockets();
    $uuid=$unix->GetUniqueID();
$users=new usersMenus();
if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
	$text[]="";
	$text[]="Community Edition";
	$text[]="*************************************";
	$text[]="";
	$text[]="You can use it on unlimited time";
    $text[]="Without corporate features.";
	$text[]="";
    $text[]="UUID...............: $uuid";
	$text[]="Corporate features are disabled:";
	$text[]="\t- No Active Directory connection";
	$text[]="\t- No cache management";
	$text[]="\t- No personal categories";
	$text[]="\t- Statistics are limited to 5 days";
	
	$f[]="#!/bin/bash";
	$f[]="INPUT=/tmp/menu.sh.$$";
	$f[]="OUTPUT=/tmp/output.sh.$$";
	$f[]="trap \"rm -f \$OUTPUT; rm -f \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
	$f[]="DIALOG=\${DIALOG=dialog}";	
	$f[]="\t$DIALOG --title \"Community Edition\" --msgbox \"".@implode("\\n", $text)."\" 20 70";
	$f[]="";
	@file_put_contents("/home/artica/tmp/bash_license_menu.sh", @implode("\n",$f));
	@chmod("/home/artica/tmp/bash_license_menu.sh",0755);
	exit();
	
}
$LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
$WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
$FINAL_TIME=0;
if(isset($LicenseInfos["FINAL_TIME"])){$FINAL_TIME=intval($LicenseInfos["FINAL_TIME"]);}

$text[]="";
$text[]="Enterprise Edition";
$text[]="*************************************";
$text[]="";
$text[]="License affected to: {$LicenseInfos["COMPANY"]}";
$text[]="Owner..............: {$LicenseInfos["EMAIL"]}";
$text[]="UUID...............: $uuid";
if($FINAL_TIME==0){
$text[]="Expire.............: Never";
}



if($FINAL_TIME>0){
	$tpl=new templates();
	$dateT=date("Y l F d",$FINAL_TIME);
	$text[]="Expire.............: $dateT (".distanceOfTimeInWords(time(),$FINAL_TIME).")";
	
}
$text[]="";
$f[]="#!/bin/bash";
$f[]="INPUT=/tmp/menu.sh.$$";
$f[]="OUTPUT=/tmp/output.sh.$$";
$f[]="trap \"rm -f \$OUTPUT; rm -f \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
$f[]="DIALOG=\${DIALOG=dialog}";
$f[]="\t$DIALOG --title \"Enterprise Edition\" --msgbox \"".@implode("\\n", $text)."\" 20 70";
$f[]="";
@file_put_contents("/home/artica/tmp/bash_license_menu.sh", @implode("\n",$f));
@chmod("/home/artica/tmp/bash_license_menu.sh",0755);
exit();


$diag[]="$DIALOG --clear  --nocancel --backtitle \"Software version $ARTICAVERSION on $HOSTNAME\"";
$diag[]="--title \"[ M E T A  M E N U ]\"";
$diag[]="--menu \"You can use the UP/DOWN arrow keys\nChoose the TASK\" 20 100 10";
$DPREF="/etc/artica-postfix/settings/Daemons";

if(!is_file(("/bin/setupcon"))) {
    $diag[] = "INSTALL \"Install the keyboard wizard\"";
}else {
    $diag[] = "KEYBOARD \"Keyboard settings wizard\"";
}

$diag[]="LOCALES \"Regional settings wizard\"";
$diag[]="LANGUAGE \"Language settings wizard\"";
$diag[]="Quit \"Return to main menu\" 2>\"\${INPUT}\"";

$f[]="#!/bin/bash";
$f[]="INPUT=/tmp/menu.sh.$$";
$f[]="OUTPUT=/tmp/output.sh.$$";
$f[]="trap \"rm -f \$OUTPUT; rm -f \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
$f[]="DIALOG=\${DIALOG=dialog}";

$f[]="function INSTALL(){";
$f[]="if [ -f /bin/setupcon ]; then";
$f[]="\t$DIALOG --title \"Already installed\" --msgbox \"The module is already installed\" 9 70";
$f[]="\treturn";
$f[]="fi";
$f[]="\t$php5 ". __FILE__." --install >/tmp/dns.log 2>&1 &";
$f[]="\t$DIALOG --tailbox /tmp/dns.log  25 150";
$f[]="}";
$f[]="";

    $f[]="function LOCALES(){";
    $f[]="\t/usr/sbin/dpkg-reconfigure locales";
    $f[]="}";
    $f[]="";

$f[]="";
$f[]="function RUNZ(){";
$f[]="if [ ! -f /bin/setupcon ]; then";
$f[]="\t$DIALOG --title \"Not installed\" --msgbox \"You need to install the module first\" 9 70";
$f[]="\treturn";
$f[]="fi";

$f[]="/usr/sbin/dpkg-reconfigure keyboard-configuration";
$f[]="/bin/setupcon";
$f[]="\t$DIALOG --title \"Reboot required\" --yesno \"You need to reboot server to apply settings.\\nDo you need to perform this operation ? Press 'Yes' to continue, or 'No' to exit\" 0 0";
$f[]="\tcase $? in";
$f[]="\t\t0)";
$f[]="\t\t/sbin/reboot";
$f[]="\t\treturn;;";
$f[]="\t1)";
$f[]="\t\treturn;;";
$f[]="\t255)";
$f[]="\t\treturn;;";
$f[]="\tesac";
$f[]="}";
$f[]="";
$f[]="";
$f[]="function RUNY(){";
$f[]="if [ ! -f /bin/setupcon ]; then";
$f[]="\t$DIALOG --title \"Not installed\" --msgbox \"You need to install the module first\" 9 70";
$f[]="\treturn";
$f[]="fi";
$f[]="/usr/sbin/dpkg-reconfigure console-setup";
$f[]="/bin/setupcon";
$f[]="\t$DIALOG --title \"Reboot required\" --yesno \"You need to reboot server to apply settings.\\nDo you need to perform this operation ? Press 'Yes' to continue, or 'No' to exit\" 0 0";
$f[]="\tcase $? in";
$f[]="\t\t0)";
$f[]="\t\t/sbin/reboot";
$f[]="\t\treturn;;";
$f[]="\t1)";
$f[]="\t\treturn;;";
$f[]="\t255)";
$f[]="\t\treturn;;";
$f[]="\tesac";
$f[]="}";
$f[]="";


$f[]="while true";
$f[]="do";
$f[]=@implode(" ", $diag);
$f[]="menuitem=$(<\"\${INPUT}\")";
$f[]="case \$menuitem in";
$f[]="INSTALL) INSTALL;;";
$f[]="KEYBOARD) RUNZ;;";
$f[]="LANGUAGE) RUNY;;";

$f[]="Quit) break;;";
$f[]="esac";
$f[]="done\n";

if($GLOBALS["VERBOSE"]){echo "Writing /home/artica/tmp/bash_license_menu.sh\n";}
@file_put_contents("/home/artica/tmp/bash_license_menu.sh", @implode("\n",$f));
@chmod("/home/artica/tmp/bash_license_menu.sh",0755);
	
}

function interface_menu($eth){
	$ARTICAVERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$unix=new unix();
	$HOSTNAME=$unix->hostname_g();
	$DIALOG=$unix->find_program("dialog");
	$php=$unix->LOCATE_PHP5_BIN();	
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$DEFAULT=$NETWORK_ALL_INTERFACES[$eth]["IPADDR"];
	$NETMASK=$NETWORK_ALL_INTERFACES[$eth]["NETMASK"];
	$GATEWAY=$NETWORK_ALL_INTERFACES[$eth]["GATEWAY"];
	
	$f[]="#!/bin/bash";
	$f[]="INPUT=/tmp/menu.sh.$$";
	$f[]="OUTPUT=/tmp/output.sh.$$";
	$f[]="trap \"rm -f \$OUTPUT; rm -f \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
	$f[]="DIALOG=\${DIALOG=dialog}";	
	@unlink("/etc/artica-postfix/WIZARDIP_$eth");
	@unlink("/etc/artica-postfix/WIZARDMASK_$eth");
	$f[]="$DIALOG --clear --title \"ENTER IP ADDRESS FOR '$eth'\" --inputbox \"Enter your IP address for the $eth Interface.\\nExample: 111.112.113.114\" 10 68 $DEFAULT 2> /etc/artica-postfix/WIZARDIP_$eth";
	
	$f[]="if [ $? = 1 -o $? = 255 ]; then";
	$f[]="rm -f /etc/artica-postfix/WIZARDIP_$eth";
	$f[]="\treturn";
	$f[]="fi";
	
	
	$f[]="$DIALOG --clear --title \"ENTER IP ADDRESS FOR '$eth'\" --inputbox \"Enter your netmask for the $eth Interface.\\nExample: 255.255.255.0\" 10 68 $NETMASK 2> /etc/artica-postfix/WIZARDMASK_$eth";
	$f[]="if [ $? = 1 -o $? = 255 ]; then";
	$f[]="rm -f /etc/artica-postfix/WIZARDMASK_$eth";
	$f[]="\treturn";
	$f[]="fi";
	
	$f[]="$DIALOG --clear --title \"ENTER IP ADDRESS FOR '$eth'\" --inputbox \"Enter your gateway for the $eth Interface.\\nExample: 111.112.113.114\\nIf this interface is the main gateway of your network, set 0.0.0.0 here\" 10 68 $GATEWAY 2> /etc/artica-postfix/WIZARDGATEWAY_$eth";
	$f[]="if [ $? = 1 -o $? = 255 ]; then";
	$f[]="rm -f /etc/artica-postfix/WIZARDGATEWAY_$eth";
	$f[]="\treturn";
	$f[]="fi";	
	
	$f[]="WIZARDIP=`cat /etc/artica-postfix/WIZARDIP_$eth`";
	$f[]="WIZARDMASK=`cat /etc/artica-postfix/WIZARDMASK_$eth`";
	$f[]="WIZARDGATEWAY=`cat /etc/artica-postfix/WIZARDGATEWAY_$eth`";
	
	$f[]="$DIALOG --title \"NETWORK SETUP COMPLETE\" --yesno \"Your networking system is now configured to use:\\n\$WIZARDIP/\$WIZARDMASK Gateway \$WIZARDGATEWAY\\nIs this correct?  Press 'Yes' to continue, or 'No' to exit\" 0 0";
  	$f[]="case $? in";
  	$f[]="0)";
  	$f[]="\techo \"$php ".__FILE__." --savenic $eth\"";
    $f[]="\t$php ".__FILE__." --savenic $eth >/tmp/$eth.log &";
    $f[]="\t$DIALOG --tailbox /tmp/$eth.log  25 150"; 
    
    $f[]="\tWIZARDRESULTS=`cat /etc/artica-postfix/WIZARDRESULT_$eth`";
  	$f[]="\tif [ \"\$WIZARDRESULTS\" eq 0 ]; then";
  	$f[]="\t$DIALOG --title \"$eth failed\" --msgbox \"Sorry, An error has occured\" 9 70";
  	$f[]="\tfi";
    $f[]="\treturn;;";
	$f[]="1)";
   	$f[]="\treturn;;";
  	$f[]="255)";
  	$f[]="\treturn;;";
	$f[]="esac";
	
	
	$f[]="\n";
	@file_put_contents("/tmp/bash_keyboard_menu.sh", @implode("\n",$f));
	@chmod("/tmp/bash_keyboard_menu.sh",0755);
	
}

function savenic($NIC){
	$unix=new unix();
	$ipClass=new IP();
	$ETH_IP=trim(@file_get_contents("/etc/artica-postfix/WIZARDIP_$NIC"));
	$NETMASK=trim(@file_get_contents("/etc/artica-postfix/WIZARDMASK_$NIC"));
	$GATEWAY=trim(@file_get_contents("/etc/artica-postfix/WIZARDGATEWAY_$NIC"));
	
	if(!$ipClass->isIPAddress($ETH_IP)){
		echo "* * * * $ETH_IP * * * * WRONG !!!!\n";
		@file_put_contents("/etc/artica-postfix/WIZARDRESULT_$NIC", 0);
		return;
	}
	if(!$ipClass->isIPAddress($GATEWAY)){
		echo "* * * * $GATEWAY * * * * WRONG !!!!\n";
		@file_put_contents("/etc/artica-postfix/WIZARDRESULT_$NIC", 0);
		return;
	}	
	
	$nics=new system_nic($NIC);
	$nics->eth=$NIC;
	$nics->IPADDR=$ETH_IP;
	$nics->NETMASK=$NETMASK;
	$nics->GATEWAY=$GATEWAY;
	$nics->dhcp=0;
	$nics->metric=1;
	$nics->defaultroute=1;
	$nics->enabled=1;	
	
	if(!$nics->SaveNic()){
		echo "* * * * MYSQL ERROR !!! * * * * WRONG !!!!\n";
		@file_put_contents("/etc/artica-postfix/WIZARDRESULT_$NIC", 0);
		return;
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$php5=$php;
	system("$php5 ".dirname(__FILE__)." /exec.virtuals-ip.php --build --force >/dev/null 2>&1");
	echo "20%] Please Wait, apply network configuration....\n";
	system("/usr/sbin/artica-phpfpm-service -restart-network");
	echo "30%] Please Wait, restarting services....\n";
	
	$unix->THREAD_COMMAND_SET("$php5 /usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure");
	$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --reconfigure-cyrus");
	system("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	system("$nohup /etc/init.d/nginx restart >/dev/null 2>&1 &");
	system("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
	echo "30%] Please Wait, Changing IP address to $NIC....\n";
	$ifconfig=$unix->find_program("ifconfig");
	system("$ifconfig $NIC down");
		system("$ifconfig $NIC $ETH_IP netmask $NETMASK up");
		system("/bin/ip route add 127.0.0.1 dev lo");
		if($GATEWAY<>"0.0.0.0"){
		echo "31%] Please Wait, Define default gateway to $GATEWAY....\n";
		system("/sbin/route add $GATEWAY dev $NIC");
		$route=$unix->find_program("route");
		shell_exec("$route add -net 0.0.0.0 gw $GATEWAY dev $NIC metric 1");
		}
		echo "95%] Restarting Web Console\n";
		system("/etc/init.d/artica-webconsole restart");
		echo "100%] Configuration done.\n";
		@file_put_contents("/etc/artica-postfix/WIZARDRESULT_$NIC", 1);
	
		echo "###################################################\n";
		echo "############                          #############\n";
		echo "############         SUCCESS          #############\n";
		echo "############                          #############\n";
		echo "###################################################\n\n\n\n";
}

function savedns(){
	
	$DNS1=@file_get_contents("/etc/artica-postfix/WIZARDMASK_DNS1");
	$DNS2=@file_get_contents("/etc/artica-postfix/WIZARDMASK_DNS2");
	
	$resolv=new resolv_conf();
	echo "92%] Set DNS to $DNS1 - $DNS2\n";
	$resolv->MainArray["DNS1"]=$DNS1;
	$resolv->MainArray["DNS2"]=$DNS1;
	$resolv->output=true;
	echo "93%] Saving config\n";
	$resolv->save();
	echo "94%] Saving /etc/resolv.conf\n";
    if(!is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/resolvapply");
    }
	echo "###################################################\n";
	echo "############                          #############\n";
	echo "############         SUCCESS          #############\n";
	echo "############                          #############\n";
	echo "###################################################\n\n\n\n";
}

