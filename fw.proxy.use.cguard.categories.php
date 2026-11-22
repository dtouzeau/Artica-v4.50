<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
}
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
    // articarest /categories/cguard/remove : catégories, bases et réglages CGuard (synchrone).
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/categories/cguard/remove"));
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        echo (is_object($json) && isset($json->Error))?$json->Error:"articarest: ".json_last_error_msg();
        return;
    }
    admin_tracks("Remove the CGuard categories service");


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
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
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
        $TRCLASS=null;
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
    $html[]="</table></div></div></div>";
    $html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";

    echo awc_table_style().$tpl->_ENGINE_parse_body($html);
}

function popup(){
    $tpl=new template_admin();
    $CGuardLicense=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CGuardLicense"));
    $uuid= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID"));
    $t=time();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/cguard.validator.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/cguard.validator.logs";
    $ARRAY["CMD"]="/categories/cguard/validate";
    $ARRAY["TITLE"]="{checking}";
    $ARRAY["AFTER"]="dialogInstance4.close();LoadAjax('table-loader-catz-pages','fw.proxy.categories.services.php?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=cguard-progress-$t')";

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