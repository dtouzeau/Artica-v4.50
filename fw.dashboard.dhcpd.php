<?php
// Tableau de bord systeme - onglet « DHCP server » (fw.system.status.php, si EnableDHCPServer=1).
// Maquette stitch_artica_dhcp_dashboard (2026-11-16). Donnees, toutes deja produites par le demon :
//  - DHCPD_POOL_STATUS : JSON de dhcpd-pools en base64 (keaDHCPD4.processDHCPPools, toutes les
//    2 minutes) : summary, subnets[], trivia.lease_file_epoch_mtime ;
//  - DHCPD_LEASE_LIST_LAST : instantane des baux ACTIFS (dhcp-lease-list.pl --parsable), reconstruit
//    au mieux toutes les 30 minutes quand le fichier de baux change. Heures en UTC (recopiees du
//    fichier de baux) ; un bail dont la fin est passee a donc expire depuis l'instantane.
// Aucun historique, aucune action sur un bail : un clic ouvre la fiche de l'ordinateur.
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once(dirname(__FILE__)."/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Meme droit que l'onglet dans fw.system.status.php.
$users=new usersMenus();
if(!$users->AsAnAdministratorGeneric){
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS}"));
    exit;
}
if(isset($_GET["page"])){dhd_page();exit;}
dhd_start();

function dhd_start(){
    $page=CurrentPageName();
    echo "<div id='dashboard-dhcpd'></div><script>LoadAjax('dashboard-dhcpd','$page?page=yes');</script>";
}

// Texte venu d'un client DHCP ou du demon : echappe, accolades comprises.
function dhd_h($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}
function dhd_t($tok):string{
    static $c=array();
    if(!isset($c[$tok])){ $tpl=new template_admin(); $c[$tok]=$tpl->_ENGINE_parse_body($tok); }
    return $c[$tok];
}
function dhd_tn($tok,$v):string{ return str_replace("%s",(string)$v,dhd_t($tok)); }
function dhd_plain($tok):string{
    $s=str_replace(array("<br>","<br/>","<br />")," ",dhd_t($tok));
    return trim(preg_replace('/\s+/',' ',html_entity_decode($s,ENT_QUOTES,"UTF-8")));
}
function dhd_num($n):string{ return number_format(intval($n),0,"."," "); }
function dhd_pct($p):string{
    $p=floatval($p);
    if($p>0 && $p<1){ return number_format($p,1)."%"; }
    return round($p)."%";
}
// « il y a 3 h » / « dans 2 h » avec les jetons de la console (dkimg_ago_*) et ceux de la page.
function dhd_ago($t):string{
    $t=intval($t);
    if($t<=0){ return ""; }
    $d=time()-$t;
    if($d<60){ return dhd_t("{dkimg_ago_now}"); }
    if($d<3600){ return dhd_tn("{dkimg_ago_min}",intval($d/60)); }
    if($d<86400){ return dhd_tn("{dkimg_ago_h}",intval($d/3600)); }
    $days=intval($d/86400);
    if($days<14){ return $days==1?dhd_t("{dkimg_ago_d1}"):dhd_tn("{dkimg_ago_d}",$days); }
    return dhd_tn("{dkimg_ago_w}",intval($days/7));
}
function dhd_in($t):string{
    $d=intval($t)-time();
    if($d<3600){ return dhd_tn("{dhd_in_min}",max(1,intval($d/60))); }
    if($d<86400){ return dhd_tn("{dhd_in_h}",intval($d/3600)); }
    return dhd_tn("{dhd_in_d}",intval($d/86400));
}
// Heure UTC « 2026-11-16 02:02:08 » de dhcp-lease-list.pl -> epoch.
function dhd_utc($v):int{
    $v=trim((string)$v);
    if(!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',$v)){ return 0; }
    $t=strtotime("$v UTC");
    return $t===false?0:intval($t);
}

// Gravite d'occupation : ok < 80 %, warn >= 80 %, crit >= 90 % ou moins de 5 adresses libres.
function dhd_tone($defined,$used,$free):string{
    if($defined<=0){ return "na"; }
    $p=$used*100/$defined;
    if($p>=90 || $free<5){ return "crit"; }
    if($p>=80){ return "warn"; }
    return "ok";
}
function dhd_badge($tone):string{
    if($tone=="crit"){ return "<span class='dhd-badge dhd-b-crit'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {dhd_full}</span>"; }
    if($tone=="warn"){ return "<span class='dhd-badge dhd-b-warn'><i class='fas fa-circle-exclamation' aria-hidden='true'></i> {dhd_filling}</span>"; }
    if($tone=="ok"){ return "<span class='dhd-badge dhd-b-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> {dhd_normal}</span>"; }
    return "";
}

// Baux de l'instantane, un par MAC (le plus recent), tries du plus recent au plus ancien.
// Deux formats : JSON du demon (keaDHCPD4.DHCPDLeaseList) ou tableau serialise de l'ancien
// exec.dhcpd-leases.php (indexe par MAC, sans heure de debut).
function dhd_leases():array{
    $raw=trim(strval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_LEASE_LIST_LAST")));
    $out=array();
    if($raw==""){ return $out; }
    if(substr($raw,0,1)=="["){
        $json=json_decode($raw,true);
        if(!is_array($json)){ return $out; }
        foreach($json as $l){
            if(!is_array($l)){ continue; }
            $mac=strtolower(trim(strval(isset($l["MAC"])?$l["MAC"]:"")));
            if($mac==""){ continue; }
            $end=dhd_utc(isset($l["Ends"])?$l["Ends"]:"");
            if(isset($out[$mac]) && $out[$mac]["end"]>=$end){ continue; }
            $out[$mac]=array("mac"=>$mac,"ip"=>trim(strval(isset($l["IPAddr"])?$l["IPAddr"]:"")),
                "host"=>trim(strval(isset($l["Hostname"])?$l["Hostname"]:"")),
                "vendor"=>trim(strval(isset($l["Manufacturer"])?$l["Manufacturer"]:"")),
                "start"=>dhd_utc(isset($l["Starts"])?$l["Starts"]:""),"end"=>$end);
        }
    }else{
        $legacy=@unserialize($raw,array("allowed_classes"=>false));
        if(!is_array($legacy)){ return $out; }
        foreach($legacy as $mac=>$l){
            if(!is_array($l)){ continue; }
            $mac=strtolower(trim((string)$mac));
            $out[$mac]=array("mac"=>$mac,"ip"=>trim(strval(isset($l["IP"])?$l["IP"]:"")),
                "host"=>trim(strval(isset($l["HOTS"])?$l["HOTS"]:"")),"vendor"=>trim(strval(isset($l["MANU"])?$l["MANU"]:"")),
                "start"=>0,"end"=>dhd_utc(isset($l["END"])?$l["END"]:""));
        }
    }
    $out=array_values($out);
    usort($out,function($a,$b){
        if($a["start"]!=$b["start"]){ return $b["start"]-$a["start"]; }
        return $b["end"]-$a["end"];
    });
    return $out;
}

// Le modele, lu UNE fois.
function dhd_model():array{
    $m=array("pool"=>false,"defined"=>0,"used"=>0,"free"=>0,"touch"=>0,"touch_pct"=>0,"pct"=>0,
        "ranges"=>array(),"lease_mtime"=>0,"leases"=>array(),"latest"=>0);
    $json=json_decode(base64_decode(strval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DHCPD_POOL_STATUS"))),true);
    if(is_array($json) && isset($json["summary"]) && is_array($json["summary"])){
        $s=$json["summary"];
        $m["pool"]=true;
        $m["defined"]=intval(isset($s["defined"])?$s["defined"]:0);
        $m["used"]=intval(isset($s["used"])?$s["used"]:0);
        $m["free"]=intval(isset($s["free"])?$s["free"]:max(0,$m["defined"]-$m["used"]));
        $m["touch"]=intval(isset($s["touch_count"])?$s["touch_count"]:0);
        $m["touch_pct"]=floatval(isset($s["touch_percent"])?$s["touch_percent"]:0);
        $m["pct"]=$m["defined"]>0?$m["used"]*100/$m["defined"]:0;
        if(isset($json["subnets"]) && is_array($json["subnets"])){
            foreach($json["subnets"] as $r){
                if(!is_array($r)){ continue; }
                $d=intval(isset($r["defined"])?$r["defined"]:0);
                $u=intval(isset($r["used"])?$r["used"]:0);
                $range=trim(strval(isset($r["range"])?$r["range"]:""));
                if($range==""){ $range=trim(strval(isset($r["first_ip"])?$r["first_ip"]:"")." - ".strval(isset($r["last_ip"])?$r["last_ip"]:"")); }
                $loc=trim(strval(isset($r["location"])?$r["location"]:""));
                $m["ranges"][]=array("range"=>$range,"loc"=>$loc=="All networks"?"":$loc,"defined"=>$d,"used"=>$u,
                    "free"=>intval(isset($r["free"])?$r["free"]:max(0,$d-$u)));
            }
        }
        if(isset($json["trivia"]["lease_file_epoch_mtime"])){ $m["lease_mtime"]=intval($json["trivia"]["lease_file_epoch_mtime"]); }
    }
    $m["leases"]=dhd_leases();
    foreach($m["leases"] as $l){ if($l["start"]>$m["latest"]){ $m["latest"]=$l["start"]; } }
    return $m;
}

function dhd_ring($val,$max,$col,$size,$stroke):string{
    $r=50-$stroke/2-1;
    $c=round(2*M_PI*$r,2);
    $h="<svg width='$size' height='$size' viewBox='0 0 100 100' aria-hidden='true'>";
    $h.="<circle cx='50' cy='50' r='$r' fill='none' stroke='#e2e8f0' stroke-width='$stroke'/>";
    if($max>0 && $val>0){
        $off=round($c*(1-min(1,$val/$max)),2);
        $h.="<circle cx='50' cy='50' r='$r' fill='none' stroke='$col' stroke-width='$stroke' stroke-linecap='round'"
            ." stroke-dasharray='$c' stroke-dashoffset='$off' transform='rotate(-90 50 50)'/>";
    }
    return $h."</svg>";
}
function dhd_col($tone):string{
    $c=array("ok"=>"#0f766e","warn"=>"#d97706","crit"=>"#dc2626","na"=>"#94a3b8");
    return $c[$tone];
}

function dhd_fig($cls,$tok,$val,$help,$extra=""):string{
    return "<div class='dhd-fig $cls'><div class='dhd-fig-h'><span class='dhd-fig-l'>$tok</span> <span class='dhd-fig-v'>$val</span>$extra</div>"
        ."<div class='dhd-fig-d'>$help</div></div>";
}

function dhd_pool(array $m):string{
    if(!$m["pool"]){
        return "<div class='dhd-card dhd-empty'><i class='fas fa-circle-question' aria-hidden='true'></i><div>{dhd_no_pool}</div></div>";
    }
    $tone=dhd_tone($m["defined"],$m["used"],$m["free"]);
    // Un total a 14 % peut cacher une plage pleine : le pire etat d'une plage remonte ici.
    $rank=array("na"=>0,"ok"=>1,"warn"=>2,"crit"=>3);
    $rtone="na";
    foreach($m["ranges"] as $r){ $t=dhd_tone($r["defined"],$r["used"],$r["free"]); if($rank[$t]>$rank[$rtone]){ $rtone=$t; } }
    $badge=dhd_badge($tone);
    if($rank[$rtone]>$rank[$tone] && $rank[$rtone]>=2){
        $badge="<span class='dhd-badge dhd-b-$rtone'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {dhd_range_full}</span>";
    }
    $pct=$m["defined"]>0?dhd_pct($m["pct"]):"&mdash;";
    $label=dhd_h(dhd_plain("{dhd_pool_used}"))." $pct";
    $h=array();
    $h[]="<div class='dhd-card dhd-pool'><div class='dhd-pool-row'>";
    $h[]="<div class='dhd-donut' role='img' aria-label='$label'>".dhd_ring($m["used"],$m["defined"],dhd_col($tone),104,11)
        ."<span class='dhd-donut-v'>$pct</span></div>";
    $h[]="<div class='dhd-donut-t'><div class='dhd-donut-l'>{dhd_pool_used}</div>$badge</div>";
    $h[]="<div class='dhd-figs'>";
    $h[]=dhd_fig("",          "{dhd_in_use}",   $m["defined"]>0?dhd_num($m["used"]):"&mdash;",   "{dhd_in_use_help}");
    $h[]=dhd_fig("dhd-f-$tone","{dhd_free}",    $m["defined"]>0?dhd_num($m["free"]):"&mdash;",   "{dhd_free_help}");
    $h[]=dhd_fig("",          "{dhd_pool_size}",$m["defined"]>0?dhd_num($m["defined"]):"&mdash;","{dhd_pool_size_help}");
    $h[]=dhd_fig("dhd-fig-wide","{dhd_served}", $m["defined"]>0?dhd_num($m["touch"]):"&mdash;","{dhd_served_help}",
        $m["defined"]>0?" <span class='dhd-fig-p'>(".dhd_pct($m["touch_pct"]).")</span>":"");
    $h[]="</div></div>";
    if($m["lease_mtime"]>0){
        $h[]="<div class='dhd-foot'><i class='fas fa-clock' aria-hidden='true'></i> "
            .dhd_tn("{dhd_leasefile}",dhd_ago($m["lease_mtime"]))." <span class='dhd-sub'>(".date("Y-m-d H:i",$m["lease_mtime"]).")</span>"
            ." <span class='dhd-dot' aria-hidden='true'>&middot;</span> {dhd_pool_every}</div>";
    }
    $h[]="</div>";
    return implode("",$h);
}

function dhd_ranges(array $m):string{
    if(!$m["pool"]){ return ""; }
    $h=array();
    $h[]="<div class='dhd-card'><h3 class='dhd-h'>{dhd_ranges}</h3><div class='dhd-expl'>{dhd_ranges_explain}</div>";
    if(count($m["ranges"])==0){
        $h[]="<div class='dhd-none'><i class='fas fa-inbox' aria-hidden='true'></i> {dhd_no_range}</div></div>";
        return implode("",$h);
    }
    foreach($m["ranges"] as $r){
        $tone=dhd_tone($r["defined"],$r["used"],$r["free"]);
        $p=$r["defined"]>0?$r["used"]*100/$r["defined"]:0;
        $w=$r["defined"]>0?max($r["used"]>0?1:0,min(100,round($p,1))):0;
        $loc=$r["loc"]!=""?"<div class='dhd-r-loc'>".dhd_h($r["loc"])."</div>":"";
        $h[]="<div class='dhd-range'>"
            ."<div class='dhd-r-name'><span class='dhd-r-ip'>".dhd_h($r["range"])."</span>$loc</div>"
            ."<div class='dhd-bar' role='img' aria-label='".dhd_h(dhd_pct($p))."'><span class='dhd-bar-f dhd-bg-$tone' style='width:$w%'></span></div>"
            ."<div class='dhd-r-pct'>".($r["defined"]>0?number_format($p,1)."%":"&mdash;")."</div>"
            ."<div class='dhd-r-use'>".dhd_tn("{dhd_r_use}",dhd_num($r["used"])." / ".dhd_num($r["defined"]))."</div>"
            ."<div class='dhd-r-free dhd-f-$tone'>".dhd_tn("{dhd_r_free}",dhd_num($r["free"]))."</div>"
            ."<div class='dhd-r-b'>".dhd_badge($tone)."</div></div>";
    }
    $h[]="</div>";
    return implode("",$h);
}

function dhd_leases_card(array $m):string{
    $n=count($m["leases"]);
    $h=array();
    $h[]="<div class='dhd-card'><div class='dhd-lh'><div><h3 class='dhd-h'>{dhd_devices}</h3><div class='dhd-expl'>{dhd_devices_explain}</div>";
    $snap=$n==1?dhd_t("{dhd_snap_1}"):dhd_tn("{dhd_snap_n}",dhd_num($n));
    if($m["latest"]>0){ $snap.=" <span class='dhd-dot' aria-hidden='true'>&middot;</span> ".dhd_tn("{dhd_latest}",dhd_ago($m["latest"])); }
    $old=$m["latest"]>0 && (time()-$m["latest"])>3600*6;
    $h[]="<div class='dhd-snap".($old?" dhd-snap-old":"")."'><i class='fas fa-camera' aria-hidden='true'></i> $snap</div></div>";
    if($n>0){
        $h[]="<label class='dhd-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i><input type='search' class='dhd-q'"
            ." placeholder='".dhd_h(dhd_plain("{dhd_filter}"))."' aria-label='".dhd_h(dhd_plain("{dhd_filter}"))."' oninput='DhdFilter(this.value)'></label>";
    }
    $h[]="</div>";
    if($n==0){
        $h[]="<div class='dhd-none'><i class='fas fa-inbox' aria-hidden='true'></i> {dhd_no_lease}</div></div>";
        return implode("",$h);
    }
    $h[]="<div class='dhd-tw'><table class='dhd-tbl'><thead><tr><th scope='col'>{dhd_c_device}</th><th scope='col'>{dhd_c_ip}</th>"
        ."<th scope='col'>{dhd_c_mac}</th><th scope='col'>{dhd_c_vendor}</th><th scope='col'>{dhd_c_leased}</th><th scope='col'>{dhd_c_expires}</th></tr></thead><tbody>";
    $now=time();
    $open=dhd_h(dhd_plain("{dhd_open}"));
    foreach($m["leases"] as $l){
        $mac=dhd_h($l["mac"]);
        $who=$l["host"]!=""?dhd_h($l["host"]):"";
        $dev=$who!=""?"<span class='dhd-dev'>$who</span>":"<span class='dhd-noname'>{dhd_noname}</span>";
        $since=$l["start"]>0?"<span title='".date("Y-m-d H:i:s",$l["start"])."'>".dhd_ago($l["start"])."</span>":"<span class='dhd-mute'>&mdash;</span>";
        if($l["end"]<=0){ $exp="<span class='dhd-mute'>&mdash;</span>"; }
        elseif($l["end"]<=$now){ $exp="<span class='dhd-exp' title='".date("Y-m-d H:i:s",$l["end"])."'><i class='fas fa-hourglass-end' aria-hidden='true'></i> {dhd_expired}</span>"; }
        else{ $exp="<span class='dhd-in' title='".date("Y-m-d H:i:s",$l["end"])."'>".dhd_in($l["end"])."</span>"; }
        $q=dhd_h(strtolower($l["mac"]." ".$l["ip"]." ".$l["host"]." ".$l["vendor"]));
        $js="Loadjs('fw.edit.computer.php?mac=".urlencode($l["mac"])."&CallBackFunction=DhdReload')";
        $h[]="<tr data-q='$q' tabindex='0' role='link' aria-label='$open ".($who!=""?$who:$mac)."'"
            ." onclick=\"$js\" onkeydown=\"if(event.key=='Enter'){ $js }\">"
            ."<td><i class='fas fa-desktop dhd-ico' aria-hidden='true'></i> $dev</td>"
            ."<td class='dhd-mono'>".dhd_h($l["ip"])."</td><td class='dhd-mono dhd-mac'>$mac</td>"
            ."<td>".($l["vendor"]!=""?dhd_h($l["vendor"]):"<span class='dhd-mute'>&mdash;</span>")."</td>"
            ."<td class='dhd-nw'>$since</td><td class='dhd-nw'>$exp</td></tr>";
    }
    $h[]="<tr class='dhd-nomatch' style='display:none'><td colspan='6'><i class='fas fa-filter-circle-xmark' aria-hidden='true'></i> {dhd_no_match}</td></tr>";
    $h[]="</tbody></table></div>";
    $h[]="<div class='dhd-pager'><span class='dhd-count'></span><span><button type='button' class='dhd-pb' data-d='-1' onclick='DhdPage(-1)'>{dhd_prev}</button>"
        ."<button type='button' class='dhd-pb' data-d='1' onclick='DhdPage(1)'>{dhd_next}</button></span></div>";
    $h[]="</div>";
    return implode("",$h);
}

function dhd_style():string{
    return "<style>
.dhd{font-size:13px;color:#0f172a;line-height:1.45;margin-top:15px}
.dhd-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:20px 24px;margin-bottom:16px;box-shadow:0 1px 2px rgba(15,23,42,.05)}
.dhd-pool-row{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.dhd-donut{position:relative;width:104px;height:104px;flex:0 0 104px}
.dhd-donut-v{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:600}
.dhd-donut-t{flex:0 0 150px}.dhd-donut-l{font-size:14px;color:#475569;margin-bottom:4px}
.dhd-figs{flex:1 1 600px;display:flex;gap:0}
.dhd-fig{flex:1 1 0;padding:0 18px;border-left:1px solid #e2e8f0;min-width:0}
.dhd-fig-wide{flex:1.4 1 0}
.dhd-fig-h{display:flex;flex-wrap:wrap;align-items:baseline;column-gap:6px}.dhd-fig-v{white-space:nowrap}.dhd-fig-l{font-size:14px;font-weight:600}
.dhd-fig-v{font-size:28px;font-weight:600;line-height:1.1}
.dhd-fig-p{color:#64748b;font-size:14px}
.dhd-fig-d{color:#64748b;font-size:13px;margin-top:4px}
.dhd-f-ok .dhd-fig-l,.dhd-f-ok .dhd-fig-v,.dhd-r-free.dhd-f-ok{color:#0f766e}
.dhd-f-warn .dhd-fig-l,.dhd-f-warn .dhd-fig-v,.dhd-r-free.dhd-f-warn{color:#b45309}
.dhd-f-crit .dhd-fig-l,.dhd-f-crit .dhd-fig-v,.dhd-r-free.dhd-f-crit{color:#b91c1c}
.dhd-badge{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap}
.dhd-b-ok{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}.dhd-b-warn{background:#fffbeb;color:#b45309;border:1px solid #fde68a}
.dhd-b-crit{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
.dhd-foot{border-top:1px solid #f1f5f9;margin-top:18px;padding-top:12px;color:#64748b}.dhd-foot i{margin-right:4px}
.dhd-sub{color:#94a3b8}.dhd-dot{color:#94a3b8;margin:0 4px}
.dhd-h{font-size:17px;font-weight:600;margin:0 0 2px}
.dhd-expl{color:#64748b;margin-bottom:14px}
.dhd-range{display:flex;align-items:center;gap:20px;background:#f8fafc;border:1px solid #f1f5f9;border-radius:8px;padding:12px 18px;margin-bottom:8px}
.dhd-r-name{flex:0 0 260px;min-width:0}.dhd-r-ip{font-family:SFMono-Regular,Menlo,Consolas,monospace;font-size:14px;font-weight:600}
.dhd-r-loc{color:#64748b;font-size:13px}
.dhd-bar{flex:1 1 auto;height:8px;border-radius:999px;background:#e2e8f0;overflow:hidden;min-width:120px}
.dhd-bar-f{display:block;height:100%;border-radius:999px}
.dhd-bg-ok{background:#0f766e}.dhd-bg-warn{background:#d97706}.dhd-bg-crit{background:#dc2626}.dhd-bg-na{background:#94a3b8}
.dhd-r-pct{flex:0 0 56px;font-family:SFMono-Regular,Menlo,Consolas,monospace;color:#475569}
.dhd-r-use,.dhd-r-free{white-space:nowrap}.dhd-r-free{font-weight:600}
.dhd-lh{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}
.dhd-snap{color:#475569;margin:-6px 0 14px}.dhd-snap i{color:#94a3b8;margin-right:4px}
.dhd-snap-old{color:#b45309}.dhd-snap-old i{color:#d97706}
.dhd-search{position:relative;width:300px;flex:0 0 300px;margin:0;font-weight:400}
.dhd-search i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8}
.dhd-q{width:100%;height:36px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px 0 34px;font-size:13px;background:#fff}
.dhd-tw{overflow-x:auto}
.dhd-tbl{width:100%;border-collapse:collapse}
.dhd-tbl th{background:#f8fafc;color:#475569;font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;text-align:left;padding:11px 14px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.dhd-tbl td{padding:11px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.dhd-tbl tbody tr[data-q]{cursor:pointer}
.dhd-tbl tbody tr[data-q]:hover td{background:#f8fafc}
.dhd-tbl tbody tr[data-q]:focus-visible{outline:2px solid #2563eb;outline-offset:-2px}
.dhd-q:focus-visible,.dhd-pb:focus-visible{outline:2px solid #2563eb;outline-offset:2px}
.dhd-ico{color:#64748b;margin-right:6px}
.dhd-dev{font-size:14px;font-weight:600;overflow-wrap:anywhere}
.dhd-noname{color:#94a3b8;font-style:italic}
.dhd-mono{font-family:SFMono-Regular,Menlo,Consolas,monospace;white-space:nowrap}.dhd-mac{color:#64748b}
.dhd-nw{white-space:nowrap}.dhd-mute{color:#94a3b8}
.dhd-in{color:#047857;font-weight:600}
.dhd-exp{display:inline-flex;align-items:center;gap:5px;background:#f1f5f9;color:#475569;border-radius:6px;padding:2px 8px;font-size:13px}
.dhd-nomatch td{text-align:center;color:#64748b;padding:24px;cursor:default}
.dhd-pager{display:flex;align-items:center;justify-content:space-between;margin-top:14px;color:#64748b}
.dhd-pb{border:1px solid #cbd5e1;background:#fff;color:#0f172a;border-radius:8px;padding:6px 14px;font-size:13px;font-weight:600;margin-left:8px}
.dhd-pb:disabled{color:#94a3b8;background:#f8fafc;cursor:default}
.dhd-none{color:#64748b;padding:18px 0 6px}.dhd-none i{margin-right:6px}
.dhd-empty{display:flex;align-items:center;gap:12px;color:#475569}.dhd-empty>i{font-size:26px;color:#94a3b8}
@media (prefers-reduced-motion:no-preference){.dhd-card{animation:dhdIn .15s ease-out}}
@keyframes dhdIn{from{opacity:0}to{opacity:1}}
@media (max-width:1200px){.dhd-figs{flex-wrap:wrap;row-gap:16px}.dhd-fig-wide{flex:1 1 100%;border-left:0;padding-left:0}.dhd-r-name{flex-basis:200px}}
</style>";
}

// Filtre, pagination (50 lignes) et rechargement silencieux toutes les 2 minutes (rythme de
// dhcpd-pools). Le filtre et la page courante survivent au rechargement.
function dhd_js():string{
    $page=CurrentPageName();
    $T=json_encode(array("count"=>dhd_plain("{dhd_showing}")),JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE);
    return "<script>
window.DhdT=$T;window.DhdS=window.DhdS||{q:'',p:0};
function DhdApply(){
  var root=document.getElementById('dhd-root');if(!root){return;}
  var S=window.DhdS,q=S.q.toLowerCase(),rows=root.querySelectorAll('tr[data-q]'),hit=[],i;
  var inp=root.querySelector('.dhd-q');if(inp&&inp.value!=S.q){inp.value=S.q;}
  for(i=0;i<rows.length;i++){if(q.length<1||rows[i].getAttribute('data-q').indexOf(q)>=0){hit.push(rows[i]);}rows[i].style.display='none';}
  var per=50,pages=Math.max(1,Math.ceil(hit.length/per));if(S.p>=pages){S.p=pages-1;}if(S.p<0){S.p=0;}
  for(i=S.p*per;i<Math.min(hit.length,(S.p+1)*per);i++){hit[i].style.display='';}
  var nm=root.querySelector('.dhd-nomatch');if(nm){nm.style.display=(hit.length==0&&rows.length>0)?'':'none';}
  var c=root.querySelector('.dhd-count');if(c){c.textContent=hit.length?DhdT.count.replace('%a',S.p*per+1).replace('%b',Math.min(hit.length,(S.p+1)*per)).replace('%c',hit.length):'';}
  var b=root.querySelectorAll('.dhd-pb');for(i=0;i<b.length;i++){var d=parseInt(b[i].getAttribute('data-d'),10);b[i].disabled=(d<0&&S.p==0)||(d>0&&S.p>=pages-1);}
  var pg=root.querySelector('.dhd-pager');if(pg){pg.style.display=hit.length>per?'':'none';}
}
function DhdFilter(v){window.DhdS.q=String(v||'').trim();window.DhdS.p=0;DhdApply();}
function DhdPage(d){window.DhdS.p+=d;DhdApply();}
function DhdReload(){if(document.getElementById('dashboard-dhcpd')){LoadAjaxSilent('dashboard-dhcpd','$page?page=yes');}}
if(window.DhdTimer){clearInterval(window.DhdTimer);}
window.DhdTimer=setInterval(function(){
  if(!document.getElementById('dhd-root')){clearInterval(window.DhdTimer);window.DhdTimer=null;return;}
  if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.ui-dialog:not([style*=\"display: none\"])')){return;}
  DhdReload();
},120000);
DhdApply();
</script>";
}

function dhd_page(){
    $tpl=new template_admin();
    $m=dhd_model();
    $h=array();
    $h[]="<div class='dhd' id='dhd-root'>";
    $h[]=dhd_pool($m);
    $h[]=dhd_ranges($m);
    $h[]=dhd_leases_card($m);
    $h[]="</div>";
    echo dhd_style().$tpl->_ENGINE_parse_body(implode("",$h)).dhd_js()."<script>NoSpinner();</script>";
}
