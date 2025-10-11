<?php
//http://ftp.linux.org.tr/slackware/slackware_source/n/network-scripts/scripts/netconfig
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
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

if($argv[1]=="--menu"){menu();exit;}
if($argv[1]=="--restore"){restore();exit;}




function menu(){
$ARTICAVERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
$unix=new unix();
$HOSTNAME=$unix->hostname_g();
$DIALOG=$unix->find_program("dialog");	
$php=$unix->LOCATE_PHP5_BIN();


$diag[]="$DIALOG --clear  --nocancel --backtitle \"Software version $ARTICAVERSION on $HOSTNAME\"";
$diag[]="--title \"[ S N A P S H O T S  M E N U ]\"";
$diag[]="--menu \"You can use the UP/DOWN arrow keys\nChoose the TASK\" 20 100 10";
$diag[]="CREATE \"Build a SnapShot\"";
$diag[]="RESTORE \"Restore a Snapshot\"";
$diag[]="Quit \"Return to main menu\" 2>\"\${INPUT}\"";

$f[]="#!/bin/bash";
$f[]="INPUT=/tmp/menu.sh.$$";
$f[]="OUTPUT=/tmp/output.sh.$$";
$f[]="trap \"rm -f \$OUTPUT; rm -f \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
$f[]="DIALOG=\${DIALOG=dialog}";

$f[]="function CREATE(){";
$f[]="\t$DIALOG --title \"Create a Snapshot\" --yesno \"Do you need to perform this operation ? Press 'Yes' to continue, or 'No' to exit\" 0 0";
$f[]="\tcase $? in";
$f[]="\t\t0)";
$f[]="\tif [ -f /tmp/dns.log ]; then";
$f[]="\t\trm /tmp/dns.log";
$f[]="\tfi";
$f[]="\t/usr/sbin/artica-phpfpm-service -snapshot-create -debug >/tmp/dns.log 2>&1 &";
$f[]="\t$DIALOG --tailbox /tmp/dns.log  25 150";
$f[]="\t\treturn;;";
$f[]="\t1)";
$f[]="\t\treturn;;";
$f[]="\t255)";
$f[]="\t\treturn;;";
$f[]="\tesac";
$f[]="}";
$f[]="";
$f[]="function RESTORE(){";
$f[]="\t$php ".__FILE__." --restore";
$f[]="/tmp/bash_snapshots_restore.sh";
$f[]="}";

$f[]="while true";
$f[]="do";
$f[]=@implode(" ", $diag);
$f[]="menuitem=$(<\"\${INPUT}\")";
$f[]="case \$menuitem in";
$f[]="CREATE) CREATE;;";
$f[]="RESTORE) RESTORE;;";
$f[]="Quit) break;;";
$f[]="esac";
$f[]="done\n";

if($GLOBALS["VERBOSE"]){echo "Writing /tmp/bash_snapshots_menu.sh\n";}
@file_put_contents("/tmp/bash_snapshots_menu.sh", @implode("\n",$f));
@chmod("/tmp/bash_snapshots_menu.sh",0755);
	
}


function restore(){
	$ARTICAVERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$unix=new unix();
	$HOSTNAME=$unix->hostname_g();
	$DIALOG=$unix->find_program("dialog");
	$php=$unix->LOCATE_PHP5_BIN();
	$q=new mysql();
	$table="snapshots";
	$database="artica_snapshots";
	
	$sql="SELECT * FROM $table";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	if(mysqli_num_rows($results)==0){
		$f[]="#!/bin/bash";
		$f[]="INPUT=/tmp/menu.sh.$$";
		$f[]="OUTPUT=/tmp/output.sh.$$";
		$f[]="trap \"rm -f \$OUTPUT; rm -f \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
		$f[]="DIALOG=\${DIALOG=dialog}";
		$f[]="\t$DIALOG --title \"R E S T O R E\" --msgbox \"Sorry, no snapshot created\" 9 70";
		if($GLOBALS["VERBOSE"]){echo "Writing /tmp/bash_snapshots_restore.sh\n";}
		@file_put_contents("/tmp/bash_snapshots_restore.sh", @implode("\n",$f));
		@chmod("/tmp/bash_snapshots_restore.sh",0755);
		return;
	}
	
	
	
	$diag[]="$DIALOG --clear  --nocancel --backtitle \"Software version $ARTICAVERSION on $HOSTNAME\"";
	$diag[]="--title \"[ S N A P S H O T S  - R E S T O R E - M E N U ]\"";
	$diag[]="--menu \"You can use the UP/DOWN arrow keys\nChoose the TASK\" 20 100 10";
	$tpl=new templates();
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$xdate=$ligne["zDate"];
		$xtime=strtotime($xdate);
		$date=$tpl->time_to_date($xtime,true);
		$diag[]="Restore$ID \"Restore $xdate - $date\"";
		$funcs[]="function restore_$ID(){";
		$funcs[]="\t$DIALOG --title \"Restore a Snapshot\" --yesno \"Do you need to restore $xdate - $date operation ? Press 'Yes' to continue, or 'No' to exit\" 0 0";
		$funcs[]="\tcase $? in";
		$funcs[]="\t\t0)";
		$funcs[]="\tif [ -f /tmp/dns.log ]; then";
		$funcs[]="\t\trm /tmp/dns.log";
		$funcs[]="\tfi";
		$funcs[]="\t$php /usr/share/artica-postfix/exec.backup.artica.php --snapshot-id $ID >/tmp/dns.log &";
		$funcs[]="\t$DIALOG --tailbox /tmp/dns.log  25 150";
		$funcs[]="\t\treturn;;";
		$funcs[]="\t1)";
		$funcs[]="\t\treturn;;";
		$funcs[]="\t255)";
		$funcs[]="\t\treturn;;";
		$funcs[]="\tesac";
		$funcs[]="}";
		$funcs[]="";
		$cases[]="Restore$ID) restore_$ID;;";
		
		
	}
	

	$diag[]="Quit \"Return to main menu\" 2>\"\${INPUT}\"";
	
	$f[]="#!/bin/bash";
	$f[]="INPUT=/tmp/menu.sh.$$";
	$f[]="OUTPUT=/tmp/output.sh.$$";
	$f[]="trap \"rm -f \$OUTPUT; rm -f \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
	$f[]="DIALOG=\${DIALOG=dialog}";
	
	@implode("\n", $funcs);

	
	$f[]="while true";
	$f[]="do";
	$f[]=@implode(" ", $diag);
	$f[]="menuitem=$(<\"\${INPUT}\")";
	$f[]="case \$menuitem in";
	$f[]=@implode("\n", $cases);
	$f[]="Quit) break;;";
	$f[]="esac";
	$f[]="done\n";
	
	if($GLOBALS["VERBOSE"]){echo "Writing /tmp/bash_snapshots_restore.sh\n";}
	@file_put_contents("/tmp/bash_snapshots_restore.sh", @implode("\n",$f));
	@chmod("/tmp/bash_snapshots_restore.sh",0755);	
	
	
	
}




