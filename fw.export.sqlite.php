<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_POST["none"])){exit;}

if(isset($_GET["js-confirm"])){popup_js();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["after"])){after();exit;}

js();


function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $table=$_GET["table"];
    $file=$_GET["file"];

    if($table==null){
        $tpl->js_mysql_alert("No specified table");
        return;
    }
    if($file==null){
        $tpl->js_mysql_alert("No specified database");
        return;
    }
    $tpl->js_dialog_confirm_action("{export} $file/$table ?","none","$file/$table","Loadjs('$page?js-confirm=yes&table=$table&file=$file')");
}

function popup_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $table=$_GET["table"];
    $file=$_GET["file"];
    $tpl->js_dialog1("{export} $file/$table","$page?popup=yes&table=$table&file=$file",650);
}
function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $table=$_GET["table"];
    $file=$_GET["file"];
    $id=md5("$table$file");
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/$file.$table.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/$file.$table.log";
    $ARRAY["CMD"]="sqlite.php?export=yes&table=$table&file=$file";
    $ARRAY["TITLE"]="{exporting}";
    $ARRAY["AFTER"]="LoadAjax('$id-download','$page?after=yes&table=$table&file=$file');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=$id')";

    $html[]="<div id='$id'></div>";
    $html[]="<div id='$id-download'></div>";
    $html[]="<script>$jsrestart;</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function after(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $table=$_GET["table"];
    $file=$_GET["file"];
    $destination="ressources/logs/web/$file.$table.gz";
    $filesize=FormatBytes(@filesize($destination)/1024);
    $html[]="<a href='$destination'>
                <div class=\"widget style1 navy-bg\">
                    <div class=\"row\">
                        <div class=\"col-xs-4\">
                            <i class=\"fas fa-file-archive fa-5x\"></i>
                        </div>
                        <div class=\"col-xs-8 text-right\">
                            <span> {download} </span>
                            <h2 class=\"font-bold\" style='font-size:18px'>$file.$table.gz <small style='color:white'>($filesize)</small></h2>
                        </div>
                    </div>
                </div></a>
            ";

    echo $tpl->_ENGINE_parse_body($html);
}










