<?php
// Service Pack 9
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.ip2host.inc");
// INCONDITIONNEL: awc_table_style() vient de la. Place dans le bootstrap
// conditionnel ci-dessus, l'include ne s'executerait jamais (class.sockets.inc
// est deja chargee par class.template-admin.inc) et la page mourrait sur
// "Call to undefined function awc_table_style()" -- une roue qui tourne sans fin.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

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
page();

function query_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{build_query}","$page?query-popup=yes",650);

}

function query_popup(){

    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    if(!$q->CREATE_STATSCOM()) {
        echo $tpl->div_error($q->mysql_error);
    }
    $results=$q->QUERY_SQL("SELECT zdate FROM statscom_days GROUP BY zdate ORDER BY zdate DESC");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    $DATES=array();
    $DATES[date("Y-m-d")]=$tpl->time_to_date(time());
    $DATES[date("Y-m-d",strtotime( '-1 days' ))]=$tpl->time_to_date(strtotime( '-1 days' ));

    while ($ligne = pg_fetch_assoc($results)) {
        $time=strtotime($ligne["zdate"]);
        $DATES[$ligne["zdate"]]=$tpl->time_to_date($time);
    }

    $results=$q->QUERY_SQL("SELECT username,ipaddr,mac FROM statscom_users GROUP BY username,ipaddr,mac ORDER BY username,ipaddr,mac");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    $ARRAY_MAC=array();$ARRAY_USER=array();$ARRAY_IP=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
        $mac=$ligne["mac"];

        if($mac<>"00:00:00:00:00:00"){
            $ARRAY_MAC[$mac]=$mac;
        }
        if($username<>null){
            $ARRAY_USER[$username]=$username;
        }
        if($ipaddr<>null){
            $ARRAY_IP[$ipaddr]=$ipaddr;
        }
    }



    $TIMES=array();$MINS=array();
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
    $form=array();
    $form[]=$tpl->field_section("{from_date}");
    $form[]=$tpl->field_array_hash($DATES,"FROM","nonull:{from_date}",$_SESSION["STATSCOM_DAY"]["FROM"]);

    $tb=explode(":",$_SESSION["STATSCOM_DAY"]["FROMH"]);
    $form[]=$tpl->field_array_hash($TIMES,"FROM_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"FROM_MIN","nonull:{minutes}",$tb[1]);

    $form[]=$tpl->field_section("{to_date}");
    $tb=explode(":",$_SESSION["STATSCOM_DAY"]["TOH"]);
    $form[]=$tpl->field_array_hash($DATES,"TO","nonull:{to_date}",$_SESSION["STATSCOM_DAY"]["TO"]);
    $form[]=$tpl->field_array_hash($TIMES,"TO_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"TO_MIN","nonull:{minutes}",$tb[1]);


    echo $tpl->form_outside("{build_query}",$form,null,"{launch}","LoadAjax('stats-com-data','$page?template=yes');","AsWebStatisticsAdministrator",true);

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

    // Le formulaire ne porte que les dates: USER / IP / MAC / SIZE ne sont
    // postes par aucun champ. Les lire sans garde emet un warning PHP 8 a
    // chaque enregistrement et ecrase les valeurs deja en session par null.
    $_SESSION["STATSCOM_DAY"]["FROM"]=$_POST["FROM"];
    $_SESSION["STATSCOM_DAY"]["TO"]=$_POST["TO"];
    $_SESSION["STATSCOM_DAY"]["FROMH"]=$_POST["FROM_HOUR"].":".$_POST["FROM_MIN"];
    $_SESSION["STATSCOM_DAY"]["TOH"]=$_POST["TO_HOUR"].":".$_POST["TO_MIN"];
    if(isset($_POST["USER"])){$_SESSION["STATSCOM_DAY"]["USER"]=$_POST["USER"];}
    if(isset($_POST["IP"])){$_SESSION["STATSCOM_DAY"]["IP"]=$_POST["IP"];}
    if(isset($_POST["MAC"])){$_SESSION["STATSCOM_DAY"]["MAC"]=$_POST["MAC"];}
    if(isset($_POST["SIZE"])){$_SESSION["STATSCOM_DAY"]["SIZE"]=$_POST["SIZE"];}
    BUILD_DEFAULT_DATA();

}

/**
 * Palette ordonnee des graphiques.
 *
 * Quinze parts ne tiennent pas dans une legende Chart.js: la legende de chaque
 * camembert est le TABLEAU pose a cote. Le graphique et le tableau parcourent
 * les memes donnees dans le meme ordre, si bien que l'INDEX fait la
 * correspondance entre une part et sa ligne -- d'ou une palette fixe et
 * ordonnee, jamais une couleur tiree au hasard.
 */
function stb_palette():array{
    return array("#006a62","#26a69a","#1c84c6","#865300","#ba1a1a","#7e57c2",
                 "#00838f","#ef6c00","#2e7d32","#ad1457","#4527a0","#00695c",
                 "#c62828","#5d4037","#37474f");
}

function stb_color($i):string{
    $p=stb_palette();
    return $p[intval($i) % count($p)];
}

/**
 * Feuille de style de la page.
 *
 * Emise PAR l'appelant, concatenee AUTOUR de _ENGINE_parse_body() et jamais
 * dedans: la regex #{(.+?)}# du moteur lit chaque corps de regle CSS comme un
 * {jeton} et declenche une recherche memcached par regle.
 */
function stb_css():string{
    if(isset($GLOBALS["STB_CSS_DONE"])){return "";}
    $GLOBALS["STB_CSS_DONE"]=true;
    return "<style>
.stb-card{background:#ffffff;border:1px solid #bcc9c6;border-radius:12px;padding:16px;margin:12px 0}
.stb-head{display:flex;align-items:flex-start;gap:12px;margin-bottom:14px}
.stb-ico{width:34px;height:34px;border-radius:8px;background:rgba(0,106,98,.1);color:#006a62;
 display:inline-flex;align-items:center;justify-content:center;font-size:15px;flex:0 0 auto}
.stb-title{font-size:14px;font-weight:700;line-height:20px;color:#111d23}
.stb-sub{font-size:12px;line-height:16px;color:#6d7a77;margin-top:2px}
.stb-grid{display:grid;grid-template-columns:minmax(260px,360px) minmax(280px,620px);gap:24px;
 justify-content:center;align-items:start}
@media(max-width:1100px){.stb-grid{grid-template-columns:minmax(0,1fr)}}
.stb-cv{position:relative;height:340px;width:100%}
.stb-cv-wide{position:relative;height:300px;width:100%}
.stb-wait{display:flex;align-items:center;justify-content:center;height:100%;min-height:120px;
 color:#6d7a77;font-size:13px}
.stb-sw{display:inline-block;width:4px;height:14px;border-radius:2px;vertical-align:middle;margin-right:8px}
.stb-lnk{color:#111d23;text-decoration:none}
.stb-lnk:hover{color:#006a62;text-decoration:underline}
.stb-meta{font-size:11px;line-height:15px;color:#6d7a77;margin-top:2px}
.stb-meta a{color:#6d7a77;text-decoration:none}
.stb-meta a:hover{color:#006a62;text-decoration:underline}
</style>";
}

/**
 * Identifiant de div recu en parametre, ramene a [A-Za-z0-9_-].
 *
 * Il est interpole dans du JavaScript executable (getElementById, cle de
 * registre): une valeur brute venue de $_GET y injecterait du code dans la
 * session de l'administrateur connecte.
 */
function stb_divid($key):string{
    $v=isset($_GET[$key])?$_GET[$key]:"";
    return preg_replace('/[^A-Za-z0-9_-]/','',(string)$v);
}

/**
 * Periode courante, lue une seule fois.
 *
 * Les quatre fonctions de donnees recopiaient ces six lignes; deux copies
 * divergentes auraient donne deux graphiques sur deux periodes differentes
 * dans la meme page, sans rien d'anormal a l'ecran.
 */
function stb_period():array{
    BUILD_DEFAULT_DATA();
    $t1=strtotime("{$_SESSION["STATSCOM_DAY"]["FROM"]} {$_SESSION["STATSCOM_DAY"]["FROMH"]}:00");
    $t2=strtotime("{$_SESSION["STATSCOM_DAY"]["TO"]} {$_SESSION["STATSCOM_DAY"]["TOH"]}:00");
    return array(
        "t1"=>$t1,
        "t2"=>$t2,
        "from"=>date("Y-m-d H:i:s",$t1),
        "to"=>date("Y-m-d H:i:s",$t2),
        "diffmin"=>intval($_SESSION["STATSCOM_DAY"]["DIFF_MIN"]));
}

/** Pas de la frise: le libelle affiche et l'expression SQL de regroupement. */
function stb_timeline_unit($diffmin):array{
    $hours=intval($diffmin)/60;
    if($hours>48){ return array("LABEL"=>"{daily}", "SQL"=>"date_part('day', zdate)", "MODE"=>"day"); }
    if($hours>6){  return array("LABEL"=>"{hourly}","SQL"=>"date_part('hour', zdate)","MODE"=>"hour"); }
    return array("LABEL"=>"{each_10minutes}","SQL"=>"zdate","MODE"=>"raw");
}

/**
 * Chargeur Chart.js d'une reponse Loadjs.
 *
 * Le shell charge deja Chart.js, mais un fragment n'a aucune garantie a ce
 * sujet et le fichier est VERSIONNE (Chart.min.js n'existe pas). Les quatre
 * graphiques partent en parallele: sans file d'attente, quatre <script>
 * identiques seraient injectes en meme temps.
 */
function stb_chart_loader_js():string{
    return "
if(typeof window.StbChart!=='function'){
 window.StbChart=function(cb){
  if(typeof Chart!=='undefined'){cb();return;}
  var q=window.__StbChartQ=window.__StbChartQ||[];
  q.push(cb);
  if(window.__StbChartLoading){return;}
  window.__StbChartLoading=true;
  var s=document.createElement('script');
  s.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js';
  s.onload=function(){window.__StbChartLoading=false;var i;for(i=0;i<q.length;i++){try{q[i]();}catch(e){}}q.length=0;};
  document.head.appendChild(s);
 };
}
";
}

/**
 * Injecte un <canvas> dans $divid et y construit le graphique.
 *
 * Le conteneur porte .stb-cv (position:relative + hauteur fixe) et le
 * graphique est responsive avec maintainAspectRatio:false: c'est la seule
 * combinaison qui survive a une creation dans un conteneur encore invisible,
 * ou le canvas mesurerait une largeur nulle.
 *
 * L'instance precedente est detruite: un rafraichissement recree le canvas et
 * laisserait sinon un objet Chart vivant accroche a un noeud detache.
 */
function stb_chart_render_js($divid,$config):string{
    $js=array();
    $js[]=stb_chart_loader_js();
    $js[]="(function(){";
    $js[]="var d=document.getElementById('$divid'); if(!d){return;}";
    $js[]="d.innerHTML=\"<canvas id='$divid-cv'></canvas>\";";
    $js[]="StbChart(function(){";
    $js[]="var c=document.getElementById('$divid-cv'); if(!c){return;}";
    $js[]="window.StbCharts=window.StbCharts||{};";
    $js[]="if(window.StbCharts['$divid']){try{window.StbCharts['$divid'].destroy();}catch(e){}}";
    $js[]="window.StbCharts['$divid']=new Chart(c,$config);";
    $js[]="});";
    $js[]="})();";
    return @implode("\n",$js);
}

/** Message a la place du graphique quand la periode ne rend aucune ligne. */
function stb_chart_empty_js($divid,$text):string{
    $html=json_encode("<div class='stb-wait'>".$text."</div>");
    return "(function(){var d=document.getElementById('$divid'); if(!d){return;} d.innerHTML=$html;})();";
}

/** Tableau JS de valeurs numeriques, formatees en PHP. */
function stb_numbers_js(array $values):string{
    $out=array();
    foreach($values as $v){ $out[]=sprintf("%.2f",floatval($v)); }
    return "[".@implode(",",$out)."]";
}

/** Chaine JS sure: les entites <, >, ', " et & sont echappees en \\uXXXX. */
function stb_js(string $text):string{
    return json_encode($text, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
}

/** Tableau JS de chaines. */
function stb_strings_js(array $values):string{
    $out=array();
    foreach($values as $v){ $out[]=stb_js((string)$v); }
    return "[".@implode(",",$out)."]";
}

/** Configuration d'un anneau: une part par entree, couleur = index de la palette. */
function stb_doughnut_config(array $labels,array $values,string $unit):string{
    $colors=array();
    $i=0;
    foreach($labels as $ignored){ $colors[]=stb_color($i); $i++; }
    $l=stb_strings_js(array_values($labels));
    $v=stb_numbers_js(array_values($values));
    $c=stb_strings_js($colors);
    $u=stb_js($unit);
    return "{type:'doughnut',"
        ."data:{labels:$l,datasets:[{data:$v,backgroundColor:$c,borderColor:'#ffffff',borderWidth:2}]},"
        ."options:{responsive:true,maintainAspectRatio:false,cutout:'55%',"
        ."plugins:{legend:{display:false},"
        ."tooltip:{callbacks:{label:function(c){return ' '+c.label+': '+c.formattedValue+' '+$u;}}}}}}";
}

/**
 * Configuration de la frise.
 *
 * Echelle x de CATEGORIES avec des libelles deja formates en PHP: aucun
 * adaptateur de date n'est charge dans la console, si bien qu'une echelle
 * type:'time' leve une erreur et ne dessine rien.
 */
function stb_line_config(array $labels,array $values,string $unit):string{
    $l=stb_strings_js(array_values($labels));
    $v=stb_numbers_js(array_values($values));
    $u=stb_js($unit);
    return "{type:'line',"
        ."data:{labels:$l,datasets:[{data:$v,borderColor:'#006a62',"
        ."backgroundColor:'rgba(0,106,98,.12)',borderWidth:2,fill:true,tension:.3,"
        ."pointRadius:2,pointHoverRadius:4,pointBackgroundColor:'#006a62'}]},"
        ."options:{responsive:true,maintainAspectRatio:false,"
        ."interaction:{mode:'index',intersect:false},"
        ."plugins:{legend:{display:false},"
        ."tooltip:{callbacks:{label:function(c){return ' '+c.formattedValue+' '+$u;}}}},"
        ."scales:{y:{beginAtZero:true,title:{display:true,text:$u},"
        ."grid:{color:'rgba(188,201,198,.35)'}},"
        ."x:{grid:{display:false},ticks:{autoSkip:true,maxRotation:0,maxTicksLimit:16}}}}}";
}

/**
 * Tableau-legende d'un camembert (design awc-*).
 *
 * $rows: une entree par part, dans l'ORDRE du graphique --
 * array("HTML"=>cellule de gauche deja construite, "VALUE"=>cellule de droite).
 * La pastille de couleur est posee ici, a partir du meme index que la part.
 */
function stb_legend_table(string $headLabel,string $headValue,array $rows,string $footLeft,string $footRight):string{
    $h=array();
    $h[]="<div class='awc-card'><div class='awc-scroll'>";
    $h[]="<table class='awc-tbl awc-tbl-compact'>";
    $h[]="<thead><tr><th>$headLabel</th><th style='text-align:right'>$headValue</th></tr></thead>";
    $h[]="<tbody>";
    if(count($rows)==0){
        $h[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $i=0;
        foreach($rows as $row){
            $color=stb_color($i);
            $h[]="<tr>";
            $h[]="<td><span class='stb-sw' style='background:$color'></span>".$row["HTML"]."</td>";
            $h[]="<td class='awc-right awc-nowrap'>".$row["VALUE"]."</td>";
            $h[]="</tr>";
            $i++;
        }
    }
    $h[]="</tbody></table></div>";
    $h[]="<div class='awc-foot'><span>$footLeft</span><span><strong>$footRight</strong></span></div>";
    $h[]="</div>";
    return @implode("\n",$h);
}

/** Entete d'une carte de la page. */
function stb_card_head(string $icon,string $title,string $subtitle):string{
    $h=array();
    $h[]="<div class='stb-head'>";
    $h[]="<span class='stb-ico'><i class='$icon'></i></span>";
    $h[]="<span><span class='stb-title'>$title</span><div class='stb-sub'>$subtitle</div></span>";
    $h[]="</div>";
    return @implode("\n",$h);
}

/** Carte pleine largeur de la frise. */
function stb_card_timeline(string $divid,string $subtitle):string{
    $h=array();
    $h[]="<div class='stb-card'>";
    $h[]=stb_card_head("fas fa-chart-area","{downloaded_flow} (MB)",$subtitle);
    $h[]="<div id='$divid' class='stb-cv-wide'><div class='stb-wait'>{loading}</div></div>";
    $h[]="</div>";
    return @implode("\n",$h);
}

/** Carte « graphique + tableau-legende » d'un TOP. */
function stb_card_top(string $icon,string $title,string $subtitle,string $chartDiv,string $tableDiv):string{
    $h=array();
    $h[]="<div class='stb-card'>";
    $h[]=stb_card_head($icon,$title,$subtitle);
    $h[]="<div class='stb-grid'>";
    $h[]="<div id='$chartDiv' class='stb-cv'><div class='stb-wait'>{loading}</div></div>";
    $h[]="<div id='$tableDiv'><div class='stb-wait'>{loading}</div></div>";
    $h[]="</div>";
    $h[]="</div>";
    return @implode("\n",$h);
}

/**
 * Charge utile d'un tableau-legende, passee dans l'URL par le repondeur du
 * graphique.
 *
 * JSON et non serialize(): la valeur transite par le navigateur, et
 * unserialize() sur une entree que l'utilisateur peut reecrire instancie des
 * objets. Encodage rawurlencode a l'aller: un '+' de base64 vaut une ESPACE
 * dans une chaine de requete, ce qui cassait silencieusement le decodage et
 * rendait un tableau vide sans la moindre erreur.
 */
function stb_payload_encode(array $data):string{
    return rawurlencode(base64_encode(json_encode($data)));
}

function stb_payload_decode($key):array{
    $raw=isset($_GET[$key])?(string)$_GET[$key]:"";
    if(strlen($raw)<3){return array();}
    $decoded=base64_decode($raw,true);
    if($decoded===false){return array();}
    $data=json_decode($decoded,true);
    if(!is_array($data)){return array();}
    return $data;
}

function template(){
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $period=stb_period();
    $unit=stb_timeline_unit($period["diffmin"]);

    $subtitle=$tpl->time_to_date($period["t1"])." &rarr; ".$tpl->time_to_date($period["t2"]);

    $html=array();
    $html[]=stb_card_timeline("vertical-timeline-$t",$subtitle." &middot; ".$unit["LABEL"]);
    $html[]=stb_card_top("fas fa-globe","{top_websites_by_size}","MB","top-sites-$t","top-sites-table-$t");
    $html[]=stb_card_top("fas fa-folder-tree","{top_categories_by_size}","MB","top-categories-$t","top-sites-categories-$t");
    $html[]=stb_card_top("fas fa-users","{top_members}","MB","top-users-$t","top-users-table-$t");

    $html[]="<script>";
    $html[]="NoSpinner();";
    $html[]="Loadjs('$page?vertical-timeline=yes&id=vertical-timeline-$t');";
    $html[]="Loadjs('$page?top-sites=yes&id=top-sites-$t&suffix=$t');";
    $html[]="Loadjs('$page?top-users=yes&id=top-users-$t&suffix=$t');";
    $html[]="Loadjs('$page?top-categories=yes&id=top-categories-$t&suffix=$t');";
    $html[]="</script>";

    echo stb_css().$tpl->_ENGINE_parse_body(@implode("\n",$html));

}

function top_sites(){
    $page=CurrentPageName();
    $t=stb_divid("suffix");
    $divid=stb_divid("id");
    $tpl=new template_admin();
    $q=new postgres_sql();
    $period=stb_period();

    $sql="SELECT SUM(statscom.size) as size,statscom_websites.familysite,statscom.siteid FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND zdate >'{$period["from"]}' and zdate < '{$period["to"]}' GROUP by familysite,statscom.siteid ORDER BY size DESC LIMIT 15";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    $labels=array();$values=array();$rows=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $sizeMB=round(($ligne["size"]/1024)/1024,2);
        $labels[]=$ligne["familysite"];
        $values[]=$sizeMB;
        $rows[]=array("site"=>$ligne["familysite"],"siteid"=>intval($ligne["siteid"]),"kb"=>$ligne["size"]/1024);
    }

    if(count($labels)==0){
        echo stb_chart_empty_js($divid,$tpl->javascript_parse_text("{no_data}"));
        echo "LoadAjax('top-sites-table-$t','$page?sites-table=".stb_payload_encode(array())."');";
        return;
    }

    echo stb_chart_render_js($divid,stb_doughnut_config($labels,$values,"MB"));
    echo "\nLoadAjax('top-sites-table-$t','$page?sites-table=".stb_payload_encode($rows)."');";

}
function top_categories(){
    $page=CurrentPageName();
    $t=stb_divid("suffix");
    $divid=stb_divid("id");
    $tpl=new template_admin();
    $q=new postgres_sql();
    $period=stb_period();

    $sql="SELECT SUM(statscom.size) as size,statscom_websites.category FROM statscom,statscom_websites WHERE statscom_websites.siteid=statscom.siteid AND zdate >'{$period["from"]}' and zdate < '{$period["to"]}' GROUP by category ORDER BY size DESC LIMIT 15";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
    $catz=new mysql_catz();

    $labels=array();$values=array();$rows=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $category=$catz->CategoryIntToStr($ligne["category"]);
        $labels[]=$category;
        $values[]=round(($ligne["size"]/1024)/1024,2);
        $rows[]=array("category"=>$category,"categoryid"=>$ligne["category"],"kb"=>$ligne["size"]/1024);
    }

    if(count($labels)==0){
        echo stb_chart_empty_js($divid,$tpl->javascript_parse_text("{no_data}"));
        echo "LoadAjax('top-sites-categories-$t','$page?categories-table=".stb_payload_encode(array())."');";
        return;
    }

    echo stb_chart_render_js($divid,stb_doughnut_config($labels,$values,"MB"));
    echo "\nLoadAjax('top-sites-categories-$t','$page?categories-table=".stb_payload_encode($rows)."');";

}

function categories_table(){
    $tpl=new template_admin();
    $table=stb_payload_decode("categories-table");

    $rows=array();$totalKB=0;
    foreach ($table as $entry) {
        $categoryid=isset($entry["categoryid"])?$entry["categoryid"]:"";
        $category=isset($entry["category"])?$entry["category"]:"";
        $kb=floatval(isset($entry["kb"])?$entry["kb"]:0);
        $totalKB=$totalKB+$kb;
        $js="Loadjs('fw.statscom.browse.categories.php?category=".urlencode($categoryid)."')";
        $label="<a href=\"javascript:blur();\" class='stb-lnk' OnClick=\"$js\">".htmlspecialchars($category)."</a>";
        $rows[]=array("HTML"=>$label,"VALUE"=>FormatBytes($kb));
    }

    echo awc_table_style().$tpl->_ENGINE_parse_body(
        stb_legend_table("{category}","{size}",$rows,"{total}",FormatBytes($totalKB)));

}

function top_users(){

    $page=CurrentPageName();
    $t=stb_divid("suffix");
    $divid=stb_divid("id");
    $tpl=new template_admin();
    $q=new postgres_sql();
    $period=stb_period();

    $sql="SELECT SUM(size) as size,username,ipaddr,mac FROM statscom  WHERE zdate >'{$period["from"]}' and zdate < '{$period["to"]}' GROUP by username,ipaddr,mac ORDER BY size DESC LIMIT 15";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    $resolveIP2HOST=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolveIP2HOST"));
    $labels=array();$values=array();$rows=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $username=$ligne["username"];
        $ipaddr=$ligne["ipaddr"];
        $MAC=$ligne["mac"];
        if($resolveIP2HOST==1){
            $host= new ip2host($ipaddr);
            $ipaddr=$host->output;
        }
        // L'etiquette de la part reprend l'identifiant le plus parlant dont on
        // dispose; le tableau, lui, montre les trois.
        $label=$username;
        if($label==null){$label=$MAC;}
        if($label==null){$label=$ipaddr;}
        $labels[]=$label;
        $values[]=round(($ligne["size"]/1024)/1024,2);
        $rows[]=array("username"=>$username,"mac"=>$MAC,"ipaddr"=>$ipaddr,"kb"=>$ligne["size"]/1024);
    }

    if(count($labels)==0){
        echo stb_chart_empty_js($divid,$tpl->javascript_parse_text("{no_data}"));
        echo "LoadAjax('top-users-table-$t','$page?users-table=".stb_payload_encode(array())."');";
        return;
    }

    echo stb_chart_render_js($divid,stb_doughnut_config($labels,$values,"MB"));
    echo "\nLoadAjax('top-users-table-$t','$page?users-table=".stb_payload_encode($rows)."');";

}

function sites_table(){
    $tpl=new template_admin();
    $table=stb_payload_decode("sites-table");

    $rows=array();$totalKB=0;
    foreach ($table as $entry) {
        $site=isset($entry["site"])?$entry["site"]:"";
        $siteid=intval(isset($entry["siteid"])?$entry["siteid"]:0);
        $kb=floatval(isset($entry["kb"])?$entry["kb"]:0);
        $totalKB=$totalKB+$kb;
        $js="Loadjs('fw.statscom.browse.website.php?siteid=$siteid')";
        $label="<a href=\"javascript:blur();\" class='stb-lnk' OnClick=\"$js\">".htmlspecialchars($site)."</a>";
        $rows[]=array("HTML"=>$label,"VALUE"=>FormatBytes($kb));
    }

    echo awc_table_style().$tpl->_ENGINE_parse_body(
        stb_legend_table("{domains}","{size}",$rows,"{total}",FormatBytes($totalKB)));
}

/** Lien vers la fiche d'un membre, pour un des trois identifiants. */
function stb_member_link(string $field,string $value,string $class):string{
    $js="Loadjs('fw.statscom.browse.users.php?field=$field&value=".urlencode($value)."')";
    $cls=($class<>"")?" class='$class'":"";
    return "<a href=\"javascript:blur();\"$cls OnClick=\"$js\">".htmlspecialchars($value)."</a>";
}

function users_table(){

    $tpl=new template_admin();
    $table=stb_payload_decode("users-table");

    $rows=array();$totalKB=0;
    foreach ($table as $entry) {
        $username=(string)(isset($entry["username"])?$entry["username"]:"");
        $mac=(string)(isset($entry["mac"])?$entry["mac"]:"");
        $ipaddr=(string)(isset($entry["ipaddr"])?$entry["ipaddr"]:"");
        $kb=floatval(isset($entry["kb"])?$entry["kb"]:0);
        $totalKB=$totalKB+$kb;

        // Un identifiant principal en clair, les autres en seconde ligne: les
        // trois alignes sur une ligne debordaient la colonne des la premiere
        // adresse MAC.
        $main=null;$meta=array();
        if($username<>""){ $main=stb_member_link("username",$username,"stb-lnk"); }
        if($mac<>""){
            $lnk=stb_member_link("mac",$mac,"");
            if($main==null){$main=stb_member_link("mac",$mac,"stb-lnk");}else{$meta[]=$lnk;}
        }
        if($ipaddr<>""){
            $lnk=stb_member_link("ipaddr",$ipaddr,"");
            if($main==null){$main=stb_member_link("ipaddr",$ipaddr,"stb-lnk");}else{$meta[]=$lnk;}
        }
        if($main==null){$main="<span class='awc-none'>{unknown}</span>";}

        $cell=$main;
        if(count($meta)>0){ $cell=$cell."<div class='stb-meta'>".@implode(" &middot; ",$meta)."</div>"; }
        $rows[]=array("HTML"=>$cell,"VALUE"=>FormatBytes($kb));
    }

    echo awc_table_style().$tpl->_ENGINE_parse_body(
        stb_legend_table("{member}","{size}",$rows,"{total}",FormatBytes($totalKB)));
}

function vertical_time_line(){
    $tpl=new template_admin();
    $q=new postgres_sql();
    $divid=stb_divid("id");
    $period=stb_period();
    $unit=stb_timeline_unit($period["diffmin"]);

    $sql="SELECT SUM(size) as size,{$unit["SQL"]} as zdate FROM statscom WHERE zdate >'{$period["from"]}' and zdate < '{$period["to"]}' GROUP by {$unit["SQL"]} ORDER BY {$unit["SQL"]}";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

    $dayword=$tpl->_ENGINE_parse_body("{day}");
    $labels=array();$values=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $datetext=date("H:i",strtotime($ligne["zdate"]));
        if($unit["MODE"]=="hour"){ $datetext="{$ligne["zdate"]}h"; }
        if($unit["MODE"]=="day"){  $datetext="$dayword {$ligne["zdate"]}"; }
        $labels[]=$datetext;
        $values[]=round(($ligne["size"]/1024)/1024,2);
    }

    if(count($labels)==0){
        echo stb_chart_empty_js($divid,$tpl->javascript_parse_text("{no_data}"));
        return;
    }

    echo stb_chart_render_js($divid,stb_line_config($labels,$values,"MB"));

}



function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html=array();
    $html[]="<div class='row ibox-content white-bg'>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top: 10px'>";
    $html[]=$tpl->button_label_table("{build_query}",
            "Loadjs('$page?query-js=yes')", "fas fa-filter","AsWebStatisticsAdministrator");
    $html[]=$tpl->button_label_table("{refresh}",
        "LoadAjax('stats-com-data','$page?template=yes')", "fas fa-sync-alt","AsWebStatisticsAdministrator");
    $html[]="</div>";

    $html[]="<div id='stats-com-data' ></div>";
    $html[]="</div>";
    $html[]="<script>LoadAjax('stats-com-data','$page?template=yes');</script>";

    echo $tpl->_ENGINE_parse_body($html);
}

function query(){
    $tpl=new template_admin();
    $catz=new mysql_catz();
    $q=new postgres_sql();
    $t=time();
    $TRCLASS=null;$WHEREU=null;$WHEREM=null;$WHEREI=null;$WHERES=null;
    //86340 = 24H
    // 172740  =

    BUILD_DEFAULT_DATA();

    $USER=isset($_SESSION["STATSCOM_DAY"]["USER"])?$_SESSION["STATSCOM_DAY"]["USER"]:null;
    $IP=isset($_SESSION["STATSCOM_DAY"]["IP"])?$_SESSION["STATSCOM_DAY"]["IP"]:null;
    $MAC=isset($_SESSION["STATSCOM_DAY"]["MAC"])?$_SESSION["STATSCOM_DAY"]["MAC"]:null;
    $SIZE=isset($_SESSION["STATSCOM_DAY"]["SIZE"])?$_SESSION["STATSCOM_DAY"]["SIZE"]:0;

    $time1=strtotime( $_SESSION["STATSCOM_DAY"]["FROM"]." ". $_SESSION["STATSCOM_DAY"]["FROMH"]);
    $time2=strtotime($_SESSION["STATSCOM_DAY"]["TO"]." ". $_SESSION["STATSCOM_DAY"]["TOH"]);

    $seconds=$time2-$time1;

    if($seconds<0){
       echo  $tpl->div_error("{please_define_dates_in_correct_order}");
        return;
    }

    $TITLES=array();

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


    if($USER<>null){
        $TITLES[]="{and} {member} $USER";
        // Pentest 2026-11-04 : USER, IP, MAC et les dates (session alimentee par le POST) etaient concatenes dans la requete.
        $WHEREU=" AND username='".pg_escape_string2($USER)."'";
    }
    if($IP<>null){
        $TITLES[]="{and} {ipaddr} $IP";
        $WHEREI=" AND ipaddr='".pg_escape_string2($IP)."'";
    }
    if($MAC<>null){
        $TITLES[]="{and} {MAC} $MAC";
        $WHEREM=" AND mac='".pg_escape_string2($MAC)."'";
    }
    if( intval($SIZE)>0){
        $size_q=round(intval($SIZE)*1024);
        $WHERES=" $TABLE_SELECTED.size > $size_q AND";
    }

    $html=array();
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
    $TABLE_SELECTED.zdate >='".pg_escape_string2($date1)."' AND  $TABLE_SELECTED.zdate <= '".pg_escape_string2($date2)."'{$WHEREU}{$WHEREI}{$WHEREM}
    GROUP BY $TABLE_SELECTED.zdate,$TABLE_SELECTED.username,$TABLE_SELECTED.ipaddr,$TABLE_SELECTED.mac,statscom_websites.sitename, statscom_websites.category ORDER BY $TABLE_SELECTED.zdate
    LIMIT {$_SESSION["STATSCOM_DAY"]["LIMIT"]}";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo  $tpl->_ENGINE_parse_body(@implode("\n",$html));
        echo  $tpl->div_error("{$seconds}s<br>".$q->mysql_error."<br>$sql");
        return;
    }

    // En-tete bati par le composant partage: les huit <th> etaient recopies a
    // la main avec leurs attributs data-sortable, qui derivaient page a page.
    $fields=array();
    $fields[]="{date}";
    $fields[]="{sitename}";
    $fields[]="{category}";
    $fields[]="{member}";
    $fields[]="{MAC}";
    $fields[]="{ipaddr}";
    $fields[]="{size}";
    $fields[]="{hits}";
    $html[]=$tpl->table_head($fields,"table-$t");

    $td1=$tpl->table_td1prc_Left();
    $tdfree=$tpl->table_tdfree();

    while ($ligne = pg_fetch_assoc($results)) {
        if ($TRCLASS == "footable-odd ") {
            $TRCLASS = null;
        } else {
            $TRCLASS = "footable-odd ";
        }
        $hits=$tpl->FormatNumber($ligne["hits"]);
        $size=FormatBytes($ligne["size"]/1024);
        $date=$ligne["zdate"];
        $sitename=$ligne["sitename"];
        $category_id=$ligne["category"];
        $category=$catz->CategoryIntToStr($category_id);
        $ipaddr=$ligne["ipaddr"];
        $username=$ligne["username"];
        $mac=$ligne["mac"];

        // fw.statscom.category.php n'existe pas: le lien tombait sur un 404 que
        // Loadjs avale sans rien dire. La fiche d'une categorie, c'est
        // fw.statscom.browse.categories.php -- la meme que le tableau du TOP.
        $categoryCell=$tpl->td_href($category,"{statistics}",
            "Loadjs('fw.statscom.browse.categories.php?category=".urlencode($category_id)."')");


        $html[]="<tr style='vertical-align:middle' class='$TRCLASS'>";
        $html[]="<td $td1>".htmlspecialchars($date)."</td>";
        $html[]="<td $tdfree>".htmlspecialchars($sitename)."</td>";
        $html[]="<td $td1>$categoryCell</td>";
        $html[]="<td $td1>".htmlspecialchars($username)."</td>";
        $html[]="<td $td1>".htmlspecialchars($mac)."</td>";
        $html[]="<td $td1>".htmlspecialchars($ipaddr)."</td>";
        $html[]="<td $td1>$size</td>";
        $html[]="<td $td1>$hits</td>";
        $html[]="</tr>";


    }

    $html[]=$tpl->table_footer("table-$t",8,true);

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
