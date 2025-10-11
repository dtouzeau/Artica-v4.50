<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["scan"])){scan_queue();exit;}
if(isset($_GET["flush"])){flush_queue();exit;}
if(isset($_GET["table-div"])){table_div();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["view-js"])){view_js();exit;}
if(isset($_GET["view-popup"])){view_popup();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["pause-queue-js"])){pause_queue_js();exit;}
if(isset($_GET["empty-queue-js"])){empty_queue_js();exit;}
if(isset($_POST["empty-queue-perform"])){empty_queue_perform();exit;}
if(isset($_POST["pause_queue"])){exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_POST["PostfixQueueEnabled"])){parameters_save();exit;}
page();

function parameters_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
   $tpl->js_dialog1("{parameters}","$page?parameters-popup=yes",890);

}
function scan_queue():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $instanceid=intval($_GET["scan"]);
    $function=$_GET["function"];
    $data=$sock->REST_API("/postfix/queue/scan/$instanceid");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        return $tpl->js_error("System error");
    }
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    $tpl->js_executed_background("");
    echo "$function();";
    return true;
}
function flush_queue():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $instanceid=intval($_GET["flush"]);
    $function=$_GET["function"];
    $data=$sock->REST_API("/postfix/queue/flush/$instanceid");
    $json = json_decode($data);
    if (json_last_error() > JSON_ERROR_NONE) {
        return $tpl->js_error("System error");
    }
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    $tpl->js_ok("{success}");
    echo "$function();";
    return true;
}

function parameters_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $PostfixQueueEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixQueueEnabled"));
    $PostfixQueueMaxMails=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixQueueMaxMails"));
    $master=new maincf_multi($instance_id);
    $in_flow_delay=$master->GET("in_flow_delay");
    $minimal_backoff_time=$master->GET("minimal_backoff_time");
    $maximal_backoff_time=$master->GET("maximal_backoff_time");
    $bounce_queue_lifetime=$master->GET("bounce_queue_lifetime");
    $maximal_queue_lifetime=$master->GET("maximal_queue_lifetime");
    $queue_run_delay=$master->GET("queue_run_delay");

    if($in_flow_delay==null){$in_flow_delay="1s";}
    if($minimal_backoff_time==null){$minimal_backoff_time="300s";}
    if($maximal_backoff_time==null){$maximal_backoff_time="4000s";}
    if($bounce_queue_lifetime==null){$bounce_queue_lifetime="5d";}
    if($maximal_queue_lifetime==null){$maximal_queue_lifetime="5d";}
    if($queue_run_delay==null){$queue_run_delay="300s";}

    if($PostfixQueueMaxMails==0){$PostfixQueueMaxMails=20;}


    $form[]=$tpl->field_hidden("instance_id",$instance_id);
    $form[]=$tpl->field_text("minimal_backoff_time","{minimal_backoff_time}",$minimal_backoff_time,true,"{minimal_backoff_time_text}");
    $form[]=$tpl->field_text("maximal_backoff_time","{maximal_backoff_time}",$maximal_backoff_time,true,"{maximal_backoff_time_text}");
    $form[]=$tpl->field_text("bounce_queue_lifetime","{bounce_queue_lifetime}",$bounce_queue_lifetime,true,"{bounce_queue_lifetime_text}");
    $form[]=$tpl->field_text("maximal_queue_lifetime","{maximal_queue_lifetime}",$maximal_queue_lifetime,true,"{maximal_queue_lifetime_text}");
    $form[]=$tpl->field_text("in_flow_delay","{in_flow_delay}",$in_flow_delay,true,"{in_flow_delay_text}");
    $form[]=$tpl->field_text("queue_run_delay","{queue_run_delay}",$queue_run_delay,true,"{queue_run_delay_text}");


    $form[]=$tpl->field_section("{watchdog}");
    $form[]=$tpl->field_checkbox("PostfixQueueEnabled","{PostfixQueueEnabled}",$PostfixQueueEnabled,"PostfixQueueMaxMails");
    $form[]=$tpl->field_numeric("PostfixQueueMaxMails","{PostfixQueueMaxMails}",$PostfixQueueMaxMails);
    echo $tpl->form_outside("{parameters}", $form,null,"{apply}","dialogInstance1.close();","AsPostfixAdministrator");


}

function parameters_save(){
   $tpl=new template_admin();
   $tpl->CLEAN_POST_XSS();
   $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostfixQueueEnabled",$_POST["PostfixQueueEnabled"]);
   $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostfixQueueMaxMails",$_POST["PostfixQueueMaxMails"]);
   $instance_id=intval($_POST["instance_id"]);
   $master=new maincf_multi($instance_id);
   $master->SAVE_POSTS();
   $GLOBALS["CLASS_SOCKETS"]->getFrameWork("postfix2.php?queue-params=yes&instance-id=$instance_id");
  }

function view_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=$_GET["view-js"];
    $instance_id=intval($_GET["instance-id"]);
	$tpl->js_dialog1("{message}: $id", "$page?view-popup=$id&instance-id=$instance_id");
	//postcat -vq XXXXXXXXXX > emailXXXXXXXXXX.txt
}

function view_popup(){
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
	$msg_id=$_GET["view-popup"];
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?postcat-q=$msg_id&instance-id=$instance_id");
	$textfile=PROGRESS_DIR."/postcat-$msg_id.txt";
	$textdata=@file_get_contents($textfile);
	echo "<textarea style='width:100%;min-height:450px;font-size:10px !important'>$textdata</textarea>";
}

function delete_js(){

	$tpl=new template_admin();
	$msgid=$_GET["delete-js"];
    $instance_id=intval($_GET["instance-id"]);
	$md=$_GET["md"];
	$tpl->js_confirm_delete("{message} $msgid", "delete", "$msgid||$instance_id","$('#$md').remove();");
	
}

function delete(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ff=explode("||",$_POST["delete"]);
	$msgid=$ff[0];
    $instance_id=$ff[1];
	if($msgid==null){echo "$msgid msgid is null;\n";return;}
	$sock=new sockets();
    $data=$sock->REST_API("/postfix/queue/delete/$instance_id/$msgid");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo json_last_error_msg();
        return false;
    }
    if (!$json->Status){
        echo $json->Error;
        return false;
    }
    return true;
}

function pause_queue_js(){
    $instance_id=intval($_GET["instance-id"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/postqueue";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/postqueue.txt";
    $ARRAY["CMD"]="cmd.php?postfix-freeze=yes&instance-id=$instance_id";
    $ARRAY["TITLE"]="{pause_the_queue}";
    $ARRAY["AFTER"]="LoadAjax('postfix-queue-div','$page?table=yes&instance-id=$instance_id');";
    $prgress=base64_encode(serialize($ARRAY));
    $pause_the_queue="Loadjs('fw.progress.php?content=$prgress&mainid=progress-postfix-queue')";
    $tpl->js_confirm_execute("{pause_the_queue_explain}","pause_queue","yes",$pause_the_queue);


}

function empty_queue_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $instance_id=intval($_GET["instance-id"]);
    $empty_queue_js="Loadjs('$page?empty-queue-perform=$instance_id')";
    $tpl->js_confirm_execute("{empty_queue_warning}","empty-queue-perform",$instance_id,$empty_queue_js);

}
function empty_queue_perform():bool{
    $instance_id=intval($_POST["empty-queue-perform"]);
    $sock=new sockets();
    $data=$sock->REST_API("/postfix/queue/empty/$instance_id");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo json_last_error_msg();
        return false;
    }
    if (!$json->Status){
        echo $json->Error;
        return false;
    }

    return admin_tracks("Empty the MTA Queue for instance $instance_id");
}



function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $instance_id=intval($_GET["instance-id"]);
    $html=$tpl->page_header("{queue} v$POSTFIX_VERSION",
        "fal fa-list-ul",
        "{postfix_queue_explain}",
        "$page?table-div=yes&instance-id=$instance_id",
        "postfix-queue-$instance_id",
        "progress-postfix-queue",false,
        "table-loader-postfix-queue"
    );


	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_POSTFIX} v$POSTFIX_VERSION",$html);
		echo $tpl->build_firewall();
		return;
	}

	
	echo $tpl->_ENGINE_parse_body($html);

}



function table_div(){
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page,null,null,null,"&table=yes&instance-id=$instance_id");

}


function table():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $function=$_GET["function"];


    $flush_the_queue="Loadjs('$page?flush=$instance_id&function=$function')";
    $scan_the_queue="Loadjs('$page?scan=$instance_id&function=$function')";
    $POSTFIX_QUEUENUM_TIME=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_QUEUENUM_TIME"));

    VERBOSE("POSTFIX_QUEUENUM_TIME=$POSTFIX_QUEUENUM_TIME",__LINE__);

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/postqueue";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/postqueue.txt";
    $ARRAY["CMD"]="cmd.php?postfix-freeze=yes&instance-id=$instance_id";
    $ARRAY["TITLE"]="{pause_the_queue}";
    $ARRAY["AFTER"]="LoadAjax('postfix-queue-div','$page?table=yes&instance-id=$instance_id');";
    $prgress=base64_encode(serialize($ARRAY));
    $pause_the_queue="Loadjs('fw.progress.php?content=$prgress&mainid=progress-postfix-queue')";




    $main=new maincf_multi($instance_id);
    $freeze_delivery_queue=intval($main->GET("freeze_delivery_queue"));
    if($freeze_delivery_queue==1) {$html[]="<div class='alert alert-danger'>{WARN_QUEUE_FREEZE}</div>";}


	$btn[]="<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"$flush_the_queue\">";
    $btn[]="<i class='fas fa-forward'></i> {flush_the_queue2} </label>";

	if($freeze_delivery_queue==0) {
        $btn[] = "<div class=\"btn-group\" data-toggle=\"buttons\" style=''>";
        $btn[] = "<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?pause-queue-js=yes&instance-id=$instance_id')\">";
        $btn[] = "<i class='fas fa-stop-circle'></i> {freeze_the_queue} </label>";
    }else{

        $btn[] = "<label class=\"btn btn btn-danger\" OnClick=\"$pause_the_queue\">";
        $btn[] = "<i class='fas fa-play-circle'></i> {unfreeze_the_queue} </label>";
    }



    $btn[] = "<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?empty-queue-js=yes&instance-id=$instance_id')\">";
    $btn[] = "<i class='fas fa-trash-alt'></i> {empty_queue} </label>";


    $btn[]="<label class=\"btn btn btn-info\" OnClick=\"$scan_the_queue;\">";
    $btn[]="<i class='fas fa-sync'></i> {actualize} </label>";

    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?parameters-js=yes&instance-id=$instance_id');\">";
    $btn[]="<i class='fas fa-tools'></i> {parameters} </label>";


    $btn[]="</div>";
	$html[]="<table id='table-postfix-queue' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	
	
	
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{arrival_time}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' nowrap>{queue}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{size}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{from}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{to}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{view2}</center></th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>{delete}</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$TRCLASS=null;

    $database="/home/artica/SQLITE/postqueue.db";
    if($instance_id>0){
        $database="/home/artica/SQLITE/postqueue.$instance_id.db";
    }

	$q=new lib_sqlite($database);

    $results=$q->QUERY_SQL("SELECT * FROM postqueuep ORDER BY arrival_time DESC LIMIT 0,250");
	if(!$q->ok){echo $tpl->FATAL_ERROR_SHOW_128($q->mysql_error);}
	
	$label["deferred"]="label-danger";
	$label["active"]="label-primary";
	$label["incoming"]="label-primary";
	$label["hold"]="label-warning";
	
	foreach ($results as $num=>$ligne){

		$queue_name=trim($ligne["queue_name"]);
		$queue_id=$ligne["queue_id"];
		$arrival_time=$ligne["arrival_time"];
		$arrival_time_int=strtotime($arrival_time);
		$distance=distanceOfTimeInWords($arrival_time_int,time(),true);
		$message_size=FormatBytes($ligne["message_size"]/1024);
		$sender=$ligne["sender"];
		$recipient=$ligne["recipient"];
		$delay_reason=$ligne["delay_reason"];	
		$id=md5(serialize($ligne));
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}	
		$html[]="<tr class='$TRCLASS' id='$id'>";
		$html[]="<td style='width:1%' nowrap>$arrival_time <small>($distance)</small></td>";
		$html[]="<td style='width:1%' nowrap><span class='label {$label[$queue_name]}'>$queue_name</span></td>";
		$html[]="<td style='width:1%' nowrap>$message_size</td>";
		$html[]="<td style='width:99%'><strong>$sender</strong><br><small>$delay_reason</small></td>";
		$html[]="<td style='width:1%' nowrap><strong>$recipient</strong></td>";

		$html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_loupe(true,"Loadjs('$page?view-js=$queue_id&instance-id=$instance_id')",null,"AsPostfixAdministrator")."</center></td>";
		$html[]="<td style='vertical-align:middle;width:1%' class='center'>".$tpl->icon_delete("Loadjs('$page?delete-js=$queue_id&md=$id&instance-id=$instance_id')","AsPostfixAdministrator")."</center></td>";
		$html[]="</tr>";
	
	
	}
	
	$html[]="</tbody>";
	$html[]="<tfoot>";
	
	$html[]="<tr>";
	$html[]="<td colspan='7'>";
	$html[]="<ul class='pagination pull-right'></ul>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</tfoot>";
	$html[]="</table>";

    $instancename="SMTP Master";
    if($instance_id>0){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $ligne=$q->mysqli_fetch_array("SELECT instancename from postfix_instances WHERE id='$instance_id'");
        $instancename="&nbsp;<small>({$ligne["instancename"]})</small>";

        $POSTFIX_QUEUENUM_TIME = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO(sprintf("POSTFIX_QUEUENUM_TIME_%s",$instance_id)));
    }

    $lastscan="";
    if ($POSTFIX_QUEUENUM_TIME > 0) {
            $lastscan = "<br><i>{last_scan}:" . $tpl->time_to_date($POSTFIX_QUEUENUM_TIME, true)."</i>";
        }

    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $TINY_ARRAY["TITLE"]="{queue} $instancename v$POSTFIX_VERSION";
    $TINY_ARRAY["ICO"]="fal fa-list-ul";
    $TINY_ARRAY["EXPL"]="{postfix_queue_explain}$lastscan";
    $TINY_ARRAY["URL"]="postfix-queue-$instance_id";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btn);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";




    $html[]="
	<script>
	$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-postfix-queue').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}


