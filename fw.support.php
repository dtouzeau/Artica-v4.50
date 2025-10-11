<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.support-tracker.inc');
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["none"])){exit();}
if(isset($_POST["EnableDNSRootInts"])){save();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["recover-js"])){recover_js();exit;}
if(isset($_GET["unlink-js"])){unlink_js();exit;}

if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["recover"])){recover();exit;}
if(isset($_POST["supportEmail"])){parameters_save();exit;}
if(isset($_POST["supportEmail1"])){recover_save();exit;}
if(isset($_GET["create-js"])){create_ticket_js();exit;}
if(isset($_GET["create-popup"])){create_ticket_popup();exit;}
if(isset($_POST["new-ticket"])){create_ticket_save();exit;}
if(isset($_GET["delete-bug-js"])){delete_bug_js();exit;}
if(isset($_GET["show-js"])){bug_js();exit;}
if(isset($_GET["show-popup"])){bug_popup();exit;}
if(isset($_GET["show-comment-js"])){comment_js();exit;}
if(isset($_GET["show-comment-popup"])){comment_popup();exit;}
if(isset($_GET["reply-js"])){reply_js();exit;}
if(isset($_GET["reply-popup"])){reply_popup();exit;}
if(isset($_POST["reply"])){reply_save();exit;}
//if(isset($_GET["spt-js"])){support_tool_js();exit;}
if(isset($_GET["timeline"])){bug_timeline();exit;}


if(isset($_GET["tabs"])){tabs();exit;}
page();
function page()
{
    $page = CurrentPageName();
    $tpl = new template_admin();

    $html = $tpl->page_header("{technical_support}",
        ico_support, "{technical_support_explain}", "$page?table=yes", "support", "progress-support-restart", false, "table-loader-support");


    if (isset($_GET["main-page"])) {
        $tpl = new template_admin(null, $html);
        echo $tpl->build_firewall("{snapshots}");
        return true;
    }

    $tpl = new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function reply_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $bugid=intval($_GET["reply-js"]);

    $q=new lib_sqlite("/home/artica/SQLITE/support.db");
    $ligne=$q->mysqli_fetch_array("SELECT id,number,subject FROM `tickets` WHERE id='$bugid'");
    $id_temp=intval($ligne["id"]);
    if($id_temp==0){
        $tpl->js_error("{already_removed}");
        return;
    }
    $number=$ligne['number'];
    $subject=$ligne["subject"];
    $tpl->js_dialog2("#$number - $subject", "$page?reply-popup=$bugid");

}

function reply_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $bugid=intval($_GET["reply-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/support.db");
    $ligne=$q->mysqli_fetch_array("SELECT id,subject FROM `tickets` WHERE id='$bugid'");
    $subject=$ligne["subject"];

    $form[]=$tpl->field_hidden("reply", $bugid);
    $form[]=$tpl->field_checkbox("SupportTool", "{attach_support_tool}", 0);
    $form[]=$tpl->field_textareacode("replycontent", null, "");
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="support.php?reply=yes";
    $ARRAY["TITLE"]="{reply_ticket}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $sync="Loadjs('fw.progress.php?content=$prgress&mainid=progress-support-restart')";

    echo $tpl->form_outside($subject, $form,null,"{reply}","dialogInstance2.close();dialogInstance1.close();$sync;","AsSystemAdministrator");

}

function reply_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    if(strlen($_POST["replycontent"])>65535){
        $tpl->_ENGINE_parse_body("{explain} Exceed 65535 {characters}");
        return;
    }

    if(!isset($_POST["replycontent"])){
        $tpl->_ENGINE_parse_body("{explain} comment missing");
        return;
    }


    if(empty($_POST["replycontent"])){
        $tpl->_ENGINE_parse_body("{explain} comment empty");
        return;
    }



    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportReply",base64_encode(serialize($_POST)));

}

function comment_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["show-comment-js"]);
    $bugid=intval($_GET["bugid"]);
    $q=new lib_sqlite("/home/artica/SQLITE/support.db");
    $ligne=$q->mysqli_fetch_array("SELECT id,number,subject FROM `tickets` WHERE id='$bugid'");
    $id_temp=intval($ligne["id"]);
    if($id_temp==0){
        $tpl->js_error("{already_removed}");
        return;
    }
    $number=$ligne["number"];
    $subject=$ligne["subject"];
    $tpl->js_dialog2("#$number - $subject", "$page?show-comment-popup=$id");

}
function comment_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["show-comment-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/support.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM `threads` WHERE id='$id'");
    $content=$ligne["body"];
    $content=preg_replace("/[\r\n]+/", "\n", $content);
    $content=trim(preg_replace("/^(<br \/>)/", "",  $content, 1));
    $content=nl2br($content);
    $creator=$ligne["createdBy"];
    $time=$ligne["createdAt"];
    echo "<div class=\"social-feed-box\">
       			<div class=\"social-avatar\">
	                <a class=\"pull-left\" href=\"\"></a>
	                <div class=\"media-body\">
	                	<a href=\"#\">$creator</a>
	                    <small class=\"text-muted\">".$tpl->time_to_date($time,true)."</small>
	                </div>
                 </div>
                            <div class=\"social-body\">
                                <p>
                                    {$content}
                                </p>
                            </div>
                            <div class=\"social-footer\"></div>

                        </div>";



}

function bug_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["show-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/support.db");
    $ligne=$q->mysqli_fetch_array("SELECT id,number,subject FROM `tickets` WHERE id='$id'");
    $id_temp=intval($ligne["id"]);
    if($id_temp==0){
        $tpl->js_error("{already_removed}");
        return;
    }
    $subject=$ligne["subject"];
    $number=$ligne["number"];

    $tpl->js_dialog1("#$number - $subject", "$page?show-popup=$id");
}

function bug_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $bugid=intval($_GET["show-popup"]);
    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
    $html[]="<label class=\"btn btn btn-primary \" 
	OnClick=\"Loadjs('$page?reply-js=$bugid');\"><i class='fas fa-reply'></i> {reply} </label>";
    $html[]="</div>";
    $html[]="<div id='support-tool-progress'></div>";
    $html[]="<div id='bug-time-line'></div>";
    $html[]="<script>LoadAjax('bug-time-line','$page?timeline=$bugid');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function bug_timeline(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $bugid=intval($_GET["timeline"]);
    $q=new lib_sqlite("/home/artica/SQLITE/support.db");

    $html[]="<div id=\"vertical-timeline\" class=\"vertical-container dark-timeline\">";

    $results=$q->QUERY_SQL("SELECT * FROM threads  WHERE convertation_id=$bugid ORDER BY `createdAt` ASC");
    //


    foreach ($results as $index=>$ligne){
        $creator=$ligne["createdBy"];
        $bg="navy-bg";
        $bt="btn-primary";

        if($ligne["type"]=='customer'){
            $bg="yellow-bg";
            $bt="btn-warning";
        }

        $btattch=null;
        $id=$ligne["id"];
        $content=$ligne["body"];
        if(strlen($content)>256){$content=substr($content,0,256)."...";}
//        $breaks = array("<br />","<br>","<br/>","<br />");
//        $content = str_replace("<br />", "\n", $content);
        $content=preg_replace("/[\r\n]+/", "\n", $content);
        $content=trim(preg_replace("/^(<br \/>)/", "",  $content, 1));
        $content=nl2br($content);

        $btattch="";
        $attachments=json_decode($ligne['attachments']);

        foreach ($attachments as $index=>$val){
            $btattch.="<a href=\"$val\" 
			class=\"btn btn-sm $bt\" style='margin:2px' target=\"_NEW\"><i class='fas fa-paperclip'></i> {attachment}</a>";
        }


        $time=$ligne["createdAt"];
        $html[]="<div class=\"vertical-timeline-block\">";
        $html[]="	<div class=\"vertical-timeline-icon $bg\">";
        $html[]="		<i class=\"fas fa-user\"></i>";
        $html[]="	 </div>";
        $html[]="	 <div class=\"vertical-timeline-content\">";
        $html[]="	 	<p>$content</p>";
        $html[]="	 	<a href=\"#\" OnClick=\"Loadjs('$page?show-comment-js=$id&bugid=$bugid');\" 
						class=\"btn btn-sm $bt\" style='margin:2px'><i class='fas fa-search'></i> {view2}</a>$btattch";
        $html[]="	  	<span class=\"vertical-date\">";
        $html[]="	 	 	$creator<br><small>".$tpl->time_to_date($time,true)."</small>" ;
        $html[]="	  	</span>";
        $html[]="	 </div>";
        $html[]="</div>";

    }
    $html[]="</div>";

    echo $tpl->_ENGINE_parse_body($html);

}

function delete_bug_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $id=intval($_GET["delete-bug-js"]);
    $md=$_GET["md"];
    $q=new lib_sqlite("/home/artica/SQLITE/support.db");
    $ligne=$q->mysqli_fetch_array("SELECT id,number,subject FROM `tickets` WHERE id='$id'");
$number=$ligne["number"];
    $id_temp=intval($ligne["id"]);
    if($id_temp==0){
        $tpl->js_error("{already_removed}");
        echo "$('#$md').remove();\n";
        return;
    }

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="support.php?delete-bug=$id";
    $ARRAY["TITLE"]="{delete} ticket #$number";
    $ARRAY["AFTER"]="$('#$md').remove();";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-support-restart')";


    $tpl->js_confirm_delete("#$number - {$ligne["subject"]}", "none", $ligne["subject"],$jsrestart);

}

function create_ticket_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SupportTracker=new SupportTracker();
    $tpl->js_dialog1("{technical_support} $SupportTracker->supportEmail {new_ticket}", "$page?create-popup=yes");

}
function create_ticket_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $DEF=unserialize(base64_decode(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportCreateBug"))));


    $PRIORITY["1"]="System Down";
    $PRIORITY["2"]="Critical";
    $PRIORITY["3"]="Urgent";
    $PRIORITY["4"]="Important";
    $PRIORITY["5"]="Low";
    $PRIORITY["6"]="Monitor";
    $PRIORITY["7"]="Informational";

    $form[]=$tpl->field_hidden("new-ticket", 1);
    $form[]=$tpl->field_array_hash($PRIORITY, "priority", "{priority}", $DEF["priority"],true);
    $form[]=$tpl->field_text("subject", "{subject}", $DEF["subject"],true);
    $form[]=$tpl->field_checkbox("SupportTool", "{attach_support_tool}", $DEF["SupportTool"]);

    $form[]=$tpl->field_section("{explain}");

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="support.php?create-bug=yes";
    $ARRAY["TITLE"]="{new_ticket}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $sync="Loadjs('fw.progress.php?content=$prgress&mainid=progress-support-restart')";

    $form[]=$tpl->field_textareacode("comment", "{comment}", $DEF["comment"]);
    echo $tpl->form_outside("{new_ticket}", $form,null,"{add}","dialogInstance1.close();$sync;","AsSystemAdministrator");

}

function create_ticket_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    if(strlen($_POST["comment"])>65535){
        $tpl->_ENGINE_parse_body("{explain} Exceed 65535 {characters}");
        return;
    }

    if(!isset($_POST["comment"])){
        $tpl->_ENGINE_parse_body("{explain} comment missing");
        return;
    }


    if(empty($_POST["comment"])){
        $tpl->_ENGINE_parse_body("{explain} comment empty");
        return;
    }

    if(!isset($_POST["subject"])){
        $tpl->_ENGINE_parse_body("{explain} subject missing");
        return;
    }


    if(empty($_POST["subject"])){
        $tpl->_ENGINE_parse_body("{explain} subject empty");
        return;
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("supportCreateBug",base64_encode(serialize($_POST)));


}

function unlink_js()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="support.php?delete-account=yes";
    $ARRAY["TITLE"]="{delete} {account}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));


    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-support-restart')";




    $tpl->js_confirm_delete("{delete} {account}", "none", "",$jsrestart);
}
function recover_js()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    //$SupportTracker=new SupportTracker();
    $tpl->js_dialog1("{technical_support} {reset} {account}", "$page?recover=yes");
}

function parameters_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SupportTracker=new SupportTracker();
    $tpl->js_dialog1("{technical_support} $SupportTracker->supportEmail", "$page?parameters=yes");
}

function recover()
{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $supportProxyName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyName"));
    $supportProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyPort"));
    $supportUseProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportUseProxy"));
    $SupportTracker=new SupportTracker();
    $supportName=$SupportTracker->supportName;
    $supportEmail=$SupportTracker->supportEmail;
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED"))==1){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1){
            if($supportProxyName==null){$supportProxyName="127.0.0.1";}
            if($supportProxyName=="127.0.0.1"){$supportProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));}
        }
    }
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="support.php?recover-account=yes";
    $ARRAY["TITLE"]="{member}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="dialogInstance1.close();Loadjs('fw.progress.php?content=$prgress&mainid=progress-support-restart')";
    $form[]=$tpl->field_hidden("supportEmail1","");
    $form[]=$tpl->field_text("supportName", "{name}", $supportName,true);
    $form[]=$tpl->field_text("supportEmail", "{email}", $supportEmail,true);
    $form[]=$tpl->field_checkbox("supportUseProxy","{UseProxyServer}",$supportUseProxy,"supportProxyName,supportProxyPort");
    $form[]=$tpl->field_text("supportProxyName", "{proxyname}", $supportProxyName);
    $form[]=$tpl->field_numeric("supportProxyPort", "{listen_port}", $supportProxyPort);
    echo $tpl->form_outside("{technical_support} {reset} {account}", $form,null,"{apply}",$jsafter,"AsSystemAdministrator");

}


function parameters(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $supportProxyName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyName"));
    $supportProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportProxyPort"));
    $supportUseProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportUseProxy"));

    $SupportTracker=new SupportTracker();
    $supportName=$SupportTracker->supportName;
    $supportEmail=$SupportTracker->supportEmail;
    $supportSecCode=$SupportTracker->supportSecCode;


    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED"))==1){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1){
            if($supportProxyName==null){$supportProxyName="127.0.0.1";}
            if($supportProxyName=="127.0.0.1"){$supportProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));}
        }
    }




    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="support.php?check-account=yes";
    $ARRAY["TITLE"]="{member}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsafter="dialogInstance1.close();Loadjs('fw.progress.php?content=$prgress&mainid=progress-support-restart')";

    $form[]=$tpl->field_text("supportEmail", "{email}", $supportEmail,true);
    $form[]=$tpl->field_text("supportName", "{name}", $supportName,true);
    $form[]=$tpl->field_password("supportSecCode", "{SecCode}", $supportSecCode);
    $form[]=$tpl->field_checkbox("supportUseProxy","{UseProxyServer}",$supportUseProxy,"supportProxyName,supportProxyPort");
    $form[]=$tpl->field_text("supportProxyName", "{proxyname}", $supportProxyName);
    $form[]=$tpl->field_numeric("supportProxyPort", "{listen_port}", $supportProxyPort);
    echo $tpl->form_outside("{technical_support} {parameters}", $form,null,"{apply}",$jsafter,"AsSystemAdministrator");
}

function table(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $supportID=intval(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("supportMID")));

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/support.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/support.progress.txt";
    $ARRAY["CMD"]="support.php?sync=yes";
    $ARRAY["TITLE"]="{synchronize}";
    $ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $sync="Loadjs('fw.progress.php?content=$prgress&mainid=progress-support-restart')";


    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
    if($supportID>0) {
        $topbuttons[] = array("Loadjs('$page?create-js=yes');", ico_plus, "{new_ticket}");
    }




    if($supportID>0) {
        $topbuttons[] = array($sync, ico_plus, "{synchronize_tickets}");
    }
    $topbuttons[] = array("Loadjs('$page?parameters-js=yes');", ico_plus, "{parameters}");
    $topbuttons[] = array("Loadjs('$page?recover-js=yes');", ico_keys, "{reset} {account}");
    $topbuttons[] = array("Loadjs('$page?unlink-js=yes');", ico_trash, "{remove} {account}");
    $jshelp="s_PopUpFull('https://wiki.articatech.com/en/maintenance/link-support-portal',1024,768,'Stress Tool')";


    $topbuttons[] = array($jshelp, ico_support, "{online_help}");
    $TINY_ARRAY["TITLE"]="{technical_support}";
    $TINY_ARRAY["ICO"]=ico_support;
    $TINY_ARRAY["EXPL"]="{technical_support_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";




    $html[]="</div>";

    if($supportID==0){
        $html[]=$tpl->FATAL_ERROR_SHOW_128("{error_support_need_registration}");
        echo $tpl->_ENGINE_parse_body($html);
        echo "<script>$jstiny</script>";
        return;
    }



    $html[]="<table id='table-support' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'  width=1% nowrap>{ticket_n}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{priority}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' >{subject}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{created}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{updated}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{assigned_to}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{reply}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'  width=1% nowrap><center>Del.</center></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $results=array();
    if(is_file("/home/artica/SQLITE/support.db")){
        $q=new lib_sqlite("/home/artica/SQLITE/support.db");
        $results=$q->QUERY_SQL("SELECT * FROM `tickets` ORDER BY updatedAt DESC");
        if(!$q->ok){echo $q->mysql_error_html();}
    }

    $STATUSS["closed"]="-info";
    $STATUSS["pending"]="-info";
    $STATUSS["active"]="-info";
    $STATUSS["deleted"]="-info";

    $prioritys["System Down"]="-danger";
    $prioritys["Critical"]="-danger";
    $prioritys["Urgent"]="-warning";
    $prioritys["Important"]="-info";
    $prioritys["Low"]="-primary";
    $prioritys["Monitor"]="-success";
    $prioritys["Informational"]="-success success-light";

    $width1="style='width:1%' nowrap";

    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $id=$ligne["id"];
        $subject=$ligne["subject"];
        $status=$ligne["status"];
        $number="#".$ligne["number"];
        $state=$ligne["state"];
        $customFields=$ligne["customFields"];
        $createdAt=$tpl->time_to_date($ligne["createdAt"],true);
        $updatedAt=$tpl->time_to_date($ligne["updatedAt"],true);
        $tags=json_decode($ligne["tags"]);

        $tag="";
        foreach ($tags as $indexTag=>$val){
            $tag.="<span><i class='fas fa-tags' id='indextag$index-$indexTag'></i>&nbsp;$val</span>&nbsp;&nbsp;";
        }


        $createdBy=$ligne["createdBy"];
        $assignee=$ligne["assignee"];
        if(is_null($assignee) OR strlen($assignee)<2){
            $assignee="{not_defined}";
        }
        $mkey=md5(serialize($ligne));
        $n_threads="<span class='badge badge-info badge-light '><i class='fas fa-envelope'></i>&nbsp;{$ligne["threads"]}</span>";
        $view="Loadjs('$page?show-js=$id')";
        $html[]="<tr class=$TRCLASS id='$mkey'>";
        $html[]="<td $width1>".$tpl->td_href($number,"{view}",$view)."</td>";
        $html[]="<td $width1><span class='label label{$STATUSS[$status]}'>$status</span><span class='label }'>$state</span></td>";
        $html[]="<td $width1><span class='label label{$prioritys[$customFields]}'>$customFields</span></td>";
        $html[]="<td>".$tpl->td_href($subject,"{view}",$view)."&nbsp;$n_threads<br>$tag</td>";
        $html[]="<td $width1>".$tpl->td_href($createdAt,"{view}",$view)."&nbsp;(<span class='muted'>$createdBy</span>) </td>";
        $html[]="<td $width1>".$tpl->td_href($updatedAt,"{view}",$view)."</td>";
        $html[]="<td $width1>".$tpl->td_href($assignee,"{view}",$view)."</td>";
        $html[]="<td $width1>". $tpl->icon_reply("Loadjs('$page?reply-js=$id')","AsSystemAdministrator"). "</td>";
        $html[]="<td $width1>". $tpl->icon_delete("Loadjs('$page?delete-bug-js=$id&md=$mkey')","AsSystemAdministrator"). "</td>";
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
    $html[]="
<script>
$jstiny
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-support').footable( { \"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });
</script>";



    echo $tpl->_ENGINE_parse_body($html);

}
function recover_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_POST['supportEmail']=base64_encode($_POST['supportEmail']);
    $_POST['supportName']=base64_encode($_POST['supportName']);
    $tpl->SAVE_POSTs();
}
function parameters_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_POST['supportEmail']=base64_encode($_POST['supportEmail']);
    $_POST['supportName']=base64_encode($_POST['supportName']);
    $_POST['supportSecCode']=base64_encode($_POST['supportSecCode']);
    $tpl->SAVE_POSTs();

}

