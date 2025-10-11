<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.milter.greylist.inc');
include_once('ressources/class.artica.graphs.inc');
include_once('ressources/class.maincf.multi.inc');

if(isset($_GET["hostname"])){if(trim($_GET["hostname"])==null){unset($_GET["hostname"]);}}

$user=new usersMenus();
if(!isset($_GET["hostname"])){
	if(!$user->AsPostfixAdministrator){FATAL_ERROR_SHOW_128("{$_GET["hostname"]}::{ERROR_NO_PRIVS}");die("DIE " .__FILE__." Line: ".__LINE__);}
}else{
	if(!PostFixMultiVerifyRights()){FATAL_ERROR_SHOW_128("{$_GET["hostname"]}::{ERROR_NO_PRIVS}");die("DIE " .__FILE__." Line: ".__LINE__);}

}
if(isset($_GET["acl-table-list"])){main_acl_table();exit;}
if(isset($_GET["acl-js"])){acl_js();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["acl-popup"])){acl_popup();exit;}
if(isset($_POST["pattern"])){acl_save();exit;}
if(isset($_POST["delete"])){delete_save();exit;}

table();


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$method=$tpl->_ENGINE_parse_body("{method}");
	$type=$tpl->_ENGINE_parse_body("{action}");
	$pattern=$tpl->_ENGINE_parse_body("{pattern}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$hostname=$_GET["hostname"];
	$add=$tpl->_ENGINE_parse_body("{add}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$about=$tpl->javascript_parse_text("{about2}");
	$blacklist=$tpl->javascript_parse_text("{blacklist}");
	$whitelist=$tpl->javascript_parse_text("{whitelist}");
	$discard=$tpl->javascript_parse_text("{discard}");
	$all=$tpl->javascript_parse_text("{all}");
	$zDate=$tpl->javascript_parse_text("{zDate}");
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("milterregex_acls","reverse","artica_backup")){
		$sql="ALTER TABLE `milterregex_acls` ADD `reverse` smallint(1) NOT NULL,ADD INDEX ( `reverse` )";
		$q->QUERY_SQL($sql,'artica_backup');
	}
	if(!$q->FIELD_EXISTS("milterregex_acls","extended","artica_backup")){
		$sql="ALTER TABLE `milterregex_acls` ADD `extended` smallint(1) NOT NULL,ADD INDEX ( `extended` )";
		$q->QUERY_SQL($sql,'artica_backup');
	}
	
	
	if(!$q->TABLE_EXISTS('milterregex_acls','artica_backup')){
		$sql="CREATE TABLE IF NOT EXISTS `milterregex_acls` (
			  `zmd5` varchar(90) NOT NULL  PRIMARY KEY,
			  `instance` varchar(128)  NOT NULL,
			  `method` varchar(50)  NOT NULL,
			  `type` varchar(50)  NOT NULL,
			  `enabled` smallint(1)  NOT NULL,
			  `pattern` varchar(256)  NOT NULL,
			  `description` varchar(255)  NOT NULL,
			  `reverse` smallint(1) NOT NULL,
			   `extended` smallint(1) NOT NULL,
			  `zDate` DATETIME,
			  KEY `instance` (`instance`),
			  KEY `enabled` (`enabled`),
			  KEY `reverse` (`reverse`),
			  KEY `extended` (`extended`),
			  KEY `zDate` (`zDate`),
			  KEY `method` (`method`),
			  KEY `type` (`type`),
			  KEY `pattern` (`pattern`)
			)";
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){
			echo $q->mysql_error_html();
		}
	
	}
	if($q->COUNT_ROWS("milterregex_acls", "artica_backup")==0){
		$sock=new sockets();
		$sock->getFrameWork("milter-regex.php?defaults=yes");
	}
	
	
	$t=time();
	if(trim($hostname)==null){$hostname="master";$_GET["hostname"]="master";}

	$about_text=$tpl->javascript_parse_text("{acl_text}");
	$POSTFIX_MULTI_INSTANCE_INFOS=$tpl->javascript_parse_text("{milter_regex}: {acls}");
	
	$html="
	<table class='MILTERREGEX_TABLE' style='display: none' id='MILTERREGEX_TABLE' style='width:99%'></table>
	
	<script>
	var idtmp='';
	$(document).ready(function(){
	$('#MILTERREGEX_TABLE').flexigrid({
	url: '$page?acl-table-list=yes&hostname=$hostname&t=$t&ou={$_GET["ou"]}',
	dataType: 'json',
	colModel : [
	
	{display: '<span style=font-size:18px>$zDate</span>', name : 'zDate', width :144, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$type</span>', name : 'type', width : 174, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$method</span>', name : 'method', width :145, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$pattern</span>', name : 'pattern', width : 419, sortable : true, align: 'left'},
	{display: '<span style=font-size:18px>$description</span>', name : 'description', width : 424, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 50, sortable : false, align: 'center'},
	],
	buttons : [
	{name: '<strong style=font-size:18px>$add</strong>', bclass: 'add', onpress : addcallistrule$t},
	{separator: true},
	{name: '<strong style=font-size:18px>$blacklist</strong>', bclass: 'Search', onpress : blacklist$t},
	{name: '<strong style=font-size:18px>$whitelist</strong>', bclass: 'Search', onpress : whitelist$t},
	{name: '<strong style=font-size:18px>$discard</strong>', bclass: 'Search', onpress : discard$t},
	
	{name: '<strong style=font-size:18px>$all</strong>', bclass: 'Search', onpress : all$t},
	
	],
	searchitems : [
	{display: '$pattern', name : 'pattern'},
	{display: '$description', name : 'description'},
	{display: '$method', name : 'method'},
	{display: '$type', name : 'type'},
	
	
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:30px>$POSTFIX_MULTI_INSTANCE_INFOS</span>',
	useRp: true,
	rp: 20,
	showTableToggleBtn: true,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});
	});
	
	function  about$t(){
	alert('$about_text');
	}
	
	function addcallistrule$t(){
		Loadjs('$page?acl-js=yes&zmd5=');
	}
	
	function LoadMilterGreyListAcl$t(index){
	YahooWin4(750,'$page?add_acl=true&num='+index+'&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$acl&nbsp;$rule&nbsp;'+index);
	}
	
	function blacklist$t(){
	$('#MILTERREGEX_TABLE').flexOptions({url: '$page?acl-table-list=yes&hostname=$hostname&t=$t&ou={$_GET["ou"]}&filterby=reject'}).flexReload();
	}
	function whitelist$t(){
	$('#MILTERREGEX_TABLE').flexOptions({url: '$page?acl-table-list=yes&hostname=$hostname&t=$t&ou={$_GET["ou"]}&filterby=accept'}).flexReload();
	}
	function discard$t(){
	$('#MILTERREGEX_TABLE').flexOptions({url: '$page?acl-table-list=yes&hostname=$hostname&t=$t&ou={$_GET["ou"]}&filterby=discard'}).flexReload();
	}
	function all$t(){
	$('#MILTERREGEX_TABLE').flexOptions({url: '$page?acl-table-list=yes&hostname=$hostname&t=$t&ou={$_GET["ou"]}&filterby='}).flexReload();
	}
	
	var X_DeleteAclIDNewFunc= function (obj) {
	var results=obj.responseText;
	if(results.length>1){alert(results);return;}
	$('#row'+idtmp).remove();
	}
	
	function DeleteAclIDNewFunc(ID){
	idtmp=ID;
	var XHR = new XHRConnection();
	XHR.appendData('DeleteAclID',ID);
	XHR.appendData('hostname','$hostname');
	XHR.sendAndLoad('$page', 'POST',X_DeleteAclIDNewFunc);
	}
	
	
	</script>
	
	";
	echo $html;
}


function acl_js(){
	$zmd5=$_GET["zmd5"];
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$hostname=urlencode($_GET["hostname"]);
	$title=$tpl->_ENGINE_parse_body("{rule}:$zmd5");
	
	if($zmd5==null){
		$title=$tpl->javascript_parse_text("{new_rule}");
	}
	
	echo "YahooWin3('895','$page?acl-popup=yes&zmd5=$zmd5&hostname=$hostname','$title');";
	
}

function delete_js(){
	$page=CurrentPageName();
	$zmd5=$_GET["delete-js"];
	header("content-type: application/x-javascript");
	$t=time();
	echo "var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;};
	$('#MILTERREGEX_TABLE').flexReload();
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('delete','$zmd5');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();";
}
function delete_save(){
	$q=new mysql();
	$sql="DELETE FROM milterregex_acls WHERE zmd5='{$_POST["delete"]}'";
	$q->QUERY_SQL($sql,"artica_backup");	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("milter-regex.php?restart=yes");
}

function acl_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$t=time();
	$btname="{apply}";
	
	
	$sql="SELECT * FROM milterregex_acls WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	if($_GET["zmd5"]==null){
		$btname="{add}";
		$ligne["enabled"]=1;
	}
	
	$method=$tpl->javascript_parse_text("{method}");
	
	$arrayd=Field_array_Hash(
		array(""=>"{select}",
	"envfrom"=>"{envfrom}",
	'envrcpt'=>"{envrcpt}",
	"header"=>"{header}",
	"subject"=>"{subject}",
	"body"=>"{body}",
	"helo"=>"{helo}",
	"connect"=>"{cnx_addr}"				
			
	),"$t-method",
	$ligne["method"],"blur();",null,0,'font-size:22px;padding:5px');
	
	
	$action["reject"]="{reject}";
	$action["discard"]="{discard}";
	$action["accept"]="{accept}";
	
	$PatternField="<textarea name='pattern-$t' id='pattern-$t' rows=10
	style='width:99%;font-size:22px !important;'>{$ligne["pattern"]}</textarea>";
	

$arrayf=Field_array_Hash($action,"$t-type",$ligne["type"],"blur();",null,0,'font-size:22px;padding:5px');
$ligne["pattern"]=trim($ligne["pattern"]);
$id=time();
$html="
<div style='width:98%' class=form>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td align='right' nowrap style='width:1%;font-size:22px' class=legend>{enabled}:</strong></td>
		<td>". Field_checkbox_design("enabled-$t", 1,$ligne["enabled"])."</td>
	</tr>		
	<tr>
		<td align='right' nowrap style='width:1%;font-size:22px' class=legend>{action}:</strong></td>
		<td><strong>$arrayf</strong></td>
	</tr>	
	<tr>
		<td align='right' nowrap style='width:1%;font-size:22px' class=legend>{analyze}:</strong></td>
		<td><strong>$arrayd</strong></td>
	</tr>

	<tr>
	<tr>
		<td snowrap style='width:1%;font-size:22px;vertical-align:top' class=legend>{regex}:</strong></td>
		<td>$PatternField</td>
	</tr>
	<tr>
		<td align='right' nowrap style='width:1%;font-size:22px' class=legend>{reverse}:</strong></td>
		<td>". Field_checkbox_design("reverse-$t", 1,$ligne["reverse"])."</td>
	</tr>	
	<tr>
		<td align='right' nowrap style='width:1%;font-size:22px' class=legend>{extended_regex}:</strong></td>
		<td>". Field_checkbox_design("extended-$t", 1,$ligne["extended"])."</td>
	</tr>	
	
<tr>
		<td align='right' width=1% nowrap><strong style='font-size:22px'>{infos}:</strong></td>
	<td>
	<textarea name='$t-infos' id='$t-infos' rows=1
	style='width:100%;font-size:22px !important;'>{$ligne["description"]}</textarea>
	</td>
</tr>
<tr>
	<td colspan=2 align='right'><hr>". button("$btname","Save$t()",30)."</td></tr>
</table>
<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;};
	YahooWin3Hide();
	$('#MILTERREGEX_TABLE').flexReload();
}
	
function Save$t(){
	var mode=document.getElementById('$t-method').value;
	var xType=document.getElementById('$t-type').value;
	var enabled=0;
	var reverse=0;
	var extended=0;
	if(mode.length==0){alert('$method = NULL');return;}
	if(xType.length==0){alert('$method = NULL');return;}
	var XHR = new XHRConnection();
	XHR.appendData('zmd5','{$_GET["zmd5"]}');
	XHR.appendData('type',xType);
	if(document.getElementById('enabled-$t').checked){enabled=1;}
	if(document.getElementById('reverse-$t').checked){reverse=1;}
	if(document.getElementById('extended-$t').checked){extended=1;}
	XHR.appendData('pattern',encodeURIComponent(document.getElementById('pattern-$t').value));
	XHR.appendData('infos',encodeURIComponent(document.getElementById('$t-infos').value));
	XHR.appendData('method',document.getElementById('$t-method').value);
	XHR.appendData('enabled',enabled);
	XHR.appendData('reverse',reverse);
	XHR.appendData('extended',extended);
	XHR.appendData('ou','{$_GET["ou"]}');
	XHR.appendData('hostname','{$_GET["hostname"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
}

function acl_save(){
	$zmd5=$_POST["zmd5"];
	$type=$_POST["type"];
	$zDate=date("Y-m-d H:i:s");
	$instance=$_POST["hostname"];
	if($instance==null){$instance="master";}
	$method=$_POST["method"];
	$enabled=$_POST["enabled"];
	$reverse=$_POST["reverse"];
	$extended=$_POST["extended"];
	$description=mysql_escape_string(trim(url_decode_special_tool($_POST["infos"])));
	$pattern=mysql_escape_string(trim(url_decode_special_tool($_POST["pattern"])));
	
	$description=trim($description);
	$description=str_replace("\n", " ", $description);
	
	$pattern=trim($pattern);
	$pattern=str_replace("\n", " ", $pattern);	
	
	if($zmd5==null){
		$zmd5=md5("$type$method$pattern$instance");
		$sql="INSERT INTO `milterregex_acls` 
			(`zmd5`,`zDate`,`instance`,`method`,`type`,`pattern`,`description`,`enabled`,`reverse`,`extended`) VALUES 
			('$zmd5','$zDate','$instance','$method','$type','$pattern','$description',$enabled,$reverse,$extended);";
		
		
	}else{
	$sql="UPDATE `milterregex_acls` 
	SET method='$method',
	`type`='$type',
	`pattern`='$pattern',
	`description`='$description',
	`reverse`='$reverse',
	`extended`='$extended',
	`enabled`='$enabled'
	WHERE zmd5='$zmd5'
	";
	}
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("milterregex_acls","reverse","artica_backup")){
		$sql="ALTER TABLE `milterregex_acls` ADD `reverse` smallint(1) NOT NULL,ADD INDEX ( `reverse` )";
		$q->QUERY_SQL($sql,'artica_backup');
	}
	if(!$q->FIELD_EXISTS("milterregex_acls","extended","artica_backup")){
		$sql="ALTER TABLE `milterregex_acls` ADD `extended` smallint(1) NOT NULL,ADD INDEX ( `extended` )";
		$q->QUERY_SQL($sql,'artica_backup');
	}	
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("milter-regex.php?restart=yes");
}


function main_acl_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$mit=new milter_greylist();
	$action=$mit->actionlist;
	$q=new mysql();
	if($_GET["hostname"]==null){$_GET["hostname"]="master";}
	$pure=new milter_greylist(false,$_GET["hostname"]);
	$t=$_GET["t"];
	$search='%';
	$table="milterregex_acls";
	$page=1;
	if(!$q->TABLE_EXISTS("milterregex_acls", "artica_backup")){$q->BuildTables();}
	$FORCE=null;
	if(!isset($_GET["filterby"])){$_GET["filterby"]=null;}
	if($_GET["filterby"]<>null){
		$FORCE=" AND `type`='{$_GET["filterby"]}'";
	}

	if($q->COUNT_ROWS($table,"artica_backup")==0){
		json_error_show("NO item,1");

	}

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			if($_POST["sortname"]=="servername"){$_POST["sortname"]="value";}
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring AND (`instance` = '{$_GET["hostname"]}') $FORCE";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 AND (`instance` = '{$_GET["hostname"]}') $FORCE";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring 
	AND (`instance` = '{$_GET["hostname"]}') $FORCE $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	$method=array(""=>"{none}",
			"envfrom"=>"{envfrom}",
			'envrcpt'=>"{envrcpt}",
			"header"=>"{header}",
			"body"=>"{body}",
			"helo"=>"{helo}",
			"subject"=>"{subject}",
			"connect"=>"{cnx_addr}");
	
	
	$action["reject"]="{reject}";
	$action["discard"]="{discard}";
	$action["accept"]="{accept}";
		

	$divstart="<span style='font-size:12px;font-weight:normal'>";
	$divstop="</div>";
	if((mysqli_num_rows($results)==0)){json_error_show("no data");}


	while ($ligne = mysqli_fetch_assoc($results)) {
		$delete=$tpl->_ENGINE_parse_body(imgsimple('delete-32.png',null,"Loadjs('$MyPage?delete-js={$ligne["zmd5"]}')"));
		$color="black";
		$text_reverse=null;
		if($ligne["enabled"]==0){
			$color="#898989";
		}
		
		if($ligne["reverse"]==1){
			$text_reverse=$tpl->_ENGINE_parse_body(" {reverse}");
		}
		

		$link="Loadjs('$MyPage?acl-js=yes&zmd5={$ligne["zmd5"]}')";


		$js="<a href=\"javascript:blur()\" OnClick=\"javascript:$link\"
		style='text-decoration:underline;font-size:12px;color:$color'>";

		
		$method_text=$tpl->_ENGINE_parse_body($method[$ligne["method"]]);
		
		$action_text=$tpl->_ENGINE_parse_body("{$action[$ligne["type"]]}");

		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:16px;color:$color'>{$ligne["zDate"]}</strong>",
						"$js<strong  style='font-size:16px;color:$color'>$action_text$text_reverse</strong><a>",
						"<span  style='font-size:16px;color:$color'>$method_text</span>",
						
						"$js<strong style='font-size:16px;color:$color'>{$ligne["pattern"]}</strong></a>",
						"$js<span style='font-size:16px;color:$color'>{$ligne["description"]}</span>",
						"<center>$delete</center>")
		);
	}


	echo json_encode($data);



}
