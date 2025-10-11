<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["table"])){table();exit;}
if(isset($_POST["PRADSInterface"])){Save();exit;}
if(isset($_GET["prads-status"])){status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}

page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="
	<div class=\"row border-bottom white-bg dashboard-header\">
		<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_PRADS}</h1>
		<p>{APP_PRADS_EXPLAIN}</p>
		</div>
	</div>
	<div class='row'>
	<div id='progress-prads-restart'></div>
	";
    $html[]="</div>";
    $html[]="<div class='row'><div class='ibox-content'>";
    $html[]="    <div id='table-loader-prads-pages'></div>";
    $html[]="</div>";
	$html[]="</div>";



    $html[]="<script>
	$.address.state('/');
	$.address.value('/network-prads');
	LoadAjax('table-loader-prads-pages','$page?tabs=yes');
	</script>";

    if(isset($_GET["main-page"])){$tpl=new template_admin(null,@implode("\n",$html));echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}
function tabs(){
    $PRADS_NODES=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PRADS_NODES"));
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["$PRADS_NODES {nodes}"]="fw.network.prads.nodes.php";
    $array["{events}"]="fw.network.prads.events.php";
    echo $tpl->tabs_default($array);
}

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $PRADSInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PRADSInterface");
    $PRADSRetention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PRADSRetention"));
    if($PRADSRetention==0){$PRADSRetention=7;}
    if($PRADSInterface==null){$PRADSInterface="eth0";}

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/prads.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/prads.log";
    $ARRAY["CMD"]="prads.php?restart=yes";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('prads-status','$page?prads-status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-prads-restart')";

    $form[]=$tpl->field_interfaces("PRADSInterface", "nooloopNoDef:{listen_interface}", $PRADSInterface);
    $form[]=$tpl->field_numeric("PRADSRetention","{retention_days}",$PRADSRetention);

    $myform=$tpl->form_outside("{parameters}", $form,null,"{apply}",
        $jsRestart,"AsSystemAdministrator");

//restart_service_each
    $html="<table style='width:100%'>
	<tr>
	<td style='vertical-align:top;width:240px'><div id='prads-status' style='margin-top:15px'></div></td>
	<td	style='vertical-align:top;width:90%'>$myform</td>
	</tr>
	</table>
	<script>LoadAjaxSilent('prads-status','$page?prads-status=yes');</script>
	";
    echo $tpl->_ENGINE_parse_body($html);

}

function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("prads.php?status=yes");
    $bsini=new Bs_IniHandler(PROGRESS_DIR."/prads.status");

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/prads.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/prads.log";
    $ARRAY["CMD"]="prads.php?restart=yes";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="LoadAjaxSilent('prads-status','$page?prads-status=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsRestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-prads-restart')";
    echo $tpl->SERVICE_STATUS($bsini, "APP_PRADS",$jsRestart);
}

function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
}
