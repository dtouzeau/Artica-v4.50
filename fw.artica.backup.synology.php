<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["SynoBackupIPaddr"])){SaveSettings();exit;}
if(isset($_POST["syno-dkms"])){dkms_save();exit;}
if(isset($_GET["server-js"])){server_js();exit;}
if(isset($_GET["server-popup"])){server_popup();exit;}
if(isset($_GET["dkms-js"])){dkms_js();exit;}
if(isset($_GET["dkms-popup"])){dkms_popup();exit;}
if(isset($_POST["RDPProxyAuthookDebug"])){video_params_save();exit;}
if(isset($_POST["SynoBackupUserName"])){signin_save();exit;}
if(isset($_GET["signin-js"])){signin_js();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["settings"])){settings();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["about"])){about();exit;}
if(isset($_GET["widgets"])){widgets();exit;}
if(isset($_GET["loggoff"])){loggoff();exit;}
if(isset($_GET["disconnect"])){disconnect();exit;}
if(isset($_GET["connect"])){connect();exit;}
if(isset($_GET["nodes"])){nodes();exit;}
if(isset($_GET["signin-popup"])){signin_popup();exit;}
table_start();

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table-start=yes";
    $array["{nodes}"]="$page?nodes=yes";
    echo $tpl->tabs_default($array);
}
function table_start(){
    $page=CurrentPageName();
    echo "<div id='synoback-status-table' style='margin-top:10px'></div>
    <script>LoadAjax('synoback-status-table','$page?table=yes');</script>";
}


function signin_js(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $tpl->js_dialog1("{need_sign_in}","$page?signin-popup=yes");

}
function signin_save(){
    $tpl    = new template_admin();
    $tpl->CLEAN_POST();
    admin_tracks("Saving Synology Active Backup for Business credentials to {$_POST["SynoBackupUserName"]}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SynoBackupUserName",$_POST["SynoBackupUserName"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SynoBackupPassword",$_POST["SynoBackupPassword"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/syno/connect");
}

function signin_popup(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $SynoBackupUserName= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SynoBackupUserName"));
    $SynoBackupPassword = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SynoBackupPassword"));
    $form[]=$tpl->field_text("SynoBackupUserName","{username}",$SynoBackupUserName);
    $form[]=$tpl->field_password("SynoBackupPassword","{password}",$SynoBackupPassword);
    echo $tpl->form_outside("{credentials}",$form,null,"{apply}","dialogInstance1.close();","AsSystemAdministrator");
}

function settings():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SynoBackupIPaddr = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SynoBackupIPaddr"));
    $AllowDKMS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SynoBackupAllowDKMS"));
    if($SynoBackupIPaddr==""){$SynoBackupIPaddr="{none}";}

    $tpl->table_form_field_js("Loadjs('$page?server-js=yes')");
    $tpl->table_form_field_text("Synology {remote_server_name}",$SynoBackupIPaddr,ico_server);
    $tpl->table_form_field_js("Loadjs('$page?dkms-js=yes')");
    $tpl->table_form_field_bool("{synobackup_allow_dkms}||{synobackup_allow_dkms_short}",$AllowDKMS,ico_cpu);
    $html[]="<div style='margin-top:10px'>".$tpl->table_form_compile()."</div>";

    $jshelp="s_PopUpFull('https://wiki.articatech.com/en/system/backup/synology',1024,768,'Wiki')";
    $topbuttons[]=array($jshelp,"fas fa-question-circle","Wiki");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $TINY_ARRAY["TITLE"]="{APP_SYNO_BACKUP}";
    $TINY_ARRAY["ICO"]=ico_backup_remote;
    $TINY_ARRAY["EXPL"]="{APP_SYNO_BACKUP_ABOUT}";
    $TINY_ARRAY["URL"]="snapshots";
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$headsjs</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
    return true;
}

function server_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("Synology {remote_server_name}","$page?server-popup=yes",650);
}

function server_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SynoBackupIPaddr = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SynoBackupIPaddr"));
    $form[]=$tpl->field_text("SynoBackupIPaddr","Synology {remote_server_name}",$SynoBackupIPaddr);
    $html[]=$tpl->div_explain("Synology {remote_server_name}||{synobackup_server_explain}");
    $html[]=$tpl->form_outside("",$form,"","{apply}",
        "dialogInstance2.close();LoadAjaxSilent('synology-config','$page?settings=yes');","AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function dkms_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{synobackup_allow_dkms}","$page?dkms-popup=yes",650);
}

function dkms_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $AllowDKMS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SynoBackupAllowDKMS"));
    $after="if(String(resp).indexOf('jserror:')===0){ctrl.input.checked=!ctrl.input.checked;"
        ."swal({title:'Oops...',text:'<strong>'+String(resp).substring(8)+'</strong>',html:true,type:'error'});}"
        ."LoadAjaxSilent('synology-config','$page?settings=yes');";
    echo $tpl->_ENGINE_parse_body($tpl->div_explain("{synobackup_allow_dkms}||{synobackup_allow_dkms_explain}"))
        .$tpl->BigCircleCheckbox("syno-dkms:1|SynoBackupAllowDKMS","{synobackup_allow_dkms}",
            "{synobackup_allow_dkms_short}",$AllowDKMS,$after,"AsSystemAdministrator");
    return true;
}

function dkms_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(!isset($_POST["SynoBackupAllowDKMS"])){
        echo $tpl->post_error("{missing} SynoBackupAllowDKMS");
        return;
    }
    $value=intval($_POST["SynoBackupAllowDKMS"])==1?1:0;
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/syno/dkms/allow/$value");
    $json=json_decode((string)$resp);
    if(!is_object($json)){
        $raw=trim(str_replace(array("'","\\"),array(" "," "),(string)$resp));
        echo $tpl->post_error($raw==""?"{error}":substr($raw,0,300));
        return;
    }
    if(!$json->Status){
        echo $tpl->post_error((string)$json->Error);
        return;
    }
    admin_tracks("Synology Active Backup for Business: agent driver compilation ".($value==1?"allowed":"denied"));
}

function SaveSettings(){
    $tpl=new template_admin();
    $log=$tpl->SAVE_POSTs();
    foreach ($log as $key=>$val){
        $tt[]="$key = $val";
    }
    admin_tracks("Synology Active Backup for Business parameters changed ".@implode(", ",$tt));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/syno/connect");
}





function status():bool{
    $tpl=new template_admin();
    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/syno/status");
    $page=CurrentPageName();
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error().
            "<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }
    $restartService=$tpl->framework_buildjs("/syno/restart",
        "synobackup.restart.progress",
        "synobackup.restart.progress.log",
        "progress-snapshot-restart");

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    echo $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_SYNO_BACKUP",$restartService));
    echo "<script>LoadAjaxSilent('syno-widgets','$page?widgets=yes');</script>";
    return true;
}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:260px;vertical-align: top'><div id='syno-status'></div></td>";
    $html[]="<td style='width:99%;vertical-align:top;'>
            <div id='syno-widgets' style='margin-top:-10px'></div>
            <div id='synology-config'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";


    $jsRefresh=$tpl->RefreshInterval_js("syno-status","$page","status=yes");


    $html[]="<script>";
    $html[]="$jsRefresh";
    $html[]="LoadAjax('synology-config','$page?settings=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function loggoff():bool{
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/syno/disconnect");
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    admin_tracks("Close Synology Active Backup for Business Session");
    $tpl->js_display_results("{operation_launched_in_background}");
    echo "LoadAjaxSilent('syno-widgets','$page?widgets=yes');";
    return true;

}
function disconnect(){
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/syno/disconnect");
    header("content-type: application/x-javascript");
    admin_tracks("Close Synology Active Backup for Business Session");
    $tpl->js_display_results("{operation_launched_in_background}");

    return true;
}
function connect(){
    $tpl=new template_admin();
    $tpl->js_display_results("{operation_launched_in_background}");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/syno/connect");
    sleep(1);
    header("content-type: application/x-javascript");
    admin_tracks("Synology Active Backup for Business Connecting operation");
    return true;
}


function widgets():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $error=null;
    $STATUS=unserialize(@file_get_contents(PROGRESS_DIR."/synobackup.infos"));

    $Connected  = $STATUS["CONNECTED"];
    $needlogin  = false;
    $SynoBackupIPaddr   = $STATUS["SERVER"];
    $LoginName  = $STATUS["USERNAME"];
    $SynobackupError=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SynobackupError"));
    if($LoginName == null ) {
        $error="{missing} {credentials}";
        $needlogin=True;
    }
    if($SynoBackupIPaddr == null ) {
        $error="{missing} {remote_server_name}";
        $needlogin=True;
    }
    if($SynobackupError>0){$needlogin=True;}
    if(!isset($STATUS["LAST_BACKUP"])){$STATUS["LAST_BACKUP"]=null;}
    if(!isset($STATUS["RESTORE"])){$STATUS["RESTORE"]=null;}



    if($STATUS["LAST_BACKUP"]=="-"){$STATUS["LAST_BACKUP"]=null;}


    if($needlogin){
        $btn["name"]="{please_sign_in}";
        $btn["ico"]="fas fa-sign-in-alt";
        $btn["js"] = "Loadjs('$page?signin-js=yes');";
        $needlogin_status=$tpl->widget_h("yellow","fas fa-sign-in-alt","{need_sign_in}",$error,$btn);

        if($SynobackupError==1){
            $error="{authentication_failed}";
            $needlogin_status=$tpl->widget_h("red","fas fa-sign-in-alt",
                "{need_sign_in}",$error,$btn);
        }
        if($SynobackupError==2){
            $error="{failed_to_connect}";
            $needlogin_status=$tpl->widget_h("red","fas fa-sign-in-alt",
                "{need_sign_in}",$error,$btn);
        }


    }else{

        $btn["name"]="{loggoff}";
        $btn["ico"]=ico_exit;
        $btn["js"] = "Loadjs('$page?loggoff=yes');";
        $needlogin_status=$tpl->widget_h("blue",ico_exit,
            "{active_session} ","$LoginName",$btn);

    }

    if($Connected==0) {
        $btn["name"]="{connect}";
        $btn["ico"]="fas fa-link";
        $btn["js"] = "Loadjs('$page?connect=yes');";
        $Connected_status = $tpl->widget_h("grey", ico_link,
            "{not_connected}", "{connection_status}",$btn);

    }else{
        $btn["name"]="{disconnect}";
        $btn["ico"]=ico_exit;
        $btn["js"] = "Loadjs('$page?disconnect=yes');";
        $state=$STATUS["SERVICE_STATUS"];
        // Pentest 2026-11-04 : etat du service et URL du portail (donnee externe) affiches sans encodage (XSS stockee).
        $state="<small style='color:white'>".req_html($state)."</small>";
        $Connected_status= $tpl->widget_h("green", "fas fa-link", $state,
            "{service_status}",$btn);
    }

    if($STATUS["LAST_BACKUP"]==null) {
        if($STATUS["RESTORE"]<>null) {
            $btn["name"] = "Restore portal";
            $btn["ico"] = "fas fa-external-link-alt";
            $btn["js"] = "s_PopUpFull('".req_js($STATUS["RESTORE"])."','1024','900');";
        }
        $status_addr = $tpl->widget_h("grey", "fa-thumbs-down", "{not_used}", "{last_backup}",$btn);

    }else{
        if($STATUS["RESTORE"]<>null) {
            $btn["name"] = "Restore portal";
            $btn["ico"] = ico_import;
            $btn["js"] = "s_PopUpFull('".req_js($STATUS["RESTORE"])."','1024','900');";
        }
        $stime=strtotime($STATUS["LAST_BACKUP"]);
        $last=distanceOfTimeInWords($stime,time());

        $status_addr= $tpl->widget_h("green", "fa-thumbs-up", "<small style='color:white'>$last</small>", "{last_backup}",$btn);
    }
   $html[]="<table style='width:100%'>
	    <tr>
	    <td style='vertical-align:top;width:200px;padding:8px'>$needlogin_status</td>
	    <td style='vertical-align:top;width:200px;padding:8px'>$Connected_status</td>
	    <td style='vertical-align:top;width:200px;padding:8px'>$status_addr</td>
	    </tr>
	   </table>
    ";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

