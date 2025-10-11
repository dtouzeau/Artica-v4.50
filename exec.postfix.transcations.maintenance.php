<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.manager.inc');
$EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
if($EnablePostfix==0){die();}
if(isset($argv[1])){
    if($argv[1]=="--clean"){CleanReasons();exit;}
    if($argv[1]=="--blk"){$GLOBALS["VERBOSE"]=true;CountofBLKW();exit;}
    if($argv[1]=="--today"){$GLOBALS["VERBOSE"]=true;Statistics_today();exit;}
    if($argv[1]=="--users"){$GLOBALS["VERBOSE"]=true;ListUsers();exit;}
}

$GLOBALS["OUTPUT"]=true;

fillempty();


function fillempty(){


	$unix=new unix();
	
	$cacheTemp="/etc/artica-postfix/pids/exec.postfix.transcations.maintenance.time";
	$unix=new unix();
	$ztime=$unix->file_time_min($cacheTemp);
	if(!$GLOBALS["VERBOSE"]){
		if($ztime<15){
			echo "Please, restart later (15mn) - current = {$ztime}mn\n";
			return ;
		}
	}
	
	@unlink($cacheTemp);
	@file_put_contents($cacheTemp, time());
	
	$q=new postgres_sql();
	$q->SMTP_TABLES();
	
	$results=$q->QUERY_SQL("SELECT * FROM smtplog WHERE msgid is not null and ipaddr != '0.0.0.0' AND length(tomail)>1 and length(frommail)>1  AND maintenance=0 ORDER by id desc");
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	while ($ligne = pg_fetch_assoc($results)) {
		$id=$ligne["id"];
		$msgid=trim($ligne["msgid"]);
		if($msgid==null){continue;}
		$q->QUERY_SQL("UPDATE smtplog SET maintenance=1 WHERE id=$id");
		
		$ipaddr=$ligne["ipaddr"];
		$tomail=trim($ligne["tomail"]);
		$frommail=trim($ligne["frommail"]);
		$subject=trim($ligne["subject"]);
		$rblart=trim($ligne["rblart"]);
		$f=array();
		$f[]="ipaddr='$ipaddr'";
		if($frommail<>null){$f[]="frommail='$frommail'";}
		if($tomail<>null){$f[]="tomail='$tomail'";}
		if(strlen($subject)>3){$f[]="subject='$subject'";}
		if(strlen($rblart)>10){$f[]="rblart='$rblart'";}
		$f[]="maintenance=1";
		
		echo "UPDATING Message: $msgid\n";
		$sql="UPDATE smtplog SET ".@implode(",", $f) ." WHERE msgid='$msgid' AND maintenance=0";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
		
		
		
	}
	
	$results=$q->QUERY_SQL("SELECT * FROM smtplog WHERE msgid is not null AND length(tomail)>1 AND maintenance=0 ORDER by id desc");
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	while ($ligne = pg_fetch_assoc($results)) {
		$id=$ligne["id"];
		$msgid=trim($ligne["msgid"]);
		$tomail=trim($ligne["tomail"]);
		$frommail=trim($ligne["frommail"]);
		$subject=trim($ligne["subject"]);
		$rblart=trim($ligne["rblart"]);
		
		if($msgid==null){continue;}
		$q->QUERY_SQL("UPDATE smtplog SET maintenance=1 WHERE id=$id");
		
		$f=array();
		if($frommail<>null){$f[]="frommail='$frommail'";}
		if($tomail<>null){$f[]="tomail='$tomail'";}
		if(strlen($subject)>3){$f[]="subject='$subject'";}
		if(strlen($rblart)>10){$f[]="rblart='$rblart'";}
		$f[]="maintenance=1";
		
		echo "UPDATING Message: $msgid\n";
		$sql="UPDATE smtplog SET ".@implode(",", $f) ." WHERE msgid='$msgid' AND maintenance=0";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
		
	}
	CountRBL();
	CountofBLKW();
    Statistics_today();
    ListUsers();
	echo "Analyze table...\n";
	$q->QUERY_SQL("ANALYZE TABLE smtplog");
	echo "VACUUM table...\n";
	$q->QUERY_SQL("VACUUM smtplog");
	
	
}

function CountRBL(){
	$sock=new sockets();
	$q=new postgres_sql();
	$q->SMTP_TABLES();
	
	$q->QUERY_SQL("UPDATE smtplog SET rbl=1,refused=1 FROM (SELECT id FROM smtplog WHERE (reason ~ 'Rbl:' AND rbl=0) as subquery WHERE subquery.id=smtplog.id");
	
	$sql="SELECT COUNT(*) as tcount FROM smtplog WHERE rbl=1";
	$ligne=$q->mysqli_fetch_array($sql);
	$sock->SET_INFO("CountOfRBLThreats", $ligne["tcount"]);
	
	
	$sql="SELECT COUNT(*) as tcount FROM smtplog  WHERE ( (refused=1) OR (infected=1) )";
	$ligne=$q->mysqli_fetch_array($sql);
	$sock->SET_INFO("CountOfSMTPThreats", $ligne["tcount"]);
	
	$sql="SELECT COUNT(*) as tcount, SUM(size) as size FROM quarmsg";
	$ligne=$q->mysqli_fetch_array($sql);
	if(!$q->ok){echo $q->mysql_error."\n";}
	$SUM_BACK=$ligne["tcount"];
	$SUM_BACKSIZE=$ligne["size"];
	$sock->SET_INFO("SMTP_SUM_QUAR", $SUM_BACK);
	$sock->SET_INFO("SMTP_SUM_QUARSIZE", $SUM_BACKSIZE);
	
	
	$sql="SELECT COUNT(*) as tcount, SUM(size) as size FROM backupmsg";
	$ligne=$q->mysqli_fetch_array($sql);
	if(!$q->ok){echo $q->mysql_error."\n";}
	$SUM_BACK=$ligne["tcount"];
	$SUM_BACKSIZE=$ligne["size"];
	$sock->SET_INFO("SMTP_SUM_BACKUP", $SUM_BACK);
	$sock->SET_INFO("SMTP_SUM_BACKUPSIZE", $SUM_BACKSIZE);

    $sql="SELECT COUNT(*) as tcount FROM autowhite";
    $ligne=$q->mysqli_fetch_array($sql);
    $SUM_WHITE=$ligne["tcount"];
    $sock->SET_INFO("SMTP_SUM_AUTOWHITE", $SUM_WHITE);

}

function CountofBLKW(){
	$sock=new sockets();
	$q=new postgres_sql();
	
	
	$ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount from miltergreylist_acls WHERE method='blacklist'");
	$SUM_COUNT_SMTP_BLK=$ligne["tcount"];
	$ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount from miltergreylist_acls WHERE method='whitelist'");
	$SUM_COUNT_SMTP_WHL=$ligne["tcount"];	
	
	$sock->SET_INFO("SUM_COUNT_SMTP_BLK", $SUM_COUNT_SMTP_BLK);
	$sock->SET_INFO("SUM_COUNT_SMTP_WHL", $SUM_COUNT_SMTP_WHL);
	
	$ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM smtplog WHERE aclid>0");
	$SUM_COUNT_SMTP_BLK_BLOCK=$ligne["tcount"];
	$sock->SET_INFO("SUM_COUNT_SMTP_BLK_BLOCK", $SUM_COUNT_SMTP_BLK_BLOCK);

    $q->QUERY_SQL("UPDATE smtplog SET reason='Artica Reputation IP',rbl=1,refused=1 WHERE reason='rblquery.artica.center'");

	

    $results=$q->QUERY_SQL("SELECT count(*) as t,reason FROM smtplog  WHERE refused=1 group by reason ORDER BY t desc LIMIT 10");
    while ($ligne = pg_fetch_assoc($results)) {
        if($GLOBALS["VERBOSE"]){echo $ligne["reason"]."\t".$ligne["t"]."\n";}
        $MAIN[$ligne["reason"]]=$ligne["t"];
    }

    $sock->SET_INFO("PIE_REFUSED_SMTP", base64_encode(serialize($MAIN)));

    $MAIN=array();
    $FirstDayOfWeek=strtotime("monday this week");
    $FirstDayOfWeek_text=date("Y-m-d 00:00:00",$FirstDayOfWeek);
    $results=$q->QUERY_SQL("SELECT count(*) as t,date_trunc('hour', zdate) as zdate FROM smtplog  WHERE refused=1 AND zdate>'$FirstDayOfWeek_text' GROUP BY date_trunc('hour', zdate) ORDER BY zdate ASC");
    while ($ligne = pg_fetch_assoc($results)) {
        if($GLOBALS["VERBOSE"]){echo $ligne["zdate"]."\t".$ligne["t"]."\n";}
        $MAIN["xdata"][]=$ligne["zdate"];
        $MAIN["ydata"][]=$ligne["t"];
    }


    $sock->SET_INFO("GRAPH_REFUSED_SMTP_WEEK", base64_encode(serialize($MAIN)));
	
	
	
	
}

function Statistics_today(){

    $q=new postgres_sql();
    $today=date("Y-m-d 00:00:00");

    $sql="SELECT COUNT(*) as tcount FROM smtplog WHERE zdate>'$today' AND refused=1";
    $ligne=$q->mysqli_fetch_array($sql);
    $ARRAY["SUM"]["REFUSED"]=$ligne["tcount"];
    $sql="SELECT COUNT(*) as tcount FROM smtplog WHERE zdate>'$today' AND sent=1";
    $ligne=$q->mysqli_fetch_array($sql);
    $ARRAY["SUM"]["SENT"]=$ligne["tcount"];

    $sql="SELECT COUNT(*) as tcount , date_trunc('hour', zdate) + (((date_part('minute', zdate)::integer / 10::integer) * 10::integer) || ' minutes')::interval AS zmins FROM smtplog WHERE zdate>'$today' AND sent=1 GROUP BY zmins;";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}

    while ($ligne = pg_fetch_assoc($results)) {
        $xtime=strtotime($ligne["zmins"]);
        $ARRAY["SENT"]["xdata"][]=date("H:i",$xtime);
        $ARRAY["SENT"]["ydata"][]=$ligne["tcount"];
    }
    $sql="SELECT COUNT(*) as tcount , date_trunc('hour', zdate) + (((date_part('minute', zdate)::integer / 10::integer) * 10::integer) || ' minutes')::interval AS zmins FROM smtplog WHERE zdate>'$today' AND refused=1 GROUP BY zmins;";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}

    while ($ligne = pg_fetch_assoc($results)) {
        $xtime=strtotime($ligne["zmins"]);
        $ARRAY["DENY"]["xdata"][]=date("H:i",$xtime);
        $ARRAY["DENY"]["ydata"][]=$ligne["tcount"];
    }
    $sock=new sockets();
    $sock->SET_INFO("GRAPH_SENTANDREFUSED_SMTP_TODAY", base64_encode(serialize($ARRAY)));
}

function ListUsers(){
    $q=new postgres_sql();
    $today=date("Y-m-d 00:00:00");

    $sql="CREATE TABLE IF NOT EXISTS smtp_users (tomail VARCHAR(256) PRIMARY KEY,todomain VARCHAR(128))";
    $q->QUERY_SQL($sql);
    if(!$q->ok){return;}
    $q->create_index("smtp_users","todomain",array("todomain"));


    $sql="SELECT tomail FROM smtplog WHERE zdate>'$today' AND sent=1 GROUP BY tomail";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;}
    $f=array();
    while ($ligne = pg_fetch_assoc($results)) {

        $tomail=$ligne["tomail"];
        if(strpos($tomail,">")>0){continue;}
        if(strpos($tomail,"orig_to=")>0){continue;}
        if(strpos($tomail,"@")==0){continue;}
        $tb=explode("@",$tomail);
        $todomain=$tb[1];
        $f[]="('$tomail','$todomain')";

    }

    if(count($f)>0){
        echo count($f)." members...\n";
        $sql="INSERT INTO smtp_users(tomail,todomain) VALUES ".@implode(",",$f)." ON CONFLICT DO NOTHING";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;}
    }

}


function CleanReasons(){

    $q=new postgres_sql();
    $sql="SELECT id FROM smtplog WHERE reason ~ 'Barracuda Reputation'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        echo "{$ligne["id"]}\n";
        $q->QUERY_SQL("UPDATE smtplog SET reason='rbl:Barracuda Reputation' WHERE id='{$ligne["id"]}'");

    }
    $sql="SELECT id FROM smtplog WHERE reason ~ 'www.abuseat.org'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        echo "{$ligne["id"]}\n";
        $q->QUERY_SQL("UPDATE smtplog SET reason='rbl:AbuseAT Reputation' WHERE id='{$ligne["id"]}'");

    }
    $sql="SELECT id FROM smtplog WHERE reason ~ 'www.spamcop.net'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        echo "{$ligne["id"]}\n";
        $q->QUERY_SQL("UPDATE smtplog SET reason='rbl:SpamCop Reputation' WHERE id='{$ligne["id"]}'");

    }
    $sql="SELECT id FROM smtplog WHERE reason ~ 'gbudb.com'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        echo "{$ligne["id"]}\n";
        $q->QUERY_SQL("UPDATE smtplog SET reason='rbl:GbuDB Reputation' WHERE id='{$ligne["id"]}'");

    }
    $sql="SELECT id FROM smtplog WHERE reason ='rbl:rblquery.artica.center'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        echo "{$ligne["id"]}\n";
        $q->QUERY_SQL("UPDATE smtplog SET reason='rbl:Artica Reputation' WHERE id='{$ligne["id"]}'");

    }

    $sql="SELECT id FROM smtplog WHERE reason ~ ' accepted for delivery'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        echo "{$ligne["id"]}\n";
        $q->QUERY_SQL("UPDATE smtplog SET reason='Sent',sent=1,refused=0 WHERE id='{$ligne["id"]}'");

    }
    $sql="SELECT id FROM smtplog WHERE reason ~ 'queued as '";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        echo "{$ligne["id"]}\n";
        $q->QUERY_SQL("UPDATE smtplog SET reason='Sent',sent=1,refused=0 WHERE id='{$ligne["id"]}'");

    }
    $sql="SELECT id FROM smtplog WHERE reason ~ '250 2.0.0 OK\s+'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        echo "{$ligne["id"]}\n";
        $q->QUERY_SQL("UPDATE smtplog SET reason='Sent',sent=1,refused=0 WHERE id='{$ligne["id"]}'");

    }
    $sql="SELECT id FROM smtplog WHERE reason ~ '250 (OK|ok|Ok)'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        echo "{$ligne["id"]}\n";
        $q->QUERY_SQL("UPDATE smtplog SET reason='Sent',sent=1,refused=0 WHERE id='{$ligne["id"]}'");

    }

    $sql="SELECT id FROM smtplog WHERE reason ~ 'conversation with (.*?)\[(.+?)\]\s+timed out while sending\s+([A-Z\s]+)'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        echo "{$ligne["id"]}\n";
        $q->QUERY_SQL("UPDATE smtplog SET reason='Timed Out',sent=0,refused=1 WHERE id='{$ligne["id"]}'");

    }



}


