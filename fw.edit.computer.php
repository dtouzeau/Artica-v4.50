<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.hosts.inc");
if(isset($_GET["computer-dhcp"])){computer_dhcp_js();exit;}
if(isset($_GET["computer-dhcp-popup"])){computer_dhcp_popup();exit;}
if(isset($_GET["computer-identity"])){computer_identity_js();exit;}
if(isset($_GET["computer-identity-popup"])){computer_identity_popup();exit;}


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["gateway"])){host_edit();exit;}
if(isset($_POST["mac"])){save();exit;}
if(isset($_GET["settings"])){settings_start();exit;}
if(isset($_GET["idFlat"])){settings_flat();exit;}
if(isset($_GET["remove"])){remove_js();exit;}
if(isset($_POST["remove"])){remove_perform();exit;}
if(isset($_GET["dhcp"])){computer_dhcp_popup();exit;}
if(isset($_GET["infos"])){settings_infos();exit;}
if(isset($_GET["infos2"])){settings_infos2();exit;}
if(isset($_GET["scanreport"])){scanreport();exit;}

js();


function remove_js():bool{
	$tpl=new template_admin();
	$CallBackFunction=CallBack();
    if(strlen($CallBackFunction)>3){
        $CallBackFunction="$CallBackFunction()";
    }

	return $tpl->js_confirm_delete($_GET["remove"],"remove",$_GET["remove"],"dialogInstance3.close();$CallBackFunction");
		
}
function CallBack():string{

    $tpl=new template_admin();
    $CallBackFunctionValue=$_GET["CallBackFunction"];
    $CallBackFunction=$CallBackFunctionValue;
    if($tpl->IsBase64($CallBackFunctionValue)){
        $CallBackFunction=trim(base64_decode($_GET["CallBackFunction"]));
    }



    $CallBackFunction_text="blur()";
    if($CallBackFunction<>null){

        if(preg_match("#=$#",$CallBackFunction)){
            $CallBackFunction=base64_decode($CallBackFunction);
        }

        if(strpos($CallBackFunction,")")==0){
            $CallBackFunction_text="$CallBackFunction()";
        }else{
            $CallBackFunction_text=$CallBackFunction;
        }
    }
    if($CallBackFunction_text=="()"){$CallBackFunction_text=null;}
    return str_replace("()","",$CallBackFunction_text);;
}

function remove_perform():bool{
	$mac=$_POST["remove"];
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM hostsnet WHERE mac='$mac'");
	$q->QUERY_SQL("DELETE FROM dhcpd_hosts WHERE mac='$mac'");
	$q->QUERY_SQL("DELETE FROM dhcpd_leases WHERE mac='$mac'");
    return admin_tracks("Remove computer $mac from database");
}

function computer_identity_js():bool{
    $url=buildurl();
    $mac=$_GET["mac"];
    $tpl=new template_admin();
    return $tpl->js_dialog4("{computer} $mac","$url&computer-identity-popup=yes",550);
}
function computer_dhcp_js():bool{
    $url=buildurl();
    $mac=$_GET["mac"];
    $tpl=new template_admin();
    return $tpl->js_dialog4("{computer} >> DHCP >> $mac","$url&computer-dhcp-popup=yes",550);
}


function js():bool{
	
	$prepend_ip=null;$ByProxy=null;
	$mac2=urlencode($_GET["mac"]);
	$tpl=new template_admin();
	$host=new hosts($_GET["mac"]);
	$CallBackFunction=urlencode($_GET["CallBackFunction"]);
	if(isset($_GET["prependip"])){$prepend_ip="&prependip={$_GET["prependip"]}";}
	if(isset($_GET["ByProxy"])){$ByProxy="&ByProxy=yes";}
	
	
	$title=$tpl->javascript_parse_text("{computer} {$_GET["mac"]} $host->hostname");
	$page=CurrentPageName();
	$tpl->js_dialog3($title, "$page?tabs=yes&mac=$mac2$prepend_ip$ByProxy&CallBackFunction=$CallBackFunction");
	return true;
}

function tabs(){
    $tpl=new template_admin();
    $tpl->CLEAN_GET_XSS();
    $EnableDHCPServer=0;
    $users=new usersMenus();
    $dhcp_installed=$users->dhcp_installed;
    if($users->ASDCHPAdmin) {
        $EnableDHCPServer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
        $EnableKEA = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
        if ($EnableKEA == 1) {
            $EnableDHCPServer = 1;
            $dhcp_installed = true;
        }
    }
	$page=CurrentPageName();

	$prepend_ip=null;$ByProxy=null;
	$host=new hosts($_GET["mac"]);
	$mac=urlencode($_GET["mac"]);
	$CallBackFunction=urlencode($_GET["CallBackFunction"]);
	if(isset($_GET["prependip"])){$prepend_ip="&prependip={$_GET["prependip"]}";}
	if(isset($_GET["ByProxy"])){$ByProxy="&ByProxy=yes";}

	
	$array[$host->hostname]="$page?settings=yes&mac=$mac$prepend_ip$ByProxy&CallBackFunction=$CallBackFunction";
	
    if($host->scanreport<>null){$array["{scan_report}"]="$page?scanreport=yes&mac=$mac&CallBackFunction=$CallBackFunction";}
    echo $tpl->tabs_default($array);
}

function buildurl(){
    $prepend_ip="";
    $ByProxy="";
    $macenc=urlencode($_GET["mac"]);
    $page=currentPageName();
    if(isset($_GET["prependip"])){$prepend_ip="{$_GET["prependip"]}";}
    $CallBackFunction=base64_encode("blur()");
    if(isset($_GET["CallBackFunction"])) {
        $CallBackFunction =trim($_GET["CallBackFunction"]);
        if(strlen($CallBackFunction)<2){
            $CallBackFunction=base64_encode("blur()");
        }

    }

    $idFlat="";

    if(isset($_GET["ByProxy"])){
        $ByProxy="&ByProxy=yes";

    }
    if(isset($_GET["idFlat"])){
        $idFlat="&idFlat=".$_GET["idFlat"];
    }
    return "$page?mac=$macenc&prependip=$prepend_ip$ByProxy&CallBackFunction=$CallBackFunction$idFlat";
}

function settings_start(){
    $macenc=urlencode($_GET["mac"]);
    $ByProxy="";
    $ByProxyToken="";

    if(isset($_GET["ByProxy"])){
        $ByProxy=true;
        $ByProxyToken="&ByProxy=yes";

    }
    $_GET["idFlat"]=md5($macenc.$ByProxy.$ByProxyToken);
    echo "<div id='{$_GET["idFlat"]}'></div>";
    $url=buildurl();
    echo "<script>LoadAjaxSilent('{$_GET["idFlat"]}','$url');</script>";

}

function computer_identity_popup():bool{
    $host=new hosts($_GET["mac"]);
    $tpl=new template_admin();
    $form[]=$tpl->field_hidden("mac",$_GET["mac"]);
    $form[]=$tpl->field_text("hostname", "{hostname}", $host->hostname,true);
    $form[]=$tpl->field_text("domainname","{domain}",$host->domainname);
    $form[]=$tpl->field_ipaddr("ipaddr", "{ipaddr}", $host->ipaddr,true);
    $form[]=$tpl->field_text("proxyalias", "{proxy_alias}", $host->proxyalias,false,"{my_proxy_aliases_text}");
    $form[]=$tpl->field_text("hostalias1", "{alias} 1", $host->hostalias1);
    $form[]=$tpl->field_text("hostalias2", "{alias} 2", $host->hostalias2);
    $form[]=$tpl->field_text("hostalias3", "{alias} 3", $host->hostalias3);
    $form[]=$tpl->field_text("hostalias4", "{alias} 4", $host->hostalias4);

    $url=buildurl();
    $js[]="dialogInstance4.close();";
    $js[]="LoadAjaxSilent('{$_GET["idFlat"]}','$url');";
    $js[]=CallBack()."()";


    $html[]=$tpl->form_outside("",$form,
        "","{apply}",@implode(";",$js),"computer");
    echo $tpl->_ENGINE_parse_body( $html);
return true;

}

function settings_flat(){
    $MacAddress=$_GET["mac"];

    $macenc=urlencode($_GET["mac"]);
    $host=new hosts($_GET["mac"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $explain=null;
    $ByProxyToken=null;
    $prepend_ip=null;
    $add=false;
    $ByProxy=false;
    $js="blur()";
    $EnableDHCPServer=0;
    if(isset($_GET["prependip"])){$prepend_ip="{$_GET["prependip"]}";}
    $DHCPDInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDInstalled"));
    if($DHCPDInstalled==1){$EnableDHCPServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));}
    $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $explainAlias="";
    if(isset($_GET["ByProxy"])){
        $ByProxy=true;
        $explainAlias="{byproxy_explain_alias}";
    }

    VERBOSE("MacAddress=$MacAddress, IP=$host->ipaddr",__LINE__);

    if($host->ipaddr==null){
        if($prepend_ip<>null){$host->ipaddr=$prepend_ip;}
        $tpl->table_form_field_text("mac","mac",$MacAddress);
        $tpl->table_form_field_button("{new_computer}",$MacAddress,ico_plus);
        echo $tpl->table_form_compile();
        return true;
    }

    if(strlen($host->domainname)>2){
        $host->hostname=$host->hostname.".".$host->domainname;
    }

    $url=buildurl();
    $tpl->table_form_field_js("Loadjs('$url&computer-identity=yes')");
    $tpl->table_form_field_text("{hostname}","<span style='text-transform:none'>$host->hostname</span>",ico_computer);
    $tpl->table_form_field_text("{ipaddr}",$host->ipaddr,ico_computer);
    $update=strtotime($host->updated);
    $tpl->table_form_field_text("{UPDATED}",$tpl->time_to_date($update,true),ico_timeout);
    $update=strtotime($host->createdate);
    $tpl->table_form_field_text("{created}",$tpl->time_to_date($update,true),ico_timeout);


    if(!$ByProxy){
        if($EnableDHCPServer==1) {
            $tpl->table_form_field_js("Loadjs('$url&computer-dhcp=yes')");
            $dhcpexpl=array();
            if ($host->dhcpfixed == 1) {
                if(strlen($host->dhcpiface)>2) {
                    $dhcpexpl[] = "{interface}:$host->dhcpiface";
                    }

                if(strlen($host->gateway)>3){
                    if($host->gateway<>"0.0.0.0") {
                        $dhcpexpl[] = "{gateway}:$host->gateway";
                    }
                }
                if(strlen($host->dns1)>3){
                    if($host->dns1<>"0.0.0.0") {
                        $dhcpexpl[] = "{DNSServer}:$host->dns1";
                    }
                }
                if(strlen($host->dns2)>3){
                    if($host->dns2<>"0.0.0.0") {
                        $dhcpexpl[] = "{DNSServer}:$host->dns2";
                    }
                }
                if(count($dhcpexpl)==0){
                    $tpl->table_form_field_bool("{dhcpfixed}", 1, ico_nic);
                }else{
                    $tpl->table_form_field_text("{dhcpfixed}", "<small>".@implode(", ",$dhcpexpl)."</small>", ico_nic);
                }
        }else {
            $tpl->table_form_field_bool("{dhcpfixed}", $host->dhcpfixed, ico_nic);
        }
        }
        if($EnableDNSDist==1){
            $tpl->table_form_field_bool("{dns_entry}", $host->dnsfixed,ico_nic);
        }
    }
    $tpl->table_form_field_js("Loadjs('$url&computer-identity=yes')");
    if($EnableSquidMicroHotSpot==1){
        $tpl->table_form_field_bool("{hotspotwhite}",$host->hotspotwhite,ico_ok);
    }
    if(strlen($explainAlias)){
        $tpl->table_form_section("{APP_PROXY}",$explainAlias);
    }

    if(strlen($host->proxyalias)>1){
        $tpl->table_form_field_text("{proxy_alias}",$host->proxyalias);
    }
    $aliases=array();
    if(strlen($host->hostalias1)>2){
        $aliases[]=$host->hostalias1;
    }
    if(strlen($host->hostalias1)>2){
        $aliases[]=$host->hostalias1;
    }
    if(strlen($host->hostalias2)>2){
        $aliases[]=$host->hostalias2;
    }
    if(strlen($host->hostalias3)>2){
        $aliases[]=$host->hostalias3;
    }
    if(strlen($host->hostalias4)>2){
        $aliases[]=$host->hostalias4;
    }
    if(count($aliases)>0){
        $tpl->table_form_field_text("{hostalias}",
            "<small>".@implode(", ",$aliases)."</small>",ico_computer);
    }


    $tpl->table_form_button("{delete}", "Loadjs('$page?remove=$macenc')","computer",ico_trash);
    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());
    return true;
}

function settings(){
	$macenc=urlencode($_GET["mac"]);
	$host=new hosts($_GET["mac"]);
	$tpl=new template_admin();
	$page=CurrentPageName();
	$explain=null;
	$ByProxyToken=null;
	$prepend_ip=null;
	$add=false;
	$ByProxy=false;
	$js="blur()";
    $EnableDHCPServer=0;
	if(isset($_GET["prependip"])){$prepend_ip="{$_GET["prependip"]}";}
    $DHCPDInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPDInstalled"));
    if($DHCPDInstalled==1){$EnableDHCPServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));}
    $EnableSquidMicroHotSpot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidMicroHotSpot"));
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    $CallBackFunction=CallBack();


	if(isset($_GET["ByProxy"])){
		$ByProxy=true;
		$ByProxyToken="&ByProxy=yes";
		$explain="{byproxy_explain_alias}";
	}
	
	$button="{apply}";
	if($host->ipaddr==null){
		$add=true;
		$button="{add}";
		$host->hostname="{new_computer}";
		if($prepend_ip<>null){$host->ipaddr=$prepend_ip;}
		$js="dialogInstance3.close();Loadjs('fw.edit.computer.php?mac=$macenc');$CallBackFunction";
	}else{
        $js=$CallBackFunction;
    }
	
	
	$form[]=$tpl->field_hidden("mac", $_GET["mac"]);

    if($EnableSquidMicroHotSpot==1){
        $form[]=$tpl->field_checkbox("hotspotwhite","{hotspotwhite}",$host->hotspotwhite);
    }else{
        $tpl->field_hidden("hotspotwhite",$host->hotspotwhite);
    }


	if(!$ByProxy){
	    if($EnableDHCPServer==1) {
            $form[] = $tpl->field_checkbox("dhcpfixed", "{dhcpfixed}", $host->dhcpfixed, false, "{dhcp_fixed_addr_explain}");
        }
        if($EnableDNSDist==1){
            $form[] = $tpl->field_checkbox("dnsfixed", "{dns_entry}", $host->dnsfixed);
        }
	}
	$form[]=$tpl->field_text("hostname", "{hostname}", $host->hostname,true);
    $form[]=$tpl->field_text("domainname","{domain}",$host->domainname);
	$form[]=$tpl->field_ipaddr("ipaddr", "{ipaddr}", $host->ipaddr,true);
	$form[]=$tpl->field_text("proxyalias", "{proxy_alias}", $host->proxyalias,false,"{my_proxy_aliases_text}");
	
	if(!$ByProxy){
		$form[]=$tpl->field_text("hostalias1", "{alias} 1", $host->hostalias1);
		$form[]=$tpl->field_text("hostalias2", "{alias} 2", $host->hostalias2);
		$form[]=$tpl->field_text("hostalias3", "{alias} 3", $host->hostalias3);
		$form[]=$tpl->field_text("hostalias4", "{alias} 4", $host->hostalias4);
	}
	
	if(!$add){
		$tpl->form_add_button("{delete}", "Loadjs('$page?remove=$macenc')");
	}
	
	$html[]=$tpl->form_outside($host->hostname ."&nbsp;|&nbsp;{$_GET["mac"]}",$form,
	$explain,$button,$js,"computer");
	echo $tpl->_ENGINE_parse_body( $html);
}

function scanreport(){
    $macenc=urlencode($_GET["mac"]);
    $host=new hosts($_GET["mac"]);

    echo "<textarea style='margin-top:10px;width:99%;height:450px'>$host->scanreport</textarea>";

}

function computer_dhcp_popup(){

    $EnableDHCPServer = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
    $EnableKEA = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    if($EnableKEA==1){
        $EnableDHCPServer=1;
    }
	$host=new hosts($_GET["mac"]);
	$tpl=new template_admin();

    $dhcp=new dhcpd();
    if($host->gateway=="0.0.0.0"){
        $host->gateway=$dhcp->gateway;
	}
	if($host->dns1=="0.0.0.0"){$host->dns1=$dhcp->DNS_1;}
	if($host->dns2=="0.0.0.0"){$host->dns2=$dhcp->DNS_2;}


	$form[]=$tpl->field_hidden("mac", $_GET["mac"]);
    if($EnableDHCPServer==1){
        $Interfaces=KeaInterfaces();
        $form[]=$tpl->field_checkbox("dhcpfixed","{dhcpfixed}",$host->dhcpfixed);
        $form[]=$tpl->field_array_hash($Interfaces,"dhcpiface","DHCP",$host->dhcpiface);
    }else{
        $form[]=$tpl->field_hidden("dhcpfixed", $host->dhcpfixed);
    }
    $form[]=$tpl->field_text("domainname","{domain}",$host->domainname);
    $form[]=$tpl->field_ipaddr("gateway","{gateway}",$host->gateway);
	$form[]=$tpl->field_ipaddr("gateway2","{gateway} 2",$host->gateway2);
	$form[]=$tpl->field_ipaddr("dns1","{DNSServer} 1",$host->dns1);
	$form[]=$tpl->field_ipaddr("dns2","{DNSServer} 2",$host->dns2);
	$form[]=$tpl->field_checkbox("pxe_enabled","{enable_feature} PXE",$host->pxe_enabled,"pxe_server,pxe_file",null);
	$form[]=$tpl->field_ipaddr("pxe_server","{pxe_server}",$host->pxe_server);
	$form[]=$tpl->field_text("pxe_file","{pxe_file}",$host->pxe_file);

    $url=buildurl();
    $js[]="dialogInstance4.close();";
    $js[]="LoadAjaxSilent('{$_GET["idFlat"]}','$url');";
    $js[]=CallBack()."()";

    $html[]=$tpl->form_outside("" ,$form,null,"{apply}",@implode(";",$js),"ASDCHPAdmin");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	

}

function settings_infos2(){
	$id=$_GET["id"];
	$macenc=urlencode($_GET["mac"]);
	$host=new hosts($_GET["mac"]);
	$tpl=new template_admin();
	$page=CurrentPageName();
	$data=unserialize(base64_decode($host->scanreport));
    if(!$data){
        $data=array();
        $data["OS"]=null;
        $data["UPTIME"]=null;
        $data["PORTS"]=array();
    }
	
	if($data["OS"]==null){$data["OS"]="{unknown}";}
	$html[]="<table style='width:100%' class='table table-striped'>
	<tr>
	<td><label>{OS}:</label></td>
	<td>{$data["OS"]}</td>
	</tr>
	<tr>
	<td><label>{uptime}:</label></td>
	<td>{$data["UPTIME"]}</td>
	</tr>
	
	";
    if(!is_array($data["PORTS"])){$data["PORTS"]=array();}
    foreach ($data["PORTS"] as $port=>$explain){
    	$html[]="	<tr>
	    <td><label>$port:</label></td>
	    <td>{$explain}</td>
	    </tR>";
	}
	$html[]="</table>";
	
	if(isset($_GET["with-results"])){
		
		if(is_file(PROGRESS_DIR."/nmap_single_progress.results")){
			$value=@file_get_contents(PROGRESS_DIR."/nmap_single_progress.results");
			$html[]="<textarea id='NONE' name='NONE' style='width:100%;height:350px;margin-bottom:20px'>$value</textarea>";
		}
		
	}

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/nmap.single.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/nmap_single_progress.txt";
	$ARRAY["CMD"]="system.php?nmap-scan-single=yes&MAC=$macenc&ipaddr=".urlencode($host->ipaddr);
	$ARRAY["TITLE"]="{scan_this_computer}: {$_GET["mac"]} - {$host->ipaddr}";
	$ARRAY["AFTER"]="LoadAjaxSilent('$id','$page?infos2=yes&mac=$macenc&id=$id&with-results=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-$id')";

	
	$html[]="<div style='text-align:right'>".$tpl->button_autnonome("{scan_this_computer}",$jsrestart,"fa-bolt")."</div>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function settings_infos(){
	$macenc=urlencode($_GET["mac"]);
	$id=md5($macenc);
	$page=CurrentPageName();
	$html="
	<div id='progress-$id'></div>
	<div id='$id'></div>
	<script>LoadAjaxSilent('$id','$page?infos2=yes&mac=$macenc&id=$id');</script>";
	echo $html;
}

function host_edit(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();

	$host=new hosts($_POST["mac"]);
	
	foreach ($_POST as $key=>$val){
		$val=strtolower(trim($val));
		$host->$key=$val;

	}
	$host->Save();
	if(!$host->ok){echo $host->mysql_error;return;}
	
	
	if(isset($_POST["proxyalias"])<>null){
		$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
		if($SQUIDEnable==1){
			$memcached=new lib_memcached();
			$memcached->saveKey($_POST["mac"].":alias", $_POST["proxyalias"]);
			$GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid.php?user-retranslation=yes");
		}
	}

}


function save():bool{
    $EnableKEA=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKEA"));
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    foreach ($_POST as $key=>$val){
        writelogs("$key=>$val",__FUNCTION__,__FILE__,__LINE__);
    }

    if(!isset($_POST["mac"])){
        echo $tpl->post_error("No Mac!");
        return false;
    }

	$q=new postgres_sql();
	$_POST["mac"]=str_replace("-", ":", $_POST["mac"]);
	$_POST["mac"]=strtolower($_POST["mac"]);

	$ipClass=new IP();
	if(!$ipClass->IsvalidMAC($_POST["mac"])){
        echo $tpl->post_error("MAC: {$_POST["mac"]} Invalid!");
		return false;
	}
    if(isset($_POST["ipaddr"])) {
        if (!$ipClass->isValid($_POST["ipaddr"])) {
            echo $tpl->post_error("IP: {$_POST["ipaddr"]} Invalid!");
            return false;
        }
    }

    if(isset($_POST["dhcpfixed"])) {
        if ($_POST["dhcpfixed"] == 1) {
            if ($EnableKEA == 0) {
                include_once('ressources/class.dhcpd.inc');
                $dhcp = new dhcpd();
                if($dhcp->gateway<>"0.0.0.0") {
                    $_POST["gateway"] = $dhcp->gateway;
                }
                if($dhcp->DNS_1<>"0.0.0.0") {
                    $_POST["dns1"] = $dhcp->DNS_1;
                }
                if($dhcp->DNS_2<>"0.0.0.0") {
                    $_POST["dns2"] = $dhcp->DNS_2;
                }
            }
        }
    }-

    writelogs("\$hostClass=new hosts({$_POST["mac"]});",__FUNCTION__,__FILE__,__LINE__);
	$hostClass=new hosts($_POST["mac"]);
	reset($_POST);
	foreach ($_POST as $key=>$val){
		$val=strtolower(trim($val));
        $hostClass->$key=$val;

	}

    if (isset($_POST["hostname"]) && !empty(trim($_POST["hostname"]))) {
        $hostname = trim($_POST["hostname"]);
        // Remove domain part if present
        if (strpos($hostname, ".") !== false) {
            $hostname = explode(".", $hostname)[0];
        }
        $hostClass->hostname = $hostname;

        // Set fullhostname with domainname if provided
        if (isset($_POST["domainname"]) && !empty(trim($_POST["domainname"]))) {
            $hostClass->fullhostname = "$hostname." . trim($_POST["domainname"]);
        } else {
            $hostClass->fullhostname = $hostname;
        }
    }
    $hostClass->Save();
	if(!$hostClass->ok){echo $tpl->post_error($hostClass->mysql_error);return false;}



    if (isset($_POST["proxyalias"]) && !empty(trim($_POST["proxyalias"]))) {
        $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
		if($SQUIDEnable==1){
		    $memcached=new lib_memcached();
		    $memcached->saveKey($_POST["mac"].":alias", $_POST["proxyalias"]);
		    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("squid.php?user-retranslation=yes");
		}
	}
    return admin_tracks_post("Add/Edit a new computer");
}
function KeaInterfaces():array{
    $net=new networking();
    $interfaces=$net->Local_interfaces();
    $MAIN=array();
    foreach ($interfaces as $interface=>$Ipaddress){
        $dhcpd=new dhcpd(0,1,$interface);
        if($dhcpd->service_enabled==0){
            continue;
        }
        $MAIN[$interface]="$dhcpd->subnet/$dhcpd->netmask";
    }
    return $MAIN;
}