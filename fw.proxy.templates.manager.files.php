<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.templates.manager.inc");

// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
// La page ajoute et supprime des fichiers des pages d'erreur du proxy.
$users=new usersMenus(); if(!$users->AsSquidAdministrator){exit();}
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
	$ID=intval($_GET["delete-js"]);
	list($found,$ligne)=templates_manager_api("GET","/proxy/templates-files/$ID");
	$filename=($found && is_array($ligne)) ? htmlspecialchars(strval($ligne["filename"] ?? "")) : "#$ID";
	$md=preg_replace('#[^a-f0-9]#',"",strval($_GET["md"]));
	$js="$('#$md').remove();";
	$tpl->js_confirm_delete("{filename} $filename", "delete", $ID,$js);
	
}

function delete(){
	list($ok,$data)=templates_manager_api("DELETE","/proxy/templates-files/".intval($_POST["delete"]));
	if(!$ok){echo strval($data);}
}

function show_file(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	list($found,$ligne)=templates_manager_api("GET","/proxy/templates-files/".intval($_GET["showpic-js"]));
	if(!$found || !is_array($ligne)){echo "alert(".json_encode(strval($ligne)).");";return;}
	// Le nom stocke devient un nom de fichier sous la racine web : jamais de chemin.
	$filename=basename(strval($ligne["filename"] ?? ""));
	if($filename=="" || $filename=="." || $filename==".." || $filename[0]=="."){return;}
	$fdata=base64_decode(strval($ligne["contentFile"] ?? ""));
	$filesize=FormatBytes(intval($ligne["contentSize"] ?? 0)/1024);
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
	// Le nom arrivait avec ses ../ : reduit a basename() et verifie sous le dossier d'upload.
	$fileName=basename(strval($_GET["file-uploaded"]));
	$uploadDir=realpath(dirname(__FILE__)."/ressources/conf/upload");
	$filepath=$uploadDir."/".$fileName;
	$real=realpath($filepath);
	if($uploadDir===false || $fileName=="" || $real===false || dirname($real)!==$uploadDir || is_link($filepath) || !is_file($real)){
		echo "alert(".json_encode("$fileName: no such file").");";
		return;
	}
	
	$type=strval(@mime_content_type($real));
	$content=base64_encode(strval(@file_get_contents($real)));
	@unlink($real);
	// Le daemon nettoie le nom (espaces, guillemets, parentheses), impose le type des .css/.woff
	// et refuse un nom deja present (l'ancien INSERT OR IGNORE l'ignorait sans rien dire).
	list($ok,$data)=templates_manager_api("POST","/proxy/templates-files",array(
		"filename"=>templates_manager_utf8($fileName),"contentType"=>$type,"contentFile"=>$content));
	if(!$ok){
		echo "alert(".json_encode(strval($data)).");";
		return;
	}
	
	echo "LoadAjax('template-manager-file-start','$page?table=yes');";
	
}


function table(){
	
	$tpl=new template_admin();
	$users=new usersMenus();
	$page=CurrentPageName();
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
	if($users->CORP_LICENSE){if($users->AsSquidAdministrator){	$html[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?new-file=yes');\"><i class='fas fa-upload'></i> {new_file} </label>";}}
	$html[]="</div>";
	
	
	$html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>"
	    ."<table class='awc-tbl'>";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th>ID</th>";
	$html[]="<th class='awc-nowrap'>{filename}</th>";
	$html[]="<th class='awc-nowrap'>{token}</th>";
	$html[]="<th>{type}</th>";
	$html[]="<th>{size}</th>";
	$html[]="<th>{delete}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody id='templatesman-rows'>";
	
	$TRCLASS=null;
	list($listed,$results)=templates_manager_api("GET","/proxy/templates-files");
	if(!$listed){
		$html[]="<tr><td colspan='6'>".$tpl->div_error(strval($results))."</td></tr>";
		$results=array();
	}
	foreach ((is_array($results)?$results:array()) as $ligne){
		$TRCLASS=null;
		$zmd5=md5(serialize($ligne));
		$ID=intval($ligne["id"] ?? 0);
		$delete=$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$zmd5')");
		$template_logo="Loadjs('$page?showpic-js=$ID')";
		
		
		$filename=htmlspecialchars(strval($ligne["filename"] ?? ""));
		$filesize=FormatBytes(intval($ligne["contentSize"] ?? 0)/1024);
		$contenttype=htmlspecialchars(strval($ligne["contentType"] ?? ""));
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
	
	$html[]="</tbody></table></div>";
	$html[]="<div class='awc-foot'><span>&nbsp;</span>"
	       ."<span class='awc-pg' id='templatesman-pager'></span></div>";
	$html[]="</div></div>";
	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	</script>";
	
	echo awc_table_style()
	    .$tpl->_ENGINE_parse_body($html)
	    .awc_pager_js($GLOBALS["FOOTABLE_PSIZE"],"templatesman-rows","templatesman-pager");
	
	
}