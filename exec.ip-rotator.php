<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.acls.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=='--build'){build();exit();}



function build(){
	iptables_delete_all();
	$sql="SELECT * FROM ip_rotator_smtp ORDER BY ID";
	$mode["nth"]="{counter}";
	$mode["random"]="{random}";
	$unix=new unix();
	$itables=$unix->find_program("iptables");
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";}
	$count=mysqli_num_rows($results);
	
	echo "Starting......: ".date("H:i:s")." TCP/IP Rotator $count items\n";
	if($count==0){return;}
	
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$ipsrc=$ligne["ipsource"];
		$ipdest=$ligne["ipdest"];
		$mode=$ligne["mode"];
		$comment=" -m comment --comment \"ArticaIpRotator\"";
		if($mode=="nth"){$mode_text=" -m statistic --mode nth --every {$ligne["mode_value"]} ";}
		if($mode=="random"){$mode_text=" -m statistic --mode random --probability {$ligne["mode_value"]} ";}
		$cmdline="$itables -t nat -A PREROUTING -p tcp -d $ipsrc --dport 25 -m state --state NEW $mode_text --packet 0 -j DNAT --to-destination $ipdest $comment";
		if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
		$results=array();
		exec($cmdline,$results);
		foreach ($results as $a=>$b){echo "Starting......: ".date("H:i:s")." TCP/IP Rotator: $b\n";}
		
	}
	
}

function iptables_delete_all(){
	$unix=new unix();
	$itables_save=$unix->find_program("iptables-save");
	$itables_restore=$unix->find_program("iptables-restore");
	echo "Starting......: ".date("H:i:s")." TCP/IP Exporting datas\n";	
	system("$itables_save > /etc/artica-postfix/iptables.conf");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaIpRotator#";	
foreach ($datas as $num=>$ligne){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){
			echo "Starting......: ".date("H:i:s")." TCP/IP Rotator Deleting rule $num\n";
			continue;
		}
		$conf=$conf . $ligne."\n";
		}

echo "Starting......: ".date("H:i:s")." TCP/IP Rotator restoring datas\n";
file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
system("$itables_restore < /etc/artica-postfix/iptables.new.conf");


}

?>