<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["status"])){status();exit;}
if(isset($_GET["conf"])){build_local_conf();exit;}
if(isset($_GET["watchdog"])){watchdog();exit;}
if(isset($_GET["master-events"])){master_events();exit;}
if(isset($_GET["client-dismount"])){client_dismount();exit;}
if(isset($_GET["probes"])){probes();exit;}
if(isset($_GET["volume-info"])){volume_info();exit;}
if(isset($_GET["delete-volume"])){volume_delete();exit;}

writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);	




function build_local_conf(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.gluster.php --conf 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
}
function master_events(){
	$rp=intval($_GET["rp"]);
	if(!is_numeric($rp)){$rp=10;}
	$unix=new unix();
	$tail=$unix->find_program("tail");

	
	$cmd=trim("$tail -n $rp /var/log/glusterfs/glusterfs.log 2>&1");	
	
	if($_GET["query"]<>null){
		$grep=$unix->find_program("grep");
		$s=base64_decode($_GET["query"]);
		$cmd=trim("$grep --binary-files=text -i -E \"$s\" /var/log/glusterfs/glusterfs.log|$tail -n $rp  2>&1");
	}	
	
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";	
	
	
}

function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --gluster 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";	
}

function client_dismount(){
$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.gluster.php --client-dismount 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	

}
function probes(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.gluster.php --probes 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
}

function volume_info(){
	$unix=new unix();
	$gluster=$unix->find_program("gluster");
	$VOLS=array();
	exec("$gluster volume info 2>&1",$results);

foreach ($results as $num=>$ligne){
	if(preg_match("#Volume Name:\s+(.+)#", $ligne,$re)){
		$volume_name=trim($re[1]);
		continue;
	}
	
	if(preg_match("#Volume ID:\s+(.+)#", $ligne,$re)){
		$VOLS[$volume_name]["ID"]=trim($re[1]);
		continue;
	}
	
	if(preg_match("#Status:\s+(.+)#", $ligne,$re)){
		$VOLS[$volume_name]["STATUS"]=trim(strtolower($re[1]));
		continue;
	}	
	
	if(preg_match("#Type:\s+(.+)#", $ligne,$re)){
		$VOLS[$volume_name]["TYPE"]=trim(strtolower($re[1]));
		continue;
	}

	if(preg_match("#Brick[0-9]+:\s+(.+)#", $ligne,$re)){
		$VOLS[$volume_name]["BRICKS"][]=trim(strtolower($re[1]));
		continue;
	}		
	
}	
	echo "<articadatascgi>". base64_encode(serialize($VOLS))."</articadatascgi>";	
}
function volume_delete(){
	$volume=base64_decode($_GET["delete-volume"]);
	$unix=new unix();
	$gluster=$unix->find_program("gluster");	
	$echo=$unix->find_program("echo");
	$cmd="$echo y|$gluster volume stop $volume force 2>&1";
	exec($cmd,$results);
	writelogs_framework("$cmd -> ".@implode(" ", $results),__FUNCTION__,__FILE__,__LINE__);$results=array();
	$cmd="$echo y|$gluster volume delete $volume 2>&1"; 
	exec($cmd,$results);
	writelogs_framework("$cmd -> ".@implode(" ", $results),__FUNCTION__,__FILE__,__LINE__);$results=array();	
	
}

