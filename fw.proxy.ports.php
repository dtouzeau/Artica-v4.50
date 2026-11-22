<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
// Verrou Artica Meta (politique sslproxy) : AC de decryptage des ports SSL tenue par Meta
include_once(dirname(__FILE__)."/ressources/class.sslproxy.meta.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_POST["SquidReconfigureAtBoot"])){saveall();exit;}
if(isset($_GET["connected-ports"])){connected_port_section();exit;}
if(isset($_GET["connected-ports-list"])){connected_port_list();exit;}
if(isset($_GET["row-status"])){pp_row_status();exit;}
if(isset($_POST["SaveConnectedPort"])){connected_port_save();exit;}
if(isset($_GET["flat-communications"])){communications_ports_flat();exit;}
if(isset($_GET["comm-icp-js"])){comm_icp_js();exit;}
if(isset($_GET["comm-icp-popup"])){comm_icp_popup();exit;}
if(isset($_POST["comm-icp-save"])){comm_icp_save();exit;}
if(isset($_GET["comm-udp-js"])){comm_udp_js();exit;}
if(isset($_GET["comm-udp-popup"])){comm_udp_popup();exit;}
if(isset($_POST["comm-udp-save"])){comm_udp_save();exit;}
if(isset($_GET["comm-snmp-js"])){comm_snmp_js();exit;}
if(isset($_GET["comm-snmp-popup"])){comm_snmp_popup();exit;}
if(isset($_POST["comm-snmp-save"])){comm_snmp_save();exit;}
if(isset($_GET["port-js"])){connected_port_js();exit;}
if(isset($_GET["port-popup"])){connected_port_popup();exit;}
if(isset($_GET["port-popup-start"])){connected_port_popup_start();exit;}
if(isset($_GET["port-delete-js"])){connected_port_delete_js();exit;}
if(isset($_POST["port-delete"])){connected_port_delete();exit;}
// Pentest 2026-11-04 : include/portid etaient recopies sans encodage dans la page (XSS). Ce sont des entiers.
foreach(array("include","portid") as $k){if(isset($_GET[$k])){$_GET[$k]=intval($_GET[$k]);}}
if(isset($_GET["transparent-ports"])){transparent_ports();exit;}
if(isset($_GET["communications-ports"])){communications_ports();exit;}
if(isset($_GET["transparent-wbl-js"])){transparent_ports_exclude_js();exit;}
if(isset($_GET["transparent-wbl"])){transparent_ports_exclude_popup();exit;}
if(isset($_POST["proxy_ports_wbl"])){transparent_ports_exclude_save();exit;}
if(isset($_GET["troubleshooting"])){troubleshooting_ports();exit;}
if(isset($_GET["SquidMgrOutGoingInterface-popup"])){SquidMgrOutGoingInterface_popup();exit;}
if(isset($_GET["SquidMgrOutGoingInterface-js"])){SquidMgrOutGoingInterface_js();exit;}
if(isset($_POST["SquidMgrOutGoingInterface"])){SquidMgrOutGoingInterface_save();exit;}
page();

function saveall(){
    $tpl    =new template_admin();
    $tpl->SAVE_POSTs();
}
function SquidMgrOutGoingInterface_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SquidMgrListenPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));

    return $tpl->js_dialog("127.0.0.1:$SquidMgrListenPort >> {outgoing_interface}", "$page?SquidMgrOutGoingInterface-popup=yes");
}
function SquidMgrOutGoingInterface_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $SquidMgrOutGoingInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrOutGoingInterface");

    $form[]=$tpl->field_interfaces("SquidMgrOutGoingInterface","{outgoing_interface}",$SquidMgrOutGoingInterface);

    $jsrestart=$tpl->framework_buildjs("/proxy/ports/reconfig",
        "squid.listenport.progress","ssquid.listenport.log",
        "progress-squid-ports-restart");

    $jsafter="LoadAjax('table-connected-proxy-ports','$page?connected-ports-list=yes');BootstrapDialog1.close();$jsrestart";

    $security="AsSquidAdministrator";
    $html[]=$tpl->form_outside("", @implode("\n", $form),null,"{apply}",$jsafter,$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function SquidMgrOutGoingInterface_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    return admin_tracks("Change listen interface for working proxy port 127.0.0.1 outgoing address");

}
function connected_port_js(){
	$ID=intval($_GET["port-js"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$title=$tpl->javascript_parse_text("{new_port}");
	include_once(dirname(__FILE__)."/ressources/class.squid.ports.patch.inc");
	Patch_table_squid_ports();
	
	
	if($ID>0){
		$ligne=$q->mysqli_fetch_array("SELECT ipaddr,port,WCCP FROM proxy_ports WHERE ID=$ID");
		if(intval($ligne["WCCP"])==1){$wccp="WCCP:";}
		$title="{listen_port}: {$ligne["ipaddr"]}:{$ligne["port"]}";
	
	}
	
	$tpl->js_dialog($title, "$page?port-popup-start=$ID");
	
}

function transparent_ports(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$EnableSquidTransparent=0;
	$EnableSquidTransparentSSL=0;
	$SquidTransparentSSLCert=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentSSLCert"));
	$SquidInRouterMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidInRouterMode"));
	$EnableSquidTproxy=0;

	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$sql="SELECT port FROM proxy_ports WHERE enabled=1 AND transparent=1 AND UseSSL=0";
	$results = $q->QUERY_SQL($sql);
	foreach($results as $index=>$ligne){$port[]=$ligne["port"];}
	if(count($port)>0){$EnableSquidTransparent=1;}
	
	$sql="SELECT port FROM proxy_ports WHERE enabled=1 AND transparent=1 AND UseSSL=1";
	$results = $q->QUERY_SQL($sql);
	$sport=array();
	foreach($results as $index=>$ligne){$sport[]=$ligne["port"];}
	if(count($sport)>0){$EnableSquidTransparentSSL=1;}
	
	
	$sql="SELECT port FROM proxy_ports WHERE enabled=1 AND TProxy=1 AND UseSSL=0";
	$results = $q->QUERY_SQL($sql);
	$port=array();
	foreach($results as $index=>$ligne){$port[]=$ligne["port"];}
	if(count($port)>0){
		$EnableSquidTransparent=1;
		$EnableSquidTproxy=1;
		$Tports=@implode(", ", $port);
	}
	
	$sql="SELECT port FROM proxy_ports WHERE enabled=1 AND TProxy=1 AND UseSSL=1";
	$results = $q->QUERY_SQL($sql);
	$sport=array();
	foreach($results as $index=>$ligne){$sport[]=$ligne["port"];}
	if(count($sport)>0){$EnableSquidTransparentSSL=1;}
	
	$SquidTransparentInterfaceIN=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentInterfaceIN"));
	$SquidTransparentInterfaceOUT=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentInterfaceOUT"));
	$SquidSSLUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSSLUrgency"));
	if($SquidSSLUrgency==1){echo $tpl->FATAL_ERROR_SHOW_128("{proxy_in_ssl_emergency_mode}");}

	$form[]=$tpl->field_section("{router_mode}","{explain_http_proxy_router_mode}");
	$form[]=$tpl->field_checkbox("SquidInRouterMode","{router_mode}",$SquidInRouterMode);
	$form[]=$tpl->field_section("{enable_this_proxy_transparent}","{explain_http_proxy_transparent}");
	$form[]=$tpl->field_checkbox("EnableSquidTransparent","{enable_this_proxy_transparent}",$EnableSquidTransparent,false);
	$form[]=$tpl->field_checkbox("EnableSquidTransparentSSL","{catch_ssl_requests}",$EnableSquidTransparentSSL,false,"{transparent_ssl_explain}");
	$form[]=$tpl->field_link("{whitelisted_src_networks}",null, "Loadjs('fw.proxy.ports.php?transparent-wbl-js=yes&include=0')");
	$form[]=$tpl->field_link("{whitelisted_destination_networks}",null, "Loadjs('$page?transparent-wbl-js=yes&include=1')");
	
	
	//$ex[1]='{whitelisted_destination_networks}';
	//$ex[0]="{whitelisted_src_networks}";
	//$title="<strong style=font-size:30px>".$tpl->javascript_parse_text("{listen_ports}: {$ex[$_GET["include"]]}")."</strong>";
	
	
	

	
	
	$form[]=$tpl->field_section("{tproxy_method}","{tproxy_method_explain}");
	$form[]=$tpl->field_checkbox("EnableSquidTproxy","{enable_this_proxy_transparent} (Tproxy)",$EnableSquidTproxy,false);
	
	
	$form[]=$tpl->field_section("{interfaces}","{tproxy_interfaces_explain}");
	$form[]=$tpl->field_interfaces("SquidTransparentInterfaceIN", "{listen_interface}", $SquidTransparentInterfaceIN);
	$form[]=$tpl->field_interfaces("SquidTransparentInterfaceOUT", "{outgoing_interface}", $SquidTransparentInterfaceOUT,"{outgoing_interface_explain_proxy}");
	
	
	
	
	include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	
	$form[]=$tpl->field_section("{certificate}","{proxy_certificate_explain}");
	$form[]=$tpl->field_array_hash($sslcertificates, "SquidTransparentSSLCert", "{use_certificate_from_certificate_center}", $SquidTransparentSSLCert);
	$html[]=$tpl->form_outside("{transparent_method}", @implode("\n", $form),"{transparent_mode_limitations}","{apply}","Loadjs('fw.proxy.ports.apply.php');","AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function transparent_ports_exclude_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ex[1]='{whitelisted_destination_networks}';
	$ex[0]="{whitelisted_src_networks}";
	$title=$ex[$_GET["include"]];
	$tpl->js_dialog1($title, "$page?transparent-wbl=yes&include={$_GET["include"]}&portid={$_GET["portid"]}");
}
function transparent_ports_exclude_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ex[1]='{whitelisted_destination_networks}';
	$ex[0]="{whitelisted_src_networks}";
	$title=$ex[$_GET["include"]];
	$form[]=$tpl->field_hidden("portid",$_GET["portid"]);
	$form[]=$tpl->field_hidden("include", $_GET["include"]);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	// Pentest 2026-11-04 : include et portid etaient concatenes dans la requete.
	$include=intval($_GET["include"]);
	$portid=intval($_GET["portid"]);
	$sql="SELECT pattern  FROM `proxy_ports_wbl` WHERE include=$include AND portid=$portid";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}
	$f=array();
	if(count($results)>0){
		foreach ($results as $index=>$ligne){
			$f[]=$ligne["pattern"];
		}
	}

	if($_GET["include"]==1) {
            if (count($f) == 0) {
                $f[] = "10.0.0.0/8";
                $f[] = "192.168.0.0/16";
                $f[] = "172.16.0.0/12";
            }
    }

    $jsrestart=$tpl->framework_buildjs("/firewall/proxy/ports",
        "squid.transparent.build","squid.transparent.build.log",
    "fiw-restart","dialogInstance1.close();LoadAjax('proxy-transparent-ports','fw.proxy.transparent.php?table=yes')");

	
	$form[]=$tpl->field_textareacode("proxy_ports_wbl", "{networks}", @implode("\n", $f));
	$html[]="<div id='fiw-restart' style='width:99%;margin-bottom:10px;margin-top:5px'></div>";
	$html[]=$tpl->form_outside("$title", @implode("\n", $form),"{subnet_simple_explain}","{apply}",$jsrestart,"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function transparent_ports_exclude_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	// Pentest 2026-11-04 : include et portid etaient concatenes dans la requete.
	$include=intval($_POST["include"]);
	$portid=intval($_POST["portid"]);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$q->QUERY_SQL("DELETE FROM proxy_ports_wbl WHERE include=$include AND portid=$portid");
	$f=explode("\n",$_POST["proxy_ports_wbl"]);
	$n=array();
	$ip=new IP();
	$ERR=array();
	// Pentest 2026-11-04 : les lignes de proxy_ports_wbl etaient concatenees dans la requete.
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(strpos($line,"'")!==false){$ERR[]=$line;continue;}
		if($ip->isValid($line)){
			$n[]="($include,$portid,'$line')";
			continue;
		}
		if($ip->IsACDIR($line)){
			$n[]="($include,$portid,'$line')";
			continue;
		}
		if($ip->IsARange($line)){
			$n[]="($include,$portid,'$line')";
			continue;			
		}
		$ERR[]=$line;
	}
	
	if(count($n)>0){
		$q->QUERY_SQL("INSERT INTO proxy_ports_wbl (include,portid,pattern) VALUES ".@implode(",", $n));
		if(!$q->ok){echo $q->mysql_error_html();}
	}
	if(count($ERR)>0){echo "$ERR Errors " .@implode("\n", $ERR)."\n";}
}

function communications_ports(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// L'onglet ne porte que le conteneur et son TINY. La carte est un fragment a
	// part pour qu'un enregistrement rafraichisse LA CARTE: recharger l'onglet
	// relancerait le TINY et ramenerait la page en haut.
	$html[]="<div id='flat-comm-ports'></div>";
	$html[]="<script>LoadAjaxSilent('flat-comm-ports','$page?flat-communications=yes');</script>";

	$TINY_ARRAY["TITLE"]="{communications_ports}";
	$TINY_ARRAY["ICO"]="fad fa-plug";
	$TINY_ARRAY["EXPL"]="";
	$TINY_ARRAY["BUTTONS"]="";
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
	$html[]="<script>$jstiny</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

// Les reglages, lus en UN endroit pour les deux consommateurs: la carte et les
// modales. Deux lectures separees, c'est deux valeurs par defaut qui divergent
// le jour ou l'une est modifiee.
function comm_ports_settings():array{
	$s=array();
	$s["SquidICPPort"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidICPPort"));
	// Lisait SquidICPPort: le port HTCP affichait la valeur ICP, et enregistrer
	// le formulaire recopiait l'une sur l'autre.
	$s["SquidHTCPPort"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidHTCPPort"));
	$s["udp_incoming_address"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("udp_incoming_address"));
	$s["udp_outgoing_address"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("udp_outgoing_address"));
	$s["SquidSNMPPort"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSNMPPort"));
	if($s["SquidSNMPPort"]==0){$s["SquidSNMPPort"]=3401;}
	$s["SNMPDCommunity"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDCommunity");
	if($s["SNMPDCommunity"]==null){$s["SNMPDCommunity"]="public";}
	$s["SNMPDNetwork"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDNetwork");
	if($s["SNMPDNetwork"]==null){$s["SNMPDNetwork"]="default";}
	$s["EnableProxyInSNMPD"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyInSNMPD"));
	return $s;
}

// Un port a 0 n'est pas le port zero: c'est le protocole eteint, et c'est ainsi
// que squid le lit. Afficher "0" laisserait croire a un port valide.
function comm_ports_portlabel($port):string{
	$port=intval($port);
	if($port<1){return "{disabled}";}
	return "$port";
}

// Une interface vide veut dire "toutes" cote squid, pas "aucune".
function comm_ports_iflabel($value):string{
	$value=trim((string)$value);
	if($value==""){return "{all_interfaces}";}
	return htmlspecialchars($value);
}

function comm_ports_snmpwalk_example(array $s):string{
	$addr=$_SERVER["SERVER_ADDR"];
	if($s["EnableProxyInSNMPD"]==1){
		return "snmpwalk -v 2c -c {$s["SNMPDCommunity"]} $addr .1.3.6.1.4.1.3495.1";
	}
	return "snmpwalk -v 2c -c {$s["SNMPDCommunity"]} $addr:{$s["SquidSNMPPort"]} .1.3.6.1.4.1.3495.1";
}

function communications_ports_flat(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$s=comm_ports_settings();
	$sec="AsSquidAdministrator";

	// Chaque ligne rearme son ouvreur: table_form_field_js() ne vaut que pour la
	// ligne SUIVANTE, et deux lignes d'une meme section ouvrent la meme modale.
	$tpl->table_form_section("{communications_ports}","{icp_port_explain}");
	$tpl->table_form_field_js("Loadjs('$page?comm-icp-js=yes')",$sec);
	$tpl->table_form_field_text("{icp_port}",comm_ports_portlabel($s["SquidICPPort"]),ico_plug);
	$tpl->table_form_field_js("Loadjs('$page?comm-icp-js=yes')",$sec);
	$tpl->table_form_field_text("{htcp_port}",comm_ports_portlabel($s["SquidHTCPPort"]),ico_plug);

	$tpl->table_form_section("{UDP_PROTOCOL}","{udp_incoming_outgoing_address}");
	$tpl->table_form_field_js("Loadjs('$page?comm-udp-js=yes')",$sec);
	$tpl->table_form_field_text("{incoming_interface}",comm_ports_iflabel($s["udp_incoming_address"]),ico_interface);
	$tpl->table_form_field_js("Loadjs('$page?comm-udp-js=yes')",$sec);
	$tpl->table_form_field_text("{outgoing_interface}",comm_ports_iflabel($s["udp_outgoing_address"]),ico_interface);

	// La commande d'exemple est de la DOCUMENTATION, pas une alerte: elle va sous
	// le titre de section comme dans l'ancien formulaire. Passee a
	// table_form_field_warn() elle recevait une pastille ambre et un triangle
	// d'avertissement pour ce qui n'est qu'un exemple a copier.
	$tpl->table_form_section("{monitor_proxy_service} (SNMP)",
		"<span style='font-family:monospace'>".htmlspecialchars(comm_ports_snmpwalk_example($s))."</span>");
	$tpl->table_form_field_js("Loadjs('$page?comm-snmp-js=yes')",$sec);
	$tpl->table_form_field_text("{listen_port} (SNMPv2c)",$s["SquidSNMPPort"],ico_monitor);
	if($s["EnableProxyInSNMPD"]==1){
		// Le SNMPD du systeme interroge lui-meme le proxy: la communaute et le
		// reseau autorise deviennent les siens. Les montrer editables ici
		// afficherait des valeurs que squid n'utilise plus -- l'ancien formulaire
		// les masquait deja, on dit en plus POURQUOI.
		$tpl->table_form_field_js("");
		$tpl->table_form_field_warn("{monitor_proxy_service_snmpd_explain}");
	}else{
		$tpl->table_form_field_js("Loadjs('$page?comm-snmp-js=yes')",$sec);
		$tpl->table_form_field_text("{snmp_community}",htmlspecialchars($s["SNMPDCommunity"]),ico_monitor);
		$tpl->table_form_field_js("Loadjs('$page?comm-snmp-js=yes')",$sec);
		$tpl->table_form_field_text("{remote_snmp_console_ip}",htmlspecialchars($s["SNMPDNetwork"]),ico_networks);
	}
	echo $tpl->table_form_compile(2);
}

// Ferme la modale puis relance le proxy: ces ports ne sont relus qu'au
// demarrage. Le rafraichissement de la carte est le callback du redemarrage, pas
// une instruction de plus -- sinon la carte se redessine avant que squid ait
// repris la valeur et affiche l'ancienne.
function comm_ports_after():string{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$restart=$tpl->framework_buildjs("/proxy/general/nohup/restart",
		"squid.articarest.nohup","squid.articarest.nohup.log","progress-squid-ports-restart",
		"LoadAjaxSilent('flat-comm-ports','$page?flat-communications=yes');");
	return "dialogInstance2.close();".$restart;
}

// Masquer un bouton n'est pas un controle de privilege: le repartiteur en tete
// de fichier repond a toute session authentifiee.
function comm_ports_require_admin():bool{
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	$tpl=new template_admin();
	echo $tpl->post_error("{ERROR_NO_PRIVS}");
	return false;
}

// Liste blanche. Le handler historique faisait SET_INFO sur TOUTE cle postee:
// n'importe quel champ ajoute a n'importe quel formulaire de la page ecrivait
// dans les reglages.
function comm_ports_save_keys(array $keys,$numeric=false):bool{
	$sock=new sockets();
	foreach($keys as $k){
		if(!isset($_POST[$k])){continue;}
		$v=$_POST[$k];
		if($numeric){$v=intval($v);}
		$sock->SET_INFO($k,$v);
	}
	return true;
}

function comm_icp_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	return $tpl->js_dialog2("{communications_ports}","$page?comm-icp-popup=yes",620);
}

function comm_icp_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$s=comm_ports_settings();
	$f[]=$tpl->field_numeric("SquidICPPort","{icp_port}",$s["SquidICPPort"],"{icp_port_explain}");
	$f[]=$tpl->field_numeric("SquidHTCPPort","{htcp_port}",$s["SquidHTCPPort"],"{htcp_port_explain}");
	$f[]=$tpl->field_hidden("comm-icp-save","yes");
	$html[]=$tpl->div_explain("{communications_ports}||{icp_port_explain}");
	$html[]=$tpl->form_outside("",@implode("\n",$f),"","{apply}",comm_ports_after(),"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function comm_icp_save(){
	if(!comm_ports_require_admin()){return;}
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	comm_ports_save_keys(array("SquidICPPort","SquidHTCPPort"),true);
	// Rien a echo en cas de succes: form_outside prend toute reponse non vide
	// pour une erreur et n'executerait pas son $jsafter.
}

function comm_udp_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	return $tpl->js_dialog2("{UDP_PROTOCOL}","$page?comm-udp-popup=yes",620);
}

function comm_udp_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$s=comm_ports_settings();
	// On demande une INTERFACE, jamais une adresse: une adresse recopiee dans un
	// reglage se perime au premier renouvellement de bail.
	$f[]=$tpl->field_interfaces("udp_incoming_address","{incoming_interface}",$s["udp_incoming_address"]);
	$f[]=$tpl->field_interfaces("udp_outgoing_address","{outgoing_interface}",$s["udp_outgoing_address"]);
	$f[]=$tpl->field_hidden("comm-udp-save","yes");
	$html[]=$tpl->div_explain("{UDP_PROTOCOL}||{udp_incoming_outgoing_address}");
	$html[]=$tpl->form_outside("",@implode("\n",$f),"","{apply}",comm_ports_after(),"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function comm_udp_save(){
	if(!comm_ports_require_admin()){return;}
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	comm_ports_save_keys(array("udp_incoming_address","udp_outgoing_address"));
}

function comm_snmp_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	return $tpl->js_dialog2("{monitor_proxy_service} (SNMP)","$page?comm-snmp-popup=yes",620);
}

function comm_snmp_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$s=comm_ports_settings();
	$f[]=$tpl->field_numeric("SquidSNMPPort","{listen_port} (SNMPv2c)",$s["SquidSNMPPort"],null);
	if($s["EnableProxyInSNMPD"]==0){
		$f[]=$tpl->field_text("SNMPDCommunity","{snmp_community}",$s["SNMPDCommunity"],null);
		$f[]=$tpl->field_ipaddr("SNMPDNetwork","{remote_snmp_console_ip}",$s["SNMPDNetwork"]);
	}
	$f[]=$tpl->field_hidden("comm-snmp-save","yes");
	$html[]=$tpl->div_explain("{monitor_proxy_service}||{monitor_proxy_service_snmpd_explain}");
	$html[]=$tpl->form_outside("",@implode("\n",$f),"","{apply}",comm_ports_after(),"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function comm_snmp_save(){
	if(!comm_ports_require_admin()){return;}
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	comm_ports_save_keys(array("SquidSNMPPort"),true);
	// Quand le SNMPD du systeme porte la requete, la modale n'affiche NI la
	// communaute NI le reseau: les ecrire depuis un POST qui ne les contient pas
	// les viderait. comm_ports_save_keys ignore une cle absente, mais la liste
	// dit deja que ces deux la n'appartiennent pas a ce cas.
	$s=comm_ports_settings();
	if($s["EnableProxyInSNMPD"]==0){
		comm_ports_save_keys(array("SNMPDCommunity","SNMPDNetwork"));
	}
}




function connected_port_delete_js(){
	$ID=intval($_GET["port-delete-js"]);
	$page=CurrentPageName();
	$tpl=new template_admin();	
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM proxy_ports WHERE ID=$ID");
	if(!is_array($ligne)){$ligne=array();}
	// Consequence dite avant l'action : quels navigateurs perdent l'acces.
	$addr="{all_interfaces}";
	$nic=strval($ligne["nic"] ?? "");
	if($nic<>""){
		$znic=new system_nic($nic);
		$addr=htmlspecialchars(strval($znic->IPADDR));
	}
	$target="<strong>$addr : ".intval($ligne["port"] ?? 0)."</strong>";
	$text=str_replace("%P",$target,$tpl->_ENGINE_parse_body("{ppc_delete_confirm}"));
	$jsafter="LoadAjax('table-connected-proxy-ports','$page?connected-ports-list=yes');";
	$tpl->js_confirm_delete($text, "port-delete", $ID,$jsafter,true);
	
}
function connected_port_delete(){
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){echo "Permission denied";return;}
	$ID=intval($_POST["port-delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT port,transparent,TProxy FROM proxy_ports WHERE ID=$ID");
	if(!is_array($ligne)){$ligne=array();}
	// Les ports du mode transparent ne se suppriment pas d'ici (aucun bouton ne l'offre).
	if(intval($ligne["transparent"] ?? 0)==1 || intval($ligne["TProxy"] ?? 0)==1){echo "Managed by transparent mode";return;}
	$q->QUERY_SQL("DELETE FROM proxy_ports WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;return;}
	admin_tracks("Delete proxy listen port ".intval($ligne["port"] ?? 0)." (ID $ID)");
	$q->QUERY_SQL("DELETE FROM squid_balancers WHERE ID=$ID");
	CheckPointers();
}
function connected_port_popup_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["port-popup-start"]);
    $html[]="<div id='connected-port-popup-$ID'></div>";
    $html[]="<script>LoadAjax('connected-port-popup-$ID','$page?port-popup=$ID');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function connected_port_popup():bool{
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
        $Params=unserialize($ligne["Params"]);
    }

    if(!isset($Params["tcpkeepalive"]["enabled"])){$Params["tcpkeepalive"]["enabled"]=0;}
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

    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $subcertificates=array();
    foreach ($cdb->subList() as $r){
        if(intval($r->Certype ?? 0)!==1){continue;}
        $cn=(string)($r->commonName ?? "");
        $on=(string)($r->organizationName ?? "");
        $subcertificates[intval($r->ID)]="$cn ($on)";
    }
    $keepalive_class="label-primary";
    $keepalive_txt="{active2}";
    if($Params["tcpkeepalive"]["enabled"]==0){
        $keepalive_class="label-default";
        $keepalive_txt="{disabled}";
    }


	$form[] = $tpl->field_hidden("SaveConnectedPort",$ID);
	$form[] = $tpl->field_checkbox("enabled","{enabled}",$ligne["enabled"],true);
	$form[] = $tpl->field_numeric("port","{listen_port}",$ligne["port"]);
    $form[] = $tpl->td_button("TCP Keepalive Timeout", "$keepalive_txt: {modify}", "Loadjs('fw.proxy.ports.tcpkeepalive.php?portid=$ID');",null,null,$keepalive_class);
	$form[] = $tpl->field_text("PortName", "{service_name2}",  $ligne["PortName"]);
	$form[] = $tpl->field_text("xnote", "{description}",  $ligne["xnote"]);
	$form[] = $tpl->field_checkbox("ProxyProtocol","{proxy_protocol}",$ligne["ProxyProtocol"],false,"{proxy_protocol_explain}");
	
	$form[]=$tpl->field_checkbox("NoAuth","{disable_authentication}",$ligne["NoAuth"]);
	$form[]=$tpl->field_checkbox("NoCache","{disable_cache}",$ligne["NoCache"]);
	$form[]=$tpl->field_checkbox("NoFilter","{disable_webfiltering}",$ligne["NoFilter"]);
    $form[]=$tpl->field_checkbox("AuthParentPort","{AuthParentPort}",$ligne["AuthParentPort"]);

    if($CountOfInterfaces>1) {
        $form[] = $tpl->field_interfaces("nic", "{listen_interface}", $ligne["nic"]);
        $form[] = $tpl->field_interfaces("outgoing_addr", "{forward_interface}", $ligne["outgoing_addr"]);
    }else{
        $tpl->field_hidden("nic",null);
        $tpl->field_hidden("outgoing_addr",null);
    }


	if(count($subcertificates)>0) {
        $subcertificates[0]="{none}";
        $form[] = $tpl->field_section("{secure_proxy}","{secure_proxy_explain}");
        $form[] = $tpl->field_checkbox("SSLPort", "{explicit_https_port}", $ligne["SSLPort"], false, "{explicit_https_port_explain}");
        $form[] = $tpl->field_checkbox("AuthSSL", "{authenticate_ssl_client}", $ligne["AuthSSL"], false, "{authenticate_ssl_client_explain}");
        $form[] = $tpl->field_array_hash($subcertificates, "subcertificate", "{server_certificate}",$ligne["subcertificate"]);
    }else{
        $ligne["SSLPort"]=0;
	    $tpl->field_hidden("SSLPort",0);
        $tpl->field_hidden("subcertificate",0);
        $tpl->field_hidden("AuthSSL",0);
    }

    if($ligne["SSLPort"]==0) {
        $form[] = $tpl->field_section("{activate_ssl_on_http_port}","{activate_ssl_on_http_port_explain}");
        $form[] = $tpl->field_checkbox("UseSSL", "{decrypt_ssl}", $ligne["UseSSL"], "sslcertificate", "{listen_port_ssl_explain}");
        if(sslproxy_meta_ca_held()){
            // AC de decryptage tenue par Artica Meta : chaque port SSL porte l'AC Meta, choix verrouille
            $metaCA=sslproxy_meta_ca_name();
            $form[] = $tpl->field_info("SSLProxyMetaROsslcertificate", "{use_certificate_from_certificate_center}", $metaCA===""?"-":sslproxy_meta_h($metaCA), "<i class='fa-solid fa-lock'></i>&nbsp;{sslproxy_meta_ca_locked}");
            $form[] = $tpl->field_hidden("sslcertificate", $metaCA);
        }else{
            $form[] = $tpl->field_array_hash($sslcertificates, "sslcertificate", "{use_certificate_from_certificate_center}", $ligne["sslcertificate"]);
            if($ID>0) {
                $form[] = $tpl->td_button("{create_certificate}", "{wizard}: {create_certificate}", "Loadjs('fw.proxy.ports.sslwizard.php?portid=$ID');");
            }
        }
    }else{
        $tpl->field_hidden("UseSSL",1);
        $tpl->field_hidden("sslcertificate",null);
    }
	$security="AsSquidAdministrator";
	$html[]=$tpl->form_outside($title, @implode("\n", $form),null,$btname,$jsafter,$security);
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}


function connected_port_save(){
		$users=new usersMenus();
		if(!$users->AsSquidAdministrator){echo "Permission denied";return;}
		$tpl=new template_admin();
		$tpl->CLEAN_POST_XSS();
		
		// Pentest 2026-11-04 : SaveConnectedPort etait concatene (sans quotes) dans la requete.
		$ID=intval($_POST["SaveConnectedPort"]);
		// Pentest 2026-11-04 : ipaddr, enabled, outgoing_addr, UseSSL, sslcertificate et nic etaient concatenes
		// dans la requete ; xnote/PortName etaient echappes a la mode MySQL (\') ce qui ne protege rien en SQLite.
		$ipaddr=sqlite_escape_string2((string)$_POST["ipaddr"]);
		$port=intval($_POST["port"]);
		$xnote=sqlite_escape_string2((string)$_POST["xnote"]);
		$PortName=sqlite_escape_string2((string)$_POST["PortName"]);
		$enabled=intval($_POST["enabled"]);
		$transparent=intval($_POST["transparent"]);
		$TProxy=intval($_POST["TProxy"]);
		$Parent=intval($_POST["Parent"]);
		$outgoing_addr=sqlite_escape_string2((string)$_POST["outgoing_addr"]);
		$UseSSL=intval($_POST["UseSSL"]);
		$sslcertificate=sqlite_escape_string2((string)$_POST["sslcertificate"]);
		$WCCP=intval($_POST["WCCP"]);
		$ICP=intval($_POST["ICP"]);
		$FTP=intval($_POST["FTP"]);
		$WANPROXY=intval($_POST["WANPROXY"]);
		$WANPROXY_PORT=intval($_POST["WANPROXY_PORT"]);
		$FTP_TRANSPARENT=intval($_POST["FTP_TRANSPARENT"]);
		$MIKROTIK_PORT=intval($_POST["MIKROTIK_PORT"]);
		$SOCKS=intval($_POST["SOCKS"]);
		$NoAuth=intval($_POST["NoAuth"]);
	    $SSLPort=intval($_POST["SSLPort"]);
		$NoCache=intval($_POST["NoCache"]);
		$NoFilter=intval($_POST["NoFilter"]);
		$subcertificate=intval($_POST["subcertificate"]);
        $AuthSSL=intval($_POST["AuthSSL"]);
        $AuthParentPort=intval($_POST["AuthParentPort"]);
	    $ProxyProtocol=intval($_POST["ProxyProtocol"]);
		$nic=sqlite_escape_string2((string)$_POST["nic"]);

		if($SSLPort==1){
            $UseSSL=1;
		    if($subcertificate==0){
		        echo "jserror:Please choose a Server certificate";
		        return;
            }
            $sslcertificate=null;
        }

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
			$SSLPort=0;
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
            if($SSLPort==0) {
                if ($port == 443) {
                    echo "$port 443 SSL port not allowed!\n";
                    return;
                }
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

		if($WCCP==1){
			$WANPROXY_PORT=0;
			if($nic==null){
				echo $tpl->javascript_parse_text("{wccp_error_interface}");
				return;
			}
	
		}
	
	
		// AC de decryptage tenue par Artica Meta : un port SSL porte l'AC Meta ; choisir un autre
		// certificat est refuse (meme regle que articarest squid.sslpPortCertDecision)
		if($UseSSL==1 && $SSLPort==0 && sslproxy_meta_ca_held()){
		    $currentCert="";
		    if($ID>0){
		        $qc=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		        $lc=$qc->mysqli_fetch_array("SELECT sslcertificate FROM proxy_ports WHERE ID=$ID");
		        $currentCert=is_array($lc)?(string)($lc["sslcertificate"] ?? ""):"";
		    }
		    if(sslproxy_meta_cert_refused((string)($_POST["sslcertificate"] ?? ""),$currentCert)){
		        echo $tpl->javascript_parse_text("{sslproxy_meta_ca_locked}");
		        return;
		    }
		    $metaCA=sslproxy_meta_ca_name();
		    if($metaCA!==""){$sslcertificate=sqlite_escape_string2($metaCA);}
		}
		$is_nat=intval($_POST["is_nat"]);
		$zMD5=md5(serialize($_POST));
		$sqladd="INSERT INTO proxy_ports (WANPROXY_PORT,WANPROXY,FTP,ICP,Parent,WCCP,is_nat,nic,ipaddr,port,xnote,enabled,transparent,TProxy,outgoing_addr,PortName,UseSSL,sslcertificate,NoAuth,MIKROTIK_PORT,FTP_TRANSPARENT,SOCKS,ProxyProtocol,NoCache,NoFilter,zMD5,AuthForced,AuthPort,NoHotspot,SSLPort,subcertificate,AuthSSL,AuthParentPort)
		VALUES ('$WANPROXY_PORT','$WANPROXY','$FTP','$ICP','$Parent','$WCCP','$is_nat','$nic','$ipaddr','$port','$xnote','$enabled','$transparent','$TProxy','$outgoing_addr','$PortName','$UseSSL','$sslcertificate',$NoAuth,'$MIKROTIK_PORT','$FTP_TRANSPARENT','$SOCKS','$ProxyProtocol',$NoCache,$NoFilter,'$zMD5',0,0,0,$SSLPort,'$subcertificate',$AuthSSL,$AuthParentPort)";
	
	
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");

    if(!$q->FIELD_EXISTS("proxy_ports","NoHotspot")){
        $q->QUERY_SQL("ALTER TABLE proxy_ports ADD NoHotspot INTEGER NOT NULL DEFAULT '0'");
    }
    if(!$q->FIELD_EXISTS("proxy_ports","AuthParentPort")){
        $q->QUERY_SQL("ALTER TABLE proxy_ports ADD AuthParentPort INTEGER NOT NULL DEFAULT '0'");
    }

    if(!$q->FIELD_EXISTS("proxy_ports","SSLPort")){
        $q->QUERY_SQL("ALTER TABLE proxy_ports ADD SSLPort INTEGER NOT NULL DEFAULT '0'");
    }

    if(!$q->FIELD_EXISTS("proxy_ports","subcertificate")){
        $q->QUERY_SQL("ALTER TABLE proxy_ports ADD subcertificate INTEGER NOT NULL DEFAULT '0'");
    }
    if(!$q->FIELD_EXISTS("proxy_ports","AuthSSL")){
        $q->QUERY_SQL("ALTER TABLE proxy_ports ADD AuthSSL INTEGER NOT NULL DEFAULT '0'");
    }



	
		if($ID==0){
			$sql="SELECT ID,PortName FROM proxy_ports WHERE port='$port' AND nic='$nic'";
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
		SSLPort='$SSLPort',
		AuthSSL='$AuthSSL',
		subcertificate='$subcertificate',
		sslcertificate='$sslcertificate',
		ProxyProtocol='$ProxyProtocol',
        AuthParentPort ='$AuthParentPort',                       
		`NoAuth`='$NoAuth', `NoCache`='$NoCache', `NoFilter`='$NoFilter',`NoHotspot`='0'
		WHERE ID=$ID";
	
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
		// form_outside prend toute reponse de plus de 3 caracteres pour une erreur :
		// l'ancien "echo last_id" faisait echouer l'ajout des que l'ID passait 999.
		if(!$q->ok){echo $q->mysql_error;return;}
		admin_tracks(($ID>0?"Update":"Create")." proxy listen port $port".($ID>0?" (ID $ID)":""));
		CheckPointers();
	
	
	}
	function CheckPointers(){
		$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND is_nat=1";
		$ligne=$q->mysqli_fetch_array($sql);
		$sock=new sockets();
		if($ligne["tcount"]>0){
		    $sock->SET_INFO("EnableTransparent27", 1);
		}else{
		      $sock->SET_INFO("EnableTransparent27", 0);
		}
	
		$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND WCCP=1";
		$ligne=$q->mysqli_fetch_array($sql);
		$sock=new sockets();
		if($ligne["tcount"]>0){$sock->SET_INFO("SquidWCCPEnabled", 1);}else{$sock->SET_INFO("SquidWCCPEnabled", 0); }
	
		$sql="SELECT COUNT(*) as tcount FROM proxy_ports WHERE enabled=1 AND FTP=1";
		$ligne=$q->mysqli_fetch_array($sql);
		$sock=new sockets();
		if($ligne["tcount"]>0){$sock->SET_INFO("ServiceFTPEnabled", 1);}else{$sock->SET_INFO("ServiceFTPEnabled", 0); }
	
	
	}	

function page():bool{
	$page=CurrentPageName();
    $tpl=new template_admin();
    
    $html=$tpl->page_header("{listen_ports}","fad fa-plug",
        "{listen_ports_v4_explain}","$page?tabs=yes","proxy-ports","progress-squid-ports-restart");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return true;
	}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/privs");
	echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{connected_ports}"]="$page?connected-ports=yes";
	$array["{transparent_ports}"]="fw.proxy.transparent.php";
    //$array["Fortigate"]="fw.proxy.transparent.fortigate.php";
   // $array["WCCP"]="fw.proxy.wccp.php";
    $array["{remote_ports}"]="fw.proxy.general.php?SafePorts=yes";
	$array["{communications_ports}"]="$page?communications-ports=yes";
    $array["{troubleshooting}"]="$page?troubleshooting=yes";

	echo $tpl->tabs_default($array);
}
function connected_port_section(){
	$page=CurrentPageName();
	echo "<div id='table-connected-proxy-ports'></div><script>LoadAjax('table-connected-proxy-ports','$page?connected-ports-list=yes');</script>";
}
/**
 * ===========================================================================
 * Onglet "Connected ports" (maquette stitch_proxy_ports_administration_console)
 * Un modele unique (ppc_model) alimente chaque widget ; un widget = une fonction.
 * CSS et JS concatenes HORS de _ENGINE_parse_body() (le regex des tokens prend
 * les accolades CSS/JS pour des {tokens}).
 * ===========================================================================
 */

/**
 * Sonde TCP d'un port d'ecoute : la box ouvre une connexion vers elle-meme.
 * Retourne array(etat ok|fail|off, message systeme). C'est la SEULE partie
 * volatile d'une ligne ; tout le reste ne bouge qu'a l'enregistrement.
 */
function pp_probe($host,$port,$enabled=1):array{
    if(intval($enabled)==0){return array("off","");}
    $errno=0;$errstr="";
    $fp=@fsockopen($host,intval($port),$errno,$errstr,1);
    if($fp){
        @fclose($fp);
        return array("ok","");
    }
    return array("fail",strval($errstr));
}

/**
 * Resout l'identifiant de ligne en (hote, port, actif).
 * Les lignes systeme (port manager, tproxy HaCluster, port sans authentification)
 * n'ont pas d'ID en base : elles portent un identifiant fixe.
 */
function pp_target($rid):array{
    $rid=(string)$rid;
    if($rid=="mgr"){
        return array("127.0.0.1",intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort")),1);
    }
    if($rid=="tproxy"){
        return array("127.0.0.1",intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTproxy")),1);
    }
    if($rid=="noauth"){
        return array("127.0.0.1",intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterNoAuthPort")),1);
    }
    if(preg_match("#^p([0-9]+)$#",$rid,$re)){
        $ID=intval($re[1]);
        $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT nic,port,enabled,transparent,TProxy FROM proxy_ports WHERE ID='$ID'");
        // Une ligne absente renvoie un tableau vide, pas false : sans ce test la sonde
        // aurait porte sur le port 0 et la cellule serait passee a "desactive".
        if(!is_array($ligne)){return array(null,0,0);}
        if(intval($ligne["port"] ?? 0)<=0){return array(null,0,0);}
        $host="127.0.0.1";
        if(strval($ligne["nic"] ?? "")<>""){
            $znic=new system_nic($ligne["nic"]);
            $host=$znic->IPADDR;
        }
        $enabled=intval($ligne["enabled"]);
        if(intval($ligne["transparent"] ?? 0)==1 || intval($ligne["TProxy"] ?? 0)==1){$enabled=1;}
        return array($host,intval($ligne["port"]),$enabled);
    }
    return array(null,0,0);
}

/**
 * Rafraichissement d'UNE ligne : re-sonde ce seul port, remplace sa cellule
 * d'etat et recalcule les compteurs (tuile "Answering", alerte).
 */
function pp_row_status(){
    header("content-type: application/x-javascript");
    $rid=preg_replace('/[^A-Za-z0-9]/','',(string)$_GET["row-status"]);
    if($rid==""){return;}
    $tpl=new template_admin();
    list($host,$port,$enabled)=pp_target($rid);
    if($host===null){return;}
    list($state,$err)=pp_probe($host,$port,$enabled);
    $payload=base64_encode(ppc_nobr($tpl->_ENGINE_parse_body(ppc_status_inner($state,$err))));
    echo "(function(){var e=document.getElementById('ppc-st-$rid');if(!e){return;}";
    echo "e.innerHTML=base64_decode('$payload');e.setAttribute('data-ppc-st','$state');";
    echo "if(window.PpcRecount){PpcRecount();}})();";
}

/**
 * Les tokens finissent leurs phrases par [br] (exigence de translate-mcp) ; aucun
 * widget de cet onglet ne veut de saut de ligne, et un <br> dans un title=""
 * casserait l'attribut : on les remplace par une espace apres le parse.
 */
function ppc_nobr($html):string{
    return (string)preg_replace('#\s*<br\s*/?>\s*#i'," ",(string)$html);
}

/** Adresses IPv4 reelles de la box (cartes actives), pour "All interfaces". */
function ppc_local_ips():array{
    $out=array();
    if(!class_exists("networking")){return $out;}
    $ip=new networking();
    $interfaces=$ip->Local_interfaces();
    if(!is_array($interfaces)){return $out;}
    foreach($interfaces as $eth){
        if($eth=="lo"){continue;}
        if(preg_match("#^(gre|dummy)#",$eth)){continue;}
        $nic=new system_nic($eth);
        if(intval($nic->enabled)==0){continue;}
        $addr=trim(strval($nic->IPADDR));
        if($addr=="" || $addr=="0.0.0.0" || $addr=="127.0.0.1"){continue;}
        $out[]=$addr;
    }
    return array_values(array_unique($out));
}

/** Une pastille de fonction : array(type, icone, libelle, info-bulle, js). */
function ppc_chip($kind,$ico,$label,$tip="",$js=""):array{
    return array("kind"=>$kind,"ico"=>$ico,"label"=>$label,"tip"=>$tip,"js"=>$js);
}

/** Ligne systeme (port interne, HaCluster) : jamais modifiable ici. */
function ppc_sys_port($rid,$port,$title,$sub,$addr,$chips):array{
    list($state,$err)=pp_probe("127.0.0.1",$port,1);
    return array("rid"=>$rid,"sys"=>true,"id"=>0,"port"=>intval($port),"name"=>$title,"note"=>"",
        "addr"=>$addr,"addr_sub"=>$sub,"copy"=>"","enabled"=>1,"structural"=>true,
        "chips"=>$chips,"cert"=>"","cert_err"=>"","warn"=>"","state"=>$state,"err"=>$err,"noauth"=>false);
}

/** Modele unique de l'onglet : lu une fois, partage par tous les widgets. */
function ppc_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $users=new usersMenus();
    $m=array();
    $m["admin"]=(bool)$users->AsSquidAdministrator;
    $m["slave"]=intval($sock->GET_INFO("PowerDNSEnableClusterSlave"))==1;
    $m["urgency"]=intval($sock->GET_INFO("SquidSSLUrgency"))==1;
    $AsFilter=true;
    if(intval($sock->GET_INFO("EnableUfdbGuard"))==0){$AsFilter=false;}
    if(!$users->APP_UFDBGUARD_INSTALLED){$AsFilter=false;}
    $m["filter"]=$AsFilter;
    $m["ips"]=ppc_local_ips();
    $m["ports"]=array();
    $m["system"]=array();

    // ---- ports systeme --------------------------------------------------------
    $mgr=intval($sock->GET_INFO("SquidMgrListenPort"));
    $out="{all_interfaces}";
    $outIf=strval($sock->GET_INFO("SquidMgrOutGoingInterface"));
    if(strlen($outIf)>3){
        $nic=new system_nic($outIf);
        $out=htmlspecialchars($nic->NICNAME." ($outIf)");
    }
    $page=CurrentPageName();
    $outLink="<a href='javascript:blur();' class='ppc-link' OnClick=\"Loadjs('$page?SquidMgrOutGoingInterface-js=yes')\">$out</a>";
    $m["system"][]=ppc_sys_port("mgr",$mgr,"{ppc_sys_mgr}","{outgoing_interface}: $outLink","127.0.0.1",
        array(ppc_chip("info","fas fa-lock","{ppc_sys_mgr_chip}","{ppc_sys_mgr_tip}")));

    $HaClusterClient=intval($sock->GET_INFO("HaClusterClient"));
    if(!$sock->CORP_LICENSE()){$HaClusterClient=0;}
    $tproxy=intval($sock->GET_INFO("HaClusterTproxy"));
    if($HaClusterClient==1 && intval($sock->GET_INFO("HaClusterTransParentMode"))==1 && $tproxy>0){
        $m["system"][]=ppc_sys_port("tproxy",$tproxy,"{ppc_sys_tproxy}","","{all_interfaces}",
            array(ppc_chip("info","fas fa-network-wired","{ppc_c_proxyproto}","{ppc_c_proxyproto_tip}")));
    }
    if($HaClusterClient==1){
        $na=intval($sock->GET_INFO("HaClusterNoAuthPort"));
        $m["system"][]=ppc_sys_port("noauth",$na,"{ppc_sys_noauth}","","{all_interfaces}",
            array(ppc_chip("info","fas fa-user-slash","{ppc_c_noauth}","{ppc_sys_noauth_tip}"),
                  ppc_chip("info","fas fa-network-wired","{ppc_c_proxyproto}","{ppc_c_proxyproto_tip}")));
    }

    // ---- ports de l'administrateur -----------------------------------------
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM proxy_ports ORDER BY port");
    if(!is_array($results)){$results=array();}
    foreach($results as $ligne){
        $p=ppc_user_port($ligne,$m);
        // Un port du mode transparent ne se saisit dans aucun navigateur : il rejoint
        // les ports systeme et sort des compteurs de l'administrateur.
        if($p["structural"]){$p["sys"]=true;$p["copy"]="";$m["system"][]=$p;continue;}
        $m["ports"][]=$p;
    }

    // ---- compteurs ---------------------------------------------------------
    $m["n_total"]=count($m["ports"]);
    $m["n_enabled"]=0;$m["n_ok"]=0;$m["n_noauth"]=0;$m["n_cert"]=0;$m["n_fail"]=0;
    foreach($m["ports"] as $p){
        if($p["state"]!="off"){$m["n_enabled"]++;}
        if($p["state"]=="ok"){$m["n_ok"]++;}
        if($p["state"]=="fail"){$m["n_fail"]++;}
        if($p["noauth"] && $p["state"]!="off"){$m["n_noauth"]++;}
        if($p["cert_err"]<>""){$m["n_cert"]++;}
    }
    foreach($m["system"] as $p){
        if($p["state"]=="fail"){$m["n_fail"]++;}
        if($p["cert_err"]<>""){$m["n_cert"]++;}
    }
    $m["n_disabled"]=$m["n_total"]-$m["n_enabled"];
    return $m;
}

/** Une ligne de proxy_ports traduite en mots courants. */
function ppc_user_port(array $ligne,array $m):array{
    $page=CurrentPageName();
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $ID=intval($ligne["ID"]);
    $port=intval($ligne["port"]);
    $enabled=intval($ligne["enabled"]);
    $structural=(intval($ligne["transparent"] ?? 0)==1 || intval($ligne["TProxy"] ?? 0)==1);
    if($structural){$enabled=1;}
    $SSLPort=intval($ligne["SSLPort"] ?? 0);
    // Meme regle que le demon (squid/sslproxy_meta_ca.go sslpProxySSLWhere) : un port
    // inspecte le HTTPS si UseSSL=1 ET SSLPort=0. Un port Secure Proxy force UseSSL=1
    // sans dechiffrer : l'ancien affichage y collait "Decrypt SSL" a tort.
    $decrypt=(intval($ligne["UseSSL"] ?? 0)==1 && $SSLPort==0);
    $noauth=(intval($ligne["NoAuth"] ?? 0)==1 && !$structural);

    $nic=strval($ligne["nic"] ?? "");
    $host="127.0.0.1";
    $addr="{all_interfaces}";
    $addrSub="{ppc_all_ifaces_sub}";
    $copyIp=count($m["ips"])>0?$m["ips"][0]:"";
    if($nic<>""){
        $znic=new system_nic($nic);
        $ip=trim(strval($znic->IPADDR));
        $host=$ip;
        if($ip<>"" && $ip<>"0.0.0.0"){
            $addr=htmlspecialchars("$ip ($nic)");
            $addrSub="{ppc_iface_sub}";
            $copyIp=$ip;
        }
    }elseif(count($m["ips"])>0){
        $addrSub="{ppc_all_ifaces_sub} &middot; ".htmlspecialchars(implode(", ",array_slice($m["ips"],0,3)));
    }
    list($state,$err)=pp_probe($host,$port,$enabled);

    $chips=array();
    if($structural){
        $chips[]=ppc_chip("info","fas fa-lock","{ppc_c_managed}","{ppc_c_managed_tip}");
    }else{
        if($noauth){
            $chips[]=ppc_chip("warn","fas fa-user-slash","{ppc_c_noauth}","{ppc_c_noauth_tip}");
        }else{
            $chips[]=ppc_chip("on","fas fa-user-lock","{ppc_c_login}","{ppc_c_login_tip}");
        }
    }
    if(intval($ligne["NoCache"] ?? 0)==0){
        $chips[]=ppc_chip("on","fas fa-database","{ppc_c_cache}","{ppc_c_cache_tip}");
    }else{
        $chips[]=ppc_chip("off","fas fa-ban","{ppc_c_nocache}","{ppc_c_nocache_tip}");
    }
    if($m["filter"]){
        if(intval($ligne["NoFilter"] ?? 0)==0){
            $chips[]=ppc_chip("on","fas fa-filter","{ppc_c_filter}","{ppc_c_filter_tip}");
        }else{
            $chips[]=ppc_chip("off","fas fa-ban","{ppc_c_nofilter}","{ppc_c_nofilter_tip}");
        }
    }
    $cert="";$certErr="";$warn="";
    if($SSLPort==1){
        $chips[]=ppc_chip("on","fas fa-shield-halved","{ppc_c_secure}","{ppc_c_secure_tip}");
        if(intval($ligne["AuthSSL"] ?? 0)==1){
            $chips[]=ppc_chip("on","fas fa-id-card","{ppc_c_clientcert}","{ppc_c_clientcert_tip}");
        }
        $sub=intval($ligne["subcertificate"] ?? 0);
        $json=json_decode($sock->REST_API("/certificate/server/$sub/0"));
        if(!is_object($json) || !$json->Status){
            $msg=is_object($json)?strval($json->Error ?? ""):"";
            $certErr="{invalid_certificate}".($msg<>""?" &mdash; ".htmlspecialchars($msg):"");
        }
    }
    if($decrypt){
        $kind=$m["urgency"]?"warn":"on";
        $chips[]=ppc_chip($kind,"fas fa-magnifying-glass","{ppc_c_inspect}","{ppc_c_inspect_tip}",
            "Loadjs('fw.proxy.ports.ssl.php?port-id=$ID')");
        if($m["urgency"]){
            $warn="{ppc_cert_emergency}";
        }else{
            $sslcertificate=strval($ligne["sslcertificate"] ?? "");
            if(strlen($sslcertificate)>3){
                $keyout="/etc/squid3/ssl/".md5($sslcertificate).".dyn";
                if(!is_file($keyout)){
                    $certErr="{missing_certificate}";
                }else{
                    $arrayCert=@openssl_x509_parse(@file_get_contents($keyout));
                    if(!is_array($arrayCert) || !isset($arrayCert["issuer"])){
                        $certErr="{invalid_certificate}";
                    }else{
                        $owner=array();
                        foreach(array("O","OU") as $k){
                            $v=$arrayCert["issuer"][$k] ?? "";
                            if(is_array($v)){$v=implode(" ",$v);}
                            if(strval($v)<>""){$owner[]=strval($v);}
                        }
                        $cert="{ppc_cert} <strong>".htmlspecialchars($sslcertificate)."</strong>";
                        if(count($owner)>0){$cert.=" &middot; {ppc_owner}: ".htmlspecialchars(implode(", ",$owner));}
                    }
                }
            }
        }
    }
    if(intval($ligne["ProxyProtocol"] ?? 0)==1){
        $chips[]=ppc_chip("info","fas fa-network-wired","{ppc_c_proxyproto}","{ppc_c_proxyproto_tip}");
    }
    if(intval($ligne["AuthParentPort"] ?? 0)==1){
        $chips[]=ppc_chip("info","fas fa-people-arrows","{AuthParentPort}","");
    }

    $name=htmlspecialchars(strval($ligne["PortName"] ?? ""));
    return array("rid"=>"p$ID","sys"=>false,"id"=>$ID,"port"=>$port,"name"=>$name,
        "note"=>htmlspecialchars(strval($ligne["xnote"] ?? "")),
        "addr"=>$addr,"addr_sub"=>$addrSub,"copy"=>($copyIp<>""?"$copyIp:$port":""),
        "enabled"=>$enabled,"structural"=>$structural,"chips"=>$chips,
        "cert"=>$cert,"cert_err"=>$certErr,"warn"=>$warn,"state"=>$state,"err"=>$err,"noauth"=>$noauth,
        "js"=>"Loadjs('$page?port-js=$ID')");
}

/** Bandeau d'accroche + encadre "How to use it" avec un exemple reel. */
function ppc_banner(array $m):string{
    $ex="";
    foreach($m["ports"] as $p){
        if($p["state"]=="off" || $p["structural"] || $p["copy"]==""){continue;}
        $ex=$p["copy"];break;
    }
    $h=array();
    $h[]="<section class='ppc-card ppc-ban'>";
    $h[]="<div class='ppc-ban-main'><div class='ppc-ban-ico'><i class='fas fa-plug'></i></div><div>";
    $h[]="<h2>{ppc_ban_title}</h2><p>{ppc_ban_text}</p>";
    $h[]="<div class='ppc-chips'>";
    $h[]="<span class='ppc-rchip'><i class='fas fa-user-check'></i>{ppc_chip_identity}</span>";
    $h[]="<span class='ppc-rchip'><i class='fas fa-lock'></i>{ppc_chip_encrypt}</span>";
    $h[]="<span class='ppc-rchip'><i class='fas fa-heart-pulse'></i>{ppc_chip_tested}</span>";
    $h[]="</div></div></div>";
    $h[]="<aside class='ppc-howto'><div class='ppc-howto-t'><i class='far fa-circle-question'></i>{ppc_howto}</div>";
    $h[]="<p>{ppc_howto_text}</p>";
    if($ex<>""){
        $exs=htmlspecialchars(str_replace(":"," : ",$ex));
        $h[]="<div class='ppc-howto-ex'><span class='ppc-mono'><i class='fas fa-network-wired'></i>$exs</span>";
        $h[]=ppc_copy_button($ex);
        $h[]="</div>";
    }
    $h[]="</aside></section>";
    return implode("",$h);
}

/** Bouton copier (repli execCommand dans le JS). */
function ppc_copy_button($text):string{
    $t=htmlspecialchars($text,ENT_QUOTES);
    return "<button type='button' class='ppc-ibtn' aria-label='{ppc_copy}' title='{ppc_copy} $t' OnClick=\"PpcCopy('$t',this)\"><i class='far fa-copy'></i></button>";
}

/** Alerte : mode d'urgence SSL (rendue seulement quand il est actif). */
function ppc_alert_emergency(array $m):string{
    if(!$m["urgency"]){return "";}
    $act="";
    if($m["admin"]){
        $act="<button type='button' class='ppc-al-act' OnClick=\"Loadjs('fw.proxy.ssl-emergency.remove.php')\">{disable_emergency_mode}</button>";
    }
    return "<div class='ppc-al ppc-al-err' role='alert'><i class='fas fa-triangle-exclamation'></i>"
        ."<span><strong>{proxy_in_ssl_emergency_mode}:</strong> {ppc_al_emergency}</span>$act</div>";
}

/** Alerte : ports qui ne repondent pas (toujours emise, masquee a zero : le JS la tient a jour). */
function ppc_alert_fail(array $m):string{
    $hidden=$m["n_fail"]==0?" hidden":"";
    $act="";
    if($m["admin"]){
        $act="<button type='button' class='ppc-al-act' OnClick=\"Loadjs('fw.proxy.apply.ports.php');\">{reconfigure_proxy_ports_restart}</button>";
    }
    return "<div class='ppc-al ppc-al-err' id='ppc-al-fail' role='alert'$hidden><i class='fas fa-circle-xmark'></i>"
        ."<span id='ppc-al-fail-t'>".ppc_count_text($m["n_fail"],"{ppc_al_fail_one}","{ppc_al_fail_many}")."</span>$act</div>";
}

/** Alerte : certificat manquant ou invalide, avec un filtre sur les lignes touchees. */
function ppc_alert_cert(array $m):string{
    if($m["n_cert"]==0){return "";}
    return "<div class='ppc-al ppc-al-warn' role='alert'><i class='fas fa-certificate'></i>"
        ."<span>".ppc_count_text($m["n_cert"],"{ppc_al_cert_one}","{ppc_al_cert_many}")."</span>"
        ."<button type='button' class='ppc-al-act' id='ppc-cert-btn' OnClick=\"PpcCertFilter()\">{ppc_al_cert_act}</button></div>";
}

/** Les %X vivent DANS la traduction : traduire d'abord, remplacer ensuite. */
function ppc_count_text($n,$one,$many):string{
    if(intval($n)==1){return $one;}
    $tpl=new template_admin();
    return str_replace("%N","<strong>".intval($n)."</strong>",$tpl->_ENGINE_parse_body($many));
}

/** Tuile "Your ports" : total, actifs, desactives. */
function ppc_kpi_ports(array $m):string{
    $tpl=new template_admin();
    $sub=str_replace(array("%E","%D"),array("<strong>{$m["n_enabled"]}</strong>","<strong>{$m["n_disabled"]}</strong>"),$tpl->_ENGINE_parse_body("{ppc_k_ports_sub}"));
    return "<div class='ppc-card ppc-kpi'><div class='ppc-kpi-h'><span>{ppc_k_ports}</span><i class='fas fa-plug'></i></div>"
        ."<div class='ppc-kpi-v'><b>{$m["n_total"]}</b><span>$sub</span></div></div>";
}

/** Tuile "Answering" : ports repondants sur ports actifs, donut mesure contre les ports actifs. */
function ppc_kpi_answering(array $m):string{
    $en=$m["n_enabled"];$ok=$m["n_ok"];
    $pct=$en>0?round($ok*100/$en):0;
    $cls=$en==0?"ppc-kpi-none":($ok<$en?"ppc-kpi-bad":"");
    return "<div class='ppc-card ppc-kpi $cls' id='ppc-k-ans'><div class='ppc-kpi-h'><span>{ppc_k_ok}</span><i class='fas fa-heart-pulse'></i></div>"
        ."<div class='ppc-kpi-row'><div class='ppc-kpi-v'><b><span id='ppc-k-ok'>$ok</span> / <span id='ppc-k-en'>$en</span></b>"
        ."<span id='ppc-k-ans-sub'>".($en==0?"{ppc_k_ok_none}":"{ppc_k_ok_sub}")."</span></div>"
        ."<svg class='ppc-donut' viewBox='0 0 36 36' role='img' aria-label='$ok / $en'>"
        ."<circle cx='18' cy='18' r='15.9155' class='ppc-donut-bg'/>"
        ."<circle cx='18' cy='18' r='15.9155' class='ppc-donut-fg' id='ppc-donut-fg' stroke-dasharray='$pct 100'/></svg></div></div>";
}

/** Tuile "Without login" : ports actifs sans authentification. */
function ppc_kpi_noauth(array $m):string{
    $n=$m["n_noauth"];
    $cls=$n>0?"ppc-kpi-warn":"";
    $sub=$n>0?"{ppc_k_noauth_sub}":"{ppc_k_noauth_zero}";
    return "<div class='ppc-card ppc-kpi $cls'><div class='ppc-kpi-h'><span>{ppc_k_noauth}</span><i class='fas fa-user-slash'></i></div>"
        ."<div class='ppc-kpi-v'><b>$n</b><span>$sub</span></div></div>";
}

/** Contenu de la cellule d'etat (aussi renvoye par pp_row_status). */
function ppc_status_inner($state,$err):string{
    if($state=="ok"){
        return "<span class='ppc-pill ppc-pill-ok'><i class='fas fa-circle-check'></i>{ppc_st_ok}</span><div class='ppc-sub'>{ppc_st_ok_sub}</div>";
    }
    if($state=="off"){
        return "<span class='ppc-pill ppc-pill-off'><i class='fas fa-circle-pause'></i>{ppc_st_off}</span><div class='ppc-sub'>{ppc_st_off_sub}</div>";
    }
    $msg=trim(strval($err));
    $msg=$msg==""?"{ppc_st_fail_nomsg}":htmlspecialchars($msg);
    return "<span class='ppc-pill ppc-pill-fail'><i class='fas fa-circle-xmark'></i>{ppc_st_fail}</span>"
        ."<div class='ppc-err'>$msg</div><div class='ppc-sub'>{ppc_st_fail_hint}</div>";
}

/** Cellule "Address to configure". */
function ppc_cell_addr(array $p):string{
    $cls=($p["state"]=="off")?" ppc-dim":"";
    $num="<span class='ppc-port ppc-mono'>{$p["port"]}</span>";
    if(!$p["sys"]){
        $num="<a href='javascript:blur();' class='ppc-port ppc-mono' OnClick=\"{$p["js"]}\">{$p["port"]}</a>";
    }
    $h="<div class='ppc-addr$cls'><div class='ppc-addr-l'>$num";
    // Un nom long reste sur la ligne du port (points de suspension), le nom complet en info-bulle.
    if($p["name"]<>""){$h.="<span class='ppc-tag ppc-tag-name' title=\"{$p["name"]}\">{$p["name"]}</span>";}
    if($p["copy"]<>""){$h.=ppc_copy_button($p["copy"]);}
    $h.="</div><div class='ppc-addr-ip'>{$p["addr"]}</div>";
    if($p["addr_sub"]<>""){$h.="<div class='ppc-sub'>{$p["addr_sub"]}</div>";}
    return $h."</div>";
}

/** Cellule "What this port does" : pastilles, certificat, description. */
function ppc_cell_does(array $p):string{
    $h="<div class='ppc-fchips'>";
    foreach($p["chips"] as $c){
        $tip=$c["tip"]<>""?" title=\"{$c["tip"]}\"":"";
        $inner="<i class='{$c["ico"]}'></i><span>{$c["label"]}</span>";
        if($c["js"]<>""){
            $h.="<a href='javascript:blur();' class='ppc-fc ppc-fc-{$c["kind"]}'$tip OnClick=\"{$c["js"]}\">$inner</a>";
        }else{
            $h.="<span class='ppc-fc ppc-fc-{$c["kind"]}'$tip>$inner</span>";
        }
    }
    $h.="</div>";
    if($p["cert"]<>""){$h.="<div class='ppc-cert'><i class='fas fa-certificate'></i><span>{$p["cert"]}</span></div>";}
    if($p["cert_err"]<>""){$h.="<div class='ppc-err'><i class='fas fa-circle-exclamation'></i> {$p["cert_err"]}</div>";}
    if($p["warn"]<>""){$h.="<div class='ppc-warn'><i class='fas fa-triangle-exclamation'></i> {$p["warn"]}</div>";}
    if($p["note"]<>""){$h.="<div class='ppc-note'>{$p["note"]}</div>";}
    return $h;
}

/** Cellule "Actions" : modifier / supprimer, ou cadenas pour les ports structurels. */
function ppc_cell_actions(array $p,array $m):string{
    if($p["sys"]){return "";}
    $page=CurrentPageName();
    $h="<button type='button' class='ppc-ibtn' aria-label='{ppc_edit} {$p["port"]}' title='{ppc_edit}' OnClick=\"{$p["js"]}\"><i class='fas fa-pen'></i></button>";
    if($m["admin"]){
        $h.="<button type='button' class='ppc-ibtn ppc-ibtn-del' aria-label='{ppc_delete} {$p["port"]}' title='{ppc_delete}' OnClick=\"Loadjs('$page?port-delete-js={$p["id"]}')\"><i class='far fa-trash-can'></i></button>";
    }
    return $h;
}

/** Une ligne de tableau (ports de l'administrateur et ports systeme). */
function ppc_row(array $p,array $m):string{
    $cls="ppc-row";
    if($p["cert_err"]<>""){$cls.=" ppc-has-cert";}
    if($p["state"]=="off"){$cls.=" ppc-row-off";}
    $sys=$p["sys"]?"1":"0";
    $h="<tr class='$cls' id='ppc-row-{$p["rid"]}'>";
    $h.="<td>".ppc_cell_addr($p)."</td>";
    $h.="<td>".ppc_cell_does($p)."</td>";
    $h.="<td><div id='ppc-st-{$p["rid"]}' class='ppc-st' data-ppc-st='{$p["state"]}' data-sys='$sys'>".ppc_status_inner($p["state"],$p["err"])."</div></td>";
    $h.="<td class='ppc-acts'>".ppc_cell_actions($p,$m)."</td>";
    return $h."</tr>";
}

function ppc_thead():string{
    return "<thead><tr><th class='ppc-c-addr'>{ppc_col_addr}</th><th>{ppc_col_does}</th>"
        ."<th class='ppc-c-st'>{status}<div class='ppc-th-sub'>{ppc_col_status_sub}</div></th>"
        ."<th class='ppc-acts'>{actions}</th></tr></thead>";
}

/** Carte "Your ports" (ou son etat vide invitant). */
function ppc_table_user(array $m):string{
    $page=CurrentPageName();
    $canAdd=($m["admin"] && !$m["slave"]);
    $add="";
    if($canAdd){
        $add="<button type='button' class='ppc-btn' OnClick=\"Loadjs('$page?port-js=0')\"><i class='fas fa-plus'></i>{new_port}</button>";
    }
    $h=array();
    $h[]="<section class='ppc-card ppc-tcard'>";
    $h[]="<div class='ppc-th'><div><h3>{ppc_tbl_title}</h3><p>{ppc_tbl_text}</p></div>$add</div>";
    if(count($m["ports"])==0){
        $h[]=ppc_empty($canAdd);
        $h[]="</section>";
        return implode("",$h);
    }
    $h[]="<div class='ppc-scroll'><table class='ppc-tbl'>".ppc_thead()."<tbody id='ppc-rows'>";
    foreach($m["ports"] as $p){$h[]=ppc_row($p,$m);}
    $h[]="</tbody></table></div>";
    $h[]="<div class='ppc-foot'><span>{showing} {$m["n_total"]} / {$m["n_total"]} {ports}</span><span class='awc-pg' id='ppc-pager'></span></div>";
    $h[]="</section>";
    return implode("",$h);
}

/** Etat vide : une invitation, jamais une ligne grise "No data". */
function ppc_empty($canAdd):string{
    $page=CurrentPageName();
    $btn="";
    if($canAdd){
        $btn="<button type='button' class='ppc-btn' OnClick=\"Loadjs('$page?port-js=0')\"><i class='fas fa-plus'></i>{new_port}</button>";
    }
    return "<div class='ppc-empty'><div class='ppc-empty-ico'><i class='fas fa-plug-circle-plus'></i></div>"
        ."<h4>{ppc_empty_t}</h4><p>{ppc_empty}</p>$btn</div>";
}

/** Carte repliee "System ports". */
function ppc_table_system(array $m):string{
    if(count($m["system"])==0){return "";}
    $n=count($m["system"]);
    $open=0;
    foreach($m["system"] as $p){if($p["state"]=="fail"){$open=1;}}
    $h=array();
    $h[]="<details class='ppc-card ppc-sys'".($open?" open":"").">";
    $h[]="<summary><span class='ppc-sys-ico'><i class='fas fa-server'></i></span>";
    $h[]="<span class='ppc-sys-t'><b>{ppc_sys_t}</b><span>{ppc_sys_text}</span></span>";
    $h[]="<span class='ppc-tag'>$n</span><i class='fas fa-chevron-down ppc-sys-chev'></i></summary>";
    $h[]="<div class='ppc-scroll'><table class='ppc-tbl'>".ppc_thead()."<tbody>";
    foreach($m["system"] as $p){$h[]=ppc_row($p,$m);}
    $h[]="</tbody></table></div></details>";
    return implode("",$h);
}

function ppc_style():string{
    $accent="#10b981";$soft="rgba(16,185,129,.12)";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        $accent=$a["accent"];$soft=$a["accent_soft"];$dark=$a["accent_dark"];
    }
    $c=array();
    $c[]=".ppc{display:flex;flex-direction:column;gap:16px;padding:12px 2px 24px;color:#1e293b;font-size:14px}";
    $c[]=".ppc [hidden]{display:none!important}";
    $c[]=".ppc-mono{font-family:'JetBrains Mono',Menlo,Consolas,monospace}";
    $c[]=".ppc-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}";
    $c[]=".ppc-ban{display:flex;gap:24px;justify-content:space-between;align-items:flex-start;padding:24px 28px;border-left:4px solid $accent}";
    $c[]=".ppc-ban-main{display:flex;gap:16px;max-width:820px}";
    $c[]=".ppc-ban-ico{flex:0 0 48px;height:48px;border-radius:10px;background:$soft;color:$dark;display:flex;align-items:center;justify-content:center;font-size:22px}";
    $c[]=".ppc-ban h2{margin:0 0 6px;font-size:22px;font-weight:600;color:#0f172a}";
    $c[]=".ppc-ban p{margin:0;line-height:1.6;color:#475569}";
    $c[]=".ppc-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}";
    $c[]=".ppc-rchip{display:inline-flex;align-items:center;gap:7px;background:#f1f5f9;border-radius:999px;padding:5px 12px;font-size:13px;color:#334155}";
    $c[]=".ppc-rchip i{color:$dark}";
    $c[]=".ppc-howto{flex:0 0 330px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 18px}";
    $c[]=".ppc-howto-t{display:flex;gap:8px;align-items:center;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#334155;margin-bottom:8px}";
    $c[]=".ppc-howto-t i{color:$dark}";
    $c[]=".ppc-howto p{font-size:13px;color:#475569;margin:0 0 12px;line-height:1.5}";
    $c[]=".ppc-howto-ex{display:flex;align-items:center;justify-content:space-between;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;font-size:14px;color:#0f172a}";
    // Font Awesome en mode SVG+JS remplace le <i> : on espace via le conteneur, jamais via i.fa-*
    $c[]=".ppc-howto-ex .ppc-mono{display:inline-flex;align-items:center;gap:8px}";
    $c[]=".ppc-howto-ex .ppc-mono>*:first-child{color:#94a3b8}";
    $c[]=".ppc-al{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:8px;border:1px solid;font-size:14px}";
    $c[]=".ppc-al>span{flex:1}";
    $c[]=".ppc-al-err{background:#fef2f2;border-color:#fecaca;color:#b91c1c}";
    $c[]=".ppc-al-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}";
    $c[]=".ppc-al-act{background:none;border:0;padding:0;font-weight:600;text-decoration:underline;color:inherit;cursor:pointer;white-space:nowrap}";
    $c[]=".ppc-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}";
    $c[]=".ppc-kpi{padding:16px 20px;min-height:96px;display:flex;flex-direction:column;justify-content:space-between;gap:8px}";
    $c[]=".ppc-kpi-h{display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#475569}";
    $c[]=".ppc-kpi-h i{color:#94a3b8}";
    $c[]=".ppc-kpi-row{display:flex;justify-content:space-between;align-items:center;gap:12px}";
    $c[]=".ppc-kpi-v{display:flex;flex-wrap:wrap;align-items:baseline;gap:4px 12px}";
    $c[]=".ppc-kpi-v b{font-size:26px;font-weight:700;color:#0f172a;font-variant-numeric:tabular-nums}";
    $c[]=".ppc-kpi-v>span{font-size:13px;color:#64748b}";
    $c[]=".ppc-kpi-v strong{color:#0f172a}";
    $c[]=".ppc-kpi-warn{border-color:#fde68a;background:#fffbeb}";
    $c[]=".ppc-kpi-warn .ppc-kpi-h,.ppc-kpi-warn .ppc-kpi-h i,.ppc-kpi-warn b,.ppc-kpi-warn .ppc-kpi-v>span{color:#92400e}";
    $c[]=".ppc-kpi-bad b{color:#b91c1c}";
    $c[]=".ppc-donut{width:44px;height:44px;flex:0 0 44px;transform:rotate(-90deg)}";
    $c[]=".ppc-donut circle{fill:none;stroke-width:4.5}";
    $c[]=".ppc-donut-bg{stroke:#e2e8f0}";
    $c[]=".ppc-donut-fg{stroke:#047857;stroke-linecap:round;transition:stroke-dasharray .3s}";
    $c[]=".ppc-kpi-bad .ppc-donut-fg{stroke:#b91c1c}";
    $c[]=".ppc-kpi-none .ppc-donut-fg{stroke:#cbd5e1}";
    $c[]=".ppc-th{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:18px 24px;border-bottom:1px solid #e2e8f0}";
    $c[]=".ppc-th h3{margin:0 0 4px;font-size:16px;font-weight:600;color:#0f172a}";
    $c[]=".ppc-th p{margin:0;font-size:13px;color:#64748b;line-height:1.5}";
    $c[]=".ppc-btn{display:inline-flex;align-items:center;gap:8px;background:$dark;color:#fff;border:0;border-radius:8px;padding:9px 16px;font-size:14px;font-weight:600;cursor:pointer;white-space:nowrap}";
    $c[]=".ppc-btn:hover{filter:brightness(1.08)}";
    $c[]=".ppc-scroll{overflow-x:auto}";
    $c[]=".ppc-tbl{width:100%;border-collapse:collapse;color:#1e293b}";
    $c[]=".ppc-tbl th{text-align:left;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#475569;padding:12px 24px;background:#f8fafc;border-bottom:1px solid #e2e8f0;vertical-align:bottom}";
    $c[]=".ppc-th-sub{font-size:13px;font-weight:400;text-transform:none;letter-spacing:0;color:#64748b;margin-top:2px}";
    $c[]=".ppc-tbl td{padding:16px 24px;border-bottom:1px solid #e2e8f0;vertical-align:top;font-size:14px}";
    $c[]=".ppc-tbl tr:last-child td{border-bottom:0}";
    $c[]=".ppc-tbl tbody tr:hover{background:#f8fafc}";
    $c[]=".ppc-c-addr{width:300px}.ppc-c-st{width:250px}";
    $c[]=".ppc-acts{width:110px;text-align:right!important;white-space:nowrap}";
    $c[]=".ppc-addr-l{display:flex;align-items:center;flex-wrap:wrap;gap:8px}";
    $c[]=".ppc-port{font-size:20px;font-weight:600;color:#0f172a!important;text-decoration:none!important}";
    $c[]="a.ppc-port:hover{color:$dark!important}";
    $c[]=".ppc-tag{display:inline-block;background:#f1f5f9;color:#334155;border-radius:6px;padding:2px 8px;font-size:13px}";
    $c[]=".ppc-addr-l{flex-wrap:nowrap}";
    $c[]=".ppc-tag-name{max-width:170px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}";
    $c[]=".ppc-addr-ip{margin-top:6px;font-size:13.5px;font-weight:500;color:#334155}";
    $c[]=".ppc-sub{font-size:13px;color:#64748b;margin-top:3px;line-height:1.45}";
    $c[]=".ppc-dim .ppc-port,.ppc-dim .ppc-addr-ip{color:#94a3b8!important}";
    $c[]=".ppc-row-off .ppc-fchips{opacity:.7}";
    $c[]=".ppc-link{color:$dark;text-decoration:underline}";
    $c[]=".ppc-ibtn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border:0;background:none;border-radius:6px;color:#64748b;cursor:pointer;font-size:14px}";
    $c[]=".ppc-ibtn:hover{background:#f1f5f9;color:$dark}";
    $c[]=".ppc-ibtn-del:hover{background:#fef2f2;color:#b91c1c}";
    $c[]=".ppc-ibtn:focus-visible,.ppc-btn:focus-visible,.ppc-al-act:focus-visible,.ppc-fc:focus-visible,.ppc-sys summary:focus-visible{outline:2px solid $accent;outline-offset:2px}";
    $c[]=".ppc-fchips{display:flex;flex-wrap:wrap;gap:6px}";
    $c[]=".ppc-fc{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:4px 11px;font-size:13px;font-weight:500;border:1px solid transparent;white-space:nowrap;text-decoration:none!important;cursor:default}";
    $c[]="a.ppc-fc{cursor:pointer}";
    $c[]=".ppc-fc-on{background:#ecfdf5;border-color:#a7f3d0;color:#047857!important}";
    $c[]=".ppc-fc-warn{background:#fffbeb;border-color:#fde68a;color:#92400e!important}";
    $c[]=".ppc-fc-off{background:#f1f5f9;color:#64748b!important}";
    $c[]=".ppc-fc-off span{text-decoration:line-through}";
    $c[]=".ppc-fc-info{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8!important}";
    $c[]=".ppc-cert{display:flex;gap:8px;align-items:center;margin-top:8px;font-size:13px;color:#475569}";
    $c[]=".ppc-cert i{color:#94a3b8}";
    $c[]=".ppc-err{margin-top:6px;font-size:13px;font-weight:500;color:#b91c1c}";
    $c[]=".ppc-warn{margin-top:6px;font-size:13px;font-weight:500;color:#92400e}";
    $c[]=".ppc-note{margin-top:8px;font-size:13px;font-style:italic;color:#64748b}";
    $c[]=".ppc-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 11px;font-size:13px;font-weight:600;border:1px solid}";
    $c[]=".ppc-pill-ok{background:#ecfdf5;border-color:#a7f3d0;color:#047857}";
    $c[]=".ppc-pill-fail{background:#fef2f2;border-color:#fecaca;color:#b91c1c}";
    $c[]=".ppc-pill-off{background:#f1f5f9;border-color:#e2e8f0;color:#64748b}";
    $c[]=".ppc-st{animation:ppcfade .15s ease-in}";
    $c[]="@keyframes ppcfade{from{opacity:.4}to{opacity:1}}";
    $c[]="@media (prefers-reduced-motion:reduce){.ppc-st{animation:none}.ppc-donut-fg{transition:none}}";
    $c[]=".ppc-foot{display:flex;justify-content:space-between;align-items:center;padding:12px 24px;border-top:1px solid #e2e8f0;font-size:13px;color:#64748b}";
    $c[]=".ppc-only-cert #ppc-rows tr.ppc-row:not(.ppc-has-cert){display:none}";
    $c[]=".ppc-empty{text-align:center;padding:48px 24px 56px}";
    $c[]=".ppc-empty-ico{width:72px;height:72px;margin:0 auto 18px;border-radius:50%;background:$soft;color:$dark;display:flex;align-items:center;justify-content:center;font-size:30px}";
    $c[]=".ppc-empty h4{margin:0 0 8px;font-size:18px;font-weight:600;color:#0f172a}";
    $c[]=".ppc-empty p{max-width:520px;margin:0 auto 20px;color:#475569;line-height:1.6}";
    $c[]=".ppc-sys summary{display:flex;align-items:center;gap:14px;padding:16px 24px;cursor:pointer;list-style:none}";
    $c[]=".ppc-sys summary::-webkit-details-marker{display:none}";
    $c[]=".ppc-sys-ico{width:36px;height:36px;border-radius:8px;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center}";
    $c[]=".ppc-sys-t{flex:1;display:flex;flex-direction:column}";
    $c[]=".ppc-sys-t b{font-size:15px;font-weight:600;color:#0f172a}";
    $c[]=".ppc-sys-t span{font-size:13px;color:#64748b}";
    $c[]=".ppc-sys-chev{color:#94a3b8;transition:transform .15s}";
    $c[]=".ppc-sys[open] .ppc-sys-chev{transform:rotate(180deg)}";
    $c[]=".ppc-sys[open] summary{border-bottom:1px solid #e2e8f0}";
    $c[]="@media (max-width:1200px){.ppc-ban{flex-direction:column}.ppc-howto{flex:none;width:100%;box-sizing:border-box}.ppc-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}";
    return "<style>".implode("\n",$c)."</style>";
}

/** JS : copie, filtre certificat, recomptage, re-sonde echelonnee ligne par ligne. */
function ppc_js(array $rids):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=array();
    foreach(array("ppc_al_fail_one","ppc_al_fail_many","ppc_copied","ppc_al_cert_act","ppc_show_all","ppc_k_ok_sub","ppc_k_ok_none") as $k){
        $t[$k]=html_entity_decode(strip_tags($tpl->_ENGINE_parse_body("{".$k."}")),ENT_QUOTES,"UTF-8");
    }
    $T=json_encode($t);
    $R=json_encode(array_values($rids));
    return "<script>
(function(){
var T=$T;
window.PpcCopy=function(txt,btn){
  function done(){ if(!btn){return;} var o=btn.getAttribute('title'); btn.setAttribute('title',T.ppc_copied); btn.style.color='#047857';
    setTimeout(function(){btn.setAttribute('title',o);btn.style.color='';},1500); }
  if(navigator.clipboard && window.isSecureContext){ navigator.clipboard.writeText(txt).then(done,function(){}); return; }
  var ta=document.createElement('textarea'); ta.value=txt; ta.style.position='fixed'; ta.style.opacity='0';
  document.body.appendChild(ta); ta.select(); try{document.execCommand('copy');done();}catch(e){} document.body.removeChild(ta);
};
window.PpcCertFilter=function(){
  var r=document.getElementById('ppc-root'); if(!r){return;}
  var on=r.classList.toggle('ppc-only-cert');
  var b=document.getElementById('ppc-cert-btn'); if(b){ b.textContent=on?T.ppc_show_all:T.ppc_al_cert_act; }
};
window.PpcRecount=function(){
  var r=document.getElementById('ppc-root'); if(!r){return;}
  var els=r.querySelectorAll('[data-ppc-st]'), en=0, ok=0, fail=0;
  for(var i=0;i<els.length;i++){
    var s=els[i].getAttribute('data-ppc-st'), sys=els[i].getAttribute('data-sys')=='1';
    if(s=='fail'){fail++;}
    if(sys){continue;}
    if(s!='off'){en++;} if(s=='ok'){ok++;}
  }
  var e=document.getElementById('ppc-k-ok'); if(e){e.textContent=ok;}
  e=document.getElementById('ppc-k-en'); if(e){e.textContent=en;}
  e=document.getElementById('ppc-donut-fg'); if(e){ e.setAttribute('stroke-dasharray',(en>0?Math.round(ok*100/en):0)+' 100'); }
  e=document.getElementById('ppc-k-ans'); if(e){ e.classList.toggle('ppc-kpi-bad',en>0&&ok<en); e.classList.toggle('ppc-kpi-none',en==0); }
  e=document.getElementById('ppc-k-ans-sub'); if(e){ e.textContent=en>0?T.ppc_k_ok_sub:T.ppc_k_ok_none; }
  var a=document.getElementById('ppc-al-fail'), at=document.getElementById('ppc-al-fail-t');
  if(a){ if(fail==0){a.setAttribute('hidden','hidden');}else{a.removeAttribute('hidden');} }
  if(at){ if(fail==1){at.textContent=T.ppc_al_fail_one;}else{ at.innerHTML=T.ppc_al_fail_many.replace('%N','<strong>'+fail+'</strong>'); } }
};
// Re-sonde LIGNE PAR LIGNE, echelonnee : chaque sonde TCP peut bloquer une seconde ;
// les lancer toutes d'un coup figerait le navigateur et le proxy.
var rids=$R, i=0;
function modalOpen(){ return document.querySelector('.modal.in,.modal.show,.jconfirm,.sweet-alert.visible')!==null; }
function tick(){
  if(!document.getElementById('ppc-root')){return;}
  if(rids.length==0){return;}
  if(!modalOpen()){ var rid=rids[i%rids.length]; i++; Loadjs('$page?row-status='+encodeURIComponent(rid)); }
  setTimeout(tick,4000);
}
setTimeout(tick,4000);
})();
</script>";
}

function connected_port_list():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $m=ppc_model();
    $users=new usersMenus();

    $rids=array();
    foreach($m["ports"] as $p){$rids[]=$p["rid"];}
    foreach($m["system"] as $p){$rids[]=$p["rid"];}

    $html=array();
    $html[]="<div class='ppc' id='ppc-root'>";
    $html[]=ppc_banner($m);
    $html[]=ppc_alert_emergency($m);
    $html[]=ppc_alert_fail($m);
    $html[]=ppc_alert_cert($m);
    $html[]="<div class='ppc-kpis'>".ppc_kpi_ports($m).ppc_kpi_answering($m).ppc_kpi_noauth($m)."</div>";
    $html[]=ppc_table_user($m);
    $html[]=ppc_table_system($m);
    $html[]="</div>";

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $jsrestart="Loadjs('fw.proxy.apply.ports.php');";
    $topbuttons=array();
    if($users->AsSquidAdministrator){
        if(!$m["slave"]){
            $topbuttons[]=array("Loadjs('$page?port-js=0')",ico_plus,"{new_port}");
            $c=0;
            $tp=$q->QUERY_SQL("SELECT ID FROM transparent_ports WHERE enabled=1");
            if(is_array($tp)){$c=count($tp);}
            if($c==0){
                $topbuttons[]=array("Loadjs('fw.proxy.wizard.transparent.php')",ico_cd,"{transparent_mode}");
            }
            $topbuttons[]=array("Loadjs('fw.proxy.ports.ssl.php')",ico_certificate,"{activate_ssl_decryption}");
            $topbuttons[]=array("Loadjs('fw.proxy.ssl.status.php?powershell-js=yes')",ico_certificate,"{cert_deploy}");
        }
        $topbuttons[]=array($jsrestart,ico_save,"{reconfigure_proxy_ports_restart}");
    }
    $topbuttons[]=array("LoadAjax('table-connected-proxy-ports','$page?connected-ports-list=yes');",ico_refresh,"{refresh}");

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{listen_ports} ({connected_ports})";
    $TINY_ARRAY["ICO"]="fad fa-plug";
    $TINY_ARRAY["EXPL"]="{ppc_tiny_expl}";
    $TINY_ARRAY["URL"]="proxy-ports";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>NoSpinner();$jstiny</script>";

    echo awc_table_style().ppc_style()
        .ppc_nobr($tpl->_ENGINE_parse_body(implode("\n",$html)))
        .ppc_js($rids)
        .awc_pager_js(50,"ppc-rows","ppc-pager");
    return true;
}

function troubleshooting_ports(){
    $page   =   CurrentPageName();
    $tpl    =   new template_admin();
    $time   =   time();


    $jsrestart=$tpl->framework_buildjs("/proxy/general/nohup/restart",
        "squid.articarest.nohup","squid.articarest.nohup.log","$time","LoadAjax('proxy-transparent-ports-status','$page?status=yes');RefreshSecondInterfaceBarrs()");

    $SquidReconfigureAtBoot=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidReconfigureAtBoot"));


    $form[]=$tpl->field_checkbox("SquidReconfigureAtBoot","{reconfigure_proxy_ports_restart}",$SquidReconfigureAtBoot,false,"{reconfigure_proxy_ports_restart_explain}");
    $html[]="<div id='$time'></div>";
    $html[]=$tpl->form_outside("{after_rebooting}",$form,null,"{apply}",$jsrestart,"AsSquidAdministrator");

    $TINY_ARRAY["TITLE"]="{troubleshooting}";
    $TINY_ARRAY["ICO"]="fad fa-plug";
    $TINY_ARRAY["EXPL"]="";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";

    echo $tpl->_ENGINE_parse_body($html);



}

