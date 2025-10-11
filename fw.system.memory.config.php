<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_POST["overcommit_memory"])){Save();exit;}
if(isset($_GET["memory-graph"])){memory_graph();exit;}
if(isset($_GET["memory-graph2"])){memory_graph2();exit;}
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

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $Overcommiting_Memory[0]="{Overcommiting_Memory_0}";
    $Overcommiting_Memory[1]="{Overcommiting_Memory_1}";
    $Overcommiting_Memory[2]="{Overcommiting_Memory_2}";

for($i=50;$i<101;$i++){
    $overcommit_ratioH[$i]="{$i}%";
}


    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/system.memory.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/system.memory.progress.txt";
    $ARRAY["CMD"]="system.php?overcommit-mem=yes";
    $ARRAY["TITLE"]="{Overcommiting_Memory_behavior}";
    $ARRAY["AFTER"]="LoadAjax('table-system-memory','$page?table=yes');;";


    $tpl->field_read_array_hash_setinfo($Overcommiting_Memory,"overcommit_memory","nonull:{Overcommiting_Memory_behavior}","sysctl:vm.overcommit_memory",true,"{vm.overcommit_memory}","AsSystemAdministrator","exec.sysctl.php --build");
    $tpl->field_read_array_hash_setinfo($overcommit_ratioH,"overcommit_ratio","nonull:{ratio}","sysctl:vm.overcommit_ratio",false,"{vm.overcommit_ratio}","AsSystemAdministrator","exec.sysctl.php --build");
    $tpl->field_read_numeric_setinfo("kernel_shmmax","{kernel_shmmax}","sysctl:kernel.shmmax","{kernel_shmmax_explain}","AsSystemAdministrator","exec.sysctl.php --build");
    $tpl->field_read_numeric_setinfo("kernel_shmall","{kernel_shmall}","sysctl:kernel.shmall","{kernel_shmall_explain}","AsSystemAdministrator","exec.sysctl.php --build");




    $xform=$tpl->field_read_compile();

    //$form[]=$tpl->field_array_hash($Overcommiting_Memory,"overcommit_memory","nonull:{Overcommiting_Memory_behavior}",$overcommit_memory,false,"{vm.overcommit_memory}");

   // $form[]=$tpl->field_array_hash($overcommit_ratioH,"overcommit_ratio","nonull:{ratio}",$overcommit_ratio,false,"{vm.overcommit_ratio}");


    //Memory Allocation Limit = Swap Space + RAM * (Overcommit Ratio / 100)




    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td valign='top' style='width:450px' nowrap><div id='memory-graph'></div><div id='memory-graph2'></div></td>";
    $html[]="<td valign='top' style='width:100%'>";
    $html[]="<div id='overcommit-progress' style='margin-bottom:10px'></div>";
    $html[]=$xform;

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