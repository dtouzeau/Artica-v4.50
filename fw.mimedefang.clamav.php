<?php
// Service Pack 9 2026-10-10

$GLOBALS["ACTIONSAV"][0]="{block_attachments_and_pass}";
$GLOBALS["ACTIONSAV"][1]="{save_message_in_quarantine}";
$GLOBALS["ACTIONSAV"][2]="{remove_message}";
$GLOBALS["ACTIONSAV"][3]="{do_nothing}";


include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
// Pentest 2026-11-04 : delete-rule-js, rule-js, popup (md5) et eth etaient recopies sans encodage dans la page (XSS).
foreach (array("delete-rule-js","rule-js","popup") as $xk){if(isset($_GET[$xk])){$_GET[$xk]=preg_replace('#[^A-Za-z0-9]#','',(string)$_GET[$xk]);}}
if(isset($_GET["eth"])){$_GET["eth"]=preg_replace('#[^A-Za-z0-9_.:@-]#','',(string)$_GET["eth"]);}
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
	$MimeDefangClamav=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangClamav"));
	$ClamAVDaemonInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClamAVDaemonInstalled"));
	$error=null;
	$js="LoadAjax('table-loader-av-rules','$page?table=yes');";
	
	if($MimeDefangClamav==0){
		$error=$tpl->FATAL_ERROR_SHOW_128("{clamav_not_enabled}");
		$js=null;
	}
	if($ClamAVDaemonInstalled==0){
		$error=$tpl->FATAL_ERROR_SHOW_128("{clamav_not_installed}");
		$js=null;		
	}

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{antivirus_rules}</h1>
	<p>{mimedefang_antivirus_rules_explain}</p>$error
	</div>

	</div>



	<div class='row'><div id='progress-mimedf-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-av-rules'></div>

	</div>
	</div>



	<script>
	$js

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function rule_delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$md5=$_GET["delete-rule-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	// Pentest 2026-11-04 : md5 etait concatene dans la requete.
	$r=$q->QUERY_SQL_PARAMS("SELECT * FROM mimedefang_antivirus WHERE zmd5=?",array($md5));
	$ligne=isset($r[0]) ? $r[0] : array();
	$mailfrom=$ligne["mailfrom"];
	$mailto=$ligne["mailto"];
	$type=$ligne["type"];
	$type_text=$tpl->javascript_parse_text($GLOBALS["ACTIONSAV"][$type]);
	$title="$mailfrom > $mailto $type_text ($type)";
	$tpl->js_confirm_delete($title, "delete", $md5,"$('#$md5').remove()");
}
function rule_delete_perform(){
	$md5=$_POST["delete"];
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	// Pentest 2026-11-04 : md5 etait concatene dans la requete.
	$q->QUERY_SQL_PARAMS("DELETE FROM mimedefang_antivirus WHERE zmd5=?",array($md5));
	if(!$q->ok){echo $q->mysql_error;}
	
}

function rule_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$md5=$_GET["rule-js"];
	$title="{new_rule}";
	
	if($md5<>null){
		$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
		// Pentest 2026-11-04 : md5 etait concatene dans la requete.
		$r=$q->QUERY_SQL_PARAMS("SELECT * FROM mimedefang_antivirus WHERE zmd5=?",array($md5));
		$ligne=isset($r[0]) ? $r[0] : array();
		$mailfrom=$ligne["mailfrom"];
		$mailto=$ligne["mailto"];
		$type=$ligne["type"];
		$type_text=$tpl->javascript_parse_text($GLOBALS["ACTIONSAV"][$type]);
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
	// Pentest 2026-11-04 : zmd5, mailfrom, mailto et ztype etaient concatenes dans la requete.
	if($zmd5<>null && !preg_match("/^[a-f0-9]{32}$/",$zmd5)){echo "Invalid rule identifier\n";return;}
	if($zmd5==null){
		$zmd5=md5($_POST["mailfrom"].$_POST["mailto"]);
		$r=$q->QUERY_SQL_PARAMS("SELECT zmd5 FROM mimedefang_antivirus WHERE zmd5=?",array($zmd5));
		$ligne=isset($r[0]) ? $r[0] : array();
		if(isset($ligne["zmd5"]) && $ligne["zmd5"]<>null){echo /* Pentest 2026-11-04 : mailfrom/mailto etaient recopies sans encodage dans la page (XSS). */ req_html($_POST["mailfrom"])." --> ".req_html($_POST["mailto"])." Already exists\n";return;}
		$q->QUERY_SQL_PARAMS("INSERT INTO mimedefang_antivirus(zmd5,mailfrom,mailto,`type`) VALUES (?,?,?,?)",array($zmd5,$_POST["mailfrom"],$_POST["mailto"],$_POST["ztype"]));
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	
	$new_zmd5=md5($_POST["mailfrom"].$_POST["mailto"]);
	if($new_zmd5==$zmd5){
		$q->QUERY_SQL_PARAMS("UPDATE mimedefang_antivirus SET `type`=? WHERE zmd5=?",array($_POST["ztype"],$zmd5));
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	
	$r=$q->QUERY_SQL_PARAMS("SELECT zmd5 FROM mimedefang_antivirus WHERE zmd5=?",array($new_zmd5));
	$ligne=isset($r[0]) ? $r[0] : array();
	if(isset($ligne["zmd5"]) && $ligne["zmd5"]<>null){echo /* Pentest 2026-11-04 : mailfrom/mailto etaient recopies sans encodage dans la page (XSS). */ req_html($_POST["mailfrom"])." --> ".req_html($_POST["mailto"])." Already exists\n";return;}
	
	$q->QUERY_SQL_PARAMS("DELETE FROM mimedefang_antivirus WHERE zmd5=?",array($zmd5));
	$q->QUERY_SQL_PARAMS("INSERT INTO mimedefang_antivirus(zmd5,mailfrom,mailto,`type`) VALUES (?,?,?,?)",array($new_zmd5,$_POST["mailfrom"],$_POST["mailto"],$_POST["ztype"]));
	if(!$q->ok){echo $q->mysql_error;}
	
}



function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$md5=$_GET["popup"];
	$title="{new_rule}";
	$bt="{add}";
	if($md5<>null){
		$bt="{apply}";
		$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
		// Pentest 2026-11-04 : md5 etait concatene dans la requete.
		$r=$q->QUERY_SQL_PARAMS("SELECT * FROM mimedefang_antivirus WHERE zmd5=?",array($md5));
		$ligne=isset($r[0]) ? $r[0] : array();
		$mailfrom=$ligne["mailfrom"];
		$mailto=$ligne["mailto"];
		$type=$ligne["type"];
		$type_text=$tpl->javascript_parse_text($GLOBALS["ACTIONSAV"][$type]);
		$title="$mailfrom > $mailto $type_text";
	}
	
	$js="dialogInstance1.close();LoadAjax('table-loader-av-rules','$page?table=yes');";
	
	$tpl->field_hidden("zmd5", $md5);
	$form[]=$tpl->field_text("mailfrom", "{sender}", $mailfrom);
	$form[]=$tpl->field_text("mailto", "{recipient}", $mailto);
	$form[]=$tpl->field_array_hash($GLOBALS["ACTIONSAV"], "ztype", "{action}", $type);
	
	echo $tpl->form_outside($title, $form,"{mimedefang_email_explain}",$bt,$js,"AsPostfixAdministrator",true);
}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `mimedefang_antivirus` ( `zmd5` TEXT PRIMARY KEY NOT NULL,  `mailfrom` TEXT NOT NULL, `mailto` TEXT NOT NULL,`type` INTEGER )");
	if(!$q->ok){
		echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;
	}
	$q->QUERY_SQL("CREATE INDEX IF NOT EXISTS mailfrom ON mimedefang_antivirus (mailfrom)");
	$q->QUERY_SQL("CREATE INDEX IF NOT EXISTS mailto ON mimedefang_antivirus (mailto)");
	
	
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
	$html[]="<th data-sortable=true data-type='text'>{action}</th>";
	$html[]="<th data-sortable=false>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$jsAfter="LoadAjax('table-loader-webhttp-rules','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	
	
	
	$TRCLASS=null;
	
	$results=$q->QUERY_SQL("SELECT * FROM mimedefang_antivirus");
	
	
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=$ligne["zmd5"];
		$mailfrom=$ligne["mailfrom"];
		$mailto=$ligne["mailto"];
		$type=$ligne["type"];
		$type_text=$GLOBALS["ACTIONSAV"][$type];
		
		if($mailfrom=="*"){$mailfrom="{everyone}";}
		if($mailto=="*"){$mailto="{everyone}";}
		$js="Loadjs('$page?rule-js=$zmd5',true);";
		
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td><strong>". $tpl->td_href($mailfrom,null,$js)."</strong></td>";
		$html[]="<td><strong>". $tpl->td_href($mailto,null,$js)."</strong></td>";
		$html[]="<td style='width:1%' nowrap><strong>". $tpl->td_href($type_text,null,$js)."</strong></td>";
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