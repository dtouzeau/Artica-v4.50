<?php
// SP 127
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["build-full-query"])){build_full_query();exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["page-de-guarde"])){page_de_guarde();exit;}
if(isset($_GET["query"])){query();exit;}
if(isset($_GET["query-js"])){query_js();exit;}
if(isset($_GET["query-popup"])){query_popup();exit;}
if(isset($_POST["FROM"])){query_save();exit;}
if(isset($_GET["vertical-timeline"])){echo vertical_time_line();exit;}
if(isset($_GET["top-sites"])){echo top_sites();exit;}
if(isset($_GET["top-users"])){echo top_users();exit;}
if(isset($_GET["top-categories"])){echo top_categories();exit;}
if(isset($_GET["template"])){template();exit;}
if(isset($_GET["sites-table"])){sites_table();exit;}
if(isset($_GET["users-table"])){echo users_table();exit;}
if(isset($_GET["categories-table"])){echo categories_table();exit;}
if(isset($_GET["member-stats"])){echo member_stats_js();exit;}
if(isset($_GET["member-stats-tabs"])){echo member_stats_tabs();exit;}
if(isset($_GET["member-stats-pane-hours"])){echo member_stats_pane_hours();exit;}
if(isset($_GET["member-stats-pane-sites"])){echo member_stats_pane_sites();exit;}

// Les six constructeurs Highcharts qui vivaient ici ont ete retires avec le refactor: ils
// lisaient la table statscom (qui n'est plus alimentee) et dessinaient avec une bibliotheque
// que les panneaux n'utilisent plus. Leurs dispatches sont supprimes avec eux -- un ?xxx=yes
// pointant sur une fonction absente est une erreur fatale, pas une page vide.
if(isset($_GET["member-stats-notcategorized"])){echo member_stats_notcategorized();exit;}
if(isset($_GET["member-stats-websites"])){echo member_stats_websites();exit;}
if(isset($_GET["build-page"])){build_page();exit;}



page();

// scm_filter_qs — le périmètre transmis à l'API et propagé entre onglets : tenant
// (INTERSECTE + focus global), site (émetteur, migration 0039 : résolu en proxy_id
// côté serveur) et nœud. Repris des sélecteurs du TINY.
function scm_filter_qs(){
    $qs="";
    $t=isset($_GET["tenants"])?trim((string)$_GET["tenants"]):"";
    if($t!=="" && preg_match('/^[A-Za-z0-9._-]{1,64}$/',$t)){ $qs.="&tenants=".rawurlencode($t)."&tenant_id=".rawurlencode($t); }
    $site=isset($_GET["site"])?trim((string)$_GET["site"]):"";
    if($site!==""){ $qs.="&site=".rawurlencode($site); }
    $node=isset($_GET["proxy_id"])?trim((string)$_GET["proxy_id"]):"";
    if($node!==""){ $qs.="&proxy_id=".rawurlencode($node); }
    return $qs;
}

function query_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $srctype=scm_srctype();
    $username=$_GET["data"];$usernamesql=scm_sqlval($username);
    $usernameenc=urlencode($username);
    $tpl->js_dialog1("{build_query}: {{$srctype}} $username","$page?query-popup=yes&srctype=$srctype&data=$usernameenc",650);

}

function member_stats_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $srctype=scm_srctype();
    $username=$_GET["data"];$usernamesql=scm_sqlval($username);
    $usernameenc=urlencode($username);
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $tpl->js_dialog10("{statistics}: {{$srctype}} $username","$page?member-stats-tabs=yes&date-from=$Date1&date-to=$Date2&srctype=$srctype&data=$usernameenc",2000);

}

function member_stats_tabs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $srctype=scm_srctype();
    $username=$_GET["data"];$usernamesql=scm_sqlval($username);
    $usernameenc=urlencode($username);
    $Date1=$_GET["date-from"];
    $Date2=$_GET["date-to"];
    $suffix="&date-from=$Date1&date-to=$Date2&srctype=$srctype&data=$usernameenc";

    // Une seule liste d'onglets. L'ancienne bifurcation renvoyait au-dela de 96 h vers
    // fw.statscom.members.cold.php, qui lit toujours la table statscom -- donc vers des
    // panneaux vides. Le SIEM choisit lui-meme son palier de compaction selon la fenetre
    // demandee, ce qui rend la distinction chaud/froid sans objet ici.
    //
    // Les libelles decrivent ce que chaque panneau montre REELLEMENT depuis le refactor:
    // l'activite (serie + classements), les sites en tableau, le detail heure par heure, et
    // la part non classee. « {top_websites} » a disparu parce que son panneau rendait le meme
    // fragment que l'activite -- deux onglets identiques ne sont pas deux vues.
    $array["{activity}"]="$page?member-stats-pane-hours=yes$suffix";
    $array["{websites}"]="$page?member-stats-websites=yes$suffix";
    $array["{detail}"]="$page?member-stats-pane-sites=yes$suffix";
    $array["{websites_not_categorized}"]="$page?member-stats-notcategorized=yes$suffix";

    echo $tpl->tabs_default($array);
}


/**
 * Correspondance entre le type de membre de cette page et le vocabulaire du SIEM.
 *
 * `mac` n'a PAS d'equivalent: l'adresse MAC n'existe que sur la famille pare-feu
 * (src_mac/dst_mac, FortiGate), la famille proxy n'en porte aucune. On renvoie une chaine
 * vide plutot qu'un repli silencieux sur l'utilisateur -- afficher les statistiques de
 * quelqu'un d'autre sous une adresse MAC serait pire que de ne rien afficher.
 */
function scm_zoom_kind($srctype){
    if($srctype==="username"){return "user";}
    if($srctype==="ipaddr"){return "client_ip";}
    return "";
}

/**
 * Fenetre du zoom SIEM la plus proche de l'intervalle demande.
 *
 * L'endpoint /api/v1/statistics/zoom raisonne en fenetres NOMMEES et RELATIVES A MAINTENANT
 * (hour/day/week/month), parce qu'il choisit son palier de compaction en consequence. Les
 * anciennes URL de cette page portent deux horodatages absolus: on prend donc la fenetre
 * dont la duree couvre l'intervalle, et l'appelant annonce celle reellement rendue.
 */
function scm_zoom_window($from,$to){
    $h=(intval($to)-intval($from))/3600;
    if($h<=1){return "hour";}
    if($h<=24){return "day";}
    if($h<=(7*24)){return "week";}
    return "month";
}

function scm_window_label($w){
    $map=array("hour"=>"{last_hour}","day"=>"{last_24h}","week"=>"{last_7days}","month"=>"30 {days}");
    return isset($map[$w])?$map[$w]:$w;
}

/**
 * Activite d'un membre dans le temps.
 *
 * Anciennement: deux graphiques Highcharts alimentes par `SELECT ... FROM statscom GROUP BY
 * zdate`. La table statscom n'est plus alimentee (EnableStatsCommunicator vide sur les
 * appliances concernees), donc le panneau ne rendait plus rien du tout -- pas une erreur,
 * une page blanche.
 *
 * Desormais: la vue de zoom proxy de fw.statistics.events.zoom.php, qui lit le SIEM et
 * dessine en Chart.js. DELEGUEE et non recopiee: c'est la meme question posee sur la meme
 * entite, et deux implementations du meme graphique divergent toujours.
 *
 * `win-view` et non `data-view`: le premier rend les compteurs, la courbe a deux axes et les
 * anneaux de classement; le second est le tableau des evenements bruts. Les deux repondent 200
 * avec du contenu credible, donc l'erreur ne se voit qu'a l'ecran.
 */
function member_stats_pane_hours(){
    $tpl=new template_admin();
    $srctype=scm_srctype();
    $username=(string)$_GET["data"];
    $kind=scm_zoom_kind($srctype);

    if($kind===""){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{MAC}||{statscom_member_mac_unsupported}"));
        return;
    }
    if(trim($username)===""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{no_data}"));
        return;
    }

    $from=intval($_GET["date-from"]);
    $to=intval($_GET["date-to"]);
    $win=scm_zoom_window($from,$to);
    $id="scmz".md5($srctype.$username.$win);

    $html=array();
    // La fenetre rendue est annoncee: elle se termine MAINTENANT, alors que l'intervalle
    // demande pouvait etre historique. Taire l'ecart ferait lire un graphique pour un autre.
    $html[]="<div class='awc-muted' style='margin:4px 0 8px 2px'>".
            "<i class='fas fa-circle-info'></i> {siem_zoom_window_note}: <b>".scm_window_label($win)."</b></div>";
    $html[]="<div id='$id'></div>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).
        "<script>LoadAjax('$id','fw.statistics.events.zoom.php?win-view=yes&kind=".urlencode($kind).
        "&value=".urlencode($username)."&window=".urlencode($win)."');</script>";
}







function query_popup(){

    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    if(!$q->CREATE_STATSCOM()) {
        echo $tpl->div_error($q->mysql_error);
    }

    if(!isset($_SESSION["STATSCOM_DAYS_CACHE"])) {
        $results = $q->QUERY_SQL("SELECT zdate FROM statscom_days GROUP BY zdate ORDER BY zdate DESC");
        if (!$q->ok) {
            echo $tpl->div_error($q->mysql_error);
        }

        $_SESSION["STATSCOM_DAYS_CACHE"][date("Y-m-d")] = $tpl->time_to_date(time());
        $_SESSION["STATSCOM_DAYS_CACHE"][date("Y-m-d", strtotime('-1 days'))] = $tpl->time_to_date(strtotime('-1 days'));

        while ($ligne = pg_fetch_assoc($results)) {
            $time = strtotime($ligne["zdate"]);
            $_SESSION["STATSCOM_DAYS_CACHE"][$ligne["zdate"]] = $tpl->time_to_date($time);
        }
    }

    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    for($i=0;$i<24;$i++){
        $h=$i;
        if($i<10){$h="0{$i}";}
        $h="$h";
        $TIMES[$h]=$h;
    }
    for($i=0;$i<60;$i++){
        $h=$i;
        if($i<10){$h="0{$i}";}
        $h="$h";
        $MINS[$h]=$h;
    }

    $tpl->field_hidden("STATSCOM_SRC",$_GET["srctype"]);
    $tpl->field_hidden("STATSCOM_SRC_DATA",$_GET["data"]);

    $form[]=$tpl->field_section("{from_date}");
    $form[]=$tpl->field_array_hash($_SESSION["STATSCOM_DAYS_CACHE"],"FROM","nonull:{from_date}",$_SESSION["STATSCOM_DAY"]["FROM"]);

    $tb=explode(":",$_SESSION["STATSCOM_DAY"]["FROMH"]);
    $form[]=$tpl->field_array_hash($TIMES,"FROM_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"FROM_MIN","nonull:{minutes}",$tb[1]);

    $form[]=$tpl->field_section("{to_date}");
    $tb=explode(":",$_SESSION["STATSCOM_DAY"]["TOH"]);
    $form[]=$tpl->field_array_hash($_SESSION["STATSCOM_DAYS_CACHE"],"TO","nonull:{to_date}",$_SESSION["STATSCOM_DAY"]["TO"]);
    $form[]=$tpl->field_array_hash($TIMES,"TO_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"TO_MIN","nonull:{minutes}",$tb[1]);


    echo $tpl->form_outside("{build_query}",$form,null,"{launch}","Loadjs('$page?build-full-query=yes');","AsWebStatisticsAdministrator",true);

}

function build_full_query(){
    $page=CurrentPageName();
    $Date1=strtotime($_SESSION["STATSCOM_DAY"]["FROM"]." ".$_SESSION["STATSCOM_DAY"]["FROMH"]);
    $Date2=strtotime($_SESSION["STATSCOM_DAY"]["TO"]." ".$_SESSION["STATSCOM_DAY"]["TOH"]);
    $srctype=$_SESSION["STATSCOM_DAY"]["STATSCOM_SRC"];
    $data=urlencode($_SESSION["STATSCOM_DAY"]["STATSCOM_SRC_DATA"]);
    echo "Loadjs('$page?member-stats=yes&date-from=$Date1&date-to=$Date2&srctype=$srctype&data=$data')";
}

function BUILD_DEFAULT_DATA(){

    if(!isset($_SESSION["STATSCOM_DAY"]["FROM"])){
        $_SESSION["STATSCOM_DAY"]["FROM"]=date("Y-m-d");
    }
    if(!isset($_SESSION["STATSCOM_DAY"]["TO"])){
        $_SESSION["STATSCOM_DAY"]["TO"]=date("Y-m-d");
    }

    if(!isset($_SESSION["STATSCOM_DAY"]["FROMH"])){
        $_SESSION["STATSCOM_DAY"]["FROMH"]="00:00";
    }
    if(!isset($_SESSION["STATSCOM_DAY"]["TOH"])){
        $_SESSION["STATSCOM_DAY"]["TOH"]="23:59";
    }
    if(!isset($_SESSION["STATSCOM_DAY"]["LIMIT"])){
        $_SESSION["STATSCOM_DAY"]["LIMIT"]="250";
    }
    if(intval($_SESSION["STATSCOM_DAY"]["LIMIT"])==0){
        $_SESSION["STATSCOM_DAY"]["LIMIT"]=250;
    }

    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");
    $_SESSION["STATSCOM_DAY"]["DIFF_MIN"]=round(($time2-$time1)/60);

}

function query_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $_SESSION["STATSCOM_DAY"]["FROM"]=$_POST["FROM"];
    $_SESSION["STATSCOM_DAY"]["TO"]=$_POST["TO"];
    $_SESSION["STATSCOM_DAY"]["FROMH"]=$_POST["FROM_HOUR"].":".$_POST["FROM_MIN"];
    $_SESSION["STATSCOM_DAY"]["TOH"]=$_POST["TO_HOUR"].":".$_POST["TO_MIN"];
    $_SESSION["STATSCOM_DAY"]["STATSCOM_SRC"]=$_POST["STATSCOM_SRC"];
    $_SESSION["STATSCOM_DAY"]["STATSCOM_SRC_DATA"]=$_POST["STATSCOM_SRC_DATA"];


    BUILD_DEFAULT_DATA();

}

function template(){
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $imgwait="<img src='/img/Eclipse-0.9s-120px.gif'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top'><div id='vertical-timeline-$t'>$imgwait</div></td>";
    $html[]="</tr>";
    $html[]="</table>";



    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top' width='2%' nowrap><div id='top-sites-table-$t'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top'><div id='top-sites-$t' style='height:600px;width:1340px'>$imgwait</div></td>";
    $html[]="</tr>";


    $html[]="<tr>";
    $html[]="<td colspan=2><hr></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top' width='2%' nowrap><div id='top-sites-categories-$t'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top'><div id='top-categories-$t' style='height:600px;width:1340px'>$imgwait</div></td>";
    $html[]="</tr>";



    $html[]="<tr>";
    $html[]="<td colspan=2><hr></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top' width='2%' nowrap><div id='top-users-table-$t'>$imgwait</div></td>";
    $html[]="<td style='vertical-align:top'><div id='top-users-$t' style='height:600px;width:1340px'>$imgwait</div></td>";
    $html[]="</tr>";
    $html[]="</table>";

    $html[]="<script>";
    $html[]="Loadjs('$page?vertical-timeline=yes&id=vertical-timeline-$t');";
    $html[]="Loadjs('$page?top-sites=yes&id=top-sites-$t&suffix=$t');";
    $html[]="Loadjs('$page?top-users=yes&id=top-users-$t&suffix=$t');";
    $html[]="Loadjs('$page?top-categories=yes&id=top-categories-$t&suffix=$t');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);


}

// Fenetre d'observation. Le SIEM repond par PERIODE: contrairement a l'ancien annuaire
// statscom_users, qui etait une liste cumulative sans date, un membre n'existe ici que s'il a
// emis pendant la fenetre choisie. C'est la difference de fond entre les deux sources, et c'est
// pourquoi la periode est un controle visible et non une constante cachee.
/**
 * Liste blanche du type de membre.
 *
 * $srctype finit comme NOM DE COLONNE dans les requetes statscom (`and $srctype='...'`): il
 * n'est donc pas echappable, il doit etre choisi. Toute valeur hors liste retombe sur
 * `username`, jamais sur la valeur fournie.
 */
function scm_srctype():string{
    $v=isset($_GET["srctype"])?strtolower(trim((string)$_GET["srctype"])):"";
    if($v==="ipaddr" || $v==="mac"){return $v;}
    return "username";
}

/**
 * Echappe une valeur destinee a une chaine SQL statscom.
 *
 * L'identifiant du membre arrive de l'URL et partait BRUT entre apostrophes: une apostrophe
 * dans un nom d'utilisateur suffisait a detourner la requete.
 */
function scm_sqlval($v):string{
    if(function_exists("pg_escape_string2")){return pg_escape_string2((string)$v);}
    return str_replace("'","''",(string)$v);
}

function scm_period(){
    $p=isset($_GET["period"])?$_GET["period"]:"24h";
    $to=time();
    switch($p){
        case "1h":  $from=$to-3600; break;
        case "7d":  $from=$to-(7*86400); break;
        case "30d": $from=$to-(30*86400); break;
        default:    $p="24h"; $from=$to-86400; break;
    }
    return array($p, gmdate("Y-m-d\\TH:i:s\\Z",$from), gmdate("Y-m-d\\TH:i:s\\Z",$to));
}

function page_de_guarde(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    list($p,,)=scm_period();
    // La période et le périmètre (tenant/site/nœud) viennent du TINY et sont propagés
    // au search_block, donc à search(). Le segment de période qui vivait ici a été
    // déplacé dans le TINY — un seul jeu de contrôles gouverne la page.
    $fq=scm_filter_qs();

    $html=array();
    $html[]="<div class='awc-muted' style='margin:8px 0 0 2px'>{statscom_members_siem_hint}</div>";
    $html[]="<div style='margin-top:6px'>";
    $html[]=$tpl->search_block($page,"","","","&period=$p".$fq."&reload=1");
    $html[]="</div>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function top_sites(){
    $page=CurrentPageName();
    $t=$_GET["suffix"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    BUILD_DEFAULT_DATA();
    $DIFF_MIN=$_SESSION["STATSCOM_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    $sql="SELECT SUM(statscom.size) as size,statscom_websites.familysite,statscom.siteid FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by familysite,statscom.siteid ORDER BY size DESC LIMIT 15";

    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $PieData2[$ligne["familysite"]]=array($size,$ligne["siteid"]);
        $PieData[$ligne["familysite"]]=$size;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_websites_by_size} (MB)";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

    echo "LoadAjax('top-sites-table-$t','$page?sites-table=$encoded');";


}
function top_categories(){
    $page=CurrentPageName();
    $t=$_GET["suffix"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    BUILD_DEFAULT_DATA();
    $DIFF_MIN=$_SESSION["STATSCOM_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    $sql="SELECT SUM(statscom.size) as size,statscom_websites.category FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND zdate >'$strtime' and zdate < '$strtoTime' GROUP by category ORDER BY size DESC LIMIT 15";

    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    $catz=new mysql_catz();
    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $category=$catz->CategoryIntToStr($ligne["category"]);
        $PieData[$category]=$size;
        $PieData2[$ligne["category"]]=$ligne["size"]/1024;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_categories_by_size} (MB)";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

    echo "LoadAjax('top-sites-categories-$t','$page?categories-table=$encoded');";


}

function categories_table(){
    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{category}</th>";
    $html[]="<th>{size}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["categories-table"]),array("allowed_classes"=>false));
    $catz=new mysql_catz();
    foreach ( $table as $categoryid=>$sizeM) {

        // Pentest 2026-11-04 : categoryid (donnee externe) affiche sans encodage (XSS stockee).
        $categoryid=intval($categoryid);
        $category=$catz->CategoryIntToStr($categoryid);
        $link=$tpl->td_href($category,null,"Loadjs('fw.statscom.browse.categories.php?category=$categoryid')");

        $html[]="<tr>";
        $html[]="<td><strong>$link</strong></td>";
        $html[]="<td><strong>". FormatBytes($sizeM)."</strong></td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);

}

function top_users(){

    $page=CurrentPageName();
    $t=$_GET["suffix"];
    $tpl=new template_admin();
    $q=new postgres_sql();
    BUILD_DEFAULT_DATA();
    $DIFF_MIN=$_SESSION["STATSCOM_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    $sql="SELECT SUM(size) as size,username,ipaddr,MAC FROM statscom  WHERE zdate >'$strtime' and zdate < '$strtoTime' GROUP by username,ipaddr,MAC ORDER BY size DESC LIMIT 15";

    $q->QUERY_SQL($sql);
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
        $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
        if($resolveIP2HOST==1){
            $host= new ip2host($ipaddr);
            $ipaddr=$host->output;
        }
        $MAC=$ligne["MAC"];
        $issu=array();
        $issu[]=$username;
        $issu[]=$MAC;
        $issu[]=$ipaddr;
        $PieData2[@implode("|",$issu)]=$size;
        if($username<>null){
            $PieData[$username]=$size;
            continue;
        }
        if($MAC<>null){
            $PieData[$MAC]=$size;
            continue;
        }
        $PieData[$ipaddr]=$size;
    }

    $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = "{top_members} (MB)";
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();

    echo "LoadAjax('top-users-table-$t','$page?users-table=$encoded');";


}

function users_table(){

    $tpl=new template_admin();
    $html[]="<table style='width:100%' class='table'>";
    $html[]="<tr>";
    $html[]="<th>{member}</th>";
    $html[]="</tr>";
    $table=unserialize(base64_decode($_GET["users-table"]),array("allowed_classes"=>false));
    foreach ( $table as $site=>$sizeM) {
        $h=explode("|",$site);
        $t=array();
        if($h[0]<>null){
            $t[]=$tpl->td_href($h[0],null,"Loadjs('fw.statscom.browse.users.php?field=username&value=".urlencode($h[0])."')");
        }
        if($h[1]<>null){
            $t[]=$tpl->td_href($h[1],null,"Loadjs('fw.statscom.browse.users.php?field=mac&value=".urlencode($h[1])."')");
        }
        if($h[2]<>null){
            $t[]=$tpl->td_href($h[2],null,"Loadjs('fw.statscom.browse.users.php?field=ipaddr&value=".urlencode($h[2])."')");
        }

        $html[]="<tr>";
        $html[]="<td><strong>".@implode("&nbsp;|&nbsp;",$t)."</strong></td>";
        $html[]="<td>".FormatBytes($sizeM*1024)."</td>";
        $html[]="</tr>";

    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

function vertical_time_line(){
    $tpl=new template_admin();
    $q=new postgres_sql();
    BUILD_DEFAULT_DATA();
    $DIFF_MIN=$_SESSION["STATSCOM_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    echo "// DIFF: $DIFF_MIN hours";
    $time1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);
    $UNIT="{each_10minutes}";
    $SQL_DATE="zdate";



    if($DIFF_HOURS>6){
        $UNIT="{hourly}";
        $SQL_DATE="date_part('hour', zdate)";
    }

    if($DIFF_HOURS>48) {
        $UNIT = "{daily}";
        $SQL_DATE = "date_part('day', zdate)";
    }

    $sql="SELECT SUM(size) as size,$SQL_DATE as zdate FROM statscom WHERE zdate >'$strtime' and zdate < '$strtoTime' GROUP by $SQL_DATE ORDER BY $SQL_DATE";


    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    while ($ligne = pg_fetch_assoc($results)) {
        $size = $ligne["size"];
        $size = $size / 1024;
        $size = $size / 1024;
        $size = round($size);
        echo "// {$ligne["zdate"]}\n";
        $stime=strtotime($ligne["zdate"]);
        $datetext=date("H:i",$stime);
        if($DIFF_HOURS>6){
            $datetext="{$ligne["zdate"]}h";
        }
        if($DIFF_HOURS>48){
            $datetext=$tpl->_ENGINE_parse_body("{day}")." {$ligne["zdate"]}";
        }
        $xdata[]=$datetext;
        $ydata[]=$size;
    }


    $stimeFrom=$tpl->time_to_date($time1);
    $stimeTo=$tpl->time_to_date($time2);
    $title="{downloaded_flow} (MB) $stimeFrom - $stimeTo {$UNIT}";
    $timetext=$_GET["interval"];
    $highcharts=new highcharts();
    $highcharts->container=$_GET["id"];
    $highcharts->xAxis=$xdata;
    $highcharts->Title=$title;
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->yAxisTtitle="MB";
    $highcharts->xAxis_labels=true;

    $highcharts->LegendSuffix="MB";
    $highcharts->xAxisTtitle=$timetext;
    $highcharts->datas=array("{size}"=>$ydata);
    echo $highcharts->BuildChart();
}



function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{statscom_members_title}","fas fa-users","{statscom_members_explain}",
        "$page?build-page=yes","siem-proxy-members","progress-siem-proxy-members",false,"table-siem-proxy-members");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{statscom_members_title}",$html);
        echo $tpl->build_firewall();return;
    }
    echo $tpl->_ENGINE_parse_body($html);
}

// build_page — le corps chargé par l'en-tête : le conteneur de la liste des membres
// (rechargé par LoadAjaxSilent) et le TINY (période + tenant / site / nœud). Les
// filtres du TINY GOUVERNENT toute la page ; ScmReload relit leurs valeurs et
// recharge la liste avec.
function build_page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $stats=new statistics_appliance();
    if($stats->denied){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{access_denied}")); return; }

    list($p,,)=scm_period();
    $q="page-de-guarde=yes&period=$p".scm_filter_qs();

    $html=array();
    $html[]="<div id='stats-com-members' style='margin-top:10px'></div>";
    $html[]="<script>LoadAjaxSilent('stats-com-members','$page?".$q."');</script>";

    // ScmReload lit période + tenant/site/nœud dans le TINY et recharge la liste.
    // ScmPeriod fixe la période (segment) puis recharge.
    $html[]="<script>".
        "var SCM={period:'".$p."'};".
        "function ScmReload(){var u='$page?page-de-guarde=yes&period='+encodeURIComponent(SCM.period);".
        "var t=document.getElementById('scm-tenant');if(t&&t.value){u+='&tenants='+encodeURIComponent(t.value);}".
        "var s=document.getElementById('scm-site');if(s&&s.value){u+='&site='+encodeURIComponent(s.value);}".
        "var n=document.getElementById('scm-node');if(n&&n.value){u+='&proxy_id='+encodeURIComponent(n.value);}".
        "LoadAjaxSilent('stats-com-members',u);}".
        "function ScmPeriod(p){SCM.period=p;ScmReload();}".
        "</script>";

    $topbuttons=array();
    $topbuttons[]=array("ScmReload()",ico_refresh,"{refresh}");
    $TINY=array();
    $TINY["TITLE"]="{statscom_members_title}";
    $TINY["ICO"]="fas fa-users";
    $TINY["EXPL"]="{statscom_members_explain}";
    $TINY["URL"]="siem-proxy-members";
    $TINY["BUTTONS"]=siem_tiny_filters(scm_tiny_fields($stats),$tpl->table_buttons($topbuttons));
    $html[]="<script>NoSpinner();\nLoadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY)))."');</script>";

    echo siem_tiny_filters_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
}

// scm_tiny_fields — les champs du TINY : période (segment), tenant / site / nœud.
function scm_tiny_fields($stats){
    list($p,$from,$to)=scm_period();
    $fields=array();
    $fields[]=array("TYPE"=>"segment","ID"=>"scm-period","LABEL"=>"{period}",
        "VALUE"=>$p,"JS"=>"ScmPeriod",
        "OPTIONS"=>array("1h"=>"1h","24h"=>"24h","7d"=>"7d","30d"=>"30d"));

    // Tenant : admin global -> tous ; opérateur cumulant ≥2 tenants -> son ensemble.
    $tenantOpts=array();
    if($stats->global){
        foreach(siem_admin_tenants() as $t){ $tenantOpts[$t["id"]]=$t["name"]; }
    }else{
        $set=array_filter(array_map("trim",explode(",",(string)$stats->tenant)));
        if(count($set)>=2){ foreach($set as $tt){ $tenantOpts[$tt]=$tt; } }
    }
    if(count($tenantOpts)>0){
        $fields[]=array("TYPE"=>"select","ID"=>"scm-tenant","LABEL"=>"{tenant}",
            "VALUE"=>isset($_GET["tenants"])?trim((string)$_GET["tenants"]):"",
            "OPTIONS"=>array(""=>"{all}")+$tenantOpts,"JS"=>"ScmReload");
    }

    // Site (émetteur, migration 0039 : node_sites ∪ proxies.site_id).
    $siteOpts=siem_sites_options();
    if(count($siteOpts)>1){
        $fields[]=array("TYPE"=>"select","ID"=>"scm-site","LABEL"=>"{siem_map_site}",
            "VALUE"=>isset($_GET["site"])?trim((string)$_GET["site"]):"",
            "OPTIONS"=>$siteOpts,"JS"=>"ScmReload");
    }

    // Nœud (proxy_id) : les collecteurs actifs sur la période.
    $nodeOpts=array(""=>"{all}");
    $nres=$stats->get("/api/v1/statistics/proxies?from=".urlencode($from)."&to=".urlencode($to));
    if(is_array($nres)&&isset($nres["items"])&&is_array($nres["items"])){
        foreach($nres["items"] as $it){
            $k=isset($it["key"])?trim((string)$it["key"]):"";
            if($k!==""&&$k!=="unknown"){ $nodeOpts[$k]=$k; }
        }
    }
    if(count($nodeOpts)>1){
        $fields[]=array("TYPE"=>"select","ID"=>"scm-node","LABEL"=>"{siem_map_emitter}",
            "VALUE"=>isset($_GET["proxy_id"])?trim((string)$_GET["proxy_id"]):"",
            "OPTIONS"=>$nodeOpts,"JS"=>"ScmReload");
    }
    return $fields;
}

function query(){
    $tpl=new template_admin();
    $catz=new mysql_catz();
    $q=new postgres_sql();
    $t=time();
    $TRCLASS=null;$WHEREU=null;$WHEREM=null;$WHEREI=null;$WHERES=null;
    //86340 = 24H
    // 172740  =



    $time1=strtotime( $_SESSION["STATSCOM_DAY"]["FROM"]." ". $_SESSION["STATSCOM_DAY"]["FROMH"]);
    $time2=strtotime($_SESSION["STATSCOM_DAY"]["TO"]." ". $_SESSION["STATSCOM_DAY"]["TOH"]);

    $seconds=$time2-$time1;

    if($seconds<0){
       echo  $tpl->div_error("{please_define_dates_in_correct_order}");
        return;
    }



    if($seconds<259150){
        $TABLE_SELECTED="statscom";
        $date1=$_SESSION["STATSCOM_DAY"]["FROM"]." ". $_SESSION["STATSCOM_DAY"]["FROMH"];
        $date2=$_SESSION["STATSCOM_DAY"]["TO"]." ". $_SESSION["STATSCOM_DAY"]["TOH"];
        $tt2=date("Y-m-d",strtotime($date2));

        $TITLES[]="{from} " .$tpl->time_to_date(strtotime($date1),true);
        if($tt2==date("Y-m-d")) {
            $TITLES[] = "{to} {today} " . $tpl->time_to_date(strtotime($date2), true);
        }else{
            $TITLES[] = "{to} " . $tpl->time_to_date(strtotime($date2), true);
        }
    }else{
        $TABLE_SELECTED="statscom_days";
        $date1=$_SESSION["STATSCOM_DAY"]["FROM"];
        $date2=$_SESSION["STATSCOM_DAY"]["TO"];
        $tt2=date("Y-m-d",strtotime($date2));
        $TITLES[]="{from} " .$tpl->time_to_date(strtotime($date1),false);
        if($tt2==date("Y-m-d")) {
            $TITLES[] = "{to} {today} " . $tpl->time_to_date(strtotime($date2), false);
        }else{
            $TITLES[] = "{to} " . $tpl->time_to_date(strtotime($date2), false);
        }
    }

    $TITLES[]="(".distanceOfTimeInWords(strtotime($date1),strtotime($date2)).")";


    // Pentest 2026-11-04 : dates, USER, IP, MAC et LIMIT (session remplie depuis $_POST) etaient concatenes dans la requete.
    $sqlParams=array((string)$date1,(string)$date2);
    if($_SESSION["STATSCOM_DAY"]["USER"]<>null){
        $TITLES[]="{and} {member} {$_SESSION["STATSCOM_DAY"]["USER"]}";
        $sqlParams[]=(string)$_SESSION["STATSCOM_DAY"]["USER"];
        $WHEREU=" AND username=\$".count($sqlParams);
    }
    if($_SESSION["STATSCOM_DAY"]["IP"]<>null){
        $TITLES[]="{and} {ipaddr} {$_SESSION["STATSCOM_DAY"]["IP"]}";
        $sqlParams[]=(string)$_SESSION["STATSCOM_DAY"]["IP"];
        $WHEREI=" AND ipaddr=\$".count($sqlParams);
    }
    if($_SESSION["STATSCOM_DAY"]["MAC"]<>null){
        $TITLES[]="{and} {MAC} {$_SESSION["STATSCOM_DAY"]["MAC"]}";
        $sqlParams[]=(string)$_SESSION["STATSCOM_DAY"]["MAC"];
        $WHEREM=" AND mac=\$".count($sqlParams);
    }
    $sqlLimit=intval($_SESSION["STATSCOM_DAY"]["LIMIT"]);
    if($sqlLimit<1){$sqlLimit=250;}
    if( intval($_SESSION["STATSCOM_DAY"]["SIZE"])>0){
        $size_q=round(intval( $_SESSION["STATSCOM_DAY"]["SIZE"])*1024);
        $WHERES=" $TABLE_SELECTED.size > $size_q AND";
    }

    $html[]="<H2 style='margin-top: 10px'>".@implode(" ",$TITLES)."</H2>";


    $sql="SELECT SUM($TABLE_SELECTED.hits) as hits, 
    SUM($TABLE_SELECTED.size) as size,
    $TABLE_SELECTED.username,
    $TABLE_SELECTED.ipaddr,
    $TABLE_SELECTED.mac,
    $TABLE_SELECTED.zdate,
    statscom_websites.sitename,
    statscom_websites.category   
    FROM $TABLE_SELECTED,statscom_websites WHERE{$WHERES}
    statscom_websites.siteid=$TABLE_SELECTED.siteid AND
    $TABLE_SELECTED.zdate >=\$1 AND  $TABLE_SELECTED.zdate <= \$2{$WHEREU}{$WHEREI}{$WHEREM}
    GROUP BY $TABLE_SELECTED.zdate,$TABLE_SELECTED.username,$TABLE_SELECTED.ipaddr,$TABLE_SELECTED.mac,statscom_websites.sitename, statscom_websites.category ORDER BY $TABLE_SELECTED.zdate
    LIMIT $sqlLimit";

    $results=$q->QUERY_SQL_PARAMS($sql,$sqlParams);
    if(!$q->ok){
        echo  $tpl->_ENGINE_parse_body($html);
        echo  $tpl->div_error("{$seconds}s<br>".$q->mysql_error."<br>$sql");
        return;
    }

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
           ."<table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{sitename}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{member}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{MAC}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hits}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody id='scmd-rows'>";

    $td1=$tpl->table_td1prc_Left();
    $tdfree=$tpl->table_tdfree();

    while ($ligne = pg_fetch_assoc($results)) {
        $TRCLASS=null;
        $hits=$tpl->FormatNumber($ligne["hits"]);
        $size=FormatBytes($ligne["size"]/1024);
        $date=$ligne["zdate"];
        $sitename=$ligne["sitename"];
        $category_id=$ligne["category"];
        $category=$catz->CategoryIntToStr($category_id);
        $ipaddr=$ligne["ipaddr"];
        $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
        if($resolveIP2HOST==1){
            $host= new ip2host($ipaddr);
            $ipaddr=$host->output;
        }
        $username=$ligne["username"];
        $mac=$ligne["mac"];

        $params=array();
        $params[]="username=".urlencode($username);
        $params[]="category=".urlencode($category_id);
        $params[]="sitename=".urlencode($sitename);
        $params[]="mac=".urlencode($mac);
        $params[]="ipaddr=".urlencode($ipaddr);
        $params[]="date1=".urlencode($date1);
        $params[]="date2=".urlencode($date2);

        $parm=@implode("&",$params);

        $category=$tpl->td_href($category,"{statistics}","Loadjs('fw.statscom.category.php?data=yes&$params')");


        // Pentest 2026-11-04 : site/membre/MAC/IP (donnee externe) affiche sans encodage (XSS stockee).
        $date=req_html($date);$sitename=req_html($sitename);$username=req_html($username);
        $mac=req_html($mac);$ipaddr=req_html($ipaddr);
        $html[]="<tr style='vertical-align:middle' class='$TRCLASS'>";
        $html[]="<td $td1>$date</td>";
        $html[]="<td $tdfree>$sitename</td>";
        $html[]="<td $td1>$category</td>";
        $html[]="<td $td1>$username</td>";
        $html[]="<td $td1>$mac</td>";
        $html[]="<td $td1>$ipaddr</td>";
        $html[]="<td $td1>$size</td>";
        $html[]="<td $td1>$hits</td>";
        $html[]="</tr>";


    }

    $html[]="</tbody>";
    $html[]="</table></div><div class='awc-foot'><span>&nbsp;</span>"
           ."<span class='awc-pg' id='scmd-pager'></span></div></div></div>";
    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
</script>";

    // Le pager remplace la pagination FooTable (data-page-size=100) que portait
    // ce tableau. Il se concatene APRES le parse: son JS contient des blocs
    // {...} que la regex #{(.+?)}# du moteur de traduction lirait en {token}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n", $html))
        .awc_pager_js(100,"scmd-rows","scmd-pager");

}

function search(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $stats=new statistics_appliance();
    if($stats->denied){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{access_denied}")); return; }
    list($p,$from,$to)=scm_period();

    $term=isset($_GET["search"])?trim((string)$_GET["search"]):"";
    $termlo=strtolower($term);

    // Deux classements, PAS une jointure. Le SIEM agrege les utilisateurs et les adresses
    // clientes separement (`username` et `client_ip` vivent chacun dans son propre etat uniq):
    // il n'existe aucune reponse qui associe l'un a l'autre. L'ancien annuaire portait le
    // triplet (utilisateur, adresse, MAC) parce qu'il etait alimente ligne a ligne; le
    // reconstituer ici demanderait de lire la table brute, ce qu'aucun tableau de bord ne fait.
    // On liste donc les membres des DEUX cotes, en disant lequel est lequel.
    // Le périmètre (tenant/site/nœud) du TINY est appliqué aux DEUX classements : le
    // serveur résout le site en proxy_id (node_sites ∪ proxies.site_id) et le tenant
    // reste résolu côté serveur.
    $qs="from=".urlencode($from)."&to=".urlencode($to)."&limit=200&order_by=bytes".scm_filter_qs();
    $ru=$stats->get("/api/v1/statistics/top/users?$qs");
    if(!$stats->ok()){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{api_unavailable}")); return; }
    $ri=$stats->get("/api/v1/statistics/top/client-ips?$qs");
    if(!$stats->ok()){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{api_unavailable}")); return; }

    $hideUnknown=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("statscomHideUnkownMembers"));
    $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));

    $members=array();
    foreach(array("username"=>$ru,"ipaddr"=>$ri) as $srctype=>$resp){
        $items=(is_array($resp) && isset($resp["items"]) && is_array($resp["items"]))?$resp["items"]:array();
        foreach($items as $it){
            $key=trim((string)(isset($it["key"])?$it["key"]:""));
            // Trafic non authentifie. Squid ecrit un TIRET, pas une chaine vide -- mesure sur
            // une appliance reelle: la premiere ligne du classement est `-` avec 153 Go. Ne pas
            // le reconnaitre afficherait un membre litteralement nomme « - » en tete de tableau.
            // C'est une absence d'identite, pas un membre: on garde la ligne (le trafic est reel)
            // sans lui inventer de nom, et le reglage existant permet de la masquer.
            if($key==="" || $key==="-"){
                $key="";
                if($srctype==="username" && $hideUnknown==1){ continue; }
            }
            if($termlo!=="" && strpos(strtolower($key),$termlo)===false){ continue; }
            $members[]=array(
                "srctype"=>$srctype,
                "key"=>$key,
                "tenant"=>trim((string)(isset($it["tenant"])?$it["tenant"]:"")),
                "requests"=>isset($it["requests"])?(float)$it["requests"]:0,
                // ABSENT N'EST PAS ZERO: un classement ordonne par octets renvoie toujours la
                // mesure, mais on ne la fabrique pas si elle manque.
                "bytes"=>isset($it["bytes"])?(float)$it["bytes"]:null,
            );
        }
    }
    usort($members,function($a,$b){
        $x=($a["bytes"]===null)?-1:$a["bytes"];
        $y=($b["bytes"]===null)?-1:$b["bytes"];
        if($x==$y){return 0;}
        return ($x<$y)?1:-1;
    });

    $multi=false;
    foreach($members as $m){ if($m["tenant"]!==""){ $multi=true; break; } }

    $rows=array();
    foreach($members as $m){
        $key=$m["key"];
        $isuser=($m["srctype"]==="username");
        if($key===""){
            $label="{unknown}";
            $link=$label;
        }else{
            $shown=$key;
            // La resolution inverse ne concerne que les adresses, et elle reste facultative:
            // c'est un aller-retour DNS par ligne.
            if(!$isuser && $resolveIP2HOST==1){
                $h=new ip2host($key);
                if(trim((string)$h->output)!==""){ $shown=$h->output; }
            }
            // Comme fw.statistics.rollup.php : le membre ouvre la modale de drill-down
            // (fw.statistics.rollup.drill.php) sur la PÉRIODE et le PÉRIMÈTRE courants
            // (tenant/site/nœud). username -> kind username ; ipaddr -> client_ip. La
            // valeur brute part rawurlencodée (c'est ce que l'API filtre).
            $drillKind=$isuser?"username":"client_ip";
            $durl="fw.statistics.rollup.drill.php?drill-js=yes&kind=".$drillKind.
                  "&value=".rawurlencode($key)."&period=".rawurlencode($p).scm_filter_qs();
            $link="<a href=\"javascript:blur();\" onclick=\"Loadjs('".$durl."')\">".htmlspecialchars($shown)."</a>";
        }
        $ico=$isuser?"fas fa-user":"fas fa-desktop";
        $ident="<div class='awc-ident'><span class='awc-ico'><i class='$ico'></i></span><span>".
               "<span class='awc-name'>".$link."</span>";
        if(!$isuser && $key!=="" && $resolveIP2HOST==1){ $ident.="<div class='awc-meta'>".htmlspecialchars($key)."</div>"; }
        $ident.="</span></div>";

        $vol=($m["bytes"]===null)?"<span class='awc-none'>-</span>":siem_bytes($m["bytes"]);

        $h ="<tr>";
        $h.="<td>".$ident."</td>";
        $h.="<td class='awc-nowrap'><span class='awc-tag'>".($isuser?"{member}":"{ipaddr}")."</span></td>";
        // awc-tag et non siem_row_tenant(): la puce de perimetre tire son style de
        // siem_scope_style(), que cette page n'emet pas -- elle sortirait sans mise en forme.
        if($multi){ $h.="<td class='awc-nowrap'>".($m["tenant"]===""?"":"<span class='awc-tag'>".htmlspecialchars($m["tenant"])."</span>")."</td>"; }
        $h.="<td class='awc-right awc-nowrap'>".$tpl->FormatNumber($m["requests"])."</td>";
        $h.="<td class='awc-right awc-nowrap'>".$vol."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $shown=count($rows);
    $cols=$multi?5:4;
    $html=array();
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{member}</th><th>{type}</th>";
    if($multi){ $html[]="<th>{tenant}</th>"; }
    $html[]="<th class='awc-right'>{requests}</th><th class='awc-right'>{volume}</th>";
    $html[]="</tr></thead><tbody id='scmm-rows'>";
    $html[]=($shown==0)?"<tr><td colspan='$cols'><div class='awc-empty'>{no_data}</div></td></tr>":implode("\n",$rows);
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $shown</span><span class='awc-pg' id='scmm-pager'></span></div>";
    $html[]="</div>";

    $js="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$js
        .awc_pager_js(50,"scmm-rows","scmm-pager");
}

/**
 * Interroge le zoom proxy du SIEM pour un membre, et renvoie la reponse decodee.
 *
 * Un seul appel sert les trois panneaux: la reponse porte a la fois la serie, les classements
 * et les totaux. En emettre un par panneau ferait payer trois fois la meme lecture.
 */
function scm_zoom_fetch($kind,$value,$win,&$err){
    $err="";
    $stats=new statistics_appliance();
    if($stats->denied){ $err="{access_denied}"; return null; }
    $o=$stats->get("/api/v1/statistics/zoom?kind=".rawurlencode($kind)."&value=".rawurlencode($value).
                   "&window=".rawurlencode($win));
    if(!$stats->ok()){ $err="{api_unavailable}"; return null; }
    return is_array($o)?$o:null;
}

// Retrouve un classement par son nom dans la reponse de zoom.
function scm_zoom_ranking($o,$name){
    if(!is_array($o) || !isset($o["rankings"]) || !is_array($o["rankings"])){return array();}
    foreach($o["rankings"] as $rk){
        if(isset($rk["name"]) && $rk["name"]===$name){
            return (isset($rk["rows"]) && is_array($rk["rows"]))?$rk["rows"]:array();
        }
    }
    return array();
}

// En-tete commun aux trois panneaux: la fenetre reellement rendue.
function scm_pane_head($win){
    return "<div class='awc-muted' style='margin:4px 0 8px 2px'>".
           "<i class='fas fa-circle-info'></i> {siem_zoom_window_note}: <b>".scm_window_label($win)."</b></div>";
}

/**
 * Prepare les trois panneaux: valide le membre, choisit la fenetre, appelle le SIEM.
 *
 * Renvoie false APRES avoir affiche la raison. Les trois panneaux partagent exactement les
 * memes conditions d'echec (type de membre sans equivalent SIEM, valeur vide, API muette), et
 * les recopier trois fois garantissait qu'elles finiraient par diverger.
 */
function scm_pane_prepare(&$kind,&$value,&$win,&$o){
    $tpl=new template_admin();
    $srctype=scm_srctype();
    $value=(string)$_GET["data"];
    $kind=scm_zoom_kind($srctype);
    if($kind===""){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{MAC}||{statscom_member_mac_unsupported}"));
        return false;
    }
    if(trim($value)===""){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{no_data}"));
        return false;
    }
    $win=scm_zoom_window(intval($_GET["date-from"]),intval($_GET["date-to"]));
    $err="";
    $o=scm_zoom_fetch($kind,$value,$win,$err);
    if($o===null){
        echo $tpl->_ENGINE_parse_body($tpl->div_error($err));
        return false;
    }
    return true;
}

/**
 * Palmares du membre.
 *
 * Anciennement quatre camemberts Highcharts alimentes par statscom. Desormais la vue de zoom
 * du SIEM, qui rend les memes quatre classements (sites par requetes, sites par volume,
 * categories, classes HTTP) en Chart.js -- deleguee, pas recopiee.
 */
function member_stats_pane_sites(){
    $tpl=new template_admin();
    $srctype=scm_srctype();
    $value=(string)$_GET["data"];
    $kind=scm_zoom_kind($srctype);
    if($kind===""){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{MAC}||{statscom_member_mac_unsupported}"));
        return;
    }
    if(trim($value)===""){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{no_data}")); return; }
    $id="scmd".md5($srctype.$value);
    // data-view et non win-view: ce dernier est deja rendu par l'onglet d'activite, et deux
    // onglets rendant le meme fragment ne sont pas deux vues, c'est la meme deux fois.
    // `data-view` est la seule lecture SIEM par membre encore inutilisee: le detail heure par
    // heure, site par site, avec rebond vers le zoom du site.
    echo awc_table_style().$tpl->_ENGINE_parse_body("<div class='awc-muted' style='margin:4px 0 8px 2px'>".
        "<i class='fas fa-circle-info'></i> {siem_detail_today_note}</div><div id='$id'></div>").
        "<script>LoadAjax('$id','fw.statistics.events.zoom.php?data-view=yes&kind=".urlencode($kind).
        "&value=".urlencode($value)."');</script>";
}

/**
 * Les sites du membre, en tableau.
 *
 * Fusion des DEUX classements de domaines que renvoie le zoom: celui ordonne par requetes et
 * celui ordonne par volume. Chaque ligne porte deja les deux mesures; l'union donne donc un
 * tableau complet sur les sites qui comptent, sans requete supplementaire.
 *
 * Ce n'est PAS la liste exhaustive des sites visites, et le panneau le dit: le SIEM plafonne
 * chaque classement a dix entrees. Presenter cette union comme « les sites du membre » ferait
 * lire une absence comme un zero.
 */
function member_stats_websites(){
    $tpl=new template_admin();
    $kind="";$value="";$win="";$o=null;
    if(!scm_pane_prepare($kind,$value,$win,$o)){return;}

    $merged=array();
    foreach(array("top_domains","top_domains_bytes") as $rname){
        foreach(scm_zoom_ranking($o,$rname) as $row){
            $k=trim((string)(isset($row["key"])?$row["key"]:""));
            if($k===""){continue;}
            // La cle gagne ses mesures une seule fois: les deux classements portent les memes
            // valeurs pour un site present dans les deux, les additionner le compterait double.
            if(isset($merged[$k])){continue;}
            $merged[$k]=array(
                "requests"=>isset($row["requests"])?(float)$row["requests"]:0,
                "bytes"=>isset($row["bytes"])?(float)$row["bytes"]:null,
            );
        }
    }
    uasort($merged,function($a,$b){
        $x=($a["bytes"]===null)?-1:$a["bytes"]; $y=($b["bytes"]===null)?-1:$b["bytes"];
        if($x==$y){return 0;} return ($x<$y)?1:-1;
    });

    $rows=array();
    foreach($merged as $site=>$m){
        $link="<a href=\"javascript:blur();\" onclick=\"Loadjs('fw.statistics.events.zoom.php?zoom-js=yes&kind=domain&value=".
              rawurlencode($site)."&diag=5')\">".htmlspecialchars($site)."</a>";
        $vol=($m["bytes"]===null)?"<span class='awc-none'>-</span>":siem_bytes($m["bytes"]);
        $h ="<tr>";
        $h.="<td><div class='awc-ident'><span class='awc-ico'><i class='fas fa-globe'></i></span>".
            "<span><span class='awc-name'>".$link."</span></span></div></td>";
        $h.="<td class='awc-right awc-nowrap'>".$tpl->FormatNumber($m["requests"])."</td>";
        $h.="<td class='awc-right awc-nowrap'>".$vol."</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $shown=count($rows);
    $html=array();
    $html[]=scm_pane_head($win);
    $html[]="<div class='awc-muted' style='margin:0 0 8px 2px'><i class='fas fa-circle-info'></i> {siem_top_sites_capped}</div>";
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr><th>{domains}</th>".
            "<th class='awc-right'>{requests}</th><th class='awc-right'>{volume}</th></tr></thead><tbody id='scmw-rows'>";
    $html[]=($shown==0)?"<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>":implode("\n",$rows);
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $shown</span><span class='awc-pg' id='scmw-pager'></span></div>";
    $html[]="</div>";
    $js="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$js
        .awc_pager_js(50,"scmw-rows","scmw-pager");
}

/**
 * Trafic non classe du membre.
 *
 * L'ancienne version listait les SITES dont la categorie valait 0, en croisant statscom avec
 * statscom_websites. Ce croisement n'existe pas ici: le zoom filtre sur UNE entite, et rien ne
 * permet de demander « les sites de ce membre dont la categorie est inconnue ».
 *
 * Le panneau montre donc ce que le SIEM sait reellement dire -- la part non classee dans le
 * classement des categories du membre -- et nomme explicitement ce qu'il ne peut pas dire. Un
 * tableau vide se serait lu comme « tout est classe », qui est une affirmation, pas une absence.
 */
/**
 * Toutes les categories du membre, sur la periode REELLEMENT demandee.
 *
 * Utilise /api/v1/statistics/user/categories, ajoute au moteur Go pour ce croisement. Les deux
 * differences avec le classement du zoom sont exactement ce qui manquait ici:
 *   - la periode est celle des parametres, pas une fenetre nommee se terminant maintenant;
 *   - la limite est choisie, et la reponse dit si elle a coupe.
 *
 * Le panneau garde son titre « non categorises » mais montre desormais la repartition
 * COMPLETE, la part non classee en tete: un pourcentage n'a de sens qu'avec son denominateur,
 * et ce denominateur est le total renvoye par l'API -- pas la somme des lignes affichees.
 */
function member_stats_notcategorized(){
    $tpl=new template_admin();
    $srctype=scm_srctype();
    $username=(string)$_GET["data"];
    if(scm_zoom_kind($srctype)===""){
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{MAC}||{statscom_member_mac_unsupported}"));
        return;
    }
    // L'endpoint croise la CATEGORIE avec l'UTILISATEUR. Une adresse cliente n'est pas un
    // utilisateur: plutot que de lui renvoyer les categories de quelqu'un d'autre, on le dit.
    if($srctype!=="username"){
        echo $tpl->_ENGINE_parse_body($tpl->div_info("{category}||{siem_categories_user_only}"));
        return;
    }
    if(trim($username)===""){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{no_data}")); return; }

    $stats=new statistics_appliance();
    if($stats->denied){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{access_denied}")); return; }
    $from=intval($_GET["date-from"]);
    $to=intval($_GET["date-to"]);
    if($to<=$from){ $to=time(); $from=$to-86400; }
    $q="username=".rawurlencode($username).
       "&from=".rawurlencode(gmdate("Y-m-d\\TH:i:s\\Z",$from)).
       "&to=".rawurlencode(gmdate("Y-m-d\\TH:i:s\\Z",$to)).
       "&order_by=requests&limit=100";
    $r=$stats->get("/api/v1/statistics/user/categories?$q");
    if(!$stats->ok()){ echo $tpl->_ENGINE_parse_body($tpl->div_error("{api_unavailable}")); return; }

    $items=(is_array($r)&&isset($r["items"])&&is_array($r["items"]))?$r["items"]:array();
    // Le denominateur vient de l'API et porte sur TOUTES les categories, pas seulement sur
    // celles rendues. Recalculer une somme a partir des lignes affichees gonflerait chaque
    // pourcentage des le moment ou la limite mord.
    $totR=isset($r["total_requests"])?(float)$r["total_requests"]:0;
    $totB=isset($r["total_bytes"])?(float)$r["total_bytes"]:0;
    $truncated=!empty($r["truncated"]);

    // La part non classee remonte en tete: c'est la question que pose cet onglet.
    $unR=0;$unB=0;$found=false;
    $rows=array();
    foreach($items as $it){
        $key=trim((string)(isset($it["key"])?$it["key"]:""));
        $rq=isset($it["requests"])?(float)$it["requests"]:0;
        $by=isset($it["bytes"])?(float)$it["bytes"]:null;
        $lo=strtolower($key);
        $isun=($lo===""||$lo==="unknown"||$lo==="uncategorized");
        if($isun){ $found=true;$unR+=$rq;$unB+=(float)$by; }
        $label=$isun?"{uncategorized}":htmlspecialchars($key);
        $ico=$isun?"fas fa-circle-question":"fas fa-tag";
        $pct=($totR>0)?round(($rq/$totR)*100,1):0;
        $h ="<tr>";
        $h.="<td><div class='awc-ident'><span class='awc-ico".($isun?" awc-ico-off":"")."'><i class='$ico'></i></span>".
            "<span><span class='awc-name'>".$label."</span></span></div></td>";
        $h.="<td class='awc-right awc-nowrap'>".$tpl->FormatNumber($rq)."</td>";
        $h.="<td class='awc-right awc-nowrap'>".(($by===null)?"<span class='awc-none'>-</span>":siem_bytes($by))."</td>";
        $h.="<td class='awc-right awc-nowrap'>".$pct." %</td>";
        $h.="</tr>";
        if($isun){ array_unshift($rows,$h); } else { $rows[]=$h; }
    }

    $shown=count($rows);
    $html=array();
    $html[]="<div class='awc-muted' style='margin:4px 0 8px 2px'><i class='fas fa-circle-info'></i> ".
            "{period}: ".gmdate("Y-m-d H:i",$from)." &rarr; ".gmdate("Y-m-d H:i",$to)." UTC</div>";
    if($found){
        $upct=($totR>0)?round(($unR/$totR)*100,1):0;
        $html[]=$tpl->div_info("{websites_not_categorized}||".$tpl->FormatNumber($unR)." {requests} (".$upct." %) &middot; ".siem_bytes($unB));
    }else{
        $html[]=$tpl->div_info("{websites_not_categorized}||{siem_uncategorized_none}");
    }
    if($truncated){
        $html[]="<div class='awc-muted' style='margin:0 0 8px 2px'><i class='fas fa-circle-info'></i> {results_truncated}</div>";
    }
    $html[]="<div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr><th>{category}</th>".
            "<th class='awc-right'>{requests}</th><th class='awc-right'>{volume}</th><th class='awc-right'>%</th>".
            "</tr></thead><tbody id='scmc-rows'>";
    $html[]=($shown==0)?"<tr><td colspan='4'><div class='awc-empty'>{no_data}</div></td></tr>":implode("\n",$rows);
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} $shown &middot; ".$tpl->FormatNumber($totR)." {requests} &middot; ".
            siem_bytes($totB)."</span><span class='awc-pg' id='scmc-pager'></span></div>";
    $html[]="</div>";
    $html[]=$tpl->div_explain("{websites_not_categorized}||{siem_uncategorized_no_site_list}");

    $js="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";
    echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$js
        .awc_pager_js(50,"scmc-rows","scmc-pager");
}
