<?php

$GLOBALS["GroupType"]["src"]="{src_addr}";
$GLOBALS["GroupType"]["arp"]="{ComputerMacAddress}";
$GLOBALS["GroupType"]["dst"]="{dst_addr}";
$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
$GLOBALS["GroupType"]["dstdom_regex"]="{dstdomain_regex}";
$GLOBALS["GroupType"]["browser"]="{browser}";

if(!isset($_SERVER["HTTP_ARTICAKEY"])){die();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__).'/ressources/class.rest.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDRESTFulEnabled"))==0){$RestAPi=new RestAPi();$RestAPi->response("Disabled feature", 503);exit;}

isAuth();


$request_uri=$_SERVER["REQUEST_URI"];
$request_uri=str_replace("/api/rest/proxy/white/", "", $request_uri);
$f=explode("/",$request_uri);

if($f[0]=="list"){LIST_RULES($f[1],$f[2]);exit;}
if($f[0]=="add"){ADD_RULE($f[1],$f[2],$f[3]);exit;}
if($f[0]=="del"){DEL_RULE($f[1]);exit;}
if($f[0]=="apply"){APPLY_RULES();exit;}
if(is_numeric($f[0])){EDIT_RULE($f[0],$f[1],$f[2],$f[3]);exit;}

writelogs("Unable to understand query <{$f[0]}> <{$f[1]}> <{$f[2]}> <{$f[3]}> in $request_uri",__FUNCTION__,__FILE__,__LINE__);
$array["status"]=false;
$array["message"]="Unable to understand query <{$f[0]}> <{$f[1]}> <{$f[2]}> <{$f[3]}> in $request_uri";
$array["results"]=array();
$RestAPi=new RestAPi();
$RestAPi->response(json_encode($array),503);


function isAuth(){
    $RestAPi = new RestAPi();
    $SquidRestFulApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRestFulApi"));
    $SystemRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemRESTFulAPIKey"));



    if(isset($_SERVER["ArticaKey"])){$MyArticaKey=$_SERVER["ArticaKey"];}
    if(isset($_SERVER["HTTP_ARTICAKEY"])){$MyArticaKey=$_SERVER["HTTP_ARTICAKEY"];}
    if($MyArticaKey==null) {
        $array["status"] = false;
        $array["message"] = "Authentication Failed ( missing header or Key)";
        $array["category"] = 0;
        logon_events("FAILED");
        $RestAPi->response(json_encode($array), 407);
        exit;
    }

    if($MyArticaKey==$SystemRESTFulAPIKey){logon_events("OK");return true;}
    if($MyArticaKey==$SquidRestFulApi){logon_events("OK");return true;}

    logon_events("FAILED");
    $array["status"] = false;
    $array["message"] = "Authentication Failed Length=".strlen($MyArticaKey)."/".strlen($SquidRestFulApi);
    $array["category"] = 0;
    $RestAPi->response(json_encode($array), 407);
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
function APPLY_RULES(){
	
	$sock=new sockets();
	$sock->REST_API("/proxy/whitelists/nohupcompile");
	$array["status"]=true;
	$array["message"]="Success";
	$array["results"]=array();
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
}

function EDIT_RULE($ID,$pattern=null,$description=null,$enabled=1){
    $f=array();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    if($pattern<>null){
        if(preg_match("#^b64:(.+)#",$pattern,$re)){$pattern=base64_decode($re[1]);}
    }

	if($pattern<>"none"){
	    $f[]="pattern='$pattern'";

    }
    if($description<>"none"){
        $description=$q->sqlite_escape_string2($description);
        $f[]="description='$description'";

    }

    $f[]="enabled=$enabled";


    $sql="UPDATE acls_whitelist SET ".@implode(",",$f). " WHERE ID=$ID";
    $q->QUERY_SQL($sql);

    if(!$q->ok){
        $array["status"]=false;
        $array["message"]="$q->mysql_error";
        $array["count"]=0;
        $array["results"]=null;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),503);
        exit;
    }

    $array["status"]=true;
    $array["message"]="Success";
    $array["count"]=1;
    $array["results"]=null;
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    exit;
	
}

function ADD_RULE($pattern=null,$type=null,$description=null){
    if(preg_match("#^b64:(.+)#",$pattern,$re)){$pattern=base64_decode($re[1]);}
    $pattern=trim(strtolower($pattern));
    $type=trim(strtolower($type));

    if($pattern==null){
        $array["status"]=false;
        $array["message"]="Pattern is null";
        $array["count"]=0;
        $array["results"]=null;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;

    }

    if(!isset($GLOBALS["GroupType"][$type])){
        $array["status"]=false;
        $array["message"]="Type $type not supported";
        $array["count"]=0;
        $array["results"]=null;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;
    }

    $ip=new IP();
    $lib=new lib_memcached();


    $item=trim(strtolower($pattern));

    if( ($type=="src") OR ($type=="dst")){
            if(!$ip->isIPAddressOrRange($item)){
                $array["status"]=false;
                $array["message"]="Type $type $item not supported";
                $array["count"]=0;
                $array["results"]=null;
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array),503);
                exit;
            }
        }


        if( $type=="arp"){
            $item=str_replace("-", ":", $item);
            if(!$ip->IsvalidMAC($item)){
                    $array["status"]=false;
                    $array["message"]="Type $type $item not supported";
                    $array["count"]=0;
                    $array["results"]=null;
                    $RestAPi=new RestAPi();
                    $RestAPi->response(json_encode($array),503);
                    exit;
            }
        }

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $description=$q->sqlite_escape_string2($description);
        $line=str_replace("^","",$item);
        if(substr($line,0,1)=="."){$line=substr($line, 1,strlen($line));}
        $lib->saveKey("isWhite:$line",true,300);
        $zDate=date("Y-m-d H:i:s");
        $item=$q->sqlite_escape_string2($item);
        $f[]="('$zDate','$type','$item',1,'$description')";
        $q->QUERY_SQL("INSERT INTO acls_whitelist (zDate,ztype,pattern,enabled,description) VALUES ".@implode(",", $f));

        if(!$q->ok){
            $array["status"]=false;
            $array["message"]="$q->mysql_error";
            $array["count"]=0;
            $array["results"]=null;
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),503);
            exit;
        }

    $array["status"]=true;
    $array["message"]="Success";
    $array["count"]=1;
    $array["results"]=null;
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    exit;

}

function DEL_RULE($ID){

	$ID=intval($ID);
	if($ID==0){
		$array["status"]=false;
		$array["message"]="ID is null";
		$array["count"]=0;
		$array["results"]=null;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
	
	}
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$q->QUERY_SQL("DELETE FROM acls_whitelist WHERE ID='$ID'");
	
	if(!$q->ok){
		$array["status"]=false;
		$array["message"]=$q->mysql_error;
		$array["count"]=0;
		$array["results"]=null;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
	}	
	
	$array["status"]=true;
	$array["message"]="Success";
	$array["count"]=1;
	$array["results"]=array();
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	exit;
	
}

function LIST_RULES($pattern=null,$max=250){
    if($pattern<>null){
        if(preg_match("#^b64:(.+)#",$pattern,$re)){$pattern=base64_decode($re[1]);}
    }
    if($pattern=="all"){$pattern=null;}
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$tpl=new template_admin();

	if($pattern<>null){
        $pattern=str_replace("*","%",$pattern);
        $pattern=" WHERE (pattern LIKE '$pattern' OR description LIKE '$pattern')";
    }

	if($max==0){$max=250;}

    $sql="SELECT * FROM acls_whitelist{$pattern} ORDER by zDate DESC";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){

        $array["status"]=false;
        $array["message"]="$q->mysql_error";
        $array["count"]=0;
        $array["results"]=array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),503);
        return;}


   	$results=$q->QUERY_SQL($sql);
	$count=count($results);

    foreach ($GLOBALS["GroupType"] as $type=>$desc) {
        $types[$type]=$tpl->_ENGINE_parse_body($desc);
	}
	
	$array["status"]=true;
	$array["message"]="Global Whitelist MAX items=$max $pattern";
	$array["count"]=$count;
	$array["results"]=$results;
	$array["AVAILABLE_TYPES"]=$types;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	
}

