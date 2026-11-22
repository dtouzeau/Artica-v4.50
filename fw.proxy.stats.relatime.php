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
		$html[]=stats_zoom_row($key,$val);
	}
	$html[]="</tbody></table></div></div></div>";

	// La feuille est concatenee APRES l'analyse: la regex #{(.+?)}# de
	// _ENGINE_parse_body prendrait chaque corps de regle CSS pour un {token}.
	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("", $html));

}

/**
 * Une ligne cle/valeur du detail d'une requete.
 * Les deux cotes viennent du journal: ils sont echappes.
 */
function stats_zoom_row($key,$val):string{
	return "<tr><td class='awc-kv-k'>".htmlspecialchars((string)$key)."</td>"
		."<td><span class='awc-name'>".htmlspecialchars((string)$val)."</span></td></tr>";
}
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["STATS_SEARCH"])){$_SESSION["STATS_SEARCH"]="50 events";}
	
	// La valeur de recherche revient de la session et repartait BRUTE dans un
	// attribut value="..." : une valeur contenant un guillemet fermait
	// l'attribut.
	$searchvalue=htmlspecialchars((string)$_SESSION["STATS_SEARCH"],ENT_QUOTES);

	$html=array();
	$html[]="<div class=\"row border-bottom white-bg dashboard-header\">";
	$html[]="<div class=\"col-sm-8\"><h1 class=ng-binding>{realtime_requests}</h1></div>";
	$html[]="</div>";
	$html[]=stats_search_bar($t,$searchvalue);
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
 * Barre de recherche des requetes temps reel.
 * Remplace l'ancien .input-group Bootstrap et le faux spinner sk-spinner-wave
 * qui restait affiche en permanence: LoadAjax fournit deja son propre spinner.
 */
function stats_search_bar($t,$searchvalue):string{
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
	// articarest /system/logfile/search/squidtail (remplace framework/squid.php?squidtail-real).
	$logerror="";
	$loglines=$tpl->logfile_search("squidtail",$MAIN,false,250,$logerror);
	if($logerror<>""){
		echo $tpl->div_error($logerror);
		return;
	}
	
	// Quatorze libelles traduits ({categories}, {sitename}, {hostnames}, ...) et
	// quatre objets metier (squid_familysite, mysql_squid_builder, mysql_catz,
	// IP) etaient construits ici sans jamais servir. Le pire etait
	// mysql_squid_builder: une connexion MySQL ouverte a chaque rafraichissement
	// pour rien.
	// Cette fonction termine par un echo BRUT (pas de _ENGINE_parse_body): tout
	// libelle doit donc etre traduit ici, sinon il s'affiche en {token} litteral.
	$zdate_text=$tpl->_ENGINE_parse_body("{zDate}");
	$server_text=$tpl->_ENGINE_parse_body("{server}");
	$member_text=$tpl->_ENGINE_parse_body("{member}");
	$status_text=$tpl->_ENGINE_parse_body("{status}");
	$uri_text=$tpl->_ENGINE_parse_body("{url}");
	$size_text=$tpl->_ENGINE_parse_body("{size}");
	$showing_text=$tpl->_ENGINE_parse_body("{showing}");
	$nodata_text=$tpl->_ENGINE_parse_body("{no_data}");

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
	$html[]="<th>$zdate_text</th>";
	$html[]="<th>$server_text</th>";
	$html[]="<th>$member_text</th>";
	$html[]="<th>$status_text</th>";
	$html[]="<th>$uri_text</th>";
	$html[]="<th class='center'>$size_text</th>";
	$html[]="</tr></thead><tbody id='sr-rows'>";

	$data=$loglines;
	if(count($data)>3){$_SESSION["STATS_SEARCH"]=$_GET["search"];}
	$logfileD=new logfile_daemon();
	rsort($data);
	$CATEGORIES=$MAIN["CATEGORIES"] ?? null;
	$c=0;

	foreach ($data as $line){
		$xusers=array();

		$re=explode(":::", $line);
		// La ligne est decoupee en 19 champs et l'ancienne version les lisait
		// tous sans verifier: une ligne tronquee provoquait une cascade
		// d'avertissements "Undefined array key" en PHP 8.
		if(count($re)<19){continue;}
		$c++;

		if(preg_match("#^.*?\):\s+(.+)#", trim($re[0]),$rz)){$re[0]=$rz[1];}

		$color="black";
		$mac=trim(strtolower($re[0]));
		// C'etait "$mac==null;" -- une COMPARAISON sans effet, pas une
		// affectation. L'adresse n'etait donc pas neutralisee ici.
		if($mac=="-"){$mac=null;}
		$mac=str_replace("-", ":", $mac);
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		$ipaddr=trim($re[1]);
		if(!isset($GLOBALS["USER_MEM"])){$GLOBALS["USER_MEM"]=0;}
		$uid=$re[2];
		$uid2=$re[3];
		if($uid=="-"){$uid=null;}
		if($uid2=="-"){$uid2=null;}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $uid2)){$uid2=null;}
		if($uid==null){ if($uid2<>null){$uid=$uid2;} }
		
		$zdate=$re[4];
		$xtime=strtotime($zdate);
		if(!is_numeric($xtime)){continue;}
		$SUFFIX_DATE=date("YmdH",$xtime);
		$logzdate=date("Y-m-d H:i:s",$xtime);
		
		
		$proto=$re[5];
		$uri=$re[6];
		$code_error=$re[8];
		$SIZE=$re[9];
		$SquidCode=$re[10];
		$UserAgent=urldecode($re[11]);
		$Forwarded=$re[12];
		$sitename=trim($re[13]);
		$hostname=trim($re[14]);
		$response_time=$re[15];
		$MimeType=trim($re[16]);
		$sni=trim($re[17]);
		$proxyname=trim($re[18]);
		$size=$SIZE;
		$uid=trim(strtolower(str_replace("%20", " ", $uid)));
		$uid=str_replace("%25", "-", $uid);
		if($uid=="-"){$uid=null;}
		$Forwarded=str_replace("%25", "", $Forwarded);
		if($sni=="-"){$sni=null;}
		
		if($logfileD->CACHEDORNOT($SquidCode)){$color="#009223";}
		$codeToString=$logfileD->codeToString($code_error);
		
		if($proto=="CONNECT"){$color="#BAB700";$proto="SSL";}
		if($code_error>399){$color="#D0080A";}
		if($code_error==307){$color="#F59C44";}
		
		if(($proto=="GET") or ($proto=="POST")){
			if(preg_match("#TCP_REDIRECT#", $SquidCode)){
				$color="#A01E1E";
			}
		}
		
		
		
		if(strpos($uid, '$')>0){
			if(substr($uid, strlen($uid)-1,1)=="$"){
				$uid=null;
			}
		}
		
		if($sni<>null){
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $sitename)){$sitename=$sni;}
		}
		if($proxyname<>null){
			if(preg_match("#proxyname=(.+)#", $proxyname,$re)){
				$proxyname=$re[1];
			}
		}
		
		if($sitename=="-"){
			$h=parse_url($uri);
			if(isset($h["host"])){$sitename=$h["host"]; }
		}
		
		
		if(strpos($sitename, ":")>0){
			$XA=explode(":",$sitename);
			$sitename=$XA[0];
		}
		
		
		if($Forwarded=="unknown"){$Forwarded=null;}
		if($Forwarded=="-"){$Forwarded=null;}
		if($Forwarded=="0.0.0.0"){$Forwarded=null;}
		if($Forwarded=="255.255.255.255"){$Forwarded=null;}
		
		
		if(strlen($Forwarded)>4){
			$ipaddr=$Forwarded;
			$mac=null;
		}
		
		$ipaddr=str_replace("%25", "-", $ipaddr);
		$mac=str_replace("%25", "-", $mac);
		if($mac=="-"){$mac=null;}
		if($mac=="00:00:00:00:00:00"){$mac=null;}
		

		if(preg_match("#([0-9:a-z]+)$#", $mac,$z)){$mac=$z[1];}
		
		$xusers[]=$ipaddr;
		if($mac<>null){$xusers[]="$mac";}
		if($uid<>null){$xusers[]="$uid";}
		
		
		$SquidCode=str_replace(":HIER_DIRECT","",$SquidCode);
		
		// La branche "moins de 1 Ko" affectait $SIZE au lieu de $size: la
		// cellule affichait donc le nombre d'octets NU, sans unite, pour toutes
		// les requetes sous 1 Ko.
		if($SIZE>1024){$size=FormatBytes($SIZE/1024);}else{$size="$SIZE Bytes";}

		$MAINUTI=$logfileD->parseURL($uri,$CATEGORIES,$proto);
		$link=$MAINUTI["LINK"];
		$uri=$MAINUTI["URL"];

		// Pentest 2026-11-04 : URL, proxy, membres, code (journal Squid) (donnee externe) affiche sans encodage (XSS stockee).
		$link=$tpl->icon_link(1,"s_PopUpFull('".req_js($MAINUTI["GET_URL"])."','1024','900');");
		$html[]=stats_request_row($color,array(
			"date"    =>req_html($logzdate),
			"server"  =>req_html($proxyname),
			"members" =>req_html(@implode(" | ", $xusers)),
			"status"  =>req_html("$SquidCode/$code_error/$proto"),
			"url"     =>req_html($uri).$link,
			"size"    =>req_html($size)));

	}
	if($c==0){
		$html[]="<tr><td colspan='6' class='awc-empty'>$nodata_text</td></tr>";
	}

	// L'ancienne version ouvrait le pied A L'INTERIEUR du corps du tableau puis
	// refermait les deux dans le mauvais ordre, avec un colspan de 5 pour sept
	// colonnes. Le pied awc est hors du tableau.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>$showing_text $c</span>";
	$html[]="<span class='awc-pg' id='sr-pager'></span>";
	$html[]="</div></div></div>";
	$html[]="<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>";

	// awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
	echo awc_table_style().@implode("\n", $html).awc_pager_js(50,"sr-rows","sr-pager");
	
	
	
}

/**
 * Une requete du journal temps reel.
 * L'ancienne version ecrivait "<td <span style=...>" pour la colonne des
 * membres: le <span> devenait un attribut du <td> et son </span> restait
 * orphelin. L'URL declarait par ailleurs un colspan=2 dans l'en-tete pour
 * couvrir deux cellules distinctes (adresse et lien), ce qui n'est plus
 * necessaire: les deux tiennent dans la meme cellule.
 * $cells["url"] contient du HTML construit par parseURL(): il ne peut pas etre
 * echappe ici.
 */
function stats_request_row($color,$cells):string{
    $h ="<tr>";
    $h.="<td class='awc-nowrap awc-mono' style='color:$color'>{$cells["date"]}</td>";
    $h.="<td style='color:$color'>{$cells["server"]}</td>";
    $h.="<td style='color:$color'>{$cells["members"]}</td>";
    $h.="<td class='awc-nowrap' style='color:$color'>{$cells["status"]}</td>";
    $h.="<td style='color:$color'>{$cells["url"]}</td>";
    $h.="<td class='center awc-nowrap awc-mono' style='color:$color'>{$cells["size"]}</td>";
    $h.="</tr>";
    return $h;
}
