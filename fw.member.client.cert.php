<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_POST["uid"])){Save();exit;}
if(isset($_GET["client-certificate"])){client_certificate();exit;}
page();


function page(){
    $tpl=new template_admin();
    $users=new usersMenus();
    $page=CurrentPageName();
    if(!$users->AllowAddGroup){
        echo $tpl->div_error("{ERROR_NO_PRIVS2}");
        return false;
    }

    $md=md5($_GET["sdata"]);
    $sdata=unserialize(base64_decode($_GET["sdata"]));
    $newdata=urlencode($_GET["sdata"]);
    $uid=$sdata["uid"];
    $type=$sdata["type"];
    $displayname=$sdata["displayname"];
    if(isset($sdata["dn"])){$tpl->field_hidden("dn",$sdata["dn"]);}

    $js=$tpl->framework_buildjs("webconsole.php?client-certificate=yes",
        "manager-certificate.progress","manager-certificate.log",
        "client-certificate-progress",
        "LoadAjax('client-certificate-results','$page?client-certificate=yes&sdata=$newdata');");

    $html[]="<div id='$md'></div>";
    $tpl->field_hidden("uid",$uid);
    $tpl->field_hidden("type",$type);
    $tpl->field_hidden("displayname",$displayname);
    $form[]=$tpl->field_password2("cert-password","{password}",null,true);
    $html[]="<div id='client-certificate-progress'></div>";
    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:240px'>";
    $html[]="<div id='client-certificate-results'></div>";
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;width:99%'>";
    $html[]= $tpl->form_outside("{client_certificate} <strong>&laquo;$displayname&raquo;</strong>",$form,null,"{create_certificate}",$js);
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('client-certificate-results','$page?client-certificate=yes&sdata=$newdata');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_POST["password"]=$_POST["cert-password"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CLEAN_CERTIFICATE_TEMP",base64_encode(serialize($_POST)));
    unset($_POST["password"]);
    foreach ($_POST as $key=>$val){
        $f[]="$key: $val";
    }

    admin_tracks("Generate Artica Web console Client certificate ".@implode(", ",$f));

}

function client_certificate(){
    $tpl=new template_admin();
    $users=new usersMenus();
    $page=CurrentPageName();
    $sdata=unserialize(base64_decode($_GET["sdata"]));
    $newdata=urlencode($_GET["sdata"]);
    $uid=$sdata["uid"];
    $type=$sdata["type"];
    $displayname=$sdata["displayname"];
    if($GLOBALS["VERBOSE"]){
        print_r($sdata);
    }
    $target_dir="/usr/share/artica-postfix/ressources/conf/certs";
    VERBOSE("$target_dir/$uid-$type.pfx",__LINE__);
    if(!is_file("$target_dir/$uid-$type.pfx")){
        echo $tpl->widget_grey("$displayname","$uid-$type.pfx<br>{certificate_not_created}");
        return false;
    }

    $js="document.location.href='/ressources/conf/certs/$uid-$type.pfx';";
    $btn[]=array("name"=>"{download}","js"=>$js,"icon"=>"fad fa-download");
    echo $tpl->widget_vert("$displayname","$uid-$type.pfx",$btn);
    return true;
}