<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}

if(isset($_GET["popup"])){popup();exit;}

js();

function js(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();$SquidVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidVersion");
	$tpl->js_dialog("{services_operations} {APP_SQUID} v$SquidVersion", "$page?popup=yes");
	
	
}


function popup(){
	$tpl=new template_admin();
    $page=CurrentPageName();
	$btwidth="width:230px;height:89px";
	
	
	$html[]="<div class='row'><div id='progress-action-restart'></div>
	<div class='ibox-content'>";
	$html[]="<table>";


    $jsrestart=$tpl->framework_buildjs("/proxy/all/reload",
        "squid.reload.progress","squid.reload.progress.log","progress-action-restart");


	$html[]="<tr><td style='width:1%;vertical-align:top' nowrap>	
			<button class='btn btn-primary btn-lg' type='button' style='$btwidth' OnClick=\"$jsrestart\">{reload}</button>
		</td>
			<td style='padding-left:10px'><div class='alert alert-info' style='height:89px'>{reload_squid_explain}</td>
	</tr>";


    $jsrestart=$tpl->framework_buildjs("/proxy/nohup/reconfigure",
    "squid.articarest.nohup","squid.access.center.progress.log","progress-action-restart");
	
	$html[]="<tr><td style='width:1%;vertical-align:top' nowrap >
	<button class='btn btn-primary btn-lg' style='$btwidth' type='button' OnClick=\"$jsrestart\">{reconfigure}</button></td>
	<td style='padding-left:10px'><div class='alert alert-info' style='height:89px'>{reconfigure_squid_explain}</td>
	</tr>";	
	
    $jsrestart=$tpl->framework_buildjs("/proxy/restart/reconfigure","squid.restart.progress",
            "squid.restart.progress.log","progress-action-restart");
	
	$html[]="<tr><td style='width:1%;vertical-align:top' nowrap >
	<button class='btn btn-primary btn-lg' style='$btwidth' type='button' OnClick=\"$jsrestart\">{complete_rebuild}</button></td>
	<td style='padding-left:10px'><div class='alert alert-info' style='height:89px'>{reconfigure_complete_squid_explain}</td>
	</tr>";	


    $jsrestart=$tpl->framework_buildjs("/proxy/restart/single","squid.quick.rprogress",
        "squid.quick.rprogress.log","progress-action-restart");

	$html[]="<tr><td style='width:1%;vertical-align:top' nowrap >
	<button class='btn btn-primary btn-lg' style='$btwidth' type='button' OnClick=\"$jsrestart\">{restart}</button></td>
	<td style='padding-left:10px'><div class='alert alert-info' style='height:89px'>{restart_squid_explain}</td>
	</tr>";

    $html[]="<tr><td style='width:1%;vertical-align:top' nowrap >
	<button class='btn btn-primary btn-lg' style='$btwidth' type='button' 
	OnClick=\"Loadjs('fw.proxy.debug.php');\">{APP_SQUID_DEBUG}</button></td>
	<td style='padding-left:10px'><div class='alert alert-info' style='height:89px'>{squid_debug_perform}</td>
	</tr>";
	

    $jsrestart=$tpl->framework_buildjs("/proxy/rotate","squid.rotate.progress","squid.rotate.progress.txt","progress-action-restart");
	
	$html[]="<tr><td style='width:1%;vertical-align:top' nowrap >
	<button class='btn btn-primary btn-lg' style='$btwidth' type='button' OnClick=\"$jsrestart\">{rotate}</button></td>
	<td style='padding-left:10px'><div class='alert alert-info' style='height:89px'>{squid_logrotate_perform}</td>
	</tr>";

    $jsrreadonly=$tpl->framework_buildjs("squid2.php?readonly=yes",
        "squid.readonly.progress",
        "squid.readonly.progress.txt",
        "progress-action-restart","BootstrapDialog1.close();Loadjs('$page')"
    );
    $jsrreadonlyoff=$tpl->framework_buildjs("squid2.php?readonly-off=yes",
        "squid.readonly.progress",
        "squid.readonly.progress.txt",
        "progress-action-restart","BootstrapDialog1.close();Loadjs('$page')"
    );
    $users=new usersMenus();
    if($users->AsSystemAdministrator) {
        $SquidConfReadOnly = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidConfReadOnly"));
        if ($SquidConfReadOnly == 0) {
            $html[] = "<tr><td style='width:1%;vertical-align:top' nowrap >
	<button class='btn btn-primary btn-lg' style='$btwidth' type='button' OnClick=\"$jsrreadonly\">{readonly}</button></td>
	<td style='padding-left:10px'><div class='alert alert-info' style='height:89px'>{squid_readonly_perform}</td>
	</tr>";
        } else {
            $html[] = "<tr><td style='width:1%;vertical-align:top' nowrap >
	<button class='btn btn-danger btn-lg' style='$btwidth' type='button' OnClick=\"$jsrreadonlyoff\">{writeable}</button></td>
	<td style='padding-left:10px'><div class='alert alert-danger' style='height:89px'>{squid_writeable_perform}</td>
	</tr>";
        }
    }


    $html[]="</table></div>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}