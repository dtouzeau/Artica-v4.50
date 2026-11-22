<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["zoom"])){zoom();exit;}

page();

function zoom_js(){
	header("content-type: application/x-javascript");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$data=urlencode($_GET["data"]);
	$title=$tpl->_ENGINE_parse_body("{realtime_requests}::ZOOM");
	$tpl->js_dialog($title, "$page?zoom=yes&data=$data");
}
function zoom(){
	$tpl=new template_admin();
	// each() a ete SUPPRIMEE en PHP 8.0 (obsolete depuis 7.2): sur une appliance
	// PHP 8 cette fonction s'arretait sur "Call to undefined function each()".
	// unserializeb64() remplace unserialize(base64_decode(...)) sur une valeur
	// venue de l'URL: elle rend un tableau vide au lieu d'instancier des objets.
	$data=unserializeb64($_GET["data"]);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl awc-tbl-compact'><tbody>";
	if(count($data)==0){
		$html[]="<tr><td class='awc-empty'>{no_data}</td></tr>";
	}
	foreach($data as $key=>$val){
		$html[]=cluster_zoom_row($key,$val);
	}
	$html[]="</tbody></table></div></div></div>";

	// La feuille est concatenee APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body prendrait chaque corps de regle CSS pour un {token}.
	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("", $html));

}

/**
 * Une ligne cle/valeur du detail d'un evenement de grappe.
 * Les deux cotes viennent du journal: ils sont echappes.
 */
function cluster_zoom_row($key,$val):string{
	return "<tr><td class='awc-kv-k'>".htmlspecialchars((string)$key)."</td>"
		."<td><span class='awc-name'>".htmlspecialchars((string)$val)."</span></td></tr>";
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["CLUSTER_SEARCH"])){$_SESSION["CLUSTER_SEARCH"]="50 events";}
	
	// La valeur de recherche revient de la session et repartait BRUTE dans un
	// attribut value="..." : une valeur contenant un guillemet fermait
	// l'attribut.
	$searchvalue=htmlspecialchars((string)$_SESSION["CLUSTER_SEARCH"],ENT_QUOTES);

	$html=array();
	$html[]="<div class=\"row border-bottom white-bg dashboard-header\">";
	$html[]="<div class=\"col-sm-8\"><h1 class=ng-binding>{cluster_events}</h1></div>";
	$html[]="</div>";
	$html[]=cluster_search_bar($t,$searchvalue);
	$html[]="<div id='table-loader'></div>";

	// Bloc de script concatene APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body prendrait chaque corps d'accolade JavaScript pour un
	// {token}.
	$script="<script>
		function Search$t(e){
			if(!checkEnter(e)){return;}
			ss$t();
		}
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss);
		}
		ss$t();
	</script>";

	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;

}

/**
 * Barre de recherche des evenements de grappe.
 * Remplace l'ancien .input-group Bootstrap et le faux spinner sk-spinner-wave
 * qui restait affiche en permanence: LoadAjax fournit deja son propre spinner.
 */
function cluster_search_bar($t,$searchvalue):string{
	$h ="<div class='awc-search'>";
	$h.="<span class='awc-search-field'>";
	$h.="<i class='fas fa-magnifying-glass awc-search-ico'></i>";
	$h.="<input type='text' id='search-this-$t' value=\"$searchvalue\" placeholder='{search}' "
		."OnKeyPress=\"Search$t(event);\">";
	$h.="<button type='button' class='awc-search-clear' title='{clear}' "
		."OnClick=\"document.getElementById('search-this-$t').value='';ss$t();\">"
		."<i class='fas fa-xmark'></i></button>";
	$h.="</span>";
	$h.="<span class='awc-search-actions'>";
	$h.="<button type='button' class='awc-btn' OnClick=\"ss$t();\">{search}</button>";
	$h.="</span>";
	$h.="</div>";
	return $h;
}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	$sock=new sockets();
	// articarest /system/logfile/search/cluster-client (remplace framework/squid.php?cluster-real).
	$logerror="";
	$loglines=$tpl->logfile_search("cluster-client",$MAIN,false,250,$logerror);
	if($logerror<>""){
		echo $tpl->div_error($logerror);
		return;
	}
	$categories=$tpl->javascript_parse_text("{categories}");
	$ipaddr=$tpl->javascript_parse_text("{members}");
	$error=$tpl->javascript_parse_text("{error}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$autorefresh=$tpl->javascript_parse_text("{autorefresh}");
	$hostnames=$tpl->javascript_parse_text("{hostnames}");
	$options=$tpl->javascript_parse_text("{options}");
	$all=$tpl->javascript_parse_text("{all}");
	$back=$tpl->javascript_parse_text("{back}");
	$function=$tpl->javascript_parse_text("{function}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$memory=$tpl->_ENGINE_parse_body("{memory}");
	$load=$tpl->_ENGINE_parse_body("{load}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$line=$tpl->_ENGINE_parse_body("{line}");
	
	// Cette fonction termine par un echo BRUT (pas de _ENGINE_parse_body): tout
	// libelle doit donc etre traduit ici, sinon il s'affiche en {token} litteral.
	$showing_text=$tpl->_ENGINE_parse_body("{showing}");
	$process_text=$tpl->_ENGINE_parse_body("{process}");
	$nodata_text=$tpl->_ENGINE_parse_body("{no_data}");

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'><thead><tr>";
	$html[]="<th>$zdate</th>";
	$html[]="<th>$process_text</th>";
	$html[]="<th>$events</th>";
	$html[]="<th class='center'>$line</th>";
	$html[]="<th class='center'>PID</th>";
	$html[]="<th class='center'>$memory</th>";
	$html[]="<th class='center'>$load</th>";
	$html[]="</tr></thead><tbody id='cl-rows'>";

	$data=$loglines;
	if(count($data)>3){$_SESSION["CLUSTER_SEARCH"]=$_GET["search"];}

	$today=date("Y-m-d");
	rsort($data);
	$c=0;

	foreach ($data as $rawline){
		// L'ancienne boucle reutilisait $line -- la variable de boucle ET le
		// libelle traduit de la colonne -- pour y ranger le numero de ligne.
		if(!preg_match("#(.+?)\s+\[([0-9]+)\]\s+(.+?):\s+(.+?)- function:(.+?)\s+in line:([0-9]+) Mem:(.*?)\s+Load:([0-9\.]+)#", $rawline,$re)){continue;}
		$c++;
		$html[]=cluster_event_row(array(
			"date"    =>str_replace($today." ","",$re[1]),
			"pid"     =>$re[2],
			"process" =>$re[3],
			"event"   =>$re[4],
			"function"=>$re[5],
			"line"    =>$re[6],
			"memory"  =>$re[7],
			"load"    =>$re[8]));
	}
	if($c==0){
		$html[]="<tr><td colspan='7' class='awc-empty'>$nodata_text</td></tr>";
	}

	// L'ancienne version ouvrait le pied A L'INTERIEUR du corps du tableau puis
	// refermait les deux dans le mauvais ordre (imbrication invalide), avec un
	// colspan de 6 pour 7 colonnes. Le pied awc est hors du tableau.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>$showing_text $c</span>";
	$html[]="<span class='awc-pg' id='cl-pager'></span>";
	$html[]="</div></div></div>";
	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

	// awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
	echo awc_table_style().@implode("\n", $html).awc_pager_js(50,"cl-rows","cl-pager");



}

/**
 * Un evenement de grappe.
 * Tous les champs viennent du journal texte: ils sont echappes.
 * La fonction et le numero de ligne sont replies sous le nom du processus --
 * c'etaient deux colonnes etroites qui poussaient le message hors du tableau.
 */
function cluster_event_row($e):string{
	$h ="<tr>";
	$h.="<td class='awc-nowrap awc-mono'>".htmlspecialchars($e["date"])."</td>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fas fa-microchip'></i></span>";
	$h.="<span><span class='awc-name'>".htmlspecialchars($e["process"])."</span>";
	$h.="<div class='awc-meta'>".htmlspecialchars($e["function"])."()</div></span>";
	$h.="</div></td>";
	$h.="<td>".htmlspecialchars($e["event"])."</td>";
	$h.="<td class='center awc-mono'>".htmlspecialchars($e["line"])."</td>";
	$h.="<td class='center awc-mono'>".htmlspecialchars($e["pid"])."</td>";
	$h.="<td class='center awc-mono'>".htmlspecialchars(trim($e["memory"]))."</td>";
	$h.="<td class='center awc-mono'>".htmlspecialchars($e["load"])."</td>";
	$h.="</tr>";
	return $h;
}
