<?php
// Post SP10 - 13/10/2026
// Load-Balancer ACL objects. Reads and writes go through the daemon
// (/haproxy/acls/groups, /haproxy/acls/rules, /haproxy/acls/group/*): the page
// used to build haproxy() from class.haproxy.inc, which appliances no longer
// ship, and to run its own SQL with the group ID pasted into the query.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["new-js"])){new_js();exit;}
if(isset($_GET["new-popup"])){new_popup();exit;}
if(isset($_POST["new-group"])){new_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete_perform();exit;}
page();

// Mirrors HaProxy.ACLGroupTypes.
function hgrp_types():array{
	return array("src"=>"{src_addr}","hdr(host)"=>"{dstdomain}","dst"=>"{dst_addr}","dst_port"=>"{destination_port}");
}

// Icon, tint, text colour and filter key of each type.
function hgrp_type_style():array{
	return array(
		"src"=>array("fas fa-laptop","#ecfdf5","#047857","#a7f3d0"),
		"hdr(host)"=>array("fas fa-globe","#eff6ff","#1d4ed8","#bfdbfe"),
		"dst"=>array("fas fa-server","#f2f6f6","#005447","#bfd4d1"),
		"dst_port"=>array("fas fa-plug","#fffbeb","#b45309","#fde68a"),
	);
}

function hgrp_style():string{
	return "<style>
.hg-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:10px}
.hg-tile{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;position:relative}
.hg-tile-l{font-size:13px;color:#475569;padding-right:44px}
.hg-tile-v{font-size:28px;font-weight:800;color:#0f172a;margin-top:6px;display:flex;align-items:center;gap:10px}
.hg-tile-v .hg-pill{font-size:11px;font-weight:600;padding:2px 8px;border-radius:6px}
.hg-tile-s{font-size:12px;color:#64748b;margin-top:4px}
.hg-tile-ico{position:absolute;top:16px;right:16px;width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px}
.hg-bar{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:20px 0 14px 0}
.hg-chip{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;padding:6px 14px;border-radius:999px;border:1px solid #e2e8f0;background:#fff;color:#334155!important;text-decoration:none!important;cursor:pointer}
.hg-chip .hg-dot{width:8px;height:8px;border-radius:50%}
.hg-chip .hg-n{font-size:11px;background:#f1f5f9;color:#475569;border-radius:999px;padding:1px 7px}
.hg-chip-on{background:#0f172a;border-color:#0f172a;color:#fff!important}
.hg-chip-on .hg-n{background:#334155;color:#fff}
.hg-name{font-family:inherit;font-size:15px;font-weight:700;color:#0f172a;letter-spacing:.01em}
.hg-name a{color:#0f172a;text-decoration:none}
.hg-name a:hover{color:#0f766e;text-decoration:underline}
.hg-sub{font-size:12px;color:#64748b;margin-top:2px;max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.hg-type{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;padding:3px 10px;border-radius:6px;border:1px solid;white-space:nowrap}
.hg-rule{display:inline-flex;align-items:center;gap:6px;font-size:12px;padding:3px 10px;border-radius:6px;background:#f1f5f9;border:1px solid #e2e8f0;color:#334155;margin:2px 6px 2px 0;text-decoration:none!important}
.hg-rule:hover{border-color:#94a3b8}
.hg-rule-deny{background:#fff1f2;border-color:#fecdd3;color:#be123c!important}
.hg-rule-svc{font-size:11px;color:#94a3b8}
.hg-orphan{color:#b45309;font-style:italic;font-size:13px}
.hg-row-orphan td{background:#fffdf5}
.hg-items{font-weight:700;color:#0f172a}
.hg-items-big{color:#be123c}
@media(max-width:1100px){.hg-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.hg-tiles{grid-template-columns:1fr}}
</style>";
}

function hgrp_get($route){
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($route));
	if(!is_object($json)){$e=$GLOBALS["CLASS_SOCKETS"]->mysql_error;return array(null,$e!=""?$e:"{error}");}
	if(!$json->Status){return array(null,(string)$json->Error);}
	return array($json,"");
}

// Writes never rebuild the Load-Balancer: only the {apply_configuration} button
// of the toolbar applies the configuration (same rule as fw.haproxy.acls.php).
function hgrp_post($route,$data):string{
	$data["reload"]=false;
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON($route,$data));
	if(!is_object($json)){$e=$GLOBALS["CLASS_SOCKETS"]->mysql_error;return $e!=""?$e:"{error}";}
	if(!$json->Status){return (string)$json->Error;}
	return "";
}

function hgrp_reload():string{
	$page=CurrentPageName();
	return "LoadAjaxSilent('table-haproxy-objects','$page?table=yes');";
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html=$tpl->page_header("{load_balancing} &raquo; {objects}","fas fa-object-group","{APP_HAPROXY_ACLS_OBJECTS}",
		"$page?table=yes",null,"progress-haproxy-restart",false,"table-haproxy-objects");
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{objects}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);
}

function enable_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$e=hgrp_post("/haproxy/acls/group/enable",array("id"=>intval($_GET["enable-js"]),"state"=>intval($_GET["state"] ?? 0)));
	if($e!=""){$tpl->js_error($e);return;}
	echo hgrp_reload();
}

function delete_js(){
	$tpl=new template_admin();
	$id=intval($_GET["delete-js"]);
	list($json,$err)=hgrp_get("/haproxy/acls/group?id=$id");
	$name=$json!==null?(string)$json->group->groupname:"{objects} $id";
	$tpl->js_confirm_delete(htmlspecialchars($name),"delete",$id,hgrp_reload());
}

// Items, rule links and the Group<id>.acl file go with the group (ACLGroupDelete).
function delete_perform(){
	$tpl=new template_admin();
	$e=hgrp_post("/haproxy/acls/group/delete",array("id"=>intval($_POST["delete"])));
	if($e!=""){echo $tpl->post_error($e);}
}

function new_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog2($tpl->javascript_parse_text("{new_object}"),"$page?new-popup=yes");
}

function new_popup(){
	$tpl=new template_admin();
	$form[]=$tpl->field_hidden("new-group","yes");
	$form[]=$tpl->field_text("groupname","{groupname}","",true);
	$form[]=$tpl->field_array_hash(hgrp_types(),"grouptype","{type}","src",true);
	echo $tpl->_ENGINE_parse_body($tpl->form_outside("",@implode("\n",$form),"","{add}","dialogInstance2.close();".hgrp_reload(),"AsSquidAdministrator"));
}

function new_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$type=(string)$_POST["grouptype"];
	if(!isset(hgrp_types()[$type])){echo $tpl->post_error("{type}: ".htmlspecialchars($type));return;}
	if(trim((string)$_POST["groupname"])==""){echo $tpl->post_error("{groupname}: {no_data}");return;}
	// Rules reference a group by ID, but the operator picks it by name: two
	// groups with the same name cannot be told apart in the link dialog.
	list($json,$err)=hgrp_get("/haproxy/acls/groups");
	if($json!==null && is_array($json->groups)){
		foreach($json->groups as $g){
			if(strcasecmp(trim((string)$g->groupname),trim((string)$_POST["groupname"]))==0){
				echo $tpl->post_error("{groupname}: ".htmlspecialchars((string)$_POST["groupname"])." {lbsvc_already_exists}");return;
			}
		}
	}
	$e=hgrp_post("/haproxy/acls/group/save",array("id"=>0,"groupname"=>(string)$_POST["groupname"],"grouptype"=>$type,"state"=>1));
	if($e!=""){echo $tpl->post_error($e);}
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$types=hgrp_types();
	$styles=hgrp_type_style();
	$filter=(string)($_GET["type"] ?? "");
	if($filter!="" && !isset($types[$filter])){$filter="";}
	list($json,$err)=hgrp_get("/haproxy/acls/groups");
	$groups=($json!==null && is_array($json->groups))?$json->groups:array();

	// Which rules use each group: the rules list carries its links.
	$usedBy=array();
	list($rj,$rerr)=hgrp_get("/haproxy/acls/rules");
	if($rj!==null && is_array($rj->rules)){
		foreach($rj->rules as $r){
			foreach((is_array($r->links ?? null)?$r->links:array()) as $l){$usedBy[intval($l->groupid)][]=$r;}
		}
	}

	// Tiles and per-type counts, over every group whatever the filter.
	$active=0;$items=0;$orphans=0;$perType=array();
	foreach($groups as $g){
		if($g->enabled){$active++;}
		$items+=intval($g->items);
		if(count($usedBy[intval($g->id)] ?? array())==0){$orphans++;}
		$perType[(string)$g->grouptype]=($perType[(string)$g->grouptype] ?? 0)+1;
	}
	$total=count($groups);
	$pct=$total>0?round($active*100/$total):0;
	$html[]="<div class='hg-tiles'>";
	$html[]="<div class='hg-tile'><span class='hg-tile-ico' style='background:#f1f5f9;color:#475569'><i class='fas fa-book'></i></span><div class='hg-tile-l'>{hgrp_defined}</div>".
		"<div class='hg-tile-v'>$total</div><div class='hg-tile-s'>{hgrp_reusable}</div></div>";
	$html[]="<div class='hg-tile'><span class='hg-tile-ico' style='background:#ecfdf5;color:#047857'><i class='fas fa-circle-check'></i></span><div class='hg-tile-l'>{hgrp_active}</div>".
		"<div class='hg-tile-v'>$active<span class='hg-pill' style='background:#ecfdf5;color:#047857;border:1px solid #a7f3d0'>$pct%</span></div><div class='hg-tile-s'>{hgrp_active_sub}</div></div>";
	$html[]="<div class='hg-tile'><span class='hg-tile-ico' style='background:#f0fdfa;color:#0f766e'><i class='fas fa-list-ol'></i></span><div class='hg-tile-l'>{hgrp_items_total}</div>".
		"<div class='hg-tile-v'>".number_format($items,0,","," ")."</div><div class='hg-tile-s'>{hgrp_items_sub}</div></div>";
	$html[]="<div class='hg-tile'><span class='hg-tile-ico' style='background:#fffbeb;color:#b45309'><i class='fas fa-triangle-exclamation'></i></span><div class='hg-tile-l'>{hgrp_orphans}</div>".
		"<div class='hg-tile-v' style='color:#b45309'>$orphans".($orphans>0?"<span class='hg-pill' style='background:#fffbeb;color:#b45309;border:1px solid #fde68a'>{hgrp_unlinked}</span>":"")."</div><div class='hg-tile-s'>{hgrp_orphans_sub}</div></div>";
	$html[]="</div>";

	// Type filter + search.
	$html[]="<div class='hg-bar'>";
	$html[]="<a class='hg-chip".($filter==""?" hg-chip-on":"")."' href=\"javascript:blur();\" OnClick=\"LoadAjaxSilent('table-haproxy-objects','$page?table=yes');\">{all} <span class='hg-n'>$total</span></a>";
	foreach($types as $k=>$label){
		$st=$styles[$k];
		$on=$filter===$k?" hg-chip-on":"";
		$html[]="<a class='hg-chip$on' href=\"javascript:blur();\" OnClick=\"LoadAjaxSilent('table-haproxy-objects','$page?table=yes&type=".urlencode($k)."');\"><span class='hg-dot' style='background:$st[2]'></span>$label <span class='hg-n'>".intval($perType[$k] ?? 0)."</span></a>";
	}
	$html[]="<span style='margin-left:auto;min-width:320px'>".awc_search_bar("hgrp-search","{search}")."</span>";
	$html[]="</div>";

	$html[]="<div class='awc-wrap' style='padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'>";
	$html[]="<thead><tr><th>ID</th><th>{hgrp_object}</th><th>{hgrp_criterion}</th><th>{hgrp_used_by}</th><th class='center'>{items}</th>";
	$html[]="<th class='center'>{enabled}</th><th style='text-align:right'>{actions}</th></tr></thead><tbody id='hgrp-rows'>";
	$shown=0;
	foreach($groups as $g){
		if($filter!="" && (string)$g->grouptype!==$filter){continue;}
		$shown++;
		$id=intval($g->id);
		$on=(bool)$g->enabled;
		$type=(string)$g->grouptype;
		$st=$styles[$type] ?? array("fas fa-object-group","#f1f5f9","#475569","#e2e8f0");
		$label=$types[$type] ?? htmlspecialchars($type);
		$edit="Loadjs('fw.haproxy.acls.php?group-js=$id&function=HgrpReload')";
		// The first patterns stand for the description the schema does not have.
		$preview="";
		if(intval($g->items)>0){
			list($ij,$ie)=hgrp_get("/haproxy/acls/items?groupid=$id");
			if($ij!==null && is_array($ij->patterns)){
				$first=array_slice($ij->patterns,0,3);
				$preview=htmlspecialchars(@implode(", ",$first)).(count($ij->patterns)>3?", &hellip;":"");
			}
		}
		$rules=array();
		$deny=false;
		foreach($usedBy[$id] ?? array() as $r){
			$rname=trim((string)$r->rulename)!=""?htmlspecialchars((string)$r->rulename):"{rule} ".intval($r->id);
			$isDeny=intval($r->rule_action)==3;
			if($isDeny){$deny=true;}
			$rules[]="<a class='hg-rule".($isDeny?" hg-rule-deny":"")."' href=\"javascript:blur();\" OnClick=\"Loadjs('fw.haproxy.acls.php?rule-js=".intval($r->id)."')\">".
				"<i class='fas ".($isDeny?"fa-shield-halved":"fa-filter")."'></i> $rname <span class='hg-rule-svc'>".htmlspecialchars((string)$r->servicename)."</span></a>";
		}
		$orphan=count($rules)==0;
		$rulesCell=$orphan?"<span class='hg-orphan'><i class='fas fa-circle-exclamation'></i> {hgrp_no_rule}</span>":@implode("",$rules);
		$n=$type=="all"?"*":number_format(intval($g->items),0,","," ");
		$enable=$tpl->icon_check($on?1:0,"Loadjs('$page?enable-js=$id&state=".($on?0:1)."&type=".urlencode($filter)."')",null,"AsSquidAdministrator");
		$del=$tpl->icon_delete("Loadjs('$page?delete-js=$id')","AsSquidAdministrator");
		$html[]="<tr".($orphan?" class='hg-row-orphan'":"").">";
		// The name of the Group<id>.acl file the daemon writes for this object.
		$html[]="<td style='width:1%' nowrap class='awc-mono'><strong>Group$id</strong></td>";
		$html[]="<td style='width:1%' nowrap><div class='awc-ident'><span class='awc-ico".($on?"":" awc-ico-off")."' style='background:$st[1];color:$st[2]'><i class='$st[0]'></i></span>".
			"<span><span class='hg-name'><a href=\"javascript:blur();\" OnClick=\"$edit\">".htmlspecialchars((string)$g->groupname)."</a></span>".
			($preview!=""?"<div class='hg-sub' title=\"$preview\">$preview</div>":"<div class='hg-sub awc-muted'>{no_data}</div>")."</span></div></td>";
		$html[]="<td style='width:1%' nowrap><span class='hg-type' style='background:$st[1];color:$st[2];border-color:$st[3]'><i class='$st[0]'></i> $label</span></td>";
		$html[]="<td style='width:99%'>$rulesCell</td>";
		$html[]="<td class='center'><span class='hg-items".($deny?" hg-items-big":"")."'>$n</span></td>";
		$html[]="<td class='center'>$enable</td>";
		$html[]="<td nowrap><div class='awc-acts'>".$tpl->icon_parameters($edit,"AsSquidAdministrator")."$del</div></td>";
		$html[]="</tr>";
	}
	if($err!=""){
		$html[]="<tr><td colspan='7'><div class='awc-empty'>".htmlspecialchars($err)."</div></td></tr>";
	}elseif($shown==0){
		$html[]="<tr><td colspan='7'><div class='awc-empty'>{no_data}</div></td></tr>";
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>$shown / $total {objects}</span><span class='awc-pg' id='hgrp-pager'></span></div></div></div>";

	$topbuttons=array();
	$topbuttons[]=array("Loadjs('$page?new-js=yes')",ico_plus,"{new_object}");
	$topbuttons[]=array($tpl->framework_buildjs("/haproxy/reload","haproxy.progress","haproxy.progress",
		"progress-haproxy-restart",hgrp_reload()),ico_save,"{apply_configuration}","AsSquidAdministrator");
	$topbuttons[]=array(hgrp_reload(),ico_refresh,"{refresh}");
	$TINY=array();
	$TINY["TITLE"]="{load_balancing} &raquo; {objects}";
	$TINY["ICO"]="fas fa-object-group";
	$TINY["EXPL"]="{APP_HAPROXY_ACLS_OBJECTS}";
	$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$reloadType="LoadAjaxSilent('table-haproxy-objects','$page?table=yes&type=".urlencode($filter)."');";
	$html[]="<script>NoSpinner();\nwindow.HgrpReload=function(){ $reloadType };\n".$tpl->ICON_SCRIPTS_COMPILE()."\nLoadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
	echo awc_table_style().hgrp_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).
		awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"hgrp-rows","hgrp-pager").
		awc_filter_js("hgrp-search","hgrp-rows","hgrp-pager");
}
