<?php
// Artica Meta - services systemd / init.d d'UN noeud (fenetre ouverte depuis la liste des agents).
// Maquette stitch_artica_meta_services (2026-11-16). Donnees : /netagents/services/{id}
// (liste en cache de l'agent : relue au demarrage, toutes les 6 heures et apres chaque action),
// POST /netagents/services/{id}/refresh (relecture immediate) et
// POST /netagents/services/{id}/{name}/start|stop|restart (l'agent lance l'action et repond
// « initiated » sans attendre son resultat). Routes reservees a l'infogerant par le demon.
// cpu_percent vaut toujours 0 cote agent : pas de colonne CPU.
// L'onglet Monitored (monit) est inchange.
//
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js) de l'onglet Monitored : definis dans
// class.statistics_appliance.inc. L'include DOIT rester INCONDITIONNEL (sinon fatal
// "Call to undefined function awc_table_style()").
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.netagent.artica.inc");
include_once(dirname(__FILE__)."/ressources/class.netagents.tenants.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}
// Meme porte que la liste des agents, sur TOUS les points d'entree (aucune avant : seules les
// icones d'action etaient masquees).
if(!function_exists("meta_can_view") || !meta_can_view()){
    $hit=count($_POST)>0?"post":((isset($_GET["refresh"]) || isset($_GET["monit-action"]) || count($_GET)<=1)?"services-js":"popup");
    natn_refuse("Refused: Artica Meta node services (no Artica Meta privilege)",$hit);
}
if(isset($_POST["nasv-act"])){nasv_act_post();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["start-point"])){start_point();exit;}
if(isset($_GET["nasv-list"])){nasv_list();exit;}
if(isset($_GET["monitored-point"])){monitored_point();exit;}
if(isset($_GET["monitored-search"])){monitored_search();exit;}
if(isset($_GET["refresh"])){do_refresh();exit;}
if(isset($_GET["monit-action"])){monit_action();exit;}
js();

function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["id"]);
    return $tpl->js_dialog11("<i class='fas fa-gears'></i> {nasv_title} &mdash; ".nasv_h(nasv_host($id)),"$page?tabs=$id",1024);
}

function tabs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["tabs"]);

    $monit_installed=false;
    $status=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/status/$id"));
    if(is_object($status) && !(isset($status->Status) && !$status->Status)){
        $monit_installed=(bool)($status->monit_installed??false);
    }
    // Services en premier : c'est l'onglet ouvert d'office (tabs_default charge le premier).
    $tabs=array();
    $tabs["{nasv_tab}"]="$page?start-point=$id";
    if($monit_installed){ $tabs["{monitored}"]="$page?monitored-point=$id";}
    $html=array();
    $html[]="<div style='margin-top:10px'>";
    if($monit_installed){ $html[]=$tpl->div_explain("{nasv_window_monit}"); }
    $html[]=$tpl->tabs_default($tabs);
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
}
function monitored_point():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=intval($_GET["monitored-point"]);
    $html[]="<div style='margin-top:8px;'>";
    $html[]= $tpl->search_block($page,"","","monitored-table-$id","&id=$id&monitored-search=yes");
    $html[]="<div id='monitored-table-$id'></div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// ---------------------------------------------------------------------------------------------
// Onglet Services

// Texte venu du noeud : echappe, accolades comprises (_ENGINE_parse_body les lirait comme jetons).
function nasv_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}
// Jeton traduit (cache), pour y substituer une valeur apres traduction.
function nasv_t($tok):string{
    static $c=array();
    if(!isset($c[$tok])){ $tpl=new template_admin(); $c[$tok]=$tpl->_ENGINE_parse_body($tok); }
    return $c[$tok];
}
function nasv_tn($tok,$v):string{ return str_replace("%s",(string)$v,nasv_t($tok)); }
// Jeton en texte brut (attribut title, chaine JS) : sans <br> ni entites.
function nasv_plain($tok):string{
    $s=str_replace(array("<br>","<br/>","<br />"),array(" "," "," "),nasv_t($tok));
    return trim(preg_replace('/\s+/',' ',html_entity_decode($s,ENT_QUOTES,"UTF-8")));
}
function nasv_attr($tok):string{ return nasv_h(nasv_plain($tok)); }

function nasv_host($id):string{
    $net=new ArticaNetAgents($id);
    $name=(string)$net->GetAgentHostname();
    return strlen($name)>0?$name:"#$id";
}
// Horodatage Go (RFC3339) ou systemd ("Mon 2026-11-16 08:12:33 CET") -> epoch ; 0 si vide.
function nasv_epoch($v):int{
    $v=trim((string)$v);
    if($v=="" || $v=="n/a" || strpos($v,"0001-01-01")===0){return 0;}
    $t=strtotime($v);
    if($t===false && preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/',$v,$re)){ $t=strtotime($re[1]); }
    return ($t===false || $t<=0)?0:intval($t);
}
// « il y a 3 h » avec les jetons deja traduits de la console (dkimg_ago_*).
function nasv_ago($t):string{
    $t=intval($t);
    if($t<=0){ return ""; }
    $d=time()-$t;
    if($d<60){ return nasv_t("{dkimg_ago_now}"); }
    if($d<3600){ return nasv_tn("{dkimg_ago_min}",intval($d/60)); }
    if($d<86400){ return nasv_tn("{dkimg_ago_h}",intval($d/3600)); }
    $days=intval($d/86400);
    if($days<14){ return $days==1?nasv_t("{dkimg_ago_d1}"):nasv_tn("{dkimg_ago_d}",$days); }
    if($days<60){ return nasv_tn("{dkimg_ago_w}",intval($days/7)); }
    if($days<365){ return nasv_tn("{dkimg_ago_mo}",intval($days/30)); }
    $y=intval($days/365);
    return $y==1?nasv_t("{dkimg_ago_y1}"):nasv_tn("{dkimg_ago_y}",$y);
}
function nasv_size($b):string{
    $b=floatval($b);
    if($b<=0){return "&mdash;";}
    if($b<1048576){ return max(1,round($b/1024))." KB"; }
    $mb=$b/1048576;
    if($mb<1024){ return ($mb<10?number_format($mb,1):number_format($mb,0))." MB"; }
    return number_format($mb/1024,1)." GB";
}

// Etat d'affichage d'un service. group = filtre (running | failed | stopped).
//   failed    : active=failed (le seul etat qu'aucun ancien onglet ne montrait)
//   running   : sous-etat running
//   changing  : activating / deactivating / reloading
//   completed : tache unique terminee (sous-etat exited) ; elle ne "tourne" pas
//   unknown   : script init.d sans statut lisible
//   stopped   : le reste
function nasv_state(array $s):array{
    $active=strtolower((string)(isset($s["active"])?$s["active"]:""));
    $status=strtolower((string)(isset($s["status"])?$s["status"]:""));
    if($active=="failed"){ return array("failed","failed"); }
    if(in_array($active,array("activating","deactivating","reloading"),true)){ return array("changing","stopped"); }
    if($status=="running"){ return array("running","running"); }
    if($status=="exited"){ return array("completed","stopped"); }
    if($status=="unknown"){ return array("unknown","stopped"); }
    return array("stopped","stopped");
}
function nasv_states():array{
    return array(
        "running"=>array("{nasv_running}","fas fa-circle-play"),
        "failed"=>array("{nasv_failed}","fas fa-circle-xmark"),
        "stopped"=>array("{nasv_stopped}","fas fa-circle-stop"),
        "completed"=>array("{nasv_completed}","fas fa-circle-check"),
        "changing"=>array("{nasv_changing}","fas fa-hourglass-half"),
        "unknown"=>array("{nasv_unknown}","fas fa-circle-question"),
    );
}

// Le modele, lu UNE fois.
function nasv_model($id):array{
    $m=array("id"=>$id,"error"=>"","host"=>nasv_host($id),"refreshed"=>0,"systemd"=>true,"rows"=>array(),
        "count"=>array("all"=>0,"running"=>0,"failed"=>0,"stopped"=>0));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/services/$id"),true);
    if(!is_array($json)){ $m["error"]=nasv_plain("{failed_to_contact_agent}"); return $m; }
    if(isset($json["Status"]) && !$json["Status"]){
        $m["error"]=trim((string)(isset($json["Error"])?$json["Error"]:""));
        if($m["error"]==""){ $m["error"]=nasv_plain("{failed_to_contact_agent}"); }
        return $m;
    }
    $m["refreshed"]=nasv_epoch(isset($json["last_refresh"])?$json["last_refresh"]:"");
    $m["systemd"]=!empty($json["has_systemd"]);
    $list=isset($json["services"]) && is_array($json["services"])?$json["services"]:array();
    $order=array("failed"=>0,"running"=>1,"stopped"=>2);
    foreach($list as $s){
        if(!is_array($s) || !isset($s["name"]) || trim((string)$s["name"])==""){continue;}
        list($state,$group)=nasv_state($s);
        $running=$state=="running";
        $m["rows"][]=array(
            "name"=>trim((string)$s["name"]),
            "desc"=>trim((string)(isset($s["description"])?$s["description"]:"")),
            "state"=>$state,"group"=>$group,
            "boot"=>strtolower(trim((string)(isset($s["enabled"])?$s["enabled"]:""))),
            "initd"=>(isset($s["type"]) && $s["type"]=="initd"),
            "since"=>$running?nasv_epoch(isset($s["started_at"])?$s["started_at"]:""):0,
            "pid"=>$running?intval(isset($s["main_pid"])?$s["main_pid"]:0):0,
            "mem"=>$running?floatval(isset($s["memory_bytes"])?$s["memory_bytes"]:0):0,
            "o"=>$order[$group],
        );
        $m["count"]["all"]++;
        $m["count"][$group]++;
    }
    usort($m["rows"],function($a,$b){
        if($a["o"]!=$b["o"]){ return $a["o"]-$b["o"]; }
        return strcasecmp($a["name"],$b["name"]);
    });
    return $m;
}

function nasv_strip(array $m):string{
    $c=$m["count"];
    $kpi=array();
    $kpi[]="<div class='nasv-kpi nasv-k-run'><i class='fas fa-circle-play' aria-hidden='true'></i><div><div class='nasv-kl'>{nasv_running}</div>"
        ."<div class='nasv-kv'>".($c["all"]>0?$c["running"]:"&mdash;")."<span class='nasv-kt'> / ".($c["all"]>0?$c["all"]:"&mdash;")."</span></div></div></div>";
    $kpi[]="<div class='nasv-kpi ".($c["failed"]>0?"nasv-k-fail":"nasv-k-zero")."'><i class='fas fa-circle-xmark' aria-hidden='true'></i><div><div class='nasv-kl'>{nasv_failed}</div>"
        ."<div class='nasv-kv'>".($c["failed"]>0?$c["failed"]:"&mdash;")."</div></div></div>";
    $kpi[]="<div class='nasv-kpi nasv-k-stop'><i class='fas fa-circle-stop' aria-hidden='true'></i><div><div class='nasv-kl'>{nasv_stopped}</div>"
        ."<div class='nasv-kv'>".($c["stopped"]>0?$c["stopped"]:"&mdash;")."</div></div></div>";
    $fresh="";
    if($m["refreshed"]>0){
        $stale=(time()-$m["refreshed"])>3600;
        $txt=nasv_tn("{nasv_collected}",nasv_ago($m["refreshed"])." (".date("Y-m-d H:i",$m["refreshed"]).")");
        $fresh="<span class='nasv-fresh".($stale?" nasv-fresh-old":"")."' title='".nasv_attr("{nasv_stale_tip}")."'>"
            ."<i class='fas ".($stale?"fa-clock-rotate-left":"fa-clock")."' aria-hidden='true'></i> $txt</span>";
    }
    $btn="<button type='button' class='nasv-rf' id='nasv-rf-{$m["id"]}' onclick=\"NasvRefresh({$m["id"]})\">"
        ."<i class='fas fa-arrows-rotate' aria-hidden='true'></i> {nasv_refresh}</button>";
    return "<div class='nasv-strip'>".implode("",$kpi)."<div class='nasv-strip-r'>$fresh$btn</div></div>"
        ."<div class='nasv-explain'><i class='fas fa-circle-info' aria-hidden='true'></i> {nasv_explain}</div>";
}

function nasv_filters(array $m):string{
    $c=$m["count"];
    $seg=array("all"=>"{nasv_all}","failed"=>"{nasv_failed}","running"=>"{nasv_running}","stopped"=>"{nasv_stopped}");
    $h=array();
    foreach($seg as $k=>$tok){
        $h[]="<button type='button' role='radio' aria-checked='".($k=="all"?"true":"false")."' data-f='$k'".($k=="all"?" class='on'":"")
            ." onclick=\"NasvFilter({$m["id"]},'$k')\"><span>$tok</span> <b class='nasv-n nasv-n-$k'>".$c[$k]."</b></button>";
    }
    return "<div class='nasv-bar'><div class='nasv-seg' role='radiogroup' aria-label='".nasv_attr("{nasv_col_state}")."'>".implode("",$h)."</div>"
        ."<label class='nasv-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' class='nasv-q' placeholder='".nasv_attr("{nasv_search}")."' aria-label='".nasv_attr("{nasv_search}")."'"
        ." oninput=\"NasvSearch({$m["id"]},this.value)\"></label></div>";
}

function nasv_boot($b):string{
    if($b=="enabled"){ return "<span class='nasv-boot nasv-boot-yes'><i class='fas fa-power-off' aria-hidden='true'></i> {nasv_boot_yes}</span>"; }
    if($b=="disabled"){ return "<span class='nasv-boot'>{nasv_boot_no}</span>"; }
    if($b=="static"){ return "<span class='nasv-boot'>{nasv_boot_static}</span>"; }
    if($b=="masked" || $b=="masked-runtime"){ return "<span class='nasv-boot nasv-boot-masked'><i class='fas fa-ban' aria-hidden='true'></i> {nasv_boot_masked}</span>"; }
    if($b==""){ return "<span class='nasv-mute'>&mdash;</span>"; }
    return "<span class='nasv-boot'>".nasv_h($b)."</span>";
}

function nasv_actions($id,array $r):string{
    $acts=array();
    if($r["state"]=="running"){ $acts=array("restart","stop"); }
    elseif($r["state"]=="unknown"){ $acts=array("start","stop"); }
    elseif($r["state"]!="changing"){ $acts=array("start"); }
    if($r["boot"]=="masked" || $r["boot"]=="masked-runtime"){ $acts=array_values(array_diff($acts,array("start","restart"))); }
    $def=array("start"=>array("fas fa-play","{nasv_start}"),"restart"=>array("fas fa-arrow-rotate-right","{nasv_restart}"),
        "stop"=>array("fas fa-stop","{nasv_stop}"));
    $js=nasv_h(json_encode($r["name"]));
    $h=array();
    foreach($acts as $a){
        $h[]="<button type='button' class='nasv-act nasv-act-$a' aria-label='".nasv_attr($def[$a][1])." ".nasv_h($r["name"])."'"
            ." onclick=\"NasvAct($id,$js,'$a')\"><i class='{$def[$a][0]}' aria-hidden='true'></i> {$def[$a][1]}</button>";
    }
    return implode("",$h);
}

function nasv_th($tok,$help=""):string{
    $q=$help!=""?" <i class='fas fa-circle-question nasv-help' title='".nasv_attr($help)."' aria-label='".nasv_attr($help)."'></i>":"";
    return "<th scope='col'>$tok$q</th>";
}

function nasv_table(array $m):string{
    $st=nasv_states();
    $h=array();
    $h[]="<div class='nasv-tbl-wrap'><table class='nasv-tbl'><thead><tr>";
    $h[]=nasv_th("{nasv_col_state}","{nasv_col_state_help}").nasv_th("{nasv_col_service}")
        .nasv_th("{nasv_col_boot}","{nasv_col_boot_help}").nasv_th("{nasv_col_since}","{nasv_col_since_help}")
        .nasv_th("{nasv_col_pid}").nasv_th("{nasv_col_mem}","{nasv_col_mem_help}").nasv_th("{nasv_col_actions}");
    $h[]="</tr></thead><tbody>";
    foreach($m["rows"] as $r){
        list($tok,$ico)=$st[$r["state"]];
        $main=$r["desc"]!=""?nasv_h($r["desc"]):nasv_h($r["name"]);
        $tag=$r["initd"]?" <span class='nasv-tag'>{nasv_initd}</span>":"";
        $since="<span class='nasv-mute'>&mdash;</span>";
        if($r["since"]>0){ $since="<span title='".date("Y-m-d H:i:s",$r["since"])."'>".nasv_ago($r["since"])."</span>"; }
        $q=nasv_h(function_exists("mb_strtolower")?mb_strtolower($r["name"]." ".$r["desc"],"UTF-8"):strtolower($r["name"]." ".$r["desc"]));
        $h[]="<tr data-s='1' data-g='{$r["group"]}' data-q='$q'>"
            ."<td><span class='nasv-pill nasv-p-{$r["state"]}'><i class='$ico' aria-hidden='true'></i> $tok</span></td>"
            ."<td class='nasv-svc'><div class='nasv-d'>$main$tag</div><div class='nasv-u'>".nasv_h($r["name"])."</div></td>"
            ."<td>".nasv_boot($r["boot"])."</td>"
            ."<td>$since</td>"
            ."<td class='nasv-num'>".($r["pid"]>0?$r["pid"]:"<span class='nasv-mute'>&mdash;</span>")."</td>"
            ."<td class='nasv-num'>".($r["mem"]>0?nasv_size($r["mem"]):"<span class='nasv-mute'>&mdash;</span>")."</td>"
            ."<td class='nasv-acts'>".nasv_actions($m["id"],$r)."</td></tr>";
    }
    $h[]="<tr class='nasv-nomatch' style='display:none'><td colspan='7'><i class='fas fa-filter-circle-xmark' aria-hidden='true'></i> {nasv_none_filter}</td></tr>";
    $h[]="</tbody></table></div>";
    return implode("",$h);
}

function nasv_state_box($ico,$cls,$text):string{
    return "<div class='nasv-box $cls'><i class='$ico' aria-hidden='true'></i><div>$text</div></div>";
}

// Contenu rechargeable de l'onglet (hote nasv-host-{id}).
function nasv_render($id):string{
    $m=nasv_model($id);
    $h=array();
    $h[]="<div class='nasv' id='nasv-$id'>";
    if($m["error"]!=""){
        $h[]=nasv_state_box("fas fa-plug-circle-xmark","nasv-box-err","{nasv_contact_err} ".nasv_h($m["error"]));
        $h[]="<div class='nasv-strip-r nasv-alone'><button type='button' class='nasv-rf' id='nasv-rf-$id' onclick=\"NasvRefresh($id)\">"
            ."<i class='fas fa-arrows-rotate' aria-hidden='true'></i> {nasv_refresh}</button></div>";
    }else{
        $h[]=nasv_strip($m);
        if(count($m["rows"])==0){
            $h[]=nasv_state_box("fas fa-hourglass-half","","{nasv_not_listed}");
        }else{
            $h[]=nasv_filters($m);
            $h[]=nasv_table($m);
        }
    }
    $h[]="</div>";
    $tpl=new template_admin();
    return $tpl->_ENGINE_parse_body(implode("",$h))."<script>if(window.NasvApply){NasvApply($id);}NoSpinner();</script>";
}

function nasv_style():string{
    return "<style>
.nasv{font-size:13px;color:#0f172a;line-height:1.45;margin-top:10px}
.nasv-strip{display:flex;align-items:center;gap:28px;padding:14px 18px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc}
.nasv-kpi{display:flex;align-items:center;gap:10px}
.nasv-kpi>i{font-size:22px}
.nasv-k-run>i{color:#16a34a}.nasv-k-fail>i,.nasv-k-fail .nasv-kv{color:#dc2626}.nasv-k-stop>i,.nasv-k-zero>i{color:#64748b}
.nasv-kl{font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#64748b}
.nasv-kv{font-size:20px;font-weight:600;line-height:1.1}
.nasv-kt{font-size:13px;font-weight:400;color:#64748b}
.nasv-strip-r{margin-left:auto;display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:flex-end}
.nasv-alone{margin:10px 0 0}
.nasv-fresh{color:#475569;white-space:nowrap;cursor:help}
.nasv-fresh-old{color:#b45309;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:3px 8px}
.nasv-rf{display:inline-flex;align-items:center;gap:6px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;border-radius:6px;padding:6px 12px;font-size:13px;font-weight:600;white-space:nowrap}
.nasv-rf:hover{background:#f1f5f9}.nasv-rf:disabled{opacity:.7;cursor:wait}
.nasv-rf:focus-visible,.nasv-seg button:focus-visible,.nasv-act:focus-visible,.nasv-q:focus-visible{outline:2px solid #2563eb;outline-offset:2px}
.nasv-explain{color:#475569;margin:10px 4px 14px}.nasv-explain i{color:#64748b;margin-right:4px}
.nasv-bar{display:flex;align-items:center;gap:12px;margin-bottom:10px}
.nasv-seg{display:inline-flex;background:#f1f5f9;border-radius:8px;padding:3px;gap:2px}
.nasv-seg button{border:0;background:transparent;color:#475569;font-size:13px;font-weight:600;padding:5px 12px;border-radius:6px;white-space:nowrap}
.nasv-seg button.on{background:#fff;color:#0f172a;box-shadow:0 1px 2px rgba(15,23,42,.08)}
.nasv-n{display:inline-block;min-width:20px;padding:0 6px;border-radius:999px;background:#e2e8f0;color:#334155;font-size:12px;text-align:center}
.nasv-n-failed{background:#fee2e2;color:#b91c1c}.nasv-n-running{background:#dcfce7;color:#166534}
.nasv-search{margin:0 0 0 auto;position:relative;width:280px;font-weight:400}
.nasv-search i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8}
.nasv-q{width:100%;height:34px;border:1px solid #cbd5e1;border-radius:6px;padding:0 10px 0 32px;font-size:13px;color:#0f172a;background:#fff}
.nasv-tbl-wrap{border:1px solid #e2e8f0;border-radius:10px;max-height:560px;overflow:auto}
.nasv-tbl{width:100%;border-collapse:separate;border-spacing:0}
.nasv-tbl th{position:sticky;top:0;z-index:1;background:#f8fafc;color:#475569;font-size:12px;font-weight:700;letter-spacing:.03em;text-transform:uppercase;text-align:left;padding:10px 12px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.nasv-tbl td{padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.nasv-tbl tbody tr:hover td{background:#f8fafc}
.nasv-help{color:#94a3b8;cursor:help;font-size:12px}
.nasv-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap}
.nasv-p-running{background:#dcfce7;color:#166534}.nasv-p-failed{background:#fef2f2;color:#b91c1c}
.nasv-p-stopped,.nasv-p-unknown{background:#f1f5f9;color:#475569}.nasv-p-completed{background:#eff6ff;color:#1d4ed8}
.nasv-p-changing{background:#fffbeb;color:#b45309}
.nasv-svc{min-width:0;overflow-wrap:anywhere}
.nasv-d{font-size:14px;font-weight:600;color:#0f172a}
.nasv-u{font-family:SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;color:#64748b;margin-top:1px}
.nasv-tag{display:inline-block;font-size:12px;font-weight:600;color:#1d4ed8;background:#eff6ff;border-radius:4px;padding:0 6px;margin-left:4px}
.nasv-boot{white-space:nowrap;color:#475569}.nasv-boot-yes{color:#166534;font-weight:600}.nasv-boot-masked{color:#b91c1c}
.nasv-num{white-space:nowrap;font-variant-numeric:tabular-nums}
.nasv-mute{color:#94a3b8}
.nasv-acts{white-space:nowrap;text-align:right}
.nasv-act{display:inline-flex;align-items:center;gap:5px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;border-radius:6px;padding:4px 10px;font-size:13px;font-weight:600;margin-left:6px}
.nasv-act:hover{background:#f1f5f9}
.nasv-act-start{border-color:#0f766e;background:#0f766e;color:#fff}.nasv-act-start:hover{background:#115e59}
.nasv-act-stop{border-color:#fecaca;color:#b91c1c}.nasv-act-stop:hover{background:#fef2f2}
.nasv-nomatch td{text-align:center;color:#64748b;padding:24px}
.nasv-box{display:flex;align-items:center;gap:12px;padding:22px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;color:#475569;margin-top:12px}
.nasv-box>i{font-size:26px;color:#94a3b8}
.nasv-box-err{background:#fef2f2;border-color:#fecaca;color:#991b1b;margin-top:0}.nasv-box-err>i{color:#dc2626}
.nasv-warn{margin-top:10px;background:#fffbeb;border-left:3px solid #d97706;border-radius:6px;padding:8px 10px;color:#78350f;text-align:left;font-size:13px}
@media (max-width:900px){.nasv-tbl th:nth-child(4),.nasv-tbl td:nth-child(4){display:none}.nasv-strip{flex-wrap:wrap}}
@media (max-width:780px){.nasv-tbl th:nth-child(3),.nasv-tbl td:nth-child(3){display:none}}
</style>";
}

// Fonctions JS de l'onglet, emises UNE fois par start_point() (le contenu rechargeable les appelle).
function nasv_js($id):string{
    $page=CurrentPageName();
    $T=array("host"=>nasv_host($id),"refreshing"=>nasv_plain("{nasv_refreshing}"),"cancel"=>nasv_plain("{nasv_cancel}"),
        "after"=>nasv_plain("{nasv_sent_after}"),"lose"=>nasv_plain("{nasv_confirm_lose}"));
    foreach(array("start","stop","restart") as $a){ $T["sent_$a"]=nasv_plain("{nasv_sent_$a}"); }
    foreach(array("stop","restart") as $a){
        $T["t_$a"]=nasv_plain("{nasv_confirm_{$a}_t}");
        $T["c_$a"]=nasv_plain("{nasv_confirm_$a}");
        $T["b_$a"]=nasv_plain("{nasv_btn_$a}");
    }
    $Tj=json_encode($T,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE);
    $Pj=json_encode($page);
    return "<script>
window.NasvT=window.NasvT||{};window.NasvT[$id]=$Tj;window.NasvPage=$Pj;window.NasvState=window.NasvState||{};
function NasvEsc(s){return String(s).replace(/[&<>\"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',\"'\":'&#39;'}[c];});}
function NasvSt(id){if(!NasvState[id]){NasvState[id]={f:'all',q:''};}return NasvState[id];}
function NasvApply(id){
  var st=NasvSt(id),root=document.getElementById('nasv-'+id);if(!root){return;}
  var segs=root.querySelectorAll('.nasv-seg button');
  for(var i=0;i<segs.length;i++){var on=segs[i].getAttribute('data-f')==st.f;segs[i].className=on?'on':'';segs[i].setAttribute('aria-checked',on?'true':'false');}
  var inp=root.querySelector('.nasv-q');if(inp&&inp.value!=st.q){inp.value=st.q;}
  var q=st.q.toLowerCase(),rows=root.querySelectorAll('tr[data-s]'),n=0;
  for(var j=0;j<rows.length;j++){var r=rows[j];var ok=(st.f=='all'||r.getAttribute('data-g')==st.f)&&(q.length<1||r.getAttribute('data-q').indexOf(q)>=0);r.style.display=ok?'':'none';if(ok){n++;}}
  var e=root.querySelector('.nasv-nomatch');if(e){e.style.display=(n==0&&rows.length>0)?'':'none';}
}
function NasvFilter(id,f){NasvSt(id).f=f;NasvApply(id);}
function NasvSearch(id,v){NasvSt(id).q=String(v||'').trim();NasvApply(id);}
function NasvReload(id){LoadAjaxSilent('nasv-host-'+id,NasvPage+'?nasv-list='+id);}
function NasvRefresh(id){var b=document.getElementById('nasv-rf-'+id);if(b){b.disabled=true;b.innerHTML='<i class=\"fas fa-arrows-rotate fa-spin\" aria-hidden=\"true\"></i> '+NasvEsc(NasvT[id].refreshing);}Loadjs(NasvPage+'?refresh='+id);}
function NasvSend(id,svc,act){
  var XHR=new XHRConnection();XHR.appendData('nasv-act',act);XHR.appendData('id',id);XHR.appendData('svc',svc);
  XHR.sendAndLoad(NasvPage,'POST',function(o){var r=String(o.responseText||'').replace(/^\\s+|\\s+\$/g,'');
    if(r.length>0){toastr.error(r);return;}
    toastr.success(NasvEsc(NasvT[id]['sent_'+act].replace('%s',svc))+'<br>'+NasvEsc(NasvT[id].after));
    setTimeout(function(){NasvReload(id);},5000);});
}
function NasvAct(id,svc,act){
  if(act=='start'){NasvSend(id,svc,act);return;}
  var T=NasvT[id],lose=(svc=='debian-agent'||svc=='ssh'||svc=='sshd');
  var txt=NasvEsc(T['c_'+act]).replace('%s','<b>'+NasvEsc(T.host)+'</b>');
  if(lose){txt+='<div class=\"nasv-warn\"><i class=\"fas fa-triangle-exclamation\" aria-hidden=\"true\"></i> '+NasvEsc(T.lose)+'</div>';}
  swal({title:T['t_'+act].replace('%s',svc),text:txt,html:true,type:'warning',showCancelButton:true,confirmButtonColor:'#dc2626',
    confirmButtonText:T['b_'+act],cancelButtonText:T.cancel,closeOnConfirm:true},function(ok){if(ok!==false){NasvSend(id,svc,act);}});
}
</script>";
}

function start_point():bool{
    $id=intval($_GET["start-point"]);
    // &active= des anciens liens : ignore (une seule liste, filtre cote navigateur).
    echo nasv_style().nasv_js($id)."<div id='nasv-host-$id'>".nasv_render($id)."</div>";
    return true;
}
function nasv_list():void{
    echo nasv_render(intval($_GET["nasv-list"]));
}

// Loadjs : relecture immediate de la liste par l'agent, puis rechargement de l'onglet.
function do_refresh():void{
    header("content-type: application/x-javascript");
    $id=intval($_GET["refresh"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/services/$id/refresh",array()),true);
    $err="";
    if(!is_array($json)){ $err=nasv_plain("{failed_to_contact_agent}"); }
    elseif(isset($json["Status"]) && !$json["Status"]){ $err=nasv_plain("{nasv_contact_err}")." ".(isset($json["Error"])?$json["Error"]:""); }
    elseif(isset($json["success"]) && !$json["success"]){ $err=(string)(isset($json["message"])?$json["message"]:nasv_plain("{failed_to_contact_agent}")); }
    if($err!=""){ echo "toastr.error(".json_encode(htmlspecialchars($err,ENT_QUOTES,"UTF-8"),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP).");\n"; }
    echo "NasvReload($id);";
}

// POST : start / stop / restart. Ne renvoie que l'erreur (texte), rien en cas d'envoi accepte.
function nasv_act_post():void{
    $id=intval($_POST["id"]??0);
    $act=(string)($_POST["nasv-act"]??"");
    $svc=trim((string)($_POST["svc"]??""));
    if($id<1 || !in_array($act,array("start","stop","restart"),true) || !preg_match('/^[A-Za-z0-9_@.:-]{1,200}$/',$svc)){
        echo nasv_h(nasv_plain("{nasv_bad_action}"));
        return;
    }
    if(function_exists("admin_tracks")){ admin_tracks("Artica Meta: $act service $svc on node #$id"); }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/netagents/services/$id/".rawurlencode($svc)."/$act",array()),true);
    if(!is_array($json)){ echo nasv_h(nasv_plain("{failed_to_contact_agent}")); return; }
    if(isset($json["Status"]) && !$json["Status"]){
        echo nasv_h(nasv_plain("{nasv_contact_err}")." ".(isset($json["Error"])?$json["Error"]:""));
        return;
    }
    if(isset($json["success"]) && !$json["success"]){
        $e=isset($json["error"])&&$json["error"]!=""?$json["error"]:(isset($json["message"])?$json["message"]:"");
        echo nasv_h($e!=""?$e:nasv_plain("{failed_to_contact_agent}"));
    }
}

// ---------------------------------------------------------------------------------------------
// Onglet Monitored (monit) : inchange.

// Loadjs: monit start/stop on a monitored service
function monit_action():void{
    header("content-type: application/x-javascript");
    $id=intval($_GET["id"]??0);
    $svc=trim($_GET["svc"]??"");
    $action=trim($_GET["monit-action"]??"");
    $page=CurrentPageName();
    if(!in_array($action,["start","stop"])||empty($svc)){
        echo "toastr.error('Invalid monit action');";
        return;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON(
        "/netagents/services/$id/monitored/$action",
        ["service"=>$svc]
    ));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        $err=is_object($json)?addslashes($json->Error??"Unknown error"):"Failed to contact agent";
        echo "toastr.error('$err');";
        return;
    }
    if(!$json->success){
        $err=addslashes($json->message??"Action failed");
        echo "toastr.error('$err');";
        return;
    }
    // Pentest 2026-11-04 : svc etait recopie sans encodage dans la page (XSS).
    $label=req_js(req_html($svc)); // toastr affiche du HTML
    echo "toastr.success('Monit ".ucfirst($action)." $label OK');";
    echo "LoadAjaxSilent('monitored-table-$id','$page?search=&id=$id&monitored-search=yes');";
}

function monitored_search():bool{
    $tpl=new template_admin();
    $id=intval($_GET["id"]);
    $search=trim($_GET["search"]??"");
    $page=CurrentPageName();

    // GET /netagents/services/{id}/monitored/status → MonitStatusResponse
    // success=false means monit not installed/reachable (not a transport error)
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/netagents/services/$id/monitored/status"));
    if(!is_object($json)||(isset($json->Status)&&!$json->Status)){
        $err=is_object($json)?htmlspecialchars($json->Error??"{unknown_error}"):"{failed_to_contact_agent}";
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        echo "<script>NoSpinner();</script>";
        return false;
    }
    if(!$json->success){
        echo $tpl->_ENGINE_parse_body($tpl->div_info(monit_unavailable_text((string)($json->message??""))));
        echo "<script>NoSpinner();</script>";
        return true;
    }

    // services is a map[string]MonitServiceInfo → stdClass in PHP
    $services=is_object($json->services??null)?get_object_vars($json->services):[];

    // Client-side filter by name or status_label
    if(strlen($search)>=2){
        $services=array_filter($services,function($svc) use($search){
            return stripos($svc->name??"",$search)!==false
                || stripos($svc->status_label??"",$search)!==false;
        });
    }

    $html=[];

    // Server info bar (version, hostname, uptime)
    if(isset($json->server)&&is_object($json->server)){
        $srv=$json->server;
        $ver=htmlspecialchars($srv->version??"");
        $hostname=htmlspecialchars($srv->hostname??"");
        $up=format_uptime(intval($srv->uptime??0));
        $html[]="<div style='padding:5px 8px;margin-bottom:6px;background:#f5f5f5;border-left:3px solid #1c84c6;font-size:11px'>";
        $html[]="<i class='fa fa-heartbeat'></i> <strong>monit</strong> $ver";
        if($hostname) $html[]="&nbsp;&mdash; <span style='font-family:monospace'>$hostname</span>";
        if($up!=="-") $html[]="&nbsp;<small style='color:#888'>{uptime}: $up</small>";
        $html[]="&nbsp;<span class='badge' style='background:#1c84c6;color:#fff'>".count($services)."</span>";
        $html[]="</div>";
    }

    if(empty($services)){
        $html[]=$tpl->div_info("{no_results}");
        $html[]="<script>NoSpinner();</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead><tr>";
    $html[]="<th nowrap>{status}</th>";
    $html[]="<th>{services}</th>";
    $html[]="<th nowrap>{pid}</th>";
    $html[]="<th nowrap>{memory}</th>";
    $html[]="<th nowrap>{uptime}</th>";
    $html[]="<th nowrap>{action}</th>";
    $html[]="</tr></thead>";
    $html[]="<tbody>";
    $wd1="style='width:1%' nowrap";
    $TRCLASS=null;

    foreach($services as $name=>$svc){
        $TRCLASS=null;

        $monitor=intval($svc->monitor??0);
        $status=intval($svc->status??0);
        $status_label=htmlspecialchars($svc->status_label??"unknown");
        $monitor_label=htmlspecialchars($svc->monitor_label??"");

        // Status badge: unmonitored→gray, ok(status=0)→primary, problem→danger
        if($monitor===0){
            $badge="<span class='label label-default'>{not_monitored}</span>";
        }elseif($status===0){
            $badge="<span class='label label-primary'>$status_label</span>";
        }else{
            $badge="<span class='label label-danger'>$status_label</span>";
        }

        // Name + monitor mode below
        $name_html=htmlspecialchars($name);
        if(strpos(" $name_html","APP_")>0){
            $name_html="{{$name_html}}";
        }

        $name_cell="<strong style='font-family:monospace'>$name_html</strong>";
        if($monitor_label) $name_cell.="<br><small style='color:#888'>$monitor_label</small>";

        // PID
        $pid=intval($svc->pid??0);
        $pid_cell=$pid>0?$pid:"-";
        $strong="<span class='text-muted'>";
        $strongoff="</span>";

        // Memory: mem_kb is already in kilobytes — FormatBytes() takes KB directly
        $mem_kb=intval($svc->mem_kb??0);
        if( ($mem_kb/1024)>500){
            $strong="<strong>";
            $strongoff="</strong>";
        }
        if( ($mem_kb/1024)>1200){
            $strong="<strong class='text-warning'>";
            $strongoff="</strong>";
        }
        if( ($mem_kb/1024)>2500){
            $strong="<strong class='text-danger'>";
            $strongoff="</strong>";
        }
        $mem_cell=$mem_kb>0?FormatBytes($mem_kb):"-";

        // Uptime in seconds
        $uptime_cell=format_uptime(intval($svc->uptime??0));

        // Actions: monitor=1 (monitored) → stop; otherwise → start
        $svc_enc=urlencode($name);
        $start_js="Loadjs('$page?monit-action=start&id=$id&svc=$svc_enc')";
        $stop_js="Loadjs('$page?monit-action=stop&id=$id&svc=$svc_enc')";
        $actions=$monitor===1?$tpl->icon_stop($stop_js,"AsMetaAdmin"):$tpl->icon_run($start_js,"AsMetaAdmin");

        $html[]="<tr class='$TRCLASS'>";
        $html[]="<td $wd1>$badge</td>";
        $html[]="<td>$strong$name_cell$strongoff</td>";
        $html[]="<td $wd1>$strong$pid_cell$strongoff</td>";
        $html[]="<td $wd1>$strong$mem_cell$strongoff</td>";
        $html[]="<td $wd1>$strong$uptime_cell$strongoff</td>";
        $html[]="<td $wd1>$strong$actions$strongoff</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody></table></div></div></div>";
    $html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo awc_table_style().$tpl->_ENGINE_parse_body($html);
    return true;
}

// Onglet Monitored sans monit lisible. Un agent < 1.2.17 ne lit monit que par son port HTTP
// ("set httpd port N") ; Artica configure monit sur une socket locale ("set httpd unixsocket
// /run/monit/monit.sock") et cet agent repond "monit HTTP interface not configured in
// /etc/monit/monitrc". Depuis 1.2.17 l'agent lit la socket. Message clair (mettre l'agent a
// jour) a la place du texte brut ; le detail de l'agent reste affiche en dessous, en petit.
function monit_unavailable_text(string $agentMsg):string{
    $detail=$agentMsg!==""?"<div style='margin-top:6px;font-size:12px;color:#6b7280'>".htmlspecialchars($agentMsg)."</div>":"";
    if(stripos($agentMsg,"HTTP interface not configured")!==false){
        return "{nagt_monit_unavailable}||{nagt_monit_agent_too_old}$detail";
    }
    if(stripos($agentMsg,"monit not installed")!==false){
        return "{nagt_monit_unavailable}||{nagt_monit_not_installed}$detail";
    }
    if($agentMsg===""){return "{nagt_monit_unavailable}||{nagt_monit_not_installed}";}
    return "{nagt_monit_unavailable}||".htmlspecialchars($agentMsg);
}

function format_uptime(int $seconds):string{
    if($seconds<=0) return "-";
    if($seconds<60) return "{$seconds}s";
    if($seconds<3600) return intval($seconds/60)."m";
    $h=intval($seconds/3600);
    $m=intval(($seconds%3600)/60);
    return $m>0?"{$h}h {$m}m":"{$h}h";
}
