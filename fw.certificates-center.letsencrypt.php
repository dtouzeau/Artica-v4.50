<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");

if(isset($_POST["CommonName"])){letsencrypt_save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["next-step-js"])){next_set_js();exit;}
if(isset($_GET["next-step-popup"])){next_set_popup();exit;}
if(isset($_GET["lets-encrypt-perform"])){next_set_perform();exit;}
if(isset($_GET["save-standalone"])){ save_standalone(); exit; }
if(isset($_GET["wizard-popup"])){wizard_popup();exit;}
if(isset($_GET["wizard-step1"])){wizard_step1();exit;}

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
function save_standalone(){
    $value=intval($_GET["value"]);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LetsEncryptStandaloneHTTP01",$value);
}

function tabs(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=isset($_GET["function"]) ? $_GET["function"] : "";
    $function2=isset($_GET["function2"]) ? $_GET["function2"] : "";
    $ID=isset($_GET["ID"]) ? $_GET["ID"] : 0;
    if(isset($_GET["EnableStandAlone"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LetsEncryptStandaloneHTTP01",intval($_GET["EnableStandAlone"]));
        $standalone=intval($_GET["EnableStandAlone"]);
    }
    $isReverse=isReverse();


    $html=array();
    // Standalone toggle — always visible so a relay operator can enable it,
    // which then reveals the HTTP-01 tab. Saving reloads this tabs container.
    $reload="LoadAjax('letsencrypt-tabs','$page?tabs=yes&function=$function&function2=$function2&ID=$ID');";
    $onclick="Loadjs('$page?save-standalone=yes&value='+(this.checked?1:0));setTimeout(function(){ $reload },400);";


    if($standalone==1) {
        $isReverse=false;
        $html[] = $tpl->div_explain("{LE_STANDALONE_HTTP01}||{LE_STANDALONE_HTTP01_EXPLAIN}");
    }

    $tabs=array();
    if($isReverse){
        $tabs["{http_request} {APP_NGINX}"]="$page?popup=yes&function=$function&function2=$function2&ID=$ID";
        if($ID>0){
            unset($tabs);
            $tabs["{http_request} #$ID"]="$page?next-step-popup=yes&function=$function&ID=$ID&function2=$function2";
        }


    }else{
        if($standalone==1 && $ID>0){
            $tabs["{http_request} #$ID"]="$page?next-step-popup=yes&function=$function&ID=$ID&function2=$function2";
        }else{
            $tabs["{http_request}"]="$page?popup=yes&function=$function&function2=$function2&ID=$ID";
        }
    }


    if($standalone==0) {
        $tabs["{dns_request}"] = "fw.certificates-center.letsencrypt.dns.php?tabs=yes&function=$function&function2=$function2";
    }
    $tabs["{renewal_history}"]="fw.letsencrypt.renewals.php?popup=yes";
    $html[]=$tpl->tabs_default($tabs);
    echo $tpl->_ENGINE_parse_body("<div id='letsencrypt-tabs'>".@implode("\n",$html)."</div>");
}

function wizard_popup(){
    $ID=0;
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    if(isset($_GET["ID"])){
        $ID=intval($_GET["ID"]);
    }
    $page=CurrentPageName();
    echo "<div id='letsencrypt-wizard-div'></div>";
    echo "<script>LoadAjax('letsencrypt-wizard-div','$page?wizard-step1=yes&function=$function&function2=$function2&ID=$ID');</script>";
}
function wizard_step1(){
    $tpl=new template_admin();
    $ID=0;
    $page=CurrentPageName();
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    if(isset($_GET["ID"])){
        $ID=intval($_GET["ID"]);
    }
    $isReverse=isReverse();
    $StandAloneJS="LoadAjax('letsencrypt-wizard-div','$page?tabs=yes&function=$function&function2=$function2&ID=$ID&EnableStandAlone=1');";
    $ReverseJS="LoadAjax('letsencrypt-wizard-div','$page?tabs=yes&function=$function&function2=$function2&ID=$ID&EnableStandAlone=0');";
    $btn1=$tpl->button_autnonome("Go&nbsp;&raquo;&raquo;",$StandAloneJS,
        ico_certificate,"AsSystemAdministrator",250,"btn-primary");

    $btn2=$tpl->button_autnonome("Go&nbsp;&raquo;&raquo;",$ReverseJS,
        ico_certificate,"AsSystemAdministrator",250,"btn-primary");


    if(!$isReverse){$html[]=$tpl->div_explain("{LE_STANDALONE_HTTP01}||{LE_STANDALONE_HTTP01_EXPLAIN}<div style='margin-top:20px;text-align:right;margin-right:30px'>$btn1</div>");}
    if($isReverse){$html[]=$tpl->div_explain("{LETSENCRYPT_CERTIFICATE} / {APP_NGINX}||{howto_letsencrypt}<div style='margin-top:20px;text-align:right;margin-right:30px'>$btn2</div>");}
    $html[]=$tpl->div_explain("{LETSENCRYPT_DNS_CERTIFICATE}||{howto_letsencrypt_dns}<div style='margin-top:20px;text-align:right;margin-right:30px'>$btn2</div>");
    echo $tpl->_ENGINE_parse_body($html);
}


function letsencrypt_js():bool{
    $tpl=new template_admin();
    $ID=0;
    $page=CurrentPageName();
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    if(isset($_GET["ID"])){
        $ID=intval($_GET["ID"]);
    }
    $uri="$page?tabs=yes&function=$function&function2=$function2&ID=$ID";
    if(isset($_GET["wizard"])){
        $uri="$page?wizard-popup=yes&function=$function&function2=$function2&ID=$ID";
    }
    return $tpl->js_dialog3("{LETSENCRYPT_CERTIFICATE}", $uri);
}
function next_set_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $function2=$_GET["function2"];
    return $tpl->js_dialog3("{LETSENCRYPT_CERTIFICATE} #{$_SESSION["LetsEncryptSaved"]}", "$page?next-step-popup=yes&ID={$_SESSION["LetsEncryptSaved"]}&function=$function&function2=$function2");
}
function next_set_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $gpid=intval($_SESSION["HARMPID"]);
    $function2=$_GET["function2"];
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $function=$_GET["function"];
    $ID=intval($_SESSION["LetsEncryptSaved"] ?? 0);
    if(isset($_GET["ID"])){
        $ID=intval($_GET["ID"]);
    }
    $row=$cdb->getById($ID);
    if(!is_object($row)){
        echo $tpl->div_error("Certificate $ID not found: $cdb->lastError");
        return false;
    }

    $subjectAltName=(string)($row->subjectAltName ?? "");
    $subjectAltName1=(string)($row->subjectAltName1 ?? "");
    $subjectAltName2=(string)($row->subjectAltName2 ?? "");
    $dom=(string)($row->domains ?? "");
    $emailAddress=(string)($row->emailAddress ?? "");
    $wws=subjectAltNameToString($subjectAltName,$subjectAltName1,$subjectAltName2,$dom);
    if(count($wws)==0){
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/certificate/infos/$ID/$gpid"));
        if(!$json->Status){
            echo $tpl->div_error("Could not obtain certificate data<br>$json->Error");
            return false;
        }
        $UPD=array();
        if(strlen($json->Info->SubjectAltName)>3){
            $subjectAltName=$json->Info->SubjectAltName;
            $UPD["subjectAltName"]=$subjectAltName;
        }else{
            $subjectAltName=$json->Info->DnsNames[0];
            $UPD["subjectAltName"]=$subjectAltName;
        }
        if(strlen($json->Info->SubjectAltName1)>3){
            $subjectAltName1=$json->Info->SubjectAltName1;
            $UPD["subjectAltName1"]=$subjectAltName1;
        }
        if(strlen($json->Info->SubjectAltName2)>3){
            $subjectAltName2=$json->Info->SubjectAltName2;
            $UPD["subjectAltName2"]=$subjectAltName2;
        }
        // Persist the full SAN set (the cert may have more than 3 hosts).
        if(isset($json->Info->DnsNames) && is_array($json->Info->DnsNames) && count($json->Info->DnsNames)>0){
            $dom=implode(",",$json->Info->DnsNames);
            $UPD["domains"]=$dom;
        }
        if(count($UPD)==0) {
            echo $tpl->div_error("Could not obtain certificate domain names");
            return false;
        }
        $cdb->update($ID, $UPD);
        $wws=subjectAltNameToString($subjectAltName,$subjectAltName1,$subjectAltName2,$dom);
    }

    $text=$tpl->_ENGINE_parse_body("{WIZARD_LETSENCRYPT}");
    $text=str_replace("%d",@implode(", ",$wws),$text);
    $text=str_replace("%o",$emailAddress,$text);
    $html[]="<div style='width:95%;margin-top:10px' id='letsencrypt-results'>";
    $CHECK_LETSENCRIPT_BEFORE=$tpl->_ENGINE_parse_body("{CHECK_LETSENCRIPT_BEFORE}");
    $CHECK_LETSENCRIPT_BEFORE=str_replace("%s",$tpl->td_href("letsdebug.net","https://letsdebug.net","s_PopUpFull('https://letsdebug.net','1024','900');"),$CHECK_LETSENCRIPT_BEFORE);
    $html[]=$tpl->div_explain("{LETSENCRYPT_CERTIFICATE}||$text<br><hr>$CHECK_LETSENCRIPT_BEFORE");
    
    $html[]="</div>";
    $html[]="<hr>";

    $import=$tpl->button_autnonome("{create_certificate}","LoadAjax('letsencrypt-results','$page?lets-encrypt-perform=$ID&function=$function&function2=$function2')",ico_certificate,"AsSystemAdministrator",335,"btn-warning");

    $html[]="<div style='text-align:right'>$import</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function subjectAltNameToString($subjectAltName,$subjectAltName1,$subjectAltName2,$domains=""){
    $www=array();
    $www=subjectAltNamePop($www,$subjectAltName);
    $www=subjectAltNamePop($www,$subjectAltName1);
    $www=subjectAltNamePop($www,$subjectAltName2);
    // The `domains` column holds the full SAN set as a CSV (subjectAltName/1/2
    // only carry the first 3). subjectAltNamePop expands the CSV, so passing it
    // here surfaces every host of a SAN certificate.
    $www=subjectAltNamePop($www,$domains);
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
    $standalone=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LetsEncryptStandaloneHTTP01"));
    if($standalone==1){
        $pf=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/letsencrypt/http01/preflight"));
        if(is_object($pf) && isset($pf->open) && $pf->open){
            $svc=isset($pf->service) ? $pf->service : "";
            $pid=isset($pf->pid) ? intval($pf->pid) : 0;
            $msg="{LE_HTTP01_PORT80_INUSE} ".htmlspecialchars($svc)." (PID ".$pid."). {LE_HTTP01_INVESTIGATE}";
            echo $tpl->_ENGINE_parse_body($tpl->div_error($msg));
            return false;
        }
    }
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
        // Pentest 2026-11-04 : seul un JavaScript signe par la console est execute.
        $html[] =signed_html_decode($function2);
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
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();

    $CommonName=$_POST["CommonName"];
    $subjectAltName=$_POST["SubjectAltName"] ?? "";
    $subjectAltName1=$_POST["SubjectAltName1"] ?? "";
    $subjectAltName2=$_POST["SubjectAltName2"] ?? "";


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

    $ID=$cdb->insert([
        "CommonName"=>$CommonName,
        "subjectAltName"=>$subjectAltName,
        "subjectAltName1"=>$subjectAltName1,
        "subjectAltName2"=>$subjectAltName2,
        "emailAddress"=>$emailAddress,
        "letsencrypt_dns_key"=>$letsencrypt_dns_key,
        "UseLetsEncrypt"=>1,
        "UsePrivKeyCrt"=>1,
    ]);
    if($ID<=0){echo $tpl->post_error($cdb->lastError);return false;}
    $_SESSION["LetsEncryptSaved"]=$ID;

    return admin_tracks("Certificate-center: $emailAddress Create a new Let's Encrypt certificate for $CommonName/$subjectAltName/$subjectAltName2");




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
    $LetsEncryptStandaloneHTTP01=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LetsEncryptStandaloneHTTP01"));
    $page=CurrentPageName();

    $html[]="<div style='width:95%;margin-top:10px'>";
    if($LetsEncryptStandaloneHTTP01==0) {
        $html[] = $tpl->div_explain("{LETSENCRYPT_CERTIFICATE}||{howto_letsencrypt}");
    }
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

