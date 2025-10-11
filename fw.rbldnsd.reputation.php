<?php
//$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["pattern-js"])){pattern_js();exit;}
if(isset($_GET["blk-js"])){blacklist_js();exit;}
if(isset($_GET["whl-js"])){whitelist_js();exit;}



if(isset($_GET["pattern-popup"])){pattern_popup();exit;}
if(isset($_POST["pattern"])){pattern_save();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_POST["import"])){import_save();exit;}
if(isset($_GET["pattern-import-popup"])){pattern_import_popup();exit;}

table();

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$html[]="

	</div>
	<div class='ibox-content'>
	<div id='postfix-transactions'></div>

	</div>
	</div>
	";


	$html[]=$tpl->search_block($page,"postgres","ip_reputation","ip_reputation","");
	echo $tpl->_ENGINE_parse_body($html);

}

function delete(){
	$pattern=$_GET["delete"];
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM rbl_emails WHERE pattern='$pattern'");
	echo "$('#{$_GET["md"]}').remove()\n";
}


function blacklist_js(){
	$tpl=new template_admin();
	$ipaddr=$_GET["blk-js"];
	$description=gethostbyaddr($ipaddr);
	$md=$_GET["md"];

	$date=date("Y-m-d H:i:s");
	$q=new postgres_sql();

	$ligne=$q->mysqli_fetch_array("SELECT ipaddr FROM rbl_blacklists WHERE ipaddr='$ipaddr'");
	if($ligne["ipaddr"]<>null){
		echo "$('#{$md}').remove();\n";
		$q->QUERY_SQL("UPDATE ip_reputation SET isUnknown=0 WHERE  ipaddr='$ipaddr'");
		$tpl->js_display_results("Already blacklisted");
		return;
	}
	$ligne=$q->mysqli_fetch_array("SELECT ipaddr FROM rbl_whitelists WHERE ipaddr='$ipaddr'");
	if($ligne["ipaddr"]<>null){
		echo "$('#{$md}').remove();\n";
		$q->QUERY_SQL("UPDATE ip_reputation SET isUnknown=0 WHERE  ipaddr='$ipaddr'");
		$tpl->js_display_results("Already Whitelisted");
		return;
	}

	$q->QUERY_SQL("INSERT INTO rbl_blacklists (ipaddr,description,zDate) VALUES ('$ipaddr','$description','$date')");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

	$q->QUERY_SQL("UPDATE ip_reputation SET isUnknown=0 WHERE ipaddr='$ipaddr'");


	$AbuseIPApiKey=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AbuseIPApiKey");
	if($AbuseIPApiKey==null){
		echo "$('#{$md}').remove();\n";
		return;}


	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	$url="https://www.abuseipdb.com/report/json?key=$AbuseIPApiKey&category=11&ip=$ipaddr";
	$curl=new ccurl($url);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		artica_mysql_events(0,"REST Failed to AbuseIPDB ERR.$curl->error",@implode("\n", $curl->errors)."\n".$curl->data,__FILE__,__LINE__);
		echo "$('#{$md}').remove();\n";
		return;
	}

	echo "$('#{$md}').remove();\n";





}
function whitelist_js(){
	$tpl=new template_admin();
	$ipaddr=$_GET["whl-js"];
	$description=gethostbyaddr($ipaddr);
	$md=$_GET["md"];

	$date=date("Y-m-d H:i:s");
	$q=new postgres_sql();

	$ligne=$q->mysqli_fetch_array("SELECT ipaddr FROM rbl_blacklists WHERE ipaddr='$ipaddr'");
	if($ligne["ipaddr"]<>null){
		echo "$('#{$md}').remove();\n";
		$q->QUERY_SQL("UPDATE ip_reputation SET isUnknown=0 WHERE  ipaddr='$ipaddr'");
		$tpl->js_display_results("Already blacklisted");
		return;
	}
	$ligne=$q->mysqli_fetch_array("SELECT ipaddr FROM rbl_whitelists WHERE ipaddr='$ipaddr'");
	if($ligne["ipaddr"]<>null){
		echo "$('#{$md}').remove();\n";
		$q->QUERY_SQL("UPDATE ip_reputation SET isUnknown=0 WHERE  ipaddr='$ipaddr'");
		$tpl->js_display_results("Already Whitelisted");
		return;
	}

	$q->QUERY_SQL("INSERT INTO rbl_whitelists (ipaddr,description,zDate) VALUES ('$ipaddr','$description','$date')");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}

	$q->QUERY_SQL("UPDATE ip_reputation SET isUnknown=0 WHERE ipaddr='$ipaddr'");
	echo "$('#{$md}').remove();\n";


}


function pattern_import_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $title="{import}";
    $tpl->js_dialog($title, "$page?pattern-import-popup=yes&function={$_GET["function"]}");
}
function pattern_import_popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();

    $uid=$_SESSION["uid"];
    if($uid==-100){$uid="Manager";}
    $jsafter="BootstrapDialog1.close();{$_GET["function"]}()";

    $bt="{add}";
    $title="{new_address}";
    $form[]=$tpl->field_textareacode("import","{address}", null);
    echo $tpl->form_outside($title, $form,null,$bt,$jsafter,"AsDnsAdministrator",true);
}

function pattern_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new postgres_sql();
	$pattern=$_GET["pattern-popup"];
	$uid=$_SESSION["uid"];
	if($uid==-100){$uid="Manager";}
	$jsafter="BootstrapDialog1.close();{$_GET["function"]}()";
	if($pattern==null){
		$bt="{add}";
		$title="{new_address}";
		$form[]=$tpl->field_text("pattern","{address}", $pattern);
		$description="Added $uid - ".date("Y-m-d H:i:s");
	}else{
		$bt="{apply}";
		$form[]=$tpl->field_pattern("pattern", "{address}", null,true);
		$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM rbl_emails WHERE pattern='$pattern'"));
		$description=$ligne["description"];
		$title=$pattern." - {$ligne["zdate"]}";
	}
	$form[]=$tpl->field_text("description", "{description}", $description);
	echo $tpl->form_outside($title, $form,null,$bt,$jsafter,"AsDnsAdministrator",true);
}

function pattern_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ipclass=new IP();
	$pattern=strtolower($_POST["pattern"]);
	$description=$_POST["description"];
	$date=date("Y-m-d H:i:s");
	$q=new postgres_sql();

	$q->QUERY_SQL("INSERT INTO rbl_emails (pattern,description,zDate) VALUES ('$pattern','$description','$date') ON CONFLICT DO NOTHING");
	if(!$q->ok){echo "$pattern: $q->mysql_error";}


}

function import_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode("\n",$_POST["import"]);
    unset($_POST["import"]);
    foreach($tb as $item) {
        $_POST["pattern"]=$item;
        pattern_save();
    }
}


function search(){
	
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$q=new postgres_sql();
	$t=time();

	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/rbldnsd.compile.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/rbldnsd.compile.progress.log";
	$ARRAY["CMD"]="rbldnsd.php?compile=yes";
	$ARRAY["TITLE"]="{APP_RBLDNSD} {compile_rules}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-rbldnsd-restart')";
	
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?pattern-js=&function={$_GET["function"]}');\">";
	$html[]="<i class='fa fa-plus'></i> {new_address} </label>";


    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $html[]="<label class=\"btn btn btn-info\" OnClick=\"Loadjs('$page?pattern-import-js=&function={$_GET["function"]}');\">";
    $html[]="<i class='fas fa-file-import'></i> {import} </label>";

	
	$html[]="</div>";

	$topbuttons[] = array("Loadjs('$page?ipaddr-js=&function={$_GET["function"]}');", ico_plus, "{new_address}");
	$topbuttons[] = array("Loadjs('$page?ipaddr-import-js=yes&function={$_GET["function"]}');", ico_import, "{import}");
	$topbuttons[] = array($jsRestart, ico_run, "{compile_rules}");
	
	$search=trim($_GET["search"]);
	$subquery="isUnknown=1";

	if(preg_match("#^[a-zA-Z\-\.0-9\*]+$#",$search)){
		$search2=str_replace("*","%",$search);

		$subquery="(isUnknown=1) AND ( (hostname LIKE '$search2') OR ( TEXT(ipaddr) LIKE '$search2') )";

	}


	$aliases["pattern"]="pattern";
	$querys=$tpl->query_pattern($search,$aliases);
	$MAX=$querys["MAX"];
	if($MAX==0){$MAX=150;}
	$sql="SELECT * FROM (SELECT * FROM ip_reputation WHERE $subquery ) as t {$querys["Q"]} ORDER BY zdate DESC LIMIT $MAX";
	
	if(!$q->TABLE_EXISTS("rbl_emails")){$q->SMTP_TABLES();}
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $sql<br>$q->mysql_error");return;}

	$NOTE["Neutral"]="<span class='label label'>Neutral</span>";
	$NOTE["Poor"]="<span class='label label-danger'>Poor</span>";
	$NOTE["Good"]="<span class='label label-primary'>Good</span>";
	$NOTE[null]="<span class='label label'>Good</span>";


	$TRCLASS=null;
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>R</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>BLKS</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{hostname}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{blacklist}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{whitelist}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$ipaddr=$ligne["ipaddr"];
		$hostname=trim($ligne["hostname"]);
		if($hostname=="None"){$hostname=null;}
		if($hostname==null){
			$hostname=gethostbyaddr($ipaddr);
			echo "<strong>{{$hostname}}</strong><br>";
			$q->QUERY_SQL("UPDATE ip_reputation SET hostname='$hostname' WHERE ipaddr='$ipaddr'");
		}
		$count_of_blacklists=$ligne["count_of_blacklists"];


		$blacklist_button=$tpl->button_autnonome("{blacklist}",
			"Loadjs('$page?blk-js=$ipaddr&md=$md')","fas fa-ban","AsDnsAdministrator",0,"btn-danger");

		$whitelist_button=$tpl->button_autnonome("{whitelist}",
			"Loadjs('$page?whl-js=$ipaddr&md=$md')","fas fa-check-circle","AsDnsAdministrator",0,"btn-primary");


		$zDate=strtotime($ligne["zdate"]);
		$time=$tpl->time_to_date($zDate,true);
		$class_text=null;
		$email_reputation=$ligne["email_reputation"];
		$pattern=$tpl->td_href($ipaddr,null,"Loadjs('$page?pattern-js=$ipaddr&function={$_GET["function"]}');");
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' nowrap>{$time}</td>";
		$html[]="<td style='width:1%' nowrap>{$NOTE[$email_reputation]}</td>";
		$html[]="<td style='width:1%' nowrap>{$count_of_blacklists}</td>";
		$html[]="<td style='width:1%' nowrap><span class='$class_text'>$ipaddr</span></td>";
		$html[]="<td style='width:99%' nowrap><span class='$class_text'>$hostname</span></td>";
		$html[]="<td style='width:1%' class='center' nowrap>$blacklist_button</td>";
		$html[]="<td style='width:1%' class='center' nowrap>$whitelist_button</td>";
		$html[]="</tr>";
	
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

	$TINY_ARRAY["TITLE"]="{reputation} $APP_RBLDNSD_BLACKRECORDS {records}";
	$TINY_ARRAY["ICO"]=ico_database;
	$TINY_ARRAY["EXPL"]="{APP_RBLDNSD_EXPLAIN}";
	$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="<small>$sql</small>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
?>
