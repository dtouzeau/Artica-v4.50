<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($_GET["getblacklist"])){getblacklist();}
if(isset($_GET["setblacklist"])){setblacklist();exit;}
if(isset($_GET["version"])){getVersion();exit;}
if(isset($_GET["email"])){get_email_blk();exit;}


function getVersion(){
	
	$RBLDNSD_BLCK_COUNT=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RBLDNSD_BLCK_COUNT");
	$RBLDNSD_WHITE_COUNT=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RBLDNSD_WHITE_COUNT");
	$RBLDNSD_COMPILE_TIME=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RBLDNSD_COMPILE_TIME");
	
	$ARRAY["TIME"]=$RBLDNSD_COMPILE_TIME;
	$ARRAY["BLACK_COUNT"]=$RBLDNSD_BLCK_COUNT;
	$ARRAY["WHITE_COUNT"]=$RBLDNSD_WHITE_COUNT;
	echo json_encode($ARRAY);
}



function setblacklist(){
	$ipaddr=$_GET["setblacklist"];
	$mem=new lib_memcached();
	$results=$mem->getKey("RBLQUERY:$ipaddr");
	$q=new postgres_sql();
	
	if($mem->MemCachedFound){
		$array["STATUS"]="FAILED";
		$json=json_decode($results);
		$array["ERROR"]="Already added ".date("Y-m-d H:i:s",$json->date);
		echo json_encode($array);
		return;
	}
	
	
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM rbl_blacklists WHERE ipaddr='$ipaddr'"));
	
	if($ligne["ipaddr"]<>null){
		$array["FOUND"]=true;
		$array["TYPE"]="blacklisted";
		$array["date"]=strtotime($ligne["zdate"]);
		$array["QUERY"]=$ipaddr;
		$data=json_encode($array);
		$mem->saveKey("RBLQUERY:$ipaddr", $data,600);
		$array["ERROR"]="Already blacklisted on {$ligne["zdate"]}";
		$array["STATUS"]="FAILED";
		echo json_encode($array);
		return;
	}
	
	
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM rbl_whitelists WHERE ipaddr='$ipaddr'"));
	if($ligne["ipaddr"]<>null){
		$array["FOUND"]=true;
		$array["TYPE"]="whitelisted";
		$array["date"]=strtotime($ligne["zdate"]);
		$array["QUERY"]=$ipaddr;
		$array["ERROR"]="Already whitelisted {$ligne["zdate"]}";
		$array["STATUS"]="FAILED";
        $data=json_encode($array);
		$mem->saveKey("RBLQUERY:$ipaddr", $data,600);
		echo json_encode($array);
		return;
	}
	
	$hostname=gethostbyaddr($ipaddr);
	$description="$hostname (from {$_SERVER["REMOTE_ADDR"]})";
	$date=date("Y-m-d H:i:s");
	
	$q->QUERY_SQL("INSERT INTO rbl_blacklists (ipaddr,description,zDate) VALUES ('$ipaddr','$description','$date')");
	if(!$q->ok){
		$array["QUERY"]=$ipaddr;
		$array["ERROR"]="Internal Error $q->mysql_error";
		$array["STATUS"]="FAILED";
		artica_mysql_events(0,"REST Receiver Failed $q->mysql_error",null,__FILE__,__LINE__);
		echo json_encode($array);
		return;
	}
	
	$array["FOUND"]=true;
	$array["TYPE"]="blacklisted";
	$array["date"]=time();
	$array["QUERY"]=$ipaddr;
	$array["STATUS"]="OK";
	$mem->saveKey("RBLQUERY:$ipaddr", json_encode($array),600);
	echo json_encode($array);
	
	
	$AbuseIPApiKey=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AbuseIPApiKey");
	if($AbuseIPApiKey==null){return;}
	
	
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$url="https://www.abuseipdb.com/report/json?key=$AbuseIPApiKey&category=11&ip=$ipaddr";
	writelogs($url,__FUNCTION__,__FILE__,__LINE__);
	$curl=new ccurl($url);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		artica_mysql_events(0,"REST Failed to AbuseIPDB ERR.$curl->error",@implode("\n", $curl->errors)."\n".$curl->data,__FILE__,__LINE__);
		return;
	}
	artica_mysql_events(0,"REST Success to AbuseIPDB",$curl->data,__FILE__,__LINE__);
	
	return;
	
}

function get_email_blk(){
    $email=$_GET["email"];
    $mem=new lib_memcached();
    if(!$GLOBALS["VERBOSE"]) {
        $results = $mem->getKey("RBLQUERY:$email");
        if ($mem->MemCachedFound) {
            echo $results;
            return;
        }
    }
    $q=new postgres_sql();

    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM rbl_emails WHERE pattern='$email' AND enabled=1"));

    if(!$q->ok){
        VERBOSE("MySQL ERROR",__LINE__);
        $array["FOUND"]=false;
        $array["TYPE"]="Error";
        $array["QUERY"]=$email;
        $data=json_encode($array);
        echo $data;
        return;
    }

    if($ligne["pattern"]<>null){
        $array["FOUND"]=true;
        $array["TYPE"]="Blacklisted eMail";
        $array["date"]=strtotime($ligne["zdate"]);
        $array["QUERY"]=$email;
        $data=json_encode($array);
        $mem->saveKey("RBLQUERY:$email", $data,600);
        echo $data;
        return;
    }

    $split=explode("@",$email);
    $domain=$split[1];
    if(!$GLOBALS["VERBOSE"]) {
        $results = $mem->getKey("RBLQUERY:$domain");
        if ($mem->MemCachedFound) {
            echo $results;
            return;
        }
    }

    $q=new postgres_sql();
    VERBOSE("SELECT * FROM rbl_emails WHERE pattern='$domain' AND enabled=1",__LINE__);
    $ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM rbl_emails WHERE pattern='$domain'"));

    if(!$q->ok){
        VERBOSE("$domain: MySQL ERROR",__LINE__);
        $array["FOUND"]=false;
        $array["TYPE"]="Error";
        $array["QUERY"]=$domain;
        $data=json_encode($array);
        echo $data;
        return;
    }

    if($ligne["pattern"]<>null){
        $array["FOUND"]=true;
        $array["TYPE"]="Blacklisted domain";
        $array["date"]=strtotime($ligne["zdate"]);
        $array["QUERY"]=$domain;
        $data=json_encode($array);
        $mem->saveKey("RBLQUERY:$domain", $data,600);
        echo $data;
        return;
    }

    $array["FOUND"]=false;
    $array["TYPE"]="unknown";
    $array["QUERY"]=$email;
    $data=json_encode($array);
    echo $data;

}

function getblacklist(){
    $CurrentDate=date("Y-m-d H:i:s");
	$ipaddr=$_GET["getblacklist"];
	$mem=new lib_memcached();
	$results=$mem->getKey("RBLQUERY:$ipaddr");

	if(!$GLOBALS["VERBOSE"]) {
        if ($mem->MemCachedFound) {
            echo $results;
            return;
        }
    }
	$q=new postgres_sql();
	if(!$q->TABLE_EXISTS("ip_reputation")){
	    VERBOSE("ip_reputation, no such table",__LINE__);
	    $q->SMTP_TABLES();
	}
	$zInfos=array();

    $ligneInfo=$q->mysqli_fetch_array("SELECT * from ip_reputation WHERE ipaddr='$ipaddr'");
    if($ligneInfo["zdate"]==null){
        $q->QUERY_SQL("INSERT INTO ip_reputation (zdate,ipaddr,is_parsed,isUnknown) VALUES ('$CurrentDate','$ipaddr',0,0)");
    }else{
        if($ligneInfo["is_parsed"]==1){
                foreach ($ligneInfo as $index=>$value){
                    if(is_numeric($index)){continue;}
                    $zInfos[$index]=$value;
                }

        }


    }


	$ligne=$q->mysqli_fetch_array("SELECT * FROM rbl_blacklists WHERE ipaddr='$ipaddr'");


	
	if(!$q->ok){
		$array["FOUND"]=false;
		$array["TYPE"]="Error";
		$array["QUERY"]=$ipaddr;
		$data=json_encode($array);
		echo $data;
		return;
	}
	
	if($ligne["ipaddr"]<>null){
		$array["FOUND"]=true;
		$array["TYPE"]="blacklisted";
		$array["date"]=strtotime($ligne["zdate"]);
		$array["QUERY"]=$ipaddr;
        $array["zInfos"]=$zInfos;
		$data=json_encode($array);
		$mem->saveKey("RBLQUERY:$ipaddr", $data,600);
		echo $data;
		return;
	}
	
	
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM rbl_whitelists WHERE ipaddr='$ipaddr'"));
	if($ligne["ipaddr"]<>null){
		$array["FOUND"]=true;
		$array["TYPE"]="whitelisted";
		$array["date"]=strtotime($ligne["zdate"]);
		$array["QUERY"]=$ipaddr;
        $array["zInfos"]=$zInfos;
		$data=json_encode($array);
		$mem->saveKey("RBLQUERY:$ipaddr", $data,600);
		echo $data;
		return;
	}

	$array["FOUND"]=false;
	$array["TYPE"]="unknown";
	$array["QUERY"]=$ipaddr;
    $array["zInfos"]=$zInfos;
	$data=json_encode($array);
	echo $data;
    $q->QUERY_SQL("UPDATE ip_reputation SET isUnknown=1 WHERE ipaddr='$ipaddr'");
	
}
?>