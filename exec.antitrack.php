<?php
$PATTERN=array();
antitrack($argv[1],$argv[2]);

function antitrack($filepath,$destination){
	
	$f=explode("\n",@file_get_contents($filepath));
	$FINAL=unserialize(@file_get_contents($destination));
	
	foreach ($f as $index=>$line){
		if(trim($line)==null){continue;}
		$exp=explode("#",$line);
		if(!isset($exp[1])){continue;}

		if($exp[1]<>"true"){continue;}
		
		if($exp[4]=="regexuri"){
			$FINAL[$exp[5]]=true;
			continue;
		}
		
		if($exp[4]=="regexhost"){
			$FINAL[$exp[5]]=true;
			continue;
		}
		
		if($exp[4]=="nreghost"){
			$PATTERN=str_replace(".", "\.", $exp[5]);
			$PATTERN=str_replace("*", ".*", $PATTERN);
			$PATTERN=str_replace("/", "\/", $PATTERN);
			$FINAL[$PATTERN]=true;
			continue;
		}


		if($exp[4]=="nreguri"){
			$PATTERN=str_replace(".", "\.", $exp[5]);
			$PATTERN=str_replace("*", ".*", $PATTERN);
			$PATTERN=str_replace("/", "\/", $PATTERN);
			$FINAL[$PATTERN]=true;
			continue;
		}		
		
		
		
		
		
		
		
	}

	@file_put_contents($destination,serialize($FINAL));
	echo count($FINAL)." Items added\n";
	
}
