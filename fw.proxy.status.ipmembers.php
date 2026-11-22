<?php
/**
 * Proxy members history: distinct clients, distinct websites and active
 * connections over time. Design: stitch_conntrackd_operational_users (2026-11-12).
 *
 * Rendered in two places: the {members} tab of fw.proxy.status.php and a
 * js_dialog6 modal (990px) opened from the proxy dashboard's Members tile.
 * Hence NO TINY: from a tab it would overwrite the Status header, from a
 * modal the header of the page behind it.
 *
 * Data (articarest, squid/ActiveRequests/UsersHistory, bbolt):
 *   /squid/users-history/chart?hours=N  <=24h raw samples (one per snapshot, ~90s),
 *                                       <=168h hourly avg + max, else daily avg + max
 *   /squid/users-history/raw?hours=2    latest raw sample (the "now" figures)
 *   /squid/users-history/stats          status, entries per bucket, DB size
 * Every value is a COUNT sampled at an instant, never a cumulative total.
 * METRICS_MAX_PROXY_CLIENTS_NUMBER is not used: two daemons write it with
 * different meanings; the period peak is read from the series instead.
 */
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();
if(!$users->AsProxyMonitor && !$users->AsSquidAdministrator){exit();}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["dash"])){ ipm_dash(); exit; }
ipm_page();

function js():bool{
    $tpl  = new template_admin();
    $page = CurrentPageName();
    return $tpl->js_dialog6("{ipm_members}",$page,990);
}

// ── Shell: host div + per-instance refresh timer ─────────────────────────────

// Each render gets its own host id: the page can be open in the proxy status
// tab AND in the modal of the dashboard tile at the same time, and two hosts
// sharing one id would load each other's fragment.
function ipm_page(){
    $page=CurrentPageName();
    $id="ipm-".substr(md5(uniqid("",true)),0,10);
    $h=array();
    $h[]="<div id='$id' class='ak ipm'></div>";
    $h[]="<script>";
    $h[]="window.IpmS=window.IpmS||{};";
    $h[]="window.IpmS['$id']={hours:24,timer:null};";
    $h[]="window.IpmLoad=function(id){";
    $h[]="  var s=window.IpmS[id]; if(!s){return;}";
    $h[]="  if(s.timer){clearTimeout(s.timer);s.timer=null;}";
    $h[]="  var host=document.getElementById(id); if(!host){delete window.IpmS[id];return;}";
    $h[]="  host.setAttribute('aria-busy','true');";
    // The SVG charts are drawn at the host's real width so their text stays at 12px.
    $h[]="  LoadAjaxSilent(id,'$page?dash=yes&host='+id+'&hours='+encodeURIComponent(s.hours)+'&w='+Math.round(host.clientWidth||0));";
    $h[]="};";
    $h[]="window.IpmRange=function(id,h){if(window.IpmS[id]){window.IpmS[id].hours=h;}window.IpmLoad(id);};";
    // Samples arrive every minute or two; a 5-minute refresh is plenty for a history view.
    $h[]="window.IpmArm=function(id){";
    $h[]="  var s=window.IpmS[id]; if(!s){return;}";
    $h[]="  if(s.timer){clearTimeout(s.timer);}";
    $h[]="  s.timer=setTimeout(function(){";
    $h[]="    s.timer=null;";
    $h[]="    if(!document.getElementById(id)){delete window.IpmS[id];return;}";
    $h[]="    var busy=false;";
    // The page itself may live in a modal: only another dialog on top of it blocks.
    $h[]="    if(typeof jQuery!=='undefined'){";
    $h[]="      var own=jQuery('#'+id).closest('.modal');";
    $h[]="      busy=jQuery('.modal.in, .modal.show').filter(function(){return !own.length||this!==own[0];}).length>0";
    $h[]="        || jQuery('.sweet-alert.visible').length>0;";
    $h[]="    }";
    $h[]="    if(busy){window.IpmArm(id);return;}";
    $h[]="    window.IpmLoad(id);";
    $h[]="  },300000);";
    $h[]="};";
    $h[]="window.IpmLoad('$id');";
    $h[]="</script>";
    echo implode("\n",$h);
}

// ── Data ─────────────────────────────────────────────────────────────────────

function ipm_api($path){
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($path));
    if(!is_object($json) || !property_exists($json,'success') || !$json->success) return null;
    return property_exists($json,'data')?$json->data:null;
}

function ipm_ranges(){
    return array(1=>"{last_hour}",24=>"{ipm_24h}",48=>"{ipm_48h}",168=>"{ipm_7d}",720=>"{ipm_30d}",2160=>"{ipm_90d}");
}

// Width of the host div as measured by the browser (tab or 990px modal).
function ipm_width(){
    $w=intval($_GET["w"] ?? 0);
    if($w<600){$w=1240;}
    if($w>2400){$w=2400;}
    return $w;
}

// Host id sent back by the shell; it goes into JavaScript, hence the whitelist.
function ipm_host(){
    $h=(string)($_GET["host"] ?? "");
    if(!preg_match('/^ipm-[a-f0-9]{10}$/',$h)){$h="";}
    return $h;
}

function ipm_hours(){
    $h=intval($_GET["hours"] ?? 24);
    $r=ipm_ranges();
    if(!isset($r[$h])){$h=24;}
    return $h;
}

// One read of every route; the widgets only format what this returns.
function ipm_model($hours){
    $m=array("hours"=>$hours,"now"=>time(),"agg"=>($hours>24),"grouped"=>false,"width"=>ipm_width(),"host"=>ipm_host());

    $st=ipm_api("/squid/users-history/stats");
    $m["db_ok"]=is_object($st) && (string)($st->status ?? "")=="running";
    $m["db_known"]=is_object($st);
    $m["db_entries"]=null; $m["db_size"]="";
    if(is_object($st)){
        $n=0; $seen=false;
        foreach(array("raw_entries","hourly_entries","daily_entries") as $k){
            if(property_exists($st,$k)){$n+=intval($st->$k);$seen=true;}
        }
        if($seen){$m["db_entries"]=$n;}
        $m["db_size"]=trim((string)($st->db_size_human ?? ""));
    }

    $m["labels"]=array();
    $m["series"]=array("users"=>array(),"websites"=>array(),"conns"=>array());
    $m["peaks"]=array("users"=>array(),"websites"=>array(),"conns"=>array());
    $c=ipm_api("/squid/users-history/chart?hours=$hours");
    if(is_object($c)){
        $m["labels"]=array_values(array_map("strval",(array)($c->labels ?? array())));
        $map=array("users"=>"max_users","websites"=>"max_websites","conns"=>"max_conns");
        foreach($map as $k=>$mk){
            $m["series"][$k]=array_values(array_map("floatval",(array)($c->$k ?? array())));
            if(isset($c->$mk) && is_array($c->$mk)){
                $m["peaks"][$k]=array_values(array_map("floatval",$c->$mk));
            }
        }
    }

    ipm_model_group($m);

    // "Now" = the latest raw sample, whatever the period shown below.
    $m["last"]=null;
    $raw=ipm_api("/squid/users-history/raw?hours=2");
    if(is_array($raw) && count($raw)>0){
        $s=$raw[count($raw)-1];
        if(is_object($s)){
            $m["last"]=array("ts"=>intval($s->timestamp ?? 0),"users"=>intval($s->users ?? 0),
                "websites"=>intval($s->websites ?? 0),"conns"=>intval($s->conns ?? 0));
        }
    }
    return $m;
}

// The raw bucket keeps one sample per ActiveRequests snapshot (about one a
// minute and a half, measured 2026-11-12: 912 points for 24h), not one every
// 10 minutes as the Go comment says. Past 240 points the series is cut into
// groups: average and peak of each group, so a 24h view stays light and still
// shows the spikes.
function ipm_model_group(&$m){
    $n=count($m["labels"]);
    $max=240;
    if($n<=$max){return;}
    $size=intval(ceil($n/$max));
    $labels=array(); $series=array(); $peaks=array();
    foreach($m["series"] as $k=>$vals){ $series[$k]=array(); $peaks[$k]=array(); }
    for($i=0;$i<$n;$i+=$size){
        $labels[]=$m["labels"][$i];
        foreach($m["series"] as $k=>$vals){
            $chunk=array_slice($vals,$i,$size);
            $pchunk=count($m["peaks"][$k])==$n?array_slice($m["peaks"][$k],$i,$size):$chunk;
            $series[$k][]=count($chunk)>0?array_sum($chunk)/count($chunk):0;
            $peaks[$k][]=count($pchunk)>0?max($pchunk):0;
        }
    }
    $m["labels"]=$labels; $m["series"]=$series; $m["peaks"]=$peaks;
    $m["grouped"]=true; $m["agg"]=true;
}

// Highest value of a metric over the period and the label where it happened.
// Aggregated periods carry a per-bucket maximum: that is the real peak.
function ipm_peak($m,$k){
    $vals=count($m["peaks"][$k])>0?$m["peaks"][$k]:$m["series"][$k];
    if(count($vals)==0){return array(null,"");}
    $best=0; $idx=-1;
    foreach($vals as $i=>$v){ if($idx<0 || $v>$best){$best=$v;$idx=$i;} }
    return array($best,$m["labels"][$idx] ?? "");
}

function ipm_n($v){
    if($v===null){return "—";}
    return number_format($v);
}

function ipm_short($v){
    $v=floatval($v);
    if($v>=1000000){return rtrim(rtrim(sprintf("%.1f",$v/1000000),"0"),".")."M";}
    if($v>=10000){return rtrim(rtrim(sprintf("%.1f",$v/1000),"0"),".")."k";}
    return number_format($v);
}

function ipm_nice($v){
    if($v<=0){return 1;}
    $e=floor(log10($v)); $p=pow(10,$e); $f=$v/$p;
    if($f<=1){$n=1;}elseif($f<=2){$n=2;}elseif($f<=2.5){$n=2.5;}elseif($f<=5){$n=5;}else{$n=10;}
    $r=$n*$p;
    return $r<4?4:$r;     // counts are integers: at least one unit per grid line
}

function ipm_colors(){
    $a=array("accent"=>"#0d9488","soft"=>"rgba(13,148,136,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        if(!empty($b["accent_dark"])){$a["accent"]=$b["accent_dark"];}
        if(!empty($b["accent_soft"])){$a["soft"]=$b["accent_soft"];}
    }
    return array(
        "users"=>array($a["accent"],$a["soft"]),
        "websites"=>array("#2563eb","rgba(37,99,235,.12)"),
        "conns"=>array("#64748b","rgba(100,116,139,.14)"),
    );
}

// ── SVG charts (no JS state: they re-render with the fragment) ──────────────

function ipm_svg_chart($labels,$avg,$peak,$color,$soft,$opt){
    $n=count($labels);
    if($n==0 || count($avg)!=$n){return "";}
    $hasBand=(count($peak)==$n);
    $W=intval($opt["w"]); $H=intval($opt["h"]);
    $pl=48; $pr=14; $pt=14; $pb=28;
    $ymax=0;
    foreach($avg as $v){ if($v>$ymax){$ymax=$v;} }
    if($hasBand){ foreach($peak as $v){ if($v>$ymax){$ymax=$v;} } }
    $ymax=ipm_nice($ymax*1.08);
    $iw=$W-$pl-$pr; $ih=$H-$pt-$pb;
    $xs=array();
    for($i=0;$i<$n;$i++){ $xs[$i]=($n<=1)?$pl+$iw/2:$pl+$i*$iw/($n-1); }
    $Y=function($v) use($pt,$ih,$ymax){ return round($pt+$ih-($v/$ymax)*$ih,1); };

    $o=array();
    $o[]="<svg class='ipm-svg' viewBox='0 0 $W $H' role='img' aria-label=\"".htmlspecialchars($opt["aria"])."\">";
    for($g=0;$g<=4;$g++){
        $v=$ymax*$g/4; $y=$Y($v);
        $o[]="<line x1='$pl' x2='".($W-$pr)."' y1='$y' y2='$y' class='ipm-grid'/>";
        $o[]="<text x='".($pl-8)."' y='".($y+4)."' text-anchor='end' class='ipm-tick'>".ipm_short($v)."</text>";
    }
    $step=max(1,intval(ceil($n/intval($opt["xt"] ?? 8))));
    for($i=0;$i<$n;$i+=$step){
        $anchor=($i==0 && $n>1)?"start":"middle";
        $o[]="<text x='".round($xs[$i],1)."' y='".($H-8)."' text-anchor='$anchor' class='ipm-tick'>".htmlspecialchars($labels[$i])."</text>";
    }
    if($hasBand){
        $pts=array();
        for($i=0;$i<$n;$i++){ $pts[]=round($xs[$i],1).",".$Y($peak[$i]); }
        for($i=$n-1;$i>=0;$i--){ $pts[]=round($xs[$i],1).",".$Y($avg[$i]); }
        $o[]="<polygon points='".implode(" ",$pts)."' fill='$soft' stroke='none'/>";
    }else{
        // Raw samples: light area under the line, as on the mock-up.
        $pts=array(round($xs[0],1).",".$Y(0));
        for($i=0;$i<$n;$i++){ $pts[]=round($xs[$i],1).",".$Y($avg[$i]); }
        $pts[]=round($xs[$n-1],1).",".$Y(0);
        $o[]="<polygon points='".implode(" ",$pts)."' fill='$soft' stroke='none'/>";
    }
    $d="";
    for($i=0;$i<$n;$i++){ $d.=($i==0?"M":"L").round($xs[$i],1).",".$Y($avg[$i])." "; }
    $o[]="<path d='".trim($d)."' fill='none' stroke='$color' stroke-width='2' stroke-linejoin='round' stroke-linecap='round'/>";
    if($n==1){ $o[]="<circle cx='".round($xs[0],1)."' cy='".$Y($avg[0])."' r='3' fill='$color'/>"; }
    $cw=($n<=1)?$iw:$iw/($n-1);
    for($i=0;$i<$n;$i++){
        $t=$labels[$i]."\n".$opt["name"].": ".ipm_n(round($avg[$i]));
        if($hasBand){ $t.="\n".$opt["peak_name"].": ".ipm_n(round($peak[$i])); }
        $x=round(max($pl,$xs[$i]-$cw/2),1); $w=round(min($cw,$W-$pr-$x),1);
        $o[]="<rect x='$x' y='$pt' width='$w' height='$ih' class='ipm-hv'><title>".htmlspecialchars($t)."</title></rect>";
    }
    $o[]="</svg>";
    return implode("",$o);
}

function ipm_svg_spark($data,$color,$soft){
    $n=count($data);
    if($n<2){return "";}
    $W=120; $H=32;
    $mn=min($data); $mx=max($data); $span=($mx-$mn)>0?($mx-$mn):1;
    $pts=array();
    foreach($data as $i=>$v){ $pts[]=round($i*$W/($n-1),1).",".round(3+($H-6)*(1-($v-$mn)/$span),1); }
    return "<svg class='ipm-spark' viewBox='0 0 $W $H' preserveAspectRatio='none' aria-hidden='true'>"
        ."<polygon points='0,$H ".implode(" ",$pts)." $W,$H' fill='$soft'/>"
        ."<polyline points='".implode(" ",$pts)."' fill='none' stroke='$color' stroke-width='2' vector-effect='non-scaling-stroke'/></svg>";
}

// ── Widgets ─────────────────────────────────────────────────────────────────

function ipm_toolbar($m){
    $h=array();
    $h[]="<div class='ipm-tools'>";
    $h[]="<div class='ak-seg' role='radiogroup' aria-label='{ipm_period}'>";
    foreach(ipm_ranges() as $k=>$l){
        $on=($k==$m["hours"]);
        $h[]="<button type='button' role='radio' aria-checked='".($on?"true":"false")."' class='ak-seg-b".($on?" on":"")."' onclick=\"IpmRange('{$m["host"]}',$k)\">$l</button>";
    }
    $h[]="</div>";
    if($m["hours"]<=24){$res=$m["grouped"]?"{ipm_res_grouped}":"{ipm_res_samples}";}elseif($m["hours"]<=168){$res="{ipm_res_hourly}";}else{$res="{ipm_res_daily}";}
    $h[]="<div class='ipm-tools-r'><span class='ak-muted'>$res</span>";
    $h[]="<span class='ak-muted'>{ipm_updated} <span class='ak-mono'>".date("H:i:s",$m["now"])."</span></span>";
    $h[]="<button type='button' class='ak-btn ak-btn-icon' aria-label='{refresh}' title='{refresh}' onclick=\"IpmLoad('{$m["host"]}')\"><i class='fa-solid fa-arrows-rotate'></i></button></div>";
    $h[]="</div>";
    return implode("\n",$h);
}

function ipm_zone($ico,$label,$value,$ctx,$spark){
    return "<div class='ipm-zone'><div class='ipm-zone-l'><i class='$ico' aria-hidden='true'></i> $label</div>"
        ."<div class='ipm-zone-b'><div class='ipm-zone-txt'><div class='ipm-zone-v'>$value</div><div class='ipm-zone-c'>$ctx</div></div>$spark</div></div>";
}

function ipm_zone_clients($m,$c){
    $l=$m["last"];
    $ctx=($l===null)?"{ipm_no_recent_sample}":"{ipm_latest_sample} ".date("H:i",$l["ts"]);
    return ipm_zone("fa-solid fa-users","{ipm_clients_now}",ipm_n($l===null?null:$l["users"]),$ctx,
        ipm_svg_spark($m["series"]["users"],$c["users"][0],$c["users"][1]));
}

function ipm_zone_peak($m,$c){
    list($v,$at)=ipm_peak($m,"users");
    $ctx=($v===null)?"{ipm_no_sample_period}":"{ipm_on} ".htmlspecialchars($at);
    $src=count($m["peaks"]["users"])>0?$m["peaks"]["users"]:$m["series"]["users"];
    return ipm_zone("fa-solid fa-arrow-trend-up","{ipm_peak_period}",ipm_n($v===null?null:round($v)),$ctx,
        ipm_svg_spark($src,$c["users"][0],$c["users"][1]));
}

function ipm_zone_websites($m,$c){
    $l=$m["last"];
    list($v,$at)=ipm_peak($m,"websites");
    $ctx=($v===null)?"{ipm_no_sample_period}":"{ipm_peak_in_period}: ".ipm_n(round($v));
    return ipm_zone("fa-solid fa-globe","{ipm_websites_now}",ipm_n($l===null?null:$l["websites"]),$ctx,
        ipm_svg_spark($m["series"]["websites"],$c["websites"][0],$c["websites"][1]));
}

function ipm_zone_conns($m,$c){
    $l=$m["last"];
    $ctx="—";
    if($l!==null && $l["users"]>0){ $ctx="≈ ".sprintf("%.1f",$l["conns"]/$l["users"])." {ipm_per_client}"; }
    return ipm_zone("fa-solid fa-plug","{ipm_conns_now}",ipm_n($l===null?null:$l["conns"]),$ctx,
        ipm_svg_spark($m["series"]["conns"],$c["conns"][0],$c["conns"][1]));
}

function ipm_summary($m,$c){
    return "<div class='ak-card ipm-summary'>".ipm_zone_clients($m,$c).ipm_zone_peak($m,$c)
        .ipm_zone_websites($m,$c).ipm_zone_conns($m,$c)."</div>";
}

function ipm_chart_card($m,$k,$title,$sub,$name,$c,$big){
    $avg=$m["series"][$k]; $peak=$m["peaks"][$k];
    $h=array();
    $h[]="<div class='ak-card ak-card-pad ipm-chart'>";
    $h[]="<div class='ipm-ch-head'><div><div class='ipm-ch-t'>$title</div><div class='ak-muted'>$sub</div></div>";
    $h[]="<div class='ipm-legend'><span class='ipm-lg-i'><span class='ipm-lg-dot' style='background:{$c[$k][0]}'></span>$name</span>";
    if(count($peak)>0){ $h[]="<span class='ipm-lg-i'><span class='ipm-lg-band' style='background:{$c[$k][1]};border-color:{$c[$k][0]}'></span>{ipm_avg_peak}</span>"; }
    $h[]="</div></div>";
    if(count($m["labels"])==0){
        $h[]="<div class='ak-empty-c'><i class='fa-solid fa-chart-line'></i><div>{ipm_no_sample_period}</div></div></div>";
        return implode("\n",$h);
    }
    $h[]=ipm_svg_chart($m["labels"],$avg,$peak,$c[$k][0],$c[$k][1],array(
        "w"=>ipm_chart_width($m["width"],$big),"h"=>$big?300:240,"xt"=>$big?8:5,"aria"=>$title,"name"=>$name,"peak_name"=>"{ipm_peak}"));
    $h[]="</div>";
    return implode("\n",$h);
}

// Inner width of a chart card (measured: card padding + border = 18px), two
// columns above 1100px (same breakpoint as the container query of ipm_style()).
function ipm_chart_width($host,$big){
    if($big || $host<=1100){return max(300,$host-18);}
    return max(300,intval(($host-16)/2)-34);
}

function ipm_chart_clients($m,$c){
    $sub=$m["agg"]?"{ipm_sub_agg}":"{ipm_sub_samples}";
    return ipm_chart_card($m,"users","{ipm_distinct_clients}",$sub,"{ipm_clients}",$c,true);
}

function ipm_chart_websites($m,$c){
    return ipm_chart_card($m,"websites","{ipm_distinct_websites}","{ipm_websites_sub}","{ipm_websites}",$c,false);
}

function ipm_chart_conns($m,$c){
    return ipm_chart_card($m,"conns","{ipm_active_conns}","{ipm_conns_sub}","{connections}",$c,false);
}

function ipm_footer($m){
    if($m["db_ok"]){
        $state="<span class='ak-state ak-ok'><span class='ak-dot'></span> {ipm_db_running}</span>";
    }elseif($m["db_known"]){
        $state="<span class='ak-state ak-off'><span class='ak-dot'></span> {ipm_db_not_initialized}</span>";
    }else{
        $state="<span class='ak-state ak-err'><span class='ak-dot'></span> {ipm_db_no_answer}</span>";
    }
    $p=array("{ipm_history_db}: $state");
    if($m["db_entries"]!==null){$p[]=ipm_n($m["db_entries"])." {ipm_samples_kept}";}
    if($m["db_size"]!=""){$p[]=htmlspecialchars($m["db_size"]);}
    return "<div class='ipm-foot'>".implode(" · ",$p)."</div>";
}

function ipm_unavailable($m){
    if(!$m["db_known"]){
        return "<div class='ak-card ak-card-pad'><div class='ak-empty-c'><i class='fa-solid fa-plug-circle-xmark'></i>"
            ."<div><strong>{ipm_daemon_silent}</strong></div><div class='ak-muted'>{ipm_daemon_silent_explain}</div></div></div>";
    }
    return "<div class='ak-card ak-card-pad'><div class='ak-empty-c'><i class='fa-solid fa-database'></i>"
        ."<div><strong>{ipm_history_unavailable}</strong></div><div class='ak-muted'>{ipm_history_unavailable_explain}</div></div></div>";
}

function ipm_style(){
    if(isset($GLOBALS["IPM_STYLE"])){return "";}
    $GLOBALS["IPM_STYLE"]=true;
    return "<style>
.ipm{display:block;margin-top:10px;container-type:inline-size}
.ipm[aria-busy=true]{opacity:.65;transition:opacity .15s}
.ipm .ipm-tools{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.ipm .ipm-tools-r{display:flex;align-items:center;gap:14px;font-size:13px}
.ipm .ak-seg-b.on{background:var(--ak-fill,#0f766e);color:#fff}
.ipm .ak-seg-b:focus-visible,.ipm .ak-btn:focus-visible{outline:2px solid #2563eb;outline-offset:2px}
.ipm .ipm-summary{display:grid;grid-template-columns:repeat(4,1fr);margin-bottom:16px}
.ipm .ipm-zone{padding:18px 20px;min-width:0}
.ipm .ipm-zone+.ipm-zone{border-left:1px solid #e5e7eb}
.ipm .ipm-zone-l{font-size:13px;color:#4b5563;display:flex;align-items:center;gap:8px}
.ipm .ipm-zone-l svg{color:var(--ak-fill,#0f766e)}
.ipm .ipm-zone-b{display:flex;align-items:flex-end;justify-content:space-between;gap:10px;margin-top:6px}
.ipm .ipm-zone-txt{min-width:0}
.ipm .ipm-zone-v{font-size:28px;font-weight:700;line-height:1.15;font-variant-numeric:tabular-nums;color:#111827}
.ipm .ipm-zone-c{font-size:12px;color:#4b5563;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ipm .ipm-spark{width:120px;height:32px;flex:0 0 120px;display:block}
.ipm .ipm-chart{margin-bottom:16px}
.ipm .ipm-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
.ipm .ipm-grid2 .ipm-chart{margin-bottom:0}
.ipm .ipm-ch-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:10px;font-size:13px}
.ipm .ipm-ch-t{font-size:15px;font-weight:600;color:#111827;margin-bottom:2px}
.ipm .ipm-legend{display:flex;gap:14px;flex-wrap:wrap;font-size:13px;color:#374151}
.ipm .ipm-lg-i{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.ipm .ipm-lg-dot{display:inline-block;width:10px;height:10px;border-radius:50%}
.ipm .ipm-lg-band{display:inline-block;width:14px;height:10px;border-radius:2px;border-top:2px solid}
.ipm .ipm-svg{width:100%;height:auto;display:block;overflow:visible}
.ipm .ipm-grid{stroke:#eef0f3;stroke-width:1}
.ipm .ipm-tick{font-size:12px;fill:#4b5563}
.ipm .ipm-hv{fill:transparent}
.ipm .ipm-hv:hover{fill:rgba(15,23,42,.05)}
.ipm .ipm-foot{border-top:1px solid #e5e7eb;padding:12px 2px 4px;font-size:13px;color:#4b5563;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
@container (max-width:1100px){
.ipm .ipm-summary{grid-template-columns:1fr 1fr}
.ipm .ipm-zone:nth-child(3){border-left:0}
.ipm .ipm-zone:nth-child(n+3){border-top:1px solid #e5e7eb}
.ipm .ipm-grid2{grid-template-columns:1fr}
}
@media(max-width:1200px){
.ipm .ipm-summary{grid-template-columns:1fr 1fr}
.ipm .ipm-zone:nth-child(3){border-left:0}
.ipm .ipm-zone:nth-child(n+3){border-top:1px solid #e5e7eb}
.ipm .ipm-grid2{grid-template-columns:1fr}
}
@media(prefers-reduced-motion:reduce){.ipm[aria-busy=true]{transition:none}}
</style>";
}

// ── Dashboard fragment ───────────────────────────────────────────────────────

function ipm_dash(){
    $tpl=new template_admin();
    $m=ipm_model(ipm_hours());
    $c=ipm_colors();
    $h=array();
    $h[]=ipm_toolbar($m);
    if(!$m["db_ok"] && count($m["labels"])==0){
        $h[]=ipm_unavailable($m);
    }else{
        $h[]=ipm_summary($m,$c);
        $h[]=ipm_chart_clients($m,$c);
        $h[]="<div class='ipm-grid2'>".ipm_chart_websites($m,$c).ipm_chart_conns($m,$c)."</div>";
    }
    $h[]=ipm_footer($m);
    $host=$m["host"];
    $js="<script>(function(){var h=document.getElementById('$host');if(h){h.removeAttribute('aria-busy');}"
        ."if(typeof IpmArm==='function'){IpmArm('$host');}"
        ."if(typeof NoSpinner==='function'){NoSpinner();}})();</script>";
    echo ipm_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).$js;
}
