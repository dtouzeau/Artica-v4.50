<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["upload-db-js"])){upload_db_js();exit;}
if(isset($_GET["upload-db-popup"])){upload_db_popup();exit;}
if(isset($_GET["uploadb"])){uploaded_db();exit;}
if(isset($_GET["download-db"])){download_db();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_POST["GeoIPRsync"])){Save();exit;}
if(isset($_GET["rsync-js"])){rsync_js();exit;}
if(isset($_GET["rsync-popup"])){rsync_popup();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["status-start"])){status_start();exit;}
if(isset($_POST["GeoIPAccount"])){Save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["client-js"])){client_js();exit;}
if(isset($_GET["client-popup"])){client_popup();exit;}
if(isset($_GET["search"])){client_search();exit;}
if(isset($_GET["needlogin-status"])){widget_needlogin();exit;}
if(isset($_GET["widget_date"])){widget_date();exit;}

page();
function parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{credentials}","$page?parameters-popup=yes");
}
function upload_db_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{load_databases_manually}","$page?upload-db-popup=yes");
}
function upload_db_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $bt_upload=$tpl->button_upload("{upload_backup}",$page,null,"&uploadb=yes")."&nbsp;&nbsp;";
    $html="<div class='center'>$bt_upload</div>
	<div id='progress-certificates-center-import'></div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function uploaded_db(){
    header("content-type: application/x-javascript");
    $tpl=new template_admin();
    $page=CurrentPageName();
    $file=urlencode($_GET["file-uploaded"]);

    $sock=new sockets();
    $json=json_decode($sock->REST_API("/geoip/import/$file"));

    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;

    }

    echo "LoadAjax('maxmind-geoip-status','$page?status=yes');dialogInstance2.close();";
    return admin_tracks("GeoIP database $file uploaded");
}
function rsync_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{update_from_articasrv}","$page?rsync-popup=yes");
}
function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $sock=new sockets();
    $sock->REST_API("/system/geopip/update");
}
function client_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog12("{geoip} {search} {ipaddr}","$page?client-popup=yes");
}
function client_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(isset($_GET["search"])){$_GET["search"]=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}
    $ipaddr=$_GET["search"];
    if(strlen($ipaddr)<4){
        return false;
    }
    $IP=new IP();
    if(!$IP->isValid($ipaddr)){
        echo $tpl->div_error("$ipaddr {invalid}");
        return false;
    }
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/geoip/query/$ipaddr"));
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

    if(!property_exists($json,"Info")){
        echo $tpl->div_error("Missing property Info");
        return false;
    }
    $Data=$json->Info;
    $tpl->table_form_field_text("{continent}",$Data->Continent->Names->en,ico_earth);
    $tpl->table_form_field_text("{country}",$Data->Country->Names->en ."&nbsp;/&nbsp;".$Data->Country->IsoCode,ico_earth);
    $tpl->table_form_field_text("{timezone}",$Data->Location->TimeZone,ico_clock);
echo $tpl->table_form_compile();


    return true;
}
function download_db():bool{

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/geoip/export");
    $filepath="/usr/share/artica-postfix/ressources/logs/GeoIP.tar.gz";
    header('Content-type: application/x-tar');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"GeoIP.tar.gz\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    $fsize = filesize($filepath);
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($filepath);
    return true;

}

function client_popup(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo $tpl->search_block($page);
}
function page():bool{
	$page       = CurrentPageName();
	$tpl        = new template_admin();
    $html=$tpl->page_header("{APP_GEOIP}",ico_database,
        "{APP_GEOIPUPDATE_ABOUT}","$page?tabs=yes","geoip-database",
        "progress-redis-restart",false,"table-loader-geoip-service");
	

	
	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_GEOIP}",$html);
		echo $tpl->build_firewall();
		return true;
	}
	

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?status-start=yes";
    //$array["{databases}"]="$page?table=yes";
   // $array["{events}"]="$page?events=yes";
    echo $tpl->tabs_default($array);

}
function status_start():bool{
    $page=CurrentPageName();
    echo "<div id='maxmind-geoip-progress'></div><div id='maxmind-geoip-status'></div>";
    echo "<script>LoadAjax('maxmind-geoip-status','$page?status=yes');</script>";
    return true;
}

function widget_needlogin():bool{
    $GeoIPAccount=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPAccount"));
    $GeoIPKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPKey"));
    $tpl=new template_admin();
    $GeoIPRsync=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsync"));
    if($GeoIPAccount==null || $GeoIPKey==null){
        $btn["help"]="https://wiki.articatech.com/maintenance/geolocation";
        $needlogin_status=$tpl->widget_h("red","fas fa-sign-in-alt","{need_sign_in}","{error}",$btn);
    }else{
        $btn["help"]="https://wiki.articatech.com/en/maintenance/geolocation";
        $needlogin_status=$tpl->widget_h("blue","fas fa-sign-in-alt","{active_session} ","{credentials}",$btn);

    }
    if($GeoIPRsync==0){
        echo $tpl->_ENGINE_parse_body($needlogin_status);
        return true;
    }

    $GeoIPRsyncServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsyncServer"));
    $GeoIPRsyncServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsyncServerPort"));
    $GeoIPRsyncInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsyncInterface"));


    if($GeoIPRsync==1){

        $GEO_RSYNC=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GEO_RSYNC");
        if(strlen($GEO_RSYNC)>1){
            $btn["help"]="https://wiki.articatech.com/maintenance/geolocation";
            $needlogin_status=$tpl->widget_h("red","fas fa-sign-in-alt","{error}",$GEO_RSYNC,$btn);
            echo $tpl->_ENGINE_parse_body($needlogin_status);
            return true;
        }

        $INTERF="NONE";
        if(strlen($GeoIPRsyncInterface>2)){
            $INTERF=$GeoIPRsyncInterface;
        }
        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/rsyncd/testclient/$GeoIPRsyncServer/$GeoIPRsyncServerPort/$INTERF"));
        if(!$data->Status){
            $btn["help"]="https://wiki.articatech.com/maintenance/geolocation";
            $needlogin_status=$tpl->widget_h("red","fas fa-sign-in-alt",$data->Error,"{error}",$btn);
            echo $tpl->_ENGINE_parse_body($needlogin_status);
            return true;
        }
        $btn["help"]="https://wiki.articatech.com/en/maintenance/geolocation";
        $needlogin_status=$tpl->widget_h("blue",ico_ok,$GeoIPRsyncServer,"{remote_server} ",$btn);
        echo $tpl->_ENGINE_parse_body($needlogin_status);
        return true;

    }
    return false;
}

function widget_date():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $afterjs="LoadAjax('maxmind-geoip-status','$page?status=yes')";
    $GEOIP_LAST_UPDATED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GEOIP_LAST_UPDATED"));
    $RunUpdate=$tpl->framework_buildjs("/system/geopip/update",
        "GeoipUpdate.progress","GeoipUpdate.log","maxmind-geoip-progress",$afterjs,$afterjs);
    $btn["ico"]=ico_download;
    $btn["name"]="{update2}";
    $btn["js"]=$RunUpdate;

    if($GEOIP_LAST_UPDATED>0){
        $datetime=$tpl->time_to_date($GEOIP_LAST_UPDATED,true);
        echo $tpl->_ENGINE_parse_body($tpl->widget_h("green",ico_download,"$datetime","{last_update}",$btn));
        return true;
    }

    $status_date=$tpl->widget_h("yellow",ico_download,"{none}","{last_update}",$btn);
    echo $tpl->_ENGINE_parse_body($status_date);
    return true;
}

function status(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $GeoIPAccount=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPAccount"));
    $GeoIPKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPKey"));

    $afterjs="LoadAjax('maxmind-geoip-status','$page?status=yes')";
    $GeoIPRsync=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsync"));
    $GeoIPRsyncServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsyncServer"));
    $GeoIPRsyncServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsyncServerPort"));

    $RunUpdate=$tpl->framework_buildjs("/system/geopip/update",
        "GeoipUpdate.progress","GeoipUpdate.log","maxmind-geoip-progress",$afterjs,$afterjs);


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/system/geopip/databases"));
    $resultsDB=unserialize($json->Info);
    if(!is_array($resultsDB)){
        $resultsDB=array();
    }
    $resultsDBC=count($resultsDB);

    unset($btn);
    $btn["ico"]=ico_download;
    $btn["name"]="{update2}";
    $btn["js"]=$RunUpdate;

    if($resultsDBC>2){

        $btn["ico"]=ico_download;
        $btn["name"]="{download}";
        $btn["js"]= "document.location.href='/$page?download-db=yes';";

        $status_db=$tpl->widget_h("green","fas fa-database","$resultsDBC","{databases}",$btn);
    }else{
        $status_db=$tpl->widget_h("red","fas fa-database","{error}","{databases}",$btn);
    }



    $html[]="<div style='margin-left:10px;margin-top:5px'>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'><div id='needlogin_status'></div></td>";
    $html[]="<td style='padding-left:10px;width:33%'>$status_db</td>";
    $html[]="<td style='padding-left:10px;width:33%'><div id='widget_date'></div></td>";
    $html[]="</tr>";
    $html[]="</table></div>";


    $GeoIPUpdateError=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPUpdateError");
    if(strlen($GeoIPUpdateError)>2){
        $GeoIPUpdateErrorTimeText="";
        $GeoIPUpdateErrorTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPUpdateErrorTime"));
        if(strpos($GeoIPUpdateError,"429 Too Many Requests")>0){
            $GeoIPUpdateError="Too Many Requests";
        }
        if($GeoIPUpdateErrorTime>0){
            $GeoIPUpdateErrorTimeText=" (".distanceOfTimeInWords($GeoIPUpdateErrorTime,time()).")";
        }
        $tpl->table_form_field_text("{update_error}",$GeoIPUpdateError.$GeoIPUpdateErrorTimeText,ico_error,true);
    }

    if($GeoIPRsync==0) {
        $tpl->table_form_field_js("Loadjs('$page?rsync-js=yes')", "AsSystemAdministrator");
        $tpl->table_form_field_bool("{update_from_articasrv}", 0, ico_server);
        $tpl->table_form_field_js("Loadjs('$page?parameters-js=yes')", "AsSystemAdministrator");
        $tpl->table_form_section("{credentials}");
        $tpl->table_form_field_text("{account}", $GeoIPAccount, ico_user);
        $tpl->table_form_field_text("{authorizationkey}", "<span style='text-decoration:none'>$GeoIPKey</span>", ico_key);
    }else{
        $tpl->table_form_field_js("Loadjs('$page?rsync-js=yes')", "AsSystemAdministrator");
        $tpl->table_form_field_text("{update_from_articasrv}","$GeoIPRsyncServer:$GeoIPRsyncServerPort",ico_server);
    }

    $tpl->table_form_field_js("");
    $sock=new sockets();
    $data=$sock->REST_API("/geoip/databases");
    $tpl->table_form_section("{databases}");
    $json=json_decode($data);
    if(property_exists($json,"Data")) {
        foreach ($json->Data as $index => $json2) {

            $db = $json2->filename;
            $time = $json2->time;
            $size = $json2->size;

            $date_text = $tpl->time_to_date($time, true);
            $date_explain = distanceOfTimeInWords(time(), $time);
            $size_text = FormatBytes($size / 1024);

            $tpl->table_form_field_text($db, "$size_text, {updated} $date_text ($date_explain)", ico_database);

        }
    }

    $load="Loadjs('$page?upload-db-js=yes');";
    $topbuttons[] = array($load, ico_upload, "{load_databases_manually}");
    $TINY_ARRAY["TITLE"]="{APP_GEOIP}: {skins}";
    $TINY_ARRAY["ICO"]=ico_database;
    $TINY_ARRAY["EXPL"]="{APP_GEOIPUPDATE_ABOUT}";
    $TINY_ARRAY["URL"]="geoip-database";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]=$tpl->table_form_compile();
    $js=$tpl->RefreshInterval_js("needlogin_status",$page,"needlogin-status=yes");
    $js2=$tpl->RefreshInterval_js("widget_date",$page,"widget_date=yes");
    $html[]="<script>$js;$js2;$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);

}
function rsync_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $GeoIPRsync=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsync"));
    $GeoIPRsyncServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsyncServer"));
    $GeoIPRsyncServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsyncServerPort"));
    $GeoIPRsyncInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPRsyncInterface"));
    if($GeoIPRsyncServerPort==0){
        $GeoIPRsyncServerPort=873;
    }
    $form[]= $tpl->field_checkbox("GeoIPRsync","{update_from_articasrv}",$GeoIPRsync,true);
    $form[]= $tpl->field_text("GeoIPRsyncServer","{remote_server_address}",$GeoIPRsyncServer);
    $form[]= $tpl->field_numeric("GeoIPRsyncServerPort","{remote_port}",$GeoIPRsyncServerPort);
    $form[]= $tpl->field_interfaces("GeoIPRsyncInterface","{outgoing_interface}",$GeoIPRsyncInterface);
    $html[]= $tpl->form_outside("",$form,null,"{apply}", "LoadAjax('maxmind-geoip-status','$page?status=yes');dialogInstance2.close();", "AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function parameters_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $GeoIPAccount=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPAccount"));
    $GeoIPKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GeoIPKey"));


        $form[]= $tpl->field_text("GeoIPAccount","{account}",$GeoIPAccount);
        $form[]= $tpl->field_text("GeoIPKey","{authorizationkey}",$GeoIPKey);
    $html[]= $tpl->form_outside("Maxmind: {credentials}",$form,null,"{apply}", "LoadAjax('maxmind-geoip-status','$page?status=yes');dialogInstance2.close();", "AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}



function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}