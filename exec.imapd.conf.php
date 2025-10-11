<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(posix_getuid()<>0){
	die("Cannot be used in web server mode\n\n");
}

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
$line="";
$conf="";

if(!is_file("/etc/imapd.conf")){
	write_syslog("Unable to stat /etc/imapd.conf, aborting",__FILE__);
	exit(0);
}



$tbl=explode("\n",file_get_contents("/etc/imapd.conf"));
if(!is_array($tbl)){exit();}
foreach ($tbl as $num=>$ligne){
	if(preg_match("#^([a-z0-9\_\-]+):(.+)#",$ligne,$re)){
		$ri[trim($re[1])]=trim($re[2]);
		
	}
	
}

if(!is_array($ri)){exit();}
$sock= new sockets();
$CyrusPartitionDefault=$sock->GET_INFO("CyrusPartitionDefault");

if($ri["partition-default"]==null){
	$sock=new sockets();
	if($CyrusPartitionDefault<>null){$ri["partition-default"]=$CyrusPartitionDefault;}
	else{$ri["partition-default"]="/var/spool/cyrus/mail";}
}

while (list ($num, $ligne) = each ($ri) ){
	$conf=$conf . "$num:$ligne\n";
	}
echo $conf."\n";

write_syslog("Cleaning /etc/imapd.conf done with ". strlen($conf)+' bytes',__FILE__);
file_put_contents("/etc/imapd.conf",$conf);
exit();




?>