<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.templates.manager.inc");

if(isset($_GET["new-file"])){new_file();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["upload"])){upload_popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["showpic-js"])){show_file();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog1("{file_manager}", "$page?main=yes");
}
function new_file(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog6("{new_file}", "$page?upload=yes",450);
	
}
function delete_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$sql="SELECT filename  FROM `templates_files` WHERE ID=".intval($_GET["delete-js"]);
	$ligne = $q->mysqli_fetch_array($sql);
	$js="$('#{$_GET["md"]}').remove();";
	$tpl->js_confirm_delete("{filename} " .$ligne["filename"], "delete", $_GET["delete-js"],$js);
	
}

function delete(){
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$q->QUERY_SQL("DELETE FROM `templates_files` WHERE ID=".intval($_POST["delete"]));
}

function show_file(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl=new template_admin();
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$sql="SELECT *  FROM `templates_files` WHERE ID=".intval($_GET["showpic-js"]);
	$ligne = $q->mysqli_fetch_array($sql);
	$filename=utf8_encode($ligne["filename"]);
	$fdata=base64_decode($ligne["contentfile"]);
	$filesize=FormatBytes($ligne["contentsize"]/1024);
	@file_put_contents(dirname(__FILE__)."/ressources/conf/$filename", $fdata);
	
	$tpl->js_show_pic($filename, $filesize, "/ressources/conf/$filename");
	
}

function main(){
	$page=CurrentPageName();
	echo "<div id='template-manager-file-start'></div><script>LoadAjax('template-manager-file-start','$page?table=yes');</script>";
	
}
function upload_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$bt_upload=$tpl->button_upload("{upload_a_file}",$page)."&nbsp;&nbsp;";
	$html="<center>$bt_upload</center>
	<center><small>css,js,gif,jpeg,jpg,png,woff</small></center>
	<div id='progress-pdns-import'></div>";
	echo $tpl->_ENGINE_parse_body($html);
}
function file_uploaded(){
	$page=CurrentPageName();
	$fileName=$_GET["file-uploaded"];
	$filepath=dirname(__FILE__)."/ressources/conf/upload/{$_GET["file-uploaded"]}";
	
	if(!is_file($filepath)){
		echo "alert('$filepath no such file')";
		return;
	}
	
	$filesize=@filesize($filepath);
	$keyfilename=str_replace(" ", "_", $fileName);
	$keyfilename=str_replace("(", "", $fileName);
	$keyfilename=str_replace(")", "", $fileName);
	$keyfilename=str_replace("\"", "", $fileName);
	$keyfilename=str_replace("'", "", $fileName);
	$type=mime_content_type($filepath);
	
	if(preg_match("#\.css$#", $fileName)){$type="text/css";}
	if(preg_match("#\.woff$#",$fileName)){$type="application/font-woff";}
	
	$content=@file_get_contents($filepath);
	$content=base64_encode($content);
	$content=mysql_escape_string2($content);
	@unlink($filepath);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	
	$sql="INSERT OR IGNORE INTO templates_files(filename,contentfile,contenttype,contentsize) VALUES ('$keyfilename','$content','$type','$filesize')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "alert('$q->mysql_error')";
		return;
	}
	
	echo "LoadAjax('template-manager-file-start','$page?table=yes');";
	
}


function table(){
	
	$tpl=new template_admin();
	$users=new usersMenus();
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$page=CurrentPageName();
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
	if($users->CORP_LICENSE){if($users->AsSquidAdministrator){	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?new-file=yes');\"><i class='fas fa-upload'></i> {new_file} </label>";}}
	$html[]="</div>";
	
	
	$html[]="<table id='table-template-manager-files' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>ID</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{filename}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{token}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$TRCLASS=null;
	$sql="SELECT ID,filename,contenttype,contentsize  FROM `templates_files`";
	$results = $q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$zmd5=md5(serialize($ligne));
		$ID=$ligne["ID"];
		$delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$zmd5')");
		$template_logo="Loadjs('$page?showpic-js=$ID')";
		
		
		$filename=utf8_encode($ligne["filename"]);
		$filesize=FormatBytes($ligne["contentsize"]/1024);
		$contenttype=$ligne["contenttype"];
		$token="[file=$filename]";
		
		$html[]="<tr class='$TRCLASS' id='$zmd5'>";
		$html[]="<td style='width:1%' nowrap>$ID</td>";
		$html[]="<td nowrap>".$tpl->td_href("$filename","{view2}",$template_logo)."</td>";
		$html[]="<td style='width:1%' nowrap>$token</td>";
		$html[]="<td style='width:1%' nowrap>$contenttype</td>";
		$html[]="<td style='width:1%' nowrap>$filesize</td>";
		$html[]="<td style='width:1%' nowrap>$delete</td>";
		$html[]="<td></td>";
		$html[]="</tr>";
		
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='9'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-template-manager-files').footable( {\"filtering\": {\"enabled\": true },\"sorting\": {\"enabled\": true } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}