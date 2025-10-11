<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");

if(isset($_POST["CommonName"])){letsencrypt_save();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["next-step-js"])){next_set_js();exit;}
if(isset($_GET["next-step-popup"])){next_set_popup();exit;}
if(isset($_GET["lets-encrypt-perform"])){next_set_perform();exit;}
letsencrypt_js();


function isReverse():bool{
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==1){
        return true;
    }
    $EnablePulseReverse=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePulseReverse"));
    if($EnablePulseReverse==1){
        return true;
    }
    $ActiveDirectoryRestLetsEncrypt    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestLetsEncrypt"));
    if($ActiveDirectoryRestLetsEncrypt==1){
        return true;
    }
    return false;
}

function letsencrypt_js():bool{
    $tpl=new template_admin();

    if(!isReverse()){
        return $tpl->js_error($tpl->_ENGINE_parse_body("{error_lesencrypt_nginx_not_installed}"));
    }



    $page=CurrentPageName();
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    if(isset($_GET["ID"])){
        $ID=intval($_GET["ID"]);
        if($ID>0){
            return $tpl->js_dialog3("{LETSENCRYPT_CERTIFICATE} #$ID", "$page?next-step-popup=yes&function=$function&ID=$ID&function2=$function2");
        }
    }
    return $tpl->js_dialog3("{LETSENCRYPT_CERTIFICATE}", "$page?popup=yes&function=$function&function2=$function2");

}
function next_set_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    return $tpl->js_dialog3("{LETSENCRYPT_CERTIFICATE} #{$_SESSION["LetsEncryptSaved"]}", "$page?next-step-popup=yes&function=$function&function2=$function2");
}
function next_set_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_SESSION["HARMPID"]);
    $function2=$_GET["function2"];
    $q=new lib_sqlite(GetDatabase());
    $function=$_GET["function"];
    $ID=$_SESSION["LetsEncryptSaved"];
    if(isset($_GET["ID"])){
        $ID=$_GET["ID"];
    }
    $ligne=$q->mysqli_fetch_array("SELECT * FROM sslcertificates WHERE ID=$ID");

    $subjectAltName=$ligne["subjectAltName"];
    $subjectAltName1=$ligne["subjectAltName1"];
    $subjectAltName2=$ligne["subjectAltName2"];
    $emailAddress=$ligne["emailAddress"];
    $wws=subjectAltNameToString($subjectAltName,$subjectAltName1,$subjectAltName2);
    if(count($wws)==0){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/certificate/infos/$ID/$gpid"));
        if(!$json->Status){
            echo $tpl->div_error("Could not obtain certificate data<br>$json->Error");
            return false;
        }
        $UPD=array();
        if(strlen($json->Info->SubjectAltName)>3){
            $subjectAltName=$json->Info->SubjectAltName;
            $UPD[]="SubjectAltName='$subjectAltName'";
        }else{
            $subjectAltName=$json->Info->DnsNames[0];
            $UPD[]="SubjectAltName='$subjectAltName'";
        }
        if(strlen($json->Info->SubjectAltName1)>3){
            $subjectAltName1=$json->Info->SubjectAltName1;
            $UPD[]="subjectAltName1='$subjectAltName1'";
        }
        if(strlen($json->Info->SubjectAltName2)>3){
            $subjectAltName2=$json->Info->SubjectAltName2;
            $UPD[]="subjectAltName2='$subjectAltName2'";
        }
        if(count($UPD)==0) {
            echo $tpl->div_error("Could not obtain certificate domain names");
            return false;
        }
        $q->QUERY_SQL("UPDATE sslcertificates SET ".@implode(",",$UPD)." WHERE ID=$ID");
        $wws=subjectAltNameToString($subjectAltName,$subjectAltName1,$subjectAltName2);
    }

    $text=$tpl->_ENGINE_parse_body("{WIZARD_LETSENCRYPT}");
    $text=str_replace("%d",@implode(", ",$wws),$text);
    $text=str_replace("%o",$emailAddress,$text);
    $html[]="<div style='width:95%' id='letsencrypt-results'>";
    $html[]=$tpl->div_explain("{LETSENCRYPT_CERTIFICATE}||$text");

    $CHECK_LETSENCRIPT_BEFORE=$tpl->_ENGINE_parse_body("{CHECK_LETSENCRIPT_BEFORE}");
    $CHECK_LETSENCRIPT_BEFORE=str_replace("%s",$tpl->td_href("letsdebug.net","https://letsdebug.net","s_PopUpFull('https://letsdebug.net','1024','900');"),$CHECK_LETSENCRIPT_BEFORE);

    $html[]=$tpl->div_warning("Let's Debug||$CHECK_LETSENCRIPT_BEFORE");
    $html[]="</div>";
    $html[]="<hr>";

    $import=$tpl->button_autnonome("{create_certificate}","LoadAjax('letsencrypt-results','$page?lets-encrypt-perform=$ID&function=$function&function2=$function2')",ico_certificate,"AsSystemAdministrator",335,"btn-warning");

    $html[]="<div style='text-align:right'>$import</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function subjectAltNameToString($subjectAltName,$subjectAltName1,$subjectAltName2){
    $www=array();
    $www=subjectAltNamePop($www,$subjectAltName);
    $www=subjectAltNamePop($www,$subjectAltName1);
    $www=subjectAltNamePop($www,$subjectAltName2);
    $FINAL=array();
    foreach ($www as $key=>$value){
        if(strlen($key)<4){
            continue;
        }
        $FINAL[]=$key;

    }
    return $FINAL;
}

function subjectAltNamePop($array,$subjectAltName):array{
    $subjectAltName=trim(strtolower($subjectAltName));
    if(strlen($subjectAltName)<4){
        return $array;
    }
    if(strpos(" $subjectAltName",",")>0){
        $f=explode(",",$subjectAltName);
        foreach ($f as $dom){
            $dom=trim(strtolower($dom));
            if(strlen($dom)<3){continue;}
            $array[$dom]=true;
        }
        return $array;
    }
    $array[$subjectAltName]=true;
    return $array;
}


function next_set_perform():bool{
    $ID=intval($_GET["lets-encrypt-perform"]);
    $tpl=new template_admin();
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    $gpid=0;
    if(isset($_SESSION["HARMPID"])) {
        $gpid = intval($_SESSION["HARMPID"]);
    }

    $sock=new sockets();
    $sock->REST_API_TIMEOUT=60;
    $data=$sock->REST_API("/certificate/letsencrypt/$ID/$gpid");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error($tpl->_ENGINE_parse_body(json_last_error_msg()));
        return false;
    }


    if (!$json->Status){
        $json->Error=str_replace("::","<br>",$json->Error);
        $json->Traces=str_replace("::","<br>",$json->Traces);
        echo $tpl->_ENGINE_parse_body($tpl->div_error($json->Error."<br>".$json->Traces));
        return false;
    }

    $html[]="<script>";
    if(strlen($function)>3) {
        $html[] = "$function();";
    }
    if(strlen($function2)>3) {
        $html[] =base64_decode($function2);
    }

    $html[]="dialogInstance3.close();";
    $html[]="</script>";
    echo @implode("\n",$html);


    return true;

}

function letsencrypt_save():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $q=new lib_sqlite(GetDatabase());

    $textes=array("subjectAltName", "subjectAltName1", "subjectAltName2", "CertificateCenterCSR", "AdditionalNames", "subjectKeyIdentifier", "letsencrypt_dns_key","emailAddress");

    foreach ($textes as $field){
        if(!$q->FIELD_EXISTS("sslcertificates","$field")) {
            $q->QUERY_SQL("ALTER TABLE sslcertificates ADD $field TEXT NOT NULL DEFAULT ''");
        }
    }
    $CommonName=$_POST["CommonName"];
    $subjectAltName=$_POST["SubjectAltName"];
    $subjectAltName1=$_POST["SubjectAltName1"];
    $subjectAltName2=$_POST["subjectAltName2"];


    $addwww=intval($_POST["addwww"]);
    if($addwww==1) {

        $subjectAltName=removePrefix($subjectAltName);
        $subjectAltName1=removePrefix($subjectAltName1);
        $subjectAltName2=removePrefix($subjectAltName2);


        $subjectAltName=AddPrefix($subjectAltName);
        $subjectAltName1=AddPrefix($subjectAltName1);
        $subjectAltName2=AddPrefix($subjectAltName2);
    }

    $emailAddress=$_POST["emailAddress"];
    $letsencrypt_dns_key=md5(serialize($_POST));

    $q->QUERY_SQL("INSERT INTO sslcertificates(CommonName,subjectAltName,subjectAltName1,subjectAltName2,emailAddress,letsencrypt_dns_key,UseLetsEncrypt,UsePrivKeyCrt ) VALUES ('$CommonName','$subjectAltName','$subjectAltName1','$subjectAltName2','$emailAddress','$letsencrypt_dns_key',1,1)");
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE letsencrypt_dns_key='$letsencrypt_dns_key'");

    $ID=intval($ligne["ID"]);
    if($ID==0){echo $tpl->post_error("ID is 0 ?? never append!!??");return false;}
    $_SESSION["LetsEncryptSaved"]=$ID;
    return true;




}

function AddPrefix($domain){
    $domain=trim($domain);
    if(strlen($domain)<3){
        return "";
    }

    if(strpos(" $domain",",")==0){
        return "$domain,www.$domain";
    }
    $f=array();
    $tb=explode(",",$domain);
    foreach ($tb as $dom){
        $dom=trim(strtolower($dom));
        if(strlen($dom)<3){continue;}
        if(isset($AL[$dom])){continue;}
        $f[]=$dom;
        $f[]="www.$dom";
        $AL[$dom]=true;
    }
    return @implode(",",$f);
}

function removePrefix($domain){

    if(strpos(" $domain",",")==0){
        return removeWwwPrefix($domain);
    }
    $f=array();
    $tb=explode(",",$domain);
    foreach ($tb as $dom){
        $dom=trim(strtolower($dom));
        if(isset($AL[$dom])){continue;}
        $dom=removeWwwPrefix($dom);
        if(strlen($dom)<3){continue;}
        $f[]=$dom;
        $AL[$dom]=true;
    }
    return @implode(",",$f);
}

function removeWwwPrefix($domain) {
    if(strlen($domain)<3){
        return $domain;
    }
    $parsedUrl = parse_url($domain);
    if (isset($parsedUrl['host'])) {
        $host = $parsedUrl['host'];
        if (strpos($host, 'www.') === 0) {
            $host = str_replace('www.', '', $host);
        }
        return $host;
    }
    return $domain;
}


function popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $page=CurrentPageName();

    $html[]="<div style='width:95%'>";
    $html[]=$tpl->div_explain("{LETSENCRYPT_CERTIFICATE}||{howto_letsencrypt}");
    $form[]=$tpl->field_text("CommonName","{CertificateName}","",true);
    $form[]=$tpl->field_email("emailAddress","{email}","",true);
    $form[]=$tpl->field_checkbox("addwww","{also_add_www}");
    $form[]=$tpl->field_text("SubjectAltName","{SiteDomain}","",true);
    $form[]=$tpl->field_text("SubjectAltName1","{domain} 2","");
    $form[]=$tpl->field_text("SubjectAltName2","{domain} 3","");
     $html[]=$tpl->form_outside(null, $form,null,"{save}","$function();Loadjs('$page?next-step-js=yes&function=$function');","AsCertifsManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function GetDatabase(){
    $db="/home/artica/SQLITE/certificates.db";
    if(isset($_SESSION["HARMPID"])){
        $gpid=intval($_SESSION["HARMPID"]);
        if($gpid>0){
            $db="/home/artica/SQLITE/certificates.$gpid.db";
        }
    }
    return $db;
}
