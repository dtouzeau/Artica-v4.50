<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["verbose"])){
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string',null);
    ini_set('error_append_string',null);
}
$user=new usersMenus();
if(isset($_GET["core-head"])){core_heads();exit;}
if(isset($_GET["core-search"])){core_search();exit;}
if(isset($_GET["core-js"])){core_js();exit;}
if(isset($_GET["core-popup"])){core_popup();exit;}

if(isset($_GET["working-directory-js"])){section_workingdir_js();exit;}
if(isset($_GET["working-directory-popup"])){section_workingdir_popup();exit;}
if(isset($_POST["ArticaRepoDir"])){section_workingdir_save();exit;}

if(isset($_GET["upstream-js"])){section_upstream_js();exit;}
if(isset($_GET["upstream-popup"])){section_upstream_popup();exit;}
if(isset($_POST["ArticaRepoOfficialRepo"])){section_upstream_save();exit;}


if(isset($_GET["listen-js"])){section_listen_js();exit;}
if(isset($_GET["listen-popup"])){section_listen_popup();exit;}
if(isset($_POST["ArticaRepoInterface"])){section_listen_save();exit;}
if(isset($_GET["hotfix-head"])){hotfix_head();exit;}

if(isset($_GET["status-size"])){status_size();exit;}

if(isset($_GET["search"])){events_search();exit;}
clean_xss_deep();
if(isset($_POST["webserverpath"])){SaveConf();exit;}
if(isset($_POST["UbuntuCountryCode"])){SaveConf();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-flat"])){table_flat();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["stop-js"])){stop_js();exit;}
if(isset($_GET["start-js"])){start_js();exit;}
if(isset($_GET["remove-js"])){remove_js();exit;}
if(isset($_GET["move-js"])){move_js();exit;}
if(isset($_POST["stop"])){stop();exit;}
if(isset($_POST["remove"])){remove_directory();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["hotfix-search"])){hotfix_search();exit;}


page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{repositories}",ico_download,
        "{REPOSITORY_ARTICA_MIRROR_WHY}","$page?tabs=yes","articamirror","progress-articamirror-restart",false,"table-articamirror");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica:{APP_APT_MIRROR}",$html);
        echo $tpl->build_firewall();
        return;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}
function stop_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->js_confirm_execute("{DOWNLOAD_CONFIRM_CANCEL}","stop","true","LoadAjax('table-articamirror2','$page?table_flat=yes');");
}
function remove_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsbut = $tpl->framework_buildjs(
        "aptget.php?debian-mirror-remove=yes",
        "debian-mirror.progress",
        "debian-mirror.log",
        "progress-articamirror-restart",
        "LoadAjax('table-articamirror2','$page?table_flat=yes');");

    $tpl->js_confirm_execute("{remove_directory}","remove","true",$jsbut);
}
function start_js(){
    $page=CurrentPageName();
    admin_tracks("Starting APT Mirror download task");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?apt-mirror-start=yes");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-articamirror2','$page?table_flat=yes');";
}

function move_js(){
    $page=CurrentPageName();
    admin_tracks("Starting APT Mirror move directories task");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?apt-mirror-move=yes");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-articamirror2','$page?table_flat=yes');";

}
function section_listen_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog2("{listen}","$page?listen-popup=yes",650);
}
function section_workingdir_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{working_directory}","$page?section-workdir-popup=yes");
}
function section_upstream_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{repository}","$page?upstream-popup=yes");
}


function section_workingdir_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ArticaRepoDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoDir"));
    if(strlen($ArticaRepoDir)<3){
        $ArticaRepoDir="/home/artica/repository";
    }

    $html[]="<div id='docker-workdir-progress'></div>";
    $form[]=$tpl->field_browse_directory("ArticaRepoDir","{working_directory}",$ArticaRepoDir);

    /*$jsafter=$tpl->framework_buildjs(
        "docker.php?move-workdir=yes",
        "docker.workdir.progress","docker.workdir.log",
        "docker-workdir-progress",section_js(),null,null,"AsDockerAdmin"
    );
    */
    $jsafter="BootstrapDialog1.close();LoadAjax('table-articamirror2','$page?table_flat=yes');";
    $html[]=$tpl->form_outside(null,$form,null,"{apply}",$jsafter,"AsDebianSystem");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_workingdir_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaRepoDir",$_POST["ArticaRepoDir"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepo/scan");
    return admin_tracks("Change Artica repositories directory");
}
function section_listen_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepo/reload");
    return admin_tracks_post("Change Artica repositories listen interface");
}
function section_upstream_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/articarepo/client/run");
    return admin_tracks_post("Change Artica repositories source address");
}


function stop(){
    admin_tracks("Stopping APT Mirror download task");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?apt-mirror-stop=yes");
}



function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{service_status}"]="$page?table-flat=yes";
    $array["{core_versions}"]="$page?core-head=yes";
    $array["Hotfixes"]="$page?hotfix-head=yes";
    $array["{events}"]="$page?events=yes";
    echo $tpl->tabs_default($array);
}
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div id='table-articamirror2'></div>
    <script>LoadAjaxSilent('table-articamirror2','$page?table_flat=yes');</script>
    ";
}
function flat_repository($tpl){
    $page=CurrentPageName();
    $ArticaRepoOfficialRepo=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoOfficialRepo"));
    $ArticaInternalRepo=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaInternalRepo");

    $tpl->table_form_field_js("Loadjs('$page?upstream-js=yes')");
    if($ArticaRepoOfficialRepo==0){
        if(strlen($ArticaInternalRepo)<3){
            $tpl->table_form_field_bool("{repository}",0,ico_servcloud);
            return $tpl;
        }
        $tpl->table_form_field_text("{repository}",$ArticaInternalRepo,ico_servcloud);
        return $tpl;
    }
    $repoSource[1]="update.artica.cloud (https)";
    $repoSource[2]="articatech.net (http)";
    $tpl->table_form_field_text("{repository}",$repoSource[$ArticaRepoOfficialRepo],ico_servcloud);
    return $tpl;
}
function section_upstream_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $repoSource[0]="{internal}";
    $repoSource[1]="update.artica.cloud (https)";
    $repoSource[2]="articatech.net (http)";

    $ArticaRepoOfficialRepo=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoOfficialRepo"));
    $ArticaInternalRepo=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaInternalRepo");
    $form[]=$tpl->field_array_hash($repoSource,"ArticaRepoOfficialRepo","{repository}",$ArticaRepoOfficialRepo);
    $form[]=$tpl->field_text("ArticaInternalRepo","{url}",$ArticaInternalRepo);
    $jsafter="BootstrapDialog1.close();LoadAjaxSilent('table-articamirror2','$page?table_flat=yes');";
    $html[]=$tpl->form_outside(null,$form,null,"{apply}",$jsafter,"AsDebianSystem");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table_flat(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:260px;vertical-align:top'>";
    $html[]="<div id='articamirror-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:80%;vertical-align:top;padding-left:15px'>";

    $repoSource[0]="{internal}";
    $repoSource[1]="update.artica.cloud (https)";
    $repoSource[2]="articatech.net (http)";

    $ArticaRepoDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoDir");
    $ArticaRepoInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoInterface");
    $ArticaRepoPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoPort"));
    $ArticaRepoCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoCertificate");

    if(strlen($ArticaRepoDir)<3){
        $ArticaRepoDir="/home/artica/repository";
    }
    if($ArticaRepoInterface==""){
        $ArticaRepoInterface="0.0.0.0";
    }
    if($ArticaRepoPort==0){
        $ArticaRepoPort=8480;
    }

    if(strlen($ArticaRepoCertificate)>3){
        $ArticaRepoCertificate=" ($ArticaRepoCertificate)";
    }
    $tpl->table_form_field_js("Loadjs('$page?listen-js=yes')");
    $tpl->table_form_field_text("{listen_interface}","https://$ArticaRepoInterface:$ArticaRepoPort$ArticaRepoCertificate",ico_nic);

    $tpl->table_form_field_js("Loadjs('$page?working-directory-js=yes')");
    $tpl->table_form_field_text("{working_directory}",$ArticaRepoDir,ico_directory);

    $tpl=flat_repository($tpl);
    $html[]=$tpl->table_form_compile();
    $html[]="<div id='status-size'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";


    $TINY_ARRAY["TITLE"]="Artica {repositories}";
    $TINY_ARRAY["ICO"]=ico_download;
    $TINY_ARRAY["EXPL"]="{REPOSITORY_ARTICA_MIRROR_WHY}";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $Refresh=$tpl->RefreshInterval_js("articamirror-status",$page,"status=yes");
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]=$Refresh;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function SaveConf(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("articamirrorConfig")));
    if(!isset($config["debian_mirror"])){$config["debian_mirror"]="http://ftp.de.debian.org/";}
    if(!isset($config["DebianEnabled"])){$config["DebianEnabled"]=0;}
    if(!isset($config["UbuntuCountryCode"])){$config["UbuntuCountryCode"]="us";}

    if(isset($config["webserverpath"])) {
        if ($config["webserverpath"] <> null) {
            if (!preg_match("#^\/(home|media)\/#", $config["webserverpath"])) {
                echo $tpl->post_error("{wrong_value} {$config["webserverpath"]}");
                return false;
            }
        }
    }


    $tpl->SAVE_Admin_track("Saving APT Mirror settings");
    if(isset($_POST["webserverpath"])) {
        if ($config["webserverpath"] <> $_POST["webserverpath"]) {
            $_POST["webserverpath_migr"] = $_POST["webserverpath"];
            unset($_POST["webserverpath"]);
        }
    }

    foreach ($_POST as $key=>$val){
        $val=trim($val);
        if($val==null){continue;}
        slogs("WebConsole: Saving $key=[$val]");
        $config[$key]=$val;
    }

    foreach ($config as $key=>$val){
        $val=trim($val);
        if($val==null){continue;}
        slogs("WebConsole: new Setting $key=[$val]");
    }
    $newConf=base64_encode(serialize($config));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("articamirrorConfig",$newConf);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?apt-mirror-save=yes");


}

function slogs($text){
    openlog("apt-mirror", LOG_PID, LOG_SYSLOG);
    syslog(LOG_INFO, "$text");
    closelog();
}
function status_size():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ArticaRepoStatus=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoStatus"));
    if(!$ArticaRepoStatus){
        $ArticaRepoStatus["WEBF"]=0;
        $ArticaRepoStatus["TIME"]=0;
    }

    if($ArticaRepoStatus["WEBF"]==0){
        $tpl->table_form_field_bool("{webfiltering}",false,ico_directory);
    }else{
        $tpl->table_form_field_text("{webfiltering}",FormatBytes($ArticaRepoStatus["WEBF"]),ico_directory);
    }
    echo $tpl->table_form_compile();
    return true;
}
function status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $EnableArticaMirror=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMirror"));

    if($EnableArticaMirror==0) {
        $jsbut = $tpl->framework_buildjs(
            "/articarepo/install",
            "artica.mirror.progress",
            "artica.mirror.progress.log",
            "progress-articamirror-restart",
            "");

        $button["name"] = "{enable}";
        $button["js"] = $jsbut;
        $html[] = $tpl->widget_h("grey", "fad fa-compact-disc", "{disabled}", "{repositories}", $button);
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    echo "<script>LoadAjaxSilent('status-size','$page?status-size=yes');</script>";




    $jsbut = $tpl->framework_buildjs(
        "/articarepo/uninstall",
        "artica.mirror.progress",
        "artica.mirror.progress.log",
        "progress-articamirror-restart",
        "");

        $button["name"] = "{uninstall}";
        $button["js"] = $jsbut;
        $html[] = $tpl->widget_h("green", "fad fa-compact-disc", "{installed}", "{repositories}", $button);

    echo $tpl->_ENGINE_parse_body($html);

    $ArticaRepoInterfaceToV4=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoInterfaceToV4");
    $ArticaRepoPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoPort"));

    if($ArticaRepoPort==0){
        $ArticaRepoPort=8480;
    }
    $curl=new ccurl("https://$ArticaRepoInterfaceToV4:$ArticaRepoPort/status");
    $curl->WgetBindIpAddress="127.0.0.1";
    $curl->NoLocalProxy();
    if(!$curl->get()){
        echo $tpl->widget_rouge("{error}",$curl->error);
        return false;
    }
    $json=json_decode($curl->data);
    if(!$json->Status){
        echo $tpl->widget_rouge("{error}",$json->Error);
        return false;
    }
    $cnx=$tpl->FormatNumber($json->Info);
    echo $tpl->widget_vert("{connections}",$cnx);
return true;




}
function events(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    echo "<div style='margin-top:5px'>&nbsp;</div>";
    echo $tpl->search_block($page,null);
}
function events_search(){
    clean_xss_deep();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $search="";
    if(isset($_GET["search"])){$_GET["search"]=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}
    $MAIN=$tpl->format_search_protocol($_GET["search"]);
    $RFile=PROGRESS_DIR."/apt-mirror.syslog";
    $PFile=PROGRESS_DIR."/apt-mirror.syslog.pattern";
    $line=base64_encode(serialize($MAIN));
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?syslog-mirror=$line");


    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>PID</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";
    if(isset($_GET["search"])){$_GET["search"]=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}
    $data=explode("\n",@file_get_contents($RFile));
    if(count($data)>3){$_SESSION["HACLUSTER_SEARCH"]=$_GET["search"];}
    krsort($data);


    foreach ($data as $line){
        $line=trim($line);

        if(!preg_match("#^([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+.*?\[([0-9]+)\]:\s+(.+)#", $line,$re)){
            //echo "<strong style='color:red'>$line</strong><br>";
            continue;}

        $xtime=strtotime($re[1] ." ".$re[2]." ".$re[3]);
        $FTime=date("Y-m-d H:i:s",$xtime);
        $curDate=date("Y-m-d");
        $FTime=trim(str_replace($curDate, "", $FTime));
        $pid=$re[4];
        $line=$re[5];

        if(preg_match("#(fatal|Err)#i", $line)){
            $line="<span class='text-danger'>$line</span>";
        }




        $html[]="<tr>
				<td style='width:1%' nowrap>$FTime</td>
				<td style='width:1%' nowrap>$pid</td>
				<td>$line</td>
				</tr>";

    }


    if($_GET["search"]==null){$_GET["search"]="*";}
    $TINY_ARRAY["TITLE"]="Artica {repositores} {events} &laquo;{$_GET["search"]}&raquo;";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{REPOSITORY_ARTICA_MIRROR_WHY}";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents($PFile)."</i></div>";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
}
function section_listen_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ArticaRepoInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoInterface");
    $ArticaRepoPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoPort"));
    if($ArticaRepoPort==0){
        $ArticaRepoPort=8480;
    }
    $form[]=$tpl->field_interfaces("ArticaRepoInterface","{listen_interface}",$ArticaRepoInterface);
    $ArticaRepoCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoCertificate");

    $form[]=$tpl->field_numeric("ArticaRepoPort","{listen_port}",$ArticaRepoPort);
    $form[]=$tpl->field_certificate("ArticaRepoCertificate","{certificate}",null,$ArticaRepoCertificate);
    $jsafter="BootstrapDialog1.close();LoadAjax('table-articamirror2','$page?table_flat=yes');";
    echo $tpl->form_outside(null,$form,null,"{apply}",$jsafter,"AsSquidAdministrator",true);
    return true;
}
function hotfix_head():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,null,null,null,"&hotfix-search=yes");
    echo "</div>";
    return true;
}
function hotfix_search(){

}

function core_heads():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,null,null,null,"&core-search=yes");
    echo "</div>";
    return true;
}

function core_js():bool{
    $page=currentPageName();
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_dialog2("{core_versions}","$page?core-popup=yes&function=$function",800);
}
function core_popup():bool{
    $t=time();
    $tpl=new template_admin();
    $function=$_GET["function"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateMainIndex"));
    if(!property_exists($json,"OfficialVersions")){
        return false;
    }
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{version}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' style='width:1%' nowrap>{size}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS="";
    $ico=ico_file_zip;
    foreach ($json->OfficialVersions as $bin=>$OfficialVersion){


        $funct=$tpl->framework_buildjs("/repository/client/download/core/$bin",
            "repository.progress","repository.log","progress-articamirror-restart","$function()");

        $choose="<button class='btn btn-primary btn-xs' type='button' OnClick=\"$funct();\">{select}</button>";
        
        $size=$OfficialVersion->FILESIZE;
        $date=$OfficialVersion->FILEDATE;
        $version=$OfficialVersion->VERSION;
        $md=md5(json_encode($OfficialVersion));
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td><strong><i class='$ico'></i>&nbsp;$version</strong></td>";
        $html[]="<td style='width:1%' nowrap>".$tpl->time_to_date($date,true)."</td>";
        $html[]="<td  style='text-align:left'>". FormatBytes($size/1024)."</td>";
        $html[]="<td style='width:1%'  style='text-align:left' nowrap>$choose</td>";
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
    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) }); 
</script>
";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function core_search(){
    $page=currentPageName();
    $tpl=new template_admin();
    $t=time();
    $function=$_GET["function"];
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{version}</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text' style='width:1%' nowrap>{from_date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' style='width:1%' nowrap>{to_date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $json=json_encode(array());

    if(property_exists($json,"OfficialVersions")){

        foreach ($json->OfficialVersions as $OfficialVersion){
            var_dump($OfficialVersion);

            $html[]="<tr class='$TRCLASS' id='acl-$ID'>";
            $html[]="<td><strong><i class='$ico'></i>&nbsp;$sitename</strong></td>";
            $html[]="<td style='width:1%' nowrap></td>";
            $html[]="<td style='width:1%' nowrap><small>{expire_in}: $distance</small></td>";
            $html[]="<td  style='text-align:left'>$issuer</td>";
            $html[]="<td style='width:1%'  style='text-align:left' nowrap>$delete</td>";
            $html[]="</tr>";

        }


    }

    $topbuttons[]=array("Loadjs('$page?core-js=yes&function=$function');",ico_plus,"{add}");

    $TINY_ARRAY["TITLE"]="Artica {repositories} {core_versions}";
    $TINY_ARRAY["ICO"]=ico_download;
    $TINY_ARRAY["EXPL"]="{REPOSITORY_ARTICA_MIRROR_WHY}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='6'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="
<script> 
NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
$(document).ready(function() { $('#table-$t').footable({ \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ) }); 
$jstiny
</script>
";
echo $tpl->_ENGINE_parse_body($html);
}

