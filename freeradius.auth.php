<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}
	// freeradius_db
	
	
	if(isset($_GET["connection-id-js"])){connection_id_js();exit;}
	if(isset($_GET["connection-form-id"])){connection_id();exit;}
	if(isset($_GET["connection-form"])){connection_form();exit;}
	if(isset($_POST["connectionname"])){connection_save();exit;}
	if(isset($_GET["query"])){connection_list();exit;}
	if(isset($_POST["EnableLocalLDAPServer"])){EnableLocalLDAPServer();exit;}
	if(isset($_POST["connection-delete"])){connection_delete();exit;}
	if(isset($_POST["EnableDisable"])){connection_enable();exit;}
	page();
function connection_id_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$connection_id=$_GET["connection-id-js"];
	$title=$tpl->javascript_parse_text("{session_manager}::{$_GET["ID"]}");
	$t=$_GET["t"];
	
	if($connection_id>0){
		$q=new mysql();
		$ligne=mysqli_fetch_array(
				$q->QUERY_SQL("SELECT * FROM freeradius_db WHERE ID=$connection_id","artica_backup"));
		$title=$tpl->javascript_parse_text($ligne["connectionname"]);
	}
	
	echo "YahooWin2('650','$page?connection-form-id=$connection_id&t=$t','$title')";
	
}


function connection_id(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$connection_id=$_GET["connection-form-id"];
	$CONNECTIONS_TYPE["ldap"]="{ldap}";
	$CONNECTIONS_TYPE["mysql_local"]="{local_mysql}";
	$CONNECTIONS_TYPE["ad"]="{ActiveDirectory}";
	if($connection_id>0){
		$q=new mysql();
		$ligne=mysqli_fetch_array(
				$q->QUERY_SQL("SELECT * FROM freeradius_db WHERE ID=$connection_id","artica_backup"));
	}
	
	$connect_type=Field_array_Hash($CONNECTIONS_TYPE, "connectiontype-$t",$ligne["connectiontype"],
			"ConnectTypeChangeForm$t()",null,0,"font-size:16px");
	
	$html="<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{connection_type}:</td>
		<td>$connect_type</td>		
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{connection_name}:</td>
		<td>". Field_text("connectionname-$t",$ligne["connectionname"],"font-size:16px;width:220px")."</td>		
	</tr>		
	</table>	
	<div id='cnx-$t'></div>
			
	
	<script>
		function ConnectTypeChangeForm$t(){
			var cnxt=document.getElementById('connectiontype-$t').value;
			LoadAjax('cnx-$t','$page?connection-form=yes&cnxt='+cnxt+'&connection-id=$connection_id&t=$t');
		
		}
		
		ConnectTypeChangeForm$t();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function connection_form(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$t=$_GET["t"];
	$connection_id=$_GET["connection-id"];	
	$cnxt=$_GET["cnxt"];
	
	if($cnxt=="ldap"){connection_form_ldap();exit;}
	if($cnxt=="ad"){connection_form_ad();exit;}
	if($cnxt=="mysql_local"){connection_form_mysql_local();exit;}

	
}
function connection_form_ad(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$connection_id=$_GET["connection-id"];
	$cnxt=$_GET["cnxt"];
	$btname="{add}";
	$array=array();
	if($connection_id>0){
		$btname="{apply}";
		$q=new mysql();
		$ligne=mysqli_fetch_array(
				$q->QUERY_SQL("SELECT params FROM freeradius_db WHERE ID=$connection_id","artica_backup")
		);
		$array=unserialize(base64_decode($ligne["params"]));

	}



	if($array["LDAP_FILTER"]==null){$array["LDAP_FILTER"]="(uid=%{%{Stripped-User-Name}:-%{User-Name}})";}
	if($array["PASSWORD_ATTRIBUTE"]==null){$array["PASSWORD_ATTRIBUTE"]="userPassword";}
	if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
	if($array["LDAP_DN"]==null){$array["LDAP_DN"]="user@domain.tld";}

	$tt=time();
	$html="
	<div id='$tt'></div>
	<table style='width:99%' class=form>
	<tr>
	<td class=legend style='font-size:16px'>{hostname}:</td>
	<td>". Field_text("LDAP_SERVER-$tt",$array["LDAP_SERVER"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{ldap_port}:</td>
		<td>". Field_text("LDAP_PORT-$tt",$array["LDAP_PORT"],"font-size:16px;padding:3px;width:90px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{ldap_suffix}:</td>
		<td>". Field_text("LDAP_SUFFIX-$tt",$array["LDAP_SUFFIX"],"font-size:16px;padding:3px;width:310px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{username} ({read}):</td>
		<td>". Field_text("LDAP_DN-$tt",$array["LDAP_DN"],"font-size:16px;padding:3px;width:310px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>". Field_password("LDAP_PASSWORD-$tt",$array["LDAP_PASSWORD"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{group}:</td>
		<td>". Field_text("ADGROUP-$tt",$array["ADGROUP"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>								
	<tr>
		<td colspan=2 align='right'>
				<hr>". button($btname,"Save$tt()","18px")."</td>
	</tr>
	</table>


<script>
	var x_Save$tt= function (obj) {
	var connection_id=$connection_id;
	var results=obj.responseText;
	if(results.length>3){alert(results);document.getElementById('$tt').innerHTML='';return;}
	document.getElementById('$tt').innerHTML='';
	if(connection_id==0){YahooWin2Hide();}
	$('#$t').flexReload();
}


function Save$tt(){
	var XHR = new XHRConnection();
	XHR.appendData('connectionname', encodeURIComponent(document.getElementById('connectionname-$t').value));
	XHR.appendData('connectiontype', document.getElementById('connectiontype-$t').value);
	XHR.appendData('ID', '$connection_id');
	
	XHR.appendData('LDAP_SERVER', document.getElementById('LDAP_SERVER-$tt').value);
	XHR.appendData('LDAP_PORT', document.getElementById('LDAP_PORT-$tt').value);
	XHR.appendData('LDAP_SUFFIX', document.getElementById('LDAP_SUFFIX-$tt').value);
	XHR.appendData('LDAP_DN', document.getElementById('LDAP_DN-$tt').value);
	XHR.appendData('ADGROUP', encodeURIComponent(document.getElementById('ADGROUP-$tt').value));
	XHR.appendData('LDAP_PASSWORD', encodeURIComponent(document.getElementById('LDAP_PASSWORD-$tt').value));
	AnimateDiv('$tt');
	XHR.sendAndLoad('$page', 'POST',x_Save$tt);
}


</script>";

echo $tpl->_ENGINE_parse_body($html);

}

function connection_form_mysql_local(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$connection_id=$_GET["connection-id"];
	$cnxt=$_GET["cnxt"];
	$btname="{add}";
	$array=array();
	if($connection_id>0){
		$btname="{apply}";
		$q=new mysql();
		$ligne=mysqli_fetch_array(
				$q->QUERY_SQL("SELECT params FROM freeradius_db WHERE ID=$connection_id","artica_backup")
		);
		$array=unserialize(base64_decode($ligne["params"]));
	
	}
	
	
	
	
	$tt=time();
	$html="
	<div style='font-size:16px' class=explain>{radius_local_mysqldb_explain}</div>
	<div id='$tt'></div>
	<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
	<tr>
		<td colspan=2 align='right'>
				<hr>". button($btname,"Save$tt()","18px")."</td>
	</tr>
	</table>
	</div>
	
	<script>
	var x_Save$tt= function (obj) {
		var connection_id=$connection_id;
		var results=obj.responseText;
		if(results.length>3){alert(results);document.getElementById('$tt').innerHTML='';return;}
		document.getElementById('$tt').innerHTML='';
		if(connection_id==0){YahooWin2Hide();}
		$('#$t').flexReload();
	}
	
	
	function Save$tt(){
		var XHR = new XHRConnection();
		XHR.appendData('connectionname', encodeURIComponent(document.getElementById('connectionname-$t').value));
		XHR.appendData('connectiontype', document.getElementById('connectiontype-$t').value);
		XHR.appendData('ID', '$connection_id');
		AnimateDiv('$tt');
		XHR.sendAndLoad('$page', 'POST',x_Save$tt);
	}
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}


function connection_form_ldap(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$connection_id=$_GET["connection-id"];
	$cnxt=$_GET["cnxt"];
	$btname="{add}";
	$array=array();
	if($connection_id>0){
		$btname="{apply}";
		$q=new mysql();
		$ligne=mysqli_fetch_array(
			$q->QUERY_SQL("SELECT params FROM freeradius_db WHERE ID=$connection_id","artica_backup")
		);
		$array=unserialize(base64_decode($ligne["params"]));
		
	}
	
	

	if($array["LDAP_FILTER"]==null){$array["LDAP_FILTER"]="(uid=%{%{Stripped-User-Name}:-%{User-Name}})";}
	if($array["PASSWORD_ATTRIBUTE"]==null){$array["PASSWORD_ATTRIBUTE"]="userPassword";}
	if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
	
	$tt=time();
	$html="
	<div id='$tt'></div>
	<div class=explain style='font-size:14px'>{ldap_cleartext_warn}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{hostname}:</td>
		<td>". Field_text("LDAP_SERVER-$tt",$array["LDAP_SERVER"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{ldap_port}:</td>
		<td>". Field_text("LDAP_PORT-$tt",$array["LDAP_PORT"],"font-size:16px;padding:3px;width:90px")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:16px'>{ldap_suffix}:</td>
		<td>". Field_text("LDAP_SUFFIX-$tt",$array["LDAP_SUFFIX"],"font-size:16px;padding:3px;width:390px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{bind_dn}:</td>
		<td>". Field_text("LDAP_DN-$tt",$array["LDAP_DN"],"font-size:12px;padding:3px;width:390px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password}:</td>
		<td>". Field_password("LDAP_PASSWORD-$tt",$array["LDAP_PASSWORD"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>					
	<tr>
		<td class=legend style='font-size:16px'>{access_attr} (yes/no):</td>
		<td>". Field_text("ACCESS_ATTRIBUTE-$tt",$array["ACCESS_ATTRIBUTE"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{password_attribute}:</td>
		<td>". Field_text("PASSWORD_ATTRIBUTE-$tt",$array["PASSWORD_ATTRIBUTE"],"font-size:16px;padding:3px;width:190px")."</td>
	</tr>	
				
	<tr>
		<td class=legend style='font-size:16px'>{ldap_filter}:</td>
		<td>". Field_text("LDAP_FILTER-$tt",$array["LDAP_FILTER"],"font-size:14px;padding:3px;width:390px")."</td>
	</tr>				
	<tr>
		<td colspan=2 align='right'>
				<hr>". button($btname,"Save$tt()","18px")."</td>
	</tr>
	</table>
						
						
	<script>
		var x_Save$tt= function (obj) {
		var connection_id=$connection_id;
		var results=obj.responseText;
		if(results.length>3){alert(results);document.getElementById('$tt').innerHTML='';return;}
		document.getElementById('$tt').innerHTML='';
		if(connection_id==0){YahooWin2Hide();}
		$('#$t').flexReload();
	}	
	
	
		function Save$tt(){
				var XHR = new XHRConnection();
				XHR.appendData('connectionname', encodeURIComponent(document.getElementById('connectionname-$t').value));
				XHR.appendData('connectiontype', document.getElementById('connectiontype-$t').value);
				XHR.appendData('ID', '$connection_id');
				
				XHR.appendData('LDAP_SERVER', document.getElementById('LDAP_SERVER-$tt').value);
				XHR.appendData('LDAP_PORT', document.getElementById('LDAP_PORT-$tt').value);
				XHR.appendData('LDAP_SUFFIX', document.getElementById('LDAP_SUFFIX-$tt').value);
				XHR.appendData('LDAP_DN', document.getElementById('LDAP_DN-$tt').value);
				XHR.appendData('LDAP_PASSWORD', encodeURIComponent(document.getElementById('LDAP_PASSWORD-$tt').value));
				XHR.appendData('ACCESS_ATTRIBUTE', document.getElementById('ACCESS_ATTRIBUTE-$tt').value);
				XHR.appendData('PASSWORD_ATTRIBUTE', encodeURIComponent(document.getElementById('PASSWORD_ATTRIBUTE-$tt').value));
				XHR.appendData('LDAP_FILTER', encodeURIComponent(document.getElementById('LDAP_FILTER-$tt').value));
				AnimateDiv('$tt');
				XHR.sendAndLoad('$page', 'POST',x_Save$tt);
		}
	
		
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function connection_save(){
	$ID=$_POST["ID"];
	$_POST["connectionname"]=url_decode_special_tool($_POST["connectionname"]);
	if($_POST["connectionname"]==null){$_POST["connectionname"]=time();}
	if(isset($_POST["LDAP_FILTER"])){$_POST["LDAP_FILTER"]=url_decode_special_tool($_POST["LDAP_FILTER"]);}
	if(isset($_POST["LDAP_PASSWORD"])){$_POST["LDAP_PASSWORD"]=url_decode_special_tool($_POST["LDAP_PASSWORD"]);}
	if(isset($_POST["ADGROUP"])){$_POST["ADGROUP"]=url_decode_special_tool($_POST["ADGROUP"]);}
	
	
	$params=base64_encode(serialize($_POST));
	if($ID==0){
		$sql="INSERT IGNORE INTO freeradius_db
				(`connectionname`,`connectiontype` ,`params`,`enabled`)
			VALUES('{$_POST["connectionname"]}','{$_POST["connectiontype"]}','$params',1)";
		
	}else{
		$sql="UPDATE freeradius_db SET `connectionname`='{$_POST["connectionname"]}',
		`connectiontype`='{$_POST["connectiontype"]}',`params`='$params'
		WHERE ID=$ID
		";
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("freeradius.php?restart=yes");
	
}
	
function page(){
	
		$page=CurrentPageName();
		$tpl=new templates();
		$q=new mysql();
		$sock=new sockets();
		$add=$tpl->javascript_parse_text("{new_connection}");
		$address=$tpl->javascript_parse_text("{address}");
		$enabled=$tpl->javascript_parse_text("{enabled}");
		$connection=$tpl->javascript_parse_text("{connection}");
		$connectiontype=$tpl->javascript_parse_text("{connection_type}");
		$tablewidht=883;
		$t=time();
	
		$buttons="buttons : [
		{name: '$add', bclass: 'Add', onpress : AddConnection$t},
		],	";
	
	
	
echo "
<table class='$t' style='display: none' id='$t' style='width:99%;text-align:left'></table>
<script>
	var MEMM$t='';
	$(document).ready(function(){
		$('#$t').flexigrid({
			url: '$page?query=yes&t=$t',
			dataType: 'json',
			colModel : [
			{display: '$connection', name : 'connectionname', width : 568, sortable : false, align: 'left'},
			{display: '$connectiontype', name : 'connectiontype', width : 158, sortable : false, align: 'left'},
			{display: '$enabled', name : 'enabled', width : 40, sortable : true, align: 'center'},
			{display: '&nbsp;', name : 'none2', width : 40, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$connection', name : 'connectionname'}
		],
		sortname: 'ID',
		sortorder: 'desc',
		usepager: true,
		title: '',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: $tablewidht,
		height: 450,
		singleSelect: true
		});
	});
	
	
	
	function RefreshTable$t(){
		$('#$t').flexReload();
	}
	
	function enable_ip_authentication_save$t(){
	var XHR = new XHRConnection();
	if(document.getElementById('LimitByIp').checked){XHR.appendData('LimitByIp',1);}else{XHR.appendData('LimitByIp',0);}
	XHR.appendData('servername','{$_GET["servername"]}');
			XHR.sendAndLoad('$page', 'POST',x_AuthIpAdd$t);
	}
	
	var x_Refresh$t=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		RefreshTable$t()
	}
	
	var x_ConnectionDelete$t=function (obj) {
		var results=obj.responseText;
		if(results.length>2){alert(results);return;}
		$('#row'+MEMM$t).remove();
	}
	
	function AddConnection$t(){
		Loadjs('$page?connection-id-js=0&t=$t');
	}
	
	function EnableLocalLDAPServer$t(){
		var XHR = new XHRConnection();
		XHR.appendData('EnableLocalLDAPServer','yes');
		XHR.sendAndLoad('$page', 'POST',x_Refresh$t);	
	}
	
	function EnableDisable$t(ID){
		var XHR = new XHRConnection();
		XHR.appendData('EnableDisable',ID);
		XHR.sendAndLoad('$page', 'POST',x_Refresh$t);	
	}
	
	function ConnectionDelete$t(id){
	MEMM$t=id;
	var XHR = new XHRConnection();
	XHR.appendData('connection-delete',id);
	XHR.sendAndLoad('$page', 'POST',x_ConnectionDelete$t);
		
	}
</script>
	";
}	

function connection_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql();
	$database="artica_backup";
	$t=$_GET["t"];
	$search='%';
	$table="freeradius_db";
	$page=1;
	$data = array();
	$data['rows'] = array();
	$FORCE_FILTER=null;
	$FreeRadiusEnableLocalLdap=$sock->GET_INFO("FreeRadiusEnableLocalLdap");
	if(!is_numeric($FreeRadiusEnableLocalLdap)){$FreeRadiusEnableLocalLdap=1;}
	if(!$q->TABLE_EXISTS($table, $database)){$q->BuildTables();}
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
			
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$ldap=$tpl->javascript_parse_text("{ldap}");
	$local_ldap_service=$tpl->_ENGINE_parse_body("{local_ldap_service}");
	
	$CONNECTIONS_TYPE["ldap"]="{ldap}";
	$CONNECTIONS_TYPE["mysql"]="{mysql}";
	$CONNECTIONS_TYPE["ad"]="{ActiveDirectory}";	
	$CONNECTIONS_TYPE["mysql_local"]="{local_mysql}";
	
	
	if($searchstring==null){
		$color="black";
		if($FreeRadiusEnableLocalLdap==0){$color="#8a8a8a";}
		$disable=Field_checkbox("sessionid_00", 1,$FreeRadiusEnableLocalLdap,"EnableLocalLDAPServer$t()");
		$data['rows'][] = array(
				'id' => "acl00",
				'cell' => array(
						$tpl->_ENGINE_parse_body("<span style=\"font-size:16px;color:$color\">{local_ldap_service}</span>"),
						"<span style=\"font-size:16px;color:$color\">$ldap</span>",
						$disable,
						"&nbsp;"
			)
		);
		$total=	$total+1;

		if($EnableKerbAuth==1){
			$color="black";
			$array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
			$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
			$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
			$domaindow=strtolower($array["WINDOWS_DNS_SUFFIX"]);
			$kinitpassword=$array["WINDOWS_SERVER_PASS"];
			$workgroup=strtoupper($array["ADNETBIOSDOMAIN"]);
			$data['rows'][] = array(
					'id' => "acl10",
					'cell' => array(
							"<span style=\"font-size:16px;color:$color\">{$hostname}/$domaindow ($workgroup)</span>",
							$tpl->_ENGINE_parse_body("<span style=\"font-size:16px;color:$color\">{ActiveDirectory}</span>"),
							"&nbsp;",
							"&nbsp;"
					)
			);
			
		$total=	$total+1;}
		
	}
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	
	$data['page'] = $page;
	$data['total'] = $total;
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$disable=Field_checkbox("sessionid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisable$t('{$ligne['ID']}')");
		$ligne['connectionname']=utf8_encode($ligne['connectionname']);
		
		$array=unserialize(base64_decode($ligne["params"]));
		
		$ADGROUP=trim($array["ADGROUP"]);
		if($ADGROUP<>null){$ADGROUPT="{groups} - $ADGROUP -";}
		if($ligne['connectiontype']=="ldap"){
			$tpl->_ENGINE_parse_body($cnx="ldap://{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}<br>{$array["LDAP_DN"]} $ADGROUP");
				
		}		
		if($ligne['connectiontype']=="ad"){
			$cnx=$tpl->_ENGINE_parse_body("{ActiveDirectory}: {$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}<br>{$array["LDAP_DN"]} $ADGROUPT");
		
		}		
		$ligne['connectiontype']=$tpl->javascript_parse_text($CONNECTIONS_TYPE[$ligne['connectiontype']]);
		$delete=imgsimple("delete-24.png",null,"ConnectionDelete$t('{$ligne['ID']}')");
		if($ligne["enabled"]==0){$color="#8a8a8a";}
	

		$data['rows'][] = array(
				'id' => "{$ligne['ID']}",
				'cell' => array("<a href=\"javascript:blur();\" 
						OnClick=\"Loadjs('$MyPage?connection-id-js={$ligne['ID']}&t=$t');\" 
						style=\"font-size:16px;text-decoration:underline;color:$color\">
						{$ligne['connectionname']}</a>
						<div style='font-size:11px'><i>$cnx</i></div>",
				"<span style=\"font-size:16px;color:$color\">{$ligne['connectiontype']}</span>",
				$disable,
				$delete
				)
		);
	}
	
	
	echo json_encode($data);	
	
}
function EnableLocalLDAPServer(){
	$sock=new sockets();
	$FreeRadiusEnableLocalLdap=$sock->GET_INFO("FreeRadiusEnableLocalLdap");
	if(!is_numeric($FreeRadiusEnableLocalLdap)){$FreeRadiusEnableLocalLdap=1;}
	if($FreeRadiusEnableLocalLdap==1){$sock->SET_INFO("FreeRadiusEnableLocalLdap",0);}
	if($FreeRadiusEnableLocalLdap==0){$sock->SET_INFO("FreeRadiusEnableLocalLdap",1);}
	$sock=new sockets();
	$sock->getFrameWork("freeradius.php?restart=yes");	
}

function connection_delete(){
	$q=new mysql();
	$ID=$_POST["connection-delete"];
	$sql="DELETE FROM freeradius_db WHERE ID=$ID";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("freeradius.php?restart=yes");	
}
function connection_enable(){
	$ID=$_POST["EnableDisable"];
	$q=new mysql();
	$ligne=mysqli_fetch_array(
			$q->QUERY_SQL("SELECT enabled FROM freeradius_db WHERE ID=$ID","artica_backup")
	);
	
	if($ligne["enabled"]==0){$enable=1;}else{$enable=0;}
	$q->QUERY_SQL("UPDATE freeradius_db SET enabled=$enable WHERE ID=$ID","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("freeradius.php?restart=yes");
}

