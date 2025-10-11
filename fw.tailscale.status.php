<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_POST["TailScaleHostname"])){SaveSettings();exit;}
if(isset($_POST["RDPProxyAuthookDebug"])){video_params_save();exit;}
if(isset($_POST["RDPProxyPort"])){SaveSettings();exit;}
if(isset($_POST["TailScaleAuthorizationKey"])){authorizationkey_save();exit;}
if(isset($_GET["authorizationkey-js"])){authorizationkey_js();exit;}
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
if(isset($_GET["authorizationkey-popup"])){authorizationkey_popup();exit;}
page();

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table-start=yes";
    $array["{nodes}"]="$page?nodes=yes";

    echo $tpl->tabs_default($array);
}
function table_start(){
    $page=CurrentPageName();
    echo "<div id='tailscale-status-table' style='margin-top:10px'></div>
    <script>LoadAjax('tailscale-status-table','$page?table=yes');</script>";
}


function authorizationkey_js(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $tpl->js_dialog1("{authorizationkey}","$page?authorizationkey-popup=yes");

}
function authorizationkey_save(){
    $tpl    = new template_admin();
    $tpl->CLEAN_POST();
    admin_tracks("Saving TailScale Cloud VPN Authorization key {$_POST["TailScaleAuthorizationKey"]}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("TailScaleAuthorizationKey",$_POST["TailScaleAuthorizationKey"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("tailscale.php?connect=yes");
}

function authorizationkey_popup(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $form[]=$tpl->field_text("TailScaleAuthorizationKey","{authorizationkey}",null);
    echo $tpl->form_outside("{key}",$form,null,"{apply}","dialogInstance1.close();LoadAjaxSilent('tailscale-widgets','$page?widgets=yes');","AsSystemAdministrator");
}

function settings(){
    $page   = CurrentPageName();
    $tpl=new template_admin();
    $TailScaleHostname = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("TailScaleHostname");
    $TailScaleInComCnx = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TailScaleInComCnx"));
    $TailScanUseSbunets = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TailScanUseSbunets"));
    $TailScanUseDNS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TailScanUseDNS"));
    $EnableArticaAsGateway = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway"));
    $TailScaleAsGateway= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TailScaleAsGateway"));
    $TailScaleAsGatewayEnabled=false;

    if($EnableArticaAsGateway==0){
        $TailScaleAsGateway=0;
        $TailScaleAsGatewayEnabled=true;
    }

    $form[]=$tpl->field_checkbox("TailScanUseSbunets","{accept_routes}",$TailScanUseSbunets);
    $form[]=$tpl->field_checkbox("TailScaleAsGateway","{act_as_gateway}",$TailScaleAsGateway,false,null,$TailScaleAsGatewayEnabled);
    $form[]=$tpl->field_checkbox("TailScaleInComCnx","{allow_incoming_connections}",$TailScaleInComCnx);
    $form[]=$tpl->field_checkbox("TailScanUseDNS","{use_network_dns_settings}",$TailScanUseDNS);
    $form[]=$tpl->field_text("TailScaleHostname","{displayed_hostname}",$TailScaleHostname);


    $btn_term_js="s_PopUpFull('https://tailscale.com/terms','1024','900');";
    $tpl->form_add_button("{terme_of_use}",$btn_term_js);

    echo $tpl->form_outside("{parameters}",$form,null,"{apply}",
        "LoadAjaxSilent('tailscale-widgets','$page?widgets=yes');","AsSystemAdministrator");




}

function SaveSettings(){
    $tpl=new template_admin();
    $log=$tpl->SAVE_POSTs();
    foreach ($log as $key=>$val){
        $tt[]="$key = $val";
    }
    admin_tracks("TailScale parameters changed ".@implode(", ",$tt));
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("tailscale.php?connect=yes");
}

function nodes():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("tailscale.php?status=yes");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tableid=time();
    $TRCLASS=null;
    $html[]=$tpl->table_head(array("{vpn_hosts}","{ipaddr}","{transmission}","{reception}"),"table-$tableid");
    $INFOS=unserialize(@file_get_contents(PROGRESS_DIR."/tailscale.infos"));
    $sjson=json_decode( $INFOS["JSON"]);

    if(!property_exists($sjson,"Peer")) {
        echo $tpl->div_error("{no_data}");
        return false;
    }
        foreach ($sjson->Peer as $index=>$Peer){
            $HostName=$Peer->HostName;
            $TailAddr=$Peer->TailAddr;
            $RxBytes=$Peer->RxBytes;
            $TxBytes=$Peer->TxBytes;
            if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
            if($RxBytes>1024){
                $RxBytes=FormatBytes($RxBytes/1024);
            }
            if($TxBytes>1024){
                $TxBytes=FormatBytes($TxBytes/1024);
            }
            $html[]="<tr class='$TRCLASS' id='$index'>";

            $html[]="<td width=50% ><strong>$HostName</strong></td>";
            $html[]="<td width=1% nowrap>$TailAddr</td>";
            $html[]="<td width=1% nowrap>$TxBytes</td>";
            $html[]="<td width=1% nowrap>$RxBytes</td>";
            $html[]="</tr>";
        }
        $html[]=$tpl->table_footer("table-$tableid",4,false);
        echo $tpl->_ENGINE_parse_body($html);

    return true;
}



function page():bool{
    $page=CurrentPageName();
    $APP_TAILSCALE_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_TAILSCALE_VERSION");
    $users  = new usersMenus();
    if(!$users->AsVPNManager){return false;}

    $html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_TAILSCALE} v$APP_TAILSCALE_VERSION &raquo;&raquo; {service_status}</h1>
	<p>{APP_TAILSCALE_ABOUT}</p>
    </div>
	</div>
	<div class='row'><div id='progress-tailscale-restart'></div>
	<div class='ibox-content' style='min-height:600px'>
	<div id='table-tailscale-status'></div>
	</div>
	</div>
    <script>
	$.address.state('/');
	$.address.value('/tailscale');
	LoadAjax('table-tailscale-status','$page?tabs=yes');
	</script>";

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_TAILSCALE} v$APP_TAILSCALE_VERSION &raquo;&raquo; {service_status}",$html);
        echo $tpl->build_firewall();
        return true;
    }


    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("tailscale.php?status=yes");
    $ini->loadFile(PROGRESS_DIR."/tailscale.status");

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/tailscale.restart.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/tailscale.restart.log";
    $ARRAY["CMD"]="tailscale.php?restart=yes";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="LoadAjax('tailscale-status-table','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $restartService="Loadjs('fw.progress.php?content=$prgress&mainid=progress-tailscale-restart');";


    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:260px' valign='top'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr><td>
	<div class=\"ibox\">
    	<div class=\"ibox-content\">".
        $tpl->SERVICE_STATUS($ini, "APP_TAILSCALE",$restartService).
        $tpl->SERVICE_STATUS($ini, "APP_TAILSCALE_WEB")

        ."</div>
	    	</div>
	    </td>
	</tr>";
    $html[]="</table></td>";



    $html[]="<td style='width:99%;vertical-align:top'>
            <div id='tailscale-widgets'></div>
            <div id='tailscale-config'></div>";
    $html[]="</td>";
    $html[]="</tr>";

    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('tailscale-widgets','$page?widgets=yes');";
    $html[]="LoadAjax('tailscale-config','$page?settings=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function loggoff():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("tailscale.php?logoff=yes");
    sleep(1);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    admin_tracks("Close TailScale VPN Session");
    echo "LoadAjaxSilent('tailscale-widgets','$page?widgets=yes');";
    return true;

}
function disconnect(){
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("tailscale.php?disconnect=yes");
    sleep(1);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    admin_tracks("Disconnect from TailScale Cloud VPN");
    echo "LoadAjaxSilent('tailscale-widgets','$page?widgets=yes');";
    return true;
}
function connect(){
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("tailscale.php?connect=yes");
    sleep(1);
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    admin_tracks("Connecting from TailScale Cloud VPN");
    echo "LoadAjaxSilent('tailscale-widgets','$page?widgets=yes');";
    return true;
}


function widgets():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("tailscale.php?status=yes");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $INFOS=unserialize(@file_get_contents(PROGRESS_DIR."/tailscale.infos"));
    $json=json_decode($INFOS["JSON"]);
    if(!isset($INFOS["CURRENT_IP"])){$INFOS["CURRENT_IP"]=null;}


    $TailScaleAuthorizationKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TailScaleAuthorizationKey"));


    $Connected  = false;
    $needlogin  = false;
    $AuthURL    = null;
    $LoginName  = null;

    if(property_exists($json,"BackendState")){
        VERBOSE("BackendState == $json->BackendState");

        if(strtolower($json->BackendState)=="running"){
            $Connected=true;
        }
        if(strtolower($json->BackendState)=="stopped"){
            $Connected=false;
        }


        if(strtolower($json->BackendState)==strtolower("NeedsLogin")){
            $Connected=false;
            $needlogin=true;
        }

    }

    if($needlogin){
        $AuthURL=$json->AuthURL;

    }
    if(property_exists($json ,"User")) {
        foreach ($json->User as $index => $line) {
            if (property_exists($line, "LoginName")) {
                $LoginName = $line->LoginName;
                break;
            }
        }
    }

    if($needlogin){

        $btn["name"]="{please_sign_in}";
        $btn["icon"]="fas fa-sign-in-alt";
        if($AuthURL<>null) {
            $btn["js"] = "s_PopUpFull('$AuthURL','1024','900');";
        }else{
            $btn["js"] = "s_PopUpFull('/tailscale-web/')";
        }
        $needlogin_status=$tpl->widget_h("yellow","fas fa-sign-in-alt","{need_sign_in}","{authenticate}",$btn);
    }else{
        $Peers=count($INFOS["PEERS"]);
        $btn["name"]="{loggoff}";
        $btn["icon"]="fas fa-sign-in-alt";
        $btn["js"] = "Loadjs('$page?loggoff=yes');";
        $needlogin_status=$tpl->widget_h("blue","fas fa-sign-in-alt","{active_session} ","$LoginName ($Peers {nodes})",$btn);

    }

    if(!$Connected) {
        $btn["name"]="{connect}";
        $btn["icon"]="fas fa-link";
        $btn["js"] = "Loadjs('$page?connect=yes');";
        $Connected_status = $tpl->widget_h("grey", "fas fa-unlink", "{not_connected}", "{connection_status}",$btn);

    }else{
        $btn["name"]="{disconnect}";
        $btn["icon"]="fas fa-unlink";
        $btn["js"] = "Loadjs('$page?disconnect=yes');";
        $state="{connected}";$addstate="{connection_status}";
        if($INFOS["CURRENT_IP"]<>null) {$addstate="{vpn_address}";$state=$INFOS["CURRENT_IP"];}
        $Connected_status= $tpl->widget_h("green", "fas fa-link", $state, "$addstate",$btn);
    }

    if($TailScaleAuthorizationKey==null) {
        $btn["name"]="{authorizationkey}";
        $btn["icon"]="fa-thumbs-up";
        $btn["js"] = "Loadjs('$page?authorizationkey-js=yes');";
        $status_addr = $tpl->widget_h("grey", "fa-thumbs-down", "{not_used}", "{authorizationkey}",$btn);

    }else{
        $status_addr= $tpl->widget_h("green", "fa-thumbs-up", "{active2}", "{authorizationkey}");
    }
   $html[]="<table style='width:100%'>
	    <tr>
	    <td style='vertical-align:top;width:200px;padding:8px'>$needlogin_status</td>
	    <td style='vertical-align:top;width:200px;padding:8px'>$Connected_status</td>
	    <td style='vertical-align:top;width:200px;padding:8px'>$status_addr</td>
	    </tr>
	   </table>
<script>
    function TailScaleRunStatus(){
        if(!document.getElementById('tailscale-widgets')){return false;}
        LoadAjaxSilent('tailscale-widgets','$page?widgets=yes');
    }
    setTimeout('TailScaleRunStatus()', 5000)
</script>
    ";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

