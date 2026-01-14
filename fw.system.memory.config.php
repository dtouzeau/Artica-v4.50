<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_POST["overcommit_memory"])){table_save();exit;}
if(isset($_GET["form-popup"])){form_popup();exit;}
if(isset($_GET["form-js"])){form_js();exit;}
if(isset($_GET["flat"])){table_flat();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["memory-graph"])){memory_graph();exit;}
if(isset($_GET["memory-graph2"])){memory_graph2();exit;}
if(isset($_GET["MemoryCacheCleaning-js"])){MemoryCacheCleaning_js();exit;}
if(isset($_GET["MemoryCacheCleaning-popup"])){MemoryCacheCleaning_popup();exit;}
if(isset($_POST["MemoryCacheCleaning"])){MemoryCacheCleaning_save();exit;}
page();
function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header(
        "{memory_info}","fad fa-memory",
        "{memory_info_text}","$page?tabs=yes","system-memory","progress-system-memory",false,"table-system-memory");



    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return;}
    echo $tpl->_ENGINE_parse_body($html);

}
function tabs():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table=yes";
    $array["{processes}"]="fw.system.memory.processes.php";
    echo $tpl->tabs_default($array);
    return true;
}
function form_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return  $tpl->js_dialog1("{parameters}","$page?form-popup=yes",650);
}
function MemoryCacheCleaning_js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return  $tpl->js_dialog1("{clean_cache}","$page?MemoryCacheCleaning-popup=yes",650);
}
function table_flat():bool{

    $page=CurrentPageName();
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/sysctl/json"));

    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

    $Overcommiting_Memory[0]="{Overcommiting_Memory_0}";
    $Overcommiting_Memory[1]="{Overcommiting_Memory_1}";
    $Overcommiting_Memory[2]="{Overcommiting_Memory_2}";
    for($i=50;$i<101;$i++){
        $overcommit_ratioH[$i]="$i%";
    }
    $MemoryCacheCleaning=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MemoryCacheCleaning"));
    $tpl->table_form_field_js("Loadjs('$page?MemoryCacheCleaning-js=yes');","AsSystemAdministrator");
    if($MemoryCacheCleaning==0 OR $MemoryCacheCleaning>95){
        $tpl->table_form_field_bool("{clean_cache}",0,"fa-solid fa-broom");
    }else{
        $tpl->table_form_field_text("{clean_cache}","> $MemoryCacheCleaning%","fa-solid fa-broom");
    }


    $tpl->table_form_field_js("Loadjs('$page?form-js=yes');","AsSystemAdministrator");
    $tpl->table_form_field_text("{Overcommiting_Memory_behavior}",$Overcommiting_Memory[$json->kernel->overcommit_memory],ico_mem);
    if($json->kernel->overcommit_memory==2) {
        $tpl->table_form_field_text("{ratio}", $overcommit_ratioH[$json->kernel->overcommit_ratio], ico_mem);
    }
    $tpl->table_form_field_text("{kernel_shmmax}",FormatBytes($json->kernel->shmmax/1024),ico_mem);
    $tpl->table_form_field_text("{kernel_shmall}",FormatBytes($json->kernel->shmall/1024),ico_mem);
    echo $tpl->table_form_compile();
    return true;
}
function MemoryCacheCleaning_popup():bool{

    $tpl=new template_admin();
    $page=CurrentPageName();
    $MemoryCacheCleaning=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MemoryCacheCleaning"));
    if($MemoryCacheCleaning<5 OR $MemoryCacheCleaning>95){
        $MemoryCacheCleaning=0;
    }
    $f[0]="{disabled}";
    for ($i=5;$i<95;$i++){
        $f[$i]="$i%";
    }

    $form[]=$tpl->field_array_hash($f,"MemoryCacheCleaning","{max_value}",$MemoryCacheCleaning);
    $js[]="dialogInstance1.close();";
    $js[]="LoadAjax('overcommit-progress','$page?flat=yes');";
    echo $tpl->form_outside("",$form,"{sys_drop_caches}","{apply}",implode(";",$js));
    return true;
}
function MemoryCacheCleaning_save():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MemoryCacheCleaning",$_POST["MemoryCacheCleaning"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/memory/dirtyratio");
    return admin_tracks("Save system memory cache cleaning after {$_POST["MemoryCacheCleaning"]}%");

}

function form_popup():bool{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/sysctl/json"));
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }

    $Overcommiting_Memory[0]="{Overcommiting_Memory_0}";
    $Overcommiting_Memory[1]="{Overcommiting_Memory_1}";
    if($json->kernel->isSwapByTable) {
        $Overcommiting_Memory[2] = "{Overcommiting_Memory_2}";
    }
    for($i=50;$i<101;$i++){
        $overcommit_ratioH[$i]="$i%";
    }
    $form[]=$tpl->field_section("{Overcommiting_Memory_behavior}","{vm.overcommit_memory}");
    $form[]=$tpl->field_array_hash($Overcommiting_Memory,"overcommit_memory","{Overcommiting_Memory_behavior}",$json->kernel->overcommit_memory);

    $form[]=$tpl->field_section("{ratio}","{vm.overcommit_ratio}");
    $form[]=$tpl->field_array_hash($overcommit_ratioH,"overcommit_ratio","{ratio}",$json->kernel->overcommit_ratio);

    $form[]=$tpl->field_section("{kernel_shmmax}","{kernel_shmmax_explain}");
    $form[]=$tpl->field_numeric("kernel_shmmax","{kernel_shmmax} (bytes)",$json->kernel->shmmax);
    $form[]=$tpl->field_section("{kernel_shmall}","{kernel_shmall_explain}");
    $form[]=$tpl->field_numeric("kernel_shmall","{kernel_shmall} (bytes)",$json->kernel->shmall);

    $js[]="LoadAjax('overcommit-progress','$page?flat=yes');";
    $js[]="dialogInstance1.close();";
    $js[]="LoadAjax('overcommit-progress','$page?flat=yes');";

    echo $tpl->form_outside("",$form,"","{apply}",implode(";",$js));
    return true;

}
function table_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/sysctl");
    return admin_tracks_post("Saving system parameters");

}
function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();




    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:450px;vertical-align:top' nowrap><div id='memory-graph'></div><div id='memory-graph2'></div></td>";
    $html[]="<td style='width:100%;;vertical-align:top'>";
    $html[]="<div id='overcommit-progress' style='margin-bottom:10px'></div>";


    if(is_file("img/squid/system_memory-day.flat.png")){
        $t=time();
        $html[]="<div style='margin-top:10px;padding:5px'><img src='img/squid/system_memory-day.flat.png?t=$t'></div>";
        $html[]="<div style='margin-top:10px;padding:5px'><img src='img/squid/system_memory-month.flat.png?t=$t'></div>";
    }

    $TINY_ARRAY["TITLE"]="{memory_info}";
    $TINY_ARRAY["ICO"]="fad fa-memory";
    $TINY_ARRAY["EXPL"]="{memory_info_text}";
    $TINY_ARRAY["URL"]="system-memory";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";


    $html[]=$tpl->RefreshInterval_js("overcommit-progress",$page,"flat=yes");
    $html[]="Loadjs('$page?memory-graph=yes');";
    $html[]="Loadjs('$page?memory-graph2=yes');";
    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function memory_graph(){



    $meminfo=explode("\n",@file_get_contents("/proc/meminfo"));
    $ratio=intval(@file_get_contents("/proc/sys/vm/overcommit_ratio"));

    foreach ($meminfo as $line){
        if(preg_match("#^(.+?):\s+([0-9]+)#",$line,$re)){
            $key=strtolower($re[1]);
            $value=intval($re[2]);
            $MAIN[$key]=$value;
        }
    }

    $MemTotal=intval($MAIN["memtotal"]);
    $MemFree=intval($MAIN["memfree"]);
    $MemUsed=$MemTotal-$MemFree;
    $SwapTotal=intval($MAIN["swaptotal"]);
    $CommitLimit=intval($MAIN["commitlimit"]);
    $Committed_AS=intval($MAIN["committed_as"]);
    $CommitRest=$CommitLimit-$Committed_AS;
    $calculated=($SwapTotal+$MemTotal)*($ratio/100);



    $CommitLimit_text=FormatBytes($CommitLimit);
    $Committed_AS_text=FormatBytes($Committed_AS);

    $PieData["{amount}"]=$CommitRest;
    $PieData["{amountr}"]=$Committed_AS;

    $tpl=new templates();
    $highcharts=new highcharts();
    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->container="memory-graph";
    $highcharts->PieDatas=$PieData;
    $highcharts->LegendSuffix="Kb";
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="$Committed_AS_text / $CommitLimit_text";
    $highcharts->PieRedGreen=true;
    $highcharts->Title=$tpl->_ENGINE_parse_body("{Overcommiting_Memory_behavior} {$ratio}% $Committed_AS_text / $CommitLimit_text");
    $highcharts->RemoveLock=true;
    echo $highcharts->BuildChart();



}

function memory_graph2(){

    $ratio=intval(@file_get_contents("/proc/sys/vm/overcommit_ratio"));
    $behavior=intval(@file_get_contents("/proc/sys/vm/overcommit_memory"));
    $meminfo=explode("\n",@file_get_contents("/proc/meminfo"));

    foreach ($meminfo as $line){
        if(preg_match("#^(.+?):\s+([0-9]+)#",$line,$re)){
            $key=strtolower($re[1]);
            $value=intval($re[2]);
            $MAIN[$key]=$value;
        }
    }

    $MemTotal=intval($MAIN["memtotal"]);
    $MemFree=intval($MAIN["memfree"]);
    $MemUsed=$MemTotal-$MemFree;
    $SwapTotal=intval($MAIN["swaptotal"]);
    $SwapFree=intval($MAIN["swapfree"]);
    $swapused=$SwapTotal-$SwapFree;

    $AllMemory=$MemTotal+$SwapTotal;
    $AllUsed=$MemUsed+$swapused;
    $percentuse=round(($AllUsed/$AllMemory)*100,2);
    $AllMemory_text=FormatBytes($AllMemory);
    $AllUsed_text=FormatBytes($AllUsed);
    $highcharts=new highcharts();
    $highcharts->PieRedGreen=true;
    $PieData["{free}"]=$MemFree;
    $PieData["{used}"]=$MemUsed;

    if($SwapTotal>0) {
        $highcharts->PieRedGreen=false;
        $PieData["{swap} {free}"] = $SwapFree;
        $PieData["{swap} {used}"] = $swapused;

    }
    $tpl=new templates();

    $highcharts->TitleFontSize="14px";
    $highcharts->AxisFontsize="12px";
    $highcharts->container="memory-graph2";
    $highcharts->PieDatas=$PieData;
    $highcharts->LegendSuffix="Kb";
    $highcharts->ChartType="pie";
    $highcharts->PiePlotTitle="{memory} {$percentuse}% $AllUsed_text/$AllMemory_text";
    $highcharts->Title=$tpl->_ENGINE_parse_body("{memory} {$percentuse}% $AllUsed_text/$AllMemory_text");
    $highcharts->RemoveLock=true;
    echo $highcharts->BuildChart();


}

function Save(){

    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}