<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Include INCONDITIONNEL de premier niveau (CLAUDE.md, awc-* regle 1). // Service Pack 9
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");

if(isset($_GET["query"])){query();exit;}
if(isset($_GET["query-js"])){query_js();exit;}
if(isset($_GET["query-popup"])){query_popup();exit;}
if(isset($_POST["FROM"])){query_save();exit;}
if(isset($_GET["vertical-timeline"])){echo vertical_time_line();exit;}
if(isset($_GET["top-tunnels"])){echo top_tunnels();exit;}
if(isset($_GET["tunnels-table"])){tunnels_table();exit;}
if(isset($_GET["top-members"])){echo top_members();exit;}
if(isset($_GET["members-table"])){echo members_table();exit;}
if(isset($_GET["top-vips"])){echo top_vips();exit;}
if(isset($_GET["vips-table"])){echo vips_table();exit;}
if(isset($_GET["top-remote-hosts"])){echo top_remote_hosts();exit;}
if(isset($_GET["remote-hosts-table"])){echo remote_hosts_table();exit;}
if(isset($_GET["template"])){template();exit;}



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
    if (!$q->TABLE_EXISTS('strongswan_stats')) {

        $sql = "CREATE TABLE  IF NOT EXISTS `strongswan_stats` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`zate` INTEGER,
				`spi` varchar(60),
				`conn_name` varchar(128) NOT NULL,
                `username` varchar(128) NOT NULL,
				`remote_host` TEXT,
                `local_vip` TEXT,
                `time` INTEGER ,
				`bytes_in` INTEGER,
				`bytes_out` INTEGER,
    			`packets_in` INTEGER,
				`packets_out` INTEGER
		) ";
        $q->QUERY_SQL($sql);
    }
    $results=$q->QUERY_SQL("SELECT date_trunc('day',zdate)::timestamp::date as zdate FROM strongswan_stats GROUP BY 1 ORDER BY 1 DESC; ");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}

    //$DATES[date("Y-m-d")]=$tpl->time_to_date(time());
    //$DATES[date("Y-m-d",strtotime( '-1 days' ))]=$tpl->time_to_date(strtotime( '-1 days' ));

    while ($ligne = pg_fetch_assoc($results)) {
        $time=strtotime($ligne["zdate"]);
        $DATES[$ligne["zdate"]]=$tpl->time_to_date($time);
    }
    
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

    $form[]=$tpl->field_section("{from_date}");
    $form[]=$tpl->field_array_hash($DATES,"FROM","nonull:{from_date}",$_SESSION["IPPSEC_DAY"]["FROM"]);

    $tb=explode(":",$_SESSION["IPPSEC_DAY"]["FROMH"]);
    $form[]=$tpl->field_array_hash($TIMES,"FROM_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"FROM_MIN","nonull:{minutes}",$tb[1]);

    $form[]=$tpl->field_section("{to_date}");
    $tb=explode(":",$_SESSION["IPPSEC_DAY"]["TOH"]);
    $form[]=$tpl->field_array_hash($DATES,"TO","nonull:{to_date}",$_SESSION["IPPSEC_DAY"]["TO"]);
    $form[]=$tpl->field_array_hash($TIMES,"TO_HOUR","nonull:{from_time}",$tb[0]);
    $form[]=$tpl->field_array_hash($MINS,"TO_MIN","nonull:{minutes}",$tb[1]);


    echo $tpl->form_outside("{build_query}",$form,null,"{launch}","LoadAjax('ipsec-data','$page?template=yes');","AsWebStatisticsAdministrator",true);

}

function BUILD_DEFAULT_DATA(){

    if(!isset($_SESSION["IPPSEC_DAY"]["FROM"])){
        $_SESSION["IPPSEC_DAY"]["FROM"]=date("Y-m-d");
    }
    if(!isset($_SESSION["IPPSEC_DAY"]["TO"])){
        $_SESSION["IPPSEC_DAY"]["TO"]=date("Y-m-d");
    }

    if(!isset($_SESSION["IPPSEC_DAY"]["FROMH"])){
        $_SESSION["IPPSEC_DAY"]["FROMH"]="00:00";
    }
    if(!isset($_SESSION["IPPSEC_DAY"]["TOH"])){
        $_SESSION["IPPSEC_DAY"]["TOH"]="23:59";
    }
    if(!isset($_SESSION["IPPSEC_DAY"]["LIMIT"])){
        $_SESSION["IPPSEC_DAY"]["LIMIT"]="250";
    }
    if(intval($_SESSION["IPPSEC_DAY"]["LIMIT"])==0){
        $_SESSION["IPPSEC_DAY"]["LIMIT"]=250;
    }

    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $_SESSION["IPPSEC_DAY"]["DIFF_MIN"]=round(($time2-$time1)/60);

}

function query_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    $_SESSION["IPPSEC_DAY"]["FROM"]=$_POST["FROM"];
    $_SESSION["IPPSEC_DAY"]["TO"]=$_POST["TO"];
    $_SESSION["IPPSEC_DAY"]["FROMH"]=$_POST["FROM_HOUR"].":".$_POST["FROM_MIN"];
    $_SESSION["IPPSEC_DAY"]["TOH"]=$_POST["TO_HOUR"].":".$_POST["TO_MIN"];
    $_SESSION["IPPSEC_DAY"]["USER"]=$_POST["USER"];
    $_SESSION["IPPSEC_DAY"]["IP"]=$_POST["IP"];
    $_SESSION["IPPSEC_DAY"]["MAC"]=$_POST["MAC"];
    $_SESSION["IPPSEC_DAY"]["SIZE"]=$_POST["SIZE"];
    BUILD_DEFAULT_DATA();

}

/**
 * Mise en page des statistiques: une carte par theme, le tableau de detail a
 * gauche et le graphique a droite.
 *
 * L'ancienne version imbriquait deux <table style='width:100%'> pour tenir la
 * colonne -- une mise en page en tableau, qui ne se replie pas et impose une
 * hauteur fixe de 600px a chaque graphique. Ici, flexbox: sous 1100px la carte
 * passe en une colonne, et la hauteur du graphique est fixee par SswChart()
 * (un conteneur a hauteur explicite + responsive/maintainAspectRatio:false,
 * seule combinaison qui laisse Chart.js se redimensionner sans s'effondrer).
 */
function ssw_stats_css():string{
    if(isset($GLOBALS["SSW_STATS_CSS_DONE"])){return "";}
    $GLOBALS["SSW_STATS_CSS_DONE"]=true;
    return "<style>
.sswst{margin:10px 0}
.sswst-card{background:#fff;border:1px solid #bcc9c6;border-radius:12px;padding:16px 18px;margin-bottom:14px}
.sswst-row{display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap}
.sswst-side{flex:0 0 300px;min-width:260px;max-width:100%}
.sswst-main{flex:1 1 480px;min-width:320px}
.sswst-duo{display:flex;gap:14px;flex-wrap:wrap}
.sswst-duo>div{flex:1 1 420px;min-width:300px;background:#fff;border:1px solid #bcc9c6;border-radius:12px;padding:14px 16px}
.sswst-wait{text-align:center;padding:24px 0;opacity:.65}
@media(max-width:1100px){.sswst-side{flex:1 1 100%}}
</style>";
}

function template(){
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $imgwait="<div class='sswst-wait'><img src='/img/Eclipse-0.9s-120px.gif' alt=''></div>";

    // [cle, div du graphique, div du tableau, action Loadjs]
    $blocks=array(
        array("top-tunnels","top-tunnels-table","top-tunnels"),
        array("top-vips","top-vips-table","top-vips"),
        array("top-members","top-members-table","top-members"),
        array("top-remote-hosts","top-remote-hosts-table","top-remote-hosts"),
    );

    $html=array();
    $html[]="<div class='sswst'>";
    $html[]="<div class='sswst-duo'>";
    $html[]="<div id='vertical-timeline-bytes-$t'>$imgwait</div>";
    $html[]="<div id='vertical-timeline-packets-$t'>$imgwait</div>";
    $html[]="</div>";

    foreach($blocks as $b){
        list($chart,$table,$action)=$b;
        $html[]="<div class='sswst-card'><div class='sswst-row'>";
        $html[]="<div class='sswst-side'><div id='$table-$t'>$imgwait</div></div>";
        $html[]="<div class='sswst-main'><div id='$chart-$t'>$imgwait</div></div>";
        $html[]="</div></div>";
    }
    $html[]="</div>";

    // Les appels sont serialises: quatre agregations imbriquees lancees d'un
    // coup sur la meme base saturent le pool de connexions PostgreSQL, et le
    // navigateur n'affiche alors rien pendant plusieurs secondes. Chaque
    // graphique demarre le suivant.
    $js=array();
    $js[]="Loadjs('$page?vertical-timeline=yes&id=$t');";
    foreach($blocks as $b){
        list($chart,$table,$action)=$b;
        $js[]="Loadjs('$page?$action=yes&id=$chart-$t&suffix=$t');";
    }

    // Feuille de style concatenee HORS _ENGINE_parse_body: son moteur lit
    // chaque corps de regle CSS comme un {token} (CLAUDE.md).
    echo ssw_stats_css()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();\n".@implode("\n",$js)."</script>";
}

// ────────────────────────────────────────────────────────────────────────────
// Graphiques: Chart.js 4.x (angular/js/plugins/chartJs/Chart.min.4.5.1.js),
// en remplacement de Highcharts.
//
// Ces fragments sont servis a Loadjs(), donc evalues comme du JavaScript: on ne
// peut pas y glisser une balise <script src>. Le chargeur ci-dessous s'en
// charge et rappelle le code une fois la bibliotheque prete -- il fonctionne
// que le shell l'ait deja chargee (c'est le cas, class.template-admin.inc)
// ou non, et un simple "if(typeof Chart==='undefined')return;" aurait au
// contraire saute le graphique en silence.
//
// SswChart() DETRUIT le graphique precedent du meme conteneur avant d'en creer
// un autre: sans cela, chaque rafraichissement laisse une instance Chart
// accrochee a un canvas detache -- une fuite qui survit a tous les
// rafraichissements suivants -- et deux graphiques se superposent sur le meme
// identifiant.
// ────────────────────────────────────────────────────────────────────────────
function ssw_chart_boot():string{
    if(isset($GLOBALS["SSW_CHART_BOOT_DONE"])){return "";}
    $GLOBALS["SSW_CHART_BOOT_DONE"]=true;
    return "
if(typeof window.SswChart!=='function'){
 window.SswEnsureChart=function(cb){
  if(typeof Chart!=='undefined'){cb();return;}
  var s=document.createElement('script');
  s.src='angular/js/plugins/chartJs/Chart.min.4.5.1.js';
  s.onload=function(){cb();};
  document.head.appendChild(s);
 };
 window.SswChart=function(divid,height,config,unit){
  window.SswEnsureChart(function(){
   var d=document.getElementById(divid); if(!d){return;}
   if(d.__sswChart){try{d.__sswChart.destroy();}catch(e){}}
   d.innerHTML=\"<div style='position:relative;height:\"+height+\"px;width:100%'><canvas></canvas></div>\";
   var cv=d.getElementsByTagName('canvas')[0]; if(!cv){return;}
   if(unit){
    config.options=config.options||{};
    config.options.plugins=config.options.plugins||{};
    config.options.plugins.tooltip={callbacks:{label:function(c){
      var v=c.parsed; if(v!==null && typeof v==='object'){v=v.y;}
      return ' '+c.dataset.label+': '+v+' '+unit;
    }}};
   }
   d.__sswChart=new Chart(cv.getContext('2d'),config);
  });
 };
}
";
}

// Palette stable: la meme serie garde la meme couleur d'un rafraichissement a
// l'autre, ce qu'une couleur tiree au hasard ne garantit pas.
function ssw_palette():array{
    return array("#006a62","#26a69a","#0284c7","#005447","#d97706","#dc2626",
                 "#0891b2","#65a30d","#db2777","#475569","#b45309","#1d4ed8",
                 "#059669","#005447","#e11d48");
}

/**
 * Les quatre « top 15 » ne different que par la colonne de regroupement, le
 * titre et le tableau de detail qu'ils rafraichissent. Ils etaient quatre
 * copies de 45 lignes: une divergence sur l'une (le graphique des hotes
 * distants portait le titre {vips_top}) ne se voyait nulle part.
 *
 * $column est choisi par l'appelant dans une liste FERMEE, jamais par la
 * requete: il entre dans le SQL par interpolation.
 */
function ssw_top_chart($column,$titleToken,$tableDiv,$tableAction){
    $allowed=array("conn_name","username","local_vip","remote_host");
    if(!in_array($column,$allowed,true)){return;}
    $page=CurrentPageName();
    $t=preg_replace('/[^0-9]/','',(string)($_GET["suffix"] ?? ""));
    $divid=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["id"] ?? ""));
    if($divid===""){return;}
    $tpl=new template_admin();
    $q=new postgres_sql();
    BUILD_DEFAULT_DATA();

    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    $sql="select SUM(bytes_in + bytes_out) as size,$column from (SELECT distinct on (spi_in) spi_in,"
        ." MAX(bytes_in) as bytes_in, MAX(bytes_out) as bytes_out, $column,zdate FROM strongswan_stats"
        ." where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY spi_in,$column,zdate"
        ." ORDER BY spi_in,$column) x where zdate >'$strtime' and zdate < '$strtoTime'"
        ." GROUP BY $column ORDER BY size DESC LIMIT 15";

    // UNE seule execution: la version d'origine appelait QUERY_SQL() deux fois
    // et jetait le premier resultat -- cette agregation imbriquee etait donc
    // payee deux fois a chaque affichage.
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        // Le message PostgreSQL nomme des tables et des colonnes: journal, pas
        // ecran. Et le SQL n'est plus echo'e dans la reponse -- il n'y tenait
        // que par accident (l'echo precedent n'ayant pas de saut de ligne, la
        // requete se retrouvait a l'interieur d'un commentaire JavaScript).
        if(function_exists("writelogs")){writelogs("strongswan_stats: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
        $tpl->js_error("{error}");
        return;
    }

    // Accumulateurs initialises AVANT la boucle: sans lignes, serialize() et
    // json_encode() recevaient des variables inexistantes.
    $labels=array();
    $values=array();
    $PieData2=array();
    while($ligne=pg_fetch_assoc($results)){
        $name=trim((string)$ligne[$column]);
        if($name===""){$name=$tpl->_ENGINE_parse_body("{unknown}");}
        $size=round(floatval($ligne["size"])/1024/1024);
        $labels[]=$name;
        $values[]=$size;
        $PieData2[$name]=$size;
    }
    // JSON et non serialize(): la charge utile repart dans l'URL et sera relue
    // cote tableau. json_decode() ne peut instancier aucun objet, unserialize()
    // si -- c'est la classe de faille CVE-2024-2054 sur ce produit.
    $encoded=base64_encode(json_encode($PieData2));

    $colors=array();
    $pal=ssw_palette();
    for($i=0;$i<count($labels);$i++){$colors[]=$pal[$i % count($pal)];}

    $title=$tpl->_ENGINE_parse_body($titleToken);
    $cfg=array(
        "type"=>"doughnut",
        "data"=>array("labels"=>$labels,"datasets"=>array(array(
            "label"=>"MB","data"=>$values,"backgroundColor"=>$colors,
            "borderColor"=>"#ffffff","borderWidth"=>2))),
        "options"=>array(
            "responsive"=>true,"maintainAspectRatio"=>false,"cutout"=>"55%",
            "plugins"=>array(
                "title"=>array("display"=>true,"text"=>$title,
                    "font"=>array("size"=>14,"weight"=>"600"),"color"=>"#0f172a",
                    "padding"=>array("bottom"=>14)),
                "legend"=>array("position"=>"right",
                    "labels"=>array("boxWidth"=>12,"boxHeight"=>12,"padding"=>10,
                        "font"=>array("size"=>12),"color"=>"#334155")))));

    echo ssw_chart_boot()
        ."SswChart(".json_encode($divid).",420,".json_encode($cfg).",'MB');\n"
        ."LoadAjax('$tableDiv-$t','$page?$tableAction=$encoded');";
}

function top_tunnels(){      ssw_top_chart("conn_name","{APP_STRONGSWAN_TOP_TUNNELS_BY_TRAFFIC_FLOW}","top-tunnels-table","tunnels-table"); }
function top_members(){      ssw_top_chart("username","{username_top}","top-members-table","members-table"); }
function top_vips(){         ssw_top_chart("local_vip","{vips_top}","top-vips-table","vips-table"); }
// Le titre etait {vips_top}, copie du graphique precedent: ce graphique-ci
// porte sur les hotes DISTANTS.
function top_remote_hosts(){ ssw_top_chart("remote_host","{remote_hosts_top}","top-remote-hosts-table","remote-hosts-table"); }
/**
 * Tableau de detail accompagnant un graphique « top 15 ».
 *
 * SECURITE: la charge utile transite en base64 dans l'URL et etait relue par
 * unserialize(). unserialize() sur une donnee qui passe par le navigateur est
 * une deserialisation PHP non fiable -- la classe de faille qui a deja touche
 * ce produit (CVE-2024-2054). json_decode() ne peut instancier aucun objet et
 * rend exactement le meme service ici.
 *
 * Format: {"<nom>": <taille en Mo>}. L'ancien {"<nom>": [taille, nom]}
 * repetait le nom en valeur alors qu'il etait deja la cle.
 */
function ssw_top_table($getparam,$labelToken,$linkjs){
    $tpl=new template_admin();
    $raw=(string)($_GET[$getparam] ?? "");
    $table=json_decode(base64_decode($raw),true);
    if(!is_array($table)){$table=array();}

    $rows=array();
    foreach($table as $name=>$size){
        if(is_array($size)){$size=isset($size[0])?$size[0]:0;}   // ancien format
        $name=(string)$name;
        // urlencode: un nom de tunnel ou un identifiant portant un espace ou un
        // & tronquait le parametre suivant de l'URL de detail.
        $js=str_replace("%VALUE%",urlencode($name),$linkjs);
        $rows[]="<tr>"
            ."<td><div class='awc-ident'>"
            ."<span class='awc-ico'><i class='fas fa-chart-pie'></i></span>"
            ."<span class='awc-name'>".$tpl->td_href(htmlspecialchars($name),null,$js)."</span>"
            ."</div></td>"
            ."<td class='center awc-mono'>".FormatBytes(floatval($size)*1024)."</td>"
            ."</tr>";
    }

    $html=array();
    $html[]="<div class='awc-wrap' style='margin:0;padding:0'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>$labelToken</th>";
    $html[]="<th class='center' nowrap>{APP_STRONGSWAN_IN_OUT_MB}</th>";
    $html[]="</tr></thead><tbody>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div></div></div>";

    // Feuille de style HORS _ENGINE_parse_body (ses accolades CSS seraient
    // lues comme des jetons), et ICON_SCRIPTS_COMPILE() plutot que la
    // propriete d'instance ICON_SCRIPTS.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";
}

function members_table(){      ssw_top_table("members-table","{member}","Loadjs('fw.strongswan.stats.members.php?field=username&value=%VALUE%')"); }
function vips_table(){         ssw_top_table("vips-table","{vips}","Loadjs('fw.strongswan.stats.members.php?field=local_vip&value=%VALUE%')"); }
function remote_hosts_table(){ ssw_top_table("remote-hosts-table","{remote_hosts}","Loadjs('fw.strongswan.stats.members.php?field=remote_host&value=%VALUE%')"); }
function tunnels_table(){      ssw_top_table("tunnels-table","{tunnel}","Loadjs('fw.strongswan.stats.tunnel.php?tunnel=%VALUE%')"); }
function ssw_timeline_chart($divid,$title,$labels,$series,$unit,$yTitle){
    $tpl=new template_admin();
    $pal=ssw_palette();
    $datasets=array();
    $i=0;
    foreach($series as $name=>$values){
        $c=$pal[$i % count($pal)];
        $datasets[]=array(
            "label"=>$name,"data"=>array_values($values),
            "borderColor"=>$c,"backgroundColor"=>$c,
            "borderWidth"=>2,"pointRadius"=>0,"pointHoverRadius"=>4,
            "tension"=>0.25,"fill"=>false);
        $i++;
    }
    $cfg=array(
        "type"=>"line",
        "data"=>array("labels"=>$labels,"datasets"=>$datasets),
        "options"=>array(
            "responsive"=>true,"maintainAspectRatio"=>false,
            "interaction"=>array("mode"=>"index","intersect"=>false),
            "plugins"=>array(
                "title"=>array("display"=>true,"text"=>$tpl->_ENGINE_parse_body($title),
                    "font"=>array("size"=>14,"weight"=>"600"),"color"=>"#0f172a",
                    "padding"=>array("bottom"=>12)),
                "legend"=>array("position"=>"top","align"=>"end",
                    "labels"=>array("boxWidth"=>12,"boxHeight"=>12,"usePointStyle"=>true,
                        "font"=>array("size"=>12),"color"=>"#334155"))),
            "scales"=>array(
                // Pas de type sur X: Chart.js retombe sur l'axe categorie.
                "x"=>array("grid"=>array("display"=>false),
                    "ticks"=>array("maxRotation"=>0,"autoSkip"=>true,"maxTicksLimit"=>12,
                        "font"=>array("size"=>11),"color"=>"#64748b")),
                "y"=>array("beginAtZero"=>true,
                    // $yTitle peut etre un jeton ({packets}): il faut le resoudre AVANT
                    // json_encode, sinon les accolades partent telles quelles dans
                    // le graphique -- _ENGINE_parse_body ne passe jamais sur ce JSON.
                    "title"=>array("display"=>true,"text"=>$tpl->_ENGINE_parse_body($yTitle),"color"=>"#64748b"),
                    "grid"=>array("color"=>"rgba(188,201,198,.35)"),
                    "ticks"=>array("font"=>array("size"=>11),"color"=>"#64748b")))));
    return ssw_chart_boot()."SswChart(".json_encode($divid).",300,".json_encode($cfg).",".json_encode($unit).");\n";
}

function vertical_time_line(){
    $tpl=new template_admin();
    $q=new postgres_sql();
    BUILD_DEFAULT_DATA();
    $id=preg_replace('/[^A-Za-z0-9_-]/','',(string)($_GET["id"] ?? ""));
    if($id===""){return;}
    $DIFF_MIN=$_SESSION["IPPSEC_DAY"]["DIFF_MIN"];
    $DIFF_HOURS=$DIFF_MIN/60;
    $time1=strtotime("{$_SESSION["IPPSEC_DAY"]["FROM"]} {$_SESSION["IPPSEC_DAY"]["FROMH"]}:00");
    $time2=strtotime("{$_SESSION["IPPSEC_DAY"]["TO"]} {$_SESSION["IPPSEC_DAY"]["TOH"]}:00");
    $strtime=date("Y-m-d H:i:s",$time1);
    $strtoTime=date("Y-m-d H:i:s",$time2);

    $UNIT="{each_10minutes}";
    $SQL_DATE="zdate";
    if($DIFF_HOURS>6){ $UNIT="{hourly}"; $SQL_DATE="date_part('hour', zdate)"; }
    if($DIFF_HOURS>24){ $UNIT="{daily}"; $SQL_DATE="date_part('day', zdate)"; }

    // Le libelle de l'axe depend du meme palier que la requete: une seule
    // fonction pour les deux graphiques, sinon les deux echelles peuvent
    // diverger a la prochaine retouche.
    $dayWord=$tpl->_ENGINE_parse_body("{day}");
    $mklabel=function($raw) use($DIFF_HOURS,$dayWord){
        if($DIFF_HOURS>24){ return $dayWord." ".$raw; }
        if($DIFF_HOURS>6){ return $raw."h"; }
        $st=strtotime((string)$raw);
        return ($st!==false && $st>0)?date("H:i",$st):(string)$raw;
    };

    $stimeFrom=$tpl->time_to_date($time1);
    $stimeTo=$tpl->time_to_date($time2);
    $out="";

    // ── Trafic (MB) ─────────────────────────────────────────────────────────
    $sql="select sum(bytes_in) as bytes_in, sum(bytes_out) as bytes_out, $SQL_DATE as zdate from"
        ." (SELECT distinct on (spi_in) spi_in, MAX(bytes_in) as bytes_in, MAX(bytes_out) as bytes_out, zdate"
        ." FROM strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime'"
        ." GROUP BY spi_in, zdate ORDER BY spi_in, zdate) x"
        ." where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY $SQL_DATE ORDER BY $SQL_DATE";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        if(function_exists("writelogs")){writelogs("strongswan_stats bytes: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
        $tpl->js_error("{error}");
        return;
    }
    // Accumulateurs initialises avant la boucle (plancher PHP 8).
    $xdata=array(); $mb_in=array(); $mb_out=array();
    while($ligne=pg_fetch_assoc($results)){
        $xdata[]=$mklabel($ligne["zdate"]);
        $mb_in[]=round(floatval($ligne["bytes_in"])/1024/1024);
        $mb_out[]=round(floatval($ligne["bytes_out"])/1024/1024);
    }
    $title="{APP_STRONGSWAN_ALL_TUNNELS} - {APP_STRONGSWAN_TRAFFIC_FLOW} (MB) - $stimeFrom - $stimeTo $UNIT";
    $out.=ssw_timeline_chart("vertical-timeline-bytes-$id",$title,$xdata,
        array("In"=>$mb_in,"Out"=>$mb_out),"MB","MB");

    // ── Paquets ─────────────────────────────────────────────────────────────
    $sql="select sum(packets_out) as packets_out, sum(packets_in) as packets_in, $SQL_DATE as zdate from"
        ." (SELECT distinct on (spi_in) spi_in, MAX(packets_out) as packets_out, MAX(packets_in) as packets_in, zdate"
        ." FROM strongswan_stats where zdate >'$strtime' and zdate < '$strtoTime'"
        ." GROUP BY spi_in, zdate ORDER BY spi_in, zdate) x"
        ." where zdate >'$strtime' and zdate < '$strtoTime' GROUP BY $SQL_DATE ORDER BY $SQL_DATE";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        if(function_exists("writelogs")){writelogs("strongswan_stats packets: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
        echo $out; $tpl->js_error("{error}");
        return;
    }
    $xdata_pk=array(); $pk_in=array(); $pk_out=array();
    while($ligne=pg_fetch_assoc($results)){
        $xdata_pk[]=$mklabel($ligne["zdate"]);
        $pk_in[]=intval($ligne["packets_in"]);
        $pk_out[]=intval($ligne["packets_out"]);
    }
    $title="{APP_STRONGSWAN_ALL_TUNNELS} - {APP_STRONGSWAN_PACKETS_FLOW} - $stimeFrom - $stimeTo $UNIT";
    $out.=ssw_timeline_chart("vertical-timeline-packets-$id",$title,$xdata_pk,
        array("In"=>$pk_in,"Out"=>$pk_out),"","{packets}");

    echo $out;
}

function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $btn=$tpl->button_inline("{online_help}","s_PopUp('https://wiki.articatech.com/en/network/vpn/setup-a-vpn-ipsec','1024','800')","fa-solid fa-headset",null,null,"btn-blue");

    $html[]="<div class='row ibox-content white-bg'>";
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top: 10px'>";
    $html[]=$tpl->button_label_table("{build_query}",
        "Loadjs('$page?query-js=yes')", "fas fa-filter","AsWebStatisticsAdministrator");
    $html[]=$tpl->button_label_table("{refresh}",
        "LoadAjax('ipsec-data','$page?template=yes')", "fas fa-sync-alt","AsWebStatisticsAdministrator");
    $html[]="$btn</div>";

    $html[]="<div id='ipsec-data' ></div>";
    $html[]="</div>";
    $html[]="<script>
$.address.state('/');$.address.value('/ipsec-statistics');
LoadAjax('ipsec-data','$page?template=yes');</script>";

    if(isset($_GET["main-page"])){$tpl=new template_admin('Artica: IPSec Statistics',$tpl->_ENGINE_parse_body($html));echo $tpl->build_firewall();return;}
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



    $time1=strtotime( $_SESSION["IPPSEC_DAY"]["FROM"]." ". $_SESSION["IPPSEC_DAY"]["FROMH"]);
    $time2=strtotime($_SESSION["IPPSEC_DAY"]["TO"]." ". $_SESSION["IPPSEC_DAY"]["TOH"]);

    $seconds=$time2-$time1;

    if($seconds<0){
        echo  $tpl->div_error("{please_define_dates_in_correct_order}");
        return;
    }



    if($seconds<259150){
        $TABLE_SELECTED="statscom";
        $date1=$_SESSION["IPPSEC_DAY"]["FROM"]." ". $_SESSION["IPPSEC_DAY"]["FROMH"];
        $date2=$_SESSION["IPPSEC_DAY"]["TO"]." ". $_SESSION["IPPSEC_DAY"]["TOH"];
        $tt2=date("Y-m-d",strtotime($date2));

        $TITLES[]="{from} " .$tpl->time_to_date(strtotime($date1),true);
        if($tt2==date("Y-m-d")) {
            $TITLES[] = "{to} {today} " . $tpl->time_to_date(strtotime($date2), true);
        }else{
            $TITLES[] = "{to} " . $tpl->time_to_date(strtotime($date2), true);
        }
    }else{
        $TABLE_SELECTED="statscom_days";
        $date1=$_SESSION["IPPSEC_DAY"]["FROM"];
        $date2=$_SESSION["IPPSEC_DAY"]["TO"];
        $tt2=date("Y-m-d",strtotime($date2));
        $TITLES[]="{from} " .$tpl->time_to_date(strtotime($date1),false);
        if($tt2==date("Y-m-d")) {
            $TITLES[] = "{to} {today} " . $tpl->time_to_date(strtotime($date2), false);
        }else{
            $TITLES[] = "{to} " . $tpl->time_to_date(strtotime($date2), false);
        }
    }

    $TITLES[]="(".distanceOfTimeInWords(strtotime($date1),strtotime($date2)).")";
    // Pentest 2026-11-04 : les dates, membre, IP, MAC et limite (saisis dans le formulaire, gardes en
    // session) etaient concatenes dans la requete.
    $date1=pg_escape_string2($date1);
    $date2=pg_escape_string2($date2);
    $WHEREU="";$WHEREI="";$WHEREM="";$WHERES="";


    if($_SESSION["IPPSEC_DAY"]["USER"]<>null){
        $TITLES[]="{and} {member} {$_SESSION["IPPSEC_DAY"]["USER"]}";
        $WHEREU=" AND username='".pg_escape_string2($_SESSION["IPPSEC_DAY"]["USER"])."'";
    }
    if($_SESSION["IPPSEC_DAY"]["IP"]<>null){
        $TITLES[]="{and} {ipaddr} {$_SESSION["IPPSEC_DAY"]["IP"]}";
        $WHEREI=" AND ipaddr='".pg_escape_string2($_SESSION["IPPSEC_DAY"]["IP"])."'";
    }
    if($_SESSION["IPPSEC_DAY"]["MAC"]<>null){
        $TITLES[]="{and} {MAC} {$_SESSION["IPPSEC_DAY"]["MAC"]}";
        $WHEREM=" AND mac='".pg_escape_string2($_SESSION["IPPSEC_DAY"]["MAC"])."'";
    }
    if( intval($_SESSION["IPPSEC_DAY"]["SIZE"])>0){
        $size_q=round(intval( $_SESSION["IPPSEC_DAY"]["SIZE"])*1024);
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
    $TABLE_SELECTED.zdate >='$date1' AND  $TABLE_SELECTED.zdate <= '$date2'{$WHEREU}{$WHEREI}{$WHEREM}
    GROUP BY $TABLE_SELECTED.zdate,$TABLE_SELECTED.username,$TABLE_SELECTED.ipaddr,$TABLE_SELECTED.mac,statscom_websites.sitename, statscom_websites.category ORDER BY $TABLE_SELECTED.zdate
    LIMIT ".intval($_SESSION["IPPSEC_DAY"]["LIMIT"]);

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo  $tpl->_ENGINE_parse_body($html);
        echo  $tpl->div_error("{$seconds}s<br>".$q->mysql_error."<br>$sql");
        return;
    }

    $rows=array();
    while($ligne=pg_fetch_assoc($results)){
        $hits=$tpl->FormatNumber($ligne["hits"]);
        $size=FormatBytes($ligne["size"]/1024);
        $date=(string)$ligne["zdate"];
        $sitename=(string)$ligne["sitename"];
        $category_id=$ligne["category"];
        $category=$catz->CategoryIntToStr($category_id);
        $ipaddr=(string)$ligne["ipaddr"];
        $username=(string)$ligne["username"];
        $mac=(string)$ligne["mac"];

        $params=array();
        $params[]="username=".urlencode($username);
        $params[]="category=".urlencode($category_id);
        $params[]="sitename=".urlencode($sitename);
        $params[]="mac=".urlencode($mac);
        $params[]="ipaddr=".urlencode($ipaddr);
        $params[]="date1=".urlencode($date1);
        $params[]="date2=".urlencode($date2);
        $parm=@implode("&",$params);

        // $params (le TABLEAU) etait interpole ici au lieu de $parm (la chaine
        // assemblee juste au-dessus): l'URL recevait litteralement "Array", si
        // bien que le detail par categorie n'a jamais recu un seul de ses sept
        // parametres. Notice PHP cote serveur, lien mort cote operateur.
        $catLink=$tpl->td_href($category,"{statistics}","Loadjs('fw.statscom.category.php?data=yes&$parm')");

        $named=(trim($username)!=="");
        $h ="<tr>";
        $h.="<td><div class='awc-ident'>";
        $h.="<span class='awc-ico'><i class='fas fa-globe'></i></span>";
        $h.="<span><span class='awc-name'>".htmlspecialchars($sitename)."</span>";
        $h.="<div class='awc-meta'>".htmlspecialchars($date)."</div></span></div></td>";
        $h.="<td>$catLink</td>";
        $h.="<td class='awc-mono'>".($named?htmlspecialchars($username):"<span class='awc-none'>{unknown}</span>")."</td>";
        $h.="<td class='awc-mono'>".htmlspecialchars($mac)."</td>";
        $h.="<td class='awc-mono'>".htmlspecialchars($ipaddr)."</td>";
        $h.="<td class='center awc-mono'>$size</td>";
        $h.="<td class='center awc-mono'>$hits</td>";
        $h.="</tr>";
        $rows[]=$h;
    }

    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
    $html[]="<th>{sitename}</th>";
    $html[]="<th nowrap>{category}</th>";
    $html[]="<th nowrap>{member}</th>";
    $html[]="<th nowrap>{MAC}</th>";
    $html[]="<th nowrap>{ipaddr}</th>";
    $html[]="<th class='center' nowrap>{size}</th>";
    $html[]="<th class='center' nowrap>{hits}</th>";
    $html[]="</tr></thead><tbody id='sswz-rows'>";
    if(count($rows)==0){
        $html[]="<tr><td colspan='7'><div class='awc-empty'>{no_data}</div></td></tr>";
    }else{
        $html[]=@implode("\n",$rows);
    }
    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'><span>{showing} ".count($rows)." {items}</span>"
           ."<span class='awc-pg' id='sswz-pager'></span></div>";
    $html[]="</div></div>";

    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>"
        .awc_pager_js(50,"sswz-rows","sswz-pager");
}
