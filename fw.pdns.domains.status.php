<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-id"])){item_id();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["xkind"])){item_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){item_delete();exit;}


js();

function js(){
	$page=CurrentPageName();
	$domain_id=intval($_GET["domain_id"]);
	$tpl=new template_admin();
	$q=new mysql_pdns();
	$domainame=$q->GetDomainName($domain_id);
	$tpl->js_dialog5("$domainame: {status}", "$page?popup=yes&domain_id=$domain_id",900);
	
}


function popup(){
	$domain_id=intval($_GET["domain_id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();	
	$html="<div id='div-dns-status-data'></div><script> LoadAjax('div-dns-status-data','$page?table=yes&domain_id=$domain_id');</script>";
	echo $html;
}

function table(){
	$domain_id=intval($_GET["domain_id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	
	$html[]="<table id='table-dns-status-data' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{level}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{domain}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{info}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$sql="SELECT * FROM pdnsutil_chkzones";
	if($domain_id>0){$sql="SELECT * FROM pdnsutil_chkzones WHERE domain_id=$domain_id";}
	$q=new mysql_pdns();
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html());}
	
	$error_type[1]="danger";
	$error_type[0]="warning";
	
	$error_typet[1]="{warning}";
	$error_typet[0]="{info}";
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zerror=$error_type[$ligne["error_type"]];
		$content=$ligne["content"];
		$id=$ligne["id"];
		$md=md5(serialize($ligne));
		$domain_id=$ligne["domain_id"];
		$domainname=$q->GetDomainName($domain_id);
		
		$js="Loadjs('fw.dns.domain.php?domain-id=$domain_id');";
		$domainname=$tpl->td_href($domainname,null,$js);

		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1%><span class='label label-$zerror'>{$error_typet[$ligne["error_type"]]}</span></td>";
		$html[]="<td width=1% nowrap>$domainname</td>";
		$html[]="<td width=99%>$content</td>";
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
	$(document).ready(function() { $('#table-dns-status-data').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}