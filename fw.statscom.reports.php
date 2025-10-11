<?php

$GLOBALS["TASKS_TYPE"][0]="{APP_PROXY}: {yesterday_report}";
$GLOBALS["TASKS_TYPE"][1]="{APP_PROXY}: {daily_report}";
$GLOBALS["TASKS_TYPE"][2]="{APP_PROXY}: {not_categorized}";
$GLOBALS["TASKS_TYPE"][3]="{APP_PROXY}: {last_weekly_report}";
$GLOBALS["TASKS_TYPE"][4]="{APP_PROXY}: {current_weekly_report}";
$GLOBALS["TASKS_TYPE"][5]="{APP_PROXY}: {last_monthly_report}";
$GLOBALS["TASKS_TYPE"][6]="{APP_PROXY}: {current_monthly_report}";
$GLOBALS["TASKS_TYPE"][7]="{APP_PROXY}: {synchronize_data}";



include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.tasks.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["smtp_auth_enabled"])){smtp_auth_save();exit;}
if(isset($_GET["smtp-auth-js"])){smtp_auth_js();exit;}
if(isset($_GET["smtp-auth-popup"])){smtp_auth_popup();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_GET["AddNewSchedule-js"])){schedule_js();exit;}
if(isset($_GET["AddNewSchedule-popup"])){schedule_popup();exit;}
if(isset($_GET["enable-task-js"])){schedule_enable();exit;}
if(isset($_GET["delete-task-js"])){schedule_delete_js();exit;}
if(isset($_GET["run-task-js"])){schedule_run_task_js();exit;}
if(isset($_POST["ID"])){schedule_save();exit;}
if(isset($_POST["remove"])){schedule_remove();exit;}
if(isset($_POST["execute"])){schedule_execute();exit;}
if(isset($_GET["sub-main"])){sub_page();exit;}
if(isset($_GET["microstart"])){microstart();exit;}

page();


function microstart(){
    $page=CurrentPageName();
    echo "	<div class='row'>
		<div id='progress-firehol-restart'></div>
		<div class='ibox-content'>
			<div id='table-pdf-reports-task-loader'></div>
		</div>
	</div>	<script>
	LoadAjax('table-pdf-reports-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');

	</script>";

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $explain=null;


    $html="
    <div class='row'>
		<div id='progress-firehol-restart'></div>
		<div class='ibox-content'>
			<div id='table-pdf-reports-task-loader'></div>
		</div>
	</div>



	<script>
	LoadAjax('table-pdf-reports-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');

	</script>";

    echo $tpl->_ENGINE_parse_body($html);

}

function sub_page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    if(intval($_GET["ForceTaskType"])>0){
        $tasks=new system_tasks();
        $taskz=$tpl->_ENGINE_parse_body($tasks->tasks_array[$_GET["ForceTaskType"] ] );
        $task_explain=$tpl->_ENGINE_parse_body($tasks->tasks_explain_array[$_GET["ForceTaskType"] ] );
    }

    echo "<div class='row' style='margin-top:10px'>
		<h2>$taskz</h2><p>$task_explain</p>
		<div id='progress-firehol-restart'></div>
		<div class='ibox-content'>
			<div id='table-pdf-reports-task-loader'></div>
		</div>
	</div>



	<script>
	LoadAjax('table-pdf-reports-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');

	</script>";

}

function schedule_enable(){
    header("content-type: application/x-javascript");
    $ID=$_GET["enable-task-js"];
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM pdf_reports WHERE ID=$ID");
    $enabled=$ligne["enabled"];
    if($enabled==0){$enabled=1;}else{$enabled=0;}
    $q->QUERY_SQL("UPDATE pdf_reports SET `enabled`='$enabled' WHERE ID='$ID'");
    if(!$q->ok){
        echo "alert('".$q->mysql_error."')";
    }

}
function schedule_run_task_js(){
    $ID=$_GET["run-task-js"];
    $tasks=new system_tasks();
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $tpl=new template_admin();
    $ligne=$q->mysqli_fetch_array("SELECT TaskType FROM pdf_reports WHERE ID=$ID");
    $taskz=$tpl->javascript_parse_text($tasks->tasks_array[ $ligne["TaskType"] ] );
    $title="{schedule}::$ID::{$ligne["TaskType"]} ({$taskz})";
    $tpl->js_confirm_execute($title,"execute",$ID);
}

function schedule_execute(){

    $sock=new sockets();
    $sock->getFrameWork("services.php?run-pdf-task={$_POST["execute"]}");

}


function schedule_delete_js(){
    $ID=$_GET["delete-task-js"];
    $tasks=new system_tasks();
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ligne=$q->mysqli_fetch_array("SELECT TaskType FROM pdf_reports WHERE ID=$ID");
    $taskz=$tpl->javascript_parse_text($tasks->tasks_array[ $ligne["TaskType"] ] );
    $title="{schedule}::$ID::{$ligne["TaskType"]} ({$taskz})";
    $tpl->js_confirm_delete($title,"remove",$ID,"LoadAjax('table-pdf-reports-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');");
}
function schedule_remove(){
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $ID=$_POST["remove"];
    $ligne=$q->mysqli_fetch_array("SELECT * FROM pdf_reports WHERE ID=$ID");
    admin_tracks("Deleting PDF report schedule {$ID}: {$ligne["subject"]}");
    $q->QUERY_SQL("DELETE FROM pdf_reports WHERE ID=$ID");
}

function schedule_js(){
    $tasks=new system_tasks();
    header("content-type: application/x-javascript");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title=$tpl->javascript_parse_text("{new_schedule}");
    $id=intval($_GET["ID"]);
    if($id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
        $ligne=$q->mysqli_fetch_array("SELECT TaskType FROM pdf_reports WHERE ID=$id");
        $taskz=$tpl->javascript_parse_text($tasks->tasks_array[ $ligne["TaskType"] ] );
        $title="{schedule}::$id::{$ligne["TaskType"]} ({$taskz})";

    }

    $tpl->js_dialog1($title, "$page?AddNewSchedule-popup=yes&ID=$id&ForceTaskType={$_GET["ForceTaskType"]}&ForceType={$_GET["ForceTaskType"]}");
}
function schedule_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $no_schedule_set=null;
    $buttontext="{add}";
    if(isset($_GET["ForceTaskType"])){
        if(intval($_GET["ForceTaskType"])>0){$_GET["ForceType"]=$_GET["ForceTaskType"];}
    }
    if(!isset($_GET["ForceType"])){$_GET["ForceType"]=0;}
    if(!is_numeric($_GET["ForceType"])){$_GET["ForceType"]=0;}
    $ID=$_GET["ID"];
    $titleplus=null;
    $jsafter="dialogInstance1.close();LoadAjax('table-pdf-reports-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');";
    if($ID>0){
        $buttontext="{apply}";
        $ligne=$q->mysqli_fetch_array("SELECT * FROM pdf_reports WHERE ID=$ID");
        $ligne["TimeDescription"]=utf8_encode($ligne["TimeDescription"]);
        $titleplus="ID:$ID, {type} {$ligne["TaskType"]} &laquo;{$ligne["TimeText"]}&raquo;";
        $jsafter="LoadAjax('table-pdf-reports-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');";
        $no_schedule_set="<div class='alert alert-danger'>".$tpl->javascript_parse_text("{no_schedule_set}")."</div>";
    }

    if(!is_numeric($ligne["TaskType"])){$ligne["TaskType"]=0;}
    if(!is_numeric($ID)){$ID=0;}

    $task_type=$GLOBALS["TASKS_TYPE"];

    foreach ($task_type  as $TaskType=>$content){
        $taskz[$TaskType]="[{$TaskType}] ".$tpl->_ENGINE_parse_body($content);

    }
    if($_GET["ForceType"]>0){
        $ligne["TaskType"]=$_GET["ForceType"];
        unset($taskz);
        $taskz[$_GET["ForceType"]]=$tpl->_ENGINE_parse_body($task_type[$_GET["ForceType"]]);
        $titleplus=": <strong>{$GLOBALS["TASKS_TYPE"][$_GET["ForceType"]]}</strong>";
    }

    if($ligne["TimeText"]<>null){$no_schedule_set=null;}


    $html[]=$no_schedule_set;
    $form[]=$tpl->field_hidden("ID", $ID);
    if($_GET["ForceType"]==0){
        $form[]=$tpl->field_array_hash($taskz, "TaskType", "{task_type}", $ligne["TaskType"],true);
    }else{
        $form[]=$tpl->field_hidden("TaskType", $_GET["ForceType"]);
        if($ligne["TimeDescription"]==null){$ligne["TimeDescription"]="Task:{$_GET["ForceType"]} ".$tpl->_ENGINE_parse_body($task_type[$_GET["ForceType"]])." {time}:".date("Y-m-d H:i");}
    }
    $form[]=$tpl->field_text("TimeDescription", "{description}", $ligne["TimeDescription"]);
    $form[]=$tpl->field_schedule("TimeText", "{schedule}", $ligne["TimeText"],true);
    $form[]=$tpl->field_text("recipients","{recipients}",$ligne["recipients"]);
    $form[]=$tpl->field_text("subject","{subject}",$ligne["subject"]);

    $html[]=$tpl->form_outside("{set_schedule} $titleplus", @implode("\n", $form),
        $GLOBALS["TASKS_TYPE"][ $ligne["TaskType"]],$buttontext,$jsafter,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function schedule_save(){
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $tpl=new template_admin();
    $task_type=$GLOBALS["TASKS_TYPE"];
    $tpl->CLEAN_POST();
    $_POST["subject"]=str_replace("'","`",$_POST["subject"]);
    $_POST["TimeDescription"]=str_replace("'","`",$_POST["TimeDescription"]);

    $info=$tpl->javascript_parse_text($task_type[$_POST["TaskType"]]);
    $defaultdesc=replace_accents($info);
    if($_POST["TimeDescription"]==null){$_POST["TimeDescription"]=$defaultdesc ." : {$_POST["TimeText"]}";}

    $_POST["TimeDescription"]=mysql_escape_string2($_POST["TimeDescription"]);
    $subject=$_POST["subject"];
    $recipients=$_POST["recipients"];


    $sql="INSERT INTO pdf_reports (TimeDescription,TimeText,TaskType,enabled,recipients,subject)
	VALUES('{$_POST["TimeDescription"]}','{$_POST["TimeText"]}','{$_POST["TaskType"]}',1,'$recipients','$subject')";

    if($_POST["ID"]>0){
        $sql="UPDATE pdf_reports SET
	TimeDescription='{$_POST["TimeDescription"]}',
			TimeText='{$_POST["TimeText"]}',
			TaskType='{$_POST["TaskType"]}',
			recipients='$recipients',
			subject='$subject'
			WHERE ID={$_POST["ID"]}
				";

    }


    if(!$q->TABLE_EXISTS("pdf_reports")){
        $sql="CREATE TABLE IF NOT EXISTS `pdf_reports` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`TimeText` VARCHAR( 128 ),
		`TimeDescription` VARCHAR( 128 ),
		`TaskType` INTEGER,
		`recipients` TEXT,
		`subject` TEXT,
		`enabled` INTEGER )";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}
    }
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo "jserror:".$tpl->javascript_parse_text($q->mysql_error);return false;}

    admin_tracks("Saving PDF report schedule {$_POST["ID"]}: {$_POST["subject"]}");

    return true;

}


function main(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $table="pdf_reports";
    $description=$tpl->_ENGINE_parse_body("{description}");
    $task=$tpl->_ENGINE_parse_body("{task}");
    $run=$tpl->_ENGINE_parse_body("{run}");
    $build_config=$tpl->_ENGINE_parse_body("{apply_all_schedules}");
    $enabled=$tpl->_ENGINE_parse_body("{enabled}");
    $ForceTaskType=$_GET["ForceTaskType"];
    $FORCE_FILTER=null;
    $new_entry=$tpl->_ENGINE_parse_body("{new_task}");
    $t=time();


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/statscom.report.progres";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/statscom.report.log";
    $ARRAY["CMD"]="statscom.php?schedules=yes";
    $ARRAY["TITLE"]="{apply_all_schedules}";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";


    $schedules=new system_tasks();
    if(isset($_GET["ForceTaskType"])){
        $new_entry=$new_entry." &raquo; ".$schedules->tasks_array[$_GET["ForceTaskType"]];
    }

    $delete=$tpl->javascript_parse_text("{delete}");
    $zone=$tpl->_ENGINE_parse_body("{zone}");
    $dns_server=$tpl->_ENGINE_parse_body("{dns_server}");


    $html[]=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?AddNewSchedule-js=yes&ID=0&ForceTaskType={$_GET["ForceTaskType"]}');\"><i class='fa fa-plus'></i> $new_entry </label>
						
			<label class=\"btn btn btn-primary\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> $build_config </label>
			</div>");


    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";



    // text,html,number,date
    $TRCLASS=null;
    $html[]="<th data-sortable=true class='text-capitalize' data-type='number'>ID</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$task</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{recipients}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{schedule}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$description</th>";
    $html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{download}</th>";
    $html[]="<th data-sortable=false class='text-capitalize center' data-type='text'>$run</th>";
    $html[]="<th data-sortable=false class='text-capitalize center' data-type='text>$enabled</th>";
    $html[]="<th data-sortable=false class='text-capitalize center' data-type='text'>$delete</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    if(intval($_GET["ForceTaskType"])>0){
        $FORCE_FILTER="WHERE TaskType='{$_GET["ForceTaskType"]}'";
    }

    $sql="SELECT *  FROM $table $FORCE_FILTER";
    writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $tdprc1=$tpl->table_td1prc();
    $tdprc1l=$tpl->table_td1prc_left();

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $color="#676A6C";
        $md=md5(serialize($ligne));
        $TaskType=$ligne["TaskType"];
        $taskid=intval($ligne['ID']);
        $local_path="/usr/share/artica-postfix/PDF/$taskid.pdf";

        $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/statscom.schedule.progres";
        $ARRAY["LOG_FILE"]=PROGRESS_DIR."/statscom.report.log";
        $ARRAY["CMD"]="statscom.php?schedule={$ligne['ID']}";
        $ARRAY["TITLE"]="{building_schedule}";
        $prgress=base64_encode(serialize($ARRAY));
        $jsSend="Loadjs('fw.progress.php?content=$prgress&mainid=progress-firehol-restart')";

        $ligne["TaskType"]=$tpl->_ENGINE_parse_body($GLOBALS["TASKS_TYPE"][$ligne["TaskType"]]);
        $TimeDescription=$ligne["TimeDescription"];
        $color_href=null;


        $enable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-task-js={$ligne['ID']}&ForceTaskType={$_GET["ForceTaskType"]}')",$md,"AsSystemAdministrator");

        $delete=$tpl->icon_delete("Loadjs('$page?delete-task-js={$ligne['ID']}&ForceTaskType={$_GET["ForceTaskType"]}')","AsSystemAdministrator");
        $explainTXT=$ligne["subject"];

        if($TaskType==7){
            $explainTXT=$explainTXT."<br><small>{synchronize_data_stats}</small>";
        }

        $run=$tpl->icon_run($jsSend);
        if($ligne["enabled"]==0){$color="#A0A0A0";$run="&nbsp;";$color_href="style='color:#A0A0A0'";}

        $TimeText=$tpl->_ENGINE_parse_body($schedules->PatternToHuman($ligne["TimeText"]));
        $TimeText=str_replace("<br>", "", $TimeText);
        if(preg_match("#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)#", $TimeDescription,$re)){$TimeDescription=$TimeText;$TimeText=null;}

        $js="Loadjs('$page?AddNewSchedule-js=yes&ID={$ligne['ID']}&ForceTaskType={$_GET["ForceTaskType"]}');";

        $js="<a href=\"javascript:blur();\" OnClick=\"$js\" $color_href>";


        $ligne["TaskType"]=utf8_encode($ligne["TaskType"]);
        $ligne["recipients"]=str_replace(";",",",$ligne["recipients"]);
        $ligne["recipients"]=str_replace(",","<br>",$ligne["recipients"]);

        $download=$tpl->icon_download_artica($local_path);

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td $tdprc1>$js{$ligne['ID']}</a></td>";
        $html[]="<td $tdprc1l>$js{$ligne["TaskType"]}</a></td>";
        $html[]="<td width='1%' nowrap>$js<small>{$ligne["recipients"]}</small></a></td>";
        $html[]="<td style='color:$color'>$TimeDescription</td>";
        $html[]="<td style='color:$color'>$explainTXT</td>";
        $html[]="<td $tdprc1>$download</td>";
        $html[]="<td $tdprc1>$run</td>";
        $html[]="<td $tdprc1>$enable</td>";
        $html[]="<td $tdprc1>$delete</td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='8'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable(
	{
	\"filtering\": {
	\"enabled\": true
},
\"sorting\": {
\"enabled\": true
}

}


); });

function CreateNewVPNRoute(){
Loadjs('$page?ruleid-js=');

}


</script>";

    echo $tpl->_ENGINE_parse_body($html);
}
