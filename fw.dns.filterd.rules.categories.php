<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.dansguardian.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.categories.inc');
include_once("ressources/class.ldap-extern.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");

if(isset($_GET["list"])){table();exit;}
if(isset($_GET["category-add-js"])){categoy_add_js();exit;}
if(isset($_GET["category-add"])){categoy_add();exit;}
if(isset($_GET["category-post-js"])){categoy_post();exit;}
if(isset($_GET["category-del-js"])){category_del();exit;}	
	//category-del-js=$categorykey&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}&md=$md

page();

function categoy_add_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog2("{categories}", "$page?category-add=yes&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}");
	
	
}


function category_del(){
	header("content-type: application/x-javascript");
	$category=$_GET["category-del-js"];
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$modeblk=$_GET["modeblk"];
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="DELETE FROM webfilter_blks WHERE category='$category' AND modeblk='$modeblk' AND webfilter_id='$ID'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	echo "$('#{$_GET["md"]}').remove();\n";
	echo "LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')";
}

function categoy_post(){
	header("content-type: application/x-javascript");
	$category=$_GET["category-post-js"];
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$modeblk=$_GET["modeblk"];
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="SELECT ID FROM webfilter_blks WHERE category='$category' AND modeblk='$modeblk' AND webfilter_id='$ID'";
	$ligne=$q->mysqli_fetch_array($sql);
	if($ligne["ID"]>0){return;}
	
	$sql="INSERT OR IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('$ID','{$category}','{$modeblk}')";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo "alert('".$tpl->javascript_parse_text($q->mysql_error)."');";return;}
	
	echo "$('#{$_GET["md"]}').remove();\n";
	echo "RefreshUfdbCategoriesList();\n";
	echo "LoadAjax('table-loader-dnsfilterd-rules','fw.dns.filterd.rules.php?table=yes')";
	
	
}

function page(){
	$page=CurrentPageName();
	echo "<div id='ufdb-categories-list' style='margin-top:20px'></div>
	<script>
		function RefreshUfdbCategoriesList(){
			LoadAjax('ufdb-categories-list','$page?list=yes&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}');
			
		}
		RefreshUfdbCategoriesList();
	</script>	
	";
	
	
}

function categoy_add(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	
	$sql="SELECT `category` FROM webfilter_blks WHERE `webfilter_id`={$_GET["ID"]} AND modeblk={$_GET["modeblk"]}";
	$results=$q->QUERY_SQL($sql);
	
	foreach ($results as $index=>$ligne){$cats[$ligne["category"]]=true;}
	
	
	$dans=new dansguardian_rules();
	
	$q=new postgres_sql();
	if(!$q->TABLE_EXISTS("personal_categories")){
		$categories=new categories();
		$categories->initialize();
		
	}
	$sql="SELECT *  FROM personal_categories ORDER BY categoryname";
	$users=new usersMenus();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		$sql="SELECT *  FROM personal_categories WHERE free_category=1 ORDER BY categoryname";
	}
	
	
	$results = $q->QUERY_SQL($sql);
	$compile=$tpl->_ENGINE_parse_body("{compile}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$results = $q->QUERY_SQL($sql);
	
	if(!$q->ok){echo $q->mysql_error;exit();}
	
	$TRCLASS=null;
	
	$html[]="<table id='table-category-add-{$_GET["modeblk"]}' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=false class='text-capitalize' >&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$category</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$description}</th>";
	$html[]="<th data-sortable=false>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	

	$TRCLASS=null;
	$users=new usersMenus();
	
	$UfdbMasterCache=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbMasterCache"));
	
	while ($ligne = pg_fetch_assoc($results)) {
		$category_id=$ligne["category_id"];
		$categoryname=$ligne["categoryname"];
		$categorykey=$ligne["categorykey"];
		$categorytable=$ligne["categorytable"];
		$items=$ligne["items"];
		$category_id=$ligne["category_id"];
		$category_description=$ligne["category_description"];
		$category_icon=$ligne["category_icon"];
		$master_category=$ligne["master_category"];
		$official_category=$ligne["official_category"];
		$free_category=$ligne["free_category"];
		$ISOFFICIAL=false;
		if($official_category==1){$ISOFFICIAL=true;}
		if($free_category==1){$ISOFFICIAL=true;}
		if($category_icon==null){$category_icon="20-categories-personnal.png";}
		if(isset($cats[$category_id])){continue;}
		$elements=null;
		
		
		$category_table=$categorytable;
		if(!$ISOFFICIAL){
			$category_table_elements=$items;
		}else{
			$category_table_elements=$UfdbMasterCache[$category_id]["items"];
		}
		$DBTXT=array();
		$database_items=null;
		$license=null;
		$img="{$category_icon}";
		$val=0;
		$md=md5($ligne["categorykey"]);
		$ligne['description']=utf8_encode($category_description);
		$categorykey=urlencode($ligne["categorykey"]);
		
		$js="Loadjs('$page?category-post-js=$category_id&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}&md=$md')";
		$button="<button class='btn btn-primary btn-xs' OnClick=\"javascript:$js\">{select}</button>";
		if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
			if($official_category==1){
				$license=" <i class='text-danger'>({license_error})</i>";
				$button="<button class='btn btn-default btn-xs' OnClick=\"javascript:blur()\">{select}</button>";
				
			}
		}
		if($category_table_elements>0){$elements="<br><strong><small>".FormatNumber($category_table_elements)." {items}</small></strong>";}
		
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%'><img src='$img'></td>";
		$html[]="<td width=1% nowrap>".$tpl->_ENGINE_parse_body($categoryname)."</td>";
		$html[]="<td>".$tpl->_ENGINE_parse_body("{$ligne['description']} $license{$elements}")."</td>";
		$html[]=$tpl->_ENGINE_parse_body("<td width=1% nowrap>$button</td>");
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
	$html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-category-add-{$_GET["modeblk"]}').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
$html[]="</script>";
echo @implode("\n", $html);
	
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	
	
	$tableProd="webfilter_blks";
	$items=$tpl->_ENGINE_parse_body("{items}");
	$compile=$tpl->_ENGINE_parse_body("{compile}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$catz=new mysql_catz();
	$TRCLASS=null;
	
	
	
	$sql="SELECT `category` FROM $tableProd WHERE `webfilter_id`={$_GET["ID"]} AND modeblk={$_GET["modeblk"]}";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html(true);}
	

	$html[]=$tpl->_ENGINE_parse_body("
	
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?category-add-js=yes&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}')\"><i class='fa fa-plus'></i> {add_categories} </label>
			</div>
				<div class=\"btn-group\" data-toggle=\"buttons\">
			</div>
			<table id='table-all-categories' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=false class='text-capitalize' >&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$category</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$description}</th>";
	$html[]="<th data-sortable=false>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	$postgres=new postgres_sql();
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$category_id=intval($ligne["category"]);
		$ligne2=pg_fetch_array($postgres->QUERY_SQL("SELECT * FROM personal_categories WHERE category_id='$category_id'"));
		$items=$ligne2["items"];
		$categorykey=$category_id;
		$category_description=$ligne2["category_description"];
		$categoryname=$tpl->_ENGINE_parse_body($ligne2["categoryname"]);
		$category_icon=$ligne2["category_icon"];
		
		
		$DBTXT=array();
		$database_items=null;
		$img="img/{$ligne["picture"]}";
		$val=0;
		
		$md=md5("{$category_id}{$_GET["ID"]}");
		$ligne['description']=utf8_encode($tpl->_ENGINE_parse_body($category_description));
		
		$jsdel="Loadjs('$page?category-del-js=$categorykey&ID={$_GET["ID"]}&modeblk={$_GET["modeblk"]}&md=$md')";
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%'><img src='$category_icon'></td>";
		$html[]="<td>{$categoryname}</td>";
		$html[]="<td>{$ligne['description']}</td>";
		$html[]="<td>". $tpl->icon_delete($jsdel,"AsDnsAdministrator")."</td>";
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
	$html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-all-categories').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });";
		echo @implode("\n", $html);
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}