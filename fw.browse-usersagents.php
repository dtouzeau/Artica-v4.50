<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// Kit de tables awc_*. Inclusion INCONDITIONNELLE : dans le bootstrap
// if(!class_exists("sockets")) elle ne s'executerait jamais, et la page mourrait
// sur "Call to undefined function awc_table_style()" -- un spinner infini.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : field-id/ModeRegex/title etaient recopies sans encodage dans la page (XSS).
// field-id est un id DOM (md5 dans field_browse_UserAgent), ModeRegex un entier ; title va dans un titre de modale
// rendu en HTML apres decodage des entites : on retire les caracteres de balisage.
if(isset($_GET["field-id"])){$_GET["field-id"]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["field-id"]);}
if(isset($_GET["ModeRegex"])){$_GET["ModeRegex"]=intval($_GET["ModeRegex"]);}
if(isset($_GET["title"])){$_GET["title"]=preg_replace('/[<>&\\\\]/','',(string)$_GET["title"]);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search"])){search();exit;}
js();

function js(){
	$page=CurrentPageName();
	$title=$_GET["title"];
	$field_id=$_GET["field-id"];
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("$title");
	$tpl->js_dialog9($title, "$page?popup=yes&field-id=$field_id&ModeRegex={$_GET["ModeRegex"]}",750);
	
	
}

function popup()
{
    $tpl = new template_admin();
    $page = CurrentPageName();
    $field_id = $_GET["field-id"];
    $t = time();

    echo $tpl->search_block($page, null, null, null, "&field-id=$field_id&ModeRegex={$_GET["ModeRegex"]}");

}
function search(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $TRCLASS=null;
    $field_id = $_GET["field-id"];
    $t = time();
    $ua_list = acls_api_get("/proxy/useragents");
    $results = is_array($ua_list) ? array_slice($ua_list, 0, 100) : [];

	
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th>{http_user_agent}</th>";
    $html[]="<th>{select}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	// L'id du <tbody> ancre awc_pager_js() : sans lui le pager rend vide et
	// toutes les lignes s'affichent d'un bloc, sans que rien ne le signale.
	$html[]="<tbody id='bua-rows'>";

foreach ($results as $index=>$ligne){
		$md=md5(serialize($ligne));
		

		$source=trim($ligne["source"]);
		if($source==null){continue;}
		$regex=$ligne["regex"];
        $source_text=$source;
		$len=strlen($source);
		if($len>70){
		    $source_text=substr($source,0,67)."...";
        }
    $js="document.getElementById('{$_GET["field-id"]}').value='$source';dialogInstance9.close();";
    $source_text=$tpl->td_href($source_text,$source);
		$html[]="<tr id='$md'>";
		$html[]="<td width=99% nowrap><strong>$source_text</strong></td>";
		$html[]="<td width=1% class='center' nowrap>". $tpl->icon_select($js,"AsProxyMonitor")."</center></td>";
		$html[]="</tr>";
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>$c {items}</span>";
	$html[]="<span class='awc-pg' id='bua-pager'></span></div>";
	$html[]="</div></div>";
	$html[]="
	<script>
	NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."
	</script>";
	
	// Feuille et pager concatenes AUTOUR du parsing : la regex de
	// _ENGINE_parse_body lit chaque regle CSS et chaque bloc JS comme un jeton.
	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n", $html)).awc_pager_js(25,"bua-rows","bua-pager");
	
	
}


