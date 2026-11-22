<?php
/**
 * DNS configuration pushed to every enabled agent of a group (the hostname is
 * never changed for a group). Same form kit as the node tab: ressources/class.netagent.dns.inc.
 *
 * Entry:  tab "DNS" of fw.netagents.groups.network.php -> ?id={group_id}
 *         Loadjs('fw.netagents.group.dns.php?dns-js={group_id}') -> same form in a dialog
 * API:    GET  /netagents/groups/{id}                 (name, agents[]: id, hostname, enabled, artica)
 *         GET  /netagents/status/{agent_id}           (pre-fill from the first enabled node that reports DNS)
 *         POST /netagents/system/group/{id}/network   {"dns":{...}}
 *         -> {group_id, total, success, failed, results:[{agent_id,hostname,success,message,ignored[]}]}
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.dns.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){ $GLOBALS["CLASS_SOCKETS"]=new sockets(); }

if(!nadns_is_admin()){
    if(isset($_GET["dns-js"])){ exit; }
    $tpl=new template_admin();
    if(isset($_POST["save"])){ nadns_reply(false,$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")); exit; }
    echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS}"));
    exit;
}
if(isset($_GET["dns-js"])){ gdns_js(); exit; }
if(isset($_POST["save"])){ gdns_save(); exit; }
gdns_form();

/** Opens the group form in a dialog. */
function gdns_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["dns-js"]);
    $g=gdns_group_model($id);
    $name=$g["name"]!=""?nadns_h($g["name"]):"#$id";
    return $tpl->js_dialog3("{dns_settings} &mdash; $name","$page?id=$id",1100);
}

/** The form: notices, group card and name servers, resolver and options, action bar. */
function gdns_form():bool{
    $tpl=new template_admin();
    $id=nadns_id();
    $g=gdns_group_model($id);
    if($g["error"]!=""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($g["error"]));
        return false;
    }
    $m=gdns_reference_model($g);
    $m["artica"]=$g["artica"]>0;
    $root="nadns-g$id";

    $h=array();
    $h[]="<div class='nadns' id='$root'>";
    $h[]=gdns_notice_empty($g);
    $h[]=gdns_notice_artica($g);
    $h[]="<div class='nadns-grid'>";
    $h[]="<div class='nadns-col'>".gdns_card_group($g,$m).nadns_card_nameservers($m)."</div>";
    $h[]="<div class='nadns-col'>".nadns_card_resolver($m).nadns_card_options($m)."</div>";
    $h[]="</div>";
    $h[]=nadns_actionbar(gdns_apply_label($g));
    $h[]="</div>";

    $extra=array("mode"=>"group","root"=>$root,"targets"=>$g["enabled"],
        "t"=>array("save"=>gdns_apply_label($g),"ready"=>$tpl->_ENGINE_parse_body("{nadns_ready}")));
    echo nadns_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).nadns_js($id,$m,$extra);
    return true;
}

/** Pushes the form to every enabled agent; answers JSON {ok, level, html}. */
function gdns_save():void{
    $tpl=new template_admin();
    $id=nadns_id();
    $error="";
    $dns=nadns_post_dns($error);
    if($dns===null){ nadns_reply(false,$error); return; }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/system/group/$id/network",array("dns"=>$dns)));
    if(!is_object($json)){ nadns_reply(false,nadns_error_html("{nadns_err_daemon}")); return; }
    if(isset($json->Status) && !$json->Status){
        nadns_reply(false,nadns_h(isset($json->Error)?$json->Error:"error"));
        return;
    }
    $total=isset($json->total)?intval($json->total):0;
    $success=isset($json->success)?intval($json->success):0;
    $fails=array();
    $partial=array();
    if(isset($json->results) && is_array($json->results)){
        foreach($json->results as $r){
            if(!is_object($r)){ continue; }
            if(empty($r->success)){ $fails[]=$r; continue; }
            if(isset($r->ignored) && is_array($r->ignored) && count($r->ignored)>0){ $partial[]=$r; }
        }
    }

    $level=gdns_result_level($total,$success);
    if($level=="ok" && count($partial)>0){ $level="warn"; }
    $out=array("ok"=>true,"level"=>$level,"html"=>gdns_result_html($total,$success,$fails).gdns_ignored_html($partial));
    admin_tracks("Group #$id: DNS configuration (".implode(", ",$dns["nameservers"]).") pushed to $total agents ($success success, ".count($fails)." failed)");
    header("content-type: application/json");
    echo json_encode($out);
}

// ── Data ─────────────────────────────────────────────────────────────────────

function gdns_group_model(int $id):array{
    $g=array("error"=>"","name"=>"","members"=>array(),"enabled"=>0,"disabled"=>0,"artica"=>0);
    $json=nadns_rest("/netagents/groups/$id");
    if(!is_object($json)){ $g["error"]="{nadns_err_daemon}"; return $g; }
    if(isset($json->Status) && !$json->Status){
        $g["error"]=nadns_h(isset($json->Error)?$json->Error:"error");
        return $g;
    }
    $g["name"]=isset($json->name)?trim((string)$json->name):"";
    $agents=(isset($json->agents) && is_array($json->agents))?$json->agents:array();
    foreach($agents as $a){
        if(!is_object($a)){ continue; }
        $aid=isset($a->id)?intval($a->id):0;
        $host=(isset($a->hostname) && trim((string)$a->hostname)!="")?trim((string)$a->hostname):"#$aid";
        $on=!empty($a->enabled);
        $artica=!empty($a->artica) || strlen(isset($a->artica_version)?(string)$a->artica_version:"")>3;
        $g["members"][]=array("id"=>$aid,"hostname"=>$host,"enabled"=>$on,"artica"=>$artica);
        if($on){
            $g["enabled"]++;
            if($artica){ $g["artica"]++; }
        }else{
            $g["disabled"]++;
        }
    }
    return $g;
}

/** Pre-fill: the first enabled node (5 at most are asked) that reports its resolver. */
function gdns_reference_model(array &$g):array{
    $g["ref"]="";
    $g["ref_at"]=0;
    $asked=0;
    foreach($g["members"] as $mb){
        if(!$mb["enabled"] || $mb["id"]<1){ continue; }
        if(++$asked>5){ break; }
        $m=nadns_model(nadns_rest("/netagents/status/".$mb["id"]));
        if($m["error"]!="" || $m["pending"] || $m["nodns"]){ continue; }
        $g["ref"]=$mb["hostname"];
        $g["ref_at"]=$m["status_at"];
        return $m;
    }
    return nadns_model(json_decode('{"dns":{}}'));
}

// ── Widgets ──────────────────────────────────────────────────────────────────

function gdns_apply_label(array $g):string{
    $tpl=new template_admin();
    if($g["enabled"]==1){ return $tpl->_ENGINE_parse_body("{nadns_apply_node}"); }
    return str_replace("%s",strval($g["enabled"]),$tpl->_ENGINE_parse_body("{nadns_apply_nodes}"));
}

function gdns_notice_empty(array $g):string{
    if($g["enabled"]>0){ return ""; }
    return "<div class='nadns-notice nadns-notice-stale' role='status'>".nadns_ico("fas fa-circle-exclamation")
        ."<span>{nadns_group_empty}</span></div>";
}

function gdns_notice_artica(array $g):string{
    if($g["artica"]<1){ return ""; }
    return "<div class='nadns-notice nadns-notice-info' role='status'>".nadns_ico("fas fa-circle-info")
        ."<span><strong>".$g["artica"]." / ".$g["enabled"]." {nadns_group_artica}</strong> {nadns_artica_text}</span></div>";
}

function gdns_card_group(array $g,array $m):string{
    $name=$g["name"]!=""?nadns_h($g["name"]):"&mdash;";
    $h=array();
    $h[]="<section class='nadns-card'>";
    $h[]=nadns_card_head("fas fa-layer-group","{nadns_applies_to}");
    $h[]="<div class='nadns-gname'>$name</div>";
    $h[]="<div class='nadns-facts'>";
    $h[]=gdns_fact($g["enabled"],"{nadns_enabled_nodes}");
    $h[]=gdns_fact($g["disabled"],"{nadns_skipped_nodes}");
    $h[]=gdns_fact($g["artica"],"{nadns_artica_nodes}");
    $h[]="</div>";
    $h[]=gdns_members($g);
    $h[]=gdns_prefill_line($g);
    $h[]="<p class='nadns-help'>{group_dns_explain}</p>";
    $h[]="</section>";
    return implode("\n",$h);
}

function gdns_fact(int $n,string $label):string{
    return "<div class='nadns-fact'><b>$n</b><span>$label</span></div>";
}

/** Member names (12 at most), enabled first; disabled ones struck through (they are skipped). */
function gdns_members(array $g):string{
    if(count($g["members"])==0){ return ""; }
    $on=array(); $off=array();
    foreach($g["members"] as $mb){
        $title=nadns_h($mb["hostname"]);
        if($mb["enabled"]){ $on[]="<span class='nadns-member nadns-mono' title='$title'>$title</span>"; continue; }
        $off[]="<span class='nadns-member nadns-member-off nadns-mono' title='$title'>$title</span>";
    }
    $all=array_merge($on,$off);
    $more="";
    if(count($all)>12){
        $tpl=new template_admin();
        $more="<span class='nadns-member'>".str_replace("%s",strval(count($all)-12),$tpl->_ENGINE_parse_body("{nadns_more}"))."</span>";
        $all=array_slice($all,0,12);
    }
    return "<div class='nadns-members'>".implode("",$all).$more."</div>";
}

function gdns_prefill_line(array $g):string{
    if($g["ref"]==""){
        return $g["enabled"]>0?"<p class='nadns-help'>".nadns_ico("fas fa-circle-info")." {nadns_prefilled_none}</p>":"";
    }
    $when=$g["ref_at"]>0?" &middot; ".date("Y-m-d H:i",$g["ref_at"]):"";
    return "<p class='nadns-help'>".nadns_ico("fas fa-copy")." {nadns_prefilled} <span class='nadns-mono'>".nadns_h($g["ref"])."</span>$when</p>";
}

/** Nodes where Artica kept only part of the form (articarest results[].ignored), 10 at most. */
function gdns_ignored_html(array $partial):string{
    if(count($partial)==0){ return ""; }
    $tpl=new template_admin();
    $h=array();
    $h[]="<span class='nadns-fb-node'>".$tpl->_ENGINE_parse_body("{netagent_dns_ignored}")."</span>";
    foreach(array_slice($partial,0,10) as $r){
        $host=nadns_h((isset($r->hostname) && $r->hostname!="")?$r->hostname:"#".(isset($r->agent_id)?intval($r->agent_id):0));
        $chips=array();
        foreach($r->ignored as $item){ $chips[]="<span class='nadns-fb-chip'>".nadns_h((string)$item)."</span>"; }
        $h[]="<span class='nadns-fb-node'><span class='nadns-mono'>$host</span> ".implode("",$chips)."</span>";
    }
    if(count($partial)>10){
        $h[]="<span class='nadns-fb-node'>".str_replace("%s",strval(count($partial)-10),$tpl->_ENGINE_parse_body("{nadns_more}"))."</span>";
    }
    return implode("",$h);
}

function gdns_result_level(int $total,int $success):string{
    if($total>0 && $success==$total){ return "ok"; }
    if($success>0){ return "warn"; }
    return "err";
}

/** Summary line, the failed nodes (10 at most) and the wait notice when something was applied. */
function gdns_result_html(int $total,int $success,array $fails):string{
    $tpl=new template_admin();
    if($total==0){ return $tpl->_ENGINE_parse_body("{nadns_group_empty}"); }
    $h=array();
    $h[]="<strong>".str_replace(array("%a","%t"),array(strval($success),strval($total)),$tpl->_ENGINE_parse_body("{nadns_group_applied}"))."</strong>";
    $shown=array_slice($fails,0,10);
    foreach($shown as $r){
        $host=nadns_h((isset($r->hostname) && $r->hostname!="")?$r->hostname:"#".(isset($r->agent_id)?intval($r->agent_id):0));
        $msg=nadns_h(isset($r->message)?$r->message:"");
        $h[]="<span class='nadns-fb-node'><span class='nadns-mono'>$host</span>".($msg!=""?" &mdash; $msg":"")."</span>";
    }
    if(count($fails)>10){
        $h[]="<span class='nadns-fb-node'>".str_replace("%s",strval(count($fails)-10),$tpl->_ENGINE_parse_body("{nadns_more}"))."</span>";
    }
    if($success>0){ $h[]="<span class='nadns-fb-node'>".$tpl->_ENGINE_parse_body("{meta_wait_parameters}")."</span>"; }
    return implode("",$h);
}
