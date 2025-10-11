<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tinyjs"])){Tinyjs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["ip_addr"])){net_save();exit;}
if(isset($_GET["net-js"])){net_js();exit;}
if(isset($_GET["net-popup"])){net_popup();exit;}
if(isset($_GET["netmask-unlink"])){net_del();exit;}
if(isset($_GET["report-js"])){report_js();exit;}
if(isset($_GET["report-popup"])){report_popup();exit;}
if(isset($_GET["ping-report-js"])){ping_report_js();exit;}
if(isset($_GET["ping-report-popup"])){ping_report_popup();exit;}
if(isset($_GET["ping-report-table"])){ping_report_table();exit;}
if(isset($_GET["enable"])){enable();exit;}
if(isset($_GET["enabled-trusted"])){enable_trusted();exit;}
if(isset($_GET["arpscanner-js"])){arpscanner_js();exit;}
if(isset($_GET["arpscanner-popup"])){arpscanner_popup();exit;}
if(isset($_GET["arpscanner-close"])){arpscanner_close();exit;}
if(isset($_GET["arpscanner-enable"])){arpscanner_enable();exit;}
page();

function enable_trusted():bool{
    $cdir=$_GET["enabled-trusted"];
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql="SELECT trusted FROM networks_infos WHERE ipaddr='$cdir'";
    $ligne=$q->mysqli_fetch_array($sql);
    $srctrusted=intval($ligne["trusted"]);
    if($srctrusted==1){
        $trusted=0;
    }else{
        $trusted=1;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("UPDATE networks_infos SET trusted=$trusted WHERE ipaddr='$cdir'" );
    if(!$q->ok){
        return $tpl->js_mysql_alert($q->mysql_error);
    }
    $memcached=new lib_memcached();
    $memcached->saveKey("WebConsoleTrustedNet","");

    admin_tracks("Set My Network $cdir to trusted=$trusted");
    return net_compile();
}
function arpscanner_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog_modal("Passive ARP Scanner","$page?arpscanner-popup=yes");
}
function arpscanner_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]=$tpl->div_explain("ico:".ico_radar.";Passive ARP Scanner||{explain_arpscanner}");
    $EnableARPScanner= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableARPScanner"));
    $ARPScannerSeen= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARPScannerSeen"));
    if($ARPScannerSeen==0){$EnableARPScanner=1;}

    $sock=new sockets();
    $data=$sock->REST_API("/system/network/arpscan/count");
    $json=json_decode($data);
    $Count=$json->count;

    if($EnableARPScanner==1){
        $btn["name"]="{disable}";
        $btn["ico"]=ico_disabled;
        $btn["js"] = "Loadjs('$page?arpscanner-enable=0');";
        $html[]=$tpl->widget_h("green",ico_radar,"{active2} $Count {hosts}","ARP Scanner",$btn,null);
    }else{
        $btn=array("name"=>"{enable}","js"=>"Loadjs('$page?arpscanner-enable=1');",
            "ico"=>ico_check);
        $html[]=$tpl->widget_h("grey",ico_radar,"{active2} $Count {hosts}","ARP Scanner",$btn,null);
    }


    $button=$tpl->button_autnonome("{close}","DialogModal.close();Loadjs('$page?arpscanner-close=yes');",ico_exit);
    $html[]="<div style='margin:10px;text-align:right;'>$button</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function arpscanner_close():bool{
    $page=CurrentPageName();

    $ARPScannerSeen= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARPScannerSeen"));
    if($ARPScannerSeen==0) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ARPScannerSeen", 1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableARPScanner", 1);
    }
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');\n";
    echo "if ( document.getElementById('progress-network-restart') ){\n";
    echo "Loadjs('$page?tinyjs=yes');\n";
    echo "}";
    echo "DialogModal.close();\n";
    return true;
}
function arpscanner_enable():bool{
    $page=CurrentPageName();
    $enable=intval($_GET["arpscanner-enable"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ARPScannerSeen",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableARPScanner",$enable);
    $sock=new sockets();
    $sock->REST_API("/myself/restart");
    header("content-type: application/x-javascript");
    echo "Loadjs('$page?arpscanner-close=yes');\n";
    return admin_tracks("Enable ARP Scanner == $enable");
}

function net_compile():bool{
    return reload_services();
}

function net_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$net_title=$_GET["net-js"];
	$net=urlencode($net_title);
	if($net==null){$title="{new_network}";}else{$title=$net_title;}
	return $tpl->js_dialog($title, "$page?net-popup=$net");
}

function report_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$net=$_GET["report-js"];
	return $tpl->js_dialog("{report} $net", "$page?report-popup=".urlencode($net));
}
function ping_report_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$net=$_GET["ping-report-js"];
	return $tpl->js_dialog("{report} $net", "$page?ping-report-popup=".urlencode($net));
}
function ping_report_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$net=$_GET["ping-report-popup"];
	
	echo $tpl->_ENGINE_parse_body("<div class=\"btn-group\" data-toggle=\"buttons\">
	<label class=\"btn btn btn-primary\" OnClick=\"LoadAjaxSilent('ping-report-popup','$page?ping-report-table=".urlencode($net)."&available=1')\"><i class='fa fa-search'></i> {available} </label>
	<label class=\"btn btn btn-warning\" OnClick=\"LoadAjaxSilent('ping-report-popup','$page?ping-report-table=".urlencode($net)."&available=0')\"><i class='fa fa-search'></i> {unavailable} </label>
	</div>");
	
	
	echo "<div id='ping-report-popup'></div><script>LoadAjaxSilent('ping-report-popup','$page?ping-report-table=".urlencode($net)."&available=0')</script>";
    return true;


}
function net_del(){
	$netDel=$_GET["netmask-unlink"];
	$net=new networkscanner();


    foreach ($net->networklist as $num=>$maks){
		if($maks==$netDel){
			$q=new mysql();
			$q_interface=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
			$q_interface->QUERY_SQL("DELETE FROM networks_infos WHERE ipaddr='$netDel'");
			
			$q=new lib_sqlite("/home/artica/SQLITE/fping.db");
			$q->QUERY_SQL("DELETE FROM fping WHERE network='$netDel'");
			
			$q=new lib_sqlite("/home/artica/SQLITE/nmapping.db");
			$q->QUERY_SQL("DELETE FROM nmapping WHERE network='$netDel'");
			unset($net->networklist[$num]);
			$net->save();
            $memcached=new lib_memcached();
            $memcached->saveKey("WebConsoleTrustedNet","");
            admin_tracks("My Network: Delete local network $netDel");
			echo "$('#{$_GET["md"]}').remove();\n";
			reload_services();			
			return;
		}
	}
	
}

function reload_services():bool{
	$EnableFail2Ban=0;
	$EnableSuricata=0;
	$FAIL2BAN_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FAIL2BAN_INSTALLED"));
    $EnableTailScaleService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTailScaleService"));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
	if($FAIL2BAN_INSTALLED==1){
		$EnableFail2Ban=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFail2Ban"));
	}
	
	$SURICATA_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SURICATA_INSTALLED"));
	if($SURICATA_INSTALLED==1){$EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));}
	$EnableDKFilter=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDKFilter"));
    $NTPDEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDEnabled"));

	if($EnableFail2Ban==1){$GLOBALS["CLASS_SOCKETS"]->getFrameWork("fail2ban.php?restart=yes");}
	if($EnableSuricata==1){$GLOBALS["CLASS_SOCKETS"]->REST_API("/suricata/reload");}
    if($EnableDKFilter==1){$GLOBALS["CLASS_SOCKETS"]->getFrameWork("opendkim.php?restart=yes");}
    if($EnableTailScaleService==1){$GLOBALS["CLASS_SOCKETS"]->getFrameWork("tailscale.php?connect=yes");}
    if($NTPDEnabled==1){$GLOBALS["CLASS_SOCKETS"]->REST_API("/ntpd/reconfigure");}


    $EnableCrowdSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrowdSec"));
    if($EnableCrowdSec==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/whitelist");
    }

    $EnableRsyncDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRsyncDaemon"));
    if($EnableRsyncDaemon==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/rsyncd/restart");
    }

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/firewall/trustednets");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/officenets");
    return true;
}


function net_popup():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$network=$_GET["net-popup"];
	$SHOW_VPN=isVPN();
	$q=new mysql();
	$bt="{add}";
	$safter="BootstrapDialog1.close();";
	$scannable=1;
    $ligne["enabled"]=1;
    $pinginterval=0;
	if($network<>null){
		$net=explode("/",$network);
		$sql="SELECT * FROM networks_infos WHERE ipaddr='$network'";
		$q_interface=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$ligne=$q_interface->mysqli_fetch_array($sql);
		$netinfos=$ligne["netinfos"];
		$scannable=intval($ligne["scannable"]);
		$pingable=intval($ligne["pingable"]);
		$pinginterval=intval($ligne["pinginterval"]);
        $netmask="";
		$title=$network;
		$bt="{apply}";
		if(intval($net[1])>0){
			$ipv=new ipv4($net[0],$net[1]);
			$net[0]=$ipv->address();
			$netmask=$ipv->netmask();
			$safter=null;
		} 	
	}

    $form[]=$tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	if($network==null){
		$form[]=$tpl->field_ipaddr("ip_addr", "{ip_address}", null);
		$form[]=$tpl->field_maskcdir("netmask", "nonull:{netmask}", null);
		
	}else{
		$form[]=$tpl->field_info("ip_addr", "{ip_address}", $net[0]);
		$form[]=$tpl->field_info("netmask", "{netmask}", $netmask);
		$form[]=$tpl->field_info("netmaskcdir", "{cdir}", $network);
	}
	
	
	$form[]=$tpl->field_checkbox("scannable","{can_be_analyzed}",$scannable);
	$FPING_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FPING_INSTALLED"));
	
	if($FPING_INSTALLED==1){
		if($pinginterval<5){$pinginterval=15;}
		$durations[15]="15 {minutes}";
		$durations[30]="30 {minutes}";
		$durations[60]="1 {hour}";
		$durations[120]="2 {hours}";
		$durations[240]="4 {hours}";
		$durations[480]="8 {hours}";
		$durations[720]="12 {hours}";
		$durations[960]="16 {hours}";
		$durations[1440]="1 {day}";
		$durations[2880]="2 {days}";
		$durations[5760]="4 {days}";
		$durations[10080]="1 {week}";
		$durations[20160]="2 {weeks}";
		$durations[43200]="1 {month}";
		



		$form[]=$tpl->field_hidden("noping", intval($ligne["noping"]));
		$form[]=$tpl->field_hidden("yesping", intval($ligne["yesping"]));
		$form[]=$tpl->field_hidden("prcping", floatval($ligne["prcping"]));

        $VPN_DISABLED=true;
        if($SHOW_VPN){$VPN_DISABLED=false;}
        $form[]=$tpl->field_checkbox("trusted","{trusted_network}",$ligne["trusted"],false);
        $form[]=$tpl->field_checkbox("vpn","{publish_in_vpn_network}",$ligne["vpn"],false,null,$VPN_DISABLED);
        $form[]=$tpl->field_checkbox("pingable","{can_use_ping}",$pingable,false,"{can_use_ping_explain}");
		$form[]=$tpl->field_array_hash($durations, "pinginterval", "{scan_interval} (ping)", $pinginterval);
		
	}
	
	
	
	
	
	$form[]=$tpl->field_text("netinfos", "{description}", base64_decode($netinfos));
	$html=$tpl->form_outside($title, @implode("\n", $form),"",$bt,"LoadAjax('table-loader-network-service','$page?table=yes');$safter","AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function Tinyjs():bool{
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
    $tpl=new template_admin();
    $users=new usersMenus();
    $page=CurrentPageName();
    $Enablentopng=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablentopng"));
    $add="Loadjs('$page?net-js=');";
    $btns=array();

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/nmap.pingnet.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/nmap.pingnet.progress.txt";
    $ARRAY["CMD"]="network.php?nmap-ping=yes";
    $ARRAY["TITLE"]="{nmap_scan_ping_title}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-network-service','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $NMAPInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NMAPInstalled"));
    if($NMAPInstalled==0){$jsscan="blur();";}
    $jsscan="Loadjs('fw.progress.php?content=$prgress&mainid=progress-network-restart')";



    $topbuttons[]=array($add,
        ico_plus,"{new_network}");

    $topbuttons[]=array($jsscan,
        ico_refresh,"{scan_your_network}");

    if($FireHolEnable==1){

        $ReconfigureFirewall=$tpl->framework_buildjs(
            "/firewall/reconfigure","firehol.reconfigure.progress",
            "firehol.reconfigure.log",
            "progress-network-restart",
            "");

        $topbuttons[]=array($ReconfigureFirewall,
            ico_save,"{apply_firewall_rules}");

    }
    if($Enablentopng==1){
        $jsrestart_ntopng=$tpl->framework_buildjs(
            "ntopng.php?restart=yes",
            "restart-ntopng.progress","restart-ntopng.progress.log",
            "progress-network-restart","LoadAjax('table-loader-network-service','$page?table=yes');");
        $topbuttons[]=array($jsrestart_ntopng,
            ico_refresh,"{restart} {APP_NTOPNG}");
    }
    $EnableARPScanner= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableARPScanner"));
    $ARPScannerSeen= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARPScannerSeen"));
    if($ARPScannerSeen==0){$EnableARPScanner=1;}

    if($EnableARPScanner==1) {
        $topbuttons[] = array("Loadjs('$page?arpscanner-js=yes')",
            ico_radar, "ARP Scanner ({active2})");
    }else{
        $topbuttons[] = array("Loadjs('$page?arpscanner-js=yes')",
            ico_radar, "ARP Scanner ({inactive2})");
    }


    if($users->AsFirewallManager) {
        $btns = $tpl->table_buttons($topbuttons);
    }

    $TINY_ARRAY["TITLE"]="{your_networks}";
    $TINY_ARRAY["ICO"]="fa fa-wifi";
    $TINY_ARRAY["EXPL"]="{your_networks_explain}";
    $TINY_ARRAY["URL"]="networks";
    $TINY_ARRAY["BUTTONS"] = $btns;

    header("content-type: application/x-javascript");
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    return true;
}

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

    $html=$tpl->page_header("{your_networks}","fa fa-wifi","{your_networks_explain}","$page?table=yes","networks","progress-network-restart",false,"table-loader-network-service"
    );

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return true;
	}
	
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function net_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
	$netinfos=$tpl->CLEAN_BAD_XSS($_POST["netinfos"]);
	
	if(!isset($_POST["netmaskcdir"])){
		$ipaddr=$tpl->CLEAN_BAD_CHARSNET($_POST["ip_addr"]);
		$netmask=intval($_POST["netmask"]);
        $newipaddr=$ipaddr;
		if($netmask<32) {
            $ttr = explode(".", $ipaddr);
            $newipaddr = $ttr[0] . "." . $ttr[1] . "." . $ttr[2] . ".0";
        }
		$cdir=$newipaddr."/".$_POST["netmask"];
		$net=new networkscanner();
		$net->networklist[]=$cdir;
		$net->save();
	}else{
		$cdir=$_POST["netmaskcdir"];
	}
	$netinfos=base64_encode($netinfos);

	
	if(!isset($_POST["pingable"])){$_POST["pingable"]=0;}
	if(!isset($_POST["pinginterval"])){$_POST["pinginterval"]=0;}
	
	//noping,yesping,prcping
	$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

	$sql="DELETE FROM networks_infos WHERE ipaddr='$cdir'";
    $q->QUERY_SQL($sql);
    $q->QUERY_SQL("INSERT INTO networks_infos (enabled,scannable,netinfos,ipaddr,pingable,pinginterval,noping,yesping,prcping,trusted,vpn) 
			VALUES('{$_POST["enabled"]}','{$_POST["scannable"]}','$netinfos','$cdir','{$_POST["pingable"]}','{$_POST["pinginterval"]}','{$_POST["noping"]}','{$_POST["yesping"]}','{$_POST["prcping"]}','{$_POST["trusted"]}','{$_POST["vpn"]}')");
	if(!$q->ok){echo $q->mysql_error;return false;}

    admin_tracks_post("My Network: Create a new local network $cdir - $netinfos");
    $memcached=new lib_memcached();
    $memcached->saveKey("WebConsoleTrustedNet","");
	
	$sql="SELECT pinginterval,ipaddr FROM networks_infos WHERE pingable='1'";
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$NET_PINGABLE[$ligne["ipaddr"]]=$ligne["pinginterval"];
		
	}
	$sock=new sockets();
	$sock->SaveConfigFile(serialize($NET_PINGABLE), "NET_PINGABLE");
	reload_services();
    return admin_tracks_post("Save Networks Informations");
}



function ping_report_table(){
	$net=$_GET["ping-report-table"];
	$netmd5=md5($net);
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	$html[]="<table id='ping-report-$netmd5' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";



	$html[]="<th data-sortable=true style='width:1%'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$q=new lib_sqlite("/home/artica/SQLITE/fping.db");
	$results=$q->QUERY_SQL("SELECT * FROM fping WHERE network='$net' AND available=".intval($_GET["available"])." ORDER BY hton");
	$TRCLASS=null;
	if(!$q->ok){echo $q->mysql_error_html();}

	foreach ($results as $index=>$ligne){
		$aclid=md5(serialize($ligne));
		$class=null;
		$q=new postgres_sql();
		if($ligne["mac"]<>null){
			$ligne2=@pg_fetch_array($q->QUERY_SQL("SELECT * FROM hostsnet WHERE ipaddr='{$ligne["ipaddr"]}'"));
		}

		if($_GET["available"]==1){
			$class="text-info ";
		}else{
			$class="text-danger ";
		}


		//javascript:

		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
		$html[]="<td class=\"center\"><i class='{$class}fa fa-desktop'></i></td>";
		if($ligne2["mac"]<>null){
			$js="Loadjs('fw.edit.computer.php?mac=".urlencode($ligne["mac"])."&CallBackFunction=')";
			if($ligne["hostname"]==null){$ligne["hostname"]=$ligne2["hostname"];}
			$html[]="<td>".$tpl->td_href($ligne["hostname"],$ligne["mac"],$js)."</td>";
			$html[]="<td class='$class'>".$tpl->td_href($ligne["ipaddr"],$ligne["mac"],$js)."</td>";
				
		}else{
			$html[]="<td>{$ligne["hostname"]}</td>";
			$html[]="<td class='$class'>{$ligne["ipaddr"]}</td>";
		}
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
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#ping-report-$netmd5').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function report_popup(){
	$net=$_GET["report-popup"];
	$netmd5=md5($net);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new mysql();
	
	
	
	
	$html[]="<table id='table-report-$netmd5' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	
	
	$html[]="<th data-sortable=true style='width:1%'></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{MAC}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{vendor}</th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$q=new lib_sqlite("/home/artica/SQLITE/nmapping.db");
	$results=$q->QUERY_SQL("SELECT * FROM nmapping WHERE network='$net'","artica_backup");
	$TRCLASS=null;
	
	
	foreach ($results as $index=>$ligne){
		$class=null;
		$q=new postgres_sql();
		if($ligne["mac"]<>null){
			$ligne2=@pg_fetch_array($q->QUERY_SQL("SELECT * FROM hostsnet WHERE mac='{$ligne["mac"]}'"));
		}
		
		$aclid=md5(serialize($ligne));
		
		if($ligne2["mac"]<>null){
			$class="text-success ";
		}
		
		
		//javascript:
		
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='row-parent-$aclid'>";
		$html[]="<td class=\"center\"><i class='{$class}fa fa-desktop'></i></td>";
		if($ligne2["mac"]<>null){
			$js="Loadjs('fw.edit.computer.php?mac=".urlencode($ligne["mac"])."&CallBackFunction=')";
			if($ligne["hostname"]==null){$ligne["hostname"]=$ligne2["hostname"];}
			$html[]="<td>".$tpl->td_href($ligne["hostname"],$ligne["mac"],$js)."</td>";
			$html[]="<td>".$tpl->td_href($ligne["ipaddr"],$ligne["mac"],$js)."</td>";
			
		}else{
			$html[]="<td>{$ligne["hostname"]}</td>";
			$html[]="<td>{$ligne["ipaddr"]}</td>";
		}
		$html[]="<td>{$ligne["mac"]}</td>";
		$html[]="<td>{$ligne["vendor"]}</td>";
		$html[]="</tr>";
	}
	
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
	$(document).ready(function() { $('#table-report-$netmd5').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function enable(){
    $netDel=$_GET["enable"];
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM networks_infos WHERE ipaddr='$netDel'");
    if(intval($ligne["enabled"])==1){
        $q->QUERY_SQL("UPDATE networks_infos SET enabled=0 WHERE ipaddr='$netDel'");
        reload_services();
        return;
    }
    $q->QUERY_SQL("UPDATE networks_infos SET enabled=1 WHERE ipaddr='$netDel'");
    $memcached=new lib_memcached();
    $memcached->saveKey("WebConsoleTrustedNet","");
    reload_services();
}

function isVPN():bool{
    $EnableTailScaleService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTailScaleService"));
    $SHOW_VPN=false;
    if($EnableTailScaleService==1){
        $SHOW_VPN=true;
    }
    return $SHOW_VPN;
}

function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $isFw=IsFw();
	$q=new lib_sqlite("/home/artica/SQLITE/nmapping.db");
	$items=$q->COUNT_ROWS("nmapping");
	$FPING_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FPING_INSTALLED"));
	$SHOW_VPN=isVPN();

	$table[]="<table class='table table-hover'><thead>
	<tr>
	<th style='width:1%'  nowrap>{enabled}</th>
	<th>{networks}</th>
	<th style='width:1%'  nowrap>{trusted_network}</th>
	<th style='width:1%'  nowrap>{vpn_allowed}</th>
	<th style='width:1%'  nowrap>% {used}</th>
	<th style='width:1%'  nowrap>Ping</th>
	<th style='width:1%'  nowrap>Scan</th>
	<th style='width:1%'  nowrap>{scan_report} $items {items}</th>
	<th style='width:1%'  nowrap>DEL</th>
	</tr>
	</thead>
	<tbody>
	";


    $durations[15]="15 {minutes}";
    $durations[30]="30 {minutes}";
    $durations[60]="1 {hour}";
    $durations[120]="2 {hours}";
    $durations[240]="4 {hours}";
    $durations[480]="8 {hours}";
    $durations[720]="12 {hours}";
    $durations[960]="16 {hours}";
    $durations[1440]="1 {day}";
    $durations[2880]="2 {days}";
    $durations[5760]="4 {days}";
    $durations[10080]="1 {week}";
    $durations[20160]="2 {weeks}";
    $durations[43200]="1 {month}";


    $q_nmapping=new lib_sqlite("/home/artica/SQLITE/nmapping.db");
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

    $results=$q->QUERY_SQL("SELECT * FROM routing_rules_src");
    foreach ($results as $index=>$ligne){
        $pattern=$ligne["pattern"];
        if($pattern=="0.0.0.0/0.0.0.0"){continue;}
        $netinfos=base64_encode("Network $pattern");
        $q->QUERY_SQL("INSERT INTO networks_infos (ipaddr,netinfos) VALUES ('$pattern','$netinfos')");

    }
    $results=$q->QUERY_SQL("SELECT * FROM routing_rules_dest");

    foreach ($results as $index=>$ligne){
        $pattern=$ligne["pattern"];
        if($pattern=="0.0.0.0/0.0.0.0"){continue;}
        $netinfos=base64_encode("Network $pattern");
        $q->QUERY_SQL("INSERT INTO networks_infos (ipaddr,netinfos) VALUES ('$pattern','$netinfos')");

    }

    $addDef=false;
    $ligne3=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM networks_infos WHERE trusted=1 AND enabled=1");
    $tcount=$ligne3["tcount"];
    $zNet=array();
    if($tcount==0){
        $addDef=true;
        $zNet["10.0.0.0/8"] = true;
		$zNet["172.16.0.0/12"] = true;
		$zNet["192.168.0.0/16"] = true;
    }


    $td1=$tpl->table_td1prc();
    $results=$q->QUERY_SQL("SELECT * FROM networks_infos");
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $maks=$ligne["ipaddr"];
        if(!preg_match("#^[0-9.]+/[0-9]+#",$maks)){
            $q->QUERY_SQL("DELETE FROM networks_infos WHERE ID=$ID");
            $q_nmapping->QUERY_SQL("DELETE FROM nmapping WHERE network='$maks'");
            continue;
        }
        $pingable_text="";
        $maks_encoded=urlencode($maks);
        $scannable=intval($ligne["scannable"]);
        $pingable=intval($ligne["pingable"]);
        $pinginterval=intval($ligne["pinginterval"]);
        $prcping=intval($ligne["prcping"]);
        $trusted=intval($ligne["trusted"]);
        $enabled=intval($ligne["enabled"]);
        $vpn=intval($ligne["vpn"]);
        $trusted_network_fw=null;

        if($addDef){
            if(isset($zNet[$maks])){
                $trusted=1;
                unset($zNet[$maks]);
            }
        }

        if(!$q->ok){echo $q->mysql_error_html(true);}
        $netinfos=htmlspecialchars(base64_decode($ligne["netinfos"]));
        $netinfos=nl2br($netinfos);
        if($netinfos==null){$netinfos=" <i>({no_info})</i>";}
        $report_button="{no_report}";
        $scannable_text=null;
        $sql="SELECT COUNT(*) as tcount FROM nmapping WHERE network='$maks'";
        $ligne=$q_nmapping->mysqli_fetch_array($sql);
        if(!$q->ok){echo $q->mysql_error_html(true);}
        $patternenc=urlencode($maks);
	$NmapCount=$ligne["tcount"];
	$ping_prc=$tpl->icon_nothing();
	
	if($NmapCount>0){
		$report_button="<a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?report-js=$patternenc');\"><span class='label label-primary'>$NmapCount {items}</span></a>";
	}
	
	
	if($scannable==1){
		$scannable_icon="<i class='fas fa-check'></i>";
		$scannable_text="{can_be_analyzed}";
	}else{
		$report_button=$tpl->icon_nothing();
		$scannable_icon="&nbsp;";
	}
	
	if($pingable==1){
		$pingable_icon="<i class='fas fa-check'></i>";
		$pingable_text=", {can_use_ping} {each} {$durations[$pinginterval]}";
		if($prcping>0) {
            $ping_prc = "<a href=\"javascript:blur();\" 
			OnClick=\"Loadjs('$page?ping-report-js=$patternenc');\">
			<span class='label label-primary'>$prcping%</span></a>";
        }
	}else{
		$pingable_icon=$tpl->icon_nothing();
	}


	$trusted_icon=$tpl->icon_check($trusted,"Loadjs('$page?enabled-trusted=$maks_encoded')",null,"AsFirewallManager");
    if($trusted==1){
        $trusted_network_fw="{trusted_network_fw}, ";
    }


	
	if($FPING_INSTALLED==0){
		$pingable_text=null;
		$pingable_icon=$tpl->icon_nothing();
	}
        $enabled_ico=$tpl->icon_check($enabled,"Loadjs('$page?enable=$maks_encoded')",
            null,"AsFirewallManager");

	if($enabled)
	
	$id=md5($maks);
        $zAMXTXT=array();
        $AMXTXT=null;
	    if($trusted_network_fw<>null){
            $zAMXTXT[]=$trusted_network_fw;
        }
        if($scannable_text<>null){
            $zAMXTXT[]=$scannable_text;
        }
        if($pingable_text<>null){
            $zAMXTXT[]=$pingable_text;
        }
        if($netinfos<>null){
            $zAMXTXT[]=$netinfos;
        }
        if(count($zAMXTXT)>0){
            $AMXTXT="<br><small>".@implode("<br>",$zAMXTXT)."</small>";
        }


    $vpn_ico=$tpl->icon_nothing();
    if($SHOW_VPN){
        if($vpn==1){
            $vpn_ico="<i class='fas fa-check'></i>";
        }
    }



	$table[]="<tr id='$id'>";
	$table[]="<td $td1>$enabled_ico</td>";
	$table[]="<td>". $tpl->td_href($maks,"{click_to_edit}","Loadjs('$page?net-js={$maks_encoded}');","AsFirewallManager")."$AMXTXT</td>";
    $table[]="<td $td1>$trusted_icon</td>";
    $table[]="<td $td1>$vpn_ico</td>";
	$table[]="<td $td1>$ping_prc</td>";
	$table[]="<td $td1>$pingable_icon</td>";
	$table[]="<td $td1>$scannable_icon</td>";
	$table[]="<td>$report_button</td>";
	$table[]="<td>". $tpl->icon_delete("Loadjs('$page?netmask-unlink={$maks_encoded}&md=$id')","AsFirewallManager")."</td>";
	$table[]="</tr>";
	
	}

    if($addDef){
        foreach ($zNet as $maks=>$none){
            $trusted_icon=$tpl->icon_check(1,"",null,"AsFirewallManager");
            $enabled_ico=$tpl->icon_check(1,"",null,"AsFirewallManager");
            $vpn_ico=$tpl->icon_nothing();
            $ping_prc=$tpl->icon_nothing();
            $pingable_icon=$tpl->icon_nothing();
            $scannable_icon=$tpl->icon_nothing();
            $report_button="&nbsp;";
            $table[]="<tr id='$id'>";
            $table[]="<td $td1>$enabled_ico</td>";
            $table[]="<td>$maks</td>";
            $table[]="<td $td1>$trusted_icon</td>";
            $table[]="<td $td1>$vpn_ico</td>";
            $table[]="<td $td1>$ping_prc</td>";
            $table[]="<td $td1>$pingable_icon</td>";
            $table[]="<td $td1>$scannable_icon</td>";
            $table[]="<td>$report_button</td>";
            $table[]="<td>&nbsp;</td>";
            $table[]="</tr>";
        }
    }

	$table[]="</tbody></table>";
    $table[]="<script>";
    $table[]="Loadjs('$page?tinyjs=yes');";
    $table[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $table));
    return true;
}
function IsFw():bool{
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
    if($FireHolEnable==1){return true;}
    $EnableCrowdSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrowdSec"));
    if($EnableCrowdSec==1){return true;}
    return false;
}


