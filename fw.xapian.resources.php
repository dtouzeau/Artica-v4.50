<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["folder-js"])){folder_js();exit;}
if(isset($_GET["folder-popup"])){folder_popup();exit;}
if(isset($_POST["XapianSearchInterface"])){save();exit;}
if(isset($_POST["ID"])){folder_save();exit;}
if(isset($_GET["repair-table"])){repair_table();exit;}
if(isset($_POST["repairtable"])){repairtable();exit;}
if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete_resource"])){delete_perform();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{InstantSearch} &raquo;&raquo; {resources}</h1>
	<p>{InstantSearch_resources_explain}</p>
	</div>

	</div>



	<div class='row'><div id='progress-xapian-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-xapian-service'></div>

	</div>
	</div>



	<script>
	LoadAjax('table-loader-xapian-service','$page?table=yes');

	</script>";

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function enable_js(){
	$t=time();
	$ID=$_GET["enable-js"];
	$page=CurrentPageName();
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT enabled FROM xapian_folders WHERE ID='$ID'","artica_backup"));
	if(!$q->ok){echo "alert('$q->mysql_error')";return;}

	if(intval($ligne["enabled"])==0){$enable=1;}else{$enable=0;}

	$q->QUERY_SQL("UPDATE xapian_folders SET enabled='$enable' WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo "alert('$q->mysql_error')";}

}

function delete_js(){
	$t=time();
	$tpl=new template_admin();
	$ID=intval($_GET["delete-js"]);
	if($ID==0){return;}
	$md=$_GET["md"];
	$page=CurrentPageName();
	$q=new mysql();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ressourcename FROM xapian_folders WHERE ID='$ID'","artica_backup"));
	$tpl->js_confirm_delete("{XAPIAN_DELETE_RESOURCE} {$ligne["ressourcename"]}","delete_resource",$ID,"$('#$md').remove()");
}
function delete_perform(){
	$ID=intval($_POST["delete_resource"]);
	
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM xapian_folders  WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("xapian.php?delete-db=$ID");
}

function repair_table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){return null;}
	$tpl->js_confirm_delete("{repair_table_explain}", "repairtable", "yes","LoadAjax('table-loader-xapian-service','$page?table=yes');");
}
function repairtable(){
	$q=new mysql();
	$q->DELETE_TABLE("xapian_folders", "artica_backup");
	include_once(dirname(__FILE__).'/ressources/class.mysql.xapian.builder.inc');
	$q=new mysql_xapian_builder();
	if(!$q->build()){return;}
	$sock=new sockets();
	$sock->getFrameWork("xapian.php?delete-all=yes");
}

function folder_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["folder-js"]);
	if($ID==0){$title=$tpl->javascript_parse_text("{add_shared_folder}");}
	if($ID>0){
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ressourcename FROM xapian_folders WHERE ID=$ID","artica_backup"));
		$title=$tpl->javascript_parse_text("{resource} $ID {$ligne["ressourcename"]}");
	}
	
	
	$tpl->js_dialog1($title, "$page?folder-popup=$ID");	
}
function folder_popup(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ID=intval($_GET["folder-popup"]);
	$jsafter="LoadAjax('table-loader-xapian-service','$page?table=yes');";
	if($ID==0){
		$title=$tpl->_ENGINE_parse_body("{add_shared_folder}");
		$btname="{add}";
		$jsafter="dialogInstance1.close();LoadAjax('table-loader-xapian-service','$page?table=yes');";
		$ligne["enabled"]=1;
		$ligne["zorder"]=1;
		$ligne["depth"]=5;
		$ligne["stemming"]="none";
	}else{
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT * FROM xapian_folders WHERE ID=$ID","artica_backup"));
		$title=$tpl->_ENGINE_parse_body("{resource} $ID {$ligne["ressourcename"]}");
		$btname="{apply}";
	}
	
	$stemming_line="english arabic armenian basque catalan danish dutch earlyenglish english finnish french german german2 hungarian italian kraaij_pohlmann lovins norwegian porter portuguese romanian russian spanish swedish turkish";
	$stemming["none"]="{none}";
	$ztem=explode(" ",$stemming_line);
	foreach ($ztem as $line){
		$stemming[$line]=$line;
	}
	
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_hidden("ztype", "smb");
	
	$form[]=$tpl->field_section("{shared_folder}");
	$form[]=$tpl->field_checkbox("enabled", "{enabled}", $ligne["enabled"],true);
	$form[]=$tpl->field_text("ressourcename", "{resource_name}", $ligne["ressourcename"]);
	$form[]=$tpl->field_text("ressourceexplain", "{description}", $ligne["ressourceexplain"]);

	
	$form[]=$tpl->field_section("{resource}");
	if($ID==0){
		$form[]=$tpl->field_text("hostname", "{hostname}", $ligne["hostname"]);
		$form[]=$tpl->field_text("sfolder", "{shared_folder}", $ligne["sfolder"]);
		$form[]=$tpl->field_text("tfolder", "{target_folder}", $ligne["tfolder"]);

	}else{
		$form[]=$tpl->field_text("sfolder", "{shared_folder}", $ligne["sfolder"]);
		$form[]=$tpl->field_text("tfolder", "{target_folder}", $ligne["tfolder"]);
		$form[]=$tpl->field_info("hostname", "{hostname}", $ligne["hostname"]);

		$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/omindex.single.progress";
		$ARRAY["LOG_FILE"]=PROGRESS_DIR."/omindex.single.log";
		$ARRAY["CMD"]="xapian.php?scanid=$ID";
		$ARRAY["TITLE"]="{InstantSearch}";
		$ARRAY["AFTER"]="LoadAjax('table-loader-xapian-service','$page?table=yes');";
		$prgress=base64_encode(serialize($ARRAY));
		$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=scanner-single-file-$ID')";
		
		$tpl->form_add_button("{analyze}", $jsrestart);
	}
		
	$form[]=$tpl->field_section("{parameters}");
	$form[]=$tpl->field_array_hash($stemming, "stemming", "{stemming}", $ligne["stemming"],false,"{stemming_explain}");
	$form[]=$tpl->field_checkbox("reindex", "{reindex}", $ligne["reindex"],false,"{xapian_reindex_explain}");
	$form[]=$tpl->field_numeric("depth","{depth}",$ligne["depth"],"{xapian_depth_explain}");
	$form[]=$tpl->field_numeric("zorder","{order}",$ligne["zorder"],"{xapian_order_explain}");
	
	$form[]=$tpl->field_section("{security}");
	$form[]=$tpl->field_text("username","{username}",$ligne["username"]);
	$form[]=$tpl->field_text("workgroup", "{workgroup}", $ligne["workgroup"]);
	$form[]=$tpl->field_password2("password", "{password}", $ligne["password"]);
	
	$html[]=$tpl->form_outside($title, @implode("\n", $form),$ligne["ressourceexplain"],$btname,$jsafter,"AsSystemAdministrator");
	$html[]="<div id='scanner-single-file-$ID'></div>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function folder_save(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$q=new mysql();

	$tpl->CLEAN_POST();
	$tpl->MYSQL_POST();
	
	include_once(dirname(__FILE__).'/ressources/class.mysql.xapian.builder.inc');
	$mysql_xapian_builder=new mysql_xapian_builder();
	if(!$mysql_xapian_builder->build()){return;}
	
	$_POST["directory"]=md5(serialize($_POST));
	$_POST["tfolder"]=str_replace("\\", "/", $_POST["tfolder"]);
	$_POST["tfolder"]=str_replace("//", "/", $_POST["tfolder"]);
	
	$fields=explode(",","ressourcename,ressourceexplain,directory,stemming,depth,hostname,sfolder,tfolder,reindex,enabled,username,password,workgroup,zorder,ztype");
	
	foreach ($fields as $field){
		$insertA[]="`$field`";
		$insertB[]="'{$_POST[$field]}'";
		$updateA[]="`$field`='{$_POST[$field]}'";
		
	}
	
	
	$ID=$_POST["ID"];
	if($ID==0){
		$sql="INSERT IGNORE INTO xapian_folders (". @implode(",", $insertA).") VALUES (".@implode(",", $insertB).")";
	}else{
		$sql="UPDATE xapian_folders SET ".@implode(",", $updateA)." WHERE ID=$ID";
	}
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."<hr>$sql";}
	
}
	


function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$sock=new sockets();
	$page=CurrentPageName();
	$q=new mysql();
	
	$database='artica_backup';
	$UnboundEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));

	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/omindex.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/omindex.log";
	$ARRAY["CMD"]="xapian.php?scan=yes";
	$ARRAY["TITLE"]="{InstantSearch}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-xapian-service','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-xapian-restart')";

	$delete=$tpl->javascript_parse_text("{delete}");
	$new_entry=$tpl->_ENGINE_parse_body("{new_record}");
	$t=time();
	$content=$tpl->_ENGINE_parse_body("{content}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$record=$tpl->_ENGINE_parse_body("{record}");
	$domains=$tpl->_ENGINE_parse_body("{domains}");
	$dashboard=$tpl->_ENGINE_parse_body("{dashboard}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$apply=$tpl->_ENGINE_parse_body("{apply}");
	$local_domains=$tpl->_ENGINE_parse_body("{local_domains}");
	$add_shared_folder=$tpl->_ENGINE_parse_body("{add_shared_folder}");
	$domain_id=intval($_GET["master-domain"]);
	
	$droptable=null;
	$users=new usersMenus();
	if($users->AsSystemAdministrator){$droptable="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?repair-table=yes')\"><i class='fa fa-sign-in'></i> {repair_table} </label>";}
	
	$html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?folder-js=0');;\">
				<i class='fa fa-plus'></i> $add_shared_folder </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-sign-in'></i> {launch_scan} </label>
			$droptable
			</div>");


	$html[]="<table id='table-xapian-folders' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	


	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{resource}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='text-align:right'>{skipped}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{last_scan}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{$delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$sql="SELECT * FROM xapian_folders ORDER BY zorder";
	$results = $q->QUERY_SQL($sql,$database);
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$ScannedTime=$tpl->icon_nothing();
		$md=md5(serialize($ligne));
		$ID=$ligne["ID"];
		$enabled=intval($ligne["enabled"]);
		$indexed=intval($ligne["indexed"]);
		$filesnumber=FormatNumber($ligne["filesnumber"]);
		$SkippedFiles=FormatNumber($ligne["SkippedFiles"]);
		$ressourcename=trim($ligne["ressourcename"]);
		if($ressourcename==null){$ressourcename="{unknown}";}
		$ztype=$ligne["ztype"];
		$GetError=intval($ligne["GetError"]);
		if($ztype=="smb"){
			$ligne["tfolder"]=str_replace("/", "\\", $ligne["tfolder"]);
			$resource="\\\\{$ligne["hostname"]}\\{$ligne["sfolder"]}\\{$ligne["tfolder"]}";
			
		}
		
		if($indexed==0){$status="<span class='label'>{not_indexed}</span>";}
		if($indexed==1){$status="<span class='label label-primary'>$filesnumber {indexes}</span>";}
		if($enabled==0){$status="<span class='label'>{disabled}</span>";}
		
		if($indexed==1){
			$ScannedTime=$ligne["ScannedTime"];
			
		}
		if($GetError==1){
			$status="<span class='label label-danger'>{error}</span>";
			$error="<br><small class=text-danger>{$ligne["GetErrorText"]}</small>";
			$ScannedTime=$ligne["ScannedTime"];
		}
		
		
		
		$edit="Loadjs('$page?folder-js=$ID');";
		$href=$tpl->td_href($ressourcename,$ligne["ressourceexplain"],$edit);
		
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1%>$ID</a></td>";
		$html[]="<td width=1% nowrap>$status</td>";
		$html[]="<td><H3>$href</H3>$resource</a>$error</td>";
		$html[]="<td width=1% nowrap  align='right'>$SkippedFiles</a></td>";
		
		$html[]="<td width=1% nowrap>$ScannedTime</a></td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_check($enabled,"Loadjs('$page?enable-js=$ID&id=$md')","AsDnsAdministrator")."</center></td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')","AsDnsAdministrator")."</center></td>";
		$html[]="</tr>";
	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
		NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
		$(document).ready(function() { $('#table-xapian-folders').footable(	{ 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}