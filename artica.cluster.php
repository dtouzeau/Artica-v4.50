<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ccurl.inc');
	include_once('ressources/class.system.network.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsWebStatisticsAdministrator){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die("DIE " .__FILE__." Line: ".__LINE__);	
}


if(isset($_GET["rule-tabs"])){rule_tab();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["rule-id-js"])){rule_js();exit;}
if(isset($_GET["rule-id"])){rule_popup();exit;}
if(isset($_GET["group-text"])){echo rule_group_text($_GET["group-text"]);exit;}
if(isset($_GET["search"])){search();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_rule_js();exit;}
if(isset($_POST["DELETERULE"])){delete_rule();exit;}
table();





function delete_rule_js(){
	$t=time();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
echo "
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	$('#ARTICA_CLUSTER_TABLE').flexReload();
	
	
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('DELETERULE','{$_GET["ID"]}');
 	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();";
	
	
}

function rule_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["rule-id-js"]);
	$tpl=new templates();
	$q=new mysql();
	$title=$tpl->javascript_parse_text("{new_client}");

	if($ID>0){
		$ligne=@mysqli_fetch_array($q->QUERY_SQL("SELECT HOSTNAME FROM artica_clusters WHERE ID=$ID","artica_backup"));
		$title=$tpl->javascript_parse_text("{rule}:{$ligne["HOSTNAME"]}");
		echo "YahooWin2Hide();YahooWin2('850','$page?rule-id=yes&ID=$ID&t={$_GET["t"]}','$title')";
		return;
	}
	echo "YahooWin2Hide();YahooWin2('850','$page?rule-id=yes&ID=$ID&t={$_GET["t"]}','$title')";
}

function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql();
	$users=new usersMenus();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		echo FATAL_ERROR_SHOW_128("{this_feature_is_disabled_corp_license}");
		die("DIE " .__FILE__." Line: ".__LINE__);
	}
	
	
	
	if(!$q->TABLE_EXISTS("artica_clusters", "artica_backup")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_clusters` (
				  `ID` INT(11) NOT NULL AUTO_INCREMENT,
				  `HOSTNAME` varchar(128) NOT NULL,
				  `LOCALNIC` varchar(40) NOT NULL DEFAULT 'eth0',
				  `LISTEN_PORT` smallint(3) NOT NULL DEFAULT '9000',
				  `enabled` smallint(1) NOT NULL DEFAULT '1',
				  `USERNAME` varchar(128) NOT NULL,
				  `PASSWORD` TEXT NOT NULL,
				  PRIMARY KEY (`ID`),
				  UNIQUE KEY (`HOSTNAME`),
				  KEY `USERNAME` (`USERNAME`)
				) ENGINE=MYISAM AUTO_INCREMENT=10;";
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){echo $q->mysql_error_html();exit;}
	}
	
	if(!$q->FIELD_EXISTS("artica_clusters","LOCALNIC","artica_backup")){
		$sql="ALTER TABLE `artica_clusters` ADD `LOCALNIC` varchar(40) NOT NULL DEFAULT 'eth0'";
		$q->QUERY_SQL($sql,'artica_backup');
	}
	
	$t=time();
	$pattern=$tpl->_ENGINE_parse_body("{pattern}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_client}");
	$title=$tpl->javascript_parse_text("{artica_cluster}");
	$cache_deny=$tpl->javascript_parse_text("{cache}");
	$global_access=$tpl->javascript_parse_text("{global_access}");
	$deny_auth=$tpl->javascript_parse_text("{authentication}");
	$deny_ufdb=$tpl->javascript_parse_text("{webfiltering}");
	$deny_icap=$tpl->javascript_parse_text("{antivirus}");
	$groupid=$tpl->javascript_parse_text("{SquidGroup}");
	$username=$tpl->javascript_parse_text("{username}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$hostname=$tpl->javascript_parse_text("{remote_artica_server}");
	
	$client=$tpl->javascript_parse_text("{client}");
	$t=time();
	$apply=$tpl->javascript_parse_text("{replicate}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");


	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : NewRule$t},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'apply', onpress : replicate$t},
	
	],";	
	
	
	$html="
<table class='ARTICA_CLUSTER_TABLE' style='display: none' id='ARTICA_CLUSTER_TABLE' style='width:100%'></table>
<script>
function Load{$_GET["t"]}(){

	$('#ARTICA_CLUSTER_TABLE').flexigrid({
	url: '$page?search=yes&t={$_GET["t"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:22px>$hostname</span>', name : 'HOSTNAME', width :427, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$username</span>', name : 'USERNAME', width :224, sortable : false, align: 'left'},
	{display: '<span style=font-size:22px>$enabled</span>', name : 'enabled', width : 118, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},
	],
	$buttons

	sortname: 'HOSTNAME',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	});
}

function NewRule$t(){
	Loadjs('$page?rule-id-js=0&t={$_GET["t"]}');
}

function replicate$t(){
	Loadjs('artica.cluster.progress.php');
}

function SSLOptions$t(){
	Loadjs('squid.ssl.center.php?js=yes');
}


Load{$_GET["t"]}();

</script>
";	
	
	echo $html;
	
}

function rule_save(){
	
	$ID=$_POST["ID"];
	unset($_POST["ID"]);
	
	$_POST["PASSWORD"]=url_decode_special_tool($_POST["PASSWORD"]);
	
	$password=$_POST["PASSWORD"];
	$admin=$_POST["USERNAME"];
	$creds=base64_encode(serialize(array("ADM"=>$admin,"PASS"=>md5($password))));
	$uri="https://{$_POST["HOSTNAME"]}:{$_POST["LISTEN_PORT"]}/listen.snapshots.php?creds=$creds&hello=yes";
	
	
	foreach ($_POST as $key=>$val){
		
		$add_fields[]="`$key`";
		$add_values[]="'$val'";
		$edit_fields[]="`$key`='$val'";
		
		
	}
	
	
	if($ID==0){
		$tpl=new templates();
		$curl=new ccurl($uri);
		$curl->NoHTTP_POST=true;
		if(!$curl->get()){
			echo $tpl->javascript_parse_text("https://{$_POST["HOSTNAME"]}:{$_POST["LISTEN_PORT"]} {failed}:{protocol_error} [$curl->error] in line:". __LINE__."\n");
			return;
		}
		
		if(preg_match("#<ERROR>(.*?)</ERROR>#is", $curl->data,$re)){
			echo $tpl->javascript_parse_text("https://{$_POST["HOSTNAME"]}:{$_POST["LISTEN_PORT"]} {failed} ({$re[1]}) in line:". __LINE__."\n\n");
			return;
				
		}		
		
		if(!preg_match("#<ANSWER>HELLO</ANSWER>#is", $curl->data)){
			echo $tpl->javascript_parse_text("https://{$_POST["HOSTNAME"]}:{$_POST["LISTEN_PORT"]} {failed}:{protocol_error} [$curl->error] in line:". __LINE__."\n$curl->data\n");
			return;
			
		}
		
		
		$sql="INSERT IGNORE INTO artica_clusters (".@implode(",", $add_fields).") VALUES (".@implode(",", $add_values).")";
		
	}else{
		$sql="UPDATE artica_clusters SET ".@implode(",", $edit_fields)." WHERE ID='$ID'";
	}
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("artica_clusters","LOCALNIC","artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `artica_clusters` ADD `LOCALNIC` varchar(40) NOT NULL DEFAULT 'eth0'",'artica_backup');
	}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function delete_rule(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM artica_clusters WHERE ID='{$_POST["DELETERULE"]}'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
}

function rule_popup(){
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$btname="{add}";
	$t=time();
	$q=new mysql();
	$title=$tpl->javascript_parse_text("{new_client}");
	
	if($ID>0){
		$ligne=@mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM artica_clusters WHERE ID=$ID","artica_backup"));
		if(!$q->ok){echo $q->mysql_error_html();}
		$btname="{apply}";
		$title="{rule}:{$ligne["HOSTNAME"]}:{$ligne["LISTEN_PORT"]}";
	}
	
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	if(!is_numeric($ligne["LISTEN_PORT"])){$ligne["LISTEN_PORT"]=9000;}
	if(trim($ligne["USERNAME"])==null){$ligne["USERNAME"]="Manager";}
	if(trim($ligne["LOCALNIC"])==null){$ligne["LOCALNIC"]="eth0";}
	$ip=new networking();
	$Interfaces=$ip->Local_interfaces();
	$Interfaces[null]="{default}";
	unset($Interfaces["lo"]);

$html="
<p class=text-info style='font-size:18px;margin-bottom:20px'>{artica_cluster_explain}</p>		
<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
	<td colspan=2><div style='font-size:32px;margin-bottom:15px'>$title</div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:20px'>{enabled}:</td>
		<td style='font-size:20px'>". Field_checkbox_design("enabled-$t", 1,$ligne["enabled"],"")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:20px' nowrap>". texttooltip("{outgoing_address}","{haproxy_local_interface_help}").":</td>
		<td>". Field_array_Hash($Interfaces,"LOCALNIC-$t",$ligne["LOCALNIC"],"style:font-size:20px;padding:3px;")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:20px'>{remote_artica_server}:</td>
		<td style='font-size:20px'>". Field_text("HOSTNAME-$t", $ligne["HOSTNAME"],"font-size:20px;width:350px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:20px'>{remote_artica_port}:</td>
		<td style='font-size:20px'>". Field_text("LISTEN_PORT-$t", $ligne["LISTEN_PORT"],"font-size:20px;width:130px")."</td>
	</tr>		

	<tr>
		<td class=legend style='font-size:22px'>{SuperAdmin}:</td>
		<td style='font-size:22px'>". Field_text("USERNAME-$t",$ligne["USERNAME"],"font-size:22px;width:240px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{password}:</td>
		<td style='font-size:22px'>". Field_password("PASSWORD-$t",$ligne["PASSWORD"],"font-size:22px;width:300px")."</td>
	</tr>				
				
	<tr style='height:50px'>
		<td colspan=2 align='right'><hr>". button($btname,"Save$t()",32)."</td>
 </tr>
 </table>
	
 <script>
var xSave$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	var ID=$ID;
	if(ID==0){YahooWin2Hide();}
	$('#ARTICA_CLUSTER_TABLE').flexReload();
	
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ID','$ID');
	XHR.appendData('HOSTNAME',document.getElementById('HOSTNAME-$t').value);
	XHR.appendData('LISTEN_PORT',document.getElementById('LISTEN_PORT-$t').value);
 	if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
 	XHR.appendData('USERNAME',document.getElementById('USERNAME-$t').value);
 	XHR.appendData('LOCALNIC',document.getElementById('LOCALNIC-$t').value);
	XHR.appendData('PASSWORD',encodeURIComponent(document.getElementById('PASSWORD-$t').value));
 	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
	
	
 ";
	
 echo $tpl->_ENGINE_parse_body($html);
}


	
function search(){
		$tpl=new templates();
		$MyPage=CurrentPageName();
		$q=new mysql();
		$sock=new sockets();
		$t=$_GET["t"];
		$search='%';
		$table="artica_clusters";
		$page=1;
		$FORCE_FILTER=null;
		$total=0;


				
		
	
		if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
		if(isset($_POST['page'])) {$page = $_POST['page'];}
	
		$searchstring=string_to_flexquery();
		if($searchstring<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			$total = $ligne["TCOUNT"];
	
		}else{
			$total = $q->COUNT_ROWS($table,"artica_backup");
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
		$pageStart = ($page-1)*$rp;
		if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
		$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
		$results = $q->QUERY_SQL($sql,"artica_backup");
	
		$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
	
		if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
		if(mysqli_num_rows($results)==0){json_error_show("!!! no data");}

		
		$edit=$tpl->javascript_parse_text("{edit}");
		$squid_acls_groups=new squid_acls_groups();
		while ($ligne = mysqli_fetch_assoc($results)) {
			$color="black";
			$ID=$ligne["ID"];
			$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-rule-js=yes&ID=$ID&t={$_GET["t"]}',true)");
			$edit_group=null;
			$hostname=$ligne["HOSTNAME"];
			$port=$ligne["LISTEN_PORT"];
			$USERNAME=$ligne["USERNAME"];
			$LOCALNIC=$ligne["LOCALNIC"];
			$img="ok32.png";

			if($ligne["enabled"]==0){
				$img="ok32-grey.png";
				$color="#8a8a8a";
			}

			$EditJs="<a href=\"javascript:blur();\"
			OnClick=\"Loadjs('$MyPage?rule-id-js=$ID&t={$_GET["t"]}');\"
			style='font-size:26px;font-weight:normal;color:$color;text-decoration:underline'>";
			
				
			$data['rows'][] = array(
			 'id' => $ID,
			 'cell' => array(
			 		"<span style='font-size:26px;font-weight:normal;color:$color'>$LOCALNIC&nbsp;&nbsp;&raquo;&raquo;&nbsp;&nbsp;$EditJs$hostname:$port</a></span>",
			 		"<span style='font-size:26px;font-weight:normal;color:$color'>$EditJs$USERNAME</a></span>",
			 		"<center><img src='img/$img'></a></center>",
			 		"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'>$delete</center>",)
						);
		}
	
		echo json_encode($data);
	}
