<?php
// Post SP10 - 13/10/2026
//Artica Service Pack 8 - Sep 2026
// Last computers: one row per computer that obtained a lease (PostgreSQL dhcpd_lasts),
// written by the dhcpd "on commit" hook (/usr/share/artica-postfix/bin/dhcpclient):
// delete-by-MAC, delete-by-IP, insert -- so one row per MAC and per IP, zdate = time of
// the last commit in the appliance's LOCAL time (Go time.Now()), a "timestamp without
// time zone". strtotime() reads it in PHP's zone, which is the same (timezones key).
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.dhcpmetrics.inc");
$users=new usersMenus();if(!$users->AsFirewallManager && !$users->ASDCHPAdmin){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["build-page"])){build_page();exit;}
if(isset($_GET["search"])){table();exit;}
if(isset($_GET["switch-dns"])){switch_dns();exit;}
page();

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html=$tpl->page_header("{last_computers}","fa fa-computer","{dlast_explain}",
		"$page?build-page=yes","dhcp-last","progress-dhcplast-restart",false,"table-dhcp-last");
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{last_computers}",$html);
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

// Search term -> LIKE pattern. postgres_sql has no bound parameters, so the term is
// reduced to the characters a MAC, an IP or a hostname can hold: no quote, no
// backslash, no % can reach the SQL string. "_" is a LIKE wildcard: escaped.
function dlast_like($search){
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

// A name the client did not send: dhcpclient derives "192-168-1-5" from the address,
// and before 2026-09-27 dhcpd passed the placeholder "noname".
function dlast_is_anonymous($name){
	$name=trim((string)$name);
	if($name=="" || $name=="noname"){return true;}
	return preg_match('/^[0-9]{1,3}-[0-9]{1,3}-[0-9]{1,3}-[0-9]{1,3}$/',$name)==1;
}

function dlast_seen_cell($tpl,$zdate){
	$ts=strtotime((string)$zdate);
	if($ts===false || $ts<=0){return "<span class='awc-none'>{never}</span>";}
	$h="<div class='awc-seen'>".dhcpm_h(distanceOfTimeInWords($ts,time()))."</div>";
	$h.="<div class='awc-seen-sub'>".dhcpm_h($tpl->time_to_date($ts,true))."</div>";
	return $h;
}

function dlast_dns_names($CURDNS,$ip){
	if(!isset($CURDNS[$ip]) || !is_array($CURDNS[$ip])){return array();}
	return CleanDNSHosts($CURDNS[$ip]);
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));

	$topbuttons=array();
	if($function<>""){$topbuttons[]=array("$function()",ico_refresh,"{refresh}");}
	$TINY=array();
	$TINY["TITLE"]="{last_computers}";
	$TINY["ICO"]="fa fa-computer";
	$TINY["EXPL"]="{dlast_explain}";
	$TINY["URL"]="dhcp-last";
	$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";

	$DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
	if($DisablePostGres==1){
		$installjs=$tpl->framework_buildjs("/postgresql/install","postgres.progress","postgres.log",
			"progress-dhcplast-restart","$function()");
		$btn=$tpl->button_autnonome("{install} {APP_POSTGRES}",$installjs,ico_cd,"AsSystemAdministrator",240,"btn-warning");
		$install="<div style='text-align:right;margin-top:20px'>$btn</div>";
		echo $tpl->_ENGINE_parse_body($tpl->div_warning("{APP_POSTGRES} {missing}||{need_postgresql_1}<hr>$install"))
			."<script>NoSpinner();\n$jstiny</script>";
		return false;
	}

	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
	$CURDNS=array();
	if($UnboundEnabled==1){
		$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/dump/hosts/a"));
		if(is_object($json) && property_exists($json,"Hosts")){
			$CURDNS=json_decode(json_encode($json->Hosts),true);
			if(!is_array($CURDNS)){$CURDNS=array();}
		}
	}

	$q=new postgres_sql();
	$ss=dlast_like(isset($_GET["search"])?$_GET["search"]:"");
	$withHostsnet=$q->TABLE_EXISTS("hostsnet");
	$join="";
	$cols="NULL AS hmac, NULL AS fullhostname, NULL AS proxyalias";
	$whereHost="";
	if($withHostsnet){
		$join="LEFT JOIN (SELECT DISTINCT ON (mac) mac,fullhostname,proxyalias FROM hostsnet) h ON h.mac=l.mac";
		$cols="h.mac::text AS hmac, h.fullhostname, h.proxyalias";
		$whereHost=" OR h.fullhostname ILIKE '$ss'";
	}
	$sql="SELECT l.mac::text AS mac, host(l.ipaddr) AS ipaddr, l.hostname, l.zdate, $cols
		FROM dhcpd_lasts l $join
		WHERE l.mac IS NOT NULL AND (l.mac::text ILIKE '$ss' OR host(l.ipaddr) LIKE '$ss' OR l.hostname ILIKE '$ss'$whereHost)
		ORDER BY l.zdate DESC NULLS LAST LIMIT 500";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $tpl->_ENGINE_parse_body($tpl->div_error("{last_computers}||".dhcpm_h($q->mysql_error)))
			."<script>NoSpinner();\n$jstiny</script>";
		return false;
	}
	$total=0;
	$rc=$q->QUERY_SQL("SELECT COUNT(*) AS tcount FROM dhcpd_lasts");
	if($q->ok){$lc=pg_fetch_assoc($rc);$total=intval($lc["tcount"]);}

	$rows=array();
	while($ligne=pg_fetch_assoc($results)){
		$rows[]=dlast_row($tpl,$page,$ligne,$CURDNS,$UnboundEnabled==1,$function);
	}
	$cols=$UnboundEnabled==1?5:4;

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{dlast_computer}</th>";
	$html[]="<th>{ipaddr}</th>";
	$html[]="<th>{last_seen}</th>";
	if($UnboundEnabled==1){$html[]="<th>{dlast_dns_records}</th>";}
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='dlast-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='$cols'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=implode("\n",$rows);
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>{showing} ".count($rows)." / $total {computers}</span>";
	$html[]="<span class='awc-pg' id='dlast-pager'></span></div>";
	$html[]="</div></div>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(implode("\n",$html))
		."<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\n$jstiny</script>"
		.awc_pager_js(25,"dlast-rows","dlast-pager");
	return true;
}

function dlast_row($tpl,$page,$ligne,$CURDNS,$unbound,$function){
	$mac=trim((string)$ligne["mac"]);
	$ip=trim((string)$ligne["ipaddr"]);
	$leaseName=trim((string)$ligne["hostname"]);
	$name=$leaseName;
	$known=trim((string)$ligne["hmac"])<>"";
	if($known && trim((string)$ligne["fullhostname"])<>""){$name=trim((string)$ligne["fullhostname"]);}
	$alias=trim((string)$ligne["proxyalias"]);

	$CallBackFunction=base64_encode("$function()");
	if($known){
		$jshost="Loadjs('fw.edit.computer.php?mac=".urlencode($mac)."&CallBackFunction=".urlencode($CallBackFunction)."')";
		$actComputer="<a class='awc-act' title='{dlast_edit_computer}' href=\"javascript:blur();\" onclick=\"$jshost\"><i class='".ico_computer."'></i></a>";
	}else{
		$ff=array();
		$ff[]="mac=".urlencode($mac);
		$ff[]="hostname=".urlencode(dlast_is_anonymous($leaseName)?"":$leaseName);
		$ff[]="ipaddr=".urlencode($ip);
		$ff[]="CallBackFunction=".urlencode($CallBackFunction);
		$jshost="Loadjs('fw.add.computer.php?".implode("&",$ff)."')";
		$actComputer="<a class='awc-act awc-act-go' title='{dlast_register_computer}' href=\"javascript:blur();\" onclick=\"$jshost\"><i class='fas fa-plus'></i></a>";
	}

	$ts=strtotime((string)$ligne["zdate"]);
	$stale=($ts===false || $ts<=0 || (time()-$ts)>30*86400);
	$icooff=$stale?" awc-ico-off":"";
	$nameoff=$stale?" awc-name-off":"";
	$staleTitle=$stale?" title='{dlast_stale}'":"";

	if(dlast_is_anonymous($name)){
		$label="<a href=\"javascript:blur();\" onclick=\"$jshost\" title='{dlast_anonymous}' style='font-style:italic;font-weight:600'>".dhcpm_h($name==""?$ip:$name)."</a>";
	}else{
		$label="<a href=\"javascript:blur();\" onclick=\"$jshost\">".dhcpm_h($name)."</a>";
	}
	$meta=dhcpm_h($mac);
	if($alias<>""){$meta.=" &middot; ".dhcpm_h($alias);}

	$h="<tr>";
	$h.="<td><div class='awc-ident'$staleTitle>";
	$h.="<span class='awc-ico$icooff'><i class='".ico_computer."'></i></span>";
	$h.="<span><span class='awc-name$nameoff'>$label</span>";
	$h.="<div class='awc-meta'>$meta</div></span></div></td>";
	$h.="<td class='awc-mono' style='white-space:nowrap'>".dhcpm_h($ip)."</td>";
	$h.="<td style='white-space:nowrap'>".dlast_seen_cell($tpl,$ligne["zdate"])."</td>";

	$actDNS="";
	if($unbound){
		$names=dlast_dns_names($CURDNS,$ip);
		if(count($names)==0){
			$h.="<td><span class='awc-none'>{dlast_no_dns_record}</span></td>";
		}else{
			$chips=array();
			foreach($names as $n){$chips[]="<span class='awc-tag'>".dhcpm_h(rtrim($n,"."))."</span>";}
			$h.="<td><div class='awc-chips' style='gap:6px'>".implode("",$chips)."</div></td>";
		}
		$id="dlast-dns-".md5($ip.$mac);
		$DNS=count($names)>0?1:0;
		$url="$page?switch-dns=$DNS&function=$function&IP=".urlencode($ip)."&id=$id&hostname=".urlencode($name);
		if($DNS==1){
			$actDNS="<span id='$id'><a class='awc-act awc-act-danger' title='{dlast_remove_dns}' href=\"javascript:blur();\" onclick=\"Loadjs('$url')\"><i class='fas fa-link-slash'></i></a></span>";
		}elseif(!dlast_is_anonymous($name)){
			$actDNS="<span id='$id'><a class='awc-act awc-act-go' title='{dlast_add_dns}' href=\"javascript:blur();\" onclick=\"Loadjs('$url')\"><i class='fas fa-link'></i></a></span>";
		}
	}
	$h.="<td><div class='awc-acts'>$actDNS$actComputer</div></td>";
	$h.="</tr>";
	return $h;
}

function switch_dns(){
	$tpl=new template_admin();
	header("content-type: application/x-javascript");
	$IPStr=trim((string)$_GET["IP"]);
	$id=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["id"]);
	$hostname=trim((string)$_GET["hostname"]);
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
	$refresh=$function<>""?"$function();":"";
	if(!filter_var($IPStr,FILTER_VALIDATE_IP)){return $tpl->js_error("{dlast_dns_invalid}");}
	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
	if($UnboundEnabled<>1){return true;}

	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/dump/hosts/a"));
	if(!is_object($json) || !property_exists($json,"Hosts")){return $tpl->js_error("{dlast_dns_invalid}");}
	$CURDNS=json_decode(json_encode($json->Hosts),true);
	$HostArray=array();
	if(is_array($CURDNS) && isset($CURDNS[$IPStr])){$HostArray=CleanDNSHosts($CURDNS[$IPStr]);}

	if(count($HostArray)>0){
		foreach($HostArray as $host){
			if(!preg_match('/^[A-Za-z0-9][A-Za-z0-9.\-_]*$/',$host)){continue;}
			$GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/remove/hosts/".rawurlencode($host));
			admin_tracks("Removing Host $host from DNS cache memory database");
		}
		echo "$('#$id').remove();\n$refresh\n";
		return true;
	}
	if(dlast_is_anonymous($hostname) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9.\-_]{0,127}$/',$hostname)){
		return $tpl->js_error("{dlast_dns_invalid}");
	}
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/add/host/".rawurlencode($hostname)."/".rawurlencode($IPStr)));
	if(!is_object($json) || !$json->Status){
		return $tpl->js_error(is_object($json)?$json->Error:"{dlast_dns_invalid}");
	}
	admin_tracks("Adding Host $hostname/$IPStr from DNS cache memory database");
	echo "$('#$id').remove();\n$refresh\n";
	return true;
}

function CleanDNSHosts($array):array{
	$HostArray=array();
	if(!is_array($array)){return $HostArray;}
	foreach($array as $host){
		if(strlen($host)<2){continue;}
		$HostArray[]=$host;
	}
	return $HostArray;
}
