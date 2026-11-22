<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["page2"])){page2();exit;}
if(isset($_GET["report"])){report();exit;}
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{rlg_title}",ico_database,"{rlg_explain}",
        "$page?page2=yes","proxy-legal-list","progress-logrotate-restart",false,"table-loader-proxy-list");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{legal_logs}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
/* ─────────────────────────────────────────────────────────────────────
 * Legal logs storage (maquette stitch_legal_logs_storage_console) — prefix rlg_.
 * page2() renders the whole content once: value banner, capacity forecast
 * (articarest /logs/capacity), coverage strip and the archives list
 * (proxy_search.db proxy_time + proxy_reports). One widget = one function,
 * all fed by rlg_model(). The list is filtered, sorted and paged in the
 * browser (one archive per day and per source: a few hundred rows).
 * ---------------------------------------------------------------------*/


function rlg_esc($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8"));
}

function rlg_t($tpl,$token,$args=array()):string{
    $s=$tpl->_ENGINE_parse_body("{".$token."}");
    foreach($args as $a){
        $p=strpos($s,"%s");
        if($p===false){break;}
        $s=substr($s,0,$p).$a.substr($s,$p+2);
    }
    return $s;
}

function rlg_accent():string{
    $accent="#005447";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
    }
    return $accent;
}

function rlg_bytes($b):string{
    // FormatBytes() attend des KILO-octets.
    return FormatBytes(floatval($b)/1024,true);
}

function rlg_num($n):string{
    return number_format(intval($n),0,".",",");
}

function rlg_ago($tpl,$ts):string{
    $d=time()-intval($ts);
    if($d<60){return rlg_t($tpl,"rlg_ago_now");}
    if($d<3600){return rlg_t($tpl,"rlg_ago_min",array(intval($d/60)));}
    if($d<86400){return rlg_t($tpl,"rlg_ago_h",array(intval($d/3600)));}
    return rlg_t($tpl,"rlg_ago_d",array(intval($d/86400)));
}

function rlg_span($tpl,$from,$to):string{
    $d=intval($to)-intval($from);
    if($d<=0){return "";}
    if($d<3600){return rlg_t($tpl,"rlg_span_min",array(max(1,intval(round($d/60)))));}
    // Une archive quotidienne couvre 23 h 59 : arrondie a un jour.
    if($d<82800){return rlg_t($tpl,"rlg_span_h",array(intval(round($d/3600))));}
    $days=intval(round($d/86400));
    if($days<=1){return rlg_t($tpl,"rlg_span_1d");}
    return rlg_t($tpl,"rlg_span_d",array($days));
}

function rlg_settings():array{
    $maxDays=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDays"));
    // Memes valeurs par defaut que le demon (logrotate.LoadSettings / Capacity).
    if($maxDays<=0){$maxDays=365;}
    $h=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotateH"));
    $m=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotateM"));
    if(strlen($h)<2){$h="00";}
    if(strlen($m)<2){$m="05";}
    return array("max_days"=>$maxDays,"rotate_at"=>"$h:$m");
}

function rlg_capacity():array{
    $j=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/logs/capacity"),true);
    if(!is_array($j) || empty($j["Status"]) || !isset($j["Data"]) || !is_array($j["Data"])){
        return array("ok"=>false);
    }
    $c=$j["Data"];
    $c["ok"]=true;
    return $c;
}

/* Jours couverts par au moins une archive, du plus ancien jour a HIER : le
 * jour courant n'a pas encore d'archive avant la prochaine rotation. */
function rlg_coverage(array $rows):array{
    $out=array("days"=>array(),"first"=>"","last"=>"","present"=>0,"missing"=>array());
    if(count($rows)==0){return $out;}
    $covered=array();
    $minTs=0;
    foreach($rows as $r){
        $from=intval($r["zdate"]); $to=intval($r["zDateTo"]);
        if($from<=0){continue;}
        // Une archive quotidienne finit a 00:04 le lendemain : ces quelques
        // minutes ne font pas une journee archivee.
        $to=$to-3600;
        if($to<$from){$to=$from;}
        if($minTs==0 || $from<$minTs){$minTs=$from;}
        $d=strtotime(date("Y-m-d",$from));
        $end=strtotime(date("Y-m-d",$to));
        $guard=0;
        while($d<=$end && $guard<400){
            $covered[date("Y-m-d",$d)]=true;
            $d=strtotime("+1 day",$d);
            $guard++;
        }
    }
    if($minTs==0){return $out;}
    $yesterday=strtotime(date("Y-m-d",strtotime("-1 day")));
    $start=strtotime(date("Y-m-d",$minTs));
    $floor=strtotime("-".(400-1)." days",$yesterday);
    if($start<$floor){$start=$floor;}
    if($start>$yesterday){return $out;}
    for($d=$start;$d<=$yesterday;$d=strtotime("+1 day",$d)){
        $k=date("Y-m-d",$d);
        $ok=isset($covered[$k]);
        $out["days"][]=array($k,$ok);
        if($ok){$out["present"]++;}else{$out["missing"][]=$k;}
    }
    $out["first"]=date("Y-m-d",$start);
    $out["last"]=date("Y-m-d",$yesterday);
    return $out;
}

function rlg_model():array{
    $m=array("ok"=>true,"error"=>"","rows"=>array(),"total"=>0,"oldest"=>0,
        "counts"=>array("all"=>0,"0"=>0,"1"=>0,"2"=>0),"reports"=>array());
    $q=new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
    $rows=$q->QUERY_SQL("SELECT zmd5,sourcefile,ztype,zdate,zDateTo,filesize FROM proxy_time ORDER BY zdate DESC");
    if(!$q->ok){$m["ok"]=false;$m["error"]=$q->mysql_error;}
    if(!is_array($rows)){$rows=array();}
    $clean=array();
    foreach($rows as $r){
        if(!is_array($r)){continue;}
        $t=intval($r["ztype"]);
        $clean[]=$r;
        $m["counts"]["all"]++;
        if(isset($m["counts"][(string)$t])){$m["counts"][(string)$t]++;}
        $z=intval($r["zdate"]);
        if($z>0 && ($m["oldest"]==0 || $z<$m["oldest"])){$m["oldest"]=$z;}
    }
    $m["total"]=count($clean);
    $m["coverage"]=rlg_coverage($clean);
    $m["rows"]=array_slice($clean,0,1200);
    // Etat des rapports en UNE requete, sans charger leur HTML ; "NONE" = echec.
    $rep=$q->QUERY_SQL("SELECT zmd5, CASE WHEN report='NONE' THEN 1 ELSE 0 END AS failed FROM proxy_reports");
    if(is_array($rep)){
        foreach($rep as $r){
            if(!is_array($r)){continue;}
            $m["reports"][(string)$r["zmd5"]]=intval($r["failed"])==1?"failed":"ok";
        }
    }
    $m["capacity"]=rlg_capacity();
    $m["settings"]=rlg_settings();
    return $m;
}

/* ── Widgets ────────────────────────────────────────────────────────── */

function rlg_banner($tpl,array $m):string{
    $s=$m["settings"];
    $cov=$m["coverage"];
    $chips=array();
    if($m["oldest"]>0){
        $days=count($cov["days"]);
        $chips[]="<span class='rlg-chip'><i class='fas fa-calendar-check'></i>"
            .rlg_t($tpl,"rlg_chip_back",array(date("Y-m-d",$m["oldest"]),rlg_num(max(1,$days))))."</span>";
    }else{
        $chips[]="<span class='rlg-chip'><i class='fas fa-calendar-check'></i>{rlg_chip_none}</span>";
    }
    $chips[]="<span class='rlg-chip'><i class='fas fa-clock-rotate-left'></i>".rlg_t($tpl,"rlg_chip_kept",array(rlg_num($s["max_days"])))."</span>";
    $chips[]="<span class='rlg-chip'><i class='fas fa-lock'></i>{rlg_chip_readonly}</span>";
    $chips[]="<span class='rlg-chip'><i class='fas fa-clock'></i>".rlg_t($tpl,"rlg_chip_rotate",array(rlg_esc($s["rotate_at"])))."</span>";
    return "<div class='rlg-card rlg-banner'>"
        ."<div class='rlg-bico' aria-hidden='true'><i class='fas fa-scale-balanced'></i></div>"
        ."<div class='rlg-btxt'><h2 class='rlg-btitle'>{rlg_banner_title}</h2>"
        ."<p class='rlg-bdesc'>{rlg_banner_text}</p>"
        ."<div class='rlg-chips'>".implode("",$chips)."</div></div></div>";
}

function rlg_disk_bar($tpl,array $c):string{
    $total=floatval(isset($c["fs_total_bytes"])?$c["fs_total_bytes"]:0);
    $free=floatval(isset($c["fs_free_bytes"])?$c["fs_free_bytes"]:0);
    $arch=floatval(isset($c["archive_bytes"])?$c["archive_bytes"]:0);
    $dir=isset($c["dir"])?(string)$c["dir"]:"";
    if($total<=0){
        return "<div class='rlg-sub'>{rlg_disk_unknown}</div>";
    }
    $other=$total-$free-$arch;
    if($other<0){$other=0;}
    $pa=round($arch*100/$total,1); $po=round($other*100/$total,1); $pf=max(0,round(100-$pa-$po,1));
    $la=rlg_t($tpl,"rlg_leg_archives",array(rlg_bytes($arch),intval(round($pa))));
    $lo=rlg_t($tpl,"rlg_leg_other",array(rlg_bytes($other),intval(round($po))));
    $lf=rlg_t($tpl,"rlg_leg_free",array(rlg_bytes($free),intval(round($pf))));
    $aria=strip_tags("$la, $lo, $lf");
    return "<div class='rlg-cap-label'>".rlg_t($tpl,"rlg_disk_label",array("<span class='rlg-mono'>".rlg_esc($dir)."</span>"))."</div>"
        ."<div class='rlg-bar' role='img' aria-label='".rlg_esc(html_entity_decode($aria,ENT_QUOTES,"UTF-8"))."'>"
        ."<div class='rlg-bar-a' style='width:$pa%' title='".rlg_esc(strip_tags($la))."'></div>"
        ."<div class='rlg-bar-o' style='width:$po%' title='".rlg_esc(strip_tags($lo))."'></div>"
        ."<div class='rlg-bar-f' style='width:$pf%' title='".rlg_esc(strip_tags($lf))."'></div></div>"
        ."<div class='rlg-legend'><span><i class='rlg-sw rlg-sw-a'></i>$la</span><span><i class='rlg-sw rlg-sw-o'></i>$lo</span>"
        ."<span><i class='rlg-sw rlg-sw-f'></i>$lf</span><span class='rlg-total'>".rlg_t($tpl,"rlg_leg_total",array(rlg_bytes($total)))."</span></div>";
}

function rlg_daily_volume($tpl,array $c):string{
    $min=floatval(isset($c["rate_min_mb_per_day"])?$c["rate_min_mb_per_day"]:0);
    $max=floatval(isset($c["rate_max_mb_per_day"])?$c["rate_max_mb_per_day"]:0);
    $avg=floatval(isset($c["rate_avg_mb_per_day"])?$c["rate_avg_mb_per_day"]:0);
    $win=intval(isset($c["rate_window_days"])?$c["rate_window_days"]:0);
    if($max<=0){return "";}
    $txt=rlg_t($tpl,"rlg_daily",array(rlg_bytes($min*1048576),rlg_bytes($max*1048576),rlg_bytes($avg*1048576)));
    $sub=$win>0?" <span class='rlg-sub'>".rlg_t($tpl,"rlg_daily_window",array($win))."</span>":"";
    return "<div class='rlg-daily'><i class='fas fa-chart-simple' aria-hidden='true'></i><span>$txt$sub</span></div>";
}

function rlg_verdict($tpl,array $c,array $s):string{
    $errs=isset($c["errors"]) && is_array($c["errors"])?$c["errors"]:array();
    $total=floatval(isset($c["fs_total_bytes"])?$c["fs_total_bytes"]:0);
    if($total<=0){
        $e=count($errs)>0?"<div class='rlg-vtxt'>".rlg_esc($errs[0])."</div>":"";
        return "<div class='rlg-verdict rlg-v-na'><span class='rlg-vico'><i class='fas fa-circle-info'></i></span><div><div class='rlg-vtitle'>{rlg_v_na_title}</div>$e</div></div>";
    }
    $maxDays=intval(isset($c["max_days"])&&intval($c["max_days"])>0?$c["max_days"]:$s["max_days"]);
    $req=sprintf("%.1f GB",floatval(isset($c["required_gb"])?$c["required_gb"]:0));
    $free=rlg_bytes(isset($c["fs_free_bytes"])?$c["fs_free_bytes"]:0);
    if(!empty($c["sufficient"])){
        return "<div class='rlg-verdict rlg-v-ok'><span class='rlg-vico'><i class='fas fa-circle-check'></i></span><div>"
            ."<div class='rlg-vtitle'>".rlg_t($tpl,"rlg_v_ok_title",array(rlg_num($maxDays)))."</div>"
            ."<div class='rlg-vtxt'>".rlg_t($tpl,"rlg_v_ok_text",array($req,$free))."</div></div></div>";
    }
    $sat=isset($c["saturation_date"])?trim((string)$c["saturation_date"]):"";
    $inDays=intval(isset($c["days_to_saturation"])?$c["days_to_saturation"]:0);
    if($sat!==""){
        $title=rlg_t($tpl,"rlg_v_warn_title",array(rlg_esc($sat)));
        $text=rlg_t($tpl,"rlg_v_warn_text",array(rlg_num($inDays),rlg_num($maxDays)));
    }else{
        $title=rlg_t($tpl,"rlg_v_short_title",array(rlg_num($maxDays)));
        $text=rlg_t($tpl,"rlg_v_ok_text",array($req,$free));
    }
    return "<div class='rlg-verdict rlg-v-warn' role='alert'><span class='rlg-vico'><i class='fas fa-triangle-exclamation'></i></span><div>"
        ."<div class='rlg-vtitle'>$title</div><div class='rlg-vtxt'>$text</div><div class='rlg-vtxt'>{rlg_v_warn_help}</div></div></div>";
}

function rlg_capacity_card($tpl,array $m):string{
    $c=$m["capacity"];
    $s=$m["settings"];
    if(empty($c["ok"])){
        return "<div class='rlg-card rlg-pad'><h3 class='rlg-h3'>{rlg_cap_title}</h3>"
            ."<div class='rlg-verdict rlg-v-na'><span class='rlg-vico'><i class='fas fa-circle-info'></i></span><div><div class='rlg-vtitle'>{rlg_v_na_title}</div>"
            ."<div class='rlg-vtxt'>{rlg_v_na_text}</div></div></div></div>";
    }
    $upd="";
    $ev=intval(isset($c["evaluated_at"])?$c["evaluated_at"]:0);
    if($ev>0){
        $upd="<div class='rlg-upd' title='".date("Y-m-d H:i:s",$ev)."'><i class='fas fa-rotate' aria-hidden='true'></i>".rlg_t($tpl,"rlg_updated",array(rlg_ago($tpl,$ev)))."</div>";
    }
    return "<div class='rlg-card rlg-pad'><h3 class='rlg-h3'>{rlg_cap_title}</h3>"
        ."<p class='rlg-cexpl'>{rlg_cap_explain}</p>"
        ."<div class='rlg-cap'><div class='rlg-cap-l'>".rlg_disk_bar($tpl,$c).rlg_daily_volume($tpl,$c)."</div>"
        ."<div class='rlg-cap-r'>".rlg_verdict($tpl,$c,$s).$upd."</div></div></div>";
}

function rlg_coverage_card($tpl,array $m):string{
    $cov=$m["coverage"];
    $n=count($cov["days"]);
    if($n==0){return "";}
    $miss=count($cov["missing"]);
    if($miss==0){
        $badge="<span class='rlg-badge rlg-badge-ok'><i class='fas fa-circle-check'></i>".rlg_t($tpl,"rlg_cov_full",array(rlg_num($n)))."</span>";
    }else{
        $badge="<span class='rlg-badge rlg-badge-warn'><i class='fas fa-triangle-exclamation'></i>".rlg_t($tpl,"rlg_cov_gaps",array(rlg_num($cov["present"]),rlg_num($n),rlg_num($miss)))."</span>";
    }
    // Segments contigus : quelques noeuds au lieu d'un par jour.
    $segs=array();
    $runStart=0; $runOk=$cov["days"][0][1];
    for($i=1;$i<=$n;$i++){
        if($i<$n && $cov["days"][$i][1]===$runOk){continue;}
        $len=$i-$runStart;
        $a=$cov["days"][$runStart][0]; $b=$cov["days"][$i-1][0];
        $range=$a==$b?$a:"$a &rarr; $b";
        if($runOk){
            $tip=rlg_t($tpl,"rlg_cov_seg_ok",array($range));
            $segs[]="<div class='rlg-cseg rlg-cseg-ok' style='flex-grow:$len' title='".strip_tags($tip)."'></div>";
        }else{
            $tip=rlg_t($tpl,"rlg_cov_seg_gap",array($range));
            $segs[]="<div class='rlg-cseg rlg-cseg-gap' style='flex-grow:$len' title='".strip_tags($tip)."'></div>";
        }
        if($i<$n){$runStart=$i;$runOk=$cov["days"][$i][1];}
    }
    $gapTxt="";
    if($miss>0){
        $list=array_slice($cov["missing"],0,6);
        $more=$miss>6?" ".rlg_t($tpl,"rlg_cov_more",array($miss-6)):"";
        $gapTxt="<span class='rlg-gaps'><i class='rlg-sw rlg-sw-gap'></i>".rlg_t($tpl,"rlg_cov_gap_list",array(implode(" &middot; ",$list).$more))."</span>";
    }
    return "<div class='rlg-card rlg-pad'><div class='rlg-cov-head'><h3 class='rlg-h3'>{rlg_cov_title}</h3>$badge</div>"
        ."<div class='rlg-strip' role='img' aria-label='".strip_tags(rlg_t($tpl,"rlg_cov_gaps",array($cov["present"],$n,$miss)))."'>".implode("",$segs)."</div>"
        ."<div class='rlg-cov-legend'><span class='rlg-mono'>{$cov["first"]}</span>$gapTxt<span class='rlg-mono'>".rlg_t($tpl,"rlg_cov_yesterday",array($cov["last"]))."</span></div>"
        ."<div class='rlg-sub'><i class='fas fa-circle-info' aria-hidden='true'></i> {rlg_cov_explain}</div></div>";
}

function rlg_source($t):string{
    if($t==0){return "<span class='rlg-src'><i class='fas fa-server' aria-hidden='true'></i>{rlg_src_proxy}</span>";}
    if($t==1){return "<span class='rlg-src'><i class='fas fa-shield-halved' aria-hidden='true'></i>{rlg_src_forti}</span>";}
    if($t==2){return "<span class='rlg-src'><i class='fas fa-globe' aria-hidden='true'></i>{rlg_src_reverse}</span>";}
    return "<span class='rlg-none'>&mdash;</span>";
}

function rlg_report_cell($zmd5,$state,$name):string{
    $page=CurrentPageName();
    if($state=="ok"){
        return "<button type='button' class='rlg-btn-out' OnClick=\"s_PopUp('$page?report=$zmd5','1024','900');\" title='{rlg_report_tip}' aria-label='{rlg_open_report}: $name'><i class='fas fa-chart-column' aria-hidden='true'></i> {rlg_open_report}</button>";
    }
    if($state=="failed"){
        return "<span class='rlg-rfail' title='{rlg_report_failed_tip}'><i class='fas fa-circle-exclamation' aria-hidden='true'></i>{rlg_report_failed}</span>";
    }
    return "<span class='rlg-none' title='{rlg_report_none_tip}'>&mdash;</span>";
}

function rlg_row($tpl,array $r,array $reports):string{
    $zmd5=preg_replace('/[^a-f0-9]/','',(string)$r["zmd5"]);
    $path=(string)$r["sourcefile"];
    $name=rlg_esc(basename($path));
    $from=intval($r["zdate"]); $to=intval($r["zDateTo"]);
    $t=intval($r["ztype"]);
    $size=floatval($r["filesize"]);
    $fromTxt=$from>0?date("Y-m-d H:i",$from):"&mdash;";
    $toTxt=$to>0?(date("Y-m-d",$to)==date("Y-m-d",$from)?date("H:i",$to):date("Y-m-d H:i",$to)):"&mdash;";
    $span=rlg_span($tpl,$from,$to);
    $key=strtolower(basename($path))." ".($from>0?date("Y-m-d",$from):"")." ".($to>0?date("Y-m-d",$to):"");
    $state=isset($reports[$zmd5])?$reports[$zmd5]:"";
    return "<tr data-src='$t' data-key='".rlg_esc($key)."' data-ts='$from' data-size='".sprintf("%.0f",$size)."'>"
        ."<td><div class='rlg-file'><span class='rlg-fico'><i class='fas fa-file-zipper' aria-hidden='true'></i></span>"
        ."<span class='rlg-fname' title='".rlg_esc($path)."'>$name</span></div></td>"
        ."<td>".rlg_source($t)."</td>"
        ."<td class='rlg-nowrap'><span class='rlg-mono'>$fromTxt &rarr; $toTxt</span>".($span!==""?" <span class='rlg-sub'>$span</span>":"")."</td>"
        ."<td class='rlg-nowrap rlg-mono rlg-right'>".rlg_bytes($size)."</td>"
        ."<td class='rlg-right'>".rlg_report_cell($zmd5,$state,$name)."</td></tr>";
}

function rlg_list_toolbar(array $m):string{
    $c=$m["counts"];
    $segs=array("all"=>"{rlg_seg_all}","0"=>"{rlg_src_proxy}","1"=>"{rlg_src_forti}","2"=>"{rlg_src_reverse}");
    $b=array();
    foreach($segs as $k=>$label){
        $n=intval($c[(string)$k]);
        if($k!=="all" && $n==0){continue;}
        $sel=$k==="all"?"true":"false";
        $b[]="<button type='button' role='tab' class='rlg-seg' data-seg='$k' aria-selected='$sel' OnClick=\"RlgSeg('$k')\">$label <span class='rlg-segn'>".rlg_num($n)."</span></button>";
    }
    return "<div class='rlg-tb'>"
        ."<div class='rlg-find'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' id='rlg-find' placeholder='{rlg_find}' aria-label='{rlg_find}' OnInput='RlgApply(1)' autocomplete='off'></div>"
        ."<div class='rlg-segs' role='tablist' aria-label='{rlg_col_source}'>".implode("",$b)."</div>"
        ."<label class='rlg-sort'><span>{rlg_sort}</span><select id='rlg-sort' OnChange='RlgSort()'>"
        ."<option value='new'>{rlg_sort_new}</option><option value='old'>{rlg_sort_old}</option>"
        ."<option value='big'>{rlg_sort_big}</option><option value='small'>{rlg_sort_small}</option></select></label></div>";
}

function rlg_empty():string{
    return "<div class='rlg-empty'><div class='rlg-eico'><i class='fas fa-box-archive' aria-hidden='true'></i></div>"
        ."<h3>{rlg_empty_title}</h3><p>{rlg_empty_text}</p></div>";
}

function rlg_list($tpl,array $m):string{
    $html=array();
    $html[]="<div class='rlg-card rlg-list'>";
    if(!$m["ok"]){
        $html[]="<div class='rlg-err' role='alert'><i class='fas fa-triangle-exclamation'></i> {rlg_db_error} <span class='rlg-mono'>".rlg_esc($m["error"])."</span></div>";
    }
    if($m["total"]==0){
        $html[]=rlg_empty();
        $html[]="</div>";
        return implode("\n",$html);
    }
    $html[]=rlg_list_toolbar($m);
    $html[]="<div class='rlg-scroll'><table class='rlg-tbl' id='rlg-tbl'><thead><tr>"
        ."<th>{rlg_col_archive}</th><th>{rlg_col_source}</th><th>{rlg_col_covers}</th><th class='rlg-right'>{rlg_col_size}</th><th class='rlg-right'>{rlg_col_report}</th>"
        ."</tr></thead><tbody id='rlg-rows'>";
    foreach($m["rows"] as $r){
        $html[]=rlg_row($tpl,$r,$m["reports"]);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='rlg-nomatch' id='rlg-nomatch' style='display:none'><span id='rlg-nomatch-txt'></span> <button type='button' class='rlg-link' OnClick='RlgClear()'>{rlg_clear}</button></div>";
    $foot="";
    if($m["total"]>count($m["rows"])){
        $foot="<span class='rlg-sub'>".rlg_t($tpl,"rlg_capped",array(rlg_num(count($m["rows"])),rlg_num($m["total"])))."</span>";
    }
    $html[]="<div class='rlg-foot'><span id='rlg-showing'></span>$foot<span class='rlg-pager' id='rlg-pager'></span></div>";
    $html[]="</div>";
    return implode("\n",$html);
}

function page2(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $m=rlg_model();

    $html=array();
    $html[]="<div class='rlg'>";
    $html[]=rlg_banner($tpl,$m);
    $html[]=rlg_capacity_card($tpl,$m);
    $html[]=rlg_coverage_card($tpl,$m);
    $html[]=rlg_list($tpl,$m);
    $html[]="</div>";

    $reload="LoadAjax('table-loader-proxy-list','$page?page2=yes')";
    $topbuttons=array();
    if(isRights()){
        $scan=$tpl->framework_buildjs("/logrotate/scan/storage","logrotate.scan","logrotate.log",
            "progress-logrotate-restart",$reload,null,null,"AsSquidAdministrator","{rlg_btn_refresh}");
        $rebuild=$tpl->framework_buildjs("/logrotate/scan/rebuild","logrotate.scan","logrotate.log",
            "progress-logrotate-restart",$reload,null,"{rlg_rebuild_confirm}","AsSquidAdministrator","{rlg_btn_rebuild}");
        $topbuttons[]=array($scan,ico_refresh,"{rlg_btn_refresh}");
        $topbuttons[]=array($rebuild,ico_retweet,"{rlg_btn_rebuild}");
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{rlg_title}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{rlg_explain}";
    $TINY_ARRAY["URL"]="proxy-legal-list";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo rlg_style().$tpl->_ENGINE_parse_body(implode("\n",$html))
        ."<script>NoSpinner();\n".rlg_js($tpl)."\n$tiny</script>";
}

function rlg_js($tpl):string{
    $nomatch=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{rlg_no_match}"),ENT_QUOTES,"UTF-8"));
    $showing=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{rlg_showing}"),ENT_QUOTES,"UTF-8"));
    $prev=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{rlg_prev}"),ENT_QUOTES,"UTF-8"));
    $next=json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{rlg_next}"),ENT_QUOTES,"UTF-8"));
    return "
window.RlgState={seg:'all',page:1,per:50};
function RlgRows(){ var tb=document.getElementById('rlg-rows'); return tb?Array.prototype.slice.call(tb.rows):[]; }
function RlgSeg(k){
    RlgState.seg=k;
    var b=document.querySelectorAll('.rlg-seg');
    for(var i=0;i<b.length;i++){ b[i].setAttribute('aria-selected', b[i].getAttribute('data-seg')===k?'true':'false'); }
    RlgApply(1);
}
function RlgSort(){
    var tb=document.getElementById('rlg-rows'); if(!tb){return;}
    var v=document.getElementById('rlg-sort').value;
    var rows=RlgRows();
    rows.sort(function(a,b){
        var ta=+a.getAttribute('data-ts'), tb2=+b.getAttribute('data-ts');
        var sa=+a.getAttribute('data-size'), sb=+b.getAttribute('data-size');
        if(v==='old'){ return ta-tb2; }
        if(v==='big'){ return sb-sa; }
        if(v==='small'){ return sa-sb; }
        return tb2-ta;
    });
    for(var i=0;i<rows.length;i++){ tb.appendChild(rows[i]); }
    RlgApply(1);
}
function RlgApply(p){
    if(p){ RlgState.page=p; }
    var inp=document.getElementById('rlg-find');
    var q=inp?inp.value.trim().toLowerCase():'';
    var rows=RlgRows(), match=[];
    for(var i=0;i<rows.length;i++){
        var r=rows[i];
        var ok=(RlgState.seg==='all' || r.getAttribute('data-src')===RlgState.seg) && (q==='' || (r.getAttribute('data-key')||'').indexOf(q)>-1);
        r.style.display='none';
        if(ok){ match.push(r); }
    }
    var pages=Math.max(1,Math.ceil(match.length/RlgState.per));
    if(RlgState.page>pages){ RlgState.page=pages; }
    var a=(RlgState.page-1)*RlgState.per, b=Math.min(match.length,a+RlgState.per);
    for(var j=a;j<b;j++){ match[j].style.display=''; }
    var nm=document.getElementById('rlg-nomatch');
    if(nm){ nm.style.display=(rows.length>0 && match.length===0)?'':'none';
        var t=document.getElementById('rlg-nomatch-txt'); if(t){ t.textContent=$nomatch.replace('%s', q!==''?'“'+q+'”':''); } }
    var sh=document.getElementById('rlg-showing');
    if(sh){ sh.textContent=match.length? $showing.replace('%s',(a+1)+'–'+b).replace('%s',match.length) : ''; }
    RlgPager(pages);
}
function RlgPager(pages){
    var pg=document.getElementById('rlg-pager'); if(!pg){return;}
    while(pg.firstChild){ pg.removeChild(pg.firstChild); }
    if(pages<2){ return; }
    var cur=RlgState.page;
    function btn(label,p,dis,act){
        var e=document.createElement('button'); e.type='button'; e.className='rlg-pg'+(act?' rlg-pg-on':'');
        e.textContent=label; if(dis){ e.disabled=true; } else { e.onclick=function(){ RlgApply(p); }; }
        if(act){ e.setAttribute('aria-current','page'); }
        pg.appendChild(e);
    }
    btn($prev,cur-1,cur<=1,false);
    var list=[1], k;
    for(k=cur-1;k<=cur+1;k++){ if(k>1 && k<pages){ list.push(k); } }
    list.push(pages);
    var last=0;
    for(k=0;k<list.length;k++){
        if(list[k]-last>1){ var s=document.createElement('span'); s.className='rlg-pg-gap'; s.textContent='…'; pg.appendChild(s); }
        btn(String(list[k]),list[k],false,list[k]===cur); last=list[k];
    }
    btn($next,cur+1,cur>=pages,false);
}
function RlgClear(){ var inp=document.getElementById('rlg-find'); if(inp){ inp.value=''; } RlgSeg('all'); }
RlgApply(1);
";
}

function rlg_style():string{
    $a=rlg_accent();
    return "<style>
.rlg{display:flex;flex-direction:column;gap:16px;margin-top:10px;color:#111827;font-size:14px}
.rlg-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.05);min-width:0}
.rlg-pad{padding:20px 24px}
.rlg-banner{display:flex;gap:18px;align-items:flex-start;padding:20px 24px;border-left:4px solid $a}
.rlg-bico{flex:0 0 48px;height:48px;border-radius:12px;background:#e6eeed;color:$a;display:flex;align-items:center;justify-content:center;font-size:20px}
.rlg-btxt{min-width:0}
.rlg-btitle{margin:0 0 6px 0;font-size:18px;font-weight:600;color:#111827;line-height:1.3}
.rlg-bdesc{margin:0;color:#374151;font-size:14px;line-height:1.55;max-width:1000px}
.rlg-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.rlg-chip{display:inline-flex;align-items:center;gap:7px;background:#f2f6f6;border:1px solid #e1ebe9;color:#1f2937;border-radius:999px;padding:4px 12px;font-size:13px;white-space:nowrap}
.rlg-chip i,.rlg-chip svg{color:$a;font-size:13px}
.rlg-h3{margin:0;font-size:15px;font-weight:600;color:#111827}
.rlg-cexpl{margin:4px 0 14px;color:#4b5563;font-size:13px;line-height:1.5}
.rlg-cap{display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:24px}
.rlg-cap-r{border-left:1px solid #e5e7eb;padding-left:24px;display:flex;flex-direction:column;gap:10px}
.rlg-cap-label{font-size:13px;color:#4b5563;margin-bottom:8px}
.rlg-mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px}
.rlg-bar{display:flex;height:14px;border-radius:999px;overflow:hidden;background:#e5e7eb;border:1px solid #e5e7eb}
.rlg-bar-a{background:$a}
.rlg-bar-o{background:#9ca3af}
.rlg-bar-f{background:#e5e7eb}
.rlg-legend{display:flex;flex-wrap:wrap;gap:6px 18px;margin-top:10px;font-size:13px;color:#374151;align-items:center}
.rlg-legend>span{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.rlg-total{margin-left:auto;color:#6b7280}
.rlg-sw{display:inline-block;width:10px;height:10px;border-radius:3px}
.rlg-sw-a{background:$a}
.rlg-sw-o{background:#9ca3af}
.rlg-sw-f{background:#e5e7eb;border:1px solid #d1d5db}
.rlg-sw-gap{background:#f87171}
.rlg-daily{display:flex;gap:8px;align-items:baseline;margin-top:16px;padding-top:12px;border-top:1px solid #f1f3f5;font-size:13px;color:#374151}
.rlg-daily>i,.rlg-daily>svg{color:#6b7280}
.rlg-sub{font-size:13px;color:#6b7280}
.rlg-verdict{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:10px;border:1px solid}
.rlg-vico{font-size:20px;line-height:1;margin-top:2px}
.rlg-vtitle{font-size:15px;font-weight:600}
.rlg-vtxt{font-size:13px;line-height:1.5;margin-top:4px}
.rlg-v-ok{background:#f0fdf4;border-color:#bbf7d0;color:#14532d}
.rlg-v-ok .rlg-vico{color:#16a34a}
.rlg-v-warn{background:#fffbeb;border-color:#fde68a;color:#78350f}
.rlg-v-warn .rlg-vico{color:#d97706}
.rlg-v-na{background:#f9fafb;border-color:#e5e7eb;color:#374151}
.rlg-v-na .rlg-vico{color:#6b7280}
.rlg-upd{font-size:13px;color:#6b7280;display:flex;gap:6px;align-items:center}
.rlg-cov-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:12px}
.rlg-badge{display:inline-flex;gap:7px;align-items:center;border-radius:999px;padding:4px 12px;font-size:13px;font-weight:500;border:1px solid}
.rlg-badge-ok{background:#f0fdf4;border-color:#bbf7d0;color:#166534}
.rlg-badge-warn{background:#fffbeb;border-color:#fde68a;color:#92400e}
.rlg-strip{display:flex;height:28px;padding:6px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;gap:1px}
.rlg-cseg{flex-basis:0;min-width:2px;border-radius:2px}
.rlg-cseg-ok{background:$a}
.rlg-cseg-gap{background:#fecaca;box-shadow:inset 0 0 0 1px #f87171;min-width:5px}
.rlg-cov-legend{display:flex;justify-content:space-between;gap:12px;margin:8px 0;font-size:13px;color:#374151;flex-wrap:wrap}
.rlg-gaps{display:inline-flex;gap:6px;align-items:center;color:#b45309}
.rlg-list{overflow:hidden}
.rlg-tb{display:flex;align-items:center;gap:16px;padding:12px 16px;border-bottom:1px solid #e5e7eb;flex-wrap:wrap}
.rlg-find{position:relative;flex:0 1 360px;min-width:220px}
.rlg-find>i,.rlg-find>svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#6b7280;font-size:13px}
.rlg-find input{width:100%;height:38px;border:1px solid #d1d5db;border-radius:8px;padding:0 12px 0 34px;font-size:14px;color:#111827;background:#f9fafb}
.rlg-find input:focus,.rlg-sort select:focus{outline:2px solid $a;outline-offset:1px;background:#fff}
.rlg-segs{display:inline-flex;flex-shrink:0;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:9px;padding:3px;gap:2px}
.rlg-seg{white-space:nowrap;border:0;background:transparent;color:#374151;font-size:13px;font-weight:500;padding:6px 12px;border-radius:7px;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.rlg-seg[aria-selected=true]{background:#fff;color:$a;box-shadow:0 1px 2px rgba(0,0,0,.08);font-weight:600}
.rlg-segn{background:#e5e7eb;color:#374151;border-radius:999px;padding:0 7px;font-size:13px;line-height:18px;min-width:18px;text-align:center}
.rlg-seg[aria-selected=true] .rlg-segn{background:#e6eeed;color:$a}
.rlg-sort{margin-left:auto;display:inline-flex;gap:8px;align-items:center;font-size:13px;color:#4b5563;font-weight:400;margin-bottom:0}
.rlg-sort select{height:36px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;font-size:13px;background:#fff;color:#111827}
.rlg-seg:focus-visible,.rlg-btn-out:focus-visible,.rlg-pg:focus-visible,.rlg-link:focus-visible{outline:2px solid $a;outline-offset:2px}
.rlg-scroll{overflow-x:auto}
.rlg-tbl{width:100%;border-collapse:collapse;color:#111827}
.rlg-tbl th{background:#f9fafb;color:#6b7280;font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;text-align:left;padding:10px 16px;border-bottom:1px solid #e5e7eb;white-space:nowrap}
.rlg-tbl td{padding:10px 16px;border-bottom:1px solid #f1f3f5;vertical-align:middle;font-size:14px;color:#111827}
.rlg-tbl tbody tr:hover td{background:#fafbfb}
.rlg-right{text-align:right}
.rlg-tbl th.rlg-right{text-align:right}
.rlg-nowrap{white-space:nowrap}
.rlg-file{display:flex;align-items:center;gap:10px;min-width:0}
.rlg-fico{flex:0 0 32px;height:32px;border-radius:8px;background:#e6eeed;color:$a;display:flex;align-items:center;justify-content:center;font-size:14px}
.rlg-fname{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;font-weight:600;overflow-wrap:anywhere}
.rlg-src{display:inline-flex;gap:7px;align-items:center;white-space:nowrap}
.rlg-src i,.rlg-src svg{color:#6b7280}
.rlg-btn-out{display:inline-flex;gap:7px;align-items:center;border:1px solid $a;color:$a;background:#fff;border-radius:8px;padding:6px 12px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.rlg-btn-out:hover{background:#e6eeed}
.rlg-rfail{display:inline-flex;gap:6px;align-items:center;color:#6b7280;font-size:13px;white-space:nowrap}
.rlg-none{color:#9ca3af}
.rlg-foot{display:flex;align-items:center;gap:16px;padding:12px 16px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:13px;color:#4b5563;flex-wrap:wrap}
.rlg-pager{margin-left:auto;display:inline-flex;gap:4px;align-items:center}
.rlg-pg{min-width:32px;height:30px;padding:0 10px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#374151;font-size:13px;cursor:pointer}
.rlg-pg:disabled{color:#9ca3af;cursor:default}
.rlg-pg-on{background:$a;border-color:$a;color:#fff;font-weight:600}
.rlg-pg-gap{padding:0 4px;color:#6b7280}
.rlg-empty{text-align:center;padding:48px 24px}
.rlg-eico{width:64px;height:64px;margin:0 auto 14px;border-radius:16px;background:#e6eeed;color:$a;display:flex;align-items:center;justify-content:center;font-size:28px}
.rlg-empty h3{margin:0 0 6px;font-size:18px;font-weight:600;color:#111827}
.rlg-empty p{margin:0 auto;max-width:560px;color:#4b5563;font-size:14px;line-height:1.55}
.rlg-nomatch{padding:28px 20px;text-align:center;color:#4b5563;font-size:14px}
.rlg-link{border:0;background:none;color:$a;font-weight:600;cursor:pointer;text-decoration:underline;font-size:14px}
.rlg-err{margin:12px 16px 0;padding:10px 14px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:13px}
@media (prefers-reduced-motion: reduce){.rlg *{transition:none!important}}
@media (max-width:1200px){
 .rlg-cap{grid-template-columns:1fr}
 .rlg-cap-r{border-left:0;padding-left:0;border-top:1px solid #e5e7eb;padding-top:16px}
}
</style>";
}

function report(){
    // $_GET["report"] partait BRUT dans la requete SQLite: injection directe
    // depuis l'URL. La colonne est toujours un md5.
    $zmd5=preg_replace('/[^a-f0-9]/','',(string)($_GET["report"] ?? ""));
    if($zmd5===""){return;}
    $q=new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
    $ligne=$q->mysqli_fetch_array("SELECT report FROM proxy_reports WHERE zmd5='$zmd5'");
    if(!is_array($ligne)){return;}
    // "NONE" = le demon n'a pas pu produire le rapport (stocke en clair, pas en base64).
    if(trim((string)$ligne["report"])=="NONE"){
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body("<p style='font-family:sans-serif;padding:24px'>{rlg_report_failed_tip}</p>");
        return;
    }
    $report=base64_decode((string)$ligne["report"]);
    echo $report;

}

function isRights():bool{
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
    return false;
}

