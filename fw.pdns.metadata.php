<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["item-js"])){item_js();exit;}
if(isset($_GET["item-id"])){item_id();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["xkind"])){item_save();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){item_delete();exit;}


js();

function js(){
	$page=CurrentPageName();
	$domain_id=intval($_GET["domain_id"]);
	$tpl=new template_admin();
	$q=new mysql_pdns();
	$domainame=$q->GetDomainName($domain_id);
	$tpl->js_dialog6("$domainame: {META_DATA}", "$page?popup=yes&domain_id=$domain_id",900);
	
}
function delete_js(){
	$id=$_GET["delete-js"];
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_confirm_delete($id, "delete", $id,"$('#{$_GET["id"]}').remove();");
}

function item_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=intval($_GET["item-js"]);
	$domain_id=intval($_GET["domain_id"]);
	$q=new mysql_pdns();
	$domainame=$q->GetDomainName($domain_id);
	if($id==0){$title="$domainame: {new_item}";}else{$title="$domainame:{item} $id";}
	$tpl->js_dialog7($title, "$page?item-id=$id&domain_id=$domain_id",500);
}

function item_id(){
	$q=new mysql_pdns();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=intval($_GET["item-id"]);
	$domain_id=intval($_GET["domain_id"]);
	$domainame=$q->GetDomainName($domain_id);
	if($id==0){$title="{new_item}";$bt="{add}";}else{$title="{item} $id";$bt="{apply}";}
	
	$sql="select * from domainmetadata where id='$id'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	
	$form[]=$tpl->field_hidden("id", $id);
	$form[]=$tpl->field_hidden("domain_id", $domain_id);
	$form[]=$tpl->field_text("xkind", "kind", $ligne["kind"]);
	$form[]=$tpl->field_text("content", "{content}", $ligne["content"]);
	echo $tpl->form_outside("$domainame:$title", $form,null,$bt,"LoadAjax('div-dns-meta-data','$page?table=yes&domain_id=$domain_id')","AsDnsAdministrator");
	
}

function item_save(){
	$q=new mysql_pdns();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$id=$_POST["id"];
	$domain_id=$_POST["domain_id"];
	$kind=$_POST["xkind"];
	$content=$_POST["content"];
	if($id==0){
		$q->QUERY_SQL("insert into domainmetadata(domain_id, kind, content) 
				values($domain_id, '$kind','$content');");
	}else{
		$q->QUERY_SQL("update domainmetadata SET kind='$kind',content='$content' WHERE id=$id");
	}
	if(!$q->ok){echo $q->mysql_error;}
	
}


function item_delete(){
	$q=new mysql_pdns();
	$id=intval($_POST["delete"]);
	$q->QUERY_SQL("DELETE FROM domainmetadata WHERE id='$id'");
	if(!$q->ok){echo $q->mysql_error;}
}

function popup(){
	$domain_id=intval($_GET["domain_id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();	
	$html="<div id='div-dns-meta-data'></div><script> LoadAjax('div-dns-meta-data','$page?table=yes&domain_id=$domain_id');</script>";
	echo $html;
}

function table(){
	$domain_id=intval($_GET["domain_id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
	if($PowerDNSEnableClusterSlave==0){
		$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?item-js=0&domain_id=$domain_id');\"><i class='fa fa-plus'></i> {new_item} </label>";
	}
	$html[]="</div>";
	
	
	$html[]="<table id='table-dns-meta-data' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{key}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{META_DATA}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
//
	$sql="SELECT * FROM domainmetadata WHERE domain_id=$domain_id";
	$q=new mysql_pdns();
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error_html());}
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$kind=$ligne["kind"];
		$content=$ligne["content"];
		$id=$ligne["id"];
		$kind=$tpl->td_href($kind,"{click_to_edit}","Loadjs('$page?item-js=$id&domain_id=$domain_id');");
		$md=md5(serialize($ligne));
		$delete_icon=$tpl->icon_nothing();
		if($PowerDNSEnableClusterSlave==0){$delete_icon=$tpl->icon_delete("Loadjs('$page?delete-js=$id&id=$md')","AsDnsAdministrator");}
		
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td width=1%>$id</td>";
		$html[]="<td width=1% nowrap><strong>$kind</strong></td>";
		$html[]="<td nowrap>$content</td>";
		$html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>$delete_icon</center></td>";
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
	$(document).ready(function() { $('#table-dns-meta-data').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
</script>";	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}