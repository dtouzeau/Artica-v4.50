<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
// awc_table_style() and friends: shared kit, included at top level (never conditionally).
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["page2"])){page2();exit;}
if(isset($_GET["ruleid-js"])){rule_id_js();exit;}
if(isset($_GET["rule-popup"])){rule_tab();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["rule-save"])){rule_main_save();exit;}
if(isset($_GET["launch"])){launch();exit;}
if(isset($_POST["delete_rule"])){rule_delete();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["run-js"])){run_js();exit;}
if(isset($_POST["run"])){run_launch();exit;}
page();

/* ===========================================================================
 * Legal logs » History search (maquette Stitch « Legal log search », 2026-11-21).
 *
 * Donnees reelles seulement :
 *  - proxy_time (proxy_search.db) : un enregistrement par fichier archive (zdate, zDateTo,
 *    ztype 0=Proxy 1=FortiGate 2=Reverse proxy, filesize en octets) -> couverture par type ;
 *  - BackupMaxDaysDirSize = « Ko du dossier d'archives:Ko de la partition » (logrotate.go) ;
 *  - proxy_search : criteres + executed / percentage / lines ; fichier de progression
 *    search.<ID>.progress (POURC > 100 = echec) ; resultat /home/artica/squidsearchs/<ID>.log ;
 *  - articarest : 3 recherches au plus en meme temps, sans file d'attente.
 * Pas de duree, pas d'etat « refuse » persistant : le refus est montre au lancement.
 * ========================================================================= */

function rls_types():array{
    return array(
        0=>array("{rls_t_proxy}","fa-cloud","{rls_t_proxy_s}"),
        2=>array("{rls_t_rproxy}","fa-globe","{rls_t_rproxy_s}"),
        1=>array("FortiGate","fa-shield-halved","{rls_t_forti_s}"),
    );
}
function rls_fn():string{return "RlsRefresh";}

// Droit d'ecriture : creer, modifier, lancer, supprimer une recherche.
function isRights():bool{
	$users=new usersMenus();
	if($users->AsSquidAdministrator){return true;}
	if($users->AsDansGuardianAdministrator){return true;}
    return false;
}

function rls_db(){return new lib_sqlite("/home/artica/SQLITE/proxy_search.db");}

// Valeur echappee (HTML + accolades : le moteur prendrait {x} pour un jeton).
function rls_e($s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars((string)$s,ENT_QUOTES));
}
function rls_t($tpl,string $token,array $vals=array()):string{
    $txt=$tpl->_ENGINE_parse_body("{".$token."}");
    $txt=trim(preg_replace('#\s*<br\s*/?>\s*#i',' ',str_replace("[br]","<br>",$txt)));
    $i=0;
    return preg_replace_callback('/%s/',function($m) use ($vals,&$i){
        $v=isset($vals[$i])?(string)$vals[$i]:"";
        $i++;
        return $v;
    },$txt);
}
// « 3 March 2025 08:00 » ; le mois est un jeton traduit ({March}…).
function rls_date(int $ts,bool $time=true):string{
    if($ts<=0){return "&mdash;";}
    return date("j",$ts)." {".date("F",$ts)."} ".date("Y",$ts).($time?" ".date("H:i",$ts):"");
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{legal_logs}",ico_search_in_file,"{legal_logs_explain}",
        "$page?page2=yes","proxy-legal-search","progress-logrotate-restart",false,"table-loader-proxy-search");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{legal_logs}",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// Couverture des archives par type : fichiers, plus ancienne et plus recente date, taille.
function rls_coverage():array{
    $out=array();
    $q=rls_db();
    $res=$q->QUERY_SQL("SELECT ztype, COUNT(*) AS n, MIN(zdate) AS a, MAX(zDateTo) AS b, SUM(filesize) AS s FROM proxy_time GROUP BY ztype");
    if(!$q->ok || !is_array($res)){return $out;}
    foreach($res as $r){
        $out[intval($r["ztype"])]=array("n"=>intval($r["n"]),"from"=>intval($r["a"]),"to"=>intval($r["b"]),"size"=>floatval($r["s"]));
    }
    return $out;
}

// Taille du dossier d'archives et de sa partition, en Ko (-1 si inconnue).
function rls_storage():array{
    $raw=(string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDirSize");
    if(strpos($raw,":")===false){return array(-1,-1);}
    $tb=explode(":",$raw);
    return array(floatval($tb[0]),floatval($tb[1]));
}

function rls_model():array{
    return array(
        "crypt"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateEnableCrypt"))==1,
        "coverage"=>rls_coverage(),
        "storage"=>rls_storage(),
        "write"=>isRights(),
    );
}

// 1. Bandeau d'accroche.
function rls_banner():string{
    return "<section class='rls-card rls-banner'><div class='rls-bico' aria-hidden='true'><i class='fas fa-magnifying-glass-chart'></i></div>"
        ."<div class='rls-btxt'><h2>{rls_title}</h2><p>{rls_text}</p><div class='rls-chips'>"
        ."<span class='rls-chip'><i class='fas fa-lock' aria-hidden='true'></i>{rls_chip_ro}</span>"
        ."<span class='rls-chip'><i class='fas fa-layer-group' aria-hidden='true'></i>{rls_chip_3}</span>"
        ."<span class='rls-chip'><i class='fas fa-box-archive' aria-hidden='true'></i>{rls_chip_kept}</span>"
        ."</div></div></section>";
}

// 2a. Une carte de couverture par type d'archive.
function rls_cov_card($tpl,int $type,array $c,bool $write):string{
    $t=rls_types();
    $to=$c["to"]>0 && date("Y-m-d",$c["to"])==date("Y-m-d")?"{rls_today}":rls_date($c["to"],false);
    $period=rls_t($tpl,"rls_cov_period",array(rls_date($c["from"],false),$to));
    $files=rls_t($tpl,"rls_cov_files",array(number_format($c["n"]),FormatBytes($c["size"]/1024,true)));
    $btn=$write
        ?"<button type='button' class='rls-btn primary wide' OnClick=\"Loadjs('".CurrentPageName()."?ruleid-js=0&type=$type&function=".rls_fn()."',true);\"><i class='fas fa-plus' aria-hidden='true'></i>{new_search}</button>"
        :"";
    return "<div class='rls-card rls-cov'><div class='hd'><span class='ic' aria-hidden='true'><i class='fas {$t[$type][1]}'></i></span>"
        ."<div><h3>{$t[$type][0]}</h3><div class='s'>{$t[$type][2]}</div></div></div>"
        ."<div class='p'>$period</div><div class='f'>$files</div>$btn</div>";
}

// 2b. Stockage des archives : anneau mesure contre la taille de la partition.
function rls_storage_card($tpl,array $st):string{
    list($dir,$part)=$st;
    if($dir<0 || $part<=0){
        return "<div class='rls-card rls-cov'><div class='hd'><span class='ic' aria-hidden='true'><i class='fas fa-hard-drive'></i></span><div><h3>{rls_storage_t}</h3></div></div><div class='f'>&mdash;</div></div>";
    }
    $pct=max(0,min(100,100*$dir/$part));
    $r=20;$c=2*M_PI*$r;$on=round($c*$pct/100,2);$off=round($c-$on,2);
    $col=$pct>=90?"#dc2626":($pct>=75?"#d97706":"var(--rls-accent)");
    $ring="<svg width='52' height='52' viewBox='0 0 52 52' role='img' aria-label='".round($pct)."%'><circle cx='26' cy='26' r='$r' fill='none' stroke='#e5e7eb' stroke-width='6'/>"
        ."<circle cx='26' cy='26' r='$r' fill='none' stroke='$col' stroke-width='6' stroke-dasharray='$on $off' transform='rotate(-90 26 26)'/><text x='26' y='30' text-anchor='middle'>".round($pct)."%</text></svg>";
    $used=rls_t($tpl,"rls_storage_used",array(FormatBytes($dir,true),FormatBytes($part,true)));
    return "<div class='rls-card rls-cov'><div class='hd'><span class='ic' aria-hidden='true'><i class='fas fa-hard-drive'></i></span><div><h3>{rls_storage_t}</h3><div class='s'>{rls_storage_s}</div></div></div>"
        ."<div class='rls-ring'>$ring<div>$used</div></div></div>";
}

// 2. Bande de couverture, ou carte bloquante (chiffrement) / invitation (aucune archive).
function rls_coverage_strip($tpl,array $m):string{
    if($m["crypt"]){
        return "<section class='rls-card rls-block warn'><i class='fas fa-lock' aria-hidden='true'></i><div><h3>{rls_crypt_t}</h3><p>{rls_crypt_text}</p></div>"
            ."<button type='button' class='rls-btn' OnClick=\"document.location.href='/logs-rotate';\"><i class='fas fa-sliders' aria-hidden='true'></i>{rls_open_settings}</button></section>";
    }
    if(count($m["coverage"])==0){
        return "<section class='rls-card rls-block'><i class='fas fa-box-open' aria-hidden='true'></i><div><h3>{rls_noarch_t}</h3><p>{rls_noarch_text}</p></div>"
            ."<button type='button' class='rls-btn' OnClick=\"document.location.href='/logs-rotate';\"><i class='fas fa-sliders' aria-hidden='true'></i>{rls_open_settings}</button></section>";
    }
    $h=array();
    $h[]="<section class='rls-covs'>";
    foreach(rls_types() as $type=>$t){
        if(!isset($m["coverage"][$type])){continue;}
        $h[]=rls_cov_card($tpl,$type,$m["coverage"][$type],$m["write"]);
    }
    $h[]=rls_storage_card($tpl,$m["storage"]);
    $h[]="</section>";
    return @implode("",$h);
}

function page2(){
    $tpl=new template_admin();
	$page=CurrentPageName();
    $m=rls_model();
    $html="<div class='rls'>".rls_banner().rls_coverage_strip($tpl,$m);
    if(!$m["crypt"]){$html.="<section class='rls-card rls-list'><div id='rls-host'></div></section>";}
    $html.="</div>";
    echo rls_style().$tpl->_ENGINE_parse_body($html).rls_js($page,!$m["crypt"]);
}

function rls_js(string $page,bool $load):string{
    $fn=rls_fn();
    return "<script>
window.RlsFilter=window.RlsFilter||'all';
function $fn(){if(!document.getElementById('rls-host')){return;}clearTimeout(window.RlsTimer);LoadAjaxSilent('rls-host','$page?table=yes&function=$fn');}
function RlsBusy(){return !!document.querySelector('.modal.in,.modal.show,.sweet-alert.visible,.sweet-overlay[style*=\"block\"]');}
function RlsArm(){clearTimeout(window.RlsTimer);window.RlsTimer=setTimeout(function(){if(!document.getElementById('rls-host')){return;}if(RlsBusy()){RlsArm();return;}$fn();},3000);}
function RlsSetFilter(f){window.RlsFilter=f;var b=document.querySelectorAll('.rls-fbtn');
 for(var i=0;i<b.length;i++){var on=b[i].getAttribute('data-f')===f;b[i].className='rls-fbtn'+(on?' on':'');b[i].setAttribute('aria-checked',on?'true':'false');}
 var r=document.querySelectorAll('.rls-row');var shown=0;for(var k=0;k<r.length;k++){var ok=f==='all'||r[k].getAttribute('data-s')===f;r[k].style.display=ok?'':'none';if(ok){shown++;}}
 var z=document.getElementById('rls-zero');if(z){z.hidden=shown>0||r.length===0;}}
".($load?"$fn();":"")."
</script>";
}

function launch():bool{
    $tpl=new template_admin();
    if(!isRights()){$tpl->js_error("{ERROR_NO_PRIVS2}");return false;}
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    // Daemon starts up to MaxConcurrentSearches and skips the rest (no queue).
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/legal/logs/search/all");
    admin_tracks("Legal logs: launch all history searches");
    echo "$function();";
    return true;
}

function run_js(){
    $tpl=new template_admin();
    if(!isRights()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_GET["run-js"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $tpl->js_confirm_execute(rls_t($tpl,"rls_run_confirm",array($ID)),"run",$ID,"$function();");
}
function run_launch(){
    $tpl=new template_admin();
    if(!isRights()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
    $ID=intval($_POST["run"]);
    $q=rls_db();
    $q->QUERY_SQL("UPDATE proxy_search SET `executed`='0',`lines`='0',`percentage`='0' WHERE ID='$ID'");
    if(!$q->ok){echo $q->mysql_error;return false;}
    // The daemon admits at most 3 concurrent searches and refuses a search that is already
    // running: its reason is echoed (non-empty = shown by js_confirm_execute).
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/legal/logs/search/$ID"));
    if(is_object($json) && isset($json->Status) && !$json->Status){
        $q->QUERY_SQL("UPDATE proxy_search SET executed=1 WHERE ID='$ID'");
        echo $tpl->_ENGINE_parse_body($json->Error);
        return false;
    }
    admin_tracks("Legal logs: run history search $ID");
    return true;
}

function download(){
    $aclid=intval($_GET["download"]);
    $mainpath="/home/artica/squidsearchs/$aclid.log";
    if(!is_file($mainpath)){header("HTTP/1.1 404 Not Found");echo "Not found";return;}
    header('Content-type: text/plain');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"search-$aclid.log\"");
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("Content-Length: ".filesize($mainpath));
    ob_clean();
    flush();
    readfile($mainpath);
}

function rule_delete_js(){
	$tpl=new template_admin();
    if(!isRights()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
	$aclid=intval($_GET["delete-rule-js"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $tpl->js_confirm_delete(rls_t($tpl,"rls_delete_confirm",array($aclid)),"delete_rule",$aclid,"$function();");
}

function rule_delete():bool{
    $tpl=new template_admin();
    if(!isRights()){echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");return false;}
    $aclid=intval($_POST["delete_rule"]);
    // result file and report directory are root-owned: the daemon removes them.
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/legal/logs/search/$aclid/remove");
    $q=rls_db();
    $ligne=$q->mysqli_fetch_array("SELECT zmd5 FROM proxy_search WHERE ID='$aclid'");
    $zmd5=isset($ligne["zmd5"])?$q->sqlite_escape_string2((string)$ligne["zmd5"]):"";
    if($zmd5!==""){$q->QUERY_SQL("DELETE FROM proxy_reports WHERE zmd5='$zmd5'");}
    $q->QUERY_SQL("DELETE FROM proxy_search WHERE ID=$aclid");
    if(!$q->ok){echo $q->mysql_error;return false;}
    admin_tracks("Legal logs: delete history search $aclid");
    return true;
}

function rule_id_js():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    if(!isRights()){
        $tpl->js_error("{ERROR_NO_PRIVS2}");
        return false;
    }
    $ztype=intval($_GET["type"] ?? 0);
	$id=intval($_GET["ruleid-js"]);
	$title=$id>0?rls_t($tpl,"rls_search_n",array($id)):$tpl->_ENGINE_parse_body("{new_search}");
	$title=$tpl->javascript_parse_text($title);
	$tpl->js_dialog($title,"$page?rule-popup=$id&function=$function&type=$ztype");
	return true;
}

function rule_tab(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$aclid=intval($_GET["rule-popup"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $ztype=intval($_GET["type"] ?? 0);
    $q=rls_db();
    $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM proxy_time WHERE ztype=$ztype");
    $tcount=intval($ligne["tcount"] ?? 0);
    $label=rls_t($tpl,"rls_tab_files",array(number_format($tcount)));
    $array[$label]="$page?rule-settings=$aclid&function=$function&type=$ztype";
	echo $tpl->tabs_default($array);
}

// La fenetre d'une recherche : 1. Quand, 2. Qui, 3. Ou, options — chaque champ explique.
function rule_settings():bool{
	$ID=intval($_GET["rule-settings"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
	$tpl=new template_admin();
    $logfileD=new logfile_daemon();
	$q=rls_db();
	$btname="{rls_add}";
	$title="";
	$BootstrapDialog="BootstrapDialog1.close();";
    $uuid="";$datefrom="";$timefrom="";$dateto="";$timeto="";
    $username="";$ipsrc="";$ipdest="";$category="";$sitename="";$squidcode="";$uri="";
    $enabled=1;$maxlines=500;
    $ztype=intval($_GET["type"] ?? 1);
	if($ID>0){
		$btname="{rls_apply}";
		$ligne=$q->mysqli_fetch_array("SELECT * FROM proxy_search WHERE ID='$ID'");
        $uuid=$ligne["uuid"];
        $datefrom=$ligne["datefrom"];$timefrom=$ligne["timefrom"];
        $dateto=$ligne["dateto"];$timeto=$ligne["timeto"];
        $username=$ligne["username"];$ipsrc=$ligne["ipsrc"];$ipdest=$ligne["ipdest"];
        $category=$ligne["category"];$sitename=$ligne["sitename"];$squidcode=$ligne["squidcode"];
        $enabled=$ligne["enabled"];$maxlines=$ligne["maxlines"];$uri=$ligne["uri"];
        $ztype=intval($ligne["ztype"]);
	}
    $ligneF=$q->mysqli_fetch_array("SELECT MIN(zdate) AS a, MAX(zDateTo) AS b, COUNT(*) AS n FROM proxy_time WHERE ztype=$ztype");
    $a=intval($ligneF["a"] ?? 0);$b=intval($ligneF["b"] ?? 0);
    $dateDefaultFrom=date("Y-m-d H:i:00",$a);
    $dateDefaultTo=date("Y-m-d H:i:00",$b);
    if(strlen($timeto)<3){$timeto="00:00:00";}
    if(strlen($timefrom)<3){$timefrom="00:00:00";}
    $squid_codes=$logfileD->codeToString(0,true);
    $cover=rls_t($tpl,"rls_f_cover",array(rls_date($a,false),rls_date($b,false),number_format(intval($ligneF["n"] ?? 0))));

	$tpl->field_hidden("rule-save", $ID);
    $form[]=$tpl->field_hidden("uuid",$uuid);
    $form[]=$tpl->field_hidden("ztype",$ztype);
    $form[]=$tpl->field_section("{rls_f_when}",$cover);
    $form[]=$tpl->field_daterange("datefromto","{rls_f_period}","$datefrom $timefrom - $dateto $timeto",$dateDefaultFrom,$dateDefaultTo);

    $form[]=$tpl->field_section("{rls_f_who}","{rls_f_who_help}");
    $form[]=$tpl->field_text("ipsrc","{rls_f_ip}",$ipsrc,false,"{rls_f_ip_help}");
    if($ztype==2){
        $form[]=$tpl->field_text("username","User-Agent",$username,false,"{rls_f_ua_help}");
    }else{
        $form[]=$tpl->field_text("username","{username}",$username,false,"{rls_f_user_help}");
    }

    $form[]=$tpl->field_section("{rls_f_where}","{rls_f_who_help}");
    $form[]=$tpl->field_text("sitename","{rls_f_site}",$sitename,false,"{rls_f_site_help}");
    if($ztype==1){
        $form[]=$tpl->field_text("ipdest","{rls_f_server}",$ipdest,false,"{rls_f_server_help}");
    }
    if($ztype==2){
        $form[]=$tpl->field_text("uri","{url}",$uri,false,"{rls_f_url_help}");
    }
    // Category filter: Squid (ztype 0) and FortiGate (ztype 1) access logs carry a category ID.
    if($ztype==0 || $ztype==1){
        $form[]=$tpl->field_categories_list("category","{category}",$category,"{rls_f_cat_help}");
    }
    $form[]=$tpl->field_array_hash($squid_codes,"squidcode","{rls_f_code}",$squidcode,false,"{rls_f_code_help}");

    $form[]=$tpl->field_section("{rls_f_options}");
    $form[]=$tpl->field_numeric("maxlines","{rls_f_max}",$maxlines,"{rls_f_max_help}");
    $form[]=$tpl->field_checkbox("enabled","{enabled}",$enabled,false,"{rls_f_enabled_help}");
	echo $tpl->form_outside($title,@implode("\n", $form),"{rls_f_explain}",$btname,"$function();$BootstrapDialog");
    return true;
}

function rule_main_save():bool{
	$tpl=new template_admin();
    if(!isRights()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $tpl->CLEAN_POST();
    $q=rls_db();
    $ID=intval($_POST["rule-save"]);
    $uuid=$q->sqlite_escape_string2((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID"));
    // Free-text fields are escaped before interpolation; numeric fields are intval'd.
    $username=$q->sqlite_escape_string2((string)($_POST["username"] ?? ""));
    $ipsrc=$q->sqlite_escape_string2((string)($_POST["ipsrc"] ?? ""));
    $ipdest=$q->sqlite_escape_string2((string)($_POST["ipdest"] ?? ""));
    $category=$q->sqlite_escape_string2((string)($_POST["category"] ?? ""));
    $sitename=$q->sqlite_escape_string2((string)($_POST["sitename"] ?? ""));
    $uri=$q->sqlite_escape_string2((string)($_POST["uri"] ?? ""));
    $squidcode=intval($_POST["squidcode"] ?? 0);
    $maxlines=intval($_POST["maxlines"] ?? 500);
    if($maxlines<1){$maxlines=500;}
    $enabled=intval($_POST["enabled"] ?? 0);
    $ztype=intval($_POST["ztype"] ?? 0);

    $datefromto=(string)($_POST["datefromto"] ?? "");
    if(!preg_match("#^(.+?)\s+([0-9:]+)\s+-\s+(.+?)\s+([0-9:]+)#",$datefromto,$re)){
        echo $tpl->post_error("{rls_f_bad_period}");
        return false;
    }
    $datefromInt=strtotime("$re[1] $re[2]");
    $datetoInt=strtotime("$re[3] $re[4]");
    if($datefromInt===false || $datetoInt===false){echo $tpl->post_error("{rls_f_bad_period}");return false;}
    $datefrom=date("Y-m-d",$datefromInt);$timefrom=date("H:i",$datefromInt);
    $dateto=date("Y-m-d",$datetoInt);$timeto=date("H:i",$datetoInt);

    $set=array("uuid"=>$uuid,"maxlines"=>$maxlines,"datefrom"=>$datefrom,"timefrom"=>$timefrom,"timeto"=>$timeto,
        "ztype"=>$ztype,"dateto"=>$dateto,"username"=>$username,"ipsrc"=>$ipsrc,"ipdest"=>$ipdest,
        "category"=>$category,"sitename"=>$sitename,"squidcode"=>$squidcode,"uri"=>$uri,"enabled"=>$enabled);
    $edit=array();
    foreach($set as $k=>$v){$edit[]="`$k`='$v'";}
    // Criteria changed: the previous result no longer matches, the search waits for a new run.
    $edit[]="`executed`='0'";

    if($ID==0){
        $add=$set+array("logspath"=>"","lines"=>0,"size"=>0,"executed"=>0,"percentage"=>0);
        $fields=array();$values=array();
        foreach($add as $k=>$v){$fields[]="`$k`";$values[]="'$v'";}
        $q->QUERY_SQL("INSERT INTO proxy_search (".@implode(",",$fields).") VALUES (".@implode(",",$values).")");
        if(!$q->ok){echo $tpl->post_error($tpl->javascript_parse_text($q->mysql_error));return false;}
        admin_tracks("Legal logs: add a history search ".@implode(", ",$edit));
        return true;
	}
    $q->QUERY_SQL("UPDATE proxy_search SET ".@implode(",",$edit)." WHERE ID='$ID'");
	if(!$q->ok){echo $tpl->post_error($tpl->javascript_parse_text($q->mysql_error));return false;}
	admin_tracks("Legal logs: update history search $ID ".@implode(", ",$edit));
	return true;
}

// Progression lue dans search.<ID>.progress : array(pourcentage, texte, echec).
function search_progress_read($ID):array{
    $f="/usr/share/artica-postfix/ressources/logs/web/search.$ID.progress";
    if(!is_file($f)){return array(0,"",false);}
    $raw=@file_get_contents($f);
    if($raw===false || strlen($raw)<3){return array(0,"",false);}
    $a=@unserialize($raw);
    if(!is_array($a)){return array(0,"",false);}
    $pourc=isset($a["POURC"])?intval($a["POURC"]):0;
    $text=isset($a["TEXT"])?(string)$a["TEXT"]:"";
    if($pourc>100){return array(100,$text,true);}
    return array($pourc,$text,false);
}

// Etat d'une recherche : cle de filtre, pastille, recherche en cours.
function rls_state($tpl,array $l):array{
    $ID=intval($l["ID"]);
    if(intval($l["executed"])==1){
        $n=intval($l["lines"]);
        if($n>0){return array("done","<span class='rls-pill ok'><i class='fas fa-circle-check' aria-hidden='true'></i>".rls_t($tpl,"rls_st_found",array(number_format($n)))."</span>",false);}
        return array("done","<span class='rls-pill'><i class='fas fa-circle-minus' aria-hidden='true'></i>{rls_st_nothing}</span>",false);
    }
    list($pourc,$text,$failed)=search_progress_read($ID);
    if($failed){
        return array("notrun","<span class='rls-pill err'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i>{rls_st_failed}</span>"
            .($text!==""?"<div class='rls-ptext'>".rls_e($tpl->_ENGINE_parse_body($text))."</div>":""),false);
    }
    if($pourc<=0){$pourc=intval($l["percentage"]);}
    if($pourc>0){
        $t=$text!==""?"<div class='rls-ptext' title=\"".rls_e($tpl->_ENGINE_parse_body($text))."\">".rls_e($tpl->_ENGINE_parse_body($text))."</div>":"";
        return array("running","<span class='rls-pill run'><i class='fas fa-spinner' aria-hidden='true'></i>".rls_t($tpl,"rls_st_running",array($pourc))."</span>"
            ."<div class='rls-bar' role='progressbar' aria-valuemin='0' aria-valuemax='100' aria-valuenow='$pourc' aria-label=\"$pourc%\"><span style='width:$pourc%'></span></div>$t",true);
    }
    return array("notrun","<span class='rls-pill'><i class='fas fa-circle-pause' aria-hidden='true'></i>{rls_st_notrun}</span>",false);
}

// Les criteres d'une recherche en pastilles lisibles.
function rls_criteria($tpl,array $l):string{
    $from=strtotime($l["datefrom"]." ".$l["timefrom"]);$to=strtotime($l["dateto"]." ".$l["timeto"]);
    $c=array();
    $c[]="<span class='rls-cc date'><i class='fas fa-calendar' aria-hidden='true'></i>".rls_date($from===false?0:$from)." &rarr; ".rls_date($to===false?0:$to)."</span>";
    $ztype=intval($l["ztype"]);
    $add=function($label,$val,$mono=true,$cls="") use (&$c){
        if(trim((string)$val)===""){return;}
        $c[]="<span class='rls-cc $cls'><b>$label</b> <span class='".($mono?"m":"")."'>".rls_e($val)."</span></span>";
    };
    $add("{rls_c_ip}",$l["ipsrc"]);
    $add($ztype==2?"User-Agent":"{rls_c_user}",$l["username"]);
    $add("{rls_c_site}",$l["sitename"]);
    $add("{rls_c_server}",$l["ipdest"]);
    $add("{rls_c_url}",$l["uri"]);
    if(intval($l["category"])>0){
        $catz=new mysql_catz();
        $name=(string)$catz->CategoryIntToStr(intval($l["category"]));
        $add("{rls_c_cat}",$name!==""?$name:"#".intval($l["category"]),false);
    }
    if(intval($l["squidcode"])>0){$add("{rls_c_code}",intval($l["squidcode"]),true,"red");}
    $c[]="<span class='rls-cc soft'>".rls_t($tpl,"rls_c_max",array(number_format(intval($l["maxlines"]))))."</span>";
    return @implode("",$c);
}

// Nombre de fichiers archives qui touchent la periode : meme regle que le daemon
// (logrotate.SearchFilesinDB, chevauchement ; chaque ligne est ensuite filtree sur son heure).
function rls_files_for($l):int{
    $from=strtotime($l["datefrom"]." ".$l["timefrom"]);$to=strtotime($l["dateto"]." ".$l["timeto"]);
    if($from===false || $to===false){return 0;}
    $z=intval($l["ztype"]);
    $q=rls_db();
    $r=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM proxy_time WHERE ztype=$z AND zdate <= $to AND zDateTo >= $from");
    return intval($r["tcount"] ?? 0);
}

function rls_actbtn(string $label,string $icon,string $js,bool $enabled,string $why,string $cls=""):string{
    if(!$enabled){
        return "<button type='button' class='rls-btn sm $cls' disabled title=\"$why\"><i class='fas $icon' aria-hidden='true'></i>$label</button>";
    }
    return "<button type='button' class='rls-btn sm $cls' OnClick=\"$js\"><i class='fas $icon' aria-hidden='true'></i>$label</button>";
}

// Une ligne de recherche.
function rls_row($tpl,array $l,bool $write):string{
    $page=CurrentPageName();$fn=rls_fn();
    $ID=intval($l["ID"]);$type=intval($l["ztype"]);
    $t=rls_types();
    $typeLabel=isset($t[$type])?$t[$type][0]:"#$type";
    list($skey,$pill,$running)=rls_state($tpl,$l);
    $files=rls_files_for($l);
    $note=$files>0
        ?"<div class='rls-note'>".rls_t($tpl,$files==1?"rls_files1":"rls_files",array(number_format($files)))."</div>"
        :"<div class='rls-note red'><i class='fas fa-circle-exclamation' aria-hidden='true'></i>{rls_files0}</div>";
    $disabled=intval($l["enabled"])==0?"<span class='rls-pill'><i class='fas fa-toggle-off' aria-hidden='true'></i>{rls_st_disabled}</span>":"";
    $done=intval($l["executed"])==1;
    $lines=intval($l["lines"]);
    $file="/home/artica/squidsearchs/$ID.log";
    $size=is_file($file)?@filesize($file):0;
    $readpage=($type==2)?"fw.nginx.requests.php":"fw.proxy.relatime.php";
    $wait="{rls_why_finished}";
    $acts=array();
    if($write){
        $acts[]=rls_actbtn($done?"{rls_run_again}":"{rls_run}","fa-play","Loadjs('$page?run-js=$ID&function=$fn')",!$running && $files>0,$running?"{rls_why_running}":"{rls_why_nofile}",$done?"":"primary");
    }
    $acts[]=rls_actbtn("{rls_read}","fa-eye","Loadjs('$readpage?search-file-js=$ID&function=$fn')",$done && $lines>0,$wait);
    $acts[]=rls_actbtn("{rls_report}","fa-chart-pie","s_PopUp('fw.proxy.rotate.report.php?ID=$ID','1280','900');",$done && $lines>0,$wait);
    $dl=$size>0?"{rls_download} <small>(".FormatBytes($size/1024,true).")</small>":"{rls_download}";
    $acts[]=rls_actbtn($dl,"fa-download","document.location.href='$page?download=$ID'",$done && $size>0,$wait);
    if($write){
        $acts[]=rls_actbtn("{rls_edit}","fa-pen","Loadjs('$page?ruleid-js=$ID&type=$type&function=$fn',true);",!$running,"{rls_why_running}","ghost");
        $acts[]="<button type='button' class='rls-btn sm ghost danger' aria-label=\"".rls_e(rls_t($tpl,"rls_delete_aria",array($ID)))."\" OnClick=\"Loadjs('$page?delete-rule-js=$ID&function=$fn')\"><i class='fas fa-trash' aria-hidden='true'></i></button>";
    }
    return "<div class='rls-row".($running?" running":"")."' data-s='$skey'>"
        ."<div class='c1'><div class='t'><b>".rls_t($tpl,"rls_search_n",array($ID))."</b><span class='rls-type'>$typeLabel</span>$disabled</div>"
        ."<div class='rls-ccs'>".rls_criteria($tpl,$l)."</div>$note</div>"
        ."<div class='c2'>$pill</div><div class='c3'>".@implode("",$acts)."</div></div>";
}

// Repondeur ?table=yes : la carte « Your searches ».
function table(){
	$tpl=new template_admin();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateEnableCrypt"))==1){
        echo $tpl->_ENGINE_parse_body("<div class='rls-empty'><h4>{rls_crypt_t}</h4><p>{rls_crypt_text}</p></div>");
        return false;
    }
    $page=CurrentPageName();$fn=rls_fn();
    $write=isRights();
	$q=rls_db();
	$results=$q->QUERY_SQL("SELECT * FROM proxy_search ORDER BY ID DESC");
    $err=$q->ok?"":$q->mysql_error;
    if(!is_array($results)){$results=array();}
    $rows=array();$count=array("all"=>0,"running"=>0,"done"=>0,"notrun"=>0);$anyRunning=false;
	foreach($results as $ligne){
        $r=rls_row($tpl,$ligne,$write);
        if(preg_match("#data-s='([a-z]+)'#",$r,$re)){$count[$re[1]]++;}
        if(strpos($r,"rls-row running")!==false){$anyRunning=true;}
        $count["all"]++;
        $rows[]=$r;
	}
    $h=array();
    $h[]="<div class='rls-lhead'><div><h3>{rls_list_t} <span class='rls-tag'>".rls_t($tpl,"rls_list_count",array($count["all"],$count["running"]))."</span></h3>";
    $h[]="<div class='rls-filters' role='radiogroup' aria-label=\"{rls_filter}\">";
    foreach(array("all"=>"{rls_f_all}","running"=>"{rls_f_running}","done"=>"{rls_f_done}","notrun"=>"{rls_f_notrun}") as $k=>$lab){
        $h[]="<button type='button' role='radio' aria-checked='false' class='rls-fbtn' data-f='$k' OnClick=\"RlsSetFilter('$k');\">$lab ({$count[$k]})</button>";
    }
    $h[]="</div></div>";
    if($write){
        $h[]="<div class='rls-runall'><button type='button' class='rls-btn' OnClick=\"Loadjs('$page?launch=yes&function=$fn');\"><i class='fas fa-rocket' aria-hidden='true'></i>{rls_runall}</button><p>{rls_runall_help}</p></div>";
    }
    $h[]="</div>";
    if($err!==""){$h[]="<div class='rls-err'>".rls_e($err)."</div>";}
    if(count($rows)==0){
        $h[]="<div class='rls-empty'><div class='ic'><i class='fas fa-magnifying-glass' aria-hidden='true'></i></div><h4>{rls_empty_t}</h4><p>{rls_empty_text}</p></div>";
    }else{
        $h[]="<div class='rls-thead' aria-hidden='true'><div>{rls_col_search}</div><div>{rls_col_state}</div><div>{rls_col_actions}</div></div>";
        $h[]=@implode("",$rows);
        $h[]="<div class='rls-zero' id='rls-zero' hidden>{rls_zero}</div>";
    }

    $TINY_ARRAY=array();
    $topbuttons=array(array("$fn();",ico_refresh,"{refresh}"));
    $TINY_ARRAY["TITLE"]="{APP_SQUID} &raquo; {legal_logs}: {history_search}";
    $TINY_ARRAY["ICO"]="fas fa-file-search";
    $TINY_ARRAY["EXPL"]="{legal_logs_explain}";
    $TINY_ARRAY["URL"]="proxy-legal-search";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo $tpl->_ENGINE_parse_body(@implode("",$h))
        ."<script>NoSpinner();RlsSetFilter(window.RlsFilter||'all');".($anyRunning?"RlsArm();":"")."var rh=document.getElementById('rls-host');if(rh&&!rh.getAttribute('data-tiny')){rh.setAttribute('data-tiny','1');$jstiny}</script>";
}

function rls_style():string{
    if(!empty($GLOBALS["RLS_CSS"])){return "";}
    $GLOBALS["RLS_CSS"]=true;
    $ac=array("accent"=>"#005447","accent_dark"=>"#003d33","accent_soft"=>"rgba(0,84,71,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return "<style>
.rls{--rls-accent:{$ac["accent"]};--rls-dark:{$ac["accent_dark"]};--rls-soft:{$ac["accent_soft"]};display:flex;flex-direction:column;gap:16px;margin-top:10px;color:#0f172a;font-size:14px}
.rls *{box-sizing:border-box}
.rls [hidden]{display:none!important}
.rls-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 2px rgba(0,0,0,.04);padding:20px}
.rls-banner{display:flex;gap:16px;align-items:flex-start}
.rls-bico{flex:0 0 44px;width:44px;height:44px;border-radius:10px;background:var(--rls-soft);color:var(--rls-accent);display:flex;align-items:center;justify-content:center;font-size:20px}
.rls-btxt h2{margin:0 0 6px 0;font-size:19px;font-weight:600}
.rls-btxt p{margin:0;color:#475569;line-height:1.55}
.rls-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.rls-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:999px;background:#f2f6f6;border:1px solid #e1ebe9;font-size:13px;color:#1e293b}
.rls-chip i,.rls-chip svg{color:var(--rls-accent)}
.rls-covs{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
.rls-cov{display:flex;flex-direction:column;gap:8px;min-width:0}
.rls-cov .hd{display:flex;gap:12px;align-items:center}
.rls-cov .ic{flex:0 0 36px;width:36px;height:36px;border-radius:8px;background:#f1f5f9;color:var(--rls-accent);display:flex;align-items:center;justify-content:center}
.rls-cov h3{margin:0;font-size:17px;font-weight:600}
.rls-cov .s{font-size:13px;color:#64748b}
.rls-cov .p{font-size:15px;font-weight:600;margin-top:4px}
.rls-cov .f{font-size:13px;color:#64748b;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.rls-ring{display:flex;gap:12px;align-items:center;font-size:14px;color:#334155;margin-top:4px}
.rls-ring text{font-size:12px;font-weight:600;fill:#0f172a}
.rls-block{display:flex;gap:16px;align-items:center}
.rls-block>i,.rls-block>svg{font-size:26px;color:var(--rls-accent)}
.rls-block h3{margin:0 0 4px 0;font-size:17px;font-weight:600}
.rls-block p{margin:0;color:#475569}
.rls-block>div{flex:1}
.rls-block.warn{background:#fffbeb;border-color:#fde68a}
.rls-block.warn>i,.rls-block.warn>svg{color:#d97706}
.rls-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;height:36px;padding:0 14px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#1e293b;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap}
.rls-btn:hover{border-color:var(--rls-accent);color:var(--rls-dark)}
.rls-btn.primary{background:var(--rls-accent);border-color:var(--rls-accent);color:#fff}
.rls-btn.primary:hover{background:var(--rls-dark);color:#fff}
.rls-btn.wide{width:100%;margin-top:auto}
.rls-btn.sm{height:32px;padding:0 10px}
.rls-btn.sm small{font-weight:400;opacity:.85}
.rls-btn.ghost{border-color:transparent;background:transparent}
.rls-btn.ghost:hover{background:#f1f5f9}
.rls-btn.danger{color:#b91c1c}
.rls-btn[disabled]{opacity:.45;cursor:not-allowed}
.rls-btn:focus-visible,.rls-fbtn:focus-visible{outline:2px solid var(--rls-accent);outline-offset:2px}
.rls-list{padding:0;overflow:hidden}
.rls-lhead{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;padding:20px}
.rls-lhead h3{margin:0 0 10px 0;font-size:18px;font-weight:600;display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.rls-tag{font-size:13px;font-weight:500;color:#475569;background:#f1f5f9;border-radius:6px;padding:2px 8px}
.rls-filters{display:flex;flex-wrap:wrap;gap:6px}
.rls-fbtn{height:30px;padding:0 12px;border-radius:999px;border:1px solid #e5e7eb;background:#f8fafc;font-size:13px;color:#334155;cursor:pointer}
.rls-fbtn.on{background:var(--rls-accent);border-color:var(--rls-accent);color:#fff;font-weight:600}
.rls-runall{text-align:right;max-width:420px}
.rls-runall p{margin:6px 0 0 0;font-size:13px;color:#64748b;line-height:1.45}
.rls-thead,.rls-row{display:grid;grid-template-columns:minmax(0,1fr) 220px auto;gap:16px;align-items:center;padding:14px 20px}
.rls-thead{background:#f8fafc;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;font-size:13px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#475569;padding-top:10px;padding-bottom:10px}
.rls-thead>div:last-child{text-align:right}
.rls-row{border-bottom:1px solid #f1f5f9;box-shadow:inset 3px 0 0 transparent}
.rls-row.running{box-shadow:inset 3px 0 0 var(--rls-accent)}
.rls-row .c1{min-width:0}
.rls-row .t{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:6px}
.rls-type{font-size:13px;font-weight:600;color:var(--rls-dark);background:#e6eeed;border-radius:6px;padding:1px 8px}
.rls-ccs{display:flex;flex-wrap:wrap;gap:6px}
.rls-cc{display:inline-flex;gap:5px;align-items:center;font-size:13px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:2px 8px;color:#334155;max-width:100%;overflow-wrap:anywhere}
.rls-cc b{font-weight:600;color:#475569}
.rls-cc .m{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.rls-cc.date{background:#f8fafc}
.rls-cc.date i,.rls-cc.date svg{color:#64748b}
.rls-cc.red{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
.rls-cc.soft{background:transparent;border-style:dashed;color:#64748b}
.rls-note{margin-top:6px;font-size:13px;color:#64748b}
.rls-note.red{color:#b91c1c;display:flex;gap:6px;align-items:center}
.rls-row .c2{min-width:0}
.rls-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 10px;border-radius:999px;font-size:13px;font-weight:600;border:1px solid #e5e7eb;background:#f8fafc;color:#475569;white-space:nowrap}
.rls-pill.ok{background:#f2f6f6;border-color:#bfd4d1;color:var(--rls-dark)}
.rls-pill.run{background:#fff;border-color:#bfd4d1;color:var(--rls-dark)}
.rls-pill.err{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
.rls-bar{height:6px;border-radius:999px;background:#e5e7eb;overflow:hidden;margin-top:6px}
.rls-bar span{display:block;height:100%;background:var(--rls-accent)}
.rls-ptext{font-size:13px;color:#64748b;margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rls-row .c3{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end}
.rls-empty{text-align:center;padding:32px 20px}
.rls-empty .ic{width:52px;height:52px;margin:0 auto 10px auto;border-radius:50%;background:var(--rls-soft);color:var(--rls-accent);display:flex;align-items:center;justify-content:center;font-size:22px}
.rls-empty h4{margin:0 0 6px 0;font-size:16px;font-weight:600}
.rls-empty p{margin:0 auto;max-width:520px;font-size:13px;color:#64748b}
.rls-zero{padding:24px;text-align:center;color:#64748b}
.rls-err{margin:0 20px 12px 20px;padding:10px 14px;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:13px}
@media (max-width:1200px){.rls-thead{display:none}.rls-row{grid-template-columns:1fr}.rls-row .c3{justify-content:flex-start}.rls-lhead{flex-direction:column}.rls-runall{text-align:left}}
@media (prefers-reduced-motion:no-preference){.rls-btn,.rls-fbtn{transition:all .15s ease}.rls-bar span{transition:width .3s ease}}
</style>";
}
