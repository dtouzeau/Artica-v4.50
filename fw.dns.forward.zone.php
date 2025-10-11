<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["unbound-unknown-domains-js"])){unbound_unknown_domains_js();exit;}
if(isset($_GET["unbound-unknown-domains-popup"])){unbound_unknown_domains_popup();exit;}
if(isset($_POST["UnboundUseSystemDNS"])){unbound_unknown_domains_save();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["main2"])){main_table();exit;}
if(isset($_GET["dnssec-js"])){dnssec_js();exit;}
if(isset($_GET["dnssec-popup"])){dnssec_popup();exit;}

if(isset($_GET["multidomains-js"])){multidomains_js();exit;}
if(isset($_POST["multidomains"])){multidomains_save();exit;}
if(isset($_GET["multidomains-popup"])){multidomains_popup();exit;}



if(isset($_GET["transparent-js"])){transparent_js();exit;}
if(isset($_GET["transparent-popup"])){transparent_popup();exit;}

if(isset($_POST["DNSSEC"])){dnssec_save();exit;}
if(isset($_POST["TRANSPARENT"])){transparent_save();exit;}



if(isset($_GET["zone-id-js"])){zone_js();exit;}
if(isset($_GET["zone-id"])){zone();exit;}
if(isset($_POST["ID"])){zone_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete-zone"])){delete_zone();exit;}
if(isset($_GET["dns-forward-bts"])){echo base64_decode($_GET["dns-forward-bts"]);exit;}
page();

function dnssec_js():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{whitelist}: DNSSEC", "$page?dnssec-popup=yes&function=$function",600);
}
function transparent_js():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{transparent}", "$page?transparent-popup=yes&function=$function",600);
}
function multidomains_js():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["multidomains-js"]);
    return $tpl->js_dialog2("{multidomains}", "$page?multidomains-popup=$ID&function=$function",600);
}
function multidomainsToArray($ligne):array{
    $hostnames_decoded = json_decode($ligne["hostname"], true); // Always decode as an associative array
    if (!is_array($hostnames_decoded) || empty($hostnames_decoded)) {
        $hostnames = [
            "DNS1" => "8.8.8.8",
            "DNS2" => "8.8.4.4"
        ];
        return $hostnames;
    }
    $hostnames = $hostnames_decoded;


    if (!array_key_exists("DNS1", $hostnames)) {
        $hostnames["DNS1"]="8.8.8.8";
    }
    if (!array_key_exists("DNS2", $hostnames)) {
        $hostnames["DNS2"]="8.8.4.4";
    }
    return $hostnames;
}
function multidomainsCleanBulk($bulk):string{
    $tb=explode("\n",$bulk);
    $z=array();
    foreach ($tb as $t) {
        $t=trim($t);
        if(is_null($t)){
            continue;
        }
        $z[]=$t;
    }
    return @implode("\n",$z);
}
function multidomains_popup():bool{
    $function=$_GET["function"];
    $tpl=new template_admin();
    $ID=intval($_GET["multidomains-popup"]);
    $btn="{add}";
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $ligne = $q->mysqli_fetch_array("SELECT * FROM pdns_fwzones WHERE ID=$ID");
    if(!isset($ligne["zone"])){
        $ligne["zone"]="New zone group";
    }
    if($ID>0){
        $btn="{apply}";
    }
    $useTLS=intval($ligne["useTLS"]);
    $hostnames=multidomainsToArray($ligne);
    $form[]=$tpl->field_hidden("multidomains",$ID);
    $form[]=$tpl->field_text("zone","{rulename}",$ligne["zone"]);
    $form[]=$tpl->field_checkbox("useTLS","{useTLS}",$useTLS);
    $form[]=$tpl->field_text("DNS1","{primary_dns}",$hostnames["DNS1"]);
    $form[]=$tpl->field_text("DNS2","{secondary_dns}", $hostnames["DNS2"]);
    $form[]=$tpl->field_textareacode("dnsbulk","{domains}",$ligne["dnsbulk"]);
    echo $tpl->form_outside("",$form,"",$btn,"dialogInstance2.close();$function();");
    return true;
}
function multidomains_save():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["multidomains"]);
    $hostnames["DNS1"]=$_POST["DNS1"];
    $hostnames["DNS2"]=$_POST["DNS2"];
    $zone=$_POST["zone"];
    $useTLS=intval($_POST["useTLS"]);
    $jsonhostnames=json_encode($hostnames);
    $dnsbulk=multidomainsCleanBulk($_POST["dnsbulk"]);
    if($ID==0){
        $sql="INSERT INTO pdns_fwzones (zone,useTLS,hostname,dnsbulk,bulk) VALUES('$zone',$useTLS,'$jsonhostnames','$dnsbulk',1);)";

    }else{
        $sql="UPDATE pdns_fwzones SET zone='$zone',useTLS=$useTLS,hostname='$jsonhostnames',dnsbulk='$dnsbulk',bulk=1 WHERE ID=$ID;";
    }
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error."\n$sql");
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/build/zones");
    return admin_tracks_post("Save DNS Cache Bulk domains redirection");

}

function transparent_popup():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $form[]=$tpl->field_text("TRANSPARENT","{domain}","",true);
    $html[]=$tpl->form_outside("", @implode("\n", $form),"","{add}",
        "$function();LoadAjax('table-loader','$page?main=yes');dialogInstance2.close();","AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function transparent_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM=$tpl->javascript_parse_text('{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}');
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");

    if($_POST["TRANSPARENT"]==null){
        echo $tpl->post_error($ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM);
        return false;
    }

    $domain=$_POST["TRANSPARENT"];
    $sql="INSERT OR IGNORE INTO pdns_fwzones (zone,port,hostname,recursive,useTLS,transparent) VALUES('$domain','53','','0','0',1)";

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error( $q->mysql_error);
        return false;
    }

    $UnboundEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled");
    if($UnboundEnabled==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/reconfigure");
    }

    return admin_tracks_post("Adding new $domain in transparent mode for the DNS Cache service");

}

function dnssec_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM=$tpl->javascript_parse_text('{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}');
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");

    if($_POST["DNSSEC"]==null){
        echo $tpl->post_error($ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM);
        return false;
    }

    $domain=$_POST["DNSSEC"];
    $sql="INSERT OR IGNORE INTO pdns_fwzones (zone,port,hostname,recursive,useTLS,onlyinsecure,insecure) VALUES('$domain','53','','0','0',1,1)";

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->post_error( $q->mysql_error);
        return false;
    }

    $UnboundEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled");
    if($UnboundEnabled==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/reconfigure");
    }

    return admin_tracks_post("Adding new $domain inside DNSSEC whitelist forward zone");

}
function dnssec_popup():bool{
    $function=$_GET["function"];
    $page=CurrentPageName();
    $tpl=new template_admin();
    $form[]=$tpl->field_text("DNSSEC","{domain}","",true);
    $html[]=$tpl->form_outside("", @implode("\n", $form),"","{add}",
        "$function();LoadAjax('table-loader','$page?main=yes');dialogInstance2.close();","AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function zone_js():bool{
	$function=$_GET["function"];
	$id=intval($_GET["zone-id-js"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{new_forward_zone}");

	if($id>0){
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
		$sql="SELECT * FROM pdns_fwzones WHERE ID=$id";
		$ligne=$q->mysqli_fetch_array($sql);
		$hostname=$ligne["hostname"];
		$port=$ligne["port"];
		$zone=$ligne["zone"];
		$title="$zone [$hostname:$port]";
	}
	
	return $tpl->js_dialog($title, "$page?zone-id=$id&function=$function&domain={$_GET["domain"]}");
	
}

function unbound_unknown_domains_js():bool{
    $page=CurrentPageName();
    $function=$_GET["function"];
    $tpl=new template_admin();
    return $tpl->js_dialog1("{unknown_domains}","$page?unbound-unknown-domains-popup=yes&function=$function",550);
}

function delete_js():bool{
	$page=CurrentPageName();
	$t=time();
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	$id=intval($_GET["delete-js"]);
	$confirm=$tpl->javascript_parse_text("{delete} {item} $id ?");
	
	echo "
var xDelete$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#{$_GET["id"]}').remove();
}
	
function Delete$t(){
	if(!confirm('$confirm')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-zone','$id');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
}
	
Delete$t();
	";
	return true;
	
	
}

function delete_zone():bool{
	$id=$_POST["delete-zone"];
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL("DELETE FROM pdns_fwzones WHERE ID='$id'");
	if(!$q->ok){echo $q->mysql_error;return false;}
    return admin_tracks("Removed DNS Forward Zone #$id");
}

function zone():bool{
		$tpl=new template_admin();
        if(!isset($_GET["function"])){$_GET["function"]=null;}
        $function=$_GET["function"];

        $useTLS=0;
        $hostname=null;
		$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
        $zone=null;
		$id=intval($_GET["zone-id"]);
		$function_js=null;
        if($function<>null){$function_js="$function();";}
        $recursive=0;
		$bname="{add}";
        $port="";
		$page=CurrentPageName();
		$title=$tpl->javascript_parse_text("{new_forward_zone}");
		$q=new lib_sqlite("/home/artica/SQLITE/dns.db");

		if($id>0){
			$bname="{apply}";
			$sql="SELECT * FROM pdns_fwzones WHERE ID=$id";
			$ligne=$q->mysqli_fetch_array($sql);
			$hostname=$ligne["hostname"];
			$zone=$ligne["zone"];
			$port=$ligne["port"];
			$recursive=$ligne["recursive"];
			$title="$zone &raquo;&raquo; $hostname:$port";
			$useTLS=intval($ligne["useTLS"]);

		}
	    if($zone==null){
            if(isset($_GET["domain"])){
                $zone=$_GET["domain"];
            }
        }
	
		if(!is_numeric($port)){$port=53;}	

		$form[]=$tpl->field_hidden("ID", $id);
		$form[]=$tpl->field_text("zone", "{domain}", $zone);
		if($UnboundEnabled==0){$form[]=$tpl->field_checkbox("recursive","{recursive}",$recursive);}
		if($UnboundEnabled==1){$tpl->field_hidden("recursive", "0");}
		$form[]=$tpl->field_ipaddr("hostname", "{ipaddr}", $hostname);
		$form[]=$tpl->field_numeric("port","{listen_port}",$port);
		
		if($UnboundEnabled==1){
			$form[]=$tpl->field_checkbox("useTLS","{useTLS}",$useTLS);
		}else{
			$tpl->field_hidden("useTLS", "0");
		}
		
		
		$html[]=$tpl->form_outside("$title", @implode("\n", $form),"{ADD_DNS_ZONE_TEXT}",$bname,
				"$function_js;LoadAjax('table-loader','$page?main=yes');BootstrapDialog1.close();","AsDnsAdministrator");
		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

	return true;
}

function zone_save():bool{
	$id=$_POST["ID"];
	$ipv4=new IP();
	$tpl=new template_admin();
	$tpl->CLEAN_POST_XSS();
    $ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM=$tpl->javascript_parse_text('{ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM}');
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	if($_POST["hostname"]==null){
        echo $tpl->post_error($ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM);
        return false;

    }
	if($_POST["zone"]==null){
        echo $tpl->post_error($ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM);
        return false;
    }
	if(!$ipv4->isValid($_POST["hostname"])){
        echo $tpl->post_error($ERROR_VALUE_MISSING_PLEASE_FILL_THE_FORM);
        return false;
	}
	if(!is_numeric($_POST["port"])){$_POST["port"]=53;}
	$sql="INSERT OR IGNORE INTO pdns_fwzones (zone,port,hostname,recursive,useTLS) VALUES('{$_POST["zone"]}','{$_POST["port"]}','{$_POST["hostname"]}','{$_POST["recursive"]}','{$_POST["useTLS"]}')";
	if($id>0){
	$sql="UPDATE pdns_fwzones SET port='{$_POST["port"]}',
		zone='{$_POST["zone"]}',
		recursive='{$_POST["recursive"]}',
		useTLS='{$_POST["useTLS"]}',
		hostname='{$_POST["hostname"]}' WHERE ID='$id'";
    }
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
        echo $tpl->post_error( $q->mysql_error);
        return false;
    }

    $UnboundEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled");
    if($UnboundEnabled==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/control/reconfigure");
    }

    return admin_tracks_post("Editing DNS forward zone");
	
}

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();


    $html=$tpl->page_header("{forward_zones}","far fa-arrows","{forward_zones_explain}",
        "$page?main=yes","dns-forward","progress-firehol-restart",false,"table-loader");

    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return true;}

	echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function main():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'></div>";
    echo $tpl->search_block($page,null,null,null,"&main2=yes");
    return true;
}

function main_table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $jsrestart="blur();";
    $function=$_GET["function"];
	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    $PowerDNSEnableRecursor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableRecursor"));
    $DoNotUseLocalDNSCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DoNotUseLocalDNSCache"));
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
    if($PowerDNSEnableRecursor==1){
        $jsrestart=$tpl->framework_buildjs(
            "pdns.php?reload-recusor=yes",
            "recusor.restart.progress",
            "recusor.restart.log",
            "dnsfw-zone-restart",
            "document.getElementById('dnsfw-zone-restart').innerHTML=''"
        );

    }

    if($DoNotUseLocalDNSCache==0){
        $jsrestart="Loadjs('fw.dns.servers.php?restart-localdnscache');document.getElementById('dnsfw-zone-restart').innerHTML=''";


    }

	if($UnboundEnabled==1){
        $jsrestart=$tpl->framework_buildjs(
            "/unbound/control/reconfigure",
            "unbound.reconfigure.progress",
            "unbound.reconfigure.log",
            "dnsfw-zone-restart",
            "document.getElementById('dnsfw-zone-restart').innerHTML=''"
        );
	}

    if($EnableDNSDist==1) {
        $jsrestart = $tpl->framework_buildjs("/dnsfw/service/php/restart",
            "dnsdist.restart",
            "dnsdist.restart.log",
            "dnsfw-zone-restart",
            "document.getElementById('dnsfw-zone-restart').innerHTML=''");
    }

	$delete=$tpl->javascript_parse_text("{delete}");
	$zone=$tpl->_ENGINE_parse_body("{zone}");
	$dns_server=$tpl->_ENGINE_parse_body("{dns_server}");
    $UnBoundDNSSEC=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundDNSSEC"));


    $topbuttons[] = array("Loadjs('$page?zone-id-js=0&function=$function')", ico_plus, "{new_forward_zone}");
    if($UnboundEnabled==1) {
        $topbuttons[] = array("Loadjs('$page?multidomains-js=0&function=$function')", ico_plus, "{multidomains}");

        if ($UnBoundDNSSEC == 1) {
            $topbuttons[] = array("Loadjs('$page?dnssec-js=yes&function=$function')", ico_plus, "{whitelist}: DNSSEC");
        }
        $topbuttons[] = array("Loadjs('$page?transparent-js=yes&function=$function')", ico_plus, "{transparent}");
    }


    $topbuttons[] = array($jsrestart, ico_save, "{reconfigure_service}");

    $html[]="<div id='dnsfw-zone-restart'></div>";
	$html[]="<table id='table-dns-forward-zones' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$zone</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$dns_server</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$delete</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
    $QUS=1;

    $search=$_GET["search"];
    if($search<>null){
        $search="*".$_GET["search"]."*";
        $search=str_replace("**","*",$search);
        $QUS="WHERE ( (zone LIKE '$search' ) OR ( hostname LIKE '$search') OR ( dnsbulk LIKE '$search') )";
    }
	$TRCLASS=null;
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="SELECT *  FROM pdns_fwzones WHERE $QUS ORDER by zone";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	foreach ($results as $index=>$ligne){

        $labelTransparent="";
		$md=md5(serialize($ligne));
		$id=$ligne["ID"];
		$text_recursive=null;
		$useTLS=intval($ligne["useTLS"]);
		$useTLSCert=null;
        $bulk=intval($ligne["bulk"]);
        $hostname="";
        $dnsCount=0;
        $dnsbulk=multidomainsCleanBulk($ligne["dnsbulk"]);
		if($useTLS==1){
            $useTLSCert="&nbsp;<i class=\"fas fa-file-certificate\" id='$index'></i>";
        }
        if($bulk==1){
            if($UnboundEnabled==0) {
                continue;
            }
        }

        if($bulk==0) {
            $hostname = $ligne["hostname"] . ":" . $ligne["port"];
        }
		$zone=trim($ligne["zone"]);
		$recursive=$ligne["recursive"];
        $onlyinsecure=$ligne["onlyinsecure"];
        $transparent=$ligne["transparent"];

        if($transparent==1){
            if($UnboundEnabled==0){continue;}
            $hostname = "{automatic}";
            $labelTransparent="<span class='label label-info'>{transparent}</span>";
        }

		if($recursive==1){$text_recursive=$tpl->_ENGINE_parse_body("&nbsp;<i>({recursive})</i>");}
		if($zone=="*"){$zone=$tpl->_ENGINE_parse_body("{all} (*)");}
		if($zone=="."){$zone=$tpl->_ENGINE_parse_body("{all} (*)");}
        $labelInsecure="";
        if($onlyinsecure==1){
            if($UnboundEnabled==0){continue;}
            if($UnBoundDNSSEC==0) {continue;}
            $hostname = "{automatic}";
            if ($ligne["insecure"]==1){
                $labelInsecure="<span class='label label-warning'>DNSSEC {insecure}</span>";
            }
        }
        if($UnboundEnabled==1) {
            if ($UnBoundDNSSEC == 1) {
                if ($ligne["insecure"] == 1) {
                    $labelInsecure = "<span class='label label-warning'>DNSSEC {insecure}</span>";
                }
            }
        }
        if($bulk==1){
            $hostnames=multidomainsToArray($ligne);
            $tb=array();
            foreach ($hostnames as $key=>$dns) {
                VERBOSE("$key -> $dns",__LINE__);
                $tb[]=$dns;
            }
            $hostname=@implode("&nbsp;|&nbsp;", $tb);
            $tb=explode("\n", $dnsbulk);
            foreach ($tb as $dns) {
                if(strlen($dns)>2) {
                    $dnsCount++;
                }
            }
            $zone=$tpl->td_href($zone,"","Loadjs('$page?multidomains-js=$id&function=$function')")." ($dnsCount {domains})";
        }

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td nowrap style='font-weight:bold;width:1%'><i class='fas fa-globe'></i>&nbsp;$zone</td>";
		$html[]="<td><i class='fas fa-arrow-to-right'></i>&nbsp;$hostname $text_recursive$useTLSCert$labelInsecure$labelTransparent</td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_delete("Loadjs('$page?delete-js=$id&id=$md')","AsDnsAdministrator")."</td>";
		$html[]="</tr>";
	}

    $UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));


    if($UnboundEnabled==1){
        $md=md5(time());
        $f=UnboundUnknownDomains();
        $UnknownDomains=$tpl->td_href("{unknown_domains}","","Loadjs('$page?unbound-unknown-domains-js=yes&function=$function');");
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td nowrap style='font-weight:bold;width:1%'><i class='".ico_star_asterisk."'></i>&nbsp;$UnknownDomains</td>";
        $html[]="<td><i class='fas fa-arrow-to-right'></i>&nbsp;".@implode(", ",$f)."</td>";
        $html[]="<td style='vertical-align:middle;width:1%' class='center'>&nbsp;</td>";
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

    $TINY_ARRAY["TITLE"]="{forward_zones}";
    $TINY_ARRAY["ICO"]="far fa-arrows";
    $TINY_ARRAY["EXPL"]="{forward_zones_explain}";
    $TINY_ARRAY["URL"]="dns-forward";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


	$html[]="<script>";
    $html[]="NoSpinner();";
    $html[]=@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]=$jstiny;
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function UnboundUnknownDomains():array{


    $UnboundEnforceUnknownDomains=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnforceUnknownDomains"));
    if($UnboundEnforceUnknownDomains==1){
        return UnboundEnforcedDomains();
    }
    $IP=new IP();
    $f=array();
    $resolv = new resolv_conf();
    $DNS_TEMP = array();
    $DNSForEUBackendsTypes[0] = "{inactive2}";
    $DNSForEUBackendsTypes[1] = "{protective_resolution}";
    $DNSForEUBackendsTypes[2] = "{child_protection}";
    $DNSForEUBackendsTypes[3] = "{ads_protection}";
    $DNSForEUBackendsTypes[4] = "{child_protection} & {ads_protection}";
    $DNSForEUBackendsTypes[5] = "{unfiltered_resolution}";
    $UseDNSForEUBackends=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseDNSForEUBackends"));
    VERBOSE("$DNSForEUBackendsTypes[$UseDNSForEUBackends]",__LINE__);

    if($UseDNSForEUBackends>0){
        $DNS_TEMP["{UseDNSForEUBackends} (<small>$DNSForEUBackendsTypes[$UseDNSForEUBackends]</small>)"]=true;
    }


    if ($IP->isValid($resolv->MainArray["DNS1"])) {
        $DNS_TEMP[$resolv->MainArray["DNS1"]] = true;
    }
    if ($IP->isValid($resolv->MainArray["DNS2"])) {
        $DNS_TEMP[$resolv->MainArray["DNS2"]] = true;
    }
    if(isset($resolv->MainArray["DNS3"])) {
        if ($IP->isValid($resolv->MainArray["DNS3"])) {
            $DNS_TEMP[$resolv->MainArray["DNS3"]] = true;
        }
    }
    foreach ($DNS_TEMP as $serv => $none) {
        $f[] = $serv;
    }
    return $f;
}
function UnboundEnforcedDomains():array{
    $UnboundEnforcedDomains=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnforcedDomains"));
    $vals=explode(";",$UnboundEnforcedDomains);
    $DNSForEUBackendsTypes[0] = "{inactive2}";
    $DNSForEUBackendsTypes[1] = "{protective_resolution}";
    $DNSForEUBackendsTypes[2] = "{child_protection}";
    $DNSForEUBackendsTypes[3] = "{ads_protection}";
    $DNSForEUBackendsTypes[4] = "{child_protection} & {ads_protection}";
    $DNSForEUBackendsTypes[5] = "{unfiltered_resolution}";
    $UseDNSForEUBackends=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseDNSForEUBackends"));
    if($UseDNSForEUBackends==1){
        $new[]=$DNSForEUBackendsTypes[$UseDNSForEUBackends];
        foreach ($vals as $serv ) {
            $new[]=$serv;
        }
        return $new;

    }
    return $vals;
}

function unbound_unknown_domains_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    if($_POST["UnboundUseSystemDNS"]==1){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnboundEnforceUnknownDomains",0);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnboundEnforceUnknownDomains",1);
    }
    $f=array();
    for($i=1;$i<5;$i++) {
        if(!isset($_POST["DNS$i"])){continue;}
        if (strlen($_POST["DNS$i"]) > 4) {
            $f[] = trim($_POST["DNS$i"]);
        }
    }
    $UnboundEnforcedDomains=base64_encode(@implode(";",$f));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnboundEnforcedDomains",$UnboundEnforcedDomains);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/build/zones");
    return admin_tracks_post("Save DNS unknown domains rule to");
}

function unbound_unknown_domains_popup():bool{
    $function=$_GET["function"];
    $tpl=new template_admin();
    $UnboundEnforceUnknownDomains=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnforceUnknownDomains"));
    $UnboundUseSystemDNS=1;
    if($UnboundEnforceUnknownDomains==1){
        $UnboundUseSystemDNS=0;
    }

    $form[] = $tpl->field_checkbox("UnboundUseSystemDNS", "{SambaDnsProxy}", $UnboundUseSystemDNS);

    $UnboundEnforcedDomains=explode(";",base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnforcedDomains")));


    if(!isset($UnboundEnforcedDomains[0])){
        $UnboundEnforcedDomains[0]="";
    }
    if(!isset($UnboundEnforcedDomains[1])){
        $UnboundEnforcedDomains[1]="";
    }
    if(!isset($UnboundEnforcedDomains[2])){
        $UnboundEnforcedDomains[2]="";
    }
    if(!isset($UnboundEnforcedDomains[3])){
        $UnboundEnforcedDomains[3]="";
    }

    $js="dialogInstance1.close();$function();";

    $form[] = $tpl->field_text("DNS1", "{primary_dns} ", $UnboundEnforcedDomains[0]);
    $form[] = $tpl->field_text("DNS2", "{secondary_dns} ", $UnboundEnforcedDomains[1]);
    $form[] = $tpl->field_text("DNS3", "{DNSServer} 3", $UnboundEnforcedDomains[2]);
    $form[] = $tpl->field_text("DNS4", "{DNSServer} 4", $UnboundEnforcedDomains[3]);

    echo $tpl->form_outside("",$form,"","{apply}",$js);
    return true;

}