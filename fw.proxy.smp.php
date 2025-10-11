<?php
if(isset($_POST["NONE"])){die();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
include_once(dirname(__FILE__)."/ressources/class.icon.top.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["cpus-config"])){cpus_config();exit;}
if(isset($_GET["cpus-sum"])){cpu_sum();exit;}
if(isset($_GET["cpus-status"])){cpus_status();exit;}
if(isset($_GET["performance-after"])){performance_save_after();exit;}
if(isset($_POST["PerformanceSave"])){PerformanceSave();exit;}
if(isset($_GET["switch-cpu-number"])){switch_cpu_number();exit;}

page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{your_proxy} - {multiple_instances}",
        ico_cpu,"{SQUID_SMP_EXPLAIN}<br>{squid_worker_explain}","$page?tabs=yes","proxy-smp","progress-smp-restart");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return;
    }
    echo $tpl->_ENGINE_parse_body($html);



}
function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{multiple_cpus}"]="$page?table=yes";
    echo $tpl->tabs_default($array);
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td style='width:340px;vertical-align: top'>";
    $html[]="<div id='cpus-status' style='width: 340px'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;padding-left:15px;vertical-align: top'>";
    $html[]="<div id='cpus-sum'></div>";
    $html[]="<div id='cpus-config'></div></td>";
    $html[]="<tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('cpus-config','$page?cpus-config=yes');";
    $html[]=$tpl->RefreshInterval_js("cpus-sum",$page,"?cpus-sum=yes");
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function widget_cpu_usage():string{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/monitor/filedesc"));


    if(!$json->Status){
        return $tpl->widget_style1("red-bg","fas fa-microchip","{CpuUsage}",$json->Error);
    }
    $FULL_USAGE=0;
    if(property_exists($json->Info,"full_usage")) {
        $FULL_USAGE = floatval($json->Info->full_usage);
    }
    return $tpl->widget_style1("navy-bg","fas fa-microchip","{CpuUsage}",round($FULL_USAGE,2)."%");
}

function cpu_sum(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/monitor/filedesc"));


    if(!$json->Status){
        VERBOSE("Cache Manager return False!",__LINE__);
        $js_restart=$tpl->framework_buildjs("/proxy/restart/single","squid.quick.rprogress",
            "squid.quick.rprogress.log","cpus-sum");
        $button_restart=$tpl->button_autnonome("{restart}", $js_restart, "fas fa-sync-alt","AsProxyMonitor","99%","btn-danger");
        $filedesc_usage=$tpl->widget_style1("red-bg","fas fa-tachometer-alt","{file_descriptors}",$button_restart);
        $requests= $tpl->widget_style1("red-bg","fas fa-cloud-showers","{requests}/{minute}","{error}");
        $html[]="<table style='width:100%'>";
        $html[]="<tr>";
        $html[]="<td style='width:33%' style='padding-left: 10px'>$filedesc_usage</td>";
        $html[]="<td style='width:33%' style='padding-left: 10px'>$requests</td>";
        $html[]="<script>";
        $html[]="LoadAjaxSilent('cpus-status','$page?cpus-status=yes&fullusage=0');";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }
    $HTTP_RQS=0;
    $FULL_USAGE=0;
    $percent_feldesc=0;



    $MAX_FILE_DESC=$json->Info->current_file_descriptors;
    if(property_exists($json,"average_http_requests_per_minute")) {
        $HTTP_RQS = $json->Info->average_http_requests_per_minute;
    }

    $color="navy-bg";
    if(property_exists($json->Info,"percentage")) {
        $percent_feldesc = $json->Info->percentage;
    }

        if ($percent_feldesc > 70) {
            $color = "yellow-bg";
        }
        if ($percent_feldesc > 95) {
            $color = "red-bg";

        }

    if($percent_feldesc==0){
        $filedesc_usage=$tpl->widget_style1("gray-bg","fas fa-tachometer-alt","{file_descriptors}",$tpl->FormatNumber($json->Info->current_file_descriptors_in_use));
    }else{
        $filedesc_usage=$tpl->widget_style1($color,"fas fa-tachometer-alt","{file_descriptors} max:&nbsp;<strong>$MAX_FILE_DESC</strong>","$percent_feldesc%");
    }

$requests= $tpl->widget_style1("navy-bg","fas fa-cloud-showers","{requests}/{minute}",$HTTP_RQS);


    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%;padding-left:5px' style='padding-left: 10px'>$filedesc_usage</td>";
    $html[]="<td style='width:33%;padding-left:5px' style='padding-left: 10px'>$requests</td>";
    $html[]="<script>";
    $html[]="LoadAjaxSilent('cpus-status','$page?cpus-status=yes&fullusage=$FULL_USAGE');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function cpus_status(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $GLOBALS["makeQueryForce"]=true;
    $cache_manager=new cache_manager();
    $data=$cache_manager->makeQuery("utilization",true);
    $textes=array();

    $html[]=widget_cpu_usage();

    if(!$cache_manager->ok){
        $widget_style2=array();
        $widget_style2[]="<table style='width:100%'>";
        $widget_style2[]="<tr>";
        $widget_style2[]="<td></td>";
        $widget_style2[]="<td style='padding-left: 15px;vertical-align: top;padding-bottom:15px'><H2>{instance} ???</H2>";
        $widget_style2[]="</tr>";
        $widget_style2[]="<tr>";
        $widget_style2[]="<td>&nbsp;</td>";
        $widget_style2[]="<td style='padding-left: 15px'>";
        $widget_style2[]=@implode("\n",$textes);
        $widget_style2[]="</td>";
        $widget_style2[]="</tr>";
        $widget_style2[]="</table>";
        $html[] = $tpl->widget_style2("red-bg", "fas fa-microchip", @implode("\n", $widget_style2));
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $CPU=0;
    foreach ($data as $line){
        $line=trim($line);
        if(preg_match("#by kid([0-9]+)\s+\{#i",$line,$re)){$CPU=intval($re[1])+1;}
        if(preg_match("#Last\s+([0-9]+)\s+minutes#i",$line,$re)){$mins=intval($re[1]);}
        if(preg_match("#Last\s+([0-9]+)\s+hour#i",$line,$re)){$mins=intval($re[1])*60;}
        if(preg_match("#Last\s+([0-9]+)\s+day#i",$line,$re)){
            $days=intval($re[1]);
            $hours=$days*24;
            $mins=$hours*60;
        }
        if(preg_match("#Totals since cache startup#",$line,$re)){$mins=0;}


        if(preg_match("#Last\s+day#",$line,$re)){$mins=1440;}
        if(preg_match("#Last\s+hour#",$line,$re)){$mins=60;}
        if(strpos($line,"=")==0){continue;}
        $tb=explode("=",$line);
        $MAIN[$CPU][$mins][trim($tb[0])]=trim($tb[1]);


    }

    $FULL_USAGE=floatval($_GET["fullusage"]);




    $http_request_tot=0;
    foreach ($MAIN as $cpunum=>$array){
        if(!isset($array[5]["server.http.requests"])){
            $MAIN[$cpunum][5]["server.http.requests"]=$array[0]["server.http.requests"];
            $array[5]["server.http.requests"]=$array[0]["server.http.requests"];
        }
        if(!isset($array[5]["cpu_usage"])){
            $MAIN[$cpunum][5]["cpu_usage"]=$array[0]["cpu_usage"];
        }


        $server_http_requests=$array[5]["server.http.requests"];
        if(preg_match("#([0-9\.]+)\/sec#",$server_http_requests,$re)){
            $http_request=floatval($re[1]);
            $http_request_tot=$http_request_tot+$http_request;
        }
    }
    $srcjs=array();
    foreach ($MAIN as $cpunum=>$array){
        $cpu_usage=0;
        if(!isset($array[5]["cpu_usage"])){$array[5]["cpu_usage"]=0;}
        if(preg_match("#([0-9\.]+)#",$array[5]["cpu_usage"],$re)) {
            $cpu_usage = $re[1];
        }

        $cpu_usage_text=round($cpu_usage,2);
        $server_http_requests=$array[5]["server.http.requests"];
        $srcjs[]="$(\"#cpu-usage-$cpunum\").peity(\"pie\",{ fill: [\"#18a689\", \"#eeeeee\"], height:38,width:38 });";

        $textes=array();
        $textes[]="<table>";
        $textes[]="<tr><td nowrap>{CpuUsage}: {$cpu_usage_text}%</td></tr>";
        $textes[]="<tr><td nowrap>{requests}: $server_http_requests</td></tr>";
        $textes[]="</table>";

        $widget_style2=array();
        $widget_style2[]="<table style='width:100%'>";
        $widget_style2[]="<tr>";
        $widget_style2[]="<td><span id='cpu-usage-$cpunum'>$cpu_usage,$FULL_USAGE</span></td>";
        $widget_style2[]="<td style='padding-left: 15px;vertical-align: top;padding-bottom:15px'><H2>{instance}&nbsp;$cpunum</H2>";
        $widget_style2[]="</tr>";
        $widget_style2[]="<tr>";
        $widget_style2[]="<td>&nbsp;</td>";
        $widget_style2[]="<td style='padding-left: 15px'>";
        $widget_style2[]=@implode("\n",$textes);
        $widget_style2[]="</td>";
        $widget_style2[]="</tr>";
        $widget_style2[]="</table>";
        $html[] = $tpl->widget_style2("navy-bg", "fas fa-microchip", @implode("\n", $widget_style2));

    }


    $html[]="<script>";
    $html[]=@implode("\n",$srcjs);
    $html[]="</script>";


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function performance_save_after(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $RESTART=false;
    $smp=$_GET["smp"];
    $SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    $SquidSMPConfig_md5=md5(serialize($SquidSMPConfig));


    if($SquidSMPConfig_md5<>$smp){

        $RESTART=true;
    }

   
    if($RESTART){

        $jsrestart=$tpl->framework_buildjs(
            "/proxy/general/nohup/restart",
            "squid.articarest.nohup","squid.articarest.nohup.log",
            "progress-smp-restart" );

        echo $tpl->js_confirm_execute("{need_restart_reconfigure_proxy}",
            "NONE","Restart Proxy service",$jsrestart);
        die();

    }

    $jsrestart=$tpl->framework_buildjs(
        "/proxy/general/nohup/restart",
        "squid.articarest.nohup","squid.articarest.nohup.log",
        "progress-smp-restart");

    header("content-type: application/x-javascript");
    echo $jsrestart;
}

function switch_cpu_number():bool{
    $page=CurrentPageName();
    header("content-type: application/x-javascript");
    $CpuToUse=intval($_GET["switch-cpu-number"]);
    $SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    $SquidSMPConfig_md5=md5(serialize($SquidSMPConfig));
    if(isset($SquidSMPConfig[$CpuToUse])){
        unset($SquidSMPConfig[$CpuToUse]);
    }else{
        $SquidSMPConfig[$CpuToUse]=$CpuToUse;
    }
    $GLOBALS["CLASS_SOCKETS"]->SaveConfigFile(serialize($SquidSMPConfig), "SquidSMPConfig");
    echo "LoadAjax('cpus-config','$page?cpus-config=yes');\n";
    echo "Loadjs('$page?performance-after=yes&smp=$SquidSMPConfig_md5');";
    return admin_tracks("Save the use of proxy CPU Number $CpuToUse");
}

function PerformanceSave(){
    $sock=new sockets();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $SquidSMPConfig=array();
    foreach ($_POST as $key=>$val){
        if(preg_match("#SMP([0-9]+)#", $key,$re)){
            if(intval($val)>0){$SquidSMPConfig[$re[1]]=$val;}
            unset($_POST[$key]);
        }
    }

    $sock->SaveConfigFile(serialize($SquidSMPConfig), "SquidSMPConfig");
}

function cpus_config() :bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $security="AsSquidAdministrator";

    $SquidSMPConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSMPConfig"));
    $CPU_NUMBER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CPU_NUMBER"));
    $SquidCpuNumberHASH[0]="{disabled} - {not_used}";
    for($i=1;$i<$CPU_NUMBER+1;$i++){
        $SquidCpuNumberHASH[$i]="{use} CPU N.$i";
    }
    for($i=1;$i<$CPU_NUMBER+1;$i++) {
        $tpl->table_form_field_js("Loadjs('$page?switch-cpu-number=$i');", $security);
        if (!isset($SquidSMPConfig[$i])) {
            $tpl->table_form_field_bool("{use} CPU N.$i", false, ico_cpu);
        } else {
            $tpl->table_form_field_bool("{use} CPU N.$i", true, ico_cpu);
        }
    }
    $html[]=$tpl->table_form_compile();
    echo $tpl->_ENGINE_parse_body($html);

    $jsrestart=$tpl->framework_buildjs(
        "/proxy/general/nohup/restart",
        "squid.articarest.nohup","squid.articarest.nohup.log",
        "progress-smp-restart");


    $topbuttons[] = array($jsrestart,ico_refresh,"{restart_proxy_service}");
    $TINY_ARRAY["TITLE"]="{your_proxy} - {multiple_instances}";
    $TINY_ARRAY["ICO"]=ico_cpu;
    $TINY_ARRAY["EXPL"]="{SQUID_SMP_EXPLAIN}<br>{squid_worker_explain}";
    $TINY_ARRAY["URL"]="proxy-smp";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    echo "<script type=\"text/javascript\">$jstiny</script>";
    return true;
}