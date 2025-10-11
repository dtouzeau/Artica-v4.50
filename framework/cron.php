<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["listfiles"])){listfiles();exit;}



function listfiles(){

    if(!$f=opendir("/etc/cron.d")){
        return;
    }

    if (!$handle = opendir("/etc/cron.d")) {return;}
    while (false !== ($filename = readdir($handle))) {
        if ($filename == ".") {
            continue;
        }
        if ($filename == "..") {
            continue;
        }
        $array[$filename] = true;
    }

    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/cron.lists",serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/web/cron.lists",0755);
	
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
