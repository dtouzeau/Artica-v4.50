<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){
    if(!is_file(dirname(__FILE__)."/ressources/VERBOSE")){
        $GLOBALS["VERBOSE"]=false;
        unset($_GET["verbose"]);
    }else {
        echo "VERBOSED!!!!<br>\n\n";
    }
}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.langages.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.privileges.inc');
include_once(dirname(__FILE__).'/ressources/class.artica-logon.inc');
include_once(dirname(__FILE__)."/ressources/externals/GeoIP2/vendor/autoload.php");
use GeoIp2\Database\Reader;
//$GLOBALS["VERBOSE"]=true;
if(isset($_GET["artica-restcheck"])){RestCheck();exit;}
if(isset($_GET["apikey"])){apikey();exit;}
if(isset($_GET["disconnect"])){disconnect();exit;}
if(isset($_SESSION["uid"])){
    $HTTP_X_ARTICA_SUBFOLDER=null;
    //if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){$HTTP_X_ARTICA_SUBFOLDER=$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/"; }
   // echo "Location: /{$HTTP_X_ARTICA_SUBFOLDER}index";die();
    header("Location: /{$HTTP_X_ARTICA_SUBFOLDER}index");exit();
}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string',null);
    ini_set('error_append_string',null);
}
CheckTrustedNets();

if(isset($_POST["username"])){CheckLogin();exit;}
if(isset($_POST["2fa"])){Check2fa();exit;}
if(isset($_GET["debugcredentials"])){debugcredentials();exit;}

header("Content-Security-Policy","default-src 'self';script-src 'self' 'unsafe-inline';font-src 'self' data: fonts.gstatic.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com");

$WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
if(!isset($WizardSavedSettings["smtp_domainname"])){$WizardSavedSettings["smtp_domainname"]=null;}
if(!is_null($WizardSavedSettings["smtp_domainname"])){
    $WizardSavedSettings["smtp_domainname"]=trim($WizardSavedSettings["smtp_domainname"]);
}
$smtp_domainname=$WizardSavedSettings["smtp_domainname"];

VERBOSE("smtp domain name: $smtp_domainname",__LINE__);

if(!is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")) {
    if (!is_file("/etc/artica-postfix/WIZARD_INSTALL_EXECUTED")) {
        if ($smtp_domainname == null) {
            header('location:fw.wizard.intro.php');
            exit;
        }
    }
}


$ArticaWebOldLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebOldLogin"));
if($ArticaWebOldLogin==1){
    login();
    exit;
}

new_login();

function disconnect():bool{
    logon_events("LOGOUT");
    admin_tracks("Logout");

    $HTTP_X_ARTICA_SUBFOLDER="/";
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }

    $artica_logon=new artica_logon();
    $artica_logon->logoff();
    echo "
    <html>
	<head><META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=$HTTP_X_ARTICA_SUBFOLDER\"> </head>
	<body>
	<script>document.location.href='$HTTP_X_ARTICA_SUBFOLDER';</script>
	</body>
	</html>";
    return true;

}
function RestCheck():bool{

    if(!function_exists("curl_init")){
        header("X-CURL: 0");
        return false;
    }
    header("X-CURL: 1");
    return true;
}

function new_css():string{

    if(!isset($_COOKIE["userfont"])){$_COOKIE["userfont"]=null;}
    $HideArticaLogo             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaLogo"));
    $ArticaBackGroundColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLoginBackGroundColor"));
    $ArticaFontColor=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLoginFontColor"));
    $ArticaFontColorTitle=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFontColorTitle"));
    $ArticaFontColorFields=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFontColorFields"));
    if($ArticaFontColor==null){$ArticaFontColor="#a7b1c2";}
    if($ArticaBackGroundColor==null){$ArticaBackGroundColor="#283437";}
    if($ArticaFontColorTitle==null){$ArticaFontColorTitle="#ffffff";}
    if($ArticaFontColorFields==null){$ArticaFontColorFields="#ffffff";}



    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $HideArticaLogo=0;
        $ArticaBackGroundColor="#283437";
        $ArticaFontColor="#a7b1c2";

    }
    $f[]="<style>";
    $f[]="body{";
    if($HideArticaLogo==0) {
        $f[] = "background-image:url('img/Articafond3.png');";
    }


    if($_COOKIE["userfont"]==null){
        $fontFamily="font-family: Arial, \"MS UI Gothic\", \"MS P Gothic\", sans-serif;";

    }else{
        if($_COOKIE["userfont"]=="standard"){
            $fontFamily="font-family: \"open sans\",\"Helvetica Neue\",Helvetica,Arial,sans-serif;";

        }else{
            $fontFamily="font-family: '{$_COOKIE["userfont"]}',Arial, \"MS UI Gothic\", \"MS P Gothic\", sans-serif;";

        }
    }

    $f[]=$fontFamily;
    //$f[]="font-family: Arial, MS UI Gothic, MS P Gothic, sans-serif;";
    $f[]="}";
    $f[]=".wrapper{";
    $f[]="background-color:$ArticaBackGroundColor;";
    $f[]="background-size: 1280px 1076px;";
    $f[]="background-position: center 0%;";
    $f[]="background-repeat:no-repeat;";
    $f[]="margin: 0px;";
    $f[]="background:$ArticaBackGroundColor\9;";
    $f[]="}";
    $f[]=".title_name {";
    $f[]="font-size: 40pt;";
    $f[]="color:$ArticaFontColorTitle;";
    $f[]="}";
    $f[]=".LoginTitle2{";
    $f[]="font-size: 26pt;";
    $f[]="color:$ArticaFontColor;";
    $f[]="margin-left:78px;";
    $f[]="margin-top: 10px;";
    $f[]="}";
    $f[]=".login_img{";
    $f[]="width:43px;";
    $f[]="height:43px;";
    $f[]="}";
    $f[]=".p1{";
    $f[]="font-size: 11pt;";
    $f[]="color:$ArticaFontColorTitle;";
    $f[]="width:480px;";
    $f[]="}";
    $f[]=".ArticaVer{";
    $f[]="margin-top:20px;";
    $f[]="font-size:x-small;";
    $f[]="color:$ArticaFontColorTitle;";
    $f[]="text-align: right";
    $f[]="}";
    $f[]=".button{";
    $f[]="background-color:#18a689;";
    $f[]="border-radius: 4px ;";
    $f[]="transition: visibility 0s linear 0.218s,opacity 0.218s,background-color 0.218s;";
    $f[]="height: 68px;";
    $f[]="width: 300px;";
    $f[]="font-size: 28pt;";
    $f[]="color:#fff;";
    $f[]="text-align: center;";
    $f[]="float:right;";
    $f[]="margin:50px 0px 0px 78px;";
    $f[]="line-height:68px;";
    $f[]="cursor:pointer;";
    $f[]="}";
    $f[]=".form_input{";
    $f[]="background-color:rgba(255,255,255,0.2);";
    $f[]="background-color:#576D73\9;";
    $f[]="border-radius: 4px;";
    $f[]="padding:23px 22px;";
    $f[]="width: 480px;";
    $f[]="border: 0;";
    $f[]="height:30px;";
    $f[]="color:$ArticaFontColorFields;";
    $f[]="font-size:28px;";
    $f[]="font-weight:bold;";
    $f[]="}";
    $f[]=".nologin{";
    $f[]="margin:10px 0px 0px 78px;";
    $f[]="background-color:rgba(255,255,255,0.2);";
    $f[]="padding:20px;";
    $f[]="line-height:36px;";
    $f[]="border-radius: 5px;";
    $f[]="width: 480px;";
    $f[]="border: 0;";
    $f[]="color:#FFF;";
    $f[]="color:#FFF\9; /* IE6 IE7 IE8 */";
    $f[]="font-size:28px;";
    $f[]="}";
    $f[]=".div_table{";
    $f[]="display:table;";
    $f[]="}";
    $f[]=".div_tr{";
    $f[]="display:table-row;";
    $f[]="}";
    $f[]=".div_td{";
    $f[]="display:table-cell;";
    $f[]="}";
    $f[]=".title_gap{";
    $f[]="margin:20px 0px 0px 78px;";
    $f[]="}";
    $f[]=".img_gap{";
    $f[]="padding-right:30px;";
    $f[]="vertical-align:middle;";
    $f[]="}";
    $f[]=".password_gap{";
    $f[]="margin:30px 0px 0px 78px;";
    $f[]="}";
    $f[]=".error_hint{";
    $f[]="color: rgb(255, 204, 0);";
    $f[]="margin:10px 0px -10px 78px;";
    $f[]="font-size: 18px;";
    $f[]="}";
    $f[]=".error_hint1{";
    $f[]="margin:40px 0px -10px 78px;";
    $f[]="font-size: 24px;";
    $f[]="line-height:32px;";
    $f[]="width: 580px;";
    $f[]="}";
    $f[]=".main_field_gap{";
    $f[]="margin:100px auto 0;";
    $f[]="}";
    $f[]=".warming_desc{";
    $f[]="font-size: 16px;";
    $f[]="color:#FC0;";
    $f[]="width: 600px;";
    $f[]="}";
    $f[]="#captcha_img_div{";
    $f[]="margin: 30px 0px 0px 30px;";
    $f[]="width: 160px;";
    $f[]="height: 60px;";
    $f[]="border-radius: 4px;";
    $f[]="background-color:#FFF;";
    $f[]="float: left;";
    $f[]="}";
    $f[]="#captcha_pic{";
    $f[]="width: 90%;";
    $f[]="height:90%;";
    $f[]="margin: 3px 0px 0px 0px;";
    $f[]="}";
    $f[]="#captcha_input_div{";
    $f[]="margin: 30px 0px 0px 78px;";
    $f[]="float: left;";
    $f[]="}";
    $f[]="#captcha_text{";
    $f[]="width: 245px;";
    $f[]="background-color: rgba(255,255,255,0.2);";
    $f[]="background-color: #576D73\9;";
    $f[]="border-radius: 4px;";
    $f[]="padding: 15px 22px;";
    $f[]="border: 0;";
    $f[]="height: 30px;";
    $f[]="color: #fff;";
    $f[]="font-size: 28px;";
    $f[]="}";
    $f[]="/*for mobile device*/";
    $f[]="@media screen and (max-width: 1000px){";
    $f[]=".title_name {";
    $f[]="font-size: 20pt;";
    $f[]="color:$ArticaFontColor;";
    $f[]="margin-left:15px;";
    $f[]="}";
    $f[]=".LoginTitle2{";
    $f[]="font-size: 13pt;";
    $f[]="margin-left: 15px;";
    $f[]="}";
    $f[]=".p1{";
    $f[]="font-size: 12pt;";
    $f[]="width:100%;";
    $f[]="}";
    $f[]=".login_img{";
    $f[]="background-size: 75%;";
    $f[]="}";
    $f[]=".form_input{";
    $f[]="padding:10px 11px;";
    $f[]="width: 100%;";
    $f[]="height:30px;";
    $f[]="font-size:16px";
    $f[]="}";
    $f[]=".button{";
    $f[]="height: 50px;";
    $f[]="width: 100%;";
    $f[]="font-size: 14pt;";
    $f[]="text-align: center;";
    $f[]="float:right;";
    $f[]="margin: 25px -22px 40px 15px;";
    $f[]="line-height:50px;";
    $f[]="padding-left: 7px;";
    $f[]="}";
    $f[]=".nologin{";
    $f[]="margin-left:10px;";
    $f[]="padding:10px;";
    $f[]="line-height:18px;";
    $f[]="width: 100%;";
    $f[]="font-size:14px;";
    $f[]="}";
    $f[]=".error_hint{";
    $f[]="margin-left:10px;";
    $f[]="}";
    $f[]=".error_hint1{";
    $f[]="width: 100%;";
    $f[]="font-size:14px;";
    $f[]="}";
    $f[]=".main_field_gap{";
    $f[]="width:80%;";
    $f[]="margin:30px 0 0 15px;";
    $f[]="/*margin:30px auto 0;*/";
    $f[]="}";
    $f[]=".title_gap{";
    $f[]="margin-left:15px;";
    $f[]="}";
    $f[]=".password_gap{";
    $f[]="margin-left:15px;";
    $f[]="}";
    $f[]=".img_gap{";
    $f[]="padding-right:0;";
    $f[]="vertical-align:middle;";
    $f[]="}";
    $f[]=".warming_desc{";
    $f[]="margin: 10px 15px;";
    $f[]="width: 100%;";
    $f[]="}";
    $f[]="</style>";
    $f[]="";
    return @implode("",$f);
}

function client_certificate(){
    if(!isset($_SERVER["SSL_CLIENT_RAW_CERT"])){return false;}
    $LighttpdAllowAuthenticateScreen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdAllowAuthenticateScreen"));
    if($LighttpdAllowAuthenticateScreen==1){return false;}
    $array=openssl_x509_parse($_SERVER["SSL_CLIENT_RAW_CERT"]);
    if(!isset($array["subject"])){
        VERBOSE("client_certificate: subject isset:False",__LINE__);
        return false;
    }
    $login=new artica_logon();
    $login->x509=$array;
    if(!$login->CheckCreds()){
        $ip=GetRemoteIP();
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/btmp/{$array["subject"]}/$ip/ArticaWebx509:notty");
        return false;
    }

    $HTTP_X_ARTICA_SUBFOLDER=null;
    //if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){$HTTP_X_ARTICA_SUBFOLDER=$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/"; }

    $redirect="/{$HTTP_X_ARTICA_SUBFOLDER}index";
    setcookie("shellinaboxCooKie", "1", time()+172800,"","",true,true);
    echo "<html>
	<head><META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=$redirect\"> </head>
	<body>
	<script>document.location.href='$redirect';</script>
	</body>
	</html>";
    exit();
}

function GetMyHostname():string{
    $hostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");
    if(!is_null($hostname)){
        if(strlen($hostname)>3){
            return $hostname;
        }
    }
    return php_uname("n");

}

function new_login($return=false,$error=null){
    client_certificate();
    $page=CurrentPageName();
    $sock=new sockets();
    $tpl=new template_admin();
    $clogo="";
    $TitleOfArticaPage          = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LoginTitle2"));
    $TitleLogon                 = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TitleLogon"));
    $TextOfArticaPage           = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TextOfArticaPage"));
    $useCustomLogo             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useCustomLogo"));
    $customLogo=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("customLogo"));
    $HideArticaVersion          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaVersion"));
    $WebConsoleGoogle2FA        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebConsoleGoogle2FA"));
    $HideVirtualizationVersion  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideVirtualizationVersion"));

    if($TitleOfArticaPage==null){$TitleOfArticaPage="%SERVERNAME%";}
    if($TextOfArticaPage==null){$TextOfArticaPage="{default_login_explain}";}
    if($TitleLogon==null){$TitleLogon="{Connection}";}
    $title_button="{login}";
    $ArticaVer=null;

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $TextOfArticaPage="{default_login_explain}";
        $TitleOfArticaPage="%SERVERNAME%";
        $HideArticaVersion=0;
        $HideVirtualizationVersion=0;
        $TitleLogon="{Connection}";
        $useCustomLogo=0;
     }
    if(strlen($customLogo)<2){
        $useCustomLogo=0;
    }
    if(strpos(" $TitleOfArticaPage","%SERVERNAME%")>0){
        $hostname=GetMyHostname();
        $myhostname=str_replace("%SERVERNAME%",$hostname,$TitleOfArticaPage);
    }else{
        $myhostname=$TitleOfArticaPage;
    }

    fingerprint();

    if($HideArticaVersion==0) {
        $SP=null;
        $CURVER=trim(@file_get_contents("VERSION"));
        $CURPATCH=$GLOBALS["CLASS_SOCKETS"]->getFrameWork("artica.php?SPVersion=yes");
        if($CURPATCH>0){
            $SP="&nbsp;Service Pack $CURPATCH";
        }
        $ArticaVer = "Artica $CURVER{$SP} &copy; " . date("Y");

    }
    $f[]="<!DOCTYPE html>";
    $f[]="<html xmlns=\"http://www.w3.org/1999/xhtml\">";
    $f[]="<html xmlns:v>";
    $f[]="<head>";
    $f[]="<meta http-equiv=\"X-UA-Compatible\" content=\"IE=Edge\"/>";
    $f[]="<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
    $f[]="<meta HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">";
    $f[]="<meta HTTP-EQUIV=\"Expires\" CONTENT=\"-1\">";
    $f[]="<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, user-scalable=no\">";
    $f[]="<link rel='icon' href='ressources/templates/default/favicon.ico' type='image/x-icon' />";
    $f[]="<link rel='shortcut icon' href='ressources/templates/default/favicon.ico' type='image/x-icon' />";
    $f[]="<title>$myhostname</title>";
    $f[]=new_css();
    $f[]="<script src=\"/fingerprint/query\"></script>";
    $f[]="<script>";
    $f[]="function login(){";
    $f[]="document.form.submit();";
    $f[]="}";
    $f[]="function initial(){";

    $f[]="if(document.getElementById('username')){";
    $f[]="    document.form.username.focus();";
    $f[]="    document.form.username.onkeyup = function(e){";
    $f[]="        e=e||event;";
    $f[]="        if(e.keyCode == 13){";
    $f[]="            document.form.password.focus();";
    $f[]="            return false;";
    $f[]="        }";
    $f[]="    };";
    $f[]="    document.form.username.onkeypress = function(e){";
    $f[]="        e=e||event;";
    $f[]="        if(e.keyCode == 13){ return false; }";
    $f[]="    };";
    $f[]="    document.form.password.onkeyup = function(e){";
    $f[]="        e=e||event;";
    $f[]="        if(e.keyCode == 13){";
    $f[]="            login();";
    $f[]="            return false;";
    $f[]="        }";
    $f[]="    };";
    $f[]="    document.form.password.onkeypress = function(e){";
    $f[]="        e = e || event;";
    $f[]="        if (e.keyCode == 13) {";
    $f[]="            return false;";
    $f[]="        }";
    $f[]="    };";
    $f[]="  }";
    $f[]="}";
    $f[]="</script>";
    $f[]="</head>";

    $VirtualText=null;
    if($HideVirtualizationVersion==0) {
        if ($sock->GET_INFO("VMWARE_HOST") == 1) {
            $VirtualText = "VMWare Edition";
        }
        if (intval($sock->GET_INFO("NUTANIX_HOST")) == 1) {
            $VirtualText="Nutanix Edition";
        }
        if ($sock->GET_INFO("QEMU_HOST") == 1) {
           $VirtualText="Qemu Edition";
        }
    }

    $HTTP_X_ARTICA_SUBFOLDER=null;
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }



$f[]="<body class=\"wrapper\" onload=\"initial();\">";
    $f[]="<iframe name=\"hidden_frame\" id=\"hidden_frame\" width=\"0\" height=\"0\" style='border:0'></iframe>";
    $f[]="<iframe id=\"dmRedirection\" width=\"0\" height=\"0\" frameborder=\"0\" scrolling=\"no\" src=\"\"></iframe>";
    $f[]="<form method=\"post\" name=\"form\" action=\"$HTTP_X_ARTICA_SUBFOLDER$page\" target=\"\">";
    $rquire[]="userfont";
    $rquire[]="artica-language";
    $rquire[]="StandardDropDown";
    $rquire[]="HTMLTITLE";

    $FORM_ACCEPTABLE=true;

    if(GeoIPCheck()){
        $FORM_ACCEPTABLE=false;
    }

    if($FORM_ACCEPTABLE) {
        foreach ($rquire as $field) {
            if (!isset($_COOKIE[$field])) {
                $_COOKIE[$field] = null;
            }
            $_COOKIE[$field] = $tpl->CLEAN_BAD_XSS($_COOKIE[$field]);
            $f[] = "<input type='hidden' name='$field' value='$_COOKIE[$field]'>";
        }
    }



    $f[]="<div class=\"div_table main_field_gap\">";
    $f[]="<div class=\"div_tr\">";
    $f[]="<div class=\"title_name\">";
    $f[]="<div class=\"div_td img_gap\">";
    $f[]="<div class=\"login_img\"></div>";
    $f[]="</div>";
    if($useCustomLogo==1){
        $clogo = "<div style='width:100%;max-width: 524px;text-align:right;margin-top:-5px;padding-top:5px'><img alt='' style='width:100%' src='data:image/png;base64,$customLogo'></div>";

    }
    if($FORM_ACCEPTABLE) {
        $f[] = "<div class=\"div_td\">$clogo$TitleLogon</div>";
    }
    $f[]="</div>";

    if($VirtualText<>null){
        $VirtualText="&nbsp;<span style='font-size:small'>($VirtualText)</span>";
    }


    clean_xss_deep();
    if ($error == "2FAOK") { $error = null; }



    if($error<>null){
        $TextOfArticaPage="<span style='color:#ee8d8d;font-weight: bolder'>$error</span>";
    }
    if(!is_file(dirname(__FILE__) . '/ressources/class.manager.inc')){
        $TextOfArticaPage=$TextOfArticaPage."<br>
        <p style='color:#ee8d8d;font-weight: bolder'>{no_service_pack4}</p>";
    }


    $deuxfas=array();
    if ($WebConsoleGoogle2FA == 1) {
        if (isset($_SESSION["2FAOK"])) {
            $ttime = $tpl->time_diff_min($_SESSION["2FAOK"]);
            if ($ttime > 2) {
                unset($_SESSION["2FAOK"]);
            }
        }

        if (!isset($_SESSION["2FAOK"])) {
            if ($error == "2FAOK") { $_SESSION["2FAOK"] = time(); }
            if ($error <> "2FAOK") {
                $deuxfas[] = "<div id=\"name_title_ie\" style=\"display:none;margin:20px 0 -10px 78px;\" class=\"p1 title_gap\">{2faask}</div>";
                $deuxfas[] = "<div class=\"title_gap\">";
                $deuxfas[] = "<input type=\"text\" id=\"2fa\" name=\"2fa\" tabindex=\"1\" class=\"form_input\" maxlength=\"20\" autocapitalize=\"off\" autocomplete=\"off\" placeholder=\"{2faask}\" >";
                $deuxfas[] = "</div>";
                $WebConsoleGoogle2FA=1;
            }

        }
        if(count($deuxfas)==0){$WebConsoleGoogle2FA=0;}
    }





    if($FORM_ACCEPTABLE) {
        $f[]="<div class=\"LoginTitle2\">$myhostname$VirtualText</div>";
        $f[] = "<div id=\"login_filed\">";
        $f[] = "<div class=\"p1 title_gap\">$TextOfArticaPage</div>";
        if ($WebConsoleGoogle2FA == 0) {
            $f[] = "<div id=\"name_title_ie\" style=\"display:none;margin:20px 0 -10px 78px;\" class=\"p1 title_gap\">{username}</div>";
            $f[] = "<div class=\"title_gap\">";
            $f[] = "<input type=\"text\" id=\"username\" name=\"username\" tabindex=\"1\" class=\"form_input\"autocapitalize=\"off\" autocomplete=\"off\" placeholder=\"{username}\" >";
            $f[] = "</div>";
            $f[] = "<div id=\"password_title_ie\" style=\"display:none;margin:20px 0 -20px 78px;\" class=\"p1 title_gap\">{password}</div>";
            $f[] = "<div class=\"password_gap\">";
            $f[] = "<input type=\"password\" name=\"password\" tabindex=\"2\" class=\"form_input\" placeholder=\"{password}\" autocapitalize=\"off\" autocomplete=\"off\" >";
            $f[] = "</div>";
        } else {
            $f[] = @implode("", $deuxfas);
        }

        $f[] = "<div class=\"button\" onclick=\"login();\">$title_button&nbsp;&raquo;&raquo;</div>";
        $f[] = "</div>";
    }
    $f[]="</div>";
    if($ArticaVer<>null) {
        if($FORM_ACCEPTABLE) {
            $f[] = "<p class='ArticaVer'>$ArticaVer</p>";
        }
    }
    $f[]="</form>";
    $f[]="</body>";
    $f[]="</html>";

    if($return){return $tpl->_ENGINE_parse_body($f);}
    header("X-Frame-Options: \"sameorigin\"");
    echo $tpl->_ENGINE_parse_body($f);
    return true;
}

function fingerprint_status($visitorId):int{
    $q = new postgres_sql();
    $ligne = $q->mysqli_fetch_array("SELECT status FROM fingerprints WHERE fingerprint='$visitorId'");
    if(!$q->ok){
        return 0;
    }
    return intval($ligne["status"]);
}
function fingerprint():bool{
    $visitorId="";
    if(isset($_COOKIE["visitorId"])){$visitorId=$_COOKIE["visitorId"];}
    $ArticaWebFingerPrint=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrint"));
    $ArticaWebFingerPrintCaptcha=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrintCaptcha"));
    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));

    if($DisablePostGres==1) { return true;}
    if ($ArticaWebFingerPrint == 0) {return true;}


    if ($visitorId == "") {
        if ($ArticaWebFingerPrintCaptcha == 1) {
            header("Location: /captcha");
            die();
        }

        echo "<html>
                <head>
                <META HTTP-EQUIV=\"Refresh\" CONTENT=\"3; URL=/\"> 
                <script src=\"/fingerprint/query\"></script>
                </head>
                <body></body>
                </html>";
        die();
    }

    $Status=fingerprint_status($visitorId);

    if($Status==1){return true;}

    if($Status==2){
        page_no_privs();
        die;
    }
    if($Status==0){
        if($ArticaWebFingerPrintCaptcha==1) {
            header("Location: /captcha");
            exit();
        }
        page_no_privs();
        die;
    }

    return true;
}

function login($return=false,$error=null){
    VERBOSE(__FUNCTION__,__LINE__);
    client_certificate();
    clean_xss_deep();
    if($GLOBALS["VERBOSE"]){echo "<H1>".__FILE__."</H1><br>\n";$uriverbadd="?verbose=yes";}
    $page=CurrentPageName();
    $error_text=null;
    $sock=new sockets();
    $tpl=new template_admin();
    $myhostname=null;
    $ArticaVer=null;

    $bgstyle="background-image:url(img/Articafond1.png);background-repeat:no-repeat;";
    $columns="col-md-6";
    $TitleOfArticaPage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TitleOfArticaPage"));
    $TextOfArticaPage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TextOfArticaPage"));
    $HideArticaLogo=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaLogo"));
    $useCustomLogo             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("useCustomLogo"));
    $customLogo=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("customLogo"));
    if($TitleOfArticaPage==null){$TitleOfArticaPage="%SERVERNAME%";}
    if($TextOfArticaPage==null){$TextOfArticaPage="{default_login_explain}";}
    $HideArticaVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaVersion"));
    $HideVirtualizationVersion=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideVirtualizationVersion"));
    if(strlen($customLogo)<2){
        $useCustomLogo=0;
    }

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        if($HideArticaLogo==1){$bgstyle=null;}
        $TextOfArticaPage="{default_login_explain}";
        $TitleOfArticaPage="%SERVERNAME%";
        $HideArticaVersion=0;
        $HideVirtualizationVersion=0;
        $HideArticaLogo=0;
        $useCustomLogo=0;

    }



    if(strpos(" $TitleOfArticaPage","%SERVERNAME%")>0){
        $hostname=GetMyHostname();
        $myhostname=str_replace("%SERVERNAME%",$hostname,$TitleOfArticaPage);
    }else{
        $myhostname=$TitleOfArticaPage;
    }

    if($HideArticaLogo==1) {
        $bgstyle=null;
        $columns="col-md-11";
    }
    if($useCustomLogo==1){
        $columns="col-md-6";
    }

    if($HideArticaVersion==0) {
        $SP=null;
        $CURVER=trim(@file_get_contents("VERSION"));
        $CURPATCH=$GLOBALS["CLASS_SOCKETS"]->getFrameWork("artica.php?SPVersion=yes");
        if($CURPATCH>0){
            $SP="&nbsp;Service Pack $CURPATCH";
        }
        $ArticaVer = "Artica $CURVER{$SP} &copy; " . date("Y");

    }

    if($GLOBALS["VERBOSE"]){echo "ERROR --- $error<br>\n";}


    $picture=null;
    if($HideVirtualizationVersion==0) {
        if ($sock->GET_INFO("VMWARE_HOST") == 1) {
            $picture = "<div style='width:100%;text-align:right;border-top:1px solid #717075;margin-top:-5px;padding-top:5px'><img src='img/vmware-edition.png'></div>";
        }
        if (intval($sock->GET_INFO("NUTANIX_HOST")) == 1) {
            $picture = "<div style='width:100%;text-align:right;border-top:1px solid #717075;margin-top:-5px;padding-top:5px'><img src='img/nutanix-edition.png'></div>";
        }
        if ($sock->GET_INFO("QEMU_HOST") == 1) {
            $picture = "<div style='width:100%;text-align:right;border-top:1px solid #717075;margin-top:-5px;padding-top:5px'><img src='img/qemu-edition.png'></div>";
        }
    }

    if($useCustomLogo==1){
        $clogo = "<div style='width:100%;max-width: 524px;text-align:right;margin-top:-5px;padding-top:5px'><img style='width:100%' src='data:image/png;base64,$customLogo'></div>";

    }

    if($picture<>null){$picture="<p>$picture</p>";}

    if($error<>null) {
        if ($error <> "2FAOK") {
            $error_text = "<div class='alert alert-danger'>$error</div>";
        }
    }

    $html="<!DOCTYPE html>
<html>

<head>
    <meta charset=\"utf-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">

    <title>$myhostname</title>

    <link href=\"/angular/bootstrap.min.css\" rel=\"stylesheet\">
    <link href=\"/angular/font-awesome/css/all.min.css\" rel=\"stylesheet\">

    <link href=\"/angular/animate.css\" rel=\"stylesheet\">
    <link href=\"/angular/style.css\" rel=\"stylesheet\">
    <link href=\"/angular.css.php\" rel=\"stylesheet\">
</head>

<body class=\"gray-bg\" style='$bgstyle'>
<!-- ".basename(__FILE__) ." " .__LINE__. " -->
    <div class=\"loginColumns animated fadeInDown\">
        <div class=\"row\">

            <div class=\"$columns\">
            $clogo
                <h2 class=\"font-bold\">$myhostname</h2>
                $picture
				<p>$TextOfArticaPage</p>


            </div>
            <div class=\"$columns\">
                <div class=\"ibox-content\">";

    $rquire[]="userfont";
    $rquire[]="artica-language";
    $rquire[]="StandardDropDown";
    $rquire[]="HTMLTITLE";

    foreach ($rquire as $field){
        if(!isset($_COOKIE[$field])){$_COOKIE[$field]=null;}
        $fields[]="<input type='hidden' name='$field' value='{$_COOKIE[$field]}'>";
    }

    $added_fields=@implode("\n",$fields);
    $valueuser=null;
    $valuepass=null;
    if(isset($_POST["username"])){
        $valueuser="value=\"{$_POST["username"]}\"";
    }
    if(isset($_POST["password"])){
        $valuepass="value=\"{$_POST["password"]}\"";
    }

    $WebConsoleGoogle2FA           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebConsoleGoogle2FA"));

    $form_login="<form class=\"m-t\" role=\"form\" action=\"$page\" method='post'>
                        <div class=\"form-group\">
                            $added_fields
                            <input type=\"username\" class=\"form-control\" placeholder=\"{username}\" $valueuser name='username' required=\"\">
                        </div>
                        <div class=\"form-group\">
                            <input type=\"password\" class=\"form-control\" placeholder=\"{password}\" $valuepass name='password' required=\"\">
                        </div>
                        <button type=\"submit\" class=\"btn btn-primary block full-width m-b\">{login}</button>
                </form>";


        if ($WebConsoleGoogle2FA == 1) {
            if (isset($_SESSION["2FAOK"])) {
                $ttime = $tpl->time_diff_min($_SESSION["2FAOK"]);
                if ($ttime > 2) {
                    unset($_SESSION["2FAOK"]);
                }
            }

            if (!isset($_SESSION["2FAOK"])) {
                if ($error == "2FAOK") {
                    $_SESSION["2FAOK"] = time();
                }
                if ($error <> "2FAOK") {
                    $stylefield = "style='font-size:30px;height: 40px'";
                    $form_login = "<form class=\"m-t\" role=\"form\" action=\"$page\" method='post'>
                        <div class=\"form-group\">
                        <input type=\"2fa\" class=\"form-control\" 
                            placeholder=\"{2faask}\" $valueuser name='2fa' required=\"\" $stylefield></div>
                        <button type=\"submit\" class=\"btn btn-primary block full-width m-b\">{check_code}</button>
                </form>";
                }

            }
        }

    if(GeoIPCheck()){
        $form_login=null;
    }

    $html=$html.$form_login;
    $html=$html." $error_text
                    <p class=\"m-t\" style='text-align:right'>
                        <small>$ArticaVer</small>
                    </p>
                </div>
            </div>
        </div>
</body>

</html>";

    if($return){return $tpl->_ENGINE_parse_body($html);}
    header("X-Frame-Options: \"sameorigin\"");
    echo $tpl->_ENGINE_parse_body($html);



}
function ifRights($users):bool{
    if(!isset($_SESSION["MANAGE_CATEGORIES"])){$_SESSION["MANAGE_CATEGORIES"]=array();}
    if(count($_SESSION["MANAGE_CATEGORIES"])>0){return true;}
    if($users->AsWebMaster){return true;}
    if($users->AsWebSecurity){return true;}
    if($users->AsFirewallManager){return true;}
    if($users->AsDatabaseAdministrator){return true;}
    if($users->AsVPNManager){return true;}
    if($users->AsProxyMonitor){return true;}
    if($users->AsMessagingOrg ){return true;}
    if($users->AsPostfixAdministrator ){return true;}
    if($users->AsDnsAdministrator ){return true;}
    if($users->AsWebStatisticsAdministrator ){return true;}
    if($users->AsCertifsManager){return true;}
    if($users->AsDockerAdmin){return true;}
    if($users->AsDockerReverse){return true;}
    if($users->AsSquidPersonalCategories){return true;}
    if($users->AsHamrpAdmin){return true;}
    if($users->AsWebSecurity){return true;}
    if($users->AsWebMonitor){return true;}
    if($users->AsDansGuardianAdministrator){return true;}

    if(count($users->NGINX_SERVICES)>0){
        $EnableNginx=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx");
        if($EnableNginx==1){
            return true;
        }
    }
    if(count($users->SIMPLE_ACLS)>0){
        return true;
    }

    return false;
}
function debugcredentials(){

    $DebugInterfaceCredentials=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebugInterfaceCredentials"));
    if($DebugInterfaceCredentials==0){exit;}
    $GLOBALS["VERBOSE"]=true;
    $_POST["username"]=$_GET["username"];
    $_POST["password"]=$_GET["password"];
    CheckLogin();
}
function Check2fa(){
    include_once(dirname(__FILE__)."/ressources/externals/PHPGangsta/google2fa.inc");
    $code=$_POST["2fa"];
    $ga = new PHPGangsta_GoogleAuthenticator();
    $Artica2FAToken = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Artica2FAToken"));

    $checkResult = $ga->verifyCode($Artica2FAToken, $code, 3);    // 2 = 2*30sec clock tolerance
    if ($checkResult) {
        admin_tracks("Success logon using 2FA");
        echo login(true,"2FAOK");
    } else {
        logon_events("FAILED");
        admin_tracks("Logon using 2FA Failed");
        echo login(true,"{2fa_failed}");
    }

}

function LoginPage($error=""):bool{
    $ArticaWebOldLogin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebOldLogin"));
    if($ArticaWebOldLogin==1){
        echo login(true,$error);
        die();
    }
    echo new_login(true,$error);
    die();

}

function CheckLogin():bool{
$visitorId="";
    if(GeoIPCheck()){
        echo new_login(true,"");
        return true;
    }

    $POST_OUT=false;
    clean_xss_deep();
    if(preg_match("#(.+?):VERBOSE#i",$_POST["username"],$re)){
        $_POST["username"]=$re[1];
        $POST_OUT=true;
        $GLOBALS["VERBOSE"]=true;
    }

    if(isset($_COOKIE["visitorId"])){
        $visitorId=$_COOKIE["visitorId"];
        $_SESSION["visitorId"]=$_COOKIE["visitorId"];
    }


    $ArticaWebFingerPrint=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebFingerPrint"));

    if($ArticaWebFingerPrint==1){
        if($visitorId==""){
            $ip=GetRemoteIP();
            admin_tracks("Failed to logon ({$_POST["username"]}/$ip) no fingerprint associated");
            LoginPage();
        }
        $Status=fingerprint_status($visitorId);
        if($Status<>1){
            $ip=GetRemoteIP();
            admin_tracks("Failed to logon ({$_POST["username"]}/$ip) fingerprint $visitorId is not allowed by the Artica Web console");
            LoginPage();
        }
    }


    foreach ($_SERVER as $index=>$value){
        if(is_array($value)){
            foreach ($value as $a=>$b){
                $notice[]="$index:$a:$b";
            }
            continue;
        }
        $notice[]="$index:$value";
    }

    $DebugWebConsoleAuth=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DebugWebConsoleAuth");
    $tpl=new template_admin();
    $_POST["username"]=$tpl->CLEAN_BAD_XSS($_POST["username"]);
    $login=new artica_logon();

    if(method_exists($login,"VERBOSE")) {
        $login->VERBOSE("Check Login {$_POST["username"]}...", __FILE__);
    }
    webconsole_syslog("Check Login {$_POST["username"]}...",__FILE__);

    if(method_exists($login,"VERBOSE")) {
        $login->VERBOSE("CheckCreds({$_POST["username"]},...)", __FILE__);
    }
    if(!$login->CheckCreds($_POST["username"],$_POST["password"])){

        if($DebugWebConsoleAuth==1){
            writelogs("[{$_POST["username"]}] Failed to logon",__FUNCTION__,__FILE__,__LINE__);;
        }

        if(method_exists($login,"VERBOSE")) {
            $login->VERBOSE("Checking Login...FAILED", __LINE__);
        }
        logon_events("FAILED");
        $ip=GetRemoteIP();
        if($DebugWebConsoleAuth==0) {
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/btmp/{$_POST["username"]}/$ip/ArticaWeb:notty");
        }
        admin_tracks("Failed to logon ({$_POST["username"]}) on the Artica Web console from $ip L.".__LINE__);
        echo new_login(true,"{wrong_password_or_username}");


        $tpl->squid_admin_mysql(1,"Failed to logon ({$_POST["username"]}) on the Artica Web console from $ip  L.".__LINE__,@implode("\n",$notice),__FILE__,__LINE__);
        return false;
    }


    $users=new usersMenus();
    if(!ifRights($users)){
        logon_events("NO_PRIVS");
        $ERROR="{ERROR_NO_PRIVS}";
        if($POST_OUT){
            $tt[]="<H1 style='font-size:11px'>{ERROR_NO_PRIVS}</H1>";
            if(is_array($login->VERBOSE)) {
                foreach ($login->VERBOSE as $line) {
                    $tt[] = "<div style='font-size:11px'>$line</div>";

                }
            }
            $ERROR=@implode("\n",$tt);
        }
        $ip=GetRemoteIP();
        admin_tracks("Failed to logon ({$_POST["username"]}) no privileges associated on the Artica Web console from $ip");
        $login->logoff();
        $tpl->squid_admin_mysql(1,"Failed to logon ({$_POST["username"]}) on the Artica Web console from $ip",@implode("\n",$notice),__FILE__,__LINE__);
        if(isset($_GET["debugcredentials"])){VERBOSE("login->logoff()");exit;}
        LoginPage($ERROR);
        return false;
    }
    $HTTP_X_ARTICA_SUBFOLDER="/";
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        $HTTP_X_ARTICA_SUBFOLDER="/".$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/";
    }
    $redirect="{$HTTP_X_ARTICA_SUBFOLDER}index";


    $GLOBALS["CLASS_SOCKETS"]->REST_API("/rrd/allimages");
    $ip=GetRemoteIP();
    logon_events("SUCCESS");
    admin_tracks("Success to logon ({$_POST["username"]}) on the Artica Web console from $ip");
    $tpl->squid_admin_mysql(2,"Success to logon ({$_POST["username"]}) on the Artica Web console from $ip",@implode("\n",$notice),__FILE__,__LINE__);


    if($users->AsWebStatisticsAdministrator){
        setcookie("AsWebStatisticsCooKie", "1", time()+172800,"","",true,true);
    }

    $rquire[]="userfont";
    $rquire[]="artica-language";
    $rquire[]="StandardDropDown";
    $rquire[]="HTMLTITLE";


    foreach ($rquire as $field){
        if(isset($_POST[$field])){
            $datasrc=$_POST[$field];
            $data=$tpl->CLEAN_BAD_XSS($datasrc);
            if($data<>$datasrc){
                admin_tracks("XSS Detected on value COOKIE $field [$datasrc]");
            }
            setcookie($field, $data, time()+31536000,"","",true,true);
        }
    }
    setcookie("shellinaboxCooKie", "1", time()+172800,"","",true,true);

    echo "<html>
	<head><META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=$redirect\"> </head>
	<body>
	<script>document.location.href='$redirect';</script>
	</body>
	</html>";
    return true;
}
function LoadTrustedNets():array{
    $memcached=new lib_memcached();
    $data=$memcached->getKey("WebConsoleTrustedNet");
    if(strlen($data)>7){
        return explode(",",$data);
    }
    $f=array();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results= $q->QUERY_SQL("SELECT * FROM networks_infos WHERE trusted=1 AND enabled=1");
    foreach ($results as $index => $ligne) {
        $maks=$ligne["ipaddr"];
        $f[]=$maks;
    }
    $memcached->saveKey("WebConsoleTrustedNet",@implode(",",$f));
    return $f;


}
function CheckTrustedNets():bool{
    $WebConsoleCheckTrustedNets = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebConsoleCheckTrustedNets"));
    if ($WebConsoleCheckTrustedNets == 0) {
        return false;
    }
    $GetRemoteIP=GetRemoteIP();
    $Nets=LoadTrustedNets();
    $IP=new IP();
    $c=0;
    foreach ($Nets as $masks){
        if(!$IP::IsACDIROrIsValid($masks)){
            logon_events("INVALID CONFIGURATION MASK $masks");
            continue;
        }
        $c++;
        if(webconsole_ip_in_range($GetRemoteIP,$masks)){
            logon_events("TRUSTED/$masks");
            return true;
        }
    }
    if($c>0) {
        return logon_events("UNTRUSTED");
    }
    return false;
}
function GetRemoteIP():string{
    $IPADDR="";
    if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
    if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
    return $IPADDR;
}
function apikey_die(){
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
    echo @file_get_contents("404.html");
    die();
}
function apikey(){
    if(GeoIPCheck()){apikey_die();}
    $tpl=new template_admin();
    $md5=$tpl->CLEAN_BAD_CHARSNET($_GET["apikey"]);
    webconsole_syslog("API KEY START WITH: {$_GET["apikey"]}");

    $AuthLinkRestrictions=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AuthLinkRestrictions")));
    if(!is_array($AuthLinkRestrictions)){$AuthLinkRestrictions=array();}
    if(count($AuthLinkRestrictions)==0){
        $AuthLinkRestrictions[] = "192.168.0.0/16";
        $AuthLinkRestrictions[] = "10.0.0.0/8";
        $AuthLinkRestrictions[] = "172.16.0.0/12";
    }
    $IPADDR=GetRemoteIP();
    $PASS_IPADDR=false;

    foreach ($AuthLinkRestrictions as $cdir){
        if(webconsole_ip_in_range($IPADDR,$cdir)){
            $PASS_IPADDR=true;
            break;
        }
    }

    if(!$PASS_IPADDR){
        webconsole_syslog("API KEY FAILED, did not matches any of ".@implode(", ",$AuthLinkRestrictions));
        apikey_die();
    }


    if($md5==null){
        logon_events("FAILED");
        webconsole_syslog("API KEY FAILED, no token sent");
        apikey_die();
    }


    if(!preg_match("#^[0-9a-z]+$#",$md5)){
        webconsole_syslog("API KEY FAILED, Corrupted token sent: \"$md5\"");
        apikey_die();
    }
    $q=new lib_sqlite("/home/artica/SQLITE/admins.db");
    if($q->COUNT_ROWS("APIs")==0){
        webconsole_syslog("API KEY FAILED, No API KEY in database");
        logon_events("FAILED");
        apikey_die();
    }

    $ligne=$q->mysqli_fetch_array("SELECT ID,content FROM `APIs` WHERE zmd5='{$md5}'");
    $ID=intval($ligne["ID"]);
    if($ID==0){
        logon_events("FAILED");
        webconsole_syslog("API KEY FAILED, $md5 did not matches any record");
        apikey_die();}
    $CONTENT=unserialize(base64_decode($ligne["content"]));
    $_SESSION["uid"]=$CONTENT["uid"];
    if($_SESSION["uid"]=="-100"){$Name="SuperAdmin";}else{$Name=$_SESSION["uid"];}

    if(isset($CONTENT["ACTIVE_DIRECTORY_INDEX"])){
        $_SESSION["ACTIVE_DIRECTORY_INDEX"]=$CONTENT["ACTIVE_DIRECTORY_INDEX"];
        $_SESSION["ACTIVE_DIRECTORY_DN"]=$CONTENT["ACTIVE_DIRECTORY_DN"];
        $_SESSION["ACTIVE_DIRECTORY_INFO"]=$CONTENT["ACTIVE_DIRECTORY_INFO"];
    }

    if(isset($CONTENT["RADIUS_ID"])){$_SESSION["RADIUS_ID"]=$CONTENT["RADIUS_ID"];}
    if(isset($CONTENT["SQLITE_ID"])){$_SESSION["SQLITE_ID"]=$CONTENT["SQLITE_ID"];}
    if(isset($CONTENT["detected_lang"])){$_SESSION["detected_lang"]=$CONTENT["detected_lang"];}
    if(isset($CONTENT["UID_KEY"])){$_SESSION["UID_KEY"]=$CONTENT["UID_KEY"];}
    if(isset($CONTENT["CORP"])){$_SESSION["CORP"]=$CONTENT["CORP"];}
    if(isset($CONTENT["privileges_array"])){$_SESSION["privileges_array"]=$CONTENT["privileges_array"];}
    if(isset($CONTENT["privs"])){$_SESSION["privs"]=$CONTENT["privs"];}
    if(isset($CONTENT["OU_LANG"])){$_SESSION["OU_LANG"]=$CONTENT["OU_LANG"];}
    if(isset($CONTENT["privileges"])){$_SESSION["privileges"]=$CONTENT["privileges"];}
    if(isset($CONTENT["groupid"])){$_SESSION["groupid"]=$CONTENT["groupid"];}
    if(isset($CONTENT["ou"])){$_SESSION["ou"]=$CONTENT["ou"];}


    $login=new artica_logon();
    logon_events("OK");
    $ip=$login->GetRemoteIP();
    $tpl->squid_admin_mysql(2,"Success to logon ({$_POST["username"]}) on Artica Web console API from $ip as $Name",$login->GetNotice(),__FILE__,__LINE__);
    $HTTP_X_ARTICA_SUBFOLDER=null;
    //if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){$HTTP_X_ARTICA_SUBFOLDER=$_SERVER["HTTP_X_ARTICA_SUBFOLDER"]."/"; }
    webconsole_syslog("API KEY SUCCESS, Redirecting to /{$HTTP_X_ARTICA_SUBFOLDER}index");
    echo "<html>
	<head>
	<title>$Name: Redirecting</title>
	<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=/{$HTTP_X_ARTICA_SUBFOLDER}index\"> </head>
	<body>
	<script>document.location.href='/{$HTTP_X_ARTICA_SUBFOLDER}index';</script>
	</body>
	</html>";
    exit();

}
function logon_events($succes):bool{
    $uid=null;
    if(isset($_SESSION["uid"])) {
        $uid = $_SESSION["uid"];
    }

    if (empty($uid)) {
        if(isset($_POST["username"])) {
            $uid = $_POST["username"];
        }
    }
    if($uid==-100){$uid="Manager";}
    $IPADDR=GetRemoteIP();
    $logFile="/var/log/artica-webauth.log";
    $date=date('M  j H:i:s');
    if(is_writable($logFile)) {
        $f = fopen($logFile, 'a');
        $hostname = GetMyHostname();
        fwrite($f, "$date $hostname: $uid $IPADDR $succes\n");
        fclose($f);
    }
    return true;
}
function webconsole_ip_in_range( $ip, $range ) {
    if ( strpos( $range, '/' ) == false ) { $range .= '/32';}
    list( $range, $netmask ) = explode( '/', $range, 2 );
    $range_decimal = ip2long( $range );
    $ip_decimal = ip2long( $ip );
    $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}

function GeoIPCheck():bool{

    $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
    $PHP_GEOIP_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHP_GEOIP_INSTALLED"));
    if($PHP_GEOIP_INSTALLED==0){
        VERBOSE("PHP_GEOIP_INSTALLED = 0",__LINE__);
        $EnableGeoipUpdate=0;
    }
    if($EnableGeoipUpdate==0){return false;}
    if (!extension_loaded("maxminddb")) {
        VERBOSE("extension_loaded -> maxminddb -> FALSE",__LINE__);
        return false;
    }



    $ArticaWebDenyCountries=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebDenyCountries"));
    $c=0;
    foreach ($ArticaWebDenyCountries as $CN=>$none){
        if (strlen($CN)<2){continue;}
        $c++;
    }
    if($c==0){ return false; }
    $GetRemoteIP=GetRemoteIP();
    try {
            $reader = new Reader('/usr/local/share/GeoIP/GeoLite2-Country.mmdb');
            $record = $reader->country($GetRemoteIP);
            $value["countryCode"] = $record->country->isoCode;
            $value["countryName"] = $record->country->name;
        } catch (Exception $e) {
            writelogs("GeoIP: fatal error:".  $e->getMessage(),__FUNCTION__,__FILE__,__LINE__);
            return false;
        }
    if ($value["countryCode"] == null) {
        writelogs("GeoIP: countryCode($GetRemoteIP) is null",__FUNCTION__,__FILE__,__LINE__);
        return false;
    }
    $countryCode=$value["countryCode"];
    VERBOSE("countryCode = $countryCode",__LINE__);
    if(isset($ArticaWebDenyCountries[$countryCode])){
        logon_events("FAILED");
        return true;
    }
    return false;
}
function page_no_privs():bool{
    VERBOSE("BUILDING ERROR 500",__LINE__);
    $data=@file_get_contents("generic.html");
    $data=str_replace("_CODE_",500,$data);
    $data=str_replace("_TITLE_","{ERROR_NO_PRIVS}",$data);

    $tpl=new template_admin();
    $content=$tpl->_ENGINE_parse_body("<strong>{$_SESSION["uid"]}</strong>, {not allowed}<p>{session_expired_text}</p>");
    $content=str_replace("href=logon.php","/fw.login.php?disconnect=yes",$content);
    $data=str_replace("_DESC_",$content,$data);
    echo $tpl->_ENGINE_parse_body($data);
    return true;
}