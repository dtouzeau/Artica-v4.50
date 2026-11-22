<?php
// Post SP10 - 13/10/2026
// Load-Balancer ACL rules. Every read and write goes through the daemon
// (/haproxy/acls/*, HaProxy/aclsdb.go + aclslist.go): the page used to run its
// own SQL on haproxy.db with mysqli_* calls on SQLite results, which died with a
// PHP fatal error, and it depended on class.haproxy.inc which appliances no
// longer ship.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["build-page"])){build_page();exit;}
if(isset($_GET["search"])){search();exit;}

if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["rule-tabs"])){rule_tabs();exit;}
if(isset($_GET["rule-params"])){rule_params();exit;}
if(isset($_GET["rule-target"])){rule_target();exit;}
if(isset($_POST["rule-save"])){rule_save();exit;}
if(isset($_GET["rule-enable-js"])){rule_enable_js();exit;}
if(isset($_GET["rule-move-js"])){rule_move_js();exit;}
if(isset($_GET["rule-delete-js"])){rule_delete_js();exit;}
if(isset($_POST["rule-delete"])){rule_delete();exit;}

if(isset($_GET["rule-objects"])){rule_objects();exit;}
if(isset($_GET["objects-table"])){objects_table();exit;}
if(isset($_GET["link-revert-js"])){link_state_js("revert");exit;}
if(isset($_GET["link-operator-js"])){link_state_js("operator");exit;}
if(isset($_GET["link-move-js"])){link_move_js();exit;}
if(isset($_GET["link-delete-js"])){link_delete_js();exit;}
if(isset($_GET["link-group-enable-js"])){link_group_enable_js();exit;}

if(isset($_GET["newgroup-js"])){newgroup_js();exit;}
if(isset($_GET["newgroup-popup"])){newgroup_popup();exit;}
if(isset($_POST["newgroup"])){newgroup_save();exit;}
if(isset($_GET["linkgroup-js"])){linkgroup_js();exit;}
if(isset($_GET["linkgroup-popup"])){linkgroup_popup();exit;}
if(isset($_POST["link-group"])){linkgroup_save();exit;}

if(isset($_GET["group-js"])){group_js();exit;}
if(isset($_GET["group-tabs"])){group_tabs();exit;}
if(isset($_GET["group-params"])){group_params();exit;}
if(isset($_POST["group-save"])){group_save();exit;}
if(isset($_GET["group-items"])){group_items();exit;}
if(isset($_POST["group-items-save"])){group_items_save();exit;}
page();

// ---------------------------------------------------------------- helpers

function acl_actions():array{
	return array(0=>"{do_nothing}",1=>"{use_backend}",2=>"{use_server}",3=>"{deny}");
}

// Mirrors HaProxy.ACLGroupTypes.
function acl_group_types():array{
	return array("src"=>"{src_addr}","hdr(host)"=>"{dstdomain}","dst"=>"{dst_addr}","dst_port"=>"{destination_port}");
}

// The caller's refresh function, as a statement (empty when none was passed).
function acl_function_call():string{
	$fn=acl_function();
	return $fn==""?"":"if(typeof $fn==='function'){ $fn(); }";
}

function acl_function():string{
	return preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
}

function acl_get($route){
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($route));
	if(!is_object($json)){return array(null,$GLOBALS["CLASS_SOCKETS"]->mysql_error!=""?$GLOBALS["CLASS_SOCKETS"]->mysql_error:"{error}");}
	if(!$json->Status){return array(null,(string)$json->Error);}
	return array($json,"");
}

// POST to a mutating route. Returns "" on success, the error otherwise.
// Writes never rebuild the Load-Balancer: only the {apply_configuration} button
// of the toolbar applies the configuration.
function acl_post($route,$data):string{
	$data["reload"]=false;
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON($route,$data));
	if(!is_object($json)){
		$e=$GLOBALS["CLASS_SOCKETS"]->mysql_error;
		return $e!=""?$e:"{error}";
	}
	if(!$json->Status){return (string)$json->Error;}
	return "";
}

function acl_rule($id){
	list($json,$err)=acl_get("/haproxy/acls/rule?id=".intval($id));
	if($json===null){return null;}
	return $json->rule;
}

// The sentence a rule reads as: the groups it tests, then what it does.
function acl_describe($rule):string{
	return acl_sentence($rule,true);
}

// The rule as it reads: FOR <object> AND NOT <object> THEN <action>. $chips
// renders the card form of the list; false gives the plain sentence of the
// editor preview. Disabled or empty groups are left out, as buildGroups() does.
function acl_sentence($rule,$chips=true):string{
	$types=acl_group_types();
	$terms=array();
	$links=is_array($rule->links ?? null)?$rule->links:array();
	foreach($links as $l){
		if(!$l->group_enabled || intval($l->items)==0){continue;}
		$type=$types[$l->grouptype] ?? htmlspecialchars((string)$l->grouptype);
		$name=htmlspecialchars((string)$l->groupname);
		$n=intval($l->items);
		$t="";
		if(count($terms)>0){$t.="<span class='lba-kw'>".($l->operator?"{or}":"{and}")."</span>";}
		if($l->revert){$t.="<span class='lba-kw lba-kw-not'>{lbacl_not}</span>";}
		$t.="<span class='lba-obj'>$type</span><span class='lba-val' title=\"$name\">$name &middot; $n {items}</span>";
		$terms[]=$t;
	}
	if(count($terms)==0){return "";}
	$s="<span class='lba-kw'>{lbacl_for}</span>".@implode("",$terms)."<span class='lba-kw'>{lbacl_then}</span>";
	// The editor preview draws the action itself, from the card being chosen.
	return $chips?$s.acl_action_html($rule):$s;
}

// Name of the target of a rule (backend group for action 1, server for action 2):
// rule_action_data only carries the id. One request per service and action, cached.
function acl_target_name($servicename,$action,$data):string{
	static $CACHE=array();
	$action=intval($action);
	$data=intval($data);
	if($data==0 || ($action!=1 && $action!=2)){return "";}
	$key="$action|$servicename";
	if(!isset($CACHE[$key])){
		$CACHE[$key]=array();
		if($action==1){
			list($json,$err)=acl_get("/haproxy/backends/groups?servicename=".urlencode((string)$servicename));
			if($json!==null && is_array($json->backend_groups)){
				foreach($json->backend_groups as $g){$CACHE[$key][intval($g->id)]=(string)$g->groupname;}
			}
		}else{
			list($json,$err)=acl_get("/haproxy/backends?servicename=".urlencode((string)$servicename));
			if($json!==null && is_array($json->backends)){
				foreach($json->backends as $b){$CACHE[$key][intval($b->id)]=(string)$b->backendname;}
			}
		}
	}
	return $CACHE[$key][$data] ?? "";
}

// Why a rule has no condition: nothing linked, linked objects disabled, or enabled
// but empty. They are not fixed in the same place, so they are not merged.
function acl_incomplete_reason($rule):string{
	$links=is_array($rule->links ?? null)?$rule->links:array();
	if(count($links)==0){return "";}
	$off=array();$empty=array();
	foreach($links as $l){
		if(!$l->group_enabled){$off[]=(string)$l->groupname;continue;}
		if(intval($l->items)==0){$empty[]=(string)$l->groupname;}
	}
	if(count($off)>0){return "{group} \"".htmlspecialchars(implode(", ",$off))."\": {disabled}";}
	if(count($empty)>0){return "{group} \"".htmlspecialchars(implode(", ",$empty))."\": 0 {items}";}
	return "";
}

function acl_action_html($rule):string{
	$a=intval($rule->rule_action);
	$label=(string)$rule->rule_action_label;
	if($label==""){$label=acl_target_name((string)$rule->servicename,$a,(string)$rule->rule_action_data);}
	$label=htmlspecialchars($label);
	switch($a){
		case 1: return "<span class='lba-act lba-act-route'><i class='fas fa-layer-group'></i> {lbacl_do_use_backend}".($label!=""?" : <strong>$label</strong>":"")."</span>";
		case 2: return "<span class='lba-act lba-act-route'><i class='fas fa-server'></i> {lbacl_do_use_server}".($label!=""?" : <strong>$label</strong>":"")."</span>";
		case 3: return "<span class='lba-act lba-act-deny'><i class='fas fa-shield-halved'></i> {lbacl_do_deny}</span>";
	}
	return "<span class='lba-act'><i class='fas fa-circle-pause'></i> {lbacl_do_nothing}</span>";
}

function acl_style():string{
	return "<style>
.lba-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:10px}
.lba-tile{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;position:relative}
.lba-tile-l{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b;padding-right:44px}
.lba-tile-v{font-size:26px;font-weight:800;color:#0f172a;margin-top:6px}
.lba-tile-v small{font-size:13px;font-weight:500;color:#64748b;margin-left:6px}
.lba-tile-s{font-size:12px;margin-top:4px;color:#64748b}
.lba-tile-ico{position:absolute;top:14px;right:14px;width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px}
.lba-note{font-size:13px;color:#475569;margin:14px 0 10px 2px}
.lba-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px 18px;margin-bottom:14px;display:flex;gap:16px;align-items:flex-start}
.lba-card-off{background:#fafafa}
.lba-order{flex:0 0 auto;width:38px;border:1px solid #e2e8f0;border-radius:8px;display:flex;flex-direction:column;align-items:center;padding:4px 0;font-weight:700;color:#0f172a}
.lba-order a{color:#94a3b8!important;font-size:12px;padding:2px 6px;text-decoration:none!important}
.lba-order a:hover{color:#0f766e!important}
.lba-main{flex:1 1 auto;min-width:0}
.lba-head{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:10px}
.lba-title{font-size:16px;font-weight:700;color:#0f172a;text-decoration:none!important}
.lba-title:hover{color:#0f766e}
.lba-svc{font-size:11px;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;padding:2px 8px;border-radius:4px}
.lba-st{font-size:11px;font-weight:700;padding:2px 9px;border-radius:999px;display:inline-flex;align-items:center;gap:5px}
.lba-st:before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor}
.lba-st-on{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}
.lba-st-sec{background:#fff1f2;color:#be123c;border:1px solid #fecdd3}
.lba-st-inc{background:#fffbeb;color:#b45309;border:1px solid #fde68a}
.lba-st-off{background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0}
.lba-cond{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;display:flex;flex-wrap:wrap;align-items:center;gap:8px;font-size:13px}
.lba-kw{font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;background:#e2e8f0;color:#334155;padding:3px 8px;border-radius:4px}
.lba-kw-not{background:#ffe4e6;color:#be123c}
.lba-obj{font-weight:600;color:#0f172a}
.lba-val{background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:2px 8px;color:#334155;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.lba-act{font-weight:600;color:#475569}
.lba-act-route{color:#1d4ed8}
.lba-act-deny{color:#be123c}
.lba-inc{color:#b45309;font-style:italic}
.lba-inc a{font-style:normal;font-weight:600;color:#047857!important;margin-left:10px}
.lba-side{flex:0 0 auto;display:flex;align-items:center;gap:10px;padding-top:4px}
.lba-svchead{display:flex;align-items:center;gap:10px;margin:18px 2px 10px 2px}
.lba-svchead-ico{width:30px;height:30px;border-radius:8px;background:#f1f5f9;color:#475569;display:flex;align-items:center;justify-content:center;font-size:13px}
.lba-svchead-n{font-size:15px;font-weight:700;color:#0f172a}
.lba-svchead-c{font-size:12px;color:#64748b}
.lba-svchead a{margin-left:auto;font-size:13px;font-weight:600;color:#047857!important;text-decoration:none!important}
.lba-st-idle{background:#f1f5f9;color:#475569;border:1px solid #cbd5e1}
@media(max-width:1100px){.lba-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:700px){.lba-tiles{grid-template-columns:1fr}.lba-card{flex-wrap:wrap}}
</style>";
}

// js_error() emits the JavaScript itself (popup_error) and returns a bool.
function acl_js_error($e){
	$tpl=new template_admin();
	$tpl->js_error($e);
}

// ---------------------------------------------------------------- page

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html=$tpl->page_header("{load_balancing} &raquo; {ACLS}","fas fa-filter","{APP_HAPROXY_ACLS}",
		"$page?build-page=yes",null,"progress-haproxy-restart",false,"table-haproxy-acls");
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{ACLS}",$html);
		echo $tpl->build_firewall();
		return;
	}
	echo $tpl->_ENGINE_parse_body($html);
}

function build_page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// The last search is kept for the session and put back in the field.
	$last=htmlspecialchars((string)($_SESSION["HAPROXY_ACL_SEARCH"] ?? ""),ENT_QUOTES);
	$html[]="<div style='margin-top:10px'>";
	$html[]=$tpl->search_block($page,"","",$last!=""?"value=$last":"","");
	$html[]="</div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function search(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$function=acl_function();
	$search=trim((string)($_GET["search"] ?? ""));
	$_SESSION["HAPROXY_ACL_SEARCH"]=$search;
	list($json,$err)=acl_get("/haproxy/acls/rules?search=".urlencode($search));
	$rules=($json!==null && is_array($json->rules))?$json->rules:array();

	// Tiles over every rule, whatever the search.
	$all=$rules;
	if($search!=""){
		list($ja,$ea)=acl_get("/haproxy/acls/rules");
		if($ja!==null && is_array($ja->rules)){$all=$ja->rules;}
	}
	$active=0;$routing=0;$blocking=0;
	foreach($all as $r){
		if($r->enabled){$active++;}
		$a=intval($r->rule_action);
		if($a==1 || $a==2){$routing++;}
		if($a==3){$blocking++;}
	}
	list($jg,$eg)=acl_get("/haproxy/acls/groups");
	$objects=($jg!==null && is_array($jg->groups))?count($jg->groups):0;
	$total=count($all);
	$pct=$total>0?round($active*100/$total):0;
	$html[]="<div class='lba-tiles'>";
	$html[]="<div class='lba-tile'><span class='lba-tile-ico' style='background:#ecfdf5;color:#047857'><i class='fas fa-toggle-on'></i></span><div class='lba-tile-l'>{lbacl_active_rules}</div>".
		"<div class='lba-tile-v'>$active<small>/ $total</small></div><div class='lba-tile-s' style='color:#047857'>$pct% {lbsvc_operational}</div></div>";
	$html[]="<div class='lba-tile'><span class='lba-tile-ico' style='background:#eff6ff;color:#1d4ed8'><i class='fas fa-code-branch'></i></span><div class='lba-tile-l'>{lbacl_routing}</div>".
		"<div class='lba-tile-v' style='color:#1d4ed8'>$routing</div><div class='lba-tile-s'>{lbacl_routing_sub}</div></div>";
	$html[]="<div class='lba-tile'><span class='lba-tile-ico' style='background:#fff1f2;color:#be123c'><i class='fas fa-ban'></i></span><div class='lba-tile-l'>{lbacl_blocking}</div>".
		"<div class='lba-tile-v' style='color:#be123c'>$blocking</div><div class='lba-tile-s'>{lbacl_blocking_sub}</div></div>";
	$html[]="<div class='lba-tile'><span class='lba-tile-ico' style='background:#fffbeb;color:#b45309'><i class='fas fa-folder-open'></i></span><div class='lba-tile-l'>{lbacl_objects}</div>".
		"<div class='lba-tile-v' style='color:#b45309'>$objects</div><div class='lba-tile-s'><a href=\"javascript:blur();\" OnClick=\"LoadAjaxSilent('MainContent','fw.haproxy.groups.php');\" style='font-weight:600;color:#047857'>{lbacl_manage_library} &rarr;</a></div></div>";
	$html[]="</div>";
	$html[]="<div class='lba-note'><i class='fas fa-list-ol'></i>&nbsp; {lbacl_order_note}</div>";

	if($err!=""){
		$html[]="<div class='awc-empty'>".htmlspecialchars($err)."</div>";
	}elseif(count($rules)==0){
		$html[]="<div class='lba-card' style='justify-content:center'><div class='awc-empty'>{no_data}</div></div>";
	}
	// The daemon returns the rules sorted by service then evaluation order: each
	// service opens with its own header, which also creates a rule for it.
	$count=array();
	foreach($rules as $r){$count[(string)$r->servicename]=($count[(string)$r->servicename] ?? 0)+1;}
	$prev=null;
	foreach($rules as $r){
		$servicename=(string)$r->servicename;
		if($servicename!==$prev){
			$prev=$servicename;
			$senc=urlencode($servicename);
			$html[]="<div class='lba-svchead'><span class='lba-svchead-ico'><i class='fas fa-random'></i></span>".
				"<span><div class='lba-svchead-n'>".htmlspecialchars($servicename)."</div><div class='lba-svchead-c'>".$count[$servicename]." {rules}</div></span>".
				"<a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?rule-js=0&servicename=$senc&function=$function')\"><i class='fas fa-plus'></i> {new_rule}</a></div>";
		}
		$id=intval($r->id);
		$on=(bool)$r->enabled;
		$rulename=trim((string)$r->rulename);
		$name=$rulename!=""?htmlspecialchars(strlen($rulename)>50?substr($rulename,0,47)."...":$rulename):"{rule} $id";
		$edit="Loadjs('$page?rule-js=$id&function=$function')";
		$sentence=acl_sentence($r,true);
		// Switched off by hand and "do nothing" are not the same: the first comes
		// back as it was, the second still has to be given an action.
		if(!$on){$status="<span class='lba-st lba-st-off'>{disabled}</span>";}
		elseif($sentence==""){$status="<span class='lba-st lba-st-inc'>{lbacl_incomplete_badge}</span>";}
		elseif(intval($r->rule_action)==0){$status="<span class='lba-st lba-st-idle'>{inactive}</span>";}
		elseif(intval($r->rule_action)==3){$status="<span class='lba-st lba-st-sec'>{lbacl_security}</span>";}
		else{$status="<span class='lba-st lba-st-on'>{active2}</span>";}
		if($sentence==""){
			$why=acl_incomplete_reason($r);
			$sentence="<span class='lba-inc'><i class='fas fa-triangle-exclamation'></i> {lbacl_incomplete}".($why!=""?" &mdash; $why":"").
				"<a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?rule-js=$id&tab=objects&function=$function')\">+ {lbacl_add_conditions}</a></span>".
				"<span style='margin-left:auto'>".acl_action_html($r)."</span>";
		}
		$up="<a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?rule-move-js=$id&direction=up&function=$function')\" title='{up}'><i class='fas fa-chevron-up'></i></a>";
		$down="<a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?rule-move-js=$id&direction=down&function=$function')\" title='{down}'><i class='fas fa-chevron-down'></i></a>";
		$enable=$tpl->icon_check($on?1:0,"Loadjs('$page?rule-enable-js=$id&state=".($on?0:1)."&function=$function')",null,"AsSquidAdministrator");
		$del=$tpl->icon_delete("Loadjs('$page?rule-delete-js=$id&function=$function')","AsSquidAdministrator");
		$html[]="<div class='lba-card".($on?"":" lba-card-off")."'>";
		$html[]="<div class='lba-order'>$up<span>".intval($r->zorder)."</span>$down</div>";
		$html[]="<div class='lba-main'><div class='lba-head'><a class='lba-title' href=\"javascript:blur();\" OnClick=\"$edit\">$name</a>".
			"<span class='lba-svc'>".htmlspecialchars((string)$r->servicename)."</span>$status</div>";
		$html[]="<div class='lba-cond'>$sentence</div></div>";
		$html[]="<div class='lba-side'>$enable".$tpl->icon_parameters($edit,"AsSquidAdministrator")."$del</div>";
		$html[]="</div>";
	}

	$topbuttons=array();
	$topbuttons[]=array("Loadjs('$page?rule-js=0&function=$function')",ico_plus,"{new_rule}");
	// Saving a rule, an object or a link applies nothing: this button rebuilds the
	// configuration and reloads the Load-Balancer.
	$topbuttons[]=array($tpl->framework_buildjs("/haproxy/reload","haproxy.progress","haproxy.progress",
		"progress-haproxy-restart",$function!=""?"$function();":""),ico_save,"{apply_configuration}","AsSquidAdministrator");
	$topbuttons[]=array("$function()",ico_refresh,"{refresh}");
	$TINY=array();
	$TINY["TITLE"]="{load_balancing} &raquo; {ACLS}";
	$TINY["ICO"]="fas fa-filter";
	$TINY["EXPL"]="{APP_HAPROXY_ACLS}";
	$TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."\nLoadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";
	echo acl_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;
}

// ---------------------------------------------------------------- rule

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=intval($_GET["rule-js"]);
	$function=acl_function();
	// A new rule opened from a service header comes with that service selected.
	$senc=urlencode((string)($_GET["servicename"] ?? ""));
	$title="{new_rule}";
	if($id>0){
		$r=acl_rule($id);
		if($r!==null){$title=$r->servicename."/".($r->rulename!=""?$r->rulename:"{rule} $id");}
	}
	$tpl->js_dialog1($tpl->javascript_parse_text($title),"$page?rule-tabs=$id&servicename=$senc&function=$function");
}

function rule_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=intval($_GET["rule-tabs"]);
	$function=acl_function();
	$senc=urlencode((string)($_GET["servicename"] ?? ""));
	$objects=0;
	if($id>0){
		$r=acl_rule($id);
		if($r!==null && is_array($r->links ?? null)){$objects=count($r->links);}
	}
	$params=array("{lbacl_tab_rule}"=>"$page?rule-params=$id&servicename=$senc&function=$function");
	$objs=array();
	if($id>0){$objs["{lbacl_tab_objects} ($objects)"]="$page?rule-objects=$id&function=$function";}
	// "+ Add conditions now" opens straight on the objects: tabs_default loads its first tab.
	$array=(($_GET["tab"] ?? "")=="objects" && $id>0)?$objs+$params:$params+$objs;
	echo $tpl->tabs_default($array);
}

function lbr_style():string{
	return "<style>
.lbr-form{padding:10px 4px 0 4px}
.lbr-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.lbr-label{display:block;font-size:13px;font-weight:600;color:#0f172a;margin-bottom:6px}
.lbr-input{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:9px 12px;font-size:14px;color:#0f172a;background:#fff}
.lbr-input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}
.lbr-order{display:flex;align-items:center;gap:14px;margin-top:16px}
.lbr-order .lbr-input{width:120px}
.lbr-hint{font-size:13px;color:#64748b}
.lbr-sec{font-size:14px;font-weight:600;color:#0f172a;margin:22px 0 10px 0}
.lbr-cards{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.lbr-card{display:flex;gap:12px;align-items:flex-start;border:1px solid #e2e8f0;border-radius:12px;padding:14px;cursor:pointer;background:#fff;transition:border-color .15s,background .15s}
.lbr-card:hover{border-color:#93c5fd}
.lbr-card input{margin-top:3px}
.lbr-card-on{border:2px solid #2563eb;background:#eff6ff;padding:13px}
.lbr-card-t{font-size:14px;font-weight:600;color:#0f172a}
.lbr-card-x{font-size:12px;color:#64748b;margin-top:2px}
.lbr-target{margin-top:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px}
.lbr-target:empty{display:none}
.lbr-row{display:flex;gap:10px}
.lbr-btn{border:1px solid #cbd5e1;border-radius:8px;background:#fff;padding:8px 14px;font-size:13px;color:#0f172a;cursor:pointer;white-space:nowrap}
.lbr-preview{margin-top:16px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:14px 16px}
.lbr-preview-t{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#0369a1;margin-bottom:8px}
.lbr-preview .lba-cond{background:transparent;border:0;padding:0}
.lbr-foot{display:flex;align-items:center;justify-content:flex-end;gap:12px;margin-top:20px;padding-top:16px;border-top:1px solid #e2e8f0}
.lbr-foot .lbr-msg{margin-right:auto;font-size:13px;color:#64748b}
.lbr-save{background:#2563eb;color:#fff;border:0;border-radius:8px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer}
.lbr-save:hover{background:#1d4ed8}
.lbr-cancel{background:none;border:0;font-size:14px;font-weight:600;color:#0f172a;cursor:pointer}
.lbr-err{color:#b91c1c;font-size:13px;margin-top:12px}
.lbr-err:empty{display:none}
@media(max-width:700px){.lbr-grid,.lbr-cards{grid-template-columns:1fr}}
</style>";
}

function rule_params(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=intval($_GET["rule-params"]);
	$function=acl_function();
	$t=md5(microtime().$id);
	$rule=$id>0?acl_rule($id):null;

	$services=array();
	list($json,$err)=acl_get("/haproxy/services");
	if($json!==null && is_array($json->services)){
		foreach($json->services as $s){$services[(string)$s->servicename]=(string)$s->servicename;}
	}
	$current=$rule!==null?(string)$rule->servicename:(string)key($services);
	$wanted=(string)($_GET["servicename"] ?? "");
	if($rule===null && $wanted!="" && isset($services[$wanted])){$current=$wanted;}
	if($current!="" && !isset($services[$current])){$services[$current]=$current;}
	$action=$rule!==null?intval($rule->rule_action):0;
	$data=$rule!==null?(string)$rule->rule_action_data:"";

	$opts=array();
	foreach($services as $k=>$v){
		$sel=$k===$current?" selected":"";
		$opts[]="<option value=\"".htmlspecialchars($k)."\"$sel>".htmlspecialchars($v)."</option>";
	}
	$cards=array(0=>array("{do_nothing}","{lbacl_a0_text}"),1=>array("{lbacl_do_use_backend}","{lbacl_a1_text}"),
		2=>array("{lbacl_do_use_server}","{lbacl_a2_text}"),3=>array("{lbacl_a3_title}","{lbacl_a3_text}"));
	$cardsHtml=array();
	foreach($cards as $a=>$c){
		$on=$a==$action?" lbr-card-on":"";
		$chk=$a==$action?" checked":"";
		$cardsHtml[]="<label class='lbr-card$on' data-a='$a'><input type='radio' name='lbr-action-$t' value='$a'$chk>".
			"<span><span class='lbr-card-t'>$c[0]</span><div class='lbr-card-x'>$c[1]</div></span></label>";
	}
	$conditions=$rule!==null?acl_sentence($rule,false):"";
	if($conditions==""){$conditions="<span class='lba-inc'>{lbacl_incomplete}</span>";}

	$h=array();
	$h[]="<div class='lbr-form' id='lbr-$t'>";
	$h[]="<div class='lbr-grid'>";
	$h[]="<div><label class='lbr-label'>{rulename}</label><input class='lbr-input' id='lbr-name-$t' type='text' value=\"".htmlspecialchars($rule!==null?(string)$rule->rulename:"")."\"></div>";
	$h[]="<div><label class='lbr-label'>{tcp_services}</label><select class='lbr-input' id='lbr-svc-$t'>".@implode("",$opts)."</select></div>";
	$h[]="</div>";
	$h[]="<div><div class='lbr-order'><div><label class='lbr-label'>{order}</label><input class='lbr-input' id='lbr-order-$t' type='number' min='0' value='".($rule!==null?intval($rule->zorder):0)."'></div>".
		"<span class='lbr-hint' style='margin-top:22px'>{lbacl_order_hint}</span></div></div>";
	$h[]="<div class='lbr-sec'>{lbacl_action_title}</div>";
	$h[]="<div class='lbr-cards'>".@implode("",$cardsHtml)."</div>";
	$h[]="<div class='lbr-target' id='lbr-target-$t'></div>";
	$h[]="<div class='lbr-preview'><div class='lbr-preview-t'>{lbacl_sentence}</div><div class='lba-cond'>$conditions<span id='lbr-pv-act-$t'></span></div></div>";
	$h[]="<div class='lbr-err' id='lbr-err-$t'></div>";
	$h[]="<div class='lbr-foot'><span class='lbr-msg' id='lbr-msg-$t'></span>".
		"<button type='button' class='lbr-cancel' OnClick=\"dialogInstance1.close();\">{cancel}</button>".
		"<button type='button' class='lbr-save' OnClick=\"LbrSave$t();\">{lbacl_save}</button></div>";
	$h[]="</div>";

	$labels=array();
	foreach(array(0=>"{lbacl_do_nothing}",1=>"{lbacl_do_use_backend}",2=>"{lbacl_do_use_server}",3=>"{lbacl_do_deny}") as $a=>$l){
		$labels[$a]=$tpl->javascript_parse_text($l);
	}
	$after=$function!=""?"if(typeof $function==='function'){ $function(); }":"";
	$saved=$tpl->javascript_parse_text("{success}");
	$js="<script>
(function(){
var root=document.getElementById('lbr-$t');
var labels=".json_encode($labels).";
var current=".json_encode($data).";
function action(){var r=root.querySelector('input[name=\"lbr-action-$t\"]:checked');return r?r.value:'0';}
function preview(){
  var a=action();var sel=document.getElementById('lbr-data-$t');var txt=labels[a]||'';
  if(sel && sel.selectedIndex>=0 && sel.value!==''){txt+=' : '+sel.options[sel.selectedIndex].text;}
  var cls=a=='3'?'lba-act lba-act-deny':(a=='1'||a=='2'?'lba-act lba-act-route':'lba-act');
  var pv=document.getElementById('lbr-pv-act-$t');pv.className=cls;pv.textContent=txt;
}
window.LbrPreview$t=preview;
function target(){
  var a=action();var box=document.getElementById('lbr-target-$t');
  if(a!='1' && a!='2'){box.innerHTML='';preview();return;}
  var svc=encodeURIComponent(document.getElementById('lbr-svc-$t').value);
  LoadAjaxSilent('lbr-target-$t','$page?rule-target=$id&t=$t&method='+a+'&servicename='+svc+'&current='+encodeURIComponent(current));
}
window.LbrTarget$t=target;
root.querySelectorAll('.lbr-card').forEach(function(c){c.addEventListener('click',function(){
  root.querySelectorAll('.lbr-card').forEach(function(x){x.classList.remove('lbr-card-on');});
  c.classList.add('lbr-card-on');c.querySelector('input').checked=true;target();});});
document.getElementById('lbr-svc-$t').addEventListener('change',function(){current='';target();});
window.LbrSave$t=function(){
  var sel=document.getElementById('lbr-data-$t');
  var d={'rule-save':'$id','rulename':document.getElementById('lbr-name-$t').value,
    'servicename':document.getElementById('lbr-svc-$t').value,'zorder':document.getElementById('lbr-order-$t').value,
    'rule_action':action(),'rule_action_data':sel?sel.value:''};
  var err=document.getElementById('lbr-err-$t');err.innerHTML='';
  $.post('$page',d,function(res){
    res=String(res);
    if(res.trim().length<=3){
      $after
      ".($id==0?"dialogInstance1.close();":"document.getElementById('lbr-msg-$t').textContent=".json_encode($saved).";current=d.rule_action_data;")."
      return;
    }
    if(res.indexOf('jserror:')===0){res=res.substring(8);}
    err.innerHTML=res;
  });
};
target();preview();
})();
</script>";
	echo lbr_style().acl_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).$js;
}

// The target picker of use_backend (a backend group) or use-server (a server)
// for the chosen service.
function rule_target(){
	$tpl=new template_admin();
	$t=preg_replace('/[^a-f0-9]/','',(string)($_GET["t"] ?? ""));
	$method=intval($_GET["method"] ?? 0);
	$svc=(string)($_GET["servicename"] ?? "");
	$cur=(string)($_GET["current"] ?? "");
	$options=array();
	if($method==1){
		list($json,$err)=acl_get("/haproxy/backends/groups?servicename=".urlencode($svc));
		if($json!==null && is_array($json->backend_groups)){
			foreach($json->backend_groups as $g){$options[(string)$g->id]=$g->groupname." (".intval($g->backends)." {servers})";}
		}
		$label="{lbacl_target_group}";
	}else{
		list($json,$err)=acl_get("/haproxy/backends?servicename=".urlencode($svc));
		if($json!==null && is_array($json->backends)){
			foreach($json->backends as $b){$options[(string)$b->id]=$b->backendname." (".$b->listen_ip.":".$b->listen_port.")";}
		}
		$label="{lbacl_target_server}";
	}
	if($cur!="" && !isset($options[$cur])){$options[$cur]=$cur;}
	$h=array();
	$h[]="<label class='lbr-label'>$label</label>";
	if(count($options)==0){
		$h[]="<div class='lbr-hint'>{lbacl_no_target}</div><input type='hidden' id='lbr-data-$t' value=''>";
	}else{
		$o=array();
		foreach($options as $k=>$v){$o[]="<option value=\"".htmlspecialchars($k)."\"".($k===$cur?" selected":"").">".htmlspecialchars($v)."</option>";}
		$h[]="<div class='lbr-row'><select class='lbr-input' id='lbr-data-$t' OnChange=\"LbrPreview$t();\">".@implode("",$o)."</select>".
			"<button type='button' class='lbr-btn' OnClick=\"Loadjs('fw.haproxy.services.php?balancer-js=".urlencode($svc)."');\">{lbacl_manage}</button></div>";
	}
	$h[]="<script>if(typeof LbrPreview$t==='function'){LbrPreview$t();}</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n",$h));
}

function rule_save(){
	$tpl=new template_admin();
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return;}
	$tpl->CLEAN_POST();
	$id=intval($_POST["rule-save"]);
	if($id>0 && acl_rule($id)===null){echo $tpl->post_error("{rule} $id: {no_data}");return;}
	$svc=trim((string)$_POST["servicename"]);
	if($svc==""){echo $tpl->post_error("{tcp_services}: {no_data}");return;}
	$action=intval($_POST["rule_action"]);
	if($action<0 || $action>3){$action=0;}
	$data=($action==1 || $action==2)?(string)$_POST["rule_action_data"]:"";
	if(($action==1 || $action==2) && $data==""){echo $tpl->post_error("{lbacl_no_target}");return;}
	$e=acl_post("/haproxy/acls/rule/save",array("id"=>$id,"rulename"=>(string)$_POST["rulename"],"servicename"=>$svc,
		"zorder"=>intval($_POST["zorder"]),"rule_action"=>$action,"rule_action_data"=>$data));
	if($e!=""){echo $tpl->post_error($e);}
}

function rule_enable_js(){
	header("content-type: application/x-javascript");
	$id=intval($_GET["rule-enable-js"]);
	$e=acl_post("/haproxy/acls/rule/enable",array("id"=>$id,"state"=>intval($_GET["state"] ?? 0)));
	if($e!=""){acl_js_error($e);return;}
	$function=acl_function();
	if($function!=""){echo "$function();";}
}

function rule_move_js(){
	header("content-type: application/x-javascript");
	$id=intval($_GET["rule-move-js"]);
	$dir=($_GET["direction"] ?? "")=="up"?"up":"down";
	$e=acl_post("/haproxy/acls/rule/move",array("id"=>$id,"direction"=>$dir));
	if($e!=""){acl_js_error($e);return;}
	$function=acl_function();
	if($function!=""){echo "$function();";}
}

function rule_delete_js(){
	$tpl=new template_admin();
	$id=intval($_GET["rule-delete-js"]);
	$function=acl_function();
	$r=acl_rule($id);
	$name=$r!==null && $r->rulename!=""?$r->rulename:"{rule} $id";
	$tpl->js_confirm_delete($tpl->javascript_parse_text($name),"rule-delete",$id,$function!=""?"$function()":"");
}

function rule_delete(){
	$tpl=new template_admin();
	$e=acl_post("/haproxy/acls/rule/delete",array("id"=>intval($_POST["rule-delete"])));
	if($e!=""){echo $tpl->post_error($e);}
}

// ---------------------------------------------------------------- objects of a rule

function rule_objects(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=intval($_GET["rule-objects"]);
	$function=acl_function();
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin:10px 0'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?newgroup-js=$id&function=$function');\"><i class='fa fa-plus'></i> {new_object} </label>";
	$html[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?linkgroup-js=$id&function=$function');\"><i class='fa fa-link'></i> {link_object} </label>";
	$html[]="</div>";
	$html[]="<div id='acl-objects-$id'></div>";
	$html[]="<script>function AclObjects$id(){LoadAjaxSilent('acl-objects-$id','$page?objects-table=$id&function=$function');".($function!=""?"if(typeof $function==='function'){ $function(); }":"")."}";
	$html[]="LoadAjaxSilent('acl-objects-$id','$page?objects-table=$id&function=$function');</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function objects_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=intval($_GET["objects-table"]);
	$function=acl_function();
	list($json,$err)=acl_get("/haproxy/acls/links?ruleid=$id");
	$links=($json!==null && is_array($json->links))?$json->links:array();
	$types=acl_group_types();

	$html[]="<div class='awc-card'><div class='awc-scroll'><table class='awc-tbl'><thead><tr>";
	$html[]="<th style='width:1%'>{order}</th><th>{objects}</th><th style='width:1%'>{items}</th>";
	$html[]="<th class='center' style='width:1%'>{reverse}</th><th class='center' style='width:1%'>{operator}</th>";
	$html[]="<th class='center' style='width:1%'>{enabled}</th>";
	$html[]="<th style='width:1%;text-align:right'>{actions}</th></tr></thead><tbody>";
	if($err!=""){
		$html[]="<tr><td colspan='7'><div class='awc-empty'>".htmlspecialchars($err)."</div></td></tr>";
	}elseif(count($links)==0){
		$html[]="<tr><td colspan='7'><div class='awc-empty'>{no_group_defined}</div></td></tr>";
	}
	$q="&ruleid=$id&function=$function";
	// The column shows the rank in the sorted list: zorder is internal, it can be
	// zero or skip values.
	$rank=0;
	$last=count($links);
	foreach($links as $l){
		$rank++;
		$lid=intval($l->id);
		$gid=intval($l->groupid);
		$on=(bool)$l->group_enabled;
		$type=$types[$l->grouptype] ?? htmlspecialchars((string)$l->grouptype);
		$revert=$tpl->icon_check($l->revert?1:0,"Loadjs('$page?link-revert-js=$lid&state=".($l->revert?0:1)."$q')",null,"AsSquidAdministrator");
		$op=$l->operator?"{or}":"{and}";
		$operator="<a class='awc-badge awc-badge-on' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?link-operator-js=$lid&state=".($l->operator?0:1)."$q')\">$op</a>";
		// The flag belongs to the OBJECT: switching it affects every rule using it.
		$enable=$tpl->icon_check($on?1:0,"Loadjs('$page?link-group-enable-js=$gid&state=".($on?0:1)."$q')",null,"AsSquidAdministrator");
		$up=$rank==1?"":$tpl->icon_up("Loadjs('$page?link-move-js=$lid&direction=up$q')","AsSquidAdministrator");
		$down=$rank==$last?"":$tpl->icon_down("Loadjs('$page?link-move-js=$lid&direction=down$q')","AsSquidAdministrator");
		$del=$tpl->icon_unlink("Loadjs('$page?link-delete-js=$lid$q')","AsSquidAdministrator");
		$html[]="<tr>";
		$html[]="<td class='center awc-mono'>$rank</td>";
		$html[]="<td><div class='awc-ident'><span class='awc-ico".($on?"":" awc-ico-off")."'><i class='fas fa-object-group'></i></span>".
			"<span><span class='awc-name".($on?"":" awc-name-off")."'><a href=\"javascript:blur();\" OnClick=\"Loadjs('$page?group-js=".intval($l->groupid)."$q')\">".htmlspecialchars((string)$l->groupname)."</a></span>".
			"<div class='awc-meta'>$type</div></span></div></td>";
		$html[]="<td class='center awc-mono'>".intval($l->items)."</td>";
		$html[]="<td class='center'>$revert</td>";
		$html[]="<td class='center'>$operator</td>";
		$html[]="<td class='center'>$enable</td>";
		$html[]="<td nowrap><div class='awc-acts'>$up$down$del</div></td>";
		$html[]="</tr>";
	}
	$html[]="</tbody></table></div></div>";
	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html))."<script>\n".$tpl->ICON_SCRIPTS_COMPILE()."\n</script>";
}

function link_after():string{
	$id=intval($_GET["ruleid"] ?? 0);
	return "if(typeof AclObjects$id==='function'){AclObjects$id();}";
}

function link_state_js($what){
	header("content-type: application/x-javascript");
	$lid=intval($_GET["link-$what-js"]);
	$e=acl_post("/haproxy/acls/link/$what",array("id"=>$lid,"state"=>intval($_GET["state"] ?? 0)));
	if($e!=""){acl_js_error($e);return;}
	echo link_after();
}

// Switches the linked OBJECT on or off (same route as fw.haproxy.groups.php); the
// rule list is refreshed too, since its sentence leaves disabled objects out.
function link_group_enable_js(){
	header("content-type: application/x-javascript");
	$gid=intval($_GET["link-group-enable-js"]);
	$e=acl_post("/haproxy/acls/group/enable",array("id"=>$gid,"state"=>intval($_GET["state"] ?? 0)==1?1:0));
	if($e!=""){acl_js_error($e);return;}
	echo link_after().acl_function_call();
}

function link_move_js(){
	header("content-type: application/x-javascript");
	$lid=intval($_GET["link-move-js"]);
	$dir=($_GET["direction"] ?? "")=="up"?"up":"down";
	$e=acl_post("/haproxy/acls/link/move",array("id"=>$lid,"direction"=>$dir));
	if($e!=""){acl_js_error($e);return;}
	echo link_after();
}

function link_delete_js(){
	header("content-type: application/x-javascript");
	$lid=intval($_GET["link-delete-js"]);
	$e=acl_post("/haproxy/acls/link/delete",array("id"=>$lid));
	if($e!=""){acl_js_error($e);return;}
	echo link_after();
}

// ---------------------------------------------------------------- new / linked group

function newgroup_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=intval($_GET["newgroup-js"]);
	$tpl->js_dialog2($tpl->javascript_parse_text("{new_group}"),"$page?newgroup-popup=$id&function=".acl_function());
}

function newgroup_popup(){
	$tpl=new template_admin();
	$id=intval($_GET["newgroup-popup"]);
	$form[]=$tpl->field_hidden("newgroup",$id);
	$form[]=$tpl->field_text("groupname","{groupname}","",true);
	$form[]=$tpl->field_array_hash(acl_group_types(),"grouptype","{type}","src",true);
	echo $tpl->form_outside("",@implode("\n",$form),"","{add}","dialogInstance2.close();if(typeof AclObjects$id==='function'){AclObjects$id();}","AsSquidAdministrator");
}

function newgroup_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ruleid=intval($_POST["newgroup"]);
	$type=(string)$_POST["grouptype"];
	if(!isset(acl_group_types()[$type])){echo $tpl->post_error("{type}: ".htmlspecialchars($type));return;}
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/haproxy/acls/group/save",
		array("id"=>0,"groupname"=>(string)$_POST["groupname"],"grouptype"=>$type,"state"=>1,"reload"=>false)));
	if(!is_object($json) || !$json->Status){echo $tpl->post_error(is_object($json)?$json->Error:"{error}");return;}
	$gid=intval($json->Info);
	if($gid==0){echo $tpl->post_error("{error}");return;}
	$e=acl_post("/haproxy/acls/link/add",array("ruleid"=>$ruleid,"groupid"=>$gid));
	if($e!=""){echo $tpl->post_error($e);}
}

function linkgroup_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=intval($_GET["linkgroup-js"]);
	$tpl->js_dialog2($tpl->javascript_parse_text("{link_group}"),"$page?linkgroup-popup=$id&function=".acl_function());
}

function linkgroup_popup(){
	$tpl=new template_admin();
	$id=intval($_GET["linkgroup-popup"]);
	list($json,$err)=acl_get("/haproxy/acls/groups");
	$types=acl_group_types();
	$options=array();
	if($json!==null && is_array($json->groups)){
		foreach($json->groups as $g){
			$options[$g->id]=$g->groupname." (".$tpl->javascript_parse_text($types[$g->grouptype] ?? $g->grouptype)." - ".intval($g->items).")";
		}
	}
	if(count($options)==0){
		echo $tpl->_ENGINE_parse_body($tpl->div_warning("{objects}||{no_data}"));
		return;
	}
	$form[]=$tpl->field_hidden("link-group",$id);
	$form[]=$tpl->field_array_hash($options,"groupid","{objects}","",true);
	echo $tpl->form_outside("",@implode("\n",$form),"","{link_object}","dialogInstance2.close();if(typeof AclObjects$id==='function'){AclObjects$id();}","AsSquidAdministrator");
}

function linkgroup_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$e=acl_post("/haproxy/acls/link/add",array("ruleid"=>intval($_POST["link-group"]),"groupid"=>intval($_POST["groupid"])));
	if($e!=""){echo $tpl->post_error($e);}
}

// ---------------------------------------------------------------- group editor

function acl_group($id){
	list($json,$err)=acl_get("/haproxy/acls/group?id=".intval($id));
	if($json===null){return null;}
	return $json->group;
}

function group_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$gid=intval($_GET["group-js"]);
	$ruleid=intval($_GET["ruleid"] ?? 0);
	$g=acl_group($gid);
	$title=$g!==null?$g->groupname:"{objects}";
	$tpl->js_dialog2($tpl->javascript_parse_text($title),"$page?group-tabs=$gid&ruleid=$ruleid&function=".acl_function());
}

function group_tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$gid=intval($_GET["group-tabs"]);
	$ruleid=intval($_GET["ruleid"] ?? 0);
	$fn=acl_function();
	$array["{parameters}"]="$page?group-params=$gid&ruleid=$ruleid&function=$fn";
	$array["{items}"]="$page?group-items=$gid&ruleid=$ruleid&function=$fn";
	echo $tpl->tabs_default($array);
}

function group_params(){
	$tpl=new template_admin();
	$gid=intval($_GET["group-params"]);
	$ruleid=intval($_GET["ruleid"] ?? 0);
	$g=acl_group($gid);
	if($g===null){echo $tpl->_ENGINE_parse_body($tpl->div_error("{objects} $gid: {no_data}"));return;}
	$types=acl_group_types();
	$form[]=$tpl->field_hidden("group-save",$gid);
	$form[]=$tpl->field_info("grouptype","{type}",$types[$g->grouptype] ?? (string)$g->grouptype); // field_info filtre la valeur (pentest 2026-11-04)
	$form[]=$tpl->field_text("groupname","{groupname}",$g->groupname,true);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$g->enabled?1:0);
	echo $tpl->form_outside("",@implode("\n",$form),"","{apply}","if(typeof AclObjects$ruleid==='function'){AclObjects$ruleid();}".acl_function_call(),"AsSquidAdministrator");
}

function group_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$gid=intval($_POST["group-save"]);
	$g=acl_group($gid);
	if($g===null){echo $tpl->post_error("{objects} $gid: {no_data}");return;}
	$e=acl_post("/haproxy/acls/group/save",array("id"=>$gid,"groupname"=>(string)$_POST["groupname"],
		"grouptype"=>(string)$g->grouptype,"state"=>intval($_POST["enabled"] ?? 0)));
	if($e!=""){echo $tpl->post_error($e);}
}

function group_items(){
	$tpl=new template_admin();
	$gid=intval($_GET["group-items"]);
	$ruleid=intval($_GET["ruleid"] ?? 0);
	$g=acl_group($gid);
	if($g===null){echo $tpl->_ENGINE_parse_body($tpl->div_error("{objects} $gid: {no_data}"));return;}
	list($json,$err)=acl_get("/haproxy/acls/items?groupid=$gid");
	$patterns=($json!==null && is_array($json->patterns))?$json->patterns:array();
	$explain=array("src"=>"{acl_src_text}","dst"=>"{acl_dst_text}","hdr(host)"=>"{squid_ask_domain}","dst_port"=>"{acl_squid_remote_ports_explain}");
	$html[]=$tpl->div_explain(htmlspecialchars((string)$g->groupname)."||".($explain[$g->grouptype] ?? ""));
	$form[]=$tpl->field_hidden("group-items-save",$gid);
	$form[]=$tpl->field_textareacode("items",null,@implode("\n",$patterns));
	$html[]=$tpl->form_outside("",@implode("\n",$form),"","{apply}","if(typeof AclObjects$ruleid==='function'){AclObjects$ruleid();}".acl_function_call(),"AsSquidAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function group_items_save(){
	$tpl=new template_admin();
	// The form posts every field URL-encoded: CLEAN_POST() is what decodes
	// them. Without it a list arrived as ONE pattern, "10.0.0.0%2F8%0A...".
	// It only decodes and trims $_POST; the patterns are validated and bound by
	// the daemon.
	$tpl->CLEAN_POST();
	$gid=intval($_POST["group-items-save"]);
	$e=acl_post("/haproxy/acls/items/save",array("groupid"=>$gid,"patterns_text"=>(string)$_POST["items"]));
	if($e!=""){echo $tpl->post_error($e);}
}
