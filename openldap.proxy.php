<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',1);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
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
	if(isset($_GET["IDP"])){connection_settings();exit;}
	if(isset($_POST["hostname"])){connection_save();exit;}
	if(isset($_GET["items-rules"])){items();exit;}
	if(isset($_POST["del-ID"])){connection_delete();exit;}
	if(isset($_POST["enable-ID"])){connection_enable();exit;}
	
$usersmenus=new usersMenus();
if(isset($_GET["js"])){js();exit;}
if($usersmenus->AsArticaAdministrator==false){die("DIE " .__FILE__." Line: ".__LINE__);exit;}
if(isset($_GET["proxy-parameters"])){proxy_parameters();exit;}
popup();

function popup(){
	$tpl=new templates();
	$users=new usersMenus();
	$checks=true;
	if(!$users->LDAP_BACKLDAP){$checks=false;}
	if(!$users->LDAP_BACKMETA){$checks=false;}
	if(!$checks){FATAL_ERROR_SHOW_128("{ldap_doesnt_support_proxy}");return;}
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=300;
	$TB_WIDTH=790;
	
	
	$t=time();
	$new_entry=$tpl->_ENGINE_parse_body("{new_connection}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$connection=$tpl->_ENGINE_parse_body("{connection}");
	$title=$tpl->_ENGINE_parse_body("{rules}:&nbsp;&laquo;{ldap_proxy}&raquo;");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$ask_delete_rule=$tpl->javascript_parse_text("{delete_this_rule}");
	$proxy_parameters=$tpl->_ENGINE_parse_body("{proxy_parameters}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: '$proxy_parameters', bclass: 'Reconf', onpress : ProxyLdapParameters},
	{name: '$compile_rules', bclass: 'Reconf', onpress : OpenLDAPCompilesRules},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	],	";
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items-rules=yes&t=$t',
	dataType: 'json',
	colModel : [	
		{display: '$connection', name : 'cnxstring', width :626, sortable : true, align: 'left'},
		{display: 'RWM', name : 'none', width :31, sortable : false, align: 'center'},
		{display: '$enable', name : 'enabled', width :31, sortable : true, align: 'center'},
		
		{display: '&nbsp;', name : 'action', width :31, sortable : false, align: 'center'},

	],
	$buttons

	searchitems : [
		{display: '$connection', name : 'cnxstring'},
		

	],
	sortname: 'cnxstring',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function ItemHelp$t(){
	s_PopUpFull('http://www.mail-appliance.org/index.php?cID=316','1024','900');
}


var x_NewGItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    $('#flexRT$t').flexReload();
}

function NewGItem$t(){
	YahooWin('650','$page?IDP=&t=$t','$new_entry');
	
}
function GItem$t(ID,title){
	YahooWin('650','$page?IDP='+ID+'&t=$t',title);
	
}

var x_DeleteCnxString$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#row'+mem$t).remove();
}

function OpenLDAPEnablecnx(ID){
	var value=0;
	if(document.getElementById('enable-'+ID).checked){value=1;}
 	var XHR = new XHRConnection();
    XHR.appendData('enable-ID',ID);
    XHR.appendData('value',value);
    XHR.sendAndLoad('$page', 'POST',x_NewGItem$t);		
}

function ProxyLdapParameters(){
	YahooWin4('650','$page?proxy-parameters=yes&t=$t','$proxy_parameters');
}




function DeleteCnxString$t(ID){
	if(confirm('$ask_delete_rule')){
		mem$t=ID;
 		var XHR = new XHRConnection();
      	XHR.appendData('del-ID',ID);
      	XHR.sendAndLoad('$page', 'POST',x_DeleteCnxString$t);		
	
	}

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
	$table="openldap_proxy";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	$OpenLdapProxySuffix=$sock->GET_INFO("OpenLdapProxySuffix");
	if($OpenLdapProxySuffix==null){$OpenLdapProxySuffix="dc=meta";}	

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
	

	if(!is_numeric($rp)){$rp=1;}
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

	if(mysqli_num_rows($results)==0){json_error_show("no data");}
	
	while ($ligne = mysqli_fetch_assoc($results)) {
	$ID=$ligne["ID"];
	
		$articabranch=$ligne["articabranch"];
		$suffixlink=$ligne["suffixlink"];
		if($suffixlink=="*"){$suffixlink="";}
		if($suffixlink==","){$suffixlink="";}
		if($suffixlink<>null){$suffixlink="$suffixlink,";}
		if($articabranch==1){$suffixlink=null;}
		$suffixmassage=$tpl->_ENGINE_parse_body( "<div style='font-size:11px'><i>{redirect_to_suffix}:$suffixlink$OpenLdapProxySuffix</i></div>");

	$color="black";
	$delete=imgsimple("delete-24.png","","DeleteCnxString$t('$ID')");
	
	$enable=Field_checkbox("enable-{$ligne["ID"]}", 1,$ligne["enabled"],"OpenLDAPEnablecnx({$ligne["ID"]})");
	
	if($ligne["enabled"]==0){$color="#8a8a8a";}
	$urljs="<a href=\"javascript:blur();\" OnClick=\"javascript:GItem$t('$ID','{$ligne["cnxstring"]}');\"
	style='font-size:16px;color:$color;text-decoration:underline'>";
	
	
	$rwm=imgsimple("table-show-24.png",null,"Loadjs('openldap.proxy.rwm.php?ID=$ID')");
	
	$data['rows'][] = array(
		'id' => "$ID",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["cnxstring"]}</a>$suffixmassage</span>",
			"<span style='font-size:16px;color:$color'>$rwm</span>",
			"<span style='font-size:16px;color:$color'>$enable</span>",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}

function proxy_parameters(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=$_GET["t"];
	$EnableOpenLdapProxy=$sock->GET_INFO("EnableOpenLdapProxy");
	$OpenLdapProxySuffix=$sock->GET_INFO("OpenLdapProxySuffix");
	if($OpenLdapProxySuffix==null){$OpenLdapProxySuffix="dc=meta";}
	if(!is_numeric($EnableOpenLdapProxy)){$EnableOpenLdapProxy=0;}
	$html="
	<div id='$t-div'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{enable_proxy_mode}:</td>
		<td>". Field_checkbox("EnableOpenLdapProxy", 1,$EnableOpenLdapProxy,"EnableOpenLdapProxyCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{ldap_suffix}:</td>
		<td>". Field_text("OpenLdapProxySuffix", $OpenLdapProxySuffix,"font-size:16px;width:350px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>
			<hr>". button("{apply}","SaveProxyLdap()","18px")."</td>
		</tr>
	</table>
	<script>
	var x_ChangeLdapSuffixPerform= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		document.getElementById('$t-div').innerHTML='';
		$('#flexRT$t').flexReload();
	}		
			
	function EnableOpenLdapProxyCheck(){
			var EnableOpenLdapProxy=0;
			if(document.getElementById('EnableOpenLdapProxy').checked){EnableOpenLdapProxy=1;}
			document.getElementById('OpenLdapProxySuffix').disabled=true;
			if(EnableOpenLdapProxy==1){
				document.getElementById('OpenLdapProxySuffix').disabled=false;
			}
			
	}

	function SaveProxyLdap(){
			var XHR = new XHRConnection();
			var EnableOpenLdapProxy=0;
			if(document.getElementById('EnableOpenLdapProxy').checked){EnableOpenLdapProxy=1;}
			XHR.appendData('OpenLdapProxySuffix',document.getElementById('OpenLdapProxySuffix').value);
			XHR.appendData('EnableOpenLdapProxy',EnableOpenLdapProxy);
			AnimateDiv('$t-div');
			XHR.sendAndLoad('$page', 'POST',x_ChangeLdapSuffixPerform);			
	}	
	EnableOpenLdapProxyCheck();
</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function EnableOpenLdapProxySave(){
	$sock=new sockets();
	$sock->SET_INFO("EnableOpenLdapProxy", $_POST["EnableOpenLdapProxy"]);
	$sock->SET_INFO("OpenLdapProxySuffix", $_POST["OpenLdapProxySuffix"]);
	
}

function connection_settings(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$ID=$_GET["IDP"];
	$tt=$_GET["t"];
	$t=time();
	if(!is_numeric($ID)){$ID=0;}
	$btnmae="{add}";
	if($ID>0){
		$q=new mysql();
		$btnmae="{apply}";
		$sql="SELECT * FROM openldap_proxy WHERE ID='$ID'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));		
		
	}
	
	$EnableOpenLdapProxy=$sock->GET_INFO("EnableOpenLdapProxy");
	$OpenLdapProxySuffix=$sock->GET_INFO("OpenLdapProxySuffix");
	if($OpenLdapProxySuffix==null){$OpenLdapProxySuffix="dc=meta";}	
	
	if(!is_numeric($ligne["port"])){$ligne["port"]=389;}
	if($ligne["suffixlink"]==null){$ligne["suffixlink"]="dc=organizations";}
	
	
	$html="
	<div id='$t-div'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{hostname}:</td>
		<td>". Field_text("hostname-$t", $ligne["hostname"],"font-size:16px;width:300px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{port}:</td>
		<td>". Field_text("port-$t", $ligne["port"],"font-size:16px;width:60px")."</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{ldap_suffix}:</td>
		<td>". Field_text("suffix-$t", $ligne["suffix"],"font-size:13px;width:300px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{is_an_articasrv}:</td>
		<td>". Field_checkbox("articabranch-$t", 1,$ligne["articabranch"],"articabranchCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{link_to_branch}:</td>
		<td style='font-size:13px'>". Field_text("suffixlink-$t", $ligne["suffixlink"],"font-size:13px;width:240px;text-align:right").",$OpenLdapProxySuffix</td>
	</tr>	
	
	<tr>
		<td class=legend style='font-size:16px'>{ldap_user_dn}:</td>
		<td>".Field_text("username-$t",$ligne["username"],"font-size:13px;;width:300px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:16px'>{ldap_password}:</td>
		<td>".Field_password("password-$t",$ligne["password"],"font-size:16px;width:190px")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>
			<hr>". button("$btnmae","Savecnxset$t()","18px")."</td>
		</tr>
	</table>
	<script>
	
	
	var x_Savecnxset$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		document.getElementById('$t-div').innerHTML='';
		$('#flexRT$tt').flexReload();
	}		
			
	
	function Savecnxset$t(){
			var articabranch=0;
			var XHR = new XHRConnection();
			if(document.getElementById('articabranch-$t').checked){articabranch=1;}
			var pp=encodeURIComponent(document.getElementById('password-$t').value);
			XHR.appendData('hostname',document.getElementById('hostname-$t').value);
			XHR.appendData('port',document.getElementById('port-$t').value);
			XHR.appendData('suffix',document.getElementById('suffix-$t').value);
			XHR.appendData('username',document.getElementById('username-$t').value);
			XHR.appendData('suffixlink',document.getElementById('suffixlink-$t').value);
			XHR.appendData('articabranch',articabranch);
			XHR.appendData('password',pp);
			XHR.appendData('ID','$ID');
			AnimateDiv('$t-div');
			XHR.sendAndLoad('$page', 'POST',x_Savecnxset$t);			
	}
	
	function articabranchCheck(){
		document.getElementById('suffixlink-$t').disabled=false;
		if(document.getElementById('articabranch-$t').checked){
			document.getElementById('suffixlink-$t').disabled=true;
		}
	
	}
	articabranchCheck();
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
}

function connection_enable(){
	$q=new mysql();
	$sql="UPDATE openldap_proxy SET enabled='{$_POST["value"]}' WHERE ID='{$_POST["enable-ID"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}		
}

function connection_delete(){
	
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM openldap_proxy WHERE ID='{$_POST["del-ID"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	
}

function connection_save(){
	$q=new mysql();
$r=explode(",",$_POST["username"]);
$uu=trim($r[0]);
if(preg_match("^[a-zA-Z]+=(.*?)[$|,]#", $uu,$re)){$uu=$re[1];}
if(preg_match("^cn=(.*?)#i", $uu,$re)){$uu=$re[1];}
$uu=str_replace("CN=", "", $uu);
$uu=str_replace("cn=", "", $uu);
$uu=str_replace("Cn=", "", $uu);
$uu=str_replace("cN=", "", $uu);
$cnxstring="ldap://$uu@{$_POST["hostname"]}:{$_POST["port"]}/{$_POST["suffix"]}";
	
	$_POST["password"]=addslashes(url_decode_special_tool($_POST["password"]));
	$sql="INSERT INTO openldap_proxy(hostname,port,suffix,username,password,cnxstring,enabled,articabranch,suffixlink)
	VALUES('{$_POST["hostname"]}','{$_POST["port"]}','{$_POST["suffix"]}',
	'{$_POST["username"]}','{$_POST["password"]}','$cnxstring',1,'{$_POST["articabranch"]}','{$_POST["suffixlink"]}')";
	
	if($_POST["ID"]>0){
		$sql="UPDATE openldap_proxy SET 
			hostname='{$_POST["hostname"]}',
			port='{$_POST["port"]}',
			suffix='{$_POST["suffix"]}',
			username='{$_POST["username"]}',
			password='{$_POST["password"]}',
			cnxstring='$cnxstring',
			articabranch='{$_POST["articabranch"]}',
			suffixlink='{$_POST["suffixlink"]}'
			WHERE ID='{$_POST["ID"]}'";
	}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
		
	
	
	
}
