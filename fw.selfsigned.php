<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["CommonName"])){Save();exit;}

js();

function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog1("{SELF_ROOT_CERT}", "$page?popup=yes&function=$function");
}


function popup(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $myhostname=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname");
    $db=file_get_contents(dirname(__FILE__) . '/ressources/databases/ISO-3166-Codes-Countries.txt');
    $tbl=explode("\n",$db);
    foreach ($tbl as $line){
        if(preg_match('#(.+?);\s+([A-Z]{1,2})#',$line,$regs)){
            $regs[2]=trim($regs[2]);
            $regs[1]=trim($regs[1]);
            $array_country_codes["{$regs[1]}_{$regs[2]}"]=$regs[1];
            $array_country_codes2[$regs[2]]="{$regs[1]}_{$regs[2]}";
        }
    }



    $ENC[1024]=1024;
    $ENC[2048]=2048;
    $ENC[4096]=4096;

    $ligne=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CertificateCenterCSR"));

    $t=time();
    if($ligne["CountryName"]==null){$ligne["CountryName"]="US";}
    if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
    if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
    if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
    if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
    if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}

    if($ligne["subjectAltName"]==null){$ligne["subjectAltName"]=$myhostname;}
    if($ligne["subjectAltName1"]==null){$ligne["subjectAltName1"]=$_SERVER["SERVER_ADDR"];}
    if($ligne["subjectAltName2"]==null){$ligne["subjectAltName2"]=$_SERVER["SERVER_NAME"];}
    if($ligne["CertificateName"]==null){$ligne["CertificateName"]="RootCA_".time();}

    if(!is_numeric($ligne["CertificateMaxDays"])){$ligne["CertificateMaxDays"]=3650;}
    if(!is_numeric($ligne["levelenc"])){$ligne["levelenc"]=4096;}

    $form[]=$tpl->field_text("CertificateName", "{CertificateName}", $ligne["CertificateName"],true,null);
    $form[]=$tpl->field_text("CommonName", "{CommonName}", $myhostname,true,null);
    $form[]=$tpl->field_text("subjectAltName", "{subjectAltName}", $ligne["subjectAltName"],true,null);
    $form[]=$tpl->field_text("subjectAltName1", "{subjectAltName} (1)", $ligne["subjectAltName1"],true,null);
    $form[]=$tpl->field_text("subjectAltName2", "{subjectAltName} (2)", $ligne["subjectAltName2"],false,null);
    $form[]=$tpl->field_array_hash($array_country_codes,"CountryName", "nonull:{countryName}", $ligne["CountryName"]);
    $form[]=$tpl->field_text("stateOrProvinceName", "{stateOrProvinceName}", $ligne["stateOrProvinceName"]);
    $form[]=$tpl->field_text("localityName", "{localityName}", $ligne["localityName"]);
    $form[]=$tpl->field_text("OrganizationName", "{organizationName}", $ligne["OrganizationName"]);
    $form[]=$tpl->field_text("OrganizationalUnit", "{organizationalUnitName}", $ligne["OrganizationalUnit"]);
    $form[]=$tpl->field_text("emailAddress", "{emailAddress}", $ligne["emailAddress"]);
    $form[]=$tpl->field_array_hash($ENC, "levelenc", "{level_encryption}", $ligne["levelenc"]);
    $form[]=$tpl->field_numeric("CertificateMaxDays","{CertificateMaxDays} ({days})",$ligne["CertificateMaxDays"]);

    $after[]="dialogInstance1.close();";
    if(strlen($function)>2){
        $after[]="$function();";
    }


    //LoadAjax('table-loader-sslcerts-service','fw.certificates-center.php?table=yes');";


    $html[]="<div id='progress-$t'></div>";
    $html[]=$tpl->form_outside("{SELF_ROOT_CERT}", @implode("\n", $form),"{SELF_ROOT_CERT_EXPLAIN}","{create}",@implode(";",$after),"AsCertifsManager");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST_XSS();
    $_POST["CertificateName"]=$tpl->CLEAN_BAD_CHARSNET($_POST["CertificateName"]);
    $_POST["NodeID"]=0;
    if(isset($_SESSION["HARMPID"])) {
        $_POST["NodeID"]=intval($_SESSION["HARMPID"]);
    }

    $CountryName=$_POST["CountryName"];
    if(preg_match("#^.+?_([A-Z]+)#",$CountryName,$re)){
        $_POST["CountryName"]=$re[1];
    }

    $sock=new sockets();
    $data=$sock->REST_API_POST("/certificate/selfsigned",$_POST);
    $json=json_decode($data);
    if(!$json->Status){
        $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks_post("Success Creating new self-signed certificate");


}