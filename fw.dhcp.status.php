<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpd.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["range1"])){dhcp_save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["config-file-js"])){config_file_js();exit;}
if(isset($_GET["config-file-popup"])){config_file_popup();exit;}
if(isset($_POST["configfile"])){config_file_save();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$DHCPDVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDVersion");

    $html=$tpl->page_header("{APP_DHCP} v$DHCPDVersion",ico_computer_down,"{EnableDHCPServer_text}","$page?table=yes","dhcp-status",
        "progress-dhcp-restart",false,"table-loader-dhcp-service");
	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_DHCP} v$DHCPDVersion",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function table():bool{
	$tpl=new template_admin();
    $page=CurrentPageName();
    $dhcpd_free=null;
    $DHCPD_POOLS_JSON=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_POOLS_JSON");
    if(!function_exists("json_decode")){
        echo $tpl->div_error("{missing_php_modules} json_decode");
        $DHCPD_POOLS_JSON=null;
    }
	if(strlen($DHCPD_POOLS_JSON)>10){
        $json=json_decode($DHCPD_POOLS_JSON);
        $DHCPD_COUNT_OF_QUERIES=FormatNumber($json->summary->defined);
        $dhcpd_leases=FormatNumber($json->summary->used);
        $dhcpd_free=FormatNumber($json->summary->free);
    }else{
        $q=new postgres_sql();
        $dhcpd_leases=$q->COUNT_ROWS_LOW("dhcpd_leases");
        $DHCPD_COUNT_OF_QUERIES=FormatNumber($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_COUNT_OF_QUERIES"));

    }

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:220px;vertical-align:top'><div id='dhcp-service'></div></td>";
    $html[]="<td style='width:78%;vertical-align:top'>";
    $html[]="<div class=\"col-lg-4\">
	<!-- -------------------------------------------------------------------------------------------------- -->
	<div class=\"widget style1 lazur-bg\">
	<div class=\"row\">
	<div class=\"col-xs-4\">
	<i class=\"fa fa-desktop fa-5x\"></i>
	</div>
	<div class=\"col-xs-8 text-right\">
	<span> {computers_number} </span>
	<h2 class=\"font-bold\">$DHCPD_COUNT_OF_QUERIES</h2>
	</div>
	</div>
	</div>
	</div>
	<!-- -------------------------------------------------------------------------------------------------- -->
	
	<!-- ------------------------------------------------------------------------------------------------- -->
	<div class=\"col-lg-4\">
	    <div class=\"widget style1 navy-bg\">
	        <div class=\"row\">
	            <div class=\"col-xs-4\">
	                <i class=\"fas fa-laptop fa-5x\"></i>
	            </div>
	            <div class=\"col-xs-8 text-right\">
	                <span> {leases} </span>
	                <h2 class=\"font-bold\">$dhcpd_leases</h2>
	            </div>
	        </div>
	    </div>
    </div>
	<!-- -------------------------------------------------------------------------------------------------- -->
	";

	if($dhcpd_free<>null){
        $html[]="	<div class=\"col-lg-4\">
	    <div class=\"widget style1 navy-bg\">
	        <div class=\"row\">
	            <div class=\"col-xs-4\">
	                <i class=\"fas fa-pause fa-5x\"></i>
	            </div>
	            <div class=\"col-xs-8 text-right\">
	                <span> {free} IP</span>
	                <h2 class=\"font-bold\">$dhcpd_free</h2>
	            </div>
	        </div>
	    </div>
    </div>
	<!-- -------------------------------------------------------------------------------------------------- -->
	";

    }
	
	$t=time();

    $href="Loadjs('fw.rrd.php?img=dhcp3');";

    if(is_file("img/squid/dhcp3-dashboard-hourly.micro.png")) {
        $html[] = "<table style='width:100%'>";
        $html[] = "<tr>";
        $html[] = "<td style='width:50%;vertical-align:top'>";
        $html[] = "<div style='margin-top:10px;padding:5px;' class='center'>";
        $html[]=$tpl->td_href("<img src='img/squid/dhcp3-dashboard-hourly.micro.png?$t'>","",$href);
        $html[]="</div>";
        $html[]="</td>";
        $html[] = "<td style='width:50%;vertical-align:top'>";
        $html[] = "<div style='margin-top:10px;padding:5px;' class='center'>";
        $html[]=$tpl->td_href("<img src='img/squid/dhcp3-dashboard-yesterday.micro.png?$t'>","",$href);
        $html[]="</div>";
        $html[]="</td>";
        $html[]="</tr>";
        $html[]="</table>";
    }
	
	


    $DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    if($DisablePostGres==1){
        $installjs=$tpl->framework_buildjs(
            "/postgresql/install","postgres.progress","postgres.log","progress-dhcp-restart",
            "LoadAjax('dhcp-status','$page?table=yes')"
        );

        $btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
        $install="<div style='text-align:right;margin-top:20px'>$btn</div>";

        $html[]=$tpl->div_warning("{APP_POSTGRES} {missing}||{need_postgresql_1}<hr>$install");
    }

	$html[]="</div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('dhcp-service','$page?status=yes');";
    $html[]="</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	return true;
}

function dhcp_save():bool{
	$sock=new sockets();
	$tpl=new template_admin();
    $tpl->CLEAN_POST();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$DisableNetworksManagement=$sock->GET_INFO("DisableNetworksManagement");
	if($DisableNetworksManagement==null){$DisableNetworksManagement=0;}
	if($DisableNetworksManagement==1){echo $ERROR_NO_PRIVS;return false;}
	$IPClass=new IP();
	
	$dhcp=new dhcpd(0,1);
	$sock=new sockets();
	
	$sock->SET_INFO('EnableDHCPUseHostnameOnFixed',$_POST["EnableDHCPUseHostnameOnFixed"]);
	$sock->SET_INFO("IncludeDHCPLdapDatabase", $_POST["IncludeDHCPLdapDatabase"]);

	$dhcp->listen_nic=$_POST["listen_nic"];
	$dhcp->deny_unkown_clients=$_POST["deny_unkown_clients"];
	$dhcp->ddns_domainname=$_POST["ddns_domainname"];
	$dhcp->max_lease_time=$_POST["max_lease_time"];
	$dhcp->get_lease_hostnames=$_POST["get_lease_hostnames"];
	$dhcp->netmask=$_POST["netmask"];
	$dhcp->range1=$_POST["range1"];
	$dhcp->range2=$_POST["range2"];
	$dhcp->subnet=$_POST["subnet"];
	$dhcp->broadcast=$_POST["broadcast"];
	$dhcp->WINS=$_POST["WINS"];
	$dhcp->ping_check=$_POST["DHCPPing_check"];
	$dhcp->authoritative=$_POST["DHCPauthoritative"];
	$dhcp->local_pac_server=$_POST["local-pac-server"];
	$dhcp->browser_portal_page=$_POST["browser-portal-page"];
	$dhcp->gateway=$_POST["gateway"];
	$dhcp->DNS_1=$_POST["DNS_1"];
	$dhcp->DNS_2=$_POST["DNS_2"];
	$dhcp->ntp_server=$_POST["ntp_server"];
	
	$dhcp->EnableArticaAsDNSFirst=$_POST["EnableArticaAsDNSFirst"];
	$dhcp->do_no_verify_range=$_POST["do_no_verify_range"];
	
	$MASK=$IPClass->maskTocdir($dhcp->subnet, $dhcp->netmask);
	
	if(!$IPClass->isInRange($dhcp->range1, "$MASK")){
		echo "$dhcp->subnet/$dhcp->netmask = $MASK\n$dhcp->range1 invalid for $MASK\n";
		return false;
	}
	if(!$IPClass->isInRange($dhcp->range2, "$MASK")){
	echo "$dhcp->subnet/$dhcp->netmask = $MASK\n$dhcp->range1 invalid for $MASK\n";
	return false;
	}
	if($dhcp->ddns_domainname==null){$dhcp->ddns_domainname="home.lan";}
	$dhcp->Save();
    return true;
}


function status(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new template_admin();

    $data = $sock->REST_API("/dhcpd/service/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>$sock->mysql_error","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>$sock->mysql_error","{error}"));
        return true;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);



    $jsrestart=$tpl->framework_buildjs("/dhcpd/service/restart",
        "dhcpd.progress","dhcpd.progress.txt","progress-dhcp-restart");


	$btn_config=$tpl->button_autnonome("{config_file}", "Loadjs('$page?config-file-js=yes')", "fas fa-file-code","AsSystemAdministrator",335);
	echo $tpl->SERVICE_STATUS($ini, "APP_DHCP",$jsrestart).$tpl->_ENGINE_parse_body($btn_config);
	
	
}

function config_file_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();return;}
	$tpl->js_dialog6("{APP_DHCP} >> {config_file}", "$page?config-file-popup=yes",1000);

}
function config_file_save(){

	$data=url_decode_special_tool($_POST["configfile"]);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/dhcpd.config", $data);
}

function config_file_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$sock->getFrameWork("dhcpd.php?config-file=yes");
	$data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/dhcpd.config");
	$form[]=$tpl->field_textareacode("configfile", null, $data);

    $jsafter=$tpl->framework_buildjs("/dhcpd/service/restart",
        "dhcpd.progress","dhcpd.progress.txt","progress-dhcp-restart");


	echo $tpl->form_outside("{config_file}", @implode("", $form),null,"{apply}",$jsafter,"AsSystemAdministrator");

}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}