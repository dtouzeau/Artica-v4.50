<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
// Kit de tables awc_*. Inclusion INCONDITIONNELLE : dans le bootstrap
// if(!class_exists("sockets")) elle ne s'executerait jamais, et la page mourrait
// sur "Call to undefined function awc_table_style()" -- un spinner infini.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Pentest 2026-11-04 : field-id etait recopie sans encodage dans la page (XSS).
if(isset($_GET["field-id"])){$_GET["field-id"]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["field-id"]);}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["search"])){search();exit;}

js();


function js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	
	$title=$tpl->javascript_parse_text("{APP_OPENLDAP} {members}");
	$tpl->js_dialog7($title, "$page?content=yes&field-id={$_GET["field-id"]}",650);
}
function content(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new template_admin();
// Enveloppee dans .siem-pick : search_block_css() fixe
	// ".sb-stitch .input-group{width:800px}", dimensionne pour une pleine page. Dans
	// cette modale le bouton Go! sortirait du cadre. siem_pick_style() rend la
	// largeur fluide, par une classe et non en elargissant la feuille partagee.
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
	echo $tpl->_ENGINE_parse_body($html);
	
}
function search(){
	$search="*{$_GET["search"]}*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);
	$field_user=$_GET["field-id"];
	$tpl=new template_admin();
	$ldap=new clladp();
    $array = $ldap->UserAndGroupSearch(null, $search, 100,false,true);



	$t=time();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th style='width:1%'>&nbsp;</th>";
	$html[]="<th>{members}</th>";
	$html[]="<th>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	// L'id du <tbody> ancre awc_pager_js() : sans lui le pager rend vide et
	// toutes les lignes s'affichent d'un bloc, sans que rien ne le signale.
	$html[]="<tbody id='blu-rows'>";
	$TRCLASS=null;
	$c=0;

	$Max=$array["count"];

	for($i=0;$i<$Max;$i++){
		$c++;
	    $main=$array[$i];
        $displayname=$main["displayname"][0];
        $givenname=$main["givenname"][0];
        $sn=$main["sn"][0];
	    $dn=$main["dn"];
        $uid=$main["uid"][0];
        $disp="$givenname $sn <small>($displayname - $uid)</small>";

		$zmd5=md5(serialize($main));
        // Pentest 2026-11-04 : le DN (contenu LDAP) etait recopie sans encodage dans la page (XSS).
        $select=$tpl->icon_select("SelectThis$t('".req_js($dn)."')");
        $html[]="<tr id='tr-$zmd5'>";
        $html[]="<td class=center style='width:1%'><i class='fas fa-user-circle'></i></td>";
		$disp=req_html("$givenname $sn")." <small>(".req_html("$displayname - $uid").")</small>";
		$html[]="<td style='width:99%'><span style='font-weight:bold'>$disp</td>";
		$html[]="<td class=center style='width:1%'>$select</td>";
		$html[]="</tr>";
		
		
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>$c {users}</span>";
	$html[]="<span class='awc-pg' id='blu-pager'></span></div>";
	$html[]="</div></div>";
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
	
	// Feuille et pager concatenes AUTOUR du parsing : la regex de
	// _ENGINE_parse_body lit chaque regle CSS et chaque bloc JS comme un jeton.
	echo awc_table_style().$tpl->_ENGINE_parse_body($html).awc_pager_js(25,"blu-rows","blu-pager");
	
}