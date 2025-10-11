<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);




if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--menu"){menu();}

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
    $unix=new unix();
    $DIALOG=$unix->find_program("dialog");



    $ARTICAVERSION=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
    if(is_file("/usr/share/artica-postfix/SP/$ARTICAVERSION")){
        $SP=intval(trim(@file_get_contents("/usr/share/artica-postfix/SP/$ARTICAVERSION")));
        if($SP>0){$ARTICAVERSION="$ARTICAVERSION Service Pack $SP";}
    }

$diag[]="$DIALOG --clear  --nocancel --backtitle \"Artica version $ARTICAVERSION\"";
$diag[]="--title \"[ Remote Control using DWService ]\"";
$diag[]="--menu \"You can use the UP/DOWN arrow keys\nChoose the TASK\" 20 100 10";

    $DWAgentKeyMenu=null;
    $DWAgentKey = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DWAgentKey"));
    if($DWAgentKey<>null){
        if(preg_match("#^[0-9]+-[0-9]+-[0-9]+$#",$DWAgentKey)) {
            $DWAgentKeyMenu = $DWAgentKey;
            $DWAgentKey = " ($DWAgentKey)";
        }
    }

if(is_file("/etc/init.d/dwagent")) {
    $diag[] = "UNINSTALL \"Uninstall DWService\"";
    $diag[] = "RESTART \"Restart DWservice\"";
    $diag[] = "KEY \"Set the Remote Control Key{$DWAgentKey}\"";
}else{
    $diag[] = "INSTALL \"Install DWService\"";
}
$diag[]="Quit \"Return to main menu\" 2>\"\${INPUT}\"";

$f[]="#!/bin/bash";
$f[]="INPUT=/tmp/menu.sh.$$";
$f[]="OUTPUT=/tmp/output.sh.$$";
$f[]="trap \"rm -f \$OUTPUT; rm -f \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
$f[]="DIALOG=\${DIALOG=dialog}";



$f[]="function SETUP_KEY(){";
if($DWAgentKeyMenu==null) {
    $f[] = "\t$DIALOG --clear --title \"Remote Control Key\" --inputbox \"Enter the Remote Control Key\" 10 68 \"000-000-000\" 2> /etc/artica-postfix/DWSERVICE_SETUP_KEY";
}else{
    $f[] = "\t$DIALOG --clear --title \"Remote Control Key\" --inputbox \"Enter the Remote Control Key\" 10 68 \"$DWAgentKeyMenu\" 2> /etc/artica-postfix/DWSERVICE_SETUP_KEY";
}
$f[]="\tcase $? in";
    $f[]="\t\t1)";
    $f[]="\t\trm /etc/artica-postfix/DWSERVICE_SETUP_KEY || true";
    $f[]="\t\treturn";
    $f[]="\tesac";
    $f[]="\t\techo 50 | /usr/bin/dialog --gauge  \"Reconfiguring DWService Agent...\" 10 70 0";
    $f[]="/usr/bin/php /usr/share/artica-postfix/exec.dwagent.php --reconfigure >/dev/null 2>&1";
    $f[]="\t\techo 70 | /usr/bin/dialog --gauge  \"Stopping DWService Agent...\" 10 70 0";
    $f[]="\t/etc/init.d/dwagent stop >/dev/null 2>&1";
    $f[]="\t\techo 80 | /usr/bin/dialog --gauge  \"Starting DWService Agent...\" 10 70 0";
    $f[]="\t/etc/init.d/dwagent start >/dev/null 2>&1";
    $f[]="\techo 100 | /usr/bin/dialog --gauge  \"Restarting DWService Agent success...\" 10 70 0";
    $f[]="\t/usr/bin/php /usr/share/artica-postfix/exec.menu.dwservice.php --menu";
    $f[]="\t/tmp/bash_dwservice_menu.sh";
    $f[]="\texit";
$f[]="}\n";


$f[]="function INSTALL(){";
    $f[]="\t$DIALOG --title \"Install Remote Control service\" --yesno \"This operation will install the Remote Control agent service\nAfter installing the service you will be able to set the Remote Control Key\nPress 'Yes' to continue, or 'No' to exit\" 0 0";
    $f[]="\tcase $? in";
    $f[]="\t0)";
    $f[]="\t\techo 50 | /usr/bin/dialog --gauge  \"Installing DWService Agent...\" 10 70 0";
    $f[]="\t\t/usr/bin/php /usr/share/artica-postfix/exec.dwagent.php --install >/dev/null 2>&1";


    $f[]="\t\tif [ ! -f /etc/init.d/dwagent ]";
    $f[]="\t\tthen";
    $f[]="\t\t\t/usr/bin/dialog --title \"\Zb\Z1ERROR! ERROR!\" --colors --infobox \"\Zb\Z1Installation failed\nPress OK to return back to menu\"  0 0";
    $f[]="\t\t\treturn";
    $f[]="\t\tfi";

    $f[]="\t\techo 100 | /usr/bin/dialog --gauge  \"Installing DWService Agent success...\" 10 70 0";
    $f[]="\t\t/usr/bin/php /usr/share/artica-postfix/exec.menu.dwservice.php --menu";
    $f[]="\t\t/tmp/bash_dwservice_menu.sh";
    $f[]="\t\texit;;";
	$f[]="\tesac";
$f[]="}";
$f[]="";

    $f[]="function RESTART(){";
    $f[]="\t\techo 50 | /usr/bin/dialog --gauge  \"Stopping DWService Agent...\" 10 70 0";
    $f[]="\t/etc/init.d/dwagent stop >/dev/null 2>&1";
    $f[]="\t\techo 70 | /usr/bin/dialog --gauge  \"Starting DWService Agent...\" 10 70 0";
    $f[]="\t/etc/init.d/dwagent start >/dev/null 2>&1";
    $f[]="\techo 100 | /usr/bin/dialog --gauge  \"Restarting DWService Agent success...\" 10 70 0";
    $f[]="sleep 1";
    $f[]="}";
    $f[]="";


    $f[]="function UNINSTALL(){";
    $f[]="\t/usr/bin/dialog --title \"Uninstall Remote Control service\" --yesno \"This operation will uninstall the Remote Control agent service\nPress 'Yes' to continue, or 'No' to exit\" 0 0";
    $f[]="\tcase $? in";
    $f[]="\t0)";
    $f[]="\t\techo 50 | /usr/bin/dialog --gauge  \"Uninstalling DWService Agent...\" 10 70 0";
    $f[]="\t\t/usr/bin/php /usr/share/artica-postfix/exec.dwagent.php --uninstall >/dev/null 2>&1";
    $f[]="\t\tif [ -f /etc/init.d/dwagent ]";
    $f[]="\t\tthen";
    $f[]="\t\t\t/usr/bin/dialog --title \"\Zb\Z1ERROR! ERROR!\" --colors --infobox \"\Zb\Z1uninstallation failed\nPress OK to return back to menu\"  0 0";
    $f[]="\t\t\treturn";
    $f[]="\t\tfi";
    $f[]="\t\techo 100 | /usr/bin/dialog --gauge  \"Uninstalling DWService Agent success...\" 10 70 0";
    $f[]="\t\t/usr/bin/php /usr/share/artica-postfix/exec.menu.dwservice.php --menu";
    $f[]="\t\t/tmp/bash_dwservice_menu.sh";
    $f[]="\t\texit;;";
    $f[]="\tesac";
    $f[]="}";

$f[]="";
$f[]="";



$f[]="while true";
$f[]="do";
$f[]=@implode(" ", $diag);
$f[]="menuitem=$(<\"\${INPUT}\")";
$f[]="case \$menuitem in";
$f[]="INSTALL) INSTALL;;";
$f[]="UNINSTALL) UNINSTALL;;";
$f[]="RESTART) RESTART;;";
$f[]="KEY) SETUP_KEY;;";

$f[]="Quit) break;;";
$f[]="esac";
$f[]="done\n";

if($GLOBALS["VERBOSE"]){echo "Writing /tmp/bash_dwservice_menu.sh\n";}
@file_put_contents("/tmp/bash_dwservice_menu.sh", @implode("\n",$f));
@chmod("/tmp/bash_dwservice_menu.sh",0755);
	
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
	$resolv->save();
	echo "93%] Saving config\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/resolvapply");
	echo "94%] Saving /etc/resolv.conf\n";
	echo "###################################################\n";
	echo "############                          #############\n";
	echo "############         SUCCESS          #############\n";
	echo "############                          #############\n";
	echo "###################################################\n\n\n\n";
}

