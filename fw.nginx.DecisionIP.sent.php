<?php
/**
 * DecisionIP — reports sent by the reverse proxy to the shared IP reputation service.
 * Data (reverse-proxy daemon, REST_API_NGINX):
 *   /decisionip/metrics         totals since start_time, per-category counts over the kept days
 *   /decisionip/metrics/hourly  today, per hour ("YYYY-MM-DD-HH")
 *   /decisionip/metrics/daily   up to 7 days
 *   /decisionip/buffer          the queue waiting to be delivered to the local agent
 * The reporting switch is EnableDecisionIP (EmptyBuffer() clears the queue when it is 0);
 * /decisionip/status returns it as "enabled" since 2026-11-21 (DecisionIPCheck is "check_enabled").
 */
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();
if(!$users->AsFirewallManager){exit();}

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors',1);ini_set('error_reporting',E_ALL);}

if(isset($_GET["dip-panel"])){dip_panel();exit;}
if(isset($_GET["main-content"])){main_content();exit;}

page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("DecisionIP {sent_events}",ico_chart_line,"{DecisionIP_sent_events_explain}",
        "$page?main-content=yes","decisionip-sent","progress-decisionip-sent",false,"table-decisionip-sent");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function main_content(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    $topbuttons[]=array("DipRefresh();",ico_refresh,"{refresh}");
    $topbuttons[]=array("document.location.href='/decisionip';",ico_params,"{dip_btn_settings}");
    $topbuttons[]=array("s_PopUp('https://wiki.articatech.com/en/reverse-proxy/security/decisionip','1024','800')","fa-solid fa-circle-question","{online_help}");
    $TINY=array();
    $TINY["TITLE"]="DecisionIP {sent_events}";
    $TINY["ICO"]=ico_chart_line;
    $TINY["EXPL"]="{DecisionIP_sent_events_explain}";
    $TINY["URL"]="decisionip-sent";
    $TINY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
    echo "<div id='dip-host'>";
    dip_panel();
    echo "</div><script>$tiny</script>";
}

// ---------------------------------------------------------------- helpers

function dip_tr(string $token,array $args=array()):string{
    $tpl=new template_admin();
    $s=$tpl->_ENGINE_parse_body("{".$token."}");
    if(count($args)==0){return $s;}
    $keys=array("%a","%b","%c");
    if(strpos($s,"%s")!==false){
        foreach($args as $a){
            $p=strpos($s,"%s");
            if($p===false){break;}
            $s=substr($s,0,$p).$a.substr($s,$p+2);
        }
        return $s;
    }
    foreach($args as $i=>$a){
        if(!isset($keys[$i])){break;}
        $s=str_replace($keys[$i],$a,$s);
    }
    return $s;
}

// Attacker-controlled text (user agents, daemon codes) goes through the token engine with
// the rest of the panel: neutralise its {token} and [br] syntax as well as HTML.
function dip_esc($s):string{
    $s=htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8");
    return str_replace(array("{","}","[","]"),array("&#123;","&#125;","&#91;","&#93;"),$s);
}

function dip_num($n):string{
    return number_format(floatval($n),0,".",",");
}

function dip_age(int $min):string{
    if($min<1){$min=0;}
    if($min<60){return dip_tr("dip_age_min",array($min));}
    return dip_tr("dip_age_h",array(intval($min/60),$min%60));
}

function dip_api(string $uri){
    $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX($uri);
    if(!is_string($raw) || $raw===""){return null;}
    $json=json_decode($raw,true);
    if(!is_array($json)){return null;}
    return $json;
}

// Human label of a category code produced by the daemon (SyslogUnix / ModSecurity).
function dip_cat_label(string $code):string{
    if($code=="NGINX-403"){return "{dip_cat_403}";}
    if($code=="NGINX-444"){return "{dip_cat_444}";}
    if(strpos($code,"NGINX-404-")===0){
        $g=substr($code,10);
        return "{dip_cat_404}".($g!==""?" (".dip_esc($g).")":"");
    }
    if(strpos($code,"WAF-")===0){return "{dip_cat_waf_one}";}
    return dip_esc($code);
}

// Axis maximum = 4 readable steps (1, 2, 3, 4, 5, 6, 8 x 10^n), so the 4 grid lines are round numbers.
function dip_nice_max(int $v):int{
    if($v<=4){return 4;}
    $raw=$v/4;
    $mag=pow(10,floor(log10($raw)));
    foreach(array(1,2,3,4,5,6,8,10) as $f){
        if($raw<=$f*$mag){return intval($f*$mag*4);}
    }
    return $v;
}

// ---------------------------------------------------------------- model

function dip_model():array{
    $m=array("error"=>false,"enabled"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDecisionIP"))==1,
        "total_posted"=>0,"total_errors"=>0,"start_time"=>0,"hours"=>array(),"days"=>array(),
        "today_p"=>0,"today_e"=>0,"week_p"=>0,"week_e"=>0,"last_report"=>"","last_title"=>"",
        "cats"=>array(),"waf_rules"=>array(),"given_up"=>array(),"cat_max"=>0,
        "queue"=>array(),"pending"=>0,"oldest"=>0,"queue_known"=>false);

    $sum=dip_api("/decisionip/metrics");
    if(!is_array($sum) || !array_key_exists("total_posted",$sum)){
        $m["error"]=true;
        return $m;
    }
    $m["total_posted"]=intval($sum["total_posted"]);
    $m["total_errors"]=intval($sum["total_errors"] ?? 0);
    $m["start_time"]=intval($sum["start_time"] ?? 0);

    // Today, per hour. Hours after the current one are "future", never zero.
    $nowH=intval(date("G"));
    $today=date("Y-m-d");
    $hours=array();
    for($h=0;$h<24;$h++){$hours[$h]=array("p"=>0,"e"=>0,"future"=>$h>$nowH);}
    $hj=dip_api("/decisionip/metrics/hourly");
    if(is_array($hj) && isset($hj["data"]) && is_array($hj["data"])){
        foreach($hj["data"] as $row){
            if(!is_array($row) || !isset($row["hour"])){continue;}
            $parts=explode("-",(string)$row["hour"]);
            if(count($parts)!=4){continue;}
            if($parts[0]."-".$parts[1]."-".$parts[2]!=$today){continue;}
            $h=intval($parts[3]);
            if($h<0 || $h>23){continue;}
            $hours[$h]["p"]=intval($row["total_posted"] ?? 0);
            $hours[$h]["e"]=intval($row["total_errors"] ?? 0);
        }
    }
    foreach($hours as $h=>$v){$m["today_p"]+=$v["p"];$m["today_e"]+=$v["e"];}
    $m["hours"]=$hours;

    // Last 7 days. A day before tracking started is "untracked", never zero.
    $byDate=array();
    $dj=dip_api("/decisionip/metrics/daily");
    if(is_array($dj) && isset($dj["data"]) && is_array($dj["data"])){
        foreach($dj["data"] as $row){
            if(!is_array($row) || !isset($row["date"])){continue;}
            $byDate[(string)$row["date"]]=array("p"=>intval($row["total_posted"] ?? 0),"e"=>intval($row["total_errors"] ?? 0));
        }
    }
    $startDay=$m["start_time"]>0?date("Y-m-d",$m["start_time"]):"";
    $days=array();
    for($i=6;$i>=0;$i--){
        $ts=strtotime("-$i day");
        $d=date("Y-m-d",$ts);
        $v=isset($byDate[$d])?$byDate[$d]:array("p"=>0,"e"=>0);
        if($d==$today){$v=array("p"=>$m["today_p"],"e"=>$m["today_e"]);}
        $untracked=($startDay!=="" && $d<$startDay && !isset($byDate[$d]));
        $days[]=array("date"=>$d,"ts"=>$ts,"p"=>$v["p"],"e"=>$v["e"],"untracked"=>$untracked);
        $m["week_p"]+=$v["p"];$m["week_e"]+=$v["e"];
    }
    $m["days"]=$days;

    // Last report: last_success (daemon 2026-11-21+). last_updated is never used: it also moves
    // on failures. Older daemons, or a store saved before the field existed, fall back to the
    // most recent hour (today) or day that carries a successful report.
    $ls=intval($sum["last_success"] ?? 0);
    if($ls>0 && $ls<=time()+60){
        $age=intval((time()-$ls)/60);
        if($age<1){
            $m["last_report"]=dip_tr("dip_chip_last_now");
        }elseif($age<1440){
            $m["last_report"]=dip_tr("dip_chip_last_ago",array(dip_age($age)));
        }else{
            $m["last_report"]=dip_tr("dip_chip_last_day",array(date("j M",$ls)));
        }
        $m["last_title"]=date("Y-m-d H:i:s",$ls);
    }
    for($h=23;$h>=0 && $m["last_report"]==="";$h--){
        if($hours[$h]["p"]>0){$m["last_report"]=dip_tr("dip_chip_last_today",array(sprintf("%02d:00-%02d:59",$h,$h)));break;}
    }
    if($m["last_report"]===""){
        for($i=count($days)-1;$i>=0;$i--){
            if($days[$i]["p"]>0){$m["last_report"]=dip_tr("dip_chip_last_day",array(date("j M",$days[$i]["ts"])));break;}
        }
    }

    // Categories over the kept days. TIMEOUT-/MAXRETRY- are give-ups, not threat types.
    $posted=(isset($sum["by_category"]) && is_array($sum["by_category"]))?$sum["by_category"]:array();
    $errors=(isset($sum["errors_by_category"]) && is_array($sum["errors_by_category"]))?$sum["errors_by_category"]:array();
    $cats=array();$waf=array();$gu=array();
    $wafRow=array("code"=>"WAF","p"=>0,"e"=>0);
    foreach($posted as $code=>$n){
        $code=(string)$code;$n=intval($n);
        if(strpos($code,"WAF-")===0){
            if(!isset($waf[$code])){$waf[$code]=array("p"=>0,"e"=>0);}
            $waf[$code]["p"]+=$n;$wafRow["p"]+=$n;continue;
        }
        if(!isset($cats[$code])){$cats[$code]=array("code"=>$code,"p"=>0,"e"=>0);}
        $cats[$code]["p"]+=$n;
    }
    foreach($errors as $code=>$n){
        $code=(string)$code;$n=intval($n);
        if(strpos($code,"TIMEOUT-")===0 || strpos($code,"MAXRETRY-")===0){
            $gu[]=array("code"=>$code,"n"=>$n,"kind"=>strpos($code,"TIMEOUT-")===0?"timeout":"maxretry",
                "inner"=>substr($code,strpos($code,"-")+1));
            continue;
        }
        if(strpos($code,"WAF-")===0){
            if(!isset($waf[$code])){$waf[$code]=array("p"=>0,"e"=>0);}
            $waf[$code]["e"]+=$n;$wafRow["e"]+=$n;continue;
        }
        if(!isset($cats[$code])){$cats[$code]=array("code"=>$code,"p"=>0,"e"=>0);}
        $cats[$code]["e"]+=$n;
    }
    if($wafRow["p"]>0 || $wafRow["e"]>0){$cats["WAF"]=$wafRow;}
    $cats=array_values($cats);
    usort($cats,function($a,$b){ if($a["p"]==$b["p"]){return $b["e"]-$a["e"];} return $b["p"]-$a["p"]; });
    uasort($waf,function($a,$b){ if($a["p"]==$b["p"]){return $b["e"]-$a["e"];} return $b["p"]-$a["p"]; });
    usort($gu,function($a,$b){ return $b["n"]-$a["n"]; });
    foreach($cats as $c){if($c["p"]>$m["cat_max"]){$m["cat_max"]=$c["p"];}}
    $m["cats"]=$cats;$m["waf_rules"]=$waf;$m["given_up"]=$gu;

    // Queue, oldest first.
    $bj=dip_api("/decisionip/buffer");
    if(is_array($bj) && isset($bj["items"]) && is_array($bj["items"])){
        $m["queue_known"]=true;
        $q=array();
        foreach($bj["items"] as $it){
            if(!is_array($it) || !isset($it["ip"])){continue;}
            $q[]=array("ip"=>(string)$it["ip"],"category"=>(string)($it["category"] ?? ""),
                "age"=>intval($it["age_minutes"] ?? 0),"failed"=>intval($it["failed_attempts"] ?? 0),
                "ua"=>(string)($it["user_agent"] ?? ""));
        }
        usort($q,function($a,$b){ return $b["age"]-$a["age"]; });
        $m["queue"]=$q;
        $m["pending"]=count($q);
        $m["oldest"]=count($q)>0?$q[0]["age"]:0;
    }
    return $m;
}

// ---------------------------------------------------------------- panel

function dip_panel(){
    $tpl=new template_admin();
    $m=dip_model();
    $view=(isset($_GET["view"]) && $_GET["view"]=="day")?"day":"hour";
    $p=max(1,intval($_GET["p"] ?? 1));
    $w=array();
    if($m["error"]){
        $w[]=dip_error_card();
    }else{
        $w[]=dip_hero($m);
        $w[]=dip_alert($m);
        $w[]=dip_kpis($m);
        $nothingYet=($m["total_posted"]==0 && $m["total_errors"]==0 && $m["pending"]==0);
        if($nothingYet){
            $w[]=dip_empty_card();
        }else{
            $w[]=dip_activity($m,$view);
            $w[]="<div class='dip-two'>".dip_categories($m).dip_queue($m,$p)."</div>";
        }
    }
    $accent="#005447";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){$a=brand_ui_accents(brand_palette());if(!empty($a["accent"])){$accent=$a["accent"];}}
    echo dip_style().$tpl->_ENGINE_parse_body("<div class='dip' id='dip-panel' style='--dip-accent:$accent'>".@implode("\n",$w)."</div>").dip_js($view,$p);
}

function dip_error_card():string{
    return "<div class='dip-card dip-empty' role='alert'>
<div class='dip-empty-ico dip-empty-err'><i class='fa-solid fa-plug-circle-xmark' aria-hidden='true'></i></div>
<h3>{dip_err_title}</h3><p>{dip_err_text}</p></div>";
}

function dip_hero(array $m):string{
    $chips=array();
    if($m["week_p"]>0){$chips[]="<span class='dip-chip dip-chip-ok'><i class='fa-solid fa-paper-plane' aria-hidden='true'></i>".dip_tr("dip_chip_sent7",array(dip_num($m["week_p"])))."</span>";}
    if($m["last_report"]!==""){
        $tt=$m["last_title"]!==""?" title=\"{$m["last_title"]}\"":"";
        $chips[]="<span class='dip-chip'$tt><i class='fa-solid fa-clock' aria-hidden='true'></i>{$m["last_report"]}</span>";
    }else{
        $chips[]="<span class='dip-chip'><i class='fa-solid fa-clock' aria-hidden='true'></i>{dip_chip_last_none}</span>";
    }
    if($m["pending"]>0){
        $chips[]="<span class='dip-chip'><i class='fa-solid fa-hourglass-half' aria-hidden='true'></i>".dip_tr("dip_chip_waiting",array(dip_num($m["pending"])))."</span>";
    }
    return "<section class='dip-hero' aria-labelledby='dip-hero-t'>
<div class='dip-hero-ico' aria-hidden='true'><i class='fa-solid fa-satellite-dish'></i></div>
<div><h2 id='dip-hero-t'>{dip_hero_title}</h2><p>{dip_hero_text}</p>
<div class='dip-chips'>".implode("",$chips)."</div></div></section>";
}

function dip_alert(array $m):string{
    if(!$m["enabled"]){
        return "<div class='dip-alert dip-alert-err' role='alert'><i class='fa-solid fa-power-off' aria-hidden='true'></i>
<div class='dip-alert-t'>{dip_alert_off}</div>
<a href='javascript:void(0)' OnClick=\"document.location.href='/decisionip';\">{dip_alert_off_link}</a></div>";
    }
    if($m["today_e"]>0){
        return "<div class='dip-alert dip-alert-warn' role='alert'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i>
<div class='dip-alert-t'>".dip_tr("dip_alert_failed",array(dip_num($m["today_e"])))."</div>
<a href='javascript:void(0)' OnClick=\"DipGoQueue();\">{dip_alert_failed_link} <i class='fa-solid fa-arrow-down' aria-hidden='true'></i></a></div>";
    }
    return "";
}

function dip_kpis(array $m):string{
    return "<div class='dip-kpis'>".dip_kpi_reported($m).dip_kpi_failed($m).dip_kpi_waiting($m).dip_kpi_today($m)."</div>";
}

function dip_kpi(string $label,string $value,string $sub,string $ico,string $mod=""):string{
    return "<div class='dip-kpi $mod'><div class='dip-kpi-b'><div class='dip-kpi-l'>$label</div>
<div class='dip-kpi-v'>$value</div><div class='dip-kpi-s'>$sub</div></div>
<div class='dip-kpi-i' aria-hidden='true'><i class='$ico'></i></div></div>";
}

function dip_kpi_reported(array $m):string{
    $sub=$m["start_time"]>0?dip_tr("dip_kpi_since",array(date("j M Y",$m["start_time"]))):"&nbsp;";
    return dip_kpi("{dip_kpi_reported}",dip_num($m["total_posted"]),$sub,"fa-solid fa-cloud-arrow-up");
}

function dip_kpi_failed(array $m):string{
    $att=$m["total_posted"]+$m["total_errors"];
    $sub="&mdash;";
    if($att>0 && $m["total_errors"]>0){
        $r=$m["total_errors"]*100/$att;
        $rate=$r<0.1?"&lt;0.1&nbsp;%":sprintf("%.1f",$r)."&nbsp;%";
        $sub=dip_tr("dip_kpi_failed_rate",array($rate));
    }elseif($att>0){
        $sub=dip_tr("dip_kpi_failed_rate",array("0&nbsp;%"));
    }
    $mod=$m["total_errors"]>0?"dip-kpi-warn":"dip-kpi-mute";
    return dip_kpi("{dip_kpi_failed}",dip_num($m["total_errors"]),$sub,"fa-solid fa-circle-exclamation",$mod);
}

function dip_kpi_waiting(array $m):string{
    if(!$m["queue_known"]){return dip_kpi("{dip_kpi_waiting}","&mdash;","&nbsp;","fa-solid fa-hourglass-half");}
    $sub=$m["pending"]>0?dip_tr("dip_kpi_oldest",array(dip_age($m["oldest"]))):"{dip_kpi_nothing}";
    return dip_kpi("{dip_kpi_waiting}",dip_num($m["pending"]),$sub,"fa-solid fa-hourglass-half");
}

function dip_kpi_today(array $m):string{
    $sub=$m["today_e"]>0?"<span class='dip-amber'>".dip_tr("dip_kpi_today_failed",array(dip_num($m["today_e"])))."</span>":"{dip_kpi_today_sub}";
    return dip_kpi("{dip_kpi_today}",dip_num($m["today_p"])." ".dip_spark($m["hours"]),$sub,"fa-solid fa-calendar-day");
}

// Today's reports per hour, up to the current hour (future hours are not drawn).
function dip_spark(array $hours):string{
    $pts=array();
    foreach($hours as $h=>$v){if(!$v["future"]){$pts[]=$v["p"];}}
    if(count($pts)<2){return "";}
    $max=max($pts);
    if($max<=0){return "";}
    $n=count($pts);$coords=array();
    foreach($pts as $i=>$v){
        $x=round($i*100/($n-1),1);
        $y=round(22-($v/$max)*20,1);
        $coords[]="$x,$y";
    }
    return "<svg class='dip-spark' viewBox='0 0 100 24' preserveAspectRatio='none' aria-hidden='true'><polyline fill='none' stroke='currentColor' stroke-width='2' stroke-linejoin='round' stroke-linecap='round' vector-effect='non-scaling-stroke' points='".implode(" ",$coords)."'/></svg>";
}

function dip_activity(array $m,string $view):string{
    $hbars=array();
    foreach($m["hours"] as $h=>$v){
        $lbl=sprintf("%02d",$h);
        $hbars[]=array("label"=>$lbl,"p"=>$v["p"],"e"=>$v["e"],"off"=>$v["future"],"title"=>sprintf("%02d:00",$h));
    }
    $dbars=array();
    foreach($m["days"] as $d){
        $lbl=date("j M",$d["ts"]);
        $dbars[]=array("label"=>$lbl,"p"=>$d["p"],"e"=>$d["e"],"off"=>$d["untracked"],"title"=>$lbl);
    }
    $isH=$view=="hour";
    $seg="<div class='dip-seg' role='radiogroup' aria-label='{dip_act_title}'>
<button type='button' role='radio' data-v='hour' aria-checked='".($isH?"true":"false")."' tabindex='".($isH?"0":"-1")."' OnClick=\"DipView('hour');\" onkeydown='DipSegKey(event)'>{dip_view_hour}</button>
<button type='button' role='radio' data-v='day' aria-checked='".($isH?"false":"true")."' tabindex='".($isH?"-1":"0")."' OnClick=\"DipView('day');\" onkeydown='DipSegKey(event)'>{dip_view_day}</button></div>";
    $legend="<div class='dip-legend'><span><i class='dip-sw dip-sw-p'></i>{dip_legend_reported}</span><span><i class='dip-sw dip-sw-e'></i>{dip_legend_failed}</span></div>";
    $srH=dip_tr("dip_sr_hour",array(dip_num($m["today_p"]),dip_num($m["today_e"])));
    $srD=dip_tr("dip_sr_day",array(dip_num($m["week_p"]),dip_num($m["week_e"])));
    $tz=dip_tr("dip_tz",array(date("P")));
    return "<section class='dip-card' aria-labelledby='dip-act-t'>
<div class='dip-card-h'><div><h3 id='dip-act-t'>{dip_act_title}</h3><p class='dip-x'>{dip_act_explain}</p></div>
<div class='dip-card-tools'>$legend$seg</div></div>
".dip_bars($hbars,"hour",!$isH,$srH)."
".dip_bars($dbars,"day",$isH,$srD)."
<div class='dip-foot'><span>{dip_retention}</span><span class='dip-foot-tz'".($isH?"":" hidden").">$tz</span></div>
</section>";
}

function dip_bars(array $bars,string $v,bool $hidden,string $sr):string{
    $max=0;
    foreach($bars as $b){if($b["p"]+$b["e"]>$max){$max=$b["p"]+$b["e"];}}
    $top=dip_nice_max($max);
    $grid=array();
    foreach(array(4,3,2,1,0) as $k){
        $val=intval(round($top*$k/4));
        $grid[]="<div class='dip-gl' style='bottom:".($k*25)."%'><span>".dip_num($val)."</span></div>";
    }
    $cols=array();
    foreach($bars as $b){
        if($b["off"]){
            $t=$b["title"].": ".dip_tr($v=="hour"?"dip_bar_future":"dip_bar_untracked");
            $cols[]="<div class='dip-col dip-col-off' title=\"$t\"><div class='dip-stack'></div><span class='dip-cl'>{$b["label"]}</span></div>";
            continue;
        }
        $t=dip_tr("dip_bar_title",array($b["title"],dip_num($b["p"]),dip_num($b["e"])));
        $hp=$top>0?round($b["p"]*100/$top,2):0;
        $he=$top>0?round($b["e"]*100/$top,2):0;
        $seg="";
        if($b["e"]>0){$seg.="<div class='dip-bar-e' style='height:$he%'></div>";}
        if($b["p"]>0){$seg.="<div class='dip-bar-p' style='height:$hp%'></div>";}
        $cols[]="<div class='dip-col' title=\"$t\"><div class='dip-stack'>$seg</div><span class='dip-cl'>{$b["label"]}</span></div>";
    }
    $n=count($bars);
    return "<div class='dip-chart dip-chart-$v' data-v='$v'".($hidden?" hidden":"")."><p class='dip-sr'>$sr</p>
<div class='dip-plot' aria-hidden='true'>".implode("",$grid)."<div class='dip-cols' style='grid-template-columns:repeat($n,1fr)'>".implode("",$cols)."</div></div></div>";
}

function dip_cat_row(string $label,string $code,int $p,int $e,int $max,string $extra=""):string{
    $w=$max>0?round($p*100/$max,2):0;
    $fail=$e>0?"<span class='dip-pill-warn'>".dip_tr("dip_failed_n",array(dip_num($e)))."</span>":"";
    return "<div class='dip-cat'><div class='dip-cat-h'><div class='dip-cat-n'><span class='dip-cat-l'>$label</span> <span class='dip-mono'>".$code."</span></div>
<div class='dip-cat-c'><b>".dip_num($p)."</b> <span class='dip-sub'>{dip_sent}</span>$fail</div></div>
<div class='dip-track'><div class='dip-fill' style='width:$w%'></div></div>$extra</div>";
}

function dip_categories(array $m):string{
    $rows=array();
    foreach($m["cats"] as $c){
        if($c["code"]=="WAF"){
            $rows[]=dip_cat_row("{dip_cat_waf}","WAF",$c["p"],$c["e"],$m["cat_max"],dip_waf_rules($m));
            continue;
        }
        $rows[]=dip_cat_row(dip_cat_label($c["code"]),dip_esc($c["code"]),$c["p"],$c["e"],$m["cat_max"]);
    }
    if(count($rows)==0){
        $rows[]="<p class='dip-none'><i class='fa-solid fa-circle-info' aria-hidden='true'></i> {dip_cats_empty}</p>";
    }
    return "<section class='dip-card' aria-labelledby='dip-cat-t'>
<h3 id='dip-cat-t'>{dip_cats_title}</h3><p class='dip-x'>{dip_cats_explain}</p>
<div class='dip-cats'>".implode("",$rows)."</div>".dip_given_up($m)."</section>";
}

function dip_waf_rules(array $m):string{
    $n=count($m["waf_rules"]);
    if($n==0){return "";}
    $li=array();
    foreach($m["waf_rules"] as $code=>$v){
        $id=substr($code,4);
        $fail=$v["e"]>0?" <span class='dip-amber'>".dip_tr("dip_failed_n",array(dip_num($v["e"])))."</span>":"";
        $li[]="<li><span>".dip_tr("dip_cat_waf_rule",array(dip_esc($id)))."</span><span><b>".dip_num($v["p"])."</b>$fail</span></li>";
    }
    return "<details class='dip-rules'><summary><i class='fa-solid fa-chevron-right' aria-hidden='true'></i> ".dip_tr("dip_cat_rules",array(dip_num($n)))."</summary><ul>".implode("",$li)."</ul></details>";
}

function dip_given_up(array $m):string{
    if(count($m["given_up"])==0){return "";}
    $li=array();
    foreach($m["given_up"] as $g){
        $lbl=$g["kind"]=="timeout"?"{dip_gu_timeout}":"{dip_gu_maxretry}";
        $li[]="<li><div><div class='dip-gu-l'>$lbl</div><div class='dip-gu-s'>".dip_cat_label($g["inner"])." <span class='dip-mono'>".dip_esc($g["code"])."</span></div></div><span class='dip-gu-n'>".dip_num($g["n"])."</span></li>";
    }
    return "<div class='dip-gu'><h4><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i> {dip_gu_title}</h4>
<p class='dip-x'>{dip_gu_explain}</p><ul>".implode("",$li)."</ul></div>";
}

function dip_queue(array $m,int $p):string{
    $per=25;
    $badge=$m["pending"]>0?"<span class='dip-badge'>".dip_tr("dip_wait_badge",array(dip_num($m["pending"])))."</span>":"";
    $head="<section class='dip-card' id='dip-waiting' aria-labelledby='dip-wait-t'>
<div class='dip-title-row'><h3 id='dip-wait-t'>{dip_wait_title}</h3>$badge</div><p class='dip-x'>{dip_wait_explain}</p>";
    if(!$m["queue_known"]){
        return $head."<div class='dip-qempty'><i class='fa-solid fa-circle-question' aria-hidden='true'></i><p>{dip_wait_unknown}</p></div></section>";
    }
    if($m["pending"]==0){
        $txt=$m["enabled"]?"{dip_wait_empty}":"{dip_wait_off}";
        $ico=$m["enabled"]?"fa-circle-check":"fa-power-off";
        return $head."<div class='dip-qempty'><i class='fa-solid $ico' aria-hidden='true'></i><p>$txt</p></div></section>";
    }
    $pages=intval(ceil($m["pending"]/$per));
    if($p>$pages){$p=$pages;}
    $off=($p-1)*$per;
    $slice=array_slice($m["queue"],$off,$per);
    $tr=array();
    foreach($slice as $q){
        $fail=$q["failed"]>0
            ?"<span class='dip-pill-warn'><i class='fa-solid fa-triangle-exclamation' aria-hidden='true'></i> ".dip_num($q["failed"])."</span><div class='dip-note'>{dip_note_50}</div>"
            :"<span class='dip-sub'>0</span>";
        $age=dip_age($q["age"]);
        if($q["age"]>=180){$age="<span class='dip-amber'>$age</span><div class='dip-note'>{dip_note_4h}</div>";}
        $ua=$q["ua"]!==""?"<span class='dip-ua' title=\"".dip_esc($q["ua"])."\">".dip_esc($q["ua"])."</span>":"<span class='dip-sub'>&mdash;</span>";
        $tr[]="<tr><td class='dip-mono dip-ip'>".dip_esc($q["ip"])."</td>
<td><div>".dip_cat_label($q["category"])."</div><div class='dip-mono dip-sub'>".dip_esc($q["category"])."</div></td>
<td>$age</td><td>$fail</td><td class='dip-ua-td'>$ua</td></tr>";
    }
    $from=$off+1;$to=$off+count($slice);
    $prev=$p>1?"OnClick=\"DipPage(".($p-1).");\"":"disabled";
    $next=$p<$pages?"OnClick=\"DipPage(".($p+1).");\"":"disabled";
    $pager="<div class='dip-pager'><span>".dip_tr("dip_pager",array("<b>".dip_num($from)."</b>","<b>".dip_num($to)."</b>","<b>".dip_num($m["pending"])."</b>"))."</span>
<span class='dip-pager-b'><button type='button' $prev aria-label='{dip_prev}'><i class='fa-solid fa-chevron-left' aria-hidden='true'></i></button><button type='button' $next aria-label='{dip_next}'><i class='fa-solid fa-chevron-right' aria-hidden='true'></i></button></span></div>";
    return $head."<div class='dip-tw'><table class='dip-table'><thead><tr><th scope='col'>{dip_col_address}</th><th scope='col'>{dip_col_reason}</th><th scope='col'>{dip_col_waiting}</th><th scope='col'>{dip_col_failed}</th><th scope='col'>{dip_col_browser}</th></tr></thead>
<tbody>".implode("",$tr)."</tbody></table></div>".($pages>1?$pager:"<div class='dip-pager'><span>".dip_tr("dip_pager",array("<b>1</b>","<b>".dip_num($to)."</b>","<b>".dip_num($m["pending"])."</b>"))."</span></div>")."</section>";
}

function dip_empty_card():string{
    return "<div class='dip-card dip-empty'>
<div class='dip-empty-ico'><i class='fa-solid fa-satellite-dish' aria-hidden='true'></i></div>
<h3>{dip_empty_title}</h3><p>{dip_empty_text}</p></div>";
}

// ---------------------------------------------------------------- style + js

function dip_style():string{
    if(isset($GLOBALS["DIP_CSS"])){return "";}
    $GLOBALS["DIP_CSS"]=true;
    $accent="#005447";$dark="#003d33";$soft="#e6eeed";$line="#bfd4d1";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    return "<style>
.dip{display:flex;flex-direction:column;gap:20px;color:#0f172a;font-size:14px;max-width:1320px}
.dip *{box-sizing:border-box}
.dip-hero{display:flex;gap:18px;align-items:flex-start;background:#f2f6f6;border:1px solid $line;border-radius:12px;padding:22px}
.dip-hero-ico{flex:0 0 auto;width:48px;height:48px;border-radius:50%;background:$accent;color:#fff;display:flex;align-items:center;justify-content:center;font-size:20px}
.dip-hero h2{font-size:18px;font-weight:600;margin:0}
.dip-hero p{font-size:14px;line-height:1.6;color:#334155;margin:6px 0 0;max-width:900px}
.dip-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.dip-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:13px;font-weight:500;border:1px solid #e2e8f0;background:#fff;color:#334155}
.dip-chip-ok{border-color:$line;color:$dark}
.dip-alert{display:flex;align-items:center;gap:12px;border-radius:12px;padding:14px 18px;font-size:14px;font-weight:500}
.dip-alert-t{flex:1 1 auto}
.dip-alert a{font-weight:600;text-decoration:underline;white-space:nowrap}
.dip-alert-warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.dip-alert-warn a{color:#92400e}
.dip-alert-err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
.dip-alert-err a{color:#b91c1c}
.dip-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
@media (max-width:1200px){.dip-kpis{grid-template-columns:repeat(2,1fr)}}
.dip-kpi{display:flex;justify-content:space-between;gap:12px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;box-shadow:0 1px 2px rgba(15,23,42,.05);min-width:0}
.dip-kpi-b{min-width:0;flex:1 1 auto}
.dip-kpi-l{font-size:13px;color:#64748b}
.dip-kpi-v{font-size:28px;font-weight:600;line-height:1.2;margin:6px 0 4px;display:flex;align-items:center;gap:12px;color:#0f172a}
.dip-kpi-s{font-size:13px;color:#64748b}
.dip-kpi-i{flex:0 0 auto;width:40px;height:40px;border-radius:10px;background:$soft;color:$accent;display:flex;align-items:center;justify-content:center;font-size:16px}
.dip-kpi-warn .dip-kpi-v,.dip-kpi-warn .dip-kpi-s{color:#b45309}
.dip-kpi-warn .dip-kpi-i{background:#fffbeb;color:#b45309}
.dip-kpi-mute .dip-kpi-v{color:#64748b}
.dip-spark{width:110px;height:24px;color:$accent;flex:0 1 auto}
.dip-amber{color:#b45309}
.dip-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px 22px;box-shadow:0 1px 2px rgba(15,23,42,.05);min-width:0}
.dip-card h3{font-size:16px;font-weight:600;margin:0;color:#0f172a}
.dip-card-h{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}
.dip-card-tools{display:flex;align-items:center;gap:18px;flex-wrap:wrap}
.dip-x{font-size:13px;color:#64748b;line-height:1.5;margin:4px 0 14px}
.dip-sub{font-size:13px;color:#64748b}
.dip-legend{display:flex;gap:14px;font-size:13px;color:#334155}
.dip-legend span{display:inline-flex;align-items:center;gap:6px}
.dip-sw{display:inline-block;width:12px;height:12px;border-radius:3px}
.dip-sw-p,.dip-bar-p{background:$accent}
.dip-sw-e,.dip-bar-e{background:#d97706}
.dip-seg{display:inline-flex;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:3px}
.dip-seg button{border:0;background:transparent;color:#475569;font-size:13px;font-weight:500;padding:6px 14px;border-radius:6px;cursor:pointer}
.dip-seg button[aria-checked=true]{background:$accent;color:#fff}
.dip-seg button:focus-visible,.dip-pager button:focus-visible,.dip-rules summary:focus-visible,.dip-alert a:focus-visible{outline:2px solid $accent;outline-offset:2px}
.dip-chart{border-top:1px solid #f1f5f9;padding-top:16px}
.dip-chart[hidden],.dip-foot-tz[hidden]{display:none}
.dip-plot{position:relative;height:200px;margin:0 0 0 34px}
.dip-gl{position:absolute;left:0;right:0;border-top:1px dashed #e2e8f0}
.dip-gl span{position:absolute;left:-34px;top:-8px;width:28px;text-align:right;font-size:11px;color:#94a3b8}
.dip-cols{position:absolute;inset:0;display:grid;gap:6px;align-items:end}
.dip-col{position:relative;height:100%;display:flex;flex-direction:column;justify-content:flex-end;align-items:stretch}
.dip-stack{display:flex;flex-direction:column;justify-content:flex-end;height:100%;border-bottom:2px solid #e2e8f0}
.dip-bar-p,.dip-bar-e{width:100%;min-height:2px}
.dip-bar-p{border-radius:0 0 0 0}
.dip-stack>div:first-child{border-radius:3px 3px 0 0}
.dip-col:hover .dip-stack>div{filter:brightness(1.15);transition:filter .15s}
.dip-col-off .dip-stack{border-bottom-style:dashed;border-bottom-color:#e2e8f0}
.dip-cl{position:absolute;bottom:-20px;left:0;right:0;text-align:center;font-size:11px;color:#64748b;white-space:nowrap}
.dip-col-off .dip-cl{color:#cbd5e1}
.dip-foot{display:flex;justify-content:space-between;gap:12px;margin-top:32px;font-size:13px;color:#64748b}
.dip-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
.dip-two{display:grid;grid-template-columns:5fr 7fr;gap:20px;align-items:start}
@media (max-width:1200px){.dip-two{grid-template-columns:1fr}}
.dip-cats{display:flex;flex-direction:column;gap:16px;border-top:1px solid #f1f5f9;padding-top:16px}
.dip-cat-h{display:flex;justify-content:space-between;align-items:baseline;gap:10px;margin-bottom:6px}
.dip-cat-n{min-width:0}
.dip-cat-l{font-weight:600}
.dip-cat-c{white-space:nowrap;display:flex;align-items:baseline;gap:6px}
.dip-mono{font-family:Menlo,Consolas,monospace;font-size:12px;color:#64748b}
.dip-track{height:8px;border-radius:999px;background:#f1f5f9;overflow:hidden}
.dip-fill{height:100%;background:$accent;border-radius:999px}
.dip-pill-warn{display:inline-flex;align-items:center;gap:4px;background:#fffbeb;color:#b45309;border:1px solid #fde68a;border-radius:6px;padding:1px 7px;font-size:12px;font-weight:600}
.dip-rules{margin-top:10px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc}
.dip-rules summary{cursor:pointer;list-style:none;padding:8px 12px;font-size:13px;font-weight:500;color:$accent}
.dip-rules summary::-webkit-details-marker{display:none}
.dip-rules[open] summary svg,.dip-rules[open] summary i{transform:rotate(90deg)}
.dip-rules ul{list-style:none;margin:0;padding:0 12px 8px}
.dip-rules li{display:flex;justify-content:space-between;gap:10px;padding:6px 0;border-top:1px solid #e2e8f0;font-size:13px}
.dip-gu{margin-top:20px;border-top:1px solid #f1f5f9;padding-top:16px}
.dip-gu h4{font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:#b45309;margin:0}
.dip-gu ul{list-style:none;margin:0;padding:0;background:#fffbeb;border:1px solid #fde68a;border-radius:8px}
.dip-gu li{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 14px}
.dip-gu li+li{border-top:1px solid #fde68a}
.dip-gu-l{font-weight:600;font-size:13px}
.dip-gu-s{font-size:12px;color:#92400e}
.dip-gu-s .dip-mono{color:#92400e}
.dip-gu-n{background:#fff;border:1px solid #fde68a;border-radius:6px;padding:2px 9px;font-weight:600;color:#b45309;font-size:13px}
.dip-none{font-size:13px;color:#64748b;margin:0}
.dip-title-row{display:flex;align-items:center;gap:10px}
.dip-badge{background:$soft;color:$dark;border:1px solid $line;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600}
.dip-tw{border-top:1px solid #f1f5f9;overflow:hidden}
.dip-table{width:100%;border-collapse:collapse;table-layout:fixed;color:#0f172a}
.dip-table th{font-size:13px;font-weight:600;color:#334155;text-align:left;padding:12px 10px;border-bottom:1px solid #e2e8f0}
.dip-table th:nth-child(1){width:22%}.dip-table th:nth-child(2){width:26%}.dip-table th:nth-child(3){width:15%}.dip-table th:nth-child(4){width:15%}
.dip-table td{font-size:13px;padding:12px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle;color:#0f172a;word-wrap:break-word}
.dip-ip{color:#0f172a;font-size:13px}
.dip-note{font-size:12px;color:#64748b;margin-top:2px}
.dip-ua{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dip-pager{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-top:14px;font-size:13px;color:#475569}
.dip-pager-b{display:inline-flex;gap:4px}
.dip-pager button{width:32px;height:32px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;color:#334155;cursor:pointer}
.dip-pager button:disabled{opacity:.4;cursor:default}
.dip-qempty{text-align:center;padding:36px 12px;border-top:1px solid #f1f5f9;color:#475569}
.dip-qempty i,.dip-qempty svg{font-size:28px;color:#94a3b8}
.dip-qempty p{margin:10px 0 0;font-size:14px}
.dip-empty{text-align:center;padding:48px 24px}
.dip-empty-ico{width:64px;height:64px;border-radius:50%;background:#f1f5f9;color:#94a3b8;display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 14px}
.dip-empty-err{background:#fef2f2;color:#b91c1c}
.dip-empty p{max-width:620px;margin:8px auto 0;color:#475569;line-height:1.6}
@media (prefers-reduced-motion:reduce){.dip *{transition:none!important}}
</style>";
}

function dip_js(string $view,int $p):string{
    $page=CurrentPageName();
    return "<script>
window.DipState={view:'$view',p:$p};
function DipView(v){
 window.DipState.view=v;
 var root=document.getElementById('dip-panel');if(!root){return;}
 root.querySelectorAll('.dip-chart').forEach(function(c){c.hidden=(c.getAttribute('data-v')!==v);});
 root.querySelectorAll('.dip-seg button').forEach(function(b){var on=b.getAttribute('data-v')===v;b.setAttribute('aria-checked',on?'true':'false');b.tabIndex=on?0:-1;});
 var tz=root.querySelector('.dip-foot-tz');if(tz){tz.hidden=(v!=='hour');}
}
function DipSegKey(e){
 if(e.key!=='ArrowLeft'&&e.key!=='ArrowRight'){return;}
 e.preventDefault();var v=window.DipState.view==='hour'?'day':'hour';DipView(v);
 var b=document.querySelector('#dip-panel .dip-seg button[data-v='+v+']');if(b){b.focus();}
}
function DipUrl(){return '$page?dip-panel=yes&view='+window.DipState.view+'&p='+window.DipState.p;}
function DipPage(n){window.DipState.p=n;LoadAjaxSilent('dip-host',DipUrl());}
function DipRefresh(){LoadAjaxSilent('dip-host',DipUrl());}
function DipGoQueue(){var e=document.getElementById('dip-waiting');if(e){e.scrollIntoView({behavior:'smooth',block:'start'});}}
if(window.DipTimer){clearTimeout(window.DipTimer);}
window.DipTimer=setTimeout(function tick(){
 if(!document.getElementById('dip-panel')){window.DipTimer=null;return;}
 if(document.hidden||jQuery('.modal.in,.modal.show,.sweet-alert.visible,.swal2-container').length){window.DipTimer=setTimeout(tick,60000);return;}
 DipRefresh();
},60000);
</script>";
}
