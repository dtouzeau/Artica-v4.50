<?php
// Service Pack 9 2026-10-10
include_once(dirname(__FILE__)."/ressources/settings.exists.inc"); // artica_setting_exists(): settings are root-only, see the file
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.autofs.inc");


if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_POST["InfluxAdminRetentionTime"])){Save();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["parameters-flat"])){parameters_flat();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_GET["tab"])){tabs();exit;}
if(isset($_GET["dash"])){ret_dashboard_inner();exit;}

if(isset($_GET["clean-now"])){clean_now_js();exit;}
if(isset($_GET["clean-now-popup"])){clean_now_popup();exit;}
if(isset($_GET["clean-after-save"])){clean_after_save();exit;}
if(isset($_POST["CleanNow"])){$_SESSION["CleanNow"]=$_POST["CleanNow"];exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["search"])){search_events();exit;}
if(isset($_GET["js"])){js();exit;}

page();


function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{retentions}","$page?tab=yes&bypopup=yes",850);
}
function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}
function page(){
	$page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{retentions}","fa fa-archive",
        "{retentions_explain}","$page?tab=yes","logs-retentions",
    "progress-logrotate-restart",false,"table-loader");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{legal_logs}",$html);
		echo $tpl->build_firewall();
		return;
	}


	echo $tpl->_ENGINE_parse_body($html);

}

/** "&bypopup=yes" quand la page vit dans la fenetre ouverte par ?js=yes. */
function ret_bypopup():string{
    return isset($_GET["bypopup"])?"&bypopup=yes":"";
}

/** JS de la fenetre de modification des durees. */
function ret_edit_js():string{
    $page=CurrentPageName();
    return "Loadjs('$page?parameters-js=yes".ret_bypopup()."')";
}

/** Valeurs de retention, avec les valeurs imposees hors licence Corporate. */
function parameters_values():array{
    $v=array();
    $v["InfluxAdminRetentionTime"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminRetentionTime"));
    $v["SystemEventsRetentionTime"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemEventsRetentionTime"));
    $v["PDNSStatsRetentionTime"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsRetentionTime"));
    if($v["InfluxAdminRetentionTime"]==0){$v["InfluxAdminRetentionTime"]=365;}
    if($v["SystemEventsRetentionTime"]==0){$v["SystemEventsRetentionTime"]=15;}
    if($v["PDNSStatsRetentionTime"]==0){$v["PDNSStatsRetentionTime"]=5;}
    $size=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsRotateDefaultSizeRotation");
    if(!is_numeric($size)){$size=100;}
    $v["LogsRotateDefaultSizeRotation"]=intval($size);
    $v["corp"]=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();
    if(!$v["corp"]){
        $v["InfluxAdminRetentionTime"]=5;
        $v["SystemEventsRetentionTime"]=7;
        $v["PDNSStatsRetentionTime"]=5;
    }
    return $v;
}

/* ---------------------------------------------------------------------------
 * Journal clean-db (/var/log/clean-postgres.log, lu par articarest)
 * Ligne : "Oct 31 05:30:00 hote clean-db[67382]: CleanDB.go[fonction:220] message"
 * ------------------------------------------------------------------------- */

/** Niveau d'une ligne : ok (suppression ou maintenance terminee), warn (verrou SQL), err, info. */
function ret_line_level($msg):string{
    if(preg_match('#busy, skipped#i',$msg)){return "warn";}
    if(preg_match('#\b(error|fatal|failed)\b#i',$msg)){return "err";}
    if(preg_match('#(removed [0-9]+ rows|cleaned [0-9]+ records|finish after)#i',$msg)){return "ok";}
    return "info";
}

/** Lignes brutes -> entrees datees, de la plus recente a la plus ancienne. */
function ret_parse_lines($lines):array{
    $out=array();
    if(!is_array($lines)){return $out;}
    $now=time();
    foreach($lines as $line){
        $line=trim((string)$line);
        $ts=false; $pid=0; $msg="";
        if(preg_match('#^([A-Z][a-z]{2})\s+([0-9]{1,2})\s+([0-9]{2}:[0-9]{2}:[0-9]{2})\s+\S+\s+clean-db\[([0-9]+)\]:\s*(.*)$#',$line,$re)){
            $ts=strtotime("{$re[1]} {$re[2]} ".date("Y",$now)." {$re[3]}");
            // Le syslog classique ne donne pas l'annee : une date future est de l'an dernier.
            if($ts!==false && $ts>$now+86400){$ts=strtotime("-1 year",$ts);}
            $pid=intval($re[4]); $msg=$re[5];
        }elseif(preg_match('#^([0-9]{4}-[0-9]{2}-[0-9]{2})[T ]([0-9]{2}:[0-9]{2}:[0-9]{2})\S*\s+\S+\s+clean-db\[([0-9]+)\]:\s*(.*)$#',$line,$re)){
            $ts=strtotime("{$re[1]} {$re[2]}");
            $pid=intval($re[3]); $msg=$re[4];
        }
        if($ts===false || $ts<=0){continue;}
        $where="";
        if(preg_match('#^(\S+\.go\[[^\]]*\])\s+(.*)$#',$msg,$m)){$where=$m[1];$msg=$m[2];}
        $rows=0; $table="";
        if(preg_match('#:\s*(\S+) removed ([0-9]+) rows#i',$msg,$m)){$table=$m[1];$rows=intval($m[2]);}
        elseif(preg_match('#:\s*(\S+) cleaned ([0-9]+) records#i',$msg,$m)){$table=$m[1];$rows=intval($m[2]);}
        $secs=0.0;
        if(preg_match('#finish after ([0-9.]+) seconds#i',$msg,$m)){$secs=floatval($m[1]);}
        $out[]=array("ts"=>$ts,"pid"=>$pid,"msg"=>$msg,"where"=>$where,
            "level"=>ret_line_level($msg),"rows"=>$rows,"table"=>$table,"secs"=>$secs);
    }
    usort($out,function($a,$b){ return $b["ts"]-$a["ts"]; });
    return $out;
}

/** Tout le journal (1500 lignes au plus), pour les mesures du tableau de bord. */
function ret_log_entries():array{
    if(isset($GLOBALS["RET_ENTRIES"])){return $GLOBALS["RET_ENTRIES"];}
    $tpl=new template_admin();
    $GLOBALS["RET_ENTRIES"]=ret_parse_lines($tpl->logfile_search("clean-postgres",array("MAX"=>1500),false,1500));
    return $GLOBALS["RET_ENTRIES"];
}

/** Regroupe les lignes en passages : meme PID et moins de 30 min d'ecart. Plus recent d'abord. */
function ret_runs($entries):array{
    $asc=array_reverse($entries);
    $runs=array(); $cur=null; $last=0; $lastpid=-1;
    foreach($asc as $e){
        if($cur===null || $e["pid"]<>$lastpid || $e["ts"]-$last>1800){
            if($cur!==null){$runs[]=$cur;}
            $cur=array("start"=>$e["ts"],"end"=>$e["ts"],"lines"=>0,"ok"=>0,"err"=>0,"warn"=>0,"info"=>0,
                "rows"=>0,"secs"=>0.0,"tables"=>array());
        }
        $cur["end"]=$e["ts"];
        $cur["lines"]++;
        $cur[$e["level"]]++;
        $cur["rows"]+=$e["rows"];
        $cur["secs"]+=$e["secs"];
        if($e["table"]<>""){$cur["tables"][$e["table"]]=true;}
        $last=$e["ts"]; $lastpid=$e["pid"];
    }
    if($cur!==null){$runs[]=$cur;}
    return array_reverse($runs);
}

/** Date lisible : "Aujourd'hui 05:30", "Hier 05:30" ou "2026-10-28 05:30". */
function ret_when($ts):string{
    $day=date("Y-m-d",$ts);
    if($day==date("Y-m-d")){return "{today} ".date("H:i",$ts);}
    if($day==date("Y-m-d",strtotime("-1 day"))){return "{yesterday} ".date("H:i",$ts);}
    return date("Y-m-d H:i",$ts);
}

function ret_num($n):string{
    return number_format(floatval($n),0,","," ");
}

/* ---------------------------------------------------------------------------
 * Tableau de bord : un widget = une fonction
 * ------------------------------------------------------------------------- */

/** Contenu de ?tab=yes : un hote rechargeable apres chaque enregistrement. */
function tabs(){
    echo "<div id='ret-dash'>";
    ret_dashboard_inner();
    echo "</div>";
}

function ret_dashboard_inner(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $bypopup=ret_bypopup();
    $v=parameters_values();
    $entries=ret_log_entries();
    $runs=ret_runs($entries);

    $html=array();
    $html[]="<div class='ret-wrap'>";
    if(!$v["corp"]){$html[]=ret_license_banner();}
    $html[]=ret_kpi_row($v);
    $html[]=ret_scale_card($v);
    $html[]="<div class='ret-cols'>";
    $html[]=ret_last_run_card($runs);
    $html[]=ret_summary_card($entries,$runs);
    $html[]="</div>";
    $html[]="</div>";

    $tabs=array();
    $tabs["{parameters}"]="$page?parameters=yes&embedded=yes$bypopup";
    $tabs["{events} (".count($entries).")"]="$page?events=yes$bypopup";

    echo ret_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
    echo "<div class='ret-tabs'>".$tpl->tabs_default($tabs)."</div>";
    echo ret_peity_js();
    if($bypopup==""){echo "<script>".ret_tiny()."</script>";}
}

/** En-tete de page : titre, explication et boutons. */
function ret_tiny():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $topbuttons=array();
    $topbuttons[]=array(ret_edit_js(),ico_edit,"{ret_edit_durations}");
    $topbuttons[]=array("Loadjs('$page?clean-now=yes')",ico_trash,"{clean_databases}");
    $topbuttons[]=array("LoadAjaxSilent('ret-dash','$page?dash=yes')",ico_refresh,"{refresh}");
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{retentions}";
    $TINY_ARRAY["ICO"]="fa fa-archive";
    $TINY_ARRAY["EXPL"]="{retentions_explain}";
    $TINY_ARRAY["URL"]="logs-retentions";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
}

/** Bandeau : sans licence Corporate, les durees sont imposees. */
function ret_license_banner():string{
    $lic_page=$GLOBALS["CLASS_SOCKETS"]->USE_LICENSE_V2()?"fw.licensev2.php":"fw.license.php";
    $js="if(typeof dialogInstance1=='object'){dialogInstance1.close();}LoadAjax('MainContent','$lic_page');";
    return "<div class='ret-lic' role='note'><span class='ret-lic-ico'><i class='fas fa-lock'></i></span>"
        ."<p>{ret_license_notice}</p>"
        ."<a href='javascript:blur();' class='ret-lic-a' onclick=\"$js\">{ret_license_more} <i class='fas fa-arrow-right'></i></a></div>";
}

/** Les 4 cartes de mesure (une par reglage). */
function ret_kpi_row($v):string{
    $h="<div class='ret-kpis'>";
    $h.=ret_kpi_card("{StatisticsRetentionPeriod}",$v["InfluxAdminRetentionTime"],"{days}","{ret_kpi_stats_sub}",true,"fas fa-chart-column");
    $h.=ret_kpi_card("{PDNSStatsRetentionTime}",$v["PDNSStatsRetentionTime"],"{days}","{ret_kpi_dns_sub}",true,"fas fa-database");
    $h.=ret_kpi_card("{SystemEventsRetentionTime}",$v["SystemEventsRetentionTime"],"{days}","{ret_kpi_events_sub}",true,"far fa-clock");
    $h.=ret_kpi_card("{remove_if_files_exceed}",$v["LogsRotateDefaultSizeRotation"],"MB","{ret_kpi_size_sub}",false,"far fa-file-lines");
    $h.="</div>";
    return $h;
}

/** Une carte : libelle, valeur, aide, et anneau peity (duree rapportee a 365 jours). */
function ret_kpi_card($label,$value,$unit,$sub,$isDays,$ico):string{
    $js=ret_edit_js();
    $value=intval($value);
    $art="<span class='ret-kpi-ico' aria-hidden='true'><i class='$ico'></i></span>";
    if($isDays){
        $f=min($value,365);
        $art="<span class='ret-g-donut' role='img' aria-label='$value {days} / 365'>$f/365</span>";
    }
    return "<div class='ret-kpi' role='button' tabindex='0' onclick=\"$js\" onkeydown=\"if(event.keyCode==13||event.keyCode==32){event.preventDefault();$js}\">"
        ."<div class='ret-kpi-body'><span class='ret-kpi-l'>$label</span>"
        ."<span class='ret-kpi-v'>".ret_num($value)."<small>$unit</small></span>"
        ."<span class='ret-kpi-s'>$sub</span></div>"
        ."<div class='ret-kpi-art'>$art<span class='ret-kpi-edit' aria-hidden='true'><i class='fas fa-pen'></i></span></div></div>";
}

/** Echelle de conservation : les 3 durees sur une meme frise. */
function ret_scale_card($v):string{
    $rows=array(
        array("{ret_stats_general}",$v["InfluxAdminRetentionTime"],"1"),
        array("{ret_stats_dns}",$v["PDNSStatsRetentionTime"],".7"),
        array("{ret_system_events}",$v["SystemEventsRetentionTime"],".5"),
    );
    $max=365;
    foreach($rows as $r){ if($r[1]>$max){$max=$r[1];} }
    $h="<div class='ak-card ret-card'><div class='ret-card-h'><span class='ret-card-t'><i class='fas fa-bolt'></i> {ret_scale_title}</span>"
        ."<span class='ak-mono ret-muted'>{ret_scale_max} $max {days}</span></div>";
    $h.="<div class='ret-scale'>";
    foreach($rows as $r){
        $pct=round(max(1,min(100,$r[1]/$max*100)),2);
        $h.="<div class='ret-scale-r'><span class='ret-scale-l'>{$r[0]}</span>"
            ."<span class='ret-scale-track'><span class='ret-scale-bar' style='width:$pct%;opacity:{$r[2]}'></span></span>"
            ."<span class='ret-scale-v'>".intval($r[1])." {days}</span></div>";
    }
    $h.="<div class='ret-ticks'>";
    foreach(array(0,30,90,180,365) as $t){
        if($t>$max){continue;}
        $pos=round($t/$max*100,2);
        $h.="<span style='left:$pos%'>$t</span>";
    }
    $h.="</div></div>";
    $h.="<div class='ret-note'><i class='fas fa-hard-drive'></i> {ret_scale_disk}</div></div>";
    return $h;
}

/** Dernier passage du nettoyage + histogramme des 14 derniers passages. */
function ret_last_run_card($runs):string{
    $h="<div class='ak-card ret-card ret-last'><div class='ret-card-h'><div><span class='ret-over'>{ret_task_status}</span>";
    if(count($runs)==0){
        $h.="<h3 class='ret-h3'>{last_run}</h3></div></div>";
        $h.="<div class='ret-empty'><i class='far fa-calendar-xmark'></i> {ret_no_run}</div></div>";
        return $h;
    }
    $r=$runs[0];
    $bad=($r["err"]+$r["warn"])>0;
    $pill=$bad
        ?"<span class='ak-pill ak-err'><i class='fas fa-triangle-exclamation'></i> {ret_state_err}</span>"
        :"<span class='ak-pill ak-ok'><i class='fas fa-check'></i> {ret_state_ok}</span>";
    $h.="<h3 class='ret-h3'>{last_run} : ".ret_when($r["start"])."</h3></div>$pill</div>";

    $h.="<div class='ret-last-mid'><div class='ret-last-txt'>";
    $h.="<p><b class='ak-mono'>".ret_num($r["rows"])."</b> {ret_rows_removed}";
    if($r["secs"]>0){$h.=" &middot; {ret_maintenance_time} : <b class='ak-mono'>".round($r["secs"],1)." s</b>";}
    $h.="</p>";
    if(count($r["tables"])>0){
        $t=array_keys($r["tables"]);
        $h.="<p class='ret-muted'>{tables} : <span class='ak-mono'>".htmlspecialchars(implode(", ",$t),ENT_QUOTES)."</span></p>";
    }
    $h.="</div>".ret_runs_bars($runs)."</div>";

    $next=$r["start"]+86400;
    $h.="<div class='ret-last-foot'><span class='ret-muted'>{ret_next_run} <b>".ret_when($next)."</b></span>"
        ."<button type='button' class='ak-btn' onclick=\"RetShowEvents()\">{ret_view_log} <i class='fas fa-arrow-right'></i></button></div>";
    $h.="</div>";
    return $h;
}

/** Histogramme peity des 14 derniers passages (lignes supprimees, ou 1 barre par passage). */
function ret_runs_bars($runs):string{
    $last=array_reverse(array_slice($runs,0,14));
    $vals=array(); $states=array(); $sum=0; $bad=0;
    foreach($last as $r){ $sum+=$r["rows"]; }
    foreach($last as $r){
        $vals[]=$sum>0?max(intval($r["rows"]),0):1;
        $isBad=($r["err"]+$r["warn"])>0;
        if($isBad){$bad++;}
        $states[]=$isBad?"err":"ok";
    }
    $n=count($last);
    $cap="$n {ret_last_runs}";
    if($bad>0){$cap.=" &middot; $bad {ret_state_err}";}
    if($sum==0){$cap.=" &middot; {ret_no_rows_removed}";}
    $w=max(48,min(220,$n*16));
    return "<div class='ret-bars'><span class='ret-g-bars' data-w='$w' data-st='".implode(",",$states)."' role='img' aria-label='$n {ret_last_runs}'>".implode(",",$vals)."</span>"
        ."<span class='ret-bars-cap'>$cap</span></div>";
}

/** Synthese du journal : donut succes / erreurs / informations. */
function ret_summary_card($entries,$runs):string{
    $ok=0;$err=0;$info=0;$lock=0;
    foreach($entries as $e){
        if($e["level"]=="ok"){$ok++;continue;}
        if($e["level"]=="err"){$err++;continue;}
        if($e["level"]=="warn"){$err++;$lock++;continue;}
        $info++;
    }
    $total=count($entries);
    $h="<div class='ak-card ret-card ret-sum'><div class='ret-card-h'><span class='ret-card-t'>{ret_summary_title}</span>";
    $nruns=count($runs);
    if($nruns>0){
        $good=0;
        foreach($runs as $r){ if(($r["err"]+$r["warn"])==0){$good++;} }
        $rate=round($good/$nruns*100);
        $cls=$rate==100?"ak-ok":"ak-warn";
        $h.="<span class='ak-chip $cls'>$rate % {ret_runs_ok_rate}</span>";
    }
    $h.="</div>";
    if($total==0){
        $h.="<div class='ret-empty'><i class='far fa-file-lines'></i> {ret_no_run}</div></div>";
        return $h;
    }
    $h.="<div class='ret-sum-mid'><div class='ret-sum-donut'><span class='ret-g-sum' role='img' aria-label='$ok {ret_legend_ok}, $err {ret_legend_err}, $info {ret_legend_info}'>$ok,$err,$info</span>"
        ."<b class='ak-mono'>$total</b></div><ul class='ret-legend'>"
        ."<li><span class='ak-dot ak-ok'></span>{ret_legend_ok}<b class='ak-mono'>$ok</b></li>"
        ."<li><span class='ak-dot ak-err'></span>{ret_legend_err}<b class='ak-mono'>$err</b></li>"
        ."<li><span class='ak-dot ak-info'></span>{ret_legend_info}<b class='ak-mono'>$info</b></li></ul></div>";
    if($lock==0){
        $h.="<p class='ret-note'><i class='fas fa-lock-open'></i> {ret_no_lock}</p>";
    }else{
        $h.="<p class='ret-note ret-note-warn'><i class='fas fa-lock'></i> $lock {ret_locks}</p>";
    }
    $h.="</div>";
    return $h;
}

/* ---------------------------------------------------------------------------
 * Onglet Parametres
 * ------------------------------------------------------------------------- */

/** Onglet Parametres. Hors tableau de bord (onglet de fw.artica.backup.php), garde son en-tete. */
function parameters(){
    echo ret_style()."<div id='retention-parameters'>";
    parameters_flat();
    echo "</div>";
    if(!isset($_GET["bypopup"]) && !isset($_GET["embedded"])) {
        $tpl=new template_admin();
        $page=CurrentPageName();
        $topbuttons=array();
        $topbuttons[]=array(ret_edit_js(),ico_edit,"{ret_edit_durations}");
        $topbuttons[]=array("Loadjs('$page?clean-now=yes')",ico_trash,"{clean_databases}");
        $TINY_ARRAY["TITLE"] = "{statistics_retention_parameters}";
        $TINY_ARRAY["ICO"] = "fa fa-archive";
        $TINY_ARRAY["EXPL"] = "{statistics_retention_explain}";
        $TINY_ARRAY["URL"] = "logs-retentions";
        $TINY_ARRAY["BUTTONS"] = $tpl->table_buttons($topbuttons);
        echo "<script>Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');</script>";
    }
}

/** Les deux groupes de reglages ; chaque ligne ouvre la fenetre de modification. */
function parameters_flat(){
    $tpl=new template_admin();
    $v=parameters_values();
    $h=ret_settings_group("{statistics_retention_parameters}","{ret_scale_disk}",
        ret_settings_row("fas fa-chart-column","{StatisticsRetentionPeriod} ({general})","{ret_desc_stats}",$v["InfluxAdminRetentionTime"]." {days}")
        .ret_settings_row("fas fa-database","{PDNSStatsRetentionTime}","{PDNSStatsRetentionTime_explain}",$v["PDNSStatsRetentionTime"]." {days}"));
    $h.=ret_settings_group("{logs_retention_parameters}","{logs_retention_parameters_explain}",
        ret_settings_row("far fa-clock","{SystemEventsRetentionTime}","{ret_desc_events}",$v["SystemEventsRetentionTime"]." {days}")
        .ret_settings_row("far fa-folder","{remove_if_files_exceed}","{ret_desc_size}",$v["LogsRotateDefaultSizeRotation"]." MB"));
    if(isset($_GET["bypopup"]) && !isset($_GET["embedded"])){
        $page=CurrentPageName();
        $h.="<div class='ret-actions'>".$tpl->button_autnonome("{clean_databases}","Loadjs('$page?clean-now=yes')",ico_trash,"AsWebStatisticsAdministrator",0,"btn-danger")."</div>";
    }
    echo $tpl->_ENGINE_parse_body("<div class='ret-settings'>$h</div>");
}

function ret_settings_group($title,$explain,$rows):string{
    return "<section class='ak-card ret-grp'><header><h4>$title</h4><p>$explain</p></header><div class='ret-grp-rows'>$rows</div></section>";
}

function ret_settings_row($ico,$label,$desc,$value):string{
    $js=ret_edit_js();
    return "<div class='ret-row' role='button' tabindex='0' onclick=\"$js\" onkeydown=\"if(event.keyCode==13||event.keyCode==32){event.preventDefault();$js}\">"
        ."<span class='ret-row-ico' aria-hidden='true'><i class='$ico'></i></span>"
        ."<span class='ret-row-txt'><b>$label</b><small>$desc</small></span>"
        ."<span class='ret-row-v'>$value</span>"
        ."<span class='ret-row-edit' aria-hidden='true'><i class='fas fa-pen'></i></span></div>";
}

function parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog5("{ret_edit_durations}","$page?parameters-popup=yes".ret_bypopup(),650);
}

/** Formulaire de la fenetre : enregistre, ferme, puis recharge le tableau de bord. */
function parameters_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $v=parameters_values();
    $bypopup=ret_bypopup();

    $form[]=$tpl->field_numeric("InfluxAdminRetentionTime","{StatisticsRetentionPeriod} ({general}) ({days})",$v["InfluxAdminRetentionTime"]);
    $form[]=$tpl->field_numeric("PDNSStatsRetentionTime","{PDNSStatsRetentionTime} ({days})",
        $v["PDNSStatsRetentionTime"],"{PDNSStatsRetentionTime_explain}");
    $form[]=$tpl->field_section("{logs_retention_parameters}","{logs_retention_parameters_explain}");
    $form[]=$tpl->field_numeric("SystemEventsRetentionTime","{SystemEventsRetentionTime} ({days})",$v["SystemEventsRetentionTime"]);
    $form[]=$tpl->field_numeric("LogsRotateDefaultSizeRotation",
        "{remove_if_files_exceed} (MB)", $v["LogsRotateDefaultSizeRotation"],null);

    $jsafter="dialogInstance5.close();"
        ."if(document.getElementById('ret-dash')){LoadAjaxSilent('ret-dash','$page?dash=yes$bypopup');}"
        ."else if(document.getElementById('retention-parameters')){LoadAjaxSilent('retention-parameters','$page?parameters-flat=yes$bypopup');}";
    $html=array();
    if(!$v["corp"]){
        $html[]="<div class='ret-lic' role='note'><span class='ret-lic-ico'><i class='fas fa-lock'></i></span><p>{ret_license_notice}</p></div>";
    }
    $html[]=$tpl->form_outside("",$form,null,"{apply}",$jsafter,"AsSystemAdministrator");
    echo ret_style().$tpl->_ENGINE_parse_body($html).ret_presets_js();
    return true;
}

/** Raccourcis de duree sous les champs en jours de la fenetre. */
function ret_presets_js():string{
    $tpl=new template_admin();
    $lbl=json_encode($tpl->javascript_parse_text("{ret_shortcuts}"));
    $days=json_encode($tpl->javascript_parse_text("{days}"));
    return "<script>
(function(){
  var P={InfluxAdminRetentionTime:[7,30,90,365],PDNSStatsRetentionTime:[5,15,30],SystemEventsRetentionTime:[7,15,30]};
  function add(name,list){
    var inp=document.querySelector('input[name='+name+']'); if(!inp){return;}
    var box=document.createElement('div'); box.className='ret-presets'; box.setAttribute('role','group'); box.setAttribute('aria-label',$lbl);
    var t=document.createElement('span'); t.textContent=$lbl+' :'; box.appendChild(t);
    list.forEach(function(d){
      var b=document.createElement('button'); b.type='button'; b.className='ret-preset'; b.textContent=d+' '+$days.toLowerCase();
      if(parseInt(inp.value,10)===d){b.className+=' ret-preset-on';}
      b.onclick=function(){
        inp.value=d; if(window.jQuery){jQuery(inp).trigger('change');}
        var all=box.querySelectorAll('.ret-preset'); for(var i=0;i<all.length;i++){all[i].className='ret-preset';}
        b.className='ret-preset ret-preset-on';
      };
      box.appendChild(b);
    });
    var host=inp.closest?(inp.closest('.form-group')||inp.parentNode):inp.parentNode;
    host.appendChild(box);
  }
  for(var n in P){ if(P.hasOwnProperty(n)){ add(n,P[n]); } }
})();
</script>";
}

/* ---------------------------------------------------------------------------
 * Purge
 * ------------------------------------------------------------------------- */

function clean_now_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog6("{clean_databases}", "$page?clean-now-popup=yes",550);
}
function clean_now_popup():bool{
    $tpl=new template_admin();
    $v=parameters_values();
    $days=intval($v["InfluxAdminRetentionTime"]);

    $after="dialogInstance6.close();if(document.getElementById('ret-dash')){LoadAjaxSilent('ret-dash','".CurrentPageName()."?dash=yes');}";
    $jsrestart=$tpl->framework_buildjs("/postgresql/cleandb",
        "squid.statistics.purge.progress","squid.statistics.purge.progress.txt",
        "clean-database-progress",$after);

    $html[]="<div class='ret-purge'><span class='ret-purge-ico'><i class='fas fa-triangle-exclamation'></i></span>";
    $html[]="<div><h3>{clean_databases}</h3><p>{ret_purge_warning}</p>";
    $html[]="<p class='ret-purge-days'>{StatisticsRetentionPeriod} : <b class='ak-mono'>$days {days}</b></p></div></div>";
    $html[]="<div id='clean-database-progress'></div>";
    $html[]="<div class='ret-actions'>".$tpl->button_autnonome("{purge_statistics_database}",$jsrestart,ico_trash,"AsWebStatisticsAdministrator",0,"btn-danger")."</div>";

    echo ret_style().$tpl->_ENGINE_parse_body($html);
    return true;
}

/* ---------------------------------------------------------------------------
 * Onglet Evenements
 * ------------------------------------------------------------------------- */

function events(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page);
    echo "</div>";
}

function search_events(){
    $tpl        = new template_admin();
    $search     = trim(strtolower((string)($_GET["search"] ?? "")));
    $MAIN       = $tpl->format_search_protocol($search);
    $entries    = ret_parse_lines($tpl->logfile_search("clean-postgres",$MAIN));

    $c=array("all"=>count($entries),"ok"=>0,"err"=>0,"info"=>0);
    foreach($entries as $e){
        $k=$e["level"]=="warn"?"err":$e["level"];
        $c[$k]++;
    }
    $words=array("ok"=>"{ret_state_ok}","err"=>"{ret_state_err}","warn"=>"{ret_state_lock}","info"=>"{ret_state_info}");
    $icons=array("ok"=>"fas fa-circle-check","err"=>"fas fa-circle-xmark","warn"=>"fas fa-lock","info"=>"fas fa-circle-info");

    $h=array();
    $h[]="<div class='ak-seg ret-ev-bar' role='group' aria-label='{events}'>";
    foreach(array("all"=>"{all}","ok"=>"{ret_state_ok}","err"=>"{ret_state_err}","info"=>"{ret_state_info}") as $k=>$lbl){
        $on=$k=="all"?" on":"";
        $pressed=$k=="all"?"true":"false";
        $h[]="<button type='button' class='ak-seg-b ret-seg$on' aria-pressed='$pressed' onclick=\"RetEvFilter('$k',this)\">$lbl <b>{$c[$k]}</b></button>";
    }
    $h[]="</div>";
    $h[]="<table class='ret-ev' id='ret-ev-tbl'><thead><tr><th style='width:170px'>{date}</th><th style='width:90px'>PID</th><th>{events}</th></tr></thead><tbody>";
    if(count($entries)==0){
        $h[]="<tr><td colspan='3' class='ret-ev-empty'>{ret_no_events}</td></tr>";
    }
    $band=0; $lastpid=-1; $lastts=0;
    foreach($entries as $e){
        if($e["pid"]<>$lastpid || abs($lastts-$e["ts"])>1800){$band=1-$band;}
        $lastpid=$e["pid"]; $lastts=$e["ts"];
        $lv=$e["level"];
        $st=$lv=="warn"?"err":$lv;
        $msg=htmlspecialchars($e["msg"],ENT_QUOTES);
        $title=htmlspecialchars($e["where"],ENT_QUOTES);
        $h[]="<tr data-st='$st' class='ret-ev-$lv'>"
            ."<td class='ak-mono'>".date("Y-m-d H:i:s",$e["ts"])."</td>"
            ."<td class='ak-mono ret-band-$band'>{$e["pid"]}</td>"
            ."<td><span class='ret-ev-st'><i class='{$icons[$lv]}' aria-hidden='true'></i> {$words[$lv]}</span> <span class='ret-ev-msg' title='$title'>$msg</span></td></tr>";
    }
    $h[]="</tbody></table>";
    echo ret_style().$tpl->_ENGINE_parse_body($h)."<script>
function RetEvFilter(st,btn){
  var rows=document.querySelectorAll('#ret-ev-tbl tbody tr[data-st]');
  for(var i=0;i<rows.length;i++){ rows[i].style.display=(st==='all'||rows[i].getAttribute('data-st')===st)?'':'none'; }
  var b=btn.parentNode.querySelectorAll('.ret-seg');
  for(var j=0;j<b.length;j++){ b[j].className='ak-seg-b ret-seg'; b[j].setAttribute('aria-pressed','false'); }
  btn.className='ak-seg-b ret-seg on'; btn.setAttribute('aria-pressed','true');
}
NoSpinner();
</script>";
}

/* ---------------------------------------------------------------------------
 * Graphiques et style
 * ------------------------------------------------------------------------- */

function ret_accents():array{
    $a=array("accent"=>"#10b981","accent_dark"=>"#0d9488","accent_soft"=>"rgba(16,185,129,.12)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $b=brand_ui_accents(brand_palette());
        foreach(array("accent","accent_dark","accent_soft") as $k){ if(!empty($b[$k])){$a[$k]=$b[$k];} }
    }
    return $a;
}

/** peity 3.3.0 (js/jquery.peity.min.js), charge a la demande si le shell ne l'a pas. */
function ret_peity_js():string{
    $a=ret_accents();
    $A=json_encode($a["accent"]);
    return "<script>
function RetShowEvents(){
  var a=jQuery('.ret-tabs .nav-tabs a'); if(a.length>1){ a.eq(1).click(); }
  var t=document.querySelector('.ret-tabs'); if(t && t.scrollIntoView){ t.scrollIntoView({behavior:'smooth',block:'start'}); }
}
(function(){
  if(typeof jQuery==='undefined'){return;}
  var run=function(){
    var \$=jQuery;
    \$('.ret-g-donut').peity('donut',{radius:22,innerRadius:15,fill:[$A,'#e2e8f0']});
    \$('.ret-g-sum').peity('donut',{radius:34,innerRadius:24,fill:[$A,'#b91c1c','#60a5fa']});
    \$('.ret-g-bars').each(function(){
      var st=(this.getAttribute('data-st')||'').split(',');
      \$(this).peity('bar',{height:40,width:parseInt(this.getAttribute('data-w')||'220',10),padding:0.25,fill:function(v,i){ return st[i]==='err'?'#d97706':$A; }});
    });
  };
  if(jQuery.fn.peity){run();return;}
  var sc=document.createElement('script'); sc.src='js/jquery.peity.min.js'; sc.onload=run; document.head.appendChild(sc);
})();
</script>";
}

function ret_style():string{
    if(isset($GLOBALS["RET_STYLE_DONE"])){return "";}
    $GLOBALS["RET_STYLE_DONE"]=true;
    $a=ret_accents();
    $ac=$a["accent"]; $dk=$a["accent_dark"]; $soft=$a["accent_soft"];
    return "<style>
.ret-wrap{display:flex;flex-direction:column;gap:20px;margin:14px 0 20px}
.ret-muted{color:#3d4947;font-size:12px}
.ret-lic{display:flex;align-items:center;gap:12px;padding:12px 16px;border:1px solid #fcd34d;border-radius:12px;background:#fef3c7;color:#422006;font-size:13px;margin-bottom:12px}
.ret-wrap .ret-lic{margin-bottom:0}
.ret-lic p{margin:0;flex:1 1 auto;line-height:1.5}
.ret-lic-ico{width:30px;height:30px;border-radius:50%;background:#fde68a;color:#92400e;display:inline-flex;align-items:center;justify-content:center;flex:0 0 30px}
.ret-lic-a{color:#78350f;font-weight:600;white-space:nowrap;text-decoration:none}
.ret-lic-a:hover,.ret-lic-a:focus-visible{text-decoration:underline;color:#451a03}
.ret-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:20px}
.ret-kpi{display:flex;justify-content:space-between;gap:12px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px;box-shadow:0 1px 2px rgba(15,23,42,.06);cursor:pointer;transition:border-color .15s,box-shadow .15s;animation:retIn .15s ease-out both}
.ret-kpi:hover{border-color:$ac;box-shadow:0 4px 12px rgba(15,23,42,.08)}
.ret-kpi:focus-visible,.ret-row:focus-visible,.ret-preset:focus-visible{outline:2px solid $dk;outline-offset:2px}
.ret-kpi-body{display:flex;flex-direction:column;gap:6px;min-width:0}
.ret-kpi-l{font-size:13px;font-weight:600;color:#3d4947;line-height:1.3}
.ret-kpi-v{font-size:32px;font-weight:700;color:#111d23;line-height:1;font-variant-numeric:tabular-nums}
.ret-kpi-v small{font-size:14px;font-weight:600;color:#3d4947;margin-left:6px}
.ret-kpi-s{font-size:12px;color:#3d4947}
.ret-kpi-art{display:flex;flex-direction:column;align-items:center;justify-content:space-between;gap:6px}
.ret-kpi-ico{width:44px;height:44px;border-radius:50%;background:#f1f5f9;color:#475569;display:inline-flex;align-items:center;justify-content:center;font-size:18px}
.ret-kpi-edit,.ret-row-edit{color:$dk;opacity:0;transition:opacity .15s;font-size:13px}
.ret-kpi:hover .ret-kpi-edit,.ret-kpi:focus-visible .ret-kpi-edit,.ret-row:hover .ret-row-edit,.ret-row:focus-visible .ret-row-edit{opacity:1}
.ret-card{padding:20px 24px}
.ret-card-h{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}
.ret-card-t{font-size:15px;font-weight:600;color:#111d23}
.ret-card-t i{color:$dk;margin-right:6px}
.ret-scale{display:flex;flex-direction:column;gap:12px}
.ret-scale-r{display:flex;align-items:center;gap:14px;font-size:13px}
.ret-scale-l{width:180px;flex:0 0 180px;color:#111d23;font-weight:500}
.ret-scale-track{flex:1 1 auto;height:12px;border-radius:999px;background:#eef2f6;overflow:hidden}
.ret-scale-bar{display:block;height:100%;border-radius:999px;background:$ac;animation:retGrow .4s ease-out both;transform-origin:left}
.ret-scale-v{width:90px;flex:0 0 90px;text-align:right;font-family:ui-monospace,Menlo,Consolas,monospace;font-weight:600;font-size:12px;color:#111d23;background:$soft;border-radius:6px;padding:2px 8px}
.ret-ticks{position:relative;height:18px;margin:2px 104px 0 194px;border-top:1px solid #e2e8f0;font:11px ui-monospace,Menlo,Consolas,monospace;color:#64748b}
.ret-ticks span{position:absolute;top:3px;transform:translateX(-50%)}
.ret-note{display:flex;align-items:center;gap:8px;margin:14px 0 0;padding:9px 12px;border-radius:8px;background:#f8fafc;border:1px solid #eef2f6;font-size:12px;color:#3d4947}
.ret-note-warn{background:#fef3c7;border-color:#fcd34d;color:#422006}
.ret-cols{display:grid;grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:20px}
.ret-over{display:block;font-size:11px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#3d4947}
.ret-h3{margin:4px 0 0;font-size:18px;font-weight:700;color:#111d23}
.ret-last-mid{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:14px 0;border-top:1px solid #eef2f6;border-bottom:1px solid #eef2f6}
.ret-last-txt p{margin:0 0 4px;font-size:13px;color:#3d4947}
.ret-last-txt b{color:#111d23;font-size:14px}
.ret-bars{display:flex;flex-direction:column;align-items:flex-end;gap:4px}
.ret-bars svg{background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:4px}
.ret-bars-cap{font:11px ui-monospace,Menlo,Consolas,monospace;color:#64748b}
.ret-last-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;padding-top:12px}
.ret-sum-mid{display:flex;align-items:center;gap:22px}
.ret-sum-donut{position:relative;width:68px;height:68px;flex:0 0 68px}
.ret-sum-donut b{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:13px;color:#111d23}
.ret-legend{list-style:none;margin:0;padding:0;flex:1 1 auto;display:flex;flex-direction:column;gap:6px;font-size:13px;color:#3d4947}
.ret-legend li{display:flex;align-items:center;gap:8px}
.ret-legend b{margin-left:auto;color:#111d23}
.ret-empty{padding:18px 0;color:#3d4947;font-size:13px}
.ret-empty i{margin-right:6px;color:#64748b}
.ret-tabs{margin-top:4px}
.ret-settings{display:flex;flex-direction:column;gap:20px;margin:16px 0}
.ret-grp header{padding:12px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0}
.ret-grp h4{margin:0;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#111d23}
.ret-grp header p{margin:3px 0 0;font-size:12px;color:#3d4947;line-height:1.5}
.ret-row{display:flex;align-items:center;gap:14px;padding:14px 20px;border-top:1px solid #f1f5f9;cursor:pointer;transition:background .15s}
.ret-grp-rows .ret-row:first-child{border-top:0}
.ret-row:hover{background:#f8fafc}
.ret-row-ico{width:34px;height:34px;border-radius:8px;background:$soft;color:$dk;display:inline-flex;align-items:center;justify-content:center;flex:0 0 34px}
.ret-row-txt{flex:1 1 auto;display:flex;flex-direction:column;gap:2px;min-width:0}
.ret-row-txt b{font-size:14px;font-weight:600;color:#111d23}
.ret-row-txt small{font-size:12px;color:#3d4947;line-height:1.45}
.ret-row-v{font:700 13px ui-monospace,Menlo,Consolas,monospace;color:#111d23;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:6px 12px;white-space:nowrap}
.ret-actions{margin:16px 0 4px;text-align:right}
.ret-presets{display:flex;align-items:center;gap:6px;margin-top:6px;font-size:12px;color:#3d4947}
.ret-preset{border:1px solid #e2e8f0;background:#f1f5f9;color:#111d23;border-radius:6px;padding:2px 9px;font:12px ui-monospace,Menlo,Consolas,monospace;cursor:pointer}
.ret-preset:hover{border-color:$ac}
.ret-preset-on{background:$soft;border-color:$ac;color:#111d23;font-weight:700}
.ret-purge{display:flex;gap:16px;align-items:flex-start;margin:4px 0 12px}
.ret-purge-ico{width:46px;height:46px;border-radius:12px;background:#fee2e2;color:#b91c1c;display:inline-flex;align-items:center;justify-content:center;font-size:20px;flex:0 0 46px}
.ret-purge h3{margin:0 0 6px;font-size:17px;font-weight:700;color:#991b1b}
.ret-purge p{margin:0 0 6px;font-size:13px;color:#3d4947;line-height:1.5}
.ret-purge-days b{color:#111d23}
.ret-ev-bar{margin:12px 0}
.ret-seg b{font-family:ui-monospace,Menlo,Consolas,monospace;margin-left:4px}
.ret-ev{width:100%;border-collapse:separate;border-spacing:0;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;font-size:12px;background:#fff;color:#111d23}
.ret-ev thead th{position:sticky;top:0;background:#f8fafc;color:#3d4947;font-size:11px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;text-align:left;padding:10px 14px;border-bottom:1px solid #e2e8f0}
.ret-ev td{padding:10px 14px;border-top:1px solid #f1f5f9;vertical-align:top;color:#111d23}
.ret-ev tbody tr:first-child td{border-top:0}
.ret-ev tbody tr:hover td{background:#f8fafc}
.ret-ev td.ak-mono{white-space:nowrap;color:#475569}
.ret-band-0{box-shadow:inset 3px 0 0 $ac}
.ret-band-1{box-shadow:inset 3px 0 0 #94a3b8}
.ret-ev-st{display:inline-flex;align-items:center;gap:5px;font-weight:700;margin-right:6px;white-space:nowrap}
.ret-ev-ok .ret-ev-st{color:#047857}
.ret-ev-err .ret-ev-st{color:#b91c1c}
.ret-ev-warn .ret-ev-st{color:#b45309}
.ret-ev-info .ret-ev-st{color:#1d4ed8}
.ret-ev-msg{font-family:ui-monospace,Menlo,Consolas,monospace;word-break:break-word}
.ret-ev-empty{text-align:center;color:#3d4947;padding:24px}
@keyframes retIn{from{opacity:0}to{opacity:1}}
@keyframes retGrow{from{transform:scaleX(0)}to{transform:scaleX(1)}}
@media (prefers-reduced-motion:reduce){.ret-kpi,.ret-scale-bar{animation:none}}
</style>";
}

function main(){
	$tpl=new template_admin();
	$AutoFSEnabled=1;
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	
	if($SquidPerformance>2){$AutoFSEnabled=0;}
	if($EnableIntelCeleron==1){$AutoFSEnabled=0;}

    $SquidRotateOnlySchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateOnlySchedule"));
	$LogRotatePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotatePath");
	$BackupMaxDays=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDays");
	$BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
	$LogRotateH=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotateH");
	$LogRotateM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogRotateM");

	if(!is_numeric($BackupMaxDays)){$BackupMaxDays=365;}
	

	
	if($LogRotateH==null){$LogRotateH="00";}
	if($LogRotateM==null){$LogRotateM="05";}
	
	$t=time();
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	
	
	$BackupSquidLogsUseNas=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas");
	$BackupSquidLogsNASIpaddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASPassword");
	if(!is_numeric($BackupSquidLogsUseNas)){$BackupSquidLogsUseNas=0;}
	$SquidLogRotateFreq=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogRotateFreq"));
	if($SquidLogRotateFreq<10){$SquidLogRotateFreq=1440;}

	
	$BackupLogsMaxStoragePercent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupLogsMaxStoragePercent"));
	
	if($BackupLogsMaxStoragePercent==0){$BackupLogsMaxStoragePercent=50;}
	$SquidRotateAutomount=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomount"));
	$SquidRotateClean=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateClean"));
	$SquidRotateAutomountRes=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomountRes");
	$SquidRotateAutomountFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidRotateAutomountFolder");
	
	$BackupSquidLogsUseNas=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsUseNas"));
	$BackupSquidLogsNASIpaddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASIpaddr");
	$BackupSquidLogsNASFolder=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder");
	$BackupSquidLogsNASUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASUser");
	$BackupSquidLogsNASPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASPassword");
	$BackupSquidLogsNASFolder2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupSquidLogsNASFolder2");
	if($BackupSquidLogsNASFolder2==null){$BackupSquidLogsNASFolder2="artica-backup-syslog";}
	
	$SquidNoAccessLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
	$t=time();


	
	if(!artica_setting_exists("SquidLogsExceptions")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidLogsExceptions", "127.0.0.1");}
	
	
	$SquidLogsExceptions=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidLogsExceptions");
	
	
	
	$AUTOFSR[null]="{select}";
	if($AutoFSEnabled==1){
		$autofs=new autofs();
		$hashZ=$autofs->automounts_Browse();
		if(file_exists('ressources/usb.scan.inc')){include("ressources/usb.scan.inc");}
		foreach ($hashZ as $localmount=>$array){$AUTOFSR[$localmount]="{$localmount}";}
	}
	
	
	$freq[20]="20mn";
	$freq[30]="30mn";
	$freq[60]="1h";
	$freq[120]="2h";
	$freq[300]="5h";
	$freq[600]="10h";
	$freq[1440]="24h";
	$freq[2880]="48h";
	$freq[4320]="3 {days}";
	$freq[10080]="1 {week}";
	
	
	for($i=0;$i<24;$i++){
		$H=$i;
		if($i<10){$H="0$i";}
		$Hours[$i]=$H;
	}
	
	for($i=0;$i<60;$i++){
		$M=$i;
		if($i<10){$M="0$i";}
		$Mins[$i]=$M;
	}
	

	$disabled=false;

	$form[]=$tpl->field_clock("RotateClock", "{schedule}", "$LogRotateH:$LogRotateM","");
    $form[]=$tpl->field_checkbox("SquidRotateOnlySchedule", "{only_by_schedule}",
            $SquidRotateOnlySchedule,false,"{only_by_schedule_squid_rotation_explain}");






	$form[]=$tpl->field_text("LogRotatePath","{temporay_storage_path}",$LogRotatePath,false,"{temporay_storage_path_explain}",$disabled);
	$form[]=$tpl->field_text("BackupMaxDaysDir","{backup_folder}",$BackupMaxDaysDir,false,"{BackupMaxDaysDir_explain}",$disabled);
	$form[]=$tpl->field_checkbox("SquidRotateAutomount", "{automount_ressource}", $SquidRotateAutomount,"SquidRotateAutomountFolder,SquidRotateAutomountFolder","{automount_ressource_explain}");
	$form[]=$tpl->field_array_hash($AUTOFSR,"SquidRotateAutomountRes","{resource}",$SquidRotateAutomountRes,false,null);
	$form[]=$tpl->field_text("SquidRotateAutomountFolder","{directory}",$SquidRotateAutomountFolder,false,"{SquidRotateAutomountFolder}",$disabled);
	$form[]=$tpl->field_checkbox("SquidRotateClean", "{clean_old_files}", $SquidRotateClean,false,"{clean_old_files_accesslog_explain}");
	$form[]=$tpl->field_numeric("BackupMaxDays", "{max_storage_days}", $BackupMaxDays,"{max_storage_days_log_explain}");
	$form[]=$tpl->field_numeric("BackupLogsMaxStoragePercent", "{max_percent_storage} (%)", $BackupLogsMaxStoragePercent,"{BackupLogsMaxStoragePercent_explain}");
	$form[]=$tpl->field_text("SquidLogsExceptions","{SquidLogsExceptions}",$SquidLogsExceptions,false,"{SquidLogsExceptions_explain}");
	
	
	$users=new usersMenus();
	if($users->APP_DAVSERVER){
		$WebDavSquidLogsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsEnabled"));
		$WebDavSquidLogsPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsPort"));
		$WebDavSquidLogsInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsInterface");
		$WebDavSquidLogsUsername=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsUsername");
		$WebDavSquidLogsPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsPassword");
		$WebDavSquidLogsAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebDavSquidLogsInterface"));
		if($WebDavSquidLogsPort==0){$WebDavSquidLogsPort=8008;}
		$form[]=$tpl->field_section("{access_to_storage}","{access_to_storage_text}");
		$form[]=$tpl->field_checkbox("{enable_webdav_service}","WebDavSquidLogsEnabled",$WebDavSquidLogsEnabled,"WebDavSquidLogsAuth,WebDavSquidLogsPort,WebDavSquidLogsInterface,WebDavSquidLogsUsername,WebDavSquidLogsPassword");
		$form[]=$tpl->field_numeric("WebDavSquidLogsPort", "{listen_port}", $WebDavSquidLogsPort);
		$form[]=$tpl->field_interfaces("WebDavSquidLogsInterface","{interface}",$WebDavSquidLogsInterface);
		$form[]=$tpl->field_checkbox("{EnableAuth}","WebDavSquidLogsAuth",$WebDavSquidLogsAuth,"WebDavSquidLogsUsername,WebDavSquidLogsPassword");
		$form[]=$tpl->field_text("WebDavSquidLogsUsername","{username}",$WebDavSquidLogsUsername,false);
		$form[]=$tpl->field_password("WebDavSquidLogsPassword","{password}",$WebDavSquidLogsPassword,false);
	}



	
	$form[]=$tpl->field_section("{NAS_storage}","{log_retention_nas_text}");

	$form[]=$tpl->field_checkbox("BackupSquidLogsUseNas","{use_remote_nas}",$BackupSquidLogsUseNas,"BackupSquidLogsNASIpaddr,BackupSquidLogsNASFolder,BackupSquidLogsNASFolder2,BackupSquidLogsNASUser,BackupSquidLogsNASPassword","{BackupSquidLogsUseNas_explain}");
	$form[]=$tpl->field_text("BackupSquidLogsNASIpaddr","{hostname}",$BackupSquidLogsNASIpaddr,false,"",$disabled);
	$form[]=$tpl->field_text("BackupSquidLogsNASFolder","{shared_folder}",$BackupSquidLogsNASFolder,false,"",$disabled);
	$form[]=$tpl->field_text("BackupSquidLogsNASFolder2","{storage_directory}",$BackupSquidLogsNASFolder2,false,"",$disabled);
	$form[]=$tpl->field_text("BackupSquidLogsNASUser","{username}",$BackupSquidLogsNASUser,false,"",$disabled);
	$form[]=$tpl->field_password("BackupSquidLogsNASPassword","{password}",$BackupSquidLogsNASPassword,false,"",$disabled);
	
	


	$jsTestNas=$tpl->framework_buildjs("/logrotate/testnas","squid.nas.storage.progress","squid.nas.storage.progress.txt","progress-logrotate-restart");
	
    $export_logs_now=$tpl->framework_buildjs("/proxy/rotate","squid.rotate.progress","squid.rotate.progress.txt","progress-logrotate-restart");
	
	$form[]=$tpl->form_add_button("{NAS_storage}:{test_connection}",$jsTestNas);
	$form[]=$tpl->form_add_button("{export_logs_now}",$export_logs_now);

    $js[]="if(typeof dialogInstance1 == 'object'){ dialogInstance1.close();}";
    $js[]="if ( document.getElementById('artica-backup-parameters-static') ){";
    $js[]="LoadAjax('artica-backup-parameters-static','fw.artica.backup.php?parameters2=yes');";
    $js[]="}";
	
	$tpl->form_privileges="AsSquidAdministrator";
    $html[]="<div style='margin-top:20Px'>&nbsp;</div>";
	$html[]=$tpl->form_outside("{log_retention}", $form,"{squid_backuped_logs_explain}","{apply}",@implode("",$js),"AsSquidAdministrator",true);

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}