<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.browser.detection.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["categories"])){categories();exit;}
	if(isset($_GET["category-search"])){categories_search();exit;}
	if(isset($_GET["add-perso-cat-js"])){add_category_js();exit;}
	if(isset($_GET["add-perso-cat-tabs"])){add_category_tabs();exit;}
	if(isset($_GET["add-perso-cat-popup"])){add_category_popup();exit;}
	
	
	if(isset($_GET["popup-personal"])){popup_personal();exit;}
	if(isset($_GET["popup-personal-list"])){echo popup_personal_list($_GET["rule_main"],$_GET["category"]);exit;}
	if(isset($_GET["personal_category_delete"])){personal_category_delete();exit;}
	if(isset($_GET["WebsiteToAdd"])){echo popup_personal_add();exit;}
	
	page();
	
	
function add_category_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$widownsize=995;
	$t=$_GET["t"];
	$title=$tpl->_ENGINE_parse_body("{category}::{$_GET["cat"]}");
	$html="YahooWin5('$widownsize','$page?add-perso-cat-tabs=yes&cat={$_GET["cat"]}&t=$t','$title');";
	echo $html;
}	
function add_category_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$catname=trim($_GET["cat"]);
	$catname_enc=urlencode($catname);

	
	$array["add-perso-cat-popup"]=$catname;
	$array["manage"]='{websites}';
	$array["urls"]='{urls}';

	$fontsize=18;
	$catzenc=urlencode($_GET["cat"]);
	$t=$_GET["t"];
	foreach ($array as $num=>$ligne){

		if($num=="manage"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.categories.php?popup=yes&category=$catname_enc&tablesize=695&t=$t\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="urls"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.categories.urls.php?popup=yes&category=$catname_enc&tablesize=695&t=$t\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}

		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"$page?$num=$t&t=$t&cat=$catname_enc\" style='font-size:18px'><span>$ligne</span></a></li>\n");
	}



	echo build_artica_tabs($html, "main_zoom_catz");



}

function add_category_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$dans=new dansguardian_rules();
	$time=time();
	$q=new mysql_squid_builder();
	$error_max_dbname=$tpl->javascript_parse_text("{error_max_database_name_no_more_than}");
	$error_category_textexpl=$tpl->javascript_parse_text("{error_category_textexpl}");
	$error_category_nomore5=$tpl->javascript_parse_text("{error_category_nomore5}");


	if(!$q->FIELD_EXISTS("personal_categories", "PublicMode")){
		$q->QUERY_SQL("ALTER TABLE `personal_categories`
				ADD `PublicMode` smallint( 1 ) NOT NULL ,
				ADD INDEX ( `PublicMode` )");
	}

	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$catenc=urlencode($_GET["cat"]);
	if($_GET["cat"]==null){$actions=null;}
	if($_GET["cat"]<>null){
		$sql="SELECT * FROM personal_categories WHERE category='{$_GET["cat"]}'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	}

	$groups=$dans->LoadBlackListesGroups();
	$groups[null]="{select}";
	$field=Field_array_Hash($groups, "CatzByGroupL",null,null,null,0,"font-size:22px");

	$blacklists=$dans->array_blacksites;
	$description=utf8_encode($ligne["category_description"]);
	if(isset($blacklists[$_GET["cat"]])){$description=$blacklists[$_GET["cat"]];}

	$html="
	<div id='perso-cat-form'></div>
	<div style='width:98%' class=form>
			<table style='width:100%'>
			<tbody>
			<tr>
				<td class=legend style='font-size:22px'>{category}:</td>
				<td style='font-size:22px'>{$_GET["cat"]}</td>
				</tr>
				<tr>
				<td class=legend style='font-size:22px'>{description}:</td>
				<td style='font-size:22px'>$description</td>
				</tr>
			</tbody>
			</table>
	</div>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
	
	
function page(){
	$page=CurrentPageName();

	
	
	
	unset($_SESSION["ProxyCategoriesPermissions"]);
	$ie=browser_detection();
	
	if($ie=="ie"){
		$tpl=new template_users("Fatal error",FATAL_ERROR_SHOW_128("{NOIEPLEASE_TEXT}"),$_SESSION,0,0,0);
		$html=$tpl->web_page;
		echo $html;
		return;
	}
	
	$title="{categories}";
	$html="
	<div id='public_categories_div'></div>
	<script>
		$alert
		LoadAjax('public_categories_div','$page?tabs=yes');
	</script>";
	
	
	
	
	
	
	$tpl=new template_users($title,$html,$_SESSION,0,0,0);
	$html=$tpl->web_page;
	echo $html;	
return;	
	
	
}	

function tabs(){
	if(GET_CACHED(__FILE__, __FUNCTION__)){return;}
	$squid=new squidbee();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();

	$array["table"]="{categories}";

	$fontsize=18;

	$t=time();
	foreach ($array as $num=>$ligne){


		if($num=="table"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
			<a href=\"$page?categories=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;

		}


		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}



	$html= build_artica_tabs($html,'main_perso_categories',1024);
	SET_CACHED(__FILE__, __FUNCTION__, null, $html);
	echo $html;

}
	
	
function categories(){
	$page=CurrentPageName();
	$tpl=new templates();

	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("webfilters_categories_caches")){$q->CheckTables();}else{
		$q->QUERY_SQL("TRUNCATE TABLE webfilters_categories_caches");
	}

	$q->QUERY_SQL("DELETE FROM personal_categories WHERE category='';");
	$OnlyPersonal=null;
	$dans=new dansguardian_rules();
	$dans->LoadBlackListes();


	$purge_catagories_database_explain=$tpl->javascript_parse_text("{purge_catagories_database_explain}");
	$purge_catagories_table_explain=$tpl->javascript_parse_text("{purge_catagories_table_explain}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{new_category}");
	$purge=$tpl->_ENGINE_parse_body("{purgeAll}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$tablewith=691;
	$compilesize=35;
	$size_elemnts=50;
	$size_size=58;
	$title=$tpl->javascript_parse_text("{categories}");
	$deletetext=$tpl->javascript_parse_text("{purge}");
	$delete="{display: '$deletetext', name : 'icon3', width : 90, sortable : false, align: 'center'},";


	$bt_add="{name: '$addCat', bclass: 'add', onpress : AddNewCategory},";

	$t=time();
	$html="
<table class='PERSONAL_CATEGORIES_TABLE' style='display: none' id='PERSONAL_CATEGORIES_TABLE' style='width:99%'></table>
<script>
$(document).ready(function(){
	$('#PERSONAL_CATEGORIES_TABLE').flexigrid({
	url: '$page?category-search=yes',
	dataType: 'json',
	colModel : [
	{display: '$category', name : 'category', width : 416, sortable : false, align: 'left'},
	{display: '$items', name : 'TABLE_ROWS', width : 121, sortable : true, align: 'right'},
	{display: 'compile', name : 'icon2', width : 121, sortable : false, align: 'center'},
	

	],
	buttons : [
		
		
	],
	searchitems : [
	{display: '$category', name : 'category'},
	],
	sortname: 'category',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true

});
});


function AddNewCategory(){
	Loadjs('$page?add-perso-cat-js=yes&t=$t');
}

function SwitchToArtica(){
$('#dansguardian2-category-$t').flexOptions({url: '$page?category-search=yes&minisize={$_GET["minisize"]}&t=$t&artica=1'}).flexReload();
}

function SaveAllToDisk(){
Loadjs('$page?compile-all-dbs-js=yes')

}

function LoadCategoriesSize(){
Loadjs('dansguardian2.compilesize.php')
}

function CategoryDansSearchCheck(e){
if(checkEnter(e)){CategoryDansSearch();}
}

function CategoryDansSearch(){
var se=escape(document.getElementById('category-dnas-search').value);
LoadAjax('dansguardian2-category-list','$page?category-search='+se,false);

}

function DansGuardianCompileDB(category){
Loadjs('ufdbguard.compile.category.php?category='+category);
}

function CheckStatsApplianceC(){
LoadAjax('CheckStatsAppliance','$page?CheckStatsAppliance=yes',false);
}

var X_PurgeCategoriesDatabase= function (obj) {
var results=obj.responseText;
if(results.length>2){alert(results);}
RefreshAllTabs();
}

function PurgeCategoriesDatabase(){
if(confirm('$purge_catagories_database_explain')){
var XHR = new XHRConnection();
XHR.appendData('PurgeCategoriesDatabase','yes');
AnimateDiv('dansguardian2-category-list');
XHR.sendAndLoad('$page', 'POST',X_PurgeCategoriesDatabase);
}

}

var X_TableCategoryPurge= function (obj) {
var results=obj.responseText;
if(results.length>2){alert(results);}
$('#dansguardian2-category-$t').flexReload();
}

function TableCategoryPurge(tablename){
if(confirm('$purge_catagories_table_explain')){
var XHR = new XHRConnection();
XHR.appendData('PurgeCategoryTable',tablename);
XHR.sendAndLoad('dansguardian2.databases.compiled.php', 'POST',X_TableCategoryPurge);
}
}


CheckStatsApplianceC();
</script>

";

echo $tpl->_ENGINE_parse_body($html);


}

function categories_search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$dans=new dansguardian_rules();
	$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$t=$_GET["t"];
	$OnlyPersonal=0;
	
	$rp=200;
	if(isset($_GET["artica"])){$artica=true;}
	if(!$q->BD_CONNECT()){json_error_show("Testing connection to MySQL server failed...",1);}

	$sql="SELECT * FROM personal_categories WHERE `PublicMode`=1";
	$table="personal_categories";
	$searchstring=string_to_flexquery();
	$page=1;


	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}


	if (isset($_POST['page'])) {$page = $_POST['page'];}


	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE PublicMode=1 $searchstring";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];

	}else{
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE PublicMode=1";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT * FROM $table WHERE PublicMode=1 $searchstring $ORDER $limitSql ";

	writelogs("$q->mysql_admin:$q->mysql_password:$sql",__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);

	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}


	if(mysqli_num_rows($results)==0){json_error_show("Not found...",1);}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();


	$enc=new mysql_catz();

	$field="category";
	$field_description="category_description";
	$CATZ_ARRAY=unserialize(@file_get_contents("/home/artica/categories_databases/CATZ_ARRAY"));
	$CategoriesCheckRightsWrite=CategoriesCheckRightsWrite();

	$TransArray=$enc->TransArray();
	while (list ($tablename, $items) = each ($CATZ_ARRAY) ){
		if(!isset($TransArray[$tablename])){continue;}
		$CATZ_ARRAY2[$TransArray[$tablename]]=$items;
	}

	while ($ligne = mysqli_fetch_assoc($results)) {
		$color="black";
		$categorykey=$ligne["category"];
		if($categorykey==null){$categorykey="UnkNown";}
		//Array ( [category] => [category_description] => Ma catégorie [master_category] => [sended] => 1 )
		if($GLOBALS["VERBOSE"]){echo "Found  $field:{$categorykey}<br>\n";}
		$categoryname=$categorykey;
		$text_category=null;

		$table=$q->cat_totablename($categorykey);
		if($GLOBALS["VERBOSE"]){echo "Scanning table $table<br>\n";}
			

		$itemsEncTxt=null;
		$items=$q->COUNT_ROWS($table);
			

		if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
		$compile=imgsimple("compile-distri-32.png","{saveToDisk}","DansGuardianCompileDB('$categoryname')");

		if($dans->array_pics[$categoryname]<>null){
			$pic="<img src='img/{$dans->array_pics[$categoryname]}'>";}else{$pic="&nbsp;";}

			$sizedb_org=$q->TABLE_SIZE($table);
			$sizedb=FormatBytes($sizedb_org/1024);


			$linkcat="<a href=\"javascript:blur();\" OnClick=\"Loadjs('squid.categories.php?category={$categoryname}&t=$t',true)\"
			style='font-size:18px;font-weight:bold;color:$color;text-decoration:underline'>";

			$text_category=$tpl->_ENGINE_parse_body(utf8_decode($ligne[$field_description]));
			$text_category=trim($text_category);

			$linkcat="<a href=\"javascript:blur();\" OnClick=\"Loadjs('$MyPage?add-perso-cat-js=yes&cat=$categoryname&t=$t',true)\"
			style='font-size:18px;font-weight:bold;color:$color;text-decoration:underline'>";


				
			$viewDB=imgsimple("mysql-browse-database-32.png",null,"javascript:Loadjs('squid.categories.php?category={$categoryname}',true)");


			$text_category=utf8_encode($text_category);
			$categoryname_text=utf8_encode($categoryname);
			$categoryText=$tpl->_ENGINE_parse_body("<span style='font-size:26px';font-weight:bold'>$linkcat$categoryname_text</div>
					</a><br><span style='font-size:18px;width:100%;font-weight:normal'>{$text_category}</div>");
				
			$itemsEncTxt="<span style='font-size:32px;font-weight:bold'>".numberFormat($items,0,""," ");"</span>";


			$compile=imgsimple("compile-distri-48.png",null,"DansGuardianCompileDB('$categoryname')");
			$delete=imgsimple("dustbin-48.png",null,"TableCategoryPurge('$table')");
				


			if($categoryname=="UnkNown"){
				$linkcat=null;
				$delete=imgsimple("delete-48.png",null,"TableCategoryPurge('')");
			}

			if(!$CategoriesCheckRightsWrite){$compile=null;}
			$cell=array();
			$cell[]=$categoryText;
			$cell[]=$itemsEncTxt;
			$cell[]=$compile;
			

			$data['rows'][] = array(
					'id' => $ligne['ID'],
					'cell' => $cell
			);
	}


	echo json_encode($data);

}
	
	
?>