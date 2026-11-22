<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["search"])){table();exit;}
// Six lignes de repartition ont ete retirees ici: enable-signature,
// enable-firewall, rule-popup, rule-settings, ID (POST), delete-rule-js et
// delete-remove (POST) appelaient enable_signature(), enable_firewall(),
// rule_settings(), rule_save(), delete_js() et delete_remove() -- SIX fonctions
// qui n'existent NI dans cette page NI dans aucune classe du depot (verifie).
// Chacune de ces requetes produisait un "Call to undefined function" fatal.
// $_POST["ID"] etant un nom de champ tres courant, un envoi de formulaire egare
// suffisait a mettre la page en erreur 500.
page();



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// $t etait calcule ici sans jamais servir, et la cle de session etait lue
	// avant d'exister (avertissement "Undefined array key" a la premiere visite).
	if(!isset($_SESSION["CICAPSRV_SEARCH"]) || $_SESSION["CICAPSRV_SEARCH"]==null){
		$_SESSION["CICAPSRV_SEARCH"]="limit 200";
	}

    $html=$tpl->page_header("{SERVICE_WEBAVEX} {events}",ico_eye,"{ACTIVATE_ICAP_AV_TEXT}",null,"cicap-events",null,true);

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall("{APP_C_ICAP}");
        return;
    }
	
	echo $tpl->_ENGINE_parse_body($html);

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	// $t, $title, $target_file, $js et $class etaient calcules ici sans jamais
	// servir, et $GLOBALS["jsAfterEnc"] -- construit a partir de $_GET["eth"] --
	// n'etait relu NULLE PART.
	$zdate=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->javascript_parse_text("{events}");
	$showing_text=$tpl->_ENGINE_parse_body("{showing}");
	$nodata_text=$tpl->_ENGINE_parse_body("{no_data}");
	$sock=new sockets();

	$_SESSION["CICAPSRV_SEARCH"]=trim(strtolower((string)($_GET["search"] ?? "")));
	$search=$tpl->query_pattern($_SESSION["CICAPSRV_SEARCH"]);
    if(strlen($search["S"])<2){$search["S"]=".";}
	$ss=base64_encode($search["S"]);

	$html=array();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl awc-tbl-compact'><thead><tr>";
	$html[]="<th>$zdate</th>";
	$html[]="<th>$events</th>";
	$html[]="</tr></thead><tbody id='ce-rows'>";

    $EndPoint="/cicap/events/$ss";
    $data=$sock->REST_API($EndPoint);

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("Decoding: ".strlen($data)." bytes<hr>".htmlspecialchars($data).json_last_error_msg());
        return false;
    }
    // json_decode() rend NULL quand le daemon ne repond pas: lire ->Status
    // dessus emet un avertissement et la page reste vide sans explication.
    if(!is_object($json) || !isset($json->Status) || !$json->Status){
        echo $tpl->div_error(is_object($json) && isset($json->Error)?htmlspecialchars($json->Error):"{unknown_error}");
        return false;
    }
    $logs=(isset($json->Logs) && is_array($json->Logs))?$json->Logs:array();

    $c=0;
    foreach ($logs as $srcline){
		if(trim((string)$srcline)==""){continue;}
		if($GLOBALS['VERBOSE']){echo "FOUND $srcline\n";}

        list($datetext,$pid,$line)=cicap_parse_row($srcline);
        $line=str_replace(array("#012","#011"),"",$line);
		$c++;
		$html[]=cicap_event_row($datetext,$line);

	}
	if($c==0){
		$html[]="<tr><td colspan='2' class='awc-empty'>$nodata_text</td></tr>";
	}

	// L'ancienne version placait le pied A L'INTERIEUR du corps du tableau.
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'>";
	$html[]="<span>$showing_text $c</span>";
	$html[]="<span class='awc-pg' id='ce-pager'></span>";
	$html[]="</div></div></div>";

    $TINY_ARRAY["TITLE"]="{SERVICE_WEBAVEX} {events} &laquo;".htmlspecialchars($search["S"])."&raquo; $c {results}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{ACTIVATE_ICAP_AV_TEXT}";
    $TINY_ARRAY["URL"]="cicap-events";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	// awc_pager_js() APRES le tableau: les <tr> doivent exister dans le DOM.
	echo awc_table_style()
	    .@implode("\n", $html)
	    ."<script>$jstiny\nNoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>"
	    .awc_pager_js(50,"ce-rows","ce-pager");

}
/**
 * Un evenement du service c-icap.
 * L'ancienne version fermait la cellule du message par un </a> orphelin -- il
 * n'y avait aucun <a> a fermer -- et rendait la ligne de journal BRUTE.
 */
function cicap_event_row($datetext,$line):string{
    $class=cicap_event_class($line);
    // Pentest 2026-11-04 : ligne de journal c-icap (donnee externe) : htmlspecialchars UTF-8 rendait "" sur un octet Latin-1 (XSS stockee).
    $h ="<tr>";
    $h.="<td class='awc-nowrap awc-mono $class'>".req_html((string)$datetext)."</td>";
    $h.="<td class='$class'>".req_html((string)$line)."</td>";
    $h.="</tr>";
    return $h;
}

/**
 * Classe de couleur d'un evenement.
 * La derniere correspondance l'emporte, comme dans la cascade de conditions
 * qu'elle remplace.
 */
function cicap_event_class($line):string{
    static $rules=array(
        "#(does not exist|Error while|Can not|VIRUS DETECTED)#i"   =>"font-bold text-danger",
        "#(Setting antivirus default engine|success)#i"            =>"text-info",
        "#(erm signal received|Reloading|Restarting)#i"            =>"font-bold text-warning");
    $class="";
    foreach($rules as $re=>$c){
        if(preg_match($re,$line)){$class=$c;}
    }
    return $class;
}

/**
 * Decoupe une ligne syslog c-icap en (date, pid, message).
 * L'ancienne ParseRows() ne rendait RIEN quand aucun des deux motifs ne
 * correspondait: le list() cote appelant recevait null, ce qui emet trois
 * avertissements en PHP 8 et produit une ligne vide. Elle rend desormais la
 * ligne telle quelle, ce qui est toujours plus utile qu'une ligne blanche.
 * Le mois etait par ailleurs cherche dans la table sans verifier qu'il y est.
 */
function cicap_parse_row($line){
    $tpl=new template_admin();
    $months=array("Jan"=>"01","Feb"=>"02" ,"Mar"=>"03","Apr"=>"04", "May"=>"05","Jun"=>"06", "Jul"=>"07", "Aug"=>"08", "Sep"=>"09", "Oct"=>"10","Nov"=>"11", "Dec"=>"12");
    $line=trim((string)$line);

    $patterns=array(
        "#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?\[([0-9]+)\]: (.+)#",
        "#^(.+?)\s+([0-9]+)\s+([0-9:]+).*?c-icap:\s+([0-9]+)\/[0-9]+, (.+)#");

    foreach($patterns as $re){
        if(!preg_match($re,$line,$m)){continue;}
        $month=$months[$m[1]] ?? null;
        if($month===null){continue;}
        $date=strtotime(date("Y")."-$month-{$m[2]} {$m[3]}");
        return array($tpl->time_to_date($date,true),$m[4],$m[5]);
    }

    return array("",0,$line);
}
