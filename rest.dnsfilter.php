<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__).'/ressources/class.rest.inc');
include_once(dirname(__FILE__).'/ressources/class.dnsfilter.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.familysites.inc');
include_once(dirname(__FILE__).'/ressources/class.categories.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

isEnabled();


$request_uri=$_SERVER["REQUEST_URI"];
$request_uri=str_replace("/api/rest/dnsfilter/", "", $request_uri);
$f=explode("/",$request_uri);

foreach ($f as $index=>$params){
    $params=str_replace("?","",$params);
    $f[$index]=$params;
}

if($f[0]=="rules"){LIST_RULES($f[1]);exit;}
if($f[0]=="save-rule"){SAVE_RULE();exit;}
if($f[0]=="delete"){DELETE_RULE($f[1]);exit;}
if($f[0]=="categories"){LIST_CATEGORIES();exit;}
if($f[0]=="service"){SERVICE($f[1],$f[2],$f[3]);exit;}


RestSyslog("Unable to understand query <{$f[0]}> <{$f[1]}> in $request_uri");

$array["status"]=false;
$array["message"]="Unable to understand query <{$f[0]}> <{$f[1]}> in $request_uri";
$array["category"]=0;

$RestAPi=new RestAPi();
$RestAPi->response(json_encode($array),404);


function isEnabled(){

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $array["status"]=false;
        $array["message"]="License Error";
        $array["category"]=0;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),407);
        logon_events("FAILED");
        RestSyslog("License Error.");
        exit;
    }
    $EnableCategoriesRESTFul=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSFilterdRest"));
    if($EnableCategoriesRESTFul==1){return isAuth();}
    $array["status"]=false;
    $array["message"]="Feature disabled";
    $array["category"]=0;
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),407);
    logon_events("FAILED");
    RestSyslog("Feature not enabled.");
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


function isAuth(){
    $RestAPi = new RestAPi();
    $DNSFilterRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFilterRESTFulAPIKey"));
    $SystemRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemRESTFulAPIKey"));



    if(isset($_SERVER["ArticaKey"])){$MyArticaKey=$_SERVER["ArticaKey"];}
    if(isset($_SERVER["HTTP_ARTICAKEY"])){$MyArticaKey=$_SERVER["HTTP_ARTICAKEY"];}
    if($MyArticaKey==null) {
        $array["status"] = false;
        RestSyslog("Authentication Failed ( missing header)");
        $array["message"] = "Authentication Failed ( missing header)";
        $array["category"] = 0;
        $RestAPi->response(json_encode($array), 407);
        logon_events("FAILED");
        exit;
    }

    if($MyArticaKey==$SystemRESTFulAPIKey){return true;}
    if($MyArticaKey==$DNSFilterRESTFulAPIKey){return true;}

     RestSyslog("Authentication Failed");
     $array["status"] = false;
     $array["message"] = "Authentication Failed";
     $array["category"] = 0;
     $RestAPi->response(json_encode($array), 407);
     exit;

}

function SERVICE($command=null,$second_command=null,$troisiemme=null){

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


function LIST_CATEGORIES(){
    $sql="SELECT * FROM personal_categories order by categoryname";
    $q=new postgres_sql();
    $results=$q->QUERY_SQL($sql);

    if(!$q->ok){
        $array["status"]=false;
        $array["message"]=$q->mysql_error;
        $array["categories"]=array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }

    while ($ligne = pg_fetch_assoc($results)) {
        $category_id=$ligne["category_id"];
        $categoryname=$ligne["categoryname"];
        $categorykey=$ligne["categorykey"];
        $description=$ligne["category_description"];
        if(preg_match("#^reserved#", $categoryname)){continue;}
        $CATEGORIES[$category_id]["NAME"]=$categoryname;
        $CATEGORIES[$category_id]["KEY"]=$categorykey;
        $CATEGORIES[$category_id]["id"]=$category_id;
        $CATEGORIES[$category_id]["DESCRIPTION"]=$description;
    }


    if(count($CATEGORIES)==0){
        $array["status"]=false;
        $array["message"]="No category";
        $array["TOTAL"]=count($CATEGORIES);
        $array["categories"]=array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
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
    $q->QUERY_SQL("DELETE FROM webfilter_ipsources WHERE ruleid='$ID'");


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

    $ID=$_POST["ID"];

    if($ID==0){
        $sock=new dnsfiltersocks();
        $DEFAULTARRAY=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
        $DNS_PARAMS=unserialize(base64_decode($DEFAULTARRAY["ExternalWebPage"]));
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

        $DNS_PARAMS["dns_use_default"]=1;

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

    if(isset($_POST["dns_use_default"])){
        $DNS_PARAMS["dns_use_default"]=$_POST["dns_use_default"];
    }
    if(isset($_POST["dns_ipaddr"])){
        $DNS_PARAMS["dns_ipaddr"]=$_POST["dns_ipaddr"];
    }
    if(isset($_POST["dns_neg_ttl"])){
        $DNS_PARAMS["dns_neg_ttl"]=$_POST["dns_neg_ttl"];
    }


    $DNS_PARAMS_NEW=base64_encode(serialize($DNS_PARAMS));
    $Fields_add[]="ExternalWebPage";
    $values_add[]="'$DNS_PARAMS_NEW'";
    $Fields_edit[]="ExternalWebPage='$DNS_PARAMS_NEW'";
    $DEFAULTARRAY["ExternalWebPage"]=$DNS_PARAMS_NEW;

    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");

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
        $ID = intval($ligne["ID"]);
        RestSyslog("Create new rule {$_POST["rulename"]} as ID $ID");
        if($ID==0){
            $q->QUERY_SQL($sql);
            if(!$q->ok){
                $array["status"]=false;
                $array["message"]="Unable to find the ID";
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array),500);
                exit;
            }
        }

        if(isset($_POST["blacklists"])){
                RestSyslog("Create new rule as {$_POST["blacklists"]} as blacklists");
                $zcategories=explode(",",$_POST["blacklists"]);
                foreach ($zcategories as $category_id){
                    $q->QUERY_SQL("INSERT INTO webfilter_blks (webfilter_id,category,modeblk) 
                    VALUES ('$ID','$category_id',0)");
                    RestSyslog("Create new rule  new category $ID/$category_id as blacklists");
                    if(!$q->ok){
                        $array["status"]=false;
                        $array["message"]=$q->mysql_error;
                        $RestAPi=new RestAPi();
                        $RestAPi->response(json_encode($array),500);
                        exit;
                    }
                }

        }
        if(isset($_POST["whitelists"])){
            RestSyslog("Create new rule as {$_POST["whitelists"]} as whitelist");
            $zcategories=explode(",",$_POST["whitelists"]);
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

        }

        if(isset($_POST["src"])){
            $td=explode(",",$_POST["src"]);
            $ipClass=new IP();
            foreach ($td as $line){
                if(!$ipClass->isIPAddressOrRange($line)){continue;}
                $q->QUERY_SQL("INSERT INTO webfilter_ipsources (ruleid,ipaddr) VALUES ($ID,'$line')");
            }
        }

        $array["status"]=True;
        $array["message"]=@implode(",",$Fields_add)." Fields added";
        $array["rules"]=array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;
    }




        if($ID==0){
            RestSyslog("Edit default rule...");
            $sock=new dnsfiltersocks();
            $sock->SET_INFO("DansGuardianDefaultMainRule",serialize($DEFAULTARRAY));
        }

            if(isset($_POST["blacklists"])){
                $q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id=0 AND modeblk=0");
                $zcategories=explode(",",$_POST["blacklists"]);
                foreach ($zcategories as $category_id){
                    $q->QUERY_SQL("INSERT INTO webfilter_blks (webfilter_id,category,modeblk) 
                    VALUES ('0','$category_id',0)");
                    if(!$q->ok){
                        $array["status"]=false;
                        $array["message"]=$q->mysql_error;
                        $RestAPi=new RestAPi();
                        $RestAPi->response(json_encode($array),500);
                        exit;}
                }

            }


            if(isset($_POST["whitelists"])){
                $q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id=0 AND modeblk=1");
                $zcategories=explode(",",$_POST["whitelists"]);
                foreach ($zcategories as $category_id){
                    $q->QUERY_SQL("INSERT INTO webfilter_blks (webfilter_id,category,modeblk) 
                    VALUES ('0','$category_id',1)");
                    if(!$q->ok){
                        $array["status"]=false;
                        $array["message"]=$q->mysql_error;
                        $RestAPi=new RestAPi();
                        $RestAPi->response(json_encode($array),500);
                        exit;}
                }

            }



            $array["status"]=True;
            $array["message"]=@implode(",",$Fields_add)." Fields edited";
            $array["rules"]=array();
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),200);
            exit;




    RestSyslog("Edit rule ID $ID...");
    $sql="UPDATE webfilter_rules SET ".@implode(",",$Fields_edit)." WHERE ID=$ID";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        $array["status"]=false;
        $array["message"]=$q->mysql_error;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }

    if(isset($_POST["blacklists"])){
        $q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id=$ID AND modeblk=0");
        $zcategories=explode(",",$_POST["blacklists"]);
        foreach ($zcategories as $category_id){
            $q->QUERY_SQL("INSERT INTO webfilter_blks (webfilter_id,category,modeblk) 
                    VALUES ('$ID','$category_id',0)");
            if(!$q->ok){
                $array["status"]=false;
                $array["message"]=$q->mysql_error;
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array),500);
                exit;}
        }

    }
    if(isset($_POST["whitelists"])){
        $q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id=$ID AND modeblk=1");
        $zcategories=explode(",",$_POST["whitelists"]);
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

    }

    if(isset($_POST["src"])){
        $td=explode(",",$_POST["src"]);
        $q->QUERY_SQL("DELETE FROM webfilter_ipsources WHERE ruleid='$ID'");
        $ipClass=new IP();
        foreach ($td as $line){
            if(!$ipClass->isIPAddressOrRange($line)){continue;}
            $q->QUERY_SQL("INSERT INTO webfilter_ipsources (ruleid,ipaddr) 
            VALUES ($ID,'$line')");
        }

    }

    $array["status"]=True;
    $array["message"]=@implode(",",$Fields_add)." Fields added";
    $array["rules"]=array();
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    exit;
}



function LIST_RULES($second_command=null){

    if($second_command=="apply"){
        $sock=new sockets();
        $sock->getFrameWork("dnsfilterd.php?compile-rules=yes");
        $array["status"]=true;
        $array["message"]="Rules are compiled in background mode";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;
    }


    $webfilter=new webfilter_rules("dns.db");
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");

	

    $rules=array();
    $catz=new mysql_catz();
    $sock=new dnsfiltersocks();
    $ligne=unserialize($sock->GET_INFO("DansGuardianDefaultMainRule"));
    $rulename="default";
    $CountDeBlack=intval($webfilter->COUNTDEGBLKS(0));
    $CountDewhite=intval($webfilter->COUNTDEGBWLS(0));
    $LigneCount=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM webfilter_ipsources WHERE ruleid={$ligne['ID']}");
    $CountDeGroups=intval($LigneCount["tcount"]);
    $groupmode=$ligne["groupmode"];
    $zOrder=0;
    $DNS_PARAMS=unserialize(base64_decode($ligne["ExternalWebPage"]));
    if(!isset($DNS_PARAMS["dns_use_default"])){$DNS_PARAMS["dns_use_default"]=1;}
    if(!isset($DNS_PARAMS["dns_ipaddr"])){$DNS_PARAMS["dns_ipaddr"]="127.0.0.1";}
    if(!isset($DNS_PARAMS["dns_neg_ttl"])){$DNS_PARAMS["dns_neg_ttl"]=3600;}
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
    $rules[0]["dns_use_default"]=$DNS_PARAMS["dns_use_default"];
    $rules[0]["dns_ipaddr"]=$DNS_PARAMS["dns_ipaddr"];
    $rules[0]["dns_neg_ttl"]=$DNS_PARAMS["dns_neg_ttl"];

    $zmodeblk[0]="blacklists";
    $zmodeblk[1]="whitelists";

    $sql="SELECT * FROM webfilter_blks WHERE `webfilter_id`=0";
    $results = $q->QUERY_SQL($sql);

    if(!$q->ok){
        $array["status"]=false;
        $array["message"]=$q->mysql_error;
        $array["rules"]=array();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }


    foreach ($results as $index=>$ligne){
        $category_id=$ligne["category"];
        $modeblk=$ligne["modeblk"];
        $rules[0][$zmodeblk[$modeblk]]["CATEGORIES"][$category_id]=$catz->CategoryIntToStr($category_id);
    }

    $sql="SELECT * FROM webfilter_rules ORDER BY zOrder";
    $results=$q->QUERY_SQL($sql);

    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $rules[$ID]["ID"]=$ID;
        $groupmode=$ligne["groupmode"];
        $rulename=$ligne["groupname"];
        $CountDeBlack=intval($webfilter->COUNTDEGBLKS($ligne["ID"]));
        $CountDewhite=intval($webfilter->COUNTDEGBWLS($ligne["ID"]));
        $LigneCount=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM webfilter_ipsources WHERE ruleid={$ligne['ID']}");
        $CountDeGroups=intval($LigneCount["tcount"]);
        $zOrder=$ligne["zOrder"];
        $DNS_PARAMS=unserialize(base64_decode($ligne["ExternalWebPage"]));
        if(!isset($DNS_PARAMS["dns_use_default"])){$DNS_PARAMS["dns_use_default"]=1;}
        if(!isset($DNS_PARAMS["dns_ipaddr"])){$DNS_PARAMS["dns_ipaddr"]="127.0.0.1";}
        if(!isset($DNS_PARAMS["dns_neg_ttl"])){$DNS_PARAMS["dns_neg_ttl"]=3600;}


        $rules[$ID]["rulename"]=$rulename;
        $rules[$ID]["order"]=$zOrder;
        $rules[$ID]["blacklists"]["COUNT"]=$CountDeBlack;
        $rules[$ID]["whitelists"]["COUNT"]=$CountDewhite;
        $rules[$ID]["src"]["COUNT"]=$CountDeGroups;
        $rules[$ID]["mode"]=$groupmode;
        $rules[$ID]["AllSystems"]=$ligne["AllSystems"];
        $rules[$ID]["endofrule"]=$ligne["endofrule"];
        $rules[$ID]["enabled"]=intval($ligne["enabled"]);
        $rules[$ID]["dns_use_default"]=$DNS_PARAMS["dns_use_default"];
        $rules[$ID]["dns_ipaddr"]=$DNS_PARAMS["dns_ipaddr"];
        $rules[$ID]["dns_neg_ttl"]=$DNS_PARAMS["dns_neg_ttl"];

        $sql="SELECT * FROM webfilter_blks WHERE `webfilter_id`=$ID";
        $results2 = $q->QUERY_SQL($sql);
        foreach ($results2 as $index2=>$ligne2){
            $category_id=$ligne2["category"];
            $modeblk=intval($ligne2["modeblk"]);
            $rules[$ID][$zmodeblk[$modeblk]]["CATEGORIES"][$category_id]=$catz->CategoryIntToStr($category_id);
        }


        $results2=$q->QUERY_SQL("SELECT * FROM webfilter_ipsources WHERE ruleid='$ID' ORDER BY ipaddr");
        foreach ($results2 as $index2=>$ligne2){
            $rules[$ID]["src"]["CLIENTS"][]=$ligne2["ipaddr"];
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






