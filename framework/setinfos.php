<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
if(isset($_GET["remove-path"])){@unlink("/etc/artica-postfix/settings/Daemons/{$_GET["remove-path"]}");exit;}

if(isset($_GET["SaveConfigFile"])){SaveConfigToFile();exit;} // Toujours en premier.
if(isset($_GET["cluster-key"])){CLUSTER_KEY();exit;}
if(isset($_GET["key"])){SaveKey();exit;}


foreach ($_GET as $key=>$val) {
    writelogs_framework("Fatal, cannot understand query $key","none","none",basename(__FILE__));
}


function SaveKey(){
	

	$value=base64_decode($_GET["value"]);
	$key=base64_decode($_GET["key"]);
    $DestPath="/etc/artica-postfix/settings/Daemons";
    @chown($DestPath, "www-data");
    @chgrp($DestPath, "www-data");
    @chmod("$DestPath", 0755);

    sys_events(basename(__FILE__)."::{$_SERVER['REMOTE_ADDR']}:: Save key $key (". strlen($_GET["value"]).") bytes length()");

	if(!is_dir("/etc/artica-postfix/settings/Daemons")){@mkdir("/etc/artica-postfix/settings/Daemons",0755,true);}
	@file_put_contents("$DestPath/$key", $value);

	if(is_file("$DestPath/$key")){
		@chmod("$DestPath/$key", 0755);
		@chown("$DestPath/$key", "www-data");
		@chgrp("$DestPath/$key", "www-data");
	}
	if(isset($_GET["verbose"])){echo "$key == '$value'\n";}
}
function SaveConfigToFile(){
    $debug=false;
    if(isset($_GET["verbose"])){$debug=true;}
    $CachePath="/usr/share/artica-postfix/ressources/conf";
	$file="$CachePath/{$_GET["SaveConfigFile"]}";

	$key=base64_decode($_GET["key"]);
	if($debug){echo "Key: $key<br>\n";}

	if(!is_file($file)){
        writelogs_framework("Fatal, ($key) Unable to stat source file $file",__FUNCTION__,__FILE__,__LINE__);
        return;
	}
    $DestPath="/etc/artica-postfix/settings/Daemons";
	$DestFile="$DestPath/$key";
    if($debug){echo "Source File      : $file<br>\n";}
    if($debug){echo "Destination File : $DestFile<br>\n";}
    @chown($DestPath, "www-data");
    @chgrp($DestPath, "www-data");

	if(is_file($DestFile)){@unlink($DestFile);}
	if(!copy($file,$DestFile)){
        writelogs_framework("FATAL: ($key) Unable to Copy $file,$DestFile",
            __FUNCTION__,__FILE__,__LINE__);
        @unlink($file);
        return;
    }

	if(is_file($DestFile)){
		@chmod($DestFile, 0755);
		@chown($DestFile, "www-data");
		@chgrp($DestFile, "www-data");
	}else{
		writelogs_framework("FATAL: ($key) Unable to stat $DestFile AFTER COPY",__FUNCTION__,__FILE__,__LINE__);
	}
	unlink($file);

}

function CLUSTER_KEY(){
	sys_events(basename(__FILE__)."::{$_SERVER['REMOTE_ADDR']}:: Save cluster key {$_GET["cluster-key"]} (". strlen($_GET["value"]).") bytes length()");	
	@copy("/usr/share/artica-postfix/ressources/logs/cluster/{$_GET["cluster-key"]}","/etc/artica-cluster/{$_GET["cluster-key"]}");
	@unlink("/usr/share/artica-postfix/ressources/logs/cluster/{$_GET["cluster-key"]}");	
}


?>