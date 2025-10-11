<?php
$GLOBALS["FORCE"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}


if($argv[1]=="--chassis"){
	if(!$GLOBALS["FORCE"]){
		if(is_file("/etc/artica-postfix/dmidecode.cache")){
			$datas=@file_get_contents("/etc/artica-postfix/dmidecode.cache");
			$newdatas=urlencode(base64_encode($datas));
			@file_put_contents("/etc/artica-postfix/dmidecode.cache.url", $newdatas);
			exit();
		}
	}
	
}


$cache_file="/etc/artica-postfix/dmidecode.cache";
if(!$GLOBALS["VERBOSE"]){
	if(is_file($cache_file)){
		$mem=file_time_min($cache_file);
		if($mem<240){return null;}
	}
}

    $unix=new unix();
    $LINUX_INFO_PATH="/etc/artica-postfix/settings/Daemons/LINUX_INFO_TXT";
    $LINUX_INFO_PATH_TIME=$unix->file_time_min($LINUX_INFO_PATH);
    if($LINUX_INFO_PATH_TIME>30){
        @chmod("/usr/share/artica-postfix/bin/linux-info.sh",0755);
        shell_exec("/usr/share/artica-postfix/bin/linux-info.sh --all >$LINUX_INFO_PATH 2>&1");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LINUX_INFO_TXT",@file_get_contents($LINUX_INFO_PATH));
    }




    $dmidecode=$unix->find_program("dmidecode");
    $virtwhat=$unix->find_program("virt-what");
if(is_file($dmidecode)){
    exec("$dmidecode -s system-serial-number 2>&1",$results);
    $block="([0-9a-z]+)";
    $pattern="VMware-$block\s+$block\s+$block\s+$block\s+$block\s+$block\s+$block\s+$block-$block\s+$block\s+$block\s+$block\s+$block\s+$block\s+$block\s+$block";
    if(preg_match("#$pattern#",$results[0],$re)){
        $vmware_serial="{$re[1]}{$re[2]}{$re[3]}{$re[4]}-{$re[5]}{$re[6]}-{$re[7]}{$re[8]}-{$re[9]}{$re[10]}-{$re[11]}{$re[12]}{$re[13]}{$re[14]}{$re[15]}{$re[16]}";
        $final_array["VMWARE_SERIAL"]=$vmware_serial;
    }else{
        if($GLOBALS["VERBOSE"]){echo "system-serial-number: $pattern -> {$results[0]} -> False;\n";}
    }


    $results=array();
	exec("$dmidecode --type 1 2>&1",$results);
	foreach ($results as $index=>$line){
		
		if(preg_match("#Manufacturer:\s+(.+)#",$line,$re)){
			$Manufacturer=$re[1];
		}
		
		if(preg_match("#Product Name:\s+(.+)#",$line,$re)){
			$ProductName=$re[1];
		}	
	}
	unset($results);
	exec("$dmidecode --type 3 2>&1",$results);
	foreach ($results as $index=>$line){
		
		if(preg_match("#Manufacturer:\s+(.+)#",$line,$re)){
			$chassisManufacturer=$re[1];
		}
		
		
	}
	
}
$PROCS=array();
unset($results);
$f=@explode("\n",@file_get_contents("/proc/cpuinfo"));
foreach ( $f as $index=>$line ){
	if(preg_match("#processor\s+:\s+([0-9]+)#",$line,$re)){
		$proc=$re[1];
	}
	
	if(preg_match("#model name\s+:\s+(.+)#",$line,$re)){
		$PROCS[$proc]["MODEL"]=trim($re[1]);
		$PROCS[$proc]["MODEL"]=str_replace("  "," ",$PROCS[$proc]["MODEL"]);
	}
	if(preg_match("#cpu MHz\s+:\s+([0-9]+)#",$line,$re)){
		$found=$re[1];
		if($GLOBALS["VERBOSE"]){echo "Proc:$proc -> $found MHZ\n";}
		$found=$found/1000;
		if($GLOBALS["VERBOSE"]){echo "Proc:$proc -> $found MHZ\n";}
		$PROCS[$proc]["MHZ"]=round($found,2);
		if($GLOBALS["VERBOSE"]){echo "Proc:$proc -> {$PROCS[$proc]["MHZ"]} GHZ\n";}
	}
}




$final_array["MANUFACTURER"]=$Manufacturer;
$final_array["PRODUCT"]=$ProductName;
$final_array["CHASSIS"]=$chassisManufacturer;
$final_array["PROCESSORS"]=count($PROCS);
$final_array["MHZ"]=$PROCS[0]["MHZ"];
$final_array["PROC_TYPE"]=$PROCS[0]["MODEL"];
$virtwhatB=virtwhat();
if($virtwhatB<>null){
		$final_array["MANUFACTURER"]=$virtwhatB;
		$final_array["PRODUCT"]=$virtwhatB;
		$final_array["CHASSIS"]=$virtwhatB;
	}


if($GLOBALS["VERBOSE"]){print_r($final_array);}
$newdatas=urlencode(base64_encode(serialize($final_array)));
@file_put_contents("$cache_file",serialize($final_array));
@file_put_contents("/etc/artica-postfix/dmidecode.cache.url",$newdatas);
$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DMIDECODE_CACHE",serialize($final_array));


function virtwhat(){
	$unix=new unix();
	$virtwhat=$unix->find_program("virt-what");	
	if(!is_file($virtwhat)){return;}
	exec("$virtwhat 2>&1",$virtwhatA);
	$virtwhatB=trim(@implode(" ", $virtwhatA));
	if($GLOBALS["VERBOSE"]){echo "Found: $virtwhatB\n";}
	if(preg_match("#^(.+?)\s+#", $virtwhatB,$re)){$virtwhatB=$re[1];}
	if($GLOBALS["VERBOSE"]){echo "Found: $virtwhatB\n";}
	if($virtwhatB==null){return;}
	return $virtwhatB;
	
}
