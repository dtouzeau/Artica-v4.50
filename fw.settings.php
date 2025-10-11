<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["nic-form-default-popup"])){nic_form_default_popup();exit;}
if(isset($_GET["nic-fw-default"])){nic_form_default_js();exit;}
if(isset($_GET["firewall-status"])){firewall_status();exit;}
if(isset($_POST["nic-default"])){nic_form_default_save();exit;}
if(isset($_POST["nic-settings"])){nic_settings();exit;}
if(isset($_POST["FireHoleStoreEvents"])){logs_settings_save();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["flat"])){flat();exit;}
if(isset($_GET["nic-fw-edit"])){nic_form_settings();exit;}
if(isset($_GET["nic-form-settings-build"])){nic_form_settings_build();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["interfaces-start"])){interfaces_start();exit;}
if(isset($_GET["interfaces"])){interfaces();exit;}
if(isset($_GET["modules-start"])){modules_start();exit;}
if(isset($_GET["modules-table"])){modules_table();exit;}
if(isset($_GET["fw-parms-js"])){fw_params_js();exit;}
if(isset($_GET["fw-parms-popup"])){fw_params_popup();exit;}
page();


function tabs(){
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $array["{interfaces}"]="$page?interfaces-start=yes";
    $array["{modules}"]="fw.settings.php?modules-start=yes";
    echo $tpl->tabs_default($array);
}

function nic_settings(){
	//print_r($_POST);
	$nic=new system_nic($_POST["nic-settings"]);
	$nic->isFW=$_POST["isFW"];
	$nic->firewall_policy=$_POST["firewall_policy"];
	$nic->firewall_behavior=$_POST["firewall_behavior"];
	$nic->firewall_masquerade=$_POST["firewall_masquerade"];
	$nic->firewall_artica=$_POST["firewall_artica"];
	$nic->firewall_ping=$_POST["firewall_ping"];
    $nic->denydhcp=$_POST["denydhcp"];
    $nic->AntiDDOS=$_POST["AntiDDOS"];
    $nic->xtIpv4options=$_POST["xtIpv4options"];
	$nic->SaveNic();
	
}

function firewall_status():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();


    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/isactive"));

    if(!property_exists($data,"Status")){
        $status=$tpl->widget_rouge("{firewall_status}","API ERROR","");
        echo $tpl->_ENGINE_parse_body($status);
        return true;
    }

    if(!$data->Status){
          $jsrestart=$tpl->framework_buildjs(
            "/firewall/reconfigure","firehol.reconfigure.progress",
            "firehol.reconfigure.log",
            "progress-firehol-restart",
        "LoadAjax('table-loader','$page?tabs=yes');");

        $btn[0]["name"]="{start}";
        $btn[0]["icon"]=ico_play;
        $btn[0]["js"]=$jsrestart;
        $status=$tpl->widget_rouge("{firewall_status}","{inactive}",$btn);
    }else{
        $time=$data->Since;
        $jsrestart=$tpl->framework_buildjs(
            "/firewall/stop","
            firehol.reconfigure.progress",
            "firehol.reconfigure.log",
            "progress-firehol-restart",
            "LoadAjax('table-loader','$page?tabs=yes');");

        $btn[0]["name"]="{stop}";
        $btn[0]["icon"]="fas fa-stop";
        $btn[0]["js"]=$jsrestart;

        $jsrestart=$tpl->framework_buildjs(
            "/firewall/reconfigure",
            "firehol.reconfigure.progress",
            "firehol.reconfigure.log",
            "progress-firehol-restart",
            "LoadAjax('table-loader','$page?tabs=yes');");


        $btn[1]["name"]="{restart}";
        $btn[1]["icon"]="fas fa-sync";
        $btn[1]["js"]=$jsrestart;
        $sdate=$tpl->time_to_date($time,true);
        $status=$tpl->widget_vert("{firewall_status}","{active2}<br><small style='color:white;font-size: 12px'>{last_config}:&nbsp;$sdate</small>",$btn);
    }

    $APP_XKERNEL=$tpl->widget_vert("{APP_XKERNEL}","{installed}");
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/xtables"));
    if(!$json->Status) {
        if (property_exists($json, "Version")) {
            $kernver = $json->Version;
            $js="Loadjs('fw.system.upgrade-software.php?product=APP_XKERNEL');";
            $btn=array();
            $btn[0]["name"] = "{fix_it}";
            $btn[0]["icon"] = ico_cd;
            $btn[0]["js"] = $js;
            $APP_XKERNEL=$tpl->widget_jaune("{APP_XKERNEL} $kernver","{not_installed}",$btn);
        }
    }

    echo $tpl->_ENGINE_parse_body($status.$APP_XKERNEL);
    return true;
}

function modules_start(){
    $page       = CurrentPageName();
    $addon=null;
    if(isset($_GET["OnlyLoaded"])){$addon="&OnlyLoaded=yes";}
    echo        "<div id='iptables-modules-start' style='margin-top:10px'></div><script>LoadAjax('iptables-modules-start','$page?modules-table=yes$addon')</script>";
}

function page(){
	$page               = CurrentPageName();
	$tpl                = new template_admin();
	$IPTABLES_VERSION   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_VERSION");
$html=$tpl->page_header("{firewall_parameters} v$IPTABLES_VERSION",
    "fab fa-free-code-camp","{firewallexplain}","$page?tabs=yes","firewall-parameters",
        "progress-firehol-restart",false,"table-loader");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{firewall_parameters}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);

}

function logs_settings_save():bool{
	$tpl=new template_admin();
	$tpl->SAVE_POSTs();
    return admin_tracks_post("Save global firewall interface settings");
}
function nic_form_settings():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();	
	$val=$_GET["nic-fw-edit"];
	$nic=new system_nic($val);
	return $tpl->js_dialog1("$val $nic->NICNAME $nic->IPADDR", "$page?nic-form-settings-build=$val");
}
function nic_form_default_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{default}", "$page?nic-form-default-popup=yes");
}
function nic_form_default_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $l=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireWallDefaultInterfacesParams"));
    $xtIpv4optionsInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("xtIpv4optionsInstalled"));

    if(!isset($l["firewall_policy"])){
        $l["firewall_policy"]="accept";
        $l["firewall_behavior"]=0;
        $l["firewall_artica"]=1;
        $l["AntiDDOS"]=0;
        $l["denydhcp"]=0;
        $l["firewall_ping"]="accept";
        $l["isFW"]=1;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FireWallDefaultInterfacesParams",base64_encode(serialize($l)));
    }

    $BEHA["reject"]="{finally_deny_all}";
    $BEHA["accept"]="{finally_allow_all}";

    $BEHA2[0]="{not_defined}";
    $BEHA2[1]="{act_as_lan}";
    $BEHA2[2]="{act_as_wan}";

    $BEHA3["accept"]="{accept}";
    $BEHA3["trusted"]="{accept_trusted}";
    $BEHA3["deny"]="{deny}";

    $jsrestart=$tpl->framework_buildjs(
        "/firewall/reconfigure","firehol.reconfigure.progress",
        "firehol.reconfigure.log",
        "progress-firehol-restart",
        "LoadAjax('table-loader','$page?tabs=yes');");


    $form[]=$tpl->field_hidden("nic-default", "yes");
    $form[]=$tpl->field_hidden("isFW", 1);
    $form[]=$tpl->field_array_hash($BEHA, "firewall_policy", "{firewall_policy}", $l["firewall_policy"]);
    $form[]=$tpl->field_array_hash($BEHA2, "firewall_behavior", "{firewall_behavior}", $l["firewall_behavior"]);
    $form[]=$tpl->field_array_hash($BEHA3, "firewall_ping", "{firewall_ping}", $l["firewall_ping"]);
    $form[]=$tpl->field_checkbox("denydhcp", "{deny} DHCP", $l["denydhcp"]);
    $form[]=$tpl->field_checkbox("AntiDDOS", "Anti-DDOS", $l["AntiDDOS"]);
    $form[]=$tpl->field_checkbox("firewall_artica", "{accept_artica_w}", $l["firewall_artica"]);
    if($xtIpv4optionsInstalled==0){
        $form[]=$tpl->field_checkbox("xtIpv4optionsEnabled", "{suspicious_header_filter} (<small><strong>{missing_module}</strong></small>)", 0,false,"{suspicious_header_filter_explain}",true);
    }else{
        $form[]=$tpl->field_checkbox("xtIpv4optionsEnabled", "{suspicious_header_filter} ", $l["xtIpv4optionsEnabled"],false,"{suspicious_header_filter_explain}");
    }

    $html[]=$tpl->form_outside("{default}", $form,null,"{apply}","dialogInstance1.close();LoadAjax('Firewall-interfaces-table','$page?interfaces=yes');$jsrestart","AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function nic_form_default_save():bool{
    $l=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireWallDefaultInterfacesParams"));
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    foreach ($_POST as $key => $value) {
        $l[$key]=$value;
    }
    $newl=serialize($l);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FireWallDefaultInterfacesParams", base64_encode($newl));
    return admin_tracks_post("Save Firewall default rule");
}
function nic_form_settings_build(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$val=$_GET["nic-form-settings-build"];
	$BEHA["reject"]="{finally_deny_all}";
	$BEHA["accept"]="{finally_allow_all}";
		
	$BEHA2[0]="{not_defined}";
	$BEHA2[1]="{act_as_lan}";
	$BEHA2[2]="{act_as_wan}";

    $BEHA3["accept"]="{accept}";
    $BEHA3["trusted"]="{accept_trusted}";
    $BEHA3["deny"]="{deny}";
		
	$masquerade[0]="{none}";
	$masquerade[1]="{masquerading}";
	$masquerade[2]="{masquerading_invert}";
	$nic=new system_nic($val);
    $xtIpv4optionsInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("xtIpv4optionsInstalled"));


    $jsrestart=$tpl->framework_buildjs(
        "/firewall/reconfigure","firehol.reconfigure.progress",
        "firehol.reconfigure.log",
        "progress-firehol-restart",
        "LoadAjax('table-loader','$page?tabs=yes');");
	
	
	$form[]=$tpl->field_hidden("nic-settings", $val);
	$form[]=$tpl->field_checkbox("isFW", "{activate_firewall_nic}", $nic->isFW,true,"{activate_firewall_nic_explain}");
	$form[]=$tpl->field_array_hash($BEHA, "firewall_policy", "{firewall_policy}", $nic->firewall_policy);
	$form[]=$tpl->field_array_hash($BEHA2, "firewall_behavior", "{firewall_behavior}", $nic->firewall_behavior);
    $form[]=$tpl->field_array_hash($BEHA3, "firewall_ping", "{firewall_ping}", $nic->firewall_ping);
    $form[]=$tpl->field_checkbox("denydhcp", "{deny} DHCP", $nic->denydhcp);
    $form[]=$tpl->field_checkbox("AntiDDOS", "Anti-DDOS", $nic->AntiDDOS);
    $form[]=$tpl->field_checkbox("firewall_masquerade", "{masquerading}", $nic->firewall_masquerade);
	$form[]=$tpl->field_checkbox("firewall_artica", "{accept_artica_w}", $nic->firewall_artica);

    if($xtIpv4optionsInstalled==0){
        $form[]=$tpl->field_checkbox("xtIpv4options", "{suspicious_header_filter} (<small><strong>{missing_module}</strong></small>)", 0,false,"{suspicious_header_filter_explain}",true);
    }else{
        $form[]=$tpl->field_checkbox("xtIpv4options", "{suspicious_header_filter} ", $nic->xtIpv4options,false,"{suspicious_header_filter_explain}");
    }

	$html[]=$tpl->form_outside("$val $nic->NICNAME $nic->IPADDR", @implode("\n", $form),null,"{apply}","dialogInstance1.close();LoadAjax('Firewall-interfaces-table','$page?interfaces=yes');$jsrestart","AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
}

function flat(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $DNSAmplificationProtection=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSAmplificationProtection"));
    $FireHoleStoreEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleStoreEvents"));
    $FireHoleStoreEventsMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleStoreEvents"));
    $FireHoleLogAllEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleLogAllEvents"));
    if($FireHoleStoreEventsMaxSize==0){$FireHoleStoreEventsMaxSize=100;}

    $tpl->table_form_field_js("Loadjs('$page?fw-parms-js=yes')","AsSystemAdministrator");
    $tpl->table_form_field_bool("{DNSAmplificationProtection}",$DNSAmplificationProtection,ico_shield);

    $FirewallSyslogDoNotStorelogsLocally=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FirewallSyslogDoNotStorelogsLocally"));


    $tpl->table_form_field_bool("{log_all_events}",$FireHoleLogAllEvents,ico_logsink);
    $tpl->table_form_field_bool("{not_store_log_locally}",$FirewallSyslogDoNotStorelogsLocally,ico_logsink);
        $tpl->table_form_field_bool("{backup_firewall_events}",$FireHoleStoreEvents,ico_logsink);
    $tpl->table_form_field_text("{export_log_if_size_exceed}","$FireHoleStoreEventsMaxSize MB",ico_logsink);
    echo $tpl->table_form_compile();

}

function fw_params_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{parameters}","$page?fw-parms-popup=yes",650);
}
function fw_params_popup():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $FireHoleStoreEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleStoreEvents"));
	$FireHoleStoreEventsMaxSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleStoreEvents"));
	$FireHoleLogAllEvents=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHoleLogAllEvents"));
	if($FireHoleStoreEventsMaxSize==0){$FireHoleStoreEventsMaxSize=100;}

    $DNSAmplificationProtection=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSAmplificationProtection"));

    $jsrestart=$tpl->framework_buildjs(
        "/firewall/reconfigure","firehol.reconfigure.progress",
        "firehol.reconfigure.log",
        "progress-firehol-restart",
        "LoadAjax('table-loader','$page?tabs=yes');");



    $form[]=$tpl->field_checkbox("DNSAmplificationProtection","{DNSAmplificationProtection}",$DNSAmplificationProtection,false,"");


    $FirewallSyslogDoNotStorelogsLocally=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FirewallSyslogDoNotStorelogsLocally"));


    $form[]=$tpl->field_checkbox("FireHoleLogAllEvents","{log_all_events}",$FireHoleLogAllEvents,false,"{firewall_log_all_events}");
    $form[] = $tpl->field_checkbox("FirewallSyslogDoNotStorelogsLocally",
        "{not_store_log_locally}", $FirewallSyslogDoNotStorelogsLocally);
	$form[]=$tpl->field_checkbox("FireHoleStoreEvents","{backup_firewall_events}",$FireHoleStoreEvents,false,"{FireHoleStoreEvents_explain}");
	$form[]=$tpl->field_numeric("FireHoleStoreEventsMaxSize","{export_log_if_size_exceed} (MB)",$FireHoleStoreEventsMaxSize,"{FireHoleStoreEventsMaxSize_explain}");


	$html[]=$tpl->form_outside(null, $form,null,'{apply}',"dialogInstance2.close();LoadAjax('Firewall-interfaces-settings','$page?flat=yes');$jsrestart","AsSystemAdministrator",true);
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
return true;
			
						
}
function modules_table():bool{
    $t                          = time();
    $TRCLASS                    = null;
    $tpl                        = new template_admin();
    $IPTABLES_MODULES_INFO_TIME = intval($GLOBALS["CLASS_SOCKETS"]->getFrameWork("IPTABLES_MODULES_INFO_TIME"));
    $tt                         = $tpl->time_diff_min($IPTABLES_MODULES_INFO_TIME);
    $ONLYLOADED                 = false;
    if(isset($_GET["OnlyLoaded"])){$ONLYLOADED=true;}

    if($tt>30) {
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("firehol.php?modules-infos=yes");
    }
    $IPTABLES_MODULES_INFO=unserialize( $GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_MODULES_INFO"));



    $html[] = "<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[] = "<thead>";
    $html[]="<tr>";

    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{module}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap >{description}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{aliases}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{depends}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    foreach ($IPTABLES_MODULES_INFO as $modulename=>$MAIN){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        if(!isset($MAIN["DESC"])){$MAIN["DESC"]="&nbsp;";}
        $DESC       = $MAIN["DESC"];
        $status     = "<span class='label'>{unknown}</span>";
        $color      = "#CCCCCC";
        $ALIASES    = $tpl->icon_nothing();
        $depends    = $tpl->icon_nothing();



        if(isset($MAIN["depends"])){
            $depends=$MAIN["depends"];
            $depends=str_replace(",",", ",$depends);
        }

        if(isset($MAIN["ALIASES"])) {
            $tDESC=array();
            foreach ($MAIN["ALIASES"] as $line){
                $line=trim($line);
                if($line==null){continue;}
                if(preg_match("#^(pci|input):#",$line)){continue;}
                $tDESC[]=$line;
            }
            $ALIASES = @implode(", ",$tDESC);
        }
        if(!isset($MAIN["LOADED"])){
            if($ONLYLOADED){continue;}
            $MAIN["LOADED"]=0;
        }



        if(isset($MAIN["INSTALLED"])) {
            if($MAIN["INSTALLED"]) {
                $status     = "<span class='label label-warning'>&nbsp;&nbsp;{unloaded}&nbsp;&nbsp;</span>";
                $color      = "#CCCCCC";
            }else{
                if($ONLYLOADED){continue;}
                $status     ="<span class='label'>{not_installed}</span>";
                $color      ="#CCCCCC";
            }
        }

        if($MAIN["LOADED"]==1){
            $status     = "<span class='label label-primary'>&nbsp;&nbsp;&nbsp;&nbsp;{loaded}&nbsp;&nbsp;&nbsp;&nbsp;</span>";
            $color      = "#000000";
        }else{

            if($ONLYLOADED){continue;}
        }


        if(!isset($MAIN["INSTALLED"])){
            $status="<span class='label'>{not_installed}</span>";
            $color="#CCCCCC";
            if($ONLYLOADED){continue;}
        }
        if($DESC==null){$DESC="&nbsp;";}
        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td nowrap width=1%>$status</td>";
        $html[]="<td nowrap width=1% style='color:$color'><i class=\"fas fa-cube\" style='color:$color'></i>&nbsp;$modulename</td>";
        $html[]="<td style='color:$color'>$DESC</td>";
        $html[]="<td style='color:$color'>$ALIASES</td>";
        $html[]="<td style='color:$color'>$depends</td>";
        $html[]="</tr>";

    }

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
	$(document).ready(function() { $('#table-$t').footable( { \"paging\": { \"size\": 150}, \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true } } ); });
</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function interfaces_start():bool{

    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:250px;padding-left: 10px;padding-right: 10px'>";
    $html[]="<div id='Firewall-status'>". $tpl->spinner()."</div>";
    $html[]="</td>";
    $html[]="<td style='vertical-align: top;width:99%'>";
    $html[]="<div id='Firewall-interfaces-table'>". $tpl->spinner()."</div>";
    $html[]="<div id='Firewall-interfaces-settings'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<script>";

    $IPTABLES_VERSION   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPTABLES_VERSION");
    $uninstall=$tpl->framework_buildjs("/firewall/uninstall",
    "firehol.reconfigure.progress",
    "firehol.reconfigure.log","progress-firehol-restart","document.location.href='/index'");

    $topbuttons[] = array($uninstall, ico_trash, "{uninstall}");

    $jsrestart=$tpl->framework_buildjs(
        "/firewall/reconfigure","firehol.reconfigure.progress",
        "firehol.reconfigure.log",
        "progress-firehol-restart",
        "LoadAjax('Firewall-interfaces-table','$page?interfaces=yes')");


    $topbuttons[] = array($jsrestart, ico_refresh, "{apply_firewall_rules}");


    $TINY_ARRAY["TITLE"]="{firewall_parameters} v$IPTABLES_VERSION";
    $TINY_ARRAY["ICO"]="fab fa-free-code-camp";
    $TINY_ARRAY["EXPL"]="{firewallexplain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);


    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]=$jstiny;
    $html[]="LoadAjax('Firewall-interfaces-table','$page?interfaces=yes');";
    $html[]="LoadAjax('Firewall-interfaces-settings','$page?flat=yes');";
    $html[]="</script>";

    echo @implode("\n",$html);
    return true;

}
function interfaces(){
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    $t                  = time();
    $interface          = $tpl->_ENGINE_parse_body("{interface}");
    $enabled_text       = $tpl->_ENGINE_parse_body("{enabled}");
    $firewall_policy    = $tpl->_ENGINE_parse_body("{firewall_policy}");
    $firewall_behavior  = $tpl->_ENGINE_parse_body("{firewall_behavior}");
    $masquerading       = $tpl->_ENGINE_parse_body("{masquerading}");
    $accept_artica_w    = $tpl->_ENGINE_parse_body("{webconsole}");

    $thps="data-sortable=true class='text-capitalize center' data-type='text'";
    $html[] = "<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[] = "<thead>";
    $html[]="<tr>";
    $html[]="<th $thps >$interface</th>";
    $html[]="<th $thps nowrap >$enabled_text</th>";
    $html[]="<th $thps nowrap>{policy}</th>";
    $html[]="<th $thps nowrap>{behavior}</th>";
    $html[]="<th $thps nowrap>{headers}</th>";
    $html[]="<th $thps nowrap>$masquerading</th>";
    $html[]="<th $thps nowrap>DDOS</th>";
    $html[]="<th $thps nowrap>DHCP</th>";
    $html[]="<th $thps nowrap>$accept_artica_w</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
    $GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
    $TRCLASS=null;

    $datas=TCP_LIST_NICS_W();

    $BEHA["reject"]="<span class='label label-danger'>{deny_all}</span>";
    $BEHA["accept"]="<span class='label label-primary'>{allow_all}</span>";

    $BEHA2[0]="<span class='label'>{none}</span>";
    $BEHA2[1]="<span class='label label-primary'>LAN</span>";
    $BEHA2[2]="<span class='label label-warning'>WAN</span>";

    $masquerade[0]="{none}";
    $masquerade[1]="{masquerading}";
    $masquerade[2]="{masquerading_invert}";
    $tpl=new template_admin();
    $uncheck="<i class=\"far fa-square\"></i>";
    $ban="<span class='text-danger'><i class=\"fas fa-ban\"></i></span>";
    foreach ($datas as $num=>$val){
        $masquerade_F       = $uncheck;
        $headers_F=$uncheck;
        $val=trim($val);
        $fa_ethernet_class=null;
        if($val==null){continue;}
        $nic                = new system_nic($val);
        if($nic->enabled==0){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $firewall_policy_F=$BEHA[$nic->firewall_policy];
        $firewall_behavior_F=$BEHA2[$nic->firewall_behavior];
        $firewall_articaF=$uncheck;
        $isFW_icon=$uncheck;
        $DDOS_icon=$uncheck;
        if($nic->firewall_masquerade==1){
            $masquerade_F="<i class='fas fa-check'></i>";
        }
        if($nic->firewall_artica==1){
            $firewall_articaF="<i class='fas fa-check'></i>";
        }
        if($nic->xtIpv4options ==1){
            $headers_F="<i class='fas fa-check'></i>";
        }

        if($nic->isFW==1){
            $isFW_icon="<i class='fas fa-check'></i>";
            $fa_ethernet_class="style='color:#1ab394'";
            if($nic->AntiDDOS==1){
                $DDOS_icon=$ban;
            }

        }else{
            $nic->denydhcp=0;
            $firewall_policy_F=$uncheck;
            $firewall_behavior_F=$uncheck;
            $firewall_articaF=$uncheck;
        }
//
        $link=$tpl->td_href("$val $nic->IPADDR","{click_to_edit}","Loadjs('$page?nic-fw-edit=$val')");

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td nowrap><i class='fas fa-ethernet' $fa_ethernet_class></i>&nbsp;$link</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$isFW_icon</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$firewall_policy_F</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$firewall_behavior_F</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$headers_F</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$masquerade_F</td>";
        $html[]="<td style='width:1%' class='center' nowrap>$DDOS_icon</td>";
        if($nic->denydhcp==1){
            $html[]="<td style='width:1%' class='center' nowrap>$ban</td>";
        }else{
            $html[]="<td style='width:1%' class='center' nowrap>$uncheck</td>";
        }
        $html[]="<td style='width:1%' class='center' nowrap>$firewall_articaF</td>";
        $html[]="</tr>";
    }
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]=getRowDefault($TRCLASS);

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='8'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $js=$tpl->RefreshInterval_js("Firewall-status","$page","firewall-status=yes");

    $html[]="
<script>
    NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true } } ); });
    $js
</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function getRowDefault($TRCLASS):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $uncheck="<i class=\"far fa-square\"></i>";
    $headers_F=$uncheck;
    $ban="<span class='text-danger'><i class=\"fas fa-ban\"></i></span>";
    $BEHA["reject"]="<span class='label label-danger'>{deny_all}</span>";
    $BEHA["accept"]="<span class='label label-primary'>{allow_all}</span>";

    $BEHA2[0]="<span class='label'>{none}</span>";
    $BEHA2[1]="<span class='label label-primary'>LAN</span>";
    $BEHA2[2]="<span class='label label-warning'>WAN</span>";

    $FireWallDefaultInterfacesParams=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireWallDefaultInterfacesParams"));

    if(!isset($FireWallDefaultInterfacesParams["firewall_policy"])){
        $FireWallDefaultInterfacesParams["firewall_policy"]="accept";
        $FireWallDefaultInterfacesParams["firewall_behavior"]=0;
        $FireWallDefaultInterfacesParams["firewall_artica"]=1;
        $FireWallDefaultInterfacesParams["AntiDDOS"]=0;
        $FireWallDefaultInterfacesParams["denydhcp"]=0;
        $FireWallDefaultInterfacesParams["isFW"]=1;
        $FireWallDefaultInterfacesParams["xtIpv4optionsEnabled"]=0;
        $FireWallDefaultInterfacesParams["firewall_ping"]="accept";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FireWallDefaultInterfacesParams",base64_encode(serialize($FireWallDefaultInterfacesParams)));
    }

    $firewall_policy_F=$BEHA[$FireWallDefaultInterfacesParams["firewall_policy"]];
    $firewall_behavior_F=$BEHA2[$FireWallDefaultInterfacesParams["firewall_behavior"]];
    $firewall_articaF=$uncheck;
    $DDOS_icon=$uncheck;

    if($FireWallDefaultInterfacesParams["firewall_artica"]==1){
        $firewall_articaF="<i class='fas fa-check'></i>";
    }
    if($FireWallDefaultInterfacesParams["xtIpv4optionsEnabled"]==1){
        $headers_F="<i class='fas fa-check'></i>";
    }

    $isFW_icon="<i class='fas fa-check'></i>";
    $fa_ethernet_class="style='color:#1ab394'";
    if($FireWallDefaultInterfacesParams["AntiDDOS"]==1){
        $DDOS_icon=$ban;
    }

    $link=$tpl->td_href("{default}","{click_to_edit}","Loadjs('$page?nic-fw-default=yes')");

    $html[]="<tr class='$TRCLASS'>";
    $html[]="<td nowrap><i class='fas fa-ethernet' $fa_ethernet_class></i>&nbsp;$link</td>";
    $html[]="<td style='width:1%' class='center' nowrap>$isFW_icon</td>";
    $html[]="<td style='width:1%' class='center' nowrap>$firewall_policy_F</td>";
    $html[]="<td style='width:1%' class='center' nowrap>$firewall_behavior_F</td>";
    $html[]="<td style='width:1%' class='center' nowrap>$headers_F</td>";
    $html[]="<td style='width:1%' class='center' nowrap><span class='label'>{none}</span></td>";
    $html[]="<td style='width:1%' class='center' nowrap>$DDOS_icon</td>";
    if($FireWallDefaultInterfacesParams["denydhcp"]){
        $html[]="<td style='width:1%' class='center' nowrap>$ban</td>";
    }else{
        $html[]="<td style='width:1%' class='center' nowrap>$uncheck</td>";
    }
    $html[]="<td style='width:1%' class='center' nowrap>$firewall_articaF</td>";
    $html[]="</tr>";
    return @implode("\n", $html);

}