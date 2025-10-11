<?php
$GLOBALS["ICON_FAMILY"]="PARAMETERS";
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');

	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once('ressources/class.samba.inc');
	
	if(isset($_POST["EnableOpenLdapProxy"])){EnableOpenLdapProxySave();exit;}
	if(isset($_GET["proxyid"])){attribute_popup();exit;}
	if(isset($_POST["proxyid"])){attribute_save();exit;}
	if(isset($_GET["items-rules"])){items();exit;}
	if(isset($_POST["del-ID"])){attribute_delete();exit;}
	if(isset($_POST["ADADD"])){ADADD();exit;}
	if(isset($_POST["ADLDAP"])){DEFAULT_ADD();exit;}
	
	
	
$usersmenus=new usersMenus();
if($usersmenus->AsArticaAdministrator==false){die("DIE " .__FILE__." Line: ".__LINE__);exit;}
if(isset($_GET["popup"])){popup();exit;}
js();



function js(){
	$ID=$_GET["ID"];
	$q=new mysql();
	$tpl=new templates();
	$page=CurrentPageName();
	$sql="SELECT cnxstring FROM openldap_proxy WHERE ID='$ID'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$title=$ligne["cnxstring"];
	echo "YahooWin4('650','$page?popup=yes&ID=$ID','$title');";
	
}



function popup(){
	$tpl=new templates();
	$users=new usersMenus();
	$checks=true;
	if(!$users->LDAP_BACKLDAP){$checks=false;}
	if(!$users->LDAP_BACKMETA){$checks=false;}
	if(!$checks){FATAL_ERROR_SHOW_128("{ldap_doesnt_support_proxy}");return;}
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=630;
	
	
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_rewrite_rule}");
	$attribute=$tpl->_ENGINE_parse_body("{attribute}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$match=$tpl->_ENGINE_parse_body("{match}");
	$ask_delete_rule=$tpl->javascript_parse_text("{ask_delete_rule}");
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: 'Active Directory', bclass: 'Add', onpress : NewADItems$t},
	{name: 'OpenLDAP', bclass: 'Add', onpress : NewOLDItems$t},
	
	],	";
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items-rules=yes&t=$t&ID=$ID',
	dataType: 'json',
	colModel : [	
		{display: '$attribute', name : 'attribute', width :200, sortable : true, align: 'left'},
		{display: '$type', name : 'type', width :128, sortable : false, align: 'left'},
		{display: '$match', name : 'match', width :200, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'action', width :31, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$attribute', name : 'attribute'},
		{display: '$type', name : 'type'},
		{display: '$match', name : 'match'},
		
		

	],
	sortname: 'attribute',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=305','1024','900');
}




var x_NewGItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#flexRT$t').flexReload();
}

function NewADItems$t(){
 		var XHR = new XHRConnection();
      	XHR.appendData('ADADD',$ID);
      	XHR.sendAndLoad('$page', 'POST', x_NewGItem$t);

}
function NewOLDItems$t(){
		var XHR = new XHRConnection();
      	XHR.appendData('ADLDAP',$ID);
      	XHR.sendAndLoad('$page', 'POST', x_NewGItem$t);
}
function NewGItem$t(){
	YahooWin5('550','$page?ID=&t=$t&proxyid=$ID','$new_entry');
	
}
function GItem$t(ID,title){
	YahooWin5('550','$page?ID='+ID+'&t=$t&proxyid=$ID',title);
	
}

var x_DeleteAttribute$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#row'+mem$t).remove();
}

function DeleteAttribute$t(ID){
		mem$t=ID;
 		var XHR = new XHRConnection();
      	XHR.appendData('del-ID',ID);
      	XHR.sendAndLoad('$page', 'POST',x_DeleteAttribute$t);		
	}

</script>";
	
	echo $html;
	
//openldap_proxy	
}
function items(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$sock=new sockets();		
	
	$search='%';
	$table="openldap_proxyattrs";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=" AND proxyid={$_GET["ID"]}";

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$color="black";
		$delete=imgsimple("delete-24.png","","DeleteAttribute$t('$ID')");
		$explain="{$ligne["attribute"]} [{$ligne["match"]}] -&raquo; {$ligne["match"]}";
		$urljs="<a href=\"javascript:blur();\" OnClick=\"javascript:GItem$t('$ID','$explain');\"
		style='font-size:16px;color:$color;text-decoration:underline'>";
	
	$data['rows'][] = array(
		'id' => "$ID",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["attribute"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["type"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["match"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}





function attribute_popup(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$proxyid=$_GET["proxyid"];
	$tt=$_GET["t"];
	$t=time();
	if(!is_numeric($ID)){$ID=0;}
	$btnmae="{add}";
	if($ID>0){
		$q=new mysql();
		$btnmae="{apply}";
		$sql="SELECT * FROM openldap_proxyattrs WHERE ID='$ID'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));		
		
	}
	
	$hash["attribute"]="attribute";
	$hash["objectClass"]="objectClass";
	
	$EnableOpenLdapProxy=$sock->GET_INFO("EnableOpenLdapProxy");
	$OpenLdapProxySuffix=$sock->GET_INFO("OpenLdapProxySuffix");
	if($OpenLdapProxySuffix==null){$OpenLdapProxySuffix="dc=meta";}	
	
	if(!is_numeric($ligne["port"])){$ligne["port"]=389;}
	if($ligne["suffixlink"]==null){$ligne["suffixlink"]="dc=organizations";}
	
	
	$html="
	<div id='$t-div'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{attribute}:</td>
		<td>". Field_text("attribute-$t", $ligne["attribute"],"font-size:16px;width:250px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{type}:</td>
		<td>". Field_array_Hash($hash,"type-$t",$ligne["type"],"blur();",null,0,"font-size:16px")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{match}:</td>
		<td>". Field_text("match-$t", $ligne["match"],"font-size:16px;width:250px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>
			<hr>". button("$btnmae","Savecnxset$t()","18px")."</td>
		</tr>
	</table>
	<script>
	
	
	var x_Savecnxset$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('$t-div').innerHTML='';
		if(results.length>0){alert(results);return;}

		var ID=$ID;
		if(ID==0){YahooWin5Hide();}
		$('#flexRT$tt').flexReload();
	}		
			
	
	function Savecnxset$t(){
			var articabranch=0;
			var XHR = new XHRConnection();
			
			XHR.appendData('attribute',document.getElementById('attribute-$t').value);
			XHR.appendData('type',document.getElementById('type-$t').value);
			XHR.appendData('match',document.getElementById('match-$t').value);
			
			XHR.appendData('proxyid',$proxyid);
			XHR.appendData('ID','$ID');
			AnimateDiv('$t-div');
			XHR.sendAndLoad('$page', 'POST',x_Savecnxset$t);			
	}
	

</script>
";
	echo $tpl->_ENGINE_parse_body($html);
}


function attribute_delete(){
	
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM openldap_proxyattrs WHERE ID='{$_POST["del-ID"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	
}

function ADADD(){
	$q=new mysql();
	$proxyid=$_POST["ADADD"];
	$sql="INSERT INTO openldap_proxyattrs(`type`,`attribute`,`match`,`proxyid`)
	VALUES
	('attribute','uid','sAMAccountName','$proxyid'),
('objectClass','posixGroup','group','$proxyid'), 
('objectClass','posixAccount','person','$proxyid'),
('attribute','memberUid','member','$proxyid'),
('attribute','cn','name','$proxyid'),
('attribute','sn','sn','$proxyid'),
('attribute','mail','mail','$proxyid'),
('attribute','company','company','$proxyid'),
('attribute','entry','entry','$proxyid')
";
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	
}


function DEFAULT_ADD(){
	$proxyid=$_POST["ADLDAP"];
		$q=new mysql();
	$sql="INSERT INTO openldap_proxyattrs(`type`,`attribute`,`match`,`proxyid`)
	VALUES	
('attribute','memberUid','*','$proxyid'),
('attribute','gidNumber','*','$proxyid'),
('attribute','uid','*','$proxyid'),
('attribute','ou','*','$proxyid'),
('attribute','cn','*','$proxyid'),
('attribute','sn','*','$proxyid'),
('attribute','givenname','*','$proxyid'),
('attribute','mail','*','$proxyid'),
('attribute','telephonenumber','*','$proxyid'),
('objectclass','posixGroup','*','$proxyid'),
('attribute','associatedDomain','*','$proxyid'),
('objectclass','OrganizationalUnit','*','$proxyid')	
";
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}	
		
}

function attribute_save(){
	$q=new mysql();

	$sql="INSERT INTO openldap_proxyattrs(`attribute`,`type`,`match`,`proxyid`)
	VALUES('{$_POST["attribute"]}','{$_POST["type"]}','{$_POST["match"]}',
	'{$_POST["proxyid"]}')";
	
	if($_POST["ID"]>0){
		$sql="UPDATE openldap_proxyattrs SET 
			`attribute`='{$_POST["attribute"]}',
			`type`='{$_POST["type"]}',
			`match`='{$_POST["match"]}'
			WHERE ID='{$_POST["ID"]}'";
	}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
		
	
	
	
}
