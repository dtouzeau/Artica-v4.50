<?php
//SP 100
$GLOBALS["CLUSTER_PORT_FEATURE"]=true;
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
include_once(dirname(__FILE__)."/ressources/class.snapshots.blacklists.inc");
if(isset($_GET["reload-www"])){reload_www();exit;}
if(isset($_GET["stop-www"])){stop_www();exit;}
if(isset($_GET["interface-js"])){interface_js();exit;}
if(isset($_GET["interface-popup"])){interface_popup();exit;}
if(isset($_GET["Launch"])){Launch();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["params-js"])){parameters_js();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["parameters-start"])){parameters_start();exit;}
if(isset($_GET["flat-parameters-cluster-master"])){flat_parameters();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["ClusterReplicateOfficalDatabases"])){Save();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_GET["remove-node"])){remove_node();exit;}
page();


function reload_www():bool{
    $tpl=new template_admin();
   $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cluster/server/reload"));
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->js_error(json_last_error_msg());
    }
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    return true;

}
function stop_www():bool{
    $tpl=new template_admin();
   $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cluster/server/stop"));
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->js_error(json_last_error_msg());
    }
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    return true;

}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("Cluster ({master_mode})",
        "fas fa-clone","{pdns_master_mode_explain}","$page?tabs=yes",
        "cluster-master","progress-cluster-restart",false,"table-cluster-master");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Cluster Master",$html);
        echo $tpl->build_firewall();return true;}
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function remove_node():bool{
    $uuid=$_GET["remove-node"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterMasterNodes"));

    if(!property_exists($json,"nodes")){
        return  false;
    }

    unset($json->nodes[$uuid]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterMasterNodes",json_encode($json));
    return admin_tracks("Remove cluster slave $uuid");
}
function interface_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{listen}/{certificate}","$page?interface-popup=yes",550 );
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
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{parameters}","$page?parameters-popup=yes",550 );
}

function parameters_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:400px;vertical-align:top;'>";
    $html[]="<div id='cluster-status' style='width:400px'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>";
    $html[]="<div id='flat-parameters-cluster-master'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $jsRestart=$tpl->framework_buildjs("/cluster/server/build",
        "artica.cluster.progress","artica.cluster.log",
        "progress-cluster-restart");


    $topbuttons=array();

    if($users->AsSystemAdministrator) {
        $topbuttons[] = array($jsRestart, ico_file_zip, "{build2}: {cluster_package}");
    }
    $s_PopUp="s_PopUp('https://wiki.articatech.com/en/system/clusterv2','1024','800')";
    $topbuttons[] = array($s_PopUp,ico_support, "{help}");


    $TINY_ARRAY["TITLE"]="Cluster ({master_mode})";
    $TINY_ARRAY["ICO"]="fas fa-clone";
    $TINY_ARRAY["EXPL"]="{pdns_master_mode_explain}";
    $TINY_ARRAY["URL"]="cluster-master";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $jsRefresh=$tpl->RefreshInterval_js("cluster-status",$page,"status=yes");
    $html[]="<script>";
    $html[]="LoadAjax('flat-parameters-cluster-master','$page?flat-parameters-cluster-master=yes');";
    $html[]=$jstiny;
    $html[]=$jsRefresh;
    $html[]="</script>";
    echo @implode("",$html);
    return true;
}
function interface_popup():bool{

    $ClusterServiceCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServiceCertificate");
    $page=CurrentPageName();
    $tpl=new template_admin();
    if(strlen($ClusterServiceCertificate)<3){
        $jsRestart=$tpl->framework_buildjs("/cluster/server/create/certificate",
            "artica.cluster.progress","artica.cluster.log",
            "progress-cluster-restart","LoadAjax('flat-parameters-cluster-master','$page?flat-parameters-cluster-master=yes');");

        $html[]="<div style='margin:30px' class='center'>";
        $html[]=$tpl->button_autnonome("{create_certificate}","dialogInstance2.close();$jsRestart","fas fa-file-certificate",null,350);
        $html[]="</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;

    }
    $ClusterServicePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServicePort"));
    if($ClusterServicePort==0){$ClusterServicePort=58787;}
    $ClusterServiceInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServiceInterface");

    $html[]=$tpl->field_interfaces("ClusterServiceInterface","{listen_interface}",$ClusterServiceInterface);
    $html[]=$tpl->field_numeric("ClusterServicePort","{listen_port}",$ClusterServicePort);
    echo $tpl->form_outside("",$html,"","{apply}",
    "LoadAjax('flat-parameters-cluster-master','$page?flat-parameters-cluster-master=yes');dialogInstance2.close();");
    return true;
}


function flat_parameters():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ClusterReplicateOfficalDatabases=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterReplicateOfficalDatabases");
    $ClusterNotReplicateWeb=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateWeb"));
    $ClusterNotReplicateTasks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateTasks"));
    $ClusterNotReplicateWebParameters=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateWebParameters"));
    $ClusterNotReplicateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateAD"));

    $ClusterServicePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServicePort"));
    if($ClusterServicePort==0){$ClusterServicePort="58787";}
    $ClusterServiceInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServiceInterface");

    if($ClusterServiceInterface==null){
        $ClusterServiceInterface="{all}";
    }
    $ClusterServiceCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServiceCertificate");
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

    $tpl->table_form_field_js("Loadjs('$page?interface-js=yes')");


    if (strlen($ClusterServiceCertificate) > 3) {
        if($HaClusterClient==0){
            $tpl->table_form_field_text("{listen}", "<span style='text-transform:none'>https://$ClusterServiceInterface:$ClusterServicePort</span>", ico_interface);

            $q = new lib_sqlite("/home/artica/SQLITE/certificates.db");
            $ligne = $q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='$ClusterServiceCertificate'");
            if (!isset($ligne['ID'])) {
                $ligne['ID'] = 0;
            }
            $CertID = intval($ligne['ID']);

            if ($CertID == 0) {
                $jsRestart = $tpl->framework_buildjs("/cluster/server/create/certificate",
                    "artica.cluster.progress", "artica.cluster.log",
                    "progress-cluster-restart", "LoadAjax('flat-parameters-cluster-master','$page?flat-parameters-cluster-master=yes');");
                $tpl->table_form_field_js($jsRestart);
                $tpl->table_form_field_button("{create_certificate}", "{create_certificate}", ico_certificate);
            }


            $ligne = $q->mysqli_fetch_array("SELECT count(*) as tcount FROM subcertificates WHERE certid='$CertID'");
            $Count = $ligne['tcount'];
            $tpl->table_form_field_js("Loadjs('fw.certificates-center.php?subcertificates-js=$CertID&OnlyClient=yes&NoPass=yes')");
            if ($Count == 0) {
                $tpl->table_form_field_bool("{client_certificates}", "$Count", ico_interface);
            } else {
                $tpl->table_form_field_text("{client_certificates}", "$Count", ico_interface);
            }

        }
    } else {
        $tpl->table_form_field_js("Loadjs('$page?interface-js=yes')");
        $tpl->table_form_field_text("{listen}", "<span style='text-transform:none'>{no_certificate}</span>", ico_interface);
   }



    $tpl->table_form_field_js("Loadjs('$page?params-js=yes')");
    $tpl->table_form_field_bool("{ClusterReplicateOfficalDatabases}",$ClusterReplicateOfficalDatabases,ico_database);
    $tpl->table_form_field_bool("{ClusterNotReplicateWeb}",$ClusterNotReplicateWeb,ico_earth);
    $tpl->table_form_field_bool("{NotReplicateWebConsoleParameters}",$ClusterNotReplicateWebParameters,ico_earth);
    $tpl->table_form_field_bool("{ClusterNotReplicateTasks}",$ClusterNotReplicateTasks,ico_clock);
    $tpl->table_form_field_bool("{NotReplicateADParameters}",$ClusterNotReplicateAD,ico_microsoft);

    echo $tpl->table_form_compile();
    return true;

}

function parameters_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ClusterReplicateOfficalDatabases=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterReplicateOfficalDatabases");
    $ClusterNotReplicateWeb=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateWeb"));
    $ClusterNotReplicateTasks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateTasks"));
    $ClusterNotReplicateWebParameters=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateWebParameters"));
    $ClusterNotReplicateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateAD"));


    $form[]=$tpl->field_checkbox("ClusterReplicateOfficalDatabases","{ClusterReplicateOfficalDatabases}",$ClusterReplicateOfficalDatabases);
    $form[]=$tpl->field_checkbox("ClusterNotReplicateWeb","{ClusterNotReplicateWeb}",$ClusterNotReplicateWeb);
    $form[]=$tpl->field_checkbox("ClusterNotReplicateTasks","{ClusterNotReplicateTasks}",$ClusterNotReplicateTasks);
    $form[]=$tpl->field_checkbox("ClusterNotReplicateWebParameters","{NotReplicateWebConsoleParameters}",$ClusterNotReplicateWebParameters);
    $form[]=$tpl->field_checkbox("ClusterNotReplicateAD","{NotReplicateADParameters}",$ClusterNotReplicateAD);
    $html=$tpl->form_outside("",$form,null,"{apply}","LoadAjax('flat-parameters-cluster-master','$page?flat-parameters-cluster-master=yes');",
        "AsSystemAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
return true;

}

function Launch():bool{
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/cluster/server/build");
    return true;
}

function status():bool{
    $tpl=new template_admin();
   $PowerDNSEnableClusterMasterTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMasterTime"));
   $PowerDNSEnableClusterMasterSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMasterSize"));

   if($PowerDNSEnableClusterMasterTime==0){
        $html=$tpl->widget_h("gray","fas fa-database","-","{cluster_package}");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
   }
   $title_time=$tpl->time_to_date($PowerDNSEnableClusterMasterTime,true);
   $time=distanceOfTimeInWords($PowerDNSEnableClusterMasterTime,time());
   $size=FormatBytes($PowerDNSEnableClusterMasterSize/1024);
    $ACLUSTER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ACLUSTER_VERSION");
    $time="<span style='font-size:20px'>$time</span>";
   $html=$tpl->widget_h("green","fas fa-database","$ACLUSTER_VERSION<br><small style='color:white'>$time ($size)</small>","{cluster_package}<br>$title_time");
    echo $tpl->_ENGINE_parse_body($html);


    echo local_service_status();

    return false;
}

function unbound_backage_status($json):string{
    $UnboundEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled");
    if ($UnboundEnabled==0){
        return "";

    }
    $tpl=new template_admin();


    if(!property_exists($json,"Unbound")){
        return $tpl->_ENGINE_parse_body($tpl->widget_h("grey","fas fa-database","-","{APP_UNBOUND} {cluster_package}"));

    }
    if(!property_exists($json->Unbound,"Time")){
        return $tpl->_ENGINE_parse_body($tpl->widget_h("grey","fas fa-database","-","{APP_UNBOUND} {cluster_package}"));

    }

    $Time=$json->Unbound->Time;
    $title_time=$tpl->time_to_date($Time,true);
    $time=distanceOfTimeInWords($Time,time());

    return $tpl->_ENGINE_parse_body($tpl->widget_h("green","fas fa-database","$Time<br><small style='color:white'>$time</small>","{cluster_package}: {APP_UNBOUND}<br>$title_time"));


}
function local_service_status():string{
    $tpl=new template_admin();
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){
        return "";
    }
    $ClusterServiceIfaceTov4 = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServiceIfaceTov4"));
    if (strlen($ClusterServiceIfaceTov4) < 4) {
        return "";
    }
    $ClusterServicePort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServicePort"));
    if ($ClusterServicePort == 0) {
        $ClusterServicePort = 58787;
    }
    $page=CurrentPageName();
    $curl = new ccurl("https://$ClusterServiceIfaceTov4:$ClusterServicePort/status", true);
    $curl->NoLocalProxy();
    if (!$curl->get()) {
        $btn = array();
        $btn[0]["margin"] = 0;
        $btn[0]["name"] = "{start}";
        $btn[0]["icon"] = ico_run;
        $btn[0]["js"] = "Loadjs('$page?reload-www=yes');";
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg", ico_bug, "$ClusterServiceIfaceTov4:$ClusterServicePort - $curl->error", "{local_service}",$btn));
    }

    $json=json_decode($curl->data);
    if (json_last_error() > JSON_ERROR_NONE) {
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg", ico_bug, json_last_error_msg(), "{local_service} {error}"));
    }
    if (!property_exists($json,"Status")){
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg", ico_bug, "No Status property", "{local_service} {error}"));
    }



    if(!$json->Status){
        $btn = array();
        $btn[0]["margin"] = 0;
        $btn[0]["name"] = "{reload}";
        $btn[0]["icon"] = ico_retweet;
        $btn[0]["js"] = "Loadjs('$page?reload-www=yes');";
        return $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg", ico_bug, $json->Error, "{local_service} {error}",$btn));
    }

    $btn = array();
    $btn[0]["margin"] = 0;
    $btn[0]["name"] = "{reload}";
    $btn[0]["icon"] = ico_retweet;
    $btn[0]["js"] = "Loadjs('$page?reload-www=yes');";

    $btn[1]["margin"] = 0;
    $btn[1]["name"] = "{stop}";
    $btn[1]["icon"] = ico_stop;
    $btn[1]["js"] = "Loadjs('$page?stop-www=yes');";

    return unbound_backage_status($json).$tpl->_ENGINE_parse_body($tpl->widget_style1("navy-bg", ico_clouds, "$ClusterServiceIfaceTov4:$ClusterServicePort", "{connections}: ".$tpl->FormatNumber( $json->Info),$btn)).ClusterClients();




}

function ClusterClients():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterMasterNodes"));
    if (json_last_error() > JSON_ERROR_NONE) {
        return  $tpl->_ENGINE_parse_body($tpl->widget_style1("gray-bg","fa-solid fa-display-slash","{clients}","{none}"));

    }

   if(!property_exists($json,"nodes")){
       return  $tpl->_ENGINE_parse_body($tpl->widget_style1("gray-bg","fa-solid fa-display-slash","{clients}","{none}"));

   }
    $zNodes=array();
   foreach($json->nodes as $uuid=>$node){

       $ip=$node->ips[0];
       $hostname=$node->hostname;
       if(strpos($hostname,".")>0){
           $tb=explode(".",$hostname);
           $hostname=$tb[0];
       }

       $connectionTime=$node->connectionTime;
       $connectionsCount=$node->connectionsCount;
       $connectionName=$node->connectionName;

       if(strpos($connectionName,".")>0){
           $tb=explode(".",$connectionName);
           $connectionName=$tb[0];
       }

       $distances=distanceOfTimeInWords($connectionTime,time());
       $timeMin=DistanceInMns($connectionTime);
       if($timeMin>240){
           $zNodes[]=$tpl->_ENGINE_parse_body($tpl->widget_style1("yellow-bg","fa-solid fa-display-medical","$hostname ($ip)<br>$distances",$connectionName));
           continue;
       }
       $btn = array();
       $btn[0]["margin"] = 0;
       $btn[0]["name"] = "{remove}";
       $btn[0]["icon"] = ico_trash;
       $btn[0]["js"] = "Loadjs('$page?remove-node=$uuid');";


       $zNodes[]=$tpl->_ENGINE_parse_body($tpl->widget_style1("navy-bg","fa-solid fa-display","$hostname ($ip)<br>{connections}: $connectionsCount<br>$distances",$connectionName,$btn));


   }
   return $tpl->_ENGINE_parse_body($zNodes);

}
function DistanceInMns($time):int{
    $data1 = $time;
    $data2 = time();
    $difference = ($data2 - $data1);
    return round($difference/60);
}