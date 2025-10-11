<?php
	$GLOBALS["ICON_FAMILY"]="PARAMETERS";
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once('ressources/class.samba.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	
$usersmenus=new usersMenus();
if($usersmenus->AsArticaAdministrator==false){header("content-type: application/x-javascript");die("alert('No privileges');");}

if(isset($_GET["tabs"])){tabs();exit;}

if(isset($_GET["js"])){js_connection();exit;}
if(isset($_GET["connection-popup"])){connection_popup();exit;}
if(isset($_GET["remove-connection-js"])){remove_connection_js();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["items"])){items();exit;}
if(isset($_POST["connection-id"])){save();exit;}
if(isset($_POST["remove-connection"])){remove_connection_perform();exit;}
if(isset($_GET["help"])){help();exit;}
js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{TEXT_TO_CSV}");
	echo "YahooWin(922,'$page?tabs=yes','$title',true);";
}

function js_connection(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["connection-id"];
	$t=$_GET["t"];
	
	if($ID==0){$title=$tpl->javascript_parse_text("{new_connection}");}
	if($ID>0){
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT connection FROM texttoldap WHERE ID='$ID'","artica_backup"));
		$connection=$ligne["connection"];
		$title=$tpl->javascript_parse_text("{connection}: $connection");
	}
	
	echo "YahooWin2('700','$page?connection-popup=yes&connection-id=$ID&t=$t','$title')";	
}

function remove_connection_js(){
	$ID=$_GET["remove-connection-js"];
	header("content-type: application/x-javascript");
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT connection FROM texttoldap WHERE ID='$ID'","artica_backup"));
	$connection=$ligne["connection"];
	$tpl=new templates();
	$page=CurrentPageName();
	$delete=$tpl->javascript_parse_text("{delete} $connection ?");
	$t=time();
	echo"
			
var xSave$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		var ID='$ID';
		$('#flexRT{$_GET["t"]}').flexReload();
		$('#flexRT{$_GET["tt"]}').flexReload();
		ExecuteByClassName('SearchFunction');
	}
	
function Save$t(){
	if( !confirm('$delete') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('remove-connection',  '$ID');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();";
}

function remove_connection_perform(){
	$ID=$_POST["remove-connection"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM texttoldap WHERE ID=$ID","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}

function connection_popup(){
	$ID=$_GET["connection-id"];
	$tpl=new templates();
	$q=new mysql();
	$page=CurrentPageName();
	$bt_title="{add}";
	
	

	
	if($ID==0){$title=$tpl->javascript_parse_text("{new_connection}");}
	if($ID<>null){
		$bt_title="{apply}";
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM texttoldap WHERE ID='$ID'","artica_backup"));
	}
	$t=time();
	$ldap_group_text="-";
	if($ligne["ldapgroup"]>0){
		$gp=new groups($ligne["ldapgroup"]);
		$ldap_group_text=$gp->groupName;
	}
	
	$html="
	<div style='font-size:20px'>$title</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{connection}:</td>
		<td>". Field_text("connection-$t",$ligne["connection"],"font-size:16px;width:300px")."</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:16px'>{hostname}:</td>
		<td>". Field_text("hostname-$t",$ligne["hostname"],"font-size:16px;width:250px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{shared_folder}:</td>
		<td>". Field_text("folder-$t",$ligne["folder"],"font-size:16px;width:250px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{filename}:</td>
		<td>". Field_text("filename-$t",$ligne["filename"],"font-size:16px;width:250px")."</td>
	</tr>							
	<tr>
		<td class=legend style='font-size:16px'>{username}:</td>
		<td>". Field_text("username-$t",$ligne["username"],"font-size:16px;width:250px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>". Field_password("password-$t",$ligne["password"],"font-size:16px;width:250px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>{default_ldap_group}:</td>
		<td>". Field_hidden("ldapgroup-$t",$ligne["ldapgroup"])."<span id='group-text-$t' style='font-size:16px;'>$ldap_group_text</span>&nbsp;&nbsp;&nbsp;". button_browse_ldap_group("ChooseGroup$t")."</td>
	</tr>				
	<tr>
		<td colspan=2 align='right'>". button($bt_title,"Save$t()",18)."</td>
	</tr>
	</table>
<script>
	var xSave$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		var ID='$ID';
		$('#flexRT{$_GET["t"]}').flexReload();
		$('#flexRT{$_GET["tt"]}').flexReload();
		ExecuteByClassName('SearchFunction');
		if(ID.length==0){YahooWin2Hide();}
	}
	
	function SaveCHK$t(e){
		if(!checkEnter(e)){return;}
		Save$t();
	}
	
	function ChooseGroup$t(num,groupname){
		document.getElementById('group-text-$t').innerHTML=groupname;
		document.getElementById('ldapgroup-$t').value=num;
	}
	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('connection-id',  '$ID');
	XHR.appendData('connection',  encodeURIComponent(document.getElementById('connection-$t').value));
	XHR.appendData('hostname',  encodeURIComponent(document.getElementById('hostname-$t').value));
	XHR.appendData('folder',  encodeURIComponent(document.getElementById('folder-$t').value));
	XHR.appendData('filename',  encodeURIComponent(document.getElementById('filename-$t').value));
	XHR.appendData('username',  encodeURIComponent(document.getElementById('username-$t').value));
	XHR.appendData('password',  encodeURIComponent(document.getElementById('password-$t').value));
	XHR.appendData('ldapgroup',  encodeURIComponent(document.getElementById('ldapgroup-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	</script>	";
echo $tpl->_ENGINE_parse_body($html);
}

function help(){
	$tpl=new templates();
	$html="<div class=explain style='font-size:16px'>
	<strong style='font-size:18px'>{TEXT_MEMBERS_TO_CSV}</strong><br>
	{csv_to_ldap_howto}
	<hr>
	<a href='ressources/csvToLdap.csv' style='text-decoration:underline;font-size:18px'>csvToLdap.csv</a>		
	</div>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql();
	$array["table"]='{connections}';
	$array["schedule"]='{schedules}';
	$fontsize=14;
	
	foreach ($array as $num=>$ligne){
		if($num=="schedule"){
			$tab[]= $tpl->_ENGINE_parse_body("<li><a href=\"schedules.php?ForceTaskType=70\" style='font-size:14px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
		
	}
	
	echo build_artica_tabs($tab, "TEXT_TO_CSV_TAB");	
	
}
function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	
	$type=$tpl->javascript_parse_text("{type}");
	$from=$tpl->_ENGINE_parse_body("{from}");
	$to=$tpl->javascript_parse_text("{to}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$delete=$tpl->javascript_parse_text("{delete} {zone} ?");
	$ldap_group=$tpl->javascript_parse_text("{ldap_group}");
	$new_connection=$tpl->javascript_parse_text("{new_connection}");
	$groupname=$tpl->javascript_parse_text("{groupname}");
	$filename=$tpl->javascript_parse_text("{filename}");
	$server=$tpl->javascript_parse_text("{server}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$folder=$tpl->javascript_parse_text("{folder}");
	$connection=$tpl->javascript_parse_text("{connection}");
	$title=$tpl->_ENGINE_parse_body("{TEXT_TO_CSV}");
	$t=time();
	$help=$tpl->_ENGINE_parse_body("{help}");
	
	$buttons="
	buttons : [
	{name: '$new_connection', bclass: 'add', onpress : NewRule$tt},
	{name: '$help', bclass: 'Help', onpress : Help$tt},
	],";
	
	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?items=yes&ruleid={$_GET["ruleid"]}&t={$_GET["t"]}&t-rule={$_GET["t-rule"]}&tt=$tt',
	dataType: 'json',
	colModel : [
	{display: '$connection', name : 'connection', width :120, sortable : true, align: 'left'},
	{display: '$folder', name : 'folder', width :232, sortable : true, align: 'left'},
	{display: '$server', name : 'server', width :93, sortable : true, align: 'left'},
	{display: '$filename', name : 'filename', width :120, sortable : true, align: 'left'},
	{display: '$ldap_group', name : 'ldap_group', width :120, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$connection', name : 'connection'},
	{display: '$folder', name : 'folder'},
	{display: '$server', name : 'server'},
	{display: '$filename', name : 'filename'},
	],
	sortname: 'ID',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	}
	
	var xNewRule$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$tt').flexReload();
	$('#flexRT{$_GET["t"]}').flexReload();
	$('#flexRT{$_GET["t-rule"]}').flexReload();
	}
	
	function Apply$tt(){
			Loadjs('shorewall.php?apply-js=yes',true);
	}
	
	function Link$tt(ID){
	var XHR = new XHRConnection();
	XHR.appendData('link-group', ID);
	XHR.appendData('ruleid', '{$_GET["ruleid"]}');
	XHR.sendAndLoad('$page', 'POST', xNewRule$tt);
	}
	
	
	function Help$tt(){
		YahooWin3('600','$page?help=yes','$help',true);
	}
	
	
	function NewRule$tt(){
			Loadjs('$page?js=yes&connection-id=0&t=$tt',true);
	}
	function Delete$tt(zmd5){
		if(confirm('$delete')){
				var XHR = new XHRConnection();
				XHR.appendData('policy-delete', zmd5);
				XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
		}
	}
	function RuleEnable$tt(ID,md5){
		var XHR = new XHRConnection();
		XHR.appendData('rule-enable', ID);
		if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
		XHR.sendAndLoad('$page', 'POST',xRuleEnable$tt);
	}
	var x_LinkAclRuleGpid$tt= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-$t').flexReload();
		$('#flexRT$tt').flexReload();
		ExecuteByClassName('SearchFunction');
	}
	function FlexReloadRulesRewrite(){
		$('#flexRT$t').flexReload();
	}
	
	function MoveRuleDestination$tt(mkey,direction){
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-move', mkey);
	XHR.appendData('direction', direction);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
	}
	
	function MoveRuleDestinationAsk$tt(mkey,def){
	var zorder=prompt('Order',def);
	if(!zorder){return;}
			var XHR = new XHRConnection();
			XHR.appendData('rules-destination-move', mkey);
			XHR.appendData('rules-destination-zorder', zorder);
			XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$tt);
	}
Start$tt();
</script>
";
echo $html;
		
	
	
}
function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();

	$t=$_GET["tt"];
	$search='%';
	$table="texttoldap";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;

	if(!$q->TABLE_EXISTS("texttoldap","artica_backup")){$q->BuildTables();}

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();

	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS("texttoldap","artica_backup");
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");

	$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysqli_num_rows($results)==0){json_error_show("no data $sql");}

	$fontsize="14";

	while ($ligne = mysqli_fetch_assoc($results)) {
		$color="black";
		$NICNAME=null;
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?remove-connection-js={$ligne["ID"]}&t={$_GET["t"]}&t-rule={$_GET["t-rule"]}&tt={$_GET["tt"]}')");

		

		$editjs="<a href=\"javascript:blur();\"
		OnClick=\"Loadjs('$MyPage?js=yes&connection-id={$ligne['ID']}&t={$_GET["t"]}',true);\"
		style='font-size:{$fontsize}px;font-weight:bold;color:$color;text-decoration:underline'>";

		$connection=$ligne["connection"];
		$folder=$ligne["folder"];
		$hostname=$ligne["hostname"];
		$filename=$ligne["filename"];
		$ldap_group=$ligne["ldapgroup"];
		$ldap_group_text="-";
		if($ligne["ldapgroup"]>0){
			$gp=new groups($ligne["ldapgroup"]);
			$ldap_group_text=$gp->groupName;
		}

		

		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$connection</span>",
						"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$editjs$folder</a></span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$hostname</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$filename</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$ldap_group_text</span>",
						"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>"

						,)
		);
	}


	echo json_encode($data);

}

function save(){
	$q=new mysql();
	$table="texttoldap";
	$tpl=new templates();
	
	
	$editF=false;
	$ID=$_POST["connection-id"];
	unset($_POST["connection-id"]);
	
	foreach ($_POST as $key=>$value){
		$value=url_decode_special_tool($value);
		$fields[]="`$key`";
		$values[]="'".mysql_escape_string2($value)."'";
		$edit[]="`$key`='".mysql_escape_string2($value)."'";
	
	}
	
	$sql_edit="UPDATE `$table` SET ".@implode(",", $edit)." WHERE ID='$ID'";
	$sql="INSERT IGNORE INTO `$table` (".@implode(",", $fields).") VALUES (".@implode(",", $values).")";
	if($ID>0){$sql=$sql_edit;}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Mysql error: `$q->mysql_error`";;return;}
	$tpl=new templates();
	$tpl->javascript_parse_text("{success}");	
	
}
