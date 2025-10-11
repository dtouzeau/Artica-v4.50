<?php



include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-rules"])){table_rules();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_POST["zmd5"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete"])){rule_delete_perform();exit;}
if(isset($_GET["backup-table"])){backup_table();exit;}


page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MimeDefangArchiver=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangArchiver",true));
	
	$error=null;
	$js="LoadAjax('table-loader-backup','$page?tabs=yes');";
	
	
	if($MimeDefangArchiver==0){
		$error=$tpl->FATAL_ERROR_SHOW_128("{backup_feature_not_enabled}");
		$js=null;
	}


	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{backupemail_behavior}</h1>
	<p>{backupemail_behavior_explain}</p>$error
	</div>

	</div>



	<div class='row'><div id='progress-mimedf-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-backup'></div>

	</div>
	</div>



	<script>
	$js

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function timeslist(){
	$tpl=new template_admin();
	$GLOBALS["TIMES"][10080]=$tpl->javascript_parse_text("7 {days}");
	$GLOBALS["TIMES"][14400]=$tpl->javascript_parse_text("10 {days}");
	$GLOBALS["TIMES"][21600]=$tpl->javascript_parse_text("15 {days}");
	$GLOBALS["TIMES"][43200]=$tpl->javascript_parse_text("1 {month}");
	$GLOBALS["TIMES"][129600]=$tpl->javascript_parse_text("3 {months}");
	$GLOBALS["TIMES"][259200]=$tpl->javascript_parse_text("6 {months}");
	$GLOBALS["TIMES"][518400]=$tpl->javascript_parse_text("1 {year}");
	$GLOBALS["TIMES"][1036800]=$tpl->javascript_parse_text("2 {years}");
	$GLOBALS["TIMES"][2073600]=$tpl->javascript_parse_text("4 {years}");
	$GLOBALS["TIMES"][4147200]=$tpl->javascript_parse_text("8 {years}");
}

function backup_table(){
	
	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{backuped_emails}"]="fw.mimedefang.backup.table.php";
	$array["{rules}"]="$page?table=yes";
	echo $tpl->tabs_default($array);
}

function rule_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$md5=$_GET["delete-rule-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM mimedefang_backup WHERE zmd5='$md5'");
	$mailfrom=$ligne["mailfrom"];
	$mailto=$ligne["mailto"];
	$type=$ligne["retentiontime"];
	timeslist();
	$type_text=$tpl->javascript_parse_text($GLOBALS["TIMES"][$type]);
	$title="$mailfrom > $mailto $type_text ($type)";
	$tpl->js_confirm_delete($title, "delete", $md5,"$('#$md5').remove()");
}
function rule_delete_perform(){
	$md5=$_POST["delete"];
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$q->QUERY_SQL("DELETE FROM mimedefang_backup WHERE zmd5='$md5'");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$md5=$_GET["rule-js"];
	$title="{new_rule}";
	timeslist();
	if($md5<>null){
		$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM mimedefang_backup WHERE zmd5='$md5'");
		$mailfrom=$ligne["mailfrom"];
		$mailto=$ligne["mailto"];
		$type=$ligne["retentiontime"];
		$type_text=$tpl->javascript_parse_text($GLOBALS["TIMES"][$type]);
		$title="$mailfrom > $mailto $type_text ($type)";
	}
	
	$tpl->js_dialog1($title, "$page?popup=$md5");
	
}

function rule_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$zmd5=$_POST["zmd5"];
	if($zmd5==null){
		$zmd5=md5($_POST["mailfrom"].$_POST["mailto"]);
		$ligne=$q->mysqli_fetch_array("SELECT zmd5 FROM mimedefang_backup WHERE zmd5='$zmd5'");
		if($ligne["zmd5"]<>null){echo "{$_POST["mailfrom"]} --> {$_POST["mailto"]} Already exists\n";return;}
		$q->QUERY_SQL("INSERT INTO mimedefang_backup(zmd5,mailfrom,mailto,`retentiontime`) VALUES ('$zmd5','{$_POST["mailfrom"]}','{$_POST["mailto"]}','{$_POST["retention"]}')");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	
	$new_zmd5=md5($_POST["mailfrom"].$_POST["mailto"]);
	if($new_zmd5==$zmd5){
		$q->QUERY_SQL("UPDATE mimedefang_backup SET `retentiontime`='{$_POST["retention"]}' WHERE zmd5='$zmd5'");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	
	$ligne=$q->mysqli_fetch_array("SELECT zmd5 FROM mimedefang_backup WHERE zmd5='$new_zmd5'");
	if($ligne["zmd5"]<>null){echo "{$_POST["mailfrom"]} --> {$_POST["mailto"]} Already exists\n";return;}
	
	$q->QUERY_SQL("DELETE FROM mimedefang_backup WHERE zmd5='$zmd5'");
	$q->QUERY_SQL("INSERT INTO mimedefang_backup(zmd5,mailfrom,mailto,`retentiontime`) VALUES ('$new_zmd5','{$_POST["mailfrom"]}','{$_POST["mailto"]}','{$_POST["retention"]}')");
	if(!$q->ok){echo $q->mysql_error;}
	
}



function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$md5=$_GET["popup"];
	$title="{new_rule}";
	$bt="{add}";
	timeslist();
	if($md5<>null){
		$bt="{apply}";
		$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
		$ligne=$q->mysqli_fetch_array("SELECT * FROM mimedefang_backup WHERE zmd5='$md5'");
		$mailfrom=$ligne["mailfrom"];
		$mailto=$ligne["mailto"];
		$retention=$ligne["retentiontime"];
		$retention_text=$tpl->javascript_parse_text($GLOBALS["TIMES"][$retention]);
		$title="$mailfrom > $mailto $retention_text";
	}
	
	$js="dialogInstance1.close();LoadAjax('table-loader-backup-rules','$page?table-rules=yes');";
	
	$tpl->field_hidden("zmd5", $md5);
	$form[]=$tpl->field_text("mailfrom", "{sender}", $mailfrom);
	$form[]=$tpl->field_text("mailto", "{recipient}", $mailto);
	
	$form[]=$tpl->field_array_hash($GLOBALS["TIMES"], "retention", "{retention}", $retention);
	
	echo $tpl->form_outside($title, $form,"{mimedefang_email_explain}",$bt,$js,"AsPostfixAdministrator",true);
}

function table(){
	$page=CurrentPageName();
	echo "<div id='table-loader-backup-rules'></div><script>LoadAjax('table-loader-backup-rules','$page?table-rules=yes');</script>";
	return;
	
}

function table_rules(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	timeslist();
	
	if(!$q->ok){
		echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;
	}
	
	
	
	$t=time();
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress.log";
	
	
	$ARRAY["CMD"]="mimedefang.php?reload=yes";
	$ARRAY["TITLE"]="{apply_parameters}";
	$prgress=base64_encode(serialize($ARRAY));
	$add="Loadjs('$page?rule-js=',true);";
	
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-mimedf-restart')";
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
	$html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart;\"><i class='fa fa-save'></i> {apply_rules} </label>";
	$html[]="</div>";
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true data-type='text'>{sender}</th>";
	$html[]="<th data-sortable=true data-type='text'>{recipients}</th>";
	$html[]="<th data-sortable=true data-type='text'>{retention}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-loader-webhttp-rules','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	
	
	
	$TRCLASS=null;
	
	$results=$q->QUERY_SQL("SELECT * FROM mimedefang_backup");
	
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=$ligne["zmd5"];
		$mailfrom=$ligne["mailfrom"];
		$mailto=$ligne["mailto"];
		$retention=$ligne["retentiontime"];
		$retention_text=$GLOBALS["TIMES"][$retention];
		
		if($mailfrom=="*"){$mailfrom="{everyone}";}
		if($mailto=="*"){$mailto="{everyone}";}
		$js="Loadjs('$page?rule-js=$zmd5',true);";
		
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td><strong>". $tpl->td_href($mailfrom,null,$js)."</strong></td>";
		$html[]="<td><strong>". $tpl->td_href($mailto,null,$js)."</strong></td>";
		$html[]="<td width=1% nowrap><strong>". $tpl->td_href($retention_text,null,$js)."</strong></td>";
		$html[]="<td width=1% class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-rule-js=$zmd5')","AsPostfixAdministrator") ."</center></td>";
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
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}