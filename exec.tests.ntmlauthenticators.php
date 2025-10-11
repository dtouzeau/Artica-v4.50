<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PRIVS"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
_ntmlauthenticators();
function _ntmlauthenticators(){
    $unix=new unix();
    $datas=explode("\n",$unix->squidclient("ntlmauthenticator"));

	$CPU_NUMBER=0;
	foreach ($datas as $num=>$ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#by kid([0-9]+)#", $ligne,$re)){
			$CPU_NUMBER=$re[1];
			$MAIN[$CPU_NUMBER]["PROCESSES"]=0;
			continue;
		}

		if(preg_match("#number active: ([0-9]+) of ([0-9]+)#",$ligne,$re)){
			$Active=intval($re[1]);
			$Max=intval($re[2]);
			$MAIN[$CPU_NUMBER]["MAX"]=$Max;
			$MAIN[$CPU_NUMBER]["ACTIVE"]=$Active;
			if(!isset($MAIN[$CPU_NUMBER]["PROCESSES"])){$MAIN[$CPU_NUMBER]["PROCESSES"]=0;}
		}
		
		if(preg_match("#^([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(B|C|R|S|P|\s)\s+([0-9\.]+)\s+([0-9\.]+)\s+(.*)#", $ligne,$re)){
			$ID=$re[1];
			$FD=$re[2];
			$PID=$re[3];
			$Requests=$re[4];
			$Replies=$re[5];
			$Flags=trim($re[6]);
			$Time=$re[7];
			$Offset=$re[8];
			$Request_text=$re[9];
			if($Flags<>null){
				$MAIN[$CPU_NUMBER]["PROCESSES"]=$MAIN[$CPU_NUMBER]["PROCESSES"]+1;
			}
			
			
		}
	}

	while (list ($CPUNUMBER, $ARRAY) = each ($MAIN) ){
		$PROCESSES=$ARRAY["PROCESSES"];
		$Active=$ARRAY["ACTIVE"];
		$Max=$ARRAY["MAX"];
		
		if($PROCESSES==0){
			$results[$CPUNUMBER]=0;
			continue;
		}
		
		$prc=round(($PROCESSES/$Max)*100);
		$results[$CPUNUMBER]=$prc;
		
	}
	print_r($results);
}