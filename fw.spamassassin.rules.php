<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

$GLOBALS["RULES_TYPE"]["body"]="{spam_body_item}";
$GLOBALS["RULES_TYPE"]["rawbody"]="{spam_rawbody_item}";
$GLOBALS["RULES_TYPE"]["full"]="{spam_full_item}";
$GLOBALS["RULES_TYPE"]["uri"]="{spam_uri_item}";
$GLOBALS["RULES_TYPE"]["header"]="{spam_header_item}";


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["meta-rule-js"])){meta_rule_js();exit;}
if(isset($_GET["meta-rule-popup"])){meta_rule_popup();exit;}
if(isset($_GET["meta-rule-tabs"])){meta_rule_tabs();exit;}
if(isset($_GET["meta-rule-delete"])){meta_rule_delete();exit;}
if(isset($_POST["meta-rule-delete"])){meta_rule_delete_perform();exit;}
if(isset($_GET["meta-rule-enable"])){meta_rule_enable();exit;}
if(isset($_GET["subrules-start"])){subrules_start();exit;}
if(isset($_GET["subrules-table"])){subrules_table();exit;}
if(isset($_GET["subrule-popup"])){subrules_popup();exit;}
if(isset($_GET["subrule-js"])){subrules_js();exit;}
if(isset($_GET["subrules-enable"])){subrules_enable();exit;}
if(isset($_GET["subrules-delete"])){subrules_delete();exit;}


if(isset($_POST["meta-id"])){meta_rule_save();exit;}
if(isset($_POST["subruleid"])){subrules_save();exit;}

start();

function start(){
	$page=CurrentPageName();
	
	echo "
	<div id='spammassassin-table-progress'></div>		
	<div style='margin-top:10px' id='spammassassin-table'></div>
		<script>LoadAjax('spammassassin-table','$page?table=yes');</script>
	";
	
	
}

function subrules_start(){
	$page=CurrentPageName();
	$ID=intval($_GET["subrules-start"]);
	echo "<div style='margin-top:10px' id='subrules-table-$ID'></div>
	<script>LoadAjax('subrules-table-$ID','$page?subrules-table=$ID');</script>
	";
	
}

function subrules_delete(){
	$md=$_GET["md"];
	$ID=intval($_GET["subrules-delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$q->QUERY_SQL("DELETE FROM sub_rules WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	header("content-type: application/x-javascript");
	echo "$('#$md').remove();\n";
	$page=CurrentPageName();
	echo "if(document.getElementById('spammassassin-table') ){ LoadAjaxSilent('spammassassin-table','$page?table=yes'); }";
	
}


function meta_rule_js(){
	$ID=intval($_GET["meta-rule-js"]);
	
	if(isset($_GET["session"])){
		$ID=intval($_SESSION["SPAMASSID"]);
	}
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	if($ID==0){
		$tpl->js_dialog1("{new_rule}", "$page?meta-rule-popup=0",850);
		return;
	}
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array("SELECT rulename FROM `meta_rules` WHERE ID=$ID");
	$md=md5(serialize($ligne));
	$rulename=$ligne["rulename"];
	$tpl->js_dialog1("$rulename", "$page?meta-rule-tabs=$ID",900);
	
}

function subrules_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["subrule-js"]);
	$ruleid=intval($_GET["ruleid"]);
	if($ID==0){
		$title="{new_rule}";
	}else{
		$title="{rule}:$ID";
	}
	$tpl->js_dialog2("$title", "$page?subrule-popup=$ID&ruleid=$ruleid",900);
	
	
}

function subrules_popup(){




}

function meta_rule_tabs(){
	$ID=intval($_GET["meta-rule-tabs"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array("SELECT rulename FROM `meta_rules` WHERE ID=$ID");
	
	$array[$ligne["rulename"]]="$page?meta-rule-popup=$ID";
	$array["{rules}"]="fw.spamassassin.rules.php?subrules-start=$ID";
	echo $tpl->tabs_default($array);
}

function meta_rule_delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["meta-rule-delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$md=$_GET["md"];
	$ligne=$q->mysqli_fetch_array("SELECT rulename FROM `meta_rules` WHERE ID=$ID");
	$tpl->js_confirm_delete($ligne["rulename"], "meta-rule-delete", $ID,"$('#$md').remove()");
}
function meta_rule_enable(){
	$ID=$_GET["meta-rule-enable"];
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM `meta_rules` WHERE ID=$ID");
	if($ligne["enabled"]==0){
		$q->QUERY_SQL("UPDATE meta_rules SET enabled='1' WHERE ID=$ID");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	
	$q->QUERY_SQL("UPDATE meta_rules SET enabled='0' WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}
}
function subrules_enable(){
	$ID=$_GET["subrules-enable"];
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM `sub_rules` WHERE ID=$ID");
	if($ligne["enabled"]==0){
		$q->QUERY_SQL("UPDATE sub_rules SET enabled='1' WHERE ID=$ID");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}

	$q->QUERY_SQL("UPDATE sub_rules SET enabled='0' WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}
	
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	echo "if(document.getElementById('spammassassin-table') ){
				LoadAjaxSilent('spammassassin-table','$page?table=yes');
			}";
	
}

function meta_rule_delete_perform(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_POST["meta-rule-delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$q->QUERY_SQL("DELETE FROM sub_rules WHERE meta_id='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
	$q->QUERY_SQL("DELETE FROM meta_rules WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
}

function subrules_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$ID=intval($_GET["subrule-popup"]);
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ruleid=$_GET["ruleid"];
	$ligne=$q->mysqli_fetch_array("SELECT rulename FROM `meta_rules` WHERE ID=$ruleid");
	
	$rulename=$ligne["rulename"];
	$title="$rulename: {rule}: {$rulename}_{$ID}";
	
	$ligne=$q->mysqli_fetch_array("SELECT * FROM `sub_rules` WHERE ID=$ID");
	
	
	
	$btn="{apply}";
	$jsafter="UpdateTables$t();";
	if($ID==0){
		$title="$rulename: {new_rule}";
		$btn="{add}";
		$jsafter="UpdateTables$t();dialogInstance2.close();";
	}
	

	$tpl->field_hidden("subruleid", $ID);
	$tpl->field_hidden("ruleid", $ruleid);
	$form[]=$tpl->field_array_hash($GLOBALS["RULES_TYPE"], "ruletype","{type_of_rule}", $ligne["ruletype"]);
	$form[]=$tpl->field_browse_smtp_header("header", "{header}", $ligne["header"]);
	$form[]=$tpl->field_text("pattern", "{pattern}", base64_decode($ligne["pattern"]));
	echo $tpl->form_outside($title, $form,"{spam_subrule_explain}",$btn,$jsafter,"AsPostfixAdministrator").
	
	"<script>
		function UpdateTables$t(){
			if(document.getElementById('subrules-table-$ruleid') ){
				LoadAjaxSilent('subrules-table-$ruleid','$page?subrules-table=$ruleid');
			}
			if(document.getElementById('spammassassin-table') ){
				LoadAjaxSilent('spammassassin-table','$page?table=yes');
			}
		
		}
	</script>";
	
}

function subrules_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=$_POST["subruleid"];
	$ruleid=$_POST["ruleid"];
	$ruletype=$_POST["ruletype"];
	$header=$_POST["header"];
	
	if($ruletype=="header"){
		if($header==null){echo "Header is mandatory";return;}
	}

	$pattern=base64_encode($_POST["pattern"]);
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	if($ID==0){
		$sql="INSERT INTO sub_rules (meta_id,ruletype,enabled,header,pattern)
		VALUES('$ruleid','$ruletype','1','$header','$pattern')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}
	
	$sql="UPDATE sub_rules SET ruletype='$ruletype', header='$header',pattern='$pattern' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	
}


function meta_rule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["meta-rule-popup"]);
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM `meta_rules` WHERE ID=$ID");
	
/*	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	`rulename` TEXT ,
	`describe` TEXT,
	`enabled` INTEGER NOT NULL DEFAULT 1,
	`finalscore` INTEGER NOT NULL,
	`calculation` INTEGER )";
	
*/	
	
	$calculation[1]="{all_rules_matches}";
	$calculation[2]="{one_of_rule_matches}";
	$title=$ligne["rulename"];
	$btn="{apply}";
	$jsafter="LoadAjax('spammassassin-table','$page?table=yes');";
	
	
	if($ID==0){
		$title="{new_rule}";
		$btn="{add}";
		$jsafter="dialogInstance1.close();LoadAjax('spammassassin-table','$page?table=yes');Loadjs('$page?meta-rule-js=0&session=yes');";
	}
	
	
	$tpl->field_hidden("meta-id", $ID);
	
	if(intval($ligne["calculation"])==0){$ligne["calculation"]=1;}
	if(intval($ligne["finalscore"])==0){$ligne["finalscore"]=4;}
	$form[]=$tpl->field_text("rulename", "{rulename}", $ligne["rulename"]);
	$form[]=$tpl->field_text("describe","{description}",$ligne["describe"]);
	$form[]=$tpl->field_numeric("finalscore","{score}",$ligne["finalscore"]);
	$form[]=$tpl->field_array_hash($calculation, "calculation","{calculation}", $ligne["calculation"]);
	
	echo $tpl->form_outside($title, $form,null,$btn,$jsafter,"AsPostfixAdministrator");
	
	
}

function meta_rule_save(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["meta-id"]);
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	
	$rulename=$_POST["rulename"];
	$rulename=replace_accents($rulename);
	$rulename=str_replace(" ", "_", $rulename);
	$rulename=str_replace("[", "", $rulename);
	$rulename=str_replace("]", "", $rulename);
	$rulename=str_replace("(", "", $rulename);
	$rulename=str_replace(")", "", $rulename);
	$rulename=str_replace("{", "", $rulename);
	$rulename=str_replace("}", "", $rulename);
	$rulename=str_replace("-", "_", $rulename);
	$rulename=str_replace("@", "", $rulename);
	$rulename=str_replace("#", "", $rulename);
	$rulename=str_replace("\"", "", $rulename);
	$rulename=str_replace("'", "", $rulename);
	$rulename=str_replace("~", "", $rulename);
	$rulename=str_replace("&", "", $rulename);
	$rulename=str_replace("+", "", $rulename);
	$rulename=str_replace("=", "", $rulename);
	$rulename=str_replace("*", "", $rulename);
	$rulename=str_replace("|", "_", $rulename);
	$rulename=str_replace("\\", "_", $rulename);
	$rulename=str_replace("/", "_", $rulename);
	$rulename=str_replace("!", "", $rulename);
	$rulename=str_replace("%", "", $rulename);
	$rulename=str_replace(":", "", $rulename);
	$rulename=str_replace(";", "", $rulename);
	$rulename=str_replace("?", "", $rulename);
	$rulename=str_replace(",", "", $rulename);
	$rulename=str_replace("€", "", $rulename);
	$rulename=str_replace("$", "", $rulename);
	
	$rulename=strtoupper($rulename);
	
	$_POST["describe"]=$q->sqlite_escape_string2($_POST["describe"]);
	
	if($ID==0){
		$sql="INSERT INTO meta_rules (rulename,describe,finalscore,calculation,enabled) 
		VALUES ('$rulename','{$_POST["describe"]}','{$_POST["finalscore"]}','{$_POST["calculation"]}',1)";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
		$_SESSION["SPAMASSID"]=$q->last_id;
		return;
	}
	
	$sql="UPDATE meta_rules SET rulename='$rulename', 
	describe='{$_POST["describe"]}',
	finalscore='{$_POST["finalscore"]}',
	calculation='{$_POST["calculation"]}' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function subrules_table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$TRCLASS=null;
	$t=time();
	$ID=intval($_GET["subrules-table"]);
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$sql="SELECT * FROM `sub_rules` WHERE meta_id=$ID ORDER BY ID DESC";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}
	
	

	$add="Loadjs('$page?subrule-js=0&ruleid=$ID')";
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
	$html[]="</div>";
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\"></div>";
	
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{type_of_rule}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{value}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1%>{enable}</th>";
	$html[]="<th data-sortable=false width=1%>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	

	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$ID=$ligne["ID"];
		$md=md5(serialize($ligne));
		$enabled=$ligne["enabled"];
		$ruletype=$ligne["ruletype"];
		$header=$ligne["header"];
		$pattern=base64_decode($ligne["pattern"]);
		$ruleid=$ligne["meta_id"];
		
		$TypeText=$GLOBALS["RULES_TYPE"][$ruletype];
		if($ruletype=="header"){
			$pattern="$header: $pattern";
		}
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>". $tpl->td_href($TypeText,null,"Loadjs('$page?subrule-js={$ligne['ID']}&ruleid=$ruleid')")."</strong>";
		$html[]="<td>$pattern</td>";
		$html[]="<td width=1% class='center' nowrap>". $tpl->icon_check($ligne["enabled"],"Loadjs('$page?subrules-enable=$ID')",null,"AsPostfixAdministrator")."</center></td>";
		$html[]="<td width=1% class='center' nowrap>". $tpl->icon_delete("Loadjs('$page?subrules-delete={$ligne['ID']}&md=$md')","AsPostfixAdministrator")."</center></td>";
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
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$TRCLASS=null;
	$t=time();

	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$sql="SELECT * FROM `meta_rules` ORDER BY rulename";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);return;}

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/spamassassin.urls.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/spamassassin.urls.progress.log";
	$ARRAY["CMD"]="milter-spamass.php?urls-database=yess";
	$ARRAY["TITLE"]="{building_rules}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=spammassassin-table-progress');";
	

	$add="Loadjs('$page?meta-rule-js=0')";

	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_rule} </label>";
	$html[]="<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_parameters} </label>";
	$html[]="</div>";
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\"></div>";

	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{rulename}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{explain}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1%>{enable}</th>";
	$html[]="<th data-sortable=false width=1%>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	


	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$ID=$ligne["ID"];
		$md=md5(serialize($ligne));
		$enabled=$ligne["enabled"];
		$rulename=$ligne["rulename"];
		$EXPLAIN=EXPLAIN_THIS($ligne);


		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>". $tpl->td_href($rulename,null,"Loadjs('$page?meta-rule-js={$ligne['ID']}')")."</strong>";
		$html[]="<td>$EXPLAIN</td>";
		$html[]="<td width=1% class='center' nowrap>". $tpl->icon_check($ligne["enabled"],"Loadjs('$page?meta-rule-enable=$ID')",null,"AsPostfixAdministrator")."</center></td>";
		$html[]="<td width=1% class='center' nowrap>". $tpl->icon_delete("Loadjs('$page?meta-rule-delete={$ligne['ID']}&md=$md')","AsPostfixAdministrator")."</center></td>";
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
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";


	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function EXPLAIN_SUBRULES($ID){
	$page=CurrentPageName();
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$sql="SELECT * FROM `sub_rules` WHERE meta_id=$ID AND enabled=1 ORDER BY ID DESC";
	$tpl=new template_admin();
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		$IDrow=$ligne["ID"];
		$md=md5(serialize($ligne));
		$enabled=$ligne["enabled"];
		$ruletype=$ligne["ruletype"];
		$header=$ligne["header"];
		$pattern=base64_decode($ligne["pattern"]);
		$TypeText=$GLOBALS["RULES_TYPE"][$ruletype];
		if($ruletype=="header"){
			$TypeText=$TypeText." $header";
		}
		$f[]=$tpl->td_href("R.$IDrow $TypeText",null,"Loadjs('$page?subrule-js=$IDrow&ruleid=$ID');");
	}
	
	return $f;
	
}

function EXPLAIN_THIS($ligne){
	
	$EXPLAIN_SUBRULES=EXPLAIN_SUBRULES($ligne["ID"]);
	
	if(count($EXPLAIN_SUBRULES)==0){
		return "{$ligne["describe"]}<br><small>{do_nothing} ({no_rule})</small>";
		
	}
	
	$finalscore=intval($ligne["finalscore"]);
	$XSpamStatusHeaderScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XSpamStatusHeaderScore"));
	$SpamAssassinRequiredScore=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinRequiredScore"));
	$block_with_required_score=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssBlockWithRequiredScore"));
	
	if($XSpamStatusHeaderScore==0){$XSpamStatusHeaderScore=4;}
	if($SpamAssassinRequiredScore==0){$SpamAssassinRequiredScore=8;}
	if($block_with_required_score==0){$block_with_required_score=15;}
	
	if($ligne["describe"]<>null){
		$f[]="{$ligne["describe"]}";
	}
	
	$f[]="<small>{for_elements}: ".@implode(", ", $EXPLAIN_SUBRULES);
	
	if($finalscore<$XSpamStatusHeaderScore){
		$textscore="{do_nothing} ({whitelist})";
	}
	
	if($finalscore>=$XSpamStatusHeaderScore){
		$textscore="{X-Spam-Status-header}";
	}
	if($finalscore>=$SpamAssassinRequiredScore){
		$textscore="{put_messages_in_quarantine}";
	}
	if($finalscore>=$block_with_required_score){
		$textscore="{reject_messages}";
	}
	
	$f[]="{affect_score} <strong>$finalscore</strong> {and} $textscore";
	
	return @implode("<br>", $f)."</small>";
	
	
}