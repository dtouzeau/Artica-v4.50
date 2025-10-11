<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["after-js"])){after_js();exit;}
if(isset($_GET["after-download"])){after_download();exit;}
if(isset($_GET["after-download-popup"])){after_download_popup();exit;}
if(isset($_POST["SquidInDebugModeHTTPPort"])){Save();exit;}
js();

function js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return  $tpl->js_dialog12("{debug}","$page?popup=yes");
}

function after_download():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return  $tpl->js_dialog12("{debug}","$page?after-download-popup=yes");
}
function after_download_popup():bool{
    $tpl=new template_admin();
    $Package=PROGRESS_DIR."/proxy-debug.tar.gz";
    if(!is_file($Package)){

        echo $tpl->div_error("proxy-debug.tar.gz {no_such_file}");
        return true;
    }

    $size=filesize($Package);
    $size=FormatBytes($size/1024);
    $date=date("Y-m-d H:i:s",filemtime($Package));
    $url="ressources/logs/web/proxy-debug.tar.gz";
    $filedown="
		<center style='margin:15px'>
		<a href='$url'>
		<img src='img/file-compressed-128.png' class='img-rounded'>
		</a><br>
		<a href='$url'><small>proxy-debug.tar.gz ($size)</small></a><br>
		<a href='$url'><small>$date</small></a>
		</center>
			
		";
    echo $tpl->_ENGINE_parse_body($tpl->div_explain("{download}||$filedown"));
    return true;
}

function popup():bool{
    $EnableSquidInDebugMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidInDebugMode"));
    if($EnableSquidInDebugMode==0){
        return popup_wizard();
    }else{
        return popup_installed();
    }

    return true;
}
function after_js():bool{
    $page=CurrentPageName();
    header("Content-type: application/x-javascript");
    echo "dialogInstance12.close();\n";
    echo "Loadjs('$page');\n";
    echo "if(document.getElementById('applications-squid-status') ){\n";
    echo "LoadAjax('applications-squid-status','fw.proxy.status.php?applications-squid-status=yes')";
    echo "}\n";
    return true;
}

function popup_installed():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $jsAfter=$tpl->framework_buildjs("/proxy/debug/disable-instance",
        "squid.debug.progress",
        "squid.debug.log",
        "squid-in-debug-mode-div",
        "Loadjs('$page?after-download=yes')");

    $btn=$tpl->button_autnonome("{uninstall}",$jsAfter,ico_trash,"AsSystemAdministrator",435,"btn-warning");

    $form[]="<div id='squid-in-debug-mode-div'></div>";
    $form[]=$tpl->div_warning("{squid_debug_perform_remove}<div style='text-align:right;margin:20px'>$btn</div>");
    echo $tpl->_ENGINE_parse_body($form);
    return true;
}

function popup_wizard():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SquidInDebugModeHTTPPort=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidInDebugModeHTTPPort");


    if($SquidInDebugModeHTTPPort==0){
        $SquidInDebugModeHTTPPort=8880;
    }
    $Package=PROGRESS_DIR."/proxy-debug.tar.gz";

    if(is_file($Package)){
        $size=filesize($Package);
        $size=FormatBytes($size/1024);
        $date=date("Y-m-d H:i:s",filemtime($Package));
        $form[]="<div style='margin-bottom:65px;margin-top:30px'><H3><a href='ressources/logs/web/proxy-debug.tar.gz'>{download} proxy-debug.tar.gz ($size) {created} $date</a></H3></div>";
    }
    $form[]="<div id='squid-in-debug-mode-div'></div>";
    $form[]=$tpl->field_numeric("SquidInDebugModeHTTPPort","{listen_port} (HTTP)",$SquidInDebugModeHTTPPort);

    $jsAfter=$tpl->framework_buildjs("/proxy/debug/enable-instance",
        "squid.debug.progress",
        "squid.debug.log",
        "squid-in-debug-mode-div",
        "Loadjs('$page?after-js=yes')");

    echo $tpl->form_outside("",$form,"{squid_debug_perform_explain}","{start}",$jsAfter);
    return true;
}

function Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tpl->SAVE_POSTs();
    return true;
}