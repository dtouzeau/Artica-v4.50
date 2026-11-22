<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["domain-search"])){domains_search();exit;}
if(isset($_GET["restart-js"])){restart_js();exit;}
if(isset($_GET["today"])){today();exit;}
if(isset($_GET["week"])){week_stats();exit;}
if(isset($_GET["hourly"])){hourly_stats();exit;}
if(isset($_GET["month"])){month_stats();exit;}
if(isset($_GET["yesterday"])){yesterday_stats();exit;}
if(isset($_GET["year"])){year_stats();exit;}
if(isset($_GET["top-categories"])){top_categories();exit;}
if(isset($_GET["restart-perform"])){restart_perform();exit;}
if(isset($_GET["domains-start"])){domains_start();exit;}
if(isset($_GET["search-category"])){filter_category();exit;}
page();


function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $array["{yesterday}"]="$page?yesterday=yes";
    $array["{hourly}"]="$page?hourly=yes";
    $array["{today}"]="$page?today=yes";
    $array["{this_week}"]="$page?week=yes";
    $array["{this_month}"]="$page?month=yes";
    $array["{this_year}"]="$page?year=yes";
    $array["{domains}"]="$page?domains-start=yes";
    echo "<div style='margin-top:10px'>";
    echo $tpl->tabs_default($array);
    echo "</div>";
}
// $_GET["function"] est le nom de la fonction JS de rafraichissement generee
// par search_block(): il repart dans du JS et dans des URL, il ne peut porter
// qu'un identifiant JavaScript.
function dnsst_function():string{
    $f=isset($_GET["function"])?(string)$_GET["function"]:"";
    return preg_replace('/[^A-Za-z0-9_]/','',$f);
}
// Identifiant d'element du DOM, reinjecte dans getElementById('...').
function dnsst_domid($key):string{
    $v=isset($_GET[$key])?(string)$_GET[$key]:"";
    return preg_replace('/[^A-Za-z0-9_-]/','',$v);
}
// La categorie est un ENTIER de dnsfw_stats.categoryid. Elle etait recopiee
// telle quelle depuis l'URL vers la session, puis interpolee SANS guillemets
// dans "AND categoryid=..." -- aucun nettoyage de chaine ne protege une
// interpolation nue.
function dnsst_category():string{
    if(!isset($_SESSION["NGINXSEARCH"]["CATEGORY"])){return "";}
    $c=$_SESSION["NGINXSEARCH"]["CATEGORY"];
    if(is_array($c)){return "";}
    if(trim((string)$c)===""){return "";}
    return (string)intval($c);
}
function filter_category(){
    $cat=isset($_GET["search-category"])?trim((string)$_GET["search-category"]):"";
    $_SESSION["NGINXSEARCH"]["CATEGORY"]=($cat==="")?"":(string)intval($cat);
    $function=dnsst_function();
    header("content-type: application/x-javascript");
    // "$function();" avec un $function vide emet un "();" litteral: SyntaxError
    // qui avorte tout le bloc evalue par Loadjs().
    if($function!==""){echo "$function();";}
}

function domains_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    $q=new postgres_sql();
    $sql="SELECT SUM(hits) as hits,categoryid FROM dnsfw_stats GROUP BY categoryid ORDER BY hits DESC";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);return false;}
    $options["DROPDOWN"]["CONTENT"][$tpl->_ENGINE_parse_body("{all}")]="Loadjs('$page?search-category=&function=%s')";
    $catz=new mysql_catz();
    while ($ligne = pg_fetch_assoc($results)) {
        $cid=$ligne["categoryid"];
        $category=$catz->CategoryIntToStr($cid);
        $options["DROPDOWN"]["CONTENT"][$category]="Loadjs('$page?search-category=$cid&function=%s')";
    }

    $options["DROPDOWN"]["TITLE"]=$tpl->_ENGINE_parse_body("{categories}");
    echo $tpl->search_block($page,null,null,null,"&domain-search=yes",$options);
    echo "</div>";
    return true;
}

// Une ligne = une fonction. Le domaine vient de dnsfw_stats: il a ete choisi
// par le client qui a emis la requete DNS, il est echappe.
// Pentest 2026-11-04 : domaine (donnee externe) : htmlspecialchars UTF-8 rendait "" sur du Latin-1, remplace par req_html (XSS stockee).
function dnsst_dom_row($tpl,$domain,$category,$hits):string{
    return "<tr>"
        ."<td nowrap><span class='awc-ident'>"
            ."<span class='awc-ico'><i class='".ico_earth."'></i></span>"
            ."<span class='awc-name'>".req_html((string)$domain)."</span></span></td>"
        ."<td style='width:1%' nowrap><span class='awc-tag'>".req_html((string)$category)."</span></td>"
        ."<td class='center' style='width:1%' nowrap><span class='awc-seen'>".$tpl->FormatNumber($hits)."</span></td>"
        ."</tr>";
}

function domains_search():bool{
    $tpl=new template_admin();
    $q=new postgres_sql();

    // La categorie est normalisee en entier (dnsst_category), le motif de
    // recherche est nettoye: il etait interpole dans LIKE '...' avec pour seule
    // transformation un str_replace de "*" en "%", donc une apostrophe suffisait
    // a sortir du litteral. postgres_sql::QUERY_SQL n'expose pas de variante a
    // parametres lies: on s'appuie sur CLEAN_BAD_XSS_SQL(), qui retire
    // apostrophes, guillemets, antislashs, parentheses et points-virgules.
    $cat=dnsst_category();
    $catz=""; $catz2="";
    if($cat!==""){
        $catz=" AND categoryid=$cat";
        $catz2=" WHERE categoryid=$cat";
    }

    $raw=isset($_GET["search"])?trim((string)$_GET["search"]):"";
    if(strlen($raw)>2){
        $search=str_replace("*","%",$tpl->CLEAN_BAD_XSS_SQL($raw));
        $sql="SELECT SUM(hits) as hits,domain,categoryid FROM dnsfw_stats
        WHERE domain LIKE '$search'$catz
        GROUP by (domain,categoryid) ORDER BY hits DESC LIMIT 150";
    }else{
        $sql="SELECT SUM(hits) as hits,domain,categoryid FROM dnsfw_stats $catz2 GROUP by (domain,categoryid) ORDER BY hits DESC LIMIT 150";
    }

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);return false;}

    $catnames=new mysql_catz();
    $rows=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $rows[]=dnsst_dom_row($tpl,
            $ligne["domain"] ?? "",
            $catnames->CategoryIntToStr($ligne["categoryid"] ?? 0),
            $ligne["hits"] ?? 0);
    }
    $c=count($rows);

    $html=array();
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
    $html[]="<thead><tr>";
    $html[]="<th>{domains}</th>";
    // La quatrieme colonne, un <th>&nbsp;</th> pour un <td></td> vide a chaque
    // ligne, ne pouvait rien porter: elle disparait.
    $html[]="<th nowrap>{category}</th>";
    $html[]="<th class='center' nowrap>{hits}</th>";
    $html[]="</tr></thead><tbody id='dnsst-dom-rows'>";
    if($c==0){
        $html[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    // Le <tfoot> ne portait qu'un <ul class='pagination'> jamais alimente:
    // l'init footable ciblait $('#table-<time>'), un identifiant qu'AUCUN
    // element de la page ne portait -- ni pagination, ni tri.
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c {items}</span>";
    $html[]="<span class='awc-pg' id='dnsst-dom-pager'></span>";
    $html[]="</div></div></div>";

    // La requete remonte au plus 150 lignes: le dire plutot que de laisser
    // croire que c'est la totalite.
    if($c>=150){
        $html[]="<div class='awc-meta' style='padding:0 16px 10px 16px'>{search_truncated_explain}</div>";
    }

    $script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        .$script
        .awc_pager_js(50,"dnsst-dom-rows","dnsst-dom-pager");
    return true;
}

function restart_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $id=dnsst_domid("id");
    $html=base64_encode($tpl->_ENGINE_parse_body("<div style='margin:50px;' class='center'><H1>{restarting} {please_wait}....</H1></div>"));

    $f[]="document.getElementById('$id').innerHTML=base64_decode('$html');";
    $f[]="Loadjs('$page?restart-perform=yes&id=$id')";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
}
function restart_perform():bool{
    $tpl=new template_admin();
    admin_tracks("Restarting the DNS Firewall service");
    $sock=new sockets();
    $data=$sock->REST_API("/dnsfw/service/restart");
    $json=json_decode($data);
    $id=dnsst_domid("id");
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->js_error(json_last_error_msg());
    }
    header("content-type: application/x-javascript");
    if(!$json->Status){
        // Pentest 2026-11-04 : message d'erreur de l'API (donnee externe) affiche sans encodage (XSS stockee).
        $html=base64_encode($tpl->_ENGINE_parse_body("<div style='margin:50px;' class='center'><H1 style='color:red'>".req_html($json->Error)."</H1></div>"));
        echo "document.getElementById('$id').innerHTML=base64_decode('$html');";
        return false;
    }

    echo "document.getElementById('$id').innerHTML='';";
    return true;

}
// Les six vues de periode (heure, hier, jour, semaine, mois, annee) etaient
// SIX fonctions rigoureusement identiques a un seul mot pres. Une seule les
// rend desormais; les six noms restent, les repartiteurs en tete de fichier les
// appellent par leur nom.
function dnsdist_period_page($qq):bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $t=time();

    $html=array();
    $html[]="<div id='dnsdist-restart-service' style='margin-top:10px'></div>";
    $html[]="<table style='width:100%'>";
    // La premiere <tr> n'etait JAMAIS fermee (six fois, une par copie): seule
    // la reparation du parseur HTML du navigateur la rendait lisible. Et un
    // attribut colspan sur une <div> ne veut rien dire.
    $html[]="<tr>";
    $html[]="<td colspan='2' style='vertical-align: top'>";
    $html[]="<div class='center'><img src='img/squid/dnsfw-$qq.flat.png?t=$t' style='max-width:100%'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-categories'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-rules'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-ipaddr'></div></td>";
    $html[]="<td style='vertical-align: top;width:50%'><div id='top-domain'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";

    // Le <script> reste en dehors de l'analyse: la regex #{(.+?)}# de
    // _ENGINE_parse_body() y lirait chaque bloc d'accolades comme un {token}.
    $js=array();
    $js[]="<script>";
    $js[]=dnsdist_status_tiny();
    $js[]="Loadjs('$page?top-categories=$qq&field=categoryid&id=top-categories');";
    $js[]="Loadjs('$page?top-categories=$qq&field=ruleid&id=top-rules');";
    $js[]="Loadjs('$page?top-categories=$qq&field=ipaddr&id=top-ipaddr');";
    $js[]="Loadjs('$page?top-categories=$qq&field=domain&id=top-domain');";
    $js[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html)).@implode("\n",$js);
    return true;
}
function year_stats():bool{      return dnsdist_period_page("year"); }
function hourly_stats():bool{    return dnsdist_period_page("hourly"); }
function yesterday_stats():bool{ return dnsdist_period_page("yesterday"); }
function week_stats():bool{      return dnsdist_period_page("week"); }
function month_stats():bool{     return dnsdist_period_page("month"); }
function today():bool{           return dnsdist_period_page("day"); }
function top_categories():bool{
    // $_GET["suffix"] etait affecte et jamais relu dans cette fonction.
    $tpl=new template_admin();
    $q=new postgres_sql();

    $qSqlite=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $title="{top_categories_by_hits}";
    $QueryTime=isset($_GET["top-categories"])?(string)$_GET["top-categories"]:"";
    // $field est un NOM DE COLONNE, interpole dans le SELECT, le GROUP BY et
    // relu comme cle de ligne: un nom de colonne ne peut jamais etre lie en
    // parametre, il se valide contre une liste blanche ou rien ne le protege.
    $FIELDS=array("categoryid"=>true,"ruleid"=>true,"ipaddr"=>true,"domain"=>true);
    $field=isset($_GET["field"])?(string)$_GET["field"]:"categoryid";
    if(!isset($FIELDS[$field])){$field="categoryid";}

    if($QueryTime=="hourly"){
        $strtime=date("Y-m-d H:00:00");
        $strtoTime=date("Y-m-d H:59:59");

    }

    if($QueryTime=="day"){
        $strtime=date("Y-m-d 00:00:00");
        $strtoTime=date("Y-m-d 23:59:59");

    }
    if($QueryTime=="week"){
        $now = new DateTime();
        $firstDayOfWeek = $now->modify('Monday this week');
        $strtime=$firstDayOfWeek->format('Y-m-d 00:00:00');
        $lastDayOfWeek = $now->modify('Sunday this week')->setTime(23, 59, 59);
        $strtoTime= $lastDayOfWeek->format('Y-m-d H:i:s');
    }
    if($QueryTime=="month"){
        $now = new DateTime();
        $firstDayOfWeek = $now->modify('first day of this month');
        $strtime=$firstDayOfWeek->format('Y-m-d 00:00:00');
        $lastDayOfWeek = $now->modify('last day of this month')->setTime(23, 59, 59);
        $strtoTime= $lastDayOfWeek->format('Y-m-d H:i:s');
    }
    if($QueryTime=="year"){
        $now = new DateTime();
        $firstDayOfWeek = $now->modify('first day of this year');
        $strtime=$firstDayOfWeek->format('Y-m-d 00:00:00');
        $lastDayOfWeek = $now->modify('last day of this year')->setTime(23, 59, 59);
        $strtoTime= $lastDayOfWeek->format('Y-m-d H:i:s');
    }
    if($QueryTime=="yesterday"){
        $now = new DateTime();
        $firstDayOfWeek = $now->modify('yesterday');
        $strtime=$firstDayOfWeek->format('Y-m-d 00:00:00');
        $strtoTime= $firstDayOfWeek->format('Y-m-d 23:59:59');
    }
    if($field=="ruleid") {
        $title="{top} {rules}";
    }
    if($field=="ipaddr") {
        $title="{top_ipaddr}";
    }
    if($field=="domain") {
        $title="{top_domains}";
    }
    $PieData=array();
    // Un intervalle inconnu laissait $strtime/$strtoTime NON DEFINIES: la
    // requete partait avec deux bornes vides.
    if(!isset($strtime) || !isset($strtoTime)){
        $strtime=date("Y-m-d 00:00:00");
        $strtoTime=date("Y-m-d 23:59:59");
    }
    $sql="SELECT SUM(hits) as hits,$field FROM dnsfw_stats WHERE zdate >'$strtime' and zdate < '$strtoTime' GROUP by $field ORDER BY hits DESC LIMIT 15";

    if(!$q->TABLE_EXISTS("dnsfw_stats")){
        return true;
    }

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return false;}
    $catz=new mysql_catz();
    while ($ligne = pg_fetch_assoc($results)) {
        $hits = $ligne["hits"];
        $text=$ligne[$field];
        if($field=="categoryid") {
            $text = $catz->CategoryIntToStr($ligne["categoryid"]);
        }
        if($field=="ruleid") {
            // Pentest 2026-11-04 : ruleid (lu en base PostgreSQL) etait concatene dans la requete.
            $ligne2=$qSqlite->mysqli_fetch_array("SELECT rulename FROM dnsdist_rules WHERE ID=".intval($ligne["ruleid"]));
            $text=$ligne2["rulename"];
        }



        // Pentest 2026-11-04 : domaine/IP du client DNS (donnee externe) injecte dans le JS du graphique highcharts sans encodage (XSS stockee).
        // class.highcharts.inc ne neutralise que l'apostrophe : retirer ici les caracteres qui cassent une chaine JS ou ouvrent une balise.
        $text=preg_replace('#[\\\\<>"\'`\x00-\x1f]#','',(string)$text);
        if(strlen($text)<3){continue;}
        $PieData[$text]=$hits;
    }

    if(count($PieData)==0){
        return true;
    }
   // $encoded=base64_encode(serialize($PieData2));

    $highcharts=new highcharts();
    $highcharts->container=dnsst_domid("id");
    $highcharts->PieDatas=$PieData;
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle = $title;
    $highcharts->Title=$highcharts->PiePlotTitle;
    echo $highcharts->BuildChart();
    return true;
    //echo "LoadAjax('top-categories','$page?categories-table=$encoded');";


}
function dnsdist_status_tiny():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    if($users->AsDnsAdministrator) {
        $topbuttons[] = array("Loadjs('$page?addhost-js=yes&function=".dnsst_function()."');", "fad fa-desktop", "{new_host_resolve}");
    }
    $topbuttons[] = array("LoadAjaxSilent('MainContent','fw.dns.dnsdist.rules.php');", "fab fa-gripfire", "{firewall_rules}");

    if($users->AsDnsAdministrator) {
        $topbuttons[] = array("Loadjs('$page?restart-js=yes&function=".dnsst_function()."&id=dnsdist-restart-service');", ico_refresh, "{restart_service}");
    }

    $APP_DNSDIST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DNSDIST_VERSION");
    $error=CountOfInterfaces();


    $TINY_ARRAY["TITLE"]="{APP_DNSDIST} v$APP_DNSDIST_VERSION {statistics}";
    $TINY_ARRAY["ICO"]="fab fa-gripfire";
    $TINY_ARRAY["EXPL"]="$error{APP_DNSDIST_EXPLAIN2}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

}

function CountOfInterfaces():string{
    $PowerDNSListenAddr=explode("\n",trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSListenAddr")));
    $Count=0;
    foreach ($PowerDNSListenAddr as $interface){
        $interface=trim($interface);
        if (strlen($interface)<3){
            continue;
        }
        $Count++;
    }
    if($Count>0){return "";}
    return "<p class='text-danger'><strong>{no_listen_interfaces_defined}</strong></p>";
}