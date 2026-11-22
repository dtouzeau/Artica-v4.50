<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["restarted"])){restarted();exit;}
js();

function js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog2("modal:{FRAMEWORK_COM_ERROR}","$page?popup=yes",540);

}
function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<H1>{APP_FRAMEWORK}: {restarting}</H1>";
    $html[]="<div id='FRAMEWORK_COM_ERROR'></div>";


    $html[]="<script>";
    $html[]="LoadAjax('FRAMEWORK_COM_ERROR','$page?restarted=yes')";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
}

function restarted():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();

    if(isset($_GET["restart"])){
        // Cette page tourne dans artica-phpfpm, le service qu'elle relance, et se
        // recharge toutes les 2 s tant que le framework ne repond pas : une
        // relance par minute au plus, toutes sessions confondues (fichier
        // temoin). La reponse peut ne jamais revenir : le worker est tue par la
        // relance, le daemon la termine de son cote.
        $stamp=PROGRESS_DIR."/framework-restart.time";
        $last=is_file($stamp)?intval(@filemtime($stamp)):0;
        if(time()-$last>60){
            @touch($stamp);
            $GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/php/unixrestart");
        }
    }

    if(!CHECK_FRAMEWORK()){
        $html[]="<center style='margin:30px'><hr><h2 class='text-danger'>{FRAMEWORK_COM_ERROR}<br>{please_wait_reloading_service}</h2><hr></center>";
        $html[]="<script>";
        $html[]="function CHECK_FRAMEWORK(){";
        $html[]="LoadAjax('FRAMEWORK_COM_ERROR','$page?restarted=yes&restart=yes')";
        $html[]="}";
        $html[]="setTimeout(\"CHECK_FRAMEWORK()\",2000);";
        $html[]="</script>";
        echo $tpl->_ENGINE_parse_body($html);
        return true;
    }

    $html[]="<center style='margin:30px'><hr><H2 class='text-success'>{success}</H2><hr></center>";
    $html[]="<script>";
    $html[]="function CHECK_FRAMEWORK(){";
    $html[]="\tdialogInstance2.close();";
    $html[]="}";
    $html[]="setTimeout(\"CHECK_FRAMEWORK()\",3000);";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function CHECK_FRAMEWORK():bool{
    include_once (dirname(__FILE__)."/ressources/class.framework.inc");
    $frame=new fcgi_framework("index.php");
    $results=$frame->Get();
    $OK=true;
    if(!$frame->ok){
        VERBOSE("CHECK_FRAMEWORK ->index.php ERROR $frame->IOERROR",__LINE__);
        $OK=false;
    }
    if(!preg_match("#<OK>#",$results)){
        $OK=false;
    }
    return $OK;
}