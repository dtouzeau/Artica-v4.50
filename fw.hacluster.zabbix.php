<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.patch.tables.fw.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_POST["HaClusterTransParentMode"])){Save();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["template"])){download_template();exit;}
table();

function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

}

function table(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px' valign='top'>";
    $html[]="<div id='zabbix-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;padding-left: 20px' valign='top'>";
    $html[]="<p>{hacluster_zabbix_howto}</p>";
    $html[]="<div style='text-align:right'>";
    $html[]=$tpl->button_autnonome("template_app_hacluster.xml","s_PopUp('$page?template=yes&filename=template_app_hacluster.xml',0,0,'');","fas fa-file-certificate");
    $HaClusterTransParentMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterTransParentMode"));
    if($HaClusterTransParentMode==1){
        $html[]="&nbsp;&nbsp;";
        $html[]=$tpl->button_autnonome("template_app_hacluster_transparent.xml",
                "s_PopUp('$page?template=yes&filename=template_app_hacluster_transparent.xml',0,0,'');","fas fa-file-certificate");
    }
    $html[]="</div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>LoadAjax('zabbix-status','$page?status=yes')</script>";
    echo $tpl->_ENGINE_parse_body($html);

}

function status(){
    $tpl=new template_admin();
    $ZABBIX_IN_CONFIG=false;
    $f=explode("\n",@file_get_contents("/etc/hacluster/hacluster.cfg"));

    foreach ($f as $line){
        if(preg_match("#stats uri\s+\/stats#",$line)){
            $ZABBIX_IN_CONFIG=true;
            break;
        }
        if($GLOBALS["VERBOSE"]){echo "NOT: \"$line\"\n";}
    }

    if(!$ZABBIX_IN_CONFIG){
        echo $tpl->widget_jaune("{status}","{not_configured}");
        return;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:44787/stats;csv");
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data=curl_exec($ch);
    $CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    $curl_errno=curl_errno($ch);
    $curl_strerr=curl_strerror($curl_errno);

    if($curl_errno>0){
        echo $tpl->widget_jaune("$curl_strerr","{no_data}");
        return;

    }

    echo $tpl->widget_vert("{status}","OK");



}

function download_template(){
    $filename=$_GET["filename"];
    $file=dirname(__FILE__)."/bin/install/zabbix/$filename";
    $fsize=@file_get_contents($file);
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    readfile($file);
}