<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");

if(isset($_GET["certificate-popup"])){certificate_wizard0();exit;}
if(isset($_GET["certificate-wiz1"])){certificate_wizard1();exit;}
if(isset($_GET["certificate-wiz2"])){certificate_wizard2();exit;}
if(isset($_GET["certificate-wiz3"])){certificate_wizard3();exit;}
if(isset($_POST["le-wiz1"])){le_wiz1_save();exit;}
if(isset($_POST["service-id"])){certificate_save();exit;}
js();



function js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $serviceid=intval($_GET["service-id"]);
    $function=$_GET["function"];
    if($serviceid==0){
        return $tpl->js_error("Service ID is 0 ??");
    }
    $servicename=get_servicename($serviceid);
    return $tpl->js_dialog5($servicename. " > {certificate} > Let's Encrypt", "$page?certificate-popup=true&service-id=$serviceid&function=$function",650);
}
function get_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $sock=new socksngix($ID);
    return $sock->GetServiceName();
}
function fetch_nginx_service_hosts($ID):string{
    $ID=intval($ID);
    if($ID==0){return "";}
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/ID/$ID");
    $data=json_decode($resp,true);
    if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return "";}
    return isset($data["rows"][0]["hosts"]) ? strval($data["rows"][0]["hosts"]) : "";
}

function certificate_wizard0():bool{
    $tpl=new template_admin();
    $serviceid=intval($_GET["service-id"]);
    $function=$_GET["function"];
    $page=CurrentPageName();
    echo "<div id='certificate-wizard-$serviceid'></div>";
    echo "<script>LoadAjax('certificate-wizard-$serviceid','$page?certificate-wiz1=true&service-id=$serviceid&function=$function')</script>";
    return true;
}

function certificate_wizard1():bool{
    $tpl=new template_admin();
    $serviceid=intval($_GET["service-id"]);
    $function=$_GET["function"];
    $page=CurrentPageName();
    $Hosts=ExtractHost($serviceid);
    if(count($Hosts)==0){
        echo $tpl->div_error($tpl->_ENGINE_parse_body("{no_domain_valid}"));
        return false;
    }

    // Per-name selection: each valid server name is an interactive checkbox
    // (checked = included in the Let's Encrypt certificate). Unchecked names are
    // excluded from the cert and get a 302 redirect to the primary (first checked)
    // name — handled in certificate_save(). Invalid names cannot be certified and
    // are shown disabled.
    $form=array();
    $form[]=$tpl->field_hidden("service-id",$serviceid);
    $form[]=$tpl->field_hidden("function",$function);
    $form[]=$tpl->field_hidden("le-wiz1","1");
    $hostmap=array();
    $c=0;
    foreach ($Hosts as $host=>$val){
        if(!$val){
            $form[]="<div style='color:#999;padding:4px 0'><i class='".ico_bug."'></i>&nbsp;<span style='text-transform:none'>".htmlspecialchars($host)."</span></div>";
            continue;
        }
        $md=md5($host);
        $hostmap[$md]=$host;
        $form[]=$tpl->field_checkbox("le_inc_$md","<span style='text-transform:none'>".htmlspecialchars($host)."</span>",1);
        $c++;
    }
    $form[]=$tpl->field_hidden("le-hostmap",base64_encode(json_encode($hostmap)));

    if($c==0){
        echo $tpl->div_error($tpl->_ENGINE_parse_body("{no_domain_valid}"));
        return false;
    }
    $jsafter="LoadAjax('certificate-wizard-$serviceid','$page?certificate-wiz2=true&service-id=$serviceid&function=$function')";
    echo $tpl->_ENGINE_parse_body($tpl->form_outside("",$form,"{letsencryptval1}","{next}",$jsafter,"AsWebMaster"));
    return true;
}

// Stores the per-name include selection from certificate_wizard1 into the session.
// Triggered by the wizard-1 form POST (marker le-wiz1). certificate_save() reads it.
function le_wiz1_save():bool{
    $serviceid=intval($_POST["service-id"]);
    $hostmap=json_decode(base64_decode($_POST["le-hostmap"]),true);
    if(!is_array($hostmap)){$hostmap=array();}
    $sel=array();
    foreach($hostmap as $md=>$host){
        $sel[$host]=(isset($_POST["le_inc_$md"]) && intval($_POST["le_inc_$md"])==1) ? 1 : 0;
    }
    $_SESSION["LE_SEL_$serviceid"]=$sel;
    return true;
}

// Adds a 302 redirect (domain>primary) for each excluded server name, reusing the
// existing nginx_services.hosts "domain>redirect" machinery. The bare primary name
// (no scheme) makes the daemon emit `return 302 $scheme://primary$request_uri;`
// (ServersBlockRedirects.go), preserving scheme + path.
function le_setup_redirects($serviceid,$excluded,$primary):bool{
    $primary=trim(strtolower($primary));
    if($primary==""||count($excluded)==0){return true;}
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/ID/$serviceid");
    $data=json_decode($resp,true);
    $hosts=(is_array($data) && isset($data["rows"][0]["hosts"])) ? strval($data["rows"][0]["hosts"]) : "";
    $MAIN=array();
    foreach(explode("||",$hosts) as $d){
        $d=trim(strtolower($d));
        if($d==""){continue;}
        if(preg_match("#^(.+?)>(.+)#",$d,$re)){$MAIN[$re[1]]=$re[2];continue;}
        $MAIN[$d]="";
    }
    foreach($excluded as $h){
        $h=trim(strtolower($h));
        if($h==""||$h==$primary){continue;}
        $MAIN[$h]=$primary;
    }
    $F=array();
    foreach($MAIN as $d=>$r){
        $d=trim(strtolower($d));
        if($d==""){continue;}
        if(strpos($d,";")>0){continue;}
        if($r<>null && $r!=$d){$F[]="$d>$r";}else{$F[]=$d;}
    }
    $newval=trim(implode("||",$F));
    $result=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON("/site/$serviceid/update-service",["hosts"=>$newval,"goodconftime"=>0]),true);
    if(is_array($result) && !empty($result["Status"])){
        $GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/reverse-proxy/singlehup/$serviceid");
        return true;
    }
    return false;
}

function ExtractHost($ID):array{
    $hosts=fetch_nginx_service_hosts($ID);
    $Zhosts=explode("||",$hosts);
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
function certificate_wizard2():bool{
    $tpl=new template_admin();
    $serviceid=intval($_GET["service-id"]);
    $function=$_GET["function"];
    $page=CurrentPageName();
    $html[]="<div id='certificate-progress-$serviceid'></div>";
    $form[]=$tpl->field_hidden("service-id",$serviceid);
    $form[]=$tpl->field_email("email","{email}","",true);
    $html[]= $tpl->form_outside("",$form,"{letsencryptval2}","{create_certificate}","Loadjs('$page?certificate-wiz3=true&service-id=$serviceid&function=$function')");
    echo $tpl->_ENGINE_parse_body(implode("\n",$html));
    return true;
}

function certificate_wizard3():bool{
    $tpl=new template_admin();
    $encoded=$_SESSION["AUTOLETSENCRYPT"];
    $serviceid=intval($_GET["service-id"]);
    $function=$_GET["function"];
    $sfunc="";
    if(strlen($function)>0){$sfunc="$function();";}

    $js=$tpl->framework_buildjs("/reverse-proxy/letsencrypt/$encoded",
    "reverseProxyCreateLetsEncrypt.progress",
        "reverseProxyCreateLetsEncrypt.log",
        "certificate-progress-$serviceid",
        "dialogInstance5.close();$sfunc");

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
    // Per-name selection from wizard 1 (session). Absent => include all valid hosts
    // (backward compatible). Unchecked valid hosts are excluded from the SAN list and
    // get a 302 redirect to the primary (first included) name.
    $sel=isset($_SESSION["LE_SEL_".$array["serviceid"]]) ? $_SESSION["LE_SEL_".$array["serviceid"]] : array();
    $hasSel=is_array($sel) && count($sel)>0;
    $domains=array();
    $excluded=array();
    foreach ($Hosts as $host=>$val){
        if(!$val){
            continue;
        }
        $inc = $hasSel ? (isset($sel[$host]) ? ($sel[$host]==1) : true) : true;
        if($inc){
            $domains[]=$host;
        }else{
            $excluded[]=$host;
        }
    }
    if(count($domains)==0){
        echo $tpl->post_error($tpl->_ENGINE_parse_body("{no_domain_valid}"));
        return false;
    }
    if(count($excluded)>0){
        le_setup_redirects($array["serviceid"],$excluded,$domains[0]);
    }
    $servicename=get_servicename($array["serviceid"]);
    $array["servicename"]=$servicename;
    $array["domains"]=@implode(",",$domains);
    $encoded=urlencode(base64_encode(serialize($array)));
    $_SESSION["AUTOLETSENCRYPT"]=$encoded;
    unset($_SESSION["LE_SEL_".$array["serviceid"]]);
    return admin_tracks("Create Let`s Encrypt certificate for $servicename");

}