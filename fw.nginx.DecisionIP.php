<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["decision-top"])){decision_top();exit;}
if(isset($_GET["register-js"])){register_js();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["center-flat"])){flat_config();exit;}
if(isset($_GET["active-js"])){active_js();exit;}
if(isset($_GET["RecordsPerDB-js"])){RecordsPerDB_js();exit;}
if(isset($_GET["RecordsPerDB-popup"])){RecordsPerDB_popup();exit;}
if(isset($_POST["RecordsPerDB"])){RecordsPerDB_save();exit;}
if(isset($_GET["decision-warn"])){decision_warn();exit;}
if(isset($_POST["userkey"])){userkey_save();exit;}
if(isset($_GET["server-key"])){serverkey_request();exit;}
if(isset($_GET["services-status"])){service_status();exit;}
if(isset($_GET["decision-db"])){flat_databases();exit;}
if(isset($_GET["tiny"])){tiny();exit;}
if(isset($_POST["postdb"])){flat_databases_save();exit;}
if(isset($_GET["firewall-restart"])){firewall_restart();exit;}
if(isset($_GET["already-register-js"])){already_register_js();exit;}
if(isset($_GET["already-register-popup"])){already_register_popup();exit;}
if(isset($_POST["aukey"])){already_register_save();exit;}
if(isset($_GET["reload"])){reload();exit;}

page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;


    $html=$tpl->page_header("DecisionIP","fa-decisionip",
        "{decisionIPAbout}","$page?table-start=yes",
        "decisionip","progress-decisionip-restart",false);

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("DecisionIP",$html);
        echo $tpl->build_firewall();
        return true;
    }
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function register_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{register}";
    if(isset($_GET["renew"])){
        $title="{renew_license}";
    }
    return $tpl->js_dialog1($title,"$page?register-popup=yes",650);
}
function already_register_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $title="{already_server_key}";
    return $tpl->js_dialog1($title,"$page?already-register-popup=yes",650);
}
function already_register_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $Array=getConfig();
    $inf=$Array["data"]["sections"]["auth"];
    $form[]=$tpl->field_text("aukey","{userkey}",$inf["user_key"],true);
    $form[]=$tpl->field_text("sukey","{serverkey}",$inf["server_key"],true);

    $js[]="dialogInstance1.close();";
    $js[]="Loadjs('$page?tiny=yes');";
    $js[]="LoadAjax('decision-warn','$page?decision-warn=yes');";

    echo $tpl->form_outside("",$form,"","{apply}",implode("",$js));
    return true;
}
function already_register_save():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $aukey=urlencode($_POST["aukey"]);
    $sukey=urlencode($_POST["sukey"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/register/$aukey/$sukey"),true);
    if(!isset($json["Status"])){
        echo $tpl->post_error($tpl->js_error("{protocol_error}"));
        return false;
    }
    if(!$json["Status"]){
        echo $tpl->post_error($tpl->js_error($json["Error"]));
        return false;
    }
    return admin_tracks("Register to DecisionIP: user:$aukey server:$sukey");
}
function firewall_restart():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/firewall/restart"),true);
    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");
    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }
    return $tpl->js_ok("{success}");

}
function reload():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionipapps/reload"),true);
    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");
    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }
    return $tpl->js_ok("{success}");

}

function RecordsPerDB_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{records_per_db}","$page?RecordsPerDB-popup=yes",550);
}
function RecordsPerDB_popup():bool{
    $tpl=new template_admin();
    $json=DecisionIPConfig();

    $download=$tpl->framework_buildjs("/decisionip/fetchs",
        "decisionip.down","decisionip.down.log","progress-decisionip-restart");

    echo $tpl->BigIntegerField("RecordsPerDB","{records}","{records_per_db_decision_ip}",
        $json->records_per_db,
    "dialogInstance1.close();$download",100,200000);
    return true;
}
function RecordsPerDB_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $json=DecisionIPConfig();
    $json->records_per_db=$_POST["RecordsPerDB"];
    $data=json_encode($json);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DecisionIPConfig",$data);
    return admin_tracks("Save DecisionIP Records database to $json->records_per_db");
}
function userkey_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $usrkey=urlencode($_POST["userkey"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/userkey/$usrkey"),true);
    if(!isset($json["Status"])){
        header('HTTP/1.1 400 Protocol error');
        return true;
    }
    if(!$json["Status"]){
        header('HTTP/1.1 400 '.$json["Error"]);
        return true;
    }
    return admin_tracks("Success saving DecisionIP user key $usrkey");
}

function service_status():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/status"),);

    if(!isset($json->Status)){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_bug,"{protocol_error}","{error}"));
        return true;
    }
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_style1("red-bg",ico_bug,$json->Error,"{error}"));
        return true;
    }
    $restart=$tpl->framework_buildjs("/decisionipapps/restart",
        "decisionip.restart.progress",
        "decisionip.restart.progress.log",
        "progress-decisionip-restart");
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_DECISIONIP",$restart);

    $CurDB=CurrentIPset();
    $html[]="<div style='border:1px solid #1ab394;border-radius:5px;padding:5px'>";
    $html[]="<table style='width:100%'>";
   foreach ($CurDB as $db=>$Number){
       $color="#1ab394";
       $html[]="<tr>";
       $tmp="&nbsp;";;
       $html[]="<td style='width:1%;padding-top:5px'>";
       if($Number==0){
           $color="#cccccc";
       }
       if($db=="activeattacks-tmp"){
           continue;
       }
       $Number=$tpl->FormatNumber($Number);
       $html[]=$tpl->Round(32,$color);
       $html[]="</td>";
       $html[]="<td style='width:100%;padding-left:8px;padding-top:5px'><strong>{didb_{$db}}$tmp {$Number} {items}</strong></td>";
       $html[]="</tr>";
   }
    $html[]="</table>";
    $html[]="</div>";


    echo $tpl->_ENGINE_parse_body($html);


return true;
}

function isKey():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/config"),true);

    if(!isset($json["data"]["sections"])){
        return false;
    }
    $user_key=$json["data"]["sections"]["auth"]["user_key"];
    if(strlen($user_key)<5){
        return false;

    }
    return true;
}
function isKeysValid():bool{
    $json=getConfig();

    if(!isset($json["data"]["sections"])){
        return false;
    }
    $user_key=$json["data"]["sections"]["auth"]["user_key"];
    if(strlen($user_key)<5){
        return false;
    }
    $user_key=$json["data"]["sections"]["auth"]["server_key"];
    if(strlen($user_key)<5){
        return false;
    }
    return true;
}
function getConfig():array{

    if(isset($GLOBALS["DECSIONIP"])){
        return $GLOBALS["DECIONIP"];
    }
    $GLOBALS["DECIONIP"]=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/config"),true);
    return $GLOBALS["DECIONIP"];
}
function isServerKey():bool{

    $json=getConfig();

    if(!isset($json["data"]["sections"])){
        return false;
    }

    $user_key=$json["data"]["sections"]["auth"]["server_key"];

    if(strlen($user_key)<5){
        return false;

    }
    return true;
}
function serverkey_request():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/serverkey"),true);
    if(!isset($json["Status"])){
        return $tpl->js_error("{protocol_error}");
    }
    if(!$json["Status"]){
        return $tpl->js_error($json["Error"]);
    }
    $js[]="Loadjs('$page?tiny=yes');";
    $js[]="LoadAjax('decision-warn','$page?decision-warn=yes');";
    header("content-type: application/x-javascript");
    echo @implode("\n",$js);
    return admin_tracks("Success request DecisionIP server key");
}
function decision_warn():bool{
    $page=CurrentPageName();
    $js[]="Loadjs('$page?tiny=yes');";
    $js[]="LoadAjax('decision-warn','$page?decision-warn=yes');";

    $tpl=new template_admin();
    if(!isKey()){
        $btn=$tpl->button_autnonome("{register}: {online_help}",
            "s_PopUp('https://wiki.articatech.com/en/reverse-proxy/security/decisionip/userkey','1024','800')",
            "fa-solid fa-headset","AsWebMaster",335,"btn-warning");
        $html[]="<div class='center'><div style='width: 798px;display: inline-block;text-align: left;'>";
        $html[]=$tpl->div_warning("{register}||{userkey_missing_explain}<div style='text-align:right;margin:30px'>$btn</div>");
        $html[]="</div></div>";
        $html[]=$tpl->BigTextField("userkey","{userkey}","{userkey_portal_explain}","",
            implode(";",$js));
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    if(!isServerKey()){
        $btn=$tpl->button_autnonome("{register}: {your_server}",
            "Loadjs('$page?server-key=yes')",
            ico_refresh,"AsWebMaster",335,"btn-warning");
        $html[]="<div class='center'><div style='width: 798px;display: inline-block;text-align: left;'>";
        $html[]=$tpl->div_warning("{register}||{serverkey_missing_explain}<div style='text-align:right;margin:30px'>$btn</div>");
        $html[]="</div></div>";
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    echo "<script>$('#decision-warn').remove()</script>";
    return false;
}

function decision_top():bool{
    $tpl=new template_admin();

    $jsStats=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/stats"));
    if(!$jsStats->Status){
        $btn="";
        $widget_total_entries = $tpl->widget_style1("red-bg", ico_database, "{error}", "{error}", $btn);
        $widget_total_packets = $tpl->widget_style1("red-bg", ico_clouds,  "{error}","{error}", $btn);
        $widget_bandwidht = $tpl->widget_style1("red-bg", ico_nic,  "{error}","{error}", $btn);
        $html[]="<table style='width:100%'>";
        $html[]="<tr>";
        $html[]="<td style='width:33%'>$widget_total_entries</td>";
        $html[]="<td style='width:33%;padding-left:5px'>$widget_total_packets</td>";
        $html[]="<td style='width:33%;padding-left:5px'>$widget_bandwidht</td>";
        $html[]="</tr>";
        $html[]="</table>";

        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $Stats=json_decode($jsStats->Info);


   // object(stdClass)#9 (5) { ["timestamp"]=> int(1764898329) ["total_entries"]=> int(1000) ["total_packets"]=> int(0) ["total_bytes"]=> int(0) ["ipsets"]

            $btn="";
        $total_entries=$tpl->FormatNumber($Stats->total_entries);
        $widget_total_entries = $tpl->widget_style1("green-bg", ico_database, "{records}", $total_entries, $btn);
        if($Stats->total_entries==0){
            $widget_total_entries = $tpl->widget_style1("gray-bg", ico_database, "{records}", $total_entries, $btn);
        }
        $total_packets=$tpl->FormatNumber($Stats->total_packets);
        $widget_total_packets = $tpl->widget_style1("green-bg", ico_clouds,  "{packets}",$total_packets, $btn);
        if($Stats->total_packets==0){
            $widget_total_packets = $tpl->widget_style1("gray-bg", ico_clouds,  "{packets}",$total_packets, $btn);
        }

        $total_bytes=$Stats->total_bytes/1024;
        $total_bytesx=FormatBytes($total_bytes);
        $widget_bandwidht = $tpl->widget_style1("green-bg", ico_nic,  "{bandwidth}",$total_bytesx, $btn);
        if($Stats->total_bytes==0){
            $widget_bandwidht = $tpl->widget_style1("gray-bg", ico_nic,  "{bandwidth}",$total_bytesx, $btn);
        }



    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_total_entries</td>";
    $html[]="<td style='width:33%;padding-left:10px'>$widget_total_packets</td>";
    $html[]="<td style='width:33%;padding-left:10px'>$widget_bandwidht</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table_start():bool{
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;
    $page=CurrentPageName();
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:330px;vertical-align: top'><div id='decision-ip-agent-status' style='width:330px'></div></td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>
    <div id='decision-warn' style='margin-bottom: 20px'></div>
    <div id='decision-top' style='margin-top:-7px'></div>
    <div id='decision-center'></div>
    <div id='decision-db' style='margin-top:10px'></div>
    </td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $js=$tpl->RefreshInterval_js("decision-ip-agent-status",$page,"services-status=yes");

    $html[]="Loadjs('$page?tiny=yes');";
    $html[]="LoadAjax('decision-warn','$page?decision-warn=yes');";
    $html[]="LoadAjax('decision-db','$page?decision-db=yes');";
    $html[]=$js;

    $isKey=isKeysValid();
    if($isKey) {
        $html[]="LoadAjax('decision-center','$page?center-flat=yes');";
    }
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function tiny(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?firewall-restart=yes')", ico_refresh, "{your_firewall}: {restart}");
    $topbuttons[] = array("Loadjs('$page?reload=yes')", ico_refresh, "{reload_service}");
    $topbuttons[] = array("Loadjs('$page?already-register-js=yes')",ico_server,"{already_server_key}");


    $TINY_ARRAY["TITLE"]="DecisionIP";
    $TINY_ARRAY["ICO"]="fa-decisionip";
    $TINY_ARRAY["EXPL"]="{decisionIPAbout}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    header("content-type: application/x-javascript");
    echo "Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');\n";



}


function GetStats():array{
    $jsStats=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/stats"));
    if(!$jsStats->Status){
        return array();
    }
    $Stats=json_decode($jsStats->Info);
    if(!property_exists($Stats,"ipsets")){
        return array();
    }
    $MAIN=array();
   $tpl=new template_admin();
   foreach ($Stats->ipsets as $index=>$json){
    $name=$json->name;
    $entries=$tpl->FormatNumber($json->entries);
    $packets=$tpl->FormatNumber($json->packets);
    $bytes=FormatBytes($json->bytes/1024);
    $MAIN["$name"]=array("ENTRIES"=>$entries,"PACKETS"=>$packets,"BYTES"=>$bytes);
   };
   return $MAIN;
}
function ParseErrors($json):array{
    if(!property_exists($json,"errors")){
        return array();
    }
    $MAIN=array();
    foreach ($json->errors as $list=>$error){
        $MAIN[$list]=$error;
    }
    return $MAIN;
}
function ParseTimes($json):array{
    if(!property_exists($json,"md5_records")){
        return array();
    }
    $MAIN=array();
    $tpl=new template_admin();
    foreach ($json->md5_records as $list=>$Info){

        if(intval($Info->last_check)>0) {
            $MAIN[$list]["LASTCHECK"] = $tpl->time_to_date($Info->last_check, true);
        }
        if(intval($Info->last_download)>0) {
            $MAIN[$list]["LASTDOWN"] = $tpl->time_to_date($Info->last_download, true);
        }

    }
    return $MAIN;
}

function BuildDB($tpl,$Name,$json,$stats,$errors,$Times){
    $Title=array(
        "dipaa"=>"DecisionIP Active Attacks",
        "dipspam"=>"DecisionIP SPAM",
        "dipentor"=>"TOR Exit nodes",
        "dippx"=>"Proxies Exit nodes",
        "dipvpn"=>"VPN Exit nodes",
        "diphoney"=>"DecisionIP HoneyPots"
    );
    $page=CurrentPageName();
    $tpl->table_form_field_js("Loadjs('$page?active-js=$Name');","AsFirewallManager");
    $ico=ico_shield;$down="";
    $Error=false;

    if(intval($json->{$Name})==0) {
        $ico = ico_shield_disabled;
        $tpl->table_form_field_bool("noright:".$Title[$Name], $json->{$Name}, $ico);
        return $tpl;
    }
    $ENTRIES=0;
    $PACKETS=0;
    $BYTES=0;
    $LASTCHECK="";
    if(isset($stats[$Name])) {
        $statsE = $stats[$Name];
        $ENTRIES = $statsE["ENTRIES"];
        $PACKETS = $statsE["PACKETS"];
        $BYTES = $statsE["BYTES"];
    }

    if(isset($Times[$Name]["LASTCHECK"])) {
        $LASTCHECK = $Times[$Name]["LASTCHECK"];
    }
    if(isset($Times[$Name])){
        $down=$Times[$Name]["LASTDOWN"];
        if(strlen($down)<5 && strlen($LASTCHECK)>1) {
            $down=$LASTCHECK;
        }
    }
    if(strlen($down)>2){
        $down=" ($down)";
    }
    $Text="{records} $ENTRIES$down - $PACKETS/$BYTES";
    if(isset($errors[$Name])){
        $sError=$errors[$Name];
        if(strlen($sError)>2){
            $Error=true;
            $Text="$Text<br><small><i><span class='text-error' style='font-weight: normal;font-size:11px'>$LASTCHECK: $sError</span></i></small>";
        }
    }
    $tpl->table_form_field_text("noright:".$Title[$Name],$Text,$ico,$Error);
    return $tpl;
}

function flat_databases(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $databases=GetDatabases();



    foreach ($databases as $db=>$enabled){

        $title="{didb_{$db}}";
        $explain="{didb_{$db}_explain}";
        if($db=="datashield"){
            $title="Data-Shield IPv4 Blocklist";
            $explainZ[]="<table style='width:100%;'>";
            $explainZ[]="<tr><td style='width:1%;vertical-align: top;padding:3px' nowrap><img src='img/lminne.png'></td><td style='padding-left:10px'>{laurentMinneIDS}</td></tr></table>";
            $explain=@implode("",$explainZ);
        }

        $html[]=$tpl->BigCircleCheckbox("postdb:$db|enabled",$title,$explain,$enabled);

    }

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function CurrentIPset():array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/ipsets"),true);
    $databases=array();
    foreach ($json as $ipset){
        if(!$ipset["enabled"]){
            continue;
        }
        $databases[$ipset["database"]]=$ipset["entries"];
    }
    return $databases;
}


function flat_config():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    $health=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/health"),true);
    if(isset($health["Version"])){
        //print_r($health);
        $tpl->table_form_field_text("{version}",$health["Version"],ico_infoi);
        $tpl->table_form_field_text("{license}",$health["license"],ico_certificate);
        $tpl->table_form_field_text("{buffer}",$tpl->FormatNumber($health["buffer"]),ico_list_opt);
        if(isset($health["downerror"]) && $health["downerror"]==true){
            $zdate=$tpl->time_to_date($health["last_download_error_at"],true);
            $tpl->table_form_field_text("{error}","<small>$zdate: {$health["last_down_error"]}</small>",ico_bug,true);

        }

    }
    $html[]=$tpl->table_form_compile();
    $html[]="<script>LoadAjaxSilent('decision-top','$page?decision-top=yes');Loadjs('$page?tiny=yes');</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function GetDatabases():array{
    $dbIn=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/databases"),true);
    if(!isset($dbIn["success"])){
        return array();
    }
    foreach ($dbIn["data"]["databases"] as $i=>$ligne){
        $dbnames[$ligne["name"]]=$ligne["enabled"];
    }
    return $dbnames;
}




function flat_databases_save():bool{

    $RequiredDb=$_POST["postdb"];
    $Renabled=intval($_POST["enabled"]);
    $dbs=GetDatabases();
    foreach ($dbs as $db=>$enabled){
        if($db==$RequiredDb){
            continue;
        }
        if($enabled){
            $data["databases"][]=$db;
        }
    }
    if($Renabled){
        $data["databases"][]=$RequiredDb;
    }

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_PUT_JSON("/decisionip/databases", $data));
    if (!$json) {
        // curl or JSON decode failure
        header('HTTP/1.1 400 Protocol error');
        return false;
    }

    if (!$json->success) {
        header("HTTP/1.1 400 {$json->error}");
        return false;
    }
    return admin_tracks("DecisionIP set $RequiredDb to $Renabled");
}