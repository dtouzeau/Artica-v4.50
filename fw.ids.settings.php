<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["main-js"])){main_js();exit;}
if(isset($_GET["statistics-js"])){statistics_js();exit;}

if(isset($_GET["update-js"])){update_js();exit;}
if(isset($_GET["updates-parameters"])){updates_parameters();exit;}

if(isset($_GET["alienvault-js"])){alienvault_js();exit;}
if(isset($_GET["alienvault-parameters"])){alienvault_parameters();exit;}
if(isset($_POST["OtxEnabled"])){alienvault_save();exit;}

if(isset($_GET["DataShieldIPv4Blocklist-js"])){DataShieldIPv4Blocklist_js();exit;}
if(isset($_GET["DataShieldIPv4Blocklist-parameters"])){DataShieldIPv4Blocklist_parameters();exit;}
if(isset($_POST["DataShieldIPv4Blocklist"])){DataShieldIPv4Blocklist_save();exit;}



if(isset($_GET["statistics-parameters"])){statistics_parameters();exit;}
if(isset($_POST["SnortRulesCode"])){Save_gen();exit;}
if(isset($_POST["nic-settings"])){Save_nic();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["firewall-parameters"])){firewall_parameters();exit;}
if(isset($_POST["SuricataFirewallPurges"])){firewall_parameters_save();exit;}
if(isset($_POST["SuricataPurges"])){satistics_parameters_save();exit;}
if(isset($_POST["SuricataUpdateInterval"])){satistics_parameters_save();exit;}
if(isset($_GET["interface-js"])){interface_js();exit;}
if(isset($_GET["interface-popup"])){interface_popup();exit;}
if(isset($_GET["interface-layer"])){interface_layer();exit;}

page();

function main_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $addon="";
    if(isset($_GET["ndpid"])){
        $addon="&ndpid=yes";
    }
    return $tpl->js_dialog1("{listen_interfaces}","$page?main=yes$addon",850);
}
function interface_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $nic=$_GET["interface-js"];
    return $tpl->js_dialog1("{listen_interfaces} $nic", "$page?interface-popup=$nic");
}
function interface_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $iface=$_GET["interface-popup"];
    $html[]="<div id='div-iface-$iface'></div>";
    $html[]="<script>LoadAjax('div-iface-$iface','$page?interface-layer=$iface');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function enable_js():bool{
    $field=$_GET["field"];
    $iface=$_GET["enable-js"];
    $tpl=new template_admin();
    $add=false;
    $q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM suricata_interfaces WHERE interface='$iface'");
    if(!isset($ligne["interface"])){
        $add=true;
    }
    if(!isset($ligne[$field])){
        $ligne[$field]=0;
    }
    if($ligne[$field]==0) {
        $upd = "UPDATE suricata_interfaces SET $field=1 WHERE interface='$iface'";
        $sqladd="INSERT INTO suricata_interfaces (interface, $field,PortsUDP,PortsTCP) VALUES ('$iface', 1,'*','*');";
    }else{
        $upd = "UPDATE suricata_interfaces SET $field=0 WHERE interface='$iface'";
        $sqladd="INSERT INTO suricata_interfaces (interface, $field,PortsUDP,PortsTCP) VALUES ('$iface', 1,'*','*');";
    }
    $sql=$upd;
    if($add){
        $sql=$sqladd;
    }
    //writelogs( "Interface $iface $field $ligne[$field] $sql",__FUNCTION__,__FILE__,__LINE__);
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->js_error($q->mysql_error);
        return false;
    }
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    echo "LoadAjax('div-iface-$iface','$page?interface-layer=$iface');\n";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/suricata/reconfigure");
    return admin_tracks_post("Save IDS settings for $iface");
}
function interface_layer():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $iface=$_GET["interface-layer"];
    $q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM suricata_interfaces WHERE interface='$iface'");
    if(!isset($ligne["enable"])){
        $ligne["enable"]=0;
    }
    if(!isset($ligne["PortsTCP"])){
        $ligne["PortsTCP"]="{all}";
    }
    if(!isset($ligne["PortsUDP"])){
        $ligne["PortsUDP"]="{all}";
    }
    $tpl->table_form_field_js("Loadjs('$page?enable-js=$iface&field=enable');");
    $tpl->table_form_field_bool($iface,$ligne["enable"],ico_nic);
    $tpl->table_form_field_js("Loadjs('$page?enable-js=$iface&field=WantIPv6');");
    $tpl->table_form_field_bool("{enable_ipv6}",$ligne["WantIPv6"],ico_nic);
    $tpl->table_form_field_js("Loadjs('$page?enable-js=$iface&field=WhiteInternalNets');");
    $tpl->table_form_field_bool("{trusted_networks}",$ligne["WhiteInternalNets"],ico_networks);
    $tpl->table_form_field_js("Loadjs('$page?enable-js=$iface&field=NoBrodcast');");
    $tpl->table_form_field_bool("{exclude_brodcasts}",$ligne["NoBrodcast"],ico_networks);
    $tpl->table_form_field_js("Loadjs('$page?enable-js=$iface&field=NoMulticast');");
    $tpl->table_form_field_bool("{exclude_multicasts}",$ligne["NoMulticast"],ico_networks);
    $tpl->table_form_field_js("Loadjs('$page?enable-js=$iface&field=NoARP');");
    $tpl->table_form_field_bool("{exclude_arps}",$ligne["NoARP"],ico_networks);
    $tpl->table_form_field_js("Loadjs('$page?enable-js=$iface&field=OnlyNewTCP');");
    $tpl->table_form_field_bool("{only_new_tcp}",$ligne["OnlyNewTCP"],ico_networks);
    $tpl->table_form_field_js("Loadjs('$page?enable-PortsTCP=$iface');");
    $tpl->table_form_field_text("{listen_ports} TCP",$ligne["PortsTCP"],ico_networks);
    $tpl->table_form_field_js("Loadjs('$page?enable-PortsUDP=$iface');");
    $tpl->table_form_field_text("{listen_ports} UDP",$ligne["PortsUDP"],ico_networks);
    echo $tpl->table_form_compile();
    return true;
}
function statistics_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{statistics_parameters}","$page?statistics-parameters=yes",650);
}
function update_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{update_settings}","$page?updates-parameters=yes",650);
}
function alienvault_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("AlienVault","$page?alienvault-parameters=yes",650);
}
function DataShieldIPv4Blocklist_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("Data-Shield IPv4 Blocklist","$page?DataShieldIPv4Blocklist-parameters=yes",850);
}
function DataShieldIPv4Blocklist_parameters():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/status"));
    if(!$json->Status){
        $html[]=$tpl->div_error("{error} API||$json->Error");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return false;
    }
    $GlobalConfig=$json->Info->DataShieldIPv4Blocklist;

    $explain[]="<table style='width:100%;'>";
    $explain[]="<tr><td style='width:1%;vertical-align: top;padding:3px' nowrap><img src='img/lminne.png'></td><td style='padding-left:10px'>{laurentMinneIDS}</td></tr></table>";



    $html[]=$tpl->BigCircleCheckbox("DataShieldIPv4BlocklistEnabled","Data-Shield IPv4 Blocklist",@implode("",$explain),$GlobalConfig);
    $html[]="<div style='margin-top:20px'>";
    $html[]=$tpl->div_explain("notitle:{laurentMinneMoney}");
    $html[]="</div>";

    echo $tpl->_ENGINE_parse_body($html);
   return true;
}
function DataShieldIPv4Blocklist_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/status"));
    if(!$json->Status){
        echo $tpl->post_error("{error} API||$json->Error");
        return false;
    }

    $DataShieldIPv4BlocklistEnabled=intval($_POST["DataShieldIPv4BlocklistEnabled"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/DataShieldIPv4Blocklist/save/$DataShieldIPv4BlocklistEnabled"));
    if(!$json->Status){
        echo $tpl->post_error("{error} API||$json->Error");
        return false;
    }
    return admin_tracks("Set Data-Shield IPv4 Blocklist enable to $DataShieldIPv4BlocklistEnabled");
}


function nic_settings(){
	$nic=new system_nic($_POST["nic-settings"]);
	$nic->firewall_policy=$_POST["firewall_policy"];
	$nic->firewall_behavior=$_POST["firewall_behavior"];
	$nic->firewall_masquerade=$_POST["firewall_masquerade"];
	$nic->firewall_artica=$_POST["firewall_artica"];
	//$nic->DenyCountries=$_POST["DenyCountries"];
	$nic->SaveNic();
	
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{IDS}: {parameters}",
        "fas fa-cogs","{about_ids}",
        "$page?tabs=yes","ids-settings","progress-firehol-restart",false,"table-loader");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);


}
function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
	$array["{listen_interfaces}"]="$page?main=yes";
    if($EnableSuricata==1) {
        $array["{firewall_detection_engines}"] = "$page?firewall-parameters=yes";
        $array["{updates}"] = "$page?updates-parameters=yes";
    }
	$array["{statistics_engine}"]="$page?statistics-parameters=yes";
	echo $tpl->tabs_default($array);
    return true;

}
function updates_parameters():bool{
	$tpl=new template_admin();



	$SuricataUpdateInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataUpdateInterval"));
	if($SuricataUpdateInterval==0){$SuricataUpdateInterval=1440;}
	
	$maxtime_array[1]="{never}";
	$maxtime_array[420]="4 {hours}";
	$maxtime_array[480]="8 {hours}";
	$maxtime_array[720]="12 {hours}";
	$maxtime_array[1440]="1 {day}";
	$maxtime_array[2880]="2 {days}";
	$maxtime_array[10080]="1 {week}";

	$form[]=$tpl->field_array_hash($maxtime_array, "SuricataUpdateInterval", "{update_each}", $SuricataUpdateInterval);


	echo $tpl->form_outside("", @implode("\n", $form),null,"{apply}","blur()","AsFirewallManager",false);
    return true;

}
function alienvault_parameters():bool{
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/status"));
    if(!$json->Status){
        $html[]=$tpl->div_error("{error} API||$json->Error");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return false;
    }
    $GlobalConfig=$json->Info->Otx;
    $form[]=$tpl->field_checkbox("OtxEnabled", "{enabled}", $GlobalConfig->Enabled,true);
    $form[]=$tpl->field_text("ApiKey", "{API_KEY}", $GlobalConfig->ApiKey);
    $form[]=$tpl->field_numeric("MaxPages", "{max_feeds}", $GlobalConfig->MaxPages,true);
    echo $tpl->form_outside("", @implode("\n", $form),"{otx_explain}","{apply}","blur()","AsFirewallManager",false);
    return true;

}
function alienvault_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if(strlen($_POST["ApiKey"])<10){
       echo $tpl->post_error("{error} API||{API_KEY} < 10 characters");
       return false;
    }
    $ApiKey=urlencode($_POST["ApiKey"]);
    $MaxPages=intval($_POST["MaxPages"]);
    $OtxEnabled=intval($_POST["OtxEnabled"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/otx/save/$ApiKey/$MaxPages/$OtxEnabled"));
    if(!$json->Status){
        echo $tpl->post_error("{error}||$json->Error");
        return false;
    }
    return admin_tracks_post("Save IDS AlienVault data feeds settings");
}



function statistics_parameters():bool{
	$tpl=new template_admin();
	$SuricataPurge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataPurge"));
	if($SuricataPurge==0){$SuricataPurge=15;}
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$SuricataPurge=2;}

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/status"));
    if(!$json->Status){
        $html[]=$tpl->div_error("{error} API||$json->Error");
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        return false;
    }
    $GlobalConfig=$json->Info;

	$form[]=$tpl->field_numeric("SuricataPurges", "{retention_days}", $SuricataPurge,"{SuricataPurges}");

    $form[]=$tpl->field_checkbox("UseQueueFailed","{use_queue_failed}",$GlobalConfig->UseQueueFailed);
    $form[]=$tpl->field_browse_directory("QueueFailed","{queue_path}",$GlobalConfig->QueueFailed);

	echo "<p>&nbsp;</p>".$tpl->form_outside("", @implode("\n", $form),null,"{apply}",
            "LoadAjax('suricata-config','fw.ids.dashboard.php?flat-config=yes');",
            "AsFirewallManager",true);
	return true;
}
function firewall_parameters(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$EnableFail2Ban=0;
	$FAIL2BAN_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_INSTALLED"));
	if($FAIL2BAN_INSTALLED==1){
		$EnableFail2Ban=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFail2Ban"));
	}
    $SuricataPfRing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataPfRing"));
	$SuricateIPReputations=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricateIPReputations"));
	$SuricataFirewallPurges=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataFirewallPurges"));
	$SuricataFail2ban=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataFail2ban"));
	if($SuricataFirewallPurges==0){$SuricataFirewallPurges=24;}
	
	
	//$form[]=$tpl->field_checkbox("SuricateIPReputations","{enable_ip_reputation}",$SuricateIPReputations,false,"{enable_ip_reputation_ids_explain}");

    $form[]=$tpl->field_checkbox("SuricataPfRing","{SuricataPfRing}",$SuricataPfRing,false,"{SuricataPfRing}");

	
	if($EnableFail2Ban==1){
		$form[]=$tpl->field_checkbox("SuricataFail2ban","{use_fail2ban_service}",$SuricataFail2ban,false,"{use_fail2ban_service_explain}");
		
	}

	
	$form[]=$tpl->field_numeric("SuricataFirewallPurges", "{firewall_retention} ({days})", $SuricataFirewallPurges,"{firewall_retention_suricata_explain}");



	echo "<p>&nbsp;</p>".$tpl->form_outside("{firewall_detection_engines}", @implode("\n", $form),null,"{apply}","Loadjs('fw.ids.dashboard.php?reconfigure-js=yes');","AsFirewallManager");
}
function firewall_parameters_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$sock=new sockets();
	foreach ($_POST as $key=>$val){
		$sock->SET_INFO($key, $val);
		
	}
	$EnableFail2Ban=0;
	$FAIL2BAN_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_INSTALLED"));
	if($FAIL2BAN_INSTALLED==1){
		$EnableFail2Ban=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFail2Ban"));
	}
	
	if($EnableFail2Ban==1){$sock->getFrameWork("fail2ban.php?reload=yes");}
	$sock->getFrameWork("suricata.php?restart-tail=yes");
	
}
function satistics_parameters_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();

    $QueueFailed=urlencode($_POST["QueueFailed"]);
    $UseQueueFailed=intval($_POST["UseQueueFailed"]);

    $GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/config/queue/$QueueFailed/$UseQueueFailed");
    unset($_POST["QueueFailed"]);
    unset($_POST["UseQueueFailed"]);

	foreach ($_POST as $key=>$val){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO($key, $val);
	
	}
}
function main(){
	$tpl=new template_admin();
    $AsNDPI=false;

    if(isset($_GET["ndpid"])){
        $AsNDPI=true;
    }
	$page=CurrentPageName();
	$interface=$tpl->_ENGINE_parse_body("{interface}");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$threads=$tpl->javascript_parse_text("{threads}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	if(!isset($_GET["eth"])){$_GET["eth"]=null;}
    $pattern_exclude    ="^(dummy|teql|ip6tnl|tunl|gre|ifb|sit|gretap|erspan)[0-9]+";
	$t=time();

	$html[]="<p>&nbsp;</p>";
	$html[]=$tpl->_ENGINE_parse_body("
			
			<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
			$html[]="<thead>";
			$html[]="<tr>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$interface</th>";
			$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$enabled</th>";
            if(!$AsNDPI) {
                $html[] = "<th data-sortable=true class='text-capitalize' data-type='text'>$threads</th>";
            }
			$html[]="<th data-sortable=false></th>";
			$html[]="</tr>";
			$html[]="</thead>";
			$html[]="<tbody>";
	
			$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
			$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
			$TRCLASS=null;	
	
			$datas=TCP_LIST_NICS_W();

            $FormJs="if(document.getElementById('suricata-config')){LoadAjax('suricata-config','fw.ids.dashboard.php?flat-config=yes');} if(document.getElementById('ndpid-flat-config')){LoadAjaxSilent('ndpid-flat-config','fw.network.ndpid.php?ndpid-flat-config=yes');}";
	
			
			$q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
			foreach ($datas as $val){
                if(preg_match("#$pattern_exclude#",$val)){continue;}
				$tpl=new template_admin();
				$tpl->FORM_IN_ARRAY=true;
				if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
				$val=trim($val);
                $mirror=null;
				if($val==null){continue;}

				$nic=new system_nic($val);
                if($nic->enabled==0){continue;}
                if($nic->UseSPAN==1){
                    $mirror="<span class='label label-inverse'>{mirror}</span>";
                }

				$html[]="<tr class='$TRCLASS'>";
				$html[]="<td><span style='font-size:14px'>$val $nic->NICNAME $nic->IPADDR&nbsp;$mirror</span></td>";
				$html[]=$tpl->field_hidden("nic-settings", $val);
				
				$ligne=$q->mysqli_fetch_array("SELECT interface,threads,enable FROM suricata_interfaces WHERE interface='$val'");
				if(intval($ligne["threads"])==0){$ligne["threads"]=5;}
				$html[]=$tpl->field_checkbox("enable", "1$val", $ligne["enable"]);
                if(!$AsNDPI) {
                    $html[] = $tpl->field_numeric("threads", "2$val", $ligne["threads"]);
                }else{
                    $html[] = $tpl->field_hidden("threads",$ligne["threads"]);
                }
				$html[]=$tpl->form_outside(null, null,null,"{apply}", $FormJs,"AsFirewallManager");
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
			$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true } } ); });
			</script>";
			
			echo $tpl->_ENGINE_parse_body($html);
			
						
}
function Save_nic(){
	$q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
	$q->QUERY_SQL("DELETE FROM suricata_interfaces WHERE interface='{$_POST["nic-settings"]}'");
	if(!$q->ok){echo $q->mysql_error_html(true);return;}
	if($_POST["enable"]==0){return;}
	$q->QUERY_SQL("INSERT INTO suricata_interfaces (interface,threads,enable) VALUES ('{$_POST["nic-settings"]}','{$_POST["threads"]}',1)");
	if(!$q->ok){echo $q->mysql_error_html(true);}
}
function Save_gen(){
	$sock=new sockets();
	$sock->SET_INFO("SnortRulesCode", $_POST["SnortRulesCode"]);
	$sock->SET_INFO("SuricataFirewallPurges", $_POST["SuricataFirewallPurges"]);
	$sock->SET_INFO("SuricataPurges", $_POST["SuricataPurges"]);	
	
}