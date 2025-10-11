<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["popup"])){popup();exit;}
js();


function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    //UseCloudArticaCategories_text
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        $tpl->js_error("{onlycorpavailable}");
        return;
    }

    $tpl->js_dialog4("{UseCloudArticaCategories}","$page?popup=yes");

}
function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $config["PROGRESS_FILE"]=PROGRESS_DIR."/filebeat.progress";
    $config["LOG_FILE"]=PROGRESS_DIR."/filebeat.log";
    $config["CMD"]="filebeat.php?cloud-install=yes";
    $config["TITLE"]="{UseCloudArticaCategories}";
    $config["AFTER"]="dialogInstance4.close();if(document.getElementById('table-loader-catz-pages')){LoadAjax('table-loader-catz-pages','fw.proxy.categories.services.php?table=yes');}";
    $prgress=base64_encode(serialize($config));
    $jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=UseCloudArticaCategories-progress')";

    $html[]="<div style='margin-top:20px;maring-bottom:20px' id='UseCloudArticaCategories-progress'></div>";
    $html[]="<div class='alert alert-info' style='margin-bottom: 10px'>{UseCloudArticaCategories_text}</div>";
    $html[]="<div style='text-align:right'>".$tpl->button_autnonome("{start_install}",$jsafter,ico_cd,"AsSquidAdministrator")."</div>";

    echo $tpl->_ENGINE_parse_body($html);

}