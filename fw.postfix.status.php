<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");

if(isset($_GET["restart-postfix-3"])){restart_postfix_3();exit;}
if(isset($_GET["restart-postfix-2"])){restart_postfix_2();exit;}
if(isset($_GET["restart-postfix-popup"])){restart_postfix_1();exit;}
if(isset($_GET["restart-postfix-js"])){restart_postfix_js();exit;}
if(isset($_GET["table2"])){table2();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["stats-mailqueue"])){stats_mailqueue();exit;}
if(isset($_GET["stats-mailstats"])){stats_mailstats();exit;}
if(isset($_GET["stats-mailvolume"])){stats_mailvolume();exit;}

if(isset($_GET["milter-greylist-status"])){milter_grey_list_status();exit;}
if(isset($_GET["milter-regex-status"])){milter_regex_status();exit;}
if(isset($_GET["right-status"])){right_status();exit;}
if(isset($_GET["socks-connection"])){sock_connection();exit;}
if(isset($_GET["instance-reinstall-js"])){reinstall_instance();exit;}
if(isset($_POST["instance-reinstall"])){reinstall_instance_confirm();exit;}
if(isset($_GET["left-postfix-status"])){left_postfix_status();exit;}
if(isset($_GET["right-top-status"])){top_status();exit;}
page();

function reinstall_instance():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-reinstall-js"]);
    $instancename=get_instance_name($instance_id);

    $log="Reinstall the SMTP instance $instancename ($instance_id)";
    $restart=$tpl->framework_buildjs(
        "postfix2.php?reinstall-instance=$instance_id",
        "postfix-multi.$instance_id.reinstall.progress",
        "postfix-multi.$instance_id.reinstall.log","progress-postfix-restart"
    );


    $tpl->js_dialog_confirm_action("$instancename:{reinstall_instance}","instance-reinstall",$log,$restart);
    return true;
}

function restart_postfix_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance=intval($_GET["restart-postfix-js"]);
    return $tpl->js_dialog_modal("{restarting_service}","$page?restart-postfix-popup=$instance");

}
function restart_postfix_1():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance=intval($_GET["restart-postfix-popup"]);
    $html[]="<H1 id='h1_restart_postfix_1'>{stopping_service}</H1>";
    $html[]="<div id='restart_postfix_1' style='margin:30px'></div>";
    $html[]="<script>";
    $html[]="LoadAjax('restart_postfix_1','$page?restart-postfix-2=$instance');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function restart_postfix_2():bool{
    $page=CurrentPageName();
    $instance=intval($_GET["restart-postfix-2"]);
    $sock=new sockets();
    $data=$sock->REST_API("/postfix/master/stop/$instance");
    $tpl=new template_admin();
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        $html[]=$tpl->widget_rouge("{error}",json_last_error_msg());
        $html[]="<div style='text-align:right'>".$tpl->button_autnonome("{close}","DialogModal.close();",ico_lock)."</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    if(!$json->Status){
        $html[]=$tpl->widget_rouge("{error}","{failed}");
        $html[]="<div style='text-align:right'>".$tpl->button_autnonome("{close}","DialogModal.close();",ico_lock)."</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    $title=base64_encode($tpl->_ENGINE_parse_body("{starting_service}"));
    $html[]="<script>";
    $html[]="var tempvalue='$title';";
    $html[]="document.getElementById('h1_restart_postfix_1').innerHTML=base64_decode(tempvalue);";
    $html[]="LoadAjax('restart_postfix_1','$page?restart-postfix-3=$instance');";
    $html[]="</script>";
    echo @implode("\n",$html);
return true;
}
function restart_postfix_3():bool{
    $page=CurrentPageName();
    $instance=intval($_GET["restart-postfix-2"]);
    $sock=new sockets();
    $data=$sock->REST_API("/postfix/master/start/$instance");
    $tpl=new template_admin();
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        $html[]=$tpl->widget_rouge("{error}",json_last_error_msg());
        $html[]="<div style='text-align:right'>".$tpl->button_autnonome("{close}","DialogModal.close();",ico_lock)."</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    if(!$json->Status){
        $html[]=$tpl->widget_rouge("{error}","{failed}");
        $html[]="<div style='text-align:right'>".$tpl->button_autnonome("{close}","DialogModal.close();",ico_lock)."</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    $html[]="<script>";
    $html[]="LoadAjaxSilent('postfix-status-div','$page?table2=yes&instance-id=$instance');";
    $html[]="LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');";
    $html[]="DialogModal.close();";
    $html[]="</script>";
    echo @implode("\n",$html);
    return true;
}

function reinstall_instance_confirm():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    admin_tracks($_POST["instance-reinstall"]);
    return true;
}
function get_instance_name($ID):string{
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $ligne=$q->mysqli_fetch_array("SELECT instancename FROM postfix_instances WHERE id='$ID'");
    return trim($ligne["instancename"]);

}

function page():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
	$POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));
	$title=$tpl->_ENGINE_parse_body("{APP_POSTFIX} &raquo;&raquo; {service_status}");

    if($EnablePostfixMultiInstance==1){
        $title=$tpl->_ENGINE_parse_body("{InternalRouter} &raquo;&raquo; {service_status}");
    }

	$js="$page?table=yes&instance-id=$instance_id";
	$MUNIN_CLIENT_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MUNIN_CLIENT_INSTALLED"));
	$EnableMunin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMunin"));
    if($instance_id>0){$EnableMunin=0;}
	if($MUNIN_CLIENT_INSTALLED==1){
		if($EnableMunin==1){
            $js="$page?tabs=yes";
		}
	}

    $html=$tpl->page_header(
        "$title v$POSTFIX_VERSION","fas fa-tachometer-alt",
        "{APP_POSTFIX_TEXT}",
        $js,
        "postfix-status-$instance_id","progress-postfix-restart",false,"table-postfix"

    );

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_POSTFIX} v$POSTFIX_VERSION",$html);
		echo $tpl->build_firewall();
		return true;
	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function tabs():bool{
	$page=CurrentPageName();
	$tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
	$array["{status}"]="$page?table=yes&instance-id=$instance_id";
	$array["{statistics} {queue}"]="$page?stats-mailqueue=yess&instance-id=$instance_id";
	$array["{statistics} {messages}"]="$page?stats-mailstats=yess&instance-id=$instance_id";
	$array["{statistics} {volumes}"]="$page?stats-mailvolume=yess&instance-id=$instance_id";
	echo $tpl->tabs_default($array);
    return true;
}
function table():bool{
    $instance_id=intval($_GET["instance-id"]);
    $page=CurrentPageName();
    echo "<div id='postfix-status-div'></div>
    <script>LoadAjaxSilent('postfix-status-div','$page?table2=yess&instance-id=$instance_id');</script>
    ";
    return true;
}

function tiny_instance($instance_id=0):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $POSTFIX_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
    $postfix_restart=$tpl->framework_buildjs(
        "postfix2.php?multi-restart=$instance_id",
        "postfix-multi.$instance_id.restart.progress",
        "postfix-multi.$instance_id.restart.progress.log",
        "progress-postfix-restart"

    );

    $postfix_reconfigure=$tpl->framework_buildjs(
        "postfix2.php?multi-reconfigure-single=$instance_id",
        "postfix-multi.$instance_id.reconfigure.progress",
        "postfix-multi.$instance_id.reconfigure.log",
        "progress-postfix-restart"
    );

    $postfix_reinstall="Loadjs('$page?instance-reinstall-js=$instance_id')";

    $bts[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    $bts[]="<label class=\"btn btn btn-primary\" OnClick=\"$postfix_restart;\"><i class='".ico_refresh."'></i> {restart} </label>";

    $bts[]="<label class=\"btn btn btn-warning\" OnClick=\"$postfix_reinstall;\"><i class='".ico_cd."'></i> {reinstall} </label>";

    $bts[]="<label class=\"btn btn btn-blue\" OnClick=\"$postfix_reconfigure;\"><i class='".ico_configure."'></i> {reconfigure} </label>";

    $bts[]="</div>";

    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $sql="SELECT instancename FROM  `postfix_instances` WHERE ID=$instance_id";
    $ligne=$q->mysqli_fetch_array($sql);
    $instancename=$ligne["instancename"];
    $title=$tpl->_ENGINE_parse_body("$instancename v$POSTFIX_VERSION &raquo;&raquo; {service_status}");


    $TINY_ARRAY["TITLE"]=$title;
    $TINY_ARRAY["ICO"]=" fa-solid fa-gauge-circle-bolt";
    $TINY_ARRAY["EXPL"]="{APP_POSTFIX_TEXT}";
    $TINY_ARRAY["URL"] = "postfix-status-$instance_id";
    $TINY_ARRAY["BUTTONS"]=@implode("",$bts);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

}

function table2():bool{
    $instance_id=intval($_GET["instance-id"]);
	$page=CurrentPageName();
	$tpl=new template_admin();
    $mulitple=null;
    $headsjs=null;
    VERBOSE("Instance ID = $instance_id");




	$html[]="<table style='width:100%'>";
	$html[]="<tr>";
	$html[]="<td style='width:260px;vertical-align: top;'>";
    $html[]="    <div id='left-postfix-status-$instance_id'></div>";
    $html[]="    <div id='socks-connection-$instance_id'></div>";
    $html[]="    <div id='milter-greylist-status'></div>";
	$html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:5px'>";
    $html[]="<div id='right-top-status' style='margin-top:-5px'></div>";
	$html[]="<div id='right-status' style='margin-top:0px'></div>";
	$html[]="</td>";
	$html[]="</tr>";
	$html[]="</table>";
	$html[]="<script>";
    $html[]=$headsjs;
    $AutoJs=$tpl->RefreshInterval_js("left-postfix-status-$instance_id",$page,"left-postfix-status=$instance_id");

	$html[]="LoadAjax('milter-greylist-status','$page?milter-greylist-status=yes&instance-id=$instance_id');";

    $html[]="LoadAjaxSilent('right-top-status','$page?right-top-status=yes&instance-id=$instance_id');";
    $html[]="LoadAjaxSilent('right-status','$page?right-status=yes&instance-id=$instance_id');";
    $html[]=$AutoJs;
	$html[]="</script>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	return true;
}
function right_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    $postqueuepInt=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_QUEUENUM"));
    $postqueuep=FormatNumber($postqueuepInt);
    $postqueuep_time=date("H:i:s",intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_QUEUENUM_TIME")));
    $SMTP_REFUSED_INT=FormatNumber($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CountOfSMTPThreats"));

    $main=new maincf_multi($instance_id);
    $freeze_delivery_queue=intval($main->GET("freeze_delivery_queue"));
    if($freeze_delivery_queue==1) {
        $html[]=$tpl->widget_style1("red-bg","fa-stop-circle","{WARN_QUEUE_FREEZE}","{WARN_QUEUE_FREEZE");
    }

    if($postqueuepInt==0){
        $html[] = $tpl->widget_style1("gray-bg", "fa-list-ul", "{queue} ($postqueuep_time)", "{none}");
    }else {
        $html[] = $tpl->widget_style1("lazur-bg", "fa-list-ul", "{queue} ($postqueuep_time)", $postqueuep);
    }
    if($SMTP_REFUSED_INT==0) {
        $html[] = $tpl->widget_style1("gray-bg", "fa-list-ul", "{refused}", "{none}");
    }else{
        $html[] = $tpl->widget_style1("red-bg", "fa-list-ul", "{refused}", $SMTP_REFUSED_INT);
    }



    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function left_postfix_status_master(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("/postfix/master/status");
    if(!function_exists("json_decode")){
        echo $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");
        return true;
    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    $postfix_restart="Loadjs('$page?restart-postfix-js=0')";
    $html[]=$tpl->SERVICE_STATUS($bsini, "APP_POSTFIX",$postfix_restart);
    $html[]="<script>";
    $html[]="LoadAjaxSilent('socks-connection-0','$page?socks-connection=yes&instance-id=0')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function left_frontail_status():bool{
    $EnableFrontail=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFrontail"));
    if($EnableFrontail==0){
        return false;
    }
    $tpl=new template_admin();
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/frontail/postfix/status");
    if(!function_exists("json_decode")){
        echo $tpl->widget_rouge("{error}","json_decode no such function, please restart Web console");
        return true;
    }

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}",json_last_error_msg());
        return true;
    }
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    $restart=$tpl->framework_buildjs("/frontail/postfix/restart",
        "frontail.install.progress","frontail.install.log","progress-postfix-restart");

    echo $tpl->SERVICE_STATUS($bsini, "APP_FRONTAIL_MAILLOG",$restart);


return true;
}

function left_postfix_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["left-postfix-status"]);
    $postfix_restart=null;

    if($instance_id==0) {
        left_postfix_status_master();
        left_frontail_status();
        return true;
    }

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("postfix2.php?instance-status=$instance_id");
    $ini=new Bs_IniHandler(PROGRESS_DIR . "/postfix.$instance_id.status");
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_POSTFIX",$postfix_restart);
    left_frontail_status();
    $html[]="<script>";
    $html[]="LoadAjaxSilent('socks-connection-$instance_id','$page?socks-connection=yes&instance-id=$instance_id')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function top_status():bool{
    $EnableDKFilter=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDKFilter"));
    $page=CurrentPageName();
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);

    $html[]="<table style='width:100%;'>";
    $html[]="<td style='width:33%'>";

    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
    if($FireHolEnable==0){
        $html[]=$tpl->widget_style1("gray-bg","fa fa-thumbs-down","{APP_FIREWALL_REPUTATION}","{disabled}");
    }else{
        $PostFixAutopIpsets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostFixAutopIpsets"));
        if($PostFixAutopIpsets==0){
            $html[]=$tpl->widget_style1("gray-bg","fa fa-thumbs-down","{APP_FIREWALL_REPUTATION}","{disabled}");
        }else{
            $html[]=$tpl->widget_style1("navy-bg","fa fa-thumbs-up","{APP_FIREWALL_REPUTATION}","{active2}");
        }

    }
    $html[]="</td>";
    $html[]="<td style='width:33%;padding-left:5px'>";




    if($EnableDKFilter==0){
        $html[]=$tpl->widget_style1("gray-bg","fa fa-thumbs-down","{APP_OPENDKIM}","{disabled}");
    }else{
        $html[]=$tpl->widget_style1("navy-bg","fa fa-thumbs-up","{APP_OPENDKIM}","{active2}");

    }
    $html[]="</td>";
    $html[]="<td style='width:33%;padding-left:5px'>";


    $EnableMilterGreylistExternalDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterGreylistExternalDB"));
    if($EnableMilterGreylistExternalDB==0){
        $html[]=$tpl->widget_style1("gray-bg","fa fa-thumbs-down","{articatech_rbl}","{disabled}");
    }else{
        $html[]=$tpl->widget_style1("navy-bg","fa fa-thumbs-up","{articatech_rbl}","{active2}");
    }
    $widget_multiple_instances=widget_multiple_instances();
    $widget_artica_milter=widget_artica_milter();
    $widget_milter_greylist=widget_milter_greylist();

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_multiple_instances</td>";
    $html[]="<td style='width:33%;padding-left: 5px'>$widget_artica_milter</td>";
    $html[]="<td style='width:33%;padding-left: 5px'>$widget_milter_greylist</td>";
    $html[]="</tr>";




    $instancename="SMTP Master";
    if($instance_id>0){
        $jstiny=tiny_instance($instance_id);
    }else {
        $POSTFIX_VERSION = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");
        $TINY_ARRAY["TITLE"] = "{APP_POSTFIX} &raquo;&raquo; {service_status} <small>($instancename) v.$POSTFIX_VERSION</small>";

        $EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));

        if($EnablePostfixMultiInstance==1){
            $TINY_ARRAY["TITLE"] = "{InternalRouter} &raquo;&raquo; {service_status} <small>($instancename) v.$POSTFIX_VERSION</small>";

        }

        $EnableFrontailPostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFrontailPostfix"));
        if($EnableFrontailPostfix==1) {
            $topbuttons[] = array("s_PopUp('/maillog/',1024,768,'Mail.log')", ico_eye, "{APP_FRONTAIL_MAILLOG}");
        }


        $TINY_ARRAY["ICO"] = "fas fa-tachometer-alt";
        $TINY_ARRAY["EXPL"] = "{APP_POSTFIX_TEXT}";
        $TINY_ARRAY["URL"] = "postfix-status-$instance_id";
        $TINY_ARRAY["BUTTONS"] = $tpl->table_buttons($topbuttons);
        $jstiny = "Loadjs('fw.progress.php?tiny-page=" . urlencode(base64_encode(serialize($TINY_ARRAY))) . "');";
    }



    $html[]="</table>";
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function widget_milter_greylist():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ico="fa-solid fa-hourglass-clock";
    $button["help"]="https://wiki.articatech.com/en/smtp-service";

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_GREYLIST_INSTALLED"))==0){
        return $tpl->widget_h("grey",$ico,"{not_installed}","{MILTER_GREYLIST}",$button);
    }
    $MilterGreyListEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListEnabled"));
    if($MilterGreyListEnabled==0){
        return $tpl->widget_h("grey",$ico,"{not_installed}","{MILTER_GREYLIST}",$button);
    }
    return $tpl->widget_h("grey",$ico,"{not_installed}","{MILTER_GREYLIST}",$button);




}
function widget_artica_milter():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $EnableArticaMilter=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMilter"));
    if($EnableArticaMilter==1){

        $jsremove=$tpl->framework_buildjs(
            "articamilter.php?uninstall=yes",
            "articamilter.progress",
            "articamilter.log","progress-postfix-restart",
            "LoadAjaxSilent('right-status','$page?right-status=yes');"
        );

        $button["name"] = "{uninstall}";
        $button["js"] = $jsremove;

        return $tpl->widget_h("green","fa-solid fa-filter-list","{active2}","Artica Milter",$button);

    }

        $install=$tpl->framework_buildjs(
            "articamilter.php?install=yes",
            "articamilter.progress",
            "articamilter.log","progress-postfix-restart",
            "LoadAjaxSilent('right-status','$page?right-status=yes');"
        );

        $button["ico"]=ico_cd;
        $button["name"] = "{install}";
        $button["js"] = $install;
     return $tpl->widget_h("grey","fa-solid fa-filter-circle-xmark","{disabled}","Artica Milter",$button);

}
function widget_multiple_instances():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ico="fa-solid fa-layer-group";
    $EnablePostfixMultiInstance = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));

    $postfix_install_instances = $tpl->framework_buildjs("postfix2.php?multi-install=yes",
        "postfix-multi.progress", "postfix-multi.progress.log", "progress-postfix-restart",
        "LoadAjax('table-postfix','$page?table=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');");

    $postfix_remove_instances = $tpl->framework_buildjs("postfix2.php?multi-uninstall=yes",
        "postfix-multi.progress", "postfix-multi.progress.log", "progress-postfix-restart",
        "LoadAjax('table-postfix','$page?table=yes');LoadAjaxSilent('left-barr','fw-left-menus.php?nothing=yes');");

    if ($EnablePostfixMultiInstance == 0) {
        $button["ico"]=ico_cd;
        $button["name"] = "{install}";
        $button["js"] = $postfix_install_instances;

        return $tpl->widget_h("grey",$ico,"{disabled}","{multiple_instances}", $button);
    }
        $q = new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $postfix_instances = intval($q->COUNT_ROWS("postfix_instances"));
        $button["ico"]=ico_trash;
        $button["name"] = "{uninstall}";
        $button["js"] = $postfix_remove_instances;
        return $tpl->widget_h("green",$ico,"$postfix_instances {instances}","{multiple_instances}", $button);




}
function sock_connection():bool{
    $tpl=new template_admin();
    $instance_id=intval($_GET["instance-id"]);
    if($instance_id==0){return false;}
    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");


    $sql="SELECT * FROM `postfix_instances` WHERE ID=$instance_id";
/*        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
        `instancename` TEXT NOT NULL,
         interface TEXT NOT NULL,
        `enabled` INTEGER NOT NULL DEFAULT 1);";
*/
    $ligne=$q->mysqli_fetch_array($sql);
    $interface=$ligne["interface"];
    $enabled=intval($ligne["enabled"]);

    if($enabled==0){
        echo $tpl->widget_grey("$interface:25","{inactive}",null,ico_interface);
        return true;
    }

    $net=new networking();
    $Ipaddr=$net->InterfaceToIPv4($interface);

    $fp=@fsockopen($Ipaddr, 25, $errno, $errstr, 2);
    if(!$fp){
        echo $tpl->widget_rouge("$Ipaddr:25 - $errstr","{error} $errno",null,ico_interface);
        return true;
    }
    echo $tpl->widget_vert("$interface:25","{connected}",null,ico_interface);
    @fclose($fp);
    return true;
}

function stats_mailqueue():bool{
	
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="postfix_mailqueue-day.png";
	$f[]="postfix_mailqueue-week.png";
	$f[]="postfix_mailqueue-month.png";
	$f[]="postfix_mailqueue-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
	return true;
}

function stats_mailstats(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="postfix_mailstats-day.png";
	$f[]="postfix_mailstats-week.png";
	$f[]="postfix_mailstats-month.png";
	$f[]="postfix_mailstats-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
}
function stats_mailvolume(){
	$t=time();
	$path="/var/cache/munin/www/localdomain/localhost.localdomain";
	$f[]="postfix_mailvolume-day.png";
	$f[]="postfix_mailvolume-week.png";
	$f[]="postfix_mailvolume-month.png";
	$f[]="postfix_mailvolume-year.png";
	$OUTPUT=false;
	foreach ($f as $image){
		if(is_file("$path/$image")){
			$OUTPUT=true;
			echo "<center style='margin-top:10px;padding:5px;background-color:#F0F0F0;border:1px solid #CCCCCC;border-radius: 5px 5px 5px 5px;'><img src='munin-images/$image?$t'></center>";
		}
	
	
	}
	
	if(!$OUTPUT){
	$tpl=new template_admin();
	echo $tpl->_ENGINE_parse_body("<div class='alert alert-danger' style='margin-top:10px'>{error_no_generated_graphs}</div>");
	}
}

function milter_grey_list_status(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$ini=new Bs_IniHandler();
	
	if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MILTER_GREYLIST_INSTALLED"))==0){
		return;
	}
	
	
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork('cmd.php?milter-greylist-ini-status=yes');
	$ini->loadFile(PROGRESS_DIR."/greylist.status");
	
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.restart.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.restart.log";
	$ARRAY["CMD"]="milter-greylist.php?restart=yes";
	$ARRAY["TITLE"]="{restarting_service}";
	$ARRAY["AFTER"]="LoadAjax('table-greylist-rules','$page?tabs=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$milterrestart_js="Loadjs('fw.progress.php?content=$prgress&mainid=progress-greylist-restart');";
	
	echo $tpl->SERVICE_STATUS($ini, "MILTER_GREYLIST",$milterrestart_js);
	
}




function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}