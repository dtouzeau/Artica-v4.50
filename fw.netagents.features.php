<?php
// awc_*() helpers: class.statistics_appliance.inc MUST stay included unconditionally.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// "Features" tab of a remote agent (fw.netagents.list.php agent_info_tabs(), ?id={agent_id}).
//
// Artica node: the feature catalog of the node (same sections, states and reasons as its own
// fw.system.services.php), read through Meta:
//   GET  /netagents/features/{id}[?refresh=1]   catalog (+ managed_by, stale copy, refreshing)
//   POST /netagents/features/{id}/action        {key, action:on|off} -> 202 {job} | 200 already
//                                               | 403 disabled/blacklisted | 409 managed/busy/
//                                               unsupported | 422 node console/blocked
//   GET  /netagents/features/{id}/jobs          last job of the node, followed every 5 s
// A feature held by a Meta policy, or installed from the node console, has no button.
// Artica too old (409 unsupported): only the "update" message, never the agent cards below.
//
// Node WITHOUT Artica (artica:false): the three debian-agent cards (DHCP, Unbound, RDS Proxy),
// functions naflg_*:
//   GET /netagents/status/{id}, DHCP POST /netagents/dhcp/{id}/install, GET .../uninstall,
//   Unbound POST /netagents/unbound/install/{id}, GET /netagents/unbound/uninstall/{id}.
//
// Writes: meta_can_view() && !meta_policy_request_ro(), checked again in every handler.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.sock.inc");
include_once(dirname(__FILE__)."/ressources/class.netagents.features.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

if(isset($_POST["nafeat-action"])){nafp_post_action();exit;}
if(isset($_POST["naflg-action"])){naflg_post_action();exit;}
if(isset($_GET["action-js"])){nafp_action_js();exit;}
if(isset($_GET["legacy-action-js"])){naflg_action_js();exit;}
if(isset($_GET["job-status"])){nafp_job_status();exit;}
if(isset($_GET["legacy"])){naflg_cards();exit;}
if(isset($_GET["table"])){table();exit;}
start();

function start():bool{
    $id=intval($_GET["id"] ?? 0);
    $page=CurrentPageName();
    echo "<div class='ak' id='nafx-host-$id'><div id='nafx-job-$id' aria-live='polite'></div><div id='nafx-list-$id'></div></div>";
    echo nafeat_filter_js().nafp_js();
    echo "<script>if(window.NafeatJobInit){delete NafeatJobInit[$id];}LoadAjaxSilent('nafx-list-$id','$page?table=$id');</script>";
    return true;
}

// Job follow and list reload. One timer per agent (window.NafeatJobT).
function nafp_js():string{
    return <<<'JS'
<script>
(function(){
  var P='fw.netagents.features.php';
  window.NafeatJobT=window.NafeatJobT||{};
  window.NafeatJobE=window.NafeatJobE||{};
  window.NafeatJobInit=window.NafeatJobInit||{};
  window.NafeatReload=function(id,refresh){
    if(document.getElementById('nafx-list-'+id)&&typeof LoadAjaxSilent==='function'){
      LoadAjaxSilent('nafx-list-'+id,P+'?table='+id+(refresh?'&refresh=1':''));
    }
  };
  window.NafeatJob=function(id,job){
    if(NafeatJobT[id]){clearTimeout(NafeatJobT[id]);NafeatJobT[id]=0;}
    var el=document.getElementById('nafx-job-'+id);if(!el){return;}
    function again(j){NafeatJobT[id]=setTimeout(function(){NafeatJob(id,j);},5000);}
    $.get(P+'?job-status='+id+'&job='+encodeURIComponent(job||''),function(h){
      el=document.getElementById('nafx-job-'+id);if(!el){return;}
      el.innerHTML=h;
      var m=el.querySelector('[data-js]'),s=m?m.getAttribute('data-js'):'none',j=m?(m.getAttribute('data-job')||''):'';
      if(s==='running'){NafeatJobE[id]=0;again(j||job);return;}
      if(s==='error'){NafeatJobE[id]=(NafeatJobE[id]||0)+1;if(NafeatJobE[id]<24){again(job);}return;}
      NafeatJobE[id]=0;
      if(s==='done'){NafeatReload(id,false);}
    },'html').fail(function(){NafeatJobE[id]=(NafeatJobE[id]||0)+1;if(NafeatJobE[id]<24){again(job);}});
  };
  // Once per opening of the tab: is a job already running on the node?
  window.NafeatJobFirst=function(id){
    if(NafeatJobInit[id]){return;}
    NafeatJobInit[id]=1;NafeatJob(id,'');
  };
  // After a confirmed action (job id kept in the session by the POST handler).
  window.NafeatAfter=function(id){NafeatJobInit[id]=1;NafeatJob(id,'');};
})();
</script>
JS;
}

function table():bool{
    $id=intval($_GET["table"] ?? 0);
    $tpl=new template_admin();
    if($id<=0){echo $tpl->div_error("{svcx_catalog_unavailable}");return false;}
    $f=nafeat_fetch($id,($_GET["refresh"] ?? "")==="1");
    switch($f["kind"]){
        case "noartica":
            return naflg_render($id);
        case "unsupported_node":
            echo nafp_banner($tpl,"ak-warn","fa-circle-up","{nafeat_update_artica}");
            return true;
        case "unsupported_agent":
            echo nafp_banner($tpl,"ak-warn","fa-circle-up","{nafeat_update_agent}");
            return true;
        case "missing":
            echo nafp_banner($tpl,"ak-warn","fa-circle-up","{nafeat_meta_missing}");
            return true;
        case "artica":
            echo nafp_render($tpl,$id,$f);
            return true;
    }
    $h="<div class='nafx'>".nafp_head($id,$f)
        ."<div class='ak-banner ak-err'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>"
        ."<span class='ak-banner-m'>{svcx_catalog_unavailable}<br>".nafeat_esc($f["error"])."</span></div></div>";
    echo nafeat_style().$tpl->_ENGINE_parse_body($h)."<script>if(typeof NoSpinner==='function'){NoSpinner();}</script>";
    return true;
}

function nafp_banner($tpl,string $tone,string $ico,string $text):string{
    return $tpl->_ENGINE_parse_body("<div class='ak-banner $tone' style='margin:10px 0'><i class='fas $ico' aria-hidden='true'></i>"
        ."<span class='ak-banner-m'>$text</span></div>")."<script>if(typeof NoSpinner==='function'){NoSpinner();}</script>";
}

function nafp_head(int $id,array $f):string{
    $sub=array();
    if($f["artica_version"]!==""){$sub[]="Artica <span class='ak-mono'>".nafeat_esc($f["artica_version"])."</span>";}
    if($f["computed_at"]>86400){$sub[]="{nafeat_computed_at} <span class='ak-mono'>".date("Y-m-d H:i",$f["computed_at"])."</span>";}
    $p=count($sub)>0?"<p>".implode(" &middot; ",$sub)."</p>":"";
    return "<div class='nafx-head'><div><h3>{features}</h3>$p</div>"
        ."<button type='button' class='ak-btn ak-btn-ghost' OnClick=\"NafeatReload($id,true)\"><i class='fas fa-arrows-rotate' aria-hidden='true'></i> {refresh}</button></div>";
}

function nafp_render($tpl,int $id,array $f):string{
    $page=CurrentPageName();
    $write=nafeat_can_write();
    $h=array();
    $h[]="<div class='nafx' id='nafx-$id'>";
    $h[]=netagent_stale_banner($f["cached_at"],$f["stale"]);
    if($f["stale"] && $f["error"]!==""){$h[]="<div class='ak-note'>".nafeat_esc($f["error"])."</div>";}
    $h[]=nafp_head($id,$f);
    $js="";
    if(count($f["features"])==0 && ($f["refreshing"] || $f["computed_at"]==0)){
        // Catalog never computed or being computed: never "no feature".
        $h[]="<div class='ak-card ak-empty-c' id='nafx-computing-$id'><i class='fas fa-spinner ak-spin' aria-hidden='true'></i><span>{svcx_computing}</span></div>";
        $js.="setTimeout(function(){if(document.getElementById('nafx-computing-$id')){NafeatReload($id,false);}},5000);";
    }elseif(count($f["features"])==0){
        $h[]="<div class='ak-card ak-empty-c'><i class='fas fa-box-open' aria-hidden='true'></i><span>{nafeat_none}</span></div>";
    }else{
        $m=nafeat_model($tpl,$f["features"]);
        if(!$write){$h[]="<div class='ak-note'><i class='fas fa-lock' aria-hidden='true'></i> {nafeat_read_only}</div>";}
        $h[]=nafeat_toolbar($m,(string)$id);
        $jsFor=function($it) use($write,$page,$id){
            if(!$write){return array("on"=>"","off"=>"");}
            $base="Loadjs('$page?action-js=$id&key={$it["key"]}&a=";
            return array("on"=>$base."on')","off"=>$base."off')");
        };
        $h[]=nafeat_sections($m,(string)$id,$jsFor);
        $js.="if(typeof NafeatRestore==='function'){NafeatRestore('$id');}";
    }
    $h[]="</div>";
    $js.="if(typeof NafeatJobFirst==='function'){NafeatJobFirst($id);}if(typeof NoSpinner==='function'){NoSpinner();}";
    return nafeat_style().$tpl->_ENGINE_parse_body(implode("\n",$h))."<script>$js</script>";
}

// Confirmation, then POST nafeat-action=<id>:<key>:<on|off> (echo nothing on success).
function nafp_action_js():bool{
    $tpl=new template_admin();
    $id=intval($_GET["action-js"]);
    $key=(string)($_GET["key"] ?? "");
    $a=((string)($_GET["a"] ?? ""))==="off"?"off":"on";
    if($id<=0 || !nafeat_key_ok($key)){return false;}
    if(!nafeat_can_write()){return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    // The title is read again from the node catalog, never taken from the request.
    $f=nafeat_fetch($id);
    $title="";
    if($f["kind"]==="artica"){
        foreach($f["features"] as $raw){
            if(is_array($raw) && (string)($raw["key"] ?? "")===$key){
                $it=nafeat_item($tpl,$raw);
                if($it!==null){$title=substr(nafeat_plain($it["title"]),0,128);}
                break;
            }
        }
    }
    if($title===""){return $tpl->js_error("{svcx_unknown_feature}");}
    // One slot per session (title of the job banner), never a growing map.
    $_SESSION["NAFEAT_LAST_TITLE"]=array("id"=>$id,"key"=>$key,"title"=>$title);
    $host=(new ArticaNetAgents($id))->GetAgentHostname();
    $txt=$tpl->_ENGINE_parse_body($a==="on"?"{nafeat_confirm_install}":"{nafeat_confirm_uninstall}");
    $txt=str_replace(array("%f","%h"),array("<strong>".nafeat_js_text($title)."</strong>","<strong>".nafeat_js_text($host)."</strong>"),$txt);
    return $tpl->js_confirm_execute($txt,"nafeat-action","$id:$key:$a","NafeatAfter($id);");
}

function nafp_post_action():bool{
    $tpl=new template_admin();
    if(!nafeat_can_write()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
    $parts=explode(":",(string)$_POST["nafeat-action"]);
    $id=intval($parts[0] ?? 0);
    $key=(string)($parts[1] ?? "");
    $a=(string)($parts[2] ?? "");
    if($id<=0 || !nafeat_key_ok($key) || ($a!=="on" && $a!=="off")){
        echo $tpl->_ENGINE_parse_body("{svcx_unknown_feature}");return false;
    }
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/features/$id/action",array("key"=>$key,"action"=>$a));
    $r=nafp_action_result((string)$raw);
    if(!$r["ok"]){echo $tpl->_ENGINE_parse_body($r["error"]);return false;}
    $_SESSION["NAFEAT_JOB_$id"]=array("job"=>$r["job"],"already"=>$r["already"],"key"=>$key,"at"=>time());
    $host=(new ArticaNetAgents($id))->GetAgentHostname();
    admin_tracks("Meta: feature $key ".($a==="on"?"install":"uninstall")." requested on $host");
    return true;
}

// Answer of POST /netagents/features/{id}/action. error = HTML template (tokens + escaped text),
// always longer than 3 characters (js_confirm_execute treats a short answer as a success).
function nafp_action_result(string $raw):array{
    $out=array("ok"=>false,"job"=>"","already"=>false,"error"=>"");
    $r=json_decode($raw,true);
    if(!is_array($r)){
        $err=trim(strip_tags($raw));
        $out["error"]=($err==="" || stripos($err,"not found")!==false)?"{nafeat_meta_missing}":"{svcx_job_refused}<br>".nafeat_esc(substr($err,0,300));
        return $out;
    }
    if(!empty($r["error"]) && !isset($r["Status"])){$out["error"]="{svcx_job_refused}<br>".nafeat_esc(substr((string)$r["error"],0,300));return $out;}
    if(empty($r["Status"])){
        $e=(string)($r["Error"] ?? "");
        if(isset($r["managed_by_scenario"]) || stripos($e,"managed by an Artica Meta scenario")===0){
            $out["error"]=nafeat_scenario_refusal($r,$e);
            return $out;
        }
        if(isset($r["managed_by"]) || $e==="managed by an Artica Meta policy"){
            $name="";
            if(isset($r["managed_by"]) && is_array($r["managed_by"])){$name=trim((string)($r["managed_by"]["name"] ?? ""));}
            $out["error"]="{nafeat_managed_refused}".($name!==""?"<br>{nafeat_managed_by} <strong>".nafeat_esc(substr($name,0,255))."</strong>":"");
            return $out;
        }
        if($e==="this feature is installed from the node console"){$out["error"]="{nafeat_node_console}";return $out;}
        if(stripos($e,"unsupported:")===0){
            $out["error"]=stripos($e,"debian-agent")!==false?"{nafeat_update_agent}":"{nafeat_update_artica}";
            return $out;
        }
        if($e===""){$e="HTTP error";}
        $out["error"]="{svcx_job_refused}<br>".nafeat_esc(substr($e,0,300));
        return $out;
    }
    if(($r["result"] ?? "")==="already"){$out["ok"]=true;$out["already"]=true;return $out;}
    $job=(string)($r["job"] ?? "");
    if(!preg_match('/^[0-9a-f]{1,32}$/',$job)){$out["error"]="{svcx_job_none}";return $out;}
    $out["ok"]=true;$out["job"]=$job;
    return $out;
}

// Banner of the last job. Marker data-js: running | done | error | none (read by NafeatJob).
// A finished job is shown only when this page followed it (job id, or the one kept by the POST).
function nafp_job_status(){
    $tpl=new template_admin();
    $id=intval($_GET["job-status"]);
    $job=(string)($_GET["job"] ?? "");
    if(!preg_match('/^[0-9a-f]{1,32}$/',$job)){$job="";}
    $none="<span data-js='none' hidden></span>";
    if($id<=0){echo $none;return;}
    $sk="NAFEAT_JOB_$id";
    if($job==="" && isset($_SESSION[$sk]) && is_array($_SESSION[$sk])){
        $s=$_SESSION[$sk];
        if(!empty($s["already"])){
            unset($_SESSION[$sk]);
            echo nafp_job_banner($tpl,nafp_job_title($id,(string)$s["key"]),"ak-ok","fa-circle-check","{svcx_job_already}","","done","");
            return;
        }
        $job=(string)$s["job"];
    }
    $raw=(string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/features/$id/jobs");
    echo nafp_job_render($tpl,$id,$job,$raw);
}

function nafp_job_render($tpl,int $id,string $job,string $raw):string{
    $sk="NAFEAT_JOB_$id";
    $none="<span data-js='none' hidden></span>";
    $j=json_decode($raw,true);
    if(!is_array($j) || empty($j["Status"])){
        if($job===""){return $none;}
        $err=is_array($j)?(string)($j["Error"] ?? ($j["error"] ?? "")):trim(strip_tags($raw));
        return nafp_job_banner($tpl,"","ak-err","fa-triangle-exclamation","{svcx_catalog_unavailable}",nafeat_esc(substr($err,0,300)),"error",$job);
    }
    $r=isset($j["job"]) && is_array($j["job"])?$j["job"]:null;
    if($r===null){
        if($job===""){return $none;}
        unset($_SESSION[$sk]);
        return nafp_job_banner($tpl,"","ak-off","fa-circle-info","{svcx_job_none}","","done","");
    }
    $jid=(string)($r["id"] ?? "");
    if(!preg_match('/^[0-9a-f]{1,32}$/',$jid)){$jid="";}
    $key=(string)($r["key"] ?? "");
    $state=(string)($r["state"] ?? "");
    $title=nafp_job_title($id,$key);
    $msg=nafeat_esc(substr((string)($r["message"] ?? ""),0,500));
    if($state==="running"){
        $pc=max(0,min(100,intval($r["progress"] ?? 0)));
        $h=nafp_job_banner($tpl,$title,"ak-info","fa-spinner ak-spin","{svcx_job_running} ($pc%)",$msg,"running",$jid,$pc);
        return $h;
    }
    if($job===""){return $none;}
    if($jid!==$job){
        unset($_SESSION[$sk]);
        return nafp_job_banner($tpl,$title,"ak-warn","fa-circle-info","{svcx_job_replaced}","","done","");
    }
    unset($_SESSION[$sk]);
    $map=array("ok"=>array("ak-ok","fa-circle-check","{svcx_job_ok}"),
        "already"=>array("ak-ok","fa-circle-check","{svcx_job_already}"),
        "failed"=>array("ak-err","fa-triangle-exclamation","{svcx_job_failed}"),
        "refused"=>array("ak-err","fa-ban","{svcx_job_refused}"));
    $v=isset($map[$state])?$map[$state]:$map["failed"];
    if($state==="ok" && !empty($r["reboot_required"])){
        // Never an automatic restart: the administrator decides.
        $msg.=($msg!==""?"<br>":"")."<strong><i class='fas fa-power-off' aria-hidden='true'></i> {nafeat_reboot_required}</strong>";
    }
    return nafp_job_banner($tpl,$title,$v[0],$v[1],$v[2],$msg,"done",$jid);
}

function nafp_job_title(int $id,string $key):string{
    $l=$_SESSION["NAFEAT_LAST_TITLE"] ?? null;
    if(is_array($l) && intval($l["id"] ?? 0)===$id && (string)($l["key"] ?? "")===$key){
        return nafeat_js_text((string)($l["title"] ?? ""));
    }
    return nafeat_key_ok($key)?$key:"";
}

function nafp_job_banner($tpl,string $title,string $tone,string $ico,string $label,string $msg,string $js,string $job,int $pc=-1):string{
    $t=$title!==""?"<strong>$title</strong> &middot; ":"";
    $h="<div class='ak-banner $tone' style='margin:10px 0'><i class='fas $ico' aria-hidden='true'></i><span class='ak-banner-m'>$t$label";
    if($msg!==""){$h.="<br><span class='ak-note'>$msg</span>";}
    $h.="</span></div>";
    if($pc>=0){$h.="<div class='ak-bar' style='margin:-4px 0 10px'><span style='width:$pc%'></span></div>";}
    $h.="<span data-js='$js' data-job='$job' hidden></span>";
    return $tpl->_ENGINE_parse_body($h);
}

// == Node without Artica: the debian-agent cards ===============================================

function naflg_defs():array{
    return array(
        "dhcp"=>array("title"=>"{APP_DHCP}","desc"=>"{nafeat_dhcp_desc}","ico"=>"fa-network-wired","tile"=>"dhcp",
            "product"=>"APP_DHCP","manage"=>"fw.netagents.dhcp.php?dhcp-js=%d"),
        "unbound"=>array("title"=>"{APP_UNBOUND}","desc"=>"{nafeat_unbound_desc}","ico"=>"fa-database","tile"=>"dns",
            "product"=>"APP_UNBOUND","manage"=>"fw.netagents.unbound.php?unbound-js=%d"),
        "rds"=>array("title"=>"{APP_RDPPROXY}","desc"=>"{nafeat_rds_desc}","ico"=>"fa-desktop","tile"=>"rds",
            "product"=>"","manage"=>""),
    );
}

// Wrapper refreshed every 5 s (cached agent status only, no node call).
function naflg_render(int $id):bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div id='naflg-$id'>";
    naflg_cards_html($id);
    echo "</div><script>".$tpl->RefreshInterval_js("naflg-$id",$page,"legacy=$id",5)."</script>";
    return true;
}

function naflg_cards():bool{
    $id=intval($_GET["legacy"] ?? 0);
    $st=(new ArticaNetAgents($id))->Status();
    if(!empty($st["artica"])){$tpl=new template_admin();echo $tpl->_ENGINE_parse_body("{nafeat_legacy_artica}");return false;}
    return naflg_cards_html($id);
}

// Pending action of the session: shown as "Applying" until the reported state reaches the
// target, or for 10 minutes at most.
function naflg_pending_key(string $f,int $id):string{ return "NAFEAT_PENDING_{$f}_$id"; }

function naflg_pending(string $f,int $id,string $state):bool{
    $k=naflg_pending_key($f,$id);
    if(!isset($_SESSION[$k]) || !is_array($_SESSION[$k])){return false;}
    $p=$_SESSION[$k];
    $done=($p["target"]==="on" && $state==="active") || ($p["target"]==="off" && $state!=="active");
    if($done || time()-intval($p["at"])>600){unset($_SESSION[$k]);return false;}
    return true;
}

function naflg_model(int $id):array{
    $na=new ArticaNetAgents($id);
    $st=$na->Status();
    $m=array("id"=>$id,"hostname"=>(string)($st["hostname"] ?? ""),"debver"=>intval($st["debian_version"] ?? 0),
        "cached_at"=>(string)($st["cached_at"] ?? ""),"artica"=>!empty($st["artica"]),"reachable"=>count($st)>0,"features"=>array());

    $d=(isset($st["dhcp3"]) && is_array($st["dhcp3"]))?$st["dhcp3"]:array();
    $state=empty($d["exists"])?"absent":(empty($d["installed"])?"inactive":"active");
    $m["features"]["dhcp"]=array("state"=>$state,"version"=>trim((string)($d["version"] ?? "")),"note"=>"");

    $u=(isset($st["unbound"]) && is_array($st["unbound"]))?$st["unbound"]:array();
    $state=empty($u["installed"])?"absent":(empty($u["enabled"])?"inactive":"active");
    $m["features"]["unbound"]=array("state"=>$state,"version"=>"","note"=>"");

    // RDS Proxy is an Artica feature: on a node without Artica it is never available.
    $m["features"]["rds"]=array("state"=>"noartica","version"=>"","note"=>"");

    foreach($m["features"] as $f=>$x){
        if($x["state"]!=="noartica" && naflg_pending($f,$id,$x["state"])){$m["features"][$f]["pending"]=true;}
    }
    return $m;
}

function naflg_cards_html(int $id):bool{
    $tpl=new template_admin();
    $m=naflg_model($id);
    $h=array();
    $h[]="<div class='nafeat' id='nafeat-$id'>";
    $h[]=naflg_header($m);
    $h[]="<div class='nafeat-grid'>";
    foreach(naflg_defs() as $f=>$def){$h[]=naflg_card($m,$f,$def);}
    $h[]="</div>";
    $h[]="<p class='nafeat-foot'><i class='fas fa-circle-info' aria-hidden='true'></i><span>{nafeat_footnote}</span></p>";
    $h[]="</div>";
    echo naflg_style().$tpl->_ENGINE_parse_body(implode("\n",$h))."<script>if(typeof NoSpinner==='function'){NoSpinner();}</script>";
    return true;
}

function naflg_header(array $m):string{
    $page=CurrentPageName();
    $id=$m["id"];
    $when="";
    if($m["cached_at"]!==""){
        try{$d=new DateTime($m["cached_at"]);$d->setTimezone(new DateTimeZone(date_default_timezone_get()));$when=$d->format("Y-m-d H:i");}catch(Exception $e){$when="";}
    }
    $last=$when!==""?"<span class='nafeat-last'><i class='fas fa-clock' aria-hidden='true'></i>{nafeat_last_report} <span class='ak-mono'>$when</span></span>":"";
    return "<div class='nafeat-head'><div><h3>{features}</h3><p>{nafeat_subtitle}</p></div>"
        ."<div class='nafeat-head-r'>$last<button type='button' class='ak-btn ak-btn-ghost' OnClick=\"LoadAjaxSilent('naflg-$id','$page?legacy=$id')\"><i class='fas fa-arrows-rotate' aria-hidden='true'></i><span>{refresh}</span></button></div></div>";
}

function naflg_badge(string $state,bool $pending):string{
    if($pending){
        return "<span class='nafeat-badge nafeat-b-run' aria-live='polite'><i class='fas fa-spinner fa-spin' aria-hidden='true'></i>{nafeat_applying}</span>";
    }
    $m=array("active"=>array("fa-circle-check","nafeat-b-ok","{nafeat_active}"),
        "inactive"=>array("fa-circle-pause","nafeat-b-warn","{nafeat_inactive}"),
        "absent"=>array("fa-circle-minus","nafeat-b-off","{nafeat_absent}"),
        "noartica"=>array("fa-circle-minus","nafeat-b-off","{nafeat_noartica}"));
    $x=$m[$state] ?? $m["absent"];
    return "<span class='nafeat-badge {$x[1]}'><i class='fas {$x[0]}' aria-hidden='true'></i>{$x[2]}</span>";
}

function naflg_card(array $m,string $f,array $def):string{
    $x=$m["features"][$f];
    $pending=!empty($x["pending"]);
    $ver=$x["version"]!==""?"<span class='nafeat-ver ak-mono'>v".htmlspecialchars($x["version"])."</span>":"";
    $note=$x["note"]!==""?"<p class='nafeat-note'><i class='fas fa-sliders' aria-hidden='true'></i>{$x["note"]}</p>":"";
    return "<section class='nafeat-card nafeat-s-{$x["state"]}' aria-labelledby='nafeat-t-$f-{$m["id"]}'>"
        ."<div class='nafeat-top'><span class='nafeat-tile nafeat-tile-{$def["tile"]}'><i class='fas {$def["ico"]}' aria-hidden='true'></i></span>"
        ."<div class='nafeat-name'><h4 id='nafeat-t-$f-{$m["id"]}'>{$def["title"]}</h4>$ver</div>".naflg_badge($x["state"],$pending)."</div>"
        ."<p class='nafeat-desc'>{$def["desc"]}</p>$note"
        .naflg_actions($m,$f,$def,$x["state"],$pending)
        ."</section>";
}

function naflg_actions(array $m,string $f,array $def,string $state,bool $pending):string{
    $page=CurrentPageName();
    $id=$m["id"];
    $dis=$pending?" disabled":"";
    $write=nafeat_can_write();
    $install="";
    if($def["product"]!=="" && $write){
        $install="Loadjs('fw.netagents.free-install.php?js=$id&product={$def["product"]}&debver={$m["debver"]}');";
    }
    $b=array();
    if($state==="noartica"){
        return "<div class='nafeat-act'><span class='ak-muted'>{nafeat_noartica_explain}</span></div>";
    }
    if($state==="absent"){
        if($install===""){return "<div class='nafeat-act'></div>";}
        $b[]="<button type='button' class='ak-btn ak-btn-primary'$dis OnClick=\"$install\"><i class='fas fa-download' aria-hidden='true'></i><span>{nafeat_install_package}</span></button>";
        return "<div class='nafeat-act'>".implode("",$b)."</div>";
    }
    if($state==="inactive"){
        if($write){
            $b[]="<button type='button' class='ak-btn ak-btn-primary'$dis OnClick=\"Loadjs('$page?legacy-action-js=$id&f=$f&a=on')\"><i class='fas fa-power-off' aria-hidden='true'></i><span>{nafeat_activate}</span></button>";
        }
    }elseif($def["manage"]!==""){
        $js="Loadjs('".sprintf($def["manage"],$id)."');";
        $b[]="<button type='button' class='ak-btn ak-btn-primary'$dis OnClick=\"$js\"><i class='fas fa-sliders' aria-hidden='true'></i><span>{manage}</span></button>";
    }
    if($install!==""){
        $b[]="<button type='button' class='ak-btn'$dis OnClick=\"$install\"><i class='fas fa-arrow-up-from-bracket' aria-hidden='true'></i><span>{nafeat_update_package}</span></button>";
    }
    if($state==="active" && $write){
        $b[]="<button type='button' class='ak-btn ak-btn-danger nafeat-btn-danger'$dis OnClick=\"Loadjs('$page?legacy-action-js=$id&f=$f&a=off')\"><i class='fas fa-power-off' aria-hidden='true'></i><span>{nafeat_deactivate}</span></button>";
    }
    return "<div class='nafeat-act'>".implode("",$b)."</div>";
}

function naflg_action_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["legacy-action-js"]);
    $f=(string)($_GET["f"] ?? "");
    $a=((string)($_GET["a"] ?? ""))==="off"?"off":"on";
    $defs=naflg_defs();
    if(!isset($defs[$f]) || $f==="rds"){return false;}
    if(!nafeat_can_write()){return $tpl->js_error("{ERROR_NO_PRIVS2}");}
    // The text follows "{execute_this_task}: ... ?" of js_confirm_execute.
    $host=nafeat_js_text((new ArticaNetAgents($id))->GetAgentHostname());
    $txt=$tpl->_ENGINE_parse_body($a==="on"?"{nafeat_confirm_on}":"{nafeat_confirm_off}");
    $txt=str_replace(array("%f","%h"),array($tpl->_ENGINE_parse_body($defs[$f]["title"]),"<strong>$host</strong>"),$txt);
    $after="setTimeout(function(){ LoadAjaxSilent('naflg-$id','$page?legacy=$id'); }, 1500);";
    return $tpl->js_confirm_execute($txt,"naflg-action","$id:$f:$a",$after);
}

// Daemon answer -> error text ("" = success): outFalse ({Status,Error}), install results
// ({success,message}) and a curl failure ({error}).
function naflg_err($raw):string{
    $j=json_decode((string)$raw,true);
    if(!is_array($j)){return "Invalid response from the Meta service";}
    if(!empty($j["error"])){return (string)$j["error"];}
    if(array_key_exists("Status",$j) && !$j["Status"]){return (string)($j["Error"] ?? "Error");}
    if(array_key_exists("success",$j) && !$j["success"]){return (string)($j["message"] ?? "Error");}
    return "";
}

// Value posted by js_confirm_execute: "<agent id>:<dhcp|unbound>:<on|off>". Refused on an
// Artica node: its DHCP and DNS cache belong to Artica (never two dhcpd or two resolvers).
function naflg_post_action():bool{
    $tpl=new template_admin();
    if(!nafeat_can_write()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
    $parts=explode(":",(string)$_POST["naflg-action"]);
    $id=intval($parts[0] ?? 0);
    $f=(string)($parts[1] ?? "");
    $a=((string)($parts[2] ?? ""))==="off"?"off":"on";
    if($id<=0 || ($f!=="dhcp" && $f!=="unbound")){echo "Invalid request";return false;}
    $na=new ArticaNetAgents($id);
    $st=$na->Status();
    if(!empty($st["artica"])){echo $tpl->_ENGINE_parse_body("{nafeat_legacy_artica}");return false;}
    $sock=$GLOBALS["CLASS_SOCKETS"];
    if($f==="dhcp"){
        $err=naflg_err($a==="on"?$sock->REST_API_POST("/netagents/dhcp/$id/install",array()):$sock->REST_API("/netagents/dhcp/$id/uninstall"));
    }else{
        $err=naflg_err($a==="on"?$sock->REST_API_POST("/netagents/unbound/install/$id",array()):$sock->REST_API("/netagents/unbound/uninstall/$id"));
    }
    if($err!==""){echo htmlspecialchars(strlen($err)>3?$err:"Error: $err");return false;}
    $_SESSION[naflg_pending_key($f,$id)]=array("target"=>$a,"at"=>time());
    admin_tracks("Meta: ".($a==="on"?"activate":"deactivate")." the $f feature on ".$na->GetAgentHostname());
    return true;
}

function naflg_style():string{
    $accent="#10b981";$dark="#0d9488";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
    }
    return "<style>
.nafeat{--nf-a:$accent;--nf-ad:$dark;color:#111827;font-size:13px;padding:4px 2px}
.nafeat *{box-sizing:border-box}
.nafeat-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding-bottom:14px;border-bottom:1px solid #f1f5f9;margin-bottom:16px}
.nafeat-head h3{margin:0 0 4px;font-size:16px;font-weight:600}
.nafeat-head p{margin:0;color:#6b7280}
.nafeat-head-r{display:flex;align-items:center;gap:14px;flex-shrink:0}
.nafeat-last{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#6b7280;white-space:nowrap}
.nafeat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(420px,1fr));gap:16px}
.nafeat-card{display:flex;flex-direction:column;gap:10px;min-height:200px;padding:20px;border:1px solid #e5e7eb;border-radius:12px;background:#fff}
.nafeat-top{display:flex;align-items:flex-start;gap:12px}
.nafeat-tile{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:10px;font-size:18px;flex-shrink:0}
.nafeat-tile-dhcp{background:#e6eeed;color:#005447}.nafeat-tile-dns{background:#ecfeff;color:#0e7490}.nafeat-tile-rds{background:#f2f6f6;color:#005447}
.nafeat-name{display:flex;align-items:center;flex-wrap:wrap;gap:8px;flex:1;min-width:0;min-height:44px}
.nafeat-name h4{margin:0;font-size:16px;font-weight:600}
.nafeat-ver{padding:1px 7px;border:1px solid #d1d5db;border-radius:6px;font-size:12px;color:#374151;white-space:nowrap}
.nafeat-badge{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap;flex-shrink:0}
.nafeat-b-ok{background:#ecfdf5;color:#047857}.nafeat-b-warn{background:#fffbeb;color:#92400e}
.nafeat-b-off{background:#f3f4f6;color:#4b5563}.nafeat-b-run{background:#eff6ff;color:#1d4ed8}
.nafeat-desc{margin:0;color:#4b5563;line-height:1.5;flex:1}
.nafeat-note{display:flex;align-items:center;gap:6px;margin:0;font-size:12px;color:#1d4ed8}
.nafeat-act{display:flex;align-items:center;flex-wrap:wrap;gap:8px;padding-top:12px;border-top:1px solid #f1f5f9}
.nafeat-btn-danger{margin-left:auto}
.nafeat-foot{display:flex;align-items:center;gap:8px;margin:14px 0 0;font-size:12px;color:#6b7280}
@media (prefers-reduced-motion:reduce){.nafeat .fa-spin{animation:none}}
</style>";
}
