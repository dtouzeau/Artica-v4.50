<?php
$GLOBALS["PEITYCONF"]="{ width:280,height:25,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";
$GLOBALS["DYNAMIC_RATE_FEATURE"]=false;
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.reverse.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.tpl.inc");
include_once(dirname(__FILE__)."/ressources/class.modsecurity.tools.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_POST["vitrification"])){vitrification_save();exit;}
if(isset($_GET["vitrification-js"])){vitrification_js();exit;}
if(isset($_GET["vitrification-disk-js"])){vitrification_disk_js();exit;}
if(isset($_GET["vitrification-switch"])){vitrification_switch();exit;}
if(isset($_GET["vitrification-popup"])){vitrification_popup();exit;}
if(isset($_GET["vitrification-disk-popup"])){vitrification_disk_popup();exit;}
if(isset($_GET["vitrification-status"])){vitrification_status();exit;}
if(isset($_GET["vitrification-run"])){vitrification_run();exit;}
if(isset($_GET["vitrification-on"])){vitrification_on();exit;}
if(isset($_GET["vitrification-off"])){vitrification_off();exit;}
if(isset($_GET["vitrification-config"])){vitrification_config();exit;}
if(isset($_POST["max_latency_ms"])){vitrification_config_save();exit;}
if(isset($_POST["max_dirsize_mb"])){vitrification_config_save();exit;}
if(isset($_POST["vitr_depth"])){vitrification_config_save();exit;}
if(isset($_POST["auto_switch"])){vitrification_config_save();exit;}
vitrification_js();

function vitrification_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["js"]);
    $sock=new socksngix($ID);
    $servicename=$sock->GetServiceName();
    return $tpl->js_dialog2("#$ID - $servicename", "$page?vitrification-popup=$ID");
}
function vitrification_disk_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["vitrification-disk-js"]);
    $sock=new socksngix($ID);
    $servicename=$sock->GetServiceName();
    return $tpl->js_dialog4("#$ID - $servicename", "$page?vitrification-disk-popup=$ID");
}
function vitrification_save():bool{
    $ID=$_POST["ID"];
    $vitrification=$_POST["vitrification"];
    $sock=new socksngix($ID);
    $sock->SET_INFO("VitrificationEnabled",$vitrification);
    $servicename=$sock->GetServiceName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/vitrification/chock/$ID");
    return admin_tracks("Set vitrification feature to $vitrification for $servicename");
}
function vitrification_status():bool{
    $page                       = CurrentPageName();
    $ID=intval($_GET["vitrification-status"]);
    $tpl=new template_admin();$tpl->CLUSTER_CLI=true;

    $sock=new socksngix($ID);
    $ligne=$sock->GetCache();

    if($ligne["vitrification_enabled"]==0) {
        return true;
    }
    $ay=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/vitrification/fetch/status/$ID"),true);

    if(!isset($ay["Status"]) OR !isset($ay["Data"])){
        return true;
    }

    $array=$ay["Data"];

    if($array["Running"]){
        $pr=$tpl->progress_barr_static($ligne["Percentage"],$ligne["LastOperation"]);
        $tpl->table_form_field_text("{status}",$pr,ico_refresh_animate);
    }else{
        if($array["LastExecutionFailed"]){
            $tpl->table_form_field_text("{status}",$ligne["LastError"],ico_bug,true);
        }else{
            $last_time="";
            if(intval($array["LastExecution"])>0){
                $last_time="&nbsp;<small>(".$tpl->time_to_date($array["LastExecution"],true)." - ".$array["LastOperation"].")</small>";
            }
            $tpl->table_form_field_text("{status}","{sleeping}$last_time","fas fa-snooze");
            $tpl->table_form_field_js("Loadjs('$page?vitrification-run=$ID');");
            $tpl->table_form_field_button("{action}","{run}","fas fa-sync");
            $tpl->table_form_field_js("");
        }
    }
    $ay=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/vitrification/storage/status/$ID"),true);

     if(!isset($ay["Status"]) OR !isset($ay["Data"])){
         echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());
         return true;
     }
     $array=$ay["Data"];
     $TotalSizeBytes=$array["TotalSizeBytes"];
     $MaxSizeBytes=$array["MaxSizeBytes"];
     $perc=round(($TotalSizeBytes/$MaxSizeBytes)*100,2);
     if($TotalSizeBytes>1024){
         $TotalSizeBytesT=FormatBytes($TotalSizeBytes/1024);
         $MaxSizeBytes=FormatBytes($MaxSizeBytes/1024);

         $tpl->table_form_field_js("Loadjs('$page?vitrification-disk-js=$ID');");
         $tpl->table_form_field_text("{directory_size}","$TotalSizeBytesT/$MaxSizeBytes ($perc%)",ico_weight);
         $tpl->table_form_field_js("");

    }
    if(count($array["Domains"])>0){
        foreach ($array["Domains"] as $dom=>$domain){
            //$path=$domain["Path"];
            $SizeBytes=FormatBytes($domain["SizeBytes"]/1024);
            $Exists=$domain["Exists"];
            if($Exists){
                $tpl->table_form_field_text($dom,"$SizeBytes",ico_weight);
            }
        }
    }
    if($TotalSizeBytes>0) {
        if (!$array["IsVitrified"]) {
            $tpl->table_form_field_js("Loadjs('$page?vitrification-on=$ID');");
            $tpl->table_form_field_bool("{isVitrified}", 0, "fas far fa-wine-glass");
        } else {
            $tpl->table_form_field_js("Loadjs('$page?vitrification-off=$ID');");
            $tpl->table_form_field_bool("{vitrified_mode}", 1, "fas far fa-wine-glass");
        }
    }

    echo $tpl->_ENGINE_parse_body($tpl->table_form_compile());

    return true;
}
function vitrification_on():bool{
    $ID=intval($_GET["vitrification-on"]);
    $sockngix                   = new socksngix($ID);
    $hostname                   = $sockngix->GetServiceName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/vitrification/switch/on/$ID");
    return admin_tracks("Activate $hostname to use vitrification mode");

}
function vitrification_off():bool{
    $ID=intval($_GET["vitrification-off"]);
    $sockngix                   = new socksngix($ID);
    $hostname                   = $sockngix->GetServiceName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/vitrification/switch/off/$ID");
    return admin_tracks("Back $hostname to use backend mode");
}
function vitrification_run():bool{
    $ID                         = intval($_GET["vitrification-run"]);
    $sockngix                   = new socksngix($ID);
    $hostname                   = $sockngix->GetServiceName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/vitrification/fetch/start/$ID");
    return admin_tracks("Run vitrification task for $hostname");
}


function vitrification_switch():bool{
    $ID                         = intval($_GET["vitrification-switch"]);
    $sockngix                   = new socksngix($ID);
    $page=CurrentPageName();
    $vitrification=intval($sockngix->GET_INFO("EnableVitrification"));
    VERBOSE("$ID: EnableVitrification=$vitrification",__LINE__);

    if($vitrification==0){
        $vitrification_text="On";
        $vitrification=1;
    }else{
        $vitrification_text="Off";
        $vitrification=0;
    }
    VERBOSE("$ID: EnableVitrification=$vitrification",__LINE__);
    $sname=get_servicename($ID);
    $sockngix->SET_INFO("EnableVitrification",$vitrification);
    header("content-type: application/x-javascript");
    echo "LoadAjax('vitrification-status-$ID','$page?vitrification-status=$ID');";
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$ID");
    return admin_tracks("Turn vitrification to $vitrification_text for service $ID $sname");

}
function vitrification_popup():bool{
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();$tpl->CLUSTER_CLI=true;
    $ID                         = intval($_GET["vitrification-popup"]);

    $sock=new socksngix($ID);
    $ligne=$sock->GetCache();



    $html[]="<div id='vitrification-status-$ID' style='margin-bottom:10px'></div>";
    $html[]="<table style='width:100%;'>";
    $html[]="<td style='width:100%;vertical-align: top'>";
    $html[]=$tpl->BigCircleCheckbox("ID:$ID|vitrification","{vitrification}",
        "{vitrification_explain}",$ligne["vitrification_enabled"],reload($ID),"AsSystemWebMaster");
    $html[]="<div id='vitrification-config-$ID'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $js=$tpl->RefreshInterval_js("vitrification-status-$ID",$page,"vitrification-status=$ID");
    $html[]=$js;
    $html[]="LoadAjax('vitrification-config-$ID','$page?vitrification-config=$ID');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function reload($ID):string{
    $page=CurrentPageName();
    $f[]="LoadAjax('www-parameters-$ID','fw.nginx.sites.php?www-parameters2=$ID');";
    $f[]="LoadAjax('vitrification-config-$ID','$page?vitrification-config=$ID');";
   //$f[]="LoadAjax('vitrification-status-$ID','$page?vitrification-status=$ID');";
    return @implode("",$f);
}
function vitrification_config(){
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["vitrification-config"]);
    $sock=new socksngix($ID);
    $ligne=$sock->GetCache();
    if($ligne["vitrification_enabled"]==0){
        return "";
    }

    $ligne=$ligne["vitrification_config"];

    $auto_switch=$ligne["auto_switch"];
    $max_latency_ms=intval($ligne["max_latency_ms"]);
    if($max_latency_ms==0){
        $max_latency_ms=200;
    }
    $html[]=$tpl->BigCircleCheckbox("ID:$ID|auto_switch","{auto_switch}",
        "{auto_switch_vitrification}",$auto_switch,reload($ID),"AsSystemWebMaster");

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/backends-scanner/average/$ID"),true);
    $latency="";

    if(isset($json["Data"]["average_ms"])){
        $latency="&nbsp;(".round($json["Data"]["average_ms"],2)."ms)";
    }
    $html[]=$tpl->BigIntegerField("ID:$ID|max_latency_ms","{latency}$latency",
        "{max_latency_ms_explain}",$max_latency_ms);




    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function vitrification_config_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ID=$_POST["ID"];
    $sock=new socksngix($ID);
    $Conf=json_decode($sock->GET_INFO("VitrificationConfig"),true);

    if(!isset($Conf["auto_switch"])){
        $Conf["auto_switch"]=0;
    }
    if(!isset($Conf["max_latency_ms"])){
        $Conf["max_latency_ms"]=200;
    }
    if(!isset($Conf["max_dirsize_mb"])){
        $Conf["max_dirsize_mb"]=20;
    }
    if(!isset($Conf["vitr_depth"])){
        $Conf["vitr_depth"]=3;
    }

    if(isset($_POST["max_dirsize_mb"])){
        $Conf["max_dirsize_mb"]=intval($_POST["max_dirsize_mb"]);
    }

    if(isset($_POST["auto_switch"])){
        $Conf["auto_switch"]=$_POST["auto_switch"];
    }
    if(isset($_POST["max_latency_ms"])){
        $Conf["max_latency_ms"]=intval($_POST["max_latency_ms"]);
    }
    if(isset($_POST["vitr_depth"])){
        $Conf["vitr_depth"]=intval($_POST["vitr_depth"]);
    }


    $sock->SET_INFO("VitrificationConfig",json_encode($Conf));
    $hostname                   = $sock->GetServiceName();
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/flush-cache");
    return admin_tracks_post("Save Vitrification configuration for site $hostname");
}
function vitrification_disk_popup():bool{
    $page                       = CurrentPageName();
    $tpl                        = new template_admin();$tpl->CLUSTER_CLI=true;
    $ID=intval($_GET["vitrification-disk-popup"]);
    $sock=new socksngix($ID);
    $ligne=json_decode($sock->GET_INFO("VitrificationConfig"),true);
    $max_dirsize_mb=$ligne["max_dirsize_mb"];
    $vitr_depth=$ligne["vitr_depth"];

    if($max_dirsize_mb==0){
        $max_dirsize_mb=20;
    }
    if($vitr_depth==0){
        $vitr_depth=3;
    }

    $html[]=$tpl->BigIntegerField("ID:$ID|max_dirsize_mb","{max_directory_size}",
        "{max_directory_size_mb_explain}",$max_dirsize_mb);

    $html[]=$tpl->BigIntegerField("ID:$ID|vitr_depth","{website_crawling_depth}",
        "{crawling_depth_explain}",$vitr_depth);

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}