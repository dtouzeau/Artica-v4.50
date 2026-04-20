<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["zram-js"])){zram_js();exit;}
if(isset($_GET["zram-popup"])){zram_popup();exit;}
if(isset($_POST["EnableZram"])){zram_save();exit;}
if(isset($_POST["overcommit_memory"])){table_save();exit;}
if(isset($_GET["form-popup"])){form_popup();exit;}
if(isset($_GET["form-js"])){form_js();exit;}
if(isset($_GET["flat"])){table_flat();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["memory-graph"])){memory_graph();exit;}
if(isset($_GET["memory-graph2"])){memory_graph2();exit;}
if(isset($_GET["zram-section"])){zram_section();exit;}
if(isset($_GET["zram-status-chart"])){zram_status_chart();exit;}
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
function zram_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return  $tpl->js_dialog1("{zram_compressed_swap}","$page?zram-popup=yes",650);
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
    $html[]="<div id='zram-section' style='margin-top:10px'></div>";

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
    $html[]="LoadAjax('zram-section','$page?zram-section=yes');";
    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function memory_graph(){
    header("content-type: application/x-javascript");
    $meminfo=explode("\n",@file_get_contents("/proc/meminfo"));
    $ratio=intval(@file_get_contents("/proc/sys/vm/overcommit_ratio"));
    $behavior=intval(@file_get_contents("/proc/sys/vm/overcommit_memory"));

    foreach ($meminfo as $line){
        if(preg_match("#^(.+?):\s+([0-9]+)#",$line,$re)){
            $key=strtolower($re[1]);
            $value=intval($re[2]);
            $MAIN[$key]=$value;
        }
    }

    $MemTotal=intval($MAIN["memtotal"]);
    $MemFree=intval($MAIN["memfree"]);
    $Buffers=intval($MAIN["buffers"]);
    $Cached=intval($MAIN["cached"]);
    $MemAvailable=intval($MAIN["memavailable"]);
    $tpl=new template_admin();
    $chart=new Chartjs();
    $chart->container="memory-graph";
    $chart->DataToSize=true;

    if($behavior==1){
        // overcommit=1: commit limit is meaningless, show actual RAM usage
        $MemUsed=$MemTotal-$MemAvailable;
        $MemTotal_text=FormatBytes($MemTotal);
        $MemUsed_text=FormatBytes($MemUsed);
        $pct=($MemTotal>0)?round(($MemUsed/$MemTotal)*100,1):0;
        $chart->Title=$tpl->_ENGINE_parse_body("{RAM} {$pct}% $MemUsed_text / $MemTotal_text");
        $chart->PieDatas=array(
            $tpl->_ENGINE_parse_body($tpl->javascript_parse_text("{available_commit}"))=>$MemAvailable,
            $tpl->_ENGINE_parse_body($tpl->javascript_parse_text("{used}"))=>$MemUsed,
        );
    }else{
        $CommitLimit=intval($MAIN["commitlimit"]);
        $Committed_AS=intval($MAIN["committed_as"]);
        $CommitRest=max(0,$CommitLimit-$Committed_AS);
        $CommitLimit_text=FormatBytes($CommitLimit);
        $Committed_AS_text=FormatBytes($Committed_AS);
        $chart->Title=$tpl->_ENGINE_parse_body("{committed_memory} ({$ratio}%) $Committed_AS_text / $CommitLimit_text");
        $chart->PieDatas=array(
            $tpl->javascript_parse_text("{available_commit}")=>$CommitRest,
            $tpl->javascript_parse_text("{committed_amount}")=>$Committed_AS,
        );
    }
    echo $chart->Doughnut2rows();
}

function memory_graph2(){
    header("content-type: application/x-javascript");
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
    $percentuse=($AllMemory>0)?round(($AllUsed/$AllMemory)*100,2):0;
    $AllMemory_text=FormatBytes($AllMemory);
    $AllUsed_text=FormatBytes($AllUsed);

    $tpl=new template_admin();
    $PieData=array();
    $PieData[$tpl->javascript_parse_text("{free}")]=$MemFree;
    $PieData[$tpl->javascript_parse_text("{used}")]=$MemUsed;
    if($SwapTotal>0){
        $PieData[$tpl->javascript_parse_text("SWAP {free}")]=$SwapFree;
        $PieData[$tpl->javascript_parse_text("SWAP {used}")]=$swapused;
    }

    $chart=new Chartjs();
    $chart->container="memory-graph2";
    $chart->Title=$tpl->_ENGINE_parse_body("{memory_usage} {$percentuse}% $AllUsed_text / $AllMemory_text");
    $chart->PieDatas=$PieData;
    $chart->DataToSize=true;
    echo $chart->Pie();
}

function zram_section(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableZram=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZram"));
    $ZramSizePercent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZramSizePercent"));
    if($ZramSizePercent==0){$ZramSizePercent=50;}



    $tpl->table_form_section("{zram_compressed_swap}","{zram_explain}");
    $tpl->table_form_field_js("Loadjs('$page?zram-js=yes')","AsSystemAdministrator");
    if($EnableZram==0) {
        $tpl->table_form_field_bool("zRam", $EnableZram, ico_mem);
    }else{
        $tpl->table_form_field_text("zRam","{active2} $ZramSizePercent%");
    }

    $html[]=$tpl->table_form_compile();


    if($EnableZram==1){
        $html[]="<div id='zram-status-chart' style='margin-top:10px'></div>";
        $html[]="<script>LoadAjax('zram-status-chart','$page?zram-status-chart=yes');</script>";
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function zram_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/zmem/reconfigure");
    admin_tracks_post("Saving zRam settings");
}
function zram_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $EnableZram=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableZram"));
    $ZramSizePercent=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZramSizePercent"));
    if($ZramSizePercent==0){$ZramSizePercent=50;}

    $percOptions=array();
    for($i=10;$i<=80;$i+=5){
        $percOptions[$i]="$i%";
    }

    $form[]=$tpl->field_checkbox("EnableZram","{enable_feature}",$EnableZram);
    $form[]=$tpl->field_array_hash($percOptions,"ZramSizePercent","{zram_size_percent}",$ZramSizePercent);

    $js_after[]="dialogInstance1.close();LoadAjax('zram-section','$page?zram-section=yes');";
    $html[]=$tpl->form_outside("",$form,null,"{apply}",implode(";",$js_after));

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function zram_status_chart(){
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/zmem/status"));

    if(!is_object($json) || !isset($json->success) || !$json->success){
        $err=is_object($json) && isset($json->error)?$json->error:"Cannot reach zMem API";
        echo $tpl->div_error($err);
        return;
    }
    $d=$json->data;
    if(!$d->enabled){
        echo "<div style='text-align:center;padding:20px;color:#999'><i class='fa fa-info-circle'></i>&nbsp;{inactive2}</div>";
        echo $tpl->_ENGINE_parse_body("");
        return;
    }

    $html[]="<table style='width:100%'><tr>";
    $html[]="<td style='width:350px;vertical-align:top'><div id='zram-doughnut' style='height:300px'></div></td>";
    $html[]="<td style='vertical-align:top;padding-left:15px'>";

    $comprRatio=round($d->compression_ratio,2);
    $spaceSaved=round($d->space_saving_pct,1);
    $memEff=round($d->mem_efficiency_pct,1);
    $origMB=round($d->orig_data_mb,1);
    $comprMB=round($d->compr_data_mb,1);
    $memUsedMB=round($d->mem_used_mb,1);
    $swapUsedMB=round($d->swap_used_mb,1);
    $swapTotalMB=round($d->swap_total_mb,1);
    $swapPct=round($d->swap_used_pct,1);
    $algo=strtoupper($d->comp_algorithm);
    $diskMB=$d->disksize_mb;

    $tpl->table_form_field_text("{zram_algorithm}",$algo,"fa-solid fa-compress");
    $tpl->table_form_field_text("{zram_compression_ratio}","{$comprRatio}:1","fa-solid fa-chart-line");
    $tpl->table_form_field_text("{zram_space_saved}","{$spaceSaved}%","fa-solid fa-arrow-down");
    $tpl->table_form_field_text("{zram_mem_efficiency}","{$memEff}%","fa-solid fa-gauge-high");
    $tpl->table_form_field_text("{zram_original_data}","{$origMB} MB","fa-solid fa-database");
    $tpl->table_form_field_text("{zram_compressed_data}","{$comprMB} MB","fa-solid fa-file-zipper");
    $tpl->table_form_field_text("{zram_swap_usage}","{$swapUsedMB} MB / {$swapTotalMB} MB ({$swapPct}%)","fa-solid fa-hard-drive");
    $html[]=$tpl->table_form_compile();

    $html[]="</td></tr></table>";

    // Doughnut chart: original vs compressed data
    if($origMB>0){
        $chart=new Chartjs();
        $chart->container="zram-doughnut";
        $chart->Title=$tpl->_ENGINE_parse_body("{zram_compressed_swap} - {$diskMB} MB");
        $chart->PieDatas=array(
            $tpl->_ENGINE_parse_body("{zram_compressed_data}")=>$comprMB,
            $tpl->_ENGINE_parse_body("{zram_space_saved}")=>round($origMB-$comprMB,1),
        );
        $chart->LegendSuffix="MB";
        $html[]="<script>".$chart->Doughnut2rows()."</script>";
    }

    echo $tpl->_ENGINE_parse_body($html);
}

function Save(){

    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}