<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["PrivoxyPort"])){Save();exit;}
if(isset($_GET["status"])){service_status();exit;}
if(isset($_GET["ufdbconf-popup"])){ufdbconf_popup();exit;}
if(isset($_GET["ufdbdebug-popup"])){ufdbdebug_popup();exit;}
if(isset($_GET["ufdbdebug"])){ufdbdebug_js();exit;}
if(isset($_GET["3proxytop"])){top_status();exit;}
if(isset($_GET["3proxycenter"])){center_status();exit;}
if(isset($_GET["uninstall"])){uninstall_ask();exit;}
if(isset($_POST["uninstall"])){uninstall_confirm();exit;}

page();


function uninstall_ask():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Uninstall3Proxy=$tpl->framework_buildjs(
        "/3proxy/uninstall",
        "3proxy.progress",
        "3proxy.progress.log",
        "progress-3proxy-restart",
        "document.location.href='/index'");

    echo $tpl->js_confirm_execute("{disable_feature}",
        "uninstall","{disable_feature}",$Uninstall3Proxy);
    return true;
}
function uninstall_confirm():bool{
    return admin_tracks("Uninstall Universal Proxy service..");
}

function ufdbdebug_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	return $tpl->js_dialog1("{debug_mode}", "$page?ufdbdebug-popup=yes");

}
function ufdbconf_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{file_configuration}", "$page?ufdbconf-popup=yes");
	
}
function center_status():bool{
    $tpl=new template_admin();
    $range=upx_range($_GET["range"] ?? "24h");

    // L'alerte passe en premier : sur une appliance neuve il n'y a encore aucune
    // statistique, et c'est justement la que l'operateur doit apprendre que le
    // bundle iptables manque.
    echo iptables_addons_warning();

    $m=upx_model($range);
    $h=array();
    $h[]="<div class='upx' id='upx-root'>";
    $h[]=upx_toolbar($m);
    if(!$m["ok"]){
        $h[]=upx_unavailable($m);
    }elseif($m["first"]==0){
        $h[]=upx_empty();
    }else{
        $h[]=upx_summary($m);
        $h[]="<div class='upx-charts'>";
        $h[]=upx_chart_requests();
        $h[]=upx_chart_traffic();
        $h[]="</div>";
        $h[]=upx_services($m);
    }
    $h[]="</div>";
    echo upx_style().$tpl->_ENGINE_parse_body(@implode("\n",$h)).upx_js($m);
    return true;
}

// ---------------------------------------------------------------------------
// Statistiques de trafic : GET /3proxy/metrics (base bbolt d'articarest, 15 jours)
// ---------------------------------------------------------------------------

function upx_ranges():array{
    return array("1h"=>"1h","6h"=>"6h","24h"=>"24h","7d"=>"7d","15d"=>"15d");
}
function upx_range($r):string{
    $r=(string)$r;
    $ranges=upx_ranges();
    if(!isset($ranges[$r])){return "24h";}
    return $r;
}
function upx_bytes($n):string{
    $n=floatval($n);
    if($n<=0){return "0 B";}
    if($n<1024){return intval($n)." B";}
    return FormatBytes($n/1024,true);
}

/** Modele unique : tous les widgets lisent ce tableau. */
function upx_model(string $range):array{
    $m=array("ok"=>false,"error"=>"","range"=>$range,"step"=>0,"first"=>0,"retention"=>15,
        "labels"=>array(),"req"=>array(),"err"=>array(),"sent"=>array(),"recv"=>array(),
        "requests"=>0,"errors"=>0,"sent_total"=>0,"recv_total"=>0,"peak"=>0,"peak_time"=>0,"services"=>array());
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/3proxy/metrics?range=$range");
    $json=json_decode((string)$raw);
    // Daemon d'avant la route : reponse non-JSON. On ne dessine pas un trafic nul a la place.
    if(!is_object($json) || !property_exists($json,"Status")){
        $m["error"]="{upx_unavailable}";
        return $m;
    }
    if(!$json->Status || !isset($json->Data) || !is_object($json->Data)){
        $m["error"]=htmlspecialchars((string)($json->Error ?? ""));
        return $m;
    }
    $d=$json->Data;
    $m["ok"]=true;
    $m["step"]=intval($d->step ?? 0);
    $m["first"]=intval($d->first_sample ?? 0);
    $m["retention"]=intval($d->retention_days ?? 15);
    $m["requests"]=intval($d->requests ?? 0);
    $m["errors"]=intval($d->errors ?? 0);
    $m["sent_total"]=floatval($d->sent ?? 0);
    $m["recv_total"]=floatval($d->received ?? 0);
    $m["peak"]=intval($d->peak_requests ?? 0);
    $m["peak_time"]=intval($d->peak_time ?? 0);

    $fmt="H:i";
    if($range=="24h"){$fmt="D H:i";}
    if($range=="7d" || $range=="15d"){$fmt="M d H:i";}
    $points=(isset($d->points) && is_array($d->points))?$d->points:array();
    foreach($points as $p){
        $m["labels"][]=date($fmt,intval($p->t));
        // Intervalle sans mesure (avant le premier echantillon) : un trou, pas un zero.
        if(empty($p->have)){
            $m["req"][]=null; $m["err"][]=null; $m["sent"][]=null; $m["recv"][]=null;
            continue;
        }
        $m["req"][]=intval($p->requests);
        $m["err"][]=intval($p->errors);
        $m["sent"][]=floatval($p->sent);
        $m["recv"][]=floatval($p->received);
    }
    $services=(isset($d->services) && is_array($d->services))?$d->services:array();
    foreach($services as $s){
        $m["services"][]=array(
            "type"=>(string)($s->type ?? ""),"port"=>intval($s->port ?? 0),
            "requests"=>intval($s->requests ?? 0),"errors"=>intval($s->errors ?? 0),
            "sent"=>floatval($s->sent ?? 0),"received"=>floatval($s->received ?? 0));
    }
    return $m;
}

function upx_toolbar(array $m):string{
    $btn=array();
    foreach(upx_ranges() as $key=>$label){
        $on=($key==$m["range"]);
        $cls=$on?"upx-seg upx-seg-on":"upx-seg";
        $btn[]="<button type='button' class='$cls' aria-pressed='".($on?"true":"false")."' OnClick=\"UpxSetRange('$key')\">$label</button>";
    }
    $note="";
    if($m["ok"]){
        $tpl=new template_admin();
        $txt=str_replace("%s",intval($m["retention"]),$tpl->_ENGINE_parse_body("{upx_retention}"));
        $note="<span class='upx-note'><i class='fa-solid fa-clock-rotate-left' aria-hidden='true'></i> $txt</span>";
    }
    return "<div class='upx-bar'><div class='upx-bar-t'><i class='fa-solid fa-chart-line' aria-hidden='true'></i> {upx_history}</div>"
        ."<div class='upx-bar-r'>$note<div class='upx-segs' role='group' aria-label='{upx_period}'>".@implode("",$btn)."</div></div></div>";
}

function upx_unavailable(array $m):string{
    $err=$m["error"];
    if($err==""){$err="{upx_unavailable}";}
    return "<div class='upx-msg upx-msg-warn'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i><div>$err</div></div>";
}

function upx_empty():string{
    return "<div class='upx-msg'><i class='fa-solid fa-chart-column' aria-hidden='true'></i><div>{upx_empty}</div></div>";
}

function upx_stat(string $label,string $value,string $sub,string $cls):string{
    return "<div class='upx-stat $cls'><div class='upx-stat-l'>$label</div><div class='upx-stat-v'>$value</div><div class='upx-stat-s'>$sub</div></div>";
}

function upx_summary(array $m):string{
    $tpl=new template_admin();
    $req=$tpl->FormatNumber($m["requests"]);
    $peak="&nbsp;";
    if($m["peak"]>0){
        $when=date("H:i",$m["peak_time"]);
        if($m["range"]=="24h"){$when=date("D H:i",$m["peak_time"]);}
        if($m["range"]=="7d" || $m["range"]=="15d"){$when=date("M d H:i",$m["peak_time"]);}
        $peak=str_replace(array("%s","%t"),array($tpl->FormatNumber($m["peak"]),$when),$tpl->_ENGINE_parse_body("{upx_peak}"));
    }
    $rate="&nbsp;";
    $errCls="upx-ok";
    if($m["requests"]>0){
        $pct=round($m["errors"]*100/$m["requests"],1);
        $rate=str_replace("%s","$pct %",$tpl->_ENGINE_parse_body("{upx_error_rate}"));
        if($m["errors"]>0){$errCls="upx-warn";}
    }
    $h=array();
    $h[]="<div class='upx-stats'>";
    $h[]=upx_stat("{requests}",$req,$peak,"");
    $h[]=upx_stat("{errors}",$tpl->FormatNumber($m["errors"]),$rate,$errCls);
    $h[]=upx_stat("{sent_traffic}",upx_bytes($m["sent_total"]),"&nbsp;","");
    $h[]=upx_stat("{received_traffic}",upx_bytes($m["recv_total"]),"&nbsp;","");
    $h[]="</div>";
    return @implode("",$h);
}

function upx_chart_requests():string{
    return "<div class='upx-card'><div class='upx-card-t'><i class='fa-solid fa-arrow-right-arrow-left' aria-hidden='true'></i> {upx_requests_chart}</div>"
        ."<div class='upx-canvas'><canvas id='upx-chart-req' role='img' aria-label='{upx_requests_chart}'></canvas></div></div>";
}

function upx_chart_traffic():string{
    return "<div class='upx-card'><div class='upx-card-t'><i class='fa-solid fa-right-left' aria-hidden='true'></i> {traffic}</div>"
        ."<div class='upx-canvas'><canvas id='upx-chart-bw' role='img' aria-label='{traffic}'></canvas></div></div>";
}

function upx_services(array $m):string{
    $tpl=new template_admin();
    $h=array();
    $h[]="<div class='upx-card'><div class='upx-card-t'><i class='fa-solid fa-server' aria-hidden='true'></i> {upx_by_service}</div>";
    if(count($m["services"])==0){
        // Historique importe de l'ancienne base RRD : elle ne connaissait que les totaux.
        $h[]="<div class='upx-msg upx-msg-in'><i class='fa-solid fa-circle-info' aria-hidden='true'></i><div>{upx_no_service}</div></div></div>";
        return @implode("",$h);
    }
    $max=1;
    foreach($m["services"] as $s){ if($s["requests"]>$max){$max=$s["requests"];} }
    $h[]="<table class='upx-tbl'><thead><tr><th>{upx_service}</th><th>{upx_port}</th><th class='upx-r'>{requests}</th>"
        ."<th class='upx-r'>{errors}</th><th class='upx-r'>{sent_traffic}</th><th class='upx-r'>{received_traffic}</th></tr></thead><tbody>";
    foreach($m["services"] as $s){
        $w=round($s["requests"]*100/$max);
        $type=htmlspecialchars($s["type"]);
        $errCls=$s["errors"]>0?"upx-r upx-warn":"upx-r";
        $h[]="<tr><td><span class='upx-type'>$type</span></td><td class='upx-mono'>{$s["port"]}</td>"
            ."<td class='upx-r'><div class='upx-share'><span style='width:$w%'></span></div>".$tpl->FormatNumber($s["requests"])."</td>"
            ."<td class='$errCls'>".$tpl->FormatNumber($s["errors"])."</td>"
            ."<td class='upx-r'>".upx_bytes($s["sent"])."</td><td class='upx-r'>".upx_bytes($s["received"])."</td></tr>";
    }
    $h[]="</tbody></table></div>";
    return @implode("",$h);
}

function upx_accent():string{
    $accent="#10b981";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
    }
    // 6 chiffres exactement : le JS y colle un suffixe d'opacite ("b3", "26").
    if(preg_match('/^#([0-9a-fA-F])([0-9a-fA-F])([0-9a-fA-F])$/',$accent,$r)){$accent="#$r[1]$r[1]$r[2]$r[2]$r[3]$r[3]";}
    if(preg_match('/^(#[0-9a-fA-F]{6})[0-9a-fA-F]{2}$/',$accent,$r)){$accent=$r[1];}
    if(!preg_match('/^#[0-9a-fA-F]{6}$/',$accent)){$accent="#10b981";}
    return $accent;
}

/** Libelle traduit pour une chaine JS : entites decodees (les traductions stockent les accents en &#233;). */
function upx_jstr($tpl,string $token):string{
    $t=html_entity_decode(strip_tags($tpl->_ENGINE_parse_body($token)),ENT_QUOTES,"UTF-8");
    return json_encode($t,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
}

function upx_js(array $m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $range=$m["range"];
    $f=array();
    $f[]="<script>";
    $f[]="(function(){";
    $f[]="if(window.UpxCharts){for(var i=0;i<window.UpxCharts.length;i++){try{window.UpxCharts[i].destroy();}catch(e){}}}";
    $f[]="window.UpxCharts=[];";
    $f[]="window.UpxRange='$range';";
    $f[]="window.UpxSetRange=function(r){window.UpxRange=r;LoadAjaxSilent('3proxycenter','$page?3proxycenter=yes&range='+encodeURIComponent(r));};";
    // Une seule minuterie : rafraichit les graphiques chaque minute, pas pendant une fenetre ouverte.
    $f[]="if(window.UpxTimer){clearInterval(window.UpxTimer);}";
    $f[]="window.UpxTimer=setInterval(function(){";
    $f[]="  if(!document.getElementById('upx-root')){clearInterval(window.UpxTimer);window.UpxTimer=null;return;}";
    $f[]="  if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.swal2-container')){return;}";
    $f[]="  LoadAjaxSilent('3proxycenter','$page?3proxycenter=yes&range='+encodeURIComponent(window.UpxRange));";
    $f[]="},60000);";
    if($m["ok"] && $m["first"]>0 && count($m["labels"])>0){
        $accent=upx_accent();
        $L=upx_jstr($tpl,"{requests}");
        $E=upx_jstr($tpl,"{errors}");
        $S=upx_jstr($tpl,"{sent_traffic}");
        $R=upx_jstr($tpl,"{received_traffic}");
        $f[]="var labels=".json_encode($m["labels"]).";";
        $f[]="var req=".json_encode($m["req"]).",err=".json_encode($m["err"]).",snt=".json_encode($m["sent"]).",rcv=".json_encode($m["recv"]).";";
        $f[]="function upxB(v){v=Number(v)||0;var u=['B','KB','MB','GB','TB'],i=0;while(v>=1024&&i<u.length-1){v/=1024;i++;}return (i==0?v:v.toFixed(v<10?2:1))+' '+u[i];}";
        $f[]="function upxN(v){return (Number(v)||0).toLocaleString();}";
        $f[]="if(typeof Chart==='undefined'){return;}";
        $f[]="var red=matchMedia('(prefers-reduced-motion: reduce)').matches;";
        $f[]="var base={responsive:true,maintainAspectRatio:false,animation:red?false:{duration:400},interaction:{mode:'index',intersect:false},spanGaps:false,";
        $f[]="  plugins:{legend:{position:'bottom',labels:{boxWidth:12,font:{size:12}}}},scales:{x:{ticks:{maxTicksLimit:10,font:{size:11}},grid:{display:false}}}};";
        $f[]="function opts(fmt){var o=JSON.parse(JSON.stringify(base));o.animation=base.animation;o.scales.y={beginAtZero:true,ticks:{font:{size:11},callback:function(v){return fmt(v);}}};";
        $f[]="  o.plugins.tooltip={callbacks:{label:function(c){return ' '+c.dataset.label+': '+(c.raw===null?'-':fmt(c.raw));}}};return o;}";
        $f[]="var c1=document.getElementById('upx-chart-req');";
        $f[]="if(c1){window.UpxCharts.push(new Chart(c1,{type:'bar',data:{labels:labels,datasets:[";
        $f[]="  {type:'bar',label:$L,data:req,backgroundColor:'{$accent}b3',borderColor:'$accent',borderWidth:0,borderRadius:2,order:2},";
        $f[]="  {type:'line',label:$E,data:err,borderColor:'#dc2626',backgroundColor:'#dc2626',pointRadius:0,borderWidth:2,tension:0.25,order:1}";
        $f[]="]},options:opts(upxN)}));}";
        $f[]="var c2=document.getElementById('upx-chart-bw');";
        $f[]="if(c2){window.UpxCharts.push(new Chart(c2,{type:'line',data:{labels:labels,datasets:[";
        $f[]="  {label:$R,data:rcv,borderColor:'$accent',backgroundColor:'{$accent}26',fill:true,pointRadius:0,borderWidth:2,tension:0.25},";
        $f[]="  {label:$S,data:snt,borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.10)',fill:true,pointRadius:0,borderWidth:2,tension:0.25,borderDash:[6,3]}";
        $f[]="]},options:opts(upxB)}));}";
    }
    $f[]="})();";
    $f[]="</script>";
    return @implode("\n",$f);
}

function upx_style():string{
    if(isset($GLOBALS["UPX_STYLE_DONE"])){return "";}
    $GLOBALS["UPX_STYLE_DONE"]=true;
    $accent=upx_accent();
    return "<style>
.upx{margin:12px 0 0 5px;color:#1e293b;font-size:13px}
.upx-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px}
.upx-bar-t{font-size:16px;font-weight:600}
.upx-bar-t svg,.upx-bar-t i{color:$accent;margin-right:6px}
.upx-bar-r{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.upx-note{color:#475569;font-size:13px}
.upx-segs{display:inline-flex;border:1px solid #cbd5e1;border-radius:8px;overflow:hidden;background:#fff}
.upx-seg{border:0;background:transparent;padding:6px 14px;font-size:13px;font-weight:600;color:#334155;cursor:pointer;border-right:1px solid #e2e8f0}
.upx-seg:last-child{border-right:0}
.upx-seg:hover{background:#f1f5f9}
.upx-seg:focus-visible{outline:2px solid $accent;outline-offset:-2px}
.upx-seg-on{background:$accent;color:#fff}
.upx-seg-on:hover{background:$accent}
.upx-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:12px}
.upx-stat{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;box-sizing:border-box}
.upx-stat-l{font-size:13px;color:#475569;font-weight:600}
.upx-stat-v{font-size:24px;font-weight:700;margin-top:2px;white-space:nowrap}
.upx-stat-s{font-size:13px;color:#64748b;margin-top:2px}
.upx-stat.upx-warn .upx-stat-v{color:#b45309}
.upx-charts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:12px}
.upx-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;box-sizing:border-box;min-width:0;margin-bottom:12px}
.upx-charts .upx-card{margin-bottom:0}
.upx-card-t{font-size:14px;font-weight:600;margin-bottom:8px}
.upx-card-t svg,.upx-card-t i{color:#64748b;margin-right:6px}
.upx-canvas{position:relative;height:260px}
.upx-msg{display:flex;gap:12px;align-items:flex-start;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;font-size:13px;color:#334155}
.upx-msg svg,.upx-msg i{font-size:20px;color:#64748b;flex:0 0 auto}
.upx-msg-warn{background:#fffbeb;border-color:#fcd34d}
.upx-msg-warn svg,.upx-msg-warn i{color:#b45309}
.upx-msg-in{border:0;background:transparent;padding:4px 0}
.upx-tbl{width:100%;border-collapse:collapse;font-size:13px}
.upx-tbl th{text-align:left;color:#475569;font-weight:600;padding:6px 8px;border-bottom:1px solid #e2e8f0}
.upx-tbl td{padding:7px 8px;border-bottom:1px solid #f1f5f9;color:#1e293b}
.upx-tbl .upx-r{text-align:right;white-space:nowrap}
.upx-tbl td.upx-warn{color:#b45309;font-weight:600}
.upx-type{display:inline-block;padding:1px 8px;border-radius:999px;background:#f1f5f9;font-weight:600;font-size:12px;letter-spacing:.02em}
.upx-mono{font-family:monospace}
.upx-share{display:inline-block;width:70px;height:6px;background:#f1f5f9;border-radius:3px;margin-right:8px;vertical-align:middle;overflow:hidden}
.upx-share span{display:block;height:100%;background:$accent}
@media (max-width:1200px){.upx-charts{grid-template-columns:minmax(0,1fr)}.upx-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>";
}

// Signale que les "iptables add-ons" d'Artica ne sont pas installes.
//
// Ces add-ons ne sont pas un paquet Debian mais un tarball Artica, donc ni
// "dpkg -l" ni la liste des mises a jour ne le montrent : sans eux
// /usr/bin/redsocks2 est absent et le redirecteur TCP transparent echoue au
// demarrage avec un message que rien ne relie a une installation manquante
// (incident support-uPS, 4.50). L'attribut "redsock" de l'API porte la reponse.
function iptables_addons_warning():string{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/iptables/addons"));

    // Daemon plus ancien que la route (reponse non-JSON "Not Found") ou champ
    // absent : on n'affiche rien plutot qu'une alerte inventee.
    if(!is_object($json)){return "";}
    if(!property_exists($json,"redsock")){return "";}
    if($json->redsock){return "";}

    $missing="";
    if(property_exists($json,"missing") && is_array($json->missing) && count($json->missing)>0){
        $missing="<br><br>{missing_components}: <span style='font-family:monospace'>".
            htmlspecialchars(@implode(", ",$json->missing))."</span>";
    }

    return $tpl->div_warning("{iptables_addons_not_installed}||{iptables_addons_not_installed_explain}$missing");
}
function top_status():bool{

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/3proxy/stats"));
    $tpl=new template_admin();

    if(!$json->Status){
        return false;
    }

    $html[]="<table style='width:100%;margin-left:5px;margin-top: -8px;'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>";
    if($json->Sent>0) {
        $Sent = FormatBytes($json->Sent / 1024);
        $html[] = $tpl->widget_h("green", ico_upload, $Sent, "{sent_traffic}");
    }else{
        $html[]= $tpl->widget_h("grey", ico_upload,
            "{none}","{sent_traffic}");
    }
    $html[]="</td>";
    $html[]="<td style='width:33%;padding-left: 5px'>";
    if($json->Received>0) {
        $Received = FormatBytes($json->Received / 1024);
        $html[]= $tpl->widget_h("green", ico_download,
            $Received,"{received_traffic}");
    }else{
        $html[]= $tpl->widget_h("grey", ico_download,
            "{none}","{received_traffic}");
    }

    $html[]="</td>";
    $html[]="<td style='width:33%;padding-left: 5px'>";
    if($json->Requests>0) {
        $Requests = $tpl->FormatNumber($json->Requests);
        $html[] = $tpl->widget_h("green", ico_clouds, $Requests, "{requests}");
    }else{
        $html[]= $tpl->widget_h("grey", ico_download,"{none}","{requests}");
    }
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function ufdbconf_popup(){
	$tpl=new template_admin();
	$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
    $t=array();
	foreach ($f as $line){
		if(strlen($line)>86){
			$t[]=substr($line,0,86)."...";
			continue;
		}
		
		$t[]=$line;
	}
	
	$form=$tpl->field_textareacode("xxx", null, @implode("\n", $t));
	echo $tpl->form_outside(null, $form);
}



function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_3PROXY_VERSION");

    $html=$tpl->page_header("{APP_3PROXY} v$PrivoxyVersion &raquo;&raquo; {service_status}","fas fa-globe"
    ,"{APP_3PROXY_EXPLAIN}","$page?table=yes","universal-proxy-status","progress-3proxy-restart",false,"table-3proxy-status");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_3PROXY}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function service_status():bool{
    $tpl=new template_admin();
    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/3proxy/status");
    $page=CurrentPageName();
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error().
            "<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);

    $jsrestart3proxy=$tpl->framework_buildjs(
        "/3proxy/restart",
        "3proxy.progress",
        "3proxy.progress.log",
        "progress-3proxy-restart");

    echo $tpl->SERVICE_STATUS($ini, "APP_3PROXY",$jsrestart3proxy);

    $data =  $GLOBALS["CLASS_SOCKETS"]->REST_API("/redsocks/status");

    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error().
            "<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return true;
    }

    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);

    $jsrestart3proxy=$tpl->framework_buildjs(
        "/redsocks/restart",
        "redsocks.progress",
        "redsocks.progress.log",
        "progress-3proxy-restart");

    echo $tpl->SERVICE_STATUS($ini, "APP_REDSOCKS",$jsrestart3proxy);

    $InstallRedSocks=$tpl->framework_buildjs(
        "/redsocks/install",
        "redsocks.install.progress",
        "redsocks.install.progress.log",
        "progress-3proxy-restart");


    $Reload3Proxy=$tpl->framework_buildjs(
        "/3proxy/reload",
        "3proxy.progress",
        "3proxy.progress.log",
        "progress-3proxy-restart");

    $UninstallRedSocks=$tpl->framework_buildjs(
        "/redsocks/uninstall",
        "redsocks.install.progress",
        "redsocks.install.progress.log",
        "progress-3proxy-restart");

    $Uninstall3Proxy="Loadjs('$page?uninstall=yes');";



    $EnableRedSocks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedSocks"));
    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_3PROXY_VERSION");
    if($EnableRedSocks==0) {
        $topbuttons[] = array($InstallRedSocks, ico_cd, "{APP_REDSOCKS} {install}");
    }else{
        $topbuttons[] = array($UninstallRedSocks, ico_trash, "{APP_REDSOCKS} {uninstall}");
    }
    $topbuttons[] = array($Reload3Proxy, ico_retweet, "{reload}");
    $topbuttons[] = array($Uninstall3Proxy, ico_trash, "{uninstall}");


    $TINY_ARRAY["TITLE"]="{APP_3PROXY} v$PrivoxyVersion &raquo;&raquo; {service_status}";
    $TINY_ARRAY["ICO"]="fas fa-globe";
    $TINY_ARRAY["EXPL"]="{APP_3PROXY_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $headsjs="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $page=CurrentPageName();
    echo "\n\n\n<script>$headsjs;
    LoadAjaxSilent('3proxytop','$page?3proxytop=yes');
    </script>";
    return true;
}

function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px;vertical-align: top'>";
	$html[]="<div id='3proxystatus'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top'>";
    $html[]="<div id='3proxytop'></div>";
    $html[]="<div id='3proxycenter'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $js=$tpl->RefreshInterval_js("3proxystatus",$page,"status=yes",5);
    // Les graphiques ont leur propre minuterie (60 s) : la boucle de statut de 5 s
    // les recreait a chaque passage.
    $html[]="<script>$js\nLoadAjaxSilent('3proxycenter','$page?3proxycenter=yes&range=24h');</script>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}

function Save(){
	$sock=new sockets();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	$_POST["zipproxy_MaxSize"]=$_POST["zipproxy_MaxSize"]*1024;
	
	foreach ($_POST as $key=>$value){
		$sock->SET_INFO($key, $value);
	}
	
}

function Main(){
    $tpl=new template_admin();
    $page=CurrentPageName();

}
