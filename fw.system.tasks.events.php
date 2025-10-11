<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
include_once(dirname(__FILE__)."/ressources/class.tasks.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


if(isset($_GET["table"])){table();exit;}
if(isset($_GET["ShowID-js"])){ShowID_js();exit;}
if(isset($_GET["ShowID"])){ShowID();exit;}
if(isset($_GET["rule-popup"])){rule_settings();exit;}
if(isset($_GET["rule-settings"])){rule_settings();exit;}
if(isset($_POST["ID"])){rule_save();exit;}
if(isset($_GET["remove-events"])){remove_events_js();exit;}
if(isset($_GET["empty-js"])){empty_js();exit;}
if(isset($_POST["empty"])){empty_table();exit;}
if(isset($_POST["remove-events"])){remove_events_perform();exit;}
page();

function ShowID_js(){
	
	$id=$_GET["ShowID-js"];
	if(!is_numeric($id)){
	
		return;
	
	}$tpl=new template_admin();
	$page=CurrentPageName();
	$sql="SELECT subject FROM squid_admin_mysql WHERE ID=$id";
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$ligne=$q->mysqli_fetch_array($sql);
	$subject=$tpl->javascript_parse_text($ligne["subject"]);
	$tpl->js_dialog($subject, "$page?ShowID=$id&function={$_GET["function"]}");
	
}
function empty_js(){
	$tpl=new template_admin();
	$title="{system_events}";
	$tpl->js_confirm_empty($title,"empty","yes","{$_GET["function"]}();");
	
}

function empty_table(){
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$q->QUERY_SQL("DROP TABLE squid_admin_mysql");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/frontend/notifications");
	
}

function ShowID(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sql="SELECT * FROM squid_admin_mysql WHERE ID={$_GET["ShowID"]}";
	$q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
	$ligne=$q->mysqli_fetch_array($sql);
	$content=$tpl->_ENGINE_parse_body($ligne["content"]);
	$content=nl2br($content);
	echo "<p>$content</p>";

	$filename=$ligne["filename"];
	$line=$ligne["line"];
	$html[]="<div style='text-align:right;margin-top:10px'>";
	$html[]=$tpl->button_autnonome("{remove_all_same}","Loadjs('$page?remove-events=yes&filename=$filename&line=$line&function={$_GET["function"]}');",
        "fas fa-trash-alt","AsSystemAdministrator",0,"btn-danger");
	$html[]="</div>";
	echo $tpl->_ENGINE_parse_body($html);

}
function remove_events_js(){
    $filename=$_GET["filename"];
    $line=$_GET["line"];
    $tpl=new template_admin();
    $funcadd=null;

    if($_GET["function"]<>null){
        $funcadd="{$_GET["function"]}();";
    }

    $tpl->js_confirm_empty("{remove_all_same} $filename ($line)","remove-events","$filename;$line",
        "BootstrapDialog1.close();{$funcadd}RefreshNotifs();");
}
function remove_events_perform(){
    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $tt=explode(";",$_POST["remove-events"]);
    $filename=$tt[0];
    $line=intval($tt[1]);

    $q->QUERY_SQL("DELETE FROM squid_admin_mysql WHERE filename='$filename' AND line='$line'");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/frontend/notifications");

}

function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($_GET["t"])){$t=time();}else{$t=$_GET["t"];}
	if($_SESSION["SYSEVS_SEARCH"]==null){$_SESSION["SYSEVS_SEARCH"]="limit 200";}
	
	$html[]="<div class='row'><div class='ibox-content'>";
	$html[]=$tpl->search_block($page,"sqlite:/home/artica/SQLITE/schedule_events.db","events","table-loader","&table=yes&TaskType={$_GET["TaskType"]}");
	$html[]="<div id='table-loader'></div>
	</div>
	</div>
		
		
		
<script>
		Start$t();
	</script>";
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,@implode("\n", $html));
        echo $tpl->build_firewall();
        return;
    }
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function table(){
	$tpl=new template_admin();
	$page=CurrentPageName();

	$eth_sql=null;
	$token=null;
	$class=null;

	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$date=$tpl->_ENGINE_parse_body("{date}");
	$events=$tpl->javascript_parse_text("{events}");


	$html[]=$tpl->_ENGINE_parse_body("
			<table id='table-firewall-rules' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">");
	$html[]="<thead>";
	$html[]="<tr>";


	$search=trim($_GET["search"]);
	$_SESSION["SYSEVS_SEARCH"]=trim(strtolower($search));
    $searchsql=null;
	if($search<>null) {
        $search = "*$search*";
        $search = str_replace("***", "*", $search);
        $search = str_replace("**", "*", $search);
        $search = str_replace("*", "%", $search);
        $search = str_replace("%%", "%", $search);

        $searchsql=" AND (subject LIKE '$search')";
    }

    $q=new lib_sqlite("/home/artica/SQLITE/schedule_events.db");

	
	$sql="SELECT ID,prio as severity,zdate,subject FROM events WHERE TaskType={$_GET["TaskType"]}{$searchsql} ORDER BY zdate DESC LIMIT 500";
	
	$results=$q->QUERY_SQL($sql);
	

	
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$date</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>$events</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";

	$jsAfter="LoadAjax('table-loader','$page?table=yes&eth={$_GET["eth"]}');";
	$GLOBALS["jsAfterEnc"]=base64_encode($jsAfter);
	

	
	if(!$q->ok){
		
		echo "<div class='alert alert-danger'>$q->mysql_error<br><strong><code>{$_GET["search"]}</code></strong><br><strong><code>$sql</code></strong></div>";
	}
	
	$severityCL[0]="label-danger";
	$severityCL[1]="label-warning";
	$severityCL[2]="label-primary";
	
	$severityTX[0]="text-danger";
	$severityTX[1]="text-warning";
	$severityTX[2]="text-primary";

	
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$text_class=null;
		$id=md5(serialize($ligne));
		$zdate=$tpl->time_to_date($ligne["zdate"],true);
		$subject=$ligne["subject"];
		if(preg_match("#success#i",$subject)){$ligne["severity"]=2;}
		$severity_class=$severityCL[$ligne["severity"]];

		$html[]="<tr class='$TRCLASS' id='$id'>";
		$html[]="<td class=\"$text_class\" width=1% nowrap><div class='label $severity_class' style='font-size:13px;padding:10px;width:100%' >$zdate</a></div></td>";
		$html[]="<td class=\"$text_class\">{$subject}</td>";
		$html[]="</tr>";
		

	}

	$html[]="</tbody>";
	$html[]="<tfoot>";

	$html[]="<tr>";
	$html[]="<td colspan='2'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table><div><i>$sql</i></div>";

    $title="{events}";
    $explain="{system_events}";
    if(intval($_GET["TaskType"])>0){
        $tasks=new system_tasks();
        $title="{events}: ".$tpl->_ENGINE_parse_body($tasks->tasks_array[$_GET["TaskType"] ] );
        $explain=$tpl->_ENGINE_parse_body($tasks->tasks_explain_array[$_GET["TaskType"] ] );
    }

    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]=$explain;
    $TINY_ARRAY["URL"]="";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";



    $html[]="
	<script>
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('.footable').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
$jstiny
</script>";

			echo $tpl->_ENGINE_parse_body($html);

}
