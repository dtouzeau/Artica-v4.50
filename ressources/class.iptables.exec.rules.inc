<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


class iptables_exec{
	
	
	function __construct(){
		$unix=new unix();
		$GLOBALS["iptables"]=$unix->find_program("iptables");
		$GLOBALS["iptables-restore"]=$unix->find_program("iptables-restore");
		$GLOBALS["iptables-save"]=$unix->find_program("iptables-save");
		
		
		
		
	}
	
	function buildrules(){
		$q=new mysql();
		$GLOBALS["ALLOWED"]=array();
		$GLOBALS["BANNED"]=array();
		$GLOBALS["ALLOWED_CROSS1"]=array();
		$GLOBALS["BANNED_CROSS1"]=array();
		if(!$q->test_mysqli_connection()){echo "Starting......: ".date("H:i:s")." ipTables central... Mysql is not ready aborting...\n";return;}

		if($GLOBALS["VERBOSE"]){writelogs("Deleting rules...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		$this->delete_rules();
		if($GLOBALS["VERBOSE"]){writelogs("->create_crossroads_smtp_rules()",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		$this->create_crossroads_smtp_rules();
		if($GLOBALS["VERBOSE"]){writelogs("->multipleBubble()",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		$this->multipleBubble();
		
		if($GLOBALS["VERBOSE"]){writelogs(count($GLOBALS["ALLOWED_CROSS1"])." Crossroads allowed rules",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		while (list ($num, $ligne) = each ($GLOBALS["ALLOWED_CROSS1"]) ){
			writelogs("$ligne",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			shell_exec($ligne);
		}

		if($GLOBALS["VERBOSE"]){writelogs(count($GLOBALS["BANNED_CROSS1"])." Crossroads banned rules",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		while (list ($num, $ligne) = each ($GLOBALS["BANNED_CROSS1"]) ){
			writelogs("$ligne",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			shell_exec($ligne);
		}		
		
		
		
		if($GLOBALS["VERBOSE"]){writelogs(count($GLOBALS["ALLOWED"])." multipleBubble allowed rules",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		while (list ($num, $ligne) = each ($GLOBALS["ALLOWED"]) ){
			writelogs("$ligne",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			shell_exec($ligne);
		}
		
		if($GLOBALS["VERBOSE"]){writelogs(count($GLOBALS["BANNED"])." multipleBubble banned rules",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		while (list ($num, $ligne) = each ($GLOBALS["BANNED"]) ){
			writelogs("$ligne",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			shell_exec($ligne);
		}		
		
	}
	
	private function create_crossroads_smtp_rules(){
		$sock=new sockets();
		$EnableCrossRoads=$sock->GET_INFO("EnableCrossRoads");
		if(!is_numeric($EnableCrossRoads)){
			if($GLOBALS["VERBOSE"]){writelogs("EnableCrossRoads = $EnableCrossRoads -> aborting",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			return;
		}
		if($EnableCrossRoads==0){return;}
		$MAIN=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrossRoadsParams")));
		$listen_ip=$MAIN["PARAMS"]["listen_ip"];
		$listen_port=$MAIN["PARAMS"]["listen_port"];
		if(!is_numeric($listen_port)){return;}
		if(!preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $listen_ip)){if($GLOBALS["VERBOSE"]){writelogs("EnableCrossRoads: `$listen_ip` no match ^[0-9]\.[0-9]+\.[0-9]+\.[0-9] -> aborting",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}return;}
		
		if(isset($MAIN["ALLOW"])){
			while (list ($num, $ligne) = each ($MAIN["ALLOW"]) ){
				$ligne=$this->iptables_transformIP($ligne);
				if($GLOBALS["VERBOSE"]){writelogs("EnableCrossRoads: Allow  $ligne to $listen_ip:$listen_port",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
				$GLOBALS["ALLOWED_CROSS1"][]="{$GLOBALS["iptables"]} -A INPUT --protocol tcp  --source $ligne --destination $listen_ip --destination-port $listen_port -j ACCEPT -m comment --comment \"ArticaCrossRoadsMainInstance\"";
			}
		}else{
			if($GLOBALS["VERBOSE"]){writelogs("EnableCrossRoads: Allow  (no rules)",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		
		
		if(isset($MAIN["DENY"])){
			while (list ($num, $ligne) = each ($MAIN["DENY"]) ){
				$ligne=$this->iptables_transformIP($ligne);
				if($GLOBALS["VERBOSE"]){writelogs("EnableCrossRoads: Deny  $ligne to $listen_ip:$listen_port",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
				$GLOBALS["BANNED_CROSS1"][]="{$GLOBALS["iptables"]} -A INPUT --protocol tcp  --source $ligne --destination $listen_ip --destination-port $listen_port -j DROP -m comment --comment \"ArticaCrossRoadsMainInstance\"";
			}
		}else{
			if($GLOBALS["VERBOSE"]){writelogs("EnableCrossRoads: Deny  (no rules)",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}		
		
		
	} 
	
	private function iptables_transformIP($ip){
		if($ip=="0.0.0.0"){return "0/0";}
		return $ip;
	}
	
	public function is_chain_exists($chainname){
		exec("{$GLOBALS["iptables"]} -L $chainname 2>&1",$results);
		foreach ($results as $num=>$ligne){
			if(preg_match("#No chain#i", $ligne)){return false;}
			if(preg_match("#Chain $chainname.*?[0-9]+#",$ligne)){return true;}
		}
	}
	
	private function multipleBubble(){
		$sock=new sockets();
		$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
		if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
		if($EnablePostfixMultiInstance==0){return;}
		$PostfixMultiCreateBubble=$sock->GET_INFO("PostfixMultiCreateBubble");
		if(!is_numeric($PostfixMultiCreateBubble)){$PostfixMultiCreateBubble=0;}
		if($PostfixMultiCreateBubble==0){return;}
		$q=new mysql();
		$sql="SELECT * FROM `postfix_multi` WHERE `key` = 'myhostname'";
		
		if($GLOBALS["VERBOSE"]){echo $sql."\n";}
		
		$results = $q->QUERY_SQL($sql,"artica_backup");
		writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		writelogs(mysqli_num_rows($results)." rows...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){$ipaddr[$ligne["ip_address"]]=$ligne["ip_address"];}
		
		$sock=new sockets();
		$EnableCrossRoads=$sock->GET_INFO("EnableCrossRoads");
		if(!is_numeric($EnableCrossRoads)){$EnableCrossRoads=0;}
		if($EnableCrossRoads==1){
			$MAIN=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrossRoadsParams")));
			$crossroads_internal=$MAIN["PARAMS"]["listen_ip"];
			if($crossroads_internal==null){$EnableCrossRoads=0;}
			writelogs("Postfix Bubble Crossroads is enable ($EnableCrossRoads) with IP $crossroads_internal",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		}
		
		if($EnableCrossRoads==1){$this->multipleBubble_allowFrom($crossroads_internal,$ipaddr);}
		
		
		
		while (list ($num, $ligne) = each ($ipaddr) ){
			$GLOBALS["ALLOWED"][]="{$GLOBALS["iptables"]} -A INPUT --protocol tcp  --source $num --destination $num --destination-port 25 -j ACCEPT -m comment --comment \"ArticaPostfixBubble\"";	
			$this->multipleBubble_allowFrom($num,$ipaddr);
		}
		
		$q=new mysql();
		$sql="SELECT ipaddr FROM postfix_whitelist_con";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if(isset($ipaddr[$ligne["ipaddr"]])){continue;}
			$this->multipleBubble_allowFrom($ligne["ipaddr"],$ipaddr);
		}
		
		
		
		
		reset($ipaddr);
		while (list ($num, $ligne) = each ($ipaddr) ){
			$GLOBALS["BANNED"][]="{$GLOBALS["iptables"]} -A INPUT --protocol tcp  --source 0/0 --destination $ligne --destination-port 25 -j DROP -m comment --comment \"ArticaPostfixBubble\"";
		}
}

	private function multipleBubble_allowFrom($ipsrc,$destArr){
		unset($destArr[$ipsrc]);
		reset($destArr);
		while (list ($num, $b) = each ($destArr) ){
			$GLOBALS["ALLOWED"][]="{$GLOBALS["iptables"]} -A INPUT --protocol tcp  --source $ipsrc --destination $num --destination-port 25 -j ACCEPT -m comment --comment \"ArticaPostfixBubble\"";	
		}
	}
	
	
	
private function delete_rules(){
		$unix=new unix();
		$iptables_save=$GLOBALS["iptables-save"];
		$iptables_restore=$GLOBALS["iptables-restore"];	
		$conf=null;
		shell_exec("$iptables_save > /etc/artica-postfix/iptables.conf");
		$data=file_get_contents("/etc/artica-postfix/iptables.conf");
		$datas=explode("\n",$data);
		
		$pattern="#.+?ArticaCrossRoadsMainInstance#";
		
		$count=0;
		foreach ($datas as $num=>$ligne){
				if($ligne==null){continue;}
				if(preg_match("#.+?ArticaPostfixBubble#",$ligne)){$count++;continue;}
				if(preg_match("#.+?ArticaCrossRoadsMainInstance#",$ligne)){$count++;continue;}
				$conf=$conf . $ligne."\n";
				}
		
		file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
		shell_exec("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
			
}	
	
	
}