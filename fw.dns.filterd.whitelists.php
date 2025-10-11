<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-id"])){item_id();exit;}
if(isset($_GET["item-enable"])){item_enable();exit;}
if(isset($_GET["item-delete"])){item_delete();exit;}
if(isset($_POST["ID"])){item_save();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	echo "<div id='dnsfilter-white-div' style='margin-top:10px'></div><script>LoadAjax('dnsfilter-white-div','$page?table=yes');</script>";
}

function item_enable(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["item-enable"]);
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM webfilter_whitelists WHERE ID=$ID");
	if(intval($ligne["enabled"])==1){$enabled=0;}else{$enabled=1;}
	$sql="UPDATE webfilter_whitelists SET enabled=$enabled WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);}
}

function item_delete(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["item-delete"]);
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$q->QUERY_SQL("DELETE FROM webfilter_whitelists WHERE ID=$ID");
	if(!$q->ok){$tpl->js_mysql_alert($q->mysql_error);return;}
	header("content-type: application/x-javascript");
	echo "$('#{$_GET["md"]}').remove();";
	
}

function item_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["item-js"]);
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	if($ID==0){$title="{new_item}";}else{
		$ligne=$q->mysqli_fetch_array("SELECT pattern FROM webfilter_whitelists WHERE ID=$ID");
		$title="{item} {$ligne["pattern"]} - $ID";}
	$tpl->js_dialog1($title, "$page?item-id=$ID");
}

function item_id(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ID=intval($_GET["item-id"]);	
	$title="{new_item}";
	$bt="{add}";
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$jsafter="LoadAjax('dnsfilter-white-div','$page?table=yes');dialogInstance1.close();";
	$ligne=$q->mysqli_fetch_array("SELECT * FROM webfilter_whitelists WHERE ID=$ID");
	if($ID>0){$title="{item}: {$ligne["pattern"]}";$bt="{apply}";}
	$ztype[0]="{client_source_ip_address}";
	$ztype[1]="{destination_domain}";
	
	$form[]=$tpl->field_hidden("ID", $ID);
	$form[]=$tpl->field_array_hash($ztype, "ztype", "{type}", $ligne["type"]);
	$form[]=$tpl->field_text("pattern", "{item}", $ligne["pattern"],true);
	echo $tpl->form_outside($title, $form,"{dnsfilterd_white_explain}",$bt,$jsafter,"AsDnsAdministrator");
	
	
}

function item_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$ID=intval($_POST["ID"]);
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	if($ID==0){
		$sql="INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('{$_POST["pattern"]}','{$_POST["ztype"]}',1)";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
		
	}
	$sql="UPDATE webfilter_whitelists SET `pattern`='{$_POST["pattern"]}',`type`='{$_POST["ztype"]}' WHERE ID=$ID";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
}


function table(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$add="Loadjs('$page?item-js=0');";
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress.log";
	$ARRAY["CMD"]="dnsfilterd.php?compile-rules=yes";
	$ARRAY["TITLE"]="{apply_webiltering_rules}";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-dnsfilter-restart')";
	$t=time();

	$html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"$add\"><i class='fa fa-plus'></i> {new_item} </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_dnsfiltering_rules} </label>
			</div>
			<div class=\"btn-group\" data-toggle=\"buttons\"></div>");
	
	
	
	
	$html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{items}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' width=1%>{enable}</th>";
	$html[]="<th data-sortable=false width=1%>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$sql="SELECT * FROM webfilter_whitelists ORDER BY pattern";
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$results=$q->QUERY_SQL($sql);
	$ztype[0]="{client_source_ip_address}";
	$ztype[1]="{destination_domain}";
	

	
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		$ID=$ligne["ID"];
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$md=md5(serialize($ligne));
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td><strong>".$tpl->td_href($ligne["pattern"],null,"Loadjs('$page?item-js=$ID');")."</strong></td>";
		$html[]="<td>{$ztype[$ligne["type"]]}</td>";
		$html[]="<td width=1%><center>".$tpl->icon_check($ligne["enabled"],"Loadjs('$page?item-enable=$ID')","AsDnsAdministrator")."</td>";
		$html[]="<td width=1%><center>".$tpl->icon_delete("Loadjs('$page?item-delete=$ID&md=$md')","AsDnsAdministrator")."</td>";
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
	
	echo $tpl->_ENGINE_parse_body($html);

}


