<?php
// stamp 01/11/2026
// Service Pack 9 2026-10-10
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
    if(!fw_progress_page_priv("AsSystemAdministrator")){return false;}   // task link signed for this session: built only for whom may run it
    $tpl=new template_admin();
    $page=CurrentPageName();
    // A table name and a database name (squid_admin_mysql / system_events…): both become
    // segments of the articarest route and the names of the progress and export files.
    $table=(string)($_GET["table"] ?? "");
    $file=(string)($_GET["file"] ?? "");
    if(!preg_match('/^[A-Za-z0-9_]{1,64}$/',$table) || !preg_match('/^[A-Za-z0-9_.-]{1,64}$/',$file) || strpos($file,"..")!==false){return fw_progress_bad_value("table/file");}
    $id=md5("$table$file");
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/$file.$table.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/$file.$table.log";
    $ARRAY["CMD"]="/system/sqlite/export/$file/$table";
    $ARRAY["TITLE"]="{exporting}";
    $ARRAY["AFTER"]="LoadAjax('$id-download','$page?after=yes&table=$table&file=$file');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?".fw_progress_args_b64($prgress)."&mainid=$id')";

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










