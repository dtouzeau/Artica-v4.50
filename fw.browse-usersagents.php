<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["operation"])){operation();exit;}
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
    $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $field_id = $_GET["field-id"];
    $t = time();

    echo $tpl->search_block($page, null, null, null, "&field-id=$field_id&ModeRegex={$_GET["ModeRegex"]}");

}
function search(){
    $tpl = new template_admin();
    $page = CurrentPageName();
    $TRCLASS=null;
    $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $field_id = $_GET["field-id"];
    $t = time();
	$results=$q->QUERY_SQL("SELECT * FROM UserAgents ORDER BY source LIMIT 100");

	
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{http_user_agent}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' width=1%>{select}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

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
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=99% nowrap><strong>$source_text</strong></td>";
		$html[]="<td width=1% class='center' nowrap>". $tpl->icon_select($js,"AsProxyMonitor")."</center></td>";
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
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
	
}


