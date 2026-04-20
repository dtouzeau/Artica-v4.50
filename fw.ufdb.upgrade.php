<?php
include_once(dirname(__FILE__) . "/ressources/class.template-admin.inc");
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"] = new sockets();

if(isset($_GET["popup"])){popup();exit;}
js();


function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog7("{APP_UFDBGUARDD}","$page?popup=yes");
}

function popup():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();

    $info=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/webfilter/latestver"),
        true);

    if(!isset($info["Version"])){
        return "";
    }
    $NeVer=$info["Version"];
    if($NeVer==""){
        return "";
    }
    $Curver=$info["CurVer"];

    $Token="DisableUfdbguardV$NeVer";
    if($GLOBALS["CLASS_SOCKETS"]->GET_INFO($Token)){
        return "";
    }
    $STEXT = $tpl->_ENGINE_parse_body("{NEW_VERSION_TEXT}");
    $STEXT = str_replace("%product", "{APP_UFDBGUARDD}", $STEXT);
    $STEXT = str_replace("%ver", $Curver, $STEXT);
    $STEXT = str_replace("%next", $NeVer, $STEXT);

    $js=$tpl->framework_buildjs("/webfilter/upgrade",
        "ufdb.upgrade.progress",
        "ufdb.upgrade.progress.log",
        "ufdbg-upgrade-progress","dialogInstance7.close();");

    $btn=$tpl->button_autnonome("{install} $NeVer",$js,ico_cd,"AsSquidAdministrator",350,"btn-primary",80);
    $html[]="<div id='ufdbg-upgrade-progress'></div>";
    $html[]=$tpl->div_info("v$NeVer||<p>$STEXT</p><div class='center' style='margin:20px'>$btn</div>");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}