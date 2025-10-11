<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsSystemAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])) {table();exit;}
page();




function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html[]=$tpl->search_block($page,"sqlite:/home/artica/SQLITE/dpkg.db","debian_packages","paquets-debian","");
	echo $tpl->_ENGINE_parse_body($html);
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new mysql();
	$eth_sql=null;
	$token=null;
	$class=null;

	$js="OnClick=\"javascript:LoadAjax('table-loader','$page?table=yes&eth=');\"";
	
	$q=new lib_sqlite("/home/artica/SQLITE/dpkg.db");


	$t=time();
	

	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-paquets-debian' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";

	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])),array());
	if($search["MAX"]==0){$search["MAX"]=150;}
	$sql="SELECT * FROM debian_packages {$search["Q"]}ORDER BY package_name DESC LIMIT {$search["MAX"]}";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error."<br>$sql");return;}


	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{software}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{explain}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$Pstatus["rc"]="<span class='label'>{removed_package}</span>";
	$Pstatus["ii"]="<span class='label label-info'>{installed_package}</span>";
	$Pstatus["hi"]="<span class='label label-warning'>{partially_installed}</span>";
	$Pstatus["a-uu"]="<span class='label label-warning'>{to_be_uninstalled}</span>";
	$Pstatus["a-ii"]="<span class='label label-danger'>{to_be_installed}</span>";
	

	


	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$r=trim($ligne["package_status"]);
		$status=$r;$text_class=null;
		if(isset($Pstatus[$r])){
			$status=$Pstatus[$r];
		}
		$package_name=$ligne["package_name"];
		$package_info=$ligne["package_info"];
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap>$status</td>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap>$package_name</a></td>";
		$html[]="<td class=\"$text_class\" style='width:99%'>$package_info</td>";
		$html[]="</tr>";


	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='3'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table><div><small>$sql</small></div>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-paquets-debian').footable( { \"filtering\": { \"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}