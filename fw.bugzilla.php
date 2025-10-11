<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["none"])){exit();}
if(isset($_POST["EnableDNSRootInts"])){save();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_POST["BugzillaAccount"])){parameters_save();exit;}
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
if(isset($_GET["spt-js"])){support_tool_js();exit;}
if(isset($_GET["timeline"])){bug_timeline();exit;}


if(isset($_GET["tabs"])){tabs();exit;}
page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	

	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{technical_support}</h1>
	<p>{technical_support_explain}</p>
	</div>

	</div>



	<div class='row'><div id='progress-bugzilla-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-support'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/support');
	LoadAjax('table-loader-support','$page?table=yes');

	</script>";

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}

function support_tool_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$bugid=intval($_GET["spt-js"]);
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.debug.support-tool.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.debug.support-tool.progress.txt";
	$ARRAY["CMD"]="bugzilla.php?support-tool=$bugid";
	$ARRAY["TITLE"]="{support_package}";
	$ARRAY["AFTER"]="LoadAjax('bug-time-line','$page?timeline=$bugid');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=support-tool-progress')";
	$tpl->js_confirm_execute("{upload_support_tool_ask}", "none", "none",$jsrestart);
}

function reply_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$bugid=intval($_GET["reply-js"]);
	
	$q=new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
	$ligne=$q->mysqli_fetch_array("SELECT id,summary FROM `bugs` WHERE id='$bugid'");
	$id_temp=intval($ligne["id"]);
	if($id_temp==0){
		$tpl->js_error("{already_removed}");
		return;
	}
	$summary=$ligne["summary"];
	$tpl->js_dialog2("($bugid) $summary", "$page?reply-popup=$bugid");
	
}

function reply_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$bugid=intval($_GET["reply-popup"]);
	$q=new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
	$ligne=$q->mysqli_fetch_array("SELECT id,summary FROM `bugs` WHERE id='$bugid'");
	$summary=$ligne["summary"];
	
	$form[]=$tpl->field_hidden("reply", $bugid);
	$form[]=$tpl->field_textareacode("replycontent", null, "Reply:\n\n");
	echo $tpl->form_outside($summary, $form,null,"{reply}","dialogInstance2.close();","AsSystemAdministrator");
}

function reply_save(){
	$id=time();
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$bugid=intval($_POST["reply"]);
	$q=new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
	$BugzillaAccount=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaAccount"));
	$text=str_replace("'", "''", $_POST["replycontent"]);
	$sql="INSERT OR IGNORE INTO discuss (`bugid`,`id`,`creator`,`time`,`content`,`attachment_id`) 
	VALUES ('$bugid','$id','$BugzillaAccount','$id','$text','0')";
	$q->QUERY_SQL($sql);
	
	$MAIN["bug"]=$bugid;
	$MAIN["CONTENT"]=$_POST["replycontent"];
	$sock=new sockets();
	$sock->SaveConfigFile(serialize($MAIN), "BugzillaReply");
	$sock->getFrameWork("bugzilla.php?reply=yes");
	
}

function comment_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=intval($_GET["show-comment-js"]);
	$bugid=intval($_GET["bugid"]);
	$q=new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
	$ligne=$q->mysqli_fetch_array("SELECT id,summary FROM `bugs` WHERE id='$bugid'");
	$id_temp=intval($ligne["id"]);
	if($id_temp==0){
		$tpl->js_error("{already_removed}");
		return;
	}
	$summary=$ligne["summary"];
	$tpl->js_dialog2("$summary", "$page?show-comment-popup=$id");
	
}
function comment_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=intval($_GET["show-comment-popup"]);
	$q=new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
	$ligne=$q->mysqli_fetch_array("SELECT * FROM `discuss` WHERE id='$id'");
	$ligne['content']=htmlspecialchars($ligne['content']);
	$ligne['content']=nl2br($ligne["content"]);
	$creator=$ligne["creator"];
	$time=$ligne["time"];
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
                                    {$ligne['content']}
                                </p>
                            </div>
                            <div class=\"social-footer\"></div>

                        </div>";

	
	
}

function bug_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$id=intval($_GET["show-js"]);
	$q=new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
	$ligne=$q->mysqli_fetch_array("SELECT id,summary FROM `bugs` WHERE id='$id'");
	$id_temp=intval($ligne["id"]);
	if($id_temp==0){
		$tpl->js_error("{already_removed}");
		return;
	}
	$summary=$ligne["summary"];
	$tpl->js_dialog1("($id) $summary", "$page?show-popup=$id");
}

function bug_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$BugzillaAccount=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaAccount"));
	$bugid=intval($_GET["show-popup"]);
	$q=new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
	$html[]="<label class=\"btn btn btn-primary \" 
	OnClick=\"Loadjs('$page?reply-js=$bugid');\"><i class='fas fa-reply'></i> {reply} </label>";
	
	$html[]="<label class=\"btn btn btn-warning \"
	OnClick=\"Loadjs('$page?spt-js=$bugid');\"><i class='fas fa-cloud-upload'></i> Support Tool </label>";
	
	
	
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
	$BugzillaAccount=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaAccount"));
	$bugid=intval($_GET["timeline"]);
	$q=new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
	
	$html[]="<div id=\"vertical-timeline\" class=\"vertical-container dark-timeline\">";
	
	$results=$q->QUERY_SQL("SELECT * FROM discuss  WHERE bugid=$bugid ORDER BY `time` DESC");
	//
	
	
	foreach ($results as $index=>$ligne){
		$creator=$ligne["creator"];
		$bg="navy-bg";
		$bt="btn-primary";
		
		if($creator==$BugzillaAccount){
			$bg="yellow-bg";
			$bt="btn-warning";
		}
		
		$btattch=null;
		$id=$ligne["id"];
		$content=$ligne["content"];
		if(strlen($content)>256){$content=substr($content,0,256)."...";}
		$content=nl2br($content);
		$attachment_id=$ligne["attachment_id"];
		if($attachment_id>0){
			$btattch="<a href=\"https://bugs.articatech.com/attachment.cgi?id=$attachment_id\" 
			class=\"btn btn-sm $bt\" style='margin:2px'><i class='fas fa-paperclip'></i> {attachment}</a>";
		}
		
		$time=$ligne["time"];
		$html[]="<div class=\"vertical-timeline-block\">";
		$html[]="	<div class=\"vertical-timeline-icon $bg\">";
		$html[]="		<i class=\"fas fa-user\"></i>";
		$html[]="	 </div>";
		$html[]="	 <div class=\"vertical-timeline-content\">";
		$html[]="	 	<p>{info}: $content</p>";
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
	$q=new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
	$ligne=$q->mysqli_fetch_array("SELECT id,summary FROM `bugs` WHERE id='$id'");
	
	$id_temp=intval($ligne["id"]);
	if($id_temp==0){
		$tpl->js_error("{already_removed}");
		echo "$('#$md').remove();\n";
		return;
	}
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/bugzilla.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/bugzilla.progress.txt";
	$ARRAY["CMD"]="bugzilla.php?delete-bug=$id";
	$ARRAY["TITLE"]="{delete} $id";
	$ARRAY["AFTER"]="$('#$md').remove();";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-bugzilla-restart')";
	
	
	$tpl->js_confirm_delete($ligne["summary"], "none", $ligne["summary"],$jsrestart);
	
}

function create_ticket_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$BugzillaAccount=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaAccount"));
	$tpl->js_dialog1("{technical_support} $BugzillaAccount {new_ticket}", "$page?create-popup=yes");	
	
}
function create_ticket_popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();

	$DEF=unserialize(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaCreateBug")));
	
	
	$COMPONENTS["Feature requests"]="{feature_requests}";
	$COMPONENTS["Installation"]="{installation}";
	$COMPONENTS["Messaging"]="{messaging}";
	$COMPONENTS["Network"]="{network}";
	$COMPONENTS["Proxy"]="{APP_SQUID}";
	$COMPONENTS["Statistics"]="{statistics}";
	$COMPONENTS["System"]="{your_system}";
	$COMPONENTS["Update"]="{update2}";
	$COMPONENTS["License"]="{license2}";

	$form[]=$tpl->field_hidden("new-ticket", 1);
	$form[]=$tpl->field_array_hash($COMPONENTS, "component", "{component}", $DEF["component"],true);
	$form[]=$tpl->field_text("summary", "{summary}", $DEF["summary"],true);
	$form[]=$tpl->field_checkbox("SupportTool", "{attach_support_tool}", $DEF["SupportTool"]);
	
	$form[]=$tpl->field_section("{explain}");
	
	if($DEF["comment"]==null){$DEF["comment"]="Set here your question about the product\n\n";}
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/bugzilla.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/bugzilla.progress.txt";
	$ARRAY["CMD"]="bugzilla.php?create-bug=yes";
	$ARRAY["TITLE"]="{new_ticket}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$sync="Loadjs('fw.progress.php?content=$prgress&mainid=progress-bugzilla-restart')";
	
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
	

	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("BugzillaCreateBug",base64_encode(serialize($_POST)));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("BugzillaCreateBug",base64_encode(serialize($_POST)));


}



function parameters_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
	$WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
	if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	$BugzillaAccount=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaAccount"));
	if($BugzillaAccount==null){$BugzillaAccount=$LicenseInfos["EMAIL"];}
	$tpl->js_dialog1("{technical_support} $BugzillaAccount", "$page?parameters=yes");
}

function parameters(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
	$WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
	if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	$BugzillaPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaPassword"));
	$BugzillaProxyName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaProxyName"));
	$BugzillaProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaProxyPort"));
	$BugzillaUseProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaUseProxy"));
	$BugzillaName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaName"));
	$BugzillaAccount=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaAccount"));
	$BugzillaApikey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaApikey"));
	if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_INSTALLED"))==1){
		if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1){
			if($BugzillaProxyName==null){$BugzillaProxyName="127.0.0.1";}
			if($BugzillaProxyName=="127.0.0.1"){$BugzillaProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));}
		}
	}
	$BugzillaUseProxyPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/bugzilla.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/bugzilla.progress.txt";
	$ARRAY["CMD"]="bugzilla.php?check-account=yes";
	$ARRAY["TITLE"]="{member}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');LoadAjaxSilent('top-barr','fw-top-bar.php');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsafter="dialogInstance1.close();Loadjs('fw.progress.php?content=$prgress&mainid=progress-bugzilla-restart')";

	if($BugzillaAccount==null){$BugzillaAccount=$LicenseInfos["EMAIL"];}

	$form[]=$tpl->field_text("BugzillaAccount", "{account}", $LicenseInfos["EMAIL"]);
	$form[]=$tpl->field_text("BugzillaName", "{name}", $BugzillaName);
	$form[]=$tpl->field_text("BugzillaApikey", "API KEY", $BugzillaApikey,false,"{BUGZILLAPI}");
	$form[]=$tpl->field_password2("BugzillaPassword", "{password}", $BugzillaPassword);
	$form[]=$tpl->field_checkbox("BugzillaUseProxy","{UseProxyServer}",$BugzillaUseProxy,"BugzillaProxyName,BugzillaProxyPort");
	$form[]=$tpl->field_text("BugzillaProxyName", "{proxyname}", $BugzillaProxyName);
	$form[]=$tpl->field_numeric("BugzillaProxyPort", "{listen_port}", $BugzillaProxyPort);
	echo $tpl->form_outside("{technical_support} {parameters}", $form,null,"{apply}",$jsafter,"AsSystemAdministrator");
}

function table(){

	$page=CurrentPageName();
	$tpl=new template_admin();
	$BugzillaID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaID"));
	$BugzillaPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaPassword"));
	$BugzillaApikey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BugzillaApikey"));
	
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/bugzilla.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/bugzilla.progress.txt";
	$ARRAY["CMD"]="bugzilla.php?sync=yes";
	$ARRAY["TITLE"]="{synchronize}";
	$ARRAY["AFTER"]="LoadAjax('table-loader-support','$page?table=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$sync="Loadjs('fw.progress.php?content=$prgress&mainid=progress-bugzilla-restart')";
	
	
	$html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:20px'>";
	if($BugzillaID>0){$html[]="<label class=\"btn btn btn-primary \" OnClick=\"Loadjs('$page?create-js=yes');\"><i class='fas fa-plus'></i> {new_ticket} </label>";}
	$html[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?parameters-js=yes');\"><i class='fas fa-cogs'></i> {parameters} </label>";
	if($BugzillaID>0){$html[]="<label class=\"btn btn btn-primary\" OnClick=\"javascript:$sync;\"><i class='fas fa-sync'></i> {synchronize} </label>";}
	
	
	$html[]="</div>";
	
	if($BugzillaID==0){
		$html[]=$tpl->FATAL_ERROR_SHOW_128("{error_bugzilla_need_registration}");
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	if($BugzillaPassword==null){
		if($BugzillaApikey==null){
			$html[]=$tpl->FATAL_ERROR_SHOW_128("{error_bugzilla_need_registration}");
			echo $tpl->_ENGINE_parse_body($html);		
			return;
		}
	}
	
	$html[]="<table id='table-bugzilla' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
	$html[]="<thead>";
	$html[]="<tr>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{priority}/{severity}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{component}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{subject}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{created}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{updated}</th>";
	$html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{reply}</th>";
	$html[]="<th data-sortable=true class='text-capitalize center' data-type='text'>Del.</center></th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	$results=array();
	if(is_file("/home/artica/SQLITE/bugzilla.db")){
		$q=new lib_sqlite("/home/artica/SQLITE/bugzilla.db");
		$results=$q->QUERY_SQL("SELECT * FROM `bugs` ORDER BY last_change_time DESC");
		if(!$q->ok){echo $q->mysql_error_html();}
	}
	
	$STATUSS["CONFIRMED"]=null;
	$STATUSS["IN_PROGRESS"]="-warning";
	$STATUSS["RESOLVED"]="-primary";
	
	$severitys["blocker"]="-danger";
	$severitys["critical"]="-danger";
	$severitys["major"]="-warning";
	$severitys["normal"]="-info";
	$severitys["minor"]="-primary";
	$severitys["trivial"]="";
	$severitys["enhancement"]="-success";

	$prioritys["Highest"]="-danger";
	$prioritys["High"]="-warning";
	$prioritys["Normal"]="-info";
	$prioritys["Low"]="-primary";
	$prioritys["Lowest"]=null;
	
	$TRCLASS=null;
	foreach ($results as $index=>$ligne){
		if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
		$id=$ligne["id"];
		$summary=$ligne["summary"];
		$status=$ligne["status"];
		$priority=$ligne["priority"];
		$severity=$ligne["severity"];
		$component=$ligne["component"];
		$creation_time=$tpl->time_to_date($ligne["creation_time"],true);
		$last_change_time=$tpl->time_to_date($ligne["last_change_time"],true);
		$version=$ligne["version"];
		$mkey=md5(serialize($ligne));
		$view="Loadjs('$page?show-js=$id')";
		$html[]="<tr class=$TRCLASS id='$mkey'>";
		$html[]="<td width=1% nowrap><span class='label label{$STATUSS[$status]}'>{{$status}}</span></td>";
		$html[]="<td width=1% nowrap><span class='label label{$prioritys[$priority]}'>$priority</span>&nbsp;&nbsp;<span class='label label{$severitys[$severity]}'>$severity</td>";
		$html[]="<td width=1% nowrap>".$tpl->td_href($component,"{view}",$view)."</td>";
		$html[]="<td><strong>v{$version}:<br>".$tpl->td_href($summary,"{view}",$view)."</td>";
		$html[]="<td width=1% nowrap>".$tpl->td_href($creation_time,"{view}",$view)."</td>";
		$html[]="<td width=1% nowrap>".$tpl->td_href($last_change_time,"{view}",$view)."</td>";
		$html[]="<td width=1%>". $tpl->icon_reply("Loadjs('$page?reply-js=$id')","AsSystemAdministrator"). "</td>";
		$html[]="<td width=1%>". $tpl->icon_delete("Loadjs('$page?delete-bug-js=$id&md=$mkey')","AsSystemAdministrator"). "</td>";
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
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-bugzilla').footable( { \"filtering\": { \"enabled\": true },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });
</script>";
	
	
	
	echo $tpl->_ENGINE_parse_body($html);

}

function parameters_save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	
	
	
	
	$tpl->SAVE_POSTs();

}

