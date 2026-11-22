<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
// SMTP firewall blocklist (ipset SMTPDeny). Rewired 2026-08 to talk to the artica-postfix daemon over
// /run/artica-postfix.sock instead of opening PostgreSQL directly and shelling the old PHP SMTP ipset script
// through framework/postfix2.php. The daemon owns smtp_ipset (parameterised — the old raw-SQL page had
// injection holes) and the SMTPDeny ipset/firewall-rule lifecycle.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
// awc_table_style(): shared entity-table stylesheet
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["ipaddr-js"])){ipaddr_js();exit;}
if(isset($_GET["ipaddr-popup"])){ipaddr_popup();exit;}
if(isset($_POST["ipaddr"])){ipaddr_save();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_GET["enable"])){enable();exit;}

table();

// pfxfw_admin returns the acting admin name for the daemon's audit log.
function pfxfw_admin(){
	$admin=isset($_SESSION["uid"])?$_SESSION["uid"]:"Manager";
	if($admin==-100){$admin="Manager";}
	return (string)$admin;
}

// pfxfw_err extracts a daemon error message, "" on success. A non-JSON, non-empty body (a bare
// "Not Found" from an older daemon binary) is treated as an error rather than a silent success.
function pfxfw_err($resp){
	$r=json_decode((string)$resp,true);
	if(is_array($r)){
		if(isset($r["success"]) && !$r["success"]){return (string)(isset($r["error"])?$r["error"]:"error");}
		if(!empty($r["error"])){return (string)$r["error"];}
		return "";
	}
	$raw=trim(str_replace(array("'","\\"),array(" "," "),(string)$resp));
	return $raw==""?"":substr($raw,0,300);
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html[]="
	</div>
	<div class='ibox-content'>
	<div id='postfix-ipset'></div>
	</div>
	</div>
	";
	// type "" — no PostgreSQL autocomplete: the page no longer opens the database, the daemon serves it.
	$html[]=$tpl->search_block($page,"","","","");
	echo $tpl->_ENGINE_parse_body($html);
}

function delete(){
	$tpl=new template_admin();
	$ipaddr=(string)$_GET["delete"];
	$resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/ipset/delete?ipaddr=".urlencode($ipaddr)."&admin=".urlencode(pfxfw_admin()));
	$e=pfxfw_err($resp);
	if($e!==""){echo $tpl->js_mysql_alert($e);return;}
	// The row id is a md5: restrict it to hex so it cannot inject an arbitrary jQuery selector.
	$md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
	echo "$('#$md').remove()\n";
}

function ipaddr_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ipaddr=$_GET["ipaddr-js"];
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
	$tpl->js_prompt("{new_address}", "{set_address_generic}", "far fa-plus-square", "$page", "ipaddr","$function()");
}

function enable(){
	$tpl=new template_admin();
	$ipaddr=(string)$_GET["enable"];
	$resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/ipset/toggle?ipaddr=".urlencode($ipaddr)."&admin=".urlencode(pfxfw_admin()));
	$e=pfxfw_err($resp);
	if($e!==""){echo $tpl->js_mysql_alert($e);}
}

function ipaddr_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ipaddr=$_GET["ipaddr-popup"];
	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
	$jsafter="BootstrapDialog1.close();$function()";
	if($ipaddr==null){
		$bt="{add}";
		$title="{new_address}";
		$form[]=$tpl->field_text("ipaddr","{address}", $ipaddr);
	}else{
		$bt="{apply}";
		$form[]=$tpl->field_ipaddr("ipaddr", "{address}", null,true);
		$title=$ipaddr;
	}
	$form[]=$tpl->field_text("description", "{description}", "");
	echo $tpl->form_outside($title, $form,null,$bt,$jsafter,"AsDnsAdministrator",true);
}

function ipaddr_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ipaddr=(string)$_POST["ipaddr"];
	$resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX_POST_JSON("/postfix/ipset/add",
		array("ipaddr"=>$ipaddr,"admin"=>pfxfw_admin()));
	$e=pfxfw_err($resp);
	if($e!==""){echo $e;return;}
}

function search(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$t=time();

	// Compile ("apply rows to the live ipset") and Update ("download the blocklists then compile") are
	// fire-and-track daemon jobs; the progress bar polls ipset.progress. CMD carries the `postfix:`
	// prefix so fw.progress.php dispatches it to /run/artica-postfix.sock.
	$AC["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ipset.progress";
	$AC["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ipset.progress.log";
	$AC["CMD"]="postfix:/postfix/ipset/compile";
	$AC["TITLE"]="{APP_FIREWALL} {compile_rules}";
	$jsCompile="Loadjs('fw.progress.php?".fw_progress_args($AC)."&mainid=postfix-ipset')";
	$AU=$AC; $AU["CMD"]="postfix:/postfix/ipset/download"; $AU["TITLE"]="{APP_FIREWALL} {update}";
	$jsUpdate="Loadjs('fw.progress.php?".fw_progress_args($AU)."&mainid=postfix-ipset')";

	$function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?ipaddr-js=&function=$function');\">";
	$html[]="<i class='fa fa-plus'></i> {new_address} </label>";
	$html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsCompile;\">";
	$html[]="<i class='fa fa-cogs'></i> {compile_rules} </label>";
	$html[]="<label class=\"btn btn btn-success\" OnClick=\"$jsUpdate;\">";
	$html[]="<i class='fa fa-download'></i> {update} </label>";
	$html[]="</div>";

	$search="";
	if(isset($_GET["search"])){$search=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}
	$resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/ipset/list?limit=2000&search=".urlencode($search));
	$j=json_decode((string)$resp,true);
	if(!is_array($j) || empty($j["success"])){
		echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
		echo $tpl->FATAL_ERROR_SHOW_128("{no_data}");
		return;
	}
	$entries=isset($j["entries"])&&is_array($j["entries"])?$j["entries"]:array();
	$sources=isset($j["sources"])&&is_array($j["sources"])?$j["sources"]:array();

	$rows=array();
	foreach($entries as $e){ $rows[]=pfxfw_row($tpl,$page,$sources,$e); }

	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>{ipaddr}</th>";
	$html[]="<th>{time}</th>";
	$html[]="<th style='text-align:right'>{actions}</th>";
	$html[]="</tr></thead><tbody id='pfxfw-rows'>";
	if(count($rows)==0){
		$html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	$shown=count($rows);
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $shown / $shown</span>";
	$html[]="<span class='awc-pg' id='pfxfw-pager'></span>";
	$html[]="</div></div></div>";
	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

	echo awc_table_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.awc_pager_js(50,"pfxfw-rows","pfxfw-pager");
}

/**
 * One ipset entry: pattern as identity, source as subtitle, enable toggle and delete. Automatic
 * (downloaded) entries cannot be hand-deleted — they would just come back on the next feed refresh.
 */
function pfxfw_row($tpl,$page,$sources,$e):string{
	$ipaddr=(string)($e["pattern"] ?? "");
	$md=md5($ipaddr);
	$enc=urlencode($ipaddr);
	$auto=(intval($e["automatic"] ?? 0)!=0);
	$en=(intval($e["enabled"] ?? 0)!=0);
	$patype=(string)($e["patype"] ?? "");
	$type=isset($sources[$patype])?(string)$sources[$patype]:$patype;

	$enable=$tpl->icon_check($en?1:0,"Loadjs('$page?enable=$enc&md=$md')");
	$delete=$auto?$tpl->icon_nothing():$tpl->icon_delete("Loadjs('$page?delete=$enc&md=$md')");

	$h ="<tr id='$md'>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='".($en?"awc-ico":"awc-ico awc-ico-off")."'><i class='fas fa-ban'></i></span>";
	$h.="<span><span class='".($en?"awc-name":"awc-name awc-name-off")."'>".htmlspecialchars($ipaddr)."</span>";
	$meta=array();
	if($type!=="" && $type!=="0" && $type!=="-"){$meta[]=htmlspecialchars($type);}
	if($auto){$meta[]="{automatic}";}
	if(count($meta)>0){$h.="<div class='awc-meta'>".@implode(" &middot; ",$meta)."</div>";}
	$h.="</span></div></td>";
	$h.="<td class='awc-mono'>".htmlspecialchars($tpl->time_to_date(strtotime((string)($e["zdate"] ?? "")),true))."</td>";
	$h.="<td><div class='awc-acts'>$enable$delete</div></td>";
	$h.="</tr>";
	return $h;
}
?>
