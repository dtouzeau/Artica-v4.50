<?php
$GLOBALS["CLUSTER_PORT_FEATURE"]=true;
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
include_once(dirname(__FILE__)."/ressources/class.snapshots.blacklists.inc");
include_once(dirname(__FILE__)."/ressources/externals/class.aesCrypt.inc");


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded_js();exit;}
if(isset($_GET["client-certificate-js"])){client_certificate_js();exit;}
if(isset($_GET["client-certificate-popup"])){client_certificate_popup();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["parameters-popup"])){parameters();exit;}
if(isset($_GET["parameters-start"])){parameters_start();exit;}
if(isset($_GET["parameters-flat"])){parameters_flat();exit;}

if(isset($_GET["status"])){status();exit;}
if(isset($_POST["PowerDNSClusterSlaveInterface"])){Save();exit;}
if(isset($_GET["freeze"])){freeze();exit;}
page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("Cluster ({slave_mode})",
        "fas fa-clone","{pdns_slave_mode_explain}","$page?tabs=yes",
        "cluster-client","progress-cluster-restart",false,"table-cluster-client");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Cluster ({slave_mode})",$html);
        echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body( $html);

}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{parameters}"]="$page?parameters-start=yes";
    $array["{events}"]="fw.system.cluster.events.php";
    echo $tpl->tabs_default($array);
    return true;
}
function parameters_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{cluster_configuration}","$page?parameters-popup=yes");
}
function client_certificate_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("{client_certificate}","$page?client-certificate-popup=yes",650);
}
function client_certificate_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<div style='margin:30px'>";
    $html[]="<H2>{client_certificate}</H2>";
    $html[]="<p style='font-size:14px'>{import_client_certificate_cluster}</p>\n";
    $html[]="<div class='center'>". $tpl->button_upload("{upload}",$page,null)."</div>";
    $html[]="</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function file_uploaded_js():bool{
    $filename=urlencode($_GET["file-uploaded"]);
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cluster/client/certificate/import/$filename"));
    header("content-type: application/x-javascript");

    if(!$json->Status){
        $tpl->js_error($json->Error);
        return false;
    }
    echo "dialogInstance1.close();LoadAjax('form-flat','$page?parameters-flat=yes');";
    return true;

}

function parameters_start(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:400px;vertical-align:top'>";
    $html[]="<div id='cluster-status' style='width:400px;vertical-align: top'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align: top'>";
    $html[]="<div id='form-flat'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $js=$tpl->RefreshInterval_js("cluster-status",$page,"status=yes");
    $html[]="<script>";
    $html[]=$js;
    $html[]="LoadAjax('form-flat','$page?parameters-flat=yes');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);

}
function parameters_flat(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $PowerDNSClusterSlaveInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterSlaveInterface"));
    $PowerDNSClusterMasterAddress = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterAddress"));
    $PowerDNSClusterMasterPort = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterPort"));


    if ($PowerDNSClusterMasterPort == 0) {
        $PowerDNSClusterMasterPort = 58787;
    }

    $jsRestart=$tpl->framework_buildjs("/cluster/client/download",
        "artica.cluster.progress","artica.cluster.log","progress-cluster-restart",
        "LoadAjax('form-flat','$page?parameters-flat=yes');");

    $jsRestartV2=$tpl->framework_buildjs("/cluster/client/v2/download",
        "artica.cluster.progress","artica.cluster.log","progress-cluster-restart",
        "LoadAjax('form-flat','$page?parameters-flat=yes');");

    $jsRestartForce=$tpl->framework_buildjs("/cluster/client/force",
        "artica.cluster.progress","artica.cluster.log","progress-cluster-restart",
        "LoadAjax('form-flat','$page?parameters-flat=yes');");

    $jsRestartForceV2=$tpl->framework_buildjs("/cluster/client/v2/force",
        "artica.cluster.progress","artica.cluster.log","progress-cluster-restart",
        "LoadAjax('form-flat','$page?parameters-flat=yes');");

    $HaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==0) {
        $ClientCertificate = "";
        $PowerDNSClusterSlaveCertificate = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterSlaveCertificate");

        if (strlen($PowerDNSClusterSlaveCertificate) > 10) {
            $json = json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterSlaveCertificate"));

            if (property_exists($json, "Subject")) {
                $Exploded = explode(",", $json->Subject);
                $ClientCertificate = $Exploded[0];
                foreach ($Exploded as $Value) {
                    if (preg_match("#OU=(.+)#i", $Value, $re)) {
                        $ClientCertificate = $ClientCertificate . " /$re[1]";
                    }
                    if (preg_match("#O=(.+)#i", $Value, $re)) {
                        $ClientCertificate = $ClientCertificate . " /$re[1]";
                    }
                }
            }
        }
        $tpl->table_form_field_js("Loadjs('$page?client-certificate-js=yes')");
        if ($ClientCertificate == "") {
            $tpl->table_form_field_bool("{client_certificate}", 0, ico_certificate);
        } else {
            $tpl->table_form_field_text("{client_certificate}", $ClientCertificate, ico_certificate);
        }





        $PowerDNSEnableClusterSlaveSchedule = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlaveSchedule"));
        if ($PowerDNSEnableClusterSlaveSchedule < 5) {
            $PowerDNSEnableClusterSlaveSchedule = 15;
        }



        $MINS[5] = "{each} 5 {minutes}";
        $MINS[10] = "{each} 10 {minutes}";
        $MINS[15] = "{each} 15 {minutes}";
        $MINS[20] = "{each} 20 {minutes}";
        $MINS[30] = "{each} 30 {minutes}";

        $tpl->table_form_field_js("Loadjs('$page?parameters-js=yes')");
        $tpl->table_form_field_text("{master_address}", "$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort", ico_server);
        if (strlen($PowerDNSClusterSlaveInterface) < 2) {
            $tpl->table_form_field_text("{outgoing_interface}", "{all}", ico_nic);
        }
        $tpl->table_form_field_text("{schedule}",$MINS[$PowerDNSEnableClusterSlaveSchedule],ico_clock);

        $html[]=$tpl->table_form_compile();
    }

    $topbuttons=array();


    if($PowerDNSClusterMasterAddress<>null) {
        $users=new usersMenus();
        if ($users->AsSystemAdministrator) {
            $topbuttons[] = array($jsRestart, ico_refresh, "{synchronize}");
            $topbuttons[] = array($jsRestartForce, ico_refresh, "{force_synchronize}");
        }
        $PowerDNSClusterSlaveCertificate = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterSlaveCertificate");

        if(strlen($PowerDNSClusterSlaveCertificate)>10) {
            $topbuttons=array();
            $topbuttons[] = array($jsRestartV2, ico_refresh, "{synchronize}");
            $topbuttons[] = array($jsRestartForceV2, ico_refresh, "{force_synchronize}");
        }
    }

    $jstiny2="";

    if($HaClusterClient==1){
        $html[]="<div style='padding-left:15px'><H1>{APP_HACLUSTER_CLIENT}</H1><div id='hacluster-client-flat'></div></div>";
        $jstiny2="LoadAjax('hacluster-client-flat','fw.dashboard.HaCluster.php?flat=yes');";
    }


    $TINY_ARRAY["TITLE"]="Cluster ({slave_mode})";
    $TINY_ARRAY["ICO"]="fas fa-clone";
    $TINY_ARRAY["EXPL"]="{pdns_slave_mode_explain}";
    $TINY_ARRAY["URL"]="cluster-client";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="<script>";
    $html[]=$jstiny;
    $html[]=$jstiny2;
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);

}
function parameters():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $jsRestart="dialogInstance1.close();LoadAjax('form-flat','$page?parameters-flat=yes');".$tpl->framework_buildjs("/cluster/client/download",
        "artica.cluster.progress","artica.cluster.log","progress-cluster-restart",
        "LoadAjax('table-cluster-client','$page?tabs=yes');");

    $PowerDNSClusterSlaveInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterSlaveInterface"));
    $PowerDNSClusterMasterAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterAddress"));
    $PowerDNSClusterMasterPort=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterPort"));
    $PowerDNSEnableClusterSlaveSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlaveSchedule"));
    if($PowerDNSEnableClusterSlaveSchedule<5){$PowerDNSEnableClusterSlaveSchedule=15;}

    if($GLOBALS["CLUSTER_PORT_FEATURE"]) {
        if ($PowerDNSClusterMasterPort < 5) {
            $PowerDNSClusterMasterPort = 58787;
        }
    }else{
        if ($PowerDNSClusterMasterPort < 5) {
            $PowerDNSClusterMasterPort = 9000;
        }
    }

    $MINS[5]="{each} 5 {minutes}";
    $MINS[10]="{each} 10 {minutes}";
    $MINS[15]="{each} 15 {minutes}";
    $MINS[20]="{each} 20 {minutes}";
    $MINS[30]="{each} 30 {minutes}";

    $form[]=$tpl->field_text("PowerDNSClusterMasterAddress", "{master_address}", $PowerDNSClusterMasterAddress);
    $form[]=$tpl->field_numeric("PowerDNSClusterMasterPort","{remote_port}",$PowerDNSClusterMasterPort);

    $form[]=$tpl->field_interfaces("PowerDNSClusterSlaveInterface", "{outgoing_interface}", $PowerDNSClusterSlaveInterface);

    $form[]=$tpl->field_array_hash($MINS,"PowerDNSEnableClusterSlaveSchedule","{schedule}",$PowerDNSEnableClusterSlaveSchedule);
    echo $tpl->form_outside("", $form,null,"{apply}",$jsRestart,"AsSystemAdministrator",true);
return true;

}

function Save():bool{
    $tpl=new template_admin();

    $PowerDNSClusterMasterAddress=$_POST["PowerDNSClusterMasterAddress"];
    $PowerDNSClusterMasterPort=$_POST["PowerDNSClusterMasterPort"];
    $tpl->SAVE_POSTs();

    if($GLOBALS["CLUSTER_PORT_FEATURE"]){
        $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cluster/client/v2/status"));

        if(!$data->Status){
            echo "jserror: $data->Error";
            return false;
        }

        return true;
    }


    $uri="https://$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort/pdns-cluster/index.txt";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_URL, "$uri");
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $data=curl_exec($ch);
    $CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    $curl_errno=curl_errno($ch);

    $error=curl_errno($ch);
    if($error>0){
        $text=curl_error($ch);
        echo "jserror:".$tpl->_ENGINE_parse_body("{error} $error $text");
        return false;
    }

    if($CURLINFO_HTTP_CODE>200){
        echo "jserror:".$tpl->_ENGINE_parse_body("https://$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort<br>{error} $CURLINFO_HTTP_CODE");
        return false;
    }

    $data=unserialize(base64_decode($data));

    if(!isset($ARRAY["UUID"])){$ARRAY["UUID"]=null;}
    $uuid=base64_decode($GLOBALS["CLASS_SOCKETS"]->getFrameWork("cmd.php?system-unique-id=yes"));
    if($ARRAY["UUID"]<>null){
        if(trim($ARRAY["UUID"])==trim($uuid)){
            echo "jserror:Loop back to myself, same uuid";
            return false;
        }
    }

    $tpl->SAVE_POSTs();
return true;

}
function freeze():bool{
    $page=CurrentPageName();
    $PowerDNSClusterClientStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterClientStop"));

    if($PowerDNSClusterClientStop==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSClusterClientStop",1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSClusterClientStop",0);
    }
    header("content-type: application/x-javascript");
    echo "LoadAjax('cluster-status','$page?status=yes');";
    return true;
}

function package_statusv2():string{
    $tpl=new template_admin();
    $PowerDNSClusterClientStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterClientStop"));

    $ClusterSlaveInfo=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterSlaveInfo"));
    if (json_last_error()> JSON_ERROR_NONE) {
        $html=$tpl->widget_h("gray","fas fa-database","-","{last_sync}");
        return $tpl->_ENGINE_parse_body($html);
    }

    if(!$ClusterSlaveInfo->Status){
        $html=$tpl->widget_h("red","fas fa-database","{error}",$ClusterSlaveInfo->Error);
        return $tpl->_ENGINE_parse_body($html);
    }


    $ReplicTime=$ClusterSlaveInfo->ReplicTime;
    if($ReplicTime==0){
        $html=$tpl->widget_h("yellow","fas fa-database","-","{last_sync}");
        return $tpl->_ENGINE_parse_body($html);
    }



    $time=$tpl->time_to_date($ReplicTime,true);
    $Distance=distanceOfTimeInWords($ReplicTime,time());
    if($PowerDNSClusterClientStop==1){
        $html= $tpl->widget_h("gray","fas fa-database","$time","{last_sync} {cluster_replication_freezed}");
        return $tpl->_ENGINE_parse_body($html);
    }

    if(property_exists($ClusterSlaveInfo,"PackageVersion")){
        if(strlen($ClusterSlaveInfo->PackageVersion)>1){
        $html= $tpl->widget_h("green","fas fa-database",
            "<span style='font-size:22px'>v$ClusterSlaveInfo->PackageVersion</span>","{last_sync} $Distance");
        return $tpl->_ENGINE_parse_body($html).unbound_client_status();
        }
    }

    $html[]=$tpl->widget_h("green","fas fa-database","$time<br><small style='color:white;font-size:11px'>{since} $Distance</small>","{last_sync}");

    $html[]=unbound_client_status();
    return $tpl->_ENGINE_parse_body($html);
}
function unbound_client_status():string{
    $UnboundEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled");
    if ($UnboundEnabled==0){
        VERBOSE("UnboundEnabled=$UnboundEnabled",__LINE__);
        return "";

    }
    $tpl=new template_admin();
    $json_string=json_decode(file_get_contents("/etc/artica-postfix/UnboundCluster.json"));

    if ($json_string === false) {
        return $tpl->_ENGINE_parse_body($tpl->widget_h("grey","fas fa-exclamation-triangle","-","{APP_UNBOUND} {cluster_package}"));
    }
    $json = json_decode($json_string);

    if (!is_object($json)) {
        return $tpl->_ENGINE_parse_body($tpl->widget_h("grey","fas fa-exclamation-triangle","-","{APP_UNBOUND} {cluster_package}"));
    }

    if (!property_exists($json, "Time")) {
        return $tpl->_ENGINE_parse_body($tpl->widget_h("grey","fas fa-database","-","{APP_UNBOUND} {cluster_package}"));
    }
    $Time=$json->Time;
    $title_time=$tpl->time_to_date($Time,true);
    $time=distanceOfTimeInWords($Time,time());

    return $tpl->_ENGINE_parse_body($tpl->widget_h("green","fas fa-database","$Time<br><small style='color:white'>$time</small>","{cluster_package}: {APP_UNBOUND}<br>$title_time"));


}

function package_status():string{
    return package_statusv2();

}

function cluster_Status_haclient():string{
    $tpl=new template_admin();
    $HaClusterIP=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterIP");
    $HaClusterMyMasterAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterMyMasterAddr"));
    if(strlen($HaClusterMyMasterAddr)<3){
        return "";
    }

    if(strlen($HaClusterIP)<3){
        return $tpl->widget_style1("gray-bg", "fa-solid fa-wifi-slash", "{missing}", " {PostfixMasterServerIdentity}");
    }
    $connected_to="<small>$HaClusterIP&nbsp;<i class='".ico_arrow_right."'></i>&nbsp;$HaClusterMyMasterAddr</small>";
    return $tpl->widget_style1("navy-bg", ico_link,"{connected_to} $connected_to", "{linked}");
}

function cluster_Status():string{


    $tpl=new template_admin();
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){
        return cluster_Status_haclient();
    }

    $PowerDNSClusterSlaveCertificate = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterSlaveCertificate");
    $PowerDNSClusterMasterAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterAddress"));
    if(strlen($PowerDNSClusterMasterAddress)<3){
        return $tpl->widget_style1("gray-bg", "fa-solid fa-wifi-slash", "{missing}", " {PostfixMasterServerIdentity}");
    }
    if(strlen($PowerDNSClusterSlaveCertificate)<10){
        return $tpl->widget_style1("gray-bg", "fa-solid fa-wifi-slash", "{missing}", "{client_certificate}");
    }


    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cluster/client/v2/status"));
    if( !$data->Status) {
        $error="{disconnected}";
        if(preg_match("#tls:\s+(.+)#",$data->Error,$re)){
            $data->Error=$re[1];
            $error="{protocol_error}";
        }
        return $tpl->widget_style1("bg-red", "fa-solid fa-wifi-slash", $data->Error,$error);
    }
    if(!property_exists($data,"Info")){
        return $tpl->widget_style1("bg-red", "fa-solid fa-wifi-slash", "{error}", "{protocol_error}");
    }
    $json2=json_decode($data->Info);
    return $tpl->widget_style1("navy-bg", ico_link,"{connected_to} $json2->Hostname", "{linked}");
}

function status(){
   $tpl=new template_admin();
   $page=CurrentPageName();
   $PowerDNSClusterClientStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterClientStop"));


   if($GLOBALS["CLUSTER_PORT_FEATURE"]) {
       $html[]=cluster_Status();
   }

   $html[]=package_status();
   $html[]="<div style='margin:20px'>";

    if($PowerDNSClusterClientStop==1){

        $bt=$tpl->button_autnonome("{enable_replication}",
            "Loadjs('$page?freeze=yes')",ico_play,"AsSystemAdministrator",350,"btn-danger");

    }else{
        $bt=$tpl->button_autnonome("{freeze_replication}",
            "Loadjs('$page?freeze=yes')", "fa-stop","AsSystemAdministrator",350,"btn-primary");

    }
    $html[]=$bt;
    $html[]="</div>";

    echo $tpl->_ENGINE_parse_body($html);
    return false;
}