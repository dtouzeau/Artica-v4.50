<?php
//SP 100
$GLOBALS["CLUSTER_PORT_FEATURE"]=true;
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.templates-simple.inc");
include_once(dirname(__FILE__)."/ressources/class.snapshots.blacklists.inc");
if(isset($_GET["build-page"])){build_page();exit;}
// Ancien point d'entree du contenu : garde pour les liens existants.
if(isset($_GET["tabs"])){build_page();exit;}
if(isset($_GET["reload-www"])){reload_www();exit;}
if(isset($_GET["stop-www"])){stop_www();exit;}
if(isset($_GET["interface-js"])){interface_js();exit;}
if(isset($_GET["interface-popup"])){interface_popup();exit;}
if(isset($_GET["Launch"])){Launch();exit;}
if(isset($_GET["params-js"])){parameters_js();exit;}
if(isset($_GET["parameters-tab"])){parameters_tab();exit;}
if(isset($_GET["flat-parameters-cluster-master"])){flat_parameters();exit;}
if(isset($_GET["status"])){status();exit;}

if(isset($_POST["ClusterServiceInterface"])){Save();exit;}
if(isset($_POST["ClusterReplicateOfficalDatabases"])){Save();exit;}
if(isset($_GET["parameters-popup"])){parameters_popup();exit;}
if(isset($_GET["remove-node-js"])){remove_node_js();exit;}
if(isset($_POST["remove-node-uuid"])){remove_node();exit;}
page();

function cluster_master_admin():bool{
    $users=new usersMenus();
    return (bool)$users->AsSystemAdministrator;
}

function reload_www():bool{
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    if(!cluster_master_admin()){$tpl->js_error("{ERROR_NO_PRIVS2}");return false;}

    $pr=$tpl->framework_buildjs("/cluster/server/reload",
        "cluster-server.reload.progress",
        "cluster-server.reload.progress.log",
        "progress-cluster-restart");
    echo $pr;
    return true;

}
function stop_www():bool{
    $tpl=new template_admin();
    header("content-type: application/x-javascript");
    if(!cluster_master_admin()){$tpl->js_error("{ERROR_NO_PRIVS2}");return false;}
    $pr=$tpl->framework_buildjs("/cluster/server/stop",
        "cluster-server.reload.progress",
        "cluster-server.reload.progress.log",
        "progress-cluster-restart");
    echo $pr;
    return true;

}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("Cluster ({master_mode})",
        "fas fa-clone","{pdns_master_mode_explain}","$page?build-page=yes",
        "cluster-master","progress-cluster-restart",false,"table-cluster-master");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Cluster Master",$html);
        echo $tpl->build_firewall();return true;}
    echo $tpl->_ENGINE_parse_body($html);
    return true;

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

    $jsBuild=$tpl->framework_buildjs("/cluster/server/build",
        "artica.cluster.progress","artica.cluster.log",
        "progress-cluster-restart","LoadAjaxSilent('cluster-status','$page?status=yes');");
    $topbuttons=array();
    if(cluster_master_admin()) {
        $topbuttons[] = array($jsBuild, ico_file_zip, "{build2}: {cluster_package}");
    }
    $s_PopUp="s_PopUp('https://wiki.articatech.com/en/system/clusterv2','1024','800')";
    $topbuttons[] = array($s_PopUp,ico_support, "{help}");

    $TINY_ARRAY["TITLE"]="Cluster ({master_mode})";
    $TINY_ARRAY["ICO"]="fas fa-clone";
    $TINY_ARRAY["EXPL"]="{pdns_master_mode_explain}";
    $TINY_ARRAY["URL"]="cluster-master";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $end[]="</td>";
    $end[]="</tr>";
    $end[]="</table>";
    // Le TINY apres les onglets, dans son propre script (recette des pages).
    $end[]="<script>";
    $end[]=$tpl->RefreshInterval_js("cluster-status",$page,"status=yes");
    $end[]=$jstiny;
    $end[]="</script>";
    echo $tpl->_ENGINE_parse_body($html).$tabs.$tpl->_ENGINE_parse_body($end);
    return true;
}

function remove_node_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    if(!cluster_master_admin()){$tpl->js_error("{ERROR_NO_PRIVS2}");return false;}
    // Les deux valeurs finissent dans du JavaScript : caracteres d'UUID et de nom d'hote seulement.
    $uuid=preg_replace('/[^A-Za-z0-9._:-]/','',(string)($_GET["remove-node-js"] ?? ""));
    $name=preg_replace('/[^A-Za-z0-9._-]/','',(string)($_GET["name"] ?? ""));
    if($name==""){$name=$uuid;}
    return $tpl->js_confirm_delete($name,"remove-node-uuid",$uuid,"LoadAjaxSilent('cluster-status','$page?status=yes');");
}

// POST de js_confirm_delete : ne rien ecrire en cas de succes.
function remove_node(): bool {
    $tpl=new template_admin();
    if(!cluster_master_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $uuid = (string)($_POST["remove-node-uuid"] ?? '');
    if ($uuid === '') { return false; }

    $data = json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterMasterNodes"), true);
    if (!is_array($data) || !isset($data["nodes"]) || !is_array($data["nodes"])) {
        return false;
    }

    unset($data["nodes"][$uuid]);

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterMasterNodes", json_encode($data));
    return admin_tracks("Remove cluster slave $uuid");
}
function interface_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{listen}/{certificate}","$page?interface-popup=yes",550 );
}

function parameters_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{parameters}","$page?parameters-popup=yes",550 );
}

function parameters_tab():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div id='flat-parameters-cluster-master' style='margin-top:10px'></div>";
    $html[]="<script>LoadAjaxSilent('flat-parameters-cluster-master','$page?flat-parameters-cluster-master=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function interface_popup():bool{

    $ClusterServiceCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServiceCertificate");
    $page=CurrentPageName();
    $tpl=new template_admin();
    $flat="LoadAjaxSilent('flat-parameters-cluster-master','$page?flat-parameters-cluster-master=yes');";
    if(strlen($ClusterServiceCertificate)<3){
        $jsRestart=$tpl->framework_buildjs("/cluster/server/create/certificate",
            "artica.cluster.progress","artica.cluster.log",
            "progress-cluster-restart",$flat);

        $html[]="<div style='margin:30px' class='center'>";
        $html[]=$tpl->button_autnonome("{create_certificate}","dialogInstance2.close();$jsRestart","fas fa-file-certificate","AsSystemAdministrator",350);
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
    "dialogInstance2.close();$flat","AsSystemAdministrator");
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

    $tpl->table_form_section("{listen}");
    $tpl->table_form_field_js("Loadjs('$page?interface-js=yes')");

    if (strlen($ClusterServiceCertificate) > 3) {
        if($HaClusterClient==0){
            $tpl->table_form_field_text("{listen}", "<span style='text-transform:none'>https://".htmlspecialchars($ClusterServiceInterface).":$ClusterServicePort</span>", ico_interface);

            $CertID=cluster_master_cert_id();
            if ($CertID == 0) {
                $jsRestart = $tpl->framework_buildjs("/cluster/server/create/certificate",
                    "artica.cluster.progress", "artica.cluster.log",
                    "progress-cluster-restart", "LoadAjaxSilent('flat-parameters-cluster-master','$page?flat-parameters-cluster-master=yes');");
                $tpl->table_form_field_js($jsRestart);
                $tpl->table_form_field_button("{create_certificate}", "{create_certificate}", ico_certificate);
            }

            include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
            $cdb = new CertificatesDB();
            $Count = count($cdb->subList(intval($CertID)));
            $tpl->table_form_field_js("Loadjs('fw.certificates-center.php?subcertificates-js=$CertID&OnlyClient=yes&NoPass=yes')");
            if ($Count == 0) {
                $tpl->table_form_field_bool("{client_certificates}", "$Count", ico_interface);
            } else {
                $tpl->table_form_field_text("{client_certificates}", "$Count", ico_interface);
            }

        }
    } else {
        $tpl->table_form_field_text("{listen}", "<span style='text-transform:none'>{no_certificate}</span>", ico_interface);
   }

    $tpl->table_form_section("{replication}");
    $tpl->table_form_field_js("Loadjs('$page?params-js=yes')");
    $tpl->table_form_field_bool("{ClusterReplicateOfficalDatabases}",$ClusterReplicateOfficalDatabases,ico_database);
    $tpl->table_form_field_bool("{ClusterNotReplicateWeb}",$ClusterNotReplicateWeb,ico_earth);
    $tpl->table_form_field_bool("{NotReplicateWebConsoleParameters}",$ClusterNotReplicateWebParameters,ico_earth);
    $tpl->table_form_field_bool("{ClusterNotReplicateTasks}",$ClusterNotReplicateTasks,ico_clock);
    $tpl->table_form_field_bool("{NotReplicateADParameters}",$ClusterNotReplicateAD,ico_microsoft);

    echo $tpl->table_form_compile(2);
    return true;

}

// Certificat serveur du maitre : ses sous-certificats sont les certificats
// clients des noeuds esclaves (un par noeud).
function cluster_master_cert_id():int{
    $ClusterServiceCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterServiceCertificate");
    if(strlen($ClusterServiceCertificate)<4){return 0;}
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb = new CertificatesDB();
    [$exists, $CertID] = $cdb->exists($ClusterServiceCertificate);
    if (!$exists) { return 0; }
    return intval($CertID);
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
    // La modale ne se fermait pas apres l'enregistrement.
    $html=$tpl->form_outside("",$form,null,"{apply}","dialogInstance2.close();LoadAjaxSilent('flat-parameters-cluster-master','$page?flat-parameters-cluster-master=yes');",
        "AsSystemAdministrator",true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Save():bool{
    $tpl=new template_admin();
    if(!cluster_master_admin()){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
    $tpl->SAVE_POSTs();
return true;

}

function Launch():bool{
    if(!cluster_master_admin()){return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/cluster/server/build");
    return true;
}

function status():bool{
    $tpl=new template_admin();
    $html[]=package_status();
    $html[]=local_service_status();
    echo ccm_style().$tpl->_ENGINE_parse_body($html);
    return false;
}

function package_status():string{
    $tpl=new template_admin();
    $PowerDNSEnableClusterMasterTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMasterTime"));
    $PowerDNSEnableClusterMasterSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMasterSize"));

    if($PowerDNSEnableClusterMasterTime==0){
        return $tpl->widget_h("gray",ico_database,"-","{cluster_package}");
    }
    $title_time=$tpl->time_to_date($PowerDNSEnableClusterMasterTime,true);
    $time=distanceOfTimeInWords($PowerDNSEnableClusterMasterTime,time());
    $size=FormatBytes($PowerDNSEnableClusterMasterSize/1024);
    $ACLUSTER_VERSION=htmlspecialchars((string)$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ACLUSTER_VERSION"));
    return $tpl->widget_h("green",ico_database,
       "<span style='font-size:22px'>$ACLUSTER_VERSION</span><br><small style='color:white;font-size:12px'>$time ($size)</small><br><small style='color:white;font-size:12px'>$title_time</small>",
       "{cluster_package}");
}

function unbound_backage_status($json):string{
    $UnboundEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled");
    if ($UnboundEnabled==0){
        return "";

    }
    $tpl=new template_admin();

    if(!property_exists($json,"Unbound") || !property_exists($json->Unbound,"Time")){
        return $tpl->widget_h("grey",ico_database,"-","{APP_UNBOUND} {cluster_package}");
    }

    $Time=intval($json->Unbound->Time);
    $title_time=$tpl->time_to_date($Time,true);
    $time=distanceOfTimeInWords($Time,time());
    // La valeur principale etait l'horodatage Unix brut (« 1790669568 »).
    return $tpl->widget_h("green",ico_database,"<span style='font-size:22px'>$time</span><br><small style='color:white;font-size:12px'>$title_time</small>","{cluster_package}: {APP_UNBOUND}");
}

// Service d'ecoute du maitre : interroge son propre /status, comme un esclave.
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
    $addr=htmlspecialchars("$ClusterServiceIfaceTov4:$ClusterServicePort");
    $admin=cluster_master_admin();
    $btnReload=array();
    $btnStop=array();
    if($admin){
        $btnReload=array("name"=>"{reload}","js"=>"Loadjs('$page?reload-www=yes');","ico"=>ico_retweet);
        $btnStop=array("name"=>"{stop}","js"=>"Loadjs('$page?stop-www=yes');","ico"=>ico_stop);
    }

    $curl = new ccurl("https://$ClusterServiceIfaceTov4:$ClusterServicePort/status", true);
    $curl->NoLocalProxy();
    if (!$curl->get()) {
        $btn=array();
        if($admin){$btn=array("name"=>"{start}","js"=>"Loadjs('$page?reload-www=yes');","ico"=>ico_run);}
        return $tpl->widget_h("red",ico_clouds,"<span style='font-size:16px'>$addr<br>".htmlspecialchars((string)$curl->error)."</span>","{local_service}: {error}",$btn);
    }

    $json=json_decode($curl->data);
    if (json_last_error() > JSON_ERROR_NONE || !is_object($json) || !property_exists($json,"Status")) {
        return $tpl->widget_h("red",ico_clouds,"<span style='font-size:16px'>$addr</span>","{local_service}: {protocol_error}",$btnReload);
    }

    if(!$json->Status){
        return $tpl->widget_h("red",ico_clouds,"<span style='font-size:16px'>".htmlspecialchars((string)$json->Error)."</span>","{local_service}: {error}",$btnReload);
    }

    // « Info » compte les requetes servies depuis le demarrage du service, pas
    // des connexions ouvertes : le libelle le dit.
    $requests=$tpl->FormatNumber(intval($json->Info));
    $card=$tpl->widget_h("green",ico_clouds,
        "<span style='font-size:22px'>$addr</span><br><small style='color:white;font-size:12px'>{requests}: $requests</small>",
        "{local_service}",$btnReload,$btnStop);

    return unbound_backage_status($json).$card.ClusterClients();
}

// Liste des noeuds esclaves : une carte, une ligne par noeud (et non une tuile
// par noeud, qui repoussait tout le reste sous la ligne de flottaison).
function ClusterClients():string{
    $page=CurrentPageName();
    $admin=cluster_master_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterMasterNodes"),true);
    $nodes=array();
    if(is_array($json) && isset($json["nodes"]) && is_array($json["nodes"])){
        $nodes=$json["nodes"];
    }
    // Un esclave interroge le maitre chaque minute : au-dela de 5 minutes, il ne repond plus.
    uasort($nodes,function($a,$b){return intval($b["connectionTime"] ?? 0)-intval($a["connectionTime"] ?? 0);});

    $rows=array();
    $online=0;
    foreach($nodes as $uuid=>$node){
        $ips=$node["ips"] ?? array();
        $ip=is_array($ips) && count($ips)>0?(string)$ips[0]:"";
        $hostname=(string)($node["hostname"] ?? "");
        if($hostname==""){$hostname=(string)($node["connectionName"] ?? $uuid);}
        if(strpos($hostname,".")>0){$hostname=explode(".",$hostname)[0];}
        $connectionTime=intval($node["connectionTime"] ?? 0);
        $count=intval($node["connectionsCount"] ?? 0);

        if($connectionTime>0 && DistanceInMns($connectionTime)<=5){
            $online++;
            $badge="<span class='ccm-badge ccm-on'>{online}</span>";
            $ico="fa-solid fa-display";
        }else{
            $badge="<span class='ccm-badge ccm-off'>{offline}</span>";
            $ico="fa-solid fa-display-medical";
        }
        $seen="{never}";
        if($connectionTime>0){$seen=distanceOfTimeInWords($connectionTime,time());}

        $act="";
        if($admin){
            $js="Loadjs('$page?remove-node-js=".urlencode($uuid)."&name=".urlencode($hostname)."');";
            $act="<a class='ccm-act' href=\"javascript:blur();\" OnClick=\"$js\" title='{remove}'><i class='".ico_trash."'></i></a>";
        }
        $rows[]="<div class='ccm-node'>"
            ."<div class='ccm-node-ico'><i class='$ico'></i></div>"
            ."<div class='ccm-node-body'>"
            ."<div class='ccm-node-name'>".htmlspecialchars($hostname)." $badge</div>"
            ."<div class='ccm-node-meta'>".htmlspecialchars($ip)."</div>"
            ."<div class='ccm-node-meta'>{last_seen}: $seen &middot; {connections}: ".number_format($count,0,""," ")."</div>"
            ."</div>$act</div>";
    }

    $html[]="<div class='ccm-card'>";
    $html[]="<div class='ccm-head'><span class='ccm-title'><i class='fa-solid fa-sitemap'></i>&nbsp;{cluster_slave_nodes}</span>";
    $html[]="<span class='ccm-count'>$online / ".count($nodes)." {online}</span></div>";
    if(count($rows)==0){
        $html[]="<div class='ccm-empty'>{none}</div>";
    }else{
        $html[]=@implode("",$rows);
    }
    // Ajouter un noeud = lui emettre son certificat client, signe par le certificat du maitre.
    $CertID=$admin?cluster_master_cert_id():0;
    if($CertID>0){
        $js="Loadjs('fw.certificates-center.php?subcertificates-js=$CertID&OnlyClient=yes&NoPass=yes');";
        $html[]="<a class='ccm-add' href=\"javascript:blur();\" OnClick=\"$js\"><i class='".ico_plus."'></i>&nbsp;{new_node}</a>";
    }
    $html[]="</div>";
    return @implode("",$html);
}
function DistanceInMns($time):int{
    $data1 = $time;
    $data2 = time();
    $difference = ($data2 - $data1);
    return round($difference/60);
}

// Emise hors de _ENGINE_parse_body : le moteur lirait chaque regle CSS comme un {jeton}.
function ccm_style():string{
    if(isset($GLOBALS["CCM_STYLE"])){return "";}
    $GLOBALS["CCM_STYLE"]=true;
    return "<style>
.ccm-card{background:#fff;border:1px solid #bcc9c6;border-radius:12px;padding:16px 18px;margin:10px 0}
.ccm-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}
.ccm-title{font-size:15px;font-weight:700;color:#111d23}
.ccm-count{font-size:12px;font-weight:600;color:#3d4947;background:#e3f0f8;border-radius:10px;padding:2px 10px;white-space:nowrap}
.ccm-node{display:flex;align-items:flex-start;gap:12px;padding:10px 0;border-top:1px solid rgba(188,201,198,.4)}
.ccm-node:first-of-type{border-top:0}
.ccm-node-ico{width:36px;height:36px;border-radius:8px;background:rgba(0,106,98,.1);color:#006a62;display:flex;align-items:center;justify-content:center;font-size:16px;flex:0 0 auto}
.ccm-node-body{flex:1 1 auto;min-width:0}
.ccm-node-name{font-size:14px;font-weight:700;color:#111d23;overflow-wrap:anywhere}
.ccm-node-meta{font-size:12px;color:#3d4947;margin-top:2px;overflow-wrap:anywhere}
.ccm-badge{display:inline-block;font-size:10px;font-weight:700;text-transform:uppercase;padding:2px 7px;border-radius:4px;margin-left:6px;vertical-align:middle}
.ccm-on{background:#26a69a;color:#fff}
.ccm-off{background:#865300;color:#fff}
.ccm-act{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;color:#3d4947;flex:0 0 auto}
.ccm-act:hover{background:#ffdad6;color:#ba1a1a}
.ccm-empty{padding:14px 0;text-align:center;color:#6d7a77;font-size:13px}
.ccm-add{display:block;text-align:center;margin-top:10px;padding:9px;border-radius:8px;background:#e3f0f8;color:#006a62!important;font-weight:600;text-decoration:none!important}
.ccm-add:hover{background:#d0e6f2}
</style>";
}
