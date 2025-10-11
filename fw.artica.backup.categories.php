<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_POST["none"])){die();}
if(isset($_GET["download"])){download();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_GET["restore-js"])){restore_js();exit;}
table_start();

function table_start(){$page=CurrentPageName();echo "

<div id='table-categories-list' style='margin-top:10px'></div><script>LoadAjax('table-categories-list','fw.artica.backup.categories.php?table=yes');</script>";}


function delete_js(){
    $md=$_GET["md"];
    $ID=$_GET["delete-js"];
    $sock=new sockets();
    $sock->getFrameWork("ufdbguard.php?backup-delete=$ID");
    echo "$('#$md').remove();\n";

}

function restore_js(){
    $ID=$_GET["restore-js"];

    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/categoriesbackup.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM backup WHERE ID=$ID");
    $sourcepath=basename($ligne["sourcepath"]);

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/backup_categories.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/backup_categories.log";
    $ARRAY["CMD"]="ufdbguard.php?restore-id=$ID";
    $ARRAY["TITLE"]="{restore_backup} $sourcepath";
    $ARRAY["AFTER"]="LoadAjax('table-categories-list','fw.artica.backup.categories.php?table=yes')";
    $prgress=base64_encode(serialize($ARRAY));
    $js="Loadjs('fw.progress.php?content=$prgress&mainid=restore-task-$ID')\n";



    $tpl->js_confirm_execute("{restore} $sourcepath ?","none","none",$js);




}
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $TRCLASS=null;
    $t=time();


    $jsbackup=$tpl->framework_buildjs("/categories/backup",
    "backup_categories.progress","backup_categories.log","progress-snapshot-restart","LoadAjax('table-categories-list','$page?table=yes');");

    $restore="Loadjs('fw.ufdb.categories.restore.php')";

    $btns=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\" style=''>
			<label class=\"btn btn btn-primary\" OnClick=\"$jsbackup\"><i class='fa fa-plus'></i> {backup} </label>
			<label class=\"btn btn btn-info\" OnClick=\"$restore\"><i class='fa fa-download'></i> {restore_backup} </label>
			</div>");


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{filename}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{restore}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q=new lib_sqlite("/home/artica/SQLITE/categoriesbackup.db");
    $sql="CREATE TABLE IF NOT EXISTS `backup` (
		 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		 `sourcepath` TEXT UNIQUE,
		`created` INTEGER NOT NULL DEFAULT 0,
		`filesize` INTEGER NOT NULL DEFAULT 0,
		`events` TEXT )";
    $q->QUERY_SQL($sql);



    $results=$q->QUERY_SQL("SELECT * FROM backup ORDER BY ID DESC");
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);return;}
    foreach ($results as $ligne){
        $ID=$ligne["ID"];
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $filename=$ligne["sourcepath"];
        $time=$tpl->time_to_date($ligne["created"],true);
        $size=FormatBytes($ligne["filesize"]/1024);
        $filename_enc=urlencode($filename);

        $filename=$tpl->td_href($filename,"{download}","s_PopUp('$page?download=$ID',550,250);");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width=1% nowrap>$time</td>";
        $html[]="<td><div id='restore-task-$ID'>$filename</div></td>";
        $html[]="<td width=1% nowrap>$size</td>";
        $html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>".$tpl->icon_run("Loadjs('$page?restore-js=$ID&md=$md')","AsDnsAdministrator")."</center></td>";
        $html[]="<td style='vertical-align:middle' width=1% class='center' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-js=$ID&md=$md')","AsDnsAdministrator")."</center></td>";
        $html[]="</tr>";



    }


    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='5'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $TINY_ARRAY["TITLE"]="{backup_categories}";
    $TINY_ARRAY["ICO"]="fa fa-archive";
    $TINY_ARRAY["EXPL"]="{backup_categories_explain}";
    $TINY_ARRAY["URL"]="snapshots";
    $TINY_ARRAY["BUTTONS"]=$btns;

    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
		<script>
			NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
			$(document).ready(function() { $('#table-$t').footable( { 	\"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
			$jstiny
		</script>";


    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));


}

function download(){
    $ID=$_GET["download"];
    $q=new lib_sqlite("/home/artica/SQLITE/categoriesbackup.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM backup WHERE ID=$ID");
    $sourcepath=$ligne["sourcepath"];
    $filename=basename($sourcepath);
    $fsize=$ligne["filesize"];

    if(!$GLOBALS["VERBOSE"]){
        header('Content-type: application/x-tar');
        header('Content-Transfer-Encoding: binary');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
        header("Content-Length: ".$fsize);
        ob_clean();
        flush();
    }
    if(is_file($sourcepath)){
        readfile($sourcepath);
    }
}