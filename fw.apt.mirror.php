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
if($user->AsDebianSystem==false){die();}
if(isset($_GET["search"])){events_search();exit;}
clean_xss_deep();
if(isset($_POST["webserverpath"])){SaveConf();exit;}
if(isset($_POST["UbuntuCountryCode"])){SaveConf();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table2"])){table2();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["stop-js"])){stop_js();exit;}
if(isset($_GET["start-js"])){start_js();exit;}
if(isset($_GET["remove-js"])){remove_js();exit;}
if(isset($_GET["move-js"])){move_js();exit;}
if(isset($_POST["stop"])){stop();exit;}
if(isset($_POST["remove"])){remove_directory();exit;}
if(isset($_GET["events"])){events();exit;}


page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $APT_MIRROR_VERSION=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APT_MIRROR_VERSION"));
    $html=$tpl->page_header("{APP_APT_MIRROR} v$APT_MIRROR_VERSION","fab fa-linux",
        "{REPOSITORY_DEB_MIRROR_WHY}","$page?tabs=yes","aptmirror","progress-aptmirror-restart",false,"table-aptmirror");

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
    $tpl->js_confirm_execute("{DOWNLOAD_CONFIRM_CANCEL}","stop","true","LoadAjax('table-aptmirror2','$page?table2=yes');");
}
function remove_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsbut = $tpl->framework_buildjs(
        "aptget.php?debian-mirror-remove=yes",
        "debian-mirror.progress",
        "debian-mirror.log",
        "progress-aptmirror-restart",
        "LoadAjax('table-aptmirror2','$page?table2=yes');");

    $tpl->js_confirm_execute("{remove_directory}","remove","true",$jsbut);
}
function start_js(){
    $page=CurrentPageName();
    admin_tracks("Starting APT Mirror download task");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?apt-mirror-start=yes");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-aptmirror2','$page?table2=yes');";
}

function move_js(){
    $page=CurrentPageName();
    admin_tracks("Starting APT Mirror move directories task");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?apt-mirror-move=yes");
    header("content-type: application/x-javascript");
    echo "LoadAjax('table-aptmirror2','$page?table2=yes');";

}

function stop(){
    admin_tracks("Stopping APT Mirror download task");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?apt-mirror-stop=yes");
}
function remove_directory(){
    admin_tracks("Removing APT Mirror directory");

}


function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{service_status}"]="$page?table=yes";
    $array["{events}"]="$page?events=yes";
    echo $tpl->tabs_default($array);
}
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div id='table-aptmirror2'></div>
    <script>LoadAjax('table-aptmirror2','$page?table2=yes');</script>
    ";
}

function table2(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $disabled=false;
    $EnableAptMirror=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAptMirror"));
    $config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AptMirrorConfig")));
    if(!isset($config["debian_mirror"])){$config["debian_mirror"]="http://ftp.de.debian.org/";}
    if(!isset($config["DebianEnabled"])){$config["DebianEnabled"]=0;}
    if(!isset($config["UbuntuCountryCode"])){$config["UbuntuCountryCode"]="us";}
    if(!isset($config["webserverpath"])){$config["webserverpath"]="/home/artica/apt-mirror";}
    if(!isset($config["nthreads"])){$config["nthreads"]=2;}
    if(!isset($config["kbs"])){$config["kbs"]=0;}
    if(!isset($config["Schedule"])){$config["Schedule"]=180;}



    if(!isset($config["webserverpath_migr"])){$config["webserverpath_migr"]=null;}
    $APT_MIRROR_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APT_MIRROR_INSTALLED"));
    if(!isset($config["bullseye"])){$config["bullseye"]=0;}
    if($config["debian_mirror"]==null){$config["debian_mirror"]="http://ftp.de.debian.org/";}
    if($config["UbuntuCountryCode"]==null){$config["UbuntuCountryCode"]="us";}
    if($config["webserverpath"]==null){$config["webserverpath"]="/home/artica/apt-mirror";}
    if(intval($config["nthreads"])==0){$config["nthreads"]=2;}




    $ctcode["de"]="de";
    $ctcode["am"]="am";
    $ctcode["us"]="us";
    $ctcode["au"]="au";
    $ctcode["at"]="at";
    $ctcode["be"]="be";
    $ctcode["by"]="by";
    $ctcode["br"]="br";
    $ctcode["ca"]="ca";
    $ctcode["cl"]="cl";
    $ctcode["cn"]="cn";
    $ctcode["kr"]="kr";
    $ctcode["hr"]="hr";
    $ctcode["dk"]="dk";
    $ctcode["es"]="es";
    $ctcode["fi"]="fi";
    $ctcode["fr"]="fr";
    $ctcode["hk"]="hk";
    $ctcode["hu"]="hu";
    $ctcode["is"]="is";
    $ctcode["it"]="it";
    $ctcode["jp"]="jp";
    $ctcode["lt"]="lt";
    $ctcode["md"]="md";
    $ctcode["no"]="no";
    $ctcode["nc"]="nc";
    $ctcode["nz"]="nz";
    $ctcode["nl"]="nl";
    $ctcode["pl"]="pl";
    $ctcode["pt"]="pt";
    $ctcode["cz"]="cz";
    $ctcode["uk"]="uk";
    $ctcode["ru"]="ru";
    $ctcode["si"]="si";
    $ctcode["sk"]="sk";
    $ctcode["se"]="se";
    $ctcode["ch"]="ch";
    $ctcode["tw"]="tw";

    $Timez[5]="5 {minutes}";
    $Timez[10]="10 {minutes}";
    $Timez[15]="15 {minutes}";
    $Timez[30]="30 {minutes}";
    $Timez[60]="1 {hour}";
    $Timez[120]="2 {hours}";
    $Timez[180]="3 {hours}";
    $Timez[360]="6 {hours}";
    $Timez[720]="12 {hours}";
    $Timez[1440]="1 {day}";
    $Timez[2880]="2 {days}";

    if($config["webserverpath_migr"]<>null){
        $form[]=$tpl->field_info("webserverpath_migr_none","{storage_directory}","{$config["webserverpath"]}&raquo;{$config["webserverpath_migr"]}");
    }else {
        $form[] = $tpl->field_browse_directory("webserverpath", "{storage_directory}", $config["webserverpath"]);
    }
    $form[]=$tpl->field_array_hash($ctcode,"UbuntuCountryCode","{country_code}",$config["UbuntuCountryCode"]);
    $form[]=$tpl->field_numeric("nthreads","{download_tasks}",$config["nthreads"],"{apt_mirror_threads}");
    $form[]=$tpl->field_numeric("kbs","{download_kbs} (KiloBytes/s)",$config["kbs"],"{apt_mirror_threads}");


    $form[]=$tpl->field_checkbox("buster","Buster (v10)",1,false,null,true);
    $form[]=$tpl->field_checkbox("bullseye","Bullseye (v11)",$config["bullseye"]);

    $form[]=$tpl->field_array_hash($Timez, "Schedule", "{update_each}", $config["Schedule"]);

    if($APT_MIRROR_INSTALLED==0){
        $ttitle_prefix=": {not_installed}";
        $disabled=true;
    }
    if($EnableAptMirror==0){
        $ttitle_prefix=": {feature_disabled}";
        $disabled=true;
    }

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:260px;vertical-align:top'>";
    $html[]="<div id='apt-mirror-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:80%;vertical-align:top;padding-left:15px'>";
    $html[]=$tpl->form_outside("{DEBIAN_REPOSITORIES}$ttitle_prefix",$form,null,"{apply}",
        "LoadAjax('table-aptmirror2','$page?table2=yes');","AsDebianSystem",false,$disabled);

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";




    $infowait="";
    $APT_MIRROR_VERSION=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APT_MIRROR_VERSION"));
    $TINY_ARRAY["TITLE"]="{APP_APT_MIRROR} v$APT_MIRROR_VERSION";
    $TINY_ARRAY["ICO"]="fab fa-linux";
    $TINY_ARRAY["EXPL"]="{REPOSITORY_DEB_MIRROR_WHY}$infowait";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="<script>";
    $html[]="LoadAjax('apt-mirror-status','$page?status=yes');$jstiny";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function SaveConf(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AptMirrorConfig")));
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
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AptMirrorConfig",$newConf);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?apt-mirror-save=yes");


}

function slogs($text){
    openlog("apt-mirror", LOG_PID, LOG_SYSLOG);
    syslog(LOG_INFO, "$text");
    closelog();
}

function status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $APT_MIRROR_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APT_MIRROR_INSTALLED"));
    $EnableAptMirror=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAptMirror"));
    if($APT_MIRROR_INSTALLED==0) {
        $button=array();
        $html[] = $tpl->widget_h("gray", "fas fa-exclamation-circle", "{not_installed}", "{disabled}", $button);
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    if($EnableAptMirror==0) {
        $jsbut = $tpl->framework_buildjs(
            "aptget.php?debian-mirror=yes",
            "debian-mirror.progress",
            "debian-mirror.log",
            "progress-aptmirror-restart",
            "LoadAjax('table-aptmirror2','$page?table2=yes');");

        $button["name"] = "{enable}";
        $button["js"] = $jsbut;
        $html[] = $tpl->widget_h("grey", "fad fa-compact-disc", "{disabled}", "{APP_APT_MIRROR}", $button);
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $APT_MIRROR_NO_SIZE=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APT_MIRROR_NO_SIZE"));
    if($APT_MIRROR_NO_SIZE<>null){
        $ARRAY=unserialize($APT_MIRROR_NO_SIZE);
        if(count($ARRAY)>1){
            $required=FormatBytes($ARRAY[0]/1024);
            $cur=FormatBytes($ARRAY[1]/1024);
            $html[]=$tpl->div_error("{insufficient_disk_space}||{required} <strong>$required</strong>, {current}: <strong>$cur</strong>");

        }

    }

    $stop_js="Loadjs('$page?stop-js=yes')";
    $start_js="Loadjs('$page?start-js=yes')";
    $jsremove="Loadjs('$page?remove-js=yes')";
    $move_js="Loadjs('$page?move-js=yes')";


    $config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AptMirrorConfig")));
    if(!isset($config["webserverpath_migr"])){$config["webserverpath_migr"]=null;}
    if($config["webserverpath_migr"]<>null) {
        $LOCKIT=true;
    }




    $jsbut = $tpl->framework_buildjs(
            "aptget.php?debian-mirror-uninstall=yes",
            "debian-mirror.progress",
            "debian-mirror.log",
            "progress-aptmirror-restart",
            "LoadAjax('table-aptmirror2','$page?table2=yes');");

        $button["name"] = "{uninstall}";
        $button["js"] = $jsbut;
        $jsrestart=null;
        $html[] = $tpl->widget_h("green", "fad fa-compact-disc", "{installed}", "{APP_APT_MIRROR}", $button);
        $tpath=PROGRESS_DIR."/apt-mirror.status";
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?apt-mirror-ini-status=yes");
        $ini->loadFile($tpath);

        $AptMirrorRepoSize=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("AptMirrorRepoSize");
        if($AptMirrorRepoSize<>null){
            $button["name"] = "{remove}";
            $button["js"] = $jsremove;
            if($LOCKIT){$button=array();}
            $html[] = $tpl->widget_h("green", "fas fa-hdd", $AptMirrorRepoSize, "{disk_usage}", $button);
        }





    if(!isset($ini->_params["APP_APT_MIRROR"]["running"])){
        $ini->_params["APP_APT_MIRROR"]["running"]=0;
    }

    if($ini->_params["APP_APT_MIRROR"]["running"]==1) {
        $html[] = $tpl->SERVICE_STATUS($ini, "APP_APT_MIRROR", $jsrestart);
    }else{
        $button["name"] = "{run_task}";
        $button["js"] = $start_js;
        if(!$LOCKIT){
            $html[] = $tpl->widget_h("grey", "fas fa-stop-circle", "{sleeping}", "{APP_APT_MIRROR}", $button);
        }
        if($LOCKIT) {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?debian-mirror-ismove=yes");
            $FILE=PROGRESS_DIR."/APT_MIRROR_MOVE_RUNING";
            $APT_MIRROR_MOVE_RUNING=intval(@file_get_contents($FILE));
            if($APT_MIRROR_MOVE_RUNING==1) {
                $html[] = $tpl->widget_h("green", "fas fa-people-carry", "{moving_data}", "{running}");
            }else{
                $button["name"] = "{run_task}";
                $button["js"] = $move_js;
                $html[] = $tpl->widget_h("yellow", "fas fa-people-carry", "{moving_data}", "{stopped}",$button);
            }
        }

    }

    $statusfile     = PROGRESS_DIR."/apt-mirror-web.status";
    $ini->loadFile($statusfile);
    $html[] = $tpl->SERVICE_STATUS($ini,"APP_APT_MIRROR_WEB:width=100%");


    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";


    if($ini->_params["APP_APT_MIRROR"]["running"]==0) {
        if(!$LOCKIT) {
            $bts[] = "<label class=\"btn btn btn-primary\" OnClick=\"$start_js\"><i class='fas fa-play-circle'></i> {run_update_task_now}</label>";
        }
    }else{
        $bts[] = "<label class=\"btn btn btn-warning\" OnClick=\"$stop_js;\"><i class='fas fa-stop-circle'></i> {stop} </label>";

    }
    $bts[]="</div>";

    $infowait=null;
    if(!isset($config["webserverpath_migr"])){$config["webserverpath_migr"]=null;}
    if($config["webserverpath_migr"]<>null) {
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?debian-mirror-ismove=yes");
        $FILE=PROGRESS_DIR."/APT_MIRROR_MOVE_RUNING";
        $APT_MIRROR_MOVE_RUNING=intval(@file_get_contents($FILE));
        if($APT_MIRROR_MOVE_RUNING==1) {
            $infowait = "<p><table style='width:100%'>
            <tr>
            <td width='1%'><img src='img/wait-clock.gif'></td>
            <td width='99%' style='padding-left:10px'><H3>{moving_files_to_new_folder}</H3></td>
            </tr>
            </table>";
        }
    }



    $TINY_ARRAY["TITLE"]="{APP_APT_MIRROR}";
    $TINY_ARRAY["ICO"]="fab fa-linux";
    $TINY_ARRAY["EXPL"]="{REPOSITORY_DEB_MIRROR_WHY}$infowait";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);

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
				<td width=1% nowrap>$FTime</td>
				<td width=1% nowrap>$pid</td>
				<td>$line</td>
				</tr>";

    }
    if(isset($_GET["search"])){$_GET["search"]=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}
    if($_GET["search"]==null){$_GET["search"]="*";}
    $TINY_ARRAY["TITLE"]="{APP_APT_MIRROR} {events} &laquo;{$_GET["search"]}&raquo;";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{REPOSITORY_DEB_MIRROR_WHY}";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody></table>";
    $html[]="<div><i>".@file_get_contents($PFile)."</i></div>";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);



}
