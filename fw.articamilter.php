<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// awc_table_style()/awc_pager_js(): la feuille et le pager partages des tableaux d'entites.
// Inclusion INCONDITIONNELLE: placee dans un bloc de bootstrap elle ne s'executerait
// jamais et le tableau mourrait sur « Call to undefined function awc_table_style() ».
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table1"])){table1();exit;}
if(isset($_GET["ping"])){amilter_ping();exit;}
if(isset($_POST["ArticaMilterDebug"])){save();exit;}

if(isset($_GET["status"])){amilter_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}

if(isset($_GET["section-service-js"])){section_service_js();exit;}
if(isset($_GET["section-service-popup"])){section_service_popup();exit;}

if(isset($_GET["section-features-js"])){section_features_js();exit;}
if(isset($_GET["section-features-popup"])){section_features_popup();exit;}

if(isset($_GET["section-auth-js"])){section_auth_js();exit;}
if(isset($_GET["section-auth-popup"])){section_auth_popup();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["events-start"])){events_start();exit;}
if(isset($_GET["search"])){events_search();exit;}
if(isset($_GET["download-logs"])){events_download();exit;}
page();
function page(){
    //
    $page=CurrentPageName();

    $instance_id=intval($_GET["instance-id"]);
    $tpl=new template_admin();

    $ARTICA_MILTER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_MILTER_VERSION");

    $html=$tpl->page_header("Artica Milter v$ARTICA_MILTER_VERSION",
        ico_plug,"{artica_milter_explain}",
        "$page?tabs=$instance_id","/artica-milter-$instance_id","progress-webapi-restart",false,"table-loader-webapi");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);

}
function section_service_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{parameters}","$page?section-service-popup=yes");
}
function section_features_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{features}","$page?section-features-popup=yes");
}
function section_auth_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{test_authentication}","$page?section-auth-popup=yes");
}

function section_js_form():string{
    $page=CurrentPageName();
    return "BootstrapDialog1.close();LoadAjaxSilent('progress-amilter-start','$page?table1=yes');";
}
function restart():bool{
    $page=CurrentPageName();
    $sock=new sockets();
    $sock->REST_API_POSTFIX("/postfix/articamilter/restart");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('progress-amilter-start','$page?table1=yes');";
    return admin_tracks("Launch order to restart Artica Milter service");
}
function reload(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $data=$sock->MILTER_API("/reload");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->js_error("{error}");
        return true;
    }


    $tpl->js_executed_background("{reloading_service}");
    echo "LoadAjaxSilent('progress-amilter-start','$page?table1=yes');";
    return true;
}
function amilter_ping(){
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $interval=3000;
    $ping=intval($_GET["ping"]);
    $f[]="function amilterRefresh(){";
    $f[]="\tvar srcping=document.getElementById('artica-milter-ping').value;";
    $f[]="\tLoadAjaxSilent('amilter-status','$page?status=yes');";
    $f[]="}";
    $f[]="";
    $f[]="function amilterPing(){";
    $f[]="\tif(!document.getElementById('artica-milter-ping')){ return;}";
    $f[]="\tvar currentPing='$ping';";
    $f[]="\tvar srcping=document.getElementById('artica-milter-ping').value;";
    $f[]="\tif (srcping.length==0){";
    $f[]="\t\tdocument.getElementById('artica-milter-ping').value=currentPing;";
    $f[]="\t\tsetTimeout('amilterRefresh()',$interval);";
    $f[]="\t\treturn;";
    $f[]="\t}";
    $f[]="\tvar now = new Date().getTime();";
    $f[]="\tvar unixMilliseconds = srcping * 1000;";
    $f[]="\tvar differenceMilliseconds = now - unixMilliseconds;";
    $f[]="\tSecs = Math.floor(differenceMilliseconds / 1000);";
    $f[]="\tif (Secs < 3) { return; }";
    $f[]="\tdocument.getElementById('artica-milter-ping').value=currentPing;";
    $f[]="\tsetTimeout('amilterRefresh()',$interval);";
    $f[]="}";
    $f[]="amilterPing();\n";
    echo @implode("\n",$f);
    return true;
}

function section_service_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ArticaMilterDebug   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMilterDebug"));
    $form[] = $tpl->field_checkbox("ArticaMilterDebug", "{debug}", $ArticaMilterDebug);
    $security="AsPostfixAdministrator";

    $times[10080]=$tpl->javascript_parse_text("7 {days}");
    $times[14400]=$tpl->javascript_parse_text("10 {days}");
    $times[21600]=$tpl->javascript_parse_text("15 {days}");
    $times[43200]=$tpl->javascript_parse_text("1 {month}");
    $times[129600]=$tpl->javascript_parse_text("3 {months}");

    $MimeDefangMaxQuartime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangMaxQuartime"));
    // La valeur vient d'un reglage libre, pas d'une liste fermee: une retention reglee
    // ailleurs (par l'API, par un import) sortait de $times en index indefini -- cellule
    // vide en lecture, aucune option selectionnee dans la liste. On retombe sur le defaut.
    if(!isset($times[$MimeDefangMaxQuartime])){$MimeDefangMaxQuartime=129600;}

    $form[]=$tpl->field_array_hash($times, "MimeDefangMaxQuartime", "{retention} ({backup})", $MimeDefangMaxQuartime);


    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_features_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryRestShellEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestShellEnable"));
    $ActiveDirectoryRestShellPass   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestShellPass"));
    $ActiveDirectoryRestSnapsEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestSnapsEnable"));

    $form[] = $tpl->field_checkbox("ActiveDirectoryRestShellEnable","{allow_execute_scripts}",$ActiveDirectoryRestShellEnable,
        "ActiveDirectoryRestShellPass");
    $form[] = $tpl->field_text("ActiveDirectoryRestShellPass","{passphrase} X-Auth-Token",$ActiveDirectoryRestShellPass);
    $form[] = $tpl->field_checkbox("ActiveDirectoryRestSnapsEnable","{allow_snapshots}",$ActiveDirectoryRestSnapsEnable);


    $security="AsPostfixAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function section_auth_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ActiveDirectoryRestTestUser    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestTestUser"));
    $ActiveDirectoryRestUser        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestUser"));
    $ActiveDirectoryRestPass        = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestPass"));
    $ActiveDirectoryRestTestURL     = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestTestURL"));

    $form[] = $tpl->field_checkbox("ActiveDirectoryRestTestUser","{enable_feature}",$ActiveDirectoryRestTestUser,
        "ActiveDirectoryRestTestURL,ActiveDirectoryRestUser,ActiveDirectoryRestPass");
    $form[] = $tpl->field_text("ActiveDirectoryRestTestURL","{uri_test}",$ActiveDirectoryRestTestURL);
    $form[] = $tpl->field_text("ActiveDirectoryRestUser","{username}",$ActiveDirectoryRestUser);
    $form[] = $tpl->field_password2("ActiveDirectoryRestPass","{password}",$ActiveDirectoryRestPass);


    $security="AsPostfixAdministrator";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}", section_js_form(),$security);
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["{events}"]="$page?events-start=yes";
    echo $tpl->tabs_default($array);
    return true;
}
function events_start():bool{
    $tpl    = new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page);
    echo "</div>";
    return true;
}


function amilter_status():bool{
    $t=time();
    $tpl    = new template_admin();
    $page=CurrentPageName();
    $sock=new sockets();
    $data=$sock->REST_API_POSTFIX("/postfix/articamilter/status");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    $jsRestart="Loadjs('$page?restart=yes');";

    $final[]=$tpl->SERVICE_STATUS($bsini, "ARTICA_MILTER",$jsRestart);
    $final[]="<script>Loadjs('$page?ping=$t');</script>";
    echo $tpl->_ENGINE_parse_body($final);
    return true;

}
function table():bool{
    $page=CurrentPageName();
    echo "<div style='margin-top:10px' id='progress-amilter-start'></div>
<script>LoadAjaxSilent('progress-amilter-start','$page?table1=yes')</script>";
    return true;

}

function table1(){

    $tpl                            = new template_admin();
    $page                           = CurrentPageName();

    $times[10080]=$tpl->javascript_parse_text("7 {days}");
    $times[14400]=$tpl->javascript_parse_text("10 {days}");
    $times[21600]=$tpl->javascript_parse_text("15 {days}");
    $times[43200]=$tpl->javascript_parse_text("1 {month}");
    $times[129600]=$tpl->javascript_parse_text("3 {months}");

    $MimeDefangMaxQuartime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangMaxQuartime"));
    // La valeur vient d'un reglage libre, pas d'une liste fermee: une retention reglee
    // ailleurs (par l'API, par un import) sortait de $times en index indefini -- cellule
    // vide en lecture, aucune option selectionnee dans la liste. On retombe sur le defaut.
    if(!isset($times[$MimeDefangMaxQuartime])){$MimeDefangMaxQuartime=129600;}




    $ArticaMilterDebug   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaMilterDebug"));
    $tpl->table_form_field_js("Loadjs('$page?section-service-js=yes')");
    $tpl->table_form_field_text("{retention} ({backup})",$times[$MimeDefangMaxQuartime],ico_database);
    $tpl->table_form_field_bool("{debug}",$ArticaMilterDebug,ico_bug);


    $myform=$tpl->table_form_compile();

    $topbuttons[] = array("Loadjs('$page?restart=yes');", ico_refresh, "{restart_service}");
    $topbuttons[] = array("Loadjs('$page?reload=yes');", ico_refresh, "{reload_service}");
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    // Flex, pas un <table> a colonne figee: SERVICE_STATUS() rend une tuile de 335px de
    // large et la cellule lui en accordait 240 -- elle debordait sur le formulaire a
    // droite. En flex la tuile prend sa largeur naturelle et la carte prend le reste.
    $html="<input type='hidden' value='' id='artica-milter-ping'>
	<div style='display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start;margin-top:10px'>
		<div style='flex:0 0 auto'><div id='amilter-status'></div></div>
		<div style='flex:1 1 420px;min-width:0'>$myform</div>
	</div>
	<script>LoadAjaxSilent('amilter-status','$page?status=yes');$jstiny</script>
	";


    echo $tpl->_ENGINE_parse_body($html);
}




function save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $EnableUnBoundSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnBoundSNMPD"));

    if(isset($_POST["InComingInterfaces"])){
        $array=explode(",",$_POST["InComingInterfaces"]);
        $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(@implode("\n", $array), "PowerDNSListenAddr");
        unset($_POST["InComingInterfaces"]);
    }

    $tpl->SAVE_POSTs();



    if($_POST["EnableUnBoundSNMPD"]<>$EnableUnBoundSNMPD){$GLOBALS["CLASS_SOCKETS"]->REST_API("/snmpd/restart");}
}
function events_download():bool{
    $sock=new sockets();
    $data=$sock->REST_API_POSTFIX("/postfix/articamilter/events/compress");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        die();
    }
    if(!$json->Status){
        header('Content-type: '."text/plain");
        header('Content-Transfer-Encoding: binary');
        header("Content-Disposition: attachment; filename=\"artica-milter.txt\"");
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
        header("Content-Length: ".strlen($json->Error));
        ob_clean();
        flush();
        echo $json->Error;
        return false;
    }
    $content=base64_decode($json->Content);

    header('Content-type: '."application/x-gzip");
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"artica-milter.gz\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".strlen($content));
    ob_clean();
    flush();
    echo $content;

    return true;
}

/**
 * amilter_severity — classe une ligne de journal a partir de son marqueur.
 *
 * Retourne array(cle, libelle, classe de badge, icone). Le marqueur est retire de
 * la ligne: il devient une COLONNE, pas un fragment de la phrase. Un badge en tete
 * de ligne se lit d'un coup d'oeil sur 200 lignes, un <span> insere au milieu du
 * texte non -- et c'est bien la lecture qu'on vient chercher dans un journal.
 */
function amilter_severity(string $line):array{
    $map=array(
        "[ERROR]:"   => array("error","ERROR","awc-badge-alert","fa-circle-exclamation"),
        "[WARNING]:" => array("warn","WARN","awc-badge-warn","fa-triangle-exclamation"),
        "WARNING:"   => array("warn","WARN","awc-badge-warn","fa-triangle-exclamation"),
        "[RESTAPI]:" => array("api","API","awc-badge-on","fa-code"),
        "[START]:"   => array("start","START","awc-badge-on","fa-play"),
        "[INFO]:"    => array("info","INFO","awc-badge-on","fa-circle-info"),
        "[DEBUG]:"   => array("debug","DEBUG","awc-badge-off","fa-bug"),
        "[DEBUG] "   => array("debug","DEBUG","awc-badge-off","fa-bug"),
    );
    foreach($map as $marker=>$sev){
        if(strpos($line,$marker)===0){
            return array($sev[0],$sev[1],$sev[2],$sev[3],trim(substr($line,strlen($marker))));
        }
    }
    if(strpos($line,"Starting ")===0){
        return array("start","START","awc-badge-on","fa-play",trim(substr($line,9)));
    }
    // Pas de marqueur: on classe sur le contenu. « fatal » et « error » sont les deux
    // seuls mots que le service ecrit sans prefixe quand il meurt tot.
    if(preg_match("#(fatal|error)#i",$line)){
        return array("error","ERROR","awc-badge-alert","fa-circle-exclamation",$line);
    }
    return array("info","INFO","awc-badge-on","fa-circle-info",$line);
}

/**
 * amilter_event_row — une ligne d'evenement du journal.
 *
 * $message arrive DEJA echappe: la ligne vient d'un fichier de journal ecrit par un
 * service qui y recopie des donnees de message (expediteur, sujet), donc du texte
 * qu'un tiers controle. L'echappement est fait par l'appelant, avant le decoupage
 * du marqueur, pour qu'aucun chemin ne puisse l'oublier.
 */
function amilter_event_row(string $ftime,string $pid,array $sev,string $message):string{
    $cls=($sev[0]=="error")?"awc-ico awc-ico-off":"awc-ico";
    $txt=($sev[0]=="error")?"<span class='awc-down'>$message</span>":$message;
    $h ="<tr>";
    $h.="<td style='width:1%' nowrap><div class='awc-ident' style='gap:10px'>";
    $h.="<span class='$cls' style='width:30px;height:30px;font-size:13px'><i class='fas ".$sev[3]."'></i></span>";
    $h.="<span><span class='awc-name' style='font-size:13px;line-height:16px'>$ftime</span>";
    $h.="<div class='awc-meta'>PID $pid</div></span></div></td>";
    $h.="<td class='center' style='width:1%' nowrap><span class='awc-badge ".$sev[2]."'>".$sev[1]."</span></td>";
    $h.="<td><span class='awc-mono' style='word-break:break-word'>$txt</span></td>";
    $h.="</tr>";
    return $h;
}

function events_search(){
    clean_xss_deep();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $MAIN=$tpl->format_search_protocol(isset($_GET["search"])?$_GET["search"]:null);
    $sock=new sockets();
    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    $data=$sock->REST_API_POSTFIX("/postfix/articamilter/events/$rp/$search");

    $json=json_decode($data);
    // Deux sorties distinctes, et chacune REND LA MAIN. L'ancienne version affichait
    // l'erreur puis continuait: sur un JSON illisible, $json vaut null et la ligne
    // suivante lisait ->Status dessus (avertissement PHP 8), puis foreach parcourait
    // ->Logs d'un null. La page repondait 200 avec un tableau vide et une erreur.
    if(json_last_error()>JSON_ERROR_NONE){
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
        return;
    }
    if(!is_object($json) || !$json->Status){
        $err=is_object($json)?$json->Error:"";
        echo $tpl->div_error("{error}<hr>".htmlspecialchars((string)$err));
        return;
    }

    $logs=(isset($json->Logs) && is_array($json->Logs))?$json->Logs:array();
    $rowsHtml=array();
    $counters=array("error"=>0,"warn"=>0,"debug"=>0);

    foreach($logs as $line){
        $line=trim((string)$line);
        if(!preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:\s+(.+)#",$line,$re)){continue;}

        // Horodatage COMPLET, toujours. L'ancienne version retirait la date du jour pour
        // gagner de la place, ce qui donnait un melange de « 01:12:03 » et de
        // « 2026-09-06 23:50:00 » dans le meme tableau des que le journal traversait
        // minuit -- ou simplement quand le fuseau de PHP et l'heure ecrite par le
        // service ne tombaient pas le meme jour. Ici la date a sa propre colonne.
        $xtime=strtotime($re[1]." ".$re[2]." ".$re[3]);
        $FTime=($xtime===false)?"-":date("Y-m-d H:i:s",$xtime);
        $pid=$re[4];

        // ECHAPPER D'ABORD, decouper ensuite. Le journal recopie des donnees de
        // message; une adresse contenant <script> arrivait telle quelle dans le HTML.
        // Les marqueurs ([DEBUG]:, WARNING:) ne portent aucun caractere special, donc
        // ils traversent htmlspecialchars() intacts et restent reconnaissables.
        $sev=amilter_severity(htmlspecialchars($re[5],ENT_QUOTES));
        if(isset($counters[$sev[0]])){$counters[$sev[0]]++;}
        $rowsHtml[]=amilter_event_row($FTime,$pid,$sev,$sev[4]);
    }

    $total=count($rowsHtml);
    $html=array();
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{date}</th><th class='center'>{level}</th><th>{events}</th>";
    $html[]="</tr></thead><tbody id='amilter-ev-rows'>";
    if($total==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_events}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rowsHtml);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $total / $total {events}"
        .($counters["error"]>0?" &middot; <span class='awc-down'>".$counters["error"]." {errors}</span>":"")
        .($counters["warn"]>0?" &middot; ".$counters["warn"]." {warning}":"")
        ."</span><span class='awc-pg' id='amilter-ev-pager'></span></div>";
    $html[]="</div></div>";

    $topbuttons=array();
    $topbuttons[]=array("document.location.href='$page?download-logs=yes';",ico_download,"{download} {LogTypeM}");

    // Le terme AFFICHE est celui qui a servi a interroger le daemon, echappe. L'ancien
    // titre interpolait $_GET["search"] brut dans un litteral JavaScript: un apostrophe
    // y decapitait tout l'en-tete, un antislash aussi (voir siem_tiny_filters).
    $shown=($search=="NONE")?"*":$search;
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="Artica Milter {events} &laquo;".htmlspecialchars($shown,ENT_QUOTES)."&raquo;";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{artica_milter_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>if(typeof NoSpinner==='function'){NoSpinner();}$jstiny</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .awc_pager_js(50,"amilter-ev-rows","amilter-ev-pager");
}
