<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["decision-top"])){decision_top();exit;}
if(isset($_POST["register"])){register_save();exit;}
if(isset($_GET["register-js"])){register_js();exit;}
if(isset($_GET["register-popup"])){register_popup();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["center-flat"])){flat_config();exit;}
if(isset($_GET["active-js"])){active_js();exit;}
if(isset($_GET["RecordsPerDB-js"])){RecordsPerDB_js();exit;}
if(isset($_GET["RecordsPerDB-popup"])){RecordsPerDB_popup();exit;}
if(isset($_POST["RecordsPerDB"])){RecordsPerDB_save();exit;}
page();
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLUSTER_CLI=true;


    $html=$tpl->page_header("DecisionIP","fas fa-helmet-battle",
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
    "dialogInstance1.close();$download",100,100000);
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
        $html[]="<td style='width:33%'>$widget_total_packets</td>";
        $html[]="<td style='width:33%'>$widget_bandwidht</td>";
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
    $html[]="<td style='width:250px;vertical-align: top'></td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px'>
    <div id='decision-top' style='margin-bottom: 15px'></div>
    <div id='decision-center'></div></td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";

    $download=$tpl->framework_buildjs("/decisionip/fetchs",
        "decisionip.down","decisionip.down.log","progress-decisionip-restart");
    $topbuttons[] = array($download, ico_download, "{update2}");


    $register="Loadjs('$page?register-js=yes&renew=yes');";
    $topbuttons[] = array($register, ico_certificate, "{renew_license}");

    $reconfigure=$tpl->framework_buildjs("/decisionip/reconfigure",
        "decisionip.reconf","decisionip.reconf.log","progress-decisionip-restart");
    $topbuttons[] = array($reconfigure, ico_retweet, "{reconfigure}");

    $TINY_ARRAY["TITLE"]="DecisionIP";
    $TINY_ARRAY["ICO"]="fas fa-helmet-battle";
    $TINY_ARRAY["EXPL"]="{decisionIPAbout}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]=$jstiny;
    $html[]=$tpl->RefreshInterval_js("decision-center",$page,"center-flat=yes");
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function DecisionIPConfig(){
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DecisionIPConfig"));
    if (json_last_error()> JSON_ERROR_NONE) {
        $arr["dipaa"]=0;
        $arr["dipspam"]=0;
        $arr["dipentor"]=0;
        $arr["dippx"]=0;
        $arr["dipvpn"]=0;
        $arr["diphoney"]=0;
        $arr["records_per_db"]=10000;
        error_log("Set default config!");
        $json=json_decode(json_encode($arr));
    }
    if(!property_exists($json,"records_per_db")){
        $json->records_per_db=1000;
    }

    return $json;
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

function flat_config():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsonData=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DecisionIPJson");

    if(strlen($jsonData)<5){
        $go=$tpl->button_autnonome("{register}","Loadjs('$page?register-js=yes');",ico_field,"AsWebMaster",335,"btn-warning");
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{freeintegration}||<p style='font-size:16px'>{decisionIPAboutErr1}</p><div style='text-align:right;margin:30px'>$go</div>"));
        return true;
    }

    if (json_last_error()> JSON_ERROR_NONE) {
        $go=$tpl->button_autnonome("{register}","Loadjs('$page?register-js=yes');",ico_field,"AsWebMaster",335,"btn-warning");
        echo $tpl->_ENGINE_parse_body($tpl->div_warning("{freeintegration}||<p style='font-size:16px'>{decisionIPAboutErr1}</p><div style='text-align:right;margin:30px'>$go</div>"));
        return true;
    }

    $json=DecisionIPConfig();
    $tpl->table_form_field_js("Loadjs('$page?RecordsPerDB-js=yes');","AsFirewallManager");
    $tpl->table_form_field_text("{records_per_db}",$tpl->FormatNumber($json->records_per_db),ico_list);


    $stats=GetStats();
    $errors=ParseErrors($json);
    $Times=ParseTimes($json);
    $bases=array("dipaa", "dipspam", "dipentor", "dippx", "dipvpn", "diphoney");
    foreach ($bases as $base){
        $tpl=BuildDB($tpl,$base,$json,$stats,$errors,$Times);
    }

    echo $tpl->table_form_compile();
    echo "<script>LoadAjaxSilent('decision-top','$page?decision-top=yes');</script>";

    return true;
}
function active_js():bool{
    $key=$_GET["active-js"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/decisionip/enabledb/$key"));

    if(!$json->Status){
        echo "alert('$json->Error');";
        return false;
    }

    return admin_tracks("Set DecisionIP fixed database $key to enable or disable");

}
function register_popup():bool{
    $tpl=new template_admin();


    $form[]=$tpl->field_hidden("register","yes");
    $form[]=$tpl->field_email("email","{email}","",true);
    echo "<div id='register-progress' style='margin-bottom: 10px'></div>";

    $js=$tpl->framework_buildjs("/decisionip/register","decisionip.progress",
        "decisionip.log","register-progress","dialogInstance1.close();");

    echo $tpl->form_outside("",$form,"{registereMailVerif}","{apply}",$js);
    return true;
}
function register_save():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DecisionIPeMail",$_POST["email"]);
    return admin_tracks("Set {$_POST["email"]} for DecisionIP registration process");
}