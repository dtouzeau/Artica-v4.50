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
    $html="<div id='dashboard-dnsfilterd'></div>
<script>LoadAjax('dashboard-dnsfilterd','$page?page=yes');</script>";

    echo $html;
}

function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $memcached=new lib_memcached();
    $value=$memcached->getKey("DNSFILTERD_HITS");
    $NumBerOfRequests=FormatNumber(intval($value));

    $value=$memcached->getKey("DNSFILTERD_BLOCKS");
    $NumBerOfBlocked=FormatNumber(intval($value));

    $sock=new sockets();
    $sock->REST_API("/unbound/control/stats");
    $f=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/unbound.control.stats"));
    $UNBOUND_CONTROL=array();
    foreach ($f as $line){
        if(preg_match("#total.num.queries=([0-9]+)#", $line,$re)){$UNBOUND_CONTROL["QUERIES"]=$re[1];}
        if(preg_match("#total.num.cachehits=([0-9]+)#", $line,$re)){$UNBOUND_CONTROL["CACHES"]=$re[1];}
        if(preg_match("#total.num.cachemiss=([0-9]+)#", $line,$re)){$UNBOUND_CONTROL["MISS"]=$re[1];}
        if(preg_match("#total.num.prefetch=([0-9]+)#", $line,$re)){$UNBOUND_CONTROL["PREFETCH"]=$re[1];}
    }

    $Cached_requests=FormatNumber($UNBOUND_CONTROL["CACHES"]);

    $html[]="<div class=\"btn-group\" data-toggle=\"buttons\" style='margin-top:10px'>";
    $html[]="<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('$page?purge=yes');\"><i class='fas fa-trash-alt'></i> {empty_cache} </label>";
    $html[]="</div>";

    $html[]="<table style='width:100%;margin-top:15px'>";
    $html[]="<tr>";
    $html[]="<td style='padding:2px'>".$tpl->widget_style1("lazur-bg","fas fa-cloud-showers","{requests}:{APP_DNSFILTERD}",$NumBerOfRequests)."</td>";
    $html[]="<td style='padding:2px'>".$tpl->widget_style1("navy-bg","fas fa-eye-slash","{blocked}:{APP_DNSFILTERD}",$NumBerOfBlocked)."</td>";
    $html[]="<td style='padding:2px'>".$tpl->widget_style1("lazur-bg",ico_download,"{cached_requests}",$Cached_requests)."</td>";
    $html[]="</tr>";
    $html[]="</table>";





    echo $tpl->_ENGINE_parse_body($html);

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}