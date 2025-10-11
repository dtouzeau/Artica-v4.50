<?php
//SP 100
$GLOBALS["CLUSTER_PORT_FEATURE"]=true;
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
include_once(dirname(__FILE__)."/ressources/class.snapshots.blacklists.inc");
if(isset($_POST["MetaAddress"])){create_client_package_save();}
if(isset($_GET["reload-www"])){reload_www();exit;}
if(isset($_GET["interface-js"])){interface_js();exit;}
if(isset($_GET["interface-popup"])){interface_popup();exit;}
if(isset($_GET["Launch"])){Launch();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["params-js"])){parameters_js();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["parameters-start"])){parameters_start();exit;}
if(isset($_GET["flat-parameters-metasrv"])){flat_parameters();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["ClusterReplicateOfficalDatabases"])){Save();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_GET["remove-node"])){remove_node();exit;}
if(isset($_GET["create-client-package-js"])){create_client_package_js();exit();}
if(isset($_GET["create-client-package-popup"])){create_client_package_popup();exit;}
page();


function reload_www(){
    $page=CurrentPageName();
    $tpl=new template_admin();
   $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cluster/server/reload"));
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->js_error(json_last_error_msg());
    }
    if(!$json->Status){
        return $tpl->js_error($json->Error);
    }
    return $tpl->js_ok();

}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{artica_meta})",
        ico_ameta,"{artica_meta_server_explain}","$page?tabs=yes",
        "meta-server","progress-srvmeta-restart",false,"table-srvmeta");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica Meta",$html);
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
function create_client_package_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{create_client_package}","$page?create-client-package-popup=yes",550 );
}
function create_client_package_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $CertID=CertID();
    $t=time();
    if($CertID==0){
        echo $tpl->div_warning("{meta_no_ca}");
        return true;
    }
    $WizMetaClient=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizMetaClient"));
    if(!$WizMetaClient){
        $MetaServPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MetaServPort"));
        if($MetaServPort==0){$MetaServPort=58788;}
        $WizMetaClient["MetaServerAddress"]=$_SERVER["SERVER_ADDR"];
        $WizMetaClient["MetaServPort"]=$MetaServPort;
    }

    $certjs="Loadjs('fw.certificates-center.php?subcertificates-js=$CertID&OnlyClient=yes&NoPass=yes')";

    $jsrestart=$tpl->framework_buildjs(
        "/certificate/client/selfsigned/$CertID",
        "selfsign.progress",
        "selfsign.log",
        "progress-$t",
        "dialogInstance2.close();$certjs" );
    $html[]="<div id='progress-$t'></div>";
    $form[]=$tpl->field_hidden("CertID",$CertID);
    $form[]=$tpl->field_text("ClientName","{acl_srcdomain}","",true);
    $form[]=$tpl->field_text("MetaAddress","{MetaAddress}",$WizMetaClient["MetaAddress"]);
    $form[]=$tpl->field_numeric("MetaServPort","{listen_port}",$WizMetaClient["MetaServPort"]);
    $html[]=$tpl->form_outside("",$form,null,"{create}", "$jsrestart");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function create_client_package_save():bool {

    $CertID=$_POST["CertID"];
    $MAIN["CommonName"]=$_POST["ClientName"];

    $MAIN["MetaServerAddress"]=$_POST["MetaAddress"].":".$_POST["MetaServPort"];
    foreach ($_POST as $key => $value) {
        $MAIN[$key] = $value;
    }
    $key="CLIENT_CERTIFICATE_$CertID";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO($key,serialize($MAIN));
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/certificate/client/selfsigned/$CertID");
    return admin_tracks("Create a Meta Client certificate {$MAIN["CommonName"]}");
}

function parameters_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $users=new usersMenus();
    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:400px;vertical-align:top;'>";
    $html[]="<div id='metasrv-status' style='width:400px'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>";
    $html[]="<div id='flat-parameters-metasrv'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    $jsRestart=$tpl->framework_buildjs("/metasrv/restart",
        "meta.progress","meta.progress.log",
        "progress-srvmeta-restart");


    $topbuttons=array();

    if($users->AsSystemAdministrator) {
        $topbuttons[] = array("Loadjs('$page?create-client-package-js=yes')", ico_file_zip, "{create_client_package}");
    }
    $s_PopUp="s_PopUp('https://wiki.articatech.com/en/system/clusterv2','1024','800')";
    $topbuttons[] = array($s_PopUp,ico_support, "{help}");


    $TINY_ARRAY["TITLE"]="{artica_meta}";
    $TINY_ARRAY["ICO"]=ico_ameta;
    $TINY_ARRAY["EXPL"]="{artica_meta_server_explain}";
    $TINY_ARRAY["URL"]="metasrv";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $jsRefresh=$tpl->RefreshInterval_js("metasrv-status",$page,"status=yes");
    $html[]="<script>";
    $html[]="LoadAjax('flat-parameters-metasrv','$page?flat-parameters-metasrv=yes');";
    $html[]=$jstiny;
    $html[]=$jsRefresh;
    $html[]="</script>";
    echo @implode("",$html);
    return true;
}
function interface_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $MetaServInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MetaServInterface");
    $MetaServCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MetaServCertificate");
    $MetaServUseLocalReverse=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MetaServUseReverse");
    $EnableNginx = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx");
    $MetaServPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MetaServPort"));
    if($MetaServPort==0){$MetaServPort=58788;}
    if($EnableNginx==0){
        $MetaServUseLocalReverse=0;
    }

    $jsRestart=$tpl->framework_buildjs("/metasrv/restart",
        "meta.progress","meta.progress.log",
        "progress-srvmeta-restart");

    $form[]=$tpl->field_interfaces("MetaServInterface","{listen_interface}",$MetaServInterface);
    $form[]=$tpl->field_numeric("MetaServPort","{listen_port}",$MetaServPort);
    if($EnableNginx==1){
        $form[]=$tpl->field_checkbox("MetaServUseReverse","{use_reverse}",$MetaServUseLocalReverse);
    }




    echo $tpl->form_outside("",$form,"","{apply}",
    "LoadAjax('flat-parameters-metasrv','$page?flat-parameters-metasrv=yes');dialogInstance2.close();$jsRestart");
    return true;
}
function CertID():int{
    $MetaServCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MetaServCertificate");
    $q = new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $ligne = $q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='$MetaServCertificate'");
    if (!isset($ligne['ID'])) {
        $ligne['ID'] = 0;
    }
    return intval($ligne['ID']);
}


function flat_parameters():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $MetaServInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MetaServInterface");
    $MetaServCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MetaServCertificate");
    $MetaServUseLocalReverse=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MetaServUseReverse");
    $EnableNginx = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx");
    if($EnableNginx==0){
        $MetaServUseLocalReverse=0;
    }

    $tpl->table_form_field_js("Loadjs('$page?interface-js=yes')");
    $MetaServPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MetaServPort"));
    if($MetaServPort==0){$MetaServPort=58788;}


    if($MetaServInterface==null){
        $MetaServInterface="0.0.0.0";
    }
    $MetaUri="https://$MetaServInterface:$MetaServPort";

    if($MetaServUseLocalReverse==1){
        $MetaUri="unix://run/metasrv/listener.sock";
    }
    if($EnableNginx==1) {
        $tpl->table_form_field_bool("{use_reverse_proxy}", $MetaServUseLocalReverse, ico_clouds);
    }
    $tpl->table_form_field_text("{listen}", "<span style='text-transform:none'>$MetaUri</span>", ico_interface);

    if($MetaServUseLocalReverse==0) {
        if (strlen($MetaServCertificate) > 3) {

            $CertID=CertID();

            if ($CertID == 0) {
                $jsRestart = $tpl->framework_buildjs("/cluster/server/create/certificate",
                    "artica.cluster.progress", "artica.cluster.log",
                    "progress-srvmeta-restart", "LoadAjax('flat-parameters-metasrv','$page?flat-parameters-metasrv=yes');");
                $tpl->table_form_field_js($jsRestart);
                $tpl->table_form_field_button("{create_certificate}", "{create_certificate}", ico_certificate);
            }

            $q = new lib_sqlite("/home/artica/SQLITE/certificates.db");
            $ligne = $q->mysqli_fetch_array("SELECT count(*) as tcount FROM subcertificates WHERE certid='$CertID'");
            $Count = $ligne['tcount'];
            $tpl->table_form_field_js("Loadjs('fw.certificates-center.php?subcertificates-js=$CertID&OnlyClient=yes&NoPass=yes')");
            if ($Count == 0) {
                $tpl->table_form_field_bool("{client_certificates}", "$Count", ico_interface);
            } else {
                $tpl->table_form_field_text("{client_certificates}", "$Count", ico_interface);
            }

        } else {
            $tpl->table_form_field_text("{certificate}", "{none}", ico_certificate, true);
        }
    }

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
    $html=$tpl->form_outside("",$form,null,"{apply}","LoadAjax('flat-parameters-metasrv','$page?flat-parameters-metasrv=yes');dialogInstance2.close();",
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
function status():string{
    $tpl=new template_admin();


    $jsRestart=$tpl->framework_buildjs("/metasrv/restart",
        "meta.progress","meta.progress.log",
        "progress-srvmeta-restart");


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/metasrv/status"));

    if (json_last_error()> JSON_ERROR_NONE) {
        $status[]=$tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR",json_last_error_msg()));
        $status[]="<script>";
        //$status[]="LoadAjaxSilent('nagios-client-top','$page?nagios-client-top=yes')";
        $status[]="</script>";
        echo $tpl->_ENGINE_parse_body($status);
        return false;

    }else {
        if (!$json->Status) {
            $status[]=$tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR", $json->Error));
            $status[]="<script>";
           // $status[]="LoadAjaxSilent('nagios-client-top','$page?nagios-client-top=yes')";
            $status[]="</script>";
            echo $tpl->_ENGINE_parse_body($status);
            return false;
        } else {
            $bsini=new Bs_IniHandler();
            $bsini->loadString($json->Info);
            $status[]=$tpl->SERVICE_STATUS($bsini, "APP_ARTICA_META",$jsRestart);

        }
    }


    echo $tpl->_ENGINE_parse_body($status);
    return true;

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
       $distances=distanceOfTimeInWords($connectionTime,time());
       $timeMin=DistanceInMns($connectionTime);
       if($timeMin>240){
           echo $tpl->_ENGINE_parse_body($tpl->widget_style1("yellow-bg","fa-solid fa-display-medical","$hostname ($ip)<br>$distances",$connectionName));
           return true;
       }
       $btn = array();
       $btn[0]["margin"] = 0;
       $btn[0]["name"] = "{remove}";
       $btn[0]["icon"] = ico_trash;
       $btn[0]["js"] = "Loadjs('$page?remove-node=$uuid');";


       return  $tpl->_ENGINE_parse_body($tpl->widget_style1("navy-bg","fa-solid fa-display","$hostname ($ip)<br>{connections}: $connectionsCount<br>$distances",$connectionName,$btn));


   }
return true;
}
function DistanceInMns($time):int{
    $data1 = $time;
    $data2 = time();
    $difference = ($data2 - $data1);
    return round($difference/60);
}