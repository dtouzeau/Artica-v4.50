<?php
// POST SP10 20261023-12
// Post SP10 - 13/10/2026
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.rdpproxy.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["remove"])){remove_video_ask();exit;}
if(isset($_POST["remove"])){remove_video_perform();exit;}
if(isset($_GET["ssh-js"])){ssh_js();exit;}
if(isset($_GET["ssh-popup"])){ssh_popup();exit;}
if(isset($_GET["ssh-commands"])){ssh_commands();exit;}
if(isset($_GET["ssh-cast"])){ssh_cast();exit;}
if(isset($_GET["ssh-files"])){ssh_files();exit;}
if(isset($_GET["ssh-cast-download"])){ssh_cast_download();exit;}
// Fichiers telecharges depuis une cible WEB (spec 2026-11-11) : fenetre, table, copie d'un fichier.
if(isset($_GET["web-js"])){web_files_js();exit;}
if(isset($_GET["web-popup"])){web_files_popup();exit;}
if(isset($_GET["web-files-search"])){web_files_search();exit;}
if(isset($_GET["web-file-download"])){web_file_download();exit;}

page();

// Lire un enregistrement (zip, trace SSH), le supprimer : AsSquidAdministrator, le droit du
// journal d'acces (fw.rdpproxy.events.php). Une trace contient ce que l'administrateur distant
// a tape et vu (configurations, jetons...) : le droit de voir le menu (AsProxyMonitor) ne suffit pas.
function rdpv_allowed():bool{
    if(!isset($GLOBALS["RDPV_ALLOWED"])){
        $users=new usersMenus();
        $GLOBALS["RDPV_ALLOWED"]=(bool)$users->AsSquidAdministrator;
    }
    return $GLOBALS["RDPV_ALLOWED"];
}

// Refus d'un fragment HTML (fenetre, vue) : message explicite, jamais une page blanche.
function rdpv_denied_html():string{
    $tpl=new template_admin();
    return $tpl->_ENGINE_parse_body($tpl->div_error("{ERROR_NO_PRIVS2}"));
}

function download(){
    if(!rdpv_allowed()){header("HTTP/1.1 403 Forbidden");header("Content-type: text/plain");echo "Forbidden";return;}
    $ID=intval($_GET["download"]);
    $error=""; $dir="";
    $ligne=rdpproxy_video($ID,$error,$dir);
    if($error!="" || !isset($ligne["path"])){header("HTTP/1.1 404 Not Found");echo "Not found";return;}
    $path=basename((string)$ligne["path"]);
    $file="$dir/$path";
    if($path=="" || !is_file($file)){header("HTTP/1.1 404 Not Found");echo "Not found";return;}
    $connection_date=intval($ligne["connection_date"]);

    header('Content-type: application/zip');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"{$connection_date}_$path\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".filesize($file));
    ob_clean();
    flush();
    readfile($file);

}

function remove_video_ask(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!rdpv_allowed()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_GET["remove"]);
    $md=preg_replace('/[^a-f0-9]/','',(string)$_GET["md"]);
    $error="";
    $ligne=rdpproxy_video($ID,$error);
    if($error!=""){echo $tpl->js_error($error);return;}
    $duration=htmlspecialchars((string)$ligne["duration"]);
    $psize=FormatBytes(intval($ligne["psize"])/1024);
    $path=htmlspecialchars((string)$ligne["path"]);

    $after="$('#$md').remove();if(typeof RdpvApply==='function'){RdpvApply();}";
    $tpl->js_confirm_delete("$path<br>$psize<br>{duration}: $duration","remove",$ID,$after);
}
// Echo RIEN en cas de succes : js_confirm_delete ne joue $after que sur une
// reponse vide.
// rdp_confirm_error : la boite de confirmation (js_dialog_confirm_action / js_confirm_delete)
// affiche la reponse telle quelle en HTML et ne connait pas le prefixe « jserror: » de
// post_error : texte traduit, sans balise, echappe.
// Les valeurs des jetons sont deja en entites (&egrave;, &#233;) : les decoder avant
// d'echapper, sinon « &amp;egrave; » s'affiche. Non-ASCII reencode en entites numeriques.
function rdp_confirm_error($tpl,$msg):string{
    $t=strip_tags($tpl->_ENGINE_parse_body((string)$msg));
    $t=html_entity_decode($t,ENT_QUOTES|ENT_HTML5,"UTF-8");
    $t=htmlspecialchars($t,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8");
    if(function_exists("mb_encode_numericentity")){$t=mb_encode_numericentity($t,array(0x80,0x10FFFF,0,0x1FFFFF),"UTF-8");}
    return $t;
}

function remove_video_perform(){
    $tpl=new template_admin();
    if(!rdpv_allowed()){echo rdp_confirm_error($tpl,"{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_POST["remove"]);
    // Lu AVANT la suppression, pour nommer l'enregistrement dans le journal.
    $err=""; $v=rdpproxy_video($ID,$err);
    $who=isset($v["username"])?$v["username"]:"";
    $host=isset($v["hostname"])?$v["hostname"]:"";
    $when=isset($v["connection_date"])&&intval($v["connection_date"])>0?date("Y-m-d H:i",intval($v["connection_date"])):"";
    $error=rdpproxy_video_delete($ID);
    if($error!=""){echo rdp_confirm_error($tpl,$error);return;}
    admin_tracks("Deleted RDS Proxy session recording #$ID".($who!=""?" of $who":"").($host!=""?" on $host":"").($when!=""?" ($when)":""));
}






function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");
    // En-tete standard : il porte les zones #tiny-* ou table() depose ses boutons.
    $html=$tpl->page_header("{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {videos}",
        "fas fa-video","{rdpv_explain}",
        "$page?table=yes","rdpproxy-videos",
        "progress-rdpproxy-restart",false,"table-rdpproxy-videos");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {videos}",$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// Tableau de bord des enregistrements : bandeau de capacite, 4 indicateurs, utilisateurs et
// cibles les plus enregistres, note de conservation, tableau filtrable. Les indicateurs sont
// calcules sur la liste servie par le daemon (les 500 plus recents) ; seul le total vient de
// « Total ». Une fonction par widget.
function table(){
    $tpl=new template_admin();
    $error=""; $path=""; $videoOK=false; $total=0;
    // Les 500 plus recents : le daemon n'ouvre que ces zips (la liste entiere ouvrait tout
    // l'historique a chaque affichage).
    $results=rdpproxy_videos($error,$path,$videoOK,500,$total);
    if($error!=""){echo $tpl->FATAL_ERROR_SHOW_128($error);return;}
    if(!is_array($results)){$results=array();}
    $list=array();
    foreach($results as $r){ if(is_array($r)){$list[]=rdpv_normalize($r);} }
    $total=max($total,count($list));
    // Sans FFmpeg, les sessions RDP/VNC ne deviennent pas des videos, mais le scan empaquette
    // quand meme les transcriptions SSH (RDPProxy/videos.go VideosScan).
    $sshOK=rdpproxy_ssh_supported();
    $now=time();

    $html=array();
    $html[]="<div class='rdpv'>";
    if(!$videoOK){$html[]=rdpv_capability_strip($sshOK);}
    $html[]="<div class='rdpv-kpis'>";
    $html[]=rdpv_kpi_count($list,$total,$now);
    $html[]=rdpv_kpi_space($list,$now);
    $html[]=rdpv_kpi_types($list);
    $html[]=rdpv_kpi_interrupted($list);
    $html[]="</div>";
    if(count($list)>0){
        $html[]="<div class='rdpv-tops'>";
        $html[]=rdpv_top_users($list);
        $html[]=rdpv_top_targets($list);
        $html[]="</div>";
    }
    $html[]=rdpv_retention_note();
    $html[]=rdpv_table($list,$total,$now,$videoOK||$sshOK);
    $html[]="</div>";

    echo rdpv_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).rdpv_js().rdpv_tiny($videoOK||$sshOK);
}

// Boutons de l'en-tete (TINY) : synchronisation (scan des enregistrements) et rechargement.
function rdpv_tiny($canSync):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    if(rdpv_allowed()){
        $topbuttons[]=array(rdpv_sync_js(),"fas fa-sync-alt","{synchronization}",$canSync?"AsSquidAdministrator":"FalseLicense");
    }
    $topbuttons[]=array("LoadAjax('table-rdpproxy-videos','$page?table=yes');",ico_refresh,"{reload}");
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_RDPPROXY} v$version &raquo;&raquo; {videos}";
    $TINY_ARRAY["ICO"]="fas fa-video";
    $TINY_ARRAY["EXPL"]="{rdpv_explain}";
    $TINY_ARRAY["URL"]="rdpproxy-videos";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

function rdpv_sync_js():string{
    $page=CurrentPageName();
    $ARRAY=array();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.rdpproxy-videos.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.rdpproxy-videos.progress.txt";
    $ARRAY["CMD"]="/rdpproxy/videos/scan";
    $ARRAY["TITLE"]="{synchronization}";
    $ARRAY["AFTER"]="LoadAjax('table-rdpproxy-videos','$page?table=yes');";
    // Reverifie par fw.progress.php a l'execution (tache signee) : le bouton masque ne suffit pas.
    $ARRAY["PRIV"]="AsSquidAdministrator";
    $prgress=base64_encode(serialize($ARRAY));
    return "Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-rdpproxy-restart');";
}

// rdpv_normalize : champs derives d'un enregistrement. Session SSH interrompue : articarest
// ecrit la duree « H:MM:SS (interrupted) » ; champ « interrupted » du daemon, repli sur la
// duree pour un daemon anterieur. Sans « has_video » (daemon anterieur), un enregistrement
// sans trace SSH est une video.
function rdpv_normalize(array $l):array{
    $duration=(string)($l["duration"] ?? "");
    $l["_interrupted"]=!empty($l["interrupted"]) || substr($duration,-strlen("(interrupted)"))==="(interrupted)";
    $l["_ssh"]=!empty($l["has_ssh_trace"]);
    $l["_web"]=!empty($l["has_web_files"]);
    $l["_video"]=isset($l["has_video"])?!empty($l["has_video"]):!$l["_ssh"];
    $l["_secs"]=rdpv_duration_secs($duration);
    $l["_date"]=intval($l["connection_date"] ?? 0);
    $l["_size"]=max(0,floatval($l["psize"] ?? 0));
    return $l;
}

// « H:MM:SS », « MM:SS » ou « H:MM:SS (interrupted) » -> secondes ; -1 si illisible.
function rdpv_duration_secs($d):int{
    if(!preg_match('/^\s*(?:(\d+):)?(\d{1,2}):(\d{2})/',(string)$d,$m)){return -1;}
    return intval($m[1])*3600+intval($m[2])*60+intval($m[3]);
}

function rdpv_hms($s):string{
    if($s<0){return "&mdash;";}
    return intdiv($s,3600).":".sprintf("%02d:%02d",intdiv($s%3600,60),$s%60);
}

// Age lisible : « 35 min », « 3 h », « 2 j ».
function rdpv_age($s):string{
    $s=max(0,intval($s));
    if($s<3600){return max(1,intdiv($s,60))." min";}
    if($s<86400){return intdiv($s,3600)." h";}
    return intdiv($s,86400)." {rdpt_days_short}";
}

// « il y a 3 h » : l'ordre des mots change selon la langue, le jeton porte un %s.
function rdpv_ago($s):string{
    if(!isset($GLOBALS["RDPV_AGO"])){
        $tpl=new template_admin();
        $GLOBALS["RDPV_AGO"]=$tpl->_ENGINE_parse_body("{rdpv_ago}");
        if(strpos($GLOBALS["RDPV_AGO"],"%s")===false){$GLOBALS["RDPV_AGO"]="%s";}
    }
    return str_replace("%s",rdpv_age($s),$GLOBALS["RDPV_AGO"]);
}

// Nombre par jour sur les 14 derniers jours (le plus ancien d'abord), d'apres connection_date.
function rdpv_per_day(array $list,$now,$field):array{
    $days=array();
    $today=strtotime(date("Y-m-d",$now));
    for($i=13;$i>=0;$i--){ $days[date("Y-m-d",$today-$i*86400)]=0; }
    foreach($list as $l){
        if($l["_date"]<=0){continue;}
        $k=date("Y-m-d",$l["_date"]);
        if(!isset($days[$k])){continue;}
        $days[$k]+=$field=="size"?$l["_size"]:1;
    }
    return $days;
}

function rdpv_kpi($label,$value,$sub,$art,$cls=""):string{
    return "<div class='rdpv-card rdpv-kpi $cls'><div class='rdpv-kpi-b'><span class='rdpv-kpi-l'>$label</span>"
        ."<span class='rdpv-kpi-v'>$value</span><span class='rdpv-kpi-s'>$sub</span></div><div class='rdpv-kpi-art'>$art</div></div>";
}

// Bandeau fin quand le paquet n'a pas FFmpeg ; « Details » deplie l'explication complete.
function rdpv_capability_strip($sshOK):string{
    $h="<details class='rdpv-strip'><summary><span class='rdpv-strip-ico'><i class='fas fa-video-slash' aria-hidden='true'></i></span>"
        ."<span class='rdpv-strip-t'>{rdpproxy_video_unavailable}</span><span class='rdpv-strip-d'>{rdpv_unavailable_short}</span>";
    if($sshOK){
        $h.="<span class='rdpv-strip-ok'><i class='fas fa-circle-check' aria-hidden='true'></i> {rdpv_ssh_available}</span>";
    }
    $h.="<span class='rdpv-strip-more'>{rdpv_details} <i class='fas fa-chevron-down' aria-hidden='true'></i></span></summary>";
    $h.="<div class='rdpv-strip-body'>{rdpproxy_video_unavailable_explain}".($sshOK?"<br>{rdpproxy_video_unavailable_ssh}":"")."</div></details>";
    return $h;
}

function rdpv_kpi_count(array $list,$total,$now):string{
    $n=count($list);
    $sub=$total>$n?"$n {rdpv_latest_shown}":"{rdpv_all_shown}";
    $art="<span class='rdpv-kpi-ico'><i class='fas fa-film'></i></span>";
    if($n>0){
        $days=rdpv_per_day($list,$now,"count");
        $sum=array_sum($days);
        $art="<span class='rdpv-g-days' role='img' aria-label=\"{rdpv_per_day} : $sum\" title=\"{rdpv_per_day} : $sum\">".implode(",",array_values($days))."</span>";
    }
    return rdpv_kpi("{rdpv_recordings}",number_format($total,0,","," "),$sub,$art);
}

function rdpv_kpi_space(array $list,$now):string{
    $sum=0; foreach($list as $l){ $sum+=$l["_size"]; }
    $n=count($list);
    if($n==0){
        return rdpv_kpi("{rdpv_space}","<span class='rdpv-dim'>&mdash;</span>","","<span class='rdpv-kpi-ico'><i class='fas fa-hard-drive'></i></span>");
    }
    $days=rdpv_per_day($list,$now,"size");
    $vals=array(); foreach($days as $v){ $vals[]=round($v/1048576,1); }
    $art="<span class='rdpv-g-size' role='img' aria-label=\"{rdpv_size_per_day}\" title=\"{rdpv_size_per_day}\">".implode(",",$vals)."</span>";
    return rdpv_kpi("{rdpv_space}",rdpssh_size($sum),"{rdpv_avg_size} ".rdpssh_size($sum/$n),$art);
}

function rdpv_kpi_types(array $list):string{
    $v=0;$s=0;$b=0;
    foreach($list as $l){
        if($l["_video"] && $l["_ssh"]){$b++;}elseif($l["_ssh"]){$s++;}else{$v++;}
    }
    $sub="<span class='rdpv-lg'><span class='rdpv-dot rdpv-c-video'></span>$v {rdpv_t_video}</span>"
        ."<span class='rdpv-lg'><span class='rdpv-dot rdpv-c-ssh'></span>$s {rdpv_t_ssh}</span>"
        ."<span class='rdpv-lg'><span class='rdpv-dot rdpv-c-both'></span>$b {rdpv_t_both}</span>";
    $art="<span class='rdpv-kpi-ico'><i class='fas fa-box-archive'></i></span>";
    if(count($list)>0){
        $art="<span class='rdpv-g-types' role='img' aria-label=\"$v {rdpv_t_video}, $s {rdpv_t_ssh}, $b {rdpv_t_both}\">$v,$s,$b</span>";
    }
    return rdpv_kpi("{rdpv_types}","",$sub,$art,"rdpv-kpi-types");
}

function rdpv_kpi_interrupted(array $list):string{
    $n=0; foreach($list as $l){ if($l["_interrupted"]){$n++;} }
    if($n==0){
        return rdpv_kpi("{rdpv_interrupted}","0","<span class='rdpv-good'><i class='fas fa-circle-check'></i> {rdpv_none_interrupted}</span>",
            "<span class='rdpv-kpi-ico rdpv-ico-ok'><i class='fas fa-shield-check'></i></span>");
    }
    return rdpv_kpi("{rdpv_interrupted}","<span class='rdpv-warn-v'>$n</span>","{rdpv_interrupted_sub}",
        "<span class='rdpv-kpi-ico rdpv-ico-warn'><i class='fas fa-triangle-exclamation'></i></span>","rdpv-kpi-warn");
}

// Classement des 5 valeurs les plus frequentes d'un champ ; un clic filtre le tableau.
function rdpv_top_card(array $list,$field,$title,$ico,$cls):string{
    $c=array();
    foreach($list as $l){
        $k=trim((string)($l[$field] ?? ""));
        if($k===""){continue;}
        $c[$k]=($c[$k] ?? 0)+1;
    }
    arsort($c);
    $c=array_slice($c,0,5,true);
    $max=max(1,count($c)>0?max($c):1);
    $h="<section class='rdpv-card rdpv-top' aria-label=\"$title\"><div class='rdpv-top-h'><span class='rdpv-top-t'><i class='$ico' aria-hidden='true'></i> $title</span>"
        ."<span class='rdpv-top-n'>Top 5</span></div><div class='rdpv-top-l'>";
    if(count($c)==0){$h.="<span class='rdpv-dim'>&mdash;</span>";}
    foreach($c as $k=>$n){
        $e=htmlspecialchars((string)$k,ENT_QUOTES);
        $js=htmlspecialchars(json_encode((string)$k),ENT_QUOTES);
        $pct=max(2,round($n/$max*100));
        $h.="<button type='button' class='rdpv-top-r' onclick=\"RdpvSearch($js)\" title=\"{rdpv_filter_on} $e\">"
            ."<span class='rdpv-mono rdpv-top-k'>$e</span><span class='rdpv-bar $cls' aria-hidden='true'><span style='width:$pct%'></span></span>"
            ."<span class='rdpv-top-c'>$n</span></button>";
    }
    return $h."</div></section>";
}
function rdpv_top_users(array $list):string{
    return rdpv_top_card($list,"username","{rdpv_top_users}","fas fa-user","rdpv-bar-u");
}
function rdpv_top_targets(array $list):string{
    return rdpv_top_card($list,"hostname","{rdpv_top_targets}","fas fa-server","rdpv-bar-t");
}

// Duree de conservation : le meme reglage que l'onglet video de fw.rdpproxy.status.php.
function rdpv_retention_note():string{
    $tpl=new template_admin();
    $d=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoRententionDays"));
    if($d<=0){$d=365;}
    $t=$tpl->_ENGINE_parse_body("{rdpv_retention}");
    $t=strpos($t,"%s")!==false?str_replace("%s","<b>$d</b>",$t):"$t ($d)";
    return "<div class='rdpv-ret'><i class='far fa-calendar-clock' aria-hidden='true'></i> $t</div>";
}

// Barre d'outils (recherche, filtre par type) + tableau + pied pagine.
function rdpv_table(array $list,$total,$now,$canSync):string{
    $cv=0;$cs=0;$ci=0;$max=1;
    foreach($list as $l){
        if($l["_video"]){$cv++;} if($l["_ssh"]){$cs++;} if($l["_interrupted"]){$ci++;}
        $max=max($max,$l["_secs"]);
    }
    $canDelete=rdpv_allowed();
    $n=count($list);
    $h="<div class='rdpv-card rdpv-tbl-card'><div class='rdpv-tools'><div class='rdpv-tools-l'>";
    $h.="<label class='rdpv-search'><i class='fas fa-magnifying-glass' aria-hidden='true'></i>"
        ."<input type='search' id='rdpv-q' placeholder=\"{rdpv_search_ph}\" aria-label=\"{rdpv_search_ph}\" oninput=\"RdpvApply(true)\"></label>";
    $h.="<div class='rdpv-seg' role='group' aria-label=\"{rdpv_col_content}\">";
    $h.="<button type='button' class='rdpv-f' data-v=''>{rdpv_all} ($n)</button>";
    $h.="<button type='button' class='rdpv-f' data-v='video'><i class='fas fa-video' aria-hidden='true'></i> {rdpv_t_video} ($cv)</button>";
    $h.="<button type='button' class='rdpv-f' data-v='ssh'><i class='fas fa-terminal' aria-hidden='true'></i> {rdpv_t_ssh} ($cs)</button>";
    $h.="<button type='button' class='rdpv-f' data-v='int'><i class='fas fa-circle-exclamation' aria-hidden='true'></i> {rdpv_f_interrupted} ($ci)</button>";
    $h.="</div></div><span class='rdpv-count' id='rdpv-count' aria-live='polite'></span></div>";

    $h.="<div class='rdpv-scroll'><table class='rdpv-tbl'><thead><tr>"
        ."<th>{rdpv_col_session}</th><th>{rdpv_col_date}</th><th>{rdpv_col_duration}</th><th>{rdpv_col_rule}</th>"
        ."<th>{rdpv_col_content}</th><th class='rdpv-r'>{rdpv_col_size}</th><th class='rdpv-r'>{rdpv_col_actions}</th></tr></thead><tbody id='rdpv-rows'>";
    if($n==0){
        $sync=($canDelete && $canSync)?"<button type='button' class='rdpv-btn rdpv-btn-p' onclick=\"".rdpv_sync_js()."\"><i class='fas fa-sync-alt' aria-hidden='true'></i> {synchronization}</button>":"";
        $h.="<tr><td colspan='7'><div class='rdpv-empty'><i class='fas fa-film'></i><b>{rdpv_no_record}</b><span>{rdpv_no_record_sub}</span>$sync</div></td></tr>";
    }
    foreach($list as $l){ $h.=video_row($l,$canDelete,$now,$max); }
    $h.="<tr class='rdpv-nomatch' style='display:none'><td colspan='7'>{rdpv_no_match}</td></tr>";
    $h.="</tbody></table></div>";
    $more=$total>$n?" <span class='rdpv-dim'>&middot; $n / ".number_format($total,0,","," ")." {rdpv_latest_shown}</span>":"";
    $h.="<div class='rdpv-foot'><span id='rdpv-range'></span>$more<span class='rdpv-pg' id='rdpv-pg'></span></div></div>";
    return $h;
}

// Une ligne par enregistrement : qui -> quelle cible, quand, combien de temps, quoi.
// L'id de la ligne (md5) est celui que la confirmation de suppression retire du DOM : ne pas le changer.
function video_row($ligne,$canDelete,$now=0,$max=1):string{
    $page=CurrentPageName();
    if(!isset($ligne["_secs"])){$ligne=rdpv_normalize($ligne);}
    if($now==0){$now=time();}
    $e=function($v){ return htmlspecialchars((string)$v,ENT_QUOTES); };
    $ID=intval($ligne["ID"] ?? 0);
    $md=md5(serialize(array_diff_key($ligne,array_flip(array("_interrupted","_ssh","_web","_video","_secs","_date","_size")))));
    $user=$e($ligne["username"] ?? "");
    $host=$e($ligne["hostname"] ?? "");
    $rule=$e($ligne["rule_name"] ?? "");
    $path=$e($ligne["path"] ?? "");
    $date=$ligne["_date"];
    $secs=$ligne["_secs"];
    $download="s_PopUpFull('$page?download=$ID','1024','900');";
    $who=($user!==""?$user:"&mdash;")." &rarr; ".($host!==""?$host:"&mdash;");

    // Date courte : time_to_date() ecrit « Wednesday October 28 18:03:58 » et pousse les actions hors du cadre.
    $created=$date>0?date("Y-m-d H:i",$date):"&mdash;";
    $ago=$date>0?"<span class='rdpv-sub'>".rdpv_ago($now-$date)."</span>":"";
    $pct=$secs>0?max(2,round($secs/max(1,$max)*100)):0;

    $chips="";
    if($ligne["_video"]){$chips.="<span class='rdpv-chip rdpv-chip-v'><i class='fas fa-video' aria-hidden='true'></i> {rdpv_t_video}</span>";}
    if($ligne["_ssh"]){$chips.="<span class='rdpv-chip rdpv-chip-s'><i class='fas fa-terminal' aria-hidden='true'></i> {rdpv_t_ssh}</span>";}
    if($ligne["_web"]){$chips.="<span class='rdpv-chip rdpv-chip-s'><i class='fas fa-file-arrow-down' aria-hidden='true'></i> {rdpproxy_web_files}</span>";}
    if($ligne["_interrupted"]){$chips.="<span class='rdpv-chip rdpv-chip-i' title=\"{rdpproxy_video_interrupted}\"><i class='fas fa-circle-exclamation' aria-hidden='true'></i> {rdpv_f_interrupted}</span>";}
    if($chips===""){$chips="<span class='rdpv-dim'>&mdash;</span>";}

    $types=($ligne["_video"]?" video":"").($ligne["_ssh"]?" ssh":"").($ligne["_interrupted"]?" int":"");
    $h="<tr id='$md' data-t='".trim($types)."'>";
    $h.="<td><div class='rdpv-c2'><span class='rdpv-who'>$who</span><span class='rdpv-file rdpv-mono' title='$path'>$path</span></div></td>";
    $h.="<td class='rdpv-nw'><div class='rdpv-c2'><span class='rdpv-strong'>$created</span>$ago</div></td>";
    $h.="<td class='rdpv-nw'><span class='rdpv-mono rdpv-strong'>".rdpv_hms($secs)."</span>"
        .($pct>0?"<span class='rdpv-dbar' aria-hidden='true'><span style='width:$pct%'></span></span>":"")."</td>";
    $h.="<td>".($rule!==""?"<span class='rdpv-rule rdpv-mono'>$rule</span>":"<span class='rdpv-dim'>&mdash;</span>")."</td>";
    $h.="<td><div class='rdpv-chips'>$chips</div></td>";
    $h.="<td class='rdpv-r rdpv-nw rdpv-mono'>".rdpssh_size($ligne["_size"])."</td>";
    $h.="<td class='rdpv-r'><div class='rdpv-acts'>";
    if($canDelete){
        if($ligne["_ssh"]){
            $h.="<button type='button' class='rdpv-btn' onclick=\"Loadjs('$page?ssh-js=$ID')\" aria-label=\"{rdpproxy_ssh_trace_read} : $who\"><i class='fas fa-terminal' aria-hidden='true'></i> {rdpv_t_ssh}</button>";
        }
        if($ligne["_web"]){
            $h.="<button type='button' class='rdpv-btn' onclick=\"Loadjs('$page?web-js=$ID')\" aria-label=\"{rdpproxy_web_files} : $who\"><i class='fas fa-file-arrow-down' aria-hidden='true'></i> {rdpproxy_web_files}</button>";
        }
        $h.="<button type='button' class='rdpv-btn' onclick=\"$download\" aria-label=\"{rdpv_download} : $who\"><i class='fas fa-download' aria-hidden='true'></i> {rdpv_download}</button>";
        $h.="<button type='button' class='rdpv-btn rdpv-btn-del' onclick=\"Loadjs('$page?remove=$ID&md=$md')\" aria-label=\"{rdpv_delete} : $who\" title=\"{rdpv_delete}\"><i class='fas fa-trash-can' aria-hidden='true'></i></button>";
    }
    $h.="</div></td></tr>";
    return $h;
}

// Recherche, filtre par type et pagination (25) cote navigateur. L'etat vit dans window.Rdpv*
// pour survivre au bouton « Recharger ».
function rdpv_js():string{
    $tpl=new template_admin();
    $showing=json_encode($tpl->_ENGINE_parse_body("{rdpv_showing}"));
    return "<script>
var RdpvShowing=$showing;
function RdpvSearch(v){ var i=document.getElementById('rdpv-q'); if(i){i.value=v;} RdpvApply(true); if(i){i.focus();} }
function RdpvApply(typed){
  var tb=document.getElementById('rdpv-rows'); if(!tb){return;}
  var inp=document.getElementById('rdpv-q');
  if(typed){ window.RdpvQ=inp.value; window.RdpvPage=1; } else if(inp && inp.value!==(window.RdpvQ||'')){ inp.value=window.RdpvQ||''; }
  var q=(window.RdpvQ||'').toLowerCase().replace(/^\s+|\s+$/g,''), t=window.RdpvT||'', hits=[], nomatch=null, size=25, all=0;
  for(var i=0;i<tb.rows.length;i++){
    var tr=tb.rows[i];
    if(tr.className.indexOf('rdpv-nomatch')>=0){nomatch=tr;continue;}
    if(!tr.hasAttribute('data-t')){continue;}
    all++;
    var ok=(t===''||(' '+tr.getAttribute('data-t')+' ').indexOf(' '+t+' ')>=0)&&(q===''||(tr.textContent||'').toLowerCase().indexOf(q)>=0);
    tr.style.display='none'; if(ok){hits.push(tr);}
  }
  var pages=Math.max(1,Math.ceil(hits.length/size)), cur=Math.min(window.RdpvPage||1,pages); window.RdpvPage=cur;
  for(var j=0;j<hits.length;j++){ if(j>=(cur-1)*size&&j<cur*size){hits[j].style.display='';} }
  if(nomatch){nomatch.style.display=(all>0&&hits.length===0)?'':'none';}
  var a=hits.length?(cur-1)*size+1:0, b=Math.min(cur*size,hits.length);
  var rg=document.getElementById('rdpv-range');
  if(rg){rg.innerHTML=RdpvShowing.replace('%a','<b>'+a+'</b>').replace('%b','<b>'+b+'</b>').replace('%n','<b>'+hits.length+'</b>');}
  var cn=document.getElementById('rdpv-count'); if(cn){cn.textContent=hits.length+' / '+all;}
  var f=document.querySelectorAll('.rdpv-f');
  for(var k=0;k<f.length;k++){
    var on=t===f[k].getAttribute('data-v');
    f[k].className=f[k].className.replace(/ rdpv-f-on/g,'')+(on?' rdpv-f-on':''); f[k].setAttribute('aria-pressed',on?'true':'false');
  }
  var pg=document.getElementById('rdpv-pg'), h='';
  if(pages>1){
    var btn=function(p,lbl,dis,on){ return '<button type=\"button\"'+(dis?' disabled':'')+(on?' class=\"rdpv-pg-on\" aria-current=\"page\"':'')+' onclick=\"window.RdpvPage='+p+';RdpvApply()\">'+lbl+'</button>'; };
    h+=btn(cur-1,'&lsaquo;',cur==1,false);
    var last=0;
    for(var p=1;p<=pages;p++){
      if(p==1||p==pages||Math.abs(p-cur)<=1){ if(last&&p-last>1){h+='<span>&hellip;</span>';} h+=btn(p,p,false,p==cur); last=p; }
    }
    h+=btn(cur+1,'&rsaquo;',cur==pages,false);
  }
  if(pg){pg.innerHTML=h;}
}
(function(){
  var f=document.querySelectorAll('.rdpv-f');
  for(var i=0;i<f.length;i++){ f[i].onclick=function(){ window.RdpvT=this.getAttribute('data-v'); window.RdpvPage=1; RdpvApply(); }; }
  RdpvApply();
  if(typeof jQuery==='undefined'){return;}
  var run=function(){
    jQuery('.rdpv-g-days').peity('bar',{height:44,width:112,padding:0.25,fill:['#26a69a']});
    jQuery('.rdpv-g-size').peity('line',{height:40,width:112,fill:'rgba(40,99,139,.15)',stroke:'#28638b',strokeWidth:2});
    jQuery('.rdpv-g-types').peity('donut',{radius:28,innerRadius:19,fill:['#00504a','#28638b','#6e7977']});
  };
  if(jQuery.fn.peity){run();}else{ var sc=document.createElement('script'); sc.src='js/jquery.peity.min.js'; sc.onload=run; document.head.appendChild(sc); }
})();
NoSpinner();
</script>";
}

function rdpv_style():string{
    if(isset($GLOBALS["RDPV_STYLE"])){return "";}
    $GLOBALS["RDPV_STYLE"]=true;
    return "<style>
.rdpv{display:flex;flex-direction:column;gap:18px;margin:12px 0 20px;color:#111d23}
.rdpv-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.rdpv-mono{font-family:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
.rdpv-dim{color:#64748b}.rdpv-strong{font-weight:600;color:#111d23}.rdpv-nw{white-space:nowrap}.rdpv-r{text-align:right}
.rdpv-strip{background:#fff7ed;border:1px solid #fcd9a8;border-radius:12px}
.rdpv-strip summary{display:flex;align-items:center;gap:12px;padding:10px 16px;cursor:pointer;list-style:none;flex-wrap:wrap}
.rdpv-strip summary::-webkit-details-marker{display:none}
.rdpv-strip summary:focus-visible{outline:2px solid #006a62;outline-offset:2px;border-radius:12px}
.rdpv-strip-ico{width:32px;height:32px;border-radius:8px;background:#ffddb9;color:#663e00;display:inline-flex;align-items:center;justify-content:center}
.rdpv-strip-t{font-weight:700;color:#663e00;font-size:14px}
.rdpv-strip-d{color:#3e4947;font-size:13px;flex:1 1 320px}
.rdpv-strip-ok{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;background:#d1f5ef;color:#00504a;font-size:12px;font-weight:700;white-space:nowrap}
.rdpv-strip-more{font-size:12px;font-weight:600;color:#3e4947;white-space:nowrap}
.rdpv-strip[open] .rdpv-strip-more i{transform:rotate(180deg)}
.rdpv-strip-body{padding:0 16px 12px 60px;font-size:13px;color:#3e4947;line-height:1.5}
.rdpv-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:18px}
.rdpv-kpi{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:18px 20px;min-height:120px}
.rdpv-kpi-b{display:flex;flex-direction:column;gap:6px;min-width:0}
.rdpv-kpi-l{font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#3e4947}
.rdpv-kpi-v{font-size:30px;font-weight:700;line-height:1;font-variant-numeric:tabular-nums;white-space:nowrap}
.rdpv-kpi-types .rdpv-kpi-v{display:none}
.rdpv-kpi-types .rdpv-kpi-s{flex-direction:column;gap:4px}
.rdpv-kpi-s{font-size:12px;color:#3e4947;display:flex;flex-wrap:wrap;gap:4px 10px}
.rdpv-kpi-art{flex:0 0 auto}
.rdpv-kpi-ico{width:44px;height:44px;border-radius:12px;background:#f1f5f9;color:#475569;display:inline-flex;align-items:center;justify-content:center;font-size:18px}
.rdpv-ico-ok{background:#e6f4f2;color:#006a62}.rdpv-ico-warn{background:#ffddb9;color:#663e00}
.rdpv-kpi-warn{background:#fffbeb;border-color:#fcd34d}
.rdpv-good{color:#065f46;font-weight:600}.rdpv-warn-v{color:#865300}
.rdpv-lg{display:inline-flex;align-items:center;gap:6px;white-space:nowrap;font-family:'JetBrains Mono',ui-monospace,Menlo,Consolas,monospace}
.rdpv-dot{width:8px;height:8px;border-radius:50%;display:inline-block}
.rdpv-c-video{background:#00504a}.rdpv-c-ssh{background:#28638b}.rdpv-c-both{background:#6e7977}
.rdpv-tops{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.rdpv-top{padding:16px 20px}
.rdpv-top-h{display:flex;justify-content:space-between;align-items:baseline;gap:10px;margin-bottom:8px}
.rdpv-top-t{font-size:16px;font-weight:600;color:#111d23}
.rdpv-top-t i{color:#006a62;margin-right:6px}
.rdpv-top-n{font:12px ui-monospace,Menlo,Consolas,monospace;color:#3e4947}
.rdpv-top-l{display:flex;flex-direction:column;gap:2px}
.rdpv-top-r{display:grid;grid-template-columns:minmax(0,180px) 1fr 48px;align-items:center;gap:14px;border:0;background:transparent;padding:5px 6px;border-radius:6px;text-align:left;cursor:pointer;color:#111d23;font-size:13px}
.rdpv-top-r:hover{background:#f1f5f9}
.rdpv-top-r:focus-visible{outline:2px solid #006a62;outline-offset:1px}
.rdpv-top-k{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600}
.rdpv-top-c{text-align:right;font:12px ui-monospace,Menlo,Consolas,monospace;color:#3e4947}
.rdpv-bar{height:7px;border-radius:999px;background:#e3f0f8;overflow:hidden}
.rdpv-bar span{display:block;height:100%;border-radius:999px}
.rdpv-bar-u span{background:#00504a}.rdpv-bar-t span{background:#28638b}
.rdpv-ret{font-size:13px;color:#3e4947;padding:0 4px}
.rdpv-ret i{margin-right:6px;color:#475569}
.rdpv-tbl-card{overflow:hidden}
.rdpv-tools{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:14px 18px;border-bottom:1px solid #e2e8f0;flex-wrap:wrap}
.rdpv-tools-l{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.rdpv-search{position:relative;width:320px;margin:0;font-weight:400}
.rdpv-search i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#64748b;font-size:13px}
.rdpv-search input{width:100%;height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px 0 34px;font-size:13px;color:#111d23;background:#fff}
.rdpv-search input:focus{outline:2px solid #006a62;outline-offset:1px;border-color:#006a62}
.rdpv-seg{display:inline-flex;gap:4px;padding:4px;border-radius:10px;background:#f1f5f9;border:1px solid #e2e8f0}
.rdpv-f{border:0;background:transparent;border-radius:7px;padding:6px 12px;font-size:13px;font-weight:600;color:#334155;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
.rdpv-f-on{background:#fff;color:#111d23;box-shadow:0 1px 2px rgba(15,23,42,.18)}
.rdpv-f:focus-visible,.rdpv-btn:focus-visible,.rdpv-pg button:focus-visible{outline:2px solid #006a62;outline-offset:2px}
.rdpv-count{font:12px ui-monospace,Menlo,Consolas,monospace;color:#3e4947}
.rdpv-scroll{overflow-x:auto}
.rdpv-tbl{width:100%;border-collapse:collapse;font-size:13px;color:#111d23}
.rdpv-tbl thead th{background:#f8fafc;color:#3e4947;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;text-align:left;padding:11px 14px;border-bottom:1px solid #e2e8f0;white-space:nowrap}
.rdpv-tbl thead th.rdpv-r{text-align:right}
.rdpv-tbl td{padding:11px 14px;border-top:1px solid #f1f5f9;vertical-align:middle;color:#111d23}
.rdpv-tbl tbody tr:first-child td{border-top:0}
.rdpv-tbl tbody tr:hover td{background:#f5f8fa}
.rdpv-c2{display:flex;flex-direction:column;gap:2px;min-width:0}
.rdpv-who{font-size:14px;font-weight:600;color:#111d23;white-space:nowrap}
.rdpv-file{font-size:11px;color:#64748b;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rdpv-sub{font-size:12px;color:#3e4947}
.rdpv-dbar{display:inline-block;vertical-align:middle;width:56px;height:5px;margin-left:8px;border-radius:999px;background:#e2e8f0;overflow:hidden}
.rdpv-dbar span{display:block;height:100%;background:#00504a;border-radius:999px}
.rdpv-rule{display:inline-block;padding:3px 10px;border-radius:999px;background:#eef2f6;color:#111d23;font-size:12px;white-space:nowrap}
.rdpv-chips{display:flex;gap:6px;flex-wrap:wrap}
.rdpv-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:700;white-space:nowrap}
.rdpv-chip-v{background:#d1f5ef;color:#00504a}.rdpv-chip-s{background:#dbeafe;color:#1d4ed8}.rdpv-chip-i{background:#ffddb9;color:#663e00}
.rdpv-acts{display:inline-flex;gap:6px;justify-content:flex-end;flex-wrap:nowrap}
.rdpv-btn{display:inline-flex;align-items:center;gap:6px;border:1px solid #cbd5e1;background:#fff;color:#111d23;border-radius:8px;padding:5px 10px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap}
.rdpv-btn:hover{border-color:#006a62;color:#00504a}
.rdpv-btn-p{background:#00504a;border-color:#00504a;color:#fff}
.rdpv-btn-p:hover{background:#006a62;color:#fff}
.rdpv-btn-del{margin-left:10px;width:32px;justify-content:center;padding:5px 0;border-color:#f1a6a6;color:#b91c1c}
.rdpv-btn-del:hover{background:#fef2f2;border-color:#b91c1c;color:#b91c1c}
.rdpv-nomatch td{text-align:center;color:#3e4947;padding:22px}
.rdpv-empty{display:flex;flex-direction:column;align-items:center;gap:8px;padding:34px 20px;color:#3e4947;font-size:13px;text-align:center}
.rdpv-empty>i{font-size:28px;color:#94a3b8}
.rdpv-empty b{font-size:15px;color:#111d23}
.rdpv-foot{display:flex;align-items:center;gap:10px;padding:12px 18px;border-top:1px solid #e2e8f0;background:#f8fafc;font-size:13px;flex-wrap:wrap;color:#3e4947}
.rdpv-foot b{color:#111d23}
.rdpv-pg{margin-left:auto;display:flex;align-items:center;gap:6px}
.rdpv-pg button{min-width:32px;height:32px;border:1px solid #cbd5e1;background:#fff;border-radius:6px;cursor:pointer;font-size:13px;color:#111d23}
.rdpv-pg button.rdpv-pg-on{background:#00504a;border-color:#00504a;color:#fff}
.rdpv-pg button[disabled]{opacity:.45;cursor:default}
@media (max-width:1200px){.rdpv-tops{grid-template-columns:1fr}}
</style>";
}

// ------------------------------------------------------------ trace SSH (lecture seule)
// Une fenetre par enregistrement : le journal des commandes (pagine par next_offset), la
// transcription du terminal (morceaux du .ssh.cast) et, depuis le lot 3, le journal des
// transferts de fichiers (.ssh.files.jsonl, pagine comme les commandes). Pas de lecteur asciicast : celui que
// la console embarque (xterm.js) INTERPRETE les sequences du terminal distant, alors qu'une
// transcription est une donnee non fiable. Le texte est donc nettoye (rdpproxy_terminal_text)
// puis echappe, et affiche brut ; le zip reste la copie integrale.
// Le contenu des transcriptions ne passe jamais par _ENGINE_parse_body : seuls les libelles.

// Taille en octets lisible : FormatBytes prend des Ko et les tronque en entier
// (14 o affichait « 0 KB », 293 Kio « 292 KB »).
function rdpssh_size($bytes):string{
    $b=max(0,floatval($bytes));
    if($b<1024){return intval($b)."&nbsp;B";}
    foreach(array("KB"=>1024,"MB"=>1048576,"GB"=>1073741824) as $u=>$d){
        if($b<$d*1024 || $u=="GB"){return round($b/$d,1)."&nbsp;$u";}
    }
    return intval($b)."&nbsp;B";
}
function rdpssh_id():int{
    return intval(isset($_GET["id"])?$_GET["id"]:0);
}
function rdpssh_file():string{
    return rdpproxy_ssh_transcript_file(isset($_GET["file"])?$_GET["file"]:"");
}
function rdpssh_view($ID):string{
    return "rdpssh-view-$ID";
}

// rdpssh_error : message traduit ; fichier trop volumineux => bouton du zip ; lecteurs occupes
// (503 transcript_busy) => bouton « Reessayer » ($retry = JS qui recharge la meme vue).
// Un texte qui n'est pas un de nos jetons (transport, daemon ancien) est echappe et ses
// accolades et « || » neutralises : div_error et _ENGINE_parse_body ne l'interpretent pas.
function rdpssh_error($tpl,$ID,$code,$retry=""):string{
    $page=CurrentPageName();
    $msg=rdpproxy_ssh_transcript_error($code);
    if(!preg_match('/^\{rdpproxy_[a-z0-9_]+\}$/',$msg)){
        $msg=str_replace(array("{","}","||"),array("&#123;","&#125;","&#124;&#124;"),htmlspecialchars($msg,ENT_QUOTES));
    }
    $html=$tpl->div_error($msg);
    $k=strtolower(trim((string)$code));
    if(rdpproxy_ssh_transcript_needs_zip($code)){
        $html.="<div style='margin-top:10px'><a class='btn btn-primary btn-sm' href=\"javascript:blur();\" onclick=\"s_PopUpFull('$page?download=$ID','1024','900');\"><i class='fas fa-download'></i> {rdpproxy_ssh_download_zip}</a></div>";
    }
    if($k=="transcript_busy" && $retry!=""){
        $html.="<div style='margin-top:10px'><a class='btn btn-default btn-sm' href=\"javascript:blur();\" onclick=\"$retry\"><i class='fas fa-rotate-right'></i> {rdpproxy_ssh_retry}</a></div>";
    }
    return $tpl->_ENGINE_parse_body($html);
}

function rdpssh_nav_button($js,$ico,$label,$active=false):string{
    $cls=$active?"btn btn-primary btn-sm":"btn btn-default btn-sm";
    return "<a class='$cls' style='margin:0 6px 6px 0' href=\"javascript:blur();\" onclick=\"$js\"><i class='$ico'></i> $label</a>";
}

function ssh_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!rdpv_allowed()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_GET["ssh-js"]);
    $tpl->js_dialog2("{rdpproxy_ssh_trace_title}","$page?ssh-popup=yes&id=$ID",1100);
}

function ssh_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!rdpv_allowed()){echo rdpv_denied_html();return;}
    $ID=rdpssh_id();
    $view=rdpssh_view($ID);
    $html=array();
    $html[]=$tpl->div_explain("{rdpproxy_ssh_trace_title}||{rdpproxy_ssh_trace_explain}");
    $err="";
    $r=rdpproxy_video_ssh($ID,$err);
    if($err!=""){
        echo $tpl->_ENGINE_parse_body(@implode("\n",$html)).rdpssh_error($tpl,$ID,$err);
        return;
    }
    if(!empty($r["interrupted"])){
        $html[]="<p><span class='label label-warning'>{rdpproxy_video_interrupted}</span></p>";
    }
    $first="";
    $nav=array();
    $castDl=array();
    $seen=array("commands"=>0,"cast"=>0,"files"=>0);
    foreach($r["Transcripts"] as $tr){
        if(!is_array($tr)){continue;}
        $name=rdpproxy_ssh_transcript_file(isset($tr["name"])?$tr["name"]:"");
        $kind=isset($tr["kind"])?(string)$tr["kind"]:"";
        if($name=="" || !isset($seen[$kind])){continue;}
        $seen[$kind]++;
        $size=rdpssh_size(isset($tr["size"])?$tr["size"]:0);
        $f=urlencode($name);
        if($kind=="commands"){
            $js="LoadAjax('$view','$page?ssh-commands=yes&id=$ID&file=$f&offset=0');";
            $label="{rdpproxy_ssh_commands}";
            $ico="fas fa-list";
        }elseif($kind=="files"){
            $js="LoadAjax('$view','$page?ssh-files=yes&id=$ID&file=$f&offset=0');";
            $label="{rdpproxy_ssh_files}";
            $ico="fas fa-file-arrow-up";
        }else{
            $js="LoadAjax('$view','$page?ssh-cast=yes&id=$ID&file=$f&offset=0');";
            $label="{rdpproxy_ssh_transcript}";
            $ico="fas fa-terminal";
            $castDl[]=array("s_PopUpFull('$page?ssh-cast-download=yes&id=$ID&file=$f','1024','900');",$seen[$kind]);
        }
        // Transcriptions de repli (<base>.<pid>.ssh.*) : deux sessions dans la meme seconde.
        if($seen[$kind]>1){$label.=" ({rdpproxy_ssh_additional} ".($seen[$kind]-1).")";}
        $nav[]=rdpssh_nav_button($js,$ico,"$label <small>$size</small>");
        if($first=="" && $kind=="commands"){$first=$js;}
    }
    if($first=="" && count($nav)>0){
        foreach($r["Transcripts"] as $tr){
            $name=rdpproxy_ssh_transcript_file(isset($tr["name"])?$tr["name"]:"");
            $kind=isset($tr["kind"])?(string)$tr["kind"]:"";
            if($name=="" || ($kind!="cast" && $kind!="files")){continue;}
            $act=$kind=="files"?"ssh-files":"ssh-cast";
            $first="LoadAjax('$view','$page?$act=yes&id=$ID&file=".urlencode($name)."&offset=0');";
            break;
        }
    }
    foreach($castDl as $dl){
        $nav[]=rdpssh_nav_button($dl[0],"fas fa-file-arrow-down","{rdpproxy_ssh_download_cast}".($dl[1]>1?" ({rdpproxy_ssh_additional} ".($dl[1]-1).")":""));
    }
    $nav[]=rdpssh_nav_button("s_PopUpFull('$page?download=$ID','1024','900');","fas fa-download","{rdpproxy_ssh_download_zip}");
    if(count($r["Transcripts"])==0){
        $html[]=$tpl->div_warning("{rdpproxy_ssh_no_transcript}");
    }
    $html[]="<div style='margin:10px 0'>".@implode("",$nav)."</div>";
    $html[]="<div id='$view'></div>";
    if($first!=""){$html[]="<script>$first</script>";}
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

// rdpssh_pager : boutons de page ; $prev/$next < 0 = absent.
function rdpssh_pager($tpl,$base,$offset,$prev,$next):string{
    $view=rdpssh_view(rdpssh_id());
    $b=array();
    if($offset>0){
        $b[]=rdpssh_nav_button("LoadAjax('$view','$base&offset=0');","fas fa-backward-fast","{rdpproxy_ssh_first_page}");
    }
    if($prev>=0 && $prev<$offset && $prev>0){
        $b[]=rdpssh_nav_button("LoadAjax('$view','$base&offset=$prev');","fas fa-chevron-left","{previous}");
    }
    if($next>=0){
        $b[]=rdpssh_nav_button("LoadAjax('$view','$base&offset=$next');","fas fa-chevron-right","{next_page}");
    }
    if(count($b)==0){return "";}
    return $tpl->_ENGINE_parse_body("<div style='margin-top:10px'>".@implode("",$b)."</div>");
}

function ssh_commands(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!rdpv_allowed()){echo rdpv_denied_html();return;}
    $ID=rdpssh_id();
    $file=rdpssh_file();
    $offset=max(0,intval(isset($_GET["offset"])?$_GET["offset"]:0));
    // 50 commandes : chacune peut porter jusqu'a 32 Kio de sortie.
    $limit=50;
    $err="";
    $r=rdpproxy_video_ssh_commands($ID,$file,$offset,$limit,$err);
    if($err!=""){
        $view=rdpssh_view($ID);
        echo rdpssh_error($tpl,$ID,$err,"LoadAjax('$view','$page?ssh-commands=yes&id=$ID&file=".urlencode($file)."&offset=$offset');");
        return;
    }

    $Lrefused=$tpl->_ENGINE_parse_body("{rdpproxy_ssh_cmd_refused}");
    $Linter=$tpl->_ENGINE_parse_body("{rdpproxy_ssh_cmd_interactive}");
    $Lout=$tpl->_ENGINE_parse_body("{rdpproxy_ssh_cmd_output}");
    $Ltrunc=$tpl->_ENGINE_parse_body("{rdpproxy_ssh_cmd_output_truncated}");
    $rows=array();
    foreach($r["Commands"] as $c){
        if(!is_array($c)){continue;}
        $ms=isset($c["t"])?floatval($c["t"]):0;
        $when=$ms>0?htmlspecialchars($tpl->time_to_date(intval($ms/1000),true)):"-";
        $line=rdpproxy_terminal_html(isset($c["line"])?$c["line"]:"");
        $st="";
        if(!empty($c["refused"])){$st.="<span class='label label-danger'>$Lrefused</span> ";}
        if(!empty($c["interactive"])){$st.="<span class='label label-info'>$Linter</span>";}
        $out="<span class='awc-muted'>&ndash;</span>";
        $head=isset($c["out_head"])?(string)$c["out_head"]:"";
        if($head!==""){
            $txt=rdpproxy_terminal_html($head);
            $n=substr_count(rdpproxy_terminal_text($head),"\n")+1;
            $tr=!empty($c["truncated"])?"<div class='awc-muted' style='font-size:12px'>$Ltrunc</div>":"";
            $out="<details><summary style='cursor:pointer'>$Lout ($n)</summary>"
                ."<pre style='max-height:300px;overflow:auto;white-space:pre-wrap;word-break:break-all;margin:6px 0 0 0'>$txt</pre>$tr</details>";
        }elseif(!empty($c["truncated"])){
            $out="<span class='awc-muted'>$Ltrunc</span>";
        }
        $rows[]="<tr><td style='white-space:nowrap;vertical-align:top'>$when</td>"
            ."<td style='vertical-align:top'><code style='white-space:pre-wrap;word-break:break-all'>$line</code></td>"
            ."<td style='white-space:nowrap;vertical-align:top'>$st</td>"
            ."<td style='vertical-align:top;min-width:240px'>$out</td></tr>";
    }

    $head=array();
    $head[]="<div class='awc-wrap' style='margin:0'><div class='awc-card'><div class='awc-scroll'>";
    $head[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $head[]="<th>{time}</th><th>{command}</th><th>{status}</th><th>{rdpproxy_ssh_cmd_output}</th>";
    $head[]="</tr></thead><tbody>";
    $foot=array();
    $foot[]="</tbody></table></div>";
    $foot[]="<div class='awc-foot'><span>".count($rows)." {rdpproxy_ssh_commands}</span></div>";
    $foot[]="</div></div>";
    $html=$tpl->_ENGINE_parse_body(@implode("",$head));
    if(count($rows)==0){
        $html.=$tpl->_ENGINE_parse_body("<tr><td colspan='4'><div class='awc-empty'>{rdpproxy_ssh_no_command}</div></td></tr>");
    }else{
        $html.=@implode("\n",$rows);
    }
    $html.=$tpl->_ENGINE_parse_body(@implode("",$foot));
    $malformed=intval(isset($r["malformed"])?$r["malformed"]:0);
    if($malformed>0){
        $html.=$tpl->div_warning(sprintf($tpl->_ENGINE_parse_body("{rdpproxy_ssh_malformed}"),$malformed));
    }
    $next=isset($r["next_offset"])?intval($r["next_offset"]):-1;
    $base="$page?ssh-commands=yes&id=$ID&file=".urlencode($file);
    $html.=rdpssh_pager($tpl,$base,$offset,max(0,$offset-$limit),$next);
    echo awc_table_style().$html."<script>NoSpinner();</script>";
}

// ssh_files : une page du journal des transferts de fichiers (lot 3). Chemin et raison viennent
// de la session distante : nettoyes et echappes (rdpproxy_terminal_html), jamais passes par
// _ENGINE_parse_body. L'empreinte est montree abregee, entiere au survol.
function ssh_files(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!rdpv_allowed()){echo rdpv_denied_html();return;}
    $ID=rdpssh_id();
    $file=rdpssh_file();
    $offset=max(0,intval(isset($_GET["offset"])?$_GET["offset"]:0));
    $limit=100;
    $err="";
    $r=rdpproxy_video_ssh_files($ID,$file,$offset,$limit,$err);
    if($err!=""){
        $view=rdpssh_view($ID);
        echo rdpssh_error($tpl,$ID,$err,"LoadAjax('$view','$page?ssh-files=yes&id=$ID&file=".urlencode($file)."&offset=$offset');");
        return;
    }
    $dirs=array("up"=>array("fas fa-arrow-up","{rdpproxy_ssh_file_up}"),"down"=>array("fas fa-arrow-down","{rdpproxy_ssh_file_down}"));
    $statuses=array("ok"=>"label-primary","failed"=>"label-danger","denied"=>"label-danger","cancelled"=>"label-warning","partial"=>"label-warning");
    $L=array();
    foreach(array_merge(array_keys($statuses),array("up","down")) as $k){
        $tok=isset($dirs[$k])?$dirs[$k][1]:"{rdpproxy_ssh_file_status_$k}";
        $L[$k]=$tpl->_ENGINE_parse_body($tok);
    }
    $rows=array();
    foreach($r["Files"] as $f){
        if(!is_array($f)){continue;}
        $ms=isset($f["t"])?floatval($f["t"]):0;
        $when=$ms>0?htmlspecialchars($tpl->time_to_date(intval($ms/1000),true)):"-";
        $d=isset($f["dir"])?(string)$f["dir"]:"";
        $dir=isset($dirs[$d])?"<i class='{$dirs[$d][0]}'></i> ".$L[$d]:"&ndash;";
        $path=rdpproxy_terminal_html(isset($f["path"])?$f["path"]:"");
        $size=rdpssh_size(isset($f["size"])?$f["size"]:0);
        $sum=isset($f["sha256"])?strtolower((string)$f["sha256"]):"";
        $sumHtml="<span class='awc-muted'>&ndash;</span>";
        if(preg_match('/^[0-9a-f]{64}$/',$sum)){
            $sumHtml="<code title='$sum'>".substr($sum,0,12)."&hellip;</code>";
        }
        $st=isset($f["status"])?(string)$f["status"]:"";
        $badge=isset($statuses[$st])?"<span class='label {$statuses[$st]}'>".$L[$st]."</span>":"&ndash;";
        $reason=rdpproxy_terminal_html(isset($f["reason"])?$f["reason"]:"");
        $rows[]="<tr><td style='width:1%;white-space:nowrap'>$when</td>"
            ."<td style='width:1%;white-space:nowrap'>$dir</td>"
            ."<td style='width:99%'><code style='white-space:pre-wrap;word-break:break-all'>$path</code></td>"
            ."<td style='width:1%;white-space:nowrap'>$size</td>"
            ."<td style='width:1%;white-space:nowrap'>$sumHtml</td>"
            ."<td style='width:1%;white-space:nowrap'>$badge</td>"
            ."<td style='min-width:160px'>$reason</td></tr>";
    }
    $head=array();
    $head[]="<div class='awc-wrap' style='margin:0'><div class='awc-card'><div class='awc-scroll'>";
    $head[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $head[]="<th>{time}</th><th>{direction}</th><th>{path}</th><th>{size}</th><th>SHA-256</th><th>{status}</th><th>{reason}</th>";
    $head[]="</tr></thead><tbody>";
    $foot=array();
    $foot[]="</tbody></table></div>";
    $foot[]="<div class='awc-foot'><span>".count($rows)." {rdpproxy_ssh_files}</span></div>";
    $foot[]="</div></div>";
    $html=$tpl->_ENGINE_parse_body(@implode("",$head));
    if(count($rows)==0){
        $html.=$tpl->_ENGINE_parse_body("<tr><td colspan='7'><div class='awc-empty'>{rdpproxy_ssh_no_file}</div></td></tr>");
    }else{
        $html.=@implode("\n",$rows);
    }
    $html.=$tpl->_ENGINE_parse_body(@implode("",$foot));
    $malformed=intval(isset($r["malformed"])?$r["malformed"]:0);
    if($malformed>0){
        $html.=$tpl->div_warning(sprintf($tpl->_ENGINE_parse_body("{rdpproxy_ssh_malformed}"),$malformed));
    }
    $next=isset($r["next_offset"])?intval($r["next_offset"]):-1;
    $base="$page?ssh-files=yes&id=$ID&file=".urlencode($file);
    $html.=rdpssh_pager($tpl,$base,$offset,max(0,$offset-$limit),$next);
    echo awc_table_style().$html."<script>NoSpinner();</script>";
}

function ssh_cast(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!rdpv_allowed()){echo rdpv_denied_html();return;}
    $ID=rdpssh_id();
    $file=rdpssh_file();
    $offset=max(0,intval(isset($_GET["offset"])?$_GET["offset"]:0));
    // 256 Kio par page : assez pour lire, assez peu pour que le navigateur reste fluide.
    $max=262144;
    $err="";
    $r=rdpproxy_video_ssh_cast($ID,$file,$offset,$max,$err);
    if($err!=""){
        $view=rdpssh_view($ID);
        echo rdpssh_error($tpl,$ID,$err,"LoadAjax('$view','$page?ssh-cast=yes&id=$ID&file=".urlencode($file)."&offset=$offset');");
        return;
    }
    $data=isset($r["data"])?(string)$r["data"]:"";
    $size=intval(isset($r["size"])?$r["size"]:0);
    $next=isset($r["next_offset"])?intval($r["next_offset"]):-1;
    $end=$offset+strlen($data);
    $first=-1;$last=-1;
    $txt=rdpproxy_terminal_html(rdpproxy_cast_output($data,$first,$last));

    $info=sprintf($tpl->_ENGINE_parse_body("{rdpproxy_ssh_bytes_range}"),
        number_format($offset,0,"."," "),number_format($end,0,"."," "),number_format($size,0,"."," "));
    if($first>=0){
        $info.=" &middot; ".gmdate("H:i:s",intval($first))." &rarr; ".gmdate("H:i:s",intval($last));
    }
    $html="<div class='awc-muted' style='margin-bottom:6px'>$info</div>";
    if(trim($txt)===""){
        $html.=$tpl->_ENGINE_parse_body("<div class='awc-empty'>{no_data}</div>");
    }else{
        $html.="<pre style='max-height:560px;overflow:auto;white-space:pre-wrap;word-break:break-all;background:#111d23;color:#e3f0f8;padding:12px;border-radius:8px'>$txt</pre>";
    }
    $base="$page?ssh-cast=yes&id=$ID&file=".urlencode($file);
    $html.=rdpssh_pager($tpl,$base,$offset,-1,$next);
    echo $html."<script>NoSpinner();</script>";
}

// ssh_cast_download : la transcription asciicast EXACTE (.ssh.cast), extraite du zip de
// l'enregistrement. Le daemon n'a pas de route pour servir l'entree brute (/ssh/cast rend un
// texte nettoye pour l'affichage) : la console lit le zip comme download(). Le nom doit
// figurer dans la liste /ssh du daemon (kind=cast) et etre une entree reguliere a la racine.
// Le fichier est servi en piece jointe (application/octet-stream), jamais affiche.
function ssh_cast_download(){
    if(!rdpv_allowed()){header("HTTP/1.1 403 Forbidden");header("Content-type: text/plain");echo "Forbidden";return;}
    $ID=rdpssh_id();
    $file=rdpssh_file();
    $err="";
    $r=rdpproxy_video_ssh($ID,$err);
    $ok=false;
    if($err=="" && $file!=""){
        foreach($r["Transcripts"] as $tr){
            if(is_array($tr) && isset($tr["name"],$tr["kind"]) && $tr["name"]===$file && $tr["kind"]==="cast"){$ok=true;break;}
        }
    }
    if(!$ok){header("HTTP/1.1 404 Not Found");header("Content-type: text/plain");echo "Not found";return;}
    if(!class_exists("ZipArchive")){header("HTTP/1.1 501 Not Implemented");header("Content-type: text/plain");echo "ZipArchive unavailable: download the recording archive";return;}
    $verr=""; $dir="";
    $ligne=rdpproxy_video($ID,$verr,$dir);
    $zipName=basename((string)(isset($ligne["path"])?$ligne["path"]:""));
    $zipFile="$dir/$zipName";
    if($verr!="" || $zipName=="" || !is_file($zipFile) || is_link($zipFile)){header("HTTP/1.1 404 Not Found");header("Content-type: text/plain");echo "Not found";return;}
    $zip=new ZipArchive();
    if($zip->open($zipFile)!==true){header("HTTP/1.1 500 Internal Server Error");header("Content-type: text/plain");echo "Unreadable archive";return;}
    $idx=$zip->locateName($file);
    $st=$idx===false?false:$zip->statIndex($idx);
    $regular=true;
    if($idx!==false && method_exists($zip,"getExternalAttributesIndex")){
        $opsys=0;$attr=0;
        if($zip->getExternalAttributesIndex($idx,$opsys,$attr) && $opsys==ZipArchive::OPSYS_UNIX){
            $type=($attr>>16)&0170000;
            $regular=($type==0 || $type==0100000);
        }
    }
    $fp=($st!==false && $st["name"]===$file && $regular)?$zip->getStream($file):false;
    if($fp===false){$zip->close();header("HTTP/1.1 404 Not Found");header("Content-type: text/plain");echo "Not found";return;}
    $connection_date=intval(isset($ligne["connection_date"])?$ligne["connection_date"]:0);
    header("Content-type: application/octet-stream");
    header("Content-Transfer-Encoding: binary");
    header("Content-Disposition: attachment; filename=\"{$connection_date}_$file\"");
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("X-Content-Type-Options: nosniff");
    if(isset($st["size"])){header("Content-Length: ".intval($st["size"]));}
    if(ob_get_level()>0){ob_clean();}
    flush();
    fpassthru($fp);
    fclose($fp);
    $zip->close();
}

// ------------------------------------------------ fichiers telecharges depuis une cible WEB
// Journal <base>.web.files.jsonl et copie de chaque fichier, lus par le daemon dans le zip
// (RDPProxy/http_handlers_web_files.go). Nom, raison et signature viennent de la session :
// echappes, accolades neutralisees, jamais passes par _ENGINE_parse_body. Un fichier infecte
// reste dans l'archive (preuve) mais n'est jamais servi : pas de bouton, et 403 cote daemon.

function web_files_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!rdpv_allowed()){$tpl->js_error("{ERROR_NO_PRIVS2}");return;}
    $ID=intval($_GET["web-js"]);
    $tpl->js_dialog2("{rdpproxy_web_files}","$page?web-popup=yes&id=$ID",1100);
}

function web_files_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!rdpv_allowed()){echo rdpv_denied_html();return;}
    $ID=rdpssh_id();
    $html=array();
    $html[]=$tpl->div_explain("{rdpproxy_web_files}||{rdpproxy_web_files_explain}");
    $html[]="<div style='margin-top:10px'>".$tpl->search_block($page,"","","","&web-files-search=yes&id=$ID")."</div>";
    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}

// Texte venu de la session : echappe et accolades neutralisees.
function rdpweb_h($v):string{
    $v=function_exists("rdpproxy_terminal_text")?rdpproxy_terminal_text((string)$v):(string)$v;
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,"UTF-8"));
}

// Horodatage du module : secondes ou millisecondes depuis l'epoque, ou texte (affiche tel quel).
function rdpweb_when($tpl,$t):string{
    $t=trim((string)$t);
    if($t===""){return "&ndash;";}
    if(is_numeric($t)){
        $v=floatval($t);
        if($v>100000000000){$v=$v/1000;}
        if($v<=0){return "&ndash;";}
        return htmlspecialchars($tpl->time_to_date(intval($v),true));
    }
    return rdpweb_h($t);
}

function web_files_search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(!rdpv_allowed()){echo rdpv_denied_html();return;}
    $ID=rdpssh_id();
    $search=trim((string)($_GET["search"] ?? ""));
    $err="";
    // Plafond du module : 100 fichiers par session ; 500 lignes suffisent a tout montrer.
    $r=rdpproxy_video_web_files($ID,0,500,$err);
    if($err!=""){
        $msg=rdpproxy_web_file_error($err);
        if(!preg_match('/^\{rdpproxy_[a-z0-9_]+\}$/',$msg)){$msg=rdpweb_h($msg);}
        echo $tpl->_ENGINE_parse_body($tpl->div_error($msg))."<script>NoSpinner();</script>";
        return;
    }
    $statuses=array("ready"=>array("label-info","{rdpproxy_web_file_status_ready}"),
        "delivered"=>array("label-primary","{delivered}"),
        "denied"=>array("label-danger","{denied}"),
        "failed"=>array("label-danger","{rdpproxy_ssh_file_status_failed}"),
        "cancelled"=>array("label-warning","{cancelled}"));
    $avs=array("clean"=>array("label-primary","{clean}"),"infected"=>array("label-danger","{infected}"),
        "skipped"=>array("label-default","{not_scanned}"),"error"=>array("label-warning","{error}"));
    $reasons=array("type_not_allowed","too_large","session_quota","infected","av_unavailable","disk","changed");
    $L=array();
    foreach(array_merge($statuses,$avs) as $k=>$v){$L[$v[1]]=$tpl->_ENGINE_parse_body($v[1]);}
    foreach($reasons as $k){$L[$k]=$tpl->_ENGINE_parse_body("{rdpproxy_web_file_reason_$k}");}
    $Lq=$tpl->_ENGINE_parse_body("{rdpproxy_web_files_quarantined}");
    $Ldl=$tpl->_ENGINE_parse_body("{download}");
    $rows=array();
    foreach($r["Files"] as $f){
        if(!is_array($f)){continue;}
        $name=(string)($f["name"] ?? "");
        $sum=strtolower((string)($f["sha256"] ?? ""));
        $sig=(string)($f["av_sig"] ?? "");
        if($search!=="" && stripos($name." ".$sum." ".$sig." ".($f["status"] ?? "")." ".($f["reason"] ?? ""),$search)===false){continue;}
        $n=intval($f["n"] ?? 0);
        $sumHtml="<span class='awc-muted'>&ndash;</span>";
        if(preg_match('/^[0-9a-f]{64}$/',$sum)){$sumHtml="<code title='$sum'>".substr($sum,0,12)."&hellip;</code>";}
        $s=(string)($f["status"] ?? "");
        $badge=isset($statuses[$s])?"<span class='label {$statuses[$s][0]}'>".$L[$statuses[$s][1]]."</span>":rdpweb_h($s);
        $reason=(string)($f["reason"] ?? "");
        if($reason!==""){$badge.="<div class='awc-muted' style='font-size:12px;margin-top:3px'>".(isset($L[$reason])?$L[$reason]:rdpweb_h($reason))."</div>";}
        $a=(string)($f["av"] ?? "");
        $av=isset($avs[$a])?"<span class='label {$avs[$a][0]}'>".$L[$avs[$a][1]]."</span>":"<span class='awc-muted'>&ndash;</span>";
        $sigHtml=$sig!==""?"<code style='white-space:pre-wrap;word-break:break-all'>".rdpweb_h($sig)."</code>":"<span class='awc-muted'>&ndash;</span>";
        if($a==="infected"){
            $act="<span class='text-danger' style='display:inline-block;max-width:180px;white-space:normal;font-size:12px'><i class='fas fa-shield-virus'></i> $Lq</span>";
        }elseif($n>0 && in_array($s,array("ready","delivered"),true)){
            $act="<button type='button' class='btn btn-default btn-xs' onclick=\"s_PopUpFull('$page?web-file-download=yes&id=$ID&n=$n','1024','900');\"><i class='fas fa-download'></i> $Ldl</button>";
        }else{
            $act="<span class='awc-muted'>&ndash;</span>";
        }
        $rows[]="<tr><td style='width:1%;white-space:nowrap'>".rdpweb_when($tpl,$f["t"] ?? "")."</td>"
            ."<td style='width:99%;min-width:200px'><code style='white-space:pre-wrap;overflow-wrap:anywhere'>".rdpweb_h($name)."</code></td>"
            ."<td style='width:1%;white-space:nowrap'>".rdpssh_size($f["size"] ?? 0)."</td>"
            ."<td style='width:1%;white-space:nowrap'>$sumHtml</td>"
            ."<td style='width:1%;white-space:nowrap'>$badge</td>"
            ."<td style='width:1%;white-space:nowrap'>$av</td>"
            ."<td style='min-width:140px'>$sigHtml</td>"
            ."<td style='width:1%'>$act</td></tr>";
    }
    $head="<div class='awc-wrap' style='margin:0'><div class='awc-card'><div class='awc-scroll'>"
        ."<table class='awc-tbl awc-tbl-compact'><thead><tr>"
        ."<th>{time}</th><th>{name}</th><th>{size}</th><th>SHA-256</th><th>{status}</th><th>{verdict}</th><th>{signature}</th><th></th>"
        ."</tr></thead><tbody>";
    $html=$tpl->_ENGINE_parse_body($head);
    if(count($rows)==0){
        $html.=$tpl->_ENGINE_parse_body("<tr><td colspan='8'><div class='awc-empty'>{rdpproxy_web_files_none}</div></td></tr>");
    }else{
        $html.=@implode("\n",$rows);
    }
    $html.=$tpl->_ENGINE_parse_body("</tbody></table></div><div class='awc-foot'><span>".count($rows)." {rdpproxy_web_files}</span></div></div></div>");
    $malformed=intval($r["malformed"] ?? 0);
    if($malformed>0){
        $html.=$tpl->div_warning(sprintf($tpl->_ENGINE_parse_body("{rdpproxy_ssh_malformed}"),$malformed));
    }
    echo awc_table_style().$html."<script>NoSpinner();</script>";
}

// web_file_download : la copie du fichier n, relayee depuis le daemon (qui refuse un fichier
// infecte et audite la lecture). Aucun chemin ne transite par la console.
function web_file_download(){
    if(!rdpv_allowed()){header("HTTP/1.1 403 Forbidden");header("Content-type: text/plain");echo "Forbidden";return;}
    $ID=rdpssh_id();
    $n=intval($_GET["n"] ?? 0);
    list($ok,$err)=rdpproxy_video_web_file_stream($ID,$n);
    if($ok){
        admin_tracks("Downloaded the copy of web file #$n of RDS Proxy recording #$ID");
        return;
    }
    $tpl=new template_admin();
    $msg=rdpproxy_web_file_error($err);
    if(!preg_match('/^\{rdpproxy_[a-z0-9_]+\}$/',$msg)){$msg=rdpweb_h($msg);}
    $k=strtolower(trim((string)$err));
    if($k==="quarantined"){header("HTTP/1.1 403 Forbidden");}
    elseif($k==="bad_n" || $k==="no_web_file" || $k==="video not found" || $k==="video_file_missing"){header("HTTP/1.1 404 Not Found");}
    else{header("HTTP/1.1 502 Bad Gateway");}
    header("Content-type: text/html; charset=UTF-8");
    echo "<!DOCTYPE html><html><body style='font-family:sans-serif;padding:20px'>".$tpl->_ENGINE_parse_body($tpl->div_error($msg))."</body></html>";
}
