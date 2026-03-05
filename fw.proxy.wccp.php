<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["parameters-table"])){parameters_table();exit;}
if(isset($_GET["parameters-flat"])){parameters_flat();exit;}
if(isset($_POST["WCCP_HTTP_PORT"])){saveall();exit;}
if(isset($_POST["WCCP_ASA_ADDR"])){saveRestart();exit;}

if(isset($_GET["section-traffic-js"])){section_traffic_js();exit;}
if(isset($_GET["section-traffic-popup"])){section_traffic_popup();exit;}

if(isset($_GET["section-proto-js"])){section_proto_js();exit;}
if(isset($_GET["section-proto-popup"])){section_proto_popup();exit;}

parameters();

function saveall(){
    $tpl    =new template_admin();
    $WCCP_ASA_ADDR=$_POST["WCCP_ASA_ADDR"];
    $WCCP_ASA_ROUTER=$_POST["WCCP_ASA_ROUTER"];
    if(isset($_POST["WCCP_ASA_USE"])) {
        $WCCP_ASA_USE = intval($_POST["WCCP_ASA_USE"]);
        if ($WCCP_ASA_USE == 1) {
            if ($WCCP_ASA_ADDR == $WCCP_ASA_ROUTER) {
                echo "jserror:" . $tpl->javascript_parse_text("{WCCP_ASA_ROUTER_ERR1}");
            }
        }
    }

    $tpl->SAVE_POSTs();
}
function section_traffic_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{listen}","$page?section-traffic-popup=true");
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
    //$form[]=$tpl->field_array_hash($zwccp_version,"WCCP_VERSION","{version}: WCCP",$WCCP_VERSION);
    //$form[]=$tpl->field_checkbox("WCCP_ASA_USE","CISCO ASA",$WCCP_ASA_USE,"WCCP_ASA_ROUTER");
    //$form[]=$tpl->field_ipaddr("WCCP_ASA_ROUTER","{cisco_asa_address}",$WCCP_ASA_ROUTER);
    $form[]=$tpl->field_password("WCCP_PASSWORD","{WCCP_PASSWORD}",$WCCP_PASSWORD,false,"{WCCP_PASSWORD_EXPLAIN}");
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",jsApply(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function saveRestart():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/wccp/restart");
    return admin_tracks_post("Saving WCCP parameters.");

}

function section_traffic_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";

    $WCCP_HTTP_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTP_PORT"));
    $WCCP_HTTPS_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTPS_PORT"));
    $WCCP_LOCAL_INTERFACE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_LOCAL_INTERFACE"));
    $WCCP_CERTIFICATE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_CERTIFICATE"));
    $WCCP_HTTPS_SERVICE_ID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTPS_SERVICE_ID"));

    if($WCCP_HTTP_PORT==0){$WCCP_HTTP_PORT=35026;}
    if($WCCP_HTTPS_PORT==0){$WCCP_HTTPS_PORT=35025;}
    if($WCCP_HTTPS_SERVICE_ID==0){$WCCP_HTTPS_SERVICE_ID=70;}

    $form[]=$tpl->field_interfaces("WCCP_LOCAL_INTERFACE","nooloopNoDef:{listen_interface}",$WCCP_LOCAL_INTERFACE);
    $form[]=$tpl->field_section("HTTP");
    $form[]=$tpl->field_numeric("WCCP_HTTP_PORT","{listen_port} HTTP",$WCCP_HTTP_PORT);
    $form[]=$tpl->field_section("HTTPS");
    $form[]=$tpl->field_numeric("WCCP_HTTPS_PORT","{listen_port} HTTPs",$WCCP_HTTPS_PORT);
    $form[]=$tpl->field_numeric("WCCP_HTTPS_SERVICE_ID","{service_id}",$WCCP_HTTPS_SERVICE_ID);
    $form[]=$tpl->field_certificate("WCCP_CERTIFICATE","{use_certificate_from_certificate_center}",$WCCP_CERTIFICATE);

    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",jsApply(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}

function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $SquidWCCPEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWCCPEnabled"));
    if($SquidWCCPEnabled==0){
        echo $tpl->_ENGINE_parse_body($tpl->widget_grey("{WCCP_NAME}","{feature_disabled}"));
        return true;
    }
    $WCCP_ASA_ADDR=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_ASA_ADDR"));

    if(strlen($WCCP_ASA_ADDR)<2){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("{no_lan_router_addr}","{missing_parameter}"));
        return true;
    }

    echo "<script>LoadAjaxSilent('wccp-params','$page?parameters-flat=yes');</script>";


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/wccp/status"));

    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge($json->Error,"{error}"));
        return true;
    }

    $jsrestart=$tpl->framework_buildjs("/proxy/wccp/apply",
        "squid.wccp.progress",
            "squid.wccp.log",
        "progress-squid-ports-restart","LoadAjaxSilent('wccp-params','$page?parameters-flat=yes');");

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $html[]=$tpl->SERVICE_STATUS($ini, "WCCP_NAME",$jsrestart);

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}


function parameters(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align: top'><div id='wccp-status'></div></td>";
    $html[]="<td style='width:99%;vertical-align: top;padding-left:10px'><div id='wccp-params'></div></td>";
    $html[]="</tr>";
    $html[]="<script>";
    $js=$tpl->RefreshInterval_js("wccp-status",$page,"status=yes");

    $html[]="$js";
    $html[]="LoadAjaxSilent('wccp-params','$page?parameters-flat=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function parameters_flat():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";

    $topbuttons=array();
    $TINY_ARRAY["TITLE"]="{WCCP_NAME}";
    $TINY_ARRAY["ICO"]=ico_sensor;
    $TINY_ARRAY["EXPL"]="{WCCP_NAME_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $SquidWCCPEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWCCPEnabled"));


    if($SquidWCCPEnabled==0){
        $after=$tpl->framework_buildjs("/proxy/wccp/install",
        "squid.wccp.progress",
        "squid.wccp.log",
        "progress-squid-ports-restart",
        "LoadAjaxSilent('wccp-params','$page?parameters-flat=yes');"
        );

        $html[]=$tpl->BigCircleCheckbox("SquidWCCPEnabled","{WCCP_NAME}","{WCCP_NAME_EXPLAIN}",0,$after);
        echo $tpl->_ENGINE_parse_body($html);
        echo "<script>$jstiny</script>";
        return true;
    }

    $after=$tpl->framework_buildjs("/proxy/wccp/install",
        "squid.wccp.progress",
        "squid.wccp.log",
        "progress-squid-ports-restart","LoadAjaxSilent('wccp-params','$page?parameters-flat=yes');");

    $WCCP_ASA_ROUTER=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_ASA_ROUTER"));
    $WCCP_ASA_ADDR=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_ASA_ADDR"));
    $WCCP_PASSWORD=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_PASSWORD"));
    $WCCP_ASA_USE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_ASA_USE"));
    $WCCP_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_VERSION"));

    $html[]=$tpl->BigCircleCheckbox("SquidWCCPEnabled","{WCCP_NAME}","{feature_enabled}",1,$after);

    $tpl->table_form_section("{router}");
    $tpl->table_form_field_js("Loadjs('$page?section-proto-js=yes')",$security);
    if(strlen($WCCP_ASA_ADDR)<4){
        $tpl->table_form_field_text("{router_address}","{unknown}",ico_sensor,true);
    }else{
        $tpl->table_form_field_text("{router_address}",$WCCP_ASA_ADDR." WCCPv2",ico_sensor);
    }


    $tpl->table_form_section("{http_traffic}");
    $tpl->table_form_field_js("Loadjs('$page?section-traffic-js=yes')",$security);

    $WCCP_HTTP_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTP_PORT"));
    $WCCP_LOCAL_INTERFACE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_LOCAL_INTERFACE"));
    $WCCP_HTTPS_SERVICE_ID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTPS_SERVICE_ID"));
    $WCCP_HTTPS_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_HTTPS_PORT"));
    $WCCP_CERTIFICATE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WCCP_CERTIFICATE"));
    if($WCCP_HTTP_PORT==0){$WCCP_HTTP_PORT=35026;}
    if($WCCP_HTTPS_PORT==0){$WCCP_HTTPS_PORT=35025;}

    if($WCCP_HTTPS_SERVICE_ID==0){$WCCP_HTTPS_SERVICE_ID=70;}
    $tpl->table_form_field_text("{listen}","127.0.0.1:$WCCP_HTTP_PORT",ico_nic);




    $tpl->table_form_section("{https_traffic}");
    $tpl->table_form_field_text("{service_id}",$WCCP_HTTPS_SERVICE_ID,ico_nic);
    $tpl->table_form_field_text("{listen}", "127.0.0.1:$WCCP_HTTPS_PORT", ico_nic);

    if(strlen($WCCP_CERTIFICATE)<3){
        $tpl->table_form_field_text("{certificate}","{unknown}",ico_certificate,true);
    }else{
        $tpl->table_form_field_text("{certificate}",$WCCP_CERTIFICATE,ico_certificate);
    }


    if($WCCP_ASA_USE==0){$WCCP_ASA_ROUTER=null;}




    $tpl->table_form_field_bool("CISCO ASA",$WCCP_ASA_USE,ico_proto);
    if($WCCP_ASA_USE==1){
        if(strlen($WCCP_ASA_ROUTER)<3) {
            $tpl->table_form_field_text("{cisco_asa_address}", "{unknown}", ico_sensor,true);
        }else{
            $tpl->table_form_field_text("{cisco_asa_address}", $WCCP_ASA_ROUTER, ico_sensor);
        }
    }
    if(strlen($WCCP_PASSWORD)>1){
        $tpl->table_form_field_text("{WCCP_PASSWORD}","****",ico_lock);
    }




    $html[]= $tpl->table_form_compile();
    $html[]= "<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function jsApply():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $js[]="LoadAjaxSilent('wccp-params','$page?parameters-flat=yes');";
    $js[]="dialogInstance2.close();";
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

function connected_port_save(){
		$tpl=new template_admin();
		$tpl->CLEAN_POST();
		
		$ID=$_POST["SaveConnectedPort"];
		$ipaddr=$_POST["ipaddr"];
		$port=intval($_POST["port"]);
		$xnote=mysql_escape_string2($_POST["xnote"]);
		$PortName=mysql_escape_string2($_POST["PortName"]);
		$enabled=$_POST["enabled"];
		$transparent=intval($_POST["transparent"]);
		$TProxy=intval($_POST["TProxy"]);
		$Parent=intval($_POST["Parent"]);
		$outgoing_addr=$_POST["outgoing_addr"];
		$UseSSL=$_POST["UseSSL"];
		$sslcertificate=$_POST["sslcertificate"];
		$WCCP=intval($_POST["WCCP"]);
		$ICP=intval($_POST["ICP"]);
		$FTP=intval($_POST["FTP"]);
		$WANPROXY=intval($_POST["WANPROXY"]);
		$WANPROXY_PORT=intval($_POST["WANPROXY_PORT"]);
		$FTP_TRANSPARENT=intval($_POST["FTP_TRANSPARENT"]);
		$MIKROTIK_PORT=intval($_POST["MIKROTIK_PORT"]);
		$SOCKS=intval($_POST["SOCKS"]);
		$NoAuth=intval($_POST["NoAuth"]);

	
		$NoCache=intval($_POST["NoCache"]);
		$NoFilter=intval($_POST["NoFilter"]);
	
	
		$ProxyProtocol=intval($_POST["ProxyProtocol"]);
		$nic=$_POST["nic"];

		$SquidAllow80Port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAllow80Port"));
		$tpl=new templates();
		$SquidMgrListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
	
		if($port==3401){echo "3401, reserved port\n";return;}
		if($port==3978){echo "3978, reserved port\n";return;}
		if($port==3977){echo "3977, reserved port\n";return;}
		if($port==8889){echo "$port Internal Framework port not allowed!\n";return;}
		if($port==8888){echo "$port Internal Framework port not allowed!\n";return;}
		if($port==9000){echo "$port Artica Web Console port not allowed!\n";return;}
	
		if($port>65535){$port=65535;}
		if($WANPROXY_PORT>65635){$WANPROXY_PORT=65635;}
		if($MIKROTIK_PORT>65635){$MIKROTIK_PORT=65635;}
	
		if($MIKROTIK_PORT==1){
			$FTP=0;
			$transparent=0;
			$TProxy=0;
			$WCCP=0;
			$Parent=0;
			$FTP_TRANSPARENT=0;
			$_POST["is_nat"]=0;
			if($nic==null){
				echo $tpl->javascript_parse_text("{mikrotik_error_interface}");
				return;
			}
		}
	
		if($FTP_TRANSPARENT==1){
			$transparent=0;$TProxy=0;$WCCP=0;$Parent=0;$ICP=0;$FTP=0;
			if(!is_file("/usr/sbin/frox")){
				echo $tpl->javascript_parse_text("{module_in_squid_not_installed}");
				return;
					
			}
	
			if($WANPROXY_PORT==0){
				echo $tpl->javascript_parse_text("{error} {missing_valuefor}: {proxy_port}");
				return;
			}
	
		}
	
	
	
		if($FTP==1){
			$transparent=0;
			$TProxy=0;
			$WCCP=0;
			$Parent=0;
			$FTP_TRANSPARENT=0;
			$sslcertificate=null;
			$UseSSL=0;
			$nic=null;
			$outgoing_addr=null;
			$SquidAllow80Port=1;
			$_POST["is_nat"]=0;
			$ICP=0;
		}
	
	
	
		if($SquidAllow80Port==0){
			if($port==80){
				echo "$port 80 HTTP port not allowed!\n";
				return;
			}
	
			if($port==21){
				echo "$port 21 FTP port not allowed!\n";
				return;
			}
	
			if($port==443){
				echo "$port 443 SSL port not allowed!\n";
				return;
			}
		}
	
	
	
	
		if($port==$SquidMgrListenPort){
			echo "$port == Manager port $SquidMgrListenPort (not allowed!)\n";
			return;
	
		}
	
	
	
		if(intval($_POST["is_nat"])==1){
			$transparent=0;
			$TProxy=0;
			$WCCP=0;
			$WANPROXY_PORT=0;
			$FTP_TRANSPARENT=0;
		}
	
		if($ICP==1){
			$transparent=0;
			$TProxy=0;
			$WCCP=0;
			$Parent=0;
			$sslcertificate=null;
			$UseSSL=0;
			$nic=null;
			$outgoing_addr=null;
			$WANPROXY_PORT=0;
			$FTP_TRANSPARENT=0;
		}
	
		if($UseSSL==1){
			$ProxyProtocol=0;
		}
	
	
	
		if($WCCP==1){
			$WANPROXY_PORT=0;
			if($nic==null){
				echo $tpl->javascript_parse_text("{wccp_error_interface}");
				return;
			}
	
		}
	
	
		$is_nat=intval($_POST["is_nat"]);
		$zMD5=md5(serialize($_POST));
		$sqladd="INSERT INTO proxy_ports (WANPROXY_PORT,WANPROXY,FTP,ICP,Parent,WCCP,is_nat,nic,ipaddr,port,xnote,enabled,transparent,TProxy,outgoing_addr,PortName,UseSSL,sslcertificate,NoAuth,MIKROTIK_PORT,FTP_TRANSPARENT,SOCKS,ProxyProtocol,NoCache,NoFilter,zMD5,AuthForced,AuthPort,NoHotspot)
		VALUES ('$WANPROXY_PORT','$WANPROXY','$FTP','$ICP','$Parent','$WCCP','$is_nat','$nic','$ipaddr','$port','$xnote','$enabled','$transparent','$TProxy','$outgoing_addr','$PortName','$UseSSL','$sslcertificate',$NoAuth,'$MIKROTIK_PORT','$FTP_TRANSPARENT','$SOCKS','$ProxyProtocol',$NoCache,$NoFilter,'$zMD5',0,0,0)";
	
	
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");

		if(!$q->FIELD_EXISTS("proxy_ports","NoHotspot")){
            $q->QUERY_SQL("ALTER TABLE proxy_ports ADD NoHotspot INTEGER NOT NULL DEFAULT '0'");
        }




	
		if($ID==0){
	
			$sql="SELECT ID,PortName FROM proxy_ports WHERE port='$port'";
			$ligne=$q->mysqli_fetch_array($sql);
			$ID=intval($ligne["ID"]);
	
			if($ID>0){
				$PortName=$ligne["PortName"];
				echo $tpl->javascript_parse_text("$port: {error_port_already_usedby}:$PortName");
				return;
			}
	
		}
	
	
		$sqledit="UPDATE proxy_ports SET
		WANPROXY_PORT='$WANPROXY_PORT',
		FTP_TRANSPARENT='$FTP_TRANSPARENT',
		WANPROXY='$WANPROXY',
		FTP='$FTP',
		ICP='$ICP',
		Parent='$Parent',
		TProxy='$TProxy',
		WCCP='$WCCP',
		is_nat='$is_nat',
		transparent='$transparent',
		ipaddr='$ipaddr',
		port='$port',
		nic='$nic',
		xnote='$xnote',
		MIKROTIK_PORT='$MIKROTIK_PORT',
		enabled='$enabled',nic='$nic',`is_nat`='$is_nat',
		outgoing_addr='$outgoing_addr',
		PortName='$PortName',
		SOCKS='$SOCKS',
		UseSSL='$UseSSL',
		sslcertificate='$sslcertificate',
		ProxyProtocol='$ProxyProtocol',
		`NoAuth`='$NoAuth', `NoCache`='$NoCache', `NoFilter`='$NoFilter',`NoHotspot`='0'
		WHERE ID=$ID";
	
	
		
	
		$sock=new sockets();
		$InfluxAdminPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminPort"));
		$EnableUnifiController=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminPort"));
		if($InfluxAdminPort==0){$InfluxAdminPort=8083;}
	
		if($port==$InfluxAdminPort){echo "Failed, reserved port Influx Admin Port\n";return;}
		if($port==9900){echo "Failed, reserved port WanProxy Monitor\n";return;}
		if($port==5432){echo "Failed, reserved port PostgreSQL query port\n";return;}
		if($EnableUnifiController==1){if($port==8088){echo "Failed, reserved port Unifi HTTP Port\n";return;}}
		if($port==13298){echo "Failed, reserved port Proxy NAT backend port\n";return;}
		if($SquidAllow80Port==0){if($port==21){echo "Failed, reserved port Local FTP service port\n";return;}}
		if($port==25){echo "Failed, reserved port Local SMTP service port\n";return;}
		if($port==31337){echo "Failed, reserved port Local RedSocks\n";return;}
	
	
	
		if($ID>0){$q->QUERY_SQL($sqledit);}else{$q->QUERY_SQL($sqladd);}
		if(!$q->ok){echo $q->mysql_error;}else{echo $q->last_id;}
		CheckPointers();
	
	
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

    $html=$tpl->page_header("WCCP","fas fa-router",
        "{WCCP_NAME_EXPLAIN}","$page?tabs=yes","wccp","progress-wccp-restart",false,"table-loader-proxy-wccp");


	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function connected_port_section(){
	$page=CurrentPageName();
	echo "<div id='table-connected-proxy-ports'></div><script>LoadAjax('table-connected-proxy-ports','$page?connected-ports-list=yes');</script>";
}