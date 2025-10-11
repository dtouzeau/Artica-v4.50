<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["CGuardLicense"])){Save();exit;}
if(isset($_GET["remove-js"])){remove_js();exit;}
if(isset($_POST["DELETE"])){remove();exit;}
if(isset($_GET["getlist-js"])){getlist_js();exit;}
if(isset($_GET["get-list-popup"])){getlist_popup();exit;}
js();


function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog4("{use_lemnia_cloud_service}","$page?popup=yes");
}

function remove_js(){
    $tpl=new template_admin();
    $js="LoadAjax('table-loader-catz-pages','fw.proxy.categories.services.php?table=yes');";
    $tpl->js_dialog_confirm_action("{remove_service} {use_lemnia_cloud_service}","DELETE","YES",$js);
}

function remove(){
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("useCGuardCategories",0);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("cguard.php?remove=yes");


}

function getlist_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog4("{use_lemnia_cloud_service}","$page?get-list-popup=yes");

}
function getlist_popup(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $TRCLASS=null;
    $t=time();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";

    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ID}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{items}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $CguardCatzData=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CguardCatzData")));

    foreach ($CguardCatzData as $ID=>$array){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        if(!isset($array["ITEMS"])){continue;}
        $NAME=$array["NAME"];
        $DESC=$array["DESC"];
        $md=md5(serialize($array));
        $ITEMS=$tpl->FormatNumber($array["ITEMS"]);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%' style='text-align: center' nowrap=''>$ID</td>";
        $html[]="<td width='1%' style='text-align: left' nowrap=''><strong>$NAME</strong></td>";
        $html[]="<td width='99%'  style='text-align: left' >$DESC</td>";
        $html[]="<td width='1%'  style='text-align: right' nowrap=''>$ITEMS</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function popup(){
    $tpl=new template_admin();
    $CGuardLicense=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CGuardLicense"));
    $uuid=base64_decode($GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?system-unique-id=yes"));
    $t=time();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/cguard.validator.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/cguard.validator.logs";
    $ARRAY["CMD"]="cguard.php?validator=yes";
    $ARRAY["TITLE"]="{checking}";
    $ARRAY["AFTER"]="dialogInstance4.close();LoadAjax('table-loader-catz-pages','fw.proxy.categories.services.php?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?content=$prgress&mainid=cguard-progress-$t')";

    $use_lemnia_cloud_service=$tpl->_ENGINE_parse_body("{use_lemnia_cloud_service_explain}");
    $url="https://artica.cguard-protect.com/FR/?uid=$uuid";
    $uri="<a href=\"$url\" target='_new'>$url</a>";
    $use_lemnia_cloud_service=str_replace("%uricguard%",$uri,$use_lemnia_cloud_service);
    $html[]="<div id='cguard-progress-$t'></div>";
    $form[]=$tpl->field_text("CGuardLicense","{key}",$CGuardLicense,true);

    $tpl->form_add_button("{create_cguard_key}","s_PopUpFull('$url','1024','900');");

    $html[]=$tpl->form_outside("{use_lemnia_cloud_service}", $form,$use_lemnia_cloud_service,"{apply}",$jsafter,"AsSquidAdministrator");

    echo $tpl->_ENGINE_parse_body($html);

}

function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}