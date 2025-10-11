<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded_js();exit;}
if(isset($_GET["upload-progress-popup"])){upload_progress_popup();exit;}

js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_dialog6("{import_objects_from} 3.x","$page?popup=yes&func={$_GET["func"]}",650);

}

function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<p>{import_acls_objects_v4_explain}</p>";
    $html[]="<div class=center>".$tpl->button_upload("{upload_snapshot}",$page,null,"&func={$_GET["func"]}")."</div>";
    echo $tpl->_ENGINE_parse_body($html);

}

function file_uploaded_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];
    $fileencode=urlencode($filename);
    $tpl->js_dialog6("{progress}", "$page?upload-progress-popup=$fileencode&func={$_GET["func"]}",650);
}

function upload_progress_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["upload-progress-popup"];
    $filenameenc=urlencode($filename);
    $func=base64_encode($_GET["func"]);
    $t=time();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/import3x.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/import3x.txt";
    $ARRAY["CMD"]="artica.php?import-acls-objects3x=$filenameenc";
    $ARRAY["TITLE"]="{importing}";
    $ARRAY["AFTER"]="$func;if(typeof dialogInstance6 == 'object'){ dialogInstance6.close();}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-$t-upload')";
    echo "<div class='row'><div id='progress-$t-upload'></div><script>$jsrestart</script>";
}