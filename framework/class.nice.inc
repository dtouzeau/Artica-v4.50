<?php

class exec_nice{
	public $nice;
function __construct(){	
	

	$tmp=null;
	$ArticaInCgroups=0;
	$cgroupsEnabled=0;
	$useIonice=1;
	$useNice=1;
    $nice=null;
	$ArticaCgroup=0;
	$useIonice=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useIonice"));
	$useNice=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useNice"));

    if(is_dir("/cgroup/blkio/php")){
        $cgroupsEnabled=1;
    }
    if($cgroupsEnabled==1){
		if(!is_file("/usr/bin/cgexec")){
			if($GLOBALS["VERBOSE"]){echo "exec_nice/exec_nice: /usr/bin/cgexec no such bin cgroupsEnabled = 0\n";}
			$cgroupsEnabled=0;
		}
    }
    if($cgroupsEnabled==1){
        $this->nice="/usr/bin/cgexec -g cpu,cpuset,blkio:php ";
		return $this->nice;
	}
	
    if(!class_exists("Bs_IniHandler")){
		$ini=new iniFrameWork("/etc/artica-postfix/performances.conf");
	}else{
		$ini=new Bs_IniHandler("/etc/artica-postfix/performances.conf");
	}
	
	
	if(isset($ini->_params)){
		if(isset($ini->_params["PERFORMANCES"]['ProcessNice'])){
			$tmp=intval($ini->_params["PERFORMANCES"]['ProcessNice']);
		}
	}
	if($tmp==0){$tmp=19;}
	
	if($useIonice==1){
		if(is_file("/usr/bin/ionice")){
			$ionice="/usr/bin/ionice -c2 -n7 ";
		}
	}
	
	if($useNice==1){
		if(is_file("/usr/bin/nice")){$nice="/usr/bin/nice --adjustment=$tmp ";}
	}
	if(!isset($ionice)){$ionice=null;}
	$this->nice= "$ionice$nice";
	
}
}


