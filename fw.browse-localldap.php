<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
// Kit de tables awc_* + barre de recherche. Inclusion INCONDITIONNELLE : dans le
// bootstrap if(!class_exists("sockets")) elle ne s'executerait jamais, et la page
// mourrait sur "Call to undefined function awc_table_style()" -- un spinner infini.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Pentest 2026-11-04 : field-id etait recopie sans encodage dans la page (XSS). C'est un id de DOM.
if(isset($_GET["field-id"])){$_GET["field-id"]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["field-id"]);}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["search"])){search();exit;}

js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	
	$title=$tpl->javascript_parse_text("{APP_OPENLDAP} {groups2}");
	$tpl->js_dialog7($title, "$page?content=yes&field-id={$_GET["field-id"]}",650);
}
function content(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new template_admin();
// La barre est enveloppee dans .siem-pick : search_block_css() fixe
	// ".sb-stitch .input-group{width:800px}", dimensionne pour une pleine page. Dans
	// cette modale de 650px le bouton Go! sortirait du cadre. siem_pick_style() rend
	// la largeur fluide, par une classe et non en elargissant la feuille partagee.
	$html ="<div class='siem-pick'>";
	$html.=awc_search_bar("search-this-$t");
	$html.="</div>";
	$html.="<div id='search-$t'></div>";
	$html.="
	<script>
		document.getElementById('search-this-$t').value='".addslashes((string)($_SESSION["AD_SEARCH"] ?? ""))."';
		document.getElementById('search-this-$t').onkeypress=function(e){ if(checkEnter(e)){ ss$t(); } };
		document.getElementById('search-this-$t-go').onclick=function(){ ss$t(); };
		
		function ss$t(){
			var ss=encodeURIComponent(document.getElementById('search-this-$t').value);
			LoadAjax('search-$t','$page?search='+ss+'&field-id={$_GET["field-id"]}');
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
	$ldap=new clladp();
    $array=$ldap->UserAndGroupSearch(null,$search,300,true);


	$t=time();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'>";
	$html[]="<thead><tr>";
	$html[]="<th>{groupname}</th>";
	$html[]="<th style='text-align:right'>{select}</th>";
	$html[]="</tr></thead>";
	// L'id du <tbody> ancre awc_pager_js() : sans lui le pager rend vide et toutes
	// les lignes s'affichent d'un bloc, sans que rien ne le signale.
	$html[]="<tbody id='llg-rows-$t'>";
	$TRCLASS=null;
	$c=0;

	$Max=$array["count"];

	for($i=0;$i<$Max;$i++){
		$c++;
	    $main=$array[$i];
        $memberuids=$main["memberuid"]["count"];
	    $description=$main["description"][0];
	    $groupname=$main["cn"][0];
	    if(!isset($main["gidnumber"])){continue;}
	    $gidnumber=$main["gidnumber"][0];

		$zmd5=md5(serialize($main));
        $select=$tpl->icon_select("SelectThis$t('$gidnumber')");
		$meta="$memberuids {members}";
		if($description<>null){$meta=$meta." &middot; ".htmlspecialchars($description);}
		$h ="<tr id='tr-$zmd5'>";
		$h.="<td><div class='awc-ident'>";
		$h.="<span class='awc-ico'><i class='fas fa-users'></i></span>";
		$h.="<span><span class='awc-name'>".htmlspecialchars((string)$groupname)."</span>";
		$h.="<div class='awc-meta'>$meta</div>";
		$h.="</span></div></td>";
		$h.="<td><div class='awc-acts'>$select</div></td>";
		$h.="</tr>";
		$html[]=$h;
		
		
	}
	if($c==0){
		$html[]="<tr><td colspan='2'><div class='awc-empty'>{no_data}</div></td></tr>";
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>$c {groups2}</span>";
	$html[]="<span class='awc-pg' id='llg-pager-$t'></span></div>";
	$html[]="</div></div>";
	// ICON_SCRIPTS_COMPILE() et non $tpl->ICON_SCRIPTS : la propriete d'instance se
	// perd des qu'une ligne est batie par une autre instance, et l'icone repondrait
	// alors a aucun clic, sans la moindre erreur PHP.
	$html[]="
	<script>
	NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."
	
	function SelectThis$t(name){
		if(!document.getElementById('$field_user') ){
			alert('$field_user no such id');
			return;
		}
		document.getElementById('$field_user').value=name;
		dialogInstance7.close();
	
	}
	
	
	</script>";
	
	// Feuille et pager concatenes AUTOUR du parsing : la regex #{(.+?)}# de
	// _ENGINE_parse_body lit chaque corps de regle CSS et chaque bloc JS comme un jeton.
	echo awc_table_style().$tpl->_ENGINE_parse_body($html).awc_pager_js(25,"llg-rows-$t","llg-pager-$t");

}