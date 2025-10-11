<?php
include_once(dirname(__FILE__)."/class.memcached.inc");
include_once(dirname(__FILE__)."/class.ccurl.inc");

class maillog_tools{
	var $buffer=null;	
	private $ipClass;
	function __construct(){
		if(!isset($GLOBALS["CLASS_POSTGRES"])){$GLOBALS["CLASS_POSTGRES"]=new postgres_sql();}
		if(!isset($GLOBALS["ActAsSMTPGatewayStatistics"])){$sock=new sockets();$GLOBALS["ActAsSMTPGatewayStatistics"]=$sock->GET_INFO("ActAsSMTPGatewayStatistics");if(!is_numeric($GLOBALS["ActAsSMTPGatewayStatistics"])){$GLOBALS["ActAsSMTPGatewayStatistics"]=0;}}
		$this->ipClass=new IP();
	}
	
	public function Postfix_Addconnection_error($hostname,$ip,$error_text){
		
		$time=time();
		$fam=new familysite();
		if($hostname==null){$hostname=$fam->GetComputerName($ip);}
		$curdate=date("YmdH");
		$zDate=date("Y-m-d H:i:s");
		$domain=$fam->GetFamilySites($hostname);
		$smtp_code=0;
		$infected=0;
		
		if(preg_match("#Message infected virus found\s+(.+?);#", $error_text,$re)){return;}
		
		
		
		
		$error_text=str_replace("'", "", $error_text);
		
		if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$ip)){
			$ip=gethostbyname($ip);
			if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$ip)){if($hostname<>null){$ip=gethostbyname($hostname);}}
			if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$ip)){return null;}
		}
		
		$GLOBALS["CLASS_POSTGRES"]->QUERY_SQL("INSERT INTO smtplog (zdate,fromdomain,relay_s,ipaddr,smtp_code,refused,infected,reason) VALUES('$zDate','$domain','$hostname','$ip',0,1,$infected,'$error_text')");
		if(!$GLOBALS["CLASS_POSTGRES"]->ok){
			if(function_exists("events")){events(__CLASS__." Line.".__LINE__.": PostgreSQL Error");}
			if(preg_match("#relation.*?does not exist#", $GLOBALS["CLASS_POSTGRES"]->mysql_error)){
				$GLOBALS["CLASS_POSTGRES"]->SMTP_TABLES();
			}
		}
	}	
	
	
	private function SMTP_HACKS($hostname,$ip){
		if(!isset($GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"])){return;}
		if($GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]==0){return;}
		if(!function_exists("smtp_hack_perform")){return;}
		
		$GLOBALS["SMTP_HACK"][$hostname]["RBL"]=$GLOBALS["SMTP_HACK"][$ip]["RBL"]+2;
		if(function_exists("events")){error_log("Postfix Hack: {$hostname} RBL !! {$ip}={$GLOBALS["SMTP_HACK"][$ip]["RBL"]} attempts",0);}
			
			
		if($GLOBALS["SMTP_HACK"][$ip]["RBL"]>=$GLOBALS["SMTP_HACK_CONFIG_RATE"]["RBL"]){
			smtp_hack_perform($ip,$GLOBALS["SMTP_HACK"][$ip],"RBL");
			unset($GLOBALS["SMTP_HACK"][$ip]);
		}
	}

		
		
	
	
	
	
public function amavis_spam($postfix_id,$smtp_sender,$from,$to,$size,$action){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	$file="/var/log/artica-postfix/RTM/$postfix_id.msg";
	$ini=new Bs_IniHandler($file);
	$ini->set("TIME","message-id","$postfix_id");
	$ini->set("TIME","time_end",date("Y-m-d H:i:s"));
	$ini->set("TIME","bounce_error",$action);
	$ini->set("TIME","size",$size);
	if($to<>null){$ini->set("TIME","mailto",$to);}
	if($from<>null){$ini->set("TIME","mailfrom","$from");}
	if($smtp_sender<>null){$ini->set("TIME","server_from","$smtp_sender");}
	if($action<>"Sended"){
		$ini->set("TIME","delivery_success","no");
		$this->Postfix_Addconnection_error(null,$smtp_sender,"SPAM");
	}else{
		$ini->set("TIME","delivery_success","yes");
		
	}
	$ini->saveFile($file);
}

private function ToSyslog($text){
	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog(basename(__FILE__), LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}

private function berekley_db_create($db_path){
	if(is_file($db_path)){return true;}
	$db_desttmp = @dba_open($db_path, "c","db4");
	@dba_close($db_desttmp);
	if(is_file($db_path)){return true;}
	$this->ToSyslog("berekley_db_create:: Failed Creating $db_path database");
}

private function berkleydb_write($dbpath,$key,$MAIN_ARRAY){
	$MAIN_ARRAY["TIME"]=time();
	if(!$this->berekley_db_create($dbpath)){return;}
	$db_con = @dba_open($dbpath, "w","db4");
	if(!$db_con){
		$this->ToSyslog("berkleydb_write:: $dbpath failed connect");
		@dba_close($db_con);
		return;

	}

	$seralized=serialize($MAIN_ARRAY);
	@dba_replace($key,$seralized,$db_con);
	@dba_close($db_con);


}

private function jsonArtica($ipaddr){
	
	if($ipaddr=="127.0.0.1"){
		$array["TYPE"]="whitelisted";
		$array["QUERY"]=$ipaddr;
		$data=json_encode($array);
		return base64_encode($data);		
	}
	
	if($ipaddr=="0.0.0.0"){
		$array["TYPE"]="NONE";
		$array["QUERY"]=$ipaddr;
		$data=json_encode($array);
		return base64_encode($data);
	}
	if($ipaddr==null){
		$array["TYPE"]="NONE";
		$array["QUERY"]=$ipaddr;
		return base64_encode(json_encode($array));
	}
	
	
	$mem=new lib_memcached();
	$results=$mem->getKey("RBLQUERY:$ipaddr");
	if($mem->MemCachedFound){
		error_log("rbl.artica.center $ipaddr HIT",0);
		return base64_encode($results);
	}
	
	$curl=new ccurl("https://rbl.artica.center/api/rest/rbl/query/black/$ipaddr");
	$curl->Timeout=2;
	$curl->NoHTTP_POST=true;

	if(!$curl->get()){
		error_log("rbl.artica.center Error $curl->error interface=$curl->interface Proxy=$curl->ArticaProxyServerEnabled",0);
		$array["FOUND"]=false;
		$array["TYPE"]="Error";
		$array["DESC"]="$curl->error";
		$array["QUERY"]=$ipaddr;
		$data=json_encode($array);
		error_log("rbl.artica.center $ipaddr MISS Error $curl->error",0);
		$mem->saveKey("RBLQUERY:$ipaddr", base64_encode($data),300);
		return base64_encode($data);
	}
	error_log("rbl.artica.center $ipaddr MISS ".strlen($curl->data)." bytes (1200s)",0);
	$mem->saveKey("RBLQUERY:$ipaddr", base64_encode($curl->data),1200);
	return base64_encode($curl->data);
}



public function berkleydb_relatime_write($key,$MAIN_ARRAY){

	if($GLOBALS["VERBOSE"]){echo "berkleydb_relatime_write($key\n";}
	$time=0;
	$to=null;
	$from=null;
	$server=null;
	$ipaddr=null;
	$status=null;
	$sent=0;
	$size=0;
	$smtp_code=0;
	$infected=0;
	$refused=0;
	$spamscore=0;
	$MESSAGE_ID=null;
	$fromdomain=null;
	$todomain=null;
	$subject=null;
	$reject=null;
	$relay_r=null;
	$aclid=0;
	if(!isset($MAIN_ARRAY["TIME_END"])){$MAIN_ARRAY["TIME_END"]=time();}
	if(!isset($MAIN_ARRAY["SIZE"])){$MAIN_ARRAY["SIZE"]=0;}
	if(isset($MAIN_ARRAY["RECIPIENT"])){$to=$MAIN_ARRAY["RECIPIENT"];}
	if(isset($MAIN_ARRAY["SENDER"])){$from=$MAIN_ARRAY["SENDER"];}
	if(isset($MAIN_ARRAY["HOSTNAME"])){$server=$MAIN_ARRAY["HOSTNAME"];}
	if(isset($MAIN_ARRAY["IPADDR"])){$ipaddr=$MAIN_ARRAY["IPADDR"];}
	if(isset($MAIN_ARRAY["REJECTED"])){$reject=$MAIN_ARRAY["REJECTED"];}
	if(isset($MAIN_ARRAY["SPAMSCORE"])){$spamscore=$MAIN_ARRAY["SPAMSCORE"];}
	if(isset($MAIN_ARRAY["SUBJECT"])){$subject=$MAIN_ARRAY["SUBJECT"];}
	if(isset($MAIN_ARRAY["ACLID"])){$aclid=$MAIN_ARRAY["ACLID"];}
	
	
	
	$size=intval($MAIN_ARRAY["SIZE"]);
	$TIME_END=intval($MAIN_ARRAY["TIME_END"]);
	if(isset($MAIN_ARRAY["MESSAGE_ID"])){$MESSAGE_ID=$MAIN_ARRAY["MESSAGE_ID"];}
	if(isset($MAIN_ARRAY["DATE_FROM"])){$time=$MAIN_ARRAY["DATE_FROM"];}
	if(isset($MAIN_ARRAY["SEQUENCE"])){$smtp_code=$MAIN_ARRAY["SEQUENCE"];}
	if(isset($MAIN_ARRAY["RELAIS_DEST"])){$relay_r=$MAIN_ARRAY["RELAIS_DEST"];}
	if(isset($MAIN_ARRAY["REFUSED"])){$refused=intval($MAIN_ARRAY["REFUSED"]);}
	if(isset($MAIN_ARRAY["SENT"])){$sent=intval($MAIN_ARRAY["SENT"]);}
	
	
	if($GLOBALS["VERBOSE"]){echo "Accepted: $MESSAGE_ID $ipaddr/$server <$from> <$to>\n";}
	
	
	$relay_r=str_replace("[", "", $relay_r);
	$relay_r=str_replace("]", "", $relay_r);
	$server=str_replace("]", "", $server);
	$server=str_replace("[", "", $server);
	
	if($time==0){$time=time();}
	if($MESSAGE_ID==null){if($key<>null){$MESSAGE_ID=$key;}}
	$mailfrom=$from;
	$mailto=$to;
	$reason=$reject;
	$hostname=$server;

	if(preg_match("#refused to talk to me#i",$MAIN_ARRAY["REJECTED"])){
        $MAIN_ARRAY["REJECTED"]="Communication refused";
        $MAIN_ARRAY["SENT"]=0;
        $MAIN_ARRAY["REFUSED"]=1;
    }

    if(preg_match("#No route to host#i",$MAIN_ARRAY["REJECTED"])) {
        $MAIN_ARRAY["REJECTED"] = "No route to host";
        $MAIN_ARRAY["SENT"] = 0;
        $MAIN_ARRAY["REFUSED"]=1;

    }
    if(preg_match("#Relay access denied#i",$MAIN_ARRAY["REJECTED"])) {
        $MAIN_ARRAY["REJECTED"] = "Relay access denied";
        $MAIN_ARRAY["SENT"] = 0;
        $MAIN_ARRAY["REFUSED"]=1;

    }
    if(preg_match("#no such recipient#i",$MAIN_ARRAY["REJECTED"])) {
        $MAIN_ARRAY["REJECTED"] = "Mailbox not found";
        $MAIN_ARRAY["SENT"] = 0;
        $MAIN_ARRAY["REFUSED"]=1;
    }

    if(preg_match("#rblquery\.artica\.center#i",$MAIN_ARRAY["REJECTED"])) {
        $MAIN_ARRAY["REJECTED"] = "rbl:Artica Reputation";
        $MAIN_ARRAY["SENT"] = 0;
        $MAIN_ARRAY["REFUSED"]=1;
    }


    if(preg_match("#conversation with (.*?)\[(.+?)\]\s+timed out while sending\s+([A-Z\s]+)#i",$MAIN_ARRAY["REJECTED"],$re)) {
        $MAIN_ARRAY["REJECTED"] = "Timeout after ".trim($re[3]);
        $MAIN_ARRAY["HOSTNAME"] = $re[1];
        $MAIN_ARRAY["IPADDR"] = $re[2];
        $MAIN_ARRAY["SENT"] = 0;
        $MAIN_ARRAY["REFUSED"]=1;

    }
    if(preg_match("#conversation with (.*?)\[(.+?)\]\s+timed out while performing the\s+([A-Z\s]+)#i",$MAIN_ARRAY["REJECTED"],$re)) {
        $MAIN_ARRAY["REJECTED"] = "Timeout after ".trim($re[3]);
        $MAIN_ARRAY["HOSTNAME"] = $re[1];
        $MAIN_ARRAY["IPADDR"] = $re[2];
        $MAIN_ARRAY["SENT"] = 0;
        $MAIN_ARRAY["REFUSED"]=1;
    }

    if(preg_match("#Greylisting in action#i",$MAIN_ARRAY["REJECTED"])) {
        $MAIN_ARRAY["REJECTED"] = "Greylisted";
        $MAIN_ARRAY["SENT"] = 0;
        $MAIN_ARRAY["REFUSED"]=1;

    }

    if(preg_match("#host\s+(.*?)\[(.+?)\]\s+said:\s+55[0-9]\s+#i",$MAIN_ARRAY["REJECTED"],$re)) {
        $MAIN_ARRAY["REJECTED"] = "Rejected";
        $MAIN_ARRAY["SENT"] = 0;
        $MAIN_ARRAY["HOSTNAME"] = $re[1];
        $MAIN_ARRAY["IPADDR"] = $re[2];
        $MAIN_ARRAY["REFUSED"]=1;

    }

    if(preg_match("#host\s+(.*?)\[(.+?)\]\s+said:\s+42[0-9]\s+Service not available#i",$MAIN_ARRAY["REJECTED"],$re)) {
        $MAIN_ARRAY["REJECTED"] = "Service not available";
        $MAIN_ARRAY["SENT"] = 0;
        $MAIN_ARRAY["HOSTNAME"] = $re[1];
        $MAIN_ARRAY["IPADDR"] = $re[2];
        $MAIN_ARRAY["REFUSED"]=1;
    }



	$date=date("Y-m-d H:i:s");
	
	
	if($mailfrom<>null){
		$Zmailfrom=explode("@",$mailfrom);
		$fromdomain=$Zmailfrom[1];
	}
	if($mailto<>null){
		$zmailto=explode("@",$mailto);
		$todomain=$zmailto[1];
	}
	
	if(trim(strtolower($reject))=="sended"){
		$smtp_code=100;
		$sent=1;
	}
	
	
	if(preg_match("#Message infected virus found\s+(.+?);#", $reason,$re)){
		$smtp_code=0;
		$reason=$re[1];
		if(preg_match("#Infected:(.+)#i",$reason,$re)){$reason=$re[1];}
		$infected=1;
	}
	
	if(preg_match("#Infected:(.+)#i",$reason,$re)){$reason=$re[1];$infected=1;}
	
	if($ipaddr==null){$ipaddr="0.0.0.0";}
	if(!$this->ipClass->isValid($ipaddr)){
		if($this->ipClass->isValid($hostname)){
			$tempip=$hostname;
			$hostname=$ipaddr;
			$ipaddr=$tempip;
		}else{
			$ipaddr="0.0.0.0";
		}
	}
	
	$rblart=$this->jsonArtica($ipaddr);


    if($this->ipClass->isValid($hostname)){$hostname=gethostbyaddr($hostname);}
    
	
	$F[]="zdate";
	$F[]="fromdomain";
	$F[]="todomain";
	$F[]="frommail";
	$F[]="tomail";
	$F[]="ipaddr";
	$F[]="relay_s";
	$F[]="smtp_code";
	$F[]="refused";
	$F[]="infected";
	$F[]="reason";
	$F[]="msgid";
	$F[]="sent";
	$F[]="relay_r";
	$F[]="rblart";
	$F[]="spamscore";
	$F[]="aclid";
	if($subject<>null){
	    $F[]="subject";
        $subject=str_replace("'","`",$subject);

	}
	
	$D[]="'$date'";
	$D[]="'$fromdomain'";
	$D[]="'$todomain'";
	$D[]="'$mailfrom'";
	$D[]="'$mailto'";
	$D[]="'$ipaddr'";
	$D[]="'$hostname'";
	$D[]="$smtp_code";
	$D[]="$refused";
	$D[]="$infected";
	$D[]="'$reason'";
	$D[]="'$MESSAGE_ID'";
	$D[]="'$sent'";
	$D[]="'$relay_r'";
	$D[]="'$rblart'";
	$D[]="'$spamscore'";
	$D[]="'$aclid'";
	if($subject<>null){$D[]="'$subject'";}
	
	if(function_exists("events")){error_log("SQL <$reason> from <$from> to=<$mailto> {$hostname}[$ipaddr]",0);}
	
	if($GLOBALS["VERBOSE"]){echo count($F)." Fields with ".count($D)." Values\n";}
	
	$sql="INSERT INTO smtplog (".@implode(",", $F).") VALUES (".@implode(",", $D).")";
	if($GLOBALS["VERBOSE"]){echo "$sql\n";}
	
	if(!isset($GLOBALS["CLASS_POSTGRES"])){$GLOBALS["CLASS_POSTGRES"]=new postgres_sql();}
	
	$GLOBALS["CLASS_POSTGRES"]->QUERY_SQL($sql);
	
	if(!$GLOBALS["CLASS_POSTGRES"]->ok){
		if($GLOBALS["VERBOSE"]){echo "SQL FAILED {$GLOBALS["CLASS_POSTGRES"]->mysql_error}\n";}
		if(function_exists("events")){events(__CLASS__." Line.".__LINE__.": PostgreSQL Error {$GLOBALS["CLASS_POSTGRES"]->mysql_error}");}
		if(function_exists("events")){events(__CLASS__." Line.".__LINE__.": $sql");}
		$HASH["zdate"]=$date;
		$HASH["fromdomain"]=$fromdomain;
		$HASH["todomain"]=$todomain;
		$HASH["hostname"]=$hostname;
		$HASH["mailfrom"]=$mailfrom;
		$HASH["mailto"]=$mailto;
		$HASH["ipaddr"]=$ipaddr;
		$HASH["reason"]=$reason;
		$HASH["msgid"]=$MESSAGE_ID;
		$Serialized=serialize($HASH);
		$md5=md5(serialize($Serialized));
		if(!is_dir("/home/artica/postfix/smtprefused_failed")){@mkdir("/home/artica/postfix/smtprefused_failed",0755,true);}
		@file_put_contents("/home/artica/postfix/smtprefused_failed/$md5.log", $Serialized);
	}
	
	
	
	

}



public function event_message_from($postfix_id,$from,$size){
	$ARRAY["MESSAGE_ID"]=$postfix_id;
	$ARRAY["SENDER"]=$from;
	$ARRAY["SIZE"]=$size;
	$ARRAY["SEQUENCE"]=0;
	$ARRAY["REJECTED"]="Transaction From";
	$this->berkleydb_relatime_write($postfix_id,$ARRAY);
}



function event_message_milter_reject($postfix_id,$reject,$from,$to=null,$buffer=null,$sender=null){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	
	$ARRAY["IPADDR"]=null;
	$ARRAY["MESSAGE_ID"]=$postfix_id;
	$smtp_sender=null;
	$hostname=null;
	
	
	if($sender==null){
		if(preg_match("#from.+?\[([0-9\.]+)?\]#",$buffer,$re)){$smtp_sender=$re[1];}
		if(preg_match("#assp\[.+?\]:\s+.+?\s+(.+?)\s+<#",$buffer,$re)){$smtp_sender=$re[1];}
		
		if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$smtp_sender)){
			$hostname=gethostbyaddr($smtp_sender);
		}
	}
	
	if($smtp_sender<>null){
		$ARRAY["IPADDR"]=$smtp_sender;
	}
	
	if($to<>null){$ARRAY["RECIPIENT"]=$to;}
	if($from<>null){$ARRAY["SENDER"]=$from;}
	if($hostname<>null){$ARRAY["HOSTNAME"]=$hostname;}
	$ARRAY["REJECTED"]=$reject;
	$this->berkleydb_relatime_write($postfix_id,$ARRAY);

}

function Postfix_Addconnection($hostname=null,$ip=null){
	if($ip=="127.0.0.1"){return;}
	if(isset($GLOBALS["PostfixRemoveConnections"][$re[3]])){return true;}
	$time=time();
	$curdate=date("YmdH");
	$zDate=date("Y-m-d H:i:s");
	$ARRAY["MESSAGE_ID"]=null;
	$ARRAY["SENDER"]=null;
	$ARRAY["SIZE"]=0;
	$ARRAY["REJECTED"]="Transaction Connected";
	if($ip<>null){$ARRAY["IPADDR"]=$ip;}
	if($hostname<>null){$ARRAY["HOSTNAME"]=$hostname;}
	$this->berkleydb_relatime_write(null,$ARRAY);
}


public function event_message_reject_hostname($reject,$from=null,$to=null,$server,$ipaddr=null){
	$to=str_replace("|","",$to);
	$to=trim($to);
	if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$server)){$ipaddr=$server;}
	if($server==null){$server=gethostbyaddr($ipaddr);}
	if($server=="unknown"){$server=gethostbyaddr($ipaddr);}
	
	
	if($to<>null){$ARRAY["RECIPIENT"]=$to;}
	if($from<>null){$ARRAY["SENDER"]=$from;}
	$ARRAY["HOSTNAME"]=$server;
	if($ipaddr<>null){$ARRAY["IPADDR"]=$ipaddr;}
	$ARRAY["REJECTED"]=$reject;
	$this->berkleydb_relatime_write(null,$ARRAY);
	
	
}

public function event_message_rejected($id,$reject,$from=null,$to=null,$buffer,$sender=null,$ipaddr=null){
	$this->event_message_reject_hostname($reject, $from,$to, $sender,$ipaddr);
	
}

function event_DISCARD($postfix_id,$from,$to,$buffer=null,$ipaddr=null){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	
	if($ipaddr==null){
		if(preg_match("#from.+?\[([0-9\.]+)?\]#",$buffer,$re)){
			$ARRAY["HOSTNAME"]=$re[1];
			$ARRAY["IPADDR"]=$re[1];
		}
			
	}else{
		$ARRAY["IPADDR"]=$re[1];
	}

	if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.#",$ipaddr)){
		$hostname=gethostbyaddr($ipaddr);
		$ARRAY["HOSTNAME"]=$hostname;
	}

	if($from<>null){$ARRAY["SENDER"]=$from;}
	if($to<>null){$ARRAY["RECIPIENT"]=$to;}
	$ARRAY["REJECTED"]="Discard";
	$this->berkleydb_relatime_write($postfix_id,$ARRAY);
}

public function event_messageid_rejected($msg_id_postfix,$error,$server=null,$to=null,$from=null,$ipaddr=null){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	
	$ARRAY["MESSAGE_ID"]=$msg_id_postfix;
	if($to<>null){
		$ARRAY["RECIPIENT"]=$to;
	}
	$error=str_replace("'", "`", $error);
	$ARRAY["HOSTNAME"]=$server;
	$ARRAY["REJECTED"]=$error;
	if($from<>null){$ARRAY["SENDER"]=$from;}
	if($ipaddr<>null){$ARRAY["IPADDR"]=$ipaddr;}
	$this->berkleydb_relatime_write($msg_id_postfix,$ARRAY);
	
}
public function event_messageid_removed($msg_id_postfix){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	$ARRAY["REJECTED"]="removed";
	$ARRAY["TIME_END"]=time();
	$this->berkleydb_relatime_write($msg_id_postfix,$ARRAY);

}

function event_Content_scanner_malfunction($postfix_id,$from=null,$to=null){
	$ARRAY["MESSAGE_ID"]=$postfix_id;
	if($to<>null){$ARRAY["RECIPIENT"]=$to;}
	if($from<>null){$ARRAY["SENDER"]=$from;}
	$ARRAY["REJECTED"]="Content scanner malfunction";
	$this->berkleydb_relatime_write($postfix_id,$ARRAY);
}




private function shell_exec_maillog($cmd){
	if($GLOBALS["ActAsSMTPGatewayStatistics"]==1){
		events("`$cmd` will not be executed ActAsSMTPGatewayStatistics is enabled" );
		return;
	}
	shell_exec($cmd);
	events("EXEC:`$cmd`" );
}


function event_finish($postfix_id,$to,$status,$bounce_error,$from=null,$buffer=null){
	if($GLOBALS["ActAsASyslogSMTPClient"]==1){return;}
	if($GLOBALS["EnableArticaSMTPStatistics"]==0){return;}
	$smtp_sender=null;
	$delivery_success='yes';
	if($status=='bounced'){$delivery_success='no';}
	if($status=='deferred'){$delivery_success='no';}
	if($status=='reject'){$delivery_success='no';}
	if($status=='expired'){$delivery_success='no';}

	if(preg_match("#Queued mail for delivery#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if(preg_match("#550 No Such User Here#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Mailbox Unknown";
	}

	if(preg_match("#No such user#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Mailbox Unknown";
	}

	if(preg_match("#550 5\.1\.0 error: R4\.1#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Mailbox Unknown";
	}

	if(preg_match("#Sender address rejected: need fully-qualified address#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="need fully-qualified address";
	}

	if(preg_match("#550.*?The email account that you tried to reach does not exist#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Mailbox Unknown";
	}

	if(preg_match("#no mailbox here#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Mailbox Unknown";
	}

	if(preg_match("#refused to talk to me.+?RBL rejection#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="RBL";
	}

	if(preg_match("#550.+?Service unavailable.+?blocked using.+?RBL#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="RBL";
	}

	if(preg_match("#554 : Recipient address rejected: Access denied#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Access denied";
	}


	if(preg_match("#451 4.2.0 Mailbox has an invalid format#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Mailbox corrupt";
	}

	if(preg_match("#delivered via#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}
	
	if(preg_match("#message size [0-9]+ exceeds size limit [0-9]+#",$bounce_error)){
		$status="rejected";
		$delivery_success="no";
		$bounce_error="Size Limit";
	}


	if(preg_match("#Content scanner malfunction#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Content scanner malfunction";
	}

	if(preg_match("#4\.5\.0 Failure#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Error";
	}


	if(preg_match("#250 2\.0\.0 Ok#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if(preg_match("#Host or domain name not found#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Host or domain name not found";
	}


	if(preg_match("#4\.5\.0 Error in processing#",$bounce_error)){$status="Error";$delivery_success="no"; $bounce_error="Error"; };
	if(preg_match("#Sender address rejected.+?Domain not found#",$bounce_error)){$status="Error";$delivery_success="no"; $bounce_error="Domain not found";};

	if(preg_match("#delivered to command: procmail -a#",$bounce_error)){$status="Deliver";$delivery_success="yes";$bounce_error="Sent to procmail";};

	if(preg_match("#550 must be authenticated#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Authentication error";
	};

	if(preg_match("#250 Message.+?accepted by#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	};

	if(preg_match("#250 Message.*?received#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}


	if(preg_match("#Connection timed out#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="timed out";
	};

	if(preg_match("#connect\s+to.+?Connection refused#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Connection refused";

	}

	if(preg_match("#127\.0\.0\.1.*?said:\s+450\s+[0-9\.]+\s+(.+?)\s+Mailbox Temporarily Unavailable#", $bounce_error,$re)){
		$file="/etc/artica-postfix/croned.1/postfix.lmtp.127.0.0.1:{$re[1]}.Temporarily.Unavailable";
		if(file_time_min($file)>5){
			email_error_log("Postfix:MailBox issue on {$re[1]} Temporarily Unavailable","Postfix\n$buffer\nChecks is {$re[1]} is a realy account..","postfix",0);
			@unlink($file);
			file_put_contents($file,"#");
			return;
		}
		$status="Error";
		$delivery_success="no";
		$bounce_error="Mailbox Unavailable";

	}

	if(preg_match("#temporary failure.+?artica-msmtp:\s+recipient address\s+(.+?)\s+not accepted by the server artica-msmtp#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="artica-filter error";
	}

	if(preg_match("#host.+?said: 550 No such user#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="No such user";
	}

	if(preg_match("#250 2\.1\.5 Ok#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if($bounce_error=="250 OK: data received"){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if($bounce_error=="250 Ok: queued as"){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}
	if(preg_match("#250\s+ok#",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if(preg_match("#504.+?Recipient address rejected#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="recipient address rejected";
	}

	if(preg_match("#Address rejected#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Address rejected";
	}

	if(preg_match("#conversation with .+?timed out#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="timed out";
	}

	if(preg_match("#connect to\s+(.+?)\[.+?cyrus.+?lmtp\]: Connection refused#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="mailbox service error";
		cyrus_generic_error($bounce_error,"Cyrus socket error");
	}

	if(preg_match("#host.+?\[(.+?)\]\s+said:.+?<(.+?)>: Recipient address rejected: User unknown in local recipient table#",$bounce_error,$re)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="User unknown";
		$to=$re[2];
	}
		
	if(preg_match("#host.+?said:\s+554.+?<(.+?)>:\s+Recipient address rejected.+?not existing recipient#",$bounce_error,$re)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="not existing recipient";
		$to=$re[2];
	}
		
		
	if(preg_match("#said:.+?Authentication required#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Authentication required";
	}

	if(preg_match("#temporary failure.+?[0-9]+\s+[0-9\.]+\s+Bad sender address syntax.+?could not send mail#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Bad sender address syntax";
	}

	if(preg_match("#connect.+?Permission denied#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="service permissions error";
	}

	if(preg_match("#Command died with status 255:.+?exec\.artica-filter\.php#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="artica-filter error";
	}
	
	if(preg_match("#Host or domain name not found#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="non-existent-smtp";
	}


	if(preg_match("#host\s+(.+?)\[(.+?)\]\s+said:\s+[0-9]+.+?Recipient address rejected: Access denied#",$bounce_error,$re)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Access denied";
		$smtp_sender=$re[2];
	}

	if(preg_match("#250.+?Ok#i",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if(preg_match("#Message accepted#i",$bounce_error)){
		$status="Deliver";
		$delivery_success="yes";
		$bounce_error="Sended";
	}

	if(preg_match("#host.+?said:.+?Domain of sender address.+?does not exist#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="Your domain does not exist";
	}

	if(preg_match("#connect to .+?No such file or dire#",$bounce_error)){
		$status="Error";
		$delivery_success="no";
		$bounce_error="socket error";
	}

	if(preg_match("#lost connection with.+?\[(.+?)\] while receiving#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="lost connection";$smtp_sender=$re[1];}
	if(preg_match("#host.+?\[(.+?)\] said:.+?Recipient address rejected#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="Recipient address rejected";$smtp_sender=$re[1];}

	if(preg_match("#loops back to myself#",$bounce_error,$re)){
		$status="Error";
		$delivery_success="no";
			
		$file="/etc/artica-postfix/croned.1/postfix.loops.back.to.myself";
		if(file_time_min($file)>10){
			shell_exec("{$GLOBALS["NOHUP_PATH"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.maincf.php --urgency >/dev/null 2>&1 &");
			@unlink($file);
			@file_put_contents($file, time);
		}
			
		$bounce_error="loops back to myself";
	}

	if(preg_match("#Sender denied#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="Sender denied";}
	if(preg_match("#User unknown#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="User unknown";}
	if(preg_match("#Bounce attack signature verification failed#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="Bounce attack";}
	if(preg_match("#mailbox unavailable#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="No mailbox";}
	if(preg_match("#Message rejected#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="Message rejected";}
	if(preg_match("#250 2\.0\.0 from MTA#",$bounce_error,$re)){$status="Deliver";$delivery_success="yes";$bounce_error="Sended";}
	if(preg_match("#421-ts03#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="Your are blacklisted";}
	if(preg_match("#User does not exist#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="No mailbox";}
	if(preg_match("#Recipient address rejected#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="authentication required"; }
	if(preg_match("#unsolicited mail originating from your IP address#",$bounce_error,$re)){$status="Error";$delivery_success="no";$bounce_error="Your are blacklisted"; }


	if($bounce_error=="250 OK"){$bounce_error="Sended";$delivery_success="yes";$status="Deliver";}
	if(preg_match("#lost connection with.+?\[(.+?)\]\s+#",$bounce_error,$re)){$bounce_error="lost connection";$delivery_success="no";$smtp_sender=$re[1];}
	if(preg_match("#status=bounced \(message size.+?exceeds size limit.+?of server.+?\[(.+?)\]#",$bounce_error,$re)){$bounce_error="size exceed limit";$delivery_success="no";$smtp_sender=$re[1];}
	if(preg_match("#lost connection with.+?\[(.+?)\]\s+#",$bounce_error,$re)){$bounce_error="lost connection";$delivery_success="no";$smtp_sender=$re[1];}

	if($delivery_success=="no"){
		if($bounce_error=="User unknown in relay recipient table"){$bounce_error="User unknown";}
			
		error_log("event_finish() line ".__LINE__. " bounce_error=$bounce_error",0);
		if(preg_match("#connect to.+?\[(.+?)lmtp\].+?No such file or directory#",$bounce_error,$ra)){
			error_log("Cyrus error found -> CyrusSocketErrot",0);
			cyrus_socket_error($bounce_error,$ra[1].'/lmtp');
		}
		if(preg_match("#550\s+User\s+unknown\s+<(.+?)>.+?in reply to RCPT TO command#",$bounce_error,$ra)){mailbox_unknown($bounce_error,$ra[1]);}
	}

	
	if($delivery_success=="yes"){
			$ARRAY["SENT"]=1;
	}
	
	$ARRAY["MESSAGE_ID"]=$postfix_id;
	
	if($smtp_sender==null){
		if(preg_match("#from.+?\[([0-9\.]+)?\]#",$buffer,$re)){
			$ARRAY["HOSTNAME"]=$re[1];
		}
	}else{
		$ARRAY["HOSTNAME"]=$smtp_sender;
	}

	if($from<>null){$ARRAY["SENDER"]=$from;}
	if($to<>null){$ARRAY["RECIPIENT"]=$to;}
	$bounce_error=str_replace("'", "`", $bounce_error);
	$ARRAY["REJECTED"]=$bounce_error;
	$ARRAY["TIME_END"]=time();
	$ARRAY["STATUS"]=$status;
	$this->berkleydb_relatime_write($postfix_id, $ARRAY);


	

	
	 

}
	
	
}