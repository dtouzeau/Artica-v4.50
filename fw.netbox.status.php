<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["form-config-js"])){form_config_js();exit;}
if(isset($_GET["form-config-popup"])){form_config_popup();exit;}
if(isset($_GET["tsigkeys-search"])){tsigkeys_table();exit;}
if(isset($_GET["netbox-status"])){service_status();exit;}
if(isset($_POST["NetboxDefaultSiteID"])){form_config_save();exit;}
if(isset($_GET["device-role-js"])){device_role_js();exit;}
if(isset($_GET["device-role-popup"])){device_role_popup();exit;}
if(isset($_POST["deviceroleid"])){device_role_save();exit;}

if(isset($_GET["devtype-js"])){devtype_js();exit;}
if(isset($_GET["devtype-popup"])){devtype_popup();exit;}
if(isset($_POST["devtypeid"])){devtype_save();exit;}



if(isset($_GET["site-js"])){site_js();exit;}
if(isset($_GET["site-popup"])){site_popup();exit;}
if(isset($_POST["siteid"])){site_save();exit;}



if(isset($_GET["flat-start"])){flat_start();exit;}
if(isset($_GET["netbox-flat"])){flat_config();exit;}
if(isset($_POST["dhpddns"])){ddns_save();exit;}

if(isset($_GET["search"])){table();exit;}
if(isset($_GET["enable-signature"])){enable_signature();exit;}
if(isset($_GET["enable-firewall"])){enable_firewall();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
if(isset($_GET["search-form"])){search_form();exit;}
if(isset($_GET["secretkey-js"])){secretkey_js();exit;}
if(isset($_GET["secretkey-start"])){secretkey_start();exit;}
if(isset($_GET["secretkey-popup"])){secretkey_popup();exit;}
if(isset($_GET["enable"])){enable();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_POST["delete"])){delete_perform();exit;}

page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_NETBOX}","fas fa-box-full","{APP_NETBOX_EXPLAIN}","$page?flat-start=yes","netbox-status","progress-netbox-restart");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_NETBOX}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}
function device_role_js():bool{
    $id=intval($_GET["device-role-js"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{device_role}","$page?device-role-popup=$id");
}
function site_js():bool{
    $id=intval($_GET["site_js"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{site}","$page?site-popup=$id");
}
function devtype_js():bool{
    $id=intval($_GET["devtype-js"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{device_type}","$page?devtype-popup=$id");
}
function devtype_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $btn="{apply}";
    $id=intval($_GET["devtype-popup"]);
    if($id==0){
        $btn="{add}";
    }
    $form[]=$tpl->field_hidden("devtypeid",$id);
    $form[]=$tpl->field_text("devtypename","{name}","");
    $form[]=$tpl->field_text("Comments","{description}","");
    $form[]=$tpl->field_text("Model","{model}","");
    echo $tpl->form_outside("",$form,"{device_type_explain}",$btn,"dialogInstance1.close();LoadAjax('netbox-flat','$page?netbox-flat=yes')","");
    return true;
}
function devtype_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $sock=new sockets();
    $post=base64_encode(serialize($_POST));
    $data=json_decode($sock->REST_API("/netbox/devtype/add/$post"));
    if(!$data->Status){
        echo $tpl->post_error($data->Error);
        return false;
    }
    return admin_tracks_post("Add a new Device type");
}

function site_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $btn="{apply}";
    $id=intval($_GET["site-popup"]);
    if($id==0){
        $btn="{add}";
    }
    $form[]=$tpl->field_hidden("siteid",$id);
    $form[]=$tpl->field_text("sitename","{name}","");
    echo $tpl->form_outside("",$form,"",$btn,"dialogInstance1.close();LoadAjax('netbox-flat','$page?netbox-flat=yes')","");
    return true;
}
function site_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $sock=new sockets();
    $post=base64_encode(serialize($_POST));
    $data=json_decode($sock->REST_API("/netbox/site/add/$post"));
    if(!$data->Status){
        echo $tpl->post_error($data->Error);
        return false;
    }
    return admin_tracks_post("Add a new Netbox site");

}

function form_config_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{parameters}","$page?form-config-popup=yes");
}
function form_config_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $data=json_decode($sock->REST_API("/netbox/sites"));
    $NetboxDefaultSiteID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetboxDefaultSiteID"));
    $NetboxDefaultRole=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetboxDefaultRole"));
    $NetboxDefaultDeviceType=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetboxDefaultDeviceType"));
    $HashRoles=array();
    $HashSites=array();
    $HashDevtypes=array();
    foreach ($data->Info as $array){
        $id=$array->id;
        $name=$array->name;
        $HashSites[$id]=$name;
    }

    $data=json_decode($sock->REST_API("/netbox/roles"));
    foreach ($data->Info as $array){
        $id=$array->id;
        $name=$array->name;
        $HashRoles[$id]=$name;
    }
    $data=json_decode($sock->REST_API("/netbox/devtypes"));

    foreach ($data->Info as $array){
        $id=$array->id;
        $name=$array->slug;
        $comments=$array->comments;
        $HashDevtypes[$id]="$name ($comments)";
    }



    $form[]=$tpl->field_array_hash($HashSites,"NetboxDefaultSiteID","{site}",$NetboxDefaultSiteID);
    $form[]=$tpl->field_array_hash($HashRoles,"NetboxDefaultRole","{device_role}",$NetboxDefaultRole);
    $form[]=$tpl->field_array_hash($HashDevtypes,"NetboxDefaultDeviceType","{device_type}",$NetboxDefaultRole);

    echo $tpl->form_outside("",$form,"","{apply}","dialogInstance1.close();LoadAjax('netbox-flat','$page?netbox-flat=yes')","");

return true;
}
function form_config_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return admin_tracks_post("Save Artica Netbox automation");
}

function device_role_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $btn="{apply}";
    $id=intval($_GET["device-role-popup"]);
    if($id==0){
        $btn="{add}";
    }
    $form[]=$tpl->field_hidden("deviceroleid",$id);
    $form[]=$tpl->field_text("devicerolename","{name}","");
    $form[]=$tpl->field_text("deviceroledesc","{description}","");
    echo $tpl->form_outside("",$form,"",$btn,"dialogInstance1.close();LoadAjax('netbox-flat','$page?netbox-flat=yes')","");
    return true;
}
function device_role_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $sock=new sockets();
    $post=base64_encode(serialize($_POST));
    $data=json_decode($sock->REST_API("/netbox/role/add/$post"));
    if(!$data->Status){
        echo $tpl->post_error($data->Error);
        return false;
    }
    return admin_tracks_post("Add a new Netbox device role");

}

function flat_config(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $error=array();
    $NetboxDefaultSiteID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetboxDefaultSiteID"));
    $NetboxDefaultRole=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetboxDefaultRole"));
    $NetboxDefaultDeviceType=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NetboxDefaultDeviceType"));

    if($NetboxDefaultSiteID==0){
        $error[]="{you_must_choose_asite}";

    }
    if($NetboxDefaultRole==0){
        $error[]="{you_must_choose_adevicerole}";
    }
    if($NetboxDefaultDeviceType==0){
        $error[]="{you_must_choose_adevicetype}";
    }

    if(count($error)>0){
        echo $tpl->div_error(@implode("<br>",$error));
    }




    $tpl->table_form_field_js("Loadjs('$page?form-config-js=yes')");
    $tpl->table_form_section("{automation}","{netbox_automation_explain}");

    if($NetboxDefaultSiteID==0) {
        $tpl->table_form_field_text("{site}"," {none}", ico_sitemap,true);
    }else {
        $data=json_decode($sock->REST_API("/netbox/siteinfo/$NetboxDefaultSiteID"));
        $device_count=intval($data->Info->device_count);
        $tpl->table_form_field_text("{site}", $data->Info->name ." <small>($device_count {devices})</small>", ico_sitemap);
    }
    if($NetboxDefaultRole==0) {
        $tpl->table_form_field_text("{device_role}", "{none}", ico_computer,true);
    }else{
        $data=json_decode($sock->REST_API("/netbox/device/role/$NetboxDefaultRole"));
        $tpl->table_form_field_text("{device_role}", $data->Info->name, ico_computer);
    }

    if($NetboxDefaultDeviceType==0){
        $tpl->table_form_field_text("{device_type}", "{none}", ico_computer_down,true);

    }else{
        $data=json_decode($sock->REST_API("/netbox/device/type/$NetboxDefaultDeviceType"));
        $tpl->table_form_field_text("{device_type}", $data->Info->slug." ({$data->Info->comments})", ico_computer);
    }


    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());


}
function flat_start(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:350px;vertical-align:top;'>";
    $html[]="<div id='netbox-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>";
    $html[]="<div id='netbox-flat'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";

    $topbuttons[] = array("Loadjs('$page?site-js=0')", ico_plus, "{site}");
    $topbuttons[] = array("Loadjs('$page?device-role-js=0')", ico_plus, "{device_role}");
    $topbuttons[] = array("Loadjs('$page?devtype-js=0')", ico_plus, "{device_type}");
    $topbuttons[] = array("s_PopUp('/netbox',1024,768);", ico_html, "{webadministration_console}");





    $TINY_ARRAY["TITLE"]="{APP_NETBOX}";
    $TINY_ARRAY["ICO"]="fas fa-box-full";
    $TINY_ARRAY["EXPL"]="{APP_NETBOX_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $js_refresh=$tpl->RefreshInterval_js("netbox-status",$page,"netbox-status=yes");

    $html[]=$jstiny;
    $html[]=$js_refresh;
    $html[]="LoadAjax('netbox-flat','$page?netbox-flat=yes')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function service_status():bool{
    $tpl=new template_admin();


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netbox/status"));
    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }
    if (!$json->Status) {
        return $tpl->widget_rouge("{error}", $json->Error);
    }
    $ini=new Bs_IniHandler();
    $ini2=new Bs_IniHandler();
    $ini->loadString($json->Redis);
    $ini2->loadString($json->Main);

    $jsrestart=$tpl->framework_buildjs("/netbox/restart",
        "netbox.restart.progress",
        "netbox.restart.progress.log",
        "progress-netbox-restart",
        ""
    );
    echo $tpl->SERVICE_STATUS($ini2, "APP_NETBOX",$jsrestart);
    echo $tpl->SERVICE_STATUS($ini, "APP_NETBOXCACHE","");
    return true;
}





