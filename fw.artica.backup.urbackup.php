<?php
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["settings"])){settings();exit;}

page();


function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $TINY_ARRAY["TITLE"]="{APP_URBACKUP}";
    $TINY_ARRAY["ICO"]="fa fa-archive";
    $TINY_ARRAY["EXPL"]="{APP_URBACKUP_EXPLAIN}";
    $TINY_ARRAY["URL"]="snapshots";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<table style='width:100%;margin-top:20px;'>";
    $html[]="<tr>";
    $html[]="<td valign='top' style='width:250px;'><div id='urbackup-status'></div></td>";
    $html[]="<td valign='top' style='width:99%;'><div id='urbackup-settings' style='margin-left:15px'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="LoadAjax('urbackup-status','$page?status=yes');";
    $html[]="LoadAjax('urbackup-settings','$page?settings=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}
function settings():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $UrBackupPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrBackupPort"));
    $UrBackupPortSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrBackupPortSSL"));
    if($UrBackupPort==0){$UrBackupPort=9290;}
    $TEXT_PORT[]=$UrBackupPort;
    if($UrBackupPortSSL==0){$TEXT_PORT[]="HTTP";}
    $tpl->table_form_field_js("Loadjs('$page?section-port-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_text("{listen_port}",@implode(" ",$TEXT_PORT),ico_nic);


    echo $tpl->table_form_compile();
    return true;
}

function status():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("urbackup.php?status=yes");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ini=new Bs_IniHandler();
    $ini->loadFile(PROGRESS_DIR."/urbackupsrv.status");

    $restartService=$tpl->framework_buildjs(
        "urbackup.php?restart=yes",
        "urbackup.progress",
        "urbackup.log",
        "progress-snapshot-restart",
        "LoadAjax('urbackup-status','$page?status=yes');"

    );

    $html[]=$tpl->SERVICE_STATUS($ini, "APP_URBACKUP",$restartService);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}