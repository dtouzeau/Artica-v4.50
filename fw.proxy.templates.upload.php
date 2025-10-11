<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.templates.manager.inc");

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
js();


function js(){
	$tpl=new template_admin();
	$users=new usersMenus();
	$page=CurrentPageName();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$tpl->js_no_license();return;}
	$tpl->js_dialog1("{upload_template}", "$page?popup=yes");
}

function popup(){
	$tpl=new template_admin();
	$users=new usersMenus();
	$page=CurrentPageName();
	$html="<div class='alert alert-success'>{upload_template_explain}</div>
			
		<center style='margin:50px'>".$tpl->button_upload("{upload_template}",$page)."</center>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function file_uploaded(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$fileName=$_GET["file-uploaded"];
	$filepath=dirname(__FILE__)."/ressources/conf/upload/{$_GET["file-uploaded"]}";

	if(!is_file($filepath)){
		echo "alert('$filepath no such file')";
		return;
	}
	
	$prefix="INSERT INTO templates_manager (TemplateName,CssContent,headContent,BodyContent)";
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$t=time();
	$Dirname=PROGRESS_DIR."/$t";
	@mkdir($Dirname,0755,true);
	shell_exec("/bin/tar -xhf $filepath -C $Dirname/");
	@unlink($filepath);
	
	$TemplateName=sqlite_escape_string2(@file_get_contents("$Dirname/tpl.name"));
	$CssContent=sqlite_escape_string2(base64_encode(@file_get_contents("$Dirname/css.css")));
	$headContent=sqlite_escape_string2(base64_encode(@file_get_contents("$Dirname/head.html")));
	$BodyContent=sqlite_escape_string2(base64_encode(@file_get_contents("$Dirname/body.html")));
	
	$q->QUERY_SQL($prefix." VALUES ('$TemplateName','$CssContent','$headContent','$BodyContent')");
	if(!$q->ok){
		$tpl->js_mysql_alert($q->mysql_error);
		shell_exec("/bin/rm -rf $Dirname");
		return;
	}
		
	$BaseWorkDir="$Dirname/files";
	
	if(!is_dir($BaseWorkDir)){gotofinish();return;}
	if (!$handle = opendir($BaseWorkDir)) {gotofinish();return;}
		
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		writelogs("$BaseWorkDir -> $filename...",__FUNCTION__,__FILE__,__LINE__);
		$targetFile="$BaseWorkDir/$filename";
		if(!preg_match("#\.type$#", $filename)){continue;}
		$keyfilename=str_replace(".type", "", $filename);
		$SourceFile=$BaseWorkDir."/$keyfilename";
		$filesize=@filesize($SourceFile);
		writelogs("$SourceFile {$filesize}Bytes..",__FUNCTION__,__FILE__,__LINE__);
		$type=@file_get_contents($targetFile);
		$content=@file_get_contents($SourceFile);
		$content=base64_encode($content);
		$content=sqlite_escape_string2($content);
	
		$sql="INSERT INTO templates_files(filename,contentfile,contenttype,contentsize) VALUES ('$keyfilename','$content','$type','$filesize')";
		$q->QUERY_SQL($sql);
	
		if(!$q->ok){
			$tpl->js_mysql_alert($q->mysql_error);
			shell_exec("/bin/rm -rf $Dirname");
			gotofinish(true);
			return;
		}
	}
	
	shell_exec("/bin/rm -rf $Dirname");	
	gotofinish();
	
	
	
}


function gotofinish($nohead=false){
	if(!$nohead){header("content-type: application/x-javascript");}
	echo "\ndialogInstance1.close();\n";
	echo "LoadAjax('proxy-templates-manager','fw.proxy.templates.manager.php?table=yes');";
}