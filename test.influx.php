<?php

include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squidguard.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.compile.ufdbguard.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.ufdbguard-tools.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");




	$unix=new unix();
	$klist=$unix->find_program("klist");
	exec("$klist -k /etc/squid3/krb5.keytab -t 2>&1",$results);
	
	foreach ($results as $num=>$line){
		$line=trim($line);
		$tr=explode(" ",$line);
		
		if(!is_numeric($tr[0])){continue;}
		$num=trim($tr[0]);
		$date=trim($tr[1])." ".trim($tr[2]);
		$tickets=trim($tr[3]);
		$array["NUM"]=$num;
		$array["DATE"]=$date;
		$array["ticket"]=$tickets;
		
	}

print_r($array);


die("DIE " .__FILE__." Line: ".__LINE__);
exec("/usr/share/artica-postfix/bin/ps_mem.py -s 2>&1",$results);

foreach ($results as $num=>$line){
	if(!preg_match("#[0-9\.]+\s+[A-Z-a-z]+.*?[0-9\.]+\s+[A-Z-a-z]+\s+=\s+([0-9\.]+)\s+([A-Z-a-z]+)\s+(.+)#", $line,$re)){continue;}
	$MEMORY=$re[1];
	$UNIT=$re[2];
	$PROG=trim($re[3]);
	if($UNIT=="KiB"){continue;}
	
	
	if($UNIT=="MiB"){$MEMORY=$MEMORY*1024;}
	if($UNIT=="GiB"){$MEMORY=$MEMORY*1024;$MEMORY=$MEMORY*1024;}
	if(preg_match("#memcached\.pid#", $PROG)){$PROG="Memory Cache service";}
	if(preg_match("#slapd\.conf -u#", $PROG)){$PROG="OpenLDAP server";}
	if(preg_match("#monit\.state#", $PROG)){$PROG="System Watchdog";}
	if(preg_match("#\(ssl_crtd\)#", $PROG)){$PROG="Proxy SSL Client";}
	if(preg_match("#\(ntlm_auth\)#", $PROG)){$PROG="NTLM Authenticator";}
	if(preg_match("#\(squid-[0-9]+\)#", $PROG)){$PROG="Proxy Service";}
	if(preg_match("#\(squid-coord-[0-9]+\)#", $PROG)){$PROG="Proxy Service";}
	if(preg_match("#sshd:#", $PROG)){$PROG="OpenSSH server";}
	if(preg_match("#\/ufdbgclient\.php#", $PROG)){$PROG="Web Filtering client";}
	if(preg_match("#\/external_acl_response\.php#", $PROG)){$PROG="Proxy File Watcher";}
	if(preg_match("#\/external_acl_squid\.php#", $PROG)){$PROG="Proxy ACLs Watcher";}
	if(preg_match("#\/external_acl_squid_ldap\.php#", $PROG)){$PROG="Proxy Active Directory Watcher";}
	if(preg_match("#\/exec.ufdbguard-tail\.php#", $PROG)){$PROG="Web Filtering Watcher";}
	if(preg_match("#\/opt\/squidsql\#", $PROG)){$PROG="MySQL for Proxy";}
	if(preg_match("#\/var\/run\/mysqld\/mysqld\.sock#", $PROG)){$PROG="MySQL Server";}
	if(preg_match("#\/exec.cache-logs\.php#", $PROG)){$PROG="Proxy Real-time Watchdog";}
	if(preg_match("#\/exec\.syslog\.php#", $PROG)){$PROG="System Watchdog";}
	if(preg_match("#\/bin\/ufdbguardd#", $PROG)){$PROG="Web Filtering Service";}
	if(preg_match("#\/exec\.status\.php#", $PROG)){$PROG="Services Watchdog";}
	if(preg_match("#\/exec.auth-tail\.php#", $PROG)){$PROG="Authentication Watchdog";}
	if(preg_match("#bin\/apache2#", $PROG)){$PROG="Web Service";}
	if(preg_match("#winbindd -D#", $PROG)){$PROG="Winbind Daemon";}
	if(preg_match("#apache2\.conf -k start#", $PROG)){$PROG="Web Service";}
	if(preg_match("#exec\.web-community-filter\.php#", $PROG)){$PROG="Cloud Update process";}
	if(preg_match("#tmp --log-warnings=2 --default-storage-engine=myisam#", $PROG)){$PROG="MySQL server";}
	
	if(strpos($PROG, "/")>0){
		echo "$MEMORY $UNIT $PROG\n";
		if(strpos($PROG, " ")>0){$TR=explode(" ",$PROG);$PROG=$TR[0];}
	}
	$PROG=basename($PROG);
	$PROG=str_replace("(", "", $PROG);
	$PROG=str_replace(")", "", $PROG);
	
	if($PROG=="php5"){$PROG="php";}
	if(preg_match("#^squid-#", $PROG)){$PROG="Proxy Service";}
	if(!isset($MEM[$PROG])){$MEM[$PROG]=$MEMORY;}else{$MEM[$PROG]=$MEM[$PROG]+$MEMORY;}
}
print_r($MEM);