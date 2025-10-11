<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["file-uploaded"])){file_uploaded_js();exit;}
if(isset($_GET["upload-progress-popup"])){upload_progress_popup();exit;}
if(isset($_GET["import-results"])){import_results();exit;}
page();


function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();


    $html[]="<div class='center' style='margin: 50px'>".$tpl->button_upload("{upload_3x_container}",$page)."</div>";
    $html[]="<div id='import-results' style='margin: 30px'></div>";

    $TINY_ARRAY["TITLE"]="{import_settings_from} 3.x";
    $TINY_ARRAY["ICO"]="fad fa-file-import";
    $TINY_ARRAY["EXPL"]="{import_v4_explain}";
    $TINY_ARRAY["URL"]="";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>$jstiny</script>";

    echo $tpl->_ENGINE_parse_body($html);

}

function file_uploaded_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["file-uploaded"];
    $fileencode=urlencode($filename);
    $tpl->js_dialog6("{upload_3x_container} {progress}", "$page?upload-progress-popup=$fileencode",650);
}

function upload_progress_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $filename=$_GET["upload-progress-popup"];
    $filenameenc=urlencode($filename);
    $t=time();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/import3x.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/import3x.txt";
    $ARRAY["CMD"]="artica.php?import3x=$filenameenc";
    $ARRAY["TITLE"]="{importing}";
    $ARRAY["AFTER"]="LoadAjax('import-results','$page?import-results=yes');if(typeof dialogInstance6 == 'object'){ dialogInstance6.close();}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-$t-upload')";
    echo "<div class='row'><div id='progress-$t-upload'></div><script>$jsrestart</script>";
}

function import_results(){

    $f=explode("\n",@file_get_contents(PROGRESS_DIR."/import3x.txt"));

    foreach ($f as $line){
        if($line==null){continue;}
        $line=htmlentities($line);
        echo "<div>$line</div>";

    }


}