<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["user"])){Save();exit;}
if(isset($_GET["results"])){results();exit;}

js();

function js(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog6("{check_groups}","$page?popup=yes");
}

function popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]=$tpl->field_text("user","{verify_if_user}","");
    $html[]=$tpl->field_text("group","{isMemberOf}","");
    echo "<div id='popup-user-group'></div>";
    echo $tpl->form_outside("",$html,"{CheckGroupExplain}","{verify}","LoadAjax('popup-user-group','$page?results=yes')");
    return true;

}
function results():bool{

    $GOSQUIDAUTH=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GO-SQUID-AUTH");

    $tbl=explode("\n",$GOSQUIDAUTH);
    $sline=array();
    $sline[]="<div style='margin:5px;padding:5px;border:#CCCCCC 1px solid;;border-radius: 5px'>";
    foreach ($tbl as $line) {
        $line=trim($line);

        if(preg_match("#User.*?is memberOf#",$line)){
            $sline[]="<div style='font-size:12px' class='font-bold text-navy'>".$line."</div>";
            continue;
        }
        if(preg_match("#User.*?is not memberOf#",$line)){
            $sline[]="<div style='font-size:12px' class='font-bold text-danger'>".$line."</div>";
            continue;
        }

        if(preg_match("#^[0-9]+\s+(OK|ERR)#",$line,$m)){
            $R=$m[1];
            $sline[]="<H1>$R</H1>";
            continue;
        }

        $sline[]="<div style='font-size:12px'>".$line."</div>";

    }
    $sline[]="</div>";
    echo @implode("\n",$sline);
    return true;

}
function Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $user=urlencode($_POST["user"]);
    $group=urlencode($_POST["group"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/acls/checkgrp/$user/$group");
    return admin_tracks("Check if $user is a member of $group in Proxy acls");

}
