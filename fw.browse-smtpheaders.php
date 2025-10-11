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
	$title=$tpl->javascript_parse_text("$title: {smtp_headers}");
	$tpl->js_dialog9($title, "$page?popup=yes&field-id=$field_id");
	
	
}

function popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$t=time();
	
	$f=explode("\n",@file_get_contents("ressources/databases/smtp_headers.db"));
	foreach ($f as $line){
		
	if(!preg_match("#^(.+?)Mail\s+(.+?)$#", $line,$re)){continue;}
		$Header=trim($re[1]);
		$content=trim($re[2]);
		$MAIN[$Header]=$content;
	}
	ksort($MAIN);
	
	
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{header}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1%>{select}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	foreach ($MAIN as $header=>$explain){
		$md=md5($header);
		
		$js="document.getElementById('{$_GET["field-id"]}').value='$header';dialogInstance9.close();";
		
		$header=$tpl->td_href($header,$explain,$js);
		
		
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=99% nowrap><strong>$header</strong>";
		$html[]="<td width=1% class='center' nowrap>". $tpl->icon_select($js,"AsPostfixAdministrator")."</center></td>";
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


