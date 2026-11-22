<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.tasks.inc");
// Repartiteur(s) mort(s) retire(s): rule_settings()/rule_save() ne sont
// definies NI dans ce fichier NI ailleurs, et aucun code du depot ne produit
// le parametre qui les declenchait -- ni cette page, ni une autre. Les URL
// repondaient donc un fatal « Call to undefined function ». Vestige d'un
// squelette de page recopie; les pages qui utilisent VRAIMENT ?rule-popup=
// ou ?rule-settings= definissent leur propre handler.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


// Pentest 2026-11-04 E-02/M-02 : TaskType est un entier, partout.
$_GET["TaskType"]=intval($_GET["TaskType"] ?? 0);
// Pentest 2026-11-04 : ShowID/filename/line/eth/t etaient recopies sans encodage dans la page (XSS).
foreach(array("ShowID","ShowID-js","line") as $k){
    if(isset($_GET[$k])){$_GET[$k]=intval($_GET[$k]);}
}
if(isset($_GET["filename"])){$_GET["filename"]=preg_replace('#[^A-Za-z0-9_./-]#','',(string)$_GET["filename"]);}
if(isset($_GET["eth"])){$_GET["eth"]=preg_replace('/[^A-Za-z0-9_.:@-]/','',(string)$_GET["eth"]);}
if(isset($_GET["t"])){$_GET["t"]=intval($_GET["t"]);}
// Pentest 2026-11-04 : function finit dans du JavaScript (nom de fonction JS uniquement).
if(isset($_GET["function"])){$_GET["function"]=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"]);}
if(isset($_GET["table"])){table();exit;}
page();

// ShowID/empty/remove-events retires (2026-11-11) : ils lisaient, vidaient (DROP
// TABLE) ou purgeaient squid_admin_mysql de system_events.db en connexion
// directe, alors que cette page affiche schedule_events.db et ne les appelle
// jamais. Restes d'une copie de la page des evenements systeme.

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["SYSEVS_SEARCH"]==null){$_SESSION["SYSEVS_SEARCH"]="limit 200";}
	
	$html[]="<div class='row'><div class='ibox-content'>";
	$html[]=$tpl->search_block($page,"sqlite:/home/artica/SQLITE/schedule_events.db","events","table-loader","&table=yes&TaskType={$_GET["TaskType"]}");
	$html[]="<div id='table-loader'></div>
	</div>
	</div>
		
		
		
<script>
		Start$t();
	</script>";
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,@implode("\n", $html));
        echo $tpl->build_firewall();
        return;
    }
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$eth_sql=null;
	$token=null;
	$class=null;

	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$date=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->javascript_parse_text("{events}");


	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";


	$search=trim((string)($_GET["search"] ?? ""));
	$_SESSION["SYSEVS_SEARCH"]=strtolower($search);

	// Lecture par le daemon (SystemTasks, schedule_events.db) : il cree la base
	// si elle manque et lie la recherche en parametre. Plus de lib_sqlite ici.
	$TaskType=intval($_GET["TaskType"]);
	$json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/tasks/events?type=$TaskType&limit=500&search=".urlencode($search)));
	$results=array();
	$rest_error="";
	if(!$json || !property_exists($json,"success") || !$json->success){
		$rest_error=($json && property_exists($json,"error"))?(string)$json->error:"{error} articarest";
	}elseif(is_array($json->data)){
		foreach($json->data as $ev){
			$results[]=array("ID"=>intval($ev->ID),"severity"=>intval($ev->severity),"zdate"=>intval($ev->zdate),"subject"=>(string)$ev->subject);
		}
	}

	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$date</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$events</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	

	
	if($rest_error<>""){
		echo "<div class='alert alert-danger'>".htmlspecialchars($rest_error,ENT_QUOTES)."</div>";
	}
	
	$severityCL[0]="label-danger";
	$severityCL[1]="label-warning";
	$severityCL[2]="label-primary";
	
	$severityTX[0]="text-danger";
	$severityTX[1]="text-warning";
	$severityTX[2]="text-primary";

	
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$id=md5(serialize($ligne));
		$zdate=$tpl->time_to_date($ligne["zdate"],true);
		$subject=$ligne["subject"];
		if(preg_match("#success#i",$subject)){$ligne["severity"]=2;}
		$severity_class=$severityCL[$ligne["severity"]];

		$html[]="<tr class='$TRCLASS' id='$id'>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap><div class='label $severity_class' style='font-size:13px;padding:10px;width:100%' >$zdate</a></div></td>";
		// Pentest 2026-11-04 : sujet de l'evenement (donnee externe) affiche sans encodage (XSS stockee).
		$html[]="<td class=\"$text_class\">".html_safe_fragment($subject)."</td>";
		$html[]="</tr>";
		

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $title="{events}";
    $explain="{system_events}";
    if(intval($_GET["TaskType"])>0){
        $tasks=new system_tasks();
        $title="{events}: ".$tpl->_ENGINE_parse_body($tasks->tasks_array[$_GET["TaskType"] ] );
        $explain=$tpl->_ENGINE_parse_body($tasks->tasks_explain_array[$_GET["TaskType"] ] );
    }

    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]=$explain;
    $TINY_ARRAY["URL"]="";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
$jstiny
</script>";

			echo $tpl->_ENGINE_parse_body($html);

}
