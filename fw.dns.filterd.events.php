<?php
// awc_*() (awc_table_style/awc_pager_js/awc_filter_js): helpers partages des
// tableaux d'entites, definis dans class.statistics_appliance.inc.
// L'include DOIT rester INCONDITIONNEL. Place dans le bloc
// if(!class_exists("sockets")){...}, il n'etait jamais evalue -- class.template-admin.inc
// charge deja class.sockets.inc -- d'ou un fatal "Call to undefined function
// awc_table_style()" des que la page rendait son tableau.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.dnswhitelist.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.logfile_daemon.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(!ifisright()){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
// zoom_js() ouvre "$page?zoom=yes&data=...", mais aucun dispatch n'attrapait
// ?zoom= : page() repondait, et la modale affichait la page entiere.
if(isset($_GET["zoom"])){zoom();exit;}
if(isset($_GET["button"])){button_white();exit;}
if(isset($_GET["whitelist-js"])){whitelist_js();exit;}


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
	// each() a ete SUPPRIME de PHP 8.0: cette boucle etait une erreur fatale sur
	// la moitie du parc. Elle est remplacee par un foreach, qui fait la meme
	// chose depuis toujours.
	//
	// allowed_classes=false (PHP 7.0+): sans cette option unserialize()
	// instancie toute classe nommee dans le parametre et declenche ses
	// __wakeup()/__destruct() -- injection d'objet PHP. Le retour est teste:
	// unserialize() rend false sur une chaine invalide, et foreach(false) est
	// lui aussi fatal sous PHP 8.
	$raw=base64_decode((string)($_GET["data"] ?? ""),true);
	$data=($raw===false)?false:@unserialize($raw,array("allowed_classes"=>false));
	if(!is_array($data)){$data=array();}

	$html=array();
	$html[]="<div class=ibox-content>";
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'><tbody>";
	if(count($data)==0){
		$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
	}
	foreach ($data as $key=>$val){
		// Cle et valeur viennent de l'evenement, donc du trafic DNS.
		$html[]="<tr>";
		$html[]="<td class='text-capitalize awc-mono' style='width:1%' nowrap>".req_html((string)$key).":</td>";
		$html[]="<td><strong>".req_html((string)$val)."</strong></td>";
		$html[]="</tr>";
	}
	// Les quatre balises fermantes partaient APRES le rendu, hors du contenu.
	$html[]="</tbody></table></div></div></div>";
	$html[]="</div>";
	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n",$html));
}

function ifisright(){
	$users=new usersMenus();
	if($users->AsProxyMonitor){return true;}
	if($users->AsWebStatisticsAdministrator){return true;}
	if($users->AsDnsAdministrator){return true;}
	if($users->AsFirewallManager){return true;}
}

function whitelist_js(){
	$familysite=(string)($_GET["whitelist-js"] ?? "");
	$familysiteenc=urlencode($familysite);
	// $md est reinjecte dans un id et dans une URL evaluee en JavaScript:
	// c'est un md5, donc restreint aux caracteres hexadecimaux.
	$md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
	$page=CurrentPageName();
	$tpl=new template_admin();
	// La reponse est evaluee comme du JavaScript par Loadjs, branche d'erreur
	// comprise : poser l'en-tete AVANT tout retour anticipe.
	header("content-type: application/x-javascript");
	// Chercher-puis-inverser-ou-creer est une SEULE operation cote daemon : la
	// page enchainait un SELECT puis un UPDATE, que deux clics rapproches
	// pouvaient croiser (le second lisait l'etat d'avant le premier).
	$e=artrest_error(dnswhitelist_toggle($familysite));
	if($e!=""){$tpl->js_error($e);return;}
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/unbound/reconfigure");
	echo "LoadAjaxSilent('btn-$md','$page?button=yes&md=$md&familysite=$familysiteenc')";
	
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["WEBF_SEARCH"])){$_SESSION["WEBF_SEARCH"]="50 events";}
	if(isset($_GET["logfile"])){$addPLUS="&logfile=".urlencode($_GET["logfile"]);}
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-8\"><h1 class=ng-binding>{DNS_FILTERING}: {blocked_requests} </h1></div>
	</div>
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"{$_SESSION["WEBF_SEARCH"]}\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>
	<div class='row' id='spinner'>
		<div id='progress-firehol-restart'></div>
		<div  class='ibox-content'>
		<div class='sk-spinner sk-spinner-wave'>
			<div class='sk-rect1'></div>
			<div class='sk-rect2'></div>
			<div class='sk-rect3'></div>
			<div class='sk-rect4'></div>
			<div class='sk-rect5'></div>
		</div>
		
		
			<div id='table-loader'></div>
		</div>
	</div>
	</div>
	<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss+'$addPLUS');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";

	
	echo awc_table_style().$tpl->_ENGINE_parse_body($html);

}

function button_white(){
	$page=CurrentPageName();
	$familysite=(string)($_GET["familysite"] ?? "");
	$tpl=new template_admin();
	$familysiteEnc=urlencode($familysite);
	$md=preg_replace('/[^a-f0-9]/','',(string)($_GET["md"] ?? ""));
	$state=dnswhitelist_state($familysite);
	$enabled=intval($state["Enabled"]);
	
	
	$js="Loadjs('$page?whitelist-js=$familysiteEnc&md=$md')";
	if($enabled==0){
		echo $tpl->button_autnonome("{whitelist}", $js, "fas fa-thumbs-up",null,0,"btn-info","small");
		return;
	}
	
	echo $tpl->button_autnonome("{whitelisted}", $js, "fas fa-thumbs-up",null,0,"btn-primary","small");
		
	
	
	
	
}

/**
 * Une ligne du journal de filtrage DNS.
 *
 * TOUTES ces valeurs sortent du journal du filtre, c'est-a-dire de ce que des
 * clients ont demande au resolveur: elles partaient BRUTES dans le HTML. Un nom
 * de domaine porteur de balisage devenait donc du script execute dans la console
 * au moment ou un administrateur consultait les evenements -- un XSS stocke dont
 * l'attaquant n'a besoin que d'un port 53 qui l'ecoute.
 */
function fdev_row($TR,$line,$WHITE,$zcat,$tpl,$page,$today):string{
	$md=md5($line);
	$date=(string)$TR[0];
	$TIME=(string)$TR[1];
	$ALLOW=(string)$TR[3];
	$CLIENT_IP=(string)$TR[5];
	$RULE=(string)$TR[6];
	$CATEGORY=categoryCodeTocatz($TR[7]);
	$URI=(string)$TR[8];

	$parse=parse_url($URI);
	if(!isset($parse["host"])){return "";}
	$hostname=(string)$parse["host"];

	// Echelle de gravite du filtre.
	$cls="awc-badge-off";
	if($ALLOW=="BLOCK-LD"){$cls="fdev-warn";}
	if($ALLOW=="BLOCK"){$cls="fdev-err";}
	if($ALLOW=="REDIR"){$cls="fdev-redir";}
	if($ALLOW=="PASS"){$cls="awc-badge-on";}

	$familysite=$zcat->GetFamilySites($hostname);
	if(preg_match("#([0-9]+)\.addr#",$hostname,$re)){
		$ton=$re[1];
		$hostname=str_replace("$ton.addr",long2ip($ton),$hostname);
	}
	if($date==$today){$date="";}

	$js="Loadjs('$page?whitelist-js=".urlencode($familysite)."&md=$md')";
	$bt=$tpl->button_autnonome("{whitelist}",$js,"fas fa-thumbs-up",null,0,"btn-info","small");
	if(isset($WHITE[$familysite])){
		$bt=$tpl->button_autnonome("{whitelisted}",$js,"fas fa-thumbs-up",null,0,"btn-primary","small");
	}

	// Le balisage d'origine etait casse a trois endroits: "<td <span ...>"
	// (un chevron parasite qui faisait avaler les attributs), un
	// "style='...'style='...'" colle et en double, et un <span id='btn-...'>
	// jamais ferme.
	// Pentest 2026-11-04 : champs du journal DNS (donnee externe) : htmlspecialchars UTF-8 rendait "" sur du Latin-1, remplace par req_html (XSS stockee).
	$h ="<tr id='$md'>";
	$h.="<td style='width:1%' nowrap><span class='awc-seen'>".req_html(trim("$date $TIME"))."</span></td>";
	$h.="<td class='awc-mono' style='width:1%' nowrap>".req_html($CLIENT_IP)."</td>";
	$h.="<td>".req_html($RULE)." / $CATEGORY</td>";
	$h.="<td class='center' style='width:1%' nowrap><span class='awc-badge $cls'>".req_html($ALLOW)."</span></td>";
	$h.="<td><span class='awc-name'>".req_html((string)$familysite)."</span>";
	$h.="<div class='awc-meta awc-mono'>".req_html($hostname)."</div></td>";
	$h.="<td class='center' style='width:1%' nowrap><span id='btn-$md'>$bt</span></td>";
	$h.="</tr>";
	return $h;
}

/** Echelle de gravite propre a cette page; le reste vient de awc_table_style(). */
function fdev_style():string{
	return "<style>
.fdev-err{background:#ba1a1a}
.fdev-warn{background:#865300}
.fdev-redir{background:#6d7a77}
</style>";
}

function search(){
	include_once('ressources/class.ufdbguard-tools.inc');
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new template_admin();
	$GLOBALS["TPLZ"]=$tpl;

	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	$sock->getFrameWork("dnsfilterd.php?ufdb-real=yes&rp={$MAIN["MAX"]}&query=".urlencode($MAIN["TERM"]));

	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$hostnameLabel=$tpl->_ENGINE_parse_body("{hostname}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$today=date("Y-m-d");

	$WHITE=array();
	$results=dnswhitelist_list();
	if(is_array($results)){
		foreach ($results as $ligne){$WHITE[$ligne["pattern"]]=1;}
	}

	$filename="/usr/share/artica-postfix/ressources/logs/dnsfilterd.log.tmp";
	$data=explode("\n",(string)@file_get_contents($filename));
	krsort($data);

	$zcat=new squid_familysite();
	$rows=array();
	foreach ($data as $line){
		$TR=preg_split("/[\s]+/",$line);
		if(count($TR)<10){continue;}
		$row=fdev_row($TR,$line,$WHITE,$zcat,$tpl,$page,$today);
		if($row===""){continue;}
		$rows[]=$row;
	}
	$c=count($rows);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'><table class='awc-tbl awc-tbl-compact'>";
	$html[]="<thead><tr>";
	$html[]="<th>$zdate</th>";
	$html[]="<th>$ipaddr</th>";
	$html[]="<th>$rulename</th>";
	$html[]="<th class='center'>{action}</th>";
	$html[]="<th>$hostnameLabel</th>";
	$html[]="<th class='center'>{unblock}</th>";
	$html[]="</tr></thead><tbody id='fdev-rows'>";
	if($c==0){
		$html[]="<tr><td colspan='6'><div class='awc-empty'>{no_data}</div></td></tr>";
	}else{
		$html[]=@implode("\n",$rows);
	}
	// Le <tfoot> etait emis A L'INTERIEUR du <tbody>, et portait un pager
	// footable jamais alimente: l'init ciblait $('.footable'), un selecteur
	// GLOBAL, alors que ce tableau porte .awc-tbl.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c {items}</span>";
	$html[]="<span class='awc-pg' id='fdev-pager'></span>";
	$html[]="</div></div></div>";

	// Contenu de fichier: echappe, il n'a jamais eu vocation a etre du HTML.
	$cmd=trim((string)@file_get_contents("/usr/share/artica-postfix/ressources/logs/dnsfilterd.log.cmd"));
	if($cmd!==""){
		$html[]="<div class='awc-meta' style='padding:0 16px 10px 16px'>"
			."<span style='font-family:monospace'>".req_html($cmd)."</span></div>";
	}

	// La sortie n'etait PAS analysee: les en-tetes en {token} et le libelle
	// {unblock} s'affichaient litteralement. Les feuilles de style et le script
	// restent, eux, en dehors de l'analyse.
	$script="<script>NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."</script>";

	echo awc_table_style().fdev_style()
		.$tpl->_ENGINE_parse_body(@implode("\n",$html))
		.$script
		.awc_pager_js(50,"fdev-rows","fdev-pager");
}


function categoryCodeTocatz($category){
	if(preg_match("#P([0-9]+)#", $category,$re)){$category=$re[1];}
	if($category==0){return "($category) Unknown(0)";}

	$catz=new mysql_catz(true);
	$categories_descriptions=$catz->categories_descriptions();
	if(!isset($categories_descriptions[$category]["categoryname"])){
		return "($category) <strong>Unkown</strong>";
	}

	$name=$categories_descriptions[$category]["categoryname"];
	$category_description=$categories_descriptions[$category]["category_description"];
	$js="Loadjs('fw.ufdb.categories.php?category-js=$category')";
	return $GLOBALS["TPLZ"]->td_href($name,$category_description,$js);
}
