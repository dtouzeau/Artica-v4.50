<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");

if(isset($_GET["certificate-popup"])){certificate_wizard0();exit;}
if(isset($_GET["certificate-wiz1"])){certificate_wizard1();exit;}
if(isset($_GET["certificate-wiz2"])){certificate_wizard2();exit;}
if(isset($_GET["certificate-wiz3"])){certificate_wizard3();exit;}
if(isset($_POST["service-id"])){certificate_save();exit;}
js();



function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=intval($_GET["service-id"]);
    if($serviceid==0){
        return $tpl->js_error("Service ID is 0 ??");
    }
    $servicename=get_servicename($serviceid);
    return $tpl->js_dialog5($servicename. " > {certificate} > Let's Encrypt", "$page?certificate-popup=true&service-id=$serviceid",650);
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}
function NginxGetDB():string{
    if(!isHarmpID()){
        return "/home/artica/SQLITE/nginx.db";
    }
    $Gpid=$_SESSION["HARMPID"];
    return "/home/artica/SQLITE/nginx.$Gpid.db";
}

function certificate_wizard0():bool{
    $tpl=new template_admin();
    $serviceid=intval($_GET["service-id"]);
    $page=CurrentPageName();
    echo "<div id='certificate-wizard-$serviceid'></div>";
    echo "<script>LoadAjax('certificate-wizard-$serviceid','$page?certificate-wiz1=true&service-id=$serviceid')</script>";
    return true;
}

function certificate_wizard1():bool{
    $tpl=new template_admin();
    $serviceid=intval($_GET["service-id"]);
    $page=CurrentPageName();
    $Hosts=ExtractHost($serviceid);
    if(count($Hosts)==0){
        echo $tpl->div_error($tpl->_ENGINE_parse_body("{no_domain_valid}"));
        return false;
    }

    $tpl->table_form_section("","<p class='font-bold' style='margin-bottom: 15px;font-size:18px'>{letsencryptval1}</p>");
    $c=0;
    foreach ($Hosts as $host=>$val){

        if(!$val){
            $tpl->table_form_field_bool("<span style='text-transform:none'>$host</span>",0,ico_bug);
            continue;
        }
        $tpl->table_form_field_bool("<span style='text-transform:none'>$host</span>",1,ico_earth);
        $c++;
    }

    if($c==0){
        echo $tpl->div_error($tpl->_ENGINE_parse_body("{no_domain_valid}"));
        return false;
    }
    $tpl->table_form_button("{next}","LoadAjax('certificate-wizard-$serviceid','$page?certificate-wiz2=true&service-id=$serviceid')","",ico_arrow_right);


    echo $tpl->table_form_compile();
return true;
}

function ExtractHost($ID):array{
    $q                          = new lib_sqlite(NginxGetDB());
    $ligne      = $q->mysqli_fetch_array("SELECT hosts FROM nginx_services WHERE ID=$ID");
    $Zhosts=explode("||",$ligne["hosts"]);
    $DOMS=array();
    foreach ($Zhosts as $domains){
        if (trim($domains)==null){continue;}
        $domains=strtolower($domains);
        if(!isValidDomain($domains)){
            $DOMS[$domains]=false;
            continue;
        }
        $DOMS[$domains]=true;
    }
return $DOMS;
}
function isValidDomain(string $domain): bool{
    // 1. Quick sanity checks
    if ($domain === '' || strlen($domain) > 253) {
        return false;                               // RFC 1035/2181 total length
    }

    // 2. If intl is present, convert IDN → ASCII (xn--…)
    if (function_exists('idn_to_ascii')) {
        $converted = idn_to_ascii(
            $domain,
            IDNA_DEFAULT,                // flags
            INTL_IDNA_VARIANT_UTS46      // use modern UTS #46 rules
        );
        if ($converted === false) {
            return false;                // illegal code-points, bidi fail, etc.
        }
        $domain = $converted;
    }

    // 3. Split into labels and test each one
    $labels = explode('.', $domain);
    if (count($labels) < 2) {
        return false;                    // need at least one dot + TLD
    }

    foreach ($labels as $label) {
        // a) length 1–63
        $len = strlen($label);
        if ($len < 1 || $len > 63) {
            return false;
        }
        // b) allowed chars: a–z 0–9 hyphen, but never start/end with “-”
        //    (already punycoded to ASCII if it contained Unicode)
        if (!preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?$/', $label)) {
            return false;
        }
    }

    return true;
}
function isHarmpID():bool{
    if(!isset($_SESSION["HARMPID"])){
        return false;
    }
    if(intval($_SESSION["HARMPID"])==0){
        return false;
    }

    return true;
}
function certificate_wizard2():bool{
    $tpl=new template_admin();
    $serviceid=intval($_GET["service-id"]);
    $page=CurrentPageName();
    $html[]="<div id='certificate-progress-$serviceid'></div>";
    $form[]=$tpl->field_hidden("service-id",$serviceid);
    $form[]=$tpl->field_email("email","{email}","",true);
    $html[]= $tpl->form_outside("",$form,"{letsencryptval2}","{create_certificate}","Loadjs('$page?certificate-wiz3=true&service-id=$serviceid')");
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}

function certificate_wizard3():bool{
    $tpl=new template_admin();
    $encoded=$_SESSION["AUTOLETSENCRYPT"];
    $serviceid=intval($_GET["service-id"]);



    $js=$tpl->framework_buildjs("/reverse-proxy/letsencrypt/$encoded",
    "reverseProxyCreateLetsEncrypt.progress",
        "reverseProxyCreateLetsEncrypt.log",
        "certificate-progress-$serviceid",
        "dialogInstance5.close();");

    header("content-type: application/x-javascript");
    echo $js;
    return true;

}

function certificate_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $array["serviceid"]=intval($_POST["service-id"]);
    $array["email"]=$_POST["email"];

    $Hosts=ExtractHost($array["serviceid"]);
    if(count($Hosts)==0){
        echo $tpl->post_error($tpl->_ENGINE_parse_body("{no_domain_valid}"));
        return false;
    }
    $c=0;
    $domains=array();
    foreach ($Hosts as $host=>$val){
        if(!$val){
            continue;
        }
        $domains[]=$host;
    }
    if(count($domains)==0){
        echo $tpl->post_error($tpl->_ENGINE_parse_body("{no_domain_valid}"));
        return false;
    }
    $servicename=get_servicename($array["serviceid"]);
    $array["servicename"]=$servicename;
    $array["domains"]=@implode(",",$domains);
    $encoded=urlencode(base64_encode(serialize($array)));
    $_SESSION["AUTOLETSENCRYPT"]=$encoded;
    return admin_tracks("Create Let`s Encrypt certificate for $servicename");

}