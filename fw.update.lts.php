<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");
if(isset($_GET["popup"])){popup();exit;}

js();

function js():bool{


    $ArticaUpdateRepos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
    $key_lts=update_find_lts($ArticaUpdateRepos);
    if($key_lts==0){
        die();
    }

    $LTS=$ArticaUpdateRepos["LTS"];
    $Lastest=$LTS[$key_lts]["VERSION"];

    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog6("Artica LTS Version $Lastest","$page?popup=yes&version=$Lastest",650);
    return true;
}

function popup():bool{
    $tpl=new template_admin();
    $Lastest=$_GET["version"];

    $js=$tpl->framework_buildjs("artica.php?upgrade-lts=yes",
        "lts.progress",
        "lts.log",
        "lts-progress",
        "document.location.href='/fw.login.php?disconnect=yes'"
    );


    $NEW_LTS_TEXT=$tpl->_ENGINE_parse_body("{NEW_LTS_TEXT}");
    $NEW_LTS_TEXT=str_replace("%s",$Lastest,$NEW_LTS_TEXT);
    $html[]="<div id='lts-progress'>";
    $html[]=$tpl->div_explain($NEW_LTS_TEXT);
    $html[]="<p style='margin:30px'>";
    $html[]=$tpl->button_autnonome("{VPS_INSTALL_ARTICA} v.$Lastest",
        $js,ico_download,"AsSystemAdministrator",501);
    $html[]="</p>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}


function update_find_lts($array):int{
    if(!is_array($array["LTS"])){return 0;}
    $MAIN=$array["LTS"];$keyMain=0;foreach ($MAIN as $key=>$ligne){$key=intval($key);if($key==0){continue;}
        if($key>$keyMain){$keyMain=$key;}}
    return $keyMain;
}