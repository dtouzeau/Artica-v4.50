<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsSystemAdministrator){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["search"])) {table();exit;}
page();




function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html[]=$tpl->search_block($page,"sqlite:/home/artica/SQLITE/python-packages.db","python_packages","paquets-python","");
	echo $tpl->_ENGINE_parse_body($html);
}

function table(){
	$tpl=new template_admin();
	$eth_sql=null;
	$token=null;
	$class=null;
	$q=new lib_sqlite("/home/artica/SQLITE/python-packages.db");


	$t=time();
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\">
	<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.python-collection.php?function={$_GET["function"]}')\"><i class='fas fa-scanner'></i> {build_collection} </label>
	</div>";
	

	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-paquets-python' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	

	$search=$tpl->query_pattern(trim(strtolower($_GET["search"])),array());
	if($search["MAX"]==0){$search["MAX"]=150;}

    $sql="SELECT * FROM python_packages {$search["Q"]}ORDER BY package_name DESC LIMIT {$search["MAX"]}";
	if($_GET["search"]<>null){
        if($search["Q"]==null){
            $_GET["search"]="*{$_GET["search"]}*";
            $_GET["search"]=str_replace("**","*",$_GET["search"]);
            $_GET["search"]=str_replace("**","*",$_GET["search"]);
            $_GET["search"]=str_replace("*","%",$_GET["search"]);
            $sql="SELECT * FROM python_packages WHERE package_name LIKE '{$_GET["search"]}' OR package_description LIKE '{$_GET["search"]}' ORDER BY package_name DESC LIMIT {$search["MAX"]}";
        }

    }



	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error."<br>$sql");return;}

	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{software}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{version}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


	

	


	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$r=trim($ligne["package_status"]);
		$status=$r;$text_class=null;
		
		$package_name=$ligne["package_name"];
		$package_version=$ligne["package_version"];
		$package_description=$ligne["package_description"];
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\"  style='width:1%' nowrap>$package_name</td>";
		$html[]="<td class=\"$text_class\" style='width:1%' nowrap>$package_version</a></td>";
		$html[]="<td class=\"$text_class\">$package_description</a></td>";
		
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
	</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}