<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__).'/ressources/class.modsectools.inc');
include_once(dirname(__FILE__).'/ressources/class.ip2host.inc');
include_once(dirname(__FILE__).'/ressources/class.statistics_appliance.inc');

$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
// Plafond de la recherche des Threats : defini AVANT l'aiguillage (un define() en milieu de fichier
// n'est jamais execute quand ?search= sort par exit plus haut).
define("CSBLK_LIMIT",250);
define("CSTH_DB","/home/artica/SQLITE/crowdsec-events.db");
define("CSTH_MAX_ADDR",200);
define("CSTH_MAX_ALERTS",250);
define("CSTH_HOT",50);
define("CSEV_DB","/home/artica/SQLITE/crowdsec.db");
define("CSEV_MAX",500);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["unlink-cloud-js"])){unlink_cloud_js();exit;}
if(isset($_POST["unlink-cloud"])){unlink_cloud_perform();exit;}
if(isset($_GET["threads-js"])){threads_js();exit;}
if(isset($_GET["threats-popup"])){threads_popup();exit;}
if(isset($_GET["threats-list-search"])){threads_search();exit;}
if(isset($_GET["alerts-form"])){alerts_form();exit;}
if(isset($_GET["csth-host"])){csth_host();exit;}
if(isset($_GET["csth-ip-alerts"])){csth_ip_alerts();exit;}
if(isset($_GET["csth-trust-js"])){csth_trust_js();exit;}
if(isset($_GET["csth-trust-popup"])){csth_trust_popup();exit;}
if(isset($_GET["artica-search"])){artica_search();exit;}
if(isset($_GET["csev-host"])){csev_host();exit;}
if(isset($_GET["status-top"])){main_top();exit;}
if(isset($_GET["status-start"])){main_status();exit;}
if(isset($_GET["status"])){crowdsec_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["whitelist-js"])){whitelist_js();exit;}
if(isset($_POST["whitelist-ip"])){whitelist_ip_perform();exit;}
if(isset($_GET["resolv-this"])){resolv_this();exit;}

if(isset($_GET["restart"])){restart_js();exit;}
if(isset($_GET["restart-perform"])){restart_perform();exit;}

if(isset($_GET["join-cloud-js"])){join_cloud_js();exit;}
if(isset($_GET["join-cloud-popup"])){join_cloud_popup();exit;}

if(isset($_POST["CROWDSEC_CLOUD_TOKEN"])){console_token_save();exit;}
if(isset($_GET["config-center"])){main_config();exit;}
if(isset($_POST["csset-switch"])){csset_switch_save();exit;}
if(isset($_POST["csset-login"])){csset_login_save();exit;}
if(isset($_POST["csset-feature"])){csset_feature_save();exit;}
if(isset($_GET["threats-form"])){threats_form();exit;}
if(isset($_GET["artica-form"])){artica_form();exit;}
if(isset($_GET["search"])){threats_search();exit;}
if(isset($_GET["decisions-list-add-js"])){decisions_list_add_js();exit;}
if(isset($_GET["decisions-list-add-popup"])){decisions_list_add_popup();exit;}
if(isset($_POST["decision-add"])){decisions_list_add_save();exit;}
if(isset($_POST["csblk-unblock"])){csblk_unblock();exit;}
page();

function tabs():bool{
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $array["{status}"]="$page?status-start=yes";
    $array["{csblk_tab}"]="$page?threats-form=yes";
    $array["{threats}"]="$page?artica-form=yes";
    $array["{events}"]="$page?alerts-form=yes";
    $array["{collections}"]="fw.crowdsec.collections.php";
    $array["{service_events}"]="fw.crowdsec.events.php?form=yes";
    echo $tpl->tabs_default($array);
    return true;
}

function whitelist_ip_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ipaddr=trim($_POST["whitelist-ip"]);
    // L'adresse part dans networks_infos (sqlite) et dans une colonne inet
    // (PostgreSQL) : une valeur qui n'est pas une IP faisait echouer la seconde.
    if(!filter_var($ipaddr,FILTER_VALIDATE_IP)){
        echo $tpl->javascript_parse_text("{invalid_ip_address} $ipaddr");
        return false;
    }
    $cdir=$ipaddr."/32";
    $netinfos="Whitelist From CrowdSec";
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    // Pentest 2026-11-04 : whitelist-ip (POST) etait concatene dans la requete.
    $q->QUERY_SQL_PARAMS("DELETE FROM networks_infos WHERE ipaddr=?",array($cdir));
    if(!$q->FIELD_EXISTS("networks_infos","vpn")){
        // Le nom de la table manquait : l'ALTER echouait a chaque appel.
        $q->QUERY_SQL("ALTER TABLE networks_infos ADD vpn INTEGER NOT NULL DEFAULT 0");
    }


    $q->QUERY_SQL_PARAMS("INSERT INTO networks_infos (enabled,scannable,netinfos,ipaddr,pingable,pinginterval,noping,yesping,prcping,trusted) 
			VALUES('1','0',?,?,'0','0','1','0','0','1')",array($netinfos,$cdir));
    if(!$q->ok){
        echo $q->mysql_error;
        return false;
    }

    // crowdsec_blacklists (miroir de l'ipset, crowdsec.IpSettoDB) : la requete
    // visait crowdsec_blacklist, une table qui n'existe pas, et l'adresse restait
    // affichee dans l'onglet Firewall Rules jusqu'a la synchronisation suivante.
    $q=new postgres_sql();
    if($q->TABLE_EXISTS("crowdsec_blacklists")){
        $q->QUERY_SQL_PARAMS("DELETE FROM crowdsec_blacklists WHERE ipaddr=$1::inet",array($ipaddr));
    }


    $GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/whitelist");
    $ipaddrenc=urlencode($ipaddr);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/decision/delete/$ipaddrenc");
    admin_tracks_post("Remove $cdir - $netinfos from CrowdSec and add it to Trusted Network");
    $memcached=new lib_memcached();
    $memcached->saveKey("WebConsoleTrustedNet","");
    return true;
}

function crowdsec_blacklisted():int{
    $q=new lib_sqlite("/home/artica/SQLITE/crowdsec-events.db");
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM alerts");
    return intval($ligne["tcount"]);
}

// ---------------------------------------------------------------------------
// Bande de synthese de l'onglet Status (maquette stitch_crowdsec_status_summary).
// Source : /crowdsec/stats = `cscli metrics -o json`, compteurs du processus
// CrowdSec : ils repartent de zero a chaque redemarrage du moteur. L'anciennete
// du moteur vient de l'INI de /crowdsec/status. La duree de ban est celle du
// profil genere par articarest (crowdsec/Config.go : 14400s, fixe).
// Rechargee par crowdsec_status() a chaque rafraichissement de la colonne gauche.
// ---------------------------------------------------------------------------
function cssum_tr($token):string{
    static $cache=array();
    if(!isset($cache[$token])){ $tpl=new template_admin(); $cache[$token]=$tpl->_ENGINE_parse_body("{".$token."}"); }
    $t=$cache[$token];
    $args=func_get_args(); array_shift($args);
    foreach($args as $a){ $p=strpos($t,"%s"); if($p===false){break;} $t=substr_replace($t,(string)$a,$p,2); }
    return $t;
}

// Texte d'un token pour un attribut title="" : sans <br>, guillemets neutralises.
function cssum_attr($token):string{
    $args=func_get_args();
    $t=call_user_func_array("cssum_tr",$args);
    return str_replace('"',"&quot;",trim(preg_replace('#<br\s*/?>#i'," ",$t)));
}

function cssum_esc($v):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}

function cssum_num($n):string{ return number_format((float)$n,0,".",","); }

function cssum_short($n):string{
    $n=(float)$n;
    if($n>=1000000000){return round($n/1000000000,1)." G";}
    if($n>=1000000){return round($n/1000000,1)." M";}
    if($n>=10000){return round($n/1000,1)." K";}
    return cssum_num($n);
}

function cssum_bytes($b):string{
    $b=(float)$b; $u=array("B","KB","MB","GB","TB"); $i=0;
    while($b>=1024 && $i<count($u)-1){ $b=$b/1024; $i++; }
    return ($i==0?cssum_num($b):round($b,1))." ".$u[$i];
}

// "3 days 4 hours 12 minutes" -> "3d 4h" (deux unites au plus).
function cssum_uptime($s):string{
    $units=array("year"=>"y","month"=>"mo","week"=>"w","day"=>"d","hour"=>"h","min"=>"min","sec"=>"s");
    $out=array();
    if(preg_match_all('#([0-9]+)\s*([a-zA-Z]+)#',(string)$s,$re,PREG_SET_ORDER)){
        foreach($re as $m){
            if(intval($m[1])==0){continue;}
            foreach($units as $needle=>$abbr){
                if(strpos(strtolower($m[2]),$needle)===0){ $out[]=$m[1].$abbr; break; }
            }
            if(count($out)==2){break;}
        }
    }
    return @implode(" ",$out);
}

// Scenarios courants -> token en mots courants. Un scenario inconnu garde son nom technique.
function cssum_scenario_label($name):string{
    $short=strtolower((string)$name);
    if(strpos($short,"/")!==false){ $short=substr($short,strpos($short,"/")+1); }
    $map=array(
        "ssh-bf"=>"cssum_sc_ssh_bf","ssh-slow-bf"=>"cssum_sc_ssh_slow","ssh-cve-2024-6387"=>"cssum_sc_cve",
        "http-probing"=>"cssum_sc_http_probing","http-crawl-non_statics"=>"cssum_sc_crawl",
        "iptables-scan-multi_ports"=>"cssum_sc_portscan","http-bad-user-agent"=>"cssum_sc_bad_ua",
        "http-sensitive-files"=>"cssum_sc_sensitive","http-path-traversal-probing"=>"cssum_sc_traversal",
        "http-sqli-probing"=>"cssum_sc_sqli","http-xss-probing"=>"cssum_sc_xss",
        "http-backdoors-attempts"=>"cssum_sc_backdoor","http-admin-interface-probing"=>"cssum_sc_admin",
        "http-generic-bf"=>"cssum_sc_web_bf","http-bf-wordpress_bf"=>"cssum_sc_wp_bf",
        "http-wordpress-scan"=>"cssum_sc_wp_scan","http-open-proxy"=>"cssum_sc_open_proxy",
    );
    if(isset($map[$short])){ return cssum_tr($map[$short]); }
    if(strpos($short,"cve")!==false){ return cssum_tr("cssum_sc_cve"); }
    return "";
}

function cssum_model():array{
    $M=array("ok"=>false,"error"=>"","lines"=>0,"unparsed"=>0,"sources"=>array(),
        "alerts"=>0,"scenarios"=>array(),"blocked"=>0,"origins"=>array("here"=>0,"community"=>0,"you"=>0,"other"=>0),
        "bouncer"=>false,"dropped_pk"=>0,"dropped_b"=>0,"processed_pk"=>0,"processed_b"=>0,
        "uptime"=>"","cloud"=>"off");

    $token=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_CLOUD_TOKEN"));
    $enrolled=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_ENROLLED"));
    if($token!==""){ $M["cloud"]=strlen($enrolled)>8?"on":"wait"; }

    $st=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/status"),true);
    if(is_array($st) && !empty($st["Status"]) && isset($st["Info"])){
        $ini=new Bs_IniHandler(); $ini->loadString((string)$st["Info"]);
        if(isset($ini->_params["APP_CROWDSEC"]["uptime"]) && intval($ini->_params["APP_CROWDSEC"]["running"] ?? 0)==1){
            $M["uptime"]=cssum_uptime($ini->_params["APP_CROWDSEC"]["uptime"]);
        }
    }

    $raw=(string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/stats");
    $json=json_decode($raw,true);
    if(!is_array($json)){ $M["error"]="articarest: no answer from /crowdsec/stats"; return $M; }
    if(empty($json["Status"])){ $M["error"]=trim((string)($json["Error"] ?? "")); return $M; }
    $X=is_array($json["Metrics"] ?? null)?$json["Metrics"]:array();
    $M["ok"]=true;

    if(is_array($X["acquisition"] ?? null)){
        foreach($X["acquisition"] as $src=>$a){
            if(!is_array($a)){continue;}
            $M["lines"]+=intval($a["parsed"] ?? 0);
            $M["unparsed"]+=intval($a["unparsed"] ?? 0);
            $M["sources"][]=(string)$src;
        }
    }
    if(is_array($X["alerts"] ?? null)){
        foreach($X["alerts"] as $sc=>$n){
            $n=intval($n); if($n<=0){continue;}
            $M["alerts"]+=$n;
            $M["scenarios"][(string)$sc]=$n;
        }
        arsort($M["scenarios"]);
    }
    // decisions[raison][origine][action] = nombre de decisions actives.
    if(is_array($X["decisions"] ?? null)){
        foreach($X["decisions"] as $reason=>$byOrigin){
            if(!is_array($byOrigin)){continue;}
            foreach($byOrigin as $origin=>$byAction){
                $n=0;
                if(is_array($byAction)){ foreach($byAction as $c){ $n+=intval($c); } }
                $o=strtolower((string)$origin);
                if($o==="crowdsec"){$k="here";}
                elseif($o==="capi" || strpos($o,"lists")===0){$k="community";}
                elseif($o==="cscli" || $o==="console" || $o==="cscli-import"){$k="you";}
                else{$k="other";}
                $M["origins"][$k]+=$n;
                $M["blocked"]+=$n;
            }
        }
    }
    // bouncers[nom][origine] = {processed, dropped} : somme sur tout.
    if(is_array($X["bouncers"] ?? null)){
        foreach($X["bouncers"] as $byKey){
            if(!is_array($byKey)){continue;}
            foreach($byKey as $d){
                if(!is_array($d)){continue;}
                $M["bouncer"]=true;
                if(is_array($d["dropped"] ?? null)){ $M["dropped_pk"]+=floatval($d["dropped"]["packet"] ?? 0); $M["dropped_b"]+=floatval($d["dropped"]["byte"] ?? 0); }
                if(is_array($d["processed"] ?? null)){ $M["processed_pk"]+=floatval($d["processed"]["packet"] ?? 0); $M["processed_b"]+=floatval($d["processed"]["byte"] ?? 0); }
            }
        }
    }
    return $M;
}

function cssum_since(array $M):string{
    return $M["uptime"]!==""?cssum_attr("cssum_since_ago",$M["uptime"]):cssum_attr("cssum_since_start");
}

function cssum_chip($ico,$text):string{
    return "<span class='cssum-chip'><span class='cssum-chip-i'><i class='$ico' aria-hidden='true'></i></span>$text</span>";
}

function cssum_intro(array $M):string{
    $c=array();
    $c[]=cssum_chip("fas fa-clock",$M["uptime"]!==""?cssum_tr("cssum_chip_since",$M["uptime"]):"{cssum_chip_since_start}");
    $c[]=cssum_chip("fas fa-hourglass-half","{cssum_chip_ban}");
    $c[]=cssum_chip("fas fa-eye","{cssum_chip_logs}");
    if($M["origins"]["community"]>0){ $c[]=cssum_chip("fas fa-users","{cssum_chip_community}"); }
    return "<section class='cssum-intro'><h3>{cssum_title}</h3><p>{cssum_intro}</p><div class='cssum-chips'>".@implode("",$c)."</div></section>";
}

function cssum_tile_head($ico,$tone,$label):string{
    return "<div class='cssum-th'><span class='cssum-ti cssum-$tone'><i class='$ico' aria-hidden='true'></i></span><span class='cssum-tl'>$label</span></div>";
}

function cssum_tile_lines(array $M):string{
    $n=$M["lines"]; $src=count($M["sources"]);
    $names=array();
    foreach($M["sources"] as $s){ $s=preg_replace('#^[a-z]+:#','',$s); $names[]=basename($s); }
    $h="<article class='cssum-tile'>".cssum_tile_head("fas fa-file-lines","info","{cssum_lines}");
    if($n==0){
        $h.="<div class='cssum-big cssum-zero' title=\"".cssum_since($M)."\">{cssum_none_yet}</div><div class='cssum-sub'>{cssum_lines_wait}</div>";
    }else{
        $h.="<div class='cssum-big' title=\"".cssum_since($M)."\">".cssum_num($n)."</div>";
        $h.="<div class='cssum-sub'>".($src==1?"{cssum_lines_src1}":cssum_tr("cssum_lines_src",$src))."</div>";
        $h.="<div class='cssum-tech'>{cssum_lines_tech}</div>";
        if($M["unparsed"]>0){
            $h.="<div class='cssum-note' title=\"".cssum_attr("cssum_unparsed_tip")."\"><span class='cssum-note-i'><i class='fas fa-circle-info' aria-hidden='true'></i></span>".cssum_tr("cssum_unparsed",cssum_num($M["unparsed"]))."</div>";
        }
    }
    if(count($names)>0){
        $shown=array_slice($names,0,3);
        $more=count($names)>3?" +".(count($names)-3):"";
        $h.="<div class='cssum-foot cssum-mono' title=\"".cssum_esc(@implode(", ",$M["sources"]))."\">".cssum_esc(@implode(", ",$shown)).$more."</div>";
    }
    return $h."</article>";
}

function cssum_scenario_row($name,$n,$hidden):string{
    $label=cssum_scenario_label($name);
    $tech=cssum_esc($name);
    $main=$label!==""?$label:"<span class='cssum-mono'>$tech</span>";
    $sub=$label!==""?"<span class='cssum-mono cssum-sc-tech'>$tech</span>":"";
    return "<li".($hidden?" class='cssum-more' hidden":"")."><span class='cssum-sc-n'>$main$sub</span><span class='cssum-sc-c'>".cssum_num($n)."</span></li>";
}

function cssum_tile_attacks(array $M):string{
    $n=$M["alerts"]; $types=count($M["scenarios"]);
    $tone=$n>0?"warn":"ok";
    $h="<article class='cssum-tile'>".cssum_tile_head("fas fa-user-secret",$tone,"{cssum_attacks}");
    if($n==0){
        $h.="<div class='cssum-big cssum-good' title=\"".cssum_since($M)."\">{cssum_none}</div><div class='cssum-sub'>{cssum_attacks_none}</div>";
        return $h."</article>";
    }
    $h.="<div class='cssum-big cssum-t-warn' title=\"".cssum_since($M)."\">".cssum_num($n)."</div>";
    $h.="<div class='cssum-sub'>".($types==1?"{cssum_attacks_type1}":cssum_tr("cssum_attacks_types",$types))."</div><ul class='cssum-sc'>";
    $i=0;
    foreach($M["scenarios"] as $sc=>$c){ $h.=cssum_scenario_row($sc,$c,$i>=3); $i++; }
    $h.="</ul>";
    if($types>3){
        $h.="<button type='button' class='cssum-link cssum-foot' aria-expanded='false' OnClick='CssumMore(this)'>".cssum_tr("cssum_show_all",$types)."</button>";
    }
    return $h."</article>";
}

function cssum_tile_blocked(array $M):string{
    $page=CurrentPageName();
    $n=$M["blocked"];
    $h="<article class='cssum-tile'>".cssum_tile_head("fas fa-ban",$n>0?"err":"neutral","{cssum_blocked}");
    $h.="<div class='cssum-big".($n>0?"":" cssum-zero")."' title=\"".cssum_attr("cssum_blocked_tip")."\">".($n>0?cssum_num($n):"{cssum_none}")."</div>";
    $h.="<div class='cssum-sub'>".($n>0?"{cssum_blocked_sub}":"{cssum_blocked_none}")."</div>";
    if($n>0){
        $parts=array("here"=>"cssum_o_here","community"=>"cssum_o_community","you"=>"cssum_o_you","other"=>"cssum_o_other");
        $bar=""; $leg="";
        foreach($parts as $k=>$tok){
            $v=$M["origins"][$k];
            if($v<=0 && $k==="other"){continue;}
            if($v>0){
                // Une part non nulle reste visible (2 % au moins) sans fausser la legende chiffree.
                $pct=max(2,round($v*100/$n,1));
                $bar.="<span class='cssum-bar-$k' style='flex-grow:$pct' title=\"".cssum_attr($tok)." : ".cssum_num($v)."\"></span>";
            }
            $leg.="<li><span class='cssum-dot cssum-bar-$k' aria-hidden='true'></span><span>{{$tok}}</span><b>".cssum_num($v)."</b></li>";
        }
        $h.="<div class='cssum-bar' role='img' aria-label=\"".cssum_attr("cssum_blocked_sub")."\">$bar</div><ul class='cssum-leg'>$leg</ul>";
    }
    $h.="<button type='button' class='cssum-btn cssum-btn-p cssum-foot' OnClick=\"Loadjs('$page?threads-js=yes')\"><span class='cssum-btn-i'><i class='fas fa-list' aria-hidden='true'></i></span>{cssum_see_blocked}</button>";
    return $h."</article>";
}

function cssum_tile_traffic(array $M):string{
    $h="<article class='cssum-tile'>".cssum_tile_head("fas fa-shield-halved","ok","{cssum_traffic}");
    if(!$M["bouncer"]){
        return $h."<div class='cssum-big cssum-zero'>—</div><div class='cssum-sub'>{cssum_traffic_na}</div></article>";
    }
    $pk=$M["dropped_pk"];
    $h.="<div class='cssum-big' title=\"".cssum_since($M)."\">".cssum_short($pk)." <small>{cssum_packets}</small></div>";
    $h.="<div class='cssum-sub'>".cssum_tr("cssum_traffic_bytes",cssum_bytes($M["dropped_b"]))."</div>";
    if($M["processed_pk"]>0){
        $pct=min(100,round($pk*100/$M["processed_pk"],1));
        $h.="<div class='cssum-ratio'><svg class='cssum-donut' viewBox='0 0 36 36' role='img' aria-label='$pct %'>"
            ."<circle cx='18' cy='18' r='15.9155' fill='none' stroke='#e2e8f0' stroke-width='4'/>"
            ."<circle cx='18' cy='18' r='15.9155' fill='none' stroke='#15803d' stroke-width='4' stroke-dasharray='$pct 100' transform='rotate(-90 18 18)'/></svg>"
            ."<span>".cssum_tr("cssum_traffic_ratio","<b>".cssum_short($pk)."</b>","<b>".cssum_short($M["processed_pk"])."</b>")."</span></div>";
    }
    return $h."<div class='cssum-help cssum-foot'>{cssum_traffic_help}</div></article>";
}

function cssum_tiles(array $M):string{
    return "<div class='cssum-tiles'>".cssum_tile_lines($M).cssum_tile_attacks($M).cssum_tile_blocked($M).cssum_tile_traffic($M)."</div>";
}

function cssum_cloud(array $M):string{
    $page=CurrentPageName();
    $st=array("off"=>array("neutral","fas fa-circle-minus","cssum_cloud_off","cssum_cloud_off_text"),
              "wait"=>array("warn","fas fa-hourglass-half","cssum_cloud_wait","cssum_cloud_wait_text"),
              "on"=>array("ok","fas fa-circle-check","cssum_cloud_on","cssum_cloud_on_text"));
    $s=$st[$M["cloud"]];
    if($M["cloud"]==="off"){
        $act="<button type='button' class='cssum-btn cssum-btn-p' OnClick=\"Loadjs('$page?join-cloud-js=yes')\"><span class='cssum-btn-i'><i class='fas fa-link' aria-hidden='true'></i></span>{cssum_connect}</button>";
        $help="<span class='cssum-help'>{cssum_cloud_off_help}</span>";
    }else{
        $act="<button type='button' class='cssum-btn' OnClick=\"Loadjs('$page?unlink-cloud-js=yes')\"><span class='cssum-btn-i'><i class='fas fa-link-slash' aria-hidden='true'></i></span>{cssum_disconnect}</button>";
        $help="";
    }
    return "<section class='cssum-cloud'><span class='cssum-ci'><i class='fas fa-cloud' aria-hidden='true'></i></span>"
        ."<div class='cssum-cbody'><div class='cssum-ctitle'><b>{cssum_cloud_title}</b>"
        ."<span class='cssum-pill cssum-$s[0]'><span class='cssum-pill-i'><i class='$s[1]' aria-hidden='true'></i></span>{{$s[2]}}</span></div>"
        ."<p>{{$s[3]}}</p>$help</div><div class='cssum-cact'>$act</div></section>";
}

function cssum_error(array $M):string{
    $page=CurrentPageName();
    $det=$M["error"]!==""?"<details class='cssum-det'><summary>{cssum_err_details}</summary><pre class='cssum-mono'>".cssum_esc($M["error"])."</pre></details>":"";
    return "<section class='cssum-err' role='alert'><span class='cssum-err-i'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i></span>"
        ."<div><h4>{cssum_err_title}</h4><p>{cssum_err_text}</p>$det"
        ."<button type='button' class='cssum-btn' OnClick=\"LoadAjaxSilent('crowdsec-top','$page?status-top=yes')\"><span class='cssum-btn-i'><i class='fas fa-rotate' aria-hidden='true'></i></span>{cssum_retry}</button></div></section>";
}

function cssum_js():string{
    return "<script>window.CssumMore=function(b){var c=b.parentNode,li=c.querySelectorAll('.cssum-more'),open=b.getAttribute('aria-expanded')!=='true';for(var i=0;i<li.length;i++){li[i].hidden=!open;}b.setAttribute('aria-expanded',open?'true':'false');};</script>";
}

function cssum_style():string{
    $A="#16a34a";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){ $a=brand_ui_accents(brand_palette()); if(!empty($a["accent"])){$A=$a["accent"];} }
    return "<style>
#cssum{color:#0f172a;font-size:14px;line-height:1.45;margin-bottom:16px}
#cssum *{box-sizing:border-box}
#cssum h3,#cssum h4,#cssum p,#cssum ul{margin:0}
#cssum ul{padding:0;list-style:none}
#cssum button{font-family:inherit}
#cssum button:focus-visible,#cssum summary:focus-visible{outline:2px solid $A;outline-offset:2px}
.cssum-mono{font-family:'JetBrains Mono',Menlo,Consolas,monospace}
.cssum-intro{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;margin-bottom:16px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
#cssum .cssum-intro h3{font-size:16px;font-weight:600;margin-bottom:4px}
.cssum-intro p{font-size:13px;color:#475569}
.cssum-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.cssum-chip{display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;color:#334155;border-radius:999px;padding:4px 12px;font-size:13px}
.cssum-chip-i{display:inline-flex;font-size:12px;color:#475569}
.cssum-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:16px}
.cssum-tile{display:flex;flex-direction:column;gap:6px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;box-shadow:0 1px 2px rgba(15,23,42,.06);min-width:0}
.cssum-th{display:flex;align-items:center;gap:10px;margin-bottom:4px}
.cssum-ti{flex:0 0 36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:15px}
.cssum-tl{font-size:13px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.03em;line-height:1.3}
.cssum-info{background:#eff6ff;color:#1d4ed8}.cssum-warn{background:#fef3c7;color:#b45309}.cssum-err{background:#fef2f2;color:#b91c1c}
.cssum-ok{background:#dcfce7;color:#15803d}.cssum-neutral{background:#f1f5f9;color:#475569}
.cssum-big{font-size:28px;font-weight:700;line-height:1.15}
.cssum-big small{font-size:13px;font-weight:500;color:#64748b}
.cssum-zero{color:#64748b;font-size:22px}
.cssum-good{color:#15803d;font-size:22px}
.cssum-t-warn{color:#b45309}
.cssum-sub{font-size:13px;color:#475569}
.cssum-tech{font-size:13px;color:#94a3b8}
.cssum-note{font-size:13px;color:#64748b;display:flex;align-items:center;gap:6px;cursor:help}
.cssum-note-i{display:inline-flex;font-size:12px}
.cssum-foot{margin-top:auto;padding-top:10px;border-top:1px solid #f1f5f9;font-size:13px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cssum-sc{display:flex;flex-direction:column;gap:6px;margin-top:4px}
.cssum-sc li{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;font-size:13px}
.cssum-sc li[hidden]{display:none}
.cssum-sc-n{display:flex;flex-direction:column;min-width:0;overflow-wrap:anywhere}
.cssum-sc-tech{font-size:13px;color:#94a3b8}
.cssum-sc-c{font-weight:600;font-variant-numeric:tabular-nums}
.cssum-link{border:0;border-top:1px solid #f1f5f9;background:none;color:$A;font-weight:600;text-align:left;cursor:pointer;padding-left:0;padding-right:0}
.cssum-bar{display:flex;gap:2px;height:6px;border-radius:999px;overflow:hidden;background:#f1f5f9;margin-top:6px}
.cssum-bar span{display:block;flex-basis:0;min-width:3px}
.cssum-bar-here{background:#f59e0b}.cssum-bar-community{background:#dc2626}.cssum-bar-you{background:#2563eb}.cssum-bar-other{background:#64748b}
.cssum-leg{display:flex;flex-direction:column;gap:4px;margin-top:6px}
.cssum-leg li{display:flex;align-items:center;gap:8px;font-size:13px;color:#475569}
.cssum-leg b{margin-left:auto;color:#0f172a;font-variant-numeric:tabular-nums}
.cssum-dot{width:8px;height:8px;border-radius:50%;flex:0 0 8px}
.cssum-ratio{display:flex;align-items:center;gap:10px;font-size:13px;color:#475569;margin-top:4px}
.cssum-donut{width:40px;height:40px;flex:0 0 40px}
.cssum-help{font-size:13px;color:#64748b}
.cssum-help.cssum-foot{white-space:normal}
.cssum-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.cssum-btn:hover{border-color:#94a3b8}
.cssum-btn-p{background:$A;border-color:$A;color:#fff}
.cssum-btn-p:hover{filter:brightness(.95);border-color:$A}
.cssum-btn.cssum-foot{border-top:1px solid $A;color:#fff;margin-top:auto;padding-top:8px}
.cssum-btn-i{display:inline-flex;font-size:12px}
.cssum-cloud{display:flex;align-items:center;gap:16px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.cssum-ci{flex:0 0 44px;height:44px;border-radius:50%;background:#f1f5f9;color:#475569;display:inline-flex;align-items:center;justify-content:center;font-size:18px}
.cssum-cbody{flex:1;min-width:0}
.cssum-ctitle{display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:2px}
.cssum-ctitle b{font-size:15px;font-weight:600}
.cssum-cbody p{font-size:13px;color:#475569}
.cssum-cact{flex:0 0 auto}
.cssum-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:3px 10px;font-size:13px;font-weight:600}
.cssum-pill-i{display:inline-flex;font-size:12px}
section.cssum-err{display:flex;gap:14px;align-items:flex-start;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:18px 20px;margin-bottom:16px;color:#7f1d1d}
.cssum-err-i{display:inline-flex;font-size:22px;color:#b91c1c}
#cssum section.cssum-err h4{font-size:15px;font-weight:600;color:#991b1b;margin-bottom:4px}
#cssum section.cssum-err p{font-size:13px;margin-bottom:10px}
.cssum-det{margin-bottom:12px;font-size:13px}
.cssum-det summary{cursor:pointer;font-weight:600}
.cssum-det pre{white-space:pre-wrap;overflow-wrap:anywhere;background:#fff;border:1px solid #fecaca;border-radius:8px;padding:10px;margin:8px 0 0;font-size:13px;color:#0f172a}
@media (prefers-reduced-motion:reduce){#cssum *{transition:none!important}}
</style>";
}

function main_top():bool{
    $tpl=new template_admin();
    $M=cssum_model();
    $body=$M["ok"]?cssum_tiles($M):cssum_error($M);
    $html="<div id='cssum'>".cssum_intro($M).$body.cssum_cloud($M)."</div>";
    // CSS et JS HORS de _ENGINE_parse_body : la regex des {tokens} lirait chaque bloc d'accolades.
    echo cssum_style().$tpl->_ENGINE_parse_body($html).cssum_js();
    return true;
}

// Deconnexion de la console CrowdSec : confirmation qui dit ce qui se perd, puis POST.
function unlink_cloud_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $plain=function($t) use ($tpl){ return html_entity_decode(preg_replace('#<br\s*/?>#i'," ",$tpl->_ENGINE_parse_body((string)$t)),ENT_QUOTES,"UTF-8"); };
    $html=function($t) use ($tpl){ return $tpl->_ENGINE_parse_body((string)$t); };
    header("content-type: application/x-javascript");
    echo "swal({title:".json_encode($plain("{cssum_unlink_title}")).",text:".json_encode($html("{cssum_unlink_text}")).",html:true,type:'warning',showCancelButton:true,"
        ."confirmButtonColor:'#b91c1c',confirmButtonText:".json_encode($plain("{cssum_disconnect}")).",cancelButtonText:".json_encode($plain("{cssum_cancel}")).",closeOnConfirm:true},function(){
 var x=new XHRConnection();x.appendData('unlink-cloud','yes');
 x.sendAndLoad('$page','POST',function(o){var t=String(o.responseText||'').trim();
  if(t.length>3){ if(t.indexOf('jserror:')===0){t=t.substring(8);} setTimeout(function(){swal({title:'Oops...',text:t,html:true,type:'error'});},150); return; }
  LoadAjaxSilent('crowdsec-top','$page?status-top=yes');
  if(document.getElementById('crowdsec-config')){LoadAjax('crowdsec-config','$page?config-center=yes');}
 });
});";
    return true;
}

function unlink_cloud_perform():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsFirewallManager){ echo $tpl->post_error("{csblk_no_privs}"); return false; }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CROWDSEC_CLOUD_TOKEN","");
    return admin_tracks("Disable connection from the Crowdsec Cloud console");
}
function threads_popup():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    echo $tpl->search_block($page,null,null,null,"&threats-list-search=yes");
    return true;
}


function threads_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    return $tpl->js_dialog1("{threats}","$page?threats-popup=yes",550);
}

function join_cloud_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    return $tpl->js_dialog1("{crowdsec_webconsole}","$page?join-cloud-popup=yes");
}

function join_cloud_popup():bool{
    $tpl        = new template_admin();
    $CROWDSEC_TOKEN=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_CLOUD_TOKEN"));
    $form[]=$tpl->field_password2("CROWDSEC_CLOUD_TOKEN","{cloud_token}",$CROWDSEC_TOKEN,true);
    echo $tpl->form_outside(null, @implode("\n", $form),null,"{apply}",form_js(),
        "AsFirewallManager",true);
    return true;

}
function console_token_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $token=$_POST["CROWDSEC_CLOUD_TOKEN"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CROWDSEC_CLOUD_TOKEN",$token);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/console/connect"));
    if(!$json->Status) {
        echo $tpl->post_error($json->Error);
        return true;
    }
    return  admin_tracks_post("Connect CrowdSec to the cloud Console");

}

function restart_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html=base64_encode($tpl->_ENGINE_parse_body("<div style='margin:50px;' class='center'><H1>{restarting} {please_wait}....</H1></div>"));

    $f[]="document.getElementById('crowdsec-status').innerHTML=base64_decode('$html');";
    $f[]="Loadjs('$page?restart-perform=yes&id=crowdsec-status')";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
    return true;
}
function restart_perform():bool{
    $tpl=new template_admin();
    admin_tracks("Restarting the CrowdSec service");
    $sock=new sockets();
    $data=$sock->REST_API("/crowdsec/restart");
    $json=json_decode($data);
    $id=$_GET["id"];
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->js_error(json_last_error_msg());
    }
    header("content-type: application/x-javascript");
    if(!$json->Status){
        $html=base64_encode($tpl->_ENGINE_parse_body("<div style='margin:50px;' class='center'><H1 style='color:red'>$json->Error</H1></div>"));
        echo "document.getElementById('$id').innerHTML=base64_decode('$html');";
        return false;
    }
    $page=CurrentPageName();
    echo "LoadAjaxSilent('crowdsec-status','$page?status=yes');";
    return true;

}

function main_status():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();




    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";

    $html[]="<td style='width:240px;vertical-align: top'>";
    $html[]="<div id='crowdsec-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;padding-left: 20px;vertical-align: top'>";
    $html[]="<div id='crowdsec-top'></div>";
    $html[]="<div id='crowdsec-config'></div>";

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('crowdsec-config','$page?config-center=yes');";
    $html[]=$tpl->RefreshInterval_js("crowdsec-status",$page,"status=yes");

    $IPTABLES_VERSION   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CROWDSEC_VERSION");
    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?decisions-list-add-js=yes')", ico_plus, "{ban_ipaddr_net}");

    $TINY_ARRAY["TITLE"]="{APP_CROWDSEC} v$IPTABLES_VERSION";
    $TINY_ARRAY["ICO"]="far fa-shield-cross";
    $TINY_ARRAY["EXPL"]="{APP_CROWDSEC_EXPLAIN}";
    $TINY_ARRAY["URL"]="crowdsec-status";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);



    return true;
}
// ---------------------------------------------------------------------------
// Carte des reglages de l'onglet Status (maquette stitch_crowdsec_protection_settings).
// - Blocage par le pare-feu : EnableCrowdsecFirewallBouncer ; force par
//   ArticaPSnifferDaemon=1, sans effet quand EnableArticaNFQueue=1
//   (CrowdSecFireWallBouncer.IsEnabled()). Applique par /crowdsec/firewall/bouncer/restart,
//   qui installe ou retire les regles selon l'etat.
// - Duree de ban : 4 h fixes (profiles.yaml ecrit par articarest, crowdsec/Config.go).
// - Connexion a la console Artica : scenario articatech/articalogon-bf (leaky bucket),
//   capacite CrowdSecWebConsoleScenarioCapacity, fuite CrowdSecWebConsoleScenarioLeakSpeed (min),
//   WebConsoleCheckTrustedNets : fw.login.php ecrit UNTRUSTED, compte comme un echec.
// - Console CrowdSec : /crowdsec/console/features et /crowdsec/console/feature/{cle}/{0|1}.
// ---------------------------------------------------------------------------
function csset_tr($token):string{
    static $cache=array();
    if(!isset($cache[$token])){ $tpl=new template_admin(); $cache[$token]=$tpl->_ENGINE_parse_body("{".$token."}"); }
    $t=$cache[$token];
    $args=func_get_args(); array_shift($args);
    foreach($args as $a){ $p=strpos($t,"%s"); if($p===false){break;} $t=substr_replace($t,(string)$a,$p,2); }
    return $t;
}

function csset_plain($token):string{
    return html_entity_decode(preg_replace('#<br\s*/?>#i'," ",csset_tr($token)),ENT_QUOTES,"UTF-8");
}

// Partages de la console : cle cscli -> (token du libelle, token de l'aide).
function csset_features():array{
    return array(
        "manual"=>array("csset_f_manual","csset_f_manual_help","manual decisions"),
        "tainted"=>array("csset_f_tainted","csset_f_tainted_help","tainted scenarios"),
        "custom"=>array("csset_f_custom","csset_f_custom_help","custom scenarios"),
        "context"=>array("csset_f_context","csset_f_context_help","context"),
        "console_management"=>array("csset_f_mgmt","csset_f_mgmt_help","console management"),
    );
}

function csset_model():array{
    $S=$GLOBALS["CLASS_SOCKETS"];
    $M=array();
    $M["fw_on"]=intval($S->GET_INFO("EnableCrowdsecFirewallBouncer"))==1;
    $M["fw_mode"]="normal";
    if(intval($S->GET_INFO("EnableArticaNFQueue"))==1){ $M["fw_mode"]="elsewhere"; }
    elseif(intval($S->GET_INFO("ArticaPSnifferDaemon"))==1){ $M["fw_mode"]="forced"; }
    $M["debug"]=intval($S->GET_INFO("CrowdSecDebug"))==1;
    $cap=intval($S->GET_INFO("CrowdSecWebConsoleScenarioCapacity")); if($cap<=0){$cap=5;}
    $leak=intval($S->GET_INFO("CrowdSecWebConsoleScenarioLeakSpeed")); if($leak<=0){$leak=5;}
    $M["capacity"]=$cap; $M["leak"]=$leak;
    $M["trusted"]=intval($S->GET_INFO("WebConsoleCheckTrustedNets"))==1;
    $token=trim((string)$S->GET_INFO("CROWDSEC_CLOUD_TOKEN"));
    $enrolled=trim((string)$S->GET_INFO("CROWDSEC_ENROLLED"));
    $M["cloud"]="off";
    if($token!==""){ $M["cloud"]=strlen($enrolled)>8?"on":"wait"; }
    $M["token_tail"]=$token!==""?substr($token,-4):"";
    $M["features"]=null; $M["features_error"]=false;
    if($M["cloud"]==="on"){
        $j=json_decode((string)$S->REST_API("/crowdsec/console/features"),true);
        if(is_array($j) && !empty($j["Status"]) && is_array($j["features"] ?? null)){
            $M["features"]=array();
            foreach(array_keys(csset_features()) as $k){ $M["features"][$k]=!empty($j["features"][$k]); }
        }else{
            $M["features_error"]=true;
        }
    }
    return $M;
}

function csset_switch($id,$on,$labelId,$js,$disabled=false):string{
    $dis=$disabled?" disabled aria-disabled='true'":"";
    return "<button type='button' class='csset-sw".($on?" on":"")."' id='$id' role='switch' aria-checked='".($on?"true":"false")."' aria-labelledby='$labelId'$dis OnClick=\"$js\"><span></span></button>";
}

function csset_pill($tone,$ico,$label):string{
    return "<span class='csset-pill csset-$tone'><span class='csset-pill-i'><i class='$ico' aria-hidden='true'></i></span>$label</span>";
}

function csset_note($tone,$ico,$text):string{
    return "<div class='csset-note csset-note-$tone'><span class='csset-note-i'><i class='$ico' aria-hidden='true'></i></span><div>$text</div></div>";
}

function csset_header():string{
    return "<div class='csset-head'><span class='csset-head-i'><i class='fas fa-sliders' aria-hidden='true'></i></span>"
        ."<div><h3>{csset_title}</h3><p>{csset_intro}</p></div></div>";
}

function csset_fw_row(array $M):string{
    $mode=$M["fw_mode"];
    if($mode==="forced"){ $on=true; $pill=csset_pill("ok","fas fa-lock","{csset_always_on}"); }
    elseif($mode==="elsewhere"){ $on=false; $pill=csset_pill("neutral","fas fa-arrow-right-arrow-left","{csset_elsewhere}"); }
    else{ $on=$M["fw_on"]; $pill=$on?csset_pill("ok","fas fa-circle-check","{csset_active}"):csset_pill("neutral","fas fa-circle-minus","{csset_off}"); }
    $sw=csset_switch("csset-fw",$on,"csset-fw-l","CssetSwitch('fw',this)",$mode!=="normal");
    $h="<div class='csset-row'><div class='csset-row-h'><div class='csset-row-t'><span id='csset-fw-l' class='csset-lbl'>{csset_fw}</span>$pill"
      ."<span class='csset-tech'>{csset_fw_tech}</span></div>$sw</div>"
      ."<p class='csset-help'>{csset_fw_help}</p>";
    if($mode==="forced"){ $h.=csset_note("info","fas fa-circle-info","{csset_forced_reason}"); }
    elseif($mode==="elsewhere"){ $h.=csset_note("info","fas fa-circle-info","{csset_elsewhere_reason}"); }
    else{
        if(!$on){ $h.=csset_note("warn","fas fa-triangle-exclamation","{csset_fw_warn}"); }
        $h.=csset_note("plain","fas fa-circle-info","{csset_fw_restart}");
    }
    return $h."</div>";
}

function csset_ban_row():string{
    return "<div class='csset-row csset-row-fact'><div class='csset-row-h'><div class='csset-row-t'>"
        ."<span class='csset-lbl'><span class='csset-lbl-i'><i class='fas fa-hourglass-half' aria-hidden='true'></i></span>{csset_ban}</span>"
        ."<span class='csset-tag'>{csset_fixed}</span></div><span class='csset-fact'>{csset_4h}</span></div>"
        ."<p class='csset-help'>{csset_ban_help}</p></div>";
}

function csset_debug_row(array $M):string{
    $pill=$M["debug"]?csset_pill("warn","fas fa-bug","{csset_active}"):csset_pill("neutral","fas fa-circle-minus","{csset_off}");
    return "<div class='csset-row'><div class='csset-row-h'><div class='csset-row-t'><span id='csset-dbg-l' class='csset-lbl'>{csset_debug}</span>$pill"
        ."<span class='csset-tech'>{csset_debug_tech}</span></div>".csset_switch("csset-dbg",$M["debug"],"csset-dbg-l","CssetSwitch('debug',this)")."</div>"
        ."<p class='csset-help'>{csset_debug_help}</p></div>";
}

function csset_card_block(array $M):string{
    return "<section class='csset-card'><div class='csset-card-h'><span class='csset-card-i'><i class='fas fa-shield-halved' aria-hidden='true'></i></span><h4>{csset_block_title}</h4></div>"
        .csset_fw_row($M).csset_ban_row().csset_debug_row($M)."</section>";
}

function csset_num_field($id,$label,$help,$unit,$value,$min,$max):string{
    return "<div class='csset-num'><label for='$id' class='csset-lbl'>$label</label><p class='csset-help'>$help</p>"
        ."<div class='csset-num-in'><input type='number' id='$id' value='$value' min='$min' max='$max' step='1' OnInput='CssetExample()'><span>$unit</span></div></div>";
}

function csset_card_login(array $M):string{
    $tr=csset_switch("csset-trusted",$M["trusted"],"csset-trusted-l","CssetToggleLocal(this)");
    return "<section class='csset-card'><div class='csset-card-h'><span class='csset-card-i'><i class='fas fa-user-lock' aria-hidden='true'></i></span><h4>{csset_login_title}</h4></div>"
        ."<p class='csset-intro'>{csset_login_intro}</p>"
        ."<div class='csset-nums'>"
        .csset_num_field("csset-cap","{csset_cap}","{csset_cap_help}","{csset_attempts}",$M["capacity"],1,100)
        .csset_num_field("csset-leak","{csset_leak}","{csset_leak_help}","{csset_minutes}",$M["leak"],1,1440)
        ."</div>"
        ."<div class='csset-example' aria-live='polite'><span class='csset-note-i'><i class='fas fa-lightbulb' aria-hidden='true'></i></span><p id='csset-example'></p></div>"
        ."<div class='csset-row'><div class='csset-row-h'><span id='csset-trusted-l' class='csset-lbl'>{csset_trusted}</span>$tr</div>"
        ."<p class='csset-help'>{csset_trusted_help}</p>".csset_note("warn","fas fa-triangle-exclamation","{csset_trusted_risk}")."</div>"
        ."<div class='csset-foot'><span class='csset-help'><span class='csset-note-i'><i class='fas fa-circle-info' aria-hidden='true'></i></span>{csset_login_apply}</span>"
        ."<button type='button' class='csset-btn csset-btn-p' id='csset-login-save' OnClick='CssetSaveLogin()'><span class='csset-btn-i'><i class='fas fa-floppy-disk' aria-hidden='true'></i></span>{csset_save}</button></div>"
        ."</section>";
}

function csset_feature_row($key,array $f,$on):string{
    $id="csset-f-$key";
    $extra=$key==="console_management"?"<p class='csset-ctrl'><span class='csset-note-i'><i class='fas fa-lock' aria-hidden='true'></i></span>{csset_f_mgmt_ctrl}</p>":"";
    return "<div class='csset-feat'><div class='csset-row-h'><div class='csset-row-t'><span id='$id-l' class='csset-lbl'>{{$f[0]}}</span><span class='csset-tech'>".$f[2]."</span></div>"
        .csset_switch($id,$on,"$id-l","CssetFeature('$key',this)")."</div><p class='csset-help'>{{$f[1]}}</p>$extra</div>";
}

function csset_card_cloud(array $M):string{
    $page=CurrentPageName();
    $states=array("off"=>array("neutral","fas fa-circle-minus","csset_c_off"),"wait"=>array("warn","fas fa-hourglass-half","csset_c_wait"),"on"=>array("ok","fas fa-circle-check","csset_c_on"));
    $s=$states[$M["cloud"]];
    $h="<section class='csset-card csset-card-wide'><div class='csset-card-h'><span class='csset-card-i'><i class='fas fa-cloud' aria-hidden='true'></i></span><h4>{csset_cloud_title}</h4>"
      .csset_pill($s[0],$s[1],"{{$s[2]}}")."</div>";
    if($M["cloud"]==="off"){
        return $h."<div class='csset-cloud-off'><p>{csset_c_off_text}</p>"
            ."<button type='button' class='csset-btn csset-btn-p' OnClick=\"Loadjs('$page?join-cloud-js=yes')\"><span class='csset-btn-i'><i class='fas fa-link' aria-hidden='true'></i></span>{csset_connect}</button></div></section>";
    }
    $h.="<div class='csset-key'><span class='csset-key-i'><i class='fas fa-key' aria-hidden='true'></i></span>"
       ."<div class='csset-key-t'><span class='csset-lbl'>{csset_key}</span><span class='csset-help'>".($M["cloud"]==="on"?"{csset_key_on}":"{csset_c_wait_text}")."</span></div>"
       ."<span class='csset-mask' aria-label=\"".str_replace('"',"&quot;",csset_tr("csset_key_masked"))."\">••••••••".htmlspecialchars($M["token_tail"])."</span>"
       ."<button type='button' class='csset-btn' OnClick=\"Loadjs('$page?join-cloud-js=yes')\"><span class='csset-btn-i'><i class='fas fa-pen' aria-hidden='true'></i></span>{csset_change_key}</button></div>";
    if($M["cloud"]!=="on"){ return $h."</section>"; }
    if($M["features_error"]){
        return $h."<div class='csset-ferr'><span class='csset-note-i'><i class='fas fa-plug-circle-xmark' aria-hidden='true'></i></span>{csset_f_error}"
            ."<button type='button' class='csset-link' OnClick=\"LoadAjax('crowdsec-config','$page?config-center=yes')\">{csset_retry}</button></div></section>";
    }
    $rows=array(); $n=0;
    foreach(csset_features() as $k=>$f){ $on=!empty($M["features"][$k]); if($on){$n++;} $rows[]=csset_feature_row($k,$f,$on); }
    $h.="<div class='csset-share-h'><div><b>{csset_share_title}</b><p class='csset-help'>{csset_share_intro}</p></div><span class='csset-count' id='csset-share-n'>".csset_tr("csset_share_n",$n,count($rows))."</span></div>"
       ."<div class='csset-feats'>".@implode("",$rows)."</div>";
    return $h."</section>";
}

function csset_js(array $M):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $fwApply=$tpl->framework_buildjs("/crowdsec/firewall/bouncer/restart","crowdsec.reconfigure.progress","crowdsec.reconfigure.log",
        "progress-crowdsec-restart","LoadAjax('crowdsec-config','$page?config-center=yes');LoadAjaxSilent('crowdsec-status','$page?status=yes');");
    $reApply=$tpl->framework_buildjs("/crowdsec/reconfigure","crowdsec.reconfigure.progress","crowdsec.reconfigure.log",
        "progress-crowdsec-restart","LoadAjax('crowdsec-config','$page?config-center=yes');LoadAjaxSilent('crowdsec-status','$page?status=yes');");
    $j=function($t){ return json_encode(csset_plain($t)); };
    return "<script>
window.CssetT={ex:".$j("csset_example").",ex2:".$j("csset_example_ok").",ex1:".$j("csset_example_strict").",share:".$j("csset_share_n").",bad:".$j("csset_bad_number")."};
window.CssetErr=function(t){t=String(t||'').trim();if(t.indexOf('jserror:')===0){t=t.substring(8);}if(typeof swal==='function'){swal({title:'Oops...',text:t,html:true,type:'error'});}else{alert(t);}};
window.CssetPost=function(data,ok){var x=new XHRConnection();for(var k in data){x.appendData(k,data[k]);}x.sendAndLoad('$page','POST',function(o){var t=String(o.responseText||'').trim();if(t.length>0){CssetErr(t);return;}if(ok){ok();}});};
window.CssetSet=function(b,on){b.className='csset-sw'+(on?' on':'');b.setAttribute('aria-checked',on?'true':'false');};
window.CssetSwitch=function(kind,b){if(b.disabled){return;}var on=b.getAttribute('aria-checked')!=='true';CssetSet(b,on);b.disabled=true;
 CssetPost({'csset-switch':kind,'value':on?1:0},function(){ if(kind==='fw'){ $fwApply }else{ $reApply } });
 setTimeout(function(){b.disabled=false;},1500);};
window.CssetToggleLocal=function(b){CssetSet(b,b.getAttribute('aria-checked')!=='true');};
window.CssetFeature=function(key,b){var on=b.getAttribute('aria-checked')!=='true';CssetSet(b,on);
 CssetPost({'csset-feature':key,'value':on?1:0},function(){var n=document.querySelectorAll('.csset-feats .csset-sw.on').length,t=document.querySelectorAll('.csset-feats .csset-sw').length,e=document.getElementById('csset-share-n');if(e){e.textContent=CssetT.share.replace('%s',n).replace('%s',t);}});};
window.CssetVal=function(id,min,max){var v=parseInt((document.getElementById(id)||{}).value,10);if(isNaN(v)||v<min||v>max){return null;}return v;};
window.CssetExample=function(){var c=CssetVal('csset-cap',1,100),l=CssetVal('csset-leak',1,1440),e=document.getElementById('csset-example');if(!e){return;}
 if(c===null||l===null){e.textContent=CssetT.bad;return;}
 e.textContent=CssetT.ex.replace('%s',c+1)+' '+(c>=2?CssetT.ex2:CssetT.ex1.replace('%s',l));};
window.CssetSaveLogin=function(){var c=CssetVal('csset-cap',1,100),l=CssetVal('csset-leak',1,1440);if(c===null||l===null){CssetErr(CssetT.bad);return;}
 var tr=document.getElementById('csset-trusted').getAttribute('aria-checked')==='true'?1:0;
 CssetPost({'csset-login':'yes','capacity':c,'leak':l,'trusted':tr},function(){ $reApply });};
CssetExample();
</script>";
}

function csset_style():string{
    $A="#16a34a";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){ $a=brand_ui_accents(brand_palette()); if(!empty($a["accent"])){$A=$a["accent"];} }
    return "<style>
#csset{color:#0f172a;font-size:14px;line-height:1.45;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
#csset *{box-sizing:border-box}
#csset h3,#csset h4,#csset p{margin:0}
#csset button{font-family:inherit}
#csset button:focus-visible,#csset input:focus-visible{outline:2px solid $A;outline-offset:2px}
.csset-head{display:flex;gap:14px;align-items:flex-start;padding-bottom:16px;margin-bottom:16px;border-bottom:1px solid #f1f5f9}
.csset-head-i{flex:0 0 40px;height:40px;border-radius:10px;background:#f0fdf4;color:$A;display:inline-flex;align-items:center;justify-content:center;font-size:17px}
#csset .csset-head h3{font-size:16px;font-weight:600;margin-bottom:2px}
.csset-head p{font-size:13px;color:#475569}
.csset-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:16px;margin-bottom:16px}
.csset-card{border:1px solid #e2e8f0;border-radius:12px;padding:16px;display:flex;flex-direction:column;gap:12px;min-width:0}
.csset-card-h{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.csset-card-i{display:inline-flex;font-size:16px;color:$A}
#csset .csset-card-h h4{font-size:15px;font-weight:600;flex:1;min-width:0}
.csset-intro{font-size:13px;color:#475569}
.csset-row,.csset-feat,.csset-num{background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:12px 14px;display:flex;flex-direction:column;gap:6px;min-width:0}
.csset-row-h{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.csset-row-t{display:flex;flex-wrap:wrap;align-items:center;gap:6px 8px;min-width:0}
.csset-lbl{font-size:14px;font-weight:500;color:#0f172a;display:inline-flex;align-items:center;gap:6px;margin:0}
.csset-lbl-i{display:inline-flex;font-size:13px;color:#475569}
.csset-tech{flex-basis:100%;font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:13px;color:#94a3b8}
.csset-help{font-size:13px;color:#64748b;display:inline-flex;gap:6px;align-items:flex-start}
p.csset-help{display:block}
.csset-fact{font-size:15px;font-weight:600;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:4px 12px;white-space:nowrap}
.csset-tag{font-size:13px;color:#475569;background:#e2e8f0;border-radius:6px;padding:1px 8px}
.csset-pill{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:2px 10px;font-size:13px;font-weight:600;white-space:nowrap}
.csset-pill-i{display:inline-flex;font-size:11px}
.csset-ok{background:#dcfce7;color:#15803d}.csset-warn{background:#fef3c7;color:#b45309}.csset-neutral{background:#f1f5f9;color:#475569}
.csset-note{display:flex;gap:8px;align-items:flex-start;font-size:13px;border-radius:8px;padding:8px 10px}
.csset-note-i{display:inline-flex;font-size:12px;margin-top:2px}
.csset-note-warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.csset-note-info{background:#eff6ff;border:1px solid #dbeafe;color:#1e40af}
.csset-note-plain{color:#475569;padding:0}
.csset-sw{flex:0 0 40px;width:40px;height:22px;border-radius:999px;border:0;background:#cbd5e1;position:relative;cursor:pointer;padding:0;transition:background .15s}
.csset-sw span{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
.csset-sw.on{background:$A}
.csset-sw.on span{left:21px}
.csset-sw[disabled]{cursor:not-allowed;opacity:.6}
.csset-nums{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px}
.csset-num-in{display:flex;align-items:center;gap:8px;margin-top:auto}
.csset-num-in input{width:110px;height:36px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;font-size:14px;background:#fff;color:#0f172a}
.csset-num-in span{font-size:13px;color:#64748b}
.csset-example{display:flex;gap:10px;align-items:flex-start;background:#eff6ff;border:1px solid #dbeafe;border-radius:10px;padding:10px 12px;color:#1e40af;font-size:13px}
.csset-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:auto;padding-top:12px;border-top:1px solid #f1f5f9}
.csset-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.csset-btn:hover{border-color:#94a3b8}
.csset-btn-p{background:$A;border-color:$A;color:#fff}
.csset-btn-p:hover{filter:brightness(.95);border-color:$A}
.csset-btn-i{display:inline-flex;font-size:12px}
.csset-cloud-off{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.csset-cloud-off p{font-size:13px;color:#475569;flex:1;min-width:240px}
.csset-key{display:flex;align-items:center;gap:12px;background:#f8fafc;border:1px solid #f1f5f9;border-radius:10px;padding:12px 14px;flex-wrap:wrap}
.csset-key-i{flex:0 0 32px;height:32px;border-radius:50%;background:#dcfce7;color:#15803d;display:inline-flex;align-items:center;justify-content:center;font-size:13px}
.csset-key-t{flex:1;min-width:200px;display:flex;flex-direction:column}
.csset-mask{font-family:'JetBrains Mono',Menlo,Consolas,monospace;font-size:13px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:5px 10px;letter-spacing:.05em}
.csset-share-h{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap}
.csset-share-h b{font-size:14px;font-weight:600}
.csset-count{font-size:13px;color:#64748b}
.csset-feats{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:12px}
.csset-ctrl{font-size:13px;color:#1d4ed8;display:flex;gap:6px;align-items:flex-start}
.csset-ferr{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:13px;color:#991b1b;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 12px}
.csset-link{border:0;background:none;color:$A;font-weight:600;font-size:13px;cursor:pointer;padding:0;margin-left:auto}
@media (prefers-reduced-motion:reduce){#csset *{transition:none!important}}
</style>";
}

function main_config():bool{
    $tpl=new template_admin();
    $M=csset_model();
    $html="<div id='csset'>".csset_header()."<div class='csset-grid'>".csset_card_block($M).csset_card_login($M)."</div>".csset_card_cloud($M)."</div>";
    // CSS et JS HORS de _ENGINE_parse_body : la regex des {tokens} lirait chaque bloc d'accolades.
    echo csset_style().$tpl->_ENGINE_parse_body($html).csset_js($M);
    return true;
}

// POST csset-switch=fw|debug & value=0|1 : n'echo rien en cas de succes.
function csset_switch_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsFirewallManager){ echo $tpl->post_error("{csblk_no_privs}"); return false; }
    $keys=array("fw"=>"EnableCrowdsecFirewallBouncer","debug"=>"CrowdSecDebug");
    $kind=(string)($_POST["csset-switch"] ?? "");
    if(!isset($keys[$kind])){ echo $tpl->post_error("Unknown setting"); return false; }
    $v=intval($_POST["value"] ?? 0)==1?1:0;
    if($kind==="fw"){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue"))==1 || intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferDaemon"))==1){
            echo $tpl->post_error("{csset_fw_locked}"); return false;
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO($keys[$kind],$v);
    return admin_tracks("CrowdSec: set {$keys[$kind]} to $v");
}

// POST csset-login=yes & capacity & leak & trusted.
function csset_login_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsFirewallManager){ echo $tpl->post_error("{csblk_no_privs}"); return false; }
    $cap=intval($_POST["capacity"] ?? 0);
    $leak=intval($_POST["leak"] ?? 0);
    if($cap<1 || $cap>100 || $leak<1 || $leak>1440){ echo $tpl->post_error("{csset_bad_number}"); return false; }
    $trusted=intval($_POST["trusted"] ?? 0)==1?1:0;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CrowdSecWebConsoleScenarioCapacity",$cap);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CrowdSecWebConsoleScenarioLeakSpeed",$leak);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WebConsoleCheckTrustedNets",$trusted);
    return admin_tracks("CrowdSec: console login protection capacity=$cap leak={$leak}m trusted-networks=$trusted");
}

// POST csset-feature=<cle> & value=0|1 : partage avec la console CrowdSec (liste blanche).
function csset_feature_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsFirewallManager){ echo $tpl->post_error("{csblk_no_privs}"); return false; }
    $key=(string)($_POST["csset-feature"] ?? "");
    if(!array_key_exists($key,csset_features())){ echo $tpl->post_error("Unknown option"); return false; }
    $v=intval($_POST["value"] ?? 0)==1?1:0;
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/console/feature/$key/$v"),true);
    if(!is_array($json) || empty($json["Status"])){
        echo $tpl->post_error(is_array($json)?(string)($json["Error"] ?? "articarest"):"articarest: no answer");
        return false;
    }
    return admin_tracks("CrowdSec Console: set sharing option $key to $v");
}
function form_js():string{
    $page       = CurrentPageName();
    $tpl                = new template_admin();
    $f[]="LoadAjax('crowdsec-config','$page?config-center=yes');";
    $f[]="dialogInstance1.close();";
    $f[]=$tpl->framework_buildjs("/crowdsec/reconfigure","crowdsec.reconfigure.progress",
        "crowdsec.reconfigure.log","progress-crowdsec-restart","LoadAjax('crowdsec-status','$page?status=yes');");
    return @implode("",$f);
}
function page():bool{
	$page               = CurrentPageName();
	$tpl                = new template_admin();
	$IPTABLES_VERSION   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CROWDSEC_VERSION");
$html=$tpl->page_header("{APP_CROWDSEC} v$IPTABLES_VERSION",
    "far fa-shield-cross","{APP_CROWDSEC_EXPLAIN}","$page?tabs=yes","crowdsec-status",
        "progress-crowdsec-restart",false,"table-crowdsec");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_CROWDSEC}",$html);
		echo $tpl->build_firewall();
		return true;
	}
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function crowdsec_status():bool{
    $page               = CurrentPageName();
    $status1=crowdsec_status_widget();
    $status2=Bouncer_status();
    $js="LoadAjaxSilent('crowdsec-top','$page?status-top=yes');";
    echo "$status1<p>&nbsp;</p>$status2<script>$js</script>";
    return true;
}

function crowdsec_status_widget():string{
    $tpl                = new template_admin();
    $restartjs="Loadjs('fw.crowdsec.php?restart=yes');";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/status");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->widget_rouge("API ERROR",json_last_error_msg());

    }else {
        if (!$json->Status) {
            return $tpl->widget_rouge("API ERROR", $json->Error);
        } else {
            $ini = new Bs_IniHandler();
            $ini->loadString($json->Info);
            return  $tpl->SERVICE_STATUS($ini, "APP_CROWDSEC", $restartjs);
        }
    }

}

function Bouncer_status():string{
    $page =currentPageName();
    $tpl                = new template_admin();
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/bouncer/status");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->widget_rouge("API ERROR",json_last_error_msg());

    }else {
        if (!$json->Status) {
            return $tpl->widget_rouge("API ERROR", $json->Error);
        } else {
            if(strlen($json->Info)>10) {
                $ini = new Bs_IniHandler();
                $ini->loadString($json->Info);

                $jsRestart=$tpl->framework_buildjs("/crowdsec/firewall/bouncer/restart",
                    "crowdsec.reconfigure.progress","crowdsec.reconfigure.log",
                    "progress-crowdsec-restart",
                    "LoadAjax('crowdsec-status','$page?status=yes');"
                );


                return $tpl->SERVICE_STATUS($ini, "APP_IPTABLES_BOUNCER", $jsRestart);
            }
        }
    }
    return "";
}

// ---------------------------------------------------------------------------
// Onglet "Blocked addresses" (maquette stitch_crowdsec_blocked_addresses_console, 2026-11-21).
// Sources :
//  - GET /crowdsec/blacklist/search : miroir PostgreSQL de l'ipset crowdsec-blacklists (IpSettoDB, toutes
//    les 10 min) : Data[] {ipaddr, timeout = secondes restantes a la derniere synchronisation}, Total = toute
//    la table, Available=false => etat inconnu, Created => premiere synchronisation. Tri : temps restant.
//  - crowdsec.db (base de la LAPI), table decisions : le POURQUOI (origin, scenario = motif saisi pour un
//    bannissement manuel) et le decompte par origine. Les filtres "Detected here" et "Manual" lisent les
//    adresses dans cette base : la liste du miroir, triee par temps restant, est dominee par la liste
//    communautaire (blocages de plusieurs jours) et n'en montrerait aucune.
//  - CrowdsecIpSettoDB : horodatage de la derniere synchronisation ; modesctools : pays, ville, nom d'hote.
// ---------------------------------------------------------------------------

function csblk_tr($token):string{
    static $cache=array();
    if(!isset($cache[$token])){ $tpl=new template_admin(); $cache[$token]=$tpl->_ENGINE_parse_body("{".$token."}"); }
    $t=$cache[$token];
    $args=func_get_args(); array_shift($args);
    foreach($args as $a){ $p=strpos($t,"%s"); if($p===false){break;} $t=substr_replace($t,(string)$a,$p,2); }
    return $t;
}
function csblk_attr($v):string{
    return str_replace('"',"&quot;",trim(preg_replace('#<br\s*/?>#i'," ",(string)$v)));
}
function csblk_esc($v):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}
function csblk_lastsync():int{
    $v=trim((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrowdsecIpSettoDB"));
    return ctype_digit($v)?intval($v):0;
}
// Duree courte et neutre en langue : "3 h 12 min", "2 d 4 h", "45 min".
function csblk_dur(int $s):string{
    if($s<60){return "< 1 min";}
    $d=intdiv($s,86400); $h=intdiv($s%86400,3600); $m=intdiv($s%3600,60);
    if($d>0){ return $d." d".($h>0?" ".$h." h":""); }
    if($h>0){ return $h." h".($m>0?" ".$m." min":""); }
    return $m." min";
}
// "15:56" aujourd'hui, "Nov 23, 15:56" sinon.
function csblk_until(int $ts):string{
    if(date("Y-m-d",$ts)==date("Y-m-d")){ return date("H:i",$ts); }
    return date("M j, H:i",$ts);
}
function csblk_trusted_set():array{
    $out=array();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT ipaddr FROM networks_infos WHERE enabled=1 AND trusted=1");
    if(is_array($results)){
        foreach($results as $ligne){ $out[str_replace("/32","",trim((string)$ligne["ipaddr"]))]=true; }
    }
    return $out;
}
function csblk_is_trusted(string $ip,array $set):bool{
    if(isset($set[$ip])){return true;}
    if(strpos($ip,"/")!==false){return false;}
    foreach(array_keys($set) as $t){ if(strpos($t,"/")!==false && csth_ip_in_cidr($ip,$t)){return true;} }
    return false;
}
// Adresse unique ou reseau CIDR valides (IPv4/IPv6). Rend la forme normalisee, "" si invalide.
function csblk_valid_target(string $v):string{
    $v=trim($v);
    if(filter_var($v,FILTER_VALIDATE_IP)){ return $v; }
    if(substr_count($v,"/")!=1){ return ""; }
    list($net,$bits)=explode("/",$v,2);
    if(!filter_var($net,FILTER_VALIDATE_IP) || !ctype_digit($bits)){ return ""; }
    $max=strpos($net,":")!==false?128:32;
    $b=intval($bits);
    if($b<1 || $b>$max){ return ""; }
    if($b==$max){ return $net; }
    return "$net/$b";
}
function csblk_net_size(string $cidr):string{
    if(strpos($cidr,"/")===false || strpos($cidr,":")!==false){ return ""; }
    $b=intval(substr($cidr,strpos($cidr,"/")+1));
    if($b<8 || $b>32){ return ""; }
    return number_format(pow(2,32-$b),0,".",",");
}
// crowdsec / CAPI / lists:xxx / cscli -> here | community | manual
function csblk_origin(string $o):string{
    $o=strtolower(trim($o));
    if($o=="crowdsec"){ return "here"; }
    if(strpos($o,"cscli")===0 || $o=="console"){ return "manual"; }
    return "community";
}
// Valeur passee dans le chemin d'une route articarest : fasthttp decode le chemin une fois (un "/" encode
// une seule fois couperait la route), le handler decode ensuite la valeur.
function csblk_path($v):string{
    return rawurlencode(rawurlencode((string)$v));
}

// ---- model --------------------------------------------------------------------------------------------
function csblk_engine_db(){
    if(!is_file(CSEV_DB)){ return null; }
    return new lib_sqlite(CSEV_DB);
}
// Une adresse = une origine : celle de sa decision active qui dure le plus longtemps (la regle de la colonne
// Why). SQLite prend les colonnes nues de la ligne du MAX(), d'ou un GROUP BY value sans jointure.
function csblk_active_sql():string{
    return "SELECT value,origin,scenario,MAX(until) AS until FROM decisions WHERE until>=? AND type='ban' GROUP BY value";
}
// Condition SQL d'origine, alignee sur csblk_origin().
function csblk_origin_sql(string $origin):string{
    $manual="(lower(origin) LIKE 'cscli%' OR lower(origin)='console')";
    if($origin=="here"){ return "lower(origin)='crowdsec'"; }
    if($origin=="manual"){ return $manual; }
    return "(lower(origin)<>'crowdsec' AND NOT $manual)";
}
// Decompte par origine, chaque adresse comptee une seule fois. null = base illisible.
function csblk_origin_counts($q){
    if($q===null){ return null; }
    $rows=$q->QUERY_SQL_PARAMS("SELECT origin,COUNT(*) AS n FROM (".csblk_active_sql().") GROUP BY origin",array(gmdate("Y-m-d H:i:s")));
    if(!$q->ok || !is_array($rows)){ return null; }
    $c=array("here"=>0,"community"=>0,"manual"=>0);
    foreach($rows as $r){ $c[csblk_origin((string)$r["origin"])]+=intval($r["n"]); }
    return $c;
}
// Pourquoi chaque valeur affichee est bloquee : la decision active qui dure le plus longtemps.
function csblk_reasons($q,array $values):array{
    $out=array();
    if($q===null || count($values)==0){ return $out; }
    $now=gmdate("Y-m-d H:i:s");
    foreach(array_chunk($values,400) as $chunk){
        $binds=array_merge(array($now),$chunk);
        $rows=$q->QUERY_SQL_PARAMS("SELECT value,origin,scenario,until FROM decisions WHERE until>=? AND type='ban' AND value IN (".implode(",",array_fill(0,count($chunk),"?")).")",$binds);
        if(!is_array($rows)){ continue; }
        foreach($rows as $r){
            $v=(string)$r["value"]; $u=csev_ts($r["until"]);
            if(isset($out[$v]) && $out[$v]["until"]>=$u){ continue; }
            $out[$v]=array("origin"=>csblk_origin((string)$r["origin"]),"scenario"=>(string)$r["scenario"],"until"=>$u);
        }
    }
    return $out;
}
function csblk_geo(array &$row){
    static $modtools=null,$ipc=null;
    if($row["net"]){ return; }
    if($modtools===null){ $modtools=new modesctools(); $ipc=new IP(); }
    $modtools->hostid=0; $modtools->hostname=""; $modtools->country_name=""; $modtools->country=""; $modtools->city=""; $modtools->flag="";
    $modtools->hostinfo($row["ip"],false);
    if(intval($modtools->hostid)<=0){ return; }
    $row["known"]=true;
    $row["country"]=trim((string)$modtools->country_name);
    if($row["country"]===""){ $row["country"]=trim((string)$modtools->country); }
    $row["city"]=trim((string)$modtools->city);
    $row["flag"]=trim((string)$modtools->flag);
    $h=trim((string)$modtools->hostname);
    if($h!=="" && !$ipc->isValid($h) && $h!==$row["ip"]){ $row["host"]=$h; }
}
function csblk_new_row(string $ip,int $left,array $trusted):array{
    return array("ip"=>$ip,"left"=>$left,"net"=>strpos($ip,"/")!==false,"trusted"=>csblk_is_trusted($ip,$trusted),
        "host"=>"","country"=>"","city"=>"","flag"=>"","known"=>false,"origin"=>"","scenario"=>"","until"=>0);
}

function csblk_model(string $search,string $origin):array{
    $M=array("ok"=>false,"error"=>"","invalid"=>false,"total"=>0,"available"=>true,"created"=>false,"rows"=>array(),
        "max"=>0,"search"=>$search,"origin"=>$origin,"counts"=>null,"capped"=>false,"engine"=>false);
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/blacklist/search?limit=".CSBLK_LIMIT."&search=".urlencode($search)),true);
    if(!is_array($json)){ $M["error"]="articarest: no answer from /crowdsec/blacklist/search"; return $M; }
    if(empty($json["Status"])){
        $M["error"]=trim((string)($json["Error"] ?? ""));
        $M["invalid"]=strpos($M["error"],"invalid search")===0;
        return $M;
    }
    $M["ok"]=true;
    $M["total"]=intval($json["Total"] ?? 0);
    $M["available"]=!empty($json["Available"]);
    $M["created"]=!empty($json["Created"]);
    $q=csblk_engine_db();
    $M["engine"]=$q!==null;
    $M["counts"]=csblk_origin_counts($q);
    $trusted=csblk_trusted_set();
    $now=time();

    if(($origin=="here" || $origin=="manual") && $q!==null){
        // Adresses de cette origine, lues dans la base du moteur (fin de blocage exacte).
        $match=csth_parse_search($search);
        $rows=$q->QUERY_SQL_PARAMS("SELECT * FROM (".csblk_active_sql().") WHERE ".csblk_origin_sql($origin)." ORDER BY until DESC LIMIT 5000",array(gmdate("Y-m-d H:i:s")));
        $seen=array();
        if(is_array($rows)){
            foreach($rows as $r){
                if(csblk_origin((string)$r["origin"])!=$origin){ continue; }
                $v=trim((string)$r["value"]);
                if($v=="" || isset($seen[$v])){ continue; }
                if($match[0]!="all" && !csth_matches($match,$v,"") && $v!==$match[1]){ continue; }
                $seen[$v]=true;
                $u=csev_ts($r["until"]);
                $row=csblk_new_row($v,$u>$now?$u-$now:0,$trusted);
                $row["origin"]=$origin; $row["scenario"]=(string)$r["scenario"]; $row["until"]=$u;
                $M["rows"][]=$row;
            }
        }
        if(count($M["rows"])>CSBLK_LIMIT){ $M["capped"]=true; $M["rows"]=array_slice($M["rows"],0,CSBLK_LIMIT); }
    }else{
        $elapsed=0;
        $sync=csblk_lastsync();
        if($sync>0 && $sync<=$now){ $elapsed=$now-$sync; }
        $values=array();
        if(is_array($json["Data"] ?? null)){
            foreach($json["Data"] as $r){
                $ip=trim((string)($r["ipaddr"] ?? ""));
                if($ip===""){continue;}
                $left=intval($r["timeout"] ?? 0);
                if($left>0){ $left=max(60,$left-$elapsed); }
                $M["rows"][]=csblk_new_row($ip,$left,$trusted);
                $values[]=$ip;
            }
        }
        $why=csblk_reasons($q,$values);
        $keep=array();
        foreach($M["rows"] as $row){
            if(isset($why[$row["ip"]])){
                $row["origin"]=$why[$row["ip"]]["origin"];
                $row["scenario"]=$why[$row["ip"]]["scenario"];
                $row["until"]=$why[$row["ip"]]["until"];
            }
            if($origin=="community" && $row["origin"]!="community"){ continue; }
            $keep[]=$row;
        }
        $M["rows"]=$keep;
        $nd=count($json["Data"] ?? array());
        $M["capped"]=$nd>=CSBLK_LIMIT && $M["total"]>$nd;
    }
    foreach($M["rows"] as $i=>$row){
        csblk_geo($row);
        if($row["left"]>$M["max"]){ $M["max"]=$row["left"]; }
        $M["rows"][$i]=$row;
    }
    return $M;
}

// ---- widgets --------------------------------------------------------------------------------------------
function csblk_i($fa):string{
    return "<span class='csblk-i' aria-hidden='true'><i class='$fa'></i></span>";
}
function csblk_chip($ico,$text):string{
    return "<span class='csblk-chip'>".csblk_i($ico).$text."</span>";
}
function csblk_banner():string{
    $chips=array();
    $chips[]=csblk_chip("fas fa-hourglass-half","{csblk_chip_expire}");
    $sync=csblk_lastsync();
    if($sync>0 && $sync<=time()){
        $chips[]="<span title=\"".csblk_attr(date("Y-m-d H:i",$sync))."\">".csblk_chip("fas fa-rotate",csblk_tr("csblk_chip_sync",csblk_dur(max(60,time()-$sync))))."</span>";
    }
    $chips[]=csblk_chip("fas fa-unlock","{csblk_chip_unblock}");
    return "<section class='csblk-banner'><span class='csblk-banner-i'>".csblk_i("fas fa-ban")."</span>"
        ."<div><h3>{csblk_title}</h3><p>{csblk_intro2}</p><div class='csblk-chips'>".implode("",$chips)."</div></div></section>";
}
function csblk_tile($key,$ico,$tone,$label,$sub,$value,$on):string{
    $cls=$on?" on":"";
    return "<button type='button' class='csblk-tile csblk-t-$tone$cls' aria-pressed='".($on?"true":"false")."' data-o='$key' onclick=\"CsblkOrigin('$key')\">"
        ."<span class='csblk-tl'><span>$label</span>".csblk_i($ico)."</span><span class='csblk-big'>$value</span><span class='csblk-ts'>$sub</span></button>";
}
// Total des tuiles : la somme exacte des trois origines (meme source, meme regle) ; le miroir du pare-feu,
// rafraichi toutes les 10 min, ne sert que si la base du moteur est illisible.
function csblk_all_count(array $M):string{
    if(is_array($M["counts"])){ return number_format(array_sum($M["counts"]),0,".",","); }
    if($M["ok"] && $M["available"] && !$M["created"]){ return number_format($M["total"],0,".",","); }
    return "—";
}
function csblk_tiles(array $M):string{
    $c=$M["counts"];
    $n=function($k) use($c){ return is_array($c)?number_format($c[$k],0,".",","):"—"; };
    $o=$M["origin"];
    return "<div class='csblk-tiles'>"
        .csblk_tile("all","fas fa-shield-halved","all","{csblk_t_all}","{csblk_t_all_sub}",csblk_all_count($M),$o=="all")
        .csblk_tile("here","fas fa-crosshairs","here","{csblk_t_here}","{csblk_t_here_sub}",$n("here"),$o=="here")
        .csblk_tile("community","fas fa-users","community","{csblk_t_community}","{csblk_t_community_sub}",$n("community"),$o=="community")
        .csblk_tile("manual","fas fa-user-pen","manual","{csblk_t_manual}","{csblk_t_manual_sub}",$n("manual"),$o=="manual")
        ."</div>";
}
function csblk_search_bar(array $M):string{
    $v=csblk_esc($M["search"]);
    $n=count($M["rows"]);
    $line="";
    if($M["ok"] && $M["search"]!=="" && $n>0){
        $s=csblk_esc($M["search"]);
        $line=$n==1?csblk_tr("csblk_match_1",$s):($M["capped"]?csblk_tr("csblk_match_many",$n,$s):csblk_tr("csblk_match",$n,$s));
        $line="<p class='csblk-match'>$line · <button type='button' class='csblk-link' onclick='CsblkClear()'>{csblk_clear}</button></p>";
    }elseif($M["ok"] && $M["capped"] && $n>0){
        $line="<p class='csblk-help'>".csblk_tr("csblk_showing",number_format($n,0,".",","))."</p>";
    }
    if($M["origin"]=="community" && $M["ok"] && $M["capped"]){
        $line.="<p class='csblk-help'>{csblk_community_note}</p>";
    }
    return "<div class='csblk-searchrow'><label for='csblk-q' class='csblk-lbl'>{csblk_find_label}</label>"
        ."<div class='csblk-search'>".csblk_i("fas fa-magnifying-glass")."<input type='search' id='csblk-q' value='$v' autocomplete='off' placeholder=\"".csblk_attr(csblk_tr("csblk_find_ph"))."\" aria-describedby='csblk-q-help' oninput='CsblkQueue()'></div>"
        ."<p class='csblk-help' id='csblk-q-help'>{csblk_find_help}</p>$line</div>";
}
function csblk_state($tone,$ico,$title,$text,$action=""):string{
    return "<div class='csblk-state csblk-state-$tone'><span class='csblk-state-i'>".csblk_i($ico)."</span>"
        ."<h4>$title</h4>".($text!==""?"<p>$text</p>":"").$action."</div>";
}
function csblk_cell_addr(array $r):string{
    $ipe=csblk_esc($r["ip"]); $ipu=urlencode($r["ip"]);
    $addr=$r["known"]
        ?"<a href='javascript:void(0)' class='csblk-ip' onclick=\"Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$ipu')\">$ipe</a>"
        :"<span class='csblk-ip csblk-ip-plain'>$ipe</span>";
    $sub="";
    if($r["net"]){
        $size=csblk_net_size($r["ip"]);
        $sub="<div class='csblk-sub'><span class='csblk-tag'>".csblk_i("fas fa-network-wired")."{csblk_whole_net}</span>".($size!==""?" ".csblk_tr("csblk_net_size",$size):"")."</div>";
    }elseif($r["host"]!==""){
        $sub="<div class='csblk-sub'>".csblk_esc($r["host"])."</div>";
    }
    if($r["trusted"]){
        $sub.="<div><span class='csblk-badge csblk-b-trusted' title=\"".csblk_attr(csblk_tr("csblk_trusted_tip"))."\">".csblk_i("fas fa-shield-check")."{csblk_trusted}</span></div>";
    }
    return "<td class='csblk-c-addr'>$addr$sub</td>";
}
function csblk_cell_why(array $r):string{
    if($r["origin"]==""){
        return "<td class='csblk-c-why'><span class='csblk-muted' title=\"".csblk_attr(csblk_tr("csblk_why_unknown"))."\">—</span></td>";
    }
    $map=array("here"=>array("csblk-b-here","fas fa-crosshairs","csblk_t_here"),
        "community"=>array("csblk-b-community","fas fa-users","csblk_t_community"),
        "manual"=>array("csblk-b-manual","fas fa-user-pen","csblk_t_manual"));
    $b=$map[$r["origin"]];
    $badge="<span class='csblk-badge {$b[0]}'>".csblk_i($b[1])."{".$b[2]."}</span>";
    $sc=trim($r["scenario"]);
    if($r["origin"]=="here"){
        $line=csev_scenario_name($sc).($sc!==""?" <span class='csblk-mono csblk-sub-i'>· ".csblk_esc($sc)."</span>":"");
    }elseif($r["origin"]=="manual"){
        $line=$sc!==""?csblk_esc($sc):"{csblk_manual_noreason}";
    }else{
        $line="{csblk_shared_list}";
    }
    return "<td class='csblk-c-why'>$badge<div class='csblk-why'>$line</div></td>";
}
function csblk_cell_from(array $r):string{
    if($r["country"]===""){ return "<td class='csblk-c-from'><span class='csblk-muted'>—</span></td>"; }
    $flag=$r["flag"]!==""?"<img src='img/".csblk_esc($r["flag"])."' alt='' class='csblk-flag'>":"";
    return "<td class='csblk-c-from'><div class='csblk-loc'>$flag".csblk_esc($r["country"])."</div>".($r["city"]!==""?"<div class='csblk-sub'>".csblk_esc($r["city"])."</div>":"")."</td>";
}
function csblk_cell_left(array $r,int $max):string{
    if($r["left"]<=0){
        return "<td class='csblk-c-left'><b>".csblk_i("fas fa-infinity")."{csblk_no_end}</b></td>";
    }
    $end=$r["until"]>0?$r["until"]:time()+$r["left"];
    $pct=$max>0?max(3,round($r["left"]*100/$max)):0;
    return "<td class='csblk-c-left'><b>".csblk_tr("csblk_left",csblk_dur($r["left"]))."</b>"
        ."<div class='csblk-bar' title=\"".csblk_attr(csblk_tr("csblk_bar_tip"))."\"><span style='width:$pct%'></span></div>"
        ."<div class='csblk-sub' title=\"".csblk_attr(date("Y-m-d H:i",$end))."\">".csblk_tr("csblk_until",csblk_until($end))."</div></td>";
}
function csblk_cell_act(array $r):string{
    $page=CurrentPageName();
    $ipj=csblk_esc(json_encode($r["ip"]));
    $lbl=csblk_attr(csblk_tr("csblk_unblock_tip"));
    $h="<button type='button' class='csblk-btn' title=\"$lbl\" onclick=\"CsblkUnblock($ipj)\">".csblk_i("fas fa-unlock")."{csblk_unblock}</button>";
    if(!$r["net"] && !$r["trusted"]){
        $ipu=urlencode($r["ip"]);
        $more=csblk_attr(csblk_tr("csblk_more"));
        $h.="<div class='dropdown csblk-more'><button type='button' class='csblk-ibtn dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false' aria-label=\"$more\" title=\"$more\">".csblk_i("fas fa-ellipsis-vertical")."</button>"
            ."<ul class='dropdown-menu dropdown-menu-right'><li><a href='javascript:void(0)' title=\"".csblk_attr(csblk_tr("csblk_trust_tip"))."\" onclick=\"Loadjs('$page?whitelist-js=$ipu&function=CsblkLoad')\">".csblk_i("fas fa-user-check")." {csblk_trust}</a></li></ul></div>";
    }
    return "<td class='csblk-c-act'>$h</td>";
}
function csblk_row(array $r,int $max):string{
    return "<tr>".csblk_cell_addr($r).csblk_cell_why($r).csblk_cell_from($r).csblk_cell_left($r,$max).csblk_cell_act($r)."</tr>";
}
function csblk_table(array $M):string{
    $rows=array();
    foreach($M["rows"] as $r){ $rows[]=csblk_row($r,$M["max"]); }
    return "<section class='csblk-card'><table class='csblk-tbl'><thead><tr><th>{csblk_col_addr}</th><th>{csblk_col_why}</th><th>{csblk_col_from}</th><th>{csblk_col_left}</th>"
        ."<th class='csblk-r'>{csblk_col_act}</th></tr></thead><tbody>".implode("",$rows)."</tbody></table></section>";
}
function csblk_list_body(array $M):string{
    $page=CurrentPageName();
    $ban="<button type='button' class='csblk-btn csblk-btn-p' onclick=\"Loadjs('$page?decisions-list-add-js=yes')\">".csblk_i("fas fa-plus")."{csblk_ban}</button>";
    $clear="<button type='button' class='csblk-btn' onclick='CsblkClear()'>".csblk_i("fas fa-xmark")."{csblk_clear_search}</button>";
    if(!$M["ok"]){
        if($M["invalid"]){ return csblk_state("warn","fas fa-circle-exclamation","{csblk_invalid}","",$clear); }
        $det=$M["error"]!==""?"<details class='csblk-det'><summary>{csblk_details}</summary><pre class='csblk-mono'>".csblk_esc($M["error"])."</pre></details>":"";
        return csblk_state("err","fas fa-triangle-exclamation","{csblk_err_title}","",$det."<button type='button' class='csblk-btn' onclick='CsblkLoad()'>".csblk_i("fas fa-rotate")."{csblk_retry}</button>");
    }
    if(!$M["available"]){ return csblk_state("warn","fas fa-triangle-exclamation","{csblk_unknown_title}","{csblk_unknown_text2}"); }
    if($M["created"]){ return csblk_state("neutral","fas fa-hourglass-half","{csblk_building}","{csblk_building_text2}"); }
    if(($M["origin"]=="here" || $M["origin"]=="manual") && !$M["engine"]){ return csblk_state("warn","fas fa-triangle-exclamation","{csblk_engine_missing}","{csblk_engine_missing_text}"); }
    if(count($M["rows"])==0){
        if($M["search"]!==""){ return csblk_state("neutral","fas fa-magnifying-glass",csblk_tr("csblk_nomatch",csblk_esc($M["search"])),"{csblk_nomatch_text}",$clear); }
        if($M["origin"]!="all"){ return csblk_state("ok","fas fa-shield-check","{csblk_origin_empty}",""); }
        return csblk_state("ok","fas fa-shield-check","{csblk_empty_title}","{csblk_empty_text}",$ban);
    }
    return csblk_table($M);
}
// Corps rechargeable : tuiles, recherche, liste.
function csblk_host_html(array $M):string{
    return csblk_tiles($M).csblk_search_bar($M)."<div id='csblk-list'>".csblk_list_body($M)."</div>";
}

// ---- handlers -------------------------------------------------------------------------------------------
function csblk_params():array{
    $s=isset($_GET["search"])?trim((string)$_GET["search"]):"";
    if($s==="*"){$s="";}
    $s=substr(preg_replace('/[\x00-\x1f]/','',$s),0,64);
    $o=(string)($_GET["origin"] ?? "all");
    if(!in_array($o,array("all","here","community","manual"),true)){ $o="all"; }
    return array($s,$o);
}
function threats_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    list($s,$o)=csblk_params();
    $M=csblk_model($s,$o);
    $html="<div id='csblk' class='csblk'>".csblk_banner()."<div id='csblk-host' data-o='".csblk_esc($o)."'>".csblk_host_html($M)."</div></div>";
    $topbuttons=array();
    $topbuttons[]=array("CsblkLoad()",ico_refresh,"{refresh}");
    $topbuttons[]=array("Loadjs('$page?decisions-list-add-js=yes')",ico_plus,"{ban_ipaddr_net}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{csblk_tab}";
    $TINY_ARRAY["ICO"]="fas fa-ban";
    $TINY_ARRAY["EXPL"]="{APP_CROWDSEC_EXPLAIN}";
    $TINY_ARRAY["URL"]="crowdsec-status";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $tiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo csblk_style().$tpl->_ENGINE_parse_body($html).csblk_js($tiny);
    return true;
}
// ?search=&origin= : hote rechargeable (tuiles + recherche + liste).
function threats_search():bool{
    $tpl=new template_admin();
    list($s,$o)=csblk_params();
    echo $tpl->_ENGINE_parse_body(csblk_host_html(csblk_model($s,$o)))."<script>if(typeof CsblkAfter==='function'){CsblkAfter();}</script>";
    return true;
}
// POST csblk-unblock : leve le blocage sans mettre l'adresse en confiance.
function csblk_unblock():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsFirewallManager){ echo $tpl->javascript_parse_text("{csblk_no_privs}"); return false; }
    $ip=csblk_valid_target((string)($_POST["csblk-unblock"] ?? ""));
    if($ip===""){ echo $tpl->javascript_parse_text("{csblk_bad_target}"); return false; }
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/decision/delete/".csblk_path($ip)));
    if(!$json){ echo "articarest: no answer"; return false; }
    if(!$json->Status){ echo trim((string)$json->Error); return false; }
    // Le miroir n'est reecrit qu'a la synchronisation suivante (10 min) : la ligne part tout de suite.
    $q=new postgres_sql();
    if($q->TABLE_EXISTS("crowdsec_blacklists")){
        $q->QUERY_SQL_PARAMS("DELETE FROM crowdsec_blacklists WHERE ipaddr=$1::inet",array($ip));
    }
    admin_tracks("Unblock $ip from CrowdSec (decisions deleted)");
    return true;
}

function whitelist_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ipaddr=trim((string)($_GET["whitelist-js"] ?? ""));
    header("content-type: application/x-javascript");
    if(!filter_var($ipaddr,FILTER_VALIDATE_IP)){
        echo "swal({title:'Oops...',text:".json_encode(html_entity_decode($tpl->_ENGINE_parse_body("{invalid_ip_address}"),ENT_QUOTES,"UTF-8")." ".$ipaddr).",type:'error'});";
        return false;
    }
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $plain=function($t) use ($tpl){ return html_entity_decode(preg_replace('#<br\s*/?>#i'," ",$tpl->_ENGINE_parse_body((string)$t)),ENT_QUOTES,"UTF-8"); };
    $title=str_replace("%s",$ipaddr,$plain("{csblk_trust_title}"));
    $text=$tpl->_ENGINE_parse_body("{csblk_trust_text}");
    $after=$function!==""?"if(typeof $function==='function'){{$function}();}":"if(typeof CsblkLoad==='function'){CsblkLoad();}";
    echo "swal({title:".json_encode($title).",text:".json_encode($text).",html:true,type:'warning',showCancelButton:true,"
        ."confirmButtonColor:'#005447',confirmButtonText:".json_encode($plain("{csblk_trust_ok}")).",cancelButtonText:".json_encode($plain("{csblk_cancel}")).",closeOnConfirm:true},function(){
 var x=new XHRConnection();x.appendData('whitelist-ip',".json_encode($ipaddr).");
 x.sendAndLoad('$page','POST',function(o){var t=String(o.responseText||'').trim();
  if(t.length>0){ if(t.indexOf('jserror:')===0){t=t.substring(8);} setTimeout(function(){swal({title:'Oops...',text:t,html:true,type:'error'});},150); return; }
  $after
 });
});";
    return true;
}


// ---- Ban dialog -----------------------------------------------------------------------------------------
function decisions_list_add_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{ban_ipaddr_net}","$page?decisions-list-add-popup=yes",560);
}
function decisions_list_add_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $durs=array(60=>"csblk_d_1h",240=>"csblk_d_4h",1440=>"csblk_d_24h",10080=>"csblk_d_7d",0=>"csblk_d_custom");
    $seg=array();
    foreach($durs as $m=>$tok){
        $on=$m==1440;
        $seg[]="<button type='button' role='radio' aria-checked='".($on?"true":"false")."' class='".($on?"on":"")."' data-m='$m' onclick='CsbanDur(this)'>{".$tok."}</button>";
    }
    $trusted=array_keys(csblk_trusted_set());
    $html="<div class='csblk csban'><p class='csban-intro'>{csban_intro}</p>
<div class='csban-f'><label for='csban-ip'>{csban_addr}</label><input type='text' id='csban-ip' autocomplete='off' placeholder='203.0.113.7 or 198.51.100.0/24' oninput='CsbanCheck()'><p class='csblk-help'>{csban_addr_help}</p></div>
<div class='csban-f'><span class='csban-l' id='csban-dl'>{csban_dur}</span><div class='csban-seg' role='radiogroup' aria-labelledby='csban-dl'>".implode("",$seg)."</div>
<div id='csban-custom' hidden><input type='number' id='csban-min' min='1' max='525600' value='1440' aria-label=\"".csblk_attr(csblk_tr("csban_minutes"))."\"> {csban_minutes}</div>
<p class='csblk-help'>{csban_dur_help}</p></div>
<div class='csban-f'><label for='csban-reason'>{csban_reason}</label><input type='text' id='csban-reason' maxlength='120' placeholder='Manual block'><p class='csblk-help'>{csban_reason_help}</p></div>
<div class='csban-warn' id='csban-warn' hidden>".csblk_i("fas fa-triangle-exclamation")."<span>{csban_warn_own}</span></div>
<div class='csban-err' id='csban-err' hidden></div>
<div class='csban-btns'><button type='button' class='csblk-btn' onclick='dialogInstance1.close()'>{csblk_cancel}</button><button type='button' class='csblk-btn csblk-btn-p' id='csban-go' onclick='CsbanGo()'>".csblk_i("fas fa-ban")."{csban_block}</button></div>
</div>";
    $js="<script>
window.CsbanTrusted=".json_encode($trusted).";
function CsbanDur(b){[].forEach.call(document.querySelectorAll('.csban-seg button'),function(x){var on=x===b;x.className=on?'on':'';x.setAttribute('aria-checked',on?'true':'false');});document.getElementById('csban-custom').hidden=b.getAttribute('data-m')!=='0';}
function CsbanV4(s){var m=String(s).match(/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/);if(!m){return null;}var n=0;for(var i=1;i<5;i++){var o=+m[i];if(o>255){return null;}n=n*256+o;}return n;}
function CsbanIn(ip,cidr){var p=String(cidr).split('/'),a=CsbanV4(ip),b=CsbanV4(p[0]);if(a===null||b===null){return false;}var bits=p.length>1?+p[1]:32;if(bits===0){return true;}var d=Math.pow(2,32-bits);return Math.floor(a/d)===Math.floor(b/d);}
function CsbanCheck(){var v=document.getElementById('csban-ip').value.trim(),ip=v.split('/')[0],own=false;
 var priv=['10.0.0.0/8','172.16.0.0/12','192.168.0.0/16','127.0.0.0/8','169.254.0.0/16'].concat(window.CsbanTrusted||[]);
 if(CsbanV4(ip)!==null){priv.forEach(function(c){if(CsbanIn(ip,c)){own=true;}});}
 document.getElementById('csban-warn').hidden=!own;}
function CsbanGo(){var b=document.querySelector('.csban-seg button.on'),m=b?b.getAttribute('data-m'):'1440';if(m==='0'){m=document.getElementById('csban-min').value;}
 var e=document.getElementById('csban-err');e.hidden=true;
 var x=new XHRConnection();x.appendData('decision-add',document.getElementById('csban-ip').value.trim());x.appendData('time',m);x.appendData('description',document.getElementById('csban-reason').value.trim());
 x.sendAndLoad('$page','POST',function(o){var t=String(o.responseText||'').trim();if(t.length>0){if(t.indexOf('jserror:')===0){t=t.substring(8);}e.textContent=t.replace(/<[^>]*>/g,' ');e.hidden=false;return;}
  dialogInstance1.close();if(typeof CsblkLoad==='function'){CsblkLoad();}});}
</script>";
    echo csblk_style().$tpl->_ENGINE_parse_body($html).$js;
    return true;
}
function decisions_list_add_save():bool{
    $tpl=new template_admin();
    $users=new usersMenus();
    if(!$users->AsFirewallManager){ echo $tpl->javascript_parse_text("{csblk_no_privs}"); return false; }
    $ip=csblk_valid_target((string)($_POST["decision-add"] ?? ""));
    if($ip===""){ echo $tpl->javascript_parse_text("{csblk_bad_target}"); return false; }
    $min=intval($_POST["time"] ?? 0);
    if($min<1 || $min>525600){ echo $tpl->javascript_parse_text("{csban_bad_duration}"); return false; }
    $reason=trim(preg_replace('/[\x00-\x1f"\'`$\\\\]/','',(string)($_POST["description"] ?? "")));
    $reason=substr($reason,0,120);
    if($reason===""){ $reason="Manual block"; }
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/decision/add/".csblk_path($ip)."/$min/".csblk_path($reason)));
    if(!$json){ echo "articarest: no answer"; return false; }
    if(!$json->Status){ echo trim((string)($json->Error ?? "")); return false; }
    admin_tracks("Ban $ip for $min minutes in CrowdSec ($reason)");
    return true;
}

// ---- JS -------------------------------------------------------------------------------------------------
function csblk_js($tiny):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $plain=function($tok) use($tpl){ return html_entity_decode(trim(preg_replace('#<br\s*/?>#i'," ",$tpl->_ENGINE_parse_body("{".$tok."}"))),ENT_QUOTES,"UTF-8"); };
    $t=json_encode(array("title"=>$plain("csblk_unblock_title"),"text"=>$plain("csblk_unblock_text"),"ok"=>$plain("csblk_unblock"),"cancel"=>$plain("csblk_cancel")));
    return "<script>
window.CsblkT=$t;
function CsblkHost(){return document.getElementById('csblk-host');}
window.CsblkLoad=function(){var h=CsblkHost();if(!h){return;}var q=(document.getElementById('csblk-q')||{value:''}).value.trim();
 LoadAjaxSilent('csblk-host','$page?search='+encodeURIComponent(q)+'&origin='+encodeURIComponent(h.getAttribute('data-o')||'all'));};
window.CsblkOrigin=function(o){var h=CsblkHost();if(!h){return;}h.setAttribute('data-o',o);CsblkLoad();};
window.CsblkQueue=function(){clearTimeout(window.CsblkQT);window.CsblkQT=setTimeout(function(){var e=document.getElementById('csblk-q');if(!e){return;}var q=e.value.trim();if(q.length!==1){CsblkLoad();}},600);};
window.CsblkClear=function(){var e=document.getElementById('csblk-q');if(e){e.value='';}CsblkLoad();};
window.CsblkUnblock=function(ip){var T=window.CsblkT;
 swal({title:T.title.replace('%s',ip),text:T.text,type:'warning',showCancelButton:true,confirmButtonColor:'#005447',confirmButtonText:T.ok,cancelButtonText:T.cancel,closeOnConfirm:true},function(){
  var x=new XHRConnection();x.appendData('csblk-unblock',ip);
  x.sendAndLoad('$page','POST',function(o){var t=String(o.responseText||'').trim();
   if(t.length>0){if(t.indexOf('jserror:')===0){t=t.substring(8);}setTimeout(function(){swal({title:'Oops...',text:t,type:'error'});},150);return;}
   CsblkLoad();});});};
window.CsblkAfter=function(){NoSpinner();var e=document.getElementById('csblk-q');if(e&&window.CsblkFocus){e.focus();var n=e.value.length;try{e.setSelectionRange(n,n);}catch(x){}}};
document.addEventListener('focusin',function(ev){if(ev.target&&ev.target.id==='csblk-q'){window.CsblkFocus=true;}});
document.addEventListener('focusout',function(ev){if(ev.target&&ev.target.id==='csblk-q'){window.CsblkFocus=false;}});
NoSpinner();
$tiny
</script>";
}

// ---- CSS ------------------------------------------------------------------------------------------------
function csblk_style():string{
    if(isset($GLOBALS["CSBLK_STYLE"])){return "";}
    $GLOBALS["CSBLK_STYLE"]=true;
    $A="#005447";
    return "<style>
.csblk{color:#0f172a;font-size:14px;line-height:1.45;margin-top:15px}
.csblk *{box-sizing:border-box}
.csblk h3,.csblk h4,.csblk p{margin:0}
.csblk [hidden]{display:none!important}
.csblk button{font-family:inherit}
.csblk button:focus-visible,.csblk input:focus-visible,.csblk a:focus-visible,.csblk summary:focus-visible{outline:2px solid $A;outline-offset:2px}
.csblk-i{display:inline-flex;align-items:center;justify-content:center;width:1.1em;flex:0 0 auto}
.csblk-mono{font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace}
.csblk-muted{color:#94a3b8}
.csblk-banner{display:flex;gap:16px;padding:20px 24px;background:#f2f6f6;border:1px solid #bfd4d1;border-radius:12px;margin-bottom:20px}
.csblk-banner-i{width:42px;height:42px;border-radius:50%;background:#e6eeed;color:$A;display:flex;align-items:center;justify-content:center;font-size:18px;flex:0 0 42px}
.csblk-banner h3{font-size:18px;font-weight:600;margin-bottom:6px}
.csblk-banner p{color:#334155;max-width:980px}
.csblk-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.csblk-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#e6eeed;color:$A;font-size:13px;font-weight:500}
.csblk-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:20px}
.csblk-tile{display:flex;flex-direction:column;align-items:stretch;text-align:left;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;min-height:116px;cursor:pointer;min-width:0}
.csblk-tile:hover{border-color:#bfd4d1}
.csblk-tile.on{border:2px solid $A;padding:15px 17px;background:#fbfdfd}
.csblk-tl{display:flex;justify-content:space-between;align-items:center;font-size:14px;font-weight:600;color:#374151}
.csblk-big{font-size:28px;font-weight:700;line-height:36px;margin-top:8px}
.csblk-ts{font-size:13px;color:#4b5563;margin-top:2px}
.csblk-t-all .csblk-tl .csblk-i{color:$A}
.csblk-t-here .csblk-big,.csblk-t-here .csblk-tl .csblk-i{color:#b91c1c}
.csblk-t-community .csblk-big,.csblk-t-community .csblk-tl .csblk-i{color:$A}
.csblk-t-manual .csblk-big,.csblk-t-manual .csblk-tl .csblk-i{color:#92400e}
.csblk-searchrow{margin-bottom:16px;max-width:600px}
.csblk-lbl,.csban-f label,.csban-l{display:block;font-size:14px;font-weight:600;margin:0 0 6px}
.csblk-search{display:flex;align-items:center;gap:8px;height:40px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#6b7280}
.csblk-search:focus-within{border-color:$A;box-shadow:0 0 0 2px #bfd4d1}
.csblk-search input{border:0;outline:0;flex:1;min-width:0;font-size:14px;color:#0f172a;background:transparent;height:38px}
.csblk-help{font-size:12px;color:#6b7280;margin-top:4px!important}
.csblk-match{font-size:13px;color:#374151;margin-top:6px!important}
.csblk-link{border:0;background:none;color:$A;font-weight:600;padding:0;cursor:pointer;text-decoration:underline}
.csblk-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px}
.csblk-tbl{width:100%;border-collapse:collapse;table-layout:fixed}
.csblk-tbl th{background:#f8fafc;text-align:left;font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#4b5563;padding:12px 16px;border-bottom:1px solid #e5e7eb}
.csblk-tbl th:first-child{border-top-left-radius:10px}.csblk-tbl th:last-child{border-top-right-radius:10px}
.csblk-tbl th:nth-child(1){width:230px}.csblk-tbl th:nth-child(3){width:170px}.csblk-tbl th:nth-child(4){width:200px}.csblk-tbl th:nth-child(5){width:236px}
.csblk-tbl td{padding:14px 16px;border-bottom:1px solid #f1f5f9;vertical-align:top;color:#0f172a}
.csblk-tbl tbody tr:last-child td{border-bottom:0}
.csblk-tbl tbody tr:hover td{background:#f8fafb}
.csblk-r{text-align:right!important}
.csblk-ip{font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace;font-size:14px;font-weight:600;color:$A;word-break:break-all}
.csblk-ip-plain{color:#0f172a}
.csblk-sub{font-size:13px;color:#4b5563;margin-top:2px}
.csblk-sub-i{font-size:12px;color:#6b7280}
.csblk-tag{display:inline-flex;align-items:center;gap:4px;padding:1px 8px;border-radius:6px;background:#f1f5f9;color:#374151;font-size:12px}
.csblk-badge{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;border:1px solid transparent;white-space:nowrap;margin-top:4px}
.csblk-b-here{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.csblk-b-community{background:#e6eeed;color:$A;border-color:#bfd4d1}
.csblk-b-manual{background:#fffbeb;color:#92400e;border-color:#fde68a}
.csblk-b-trusted{background:#ecfdf5;color:#047857;border-color:#a7f3d0}
.csblk-c-why .csblk-badge{margin-top:0}
.csblk-why{margin-top:6px;color:#1f2937;word-break:break-word}
.csblk-loc{display:flex;align-items:center;gap:6px}
.csblk-flag{width:18px;height:auto}
.csblk-c-left b{display:flex;align-items:center;gap:6px;font-weight:600}
.csblk-bar{height:6px;border-radius:3px;background:#e5e7eb;margin:8px 0 6px;overflow:hidden;max-width:180px}
.csblk-bar span{display:block;height:100%;background:$A;border-radius:3px}
.csblk-c-act{text-align:right;white-space:nowrap}
.csblk-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid $A;border-radius:8px;background:#fff;color:$A;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.csblk-btn:hover{background:#e6eeed}
.csblk-btn-p{background:$A;color:#fff}
.csblk-btn-p:hover{background:#003d33;color:#fff}
.csblk-more{display:inline-block;margin-left:4px;vertical-align:top}
.csblk-ibtn{width:32px;height:32px;border:1px solid transparent;border-radius:8px;background:transparent;color:#4b5563;cursor:pointer}
.csblk-ibtn:hover{background:#f1f5f9}
.csblk-more .dropdown-menu a{display:flex;gap:8px;align-items:center;padding:8px 14px}
.csblk-state{background:#fff;border:1px solid #e5e7eb;border-radius:10px;text-align:center;padding:40px 24px}
.csblk-state-i{width:56px;height:56px;border-radius:50%;background:#e6eeed;color:$A;display:inline-flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:12px}
.csblk-state h4{font-size:16px;font-weight:600;margin-bottom:6px}
.csblk-state p{color:#4b5563;max-width:560px;margin:0 auto 14px}
.csblk-state-warn .csblk-state-i{background:#fffbeb;color:#92400e}
.csblk-state-err .csblk-state-i{background:#fef2f2;color:#b91c1c}
.csblk-det{margin:0 auto 14px;max-width:640px;text-align:left}
.csblk-det pre{white-space:pre-wrap;font-size:12px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:6px;padding:8px}
.csban{margin:0;padding:4px 2px}
.csban-intro{color:#334155;margin-bottom:16px!important}
.csban-f{margin-bottom:16px}
.csban-f input[type=text],.csban-f input[type=number]{width:100%;height:38px;border:1px solid #d1d5db;border-radius:8px;padding:0 10px;font-size:14px}
.csban-f input[type=number]{width:120px;margin-top:8px}
.csban-f input:focus{border-color:$A;outline:2px solid #bfd4d1}
.csban-seg{display:flex;flex-wrap:wrap;gap:2px;padding:3px;background:#f1f5f9;border:1px solid #e5e7eb;border-radius:10px;width:max-content;max-width:100%}
.csban-seg button{border:0;background:transparent;padding:6px 12px;border-radius:8px;font-size:13px;color:#374151;cursor:pointer}
.csban-seg button.on{background:$A;color:#fff;font-weight:600}
.csban-warn{display:flex;gap:8px;align-items:flex-start;padding:10px 12px;border-radius:8px;background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:13px;margin-bottom:12px}
.csban-err{padding:10px 12px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:13px;margin-bottom:12px;word-break:break-word}
.csban-btns{display:flex;justify-content:flex-end;gap:8px}
@media (max-width:1200px){.csblk-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (prefers-reduced-motion:no-preference){.csblk-tile,.csblk-btn,.csblk-tbl td{transition:background-color .15s,border-color .15s}}
</style>";
}

// ---------------------------------------------------------------------------
// Threats tab (Stitch port 2026-11-19, prefix csth_).
// Source: alerts table of crowdsec-events.db (one row per CrowdSec detection;
// alertid = reception time, unix seconds), per-address enrichment (modesctools),
// trusted networks (interfaces.db) and the block list mirror
// (GET /crowdsec/blacklist/search, Available=false => state unknown).
// ---------------------------------------------------------------------------

function csth_view():string{
    $v=isset($_GET["view"])?$_GET["view"]:"addr";
    return $v=="alerts"?"alerts":"addr";
}
function csth_function():string{
    return preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["function"])?$_GET["function"]:""));
}

// Plain-language name of a CrowdSec scenario: array(label, subtitle).
function csth_scenario(string $sc):array{
    $short=strtolower($sc);
    if(strpos($short,"/")!==false){$short=substr($short,strrpos($short,"/")+1);}
    $map=array(
        "http-bad-user-agent"=>"bad_user_agent","ssh-bf"=>"ssh_bf","ssh-slow-bf"=>"ssh_bf",
        "http-probing"=>"probing","http-crawl-non_statics"=>"crawl","http-sensitive-files"=>"sensitive",
        "http-path-traversal-probing"=>"traversal","http-sqli-probing"=>"sqli","http-xss-probing"=>"xss",
        "http-admin-interface-probing"=>"admin","http-backdoors-attempts"=>"backdoors",
        "articalogon-bf"=>"console_bf",
    );
    if(isset($map[$short])){
        $k=$map[$short];
        return array("{csth_sc_$k}","{csth_sc_{$k}_sub}");
    }
    $label=ucfirst(trim(str_replace(array("-","_")," ",$short)));
    if($label==""){$label="—";}
    return array(htmlspecialchars($label),"");
}

// Search typed by the operator: address, network, beginning of an address, or a behavior.
function csth_parse_search(string $q):array{
    $q=trim($q);
    if($q==""){return array("all","");}
    if(filter_var($q,FILTER_VALIDATE_IP)){return array("ip",$q);}
    if(strpos($q,"/")!==false){
        list($net,$bits)=array_pad(explode("/",$q,2),2,"");
        if(filter_var($net,FILTER_VALIDATE_IP) && ctype_digit($bits)){return array("cidr",$q);}
    }
    if(preg_match('/^[0-9a-fA-F.:]+$/',$q) && preg_match('/[.:]/',$q)){return array("prefix",strtolower($q));}
    return array("text",strtolower($q));
}
function csth_ip_in_cidr(string $ip,string $cidr):bool{
    list($net,$bits)=array_pad(explode("/",$cidr,2),2,"");
    $a=@inet_pton($ip);$b=@inet_pton($net);
    if($a===false||$b===false||strlen($a)!=strlen($b)){return false;}
    $bits=intval($bits);$max=strlen($a)*8;
    if($bits<0||$bits>$max){return false;}
    $bytes=intdiv($bits,8);$rem=$bits%8;
    if(substr($a,0,$bytes)!==substr($b,0,$bytes)){return false;}
    if($rem==0){return true;}
    $mask=chr((0xFF<<(8-$rem))&0xFF);
    return (($a[$bytes]&$mask)===($b[$bytes]&$mask));
}
function csth_matches(array $m,string $ip,string $scenario):bool{
    switch($m[0]){
        case "all": return true;
        case "ip": return @inet_pton($ip)!==false && @inet_pton($ip)===@inet_pton($m[1]);
        case "cidr": return csth_ip_in_cidr($ip,$m[1]);
        case "prefix": return strpos(strtolower($ip),$m[1])===0;
    }
    if(strpos(strtolower($scenario),$m[1])!==false){return true;}
    static $cache=array();
    if(!isset($cache[$scenario])){
        $tpl=new template_admin();
        $lbl=csth_scenario($scenario);
        $cache[$scenario]=strtolower($tpl->javascript_parse_text($lbl[0]));
    }
    return strpos($cache[$scenario],$m[1])!==false;
}

// Trusted networks (exact address or containing network).
function csth_trusted_list():array{
    $out=array();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT ipaddr FROM networks_infos WHERE enabled=1 AND trusted=1");
    if(!is_array($results)){return $out;}
    foreach($results as $ligne){$out[]=trim((string)$ligne["ipaddr"]);}
    return $out;
}
function csth_is_trusted(string $ip,array $trusted):bool{
    foreach($trusted as $t){
        if($t==""){continue;}
        if(strpos($t,"/")===false){ if($t===$ip){return true;} continue; }
        if(csth_ip_in_cidr($ip,$t)){return true;}
    }
    return false;
}

// Block list mirror. available=false: the bouncer ipset does not exist, nothing can be said.
function csth_bans(array $ips):array{
    $out=array("available"=>false,"map"=>array());
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/blacklist/search?limit=1000"));
    if(!$json || !property_exists($json,"Status") || !$json->Status){return $out;}
    if(!property_exists($json,"Available") || !$json->Available){return $out;}
    $out["available"]=true;
    foreach($json->Data as $r){$out["map"][(string)$r->ipaddr]=intval($r->timeout);}
    if(intval($json->Total)<=count($json->Data)){return $out;}
    // Mirror larger than one page: ask for the displayed addresses that were not in it.
    $asked=0;
    foreach($ips as $ip){
        if(isset($out["map"][$ip])){continue;}
        if(++$asked>50){$out["map"][$ip]=-1;continue;}   // -1 = unknown
        $one=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/blacklist/search?limit=1&search=".urlencode($ip)));
        if($one && $one->Status && count($one->Data)>0){$out["map"][$ip]=intval($one->Data[0]->timeout);}
    }
    return $out;
}

function csth_valid_ts($ts):bool{ return intval($ts)>1000000000; }

// One model for every widget of a render.
function csth_model(string $search,string $view):array{
    $q=new lib_sqlite(CSTH_DB);
    $now=time();
    $m=array("error"=>"","total"=>0,"addr_total"=>0,"trusted_n"=>0,"behaviors"=>0,"top"=>"",
        "days"=>array(),"days_total"=>0,"hot_ip"=>"","hot_n"=>0,"addresses"=>array(),"addr_match"=>0,
        "alerts"=>array(),"alerts_match"=>0,"search"=>$search,"view"=>$view,"bans"=>array("available"=>false,"map"=>array()));
    $match=csth_parse_search($search);
    $trusted=csth_trusted_list();

    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM alerts");
    if(!$q->ok){$m["error"]=$q->mysql_error;return $m;}
    $m["total"]=intval($ligne["tcount"]);

    // Pairs address x behavior: global figures + the "by address" view.
    $pairs=$q->QUERY_SQL("SELECT ipaddr,scenario,COUNT(*) AS n,MAX(alertid) AS last,MIN(alertid) AS first FROM alerts GROUP BY ipaddr,scenario ORDER BY last DESC LIMIT 5000");
    if(!is_array($pairs)){$pairs=array();}
    $allip=array();$allsc=array();
    $byip=array();
    foreach($pairs as $p){
        $ip=(string)$p["ipaddr"];$sc=(string)$p["scenario"];$n=intval($p["n"]);
        $allip[$ip]=true;
        if(!isset($allsc[$sc])){$allsc[$sc]=0;}
        $allsc[$sc]+=$n;
        if(!csth_matches($match,$ip,$sc)){continue;}
        if(!isset($byip[$ip])){$byip[$ip]=array("ip"=>$ip,"n"=>0,"last"=>0,"first"=>0,"sc"=>array());}
        $byip[$ip]["n"]+=$n;
        $byip[$ip]["sc"][$sc]=$n;
        if(intval($p["last"])>$byip[$ip]["last"]){$byip[$ip]["last"]=intval($p["last"]);}
        $f=intval($p["first"]);
        if(csth_valid_ts($f) && ($byip[$ip]["first"]==0 || $f<$byip[$ip]["first"])){$byip[$ip]["first"]=$f;}
    }
    $m["addr_total"]=count($allip);
    foreach(array_keys($allip) as $ip){ if(csth_is_trusted($ip,$trusted)){$m["trusted_n"]++;} }
    $m["behaviors"]=count($allsc);
    if(count($allsc)>0){arsort($allsc);reset($allsc);$m["top"]=key($allsc);}

    // Alerts per day, last 14 local days.
    $off=intval(date("Z"));
    $today=intdiv($now+$off,86400);
    for($i=13;$i>=0;$i--){$m["days"][$today-$i]=0;}
    $since=($today-13)*86400-$off;
    $rows=$q->QUERY_SQL_PARAMS("SELECT CAST((alertid+?)/86400 AS INTEGER) AS d,COUNT(*) AS n FROM alerts WHERE alertid>=? GROUP BY d",array($off,$since));
    if(is_array($rows)){foreach($rows as $r){$d=intval($r["d"]); if(isset($m["days"][$d])){$m["days"][$d]=intval($r["n"]);$m["days_total"]+=intval($r["n"]);}}}

    // Busiest address of the last 24 hours.
    $hot=$q->mysqli_fetch_array("SELECT ipaddr,COUNT(*) AS n FROM alerts WHERE alertid>=".intval($now-86400)." GROUP BY ipaddr ORDER BY n DESC LIMIT 1");
    if(is_array($hot) && isset($hot["n"]) && intval($hot["n"])>CSTH_HOT){$m["hot_ip"]=(string)$hot["ipaddr"];$m["hot_n"]=intval($hot["n"]);}

    if($view=="addr"){
        usort($byip,function($a,$b){return $b["last"]-$a["last"];});
        $m["addr_match"]=count($byip);
        $m["addresses"]=array_slice($byip,0,CSTH_MAX_ADDR);
        $ips=array();
        foreach($m["addresses"] as $i=>$a){
            $m["addresses"][$i]["trusted"]=csth_is_trusted($a["ip"],$trusted);
            arsort($m["addresses"][$i]["sc"]);
            $ips[]=$a["ip"];
        }
        $m["bans"]=csth_bans($ips);
        return $m;
    }

    // "All alerts" view.
    $sql="SELECT ID,EventsCount,scenario,alertid,ipaddr FROM alerts";
    $binds=array();$phpfilter=false;
    switch($match[0]){
        case "ip": $sql.=" WHERE ipaddr=?";$binds[]=$match[1];break;
        case "prefix": $sql.=" WHERE ipaddr LIKE ?";$binds[]=$match[1]."%";break;
        case "cidr": $phpfilter=true;break;
        case "text":
            $ids=array();
            foreach(array_keys($allsc) as $sc){ if(csth_matches($match,"",$sc)){$ids[]=$sc;} }
            if(count($ids)==0){return $m;}
            $sql.=" WHERE scenario IN (".implode(",",array_fill(0,count($ids),"?")).")";$binds=$ids;
            break;
    }
    $cntr=$q->QUERY_SQL_PARAMS(str_replace("SELECT ID,EventsCount,scenario,alertid,ipaddr","SELECT COUNT(*) AS tcount",$sql),$binds);
    $cnt=(is_array($cntr)&&isset($cntr[0]))?$cntr[0]:null;
    $sql.=" ORDER BY ID DESC LIMIT ".($phpfilter?5000:CSTH_MAX_ALERTS);
    $rows=$q->QUERY_SQL_PARAMS($sql,$binds);
    if(!is_array($rows)){$rows=array();}
    $out=array();
    foreach($rows as $r){
        if($phpfilter && !csth_matches($match,(string)$r["ipaddr"],"")){continue;}
        $r["trusted"]=csth_is_trusted((string)$r["ipaddr"],$trusted);
        $out[]=$r;
    }
    $m["alerts_match"]=$phpfilter?count($out):intval(is_array($cnt)?$cnt["tcount"]:count($out));
    $m["alerts"]=array_slice($out,0,CSTH_MAX_ALERTS);
    return $m;
}

// ---- small helpers ---------------------------------------------------------
// Translate THEN substitute: a placeholder replaced in the {token} name is lost.
function csth_t(string $tok,array $r=array()):string{
    static $tpl=null;
    if($tpl===null){$tpl=new template_admin();}
    return strtr($tpl->_ENGINE_parse_body("{".$tok."}"),$r);
}
function csth_ago($ts):string{
    $ts=intval($ts);
    if(!csth_valid_ts($ts)){return "—";}
    $d=time()-$ts;
    if($d<60){return "{csth_just_now}";}
    if($d<3600){return csth_t("csth_min_ago",array("%d"=>intdiv($d,60)));}
    if($d<86400){return csth_t("csth_h_ago",array("%d"=>intdiv($d,3600)));}
    return csth_t("csth_days_ago",array("%d"=>intdiv($d,86400)));
}
function csth_exact($ts):string{
    if(!csth_valid_ts($ts)){return "";}
    $tpl=new template_admin();
    return htmlspecialchars($tpl->time_to_date(intval($ts),true));
}
function csth_duration($sec):string{
    $sec=intval($sec);
    $h=intdiv($sec,3600);$mn=intdiv($sec%3600,60);
    if($h>0){return "$h h ".sprintf("%02d",$mn)." min";}
    return max(1,$mn)." min";
}
function csth_host_info(string $ip):array{
    static $mt=null;
    if($mt===null){$mt=new modesctools();}
    $mt->hostid=0;$mt->hostname="";$mt->country_name="";$mt->city="";$mt->isp="";$mt->organization="";$mt->asn_organization="";$mt->flag="";
    $mt->hostinfo($ip,false);
    if(empty($mt->hostid)){return array("known"=>false);}
    $org=trim((string)$mt->asn_organization);
    if($org==""){$org=trim((string)$mt->isp);}
    if($org==""){$org=trim((string)$mt->organization);}
    $host=trim((string)$mt->hostname);
    if($host==$ip || filter_var($host,FILTER_VALIDATE_IP)){$host="";}
    return array("known"=>true,"host"=>$host,"country"=>trim((string)$mt->country_name),
        "city"=>trim((string)$mt->city),"org"=>$org,"flag"=>(string)$mt->flag);
}
function csth_flag(array $h):string{
    if($h["known"] && $h["flag"]!=""){return "<img src='img/".htmlspecialchars($h["flag"])."' alt='' class='csth-flag'>";}
    return "<span class='ak-ico ak-ico-xs ak-off' aria-hidden='true'><i class='fas fa-globe'></i></span>";
}

// Client-side pager: shows $size children of #$listId at a time (cards or table rows).
function csth_pager_js(string $listId,string $pagerId,int $size):string{
    return "<script>(function(){var L=document.getElementById('$listId'),P=document.getElementById('$pagerId');if(!L||!P){return;}
var it=[].slice.call(L.children),n=it.length,sz=$size,cur=1,pg=Math.max(1,Math.ceil(n/sz));
if(n<=sz){P.innerHTML='';return;}
function draw(){for(var i=0;i<n;i++){it[i].style.display=(i>=(cur-1)*sz&&i<cur*sz)?'':'none';}
var e=Math.min(pg,Math.max(cur+2,5)),s=Math.max(1,e-4),h='';
h+='<button type=\"button\" class=\"'+(cur==1?'off':'')+'\" data-p=\"'+(cur-1)+'\" aria-label=\"&lsaquo;\">&lsaquo;</button>';
for(var p=s;p<=e;p++){h+='<button type=\"button\" class=\"'+(p==cur?'cur':'')+'\" data-p=\"'+p+'\">'+p+'</button>';}
h+='<button type=\"button\" class=\"'+(cur==pg?'off':'')+'\" data-p=\"'+(cur+1)+'\" aria-label=\"&rsaquo;\">&rsaquo;</button>';
P.innerHTML=h;[].forEach.call(P.querySelectorAll('button'),function(b){b.onclick=function(){var q=parseInt(this.getAttribute('data-p'),10);if(q>=1&&q<=pg&&q!=cur){cur=q;draw();}};});}
draw();})();</script>";
}
// ---- widgets ---------------------------------------------------------------
function csth_style():string{
    if(isset($GLOBALS["CSTH_STYLE"])){return "";}
    $GLOBALS["CSTH_STYLE"]=true;
    return "<style>
.csth{margin-top:4px}
.csth .csth-banner{display:flex;gap:18px;align-items:flex-start;border-left:4px solid var(--ak-accent,#10b981);padding:20px 24px;margin-bottom:16px}
.csth .csth-banner h2{font-size:19px;font-weight:600;margin:0 0 6px 0;color:#111d23}
.csth .csth-banner p{margin:0 0 10px 0;color:#3d4947;font-size:14px;line-height:1.5}
.csth-hot{margin-bottom:16px}
.csth-figs{margin-bottom:16px}
.csth-spark{display:block}
.csth-spark rect{fill:var(--ak-accent,#10b981);opacity:.55}
.csth-spark rect.csth-today{opacity:1}
.csth-help{display:flex;flex-wrap:wrap;gap:6px 24px;color:#6d7a77;font-size:13px;margin:6px 2px 12px}
.csth-help code{background:#f1f5f9;border-radius:4px;padding:1px 5px;color:#3d4947;font-size:13px}
.csth-count{color:#3d4947;font-size:13px;margin:0 2px 10px}
.csth-list{display:flex;flex-direction:column;gap:8px}
.csth-row{display:grid;grid-template-columns:minmax(200px,1.2fr) minmax(140px,1fr) minmax(200px,1.6fr) 180px 140px auto;gap:16px;align-items:center;padding:14px 18px}
.csth-row>*{min-width:0}
.csth-row .ak-pill{white-space:normal;text-align:left;line-height:1.3}
.csth-id{display:flex;gap:12px;align-items:center;min-width:0}
.csth-ip{font-size:15px;font-weight:600;color:#111d23;word-break:break-all}
.csth-sub{font-size:13px;color:#6d7a77;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.csth-loc{font-size:13px;color:#3d4947}
.csth-flag{width:20px;height:auto;flex:0 0 auto}
.csth-seen{text-align:right;font-size:13px;color:#111d23}
.csth-seen .csth-sub{text-align:right}
.csth-acts{display:flex;gap:6px;align-items:center;justify-content:flex-end}
.csth-st{display:inline-flex;gap:6px;align-items:flex-start;font-size:13px;line-height:1.35;border-radius:8px;padding:6px 10px;background:var(--ak-tbg);color:var(--ak-tfg);font-weight:500}
.csth-st small{display:block;font-size:13px;opacity:.85}
details.csth-more{grid-column:1/-1;border-top:1px solid #f1f5f9;padding-top:8px}
details.csth-more summary{cursor:pointer;color:#3d4947;font-size:13px;list-style:none}
details.csth-more summary::-webkit-details-marker{display:none}
.csth-line{display:flex;gap:12px;align-items:baseline;flex-wrap:wrap;padding:6px 0;border-top:1px dashed #e2e8f0;font-size:13px}
.csth-line:first-child{border-top:0}
.csth-line details{margin-left:auto}
.csth-line details[open]{flex-basis:100%;margin-left:0}
.csth-raw{width:100%;background:#0f172a;color:#e2e8f0;border-radius:6px;padding:8px 10px;font-size:13px;white-space:pre-wrap;word-break:break-all;margin-top:4px}
.csth-tbl td{vertical-align:middle}
.csth-pg{justify-content:flex-end;margin:12px 2px}
@media(max-width:1200px){.csth-row{grid-template-columns:1fr 1fr;}.csth-seen,.csth-seen .csth-sub{text-align:left}.csth-acts{justify-content:flex-start}}
</style>";
}

function csth_banner():string{
    $h=array();
    $h[]="<div class='ak-card csth-banner'>";
    $h[]="<span class='ak-ico ak-ico-lg ak-accent' aria-hidden='true'><i class='fas fa-shield-halved'></i></span>";
    $h[]="<div><h2>{csth_banner_title}</h2><p>{csth_banner_text}</p>";
    $h[]="<div class='ak-chips'>";
    $h[]="<span class='ak-tag'><i class='fas fa-microchip'></i> {csth_chip_local}</span>";
    $h[]="<span class='ak-tag'><i class='fas fa-hourglass-half'></i> {csth_chip_expire}</span>";
    $h[]="<span class='ak-tag'><i class='fas fa-rotate-left'></i> {csth_chip_undo}</span>";
    $h[]="</div></div></div>";
    return implode("",$h);
}

function csth_hot(array $m):string{
    if($m["hot_ip"]==""){return "";}
    $ip=htmlspecialchars($m["hot_ip"]);
    $txt=csth_t("csth_hot_text",array_combine(array("%ip","%n"),array("<b class='ak-mono'>$ip</b>","<b>".intval($m["hot_n"])."</b>")));
    $js="CsthShowOnly('".addslashes($m["hot_ip"])."')";
    return "<div class='ak-banner ak-warn csth-hot' role='status'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>"
        ."<span class='ak-banner-m'>$txt</span><button type='button' class='ak-link' OnClick=\"$js\">{csth_show_only} <i class='fas fa-arrow-right'></i></button></div>";
}

function csth_spark(array $m):string{
    $tpl=new template_admin();
    $max=max(1,max($m["days"]));
    $w=8;$gap=3;$hgt=30;$x=0;$i=0;$n=count($m["days"]);$off=intval(date("Z"));
    $s="<svg class='csth-spark' width='".($n*($w+$gap))."' height='$hgt' role='img' aria-label='{csth_spark_label}'>";
    foreach($m["days"] as $d=>$c){
        $i++;
        $bh=$c>0?max(2,round($c/$max*$hgt)):1;
        $day=htmlspecialchars($tpl->time_to_date($d*86400-$off+43200));
        $cls=$i==$n?" class='csth-today'":"";
        $s.="<rect$cls x='$x' y='".($hgt-$bh)."' width='$w' height='$bh' rx='1'><title>$day: $c</title></rect>";
        $x+=$w+$gap;
    }
    return $s."</svg>";
}

function csth_figures(array $m):string{
    $tpl=new template_admin();
    $h=array();
    $h[]="<div class='ak-grid-3 csth-figs'>";
    $h[]="<div class='ak-card ak-card-pad ak-fig'><div class='ak-fig-txt'><div class='ak-fig-l'>{csth_fig_alerts}</div>"
        ."<div class='ak-fig-v'>".$tpl->FormatNumber($m["total"])."</div>"
        ."<div class='ak-fig-s'>".csth_t("csth_fig_alerts_sub",array("%n"=>$tpl->FormatNumber($m["days_total"])))."</div></div>".csth_spark($m)."</div>";
    $nt=$m["addr_total"]-$m["trusted_n"];
    $h[]="<div class='ak-card ak-card-pad ak-fig'><div class='ak-fig-txt'><div class='ak-fig-l'>{csth_fig_addr}</div>"
        ."<div class='ak-fig-v'>".$tpl->FormatNumber($m["addr_total"])."</div>"
        ."<div class='ak-fig-s'>".csth_t("csth_fig_addr_sub",array_combine(array("%t","%n"),array(intval($m["trusted_n"]),intval($nt))))."</div></div>"
        ."<span class='ak-ico ak-off' aria-hidden='true'><i class='fas fa-network-wired'></i></span></div>";
    $top="—";
    if($m["top"]!=""){$l=csth_scenario($m["top"]);$top=$l[0];}
    $h[]="<div class='ak-card ak-card-pad ak-fig'><div class='ak-fig-txt'><div class='ak-fig-l'>{csth_fig_beh}</div>"
        ."<div class='ak-fig-v'>".$tpl->FormatNumber($m["behaviors"])."</div>"
        ."<div class='ak-fig-s'>{csth_fig_beh_sub} <b>$top</b></div></div>"
        ."<span class='ak-ico ak-off' aria-hidden='true'><i class='fas fa-user-secret'></i></span></div>";
    $h[]="</div>";
    return implode("",$h);
}

function csth_status(array $a,array $bans):string{
    if($a["trusted"]){
        return "<span class='csth-st ak-info'><i class='fas fa-circle-check'></i><span>{csth_trusted}</span></span>";
    }
    if(!$bans["available"]){
        return "<span class='csth-st ak-off' title='{csth_unknown_help}'><i class='fas fa-circle-question'></i><span>{csth_unknown}</span></span>";
    }
    if(!isset($bans["map"][$a["ip"]])){
        return "<span class='csth-st ak-off'><i class='fas fa-circle-minus'></i><span>{csth_not_blocked}</span></span>";
    }
    $t=intval($bans["map"][$a["ip"]]);
    if($t<0){return "<span class='csth-st ak-off'><i class='fas fa-circle-question'></i><span>{csth_unknown}</span></span>";}
    $left=$t>0?"<small>".csth_t("csth_lifted_in",array("%s"=>csth_duration($t)))."</small>":"";
    return "<span class='csth-st ak-err' title='{csth_lifted_help}'><i class='fas fa-ban'></i><span>{csth_blocked}$left</span></span>";
}

function csth_actions(string $ip,bool $trusted,string $function):string{
    $page=CurrentPageName();
    $ipenc=urlencode($ip);
    $h="<div class='csth-acts'>";
    if(!$trusted){
        $h.="<button type='button' class='ak-btn ak-btn-sm' title='{csth_trust_help}' OnClick=\"Loadjs('$page?csth-trust-js=$ipenc&function=$function')\"><i class='fas fa-handshake'></i> {csth_trust}</button>";
    }
    $h.="<button type='button' class='ak-btn-icon ak-btn-ghost ak-btn' aria-label='{csth_details}' title='{csth_details}' OnClick=\"Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$ipenc')\"><i class='fas fa-magnifying-glass-plus'></i></button>";
    return $h."</div>";
}

function csth_addr_row(array $a,array $bans,string $function):string{
    $page=CurrentPageName();
    $hi=csth_host_info($a["ip"]);
    $ip=htmlspecialchars($a["ip"]);
    $sub=$hi["known"]&&$hi["host"]!=""?"<span class='ak-mono'>".htmlspecialchars($hi["host"])."</span>":($hi["known"]?"":"{csth_unresolved}");
    $loc=array();
    if($hi["known"]){foreach(array("country","city","org") as $k){ if($hi[$k]!=""){$loc[]=htmlspecialchars($hi[$k]);} }}
    $locs=count($loc)>0?implode(" · ",$loc):"<span class='ak-muted'>—</span>";
    $chips=array();$i=0;
    foreach($a["sc"] as $sc=>$n){
        if(++$i>3){$chips[]="<span class='ak-count'>+".(count($a["sc"])-3)."</span>";break;}
        $l=csth_scenario($sc);
        $chips[]="<span class='ak-pill ak-accent' title='".htmlspecialchars($sc)."'>{$l[0]} <span class='ak-count'>×".intval($n)."</span></span>";
    }
    $first=csth_valid_ts($a["first"])?csth_t("csth_first_seen",array("%s"=>csth_ago($a["first"]))):"";
    $id="csth-x-".md5($a["ip"]);
    $ipenc=urlencode($a["ip"]);
    $h=array();
    $h[]="<div class='ak-card csth-row'>";
    $h[]="<div class='csth-id'>".csth_flag($hi)."<div style='min-width:0'><div class='csth-ip ak-mono'>$ip</div><div class='csth-sub'>$sub</div></div></div>";
    $h[]="<div class='csth-loc'>$locs</div>";
    $h[]="<div class='ak-chips'>".implode("",$chips)."</div>";
    $h[]="<div>".csth_status($a,$bans)."</div>";
    $h[]="<div class='csth-seen'><span title='".csth_exact($a["last"])."'>".csth_ago($a["last"])."</span><div class='csth-sub'>$first</div></div>";
    $h[]=csth_actions($a["ip"],$a["trusted"],$function);
    $h[]="<details class='csth-more' ontoggle=\"if(this.open&&!this.getAttribute('data-l')){this.setAttribute('data-l','1');LoadAjaxSilent('$id','$page?csth-ip-alerts=$ipenc');}\">"
        ."<summary><i class='fas fa-chevron-down ak-chev'></i> ".csth_t("csth_recent",array("%n"=>intval($a["n"])))."</summary><div id='$id'></div></details>";
    $h[]="</div>";
    return implode("",$h);
}

function csth_empty(array $m):string{
    if($m["total"]==0){
        return "<div class='ak-card ak-card-pad ak-empty-c'><i class='fas fa-shield-heart' aria-hidden='true'></i>"
            ."<div><b>{csth_empty_title}</b></div><div>{csth_empty_text}</div></div>";
    }
    $q=htmlspecialchars($m["search"]);
    return "<div class='ak-card ak-card-pad ak-empty-c'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<div>".csth_t("csth_no_match",array("%s"=>"<b>$q</b>"))."</div>"
        ."<button type='button' class='ak-btn' OnClick=\"CsthShowOnly('')\">{csth_clear}</button></div>";
}

function csth_addr_list(array $m,string $function):string{
    if(count($m["addresses"])==0){return csth_empty($m);}
    $tpl=new template_admin();
    $h=array();
    $cnt=csth_t("csth_showing_addr",array("%n"=>$tpl->FormatNumber(count($m["addresses"]))));
    if($m["addr_match"]>count($m["addresses"])){$cnt.=" — ".csth_t($m["search"]==""?"csth_in_total":"csth_of_total",array("%n"=>$tpl->FormatNumber($m["addr_match"])));}
    $h[]="<div class='csth-count'>$cnt</div><div class='csth-list' id='csth-list'>";
    foreach($m["addresses"] as $a){$h[]=csth_addr_row($a,$m["bans"],$function);}
    $h[]="</div><div class='ak-pg csth-pg' id='csth-pager'></div>";
    return implode("",$h);
}

function csth_alerts_table(array $m,string $function):string{
    if(count($m["alerts"])==0){return csth_empty($m);}
    $tpl=new template_admin();
    $cnt=csth_t("csth_showing_alerts",array("%n"=>$tpl->FormatNumber(count($m["alerts"]))));
    if($m["alerts_match"]>count($m["alerts"])){$cnt.=" — ".csth_t($m["search"]==""?"csth_in_total_alerts":"csth_of_total_alerts",array("%n"=>$tpl->FormatNumber($m["alerts_match"])));}
    $h=array();
    $h[]="<div class='csth-count'>$cnt</div>";
    $h[]="<div class='ak-card'><table class='ak-tbl csth-tbl'><thead><tr><th>{csth_col_time}</th><th>{csth_col_addr}</th><th>{csth_col_beh}</th>"
        ."<th class='ak-num' title='{csth_events_help}'>{csth_col_events}</th><th>{csth_col_status}</th><th class='ak-end'></th></tr></thead><tbody id='csth-rows'>";
    foreach($m["alerts"] as $r){
        $ip=(string)$r["ipaddr"];
        $hi=csth_host_info($ip);
        $l=csth_scenario((string)$r["scenario"]);
        $sub=$l[1]!=""?$l[1]:"";
        $st=$r["trusted"]?"<span class='ak-tag ak-info'><i class='fas fa-circle-check'></i> {csth_trusted}</span>":"";
        $h[]="<tr>";
        $h[]="<td class='ak-nowrap' title='".csth_exact($r["alertid"])."'>".csth_ago($r["alertid"])."</td>";
        $h[]="<td class='ak-nowrap'>".csth_flag($hi)." <span class='ak-mono'>".htmlspecialchars($ip)."</span></td>";
        $h[]="<td><div class='ak-name'>{$l[0]}</div><div class='ak-code' title='$sub'>".htmlspecialchars((string)$r["scenario"])."</div></td>";
        $h[]="<td class='ak-num'>".intval($r["EventsCount"])."</td>";
        $h[]="<td>$st</td>";
        $h[]="<td class='ak-end'>".csth_actions($ip,(bool)$r["trusted"],$function)."</td>";
        $h[]="</tr>";
    }
    $h[]="</tbody></table></div><div class='ak-pg csth-pg' id='csth-pager'></div>";
    return implode("",$h);
}

// ---- endpoints -------------------------------------------------------------
// Tab body: banner, slot for the figures, and the search host.
function artica_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $view=csth_view();
    $q=isset($_GET["q"])?(string)$_GET["q"]:"";
    $h=array();
    $h[]="<div class='ak csth'>";
    $h[]=csth_banner();
    $h[]="<div id='csth-top'></div>";
    $h[]="<div id='csth-host'></div>";
    $h[]="</div>";
    $js="<script>
function CsthQS(){var i=document.querySelector('#csth-host .sb-stitch input');return encodeURIComponent(i?i.value:'');}
function CsthLoad(view,q){window.CsthView=view;LoadAjaxSilent('csth-host','$page?csth-host=yes&view='+view+'&q='+q);}
function CsthSetView(v){CsthLoad(v,CsthQS());}
function CsthShowOnly(ip){CsthLoad(window.CsthView||'addr',encodeURIComponent(ip));}
window.CsthView='$view';
CsthLoad('$view','".urlencode($q)."');
</script>";
    echo csth_style().$tpl->_ENGINE_parse_body(implode("",$h)).$js;
    return true;
}

// The search box; its urlToadd carries the current view.
function csth_host():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $view=csth_view();
    $q=isset($_GET["q"])?trim((string)$_GET["q"]):"";
    $h=array();
    $h[]="<div style='margin-top:10px'>".$tpl->search_block($page,"","",$q==""?null:"value=$q","&artica-search=yes&view=$view")."</div>";
    $h[]="<div class='csth-help'><span>{csth_examples} <code>203.0.113.7</code> · <code>203.0.113.</code> · <code>192.0.2.0/24</code> · <code>ssh</code> · <code>password</code></span>"
        ."<span>{csth_events_help}</span></div>";
    echo $tpl->_ENGINE_parse_body(implode("",$h));
    return true;
}

// search_block responder: figures (moved above), list, TINY.
function artica_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $view=csth_view();
    $function=csth_function();
    $search=isset($_GET["search"])?trim((string)$_GET["search"]):"";
    $m=csth_model($search,$view);
    $h=array();
    if($m["error"]!=""){
        echo $tpl->div_error($m["error"]);
        return false;
    }
    $h[]="<div id='csth-top-src' style='display:none'>".csth_hot($m).csth_figures($m)."</div>";
    $h[]=$view=="addr"?csth_addr_list($m,$function):csth_alerts_table($m,$function);

    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"csth-view","LABEL"=>"{csth_view}","VALUE"=>$view,
        "OPTIONS"=>array("addr"=>"{csth_by_address}","alerts"=>"{csth_all_alerts}"),"JS"=>"CsthSetView");
    $topbuttons=array();
    $topbuttons[]=array("CsthShowOnly(decodeURIComponent(CsthQS()))",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_CROWDSEC} {threats}";
    $TINY_ARRAY["ICO"]="fas fa-shield-halved";
    $TINY_ARRAY["EXPL"]="{csth_tiny_explain}";
    $TINY_ARRAY["URL"]="crowdsec-status";
    $TINY_ARRAY["BUTTONS"]=siem_tiny_filters($fields,$tpl->table_buttons($topbuttons));
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $js="<script>
(function(){var s=document.getElementById('csth-top-src'),d=document.getElementById('csth-top');
if(s&&d){d.innerHTML=s.innerHTML;s.parentNode.removeChild(s);}})();
NoSpinner();
$jstiny
</script>";
    $pager=$view=="addr"?csth_pager_js("csth-list","csth-pager",25):csth_pager_js("csth-rows","csth-pager",25);
    echo siem_tiny_filters_style().csth_style().$tpl->_ENGINE_parse_body(implode("",$h)).$pager.$js;
    return true;
}

// ---- Events tab (journal of the CrowdSec engine: crowdsec.db, LAPI "alerts" + "decisions") ----------
// Shell: banner + host (tiles, toolbar, list) reloaded by CsevRefresh(). Every figure comes from the
// 500 most recent rows of the engine database (CSEV_MAX), the sparkline from creation times of the last 24 h.
function alerts_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=csev_query();
    $topbuttons=array();
    $topbuttons[]=array("CsevRefresh()",ico_refresh,"{csev_refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_CROWDSEC} {events}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_CROWDSEC_EXPLAIN}";
    $TINY_ARRAY["URL"]="crowdsec-status";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html=array();
    $html[]="<div id='csev' class='csev'>";
    $html[]=csev_banner();
    $html[]="<div id='csev-host' data-q='".cssum_esc($q)."'>";
    $html[]=csev_host_html($q);
    $html[]="</div>";
    $html[]="</div>";
    echo csev_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).csev_js($page)."<script>$jstiny</script>";
    return true;
}

// Reloadable part (tiles, toolbar, list).
function csev_host():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(csev_host_html(csev_query()));
    return true;
}

function csev_query():string{
    $q=trim((string)($_GET["q"] ?? ""));
    return substr(preg_replace('/[\x00-\x1f]/','',$q),0,120);
}

function csev_host_html(string $q):string{
    $M=csev_model($q);
    if($M["error"]!=""){ return csev_error($M); }
    $h=array();
    $h[]=csev_tiles($M);
    $h[]=csev_toolbar($M);
    if(count($M["rows"])==0){
        $h[]=csev_empty($M);
    }else{
        $h[]=csev_list($M);
    }
    $h[]=csev_footnote();
    $h[]="<script>if(typeof CsevAfterLoad==='function'){CsevAfterLoad();}</script>";
    return @implode("\n",$h);
}

// ---- model --------------------------------------------------------------------------------------------
// CrowdSec stores times as "2026-11-21 09:19:25.289652718+00:00" (UTC). strtotime() refuses 9 decimals,
// and the old page cut the string at the dot, which also dropped the zone (times shifted by the offset).
function csev_ts($v):int{
    $v=trim((string)$v);
    if(!preg_match('/^(\d{4}-\d\d-\d\d)[ T](\d\d:\d\d:\d\d)(?:\.\d+)?\s*(Z|[+-]\d\d:?\d\d)?/',$v,$re)){return 0;}
    $zone=(isset($re[3]) && $re[3]!="" && $re[3]!="Z")?$re[3]:"+00:00";
    $ts=strtotime($re[1]." ".$re[2]." ".$zone);
    if($ts===false || $ts<1000000000){return 0;}
    return $ts;
}

// attack | community | manual
function csev_kind(array $r):string{
    $sc=strtolower(trim((string)$r["scenario"]));
    $scope=strtolower(trim((string)$r["source_scope"]));
    if(strpos($sc,"update :")===0 || strpos($scope,"community-blocklist")!==false || strpos($scope,"lists:")===0){return "community";}
    if(strpos($sc,"manual ")===0){return "manual";}
    return "attack";
}

function csev_model(string $search):array{
    $M=array("error"=>"","rows"=>array(),"search"=>$search,"capped"=>false,
        "n"=>array("all"=>0,"attack"=>0,"blocked"=>0,"community"=>0,"manual"=>0),
        "attack_ips"=>0,"blocked_ips"=>0,"top"=>"","top_n"=>0,"hours"=>array(),"hours_total"=>0);
    if(!is_file(CSEV_DB)){ $M["error"]="missing"; return $M; }
    $q=new lib_sqlite(CSEV_DB);
    $now=time();

    // Search: same grammar as the Threats tab (address, start of address, network, words).
    $match=csth_parse_search($search);
    $cols="id,created_at,started_at,stopped_at,scenario,message,events_count,source_ip,source_range,source_scope,source_value,source_country,source_as_name,source_as_number,simulated";
    $sql="SELECT $cols FROM alerts";
    $binds=array();
    $phpfilter=false;
    switch($match[0]){
        case "ip": $sql.=" WHERE source_ip=? OR source_value=?"; $binds=array($match[1],$match[1]); break;
        case "prefix": $sql.=" WHERE source_ip LIKE ? OR source_range LIKE ?"; $binds=array($match[1]."%",$match[1]."%"); break;
        case "cidr": $phpfilter=true; break;
        case "text":
            if(ctype_digit($match[1])){ $sql.=" WHERE id=?"; $binds=array(intval($match[1])); break; }
            $like="%".$match[1]."%";
            $sql.=" WHERE scenario LIKE ? OR message LIKE ? OR source_scope LIKE ? OR source_as_name LIKE ? OR source_country LIKE ?";
            $binds=array($like,$like,$like,$like,$like);
            break;
    }
    $sql.=" ORDER BY id DESC LIMIT ".($phpfilter?5000:CSEV_MAX+1);
    $rows=$q->QUERY_SQL_PARAMS($sql,$binds);
    if(!$q->ok){ $M["error"]=(string)$q->mysql_error; return $M; }
    if(!is_array($rows)){$rows=array();}
    if($phpfilter){
        $keep=array();
        foreach($rows as $r){ if(csth_matches($match,(string)$r["source_ip"],"") || trim((string)$r["source_range"])==$match[1] || trim((string)$r["source_value"])==$match[1]){$keep[]=$r;} }
        $rows=$keep;
    }
    if(count($rows)>CSEV_MAX){ $M["capped"]=true; $rows=array_slice($rows,0,CSEV_MAX); }

    // Decisions of the listed rows (community pulls carry thousands of decisions: left out on purpose).
    $ids=array();
    foreach($rows as $i=>$r){
        $rows[$i]["kind"]=csev_kind($r);
        if($rows[$i]["kind"]!="community"){ $ids[]=intval($r["id"]); }
    }
    $dec=array();
    foreach(array_chunk($ids,400) as $chunk){
        $d=$q->QUERY_SQL_PARAMS("SELECT alert_decisions AS aid,MAX(until) AS until,MIN(type) AS type,MIN(origin) AS origin FROM decisions WHERE alert_decisions IN (".implode(",",array_fill(0,count($chunk),"?")).") GROUP BY alert_decisions",$chunk);
        if(!is_array($d)){continue;}
        foreach($d as $x){ $dec[intval($x["aid"])]=$x; }
    }

    $trusted=csth_trusted_list();
    $attIps=array(); $blkIps=array(); $sc=array();
    foreach($rows as $i=>$r){
        $r["ts"]=csev_ts($r["created_at"]);
        $r["t0"]=csev_ts($r["started_at"]);
        $r["t1"]=csev_ts($r["stopped_at"]);
        $r["sim"]=intval($r["simulated"])==1;
        $ip=trim((string)$r["source_ip"]);
        $r["ip"]=filter_var($ip,FILTER_VALIDATE_IP)?$ip:"";
        $r["trusted"]=$r["ip"]!="" && csth_is_trusted($r["ip"],$trusted);
        $r["until"]=0; $r["dtype"]=""; $r["origin"]="";
        if(isset($dec[intval($r["id"])])){
            $x=$dec[intval($r["id"])];
            $r["until"]=csev_ts($x["until"]);
            $r["dtype"]=(string)$x["type"];
            $r["origin"]=(string)$x["origin"];
            if($r["kind"]=="attack" && strtolower($r["origin"])=="cscli"){ $r["kind"]="manual"; }
        }
        $r["active"]=!$r["sim"] && $r["until"]>$now;
        $M["n"]["all"]++;
        $M["n"][$r["kind"]]++;
        // "Blocked now" = one line per blocked address (its most recent event, rows come newest
        // first), so the filter and the "Currently blocked" tile count the same thing.
        $r["blocked_head"]=false;
        if($r["active"]){
            $key=$r["ip"]!=""?$r["ip"]:(string)$r["source_value"];
            if(!isset($blkIps[$key])){ $blkIps[$key]=true; $r["blocked_head"]=true; $M["n"]["blocked"]++; }
        }
        if($r["kind"]=="attack"){
            if($r["ip"]!=""){$attIps[$r["ip"]]=true;}
            $s=(string)$r["scenario"];
            if(!isset($sc[$s])){$sc[$s]=0;}
            $sc[$s]++;
        }
        $rows[$i]=$r;
    }
    $M["rows"]=$rows;
    $M["attack_ips"]=count($attIps);
    $M["blocked_ips"]=count($blkIps);
    if(count($sc)>0){ arsort($sc); reset($sc); $M["top"]=(string)key($sc); $M["top_n"]=intval(current($sc)); }

    // Activity of the last 24 hours, one bar per hour (whole table, not only the listed rows).
    $start=$now-23*3600; $start=$start-($start%3600);
    for($h=0;$h<24;$h++){ $M["hours"][$h]=0; }
    $lim=gmdate("Y-m-d",$start);
    $t=$q->QUERY_SQL_PARAMS("SELECT created_at FROM alerts WHERE created_at>=? ORDER BY id DESC LIMIT 20000",array($lim));
    if(is_array($t)){
        foreach($t as $x){
            $ts=csev_ts($x["created_at"]);
            if($ts<$start){continue;}
            $slot=intdiv($ts-$start,3600);
            if($slot<0||$slot>23){continue;}
            $M["hours"][$slot]++;
            $M["hours_total"]++;
        }
    }
    return $M;
}

// ---- helpers --------------------------------------------------------------------------------------------
function csev_scenario_name(string $sc):string{
    $l=cssum_scenario_label($sc);
    if($l!=""){return $l;}
    $short=$sc;
    if(strpos($short,"/")!==false){ $short=substr($short,strrpos($short,"/")+1); }
    return cssum_esc(ucfirst(trim(str_replace(array("-","_")," ",$short))));
}
function csev_span($sec):string{
    $sec=max(0,intval($sec));
    if($sec<120){ return cssum_tr("csev_secs",max(1,$sec)); }
    if($sec<7200){ return cssum_tr("csev_mins",intdiv($sec,60)); }
    return cssum_tr("csev_hours",intdiv($sec,3600));
}
function csev_clock(int $ts):string{
    if(date("Y-m-d",$ts)==date("Y-m-d")){ return date("H:i",$ts); }
    return date("Y-m-d H:i",$ts);
}
function csev_ico(string $fa):string{
    return "<span class='csev-i' aria-hidden='true'><i class='$fa'></i></span>";
}

// ---- widgets --------------------------------------------------------------------------------------------
function csev_banner():string{
    $chips=array(
        array("fas fa-robot","csev_chip_auto"),
        array("fas fa-users","csev_chip_community"),
        array("fas fa-hourglass-half","csev_chip_expire"),
        array("fas fa-user-check","csev_chip_trusted"),
    );
    $c=array();
    foreach($chips as $x){ $c[]="<span class='csev-chip'>".csev_ico($x[0])."{".$x[1]."}</span>"; }
    return "<section class='csev-banner'>
<div class='csev-banner-ico'>".csev_ico("fas fa-shield-halved")."</div>
<div><h3>{csev_banner_title}</h3><p>{csev_banner_text}</p><div class='csev-chips'>".implode("",$c)."</div></div>
</section>";
}

function csev_tile_attacks(array $M):string{
    $n=$M["n"]["all"]==0?"—":cssum_num($M["n"]["attack"]);
    $sub=$M["n"]["attack"]>0?cssum_tr("csev_t_attacks_sub",cssum_num($M["attack_ips"])):"{csev_t_attacks_none}";
    return "<div class='csev-tile'><div class='csev-tl'>{csev_t_attacks}</div><div class='csev-big'>$n</div><div class='csev-ts'>$sub</div></div>";
}
function csev_tile_blocked(array $M):string{
    $b=$M["n"]["blocked"];
    $cls=$b>0?" csev-red":"";
    $ico=$b>0?csev_ico("fas fa-ban"):"";
    $n=$M["n"]["all"]==0?"—":cssum_num($M["blocked_ips"]);
    return "<div class='csev-tile'><div class='csev-tl'>{csev_t_blocked}</div><div class='csev-big$cls'>$ico$n</div><div class='csev-ts'>{csev_t_blocked_sub}</div></div>";
}
function csev_tile_top(array $M):string{
    if($M["top"]==""){
        return "<div class='csev-tile'><div class='csev-tl'>{csev_t_top}</div><div class='csev-big'>—</div><div class='csev-ts'>{csev_t_attacks_none}</div></div>";
    }
    return "<div class='csev-tile'><div class='csev-tl csev-tl-row'><span>{csev_t_top}</span><span class='csev-pill'>".cssum_tr("csev_t_top_times",cssum_num($M["top_n"]))."</span></div>
<div class='csev-topname'>".csev_scenario_name($M["top"])."</div><div class='csev-mono csev-ts'>".cssum_esc($M["top"])."</div></div>";
}
// Inline SVG: 24 bars, height relative to the busiest hour.
function csev_tile_activity(array $M):string{
    $max=max(1,max($M["hours"]));
    $bars=array();
    foreach($M["hours"] as $i=>$v){
        $h=$v>0?max(3,round($v/$max*36)):2;
        $col=$v>0?"#005447":"#e5e7eb";
        $x=$i*9;
        $bars[]="<rect x='$x' y='".(38-$h)."' width='6' height='$h' rx='1' fill='$col'><title>$v</title></rect>";
    }
    $n=$M["hours_total"]>0?cssum_tr("csev_t_activity_sub",cssum_num($M["hours_total"])):"{csev_t_activity_none}";
    return "<div class='csev-tile' title=\"".cssum_attr("csev_t_activity_help")."\"><div class='csev-tl csev-tl-row'><span>{csev_t_activity}</span><span class='csev-ts'>$n</span></div>
<svg class='csev-spark' viewBox='0 0 213 38' width='213' height='38' role='img' aria-label=\"".cssum_attr("csev_t_activity_help")."\">".implode("",$bars)."</svg></div>";
}
function csev_tiles(array $M):string{
    return "<div class='csev-tiles'>".csev_tile_attacks($M).csev_tile_blocked($M).csev_tile_top($M).csev_tile_activity($M)."</div>";
}

function csev_toolbar(array $M):string{
    $f=array("all"=>"csev_f_all","attack"=>"csev_f_attacks","blocked"=>"csev_f_blocked","community"=>"csev_f_community","manual"=>"csev_f_manual");
    $b=array();
    foreach($f as $k=>$tok){
        $red=($k=="blocked" && $M["n"][$k]>0)?" csev-cnt-red":"";
        $tip=$k=="blocked"?" title=\"".cssum_attr("csev_f_blocked_help")."\"":"";
        $b[]="<button type='button' role='tab' aria-selected='false' data-f='$k'$tip onclick=\"CsevFilter('$k')\">{".$tok."}<span class='csev-cnt$red'>".cssum_num($M["n"][$k])."</span></button>";
    }
    $v=cssum_esc($M["search"]);
    return "<div class='csev-toolbar'>
<div class='csev-search'><label class='csev-searchbox'>".csev_ico("fas fa-magnifying-glass")."<input type='search' id='csev-q' value='$v' placeholder=\"".cssum_attr("csev_search_ph")."\" aria-label=\"".cssum_attr("csev_search_ph")."\" onkeydown=\"if(event.keyCode==13){CsevSearch(this.value);}\"></label>
<div class='csev-help'>{csev_search_help}</div></div>
<div class='csev-seg' role='tablist'>".implode("",$b)."</div>
</div>";
}

function csev_cell_when(array $r):string{
    if($r["ts"]==0){return "<td class='csev-when'>—</td>";}
    $tip=csth_exact($r["ts"]);
    if($r["t0"]>0 && $r["t1"]>0){ $tip=csth_exact($r["t0"])." → ".csth_exact($r["t1"]); }
    return "<td class='csev-when' title=\"$tip\"><b>".csth_ago($r["ts"])."</b><span>".date("Y-m-d H:i",$r["ts"])."</span></td>";
}
function csev_cell_kind(array $r):string{
    $map=array("attack"=>array("csev-k-att","fas fa-crosshairs","csev_k_attack"),
        "community"=>array("csev-k-com","fas fa-users","csev_k_community"),
        "manual"=>array("csev-k-man","fas fa-user-pen","csev_k_manual"));
    $k=$map[$r["kind"]];
    $h="<span class='csev-badge {$k[0]}'>".csev_ico($k[1])."{".$k[2]."}</span>";
    if($r["sim"]){ $h.="<span class='csev-badge csev-k-sim'>".csev_ico("fas fa-flask")."{csev_k_sim}</span>"; }
    return "<td class='csev-kind'>$h</td>";
}
function csev_cell_who(array $r):string{
    if($r["kind"]=="community"){ return "<td class='csev-who'><span class='csev-muted'>—</span></td>"; }
    $geo=array();
    if(trim((string)$r["source_country"])!=""){ $geo[]=cssum_esc($r["source_country"]); }
    $as=trim((string)$r["source_as_name"]);
    if($as!=""){
        $asn=trim((string)$r["source_as_number"]);
        $geo[]=cssum_esc($as).($asn!="" && $asn!="0"?" (AS".cssum_esc($asn).")":"");
    }
    $sub=count($geo)>0?"<span class='csev-sub'>".implode(" - ",$geo)."</span>":"";
    $scope=strtolower((string)$r["source_scope"]);
    if($r["ip"]==""){
        $v=trim((string)$r["source_value"]);
        if($v==""){$v=trim((string)$r["source_range"]);}
        if($v==""){ return "<td class='csev-who'><span class='csev-muted'>—</span>$sub</td>"; }
        $pre=$scope=="range"?"<span class='csev-sub'>{csev_range}</span>":"";
        return "<td class='csev-who'>$pre<span class='csev-mono csev-ip'>".cssum_esc($v)."</span>$sub</td>";
    }
    return "<td class='csev-who'><span class='csev-mono csev-ip'>".cssum_esc($r["ip"])."</span>$sub</td>";
}
function csev_cell_what(array $r):string{
    $sc=(string)$r["scenario"];
    if($r["kind"]=="community"){
        $title="{csev_comm_title}";
        $line="";
        if(preg_match('/\+(\d+)\s*\/\s*-(\d+)/',$sc,$re)){ $line=cssum_tr("csev_comm_text",cssum_num($re[1]),cssum_num($re[2])); }
    }elseif($r["kind"]=="manual"){
        $title="{csev_manual_title}";
        $line=$r["dtype"]!=""?cssum_tr("csev_manual_text",cssum_esc($r["dtype"])):"";
    }else{
        $title=csev_scenario_name($sc);
        $n=intval($r["events_count"]);
        $line="";
        if($n>0){
            $line=($r["t0"]>0 && $r["t1"]>=$r["t0"])?cssum_tr("csev_attempts_in",cssum_num($n),csev_span($r["t1"]-$r["t0"])):cssum_tr("csev_attempts",cssum_num($n));
        }
    }
    $raw=cssum_esc($sc);
    $msg=trim((string)$r["message"]);
    if($msg!=""){ $raw.=" &bull; ".cssum_esc($msg); }
    $line=$line!=""?"<span class='csev-line'>$line</span>":"";
    return "<td class='csev-what'><b>$title</b>$line<details class='csev-raw'><summary>".csev_ico("fas fa-code")."{csev_inspect}</summary><div class='csev-mono'>$raw</div></details></td>";
}
function csev_cell_result(array $r):string{
    if($r["trusted"]){
        return "<td class='csev-res csev-ok'>".csev_ico("fas fa-user-check")."<span><b>{csev_r_trusted}</b></span></td>";
    }
    if($r["sim"]){
        return "<td class='csev-res csev-grey'>".csev_ico("fas fa-flask")."<span>{csev_r_sim}</span></td>";
    }
    if($r["kind"]=="community"){
        return "<td class='csev-res csev-grey'><span class='csev-muted'>—</span></td>";
    }
    if($r["until"]==0){
        return "<td class='csev-res csev-grey' title=\"".cssum_attr("csev_r_none_help")."\">".csev_ico("fas fa-circle-minus")."<span>{csev_r_none}</span></td>";
    }
    if($r["active"]){
        return "<td class='csev-res csev-red' title=\"".csth_exact($r["until"])."\">".csev_ico("fas fa-ban")."<span><b>".cssum_tr("csev_r_blocked",csev_clock($r["until"]))."</b><small>".cssum_tr("csev_r_in",csth_duration($r["until"]-time()))."</small></span></td>";
    }
    return "<td class='csev-res csev-grey' title=\"".csth_exact($r["until"])."\">".csev_ico("fas fa-circle-check")."<span>{csev_r_expired}<small>".cssum_tr("csev_r_expired_at",csev_clock($r["until"]))."</small></span></td>";
}
function csev_cell_action(array $r):string{
    if($r["kind"]=="community" || $r["ip"]==""){ return "<td class='csev-act'><span class='csev-muted'>—</span></td>"; }
    if($r["trusted"]){ return "<td class='csev-act'><span class='csev-done'>".csev_ico("fas fa-check")."{csev_already_trusted}</span></td>"; }
    $page=CurrentPageName();
    $ipenc=urlencode($r["ip"]);
    $lbl=cssum_attr("csev_trust_help");
    return "<td class='csev-act'><button type='button' class='csev-btn' title=\"$lbl\" aria-label=\"$lbl\" onclick=\"Loadjs('$page?whitelist-js=$ipenc&function=CsevRefresh')\">".csev_ico("fas fa-user-check")."{csev_trust_btn}</button></td>";
}
function csev_row(array $r):string{
    $f=$r["kind"].($r["blocked_head"]?" blocked":"");
    return "<tr data-f='$f'>".csev_cell_when($r).csev_cell_kind($r).csev_cell_who($r).csev_cell_what($r).csev_cell_result($r).csev_cell_action($r)."</tr>";
}
function csev_list(array $M):string{
    $rows=array();
    foreach($M["rows"] as $r){ $rows[]=csev_row($r); }
    $foot=$M["capped"]?cssum_tr("csev_cap",cssum_num(CSEV_MAX)):(count($M["rows"])==1?cssum_tr("csev_count_one"):cssum_tr("csev_count",cssum_num(count($M["rows"]))));
    return "<div class='csev-card'><table class='csev-table'>
<thead><tr><th>{csev_col_when}</th><th>{csev_col_kind}</th><th>{csev_col_who}</th><th>{csev_col_what}</th><th>{csev_col_result}</th><th class='csev-r'>{csev_col_action}</th></tr></thead>
<tbody id='csev-rows'>".implode("",$rows)."</tbody></table>
<div class='csev-fempty' id='csev-fempty' hidden>{csev_filter_empty}</div>
<div class='csev-foot'><span>$foot</span><span class='csev-pager' id='csev-pager'></span></div></div>";
}
function csev_empty(array $M):string{
    if($M["search"]!=""){
        return "<div class='csev-card csev-empty'><div class='csev-empty-ico'>".csev_ico("fas fa-magnifying-glass")."</div>
<h4>".cssum_tr("csev_nomatch_title",cssum_esc($M["search"]))."</h4><p>{csev_nomatch_text}</p>
<button type='button' class='csev-btn' onclick=\"CsevSearch('')\">".csev_ico("fas fa-xmark")."{csev_clear_search}</button></div>";
    }
    return "<div class='csev-card csev-empty'><div class='csev-empty-ico'>".csev_ico("fas fa-shield-check")."</div>
<h4>{csev_empty_title}</h4><p>{csev_empty_text}</p>
<button type='button' class='csev-btn' onclick=\"CsevRefresh()\">".csev_ico("fas fa-rotate")."{csev_refresh}</button></div>";
}
function csev_error(array $M):string{
    $txt=$M["error"]=="missing"?"{csev_error_missing}":cssum_tr("csev_error_text",cssum_esc($M["error"]));
    return "<div class='csev-card csev-empty csev-err'><div class='csev-empty-ico'>".csev_ico("fas fa-triangle-exclamation")."</div>
<h4>{csev_error_title}</h4><p>$txt</p>
<button type='button' class='csev-btn' onclick=\"CsevRefresh()\">".csev_ico("fas fa-rotate")."{csev_refresh}</button></div>";
}
function csev_footnote():string{
    return "<p class='csev-note'>".csev_ico("fas fa-circle-info")."{csev_foot_prune}</p>";
}

// ---- JS --------------------------------------------------------------------------------------------------
// One timer (30 s), skipped while a modal / SweetAlert is open, while the search field is being edited
// or while a raw line is expanded; it stops by itself once the tab is gone.
function csev_js(string $page):string{
    return "<script>
window.CsevFilterKey=window.CsevFilterKey||'all';
window.CsevPage=1;
function CsevHost(){return document.getElementById('csev-host');}
function CsevQ(){var h=CsevHost();return h?(h.getAttribute('data-q')||''):'';}
function CsevLoad(q){var h=CsevHost();if(!h){return;}h.setAttribute('data-q',q);LoadAjaxSilent('csev-host','$page?csev-host=yes&q='+encodeURIComponent(q));}
function CsevRefresh(){CsevLoad(CsevQ());}
function CsevSearch(q){window.CsevPage=1;CsevLoad(String(q||'').trim());}
function CsevFilter(k){window.CsevFilterKey=k;window.CsevPage=1;CsevApply();}
function CsevApply(){
 var k=window.CsevFilterKey,tb=document.getElementById('csev-rows'),sz=50;
 [].forEach.call(document.querySelectorAll('#csev .csev-seg button'),function(b){var on=b.getAttribute('data-f')===k;b.setAttribute('aria-selected',on?'true':'false');b.className=on?'on':'';});
 if(!tb){return;}
 var rows=[].slice.call(tb.rows),vis=[];
 rows.forEach(function(r){var f=' '+r.getAttribute('data-f')+' ';var ok=(k==='all')||(f.indexOf(' '+k+' ')>=0);r.style.display='none';if(ok){vis.push(r);}});
 var pg=Math.max(1,Math.ceil(vis.length/sz));if(window.CsevPage>pg){window.CsevPage=pg;}
 var cur=window.CsevPage;
 vis.forEach(function(r,i){if(i>=(cur-1)*sz&&i<cur*sz){r.style.display='';}});
 var e=document.getElementById('csev-fempty');if(e){e.hidden=vis.length>0;}
 var P=document.getElementById('csev-pager');if(!P){return;}
 if(pg<=1){P.innerHTML='';return;}
 var h='',s=Math.max(1,Math.min(cur-2,pg-4)),en=Math.min(pg,s+4);
 h+='<button type=\"button\" data-p=\"'+(cur-1)+'\" aria-label=\"&lsaquo;\"'+(cur==1?' disabled':'')+'>&lsaquo;</button>';
 for(var p=s;p<=en;p++){h+='<button type=\"button\" data-p=\"'+p+'\"'+(p==cur?' class=\"on\" aria-current=\"page\"':'')+'>'+p+'</button>';}
 h+='<button type=\"button\" data-p=\"'+(cur+1)+'\" aria-label=\"&rsaquo;\"'+(cur==pg?' disabled':'')+'>&rsaquo;</button>';
 P.innerHTML=h;
 [].forEach.call(P.querySelectorAll('button'),function(b){b.onclick=function(){var q=parseInt(this.getAttribute('data-p'),10);if(q>=1&&q<=pg){window.CsevPage=q;CsevApply();}};});
}
function CsevAfterLoad(){NoSpinner();CsevApply();CsevArm();}
function CsevBusy(){
 if(document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.sweet-alert.showSweetAlert')){return true;}
 var i=document.getElementById('csev-q');if(i&&(document.activeElement===i||i.value.trim()!==CsevQ())){return true;}
 if(document.querySelector('#csev details[open]')){return true;}
 return false;
}
function CsevArm(){
 if(window.CsevTimer){clearTimeout(window.CsevTimer);}
 window.CsevTimer=setTimeout(function(){window.CsevTimer=null;if(!CsevHost()){return;}if(CsevBusy()){CsevArm();return;}CsevRefresh();},30000);
}
CsevAfterLoad();
</script>";
}

// ---- CSS -------------------------------------------------------------------------------------------------
function csev_style():string{
    $A="#005447";
    return "<style>
.csev{color:#0f172a;font-size:14px;line-height:1.45;margin-top:15px}
.csev *{box-sizing:border-box}
.csev h3,.csev h4,.csev p{margin:0}
.csev [hidden]{display:none!important}
.csev button{font-family:inherit}
.csev button:focus-visible,.csev input:focus-visible,.csev summary:focus-visible{outline:2px solid $A;outline-offset:2px}
.csev-i{display:inline-flex;align-items:center;justify-content:center;width:1.1em;flex:0 0 auto}
.csev-mono{font-family:'JetBrains Mono',SFMono-Regular,Menlo,Consolas,monospace}
.csev-muted{color:#94a3b8}
.csev-banner{display:flex;gap:16px;padding:22px 24px;background:#f2f6f6;border:1px solid #bfd4d1;border-radius:12px;margin-bottom:20px}
.csev-banner-ico{width:42px;height:42px;border-radius:50%;background:#e6eeed;color:$A;display:flex;align-items:center;justify-content:center;font-size:18px;flex:0 0 42px}
.csev-banner h3{font-size:20px;font-weight:600;margin-bottom:6px}
.csev-banner p{color:#334155;max-width:980px}
.csev-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.csev-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#e6eeed;color:$A;font-size:13px;font-weight:500}
.csev-tiles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:20px}
.csev-tile{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;min-height:118px;min-width:0}
.csev-tl{font-size:14px;color:#374151;margin-bottom:6px}
.csev-tl-row{display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
.csev-big{font-size:28px;font-weight:700;line-height:36px;display:flex;align-items:center;gap:8px}
.csev-big.csev-red{color:#b91c1c}
.csev-ts{font-size:13px;color:#4b5563;margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.csev-pill{background:#e6eeed;color:$A;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:600;white-space:nowrap}
.csev-topname{font-size:16px;font-weight:600;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.csev-spark{display:block;margin-top:10px;max-width:100%}
.csev-toolbar{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:16px;flex-wrap:wrap}
.csev-search{flex:0 1 540px;min-width:280px}
.csev-searchbox{display:flex;align-items:center;gap:8px;height:40px;padding:0 12px;border:1px solid #d1d5db;border-radius:8px;background:#fff;color:#6b7280;font-weight:400;margin:0;width:100%}
.csev-searchbox input{border:0;outline:0;flex:1;min-width:0;font-size:14px;color:#0f172a;background:transparent;height:38px}
.csev-searchbox:focus-within{border-color:$A;box-shadow:0 0 0 2px #bfd4d1}
.csev-help{font-size:12px;color:#6b7280;margin-top:4px}
.csev-seg{display:flex;gap:2px;padding:3px;background:#f1f5f9;border:1px solid #e5e7eb;border-radius:10px;flex-wrap:wrap}
.csev-seg button{border:0;background:transparent;padding:6px 12px;border-radius:8px;font-size:14px;color:#374151;display:inline-flex;align-items:center;gap:6px;cursor:pointer}
.csev-seg button:hover{background:#e6eeed}
.csev-seg button.on{background:#fff;color:#0f172a;font-weight:600;box-shadow:0 1px 2px rgba(0,0,0,.08)}
.csev-cnt{font-size:12px;padding:0 7px;border-radius:999px;background:#e6eeed;color:$A;font-weight:600}
.csev-cnt-red{background:#fef2f2;color:#b91c1c}
.csev-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden}
.csev-table{width:100%;border-collapse:collapse;table-layout:fixed}
.csev-table th{background:#f8fafc;text-align:left;font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#4b5563;padding:12px 16px;border-bottom:1px solid #e5e7eb}
.csev-table th:nth-child(1){width:150px}.csev-table th:nth-child(2){width:190px}.csev-table th:nth-child(3){width:210px}
.csev-table th:nth-child(5){width:190px}.csev-table th:nth-child(6){width:150px}
.csev-table td{padding:14px 16px;border-bottom:1px solid #f1f5f9;vertical-align:top;color:#0f172a}
.csev-table tbody tr:hover td{background:#f8fafb}
.csev-r{text-align:right!important}
.csev-when b{display:block;font-weight:600}
.csev-when span,.csev-sub{display:block;font-size:13px;color:#4b5563}
.csev-when span{white-space:nowrap}
.csev-badge{white-space:nowrap;display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;margin:0 4px 4px 0;border:1px solid transparent}
.csev-k-att{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.csev-k-com{background:#e6eeed;color:$A;border-color:#bfd4d1}
.csev-k-man{background:#fffbeb;color:#92400e;border-color:#fde68a}
.csev-k-sim{background:#fff;color:#4b5563;border-color:#cbd5e1}
.csev-who{word-break:break-word}
.csev-ip{font-size:14px;font-weight:500}
.csev-what b{display:block;font-weight:600}
.csev-line{display:block;color:#374151}
.csev-raw{margin-top:6px;font-size:13px;color:#4b5563}
.csev-raw summary{cursor:pointer;display:inline-flex;align-items:center;gap:4px;list-style:none}
.csev-raw summary::-webkit-details-marker{display:none}
.csev-raw div{margin-top:6px;padding:8px 10px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:6px;font-size:12px;word-break:break-word;color:#334155}
.csev-res{display:table-cell}
.csev-res .csev-i{margin-right:6px;vertical-align:top;margin-top:3px}
.csev-res>span:last-child{display:inline-block;vertical-align:top;max-width:calc(100% - 26px)}
.csev-res small{display:block;font-size:13px}
.csev-red,.csev-table td.csev-red{color:#b91c1c}.csev-grey,.csev-table td.csev-grey{color:#4b5563}.csev-ok,.csev-table td.csev-ok{color:$A}
.csev-act{text-align:right}
.csev-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid $A;border-radius:8px;background:#fff;color:$A;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.csev-btn:hover{background:$A;color:#fff}
.csev-done{color:$A;font-size:13px;display:inline-flex;gap:6px;align-items:center}
.csev-fempty{padding:28px;text-align:center;color:#4b5563}
.csev-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 16px;background:#f8fafc;color:#4b5563;font-size:13px}
.csev-pager{display:flex;gap:4px}
.csev-pager button{min-width:30px;height:30px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#374151;cursor:pointer}
.csev-pager button.on{background:$A;border-color:$A;color:#fff}
.csev-pager button:disabled{opacity:.4;cursor:default}
.csev-empty{text-align:center;padding:40px 24px}
.csev-empty-ico{width:56px;height:56px;border-radius:50%;background:#e6eeed;color:$A;display:inline-flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:12px}
.csev-empty h4{font-size:16px;font-weight:600;margin-bottom:6px}
.csev-empty p{color:#4b5563;max-width:560px;margin:0 auto 14px}
.csev-err .csev-empty-ico{background:#fef2f2;color:#b91c1c}
.csev-note{display:flex;gap:8px;align-items:flex-start;color:#4b5563;font-size:13px;margin-top:14px!important}
@media (max-width:1200px){.csev-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (prefers-reduced-motion:no-preference){.csev-seg button,.csev-btn,.csev-table td{transition:background-color .15s,color .15s}}
</style>";
}

// Last 10 alerts of one address (expander).
function csth_ip_alerts():bool{
    $tpl=new template_admin();
    $ip=trim((string)$_GET["csth-ip-alerts"]);
    if(!filter_var($ip,FILTER_VALIDATE_IP)){echo $tpl->div_error("{invalid_ip_address} ".htmlspecialchars($ip));return false;}
    $q=new lib_sqlite(CSTH_DB);
    $rows=$q->QUERY_SQL_PARAMS("SELECT EventsCount,message,scenario,alertid FROM alerts WHERE ipaddr=? ORDER BY ID DESC LIMIT 10",array($ip));
    if(!is_array($rows)||count($rows)==0){echo $tpl->_ENGINE_parse_body("<div class='ak-note'>{csth_no_alert_ip}</div>");return true;}
    $h=array();
    foreach($rows as $r){
        $l=csth_scenario((string)$r["scenario"]);
        $h[]="<div class='csth-line'><span class='ak-nowrap' title='".csth_exact($r["alertid"])."'>".csth_ago($r["alertid"])."</span>"
            ."<b>{$l[0]}</b><span class='ak-muted ak-mono'>".htmlspecialchars((string)$r["scenario"])."</span>"
            ."<span>".csth_t("csth_n_events",array("%n"=>intval($r["EventsCount"])))."</span>"
            ."<details><summary class='ak-link'>{csth_raw}</summary><div class='csth-raw'>".htmlspecialchars((string)$r["message"])."</div></details></div>";
    }
    echo $tpl->_ENGINE_parse_body(implode("",$h));
    return true;
}

// Trust confirmation, a real modal (js_dialog_confirm_action splits every "." of an IP address).
function csth_trust_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ip=trim((string)$_GET["csth-trust-js"]);
    if(!filter_var($ip,FILTER_VALIDATE_IP)){$tpl->js_error("{invalid_ip_address} $ip");return false;}
    $function=csth_function();
    $title=str_replace("%s",$ip,$tpl->javascript_parse_text("{csth_trust_title}"));
    return $tpl->js_dialog2($title,"$page?csth-trust-popup=".urlencode($ip)."&function=$function",520);
}
function csth_trust_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ip=trim((string)$_GET["csth-trust-popup"]);
    if(!filter_var($ip,FILTER_VALIDATE_IP)){echo $tpl->div_error("{invalid_ip_address}");return false;}
    $function=csth_function();
    $after=$function!=""?"if(typeof $function==='function'){{$function}();}":"";
    $ipjs=addslashes($ip);
    $h=array();
    $h[]="<div class='ak' style='padding:4px 4px 0'>";
    $h[]="<p style='font-size:14px;line-height:1.55;margin:0 0 10px'>".csth_t("csth_trust_text",array("%s"=>"<b class='ak-mono'>".htmlspecialchars($ip)."</b>"))."</p>";
    $h[]="<div class='ak-note' style='margin-bottom:16px'><i class='fas fa-circle-info'></i> {csth_trust_undo}</div>";
    $h[]="<div class='ak-actions'><span class='ak-grow'></span>";
    $h[]="<button type='button' class='ak-btn ak-btn-ghost' OnClick=\"dialogInstance2.close();\">{csth_cancel}</button>";
    $h[]="<button type='button' class='ak-btn ak-btn-primary' id='csth-trust-go' OnClick=\"CsthTrustGo()\"><i class='fas fa-handshake'></i> {csth_trust}</button>";
    $h[]="</div></div>";
    $js="<script>
function CsthTrustGo(){
 var b=document.getElementById('csth-trust-go'); if(b){b.disabled=true;b.setAttribute('aria-busy','true');}
 var X=new XHRConnection(); X.appendData('whitelist-ip','$ipjs');
 X.sendAndLoad('$page','POST',function(o){
   var r=String(o.responseText||'');
   if(r.length>3){ if(b){b.disabled=false;b.removeAttribute('aria-busy');}
     swal({title:'Oops...',text:r.replace(/^jserror:/,''),html:false,type:'error'}); return; }
   dialogInstance2.close(); $after
 });
}
</script>";
    echo $tpl->_ENGINE_parse_body(implode("",$h)).$js;
    return true;
}

function threads_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $search=$_GET["search"];

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/stats"));
    if(!$json->Status){
        $html=$tpl->widget_style1("red-bg",ico_bug,"{error}",$json->Error);
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }
    $Metrics=$json->Metrics;

    if(!property_exists($Metrics,"alerts")){
        $html=$tpl->widget_style1("red-bg",ico_bug,"{error}","No property");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }

    $html[]="			<table class='table table-striped'>";
    $html[]="				<thead>";
    $html[]="					<tr>";
    $html[]="						<th nowrap></th>";
    $html[]="						<th nowrap>{engine}</th>";
    $html[]="                        <th style='width:1%;font-size:16px;text-align: right'>{threats}</th>";
    $html[]="					</tr>";
    $html[]="				</thead>";
    $html[]="				<tbody>";
    $TRCLASS=null;
    foreach ($Metrics->alerts as $alert=>$threats){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        if(strpos($alert,"/")>0){
            $aa=explode("/",$alert);
            $alert=$aa[1];
        }
        if(strlen($search)>0){
            if(!preg_match("#".$search."#",$alert)){
                continue;
            }
        }

        $md=md5("$alert$threats");
        $threats=$tpl->FormatNumber($threats);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%;;font-size:16px'><i class='".ico_list."'></i></td>";
        $html[]="<td style='width:99%;font-size:16px' nowrap>$alert</td>";
        $html[]="<td style='width:1%;font-size:16px;text-align: right' class='font-bold' nowrap>$threats</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function resolv_this():bool{
    $sock=new sockets();
    $ips=array();
    foreach ( $_SESSION["RESOLV_THIS"] as $ipaddr){
        $ips[]=$ipaddr;


    }

    if(count($ips)==0){return true;}
    $sock->REST_API(sprintf("/system/network/scanips/%s",@implode(",",$ips)));
    return true;
}
