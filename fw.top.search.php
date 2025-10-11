<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["results"])){results();exit;}
if(isset($_GET["results-domain-dns"])){results_dns_domains_search();exit;}

search();


function search(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$search=trim(url_decode_special_tool($_GET["search"]));

	
	if(preg_match("#^(domain|domaine)(\s+|:|=)(.+)#i", $search,$re)){
		VERBOSE("results_dns_domains_search($re[3])",__LINE__);
		results_dns_domains_search($tpl->CLEAN_BAD_CHARSNET($re[3]));
		return;
	}

	$search=$tpl->CLEAN_BAD_XSS($search);
	VERBOSE($search,__LINE__);
	$md5=md5($search);
	$sock=new sockets();
	$sock->SET_INFO("FWTOPSEARCH", $search);
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/fw.search.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/fw.search.txt";
	$ARRAY["CMD"]="services.php?search=yes";
	$ARRAY["TITLE"]="$search";
	$ARRAY["AFTER"]="LoadAjax('MainContent','$page?results=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-$md5-restart')";
	
	
	echo "<div id='progress-$md5-restart' style='background-color:white' class='row border-bottom white-bg dashboard-header'></div>
	<script>$jsrestart</script>
	
	";
	
	
}


function results(){
	
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FwSearchResults"));
}

function results_dns_domains_search($search){
	
	$prefix_search=$search;
	$tpl=new template_admin();
	$q=new mysql_pdns();
	$search="*$search*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("*", "%", $search);
	$sql="SELECT * FROM domains WHERE name LIKE '$search'";
	
	$html[]="<div class=\"row border-bottom white-bg dashboard-header\">";
	$html[]="<div class=\"col-sm-12\" id='TABLEAU-TOP-RECHERCHE'><h1 class=ng-binding>{search} &laquo;$prefix_search&raquo;</h1></div>";
	$html[]="</div>";
	$html[]="<div class=row><div class='ibox-content'>";
	$html[]="<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{items}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}
	$TRCLASS=null;
	while ($ligne = mysqli_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$item=$ligne["name"];
		$id=$ligne["id"];
		

		$jshost="Loadjs('fw.dns.domain.php?domain-id=$id');";
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$jshost\" style='text-decoration:underline'>";
		
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"\" style='width:1%'><i class='fa fas fa-database'></td>";
		$html[]="<td class=\"\">&nbsp;<strong>". $tpl->td_href($item,null,$jshost)."</strong></td>";
		$html[]="<td class=\"\">&nbsp;</td>";
		$html[]="<td class=\"\">&nbsp;</td>";
		$html[]="</tr>";
		
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='4'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="</div></div>
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": {\"enabled\": true} } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}