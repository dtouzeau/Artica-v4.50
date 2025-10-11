<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");
if(isset($_POST["toto"])){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["update-system"])){update_system();exit;}
if(isset($_GET["update-system-js"])){update_system_js();exit;}
if(isset($_GET["upgrade-system-js"])){upgrade_system_js();exit;}
if(isset($_GET["upgrade-system-js2"])){upgrade_system_js2();exit;}



if(isset($_GET["update-system-js2"])){update_system_js2();exit;}
if(isset($_GET["table-main"])){table_main();exit;}
if(isset($_GET["check-system-js"])){check_system_js();exit;}
if(isset($_GET["check-system"])){check_system();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["main-page"])){page();exit;}
table();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{OS} {update}", "fa-duotone fa-box-open", "{install_applis_text}", "$page?table-main=yes", "os-update", "debian-restart-updates", false, "debian-system-updates");

    $tpl=new template_admin("{OS} {update}",$html);
    echo $tpl->build_firewall();


}
function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
    $tpl->js_dialog1("{pkg_upgrade_interface}", "$page?table=yes&byjs=yes");
	
}

function table(){
	$page=CurrentPageName();
    $tpl=new template_admin();
    if(isset($_GET["byjs"])) {
        $bton=getbuttons();
        $html = $tpl->page_header("{OS} {update}", "fa-duotone fa-box-open", "{install_applis_text}$bton", "$page?table-main=yes&byjs=yes", null, "debian-system-updates", false, "debian-system-updates");
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    echo "<div id='debian-system-updates'></div>";
    echo "<script>LoadAjaxTiny('debian-system-updates','$page?table-main=yes');</script>";
    return true;
}

function update_system_js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_confirm_execute("{pkg_upgrade_interface}", "toto", 1,"Loadjs('$page?update-system-js2=yes')");
	
}
function upgrade_system_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_confirm_execute("{pkg_upgrade_system}", "toto", 1,"Loadjs('$page?upgrade-system-js2=yes')");
}
function update_system_js2(){
	$page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->framework_buildjs("/system/debian/upgrade","upgrade.progress","upgrade.log","debian-system-updates","LoadAjaxSilent('debian-system-updates','$page?table-main=yes');LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');");
}

function upgrade_system_js2():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->framework_buildjs("/system/debian/distupgrade","upgrade.progress","upgrade.log","debian-system-updates","LoadAjaxSilent('debian-system-updates','$page?table-main=yes');LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');");
}
/*
 * sudo apt-get update
sudo apt-get install linux-image-amd64 linux-headers-amd64
 */

function update_system(){
	$page=CurrentPageName();
    $tpl=new template_admin();
    $jsrestart=$tpl->framework_buildjs("/system/debian/upgrade","upgrade.progress","upgrade.log","debian-system-updates","LoadAjaxSilent('debian-system-updates','$page?table-main=yes');LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');");
	$html="<div id='debian-system-progress'></div>
	<script>$jsrestart</script>";
	echo $html;
}



function check_system(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/aptget.progress";
	$ARRAY["LOG_FILE"]=PROGRESS_DIR."/aptget.log";
	$ARRAY["CMD"]="aptget.php?check=yes";
	$ARRAY["TITLE"]="{check_system_updates}";
	$ARRAY["AFTER"]="LoadAjaxSilent('debian-system-updates','$page?table-main=yes');LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=debian-system-progress')";
	$html="<div id='debian-system-progress'></div>
	<script>$jsrestart</script>";
	echo $html;
}

function check_system_js(){
	$tpl=new template_admin();
	$page=CurrentPageName();
	$tpl->js_dialog5("{check_system_updates}", "$page?check-system=yes");
}

function getbuttons():string{
    $page=CurrentPageName();
    $users=new usersMenus();

    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $btn[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('$page?check-system-js=yes');\"><i class='fal fa-sync-alt'></i> {check_software_updates} </label>";

    $btcolor="btn-warning";

    $MAIN_APT_GET=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MAIN_APT_GET"));
    if(!$MAIN_APT_GET){
        $btn[]="</div>";
        return @implode("\n",$btn);
    }

    if(count($MAIN_APT_GET)>0){
        $btcolor="btn-primary";
        if($users->AsSystemAdministrator){
            $btn[]="<label class=\"btn btn btn-warning\" OnClick=\"Loadjs('$page?update-system-js=yes');\"><i class='fa fa-download'></i> {update_software_packages} </label>";

            $btn[]="<label class=\"btn btn $btcolor\" OnClick=\"Loadjs('$page?upgrade-system-js=yes');\"><i class='fa fa-download'></i> {pkg_upgrade_system} </label>";
        }
    }


    $btn[]="</div>";
    return @implode("\n",$btn);
}

function table_main(){
    $byjs=false;
    if(isset($_GET["byjs"])){$byjs=true;}
	$tpl=new template_admin();
    $html[]="<div class=row style='margin-top:20px;margin-bottom:20px;margin-left:10px'>";
	$MAIN_APT_GET=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MAIN_APT_GET"));

    $TINY_ARRAY["TITLE"]="{OS} {update}";
    $TINY_ARRAY["ICO"]="fa-duotone fa-box-open";
    $TINY_ARRAY["EXPL"]="{install_applis_text}";
    $TINY_ARRAY["BUTTONS"]=getbuttons();
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    if($byjs){$jstiny=true;}

    $MAIN_APT_GET = json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MAIN_APT_GET_JSON"));
    if (json_last_error() > JSON_ERROR_NONE) {
        $html[]="<table class='table table-striped' style='width:70%'>";
        $html[]="<tr>";
        $html[]="<td colspan='2'><H2>{system_is_uptodate}</H2></td>";
        $html[]="</tr>";
        $html[]="</tbody>";
        $html[]="</table>";
        $html[]="</div>";
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        echo "<script>$jstiny</script>";
        die();
    }
    if(!property_exists($MAIN_APT_GET, "package_number")) {
        $html[]="<table class='table table-striped' style='width:70%'>";
        $html[]="<tr>";
        $html[]="<td colspan='2'><H2>{system_is_uptodate}</H2></td>";
        $html[]="</tr>";
        $html[]="</tbody>";
        $html[]="</table>";
        $html[]="</div>";
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        echo "<script>$jstiny</script>";
        die();
    }
    if($MAIN_APT_GET->package_number==0){
        $html[]="<table class='table table-striped' style='width:70%'>";
        $html[]="<tr>";
        $html[]="<td colspan='2'><H2>{system_is_uptodate}</H2></td>";
        $html[]="</tr>";
        $html[]="</tbody>";
        $html[]="</table>";
        $html[]="</div>";
        echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
        echo "<script>$jstiny</script>";
        die();
    }
	$html[]="<p>&nbsp;</p>";
	$html[]="<table class='table table-striped' style='width:95%'>";
	$html[]="<thead>";
	$html[]="<tr>";
    $html[]="<th>&nbsp;</th>";
    $html[]="<th>{package}</th>";
	$html[]="<th>{version}</th>";
	$html[]="</tr>";
	$html[]="</thead>";
	$html[]="<tbody>";
	
	foreach ($MAIN_APT_GET->packages_list as $package=>$version){
        $Desc="";
        if(property_exists($MAIN_APT_GET->Descriptions, $package)){
            $Desc=$MAIN_APT_GET->Descriptions->{$package};
        }
        $Desc=str_replace("Description-en:","",$Desc);
		$html[]="<tr>";
        $html[]="<td style='width:1%;vertical-align:top !important'><i class='fa-3x fa-duotone fa-box-open'></i></td>";
		$html[]="<td style='width:60%'><strong style='font-size: 19px'>$package</strong><br>
<span style='font-size: 14px'>$Desc</span></td>";
		$html[]="<td style='width:40%;text-align:left' nowrap><strong style='font-size: 22px'>$version</strong></td>";
		$html[]="</tr>";
    }
	$html[]="</tbody>";
	$html[]="</table>";
	$html[]="</div>";
    $html[]="<script>";
    $html[]="if(document.getElementById('header-update-page')){";
    $html[]="document.getElementById('header-update-page').innerHTML='';";
    $html[]="}";
    $html[]=$jstiny;
    $html[]="</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

