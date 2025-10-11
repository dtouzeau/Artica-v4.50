<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}

if(isset($_GET["button-ad"])){button_ad();exit;}
if(isset($_POST["acl-rule-link"])){quota_destination_link();exit;}
if(isset($_POST["acl-rule-link-delete"])){quota_destination_unlink();exit;}

if(isset($_GET["list-items"])){list_items();exit;}
if(isset($_GET["new-quota-rule"])){new_quota_rule_js();exit;}
if(isset($_GET["get-quota-rule"])){quota_rule_js();exit;}
if(isset($_GET["service-cmds"])){service_cmds_js();exit;}
if(isset($_GET["service-cmds-popup"])){service_cmds_popup();exit;}
if(isset($_GET["service-cmds-perform"])){service_cmds_perform();exit;}
	
if(isset($_POST["EnableDisable"])){EnableDisable();exit;}

if(isset($_GET["ID-TAB"])){quota_tab();exit;}
if(isset($_GET["quota-params-members"])){quota_rule();exit;}
if(isset($_GET["quota-destination-list"])){quota_destination_list();exit;}
if(isset($_GET["quota-params-destination"])){quota_destination();exit;}
if(isset($_GET["quota-params-notification"])){quota_notification();exit;}

if(isset($_GET["ID"])){quota_rule();exit;}
if(isset($_GET["explain-ident"])){explain_ident();exit;}
if(isset($_POST["ID"])){quota_rule_save();exit;}
if(isset($_GET["quota-params"])){quota_params_js();exit;}
if(isset($_GET["quota-params-popup"])){quota_params_popup();exit;}
if(isset($_POST["TEMPLATE"])){quota_params_save();exit;}
if(isset($_POST["delete"])){quota_delete();exit;}
page();

function quota_params_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{squidquota}::{parameters}");
	echo "YahooWin2('650','$page?quota-params-popup=yes&t={$_GET["t"]}','$title')";	
}

function EnableDisable(){
	$ID=$_POST["EnableDisable"];
	$q=new mysql_squid_builder();
	$sql="SELECT enabled FROM webfilters_quotas WHERE `ID`='$ID'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if($ligne["enabled"]==0){
		$q->QUERY_SQL("UPDATE webfilters_quotas SET `enabled`=1 WHERE ID=$ID");
	}else{
		$q->QUERY_SQL("UPDATE webfilters_quotas SET `enabled`=0 WHERE ID=$ID");
	}
}

function quota_rule_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	if($_GET["ID"]>0){
		$sql="SELECT xtype,value,maxquota FROM webfilters_quotas WHERE `ID`='{$_GET["ID"]}'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$title=$tpl->javascript_parse_text("{squidquota}::{$ligne["xtype"]} ({$ligne["value"]}) max:{$ligne["maxquota"]}M");
		echo "YahooWin2('650','$page?ID-TAB=yes&ID={$_GET["ID"]}&t={$_GET["t"]}','$title')";
		return;
	}
	echo "YahooWin2('650','$page?ID={$_GET["ID"]}&t={$_GET["t"]}','$title')";
}

function service_cmds_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmd=$_GET["service-cmds"];
	$mailman=$tpl->_ENGINE_parse_body("{squidquota}::{reconfigure}");
	$html="YahooWin4('650','$page?service-cmds-popup=$cmd','$mailman::$cmd');";
	echo $html;	
}
function service_cmds_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$cmd=$_GET["service-cmds-popup"];
	$t=time();
	$html="
	<div id='pleasewait-$t''><center><div style='font-size:22px;margin:50px'>{please_wait}</div><img src='img/wait_verybig_mini_red.gif'></center></div>
	<div id='results-$t'></div>
	<script>LoadAjax('results-$t','$page?service-cmds-perform=$cmd&t=$t');</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function service_cmds_perform(){
	$sock=new sockets();
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?reconfigure-quotas-tenir=yes")));
	$html="<textarea style='height:450px;overflow:auto;width:100%;font-size:12px'>".@implode("\n", $datas)."</textarea>
<script>
	 document.getElementById('pleasewait-$t').innerHTML='';
	
</script>

";
	echo $tpl->_ENGINE_parse_body($html);
}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}
	if($_GET["day"]==null){$_GET["day"]=$q->HIER();}		
	$member=$tpl->_ENGINE_parse_body("{member}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");	
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$sitename=$tpl->_ENGINE_parse_body("{sitename}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$day=$tpl->_ENGINE_parse_body("{day}");
	$week=$tpl->_ENGINE_parse_body("{week}");
	$duration=$tpl->_ENGINE_parse_body("{duration}");
	$quotas=$tpl->_ENGINE_parse_body("{quotas}");
	$maxquota=$tpl->_ENGINE_parse_body("{maxquota}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$apply_parameters=$tpl->_ENGINE_parse_body("{apply_parameters}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$destination=$tpl->_ENGINE_parse_body("{destination}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$TB_WIDTH=550;
	$t=time();
	
	$buttons="
	buttons : [
	{name: '<b>$new_rule</b>', bclass: 'add', onpress : NewRule$t},
	{name: '<b>$parameters</b>', bclass: 'Settings', onpress :Params$t},
	{name: '<b>$apply_parameters</b>', bclass: 'Reconf', onpress :Reconf$t},
	{name: '<b>$online_help</b>', bclass: 'Help', onpress :help$t},
	
	
		],";		
	
	$html="
	<center id='anim-$t'><img src='img/wait_verybig_old.gif'></center>
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
<script>
var mem$t='';
function StartTable$t(){
document.getElementById('anim-$t').innerHTML='';

$('#$t').flexigrid({
	url: '$page?list-items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'value', width : 184, sortable : true, align: 'left'},
		{display: '$destination', name : 'xdes', width : 211, sortable : false, align: 'left'},
		{display: '$duration', name : 'duration', width : 161, sortable : true, align: 'left'},
		{display: '$maxquota', name : 'maxquota', width : 124, sortable : false, true: 'left'},
		{display: '$enabled', name : 'enabled', width : 31, sortable : false, true: 'center'},
		{display: '&nbsp;', name : 'del', width : 31, sortable : false, true: 'center'},
		
		
		
	],$buttons
	searchitems : [
		{display: '$member', name : 'value'},
		],
	sortname: 'maxquota',
	sortorder: 'asc',
	usepager: true,
	title: '$members&raquo;{$quotas}',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 832,
	height: 450,
	singleSelect: true
	
	});
}

function RefreshNodesSquidTbl(){
	$('#$t').flexReload();
}

function NewRule$t(){
	Loadjs('$page?new-quota-rule=yes&t=$t');
}
function GetRule$t(ID){
	Loadjs('$page?get-quota-rule=yes&t=$t&ID='+ID);
}

function Reconf$t(){
	Loadjs('squid.compile.php');
}

function Params$t(){
	Loadjs('$page?quota-params=yes&t=$t');
}

	var x_DeleteQuota$t= function (obj) {
		var results=obj.responseText;
		if(results.length>2){alert(results);return;}
		$('#row'+mem$t).remove();
		
	}

function DeleteQuota$t(ID){
	mem$t=ID;
	var XHR = new XHRConnection();
	XHR.appendData('delete',ID);
	XHR.sendAndLoad('$page', 'POST',x_DeleteQuota$t);	
	
}
	var x_Enable$t= function (obj) {
		var results=obj.responseText;
		if(results.length>2){alert(results);return;}
		$('#$t').flexReload();
		
	}

function EnableQuota$t(ID){
	mem$t=ID;
	var XHR = new XHRConnection();
	XHR.appendData('EnableDisable',ID);
	XHR.sendAndLoad('$page', 'POST',x_Enable$t);	
}

function help$t(){
	s_PopUpFull('http://www.youtube.com/watch?v=wYXGwKB9SPk&feature=c4-overview&list=UUYbS4gGDNP62LsEuDWOMN1Q','1024','900');
}

setTimeout('StartTable$t()',500);

</script>";
	
	echo $html;
	
	
}
function quota_notification(){
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("webfilters_quotas", "notify")){
		$q->QUERY_SQL("ALTER TABLE webfilters_quotas ADD `notify` smallint(1)  NOT NULL DEFAULT 0, ADD INDEX (`notify`)");
	}
	if(!$q->FIELD_EXISTS("webfilters_quotas", "notify_params")){
		$q->QUERY_SQL("ALTER TABLE webfilters_quotas ADD `notify_params` TEXT");
	}	

	
	
}

function quota_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_quotas WHERE ID={$_POST["delete"]}");
	if(!$q->ok){echo $q->mysql_error;}
	$q->QUERY_SQL("DELETE FROM webfilters_quotas_grp WHERE ruleid={$_POST["delete"]}");
	if(!$q->ok){echo $q->mysql_error;}
}

function quota_tab(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$array["quota-params-members"]="{source}";
	$array["quota-params-destination"]="{destination}";
	//$array["quota-params-notification"]="{notification}";
	$ID=$_GET["ID"];
	$fontsize=14;
	if(count($array)>6){$fontsize=11.5;}
	$t=time();
	foreach ($array as $num=>$ligne){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t&t=$t&ID=$ID\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	
	
	
	$html= build_artica_tabs($html,'main_squid_quota_table');
	echo $html;
	
}

function quota_params_popup(){
	$sock=new sockets();
	$t=$_GET["t"];
	$array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidQuotasParams")));
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!is_numeric($array["CACHE_TIME"])){$array["CACHE_TIME"]=360;}
	if(!is_numeric($array["DISABLE_MODULE"])){$array["DISABLE_MODULE"]=0;}
	if($array["TEMPLATE"]==null){$array["TEMPLATE"]="ERR_ACCESS_DENIED";}
	$html="
	<span id='explain-div-$t'></span>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{disable}:</td>
		<td style='font-size:16px'>". Field_checkbox("DISABLE_MODULE-$t", 1,$array["DISABLE_MODULE"],"DISABLE_MODULE_CHECK$t()")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{template}:</td>
		<td style='font-size:16px'><span id='TEMPLATEtext-$t'>{$array["TEMPLATE"]}</span>&nbsp;".Field_hidden("TEMPLATE-$t", $array["TEMPLATE"])."</td>
		<td>". button("{browse}...", "Loadjs('squid.templates.php?choose-generic=TEMPLATE-$t&divid=TEMPLATEtext-$t')","13px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{cache_time}:</td>
		<td style='font-size:16px' colspan=2>". Field_text("CACHE_TIME-$t",$array["CACHE_TIME"],"font-size:16px;width:90")."&nbsp;{seconds}</td>
	</tr>	
	
	
	<tr>
		<td colspan=3 align='right'><hr>".button("{apply}", "SaveFormRule$t()","18px")."</tr>
	</tr>
	</table>
	</div>
	<script>
		function ExplainIndet$t(){
			var exp=document.getElementById('identification-$t').value;
			LoadAjax('explain-div-$t','$page?explain-ident='+exp);
		
		}
		
	var x_SaveFormRule$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('explain-div-$t').innerHTML='';
		if(results.length>2){alert(results);return;}
		$('#$t').flexReload();
		YahooWin2Hide();
	}	

	function DISABLE_MODULE_CHECK$t(){
		if(document.getElementById('DISABLE_MODULE-$t').checked){
			document.getElementById('TEMPLATE-$t').disabled=true;
			document.getElementById('CACHE_TIME-$t').disabled=true;
		}else{
			document.getElementById('TEMPLATE-$t').disabled=false;
			document.getElementById('CACHE_TIME-$t').disabled=false;		
		}
	}
		
	function SaveFormRule$t(){	
		var XHR = new XHRConnection();
		if(document.getElementById('DISABLE_MODULE-$t').checked){XHR.appendData('DISABLE_MODULE',1);}else{XHR.appendData('DISABLE_MODULE',0);}
		XHR.appendData('TEMPLATE',document.getElementById('TEMPLATE-$t').value);
		XHR.appendData('CACHE_TIME',document.getElementById('CACHE_TIME-$t').value);
		AnimateDiv('explain-div-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveFormRule$t);		
		}	
		
		 DISABLE_MODULE_CHECK$t();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
}
function quota_params_save(){
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "SquidQuotasParams");
	$sock->getFrameWork("squid.php?squid-reconfigure=yes");
}

function new_quota_rule_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{squidquota}::{new_rule}");
	echo "YahooWin2('650','$page?ID=0&t={$_GET["t"]}','$title')";
	
}



function list_items(){
	$sock=new sockets();
	$array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidQuotasParams")));
	$tpl=new templates();	
	if(!is_numeric($array["CACHE_TIME"])){$array["CACHE_TIME"]=120;}
	if(!is_numeric($array["DISABLE_MODULE"])){$array["DISABLE_MODULE"]=0;}	
	$q=new mysql_squid_builder();	
	$search=trim($_GET["search"]);
	$dayfull="{$_GET["day"]} 00:00:00";
	$date=strtotime($dayfull);
	$table="webfilters_quotas";
	$t=$_GET["t"];
	$tpl=new templates();
	$daysuffix=$tpl->_ENGINE_parse_body(date("{l} d",$date));
	$search='%';
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){json_error_show("Table empty");}
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			if($_POST["sortname"]=="zDate"){$_POST["sortname"]="hour";}
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
		$disabled_text=$tpl->_ENGINE_parse_body("{disabled}");
	if($searchstring<>null){	
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){json_error_show("$q->mysql_error");}
		
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$durations[1]="{per_day}";
	$durations[2]="{per_hour}";	
	
	$identifications["ipaddr"]="{ipaddr}";
	$identifications["uid"]="{member}";
	$identifications["uidAD"]="{active_directory_member}";
	$identifications["MAC"]="{MAC}";
		
	
	$disabled_t=null;
	$color=";color:black;";
	if($array["DISABLE_MODULE"]==1){$color=";color:#969696;";}
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$color=";color:black;";
		$delete=imgsimple("delete-32.png","","DeleteQuota$t({$ligne["ID"]})");
		$enabled=Field_checkbox("disabled{$ligne["ID"]}", 1,$ligne["enabled"],"EnableQuota$t({$ligne["ID"]})");
		if($ligne["enabled"]==0){$color=";color:#969696;";}
		if($array["DISABLE_MODULE"]==1){$color=";color:#969696;";}
		$uri="<a href=\"javascript:blur();\" Onclick=\"javascript:GetRule$t({$ligne["ID"]});\" 
		style=\"font-size:14px;text-decoration:underline$color\">";
	$data['rows'][] = array(
		'id' => $ligne["ID"],
		'cell' => array(
			"<span style='font-size:14px$color'>$uri".$tpl->_ENGINE_parse_body("{$identifications[$ligne["xtype"]]}&nbsp;{$ligne["value"]}")."</a></span>",
			"<span style='font-size:14px$color'>$uri". $tpl->_ENGINE_parse_body(ExplainRule($ligne["ID"]))."</a></span>",
			"<span style='font-size:14px$color'>$uri". $tpl->_ENGINE_parse_body("{$durations[$ligne["duration"]]}")."</a></span>",
			"<span style='font-size:14px$color'>{$ligne["maxquota"]} MB</span>",
			$enabled,
			$delete
	
	 	 	
			)
		);
	}
	
	
echo json_encode($data);		
}

function ExplainRule($ID){
	
	$acl=new squid_acls_groups();
	$q=new mysql_squid_builder();
	
	$sql="SELECT webfilters_quotas_grp.gpid,
	webfilters_quotas_grp.zmd5, webfilters_quotas_grp.ID as LINKID, 
	webfilters_sqgroups.* FROM webfilters_quotas_grp, webfilters_sqgroups 
	WHERE webfilters_quotas_grp.gpid=webfilters_sqgroups.ID AND webfilters_quotas_grp.ruleid=$ID";
	
	
	$results = $q->QUERY_SQL($sql);
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$arrayF=$acl->FlexArray($ligne['gpid']);
		
		$t[]="{$arrayF["ROW"]}";
		
	}
	
	if(count($t)==0){return null;}
	return @implode("<br>", $t);
	
	
	
}

function button_ad(){
	$tpl=new templates();
	$t=$_GET["t"];
	$js="Loadjs('MembersBrowse.php?OnlyGroups=1&callback=FillButtonAD$t&OnlyAD=1&prepend-guid=0&prepend=0&OnlyGUID=0&OnlyName=1');";
	echo $tpl->_ENGINE_parse_body(button("{browse}...", $js,16));
	
}

function quota_rule(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	$t=$_GET["t"];	
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$bt_text="{apply}";
	if($ID<1){$bt_text="{add}";}
	if($ID>0){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM webfilters_quotas WHERE `ID`='{$_GET["ID"]}'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$ident=$ligne["xtype"];
		$duration=$ligne["duration"];
		$maxquota=$ligne["maxquota"];
		$value=$ligne["value"];
	}
	
	$identifications["ipaddr"]="{ipaddr}";
	$identifications["uid"]="{member}";
	$identifications["uidAD"]="{active_directory_member}";
	$identifications["MAC"]="{MAC}";
			
	
	$durations[1]="{per_day}";
	$durations[2]="{per_hour}";
	
	//
	
	$html="
	<span id='explain-div-$t'></span>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{identification}:</td>
		<td>". Field_array_Hash($identifications, "identification-$t",$ident,"ExplainIndet$t()",null,0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{pattern}:</td>
		<td>". Field_text("value-$t",$value,"font-size:16px")."</td>
	</tr>
	<tr><td colspan=2 style='text-align:right'><span id='button-$t'></span></td></tr>	
	<tr>
		<td class=legend style='font-size:16px'>{duration}:</td>
		<td>". Field_array_Hash($durations, "duration-$t",$duration,null,null,0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{maxquota} (MB):</td>
		<td>". Field_text("maxquota-$t",$maxquota,"font-size:16px;width:90px")."</td>
	</tr>
	
	<tr>
		<td colspan=2 align='right'><hr>".button("$bt_text", "SaveFormRule$t()","18px")."</tr>
	</tr>
	</table>
	
	<script>
		function ExplainIndet$t(){
			document.getElementById('button-$t').innerHTML='';
			var exp=document.getElementById('identification-$t').value;
			document.getElementById('value-$t').disabled=false;
			LoadAjax('explain-div-$t','$page?explain-ident='+exp);
			if(exp=='uidAD'){
				document.getElementById('value-$t').disabled=true;
				LoadAjaxTiny('button-$t','$page?button-ad=true&field=value-$t&t=$t');
			}
		
		}
		
	function CheckFied$t(){
		var exp=document.getElementById('identification-$t').value;
		document.getElementById('value-$t').disabled=false;
		if(exp=='uidAD'){
				document.getElementById('value-$t').disabled=true;
				LoadAjaxTiny('button-$t','$page?button-ad=true&field=value-$t&t=$t');
			}
	}
		
	var x_SaveFormRule$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('explain-div-$t').innerHTML='';
		if(results.length>2){alert(results);return;}
		$('#$t').flexReload();
		if($ID==0){
			YahooWin2Hide();
		}
	}	

	function FillButtonAD$t(num,prepend,gid){
		document.getElementById('value-$t').value=gid;
	}
		
	function SaveFormRule$t(){	
		var XHR = new XHRConnection();
		XHR.appendData('ID','{$_GET["ID"]}');
		XHR.appendData('xtype',document.getElementById('identification-$t').value);
		XHR.appendData('value',document.getElementById('value-$t').value);
		XHR.appendData('maxquota',document.getElementById('maxquota-$t').value);	
		XHR.appendData('duration',document.getElementById('duration-$t').value);
		AnimateDiv('explain-div-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveFormRule$t);		
		}	
		
		setTimeout('CheckFied$t()',500);
		
		
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function quota_rule_save(){
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$sql="INSERT INTO webfilters_quotas (xtype,value,maxquota,duration) 
	VALUES ('{$_POST["xtype"]}','{$_POST["value"]}','{$_POST["maxquota"]}','{$_POST["duration"]}')";
	
	if($ID>0){
		$sql="UPDATE webfilters_quotas SET 
			`xtype`='{$_POST["xtype"]}',
			`value`='{$_POST["value"]}',
			`maxquota`='{$_POST["maxquota"]}',
			`duration`='{$_POST["duration"]}'
			WHERE ID='$ID'
			";
		
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;
	return;}
	
}

function explain_ident(){
	$tpl=new templates();
	echo "<div class=explain style='font-size:14px'>".$tpl->_ENGINE_parse_body("{squidqota_{$_GET["explain-ident"]}}")."</div>";
	
}

function quota_destination(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$objects=$tpl->_ENGINE_parse_body("{objects}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_item=$tpl->_ENGINE_parse_body("{link_object}");
	$new_group=$tpl->_ENGINE_parse_body("{new_proxy_object}");
	$t=$_GET["t"];
	$tt=time();
	$html="
<table class='table-items-$tt' style='display: none' id='table-items-$tt' style='width:99%'></table>
<script>
	var DeleteAclKey$tt='';
function LoadTable$tt(){
		$('#table-items-$tt').flexigrid({
		url: '$page?quota-destination-list=yes&ID=$ID&t=$tt&aclid={$_GET["ID"]}',
		dataType: 'json',
		colModel : [
		{display: '$objects', name : 'gpid', width : 415, sortable : true, align: 'left'},
		{display: '$items', name : 'items', width : 69, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'del', width : 31, sortable : false, align: 'center'},
	
		],
		buttons : [
		{name: '$new_item', bclass: 'add', onpress : LinkAclItem$tt},
		],
		searchitems : [
		{display: '$items', name : 'GroupName'},
		],
		sortname: 'GroupName',
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
}

function LinkAclItem$tt() { Loadjs('squid.BrowseAclGroups.php?callback=LinkAclRuleGpid$tt&FilterType=dstdomain'); }
function LinkAddAclItem$tt(){ Loadjs('squid.acls.groups.php?AddGroup-js=-1&link-acl={$_GET["aclid"]}&table-acls-t=$tt'); }
	
var x_LinkAclRuleGpid$t= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#$t').flexReload();
		$('#table-items-$tt').flexReload();
		ExecuteByClassName('SearchFunction');
	}
	
function LinkAclRuleGpid$tt(gpid){
	var XHR = new XHRConnection();
	XHR.appendData('acl-rule-link', '{$_GET["ID"]}');
	XHR.appendData('gpid', gpid);
	XHR.sendAndLoad('$page', 'POST',x_LinkAclRuleGpid$t);
}
var x_DeleteObjectLinks$tt= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#row'+DeleteAclKey$tt).remove();
	$('#$t').flexReload();
	ExecuteByClassName('SearchFunction');
}


function DeleteObjectLinks$tt(mkey){
	DeleteAclKey$tt=mkey;
	var XHR = new XHRConnection();
	XHR.appendData('acl-rule-link-delete', mkey);
	XHR.sendAndLoad('$page', 'POST',x_DeleteObjectLinks$tt);
}
	

	
LoadTable$tt();
</script>
";
echo $html;	
	
}


function quota_destination_unlink(){
	//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$q=new mysql_squid_builder();
	$ruleid=$_POST["acl-rule-link-delete"];
	$sql="DELETE FROM `webfilters_quotas_grp` WHERE `zmd5`='$ruleid'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}	
	
	
}
function quota_destination_link(){
	$ruleid=$_POST["acl-rule-link"];
	if($ruleid==0){echo "NO ID !!\n";return;}
	$gpid=$_POST["gpid"];
	
	$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`webfilters_quotas_grp` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`zmd5` VARCHAR(90) NOT NULL,
			`ruleid` INT UNSIGNED,
			`gpid` INT UNSIGNED,
		    UNIQUE KEY `zmd5` (`zmd5`),
			KEY `ruleid` (`ruleid`),
			KEY `gpid` (`gpid`)
			)  ENGINE = MYISAM;";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	$zmd5=md5("$gpid$ruleid");
	$sql="INSERT IGNORE INTO `webfilters_quotas_grp` (zmd5,gpid,ruleid) VALUES ('$zmd5','$gpid','$ruleid')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}

function quota_destination_list(){
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
$tpl=new templates();
$MyPage=CurrentPageName();
$q=new mysql_squid_builder();
$ID=$_GET["ID"];
$acl=new squid_acls();
$t0=$_GET["t"];
$search='%';
$table="(SELECT webfilters_quotas_grp.gpid,webfilters_quotas_grp.zmd5, webfilters_quotas_grp.ID as LINKID, webfilters_sqgroups.* FROM webfilters_quotas_grp, webfilters_sqgroups WHERE webfilters_quotas_grp.gpid=webfilters_sqgroups.ID AND webfilters_quotas_grp.ruleid=$ID) as t";
	
		$page=1;
		if(!$q->TABLE_EXISTS("webfilters_quotas_grp")){$q->CheckTables(null,true);}
		if($q->COUNT_ROWS("webfilters_quotas_grp")==0){json_error_show("No datas");}
	
		if(isset($_POST["sortname"])){
			if($_POST["sortname"]<>null){
				$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
			}
		}
	
		if (isset($_POST['page'])) {$page = $_POST['page'];}
	
		
		$searchstring=string_to_flexquery();
	
		if($searchstring<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
			$total = $ligne["TCOUNT"];
	
		}else{
			$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
			$total = $ligne["TCOUNT"];
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
		
		$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){json_error_show($q->mysql_error."\n$sql");}
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
		if(mysqli_num_rows($results)==0){json_error_show("No item");}
		$rules=$tpl->_ENGINE_parse_body("{rules}");
		$acl=new squid_acls_groups();
	
		while ($ligne = mysqli_fetch_assoc($results)) {
			$val=0;
			$ID=$ligne["ID"];
			$md5=$ligne["zmd5"];
			$arrayF=$acl->FlexArray($ligne['gpid']);
			$delete=imgsimple("delete-24.png",null,"DeleteObjectLinks$t0('$md5')");
			
			
	
			$data['rows'][] = array(
					'id' => "$md5",
					'cell' => array($arrayF["ROW"],
							"<span style='font-size:14px;font-weight:bold'>{$arrayF["ITEMS"]}</span>",
							$delete)
			);
		}
	
	
		echo json_encode($data);
	}