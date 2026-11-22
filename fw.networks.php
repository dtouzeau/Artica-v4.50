<?php
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
// awc_table_style()/awc_pager_js()/awc_search_bar(): kit tableaux — include INCONDITIONNEL.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["tinyjs"])){Tinyjs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["ip_addr"])){net_save();exit;}
if(isset($_GET["net-js"])){net_js();exit;}
if(isset($_GET["net-new-popup"])){net_new_popup();exit;}
if(isset($_POST["new-network"])){net_new_save();exit;}
if(isset($_GET["net-popup"])){net_popup();exit;}
if(isset($_GET["netmask-unlink"])){net_del();exit;}
if(isset($_GET["net-delete-js"])){net_delete_js();exit;}
if(isset($_POST["nwl-del-confirm"])){exit;}
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
    $page=CurrentPageName();
    $cdir=(string)$_GET["enabled-trusted"];
    $tpl=new template_admin();
    if(!fwnet_valid($cdir)){return false;}
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
    net_compile();
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-loader-network-service','$page?table=yes');\n";
    return true;
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
	$net_title=trim($_GET["net-js"]);
    $function=$_GET["function"];
    if( $net_title=="" ){
        $title="{new_network}";
        return $tpl->js_dialog($title, "$page?net-new-popup=yes&function=$function");
    }

	$net=urlencode($net_title);
	$title=$net_title;
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
	$netDel=isset($_GET["netmask-unlink"])?(string)$_GET["netmask-unlink"]:"";
	if(!preg_match('#^[0-9a-fA-F.:]+/[0-9]{1,3}$#',$netDel) && !preg_match('#^[0-9.]+$#',$netDel)){
		return;
	}
	$net=new networkscanner();
	foreach ($net->networklist as $num=>$maks){
		if($maks==$netDel){
			unset($net->networklist[$num]);
			$net->save();
			break;
		}
	}
	$q_interface=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
	$q_interface->QUERY_SQL("DELETE FROM networks_infos WHERE ipaddr='$netDel'");

	$q=new lib_sqlite("/home/artica/SQLITE/fping.db");
	$q->QUERY_SQL("DELETE FROM fping WHERE network='$netDel'");

	$q=new lib_sqlite("/home/artica/SQLITE/nmapping.db");
	$q->QUERY_SQL("DELETE FROM nmapping WHERE network='$netDel'");

	$memcached=new lib_memcached();
	$memcached->saveKey("WebConsoleTrustedNet","");
	admin_tracks("My Network: Delete local network $netDel");
	$md=preg_replace('/[^a-f0-9]/','',(string)(isset($_GET["md"])?$_GET["md"]:""));
	if($md!==""){
		echo "$('#$md').remove();\n";
	}
	reload_services();
}

function net_delete_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $net=trim((string)$_GET["net-delete-js"]);
    if(!preg_match('#^[0-9a-fA-F.:]+/[0-9]{1,3}$#',$net) && !preg_match('#^[0-9.]+$#',$net)){
        return $tpl->js_error("{error}");
    }
    $md=preg_replace('/[^a-f0-9]/','',(string)(isset($_GET["md"])?$_GET["md"]:""));
    $net_enc=urlencode($net);
    $js="Loadjs('$page?netmask-unlink=$net_enc&md=$md');";
    return $tpl->js_confirm_execute(htmlspecialchars($net,ENT_QUOTES),"nwl-del-confirm","yes",$js);
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
    $NTPDEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDEnabled"));

	if($EnableFail2Ban==1){$GLOBALS["CLASS_SOCKETS"]->getFrameWork("fail2ban.php?restart=yes");}
	if($EnableSuricata==1){$GLOBALS["CLASS_SOCKETS"]->REST_API_SURICATA("/suricata/reload");}
    if($EnableTailScaleService==1){$GLOBALS["CLASS_SOCKETS"]->getFrameWork("tailscale.php?connect=yes");}
    if($NTPDEnabled==1){$GLOBALS["CLASS_SOCKETS"]->REST_API("/chrony/reconfigure");}

    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    if($UnboundEnabled==1) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/reconfig");
    }


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
function net_new_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $js[]="BootstrapDialog1.close()";
    $js[]="LoadAjax('table-loader-network-service','$page?table=yes');";

    echo $tpl->BigNetworkField("new-network","{new_network}","{new_trusted_network_explain_v6}","","192.168.1.0/24",implode(";",$js),null,true);
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
    if($network==""){
        echo $tpl->div_error("{no_network_defined}");
        return true;
    }



		$net=explode("/",$network);
		// Pentest 2026-11-04 : net-popup etait concatene dans la requete.
		$sql="SELECT * FROM networks_infos WHERE ipaddr=?";
		$q_interface=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$r=$q_interface->QUERY_SQL_PARAMS($sql,array((string)$network));
		$ligne=isset($r[0]) ? $r[0] : array();
		$scannable=intval($ligne["scannable"]);
		$pingable=intval($ligne["pingable"]);
		$pinginterval=intval($ligne["pinginterval"]);
        $enabled=intval($ligne["enabled"]);
        $trusted=intval($ligne["trusted"]);
        $dns=intval($ligne["dns"]);
        if($trusted==1){
            $dns=1;
        }
        $netmask="";
		if(intval($net[1])>0){
			$ipv=new ipv4($net[0],$net[1]);
			$net[0]=$ipv->address();
			$netmask=$ipv->netmask();
			$safter=null;
		} 	

    $explain[]="{ip_address}: <strong>$net[0]</strong>";
    $explain[]="{netmask}: <strong>$netmask</strong>";
    $explain[]="{cdir}: <strong>$network</strong>";



    $after="LoadAjax('table-loader-network-service','$page?table=yes');";

    $html[]=$tpl->BigCircleCheckbox("ip_addr:$network|enabled","{enabled}",@implode("<br>",$explain),$enabled,$after);

    $html[]=$tpl->BigExplainCheckbox("ip_addr:$network|netinfos","{description}",base64_decode($ligne["netinfos"]),$after);

    $html[]=$tpl->BigCircleCheckbox("ip_addr:$network|trusted","{trusted_network}","{trusted_network_explain}",$trusted,$after);

    $html[]=$tpl->BigCircleCheckbox("ip_addr:$network|dns","{allow_query_dns}","{allow_query_dns_explain}",$dns,$after);

    if($SHOW_VPN){
        $html[]=$tpl->BigCircleCheckbox("ip_addr:$network|vpn","{publish_in_vpn_network}","{publish_in_vpn_network_explain}",intval($ligne["vpn"]),$after);
    }

    $html[]=$tpl->BigCircleCheckbox("ip_addr:$network|scannable","{can_be_analyzed}","{can_be_analyzed_explain}",$scannable,$after);

    $FPING_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FPING_INSTALLED"));
    if($FPING_INSTALLED==1){
        if($pinginterval<5){$pinginterval=15;}

        $html[]=$tpl->BigCircleCheckbox("ip_addr:$network|pingable","{can_use_ping}","{can_use_ping_explain}",$pingable,$after);

        $html[]=$tpl->BigFieldIntegerCheckbox("ip_addr:$network|pinginterval",
        "{scan_interval} (ping - {minutes})","{scan_interval}",intval($pinginterval),null,null,$after);


    }

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



		
	}

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
    $jsscan="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-network-restart')";



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
function net_new_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $netinfos=$tpl->CLEAN_BAD_XSS($_POST["new-network"]);
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $cdir=fwnet_cidr((string)$netinfos);
    if($cdir===""){echo $tpl->post_error("{invalid_network}");return false;}
    $netinfos=$cdir;

    $netinfosBase64=base64_encode($netinfos);
    $sql="DELETE FROM networks_infos WHERE ipaddr='$cdir'";
    $q->QUERY_SQL($sql);
    $sql="DELETE FROM networks_infos WHERE ipaddr='$netinfos'";
    $q->QUERY_SQL($sql);
    $q->QUERY_SQL("INSERT INTO networks_infos (enabled,scannable,netinfos,ipaddr,pingable,pinginterval,noping,yesping,prcping,trusted,vpn) 
			VALUES('1','0','$netinfosBase64','$cdir','0','0','1','0','0','1','0')");
    if(!$q->ok){echo $q->mysql_error;return false;}
    reload_services();
    return admin_tracks_post("Add new trusetd Network $cdir");
}
function net_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $Net=(string)$_POST["ip_addr"];
    unset($_POST["ip_addr"]);
    if(!fwnet_valid($Net)){echo "{invalid_network}";return false;}
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    foreach ($_POST as $key=>$value){
        $key=preg_replace('/[^A-Za-z0-9_]/','',(string)$key);
        if($key==""){continue;}
        if($key=="netinfos"){
            $value=base64_encode($value);
        }

        $value=$q->sqlite_escape_string2($value);
        $sql="UPDATE networks_infos SET $key='$value' WHERE ipaddr='$Net'";
        if(!$q->QUERY_SQL($sql)){echo $q->mysql_error;return false;}

    }

    $memcached=new lib_memcached();
    $memcached->saveKey("WebConsoleTrustedNet","");
	
	$sql="SELECT pinginterval,ipaddr FROM networks_infos WHERE pingable='1'";
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$NET_PINGABLE[$ligne["ipaddr"]]=$ligne["pinginterval"];
		
	}

	$GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($NET_PINGABLE), "NET_PINGABLE");
	reload_services();
    return admin_tracks_post("Save $Net Network Informations");
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
	// Pentest 2026-11-04 : ping-report-table etait concatene dans la requete.
	$results=$q->QUERY_SQL_PARAMS("SELECT * FROM fping WHERE network=? AND available=".intval($_GET["available"])." ORDER BY hton",array((string)$net));
	$TRCLASS=null;
	if(!$q->ok){echo $q->mysql_error_html();}

	foreach ($results as $index=>$ligne){
		$aclid=md5(serialize($ligne));
		$class=null;
		$q=new postgres_sql();
		if($ligne["mac"]<>null){
			// Pentest 2026-11-04 : ipaddr (resultat de scan, donnee externe) etait concatene dans la requete.
			$ligne2=@pg_fetch_array($q->QUERY_SQL_PARAMS("SELECT * FROM hostsnet WHERE ipaddr=$1",array(strval($ligne["ipaddr"]))));
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
	// Pentest 2026-11-04 : report-popup etait concatene dans la requete.
	$results=$q->QUERY_SQL_PARAMS("SELECT * FROM nmapping WHERE network=?",array((string)$net));
	$TRCLASS=null;
	
	
	foreach ($results as $index=>$ligne){
		$class=null;
		$q=new postgres_sql();
		if($ligne["mac"]<>null){
			// Pentest 2026-11-04 : mac (resultat de scan, donnee externe) etait concatene dans la requete.
			$ligne2=@pg_fetch_array($q->QUERY_SQL_PARAMS("SELECT * FROM hostsnet WHERE mac=$1",array(strval($ligne["mac"]))));
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
    $page=CurrentPageName();
    $netDel=isset($_GET["enable"])?(string)$_GET["enable"]:"";
    if(!preg_match('#^[0-9a-fA-F.:]+/[0-9.]+$#',$netDel) && !preg_match('#^[0-9.]+$#',$netDel)){
        header("content-type: application/x-javascript");
        echo "console.error('enable: bad network');\n";
        return;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM networks_infos WHERE ipaddr='$netDel'");
    if(intval($ligne["enabled"])==1){
        $q->QUERY_SQL("UPDATE networks_infos SET enabled=0 WHERE ipaddr='$netDel'");
        admin_tracks("My Network: disable $netDel");
    }else{
        $q->QUERY_SQL("UPDATE networks_infos SET enabled=1 WHERE ipaddr='$netDel'");
        $memcached=new lib_memcached();
        $memcached->saveKey("WebConsoleTrustedNet","");
        admin_tracks("My Network: enable $netDel");
    }
    reload_services();
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-loader-network-service','$page?table=yes');\n";
}

function isVPN():bool{
    $EnableTailScaleService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTailScaleService"));
    $SHOW_VPN=false;
    if($EnableTailScaleService==1){
        $SHOW_VPN=true;
    }
    return $SHOW_VPN;
}

// ---------------------------------------------------------------------------
// Redesign stitch_modern_firewall_whitelist_manager — une fonction = un widget.

function nwl_css():string{
    if(!empty($GLOBALS["NWL_CSS_DONE"])){return "";}
    $GLOBALS["NWL_CSS_DONE"]=true;
    $mono="ui-monospace,SFMono-Regular,Menlo,Consolas,monospace";
    return "<style type='text/css'>
.nwl-toolbar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;
 background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:12px;margin:0 0 14px 0;
 box-shadow:0 1px 2px rgba(15,23,42,.04)}
.nwl-toolbar .sb-stitch{margin:0;flex:1 1 260px;max-width:420px}
.nwl-toolbar .sb-stitch .ibox-content{padding:0;border:0;background:transparent}
.nwl-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;
 box-shadow:0 1px 2px rgba(15,23,42,.04);margin:0 0 14px 0}
.nwl-scroll{overflow-x:auto}
.nwl-tbl{width:100%;border-collapse:collapse;text-align:left;font-size:12px;color:#334155}
.nwl-tbl thead tr{background:rgba(248,250,252,.9);border-bottom:1px solid #e2e8f0}
.nwl-tbl thead th{padding:14px 16px;font-size:11px;font-weight:600;letter-spacing:.06em;
 text-transform:uppercase;color:#64748b;white-space:nowrap}
.nwl-tbl thead th.nwl-th-main{color:#334155;font-weight:700}
.nwl-tbl tbody tr{border-top:1px solid #f1f5f9;transition:background .15s}
.nwl-tbl tbody tr:first-child{border-top:0}
.nwl-tbl tbody tr:hover{background:rgba(248,250,252,.85)}
.nwl-tbl tbody td{padding:16px;vertical-align:middle}
.nwl-tbl .nwl-c{text-align:center}
.nwl-tbl .nwl-r{text-align:right}
.nwl-off{background:rgba(255,251,235,.55);border-left:2px solid #f59e0b}
.nwl-off:hover{background:rgba(255,251,235,.85)}
.nwl-check{width:16px;height:16px;margin:0;cursor:pointer;accent-color:#0d9488;
 vertical-align:middle}
.nwl-check:disabled{cursor:not-allowed;opacity:.45}
.nwl-check-static{display:inline-flex;color:#cbd5e1;font-size:14px;line-height:1}
.nwl-cidr{font-family:$mono;font-size:13px;font-weight:700;color:#0f172a}
a.nwl-cidr-link{color:inherit;text-decoration:none;cursor:pointer;display:inline-block}
a.nwl-cidr-link:hover .nwl-cidr{color:#0d9488}
.nwl-pfx{color:#0d9488}
.nwl-sub{font-size:11px;color:#94a3b8;margin-top:2px;font-family:inherit}
.nwl-name{font-size:13px;font-weight:600;color:#1e293b}
.nwl-meta{font-size:11px;color:#94a3b8;margin-top:2px;line-height:1.35}
.nwl-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;
 font-size:11px;font-weight:600;border:1px solid transparent;white-space:nowrap;cursor:default}
a.nwl-pill{text-decoration:none!important;cursor:pointer}
.nwl-pill-yes{background:#ecfdf5;color:#047857;border-color:rgba(16,185,129,.35)}
.nwl-pill-no{background:#f1f5f9;color:#475569;border-color:#e2e8f0}
.nwl-pill-ban{background:#fff1f2;color:#be123c;border-color:rgba(244,63,94,.3)}
.nwl-acts{display:inline-flex;align-items:center;gap:2px}
.nwl-act{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;
 border:0;background:transparent;border-radius:8px;color:#94a3b8;cursor:pointer;padding:0}
.nwl-act:hover{background:#f1f5f9;color:#0d9488}
.nwl-act-ind:hover{color:#005447}
.nwl-act-warn{color:#d97706}
.nwl-act-warn:hover{background:#fef3c7;color:#b45309}
.nwl-act-del:hover{background:#fff1f2;color:#e11d48}
.nwl-foot{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;
 padding:14px 20px;border-top:1px solid rgba(226,232,240,.9);background:rgba(248,250,252,.6);
 font-size:12px;color:#64748b}
.nwl-foot b{color:#0f172a;font-family:$mono;font-weight:700}
.nwl-cards{display:grid;grid-template-columns:repeat(1,minmax(0,1fr));gap:12px;margin:0 0 8px 0}
@media(min-width:768px){.nwl-cards{grid-template-columns:repeat(3,minmax(0,1fr))}}
.nwl-diag{display:flex;align-items:flex-start;gap:12px;padding:14px;background:#fff;
 border:1px solid rgba(226,232,240,.95);border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.03)}
.nwl-diag-ico{width:36px;height:36px;border-radius:8px;display:inline-flex;align-items:center;
 justify-content:center;flex:0 0 auto;font-size:15px;border:1px solid transparent}
.nwl-diag-ico.indigo{background:#e6eeed;border-color:#ccddda;color:#005447}
.nwl-diag-ico.teal{background:#f0fdfa;border-color:#ccfbf1;color:#0d9488}
.nwl-diag-ico.emerald{background:#ecfdf5;border-color:#d1fae5;color:#059669}
.nwl-diag h4{margin:0;font-size:12px;font-weight:700;color:#0f172a}
.nwl-diag p{margin:3px 0 0 0;font-size:11px;color:#64748b;line-height:1.4}
</style>";
}

function nwl_pill($on,$clickJs=null,$ban=false):string{
    if($on){
        $cls="nwl-pill nwl-pill-yes";
        $ico="fas fa-shield-check";
        $txt="{yes}";
    }elseif($ban){
        $cls="nwl-pill nwl-pill-ban";
        $ico="fas fa-ban";
        $txt="{no}";
    }else{
        $cls="nwl-pill nwl-pill-no";
        $ico="far fa-circle-question";
        $txt="{no}";
    }
    $inner="<i class='$ico' style='font-size:10px'></i> $txt";
    if($clickJs!==null && $clickJs!==""){
        $js=htmlspecialchars($clickJs,ENT_QUOTES);
        return "<a href=\"javascript:blur();\" class='$cls' OnClick=\"$js\">$inner</a>";
    }
    return "<span class='$cls'>$inner</span>";
}

// Case a cocher native (design stitch): Loadjs enable puis refresh table.
function nwl_cell_enabled($page,$enc,$enabled,$editable):string{
    if(!$editable){
        return "<span class='nwl-check-static' title=\"{nwl_default_range}\"><i class='fas fa-minus'></i></span>";
    }
    $users=new usersMenus();
    if(!$users->AsFirewallManager){
        $chk=$enabled?" checked":"";
        return "<input type='checkbox' class='nwl-check' disabled$chk>";
    }
    $chk=$enabled?" checked":"";
    $enc=htmlspecialchars((string)$enc,ENT_QUOTES);
    return "<input type='checkbox' class='nwl-check'$chk OnClick=\"Loadjs('$page?enable=$enc');\">";
}

function nwl_netmask($cidr):string{
    $parts=explode("/",$cidr);
    if(count($parts)<2){return "";}
    $bits=intval($parts[1]);
    if($bits<0 || $bits>32){return "";}
    if($bits===0){return "0.0.0.0";}
    $ipv=new ipv4($parts[0],$bits);
    return (string)$ipv->netmask();
}

function nwl_cell_cidr($cidr,$clickJs=null):string{
    $raw=(string)$cidr;
    $host=htmlspecialchars($raw,ENT_QUOTES);
    $pfx="";
    if(preg_match('#^(.+)/([0-9]{1,3})$#',$raw,$m)){
        $host=htmlspecialchars($m[1],ENT_QUOTES);
        $pfx="/".htmlspecialchars($m[2],ENT_QUOTES);
    }
    $mask=nwl_netmask($raw);
    $sub="";
    if($mask!==""){
        $sub="<div class='nwl-sub'>{netmask}: ".htmlspecialchars($mask,ENT_QUOTES)."</div>";
    }
    $inner="<div class='nwl-cidr'>$host<span class='nwl-pfx'>$pfx</span></div>$sub";
    if($clickJs!==null && $clickJs!==""){
        $js=htmlspecialchars($clickJs,ENT_QUOTES);
        return "<a href=\"javascript:blur();\" class='nwl-cidr-link' title=\"{click_to_edit}\" OnClick=\"$js\">$inner</a>";
    }
    return $inner;
}

function nwl_cell_name($netinfos,$metaLines):string{
    $raw=trim(strip_tags(str_replace(array("<br>","<br/>","<br />"),"\n",(string)$netinfos)));
    $lines=preg_split('/\r\n|\r|\n/',$raw);
    $title=trim((string)$lines[0]);
    if($title===""){$title="{no_info}";}
    $title=htmlspecialchars($title,ENT_QUOTES);
    $h="<div class='nwl-name'>$title</div>";
    $sub=array();
    if(isset($lines[1]) && trim($lines[1])!==""){
        $sub[]=htmlspecialchars(trim($lines[1]),ENT_QUOTES);
    }
    if(is_array($metaLines)){
        foreach($metaLines as $m){
            $m=trim(strip_tags((string)$m));
            if($m!==""){$sub[]=$m;}
        }
    }
    if(count($sub)>0){
        $h.="<div class='nwl-meta'>".@implode(" · ",$sub)."</div>";
    }
    return $h;
}

function nwl_cell_actions($page,$maks_enc,$id,$nmapCount,$pingable,$prcping,$editable):string{
    $tpl=new template_admin();
    $h="<div class='nwl-acts'>";
    if($editable){
        $h.="<button type='button' class='nwl-act' title=\"{click_to_edit}\" "
            ."OnClick=\"Loadjs('$page?net-js=$maks_enc');\"><i class='fas fa-sliders'></i></button>";
    }
    if($nmapCount>0){
        $h.="<button type='button' class='nwl-act nwl-act-ind' title=\"{scan_report} ($nmapCount)\" "
            ."OnClick=\"Loadjs('$page?report-js=$maks_enc');\"><i class='fas fa-list-check'></i></button>";
    }
    if($pingable==1 && $prcping>0){
        $h.="<button type='button' class='nwl-act' title=\"Ping $prcping%\" "
            ."OnClick=\"Loadjs('$page?ping-report-js=$maks_enc');\"><i class='fas fa-satellite-dish'></i></button>";
    }
    if($editable){
        $h.="<button type='button' class='nwl-act nwl-act-del' title=\"{delete}\" "
            ."OnClick=\"Loadjs('$page?net-delete-js=$maks_enc&md=$id');\"><i class='far fa-trash-can'></i></button>";
    }
    return $h."</div>";
}

function nwl_card_ids($scanned,$total):string{
    $scanned=intval($scanned);
    $total=intval($total);
    $h ="<div class='nwl-diag'>";
    $h.="<span class='nwl-diag-ico indigo'><i class='fas fa-shield-virus'></i></span>";
    $h.="<div><h4>{nwl_ids_ips_title}</h4>";
    // Traduire AVANT de substituer : sur "{nwl_ids_ips_explain}" brut, il n'y a aucun %s à remplacer.
    $tpl=new template_admin();
    $txt=$tpl->_ENGINE_parse_body("{nwl_ids_ips_explain}");
    $h.="<p>".str_replace(array("%s","%t"),array((string)$scanned,(string)$total),$txt)."</p>";
    $h.="</div></div>";
    return $h;
}

function nwl_card_arp($enabled):string{
    $h ="<div class='nwl-diag'>";
    $h.="<span class='nwl-diag-ico teal'><i class='fas fa-radar'></i></span>";
    $h.="<div><h4>{nwl_arp_title}</h4>";
    if($enabled){
        $h.="<p>{nwl_arp_explain_on}</p>";
    }else{
        $h.="<p>{nwl_arp_explain_off}</p>";
    }
    $h.="</div></div>";
    return $h;
}

function nwl_card_strict():string{
    $h ="<div class='nwl-diag'>";
    $h.="<span class='nwl-diag-ico emerald'><i class='fas fa-lock'></i></span>";
    $h.="<div><h4>{nwl_strict_title}</h4>";
    $h.="<p>{nwl_strict_explain}</p>";
    $h.="</div></div>";
    return $h;
}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
    $q_nmapping=new lib_sqlite("/home/artica/SQLITE/nmapping.db");
    $FPING_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FPING_INSTALLED"));
    $SHOW_VPN=isVPN();

    $durations=array();
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

    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

    // Moteur de routage actif: les motifs viennent de l'API, routing_rules_* n'est plus tenu a jour.
    include_once(dirname(__FILE__)."/ressources/class.routing.ui.inc");
    $RoutingEngineWhy=rtui_legacy_write_refused();
    $RoutingEnginePatterns=null;
    if($RoutingEngineWhy=="{routing_legacy_managed}" || $RoutingEngineWhy=="{routing_mig_activating}"){$RoutingEnginePatterns=rtui_legacy_rule_patterns();}
    if($RoutingEnginePatterns!==null){
        $results=array();
        foreach ($RoutingEnginePatterns["src"] as $RoutingEnginePattern){$results[]=array("pattern"=>$RoutingEnginePattern);}
    }else{
        $results=$q->QUERY_SQL("SELECT * FROM routing_rules_src");
    }
    foreach ($results as $index=>$ligne){
        $pattern=$ligne["pattern"];
        if($pattern=="0.0.0.0/0.0.0.0"){continue;}
        $netinfos=base64_encode("Network $pattern");
        $q->QUERY_SQL_PARAMS("INSERT INTO networks_infos (ipaddr,netinfos) VALUES (?,?)",array($pattern,$netinfos));
    }
    if($RoutingEnginePatterns!==null){
        $results=array();
        foreach ($RoutingEnginePatterns["dest"] as $RoutingEnginePattern){$results[]=array("pattern"=>$RoutingEnginePattern);}
    }else{
        $results=$q->QUERY_SQL("SELECT * FROM routing_rules_dest");
    }
    foreach ($results as $index=>$ligne){
        $pattern=$ligne["pattern"];
        if($pattern=="0.0.0.0/0.0.0.0"){continue;}
        $netinfos=base64_encode("Network $pattern");
        $q->QUERY_SQL_PARAMS("INSERT INTO networks_infos (ipaddr,netinfos) VALUES (?,?)",array($pattern,$netinfos));
    }

    $addDef=false;
    // Suggestions RFC1918 seulement si la table est vide (sinon elles
    // passent pour de vraies lignes a cocher et le clic ne fait rien).
    $ligneAll=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM networks_infos");
    $zNet=array();
    if(intval($ligneAll["tcount"])==0){
        $addDef=true;
        $zNet["10.0.0.0/8"]=true;
        $zNet["172.16.0.0/12"]=true;
        $zNet["192.168.0.0/16"]=true;
    }

    $rows=array();
    $scanned=0;
    $total=0;
    $results=$q->QUERY_SQL("SELECT * FROM networks_infos");
    foreach ($results as $index=>$ligne){
        $ID=intval($ligne["ID"]);
        $maks=$ligne["ipaddr"];
        // IPv4 ou IPv6 (lot 6b) : le motif IPv4 seul effaçait au premier
        // affichage tout réseau de confiance IPv6 qu'on venait d'ajouter.
        if(!preg_match("#^([0-9.]+|[0-9a-fA-F:]+)/[0-9]+#",$maks)){
            $q->QUERY_SQL("DELETE FROM networks_infos WHERE ID=$ID");
            // Pentest 2026-11-04 : ipaddr (non valide par construction) etait concatene dans la requete.
            $q_nmapping->QUERY_SQL_PARAMS("DELETE FROM nmapping WHERE network=?",array(strval($maks)));
            continue;
        }
        $maks_encoded=urlencode($maks);
        $scannable=intval($ligne["scannable"]);
        $pingable=intval($ligne["pingable"]);
        $pinginterval=intval($ligne["pinginterval"]);
        $prcping=intval($ligne["prcping"]);
        $trusted=intval($ligne["trusted"]);
        $enabled=intval($ligne["enabled"]);
        $dns=intval($ligne["dns"]);
        $vpn=intval($ligne["vpn"]);

        if($addDef && isset($zNet[$maks])){
            $trusted=1;
            unset($zNet[$maks]);
        }
        if($trusted==1){$dns=1;}

        $netinfos=base64_decode($ligne["netinfos"]);

        // Pentest 2026-11-04 : ipaddr (motif non ancre en fin) etait concatene dans la requete.
        $ligneN=$q_nmapping->QUERY_SQL_PARAMS("SELECT COUNT(*) as tcount FROM nmapping WHERE network=?",array(strval($maks)));
        $ligneN=(is_array($ligneN) && isset($ligneN[0]))?$ligneN[0]:array();
        $nmapCount=intval($ligneN["tcount"]);

        $meta=array();
        if($trusted==1){$meta[]="{trusted_network_fw}";}
        if($scannable==1){
            $meta[]="{can_be_analyzed}";
            $scanned++;
        }
        if($pingable==1 && $FPING_INSTALLED==1){
            $iv=isset($durations[$pinginterval])?$durations[$pinginterval]:"";
            $meta[]="{can_use_ping}".($iv!==""?" {each} $iv":"");
        }

        $total++;
        $rows[]=array(
            "id"=>md5($maks),
            "cidr"=>$maks,
            "enc"=>$maks_encoded,
            "enabled"=>$enabled,
            "trusted"=>$trusted,
            "dns"=>$dns,
            "vpn"=>$vpn,
            "netinfos"=>$netinfos,
            "meta"=>$meta,
            "nmap"=>$nmapCount,
            "pingable"=>$pingable,
            "prcping"=>$prcping,
            "editable"=>true,
        );
    }

    if($addDef){
        foreach($zNet as $maks=>$none){
            $total++;
            $rows[]=array(
                "id"=>md5($maks),
                "cidr"=>$maks,
                "enc"=>urlencode($maks),
                "enabled"=>1,
                "trusted"=>1,
                "dns"=>1,
                "vpn"=>0,
                "netinfos"=>$maks,
                "meta"=>array(),
                "nmap"=>0,
                "pingable"=>0,
                "prcping"=>0,
                "editable"=>false,
            );
        }
    }

    $EnableARPScanner=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableARPScanner"));
    $ARPScannerSeen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARPScannerSeen"));
    if($ARPScannerSeen==0){$EnableARPScanner=1;}

    $html=array();
    $html[]="<div class='nwl-toolbar'>";
    $html[]=awc_search_bar("nwl-q","{nwl_filter_placeholder}");
    $html[]="</div>";

    $html[]="<div class='nwl-card'><div class='nwl-scroll'>";
    $html[]="<table class='nwl-tbl'><thead><tr>";
    $html[]="<th class='nwl-c' style='width:1%'>{enabled}</th>";
    $html[]="<th class='nwl-th-main'>{nwl_subnet_mask}</th>";
    $html[]="<th>{nwl_network_name}</th>";
    $html[]="<th class='nwl-c'>{trusted_network}</th>";
    if($SHOW_VPN){
        $html[]="<th class='nwl-c'>{vpn_allowed}</th>";
    }
    $html[]="<th class='nwl-c'>DNS</th>";
    $html[]="<th class='nwl-r'>{actions}</th>";
    $html[]="</tr></thead><tbody id='nwl-rows'>";

    $users=new usersMenus();
    $canEdit=$users->AsFirewallManager;
    foreach($rows as $row){
        $trCls=$row["enabled"]?"":" class='nwl-off'";
        $html[]="<tr id='{$row["id"]}'$trCls>";
        $html[]="<td class='nwl-c'>".nwl_cell_enabled($page,$row["enc"],$row["enabled"],$row["editable"])."</td>";
        $cidrJs=($row["editable"] && $canEdit)?"Loadjs('$page?net-js={$row["enc"]}')":null;
        $html[]="<td>".nwl_cell_cidr($row["cidr"],$cidrJs)."</td>";
        $html[]="<td>".nwl_cell_name($row["netinfos"],$row["meta"])."</td>";
        $trustedJs=$row["editable"]?"Loadjs('$page?enabled-trusted={$row["enc"]}')":null;
        $html[]="<td class='nwl-c'>".nwl_pill($row["trusted"],$trustedJs)."</td>";
        if($SHOW_VPN){
            $html[]="<td class='nwl-c'>".nwl_pill($row["vpn"]==1,null,$row["vpn"]==0)."</td>";
        }
        $html[]="<td class='nwl-c'>".nwl_pill($row["dns"]==1,null,$row["dns"]==0)."</td>";
        $html[]="<td class='nwl-r'>".nwl_cell_actions($page,$row["enc"],$row["id"],
            $row["nmap"],$row["pingable"],$row["prcping"],$row["editable"])."</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody></table></div>";
    $html[]="<div class='nwl-foot'>";
    $html[]="<span>".str_replace("%n",(string)$total,$tpl->_ENGINE_parse_body("{nwl_showing}"))."</span>";
    $html[]="<span class='awc-pg' id='nwl-pager'></span>";
    $html[]="</div></div>";

    $html[]="<div class='nwl-cards'>";
    $html[]=nwl_card_ids($scanned,$total);
    $html[]=nwl_card_arp($EnableARPScanner==1);
    $html[]=nwl_card_strict();
    $html[]="</div>";

    $html[]="<script>";
    $html[]="Loadjs('$page?tinyjs=yes&function=$function');";
    $html[]="NoSpinner();";
    $html[]="</script>";

    // Pas d'icon_check ici: pas besoin d'ICON_SCRIPTS_COMPILE.
    $js=awc_pager_js(25,"nwl-rows","nwl-pager")
        .awc_filter_js("nwl-q","nwl-rows","nwl-pager");

    echo nwl_css().awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$js;
    return true;
}
function IsFw():bool{
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
    if($FireHolEnable==1){return true;}
    $EnableCrowdSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrowdSec"));
    if($EnableCrowdSec==1){return true;}
    return false;
}

// Réseau saisi : IPv4 (NetworkMasks) ou IPv6 (lot 6b), en CIDR ; "" si
// invalide. Une saisie IPv6 rendait false, et la ligne était enregistrée avec
// une adresse vide.
function fwnet_cidr(string $raw):string{
    $raw=trim($raw);
    if(strpos($raw,":")!==false){
        $parts=explode("/",$raw,2);
        if(isset($parts[1]) && !preg_match('/^\d{1,3}$/',$parts[1])){return "";}
        $bits=isset($parts[1])?intval($parts[1]):128;
        if($bits<1 || $bits>128){return "";}
        if(!filter_var($parts[0],FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)){return "";}
        $bin=inet_pton($parts[0]);
        if($bin===false){return "";}
        $addr=inet_ntop($bin);
        if(strpos($addr,".")!==false){return "";}
        return "$addr/$bits";
    }
    $NetworkMasks=new NetworkMasks();
    $c=$NetworkMasks->networkToCIDR($raw);
    return $c===false?"":(string)$c;
}

// Clé d'une ligne de networks_infos (adresse ou CIDR, IPv4 ou IPv6) : rien
// d'autre ne doit atteindre les requêtes SQL de cette page.
function fwnet_valid(string $v):bool{
    return preg_match('#^[0-9a-fA-F.:]+(/[0-9.]{1,15})?$#',$v)===1;
}
