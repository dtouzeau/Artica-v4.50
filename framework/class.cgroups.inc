<?php

class status_cgroups{
	
	
	function __construct(){
		
		
	}
	
	
	function GetLimit($pid=0){
		if($pid==0){return "none";}
		
		if(!is_file("/proc/$pid/cgroup")){
			return "none";
		}
		
		$f=explode("\n",@file_get_contents("/proc/$pid/cgroup"));
		
		foreach ($f as $index=>$line){
			if(preg_match("#cpu:\/(.*?)#", $line,$re)){
				$group=trim($re[1]);
				if($group==null){return "unlimited";}
				return $group;
			}
		}
	}
	
	
	function set_limit($group,$pid){
		if(!is_file("/usr/bin/cgclassify")){return;}
		if(!is_dir("/cgroups/blkio/$group")){return;}
		shell_exec("/usr/bin/cgclassify -g cpu,cpuset,blkio:$group $pid");
	}
	
	
	
	
	
}

