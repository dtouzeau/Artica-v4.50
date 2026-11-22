<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style(): feuille de style partagee des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(!$tpl->xPrivs()){exit();}
if(isset($_GET["courbe"])){courbe();exit;}
if(isset($_GET["camembert"])){camembert();exit;}
if(isset($_GET["bandwidth-day"])){day_bandwidth();exit;}

page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ProxyGraphsTOPNumber=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyGraphsTOPNumber"));
	if($ProxyGraphsTOPNumber<2){$ProxyGraphsTOPNumber=10;}

	$html=array();$js=array();
	$q=new postgres_sql();
	$now=date("Y-m-d 00:00:00",strtotime('monday this week'));
	$ToChar="to_char(zdate, 'DD')";

	// ---- Bande passante ----
	$sql="SELECT $ToChar as thour,SUM(SIZE) as size FROM proxy_traffic WHERE zdate>'$now' GROUP BY thour ORDER BY thour";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	// $MAIN["xdata"] n'existait pas quand la requete ne rendait aucune ligne:
	// le count() qui suit declenchait un avertissement "Undefined array key".
	$MAIN=array("xdata"=>array(),"ydata"=>array());
	while($ligne=pg_fetch_array($results)){
		$MAIN["xdata"][]=$ligne["thour"];
		$MAIN["ydata"][]=$ligne["size"]/1024/1024/1024;
	}
	if(count($MAIN["xdata"])>1){
		$title=urlencode("{bandwidth}/{this_week}");
		$rowtitle=urlencode("| {bandwidth}");
		$timetext=urlencode("{day}");
		@file_put_contents(pxstat_data_path("week.bandwidth.data"), serialize($MAIN));
		$html[]="<div id='week-bandwidth' style='width:100%;height:350px'></div>";
		$js[]="Loadjs('$page?courbe=yes&timetext=$timetext&container=week-bandwidth&title=$title&data-file=week.bandwidth.data&legend=GB&row-title=$rowtitle');";
	}

	// ---- Requetes ----
	$sql="SELECT $ToChar as thour,SUM(rqs) as size FROM proxy_traffic WHERE zdate>'$now' GROUP BY thour ORDER BY thour";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$MAIN=array("xdata"=>array(),"ydata"=>array());
	while($ligne=pg_fetch_array($results)){
		$MAIN["xdata"][]=$ligne["thour"];
		$MAIN["ydata"][]=$ligne["size"];
	}
	if(count($MAIN["xdata"])>1){
		$timetext=urlencode("{day}");
		$title=urlencode("{requests}/{this_week}");
		$rowtitle=urlencode("| {requests}");
		@file_put_contents(pxstat_data_path("week.requests.data"), serialize($MAIN));
		$html[]="<div id='week-requests' style='width:100%;height:350px'></div>";
		$js[]="Loadjs('$page?courbe=yes&timetext=$timetext&container=week-requests&title=$title&data-file=week.requests.data&legend=rqs&row-title=$rowtitle');";
	}

	// ---- Sites les plus visites ----
	$sql="SELECT familysite,SUM(SIZE) as size,SUM(rqs) as rqs FROM proxy_traffic WHERE zdate>'$now' GROUP BY familysite ORDER BY size desc LIMIT $ProxyGraphsTOPNumber";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$PieData=array();$rows=array();
	while($ligne=pg_fetch_array($results)){
		$size=$ligne["size"]/1024;
		$PieData[$ligne["familysite"]]=$size;
		$rows[]=pxstat_top_row($ligne["familysite"],FormatNumber($ligne["rqs"]),$size,"fas fa-globe");
	}
	if(count($PieData)>1){
		@file_put_contents(pxstat_data_path("week.familysite.data"), serialize($PieData));
		$html[]=pxstat_chart_row("week-familysite",pxstat_top_table("{familysite}",$rows));
		$js[]="Loadjs('$page?camembert=yes&container=week-familysite&data-file=week.familysite.data&title=top_visited_websites');";
	}

	// ---- Categories ----
	// Cette page ecrivait "hour.category.data" et utilisait le conteneur
	// "hour-category" -- les memes que fw.proxy.statistics.php ET que l'autre
	// page de periode: le camembert affichait les donnees de la derniere page
	// chargee, pas les siennes.
	$sql="SELECT category,SUM(SIZE) as size,SUM(rqs) as rqs FROM proxy_traffic WHERE zdate>'$now' GROUP BY category ORDER BY size desc LIMIT $ProxyGraphsTOPNumber";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$PieData=array();$rows=array();
	while($ligne=pg_fetch_array($results)){
		$name=$tpl->CategoryidToName($ligne["category"]);
		$size=$ligne["size"]/1024;
		$PieData[$name]=$size;
		$rows[]=pxstat_top_row($name,FormatNumber($ligne["rqs"]),$size,"fas fa-tag");
	}
	if(count($PieData)>1){
		@file_put_contents(pxstat_data_path("week.category.data"), serialize($PieData));
		$html[]=pxstat_chart_row("week-category",pxstat_top_table("{category}",$rows));
		$js[]="Loadjs('$page?camembert=yes&container=week-category&data-file=week.category.data&title=top_visited_categories');";
	}

	// ---- Membres ----
	$sql="SELECT member,SUM(SIZE) as size,SUM(rqs) as rqs FROM proxy_traffic WHERE zdate>'$now' GROUP BY member ORDER BY size desc LIMIT $ProxyGraphsTOPNumber";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html(true));}
	$PieData=array();$rows=array();
	while($ligne=pg_fetch_array($results)){
		$size=$ligne["size"]/1024;
		$PieData[$ligne["member"]]=$size;
		$rows[]=pxstat_top_row($ligne["member"],FormatNumber($ligne["rqs"]),$size,"fas fa-user");
	}
	if(count($PieData)>1){
		@file_put_contents(pxstat_data_path("week.member.data"), serialize($PieData));
		$html[]=pxstat_chart_row("week-member",pxstat_top_table("{members}",$rows));
		$js[]="Loadjs('$page?camembert=yes&container=week-member&data-file=week.member.data&title=top_members');";
	}

	$html[]="<script>".@implode("\n", $js)."</script>";

	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n", $html));
}

/**
 * Un camembert et son classement cote a cote. Ils tenaient dans deux cellules
 * d'un tableau de mise en page dont la seconde etait declaree "width:1%" et
 * "nowrap": le tableau ne pouvait pas se replier, et le style du camembert
 * portait "with:600px" -- une propriete CSS qui n'existe pas, donc ignoree.
 */
function pxstat_chart_row($containerId,$tableHtml):string{
	$h ="<div style='display:flex;flex-wrap:wrap;gap:15px;align-items:flex-start;margin-top:15px'>";
	$h.="<div style='flex:1 1 520px;min-width:0'><div id='$containerId' style='width:100%;height:500px'></div></div>";
	$h.="<div style='flex:1 1 380px;min-width:0'>$tableHtml</div>";
	$h.="</div>";
	return $h;
}

/**
 * Un des trois classements de la page (sites, categories, membres). Les trois
 * rendaient exactement le meme balisage a trois endroits differents, avec a
 * chaque fois un "</tr>" orphelin avant la fermeture du tableau et aucun
 * <tbody>.
 */
function pxstat_top_table($headLabel,$rows):string{
	$h =array();
	$h[]="<div class='awc-card'><div class='awc-scroll'>";
	$h[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
	$h[]="<th>$headLabel</th>";
	$h[]="<th class='center'>{requests}</th>";
	$h[]="<th class='center'>{size}</th>";
	$h[]="</tr></thead><tbody>";
	if(count($rows)==0){
		$h[]="<tr><td colspan='3'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$h[]=@implode("\n",$rows);
	}
	$h[]="</tbody></table></div></div>";
	return @implode("\n",$h);
}

/** Une entree de classement: libelle, requetes, volume. */
// Pentest 2026-11-04 : site, categorie, membre (donnee externe) affiche sans encodage (XSS stockee).
function pxstat_top_row($label,$rqs,$sizeKb,$icon):string{
	$h ="<tr>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='$icon'></i></span>";
	$h.="<span class='awc-name'>".req_html($label)."</span>";
	$h.="</div></td>";
	$h.="<td class='center awc-nowrap'>$rqs</td>";
	$h.="<td class='center awc-nowrap'><b>".FormatBytes($sizeKb)."</b></td>";
	$h.="</tr>";
	return $h;
}

/**
 * Les fichiers de donnees intermediaires que courbe() et camembert() relisent.
 * Le nom arrivait BRUT de $_GET dans le chemin passe a file_get_contents():
 * "?data-file=../../../../etc/passwd" faisait lire un fichier arbitraire, puis
 * le desserialiser -- un vecteur d'injection d'objet. Seule une liste blanche
 * ferme ce chemin.
 */
function pxstat_data_files():array{
	$out=array();
	foreach(array("hour","week","month") as $p){
		foreach(array("bandwidth","requests","familysite","category","member") as $k){
			$out[]="$p.$k.data";
		}
	}
	return $out;
}

/** Le chemin du fichier de donnees demande, ou null s'il n'est pas legitime. */
function pxstat_data_path($name){
	$name=(string)$name;
	if(!in_array($name,pxstat_data_files(),true)){return null;}
	return "/usr/share/artica-postfix/ressources/logs/web/$name";
}

/**
 * Un libelle de graphique tel qu'il transite par l'URL. Il n'est compose que de
 * jetons {…} et de ponctuation: tout le reste est retire avant de l'inserer
 * dans le JavaScript produit par highcharts.
 */
function pxstat_clean_label($v):string{
	return preg_replace('/[^A-Za-z0-9_{} |\/-]/','',(string)$v);
}

function courbe(){
	// Le nom du fichier, l'identifiant du conteneur et les libelles arrivaient
	// BRUTS de $_GET et finissaient dans le JavaScript genere par highcharts:
	// un lien fabrique executait du code dans la session de l'administrateur.
	$path=pxstat_data_path(isset($_GET["data-file"])?$_GET["data-file"]:"");
	if($path===null){return;}
	$MAIN=@unserialize((string)@file_get_contents($path));
	// unserialize() renvoie false sur un fichier absent ou tronque: les acces
	// suivants a $MAIN["xdata"] declenchaient alors deux avertissements.
	if(!is_array($MAIN) || !isset($MAIN["xdata"]) || !isset($MAIN["ydata"])){return;}

	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=preg_replace('/[^A-Za-z0-9_-]/','',(string)(isset($_GET["container"])?$_GET["container"]:""));
	$legend=preg_replace('/[^A-Za-z0-9_ ]/','',(string)(isset($_GET["legend"])?$_GET["legend"]:""));
	$timetext=$tpl->_ENGINE_parse_body(pxstat_clean_label(isset($_GET["timetext"])?$_GET["timetext"]:""));
	$highcharts->xAxis=$MAIN["xdata"];
	$highcharts->Title=$tpl->_ENGINE_parse_body(pxstat_clean_label(isset($_GET["title"])?$_GET["title"]:""));
	$highcharts->TitleFontSize="22px";
	$highcharts->AxisFontsize="12px";
	$highcharts->yAxisTtitle=$legend;
	$highcharts->xAxis_labels=false;
	$highcharts->LegendPrefix=$tpl->javascript_parse_text("$timetext: ");
	$highcharts->LegendSuffix=$legend;
	$highcharts->xAxisTtitle=$timetext;
	$rowtitle=$tpl->_ENGINE_parse_body(pxstat_clean_label(isset($_GET["row-title"])?$_GET["row-title"]:""));
	$highcharts->datas=array($rowtitle=>$MAIN["ydata"]);
	echo $highcharts->BuildChart();

}

function camembert(){
	$path=pxstat_data_path(isset($_GET["data-file"])?$_GET["data-file"]:"");
	if($path===null){return;}
	$PieData=@unserialize((string)@file_get_contents($path));
	if(!is_array($PieData) || count($PieData)==0){return;}

	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=preg_replace('/[^A-Za-z0-9_-]/','',(string)(isset($_GET["container"])?$_GET["container"]:""));
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="";
	// Le titre est un JETON: seuls les caracteres d'un nom de jeton sont admis.
	$token=preg_replace('/[^A-Za-z0-9_]/','',(string)(isset($_GET["title"])?$_GET["title"]:""));
	$highcharts->Title=$tpl->_ENGINE_parse_body("{".$token."}");
	echo $highcharts->BuildChart();


}
