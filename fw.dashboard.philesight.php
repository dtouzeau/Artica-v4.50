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
    $html="<div id='dashboard-disk-usage'></div>
<script>LoadAjax('dashboard-disk-usage','$page?page=yes');</script>";

    echo $html;
}

function page(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    if(!isset($_GET["directory"])){$_GET["directory"]=base64_encode("/");}
    $sock=new sockets();
    $t=time();
    $image="system.png";
    $primary_dir=base64_decode($_GET["directory"]);
    $title_dir=$tpl->td_href("/","","LoadAjax('dashboard-disk-usage','$page?page=yes&directory={$_GET["directory"]}')");

    if($primary_dir<>"/"){
        $FINAL=array();
        $sock->getFrameWork("philesight.php?img=".$_GET["directory"]);
        $image=md5($_GET["directory"]).".png";
        $dirf=null;
        $f=explode("/",$primary_dir);
        foreach ($f as $subdir){
            $dirf=$dirf."/$subdir";
            $dirf=str_replace("//","/",$dirf);
            $dirfenc=base64_encode($dirf);
            $FINAL[]=$tpl->td_href($subdir,"","LoadAjax('dashboard-disk-usage','$page?page=yes&directory=$dirfenc')");
        }

        $title_dir=@implode("/",$FINAL);


    }

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/system.dirmon.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/system.dirmon.log";
    $ARRAY["CMD"]="philesight.php?run=yes";
    $ARRAY["TITLE"]="{scan_filesystem_size}";
    $ARRAY["AFTER"]="LoadAjax('dashboard-disk-usage','$page?page=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=philesight-progress')";

    $sock->getFrameWork("philesight.php?listdirs=".$_GET["directory"]);
    $tr=explode("\n",@file_get_contents(PROGRESS_DIR."/philesight.dirs"));

    $html2[]="<table>";
    $html2[]="<tr>";
    $html2[]="<td width=1%><i class=\"fas fa-folder\"></i></td>";
    $direnc=base64_encode("/");
    $html2[]="<td>&nbsp;&nbsp;<strong>".$tpl->td_href("/","","LoadAjax('dashboard-disk-usage','$page?page=yes&directory=$direnc')")."</strong></td>";
    $html2[]="<td width=1%>&nbsp;</td>";
    $html2[]="</tr>";
    foreach ($tr  as $line) {
        $line=trim($line);
        if($line==null){continue;}

        if(!preg_match("#([0-9A-Z\.]+)\s+(.+?)$#",$line,$re)){continue;}
        $size=$re[1];
        $dir=$primary_dir."/".trim($re[2]);
        $dir=str_replace("//","/",$dir);
        $html2[]="<tr>";
        $html2[]="<td width=1%><i class=\"fas fa-folder\"></i></td>";
        $direnc=base64_encode($dir);
        $html2[]="<td>&nbsp;&nbsp;<strong>".$tpl->td_href($dir,"","LoadAjax('dashboard-disk-usage','$page?page=yes&directory=$direnc')")."</strong></td>";
        $html2[]="<td width=1%>$size</td>";
        $html2[]="</tr>";
    }
    $html2[]="</table>";

    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td width=99% valign='top' nowrap>";
    $html[]="<H1>{directory} &laquo;$title_dir&raquo;</H1>";
    $html[]="<div id='philesight-progress'></div>";
    $html[]="</td>";
    $html[]="<td width=1% valign='top' nowrap>";
    $html[]=$tpl->button_autnonome("{scan_filesystem_size}",$jsrestart,"fas fa-sync-alt");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td width=250px valign='top'>".@implode("\n",$html2)."</td>";
    $html[]="<td width=800px valign='top' style='padding-left:10px'><img src='img/philesight/$image?time=$t' style='border: 1px solid #cccccc;-webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075); -moz-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075); box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075); -webkit-transition: border linear 0.2s, box-shadow linear 0.2s;-moz-transition: border linear 0.2s, box-shadow linear 0.2s; -o-transition: border linear 0.2s, box-shadow linear 0.2s; transition: border linear 0.2s, box-shadow linear 0.2s; -webkit-border-radius: 5px 5px 5px 5px; -moz-border-radius: 5px 5px 5px 5px; border-radius: 5px 5px 5px 5px;'></td>";
    $html[]="</tr>";
    $html[]="</table>";





    echo $tpl->_ENGINE_parse_body($html);

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}