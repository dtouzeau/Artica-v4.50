<?php
$GLOBALS["HOTSPOT_DEBUG"]=false;
ini_set('session.gc_probability', 1);
ini_set("session.save_handler","memcached");
ini_set("session.save_path","/var/run/memcached.sock");
// Patch 120 // 133
/*Google
 * https://github.com/google/google-api-php-client/releases
 * How to: https://www.codexworld.com/login-with-google-api-using-php/
 */

use PHPMailer\PHPMailer\PHPMailer;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
include_once(dirname(__FILE__)."/ressources/class.wifidog.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
session_save_path('/home/squid/hotspot/sessions');
$GLOBALS["PID"]="000";
if(function_exists("getmypid")){$GLOBALS["PID"]=getmypid();}
$GLOBALS["CACHEDIR"]="/home/artica/hotspot/caches";
$GLOBALS["DEBUG_LEVEL"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotDebug"));


//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($_REQUEST["ruleid"])){$_REQUEST["ruleid"]=1;}

if(isset($_GET["imgload"])){local_imgload();exit;}
if(isset($_GET["css"])){template_css();exit;}
if(isset($_GET["css-99999"])){CSS_99999();exit;}
if(isset($_GET["css-999998"])){CSS_999998();exit;}
if(isset($_GET["local-js"])){local_js();exit;}
if(isset($_POST["ActiveDirectoryLogin"])){ActiveDirectoryLogin();exit;}
if(isset($_POST["smtp-register"])){smtp_register();exit;}
if(isset($_GET["confirm"])){confirm();exit;}
if(isset($_GET["ForceRegister"])){index_register();exit;}
if(isset($_POST["wifi4eu"])){login_success();exit;}


index();



function index_register($error=null){
    $t=time();
    $email=null;
    $myUrl=null;
    $tpl=new template_admin();$sock=new sockets();
    $HotSpotTemplateID=intval($sock->GET_INFO("HotSpotTemplateID"));
    $wifidog_templates=new wifidog_templates($HotSpotTemplateID);
    $info=unserialize(base64_decode($_REQUEST["info"]));
    $MAC=$info["MAC"];
    $KEY=$info["KEY"];
    $MAIN_TITLE=$wifidog_templates->RegisterTitle;
    $MAIN_EXPLAIN=$wifidog_templates->REGISTER_MESSAGE_EXPLAIN;
    $submit=$wifidog_templates->SubmitButton;
    if(isset($_REQUEST["myuri"])){ $myUrl=$_REQUEST["myuri"];}
    $ip=$_REQUEST["ip"];
    $Fstyle=null;
    if(isset($_POST["email"])){$email=$_POST["email"];}
    if(strlen($wifidog_templates->FieldsStyle)>5){$Fstyle=$wifidog_templates->FieldsStyle;}

    $content[]="<div class=title2>".$wifidog_templates->char($MAIN_TITLE)."</div>";
    $content[]="<p>".$wifidog_templates->char($MAIN_EXPLAIN)."</p>";
    if($error<>null){$content[]="<p style='$wifidog_templates->ERROR_STYLE' >$error</p>";}
    $content[]="<div style='width:98%' class=form id='form-$t'>";
    $content[]="<form name='register-$t' method='post' action='hotspot.php' 
class='form-horizontal' style='padding:left:15px'>";

    $formHiddens[]="<input type='hidden' id='template_id' name='template_id' value='$HotSpotTemplateID'>";
    $formHiddens[]="<input type='hidden' id='myuri' name='myuri' value='$myUrl'>";
    $formHiddens[]="<input type='hidden' id='url' name='url' value='{$_REQUEST["url"]}'>";
    $formHiddens[]="<input type='hidden' id='MAC' name='MAC' value='$MAC'>";
    $formHiddens[]="<input type='hidden' id='KEY' name='KEY' value='$KEY'>";
    $formHiddens[]="<input type='hidden' id='ipaddr' name='ipaddr' value='$ip'>";
    $formHiddens[]="<input type='hidden' id='ip' name='ip' value='$ip'>";
    $formHiddens[]="<input type='hidden' id='info' name='info' value='{$_REQUEST["info"]}'>";

    $content[]="<input type='hidden' id='smtp-register' name='smtp-register' value='yes'>";
    $content[]=@implode("\n",$formHiddens);
    $content[]="<table style='width:100%'>";
    $content[]="<tr style='height:20px'>";
    $content[]="<td class=legend>{$wifidog_templates->LabelEmail}:</td>";
    $content[]="<td><input style='width:80%;$Fstyle' type=\"text\" placeholder=\"\" id=\"email\" name=\"email\" value='{$email}'></td>";
    $content[]="</tr>";
    $content[]="<tr><td colspan=2>&nbsp;</td></tr>";
    $content[]="<td colspan=2 align='right' class=ButtonCell>".build_button($submit, "document.forms['register-$t'].submit();");
    $content[]="</td>";
    $content[]="</tr>";
    $content[]="</table>";
    $content[]="</form></div>";


    $html=$wifidog_templates->build(@implode("\n", $content));
    $html=$tpl->_ENGINE_parse_body($html);
    echo $html;


}

function MicroHotSpotisWindowsAUTH():bool{

    $WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));

    if($WindowsActiveDirectoryKerberos==1){
        return true;
    }

    $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
    if($UseNativeKerberosAuth==1){return true;}


    $LockActiveDirectoryToKerberos= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
    if($LockActiveDirectoryToKerberos==1){return true;}


    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){return true;}

    $EnableFakeAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFakeAuth"));
    if($EnableFakeAuth==1){return true;}

    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    if($EnableKerbAuth==1){return true;}

    return true;
}

function index($error=null){
    $t=time();
    $tpl=new template_admin();
    $sock=new sockets();
    $submit=$tpl->_ENGINE_parse_body("{submit}");
    $email=null;$username=null;$password=null;
    $HotSpotTemplateID=intval($sock->GET_INFO("HotSpotTemplateID"));
    $HotSpotEntrepriseTemplate=1;
    $HotSpotWIFI4EU_ENABLE=intval($sock->GET_INFO("HotSpotWIFI4EU_ENABLE"));
    if($HotSpotEntrepriseTemplate==1){$HotSpotTemplateID=99999;}
    if($HotSpotWIFI4EU_ENABLE==1){$HotSpotTemplateID=999998;}
    WLOG("Default HotSpot template ID.$HotSpotTemplateID",true);
    if(isset($_POST["template_id"])){$HotSpotTemplateID=$_POST["template_id"];}
    $MUST_AUTH=false;
    $ONLYVOUCHER=true;
    $ONLYAD=true;
    $port_text=null;
    $Fstyle=null;
    $ip=$_REQUEST["ip"];
    $host=$_SERVER["HTTP_HOST"];
    $port=$_SERVER["SERVER_PORT"];
    if(($port<>80 ) OR ($port<>443)){$port_text=":$port";}
    $proto="http";
    if(isset($_SERVER["HTTPS"])){if($_SERVER["HTTPS"]=="on"){$proto="https";}}
    $register_button=null;
    $myUrl="$proto://$host{$port_text}/hotspot.php";
    if(isset($_POST["myuri"])){$myUrl=$_POST["myuri"];}
    $HotSpotLostLandingPage=trim($sock->GET_INFO("HotSpotLostLandingPage"));
    if($HotSpotLostLandingPage==null){$HotSpotLostLandingPage="http://www.msftncsi.com/ncsi.txt";}

    $wifidog_templates=new wifidog_templates($HotSpotTemplateID);
    $content=array();
    $HotSpotAutoLogin=intval($sock->GET_INFO("HotSpotAutoLogin"));
    $HotSpotAuthentAD=trim($sock->GET_INFO("HotSpotAuthentAD"));
    $HotSpotAuthentLocalLDAP=intval($sock->GET_INFO("HotSpotAuthentLocalLDAP"));
    $EnableOpenLDAP=intval($sock->GET_INFO("EnableOpenLDAP"));
    $HotSpotAuthentVoucher=intval($sock->GET_INFO("HotSpotAuthentVoucher"));
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
    $EnableKerbNTLM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbNTLM"));

    if(MicroHotSpotisWindowsAUTH()){
        $HotSpotAuthentAD=1;
        $HotSpotAuthentLocalLDAP=0;
        if($HotSpotAuthentVoucher==0 AND $HotSpotAutoLogin==0 ){$ONLYAD=true;}
    }

    if($GLOBALS["VERBOSE"]){
        VERBOSE("HotSpotAuthentAD = $HotSpotAuthentAD; LockActiveDirectoryToKerberos = $LockActiveDirectoryToKerberos, EnableKerbNTLM=$EnableKerbNTLM",__LINE__);
    }


    if($EnableOpenLDAP==0){$HotSpotAuthentLocalLDAP=0;}

    if($HotSpotAuthentAD==1){$MUST_AUTH=true;$ONLYVOUCHER=false;}
    if($HotSpotAuthentLocalLDAP==1){$MUST_AUTH=true;$ONLYVOUCHER=false;$ONLYAD=false;}
    if($HotSpotAuthentVoucher==1){$MUST_AUTH=true;$ONLYAD=false;}
    if($HotSpotAuthentVoucher==0){$ONLYVOUCHER=false;}
    if(isset($_REQUEST["email"])){$_GET["user"]=$_REQUEST["email"];}
    if($HotSpotAuthentAD==0){$ONLYAD=false;}

    $BuildUrlForceRegister="/".basename(__FILE__)."?ForceRegister=yes&info={$_REQUEST["info"]}&ip=".urlencode($ip)."&user=".urlencode($_GET["user"])."&url=".urlencode($_REQUEST["url"])."&myuri=".urlencode($myUrl);


    if(!$MUST_AUTH){
        $_GET["myuri"]=$myUrl;
        WLOG("No auth system as been defined, use register function...",true);
        index_register();
        die();
    }


    //$info=unserialize(base64_decode($_REQUEST["info"]));
    $info=json_decode(base64_decode($_REQUEST["info"]),TRUE);
    $MAC=$info["MAC"];
    $KEY=$info["KEY"];

    if($GLOBALS["VERBOSE"]){
        foreach ($info as $key=>$val){
            VERBOSE("INFO: $key=$val",__LINE__);
        }
    }


    if(isset($info["EMAIL"])){$email=$info["EMAIL"];}
    if(isset($_POST["KEY"])){$KEY=$_POST["KEY"];}
    if(isset($_POST["MAC"])){$MAC=$_POST["MAC"];}
    if(isset($_POST["ipaddr"])){$ip=$_POST["ipaddr"];}
    if(isset($_POST["email"])){$email=$_POST["email"];}
    if(isset($_POST["username"])){$username=$_POST["username"];}
    if(isset($_POST["password"])){$password=$_POST["password"];}

    $MAIN_TITLE=$wifidog_templates->RegisterTitle;
    $MAIN_EXPLAIN=$wifidog_templates->REGISTER_MESSAGE_EXPLAIN;
    if($GLOBALS["VERBOSE"]){ VERBOSE("MUST_AUTH: $MUST_AUTH",__LINE__);}
    if($MUST_AUTH){
        $MAIN_TITLE=$wifidog_templates->MainTitle;
        $MAIN_EXPLAIN=$wifidog_templates->WelcomeMessage;
    }else{
        $content[]="<! -- MUST AUTH: FALSE -->";
        if($HotSpotAutoLogin==0){
            $content[]="<div class=title2>".$wifidog_templates->char($wifidog_templates->MainTitle)."</div>";
            $content[]="<p>No authentication method has been defined, please contact your administrator</p>";
            $html=$wifidog_templates->build(@implode("\n", $content));
            $html=$tpl->_ENGINE_parse_body($html);
            echo $html;
            return true;
        }
    }



    if($KEY==null){
        if($GLOBALS["VERBOSE"]){ VERBOSE("KEY: NULL STOP HERE",__LINE__);}
        $wifidog_templates->RedirectPage=$HotSpotLostLandingPage;
        $ArticaSplashHotSpotRedirectText=$wifidog_templates->ArticaSplashHotSpotRedirectText."<br>$HotSpotLostLandingPage";
        $content[]="<div class=title2>".$wifidog_templates->char($wifidog_templates->RegisterTitle)."</div>";
        $content[]="<p>$ArticaSplashHotSpotRedirectText</p>";
        $html=$wifidog_templates->build(@implode("\n", $content));
        $html=$tpl->_ENGINE_parse_body($html);
        echo $html;
        return true;
    }




    if($error<>null){$content[]="<p style='$wifidog_templates->ERROR_STYLE' >$error</p>";}
    if(strlen($wifidog_templates->FieldsStyle)>5){$Fstyle=$wifidog_templates->FieldsStyle;}



    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM `sessions` WHERE `sessionkey`='$KEY'");
    WLOG("[$KEY]: $HotSpotAutoLogin --> 'YES', Session ID from sessions table ={$ligne["ID"]}",true);

    $label_username=$wifidog_templates->LabelUsername;
    $label_password=$wifidog_templates->LabelPassword;

    if($ONLYVOUCHER){
        $label_username=$wifidog_templates->LabelVoucher;
    }

    if($ONLYAD){
        $label_username=$wifidog_templates->DomainAccount;
        $MAIN_EXPLAIN=$wifidog_templates->WelcomeMessageActiveDirectory;
    }


    $content[]="<div class=title2>".$wifidog_templates->char($MAIN_TITLE)."</div>";
    $content[]="<p>".$wifidog_templates->char($MAIN_EXPLAIN)."</p>";
    $content[]="<div style='width:98%' class=form id='form-$t'>";
    $content[]="<form name='register-$t' method='post' action='hotspot.php' 
class='form-horizontal' style='padding:left:15px'>";


    $formHiddens[]="<input type='hidden' id='template_id' name='template_id' value='$HotSpotTemplateID'>";
    $formHiddens[]="<input type='hidden' id='myuri' name='myuri' value='$myUrl'>";
    $formHiddens[]="<input type='hidden' id='url' name='url' value='{$_REQUEST["url"]}'>";
    $formHiddens[]="<input type='hidden' id='MAC' name='MAC' value='$MAC'>";
    $formHiddens[]="<input type='hidden' id='KEY' name='KEY' value='$KEY'>";
    $formHiddens[]="<input type='hidden' id='ipaddr' name='ipaddr' value='$ip'>";

    if(isset($_GET["ForceRegister"])){$HotSpotAutoLogin=1;$HotSpotAuthentAD=0;}
    if($GLOBALS["VERBOSE"]){VERBOSE("HotSpotAutoLogin=$HotSpotAutoLogin", __LINE__);}
    if($GLOBALS["VERBOSE"]){VERBOSE("HotSpotAuthentAD=$HotSpotAuthentAD", __LINE__);}

    if( $MUST_AUTH ){
        if($HotSpotTemplateID<>99999) {
            VERBOSE("BUILD USERNAME AND PASSWORD FORM", __LINE__);
            $content[] = "<input type='hidden' id='ActiveDirectoryLogin' name='ActiveDirectoryLogin' value='yes'>";
            $content[] = @implode("\n", $formHiddens);
            $content[] = "<table style='width:100%'>";
            $content[] = "<tr style='height:20px'>";
            $content[] = "<td class=legend>{$label_username}:</td>";
            $content[] = "<td><input style='width:80%;$Fstyle' type=\"text\" placeholder=\"\" id=\"username\" name=\"username\" value='{$username}'></td>";
            $content[] = "</tr>";
            $content[] = "<tr style='height:20px'>";
            $content[] = "<td class=legend>{$label_password}:</td>";
            $content[] = "<td><input style='width:80%;$Fstyle' type=\"password\" placeholder=\"\" id=\"password\" name=\"password\" value='{$password}'></td>";
            $content[] = "</tr>";
            $content[] = "<tr><td colspan=2>&nbsp;</td></tr>";
            if ($HotSpotAutoLogin == 1) {
                $register_button = build_button("{register}", "javascript:document.location.href='$BuildUrlForceRegister'") . "&nbsp;&nbsp;";

            }
            $content[] = "<td colspan=2 align='right' class=ButtonCell>$register_button" . build_button($submit, "document.forms['register-$t'].submit();");
            $content[] = "</td>";
            $content[] = "</tr>";
            $content[] = "</table>";
        }

        if($HotSpotTemplateID==99999){
            $formHiddens[]="<input type='hidden' id='EnterpriseTemplate' name='EnterpriseTemplate' value='YES'>";
            $wifidog_templates->formHiddens=$formHiddens;
            $wifidog_templates->HotSpotAutoLogin=$HotSpotAutoLogin;
            if($GLOBALS["VERBOSE"]){VERBOSE("MainExplain [$MAIN_EXPLAIN]", __LINE__);}
            $wifidog_templates->MainExplain=$wifidog_templates->char($MAIN_EXPLAIN);
            $wifidog_templates->ErrorMessage=$error;
            $wifidog_templates->BuildUrlForceRegister=$BuildUrlForceRegister;
            $content=$wifidog_templates->enterprise_login_form();
        }

    }

    $content[]="</form></div>";
    if($HotSpotTemplateID==999998){
        $content=array();
        $content[]="<form method='post' action='hotspot.php' name='wifi4euform-$t' id='wifi4euform'>";
        $formHiddens[]="<input type='hidden' id='wifi4eu' name='wifi4eu' value='YES'>";
        $content[] = @implode("\n", $formHiddens);

        $content[]="</form>";

    }


    $html=$wifidog_templates->build(@implode("\n", $content));

    if($GLOBALS["VERBOSE"]){VERBOSE("Build Template of ".strlen($html)." bytes", __LINE__);}
    $html=$tpl->_ENGINE_parse_body($html);
    echo $html;
}

function build_button($text,$action){


    $jsva="onclick=\"$action;\"";

    if(preg_match("#^javascript:(.+)#", $action,$re)){
        $jsva="onclick=\"{$re[1]}\"";
    }

    $f[]="<a data-loading-text=\"{loading}...\"";
    $f[]="style=\"text-transform:capitalize\"";
    $f[]="class=\"Button2014 Button2014-success Button2014-lg\"";
    $f[]="id=\"".time()."\"";
    $f[]="$jsva";
    $f[]="href=\"javascript:Blurz()\">&laquo;&nbsp;$text&nbsp;&raquo;</a>";
    return  @implode(" ", $f);

}

function login_success_voucher($ID){
    include_once(dirname(__FILE__)."/ressources/class.memcached.inc");
    $sock=new sockets();
    $username=$_POST["username"];
    $sock=new sockets();
    $HotSpotAuthenticateEach=intval($sock->GET_INFO("HotSpotAuthenticateEach"));
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM vouchers WHERE ID=$ID");
    $ttl=intval($ligne["ttl"]);
    $expire=intval($ligne["expire"]);

    if($expire==0){
        $ttlmn=$ttl*60;
        $ttls=$ttlmn*60;
        $expire=time()+$ttls;
        $q->QUERY_SQL("UPDATE vouchers SET expire=$expire WHERE ID=$ID");
    }

    $removeaccount=0;
    $disableaccount=0;

    if($HotSpotAuthenticateEach>0){
        $HotSpotAuthenticateEachSeconds=$HotSpotAuthenticateEach*60;
        $removeaccount=time()+$HotSpotAuthenticateEachSeconds;
        WLOG("Voucher $username ({$_POST["MAC"]}/{$_POST["ipaddr"]}) Create a session for {$HotSpotAuthenticateEachSeconds}s more Session end at:".date("Y-m-d H:i:s"));
    }

    if($removeaccount==0){
        $removeaccount=$expire;
    }

    if($_POST["MAC"]<>null) {
        $q->QUERY_SQL("DELETE FROM sessions WHERE macaddress='{$_POST["MAC"]}'");
    }
    $q->QUERY_SQL("DELETE FROM sessions WHERE ipaddr='{$_POST["ipaddr"]}'");

    $KEY="voucher$ID";
    $SQLAR["autocreate"]=10;
    $SQLAR["enabled"]=1;
    $SQLAR["sessionkey"]=$KEY;
    $SQLAR["removeaccount"]=$removeaccount;
    $SQLAR["disableaccount"]=0;
    $SQLAR["macaddress"]=$_POST["MAC"];
    $SQLAR["ipaddr"]=$_POST["ipaddr"];
    $SQLAR["username"]=$username;
    $SQLAR["created"]=time();
    $SQLAR["sourceurl"]=base64_encode($_POST["url"]);


    foreach ($SQLAR as $key=>$val){
        $sqladdF[]="`$key`";
        $sqladdV[]="'$val'";
    }

    $addsql="INSERT INTO `sessions` (".@implode(",", $sqladdF).") VALUES (".@implode(",", $sqladdV).")";

    $q->QUERY_SQL($addsql);
    if(!$q->ok){
        WLOG("CONFIRM: FATAL $q->mysql_error");
        ErrorPage("{mysql_error}",$_POST["template_id"],$_POST["url"]);
        return;
    }

    $memcached=new lib_memcached();
    if($_POST["MAC"]<>null) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM sessions WHERE sessionkey='{$_POST["MAC"]}'");
        $memcached->saveKey("MICROHOTSPOT:{$_POST["MAC"]}", $ligne, 300);
    }
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sessions WHERE sessionkey='{$_POST["ipaddr"]}'");
    $memcached->saveKey("MICROHOTSPOT:{$_POST["ipaddr"]}",$ligne, 300);


    WLOG("CONFIRM: Voucher: Success login {$_POST["MAC"]} {$_POST["ipaddr"]} as $username");
    smtp_register_success();

}


function login_success(){
    include_once(dirname(__FILE__)."/ressources/class.memcached.inc");
    $sock=new sockets();
    $username=$_POST["username"];
    $wifi4eutoken=intval($sock->GET_INFO("HotSpotWIFI4EU_ENABLE"));
    if($wifi4eutoken==1){
        $username=$_POST['ipaddr'];
    }
    $HotSpotDisableAccountTime=intval($sock->GET_INFO("HotSpotDisableAccountTime"));
    $HotSpotRemoveAccountTime=intval($sock->GET_INFO("HotSpotRemoveAccountTime"));
    $HotSpotAuthenticateEach=intval($sock->GET_INFO("HotSpotAuthenticateEach"));

    if($HotSpotAuthenticateEach==0){$HotSpotAuthenticateEach=99999999;}
    $HotSpotAuthenticateEachSeconds=$HotSpotAuthenticateEach*60;

    $removeaccount=0;
    $disableaccount=0;
    $MyTime=time();
    if($HotSpotRemoveAccountTime>0){
        $HotSpotRemoveAccountTimeSec=$HotSpotRemoveAccountTime*60;
        $removeaccount=$MyTime+$HotSpotRemoveAccountTimeSec;

    }
    if($HotSpotDisableAccountTime>0){
        $HotSpotDisableAccountTimeSec=$HotSpotDisableAccountTime*60;
        $disableaccount=$MyTime+$HotSpotDisableAccountTimeSec;
    }

    $HotSpotNextAuth=time()+$HotSpotAuthenticateEachSeconds;
    $q=new lib_sqlite("/home/squid/hotspot/database.db");

    if($_POST["MAC"]<>null) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM sessions WHERE macaddress='{$_POST["MAC"]}'");
        if ($ligne["sessionkey"] <> null) {
            $removeaccount = $ligne["removeaccount"];
            $disableaccount = $ligne["disableaccount"];
            if(time()>$disableaccount){
                index("{your_account_is_disabled}");
                return;
            }
        }
    }else{
        $ligne = $q->mysqli_fetch_array("SELECT * FROM sessions WHERE ipaddr='{$_POST["ipaddr"]}'");
        if ($ligne["sessionkey"] <> null) {
            $removeaccount = $ligne["removeaccount"];
            $disableaccount = $ligne["disableaccount"];
            if(time()>$disableaccount){
                index("{your_account_is_disabled}");
                return;
            }
        }
    }


    $KEY=$_POST["KEY"];
    $SQLAR["autocreate"]=0;
    $SQLAR["enabled"]=1;
    $SQLAR["sessionkey"]=$KEY;
    $SQLAR["removeaccount"]=$removeaccount;
    $SQLAR["disableaccount"]=$disableaccount;
    $SQLAR["macaddress"]=$_POST["MAC"];
    $SQLAR["ipaddr"]=$_POST["ipaddr"];
    $SQLAR["username"]=$username;
    $SQLAR["created"]=time();
    $SQLAR["sourceurl"]=base64_encode($_POST["url"]);

    foreach ($SQLAR as $key=>$val){
        $sqladdF[]="`$key`";
        $sqladdV[]="'$val'";
        $sqlED[]="`$key`='$val'";
    }




    if($_POST["MAC"]<>null){$q->QUERY_SQL("DELETE FROM sessions WHERE macaddress='{$_POST["MAC"]}'");}

    $q->QUERY_SQL("DELETE FROM sessions WHERE ipaddr='{$_POST["ipaddr"]}'");
    $addsql="INSERT INTO `sessions` (".@implode(",", $sqladdF).") VALUES (".@implode(",", $sqladdV).")";

    $q->QUERY_SQL($addsql);
    if(!$q->ok){
        WLOG("CONFIRM: FATAL $q->mysql_error");
        ErrorPage("{mysql_error}",$_POST["template_id"],$_POST["url"]);
        return;
    }



    $memcached=new lib_memcached();
    if($_POST["MAC"]<>null) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM sessions WHERE sessionkey='{$_POST["MAC"]}'");
        $memcached->saveKey("MICROHOTSPOT:{$_POST["MAC"]}", $ligne, $HotSpotNextAuth);
    }
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sessions WHERE sessionkey='{$_POST["ipaddr"]}'");
    $memcached->saveKey("MICROHOTSPOT:{$_POST["ipaddr"]}",$ligne, $HotSpotNextAuth);


    WLOG("CONFIRM: Success login {$_POST["MAC"]} {$_POST["ipaddr"]} as $username");
    smtp_register_success();

}

function ActiveDirectoryLogin(){
    include_once(dirname(__FILE__)."/ressources/class.artica-logon.inc");
    $sock=new sockets();
    $username=$_POST["username"];
    $password=$_POST["password"];

    $HotSpotAuthentAD=trim($sock->GET_INFO("HotSpotAuthentAD"));
    $HotSpotAuthentLocalLDAP=intval($sock->GET_INFO("HotSpotAuthentLocalLDAP"));
    $EnableOpenLDAP=intval($sock->GET_INFO("EnableOpenLDAP"));
    $HotSpotAuthentVoucher=intval($sock->GET_INFO("HotSpotAuthentVoucher"));
    if($EnableOpenLDAP==0){$HotSpotAuthentLocalLDAP=0;}


    if($HotSpotAuthentVoucher==1){
        $q=new lib_sqlite("/home/squid/hotspot/database.db");
        $ligne=$q->mysqli_fetch_array("SELECT ID FROM vouchers WHERE member='$username' AND password='$password'");
        if(intval($ligne["ID"])>0){
            $expire=intval($ligne["expire"]);
            if($expire>0){
                if($expire>time()){
                    WLOG("Voucher: logon refused for $username, session expired");
                    index("{session_expired}");
                    return false;
                }
            }
            login_success_voucher($ligne['ID']);
            return true;
        }


    }


    $login=new artica_logon($username,$password);
    $login->AsHotSpot=true;

    if($HotSpotAuthentLocalLDAP==1){
        if($login->OpenLdap($username,$password)){
            login_success();
            return true;
        }

    }
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));


    if($LockActiveDirectoryToKerberos==1 OR $EnableKerbAuth==1){
        $HotSpotAuthentAD=1;
    }

    if($HotSpotAuthentAD==1){
        $IS_AD_AUTHENTICATED=false;
        $LOGIN_SUCCESS=false;
        $HotSpotLimitCountDeGroups=0;
        $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
        $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
        $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
        if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
        $HotSpotLimitAdGroups=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotLimitAdGroups"));
        foreach ($HotSpotLimitAdGroups as $DN=>$NONE){if($DN==null){continue;}$HotSpotLimitCountDeGroups++;}
        if($UseNativeKerberosAuth==1){$IS_AD_AUTHENTICATED=true;}
        if($EnableKerbAuth==1){$IS_AD_AUTHENTICATED=true;}
        if($LockActiveDirectoryToKerberos==1){$IS_AD_AUTHENTICATED=true;}
        if($IS_AD_AUTHENTICATED){WLOG("Active Directory main connection for $username Success");if($login->ActiveDirectoryMain()){
            $UserDN=$login->ACTIVE_DIRECTORY_DN;
            $LOGIN_SUCCESS=true;}else{WLOG("Active Directory Linked connection for $username Failed");}}
        if(!$LOGIN_SUCCESS){if($login->ActiveDirectoryLinked()) {
            $UserDN=$login->ACTIVE_DIRECTORY_DN;
            WLOG("Active Directory Linked connection for $username Success");$LOGIN_SUCCESS=true;}else{WLOG("Active Directory Linked connection for $username Failed");}}

        if(!$LOGIN_SUCCESS){
            index("{error_wrong_credentials}");
            return false;
        }
        WLOG("Active Directory DN $UserDN",true);
        if($HotSpotLimitCountDeGroups>0){
            $adClass=new external_ad_search();
            foreach ($HotSpotLimitAdGroups as $DN=>$NONE){
                //WLOG("BREAKPOINT 0 DN IS $DN AND USERDND is ].",true);
                if($DN==null){continue;}
                $settings=$adClass->FindParametersByDN($DN);
                if(!isset($settings["LDAP_SERVER"])){
                    WLOG("ERROR, $DN: Unable to find the connection ID");
                    continue;
                }
                $settingsEncoded=base64_encode(serialize($settings));
                $ad=new external_ad_search($settingsEncoded);
                $USERS=$ad->HashUsersFromGroupDN($DN);

//                $k = json_encode($USERS);
//                WLOG("BREAKPOINT 1 IS $k",true);
//                WLOG("BREAKPOINT 2 IS {$USERS[$UserDN]}.",true);
//                WLOG("BREAKPOINT 3 SETTINGS ARE $settingsEncoded.",true);
                $GROUPSOF = $ad->GroupsOfMember($UserDN);
                $g = json_encode($GROUPSOF);
                //WLOG("BREAKPOINT 4 GROUPS OF $g.",true);
                foreach ($GROUPSOF as $groups => $group){
                    //WLOG("BREAKPOINT 5 GROUPS OF $groups.",true);
                    if(isset($HotSpotLimitAdGroups[$groups])){
                        WLOG("SUCCESS, $DN is listed in Active Directory $DN group",true);
                        login_success();
                        return true;

                    }
                }


            }
            WLOG("ERROR, $DN is not listed in any Active Directory groups.",true);
            index("{wrong_privileges}");
            return false;

        }

        login_success();
        return true;
    }
}








function smtp_register(){

    require_once(dirname(__FILE__)."/ressources/externals/PHPMailer/PHPMailer.inc");
    require_once(dirname(__FILE__)."/ressources/externals/PHPMailer/SMTP.inc");
    require_once(dirname(__FILE__)."/ressources/externals/PHPMailer/Exception.inc");
    $sock=new sockets();
    $HotSpotAutoSMTPSrv=trim($sock->GET_INFO("HotSpotAutoSMTPSrv"));
    $HotSpotAutoSMTPPort=intval($sock->GET_INFO("HotSpotAutoSMTPPort"));
    $HotSpotAutoSMTPFrom=trim($sock->GET_INFO("HotSpotAutoSMTPFrom"));
    $HotSpotAutoSMTPUser=trim($sock->GET_INFO("HotSpotAutoSMTPUser"));
    $HotSpotAutoSMTPPass=trim($sock->GET_INFO("HotSpotAutoSMTPPass"));
    $HotSpotAutoSMTPTLS=intval($sock->GET_INFO("HotSpotAutoSMTPTLS"));
    $HotSpotAutoSMTPSSL=intval($sock->GET_INFO("HotSpotAutoSMTPSSL"));
    $HotSpotBindInterface=trim($sock->GET_INFO("HotSpotBindInterface"));
    $HotSpotAutoLoginMaxTime=intval($sock->GET_INFO("HotSpotAutoLoginMaxTime"));
    if($HotSpotAutoLoginMaxTime==0){$HotSpotAutoLoginMaxTime=5;}
    if($HotSpotAutoSMTPPort==0){$HotSpotAutoSMTPPort=25;}
    if($HotSpotAutoSMTPFrom==null){$HotSpotAutoSMTPFrom="root@localhost.local";}
    $KEY=$_POST["KEY"];

    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $email=$_POST["email"];
    $template_id=$_POST["template_id"];
    $url=$_POST["url"];
    $myuri=$_POST["myuri"];
    $ipaddr=$_POST["ipaddr"];
    $tableid=0;

    if($email==null){
        $wifidog_templates=new wifidog_templates($template_id);
        index($wifidog_templates->ErrorInvalidMail);
        return;
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $wifidog_templates=new wifidog_templates($template_id);
        index($wifidog_templates->ErrorInvalidMail);
        return;
    }


    $ligne=$q->mysqli_fetch_array("SELECT * FROM `sessions` WHERE `sessionkey`='$KEY'");
    WLOG("[$KEY]: is session exists --> '{$ligne["ID"]}'",true);
    if(intval($ligne["ID"])>0){
        $tableid=$ligne["ID"];
        if($ligne["enabled"]==0){
            WLOG("[$KEY]: enabled == 0 -> Account is disabled",true);
            index("{your_account_is_disabled}");
            return;
        }
        if($ligne["autocreate"]==2){
            index("{your_account_is_already_confirmed}");
            return;
        }

    }


    $mail = new PHPMailer();
    $wifidog_templates=new wifidog_templates($template_id);
    $HotSpotAutoLoginMaxTimeSeconds=$HotSpotAutoLoginMaxTime*60;

    $MyTime=time();
    $removeaccount=$MyTime+intval($HotSpotAutoLoginMaxTimeSeconds);


    WLOG("[$KEY]: Register for $email, directly remove account if not confirmed in ".date("Y-m-d H:i:s",$removeaccount) ." +{$HotSpotAutoLoginMaxTimeSeconds}s");

    $SQLAR["created"]=time();
    $SQLAR["autocreate"]=1;
    $SQLAR["enabled"]=1;
    $SQLAR["sessionkey"]=$KEY;
    $SQLAR["removeaccount"]=$removeaccount;
    $SQLAR["macaddress"]=$_POST["MAC"];
    $SQLAR["ipaddr"]=$_POST["ipaddr"];
    $SQLAR["username"]=$email;
    $SQLAR["sourceurl"]=base64_encode($url);

    foreach ($SQLAR as $key=>$val){
        $sqladdF[]="`$key`";
        $sqladdV[]="'$val'";
        $sqlED[]="`$key`='$val'";
    }

    if(!$q->FIELD_EXISTS("sessions","created")){
        $q->QUERY_SQL("ALTER TABLE sessions add created INTEGER");
    }


    $addsql="INSERT INTO `sessions` (".@implode(",", $sqladdF).") VALUES (".@implode(",", $sqladdV).")";
    $sqled="UPDATE `sessions` SET ".@implode(",", $sqlED)." WHERE ID='$tableid'";


    if($tableid>0){
        $q->QUERY_SQL($sqled);
    }else{
        $q->QUERY_SQL($addsql);
        $tableid=$q->last_id;
    }

    if($tableid==0){
        index("{MYSQL_ERROR} (no id) $q->mysql_error");
        return;
    }

    $MAIN["register_time"]=time();
    $MAIN["URL"]=$_REQUEST["url"];
    $MAIN["template_id"]=$template_id;
    $MAIN["username"]=$email;
    $MAIN["macaddress"]=$_POST["MAC"];
    $MAIN["ipaddr"]=$ipaddr;
    $MAIN["ID"]=$tableid;


    $confirm=base64_encode(serialize($MAIN));

    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    if($GLOBALS["DEBUG_LEVEL"]==1){$mail->SMTPDebug=3;}
    $mail->setFrom($HotSpotAutoSMTPFrom, "HotSpot System");
    $mail->addReplyTo($HotSpotAutoSMTPFrom, "HotSpot System");
    $mail->addAddress($email, $email);
    $mail->Subject = $wifidog_templates->REGISTER_SUBJECT;
    $mail->Body=$wifidog_templates->REGISTER_MESSAGE."\n$myuri?confirm=$confirm";
    //$mail->AltBody = 'This is a plain-text message body';
    $mail->Host = $HotSpotAutoSMTPSrv;
    $mail->Port = $HotSpotAutoSMTPPort;
    $SMTPOptions=array();

    if(strlen($HotSpotAutoSMTPUser)>3){
        $mail->SMTPAuth = true;
        $mail->SMTPAutoTLS=false;
        $mail->Username = $HotSpotAutoSMTPUser;
        $mail->Password = $HotSpotAutoSMTPPass;
    }

    $bindText='all';
    if($HotSpotBindInterface<>null){
        $nicz=new system_nic($HotSpotBindInterface);
        $SMTPOptions["socket"]["bindto"]=$nicz->IPADDR;
        $bindText=$nicz->IPADDR;
    }

    $mail->SMTPOptions = $SMTPOptions;
    $mail->Debugoutput = function($str, $level){ WLOG(trim($str));};

    //send the message, check for errors

    WLOG("[$KEY]: Sending message to $email, from $HotSpotAutoSMTPFrom/$bindText $HotSpotAutoSMTPSrv:$HotSpotAutoSMTPPort");

    if (!$mail->send()) {
        WLOG("[$KEY]: Sending message to $email Failed.");
        $q->QUERY_SQL("DELETE FROM `sessions` WHERE ID=$tableid");
        index_register("Mailer Error !!!");
        return;

    } else {
        WLOG("[$KEY]: Sending message to $email Success");
        smtp_register_success();
    }

}

function confirm(){
    $sock=new sockets();

    $HotSpotDisableAccountTime=intval($sock->GET_INFO("HotSpotDisableAccountTime"));
    $HotSpotRemoveAccountTime=intval($sock->GET_INFO("HotSpotRemoveAccountTime"));
    $HotSpotAutoLoginMaxTime=intval($sock->GET_INFO("HotSpotAutoLoginMaxTime"));
    $HotSpotLandingPage=trim($sock->GET_INFO("HotSpotLandingPage"));
    if($HotSpotAutoLoginMaxTime==0){$HotSpotAutoLoginMaxTime=5;}

    $HotSpotAutoLoginMaxTimeSec=$HotSpotAutoLoginMaxTime*60;
    $MAIN=unserialize(base64_decode($_GET["confirm"]));
    $register_time=$MAIN["register_time"];
    $template_id=$MAIN["template_id"];
    $tableid=$MAIN["ID"];
    if($template_id==0){$template_id=4;}
    $url=$MAIN["URL"];
    $max_register_time=$register_time+$HotSpotAutoLoginMaxTimeSec;
    if($HotSpotLandingPage<>null){$url=$HotSpotLandingPage;}

    $wifidog_templates=new wifidog_templates($template_id);


    WLOG("CONFIRM: was register on $register_time Max{$HotSpotAutoLoginMaxTime}mn ".date("Y-m-d H:i:s",$max_register_time)."...",true);

    if(time()>$max_register_time){
        $q=new lib_sqlite("/home/squid/hotspot/database.db");
        $q->QUERY_SQL("DELETE FROM `sessions` WHERE ID=$tableid");
        WLOG("CONFIRM: FAILED time exceed to confirm registration remove session id $tableid");
        ErrorPage($wifidog_templates->REGISTER_MESSAGE_TIMEOUT." {$HotSpotAutoLoginMaxTime}mn",$template_id,$url);
        return;
    }


    $HotSpotDisableAccountTime=intval($sock->GET_INFO("HotSpotDisableAccountTime"));
    $removeaccount=0;
    $disableaccount=0;

    if($HotSpotRemoveAccountTime==0) {
        if ($HotSpotDisableAccountTime > 0) {
            $HotSpotRemoveAccountTime = $HotSpotDisableAccountTime;
        }
    }

    if($HotSpotRemoveAccountTime>0){
        $removeaccount=$register_time+($HotSpotRemoveAccountTime*60);
        WLOG("CONFIRM: Will remove account on ".date("Y-m-d H:i:s",$removeaccount),true);
    }else{
        WLOG("CONFIRM: Will never remove account...",true);
    }

    if($HotSpotDisableAccountTime>0){
        $disableaccount=$register_time+($HotSpotDisableAccountTime*60);
        WLOG("CONFIRM: Will disabled account on ".date("Y-m-d H:i:s",$disableaccount),true);
    }else{
        WLOG("CONFIRM: Will never disable account...",true);
    }


    $SQLAR["autocreate"]=2;
    $SQLAR["enabled"]=1;
    $SQLAR["removeaccount"]=$removeaccount;
    $SQLAR["disableaccount"]=$disableaccount;


    foreach ($SQLAR as $key=>$val){
        $sqlED[]="`$key`='$val'";
    }
    WLOG("CONFIRM: Update database ID:$tableid",true);
    $sqled="UPDATE `sessions` SET ".@implode(",", $sqlED)." WHERE ID='$tableid'";
    $q=new lib_sqlite("/home/squid/hotspot/database.db");
    $q->QUERY_SQL($sqled);
    if(!$q->ok){
        WLOG("CONFIRM: FATAL $q->mysql_error");
        ErrorPage("{mysql_error}",$template_id,$url);
        return;
    }

    $memcached=new lib_memcached();
    if($_POST["MAC"]<>null) {
        $ligne = $q->mysqli_fetch_array("SELECT * FROM sessions WHERE sessionkey='{$_POST["MAC"]}'");
        $memcached->saveKey("MICROHOTSPOT:{$_POST["MAC"]}", $ligne, 1800);
    }
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sessions WHERE sessionkey='{$_POST["ipaddr"]}'");
    $memcached->saveKey("MICROHOTSPOT:{$_POST["ipaddr"]}",$ligne, 1800);

    WLOG("CONFIRM: Success...",true);
    ErrorPage($wifidog_templates->REGISTER_MESSAGE_SUCCESS,$template_id,$url);

}


function smtp_register_success(){
    $tpl=new template_admin();
    $sock=new sockets();
    $template_id=$_POST["template_id"];
    $url=$_POST["url"];


    $wifidog_templates=new wifidog_templates($template_id);
    $ArticaSplashHotSpotRedirectText=$wifidog_templates->ArticaSplashHotSpotRedirectText;


    if(preg_match("#detectportal\.firefox#",$url)){
        $content[]="<! -- URL:$url, remove access go to landing page -->";
    }

    $content[]="<! -- URL:$url -->";
    $HotSpotLandingPage=trim($sock->GET_INFO("HotSpotLandingPage"));
    if($HotSpotLandingPage<>null){$url=$HotSpotLandingPage;}
    $content[]="<! -- smtp_register_success -->";
    $content[]="<! -- HotSpotLandingPage:$HotSpotLandingPage -->";
    if($url==null){$url="https://www.google.com";}

    $ArticaSplashHotSpotRedirectText="$ArticaSplashHotSpotRedirectText<br>$url";
    $wifidog_templates->RedirectPage=$url;
    $wifidog_templates->MainExplain=$ArticaSplashHotSpotRedirectText;

    $content[]="<div class=title2>".$wifidog_templates->char($wifidog_templates->RegisterTitle)."</div>";
    $content[]="<p>$ArticaSplashHotSpotRedirectText</p>";

    if(isset($_POST["EnterpriseTemplate"])){
        $content=array();
        $content[]="    <div class=\"artica-form\">";
        $content[]="    <h3>".$wifidog_templates->char($wifidog_templates->RegisterTitle)."</h3>";
        $content[]="<p>$ArticaSplashHotSpotRedirectText</p>";
        $content[]="</div>";
    }



    $html=$wifidog_templates->build(@implode("\n", $content));
    $html=$tpl->_ENGINE_parse_body($html);
    echo $html;
}

function ErrorPage($error,$templateid,$url=null){
    $tpl=new template_admin();$sock=new sockets();
    $HotSpotLostLandingPage=trim($sock->GET_INFO("HotSpotLostLandingPage"));
    $wifidog_templates=new wifidog_templates($templateid);
    if($url==null){$url="http://www.msftncsi.com/ncsi.txt";}
    if($HotSpotLostLandingPage<>null){$url=$HotSpotLostLandingPage;}
    $wifidog_templates->RedirectPage=$url;
    WLOG("ERROR: Redirect to: $url",true);
    $content[]="<! -- Error Page -->";
    $content[]="<! -- HotSpotLostLandingPage:$HotSpotLostLandingPage -->";
    $content[]="<div class=title2>{error}</div>";
    $content[]="<p style='$wifidog_templates->ERROR_STYLE'>$error</p>";
    $html=$wifidog_templates->build(@implode("\n", $content));
    $html=$tpl->_ENGINE_parse_body($html);
    echo $html;


}


function local_imgload(){
    $filename=$_GET["imgload"];
    $fname=dirname(__FILE__)."/img/$filename";
    $mime_content_type=mime_content_type ( $fname );
    header("Content-type: $mime_content_type");
    header("Content-Length: " . filesize($fname));
    readfile($fname);
}

function local_js(){
    $fname=dirname(__FILE__)."/js/{$_GET["local-js"]}";
    header("Content-type: text/javascript");
    header("Content-Length: " . filesize($fname));
    readfile($fname);
}
function CSS_999998(){
    header("Content-type: text/css");
    $templates=new wifidog_templates();
    $content=$templates->CSS_999998();
    header("Content-Length: " .strlen($content));
    echo $content;
}
function CSS_99999(){
    header("Content-type: text/css");
    $templates=new wifidog_templates();
    $content=$templates->CSS_99999();
    header("Content-Length: " .strlen($content));
    echo $content;

}

function template_css(){
    $ruleid=intval($_GET["ruleid"]);
    if($ruleid==0){$ruleid=1;}
    $templateid=$_GET["template-id"];

    VERBOSE("My uid = ".getmyuid(), __LINE__);

    if(!is_dir("/home/squid/hotspot/$ruleid/files")){@mkdir("/home/squid/hotspot/$ruleid/files",0755,true);}
    $filename="/home/squid/hotspot/$ruleid/files/main.css";
    if(is_file($filename)){
        header("Content-type: text/css");
        header("Content-Length: " . filesize($filename));
        readfile($filename);
        return;
    }

    $templates_manager=new templates_manager($templateid);
    @file_put_contents($filename, $templates_manager->CssContent);

    header("Content-type: text/css");
    header("Content-Length: " . filesize($filename));
    readfile($filename);

}
function WLOG($text=null,$debug=true){

    if($GLOBALS["VERBOSE"]){
        if(function_exists("VERBOSE")){
            $LINE=0;
            $trace=@debug_backtrace();
            if(isset($trace[0])){$LINE=$trace[0]["line"];}
            if(isset($trace[1])){$LINE=$trace[1]["line"];}
            VERBOSE($text,$LINE);
        }
    }

    if($debug){if($GLOBALS["DEBUG_LEVEL"]==0){return;}}

    $handle= @fopen("/var/log/squid/hotspot.log", 'a');
    $trace=@debug_backtrace();
    if(isset($trace[0])){$LINE=$trace[0]["line"];}
    if(isset($trace[1])){$LINE=$trace[1]["line"];}


    $date=@date("Y-m-d H:i:s");
    if (is_file("/var/log/squid/hotspot.log")) {
        $size=@filesize("/var/log/squid/hotspot.log");
        if($size>1000000){
            @fclose($handle);
            unlink("/var/log/squid/hotspot.log");
            $handle = @fopen("/var/log/squid/hotspot.log", 'a');
        }


    }


    @fwrite($handle, "$date CONSOLE:[{$GLOBALS["PID"]}] L.$LINE : $text\n");
    @fclose($handle);
}

?>