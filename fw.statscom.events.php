<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
// Pentest 2026-11-04 : t/eth etaient recopies sans encodage dans la page (XSS).
if(isset($_GET["t"])){$_GET["t"]=intval($_GET["t"]);}
if(isset($_GET["eth"])){$_GET["eth"]=preg_replace('/[^A-Za-z0-9_.:@-]/','',(string)$_GET["eth"]);}
// Les repartiteurs ?enable-signature= et ?enable-firewall= appelaient
// enable_signature() et enable_firewall(), qui ne sont definies NI dans ce
// fichier NI ailleurs: les deux URL repondaient un fatal « Call to undefined
// function ». La paire appartient a la famille Suricata (elle bascule une
// signature dans suricata-rules.db et un drapeau dans suricata_sig) et n'a
// rien a faire ici; seul fw.ids.rules.php produit reellement
// « ?enable-signature= ».
// Repartiteur(s) mort(s) retire(s): rule_settings()/rule_save() ne sont
// definies NI dans ce fichier NI ailleurs, et aucun code du depot ne produit
// le parametre qui les declenchait -- ni cette page, ni une autre. Les URL
// repondaient donc un fatal « Call to undefined function ». Vestige d'un
// squelette de page recopie; les pages qui utilisent VRAIMENT ?rule-popup=
// ou ?rule-settings= definissent leur propre handler.
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
page();



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// Pentest 2026-11-04 : search (garde en session) etait recopie sans encodage dans la page (XSS).
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["STATSREDIS_SEARCH"]==null){$_SESSION["STATSREDIS_SEARCH"]="limit 200";}
	
	$html[]="
	<div class=\"row\"> 
		<div class='ibox-content'>
		<div class=\"input-group\">
      		<input type=\"text\" class=\"form-control\" value=\"".req_html($_SESSION["STATSREDIS_SEARCH"] ?? "")."\" placeholder=\"{search}\" id='search-this-$t' OnKeyPress=\"Search$t(event);\">
      		<span class=\"input-group-btn\">
       		 <button style=\"text-transform: capitalize;\" class=\"btn btn-default\" type=\"button\" OnClick=\"ss$t();\">Go!</button>
      	</span>
     </div>
    </div>
</div>	
	
	
	
		
	<div class='row'><div id='progress-firehol-restart'></div>";

	$html[]="<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
		
		
		
<script>
		function Search$t(e){
			if(!checkEnter(e) ){return;}
			ss$t();
		}
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('table-loader','$page?table=yes&t=$t&search='+ss);
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>";

	
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
	$zdate=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->javascript_parse_text("{events}");
	$target_file=PROGRESS_DIR."/statsredis.log";
	
	$js="OnClick=\"javascript:LoadAjax('table-loader','$page?table=yes&eth=');\"";
	if($_GET["eth"]==null){$class=" active";}
	
	$t=time();
	

	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";

	
	$_SESSION["STATSREDIS_SEARCH"]=trim(strtolower($_GET["search"]));
	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])));
	$ss=urlencode($search["S"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("statscom.php?searchlogs=$ss&rp={$search["MAX"]}");
	$datas=explode("\n",@file_get_contents($target_file));


	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$zdate</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$events</th>";
	
	
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	

	$STATICO["ERROR"]="<span class='label label-danger'>ERROR</span>";
    $STATICO["LOG"]=               "<span class='label'>INFO.</span>";
    $STATICO["WARNING"]=           "<span class='label label-warning'>WARN.</span>";
    $STATICO["HINT"]=              "<span class='label label-info'>HINT.</span>";
    $STATICO["STATEMENT"]=         "<span class='label label-info'>STAT.</span>";
    $STATICO["FATAL"]="<span class='label label-danger'>ERROR</span>";
	
	$TRCLASS=null;
	krsort($datas);

	$td1prc=$tpl->table_td1prc();

	foreach ($datas as $key=>$line){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		if(trim($line)==null){continue;}
		if($GLOBALS['VERBOSE']){echo "FOUND ".req_html($line)."\n";}

        if(!preg_match("#^([0-9]+):([A-Z]+)\s+(.+?)\.[0-9]+\s+.*?\s+(.+)#",$line,$re)){continue;}

        // Pentest 2026-11-04 : ligne de journal PostgreSQL (donnee externe) affiche sans encodage (XSS stockee).
        $datetext=req_html($re[3]);

		$pid=$re[1];
		$status=$re[2];
		$line=req_html($re[4]);

		if(preg_match("#(does not exist|Error while|Can not)#i", $line)){
			$text_class="text-danger";
		}
		
		if(preg_match("#Setting antivirus default engine#i", $line)){
			$text_class="text-info";
		}
		if(preg_match("#VIRUS DETECTED#i", $line)){
			$text_class="text-warning";
		}

		if(preg_match("#incomplete startup packet#",$line)){
		    $line="<span style='color:#CCCCCC'>$line</span>";
        }

	    $html[]="<tr class='$TRCLASS'>";
		$html[]="<td $td1prc>$datetext</td>";
		$html[]="<td class=\"$text_class\">$line</a></td>";
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
	$html[]="</table><div><i></i></div>";
	$html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable(
{
\"filtering\": {
\"enabled\": false
},
\"sorting\": {
				\"enabled\": true
			}

			}
		

	); });
</script>";

			echo $tpl->_ENGINE_parse_body($html);

}
