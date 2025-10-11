<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}



if($argv[1]=="--cdir16"){cdir16($argv[2]);exit;}
if($argv[1]=="--clean"){clean();exit;}



function cdir16($ipaddr){
	
	if(!preg_match("#^([0-9]+)\.([0-9]+)\.0\.0\/16$#", $ipaddr,$re)){return;}
	$ipclass=new IP();
	$q=new postgres_sql();
	
	for($i=0;$i<255;$i++){
		for($y=0;$y<255;$y++){
			$ipaddr="{$re[1]}.{$re[2]}.$i.$y";
			if(!$ipclass->isIPAddress($ipaddr)){continue;}
			$description=gethostbyaddr($ipaddr);
			echo "$ipaddr - $description\n";
			$date=date("Y-m-d H:i:s");
			$q->QUERY_SQL("DELETE FROM rbl_blacklists WHERE ipaddr='$ipaddr'");
			$q->QUERY_SQL("DELETE FROM rbl_whitelists WHERE ipaddr='$ipaddr'");
			$q->QUERY_SQL("INSERT INTO rbl_whitelists (ipaddr,description,zDate) VALUES ('$ipaddr','$description','$date')");
		}
	}
	
}


function clean(){
	$q=new postgres_sql();
	$results=$q->QUERY_SQL("SELECT ipaddr FROM rbl_blacklists WHERE description='nixspamDB'");
	
	while ($ligne = pg_fetch_assoc($results)) {
		$ipaddr=$ligne["ipaddr"];
		$description=gethostbyaddr($ipaddr);
		echo "$ipaddr -$description\n";
		$q->QUERY_SQL("UPDATE rbl_blacklists SET description='$description (nixspamDB)");
	}
	
	
	
}