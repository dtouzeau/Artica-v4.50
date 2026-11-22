<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.templates.manager.inc");
// Pentest 2026-11-04 E-06 : la page ecrit des modeles du proxy.
$users=new usersMenus(); if(!$users->AsSquidAdministrator){exit();}

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


function templates_upload_has_link($dir):bool{
	$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
	foreach($it as $f){if($f->isLink()){return true;}}
	return false;
}
function file_uploaded(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	// Pentest 2026-11-04 E-05 : le nom arrivait avec ses ../ et l'archive
	// etait extraite sans garde-fou. Nom reduit a basename(), chemin verifie
	// sous le dossier d'upload, seuls les membres attendus sont extraits.
	$fileName=basename(strval($_GET["file-uploaded"]));
	$uploadDir=realpath(dirname(__FILE__)."/ressources/conf/upload");
	$filepath=$uploadDir."/".$fileName;
	$real=realpath($filepath);
	if($uploadDir===false || $fileName=="" || $real===false || dirname($real)!==$uploadDir || is_link($filepath) || !is_file($real)){
		echo "alert(".json_encode("$fileName: no such file").")";
		return;
	}
	
	$t=time();
	$Dirname=PROGRESS_DIR."/$t";
	@mkdir($Dirname,0755,true);
	// GNU tar retire deja le / de tete et refuse les membres contenant "..".
	$cmd="/bin/tar --no-same-owner --no-same-permissions -xf ".escapeshellarg($real)." -C ".escapeshellarg($Dirname);
	foreach(array("tpl.name","css.css","head.html","body.html","files") as $m){$cmd.=" ".escapeshellarg($m);}
	exec("$cmd 2>&1");
	@unlink($real);
	// Un lien symbolique dans l'archive ferait lire un fichier de l'appliance.
	if(templates_upload_has_link($Dirname)){
		exec("/bin/rm -rf ".escapeshellarg($Dirname));
		echo "alert(".json_encode("Invalid template archive").")";
		return;
	}
	
	// Le modele et ses fichiers partent en UN appel (POST /proxy/templates-manager/import,
	// une transaction) : l'ancien code inserait le modele puis s'arretait au premier fichier
	// deja present, laissant un modele a moitie importe. Contenus en base64 (forme stockee).
	$import=array(
		"templateName"=>templates_manager_utf8(trim(strval(@file_get_contents("$Dirname/tpl.name")))),
		"cssContent"=>base64_encode(strval(@file_get_contents("$Dirname/css.css"))),
		"headContent"=>base64_encode(strval(@file_get_contents("$Dirname/head.html"))),
		"bodyContent"=>base64_encode(strval(@file_get_contents("$Dirname/body.html"))),
		"files"=>array(),
	);
		
	$BaseWorkDir="$Dirname/files";
	$handle=is_dir($BaseWorkDir) ? opendir($BaseWorkDir) : false;
		
	while ($handle && false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		writelogs("$BaseWorkDir -> $filename...",__FUNCTION__,__FILE__,__LINE__);
		$targetFile="$BaseWorkDir/$filename";
		if(!preg_match("#\.type$#", $filename)){continue;}
		$keyfilename=str_replace(".type", "", $filename);
		$SourceFile=$BaseWorkDir."/$keyfilename";
		$filesize=@filesize($SourceFile);
		writelogs("$SourceFile {$filesize}Bytes..",__FUNCTION__,__FILE__,__LINE__);
		if(!is_file($SourceFile)){continue;}
		// Nom et type viennent de l'archive : le daemon les valide.
		$import["files"][]=array(
			"filename"=>templates_manager_utf8($keyfilename),
			"contentType"=>trim(strval(@file_get_contents($targetFile))),
			"contentFile"=>base64_encode(strval(@file_get_contents($SourceFile))),
		);
	}
	if($handle){closedir($handle);}
	exec("/bin/rm -rf ".escapeshellarg($Dirname));

	list($ok,$data)=templates_manager_api("POST","/proxy/templates-manager/import",$import);
	if(!$ok){
		header("content-type: application/x-javascript");
		echo "alert(".json_encode(strval($data)).");";
		return;
	}
	gotofinish();
	
	
	
}


function gotofinish($nohead=false){
	if(!$nohead){header("content-type: application/x-javascript");}
	echo "\ndialogInstance1.close();\n";
	echo "LoadAjax('proxy-templates-manager','fw.proxy.templates.manager.php?table=yes');";
}