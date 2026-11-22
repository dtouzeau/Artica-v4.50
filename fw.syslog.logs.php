<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
// Les repartiteurs ?enable-signature= et ?enable-firewall= appelaient
// enable_signature() et enable_firewall(), qui ne sont definies NI dans ce
// fichier NI ailleurs: les deux URL repondaient un fatal « Call to undefined
// function ». La paire appartient a la famille Suricata (elle bascule une
// signature dans suricata-rules.db et un drapeau dans suricata_sig) et n'a
// rien a faire ici; seul fw.ids.rules.php produit reellement
// « ?enable-signature= ».
// Repartiteur(s) mort(s) retire(s): rule_settings()/rule_save() ne sont
// definies NI dans ce fichier NI ailleurs, et aucun code du depot ne produit
// le parametre qui les declenchait -- ni cette page, ni une autre. Les URL
// repondaient donc un fatal « Call to undefined function ». Vestige d'un
// squelette de page recopie; les pages qui utilisent VRAIMENT ?rule-popup=
// ou ?rule-settings= definissent leur propre handler.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["form"])){form();exit;}
if(isset($_GET["search"])){table();exit;}
page();

// ===========================================================================
// Journal du moteur syslog (maquette stitch_artica_log_engine_viewer, 2026-11-22).
//
// Le fichier lu est /var/log/rsyslogd.log (articarest, liste fermee de
// /system/logfile/search) : rsyslog n'y ecrit que les messages des programmes
// « rsyslogd » et « liblogging-stdlog » (logsink/Common.go), donc ce que le
// moteur dit de lui-meme -- demarrages, erreurs de configuration, envoi suspendu
// ou repris, echecs TLS -- y compris ceux des appliances qui lui envoient leurs
// journaux. Aucun compteur cote demon : les tuiles comptent les lignes chargees.
//
// Une seule grille : bandeau, alerte, tuiles, barre d'outils (champ de
// search_block + raccourcis + Refresh), resultats (filtres, liste). Le repondeur
// ?search= calcule tout sur la meme lecture puis deplace bandeau, alerte, tuiles
// et outils dans leurs emplacements.
// ===========================================================================

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{slg_title}","fas fa-file-lines","{slg_page_explain}",
        "$page?form=yes","syslogd-events","progress-syslod-events",false,"table-loader-syslog-events");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{slg_title}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function form():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $h=array();
    $h[]="<div class='slg-host'><div class='slg ak' id='slg-root'>";
    $h[]="<div class='slg-banner' id='slg-banner'></div>";
    $h[]="<div class='slg-alert-host' id='slg-alert'></div>";
    $h[]="<div class='slg-kpis-host' id='slg-kpis'></div>";
    $h[]="<div class='slg-toolbar'></div>";
    $h[]="<div class='slg-tools' id='slg-tools'></div>";
    $h[]="<div class='slg-help'>{slg_search_help}</div>";
    $h[]="<span hidden id='slg-ph'>{slg_search_ph}</span>";
    $body=$tpl->_ENGINE_parse_body(implode("\n",$h));
    // search_block() traduit deja ses jetons et porte du JS : hors du moteur.
    $search=$tpl->search_block($page);
    $script="<script>(function(){var r=document.getElementById('slg-root');if(!r){return;}"
        ."var i=r.querySelector('.sb-stitch input.form-control');var p=document.getElementById('slg-ph');"
        ."if(i&&p){i.setAttribute('placeholder',p.textContent);i.setAttribute('aria-label',p.textContent);}})();</script>";
    echo slg_style().$body.$search."</div></div>".$script;
    return true;
}

function table():bool{
    $tpl=new template_admin();
    // Filtre a la lecture : $function part dans des OnClick et du JS emis.
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $raw=trim((string)($_GET["search"] ?? ""));
    $_SESSION["SYSLOGSEVNTS"]=strtolower($raw);
    $searching=($raw!=="" && $raw!=="*");
    $tiny=slg_tiny($function);

    $MAIN=$tpl->format_search_protocol($raw,false,true,false,false);
    // format_search_protocol() pose MAX=50 quand la recherche n'en donne pas :
    // la page demandait 500 lignes et n'en recevait que 50. Sans « max N »,
    // « limit N » ou « last N » explicite, le plafond de la page (500) s'applique.
    if(!preg_match('#(^|\s)(max|limit|last)\s*=?\s*[0-9]+#i',$raw)){$MAIN["MAX"]=0;}
    // articarest /system/logfile/search/rsyslogd : lignes les plus recentes d'abord.
    $error="";
    $lines=$tpl->logfile_search("rsyslogd",$MAIN,false,500,$error);
    if($error<>""){
        echo slg_error_state($error,$function).slg_js($function,$tiny);
        return false;
    }
    $m=slg_model($lines);
    $html=array();
    if($m["total"]===0){
        $html[]=$searching?slg_no_match($raw,$function):slg_empty_state($function);
    }else{
        $html[]=slg_filters($m);
        $html[]=slg_list($m);
    }
    $html[]="<div hidden id='slg-banner-src'>".slg_banner()."</div>";
    $html[]="<div hidden id='slg-alert-src'>".slg_alert($m)."</div>";
    $html[]="<div hidden id='slg-kpis-src'>".slg_kpis($m,$searching)."</div>";
    $html[]="<div hidden id='slg-tools-src'>".slg_tools($function)."</div>";
    echo $tpl->_ENGINE_parse_body(implode("\n",$html)).slg_js($function,$tiny);
    return true;
}

// --- Modele ---------------------------------------------------------------

/** Texte venu du journal (donnee externe) : echappe, accolades neutralisees. */
function slg_esc($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES,"UTF-8",true));
}

/** « 2h 3m » / « 12m » / « 3d 4h ». */
function slg_duration(int $s):string{
    if($s<60){return "1m";}
    $d=intdiv($s,86400);$h=intdiv($s%86400,3600);$mn=intdiv($s%3600,60);
    if($d>0){return "{$d}d {$h}h";}
    if($h>0){return "{$h}h {$mn}m";}
    return "{$mn}m";
}

/** « 12m ago » : jeton %s, traduit avant substitution. */
function slg_ago(int $ts):string{
    $tpl=new template_admin();
    $fmt=$tpl->_ENGINE_parse_body("{slg_ago}");
    if(strpos($fmt,"%s")===false){$fmt="%s";}
    return str_replace("%s",slg_duration(max(time()-$ts,0)),$fmt);
}

/**
 * Moment lisible : « 12m ago » sous 7 jours, la date au-dela (« Feb 20 »,
 * « Mar 30, 2025 » hors de l'annee en cours) -- « 274d 15h ago » ne parle a personne.
 */
function slg_when(int $ts):string{
    if($ts<=0){return "&mdash;";}
    if(time()-$ts<7*86400){return slg_ago($ts);}
    return date("Y",$ts)===date("Y")?date("M j",$ts):date("M j, Y",$ts);
}

/** Date syslog sans annee : l'annee courante, ou la precedente si cela tombe dans le futur. */
function slg_ts(string $mon,string $day,string $hms):int{
    $ts=strtotime("$mon $day ".date("Y")." $hms");
    if($ts===false){return 0;}
    if($ts>time()+86400){$ts=strtotime("$mon $day ".(intval(date("Y"))-1)." $hms");}
    return $ts===false?0:$ts;
}

/**
 * Decoupe une ligne. Format traditionnel (RSYSLOG_TraditionalFileFormat) :
 * « Nov 22 10:14:02 host prog[pid]: message », le PID est souvent absent ;
 * RFC 3339 accepte aussi. Une ligne illisible garde son texte brut.
 */
function slg_parse(string $line):array{
    $r=array("ts"=>0,"host"=>"","prog"=>"","pid"=>"","msg"=>$line);
    if(preg_match('#^([A-Z][a-z]{2})\s+(\d{1,2})\s+(\d{2}:\d{2}:\d{2})\s+(\S+)\s+([^\s:\[]+)(?:\[(\d+)\])?:\s?(.*)$#',$line,$re)){
        $r["ts"]=slg_ts($re[1],$re[2],$re[3]);
        $r["host"]=$re[4];$r["prog"]=$re[5];$r["pid"]=$re[6];$r["msg"]=$re[7];
        return $r;
    }
    if(preg_match('#^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})?)\s+(\S+)\s+([^\s:\[]+)(?:\[(\d+)\])?:\s?(.*)$#',$line,$re)){
        $ts=strtotime($re[1]);
        $r["ts"]=$ts===false?0:$ts;
        $r["host"]=$re[2];$r["prog"]=$re[3];$r["pid"]=$re[4];$r["msg"]=$re[5];
    }
    return $r;
}

/**
 * Categorie par mots-cles (reprise des motifs historiques de la page). La plus
 * grave l'emporte : error > warning > info > backup > other. « exact » = sensible
 * a la casse comme l'ancien strpos, « ci » = insensible.
 */
function slg_category(string $msg):string{
    $rules=array(
        "error"=>array("OpenSSL Error","Certificate error at depth","certificate verify failed","Handshake failed with error code",
            "no shared curve between","SSL_ERROR_SSL","SSL_ERROR_SYSCALL","Syntax Error in","will be closed due to error",
            "failed to process incoming connection","retry returned error","error on handshake","invalid or yet-unknown",
            "Could not create","suspended,","could not interpret","error during parsing file","is unknown ","could not load module",
            "Failed to connect to ","checkResult error","opening error file","]: lost "),
        "warning"=>array("error: ","suspended (","suspending ourselves","resumed (","Error in received"),
        "info"=>array("initiated with","queue files exist on","origin software=\"rsyslogd\""),
        "backup"=>array("logsink_backup"),
    );
    foreach($rules as $cat=>$needles){
        foreach($needles as $n){ if(strpos($msg,$n)!==false){return $cat;} }
        if($cat==="warning" && (stripos($msg,"warning during parsing")!==false || stripos($msg,"warning:")!==false)){return "warning";}
    }
    return "other";
}

/** Resume en langage courant pour les messages connus ; "" sinon (le texte brut suffit). */
function slg_summary(string $msg):string{
    $map=array(
        array("origin software=\"rsyslogd\"","] start","slg_s_started"),
        array("origin software=\"rsyslogd\"","exiting on signal","slg_s_stopped"),
        array("origin software=\"rsyslogd\"","HUPed","slg_s_reloaded"),
        array("logsink.Restart","","slg_s_restart_artica"),
        array("logsink.Start","","slg_s_start_artica"),
        array("logsink.Reload","","slg_s_reload_artica"),
        array("RESTART_SYSLOG","log cleaning","slg_s_restart_cleaning"),
        array("suspend","","slg_s_paused"),
        array("resumed","","slg_s_resumed"),
        array("certificate verify failed","","slg_s_tls"),
        array("Certificate error at depth","","slg_s_tls"),
        array("Handshake failed","","slg_s_tls"),
        array("error on handshake","","slg_s_tls"),
        array("Failed to connect to ","","slg_s_connect"),
        array("error during parsing file","","slg_s_config"),
        array("could not load module","","slg_s_module"),
        array("high activity - starting","","slg_s_busy"),
        array("worker thread","terminated","slg_s_calm"),
        array("truncation detected","","slg_s_truncated"),
        array("treated as ':omusrmsg:","","slg_s_old_syntax"),
        array("Address already in use","","slg_s_socket"),
        array("logsink_backup","","slg_s_backup"),
    );
    foreach($map as $x){
        if(strpos($msg,$x[0])===false){continue;}
        if($x[1]!=="" && strpos($msg,$x[1])===false){continue;}
        return "{".$x[2]."}";
    }
    return "";
}

/** Retire le suffixe « [v8.2308.0 try https://www.rsyslog.com/e/2439 ] » et garde le lien d'aide officiel. */
function slg_help(string $msg):array{
    $url="";
    if(preg_match('#\s*\[v?[0-9][^\]]*?(?:try\s+(https://www\.rsyslog\.com/e/[0-9]+))?\s*\]\s*$#',$msg,$re)){
        if(isset($re[1])){$url=$re[1];}
        $msg=rtrim(substr($msg,0,strlen($msg)-strlen($re[0])));
    }
    return array($msg,$url);
}

function slg_model(array $lines):array{
    $m=array("total"=>0,"rows"=>array(),"count"=>array("error"=>0,"warning"=>0,"info"=>0,"backup"=>0,"other"=>0),
        "last"=>array("error"=>0,"warning"=>0),"start"=>0,"newest"=>0,"oldest"=>0,"paused"=>0,"pause_known"=>false);
    $prev=0;
    foreach($lines as $line){
        $line=trim((string)$line);
        if($line===""){continue;}
        $p=slg_parse($line);
        // Le format syslog n'a pas d'annee. Les lignes arrivent de la plus recente
        // a la plus ancienne : une date qui depasse la precedente (au-dela d'un jour
        // de marge, pour les horloges un peu decalees) appartient a l'annee d'avant.
        // Sans cela, une ligne de mars 2025 lue en novembre 2026 serait datee 2026.
        if($p["ts"]>0 && $prev>0 && preg_match('#^[A-Z][a-z]{2}\s#',$line)){
            $guard=0;
            while($p["ts"]>$prev+86400 && $guard<5){
                $y=strtotime("-1 year",$p["ts"]);
                if($y===false){break;}
                $p["ts"]=$y;$guard++;
            }
        }
        if($p["ts"]>0){$prev=$p["ts"];}
        list($msg,$url)=slg_help($p["msg"]);
        $cat=slg_category($p["msg"]);
        $sum=slg_summary($p["msg"]);
        $m["total"]++;
        $m["count"][$cat]++;
        $ts=$p["ts"];
        if($ts>0){
            if($m["newest"]===0 || $ts>$m["newest"]){$m["newest"]=$ts;}
            if($m["oldest"]===0 || $ts<$m["oldest"]){$m["oldest"]=$ts;}
            if(isset($m["last"][$cat]) && $ts>$m["last"][$cat]){$m["last"][$cat]=$ts;}
            if($sum==="{slg_s_started}" && $ts>$m["start"]){$m["start"]=$ts;}
        }
        // Lignes les plus recentes d'abord : la premiere qui parle de suspension
        // ou de reprise donne l'etat actuel de l'envoi.
        if(!$m["pause_known"]){
            if($sum==="{slg_s_paused}"){$m["pause_known"]=true;$m["paused"]=$ts>0?$ts:-1;}
            elseif($sum==="{slg_s_resumed}"){$m["pause_known"]=true;}
        }
        $m["rows"][]=array("p"=>$p,"msg"=>$msg,"url"=>$url,"cat"=>$cat,"sum"=>$sum);
    }
    return $m;
}

// --- TINY -------------------------------------------------------------------

function slg_tiny(string $function):string{
    $tpl=new template_admin();
    $topbuttons=array();
    if($function!==""){$topbuttons[]=array("$function()",ico_refresh,"{refresh}");}
    $TINY=array("TITLE"=>"{slg_title}","ICO"=>"fas fa-file-lines","EXPL"=>"{slg_page_explain}","URL"=>"syslogd-events",
        "BUTTONS"=>$tpl->table_buttons($topbuttons));
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');";
}

// --- Widgets (un widget = une fonction) ------------------------------------

function slg_banner():string{
    $chips=array(array("fas fa-eye","{slg_chip_readonly}"),array("fas fa-arrow-down-wide-short","{slg_chip_newest}"),
        array("fas fa-circle-question","{slg_chip_help}"));
    $c="";
    foreach($chips as $x){$c.="<span class='slg-reas'><i class='{$x[0]}' aria-hidden='true'></i> {$x[1]}</span>";}
    return "<section class='slg-hero'><div class='slg-hero-ico' aria-hidden='true'><i class='fas fa-file-lines'></i></div>"
        ."<div class='slg-hero-main'><h2 class='slg-hero-t'>{slg_hero_title}</h2><p class='slg-hero-p'>{slg_hero_text}</p>"
        ."<div class='slg-reass'>$c</div></div></section>";
}

function slg_hms(int $ts):string{
    if($ts<=0){return "&mdash;";}
    if(date("Y-m-d",$ts)===date("Y-m-d")){return date("H:i:s",$ts);}
    return date("Y",$ts)===date("Y")?date("M j H:i",$ts):date("M j, Y H:i",$ts);
}

function slg_alert(array $m):string{
    if(!$m["pause_known"] || $m["paused"]===0){return "";}
    $tpl=new template_admin();
    $since=$m["paused"]>0?slg_hms($m["paused"]):"&mdash;";
    $txt=str_replace("%s",$since,$tpl->_ENGINE_parse_body("{slg_alert_paused}"));
    return "<div class='slg-alert' role='alert'><span class='slg-alert-t'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> $txt</span>"
        ."<button type='button' class='ak-link' OnClick=\"SlgFilterSet('warning')\">{slg_show_warnings}</button></div>";
}

function slg_kpi(string $label,string $ico,string $value,string $foot,string $tone=""):string{
    $cls=$tone!==""?" slg-kpi-$tone":"";
    $i=$ico!==""?"<i class='$ico' aria-hidden='true'></i>":"";
    return "<div class='slg-kpi$cls'><div class='slg-kpi-l'><span>$label</span>$i</div><div class='slg-kpi-v'>$value</div><div class='slg-kpi-f'>$foot</div></div>";
}

function slg_last_foot(int $ts):string{
    if($ts<=0){return "{slg_none_in_lines}";}
    $tpl=new template_admin();
    $when=slg_esc($tpl->time_to_date($ts,true));
    return "<span title=\"$when\">{slg_last_one} ".slg_when($ts)."</span>";
}

function slg_kpi_errors(array $m):string{
    $n=$m["count"]["error"];
    return slg_kpi("{slg_errors}",$n>0?"fas fa-circle-exclamation":"far fa-circle-check",(string)$n,slg_last_foot($m["last"]["error"]),$n>0?"err":"");
}

function slg_kpi_warnings(array $m):string{
    $n=$m["count"]["warning"];
    return slg_kpi("{slg_warnings}",$n>0?"fas fa-triangle-exclamation":"far fa-circle-check",(string)$n,slg_last_foot($m["last"]["warning"]),$n>0?"warn":"");
}

function slg_kpi_start(array $m):string{
    if($m["start"]<=0){return slg_kpi("{slg_last_start}","fas fa-power-off","&mdash;","{slg_not_in_lines}");}
    $tpl=new template_admin();
    $when=slg_esc($tpl->time_to_date($m["start"],true));
    return slg_kpi("{slg_last_start}","fas fa-power-off","<span title=\"$when\">".slg_when($m["start"])."</span>","{slg_s_started} &middot; ".slg_hms($m["start"]));
}

function slg_kpi_lines(array $m):string{
    $foot=$m["newest"]>0?"{slg_newest}: ".slg_hms($m["newest"])." &middot; {slg_oldest}: ".slg_hms($m["oldest"]):"&mdash;";
    return slg_kpi("{slg_lines_shown}","fas fa-list",number_format($m["total"],0,".",","),$foot);
}

function slg_kpis(array $m,bool $searching):string{
    $tpl=new template_admin();
    $note=str_replace("%s",number_format($m["total"],0,".",","),$tpl->_ENGINE_parse_body($searching?"{slg_based_search}":"{slg_based_recent}"));
    return "<div class='slg-kpis'>".slg_kpi_errors($m).slg_kpi_warnings($m).slg_kpi_start($m).slg_kpi_lines($m)."</div>"
        .($m["total"]>0?"<p class='slg-note'>$note</p>":"");
}

function slg_tools(string $function):string{
    $short=array("today"=>"{slg_q_today}","last hour"=>"{slg_q_last_hour}","yesterday"=>"{slg_q_yesterday}","max 1000"=>"{slg_q_1000}");
    $h="";
    foreach($short as $q=>$lbl){
        $h.="<button type='button' class='slg-short' OnClick=\"SlgShortcut('$q','$function')\">$lbl</button>";
    }
    if($function!==""){
        $h.="<button type='button' class='ak-btn ak-btn-icon' aria-label=\"{refresh}\" title=\"{refresh}\" OnClick=\"$function()\"><i class='fas fa-rotate' aria-hidden='true'></i></button>";
    }
    return $h;
}

function slg_filters(array $m):string{
    $labels=array("all"=>"{slg_f_all}","error"=>"{slg_errors}","warning"=>"{slg_warnings}","info"=>"{slg_info}","backup"=>"{slg_backup}","other"=>"{slg_other}");
    $h="<div class='slg-chips' role='group' aria-label=\"{slg_filters_label}\" id='slg-chips'>";
    foreach($labels as $k=>$l){
        $n=$k==="all"?$m["total"]:$m["count"][$k];
        $on=$k==="all";
        $h.="<button type='button' class='slg-chip".($on?" on":"")."' data-f='$k' aria-pressed='".($on?"true":"false")."' OnClick=\"SlgFilterSet('$k')\">$l <span class='slg-chip-n'>".number_format($n,0,".",",")."</span></button>";
    }
    return $h."</div>";
}

function slg_badge(string $cat):string{
    $b=array("error"=>array("fas fa-circle-exclamation","{slg_error}"),"warning"=>array("fas fa-triangle-exclamation","{slg_warning}"),
        "info"=>array("fas fa-circle-info","{slg_info}"),"backup"=>array("fas fa-box-archive","{slg_backup}"),"other"=>array("fas fa-circle","{slg_other}"));
    return "<span class='slg-badge slg-b-$cat'><i class='{$b[$cat][0]}' aria-hidden='true'></i> {$b[$cat][1]}</span>";
}

function slg_day_label(int $ts):string{
    if($ts<=0){return "{slg_undated}";}
    $d=date("Y-m-d",$ts);
    if($d===date("Y-m-d")){return "{slg_today}";}
    if($d===date("Y-m-d",time()-86400)){return "{slg_yesterday}";}
    return date("Y",$ts)===date("Y")?date("M j",$ts):date("M j, Y",$ts);
}

function slg_source(array $p):string{
    $parts=array();
    if($p["host"]!==""){$parts[]="<i class='fas fa-server' aria-hidden='true'></i> ".slg_esc($p["host"]);}
    if($p["prog"]==="rsyslogd"){$parts[]="{slg_src_engine}";}
    elseif($p["prog"]==="liblogging-stdlog"){$parts[]="{slg_src_artica}";}
    elseif($p["prog"]!==""){$parts[]=slg_esc($p["prog"]);}
    if($p["pid"]!==""){$parts[]="{slg_process} ".slg_esc($p["pid"]);}
    return implode(" &middot; ",$parts);
}

function slg_row(array $r):string{
    $p=$r["p"];
    $time=$p["ts"]>0?date("H:i:s",$p["ts"]):"&mdash;";
    $raw=slg_esc($r["msg"]);
    $long=strlen($r["msg"])>160;
    if($r["sum"]!==""){
        $text="<div class='slg-sum'>{$r["sum"]}</div><div class='slg-raw".($long?" slg-clamp":"")."'>$raw</div>";
    }else{
        $text="<div class='slg-main".($long?" slg-clamp":"")."'>$raw</div>";
    }
    $more=$long?"<button type='button' class='slg-more' data-more=\"{slg_show_more}\" data-less=\"{slg_show_less}\" OnClick=\"SlgMore(this)\">{slg_show_more}</button>":"";
    $src=slg_source($p);
    $help="";
    if($r["url"]!==""){
        $u=slg_esc($r["url"]);
        $help="<a class='ak-link slg-help-a' href=\"$u\" target='_blank' rel='noopener noreferrer'>{slg_open_help} <i class='fas fa-arrow-up-right-from-square' aria-hidden='true'></i></a>";
    }
    return "<div class='slg-row slg-r-{$r["cat"]}' data-cat='{$r["cat"]}'>"
        ."<div class='slg-c-badge'>".slg_badge($r["cat"])."</div>"
        ."<div class='slg-c-time'>$time</div>"
        ."<div class='slg-c-msg'>$text$more".($src!==""?"<div class='slg-src'>$src</div>":"")."</div>"
        ."<div class='slg-c-help'>$help</div></div>";
}

function slg_list(array $m):string{
    $out=array();
    $day=null;
    foreach($m["rows"] as $r){
        $d=$r["p"]["ts"]>0?date("Y-m-d",$r["p"]["ts"]):"?";
        if($d!==$day){
            $day=$d;
            $out[]="<div class='slg-day'>".slg_day_label($r["p"]["ts"])."</div>";
        }
        $out[]=slg_row($r);
    }
    $out[]="<div class='slg-norow' id='slg-none' hidden>{slg_no_in_filter}</div>";
    return "<div class='slg-card slg-list' id='slg-list'>".implode("\n",$out)."</div>";
}

function slg_empty_state(string $function):string{
    $btn=$function!==""?"<button type='button' class='ak-btn ak-btn-lg ak-btn-primary' OnClick=\"$function()\"><i class='fas fa-rotate' aria-hidden='true'></i> {refresh}</button>":"";
    return "<div class='slg-card slg-empty'><i class='fas fa-file-circle-question slg-empty-ico' aria-hidden='true'></i>"
        ."<h3 class='slg-empty-t'>{slg_empty_title}</h3><p class='slg-empty-p'>{slg_empty_text}</p>$btn</div>";
}

function slg_no_match(string $q,string $function):string{
    $clear=$function!==""?" <a class='slg-inline-link' href=\"javascript:blur();\" OnClick=\"SlgShortcut('','$function')\">{slg_clear_search}</a>":"";
    return "<div class='slg-card slg-norow'><i class='fas fa-magnifying-glass' aria-hidden='true'></i> {slg_no_match} &laquo;".slg_esc($q)."&raquo;.$clear</div>";
}

function slg_error_state(string $error,string $function):string{
    $tpl=new template_admin();
    $btn=$function!==""?" <button type='button' class='ak-link' OnClick=\"$function()\">{refresh}</button>":"";
    return $tpl->_ENGINE_parse_body("<div class='slg-err' role='alert'><i class='fas fa-circle-exclamation' aria-hidden='true'></i> ".slg_esc($error)."$btn</div>");
}

// --- JS (hors du moteur : ses accolades seraient prises pour des jetons) ----

function slg_js(string $function,string $tiny):string{
    return "<script>NoSpinner();(function(){"
        ."function mv(a,b){var s=document.getElementById(a),d=document.getElementById(b);if(!d){return;}d.innerHTML='';if(!s){return;}"
        ."while(s.firstChild){d.appendChild(s.firstChild);}s.parentNode.removeChild(s);}"
        ."mv('slg-banner-src','slg-banner');mv('slg-alert-src','slg-alert');mv('slg-kpis-src','slg-kpis');mv('slg-tools-src','slg-tools');"
        ."})();"
        ."window.SlgCat=window.SlgCat||'all';"
        ."window.SlgFilterSet=function(k){window.SlgCat=k;SlgApply();};"
        ."window.SlgApply=function(){var k=window.SlgCat||'all';var box=document.getElementById('slg-chips');"
        ."if(box){var b=box.getElementsByTagName('button');for(var i=0;i<b.length;i++){var on=b[i].getAttribute('data-f')===k;"
        ."b[i].className='slg-chip'+(on?' on':'');b[i].setAttribute('aria-pressed',on?'true':'false');}}"
        ."var l=document.getElementById('slg-list');if(!l){return;}var kids=l.children,shown=0,day=null,dayShown=0;"
        ."function closeDay(){if(day){day.style.display=dayShown?'':'none';}}"
        ."for(var j=0;j<kids.length;j++){var e=kids[j];"
        ."if(e.className==='slg-day'){closeDay();day=e;dayShown=0;continue;}"
        ."var c=e.getAttribute('data-cat');if(c===null){continue;}var ok=(k==='all'||c===k);e.style.display=ok?'':'none';if(ok){shown++;dayShown++;}}"
        ."closeDay();var n=document.getElementById('slg-none');if(n){n.hidden=shown!==0;}};"
        ."window.SlgMore=function(b){var r=b.parentNode;var c=r.querySelector('.slg-clamp,.slg-open');if(!c){return;}"
        ."var open=c.className.indexOf('slg-open')>=0;c.className=c.className.replace(open?'slg-open':'slg-clamp',open?'slg-clamp':'slg-open');"
        ."b.textContent=open?b.getAttribute('data-more')||b.textContent:b.getAttribute('data-less')||b.textContent;};"
        ."window.SlgShortcut=function(q,fn){var r=document.getElementById('slg-root');var i=r?r.querySelector('.sb-stitch input.form-control'):null;"
        ."if(i){i.value=q;}if(fn&&typeof window[fn]==='function'){window[fn]();}};"
        // « Show more » seulement si le texte est vraiment coupe a 2 lignes (la
        // largeur decide, pas le nombre de caracteres).
        ."window.SlgClampCheck=function(){var b=document.querySelectorAll('#slg-list .slg-more');for(var i=0;i<b.length;i++){"
        ."var c=b[i].parentNode.querySelector('.slg-clamp');if(c&&c.scrollHeight<=c.clientHeight+1){b[i].style.display='none';}}};"
        ."SlgApply();SlgClampCheck();"
        .$tiny."</script>";
}

function slg_style():string{
    if(isset($GLOBALS["SLG_STYLE"])){return "";}
    $GLOBALS["SLG_STYLE"]=true;
    $acc="#005447";$soft="#e6eeed";$line="#bfd4d1";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$acc=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_line"])){$line=$a["accent_line"];}
    }
    $css="
.slg-host{container-type:inline-size}
.slg{--slg-accent:%ACC%;--slg-soft:%SOFT%;--slg-line:%LINE%;display:grid;grid-template-columns:minmax(0,1fr) auto;column-gap:16px;margin-top:12px;color:#0f172a}
.slg *{box-sizing:border-box}
.slg-banner{grid-column:1/-1;grid-row:1;margin-bottom:20px}
.slg-alert-host{grid-column:1/-1;grid-row:2}
.slg-kpis-host{grid-column:1/-1;grid-row:3;margin-bottom:16px}
.slg-toolbar{grid-column:1/-1;grid-row:4/6;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.slg-tools{grid-column:2;grid-row:4;align-self:center;display:flex;flex-wrap:wrap;align-items:center;gap:8px;padding:12px 16px 0 0}
.slg-help{grid-column:1/-1;grid-row:5;padding:6px 16px 14px;font-size:13px;color:#64748b}
.slg .sb-stitch,.slg .sb-stitch>.ibox-content{display:contents}
.slg .sb-stitch:before,.slg .sb-stitch:after,.slg .sb-stitch>.ibox-content:before,.slg .sb-stitch>.ibox-content:after{content:none !important;display:none !important}
.slg .sb-stitch .input-group{grid-column:1;grid-row:4;align-self:center;position:relative;display:flex !important;align-items:center;gap:8px;width:auto;max-width:620px;height:auto;margin:12px 0 0 16px;padding:0;border:0;border-radius:0;background:transparent;box-shadow:none}
.slg .sb-stitch .input-group:focus-within{box-shadow:none}
.slg .sb-stitch .sb-ico{position:absolute;left:0;top:0;bottom:0;display:flex;align-items:center;padding:0 0 0 13px;color:#64748b;font-size:14px;pointer-events:none;z-index:3}
.slg .sb-stitch .form-control{flex:1 1 0%;width:auto !important;min-width:0;float:none;height:40px !important;line-height:20px !important;padding:8px 12px 8px 38px !important;font-size:14px !important;color:#0f172a;background:#f8fafc !important;border:1px solid #cbd5e1 !important;border-radius:8px !important;box-shadow:none !important}
.slg .sb-stitch .form-control::placeholder{color:#64748b}
.slg .sb-stitch .form-control:focus{background:#fff !important;border-color:var(--slg-accent) !important;box-shadow:0 0 0 2px var(--slg-line) !important}
.slg .sb-stitch .input-group-btn{display:flex !important;flex:0 0 auto;width:auto}
.slg .sb-stitch .sb-go{height:40px !important;line-height:40px !important;padding:0 16px !important;border-radius:8px !important;background:var(--slg-accent) !important;color:#fff !important;font-size:13px !important;font-weight:600}
.slg .sb-stitch .ibox-content>div[id^='results-']{grid-column:1/-1;grid-row:6;margin-top:16px !important;min-width:0}
.slg-hero{display:flex;gap:20px;align-items:flex-start;background:#fff;border:1px solid #e2e8f0;border-left:4px solid var(--slg-accent);border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.slg-hero-ico{flex:0 0 48px;width:48px;height:48px;border-radius:10px;background:var(--slg-soft);color:var(--slg-accent);display:flex;align-items:center;justify-content:center;font-size:22px}
.slg-hero-main{flex:1 1 auto;min-width:0}
.slg-hero-t{margin:0 0 6px;font-size:22px;line-height:30px;font-weight:600;color:#0f172a}
.slg-hero-p{margin:0 0 14px;font-size:14px;line-height:22px;color:#475569;max-width:820px}
.slg-reass{display:flex;flex-wrap:wrap;gap:8px}
.slg-reas{display:inline-flex;align-items:center;gap:7px;padding:5px 12px;border-radius:999px;background:#f2f6f6;color:#0f172a;font-size:13px;line-height:18px}
.slg-reas .svg-inline--fa,.slg-reas i{color:var(--slg-accent)}
.slg-alert{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px;padding:12px 16px;border:1px solid #fde68a;border-left:4px solid #b45309;border-radius:12px;background:#fffbeb;color:#92400e;font-size:14px}
.slg-alert-t{display:inline-flex;align-items:center;gap:8px}
.slg-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.slg-kpi{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;box-shadow:0 1px 2px rgba(15,23,42,.06);min-width:0}
.slg-kpi-l{display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:600;color:#475569}
.slg-kpi-l .svg-inline--fa,.slg-kpi-l i{color:#94a3b8;font-size:15px}
.slg-kpi-v{font-size:26px;line-height:34px;font-weight:700;margin:6px 0 2px;white-space:nowrap}
.slg-kpi-f{font-size:13px;line-height:19px;color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.slg-kpi-err{border-left:4px solid #b91c1c}
.slg-kpi-err .slg-kpi-l .svg-inline--fa,.slg-kpi-err .slg-kpi-l i{color:#b91c1c}
.slg-kpi-warn{border-left:4px solid #b45309}
.slg-kpi-warn .slg-kpi-l .svg-inline--fa,.slg-kpi-warn .slg-kpi-l i{color:#b45309}
.slg-note{margin:8px 2px 0;font-size:13px;color:#64748b}
.slg-short{height:32px;padding:0 13px;border:1px solid #e2e8f0;border-radius:999px;background:#f8fafc;color:#334155;font-size:13px;cursor:pointer;white-space:nowrap}
.slg-short:hover{border-color:var(--slg-accent);color:var(--slg-accent)}
.slg-short:focus-visible,.slg-chip:focus-visible,.slg-more:focus-visible{outline:2px solid var(--slg-accent);outline-offset:2px}
.slg-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
.slg-chip{display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 14px;border:1px solid #cbd5e1;border-radius:999px;background:#fff;color:#334155;font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap}
.slg-chip:hover{border-color:var(--slg-accent);color:var(--slg-accent)}
.slg-chip.on{background:var(--slg-accent);border-color:var(--slg-accent);color:#fff}
.slg-chip-n{opacity:.75}
.slg-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06);overflow:hidden}
.slg-day{padding:8px 20px;background:#f8fafc;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;font-size:13px;font-weight:600;color:#334155}
.slg-day:first-child{border-top:0}
.slg-row{display:grid;grid-template-columns:110px 74px minmax(0,1fr) auto;gap:12px;align-items:start;padding:12px 20px;border-top:1px solid #eef2f7;border-left:3px solid transparent;transition:background .15s}
.slg-day+.slg-row{border-top:0}
.slg-row:hover{background:#f7faf9}
.slg-r-error{border-left-color:#b91c1c}
.slg-badge{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap}
.slg-b-error{background:#fef2f2;color:#991b1b}
.slg-b-warning{background:#fffbeb;color:#92400e}
.slg-b-info{background:#eff6ff;color:#1e3a8a}
.slg-b-backup{background:#f1f5f9;color:#334155}
.slg-b-other{background:#f8fafc;color:#475569;border:1px solid #e2e8f0}
.slg-b-other .svg-inline--fa{font-size:9px}
.slg-c-time{font-size:13px;font-weight:600;line-height:24px;color:#0f172a;font-variant-numeric:tabular-nums}
.slg-c-msg{min-width:0;font-size:14px;line-height:21px;padding-top:1px}
.slg-sum{font-weight:600;color:#0f172a}
.slg-raw{font-size:13px;line-height:19px;color:#475569;overflow-wrap:anywhere}
.slg-main{color:#0f172a;overflow-wrap:anywhere}
.slg-clamp{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.slg-more{margin-top:2px;padding:0;border:0;background:none;color:var(--slg-accent);font-size:13px;font-weight:600;cursor:pointer}
.slg-src{margin-top:3px;font-size:13px;color:#64748b}
.slg-src .svg-inline--fa{font-size:11px}
.slg-c-help{padding-top:2px}
.slg-help-a{font-size:13px}
.slg-norow{padding:28px 20px;text-align:center;color:#475569;font-size:14px}
.slg-inline-link{color:var(--slg-accent);font-weight:600;text-decoration:underline;cursor:pointer}
.slg-empty{padding:40px 24px;text-align:center}
.slg-empty-ico{font-size:40px;color:var(--slg-accent)}
.slg-empty-t{margin:14px 0 6px;font-size:20px;font-weight:600;color:#0f172a}
.slg-empty-p{margin:0 auto 18px;max-width:560px;font-size:14px;line-height:22px;color:#475569}
.slg-err{display:flex;align-items:center;gap:10px;padding:14px 16px;border:1px solid #fecaca;border-left:4px solid #b91c1c;border-radius:12px;background:#fef2f2;color:#991b1b;font-size:14px}
@container (max-width:1200px){.slg-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.slg-tools{grid-column:1/-1;grid-row:5;padding:10px 16px 0}.slg-help{grid-row:6}.slg .sb-stitch .ibox-content>div[id^='results-']{grid-row:7}.slg-toolbar{grid-row:4/7}}
@media (prefers-reduced-motion:reduce){.slg *{transition:none !important}}
";
    return "<style>".str_replace(array("%ACC%","%SOFT%","%LINE%"),array($acc,$soft,$line),$css)."</style>";
}
