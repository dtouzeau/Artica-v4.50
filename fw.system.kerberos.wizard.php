<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.ActiveDirectoryRootDSE.inc");
include_once(dirname(__FILE__)."/ressources/class.activedirectory.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
if(isset($_POST["WINDOWS_SERVER_ADMIN"])){Save();exit;}
if(isset($_POST["LDAP_SSL"])){Save();exit;}
if(isset($_POST["fullhosname"])){Save();exit;}
if(isset($_POST["COMPUTER_BRANCH"])){Save();exit;}


if(isset($_GET["step0"])) {step0();exit;}
if(isset($_GET["step2"])) {step2();exit;}
if(isset($_GET["step1"])) {step1();exit;}
if(isset($_GET["step3"])) {step3();exit;}
if(isset($_GET["step4"])) {step4();exit;}
if(isset($_GET["step5"])) {step5();exit;}
if(isset($_GET["js"])) {js();exit;}

function js(): bool {
    $tpl  = new template_admin();
    $page = CurrentPageName();
    return $tpl->js_dialog6("{wizard}: Active Directory", "$page?step0=yes", 750);
}

function step0():bool{
    $page = CurrentPageName();
    $html[]="<div style='margin:20px' id='kerberos-wizard'></div>";
    $html[]="<script>LoadAjaxSilent('kerberos-wizard','$page?step1=yes');</script>";
    echo @implode("\n", $html);
    return true;
}
function step2():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();

    $js_help="s_PopUpFull('https://wiki.articatech.com/en/active-directory/grant-proxy-user','1024','900');";
    $btnWiki=$tpl->button_autnonome("wiki.articatech.com", $js_help, ico_support,"AsSystemAdministrator",335);
    $html[]="<H2>{welcome_ad_wizard}</H2>";
    $html[]="<div style='font-size:16px'>{welcome_ad_wizard2}<br>{welcome_ad_wizard_wiki}";
    $html[]="<div style='margin-top:20px;text-align:right;margin-right:20px'>$btnWiki</div>";
    $html[]="</div>";

    $admin="";

    if($admin==""){
        $array = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
        if (!is_array($array)) {
            $array["WINDOWS_SERVER_ADMIN"]="";
            $array["WINDOWS_SERVER_PASS"]="";
        }
        $admin=$array["WINDOWS_SERVER_ADMIN"];
        $password=$array["WINDOWS_SERVER_PASS"];

    }
    $form[] = $tpl->field_text("WINDOWS_SERVER_ADMIN", "{administrator}", $admin, true);
    $form[] = $tpl->field_password("WINDOWS_SERVER_PASS", "{password}", $password, true);
    $tpl->form_add_button("{back}","LoadAjaxSilent('kerberos-wizard','$page?step1=yes')");
    $html[]=$tpl->form_outside("",$form,"","{next}",
        "LoadAjaxSilent('kerberos-wizard','$page?step3=yes');",
        "AsSystemAdministrator");


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function step1():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $html[]="<p style='font-size:16px'>{welcome_ad_wizard1}</p>";

    $adServer="";

    $array = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if (!is_array($array)) {
        $array["fullhosname"]="";
    }
    $adServer=$array["fullhosname"];

    if($adServer=="") {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/kerberos/find-the-ad"), true);
        if (isset($json["AdServer"])) {
            $adServer = $json["AdServer"];
        }
    }

    $form[]=$tpl->field_text("fullhosname","{ad_full_hostname}",$adServer,true);

    $html[]=$tpl->form_outside("",$form,"","{next}",
        "LoadAjaxSilent('kerberos-wizard','$page?step2=yes');",
        "AsSystemAdministrator");


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function step3():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $html[]="<p style='font-size:16px'>{welcome_ad_wizard3}</p>";
    $array = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    if(!isset($array["LDAP_SSL"])){
        $array["LDAP_SSL"]=0;
    }
    $form[] = $tpl->field_checkbox("LDAP_SSL", "{enable_ssl} (port 636)", $array["LDAP_SSL"], false, null);
    $tpl->form_add_button("{back}","LoadAjaxSilent('kerberos-wizard','$page?step2=yes')");
    $html[]=$tpl->form_outside("",$form,"","{next}",
        "LoadAjaxSilent('kerberos-wizard','$page?step4=yes');",
        "AsSystemAdministrator");


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function step4():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    $html[]="<p style='font-size:16px'>{welcome_ad_wizard4}</p>";
    $array = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/kerberos/wizard/rootdse"),true);
    if (!isset($array["COMPUTER_BRANCH"])) {$array["COMPUTER_BRANCH"] = "CN=Computers";}
    if(strlen($json["Suffix"])>0){
        $array["LDAP_SUFFIX"]=$json["Suffix"];
    }

    $form[] = $tpl->field_text("COMPUTER_BRANCH", "{ad_computers_branch}", $array["COMPUTER_BRANCH"], false, null);
    $form[] = $tpl->field_text("LDAP_SUFFIX", "{suffix}", $array["LDAP_SUFFIX"], false, null);
    $tpl->form_add_button("{back}","LoadAjaxSilent('kerberos-wizard','$page?step3=yes')");
    $html[]=$tpl->form_outside("",$form,"","{next}",
        "LoadAjaxSilent('kerberos-wizard','$page?step5=yes');",
        "AsSystemAdministrator");

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function step5():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $array = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/mktutils/isadmin"),true);
    $IsAdmin=0;
    if($json["IsAdmin"]){
        $IsAdmin=1;
    }

    $tpl->table_form_field_text("{hostname}",php_uname("n"),ico_server);
    $tpl->table_form_field_text("{ad_full_hostname}",$array["fullhosname"]." - <small>".$array["ADNETIPADDR"]."</small>",ico_server);
    $tpl->table_form_field_text("{suffix}",$array["LDAP_SUFFIX"],ico_earth);
    $tpl->table_form_field_bool("{UseSSL}",$array["LDAP_SSL"],"fab fa-expeditedssl");
    $tpl->table_form_field_text("{ad_computers_branch}",$array["COMPUTER_BRANCH"],"fas fa-folder-tree");
    $tpl->table_form_field_text("{username}",$array["WINDOWS_SERVER_ADMIN"],ico_user);
    $tpl->table_form_field_bool("{administrator_account}",$IsAdmin,ico_admin);


    $backButton=$tpl->button_autnonome("{back}",
        "LoadAjaxSilent('kerberos-wizard','$page?step4=yes');",ico_exit,
        "AsSquidAdministrator",200,"btn-primary");

    $jsAfter[]="LoadAjax('table-kerberos-single','fw.system.kerberos-single.php?table=yes')";
    $jsAfter[]="LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes')";
    $jsAfter[]="dialogInstance6.close()";

    $compile=$tpl->framework_buildjs("/mktutils/connect",
        "msktutils.progress","msktutils.log",
        "mktutils-connect-progress",
        implode(";",$jsAfter));


    $nextButton=$tpl->button_autnonome("{connect}",
        $compile,ico_link,
        "AsSquidAdministrator",200,"btn-primary");


     $html[]="<div id='mktutils-connect-progress'>";
    $html[]=$tpl->table_form_compile();
    $html[]="</div>";
    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:50%;padding-left:20px;text-align:left'>$backButton</td>";
    $html[]="<td style='width:50%;padding-right:20px;text-align:right'>$nextButton</td>";
    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $array = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    if(isset($_POST["fullhosname"])){
        $host=urlencode($_POST["fullhosname"]);
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/kerberos/ad/validate/$host"),true);
        if(!$json["Status"]){
            echo $tpl->post_error($json["Error"]);
            return false;
        }
        $_POST["ADNETIPADDR"]=$json["ipaddr"];

    }
    if(isset($_POST["WINDOWS_SERVER_ADMIN"])){
        $adm=$_POST["WINDOWS_SERVER_ADMIN"];
        if(strpos($adm,"@")==0){
            $adname=explode(".",$array["fullhosname"]);
            unset($adname[0]);
            $_POST["WINDOWS_SERVER_ADMIN"]="$adm@".@implode(".",$adname);
        }
    }


    foreach ($_POST as $key => $value) {
        $array[$key] = $value;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthInfos",base64_encode(serialize($array)));
    return admin_tracks("Setup the Active Directory connection wizard");
}