<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.computers.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}


$users=new usersMenus();
$tpl=new template_admin();
if(!$users->AsSystemAdministrator){$tpl->js_no_privileges();exit();}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["status"])){status();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $VMWARE_TOOLS_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_TOOLS_VERSION");
	$tpl->js_dialog6("{APP_VMTOOLS} v$VMWARE_TOOLS_VERSION", "$page?popup=yes",950);
}

function popup(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("vmware.array"));
    if(!is_array($array)){
		$array=array();
	}
    $html[]="<div id='progressOpenVmToolsRestart'></div>";
	$html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:335px'><div id='APP_VMTOOLS_ID'></div></td>";
	$html[]="<td style='vertical-align: top;width:90%;padding-left: 15px'>";
	$html[]="<table class='table table-striped'>";
	
	
	foreach ($array as $key=>$value){
		$html[]="<tr>
		<td><strong>{{$key}_label}:</strong></td>
		<td>$value</td>
		</tr>";
	
	}			
	$html[]="</table>";
    $jsRef=$tpl->RefreshInterval_js("APP_VMTOOLS_ID",$page,"status=yes");
	$html[]="<script>$jsRef;</script>";
	echo $tpl->_ENGINE_parse_body(implode("\n",$html));
	
}

function status():bool{
	$tpl=new template_admin();

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/vmtools/status"));
    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Decoding data ".json_last_error()."<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}","{error}"));
        return false;
    }


    if (!$json->Status) {
        echo $tpl->_ENGINE_parse_body($tpl->widget_rouge("Status = False<br>{$GLOBALS["CLASS_SOCKETS"]->mysql_error}", "{error}"));
        return false;
    }

    $jsRestart=$tpl->framework_buildjs("/vmtools/restart",
        "vmware.install.progress",
        "vmware.install.progress.txt",
        "progressOpenVmToolsRestart"
    );

    $ini = new Bs_IniHandler();
    $ini->loadString($json->Info);
    echo $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_VMTOOLS", $jsRestart));

    $EnableVMWareTools=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableVMWareTools"));
    if($EnableVMWareTools==0){
        $installjs=$tpl->framework_buildjs("/vmtools/install",
            "vmware.install.progress",
            "vmware.install.progress.txt",
            "progressOpenVmToolsRestart"
        );
        $btn=$tpl->button_autnonome("{install} {APP_VMTOOLS}",$installjs,ico_cd,"AsSystemAdministrator",335,"btn-primary");
        echo "<div>".$tpl->_ENGINE_parse_body($btn)."</div>";
    }

	return true;
	
}