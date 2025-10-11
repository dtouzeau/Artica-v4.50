<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["cert-download"])){cert_download();exit;}
if(isset($_GET["proxy-ssl-status-top"])){top_status();exit;}
if(isset($_POST["sslcrtd_program_dbsize"])){Save();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-flat"])){table_flat();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["form-js"])){form_js();exit;}
if(isset($_GET["form-popup"])){form_popup();exit;}
if(isset($_GET["powershell-js"])){powershell_js();exit;}
if(isset($_GET["powershell-popup"])){powershell_popup();exit;}
if(isset($_GET["powershell-download"])){powershell_download();exit;}
if(isset($_GET["verifypeer-js"])){verifypeer_js();exit;}
if(isset($_GET["verifypeer-popup"])){verifypeer_popup();exit;}
if(isset($_POST["SQUID_DONT_VERIFY_PEER"])){verifypeer_save();exit;}
page();


function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{ssl_protocol}&nbsp;&raquo;&nbsp;{status}"
        ,"fas fa-file-certificate","{ssl_protocol_https_explain}","$page?tabs=yes","proxy-ssl",
    "progress-ssl-restart",false,"table-acls-ssl-status");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin(null,$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function verifypeer_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{SQUID_DONT_VERIFY_PEER}","$page?verifypeer-popup=yes",650);
    return true;
}
function verifypeer_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $SQUID_DONT_VERIFY_PEER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_DONT_VERIFY_PEER"));

    $reconfigure_js=$tpl->framework_buildjs(
        "/proxy/ssl/build",
        "squid.ssl.rules.articarest.progress",
        "squid.ssl.rules.progress.log",
        "progress-acls-restart",
        "dialogInstance1.close();"
    );


    $form[]=$tpl->field_checkbox("SQUID_DONT_VERIFY_PEER","{enable}",$SQUID_DONT_VERIFY_PEER);
    $html[]=$tpl->form_outside(null, @implode("\n", $form),"{SQUID_DONT_VERIFY_PEER_EXPLAIN}","{apply}","LoadAjaxSilent('table-flat','$page?table-flat=yes');$reconfigure_js","AsSquidAdministrator");


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function verifypeer_save():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUID_DONT_VERIFY_PEER",$_POST["SQUID_DONT_VERIFY_PEER"]);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/ssl/build");
    return admin_tracks("Save Proxy Not verify the origin server’s SSL certificate to {$_POST["SQUID_DONT_VERIFY_PEER"]}");
}

function powershell_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{cert_deploy_powershell}","$page?powershell-popup=yes",850);
    return true;
}
function powershell_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html[]="<table id='table-hotspot-sessions-list' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{listen_port}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' nowrap>{certificates}</th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>crt</th>";
    $html[]="<th data-sortable=true class='text-capitalize center'>ps1</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $q2=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $results=$q->QUERY_SQL("SELECT * FROM proxy_ports WHERE UseSSL=1 AND enabled=1");
    $TRCLASS=null;
    foreach ($results as $index=>$ligne){
        $sslcertificate=$ligne["sslcertificate"];
        $sslcertificateenc=urlencode($sslcertificate);
        $download=$tpl->icon_download("document.location.href='$page?powershell-download=$sslcertificateenc'","AsProxyMonitor");

        $download_cert=$tpl->icon_download("document.location.href='$page?cert-download=$sslcertificateenc'","AsProxyMonitor");


        $ligne2=$q2->mysqli_fetch_array("SELECT pks12 FROM sslcertificates WHERE CommonName='$sslcertificate'");
        if(strlen($ligne2["pks12"])<90){
            $download=$tpl->icon_download(null);
        }

        $port=$ligne["port"];
        $nic=$ligne["nic"];
        if($nic==null){$nic="*";}
        $PortName=$ligne["PortName"];
        $md=md5(serialize($ligne));

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width=90% nowrap>$PortName ($nic:$port)</td>";
        $html[]="<td width=1% nowrap><i class='". ico_certificate."'></i>&nbsp;$sslcertificate</td>";
        $html[]="<td width=1% class='center' nowrap>$download_cert</td>";
        $html[]="<td width=1% class='center' nowrap>$download</td>";

        $html[]="</tr>";

    }

    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function cert_download():bool{
    $certificate=$_GET["cert-download"];
    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $sql="SELECT * FROM sslcertificates WHERE CommonName='$certificate'";
    $ligne=$q->mysqli_fetch_array($sql);
    $CommonName=$ligne["CommonName"];
    $field="privkey";
    $field_cert="crt";
    if(!$q->ok){echo $q->mysql_error_html(true);}
    if(intval($ligne["UsePrivKeyCrt"])==0){
        $field="Squidkey";
        $field_cert="SquidCert";

        if(strlen($ligne[$field])<10){
            if(strlen($ligne["privkey"])>10){$field="privkey";}
        }

    }

    $ligne[$field]=str_replace("\\n", "\n", $ligne[$field]);
    $private_key_content=trim($ligne[$field]);
    $ligne[$field_cert]=str_replace("\\n", "\n", $ligne[$field_cert]);
    $certificate_content=trim($ligne[$field_cert]);
    $final_content=$certificate_content."\r\n$private_key_content\r\n";

    $fsize=strlen($final_content);
    $content_type="application/x-x509-ca-cert";
    header('Content-type: '.$content_type);
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$CommonName.crt\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    echo $final_content;
    return true;

}

function powershell_download():bool{

    $certificate=$_GET["powershell-download"];

    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $sql="SELECT * FROM sslcertificates WHERE CommonName='$certificate'";
    $ligne=$q->mysqli_fetch_array($sql);
    $CommonName=$ligne["CommonName"];
    $field="privkey";
    $field_cert="crt";
    if(!$q->ok){echo $q->mysql_error_html(true);}
    if(intval($ligne["UsePrivKeyCrt"])==0){
        $field="Squidkey";
        $field_cert="SquidCert";

        if(strlen($ligne[$field])<10){
            if(strlen($ligne["privkey"])>10){$field="privkey";}
        }

    }

    $ligne[$field]=str_replace("\\n", "\n", $ligne[$field]);
    $private_key_content=trim($ligne[$field]);
    $ligne[$field_cert]=str_replace("\\n", "\n", $ligne[$field_cert]);
    $certificate_content=trim($ligne[$field_cert]);

    $final_content=base64_encode($certificate_content."\n$private_key_content\n");



    $f[]="\$pfxCertificate = \"$final_content\"";
    $f[]="Write-Output \"Decoding certificate\"";
    $f[]="\$PemDecodedCertificate = [Text.Encoding]::Utf8.GetString([Convert]::FromBase64String(\$pfxCertificate))";
    $f[]="\$tempPath = \$env:TEMP";
    $f[]="\$username = \$env:USERNAME";
    $f[]="\$pfx=\"\$tempPath\\$CommonName.pem\"";
    $f[]="\$firefoxProfile=\"C:\Users\\\$username\AppData\Roaming\Mozilla\Firefox\Profiles\"";
    $f[]="";
    $f[]="try {";
    $f[]="    Write-Output \"Writing certificate to \$pfx for \$username\"";
    $f[]="    Set-Content -Path \$pfx -Value \$PemDecodedCertificate";
    $f[]="} catch {";
    $f[]="    Write-Output \"Writing certificate to \$pfx failed, aborting\"";
    $f[]="    exit";
    $f[]="}";
    $f[]="";
    $f[]="if (!(Test-Path \$pfx )) {";
    $f[]="     Write-Output \"\$pfx permission denied\"";
    $f[]="    exit";
    $f[]="}";
    $f[]="try {";
    $f[]="    Write-Output \"Importing \$pfx\"";
    $f[]="    Import-Certificate -FilePath \$pfx -CertStoreLocation cert:\CurrentUser\Root";
    $f[]="} catch {  ";
    $f[]="    Remove-Item -Path \$pfx";
    $f[]="    Write-Output \"importing failed ( see above)\"";
    $f[]="    Write-Output \$Error[0].ToString()";
    $f[]="    exit";
    $f[]="    }";
    $f[]="";
    $f[]="if (Test-Path -Path \$firefoxProfile) {";
    $f[]="    Write-Output \"Patching Firefox...\"";
    $f[]="    Write-Output \"Stopping Firefox, if exists...\"";
    $f[]="    Get-Process | %{ if (\$_.Name -match 'firefox') { Stop-Process \$_.Id -Force} }";
    $f[]="    Get-ChildItem -Path \"\$firefoxProfile\*\" -Include prefs.js -Recurse -Depth 1 | %{ Add-Content -LiteralPath \$_ -Value 'user_pref(\"security.enterprise_roots.enabled\", true);' }";
    $f[]="}";
    $f[]="";
    $f[]="";
    $f[]="";
    $f[]="Remove-Item -Path \$pfx";
    $f[]="\$Success=\"Success importing certificate\"";
    $f[]="Write-Output \"\$Success\"";
    $final_content=@implode("\r\n",$f);
    $fsize=strlen($final_content);
    $content_type=" text/plain";
    header('Content-type: '.$content_type);
    header('Content-Transfer-Encoding: binary');
    header("Content-Disposition: attachment; filename=\"$CommonName.ps1\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    echo $final_content;
    return true;
}


function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $SSLCount=0;
    $sock=new sockets();
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $sql="SELECT count(*) as tcount FROM proxy_ports WHERE enabled=1 AND UseSSL=1";
    $ligne=$q->mysqli_fetch_array($sql);
    $SSLCount=intval($ligne["tcount"]);

    $sql="SELECT * FROM transparent_ports WHERE enabled=1";
    $results=$q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        if($ligne["sslcertificate"]<>null){$SSLCount++;}
    }

    $HaClusterClient                = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){
        $HaClusterGBConfig=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
        if(intval($HaClusterGBConfig["HaClusterDecryptSSL"])==1){
            $SSLCount++;
        }
    }
    $SquidMikrotikEnabled       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMikrotikEnabled"));
    if($SquidMikrotikEnabled==1){
        $SSLCount++;
    }

    if($SSLCount==0){
        $html[]=$tpl->FATAL_ERROR_SHOW_128("{no_sslport_defined}");
        $html[]="<div style='padding-left:10px;margin-top:30px'>";
        $html[]=$tpl->button_autnonome("{activate_ssl_decryption}","Loadjs('fw.proxy.ports.ssl.php')",ico_wizard,"AsSquidAdministrator",550,"btn-primary",125);
        $html[]="</div>";
        echo $tpl->_ENGINE_parse_body($html);
        return;
    }



    $array["{status}"]="$page?table=yes";
    $array["{ssl_rules}"]="fw.proxy.ssl.rules.php";
    $array["{certificate_validation}"]="fw.proxy.sslproxy_cert_error.php";
    $array["{ssl_whitelist}"]="fw.proxy.ssl.whitelist.php";
    $array["{certificates}"]="fw.proxy.ssl.certificates.php";
    echo $tpl->tabs_default($array);

}
function top_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $sock=new sockets();
    $data=json_decode($sock->REST_API("/proxy/ssldb"));

    if(!$data->Status){
        $status=$tpl->widget_h("red","fa-heart","{error}",$data->Error);
        $status2= $tpl->widget_h("grey","fa-certificate","0 {certificate}","{number_of_generated_certificates}");
    }else{
        $DATA=unserialize($data->Content);
        $status=$tpl->widget_h("green","fa-heart","{$DATA["DAEMONS_NUMBER"]} {processes}","{memory}: ".FormatBytes($DATA["MEMORY"]));
        $status2= $tpl->widget_h("green","fa-certificate","{$DATA["CERTSNUMBER"]} {certificates}","{number_of_generated_certificates}");
    }

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:33%;padding:10px'>";
    $html[]=$status;
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;width:33%;padding:10px'>";
    $html[]=$status2;
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{global_parameters}","$page?form-popup=yes");
    return true;
}

function form_popup():bool{

    $page=CurrentPageName();
    $tpl=new template_admin();

    $SquidSSLUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSSLUrgency"));
    if($SquidSSLUrgency==1){echo $tpl->FATAL_ERROR_SHOW_128("{proxy_in_ssl_emergency_mode}");}


    $reconfigure_js=$tpl->framework_buildjs(
        "/proxy/ssl/build",
        "squid.ssl.rules.articarest.progress",
        "squid.ssl.rules.progress.log",
        "progress-acls-restart",
        "dialogInstance1.close();"
    );

    $reconfigure_js="LoadAjaxSilent('table-flat','$page?table-flat=yes');$reconfigure_js";

    $sslproxy_versions[1]="{default}";
    $sslproxy_versions[2]="SSLv2 {only}";
    $sslproxy_versions[3]="SSLv3 {only}";
    $sslproxy_versions[4]="TLSv1.0 {only}";
    $sslproxy_versions[5]="TLSv1.1 {only}";
    $sslproxy_versions[6]="TLSv1.2 {only}";

    $sslproxy_version=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslproxy_version"));
    $sslcrtd_program_in_memory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslcrtd_program_in_memory"));
    $sslcrtd_program_dbsize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslcrtd_program_dbsize"));
    $sslcrtd_disable_cache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslcrtd_disable_cache"));
    $sslcrtd_disable_bump_error_page=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslcrtd_disable_bump_error_page"));
    $SslCrtdChildrens=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SslCrtdChildrens"));
    $SslCrtdQueueSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SslCrtdQueueSize"));
    if($SslCrtdChildrens==0){$SslCrtdChildrens=32;}
    if($SslCrtdQueueSize==0){$SslCrtdQueueSize=$SslCrtdChildrens*2;}


    if($sslproxy_version==0){$sslproxy_version=1;}
    if($sslcrtd_program_dbsize==0){$sslcrtd_program_dbsize=8;}

    $on_unsupported_protocol=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("on_unsupported_protocol"));


    $form[]=$tpl->field_checkbox("on_unsupported_protocol","{on_unsupported_protocol}",
        $on_unsupported_protocol,false,"{on_unsupported_protocol_text}");
    $form[]=$tpl->field_array_hash($sslproxy_versions, "sslproxy_version", "{sslproxy_version}", $sslproxy_version);
    $form[]=$tpl->field_numeric("sslcrtd_program_dbsize","{sslcrtd_program_dbsize} (MB)",$sslcrtd_program_dbsize);
    $form[]=$tpl->field_checkbox("sslcrtd_program_in_memory","{sslcrtd_program_in_memory}",
        $sslcrtd_program_in_memory,false);
    $form[]=$tpl->field_checkbox("sslcrtd_disable_cache","{sslcrtd_disable_cache}",
        $sslcrtd_disable_cache,false);
    $form[]=$tpl->field_checkbox("sslcrtd_disable_bump_error_page","{sslcrtd_disable_bump_error_page}",
        $sslcrtd_disable_bump_error_page,false);
    $form[]=$tpl->field_section("{performance}");
    $form[]=$tpl->field_numeric("SslCrtdChildrens","{max_processes}",$SslCrtdChildrens);
    $form[]=$tpl->field_numeric("SslCrtdQueueSize","{queue_size}",$SslCrtdQueueSize,"{squid_externl_queue_size}");




    $html[]="<div id='progress-acls-restart'></div>";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,"{apply}",$reconfigure_js,"AsSquidAdministrator");


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table():bool{
    $page=CurrentPageName();
    echo "<div id='table-flat'></div>";
    // LoadAjaxSilent('table-flat','fw.proxy.ssl.status.php?table-flat=yes');
    echo "<script>LoadAjaxSilent('table-flat','$page?table-flat=yes');</script>";
    return true;
}

function table_flat():bool{
    $addon=null;
    $page=CurrentPageName();
    $tpl=new template_admin();


    $SquidSSLUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSSLUrgency"));
    if($SquidSSLUrgency==1){echo $tpl->FATAL_ERROR_SHOW_128("{proxy_in_ssl_emergency_mode}");}



    $sslproxy_versions[1]="{default}";
    $sslproxy_versions[2]="SSLv2 {only}";
    $sslproxy_versions[3]="SSLv3 {only}";
    $sslproxy_versions[4]="TLSv1.0 {only}";
    $sslproxy_versions[5]="TLSv1.1 {only}";
    $sslproxy_versions[6]="TLSv1.2 {only}";

    $sslproxy_version=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslproxy_version"));
    $sslcrtd_program_in_memory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslcrtd_program_in_memory"));
    $sslcrtd_program_dbsize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslcrtd_program_dbsize"));
    $sslcrtd_disable_cache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslcrtd_disable_cache"));
    $sslcrtd_disable_bump_error_page=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sslcrtd_disable_bump_error_page"));
    $SslCrtdChildrens=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SslCrtdChildrens"));
    $SslCrtdQueueSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SslCrtdQueueSize"));
    if($SslCrtdChildrens==0){$SslCrtdChildrens=32;}
    if($SslCrtdQueueSize==0){$SslCrtdQueueSize=$SslCrtdChildrens*2;}


    if($sslproxy_version==0){$sslproxy_version=1;}
    if($sslcrtd_program_dbsize==0){$sslcrtd_program_dbsize=8;}

    $on_unsupported_protocol=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("on_unsupported_protocol"));


    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM proxy_ports WHERE UseSSL=1 AND enabled=1");
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $sslcertificate=$ligne["sslcertificate"];
        $port=$ligne["port"];
        $nic=$ligne["nic"];
        $PortName=$ligne["PortName"];
        if($nic==null){$nic="*";}
        $tpl->table_form_field_js("Loadjs('fw.proxy.ports.php?port-js=$ID');");
        $tpl->table_form_field_text("{listen_port} $nic:$port","$PortName - $sslcertificate",ico_certificate);
    }


if($sslcrtd_disable_cache==0) {
    if ($sslcrtd_program_in_memory == 1) {
        $text_ssldatabase[] = "{use_memory}";
    }
    $text_ssldatabase[] = "{$sslcrtd_program_dbsize}MB";

}else{
    $text_ssldatabase[]="{disabled}";
}

    $text_ssldatabase[] = " ({children} $SslCrtdChildrens {queue_size} $SslCrtdQueueSize)";





    $tpl->table_form_field_js("Loadjs('$page?powershell-js=yes')");
    $tpl->table_form_field_button("{cert_deploy}","{certs_list}",ico_computer_down);
    $tpl->table_form_field_js("Loadjs('$page?form-js=yes')");
    $tpl->table_form_field_bool("{non_http_protocol}",$on_unsupported_protocol,ico_proto);

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM sslproxy_cafile");
    $tcount=intval($ligne["tcount"]);
    if($tcount==0){
        $tpl->table_form_field_js("Loadjs('fw.proxy.ssl.trusted.php')");
        $tpl->table_form_field_bool("{trusted_certificates}",0,ico_ssl);
    }else{
        $tpl->table_form_field_js("Loadjs('fw.proxy.ssl.trusted.php')");
        $tpl->table_form_field_text("{trusted_certificates}","$tcount {certificates}",ico_ssl);
    }

    $tpl->table_form_field_js("Loadjs('$page?verifypeer-js=yes')");
    $SQUID_DONT_VERIFY_PEER=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUID_DONT_VERIFY_PEER"));
    $tpl->table_form_field_bool("{SQUID_DONT_VERIFY_PEER}",$SQUID_DONT_VERIFY_PEER,ico_ssl);

    $tpl->table_form_field_js("Loadjs('$page?form-js=yes')");
    $tpl->table_form_field_text("{sslproxy_version}",$sslproxy_versions[$sslproxy_version],ico_certificate);
    $tpl->table_form_field_text("{ssl_database}",@implode(" ",$text_ssldatabase),ico_database);

    $btn[]="<div class=\"btn-group\" data-toggle=\"buttons\">";
    if($SquidSSLUrgency==1) {
        $btn[] = "<label class=\"btn btn btn-danger\" OnClick=\"Loadjs('fw.proxy.ssl-emergency.remove.php')\"><i class='" . ico_emergency . "'></i> {disable_emergency_mode} </label>";
    }else{
        $btn[] = "<label class=\"btn btn btn-default\" OnClick=\"Loadjs('fw.proxy.ssl-emergency.enable.php')\"><i class='" . ico_emergency . "'></i> {enable_emergency_mode} </label>";

    }
    $btn[] = "</div>";
    $TINY_ARRAY["TITLE"]="{ssl_protocol}&nbsp;&raquo;&nbsp;{status}";
    $TINY_ARRAY["ICO"]="fas fa-file-certificate";
    $TINY_ARRAY["EXPL"]="{ssl_protocol_https_explain}";
    $TINY_ARRAY["URL"]="proxy-ssl";
    $TINY_ARRAY["BUTTONS"]=@implode("\n",$btn);


    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<div id='proxy-ssl-status-top'></div>";
    $html[]=$tpl->table_form_compile();
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="LoadAjax('proxy-ssl-status-top','$page?proxy-ssl-status-top=yes');";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Save():bool{
    $tpl=new template_admin();
    if(intval($_POST['sslcrtd_disable_cache'])==1){
        $_POST['sslcrtd_program_in_memory']=0;
    }else{
        $_POST['sslcrtd_program_in_memory']=1;
    }

    $tpl->SAVE_POSTs();
    return true;
}

