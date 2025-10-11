<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["btrfs-convert"])){convert_dev();exit;}
if(isset($_GET["btrfs-scan"])){disks_scan();exit;}


function convert_dev(){
	$unix=new unix();
	$dev=$_GET["btrfs-convert"];
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.brtfs.php --convert \"{$dev}\" >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
	
	
}

function disks_scan(){
	$unix=new unix();
	$btrfs=$unix->find_program("btrfs");
	$blkid=$unix->find_program("blkid");
	$cmd="$btrfs filesystem show 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	
	foreach ($results as $num=>$ligne){
		
		if(preg_match("#Label:\s+'(.*?)'\s+uuid:\s+(.+)#i", $ligne,$re)){
			$UUID=$re[2];
			$array[$UUID]["LABEL"]=$re[1];
			$array[$UUID]["DEV"]=exec("$blkid -U $UUID");
			$array[$UUID]["MOUNTED"]=$unix->MOUNTED_PATH($array[$UUID]["DEV"]);
			$array[$UUID]["DF"]=$unix->BLKID_INFOS($array[$UUID]["DEV"]);
			continue;
			
		}
		if(preg_match("#Total devices.+?FS bytes used (.+)#",  $ligne,$re)){
			$array[$UUID]["USED"]=$re[1];
			continue;
		}
		
		if(preg_match("#devid\s+([0-9]+)\s+size\s+(.+?)\s+used\s+(.+?)\s+path\s+(.+)#",  $ligne,$re)){
			writelogs_framework("$UUID: $ligne",__FUNCTION__,__FILE__,__LINE__);
			writelogs_framework("$UUID: {$re[4]}: SIZE: {$re[2]}",__FUNCTION__,__FILE__,__LINE__);
			
			$array[$UUID]["DEVICES"][$re[1]]["SIZE"]=$re[2];
			$array[$UUID]["DEVICES"][$re[1]]["USED"]=$re[3];
			$array[$UUID]["DEVICES"][$re[1]]["DEV"]=$re[4];
		}
		
		
	}
	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
	
}
