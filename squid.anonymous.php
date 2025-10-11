<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
	$user=new usersMenus();
	
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}	
	
	

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["headers-list"])){proxies_list();exit;}
	if(isset($_GET["add-proxy"])){proxies_add_popup();exit;}
	if(isset($_POST["ipsrc"])){proxies_add();exit;}
	if(isset($_POST["paranoid-anonymous"])){paranoid_anonymous();exit;}
	if(isset($_POST["standard-anonymous"])){standard_anonymous();exit;}
	if(isset($_POST["standard"])){standard();exit;}
	if(isset($_POST["header-enable"])){header_active();exit;}
	if(isset($_POST["header-allow"])){header_allow();exit;}
	if(isset($_POST["header-change"])){header_change();exit;}
	
		js();
	
function js(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{anonymous_browsing}");
	$html="YahooWin4(600,'$page?popup=yes','$title')";
	echo $html;
}


function popup(){
	$tpl=new templates();
	$sock=new sockets();
	$ENABLED=trim($sock->getFrameWork("squid.php?enable-http-violations-enabled=yes"));
	if($ENABLED<>"TRUE"){
		$html="
		<table style='width:98%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/error-128.png'></td>
			<td valign='top'><div style='font-size:18px'>{HTTP_VIOLATIONS_NOT_ENABLED_IN_SQUID}</td>
		</tr>
		</table>
		
		";
		
		echo $tpl->_ENGINE_parse_body($html);
		return;
		
	}
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();	
	$q->CheckTablesSquid();
	$enabled=$tpl->_ENGINE_parse_body("{enable}");
	$allow=$tpl->_ENGINE_parse_body("{allow}");
	$active=$tpl->javascript_parse_text("{activew}");
	$standard=$tpl->javascript_parse_text("{standard}");
	$anonymous_browsing=$tpl->_ENGINE_parse_body("{anonymous_browsing}");
	$standard_anonymous=$tpl->javascript_parse_text("{standard_anonymous}");
	$paranoid_anonymous=$tpl->javascript_parse_text("{paranoid_anonymous}");
	$set_the_new_value_for_this_header=$tpl->javascript_parse_text("{set_the_new_value_for_this_header}");
	$restart_onlysquid=$tpl->_ENGINE_parse_body("{restart_onlysquid}");
	$tt=$_GET["tt"];
	$t=time();		

	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var tmp$t='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?headers-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'img', width : 24, sortable : true, align: 'left'},
		{display: 'header', name : 'header', width : 328, sortable : true, align: 'left'},
		{display: '$active', name : 'active', width : 80, sortable : false, align: 'center'},
		{display: '$allow', name : 'allow', width : 80, sortable : false, align: 'center'},
		
		
		
	],
buttons : [
	{name: '$standard', bclass: 'Script', onpress : standard$t},
	{name: '$standard_anonymous', bclass: 'Script', onpress : standard_anonymous},
	{name: '$paranoid_anonymous', bclass: 'Script', onpress : paranoid_anonymous},
	{name: '$restart_onlysquid', bclass: 'Reload', onpress : RestartProxy$t},
	],	
	searchitems : [
		{display: 'header', name : 'header'},
		],
	sortname: 'header',
	sortorder: 'asc',
	usepager: true,
	title: '$anonymous_browsing',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 580,
	height: 300,
	singleSelect: true
	
	});   
});	
	
function AddProxyChild(){
	YahooWin5('380','$page?add-proxy=yes&t=$t','$new_proxy');

}



	var x_DeleteSquidChild$t= function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);return;}
			$('#rowTSC'+tmp$t).remove();
		}		

	function DeleteSquidChild(ID){
		tmp$t=ID;
		if(confirm('$delete_this_child ?')){
			var XHR = new XHRConnection();
			XHR.appendData('proxy-delete',ID);
			XHR.sendAndLoad('$page', 'POST',x_DeleteSquidChild$t);
		}
	}
	
	var x_EnableDisableProxyHeader$t= function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);return;}
			
		}
		
	var x_standard_anonymous$t= function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);return;}
			$('#table-$t').flexReload();
		}		

	function standard_anonymous(){
		var XHR = new XHRConnection();
		XHR.appendData('standard-anonymous',1);
		XHR.sendAndLoad('$page', 'POST',x_standard_anonymous$t);	
	}
	
function standard$t(){
		var XHR = new XHRConnection();
		XHR.appendData('standard',1);
		XHR.sendAndLoad('$page', 'POST',x_standard_anonymous$t);
}	
	
	function paranoid_anonymous(){
		var XHR = new XHRConnection();
		XHR.appendData('paranoid-anonymous',1);
		XHR.sendAndLoad('$page', 'POST',x_standard_anonymous$t);	
	}	
	
	
	
	function standard_anonymous(){
		var XHR = new XHRConnection();
		XHR.appendData('standard-anonymous',1);
		XHR.sendAndLoad('$page', 'POST',x_standard_anonymous$t);	
	}	
	
	function RestartProxy$t(){
		Loadjs('squid.restart.php?onlySquid=yes');
	}
	
	
	
	function EnableDisableProxyHeader(header,md){
		var XHR = new XHRConnection();
		XHR.appendData('header-enable',header);
		if(document.getElementById(md).checked){
			XHR.appendData('enable',1);
		}else{
			XHR.appendData('enable',0);
		}
		
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableProxyHeader$t);	
	}
	function AllowProxyHeader(header,md){
		var XHR = new XHRConnection();
		XHR.appendData('header-allow',header);
		if(document.getElementById(md).checked){
			XHR.appendData('enable',1);
		}else{
			XHR.appendData('enable',0);
		}
		
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableProxyHeader$t);	
	}
	function ChangeSquidHeader(header){
		var vl=prompt('$set_the_new_value_for_this_header:'+header);
		if(vl){
			var XHR = new XHRConnection();
			XHR.appendData('header-change',header);
			XHR.appendData('value',vl);
			XHR.sendAndLoad('$page', 'POST',x_standard_anonymous$t);
		}	
	}
</script>
";
	
	echo $html;
	
	
}

function header_active(){
	$sql="UPDATE squid_header_access SET active={$_POST["enable"]} WHERE `header`='{$_POST["header-enable"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}

function header_change(){
	$sql="UPDATE squid_header_access SET replacewith='{$_POST["value"]}' WHERE `header`='{$_POST["header-change"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
		
}

function header_allow(){
	$sql="UPDATE squid_header_access SET allow={$_POST["enable"]} WHERE `header`='{$_POST["header-allow"]}'";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}

function proxies_list(){
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	$t=$_GET["t"];
	$replace=$tpl->_ENGINE_parse_body("{replace}");
	$search='%';
	$table="squid_header_access";
	$FORCE_FILTER=null;
	$page=1;

	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No rules....");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysqli_num_rows($results)==0){json_error_show("No rules....");}
	
	

	while ($ligne = mysqli_fetch_assoc($results)) {
		$val=0;
		$replacewith=null;
		$color="black";
		
		
		$md=md5($ligne["header"]);
		$mdB=md5($ligne["header"]."block");
		$disable=Field_checkbox($md, 1,$ligne["active"],"EnableDisableProxyHeader('{$ligne['header']}','$md')");
		$allow=Field_checkbox($mdB, 1,$ligne["allow"],"AllowProxyHeader('{$ligne['header']}','$mdB')");
		
		if($ligne["allow"]==1){$img="status_ok.png";}else{$img="status_ok_red.png";}
		
		if($ligne["active"]==0){$color="#8a8a8a";$img="status_ok-grey.png";}
		if($ligne["replacewith"]<>null){$replacewith="<div style='font-size:11px'>$replace: <i style='font-size:11px'>&laquo;{$ligne["replacewith"]}&raquo;</i></div>";}
		
		
	$data['rows'][] = array(
		'id' => "TSC{$ligne['ID']}",
		'cell' => array(
		"<img src='img/$img'>",
		"<span style='font-size:16px;color:$color'><a href=\"javascript:blur();\" OnClick=\"javascript:ChangeSquidHeader('{$ligne['header']}')\"  style='font-size:16px;color:$color;text-decoration:underline'>{$ligne['header']}</a>$replacewith",
		 $disable,$allow)
		);
	}
	
	
	echo json_encode($data);		
	
}

function proxies_add_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$t=time();	
	$tt=$_GET["t"];
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{source}:</td>
		<td>". field_ipv4("ipsrc-$t", null,"font-size:16px")."</td>
	</tr>
	<tr>
		<td colspan=2 align=right><hr>". button("{add}","ChildEventAdd$t()","18px")."</td>
	</tr>
	</table>
	<script>
		var x_ChildEventAdd$t= function (obj) {
			$('#table-$tt').flexReload();
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
			
			YahooWin5Hide();
		}		

		function ChildEventAdd$t(){
			var XHR = new XHRConnection();
			XHR.appendData('ipsrc',document.getElementById('ipsrc-$t').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_ChildEventAdd$t);
		}
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function proxies_add(){
	$sql="INSERT IGNORE INTO squid_balancers (ipsrc,enabled) VALUES ('{$_POST["ipsrc"]}',1)";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}

}

function standard(){
	$q=new mysql();
	$q->QUERY_SQL("UPDATE squid_header_access SET active=0, allow=1","artica_backup");
	
}

function standard_anonymous(){
	$q=new mysql();
	$q->QUERY_SQL("UPDATE squid_header_access SET active=0, allow=1","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=0 WHERE header='From'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=0 WHERE header='Referer'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=0 WHERE header='Server'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=0 WHERE header='User-Agent'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=0 WHERE header='WWW-Authenticate'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=0 WHERE header='X-Forwarded-For'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=0 WHERE header='Via'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=0 WHERE header='Link'","artica_backup");
	
}
function paranoid_anonymous(){
	$q=new mysql();
	$q->QUERY_SQL("UPDATE squid_header_access SET active=0, allow=1","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Allow'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Authorization'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='WWW-Authenticate'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Proxy-Authorization'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Proxy-Authenticate'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Cache-Control'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Content-Encoding'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Content-Length'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Content-Type'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Date'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Expires'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Host'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='If-Modified-Since'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Last-Modified'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Location'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Pragma'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Accept'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Accept-Charset'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Accept-Encoding'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Accept-Language'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Content-Language'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Mime-Version'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Retry-After'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Title'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=1 WHERE header='Connection'","artica_backup");
	$q->QUERY_SQL("UPDATE squid_header_access SET active=1, allow=0 WHERE header='All'","artica_backup");
	
}

