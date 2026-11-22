<?php
/**
 * Conntrackd dashboard: kernel connection-tracking table, its history and the
 * hosts filling it. Tab of fw.system.status.php ("Connections") and fragment of
 * fw.rrd.php. Design: stitch_conntrackd_operational_dashboard (2026-11-12).
 *
 * Every figure comes from /conntrackd/* (articarest, networking/Conntrackd):
 *   status   -> running, pid, version, count, max, tracking_engaged/disabled
 *   metrics  -> 1h/2h = raw per-minute samples, 6h/24h/48h = hourly, 7d/30d = daily
 *   live     -> conntrackd -s (since daemon start), conntrack -S (since boot),
 *               bytes/packets of the flows present in the table now
 *   top-*    -> conntrack -L grouped by IP, computed on demand
 *
 * The recorded samples store CUMULATIVE counters (created/updated/destroyed
 * since the daemon started) and a SNAPSHOT of the bytes held by live flows.
 * Aggregates SUM those raw values, so neither can be charted as is: the
 * lifecycle chart plots deltas between samples, the volume chart the average
 * snapshot of each bucket. See ctd_model_series().
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}

if(isset($_GET["dash"])){ ctd_dash(); exit; }
ctd_page();

// ── Shell: host div + refresh logic (one global timer) ───────────────────────

// No TINY here: this page is a TAB of fw.system.status.php, a TINY would
// overwrite that page's header (and "system-conntrackd" has no nginx rewrite).
function ctd_page(){
    $page=CurrentPageName();
    $h=array();
    $h[]="<div id='ctd-host' class='ak ctd'></div>";
    $h[]="<script>";
    $h[]="window.CtdRangeV=window.CtdRangeV||'1h';";
    $h[]="window.CtdTopTab=window.CtdTopTab||'src';";
    $h[]="window.CtdLoad=function(){";
    $h[]="  if(window.CtdTimer){clearTimeout(window.CtdTimer);window.CtdTimer=null;}";
    $h[]="  var host=document.getElementById('ctd-host'); if(!host){return;}";
    $h[]="  host.setAttribute('aria-busy','true');";
    $h[]="  // The SVG charts are drawn at the host's real width so their text stays at 12px.
  LoadAjaxSilent('ctd-host','$page?dash=yes&range='+encodeURIComponent(window.CtdRangeV)+'&w='+Math.round(host.clientWidth||0));";
    $h[]="};";
    $h[]="window.CtdRange=function(r){window.CtdRangeV=r;window.CtdLoad();};";
    $h[]="window.CtdRefresh=function(){window.CtdLoad();};";
    // Samples are recorded once a minute: polling faster would only redraw the same data.
    $h[]="window.CtdArm=function(){";
    $h[]="  if(window.CtdTimer){clearTimeout(window.CtdTimer);}";
    $h[]="  window.CtdTimer=setTimeout(function(){";
    $h[]="    window.CtdTimer=null;";
    $h[]="    if(!document.getElementById('ctd-host')){return;}";
    $h[]="    var busy=(typeof ModalIsOpenOrOpening==='function' && ModalIsOpenOrOpening());";
    $h[]="    if(!busy && typeof jQuery!=='undefined'){busy=jQuery('.sweet-alert.visible').length>0;}";
    $h[]="    if(busy){window.CtdArm();return;}";
    $h[]="    window.CtdLoad();";
    $h[]="  },60000);";
    $h[]="};";
    $h[]="window.CtdTop=function(t){";
    $h[]="  window.CtdTopTab=t;";
    $h[]="  var m={src:'ctd-top-src',dst:'ctd-top-dst'};";
    $h[]="  for(var k in m){";
    $h[]="    var p=document.getElementById(m[k]); var b=document.getElementById(m[k]+'-b');";
    $h[]="    if(p){p.hidden=(k!==t);}";
    $h[]="    if(b){b.className='ak-seg-b'+(k===t?' on':'');b.setAttribute('aria-selected',k===t?'true':'false');}";
    $h[]="  }";
    $h[]="};";
    $h[]="window.CtdLoad();";
    $h[]="</script>";
    echo implode("\n",$h);
}

// ── Data ─────────────────────────────────────────────────────────────────────

function _cd_api($path){
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API($path));
    if(!is_object($json) || !property_exists($json,'success') || !$json->success) return null;
    return property_exists($json,'data')?$json->data:null;
}

function ctd_range(){
    $r=trim((string)($_GET["range"] ?? "1h"));
    $ok=array("1h","6h","24h","7d","30d");
    if(!in_array($r,$ok,true)){$r="1h";}
    return $r;
}

// Width of the host div as measured by the browser.
function ctd_width(){
    $w=intval($_GET["w"] ?? 0);
    if($w<600){$w=1240;}
    if($w>2400){$w=2400;}
    return $w;
}

// Inner width of a chart card (card padding + border measured at 18px); half
// cards above 1200px, the breakpoint of ctd_style().
function ctd_chart_width($full){
    $host=ctd_width();
    if($full || $host<=1200){return max(300,$host-50);}
    return max(300,intval(($host-24)/2)-50);
}

function _cd_fmt($range){
    $m=array('1h'=>'H:i','2h'=>'H:i','6h'=>'H:00','24h'=>'H:00','48h'=>'H:00','7d'=>'D d','30d'=>'M d');
    return isset($m[$range])?$m[$range]:'H:i';
}

// One read of every route; the widgets only format what this returns.
function ctd_model($range){
    $m=array("range"=>$range,"now"=>time());

    $st=_cd_api("/conntrackd/status");
    $m["status_ok"]=is_object($st);
    $m["running"]=false; $m["pid"]=0; $m["version"]="";
    $m["count"]=null; $m["max"]=null; $m["engaged"]=true; $m["opted_out"]=false;
    if(is_object($st)){
        $m["running"]=!empty($st->running);
        $m["pid"]=$m["running"]?intval($st->pid ?? 0):0;
        $m["version"]=trim((string)($st->version ?? ""));
        // Absent on an older daemon: assume engaged rather than cry wolf.
        $m["engaged"]=property_exists($st,'tracking_engaged')?!empty($st->tracking_engaged):true;
        $m["opted_out"]=!empty($st->tracking_disabled);
        $max=intval($st->max ?? 0);
        if($max>0){$m["max"]=$max;}
        // A kernel that tracks nothing reports 0, which is not a measurement.
        if($m["engaged"]){$m["count"]=intval($st->count ?? 0);}
    }
    $m["pct"]=null;
    if($m["count"]!==null && $m["max"]!==null){$m["pct"]=$m["count"]*100/$m["max"];}

    $series=_cd_api("/conntrackd/metrics?range=$range");
    $m["series"]=ctd_model_series(is_array($series)?$series:array(),$range);
    if($range=="1h"){
        $m["hour"]=$m["series"];
    }else{
        $hour=_cd_api("/conntrackd/metrics?range=1h");
        $m["hour"]=ctd_model_series(is_array($hour)?$hour:array(),"1h");
    }

    $live=_cd_api("/conntrackd/live");
    $m["live_ok"]=is_object($live);
    $m["daemon"]=null;
    $m["flow_bytes"]=null; $m["flow_packets"]=null;
    $m["cpus"]=array();
    if(is_object($live)){
        if(isset($live->daemon) && is_object($live->daemon)){
            $d=$live->daemon;
            $m["daemon"]=array(
                "active"=>intval($d->active_connections ?? 0),
                "created"=>intval($d->connections_created ?? 0),
                "updated"=>intval($d->connections_updated ?? 0),
                "destroyed"=>intval($d->connections_destroyed ?? 0),
            );
        }
        // Top level, not ->daemon: conntrackd -s reports 0 bytes outside HA mode.
        if(property_exists($live,"traffic_bytes")){$m["flow_bytes"]=intval($live->traffic_bytes);}
        if(property_exists($live,"traffic_packets")){$m["flow_packets"]=intval($live->traffic_packets);}
        if(isset($live->cpu_stats) && is_array($live->cpu_stats)){
            foreach($live->cpu_stats as $c){
                if(!is_object($c)){continue;}
                $m["cpus"][]=$c;
            }
        }
    }

    // Asking for 15 leaves room for the loopback entries filtered out below.
    $m["src"]=ctd_model_top(_cd_api("/conntrackd/top-sources?limit=15&sort=connections"),"sources");
    $m["dst"]=ctd_model_top(_cd_api("/conntrackd/top-destinations?limit=15&sort=connections"),"destinations");
    return $m;
}

function ctd_model_top($data,$key){
    $out=array("ok"=>false,"total"=>null,"rows"=>array());
    if(!is_object($data)){return $out;}
    $out["ok"]=true;
    if(property_exists($data,"total_entries")){$out["total"]=intval($data->total_entries);}
    $list=(isset($data->$key) && is_array($data->$key))?$data->$key:array();
    foreach($list as $r){
        if(!is_object($r)){continue;}
        $ip=(string)($r->ip ?? "");
        if($ip=="" || $ip=="::1" || substr($ip,0,4)=="127."){continue;}
        $out["rows"][]=array("ip"=>$ip,"conns"=>intval($r->connections ?? 0),"bytes"=>intval($r->bytes ?? 0));
        if(count($out["rows"])>=10){break;}
    }
    return $out;
}

// Turns the stored points into chartable series.
//  - count/active: gauges, charted as is (average per bucket when aggregated).
//  - created/updated/destroyed: cumulative since daemon start -> difference
//    between consecutive points; a negative step is a daemon restart -> gap.
//    Aggregates hold SUM(raw cumulative), so the per-bucket mean is used first.
//  - bytes/packets: snapshot of the flows present at sampling time -> the
//    average snapshot of the bucket, never a sum.
function ctd_model_series($data,$range){
    $s=array("labels"=>array(),"agg"=>false,"conn"=>array(),"lo"=>array(),"hi"=>array(),"active"=>array(),
        "created"=>array(),"updated"=>array(),"destroyed"=>array(),"bytes"=>array(),"packets"=>array(),
        "has_daemon"=>false);
    if(count($data)==0){return $s;}
    $fmt=_cd_fmt($range);
    $s["agg"]=is_object($data[0]) && property_exists($data[0],"avg_count");
    $cum=array("created"=>array(),"updated"=>array(),"destroyed"=>array());
    foreach($data as $p){
        if(!is_object($p)){continue;}
        $s["labels"][]=date($fmt,intval($p->timestamp ?? 0));
        if($s["agg"]){
            $n=max(1,intval($p->sample_count ?? 1));
            $s["conn"][]=floatval($p->avg_count ?? 0);
            $s["lo"][]=floatval($p->min_count ?? 0);
            $s["hi"][]=floatval($p->max_count ?? 0);
            $s["active"][]=floatval($p->avg_active ?? 0);
            $cum["created"][]=floatval($p->total_created ?? 0)/$n;
            $cum["updated"][]=floatval($p->total_updated ?? 0)/$n;
            $cum["destroyed"][]=floatval($p->total_destroyed ?? 0)/$n;
            $s["bytes"][]=floatval($p->total_bytes ?? 0)/$n;
            $s["packets"][]=floatval($p->total_packets ?? 0)/$n;
        }else{
            $s["conn"][]=floatval($p->count ?? 0);
            $s["active"][]=floatval($p->active_connections ?? 0);
            $cum["created"][]=floatval($p->connections_created ?? 0);
            $cum["updated"][]=floatval($p->connections_updated ?? 0);
            $cum["destroyed"][]=floatval($p->connections_destroyed ?? 0);
            $s["bytes"][]=floatval($p->traffic_bytes ?? 0);
            $s["packets"][]=floatval($p->traffic_packets ?? 0);
        }
    }
    foreach($cum as $k=>$vals){
        $out=array();
        foreach($vals as $i=>$v){
            if($v>0){$s["has_daemon"]=true;}
            if($i==0){$out[]=null;continue;}
            $d=$v-$vals[$i-1];
            $out[]=($d<0 || $vals[$i-1]<=0)?null:round($d);
        }
        $s[$k]=$out;
    }
    return $s;
}

// ── Formatting helpers ───────────────────────────────────────────────────────

function ctd_n($v){
    if($v===null){return "—";}
    return number_format($v);
}

function ctd_bytes($b){
    if($b===null){return "—";}
    $b=floatval($b);
    $u=array("B","KB","MB","GB","TB");
    $i=0;
    while($b>=1024 && $i<count($u)-1){$b=$b/1024;$i++;}
    if($i==0){return number_format($b)." B";}
    return sprintf($b>=100?"%.0f %s":"%.1f %s",$b,$u[$i]);
}

function ctd_short($v){
    $v=floatval($v);
    if($v>=1000000){return rtrim(rtrim(sprintf("%.1f",$v/1000000),"0"),".")."M";}
    if($v>=10000){return rtrim(rtrim(sprintf("%.1f",$v/1000),"0"),".")."k";}
    return number_format($v);
}

function ctd_nice($v){
    if($v<=0){return 1;}
    $e=floor(log10($v));
    $p=pow(10,$e);
    $f=$v/$p;
    if($f<=1){$n=1;}elseif($f<=2){$n=2;}elseif($f<=2.5){$n=2.5;}elseif($f<=5){$n=5;}else{$n=10;}
    return $n*$p;
}

// Usage tone: same thresholds as the previous page (50 % / 80 %).
function ctd_usage_state($pct){
    if($pct===null){return array("ak-off","fa-solid fa-circle-question","{ctd_not_measured}");}
    if($pct>80){return array("ak-err","fa-solid fa-circle-xmark","{ctd_critical}");}
    if($pct>50){return array("ak-warn","fa-solid fa-circle-exclamation","{ctd_elevated}");}
    return array("ak-ok","fa-solid fa-circle-check","{ctd_normal}");
}

function ctd_accents(){
    $a=array("accent"=>"#10b981","accent_dark"=>"#0d9488","accent_soft"=>"rgba(16,185,129,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        foreach(array("accent","accent_dark","accent_soft") as $k){ if(!empty($b[$k])){$a[$k]=$b[$k];} }
    }
    return $a;
}

// ── SVG charts (no JS state: they re-render with the fragment) ──────────────

function ctd_svg_lines($labels,$series,$opt){
    $n=count($labels);
    if($n==0){return "";}
    $W=intval($opt["w"] ?? 600); $H=intval($opt["h"] ?? 250);
    $pl=54; $pr=16; $pt=16; $pb=30;
    $ymax=0;
    foreach($series as $se){ foreach($se["data"] as $v){ if($v!==null && $v>$ymax){$ymax=$v;} } }
    if(isset($opt["band"])){ foreach($opt["band"][1] as $v){ if($v>$ymax){$ymax=$v;} } }
    if(isset($opt["ref"])){ $ymax=max($ymax,$opt["ref"][0]); }
    $ymax=ctd_nice($ymax*1.05);
    $iw=$W-$pl-$pr; $ih=$H-$pt-$pb;
    $xs=array();
    for($i=0;$i<$n;$i++){ $xs[$i]=($n<=1)?$pl+$iw/2:$pl+$i*$iw/($n-1); }
    $Y=function($v) use($pt,$ih,$ymax){ return round($pt+$ih-($v/$ymax)*$ih,1); };

    $o=array();
    $o[]="<svg class='ctd-svg' viewBox='0 0 $W $H' role='img' aria-label=\"".htmlspecialchars($opt["aria"] ?? "")."\">";
    for($g=0;$g<=4;$g++){
        $v=$ymax*$g/4; $y=$Y($v);
        $lbl=(($opt["yfmt"] ?? "")=="bytes")?ctd_bytes($v):ctd_short($v);
        $o[]="<line x1='$pl' x2='".($W-$pr)."' y1='$y' y2='$y' class='ctd-grid'/>";
        $o[]="<text x='".($pl-8)."' y='".($y+4)."' text-anchor='end' class='ctd-tick'>$lbl</text>";
    }
    $step=max(1,intval(ceil($n/6)));
    for($i=0;$i<$n;$i+=$step){
        $anchor=($i==0 && $n>1)?"start":"middle";
        $o[]="<text x='".round($xs[$i],1)."' y='".($H-8)."' text-anchor='$anchor' class='ctd-tick'>".htmlspecialchars($labels[$i])."</text>";
    }
    if(isset($opt["band"])){
        $pts=array();
        for($i=0;$i<$n;$i++){ $pts[]=round($xs[$i],1).",".$Y($opt["band"][1][$i]); }
        for($i=$n-1;$i>=0;$i--){ $pts[]=round($xs[$i],1).",".$Y($opt["band"][0][$i]); }
        $o[]="<polygon points='".implode(" ",$pts)."' fill='{$opt["band"][2]}' stroke='none'/>";
    }
    if(isset($opt["ref"])){
        $y=$Y($opt["ref"][0]);
        $o[]="<line x1='$pl' x2='".($W-$pr)."' y1='$y' y2='$y' class='ctd-ref'/>";
        $o[]="<text x='".($W-$pr)."' y='".($y-5)."' text-anchor='end' class='ctd-tick'>".htmlspecialchars($opt["ref"][1])."</text>";
    }
    foreach($series as $se){
        $d=""; $pen=false; $single=array();
        foreach($se["data"] as $i=>$v){
            if($v===null){$pen=false;continue;}
            $d.=($pen?"L":"M").round($xs[$i],1).",".$Y($v)." ";
            $prev=($i>0)?$se["data"][$i-1]:null; $next=($i<$n-1)?$se["data"][$i+1]:null;
            if($prev===null && $next===null){$single[]=$i;}
            $pen=true;
        }
        if($d!=""){$o[]="<path d='".trim($d)."' fill='none' stroke='{$se["color"]}' stroke-width='2' stroke-linejoin='round' stroke-linecap='round'/>";}
        foreach($single as $i){ $o[]="<circle cx='".round($xs[$i],1)."' cy='".$Y($se["data"][$i])."' r='3' fill='{$se["color"]}'/>"; }
    }
    // Hover columns: native tooltip with every value of that point.
    $cw=($n<=1)?$iw:$iw/($n-1);
    for($i=0;$i<$n;$i++){
        $t=array($labels[$i]);
        foreach($series as $se){ $t[]=$se["name"].": ".ctd_n($se["data"][$i]===null?null:round($se["data"][$i])); }
        if(isset($opt["band"])){ $t[]=$opt["band"][3].": ".ctd_n(round($opt["band"][0][$i]))." – ".ctd_n(round($opt["band"][1][$i])); }
        $x=round(max($pl,$xs[$i]-$cw/2),1); $w=round(min($cw,$W-$pr-$x),1);
        $o[]="<rect x='$x' y='$pt' width='$w' height='$ih' class='ctd-hv'><title>".htmlspecialchars(implode("\n",$t))."</title></rect>";
    }
    $o[]="</svg>";
    return implode("",$o);
}

function ctd_svg_bars($labels,$data,$tips,$opt){
    $n=count($labels);
    if($n==0){return "";}
    $W=intval($opt["w"] ?? 1200); $H=intval($opt["h"] ?? 220);
    $pl=64; $pr=16; $pt=12; $pb=30;
    $ymax=0; foreach($data as $v){ if($v>$ymax){$ymax=$v;} }
    $ymax=ctd_nice($ymax*1.05);
    $iw=$W-$pl-$pr; $ih=$H-$pt-$pb;
    $slot=$iw/$n; $bw=max(2,min(28,$slot*0.6));
    $o=array();
    $o[]="<svg class='ctd-svg' viewBox='0 0 $W $H' role='img' aria-label=\"".htmlspecialchars($opt["aria"] ?? "")."\">";
    for($g=0;$g<=4;$g++){
        $v=$ymax*$g/4; $y=round($pt+$ih-($v/$ymax)*$ih,1);
        $o[]="<line x1='$pl' x2='".($W-$pr)."' y1='$y' y2='$y' class='ctd-grid'/>";
        $o[]="<text x='".($pl-8)."' y='".($y+4)."' text-anchor='end' class='ctd-tick'>".ctd_bytes($v)."</text>";
    }
    $step=max(1,intval(ceil($n/8)));
    for($i=0;$i<$n;$i++){
        $cx=$pl+$slot*($i+0.5);
        $h=($data[$i]/$ymax)*$ih;
        $y=round($pt+$ih-$h,1);
        $o[]="<rect x='".round($cx-$bw/2,1)."' y='$y' width='".round($bw,1)."' height='".round($h,1)."' rx='3' fill='{$opt["color"]}'/>";
        $o[]="<rect x='".round($pl+$slot*$i,1)."' y='$pt' width='".round($slot,1)."' height='$ih' class='ctd-hv'><title>".htmlspecialchars($labels[$i]."\n".$tips[$i])."</title></rect>";
        if($i%$step==0){
            $o[]="<text x='".round($cx,1)."' y='".($H-8)."' text-anchor='middle' class='ctd-tick'>".htmlspecialchars($labels[$i])."</text>";
        }
    }
    $o[]="</svg>";
    return implode("",$o);
}

function ctd_svg_spark($data,$color,$soft){
    $n=count($data);
    if($n<2){return "";}
    $W=240; $H=40;
    $mn=min($data); $mx=max($data); $span=($mx-$mn)>0?($mx-$mn):1;
    $pts=array();
    foreach($data as $i=>$v){ $pts[]=round($i*$W/($n-1),1).",".round(3+($H-6)*(1-($v-$mn)/$span),1); }
    $area="0,$H ".implode(" ",$pts)." $W,$H";
    return "<svg class='ctd-spark' viewBox='0 0 $W $H' preserveAspectRatio='none' aria-hidden='true'>"
        ."<polygon points='$area' fill='$soft'/>"
        ."<polyline points='".implode(" ",$pts)."' fill='none' stroke='$color' stroke-width='2' vector-effect='non-scaling-stroke'/></svg>";
}

// ── Widgets ─────────────────────────────────────────────────────────────────

function ctd_banner($m){
    if(!$m["status_ok"]){
        return "<div class='ak-banner ak-err ctd-banner'><i class='fa-solid fa-plug-circle-xmark'></i>"
            ."<div class='ak-banner-m'><strong>{ctd_status_unavailable}</strong><div>{ctd_status_unavailable_explain}</div></div></div>";
    }
    if($m["engaged"]){return "";}
    $chip=$m["opted_out"]?"DisableConntrackTracking=1":"DisableConntrackTracking";
    $txt=$m["opted_out"]?"{ctd_opted_out_short}":"{ctd_not_engaged_short}";
    return "<div class='ak-banner ak-warn ctd-banner'><i class='fa-solid fa-triangle-exclamation'></i>"
        ."<div class='ak-banner-m'><strong>{conntrack_not_engaged}</strong><div>$txt <span class='ak-chip'>$chip</span></div></div></div>";
}

function ctd_hero_usage($m,$acc){
    list($tone,$ico,$lbl)=ctd_usage_state($m["pct"]);
    $pctTxt="—";
    if($m["pct"]!==null){ $pctTxt=($m["pct"]>0 && $m["pct"]<1)?"&lt;1":(string)round($m["pct"]); }
    $ring=array("ak-ok"=>$acc["accent"],"ak-warn"=>"#d97706","ak-err"=>"#ba1a1a","ak-off"=>"#94a3b8");
    $C=round(2*M_PI*30,2);
    $frac=($m["pct"]===null)?0:min(1,max(0,$m["pct"]/100));
    $dash=round($C*$frac,2);
    $aria=($m["pct"]===null)?"{ctd_table_usage}: —":"{ctd_table_usage}: $pctTxt %";
    $h=array();
    $h[]="<div class='ctd-hz ctd-hz-usage'>";
    $h[]="<div class='ctd-hz-main'>";
    $h[]="<div class='ak-kicker'>{ctd_table_usage}</div>";
    $unit=($m["pct"]===null)?"":"<small> %</small>";
    $na=($m["pct"]===null)?" ctd-big-na":"";
    $h[]="<div class='ctd-big'><span class='ctd-big-v$na'>$pctTxt$unit</span>";
    $h[]="<span class='ctd-big-s'>".ctd_n($m["count"])." / ".ctd_n($m["max"])." {ctd_connections_lc}</span></div>";
    $h[]="<div class='ctd-hz-line'><span class='ak-pill $tone'><i class='$ico'></i> $lbl</span>";
    $h[]="<span class='ak-mono ak-muted ctd-cap'>nf_conntrack_max: ".ctd_n($m["max"])."</span></div>";
    $h[]="</div>";
    $h[]="<div class='ctd-ring' role='img' aria-label=\"$aria\">";
    $h[]="<svg class='ctd-ring-svg' viewBox='0 0 72 72' aria-hidden='true'><circle cx='36' cy='36' r='30' fill='none' stroke='#e2e8f0' stroke-width='8'/>";
    if($dash>0){
        $h[]="<circle cx='36' cy='36' r='30' fill='none' stroke='{$ring[$tone]}' stroke-width='8' stroke-linecap='round' stroke-dasharray='$dash $C' transform='rotate(-90 36 36)' class='ctd-ring-arc'/>";
    }
    $h[]="</svg><span class='ctd-ring-i'><i class='fa-solid fa-database'></i></span></div>";
    $h[]="</div>";
    return implode("\n",$h);
}

function ctd_hero_spark($m,$acc){
    $vals=$m["hour"]["conn"];
    $h=array();
    $h[]="<div class='ctd-hz ctd-hz-spark'>";
    $h[]="<div class='ctd-hz-head'><span class='ctd-hz-t'>{ctd_conn_last_hour}</span><span class='ak-muted ctd-cap'>{ctd_per_minute}</span></div>";
    if(count($vals)<2){
        $h[]="<div class='ak-note ctd-spark-empty'>{ctd_no_samples}</div>";
    }else{
        $mn=min($vals); $mx=max($vals); $mean=array_sum($vals)/count($vals);
        $h[]="<div class='ctd-spark-row'><span class='ak-muted ctd-cap ak-num'>".ctd_n(round($mn))."</span>";
        $h[]=ctd_svg_spark($vals,$acc["accent"],$acc["accent_soft"]);
        $h[]="<span class='ak-muted ctd-cap ak-num'>".ctd_n(round($mx))."</span></div>";
        $h[]="<div class='ctd-hz-line'><span>{ctd_mean}: <strong>".ctd_n(round($mean))."</strong></span>";
        $h[]="<span class='ak-muted'>{ctd_lowest} ".ctd_n(round($mn))." · {ctd_highest} ".ctd_n(round($mx))."</span></div>";
    }
    $h[]="</div>";
    return implode("\n",$h);
}

function ctd_hero_service($m){
    if($m["running"]){
        $pill="<span class='ak-pill ak-ok'><i class='fa-solid fa-circle-play'></i> {running}</span>";
    }else{
        $pill="<span class='ak-pill ak-err'><i class='fa-solid fa-circle-stop'></i> {stopped}</span>";
    }
    if(!$m["status_ok"]){$pill="<span class='ak-pill ak-off'><i class='fa-solid fa-circle-question'></i> {ctd_not_measured}</span>";}
    $pid=$m["pid"]>0?(string)$m["pid"]:"—";
    $ver=$m["version"]!=""?"v".htmlspecialchars($m["version"]):"—";
    $h=array();
    $h[]="<div class='ctd-hz ctd-hz-svc'>";
    $h[]="<div class='ctd-hz-head'><span class='ctd-svc-n'>conntrackd</span>$pill</div>";
    $h[]="<div class='ctd-kv'>";
    $h[]="<div><div class='ak-muted'>{ctd_process_id}</div><div class='ak-mono ctd-kv-v'>$pid</div></div>";
    $h[]="<div><div class='ak-muted'>{ctd_daemon_version}</div><div class='ak-mono ctd-kv-v'>$ver</div></div>";
    $h[]="</div></div>";
    return implode("\n",$h);
}

function ctd_hero($m,$acc){
    return "<div class='ak-card ctd-hero'>".ctd_hero_usage($m,$acc).ctd_hero_spark($m,$acc).ctd_hero_service($m)."</div>";
}

function ctd_toolbar($m){
    $ranges=array("1h"=>"{last_hour}","6h"=>"6h","24h"=>"24h","7d"=>"7d","30d"=>"30d");
    $h=array();
    $h[]="<div class='ctd-tools'>";
    $h[]="<div class='ak-seg' role='radiogroup' aria-label='{ctd_period}'>";
    foreach($ranges as $k=>$l){
        $on=($k==$m["range"]);
        $h[]="<button type='button' role='radio' aria-checked='".($on?"true":"false")."' class='ak-seg-b".($on?" on":"")."' onclick=\"CtdRange('$k')\">$l</button>";
    }
    $h[]="</div>";
    $h[]="<div class='ctd-tools-r'><span class='ak-muted'>{ctd_updated_at} <span class='ak-mono'>".date("H:i:s",$m["now"])."</span></span>";
    $h[]="<button type='button' class='ak-btn ak-btn-icon' aria-label='{refresh}' title='{refresh}' onclick=\"CtdRefresh()\"><i class='fa-solid fa-arrows-rotate'></i></button></div>";
    $h[]="</div>";
    return implode("\n",$h);
}

function ctd_legend($items){
    $o=array();
    foreach($items as $it){
        $mark=$it[2]=="band"?"<span class='ctd-lg-band' style='background:{$it[1]}'></span>":"<span class='ctd-lg-line' style='background:{$it[1]}'></span>";
        $o[]="<span class='ctd-lg-i'>$mark{$it[0]}</span>";
    }
    return "<div class='ctd-legend'>".implode("",$o)."</div>";
}

function ctd_chart_connections($m,$acc){
    $s=$m["series"];
    $sub=$s["agg"]?(in_array($m["range"],array("7d","30d"))?"{ctd_sub_daily}":"{ctd_sub_hourly}"):"{ctd_sub_tracked}";
    $h=array();
    $h[]="<div class='ak-card ak-card-pad ctd-chart'>";
    $h[]="<div class='ctd-ch-head'><div><div class='ctd-ch-t'>{ctd_tracked_connections}</div><div class='ak-muted'>$sub</div></div>";
    if($m["max"]!==null){$h[]="<span class='ak-mono ak-muted ctd-cap'>{ctd_ceiling}: ".ctd_n($m["max"])."</span>";}
    $h[]="</div>";
    if(count($s["labels"])==0){
        $h[]="<div class='ak-empty-c'><i class='fa-solid fa-chart-line'></i><div>{ctd_no_samples}</div></div></div>";
        return implode("\n",$h);
    }
    // No "active" line here: it is conntrackd's own cache (measured 40,080
    // entries next to 337 kernel connections on a lab box), so on a shared axis
    // it flattened the real curve to zero. Its live value stays in the counters card.
    $series=array(
        array("name"=>"{connections}","color"=>$acc["accent"],"data"=>$s["conn"]),
    );
    $opt=array("w"=>ctd_chart_width(false),"h"=>250,"aria"=>"{ctd_tracked_connections}");
    $leg=array(array("{connections}",$acc["accent"],"line"));
    if($s["agg"]){
        $opt["band"]=array($s["lo"],$s["hi"],$acc["accent_soft"],"{ctd_band}");
        $leg[]=array("{ctd_band}",$acc["accent_soft"],"band");
    }
    // The ceiling line only when it is close enough to say something.
    if($m["max"]!==null){
        $peak=0; foreach(array_merge($s["conn"],$s["hi"]) as $v){ if($v>$peak){$peak=$v;} }
        if($peak>0 && $m["max"]<=2*$peak){$opt["ref"]=array($m["max"],"nf_conntrack_max");}
    }
    $h[]=ctd_svg_lines($s["labels"],$series,$opt);
    $h[]=ctd_legend($leg);
    $h[]="</div>";
    return implode("\n",$h);
}

function ctd_chart_lifecycle($m){
    $s=$m["series"];
    $h=array();
    $h[]="<div class='ak-card ak-card-pad ctd-chart'>";
    $h[]="<div class='ctd-ch-head'><div><div class='ctd-ch-t'>{ctd_lifecycle}</div><div class='ak-muted'>{ctd_lifecycle_sub}</div></div></div>";
    $has=false;
    foreach(array("created","updated","destroyed") as $k){ foreach($s[$k] as $v){ if($v!==null){$has=true;} } }
    if(!$has){
        $msg=$s["has_daemon"]?"{ctd_no_samples}":"{ctd_daemon_unavailable}";
        $h[]="<div class='ak-empty-c'><i class='fa-solid fa-recycle'></i><div>$msg</div></div></div>";
        return implode("\n",$h);
    }
    $c=array("created"=>"#0d9488","updated"=>"#2563eb","destroyed"=>"#64748b");
    $series=array(
        array("name"=>"{ctd_created}","color"=>$c["created"],"data"=>$s["created"]),
        array("name"=>"{updated}","color"=>$c["updated"],"data"=>$s["updated"]),
        array("name"=>"{destroyed}","color"=>$c["destroyed"],"data"=>$s["destroyed"]),
    );
    $h[]=ctd_svg_lines($s["labels"],$series,array("w"=>ctd_chart_width(false),"h"=>250,"aria"=>"{ctd_lifecycle}"));
    $h[]=ctd_legend(array(array("{ctd_created}",$c["created"],"line"),array("{updated}",$c["updated"],"line"),array("{destroyed}",$c["destroyed"],"line")));
    $h[]="</div>";
    return implode("\n",$h);
}

function ctd_chart_volume($m,$acc){
    $s=$m["series"];
    $h=array();
    $h[]="<div class='ak-card ak-card-pad ctd-chart ctd-chart-full'>";
    $h[]="<div class='ctd-ch-head'><div><div class='ctd-ch-t'>{ctd_volume_title}</div><div class='ak-muted'>{ctd_volume_sub}</div></div>";
    if(count($s["bytes"])>0){
        $peak=max($s["bytes"]);
        $h[]="<span class='ak-mono ak-muted ctd-cap'>{ctd_highest}: <strong>".ctd_bytes($peak)."</strong></span>";
    }
    $h[]="</div>";
    if(count($s["labels"])==0){
        $h[]="<div class='ak-empty-c'><i class='fa-solid fa-chart-column'></i><div>{ctd_no_samples}</div></div></div>";
        return implode("\n",$h);
    }
    $tips=array();
    foreach($s["bytes"] as $i=>$b){ $tips[]=ctd_bytes($b)." · ".ctd_n(round($s["packets"][$i]))." {ctd_packets_lc}"; }
    $h[]=ctd_svg_bars($s["labels"],$s["bytes"],$tips,array("w"=>ctd_chart_width(true),"h"=>220,"color"=>$acc["accent"],"aria"=>"{ctd_volume_title}"));
    $h[]="</div>";
    return implode("\n",$h);
}

function ctd_top_table($top,$id,$hidden){
    $h=array();
    $h[]="<div id='$id' role='tabpanel'".($hidden?" hidden":"").">";
    if(!$top["ok"] || count($top["rows"])==0){
        $h[]="<div class='ak-empty-c'><i class='fa-solid fa-inbox'></i><div>{no_data}</div></div></div>";
        return implode("\n",$h);
    }
    $max=max(1,$top["rows"][0]["conns"]);
    $h[]="<table class='ak-tbl ctd-top'><thead><tr><th>{ctd_ip_address}</th><th>{connections}</th><th class='ak-end'>{ctd_volume}</th></tr></thead><tbody>";
    foreach($top["rows"] as $r){
        $w=intval(round($r["conns"]*100/$max));
        $h[]="<tr><td class='ak-mono'>".htmlspecialchars($r["ip"])."</td>";
        $h[]="<td><div class='ctd-cbar'><span class='ak-num ctd-cbar-n'>".ctd_n($r["conns"])."</span><div class='ak-bar'><span style='width:$w%'></span></div></div></td>";
        $h[]="<td class='ak-num ak-mono'>".ctd_bytes($r["bytes"])."</td></tr>";
    }
    $h[]="</tbody></table>";
    $h[]="<div class='ak-card-f ctd-top-f'><span>".ctd_n($top["total"])." {ctd_tracked_entries}</span><span class='ak-muted'>".count($top["rows"])." {ctd_hosts_shown}</span></div>";
    $h[]="</div>";
    return implode("\n",$h);
}

function ctd_top($m){
    $h=array();
    $h[]="<div class='ak-card ctd-topcard'>";
    $h[]="<div class='ak-card-h ctd-top-h'><div class='ak-seg' role='tablist'>";
    $h[]="<button type='button' role='tab' id='ctd-top-src-b' aria-controls='ctd-top-src' class='ak-seg-b on' aria-selected='true' onclick=\"CtdTop('src')\">{ctd_top_sources}</button>";
    $h[]="<button type='button' role='tab' id='ctd-top-dst-b' aria-controls='ctd-top-dst' class='ak-seg-b' aria-selected='false' onclick=\"CtdTop('dst')\">{ctd_top_destinations}</button>";
    $h[]="</div><span class='ak-muted'>{ctd_current_snapshot}</span></div>";
    $h[]=ctd_top_table($m["src"],"ctd-top-src",false);
    $h[]=ctd_top_table($m["dst"],"ctd-top-dst",true);
    $h[]="</div>";
    return implode("\n",$h);
}

function ctd_counters_daemon($m){
    $h=array();
    $h[]="<div class='ak-kicker ctd-k'>{ctd_since_daemon}</div>";
    if($m["daemon"]===null){
        $h[]="<div class='ak-note'>{ctd_daemon_unavailable}</div>";
        return implode("\n",$h);
    }
    $d=$m["daemon"];
    $cells=array("{active_connections}"=>$d["active"],"{ctd_created}"=>$d["created"],"{updated}"=>$d["updated"],"{destroyed}"=>$d["destroyed"]);
    $h[]="<div class='ctd-cells ctd-cells-4'>";
    foreach($cells as $l=>$v){ $h[]="<div class='ctd-cell'><div class='ak-muted'>$l</div><div class='ak-num ak-mono ctd-cell-v'>".ctd_n($v)."</div></div>"; }
    $h[]="</div>";
    return implode("\n",$h);
}

function ctd_counters_flows($m){
    $h=array();
    $h[]="<div class='ak-kicker ctd-k'>{ctd_flows_now}</div>";
    $h[]="<div class='ctd-cells ctd-cells-2'>";
    $h[]="<div class='ctd-cell ctd-cell-row'><span class='ak-muted'>{ctd_volume}</span><span class='ak-num ak-mono ctd-cell-v'>".ctd_bytes($m["flow_bytes"])."</span></div>";
    $h[]="<div class='ctd-cell ctd-cell-row'><span class='ak-muted'>{ctd_packets}</span><span class='ak-num ak-mono ctd-cell-v'>".ctd_n($m["flow_packets"])."</span></div>";
    $h[]="</div>";
    return implode("\n",$h);
}

// Raw kernel counter names on purpose: they map 1:1 onto `conntrack -S`.
function ctd_counters_cpu($m){
    $cols=array("invalid","insert_failed","drop","early_drop","search_restart","clash_resolve");
    $alarm=array("insert_failed"=>1,"drop"=>1,"early_drop"=>1);
    $h=array();
    $h[]="<div class='ctd-k-row'><div class='ak-kicker ctd-k'>{ctd_per_cpu}</div>";
    if(count($m["cpus"])>0){$h[]="<span class='ak-muted'>".count($m["cpus"])." {ctd_cpus}</span>";}
    $h[]="</div>";
    if(count($m["cpus"])==0){
        $h[]="<div class='ak-note'>{no_data}</div>";
        return implode("\n",$h);
    }
    $h[]="<div class='ctd-cpu-scroll'><table class='ak-tbl ctd-cpu'><thead><tr><th>CPU</th>";
    // <wbr> after the underscore lets the long kernel names wrap instead of
    // pushing the last column out of a half-width card.
    foreach($cols as $c){ $h[]="<th class='ak-end ak-mono'>".str_replace("_","_<wbr>",$c)."</th>"; }
    $h[]="</tr></thead><tbody>";
    foreach($m["cpus"] as $c){
        $h[]="<tr><td class='ak-mono'><strong>".intval($c->cpu ?? 0)."</strong></td>";
        foreach($cols as $k){
            $v=property_exists($c,$k)?intval($c->$k):null;
            if($v===null){ $h[]="<td class='ak-num ak-muted'>—</td>"; continue; }
            if($v==0){ $h[]="<td class='ak-num ctd-zero'>0</td>"; continue; }
            if(isset($alarm[$k])){
                $h[]="<td class='ak-num ctd-alarm'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i> ".ctd_n($v)."</td>";
                continue;
            }
            $h[]="<td class='ak-num'>".ctd_n($v)."</td>";
        }
        $h[]="</tr>";
    }
    $h[]="</tbody></table></div>";
    return implode("\n",$h);
}

function ctd_counters($m){
    return "<div class='ak-card ak-card-pad ctd-counters'><div class='ctd-ch-t'>{ctd_counters}</div>"
        .ctd_counters_daemon($m).ctd_counters_flows($m).ctd_counters_cpu($m)."</div>";
}

function ctd_style($acc){
    if(isset($GLOBALS["CTD_STYLE"])){return "";}
    $GLOBALS["CTD_STYLE"]=true;
    $a=$acc["accent"];
    return "<style>
.ctd{display:block;margin-top:10px;container-type:inline-size}
.ctd[aria-busy=true]{opacity:.65;transition:opacity .15s}
.ctd .ctd-banner{margin-bottom:16px;align-items:flex-start}
.ctd .ctd-banner .ak-chip{margin-left:4px}
.ctd .ctd-hero{display:grid;grid-template-columns:40% 35% 25%;margin-bottom:16px}
.ctd .ctd-hz{padding:20px 24px;min-width:0}
.ctd .ctd-hz+.ctd-hz{border-left:1px solid #e2e8f0}
.ctd .ctd-hz-usage{display:flex;align-items:center;justify-content:space-between;gap:16px}
.ctd .ctd-hz-main{min-width:0}
.ctd .ctd-big{display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;margin:4px 0 8px}
.ctd .ctd-big-v{font-size:36px;font-weight:700;line-height:1.1;font-variant-numeric:tabular-nums;color:#0f172a}
.ctd .ctd-big-v small{font-size:22px;font-weight:600}
.ctd .ctd-big-na{color:#94a3b8;font-weight:400}
.ctd .ctd-big-s{font-size:13px;color:#4b5563}
.ctd .ctd-hz-line{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:13px}
.ctd .ctd-cap{font-size:12px}
.ctd .ctd-ring{position:relative;width:72px;height:72px;flex:0 0 72px}
.ctd .ctd-ring-svg{width:72px;height:72px;display:block}
.ctd .ctd-ring-i{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:18px;color:#64748b}
.ctd .ctd-ring-arc{transition:stroke-dasharray .6s ease}
.ctd .ctd-hz-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px}
.ctd .ctd-hz-t{font-size:14px;font-weight:600;color:#0f172a}
.ctd .ctd-spark-row{display:flex;align-items:center;gap:8px;margin-bottom:8px}
.ctd .ctd-spark{flex:1 1 auto;width:100%;height:40px;display:block;min-width:0}
.ctd .ctd-spark-empty{padding:12px 0}
.ctd .ctd-svc-n{font-size:15px;font-weight:600;color:#0f172a}
.ctd .ctd-kv{display:grid;grid-template-columns:1fr 1fr;gap:12px;border-top:1px solid #f1f5f9;padding-top:12px;font-size:13px}
.ctd .ctd-kv-v{font-weight:700;margin-top:4px;color:#0f172a}
.ctd .ctd-tools{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.ctd .ctd-tools-r{display:flex;align-items:center;gap:12px}
.ctd .ak-seg-b.on{background:var(--ak-fill,#0f766e);color:#fff}
.ctd .ak-seg-b:focus-visible,.ctd .ak-btn:focus-visible{outline:2px solid #2563eb;outline-offset:2px}
.ctd .ctd-grid2{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:16px}
.ctd .ctd-chart-full{margin-bottom:16px}
.ctd .ctd-ch-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:12px;font-size:13px}
.ctd .ctd-ch-t{font-size:15px;font-weight:600;color:#0f172a;margin-bottom:2px}
.ctd .ctd-svg{width:100%;height:auto;display:block;overflow:visible}
.ctd .ctd-grid{stroke:#eef0f3;stroke-width:1}
.ctd .ctd-ref{stroke:#94a3b8;stroke-width:1;stroke-dasharray:5 4}
.ctd .ctd-tick{font-size:12px;fill:#4b5563}
.ctd .ctd-hv{fill:transparent}
.ctd .ctd-hv:hover{fill:rgba(15,23,42,.05)}
.ctd .ctd-legend{display:flex;gap:18px;justify-content:center;flex-wrap:wrap;margin-top:8px;font-size:13px;color:#374151}
.ctd .ctd-lg-i{display:inline-flex;align-items:center;gap:6px}
.ctd .ctd-lg-line{display:inline-block;width:14px;height:3px;border-radius:2px}
.ctd .ctd-lg-band{display:inline-block;width:14px;height:10px;border-radius:2px}
.ctd .ctd-top-h{display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:13px}
.ctd .ctd-top td,.ctd .ctd-top th{white-space:nowrap}
.ctd .ctd-top td:first-child{width:40%}
.ctd .ctd-cbar{display:flex;align-items:center;gap:12px}
.ctd .ctd-cbar-n{min-width:52px;text-align:right}
.ctd .ctd-cbar .ak-bar{width:96px;flex:0 0 96px}
.ctd .ctd-top-f{display:flex;justify-content:space-between;font-size:13px}
.ctd .ctd-k{margin:18px 0 8px}
.ctd .ctd-k-row{display:flex;align-items:baseline;justify-content:space-between;gap:8px;font-size:13px}
.ctd .ctd-cells{display:grid;gap:0;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden}
.ctd .ctd-cells-4{grid-template-columns:repeat(4,1fr)}
.ctd .ctd-cells-2{grid-template-columns:1fr 1fr;gap:12px;border:0;border-radius:0;overflow:visible}
.ctd .ctd-cells-2 .ctd-cell{border:1px solid #e5e7eb;border-radius:8px}
.ctd .ctd-cell{padding:10px 12px;font-size:13px;min-width:0}
.ctd .ctd-cells-4 .ctd-cell+.ctd-cell{border-left:1px solid #e5e7eb}
.ctd .ctd-cell-row{display:flex;justify-content:space-between;align-items:center;gap:8px}
.ctd .ctd-cell-v{font-weight:700;color:#0f172a;margin-top:4px}
.ctd .ctd-cell-row .ctd-cell-v{margin-top:0}
.ctd .ctd-cpu-scroll{max-height:320px;overflow:auto;border:1px solid #e5e7eb;border-radius:8px}
.ctd .ctd-cpu th,.ctd .ctd-cpu td{padding:8px 9px;font-size:13px;white-space:nowrap}
.ctd .ctd-cpu th{text-transform:none;position:sticky;top:0;background:#f8fafc;white-space:normal;vertical-align:bottom;line-height:1.3}
.ctd .ctd-zero{color:#9ca3af}
.ctd .ctd-alarm{color:#b91c1c;font-weight:700}
@container (max-width:1200px){
.ctd .ctd-hero{grid-template-columns:1fr}
.ctd .ctd-hz+.ctd-hz{border-left:0;border-top:1px solid #e2e8f0}
.ctd .ctd-grid2{grid-template-columns:1fr}
}
@media(max-width:1200px){
.ctd .ctd-hero{grid-template-columns:1fr}
.ctd .ctd-hz+.ctd-hz{border-left:0;border-top:1px solid #e2e8f0}
.ctd .ctd-grid2{grid-template-columns:1fr}
}
@media(prefers-reduced-motion:reduce){.ctd .ctd-ring-arc,.ctd[aria-busy=true]{transition:none}}
</style>";
}

// ── Dashboard fragment ───────────────────────────────────────────────────────

function ctd_dash(){
    $tpl=new template_admin();
    $m=ctd_model(ctd_range());
    $acc=ctd_accents();
    $h=array();
    $h[]=ctd_banner($m);
    $h[]=ctd_hero($m,$acc);
    $h[]=ctd_toolbar($m);
    $h[]="<div class='ctd-grid2'>".ctd_chart_connections($m,$acc).ctd_chart_lifecycle($m)."</div>";
    $h[]=ctd_chart_volume($m,$acc);
    $h[]="<div class='ctd-grid2'>".ctd_top($m).ctd_counters($m)."</div>";
    $js="<script>(function(){var h=document.getElementById('ctd-host');if(h){h.removeAttribute('aria-busy');}"
        ."if(typeof CtdTop==='function'){CtdTop(window.CtdTopTab||'src');}"
        ."if(typeof CtdArm==='function'){CtdArm();}"
        ."if(typeof NoSpinner==='function'){NoSpinner();}})();</script>";
    echo ctd_style($acc).$tpl->_ENGINE_parse_body(implode("\n",$h)).$js;
}
