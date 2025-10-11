<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["MiltergreyListAddDefaultNets"])){$tpl=new template_admin();$tpl->SAVE_POSTs();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
$APP_MILTER_GREYLIST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_GREYLIST_VERSION");
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_MILTERGREYLIST} v$APP_MILTER_GREYLIST_VERSION</h1></div>
	</div>
	<div class='row'>
	<div id='progress-greylist-restart'></div>
	<div class='ibox-content'>
	<div id='table-greylist-rules'></div>
	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/greylist');
	LoadAjax('table-greylist-rules','$page?tabs=yes');

	</script>";

	if(isset($_GET["main-page"])){
	$tpl=new template_admin("{websites}",$html);
	echo $tpl->build_firewall();
	return;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function status(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ini=new Bs_IniHandler();
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork('cmd.php?milter-greylist-ini-status=yes');
	$ini->loadFile(PROGRESS_DIR."/greylist.status");
	
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.restart.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.restart.log";
	$ARRAY["CMD"]="milter-greylist.php?restart=yes";
	$ARRAY["TITLE"]="{restarting_service}";
	$ARRAY["AFTER"]="LoadAjax('table-greylist-rules','$page?tabs=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$milterrestart_js="Loadjs('fw.progress.php?content=$prgress&mainid=progress-greylist-restart');";
	
	$MiltergreyListAddDefaultNets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MiltergreyListAddDefaultNets"));
	$MilterGreyListUseTCPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListUseTCPPort"));
	$MilterGeryListTCPPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGeryListTCPPort"));
	if($MilterGeryListTCPPort==0){$MilterGeryListTCPPort=rand(11000,50000);$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MilterGeryListTCPPort", $MilterGeryListTCPPort);}
	$MilterGreyListLazyaw=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListLazyaw"));
	$RemoteMilterService=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteMilterService"));
	$MilterGreyListTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListTimeOut"));
	if($MilterGreyListTimeOut==0){$MilterGreyListTimeOut=5;}
	$MilterGreyListGreyTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListGreyTime"));
	if($MilterGreyListGreyTime==0){$MilterGreyListGreyTime=5;}
	$MilterGreyListAutoWhite=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListAutoWhite"));
	if($MilterGreyListAutoWhite==0){$MilterGreyListAutoWhite=3;}
	
	
	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="	<td style='width:260px;vertical-align: top'>";
	$html[]="		<table style='width:100%'>";
	$html[]="			<tr><td valign='top'>";
	$html[]="				<div class=\"ibox\" style='border-top:0px'>";
    $html[]="					<div class=\"ibox-content\" style='border-top:0px'>". 
    								$tpl->SERVICE_STATUS($ini, "MILTER_GREYLIST",$milterrestart_js).
    $html[]="					</div>";
    $html[]="				</div>
     						</td>
     					</tr>";
    $html[]="		</table>";
	$html[]="		</td>";
	$html[]="	<td style='width:98%'>";
	
	
	$form[]=$tpl->field_checkbox("MiltergreyListAddDefaultNets","{add_default_nets}",$MiltergreyListAddDefaultNets,false,"{milter_greylist_add_default_net_explain}");
	$form[]=$tpl->field_checkbox("MilterGreyListUseTCPPort","{useTCPPort}",$MilterGreyListUseTCPPort,"MilterGeryListTCPPort");
	$form[]=$tpl->field_numeric("MilterGeryListTCPPort","{listen_port}",$MilterGeryListTCPPort);
	$form[]=$tpl->field_text("RemoteMilterService", "{use_milter_remote_service}", $RemoteMilterService,false,"{use_milter_remote_service_explain}");
	$form[]=$tpl->field_section("{behavior}");
	$form[]=$tpl->field_checkbox("MilterGreyListLazyaw","{remove_tuple}",$MilterGreyListLazyaw,false,"{remove_tuple_text}");
	$form[]=$tpl->field_numeric("MilterGreyListTimeOut","{timeout} ({days})",$MilterGreyListTimeOut,"{mgreylisttimeout_text}");
	$form[]=$tpl->field_numeric("MilterGreyListGreyTime","{greylist} ({minutes})",$MilterGreyListGreyTime,"{greylist_text}");
	$form[]=$tpl->field_numeric("MilterGreyListAutoWhite","{autowhite} ({days})",$MilterGreyListAutoWhite,"{autowhite_text}");
	$html[]=$tpl->form_outside("{parameters}", $form,null,"{apply}",$milterrestart_js,"AsPostfixAdministrator");
	
	$html[]="	</td>";
	$html[]="</tr>";
	$html[]="</table>";
	

	
	echo $tpl->_ENGINE_parse_body($html);
	
}



function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$sock=new sockets();
	$array["{status}"]="$page?status=yes";
	$array["{database}"]="fw.postfix.milter-greylist.database.php";
	echo $tpl->tabs_default($array);

}