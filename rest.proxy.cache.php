<?php
if(!isset($_SERVER["HTTP_ARTICAKEY"])){die();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__).'/ressources/class.rest.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDRESTFulEnabled"))==0){
    events("Rest-API Feature is disabled",__LINE__);
    $RestAPi=new RestAPi();
    $RestAPi->response("Disabled feature", 503);
    exit;
}

isAuth();


$request_uri=$_SERVER["REQUEST_URI"];
if(strpos("$request_uri","?verbose=yes")){
    $GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
    $request_uri=str_replace("?verbose=yes","",$request_uri);
}
$request_uri=str_replace("/api/rest/proxy/cache/", "", $request_uri);
$f=explode("/",$request_uri);

events("Query ".@implode(", ",$f),__LINE__);

if(!isset($f[2])){$f[2]=null;}
if(!isset($f[3])){$f[3]=null;}
if(!isset($f[4])){$f[4]=null;}
if(!isset($f[5])){$f[5]=null;}
if($GLOBALS["VERBOSE"]){
    echo "Parameter[0] = '{$f[0]}'\n";
    echo "Parameter[1] = '{$f[1]}'\n";
    echo "Parameter[2] = '{$f[2]}'\n";
}

if($f[0]=="nodes"){NODES_COMMANDS($f[1],$f[2],$f[3]);exit;}
if($f[0]=="objects"){ACLS_OBJECTS_COMMANDS($f[1],$f[2],$f[3]);exit;}
if($f[0]=="pac"){PROXYPAC_COMMANDS($f[1],$f[2],$f[3],$f[4],$f[5]);exit;}
if($f[0]=="deny"){DENY_CACHE_COMMANDS($f[1],$f[2],$f[3]);exit;}
if($f[0]=="notrack"){NOTRACK_COMMANDS($f[1],$f[2],$f[3]);exit;}
if($f[0]=="snmp"){SNMP_COMMANDS($f[1],$f[2],$f[3]);exit;}
if($f[0]=="ssl"){SSL_COMMANDS($f[1],$f[2]);exit;}
if($f[0]=="activedirectory"){REST_ACTIVEDIRECTORY($f[1]);exit;}
writelogs("Unable to understand query <{$f[0]}> <{$f[1]}> <{$f[2]}> <{$f[3]}> in $request_uri",__FUNCTION__,__FILE__,__LINE__);
$array["status"]=false;
$array["message"]="Unable to understand query <{$f[0]}> <{$f[1]}> <{$f[2]}> <{$f[3]}> in $request_uri";
$array["results"]=array();
$RestAPi=new RestAPi();
$RestAPi->response(json_encode($array),404);

function REST_ACTIVEDIRECTORY($cmd=null){

    if($cmd=="status"){
        REST_ACTIVEDIRECTORY_STATUS();
        exit;
    }

    $array["status"]=false;
    $array["message"]="Unable to understand query <$cmd>";
    $array["results"]=array();
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),404);

}

function NODES_COMMANDS($command,$value1=null,$value2=null){


    if($command=="list"){
        $q=new lib_sqlite("/home/artica/SQLITE/mgr_client_list.db");
        $sql="SELECT ipaddr,RQS FROM mgr_client_list ORDER BY RQS DESC LIMIT 500";
        $results=$q->QUERY_SQL($sql);

        if(!$q->ok){
            events($q->mysql_error,__LINE__);
            $array["status"] = false;
            $array["count"] = 0;
            $array["message"] = "$q->mysql_error";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }

        $array["message"]="Connected nodes during 10mn";
        $array["status"] = true;
        $array["count"]=count($results);
        $array["results"] = $results;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 200);


    }

    $array["status"] = false;
    $array["count"] = 0;
    $array["message"] = "Unable to understand $command";
    $array["results"] = array();
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array), 407);
    die();

}


function ACLS_OBJECTS_COMMANDS($command,$value1=null,$value2=null){


    if(is_numeric($command)){
        $gpid=intval($command);
        if($value1==null){
            $array["status"]=false;
            $array["message"]="Unable to understand <$command> <$value1>";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),404);
            exit;
        }
        if($value1=="enable"){
            $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
            $q->QUERY_SQL("UPDATE webfilters_sqgroups SET enabled=1 WHERE ID=$gpid");
            if(!$q->ok){
                events($q->mysql_error,__LINE__);
                $array["status"] = false;
                $array["count"] = 0;
                $array["message"] = "$q->mysql_error";
                $array["results"] = array();
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array), 407);
                die();
            }
            $array["message"]="Enable object ID $gpid";
            $array["status"] = true;
            $array["count"]=1;
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 200);
        }
        if($value1=="disable"){
            $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
            $q->QUERY_SQL("UPDATE webfilters_sqgroups SET enabled=0 WHERE ID=$gpid");
            if(!$q->ok){
                events($q->mysql_error,__LINE__);
                $array["status"] = false;
                $array["count"] = 0;
                $array["message"] = "$q->mysql_error";
                $array["results"] = array();
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array), 407);
                die();
            }
            $array["message"]="Disable object ID $gpid";
            $array["status"] = true;
            $array["count"]=1;
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 200);
        }
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $q->QUERY_SQL("UPDATE webfilters_sqgroups SET GroupName='$value1' WHERE ID=$gpid");
        if(!$q->ok){
            events($q->mysql_error,__LINE__);
            $array["status"] = false;
            $array["count"] = 0;
            $array["message"] = "$q->mysql_error";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $array["message"]="Rename object ID $gpid with $value1";
        $array["status"] = true;
        $array["count"]=1;
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        exit;
    }

    if($command=="search"){
        ACLS_OBJECTS_SEARCH($value1);
        exit;
    }

    if($command=="add"){
        ACLS_OBJECTS_CREATE($value1,$value2);
        exit;
    }
    if($command=="del"){
        ACLS_OBJECTS_DEL($value1);
        exit;
    }

    if($command=="types"){
        ACLS_OBJECTS_TYPES();
        exit;
    }

    if($command=="items"){
        ACLS_OBJECTS_DUMP_ITEMS(intval($value1));
        exit;
    }

    if($command=="add-item"){
        ACLS_OBJECTS_ADD_ITEM($value1,$value2);
        exit;
    }
    if($command=="del-item"){
        ACLS_OBJECTS_DEL_ITEM($value1);
        exit;
    }

    $array["status"]=true;
    $array["message"]="Unable to understand <$command> <$value1>";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),404);
    exit;

}

function ACLS_OBJECTS_TYPES(){
    $tpl=new template_admin();
    $q=new mysql_squid_builder();

    foreach ($q->acl_GroupType as $type=>$description){
        if(isset($q->acl_GroupType_WPAD[$type])){
            $description=$description." ({APP_PROXY_PAC} compatible)";
        }
        $RES[$type]=$tpl->javascript_parse_text($description);

    }


    $array["message"]="List of ALCs Objects types";
    $array["status"] = true;
    $array["count"]=count($RES);
    $array["results"] = $RES;
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array), 200);

}

function ACLS_OBJECTS_DEL($gpid){

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT ID,GroupName FROM webfilters_sqgroups WHERE ID=$gpid";
    $ligne=$q->mysqli_fetch_array($sql);
    $GroupName=$ligne["GroupName"];

    $ID=intval($ligne["ID"]);
    if($ID==0){
        $array["status"] = false;
        $array["message"] = "Group $gpid doesn't exists";
        $array["count"] = 0;
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 407);
        die();

    }
    $acls=new squid_acls();
    $acls->delete_group($gpid);
    $array["message"]="Deleted object $GroupName ID $gpid";
    $array["status"] = true;
    $array["count"]=1;
    $array["results"] = array();
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array), 200);
}

function ACLS_OBJECTS_DEL_ITEM($itemid){

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT ID,pattern FROM webfilters_sqitems WHERE ID=$itemid";
    $ligne=$q->mysqli_fetch_array($sql);
    $ID=intval($ligne["ID"]);
    $pattern=$ligne["pattern"];
    if($ID==0){
        $array["status"] = false;
        $array["message"] = "Item $itemid doesn't exists";
        $array["count"] = 0;
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 407);
        die();

    }

    $q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE ID=$itemid");
    if(!$q->ok){
        events($q->mysql_error,__LINE__);
        $array["status"] = false;
        $array["count"] = 0;
        $array["message"] = "$q->mysql_error";
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 407);
        die();
    }

    $array["message"]="Deleted item $pattern ID $itemid";
    $array["status"] = true;
    $array["count"]=1;
    $array["results"] = array();
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array), 200);

}

function ACLS_OBJECTS_ADD_ITEM($gpid,$pattern){
    $pattern=url_decode_special_tool($pattern);
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT ID,GroupName FROM webfilters_sqgroups WHERE ID=$gpid";
    $ligne=$q->mysqli_fetch_array($sql);
    $GroupName=$ligne["GroupName"];
    $ID=intval($ligne["ID"]);
    if($ID==0){
        $array["status"] = false;
        $array["message"] = "Group $gpid doesn't exists";
        $array["count"] = 0;
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 407);
        die();

    }
    $description=md5(time().$gpid.$pattern);
    $sql="INSERT INTO webfilters_sqitems (gpid,pattern,description,enabled) VALUES ('$gpid','$pattern','$description',1)";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        events($q->mysql_error,__LINE__);
        $array["status"] = false;
        $array["count"] = 0;
        $array["message"] = "$q->mysql_error";
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 407);
        die();
    }
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqitems WHERE description='$description'");
    $ID=intval($ligne["ID"]);
    if($ID>0) {
        $array["New-ID"]=$ID;
        $date = date("Y-m-d H:i:s");
        $q->QUERY_SQL("UPDATE webfilters_sqitems SET description='Added $date by REST API'");
    }

    $array["message"]="Added $pattern ($ID) inside group $gpid ($GroupName)";
    $array["status"] = true;
    $array["count"]=1;
    $array["results"] = array();
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array), 200);


}

function ACLS_OBJECTS_DUMP_ITEMS($pattern){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT * FROM webfilters_sqitems WHERE gpid='$pattern'";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        events($q->mysql_error,__LINE__);
        $array["status"] = false;
        $array["count"] = 0;
        $array["message"] = "$q->mysql_error";
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 407);
        die();
    }

    foreach($results as $index=>$ligne) {

        foreach ($ligne as $key=>$value){
            if(is_numeric($key)){continue;}
            $RES[$ligne["ID"]][$key]=$value;
        }

    }

    $array["status"] = true;
    $array["count"]=count($RES);
    $array["results"] = $RES;
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array), 200);

}

function ACLS_OBJECTS_CREATE($GroupName,$GroupType){

    $qlproxy=new mysql_squid_builder();
    if(!isset($qlproxy->acl_GroupType[$GroupType])){
        $array["status"] = false;
        $array["message"] = "$GroupType unknown type";
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 407);
        die();
    }

    $params=md5(time().$GroupName.$GroupType);
    $sqladd="INSERT INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`,`PortDirection`,`tplreset`)
	VALUES ('$GroupName','$GroupType','1','','$params','0',0);";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL($sqladd);
    if(!$q->ok){
        events($q->mysql_error,__LINE__);
        $array["status"] = false;
        $array["message"] = "$q->mysql_error";
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 407);
        die();
    }

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE params='$params'");
    $ID=intval($ligne["ID"]);
    if($ID>0) {
        $q->QUERY_SQL("UPDATE webfilters_sqgroups SET params='' WHERE ID=$ID");
    }
    $array["message"]="Added $GroupName with type $GroupType";
    $array["status"] = true;
    $array["New-ID"]=$ID;
    $array["count"]=1;
    $array["results"] = array();
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array), 200);
}

function ACLS_OBJECTS_SEARCH($pattern){

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $pattern=str_replace("*","%",$pattern);
    $pattern=trim(str_replace("%%","%",$pattern));

    $array["message"] = "ACLS objects LIKE '$pattern'";
    $sql="SELECT * FROM webfilters_sqgroups WHERE GroupName LIKE '$pattern'";
    if($pattern=="%"){
        $sql="SELECT * FROM webfilters_sqgroups";
        $array["message"] = "List all ACLS objects";
    }

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        events($q->mysql_error,__LINE__);
        $array["status"] = false;
        $array["message"] = "$q->mysql_error";
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 407);
        die();
    }

    foreach($results as $index=>$ligne) {

        foreach ($ligne as $key=>$value){
            if(is_numeric($key)){continue;}
            $RES[$ligne["ID"]][$key]=$value;
        }

    }


    $array["status"] = true;
    $array["count"]=count($RES);
    $array["results"] = $RES;
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array), 200);


}


function SSL_COMMANDS($cmd1=null,$cmd2=null){

    if($GLOBALS["VERBOSE"]){echo "SSL_COMMANDS($cmd1,$cmd2)\n";}

    if($cmd1=="emergency"){

        if($cmd2=="on"){
            $sock=new sockets();
            $sock->REST_API("/proxy/ssl/emergency/on");
            $array["status"]=true;
            $array["message"]="Turn ON SSL Emergency";
            $array["results"]=array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),200);
            exit;

        }
        if($cmd2=="off"){
            $sock=new sockets();
            $sock->REST_API("/proxy/ssl/emergency/off");
            $array["status"]=true;
            $array["message"]="Turn OFF SSL Emergency";
            $array["results"]=array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),200);
            exit;
        }


        $array["status"]=true;
        $array["message"]="Unable to understand <$cmd2>";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),404);
        exit;

    }

    if($cmd1=="status"){
        $SquidSSLUrgency = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSSLUrgency"));
        $array["status"]=true;
        if($SquidSSLUrgency==1) {
            $array["message"] = "Proxy is on SSL Emergency mode";
            $array["emergency"]=1;



        }else{
            $array["message"] = "Everything is fine";
            $array["emergency"]=0;
        }

        $f=explode("\n",@file_get_contents("/etc/squid3/listen_ports.conf"));
        $array["ports"]=array();
        foreach ($f as $line){
            $line=trim($line);
            if(preg_match("#^https_port\s+(.*?):([0-9]+)#",$line,$re)){
                $mode="Connected Port";
                if(preg_match("#(intercept|tproxy)#",$line)){
                    $mode="Transparent Port";
                }
                $array["ports"][]="{$re[1]}:{$re[2]} ($mode)";
                continue;

            }
            if($GLOBALS["VERBOSE"]){echo "$line NO MATCHES\n";}
        }

        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;
    }


    $array["status"]=true;
    $array["message"]="Unable to understand <$cmd1>";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),404);
    exit;

}

function REST_ACTIVEDIRECTORY_STATUS(){
    $EnableKerbAuth                 = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $WindowsActiveDirectoryKerberos = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
    $LockActiveDirectoryToKerberos  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $ActiveDirectoryEmergency       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryEmergency"));
    $SquidUrgency                   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
    $NTLM                           = false;
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}

    if($LockActiveDirectoryToKerberos==1){$EnableKerbAuth=1;$WindowsActiveDirectoryKerberos=1;}
    $RestAPi                        = new RestAPi();
    $status                         = true;
    $messages                       = array();

    if($EnableKerbAuth==0){
        $array["status"] = false;
        $array["message"] = "Feature disabled";
        $array["category"] = 0;
        $RestAPi->response(json_encode($array), 407);
    }

    if($ActiveDirectoryEmergency==1){
        $array["status"] = false;
        $array["message"] = "Emergency for Active Directory";
        $array["category"] = 0;
        $RestAPi->response(json_encode($array), 407);
    }

    if($SquidUrgency==1){
        $array["status"] = false;
        $array["message"] = "Emergency for Proxy";
        $array["category"] = 0;
        $RestAPi->response(json_encode($array), 407);
    }

    $arrayC=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    if($WindowsActiveDirectoryKerberos==0){
        $NTLM=true;
        $CONFIG["NTLM"]="yes";
        $CONFIG["KERBEROS"]="no";
    }else{
        $CONFIG["NTLM"]="no";
        $CONFIG["KERBEROS"]="yes";
    }

    foreach ($arrayC as $key=>$val){
        if(preg_match("#_PASS#",$key)){continue;}
        $CONFIG[$key]=$val;

    }
    if($NTLM) {
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("winbindd.php?status=yes");
        $ini = new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/winbindd.status");
        $array["WINBIND_STATUS"] = $ini->_params["SAMBA_WINBIND"];
        if($ini->_params["SAMBA_WINBIND"]["running"]==0){
            $status=false;
            $messages[]="Winbind service is stopped";
        }


        $datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->getFrameWork("samba.php?netrpctestjoin=yes")));
        $test_results=test_results($datas);
        if($test_results[1]){
            $status=false;
            $messages[]="Test join failed";
            $events[]=@implode($test_results[0]);
        }else{
            $messages[]="Test join Success";
        }


        $AR["USER"]=$arrayC["WINDOWS_SERVER_ADMIN"];
        $AR["PASSWD"]=$arrayC["WINDOWS_SERVER_PASS"];
        $cmdline=base64_encode(serialize($AR));
        $datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->getFrameWork("samba.php?wbinfoalldom=yes&auth=$cmdline")));
        $test_results=test_results($datas);
        if($test_results[1]){
            $status=false;
            $messages[]="Browse Domain failed";
            $events[]=@implode("\n",$test_results[0]);
        }else{
            $messages[]="Browse Domain Success";

        }

        $datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->getFrameWork("samba.php?wbinfomoinsa=yes&auth=$cmdline")));
        $test_results=test_results($datas);
        if($test_results[1]){
            $status=false;
            $messages[]="Domain info failed (1)";
            $events[]=@implode("\n",$test_results[0]);
        }else{
            $messages[]="Domain info Success (1)";

        }
        $datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->getFrameWork("samba.php?wbinfomoinst=yes&auth=$cmdline")));
        $test_results=test_results($datas);
        if($test_results[1]){
            $status=false;
            $messages[]="Domain info failed (2)";
            $events[]=@implode("\n",$test_results[0]);
        }else{
            $messages[]="Domain info Success (2)";

        }
    }

    $array["status"] = $status;
    $array["message"] = @implode(", ",$messages);
    $array["configuration"] = $CONFIG;
    $array["events"] = $events;
    if(!$status) {
        $RestAPi->response(json_encode($array), 407);
    }
    $RestAPi->response(json_encode($array), 200);
}
function test_results($array){
    $html=null;
    $ERROR=false;
    foreach ($array as $num=>$ligne){
        $ligne=trim($ligne);
        if($ligne==null){continue;}
        $color="black";
        if(preg_match("#No logon servers#", $ligne)){$ERROR=true;}
        if(preg_match("#invalid permissions#", $ligne)){$ERROR=true;}
        if(preg_match("#No logon#", $ligne)){$ERROR=true;}
        if(preg_match("#No trusted SAM#i", $ligne)){$ERROR=true;}
        if(preg_match("#is not valid#i", $ligne)){$ERROR=true;}
        if(preg_match("#Improperly#i", $ligne)){$ERROR=true;}
        if(preg_match("#(UNSUCCESSFUL|FAILURE|NO_TRUST)#i", $ligne)){$ERROR=true;}
        if(preg_match("#(invalid credential|not correct)#i", $ligne)){$ERROR=true;}
        if(preg_match("#Could not authenticate user\s+.+?\%(.+?)\s+with plaintext#i",$ligne,$re)){$ligne=str_replace($re[1], "*****", $ligne);$ERROR=true;}
        if(preg_match("#Could not#i", $ligne)){$ERROR=true;}
        if(preg_match("#failed#i", $ligne)){$ERROR=true;}
        if(preg_match("#_CANT_#i", $ligne)){$ERROR=true;}
        if(preg_match("#no realm or workgroup#i", $ligne)){$ERROR=true;}

        if($color=="black"){
            if(preg_match("#^(.+?):\s+(.+)#", $ligne,$re)){$ligne="{$re[1]}:{$re[2]}";}
        }
        $html[]="$ligne";
    }
    return array($html,$ERROR);
}




function isAuth(){
    $RestAPi = new RestAPi();
    $SquidRestFulApi=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRestFulApi"));
    $SystemRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemRESTFulAPIKey"));



    if(isset($_SERVER["ArticaKey"])){$MyArticaKey=$_SERVER["ArticaKey"];}
    if(isset($_SERVER["HTTP_ARTICAKEY"])){$MyArticaKey=$_SERVER["HTTP_ARTICAKEY"];}
    if($MyArticaKey==null) {
        $array["status"] = false;
        $array["message"] = "Authentication Failed ( missing header)";
        $array["category"] = 0;
        events("Authentication Failed ( missing header)",__LINE__);
        logon_events("FAILED");
        $RestAPi->response(json_encode($array), 407);
        exit;
    }

    if($MyArticaKey==$SystemRESTFulAPIKey){logon_events("OK");return true;}
    if($MyArticaKey==$SquidRestFulApi){logon_events("OK");return true;}

    logon_events("FAILED");
    $array["status"] = false;
    $array["message"] = "Authentication Failed";
    $array["category"] = 0;
    events("Authentication Failed",__LINE__);
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

function SNMP_COMMANDS($command=null,$value1=null,$value2=null){
    $SNMPDCommunity=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDCommunity");
    $SNMPDNetwork=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDNetwork");
    $SquidSNMPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSNMPPort"));
    $EnableSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSNMPD"));
    $EnableProxyInSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyInSNMPD"));
    if($SquidSNMPPort==0){$SquidSNMPPort=3401;}

    if($command=="apply"){
        if($value1<>null){
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/general/nohup/restart");
            if($EnableProxyInSNMPD==1){
                $GLOBALS["CLASS_SOCKETS"]->REST_API("/snmpd/restart");
            }
            $array["status"]=True;
            $array["message"]="SNMPD Apply OK";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),200);
            return;
        }
    }

    if($command=="merge"){
        if( ($value1=="on") OR ($value1=="enable")){
            if($EnableSNMPD==0){
                $array["status"]=false;
                $array["message"]="Service SNMP is not installed";
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array),500);
                exit;
            }
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableProxyInSNMPD",1);
            $array["status"]=true;
            $array["message"]="Merging proxy SNMP into SNMP service OK";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),200);
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/general/nohup/restart");
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/snmpd/restart");
            return;
        }
        if( ($value1=="off") OR ($value1=="disable")){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableProxyInSNMPD",0);
            $array["status"]=true;
            $array["message"]="Unlink proxy SNMP From SNMP service OK";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),200);
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/general/nohup/restart");
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/snmpd/restart");
            return;


        }
        $array["status"]=false;
        $array["message"]="Please specify on or off";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        return;
    }


    if($command=="community"){
        if($value1<>null){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SNMPDCommunity",$value1);
            $array["status"]=True;
            $array["message"]="SNMPD SNMPDCommunity OK";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),200);
            return;
        }
    }
    if($command=="console"){
        if($value1<>null){
            if($EnableProxyInSNMPD==1){
                $array["status"]=false;
                $array["message"]="Service is Merged with the SNMP service";
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array),500);
                return;
            }
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SNMPDNetwork",$value1);
            $array["status"]=True;
            $array["message"]="SNMPD SNMPDNetwork OK";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),200);
            return;
        }
    }
    if($command=="port"){
        if(intval($value1)>0){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidSNMPPort",$value1);
            $array["status"]=True;
            $array["message"]="SNMPD SquidSNMPPort OK";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),200);
            return;
        }
    }

    if($command=="status"){
        $array["status"]=True;
        $array["message"]="SNMPD configuration";
        $array["settings"]["community"]=$SNMPDCommunity;
        $array["settings"]["console"]=$SNMPDNetwork;
        $array["settings"]["port"]=$SquidSNMPPort;
        $array["settings"]["merged"]=$EnableProxyInSNMPD;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
    }

    $array["status"]=false;
    $array["message"]="Unable to understand query <{$command}> <{$value1}> <{$value2}>";
    $array["results"]=array();
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),404);

}

function NOTRACK_COMMANDS($command=null,$value1=null,$value2=null){
    $array["status"]=false;
    $array["message"]="Depreciated feature";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
}

function PROXYPAC_COMMANDS($command,$value1=null,$value2=null,$value3=null,$value4=null){
    if($command=="rules"){PROXYPAC_RULES();exit;}

    if($command=="compile"){
        $sock=new sockets();
        $sock->REST_API("/proxypac/reconfigure");
        $array["message"]="Compile rules in production mode OK";
        $array["status"] = true;
        $array["count"]=1;
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        die();
        
    }

    if($command=="new") {
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $sql="INSERT INTO wpad_rules (zorder,enabled,rulename,dntlhstname,isResolvable,FinishbyDirect)
				VALUES ('0','1','New Rule','1','0','0')";
        $q->QUERY_SQL($sql);

        if(!$q->ok){
            events($q->mysql_error,__LINE__);
            $array["status"] = false;
            $array["message"] = "$q->mysql_error";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $array["message"]="Created a new Proxy PAC rule";
        $array["status"] = true;
        $array["count"]=1;
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        die();

    }
    if($command=="delete") {
        $ID=intval($value1);
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne=$q->mysqli_fetch_array("SELECT ID,rulename FROM wpad_rules WHERE ID=$ID");
        if(intval($ligne["ID"])==0){
            $array["status"] = false;
            $array["message"] = "Rule $ID Doesn't exists";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $rulename=$ligne["rulename"];
        $q->QUERY_SQL("DELETE FROM `wpad_rules` WHERE ID='$ID'");
        $q->QUERY_SQL("DELETE FROM `wpad_sources_link` WHERE aclid='$ID'");
        $q->QUERY_SQL("DELETE FROM `wpad_black_link` WHERE aclid='$ID'");
        $q->QUERY_SQL("DELETE FROM `wpad_white_link` WHERE aclid='$ID'");
        $q->QUERY_SQL("DELETE FROM `wpad_destination` WHERE aclid='$ID'");
        $q->QUERY_SQL("DELETE FROM `wpad_events` WHERE aclid='$ID'");

        $array["message"]="Deleted Proxy PAC rule $rulename";
        $array["status"] = true;
        $array["count"]=1;
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        die();

    }

    if($command=="parameters"){
        $ruleid=$value1;
        $field=$value2;
        $val=$value3;
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

        $ligne=$q->mysqli_fetch_array("SELECT ID,rulename FROM wpad_rules WHERE ID=$ruleid");
        if(intval($ligne["ID"])==0){
            $array["status"] = false;
            $array["message"] = "Rule $ruleid Doesn't exists";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $rulename=$ligne["rulename"];

        $val=$q->sqlite_escape_string2($val);
        $q->QUERY_SQL("UPDATE wpad_rules SET $field='$val'");
        if(!$q->ok){
            events($q->mysql_error,__LINE__);
            $array["status"] = false;
            $array["message"] = "$q->mysql_error";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }

        $array["message"]="Modify rule $rulename attribute $field with value [$val] for rule $ruleid";
        $array["status"] = true;
        $array["count"]=1;
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        die();
    }

    if($command=="source"){
        $ruleid=$value1;
        $gpid=$value2;

        $zmd5=md5("$ruleid$gpid");
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

        $ligne=$q->mysqli_fetch_array("SELECT ID,rulename FROM wpad_rules WHERE ID=$ruleid");
        if(intval($ligne["ID"])==0){
            $array["status"] = false;
            $array["message"] = "Rule $ruleid Doesn't exists";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $rulename=$ligne["rulename"];


        $q->QUERY_SQL("INSERT INTO wpad_sources_link (zmd5,aclid,negation,gpid,zorder) VALUES ('$zmd5','$ruleid','0','$gpid',1)");
        if(!$q->ok){
            events($q->mysql_error,__LINE__);
            $array["status"] = false;
            $array["message"] = "$q->mysql_error";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $array["message"]="Add ACL Group $gpid to the rule $rulename";
        $array["status"] = true;
        $array["count"]=1;
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        die();
    }
    if($command=="unsource"){
        $ruleid=$value1;
        $gpid=$value2;
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

        $ligne=$q->mysqli_fetch_array("SELECT ID,rulename FROM wpad_rules WHERE ID=$ruleid");
        if(intval($ligne["ID"])==0){
            $array["status"] = false;
            $array["message"] = "Rule $ruleid Doesn't exists";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $rulename=$ligne["rulename"];

        $q->QUERY_SQL("DELETE FROM wpad_sources_link WHERE aclid='$ruleid' AND gpid='$gpid'");
        if(!$q->ok){
            events($q->mysql_error,__LINE__);
            $array["status"] = false;
            $array["message"] = "$q->mysql_error";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $array["message"]="Remove ACL Group $gpid from the rule $rulename";
        $array["status"] = true;
        $array["count"]=1;
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        die();
    }

    if($command=="white"){
        $ruleid=$value1;
        $gpid=$value2;

        $zmd5=md5("$ruleid$gpid");
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

        $ligne=$q->mysqli_fetch_array("SELECT ID,rulename FROM wpad_rules WHERE ID=$ruleid");
        if(intval($ligne["ID"])==0){
            $array["status"] = false;
            $array["message"] = "Rule $ruleid Doesn't exists";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $rulename=$ligne["rulename"];


        $q->QUERY_SQL("INSERT INTO wpad_white_link (zmd5,aclid,negation,gpid,zorder) VALUES ('$zmd5','$ruleid','0','$gpid',1)");
        if(!$q->ok){
            events($q->mysql_error,__LINE__);
            $array["status"] = false;
            $array["message"] = "$q->mysql_error";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $array["message"]="Add White ACL Group $gpid to the rule $rulename";
        $array["status"] = true;
        $array["count"]=1;
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        die();
    }

    if($command=="unwhite"){
        $ruleid=$value1;
        $gpid=$value2;
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

        $ligne=$q->mysqli_fetch_array("SELECT ID,rulename FROM wpad_rules WHERE ID=$ruleid");
        if(intval($ligne["ID"])==0){
            $array["status"] = false;
            $array["message"] = "Rule $ruleid Doesn't exists";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $rulename=$ligne["rulename"];

        $q->QUERY_SQL("DELETE FROM wpad_white_link WHERE aclid='$ruleid' AND gpid='$gpid'");
        if(!$q->ok){
            events($q->mysql_error,__LINE__);
            $array["status"] = false;
            $array["message"] = "$q->mysql_error";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $array["message"]="Remove white ACL Group $gpid from the rule $rulename";
        $array["status"] = true;
        $array["count"]=1;
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        die();
    }
    if($command=="proxy"){
        $ruleid=$value1;
        $proxyName=$value2;

        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

        $ligne=$q->mysqli_fetch_array("SELECT ID,rulename FROM wpad_rules WHERE ID=$ruleid");
        if(intval($ligne["ID"])==0){
            $array["status"] = false;
            $array["message"] = "Rule $ruleid Doesn't exists";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $rulename=$ligne["rulename"];


        if(!$q->FIELD_EXISTS("wpad_destination","secure")){$q->QUERY_SQL("ALTER TABLE wpad_destination ADD `secure` INTEGER NOT NULL DEFAULT 0");}

        if(!preg_match("#(.+?):([0-9]+)$#",$proxyName,$re)){
            events("$proxyName!!!",__LINE__);
            $array["status"] = false;
            $array["message"] = "Bad pattern for proxy address";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $hostname=$re[1];
        $port=$re[2];
        $secure=0;
        $zmd5=md5("$ruleid$hostname$port");
        $q->QUERY_SQL("INSERT INTO wpad_destination (zmd5,aclid,proxyserver,proxyport,zorder,secure)
			VALUES ('$zmd5','$ruleid','$hostname','$port',0,$secure)");

        if(!$q->ok){
            events($q->mysql_error,__LINE__);
            $array["status"] = false;
            $array["message"] = "$q->mysql_error";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        $array["message"]="Adding proxy $hostname with port $port to the rule $rulename";
        $array["status"] = true;
        $array["count"]=1;
        $array["results"] = array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        die();
    }

    if($command=="unproxy") {
        $ruleid = $value1;
        $proxyName = $value2;
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        if (!preg_match("#(.+?):([0-9]+)$#", $proxyName, $re)) {
            events("$proxyName!!!", __LINE__);
            $array["status"] = false;
            $array["message"] = "Bad pattern for proxy address";
            $array["results"] = array();
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }

        $hostname = $re[1];
        $port = $re[2];
        $zmd5 = md5("$ruleid$hostname$port");
        $q->QUERY_SQL("DELETE FROM wpad_destination WHERE zmd5='$zmd5'");
        $array["message"] = "removing proxy $hostname with port $port";
        $array["status"] = true;
        $array["count"] = 1;
        $array["results"] = array();
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        die();
    }

    if($command=="proxyset") {
        $ruleid = intval($value1);
        $proxyName = $value2;
        $key=$value3;
        $value=$value4;

        if(($ruleid==0) OR ($proxyName==null) OR ($key==null) OR ($value==null)){
            events("Missing a value $ruleid/$proxyName/$key/$value !!!", __LINE__);
            $array["status"] = false;
            $array["message"] = "Missing a value $ruleid/$proxyName/$key/$value !!!";
            $array["results"] = array();
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }
        if (!preg_match("#(.+?):([0-9]+)$#", $proxyName, $re)) {
            events("$proxyName!!!", __LINE__);
            $array["status"] = false;
            $array["message"] = "Bad pattern for proxy address";
            $array["results"] = array();
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }

        $hostname=$re[1];
        $port=$re[2];
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        if(!$q->FIELD_EXISTS("wpad_destination","secure")){$q->QUERY_SQL("ALTER TABLE wpad_destination ADD `secure` INTEGER NOT NULL DEFAULT 0");}
        $q->QUERY_SQL("UPDATE wpad_destination SET `$key`='$value' WHERE proxyserver='$hostname' AND proxyport='$port' AND aclid=$ruleid");
        if(!$q->ok){
            events($q->mysql_error,__LINE__);
            $array["status"] = false;
            $array["message"] = "$q->mysql_error";
            $array["results"] = array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            die();
        }

        $array["message"] = "Modify proxy $hostname $key=$value in ACL $ruleid";
        $array["status"] = true;
        $array["count"] = 1;
        $array["results"] = array();
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        die();


    }

    $array["status"]=false;
    $array["message"]="Unable to understand query <{$command}> <{$value1}> <{$value2}>";
    $array["results"]=array();
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),404);

}

function PROXYPAC_RULES(){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT * FROM wpad_rules ORDER BY zorder");
    $RULE=array();
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];



        foreach ($ligne as $key=>$val){
            if(is_numeric($key)){continue;}
            $RULE[$ID]["parameters"][$key]=$val;
        }



        $sql="SELECT wpad_sources_link.gpid,wpad_sources_link.negation,wpad_sources_link.zmd5 as mkey,
	wpad_sources_link.zorder,
	webfilters_sqgroups.*
	FROM wpad_sources_link,webfilters_sqgroups
	WHERE wpad_sources_link.gpid=webfilters_sqgroups.ID
	AND wpad_sources_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_sources_link.zorder";
        $results2=$q->QUERY_SQL($sql);
        foreach ($results2 as $index=>$ligne){

            $RULE[$ID]["source_groups"][$ligne["gpid"]]=array("GroupName"=>$ligne["GroupName"],"negation"=>$ligne["negation"]);
        }

        $sql="SELECT * FROM `wpad_destination` WHERE aclid=$ID ORDER BY zorder";
        $results2=$q->QUERY_SQL($sql);
        foreach ($results2 as $index=>$ligne){

            $RULE[$ID]["proxies"]["{$ligne["proxyserver"]}:{$ligne["proxyport"]}"]=$ligne["zorder"];
            if($ligne["secure"]==1){
                $RULE[$ID]["secure_proxies"]["{$ligne["proxyserver"]}:{$ligne["proxyport"]}"]=1;
            }
        }




        $sql="SELECT wpad_white_link.gpid,wpad_white_link.negation,wpad_white_link.zmd5 as mkey,
        wpad_white_link.zorder,
        webfilters_sqgroups.*
        FROM wpad_white_link,webfilters_sqgroups
        WHERE wpad_white_link.gpid=webfilters_sqgroups.ID
        AND wpad_white_link.aclid=$ID
        AND webfilters_sqgroups.enabled=1
        ORDER BY wpad_white_link.zorder";

        $results2=$q->QUERY_SQL($sql);
        foreach ($results2 as $index=>$ligne){
            $RULE[$ID]["white_groups"][$ligne["gpid"]]=array("GroupName"=>$ligne["GroupName"],"negation"=>$ligne["negation"]);
        }

    }

    $array["status"]=true;
    $array["message"]="List of WPAD rules";
    $array["count"]=count($RULE);
    $array["results"]=$RULE;
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);


}


function DENY_CACHE_COMMANDS($command,$value1=null,$value2=null){
	
	if($command=="list"){DENY_CACHE_LIST();exit;}
	if($command=="add"){DENY_CACHE_ADD($value1,$value2);exit;}
	if($command=="del"){DENY_CACHE_DEL($value1);exit;}
	if($command=="apply"){DENY_CACHE_APPLY($value1);exit;}
	
	$array["status"]=false;
	$array["message"]="Unable to understand query <{$command}> <{$value1}> <{$value2}>";
	$array["results"]=array();
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),404);
}

function DENY_CACHE_APPLY(){
	$sock=new sockets();
	$sock->REST_API("/proxy/acls/denycache");
	$array["status"]=true;
	$array["message"]="Success order to proxy";
	$array["count"]=1;
	$array["results"]="OK";
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	exit;
	
}

function DENY_CACHE_DEL($item){
	$tpl=new template_admin();
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
	
	$array["status"]=true;
	$array["message"]="Success";
	$array["count"]=1;
	$array["results"]="$item";
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

function DENY_CACHE_LIST(){
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT * FROM deny_cache_domains ORDER BY items";
	$results=$q->QUERY_SQL($sql);
	$count=count($results);
	
	$array["status"]=true;
	$array["message"]="cache deny list";
	$array["count"]=$count;
	$array["results"]=$results;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	
}

function events($text,$line=0){
    if($line>0){$text="$text [$line]";}
    $LOG_SEV=LOG_INFO;
    if(function_exists("openlog")){openlog("REST_API", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
    if(function_exists("closelog")){closelog();}
}

