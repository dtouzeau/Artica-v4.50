<?php
// Post SP10 - 13/10/2026
//Artica Service Pack 8 - Sep 2026
// DHCP requests: the history of every computer that obtained a lease (PostgreSQL
// dhcpd_hosts), written by the dhcpd "on commit" hook (/usr/share/artica-postfix/bin/dhcpclient):
// created = first commit ever seen for this MAC, updated = last commit, both in the
// appliance's LOCAL time ("timestamp without time zone"). Unlike dhcpd_lasts, a row is
// never removed when the lease ends, and a new MAC taking an address does not remove the
// previous owner's row: an address can therefore appear on several rows, the older ones
// describing who held it before.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpmetrics.inc");
$users=new usersMenus();if(!$users->AsFirewallManager && !$users->ASDCHPAdmin){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["build-page"])){build_page();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
page();

// A MAC as PostgreSQL prints a macaddr: six lower-case hex pairs. Anything else is refused
// before it gets near the SQL string (postgres_sql has no bound parameters).
function dreq_mac($mac){
	$mac=strtolower(trim((string)$mac));
	if(!preg_match('/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/',$mac)){return "";}
	return $mac;
}

function delete_js(){
	$tpl=new template_admin();
	$mac=dreq_mac($_GET["delete-js"]);
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
	if($mac==""){return $tpl->js_error("{dreq_invalid_mac}");}
	$after=$function<>""?"$function()":"";
	return $tpl->js_confirm_delete($mac,"delete",$mac,$after);
}

function delete(){
	$tpl=new template_admin();
	$mac=dreq_mac($_POST["delete"]);
	if($mac==""){echo $tpl->post_error("{dreq_invalid_mac}");return false;}
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM dhcpd_hosts WHERE mac='$mac'");
	if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
	admin_tracks("Removed $mac from the DHCP requests history");
	return true;
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html=$tpl->page_header("{dhcp_requests}",ico_eye,"{dreq_explain}",
		"$page?build-page=yes","dhcp-requests","progress-dhcrequests-restart",false,"table-dhcp-requests");
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{dhcp_requests}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);
}

function build_page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html=array();
	$html[]="<div style='margin-top:10px'>";
	$html[]=$tpl->search_block($page,"","","","");
	$html[]="</div>";
	echo $tpl->_ENGINE_parse_body(implode("\n",$html));
}

// Same reduction as fw.dhcp.last.php: only the characters of a MAC, an IP or a hostname.
function dreq_like($search){
	$s=strtolower(trim((string)$search));
	$s=preg_replace('/[^a-z0-9.:\-_* ]/','',$s);
	$s=substr(trim($s),0,64);
	$s=str_replace("_","\\_",$s);
	$s=str_replace(" ","*",$s);
	$s="*$s*";
	$s=str_replace("*","%",$s);
	while(strpos($s,"%%")!==false){$s=str_replace("%%","%",$s);}
	return $s;
}

function dreq_is_anonymous($name){
	$name=trim((string)$name);
	if($name=="" || $name=="noname"){return true;}
	return preg_match('/^[0-9]{1,3}-[0-9]{1,3}-[0-9]{1,3}-[0-9]{1,3}$/',$name)==1;
}

function dreq_date_cell($tpl,$value,$relative){
	$ts=strtotime((string)$value);
	if($ts===false || $ts<=0){return "<span class='awc-none'>{never}</span>";}
	if(!$relative){return "<div class='awc-mono'>".dhcpm_h($tpl->time_to_date($ts,true))."</div>";}
	$h="<div class='awc-seen'>".dhcpm_h(distanceOfTimeInWords($ts,time()))."</div>";
	$h.="<div class='awc-seen-sub'>".dhcpm_h($tpl->time_to_date($ts,true))."</div>";
	return $h;
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));

	$topbuttons=array();
	if($function<>""){$topbuttons[]=array("$function()",ico_refresh,"{refresh}");}
	$TINY=array();
	$TINY["TITLE"]="{dhcp_requests}";
	$TINY["ICO"]=ico_eye;
	$TINY["EXPL"]="{dreq_explain}";
	$TINY["URL"]="dhcp-requests";
	$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";

	$DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
	if($DisablePostGres==1){
		$installjs=$tpl->framework_buildjs("/postgresql/install","postgres.progress","postgres.log",
			"progress-dhcrequests-restart","$function()");
		$btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
		$install="<div style='text-align:right;margin-top:20px'>$btn</div>";
		echo $tpl->_ENGINE_parse_body($tpl->div_warning("{APP_POSTGRES} {missing}||{need_postgresql_1}<hr>$install"))
			."<script>NoSpinner();\n$jstiny</script>";
		return false;
	}

	$q=new postgres_sql();
	$ss=dreq_like(isset($_GET["search"])?$_GET["search"]:"");
	$withHostsnet=$q->TABLE_EXISTS("hostsnet");
	$join="";
	$cols="NULL AS hmac, NULL AS fullhostname, NULL AS proxyalias";
	$whereHost="";
	if($withHostsnet){
		$join="LEFT JOIN (SELECT DISTINCT ON (mac) mac,fullhostname,proxyalias FROM hostsnet) h ON h.mac=d.mac";
		$cols="h.mac::text AS hmac, h.fullhostname, h.proxyalias";
		$whereHost=" OR h.fullhostname ILIKE '$ss'";
	}
	// reused: a more recent row holds the same address under another MAC -- this row
	// describes a previous owner of the address.
	$sql="SELECT d.mac::text AS mac, host(d.ipaddr) AS ipaddr, d.hostname, d.created, d.updated, $cols,
		EXISTS(SELECT 1 FROM dhcpd_hosts n WHERE n.ipaddr=d.ipaddr AND n.mac<>d.mac AND n.updated>d.updated) AS reused
		FROM dhcpd_hosts d $join
		WHERE d.mac IS NOT NULL AND (d.mac::text ILIKE '$ss' OR host(d.ipaddr) LIKE '$ss' OR d.hostname ILIKE '$ss'$whereHost)
		ORDER BY d.updated DESC NULLS LAST LIMIT 500";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $tpl->_ENGINE_parse_body($tpl->div_error("{dhcp_requests}||".dhcpm_h($q->mysql_error)))
			."<script>NoSpinner();\n$jstiny</script>";
		return false;
	}
	$total=0;
	$rc=$q->QUERY_SQL("SELECT COUNT(*) AS tcount FROM dhcpd_hosts");
	if($q->ok){$lc=pg_fetch_assoc($rc);$total=intval($lc["tcount"]);}

	$rows=array();
	while($ligne=pg_fetch_assoc($results)){
		$rows[]=dreq_row($tpl,$page,$ligne,$function);
	}

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{dlast_computer}</th>";
	$html[]="<th>{ipaddr}</th>";
	$html[]="<th>{first_seen}</th>";
	$html[]="<th>{last_seen}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='dreq-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='5'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>{showing} ".count($rows)." / $total {computers}</span>";
	$html[]="<span class='awc-pg' id='dreq-pager'></span></div>";
	$html[]="</div></div>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(implode("\n",$html))
		."<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny</script>"
		.awc_pager_js(25,"dreq-rows","dreq-pager");
	return true;
}

function dreq_row($tpl,$page,$ligne,$function){
	$mac=trim((string)$ligne["mac"]);
	$ip=trim((string)$ligne["ipaddr"]);
	$leaseName=trim((string)$ligne["hostname"]);
	$name=$leaseName;
	$known=trim((string)$ligne["hmac"])<>"";
	if($known && trim((string)$ligne["fullhostname"])<>""){$name=trim((string)$ligne["fullhostname"]);}
	$alias=trim((string)$ligne["proxyalias"]);
	$reused=($ligne["reused"]=="t" || $ligne["reused"]===true || $ligne["reused"]=="1");

	$CallBackFunction=base64_encode("$function()");
	if($known){
		$jshost="Loadjs('fw.edit.computer.php?mac=".urlencode($mac)."&CallBackFunction=".urlencode($CallBackFunction)."')";
		$actComputer="<a class='awc-act' title='{dlast_edit_computer}' href=\"javascript:blur();\" onclick=\"$jshost\"><i class='".ico_computer."'></i></a>";
	}else{
		$ff=array();
		$ff[]="mac=".urlencode($mac);
		$ff[]="hostname=".urlencode(dreq_is_anonymous($leaseName)?"":$leaseName);
		$ff[]="ipaddr=".urlencode($ip);
		$ff[]="CallBackFunction=".urlencode($CallBackFunction);
		$jshost="Loadjs('fw.add.computer.php?".implode("&",$ff)."')";
		$actComputer="<a class='awc-act awc-act-go' title='{dlast_register_computer}' href=\"javascript:blur();\" onclick=\"$jshost\"><i class='fas fa-plus'></i></a>";
	}
	$actDelete="";
	$macOk=dreq_mac($mac);
	if($macOk<>""){
		$actDelete="<a class='awc-act awc-act-danger' title='{delete}' href=\"javascript:blur();\" onclick=\"Loadjs('$page?delete-js=".urlencode($macOk)."&function=$function')\"><i class='fas fa-trash-can'></i></a>";
	}

	$ts=strtotime((string)$ligne["updated"]);
	$stale=($ts===false || $ts<=0 || (time()-$ts)>30*86400);
	$icooff=$stale?" awc-ico-off":"";
	$nameoff=$stale?" awc-name-off":"";
	$staleTitle=$stale?" title='{dlast_stale}'":"";

	if(dreq_is_anonymous($name)){
		$label="<a href=\"javascript:blur();\" onclick=\"$jshost\" title='{dlast_anonymous}' style='font-style:italic;font-weight:600'>".dhcpm_h($name==""?$ip:$name)."</a>";
	}else{
		$label="<a href=\"javascript:blur();\" onclick=\"$jshost\">".dhcpm_h($name)."</a>";
	}
	$meta=dhcpm_h($mac);
	if($alias<>""){$meta.=" &middot; ".dhcpm_h($alias);}

	$ipcell=dhcpm_h($ip);
	if($reused){
		$ipcell.="<div class='awc-seen-sub'><span class='awc-tag' title='{dreq_ip_reused_explain}'>{dreq_ip_reused}</span></div>";
	}

	$h="<tr>";
	$h.="<td><div class='awc-ident'$staleTitle>";
	$h.="<span class='awc-ico$icooff'><i class='".ico_computer."'></i></span>";
	$h.="<span><span class='awc-name$nameoff'>$label</span>";
	$h.="<div class='awc-meta'>$meta</div></span></div></td>";
	$h.="<td class='awc-mono' style='white-space:nowrap'>$ipcell</td>";
	$h.="<td style='white-space:nowrap'>".dreq_date_cell($tpl,$ligne["created"],false)."</td>";
	$h.="<td style='white-space:nowrap'>".dreq_date_cell($tpl,$ligne["updated"],true)."</td>";
	$h.="<td><div class='awc-acts'>$actComputer$actDelete</div></td>";
	$h.="</tr>";
	return $h;
}
