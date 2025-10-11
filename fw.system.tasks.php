<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){
        include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
    }
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}

include_once(dirname(__FILE__)."/ressources/class.tasks.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["btns-js"])){echo base64_decode($_GET["btns-js"]);exit;}
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


    echo "<div class='row'>
		<div id='progress-firehol-restart'></div>
		<div class='ibox-content'>
			<div id='table-system-task-loader'></div>
		</div>
	</div>	<script>
	LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');

	</script>";

}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tt=new system_tasks();
    $explain="{system_tasks_text}";
    $title="{system_tasks}";
    if(intval($_GET["ForceTaskType"])>0){
        $title=$tt->tasks_array[$_GET["ForceTaskType"]];
        $explain=$tt->tasks_explain_array[$_GET["ForceTaskType"]];
    }



    $html=$tpl->page_header($title,
        "fa fa-clock",$explain,
        "$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}",
        "system-tasks","progress-firehol-restart",false,"table-system-task-loader"
    );

    $tpl=new template_admin("System Tasks",$html);
    if(isset($_GET["main-page"])){
        echo $tpl->build_firewall();
        return;
    }


    echo $tpl->_ENGINE_parse_body($html);

}

function btns(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $taskz=null;
    $tasks=new system_tasks();
    $ForceTaskType=0;
    if(isset($_GET["ForceTaskType"])) {
        $ForceTaskType = intval($_GET["ForceTaskType"]);
    }
    if($ForceTaskType>0){
        if(isset($tasks->tasks_array[$ForceTaskType ])) {
            $taskz = $tpl->_ENGINE_parse_body($tasks->tasks_array[$ForceTaskType]);
        }
    }

    $jsrestart=$tpl->framework_buildjs(
        "services.php?build-system-tasks=yes",
        "tasks.compile.progress",
        "tasks.compile.txt",
        "progress-firehol-restart",
        "LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType=$ForceTaskType');LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');document.getElementById('progress-firehol-restart').innerHTML='';");

    $new_entry=$tpl->_ENGINE_parse_body("{new_task}");
    if(strlen($taskz)>1){
            $new_entry = $new_entry . " &raquo; $taskz";
    }

    $btns=$tpl->_ENGINE_parse_body("
			<div class=\"btn-group\" data-toggle=\"buttons\">
			<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?AddNewSchedule-js=yes&ID=0&ForceTaskType=$ForceTaskType');\"><i class='fa fa-plus'></i> $new_entry </label>
			<label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_all_schedules} </label>
			</div>");
    return $btns;
}

function sub_page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $taskz="{system_tasks}";$task_explain="{system_tasks_text}";
    $tasks=new system_tasks();
    if(intval($_GET["ForceTaskType"])>0){
        $taskz=$tpl->_ENGINE_parse_body($tasks->tasks_array[$_GET["ForceTaskType"] ] );
        $task_explain=$tpl->_ENGINE_parse_body($tasks->tasks_explain_array[$_GET["ForceTaskType"] ] );
    }


    $btns=btns();
    $TINY_ARRAY["TITLE"]=$taskz;
    $TINY_ARRAY["ICO"]="fa fa-clock";
    $TINY_ARRAY["EXPL"]=$task_explain;
    $TINY_ARRAY["URL"]="system-tasks";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    echo "<div class='row' style='margin-top:10px'>
		<div id='progress-firehol-restart'></div>
		<div class='ibox-content'>
			<div id='table-system-task-loader'></div>
		</div>
	</div>



	<script>
	LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');
    $jstiny;
	</script>";

}

function schedule_enable(){
    header("content-type: application/x-javascript");
    $ID=$_GET["enable-task-js"];
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $ligne=$q->mysqli_fetch_array("SELECT enabled FROM system_schedules WHERE ID=$ID");
    $enabled=$ligne["enabled"];
    if($enabled==0){$enabled=1;}else{$enabled=0;}
    $q->QUERY_SQL("UPDATE system_schedules SET `enabled`='$enabled' WHERE ID='$ID'");
    if(!$q->ok){
        echo "alert('".$q->mysql_error."')";
    }

}
function schedule_run_task_js(){
    $ID=$_GET["run-task-js"];
    $tasks=new system_tasks();
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $tpl=new template_admin();
    $ligne=$q->mysqli_fetch_array("SELECT TaskType FROM system_schedules WHERE ID=$ID");
    $taskz=$tpl->javascript_parse_text($tasks->tasks_array[ $ligne["TaskType"] ] );
    $title="{schedule}::$ID::{$ligne["TaskType"]} ({$taskz})";
    $tpl->js_confirm_execute($title,"execute",$ID);
}

function schedule_execute(){

    $sock=new sockets();
    $sock->getFrameWork("services.php?run-scheduled-task={$_POST["execute"]}");

}


function schedule_delete_js(){
    $ID=$_GET["delete-task-js"];
    $tasks=new system_tasks();
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ligne=$q->mysqli_fetch_array("SELECT TaskType FROM system_schedules WHERE ID=$ID");
    $taskz=$tpl->javascript_parse_text($tasks->tasks_array[ $ligne["TaskType"] ] );
    $title="{schedule}::$ID::{$ligne["TaskType"]} ({$taskz})";
    $tpl->js_confirm_delete($title,"remove",$ID,"LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');");
}
function schedule_remove(){
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $ID=$_POST["remove"];
    $q->QUERY_SQL("DELETE FROM system_schedules WHERE ID=$ID");
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
        $ligne=$q->mysqli_fetch_array("SELECT TaskType FROM system_schedules WHERE ID=$id");
        $taskz=$tpl->javascript_parse_text($tasks->tasks_array[ $ligne["TaskType"] ] );
        $title="{schedule}::$id::{$ligne["TaskType"]} ({$taskz})";

    }

    $tpl->js_dialog1($title, "$page?AddNewSchedule-popup=yes&ID=$id&ForceTaskType={$_GET["ForceTaskType"]}&ForceType={$_GET["ForceTaskType"]}");
}
function schedule_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $tasks=new system_tasks();
    $taskz=array();
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $no_schedule_set=null;
    $buttontext="{add}";
    if(isset($_GET["ForceTaskType"])){
        if(intval($_GET["ForceTaskType"])>0){$_GET["ForceType"]=$_GET["ForceTaskType"];}
    }
    if(!isset($_GET["ForceType"])){$_GET["ForceType"]=0;}
    if(!is_numeric($_GET["ForceType"])){$_GET["ForceType"]=0;}
    $ID=$_GET["ID"];
    $titleplus=null;$ligne=array();
    $jsafter="dialogInstance1.close();LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');";
    $ligne["TaskType"]=0;
    $ligne["TimeText"]="";
    $ligne["TimeDescription"]="";


    if($ID>0){
        $buttontext="{apply}";
        $ligne=$q->mysqli_fetch_array("SELECT * FROM system_schedules WHERE ID=$ID");
        $ligne["TimeDescription"]=$tpl->utf8_encode($ligne["TimeDescription"]);
        $titleplus="ID:$ID, {type} {$ligne["TaskType"]} &laquo;{$ligne["TimeText"]}&raquo;";
        $jsafter="LoadAjax('table-system-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');";
        $no_schedule_set="<div class='alert alert-danger'>".$tpl->javascript_parse_text("{no_schedule_set}")."</div>";
    }

    if(!is_numeric($ligne["TaskType"])){$ligne["TaskType"]=0;}
    if(!is_numeric($ID)){$ID=0;}

    $task_type=$tasks->tasks_array;
    unset($task_type[5]);
    unset($task_type[12]);


    if(!$users->UPDATE_UTILITYV2_INSTALLED){
        unset($task_type[13]);
    }

    $task_type=$tasks->tasks_array;
    foreach ($task_type as $TaskType=>$content){
        $taskz[$TaskType]="[{$TaskType}] ".$tpl->_ENGINE_parse_body($content);

    }
    if($_GET["ForceType"]>0){
        $ligne["TaskType"]=$_GET["ForceType"];
        unset($taskz);
        $taskz[$_GET["ForceType"]]=$tpl->_ENGINE_parse_body($task_type[$_GET["ForceType"]]);
        $titleplus=": <strong>{$tasks->tasks_array[$_GET["ForceType"]]}</strong>";
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



    $explain[]= $tasks->tasks_explain_array[ $ligne["TaskType"]];
    if($ligne["TimeText"]<>null){
        $tsk=new system_tasks();
        $explain[]="<strong>".$tsk->PatternToHuman($ligne["TimeText"],true)."</strong>";
    }
    $explain_text=@implode("<br>",$explain);
    $html[]=$tpl->form_outside("{set_schedule} $titleplus",  $form,$explain_text
       ,$buttontext,$jsafter,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function schedule_save(){
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $tpl=new template_admin();
    $task=new system_tasks();
    $task_type=$task->tasks_array;
    $tpl->CLEAN_POST();

    $info=$tpl->javascript_parse_text($task_type[$_POST["TaskType"]]);
    $defaultdesc=replace_accents($info);
    if($_POST["TimeDescription"]==null){$_POST["TimeDescription"]=$defaultdesc ." : {$_POST["TimeText"]}";}

    $_POST["TimeDescription"]=$tpl->CLEAN_BAD_XSS($_POST["TimeDescription"]);
    $_POST["TimeDescription"]=$q->sqlite_escape_string2($_POST["TimeDescription"]);

    $sql="INSERT INTO system_schedules (TimeDescription,TimeText,TaskType,enabled)
	VALUES('{$_POST["TimeDescription"]}','{$_POST["TimeText"]}','{$_POST["TaskType"]}',1)";

    if($_POST["ID"]>0){
        $sql="UPDATE system_schedules SET
	TimeDescription='{$_POST["TimeDescription"]}',
			TimeText='{$_POST["TimeText"]}',
			TaskType='{$_POST["TaskType"]}' WHERE ID={$_POST["ID"]}
				";

    }



    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;return;}


}


function main(){
    $tpl=new template_admin();
    $sock=new sockets();
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $table="system_schedules";
    if(!$q->TABLE_EXISTS($table)){$q->BuildTables();}

    $description=$tpl->_ENGINE_parse_body("{description}");
    $task=$tpl->_ENGINE_parse_body("{task}");
    $run=$tpl->_ENGINE_parse_body("{run}");
    $enabled=$tpl->_ENGINE_parse_body("{enabled}");
    $FORCE_FILTER=null;





    $sock->getFrameWork("cron.php?listfiles=yes");
    $listfiles=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/cron.lists"));

    $delete=$tpl->javascript_parse_text("{delete}");


    $html[]="<table id='table-dns-forward-zones' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $TRCLASS=null;
    $html[]="<th data-sortable=true class='text-capitalize' data-type='number'>ID</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$task</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$description</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$description</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'><center>$run</center></th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'><center>$enabled</center></th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'><center>$delete</center></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";


    if(intval($_GET["ForceTaskType"])>0){
        $FORCE_FILTER="AND TaskType='{$_GET["ForceTaskType"]}'";
    }

    $sql="SELECT *  FROM $table WHERE 1 $FORCE_FILTER";
    writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
    $results = $q->QUERY_SQL($sql);
    $schedules=new system_tasks();

    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $color="#676A6C";
        $md=md5(serialize($ligne));
        $TaskType=$ligne["TaskType"];
        $ligne["TaskType"]=$tpl->_ENGINE_parse_body($schedules->tasks_array[$ligne["TaskType"]]);
        $TimeDescription=$ligne["TimeDescription"];
        $ID=$ligne["ID"];
        $path="syssch-$ID";

        if(isset($listfiles[$path])){
            $status="<span class='label label-primary'>{active2}</span>";
        }else{
            $status="<span class='label label'>{inactive}</span>";
        }

        $color_href=null;


        $enable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-task-js={$ligne['ID']}&ForceTaskType={$_GET["ForceTaskType"]}')",$md,"AsSystemAdministrator");

        $delete=$tpl->icon_delete("Loadjs('$page?delete-task-js={$ligne['ID']}&ForceTaskType={$_GET["ForceTaskType"]}')","AsSystemAdministrator");
        $explainTXT=$tpl->_ENGINE_parse_body($schedules->tasks_explain_array[$TaskType]);

        $run=$tpl->icon_run("Loadjs('$page?run-task-js={$ligne['ID']}&ForceTaskType={$_GET["ForceTaskType"]}')");
        if($ligne["enabled"]==0){$color="#A0A0A0";$run="&nbsp;";$color_href="style='color:#A0A0A0'";}

        $TimeText=$tpl->_ENGINE_parse_body($schedules->PatternToHuman($ligne["TimeText"]));
        $TimeText=str_replace("<br>", "", $TimeText);
        if(preg_match("#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)#", $TimeDescription,$re)){
            $TimeDescription=$TimeText;$TimeText=null;}

        $js="Loadjs('$page?AddNewSchedule-js=yes&ID={$ligne['ID']}&ForceTaskType={$_GET["ForceTaskType"]}');";

        $js="<a href=\"javascript:blur();\" OnClick=\"$js\" $color_href>";


        $ligne["TaskType"]=$tpl->utf8_encode($ligne["TaskType"]);


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='color:$color;width:1%'>$js{$ligne['ID']}</a></td>";
        $html[]="<td style='color:$color;width:1%' nowrap>$js$status</a></td>";
        $html[]="<td style='color:$color'>$js{$ligne["TaskType"]}</a></td>";
        $html[]="<td style='color:$color'>$TimeDescription</td>";
        $html[]="<td style='color:$color'>$explainTXT</td>";
        $html[]="<td style='color:$color' class=center width=1%>$run</td>";
        $html[]="<td style='color:$color' class=center width=1%>$enable</td>";
        $html[]="<td style='color:$color' class=center width=1%>$delete</td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $taskz="{system_tasks}";
    $task_explain="{system_tasks_text}";
    $tasks=new system_tasks();
    if(intval($_GET["ForceTaskType"])>0){
        if(isset($tasks->tasks_array[$_GET["ForceTaskType"]])) {
            $taskz = $tpl->_ENGINE_parse_body($tasks->tasks_array[$_GET["ForceTaskType"]]);
        }
        if(isset($tasks->tasks_explain_array[$_GET["ForceTaskType"] ])) {
            $task_explain = $tpl->_ENGINE_parse_body($tasks->tasks_explain_array[$_GET["ForceTaskType"]]);
        }
    }

    $btns=btns();
    $TINY_ARRAY["TITLE"]=$taskz;
    $TINY_ARRAY["ICO"]="fa fa-clock";
    $TINY_ARRAY["EXPL"]=$task_explain;
    $TINY_ARRAY["URL"]="system-tasks";
    $TINY_ARRAY["BUTTONS"]=$btns;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="<tr>";
    $html[]="<td colspan='8'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('.footable').footable(
	{
	\"filtering\": {
	\"enabled\": true
},
\"sorting\": {
\"enabled\": true
}

}


); });
$jstiny
</script>";

    echo $tpl->_ENGINE_parse_body($html);
}