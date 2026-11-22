<?php
$GLOBALS["CLUSTER_PORT_FEATURE"]=true;
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/prefetch-info.inc"); __prefetchCommonSettings($GLOBALS["CLASS_SOCKETS"]);
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
include_once(dirname(__FILE__)."/ressources/class.snapshots.blacklists.inc");
include_once(dirname(__FILE__)."/ressources/externals/class.aesCrypt.inc");


if(isset($_GET["build-page"])){build_page();exit;}
// Ancien point d'entree du contenu (onglets en tete de page) : garde pour les liens existants.
if(isset($_GET["tabs"])){build_page();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded_js();exit;}
if(isset($_GET["client-certificate-js"])){client_certificate_js();exit;}
if(isset($_GET["client-certificate-popup"])){client_certificate_popup();exit;}
if(isset($_GET["parameters-js"])){parameters_js();exit;}
if(isset($_GET["parameters-popup"])){parameters();exit;}
if(isset($_GET["parameters-tab"])){parameters_tab();exit;}
if(isset($_GET["parameters-flat"])){parameters_flat();exit;}

// Aussi appele par fw.dashboard.HaCluster.php (div package-cluster-status) : doit rester autonome.
if(isset($_GET["status"])){status();exit;}
if(isset($_POST["PowerDNSClusterSlaveInterface"])){Save();exit;}
if(isset($_GET["freeze"])){freeze();exit;}
page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("Cluster ({slave_mode})",
        "fas fa-clone","{pdns_slave_mode_explain}","$page?build-page=yes",
        "cluster-client","progress-cluster-restart",false,"table-cluster-client");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Cluster ({slave_mode})",$html);
        echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body( $html);

}

// Colonne d'etat persistante a gauche (rafraichie toutes les 3 s), onglets a droite.
function build_page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $array["{parameters}"]="$page?parameters-tab=yes";
    $array["{events}"]="fw.system.cluster.events.php";

    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='width:400px;vertical-align:top'>";
    $html[]="<div id='cluster-status' style='width:400px'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:20px'>";
    // tabs_default() rend du HTML deja traduit : il ne repasse pas par le moteur.
    $tabs=$tpl->tabs_default($array);
    $end[]="</td>";
    $end[]="</tr>";
    $end[]="</table>";
    // Le TINY apres les onglets, dans son propre script (recette des pages).
    $end[]="<script>";
    $end[]=$tpl->RefreshInterval_js("cluster-status",$page,"status=yes");
    $end[]=cluster_client_tiny();
    $end[]="</script>";
    echo $tpl->_ENGINE_parse_body($html).$tabs.$tpl->_ENGINE_parse_body($end);
    return true;
}

function cluster_client_tiny():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $refresh="LoadAjaxSilent('cluster-client-flat','$page?parameters-flat=yes');LoadAjaxSilent('cluster-status','$page?status=yes');";
    $PowerDNSClusterMasterAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterAddress"));
    $PowerDNSClusterSlaveCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterSlaveCertificate");

    $topbuttons=array();
    $users=new usersMenus();
    if($PowerDNSClusterMasterAddress<>null && $users->AsSystemAdministrator) {
        // Avec un certificat client, la synchronisation passe par le protocole v2 (mTLS).
        $v2=strlen($PowerDNSClusterSlaveCertificate)>10?"/v2":"";
        $jsSync=$tpl->framework_buildjs("/cluster/client$v2/download",
            "artica.cluster.progress","artica.cluster.log","progress-cluster-restart",$refresh);
        $jsForce=$tpl->framework_buildjs("/cluster/client$v2/force",
            "artica.cluster.progress","artica.cluster.log","progress-cluster-restart",$refresh);
        $topbuttons[] = array($jsSync, ico_refresh, "{synchronize}");
        $topbuttons[] = array($jsForce, ico_refresh, "{force_synchronize}");
    }

    $TINY_ARRAY["TITLE"]="Cluster ({slave_mode})";
    $TINY_ARRAY["ICO"]="fas fa-clone";
    $TINY_ARRAY["EXPL"]="{pdns_slave_mode_explain}";
    $TINY_ARRAY["URL"]="cluster-client";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
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
    echo "dialogInstance1.close();LoadAjaxSilent('cluster-client-flat','$page?parameters-flat=yes');LoadAjaxSilent('cluster-status','$page?status=yes');";
    return true;

}

function parameters_tab():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div id='cluster-client-flat' style='margin-top:10px'></div>";
    $html[]="<script>LoadAjaxSilent('cluster-client-flat','$page?parameters-flat=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

// Certificat client tel que stocke par ImportClientCertificate (classcerts.OpenSSLCertificateData).
function cluster_client_certificate():array{
    $raw=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterSlaveCertificate");
    if(strlen($raw)<10){return array();}
    $json=json_decode($raw,true);
    if(!is_array($json)){return array();}
    $name=trim((string)($json["CommonName"] ?? ""));
    $Subject=(string)($json["Subject"] ?? "");
    if($Subject<>""){
        $Exploded=explode(",",$Subject);
        if($name==""){$name=trim($Exploded[0]);}
        foreach ($Exploded as $Value) {
            if (preg_match("#^\s*OU=(.+)#i", $Value, $re)) {$name.=" /".trim($re[1]);}
            elseif (preg_match("#^\s*O=(.+)#i", $Value, $re)) {$name.=" /".trim($re[1]);}
        }
    }
    $issuer=(string)($json["Issuer"] ?? "");
    if(preg_match("#CN=([^,]+)#i",$issuer,$re)){$issuer=trim($re[1]);}
    return array("name"=>$name,"issuer"=>$issuer,"notafter"=>intval($json["NotAfter"] ?? 0));
}

function parameters_flat(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $HaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){
        $html[]="<div style='padding-left:15px'><H1>{APP_HACLUSTER_CLIENT}</H1><div id='hacluster-client-flat'></div></div>";
        $html[]="<script>LoadAjax('hacluster-client-flat','fw.dashboard.HaCluster.php?flat=yes');</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return;
    }

    $PowerDNSClusterSlaveInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterSlaveInterface"));
    $PowerDNSClusterMasterAddress = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterAddress"));
    $PowerDNSClusterMasterPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterPort"));
    if ($PowerDNSClusterMasterPort == 0) {
        $PowerDNSClusterMasterPort = 58787;
    }
    $PowerDNSEnableClusterSlaveSchedule = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlaveSchedule"));
    if ($PowerDNSEnableClusterSlaveSchedule < 5) {
        $PowerDNSEnableClusterSlaveSchedule = 15;
    }

    $cert=cluster_client_certificate();
    $tpl->table_form_field_js("Loadjs('$page?client-certificate-js=yes')");
    if (!isset($cert["name"]) || $cert["name"] == "") {
        $tpl->table_form_field_bool("{client_certificate}||{cluster_client_certificate_explain}", 0, ico_certificate);
    } else {
        $tpl->table_form_field_text("{client_certificate}||{cluster_client_certificate_explain}", htmlspecialchars($cert["name"]), ico_certificate);
    }

    $master="-";
    if($PowerDNSClusterMasterAddress<>""){
        $master=htmlspecialchars("$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort");
    }
    $iface="{all}";
    if (strlen($PowerDNSClusterSlaveInterface) >= 2) {
        $iface=htmlspecialchars($PowerDNSClusterSlaveInterface);
    }

    $tpl->table_form_field_js("Loadjs('$page?parameters-js=yes')");
    $tpl->table_form_field_text("{master_address}||{cluster_master_address_explain}", $master, ico_server);
    // Toujours affichee : l'ancienne page ne montrait l'interface que lorsqu'elle etait vide.
    $tpl->table_form_field_text("{outgoing_interface}||{cluster_outgoing_interface_explain}", $iface, ico_nic);
    $tpl->table_form_field_text("{schedule}||{cluster_schedule_explain}","{each} $PowerDNSEnableClusterSlaveSchedule {minutes}",ico_clock);

    echo $tpl->table_form_compile();
}
function parameters():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $jsRestart="dialogInstance1.close();LoadAjaxSilent('cluster-client-flat','$page?parameters-flat=yes');".$tpl->framework_buildjs("/cluster/client/download",
        "artica.cluster.progress","artica.cluster.log","progress-cluster-restart",
        "LoadAjaxSilent('cluster-status','$page?status=yes');");

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
            echo (new template_admin())->post_error("$data->Error");
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
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body("{error} $error $text"));
        return false;
    }

    if($CURLINFO_HTTP_CODE>200){
        echo (new template_admin())->post_error("".$tpl->_ENGINE_parse_body("https://$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort<br>{error} $CURLINFO_HTTP_CODE"));
        return false;
    }

    $data=unserialize(base64_decode($data));

    if(!isset($ARRAY["UUID"])){$ARRAY["UUID"]=null;}
    $uuid=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSTEMID"));
    if($ARRAY["UUID"]<>null){
        if(trim($ARRAY["UUID"])==trim($uuid)){
            echo (new template_admin())->post_error("Loop back to myself, same uuid");
            return false;
        }
    }

    $tpl->SAVE_POSTs();
return true;

}
function freeze():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    // Le bouton est masque aux non-administrateurs, mais l'URL restait appelable.
    $users=new usersMenus();
    if(!$users->AsSystemAdministrator){
        $tpl->js_error("{ERROR_NO_PRIVS2}");
        return false;
    }
    $PowerDNSClusterClientStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterClientStop"));

    if($PowerDNSClusterClientStop==0){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSClusterClientStop",1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSClusterClientStop",0);
    }
    echo "LoadAjaxSilent('cluster-status','$page?status=yes');";
    return true;
}

// Carte « derniere synchronisation » ; porte aussi le bouton geler/reactiver :
// l'etat de la replication et sa commande se lisent au meme endroit.
function package_statusv2():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $PowerDNSClusterClientStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterClientStop"));

    $btn=array();
    $users=new usersMenus();
    if($users->AsSystemAdministrator){
        if($PowerDNSClusterClientStop==1){
            $btn=array("name"=>"{enable_replication}","js"=>"Loadjs('$page?freeze=yes')","ico"=>ico_play);
        }else{
            $btn=array("name"=>"{freeze_replication}","js"=>"Loadjs('$page?freeze=yes')","ico"=>"fas fa-pause");
        }
    }

    $ClusterSlaveInfo=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterSlaveInfo"));
    if (json_last_error()> JSON_ERROR_NONE || !is_object($ClusterSlaveInfo)) {
        return $tpl->widget_h("gray",ico_database,"-","{last_sync}",$btn);
    }

    if(!$ClusterSlaveInfo->Status){
        return $tpl->widget_h("red",ico_database,"<span style='font-size:16px'>".htmlspecialchars((string)$ClusterSlaveInfo->Error)."</span>","{last_sync}: {error}",$btn);
    }

    $ReplicTime=intval($ClusterSlaveInfo->ReplicTime);
    if($ReplicTime==0){
        return $tpl->widget_h("yellow",ico_database,"-","{last_sync}",$btn);
    }

    $time=$tpl->time_to_date($ReplicTime,true);
    $Distance=distanceOfTimeInWords($ReplicTime,time());
    $version="";
    if(property_exists($ClusterSlaveInfo,"PackageVersion") && strlen($ClusterSlaveInfo->PackageVersion)>1){
        $version=" &middot; v".htmlspecialchars((string)$ClusterSlaveInfo->PackageVersion);
    }
    $detail="<br><small style='color:white;font-size:12px'>$time$version</small>";

    if($PowerDNSClusterClientStop==1){
        return $tpl->widget_h("gray",ico_database,"<span style='font-size:22px'>$Distance</span>$detail","{last_sync} &middot; {cluster_replication_freezed}",$btn);
    }

    // Le client interroge le maitre chaque minute et ne telecharge que lorsque la
    // derniere replication a plus de « schedule » minutes (ClusterClient.PingMaster).
    $schedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlaveSchedule"));
    if($schedule<5){$schedule=15;}
    $next=max(1,(int)ceil(($ReplicTime+$schedule*60-time())/60));
    $detail.="<br><small style='color:white;font-size:12px'>{next_run}: &asymp; $next {minutes}</small>";

    return $tpl->widget_h("green",ico_database,"<span style='font-size:22px'>$Distance</span>$detail","{last_sync}",$btn);
}
function unbound_client_status():string{
    $UnboundEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled");
    if ($UnboundEnabled==0){
        VERBOSE("UnboundEnabled=$UnboundEnabled",__LINE__);
        return "";

    }
    $tpl=new template_admin();
    if(!file_exists("/etc/artica-postfix/UnboundCluster.json")){
        return $tpl->widget_h("grey","fas fa-exclamation-triangle","-","{APP_UNBOUND} {cluster_package}");
    }
    $json=json_decode(file_get_contents("/etc/artica-postfix/UnboundCluster.json"),true);
    if(!is_array($json)){
        return $tpl->widget_h("grey","fas fa-exclamation-triangle","-","{APP_UNBOUND} {cluster_package}");
    }
    if(!isset($json["Time"])){
        return $tpl->widget_h("grey",ico_database,"-","{APP_UNBOUND} {cluster_package}");
    }

    $Time=intval($json["Time"]);
    $title_time=$tpl->time_to_date($Time,true);
    $time=distanceOfTimeInWords($Time,time());
    // La valeur principale etait l'horodatage Unix brut (« 1790669568 »).
    return $tpl->widget_h("green",ico_database,"<span style='font-size:22px'>$time</span><br><small style='color:white;font-size:12px'>$title_time</small>","{cluster_package}: {APP_UNBOUND}");


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

// Carte « liaison au maitre » : interroge le maitre (/status) a chaque rafraichissement.
// Le temps affiche est celui de cette requete, mesure ici, pas une latence supposee.
function cluster_Status():string{
    $tpl=new template_admin();
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){
        return cluster_Status_haclient();
    }

    $PowerDNSClusterSlaveCertificate = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterSlaveCertificate");
    $PowerDNSClusterMasterAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterAddress"));
    if(strlen($PowerDNSClusterMasterAddress)<3){
        return $tpl->widget_h("gray",ico_link,"{missing}","{master_address}");
    }
    if(strlen($PowerDNSClusterSlaveCertificate)<10){
        return $tpl->widget_h("gray",ico_link,"{missing}","{client_certificate}");
    }

    $t0=microtime(true);
    $data=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/cluster/client/v2/status"),true);
    $ms=round((microtime(true)-$t0)*1000);
    if(!is_array($data)){
        return $tpl->widget_h("red",ico_link,"{error}","{protocol_error}");
    }

    if( !$data["Status"]) {
        $error="{disconnected}";
        $text=(string)($data["Error"] ?? "");
        if(preg_match("#tls:\s+(.+)#",$text,$re)){
            $text=$re[1];
            $error="{protocol_error}";
        }
        return $tpl->widget_h("red",ico_link,"<span style='font-size:16px'>".htmlspecialchars($text)."</span>",$error);
    }
    if(!isset($data["Info"])){
        return $tpl->widget_h("red",ico_link,"{error}","{protocol_error}");
    }
    $json2=json_decode($data["Info"],true);
    if(!is_array($json2)){
        return $tpl->widget_h("red",ico_link,"{error}","{protocol_error}");
    }
    // Le maitre peut repondre Status=false (licence, par exemple) avec un HTTP 200.
    if(isset($json2["Status"]) && !$json2["Status"]){
        return $tpl->widget_h("red",ico_link,"<span style='font-size:16px'>".htmlspecialchars((string)($json2["Error"] ?? ""))."</span>","{disconnected}");
    }

    $hostname=htmlspecialchars((string)($json2["Hostname"] ?? ""));
    $lines=array();
    $ver=trim((string)($json2["ArticaVer"] ?? ""));
    if($ver<>""){
        $sp=intval($json2["ServicePack"] ?? 0);
        $hf=intval($json2["Hotfix"] ?? 0);
        $v="Artica ".htmlspecialchars($ver);
        if($sp>0){$v.=" SP$sp";}
        if($hf>0){$v.=" &middot; hotfix $hf";}
        $lines[]=$v;
    }
    $lines[]="{response_time}: $ms ms";
    $cn=trim((string)($json2["client"]["CommonName"] ?? ""));
    if($cn<>""){
        $lines[]="{cluster_seen_as} ".htmlspecialchars($cn);
    }
    $small="";
    foreach($lines as $l){
        $small.="<br><small style='color:white;font-size:12px'>$l</small>";
    }
    return $tpl->widget_h("green",ico_link,"<span style='font-size:22px'>$hostname</span>$small","{connected_to}");
}

// Carte « certificat client » : emetteur et echeance reels du certificat importe.
function cluster_certificate_card():string{
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){return "";}
    $cert=cluster_client_certificate();
    if(!isset($cert["name"]) || $cert["name"]==""){return "";}

    $state="<span class='ccl-ok'>{valid}</span>";
    if($cert["notafter"]>0){
        $days=(int)floor(($cert["notafter"]-time())/86400);
        if($days<0){
            $state="<span class='ccl-bad'>{expired}</span>";
        }elseif($days<30){
            $state="<span class='ccl-warn'>{expire_soon}: {expire_in} $days {days}</span>";
        }else{
            $state="<span class='ccl-ok'>{expire_in} $days {days}</span>";
        }
    }
    $issuer="";
    if($cert["issuer"]<>""){
        $issuer="<div class='ccl-sub'>{issuer}: ".htmlspecialchars($cert["issuer"])."</div>";
    }
    return "<div class='ccl-card'><div class='ccl-ico'><i class='".ico_certificate."'></i></div><div class='ccl-body'>"
        ."<div class='ccl-title'>{client_certificate}</div>"
        ."<div class='ccl-sub'>".htmlspecialchars($cert["name"])."</div>"
        .$issuer
        ."<div class='ccl-sub'>$state</div>"
        ."</div></div>";
}

// Emise hors de _ENGINE_parse_body : le moteur lirait chaque regle CSS comme un {jeton}.
function ccl_style():string{
    if(isset($GLOBALS["CCL_STYLE"])){return "";}
    $GLOBALS["CCL_STYLE"]=true;
    return "<style>
.ccl-card{display:flex;gap:14px;align-items:flex-start;background:#fff;border:1px solid #bcc9c6;border-radius:12px;padding:16px 18px;margin:10px 0}
.ccl-ico{font-size:22px;line-height:1;color:#006a62;flex:0 0 auto;margin-top:2px}
.ccl-body{min-width:0}
.ccl-title{font-size:14px;font-weight:700;color:#111d23}
.ccl-sub{font-size:13px;color:#3d4947;margin-top:4px;overflow-wrap:anywhere}
.ccl-ok{color:#006a62;font-weight:600}
.ccl-warn{color:#865300;font-weight:600}
.ccl-bad{color:#ba1a1a;font-weight:600}
</style>";
}

function status(){
   $tpl=new template_admin();

   if($GLOBALS["CLUSTER_PORT_FEATURE"]) {
       $html[]=cluster_Status();
   }
   $html[]=package_status();
   $html[]=cluster_certificate_card();
   $html[]=unbound_client_status();

   echo ccl_style().$tpl->_ENGINE_parse_body($html);
   return false;
}
