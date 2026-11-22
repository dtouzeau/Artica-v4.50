<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table_start();exit;}
if(isset($_GET["table-main"])){table_main();exit;}
if(isset($_GET["watch-js"])){watchdog_js();exit;}
if(isset($_GET["watch-popup"])){watch_popup();exit;}
if(isset($_POST["DnsDistEnableWatchdog"])){watch_save();exit;}
if(isset($_GET["events-start"])){events_start();exit;}
// Les hotes des onglets History et Events portent ?search= : chacun a sa propre cle.
if(isset($_GET["wdh-host"])){wdh_host();exit;}
// Ancien repondeur de l'onglet History (search_block &hist=yes) : un onglet resté ouvert
// avant une mise a jour l'appelle encore ; il recoit l'hote actuel, pas la page entiere.
if(isset($_GET["hist"])){wdh_host();exit;}
if(isset($_GET["wde-host"])){wde_host();exit;}
if(isset($_GET["wde-banner"])){wde_banner_host();exit;}
if(isset($_GET["history"])){history_start();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $html=$tpl->page_header("{watchdog}",ico_watchdog,"{dns_watchdog_howto}",
        "$page?tabs=yes","dns-watchdog","progress-unbound-restart",false,"table-loader-watchdog");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}


	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["{dnswd_tab_history}"]="$page?history=yes";
    $array["{events}"]="$page?events-start=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function watchdog_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $period=dnswd_period();
    $tpl->js_dialog2("{watchdog}","$page?watch-popup=yes&period=$period");
    return true;
}
function watch_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $period=dnswd_period();
    $m=dnswd_settings();

    $jsafter[]="if(document.getElementById('dnswatch-main-status')){LoadAjaxSilent('dnswatch-main-status','$page?table-main=yes&period=$period');}";
    $jsafter[]="if(typeof WdhReload==='function'){WdhReload();}";
    $jsafter[]="if(typeof WdeAfterSettings==='function'){WdeAfterSettings();}";
    $jsafter[]="dialogInstance2.close()";

    $check=array();
    foreach(dnswd_intervals() as $n){$check[$n]=dnswd_t($tpl,$n==1?"dnswd_minute":"dnswd_minutes",strval($n));}

    // field_numeric() prend $explain en 4e paramètre : l'ancien appel y passait
    // true, ce qui affichait « 1 » sous le champ.
    $form[]=$tpl->field_checkbox("DnsDistEnableWatchdog","{ENABLE_VIRTUALBOX_WATCHDOG}",$m["enabled"],false,"{dnswd_watchdog_help}");
    $form[]=$tpl->field_text("DnsDistWatchdogHosts","{dnswd_test_websites}",$m["hosts_raw"],false,"{dnswd_hosts_help}");
    $form[]=$tpl->field_numeric("DnsDistWatchdogMaxTimeOut","{dnswd_wait_answer_s}",$m["timeout"],"{dnswd_timeout_help}");
    $form[]=$tpl->field_interfaces("DnsDistWatchdogOutInterface","{dnswd_send_through}",$m["iface"],"{dnswd_iface_help}");
    $form[]=$tpl->field_array_hash($check,"DnsProxyCacheWatchdogInt","{dnswd_check_every}",$m["interval"],false,"{dnswd_interval_help}");
    echo $tpl->form_outside(null,$form,"{dnswd_popup_intro}","{apply}",@implode(";",$jsafter),"AsDnsAdministrator");
    return true;
}
// Enregistre les CINQ réglages du watchdog, et eux seuls. L'ancien SAVE_POSTs()
// écrivait toute clé reçue en POST comme un réglage, et rien ne vérifiait le
// droit côté serveur.
function watch_save():bool{
    $tpl=new template_admin();
    if(!$tpl->IsSecurity("AsDnsAdministrator")){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $tpl->CLEAN_POST();
    $enable=intval($_POST["DnsDistEnableWatchdog"])==1;

    $hosts=array();
    foreach(explode(",",strtolower((string)$_POST["DnsDistWatchdogHosts"])) as $h){
        $h=trim($h);
        if($h==""){continue;}
        if(!preg_match('/^[a-z0-9*]([a-z0-9*\-]*[a-z0-9*])?(\.[a-z0-9*]([a-z0-9*\-]*[a-z0-9*])?)+$/',$h)){
            echo $tpl->post_error(dnswd_t($tpl,"dnswd_invalid_host",$h));return false;
        }
        $hosts[]=$h;
    }
    $timeout=intval($_POST["DnsDistWatchdogMaxTimeOut"]);
    if($timeout<1 || $timeout>30){echo $tpl->post_error("{dnswd_invalid_timeout}");return false;}
    $interval=intval($_POST["DnsProxyCacheWatchdogInt"]);
    if(!in_array($interval,dnswd_intervals())){$interval=1;}
    $iface=preg_replace('/[^A-Za-z0-9._:\-]/','',(string)$_POST["DnsDistWatchdogOutInterface"]);

    $sock=$GLOBALS["CLASS_SOCKETS"];
    $sock->SET_INFO("DnsDistDisableWatchdog",$enable?0:1);
    $sock->SET_INFO("DnsDistWatchdogHosts",@implode(",",$hosts));
    $sock->SET_INFO("DnsDistWatchdogMaxTimeOut",$timeout);
    $sock->SET_INFO("DnsProxyCacheWatchdogInt",$interval);
    $sock->SET_INFO("DnsDistWatchdogOutInterface",$iface);
    admin_tracks(($enable?"Enable":"Disable")." DNS watchdog: hosts=".@implode(",",$hosts)." timeout={$timeout}s every {$interval}min interface=$iface");
    $sock->REST_API("/reload");
    return true;
}

function table_start():bool{
    $page=CurrentPageName();
    echo "<div id='dnswatch-main-status'></div>
    <script>LoadAjaxSilent('dnswatch-main-status','$page?table-main=yes');</script>";
    return true;
}

function table_main(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $period=dnswd_period();
    $m=dnswd_model($period);

    $TINY_ARRAY["TITLE"]="{watchdog}";
    $TINY_ARRAY["ICO"]=ico_watchdog;
    $TINY_ARRAY["EXPL"]="{dns_watchdog_howto}";
    $TINY_ARRAY["BUTTONS"]=dnswd_tiny_filters($period);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html=array();
    $html[]="<div class='dnswd'>";
    $html[]=dnswd_banner($tpl,$m);
    $html[]=dnswd_tiles($tpl,$m);
    $html[]="<div class='dnswd-main'>";
    // Le <script> du graphique reste HORS de _ENGINE_parse_body() : son
    // #{(.+?)}# prendrait chaque objet JS ({display:false}) pour un jeton.
    list($chartHtml,$chartJs)=dnswd_chart($tpl,$m);
    $html[]=$chartHtml;
    $html[]=dnswd_settings_card($tpl,$m);
    $html[]="</div></div>";

    $js="<script>
function DnsWdPeriod(p){LoadAjaxSilent('dnswatch-main-status','$page?table-main=yes&period='+encodeURIComponent(p));}
function DnsWdParams(){Loadjs('$page?watch-js=yes&period=$period');}
function DnsWdHistory(){var h=document.getElementById('dnswatch-main-status');var c=h?h.closest('.tabs-container'):null;
 var a=c?c.querySelectorAll('ul.nav-tabs>li>a'):[];if(a.length>1){a[1].click();}}
function DnsWdMore(b){var x=document.getElementById('dnswd-more');if(!x){return;}x.hidden=false;x.style.display='contents';b.setAttribute('aria-expanded','true');b.style.display='none';}
$jstiny
</script>";
    echo dnswd_style().siem_tiny_filters_style().$tpl->_ENGINE_parse_body(@implode("",$html)).$chartJs.$js;
}

/* ===========================================================================
 * Onglet Events (maquettes Stitch « DNS watchdog events », 2026-11-20 et 2026-11-21).
 *
 * Source : /var/log/internet-watchdog.log, lu par GET /dnswatchdog/accesses/{max}/{search}
 * (articarest RestGenericSearch -> grep.GrepTail : rg insensible a la casse,
 * lignes les plus recentes d'abord, motif de moins de 3 caracteres = dernieres lignes).
 * Format rsyslog traditionnel : « Nov 20 10:42:07 host internet-watchdog[PID]: [LEVEL]: <appelant> <message> ».
 * Les messages viennent de LogSyslog() dans articarest/dnswatchdog/dnswatchdog.go :
 * wde_classify() en reconnait les familles et les redit en mots courants. Une ligne
 * non reconnue reste affichee telle quelle (l'ancien tableau la jetait).
 * Aucun compteur cote demon : les comptes portent sur les lignes affichees.
 *
 * Structure : events_start() pose l'hote du bandeau, la barre de filtres et l'hote
 * #wde-host ; ?wde-host=yes ne recharge QUE le journal, le champ de recherche garde
 * donc le focus. Les segments « Problems only » / « Repairs » / « Everything »
 * filtrent cote client ; les periodes (This hour, Today, Yesterday) deviennent des
 * mots-cles du motif rg, donc cherchent dans tout le journal courant. Seconde
 * maquette Stitch (2026-11-21) : tours de verification replies (wde_group),
 * colonne Level retiree (le niveau est porte par l'icone et son mot).
 * ========================================================================= */

function wde_sizes():array{return array(50,200,500);}

// Liste blanche : search part dans l'URL REST (motif rg), max dans l'URL et le JS.
function wde_params():array{
    $search=trim((string)(isset($_GET["search"])?$_GET["search"]:""));
    if($search=="*"){$search="";}
    if(function_exists("mb_substr")){$search=mb_substr($search,0,120);}else{$search=substr($search,0,120);}
    $max=intval(isset($_GET["max"])?$_GET["max"]:50);
    if(!in_array($max,wde_sizes())){$max=50;}
    $period=isset($_GET["period"])?(string)$_GET["period"]:"any";
    if(!in_array($period,array("any","hour","today","yesterday"))){$period="any";}
    return array("search"=>$search,"max"=>$max,"period"=>$period);
}

// Recherche tapee -> motif rg. Mots-cles : today, yesterday, this hour, last hour
// (la date syslog « Nov 20 10: » precede le texte). Le reste est litteral, « * » joker.
function wde_pattern(string $search,int $now):array{
    $s=strtolower($search);
    $date="";
    $day=0;
    if(preg_match('/\byesterday\b/',$s)){$day=$now-86400;$s=preg_replace('/\byesterday\b/','',$s);}
    if(preg_match('/\btoday\b/',$s)){$day=$now;$s=preg_replace('/\btoday\b/','',$s);}
    $hour="";
    if(preg_match('/\bthis hour\b/',$s)){$day=$now;$hour=date("H",$now);$s=preg_replace('/\bthis hour\b/','',$s);}
    if(preg_match('/\blast hour\b/',$s)){$day=$now-3600;$hour=date("H",$now-3600);$s=preg_replace('/\blast hour\b/','',$s);}
    if($day>0){
        $date="^".date("M",$day)."\\s+".intval(date("j",$day))."\\s";
        if($hour!==""){$date.=$hour.":";}
    }
    $term=trim(preg_replace('/\s+/',' ',str_replace("/"," ",$s)));
    $q=$term==""?"":str_replace("\\*",".*?",preg_quote($term,"/"));
    $pattern=$date;
    if($q!==""){$pattern=$date==""?$q:"$date.*?$q";}
    $short=($date=="" && $term!=="" && strlen($term)<3);
    if(strlen($pattern)<3){$pattern="NONE";}
    return array("pattern"=>$pattern,"short"=>$short);
}

// Une ligne brute -> horodatage, PID, niveau, message sans le nom d'appelant Go.
function wde_parse(string $line,int $now):array{
    $r=array("ts"=>0,"pid"=>"","level"=>"","msg"=>"","raw"=>$line);
    $rest=$line;
    if(preg_match('/^([A-Z][a-z]{2})\s+(\d{1,2})\s+(\d{2}:\d{2}:\d{2})\s+\S+\s+[^\s\[:]+(?:\[(\d+)\])?:\s*(.*)$/',$line,$re)){
        $y=intval(date("Y",$now));
        $ts=strtotime("{$re[1]} {$re[2]} $y {$re[3]}");
        // syslog ne porte pas l'annee : une date « future » est celle de l'an dernier.
        if($ts!==false && $ts>$now+86400){$ts=strtotime("{$re[1]} {$re[2]} ".($y-1)." {$re[3]}");}
        $r["ts"]=$ts===false?0:$ts;
        $r["pid"]=$re[4];
        $rest=$re[5];
    }elseif(preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})\S*\s+\S+\s+[^\s\[:]+(?:\[(\d+)\])?:\s*(.*)$/',$line,$re)){
        $ts=strtotime($re[1]);
        $r["ts"]=$ts===false?0:$ts;
        $r["pid"]=$re[2];
        $rest=$re[3];
    }
    if(preg_match('/^\[(INFO|WARNING|ERROR)\]:?\s*(.*)$/i',$rest,$re)){
        $r["level"]=strtoupper($re[1]);
        $rest=$re[2];
    }
    $rest=preg_replace('/^[\w.\-]+\.go\[[^\]]*\]\s*/','',$rest);
    $r["msg"]=trim($rest);
    return $r;
}

// Phrase d'un token dont chaque %s recoit une valeur ; une valeur « site » est
// rendue en police fixe. Tout est echappe (HTML et accolades).
function wde_sentence($tpl,string $token,array $vals):string{
    $parts=explode("%s",dnswd_t($tpl,$token,"%s"));
    $out="";
    foreach($parts as $i=>$p){
        $out.=wdh_e($p);
        if($i<count($parts)-1){
            $v=isset($vals[$i])?$vals[$i]:array("","");
            $out.=$v[1]=="site"?"<span class='wde-site'>".wdh_e($v[0])."</span>":"<b>".wdh_e($v[0])."</b>";
        }
    }
    return $out;
}

// Le serveur d'un message -> valeur technique comprise par wdh_server().
function wde_server_raw(string $s):string{
    $s=trim($s," []()");
    if(stripos($s,"cloudflare")!==false){return "127.0.0.45";}
    return $s;
}

// Une ligne analysee -> famille, icone, phrase, serveur, puces.
function wde_classify($tpl,array $l):array{
    $msg=$l["msg"];
    $c=array("kind"=>"info","icon"=>"fa-circle-info","html"=>"","server"=>"","chips"=>array(),"sub"=>"","repair"=>false);
    $restart=stripos($msg,"[RESTARTING]")!==false;
    $srvname=function($raw) use ($tpl){$s=wdh_server($tpl,$raw);return $s["label"];};

    if(preg_match('/^DOH QUERY (\S+) FROM \(([^)]*)\)\s*(\S*)/i',$msg,$re)){
        $c["kind"]="check";$c["icon"]="fa-lock";$c["server"]=wde_server_raw($re[3])."/".$re[2];
        $c["html"]=wde_sentence($tpl,"wde_m_check",array(array($srvname($c["server"]),"srv"),array($re[1],"site")));
        return $c;
    }
    if(preg_match('/^QUERY (\S+) FROM 127\.0\.0\.1 \(attempt (\d+)\/(\d+)\)/i',$msg,$re)){
        $c["kind"]="check";$c["icon"]="fa-house-signal";$c["server"]="127.0.0.1";
        $c["html"]=wde_sentence($tpl,"wde_m_local",array(array($re[1],"site"),array($re[2],"n"),array($re[3],"n")));
        return $c;
    }
    if(preg_match('/^QUERY (\S+) FROM \[?([^\]\s]+)\]?/i',$msg,$re)){
        $c["kind"]="check";$c["icon"]="fa-circle-check";$c["server"]=wde_server_raw($re[2]);
        $c["html"]=wde_sentence($tpl,"wde_m_check",array(array($srvname($c["server"]),"srv"),array($re[1],"site")));
        return $c;
    }
    if(preg_match('/Restarting service DNS Firewall web api error/i',$msg)){
        $c["kind"]="problem";$c["icon"]="fa-circle-xmark";$c["server"]="127.0.0.1";
        $c["html"]=wdh_e(wdh_t($tpl,"wde_m_restart_failed"));
        return $c;
    }
    if(preg_match('/Restarting DNS Firewall service/i',$msg)){
        $c["kind"]="repair";$c["icon"]="fa-rotate";$c["server"]="127.0.0.1";$c["repair"]=true;
        $c["html"]=wdh_e(wdh_t($tpl,"wde_m_restart"));
        $c["chips"][]=array("amber","fa-wand-magic-sparkles",wdh_t($tpl,"wde_chip_auto"));
        return $c;
    }
    if(preg_match('/^127\.0\.0\.1 refused (\S+): resolver alive/i',$msg,$re)){
        $c["kind"]="warn";$c["icon"]="fa-triangle-exclamation";$c["server"]="127.0.0.1";
        $c["html"]=wdh_e(wdh_t($tpl,"wde_m_loopback"));
        $c["sub"]=wdh_e(wdh_t($tpl,"wde_m_loopback_help"));
        return $c;
    }
    $site="";
    if(preg_match('/Response Error: Timeout:(\d+)s from (\S+) (\S+)/i',$msg,$re)){
        $c["server"]=wde_server_raw($re[2]);$site=$re[3];
        $c["chips"][]=array("red","fa-hourglass-end",wdh_t($tpl,"wde_r_timeout_s",array($re[1])));
    }elseif(preg_match('/QueryDNS\(([^)]*)\) DNS Response\(\d\) Error: from (\S+) (\S+)/i',$msg,$re)
        || preg_match('/Response(?:\(\d\))? Error: from (\S+) (\S+)/i',$msg,$re2)){
        if(isset($re2) && count($re2)>0){$re=array("",  "",$re2[1],$re2[2]);}
        $c["kind"]="problem";$c["icon"]="fa-circle-xmark";$c["server"]=wde_server_raw($re[2]);
        if($re[1]!==""){
            $c["html"]=wde_sentence($tpl,"wde_m_refusal_for",array(array($srvname($c["server"]),"srv"),array($re[1],"site")));
        }else{
            $c["html"]=wde_sentence($tpl,"wde_m_refusal",array(array($srvname($c["server"]),"srv")));
        }
        $c["chips"][]=array("code","",strtoupper($re[3]));
        return $c;
    }elseif(preg_match('/^Query (\S+): A (\S+) Error/i',$msg,$re)){
        $c["server"]=wde_server_raw($re[1]);$site=$re[2];
    }elseif(preg_match('/Unable to resolv (\S+) using (?:\[([^\]]*)\]|(Cloudflare) local DNS client|(\S+))/i',$msg,$re)){
        $site=$re[1];
        $raw=isset($re[2]) && $re[2]!==""?$re[2]:(isset($re[3]) && $re[3]!==""?"Cloudflare":(isset($re[4])?$re[4]:""));
        $c["server"]=wde_server_raw($raw);
        if(preg_match('/DoH DNS system (\S+)/i',$msg,$rd)){$c["server"].="/".$rd[1];}
    }
    if($site!==""){
        $c["kind"]="problem";$c["icon"]="fa-circle-xmark";
        $c["html"]=wde_sentence($tpl,"wde_m_failed",array(array($srvname($c["server"]),"srv"),array($site,"site")));
        if(count($c["chips"])==0){
            $why=wdh_reason($tpl,$msg);
            $c["chips"][]=array("red",$why[0],$why[1]);
        }
        if($restart){$c["repair"]=true;$c["chips"][]=array("amber","fa-rotate",wdh_t($tpl,"wde_chip_restart"));}
        return $c;
    }
    // Ligne non reconnue : le message tel quel, le niveau decide de la couleur.
    if($l["level"]=="ERROR"){$c["kind"]="problem";$c["icon"]="fa-circle-xmark";}
    if($l["level"]=="WARNING"){$c["kind"]="warn";$c["icon"]="fa-triangle-exclamation";}
    if($restart){$c["repair"]=true;}
    $c["html"]="<span class='wde-rawmsg'>".wdh_e($msg==""?$l["raw"]:$msg)."</span>";
    return $c;
}

// Le modele : lignes analysees et classees, comptes, plage horaire, puis
// regroupement des tours de verification (wde_group).
function wde_model(array $p):array{
    $tpl=new template_admin();
    $now=time();
    $m=array("error"=>"","rows"=>array(),"items"=>array(),"max"=>$p["max"],"search"=>$p["search"],"period"=>$p["period"],
        "count"=>0,"problems"=>0,"repairs"=>0,"short"=>false,"missing"=>false,"tmin"=>0,"tmax"=>0);
    $pat=wde_pattern(trim(wde_period_words($p["period"])." ".$p["search"]),$now);
    $m["short"]=$pat["short"];
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/dnswatchdog/accesses/{$p["max"]}/".rawurlencode($pat["pattern"]));
    $json=json_decode((string)$data);
    if(json_last_error()>JSON_ERROR_NONE || !is_object($json)){
        $m["error"]="articarest: ".json_last_error_msg();
        return $m;
    }
    if(!isset($json->Status) || !$json->Status){
        $m["error"]=isset($json->Error)?(string)$json->Error:"articarest: unknown error";
        return $m;
    }
    // RestGenericSearch garde Status=true quand le fichier n'existe pas encore.
    if(isset($json->Error) && strlen((string)$json->Error)>0){$m["missing"]=true;}
    $lines=(isset($json->Results) && is_array($json->Results))?$json->Results:array();
    foreach($lines as $line){
        $line=trim((string)$line);
        if($line==""){continue;}
        $l=wde_parse($line,$now);
        $c=wde_classify($tpl,$l);
        if($c["kind"]=="problem" || $c["kind"]=="warn"){$m["problems"]++;}
        if($c["repair"]){$m["repairs"]++;}
        if($l["ts"]>0){
            if($m["tmin"]==0 || $l["ts"]<$m["tmin"]){$m["tmin"]=$l["ts"];}
            if($l["ts"]>$m["tmax"]){$m["tmax"]=$l["ts"];}
        }
        $m["rows"][]=array("l"=>$l,"c"=>$c);
    }
    $m["count"]=count($m["rows"]);
    $m["items"]=wde_group($tpl,$m["rows"]);
    return $m;
}

// Le segment de periode -> mots-cles compris par wde_pattern().
function wde_period_words(string $period):string{
    $w=array("hour"=>"this hour","today"=>"today","yesterday"=>"yesterday");
    return isset($w[$period])?$w[$period]:"";
}

// Ecart maximal entre deux lignes d'un meme tour de verification : un tour
// interroge chaque serveur a la suite, les 3 essais locaux tombent a 0,7 s.
function wde_round_gap():int{return 30;}

/**
 * Lignes (plus recentes d'abord) -> elements affiches : une ligne seule, ou un
 * tour qui replie les lignes « Checked that… » ecrites a moins de 30 s d'ecart.
 * Les problemes, avertissements et reparations restent des lignes a part (le
 * filtre « Problems only » doit les voir) ; le tour compte ceux qui tombent
 * dans sa fenetre [premiere question, derniere question + 30 s].
 */
function wde_group($tpl,array $rows):array{
    $gap=wde_round_gap();
    $items=array();
    $cur=-1;
    foreach($rows as $r){
        $ts=$r["l"]["ts"];
        if($r["c"]["kind"]=="check" && $ts>0){
            if($cur>=0 && abs($items[$cur]["tmin"]-$ts)<=$gap && date("Y-m-d",$ts)==date("Y-m-d",$items[$cur]["tmax"])){
                $items[$cur]["rows"][]=$r;
                if($ts<$items[$cur]["tmin"]){$items[$cur]["tmin"]=$ts;}
                continue;
            }
            $items[]=array("type"=>"round","rows"=>array($r),"tmin"=>$ts,"tmax"=>$ts,"ts"=>$ts);
            $cur=count($items)-1;
            continue;
        }
        if($cur>=0 && $ts>0 && abs($items[$cur]["tmin"]-$ts)>$gap){$cur=-1;}
        $items[]=array("type"=>"row","row"=>$r,"ts"=>$ts);
    }
    $out=array();
    foreach($items as $it){
        if($it["type"]=="round" && count($it["rows"])<2){
            $out[]=array("type"=>"row","row"=>$it["rows"][0],"ts"=>$it["ts"]);
            continue;
        }
        if($it["type"]=="round"){
            $names=array();
            foreach($it["rows"] as $r){
                $s=wdh_server($tpl,$r["c"]["server"]);
                $names[$s["label"]]=$r["c"]["server"];
            }
            $it["servers"]=$names;
            $it["problems"]=0;
            foreach($rows as $r){
                $k=$r["c"]["kind"];$ts=$r["l"]["ts"];
                if(($k=="problem" || $k=="warn") && $ts>=$it["tmin"] && $ts<=$it["tmax"]+$gap){$it["problems"]++;}
            }
        }
        $out[]=$it;
    }
    return $out;
}

// « Today » / « Yesterday » / « November 18 ».
function wde_day_label(int $ts,int $now):string{
    if($ts<=0){return "{wde_day_unknown}";}
    $d=date("Y-m-d",$ts);
    if($d==date("Y-m-d",$now)){return "{wde_today}";}
    if($d==date("Y-m-d",$now-86400)){return "{wde_yesterday}";}
    return "{".date("F",$ts)."} ".date("j",$ts);
}

// Separateur : « Today — November 20 ».
function wde_day_sep_label(int $ts,int $now):string{
    if($ts<=0){return "{wde_day_unknown}";}
    $d=date("Y-m-d",$ts);
    $date="{".date("F",$ts)."} ".date("j",$ts);
    if($d==date("Y-m-d",$now)){return "{wde_today} &mdash; $date";}
    if($d==date("Y-m-d",$now-86400)){return "{wde_yesterday} &mdash; $date";}
    return $date;
}

// 1. Bandeau d'accroche : puces lues dans la configuration reelle.
function wde_banner($tpl,array $m):string{
    $off=$m["enabled"]==0;
    $h=array();
    $h[]="<section class='wdh-card wdh-banner'>";
    $h[]="<div class='wdh-bico' aria-hidden='true'><i class='fas fa-list-check'></i></div>";
    $h[]="<div class='wdh-btxt'><h2>{wde_title}</h2><p>{wde_text2}</p>";
    $h[]="<div class='wdh-chips'>";
    if(!$off){
        $every=dnswd_h($tpl,$m["interval"]==1?"dnswd_chip_every1":"dnswd_chip_every",strval($m["interval"]));
        $h[]="<span class='wdh-chip'><i class='fas fa-clock' aria-hidden='true'></i>$every</span>";
    }
    if(!$off && !$m["offline"] && !$m["cluster"]){
        $h[]="<span class='wdh-chip'><i class='fas fa-rotate' aria-hidden='true'></i>{wdh_chip_restart}</span>";
    }
    $h[]="<span class='wdh-chip'><i class='fas fa-arrow-down-wide-short' aria-hidden='true'></i>{wde_chip_newest}</span>";
    $h[]="</div>";
    if($off){
        $h[]="<div class='wdh-notice warn'><i class='fas fa-power-off' aria-hidden='true'></i><span>{wde_off_note}</span></div>";
    }
    $h[]=wdh_notices($tpl,$m);
    $h[]="</div>";
    $h[]="<div class='wdh-bact'>";
    if($off){
        $h[]="<button type='button' class='wdh-btn primary' OnClick=\"WdeSettings();\"><i class='fas fa-power-off' aria-hidden='true'></i>{dnswd_turn_on}</button>";
    }else{
        $h[]="<button type='button' class='wdh-btn' OnClick=\"WdeSettings();\"><i class='fas fa-sliders' aria-hidden='true'></i>{wdh_settings}</button>";
    }
    $h[]="<button type='button' class='wdh-btn icon' aria-label=\"{wdh_refresh}\" title=\"{wdh_refresh}\" OnClick=\"WdeReload();\"><i class='fas fa-rotate' aria-hidden='true'></i></button>";
    $h[]="</div></section>";
    return @implode("",$h);
}

// 2a. Segments (filtre cote navigateur sur les lignes chargees).
function wde_segments():string{
    $seg=array(
        "problems"=>array("fa-circle-exclamation","{wde_f_problems}"),
        "repairs"=>array("fa-rotate","{wde_f_repairs}"),
        "all"=>array("fa-bars","{wde_f_all}"),
    );
    $h=array();
    $h[]="<div class='wdh-seg' role='radiogroup' aria-label=\"{wde_filter_label}\">";
    foreach($seg as $k=>$s){
        $on=$k=="problems";
        $h[]="<button type='button' role='radio' aria-checked='".($on?"true":"false")."' class='wdh-sbtn wde-sbtn".($on?" on":"")."' data-f='$k' OnClick=\"WdeFilter('$k');\">"
            ."<i class='fas {$s[0]}' aria-hidden='true'></i>{$s[1]} <span class='wdh-scount' id='wde-count-$k'>&hellip;</span></button>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

// 2b. Periodes (recherche cote serveur dans le journal courant).
function wde_periods(array $p):string{
    $per=array("any"=>"{wde_p_any}","hour"=>"{wde_p_hour}","today"=>"{wde_today}","yesterday"=>"{wde_yesterday}");
    $h=array();
    $h[]="<div class='wde-per' role='radiogroup' aria-label=\"{wde_period}\"><span class='lbl' aria-hidden='true'>{wde_period}</span>";
    foreach($per as $k=>$label){
        $on=$k==$p["period"];
        $h[]="<button type='button' role='radio' aria-checked='".($on?"true":"false")."' class='wde-pbtn".($on?" on":"")."' data-p='$k' OnClick=\"WdePeriod('$k');\">$label</button>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

// 2. Barre de filtres : segments, periodes, recherche, taille, aides.
function wde_filters($tpl,array $p):string{
    $h=array();
    $h[]="<section class='wdh-card wde-filters'>";
    $h[]="<div class='wde-frow'>".wde_segments()."<span class='wde-vsep' aria-hidden='true'></span>".wde_periods($p)."</div>";
    $val=wdh_e($p["search"]);
    $h[]="<div class='wde-frow'>";
    $h[]="<div class='wdh-search wde-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>";
    $h[]="<input type='search' id='wde-q' value=\"$val\" placeholder=\"{wde_search_ph}\" aria-label=\"{wde_search_ph}\" aria-describedby='wde-qhelp' autocomplete='off'>";
    $h[]="<button type='button' class='wdh-clear' aria-label=\"{wdh_clear}\" title=\"{wdh_clear}\" OnClick=\"WdeClear();\"><i class='fas fa-xmark' aria-hidden='true'></i></button></div>";
    $h[]="<label class='wde-show' for='wde-max'>{wde_show}<select id='wde-max' class='wde-sel' OnChange=\"WdeSetMax(this.value);\">";
    foreach(wde_sizes() as $n){
        $sel=$n==$p["max"]?" selected":"";
        $h[]="<option value='$n'$sel>".dnswd_h($tpl,"wde_n_lines",strval($n))."</option>";
    }
    $h[]="</select></label>";
    $h[]="</div>";
    $ex="<button type='button' class='wde-exbtn' OnClick=\"WdeExample('8.8.8.8');\">8.8.8.8</button>";
    $qhelp=str_replace("%s",$ex,dnswd_h($tpl,"wde_q_help","%s"));
    $h[]="<div class='wde-helps'><span>{wde_period_help}</span><span id='wde-qhelp'>$qhelp</span></div>";
    $h[]="</section>";
    return @implode("",$h);
}

// 3. Ligne de portee : combien de lignes, de quand a quand, et sur quoi portent les comptes.
function wde_scope($tpl,array $m):string{
    $now=time();
    $n=number_format($m["count"]);
    if($m["tmin"]>0 && $m["count"]>1){
        $from=date("H:i",$m["tmin"]);$to=date("H:i",$m["tmax"]);
        if(date("Y-m-d",$m["tmin"])==date("Y-m-d",$m["tmax"])){
            $txt=wde_sentence($tpl,"wde_scope_day",array(array($n,"n"),array(wde_day_label($m["tmax"],$now),"n"),array($from,"n"),array($to,"n")));
        }else{
            $txt=wde_sentence($tpl,"wde_scope",array(array($n,"n"),array(wde_day_label($m["tmin"],$now)." $from","n"),array(wde_day_label($m["tmax"],$now)." $to","n")));
        }
        // Les jours sont des {tokens} que le moteur traduit : on les rend a la main.
        $txt=str_replace(array("&#123;","&#125;"),array("{","}"),$txt);
    }else{
        $txt="<b>".($m["count"]==1?wdh_h($tpl,"wde_counter1"):wdh_h($tpl,"wde_counter",array($n)))."</b>";
    }
    $txt.=" &middot; <span class='p'>".wdh_h($tpl,$m["problems"]==1?"wde_c_problem1":"wde_c_problems",array(number_format($m["problems"])))."</span>";
    $txt.=" &middot; <span class='r'>".wdh_h($tpl,$m["repairs"]==1?"wde_c_repair1":"wde_c_repairs",array(number_format($m["repairs"])))."</span>";
    return "<div class='wde-scope'><div class='l' aria-live='polite'>$txt</div>"
        ."<div class='n'><i class='fas fa-clock-rotate-left' aria-hidden='true'></i>{wde_scope_note}</div></div>";
}

// Le mot du niveau, pour l'info-bulle et les lecteurs d'ecran (jamais la seule couleur).
function wde_kind_word(string $kind):string{
    $w=array("check"=>"{wde_k_check}","problem"=>"{wde_k_problem}","warn"=>"{wde_k_warn}","repair"=>"{wde_k_repair}","round"=>"{wde_k_check}");
    return isset($w[$kind])?$w[$kind]:"{wde_k_info}";
}

// Icone ronde de 28 px, avec son mot.
function wde_kind_icon(string $kind,string $icon):string{
    $w=wde_kind_word($kind);
    return "<span class='wde-ki' role='img' title=\"$w\" aria-label=\"$w\"><i class='fas $icon' aria-hidden='true'></i></span>";
}

// Colonne Server : le nom courant seulement (l'adresse est dans le detail).
function wde_server_cell($tpl,string $raw):string{
    if($raw===""){return "<div class='wde-srv'><span class='wdh-none'>&mdash;</span></div>";}
    $s=wdh_server($tpl,$raw);
    return "<div class='wde-srv'><span class='si'><i class='fas {$s["icon"]}' aria-hidden='true'></i></span><span class='nm'>".wdh_e($s["label"])."</span></div>";
}

function wde_chips(array $chips):string{
    $h="";
    foreach($chips as $ch){
        $ico=$ch[1]!==""?"<i class='fas {$ch[1]}' aria-hidden='true'></i>":"";
        $h.="<span class='wde-chip {$ch[0]}'>$ico".wdh_e($ch[2])."</span>";
    }
    return $h;
}

function wde_when($tpl,int $ts):string{
    if($ts<=0){return "<div class='wde-when'><i class='far fa-clock' aria-hidden='true'></i><span>&mdash;</span></div>";}
    return "<div class='wde-when'><i class='far fa-clock' aria-hidden='true'></i><span title=\"".wdh_date($tpl,$ts)."\">".date("H:i:s",$ts)."</span></div>";
}

// Detail d'une ligne : adresse, processus, ligne brute, lien vers l'historique.
function wde_details($tpl,array $r,int $i):string{
    $l=$r["l"];$c=$r["c"];
    $facts=array();
    if($c["server"]!==""){
        $s=wdh_server($tpl,$c["server"]);
        $facts[]="<span><b>{wde_d_addr}</b> <span class='wde-mono'>".wdh_e(str_replace("/"," · ",$s["tech"]))."</span></span>";
    }
    if($l["pid"]!==""){$facts[]="<span>".wdh_h($tpl,"wde_pid",array($l["pid"]))."</span>";}
    if($l["level"]!==""){$facts[]="<span><b>{wde_d_level}</b> <span class='wde-mono'>".wdh_e($l["level"])."</span></span>";}
    $btn="";
    if($c["kind"]=="problem" || $c["kind"]=="repair" || $c["repair"]){
        $btn="<button type='button' class='wdh-btn sm' OnClick=\"WdeHistory();\"><i class='fas fa-clock-rotate-left' aria-hidden='true'></i>{wde_see_history}</button>";
    }
    return "<div class='wde-det' id='wde-d-$i' hidden>"
        ."<div class='wde-facts'><div class='f'>".@implode("",$facts)."</div>$btn</div>"
        ."<div class='t'><i class='fas fa-terminal' aria-hidden='true'></i>{wde_tech}</div>"
        ."<div class='wde-raw'>".wdh_e($l["raw"])."</div></div>";
}

// Une ligne du journal + son detail repliable.
function wde_row($tpl,array $r,int $i):string{
    $l=$r["l"];$c=$r["c"];
    $sub=$c["sub"]!==""?"<div class='wde-sub'>{$c["sub"]}</div>":"";
    return "<div class='wde-row k-{$c["kind"]}' data-k='{$c["kind"]}' data-r='".($c["repair"]?1:0)."'>"
        ."<div class='wde-main' role='button' tabindex='0' aria-expanded='false' aria-controls='wde-d-$i' OnClick=\"WdeToggle(this);\" OnKeyDown=\"WdeKey(event,this);\">"
        .wde_when($tpl,$l["ts"])
        ."<div class='wde-what'>".wde_kind_icon($c["kind"],$c["icon"])."<div class='wde-wt'><span class='txt'>{$c["html"]}</span>".wde_chips($c["chips"]).$sub."</div></div>"
        .wde_server_cell($tpl,$c["server"])
        ."<span class='wde-chev' aria-hidden='true'><i class='fas fa-chevron-down'></i></span>"
        ."</div>".wde_details($tpl,$r,$i)."</div>";
}

// Un tour de verification replie : resume + les questions posees.
function wde_round($tpl,array $it,int $i):string{
    $n=count($it["servers"]);
    $bad=$it["problems"]>0;
    $asked=$n==1?wdh_h($tpl,"wde_round_n1"):wdh_h($tpl,"wde_round_n",array($n));
    if($bad){
        $res="<span class='bad'>".wdh_h($tpl,$it["problems"]==1?"wde_round_p1":"wde_round_p",array($it["problems"]))."</span>";
    }else{
        $res="<span>".wdh_h($tpl,"wde_round_ok")."</span>";
    }
    $names=wdh_e(@implode(", ",array_keys($it["servers"])));
    if($n==1){
        $srv=wde_server_cell($tpl,reset($it["servers"]));
    }else{
        $srv="<div class='wde-srv' title=\"$names\"><span class='si'><i class='fas fa-server' aria-hidden='true'></i></span><span class='nm muted'>".wdh_h($tpl,"wde_round_srv",array($n))."</span></div>";
    }
    $sub=array();
    foreach($it["rows"] as $r){
        $c=$r["c"];
        $s=wdh_server($tpl,$c["server"]);
        $sub[]="<div class='wde-rline'><span class='tm'>".date("H:i:s",$r["l"]["ts"])."</span>"
            ."<span class='ic'><i class='fas {$c["icon"]}' aria-hidden='true'></i></span><span class='tx'>{$c["html"]}</span>"
            ."<span class='sv'>".wdh_e($s["label"])."</span></div>";
    }
    return "<div class='wde-row k-round".($bad?" bad":"")."' data-k='round' data-r='0'>"
        ."<div class='wde-main' role='button' tabindex='0' aria-expanded='false' aria-controls='wde-d-$i' OnClick=\"WdeToggle(this);\" OnKeyDown=\"WdeKey(event,this);\">"
        .wde_when($tpl,$it["tmax"])
        ."<div class='wde-what'>".wde_kind_icon("round",$bad?"fa-circle-exclamation":"fa-circle-check")
        ."<div class='wde-wt'><span class='txt'><b>{wde_round_t}</b> <span class='dash'>&mdash;</span> $asked, $res</span></div></div>"
        .$srv
        ."<span class='wde-chev' aria-hidden='true'><i class='fas fa-chevron-down'></i></span>"
        ."</div>"
        ."<div class='wde-det wde-rdet' id='wde-d-$i' hidden><div class='t'>{wde_round_lines}</div>".@implode("",$sub)."</div>"
        ."</div>";
}

function wde_day_sep(string $label,bool $today):string{
    $ico=$today?"fa-calendar-day":"fa-calendar";
    return "<div class='wde-day'><i class='fas $ico' aria-hidden='true'></i>$label</div>";
}

// 4. Le journal : en-tete, separateurs de jour, elements, message de filtre vide.
function wde_journal($tpl,array $m):string{
    $now=time();
    $h=array();
    $h[]="<section class='wdh-card wde-card'>";
    if($m["count"]==0){
        $h[]=wde_empty($tpl,$m);
        $h[]="</section>";
        return @implode("",$h);
    }
    $h[]="<div class='wde-head' aria-hidden='true'><div>{wde_c_when}</div><div>{wde_c_what}</div><div>{wde_c_server}</div><div></div></div>";
    $h[]="<div id='wde-rows'>";
    $last=null;
    foreach($m["items"] as $i=>$it){
        $ts=$it["ts"];
        // Une ligne sans date lisible reste sous le jour courant.
        $day=$ts>0?date("Y-m-d",$ts):($last===null?"?":$last);
        if($day!==$last){
            $h[]=wde_day_sep(wde_day_sep_label($ts,$now),$day==date("Y-m-d",$now));
            $last=$day;
        }
        $h[]=$it["type"]=="round"?wde_round($tpl,$it,$i):wde_row($tpl,$it["row"],$i);
    }
    $h[]="</div>";
    $h[]="<div class='wde-zero' id='wde-zero' hidden><span id='wde-zero-t'></span> <button type='button' class='wde-exbtn' OnClick=\"WdeFilter('all');\">{wde_show_all}</button></div>";
    $h[]=wde_footer($tpl,$m);
    $h[]="</section>";
    return @implode("",$h);
}

// Etats vides : recherche sans resultat, journal pas encore ecrit.
function wde_empty($tpl,array $m):string{
    if($m["search"]!=="" || $m["period"]!=="any"){
        $t=$m["search"]!==""?wdh_h($tpl,"wde_e_search_t",array($m["search"])):"{wde_e_period_t}";
        return "<div class='wdh-empty'><div class='ic'><i class='fas fa-magnifying-glass' aria-hidden='true'></i></div><h4>$t</h4>"
            ."<p>{wde_e_search}</p><button type='button' class='wdh-btn' OnClick=\"WdeClear(true);\">{wdh_clear}</button></div>";
    }
    $s=dnswd_settings();
    if($s["enabled"]==0){
        return "<div class='wdh-empty'><div class='ic'><i class='fas fa-power-off' aria-hidden='true'></i></div><h4>{wde_e_none_t}</h4><p>{wde_e_off}</p>"
            ."<button type='button' class='wdh-btn primary' OnClick=\"WdeSettings();\">{dnswd_turn_on}</button></div>";
    }
    $txt=wdh_h($tpl,$s["interval"]==1?"wde_e_none1":"wde_e_none",array($s["interval"]));
    return "<div class='wdh-empty'><div class='ic'><i class='fas fa-hourglass-start' aria-hidden='true'></i></div><h4>{wde_e_none_t}</h4><p>$txt</p>"
        ."<button type='button' class='wdh-btn' OnClick=\"WdeSettings();\"><i class='fas fa-sliders' aria-hidden='true'></i>{wdh_settings}</button></div>";
}

function wde_error($tpl,string $err):string{
    return "<section class='wdh-card wde-card'><div class='wdh-empty err'><div class='ic'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i></div>"
        ."<h4>{wde_err_t}</h4><p>{wde_err}</p>"
        ."<details class='wde-errdet'><summary>{wde_tech}</summary><div class='wde-raw'>".wdh_e($err)."</div></details>"
        ."<button type='button' class='wdh-btn' OnClick=\"WdeReload();\"><i class='fas fa-rotate' aria-hidden='true'></i>{wde_retry}</button></div></section>";
}

// 5. Pied (dans la carte) : « Show more lines » quand la limite est atteinte, note de rotation.
function wde_footer($tpl,array $m):string{
    $more="";
    $next=0;
    foreach(wde_sizes() as $n){if($n>$m["max"]){$next=$n;break;}}
    if($next>0 && $m["count"]>=$m["max"]){
        $more="<button type='button' class='wde-exbtn strong' OnClick=\"WdeSetMax($next);\"><i class='fas fa-arrow-down' aria-hidden='true'></i> {wde_more}</button>"
            ."<span>".dnswd_h($tpl,"wde_more_next",strval($next))."</span>";
    }
    return "<footer class='wde-foot'><div class='l'>$more</div><div class='r'><i class='fas fa-circle-info' aria-hidden='true'></i> {wde_rotated}</div></footer>";
}

// Les compteurs des segments et le texte des filtres vides.
function wde_counts($tpl,array $m):array{
    $n=number_format($m["count"]);
    return array(
        "counts"=>array("all"=>$n,"problems"=>number_format($m["problems"]),"repairs"=>number_format($m["repairs"])),
        "zero"=>array("problems"=>wdh_t($tpl,"wde_zero_problems",array($n)),"repairs"=>wdh_t($tpl,"wde_zero_repairs",array($n))),
    );
}

/**
 * L'onglet Events : hote du bandeau, filtres, hote du journal. Le TINY est pose
 * ici, une fois (il retire du titre les filtres de periode de l'onglet Status).
 */
function events_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $p=wde_params();
    $m=dnswd_settings();
    $html=array();
    $html[]="<div class='wdh wde'>";
    $html[]="<div id='wde-bannerhost'>".wde_banner($tpl,$m)."</div>";
    $html[]=wde_filters($tpl,$p);
    $html[]="<div id='wde-host'></div>";
    $html[]="</div>";

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{watchdog}";
    $TINY_ARRAY["ICO"]=ico_watchdog;
    $TINY_ARRAY["EXPL"]="{dns_watchdog_howto}";
    $TINY_ARRAY["URL"]="dns-watchdog";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo wdh_style().wde_style().$tpl->_ENGINE_parse_body(@implode("",$html))
        .wde_js($page)
        ."<script>NoSpinner();WdeState.max={$p["max"]};WdeState.period='{$p["period"]}';WdeState.filter='problems';WdeReload();$jstiny</script>";
    return true;
}

// ?wde-banner=yes : le bandeau seul, rejoue apres un enregistrement des reglages.
function wde_banner_host():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(wde_banner($tpl,dnswd_settings()));
    return true;
}

// Repondeur ?wde-host=yes&search=&max=&period= : portee, journal et pied.
function wde_host():bool{
    $tpl=new template_admin();
    $p=wde_params();
    $m=wde_model($p);
    if($m["error"]!==""){
        $c=array("counts"=>array("all"=>"—","problems"=>"—","repairs"=>"—"),"zero"=>array());
        echo $tpl->_ENGINE_parse_body(wde_error($tpl,$m["error"]))
            ."<script>NoSpinner();WdeCounts(".json_encode($c).");</script>";
        return false;
    }
    $html="";
    if($m["short"]){
        $html.="<div class='wdh-notice wde-note'><i class='fas fa-circle-info' aria-hidden='true'></i><span>{wde_short}</span></div>";
    }
    if($m["count"]>0){$html.=wde_scope($tpl,$m);}
    $html.=wde_journal($tpl,$m);
    echo $tpl->_ENGINE_parse_body($html)
        ."<script>NoSpinner();WdeCounts(".json_encode(wde_counts($tpl,$m)).");WdeApply();</script>";
    return true;
}

// --- JavaScript (hors du parseur : ses accolades seraient prises pour des tokens) ---
function wde_js(string $page):string{
    return "<script>
window.WdeState=window.WdeState||{filter:'problems',period:'any',max:50,zero:{}};
function WdeUrl(){var q=document.getElementById('wde-q');var s=q?q.value:'';
 return '$page?wde-host=yes&max='+encodeURIComponent(WdeState.max)+'&period='+encodeURIComponent(WdeState.period)+'&search='+encodeURIComponent(s);}
function WdeReload(){if(!document.getElementById('wde-host')){return;}LoadAjaxSilent('wde-host',WdeUrl());}
function WdeSettings(){Loadjs('$page?watch-js=yes');}
function WdeAfterSettings(){if(!document.getElementById('wde-bannerhost')){return;}LoadAjaxSilent('wde-bannerhost','$page?wde-banner=yes');WdeReload();}
function WdeSetMax(v){WdeState.max=parseInt(v,10)||50;var s=document.getElementById('wde-max');if(s){s.value=String(WdeState.max);}WdeReload();}
function WdeExample(t){var q=document.getElementById('wde-q');if(q){q.value=t;}WdeReload();}
function WdeRadio(sel,attr,val,base){var b=document.querySelectorAll(sel);
 for(var i=0;i<b.length;i++){var on=b[i].getAttribute(attr)===val;b[i].className=base+(on?' on':'');b[i].setAttribute('aria-checked',on?'true':'false');}}
function WdePeriod(p){WdeState.period=p;WdeRadio('.wde-pbtn','data-p',p,'wde-pbtn');WdeReload();}
function WdeClear(all){var q=document.getElementById('wde-q');if(q){q.value='';}if(all){WdeState.period='any';WdeRadio('.wde-pbtn','data-p','any','wde-pbtn');}WdeReload();if(q){q.focus();}}
function WdeCounts(c){WdeState.zero=c.zero||{};for(var k in c.counts){var e=document.getElementById('wde-count-'+k);if(e){e.textContent=c.counts[k];}}}
function WdeFilter(f){WdeState.filter=f;WdeRadio('.wde-sbtn','data-f',f,'wdh-sbtn wde-sbtn');WdeApply();}
function WdeHistory(){var h=document.getElementById('wde-host');var c=h?h.closest('.tabs-container'):null;
 var a=c?c.querySelectorAll('ul.nav-tabs>li>a'):[];if(a.length>1){a[1].click();}}
function WdeApply(){var box=document.getElementById('wde-rows');if(!box){return;}var f=WdeState.filter,kids=box.children,shown=0,sep=null,sepHas=false;
 for(var i=0;i<kids.length;i++){var el=kids[i];
  if(el.className.indexOf('wde-day')===0){if(sep){sep.style.display=sepHas?'':'none';}sep=el;sepHas=false;continue;}
  var k=el.getAttribute('data-k'),r=el.getAttribute('data-r')==='1',ok=true;
  if(f==='problems'){ok=(k==='problem'||k==='warn');}
  if(f==='repairs'){ok=r;}
  el.style.display=ok?'':'none';if(ok){shown++;sepHas=true;}}
 if(sep){sep.style.display=sepHas?'':'none';}
 var z=document.getElementById('wde-zero'),zt=document.getElementById('wde-zero-t');
 if(z&&zt){if(shown===0&&WdeState.zero[f]){zt.textContent=WdeState.zero[f];z.hidden=false;}else{z.hidden=true;}}}
function WdeToggle(el){var id=el.getAttribute('aria-controls'),d=document.getElementById(id);if(!d){return;}
 var open=el.getAttribute('aria-expanded')==='true';el.setAttribute('aria-expanded',open?'false':'true');d.hidden=open;}
function WdeKey(e,el){if(e.keyCode==13||e.keyCode==32){e.preventDefault();WdeToggle(el);}}
(function(){var q=document.getElementById('wde-q');if(!q||q.__wde){return;}q.__wde=1;
 q.addEventListener('input',function(){clearTimeout(window.WdeTimer);window.WdeTimer=setTimeout(WdeReload,600);});
 q.addEventListener('keydown',function(e){if(e.keyCode==13){e.preventDefault();clearTimeout(window.WdeTimer);WdeReload();}});})();
</script>";
}

function wde_style():string{
    if(!empty($GLOBALS["WDE_CSS"])){return "";}
    $GLOBALS["WDE_CSS"]=true;
    return "<style>
.wde .wdh-scount{font-size:13px}
.wde-filters{display:flex;flex-direction:column;gap:12px;padding:16px 20px}
.wde-frow{display:flex;flex-wrap:wrap;gap:12px 16px;align-items:center}
.wde .wdh-sbtn svg,.wde .wdh-sbtn>i{margin-right:6px}
.wde-vsep{width:1px;height:28px;background:#e5e7eb}
.wde-per{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.wde-per .lbl{font-size:13px;color:#6b7280;margin-right:2px}
.wde-pbtn{height:32px;padding:0 14px;border-radius:999px;border:1px solid transparent;background:#f3f4f6;color:#374151;font-size:13px;font-weight:500;cursor:pointer}
.wde-pbtn:hover{background:#e6eeed}
.wde-pbtn.on{background:#005447;color:#fff;font-weight:600}
.wde-pbtn:focus-visible{outline:2px solid #005447;outline-offset:2px}
.wde-search{flex:0 1 380px;min-width:260px}
.wde-show{display:flex;gap:8px;align-items:center;font-size:13px;color:#475569;font-weight:500;margin:0;white-space:nowrap}
.wde-sel{height:36px;border:1px solid #cbd5e1;border-radius:8px;padding:0 8px;font-size:13px;background:#fff;color:#0f172a}
.wde-sel:focus{outline:2px solid #005447;outline-offset:1px}
.wde-helps{display:flex;justify-content:space-between;gap:24px;padding-top:10px;border-top:1px solid #f1f5f9;font-size:13px;color:#6b7280}
.wde-exbtn{background:none;border:0;padding:0;color:#005447;cursor:pointer;font-size:13px;font-weight:500}
.wde-helps .wde-exbtn{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;background:#f1f5f9;border-radius:4px;padding:1px 6px;color:#0f172a}
.wde-exbtn:hover{color:#003d33;text-decoration:underline}
.wde-exbtn.strong{font-weight:600;display:inline-flex;gap:6px;align-items:center}
.wde-exbtn:focus-visible{outline:2px solid #005447;outline-offset:2px;border-radius:2px}
.wde-note{margin:0 0 12px 0}
.wde-scope{display:flex;justify-content:space-between;align-items:baseline;gap:16px;margin:18px 4px 10px 4px;font-size:14px;color:#334155}
.wde-scope b{color:#0f172a;font-weight:600}
.wde-scope .p{color:#b91c1c;font-weight:600}
.wde-scope .r{color:#b45309;font-weight:600}
.wde-scope .n{display:flex;gap:6px;align-items:center;font-size:13px;color:#6b7280;white-space:nowrap}
.wde-card{overflow:hidden;padding:0}
.wde-head,.wde-main{display:grid;grid-template-columns:130px minmax(0,1fr) 200px 20px;gap:16px;align-items:center}
.wde-head{padding:12px 20px;font-size:13px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#475569;border-bottom:1px solid #e2e8f0}
.wde-day{display:flex;gap:8px;align-items:center;padding:9px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:13px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#475569}
.wde-day i,.wde-day svg{color:#005447}
.wde-row{border-bottom:1px solid #f1f5f9}
.wde-main{padding:12px 20px;min-height:56px;cursor:pointer;box-shadow:inset 3px 0 0 transparent}
.wde-main:hover{background:#f9fafb}
.wde-main:focus-visible{outline:2px solid #005447;outline-offset:-2px}
.wde-row.k-problem>.wde-main{box-shadow:inset 3px 0 0 #dc2626}
.wde-row.k-warn>.wde-main,.wde-row.k-repair>.wde-main{box-shadow:inset 3px 0 0 #d97706}
.wde-when{display:flex;gap:8px;align-items:center;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#334155;white-space:nowrap}
.wde-when>i,.wde-when>svg{color:#94a3b8}
.wde-chev{display:inline-flex;justify-content:flex-end;font-size:12px;color:#94a3b8}
.wde-chev svg,.wde-chev i{transition:none}
.wde-main[aria-expanded=true] .wde-chev{transform:rotate(180deg)}
.wde-what{display:flex;gap:12px;align-items:center;min-width:0}
.wde-ki{flex:0 0 28px;width:28px;height:28px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:13px;background:#f3f4f6;color:#6b7280}
.k-check .wde-ki,.k-round .wde-ki{background:#e6eeed;color:#005447}
.k-round.bad .wde-ki,.k-problem .wde-ki{background:#fef2f2;color:#dc2626}
.k-warn .wde-ki,.k-repair .wde-ki{background:#fffbeb;color:#d97706}
.wde-wt{display:flex;flex-wrap:wrap;gap:4px 8px;align-items:center;font-size:14px;color:#0f172a;min-width:0}
.wde-wt .txt{min-width:0;overflow-wrap:anywhere}
.wde-wt .txt b{font-weight:600}
.k-round .txt b{font-weight:600}
.k-round .dash{color:#94a3b8}
.k-round .bad{color:#dc2626;font-weight:600}
.k-round .txt>span:not(.dash):not(.bad){color:#475569}
.wde-site{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;background:#f1f5f9;padding:1px 6px;border-radius:4px;color:#0f172a}
.wde-mono,.wde-rawmsg{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#334155}
.wde-sub{flex-basis:100%;font-size:13px;color:#92400e}
.wde-chip{display:inline-flex;align-items:center;gap:5px;font-size:13px;font-weight:500;padding:2px 10px;border-radius:999px;white-space:nowrap;border:1px solid transparent}
.wde-chip.red{background:#fef2f2;color:#b91c1c;border-color:#fecaca}
.wde-chip.amber{background:#fffbeb;color:#92400e;border-color:#fde68a}
.wde-chip.grey{background:#f1f5f9;color:#475569;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.wde-chip.code{background:#fef2f2;color:#b91c1c;border-color:#fecaca;border-radius:6px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:600}
.wde-srv{display:flex;gap:8px;align-items:center;min-width:0;font-size:13.5px;color:#0f172a}
.wde-srv .si{flex:0 0 14px;width:14px;color:#94a3b8;font-size:12px;text-align:center}
.wde-srv .nm{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.wde-srv .nm.muted{color:#475569}
.wde-det{padding:4px 20px 16px 186px;background:#f8fafc;border-top:1px dashed #e2e8f0}
.wde-facts{display:flex;justify-content:space-between;align-items:center;gap:16px;margin:10px 0 4px 0}
.wde-facts .f{display:flex;flex-wrap:wrap;gap:6px 20px;font-size:13px;color:#475569}
.wde-facts .f b{font-weight:600;color:#334155}
.wdh-btn.sm{height:32px;padding:0 12px;font-size:13px}
.wde-det .t{display:flex;gap:6px;align-items:center;margin:10px 0 6px 0;font-size:13px;font-weight:600;color:#475569}
.wde-raw{white-space:pre-wrap;word-break:break-all;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#1e293b;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:8px 10px;text-align:left}
.wde-rline{display:grid;grid-template-columns:70px 18px minmax(0,1fr) 180px;gap:10px;align-items:center;padding:6px 0;border-bottom:1px solid #eef2f7;font-size:13px;color:#334155}
.wde-rline:last-child{border-bottom:0}
.wde-rline .tm{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;color:#64748b}
.wde-rline .ic{color:#005447;text-align:center}
.wde-rline .tx{min-width:0;overflow-wrap:anywhere}
.wde-rline .sv{color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.wde-errdet{margin:0 0 16px 0;text-align:left}
.wde-errdet summary{cursor:pointer;font-size:13px;color:#475569;margin-bottom:6px}
.wde-zero{padding:28px 24px;text-align:center;font-size:13.5px;color:#475569}
.wde-foot{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:14px 20px;background:#fff;font-size:13px;color:#64748b;border-top:1px solid #f1f5f9}
.wde-foot .l{display:flex;gap:12px;align-items:center}
.wde-foot .r{display:flex;gap:6px;align-items:center}
@media (max-width:1200px){
 .wde-head,.wde-main{grid-template-columns:120px minmax(0,1fr) 160px 20px}
 .wde-det{padding-left:20px}
 .wde-helps{flex-direction:column;gap:4px}
}
@media (prefers-reduced-motion:no-preference){.wde-main{transition:background-color .15s ease}.wde-chev{transition:transform .15s ease}}
</style>";
}

/* ===========================================================================
 * Onglet History (maquette Stitch « DNS watchdog history », 2026-11-20).
 *
 * Source : la table SQLite dnswatchdog de /home/artica/SQLITE/dns_watchdog.db,
 * ecrite par saveAlert() (articarest/dnswatchdog/dnswatchdog.go) a chaque test
 * qui echoue :
 *   dnswatchdog(ID, zDate INTEGER, dnsserver TEXT, error TEXT, queryserv TEXT, notified INTEGER)
 * notified n'est jamais ecrit par le demon : il n'est pas affiche. Aucune ligne
 * « retabli » n'existe : une rafale (incidents rapproches) est la seule lecture
 * honnete d'une panne qui dure.
 *
 * Structure : history_start() pose le bandeau, la barre de filtres et l'hote
 * #wdh-host ; ?wdh-host=yes ne recharge QUE l'hote (tuiles + tableau), de sorte
 * que le champ de recherche garde le focus et ce qui y est tape.
 * ========================================================================= */

// Les trois adresses locales que le demon interroge (dnswatchdog.go).
function wdh_local_servers():array{return array("127.0.0.1","127.0.0.45","127.0.0.55");}
function wdh_max_rows():int{return 500;}

// Liste blanche des parametres : ils partent dans du SQL lie et dans des URL.
function wdh_params():array{
    $search=trim((string)(isset($_GET["search"])?$_GET["search"]:""));
    if($search=="*"){$search="";}
    if(function_exists("mb_substr")){$search=mb_substr($search,0,120);}else{$search=substr($search,0,120);}
    $scope=isset($_GET["scope"])?(string)$_GET["scope"]:"all";
    if(!in_array($scope,array("all","up","local"))){$scope="all";}
    $period=isset($_GET["period"])?(string)$_GET["period"]:"7d";
    if(!isset(wdhx_periods()[$period])){$period="7d";}
    $day=isset($_GET["day"])?(string)$_GET["day"]:"";
    if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$day) || strtotime($day." 00:00:00")===false){$day="";}
    return array("search"=>$search,"scope"=>$scope,"period"=>$period,"day"=>$day);
}

// Traduit un token puis remplace ses %s DANS L'ORDRE (dnswd_t les remplace tous
// par la meme valeur). Texte brut : l'appelant echappe.
function wdh_t($tpl,string $token,array $vals=array()):string{
    $txt=dnswd_t($tpl,$token,"%s");
    $i=0;
    return preg_replace_callback('/%s/',function($m) use ($vals,&$i){
        $v=isset($vals[$i])?(string)$vals[$i]:"";
        $i++;
        return $v;
    },$txt);
}
// Echappe une valeur venue de la base ou de l'URL : HTML ET accolades (le texte
// passe ensuite par _ENGINE_parse_body, qui prendrait {x} pour un token).
function wdh_e($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES));
}
// Une date de time_to_date() : ses noms de jour et de mois sont des {tokens}
// que le moteur doit traduire, on n'echappe donc pas ses accolades.
function wdh_date($tpl,int $z):string{
    return htmlspecialchars($tpl->time_to_date($z,true));
}
function wdh_h($tpl,string $token,array $vals=array()):string{
    return wdh_e(wdh_t($tpl,$token,$vals));
}

// Ecart entre deux incidents qui forme une rafale : deux balayages de suite en
// echec sont separes d'un intervalle ; on tolere le double, et jamais moins de
// 5 min (les 3 essais sur 127.0.0.1 tombent a 0,7 s d'intervalle).
function wdh_burst_gap(int $interval):int{
    return max(300,2*$interval*60);
}

// Duree courte : « 30 s », « 2 min 10 s », « 3 h », « 2 d ».
function wdh_dur($tpl,int $secs):string{
    if($secs<0){$secs=0;}
    if($secs<60){return wdh_t($tpl,"wdh_u_s",array($secs));}
    if($secs<3600){
        $m=intval(floor($secs/60));$s=$secs%60;
        $v=wdh_t($tpl,"wdh_u_m",array($m));
        if($m<10 && $s>0){$v.=" ".wdh_t($tpl,"wdh_u_s",array($s));}
        return $v;
    }
    if($secs<172800){return wdh_t($tpl,"wdh_u_h",array(intval(round($secs/3600))));}
    return wdh_t($tpl,"wdh_u_d",array(intval(round($secs/86400))));
}

// Une valeur dnsserver du demon -> nom courant, icone, valeur technique, etiquettes.
function wdh_server($tpl,string $raw):array{
    $raw=trim($raw);
    $r=array("kind"=>"up","label"=>wdh_t($tpl,"wdh_srv_upstream"),"icon"=>"fa-server","tech"=>$raw,"tags"=>array());
    if($raw===""){
        $r["label"]=wdh_t($tpl,"wdh_srv_unknown");$r["icon"]="fa-circle-question";
        return $r;
    }
    $doh=array("8.8.8.8"=>"wdh_srv_google","9.9.9.9"=>"wdh_srv_quad9","208.67.222.222"=>"wdh_srv_opendns");
    if(strpos($raw,"/")!==false){
        $ip=substr($raw,0,strpos($raw,"/"));
        $r["label"]=wdh_t($tpl,isset($doh[$ip])?$doh[$ip]:"wdh_srv_doh");
        $r["icon"]="fa-lock";
        return $r;
    }
    if($raw=="127.0.0.45"){
        $r["kind"]="local";$r["label"]=wdh_t($tpl,"wdh_srv_cloudflared");$r["icon"]="fa-cloud";
        return $r;
    }
    if($raw=="127.0.0.55"){
        $r["kind"]="local";$r["label"]=wdh_t($tpl,"wdh_srv_cache");$r["icon"]="fa-database";
        $r["tags"][]=array("amber",wdh_t($tpl,"wdh_tag_restart"),wdh_t($tpl,"wdh_tag_restart_help"));
        return $r;
    }
    if($raw=="127.0.0.1"){
        $r["kind"]="local";$r["label"]=wdh_t($tpl,"wdh_srv_local");$r["icon"]="fa-house-signal";
        $r["tags"][]=array("amber",wdh_t($tpl,"wdh_tag_restart3"),wdh_t($tpl,"wdh_tag_restart3_help"));
        return $r;
    }
    if(strpos($raw,"tcp:")===0){
        $r["tags"][]=array("grey","TCP","");
    }
    return $r;
}

// Le texte d'erreur Go -> une raison en mots courants (mots-cles du texte).
function wdh_reason($tpl,string $error):array{
    $e=strtolower($error);
    if(strpos($e,"timeout")!==false || strpos($e,"timed out")!==false || strpos($e,"deadline exceeded")!==false){
        return array("fa-hourglass-end",wdh_t($tpl,"wdh_r_timeout"));
    }
    if(strpos($e,"refused")!==false){return array("fa-ban",wdh_t($tpl,"wdh_r_refused"));}
    if(strpos($e,"no such host")!==false){return array("fa-circle-question",wdh_t($tpl,"wdh_r_notfound"));}
    return array("fa-triangle-exclamation",wdh_t($tpl,"wdh_r_other"));
}

// --- Deuxieme passe (maquette Stitch « DNS watchdog history redesign », 2026-11-21) ---
// Periode (24 h / 7 j / 30 j / tout), frise des echecs par heure ou par jour
// (comptes SQL sur zDate, cliquable pour filtrer un jour), pannes regroupees
// repliables, detail brut sur demande. La table ne garde QUE les echecs : aucun
// taux de disponibilite ni duree de panne n'est calculable.
function wdhx_periods():array{return array("24h"=>86400,"7d"=>604800,"30d"=>2592000,"all"=>0);}
function wdhx_tz():int{return intval(date("Z"));}
// Un jour, sans heure, traduit en texte brut (l'appelant echappe) : « Monday 12 October ».
function wdhx_day($tpl,int $ts):string{
    return html_entity_decode($tpl->_ENGINE_parse_body($tpl->time_to_date($ts,false)),ENT_QUOTES,"UTF-8");
}

// Clause WHERE : recherche, origine, periode, jour. $parts choisit les filtres.
function wdh_where(array $p,bool $withScope,bool $withPeriod=true,bool $withDay=true):array{
    $w=array();$args=array();
    if($p["search"]!==""){
        $like="%".str_replace("*","%",$p["search"])."%";
        $w[]="(dnsserver LIKE ? OR queryserv LIKE ? OR error LIKE ?)";
        $args[]=$like;$args[]=$like;$args[]=$like;
    }
    if($withScope && $p["scope"]!="all"){
        $in="'".@implode("','",wdh_local_servers())."'";
        $w[]=$p["scope"]=="local"?"dnsserver IN ($in)":"(dnsserver IS NULL OR dnsserver NOT IN ($in))";
    }
    $per=wdhx_periods();
    if($withPeriod && $per[$p["period"]]>0){
        $w[]="CAST(zDate AS INTEGER) >= ?";$args[]=time()-$per[$p["period"]];
    }
    if($withDay && $p["day"]!==""){
        $t=strtotime($p["day"]." 00:00:00");
        $w[]="CAST(zDate AS INTEGER) >= ? AND CAST(zDate AS INTEGER) < ?";$args[]=$t;$args[]=$t+86400;
    }
    return array(count($w)>0?" WHERE ".@implode(" AND ",$w):"",$args);
}
function wdh_query($q,string $sql,array $args){
    if(count($args)==0){return $q->QUERY_SQL($sql);}
    return $q->QUERY_SQL_PARAMS($sql,$args);
}
function wdhx_db(){return new lib_sqlite("/home/artica/SQLITE/dns_watchdog.db");}

// Toute la table (sans filtre) : nombre et plus ancien echec. Sert au bandeau.
function wdhx_whole($q):array{
    $r=array("n"=>0,"oldest"=>0);
    $res=$q->QUERY_SQL("SELECT COUNT(*) AS n, MIN(CAST(zDate AS INTEGER)) AS z FROM dnswatchdog");
    if(is_array($res) && isset($res[0])){$r["n"]=intval($res[0]["n"]);$r["oldest"]=intval($res[0]["z"]);}
    return $r;
}

// La frise : un seau par heure (24 h) ou par jour (7 j, 30 j, « tout » = 90 derniers jours),
// avec recherche + origine, sans filtre de jour. Jours sans echec inclus (valeur 0).
function wdhx_timeline($q,array $p):array{
    $t=array("unit"=>$p["period"]=="24h"?"h":"d","buckets"=>array(),"max"=>0,"busiest"=>-1);
    $tz=wdhx_tz();
    $now=time();
    if($t["unit"]=="h"){
        $first=intval(floor(($now-86400+$tz)/3600))+1;$last=intval(floor(($now+$tz)/3600));$size=3600;
    }else{
        $days=$p["period"]=="7d"?7:($p["period"]=="30d"?30:90);
        $last=intval(floor(($now+$tz)/86400));$first=$last-$days+1;$size=86400;
    }
    for($k=$first;$k<=$last;$k++){$t["buckets"][$k]=0;}
    $pp=$p;$pp["period"]="all";
    list($w,$args)=wdh_where($pp,true,false,false);
    $cond="CAST(zDate AS INTEGER) >= ?";
    $w=$w===""?" WHERE $cond":"$w AND $cond";
    $args[]=$first*$size-$tz;
    array_unshift($args,$tz);
    $res=wdh_query($q,"SELECT CAST((CAST(zDate AS INTEGER)+?)/$size AS INTEGER) AS b, COUNT(*) AS n FROM dnswatchdog$w GROUP BY b",$args);
    if(is_array($res)){
        foreach($res as $r){$b=intval($r["b"]);if(isset($t["buckets"][$b])){$t["buckets"][$b]=intval($r["n"]);}}
    }
    foreach($t["buckets"] as $b=>$n){if($n>$t["max"]){$t["max"]=$n;$t["busiest"]=$b;}}
    $t["size"]=$size;
    return $t;
}

// Le modele, lu une fois par requete.
function wdh_model(array $p):array{
    $m=dnswd_settings();
    foreach(array("search","scope","period","day") as $k){$m[$k]=$p[$k];}
    $m["error"]="";$m["rows"]=array();$m["top"]=array();$m["timeline"]=null;
    $m["count_all"]=0;$m["count_local"]=0;$m["count_up"]=0;$m["total"]=0;
    $q=wdhx_db();
    $m["whole"]=wdhx_whole($q);
    if(!$q->ok){$m["error"]=(string)$q->mysql_error;return $m;}
    $in="'".@implode("','",wdh_local_servers())."'";

    list($w,$args)=wdh_where($p,false);
    $res=wdh_query($q,"SELECT COUNT(*) AS n, SUM(CASE WHEN dnsserver IN ($in) THEN 1 ELSE 0 END) AS loc FROM dnswatchdog$w",$args);
    if(!$q->ok){$m["error"]=(string)$q->mysql_error;return $m;}
    if(is_array($res) && isset($res[0])){
        $m["count_all"]=intval($res[0]["n"]);
        $m["count_local"]=intval($res[0]["loc"]);
        $m["count_up"]=$m["count_all"]-$m["count_local"];
    }
    $m["total"]=$p["scope"]=="local"?$m["count_local"]:($p["scope"]=="up"?$m["count_up"]:$m["count_all"]);
    if($m["whole"]["n"]>0){$m["timeline"]=wdhx_timeline($q,$p);}
    if($m["total"]==0){return $m;}

    list($w,$args)=wdh_where($p,true);
    $res=wdh_query($q,"SELECT dnsserver, COUNT(*) AS n FROM dnswatchdog$w GROUP BY dnsserver ORDER BY n DESC LIMIT 5",$args);
    if(is_array($res)){
        foreach($res as $r){$m["top"][]=array("server"=>(string)$r["dnsserver"],"n"=>intval($r["n"]));}
    }
    $res=wdh_query($q,"SELECT zDate,dnsserver,queryserv,error FROM dnswatchdog$w ORDER BY ID DESC LIMIT ".wdh_max_rows(),$args);
    if(!$q->ok){$m["error"]=(string)$q->mysql_error;return $m;}
    if(is_array($res)){$m["rows"]=$res;}
    return $m;
}

// --- Widgets ---------------------------------------------------------------

function wdh_notices($tpl,array $m):string{
    $h=array();
    if($m["cluster"]){
        $h[]="<div class='wdh-notice'><i class='fas fa-diagram-project' aria-hidden='true'></i><span>{dnswd_cluster_note}</span></div>";
    }
    if($m["offline"]){
        $h[]="<div class='wdh-notice'><i class='fas fa-plug-circle-xmark' aria-hidden='true'></i><span>{dnswd_offline_note}</span></div>";
    }
    return @implode("",$h);
}

// 1. Bandeau d'accroche. Les puces lisent la configuration et la base.
function wdh_banner($tpl,array $m,int $oldest):string{
    $off=$m["enabled"]==0;
    $h=array();
    $h[]="<section class='wdh-card wdh-banner'>";
    $h[]="<div class='wdh-bico' aria-hidden='true'><i class='fas fa-clock-rotate-left'></i></div>";
    $text=$off?"{wdhx_text_off}":wdh_h($tpl,$m["interval"]==1?"wdhx_text1":"wdhx_text",array($m["interval"]));
    $h[]="<div class='wdh-btxt'><h2>{wdhx_title}</h2><p>$text</p>";
    $h[]="<div class='wdh-chips'>";
    if(!$off){
        $every=dnswd_h($tpl,$m["interval"]==1?"dnswd_chip_every1":"dnswd_chip_every",strval($m["interval"]));
        $h[]="<span class='wdh-chip'><i class='fas fa-clock' aria-hidden='true'></i>$every</span>";
    }
    if(!$off && !$m["offline"] && !$m["cluster"]){
        $h[]="<span class='wdh-chip'><i class='fas fa-rotate' aria-hidden='true'></i>{wdh_chip_restart}</span>";
    }
    if($oldest>0){
        $since=wdh_h($tpl,"wdhx_chip_kept",array(wdhx_day($tpl,$oldest)));
        $h[]="<span class='wdh-chip'><i class='fas fa-database' aria-hidden='true'></i>$since</span>";
    }
    $h[]="</div>";
    if($off){
        $h[]="<div class='wdh-notice warn'><i class='fas fa-power-off' aria-hidden='true'></i><span>{wdh_off_note}</span></div>";
    }
    $h[]=wdh_notices($tpl,$m);
    $h[]="</div>";
    $h[]="<div class='wdh-bact'>";
    if($off){
        $h[]="<button type='button' class='wdh-btn primary' OnClick=\"WdhSettings();\"><i class='fas fa-power-off' aria-hidden='true'></i>{dnswd_turn_on}</button>";
    }else{
        $h[]="<button type='button' class='wdh-btn' OnClick=\"WdhSettings();\"><i class='fas fa-sliders' aria-hidden='true'></i>{wdh_settings}</button>";
    }
    $h[]="<button type='button' class='wdh-btn ghost' OnClick=\"WdhEvents();\"><i class='fas fa-list' aria-hidden='true'></i>{wdhx_open_events}</button>";
    $h[]="<button type='button' class='wdh-btn icon' aria-label=\"{wdh_refresh}\" title=\"{wdh_refresh}\" OnClick=\"WdhReload();\"><i class='fas fa-rotate' aria-hidden='true'></i></button>";
    $h[]="</div></section>";
    return @implode("",$h);
}

// 2. Barre de filtres : periode, origine (compteurs poses par l'hote), recherche, exemples.
function wdh_filters($tpl,array $p):string{
    $per=array("24h"=>"{wdhx_p_24h}","7d"=>"{wdhx_p_7d}","30d"=>"{wdhx_p_30d}","all"=>"{wdhx_p_all}");
    $seg=array("all"=>"{wdh_f_all}","up"=>"{wdhx_f_up}","local"=>"{wdhx_f_local}");
    $h=array();
    $h[]="<section class='wdh-card wdh-filters wdhx-filters'><div class='wdhx-frow'>";
    $h[]="<div class='wdh-seg' role='radiogroup' aria-label=\"{wdhx_period_label}\">";
    foreach($per as $k=>$label){
        $on=$p["period"]==$k;
        $h[]="<button type='button' role='radio' aria-checked='".($on?"true":"false")."' class='wdh-sbtn wdhx-pbtn".($on?" on":"")."' data-p='$k' OnClick=\"WdhPeriod('$k');\">$label</button>";
    }
    $h[]="</div>";
    $h[]="<div class='wdh-seg' role='radiogroup' aria-label=\"{wdh_filter_label}\" title=\"{wdhx_origin_help}\">";
    foreach($seg as $k=>$label){
        $on=$p["scope"]==$k;
        $h[]="<button type='button' role='radio' aria-checked='".($on?"true":"false")."' class='wdh-sbtn wdhx-sbtn".($on?" on":"")."' data-scope='$k' OnClick=\"WdhScope('$k');\">$label <span class='wdh-scount' id='wdh-count-$k'>&hellip;</span></button>";
    }
    $h[]="</div></div><div class='wdhx-frow'>";
    $val=wdh_e($p["search"]);
    $h[]="<div class='wdh-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>";
    $h[]="<input type='search' id='wdh-q' value=\"$val\" placeholder=\"{wdhx_search_ph}\" aria-label=\"{wdhx_search_ph}\" autocomplete='off'>";
    $h[]="<button type='button' class='wdh-clear' aria-label=\"{wdh_clear}\" title=\"{wdh_clear}\" OnClick=\"WdhClear();\"><i class='fas fa-xmark' aria-hidden='true'></i></button></div>";
    $h[]="<div class='wdhx-ex'><span>{wdhx_try}</span>";
    foreach(array("8.8.8.8","timeout","refused") as $e){$h[]="<button type='button' OnClick=\"WdhExample('$e');\">$e</button>";}
    $h[]="</div></div></section>";
    $h[]="<div class='wdh-help'><i class='fas fa-circle-info' aria-hidden='true'></i><span>{wdhx_origin_help}</span></div>";
    return @implode("",$h);
}

// 3. Frise des echecs. Echelle = seau le plus charge de la periode.
// Libelle d'un seau, texte brut. $short : le numero du jour seul (frises denses).
function wdhx_bucket_label($tpl,array $t,int $b,bool $short):string{
    $ts=$b*$t["size"]-wdhx_tz()+($t["unit"]=="h"?0:43200);
    if($t["unit"]=="h"){return date("H:00",$ts);}
    return $short?date("j",$ts):wdhx_day($tpl,$ts);
}
function wdhx_timeline_card($tpl,array $m):string{
    $t=$m["timeline"];
    if(!is_array($t)){return "";}
    $hourly=$t["unit"]=="h";
    $sub=$hourly?"{wdhx_tl_24h}":($m["period"]=="7d"?"{wdhx_tl_7d}":($m["period"]=="30d"?"{wdhx_tl_30d}":"{wdhx_tl_all}"));
    $h=array("<section class='wdh-card wdhx-tl'><div class='wdhx-tlh'><div><h3>{wdhx_tl_title}</h3><span>$sub</span></div>");
    if($m["day"]!==""){
        $d=wdhx_day($tpl,strtotime($m["day"]." 12:00:00"));
        $h[]="<button type='button' class='wdhx-daychip' OnClick=\"WdhDay('');\" aria-label=\"{wdhx_day_clear}\"><i class='fas fa-calendar-day' aria-hidden='true'></i>".wdh_h($tpl,"wdhx_day_on",array($d))." <i class='fas fa-xmark' aria-hidden='true'></i></button>";
    }
    $h[]="</div><div class='wdhx-bars".(count($t["buckets"])>40?" dense":"")."' role='list'>";
    $n=count($t["buckets"]);$i=0;
    foreach($t["buckets"] as $b=>$cnt){
        $pct=$t["max"]>0?max(3,round(100*$cnt/$t["max"])):0;
        $full=wdhx_bucket_label($tpl,$t,$b,false);
        $tip=$full." · ".wdh_t($tpl,$cnt==1?"wdhx_n_fail1":"wdhx_n_fail",array(number_format($cnt)));
        $cls="wdhx-bar".($cnt==0?" zero":"").($b==$t["busiest"]&&$cnt>0?" top":"");
        $dayIso=$hourly?"":date("Y-m-d",$b*86400-wdhx_tz()+43200);
        if(!$hourly && $dayIso===$m["day"]){$cls.=" sel";}
        $showLbl=$hourly?($i%3==0):($n<=10 || $i%($n>40?10:5)==0 || $i==$n-1);
        $lbl=$showLbl?"<small>".wdh_e($hourly?wdhx_bucket_label($tpl,$t,$b,true):($n<=10?$full:wdhx_bucket_label($tpl,$t,$b,true)))."</small>":"<small>&nbsp;</small>";
        $bar="<i style='height:".($cnt==0?2:$pct)."%'></i>";
        if($hourly || $cnt==0){
            $h[]="<div class='$cls' role='listitem' title=\"".wdh_e($tip)."\" aria-label=\"".wdh_e($tip)."\"><span class='c'>$bar</span>$lbl</div>";
        }else{
            $act=wdh_e($tip.". ".wdh_t($tpl,"wdhx_bar_filter"));
            $h[]="<div class='$cls' role='listitem'><button type='button' class='c' title=\"".wdh_e($tip)."\" aria-label=\"$act\" OnClick=\"WdhDay('$dayIso');\">$bar</button>$lbl</div>";
        }
        $i++;
    }
    $h[]="</div>";
    if($t["max"]>0){
        $bt=wdhx_bucket_label($tpl,$t,$t["busiest"],false);
        $txt=str_replace(array("\x01","\x02"),array("<b>".wdh_e($bt)."</b>","<b>".number_format($t["max"])."</b>"),wdh_h($tpl,$hourly?"wdhx_busiest_h":"wdhx_busiest_d",array("\x01","\x02")));
        $h[]="<p class='wdhx-tlf'><i class='fas fa-chart-column' aria-hidden='true'></i><span>$txt</span></p>";
    }
    $h[]="</section>";
    return @implode("",$h);
}

// 4. Tuiles.
function wdh_tile_count($tpl,array $m):string{
    $val=$m["total"]>0?number_format($m["total"]):"0";
    $w=$m["whole"];
    $sub=$w["oldest"]>0?wdh_h($tpl,"wdhx_t_of",array(number_format($w["n"]),wdhx_day($tpl,$w["oldest"]))):"&nbsp;";
    return "<div class='wdh-card wdh-tile wdhx-tile'><div class='wdh-tl'><span>{wdhx_t_count}</span><i class='fas fa-circle-exclamation' aria-hidden='true'></i></div>"
        ."<div class='wdhx-big'>$val</div><small class='wdhx-sub'>$sub</small></div>";
}
function wdh_tile_last($tpl,array $m):string{
    if(count($m["rows"])==0){
        return "<div class='wdh-card wdh-tile wdhx-tile'><div class='wdh-tl'><span>{wdhx_t_last}</span></div><div class='wdhx-big'>&mdash;</div><small class='wdhx-sub'>&nbsp;</small></div>";
    }
    $r=$m["rows"][0];
    $z=intval($r["zDate"]);
    $srv=wdh_server($tpl,(string)$r["dnsserver"]);
    $ago=wdh_e(dnswd_ago($tpl,max(0,time()-$z)));
    $sub=wdh_date($tpl,$z)." · ".wdh_e($srv["label"]);
    $recent=(time()-$z)<3600?" recent":"";
    return "<div class='wdh-card wdh-tile wdhx-tile'><div class='wdh-tl'><span>{wdhx_t_last}</span><span class='wdh-dot$recent' aria-hidden='true'></span></div>"
        ."<div class='wdhx-big'>$ago</div><small class='wdhx-sub' title=\"$sub\">$sub</small></div>";
}
// Les 5 serveurs les plus touches, barres mesurees contre le premier ; un clic cherche ce serveur.
function wdh_tile_top($tpl,array $m):string{
    if(count($m["top"])==0 || $m["total"]==0){
        return "<div class='wdh-card wdh-tile wdhx-tile'><div class='wdh-tl'><span>{wdh_t_top}</span></div><div class='wdhx-big'>&mdash;</div><small class='wdhx-sub'>&nbsp;</small></div>";
    }
    $first=$m["top"][0];
    $pct=intval(round(100*$first["n"]/$m["total"]))." %";
    $head=wdh_h($tpl,"wdhx_t_top_n",array(number_format($first["n"]),$pct));
    $max=max(1,$first["n"]);
    $h=array("<div class='wdh-card wdh-tile wdhx-tile'><div class='wdh-tl'><span>{wdh_t_top}</span><b class='wdhx-toph'>$head</b></div><ul class='wdhx-top'>");
    foreach($m["top"] as $t){
        $s=wdh_server($tpl,$t["server"]);
        $name=$s["label"].($s["kind"]=="up" && $s["tech"]!=="" && strpos($s["tech"],"/")===false?" (".$s["tech"].")":"");
        $w=max(2,round(100*$t["n"]/$max));
        $q=$s["tech"]!==""?(strpos($s["tech"],"/")!==false?substr($s["tech"],0,strpos($s["tech"],"/")):$s["tech"]):"";
        $aria=wdh_e(wdh_t($tpl,"wdhx_top_aria",array($name,number_format($t["n"]))));
        $h[]="<li><button type='button' data-q=\"".wdh_e($q)."\" aria-label=\"$aria\" OnClick=\"WdhSearchFor(this.getAttribute('data-q'));\">"
            ."<span class='l'><span title=\"".wdh_e($name)."\">".wdh_e($name)."</span><b>".number_format($t["n"])."</b></span>"
            ."<span class='t'><i style='width:$w%'></i></span></button></li>";
    }
    $h[]="</ul></div>";
    return @implode("",$h);
}
function wdh_tiles($tpl,array $m):string{
    return "<section class='wdh-tiles' aria-label=\"{wdh_t_aria}\">".wdh_tile_count($tpl,$m).wdh_tile_last($tpl,$m).wdh_tile_top($tpl,$m)."</section>";
}

// 5. Une ligne d'echec, et sa ligne de detail (cachee). $item = numero de l'element
// de pagination (un echec isole ou une panne regroupee), $grp = id du groupe ou "".
function wdhx_reason_tone(string $error):string{
    $e=strtolower($error);
    if(strpos($e,"timeout")!==false || strpos($e,"timed out")!==false || strpos($e,"deadline exceeded")!==false){return "warn";}
    if(strpos($e,"no such host")!==false){return "neutral";}
    return "err";
}
function wdh_row($tpl,array $r,int $gap,int $burstGap,int $item,string $grp,int $idx):string{
    $z=intval($r["zDate"]);
    $srv=wdh_server($tpl,(string)$r["dnsserver"]);
    list($ico,$reason)=wdh_reason($tpl,(string)$r["error"]);
    $tone=wdhx_reason_tone((string)$r["error"]);
    $site=trim((string)$r["queryserv"]);
    $err=trim((string)$r["error"]);
    $did="wdhx-d-$idx";
    $g=$grp!==""?" data-g='$grp'":"";
    $hid=$grp!==""?" hidden":"";
    $h=array();
    $h[]="<tr class='wdhx-row".($grp!==""?" child":"")."' data-i='$item'$g$hid role='button' tabindex='0' aria-expanded='false' aria-controls='$did'>";
    $h[]="<td class='wdh-when'><div class='d' title='".date("Y-m-d H:i:s",$z)."'><b>".date("H:i",$z)."</b> <span>".wdh_e(wdhx_day($tpl,$z))."</span></div>";
    $h[]="<div class='s'><span>".wdh_e(dnswd_ago($tpl,max(0,time()-$z)))."</span>";
    if($gap>=0){
        $cls=$gap<=$burstGap?"wdh-gap close":"wdh-gap";
        $h[]="<span class='$cls' title=\"{wdh_gap_help}\">+".wdh_e(wdh_dur($tpl,$gap))."</span>";
    }
    $h[]="</div></td>";
    $h[]="<td class='wdh-srv'><div class='n'><i class='fas {$srv["icon"]}' aria-hidden='true'></i><span>".wdh_e($srv["label"])."</span></div>";
    if($srv["tech"]!==""){$h[]="<div class='wdh-mono sub'>".wdh_e($srv["tech"])."</div>";}
    $h[]="</td>";
    $h[]="<td class='wdh-mono wdh-site'>".($site!==""?wdh_e($site):"<span class='wdh-none'>&mdash;</span>")."</td>";
    $h[]="<td class='wdh-what'><div class='wdhx-rs'><span class='wdhx-pill $tone'><i class='fas $ico' aria-hidden='true'></i>".wdh_e($reason)."</span>";
    foreach($srv["tags"] as $t){
        $title=$t[2]!==""?" title=\"".wdh_e($t[2])."\"":"";
        $h[]="<span class='wdh-tag {$t[0]}'$title>".wdh_e($t[1])."</span>";
    }
    $h[]="</div></td><td class='wdhx-chev' aria-hidden='true'><i class='fas fa-chevron-down'></i></td></tr>";
    $h[]="<tr class='wdhx-det' id='$did' data-i='$item'$g hidden><td colspan='5'><div class='wdhx-dt'><i class='fas fa-code' aria-hidden='true'></i>{wdhx_raw}</div>"
        ."<pre class='wdhx-raw'>".($err!==""?wdh_e($err):"&mdash;")."</pre>"
        ."<a class='wdhx-link' href='javascript:blur();' OnClick=\"WdhEvents();\"><i class='fas fa-list' aria-hidden='true'></i>{wdhx_see_events}</a></td></tr>";
    return @implode("",$h);
}

// Les lignes. Des echecs consecutifs a moins de wdh_burst_gap() forment une panne
// regroupee : une ligne d'en-tete repliable (repliee par defaut) puis ses echecs.
function wdh_rows($tpl,array $m):string{
    $rows=$m["rows"];
    $n=count($rows);
    $burstGap=wdh_burst_gap($m["interval"]);
    $z=array();
    foreach($rows as $r){$z[]=intval($r["zDate"]);}
    $out=array();
    $i=0;$item=0;
    while($i<$n){
        $j=$i;
        // Plafond de 50 echecs par groupe : une panne continue de plusieurs jours reste paginable.
        while($j+1<$n && ($z[$j]-$z[$j+1])<=$burstGap && ($j-$i+1)<50){$j++;}
        $size=$j-$i+1;
        $grp="";
        if($size>1){
            $grp="g$item";
            $srvs=array();
            for($k=$i;$k<=$j;$k++){$srvs[(string)$rows[$k]["dnsserver"]]=true;}
            $ns=count($srvs);
            $txt=wdh_h($tpl,"wdhx_burst",array($size,wdh_dur($tpl,$z[$i]-$z[$j])));
            $sv=wdh_h($tpl,$ns==1?"wdhx_g_srv1":"wdhx_g_srv",array($ns));
            $span=date("H:i",$z[$j])." &rarr; ".date("H:i",$z[$i])." · ".wdh_e(wdhx_day($tpl,$z[$i]));
            $help=wdh_h($tpl,"wdh_burst_help",array(wdh_dur($tpl,$burstGap)));
            $out[]="<tr class='wdhx-grp' data-i='$item' data-n='$size' data-gid='$grp' role='button' tabindex='0' aria-expanded='false' title=\"$help\">"
                ."<td colspan='4'><span class='wdhx-gt'><i class='fas fa-bolt' aria-hidden='true'></i><b>$txt</b><span>· $sv</span><span class='wdh-mono'>$span</span></span></td>"
                ."<td class='wdhx-chev' aria-hidden='true'><i class='fas fa-chevron-down'></i></td></tr>";
        }
        for($k=$i;$k<=$j;$k++){
            $gap=$k+1<$n?$z[$k]-$z[$k+1]:-1;
            $out[]=wdh_row($tpl,$rows[$k],$gap,$burstGap,$item,$grp,$k);
        }
        $item++;
        $i=$j+1;
    }
    return @implode("\n",$out);
}

// 6. Etats vides.
function wdh_empty($tpl,array $m):string{
    if($m["search"]!==""){
        return "<div class='wdh-empty'><div class='ic'><i class='fas fa-magnifying-glass' aria-hidden='true'></i></div><h4>".wdh_h($tpl,"wdhx_e_search_t",array($m["search"]))."</h4><p>{wdh_e_search}</p>"
            ."<button type='button' class='wdh-btn' OnClick=\"WdhClear();\">{wdh_clear}</button></div>";
    }
    if($m["whole"]["n"]==0){
        if($m["enabled"]==0){
            return "<div class='wdh-empty'><div class='ic'><i class='fas fa-power-off' aria-hidden='true'></i></div><h4>{wdh_e_off_t}</h4><p>{wdh_e_off}</p>"
                ."<button type='button' class='wdh-btn primary' OnClick=\"WdhSettings();\">{dnswd_turn_on}</button></div>";
        }
        return "<div class='wdh-empty ok'><div class='ic'><i class='fas fa-circle-check' aria-hidden='true'></i></div><h4>{wdhx_e_none_t}</h4><p>{wdhx_e_none}</p></div>";
    }
    if($m["day"]!==""){
        return "<div class='wdh-empty ok'><div class='ic'><i class='fas fa-calendar-check' aria-hidden='true'></i></div><h4>{wdhx_e_day_t}</h4><p>{wdhx_e_filter}</p>"
            ."<button type='button' class='wdh-btn' OnClick=\"WdhDay('');\">{wdhx_day_clear}</button></div>";
    }
    if($m["period"]!="all"){
        $t=$m["period"]=="24h"?"{wdhx_e_24h_t}":($m["period"]=="7d"?"{wdhx_e_7d_t}":"{wdhx_e_30d_t}");
        $next=$m["period"]=="24h"?"30d":"all";
        $btn=$next=="30d"?"{wdhx_show_30d}":"{wdhx_show_all}";
        return "<div class='wdh-empty ok'><div class='ic'><i class='fas fa-circle-check' aria-hidden='true'></i></div><h4>$t</h4><p>{wdhx_e_filter}</p>"
            ."<button type='button' class='wdh-btn' OnClick=\"WdhPeriod('$next');\">$btn</button></div>";
    }
    return "<div class='wdh-empty'><div class='ic'><i class='fas fa-filter' aria-hidden='true'></i></div><h4>{wdhx_e_filter_t}</h4><p>{wdhx_e_filter}</p>"
        ."<button type='button' class='wdh-btn' OnClick=\"WdhScope('all');\">{wdhx_e_clear}</button></div>";
}

function wdh_table($tpl,array $m):string{
    $h=array();
    $h[]="<section class='wdh-card wdh-tcard'>";
    if(count($m["rows"])==0){
        $h[]=wdh_empty($tpl,$m);
        $h[]="</section>";
        return @implode("",$h);
    }
    $h[]="<div class='wdhx-th'><h3>{wdhx_log_title}</h3><span>{wdhx_log_sub}</span></div>";
    $h[]="<div class='wdh-scroll'><table class='wdh-tbl wdhx-tbl'><thead><tr>";
    $h[]="<th scope='col'>{wdh_c_when}</th>";
    $h[]="<th scope='col'><span title=\"{wdh_c_server_help}\">{wdh_c_server} <i class='fas fa-circle-info' aria-hidden='true'></i></span></th>";
    $h[]="<th scope='col'>{wdh_c_site}</th>";
    $h[]="<th scope='col'>{wdh_c_what}</th>";
    $h[]="<th scope='col'><span class='wdhx-sr'>{wdhx_details}</span></th>";
    $h[]="</tr></thead><tbody id='wdh-rows'>";
    $h[]=wdh_rows($tpl,$m);
    $h[]="</tbody></table></div>";
    $h[]=wdh_footer($tpl,$m);
    $h[]="</section>";
    return @implode("",$h);
}
function wdh_footer($tpl,array $m):string{
    $cap="";
    if($m["total"]>count($m["rows"])){
        $cap="<div class='wdh-cap'><i class='fas fa-circle-info' aria-hidden='true'></i>".wdh_h($tpl,"wdhx_cap_note",array(wdh_max_rows()))."</div>";
    }
    return "<footer class='wdh-foot'><div><span id='wdh-showing'></span>$cap</div><nav class='wdh-pg' id='wdh-pager' aria-label=\"{wdh_pager_label}\"></nav></footer>";
}

function wdh_error($tpl,string $err):string{
    return "<section class='wdh-card wdh-tcard'><div class='wdh-empty err'><div class='ic'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i></div>"
        ."<h4>".wdh_h($tpl,"wdh_db_error",array($err))."</h4></div></section>";
}

// --- JavaScript (hors du parseur : ses accolades seraient prises pour des tokens) ---
function wdh_js($tpl,string $page):string{
    $tShowing=json_encode(wdh_t($tpl,"wdhx_showing",array("%s","%s","%s")));
    $tPrev=json_encode(wdh_t($tpl,"wdh_prev"));
    $tNext=json_encode(wdh_t($tpl,"wdh_next"));
    return "<script>
window.WdhState=window.WdhState||{};
WdhState.scope=WdhState.scope||'all';WdhState.period=WdhState.period||'7d';WdhState.day=WdhState.day||'';WdhState.open=WdhState.open||{};
function WdhUrl(){var q=document.getElementById('wdh-q');var s=q?q.value:'';
 return '$page?wdh-host=yes&scope='+encodeURIComponent(WdhState.scope)+'&period='+encodeURIComponent(WdhState.period)+'&day='+encodeURIComponent(WdhState.day)+'&search='+encodeURIComponent(s);}
function WdhReload(){if(!document.getElementById('wdh-host')){return;}LoadAjaxSilent('wdh-host',WdhUrl());}
function WdhSettings(){Loadjs('$page?watch-js=yes');}
function WdhRadio(sel,attr,val,base){var b=document.querySelectorAll(sel);
 for(var i=0;i<b.length;i++){var on=b[i].getAttribute(attr)===val;b[i].className=base+(on?' on':'');b[i].setAttribute('aria-checked',on?'true':'false');}}
function WdhScope(s){WdhState.scope=s;WdhRadio('.wdhx-sbtn','data-scope',s,'wdh-sbtn wdhx-sbtn');WdhReload();}
function WdhPeriod(p){WdhState.period=p;WdhState.day='';WdhRadio('.wdhx-pbtn','data-p',p,'wdh-sbtn wdhx-pbtn');WdhReload();}
function WdhDay(d){WdhState.day=d;WdhReload();}
function WdhClear(){var q=document.getElementById('wdh-q');if(q){q.value='';}WdhReload();if(q){q.focus();}}
function WdhExample(t){var q=document.getElementById('wdh-q');if(q){q.value=t;}WdhReload();}
function WdhSearchFor(t){if(!t){return;}WdhExample(t);}
function WdhEvents(){var h=document.getElementById('wdh-host');var c=h?h.closest('.tabs-container'):null;
 var a=c?c.querySelectorAll('ul.nav-tabs>li>a'):[];if(a.length>2){a[2].click();}}
function WdhCounts(c){for(var k in c.counts){var e=document.getElementById('wdh-count-'+k);if(e){e.textContent=c.counts[k];}}}
function WdhFmt(t,a){var i=0;return t.replace(/%s/g,function(){return (i<a.length)?a[i++]:'';});}
function WdhItems(){var tb=document.getElementById('wdh-rows');if(!tb){return [];}var items=[],cur=null,rows=tb.rows;
 for(var i=0;i<rows.length;i++){var k=rows[i].getAttribute('data-i');if(!cur||cur.k!==k){cur={k:k,rows:[],n:0};items.push(cur);}cur.rows.push(rows[i]);
  if(rows[i].className.indexOf('wdhx-row')>=0){cur.n++;}}
 return items;}
function WdhShowItem(it,vis){for(var j=0;j<it.rows.length;j++){var r=it.rows[j],c=r.className;
 if(!vis){r.style.display='none';continue;}r.style.display='';
 if(c.indexOf('wdhx-det')>=0){var row=r.previousElementSibling;r.hidden=!(row&&row.getAttribute('aria-expanded')==='true'&&!row.hidden);}
 else if(c.indexOf('child')>=0){var g=r.getAttribute('data-g');r.hidden=!WdhState.open[g];}}}
function WdhPager(size){var items=WdhItems(),n=items.length,pages=Math.max(1,Math.ceil(n/size)),box=document.getElementById('wdh-pager'),sh=document.getElementById('wdh-showing');
 var total=0;for(var i=0;i<n;i++){total+=items[i].n;}
 function draw(cur){var a=0,b=0,seen=0;for(var i=0;i<n;i++){var vis=(i>=(cur-1)*size&&i<cur*size);WdhShowItem(items[i],vis);
   if(vis){if(!a){a=seen+1;}b=seen+items[i].n;}seen+=items[i].n;}
  if(sh){sh.textContent=WdhFmt($tShowing,[String(a),String(b),String(total)]);}
  if(!box){return;} box.innerHTML=''; if(pages<2){return;}
  function btn(label,p,dis,cur2){var x=document.createElement('button');x.type='button';x.textContent=label;
   if(dis){x.disabled=true;} if(cur2){x.className='on';x.setAttribute('aria-current','page');}
   x.onclick=function(){draw(p);};box.appendChild(x);}
  btn($tPrev,cur-1,cur==1,false);
  var end=Math.min(pages,cur+2),start=Math.max(1,end-4);end=Math.min(pages,start+4);
  for(var p=start;p<=end;p++){btn(String(p),p,false,p==cur);}
  btn($tNext,cur+1,cur==pages,false);}
 draw(1);}
function WdhToggleGroup(tr){var g=tr.getAttribute('data-gid'),open=tr.getAttribute('aria-expanded')!=='true';tr.setAttribute('aria-expanded',open?'true':'false');
 if(open){WdhState.open[g]=1;}else{delete WdhState.open[g];}
 var tb=document.getElementById('wdh-rows'),rows=tb?tb.querySelectorAll('tr[data-g=\"'+g+'\"]'):[];
 for(var i=0;i<rows.length;i++){var r=rows[i];if(r.className.indexOf('wdhx-det')>=0){var p=r.previousElementSibling;r.hidden=!open||!(p&&p.getAttribute('aria-expanded')==='true');}else{r.hidden=!open;}}}
function WdhToggleRow(tr){var d=document.getElementById(tr.getAttribute('aria-controls'));var open=tr.getAttribute('aria-expanded')!=='true';
 tr.setAttribute('aria-expanded',open?'true':'false');if(d){d.hidden=!open;}}
function WdhAfter(){var tb=document.getElementById('wdh-rows');if(!tb){return;}
 var g=tb.querySelectorAll('tr.wdhx-grp');for(var i=0;i<g.length;i++){if(WdhState.open[g[i].getAttribute('data-gid')]){g[i].setAttribute('aria-expanded','true');}}
 WdhPager(50);
 if(!tb.__wdh){tb.__wdh=1;
  tb.addEventListener('click',function(e){var tr=e.target.closest?e.target.closest('tr'):null;if(!tr||e.target.closest('a,button')){return;}
   if(tr.className.indexOf('wdhx-grp')>=0){WdhToggleGroup(tr);}else if(tr.className.indexOf('wdhx-row')>=0){WdhToggleRow(tr);}});
  tb.addEventListener('keydown',function(e){if(e.keyCode!=13&&e.keyCode!=32){return;}var tr=e.target;if(!tr||tr.tagName!=='TR'){return;}e.preventDefault();
   if(tr.className.indexOf('wdhx-grp')>=0){WdhToggleGroup(tr);}else if(tr.className.indexOf('wdhx-row')>=0){WdhToggleRow(tr);}});}}
(function(){var q=document.getElementById('wdh-q');if(!q||q.__wdh){return;}q.__wdh=1;
 q.addEventListener('input',function(){clearTimeout(window.WdhTimer);window.WdhTimer=setTimeout(WdhReload,600);});
 q.addEventListener('keydown',function(e){if(e.keyCode==13){e.preventDefault();clearTimeout(window.WdhTimer);WdhReload();}});})();
</script>";
}

/**
 * L'onglet History : bandeau, filtres, hote rechargeable. Le TINY est pose ici,
 * une fois : il retire du titre les filtres de periode de l'onglet Status.
 */
function history_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $p=wdh_params();
    $m=dnswd_settings();
    $w=wdhx_whole(wdhx_db());
    $html=array();
    $html[]="<div class='wdh wdhx'>";
    $html[]=wdh_banner($tpl,$m,$w["oldest"]);
    $html[]=wdh_filters($tpl,$p);
    $html[]="<div id='wdh-host'></div>";
    $html[]="</div>";

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{watchdog}";
    $TINY_ARRAY["ICO"]=ico_watchdog;
    $TINY_ARRAY["EXPL"]="{dns_watchdog_howto}";
    $TINY_ARRAY["URL"]="dns-watchdog";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo wdh_style().wdhx_style().$tpl->_ENGINE_parse_body(@implode("",$html))
        .wdh_js($tpl,$page)
        ."<script>NoSpinner();WdhState.scope='{$p["scope"]}';WdhState.period='{$p["period"]}';WdhState.day='';WdhReload();$jstiny</script>";
    return true;
}

// Repondeur ?wdh-host=yes&scope=&period=&day=&search= : frise, tuiles et liste.
function wdh_host():bool{
    $tpl=new template_admin();
    $p=wdh_params();
    $m=wdh_model($p);
    if($m["error"]!==""){
        $c=array("counts"=>array("all"=>"—","up"=>"—","local"=>"—"));
        echo $tpl->_ENGINE_parse_body(wdh_error($tpl,$m["error"]))
            ."<script>NoSpinner();WdhCounts(".json_encode($c).");</script>";
        return false;
    }
    $html=wdhx_timeline_card($tpl,$m).wdh_tiles($tpl,$m).wdh_table($tpl,$m);
    $c=array("counts"=>array("all"=>number_format($m["count_all"]),"up"=>number_format($m["count_up"]),"local"=>number_format($m["count_local"])));
    echo $tpl->_ENGINE_parse_body($html)
        ."<script>NoSpinner();WdhCounts(".json_encode($c).");WdhAfter();</script>";
    return true;
}

function wdhx_style():string{
    if(!empty($GLOBALS["WDHX_CSS"])){return "";}
    $GLOBALS["WDHX_CSS"]=true;
    return "<style>
.wdhx .wdh-scount,.wdhx .wdh-help,.wdhx .wdh-tv>small,.wdhx .wdh-mono,.wdhx .wdh-gap,.wdhx .wdh-tag,.wdhx .wdh-cap,.wdhx .wdh-when .s{font-size:13px}
.wdhx .wdh-tbl th{font-size:13px}
.wdh-btn.ghost{border-color:transparent;color:#005447}
.wdh-btn.ghost:hover{background:#e6eeed;border-color:#bfd4d1}
.wdhx-filters{flex-direction:column;align-items:stretch;gap:10px;padding:12px 14px}
.wdhx-frow{display:flex;flex-wrap:wrap;gap:12px 16px;align-items:center}
.wdhx-ex{display:flex;gap:6px;align-items:center;font-size:13px;color:#6b7280}
.wdhx-ex button{border:1px solid #e5e7eb;background:#f9fafb;border-radius:6px;padding:2px 8px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#1f2937;cursor:pointer}
.wdhx-ex button:hover{background:#e6eeed;border-color:#bfd4d1;color:#005447}
.wdhx-ex button:focus-visible,.wdhx-bar button:focus-visible,.wdhx-top button:focus-visible,.wdhx-daychip:focus-visible,.wdhx-tbl tr[tabindex]:focus-visible,.wdhx-link:focus-visible{outline:2px solid #005447;outline-offset:2px}
.wdhx-tl{padding:18px 22px;margin-bottom:16px}
.wdhx-tlh{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:12px}
.wdhx-tlh h3{display:inline;margin:0 10px 0 0;font-size:16px;font-weight:600;color:#0f172a}
.wdhx-tlh span{font-size:13px;color:#6b7280}
.wdhx-daychip{display:inline-flex;align-items:center;gap:8px;border:1px solid #bfd4d1;background:#e6eeed;color:#005447;border-radius:999px;padding:4px 12px;font-size:13px;font-weight:600;cursor:pointer}
.wdhx-bars{display:flex;align-items:stretch;gap:6px;height:150px}
.wdhx-bars.dense{gap:2px}
.wdhx-bar{flex:1 1 0;min-width:0;display:flex;flex-direction:column;align-items:center}
.wdhx-bar .c{flex:1 1 auto;width:100%;display:flex;align-items:flex-end;justify-content:center;border:0;background:transparent;padding:0;cursor:default;border-bottom:1px solid #e5e7eb}
.wdhx-bar button.c{cursor:pointer}
.wdhx-bar .c i{display:block;width:70%;max-width:44px;background:#3f6f68;border-radius:3px 3px 0 0;min-height:0}
.wdhx-bars.dense .wdhx-bar .c i{width:100%}
.wdhx-bar.zero .c i{background:#e5e7eb}
.wdhx-bar.top .c i{background:#005447}
.wdhx-bar.sel .c i{background:#003d33;box-shadow:0 0 0 2px #bfd4d1}
.wdhx-bar button.c:hover i{background:#003d33}
.wdhx-bar small{margin-top:6px;font-size:13px;color:#6b7280;white-space:nowrap;max-width:100%;overflow:hidden;text-overflow:ellipsis;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.wdhx-tlf{display:flex;gap:8px;align-items:center;margin:12px 0 0 0;padding-top:10px;border-top:1px solid #f1f5f9;font-size:13px;color:#4b5563}
.wdhx-tlf b{color:#0f172a}
.wdhx-tile{min-height:150px}
.wdhx-big{font-size:32px;font-weight:700;line-height:1.1;color:#0f172a;margin:10px 0 6px 0;white-space:nowrap}
.wdhx-sub{font-size:13px;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.wdhx-toph{font-size:13px;color:#0f172a;font-weight:600;white-space:nowrap}
.wdhx-top{list-style:none;margin:8px 0 0 0;padding:0;display:flex;flex-direction:column;gap:4px}
.wdhx-top button{width:100%;border:0;background:transparent;padding:3px 4px;border-radius:6px;cursor:pointer;text-align:left}
.wdhx-top button:hover{background:#f2f6f6}
.wdhx-top .l{display:flex;justify-content:space-between;gap:8px;font-size:13px;color:#1f2937}
.wdhx-top .l span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.wdhx-top .t{display:block;height:5px;border-radius:3px;background:#f1f5f9;margin-top:3px;overflow:hidden}
.wdhx-top .t i{display:block;height:100%;background:#3f6f68;border-radius:3px}
.wdhx-top li:first-child .t i{background:#005447}
.wdhx-th{display:flex;align-items:baseline;gap:10px;padding:14px 16px 4px 16px}
.wdhx-th h3{margin:0;font-size:16px;font-weight:600;color:#0f172a}
.wdhx-th span{font-size:13px;color:#6b7280}
.wdhx-sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}
.wdhx-tbl .wdh-when .d b{font-size:14px;color:#0f172a}
.wdhx-tbl .wdh-when .d span{font-size:13px;color:#6b7280;font-weight:400}
.wdhx-tbl tr.wdhx-row{cursor:pointer}
.wdhx-tbl tr.child td:first-child{box-shadow:inset 3px 0 0 #f59e0b;padding-left:28px}
.wdhx-tbl tr.wdhx-grp td{background:#fffbeb !important;cursor:pointer;border-bottom:1px solid #fde68a}
.wdhx-gt{display:flex;flex-wrap:wrap;gap:8px;align-items:center;font-size:14px;color:#92400e}
.wdhx-gt b{font-weight:600}
.wdhx-gt .wdh-mono{background:#fff;border:1px solid #fde68a;border-radius:6px;padding:1px 8px;color:#78350f}
.wdhx-chev{width:28px;color:#94a3b8;text-align:center}
.wdhx-chev svg,.wdhx-chev i{transition:transform .15s}
.wdhx-tbl tr[aria-expanded=true] .wdhx-chev svg,.wdhx-tbl tr[aria-expanded=true] .wdhx-chev i{transform:rotate(180deg)}
.wdhx-rs{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.wdhx-pill{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap}
.wdhx-pill.err{background:#fef2f2;color:#b91c1c}.wdhx-pill.warn{background:#fffbeb;color:#92400e}.wdhx-pill.neutral{background:#f3f4f6;color:#374151}
.wdhx-tbl tr.wdhx-det td{background:#f8fafc !important;padding:4px 16px 14px 44px;border-top:1px dashed #e2e8f0}
.wdhx-dt{display:flex;gap:6px;align-items:center;margin:8px 0 6px 0;font-size:13px;font-weight:600;color:#475569}
.wdhx-raw{margin:0 0 8px 0;white-space:pre-wrap;word-break:break-all;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;color:#1e293b;background:#fff;border:1px solid #e2e8f0;border-radius:6px;padding:8px 10px}
.wdhx-link{display:inline-flex;gap:6px;align-items:center;font-size:13px;font-weight:600;color:#005447;text-decoration:none}
.wdhx-link:hover{color:#003d33;text-decoration:underline}
@media (max-width:1200px){.wdhx .wdh-tiles{grid-template-columns:minmax(0,1fr)}}
@media (prefers-reduced-motion:reduce){.wdhx-chev svg,.wdhx-chev i{transition:none}}
</style>";
}

function wdh_style():string{
    if(!empty($GLOBALS["WDH_CSS"])){return "";}
    $GLOBALS["WDH_CSS"]=true;
    return "<style>
.wdh{margin:10px 0 20px 0;color:#0f172a;box-sizing:border-box}
.wdh *{box-sizing:border-box}
.wdh-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.wdh-banner{display:flex;gap:20px;align-items:flex-start;padding:22px 24px;border-left:4px solid #005447;margin-bottom:16px}
.wdh-bico{flex:0 0 40px;height:40px;border-radius:50%;background:#e6eeed;color:#005447;display:flex;align-items:center;justify-content:center;font-size:17px}
.wdh-btxt{flex:1 1 auto;min-width:0}
.wdh-btxt h2{margin:0 0 6px 0;font-size:20px;font-weight:600;line-height:1.3;color:#0f172a}
.wdh-btxt p{margin:0;font-size:14px;color:#475569;line-height:1.55;max-width:880px}
.wdh-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.wdh-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#f2f6f6;color:#005447;font-size:13px;border:1px solid #e6eeed}
.wdh-notice{display:flex;gap:10px;align-items:flex-start;margin-top:12px;padding:9px 14px;border-radius:8px;font-size:13px;line-height:1.5;background:#f1f5f9;color:#334155}
.wdh-notice.warn{background:#fef3c7;color:#92400e}
.wdh-bact{flex:0 0 auto;display:flex;gap:8px;align-items:center}
.wdh-btn{display:inline-flex;align-items:center;gap:8px;height:36px;padding:0 14px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;line-height:1}
.wdh-btn:hover{background:#f8fafc;border-color:#94a3b8}
.wdh-btn.primary{background:#005447;border-color:#005447;color:#fff}
.wdh-btn.primary:hover{background:#003d33;border-color:#003d33}
.wdh-btn.icon{width:36px;padding:0;justify-content:center}
.wdh-btn:focus-visible,.wdh-sbtn:focus-visible,.wdh-clear:focus-visible,.wdh-pg button:focus-visible{outline:2px solid #005447;outline-offset:2px}
.wdh-filters{display:flex;align-items:center;gap:16px;padding:10px 12px;margin-bottom:6px}
.wdh-seg{display:inline-flex;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:9px;padding:3px;flex:0 0 auto}
.wdh-sbtn{border:0;background:transparent;color:#475569;font-size:13px;font-weight:500;padding:6px 14px;border-radius:7px;cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;line-height:1.2}
.wdh-sbtn.on{background:#005447;color:#fff;font-weight:600}
.wdh-scount{font-size:12px;padding:1px 7px;border-radius:999px;background:#e2e8f0;color:#334155;font-weight:600}
.wdh-sbtn.on .wdh-scount{background:rgba(255,255,255,.22);color:#fff}
.wdh-search{position:relative;flex:1 1 auto;min-width:220px;max-width:560px}
.wdh-search>i,.wdh-search>svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;pointer-events:none}
.wdh-search input{width:100%;height:36px;border:1px solid #cbd5e1;border-radius:8px;padding:0 36px 0 34px;font-size:13px;color:#0f172a;background:#fff}
.wdh-search input:focus{outline:none;border-color:#005447;box-shadow:0 0 0 2px #bfd4d1}
.wdh-search input::-webkit-search-cancel-button{display:none}
.wdh-clear{position:absolute;right:4px;top:4px;width:28px;height:28px;border:0;background:transparent;color:#64748b;border-radius:6px;cursor:pointer}
.wdh-clear:hover{background:#f1f5f9}
.wdh-counter{margin-left:auto;font-size:13px;color:#475569;white-space:nowrap}
.wdh-help{display:flex;gap:8px;align-items:center;font-size:12.5px;color:#64748b;margin:0 4px 16px 4px}
.wdh-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:16px}
.wdh-tile{padding:16px 20px;min-height:104px;display:flex;flex-direction:column;justify-content:space-between;min-width:0}
.wdh-tl{display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:13px;color:#475569;font-weight:500}
.wdh-tl i{color:#94a3b8}
.wdh-tv{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-top:8px;min-width:0}
.wdh-tv>b{font-size:28px;font-weight:700;line-height:1.1;color:#0f172a;white-space:nowrap}
.wdh-tv>small{font-size:12.5px;color:#64748b;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.wdh-tv.top{align-items:center}
.wdh-topname{min-width:0;display:flex;flex-direction:column;gap:3px}
.wdh-topname .name{font-size:15px;font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.wdh-topname small{font-size:12.5px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wdh-bars{flex:0 0 auto}
.wdh-dot{width:9px;height:9px;border-radius:50%;background:#cbd5e1}
.wdh-dot.recent{background:#b91c1c}
.wdh-tcard{overflow:hidden}
.wdh-scroll{overflow-x:auto}
.wdh-tbl{width:100%;border-collapse:collapse;font-size:13px}
.wdh-tbl th{text-align:left;font-size:12px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:#475569;padding:12px 16px;border-bottom:1px solid #e2e8f0;background:#fff;white-space:nowrap}
.wdh-tbl th i{color:#94a3b8;font-size:11px}
.wdh-tbl td{padding:10px 16px;border-bottom:1px solid #f1f5f9;vertical-align:top}
.wdh-tbl tbody tr:nth-child(even) td{background:#fafbfc}
.wdh-tbl tbody tr:hover td{background:#f2f6f6}
.wdh-tbl tr.burst td:first-child{box-shadow:inset 3px 0 0 #f59e0b}
.wdh-when{width:1%;white-space:nowrap}
.wdh-when .d{color:#0f172a;font-weight:500}
.wdh-when .s{display:flex;gap:8px;align-items:center;margin-top:3px;font-size:12.5px;color:#64748b}
.wdh-gap{font-size:12px;padding:1px 7px;border-radius:999px;background:#f1f5f9;color:#475569;font-weight:600;white-space:nowrap}
.wdh-gap.close{background:#fef3c7;color:#b45309}
.wdh-burst{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#b45309;margin-bottom:4px;white-space:nowrap}
.wdh-srv{width:1%;min-width:230px}
.wdh-srv .n{display:flex;gap:4px 8px;align-items:center;flex-wrap:wrap}
.wdh-srv .n i{color:#64748b;width:14px;text-align:center}
.wdh-srv .n>span:not(.wdh-tag){font-weight:500;color:#0f172a;white-space:nowrap}
.wdh-tag{display:inline-block;font-size:12px;padding:1px 8px;border-radius:999px;font-weight:600;white-space:nowrap;cursor:default}
.wdh-tag.amber{background:#fef3c7;color:#92400e}
.wdh-tag.grey{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0}
.wdh-mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12.5px}
.wdh-mono.sub{color:#64748b;margin-top:3px}
.wdh-srv .sub{padding-left:22px}
.wdh-site{width:1%;white-space:nowrap;color:#0f172a}
.wdh-what .r{display:flex;gap:7px;align-items:center;color:#b91c1c;font-weight:500}
.wdh-what{width:100%;max-width:0}
.wdh-what .raw{max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.wdh-none{color:#94a3b8}
.wdh-foot{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:12px 16px;font-size:13px;color:#475569;border-top:1px solid #e2e8f0}
.wdh-cap{display:flex;gap:6px;align-items:center;margin-top:4px;font-size:12.5px;color:#64748b}
.wdh-pg{display:flex;gap:6px}
.wdh-pg button{min-width:34px;height:32px;padding:0 10px;border:1px solid #e2e8f0;background:#fff;border-radius:8px;font-size:13px;color:#0f172a;cursor:pointer}
.wdh-pg button.on{background:#005447;border-color:#005447;color:#fff;font-weight:600}
.wdh-pg button:disabled{color:#94a3b8;cursor:default;background:#f8fafc}
.wdh-empty{padding:48px 24px;text-align:center;max-width:560px;margin:0 auto}
.wdh-empty .ic{width:56px;height:56px;border-radius:50%;background:#f1f5f9;color:#64748b;display:flex;align-items:center;justify-content:center;font-size:24px;margin:0 auto 14px auto}
.wdh-empty.ok .ic{background:#dcfce7;color:#15803d}
.wdh-empty.err .ic{background:#fee2e2;color:#b91c1c}
.wdh-empty h4{margin:0 0 6px 0;font-size:16px;font-weight:600;color:#0f172a;word-break:break-word}
.wdh-empty p{margin:0 0 16px 0;font-size:13.5px;color:#475569;line-height:1.55}
@media (max-width:1200px){.wdh-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}.wdh-what .raw{display:none}.wdh-filters{flex-wrap:wrap}.wdh-counter{margin-left:0}}
@media (prefers-reduced-motion:no-preference){.wdh-tbl tbody tr td,.wdh-sbtn,.wdh-btn{transition:background-color .15s ease}}
</style>";
}

// ---------------------------------------------------------------------------
// Onglet Status (maquette Stitch « DNS watchdog redesign », 2026-11-20).
// Un modèle (dnswd_model) lu une fois, un widget par fonction. L'historique
// vient de /dnswatchdog/failures (base bbolt du daemon).
// ---------------------------------------------------------------------------

// Liste blanche des périodes : la valeur part dans l'URL REST et dans un OnClick.
function dnswd_periods():array{
    return array("hour"=>"{dnswd_p_hour}","day"=>"{dnswd_p_day}","yesterday"=>"{dnswd_p_yesterday}",
        "week"=>"{dnswd_p_week}","month"=>"{dnswd_p_month}","year"=>"{dnswd_p_year}");
}
function dnswd_period():string{
    $p=isset($_GET["period"])?(string)$_GET["period"]:"day";
    $all=dnswd_periods();
    return isset($all[$p])?$p:"day";
}
function dnswd_intervals():array{return array(1,3,5,10,30);}
function dnswd_tiny_filters(string $period):string{
    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"dnswd-period","LABEL"=>"{dnswd_period}","VALUE"=>$period,
        "OPTIONS"=>dnswd_periods(),"JS"=>"DnsWdPeriod");
    return siem_tiny_filters($fields,"");
}
// Rend du texte brut (entités décodées, [br] retirés) : l'appelant l'échappe.
// Traduire PUIS substituer : un %s remplacé avant ne retrouve plus sa clé.
function dnswd_t($tpl,string $token,string $value):string{
    $txt=$tpl->_ENGINE_parse_body("{".$token."}");
    $txt=trim(preg_replace('#\s*<br\s*/?>\s*#i',' ',str_replace("[br]","<br>",$txt)));
    $txt=html_entity_decode($txt,ENT_QUOTES,"UTF-8");
    return str_replace("%s",$value,$txt);
}
function dnswd_h($tpl,string $token,string $value=""):string{
    return htmlspecialchars(dnswd_t($tpl,$token,$value));
}
// Les réglages tels que le daemon les applique (mêmes valeurs par défaut que
// dnsdist.GetConfig() et pickWatchdogHost()).
function dnswd_settings():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $raw=trim((string)$s->GET_INFO("DnsDistWatchdogHosts"));
    if(strlen($raw)<4){$raw=trim((string)$s->GET_INFO("UnBoundWatchdogIPs"));}
    if(strlen($raw)<4){$raw="play.google.com,www.google.com,ogs.google.com,www.ibm.com,www.firefox.com,www.microsoft.com,www.defense.gov,www.nyc.gov,ec.europa.eu,www.icj-cij.org,safebrowsing.googleapis.com";}
    $hosts=array();
    foreach(explode(",",$raw) as $h){$h=trim($h);if($h!=""){$hosts[]=$h;}}
    $interval=intval($s->GET_INFO("DnsProxyCacheWatchdogInt"));
    if($interval<1){$interval=1;}
    $timeout=intval($s->GET_INFO("DnsDistWatchdogMaxTimeOut"));
    if($timeout<1){$timeout=3;}
    return array(
        "enabled"=>intval($s->GET_INFO("DnsDistDisableWatchdog"))==1?0:1,
        "cluster"=>intval($s->GET_INFO("HaClusterClient"))==1,
        "offline"=>intval($s->GET_INFO("NoInternetAccess"))==1,
        "hosts"=>$hosts,"hosts_raw"=>@implode(",",$hosts),
        "interval"=>$interval,"timeout"=>$timeout,
        "iface"=>trim((string)$s->GET_INFO("DnsDistWatchdogOutInterface")),
    );
}
function dnswd_model(string $period):array{
    $m=dnswd_settings();
    $m["period"]=$period;
    $m["error"]="";
    $m["data"]=null;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnswatchdog/failures?period=$period"));
    if(!$json || !property_exists($json,"Status")){
        $m["error"]="no answer from the Artica REST service";
    }elseif(!$json->Status){
        $m["error"]=property_exists($json,"Error")?(string)$json->Error:"unknown error";
    }else{
        $m["data"]=$json->Data;
    }
    return $m;
}

function dnswd_style():string{
    if(!empty($GLOBALS["DNSWD_CSS"])){return "";}
    $GLOBALS["DNSWD_CSS"]=true;
    return "<style>
.dnswd{margin:10px 0 20px 0;color:#1f2937;box-sizing:border-box}
.dnswd *{box-sizing:border-box}
.dnswd-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.dnswd-banner{display:flex;gap:20px;align-items:flex-start;padding:24px;border-left:4px solid #005447;margin-bottom:16px}
.dnswd-banner.off{border-left-color:#9ca3af}
.dnswd-bico{flex:0 0 44px;height:44px;border-radius:50%;background:#e6eeed;color:#005447;display:flex;align-items:center;justify-content:center;font-size:19px}
.dnswd-banner.off .dnswd-bico{background:#f3f4f6;color:#6b7280}
.dnswd-btxt{flex:1 1 auto;min-width:0}
.dnswd-btxt h2{margin:0 0 6px 0;font-size:20px;font-weight:600;color:#1f2937;line-height:1.3}
.dnswd-btxt p{margin:0;font-size:14px;color:#4b5563;line-height:1.55}
.dnswd-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.dnswd-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#f2f6f6;color:#005447;font-size:13px;border:1px solid #e6eeed}
.dnswd-notice{display:flex;gap:10px;align-items:flex-start;margin-top:14px;padding:10px 14px;border-radius:8px;font-size:13px;line-height:1.5}
.dnswd-notice.warn{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
.dnswd-notice.info{background:#f3f4f6;color:#374151;border:1px solid #e5e7eb}
.dnswd-notice i{margin-top:3px}
.dnswd-bact{flex:0 0 auto;display:flex;flex-direction:column;gap:8px;align-items:flex-end}
.dnswd-btn{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;border:1px solid #005447;background:#fff;color:#005447;text-decoration:none;white-space:nowrap}
.dnswd-btn:hover{background:#e6eeed;color:#003d33;text-decoration:none}
.dnswd-btn.primary{background:#005447;color:#fff}
.dnswd-btn.primary:hover{background:#003d33;color:#fff}
.dnswd-btn:focus-visible,.dnswd-link:focus-visible,.dnswd-more:focus-visible{outline:2px solid #005447;outline-offset:2px}
.dnswd-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:16px}
.dnswd-tile{padding:16px 18px;border-radius:10px;border-color:#eef0f3;min-width:0}
.dnswd-tile-h{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;font-size:14px;font-weight:600;color:#374151}
.dnswd-tag{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:999px;background:#f3f4f6;color:#4b5563;font-size:13px;font-weight:400;white-space:nowrap}
.dnswd-big{font-size:30px;font-weight:600;line-height:1.2;margin:8px 0 6px 0;color:#1f2937}
.dnswd-big.bad{color:#c0392b}
.dnswd-big.ok{color:#005447;font-size:20px;padding:6px 0 4px 0}
.dnswd-big small{font-size:18px;font-weight:400;color:#6b7280}
.dnswd-cap{font-size:13px;color:#6b7280;line-height:1.45;margin:0}
.dnswd-main{display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr);gap:16px;align-items:start}
.dnswd-chart{padding:20px 22px;min-width:0}
.dnswd-ch-h{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding-bottom:12px;border-bottom:1px solid #eef0f3;margin-bottom:14px}
.dnswd-ch-h h3,.dnswd-set h3{margin:0;font-size:16px;font-weight:600;color:#1f2937}
.dnswd-ch-h p{margin:4px 0 0 0;font-size:13px;color:#6b7280}
.dnswd-leg{display:flex;gap:16px;font-size:13px;color:#4b5563;white-space:nowrap;padding-top:2px}
.dnswd-leg span{display:inline-flex;align-items:center;gap:6px}
.dnswd-sw{display:inline-block;width:12px;height:12px;border-radius:2px;background:#e74c3c}
.dnswd-sw.none{background:repeating-linear-gradient(45deg,#f3f4f6 0 3px,#d1d5db 3px 4px);border:1px solid #e5e7eb}
.dnswd-canvas{position:relative;height:280px}
.dnswd-ch-f{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-top:14px;padding-top:12px;border-top:1px solid #eef0f3}
.dnswd-note{font-size:13px;color:#6b7280;line-height:1.5}
.dnswd-note i{margin-right:6px}
.dnswd-link{font-size:14px;font-weight:600;color:#005447;white-space:nowrap;cursor:pointer;text-decoration:none}
.dnswd-link:hover{color:#003d33;text-decoration:underline}
.dnswd-empty{text-align:center;padding:40px 10px;color:#6b7280;font-size:14px;line-height:1.5}
.dnswd-empty i{font-size:32px;color:#9ca3af;display:block;margin-bottom:12px}
.dnswd-empty b{display:block;color:#1f2937;font-size:15px;margin-bottom:4px}
.dnswd-err{color:#c0392b;font-size:13px}
.dnswd-set{padding:20px 22px;min-width:0}
.dnswd-set-h{display:flex;justify-content:space-between;align-items:center;gap:12px;padding-bottom:12px;border-bottom:1px solid #eef0f3}
.dnswd-row{display:flex;gap:12px;padding:14px 0;border-bottom:1px solid #f1f2f4}
.dnswd-row:last-child{border-bottom:0;padding-bottom:0}
.dnswd-row>i{flex:0 0 18px;color:#4b5563;margin-top:3px;text-align:center}
.dnswd-rb{flex:1 1 auto;min-width:0}
.dnswd-rt{display:flex;justify-content:space-between;align-items:baseline;gap:10px}
.dnswd-rl{font-size:14px;font-weight:600;color:#1f2937}
.dnswd-rl small{display:block;font-size:13px;font-weight:400;color:#9ca3af}
.dnswd-rv{font-size:14px;color:#1f2937;text-align:right;white-space:nowrap}
.dnswd-rv code{font-family:monospace;font-size:13px;background:#f3f4f6;padding:2px 8px;border-radius:4px;color:#1f2937}
.dnswd-rh{font-size:13px;color:#6b7280;margin-top:4px;line-height:1.45}
.dnswd-badge{display:inline-flex;align-items:center;gap:5px;padding:2px 10px;border-radius:999px;font-size:13px;font-weight:600}
.dnswd-badge.on{background:#e6eeed;color:#005447}
.dnswd-badge.off{background:#f3f4f6;color:#4b5563}
.dnswd-hosts{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.dnswd-hosts span.h{font-family:monospace;font-size:13px;background:#f3f4f6;border:1px solid #e5e7eb;padding:2px 8px;border-radius:4px;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dnswd-hosts span[hidden]{display:none}
.dnswd-more{border:0;background:none;color:#005447;font-size:13px;font-weight:600;cursor:pointer;padding:2px 4px}
.dnswd-rh code{font-family:monospace;font-size:13px;white-space:nowrap}
@media (max-width:1200px){.dnswd-main{grid-template-columns:minmax(0,1fr)}}
@media (max-width:900px){.dnswd-tiles{grid-template-columns:minmax(0,1fr)}.dnswd-banner{flex-wrap:wrap}}
</style>";
}

// 1. Bandeau d'accroche : bénéfice, puces tirées des réglages, état.
function dnswd_banner($tpl,array $m):string{
    $off=$m["enabled"]==0;
    $h=array();
    $h[]="<div class='dnswd-card dnswd-banner".($off?" off":"")."'>";
    $h[]="<div class='dnswd-bico' aria-hidden='true'><i class='fas ".($off?"fa-circle-pause":"fa-stethoscope")."'></i></div>";
    $h[]="<div class='dnswd-btxt'>";
    if($off){
        $h[]="<h2>{dnswd_off_title}</h2><p>{dnswd_off_text}</p>";
    }else{
        $h[]="<h2>{dnswd_banner_title}</h2><p>{dnswd_banner_text}</p>";
        $every=dnswd_h($tpl,$m["interval"]==1?"dnswd_chip_every1":"dnswd_chip_every",strval($m["interval"]));
        $h[]="<div class='dnswd-chips'>";
        $h[]="<span class='dnswd-chip'><i class='fas fa-clock'></i>$every</span>";
        $h[]="<span class='dnswd-chip'><i class='fas fa-globe'></i>".dnswd_h($tpl,"dnswd_chip_hosts",strval(count($m["hosts"])))."</span>";
        $h[]="<span class='dnswd-chip'><i class='fas fa-clock-rotate-left'></i>{dnswd_chip_logged}</span>";
        $h[]="</div>";
    }
    // Ces deux cas réduisent ce que fait le watchdog (dnswatchdog.go) sans l'arrêter.
    if($m["cluster"]){
        $h[]="<div class='dnswd-notice info'><i class='fas fa-diagram-project'></i><span>{dnswd_cluster_note}</span></div>";
    }
    if($m["offline"]){
        $h[]="<div class='dnswd-notice info'><i class='fas fa-plug-circle-xmark'></i><span>{dnswd_offline_note}</span></div>";
    }
    $h[]="</div>";
    $h[]="<div class='dnswd-bact'>";
    if($off){
        $h[]="<a class='dnswd-btn primary' href='javascript:blur();' OnClick=\"DnsWdParams();\"><i class='fas fa-power-off'></i>{dnswd_turn_on}</a>";
    }else{
        $h[]="<a class='dnswd-btn' href='javascript:blur();' OnClick=\"DnsWdParams();\"><i class='fas fa-sliders'></i>{parameters}</a>";
    }
    $h[]="</div></div>";
    return @implode("",$h);
}

// 2. Trois chiffres de la période. Sans données lisibles : « — », jamais 0.
function dnswd_tiles($tpl,array $m):string{
    $d=$m["data"];
    $known=$d!==null && intval($d->first_sample)>0;
    $failures=$known?intval($d->failures):null;
    $failed=$known?intval($d->failed_sweeps):null;
    $sweeps=$known?intval($d->sweeps):null;
    $last=$known?intval($d->last_failure):0;

    $h=array();
    $h[]="<div class='dnswd-tiles'>";
    $cls=($failures!==null && $failures>0)?" bad":"";
    $h[]="<div class='dnswd-card dnswd-tile'><div class='dnswd-tile-h'>{dnswd_failed_checks}</div>";
    $h[]="<div class='dnswd-big$cls'>".($failures===null?"—":number_format($failures))."</div>";
    $h[]="<p class='dnswd-cap'>{dnswd_failed_help}</p></div>";

    $cls=($failed!==null && $failed>0)?" bad":"";
    $val=$failed===null?"—":number_format($failed)." <small>".dnswd_h($tpl,"dnswd_of",number_format($sweeps))."</small>";
    $h[]="<div class='dnswd-card dnswd-tile'><div class='dnswd-tile-h'>{dnswd_tt_failed_rounds}</div>";
    $h[]="<div class='dnswd-big$cls'>$val</div>";
    $h[]="<p class='dnswd-cap'>".dnswd_h($tpl,"dnswd_rounds_help")."</p></div>";

    $h[]="<div class='dnswd-card dnswd-tile'><div class='dnswd-tile-h'><span>{dnswd_last_failure}</span>";
    if($last>0){
        $exact=date("Y-m-d H:i",$last);
        $h[]="<span class='dnswd-tag' title='$exact'><i class='far fa-calendar'></i>$exact</span></div>";
        $h[]="<div class='dnswd-big' title='$exact'>".htmlspecialchars(dnswd_ago($tpl,time()-$last))."</div>";
        $h[]="<p class='dnswd-cap'>{dnswd_last_help}</p></div>";
    }elseif($known){
        $h[]="</div><div class='dnswd-big ok'><i class='fas fa-circle-check'></i> {dnswd_none_period}</div>";
        $h[]="<p class='dnswd-cap'>{dnswd_none_help}</p></div>";
    }else{
        $h[]="</div><div class='dnswd-big'>—</div><p class='dnswd-cap'>{dnswd_no_data}</p></div>";
    }
    $h[]="</div>";
    return @implode("",$h);
}

// Ancienneté lisible. distanceOfTimeInWords() passe par les jetons partagés
// {days}/{hours} (« Days ») et ajoute « about » : jetons de page à la place.
function dnswd_ago($tpl,int $secs):string{
    if($secs<60){return dnswd_t($tpl,"dnswd_just_now","");}
    $min=intval(round($secs/60));
    if($min<90){$v=dnswd_t($tpl,$min==1?"dnswd_minute":"dnswd_minutes",strval($min));}
    else{
        $hrs=intval(round($secs/3600));
        if($hrs<48){$v=dnswd_t($tpl,"dnswd_hours",strval($hrs));}
        else{$v=dnswd_t($tpl,"dnswd_days",strval(intval(round($secs/86400))));}
    }
    return dnswd_t($tpl,"dnswd_ago",$v);
}
// Libellé d'un point selon la largeur des intervalles (Chart.js sans adaptateur
// de dates : catégories formatées ici).
function dnswd_label(int $t,int $bucket):string{
    if($bucket>=86400){return date("d/m",$t);}
    if($bucket>=3600){return date("D H:i",$t);}
    return date("H:i",$t);
}

// 3. Graphique Chart.js. Rend [html, script] : le script reste hors du parseur.
function dnswd_chart($tpl,array $m):array{
    $h=array();
    $h[]="<div class='dnswd-card dnswd-chart'>";
    $h[]="<div class='dnswd-ch-h'><div><h3>{dnswd_failures_title}</h3><p>{dnswd_chart_sub}</p></div>";
    $h[]="<div class='dnswd-leg'><span><i class='dnswd-sw'></i>{dnswd_failed_checks}</span><span><i class='dnswd-sw none'></i>{dnswd_no_round}</span></div></div>";

    if($m["error"]!==""){
        $h[]="<div class='dnswd-empty'><i class='fas fa-triangle-exclamation'></i><b>".dnswd_h($tpl,"dnswd_error",$m["error"])."</b></div></div>";
        return array(@implode("",$h),"");
    }
    $d=$m["data"];
    if(intval($d->first_sample)==0){
        $h[]="<div class='dnswd-empty'><i class='fas fa-chart-column'></i><b>{dnswd_no_data}</b>";
        $h[]=$m["enabled"]==0?"{dnswd_disabled_explain}":dnswd_h($tpl,"dnswd_no_data_explain",strval($m["interval"]));
        $h[]="</div></div>";
        return array(@implode("",$h),"");
    }

    $labels=array();$values=array();$rounds=array();$failedRounds=array();
    $bucket=intval($d->bucket_seconds);
    if(is_array($d->points)){
        foreach($d->points as $p){
            $labels[]=dnswd_label(intval($p->t),$bucket);
            $n=intval($p->sweeps);
            // Aucun balayage : point vide (null), pas zéro échec.
            $values[]=$n>0?intval($p->failures):null;
            $rounds[]=$n;
            $failedRounds[]=intval($p->failed_sweeps);
        }
    }
    $id="dnswd-c-".md5($m["period"].microtime());
    $aria=dnswd_h($tpl,"dnswd_chart_aria",number_format(intval($d->failures)));
    $h[]="<div class='dnswd-canvas'><canvas id='$id' role='img' aria-label=\"$aria\"></canvas></div>";

    $foot=array();
    $legacy=intval($d->legacy_until);
    if($legacy>0 && $legacy>=intval($d->from)){
        $foot[]="<div class='dnswd-note'><i class='fas fa-circle-info'></i>".dnswd_h($tpl,"dnswd_legacy_note",date("Y-m-d H:i",$legacy))."</div>";
    }elseif($m["enabled"]==0){
        $foot[]="<div class='dnswd-note'><i class='fas fa-circle-pause'></i>{dnswd_disabled_explain}</div>";
    }else{
        $foot[]="<div></div>";
    }
    $foot[]="<a class='dnswd-link' href='javascript:blur();' OnClick=\"DnsWdHistory();\">{dnswd_see_history} &rarr;</a>";
    $h[]="<div class='dnswd-ch-f'>".@implode("",$foot)."</div>";
    $h[]="</div>";

    $J=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP;
    $jt=function($token) use ($tpl,$J){return json_encode(dnswd_t($tpl,$token,""),$J);};
    $s=array();
    $s[]="<script>(function(){var L=".json_encode($labels,$J).",V=".json_encode($values,$J).",R=".json_encode($rounds,$J).",F=".json_encode($failedRounds,$J).";";
    $s[]="var tR=".$jt("dnswd_tt_rounds").",tF=".$jt("dnswd_tt_failed_rounds").",tN=".$jt("dnswd_no_round").",tL=".$jt("dnswd_failed_checks").";";
    $s[]="function hatch(c){var p=document.createElement('canvas');p.width=p.height=8;var x=p.getContext('2d');x.fillStyle='#f3f4f6';x.fillRect(0,0,8,8);x.strokeStyle='#d1d5db';x.lineWidth=1;x.beginPath();x.moveTo(0,8);x.lineTo(8,0);x.stroke();return c.getContext('2d').createPattern(p,'repeat');}";
    $s[]="function draw(){var c=document.getElementById('$id');if(!c){return;}";
    // Une seule instance : le fragment est rechargé à chaque période, l'ancien
    // graphique resterait sinon accroché à un canvas détaché.
    $s[]="if(window.DnsWdChart){try{window.DnsWdChart.destroy();}catch(e){}window.DnsWdChart=null;}";
    $s[]="var mx=1;for(var i=0;i<V.length;i++){if(V[i]!==null&&V[i]>mx){mx=V[i];}}";
    $s[]="var G=[];for(var j=0;j<V.length;j++){G.push(R[j]>0?null:mx);}";
    $s[]="var rm=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;";
    $s[]="window.DnsWdChart=new Chart(c,{type:'bar',data:{labels:L,datasets:[";
    $s[]="{label:tL,data:V,backgroundColor:'#e74c3c',borderRadius:2,maxBarThickness:28,grouped:false,order:1},";
    $s[]="{label:tN,data:G,backgroundColor:hatch(c),maxBarThickness:28,grouped:false,order:2}]},";
    $s[]="options:{responsive:true,maintainAspectRatio:false,animation:rm?false:{duration:250},";
    $s[]="plugins:{legend:{display:false},datalabels:{display:false},tooltip:{filter:function(x){return x.datasetIndex===(R[x.dataIndex]>0?0:1);},callbacks:{";
    $s[]="label:function(x){return R[x.dataIndex]>0?tL+': '+(V[x.dataIndex]||0):tN;},";
    $s[]="afterLabel:function(x){return R[x.dataIndex]>0?[tF+': '+F[x.dataIndex]+' / '+R[x.dataIndex]]:[];}}}},";
    $s[]="scales:{x:{grid:{display:false},ticks:{autoSkip:true,maxRotation:0,font:{size:13}}},y:{beginAtZero:true,suggestedMax:mx,ticks:{precision:0,font:{size:13}}}}}});}";
    $s[]="if(typeof Chart!=='undefined'){draw();return;}";
    $s[]="var e=document.createElement('script');e.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js';e.onload=draw;document.head.appendChild(e);})();</script>";
    return array(@implode("",$h),@implode("",$s));
}

// 4. « How the watchdog checks » : réglages en lecture, avec leur effet.
function dnswd_settings_card($tpl,array $m):string{
    $row=function($ico,$label,$tech,$value,$help){
        $t=$tech!==""?"<small>$tech</small>":"";
        return "<div class='dnswd-row'><i class='fas $ico' aria-hidden='true'></i><div class='dnswd-rb'>".
            "<div class='dnswd-rt'><span class='dnswd-rl'>$label$t</span><span class='dnswd-rv'>$value</span></div>".
            ($help!==""?"<div class='dnswd-rh'>$help</div>":"")."</div></div>";
    };
    $h=array();
    $h[]="<div class='dnswd-card dnswd-set'>";
    $h[]="<div class='dnswd-set-h'><h3>{dnswd_how_title}</h3>";
    $h[]="<a class='dnswd-btn' href='javascript:blur();' OnClick=\"DnsWdParams();\"><i class='fas fa-sliders'></i>{parameters}</a></div>";

    $badge=$m["enabled"]==1?"<span class='dnswd-badge on'><i class='fas fa-check'></i>{dnswd_on}</span>":
        "<span class='dnswd-badge off'><i class='fas fa-pause'></i>{dnswd_off}</span>";
    $h[]=$row("fa-power-off","{watchdog}","",$badge,"{dnswd_watchdog_help}");
    $h[]=$row("fa-clock","{dnswd_check_every}","",
        dnswd_h($tpl,$m["interval"]==1?"dnswd_minute":"dnswd_minutes",strval($m["interval"])),"{dnswd_interval_short}");
    $h[]=$row("fa-hourglass-half","{dnswd_wait_answer}","{timeout}",
        dnswd_h($tpl,"dnswd_seconds",strval($m["timeout"])),"{dnswd_timeout_short}");
    $iface=$m["iface"]!==""?"<code>".htmlspecialchars($m["iface"])."</code>":"{dnswd_default_route}";
    $h[]=$row("fa-network-wired","{dnswd_send_through}","{outgoing_interface}",$iface,"{dnswd_iface_short}");

    $chips=array();
    $i=0;
    foreach($m["hosts"] as $host){
        $hid=$i>=6?" hidden":"";
        $e=htmlspecialchars($host);
        $chips[]="<span class='h' title='$e'$hid>$e</span>";
        $i++;
    }
    $chipsHtml=@implode("",array_slice($chips,0,6));
    $more=count($chips)-6;
    if($more>0){
        $chipsHtml.="<span id='dnswd-more' hidden>".str_replace(" hidden","",@implode("",array_slice($chips,6)))."</span>";
        $chipsHtml.="<button type='button' class='dnswd-more' aria-expanded='false' aria-controls='dnswd-more' OnClick=\"DnsWdMore(this);\">".dnswd_h($tpl,"dnswd_more",strval($more))."</button>";
    }
    $hostsHelp="{dnswd_hosts_short} <code>crawl-66-249-66-*.googlebot.com</code>";
    $h[]="<div class='dnswd-row'><i class='fas fa-globe' aria-hidden='true'></i><div class='dnswd-rb'>".
        "<div class='dnswd-rt'><span class='dnswd-rl'>{dnswd_test_websites}<small>".dnswd_h($tpl,"dnswd_hosts_tech",strval(count($m["hosts"])))."</small></span></div>".
        "<div class='dnswd-hosts'>$chipsHtml</div><div class='dnswd-rh'>$hostsHelp</div></div></div>";
    $h[]="</div>";
    return @implode("",$h);
}
