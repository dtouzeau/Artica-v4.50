<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die("DIE " .__FILE__." Line: ".__LINE__);	
}
if(isset($_GET["timerules-list"])){timerules_list();exit;}
if(isset($_GET["EditTimeRule-js"])){EditTimeRule_js();exit;}
if(isset($_GET["EditTimeRule-popup"])){EditTimeRule_popup();exit;}
if(isset($_GET["EditTimeRule-template"])){EditTimeRule_template();exit;}



if(isset($_POST["EditTimeRule-template"])){EditTimeRule_template();exit;}
if(isset($_POST["EditTimeRule"])){EditTimeRule_save();exit;}
if(isset($_POST["EnbleTimeRule"])){EditTimeRule_enable();exit;}
if(isset($_POST["EditTimeRule-delete"])){EditTimeRule_delete();exit;}
if(isset($_POST["EditTimeTemplate"])){EditTimeRule_template_save();exit;}


if(isset($_GET["EditTimeRule-groups"])){EditTimeRule_groups();exit;}
if(isset($_GET["EditTimeRule-groups-list"])){EditTimeRule_groups_list();exit;}
if(isset($_POST["EditTimeRule-groups-add"])){EditTimeRule_groups_add();exit;}
if(isset($_POST["EditTimeRule-groups-delete"])){EditTimeRule_groups_delete();exit;}


rules();


function EditTimeRule_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	if($ID>0){
			$q=new mysql_squid_builder();
			$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT TimeName FROM webfilters_sqtimes_rules WHERE ID='$ID'"));
			$TimeName="&nbsp;&raquo;".utf8_encode($ligne["TimeName"]);
	}
	$title="{edit_time_rule}:$ID$TimeName";
	if($ID<0){$title="{new_time_rule}";}
	$title=$tpl->_ENGINE_parse_body($title);
	$html="YahooWin2(555,'$page?EditTimeRule-popup=yes&ID=$ID','$title')";
	echo $html;
}


function EditTimeRule_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];

	
	$array["EditTimeRule-groups"]='{groups}';
	$array["EditTimeRule-popup"]='{settings}';
	$array["EditTimeRule-template"]='{template}';
	

	foreach ($array as $num=>$ligne){
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes&ID=$ID&tab=yes\"><span>$ligne</span></a></li>\n");
	
	}

	
	echo "$menus
	<div id=main_content_rule_editTtimerule style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_content_rule_editTtimerule').tabs();
			
			
			});
		</script>";	
	
	
}

function EditTimeRule_popup(){
	$ID=$_GET["ID"];
	if($ID>0){if(!isset($_GET["tab"])){EditTimeRule_tabs();return;}}
	
	
	$page=CurrentPageName();
	$tpl=new templates();	

	$q=new mysql_squid_builder();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqtimes_rules WHERE ID='$ID'"));
	$TimeSpace=unserialize($ligne["TimeCode"]);
	$days=array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");
	$cron=new cron_macros();
	$buttonname="{apply}";
	if($ID<1){$buttonname="{add}";}
	
	$RuleAl[0]="{block}";
	$RuleAl[1]="{allow}";
	
	
	$t=time();
	while (list ($num, $val) = each ($days) ){
		
		$jsjs[]="if(document.getElementById('day_{$num}').checked){ XHR.appendData('day_{$num}',1);}else{ XHR.appendData('day_{$num}',0);}";
		
		
		$dd=$dd."
		<tr>
		<td width=1%>". Field_checkbox("day_{$num}",1,$TimeSpace["DAYS"][$num])."</td>
		<td width=99% class=legend style='font-size:14px' align='left'>{{$val}}</td>
		</tr>
		";
		
	}
	
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px' nowrap width=99%>{rulename}:</td>
		<td>". Field_text("TimeName",utf8_encode($ligne["TimeName"]),"font-size:14px;width:350px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' nowrap width=99%>{type}:</td>
		<td style='font-size:14px' nowrap width=99%>". Field_array_Hash($RuleAl,"Allow",$ligne["Allow"],null,null,0,"font-size:14px")."</td>
	</tr>	
	
	<tr>
		<td style='width:50%' valign='top'>
			<table style='width:99%'>
				<tbody>
					$dd
				</tbody>
			</table>
		</td>
		<td style='width:50%' valign='top'>
			<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:14px' nowrap width=99%>{hourBegin}:</td>
						<td style='font-size:14px' nowrap width=1%>". Field_array_Hash($cron->cron_hours,"BEGINH",$TimeSpace["BEGINH"],null,null,0,"font-size:14px")."H</td>
						<td style='font-size:14px' nowrap width=99%>". Field_array_Hash($cron->cron_mins,"BEGINM",$TimeSpace["BEGINH"],null,null,0,"font-size:14px")."M</td>
					</tr>
					<tr><td colspan=3>&nbsp;</td></tr>
					<tr>
						<td class=legend style='font-size:14px' nowrap width=99%>{hourEnd}:</td>
						<td style='font-size:14px' nowrap width=1%>". Field_array_Hash($cron->cron_hours,"ENDH",$TimeSpace["ENDH"],null,null,0,"font-size:14px")."H</td>
						<td style='font-size:14px' nowrap width=99%>". Field_array_Hash($cron->cron_mins,"ENDM",$TimeSpace["ENDM"],null,null,0,"font-size:14px")."M</td>
					</tr>
				</tbody>
			</table>
		</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button($buttonname, "TimeSpaceDansTimes()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_TimeSpaceDansTimes= function (obj) {
		var res=obj.responseText;
		var ID=$ID;
		if(res.length>3){alert(res);}
		RefreshSquidConnectionTable();
		if(ID==-1){YahooWin2Hide();return;}
		RefreshTab('main_content_rule_editTtimerule');
	}
	
	function TimeSpaceDansTimes(){
		      var XHR = new XHRConnection();
		      XHR.appendData('TimeName', document.getElementById('TimeName').value);
		      XHR.appendData('EditTimeRule', 'yes');
		      XHR.appendData('ID', '$ID');
		      ". @implode("\n", $jsjs)."
		      XHR.appendData('BEGINH', document.getElementById('BEGINH').value);
		      XHR.appendData('BEGINM', document.getElementById('BEGINM').value);
		      XHR.appendData('ENDH', document.getElementById('ENDH').value);
		      XHR.appendData('ENDM', document.getElementById('ENDM').value);
		      XHR.appendData('Allow', document.getElementById('Allow').value);

		      
		      AnimateDiv('$t');
		      XHR.sendAndLoad('$page', 'POST',x_TimeSpaceDansTimes);  		
		}	

	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function rules(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$q->CheckTables();
	$time=$tpl->_ENGINE_parse_body("{time}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$new_time_rule=$tpl->_ENGINE_parse_body("{new_time_rule}");
	$t=time();		
	$html=$tpl->_ENGINE_parse_body("<div class=explain style='font-size:13px'>{connection_time_squid_text}</div>")."
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var TimeRuleIDTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?timerules-list=yes',
	dataType: 'json',
	colModel : [
		{display: '', name : 'Allow', width : 32, sortable : true, align: 'left'},
		{display: '$description', name : 'TimeName', width : 209, sortable : true, align: 'left'},
		{display: '$time', name : 'TimeText', width : 448, sortable : false, align: 'left'},
		{display: '', name : 'none2', width : 22, sortable : false, align: 'left'},
		{display: '', name : 'none3', width : 36, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_time_rule', bclass: 'add', onpress : AddTimeRule},
		],	
	searchitems : [
		{display: '$description', name : 'description'},
		],
	sortname: 'TimeName',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 825,
	height: 250,
	singleSelect: true
	
	});   
});
function AddTimeRule() {
	Loadjs('$page?EditTimeRule-js=yes&ID=-1');
	
}

function RefreshSquidConnectionTable(){
	$('#table-$t').flexReload();
}


	var x_TimeRuleDansDelete= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	
	var x_EnableDisableTimeRule= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}	
	}	
	
	function TimeRuleDansDelete(ID){
		TimeRuleIDTemp=ID;
		var XHR = new XHRConnection();
		XHR.appendData('EditTimeRule-delete', 'yes');
		XHR.appendData('ID', ID);
		XHR.sendAndLoad('$page', 'POST',x_TimeRuleDansDelete);  		
	}

	var x_TimeRuleDansDelete= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	
	function EnableDisableTimeRule(ID){
		var XHR = new XHRConnection();
		XHR.appendData('EnbleTimeRule', 'yes');
		XHR.appendData('ID', ID);
		if(document.getElementById('ruleTime_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableTimeRule);  		
	}		
	
	

	
</script>
	
	";
	
	echo $html;
	
}

function EditTimeRule_groups(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$items=$tpl->_ENGINE_parse_body("{group}");
	$new_item=$tpl->_ENGINE_parse_body("{link_group}");
	$t=time();		
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var DeleteGroupItemTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?EditTimeRule-groups-list=yes&ID=$ID',
	dataType: 'json',
	colModel : [
		
		{display: '$items', name : 'GroupName', width : 304, sortable : true, align: 'left'},
		{display: '', name : 'none2', width : 110, sortable : false, align: 'left'},
		{display: '', name : 'none3', width : 36, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_item', bclass: 'add', onpress : AddGroupInTimeRule},
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
	width: 492,
	height: 250,
	singleSelect: true
	
	});   
});
	function AddGroupInTimeRule() {
		Loadjs('BrowseSquidGroups.php?CallBack=AddGroupInTimeRulePerform');
		
	}	

	function RefreshSquidGroupTimeItemsTable(){
		$('#table-$t').flexReload();
	}


	var x_AddGroupInTimeRulePerform= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		RefreshSquidGroupTimeItemsTable();
	}	
	
	 
	
	function TimeRuleDeleteGroupLink(zMD5){
		TimeRuleDeleteGroupLinkTemp=zMD5;
		var XHR = new XHRConnection();
		XHR.appendData('EditTimeRule-groups-delete', 'yes');
		XHR.appendData('zMD5', zMD5);
		XHR.sendAndLoad('$page', 'POST',x_TimeRuleDeleteGroupLink);  		
	}

	var x_TimeRuleDeleteGroupLink= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowgplink'+TimeRuleDeleteGroupLinkTemp).remove();
	}
	
	function AddGroupInTimeRulePerform(ID){
		var XHR = new XHRConnection();
		XHR.appendData('EditTimeRule-groups-add','yes');
		XHR.appendData('gpid', ID);
		XHR.appendData('ID', $ID);
		XHR.sendAndLoad('$page', 'POST',x_AddGroupInTimeRulePerform);  		
	}		
	
	

	
</script>
	
	";
	
	echo $html;
	
}

function EditTimeRule_groups_add(){
	$gpid=$_POST["gpid"];
	$ID=$_POST["ID"];
	$zmd5=md5($gpid.$ID);
	$sql="INSERT OR IGNORE INTO webfilters_sqtimes_assoc (gpid,zMD5,TimeRuleID) VALUES($gpid,'$zmd5',$ID)";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}
function EditTimeRule_groups_delete(){
	$zMD5=$_POST["zMD5"];
	$sql="DELETE FROM webfilters_sqtimes_assoc WHERE zMD5='$zMD5'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}	
	
}


function EditTimeRule_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqtimes_rules WHERE ID='$ID'"));
	$TimeSpace=unserialize($ligne["TimeCode"]);	
	foreach ($_POST as $num=>$val){
		if(preg_match("#day_([0-9]+)#", $num,$re)){
			$TimeSpace["DAYS"][$re[1]]=$val;
		}
		
	}
	
	$TimeSpace["BEGINH"]=$_POST["BEGINH"];
	$TimeSpace["BEGINM"]=$_POST["BEGINM"];
	$TimeSpace["ENDH"]=$_POST["ENDH"];
	$TimeSpace["ENDM"]=$_POST["ENDM"];
	$TimeSpaceFinal=serialize($TimeSpace);
	
	$sqladd="INSERT INTO webfilters_sqtimes_rules (TimeName,TimeCode,enabled,`Allow`) 
	VALUES ('{$_POST["TimeName"]}','$TimeSpaceFinal','1','{$_POST["Allow"]}');";
	
	$sql="UPDATE webfilters_sqtimes_rules SET `Allow`={$_POST["Allow"]},TimeName='{$_POST["TimeName"]}',TimeCode='$TimeSpaceFinal' WHERE ID='$ID'";

	
	if($ID<1){$sql=$sqladd;}
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");	

}
function EditTimeRule_enable(){
$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$sql="UPDATE webfilters_sqtimes_rules SET `enabled`='{$_POST["enable"]}' WHERE ID=$ID";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");	
}
function EditTimeRule_delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	
	$q->QUERY_SQL("DELETE FROM webfilters_sqtimes_assoc WHERE TimeRuleID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$q->QUERY_SQL("DELETE FROM webfilters_sqtimes_rules WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");	
}

function EditTimeRule_groups_list(){
//	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	
	$search='%';
	$table="webfilters_sqtimes_assoc";
	$page=1;

	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`webfilters_sqgroups.{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT,webfilters_sqgroups.* FROM `webfilters_sqtimes_assoc`,`webfilters_sqgroups` WHERE 
		webfilters_sqtimes_assoc.gpid=webfilters_sqgroups.ID
		AND webfilters_sqtimes_assoc.TimeRuleID=$ID
		$searchstring";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `webfilters_sqtimes_assoc`,`webfilters_sqgroups` WHERE 
		webfilters_sqtimes_assoc.gpid=webfilters_sqgroups.ID
		AND webfilters_sqtimes_assoc.TimeRuleID=$ID";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	
		$sql="SELECT webfilters_sqgroups.*,webfilters_sqtimes_assoc.zMD5 FROM `webfilters_sqtimes_assoc`,`webfilters_sqgroups` WHERE 
		webfilters_sqtimes_assoc.gpid=webfilters_sqgroups.ID
		AND webfilters_sqtimes_assoc.TimeRuleID=$ID
		$searchstring $FORCE_FILTER $ORDER $limitSql";	
	
		
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($q->mysql_error,"", "",""));json_encode($data);return;}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysqli_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));json_encode($data);return;}
	
	
	$GroupType["src"]="{addr}";
	$GroupType["arp"]="{ComputerMacAddress}";
	$GroupType["dstdomain"]="{dstdomain}";
	$GroupType["proxy_auth"]="{members}";	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$val=0;
		$disable=Field_checkbox("ruleTime_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableTimeRule('{$ligne['ID']}')");
		$delete=imgtootltip("delete-24.png","{delete} $GroupName","TimeRuleDeleteGroupLink('{$ligne['zMD5']}')");
		$GroupName=utf8_encode($ligne["GroupName"]);
		$GroupTypeText=$tpl->_ENGINE_parse_body($GroupType[$ligne["GroupType"]]);
		
	$data['rows'][] = array(
		'id' => "gplink{$ligne['zMD5']}",
		'cell' => array(
		"<span style='font-size:16px;text-decoration:underline'>$GroupName</span>",
		"<span style='font-size:16px;text-decoration:underline'>$GroupTypeText</span>",
		$delete)
		);
	}
	
	
echo json_encode($data);	
}


function timerules_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="webfilters_sqtimes_rules";
	$page=1;

	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	
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
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($q->mysql_error,"", "",""));json_encode($data);return;}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysqli_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));json_encode($data);return;}
	
	$days=array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");
	
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$val=0;
		$disable=Field_checkbox("ruleTime_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableTimeRule('{$ligne['ID']}')");
		writelogs($ligne['TimeName'],__FUNCTION__,__FILE__,__LINE__);
		$ligne['TimeName']=utf8_encode($ligne['TimeName']);
		$TimeSpace=unserialize($ligne["TimeCode"]);
		$days=array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");
		$f=array();
		while (list ($num, $val) = each ($TimeSpace["DAYS"]) ){	
			if($num==array()){continue;}
			if(!isset($days[$num])){continue;}
			if($days[$num]==array()){continue;}
			if($val<>1){continue;}
			$f[]= "{{$days[$num]}}";
		}	
		
		
		if(strlen($TimeSpace["BEGINH"])==1){$TimeSpace["BEGINH"]="0{$TimeSpace["BEGINH"]}";}
		if(strlen($TimeSpace["BEGINM"])==1){$TimeSpace["BEGINM"]="0{$TimeSpace["BEGINM"]}";}
		if(strlen($TimeSpace["ENDH"])==1){$TimeSpace["ENDH"]="0{$TimeSpace["ENDH"]}";}
		if(strlen($TimeSpace["ENDM"])==1){$TimeSpace["ENDM"]="0{$TimeSpace["ENDM"]}";}

		
		$text=$tpl->_ENGINE_parse_body("{from} {$TimeSpace["BEGINH"]}:{$TimeSpace["BEGINM"]} {to} {$TimeSpace["ENDH"]}:{$TimeSpace["ENDM"]} (".@implode(", ", $f).")");
		$delete=imgtootltip("delete-24.png","{delete} {$ligne['ID']}","TimeRuleDansDelete('{$ligne['ID']}')");
		$picture="arrow-down-32.png";
		if($ligne["Allow"]==0){$picture="32-stop.png";}

		
	$data['rows'][] = array(
		'id' => "time{$ligne['ID']}",
		'cell' => array(
		"<img src='img/$picture'>",
		"<a href=\"javascript:blur();\" 
		OnClick=\"Loadjs('$MyPage?EditTimeRule-js=yes&RULEID={$ligne['ruleid']}&ID={$ligne['ID']}');\" 
		style='font-size:16px;text-decoration:underline'>{$ligne['TimeName']}</span>", $text,$disable,$delete)
		);
	}
	
	
echo json_encode($data);	
}

function EditTimeRule_template(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_sqtimes_rules WHERE ID='$ID'"));
	if($ligne["Allow"]==1){
		echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:14px'>{template_error_no_sense}<div>");
		return;
	}
	
	$array=unserialize(base64_decode($ligne["TemplateError"]));
	if(!isset($array["TITLE"])){$array["TITLE"]="Access denied for this time";}
	if(!isset($array["ERROR"])){$array["ERROR"]="The requested access could not be allowed";}
	if(!isset($array["EXPLAIN"])){$array["EXPLAIN"]="Your are not allowed to acces to internet at this time";}
	if(!isset($array["REASON"])){$array["REASON"]="Surfing to internet banned";}
	$t=time();
	
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{title_title}:</td>
		<td>". Field_text("template-TITLE",$array["TITLE"],"font-size:14px;width:100%")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{error_text}:</td>
		<td><textarea id='template-ERROR' 
		style='width:100%;height:50px;overflow:auto;font-size:14px;border:1px solid #CCCCCC'>{$array["ERROR"]}</textarea></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{explain}:</td>
		<td><textarea id='template-EXPLAIN' 
		style='width:100%;height:50px;overflow:auto;font-size:14px;border:1px solid #CCCCCC'>{$array["EXPLAIN"]}</textarea></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{reason}:</td>
		<td><textarea id='template-REASON' 
		style='width:100%;height:50px;overflow:auto;font-size:14px;border:1px solid #CCCCCC'>{$array["REASON"]}</textarea></td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveTimeRuleError()",16)."<td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
	
	var x_SaveTimeRuleError= function (obj) {
		var res=obj.responseText;
		var ID=$ID;
		if(obj.lenght>3){alert(obj.lenght);}
		RefreshTab('main_content_rule_editTtimerule');
	}
	
	function SaveTimeRuleError(){
		      var XHR = new XHRConnection();
		      XHR.appendData('EditTimeTemplate', 'yes');
		      XHR.appendData('ID', '$ID');
		      XHR.appendData('TITLE', document.getElementById('template-TITLE').value);
		      XHR.appendData('ERROR', document.getElementById('template-ERROR').value);
		      XHR.appendData('EXPLAIN', document.getElementById('template-EXPLAIN').value);
		      XHR.appendData('REASON', document.getElementById('template-REASON').value);
			  AnimateDiv('$t');
		      XHR.sendAndLoad('$page', 'POST',x_SaveTimeRuleError);  		
		}	

	</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function EditTimeRule_template_save(){
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$TemplateError=base64_encode(serialize($_POST));
	$sql="UPDATE webfilters_sqtimes_rules SET `TemplateError`='$TemplateError' WHERE ID={$_POST["ID"]}";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");	
	
	
}