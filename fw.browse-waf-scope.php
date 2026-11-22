<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// Kit de tables awc_*. Inclusion INCONDITIONNELLE : dans le bootstrap
// if(!class_exists("sockets")) elle ne s'executerait jamais, et la page mourrait
// sur "Call to undefined function awc_table_style()" -- un spinner infini.
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
// Pentest 2026-11-04 : field-id etait recopie sans encodage dans la page (XSS).
if(isset($_GET["field-id"])){$_GET["field-id"]=preg_replace('/[^A-Za-z0-9_-]/','',(string)$_GET["field-id"]);}
if(isset($_GET["popup"])){popup();exit;}
js();

function js(){
	$page=CurrentPageName();
	// Pentest 2026-11-04 : title/field-id etaient recopies sans encodage dans la page (XSS).
	// Le titre est decode deux fois (html_entity_decode) avant d'etre rendu en HTML : on retire
	// les caracteres actifs plutot que de les encoder.
	$title=preg_replace('/[<>"\'`\\\\&]/','',(string)($_GET["title"] ?? ""));
	$field_id=$_GET["field-id"];
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("$title: {scope}");
	$tpl->js_dialog9($title, "$page?popup=yes&field-id=$field_id");
	
	
}

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$t=time();

    $BROWSE_VARIABLES_POPUP=BROWSE_VARIABLES_POPUP();
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
	$html[]="<table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th>{key}</th>";
	$html[]="<th>{select}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	// L'id du <tbody> ancre awc_pager_js() : sans lui le pager rend vide et
	// toutes les lignes s'affichent d'un bloc, sans que rien ne le signale.
	$html[]="<tbody id='bws-rows'>";
    $TRCLASS=null;
    foreach ($BROWSE_VARIABLES_POPUP as $key=>$explain){
		$md=md5($key);
		$js="document.getElementById('{$_GET["field-id"]}').value='$key';dialogInstance9.close();";
		$header=$tpl->td_href($key,$explain,$js);
		$html[]="<tr id='$md'>";
		$html[]="<td style='width:99%'><strong>$header</strong><br><small>$explain</small></td>";
		$html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_select($js,"AsWebMaster")."</center></td>";
		$html[]="</tr>";
	}
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>$c {items}</span>";
	$html[]="<span class='awc-pg' id='bws-pager'></span></div>";
	$html[]="</div></div>";
	$html[]="
	<script>
	NoSpinner();\n".$tpl->ICON_SCRIPTS_COMPILE()."
	</script>";
	
	// Feuille et pager concatenes AUTOUR du parsing : la regex de
	// _ENGINE_parse_body lit chaque regle CSS et chaque bloc JS comme un jeton.
	echo awc_table_style().$tpl->_ENGINE_parse_body(@implode("\n", $html)).awc_pager_js(25,"bws-rows","bws-pager");
	
	
}
function BROWSE_VARIABLES_POPUP(){
//https://github.com/SpiderLabs/ModSecurity/wiki/Reference-Manual-(v2.x)#request_uri
    $VARIABLES["QUERY_STRING"]="{QUERY_STRING_MODEC}";
    $VARIABLES["REMOTE_ADDR"]="{REMOTE_ADDR_MODSEC}";
    $VARIABLES["REQUEST_FILENAME"]="{REQUEST_FILENAME_MODSEC}";
    $VARIABLES["FILES"]="{FILES_MODSEC}";
    $VARIABLES["ARGS"]="{ARGS_MODSEC}";
    $VARIABLES["REQUEST_HEADERS"]="{REQUEST_HEADERS_MODSEC}";
    $VARIABLES["REQUEST_HEADERS_USERAGENT"]="{REQUEST_HEADERS_USERAGENT_MODSEC}";
    $VARIABLES["REQUEST_PROTO_GET"]="{REQUEST_METHOD_MODSEC} <strong>GET</strong>";
    $VARIABLES["REQUEST_PROTO_HEAD"]="{REQUEST_METHOD_MODSEC} <strong>HEAD</strong>";
    $VARIABLES["REQUEST_PROTO_POST"]="{REQUEST_METHOD_MODSEC} <strong>POST</strong>";
    $VARIABLES["REQUEST_PROTO_PUT"]="{REQUEST_METHOD_MODSEC} <strong>PUT</strong>";
    $VARIABLES["REQUEST_PROTO_DELETE"]="{REQUEST_METHOD_MODSEC} <strong>DELETE</strong>";
    $VARIABLES["REQUEST_PROTO_TRACE"]="{REQUEST_METHOD_MODSEC} <strong>TRACE</strong>";
    $VARIABLES["REQUEST_PROTO_CONNECT"]="{REQUEST_METHOD_MODSEC} <strong>CONNECT</strong>";
    return $VARIABLES;

}

