<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsPostfixAdministrator){exit();}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["query-js"])){query_js();exit;}
if(isset($_GET["query-popup"])){query_popup();exit;}
if(isset($_POST["FROM_DATE"])){query_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
page();

function query_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();	
	$tpl->js_dialog1("{new_query}", "$page?query-popup=yes");
	
}
function delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["delete-js"];
	$tpl->js_confirm_delete($id, "delete", $id,"$('#{$_GET["md"]}').remove()");
}
function delete(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$sock->getFrameWork("postfix2.php?history-delete={$_POST["delete"]}");
	$q=new lib_sqlite("/home/artica/SQLITE/postfix_events.db");
	$q->QUERY_SQL("DELETE FROM postfix_search WHERE ID='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}
function query_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();	
	$RunInvestigate=RunInvestigate();
	$form[]=$tpl->field_date("FROM_DATE","{from_date}",null);
	$form[]=$tpl->field_clock("FROM_TIME", "{from_time}", "00:00");
	$form[]=$tpl->field_numeric("maxlines", "{max_lines}", 200);
	$form[]=$tpl->field_text("STRING","{search}",null,true);
	echo $tpl->form_outside("{new_query}", $form,"{postfix_search_history_explain}","{search}","dialogInstance1.close();LoadAjax('table-postfix-investigate','$page?table=yes');$RunInvestigate","AsPostfixAdministrator");
	
}

function query_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$q=new lib_sqlite("/home/artica/SQLITE/postfix_events.db");
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `postfix_search` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`status` integer,
			`maxlines` integer,
			time integer,
			therms text,
			fsize INTEGER,
			fpath text ) ");
	
	$time=strtotime($_POST["FROM_DATE"]." ".$_POST["FROM_TIME"] );
	$_POST["STRING"]=sqlite_escape_string2($_POST["STRING"]);
	
	$q->QUERY_SQL("INSERT INTO postfix_search (`time`,`therms`,`status`,`maxlines`) VALUES ('$time','{$_POST["STRING"]}',0,'{$_POST["maxlines"]}')");
	if(!$q->ok){echo $q->mysql_error."<br>";}
	
	
	
}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{messaging} {investigate}</h1><i>{mail_investigate_explain}</i></div>
	</div>



	<div class='row'><div id='progress-postfix-investigate'></div>
	<div class='ibox-content'>

	<div id='table-postfix-investigate'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/mail-investigate');
	LoadAjax('table-postfix-investigate','$page?table=yes');

	</script>";
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,@implode("\n", $html));
		echo $tpl->build_firewall();
		return;
	}


	echo $tpl->_ENGINE_parse_body($html);

}

function RunInvestigate(){
	$page=CurrentPageName();
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/postfix.events.search.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/postfix.events.search.progress.txt";
	$ARRAY["CMD"]="postfix2.php?history-search=yes";
	$ARRAY["TITLE"]="{history} {search}";
	$ARRAY["AFTER"]="LoadAjax('table-postfix-investigate','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsApply="Loadjs('fw.progress.php?content=$prgress&mainid=progress-postfix-investigate')";
	return $jsApply;
}

function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/postfix_events.db");
	
	
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?query-js=yes');\">
				<i class='fa fas fa-search-plus'></i> {new_query} 
			</label>
			<label class=\"btn btn btn-warning\" OnClick=\"javascript:".RunInvestigate()."\">
				<i class='fas fa-play'></i> {run_search} 
			</label>			
			</div>";

	
	$html[]="<table id='table-my-postfix-search' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{from_date}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{search}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";	
	
	$results=$q->QUERY_SQL("SELECT * FROM postfix_search ORDER BY `time` DESC");
	
	$status[0]="<span class='label'>{scheduled}</span>";
	$status[1]="<span class='label label-primary'>{running}</span>";
	$status[2]="<span class='label label-success'>{completed}</span>";
	$status[3]="<span class='label label-danger'>{error}</span>";
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		
		$time=$tpl->time_to_date($ligne["time"],true);
		$size=FormatBytes($ligne["fsize"]/1024);
		$ID=$ligne["ID"];
		$md=md5(serialize($ligne));
		$therms=$ligne["therms"];
		$int_status=intval($ligne["status"]);
		
		$therms="<a href=\"/postfix-search/$ID.log\">$therms</a>";
		$download=$tpl->icon_download("direct:/postfix-search/$ID.log","AsPostfixAdministrator");
		if(intval($ligne["fsize"])==0){
			$therms=$ligne["therms"];
			$download=$tpl->icon_nothing();
		}
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1% nowrap>{$status[$int_status]}</td>";
		$html[]="<td width=1% nowrap>$time</td>";
		$html[]="<td width=1% nowrap>$size</td>";
		$html[]="<td><code>$therms</code></td>";
		$html[]="<td width=1% nowrap>$download</td>";
		$html[]="<td width=1% nowrap>".$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')","AsPostfixAdministrator")."</td>";
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
	$html[]="</table><div><i></i></div>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-my-postfix-search').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}
