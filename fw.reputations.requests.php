<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.modsectools.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();


$users=new usersMenus();if(!$users->AsDnsAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();

    // Barre de recherche au dessin de template_admin::search_block() (.sb-stitch),
    // en lieu et place du .input-group Bootstrap brut. Le comportement ne change
    // pas: la recherche reste SERVEUR (LoadAjax vers ?search=).
    // La feuille .sb-stitch est emise par search() -- avec le tableau, HORS de
    // _ENGINE_parse_body, dont la regex #{(.+?)}# prendrait chaque corps de
    // regle CSS pour un jeton a resoudre.
    $html="
	<div class=\"row sb-stitch\">
		<div class='ibox-content'>
			<div class=\"input-group\">
				<span class='sb-ico'><i class='fas fa-search'></i></span>
	      		<input type=\"text\" class=\"form-control\" value=\"\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"javascript:Search$t(event);\">
	      		<span class=\"input-group-btn\">
	       		 <button class=\"btn sb-go\" type=\"button\" OnClick=\"javascript:ss$t();\">Go!</button>
	      	</span>
     	</div>
    	</div>
</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
	<script>
	$.address.state('/');
	$.address.value('/rbl-requests');
	$.address.title('Artica: RBL requests');	
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("Artica: RBL Requests",$html);
		echo $tpl->build_firewall();
		return;
	}

	
	echo $tpl->_ENGINE_parse_body($html);

}

function search(){
	$time=null;
	$sock=new sockets();
	$tpl=new template_admin();
	$max=0;$date=null;$c=0;
	
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
    $rp=intval($MAIN["MAX"]);
    if($rp==0){$rp=250;}
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

	// L'en-tete declarait QUATRE colonnes -- dont une en colspan=3 -- pour des
	// lignes qui en emettaient SIX: chaque valeur etait affichee sous le mauvais
	// intitule.
	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'>";
	$html[]="<thead><tr>";
	$html[]="<th>{ipaddr}</th>";
	$html[]="<th class='awc-nowrap'>{service}</th>";
	$html[]="<th class='awc-nowrap'>{rule}</th>";
	$html[]="<th class='awc-nowrap'>{status}</th>";
	$html[]="<th class='awc-nowrap'>{cache}</th>";
	$html[]="<th class='awc-nowrap'>{date}</th>";
	$html[]="</tr></thead><tbody id='rbl-rows'>";

	$RULES=rblreq_rules();

	// $search partait BRUT dans le chemin REST: un terme contenant "/" ou "?"
	// changeait la route appelee.
	$json=json_decode($sock->REST_API("/reputations/requests/$rp/".rawurlencode($search)));
	// json_decode rend NULL quand le daemon ne repond pas, et le foreach sur ce
	// NULL etait une TypeError fatale en PHP 8.
	$events=(is_object($json) && isset($json->Events) && is_array($json->Events))?$json->Events:array();

	$c=0;
	foreach ($events as $line){
		$row=rblreq_row($line,$RULES);
		if($row===""){continue;}
		$html[]=$row;
		$c++;
	}
	if($c==0){
		$html[]="<tr><td colspan='6' class='awc-empty'>{no_data}</td></tr>";
	}

	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>{showing} $c</span>"
	       ."<span class='awc-pg' id='rbl-pager'></span></div>";
	$html[]="</div></div>";

	// awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
	// search_block_css(): feuille de la barre de recherche que page() a rendue.
	echo awc_table_style()
	    .$tpl->search_block_css()
	    .$tpl->_ENGINE_parse_body(@implode("\n", $html))
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"rbl-rows","rbl-pager");
}

/**
 * Table ID -> nom de regle de reputation.
 * QUERY_SQL rend false quand la base est absente: l'ancien foreach direct sur
 * ce false etait une TypeError en PHP 8, et $RULES restait indefinie quand la
 * table etait vide -- la lecture $RULES[$id] plus bas emettait alors un
 * avertissement par ligne.
 */
function rblreq_rules():array{
	$out=array();
	$q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
	$results=$q->QUERY_SQL("SELECT rulename,ID FROM rbl_reputations");
	if(!is_array($results)){return $out;}
	foreach($results as $ligne){
		if(!isset($ligne["ID"])){continue;}
		$out[$ligne["ID"]]=(string)($ligne["rulename"] ?? "");
	}
	return $out;
}

/** Pastille de verdict d'une requete de reputation. */
function rblreq_found_badge($found):string{
	if($found==="FOUND"){
		return "<span class='awc-badge' style='background:#ba1a1a'>{found}</span>";
	}
	return "<span class='awc-badge awc-badge-off'>{unknown}</span>";
}

/** Pastille d'origine de la reponse: cache local ou interrogation reelle. */
function rblreq_cache_badge($hit):string{
	if($hit){
		return "<span class='awc-badge awc-badge-on'>{cached}</span>";
	}
	return "<span class='awc-badge awc-badge-off'>{not_cached}</span>";
}

/**
 * Une requete de reputation.
 * Rend "" quand la ligne de journal ne correspond pas au format attendu:
 * l'ancienne version lisait $re[1] a $re[4] SANS verifier le resultat du
 * preg_match, ce qui produisait quatre avertissements et une ligne vide par
 * enregistrement mal forme.
 */
function rblreq_row($line,array $RULES):string{
	$tpl=new template_admin();
	$line=trim((string)$line);
	if(strlen($line)<20){return "";}
	if(!preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+.*?\]:(.+)#",$line,$re)){return "";}

	$Month=$tpl->MonthToInteger($re[1]);
	$xTime=strtotime(date("Y")."-$Month-{$re[2]} {$re[3]}");
	$time=$tpl->time_to_date($xTime,true);
	$rest=$re[4];

	$hit=(strpos($rest," HIT")>0);
	$ipaddr=""; $Service=""; $FOUND=""; $RuleName="";
	if(preg_match("#Service\s+(.+?)\s+IP:([0-9\.]+)\s+([A-Z]+)(.+)#",$rest,$re2)){
		$Service=$re2[1];
		$ipaddr=$re2[2];
		$FOUND=$re2[3];
		$rest=$re2[4];
	}
	if(preg_match("#rule:([0-9]+)#",$rest,$re3)){
		$RuleName=isset($RULES[$re3[1]])?$RULES[$re3[1]]:"";
	}

	$hostname=""; $flag="";
	if($FOUND==="FOUND" && $ipaddr!==""){
		$modtools=new modesctools();
		$modtools->hostinfo($ipaddr,true);
		if(strlen((string)$modtools->flag)>3){
			$flag="<img src='img/".htmlspecialchars((string)$modtools->flag)."' style='margin-right:8px'>";
		}
		$hostname=(string)$modtools->hostname;
	}

	$zoom="Loadjs('fw.modsecurity.threats.php?zoom-ip-js=".urlencode($ipaddr)."')";
	$h ="<tr>";
	$h.="<td><div class='awc-ident'>";
	$h.="<span class='awc-ico'><i class='fas fa-globe'></i></span>";
	$h.="<div><div class='awc-name'>";
	if($ipaddr!==""){
		$h.=$flag."<a href='javascript:blur();' OnClick=\"$zoom\">".htmlspecialchars($ipaddr)."</a>";
	}else{
		$h.="<span class='awc-none'>&mdash;</span>";
	}
	$h.="</div>";
	if($hostname!==""){$h.="<div class='awc-meta'>".htmlspecialchars($hostname)."</div>";}
	$h.="</div></div></td>";
	$h.="<td class='awc-nowrap awc-mono'>".htmlspecialchars($Service)."</td>";
	$h.="<td class='awc-nowrap awc-mono'>".htmlspecialchars($RuleName)."</td>";
	$h.="<td class='awc-nowrap'>".rblreq_found_badge($FOUND)."</td>";
	$h.="<td class='awc-nowrap'>".rblreq_cache_badge($hit)."</td>";
	$h.="<td class='awc-nowrap awc-mono'>".htmlspecialchars((string)$time)."</td>";
	$h.="</tr>";
	return $h;
}
