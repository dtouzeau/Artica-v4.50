<?php
if(!isset($_SERVER["HTTP_ARTICAKEY"])){die();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__).'/ressources/class.rest.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDRESTFulEnabled"))==0){$RestAPi=new RestAPi();$RestAPi->response("Disabled feature", 503);exit;}

isAuth();


$request_uri=$_SERVER["REQUEST_URI"];
$request_uri=str_replace("/api/rest/proxy/stats/", "", $request_uri);
$f=explode("/",$request_uri);

if($f[0]=="members"){MEMBERS_STATS($f[1]);exit;}
if($f[0]=="status"){PROXY_STATUS();exit;}
if($f[0]=="today"){PROXY_TODAY();exit;}

writelogs("Unable to understand query <{$f[0]}> <{$f[1]}> <{$f[2]}> <{$f[3]}> in $request_uri",__FUNCTION__,__FILE__,__LINE__);
$array["status"]=false;
$array["message"]="Unable to understand query <{$f[0]}> <{$f[1]}> <{$f[2]}> <{$f[3]}> in $request_uri";
$array["results"]=array();
$RestAPi=new RestAPi();
$RestAPi->response(json_encode($array),404);


function isAuth(){
    $RestAPi = new RestAPi();
    $SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");

    if($SQUIDEnable==0){
        $array["status"] = false;
        $array["message"] = "Proxy is not Activated";
        $array["category"] = 0;
        $RestAPi->response(json_encode($array), 500);
        exit;
    }

    $SQUIDRESTFulEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDRESTFulEnabled"));

    if($SQUIDRESTFulEnabled==0){
        $array["status"] = false;
        $array["message"] = "Proxy RestFul service is not Activated";
        $array["category"] = 0;
        $RestAPi->response(json_encode($array), 500);
        logon_events("FAILED");
        exit;
    }

    $EnableSquidLogger=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidLogger"));

    if($EnableSquidLogger==0){
        $array["status"] = false;
        $array["message"] = "Proxy Logger is not Activated";
        $array["category"] = 0;
        $RestAPi->response(json_encode($array), 500);
        logon_events("FAILED");
        exit;
    }


    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));

    if($EnableRedisServer==0){
        $array["status"] = false;
        $array["message"] = "Key-pair service is not Activated";
        $array["category"] = 0;
        $RestAPi->response(json_encode($array), 500);
        exit;
    }



    $SquidRestFulApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesRESTFulAPIKey"));
    $SystemRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemRESTFulAPIKey"));



    if(isset($_SERVER["ArticaKey"])){$MyArticaKey=$_SERVER["ArticaKey"];}
    if(isset($_SERVER["HTTP_ARTICAKEY"])){$MyArticaKey=$_SERVER["HTTP_ARTICAKEY"];}
    if($MyArticaKey==null) {
        $array["status"] = false;
        $array["message"] = "Authentication Failed ( missing header)";
        $array["category"] = 0;
        $RestAPi->response(json_encode($array), 407);
        logon_events("FAILED");
        exit;
    }

    if($MyArticaKey==$SystemRESTFulAPIKey){return true;}
    if($MyArticaKey==$SquidRestFulApi){return true;}


    $array["status"] = false;
    $array["message"] = "Authentication Failed";
    $array["category"] = 0;
    $RestAPi->response(json_encode($array), 407);
    logon_events("FAILED");
    exit;

}

function logon_events($succes){
    if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
    if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
    $logFile="/var/log/artica-webauth.log";
    $date=date('Y-m-d H:i:s');
    $f = fopen($logFile, 'a');
    fwrite($f, "$date $IPADDR $succes\n");
    fclose($f);
}

function MEMBERS_STATS($command,$value1=null,$value2=null){
	
	if($command=="sum"){MEMBERS_SUM();exit;}
	if($command=="list"){MEMBERS_LIST();exit;}
	if($command=="del"){DENY_CACHE_DEL($value1);exit;}
	if($command=="apply"){DENY_CACHE_APPLY($value1);exit;}

    MEMBERS_LIST();
}

function PROXY_STATUS(){
    $tpl = new template_admin();
    $xkey = $tpl->time_key_10mn();
    $KeyTotalHits = "WebStats:$xkey:TotalHits";
    $KeyTotalSize = "WebStats:$xkey:TotalSize";
    $KeyUsersList = "WebStats:$xkey:CurrentUsers";
    $KeyDomainsList = "WebStats:$xkey:CurrentDomains";

    $redis = new Redis();
    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        $RestAPi=new RestAPi();
        $array["status"] = false;
        $array["message"] = $e->getMessage();
        $array["results"] = false;
        $RestAPi->response(json_encode($array), 500);
        exit;
    }


    $TotalHits = $redis->get($KeyTotalHits);
    $TotalSize = $redis->get($KeyTotalSize);
    $Members = $redis->sMembers($KeyUsersList);
    $Domains=$redis->sMembers($KeyDomainsList);
    $redis->close();
    $RestAPi=new RestAPi();
    $array["status"] = false;
    $array["message"] = "Number of hits, members and bandwidth (each 10mn)";
    $array["results"] = array(
        "hits"=>$TotalHits,
        "bandwidth_bytes"=>$TotalSize,
        "members_count"=>count($Members),
        "domains_count"=>count($Domains)
    );
    $RestAPi->response(json_encode($array), 200);


}



function MEMBERS_LIST(){
    $tpl = new template_admin();
    $xkey = $tpl->time_key_10mn();
    $KeyUsersList = "WebStats:$xkey:CurrentUsers";
    $redis = new Redis();
    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        $RestAPi=new RestAPi();
        $array["status"] = false;
        $array["message"] = $e->getMessage();
        $array["results"] = false;
        $RestAPi->response(json_encode($array), 500);
        exit;
    }

    $Members = $redis->sMembers($KeyUsersList);
    foreach ($Members as $UserName) {
        $RQSKey = "WebStats:$xkey:CurrentUser:RQS:$UserName";
        $SizeKey = "WebStats:$xkey:CurrentUser:Size:$UserName";
        $KeyUserIP="WebStats:$xkey:CurrentUserIP:$UserName";
        $LIST[$UserName]["ipaddr"]=$redis->get($KeyUserIP);
        $LIST[$UserName]["requests"]=$redis->get($RQSKey);
        $LIST[$UserName]["bandwidth_bytes"]=$redis->get($SizeKey);
    }

	$array["status"]=true;
	$array["message"]="Success";
	$array["count"]=count($LIST);
	$array["results"]=$LIST;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	exit;
	
}

function DENY_CACHE_ADD($ztype=0,$item=null){
	$tpl=new template_admin();
	$acl=new squid_acls();
	$item=strtolower($item);
	if($item==null){
		$array["status"]=false;
		$array["message"]="Item is null";
		$array["count"]=0;
		$array["results"]=null;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),200);
		exit;
		
	}
	
	$ztype=intval($ztype);
	$IP=new IP();
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL("DELETE FROM deny_cache_domains WHERE items='$item'");
	
	if(!$q->ok){
		$array["status"]=false;
		$array["message"]=$q->mysql_error;
		$array["count"]=0;
		$array["results"]=null;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),200);
		exit;
	}
	
	
	if($IP->isIPAddressOrRange($item)){if($ztype==0){$ztype=1;}}else{
		$www=$tpl->CleanWebSite($item);
		if(substr($www,0, 1)<>"^"){$item=$acl->dstdomain_parse($www);}
	}
	
	if($www==null){
		$array["status"]=false;
		$array["message"]="Item is null";
		$array["count"]=0;
		$array["results"]=null;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),200);
		exit;
	}
	
	$q->QUERY_SQL("INSERT OR IGNORE INTO deny_cache_domains (items,ztype) VALUES ('{$item}','$ztype')");
	if(!$q->ok){
		$array["status"]=false;
		$array["message"]=$q->mysql_error;
		$array["count"]=0;
		$array["results"]=null;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),200);
		exit;
		return;
	}

	$array["status"]=true;
	$array["message"]="Success";
	$array["count"]=1;
	$array["results"]="$item";
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	exit;
}

function PROXY_TODAY(){


    $q=new postgres_sql();
    $today=date("Y-m-d 00:00:00");
    $results=$q->QUERY_SQL("SELECT hits,size, zdate FROM \"bandwidth_table\" WHERE zdate>'$today' order by zdate ASC ");




    while($ligne=@pg_fetch_assoc($results)){
        $size=$ligne["size"];
        $hits=$ligne["hits"];
        $time=strtotime($ligne["zdate"]);
        $xdata[]=date("H:i",$time);
        $ydata[]=$size;
        $ydata2[]=$hits;
    }


    $results=$q->QUERY_SQL("SELECT count(userid) as users, zdate FROM \"access_users\" WHERE zdate>'$today' GROUP BY zdate order by zdate ASC ");
    while($ligne=@pg_fetch_assoc($results)){
        $ydata3[]=$ligne["users"];
    }

    $array["status"]=true;
    $array["message"]="Today, size in bytes and requests";
    $array["count"]=1;
    $array["results"]=array("time"=>$xdata,"size_bytes"=>$ydata,"hits"=>$ydata2,"members"=>$ydata3);
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
	
}
?>