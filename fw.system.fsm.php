<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.openssh.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_POST["DIRECTORY"])){rule_save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["status2"])){status2();exit;}
if(isset($_POST["StrictModes"])){save_config();exit;}
if(isset($_GET["fsm-status-line"])){status_line();exit;}
if(isset($_GET["rules-start"])){rules_start();exit;}
if(isset($_GET["table"])){rules_table();exit;}
if(isset($_GET["ruleid"])){rule_js();exit;}
if(isset($_GET["ruleid-popup"])){rule_popup();exit;}
if(isset($_GET["ruleid-enable"])){rule_enable();exit;}
if(isset($_GET["ruleid-delete"])){rule_delete();exit;}
if(isset($_POST["rule-delete"])){rule_delete_perform();exit;}

if(isset($_GET["enable-wordpress-monitor"])){enable_wordpress_monitor();exit;}
if(isset($_GET["disable-wordpress-monitor"])){disable_wordpress_monitor();exit;}
if(isset($_GET["enable-schedule"])){enable_schedule();exit;}
if(isset($_GET["disable-schedule"])){disable_schedule();exit;}
if(isset($_GET["events-threats"])){events_detected();exit;}
if(isset($_GET["events-service"])){events_service();exit;}
if(isset($_GET["threats-search"])){events_detected_search();exit;}
if(isset($_GET["events-search"])){events_service_search();exit;}






page();
function page(){
	$page=CurrentPageName();
	$users=new usersMenus();
    $tpl=new template_admin();
    $html=$tpl->page_header("{APP_ARTICAFSMON}","fa-solid fa-folder-magnifying-glass",
        "{APP_ARTICAFSMON_EXPLAIN}","$page?tabs=yes","fsm","progress-fsm-restart",false,"table-loader-fsm-service");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_ARTICAFSMON}",$html);
        echo $tpl->build_firewall();
        return;
    }
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body($html);

}
function rule_js(){
    //LoadAjax('table-dnsfw-rules','$page?table=yes');
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval(trim($_GET["ruleid"]));
    $title="{rule}";
    if($ID==0){$title="{new_rule}";}
    $tpl->js_dialog5("$title: $ID","$page?ruleid-popup=$ID");
}
function rule_delete(){
    $md5=$_GET["md"];
    $tpl=new template_admin();
    $ID=intval($_GET["ruleid-delete"]);
    $ArticaFSMonParams=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMonParams")));
    $PARMS=$ArticaFSMonParams[$ID];
    $RULENAME=$PARMS["RULENAME"];
    $tpl->js_confirm_delete($RULENAME,"rule-delete",$ID,"$('#$md5').remove();");

}
function rule_delete_perform(){
    $ID=intval($_POST["rule-delete"]);
    $ArticaFSMonParams=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMonParams")));
    $PARMS=$ArticaFSMonParams[$ID];
    $RULENAME=$PARMS["RULENAME"];
    unset($ArticaFSMonParams[$ID]);
    $ArticaFSMonParamsSave=base64_encode(serialize($ArticaFSMonParams));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaFSMonParams",$ArticaFSMonParamsSave);
    admin_tracks("Removed File Monitor rule $RULENAME");
}
function rule_enable(){
    $ID=intval(trim($_GET["ruleid-enable"]));
    $ArticaFSMonParams=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMonParams")));
    if($ArticaFSMonParams[$ID]["ENABLED"]==0){
        $ArticaFSMonParams[$ID]["ENABLED"]=1;
    }else{
        $ArticaFSMonParams[$ID]["ENABLED"]=0;
    }
    $ArticaFSMonParamsSave=base64_encode(serialize($ArticaFSMonParams));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaFSMonParams",$ArticaFSMonParamsSave);
    admin_tracks_post("Set File Monitor rule $ID to enable={$ArticaFSMonParams[$ID]["ENABLED"]}");
}
function rule_popup(){
    $ID=intval(trim($_GET["ruleid-popup"]));
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->field_hidden("ID",$ID);
    $jsrestart=null;
    $ArticaFSMonParams=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMonParams")));
    if($ID==0){
        $PARMS["DIRECTORY"]="";
        $PARMS["ENABLED"]=1;
        $PARMS["RULENAME"]=$tpl->_ENGINE_parse_body("{new_rule}");
        $title="{new_rule}";
        $bt="{add}";
        $jsrestart="LoadAjax('table-rules-start','$page?table=yes')";
    }else{
        $PARMS=$ArticaFSMonParams[$ID];
        $title=$PARMS["RULENAME"];
        $bt="{apply}";
    }
    $jsrestart="$jsrestart;dialogInstance5.close();";
    $form[]=$tpl->field_checkbox("ENABLED","{enabled}",$PARMS["ENABLED"],true);
    $form[]=$tpl->field_browse_directory("DIRECTORY","{directory}",$PARMS["DIRECTORY"]);
    $form[]=$tpl->field_text("RULENAME","{rulename}",$PARMS["RULENAME"]);
    $html[]=$tpl->form_outside("{rule} $title",$form,null,$bt,$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}
function rule_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=intval($_POST["ID"]);

    $ArticaFSMonParams=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMonParams")));
    if(count($ArticaFSMonParams)==0){$ArticaFSMonParams[0]=array();}
    if($ID==0){
        $ArticaFSMonParams[]=$_POST;
    }else{
        $ArticaFSMonParams[$ID]=$_POST;
    }
    $ArticaFSMonParamsSave=base64_encode(serialize($ArticaFSMonParams));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaFSMonParams",$ArticaFSMonParamsSave);
    admin_tracks_post("Save File Monitor rule with");
}


function enable_schedule(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    header("content-type: application/x-javascript");
    $reload="LoadAjaxSilent('table-loader-fsm-status','$page?status2=yes')";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaFSMonSchedule",1);
    $jsaction=$tpl->framework_buildjs("fsm.php?build=yes",
        "fsm.progress",
        "fsm.log","progress-fsm-restart",$reload);
    echo $jsaction;
}
function disable_schedule(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    header("content-type: application/x-javascript");
    $reload="LoadAjaxSilent('table-loader-fsm-status','$page?status2=yes')";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaFSMonSchedule",0);
    $jsaction=$tpl->framework_buildjs("fsm.php?build=yes",
        "fsm.progress",
        "fsm.log","progress-fsm-restart",$reload);
    echo $jsaction;
}

function enable_wordpress_monitor(){
	$page=CurrentPageName();
	$tpl=new template_admin();

    header("content-type: application/x-javascript");
    $reload="LoadAjaxSilent('table-loader-fsm-status','$page?status2=yes')";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaFSMonWordpress",1);
    $jsaction=$tpl->framework_buildjs("fsm.php?restart=yes",
        "fsm.progress",
        "fsm.log","progress-fsm-restart",$reload);
    echo $jsaction;

}
function disable_wordpress_monitor(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    header("content-type: application/x-javascript");
    $reload="LoadAjaxSilent('table-loader-fsm-status','$page?status2=yes')";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaFSMonWordpress",0);
    $jsaction=$tpl->framework_buildjs("fsm.php?restart=yes",
        "fsm.progress",
        "fsm.log","progress-fsm-restart",$reload);
    echo $jsaction;

}
function rules_start(){
    $page=CurrentPageName();
    echo "<div id='table-rules-start'></div>
    <script>LoadAjax('table-rules-start','$page?table=yes')</script>
";
}


function rules_table(){

    $page               =   CurrentPageName();
    $t                  = time();
    $tpl                = new template_admin();
    $page               = CurrentPageName();
    $jsrestart=$tpl->framework_buildjs("fsm.php?restart=yes",
        "fsm.progress",
        "fsm.log","progress-fsm-restart");

    $btn="
	<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?ruleid=0')\"><i class='fa fa-plus'></i> {new_rule} </label>
        <label class=\"btn btn btn-info\" OnClick=\"$jsrestart\"><i class='fa fa-save'></i> {apply_rules}</label>
     ";

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true style='width:1%'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{path}</th>";
    $html[]="<th data-sortable=false style='width:1%'>{enable}</th>";
    $html[]="<th data-sortable=false style='width:1%'>DEL.</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $RULES=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMonParams")));
    $TRCLASS=null;
    foreach ($RULES as $ID=>$PARMS){
        if($ID==0){continue;}
        $class=null;
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md5=md5(serialize($PARMS));
        $DIRECTORY=$PARMS["DIRECTORY"];
        $ENABLED= $PARMS["ENABLED"];
        $RULENAME=$PARMS["RULENAME"];

        $edit="Loadjs('$page?ruleid=$ID')";
        $DIRECTORY=$tpl->td_href($DIRECTORY,null,$edit);
        $html[]="<tr class='$TRCLASS' id='$md5'>";
        $html[]="<td class=\"center\"><i class=\"fa-solid fa-folder-magnifying-glass\"></i></td>";
        $html[]="<td><strong>{$DIRECTORY} <small>($RULENAME)</small></strong></td>";
        $html[]="<td width='1%'>". $tpl->icon_check($ENABLED,"Loadjs('$page?ruleid-enable=$ID')")."</td>";
        $html[]="<td width='1%'>". $tpl->icon_delete("Loadjs('$page?ruleid-delete=$ID&md=$md5')")."</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $TINY_ARRAY["TITLE"]="{APP_ARTICAFSMON}: {rules}";
    $TINY_ARRAY["ICO"]="fa-solid fa-folder-magnifying-glass";
    $TINY_ARRAY["EXPL"]="{APP_ARTICAFSMON_EXPLAIN}";
    $TINY_ARRAY["URL"]="fsm";
    $TINY_ARRAY["BUTTONS"]=$btn;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
	</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}



function status(){
    $page                   = CurrentPageName();

    $TINY_ARRAY["TITLE"]="{APP_ARTICAFSMON}";
    $TINY_ARRAY["ICO"]="fa-solid fa-folder-magnifying-glass";
    $TINY_ARRAY["EXPL"]="{APP_ARTICAFSMON_EXPLAIN}";
    $TINY_ARRAY["URL"]="fsm";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    echo "<div id='table-loader-fsm-status'></div>
<script>LoadAjaxSilent('table-loader-fsm-status','$page?status2=yes');$jstiny</script>";
}

function status_line(){
    $tpl                    = new template_admin();
    $page                   = CurrentPageName();
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $EnableWordpressManagement=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWordpressManagement"));
    $ArticaFSMonWordpress=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMonWordpress"));
    $ArticaFSMon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMon"));
    $ArticaFSMonSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMonSchedule"));
    $ARTICA_FSMON_DIRS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICA_FSMON_DIRS"));

    //<i class="fa-solid fa-clock"></i>

    if($ArticaFSMonWordpress==1) {
        $btn[0]["js"] = "Loadjs('$page?disable-wordpress-monitor=yes');";
        $btn[0]["name"] = "{disable}";
        $btn[0]["icon"] = ico_cd;
        $WordpressProtection = $tpl->widget_vert("{WORDPRESS_MONITOR}",
            "{active2}",
            $btn, "fab fa-wordpress");
    }else{
        $btn[0]["js"] = "Loadjs('$page?enable-wordpress-monitor=yes');";
        $btn[0]["name"] = "{disable}";
        $btn[0]["icon"] = ico_cd;
        $WordpressProtection = $tpl->widget_grey("{WORDPRESS_MONITOR}",
            "{disabled}",
            $btn, "fab fa-wordpress");
    }

    if($EnableWordpressManagement==0){
        $WordpressProtection = $tpl->widget_grey("{WORDPRESS_MONITOR}",
            "{feature_not_installed}",
            null, "fab fa-wordpress");
    }
    if($EnableNginx==0){
        $WordpressProtection = $tpl->widget_grey("{WORDPRESS_MONITOR}",
            "{feature_not_installed}",
            null, "fab fa-wordpress");
    }
    if($ArticaFSMon==0){
        $ARTICA_FSMON_DIRS=0;
        $WordpressProtection = $tpl->widget_grey("{WORDPRESS_MONITOR}",
            "{feature_not_installed}",
            null, "fab fa-wordpress");
    }

    if($ArticaFSMonSchedule==1){
        $btn=array();
        $btn[0]["js"] = "Loadjs('$page?disable-schedule=yes');";
        $btn[0]["name"] = "{disable}";
        $btn[0]["icon"] = ico_cd;
        $ArticaFschedule=$tpl->widget_vert("{scheduled_monitoring}",
            "{active2}",
            $btn, "fa-solid fa-clock");
    }else{
        $btn=array();
        $btn[0]["js"] = "Loadjs('$page?enable-schedule=yes');";
        $btn[0]["name"] = "{enable}";
        $btn[0]["icon"] = ico_cd;
        $ArticaFschedule=$tpl->widget_grey("{scheduled_monitoring}",
            "{disabled}",
            $btn, "fa-solid fa-clock");
    }

    if($ArticaFSMon==0){
        $ArticaFschedule=$tpl->widget_grey("{scheduled_monitoring}",
            "{feature_not_installed}",
            null, "fa-solid fa-clock");
    }

    if($ARTICA_FSMON_DIRS>0){
        $MonitoredDirs = $tpl->widget_vert("{MONITORED_DIRS}",
            $ARTICA_FSMON_DIRS,
            null, "fa-solid fa-folder-magnifying-glass");
    }else{
        $MonitoredDirs = $tpl->widget_grey("{MONITORED_DIRS}",
            0,
            null, "fa-solid fa-folder-magnifying-glass");
    }


    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$WordpressProtection</td>";
    $html[]="<td style='width:33%;padding-left: 10px'>$ArticaFschedule</td>";
    $html[]="<td style='width:33%;padding-left: 10px'>$MonitoredDirs</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
}

function status2(){

	$tpl                    = new template_admin();
	$page                   = CurrentPageName();
    $ArticaFSMon           = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMon"));
	$tpl->CLUSTER_CLI=True;

    $reload="LoadAjaxSilent('table-loader-fsm-status','$page?status2=yes')";
    $jsrestart=$tpl->framework_buildjs("fsm.php?restart=yes",
        "fsm.progress",
        "fsm.log","progress-fsm-restart",$reload);


	if($ArticaFSMon==0) {
        $jsaction=$tpl->framework_buildjs("fsm.php?install=yes",
            "fsm.progress",
            "fsm.log","progress-fsm-restart",$reload);

        $btn_install = $tpl->button_autnonome("{install}",$jsaction,
            ico_cd, "AsSystemAdministrator", 335);

        $ServiceStatus=$tpl->widget_grey("{APP_ARTICAFSMON}","{not_installed}");
    }else{
        $status_file=PROGRESS_DIR."/APP_ARTICAFSMON.status";
        $ini                    = new Bs_IniHandler($status_file);
        $jsaction=$tpl->framework_buildjs("fsm.php?uninstall=yes",
            "fsm.progress",
            "fsm.log","progress-fsm-restart",$reload);


        $btn_install = $tpl->button_autnonome("{uninstall}",$jsaction,
            ico_cd, "AsSystemAdministrator", 335,"btn-danger");
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork('fsm.php?status=yes');
        $ServiceStatus=$tpl->SERVICE_STATUS($ini, "APP_ARTICAFSMON",$jsrestart);
    }
	
	$html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td valign='top' style='width:350px'>";
    $html[]="<center>";
    $html[]=$btn_install;
    $html[]="</center>";
    $html[]="<center style='margin-top:5px'>";
    $html[]=$ServiceStatus;
    $html[]="</center>";
    $html[]="</td>";
    $html[]="<td valign='top'>";
    $html[]="<div id='fsm-status-line'></div>";
    $html[]="</td>";

	$html[]="</tr></table>";
    $html[]="<script>LoadAjaxSilent('fsm-status-line','$page?fsm-status-line=yes');</script>";
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
function events_service_search(){
        $tpl        = new template_admin();
        $MAIN       = $tpl->format_search_protocol($_GET["search"],false,false,false,true);
        $line       = base64_encode(serialize($MAIN));
        $tfile      = PROGRESS_DIR."/fsm.events.syslog";
        $pat        = PROGRESS_DIR."/fsm.events.pattern";

        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("fsm.php?events=$line");
        $data=explode("\n",@file_get_contents($tfile));
        krsort($data);
        $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th style='width:1%' nowrap>{date}</th>
        	<th >{events}</th>
        </tr>
  	</thead>
	<tbody>
";


        foreach ($data as $line){

            if(!preg_match("#^(.+?)\s+(.+?)\s+(.+)#",$line,$re)){
                continue;
            }
            $class=null;
          $date=$re[1];
          $time=$re[2];
          $event=$re[3];
            $html[]="<tr>
				<td style='width:1%;' nowrap class='$class'>$date $time</td>
				<td style='width:99%' nowrap class='$class'>$event</td>
				</tr>";

        }

        $html[]="</tbody></table>";
        $html[]="<div><i>".@file_get_contents($pat)."</i></div>";
        $TINY_ARRAY["TITLE"]="{APP_ARTICAFSMON}: {service_events}";
        $TINY_ARRAY["ICO"]=ico_eye;
        $TINY_ARRAY["EXPL"]="{APP_ARTICAFSMON_EXPLAIN}";
        $TINY_ARRAY["URL"]="fsm";
        $TINY_ARRAY["BUTTONS"]=null;
        $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
        $html[]="<script>$jstiny</script>";

    echo $tpl->_ENGINE_parse_body($html);

}

function events_detected_search(){
    $tpl        = new template_admin();
    $MAIN       = $tpl->format_search_protocol($_GET["search"]);
    $line       = base64_encode(serialize($MAIN));
    $tfile      = PROGRESS_DIR."/fsm.threats.syslog";
    $pat        = PROGRESS_DIR."/fsm.threats.pattern";

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("fsm.php?threats=$line");
    $data=explode("\n",@file_get_contents($tfile));
    krsort($data);
    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th style='width:1%' nowrap>{date}</th>
        	<th style='width:1%' nowrap>PID</th>
        	<th style='width:1%' nowrap>{method}</th>
        	<th style='width:1%' nowrap>{rulename}</th>
        	<th >{path}</th>
        </tr>
  	</thead>
	<tbody>
";

    $COLORMETH["MODIFY"]="label-danger";
    $COLORMETH["CREATE"]="label-info";
    $COLORMETH["CHANGE"]="label-warning";
    $COLORMETH["REMOVE"]="label-info";

    foreach ($data as $line){

        if(!preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:\s+(.+)#",$line,$re)){
            continue;
        }

        $class=null;
        $Month=$re[1];
        $Day=$re[2];
        $time=$re[3];
        $pid=$re[4];
        $event=$re[5];

        preg_match("#\[(.+?)\]:\[(.+?)\](.+)#",$event,$re);
        $MOD=$re[1];
        $rule=$re[2];
        $pathname=$re[3];

        $method="<span class='label {$COLORMETH["$MOD"]}'>$MOD</span>";
        $html[]="<tr>
				<td style='width:1%;' nowrap class='$class'>$Month $Day $time</td>
				<td style='width:1%;' nowrap class='$class'>$pid</td>
				<td style='width:1%;' nowrap class='$class'>$method</td>
				<td style='width:1%;' nowrap class='$class'>$rule</td>
				<td class='$class'>$pathname</td>
				</tr>";

    }

    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents($pat)."</i></div>";
    $TINY_ARRAY["TITLE"]="{APP_ARTICAFSMON}: {detected}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_ARTICAFSMON_EXPLAIN}";
    $TINY_ARRAY["URL"]="fsm";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
}




function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{status}"]="$page?status=yes";
    $array["{rules}"]="$page?rules-start=yes";
	$array["{events}"]="$page?events-threats=yes";
    $array["{service_events}"]="$page?events-service=yes";
	echo $tpl->tabs_default($array);
	
}
function events_detected(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,null,null,null,"&threats-search=yes");
    echo "</div>";
}
function events_service(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,null,null,null,"&events-search=yes");
    echo "</div>";
}