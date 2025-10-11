<?php
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.pdns.inc');
	
	
	echo trim(intval(@file_get_contents("/proc/sys/net/core/wmem_max")))."\n";
	
	
	$path="/media/Cachesdb/TOTO/proxy-caches/small-cpu1";
	
	$tt=explode("/",$path);
	$pathX=null;
	while (list ($none, $subdir) = each ($tt) ){
		if($subdir==null){continue;}
		$pathX=$pathX."/$subdir";
		echo $pathX."\n";
		
	}
	
	
	
	
	
	
	
	
?>