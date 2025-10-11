<?php
$GLOBALS["HOTFIX"]="";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.upload.handler.inc");
if(isset($_POST["EXIT"])){die();}
if(isset($_POST["none"])){admin_tracks("Artica Update task as been launched");die();}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["ArticaAutoUpateOfficial"])){save();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["history"])){history();exit;}
if(isset($_GET["history-start"])){history_start();exit;}
if(isset($_GET["seen-all"])){history_seen_all();exit;}
if(isset($_GET["perform-update"])){javascript_run_update();exit;}
if(isset($_GET["rollback-sp"])){roolbackp_sp();exit;}
if(isset($_GET["delete-all-sp-js"])){delete_all_sp_js();exit;}
if(isset($_POST["deleteallsp"])){delete_all_sp_perform();exit;}
if(isset($_GET["main-rollbackjs"])){main_rollback_js();exit;}
if(isset($_POST["roolbackfull"])){main_rollback_perform();exit;}
if(isset($_GET["PatchsBackup-remove"])){PatchsBackup_remove();exit;}
if(isset($_POST["PatchsBackup-remove"])){PatchsBackup_remove_perform();exit;}
if(isset($_GET["refresh-page"])){RefreshPage();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["status-left"])){table_left();exit;}
if(isset($_GET["status-rigth"])){table_right();exit;}
if(isset($_GET["widget-currentver"])){print_r(widget_current_version());exit;}
if(isset($_GET["settings-js"])){settings_js();exit;}
if(isset($_GET["hotfixdev-js"])){hotfixdev_js();exit;}
if(isset($_GET["hotfixdev-popup"])){hotfixdev_popup();exit;}
if(isset($_POST["ArticaHotFixDevs"])){hotfixdev_save();exit;}


if(isset($_GET["settings-popup"])){table_right_form();exit;}
if(isset($_GET["update-hotfix-js"])){hotfix_rc_js();exit;}
if(isset($_GET["update-hotfix-popup"])){hotfix_rc_popup();exit;}

page();
function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/articaweb/chown");
    $html=$tpl->page_header("{update_artica}","far fa-cloud-download",
        "{install_applis_text}<div id='header-update-page'></div>","$page?tabs=yes",
        "artica-update","progress-articaupd-restart",false,"main-artica-update-section");
//LoadAjax('main-artica-update-section',$page?tabs=yes');
	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{update_artica}",$html);
		echo $tpl->build_firewall();
		return true;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function hotfix_rc_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $md=$_GET["update-hotfix-js"];

    if(strlen($md)<5){
        $ArticaUpdateRepos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
        $array=update_find_hotfix_unstable($ArticaUpdateRepos);
        if(count($array)<2){
            return $tpl->js_error("{version-up-to-date}");
        }

        $md=base64_encode(serialize($array));
    }


    return $tpl->js_dialog8("HotFix RC","$page?update-hotfix-popup=$md");
}
function hotfix_rc_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ARRAY=unserialize(base64_decode($_GET["update-hotfix-popup"]));
    if(!is_array($ARRAY)){
        echo $tpl->div_error("Decode error||Return a corrupted array");
        return true;
    }
    //Array ( [URL] => http://articatech.net/download/UPatchs/4.50.000000/hotfix/testing/0/20230713-13.tgz [VERSION] => 20230713-13 [MD5] => e99760c8a583f3f373cded563a874073 [SIZE] => 106677815 [INDEX] => 2023071313 )

    $tpl->table_form_field_text("{version}",$ARRAY["VERSION"],ico_infoi);
    $tpl->table_form_field_text("MD5",$ARRAY["MD5"],ico_infoi);

    $after[]="dialogInstance8.close()";
    $after[]="LoadAjax('status-left','$page?status-left=yes');";
    $after[]="LoadAjax('status-right','$page?status-rigth=yes')";

    $jDown="document.location.href='{$ARRAY["URL"]}';";
    $tpl->table_form_field_js($jDown);
    $tpl->table_form_field_text("{package}",basename($ARRAY["URL"]),ico_file_zip);
    $tpl->table_form_field_text("{size}",FormatBytes($ARRAY["SIZE"]/1024),ico_weight);
    $jspr=$tpl->framework_buildjs("/system/artica/hotfix/perform",
        "hotfix.update.progress",
        "hotfix.update.log","hotfix-update-progress",
        @implode(";",$after)
    );
    $html[]="<div id='hotfix-update-progress'>";
    $html[]=$tpl->div_explain("HotFix RC v{$ARRAY["VERSION"]}||{HotfixRCExplain}");
    $tpl->table_form_button("{update_now}",$jspr,"AsSystemAdministrator");
    $html[]=$tpl->table_form_compile();
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function delete_all_sp_js():bool{
    $tpl=new template_admin();
   return  $tpl->js_confirm_delete("Service Packs: {delete_all}","deleteallsp","none","document.location.href='/artica-update';");
}
function PatchsBackup_remove():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $PatchsBackupSize=FormatBytes(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PatchsBackupSize"))/1024);
    return $tpl->js_confirm_delete("{FullAgeMax} $PatchsBackupSize",
        "PatchsBackup-remove","$PatchsBackupSize",
        "LoadAjax('main-artica-update-section','$page?tabs=yes');");
}
function PatchsBackup_remove_perform():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork('artica.php?PatchsBackupRemove=yes');
    admin_tracks("Removed Service Packs backup folder.");
    return true;
}
function main_rollback_js():bool{
    $tpl=new template_admin();
    $version=$_GET["main-rollbackjs"];
    $jsrestart=$tpl->framework_buildjs("system.php?roolback-global=$version","roolback.progress","roolback.progress.txt","progress-articaupd-restart","document.location.href='/logoff.php'","AsSystemAdministrator");
    return $tpl->js_confirm_execute("Roolback : $version","roolbackfull",$version,$jsrestart);
}
function main_rollback_perform():bool{
    $version=$_POST["roolbackfull"];
    admin_tracks("Rollback main artica version to $version");
    return true;
}
function delete_all_sp_perform():bool{
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/artica/servicepacks/removeall");
    admin_tracks("Service packs history as been removed");
    return true;
}


function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["Artica"]="$page?table-start=yes";
    $array["{schedule}"]="fw.system.tasks.php?microstart=yes&ForceTaskType=83";
    $array["{history}"]="$page?history-start=yes";
	$array["{OS}"]="fw.updates.debian.php";
	echo $tpl->tabs_default($array);
    return true;
}

function roolbackp_sp():bool{
    $tpl        = new template_admin();
    $sp         = intval($_GET["rollback-sp"]);
    $page       = CurrentPageName();
    $users      = new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_no_privileges();
        die();
    }

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/roolback.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/roolback.progress.txt";
    $ARRAY["CMD"]="system.php?roolback-sp=$sp";
    $ARRAY["TITLE"]="Rollbackup Service Pack $sp";
    $ARRAY["AFTER"]="LoadAjax('table-loader-articaupd-service','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-articaupd-restart')";
    return $tpl->js_confirm_execute("Rollback Service Pack $sp","EXIT","EXIT",$jsrestart);
}

function save():bool{
	$tpl=new template_admin();

	if(isset($_POST["ArticaEnablePatchs"])){

	    if(intval($_POST["ArticaEnablePatchs"])==1){
	        $_POST["ArticaDisablePatchs"]=0;
        }else{
            $_POST["ArticaDisablePatchs"]=1;
        }
        unset($_POST["ArticaEnablePatchs"]);
    }

    admin_tracks("Artica Update settings as been modified.");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("aptget.php?config-client=yes");
	$tpl->SAVE_POSTs();
    return true;
}
function history_start():bool{

    $page=CurrentPageName();
    echo "<div id='artica-updates-history' style='margin-top:10px'></div><script>LoadAjax('artica-updates-history','$page?history=yes');</script>";
    return true;
}
function history_seen_all():bool{
    $page=CurrentPageName();
    $q=new lib_sqlite("/home/artica/SQLITE/nightly.db");
    $q->QUERY_SQL("UPDATE history SET asseen=1 WHERE asseen=0");
    header("content-type: application/x-javascript");
    echo "LoadAjaxSilent('artica-notifs-barr','fw.icon.top.php?notifs=yes');\nLoadAjax('artica-updates-history','$page?history=yes');";
    return true;
}

function history():bool{
    $tpl=new template_admin();
    $t=time();
    $page=CurrentPageName();
    $SeenAll="Loadjs('$page?seen-all=yes');";

    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">
    	<label class=\"btn btn btn-danger\" OnClick=\"$SeenAll\"><i class='fa fa-eye'></i> {mark_all_as_read} </label>
     </div>";
    $html[]="<table id='table-$t' class=\"table table-stripped\" data-page-size=\"30\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{date}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{version}</center></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width='1% nowrap'>{seen}</center></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q=new lib_sqlite("/home/artica/SQLITE/nightly.db");
    $TRCLASS=null;
    $results=$q->QUERY_SQL("SELECT * FROM history ORDER by updated DESC");

    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $version=$ligne["version"];
        $date=$tpl->time_to_date($ligne["updated"],true);
        $asseen=intval($ligne["asseen"]);
        $asseen_js="<i class=\"fas fa-check\"></i>";
        if($asseen==0) {
            $asseen_js = $tpl->icon_check($asseen, "Loadjs('fw.icon.top.php?seen-updated=$ID')");
        }
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width=1% nowrap>$date</td>";
        $html[]="<td><strong>$version</strong></td>";
        $html[]="<td width=1%>$asseen_js</td>";
        $html[]="</tr>";



    }
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){$btns=array();}
    $TINY_ARRAY["TITLE"]="{update_artica} {history}";
    $TINY_ARRAY["ICO"]=ico_clock_desk;
    $TINY_ARRAY["EXPL"]="{install_applis_text}<div id='header-update-page'></div>";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='3'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="";
    $html[]="<script>";
    $html[]="if(document.getElementById('header-update-page')){";
    $html[]="document.getElementById('header-update-page').innerHTML='';";
    $html[]="}";
    $html[]="
			NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
			$jstiny
		</script>";


    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function table_start():bool{
    $page=CurrentPageName();
    echo "<div id='artica-status-update' style='margin-top:10px'></div>
    <script>LoadAjax('artica-status-update','$page?table=yes');</script>";
    return true;
}

function table():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:80%'><div id='status-left'></div></td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:80%'><div id='status-right'></div></td>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('status-left','$page?status-left=yes');";
    $html[]="LoadAjax('status-right','$page?status-rigth=yes')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function widget_current_version():array{
    $jsWindowsWidth=0;
    if(isset($_GET["jsWindowsWidth"])) {
        $jsWindowsWidth = intval($_GET["jsWindowsWidth"]);
    }
    $tpl=new template_admin();
    $page=CurrentPageName();
    $btn1=array();
    $br="&nbsp;";
    if($jsWindowsWidth<1537){
        $br="<br>";
    }
    $ArticaUpdateServicesPacks=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateServicesPacks"));
    $ArticaUpdateRepos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
    $ArticaAutoUpateOfficial=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateOfficial"));
    $ArticaAutoUpateNightly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateNightly"));
    $ArticaDisablePatchs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDisablePatchs"));
    $NightlylServicePackVersion="";
    $CURVER=@file_get_contents("VERSION");
    $CURVER_TEXT=$tpl->StringToFonts($CURVER,"fa-1x");
    $CURVER_KEY=str_replace(".", "", $CURVER);

    $OfficialServicePack=null;
    $CURPATCH_TEXT=null;
    $CURPATCH=intval($GLOBALS["CLASS_SOCKETS"]->getFrameWork("artica.php?SPVersion=yes"));
    VERBOSE("Current version = $CURVER Service Pack [$CURPATCH]",__LINE__);
    $perform_update_js = "Loadjs('$page?perform-update=yes')";

    $users=new usersMenus();
    if($users->AsSystemAdministrator){
        $btn1["ico"] = ico_download;
        $btn1["name"] = "{manual_update}";
        $btn1["js"] = "UPLOAD";


    }
    $key_offical=update_find_latest($ArticaUpdateRepos);
    VERBOSE("[WIDGET]: Get the Official version = $key_offical",__LINE__);
    $OFFICIALS=$ArticaUpdateRepos["OFF"];
    $Lastest=$OFFICIALS[$key_offical]["VERSION"];
    $bg_color="green";
    $ServicePackIco=$tpl->StringToFonts("sp","fa-1x");

    VERBOSE("[WIDGET]: CURPATCH=$CURPATCH",__LINE__);
    if($CURPATCH>0){
        $CURPATCH_TEXT="$br$ServicePackIco ".$tpl->StringToFonts($CURPATCH,"fa-1x");
    }
    if($ArticaAutoUpateOfficial==1) {
        VERBOSE("ArticaAutoUpateOfficial = $ArticaAutoUpateOfficial Get Official updates", __LINE__);

        if ($key_offical > intval($CURVER_KEY)) {
            VERBOSE("<H1>OFFICIAL</H1> $key_offical > $CURVER_KEY", __LINE__);
            $bg_color = "yellow";
            $btn2["ico"] = ico_download_animated;
            $btn2["name"] = "{update_now} v$Lastest";
            $btn2["js"] = $perform_update_js;
            $current_text="<H2 style='color:white'>".$tpl->_ENGINE_parse_body("{current}")."</H2>";
            $widget = $tpl->widget_h("minheight:201px:$bg_color", ico_infoi, "$CURVER_TEXT$CURPATCH_TEXT", $current_text, $btn1,$btn2);
            return array($widget, true);
        }
        if ($key_offical == intval($CURVER_KEY)) {
            VERBOSE("[WIDGET]: Check Service Pack for $Lastest", __LINE__);

            if (isset($ArticaUpdateServicesPacks[$Lastest])) {
                $OfficialServicePackVersion = intval($ArticaUpdateServicesPacks[$Lastest]["VERSION"]);
                $OfficialServicePack = $ServicePackIco . " " . $tpl->StringToFonts($OfficialServicePackVersion, "fa-1x");
            }
            if($ArticaDisablePatchs==0) {
                VERBOSE("[WIDGET]: Check Service Pack for Offical [$OfficialServicePackVersion] > [$CURPATCH]", __LINE__);
                if ($OfficialServicePackVersion > $CURPATCH) {
                    $bg_color = "yellow";
                    $btn2["ico"] = ico_download_animated;
                    $btn2["name"] = "{update_now} Service Pack $OfficialServicePack";
                    $btn2["js"] = $perform_update_js;
                    $current_text = "<H2 style='color:white'>" . $tpl->_ENGINE_parse_body("{current}") . "</H2>";
                    $widget = $tpl->widget_h("minheight:201px:$bg_color", ico_infoi, "$CURVER_TEXT$CURPATCH_TEXT", $current_text, $btn1, $btn2);
                    return array($widget, true);

                }
            }

        }

    }

    if($ArticaAutoUpateNightly==1){
        VERBOSE("<H1>[NIGHTLY]</H1>: Nightly Is allowed",__LINE__);
        $key_nightly=update_find_latest_nightly($ArticaUpdateRepos);
        $NIGHTLYS=$ArticaUpdateRepos["NIGHT"];
        $Lastest_nightly=$NIGHTLYS[$key_nightly]["VERSION"];
        if ($key_nightly > intval($CURVER_KEY)) {
            VERBOSE("[NIGHTLY] $key_nightly > $CURVER_KEY", __LINE__);
            $bg_color = "yellow";
            $btn2["ico"] = ico_download_animated;
            $btn2["name"] = "{update_now} v$Lastest_nightly";
            $btn2["js"] = $perform_update_js;
            $current_text="<H2 style='color:white'>".$tpl->_ENGINE_parse_body("{current}")."</H2>";
            $widget = $tpl->widget_h("minheight:201px:$bg_color", ico_infoi, "$CURVER_TEXT$CURPATCH_TEXT", $current_text, $btn1,$btn2);
            return array($widget, true);
        }

        if ($key_nightly == intval($CURVER_KEY)) {
            VERBOSE("[NIGHTLY] Check Service Pack for $Lastest_nightly", __LINE__);

            if (isset($ArticaUpdateServicesPacks[$Lastest_nightly])) {
                $NightlylServicePackVersion = intval($ArticaUpdateServicesPacks[$Lastest_nightly]["VERSION"]);
            }
            if($ArticaDisablePatchs==0) {
                VERBOSE("[NIGHTLY] Check Service Pack for $NightlylServicePackVersion > $CURPATCH", __LINE__);
                if ($NightlylServicePackVersion > $CURPATCH) {
                    $bg_color = "yellow";
                    $btn2["ico"] = ico_download_animated;
                    $btn2["name"] = "{update_now} Service Pack $NightlylServicePackVersion";
                    $btn2["js"] = $perform_update_js;
                    $current_text = "<H2 style='color:white'>" . $tpl->_ENGINE_parse_body("{current}") . "</H2>";
                    $widget = $tpl->widget_h("minheight:201px:$bg_color",
                        ico_infoi, "$CURVER_TEXT$CURPATCH_TEXT", $current_text, $btn1, $btn2);
                    return array($widget, true);

                }
            }

        }


    }

    VERBOSE("Nothing to do...",__LINE__);
    $Hotfix="";
    if(strlen($GLOBALS["HOTFIX"])>0){
        $btn1["text-left"]="&nbsp;<small style='font-size: 14px;color:white'>Hotfix {$GLOBALS["HOTFIX"]}</small>";
    }


    $current_text="<H2 style='color:white'>".$tpl->_ENGINE_parse_body("{current}")."$Hotfix</H2>";
    $widget= $tpl->widget_h("minheight:201px:$bg_color",ico_infoi,"$CURVER_TEXT$CURPATCH_TEXT",$current_text,$btn1);
    return array($widget,false);

}
function line_hotfix_unstable($tpl){
    $page=CurrentPageName();
    $ArticaUpdateRepos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
    $array=update_find_hotfix_unstable($ArticaUpdateRepos);
    if(count($array)==0){
        return $tpl;
    }


    $VERSION=$array["VERSION"];
    $md=base64_encode(serialize($array));
    $tpl->table_form_field_js("Loadjs('$page?update-hotfix-js=$md')");
    $tpl->table_form_field_text("HotFix RC","v.$VERSION",ico_infoi_bounce);
    return $tpl;
}
function line_lts($tpl){
    $ArticaUpdateRepos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
    $key_lts=update_find_lts($ArticaUpdateRepos);
    if($key_lts==0){
        return $tpl;
    }
    $CURVER=@file_get_contents("VERSION");
    $CURVER_KEY=intval(str_replace(".", "", $CURVER));
    $LTS=$ArticaUpdateRepos["LTS"];
    $Lastest=$LTS[$key_lts]["VERSION"];
    if($GLOBALS["HOTFIX"]<>null){

        if($CURVER_KEY==$key_lts){
            $tpl->table_form_field_js("s_PopUpFull('https://wiki.articatech.com/en/maintenance/upgrade-artica/hotfix-$key_lts','1024','900')");

        }
        $tpl->table_form_field_text("{current} Hotfix",$GLOBALS["HOTFIX"],ico_archive);
        $tpl->table_form_field_js("");
    }

    if($CURVER_KEY==$key_lts){
        $hotfix=$tpl->td_href("{see_list_fixes}","{go}","s_PopUpFull('https://wiki.articatech.com/en/maintenance/upgrade-artica/hotfix-$key_lts','1024','900');");
        $tpl->table_form_field_text("LTS Version",$Lastest. "&nbsp;($hotfix)",ico_infoi);
        return $tpl;
    }

    if($key_lts>$CURVER_KEY){
        $tpl=new template_admin();
        $tpl->table_form_field_js("Loadjs('fw.update.lts.php')","AsSystemAdministrator");
        $NEW_LTS_TEXT=$tpl->_ENGINE_parse_body("{NEW_LTS_TEXT}");
        $NEW_LTS_TEXT=str_replace("%s",$Lastest,$NEW_LTS_TEXT);
        $tpl->table_form_field_text("LTS Version","<span class=text-danger>$NEW_LTS_TEXT</span>",ico_infoi_bounce);
        return $tpl;
    }

    return $tpl;

}
function table_left():bool{
    $br="&nbsp;";
    $jsWindowsWidth=0;
    if(isset($_GET["jsWindowsWidth"])) {
        $jsWindowsWidth = intval($_GET["jsWindowsWidth"]);
    }

    if($jsWindowsWidth<1537){
        $br="<br>";
    }

	$page=CurrentPageName();
	$tpl=new template_admin();
    $OfficialServicePackVersion=0;
    $OfficialServicePackURI=null;
	$users=new usersMenus();
    $tdwith=100/3;
	$bt_upload=null;
    $btns[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $perform_update_js = "Loadjs('$page?perform-update=yes')";
    $perform_update_btn = "<label class=\"btn btn btn-warning\" OnClick=\"$perform_update_js\"><i class='fa fa-download'></i> {update_now}</label>";
    $ArticaUpdateServicesPacks=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateServicesPacks"));
    $ArticaUpdateRepos=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
    $ArticaAutoUpateOfficial=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateOfficial"));
    $ArticaAutoUpateNightly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateNightly"));
    $ArticaDisablePatchs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDisablePatchs"));

    if(!is_numeric($ArticaAutoUpateOfficial)){$ArticaAutoUpateOfficial=1;}
    $official="<H2 style='color:black;text-transform: capitalize'>".$tpl->_ENGINE_parse_body("{official}")."</H2>";
    $nightly="<H2 style='color:black'>".$tpl->_ENGINE_parse_body("{nightly}")."</H2>";
    $key_offical=update_find_latest($ArticaUpdateRepos);

	$CURVER=@file_get_contents("VERSION");

    $CURPATCH=$GLOBALS["CLASS_SOCKETS"]->getFrameWork("artica.php?SPVersion=yes");
	VERBOSE("Current patch [$CURPATCH]",__LINE__);
	$CURVER_KEY=intval(str_replace(".", "", $CURVER));
	$OFFICIALS=$ArticaUpdateRepos["OFF"];

	$Lastest=$OFFICIALS[$key_offical]["VERSION"];
	$MAIN_URI=$OFFICIALS[$key_offical]["URL"];
	$MAIN_MD5=$OFFICIALS[$key_offical]["MD5"];
    $OfficialServicePack=null;
    $CURPATCH_TEXT=null;
    $expl2=null;
    $ServicePackIco=$tpl->StringToFonts("sp","fa-1x");

    $TEST_UPDATE=true;
    if($ArticaAutoUpateOfficial==0 AND $ArticaAutoUpateNightly==0 and $ArticaDisablePatchs==1){
        $TEST_UPDATE=false;
    }

    if($TEST_UPDATE) {
        $q = new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
        $ligne = $q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM system_schedules WHERE TaskType=83");
        $Updates = intval($ligne["tcount"]);
        if ($Updates == 0) {
            $expl2="<br><strong>{notice}: <strong>{update_artica} ({schedule})</strong><br>{no_update_artica}";

        }
    }

	//Service Pack
    if(isset($ArticaUpdateServicesPacks[$Lastest])){
        $OfficialServicePackURI=$ArticaUpdateServicesPacks[$Lastest]["URI"];
        $OfficialServicePackVersion=$ArticaUpdateServicesPacks[$Lastest]["VERSION"];
        $OfficialServicePack=$ServicePackIco." ".$tpl->StringToFonts($OfficialServicePackVersion,"fa-1x");
    }


    $btn1=array();
    $btn2=array();

    $BT_UPDATE=false;

    list($widget,$MustUpdate)=widget_current_version();

    VERBOSE("Widget MustUpdate=[$MustUpdate]",__LINE__);

    if($MustUpdate){
        $btns[] = $perform_update_btn;
        $BT_UPDATE=true;
    }
	$html[]="<div class='table-responsive' style='margin-top:10px'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:$tdwith%' nowrap>";
    $html[]=$widget;
    $html[]="<td style='width:$tdwith%;padding-left:10px' nowrap>";

    if($MAIN_MD5<>null){
        $btn2["ico"]=ico_rollback;
        $btn2["name"]="Rollback";
        $btn2["js"]="Loadjs('$page?main-rollbackjs=$Lastest')";
        if(!$users->AsSystemAdministrator){$btn2=array();}
    }
    $Lastest=$tpl->StringToFonts($Lastest,"fa-1x");
    if($OfficialServicePackURI<>null){
        $btn1["ico"]=ico_file_zip;
        $btn1["name"]="Service Pack $OfficialServicePackVersion";
        $btn1["js"]="document.location.href='/$OfficialServicePackURI'";
        if(!$users->AsSystemAdministrator){$btn1=array();}
    }

    $html[]=$tpl->widget_h("minheight:201px:gray",ico_repeat_1,
        "$Lastest$br$OfficialServicePack",
        "<a href=\"$MAIN_URI\" style='text-decoration:underline;'>$official</a>",
        $btn1,$btn2);
    $html[]="</td>";



    $Lastest_nightly=null;
    $MAIN_URI=null;
    $MAIN_MD5=null;
    $MAIN_FILENAME=null;
    $Lastest_nightly_ver=null;
    $key_nightly=update_find_latest_nightly($ArticaUpdateRepos);
    $NIGHTLY=$ArticaUpdateRepos["NIGHT"];
    if(isset($NIGHTLY[$key_nightly]["VERSION"])) {
        $Lastest_nightly_ver=$NIGHTLY[$key_nightly]["VERSION"];
        $Lastest_nightly = $tpl->StringToFonts($Lastest_nightly_ver,"fa-1x");
        $MAIN_URI=$NIGHTLY[$key_nightly]["URL"];
        $MAIN_MD5=$NIGHTLY[$key_nightly]["MD5"];

    }
    $OfficialServicePackNightlyURI=null;
    $OfficialServicePackTEXT=null;
    $Lastest_nightly_ver_bin=intval(str_replace(".","",$Lastest_nightly_ver));
    $curverbin=intval(str_replace(".","",$CURVER));
    VERBOSE("Curver Bin=$curverbin --> nightl $Lastest_nightly_ver_bin",__LINE__);

    if(isset($ArticaUpdateServicesPacks[$Lastest_nightly_ver])){
        $OfficialServicePackNightlyURI=$ArticaUpdateServicesPacks[$Lastest_nightly_ver]["URI"];
        $OfficialServicePackVersion=$ArticaUpdateServicesPacks[$Lastest_nightly_ver]["VERSION"];
        $OfficialServicePackTEXT=$ServicePackIco." ".$tpl->StringToFonts($OfficialServicePackVersion,"fa-1x");
    }

    
    if($MAIN_MD5<>null){
        $btn2=array();
        $btn1=array();
        if($OfficialServicePackNightlyURI<>null){
            $btn1["ico"]=ico_file_zip;
            $btn1["name"]="Service Pack $OfficialServicePackVersion";
            $btn1["js"]="document.location.href='/$OfficialServicePackNightlyURI'";

        }

        if($ArticaAutoUpateNightly==1) {
            if($CURVER_KEY==$key_nightly) {
                if ($OfficialServicePackVersion > $CURPATCH) {
                    if (!$BT_UPDATE) {
                        $btn2["ico"] = ico_download_animated;
                        $btn2["name"] = "{update_now}";
                        $btn2["js"] = $perform_update_js;
                        $btns[] = $perform_update_btn;

                    }
                }
            }
        }


        if($Lastest_nightly_ver_bin>$curverbin) {
            $html[] = "<td style='width:$tdwith%;padding-left:10px' nowrap>";
            $html[] = $tpl->widget_h("minheight:201px:gray", ico_meta,
                "$Lastest_nightly$br$OfficialServicePackTEXT",
                "<a href=\"$MAIN_URI\" style='text-decoration:underline'>$nightly</a>",
                $btn1, $btn2);
            $html[] = "</td>";
        }
    }

    $html[]="</tr>";



    $jsresfresh=$tpl->framework_buildjs("/system/artica/indexes","refresh.index.progress","refresh.index.txt","progress-articaupd-restart","LoadAjax('main-artica-update-section','$page?tabs=yes');LoadAjax('table-loader-articaupd-service','$page?tabs=yes');");

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("artica.php?PatchsBackupSize=yes");
    $PatchsBackupSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PatchsBackupSize"));



    $btns[]="<label class=\"btn btn btn-info\" OnClick=\"$jsresfresh\"><i class='fas fa-sync-alt'></i> {verify}</label>";

    $btns[]="<label class=\"btn btn btn-primary\" OnClick=\"Loadjs('fw.update.events.php')\"><i class='".ico_eye."'></i>{display_update_events}</label>";


    if($PatchsBackupSize>16384){
        $PatchsBackupSize=FormatBytes($PatchsBackupSize/1024);
        if($users->AsSystemAdministrator) {
            $btns[] = "<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?PatchsBackup-remove=yes')\">
				<i class='fa-solid fa-trash'></i> {backup} ($PatchsBackupSize) </label>";
        }
    }
    $btns[]="</div>";

    
    

    
    
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="</div>";
    $TINY_ARRAY["TITLE"]="{update_artica}";
    $TINY_ARRAY["ICO"]="far fa-cloud-download";
    $TINY_ARRAY["EXPL"]="{install_applis_text}$expl2<div id='header-update-page'></div>";
    $TINY_ARRAY["BUTTONS"]=@implode("",$btns);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<script>
        $jstiny
        document.getElementById('header-update-page').innerHTML=''</script>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;
	
}
function hotfixdev_popup():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $ArticaHotFixDevs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHotFixDevs"));
    $form[]=$tpl->field_checkbox("ArticaHotFixDevs","{enable}",$ArticaHotFixDevs);
    $security="AsSystemAdministrator";
    $after="dialogInstance2.close();LoadAjax('status-right','$page?status-rigth=yes');";
    $MyForm=$tpl->form_outside("",$form,"{ArticaHotFixDevsExplain}","{apply}",$after,$security);
    echo $MyForm;
    return true;
}
function hotfixdev_save():bool{
    $tpl = new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaHotFixDevs",$_POST["ArticaHotFixDevs"]);
    $sock=new sockets();
    $sock->REST_API("/system/artica/hotfix/update");
    return admin_tracks("Set Enable Hotfix in developpement to {$_POST["ArticaHotFixDevs"]}");
}
function table_right():bool
{
    $page = CurrentPageName();
    $tpl = new template_admin();

    $ArticaAutoUpateRsync = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsync"));
    $ArticaAutoUpateRsyncServer = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsyncServer"));
    $ArticaAutoUpateRsyncServerPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsyncServerPort"));
    $ArticaHotFixDevs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHotFixDevs"));
    $ArticaDisablePatchs = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDisablePatchs"));
    $ArticaAutoUpateOfficial = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateOfficial"));
    $ArticaAutoUpateNightly = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateNightly"));

    $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
    if (!is_numeric($ArticaAutoUpateOfficial)) {
        $ArticaAutoUpateOfficial = 1;
    }
    if ($ArticaAutoUpateRsyncServerPort == 0) {
        $ArticaAutoUpateRsyncServerPort = 873;
    }


    $CurlBandwith = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("CurlBandwith");
    $CurlTimeOut = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("CurlTimeOut");
    if (!is_numeric($CurlBandwith)) {
        $CurlBandwith = 0;
    }
    if (!is_numeric($CurlTimeOut)) {
        $CurlTimeOut = 3600;
    }
    if ($CurlTimeOut < 720) {
        $CurlTimeOut = 3600;
    }
    if ($ArticaDisablePatchs == 0) {
        $ArticaEnablePatchs = 1;
    } else {
        $ArticaEnablePatchs = 0;
    }
    $EnableAPTClientMirror = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAPTClientMirror"));
    $APTClientMirrorAddr = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APTClientMirrorAddr"));
    $sps=array();
    $tpl=line_lts($tpl);

    $backuped_patchs=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("backuped_patchs"));
    if(!$backuped_patchs){
        $backuped_patchs=array();
    }
    if(count($backuped_patchs)>0){
        $tpl->table_form_field_js("blur()", "AsSystemAdministrator");
        ksort($backuped_patchs);
        foreach ($backuped_patchs as $spname=>$spsize){
            if(intval($spname)==0){continue;}
            $sps[]=$tpl->td_href("Service Pack $spname","Rollback Service Pack $spname","Loadjs('$page?rollback-sp=$spname')");
        }

        if(count($sps)>0) {
            $users=new usersMenus();
            if($users->AsSystemAdministrator) {
                $sps[] = $tpl->td_href("<span class='label label-danger'>{delete_all}</span>", "{delete_all} Service Packs", "Loadjs('$page?delete-all-sp-js=yes')");
            }
            $tpl->table_form_field_text("Rollbacks",@implode(" | ", $sps),ico_rollback);


        }

    }

    $tpl=line_hotfix_unstable($tpl);



    $tpl->table_form_field_js("Loadjs('$page?settings-js=yes');", "AsSystemAdministrator");
    $tpl->table_form_field_bool("{update_artica_official}", $ArticaAutoUpateOfficial, ico_download);
    $tpl->table_form_field_bool("{update_services_packs}", $ArticaEnablePatchs, ico_download);
    $tpl->table_form_field_bool("{update_artica_nightly}", $ArticaAutoUpateNightly, ico_download);

    $tpl->table_form_field_js("Loadjs('$page?hotfixdev-js=yes');", "AsSystemAdministrator");
    $tpl->table_form_field_bool("{update_hotfix_dev}", $ArticaHotFixDevs, ico_download);


    $tpl->table_form_field_js("Loadjs('$page?settings-js=yes');", "AsSystemAdministrator");
    $tpl->table_form_field_bool("{ssl}", $ArticaRepoSSL, ico_ssl);
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_INSTALLED"))==1){
        if($ArticaAutoUpateRsync==0){
            $tpl->table_form_field_bool("{remote_synchronization} (Rsync)", $ArticaAutoUpateRsync, ico_rsync);

        }else{
            $tpl->table_form_field_text("{remote_synchronization} (Rsync)",
                "$ArticaAutoUpateRsyncServer:$ArticaAutoUpateRsyncServerPort", ico_rsync);
        }
    }

    $tpl->table_form_section("{system_update}");


    $use_internal_debian_mirror=$tpl->_ENGINE_parse_body("{useInternalDebianMirror}");

    if ($EnableAPTClientMirror == 0) {
        $tpl->table_form_field_bool($use_internal_debian_mirror, $EnableAPTClientMirror, ico_linux);
    }else{
        $tpl->table_form_field_text("{internal_debian_mirror_addr}", $APTClientMirrorAddr, ico_linux);
    }

    echo $tpl->table_form_compile();
    return true;

}
function hotfixdev_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->js_dialog2("{update_hotfix_dev}","$page?hotfixdev-popup=yes");
    return true;
}
function settings_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->js_dialog2("{settings}","$page?settings-popup=yes");
    return true;
}
function table_right_form():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ArticaAutoUpateRsync=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsync"));
    $ArticaAutoUpateRsyncServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsyncServer"));
    $ArticaAutoUpateRsyncServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsyncServerPort"));


    $ArticaDisablePatchs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDisablePatchs"));

    $ArticaAutoUpateOfficial=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateOfficial"));
    $ArticaAutoUpateNightly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateNightly"));
    $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
    if(!is_numeric($ArticaAutoUpateOfficial)){$ArticaAutoUpateOfficial=1;}
    if($ArticaAutoUpateRsyncServerPort==0){$ArticaAutoUpateRsyncServerPort=873;}



    $CurlBandwith=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CurlBandwith");
    $CurlTimeOut=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CurlTimeOut");
    if(!is_numeric($CurlBandwith)){$CurlBandwith=0;}
    if(!is_numeric($CurlTimeOut)){$CurlTimeOut=3600;}
    if($CurlTimeOut<720){$CurlTimeOut=3600;}

    $form[]=$tpl->field_checkbox("ArticaAutoUpateOfficial","{update_artica_official}",$ArticaAutoUpateOfficial,true,"{update_artica_official_explain}");

    if($ArticaDisablePatchs==0){$ArticaEnablePatchs=1;}else{$ArticaEnablePatchs=0;}
    $form[]=$tpl->field_checkbox("ArticaEnablePatchs","{update_services_packs}",$ArticaEnablePatchs,false,"");


    $form[]=$tpl->field_checkbox("ArticaAutoUpateNightly","{update_artica_nightly}",$ArticaAutoUpateNightly,false,"{update_artica_nightly_explain}");
    $form[]=$tpl->field_checkbox("ArticaRepoSSL","{ssl}",$ArticaRepoSSL,false);


    $form[]=$tpl->field_section("{system_update}");
    $EnableAPTClientMirror=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAPTClientMirror"));
    $APTClientMirrorAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APTClientMirrorAddr"));
    $use_internal_debian_mirror=$tpl->_ENGINE_parse_body("{useInternalDebianMirror}");
    $form[]=$tpl->field_checkbox("EnableAPTClientMirror",$use_internal_debian_mirror,$EnableAPTClientMirror,
        "APTClientMirrorAddr","");
    $form[]=$tpl->field_text("APTClientMirrorAddr","{internal_debian_mirror_addr}",$APTClientMirrorAddr);

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RSYNC_INSTALLED"))==1){
        $form[]=$tpl->field_section("{remote_synchronization} (Rsync)");
        $form[]=$tpl->field_checkbox("ArticaAutoUpateRsync","{enable_remote_synchronization}",$ArticaAutoUpateRsync,"ArticaAutoUpateRsyncServer,ArticaAutoUpateRsyncServerPort","{ArticaAutoUpateRsync}");
        $form[]=$tpl->field_text("ArticaAutoUpateRsyncServer","{remote_server}",$ArticaAutoUpateRsyncServer);
        $form[]=$tpl->field_text("ArticaAutoUpateRsyncServerPort","{remote_server_port}",$ArticaAutoUpateRsyncServerPort);
    }else{
        $form[]=$tpl->field_hidden("ArticaAutoUpateRsync", 0);
    }
    $form[]=$tpl->field_section("{timeouts}");
    $form[]=$tpl->field_numeric("CurlTimeOut","{HTTP_TIMEOUT} ({seconds})",$CurlTimeOut);
    $form[]=$tpl->field_numeric("CurlBandwith","{limit_bandwidth} (kb/s)",$CurlBandwith);

    $security="AsSystemAdministrator";
    $after="dialogInstance2.close();LoadAjax('status-right','$page?status-rigth=yes');";
    $MyForm=$tpl->form_outside("{settings}",$form,null,"{apply}",$after,$security);
    echo $MyForm;
    return true;
}

function file_uploaded(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
    $tpl=new template_admin();
	$file=$_GET["file-uploaded"];

	admin_tracks("Artica Update $file as been successfully uploaded");
    $fname=urlencode(base64_encode($file));
    $jsrestart=$tpl->framework_buildjs(
        "/system/artica/update/manual/$fname",
        "artica.install.progress",
        "artica.install.progress.txt",
        "progress-articaupd-restart",
        "LoadAjax('status-left','$page?status-left=yes');"
    );

	echo $jsrestart;
}

function update_find_latest($array):int{
    if(!is_array($array)){return 0;}
    if(!isset($array["OFF"])){return 0;}
	$MAIN=$array["OFF"];$keyMain=0;foreach ($MAIN as $key=>$ligne){$key=intval($key);if($key==0){continue;}if($key>$keyMain){$keyMain=$key;}}return $keyMain;
}

function update_find_hotfix_unstable($array):array{
    if(!isset($array["HOTFIX"])){return array();}
    $VERSION=trim(@file_get_contents("VERSION"));
    if(!isset($array["HOTFIX"][$VERSION])){return array();}
    $CURPATCH=intval($GLOBALS["CLASS_SOCKETS"]->getFrameWork("artica.php?SPVersion=yes"));
    if($CURPATCH==0){$CURPATCH="000";}
    if(!isset($array["HOTFIX"][$VERSION][$CURPATCH])){return array();}
    $ARRAY=$array["HOTFIX"][$VERSION][$CURPATCH];
    $CurHo=0;
    foreach ($ARRAY as $HotFixNum=>$array){
        if($HotFixNum>$CurHo){
            $CurHo=$HotFixNum;
        }
    }

    if($CurHo>0){
        $CURHOTFIX=intval(str_replace("-","",$GLOBALS["HOTFIX"]));
        if($CurHo>$CURHOTFIX) {
            $ARRAY[$CurHo]["INDEX"] = $CurHo;
            return $ARRAY[$CurHo];
        }
    }
    return array();

}
function update_find_lts($array):int{
    if(!is_array($array["LTS"])){return 0;}
    if(!isset($array["LTS"])){return 0;}
    $MAIN=$array["LTS"];$keyMain=0;foreach ($MAIN as $key=>$ligne){$key=intval($key);if($key==0){continue;}
        if($key>$keyMain){$keyMain=$key;}}
    return $keyMain;
}

function update_find_latest_nightly($array):int{
    if(!is_array($array["NIGHT"])){return 0;}
    if(!isset($array["NIGHT"])){return 0;}
	$MAIN=$array["NIGHT"];$keyMain=0;foreach ($MAIN as $key=>$ligne){$key=intval($key);if($key==0){continue;}if($key>$keyMain){$keyMain=$key;}}return $keyMain;
}

function RefreshPage(){

    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $f[]="if(document.getElementById('main-artica-update-section')){";
    $f[]="LoadAjax('main-artica-update-section','$page?tabs=yes');";
    $f[]="}";
    $f[]="if(document.getElementById('table-loader-articaupd-service')){";
    $f[]="LoadAjax('table-loader-articaupd-service','$page?tabs=yes');";
    $f[]="}";
    echo @implode("\n",$f);
}

function javascript_run_update():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $prgress=$tpl->framework_buildjs(
        "system.php?artica-update=yes",
        "artica.updatemanu.progress",
        "artica.updatemanu.log",
        "progress-articaupd-restart",
        "LoadAjax('status-left','$page?status-left=yes');");

    $tpl->js_confirm_execute("{artica_update}","none","ok",$prgress);
    return true;

}