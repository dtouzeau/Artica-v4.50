<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])){search();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	if(!isset($_SESSION["SAFESEARCH_SEARCH"])){$_SESSION["SAFESEARCH_SEARCH"]="50 events";}
	
	// La valeur de recherche revient de la session et repartait BRUTE dans un
	// attribut value="..." : une valeur contenant un guillemet fermait
	// l'attribut.
	$searchvalue=htmlspecialchars((string)$_SESSION["SAFESEARCH_SEARCH"],ENT_QUOTES);

	$html=array();
	$html[]=gsb_search_bar($t,$searchvalue);
	$html[]="<div id='progress-firehol-restart'></div>";
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
 * Barre de recherche des evenements Google Safe Browsing.
 * Remplace l'ancien .input-group Bootstrap.
 */
function gsb_search_bar($t,$searchvalue):string{
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
	$MAIN=$tpl->format_search_protocol($_GET["search"]);
	// articarest /system/logfile/search/safebrowsing (/var/log/SafeBrowsing.log). L'ancien
	// framework/squid2.php?events-gsb-service n'existait plus : la page restait vide.
	$logerror="";
	$loglines=$tpl->logfile_search("safebrowsing",$MAIN,true,250,$logerror);
	if($logerror<>""){
		echo $tpl->div_error($logerror);
		return;
	}
	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
	$html[]="<th>{date}</th>";
	$html[]="<th class='center'>{PID}</th>";
	$html[]="<th class='center'>{type}</th>";
	$html[]="<th>{events}</th>";
	$html[]="</tr></thead><tbody id='gs-rows'>";

	$data=$loglines;
	if(count($data)>3){$_SESSION["SAFESEARCH_SEARCH"]=$_GET["search"];}
	rsort($data);

	$c=0;
	foreach ($data as $rawline){
		$rawline=trim($rawline);
        if(!preg_match("#(.*?)\s+([0-9]+)\s+([0-9:]+).*?\s+proxy-safebrowsing\[([0-9]+)\]:.*?\[([A-Z]+)\]:(.+)#",$rawline,$re)){
            VERBOSE("NO MATCH $rawline",__LINE__);
            continue;}

		$c++;
		// $re[1], le nom du jour, etait extrait sans jamais servir.
        $stime=date("Y-m")."-{$re[2]} {$re[3]}";
		$html[]=gsb_event_row($tpl->time_to_date(strtotime($stime),true),$re[4],$re[5],trim($re[6]));

	}
	if($c==0){
		$html[]="<tr><td colspan='4' class='awc-empty'>{no_data}</td></tr>";
	}

	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>{showing} $c</span>";
	$html[]="<span class='awc-pg' id='gs-pager'></span>";
	$html[]="</div></div></div>";
	// Le motif relu du fichier partait BRUT dans la page.
	$html[]="<div><i>".htmlspecialchars((string)@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/SafeBrowsing.syslog.patter"))."</i></div>";

	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body(@implode("\n",$html))
	    ."<script>NoSpinner();</script>"
	    .awc_pager_js(50,"gs-rows","gs-pager");
	
	
	
}

/**
 * Un evenement du service Google Safe Browsing.
 * Les quatre champs viennent de syslog et partaient BRUTS dans le HTML.
 */
function gsb_event_row($date,$pid,$type,$event):string{
    $class=gsb_event_class($type,$event);
    // Pentest 2026-11-04 : ligne syslog (donnee externe, URL demandees) : htmlspecialchars UTF-8 rendait "" sur du Latin-1, remplace par req_html (XSS stockee).
    $h ="<tr>";
    $h.="<td class='awc-nowrap awc-mono $class'>".req_html($date)."</td>";
    $h.="<td class='center awc-mono $class'>".req_html($pid)."</td>";
    $h.="<td class='center awc-nowrap $class'>".req_html($type)."</td>";
    $h.="<td class='$class'>".req_html($event)."</td>";
    $h.="</tr>";
    return $h;
}

/**
 * Classe de couleur d'un evenement.
 * La derniere correspondance l'emporte, comme dans la cascade de conditions
 * qu'elle remplace. Les deux premieres regles portent sur le TYPE syslog, les
 * quatre suivantes sur le texte du message -- l'ancienne cascade melangeait les
 * deux sans que ce soit visible.
 */
function gsb_event_class($type,$event):string{
    $class="";
    if(preg_match("#(INFO)+#",$type)){$class="text-success";}
    if(preg_match("#\s+stopped(\s+|\.)#i",$event)){$class="text-warning";}
    if(preg_match("#ALERT#",$type)){$class="text-danger font-bold";}
    if(preg_match("#Disabling#",$event)){$class="text-warning";}
    if(preg_match("#Not loading#",$event)){$class="text-warning";}
    if(preg_match("#(ERROR|FATAL)#",$event)){$class="text-danger";}
    return $class;
}
