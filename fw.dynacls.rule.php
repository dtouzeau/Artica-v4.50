<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
//https://192.168.1.110:9000/fw.dynacls.rule.php?gpid=2


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ruleid-js"])){rule_js();exit;}
if(isset($_GET["rule-popup"])){rule_popup();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){delete_js();exit;}
if(isset($_GET["delete-confirm"])){delete_confirm();exit;}
if(isset($_POST["delete-remove"])){delete_remove();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new mysql_squid_builder();
	$gpid=$_GET["gpid"];
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT GroupName,params FROM webfilters_sqgroups WHERE ID=$gpid"));
	$params=unserialize(base64_decode($ligne["params"]));
	
	if($params["allow_duration"]==0){
		if($params["duration"]>0){
			$duration=$params["duration"];
			if($duration>0){$duration_text="<br>{limited_to} ".$q->durations[$duration];}
		}
	}
	
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-8\"><h1 class=ng-binding>{dynamic_acls_newbee}</h1>
	<H2>{$ligne["GroupName"]}</h2>
	<p><i>{$params["dynamic_description"]}$duration_text</i></p>
	</div>
	
	</div>
	<div class='row'><div id='progress-firehol-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader'></div>

	</div>
	</div>
		
		
		
	<script>
	LoadAjax('table-loader','$page?table=yes&gpid=$gpid');
		
	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function delete_confirm(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=time();
	$ID=intval($_GET["delete-confirm"]);
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT type,value,gpid FROM webfilter_aclsdynamic WHERE ID='$ID'"));
	$type=$tpl->_ENGINE_parse_body("{$q->acl_GroupTypeDynamic[$ligne["type"]]}");
	
	

	$html="
	<div class=row>
	<div class=\"alert alert-danger\">{delete} $type {$ligne["value"]} ?</div>
	<div style='text-align:right;margin-top:20px'><button class='btn btn-danger btn-lg' type='button'
	OnClick=\"javascript:Remove$t()\">{yes_delete_it}</button></div>
	</div>
<script>
	var xPost$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	DialogConfirm.close();
	LoadAjax('table-loader','$page?table=yes&gpid={$ligne["gpid"]}');
}

function Remove$t(){
	var XHR = new XHRConnection();
	XHR.appendData('delete-remove', '$ID');
	XHR.sendAndLoad('$page', 'POST',xPost$t);
}
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
}
function delete_js(){
	$ID=$_GET["delete-rule-js"];
	$q=new mysql_squid_builder();
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT value FROM webfilter_aclsdynamic WHERE ID='$ID'"));
	$tpl->js_dialog_confirm("{delete} {$ligne["value"]}", "$page?delete-confirm=$ID");
}
function delete_remove(){
	$ID=intval($_POST["delete-remove"]);
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilter_aclsdynamic WHERE ID='$ID'");
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}
}

function rule_js(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$tpl=new template_admin();
	$ruleid=intval($_GET["ruleid-js"]);
	if($ruleid==0){
		$title="{new_rule}";
	}else{
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT value FROM webfilter_aclsdynamic WHERE ID='$ruleid'"));
	}
	$tpl->js_dialog("{rule}: $ruleid {$ligne["value"]}","$page?rule-popup=$ruleid&gpid={$_GET["gpid"]}");
}

function rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["rule-popup"]);
	$q=new mysql_squid_builder();
	$btname="{add}";
	$enabled=1;
	$gpid=$_GET["gpid"];
	$title="{new_rule}";
	$description="New rule";
	$ligne["duration"]=0;
	
	$BootstrapDialog=null;
	if($ID>0){
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM webfilter_aclsdynamic WHERE ID='$ID'"));
		$enabled=$ligne["enabled"];
		$description=stripslashes($ligne["description"]);
		$gpid=$ligne["gpid"];
		$title="{rule} $ID)::{$ligne["value"]}";
		$btname="{apply}";
	}
	$ligne2=mysqli_fetch_array($q->QUERY_SQL("SELECT params FROM webfilters_sqgroups WHERE ID='$gpid'"));
	$params=unserialize(base64_decode($ligne2["params"]));
	if($ID==0){$ligne["duration"]=$params["duration"];$BootstrapDialog="BootstrapDialog1.close();";}	

	
	$tpl->field_hidden("gpid", $gpid);
	$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_checkbox("enabled","{enabled}",$enabled,true);
	$form[]=$tpl->field_array_hash($q->acl_GroupTypeDynamic,"type","{type}",$ligne["type"],true);
	$form[]=$tpl->field_text("value","{pattern}",$ligne["value"]);
	if(intval($params["allow_duration"])==1){
		$form[]=$tpl->field_array_hash($q->durations,"duration","{duration}",$ligne["duration"],true);
	}
	$form[]=$tpl->field_text("description","{description}",utf8_encode($ligne["description"]));
	echo $tpl->form_outside($title,@implode("\n", $form),"{dynaacl_howto}",$btname,
			"LoadAjax('table-loader','$page?table=yes&gpid=$gpid');$BootstrapDialog");
	
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	$eth_sql=null;
	$token=null;
	$class=null;
	$order=$tpl->_ENGINE_parse_body("{order}");
	$value=$tpl->javascript_parse_text("{value}");
	$description=$tpl->javascript_parse_text("{description}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$FORCE_FILTER="AND gpid='{$_GET["gpid"]}'";
	$owner=$tpl->_ENGINE_parse_body("{owner}");
	
	$t=time();
	$add="Loadjs('$page?ruleid-js=0&gpid={$_GET["gpid"]}',true);";

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.acls.dynamic.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.acls.dynamic.progress.txt";
	$ARRAY["CMD"]="squid2.php?acls-dynamic=yes";
	$ARRAY["TITLE"]="{apply_rules}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";

	$html[]=$tpl->_ENGINE_parse_body("

			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules} </label>
			</div>
			<div class=\"btn-group\" data-toggle=\"buttons\">
			</div>
			<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' >$type</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$value</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$owner}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{$description}</th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="<th data-sortable=false></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&gpid={$_GET["gpid"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	$q=new mysql_squid_builder();
	
	$results=$q->QUERY_SQL("SELECT * FROM webfilter_aclsdynamic WHERE gpid='{$_GET["gpid"]}'");
	$TRCLASS=null;
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$type=$ligne["type"];
		$pattern=$ligne["pattern"];
		$ID=$ligne["ID"];
		$duration=null;
		$finish=null;
		$square_class="text-navy";
		$text_class="text-primary";
		$color="black";
		$square="fa-check-square-o";
		$icon="<span class='label label-primary'>".$tpl->_ENGINE_parse_body("{enabled}")."</span>";
		$type=$tpl->_ENGINE_parse_body("{$q->acl_GroupTypeDynamic[$ligne["type"]]}");
		$ligne["who"]=str_replace("-100", "SuperAdmin", $ligne["who"]);
		if($ligne["who"]<>null){$ligne["who"]="{$ligne["who"]}";}
		$delete=$tpl->icon_delete("Loadjs('$page?delete-rule-js={$ligne["ID"]}')");
		
		if($ligne["duration"]>0){
			if($ligne["maxtime"]>time()){
				$finish=distanceOfTimeInWords(time(),$ligne["maxtime"]);
				$duration="&nbsp;<i>{active_for} {$q->durations[$ligne["duration"]]} ({expire_in}: {$finish})</i>";
				
			}else{
				$duration="&nbsp;<i>{expired}</i>";
				$ligne["enabled"]=0;
			}
			$duration=$tpl->_ENGINE_parse_body($duration);
		}
		
		$js="Loadjs('$page?ruleid-js={$ligne["ID"]}&gpid={$_GET["gpid"]}',true);";
		
		$description="{$ligne["description"]}$duration";
		$pattern=$ligne["value"];
		$href="href=\"javascript:blur();\" OnClick=\"javascript:$js\"";
		if(intval($ligne["enabled"])==0){
			$text_class="text-muted";
			$color="#8a8a8a";
			$icon="<span class='label'>".$tpl->_ENGINE_parse_body("{disabled}")."</span>";
		}
		
		
		
		$html[]="<tr class='$TRCLASS'>";
		$html[]="<td class=\"$text_class\"><a $href style='font-weight:bold;color:$color;text-decoration:underline'>{$type}</a></td>";
		$html[]="<td class='$text_class' style='vertical-align:middle'><a $href style='font-weight:bold;color:$color;text-decoration:underline'>{$pattern}</a></span></td>";
		$html[]="<td class='$text_class' style='vertical-align:middle'><span style='color:$color'>{$ligne["who"]}</span></td>";
		$html[]="<td class='$text_class' style='vertical-align:middle'><span style='color:$color'>$description</span></td>";
		$html[]="<td style='vertical-align:middle'><center>$icon</center></td>";
		$html[]="<td style='vertical-align:middle'><center>$delete</center></td>";
		$html[]="</tr>";

			}




	$html[]="</tbody>";
$html[]="<tfoot>";
$html[]="<tr>";
$html[]="<td colspan='4'>";
$html[]="<ul class='pagination pull-right'></ul>";
$html[]="</td>";
$html[]="</tr>";
$html[]="</tfoot>";
$html[]="</table>";
$html[]="
<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });

var xRuleGroupUpDown$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	LoadAjax('table-loader','$page?table=yes');
}

function RuleGroupUpDown$t(ID,direction,eth){
	var XHR = new XHRConnection();
	XHR.appendData('rule-order', ID);
	XHR.appendData('direction', direction);
	XHR.appendData('eth', eth);
	XHR.sendAndLoad('firehol.nic.rules.php', 'POST',xRuleGroupUpDown$t);
}
</script>";

echo @implode("\n", $html);

}
function rule_save(){
	$q=new mysql_squid_builder();

	$tpl=new templates();
	$gpid=$_POST["gpid"];
	$ruleid=$_POST["ID"];
	$function=__FUNCTION__;
	$file=__FILE__;
	$lineNumber=__LINE__;
	$sock=new sockets();
	
	$_POST["description"]=url_decode_special_tool($_POST["description"]);
	$_POST["value"]=url_decode_special_tool($_POST["value"]);
	
	$hostname=$sock->getFrameWork("system.php?hostname-g=yes");

	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID=$gpid"));
	$GroupName=$ligne["GroupName"];

	if($_POST["description"]==null){
		$_POST["description"]=$tpl->javascript_parse_text("{$q->acl_GroupTypeDynamic[$_POST["type"]]} = {$_POST["value"]}");
	}

	if(!$q->TABLE_EXISTS("webfilter_aclsdynamic")){$q->CheckTables();}
	if(!$q->FIELD_EXISTS("webfilter_aclsdynamic", "maxtime")){ $q->QUERY_SQL("ALTER TABLE `webfilter_aclsdynamic` ADD `maxtime` INT UNSIGNED , ADD INDEX ( `maxtime` )"); }
	if(!$q->FIELD_EXISTS("webfilter_aclsdynamic", "duration")){ $q->QUERY_SQL("ALTER TABLE `webfilter_aclsdynamic` ADD `duration` INT UNSIGNED ,ADD INDEX ( `duration` )"); }
	

	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT params FROM webfilters_sqgroups WHERE ID='$gpid'"));
	$tpl=new templates();
	$params=unserialize(base64_decode($ligne["params"]));

	$finaltime=0;
	$duration=0;
	if(isset($_POST["duration"])){
		if($params["allow_duration"]==1){
			if($_POST["duration"]>0){
				$duration=$_POST["duration"];
				$finaltime = strtotime("+{$_POST["duration"]} minutes", time());
			}
		}
	}


	if($params["allow_duration"]==0){
		if($params["duration"]>0){
			$duration=$params["duration"];
			$finaltime = strtotime("+{$params["duration"]} minutes", time());
		}
	}
	$q=new mysql_squid_builder();
	$uid=mysql_escape_string2($_SESSION["uid"]);
	
	if($ruleid>0){$logtype="Update item";}else{$logtype="Create item";}
	$description_log="{$q->acl_GroupTypeDynamic[$ligne["type"]]} {$_POST["value"]} {$_POST["description"]}";
	$zdate=date("Y-m-d H:i:s");


	$q2=new mysql();
	$xtime=time();
	$q2=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $description_log=str_replace("'","`",$description_log);
    $description_log=$q2->sqlite_escape_string2($description_log);
    $logtype=$q2->sqlite_escape_string2($logtype);

    $q2->QUERY_SQL("INSERT OR IGNORE INTO `squid_admin_mysql`
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES
			('$xtime','$description_log','{$logtype} in proxy object $GroupName ','$function','$file','$lineNumber','2')","artica_events");

    if(!$q2->ok){writelogs("SQL ERROR $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
	
	$_POST["description"]=mysql_escape_string2($_POST["description"]);
	$_POST["value"]=mysql_escape_string2($_POST["value"]);

	if($ruleid>0){
		$sql="UPDATE webfilter_aclsdynamic
		SET `type`='{$_POST["type"]}',
		`value`='{$_POST["value"]}',
		`description`='{$_POST["description"]}',
		`maxtime`='$finaltime',
		`duration`='$duration',
		`enabled`='{$_POST["enabled"]}'
		WHERE ID=$ruleid
		";

	}else{
		
	$sql="INSERT IGNORE INTO webfilter_aclsdynamic
	(`gpid`,`type`,`value`,`description`,`who`,`maxtime`,`duration`,`enabled`)
	VALUES ('$gpid','{$_POST["type"]}','{$_POST["value"]}','{$_POST["description"]}',
			'$uid','{$finaltime}','$duration','{$_POST["enabled"]}')";
		}

	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	}