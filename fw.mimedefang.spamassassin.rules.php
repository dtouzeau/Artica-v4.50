<?php
// Service Pack 9 2026-10-10

$GLOBALS["ACTIONSAV"][0]="{block_attachments_and_pass}";
$GLOBALS["ACTIONSAV"][1]="{save_message_in_quarantine}";
$GLOBALS["ACTIONSAV"][2]="{remove_message}";
$GLOBALS["ACTIONSAV"][3]="{do_nothing}";


include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// Pentest 2026-11-04 : delete-rule-js/rule-js/popup/eth etaient recopies sans encodage dans la page (XSS).
foreach(array("delete-rule-js","rule-js","popup") as $xssk){if(isset($_GET[$xssk])){$_GET[$xssk]=preg_replace('/[^A-Za-z0-9]/','',(string)$_GET[$xssk]);}}
if(isset($_GET["eth"])){$_GET["eth"]=preg_replace('/[^A-Za-z0-9_.:@-]/','',(string)$_GET["eth"]);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_POST["zmd5"])){rule_save();exit;}
if(isset($_GET["delete-rule-js"])){rule_delete_js();exit;}
if(isset($_POST["delete"])){rule_delete_perform();exit;}


page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$MimeDefangClamav=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangSpamAssassin"));
	
	$error=null;
	$js="LoadAjax('table-loader-as-rules','$page?table=yes');";
	
	if($MimeDefangClamav==0){
		$error=$tpl->FATAL_ERROR_SHOW_128("{spamassassin_not_enabled}");
		$js=null;
	}


	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{antispam_rules}</h1>
	<p>{mimedefang_antispam_rules_explain}</p>$error
	</div>

	</div>



	<div class='row'><div id='progress-mimedf-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-as-rules'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/postfix-policies-asrules');
	$js

	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{antispam_rules}",$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function rule_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$md5=$_GET["delete-rule-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	// Pentest 2026-11-04 : delete-rule-js etait concatene dans la requete.
	$r=$q->QUERY_SQL_PARAMS("SELECT * FROM mimedefang_spamassassin WHERE zmd5=?",array((string)$md5));
	$ligne=isset($r[0]) ? $r[0] : array();
	$mailfrom=$ligne["mailfrom"];
	$mailto=$ligne["mailto"];
	$type_text=$tpl->javascript_parse_text($GLOBALS["ACTIONSAV"][$type]);
	$title="$mailfrom > $mailto $type_text";
	$tpl->js_confirm_delete($title, "delete", $md5,"$('#$md5').remove()");
}
function rule_delete_perform(){
	$md5=$_POST["delete"];
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	// Pentest 2026-11-04 : delete etait concatene dans la requete.
	$q->QUERY_SQL_PARAMS("DELETE FROM mimedefang_spamassassin WHERE zmd5=?",array((string)$md5));
	if(!$q->ok){echo $q->mysql_error;}
	
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$md5=$_GET["rule-js"];
	$title="{new_rule}";
	
	if($md5<>null){
		$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
		// Pentest 2026-11-04 : rule-js etait concatene dans la requete.
		$r=$q->QUERY_SQL_PARAMS("SELECT * FROM mimedefang_spamassassin WHERE zmd5=?",array((string)$md5));
		$ligne=isset($r[0]) ? $r[0] : array();
		$mailfrom=$ligne["mailfrom"];
		$mailto=$ligne["mailto"];
		$title="$mailfrom > $mailto";
	}
	
	$tpl->js_dialog1($title, "$page?popup=$md5");
	
}

function rule_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$zmd5=$_POST["zmd5"];
	$new_zmd5=md5($_POST["mailfrom"].$_POST["mailto"]);
	
	
	$sqladd="INSERT INTO mimedefang_spamassassin(zmd5,mailfrom,mailto,
				`XSpamStatusHeaderScore`,
				`SpamAssBlockWithRequiredScore`,
				`SpamAssassinRequiredScore`,
				`MimeDefangMaxQuartime`,
				`MimeDefangQuarteMail`,
				`MimeDefangQuartDest`
		) VALUES (?,?,?,?,?,?,?,?,?)";
	// Pentest 2026-11-04 : les champs du formulaire etaient concatenes dans la requete.
	$sqladdParams=array($new_zmd5,(string)$_POST["mailfrom"],(string)$_POST["mailto"],
			(string)$_POST["XSpamStatusHeaderScore"],
			(string)$_POST["SpamAssBlockWithRequiredScore"],
			(string)$_POST["SpamAssassinRequiredScore"],
			(string)$_POST["MimeDefangMaxQuartime"],
			(string)$_POST["MimeDefangQuarteMail"],
			(string)$_POST["MimeDefangQuartDest"]);
	
	// Pentest 2026-11-04 : mailfrom/mailto etaient recopies sans encodage dans la reponse (XSS).
	if($zmd5==null){
		$ligne=$q->mysqli_fetch_array("SELECT zmd5 FROM mimedefang_spamassassin WHERE zmd5='$zmd5'");
		if($ligne["zmd5"]<>null){echo req_html($_POST["mailfrom"])." --> ".req_html($_POST["mailto"])." Already exists\n";return;}
		$q->QUERY_SQL_PARAMS($sqladd,$sqladdParams);
		if(!$q->ok){echo htmlspecialchars($q->mysql_error,ENT_QUOTES);}
		return;
	}

	
	
	if($new_zmd5==$zmd5){
		// Pentest 2026-11-04 : ztype et zmd5 etaient concatenes dans la requete.
		$q->QUERY_SQL_PARAMS("UPDATE mimedefang_spamassassin SET `type`=? WHERE zmd5=?",array((string)$_POST["ztype"],(string)$zmd5));
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	
	$ligne=$q->mysqli_fetch_array("SELECT zmd5 FROM mimedefang_spamassassin WHERE zmd5='$new_zmd5'");
	if($ligne["zmd5"]<>null){echo req_html($_POST["mailfrom"])." --> ".req_html($_POST["mailto"])." Already exists\n";return;}
	
	$q->QUERY_SQL_PARAMS("DELETE FROM mimedefang_spamassassin WHERE zmd5=?",array((string)$zmd5));
	$q->QUERY_SQL_PARAMS($sqladd,$sqladdParams);
	if(!$q->ok){echo $q->mysql_error;}
	
}



function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$md5=$_GET["popup"];
	$title="{new_rule}";
	$bt="{add}";
	
	$times[10080]=$tpl->javascript_parse_text("7 {days}");
	$times[14400]=$tpl->javascript_parse_text("10 {days}");
	$times[21600]=$tpl->javascript_parse_text("15 {days}");
	$times[43200]=$tpl->javascript_parse_text("1 {month}");
	$times[129600]=$tpl->javascript_parse_text("3 {months}");
	
	if($md5<>null){
		$bt="{apply}";
		$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
		// Pentest 2026-11-04 : popup etait concatene dans la requete.
		$r=$q->QUERY_SQL_PARAMS("SELECT * FROM mimedefang_spamassassin WHERE zmd5=?",array((string)$md5));
		$ligne=isset($r[0]) ? $r[0] : array();
		$mailfrom=$ligne["mailfrom"];
		$mailto=$ligne["mailto"];
		$type=$ligne["type"];
		$type_text=$tpl->javascript_parse_text($GLOBALS["ACTIONSAV"][$type]);
		$title="$mailfrom > $mailto $type_text";
		$XSpamStatusHeaderScore=$ligne["XSpamStatusHeaderScore"];
		$SpamAssBlockWithRequiredScore=$ligne["SpamAssBlockWithRequiredScore"];
		$MimeDefangQuarteMail=$ligne["MimeDefangQuarteMail"];
		$MimeDefangMaxQuartime=$ligne["MimeDefangMaxQuartime"];
		$MimeDefangQuartDest=$ligne["MimeDefangQuartDest"];
		$SpamAssassinRequiredScore=$ligne["SpamAssassinRequiredScore"];
		
	}else{
		$XSpamStatusHeaderScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XSpamStatusHeaderScore"));
		if($XSpamStatusHeaderScore==0){$XSpamStatusHeaderScore=4;}
	
	
		$SpamAssassinRequiredScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinRequiredScore"));
		if($SpamAssassinRequiredScore==0){$SpamAssassinRequiredScore=8;}
	
		$SpamAssBlockWithRequiredScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssBlockWithRequiredScore"));
		if($SpamAssBlockWithRequiredScore==0){$SpamAssBlockWithRequiredScore=15;}
		

		$MimeDefangMaxQuartime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangMaxQuartime"));
		$MimeDefangQuarteMail=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangQuarteMail"));
		$MimeDefangQuartDest=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangQuartDest"));
		if($MimeDefangMaxQuartime==0){$MimeDefangMaxQuartime=129600;}
	}
	
	$js="dialogInstance1.close();LoadAjax('table-loader-as-rules','$page?table=yes');";
	
	$tpl->field_hidden("zmd5", $md5);
	$form[]=$tpl->field_text("mailfrom", "{sender}", $mailfrom);
	$form[]=$tpl->field_text("mailto", "{recipient}", $mailto);
	$form[]=$tpl->field_numeric("XSpamStatusHeaderScore", "{X-Spam-Status-header}", $XSpamStatusHeaderScore,"{X-Spam-Status-header-text}");
	$form[]=$tpl->field_numeric("SpamAssBlockWithRequiredScore", "{block_with_required_score}", $SpamAssBlockWithRequiredScore,"{block_with_required_score_text}");
	$form[]=$tpl->field_section("{quarantine}");
	$form[]=$tpl->field_numeric("SpamAssassinRequiredScore", "{required_score_quarantine}", $SpamAssassinRequiredScore,"{required_score_text}");
	$form[]=$tpl->field_array_hash($times, "MimeDefangMaxQuartime", "{retention} ({quarantine})", $MimeDefangMaxQuartime);
	$form[]=$tpl->field_checkbox("MimeDefangQuarteMail","{forward_quarantine_messages}",$MimeDefangQuarteMail,false,"{forward_quarantine_messages_text}");
	$form[]=$tpl->field_email("MimeDefangQuartDest", "{quarantine_email_address}", $MimeDefangQuartDest);
	
	echo $tpl->form_outside($title, $form,"{mimedefang_email_explain}",$bt,$js,"AsPostfixAdministrator",true);
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	
		$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `mimedefang_spamassassin` 
			( `zmd5` TEXT PRIMARY KEY NOT NULL,  
			 `mailfrom` TEXT NOT NULL, 
			 `mailto` TEXT NOT NULL,
			 `XSpamStatusHeaderScore` INTEGER,
			 `SpamAssBlockWithRequiredScore` INTEGER,
			 `SpamAssassinRequiredScore` INTEGER,
			 `MimeDefangQuarteMail` INTEGER,
			 `MimeDefangMaxQuartime` INTEGER,
			 `MimeDefangQuartDest` TEXT)");
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
	
	$jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=progress-mimedf-restart')";
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
	$html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart;\"><i class='fa fa-save'></i> {apply_rules} </label>";
	$html[]="</div>";
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true data-type='text'>{sender}</th>";
	$html[]="<th data-sortable=true data-type='text'>{recipients}</th>";
	$html[]="<th data-sortable=true data-type='text'>{explain}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-loader-webhttp-rules','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	$times[10080]=$tpl->javascript_parse_text("7 {days}");
	$times[14400]=$tpl->javascript_parse_text("10 {days}");
	$times[21600]=$tpl->javascript_parse_text("15 {days}");
	$times[43200]=$tpl->javascript_parse_text("1 {month}");
	$times[129600]=$tpl->javascript_parse_text("3 {months}");
	
	
	$TRCLASS=null;
	
	$results=$q->QUERY_SQL("SELECT * FROM mimedefang_spamassassin");
	
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=$ligne["zmd5"];
		$mailfrom=$ligne["mailfrom"];
		$mailto=$ligne["mailto"];
		$type=$ligne["type"];
		$type_text=$GLOBALS["ACTIONSAV"][$type];
		$SpamAssassinRequiredScore=null;
		
		if($mailfrom=="*"){$mailfrom="{everyone}";}
		if($mailto=="*"){$mailto="{everyone}";}
		$js="Loadjs('$page?rule-js=$zmd5',true);";
		
		$XSpamStatusHeaderScore=$ligne["XSpamStatusHeaderScore"];
		$SpamAssBlockWithRequiredScore=$ligne["SpamAssBlockWithRequiredScore"];
		$SpamAssassinRequiredScore=$ligne["SpamAssassinRequiredScore"];
		$MimeDefangQuarteMail=$ligne["MimeDefangQuarteMail"];
		$MimeDefangMaxQuartime=$ligne["MimeDefangMaxQuartime"];
		$MimeDefangQuartDest=$ligne["MimeDefangQuartDest"];
		
		if($MimeDefangQuarteMail==1){$SpamAssassinRequiredScore_text="{forward_to} $MimeDefangQuarteMail";}else{
			$SpamAssassinRequiredScore_text="<br>{storage}: <strong>{$times[$MimeDefangMaxQuartime]}</strong>";
		}
		$explain=array();
		$explain[]="{X-Spam-Status-header}: <strong>$XSpamStatusHeaderScore</strong>";
		$explain[]="{block_with_required_score}: <strong>$SpamAssBlockWithRequiredScore</strong>";
		$explain[]="{required_score_quarantine}: <strong>$SpamAssassinRequiredScore</strong>$SpamAssassinRequiredScore_text";
		
		$zexplain=@implode(", ", $explain);
		
		
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td><strong>". $tpl->td_href($mailfrom,null,$js)."</strong></td>";
		$html[]="<td><strong>". $tpl->td_href($mailto,null,$js)."</strong></td>";
		$html[]="<td style='width:1%' nowrap>". $tpl->td_href($zexplain,null,$js)."</td>";
		$html[]="<td width=1% class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-rule-js=$zmd5')","AsPostfixAdministrator") ."</center></td>";
		$html[]="</tr>";		
		
		
	}
	
	if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
	$XSpamStatusHeaderScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XSpamStatusHeaderScore"));
	if($XSpamStatusHeaderScore==0){$XSpamStatusHeaderScore=4;}
	
	
	$SpamAssassinRequiredScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinRequiredScore"));
	if($SpamAssassinRequiredScore==0){$SpamAssassinRequiredScore=8;}
	
	$SpamAssBlockWithRequiredScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssBlockWithRequiredScore"));
	if($SpamAssBlockWithRequiredScore==0){$SpamAssBlockWithRequiredScore=15;}
	
	
	$MimeDefangMaxQuartime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangMaxQuartime"));
	$MimeDefangQuarteMail=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangQuarteMail"));
	$MimeDefangQuartDest=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangQuartDest"));
	if($MimeDefangMaxQuartime==0){$MimeDefangMaxQuartime=129600;}
	
	if($MimeDefangQuarteMail==1){$SpamAssassinRequiredScore_text="<br>{forward_messages_to} <strong>$MimeDefangQuarteMail</strong>";}else{
		$SpamAssassinRequiredScore_text="<br>{storage}: <strong>{$times[$MimeDefangMaxQuartime]}</strong>";
	}
	$explain=array();
	$explain[]="{X-Spam-Status-header}: <strong>$XSpamStatusHeaderScore</strong>";
	$explain[]="{block_with_required_score}: <strong>$SpamAssBlockWithRequiredScore</strong>";
	$explain[]="{required_score_quarantine}: <strong>$SpamAssassinRequiredScore</strong>$SpamAssassinRequiredScore_text";
	
	$zexplain=@implode(", ", $explain);
	
	
	$html[]="<tr class='$TRCLASS' id='$zmd5'>";
	$html[]="<td><strong>{default} {everyone}</strong></td>";
	$html[]="<td><strong>{default} {everyone}</strong></td>";
	$html[]="<td style='width:1%' nowrap>$zexplain</td>";
	$html[]="<td width=1% class='center' nowrap>".$tpl->icon_nothing() ."</center></td>";
	$html[]="</tr>";
	
	
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