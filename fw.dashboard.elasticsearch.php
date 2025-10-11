<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$tpl=new template_admin();
if(isset($_POST["none"])){exit;}
if(isset($_GET["page"])){page();exit;}
if(isset($_GET["purge"])){purge();exit;}
if(isset($_GET["purge-progress"])){purge_popup_js();exit;}
if(isset($_GET["purge-popup"])){purge_popup();exit;}

start();
function purge(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog_confirm_action("{squid_purge_dns_explain}","none","none","Loadjs('$page?purge-progress=yes')");
}
function purge_popup_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{empty_cache}","$page?purge-popup=yes");
}
function purge_popup(){
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress.log";
    $ARRAY["CMD"]="dnsfilterd.php?purge=yes";
    $ARRAY["TITLE"]="{empty_cache}";
    $ARRAY["AFTER"]="dialogInstance1s.close();LoadAjax('dashboard-dnsfilterd','$page?page=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=$t')";
    $html="<div id='$t'></div><script>$jsrestart</script>";
    echo $html;
}

function start(){
    $page=CurrentPageName();
    $html="<div id='dashboard-elasticsearch'></div>
<script>LoadAjax('dashboard-elasticsearch','$page?page=yes');</script>";

    echo $html;
}

function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $memcached=new lib_memcached();
    $version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_VERSION");


    $ELASTICSEARCH_NODESSTATS=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_NODESSTATS"));
    foreach ($ELASTICSEARCH_NODESSTATS->nodes as $uuid=>$znodesclass) {
        $hostname = $znodesclass->name;
        $array[$hostname]=$znodesclass;

    }


    $myhostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");
    $df=explode(".",$myhostname);
    $netbiosname=$df[0];

    $nodes_class=$array[$netbiosname];

    //print_r($nodes_class->os);
    $fs_total_in_bytes= $nodes_class->fs->total->total_in_bytes;
    $fs_free_in_bytes= $nodes_class->fs->total->free_in_bytes;
    $fs_used_in_bytes=$fs_total_in_bytes-$fs_free_in_bytes;
    $fs_used_percent=round(($fs_used_in_bytes/$fs_total_in_bytes)*100);



    $mem_color="navy-bg";
    $fs_used_color="navy-bg";
    $global_status_color="gray-bg";

    $ELASTICSEARCH_STATUS=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_STATUS"));
    $total_in_bytes = $nodes_class->os->mem->total_in_bytes;
    $used_in_bytes  = $nodes_class->os->mem->used_in_bytes;
    $used_percent   = intval($nodes_class->os->mem->used_percent);

    $max_uptime=$ELASTICSEARCH_STATUS->nodes->jvm->max_uptime;


    $global_status=$ELASTICSEARCH_STATUS->status;

    if($global_status=="green"){
        $global_status_color="navy-bg";
    }
    if($global_status=="yellow"){
        $global_status_color="yellow-bg";
    }
    if($global_status=="red"){
        $global_status_color="red-bg";
    }



    if($fs_used_percent>70){
        $fs_used_color="yellow-bg";
    }
    if($fs_used_percent>90){
        $fs_used_color="red-bg";
    }
    if($fs_used_percent==0){
        $fs_used_color="gray-bg";
    }




    if($used_percent>70){
        $mem_color="yellow-bg";
    }
    if($used_percent>90){
        $mem_color="red-bg";
    }
    if($used_percent==0){
        $mem_color="gray-bg";
    }

    $total=FormatBytes($total_in_bytes/1024);
    $used_t=FormatBytes($used_in_bytes/1024);

    $fs_total=FormatBytes($fs_total_in_bytes/1024);
    $fs_used=FormatBytes($fs_used_in_bytes/1024);



    $html[]="<H1>ElasticSearch v$version</H1>";
    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td style='padding:2px'>".$tpl->widget_style1($global_status_color,"fas fa-thermometer-three-quarters","{status}, {uptime}","{{$global_status}} $max_uptime")."</td>";
    $html[]="<td style='padding:2px'>".$tpl->widget_style1($mem_color,"fas fa-microchip","{memory}:$used_t/$total","{$used_percent}%")."</td>";
    $html[]="<td style='padding:2px'>".$tpl->widget_style1($fs_used_color,"fas fa-hdd","{disk_usage}:$fs_used/$fs_total","{$fs_used_percent}%")."</td>";

    $html[]="</tr>";
    $html[]="<tr>";

    $ELASTICSEARCH_STATUS=json_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ELASTICSEARCH_STATUS"));

    $nodes_total=intval($ELASTICSEARCH_STATUS->_nodes->total);
    $nodes_failed=intval($ELASTICSEARCH_STATUS->_nodes->failed);
    $cluster_name =$ELASTICSEARCH_STATUS->cluster_name;



    $mem_color="navy-bg";
    $available_processors=$ELASTICSEARCH_STATUS->nodes->os->available_processors;
    $mem_total=$ELASTICSEARCH_STATUS->nodes->os->mem->total;
    $mem_used_percent=$ELASTICSEARCH_STATUS->nodes->os->mem->used_percent;
    $mem_used=$ELASTICSEARCH_STATUS->nodes->os->mem->used;

    if($mem_used_percent>70){
        $mem_color="yellow-bg";
    }
    if($used_percent>90){
        $mem_color="red-bg";
    }
    if($used_percent==0){
        $mem_color="gray-bg";
    }

    $fs_total=$ELASTICSEARCH_STATUS->nodes->fs->total;
    $fs_free=$ELASTICSEARCH_STATUS->nodes->os->fs->free;
    $total_in_bytes=intval($ELASTICSEARCH_STATUS->nodes->fs->total_in_bytes);
    $free_in_bytes=intval($ELASTICSEARCH_STATUS->nodes->fs->free_in_bytes);
    $used_in_bytes=$free_in_bytes-$total_in_bytes;
    $used_in_text=FormatBytes($used_in_bytes/1024);
    $fs_percent=round( ($used_in_bytes/$total_in_bytes)*100,1);
    $fs_color="navy-bg";

    if($fs_percent>70){
        $fs_color="yellow-bg";
    }
    if($fs_percent>90){
        $fs_color="red-bg";
    }
    if($fs_percent==0){
        $fs_color="gray-bg";
    }


   if($nodes_total==0){
       $html[]="<td style='padding:2px'>".$tpl->widget_style1("red-bg","fas fa-server","$cluster_name/{nodes}","0")."</td>";

   }else{
       if($nodes_failed>0){
           $html[]="<td style='padding:2px'>".$tpl->widget_style1("red-bg","fas fa-server","$cluster_name/{nodes}","$nodes_failed {failed}/$nodes_total")."</td>";
       }else{
           $html[]="<td style='padding:2px'>".$tpl->widget_style1("navy-bg","fas fa-server","$cluster_name/{nodes}","$nodes_total - $available_processors CPUs")."</td>";
       }
     }

    $html[]="<td style='padding:2px'>".$tpl->widget_style1($mem_color,"fas fa-microchip","$cluster_name {memory}:$mem_used/$mem_total","{$mem_used_percent}%")."</td>";


    $html[]="<td style='padding:2px'>".$tpl->widget_style1($fs_color,"fas fa-hdd","$cluster_name {disk_usage}:$used_in_text/$fs_total","{$fs_used_percent}%")."</td>";


    $html[]="</tr>";

    $html[]="</table>";





    echo $tpl->_ENGINE_parse_body($html);

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}