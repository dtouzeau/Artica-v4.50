<?php
$GLOBALS["PEITYCONF"]="{ width:150,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
include_once(dirname(__FILE__)."/ressources/class.haproxy.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");

if(isset($_GET["add-proxy"])){add_proxy_js();exit;}
if(isset($_GET["switch-prod"])){switch_prod();exit;}
if(isset($_POST["switch-prod"])){switch_prod_confirmed();exit;}
if(isset($_GET["backend-zoom-js"])){backend_zoom_js();exit;}
if(isset($_GET["backend-zoom-start"])){backend_zoom_start();exit;}
if(isset($_GET["backend-zoom"])){backend_zoom();exit;}
if(isset($_GET["backend-tab"])){backend_tab();exit;}
if(isset($_GET["hacluster-client-status"])){backend_zoom_hacluster_client();exit;}
if(isset($_GET["btn"])){td_buttons();exit;}
if(isset($_GET["apikey-js"])){apikey_js();exit;}
if(isset($_GET["apikey-popup"])){apikey_popup();exit;}
if(isset($_POST["apikey"])){apikey_save();exit;}
function apikey_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["apikey-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM hacluster_backends WHERE ID=$ID");
    $title="{$ligne["backendname"]}";
    return $tpl->js_dialog3($title, "$page?apikey-popup=$ID");
}
function apikey_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["apikey-popup"]);
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT apikey FROM hacluster_backends WHERE ID=$ID");
    $APIKEY=$ligne["apikey"];
    $form[]=$tpl->field_hidden("apikey",$ID);
    $form[]=$tpl->field_password("key","{API_KEY}",$APIKEY);
    echo $tpl->form_outside("",$form,null,"{apply}",
        "dialogInstance3.close();LoadAjax('btns-$ID','$page?btn=$ID');",
        "AsSystemAdministrator"
    );
    return true;
}
function switch_prod():bool{
    $ID=$_GET["switch-prod"];
    $tpl=new template_admin();
    $page=currentPageName();
    $stats="";
    if(isset($_GET["stats"])) {
        $stats = $_GET["stats"];
    }
   return  $tpl->js_confirm_execute("{set_production_state}","switch-prod",$ID,"LoadAjaxSilent('backend-zoom-start-$ID','$page?backend-zoom=$ID&stats=$stats');");

}
function switch_prod_confirmed():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ID=$_POST["switch-prod"];
    $q->QUERY_SQL("UPDATE hacluster_backends SET status='100' WHERE ID=$ID");

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/add/$ID"));
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
    return admin_tracks("Set Proxy #$ID to switch to production mode");
}
function apikey_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["apikey"];
    $key=$_POST["key"];
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $q->QUERY_SQL("UPDATE hacluster_backends SET apikey='$key' WHERE ID=$ID");
    if(!$q->ok){
        echo $tpl->post_error($q->mysql_error);
        return false;
    }

    $sock=new sockets();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/flush");
    $temp=$sock->REST_API("/hacluster/server/checkapi/node/$ID");

    $data=json_decode($temp);
    if(!$data->Status){
        echo $tpl->post_error($data->Error);
        return false;
    }
    return admin_tracks("Saving API Key for remote node $ID");
}
function backend_zoom_js():bool{
    $stats="";
    if(isset($_GET["stats"])) {
        $stats = $_GET["stats"];
    }
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["backend-zoom-js"]);
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM hacluster_backends WHERE ID=$ID");
    $title="{$ligne["backendname"]}";
    return $tpl->js_dialog2($title, "$page?backend-tab=$ID&stats=$stats");
}
function backend_tab():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["backend-tab"]);
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM hacluster_backends WHERE ID=$ID");
    $stats=$_GET["stats"];
    $array["{status}"]="$page?backend-zoom-start=$ID&stats=$stats";
    if($ligne["microproxy"]==1) {
        $array["{system}"] = "fw.hacluster.backends.system.php?ID=$ID";
    }
    echo $tpl->tabs_default($array);
    return true;
}
function ExecTtime($start,$line){
    if(!isset($GLOBALS["VERBOSE"])){return;}
    $end = microtime(true);
    $executionTime = $end - $start;
    VERBOSE("Execution time ".number_format($executionTime, 4) . " seconds",$line);
}
function backend_zoom_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["backend-zoom-start"]);
    $stats="";
    if(isset($_GET["stats"])) {
        $stats = $_GET["stats"];
    }
    echo "<div id='backend-zoom-start-$ID'></div>";
    echo "<script type='text/javascript'>";
    echo "LoadAjaxSilent('backend-zoom-start-$ID','$page?backend-zoom=$ID&stats=$stats');";
    echo "</script>";
    return true;
    //backend-zoom=$ID&stats=$stat
}

function backend_stats($ID):array{
    $json=json_decode(base64_decode($_SESSION["jsonstats"]));
    if (json_last_error() !== JSON_ERROR_NONE) {
        td_prepare();
        $json=json_decode(base64_decode($_SESSION["jsonstats"]));
    }
    $MyCode="proxy$ID";

    if(!property_exists($json,"servers")) {
        return array();
    }
    $jsonServers=$json->servers;
    if(!property_exists($jsonServers,"proxys")) {
        return array();
    }
    $jsonStats=$json->servers->proxys;
    $MyStatsOrg=array();
    $MyStats=array();

    if(property_exists($jsonStats,$MyCode)) {
        $MyStatsOrg = $jsonStats->$MyCode;
    }

    foreach ($MyStatsOrg as $key=>$val){
        $MyStats[$key]=$val;

    }
    return $MyStats;
}
function td_prepare(){
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/inproduction");
    $json=json_decode($data);
    $_SESSION["inprod"]=$json->Info;
    $_SESSION["jsonstats"]=base64_encode(json_encode($json->Stats));
    $_SESSION["backendsSess"]=base64_encode(json_encode($json->Sessions));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/clients"));
    if(property_exists($json,"backends")) {
        $GLOBALS["backends"] = $json->backends;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/stats"));
    if($json->Status) {
        foreach ($json->Stats as $SrvProxy=>$class){
            $_SESSION["BACKENDS_STATS"][$SrvProxy]=$class;
        }
    }
}
function backend_zoom():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $stats="";
    if(isset($_GET["stats"])) {
        $stats = $_GET["stats"];
    }
    $ID=intval($_GET["backend-zoom"]);
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM hacluster_backends WHERE ID=$ID");

    $title="{$ligne["backendname"]}";
    $application_state=$ligne["status"];

    $html[]="<H2>$title</H2><hr>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:250px;vertical-align: top'><div id='hacluster-client-$ID'></div></td>";
    $html[]="<td style='vertical-align: top;padding-left:15px;width:95%'>";
    $html[]="<div id='btns-$ID' style='margin-bottom: 10px'></div>";
    $html[]="<div id='reconfigure-progress-$ID'></div>";

    if(strlen($ligne["errortext"])>3){
        $html[]=$tpl->div_error($ligne["errortext"]);
    }
    $Status["UP"]="{active2}";
    $Status["DOWN"]="{down}";
    $Status["MAINT"]="{maintenance}";
    $Status["no check"]="{inactive2}";
    $Status["L7OK"]="{active2}";
    $Status["UNK"]="{unknown}";
    $Status["L4TOUT"]="{timeout2}";
    $Status["* L4TOUT"]="{timeout2}";


    $application_states[0]="{waiting_registration}";
    $application_states[1]="{error}";
    $application_states[2]="{configuring}";
    $application_states[3]="{setup_error}";
    $application_states[4]="{setup} 50%";
    $application_states[5]="PING OK";
    $application_states[6]="{configuring} 70%";
    $application_states[7]="{configuring} 80%";
    $application_states[8]="{configuring} 90%";
    $application_states[9]="{configuring} 95%";
    $application_states[10]="{configuring} 100%";
    $application_states[110]="{rebooting}";

    $MyStats=backend_stats($ID);

    if(!isset($MyStats["chkdown"])){
        $MyStats["chkdown"]=0;
    }
    if(!isset($MyStats["check_desc"])){
        $MyStats["check_desc"]="";
    }

    if(isset($MyStats["agent_status"])){
        $MyStats["status"]=$MyStats["agent_status"];
        $MyStats["check_desc"]=$MyStats["agent_desc"]." ".$MyStats["last_agt"];
    }
    if(!isset($MyStats["status"])){
        $MyStats["status"]="UNK";
    }
    if(!isset($MyStats["weight"])){
        $MyStats["weight"]=0;
    }
    if(!isset($MyStats["connect"])){
        $MyStats["connect"]=0;
    }

    $Status_text=$Status[$MyStats["status"]]."<br><small>{$MyStats["check_desc"]}</small>";
    $tpl->table_form_field_text("{ID}", $ID, ico_params);

    if($application_state==100){
        $tpl->table_form_field_text("{application_state}", "{installed}", ico_cd);
    }else{
        $tpl->table_form_field_js("Loadjs('$page?switch-prod=$ID&stats=$stats')");
        $tpl->table_form_field_text("{application_state}",$application_states[$application_state], ico_cd,true);
        $tpl->table_form_field_js("");
    }

    $tpl->table_form_field_text("{status}", $Status_text, ico_performance);

    list($IN,$OUT)=td_inout($MyStats);
    $tpl->table_form_field_text("{bandwidth}", "$IN/$OUT", ico_performance);
    $tpl->table_form_field_text("{chkdown}", $tpl->FormatNumber($MyStats["chkdown"]), ico_computer_down);
     $tpl->table_form_field_text("{weight}",$MyStats["weight"],ico_weight);
    $tpl->table_form_field_text("{connections}",$tpl->FormatNumber($MyStats["connect"]),ico_nic);
    $html[]=$tpl->table_form_compile();

    $pingjs=$tpl->RefreshInterval_js("hacluster-client-$ID",$page,"hacluster-client-status=$ID");

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>$pingjs;";
    $html[]="LoadAjax('btns-$ID','$page?btn=$ID');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function td_buttons(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ID=intval($_GET["btn"]);
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM hacluster_backends WHERE ID=$ID");
    $microproxy=$ligne["microproxy"];
    $apikey=$ligne["apikey"];

    $reconfigure_node=$tpl->framework_buildjs("/hacluster/server/notify/node/$ID",
        "hacluster.connect.$ID.progress",
        "hacluster.connect.txt",
        "reconfigure-progress-$ID");

    $updatenode=$tpl->framework_buildjs("/hacluster/server/node/pushrest/$ID",
        "hacluster.connect.$ID.progress",
        "hacluster.connect.txt",
        "reconfigure-progress-$ID");

    $resetcertenode=$tpl->framework_buildjs("/hacluster/server/node/resetcert/$ID",
        "hacluster.connect.$ID.progress",
        "hacluster.connect.txt",
        "reconfigure-progress-$ID");


    $topbuttons[] = array($reconfigure_node, ico_refresh, "{reconfigure}");
    $topbuttons[] = array($resetcertenode, ico_certificate, "{reset} {certificate}");
    if($microproxy==1){
        $ARTICAREST_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ARTICAREST_VERSION");
        $topbuttons[] = array("Loadjs('$page?apikey-js=$ID')", ico_lock, "{API_KEY}");
        if(strlen($apikey)>1) {
            $topbuttons[] = array($updatenode, ico_upload, "{update2} v$ARTICAREST_VERSION");
        }else{
            $topbuttons[] = array("", ico_upload, "{update2} v$ARTICAREST_VERSION");
        }

    }

    echo $tpl->th_buttons($topbuttons);
}

//
function add_proxy_js():bool{
    $tpl=new template_admin();
    $ID=intval($_GET["add-proxy"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/client/add/$ID"), true);
    if($json->Status){
        $tpl->js_error($tpl->_ENGINE_parse_body($json->Error));
        return true;
    }
    return false;
}
function backend_zoom_hacluster_LBState():string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["hacluster-client-status"]);
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/clients"));
    if(!$json->Status){
        return $tpl->widget_rouge("{APP_HACLUSTER}: {protocol_error}","{error}");
    }
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM hacluster_backends WHERE ID=$ID");

    $title="{$ligne["backendname"]}";

    $proxyName="proxy$ID";
    if(!property_exists($json->backends,$proxyName)){
        $wbutton[0]["name"] = "{reconnect}";
        $wbutton[0]["icon"] =ico_link;
        $wbutton[0]["js"] = "Loadjs('$page?add-proxy=$ID')";
        return $tpl->widget_rouge($title,"{not_in_production}",$wbutton,ico_unlink);
    }

    $class=$json->backends->$proxyName;

    $srv_op_state=$class->srv_op_state;
    switch ($srv_op_state) {
        case 1:
            return $tpl->widget_jaune("{APP_PARENTLB} {status}","{maintenance}");

        case 2:
            return $tpl->widget_vert("{APP_PARENTLB} {status}","{in_production}");
        case 0:
            return $tpl->widget_jaune("{APP_PARENTLB} {status}","{not_running}");
        case 3:
            return $tpl->widget_jaune("{APP_PARENTLB} {status}","{maintenance} (2)");
        default:
            return $tpl->widget_grey("{APP_PARENTLB} {status}","CODE $srv_op_state?");
    }
}

function backend_zoom_hacluster_client():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($_GET["hacluster-client-status"]);
    $start = microtime(true);

    echo backend_zoom_hacluster_LBState();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/hacluster/server/nodes/status"));
    ExecTtime($start,__LINE__);
    if(!$json->Status){
        echo $tpl->widget_rouge("{APP_HACLUSTER_CLIENT}: {protocol_error}","{error}");
        return false;

    }
    if(!property_exists($json,"Backends")){
        echo $tpl->widget_rouge("{APP_HACLUSTER_CLIENT}: {protocol_error}","{error}");
        return false;
    }
    
    
    foreach ($json->Backends as $backend){

        if($backend->ID==$ID){
            $listen_ip=$backend->Ipstr;
            $listen_port=$backend->Port;
            $buffer=$backend->AgentStatus;
            if(!$backend->Status){
                $errstr=$backend->Error;
                echo $tpl->widget_rouge("{APP_HACLUSTER_CLIENT} ($listen_ip:$listen_port) $errstr","{error}");
                return false;
            }
            break;
        }
    }

    if($buffer=="down"){
        echo $tpl->widget_rouge("{APP_HACLUSTER_CLIENT}: {status}","{down}");
        echo "<div class='center' style='margin-top:15px'>";
        echo  $tpl->button_autnonome("{refresh}","LoadAjax('hacluster-client-$ID','$page?hacluster-client-status=$ID')",ico_refresh,"AsSystemAdministrator",335,"btn-danger");
        echo "</div>";
        return false;

    }
    if($buffer=="up"){
        echo $tpl->widget_vert("{APP_HACLUSTER_CLIENT}: {status}","OK");
        return false;

    }
    if($buffer=="100%"){
        echo $tpl->widget_vert("{APP_HACLUSTER_CLIENT}: {status}","OK 100%");
        return false;

    }


    echo "<H1>$buffer ?..</H1>";
    return true;
}
function td_inout($MyStats):array{

    if(!isset($MyStats["bin"])){
        return array("","");
    }

    $bin=$MyStats["bin"];
    $bout=$MyStats["bout"];
    if(strpos($bin,".")>0){
        $floatValue = floatval($bin);
        $bin = (int)$floatValue;
    }
    if(strpos($bout,".")>0){
        $floatValue = floatval($bin);
        $bout = (int)$floatValue;
    }
    if($bin==0){
        return array("","");
    }

    return array(FormatBytes($bin/1024),FormatBytes($bout/1024));

}