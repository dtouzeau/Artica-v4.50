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
	// unserializeb64() remplace unserialize(base64_decode(...)) sur une valeur
	// venue de l'URL: elle rend un tableau vide au lieu d'instancier des objets.
	$data=unserializeb64($_GET["data"]);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl awc-tbl-compact'><tbody>";
	if(count($data)==0){
		$html[]="<tr><td class='awc-empty'>{no_data}</td></tr>";
	}
	foreach ($data as $key=>$val){
		$html[]=daemon_zoom_row($key,$val);
	}
	$html[]="</tbody></table></div></div></div>";

	// La feuille est concatenee APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body prendrait chaque corps de regle CSS pour un {token}.
	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("", $html));

}

/**
 * Une ligne cle/valeur du detail d'un evenement.
 * Les deux cotes viennent du journal: ils sont echappes.
 */
function daemon_zoom_row($key,$val):string{
	return "<tr><td class='awc-kv-k'>".htmlspecialchars((string)$key)."</td>"
		."<td><span class='awc-name'>".htmlspecialchars((string)$val)."</span></td></tr>";
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
    $addPLUS=null;
	if(!isset($_SESSION["PROXY_DAEMON_SEARCH"])){$_SESSION["PROXY_DAEMON_SEARCH"]="50 events";}
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
	
	// La valeur de recherche revient de la session et repartait BRUTE dans un
	// attribut value="..." : une valeur contenant un guillemet fermait
	// l'attribut. Le <p> de l'explication n'etait par ailleurs jamais referme.
	$searchvalue=htmlspecialchars((string)$_SESSION["PROXY_DAEMON_SEARCH"],ENT_QUOTES);

	$html=array();
	$html[]="<div class=\"row border-bottom white-bg dashboard-header\">";
	$html[]="<div class=\"col-sm-8\"><h1 class=ng-binding>{proxy_events}</h1>";
	$html[]="<p>{proxy_events_explain}</p></div>";
	$html[]="</div>";
	$html[]=daemon_search_bar($t,$searchvalue);
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
			LoadAjax('table-loader','$page?search='+ss+'$addPLUS');
		}
		ss$t();
	</script>";

	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).$script;

}

/**
 * Barre de recherche des evenements du service.
 * Remplace l'ancien .input-group Bootstrap et le faux spinner sk-spinner-wave
 * qui restait affiche en permanence: LoadAjax fournit deja son propre spinner.
 */
function daemon_search_bar($t,$searchvalue):string{
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
	$tpl=new template_admin();
	$c=0;
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	$sock=new sockets();

	$MAIN["MAX"]=$MAIN["MAX"]+50;
	// articarest /system/logfile/search/squid-cache (remplace framework/squid.php?cachelogs).
	$logerror="";
	$loglines=$tpl->logfile_search("squid-cache",$MAIN,false,500,$logerror);
	if($logerror<>""){
		echo $tpl->div_error($logerror);
		return;
	}

	// Quatorze libelles traduits ({categories}, {members}, {sitename}, ...) et
	// cinq objets metier (squid_familysite, logfile_daemon, mysql_squid_builder,
	// mysql_catz, IP) etaient construits ici sans jamais servir. Le pire etait
	// mysql_squid_builder: une connexion MySQL ouverte a chaque rafraichissement
	// pour rien.
	// Cette fonction termine par un echo BRUT (pas de _ENGINE_parse_body): tout
	// libelle doit donc etre traduit ici, sinon il s'affiche en {token} litteral.
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$showing_text=$tpl->_ENGINE_parse_body("{showing}");
	$nodata_text=$tpl->_ENGINE_parse_body("{no_data}");

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
	$html[]="<th>$zdate</th>";
	$html[]="<th>$events</th>";
	$html[]="</tr></thead><tbody id='dm-rows'>";

	$data=$loglines;
	if(count($data)>3){$_SESSION["PROXY_DAEMON_SEARCH"]=$_GET["search"];}
	rsort($data);

	foreach ($data as $rawline){
		$datetext="&nbsp;";
		if(preg_match("#^([0-9\.\/\s+:]+)\s+#",$rawline,$re)){
			$rawline=str_replace($re[1],"",$rawline);
			$datetext=$tpl->time_to_date(strtotime($re[1]),true);
		}
		if(trim($rawline)===""){continue;}
		if(daemon_line_skipped($rawline)){continue;}
		$c++;
		$html[]=daemon_event_row($datetext,$rawline);
	}
	if($c==0){
		$html[]="<tr><td colspan='2' class='awc-empty'>$nodata_text</td></tr>";
	}

	// L'ancienne version ouvrait le pied A L'INTERIEUR du corps du tableau puis
	// refermait les deux dans le mauvais ordre: imbrication invalide, le
	// navigateur deplacait le pied hors du corps. Le pied awc est hors du tableau.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>$showing_text $c</span>";
	$html[]="<span class='awc-pg' id='dm-pager'></span>";
	$html[]="</div></div></div>";
	// La commande relancee par le collecteur partait BRUTE dans la page.
	$html[]="<div style='font-size:10px'>".htmlspecialchars((string)@file_get_contents("/usr/share/artica-postfix/ressources/logs/access.log.cmd"))."</div>";
	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

	// awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
	echo awc_table_style().@implode("\n", $html).awc_pager_js(50,"dm-rows","dm-pager");



}

/**
 * Une ligne du journal du service.
 * L'ancienne version fermait un </span> jamais ouvert dans la cellule de date,
 * et inserait la ligne de journal BRUTE dans le HTML avant de l'entourer d'un
 * <span> de couleur: le journal reproduit des URL et des messages de serveurs
 * distants, donc du texte non maitrise. La ligne est desormais echappee AVANT
 * d'etre coloree.
 */
function daemon_event_row($datetext,$rawline):string{
	$class=daemon_line_class($rawline);
	$text=htmlspecialchars($rawline);
	if($class<>""){$text="<span class='$class'>$text</span>";}
	return "<tr><td class='awc-nowrap awc-mono'>$datetext</td><td>$text</td></tr>";
}

/**
 * Lignes de demarrage et d'information sans interet operationnel.
 * Elles noyaient les erreurs reelles dans le tableau.
 */
function daemon_line_skipped($line):bool{
	static $skip=array(
		"#already defined. Ignoring#i",
		"#Socket created at#i",
		"#Sending .*? messages from#i",
		"#Stop sending#i",
		"#Set Current#i",
		"#Pinger exiting#i",
		"#Logfile: closing log#i",
		"#Logfile: opening#i",
		"#Startup: Initializ#i",
		"#Processing Configuration File#i",
		"#You should probably remove#i",
		"#is ignored to keep splay#i",
		"#is a subnetwork of#i",
		"#violates HTTP#i",
		"#empty ACL#i");
	foreach($skip as $re){
		if(preg_match($re,$line)){return true;}
	}
	return false;
}

/**
 * Classe de couleur d'une ligne de journal.
 * La derniere correspondance l'emporte, comme dans la cascade de conditions
 * qu'elle remplace. FATAL et "temporarily unavailable" arrivent en dernier:
 * ils devaient ecraser une coloration d'avertissement deja posee, ce que
 * l'ancienne version faisait en retirant les <span> a coups de str_replace --
 * lesquels retiraient aussi les </span> presents dans le texte du journal.
 *
 * Les trois regles "Detected REVIVED", "Detected DEAD" et "failure while
 * accepting a TLS connection" etaient testees par strpos(...)>0: une ligne
 * COMMENCANT par ce texte donne la position 0 et n'etait donc jamais coloree.
 * Elles sont passees en preg_match, qui n'a pas ce point aveugle.
 */
function daemon_line_class($line):string{
	static $rules=array(
		"#can't initialize#"                                   =>"text-danger",
		"#No such file or directory#i"                         =>"text-warning",
		"#TCP connection to .*?failed#"                        =>"text-danger",
		"#WARNING#i"                                           =>"text-warning",
		"#unexpected#i"                                        =>"text-warning",
		"#abnormally#i"                                        =>"text-danger",
		"#Reconfiguring#i"                                     =>"text-success",
		"#Accepting (HTTP|NAT|ICP|HTCP|SNMP|SSL)#i"            =>"text-success",
		"#(received type 1 NTLM token|Unspecified GSS failure)#i"=>"font-bold text-danger",
		"#Detected REVIVED#"                                   =>"text-warning",
		"#Detected DEAD#"                                      =>"text-danger",
		"#failure while accepting a TLS connection#"           =>"text-warning",
		"#Ready to serve requests#i"                           =>"text-success",
		"#Adding\s+#i"                                         =>"text-success",
		"#No route to host#i"                                  =>"text-warning",
		"#helperOpenServers: Starting#i"                       =>"text-success",
		"#FATAL#i"                                             =>"text-danger",
		"#temporarily unavailable#i"                           =>"text-danger");
	$class="";
	foreach($rules as $re=>$c){
		if(preg_match($re,$line)){$class=$c;}
	}
	return $class;
}
