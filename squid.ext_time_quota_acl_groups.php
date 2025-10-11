<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');$GLOBALS["VERBOSE"]=true;}	
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}

if(isset($_GET["items-list"])){items_list();exit;}
if(isset($_POST["acl-rule-link"])){items_link();exit;}
if(isset($_POST["acl-rule-link-delete"])){items_unlink();exit;}
if(isset($_POST["acl-rule-link-enabled"])){items_enabled();exit;}

items_js();

function item_delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE ID='$ID'");	
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");	
}


function item_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["item-id"];
	$gpid=$_POST["ID"];
	$q=new mysql_squid_builder();

	$sqladd="INSERT INTO webfilters_sqitems (pattern,gpid,enabled) 
	VALUES ('{$_POST["item-pattern"]}','$gpid','1');";
	
	$sql="UPDATE webfilters_sqitems SET pattern='{$_POST["item-pattern"]}' WHERE ID='$ID'";	
	if($ID<1){$sql=$sqladd;}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();	
	$sock->getFrameWork("squid.php?build-smooth=yes");	
}
	


function items_js(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	
	$objects=$tpl->_ENGINE_parse_body("{objects}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_item=$tpl->_ENGINE_parse_body("{link_object}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$reverse=$tpl->_ENGINE_parse_body("{reverse}");
	$t=$_GET["t"];
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$tOrg=$_GET["tOrg"];
	$mainrule=$_GET["mainrule"];	
	
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT QuotaType FROM ext_time_quota_acl WHERE ID=$mainrule","artica_backup"));
	$QuotaType=$ligne["QuotaType"];
	
	$search="	searchitems : [
		{display: '$items', name : 'GroupName'},
		],";
	
	$html="
	<table class='table-items-$t' style='display: none' id='table-items-$t' style='width:99%'></table>
<script>
var DeleteAclKey=0;
$(document).ready(function(){
$('#table-items-$t').flexigrid({
	url: '$page?items-list=yes&ID=$ID&t=$t&aclid={$_GET["aclid"]}',
	dataType: 'json',
	colModel : [
		{display: '$objects', name : 'gpid', width : 415, sortable : true, align: 'left'},
		{display: '$items', name : 'items', width : 69, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'enable', width : 31, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'del', width : 31, sortable : false, align: 'center'},
		
	],
buttons : [
	{name: '$new_item', bclass: 'add', onpress : LinkAclItem$t},
	{name: '$new_group', bclass: 'add', onpress : LinkAddAclItem$t},
		],	

	sortname: 'groupid',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 605,
	height: 350,
	singleSelect: true
	
	});   
});
function LinkAclItem$t() {
	Loadjs('squid.BrowseAclGroups.php?callback=LinkAclRuleGpid{$_GET["aclid"]}&FilterType=$QuotaType');
	
}	

function LinkAddAclItem$t(){
	Loadjs('squid.acls.groups.php?AddGroup-js=-1&link-acl={$_GET["aclid"]}&table-acls-t=$t&FilterType=$QuotaType&ACLType=session-time');
}

	var x_LinkAclRuleGpid{$_GET["aclid"]}= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-items-$t').flexReload();
		$('#table-$t').flexReload();
	}	

function LinkAclRuleGpid{$_GET["aclid"]}(gpid){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-link', '{$_GET["aclid"]}');
		XHR.appendData('gpid', gpid);
		XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid{$_GET["aclid"]});  		
	}
	
	
	var x_DeleteObjectLinks$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#row'+DeleteAclKey).remove();
		$('#table-$t').flexReload();
	}	
	
	
	function DeleteObjectLinks$t(mkey){
		DeleteAclKey=mkey;
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-link-delete', mkey);
		XHR.sendAndLoad('$page', 'POST',x_DeleteObjectLinks$t);
		  		
	}
	
	
	
	function ChangeEnabled$t(mkey){
		var value=0;
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-link-enabled', mkey);
		XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid{$_GET["aclid"]});
	}

	
</script>
	
	";
	
	echo $html;
	
}

function items_enabled(){
	$md5=$_POST["acl-rule-link-enabled"];
	
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT enabled FROM ext_time_quota_acl_link WHERE zmd5='$md5'","artica_backup"));
	if($ligne["enabled"]==0){$value=1;}else{$value=0;}	
	
	$sql="UPDATE ext_time_quota_acl_link SET enabled=$value WHERE zmd5='$md5'";
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function items_unlink(){
	$md5=$_POST["acl-rule-link-delete"];
	$sql="DELETE FROM ext_time_quota_acl_link WHERE zmd5='$md5'";
	$q=new mysql();
	$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function items_link(){
	$aclid=$_POST["acl-rule-link"];
	$gpid=$_POST["gpid"];
	$md5=md5($aclid.$gpid);
	$sql="INSERT IGNORE INTO ext_time_quota_acl_link (zmd5,ruleid,groupid,enabled) VALUES('$md5','$aclid','$gpid',1)";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}


function items_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$ID=$_GET["aclid"];
	$aclid=$_GET["aclid"];
	$acl=new squid_acls();
	$t0=$_GET["t"];
	$database="artica_backup";
	$search='%';
	$table="ext_time_quota_acl_link";
	$FORCE_FILTER=null;
	
	$page=1;

	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No datas");}
	
	$table="(SELECT *  FROM ext_time_quota_acl_link WHERE ruleid=$aclid) as t";
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	if(!is_numeric($rp)){$rp=50;}
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $FORCE_FILTER $ORDER $limitSql";	
	if($GLOBALS["VERBOSE"]){echo $sql."<br>\n";}
	
	$results = $q->QUERY_SQL($sql,$database);
	$total=mysqli_num_rows($results);
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysqli_num_rows($results)==0){json_error_show("No item");}
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	
	$q2=new mysql_squid_builder();
	$acl=new squid_acls_groups();
	
	if($_POST["qtype"]=="GroupName"){
		if($_POST["query"]<>null){
			$searchGroupName=string_to_flexregex();
		}
	}
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$val=0;
		$mkey=$ligne["zmd5"];
		$color="black";
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		$arrayF=$acl->FlexArray($ligne["groupid"],$ligne["enabled"]);
		$delete=imgsimple("delete-24.png",null,"DeleteObjectLinks$t0('$mkey')");
		$enable=Field_checkbox("enable-$mkey", 1,$ligne["enabled"],"ChangeEnabled$t0('$mkey')");
		
		
	$data['rows'][] = array(
		'id' => "$mkey",
		'cell' => array($arrayF["ROW"],
		"<span style='font-size:14px;font-weight:bold;color:$color'>{$arrayF["ITEMS"]}</span>",
		$enable,
		$delete)
		);
	}
	
	
	echo json_encode($data);	
}