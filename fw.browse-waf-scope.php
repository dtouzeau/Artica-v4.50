<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["operation"])){operation();exit;}
js();

function js(){
	$page=CurrentPageName();
	$title=$_GET["title"];
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
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{key}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' style='width:1%'>{select}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
    $TRCLASS=null;
    foreach ($BROWSE_VARIABLES_POPUP as $key=>$explain){
		$md=md5($key);
		$js="document.getElementById('{$_GET["field-id"]}').value='$key';dialogInstance9.close();";
		$header=$tpl->td_href($key,$explain,$js);
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:99%'><strong>$header</strong><br><small>$explain</small></td>";
		$html[]="<td style='width:1%' class='center' nowrap>". $tpl->icon_select($js,"AsWebMaster")."</center></td>";
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
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
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

