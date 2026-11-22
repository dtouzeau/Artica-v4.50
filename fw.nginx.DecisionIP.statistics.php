<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["main-stats"])){main_stats();exit;}
if(isset($_GET["dips-host"])){dips_host();exit;}
page();

function page(): bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]= $tpl->page_header("DecisionIP {realtime_graphs}",ico_chart_line,"{DecisionIP_statistics_explain}",
        "$page?main-stats=yes","decisionip-stats","progress-decifw-stats",false,"table-decifw-stats");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// Translate a token, then substitute %s with values already escaped by the caller.
function dips_t($tpl,string $token,array $args=array()):string{
    $s=$tpl->_ENGINE_parse_body("{".$token."}");
    if(count($args)==0){return $s;}
    $parts=explode("%s",$s);
    $out=array_shift($parts);
    foreach($parts as $i=>$part){ $out.=(isset($args[$i])?$args[$i]:"").$part; }
    return $out;
}

function dips_num($n):string{
    return number_format(floatval($n),0,'.',',');
}

function dips_esc($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}

// The DecisionIP block lists, in display order. Key = the iptables log prefix as
// articarest records it: the lower-cased sub-chain name (DecisionIP/Fetch.go getSubChainName,
// SyslogTail.go). The whitelist only RETURNs, it never logs.
function dips_categories():array{
    return array(
        "activeattacks"         => array("label"=>"dipev_l_activeattacks",        "help"=>"dipev_l_activeattacks_help",        "color"=>"#dc2626"),
        "anonymousrelays"       => array("label"=>"dipev_l_anonymousrelays",      "help"=>"dipev_l_anonymousrelays_help",      "color"=>"#d97706"),
        "anonymousrelays-novpn" => array("label"=>"dipev_l_anonymousrelays_novpn","help"=>"dipev_l_anonymousrelays_novpn_help","color"=>"#a16207"),
        "unethicalbots"         => array("label"=>"dipev_l_unethicalbots",        "help"=>"dipev_l_unethicalbots_help",        "color"=>"#64748b"),
        "honeypot"              => array("label"=>"dipev_l_honeypot",             "help"=>"dipev_l_honeypot_help",             "color"=>"#0e7490"),
        "shadow_fortress"       => array("label"=>"dipev_l_shadow_fortress",      "help"=>"dipev_l_external_help",             "color"=>"#005447"),
        "datashield"            => array("label"=>"dipev_l_datashield",           "help"=>"dipev_l_external_help",             "color"=>"#15803d"),
    );
}

function dips_cat_label($tpl,string $key):string{
    $cats=dips_categories();
    if(isset($cats[$key])){return dips_t($tpl,$cats[$key]["label"]);}
    return dips_esc(strtoupper($key));
}

function dips_cat_color(string $key):string{
    $cats=dips_categories();
    if(isset($cats[$key])){return $cats[$key]["color"];}
    return "#9ca3af";
}

function dips_model():array{
    $M=array("ok"=>false,"error"=>"","ts"=>0,"total"=>0,"unique"=>0,"last5"=>0,
        "cats"=>array(),"series"=>array(),"top"=>array(),"active_now"=>0,"known"=>count(dips_categories()));

    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/chart/stats");
    $json=json_decode((string)$raw,true);
    if(!is_array($json)){
        $M["error"]="unreachable";
        return $M;
    }
    if(!isset($json["Status"]) || $json["Status"]!==true || !isset($json["Data"]) || !is_array($json["Data"])){
        $M["error"]=isset($json["Error"])?(string)$json["Error"]:"unreachable";
        if($M["error"]===""){$M["error"]="unreachable";}
        return $M;
    }
    $D=$json["Data"];
    $M["ok"]=true;
    $M["ts"]=intval($D["timestamp"] ?? 0);
    $M["total"]=intval($D["total_blocked"] ?? 0);
    $M["unique"]=intval($D["total_unique_ips"] ?? 0);

    // Known categories first (fixed order), then any unknown prefix the daemon reports.
    $known=dips_categories();
    $keys=array_keys($known);
    $prefixes=(isset($D["prefixes"]) && is_array($D["prefixes"]))?$D["prefixes"]:array();
    foreach($prefixes as $k=>$v){ if(!in_array($k,$keys,true)){ $keys[]=(string)$k; } }

    $merged=array();
    foreach($keys as $k){
        $p=(isset($prefixes[$k]) && is_array($prefixes[$k]))?$prefixes[$k]:array();
        $c=array("total"=>intval($p["total_blocked"] ?? 0),"unique"=>intval($p["unique_ips"] ?? 0),
            "last5"=>intval($p["last_5min"] ?? 0),"seen"=>isset($prefixes[$k]));
        $M["cats"][$k]=$c;
        $M["last5"]+=$c["last5"];
        if($c["last5"]>0 && isset($known[$k])){$M["active_now"]++;}
        $tops=(isset($p["top_ips"]) && is_array($p["top_ips"]))?$p["top_ips"]:array();
        foreach($tops as $t){
            $ip=(string)($t["ip"] ?? "");
            if($ip===""){continue;}
            if(!isset($merged[$ip])){$merged[$ip]=array("ip"=>$ip,"count"=>0,"cats"=>array());}
            $merged[$ip]["count"]+=intval($t["count"] ?? 0);
            $merged[$ip]["cats"][]=$k;
        }
    }
    usort($merged,function($a,$b){ if($a["count"]==$b["count"]){return strcmp($a["ip"],$b["ip"]);} return ($a["count"]>$b["count"])?-1:1; });
    $M["top"]=array_slice($merged,0,10);

    $ts=(isset($D["time_series"]) && is_array($D["time_series"]))?$D["time_series"]:array();
    foreach($ts as $pt){
        $counts=(isset($pt["counts"]) && is_array($pt["counts"]))?$pt["counts"]:array();
        $row=array("t"=>intval($pt["timestamp"] ?? 0),"c"=>array());
        foreach($counts as $k=>$n){ $row["c"][(string)$k]=intval($n); }
        $M["series"][]=$row;
    }
    return $M;
}

function main_stats(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $M=dips_model();

    $html[]="<div class='dips'>";
    $html[]=dips_banner($tpl);
    $html[]="<div id='dips-host' aria-live='polite'>".dips_widgets($tpl,$M)."</div>";
    $html[]="</div>";

    $topbuttons=array();
    $topbuttons[]=array("DipsReload(true);",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="DecisionIP {realtime_graphs}";
    $TINY_ARRAY["ICO"]=ico_chart_line;
    $TINY_ARRAY["EXPL"]="{DecisionIP_statistics_explain}";
    $TINY_ARRAY["URL"]="decisionip-stats";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo dips_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).dips_js($page,$M).dips_host_js($M)."<script>$tiny</script>";
}

function dips_host(){
    $tpl=new template_admin();
    $M=dips_model();
    echo $tpl->_ENGINE_parse_body(dips_widgets($tpl,$M)).dips_host_js($M);
}

// Everything that changes on each refresh.
function dips_widgets($tpl,array $M):string{
    if(!$M["ok"]){
        return dips_alert($tpl,$M);
    }
    if($M["total"]==0){
        return dips_empty($tpl);
    }
    $h=array();
    $h[]="<div class='dips-kpis'>";
    $h[]=dips_kpi_total($tpl,$M);
    $h[]=dips_kpi_last5($tpl,$M);
    $h[]=dips_kpi_sources($tpl,$M);
    $h[]=dips_kpi_cats($tpl,$M);
    $h[]="</div>";
    $h[]=dips_live_chart($tpl,$M);
    $h[]="<div class='dips-duo'>";
    $h[]=dips_share_card($tpl,$M);
    $h[]=dips_totals_card($tpl,$M);
    $h[]="</div>";
    $h[]=dips_top_card($tpl,$M);
    return implode("\n",$h);
}

function dips_banner($tpl):string{
    return "<section class='dips-banner'>
        <div class='dips-banner-ico' aria-hidden='true'><i class='fas fa-shield-check'></i></div>
        <div class='dips-banner-body'>
            <h2 class='dips-banner-title'>{dips_banner_title}</h2>
            <p class='dips-banner-text'>{dips_banner_intro}</p>
            <div class='dips-chips'>
                <span class='dips-chip'><i class='fas fa-bolt' aria-hidden='true'></i> {dips_chip_refresh}</span>
                <span class='dips-chip'><i class='fas fa-rotate-left' aria-hidden='true'></i> {dips_chip_since_start}</span>
                <span class='dips-chip'><i class='fas fa-clock' aria-hidden='true'></i> {dips_chip_updated} <span id='dips-updated'>&mdash;</span></span>
            </div>
        </div>
        <div class='dips-live'>
            <span class='dips-live-state' id='dips-live-state'><span class='dips-dot' aria-hidden='true'></span><span id='dips-live-word' data-live=\"{dips_live}\" data-paused=\"{dips_paused}\" data-err=\"{dips_offline}\">{dips_live}</span></span>
            <span class='dips-live-sep' aria-hidden='true'></span>
            <button type='button' class='dips-switch' id='dips-switch' role='switch' aria-checked='true' onclick='DipsToggle();'>
                <span class='dips-switch-label'>{dips_live_updates}</span>
                <span class='dips-track' aria-hidden='true'><span class='dips-thumb'></span></span>
            </button>
        </div>
    </section>";
}

function dips_info($tpl,string $token):string{
    $t=dips_t($tpl,$token);
    return "<span class='dips-info' title=\"$t\" aria-label=\"$t\" tabindex='0'><i class='fas fa-circle-info' aria-hidden='true'></i></span>";
}

function dips_kpi_total($tpl,array $M):string{
    return "<div class='dips-card dips-kpi'>
        <div class='dips-kpi-head'><span>{dips_kpi_total}</span>".dips_info($tpl,"dips_kpi_total_tip")."</div>
        <div class='dips-kpi-val'>".dips_num($M["total"])."</div>
        <div class='dips-kpi-sub'>{dips_kpi_total_sub}</div>
    </div>";
}

function dips_kpi_last5($tpl,array $M):string{
    $pct=$M["total"]>0?number_format($M["last5"]*100/$M["total"],1,'.',','):"0.0";
    return "<div class='dips-card dips-kpi'>
        <div class='dips-kpi-head'><span>{dips_kpi_last5}</span>".dips_info($tpl,"dips_kpi_last5_tip")."</div>
        <div class='dips-kpi-val'>".dips_num($M["last5"])."</div>
        <div class='dips-kpi-sub'>".dips_t($tpl,"dips_kpi_last5_sub",array($pct))."</div>
    </div>";
}

function dips_kpi_sources($tpl,array $M):string{
    return "<div class='dips-card dips-kpi'>
        <div class='dips-kpi-head'><span>{dips_kpi_sources}</span>".dips_info($tpl,"dips_kpi_sources_tip")."</div>
        <div class='dips-kpi-val'>".dips_num($M["unique"])."</div>
        <div class='dips-kpi-sub'>{dips_kpi_sources_sub}</div>
    </div>";
}

function dips_kpi_cats($tpl,array $M):string{
    $names=array();
    $cats=dips_categories();
    foreach($M["cats"] as $k=>$c){ if($c["last5"]>0 && isset($cats[$k])){ $names[]=dips_cat_label($tpl,$k); } }
    $sub=count($names)>0?implode(", ",$names):dips_t($tpl,"dips_kpi_cats_none");
    $known=$M["known"];
    $now=$M["active_now"];
    return "<div class='dips-card dips-kpi'>
        <div class='dips-kpi-head'><span>{dips_kpi_cats}</span>".dips_info($tpl,"dips_kpi_cats_help")."</div>
        <div class='dips-kpi-row'><div class='dips-kpi-val'>".dips_t($tpl,"dips_x_of_y",array($M["active_now"],$known))."</div>
        <span class='dips-peity' aria-hidden='true'>$now/$known</span></div>
        <div class='dips-kpi-sub dips-ellipsis' title=\"".strip_tags($sub)."\">$sub</div>
    </div>";
}

// Axis maximum = 4 equal integer steps (1, 1.5, 2, 2.5, 3, 4, 5, 6, 8 x 10^n).
function dips_nice_max(int $v):int{
    $raw=max(1,$v)/4;
    $p=pow(10,floor(log10($raw)));
    foreach(array(1,1.5,2,2.5,3,4,5,6,8,10) as $m){ $st=$m*$p; if($st>=$raw && abs($st-round($st))<0.001){ return intval(round($st)*4); } }
    return intval(10*$p*4);
}

function dips_live_chart($tpl,array $M):string{
    $h=array();
    $h[]="<section class='dips-card dips-chart'>";
    $h[]="<div class='dips-card-head'><div><h3 class='dips-card-title'>{dips_chart_title}".dips_info($tpl,"dips_chart_tip")."</h3>
        <p class='dips-card-sub'>{dips_chart_sub}</p></div></div>";

    $series=$M["series"];
    $n=count($series);
    if($M["last5"]==0 || $n<2){
        $h[]="<div class='dips-chart-empty'><i class='fas fa-wave-square' aria-hidden='true'></i>
            <div class='dips-chart-empty-title'>{dips_chart_empty_title}</div>
            <div class='dips-chart-empty-text'>{dips_chart_empty_text}</div></div>";
        $h[]="</section>";
        return implode("\n",$h);
    }

    // Stack order: bottom = last category, top = Active attacks.
    $order=array();
    foreach(array_reverse(array_keys($M["cats"])) as $k){
        $any=false;
        foreach($series as $pt){ if(($pt["c"][$k] ?? 0)>0){ $any=true; break; } }
        if($any){ $order[]=$k; }
    }
    $max=0;
    foreach($series as $pt){ $s=0; foreach($order as $k){ $s+=($pt["c"][$k] ?? 0); } if($s>$max){$max=$s;} }
    $ymax=dips_nice_max($max);
    $W=1000;$H=200;
    $base=array_fill(0,$n,0);
    $paths=array();
    foreach($order as $k){
        $top=array();
        $lo=array();
        for($i=0;$i<$n;$i++){
            $x=sprintf('%.1f',$i*$W/($n-1));
            $v=$base[$i]+($series[$i]["c"][$k] ?? 0);
            $top[]="$x,".sprintf('%.1f',$H-($v*$H/$ymax));
            $lo[]="$x,".sprintf('%.1f',$H-($base[$i]*$H/$ymax));
            $base[$i]=$v;
        }
        $pts=implode(" ",$top)." ".implode(" ",array_reverse($lo));
        $col=dips_cat_color($k);
        $paths[]="<polygon points='$pts' fill='$col' fill-opacity='0.55' stroke='$col' stroke-width='1.5' stroke-linejoin='round' vector-effect='non-scaling-stroke'/>";
    }

    // Text summary for screen readers: total and leading category.
    $lead="";$leadN=-1;
    foreach($M["cats"] as $k=>$c){ if($c["last5"]>$leadN){$leadN=$c["last5"];$lead=$k;} }
    $aria=strip_tags(dips_t($tpl,"dips_chart_aria",array(dips_num($M["last5"]),dips_cat_label($tpl,$lead))));

    $grid=array();
    $ylabels=array();
    for($g=0;$g<=4;$g++){
        $y=$H*$g/4;
        $grid[]="<line x1='0' x2='$W' y1='$y' y2='$y' stroke='#eef0f2' stroke-width='1' vector-effect='non-scaling-stroke'/>";
        $ylabels[]="<span>".dips_num($ymax*(4-$g)/4)."</span>";
    }
    $h[]="<div class='dips-plot' role='img' aria-label=\"".dips_esc($aria)."\">";
    $h[]="<div class='dips-yaxis' aria-hidden='true'>".implode("",$ylabels)."</div>";
    $h[]="<div class='dips-plot-area'><svg viewBox='0 0 $W $H' preserveAspectRatio='none' class='dips-svg'>".implode("",$grid).implode("",$paths)."</svg>";
    $xl=array();
    foreach(array(0,0.2,0.4,0.6,0.8,1) as $f){
        $i=intval(round($f*($n-1)));
        $t=$series[$i]["t"];
        $xl[]="<span>".($t>0?date("H:i:s",$t):"")."</span>";
    }
    $h[]="<div class='dips-xaxis' aria-hidden='true'>".implode("",$xl)."</div></div>";
    $h[]="</div>";
    $h[]=dips_legend($tpl,$M);
    $h[]="</section>";
    return implode("\n",$h);
}

function dips_legend($tpl,array $M):string{
    $h=array();
    $h[]="<ul class='dips-legend'>";
    foreach($M["cats"] as $k=>$c){
        $cls=$c["last5"]>0?"":" dips-muted";
        $h[]="<li class='dips-legend-item$cls'><span class='dips-swatch' style='background:".dips_cat_color($k)."'></span>".dips_cat_label($tpl,$k)." <span class='dips-legend-n'>".dips_num($c["last5"])."</span></li>";
    }
    $h[]="</ul>";
    return implode("",$h);
}

function dips_share_card($tpl,array $M):string{
    $total=$M["last5"];
    $h=array();
    $h[]="<section class='dips-card dips-share'>";
    $h[]="<div class='dips-card-head'><div><h3 class='dips-card-title'>{dips_share_title}".dips_info($tpl,"dips_share_tip")."</h3>
        <p class='dips-card-sub'>".dips_t($tpl,"dips_share_sub",array(dips_num($total)))."</p></div></div>";

    $r=38;$C=2*M_PI*$r;$off=0;$rings=array();
    if($total>0){
        foreach($M["cats"] as $k=>$c){
            if($c["last5"]<=0){continue;}
            $len=$C*$c["last5"]/$total;
            $rings[]="<circle cx='50' cy='50' r='$r' fill='none' stroke='".dips_cat_color($k)."' stroke-width='14' stroke-dasharray='".sprintf('%.2f %.2f',$len,$C-$len)."' stroke-dashoffset='".sprintf('%.2f',-$off)."'/>";
            $off+=$len;
        }
    }
    $h[]="<div class='dips-share-body'>";
    $h[]="<div class='dips-donut'><svg viewBox='0 0 100 100' aria-hidden='true'><circle cx='50' cy='50' r='$r' fill='none' stroke='#eef0f2' stroke-width='14'/><g transform='rotate(-90 50 50)'>".implode("",$rings)."</g></svg>
        <div class='dips-donut-center'><span class='dips-donut-n'>".dips_num($total)."</span><span class='dips-donut-l'>{dips_blocked_word}</span></div></div>";
    $h[]="<ul class='dips-share-list'>";
    foreach($M["cats"] as $k=>$c){
        $pct=$total>0?number_format($c["last5"]*100/$total,1,'.',','):"0.0";
        $cls=$c["last5"]>0?"":" dips-muted";
        $h[]="<li class='dips-share-row$cls'><span class='dips-share-name'><span class='dips-swatch' style='background:".dips_cat_color($k)."'></span>".dips_cat_label($tpl,$k)."</span>
            <span class='dips-share-val'>".dips_num($c["last5"])." <span class='dips-pct'>($pct %)</span></span></li>";
    }
    $h[]="</ul></div></section>";
    return implode("\n",$h);
}

function dips_totals_card($tpl,array $M):string{
    $max=0;
    foreach($M["cats"] as $c){ if($c["total"]>$max){$max=$c["total"];} }
    $cats=dips_categories();
    $h=array();
    $h[]="<section class='dips-card dips-totals'>";
    $h[]="<div class='dips-card-head'><div><h3 class='dips-card-title'>{dips_totals_title}".dips_info($tpl,"dips_totals_tip")."</h3>
        <p class='dips-card-sub'>{dips_totals_sub}</p></div></div>";
    $h[]="<ul class='dips-bars'>";
    foreach($M["cats"] as $k=>$c){
        $w=$max>0?round($c["total"]*100/$max,1):0;
        if($c["total"]>0 && $w<1){$w=1;}
        $help=isset($cats[$k])?dips_t($tpl,$cats[$k]["help"]):"";
        $right=$c["total"]>0
            ?"<b>".dips_num($c["total"])."</b><span class='dips-bar-5'>".dips_t($tpl,"dips_bar_last5",array(dips_num($c["last5"])))."</span>"
            :"<span class='dips-bar-5'>{dips_bar_none}</span>";
        $h[]="<li class='dips-bar'>
            <div class='dips-bar-top'><span class='dips-bar-name'>".dips_cat_label($tpl,$k)."<span class='dips-bar-help'>$help</span></span><span class='dips-bar-val'>$right</span></div>
            <div class='dips-bar-track'><span style='width:$w%;background:".dips_cat_color($k)."'></span></div>
        </li>";
    }
    $h[]="</ul></section>";
    return implode("\n",$h);
}

function dips_top_card($tpl,array $M):string{
    $h=array();
    $h[]="<section class='dips-card dips-top'>";
    $h[]="<div class='dips-card-head'><div><h3 class='dips-card-title'>{dips_top_title}".dips_info($tpl,"dips_top_tip")."</h3>
        <p class='dips-card-sub'>{dips_top_sub}</p></div></div>";
    if(count($M["top"])==0){
        $h[]="<div class='dips-card-sub' style='padding:16px 0'>{dips_top_empty}</div></section>";
        return implode("\n",$h);
    }
    $max=max(1,intval($M["top"][0]["count"]));
    $h[]="<table class='dips-table'><thead><tr><th style='width:48px'>#</th><th>{dips_col_address}</th><th>{dips_col_category}</th><th class='dips-r' style='width:240px'>{dips_col_blocked}</th></tr></thead><tbody>";
    $i=0;
    foreach($M["top"] as $r){
        $i++;
        $chips=array();
        foreach($r["cats"] as $k){
            $chips[]="<span class='dips-cat'><span class='dips-swatch' style='background:".dips_cat_color($k)."'></span>".dips_cat_label($tpl,$k)."</span>";
        }
        $w=round($r["count"]*100/$max,1);
        $h[]="<tr><td class='dips-rank'>$i</td><td><span class='dips-ip'>".dips_esc($r["ip"])."</span></td><td>".implode(" ",$chips)."</td>
            <td class='dips-r'><span class='dips-mini'><span style='width:$w%'></span></span><span class='dips-count'>".dips_num($r["count"])."</span></td></tr>";
    }
    $h[]="</tbody></table></section>";
    return implode("\n",$h);
}

function dips_empty($tpl):string{
    return "<section class='dips-card dips-state'>
        <div class='dips-state-ico'><i class='fas fa-shield-halved' aria-hidden='true'></i></div>
        <div class='dips-state-body'>
            <h3 class='dips-card-title'>{dips_empty_title}</h3>
            <p class='dips-state-text'>{dips_empty_text}</p>
            <button type='button' class='dips-btn' onclick=\"document.location.href='/decisionip';\"><i class='fas fa-sliders' aria-hidden='true'></i> {dips_empty_btn}</button>
        </div>
    </section>";
}

function dips_alert($tpl,array $M):string{
    $detail="";
    if($M["error"]!=="" && $M["error"]!=="unreachable"){
        $detail="<p class='dips-state-detail'>".dips_esc($M["error"])."</p>";
    }
    return "<section class='dips-card dips-state dips-state-err' role='alert'>
        <div class='dips-state-ico'><i class='fas fa-circle-exclamation' aria-hidden='true'></i></div>
        <div class='dips-state-body'>
            <h3 class='dips-card-title'>{dips_err_title}</h3>
            <p class='dips-state-text'>{dips_err_text}</p>$detail
            <button type='button' class='dips-btn dips-btn-err' onclick='DipsReload(true);'><i class='fas fa-rotate-right' aria-hidden='true'></i> {dips_try_again}</button>
        </div>
    </section>";
}

// Run after each host render: last-update time, peity donut.
function dips_host_js(array $M):string{
    $upd=$M["ok"]&&$M["ts"]>0?date("H:i:s",$M["ts"]):"";
    return "<script>(function(){var u=document.getElementById('dips-updated');if(u){u.textContent=".json_encode($upd!==""?$upd:"—").";}
if(window.jQuery && jQuery.fn.peity){jQuery('#dips-host .dips-peity').peity('donut',{fill:['#005447','#e5e7eb'],radius:14,innerRadius:9});}
if(window.DipsSetState){DipsSetState(".($M["ok"]?"true":"false").");}})();</script>";
}

function dips_js(string $page,array $M):string{
    return "<script src='js/jquery.peity.min.js'></script>
<script>
if(typeof window.DipsPaused==='undefined'){ window.DipsPaused=false; }
window.DipsOk=".($M["ok"]?"true":"false").";
function DipsModalOpen(){
    if(document.querySelector('.modal.in, .modal.show')){ return true; }
    var sw=document.querySelector('.sweet-alert.visible, .sweet-alert.showSweetAlert');
    return !!(sw && sw.offsetParent!==null);
}
function DipsSetState(ok){
    window.DipsOk=ok;
    var w=document.getElementById('dips-live-word'), s=document.getElementById('dips-live-state'), b=document.getElementById('dips-switch');
    if(!w||!s||!b){ return; }
    var live=!window.DipsPaused;
    b.setAttribute('aria-checked', live?'true':'false');
    s.className='dips-live-state'+(live?(ok?'':' dips-live-err'):' dips-live-paused');
    w.textContent= live ? (ok?w.getAttribute('data-live'):w.getAttribute('data-err')) : w.getAttribute('data-paused');
}
function DipsToggle(){ window.DipsPaused=!window.DipsPaused; DipsSetState(window.DipsOk); if(!window.DipsPaused){ DipsReload(false); } }
function DipsReload(force){
    if(!document.getElementById('dips-host')){ return; }
    if(!force && (window.DipsPaused || document.hidden || DipsModalOpen())){ return; }
    LoadAjaxSilent('dips-host','$page?dips-host=yes');
}
(function(){
    if(window.DipsTimer){ clearInterval(window.DipsTimer); }
    window.DipsTimer=setInterval(function(){
        if(!document.getElementById('dips-host')){ clearInterval(window.DipsTimer); window.DipsTimer=null; return; }
        DipsReload(false);
    },5000);
    DipsSetState(window.DipsOk);
})();
</script>";
}

function dips_style():string{
    return "<style>
.dips{--dips-acc:#005447;--dips-acc-h:#003d33;--dips-soft:#e6eeed;--dips-bd:#bfd4d1;color:#111827;font-size:14px;padding:4px 0 24px}
.dips *{box-sizing:border-box}
.dips-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05);padding:20px 24px}
.dips-banner{display:flex;gap:20px;align-items:flex-start;background:#fff;border:1px solid #e5e7eb;border-left:4px solid var(--dips-acc);border-radius:12px;padding:20px 24px;margin-bottom:24px;box-shadow:0 1px 2px rgba(0,0,0,.05)}
.dips-banner-ico{flex:0 0 48px;height:48px;border-radius:12px;background:var(--dips-soft);color:var(--dips-acc);display:flex;align-items:center;justify-content:center;font-size:22px}
.dips-banner-body{flex:1;min-width:0}
.dips-banner-title{font-size:18px;font-weight:600;margin:0 0 6px;color:#111827}
.dips-banner-text{font-size:14px;line-height:21px;color:#374151;margin:0 0 12px;max-width:860px}
.dips-chips{display:flex;flex-wrap:wrap;gap:8px}
.dips-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#374151;background:#f2f6f6;border:1px solid #e3ebea;border-radius:999px;padding:4px 12px}
.dips-chip i,.dips-chip svg{color:var(--dips-acc)}
.dips-live{display:flex;align-items:center;gap:12px;background:#f6f7f8;border:1px solid #e5e7eb;border-radius:10px;padding:8px 14px;white-space:nowrap}
.dips-live-state{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#065f46}
.dips-dot{width:10px;height:10px;border-radius:50%;background:#16a34a;box-shadow:0 0 0 0 rgba(22,163,74,.5);animation:dipsPulse 1.8s infinite}
.dips-live-paused{color:#6b7280}.dips-live-paused .dips-dot{background:#9ca3af;animation:none}
.dips-live-err{color:#b91c1c}.dips-live-err .dips-dot{background:#dc2626;animation:none}
.dips-live-sep{width:1px;height:20px;background:#d1d5db}
.dips-switch{display:inline-flex;align-items:center;gap:10px;background:none;border:0;padding:2px;cursor:pointer;font-size:13px;color:#374151;border-radius:6px}
.dips-switch:focus-visible,.dips-btn:focus-visible,.dips-info:focus-visible{outline:2px solid var(--dips-acc);outline-offset:2px}
.dips-track{position:relative;width:40px;height:22px;border-radius:999px;background:#d1d5db;transition:background .15s}
.dips-thumb{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
.dips-switch[aria-checked=true] .dips-track{background:var(--dips-acc)}
.dips-switch[aria-checked=true] .dips-thumb{left:21px}
@keyframes dipsPulse{0%{box-shadow:0 0 0 0 rgba(22,163,74,.45)}70%{box-shadow:0 0 0 7px rgba(22,163,74,0)}100%{box-shadow:0 0 0 0 rgba(22,163,74,0)}}
.dips-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:24px}
.dips-kpi{min-height:112px;padding:16px 20px}
.dips-kpi-head{display:flex;justify-content:space-between;align-items:center;font-size:13px;color:#6b7280}
.dips-kpi-val{font-size:28px;line-height:34px;font-weight:600;letter-spacing:-.02em;font-variant-numeric:tabular-nums;margin:6px 0 4px;color:#111827}
.dips-kpi-row{display:flex;align-items:center;justify-content:space-between}
.dips-kpi-sub{font-size:13px;color:#4b5563}
.dips-ellipsis{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dips-info{color:#9ca3af;cursor:help;font-size:13px;margin-left:6px}
.dips-card-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px}
.dips-card-title{font-size:15px;font-weight:600;margin:0;color:#111827}
.dips-card-sub{font-size:13px;color:#6b7280;margin:2px 0 0}
.dips-chart{margin-bottom:24px}
.dips-plot{display:flex;gap:8px}
.dips-yaxis{display:flex;flex-direction:column;justify-content:space-between;font-size:13px;color:#6b7280;text-align:right;min-width:44px;height:248px;margin-top:-8px;font-variant-numeric:tabular-nums}
.dips-yaxis span{line-height:16px}
.dips-plot-area{flex:1;min-width:0}
.dips-svg{display:block;width:100%;height:232px;border-bottom:1px solid #e5e7eb}
.dips-xaxis{display:flex;justify-content:space-between;font-size:13px;color:#6b7280;margin-top:8px;font-variant-numeric:tabular-nums}
.dips-legend{display:flex;flex-wrap:wrap;gap:8px 24px;list-style:none;margin:16px 0 0;padding:0}
.dips-legend-item{display:inline-flex;align-items:center;gap:8px;font-size:13px;color:#374151}
.dips-legend-n{color:#6b7280;font-variant-numeric:tabular-nums}
.dips-muted{color:#9ca3af!important}.dips-muted .dips-swatch{opacity:.45}
.dips-swatch{display:inline-block;width:10px;height:10px;border-radius:50%;flex:0 0 10px}
.dips-chart-empty{text-align:center;padding:48px 16px;color:#6b7280}
.dips-chart-empty i,.dips-chart-empty svg{font-size:28px;color:#9ca3af;margin-bottom:10px}
.dips-chart-empty-title{font-size:15px;font-weight:600;color:#374151;margin-bottom:4px}
.dips-chart-empty-text{font-size:13px;max-width:520px;margin:0 auto}
.dips-duo{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px;margin-bottom:24px}
.dips-share-body{display:flex;align-items:center;gap:32px}
.dips-donut{position:relative;flex:0 0 180px;width:180px;height:180px}
.dips-donut svg{width:180px;height:180px}
.dips-donut-center{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
.dips-donut-n{font-size:22px;font-weight:600;font-variant-numeric:tabular-nums}
.dips-donut-l{font-size:13px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em}
.dips-share-list{list-style:none;margin:0;padding:0;flex:1;min-width:0}
.dips-share-row{display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px solid #f1f2f4;font-size:13px;color:#111827}
.dips-share-name{display:inline-flex;align-items:center;gap:8px}
.dips-share-val{font-variant-numeric:tabular-nums;white-space:nowrap}
.dips-pct{color:#6b7280}
.dips-bars{list-style:none;margin:0;padding:0}
.dips-bar{margin-bottom:14px}
.dips-bar-top{display:flex;justify-content:space-between;gap:12px;font-size:13px;margin-bottom:6px}
.dips-bar-name{color:#111827;font-weight:500;min-width:0}
.dips-bar-help{color:#6b7280;font-weight:400;margin-left:8px}
.dips-bar-val{white-space:nowrap;font-variant-numeric:tabular-nums}
.dips-bar-5{color:#6b7280;margin-left:10px}
.dips-bar-track{height:6px;background:#eef0f2;border-radius:999px;overflow:hidden}
.dips-bar-track span{display:block;height:100%;border-radius:999px}
.dips-top{margin-bottom:8px}
.dips-table{width:100%;border-collapse:collapse}
.dips-table th{font-size:13px;font-weight:600;color:#374151;text-align:left;padding:10px 12px;border-bottom:1px solid #e5e7eb}
.dips-table td{padding:10px 12px;border-bottom:1px solid #f1f2f4;font-size:13px;color:#111827;height:48px}
.dips-table tr:last-child td{border-bottom:0}
.dips-r,.dips-table th.dips-r{text-align:right}
.dips-rank{color:#6b7280;font-variant-numeric:tabular-nums}
.dips-ip{font-family:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;background:#f3f4f6;border-radius:4px;padding:3px 8px}
.dips-cat{display:inline-flex;align-items:center;gap:6px;border:1px solid #e5e7eb;border-radius:999px;padding:3px 10px;font-size:13px;margin:2px 4px 2px 0;white-space:nowrap}
.dips-mini{display:inline-block;vertical-align:middle;width:110px;height:6px;background:#eef0f2;border-radius:999px;overflow:hidden;margin-right:14px}
.dips-mini span{display:block;height:100%;background:var(--dips-acc);border-radius:999px}
.dips-count{display:inline-block;min-width:60px;font-variant-numeric:tabular-nums;font-weight:500}
.dips-state{display:flex;gap:20px;align-items:flex-start;padding:28px}
.dips-state-ico{flex:0 0 48px;height:48px;border-radius:12px;background:#f3f4f6;color:#6b7280;display:flex;align-items:center;justify-content:center;font-size:22px}
.dips-state-text{font-size:14px;line-height:21px;color:#374151;margin:6px 0 14px;max-width:720px}
.dips-state-detail{font-size:13px;color:#7f1d1d;font-family:ui-monospace,Menlo,monospace;margin:-6px 0 14px}
.dips-state-err{background:#fef2f2;border-color:#fecaca}
.dips-state-err .dips-state-ico{background:#fee2e2;color:#dc2626}
.dips-state-err .dips-card-title{color:#991b1b}
.dips-btn{display:inline-flex;align-items:center;gap:8px;background:var(--dips-acc);color:#fff;border:0;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer}
.dips-btn:hover{background:var(--dips-acc-h)}
.dips-btn-err{background:#dc2626}.dips-btn-err:hover{background:#b91c1c}
@media (max-width:1200px){.dips-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.dips-duo{grid-template-columns:1fr}.dips-banner{flex-wrap:wrap}}
@media (prefers-reduced-motion:reduce){.dips-dot{animation:none}.dips-track,.dips-thumb{transition:none}}
</style>";
}
