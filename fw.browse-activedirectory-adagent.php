<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
// Kit de tables awc_*. Inclusion INCONDITIONNELLE : dans le bootstrap
// if(!class_exists("sockets")) elle ne s'executerait jamais, et la page mourrait
// sur "Call to undefined function awc_table_style()" -- un spinner infini.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__).'/ressources/class.ad-agent.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Pentest 2026-11-04 : field-id etait recopie sans encodage dans la page (XSS) ; c'est un id DOM (md5 dans field_browse_*).
if(isset($_GET["field-id"])){$_GET["field-id"]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["field-id"]);}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["search"])){search();exit;}

js();


function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $addon=null;
    $title=$tpl->javascript_parse_text("Active Directory {groups2}");
    if(isset($_GET["bydn"])){$addon="&bydn=yes";}

    $tpl->js_dialog6($title, "$page?content=yes&field-id={$_GET["field-id"]}$addon",650);
}
function content(){
    $t=time();
    $page=CurrentPageName();
    $tpl=new template_admin();
    $addon=null;
    if(isset($_GET["bydn"])){$addon="&bydn=yes";}
    // Enveloppee dans .siem-pick : search_block_css() fixe
	// ".sb-stitch .input-group{width:800px}", dimensionne pour une pleine page.
	// Dans cette modale le bouton Go! sortirait du cadre ; siem_pick_style() rend
	// la largeur fluide, par une classe et non en elargissant la feuille partagee.
	$html ="<div class='siem-pick'>";
	$html.=awc_search_bar("search-this-$t");
	$html.="</div>";
	$html.="<div id='search-$t'></div>";
	$html.="
	<script>
		document.getElementById('search-this-$t').value='".req_js((string)($_SESSION["AD_SEARCH"] ?? ""))."';
		document.getElementById('search-this-$t').onkeypress=function(e){ if(checkEnter(e)){ ss$t(); } };
		document.getElementById('search-this-$t-go').onclick=function(){ ss$t(); };
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('search-$t','$page?search='+ss+'&field-id={$_GET["field-id"]}$addon');
		}
		
		function Start$t(){
			var ss=document.getElementById('search-this-$t').value;
			ss$t();
		}
		Start$t();
	</script>    
    
    
    ";
    echo siem_pick_style().$tpl->_ENGINE_parse_body($html);

}
function search(){
    $search="*{$_GET["search"]}*";
    $search=str_replace("**", "*", $search);
    $search=str_replace("**", "*", $search);
    $field_user=$_GET["field-id"];
    $tpl=new template_admin();
    $adagent=new ADAgent();
    $adagentaclgroups=$adagent->getAllGroups("$search");

    $array = json_decode($adagentaclgroups, TRUE);
    if(!is_array($array)){$array=array();}
    $t=time();$BYDN=false;
    $groupname=$tpl->_ENGINE_parse_body("{groupname}");
    $domain=$tpl->_ENGINE_parse_body("{domain}");
    $items=$tpl->javascript_parse_text("{items}");
    if(count($array)==0 || $array[0]["reason"]=="Not found"){

        $servers="Active Directory Agent API's";
        $html[]="<table id='table-wizardnew-objects' class=\"table table-hover\" data-page-size=\"100\" data-paging=\"true\">";
        $html[]="<thead>";
        $html[]="<tr>";
        $html[]="<th>$groupname</th>";
        $html[]="<th>&nbsp;</th>";
        $html[]="<th></th>";
        $html[]="</tr>";
        $html[]="</thead>";
        // L'id du <tbody> ancre awc_pager_js() : sans lui le pager rend vide et
	// toutes les lignes s'affichent d'un bloc, sans que rien ne le signale.
	$html[]="<tbody id='bada-rows'>";
        $html[]="<tr>";
        // Pentest 2026-11-04 : search etait recopie sans encodage dans la page (XSS).
        $html[]="<td colspan='4'>{your_search_results} <strong>&laquo;".req_html($search)."&raquo;</strong>{$servers}: <span class='text-danger'>{no_data}</span></td>";
        $html[]="</tr>";
        $html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>$c {groups2}</span>";
	$html[]="<span class='awc-pg' id='bada-pager'></span></div>";
	$html[]="</div></div>";

        // Feuille et pager concatenes AUTOUR du parsing : la regex de
        // _ENGINE_parse_body lit chaque regle CSS et chaque bloc JS comme un jeton.
        echo awc_table_style().$tpl->_ENGINE_parse_body($html).awc_pager_js(25,"bada-rows","bada-pager");
        return;
    }
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>$groupname</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $c=0;
    if(isset($_GET["bydn"])){$BYDN=true;}
    ksort($array);
    foreach ($array as $k => $v){
        $c++;
        $color="black";
        $zmd5=md5($v["data"]["distinguishedName"]);
        $DN_enc=urlencode($v["data"]["distinguishedName"]);
        // Pentest 2026-11-04 : distinguishedName/sAMAccountName (donnee externe AD) affiches sans encodage (XSS stockee).
        $description="<br><i>".req_html($v["data"]["distinguishedName"])."</i>";
        $select_value=$groupname;
        $html[]="<tr id='tr-$zmd5'>";
        if($BYDN){$select_value=$v["data"]["distinguishedName"];}
        $select=$tpl->icon_select("SelectThis$t('".req_js($select_value)."')");

        $html[]="<td><span style='font-weight:bold'>".req_html($v["data"]["sAMAccountName"])."</span>$description</td>";
        $html[]="<td class=center><center>$select</center></td>";
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
    $html[]="
	<script>
	NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."
	
	function SelectThis$t(name){
		if(!document.getElementById('$field_user') ){
			alert('$field_user no such id');
			return;
		}
		document.getElementById('$field_user').value=name;
		dialogInstance6.close();
	
	}
	
	
	</script>";

    echo @implode("\n", $html);

}