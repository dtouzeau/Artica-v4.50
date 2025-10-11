<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.tasks.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["main"])){main();exit;}
if(isset($_GET["AddNewSchedule-js"])){schedule_js();exit;}
if(isset($_GET["AddNewSchedule-popup"])){schedule_popup();exit;}
if(isset($_GET["enable-task-js"])){schedule_enable();exit;}
if(isset($_GET["delete-task-js"])){schedule_delete_js();exit;}
if(isset($_GET["run-task-js"])){schedule_run_task_js();exit;}
if(isset($_POST["ID"])){schedule_save();exit;}
if(isset($_POST["remove"])){schedule_remove();exit;}
if(isset($_POST["execute"])){schedule_execute();exit;}
if(isset($_GET["microstart"])){microstart();exit;}

page();


function microstart():bool{
	$page=CurrentPageName();
    if(!isset($_GET["ForceTaskType"])){$_GET["ForceTaskType"]=0;}
    $ForceTaskType=intval($_GET["ForceTaskType"]);
    $jstiny="";

	if($ForceTaskType>0){
		$qProxy=new mysql_squid_builder(true);
		$title=$qProxy->tasks_array[$ForceTaskType];
		$explain=$qProxy->tasks_explain_array[$ForceTaskType];
        $TINY_ARRAY["TITLE"]="{tasks}: $title";
        $TINY_ARRAY["ICO"]="fa fa-clock";
        $TINY_ARRAY["EXPL"]=$explain;
        $TINY_ARRAY["URL"]=null;
        $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    }

    echo "	
<div class='row'>
		<div id='progress-firehol-restart'></div>
		<div class='ibox-content'>
			<div id='table-proxy-task-loader'></div>
		</div>
</div>	
<script>
	LoadAjax('table-proxy-task-loader','$page?main=yes&ForceTaskType=$ForceTaskType');
    $jstiny
</script>";
	return true;
}


function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["ForceTaskType"])){
        $_GET["ForceTaskType"]=0;
    }

    $html=$tpl->page_header("{proxy_tasks}","fa fa-clock",
        "{proxy_tasks_explain}","$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}",
        "proxy-tasks","progress-firehol-restart",false,"table-proxy-task-loader");


	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function sub_page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$qProxy=new mysql_squid_builder(true);
    if(!isset($_GET["ForceTaskType"])){$_GET["ForceTaskType"]=0;}
    $ForceTaskType=intval($_GET["ForceTaskType"]);
    $taskz="";
    $task_explain="";
	if($ForceTaskType>0){
		$taskz=$tpl->_ENGINE_parse_body($qProxy->tasks_array[$ForceTaskType] );
		$task_explain=$tpl->_ENGINE_parse_body($qProxy->tasks_explain_array[$ForceTaskType] );
	}
	
	echo "<div class='row'>
		<h2>$taskz</h2><p>$task_explain</p>
		<div id='progress-firehol-restart'></div>
		<div class='ibox-content'>
			<div id='table-proxy-task-loader'></div>
		</div>
	</div>



	<script>
	LoadAjax('table-proxy-task-loader','$page?main=yes&ForceTaskType=$ForceTaskType');
	</script>";
	
}

function schedule_enable(){
	header("content-type: application/x-javascript");
	$ID=$_GET["enable-task-js"];
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$ligne=$q->mysqli_fetch_array("SELECT enabled FROM webfilters_schedules WHERE ID=$ID");
	$enabled=$ligne["enabled"];
	if($enabled==0){$enabled=1;}else{$enabled=0;}
	$q->QUERY_SQL("UPDATE webfilters_schedules SET `enabled`='$enabled' WHERE ID='$ID'");
	if(!$q->ok){
		echo "alert('".$q->mysql_error."')";
	}
	
}
function schedule_run_task_js(){
	$ID=$_GET["run-task-js"];
	$tasks=new system_tasks();
	$qProxy=new mysql_squid_builder(true);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ligne=$q->mysqli_fetch_array("SELECT TaskType FROM webfilters_schedules WHERE ID=$ID");
	$taskz=$tpl->javascript_parse_text($qProxy->tasks_array[ $ligne["TaskType"] ] );
	$title="{schedule}::$ID::{$ligne["TaskType"]} ({$taskz})";
	$tpl->js_confirm_execute($title,"execute",$ID);
}

function schedule_execute(){
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?run-scheduled-task={$_POST["execute"]}");

	
}


function schedule_delete_js(){
	$ID=$_GET["delete-task-js"];
	$tasks=new system_tasks();
	$qProxy=new mysql_squid_builder(true);$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$tpl=new template_admin();
	$page=CurrentPageName();
	$ligne=$q->mysqli_fetch_array("SELECT TaskType FROM webfilters_schedules WHERE ID=$ID");
	$taskz=$tpl->javascript_parse_text($qProxy->tasks_array[ $ligne["TaskType"] ] );
	$title="{schedule}::$ID::{$ligne["TaskType"]} ({$taskz})";
	$tpl->js_confirm_delete($title,"remove",$ID,"LoadAjax('table-proxy-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');");
}
function schedule_remove(){
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$ID=$_POST["remove"];
	$q->QUERY_SQL("DELETE FROM webfilters_schedules WHERE ID=$ID");
}

function schedule_js(){
	$tasks=new system_tasks();
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new template_admin();
	$title=$tpl->javascript_parse_text("{new_schedule}");
	$id=intval($_GET["ID"]);
	if($id>0){
		$qProxy=new mysql_squid_builder(true);$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
		$ligne=$q->mysqli_fetch_array("SELECT TaskType FROM webfilters_schedules WHERE ID=$id");
		$taskz=$tpl->javascript_parse_text($qProxy->tasks_array[ $ligne["TaskType"] ] );
		$title="{schedule}::$id::{$ligne["TaskType"]} ({$taskz})";
	
	}
	
	$tpl->js_dialog1($title, "$page?AddNewSchedule-popup=yes&ID=$id&ForceTaskType={$_GET["ForceTaskType"]}&ForceType={$_GET["ForceTaskType"]}");
}
function schedule_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$tasks=new system_tasks();
	$qProxy=new mysql_squid_builder(true);$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$no_schedule_set=null;
	$buttontext="{add}";
	if(isset($_GET["ForceTaskType"])){
		if(intval($_GET["ForceTaskType"])>0){$_GET["ForceType"]=$_GET["ForceTaskType"];}
	}
	if(!isset($_GET["ForceType"])){$_GET["ForceType"]=0;}
	if(!is_numeric($_GET["ForceType"])){$_GET["ForceType"]=0;}
	$ID=$_GET["ID"];
	$titleplus=null;
	$jsafter="dialogInstance1.close();LoadAjax('table-proxy-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');";
	if($ID>0){
		$buttontext="{apply}";
		$ligne=$q->mysqli_fetch_array("SELECT * FROM webfilters_schedules WHERE ID=$ID");
		$ligne["TimeDescription"]=utf8_encode($ligne["TimeDescription"]);
		$titleplus="ID:$ID, {type} {$ligne["TaskType"]} &laquo;{$ligne["TimeText"]}&raquo;";
		$jsafter="LoadAjax('table-proxy-task-loader','$page?main=yes&ForceTaskType={$_GET["ForceTaskType"]}');";
		$no_schedule_set="<div class='alert alert-danger'>".$tpl->javascript_parse_text("{no_schedule_set}")."</div>";
	}

	if(!is_numeric($ligne["TaskType"])){$ligne["TaskType"]=0;}
	if(!is_numeric($ID)){$ID=0;}

	$task_type=$qProxy->tasks_array;
	unset($task_type[5]);
	unset($task_type[12]);

	if(!$users->UPDATE_UTILITYV2_INSTALLED){
		unset($task_type[13]);
	}


	foreach ($task_type as $TaskType=>$content){
		$taskz[$TaskType]="[{$TaskType}] ".$tpl->_ENGINE_parse_body($content);

	}
	if($_GET["ForceType"]>0){
		$ligne["TaskType"]=$_GET["ForceType"];
		unset($taskz);
		$taskz[$_GET["ForceType"]]=$tpl->_ENGINE_parse_body($task_type[$_GET["ForceType"]]);
		$titleplus=": <strong>{$qProxy->tasks_array[$_GET["ForceType"]]}</strong>";
	}
	
	if($ligne["TimeText"]<>null){$no_schedule_set=null;}
	
	
	$html[]=$no_schedule_set;
	$form[]=$tpl->field_hidden("ID", $ID);
	if($_GET["ForceType"]==0){
		$form[]=$tpl->field_array_hash($taskz, "TaskType", "{task_type}", $ligne["TaskType"],true);
	}else{
		$form[]=$tpl->field_hidden("TaskType", $_GET["ForceType"]);
	}
	$form[]=$tpl->field_text("TimeDescription", "{description}", $ligne["TimeDescription"]);
	$form[]=$tpl->field_schedule("TimeText", "{schedule}", $ligne["TimeText"],true);
	$html[]=$tpl->form_outside("{set_schedule} $titleplus", @implode("\n", $form),
	$qProxy->tasks_explain_array[ $ligne["TaskType"]],$buttontext,$jsafter,"AsSystemAdministrator");
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function schedule_save(){
	$users=new usersMenus();
	$qProxy=new mysql_squid_builder(true);
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$tpl=new template_admin();
	$task=new system_tasks();
	$task_type=$qProxy->tasks_array;
	
	$tpl->CLEAN_POST();
	
	$info=$tpl->javascript_parse_text($task_type[$_POST["TaskType"]]);
	$defaultdesc=replace_accents($info);
	if($_POST["TimeDescription"]==null){$_POST["TimeDescription"]=$defaultdesc ." : {$_POST["TimeText"]}";}
	
	$_POST["TimeDescription"]=$q->sqlite_escape_string2($_POST["TimeDescription"]);
    $_POST["TimeDescription"]=$tpl->CLEAN_BAD_XSS($_POST["TimeDescription"]);
	
	$sql="INSERT OR IGNORE INTO webfilters_schedules (TimeDescription,TimeText,TaskType,enabled)
	VALUES('{$_POST["TimeDescription"]}','{$_POST["TimeText"]}','{$_POST["TaskType"]}',1)";
	
	if($_POST["ID"]>0){
	$sql="UPDATE webfilters_schedules SET
	TimeDescription='{$_POST["TimeDescription"]}',
			TimeText='{$_POST["TimeText"]}',
			TaskType='{$_POST["TaskType"]}' WHERE ID={$_POST["ID"]}
				";
	
	}
	
	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
}


function main():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$qProxy=new mysql_squid_builder(true);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$table="webfilters_schedules";
    if(!isset($_GET["ForceTaskType"])){ $_GET["ForceTaskType"]=0; }

	$description=$tpl->_ENGINE_parse_body("{description}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$build_config=$tpl->_ENGINE_parse_body("{apply_all_schedules}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$ForceTaskType=intval($_GET["ForceTaskType"]);
	$FORCE_FILTER=null;


    $jsrestart=$tpl->framework_buildjs("/proxy/schedule/apply",
        "squid.databases.schedules.progress",
    "squid.databases.schedules.progress.log",
    "progress-firehol-restart","LoadAjax('table-proxy-task-loader','$page?main=yes&ForceTaskType=$ForceTaskType');");

    //

	if($ForceTaskType==1){
		$DisableCategoriesDatabasesUpdates=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableCategoriesDatabasesUpdates"));
		if($DisableCategoriesDatabasesUpdates==1){
			echo $tpl->FATAL_ERROR_SHOW_128("{feature_disabled}");
			return false;
		}
	}

    $jsonStatus=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/schedule/status"));
	$delete=$tpl->javascript_parse_text("{delete}");
	$new_entry=$tpl->_ENGINE_parse_body("{new_task}");

	$html[]="<table id='table-proxy-tasks-zones' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$TRCLASS=null;
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
	$html[]="<th data-sortable=true class='center text-capitalize' data-type='text'>$description</th>";
	$html[]="<th data-sortable=false class='center text-capitalize' data-type='text'>$run</th>";
	$html[]="<th data-sortable=false class='center text-capitalize' data-type='text'>$enabled</th>";
	$html[]="<th data-sortable=false class='center text-capitalize' data-type='text'>$delete</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";


	if($ForceTaskType>0){
		$FORCE_FILTER="AND TaskType='$ForceTaskType'";
	}
	
	$sql="SELECT *  FROM $table WHERE 1 $FORCE_FILTER";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	$schedules=new system_tasks();

	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$color="#676A6C";
        $ID=$ligne['ID'];
		$md=md5(serialize($ligne));
		$TaskType=intval($ligne["TaskType"]);
        $explainTXT=$tpl->_ENGINE_parse_body($qProxy->tasks_array[$TaskType]);
		$TimeDescription=$ligne["TimeDescription"];

		$enable=$tpl->icon_check($ligne["enabled"],"Loadjs('$page?enable-task-js=$ID&ForceTaskType=$ForceTaskType')",$md,"AsSystemAdministrator");
		
		$delete=$tpl->icon_delete("Loadjs('$page?delete-task-js=$ID&ForceTaskType=$ForceTaskType')","AsSystemAdministrator");

		
		$run=$tpl->icon_run("Loadjs('$page?run-task-js=$ID&ForceTaskType=$ForceTaskType')");

        if($ligne["enabled"]==0){
            $color="#A0A0A0";
            $run="&nbsp;";
        }
		
		$TimeText=$tpl->_ENGINE_parse_body($schedules->PatternToHuman($ligne["TimeText"]));
		$TimeText=str_replace("<br>", "", $TimeText);
		if(preg_match("#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)#", $TimeDescription)){$TimeDescription=$TimeText;$TimeText=null;}
		
		$js="Loadjs('$page?AddNewSchedule-js=yes&ID=$ID&ForceTaskType=$ForceTaskType');";

        $explainTXT=$tpl->utf8_encode($explainTXT);
        $status="<span class='label label-default'>N.$ID {inactive2}</span>";
        if(property_exists($jsonStatus,"Tasks")) {
            if (isset($jsonStatus->Tasks->{$ID})) {
                $status="<span class='label label-primary'>N.$ID {active2}</span>";
            }
        }
        $explainTXT=$tpl->td_href($explainTXT,"",$js);
		$status=$tpl->td_href($status,"",$js);
		$html[]="<tr class='$TRCLASS' id='$md'>";
		$html[]="<td style='color:$color;width:1%' nowrap>$status</td>";
		$html[]="<td style='color:$color'>$explainTXT<br><i>$TimeDescription</i></td>";
		$html[]="<td style='color:$color;width:1%' class=center nowrap>$run</td>";
		$html[]="<td style='color:$color;width:1%' class=center nowrap>$enable</td>";
		$html[]="<td style='color:$color;width:1%' class=center nowrap>$delete</td>";
		$html[]="</tr>";

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='6'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";


    $topbuttons[] = array("Loadjs('$page?AddNewSchedule-js=yes&ID=0&ForceTaskType=$ForceTaskType');", ico_plus, $new_entry);
    $topbuttons[] = array($jsrestart, ico_save, $build_config);
    $TINY_ARRAY["TITLE"]="{proxy_tasks}";
    $TINY_ARRAY["ICO"]=ico_clock_desk;
    $TINY_ARRAY["EXPL"]="{proxy_tasks_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

	$html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
    $jstiny
</script>";

	echo @implode("\n", $html);
    return true;
}