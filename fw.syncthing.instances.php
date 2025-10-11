<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["instance-events"])){instance_events();exit;}

if(isset($_GET["instance-js"])){instance_js();exit;}
if(isset($_GET["instance-tabs"])){instance_tabs();exit;}
if(isset($_GET["instance-info"])){instance_info();exit;}
if(isset($_GET["instance-info-popup"])){instance_info_popup();exit;}
if(isset($_GET["instance-port"])){instance_port_js();exit;}
if(isset($_GET["instance-port-popup"])){instance_port_popup();exit;}
if(isset($_POST["instance-port"])){instance_port_save();exit;}
if(isset($_GET["instance-folders"])){instance_folders();exit;}
if(isset($_GET["instance-folders-buttons"])){instance_folders_button();exit;}
if(isset($_GET["folders-search"])){instance_folders_search();exit;}
if(isset($_GET["folder-add-js"])){instance_folders_add_js();exit;}
if(isset($_GET["folder-add-popup"])){instance_folders_add_popup();exit;}
if(isset($_POST["folder-add-save"])){instance_folders_add_save();exit;}
if(isset($_GET["instance-folder-delete"])){instance_folders_del_ask();exit;}
if(isset($_POST["instance-folder-delete"])){instance_folders_del_perform();exit;}
if(isset($_GET["instance-deviceid"])){instance_deviceid();exit;}
if(isset($_GET["instance-deviceid-popup"])){instance_deviceid_popup();exit;}
if(isset($_GET["instance-pending-accept"])){instance_pending_accept();exit;}
if(isset($_GET["instance-trusted-devices"])){instance_trusted_devices();exit;}
if(isset($_GET["instance-trusted-devices-list"])){instance_trusted_devices_list();exit;}
if(isset($_GET["instance-remote-folders"])){instance_remote_folders();exit;}
if(isset($_GET["instance-remote-folders-list"])){instance_remote_folders_list();exit;}
if(isset($_GET["instance-folder-accept"])){instance_folder_accept_js();exit;}
if(isset($_GET["instance-folder-accept-popup"])){instance_folder_accept_popup();exit;}
if(isset($_POST["instance-folder-accept"])){instance_folder_accept_save();exit;}
if(isset($_GET["td-status"])){td_status_all();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["start"])){start();exit;}
if(isset($_GET["search"])){table();exit;}

if(isset($_GET["instance-username-js"])){instance_username_js();exit;}
if(isset($_GET["instance-username-popup"])){instance_username_popup();exit;}
if(isset($_POST["instance-username"])){instance_username_save();exit;}
if(isset($_GET["instance-start"])){instance_start();exit;}
if(isset($_GET["instance-stop"])){instance_stop();exit;}
if(isset($_GET["instance-enable"])){instance_enable();exit;}
if(isset($_GET["instance-web"])){instance_web();exit;}

if(isset($_GET["instance-folder-sync"])){instance_folder_sync();exit;}
if(isset($_GET["instance-folder-sync-popup"])){instance_folder_sync_popup();exit;}
if(isset($_GET["instance-folder-sync-save"])){instance_folder_sync_save();exit;}




if(isset($_GET["instance-new-js"])){instance_new_js();exit;}
if(isset($_GET["new-instance-popup"])){instance_new_popup();exit;}
if(isset($_POST["new_instance_name"])){instance_new_save();exit;}

if(isset($_GET["instance-events-js"])){instance_events_js();exit;}
if(isset($_GET["instance-events-form"])){instance_events_form();exit;}



if(isset($_GET["instance-delete-js"])){instance_delete_js();exit;}
if(isset($_POST["instance-delete"])){instance_delete_perform();exit;}

if(isset($_GET["section-nics-js"])){section_nics_js();exit;}
if(isset($_GET["section-nics-popup"])){section_nics_popup();exit;}
if(isset($_POST["NICS"])){section_nics_save();exit;}


if(isset($_GET["section-traffic-js"])){section_traffic_js();exit;}
if(isset($_GET["section-traffic-popup"])){section_traffic_popup();exit;}

if(isset($_GET["section-proto-js"])){section_proto_js();exit;}
if(isset($_GET["section-proto-popup"])){section_proto_popup();exit;}

page();

function instance_delete_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["instance-delete-js"]);
    $md=$_GET["md"];
    $InstanceName=getInstanceName($ID);
    return $tpl->js_confirm_delete($InstanceName,"instance-delete",$ID,"$('#$md').remove()");
}
function instance_delete_perform():bool{
    $ID=intval($_POST["instance-delete"]);
    $InstanceName=getInstanceName($ID);
    $json=$GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/instances/delete/$ID");
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
    return admin_tracks("Deleted Syncthing Instance $InstanceName");
}
function instance_new_js():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{new_instance2}","$page?new-instance-popup=true&function=$function",650);
}
function instance_js():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $ID=$_GET["instance-js"];
    $InstanceName=getInstanceName($ID);
    $tpl=new template_admin();
    return $tpl->js_dialog2("{instance}: $InstanceName","$page?instance-tabs=$ID&function=$function",900);
}
function instance_folder_sync():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instanceid=intval($_GET["instance-folder-sync"]);
    $folderid=$_GET["folder"];
    $folderidEnc=urlencode($folderid);
    $function=$_GET["function"];
    $page=CurrentPageName();
    return $tpl->js_dialog3("$folderid {synchronize}","$page?instance-folder-sync-popup=$instanceid&folder=$folderidEnc&function=$function",650);
}

function instance_folder_sync_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $instanceid=intval($_GET["instance-folder-sync-save"]);
    $FolderID=urlencode($_GET["folder"]);
    $function=$_GET["function"];
    $change=urlencode($_GET["change"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/instances/folders/syncmethod/$instanceid/$FolderID/$change"));
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    header("content-type: application/x-javascript");
    $js[]="dialogInstance3.close();";
    $js[]="$function();";
    echo @implode("\n",$js);
    return admin_tracks("Modify Syncthing folder #$FolderID sync to $change on instance #$instanceid");

}
function instance_folder_sync_popup():bool{
    $page=CurrentPageName();
    $instanceid=$_GET["instance-folder-sync-popup"];
    $function=$_GET["function"];
    $folderid=$_GET["folder"];
    $tpl=new template_admin();
    $json=td_json($instanceid);
    $folderidEnc=urlencode($folderid);

    if(!property_exists($json->Instance,"Folders")){
        $tpl->div_error("Invalid instance..");
        return false;
    }
    $SyncType="";

    foreach (get_object_vars($json->Instance->Folders) as $path => $ligne) {
        if ($ligne->UniqueID == $folderid) {
            $SyncType = $ligne->SyncType;
        }
    }

    $iconcheck=ico_check;
    $html[]="<table style='width:100%'>";
    $TRCLASS=null;

    $sendreceive="Loadjs('$page?instance-folder-sync-save=$instanceid&folder=$folderidEnc&function=$function&change=sendreceive');";
    if ($TRCLASS == "footable-odd") {$TRCLASS = null;} else {$TRCLASS = "footable-odd";}
    $html[] = "<tr class='$TRCLASS'>";
    $html[] = "<td style='width:99%;padding:10px'><H2>{sendreceive}</H2><p>{syncthing_sendreceive}</p></td>";
    if($SyncType<>"sendreceive") {
            $html[] = "<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-primary\" OnClick=\"$sendreceive\"><i class='fas fa-arrow-alt-right'></i> {modify} </label>";
        }else{
            $html[] = "<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-default\" OnClick=\"\"><i class='$iconcheck'></i> {selected} </label>";
        }
        $html[] = "</tr>";
        $sendreceive="Loadjs('$page?instance-folder-sync-save=$instanceid&folder=$folderidEnc&function=$function&change=sendonly');";
        if ($TRCLASS == "footable-odd") {$TRCLASS = null;} else {$TRCLASS = "footable-odd";}
        $html[] = "<tr class='$TRCLASS'>";
        $html[] = "<td style='width:99%;padding:10px'><H2>{sendonly}</H2><p>{syncthing_sendonly}</p></td>";
        if($SyncType<>"sendonly") {
            $html[] = "<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-primary\" OnClick=\"$sendreceive\"><i class='fas fa-arrow-alt-right'></i> {modify} </label>";
        }else{
            $html[] = "<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-default\" OnClick=\"\"><i class='$iconcheck'></i> {selected} </label>";
        }
        $html[] = "</tr>";

        $sendreceive="Loadjs('$page?instance-folder-sync-save=$instanceid&folder=$folderidEnc&function=$function&change=receiveonly');";
        if ($TRCLASS == "footable-odd") {$TRCLASS = null;} else {$TRCLASS = "footable-odd";}
        $html[] = "<tr class='$TRCLASS'>";
        $html[] = "<td style='width:99%;padding:10px'><H2>{receiveonly}</H2><p>{syncthing_receiveonly}</p></td>";
        if($SyncType<>"receiveonly") {
            $html[] = "<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-primary\" OnClick=\"$sendreceive\"><i class='fas fa-arrow-alt-right'></i> {modify} </label>";
        }else{
            $html[] = "<td nowrap style='width:1%;padding:10px'><label class=\"btn btn btn-default\" OnClick=\"\"><i class='$iconcheck'></i> {selected} </label>";
        }
        $html[] = "</tr>";
        $html[] = "</table>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
}
function instance_folder_accept_js():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $instanceid=intval($_GET["instance-folder-accept"]);
    $deviceID=$_GET["devid"];
    $folderid=$_GET["folderid"];
    $name=$_GET["name"];
    $page=CurrentPageName();
    return $tpl->js_dialog3("$name","$page?instance-folder-accept-popup=$instanceid&devid=$deviceID&folderid=$folderid",650);

}

function instance_folder_accept_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $instanceid=intval($_POST["instance-folder-accept"]);
    $deviceid=urlencode($_POST["deviceid"]);
    $folderid=urlencode($_POST["folderid"]);
    $path=urlencode($_POST["path"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/instances/pending/folder/accept/$instanceid/$deviceid/$folderid/$path"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return true;
    }
    return admin_tracks_post("Accept pending folder for instance #$instanceid");
}

function instance_folder_accept_popup():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $page=CurrentPageName();
    $instanceid=intval($_GET["instance-folder-accept-popup"]);
    $deviceID=$_GET["devid"];
    $folderid=$_GET["folderid"];
    $form[]=$tpl->field_hidden("instance-folder-accept",$instanceid);
    $form[]=$tpl->field_hidden("deviceid",$deviceID);
    $form[]=$tpl->field_hidden("folderid",$folderid);
    $json=td_json($instanceid);
    if(!property_exists($json,"Instance")){
        echo $tpl->div_error("Property failed error ".__LINE__." for instance $instanceid");
        return false;
    }

    if(!property_exists($json->Instance,"Instance")){
        echo $tpl->div_error("Json failed error ".__LINE__." for instance $instanceid");
        return false;
    }
    $HomeDir=$json->Instance->Instance->HomeDir;
    $form[]=$tpl->field_browse_directory("path","{directory}","",$HomeDir);
    $js[]="dialogInstance3.close();";
    $js[]="LoadAjaxSilent('instance-info-$instanceid','$page?instance-info-popup=$instanceid');";
    echo $tpl->form_outside("",$form,"","{accept}",@implode(";",$js));
    return true;
}

function instance_tabs():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["instance-tabs"]);
    $tpl=new template_admin();
    $array["{info}"]="$page?instance-info=$ID";
    $array["{my_folders}"]="$page?instance-folders=$ID";
    $array["{trusted_devices}"]="$page?instance-trusted-devices=$ID";
    $array["{remote_folders}"]="$page?instance-remote-folders=$ID";


    echo $tpl->tabs_default($array);
    return true;
}
function instance_username_js():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $ID=intval($_GET["instance-username-js"]);
    $tpl=new template_admin();
    $owner=getOwner($ID);
    return $tpl->js_dialog2("$owner","$page?instance-username-popup=$ID&function=$function",650);
}
function instance_events_js():bool{
    $page=CurrentPageName();
    $ID=intval($_GET["instance-events-js"]);
    $tpl=new template_admin();
    $instancename=getInstanceName($ID);
    return $tpl->js_dialog2("$instancename {events}","$page?instance-events-form=$ID",1200);
}
function instance_username_popup():bool{
    $ID=intval($_GET["instance-username-popup"]);
    $tpl=new template_admin();
    $form[]=$tpl->field_hidden("instance-username",$ID);
    $form[]=$tpl->field_password2("instance-username-password","{password}");
    echo $tpl->form_outside("",$form,"{syncthing_password_explain}","{apply}",jsApply(),"AsSambaAdministrator");
    return true;
}
function instance_username_save():bool{
    $instanceid=$_POST["instance-username"];
    $instancePassword=$_POST["instance-username-password"];
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/syncthing/webaccess/$instanceid",array("password"=>$instancePassword)));

    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return true;
    }
   return admin_tracks("Change Syncthing Web console password for instance $instanceid");
}
function instance_trusted_devices():bool{
    $page=CurrentPageName();
    $instanceid=intval($_GET["instance-trusted-devices"]);
    echo "<div id='instance-trusted-devices-buttons-$instanceid' style='margin-top:10px'></div>
        <div id='instance-trusted-devices-$instanceid' style='margin-top:10px'></div>
        <script>LoadAjaxSilent('instance-trusted-devices-$instanceid','$page?instance-trusted-devices-list=$instanceid');</script>
    ";
    return true;
}
function instance_remote_folders():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instanceid=intval($_GET["instance-remote-folders"]);
    echo "<div id='instance-remote-folders-buttons-$instanceid' style='margin-top:10px'></div>";
    echo $tpl->search_block($page,"","","","&instance-remote-folders-list=$instanceid");
    return true;
}
function instance_remote_folders_list():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $instanceid=intval($_GET["instance-remote-folders-list"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/instances/folders/trusted/$instanceid"));

    if(!$json->Status) {
        echo $tpl->div_error($json->Error);
        return true;
    }
    if(!property_exists($json,"Instance")){
        echo $tpl->div_error("Property failed error ".__LINE__." for instance $instanceid");
        return false;
    }



    var_dump($json->Instance);
return true;

}

function instance_trusted_devices_list():bool{
    $instanceid=$_GET["instance-trusted-devices-list"];
    $TRCLASS="";
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=td_json($instanceid);
    if(!property_exists($json,"Instance")){
        return false;
    }
    if($json->Instance->Enabled==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{service_disabled}"));
        return false;
    }

    if(!$json->Instance->Running){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{service_stopped}"));
    }
    if(!property_exists($json->Instance,"TrustedDevices")){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("Decode error"));
    }

    $html[]="<table id='table-$instanceid' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{device}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $MyDevice=$json->Instance->MyDevice;
    foreach ($json->Instance->TrustedDevices as $uid=>$device) {
        $deviceID=$device->deviceID;
        $deviceName=$device->name;
        $addresses=$device->addresses;
        $tt=array();
        foreach ($addresses as $value) {

            $tt[]=$value;
        }
        $addr_flt=@implode(", ",$tt);
        $paused=$device->paused;
        $md=md5(json_encode($device));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td><strong><i class='".ico_computer."'></i>&nbsp;$deviceName</strong><br><i>$deviceID<br>$addr_flt</i></td>";
        $html[]="<td style='width:1%' nowrap>&nbsp;</td>";
        $html[]="</tr>";
    }


    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="</tfoot>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function instance_events_form():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceid=$_GET["instance-events-form"];
    $html[]="<div style='margin-top:10px'></div>";
    $html[]=$tpl->search_block($page,"","","","&instance-events=$instanceid");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function instance_port_js(){
    $instanceid=$_GET["instance-port"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=td_json($instanceid);
    $Port=$json->Instance->Instance->listenport;
    return $tpl->js_dialog9("{listen_port} $Port","$page?instance-port-popup=$instanceid",550);
}
function instance_port_popup():bool{
    $instanceid=$_GET["instance-port-popup"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=td_json($instanceid);
    $Port=$json->Instance->Instance->listenport;
    $html[]=$tpl->field_hidden("instance-port",$instanceid);
    $html[]=$tpl->field_numeric("listenport","{listen_port}",$Port);
    echo $tpl->form_outside("",$html,"","{apply}","dialogInstance9.close();");
    return true;
}
function instance_port_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $instanceid=$_POST["instance-port"];
    $instancePort=$_POST["listenport"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/instances/listenport/$instanceid/$instancePort"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return true;
    }
    return admin_tracks("Change Syncthing listen port to $instancePort for instance $instanceid");

}
function instance_deviceid(){
    $page=CurrentPageName();
    $instanceid=intval($_GET["instance-deviceid"]);
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog5("{identity}","$page?instance-deviceid-popup=$instanceid",650);
}
function instance_deviceid_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instanceid=intval($_GET["instance-deviceid-popup"]);
    $identity=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/instance/identity/$instanceid"));

    if(!$identity->Status){
        echo $tpl->div_error($identity->Error);
        return false;
    }
    echo "<H2 class='center' style='margin-bottom:40px'>$identity->DeviceID</H2>";
    echo sprintf("<div class='center'><img src='%s' class='center' style='text-align: center'></div>'>", $identity->QrCode);
    return true;
}

function instance_info():bool{
    $page=CurrentPageName();
    $instanceid=$_GET["instance-info"];
    echo "<div id='instance-info-$instanceid'></div>";
    echo "<script>LoadAjaxSilent('instance-info-$instanceid','$page?instance-info-popup=$instanceid');</script>";
    return true;
}
function instance_pending_accept():bool{
    $instanceid=$_GET["instance-pending-accept"];
    $devid=$_GET["devid"];
    $name=$_GET["name"];
    $nameEnc=urlencode($name);
    $devidEnc=urlencode($devid);
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/instances/pending/accept/$instanceid/$devidEnc/$nameEnc"));

    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    echo "LoadAjaxSilent('instance-info-$instanceid','$page?instance-info-popup=$instanceid');";
    return admin_tracks("Accept pending host $devid to instance $instanceid");
}
function instance_info_popup():bool{
    $page=CurrentPageName();
    $instanceid=$_GET["instance-info-popup"];

    $tpl=new template_admin();
    $json=td_json($instanceid);
    $DeviceID="";
    $identity=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/instance/identity/$instanceid"));

    if(!$identity->Status){
        echo $tpl->div_error($identity->Error);
    }else{
        $DeviceID=$identity->DeviceID;
    }
    $tpl->table_form_field_js("Loadjs('$page?instance-deviceid=$instanceid')");
    $tpl->table_form_field_text("{uuid}","<small style='font-size: 74%'>$DeviceID</small>",ico_computer);

    $tpl->table_form_field_js("");
    if(property_exists($json->Instance,"PendingDevices")) {
        $c = 0;
        foreach ($json->Instance->PendingDevices as $uid => $device) {
            $deviceID=$device->deviceID;
            $thbts=array();
            $devname=urlencode($device->name);
            $thbts[]=array("Loadjs('$page?instance-pending-accept=$instanceid&devid=$deviceID&name=$devname')",ico_ok,"{accept}");
            $thbts[]=array("Loadjs('$page?instance-pending-refuse=$instanceid&devid=$deviceID')",ico_ban,"{discard}");
            $address=$device->address;
            $btn=$tpl->th_buttons($thbts);
            $htm[]="<table style='width: 100%'>";
            $htm[]="<tr>";
            $htm[]="<td style='vertical-align: middle;width:1%' nowrap>$address</td>";
            $htm[]="<td style='vertical-align: middle;padding-left:10px'>$btn</td>";
            $htm[]="</table>";
            $line=@implode("",$htm);

            $tpl->table_form_field_text($device->name,"$line<i style='font-size: 74%'> $deviceID</i></>",ico_timeout);
            $c++;
        }

    }
    $icocomp=ico_computer;
    $tpl->table_form_field_js("");
    if(property_exists($json->Instance,"PendingSharedFolder")) {
        if (is_array($json->Instance->PendingSharedFolder) || is_object($json->Instance->PendingDevices)) {
            foreach ($json->Instance->PendingSharedFolder as $device) {
                $folderID=$device->folderID;
                foreach ($device->offeredBy as $deviceclass) {
                    $thbts = array();
                    $devname = urlencode($deviceclass->label);
                    $deviceID=$deviceclass->deviceID;
                    $deviceName=$deviceclass->deviceName;
                    $FolderLabel=$deviceclass->label;
                    $thbts[] = array("Loadjs('$page?instance-folder-accept=$instanceid&devid=$deviceID&name=$devname&folderid=$folderID')", ico_ok, "{accept}");
                    $thbts[] = array("Loadjs('$page?instance-pending-refuse=$instanceid&devid=$deviceID')", ico_ban, "{discard}");
                    $From="<i class='$icocomp'></i>&nbsp;{from} $deviceName";
                    $btn = $tpl->th_buttons($thbts);
                    $htm[] = "<table style='width: 100%'>";
                    $htm[] = "<tr>";
                    $htm[] = "<td style='vertical-align: middle;width:1%' nowrap>$From</td>";
                    $htm[] = "<td style='vertical-align: middle;padding-left:10px'>$btn</td>";
                    $htm[] = "</table>";
                    $line = @implode("", $htm);
                    $tpl->table_form_field_text($FolderLabel, "$line<i style='font-size: 74%'> $deviceID</i></>", ico_folder);
                }
            }
        }
    }



    $tpl->table_form_field_text("Pid",$json->Instance->Pid,ico_params);
    $tpl->table_form_field_text("{API_KEY}",$json->Instance->Instance->DBinfo->apiKey,ico_lock);
    $tpl->table_form_field_js("Loadjs('$page?instance-port=$instanceid')");
    $tpl->table_form_field_text("{listen_port}",$json->Instance->Instance->listenport,ico_nic);


    echo $tpl->table_form_compile();

    //var_dump($json);
    return true;

}
function instance_events():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceid=$_GET["instance-events"];

    $html[]="<table id='table-$instanceid' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{event}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $LEVELS["INFO"]="<span class='label label-default'>INFO</span>";
    $LEVELS["WARNING"]="<span class='label label-warning'>WARN.</span>";
    $LEVELS["debug"]="<span class='label label-default'>DEBUG</span>";
    $LEVELS["trace"]="<span class='label label-default'>TRACE</span>";

    $search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
    if(strlen($search["S"])<2){$search["S"]=".";}
    $ss=base64_encode($search["S"]);
    $jsAfter="LoadAjax('table-loader','$page?table=yes');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);



    $EndPoint="/syncthing/$instanceid/events/$ss";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API($EndPoint);

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>$data".json_last_error_msg());
        return false;
    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    $TRCLASS=null;
    foreach ($json->Logs as $line){
        $text_class="";

        if(!preg_match("#\[.*?\]\s+(.+?)\s+([A-Z]+):\s+(.+)#",$line,$re)){ echo "<span style='color:red'>$line</span><br>\n"; }
        $date=$re[1];
        $type=$LEVELS[$re[2]];
        $event=$re[3];

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td style='width:1%;' nowrap>$date</td>";
        $html[]="<td style='width:1%;' nowrap>$type</td>";
        $html[]="<td>$event</td>";
        $html[]="</tr>";


    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function section_traffic_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{listen}","$page?section-traffic-popup=true");
}
function section_nics_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{listen}","$page?section-nics-popup=true",550);
}
function instance_start():bool{
    $ID=intval($_GET["instance-start"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/start/$ID");
    return admin_tracks("Start syncthing instance $ID");
}
function instance_stop():bool{
    $ID=intval($_GET["instance-stop"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/stop/$ID");
    return admin_tracks("Stop syncthing instance $ID");
}
function instance_enable():bool{
    $ID=intval($_GET["instance-enable"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/enable/$ID");
    return admin_tracks("Switch syncthing instance $ID to enable or disable");
}
function instance_web():bool{
    $ID=intval($_GET["instance-web"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/webaccess/$ID");
    return admin_tracks("Switch syncthing instance $ID to enable or disable Web access");
}

function getInstanceName($ID):string{
    $q=new lib_sqlite("/home/artica/SQLITE/syncthing.db");
    $ligne=$q->mysqli_fetch_array("SELECT instancename FROM instances WHERE ID=$ID");
    return strval($ligne["instancename"]);
}
function getOwner($ID):string{
    $q=new lib_sqlite("/home/artica/SQLITE/syncthing.db");
    $ligne=$q->mysqli_fetch_array("SELECT username FROM instances WHERE ID=$ID");
    return strval($ligne["username"]);
}
function section_proto_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{router}","$page?section-proto-popup=true");
}
function section_proto_popup():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $security = "AsSquidAdministrator";
    $WCCP_ASA_ROUTER=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_ASA_ROUTER"));
    $WCCP_ASA_ADDR=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_ASA_ADDR"));
    $WCCP_PASSWORD=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_PASSWORD"));
    $WCCP_ASA_USE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_ASA_USE"));
    $WCCP_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_VERSION"));
    $zwccp_version[0]="{default}";
    $zwccp_version[3]="{version} 3";
    $zwccp_version[4]="{version} 4";

    if($WCCP_ASA_USE==0){$WCCP_ASA_ROUTER=null;}

    $form[]=$tpl->field_ipaddr("WCCP_ASA_ADDR","{router_address}",$WCCP_ASA_ADDR);
    $form[]=$tpl->field_array_hash($zwccp_version,"WCCP_VERSION","{version}: WCCP",$WCCP_VERSION);
    $form[]=$tpl->field_checkbox("WCCP_ASA_USE","CISCO ASA",$WCCP_ASA_USE,"WCCP_ASA_ROUTER");
    $form[]=$tpl->field_ipaddr("WCCP_ASA_ROUTER","{cisco_asa_address}",$WCCP_ASA_ROUTER);
    $form[]=$tpl->field_password2("WCCP_PASSWORD","{WCCP_PASSWORD}",$WCCP_PASSWORD,false,"{WCCP_PASSWORD_EXPLAIN}");
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",jsApply(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function section_traffic_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSambaAdministrator";

    $WCCP_HTTP_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTP_PORT"));
    $WCCP_HTTPS_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTPS_PORT"));
    $WCCP_LOCAL_INTERFACE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_LOCAL_INTERFACE"));
    $WCCP_CERTIFICATE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_CERTIFICATE"));
    $WCCP_HTTPS_SERVICE_ID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTPS_SERVICE_ID"));

    if($WCCP_HTTP_PORT==0){$WCCP_HTTP_PORT=3126;}
    if($WCCP_HTTPS_PORT==0){$WCCP_HTTPS_PORT=3125;}
    if($WCCP_HTTPS_SERVICE_ID==0){$WCCP_HTTPS_SERVICE_ID=70;}

    $form[]=$tpl->field_interfaces("WCCP_LOCAL_INTERFACE","nooloopNoDef:{listen_interface}",$WCCP_LOCAL_INTERFACE);
    $form[]=$tpl->field_numeric("WCCP_HTTP_PORT","{listen_port} HTTP",$WCCP_HTTP_PORT);
    $form[]=$tpl->field_numeric("WCCP_HTTPS_SERVICE_ID","{service_id}",$WCCP_HTTPS_SERVICE_ID);
    $form[]=$tpl->field_numeric("WCCP_HTTPS_PORT","{listen_port} HTTPs",$WCCP_HTTPS_PORT);
    $form[]=$tpl->field_certificate("WCCP_CERTIFICATE","{use_certificate_from_certificate_center}",$WCCP_CERTIFICATE);

    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",jsApply(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<script>LoadAjaxSilent('syncthing-params','$page?parameters-flat=yes');</script>";

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/status"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return false;
    }


    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge($json->Error,"{error}"));
        return true;
    }

    $jsrestart=$tpl->framework_buildjs("/syncthing/restart",
        "syncthing.progress","syncthing.progress.log","progress-syncthing-restart","LoadAjaxSilent('syncthing-params','$page?parameters-flat=yes');");


    $ini = new Bs_IniHandler();
    $ini->loadString($json->Info);
    echo $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_SYNCTHING", $jsrestart));
    return true;
}


function parameters(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align: top'><div id='syncthing-status'></div></td>";
    $html[]="<td style='width:99%;vertical-align: top'><div id='syncthing-params'></div></td>";
    $html[]="</tr>";
    $html[]="<script>";
    $js=$tpl->RefreshInterval_js("syncthing-status",$page,"status=yes");

    $html[]="$js";
    $html[]="LoadAjaxSilent('syncthing-params','$page?parameters-flat=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function GetJson():array{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/config"));
    if(!$json->Status){
        return array(false,$tpl->div_error($json->Error));
    }
    if(!property_exists($json, "Config")){
        return  array(false,$tpl->div_error("Protocol error"));
    }
    $newJson=json_decode(base64_decode($json->Config));
    if(!$newJson){
        return  array(false,$tpl->div_error("Decoding data ".json_last_error()."<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}"));
    }
    return array(true,$newJson);
}

function instance_new_popup():bool{
    $tpl=new template_admin();
    $security="AsSambaAdministrator";

    $form[]=$tpl->field_text("new_instance_name","{instance_name}","",true);
    $form[]=$tpl->field_text("username","{username}","",true);
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{create}",jsApply(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function instance_new_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $instance=$_POST["new_instance_name"];
    $username=$_POST["username"];

    $usernameenc=urlencode($username);
    $instanceenc=urlencode($instance);
    $json=$GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/instances/create/$instanceenc/$usernameenc");
    if(!$json->Status){
        if(strlen($json->Error)>2) {
            echo $tpl->post_error($json->Error);
            return false;
        }
    }
    return admin_tracks("Create a new SyncThing Instance $instance for the owner $username");
}

function section_nics_popup():bool{
    $security="AsSambaAdministrator";
    list($is,$json)=GetJson();
    if(!$is){
        echo $json;
        return false;
    }
    $SyncThingInterfaces=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SyncThingInterfaces");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $form[]=$tpl->field_interfaces_choose("NICS","{listen_interfaces}",$SyncThingInterfaces);
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",jsApply(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_nics_save():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/restart/all");
    return true;
}



function jsApply():string{
    $function=$_GET["function"];
    $js[]="dialogInstance2.close();";
    if(strlen($function)>2){
        $js[]="$function();";
    }
   return @implode(";",$js);

}




function connected_port_popup(){
	$ID=intval($_GET["port-popup"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$btname="{add}";
	$title=$tpl->javascript_parse_text("{new_port}");
	$jsafter="LoadAjax('table-connected-proxy-ports','$page?connected-ports-list=yes');BootstrapDialog1.close();";

	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT * FROM proxy_ports WHERE ID=$ID");
		$title="{$ligne["nic"]}:{$ligne["port"]}";
		if($ligne["nic"]==null){$title="{listen_port}: {$ligne["port"]}";}
		$btname="{apply}";
		$jsafter="LoadAjax('table-connected-proxy-ports','$page?connected-ports-list=yes');";
	}
	
	$ip=new networking();
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);
	
	$array[null]        = "{all}";
	$array2[null]       = "{all}";
	$CountOfInterfaces  = 0;
	foreach ($interfaces as $eth){
		if(preg_match("#^(gre|dummy)#", $eth)){continue;}
		if($eth=="lo"){continue;}
		$nic=new system_nic($eth);
		if($nic->enabled==0){continue;}
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
		$array2[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
        $CountOfInterfaces++;
	}
	
	include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	if($ligne["ipaddr"]==null){$ligne["ipaddr"]="0.0.0.0";}
	if($ligne["port"]==0){$ligne["port"]=rand(1024,63000);}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	
	$form[]=$tpl->field_hidden("SaveConnectedPort",$ID);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[]=$tpl->field_numeric("port","{listen_port}",$ligne["port"]);
	$form[]=$tpl->field_text("PortName", "{service_name2}",  $ligne["PortName"]);
	$form[]=$tpl->field_text("xnote", "{description}",  $ligne["xnote"]);
	$form[]=$tpl->field_checkbox("ProxyProtocol","{proxy_protocol}",$ligne["ProxyProtocol"],false,"{proxy_protocol_explain}");
	
	$form[]=$tpl->field_checkbox("NoAuth","{disable_authentication}",$ligne["NoAuth"]);
	$form[]=$tpl->field_checkbox("NoCache","{disable_cache}",$ligne["NoCache"]);
	$form[]=$tpl->field_checkbox("NoFilter","{disable_webfiltering}",$ligne["NoFilter"]);

    if($CountOfInterfaces>1) {
        $form[] = $tpl->field_interfaces("nic", "{listen_interface}", $ligne["nic"]);
        $form[] = $tpl->field_interfaces("outgoing_addr", "{forward_interface}", $ligne["outgoing_addr"]);
    }else{
        $tpl->field_hidden("nic",null);
        $tpl->field_hidden("outgoing_addr",null);
    }
	$form[]=$tpl->field_checkbox("UseSSL","{decrypt_ssl}",$ligne["UseSSL"],"sslcertificate","{listen_port_ssl_explain}");
	$form[]=$tpl->field_array_hash($sslcertificates, "sslcertificate", "{use_certificate_from_certificate_center}", $ligne["sslcertificate"]);
	
	$security="AsSquidAdministrator";
	$html[]=$tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsafter,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function communications_ports_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$sock=new sockets();
	foreach ($_POST as $key=>$val){
		$sock->SET_INFO($key, $val);
		
	}
	
	
	
}


	function CheckPointers(){
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND is_nat=1";
		$ligne=$q->mysqli_fetch_array($sql);
		$sock=new sockets();
		if($ligne["tcount"]>0){$sock->SET_INFO("EnableTransparent27", 1);}else{$sock->SET_INFO("EnableTransparent27", 0); }
	
		$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND WCCP=1";
		$ligne=$q->mysqli_fetch_array($sql);
		$sock=new sockets();
		if($ligne["tcount"]>0){$sock->SET_INFO("SquidWCCPEnabled", 1);}else{$sock->SET_INFO("SquidWCCPEnabled", 0); }
	
		$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND FTP=1";
		$ligne=$q->mysqli_fetch_array($sql);
		$sock=new sockets();
		if($ligne["tcount"]>0){$sock->SET_INFO("ServiceFTPEnabled", 1);}else{$sock->SET_INFO("ServiceFTPEnabled", 0); }
	
		$sock=new sockets();
		$sock->getFrameWork("ftp-proxy.php?reconfigure-silent=yes");
	
	}	

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{APP_SYNCTHING} {instances}",ico_servcloud2,
        "{syncthing_instances_explain}","$page?start=yes","syncthing-instances","progress-syncthing-restart",false,"table-loader-syncthing-instances");


	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page);
    return true;

}
function td_fullsize($ID):string{
    $json=td_json($ID);
    $tpl=new template_admin();
    if(!property_exists($json,"Instance")){
        return $tpl->icon_nothing();
    }
    if($json->Instance->Enabled==0){
        return $tpl->icon_nothing();
    }

    if(!property_exists($json->Instance,"StateSize")){
        return $tpl->icon_nothing();
    }
    if(!property_exists($json->Instance->StateSize,"totalSize")){
        return $tpl->icon_nothing();
    }

    if($json->Instance->StateSize->totalSize<2048){
        return $tpl->icon_nothing();
    }

    return FormatBytes($json->Instance->StateSize->totalSize/1024);
}
function table():bool{
    $tpl=new template_admin();
    $function=$_GET['function'];
    $page=CurrentPageName();
    $t=time();
    $TRCLASS="";
    $html[]="<div id='instances-table-$t'></div>";
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{instance}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{devices}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>Web</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{port}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' >{owner}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text' nowrap>{running_since}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{memory}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $filter=1;
    $jsID=array();
    $search=$_GET["search"];
    if(strlen($search)>0){
        $search="*".$search."*";
        $search=str_replace("**","*",$search);
        $search=str_replace("**","*",$search);
        $search=str_replace("**","%",$search);
        $filter="WHERE instancename LIKE '$search' or username LIKE '$search'";
    }
    $q=new lib_sqlite("/home/artica/SQLITE/syncthing.db");
    $results=$q->QUERY_SQL("SELECT * FROM instances WHERE $filter ORDER BY instancename ASC");
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
        return false;
    }
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne["ID"];
        $md=md5(serialize($ligne));
        $instancename=$ligne["instancename"];
        $username=$ligne["username"];
        $uiport=$ligne["uiport"];
        $listenport=$ligne["listenport"];
        $enabled=$ligne["enabled"];
        $webaccessEnabled=td_webAccess($ID);
        $webaccessbtn=td_webAccessBTN($ID);
        $events=td_events($ID);
        $action=td_action($ID);
        $memory=td_memory($ID);
        $pending=td_pending($ID);
        $devices=td_devices($ID);
        $fullsize=td_fullsize($ID);
        $ttl=td_ttl($ID);
        $jsID[]=$ID;

        $instancename=$tpl->td_href($instancename,null,"Loadjs('$page?instance-js={$ligne['ID']}')");

        $username=$tpl->td_href($username,null,"Loadjs('$page?instance-username-js={$ligne['ID']}&function=$function')");

        $DeleteJS="Loadjs('$page?instance-delete-js={$ligne['ID']}&md=$md')";
        $status=td_status($ID);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap><span id='$ID-status'>$status</span></td>";
        $html[]="<td><strong>$instancename</strong><span id='$ID-working'></span></td>";
        $html[]="<td style='width:1%' nowrap><span id=\"$ID-size\">$fullsize</span></td>";
        $html[]="<td style='width:1%' nowrap><span id=\"$ID-pending\">$pending</span></td>";
        $html[]="<td style='width:1%' nowrap><span id=\"$ID-devices\">$devices</span></td>";
        $html[]="<td style='width:1%' nowrap><span id=\"$ID-events\">$events</span></td>";
        $html[]="<td style='width:1%' nowrap><span id=\"$ID-web\">$webaccessEnabled</span></td>";
        $html[]="<td style='width:1%' class='center' nowrap><span id=\"$ID-webenable\">$webaccessbtn</span></td>";
        $html[]="<td style='width:1%'>$uiport/$listenport</td>";
        $html[]="<td style='width:1%'>$username</td>";
        $html[]="<td style='width:1%' nowrap><span id='$ID-ttl'>$ttl</span></td>";
        $html[]="<td style='width:1%' nowrap><span id='$ID-memory'>$memory</span></td>";
        $html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_check($enabled,"Loadjs
           ('$page?instance-enable=$ID')",null,"AsSambaAdministrator")."</center></td>";
        $html[]="<td style='width:1%' nowrap><span id='$ID-action'>$action</span></td>";
        $html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_delete($DeleteJS,"AsSambaAdministrator")."</center></td>";
        $html[]="</tr>";

}
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $alls=@implode(",",$jsID);

    $topbuttons[] = array("Loadjs('$page?instance-new-js=yes&function=$function')", ico_plus, "{new_instance2}");

    $TINY_ARRAY["TITLE"]="{APP_SYNCTHING} &raquo;&raquo; {instances}";
    $TINY_ARRAY["ICO"]=ico_servcloud2;
    $TINY_ARRAY["EXPL"]="{syncthing_instances_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs= "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $js=$tpl->RefreshInterval_Loadjs("instances-table-$t",$page,"td-status=$alls");

    $html[]="
    <script>
    $js
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
    $headsjs
    </script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function td_json($ID){
    if(!isset($GLOBALS["SYNCTHING_$ID"])) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/instance/status/$ID"));
        $GLOBALS["SYNCTHING_$ID"]=$json;
    }
    return $GLOBALS["SYNCTHING_$ID"];
}
function td_devices($ID):int{
    $json=td_json($ID);
    if(!property_exists($json,"Instance")){
        return 0;
    }
    if($json->Instance->Enabled==0){
        return 0;
    }

    if(!$json->Instance->Running){
        return 0;
    }
    if(!property_exists($json->Instance,"TrustedDevices")){
        return 0;
    }
    $c=0;
    foreach ($json->Instance->TrustedDevices as $uid=>$device) {
        $c++;
    }


    return $c;


}
function td_pending_folders($ID):string{
    $json=td_json($ID);
    if(!property_exists($json,"Instance")){
        return "";
    }
    if($json->Instance->Enabled==0){
        return "";
    }

    if(!$json->Instance->Running){
        return "";
    }

    if(!property_exists($json->Instance,"PendingDevices")){
        return "";
    }
    $c=0;
    if (is_array($json->Instance->PendingSharedFolder) || is_object($json->Instance->PendingDevices)) {
        foreach ($json->Instance->PendingSharedFolder as $uid => $device) {
            $c++;
        }
    }

    if($c==0){
        return "";
    }
    $class=ico_folder;
    return "&nbsp;<i class='text-warning $class'></i>";

}
function td_pending($ID):string{
    $json=td_json($ID);
    if(!property_exists($json,"Instance")){
        return "&nbsp;";
    }
    if($json->Instance->Enabled==0){
        return "-";
    }

    if(!$json->Instance->Running){
        return "&nbsp;";
    }
    if(!property_exists($json->Instance,"PendingDevices")){
        return td_pending_folders($ID);
    }
    $c=0;
    if (is_array($json->Instance->PendingDevices) || is_object($json->Instance->PendingDevices)) {
        foreach ($json->Instance->PendingDevices as $uid => $device) {
            $c++;
        }
    }

    if($c==0){
        return td_pending_folders($ID);
    }

    return "<i class='text-warning fa fa-bell'></i>".td_pending_folders($ID);

}
function td_working($ID):string{
    $f=array();
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/instance/working/$ID"));
    if(!property_exists($json,"States")){
        return "";
    }

    $icof="<i class='fas fa-folder'></i>";
    if(property_exists($json->States,"overallState")){
        if($json->States->overallState=="idle"){return "";}
        $f[] = "&nbsp;&nbsp;&nbsp;&nbsp;<span class='label label-warning'><i class='fad fa-hourglass-start'></i>&nbsp;{{$json->States->overallState}}</span>";

    }
    if(!property_exists($json->States,"folders")){
        return $tpl->_ENGINE_parse_body(@implode("\n",$f));
    }

    foreach ($json->States->folders as $folder){
        if($folder->state=="idle"){
            continue;
        }
        $folderID=$folder->folderID;
        $prc=round($folder->progress,2);
        $f[]="<div>$icof&nbsp;$folderID: $folder->state {$prc}%</div>";

    }

    return $tpl->_ENGINE_parse_body(@implode("\n",$f));

}

function td_memory($ID):string{
    $json=td_json($ID);
    if(!property_exists($json,"Instance")){
        return "&nbsp;";
    }
    if($json->Instance->Enabled==0){
        return "-";
    }

    if(!$json->Instance->Running){
        return "0.0";
    }
    return $json->Instance->MemoryString;
}
function td_ttl($ID):string{
    $json=td_json($ID);
    if(!property_exists($json,"Instance")){
        return "&nbsp;";
    }
    if($json->Instance->Enabled==0){
        return "-";
    }

    if(!$json->Instance->Running){
        return "-";
    }
    return $json->Instance->TTL;
}


function td_webAccessBTN($ID):string{
    $page=CurrentPageName();
    $json=td_json($ID);

    $js="Loadjs('$page?instance-web=$ID')";
    $OnMouse[]= "OnClick=\"$js;\"";
    $OnMouse[]="OnMouseOver=\";this.style.cursor='pointer';\"";
    $OnMouse[]="OnMouseOut=\";this.style.cursor='default';\"";
    $glances_url=@implode(" ", $OnMouse);

    if(!property_exists($json,"Instance")){
        return "&nbsp;";
    }
    if($json->Instance->Enabled==0){
        return "<span class='label label-default' $glances_url>OFF</span>";
    }
    if(strlen($json->Instance->Instance->WebaccessID)<3){
        return "<span class='label label-default' $glances_url>OFF</span>";
    }

    return "<span class='label label-primary' $glances_url>ON</span>";
}
function td_webAccess($ID):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=td_json($ID);
    if(!property_exists($json,"Instance")){
        return "<span class='label label-warning'>{web_console}</span>";
    }
    if($json->Instance->Enabled==0){
        return "<span class='label label-default'>{web_console}</span>";
    }

    if($json->Instance->Instance->WebAccess==0){
        return "<span class='label label-default'>{web_console}</span>";
    }

    if(strlen($json->Instance->Instance->WebaccessID)>2){
        $webaccessID=$json->Instance->Instance->WebaccessID;
        $instancename=$json->Instance->Instance->instancename;
        $click="OnClick=\"s_PopUp('/$webaccessID/',1024,768,'$instancename');\"";
        $OnMouse[]= $click;
        $OnMouse[]="OnMouseOver=\";this.style.cursor='pointer';\"";
        $OnMouse[]="OnMouseOut=\";this.style.cursor='default';\"";
        $glancesUrl=@implode(" ", $OnMouse);
        return "<span class=\"label label-primary\" $glancesUrl>{web_console}</span>";
    }
    return "";

}

function td_events($ID):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=td_json($ID);

    if(!property_exists($json,"Instance")){
        return "<span class='label label-warning'>{events}</span>";
    }
    if($json->Instance->Enabled==0){
        return "<span class='label label-default'>{events}</span>";
    }
    $click="OnClick=\"Loadjs('$page?instance-events-js=$ID}');\"";
    $OnMouse[]= $click;
    $OnMouse[]="OnMouseOver=\";this.style.cursor='pointer';\"";
    $OnMouse[]="OnMouseOut=\";this.style.cursor='default';\"";
    $glancesUrl=@implode(" ", $OnMouse);

    if(!$json->Instance->Running){
        return "<label class='label label-danger' $glancesUrl>{events}</label>";
    }

    return "<span class=\"label label-primary\" $glancesUrl>{events}</span>";
}
function td_action($ID):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=td_json($ID);

    if(!property_exists($json,"Instance")){
        return "&nbsp;";
    }
    if($json->Instance->Enabled==0){
        return $tpl->icon_start("","AsSambaAdministrator");
    }

    if(!$json->Instance->Running){
        return $tpl->icon_start("Loadjs('$page?instance-start=$ID')","AsSambaAdministrator");
    }
    return $tpl->icon_stop("Loadjs('$page?instance-stop=$ID')","AsSambaAdministrator");
}
function td_status_all(){
    $tpl=new template_admin();
    $ids=explode(",",$_GET["td-status"]);
    $f=array();
    foreach($ids as $ID){
        $status=base64_encode($tpl->_ENGINE_parse_body(td_status($ID)));
        $action=base64_encode($tpl->_ENGINE_parse_body(td_action($ID)));
        $www=base64_encode($tpl->_ENGINE_parse_body(td_webAccess($ID)));
        $wwwBtn=base64_encode($tpl->_ENGINE_parse_body(td_webAccessBTN($ID)));
        $altert_pending=base64_encode(td_pending($ID));
        $fullsize=base64_encode(td_fullsize($ID));
        $working=base64_encode(td_working($ID));

        $memory=td_memory($ID);
        $ttl=td_ttl($ID);
        $f[]="if( document.getElementById('$ID-status') ){";
        $f[]="document.getElementById('$ID-status').innerHTML=base64_decode('$status');";
        $f[]="}";
        $f[]="if( document.getElementById('$ID-action') ){";
        $f[]="document.getElementById('$ID-action').innerHTML=base64_decode('$action');";
        $f[]="}";
        $f[]="if( document.getElementById('$ID-memory') ){";
        $f[]="document.getElementById('$ID-memory').innerHTML='$memory';";
        $f[]="}";
        $f[]="if( document.getElementById('$ID-ttl') ){";
        $f[]="document.getElementById('$ID-ttl').innerHTML='$ttl';";
        $f[]="}";
        $f[]="if( document.getElementById('$ID-web') ){";
        $f[]="document.getElementById('$ID-web').innerHTML=base64_decode('$www');";
        $f[]="}";
        $f[]="if( document.getElementById('$ID-webenable') ){";
        $f[]="document.getElementById('$ID-webenable').innerHTML=base64_decode('$wwwBtn');";
        $f[]="}";
        $f[]="if( document.getElementById('$ID-pending') ){";
        $f[]="document.getElementById('$ID-pending').innerHTML=base64_decode('$altert_pending');";
        $f[]="}";
        $f[]="if( document.getElementById('$ID-size') ){";
        $f[]="document.getElementById('$ID-size').innerHTML=base64_decode('$fullsize');";
        $f[]="}";
        $f[] = "if( document.getElementById('$ID-working') ){";
        $f[] = "\tdocument.getElementById('$ID-working').innerHTML=base64_decode('$working');";
        $f[] = "}";






    }

    header("content-type: application/x-javascript");
    echo @implode("\n",$f);

}
function td_status($ID):string{
    $json=td_json($ID);
    if(!$json->Status){
        return "<label class='label label-danger'>{error}</label>";
    }

    if(!property_exists($json,"Instance")){
        return "<label class='label label-danger'>{error}</label>";
    }

    if($json->Instance->Enabled==0){
        return "<label class='label label-default'>{inactive2}</label>";
    }

    if(!$json->Instance->Running){
        return "<label class='label label-danger'>{stopped}</label>";
    }
    return "<label class='label label-primary'>{running}</label>";
}

function instance_folders_button():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $instanceid=intval($_GET["instance-folders-buttons"]);
    $topbuttons[] = array("Loadjs('$page?folder-add-js=$instanceid&function=$function')", ico_plus, "{new_folder}");
   echo $tpl->th_buttons($topbuttons);
   return true;
}
function instance_folders_add_js():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instanceid=$_GET["folder-add-js"];
    return $tpl->js_dialog3("{new_folder}","$page?folder-add-popup=$instanceid&function=$function",650);
}
function instance_folders_add_popup():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instanceid=$_GET["folder-add-popup"];
    $json=td_json($instanceid);
    if(!property_exists($json,"Instance")){
        echo $tpl->div_error("Json failed");
        return false;
    }

    if(!property_exists($json->Instance,"Instance")){
        echo $tpl->div_error("Json failed");
    return false;
    }
    $HomeDir=$json->Instance->Instance->HomeDir;
    $html[]=$tpl->field_hidden("folder-add-save",$instanceid);
    $html[]=$tpl->field_browse_directory("path","{directory}","",$HomeDir);
    $js[]="dialogInstance3.close();";
    $js[]="$function();";
    echo $tpl->form_outside("",$html,"","{create}",@implode(";",$js));
    return true;
}
function instance_folders_del_ask():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    //instance-folder-delete=$instanceid&ID=$ID&md=$md
    $instanceid=$_GET["instance-folder-delete"];
    $md=$_GET["md"];
    $ID=$_GET["ID"];
    return $tpl->js_confirm_delete("{folder} $ID","instance-folder-delete","$instanceid|$ID","$('#$md').remove()");
}
function instance_folders_del_perform():bool{
    $val=$_POST["instance-folder-delete"];
    $tb=explode("|",$val);
    //
    $instanceid=intval($tb[0]);
    $folderid=urlencode($tb[1]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syncthing/instance/$instanceid/folder/delete/$folderid"));
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
    return admin_tracks("Deleted a shared folder $folderid from the instance #$instanceid");
}
function instance_folders_add_save():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->CLEAN_POST();
    $instanceid=intval($_POST["folder-add-save"]);
    $Path=$_POST["path"];
    $array["path"]=$Path;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST("/syncthing/instance/$instanceid/folder/create",$array));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks("Created a new shared folder $Path for instance #$instanceid");

}

function instance_folders(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instanceid=intval($_GET["instance-folders"]);
    $html[]="<div id='instance-folders-$instanceid' style='margin-top:10px;margin-bottom:10px'></div>";
    $html[]=$tpl->search_block($page,"","","","&folders-search=$instanceid");
    echo @implode("\n",$html);
}

function instance_folders_search(){
    $TRCLASS="";
    $function=$_GET["function"];
    $page=CurrentPageName();
    $instanceid=$_GET["folders-search"];
    $tpl=new template_admin();
    $json=td_json($instanceid);
    if(!property_exists($json->Instance,"Folders")){
        $tpl->div_error("Invalid instance..");
        return false;
    }
    $html[]="<table id='table-$instanceid' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{path}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ID}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{synchronization}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $ico=ico_folder;
    $SSIZES=array();
    foreach($json->Instance->StateSize->folders as $folder){
        $folderID=$folder->folderID;
        $label=$folder->label;
        $size=$folder->size;
        $SSIZES[$folderID]=$size;


    }

    foreach (get_object_vars($json->Instance->Folders) as $path => $ligne) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $ID=$ligne->UniqueID;
        $md=md5(serialize($ligne));
        $label=$ligne->Label;
        $SyncType=$ligne->SyncType;

        $SyncType=$tpl->td_href("{{$SyncType}}","","Loadjs('$page?instance-folder-sync=$instanceid&folder=$ID&function=$function');");

        $size=0;
        if(isset($SSIZES[$ID])){
            $size=FormatBytes($SSIZES[$ID]/1024);
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td><strong><i class='$ico'></i>&nbsp;". $tpl->td_href($path,null,"Loadjs('$page?service-js=$ID')")."</strong> ($label)";
        $html[]="<td>$ID</td>";
        $html[]="<td>$SyncType</td>";
        $html[]="<td>$size</td>";
        $html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_delete("Loadjs('$page?instance-folder-delete=$instanceid&ID=$ID&md=$md')","AsFirewallManager")."</center></td>";
        $html[]="</tr>";

    }


    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
    <script>
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
    LoadAjaxSilent('instance-folders-$instanceid','$page?instance-folders-buttons=$instanceid&function=$function');
    </script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}