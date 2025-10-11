<?php
include_once(dirname(__FILE__) .'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) .'/ressources/class.system.nics.inc');


if($argv[1]=="--disable-networking"){
    $sock=new sockets();
    $sock->SET_INFO("DisableNetworking",1);
}

$unix=new unix();
$php=$unix->LOCATE_PHP5_BIN();
system("$php /usr/share/artica-postfix/exec.convert-to-sqlite.php");

$DETECTED_IP=null;
$DETECTED_NETMASK=null;
$DETECTED_GATEWAY=null;
$DNS=array();
$f=explode("\n",@file_get_contents("/etc/artica-postfix/NET_BACKUP/IPADDR"));

foreach ($f as $ipaddr){
	$ipaddr=trim($ipaddr);
	if($ipaddr==null){continue;}
	if($ipaddr=="127.0.0.1"){continue;}
	$DETECTED_IP=$ipaddr;
	break;
}

$f=explode("\n",@file_get_contents("/etc/artica-postfix/NET_BACKUP/NETMASK"));

foreach ($f as $netmask){
	$netmask=trim($netmask);
	if($netmask==null){continue;}
	$DETECTED_NETMASK=$netmask;
	break;
}
$f=explode("\n",@file_get_contents("/etc/artica-postfix/NET_BACKUP/GATEWAY"));
foreach ($f as $line){
	$line=trim($line);
	if($line==null){continue;}
	if(!preg_match("#default via\s+([0-9\.]+)\s+#", $line,$re)){continue;}
	$DETECTED_GATEWAY=$re[1];
	break;
}
$f=explode("\n",@file_get_contents("/etc/artica-postfix/NET_BACKUP/resolv.conf"));
foreach ($f as $line){
	$line=trim($line);
	if($line==null){continue;}
	if(!preg_match("#nameserver\s+([0-9\.]+)#", $line,$re)){continue;}
	if($re[1]=="127.0.0.1"){continue;}
	$DNS[]=$re[1];
}



	echo "NETWORK: $DETECTED_IP/$DETECTED_NETMASK GATEWAY: $DETECTED_GATEWAY DNS1: {$DNS[0]}\n";
	
	if( $DETECTED_IP==null){die();}
	if( $DETECTED_NETMASK==null){die();}
	if( $DETECTED_GATEWAY==null){die();}

	$brt=explode(".",$DETECTED_IP);
	$BROADCAST="{$brt[0]}.{$brt[1]}.{$brt[2]}.255";

	$nics=new system_nic("eth0");
	$nics->IPADDR=$DETECTED_IP;
	$nics->NETMASK=$DETECTED_NETMASK;
	$nics->GATEWAY=$DETECTED_GATEWAY;
	$nics->BROADCAST=$BROADCAST;
	$nics->DNS1=$DNS[0];
	$nics->DNS2=$DNS[1];
	$nics->dhcp=0;
	$nics->metric=1;
	$nics->enabled=1;
	$nics->defaultroute=1;
	$nics->SaveNic();
	
	$resolv=new resolv_conf();
	$resolv->MainArray["DNS1"]=$DNS[0];
	$resolv->MainArray["DNS2"]=$DNS[1];
	$resolv->save();

	system("$php /usr/share/artica-postfix/exec.virtuals-ip.php --build");

