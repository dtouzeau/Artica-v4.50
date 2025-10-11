<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.smtpd.notifications.inc");

$GLOBALS["CLASS_SOCKETS"]=new sockets();


if(isset($_GET["gmail-js"])){gmail_js();exit;}
if(isset($_GET["gmail-start"])){gmail_start();exit;}
if(isset($_GET["gmail-step1"])){gmail_step1();exit;}
if(isset($_GET["gmail-json"])){gmail_json();exit;}

if(isset($_GET["smtpdest-js"])){smtp_dest_js();exit;}
if(isset($_GET["smtp-dest-popup"])){smtp_dests_popup();exit;}

if(isset($_GET["smtp-main-js"])){smtp_main_js();exit;}
if(isset($_GET["smtp-main-popup"])){smtp_main_popup();exit;}
if(isset($_POST["smtp_sender"])){smtp_save();exit;}
if(isset($_POST["smtp_server_name"])){smtp_save();exit;}
if(isset($_POST["smtp2go"])){smtp_save();exit;}
if(isset($_GET["smtp2go-js"])){smtp2go_js();exit;}
if(isset($_GET["smtp2go-popup"])){smtp2go_popup();exit;}
if(isset($_GET["file-uploaded"])){smtp_import_file_uploaded();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["smtp-export-js"])){smtp_export();exit;}
if(isset($_GET["smtp-import-js"])){smtp_import_js();exit;}
if(isset($_GET["smtp-import-popup"])){smtp_import_popup();exit;}
if(isset($_GET["smtp-parameters-js"])){smtp_parameters_js();exit;}
if(isset($_GET["smtp-parameters-popup"])){smtp_parameters_popup();exit;}
if(isset($_GET["smtp-static"])){smtp_static();exit;}
if(isset($_GET["status"])){status();exit;}

if(isset($_POST["ENABLED_SQUID_WATCHDOG"])){smtp_save();exit;}
if(isset($_POST["id-save"])){id_save();exit;}
if(isset($_GET["id-js"])){id_js();exit;}
if(isset($_GET["id-popup"])){id_popup();exit;}
if(isset($_GET["id-delete"])){id_delete();exit;}
if(isset($_POST["id-delete"])){id_delete_perform();exit;}
if(isset($_GET["enable"])){id_enable();exit;}

if(isset($_GET["rules-start"])){rules_start();exit;}
if(isset($_GET["rules"])){rules();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search"])){events_search();exit;}
if(isset($_GET["smtp-tests-js"])){smtp_test_message();exit;}


function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{parameters}"]="$page?table=yes";
    $array["{rules_message_notifs}"]="$page?rules-start=yes";
    $array["{events}"]="$page?events=yes";
    echo $tpl->tabs_default($array);
    return true;
}

page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{notifications}",
        "fa fa-envelope","{notifications_explain}",
        "$page?tabs=yes",
        "notifications","progress-notifications-restart",
        false,"table-loader-notifs-pages");



	if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return true;}
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function smtp_parameters_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
   return  $tpl->js_dialog2("{parameters}","$page?smtp-parameters-popup=yes");
}
function smtp2go_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog2("SMTP2GO","$page?smtp2go-popup=yes");
}
function smtp_main_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog2("{main_parameters}","$page?smtp-main-popup=yes");
}
function smtp_dest_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog2("{main_parameters}","$page?smtp-dest-popup=yes");
}
function gmail_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog2("GMail","$page?gmail-start=yes");
}
function gmail_start(){
    $page=CurrentPageName();
    echo "<div id='gmail-wizard'></div>
    <script>LoadAjax('gmail-wizard','$page?gmail-step1=yes');</script>";
}
function gmail_step1(){
    $UfdbguardSMTPNotifs=smtp_defaults();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $gmailjson=$UfdbguardSMTPNotifs["gmailjson"];
    if(strlen($gmailjson)<10){
        $html[]="<div class=center style='margin:30px'>".$tpl->button_upload("{upload file} (json)",$page,null,"&gmail-json=yes")."</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    $GMAIL_AUTH_URL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GMAIL_AUTH_URL");
    if(strlen($GMAIL_AUTH_URL)<10){
        echo $tpl->div_error("Cannot get Authentication url...");
        return true;
    }
    $AUTHKEY=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GMAIL_AUTH_KEY");
    if(strlen($AUTHKEY)<5){
        $html[]=$tpl->div_wizard("{GMAIL_AUTH_KEY}");
        $html[]="<div><a href='$GMAIL_AUTH_URL'>$GMAIL_AUTH_URL</a></div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }



}
function gmail_json():bool{
    $FileName=$_GET["file-uploaded"];
    $tpl=new template_admin();
    $jsonfile="/usr/share/artica-postfix/ressources/conf/upload/$FileName";
    $jsonData=file_get_contents($jsonfile);
    $json=json_decode($jsonData);
    @unlink($jsonfile);
    if(json_last_error()>JSON_ERROR_NONE){
        $tpl->js_error("Not a json file");
        return true;
    }
    if(!property_exists($json,"web")){
        $tpl->js_error("Not a Google json file");
        return true;
    }

    if(!property_exists($json->web,"auth_uri")){
        $tpl->js_error("Not a Google json file");
        return true;
    }
    if(!property_exists($json->web,"redirect_uris")){
        $tpl->js_error("Missing redirect_uris token");
        return true;
    }

    $UfdbguardSMTPNotifs=smtp_defaults();
    $UfdbguardSMTPNotifs["GMAIL_AUTH_URL"]="";
    $UfdbguardSMTPNotifs["gmailjson"]=base64_encode($jsonData);
    $newparam=base64_encode(serialize($UfdbguardSMTPNotifs));
    $sock=new sockets();
    $sock->SaveConfigFile($newparam, "UfdbguardSMTPNotifs");

    $page=CurrentPageName();
    echo "LoadAjax('gmail-wizard','$page?gmail-step1=yes');";
    $sock=new sockets();
    $sock->REST_API("/gmail/tok");
    return  admin_tracks_post("Save GMail Token identification");
}

function smtp_import_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return  $tpl->js_dialog2("{parameters}:{import}","$page?smtp-import-popup=yes",450);
}
function smtp_import_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<div class=center style='margin:30px'>".$tpl->button_upload("{upload file}",$page)."</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function smtp_import_file_uploaded(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $file=$_GET["file-uploaded"];
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$file";
    $data=@file_get_contents($fullpath);
    @unlink($fullpath);

    if(strlen($data)<50){
        $tpl->js_error("{corrupted} ERR_ZERO_SIZE_OBJECT");
        return false;
    }

    $UfdbguardSMTPNotifs=unserialize(base64_decode($data));
    if(!is_array($UfdbguardSMTPNotifs)){
        $tpl->js_error("{corrupted}");
        return false;
    }

    if(!isset($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"])){
        $tpl->js_error("{corrupted}");
        return false;
    }

    admin_tracks("Import uploaded SMTP notifications settings using server {$_POST["smtp_server_name"]}");
    $newparam=base64_encode(serialize($UfdbguardSMTPNotifs));
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile($newparam, "UfdbguardSMTPNotifs");

    $jsrestart=$tpl->framework_buildjs("articasmtp.php?restart=yes",
        "articanotifs.progress",
        "articanotifs.log","progress-notifications-restart",
        "LoadAjaxSilent('smtp-notifs-form','$page?smtp-static=yes');"
    );
    header("content-type: application/x-javascript");
    echo "dialogInstance2.close();LoadAjaxSilent('smtp-notifs-form','$page?smtp-static=yes');";
    echo $jsrestart;
    return true;
}
function smtp_test_message():bool{
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    echo $tpl->framework_buildjs("/articanotifs/testnotifs",
        "articasmtp.test.progress","articasmtp.test.log","progress-notifications-restart");
    return true;
}
function rules_start(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    echo "<div id='smt-rules-table' style='margin-top:15px'></div>
    <script>LoadAjaxSilent('smt-rules-table','$page?rules=yes')</script>
    ";

}
function id_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id-js"]);
    if($id==0){
        $title="{new_rule}";
    }else{
        $title="{rule} $id";
    }

    $tpl->js_dialog1($title,"$page?id-popup=$id");
}
function id_enable(){
    $tpl=new template_admin();
    $id=intval($_GET["enable"]);
    $field=$_GET["f"];
    $q=new lib_sqlite("/home/artica/SQLITE/webconsole.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM smtp_notifications WHERE ID=$id");
    if($ligne[$field]==1){
        admin_tracks("Set $field as disable for SMTP notification rule $id");
        $q->QUERY_SQL("UPDATE smtp_notifications SET $field=0 WHERE ID=$id");
    }else{
        admin_tracks("Set $field as enable for SMTP notification rule $id");
        $q->QUERY_SQL("UPDATE smtp_notifications SET $field=1 WHERE ID=$id");
    }

    if(!$q->ok){$tpl->js_error($q->mysql_error);return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    return true;

}
function id_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["id-popup"]);
    $bt="{apply}";
    if($id==0){
        $title="{new_rule}";
        $bt="{add}";
        $jsafter="dialogInstance1.close();LoadAjaxSilent('smt-rules-table','$page?rules=yes')";
    }else{
        $title="{rule} $id";
    }
    $q=new lib_sqlite("/home/artica/SQLITE/webconsole.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM smtp_notifications WHERE ID=$id");
    $recipients=trim($ligne["recipients"]);
    $sender=$ligne["sender"];
    $critic=$ligne["critic"];
    $warning=$ligne["warning"];
    $filters=$ligne["filters"];


    $form[]=$tpl->field_hidden("id-save",$id);
    $form[]=$tpl->field_email("sender", "{smtp_sender}", $sender);
    $form[]=$tpl->field_text("recipients", "{recipients}", $recipients);
    $form[]=$tpl->field_checkbox("warning","{warning_events}",$warning);
    $form[]=$tpl->field_checkbox("critic","{critical_events}",$critic);
    $form[]=$tpl->field_textareacode("filters","{filters}",$filters);

    echo $tpl->form_outside("{smtp_notifications} $title", @implode("\n", $form),null,$bt,$jsafter,"AsSystemAdministrator");

}
function id_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite("/home/artica/SQLITE/webconsole.db");
    $id=intval($_POST["id-save"]);
    $fields[]="sender";
    $fields[]="recipients";
    $fields[]="warning";
    $fields[]="critic";
    $fields[]="filters";
    $atrck=null;
    foreach ($fields as $key){
        $datas[]="'".$q->sqlite_escape_string2($_POST[$key])."'";
        $edit[]="$key='".$q->sqlite_escape_string2($_POST[$key])."'";
        $atrck="$atrck $key={($_POST[$key]}";
    }
    if($id==0){
        $admin="Creating new SMTP notifications rule for $atrck";
        $sql="INSERT INTO smtp_notifications (".@implode(",",$fields).") VALUES (".@implode(",",$datas).")";
    }else{
        $admin="Updating SMTP notifications with $atrck";
        $sql="UPDATE smtp_notifications SET ".@implode(",",$edit)." WHERE ID=$id";
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "jserror:".$q->mysql_error."$sql";return false;}
    admin_tracks($admin);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    return true;
}
function id_delete(){
    $id=intval($_GET["id-delete"]);
    $md=$_GET["md"];
    $tpl=new template_admin();
    $tpl->js_confirm_delete("{rule} $id","id-delete","$id","$('#$md').remove();");
}
function id_delete_perform(){
    $id=intval($_POST["id-delete"]);
    $q=new lib_sqlite("/home/artica/SQLITE/webconsole.db");
    $q->QUERY_SQL("DELETE FROM smtp_notifications WHERE ID=$id");
    if(!$q->ok){echo $q->mysql_error;return false;}
    admin_tracks("Deleted SMTP notification rule $id");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    return true;
}
function rules(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/postfix.transport.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/postfix.transport.progress.txt";
    $ARRAY["CMD"]="postfix2.php?transport=yes";
    $ARRAY["TITLE"]="{reconfiguring}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-postfix-routing')";


    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?id-js=0');\">";
    $btn[]="<i class='fa fa-plus'></i> {new_rule} </label>";
    $btn[]="</div>";
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";

    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{recipients}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{sender}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{filters}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{critical}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{warning}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{enabled}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>";

    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $TRCLASS=null;

    $q=new lib_sqlite("/home/artica/SQLITE/webconsole.db");
    $results=$q->QUERY_SQL("SELECT * FROM smtp_notifications ORDER BY ID DESC");


    foreach ($results as $num=>$ligne){
        $ID=$ligne["ID"];
        $md=md5(serialize($ligne));
        $recipients=trim($ligne["recipients"]);
        $sender=$ligne["sender"];
        $critic=$ligne["critic"];
        $warning=$ligne["warning"];
        $filters=$ligne["filters"];
        if($filters==null){$filters="{all}";}
        $filters="{filters}: ".str_replace("\n",", ",$ligne["filters"]);
        if(strlen($filters)>90){$filters=substr($filters,0,87)."...";}



        $linkjs="Loadjs('$page?id-js=$ID')";
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:30%' nowrap><strong>".$tpl->td_href($recipients,null,$linkjs)."</strong></td>";
        $html[]="<td nowrap><strong>".$tpl->td_href($sender,null,$linkjs)."</strong></td>";
        $html[]="<td nowrap>$filters</td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_check($critic,"Loadjs('$page?enable=$ID&f=critic')",null,"AsSystemAdministrator")."</center></td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_check($warning,"Loadjs('$page?enable=$ID&f=warning')",null,"AsSystemAdministrator")."</center></td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable=$ID&f=enabled')",null,"AsSystemAdministrator")."</center></td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_delete("Loadjs('$page?id-delete=$ID&md=$md')","AsSystemAdministrator")."</center></td>";
        $html[]="</tr>";


    }


    $TINY_ARRAY["TITLE"]="{rules_message_notifs}";
    $TINY_ARRAY["ICO"]="fa fa-envelope";
    $TINY_ARRAY["EXPL"]="{rules_message_notifs_explain}";
    $TINY_ARRAY["URL"]="notifications";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='6'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

    //

}
function logfile_js(){
    $tpl=new template_admin();
    echo $tpl->framework_buildjs("ksrn.php?log-file=yes","ksrn.progress","ksrn.log",
        "progress-notifications-restart","document.location.href='/ressources/logs/web/ksrn.log.gz';");
}

function smtp_static():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $UfdbguardSMTPNotifs=smtp_defaults();

    $proto[]="smtp";
    if($UfdbguardSMTPNotifs["tls_enabled"]==1){
        $proto[]="tls";
    }
    if($UfdbguardSMTPNotifs["ssl_enabled"]==1){
        $proto[]="ssl";
    }
    if($UfdbguardSMTPNotifs["smtp_proxy_outgoing"]==null){
        $UfdbguardSMTPNotifs["smtp_proxy_outgoing"]="{none}";
    }

    $tpl->table_form_field_js("Loadjs('$page?smtp-main-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_bool("{smtp_enabled}",$UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"],ico_check);
    $tpl->table_form_field_bool("{debug}",$UfdbguardSMTPNotifs["smtp_proxy_debug"],ico_watchdog);
    $tpl->table_form_field_text("{outgoing_interface}","{$UfdbguardSMTPNotifs["smtp_proxy_outgoing"]}",ico_interface);
    $tpl->table_form_field_bool("{warning_events} (warning)",$UfdbguardSMTPNotifs["warning_events"],ico_engine_warning);
    if($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]==1) {
        $SMTPNotifEmergency = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SMTPNotifEmergency"));
        if ($SMTPNotifEmergency == 1) {
            echo $tpl->_ENGINE_parse_body($tpl->div_error("<strong>{SMTPNotifEmergency}</strong><br>{SMTPNotifEmergency_explain}"));
        }
    }
    $NOSMTPFEATURE=false;
    $tpl->table_form_field_js("Loadjs('$page?smtp2go-js=yes')","AsSystemAdministrator");
    if($UfdbguardSMTPNotifs["smtp2go"]==0){
        $tpl->table_form_field_bool("SMTP2GO",0,ico_smtp_send);
    }else{
        $NOSMTPFEATURE=true;
        $tpl->table_form_field_bool("SMTP2GO",1,ico_smtp_send);
    }
   // $tpl->table_form_field_js("Loadjs('$page?gmail-js=yes')","AsSystemAdministrator");
   // $tpl->table_form_field_bool("GMail",0,ico_smtp_send);

    if(!$NOSMTPFEATURE) {
        $tpl->table_form_field_js("Loadjs('$page?smtp-parameters-js=yes')", "AsSystemAdministrator");
        if ($UfdbguardSMTPNotifs["smtp_auth_user"] <> null) {
            $proto[] = $UfdbguardSMTPNotifs["smtp_auth_user"];
            $tpl->table_form_field_bool("{USE_LOGIN_AUTH}", $UfdbguardSMTPNotifs["use_login"], ico_check);
        }
        $proto_text = @implode("/", $proto) . "@";

        $tpl->table_form_field_text("{smtp_server}", "$proto_text{$UfdbguardSMTPNotifs["smtp_server_name"]}:{$UfdbguardSMTPNotifs["smtp_server_port"]}", ico_server);
    }


    $tpl->table_form_field_js("Loadjs('$page?smtpdest-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_text("{smtp_sender}",$UfdbguardSMTPNotifs["smtp_sender"],ico_admin);
    $tpl->table_form_field_text("{smtp_dest}",$UfdbguardSMTPNotifs["smtp_dest"],ico_user);

    $topbuttons=array();
    if($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]==1) {
        $topbuttons[] = array("Loadjs('$page?smtp-tests-js=yes')",ico_smtp_send,"{test_message}");
        $topbuttons[] = array("document.location.href='/$page?smtp-export-js=yes'",ico_export,"{export}");
    }
    $topbuttons[] = array("Loadjs('$page?smtp-import-js=yes')",ico_import,"{import}");
    $TINY_ARRAY["TITLE"]="{notifications}";
    $TINY_ARRAY["ICO"]="fa fa-envelope";
    $TINY_ARRAY["EXPL"]="{notifications_explain}";
    $TINY_ARRAY["URL"]="notifications";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]=$tpl->table_form_compile();
    $html[]="<script>$jstiny</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function smtp2go_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $UfdbguardSMTPNotifs=smtp_defaults();
    $jsrestart=$tpl->framework_buildjs("/articanotifs/restart",
        "articanotifs.progress",
        "articanotifs.log","progress-notifications-restart",
        "LoadAjaxSilent('smtp-notifs-form','$page?smtp-static=yes');"
    );

    $form[]=$tpl->field_checkbox("smtp2go","{enabled}", $UfdbguardSMTPNotifs["smtp2go"],true,"");
    $form[]=$tpl->field_text("webapi", "{API_KEY}", $UfdbguardSMTPNotifs["webapi"]);
    echo "<div id='progress-smtp-watchdog-restart' style='margin-top:10px'></div>".
        $tpl->form_outside("",  $form,"","{apply}",
            "dialogInstance2.close();LoadAjaxSilent('smtp-notifs-form','$page?smtp-static=yes');$jsrestart","AsSystemAdministrator");
    return true;
}
function smtp_main_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $UfdbguardSMTPNotifs=smtp_defaults();

    $jsrestart=$tpl->framework_buildjs("/articanotifs/restart",
        "articanotifs.progress",
        "articanotifs.log","progress-notifications-restart",
        "LoadAjaxSilent('smtp-notifs-form','$page?smtp-static=yes');"
    );
    $form[]=$tpl->field_checkbox("ENABLED_SQUID_WATCHDOG","{smtp_enabled}", $UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"],true,"{smtp_enabled_watchdog_explain}");

    if($UfdbguardSMTPNotifs["ENABLED_SQUID_WATCHDOG"]==1) {
        $SMTPNotifEmergency = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SMTPNotifEmergency"));
        if ($SMTPNotifEmergency == 1) {
            echo $tpl->_ENGINE_parse_body($tpl->div_error("<strong>{SMTPNotifEmergency}</strong><br>{SMTPNotifEmergency_explain}"));
        }
    }

    $form[]=$tpl->field_interfaces("smtp_proxy_outgoing","nooloopNoDef:{outgoing_interface}",$UfdbguardSMTPNotifs["smtp_proxy_outgoing"]);
    $form[]=$tpl->field_checkbox("smtp_proxy_debug","{debug}",$UfdbguardSMTPNotifs["smtp_proxy_debug"]);
    $form[]=$tpl->field_checkbox("warning_events","{warning_events} (warning)",$UfdbguardSMTPNotifs["warning_events"]);

    echo "<div id='progress-smtp-watchdog-restart' style='margin-top:10px'></div>".
        $tpl->form_outside("{smtp_notifications} ({defaults})", @implode("\n", $form),"","{apply}",
            "dialogInstance2.close();LoadAjaxSilent('smtp-notifs-form','$page?smtp-static=yes');$jsrestart","AsSystemAdministrator");
    return true;
}
function smtp_dests_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $UfdbguardSMTPNotifs=smtp_defaults();

    $jsrestart=$tpl->framework_buildjs("/articanotifs/restart",
        "articanotifs.progress",
        "articanotifs.log","progress-notifications-restart",
        "LoadAjaxSilent('smtp-notifs-form','$page?smtp-static=yes');"
    );
    $form[]=$tpl->field_email("smtp_sender", "{smtp_sender} ({default})", $UfdbguardSMTPNotifs["smtp_sender"]);
    $form[]=$tpl->field_email("smtp_dest", "{smtp_dest}  ({default})", $UfdbguardSMTPNotifs["smtp_dest"]);
    echo "<div id='progress-smtp-watchdog-restart' style='margin-top:10px'></div>".
        $tpl->form_outside("{smtp_notifications} ({defaults})", @implode("\n", $form),"","{apply}",
            "dialogInstance2.close();LoadAjaxSilent('smtp-notifs-form','$page?smtp-static=yes');$jsrestart","AsSystemAdministrator");
    return true;
}
function smtp_parameters_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $UfdbguardSMTPNotifs=smtp_defaults();

    $jsrestart=$tpl->framework_buildjs("/articanotifs/restart",
        "articanotifs.progress",
        "articanotifs.log","progress-notifications-restart",
    "LoadAjaxSilent('smtp-notifs-form','$page?smtp-static=yes');"
    );

    $form[]=$tpl->field_text("smtp_server_name", "{smtp_server_name}", $UfdbguardSMTPNotifs["smtp_server_name"]);
    $form[]=$tpl->field_numeric("smtp_server_port","{smtp_server_port}",$UfdbguardSMTPNotifs["smtp_server_port"]);
    $form[]=$tpl->field_checkbox("tls_enabled","{tls_enabled}",$UfdbguardSMTPNotifs["tls_enabled"]);
    $form[]=$tpl->field_checkbox("ssl_enabled","{UseSSL}",$UfdbguardSMTPNotifs["ssl_enabled"]);
    $form[]=$tpl->field_text("smtp_auth_user", "{smtp_auth_user}", $UfdbguardSMTPNotifs["smtp_auth_user"]);
    $form[]=$tpl->field_password2("smtp_auth_passwd", "{smtp_auth_passwd}", $UfdbguardSMTPNotifs["smtp_auth_passwd"]);
    $form[]=$tpl->field_checkbox("use_login","{USE_LOGIN_AUTH}",$UfdbguardSMTPNotifs["use_login"]);

    echo "<div id='progress-smtp-watchdog-restart' style='margin-top:10px'></div>".
        $tpl->form_outside("{smtp_notifications} ({defaults})", @implode("\n", $form),"","{apply}",
            "dialogInstance2.close();LoadAjaxSilent('smtp-notifs-form','$page?smtp-static=yes');$jsrestart","AsSystemAdministrator");
    return true;
}
function smtp_export():bool{
    $UfdbguardSMTPNotifs=smtp_defaults();
    $content=base64_encode(serialize($UfdbguardSMTPNotifs));
    header('Content-type: application/octet-stream');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"artica-smtp-notification.settings\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = strlen($content);
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    echo $content;
    return true;
}
function table():bool{


	$tpl=new template_admin();
	$page=CurrentPageName();


//restart_service_each
	$html="<table style='width:100%'>
	<tr>
	<td style='vertical-align:top;width:240px'><div id='notify-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%'><div id='smtp-notifs-form'></td>
	</tr>
	</table>
	<script>
	    LoadAjaxSilent('notify-status','$page?status=yes');
        LoadAjaxSilent('smtp-notifs-form','$page?smtp-static=yes');    
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}
function restart_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsrest=$tpl->framework_buildjs("/articanotifs/restart",
        "articanotifs.progress","articanotifs.progress.log",
        "progress-notifications-restart",
        "LoadAjaxSilent('ksrn-status','$page?ksrn-status=yes');");

    return $jsrest;
}
function status(){

    $tpl            = new template_admin();
    $jsRestart      = restart_js();
    $page=CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/articanotifs/status"));
    $bsini = new Bs_IniHandler();
    $bsini->loadString($json->Info);
    echo $tpl->SERVICE_STATUS($bsini, "ARTICA_NOTIFS",$jsRestart);


}

function smtp_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $sock=new sockets();
    if(isset($_POST["NotifyWebConsoleLogin"])) {
        $sock->SET_INFO("NotifyWebConsoleLogin", $_POST["NotifyWebConsoleLogin"]);
    }
    if(isset($_POST["NotifySSHConsoleLogin"])) {
        $sock->SET_INFO("NotifySSHConsoleLogin", $_POST["NotifySSHConsoleLogin"]);
    }
    if(isset($_POST["SMTPNotifEnabledForAuthLog"])) {
        $sock->SET_INFO("SMTPNotifEnabledForAuthLog", $_POST["ENABLED_SQUID_WATCHDOG"]);
    }
    $sock->SET_INFO("SMTPNotifEmergency", 0);

    $UfdbguardSMTPNotifs=smtp_defaults();
    foreach ($_POST as $num=>$ligne){
        $UfdbguardSMTPNotifs[$num]=$ligne;
    }
    $newparam=base64_encode(serialize($UfdbguardSMTPNotifs));
    $sock->SaveConfigFile($newparam, "UfdbguardSMTPNotifs");
    return  admin_tracks_post("Save SMTP notifications settings");
}

function events(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    if(!isset($_SESSION["SMTP_NOTIFS_SEARCH"])){$_SESSION["SMTP_NOTIFS_SEARCH"]="today this hour 50 events";}

    $html[]="

	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["SMTP_NOTIFS_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>";
    $html[]="function Search$t(e){";
    $html[]="\tif(!checkEnter(e) ){return;}";
    $html[]="ss$t();";
    $html[]="}";
    $html[]="function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";


    echo $tpl->_ENGINE_parse_body($html);

}

function events_search(){
    $time=null;
    $sock=new sockets();
    $tpl=new template_admin();
    $date=null;
    $MAIN=$tpl->format_search_protocol($_GET["search"],false,true);
    $line=base64_encode(serialize($MAIN));
    $sock->getFrameWork("articasmtp.php?artica-notifs-events=$line");
    $filename=PROGRESS_DIR."/smtpd.syslog";
    $date_text=$tpl->_ENGINE_parse_body("{date}");
    $events=$tpl->_ENGINE_parse_body("{events}");
    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>$date_text</th>
        	<th>$events</th>
        </tr>
  	</thead>
	<tbody>
";

    $data=explode("\n",@file_get_contents($filename));
    if(count($data)>3){$_SESSION["SMTP_NOTIFS_SEARCH"]=$_GET["search"];}
    rsort($data);


    foreach ($data as $line){
        $MAIN=array();
        $msg=array();
        $text_class="text-success";

        if(preg_match("#5\.7\.[0-9]+\s+#",$line)){
            $text_class="text-danger";
        }

        if(!preg_match_all("#([a-z]+)=[\"|](.+?)[\"|]#",$line,$sarray)){continue;}
        foreach ($sarray[1] as $index=>$line){
            $MAIN[$line]=$sarray[2][$index];
        }
        if(preg_match("#from=(.+?)\s+#",$line,$re)){
            $MAIN["from"]=$re[1];
        }

        $FTime=$tpl->time_to_date(strtotime($MAIN["time"]),true);
        if(isset($MAIN["error"])){
            $text_class="text-danger";
            $msg[]="<strong>{error}: {$MAIN["error"]}</strong>";
        }



        if(isset($MAIN["msg"])){
            $msg[]=$MAIN["msg"];
        }
        if(isset($MAIN["to"])){
            $msg[]="<br>{recipient}: <strong>".$MAIN["to"]."</strong>";
        }
        if(isset($MAIN["host"])){
            $msg[]="&nbsp;/&nbsp;{smtp_server_name}: <strong>".$MAIN["host"]."</strong>";
        }
        if(isset($MAIN["address"])){
            $msg[]="{listen}: ".$MAIN["address"];
        }
        if(isset($MAIN["from"])){
            $msg[]="&nbsp;/&nbsp;{smtp_sender}: ".$MAIN["from"];
        }



        $line="<span class='$text_class'>".@implode(" ",$msg)."</span>";


        $html[]="<tr>
				<td width=1% nowrap>$FTime</td>
				<td>$line</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents(PROGRESS_DIR."/smtpd.syslog.pattern")."</i></div>";
    echo $tpl->_ENGINE_parse_body($html);



}