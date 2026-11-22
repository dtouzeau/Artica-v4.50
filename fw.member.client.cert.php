<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_POST["uid"])){Save();exit;}
if(isset($_GET["client-certificate"])){client_certificate();exit;}
if(isset($_GET["download"])){client_certificate_download();exit;}
page();


/**
 * Decode le parametre sdata, la seule entree de cette page.
 *
 * Deux gardes, pas une:
 *
 *  - allowed_classes=false (PHP 7.0+) : sans lui, unserialize() INSTANCIE toute
 *    classe nommee dans la chaine et declenche ses __wakeup()/__destruct() avec
 *    des proprietes choisies par l'appelant. La console charge plus de 450
 *    classes; un ?sdata= forge etait donc une injection d'objet PHP, la porte
 *    d'entree la plus large qu'une page puisse offrir.
 *  - le retour est teste : unserialize() rend false sur une chaine invalide, et
 *    false["uid"] ne leve rien -- la page continuait avec des valeurs nulles.
 *
 * Renvoie null quand rien d'exploitable n'a pu etre lu.
 */
function cert_sdata(){
    $raw=isset($_GET["sdata"])?(string)$_GET["sdata"]:"";
    if($raw===""){return null;}
    $data=base64_decode($raw,true);
    if($data===false){return null;}
    $sdata=@unserialize($data,array("allowed_classes"=>false));
    if(!is_array($sdata)){return null;}
    return $sdata;
}

/**
 * Un composant du nom de fichier du certificat.
 *
 * $uid et $type sortent de sdata et sont concatenes en "<uid>-<type>.pfx", a la
 * fois dans un chemin disque (is_file/readfile cote nginx) et dans une URL de
 * telechargement. Ni "/" ni "\" ne figurent dans la classe autorisee: aucune
 * traversee n'est representable, et un identifiant d'annuaire legitime
 * (sAMAccountName, uid, adresse) y entre entierement.
 */
function cert_valid_component($v):bool{
    return preg_match('#^[A-Za-z0-9._@-]+$#',(string)$v)==1;
}

function page(){
    $tpl=new template_admin();
    $users=new usersMenus();
    $page=CurrentPageName();
    if(!$users->AllowAddGroup){
        echo $tpl->div_error("{ERROR_NO_PRIVS2}");
        return false;
    }

    $sdata=cert_sdata();
    if($sdata===null){
        echo $tpl->div_error("{error}: sdata");
        return false;
    }
    $md=md5($_GET["sdata"]);
    $newdata=urlencode($_GET["sdata"]);
    $uid=isset($sdata["uid"])?(string)$sdata["uid"]:"";
    $type=isset($sdata["type"])?(string)$sdata["type"]:"";
    $displayname=isset($sdata["displayname"])?(string)$sdata["displayname"]:"";
    if(!cert_valid_component($uid) || !cert_valid_component($type)){
        echo $tpl->div_error("{error}: uid/type");
        return false;
    }
    // Ce nom vient de l'annuaire via l'URL: il partait BRUT dans le titre du
    // formulaire et dans les widgets.
    $displaynameHtml=htmlspecialchars($displayname);
    if(isset($sdata["dn"])){$tpl->field_hidden("dn",(string)$sdata["dn"]);}

    // The daemon signs the certificate during the save (POST /webconsole/client-cert/member):
    // once the form is saved, only the result block needs refreshing.
    $js="LoadAjax('client-certificate-results','$page?client-certificate=yes&sdata=$newdata');";

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
    $html[]= $tpl->form_outside("{client_certificate} <strong>&laquo;$displaynameHtml&raquo;</strong>",$form,null,"{create_certificate}",$js);
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
    $users=new usersMenus();
    if(!$users->AllowAddGroup){
        echo $tpl->post_error("{ERROR_NO_PRIVS2}");
        return false;
    }
    $tpl->CLEAN_POST();
    $uid=isset($_POST["uid"])?(string)$_POST["uid"]:"";
    $type=isset($_POST["type"])?(string)$_POST["type"]:"";
    if(!cert_valid_component($uid) || !cert_valid_component($type)){
        echo $tpl->post_error("{error}: uid/type");
        return false;
    }
    // The password goes to the daemon in the request body only. It used to be kept,
    // in clear text, in the CLEAN_CERTIFICATE_TEMP setting.
    $req=array(
        "uid"=>$uid,
        "type"=>$type,
        "displayname"=>isset($_POST["displayname"])?(string)$_POST["displayname"]:"",
        "dn"=>isset($_POST["dn"])?(string)$_POST["dn"]:"",
        "password"=>isset($_POST["cert-password"])?(string)$_POST["cert-password"]:"",
    );
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/webconsole/client-cert/member",$req,120));
    if(!is_object($json)){
        echo $tpl->post_error("{error}: REST API");
        return false;
    }
    if(!$json->Status){
        echo $tpl->post_error(property_exists($json,"Error")?$json->Error:"{failed}");
        return false;
    }
    admin_tracks("Generate Artica Web console Client certificate $type:$uid");
    return true;
}

function client_certificate(){
    $tpl=new template_admin();
    $sdata=cert_sdata();
    if($sdata===null){
        echo $tpl->div_error("{error}: sdata");
        return false;
    }
    $uid=isset($sdata["uid"])?(string)$sdata["uid"]:"";
    $type=isset($sdata["type"])?(string)$sdata["type"]:"";
    $displayname=isset($sdata["displayname"])?(string)$sdata["displayname"]:"";
    // Ces deux valeurs composent un chemin disque ET une URL de telechargement:
    // sans controle, "?sdata=" forge remontait l'arborescence.
    if(!cert_valid_component($uid) || !cert_valid_component($type)){
        echo $tpl->div_error("{error}: uid/type");
        return false;
    }
    $displaynameHtml=htmlspecialchars($displayname);
    $pfx="$uid-$type.pfx";
    $pfxHtml=htmlspecialchars($pfx);

    if($GLOBALS["VERBOSE"]){
        print_r($sdata);
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/client-cert/status"));
    $members=(is_object($json) && property_exists($json,"members") && is_array($json->members))?$json->members:array();
    if(!in_array("$uid-$type",$members,true)){
        echo $tpl->widget_grey("$displaynameHtml","$pfxHtml<br>{certificate_not_created}");
        return false;
    }

    $page=CurrentPageName();
    $js="document.location.href='$page?download=yes&sdata=".urlencode($_GET["sdata"])."';";
    $btn[]=array("name"=>"{download}","js"=>$js,"icon"=>"fad fa-download");
    echo $tpl->widget_vert("$displaynameHtml","$pfxHtml",$btn);
    return true;
}

// The PFX is no longer under the web root: it is read from the daemon and sent here,
// to an administrator allowed to manage members only.
function client_certificate_download(){
    $users=new usersMenus();
    if(!$users->AllowAddGroup){die("DIE ".__FILE__." Line: ".__LINE__);}
    $sdata=cert_sdata();
    if($sdata===null){die();}
    $uid=isset($sdata["uid"])?(string)$sdata["uid"]:"";
    $type=isset($sdata["type"])?(string)$sdata["type"]:"";
    if(!cert_valid_component($uid) || !cert_valid_component($type)){die();}
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/webconsole/client-cert/member/".rawurlencode($type)."/".rawurlencode($uid)));
    if(!is_object($json) || !$json->Status || !property_exists($json,"pfx")){die();}
    $data=base64_decode((string)$json->pfx,true);
    if($data===false || strlen($data)==0){die();}
    header("Content-Type: application/x-pkcs12");
    header("Content-Transfer-Encoding: binary");
    header("Content-Disposition: attachment; filename=\"$uid-$type.pfx\"");
    header("Cache-Control: no-store");
    header("Content-Length: ".strlen($data));
    admin_tracks("Downloaded Web console client certificate $type:$uid");
    echo $data;
}
