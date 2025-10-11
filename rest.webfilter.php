<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__).'/ressources/class.rest.inc');
include_once(dirname(__FILE__).'/ressources/class.dnsfilter.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.familysites.inc');
include_once(dirname(__FILE__).'/ressources/class.categories.inc');
include_once(dirname(__FILE__).'/ressources/class.ActiveDirectory.inc');
include_once(dirname(__FILE__).'/ressources/class.external.ad.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDRESTFulEnabled"))==0){$RestAPi=new RestAPi();$RestAPi->response("Disabled feature", 503);exit;}


$request_uri=$_SERVER["REQUEST_URI"];
if(strpos("$request_uri","?verbose=yes")){$request_uri=str_replace("?verbose=yes","",$request_uri);}
$request_uri=str_replace("/api/rest/webfilter/", "", $request_uri);
$f=explode("/",$request_uri);

foreach ($f as $index=>$params){
    $params=str_replace("?","",$params);
    $f[$index]=$params;
}
if($f[0]=="categories"){LIST_CATEGORIES();exit;}
if($f[0]=="rules"){LIST_RULES($f[1]);exit;}
if($f[0]=="save-rule"){SAVE_RULE();exit;}
if($f[0]=="delete"){DELETE_RULE($f[1]);exit;}
if($f[0]=="categories"){LIST_CATEGORIES();exit;}
if($f[0]=="service"){SERVICE($f[1],$f[2],$f[3]);exit;}
if($f[0]=="groups"){LIST_GROUPS($f[1]);exit;}
if($f[0]=="group"){CMD_GROUP($f[1],$f[2]);exit;}
if($f[0]=="new"){NEW_ITEM($f[1],$f[2],$f[3],$f[4]);exit;}
if($f[0]=="items"){LIST_ITEM($f[1],$f[2],$f[3],$f[4]);exit;}
if($f[0]=="events"){WBF_EVENTS($f[1]);}
if($f[0]=="time"){RULE_TIME($f[1],$f[2],$f[3]);}



RestSyslog("Unable to understand query <{$f[0]}> <{$f[1]}> in $request_uri");

$array["status"]=false;
$array["message"]="Unable to understand query <{$f[0]}> <{$f[1]}> in $request_uri";
$array["category"]=0;

$RestAPi=new RestAPi();
$RestAPi->response(json_encode($array),404);


function status(){
    $SquidUFDBUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUFDBUrgency"));

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/ufdb/used");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("ufdbguard.php?services-status=yes");

    $UfdbUsedDatabases=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbUsedDatabases"));
    if(!is_array($UfdbUsedDatabases)){$UfdbUsedDatabases=array();}
    if(!isset($UfdbUsedDatabases["MISSING"])){$UfdbUsedDatabases["MISSING"]=array();}
    $CountDeMissing=count($UfdbUsedDatabases["MISSING"]);
    $CountDeInstalled=count($UfdbUsedDatabases["INSTALLED"]);
    $UfdbDebugAll=intval(($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbDebugAll")));


    $SquidUFDBUrgencyLastEvents=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUFDBUrgencyLastEvents");
    $ini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/databases/ALL_UFDB_STATUS");


    if(!isUfdbLinked()){
        $array["INFO"]["LINKED_TO_PROXY"]=0;
    }else{
        $array["INFO"]["LINKED_TO_PROXY"]=1;

    }
    $array1["DEBUG_MODE"]=$UfdbDebugAll;
    $array1["EMERGENCY_MODE"]=$SquidUFDBUrgency;
    $array1["EMERGENCY_MODE_WHY"]=$SquidUFDBUrgencyLastEvents;
    $array1["MISSING_DATABASES"]=$CountDeMissing;
    $array1["MISSING_DATABASES_LIST"]=$UfdbUsedDatabases["MISSING"];
    $array1["INSTALLED_DATABASES"]=$CountDeInstalled;
    $array1["SERVICE_STATUS"]=$ini;
    $RestAPi=new RestAPi();

    $array["status"]=true;
    $array["message"]="Web-Filetring status";
    $array["INFO"]=$array1;


    $RestAPi->response(json_encode($array),200);
    exit;
}

function isUfdbLinked(){
return true;
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
    $RestAPi->response(json_encode($array), 407);
    exit;

}

function RULE_TIME($ruleid,$action=null,$value=null){
    if($value=="all"){$value=null;}
    if($action=="view"){$action=null;}


    if($action==null){
        if($ruleid==0) {
            $ligne = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianDefaultMainRule")));
        }else{
            $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
            $sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ruleid";
            $ligne=$q->mysqli_fetch_array($sql);
        }

        $TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
        if(!isset($TimeSpace["RuleMatchTime"])){$TimeSpace["RuleMatchTime"]="none";}
        if(!isset($TimeSpace["RuleAlternate"])){$TimeSpace["RuleAlternate"]="0";}
        if(!isset($TimeSpace["TIMES"])){$TimeSpace["TIMES"]=array();}
        $array["matches"]=$TimeSpace["RuleMatchTime"];
        $array["alternate"]=$TimeSpace["RuleAlternate"];
        $array["period"]=array();
        foreach ($TimeSpace["TIMES"] as $index=>$ztimes) {
            $ENDH=$ztimes["ENDH"];
            $ENDM=$ztimes["ENDM"];
            $BEGINH=$ztimes["BEGINH"];
            $BEGINM=$ztimes["BEGINM"];
            $ttday=array();
            foreach ($ztimes["DAYS"] as $zday=>$none){
                $ttday[]=$zday;
            }

            $array["period"][$index] =@implode(",",$ttday).":$BEGINH:$BEGINM-$ENDH:$ENDM";

        }

        $array["status"] = true;
        $array["message"] = "Web-filtering time configuration for rule id $ruleid";
        $array["time"]=$array;
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        exit;
    }

    $Config=array();
    if($action=="period") {

        if (!preg_match("#remove:#", $value)) {
            if (!preg_match("#([a-z,]+):([0-9:]+)-([0-9:]+)$#i", $value, $re)) {
                $array["status"] = false;
                $array["message"] = "Wrong formatted time.";
                $RestAPi = new RestAPi();
                $RestAPi->response(json_encode($array), 500);
                exit;
            }
            $ddays = explode(",", $re[1]);
            foreach ($ddays as $zday) {
                $Config["DAYS"][trim(strtolower($zday))] = 1;
            }

            $TM1E = explode(":", $re[2]);
            $BEGINH = $TM1E[0];
            $BEGINM = $TM1E[1];
            $TM2E = explode(":", $re[3]);
            $ENDH = $TM2E[0];
            $ENDM = $TM2E[1];

            $Config["ENDH"] = $ENDH;
            $Config["ENDM"] = $ENDM;
            $Config["BEGINH"] = $BEGINH;
            $Config["BEGINM"] = $BEGINM;


        }
    }

    if($ruleid==0){

        $ligne=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DansGuardianDefaultMainRule")));
        $TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));

        if($action=="matches") {
            $fadd="RuleMatchTime";
            $TimeSpace["RuleMatchTime"] = $value;
        }

        if($action=="alternate") {
            $fadd="RuleAlternate";
            $TimeSpace["RuleAlternate"] = intval($value);
        }

        if($action=="period"){
            if(preg_match("#remove:([0-9]+)#",$value,$re)){
                $fadd="REMOVE:TIMES";
                unset($TimeSpace["TIMES"][$re[1]]);
            }else {
                $fadd="TIMES";
                $TimeSpace["TIMES"][] = $Config;
            }
        }



        $TimeSpaceNew=base64_encode(serialize($TimeSpace));
        $ligne["TimeSpace"]=$TimeSpaceNew;
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($ligne)), "DansGuardianDefaultMainRule");
        $array["status"] = true;
        $array["message"] = "Web-filtering time configuration ($fadd) for default rule defined";
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        exit;

    }


    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $sql="SELECT TimeSpace FROM webfilter_rules WHERE ID=$ruleid";
    $ligne=$q->mysqli_fetch_array($sql);
    $TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
    if($action=="matches") {
        $fadd="RuleMatchTime";
        $TimeSpace["RuleMatchTime"] = $value;
    }
    if($action=="alternate") {
        $fadd="RuleAlternate";
        $TimeSpace["RuleAlternate"] = intval($value);
    }

    if($action=="period"){
        if(preg_match("#remove:([0-9]+)#",$value,$re)){
            $fadd="REMOVE:TIMES";
            unset($TimeSpace["TIMES"][$re[1]]);
        }else {
            $fadd="TIMES";
            $TimeSpace["TIMES"][] = $Config;
        }
    }

    $TimeSpaceNew=base64_encode(serialize($TimeSpace));
    $sql="UPDATE webfilter_rules SET TimeSpace='$TimeSpaceNew' WHERE ID=$ruleid";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        $array["status"] = false;
        $array["message"] = "SQL Error $q->mysql_error";
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 500);
        exit;
    }

    $array["status"] = true;
    $array["message"] = "Web-filtering time configuration ($fadd) for rule $ruleid defined";
    $RestAPi = new RestAPi();
    $RestAPi->response(json_encode($array), 200);

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

function WBF_EVENTS($query){

    $tpl=new template_admin();
    $GLOBALS["TPLZ"]=$tpl;
    $MAIN=$tpl->format_search_protocol($query);
    $sock=new sockets();
    $sock->getFrameWork("squid.php?ufdb-real=yes&rp={$MAIN["MAX"]}&query=".urlencode($MAIN["TERM"]));

    $filename="/usr/share/artica-postfix/ressources/logs/ufdb.log.tmp";
    $data=explode("\n",@file_get_contents($filename));

    krsort($data);


$c=0;
    foreach ($data as $line) {
        $TR = preg_split("/[\s]+/", $line);

        if (count($TR) < 5) {
            continue;
        }


        $date = $TR[0];
        $TIME = $TR[1];
        $PID = $TR[2];
        $ALLOW = $TR[3];
        $CLIENT = $TR[4];
        $CLIENT_IP = $TR[5];
        $RULE = $TR[6];
        $CATEGORY = categoryCodeTocatz($TR[7]);
        $URI = $TR[8];


        $parse = parse_url($URI);
        $hostname = $parse["host"];
        if (!isset($parse["host"])) {
            continue;
        }
        if ($CLIENT == null) {
            $CLIENT = "-";
        }
        $c++;

        $array[$c]["DATE"]=$date;
        $array[$c]["TIME"]=$TIME;
        $array[$c]["PID"]=$PID;
        $array[$c]["METHOD"]=$ALLOW;
        $array[$c]["CLIENT"]=$CLIENT;
        $array[$c]["CLIENT_IP"]=$CLIENT_IP;
        $array[$c]["RULE"]=$RULE;
        $array[$c]["CATEGORY"]=$CATEGORY;
        $array[$c]["REMOTE_HOST"]=$hostname;
    }



    $array["status"] = true;
    $array["message"] = "Search '$query' in Web-filtering events";
    $array["TOTAL"]=$c;
    $array["events"]=$array;
    $RestAPi = new RestAPi();
    $RestAPi->response(json_encode($array), 500);
    exit;

}

function categoryCodeTocatz($category){
    if(preg_match("#P([0-9]+)#", $category,$re)){$category=$re[1];}
    if($category==0){return "($category) Unknown(0)";}

    $catz=new mysql_catz(true);
    $categories_descriptions=$catz->categories_descriptions();
    if(!isset($categories_descriptions[$category]["categoryname"])){
        return "($category) <strong>Unkown</strong>";
    }

    $name=$categories_descriptions[$category]["categoryname"];
    $category_description=$categories_descriptions[$category]["category_description"];
    $js="Loadjs('fw.ufdb.categories.php?category-js=$category')";
    return $GLOBALS["TPLZ"]->td_href($name,$category_description,$js);
}

function NEW_ITEM($type,$item_type,$name,$ruleid=0){

    if($type=="group"){
        NEW_ITEM_GROUP($item_type,$name,$ruleid);
        return null;
    }

    $array["status"] = false;
    $array["message"] = "Unable to understand type=$type";
    $RestAPi = new RestAPi();
    $RestAPi->response(json_encode($array), 500);
    exit;

}

function LIST_ITEM($command,$groupid=0,$command3=null){
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    if($command=="list"){
        if($groupid>0) {
            $sqladd=" WHERE groupid=$groupid";
            $ligne = $q->mysqli_fetch_array("SELECT * FROM webfilter_group WHERE ID=$groupid");
            $ID = intval($ligne["ID"]);
            if ($ID == 0) {
                $array["status"] = false;
                $array["message"] = "Group id : $groupid doesn`t exists ({$ligne["ID"]}";
                $RestAPi = new RestAPi();
                $RestAPi->response(json_encode($array), 500);
                exit;

            }

            $localldap = intval($ligne["localldap"]);

            if ($localldap <> 1) {
                $array["status"] = false;
                $array["message"] = "Group id : $groupid operation not supported for this group";
                $RestAPi = new RestAPi();
                $RestAPi->response(json_encode($array), 500);
                exit;

            }

        }
//(groupid,enabled,pattern)
        $results=$q->QUERY_SQL("SELECT * FROM webfilter_members $sqladd ORDER BY pattern");
        if(!$q->ok){
            $array["status"] = false;
            $array["message"] = "SQL Error $q->mysql_error";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }


        foreach ($results as $index=>$ligne){
            $MEMBERS[$ligne["ID"]]=$ligne["pattern"];
        }


        $array["status"] = true;
        $array["message"] = count($MEMBERS)." items";
        $array["TOTAL"] =count($MEMBERS);
        $array["items"]=$MEMBERS;
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        exit;

    }


    if($command=="remove"){
        $groupid=intval($groupid);
        $q->QUERY_SQL("DELETE FROM webfilter_members WHERE ID=$groupid");
        if(!$q->ok){
            $array["status"] = false;
            $array["message"] = "SQL Error $q->mysql_error";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }

        $array["status"] = true;
        $array["message"] = "Remove item id $groupid";
        $array["TOTAL"] =0;
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        exit;
    }

}

function NEW_ITEM_VIRTUAL_GROUP($pattern,$groupid){
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");

    if(preg_match("#^([0-9\.]+)Netmask([0-9]+)#i",$pattern,$re)){
        $pattern=$re[1]."/".$re[2];
    }

    $ligne=$q->mysqli_fetch_array("SELECT * FROM webfilter_group WHERE ID=$groupid");

    if($GLOBALS["VERBOSE"]){
        print_r($ligne);
    }

    if(!$q->ok){
        $array["status"] = false;
        $array["message"] = "SQL Error $q->mysql_error (L.".__LINE__.")";
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 500);
        exit;
    }
    $ID=intval($ligne["ID"]);
    if($ID==0){
        $array["status"] = false;
        $array["message"] = "Group id : $groupid doesn`t exists ({$ligne["ID"]}";
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 500);
        exit;

    }

    $localldap=intval($ligne["localldap"]);

    if($localldap<>1){
        $array["status"] = false;
        $array["message"] = "Group id : $groupid operation not supported for this group";
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 500);
        exit;

    }

    $q->QUERY_SQL("INSERT INTO webfilter_members (groupid,enabled,pattern) VALUES ('$groupid',1,'$pattern')");
    if(!$q->ok){
        $array["status"] = false;
        $array["message"] = "SQL Error $q->mysql_error";
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 500);
        exit;
    }


    $array["status"] = true;
    $array["message"] = "Success creating new item $pattern in virtual group id $groupid";
    $RestAPi = new RestAPi();
    $RestAPi->response(json_encode($array), 200);
    exit;



}

function NEW_ITEM_GROUP($item_type,$name,$ruleid=0){

    if($item_type=="item"){
        return NEW_ITEM_VIRTUAL_GROUP($name,$ruleid);
    }


    $localldap["ldap"] = "LDAP Group";
    $localldap["virtual"] = "Virtual Group";
    $localldap["ad"] = "Active Directory Group";
    $localldap[3] = "Remote LDAP Group";
    $localldap[4] = "Active Directory Group (other)";
    $localldap["ou"] = "Active Directory Organization";

    if(!isset($localldap[$item_type])){
        $array["status"] = false;
        $array["message"] = "Group type $item_type not supported";
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 500);
        exit;

    }


    if(intval($ruleid)==0){
        $array["status"] = false;
        $array["message"] = "Need rule id, you have set a non numeric or 0 value";
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 500);
        exit;
    }

    if($item_type=="virtual"){
        $sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`) 
		VALUES ('','$name',1,1,'{virtual_group} From REST API','')";
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $q->QUERY_SQL($sql_add);
        if(!$q->ok){
            $array["status"] = false;
            $array["message"] = "SQL Error $q->mysql_error";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }
        $groupid=$q->last_id;
        if(intval($groupid)==0){
            $array["status"] = false;
            $array["message"] = "SQL Error ID == 0 AFTER INSERT";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }
        $md5=md5("$ruleid$groupid");
        $sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$ruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            $array["status"] = false;
            $array["message"] = "SQL Error $q->mysql_error";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }
        $array["status"] = true;
        $array["message"] = "Success creating new Virtual group $name and link it to $ruleid rule";
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        exit;


    }



    if($item_type=="ldap"){
        $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
        if($EnableOpenLDAP==0){
            $array["status"] = false;
            $array["message"] = "OpenLDAP database is disabled";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;

        }
        $gp=new groups(null,$name);
        if(intval($gp->group_id)==0){
            $array["status"] = false;
            $array["message"] = "Unable to find group $name";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }

        $groupname=$gp->group_id;
        $sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`,gpid) 
		VALUES ('','$groupname',1,0,'{ldap_group}','Group $name from REST API',$gp->group_id)";
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $q->QUERY_SQL($sql_add);
        if(!$q->ok){
            $array["status"] = false;
            $array["message"] = "SQL Error $q->mysql_error";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }
        $groupid=$q->last_id;
        if($groupid==0){echo "Unable to find the Next ID!!\n";return;}
        $md5=md5("$ruleid$groupid");
        $sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$ruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            $array["status"] = false;
            $array["message"] = "SQL Error $q->mysql_error";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }
        $array["status"] = true;
        $array["message"] = "Success creating new LDAP group $name and link it to $ruleid rule";
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        exit;


    }

    if($item_type=="ad"){
        $ad=new external_ad_search();
        $Array=$ad->flexRTGroups($name,2);

        if(count($Array)==0){
            $array["status"] = false;
            $array["message"] = "$name not found";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }

        foreach ($Array as $sdn=>$sa){
            $dn=$sdn;
            break;
        }

        $ad=new external_ad_search();

        $ARRAY=$ad->FindParametersByDN($dn);
        if($ARRAY["samaccountname"]==null){
            $array["status"] = false;
            $array["message"] = "$name: samaccountname not found";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }
        $settings=base64_encode(serialize($ARRAY));
        $groupname=$ARRAY["samaccountname"];
        $description=$ARRAY["description"];
        $ldap_server=$ARRAY["LDAP_SERVER"].":".$ARRAY["LDAP_PORT"];

        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $dn=$q->sqlite_escape_string2($dn);
        $description=$q->sqlite_escape_string2($description);
        $sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`) VALUES ('$settings','$groupname',1,2,'$description','$dn')";

        $q->QUERY_SQL("DELETE FROM webfilter_group WHERE dn='$dn'");
        $q->QUERY_SQL($sql_add);

        if(!$q->ok){
            $array["status"] = false;
            $array["message"] = "SQL Error $q->mysql_error (L.".__LINE__.")";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
            }
        $groupid=$q->last_id;

        if($groupid==0){
            $array["status"] = false;
            $array["message"] = "Unable to find the Next ID! (L.".__LINE__.")";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }

        $md5=md5("$ruleid$groupid");
        $sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$ruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            $array["status"] = false;
            $array["message"] = "SQL Error $q->mysql_error (L.".__LINE__.")";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }
        $array["status"] = true;
        $array["message"] = "Success creating new AD group with dn $dn and link it to $ruleid rule";
        $array["GroupDN"] =$dn;
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        exit;



    }
    if($item_type=="ou"){
        $ouName=$name;
        $ad=new external_ad_search();
        $Ous=$ad->active_directory_ListOus($ouName);
        if(!$Ous){
            $array["status"] = false;
            $array["message"] = "Unable to find organization $ouName";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }
        $dn=null;
        foreach ($Ous as $sdn=>$sa){
            $dn=$sdn;
            break;
        }

        $sql_add="INSERT INTO webfilter_group (`settings`,`groupname`,`enabled`,`localldap`,`description`,`dn`) VALUES ('$ouName','$ouName',1,5,'Organization Unit $ouName','')";
        $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $q->QUERY_SQL("DELETE FROM webfilter_group WHERE groupname='$ouName'");
        $q->QUERY_SQL($sql_add);
        if(!$q->ok){
            $array["status"] = false;
            $array["message"] = "SQL Error $q->mysql_error (L.".__LINE__.")";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }

        $groupid=$q->last_id;
        if($groupid==0){
            $array["status"] = false;
            $array["message"] = "Unable to find the Next ID (L.".__LINE__.")";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }

        $md5=md5("$ruleid$groupid");
        $sql="INSERT INTO `webfilter_assoc_groups` (zMD5,webfilter_id,group_id) VALUES('$md5','$ruleid','$groupid')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            $array["status"] = false;
            $array["message"] = "SQL Error $q->mysql_error (L.".__LINE__.")";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }
        $array["status"] = true;
        $array["message"] = "Success creating new Active Directory OU with dn $dn and link it to $ruleid rule";
        $array["GroupDN"] =$dn;
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        exit;



    }

    $array["status"] = false;
    $array["message"] = "($item_type) Operation not supported ";
    $RestAPi = new RestAPi();
    $RestAPi->response(json_encode($array), 500);
    exit;





}


function SERVICE($command=null,$second_command=null,$troisiemme=null){


    if($command=="status"){return status();}

    if($command=="emergency"){
        if($second_command=="enable"){
            $sock=new sockets();
            $sock->getFrameWork("ufdbguard.php?enable-urgency=yes");
            $array["status"] = true;
            $array["message"] = "Turn to ON the Web-filter emergency";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 200);
            exit;
        }
        if($second_command=="disable"){
            $sock=new sockets();
            $sock->REST_API("/ufdbclient/emergency/off");
            $array["status"] = true;
            $array["message"] = "Turn to OFF the Web-filter emergency";
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 200);
            exit;
        }
        RestSyslog("Unable to understand query <$second_command>");
        $array["status"]=false;
        $array["message"]="Unable to understand query <$command>";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),404);
        exit;

    }

    if($command==null){
        RestSyslog("Service command not found");
        $array["status"] = false;
        $array["message"] = "No command set";
        $array["category"] = 0;
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 500);
        exit;

    }

    if($command=="restart"){
        $RestAPi = new RestAPi();
        $sock=new sockets();
        $sock->getFrameWork("dnsfilterd.php?restart=yes");
        $array["status"]=true;
        $array["message"]="Success launch in background mode the restart operation.";
        $RestAPi->response(json_encode($array),200);
        exit;
    }
    if($command=="events"){
        $rp=intval($second_command);
        $query=urlencode($troisiemme);
        $RestAPi = new RestAPi();
        $sock=new sockets();
        $sock->getFrameWork("dnsfilterd.php?ufdb-real=yes&rp=$rp&$query");
        $array["status"]=true;
        $array["message"]="Search $rp lines with $query in events";
        $f=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/dnsfilterd.log.tmp"));
        $array["TOTAL"]=count($f);
        $array["EVENTS"]=$f;
        $RestAPi->response(json_encode($array),200);
        exit;
    }

    if($command=="status") {
        $sock=new sockets();
        $sock->getFrameWork("dnsfilterd.php?status=yes");
        $ini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/dnsfilterd.status");
        $RestAPi = new RestAPi();
        $array["status"]=true;
        $array["message"]="";
        $array["SERVICE_STATUS"]=$ini->_params;
        $RestAPi->response(json_encode($array),200);
        exit;
    }




    RestSyslog("Unable to understand query <$command>");
    $array["status"]=false;
    $array["message"]="Unable to understand query <$command>";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),404);

}




function DELETE_RULE($ID){

    $ID=intval($ID);
    if($ID==0){
        RestSyslog("Cannot delete default rule...");
        $array["status"]=false;
        $array["message"]="Cannot delete default rule";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id='$ID'");
    $q->QUERY_SQL("DELETE FROM webfilter_rules WHERE ID='$ID'");
    $q->QUERY_SQL("DELETE FROM webfilter_members WHERE ruleid='$ID'");


    $array["status"]=True;
    $array["message"]="Rule $ID was deleted";
    $array["rules"]=array();
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    exit;

}

function SAVE_RULE(){
    $DEFAULTARRAY=array();
    $DNS_PARAMS=array();

    if(!isset($_POST["ID"])){
        RestSyslog("'ID' Field not set");
        $array["status"]=false;
        $array["message"]="'ID' Field not set";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }

    $ID=intval($_POST["ID"]);

    if($GLOBALS["VERBOSE"]){echo "ID = = $ID\n";}

    if($ID==0){
        $sock=new sockets();
        $DEFAULTARRAY=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));


    }


    if($ID==-1){
        if(!isset($_POST["rulename"])){
            RestSyslog("Requested rulename to create a new rule");
            $array["status"]=false;
            $array["message"]="Requested rulename to create a new rule";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),500);
            exit;
        }

    }




    if(isset($_POST["rulename"])){
        $_POST["rulename"]=strtolower(replace_accents($_POST["rulename"]));
        $_POST["rulename"]=str_replace("$", "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace("(", "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace(")", "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace("[", "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace("]", "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace("%", "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace("!", "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace(":", "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace(";", "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace(",", "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace("£", "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace("~", "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace("`", "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace('\\', "_", $_POST["rulename"]);
        $_POST["rulename"]=str_replace('/', "_", $_POST["rulename"]);
        $_POST["rulename"]=str_replace('+', "_", $_POST["rulename"]);
        $_POST["rulename"]=str_replace('=', "_", $_POST["rulename"]);
        $_POST["rulename"]=str_replace('*', "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace('&', "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace('"', "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace('{', "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace('}', "", $_POST["rulename"]);
        $_POST["rulename"]=str_replace('|', "", $_POST["rulename"]);
        $Fields_add[]="groupname";
        $values_add[]="'{$_POST["rulename"]}'";
        $Fields_edit[]="groupname='{$_POST["rulename"]}'";
    }
    if(isset($_POST["order"])){
        $Fields_add[]="zOrder";
        $values_add[]="'{$_POST["order"]}'";
        $Fields_edit[]="zOrder='{$_POST["order"]}'";
    }
    if(isset($_POST["AllSystems"])){
        $Fields_add[]="AllSystems";
        $values_add[]="'{$_POST["AllSystems"]}'";
        $DEFAULTARRAY["AllSystems"]=$_POST["AllSystems"];
        $Fields_edit[]="AllSystems='{$_POST["AllSystems"]}'";
    }
    if(isset($_POST["enabled"])){
        $Fields_add[]="enabled";

        $values_add[]="'{$_POST["enabled"]}'";
        $Fields_edit[]="enabled='{$_POST["enabled"]}'";
    }
    if(isset($_POST["endofrule"])){
        $Fields_add[]="endofrule";
        $DEFAULTARRAY["endofrule"]=$_POST["endofrule"];
        $values_add[]="'{$_POST["endofrule"]}'";
        $Fields_edit[]="endofrule='{$_POST["endofrule"]}'";
    }
    if(isset($_POST["mode"])){
        $Fields_add[]="groupmode";
        $DEFAULTARRAY["groupmode"]=$_POST["mode"];
        $values_add[]="'{$_POST["mode"]}'";
        $Fields_edit[]="groupmode='{$_POST["mode"]}'";
    }


    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");

    if(!$q->FIELD_EXISTS("webfilter_rules","zmd5")){
        $q->QUERY_SQL("ALTER TABLE webfilter_rules ADD zmd5 TEXT");
    }


    if($ID==-1) {
        $zmd5 = md5(serialize($_POST) . time());
        $Fields_add[] = "zmd5";
        $values_add[] = "'$zmd5'";
        RestSyslog("Create a new rule {$_POST["rulename"]}");
        $sql = "INSERT OR IGNORE INTO webfilter_rules (" . @implode(",", $Fields_add) . ") VALUES (" . @implode(",", $values_add) . ")";

        $q->QUERY_SQL($sql);
        if(!$q->ok){
            $array["status"]=false;
            $array["message"]=$q->mysql_error;
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),500);
            exit;
        }


        $ligne = $q->mysqli_fetch_array("SELECT ID FROM webfilter_rules WHERE zmd5='$zmd5'");
        if(!$q->ok){
            $array["status"]=false;
            $array["message"]="Unable to find the ID";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),500);
            exit;
        }
        $ID = intval($ligne["ID"]);

        if($ID==0){
            $array["status"]=false;
            $array["message"]="Unable to find the ID";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),500);
            exit;
        }

        RestSyslog("Create new rule {$_POST["rulename"]} as ID $ID");

    }

    if($ID==0){
        $sock=new sockets();
        $sock->GET_INFO("DansGuardianDefaultMainRule",serialize($DEFAULTARRAY));

    }
    if($ID>0){
        $ligne = $q->mysqli_fetch_array("SELECT ID FROM webfilter_rules WHERE ID='$ID'");
        if(intval($ligne["ID"])==0){
            $array["status"]=false;
            $array["message"]="Rule ID $ID doesn't exists.";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),500);
            die();
        }
    }

    if(isset($_POST["blacklists"])){
        if($GLOBALS["VERBOSE"]){echo "blacklists: {$_POST["blacklists"]}\n";}
        $zcategories=explode(",",$_POST["blacklists"]);

        $q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id=$ID AND modeblk=0");
        foreach ($zcategories as $category_id){
            $q->QUERY_SQL("INSERT INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('$ID','$category_id',0)");
            if(!$q->ok){$array["status"]=false;$array["message"]=$q->mysql_error;$RestAPi=new RestAPi();$RestAPi->response(json_encode($array),500);exit;}
            $Fields_add[]="Added: blacklist Category id $category_id for rule id $ID";
        }



    }


    if(isset($_POST["whitelists"])){
            RestSyslog("whitelists: {$_POST["whitelists"]}");
            $zcategories=explode(",",$_POST["whitelists"]);
            $q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id=$ID AND modeblk=1");
            foreach ($zcategories as $category_id){
                $q->QUERY_SQL("INSERT INTO webfilter_blks (webfilter_id,category,modeblk) 
                    VALUES ('$ID','$category_id',1)");
                if(!$q->ok){
                    $array["status"]=false;
                    $array["message"]=$q->mysql_error;
                    $RestAPi=new RestAPi();
                    $RestAPi->response(json_encode($array),500);
                    exit;}
            }
            $Fields_add[]="Added: Whitelist Category id $category_id for rule id $ID";

        }



    foreach ($_POST as $key=>$value){
        $tt[]=$key;
    }

    $array["status"]=True;
    $array["message"]=@implode(",",$Fields_add)." Fields added (".@implode(",",$tt).") Fields posted";
    $array["rules"]=array();
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    exit;
}
function CMD_GROUP($groupid,$command){
    if(intval($groupid)==0){
        $array["status"]=false;
        $array["message"]="Wrong group id";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        die();
    }

    $q = new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    if($command=="enable"){

        $q->QUERY_SQL("UPDATE webfilter_group SET enabled=1 WHERE ID=$groupid");
        if (!$q->ok) {
            $array["status"] = False;
            $array["message"] = "MySQL Error $q->mysql_error";
            $array["rules"] = array();
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }


        $array["status"]=true;
        $array["message"]="Enable group ID:$groupid";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        die();

    }
    if($command=="disable"){

        $q->QUERY_SQL("UPDATE webfilter_group SET enabled=0 WHERE ID=$groupid");
        if (!$q->ok) {
            $array["status"] = False;
            $array["message"] = "MySQL Error $q->mysql_error";
            $array["rules"] = array();
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }


        $array["status"]=true;
        $array["message"]="Disable group ID:$groupid";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        die();

    }
    if($command=="remove"){


        $q->QUERY_SQL("DELETE FROM webfilter_assoc_groups WHERE group_id=$groupid");
        if (!$q->ok) {
            $array["status"] = False;
            $array["message"] = "MySQL Error $q->mysql_error";
            $array["rules"] = array();
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }

        $q->QUERY_SQL("DELETE FROM webfilter_members WHERE groupid=$groupid");
        if (!$q->ok) {
            $array["status"] = False;
            $array["message"] = "MySQL Error $q->mysql_error";
            $array["rules"] = array();
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }


        $q->QUERY_SQL("DELETE FROM webfilter_group WHERE ID=$groupid");
        if (!$q->ok) {
            $array["status"] = False;
            $array["message"] = "MySQL Error $q->mysql_error";
            $array["rules"] = array();
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }

        $array["status"]=true;
        $array["message"]="Remove group ID:$groupid";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        die();

    }
}

function LIST_GROUPS($second_command=null){

    $localldap[0] = "LDAP Group";
    $localldap[1] = "Virtual Group";
    $localldap[2] = "Active Directory Group";
    $localldap[3] = "Remote LDAP Group";
    $localldap[4] = "Active Directory Group (other)";
    $localldap[5] = "Active Directory Organization";


    if(intval($second_command)==0){
        $q = new lib_sqlite("/home/artica/SQLITE/webfilter.db");
        $results = $q->QUERY_SQL("SELECT * FROM webfilter_group");
        if (!$q->ok) {
            $array["status"] = False;
            $array["message"] = "MySQL Error $q->mysql_error";
            $array["rules"] = array();
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 500);
            exit;
        }


        foreach ($results as $index => $ligne) {
            $ID=$ligne["ID"];
            $groupname=$ligne["groupname"];
            $MEMBERS_ARRAY=array();

            if ($ligne["localldap"] == 0) {
                $gp = new groups($ligne["gpid"]);
                $groupname = $gp->groupName;
                $description = $gp->description;
                $CountDeMembers = count($gp->members);
                foreach ($gp->members as $user) {
                    $MEMBERS_ARRAY[$user] = 1;
                }

            }
            if ($ligne["localldap"] == 1) {
                $sql = "SELECT * FROM webfilter_members WHERE groupid=$ID";
                $results2 = $q->QUERY_SQL($sql);
                foreach ($results2 as $index2 => $ligne2) {
                    $MEMBERS_ARRAY[$ligne2["ID"]] = array("member" => $ligne2["pattern"], "active" => $ligne2["enabled"]);
                }
                $CountDeMembers = count($MEMBERS_ARRAY);
            }

            if ($ligne["localldap"] == 2) {
                if (preg_match("#AD:(.*?):(.+)#", $ligne["dn"], $re)) {
                    $dnEnc = $re[2];
                    $LDAPID = $re[1];
                    $ad = new ActiveDirectory($LDAPID);
                    if (preg_match("#^CN=(.+?),.*#i", base64_decode($dnEnc), $re)) {
                        $groupname = $re[1];
                        $CountDeMembers = '-';
                    } else {
                        $tty = $ad->ObjectProperty(base64_decode($dnEnc));
                        $CountDeMembers = $tty["MEMBERS"];
                    }

                    $description = htmlentities($tty["description"]);
                    $description = str_replace("'", "`", $description);

                } else {
                    $settings = unserialize(base64_decode($ligne["settings"]));
                    $groupname = $ligne["groupname"];
                    $description = $ligne["description"];
                    $ad = new ActiveDirectory(0, $settings);
                    $tty = $ad->ObjectProperty($ligne["dn"]);
                    $CountDeMembers = $tty["MEMBERS"];
                }

            }


            $MAIN[$ID]["groupname"] = $groupname;
            $MAIN[$ID]["enabled"] = $ligne["enabled"];
            $MAIN[$ID]["type"] = $localldap[$ligne["localldap"]];
            $MAIN[$ID]["members_count"] = $CountDeMembers;
            $MAIN[$ID]["members"] = $MEMBERS_ARRAY;
            $MAIN[$ID]["description"] = $description;
        }
        $array["status"] = True;
        $array["message"] = count($MAIN). " Web-filter source Groups";
        $array["count"] = count($MAIN);
        $array["groups"] = $MAIN;
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 200);
        exit;


    }

    $ID = intval($second_command);
    $MAINID=$ID;
    $sql = "SELECT webfilter_assoc_groups.ID,webfilter_assoc_groups.zMD5,webfilter_assoc_groups.webfilter_id, 
	webfilter_group.groupname,webfilter_group.description,webfilter_group.gpid,
	webfilter_group.localldap,webfilter_group.ID as webfilter_group_ID, webfilter_group.dn as webfilter_group_dn, webfilter_group.settings as settings, 
	webfilter_group.enabled
	FROM webfilter_group,webfilter_assoc_groups WHERE webfilter_assoc_groups.webfilter_id={$ID} AND webfilter_assoc_groups.group_id=webfilter_group.ID ORDER BY webfilter_group.groupname";

    $q = new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $results = $q->QUERY_SQL($sql);
    if (!$q->ok) {
        $array["status"] = False;
        $array["message"] = "MySQL Error $q->mysql_error";
        $array["rules"] = array();
        $RestAPi = new RestAPi();
        $RestAPi->response(json_encode($array), 500);
        exit;
    }



    $sql = "SELECT * FROM webfilter_rules WHERE ID=$ID";
    $ligne = $q->mysqli_fetch_array($sql);
    $rulename = $ligne["groupname"];


    foreach ($results as $index => $ligne) {
        $textExplainGroup = null;
        $KEY_ID_GROUP = $ligne["webfilter_group_ID"];
        $CountDeMembers = "-";
        $Textdynamic = null;
        $md = $ligne["zMD5"];
        $webfilter_assoc_groups_ID = $ligne["ID"];
        $enabled = $ligne["enabled"];
        $groupname = $ligne["groupname"];
        $MEMBERS_ARRAY = array();

        if ($ligne["localldap"] == 0) {
            $gp = new groups($ligne["gpid"]);
            $groupname = $gp->groupName;
            $description = $gp->description;
            $CountDeMembers = count($gp->members);
        }

        if ($ligne["localldap"] == 1) {
            $sql = "SELECT * FROM webfilter_members WHERE groupid=$KEY_ID_GROUP";
            $results2 = $q->QUERY_SQL($sql);
            foreach ($results2 as $index2 => $ligne2) {
                $MEMBERS_ARRAY[$ligne2["ID"]] = array("member" => $ligne2["pattern"], "active" => $ligne2["enabled"]);
            }
            $CountDeMembers = count($MEMBERS_ARRAY);
        }

        if ($ligne["localldap"] == 2) {
            if (preg_match("#AD:(.*?):(.+)#", $ligne["webfilter_group_dn"], $re)) {
                $dnEnc = $re[2];
                $LDAPID = $re[1];
                $ad = new ActiveDirectory($LDAPID);
                if (preg_match("#^CN=(.+?),.*#i", base64_decode($dnEnc), $re)) {
                    $groupname = $re[1];
                    $CountDeMembers = '-';
                } else {
                    $tty = $ad->ObjectProperty(base64_decode($dnEnc));
                    $CountDeMembers = $tty["MEMBERS"];
                }

                $description = htmlentities($tty["description"]);
                $description = str_replace("'", "`", $description);

            } else {
                $settings = unserialize(base64_decode($ligne["settings"]));
                $groupname = $ligne["groupname"];
                $description = $ligne["description"];
                $ad = new ActiveDirectory(0, $settings);
                $tty = $ad->ObjectProperty($ligne["webfilter_group_dn"]);
                $CountDeMembers = $tty["MEMBERS"];
            }

        }

        $MAIN[$KEY_ID_GROUP]["assoc_id"] = $webfilter_assoc_groups_ID;
        $MAIN[$KEY_ID_GROUP]["groupname"] = $groupname;
        $MAIN[$KEY_ID_GROUP]["enabled"] = $enabled;
        $MAIN[$KEY_ID_GROUP]["type"] = $localldap[$ligne["localldap"]];
        $MAIN[$KEY_ID_GROUP]["members_count"] = $CountDeMembers;
        $MAIN[$KEY_ID_GROUP]["members"] = $MEMBERS_ARRAY;
        $MAIN[$KEY_ID_GROUP]["description"] = $description;

    }

    $array["status"] = True;
    $array["message"] = "Groups associated to Web-filter rule $MAINID ($rulename)";
    $array["count"] = count($MAIN);
    $array["groups"] = $MAIN;
    $RestAPi = new RestAPi();
    $RestAPi->response(json_encode($array), 200);
    exit;
}


function LIST_RULES($second_command=null){

    if($second_command=="apply"){
        artica_mysql_events(1,"Order to compile web-filtering rules (REST API)",null,"webfilter-rest",__LINE__);
        $sock=new sockets();
        $sock->REST_API("/ufdb/compile");
        $array["status"]=true;
        $array["message"]="Rules are compiled in background mode";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;
    }


    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $webfilter=new webfilter_rules();

	

    $rules=array();
    $catz=new mysql_catz();
    $sock=new sockets();
    $ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
    $rulename="default";
    $CountDeBlack=intval($webfilter->COUNTDEGBLKS(0));
    $CountDewhite=intval($webfilter->COUNTDEGBWLS(0));
    $groupmode=$ligne["groupmode"];
    $zOrder=0;

    $rules[0]["rulename"]=$rulename;
    $rules[0]["order"]=$zOrder;
    $rules[0]["blacklists"]["COUNT"]=$CountDeBlack;
    $rules[0]["whitelists"]["COUNT"]=$CountDewhite;
    $rules[0]["src"]["COUNT"]=0;
    $rules[0]["src"]["CLIENTS"]=array();
    $rules[0]["mode"]=$groupmode;
    $rules[0]["AllSystems"]=$ligne["AllSystems"];
    $rules[0]["endofrule"]=$ligne["endofrule"];
    $rules[0]["enabled"]=intval($ligne["enabled"]);


    $zmodeblk[0]="blacklists";
    $zmodeblk[1]="whitelists";

    $sql="SELECT * FROM webfilter_blks WHERE `webfilter_id`=0";
    $results = $q->QUERY_SQL($sql);

    if(!$q->ok){
        $array["status"]=false;
        $array["message"]="SQL ERROR ".$q->mysql_error;
        $array["rules"]=array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),404);
        exit;
    }


    foreach ($results as $index=>$ligne){
        $category_id=$ligne["category"];
        $modeblk=$ligne["modeblk"];
        $rules[0][$zmodeblk[$modeblk]]["CATEGORIES"][$category_id]=$catz->CategoryIntToStr($category_id);
    }

    $sql="SELECT * FROM webfilter_rules ORDER BY zOrder";
    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){
        $array["status"]=false;
        $array["message"]="SQL ERROR ".$q->mysql_error;
        $array["rules"]=array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),404);
        exit;
    }

    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $rules[$ID]["ID"]=$ID;
        $groupmode=$ligne["groupmode"];
        $rulename=$ligne["groupname"];
        $CountDeBlack=intval($webfilter->COUNTDEGBLKS($ligne["ID"]));
        $CountDewhite=intval($webfilter->COUNTDEGBWLS($ligne["ID"]));
        $LigneCount=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM webfilter_members WHERE ruleid={$ligne['ID']}");
        $CountDeGroups=intval($LigneCount["tcount"]);
        $zOrder=$ligne["zOrder"];

        $rules[$ID]["rulename"]=$rulename;
        $rules[$ID]["order"]=$zOrder;
        $rules[$ID]["blacklists"]["COUNT"]=$CountDeBlack;
        $rules[$ID]["whitelists"]["COUNT"]=$CountDewhite;
        $rules[$ID]["src"]["COUNT"]=$CountDeGroups;
        $rules[$ID]["mode"]=$groupmode;
        $rules[$ID]["AllSystems"]=$ligne["AllSystems"];
        $rules[$ID]["endofrule"]=$ligne["endofrule"];
        $rules[$ID]["enabled"]=intval($ligne["enabled"]);

        $sql="SELECT * FROM webfilter_blks WHERE `webfilter_id`=$ID";
        $results2 = $q->QUERY_SQL($sql);
        foreach ($results2 as $index2=>$ligne2){
            $category_id=$ligne2["category"];
            $modeblk=intval($ligne2["modeblk"]);
            $rules[$ID][$zmodeblk[$modeblk]]["CATEGORIES"][$category_id]=$catz->CategoryIntToStr($category_id);
        }




	}

	
	$array["status"]=true;
	$array["message"]=count($rules)." rules";
    $array["TOTAL"]=count($rules);
	$array["rules"]=$rules;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	return;
}

function GET_CATEGORIES($sitename){
	
	$q=new mysql_catz();
	$category_id=$q->GET_CATEGORIES($sitename);
	if($category_id>9999){
		$array["status"]=false;
		$array["message"]="Not categorized";
		$array["category_id"]=0;
        $array["category_name"]="unknown";
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),404);
		exit;
	}
	
	if($category_id==0){
		$array["status"]=false;
		$array["message"]="Not categorized";
		$array["category_id"]=0;
        $array["category_name"]="unknown";
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),404);
		exit;		
	}

	$cname=$q->CategoryIntToStr($category_id);
	$array["status"]=true;
	$array["message"]="$sitename categorized as $cname ($category_id)";
	$array["category_id"]=$category_id;
    $array["category_name"]=$cname;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	exit;
	
	
}

function CREATE_CATEGORIES(){
	$ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    $CategoriesRESTFulAllowCreate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesRESTFulAllowCreate"));
	$name=$_POST["name"];
	$desc=$_POST["desc"];
	
	if($name==null){
		$array["status"]=false;
		$array["message"]="Category name missing";
		$array["category_id"]=0;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),500);
		exit;
	}

	if($CategoriesRESTFulAllowCreate==0){
        $array["status"]=false;
        $array["message"]="Cannot create $name permission denied.";
        $array["category_id"]=0;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }
	
	if($ManageOfficialsCategories==1){
		$array["status"]=false;
		$array["message"]="Cannot create $name Read-only mode, your are managing Public categories.";
		$array["category_id"]=0;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),500);
		exit;
		
	}
	$category=new categories();
	if(!$category->create_category($name, $desc, 0)){
		$array["status"]=false;
		$array["message"]=$category->mysql_error;
		$array["category_id"]=0;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),500);
		exit;
	}
	
	$array["status"]=true;
	$array["message"]="Success created $name ($category->last_id)";
	$array["category_id"]=$category->last_id;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	
}

function CATEGORIZE_LIST($category_id,$pattern){
    $MAX=250;
    if(preg_match("#MAX=([0-9]+)#",$pattern,$re)){
        $MAX=$re[1];
        $pattern=str_replace("MAX={$re[1]}","",$pattern);

    }

	$pattern="*$pattern";
	$pattern=str_replace("**", "*", $pattern);
	$pattern=str_replace("**", "*", $pattern);
	$pattern=trim(str_replace("*", "%", $pattern));
	$q=new mysql_squid_builder();
	$categorytable=$q->GetCategoryTable($category_id);
	if($categorytable==null){
		$array["status"]=false;
		$array["message"]="Wrong Category ID $category_id (table not found)";
		$array["count"]=0;
		$array["sites"]=array();
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),500);
		exit;
	}
	
	$sql="SELECT * FROM $categorytable WHERE sitename LIKE '$pattern' ORDER BY sitename LIMIT $MAX ";
	
	if($pattern=="%"){$sql="SELECT * FROM $categorytable ORDER BY sitename LIMIT $MAX ";}
	
	
	
	$q=new postgres_sql();
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		$array["status"]=false;
		$array["message"]=$q->mysql_error;
		$array["count"]=0;
		$array["sites"]=array();
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),500);
		exit;
	}
	
	if(pg_num_rows($results)==0){
		$array["status"]=false;
		$array["message"]="Nothing found";
		$array["count"]=0;
		$array["sites"]=array();
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),500);
		exit;
		
	}
	
	while ($ligne = pg_fetch_assoc($results)) {
		$f[]=$ligne["sitename"];
		
	}
	$array["status"]=true;
	$array["message"]="";
	$array["count"]=count($f);
	$array["sites"]=$f;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	exit;
	
	
}

function RestSyslog($text){

    $LOG_SEV=LOG_INFO;
    if(function_exists("openlog")){openlog("DNS_FILTER_RESTAPI", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
    if(function_exists("closelog")){closelog();}
}

function CATEGORIZE($category_id,$sitename,$next_category_id){
	$FORCE=false;

    RestSyslog("Command (1):'$category_id' (2):'$sitename' (3):'$next_category_id'");

	if($sitename=="list"){
        RestSyslog("CATEGORIZE_LIST()");
		CATEGORIZE_LIST($category_id,$next_category_id);
		return;
	}

	$next_category_id=intval($next_category_id);
	$sitename=trim(strtolower($sitename));
	$sitename=str_replace(array("*",";"), "", $sitename);
	$q=new mysql_squid_builder();
	$pos=new postgres_sql();

    RestSyslog("[$sitename]: next_category_id: $next_category_id");

	if($next_category_id>0){
		$categorytable=$q->GetCategoryTable($next_category_id);
        RestSyslog("[$sitename]: next_category_id: $categorytable");
		if($categorytable==null){
			$array["status"]=false;
            RestSyslog("[$sitename]: Wrong Category ID $next_category_id (table not found)");
			$array["message"]="Wrong Category ID $next_category_id (table not found)";
			$array["category_id"]=0;
			$RestAPi=new RestAPi();
			$RestAPi->response(json_encode($array),500);
			exit;
		}

        RestSyslog("[$sitename]: DELETE FROM $categorytable WHERE sitename='$sitename'");
		$pos->QUERY_SQL("DELETE FROM $categorytable WHERE sitename='$sitename'");
		$FORCE=true;
		if(!$pos->ok){
            RestSyslog("[$sitename]: $sitename: $pos->mysql_error");
			$array["status"]=false;
			$array["message"]="$sitename: $pos->mysql_error";
			$array["category_id"]=0;
			$RestAPi=new RestAPi();
			$RestAPi->response(json_encode($array),500);
			exit;
		}
		
		if($category_id==0){
            RestSyslog("[$sitename]: Removed $sitename Success from $next_category_id");
			$array["status"]=true;
			$array["message"]="Removed $sitename Success from $next_category_id";
			$array["category_id"]=$next_category_id;
			$RestAPi=new RestAPi();
			$RestAPi->response(json_encode($array),200);
			exit;
		}
		
	}
	
	
	if($category_id==0){
        RestSyslog("[$sitename]: Wrong Category ID");
		$array["status"]=false;
		$array["message"]="Wrong Category ID";
		$array["category_id"]=0;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),500);
		exit;
	}
	if($sitename==null){
		$array["status"]=false;
		$array["message"]="Website to categorize is null";
		$array["category_id"]=0;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),500);
		exit;
	}
	
	
	$ligne=$pos->mysqli_fetch_array("SELECT categoryname FROM personal_categories where category_id=$category_id");
	
	$categoryname=trim($ligne["categoryname"]);
    RestSyslog("[$sitename]: Category $category_id ($categoryname)");

	if($categoryname==null){
        RestSyslog("[$sitename]: Category $category_id not found");
		$array["status"]=false;
		$array["message"]="Category $category_id not found";
		$array["category_id"]=0;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),500);
		exit;
		
	}
	
	$zcat=new mysql_catz();
    RestSyslog("[$sitename]: Category $category_id ($categoryname) Force=$FORCE");
	if(!$q->categorize($sitename, $category_id,$FORCE)){
		if($q->last_id>0){
			if($q->last_id==$category_id){
			    $categoryname=$zcat->CategoryIntToStr($category_id);
                RestSyslog("[$sitename]: Already categorized as $category_id ($categoryname)");
				$array["status"]=True;
				$array["message"]="$q->finaldomain Already categorized as $category_id ($categoryname)";
				$array["category_id"]=$category_id;
                $array["categoryname"]=$categoryname;
				$RestAPi=new RestAPi();
				$RestAPi->response(json_encode($array),200);
				exit;
			}
		}
		$array["status"]=false;
		$array["message"]="$q->finaldomain: $q->mysql_error";
		$array["category_id"]=$q->last_id;
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),503);
		exit;
	}
    RestSyslog("[$sitename]: Category $category_id ($categoryname) Force=$FORCE Success.");
	$array["status"]=true;
	$array["message"]="$q->finaldomain success";
	$array["category_id"]=$category_id;
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	exit;

}
function LIST_CATEGORIES_SQL(){
    $ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    if($ManageOfficialsCategories==1){
        return "SELECT * FROM personal_categories WHERE free_category=0 order by categoryname";
    }

    return "SELECT * FROM personal_categories order by categoryname";

}

function LIST_CATEGORIES(){
    $sql=LIST_CATEGORIES_SQL();
    $q=new postgres_sql();
    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){
        $array["status"]=false;
        $array["message"]=$q->mysql_error;
        $array["categories"]=array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),503);
        exit;
    }

    while ($ligne = pg_fetch_assoc($results)) {
        $category_id=$ligne["category_id"];
        $categoryname=$ligne["categoryname"];
        $categorykey=$ligne["categorykey"];
        $description=$ligne["category_description"];
        $description=str_replace("{free_edition}","Free edition",$description);
        $categoryname=str_replace("{free_edition}","Free edition",$categoryname);
        if(preg_match("#^reserved#", $categoryname)){continue;}
        $CATEGORIES[$category_id]["NAME"]=$categoryname;
        $CATEGORIES[$category_id]["id"]=$category_id;
        $CATEGORIES[$category_id]["DESCRIPTION"]=$description;
    }


    if(count($CATEGORIES)==0){
        $array["status"]=false;
        $array["message"]="No category";
        $array["TOTAL"]=count($CATEGORIES);
        $array["categories"]=array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),503);
        return;

    }


    $array["status"]=true;
    $array["message"]=count($CATEGORIES)." categories";
    $array["TOTAL"]=count($CATEGORIES);
    $array["categories"]=$CATEGORIES;
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    return;
}



