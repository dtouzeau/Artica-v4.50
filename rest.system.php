<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
//$GLOBALS["VERBOSE"]=true;
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.rest.inc');
include_once(dirname(__FILE__).'/ressources/class.external.ad.inc');

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


isAuth();

$request_uri=$_SERVER["REQUEST_URI"];
if(strpos("$request_uri","?verbose=yes")){$request_uri=str_replace("?verbose=yes","",$request_uri);}
$request_uri=str_replace("/api/rest/system/", "", $request_uri);
$f=explode("/",$request_uri);



if( isset($_POST['patch-artica']) ){PATCH_UPLOADED();exit();}
if($f[0]=="debpkg"){REST_DEBIAN_PACKAGE($f[1]);exit();}
if($f[0]=="reboot"){ REBOOT();exit; }
if($f[0]=="webconsole"){ REST_WEBCONSOLE($f[1]);exit; }
if($f[0]=="activedirectory"){ REST_ACTIVEDIRECTORY($f[1]); exit;}
if($f[0]=="interface"){ REST_INTERFACE($f[1]);exit; }
if($f[0]=="hostname"){ REST_HOSTNAME($f[1]); exit;}
if($f[0]=="info"){ EXTRACT_INFO();exit; }
if($f[0]=="articaver"){ ARTICA_VERSION();exit; }
if($f[0]=="features"){ FEATURES($f[1],$f[2],$f[3]);exit; }
if($f[0]=="swap"){ SWAP_REST($f[1],$f[2],$f[3]);exit; }
if($f[0]=="rsync-update"){ RSYNC_UPDATE($f[1],$f[2],$f[3]);exit; }
if($f[0]=="artica-update"){ UPDATE_NOW(); exit;}
if($f[0]=="statistics"){ STATISTICS($f[1],$f[2],$f[3]);exit; }
if($f[0]=="hosts"){ HOSTS_FILE($f[1],$f[2],$f[3],$f[4]);exit; }
if($f[0]=="monitor"){ MONITOR_SERVICES($f[1],$f[2],$f[3],$f[4]);exit; }
if($f[0]=="license"){ LICENSE($f[1],$f[2],$f[3],$f[4]);exit; }
if($f[0]=="snmp"){SNMP_COMMANDS($f[1],$f[2],$f[3]);exit;}
if($f[0]=="privs"){PRIVILEGES($f[1],$f[2],$f[3]);exit;}
if($f[0]=="emergency"){EMERGENCY($f[1],$f[2]);exit;}




events("Unable to understand query <{$f[0]}> <{$f[1]}> in $request_uri");
$array["status"]=false;
$array["message"]="Unable to understand query <{$f[0]}> <{$f[1]}> in $request_uri";
$RestAPi=new RestAPi();
$RestAPi->response(json_encode($array),404);
function REST_DEBIAN_PACKAGE($pkg=null){
    if($pkg==null){
        $array["status"]=False;
        $array["message"]="Please specify a debian package";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),407);
        die();

    }
    $pkg=url_decode_special_tool($pkg);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("services.php?apt-get-install=$pkg");
    $array["status"]=True;
    $array["message"]="Package $pkg is scheduled for installation";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    die();
}

function PRIVILEGES($cmd1=null,$cmd2=null,$cmd3=null,$cmd4=null){
    $q=new lib_sqlite("/home/artica/SQLITE/privileges.db");
    $trings="AsWebStatisticsAdministrator,AsDansGuardianAdministrator,AsSquidAdministrator,AsHotSpotManager,AsProxyMonitor,AsPostfixAdministrator,AsMailBoxAdministrator,AsArticaAdministrator,AsFirewallManager,AsVPNManager,AsDnsAdministrator,ASDCHPAdmin,AsCertifsManager,AsSambaAdministrator";

    if($cmd1==null){


        $results=$q->QUERY_SQL("SELECT * FROM `adgroupsprivs`");
        if(!$q->ok){
            $array["status"]=False;
            $array["message"]=$q->mysql_error;
            $array["supported"]=$trings;
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),500);
            die();
        }

        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            $DN=$ligne["DN"];
            $content=$ligne["content"];
            $ArticaGroupPrivileges=base64_decode($content);
            if(!is_array($ArticaGroupPrivileges)){
                $ldap=new clladp();
                $ArticaGroupPrivilegesArray=$ldap->_ParsePrivieleges($ArticaGroupPrivileges);
            }else{
                $ArticaGroupPrivilegesArray=$ArticaGroupPrivileges;
            }
            $GroupPrivileges=array();
            if(is_array($ArticaGroupPrivilegesArray)){
                foreach ($ArticaGroupPrivilegesArray as $num=>$val){
                    if(strtolower($val)<>"yes"){continue;}
                    $GroupPrivileges[]=$num;
                }
            }

            $MAIN[]=array("DN"=>$DN,"ID"=>$ID,"PRIVS"=>@implode(",",$GroupPrivileges));

        }
        $array["status"]=true;
        $array["supported"]=$trings;
        $array["message"]="Privileges on Artica Web console";
        $array["privileges"]=$MAIN;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        die();

    }


    if(strtolower($cmd1)=="remove"){
        $ID=intval($cmd2);
        $ligne=$q->mysqli_fetch_array("SELECT DN FROM `adgroupsprivs` WHERE ID=$ID");
        $DN=$ligne["DN"];

        $q->QUERY_SQL("DELETE FROM `adgroupsprivs` WHERE ID=$ID");
        if(!$q->ok){
            $array["status"]=False;
            $array["message"]=$q->mysql_error;
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),500);
            die();
        }
        $array["status"]=true;
        $array["message"]="Successfull removed privileges on record $DN ($ID)";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        die();


    }

    $DN=urldecode($cmd1);
    $cmd2=urldecode($cmd2);
     $zKeys=explode(",",$cmd2);

    foreach ($zKeys as $val){
        $GroupPrivilegeNew[]="[$val]=\"yes\"";
    }

    $gp=new external_ad_search();
   if(!$gp->SaveGroupPrivileges(@implode("\n",$GroupPrivilegeNew),$DN)){
       $array["status"]=False;
       $array["message"]="Failed to save privileges on $DN";
       $RestAPi=new RestAPi();
       $RestAPi->response(json_encode($array),500);
       die();
   }

    $array["status"]=true;
    $array["message"]="Success to save privileges on $DN";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    die();



}

function EMERGENCY($cmd1=null,$cmd2){
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}

    if($cmd1=="activedirectory"){
        if($cmd2=="on"){
            if($LockActiveDirectoryToKerberos==0) {
                $GLOBALS["CLASS_SOCKETS"]->getFrameWork("winbindd.php?emergency=yes");
                $array["status"]=True;
                $array["message"]="Success sent turn ON Active Directory Emergency NTLM";
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array),200);
                die();
            }else{
                $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/emergency/activedirectory/off");
                $array["status"]=True;
                $array["message"]="Success sent turn ON Active Directory Emergency Kerberos";
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array),200);
                die();
            }

        }
        if($cmd2=="off"){
            if($LockActiveDirectoryToKerberos==0) {
                $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid.php?disable-adurgency=yes");
                $array["status"]=True;
                $array["message"]="Success sent turn OFF Active Directory Emergency NTLM";
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array),200);
                die();
            }else{
                $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/emergency/activedirectory/off");
                $array["status"]=True;
                $array["message"]="Success sent turn OFF Active Directory Emergency kerberos";
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array),200);
                die();
            }
        }

        if($cmd2=="join"){
            if($LockActiveDirectoryToKerberos==0) {
                $GLOBALS["CLASS_SOCKETS"]->getFrameWork("winbindd.php?emergency=yes");
                $array["status"]=True;
                $array["message"]="Success sent NTLM join operation";
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array),200);
                die();
            }else{
                $array["status"]=false;
                $array["message"]="Join operation is only available on NTLM method.";
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array),500);
            }
        }


    }

    events("Unable to understand query <{$cmd1}> <{$cmd2}>");
    $array["status"]=false;
    $array["message"]="Unable to understand query <{$cmd1}> <{$cmd2}>";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),404);
}

function LICENSE($cmd1=null,$cmd2=null,$cmd3=null,$cmd4=null){


    if($cmd1=="gold"){
        if(!$GLOBALS["CLASS_SOCKETS"]->IsGoldKey($cmd2)){
            $array["status"]=False;
            $array["message"]="[$cmd2]: Invalid license Key";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),500);
            die();
        }
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_ARTICA_LIC_GOLD",$cmd2);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/register/license");
        $array["status"]=True;
        $array["message"]="[$cmd2]: Successfully registered";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        die();
    }


    $WizardSaved            = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings");
    $LicenseINGP            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseINGP"));
    $License                = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos");
    $LicenseInfos           = unserialize(base64_decode($License));
    $WizardSavedSettings    = unserialize(base64_decode($WizardSaved));
    if(!isset($LicenseInfos["COMPANY"])){$LicenseInfos["COMPANY"]=$WizardSavedSettings["organization"];}

    if ($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()) {
        $LicenseInfos["license_status"]="License Active";

        if($LicenseINGP>0){
            if($LicenseINGP>time()) {
                $LicenseINGPDistance = distanceOfTimeInWords(time(), $LicenseINGP);
                $LicenseInfos["license_status"] = "Grace period";
                $LicenseInfos["grace_period_expire"] = ($LicenseINGP - time()) . " seconds.";
                $LicenseInfos["grace_period_expire_text"] = $LicenseINGPDistance;
            }
        }

    }else{
        $array["status"]=True;
        $array["license_status"]=$LicenseInfos["license_status"];
        $array["message"]="Community License";
        $array["company"]=$LicenseInfos["COMPANY"];
        $array["expire"]="Never";
        $array["expire_in"]="Never";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        die();
    }

    $FINAL_TIME=0;
    if (isset($LicenseInfos["FINAL_TIME"])) {
        $FINAL_TIME=intval($LicenseInfos["FINAL_TIME"]);
    }


    if($FINAL_TIME>0){
        $ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));
        $distanceOfTimeInWords=distanceOfTimeInWords(time(), $FINAL_TIME);
    }

    if(isset($LicenseInfos["license_number"])){
        if($GLOBALS["CLASS_SOCKETS"]->IsGoldKey($LicenseInfos["license_number"])){
            $LicenseInfos["license_status"]="Gold License Active";
            $ExpiresSoon="Never";
            $distanceOfTimeInWords="unlimited";
        }
    }
    $array["status"]=True;
    $array["license_status"]=$LicenseInfos["license_status"];
    $array["message"]="Entreprise License";
    $array["company"]=$LicenseInfos["COMPANY"];
    $array["expire"]=$ExpiresSoon;
    $array["expire_in"]=$distanceOfTimeInWords;
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);

}

function SNMP_COMMANDS($command=null,$value1=null,$value2=null){

    if($command=="status"){
        $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPD_VERSION");
        $EnableSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSNMPD"));
        if($EnableSNMPD==0){
            $RestAPi=new RestAPi();
            $array["status"]=False;
            $array["message"]="SNMPD v$version - Feature not installed";
            $RestAPi->response(json_encode($array),407);
            die();
        }

        $array["status"]=True;
        $array["message"]="SNMPD v$version - status";
        $array["Info"]["version"]=$version;


        $SNMPDCommunity=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDCommunity");
        if($SNMPDCommunity==null){$SNMPDCommunity="public";}
        $SNMPDNetwork=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDNetwork");
        if($SNMPDNetwork==null){$SNMPDNetwork="default";}
        $SNMPDUsername=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDUsername"));
        $SNMPDPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDPassword"));
        $SNMPDOrganization=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDOrganization"));
        $SNMPDContact=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDContact"));
        $SNMPDagentAddress=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDagentAddress"));
        if($SNMPDagentAddress==0){$SNMPDagentAddress=161;}
        $SNMPDInterfaceAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDInterfaceAddress"));
        $SNMPDDisablev2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDDisablev2"));
        $SNMPDPassphrase=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDPassphrase"));

        $array["params"]["community"]=$SNMPDCommunity;
        $array["params"]["network"]=$SNMPDNetwork;
        $array["params"]["udp_port"]=$SNMPDagentAddress;
        $array["params"]["interface"]=$SNMPDInterfaceAddress;
        $array["params"]["disable_v2"]=$SNMPDDisablev2;
        $array["params"]["passphrase"]=$SNMPDPassphrase;
        $array["params"]["username"]=$SNMPDUsername;
        $array["params"]["password"]=$SNMPDPassword;
        $array["params"]["organization"]=$SNMPDOrganization;
        $array["params"]["contact"]=$SNMPDContact;

        $ARRAYP["username"]=$SNMPDUsername;
        $ARRAYP["password"]=$SNMPDPassword;
        $ARRAYP["passphrase"]=$SNMPDPassphrase;
        $authstring=base64_encode(serialize($ARRAYP));

        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("snmpd.php?walk-local=yes&port=$SNMPDagentAddress&com=$SNMPDCommunity&Disablev2=$SNMPDDisablev2&auth=$authstring&eth=$SNMPDInterfaceAddress");
        $content=@file_get_contents(PROGRESS_DIR."/snmpd.walk");

        if(preg_match("#STRING:\s+\"(.+?)\"#is",$content,$re)){
            $array["Info"]["SNMPWALK"]=true;
        }else{
            $array["Info"]["SNMPWALK"]=false;
        }

        $data=json_decode( $GLOBALS["CLASS_SOCKETS"]->REST_API("/snmpd/status"));
        $bsini=new Bs_IniHandler();
        $bsini->loadString($data->Info);
        foreach ($bsini->_params["APP_SNMPD"] as $key=>$val){
            $array["Info"][$key]=$val;
        }

        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        die();

    }

    $translate["community"]="SNMPDCommunity";
    $translate["network"]="SNMPDNetwork";
    $translate["udp_port"]="SNMPDagentAddress";
    $translate["interface"]="SNMPDInterfaceAddress";
    $translate["disable_v2"]="SNMPDDisablev2";
    $translate["passphrase"]="SNMPDPassphrase";
    $translate["username"]="SNMPDUsername";
    $translate["password"]="SNMPDPassword";
    $translate["organization"]="SNMPDOrganization";
    $translate["contact"]="SNMPDContact";

    if(!isset($translate[$command])){
        $array["status"]=False;
        $array["message"]="$command - Feature not understood";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),407);
        die();

    }
    $value=$GLOBALS["CLASS_SOCKETS"]->GET_INFO($translate[$command]);
    if($value1==null){
        $array["status"]=True;
        $array["message"]="$command - return value";
        $array["value"]=$value;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        die();
    }else{
        if($value1==$value){
            $array["status"]=True;
            $array["message"]="$command - $value";
            $array["value"]=$value;
            $array["action"]="Not modified";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),200);
        }else{
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO($translate[$command],$value1);
            $array["status"]=True;
            $array["message"]="$command - modify $value to $value1";
            $array["value"]=$value1;
            $array["action"]="Modified";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),200);
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/snmpd/restart");
        }
    }

}

function PATCH_UPLOADED(){
    $FILE_KEY=null;
    foreach ($_FILES as $key=>$array){
        if(isset($array["tmp_name"])){
            if(isset($array["name"])){
                if(isset($array["error"])){
                    if($GLOBALS["VERBOSE"]) {"Found File Key: $key";}
                    $FILE_KEY = $key;
                    break;

                }
            }
        }

    }



    if($FILE_KEY==null){
        $array["status"]=false;
        $array["message"]="Unable to get file key in upload";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),407);
        exit;
    }

    $tmp_file=$_FILES[$FILE_KEY]["tmp_name"];
    $filename=$_FILES[$FILE_KEY]["name"];
    $error=intval($_FILES[$FILE_KEY]["error"]);
    $size=intval($_FILES[$FILE_KEY]["size"]);
    $tarballs_file="/usr/share/artica-postfix/ressources/conf/upload/$filename";


    events("Uploaded: file:$tmp_file name:$filename Error:$error size:$size",__LINE__);
    if(is_file($tarballs_file)){
        $sock=new sockets();
        $sock->getFrameWork("system.php?install-artica-tgz=yes&filename=".urlencode($filename));
        $array["status"]=true;
        $array["message"]="$filename Success (operation made in background task)";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;
    }

    if($error>0){
        $array["status"]=false;
        $array["message"]="Error upload $error";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),407);
        exit;

    }
    events("Call move_uploaded_file($tmp_file, $tarballs_file)",__LINE__);
    if (!move_uploaded_file($tmp_file, $tarballs_file)){
        $array["status"]=false;
        $array["message"]="move_uploaded_file failed";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),407);
        exit;
    }

    if(!is_file($tarballs_file)){
        events("$tarballs_file no such file.",__LINE__);
        $array["status"]=false;
        $array["message"]="$tarballs_file (no such file)";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),407);
        exit;

    }
    $size=@filesize($tarballs_file);
    events("After Uploaded: $tarballs_file = ".FormatBytes($size/1024),__LINE__);

    if(!preg_match("#artica-[0-9\.]+\.tgz$#",$filename)){
        if(!preg_match("#ArticaP[0-9\.]+\.tgz$#",$filename)) {
            $array["status"] = false;
            $array["message"] = "$filename not correclty formated filename";
            @unlink($tarballs_file);
            $RestAPi = new RestAPi();
            $RestAPi->response(json_encode($array), 407);
            exit;
        }
    }

    $sock=new sockets();
    $sock->getFrameWork("system.php?install-artica-tgz=yes&filename=".urlencode($filename));
    $array["status"]=true;
    $array["message"]="$filename Success (operation made in background task)";
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

function UPDATE_NOW(){
    $sock=new sockets();
    $sock->getFrameWork("system.php?artica-update=yes");
    $array["status"]=true;
    $array["message"]="Launch Artica update ";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
}

function HOSTS_FILE($param1=null,$param2=null,$param3=null,$param4=null){


    $q=new lib_sqlite("/home/artica/SQLITE/etc_hosts.db");

    if(strtolower($param1)=="search"){
        $hostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
        $tt=explode(".",$hostname);
        $netbiosname=$tt[0];
        $f[]="::1     localhost ip6-localhost ip6-loopback ";
        $f[]="::1     $hostname	$netbiosname";
        $f[]="fe00::0   ip6-localnet ";
        $f[]="ff00::0   ip6-mcastprefix ";
        $f[]="ff02::1   ip6-allnodes ";
        $f[]="ff02::2   ip6-allrouters ";
        $f[]="ff02::3   ip6-allhosts ";
        $f[]="127.0.0.1     $hostname	$netbiosname";




        if($param2==null){$param2="*";}
        $param2="*$param2*";
        $param2=str_replace("**","*",$param2);
        $param2=str_replace("**","*",$param2);
        $param2=str_replace("*","%",$param2);


        $results=$q->QUERY_SQL("SELECT * FROM net_hosts WHERE (ipaddr LIKE '$param2') 
        OR (hostname LIKE '$param2') OR (alias LIKE '$param2') ORDER BY hostname");

        if(count($results)==0){
            foreach ($f as $line ){
                $zmd5=md5($line);
                preg_match("#^(.+?)\s+(.+?)\s+(.*)#",$line,$re);
                $MAIN[$zmd5]['ipaddr']=$re[1];
                $MAIN[$zmd5]['hostname']=$re[2];
                $MAIN[$zmd5]['alias']=$re[3];


            }

            $array["status"]=true;
            $array["message"]="Defaults values in hosts table (nothing found in table)";
            $array["hosts"]=$MAIN;
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),200);
            die();


        }





        foreach ($results as $index=>$ligne){
            $zmd5=$ligne["zmd5"];
            $ipaddr=$ligne['ipaddr'];
            $hostname=$ligne['hostname'];
            $alias=$ligne['alias'];
            $MAIN[$zmd5]['ipaddr']=$ipaddr;
            $MAIN[$zmd5]['hostname']=$hostname;
            $MAIN[$zmd5]['alias']=$alias;
        }

        $array["status"]=true;
        $array["message"]="Find $param2 in hosts table";
        $array["hosts"]=$MAIN;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        die();

    }

    if(strtolower($param1)=="add"){
        $ipaddr=$param2;
        $hostname=$param3;
        $alias=$param4;
        $IPClass=new IP();
        if(!$IPClass->isIPAddress($ipaddr)){
            $array["status"]=false;
            $array["message"]="Wrong IP address \"$ipaddr\"";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),500);
            die();
        }

        if(!preg_match("#^(.+?)\.(.+)#",$hostname)){
            $array["status"]=false;
            $array["message"]="$hostname must be an FQDN not a netbiosname";
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),500);
            die();

        }
        if($alias==null){
            $tp=explode(".",$hostname);
            $alias=$tp[0];
        }
        $md5=md5("$ipaddr$hostname");
        $q->QUERY_SQL("INSERT OR IGNORE INTO net_hosts (`zmd5`,`ipaddr`,`hostname`,`alias`) VALUES ('$md5','$ipaddr','$hostname','$alias')");
        if(!$q->ok){
            $array["status"]=false;
            $array["message"]=$q->mysql_error;
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),500);
            die();
        }

        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?etchosts-build=yes");
        $array["status"]=true;
        $array["message"]="Added $ipaddr $hostname in hosts table";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        return;

    }


    if(strtolower($param1)=="del"){
        $md5=$param2;
        $ligne=$q->mysqli_fetch_array("SELECT * FROM net_hosts WHERE zmd5='$md5'");
        $title="{$ligne["ipaddr"]}: {$ligne["hostname"]}";


        $q->QUERY_SQL("DELETE FROM net_hosts WHERE `zmd5`='$md5'");
        if(!$q->ok){
            $array["status"]=false;
            $array["message"]=$q->mysql_error;
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),500);
            die();
        }

        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?etchosts-build=yes");
        $array["status"]=true;
        $array["message"]="Removed entry $title in hosts table";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        return;

    }


    $array["status"]=false;
    $array["message"]="Could not understand $param1/$param2/$param3";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),400);
    die();

}

function STATISTICS($param1,$param2=null,$param3=null){

    if($param1=="retention"){
        if($param2=="days"){
            if(intval($param3)>0){
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("InfluxAdminRetentionTime",$param3);
                $array["status"]=true;
                $array["message"]="Retention time to  {$param3} days";
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array),200);
                return true;
            }else{
                $InfluxAdminRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminRetentionTime"));
                if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}
                if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$InfluxAdminRetentionTime=5;}
                $array["status"]=true;
                $array["message"]="Retention time to {$InfluxAdminRetentionTime} days";
                $array["VALUE"]=$InfluxAdminRetentionTime;
                $RestAPi=new RestAPi();
                $RestAPi->response(json_encode($array),200);
                return true;
            }
        }
    }


    $array["status"]=false;
    $array["message"]="Unable to understand $param1,$param2,$param3";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),404);

}

function RSYNC_UPDATE($enable,$rsyncserver=null,$rsyncport=0){

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaAutoUpateRsync",$enable);
    if($rsyncserver<>null){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaAutoUpateRsyncServer",$rsyncserver);
    }

    if(intval($rsyncport)>0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaAutoUpateRsyncServer",$rsyncport);

    }
    $ArticaAutoUpateRsync=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsync"));
    $ArticaAutoUpateRsyncServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsyncServer"));
    $ArticaAutoUpateRsyncServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsyncServerPort"));

    $array["status"]=true;
    $array["message"]="Rsync Update Enable=$ArticaAutoUpateRsync ($ArticaAutoUpateRsyncServer:$ArticaAutoUpateRsyncServerPort)";

    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);

}


function REST_ACTIVEDIRECTORY($action,$value=null){

    if($action=="settings"){REST_ACTIVEDIRECTORY_SETTINGS();}

    $array["status"]=false;
    $array["message"]="Unable to understand query <{$action}> <{$value}>";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),404);

}

function ARTICA_VERSION(){
    $datas=@file_get_contents("/usr/share/artica-postfix/VERSION");
    if(trim($datas)==null){$datas="0.00";}
    $CURPATCH=$GLOBALS["CLASS_SOCKETS"]->getFrameWork("artica.php?SPVersion=yes");

    $array["status"]=true;
    $array["message"]="Artica Version $datas Service Pack $CURPATCH";
    $array["version"]=$datas;
    $array["ServicePack"]=$CURPATCH;
    $array["hotfix"]=ARTICA_HOTFIX();
    $array["hostname"]=posix_uname();
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
}
function ARTICA_HOTFIX(){
    $f=explode("\n",@file_get_contents(dirname(__FILE__)."/fw.updates.php"));
    foreach ($f as $line){
        if(preg_match("#HOTFIX.*?\].*?([0-9]+)#",$line,$re)){return $re[1];}
    }
    return 0;
}


function isAuth(){

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRESTFulSystem"))==0){
        $RestAPi=new RestAPi();
        $RestAPi->response("Disabled feature", 407);
        exit;
    }
    $ApiRestKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemRESTFulAPIKey"));
    if(isset($_SERVER["ArticaKey"])){$MyArticaKey=$_SERVER["ArticaKey"];}
    if(isset($_SERVER["HTTP_ARTICAKEY"])){$MyArticaKey=$_SERVER["HTTP_ARTICAKEY"];}
    if($MyArticaKey==null){
        $RestAPi=new RestAPi();
        $RestAPi->response("Authentication Failed (missing header), means you have to use 'ArticaKey: ' in your HTTP request header", 407);
        exit;
    }
    if($MyArticaKey<>$ApiRestKey){
        $RestAPi=new RestAPi();
        $RestAPi->response("Authentication Failed", 401);
        exit;
    }


}

function SWAP_REST($section,$command1=null,$command2=null){

    if($section=="list"){return SWAP_LIST();}
    if($section=="new"){return SWAP_CREATE();}
    if($section=="delete"){return SWAP_DELETE();}
    if($section=="clean"){return SWAP_CLEAN();}

    $array["status"]=false;
    $array["message"]="Unable to understand query <{$section}> <{$command1}>";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),404);
    exit;


}

function SWAP_CREATE(){

    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $DATA=base64_encode(serialize($_POST));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CREATE_NEW_SWAP",$DATA);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("hd.php?create-swap=yes");

    $array["status"]=true;
    $array["message"]="Success sends command to create swap with {$_POST["size"]}MB space";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    exit;
}

function SWAP_DELETE(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $path=$_POST["path"];
    if($path==null){
        $array["status"]=false;
        $array["message"]="Path is null!";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("hd.php?rescan-swap=yes");
    $SWAP_PARTITIONS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SWAP_PARTITIONS"));

    if(!isset($SWAP_PARTITIONS[$_POST["path"]])){
        $array["status"]=false;
        $array["message"]="{$_POST["path"]} Does not exists!";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }
    $TYPE=$SWAP_PARTITIONS[$_POST["path"]]["TYPE"];
    if($TYPE=="partition"){
        $array["status"]=false;
        $array["message"]="{$_POST["path"]} cannot delete a swap partition";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DELETE_SWAP",base64_encode($_POST["path"]));
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("hd.php?remove-swap=yes");

    $array["status"]=true;
    $array["message"]="Success sends command to remove swap {$_POST["path"]}";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    exit;
}

function SWAP_CLEAN(){
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?empty-swap=yes");
    $array["status"]=true;
    $array["message"]="Success sends command to clean swap";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    exit;
}

function SWAP_LIST(){


    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("hd.php?rescan-swap=yes");
    $SWAP_PARTITIONS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SWAP_PARTITIONS"));
    $array["status"]=true;
    $array["message"]="Available SWAP areas";
    $array["SWAP"]=$SWAP_PARTITIONS;
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
}

function REST_WEBCONSOLE($command){

    if($command=="restart"){
        $sock=new sockets();
        $sock->getFrameWork("artica.php?webconsole-restart=yes");
        $array["status"]=true;
        $array["message"]="Restarting Artica Web console";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;
    }

    $array["status"]=false;
    $array["message"]="Unable to understand query <{$command}>";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),404);
    exit;


}

function FEATURES_CACHE_PARAMS($value=null){

    if($value==null){
        $DisableMemCacheSettings=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/DisableMemCacheSettings"));
        $array["status"]=true;
        $array["message"]="Disable caching parameters in memory (GET)";
        $array["value"]=$DisableMemCacheSettings;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);

    }

    if(is_numeric($value)){
        if(!@file_put_contents("/etc/artica-postfix/settings/Daemons/DisableMemCacheSettings",
            intval($_POST["DisableMemCacheSettings"]))){
            $uri="/setinfo/".urlencode(base64_encode("DisableMemCacheSettings")). "/".urlencode(base64_encode($_POST["DisableMemCacheSettings"]));
            $sock=new sockets();
            $sock->REST_API($uri);
        }
        $array["status"]=true;
        $array["message"]="Disable caching parameters in memory (SET)";
        $array["value"]=$value;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);

    }

}

function FEATURES($section,$command1=null,$command2=null){

    if($section=="list"){return FEATURES_LIST();return true;}
    if($section=="install"){return FEATURES_INSTALL($command1);return true;}
    if($section=="uninstall"){return FEATURES_UNINSTALL($command1);return true;}
    if($section=="cache-params"){return FEATURES_CACHE_PARAMS($command1);return true;}

    $array["status"]=false;
    $array["message"]="Unable to understand query <{$section}> <{$command1}>";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),404);
    exit;
}

function FEATURES_UNINSTALL($main_key=null){

    if($main_key==null){
        $array["status"]=false;
        $array["message"]="No product key specified for installation";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }

    $FEATURES_FUNCTION=FEATURES_FUNCTION();
    if(!isset($FEATURES_FUNCTION[$main_key])){
        $array["status"]=false;
        $array["message"]="$main_key product key does not exists";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;

    }

    $FUNCTION=$FEATURES_FUNCTION[$main_key];
    $features=new features();
    $features->json=true;
    $features->CMDLINES=true;
    $zarray=$features->$FUNCTION();


    if(intval($zarray[$main_key]["INSTALLED"]==0)){
        $array["status"]=true;
        $array["message"]="{$zarray[$main_key]["TITLE"]} Already uninstalled";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;

    }

    if(!isset($zarray[$main_key]["FRAMEWORK"])){
        $array["status"]=true;
        $array["message"]="{$zarray[$main_key]["TITLE"]} internal error for framework";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }
    $sock=new sockets();
    $sock->getFrameWork($zarray[$main_key]["FRAMEWORK"]);
    $array["status"]=true;
    $array["message"]="Removing {$zarray[$main_key]["TITLE"]} task launched";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    exit;
}


function FEATURES_INSTALL($main_key=null){

    if($main_key==null){
        $array["status"]=false;
        $array["message"]="No product key specified for installation";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),407);
        exit;
    }

    $FEATURES_FUNCTION=FEATURES_FUNCTION();
    if(!isset($FEATURES_FUNCTION[$main_key])){
        $array["status"]=false;
        $array["message"]="$main_key product key does not exists";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),407);
        exit;

    }

    $FUNCTION=$FEATURES_FUNCTION[$main_key];
    $features=new features();
    $features->json=true;
    $features->CMDLINES=true;
    $zarray=$features->$FUNCTION();



    if(!$zarray[$main_key]["AVAILABLE"]){
        $array["status"]=false;
        $array["message"]="{$zarray[$main_key]["TITLE"]} cannot be installed {$zarray[$main_key]["INFO"]}";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }

    if($zarray[$main_key]["INSTALLED"]==1){
        $array["status"]=true;
        $array["message"]="{$zarray[$main_key]["TITLE"]} Already installed";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;

    }

    if(!isset($zarray[$main_key]["FRAMEWORK"])){
        $array["status"]=true;
        $array["message"]="{$zarray[$main_key]["TITLE"]} internal error for framework";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }
    $sock=new sockets();
    $sock->getFrameWork($zarray[$main_key]["FRAMEWORK"]);
    $array["status"]=true;
    $array["message"]="{$zarray[$main_key]["TITLE"]} installation launched";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    exit;
}

function FEATURES_FUNCTION(){
    include_once(dirname(__FILE__)."/ressources/class.features.inc");
    $class = new ReflectionClass('features');
    $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

    $features=new features();
    $features->json=true;
    foreach ($methods as $class){
        if($class->name=="__construct"){continue;}
        $FUNCTION=$class->name;
        $array=$features->$FUNCTION();
        foreach ($array as $key=>$vals){
            $MAIN[$key]=$FUNCTION;
        }

    }
    return $MAIN;

}

function FEATURES_LIST(){
    include_once(dirname(__FILE__)."/ressources/class.features.inc");
    $class = new ReflectionClass('features');
    $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
    $MAIN=array();
    $features=new features();
    $features->json=true;
    foreach ($methods as $class){
        if($class->name=="__construct"){continue;}
        $FUNCTION=$class->name;
        $array=$features->$FUNCTION();
        foreach ($array as $key=>$vals){
            $MAIN[$key]=$vals;
        }

    }


    $array2["status"]=true;
    $array2["message"]="List of available features";
    $array2["features"]=$MAIN;
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array2),200);
    exit;
}


function REST_ACTIVEDIRECTORY_SETTINGS(){
    $tpl=new template_admin();
    $ipClass=new IP();
    if(strpos($_POST["fullhosname"], ".")==0){
        $array["status"]=false;
        $array["message"]=$tpl->_ENGINE_parse_body("{reject_invalid_hostname} {$_POST["fullhosname"]}");
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }


    $tb=explode(".",$_POST["fullhosname"]);
    if(!isset($_POST["WINDOWS_SERVER_NETBIOSNAME"])){$array["WINDOWS_SERVER_NETBIOSNAME"]=$tb[0];}

    if(!isset($_POST["ADNETIPADDR"])){
        $resolved=gethostbyname($_POST["fullhosname"]);
        if($ipClass->isValid($resolved)){$_POST["ADNETIPADDR"]=$resolved;}else{
            $array["status"]=false;
            $array["message"]=$tpl->javascript_parse_text("{error}: {unable_to_resolve} Active Directory: {$_POST["fullhosname"]}");
            $RestAPi=new RestAPi();
            $RestAPi->response(json_encode($array),500);
            return;
        }
    }else{
        $resolved=$_POST["ADNETIPADDR"];
    }


    if(strpos($_POST["WINDOWS_SERVER_ADMIN"], "@")>0){
        $trx=explode("@",$_POST["WINDOWS_SERVER_ADMIN"]);
        $_POST["WINDOWS_SERVER_ADMIN"]=$trx[0];
        if(!isset($_POST["WINDOWS_DNS_SUFFIX"])){$_POST["WINDOWS_DNS_SUFFIX"]=trim(strtolower($trx[1])); }
    }else{
        if(!isset($_POST["WINDOWS_DNS_SUFFIX"])){
            $tre=explode(".",$_POST["fullhosname"]);
            $hostname=$tre[0];
            unset($tre[0]);
            $_POST["WINDOWS_DNS_SUFFIX"]=@implode(".", $tre);
        }
    }


    $array["LDAP_SERVER"]=$resolved;
    $array["LDAP_DN"]=$_POST["WINDOWS_SERVER_ADMIN"]."@".$_POST["WINDOWS_DNS_SUFFIX"];
    $array["LDAP_PASSWORD"]=$_POST["WINDOWS_SERVER_PASS"];
    $array["LDAP_PORT"]=389;
    foreach ($_POST as $num=>$ligne){$array[$num]=$ligne;}

    $ldap_connection=@ldap_connect($array["LDAP_SERVER"],$array["LDAP_PORT"]);
    if(!$ldap_connection){
        $DIAG[]="{Connection_Failed_to_connect_to_DC} ldap:/{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}";
        if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$DIAG[]="$extended_error";}
        @ldap_close();
        $array["status"]=false;
        $array["message"]=$tpl->_ENGINE_parse_body(@implode("\n", $DIAG));;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        return;
    }

    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
    $bind=ldap_bind($ldap_connection, $array["LDAP_DN"],$array["LDAP_PASSWORD"]);
    if(!$bind){
        SyslogAd("{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]} {$array["LDAP_DN"]} bind failed");
        $DIAG[]="{login_Failed_to_connect_to_DC} {$array["LDAP_SERVER"]} - {$array["LDAP_DN"]}";
        $DIAG[]=ldap_err2str(ldap_errno($ldap_connection));
        if (@ldap_get_option($ldap_connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {$DIAG[]="$extended_error";}
        $array["status"]=false;
        $array["message"]=$tpl->_ENGINE_parse_body(@implode("\n", $DIAG));;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        return;
    }


    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(base64_encode(serialize($array)), "KerbAuthInfos");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthSMBV2", 1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbAuth", 1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WindowsActiveDirectoryKerberos", $_POST["WindowsActiveDirectoryKerberos"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("services.php?kerbauth-progress=yes");

    $array["status"]=true;
    $array["message"]="Active directory connection has been executed in background mode";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);

}
function SyslogAd($text){
    if(!function_exists("openlog")){return true;}
    $f=basename(__FILE__);
    $text="[$f]: $text";
    openlog("activedirectory", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}

function REST_NETWORK_APPLY(){
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("/system/network/reconfigure-restart");
    $array["status"]=true;
    $array["message"]="success";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
}

function REST_HOSTNAME($hostname){
    $tpl=new template_admin();
    $t=explode(".",$hostname);
    if(count($t)==1){
        $array["status"]=false;
        $array["message"]=$tpl->_ENGINE_parse_body("{$_POST["hostname"]}: {not_an_fqdn}");
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }

    $nic=new system_nic();
    $nic->set_hostname($hostname);
    $array["status"]=false;
    $array["message"]="$hostname success";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
}

function REBOOT(){
    $tpl=new template_admin();

    $tpl->squid_admin_mysql(0,"Reboot required by REST API",null,__FILE__,__LINE__);
    $array["status"]=true;
    $array["message"]="Reboot operation required";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);
    $sock=new sockets();
    $sock->getFrameWork("cmd.php?system-reboot=yes");


}

function MONITOR_SERVICES($cmd1=null,$cmd2=null,$cmd3=null,$cmd4=null){
    include_once("/usr/share/artica-postfix/framework/class.monit.inc");

    if($cmd1=="status"){
        $monit_unix=new monit_unix();
        $all_status=$monit_unix->all_status();
        $array["status"]=true;
        $array["message"]="Monitored services by monit";
        $array["results"]=$all_status;
        $array["events"]=$monit_unix->getlogs();
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;
    }
    if($cmd1=="reload"){
        $sock=new sockets();
        $sock->getFrameWork("monit.php?reload=yes");
        $array["status"]=true;
        $array["message"]="Reloaded monit daemon";
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;
    }
    if($cmd1=="emergencies"){
        $zemerg=array();
        $emerg=explode(",","SquidUrgency,SquidUFDBUrgency,MacToUidUrgency,DynamicACLUrgency,SquidSSLUrgency,SizeQuotasCheckerEmergency,LogsWarninStop,StoreIDUrgency,ActiveDirectoryEmergency,SquidUrgencyCaches,SquidMimeEmergency,BasicAuthenticatorEmergency,DisableAnyCache,SquidHotSpotUrgency");
        foreach ($emerg as $token){
            $value=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($token));
            if($value==0){continue;}
            $zemerg[]=$token;
        }

        $array["status"]=true;
        $array["message"]=count($zemerg)." active Emergencies modes";
        $array["count"]=count($zemerg);
        $array["emergencies"]=@implode(",",$zemerg);
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),200);
        exit;

    }

}


function EXTRACT_INFO(){

    $hostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
    if($hostname==null){
        $hostname=$GLOBALS["CLASS_SOCKETS"]->getFrameWork("system.php?hostname-g=yes");
    }
    $Message["FULL_STATUS"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LINUX_INFO_TXT");
    $Message["HOSTNAME"]=$hostname;
    $Message["CPU_PRC"]=0;
    $Message["MEM_PRC"]=0;


    $EnableGlances=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGlances"));
    if($EnableGlances==0) {
        $Message["ERR"]="Glances is not installed";

    }else{
        $tpl=new template_admin();
        $glances_dump=$tpl->glances_dump();
        $cpu=$glances_dump->cpu->total;
        $mem=$glances_dump->mem->percent;
        $mem_total=FormatBytes($glances_dump->mem->total/1024);
        $Message["CPU_PRC"]=$cpu;
        $Message["MEM_PRC"]=$mem;
        $Message["MEM_TOTAL"]=$mem_total;

    }

    $os=new os_system();
    $Message["SYSTEM_LOAD"]=sys_getloadavg();
    $Message["CPU_INFOS"]=$os->cpu_info();
    $Message["MEMORY_INFOS"]=$os->memory();
    $Message["SWAP_SPACE"]=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SWAP_PARTITIONS"));


    $datas=unserialize($GLOBALS["CLASS_SOCKETS"]->getFrameWork("services.php?dmicode=yes"));
    if(is_array($datas)){
        $proc_type=$datas["PROC_TYPE"];
        $MANUFACTURER =$datas["MANUFACTURER"];
        $PRODUCT=$datas["PRODUCT"];
        $CHASSIS=$datas["CHASSIS"];
        $Message["PROC_TYPE"]=$proc_type;
        $Message["MANUFACTURER"]=$MANUFACTURER;
        $Message["PRODUCT"]=$PRODUCT;
        $Message["CHASSIS"]=$CHASSIS;
    }

    $array["status"]=true;
    $array["message"]=$Message;
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);



}

function REST_INTERFACE($ifname){

    if($ifname=="reconfigure"){
        REST_NETWORK_APPLY();
        exit;
    }

    $nics=new system_nic($ifname);
    $nics->NoReboot=false;
    $nics->IPADDR=$_POST["IPADDR"];
    $nics->NETMASK=$_POST['NETMASK'];
    $nics->GATEWAY=$_POST["GATEWAY"];
    $nics->BROADCAST=$_POST["BROADCAST"];
    $nics->metric=$_POST["METRIC"];
    $nics->defaultroute=$_POST["DEFAULT_ROUTE"];
    if(!$nics->SaveNic()){
        $array["status"]=false;
        $array["message"]=$nics->mysql_error;
        $RestAPi=new RestAPi();
        $RestAPi->response(json_encode($array),500);
        exit;
    }
    $array["status"]=true;
    $array["message"]="{$_POST["IPADDR"]}/{$_POST['NETMASK']} GATEWAY {$_POST["GATEWAY"]} success";
    $RestAPi=new RestAPi();
    $RestAPi->response(json_encode($array),200);

}