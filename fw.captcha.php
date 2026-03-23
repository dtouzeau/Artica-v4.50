<?php
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){echo "VERBOSED!!!!<br>\n\n";}
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

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string',null);
    ini_set('error_append_string',null);
}

// ── Security headers ─────────────────────────────────────────────────────────
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; font-src 'self' data: fonts.gstatic.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com; img-src 'self' data:");
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

if(isset($_POST["uid"])){validate();exit;}

captcha();


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

    // Sanitize color values — only allow hex colors
    $ArticaBackGroundColor = preg_match('/^#[0-9a-fA-F]{3,6}$/', $ArticaBackGroundColor) ? $ArticaBackGroundColor : "#283437";
    $ArticaFontColor = preg_match('/^#[0-9a-fA-F]{3,6}$/', $ArticaFontColor) ? $ArticaFontColor : "#a7b1c2";
    $ArticaFontColorTitle = preg_match('/^#[0-9a-fA-F]{3,6}$/', $ArticaFontColorTitle) ? $ArticaFontColorTitle : "#ffffff";
    $ArticaFontColorFields = preg_match('/^#[0-9a-fA-F]{3,6}$/', $ArticaFontColorFields) ? $ArticaFontColorFields : "#ffffff";

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $HideArticaLogo=0;
        $ArticaBackGroundColor="#283437";
        $ArticaFontColor="#a7b1c2";
    }
    $f[]="<style>";
    $f[]="body{";

    $HTTP_X_ARTICA_SUBFOLDER=null;
    if(isset($_SERVER["HTTP_X_ARTICA_SUBFOLDER"])){
        // Sanitize: only allow alphanumeric, hyphens, underscores
        $raw = $_SERVER["HTTP_X_ARTICA_SUBFOLDER"];
        if(preg_match('/^[a-zA-Z0-9_-]+$/', $raw)){
            $HTTP_X_ARTICA_SUBFOLDER = "/" . $raw . "/";
        }
    }

    if($HideArticaLogo==0) {
        $f[] = "background-image:url('{$HTTP_X_ARTICA_SUBFOLDER}img/Articafond3.png');";
    }

    if($_COOKIE["userfont"]==null){
        $fontFamily="font-family: Arial, \"MS UI Gothic\", \"MS P Gothic\", sans-serif;";

    }else{
        if($_COOKIE["userfont"]=="standard"){
            $fontFamily="font-family: \"open sans\",\"Helvetica Neue\",Helvetica,Arial,sans-serif;";

        }else{
            // Sanitize cookie: only allow alphanumeric, spaces, hyphens
            $safefont = preg_replace('/[^a-zA-Z0-9 -]/', '', $_COOKIE["userfont"]);
            $fontFamily="font-family: '$safefont',Arial, \"MS UI Gothic\", \"MS P Gothic\", sans-serif;";

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
    $f[]="margin:0px auto 0;";
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
    $f[]="margin:-45px 0 0 15px;";
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



function refresh(){
    $tpl=new template_admin();

    $f[]="<!DOCTYPE html>";
    $f[]="<html xmlns=\"http://www.w3.org/1999/xhtml\">";
    $f[]="<html xmlns:v>";
    $f[]="<head>";
    $f[]="<meta http-equiv=\"X-UA-Compatible\" content=\"IE=Edge\"/>";
    $f[]="<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
    $f[]="<meta HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">";
    $f[]="<meta HTTP-EQUIV=\"Expires\" CONTENT=\"-1\">";
    $f[]="<meta http-equiv=\"refresh\" content=\"2\">";
    $f[]="<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, user-scalable=no\">";
    $f[]="<link rel='icon' href='ressources/templates/default/favicon.ico' type='image/x-icon' />";
    $f[]="<link rel='shortcut icon' href='ressources/templates/default/favicon.ico' type='image/x-icon' />";
    $f[]="<title>{please_wait}....</title>";
    $f[]=new_css();
    $f[]="<script src=\"/fingerprint/query\"></script>";
    $f[]="</head>";
    $f[]="<body class=\"wrapper\" onload=\"initial();\">";
    $f[]="<iframe name=\"hidden_frame\" id=\"hidden_frame\" width=\"0\" height=\"0\" frameborder=\"0\"></iframe>";
    $f[]="<iframe id=\"dmRedirection\" width=\"0\" height=\"0\" frameborder=\"0\" scrolling=\"no\" src=\"\"></iframe>";

    $f[]="<div class=\"div_table main_field_gap\">";
    $f[]="<div class=\"div_tr\">";
    $f[]="<div class=\"title_name\">";
    $f[]="<div class=\"div_td img_gap\">";
    $f[]="<div class=\"login_img\"></div>";
    $f[]="</div>";
    $f[] = "<div class=\"div_td\">{please_wait}</div>";
    $f[]="</div>";
    $f[]="</div>";
    $f[]="</body>";
    $f[]="</html>";
    echo $tpl->_ENGINE_parse_body($f);
    return true;
}

function captcha($return=false,$error=null){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $TitleOfArticaPage          = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CaptchaValidationTitle"));
    $TextOfArticaPage           = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CaptchaValidationText"));
    $HideArticaVersion          = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HideArticaVersion"));
    $title_button="{submit}";

    if($TitleOfArticaPage==null){$TitleOfArticaPage="{captcha_validation_required_title}";}
    if($TextOfArticaPage==null){$TextOfArticaPage="{captcha_validation_required_text}";}
    $ArticaVer=null;

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $TextOfArticaPage="{default_login_explain}";
        $TitleOfArticaPage="{captcha_validation_required_title}";
        $HideArticaVersion=0;
        $TitleLogon="{apply}";
     }

    if(!isset($_COOKIE["visitorId"])){
       return refresh();
    }
    $visitorId = preg_replace('/[^a-zA-Z0-9]/', '', $_COOKIE["visitorId"]);

    if($HideArticaVersion==0) {
        $SP=null;
        $CURVER=trim(@file_get_contents("VERSION"));
        $CURPATCH= $GLOBALS["CLASS_SOCKETS"]->SPVersion();
        if($CURPATCH>0){
            $SP="&nbsp;Service Pack $CURPATCH";
        }
        $ArticaVer = "Artica " . htmlspecialchars($CURVER) . "{$SP} &copy; " . date("Y");

    }

    // Escape admin-configurable text for safe HTML output
    $TitleOfArticaPageSafe = htmlspecialchars($TitleOfArticaPage, ENT_QUOTES, 'UTF-8');
    $TextOfArticaPageSafe  = htmlspecialchars($TextOfArticaPage, ENT_QUOTES, 'UTF-8');

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
    $f[]="<title>$TitleOfArticaPageSafe</title>";
    $f[]=new_css();
    $f[]="<script src=\"/fingerprint/query\"></script>";
    $f[]="<script>";
    $f[]="function PostIt(){";
    $f[]="document.form.submit();";
    $f[]="}";
    $f[]="function initial(){";

    $f[]="if(document.getElementById('captchauser')){";
    $f[]="    document.form.captchauser.focus();";
    $f[]="    document.form.captchauser.onkeyup = function(e){";
    $f[]="        e=e||event;";
    $f[]="        if(e.keyCode == 13){";
    $f[]="            document.form.password.focus();";
    $f[]="            return false;";
    $f[]="        }";
    $f[]="    };";
    $f[]="    document.form.captchauser.onkeypress = function(e){";
    $f[]="        e=e||event;";
    $f[]="        if(e.keyCode == 13){ return false; }";
    $f[]="    };";
    $f[]="  }";
    $f[]="}";
    $f[]="</script>";
    $f[]="</head>";



$f[]="<body class=\"wrapper\" onload=\"initial();\">";
    $f[]="<form method=\"post\" name=\"form\" action=\"$page\" target=\"\">";

    $FORM_ACCEPTABLE=true;

    if(GeoIPCheck()){
        $FORM_ACCEPTABLE=false;
    }

    $f[]="<div class=\"div_table main_field_gap\">";
    $f[]="<div class=\"div_tr\">";
    $f[]="<div class=\"title_name\">";
    $f[]="<div class=\"div_td img_gap\">";
    $f[]="<div class=\"login_img\"></div>";
    $f[]="</div>";
    if($FORM_ACCEPTABLE) {
        $f[] = "<div class=\"div_td\">$TitleLogon</div>";
    }
    $f[]="</div>";


    clean_xss_deep();



    if(!is_null($error)){
        $errorSafe = htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
        $TextOfArticaPageSafe="<span style='color:#ee8d8d;font-weight: bolder'>$errorSafe</span>";
    }


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/fingerprint/captcha"));
    if(!is_object($json) || !$json->Status){
        $errMsg = is_object($json) ? htmlspecialchars($json->Error ?? '', ENT_QUOTES, 'UTF-8') : "API error";
        $f[]=$tpl->div_error($errMsg);
    }

    // Escape API response values for safe HTML output
    $captchaImage = is_object($json) ? preg_replace('/[^a-zA-Z0-9+\/=]/', '', $json->Image ?? '') : '';
    $captchaId    = is_object($json) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $json->Id ?? '') : '';
    $visitorIdSafe = htmlspecialchars($visitorId, ENT_QUOTES, 'UTF-8');

    if($FORM_ACCEPTABLE) {
        $f[]="<div class=\"LoginTitle2\">$TitleOfArticaPageSafe</div>";
        $f[] = "<div id=\"login_filed\">";
        $f[] = "<div class=\"p1 title_gap\">$TextOfArticaPageSafe";
        $f[] = "<div style='background-color: white;text-align: center;margin: 20px;padding: 10px;   border-radius: 5px;width: 80%;'>";
        $f[]="<img src='data:image/png;base64,$captchaImage'>";
        $f[] = "</div>";
        $f[]="</div>";
        $f[]="<input type='hidden' id='uid' value='$visitorIdSafe' name='uid'>";
        $f[]="<input type='hidden' id='captchakey' value='$captchaId' name='captchakey'>";
        $f[] = "<div id=\"name_title_ie\" style=\"display:none;margin:20px 0 -10px 78px;\" class=\"p1 title_gap\">{image_text}</div>";
        $f[] = "<div class=\"title_gap\">";
        $f[] = "<input type=\"text\" id=\"captchauser\" name=\"captchauser\" tabindex=\"1\" class=\"form_input\"autocapitalize=\"off\" autocomplete=\"off\" placeholder=\"{image_text}\" >";
            $f[] = "</div>";


        $f[] = "<div class=\"button\" onclick=\"PostIt();\">$title_button&nbsp;&raquo;&raquo;</div>";
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
    echo $tpl->_ENGINE_parse_body($f);
    return true;
}

function validate():bool{
    clean_xss_deep();
    $visitorId="";
    if(isset($_COOKIE["visitorId"])){
        $visitorId = preg_replace('/[^a-zA-Z0-9]/', '', $_COOKIE["visitorId"]);
    }

    if(isset($_POST["uid"])){
        $visitorId = preg_replace('/[^a-zA-Z0-9]/', '', $_POST["uid"]);
    }

    if(strlen($visitorId)<10){
        return captcha(false,"Please activate Cookies!");
    }

    // Sanitize POST inputs before using in URL path
    $captchaKey  = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST["captchakey"] ?? '');
    $captchaUser = preg_replace('/[^a-zA-Z0-9]/', '', $_POST["captchauser"] ?? '');

    if(empty($captchaKey) || empty($captchaUser)){
        return captcha(false,"{error}");
    }

    webconsole_syslog("Check Captcha " . substr($visitorId, 0, 32) . "...",__FILE__);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/fingerprint/verify/$captchaKey/$captchaUser/$visitorId"));
    if(!is_object($json) || !$json->Status){
        webconsole_syslog("Check Captcha " . substr($visitorId, 0, 32) . " Failed",__FILE__);
        $errMsg = is_object($json) ? ($json->Error ?? "{error}") : "{error}";
        return captcha(false,$errMsg);
    }

    $mem=new lib_memcached();
    $mem->saveKey("CAPTCHA:$visitorId",9999,1800);

    echo "<html>
	<head><META HTTP-EQUIV=\"Refresh\" CONTENT=\"0; URL=/\"> </head>
	<body>
	<script>document.location.href='/';</script>
	</body>
	</html>";
    return true;
}

function GetRemoteIP():string{
    $IPADDR="";
    if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
    if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
    // Sanitize: only allow valid IP characters
    return preg_replace('/[^0-9a-fA-F.:,]/', '', $IPADDR);
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

    // Sanitize for log injection: strip newlines and control characters
    $uid = preg_replace('/[\x00-\x1f\x7f]/', '', $uid ?? '');
    $IPADDR=GetRemoteIP();
    $succes = preg_replace('/[\x00-\x1f\x7f]/', '', $succes);

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

    $ArticaWebDenyCountries=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaWebDenyCountries")));
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
