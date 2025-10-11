<?php
//$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["search"])){search();exit;}
if(isset($_GET["pattern-js"])){pattern_js();exit;}
if(isset($_GET["pattern-import-js"])){pattern_import_js();exit;}
if(isset($_GET["pattern-popup"])){pattern_popup();exit;}
if(isset($_POST["pattern"])){pattern_save();exit;}
if(isset($_GET["delete"])){delete();exit;}
if(isset($_POST["import"])){import_save();exit;}
if(isset($_GET["pattern-import-popup"])){pattern_import_popup();exit;}
if(isset($_GET["enabled"])){pattern_enable();exit;}
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


	$html[]=$tpl->search_block($page,"postgres","rbl_emails","rbl_emails","");
	echo $tpl->_ENGINE_parse_body($html);

}

function delete(){
	$pattern=$_GET["delete"];
	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM rbl_emails WHERE pattern='$pattern'");
	echo "$('#{$_GET["md"]}').remove()\n";
}

function pattern_enable(){
	$tpl=new template_admin();
	$pattern=$_GET["enabled"];
	$q=new postgres_sql();
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM rbl_emails WHERE pattern='$pattern'");
	$enabled=intval($ligne["enabled"]);
	if($enabled==0){
		$q->QUERY_SQL("UPDATE rbl_emails SET enabled=1 WHERE pattern='$pattern'");
		if(!$q->ok){$tpl->js_microdaliog_danger("MySQL ERROR",$q->mysql_error);}
		return;
	}
	$q->QUERY_SQL("UPDATE rbl_emails SET enabled=0 WHERE pattern='$pattern'");
	if(!$q->ok){$tpl->js_microdaliog_danger("MySQL ERROR",$q->mysql_error);}
}

function pattern_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$pattern=$_GET["pattern-js"];
	if($pattern==null){$title="{new_entry}";}else{$title=$pattern;}
	$tpl->js_dialog($title, "$page?pattern-popup=$pattern&function={$_GET["function"]}");
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

	$topbuttons[] = array("Loadjs('$page?pattern-js=&function={$_GET["function"]}');", ico_plus, "{new_address}");
	$topbuttons[] = array("Loadjs('$page?pattern-import-js=&function={$_GET["function"]}');');", ico_import, "{import}");

	

	$search=trim($_GET["search"]);
	$aliases["pattern"]="pattern";
	$querys=$tpl->query_pattern($search,$aliases);
	$MAX=$querys["MAX"];
	if($MAX==0){$MAX=150;}
	$sql="SELECT * FROM rbl_emails {$querys["Q"]} ORDER BY zdate DESC LIMIT $MAX";
	
	if(!$q->TABLE_EXISTS("rbl_emails")){$q->SMTP_TABLES();}

	if(!$q->FIELD_EXISTS("rbl_emails","enabled")){
		$q->QUERY_SQL("ALTER TABLE rbl_emails ADD enabled smallint NOT NULL DEFAULT 1");
	}

	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128("LINE ".__LINE__." $sql<br>$q->mysql_error");return;}
	

	$TRCLASS=null;
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{time}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{pattern}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{description}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{enabled}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	while ($ligne = pg_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$pattern=$ligne["pattern"];
		$zDate=strtotime($ligne["zdate"]);
		$time=$tpl->time_to_date($zDate,true);
		$class_text=null;
		$enabled=intval($ligne["enabled"]);
		$description=$ligne["description"];
		$pattern=$tpl->td_href($pattern,null,"Loadjs('$page?pattern-js=$pattern&function={$_GET["function"]}');");
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='width:1%' nowrap>{$time}</td>";
		$html[]="<td style='width:1%' nowrap><span class='$class_text'>$pattern</span></td>";
		$html[]="<td style='width:99%' nowrap><span class='$class_text'>$description</span></td>";
		$html[]="<td style='width:1%' class='center' nowrap>".$tpl->icon_check($enabled,"Loadjs('$page?enabled={$ligne["pattern"]}&md=$md')")."</center></td>";
		$html[]="<td style='width:1%' class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?delete={$ligne["pattern"]}&md=$md')")."</center></td>";
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


	$TINY_ARRAY["TITLE"]="{blacklist} {emailAddress}";
	$TINY_ARRAY["ICO"]=ico_email;
	$TINY_ARRAY["EXPL"]="{APP_RBLDNSD_EXPLAIN}";
	$TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
	$jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="<small>$sql</small>
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
?>
