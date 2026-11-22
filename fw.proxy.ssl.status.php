<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
// Verrou Artica Meta (politique sslproxy) : reglages tenus en lecture seule
include_once(dirname(__FILE__)."/ressources/class.sslproxy.meta.inc");
// awc_table_style() / awc_pager_js(): feuille de style et pager partages des tableaux d'entites
include_once(dirname(__FILE__)."/ressources/class.statistics_appliance.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["cert-download"])){cert_download();exit;}
if(isset($_POST["sst-toggle"])){sst_toggle_save();exit;}
// sslproxy_version : toujours poste par ce formulaire (hors catalogue Meta), meme quand
// sslcrtd_program_dbsize est tenu par Meta (lecture seule, non poste)
if(isset($_POST["sslcrtd_program_dbsize"]) || isset($_POST["sslproxy_version"])){Save();exit;}
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


    if(sslproxy_meta_key_held("SQUID_DONT_VERIFY_PEER")){sslproxy_meta_lock_form($tpl);}
    $form[]=sslproxy_meta_fld($tpl,"field_checkbox","SQUID_DONT_VERIFY_PEER","{enable}",$SQUID_DONT_VERIFY_PEER);
    $html[]=$tpl->form_outside(null, @implode("\n", $form),"{SQUID_DONT_VERIFY_PEER_EXPLAIN}","{apply}","LoadAjaxSilent('table-flat','$page?table-flat=yes');$reconfigure_js","AsSquidAdministrator");


    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function verifypeer_save():bool{
    if(sslproxy_meta_key_held("SQUID_DONT_VERIFY_PEER")){return sslproxy_meta_refuse_post();}
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
    // L'ancienne version fermait </table> sans avoir ferme </tbody>, et
    // l'identifiant du tableau ("table-hotspot-sessions-list") etait recopie
    // d'une page de sessions hotspot sans rapport.
    $html[]="<div class='awc-wrap'><div class='awc-card'><div class='awc-scroll'>";
    $html[]="<table class='awc-tbl'><thead><tr>";
    $html[]="<th>{listen_port}</th>";
    $html[]="<th>{certificates}</th>";
    $html[]="<th class='center'>crt</th>";
    $html[]="<th class='center'>ps1</th>";
    $html[]="</tr></thead><tbody id='ps-rows'>";

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $inService=array();
    $results=$q->QUERY_SQL("SELECT * FROM proxy_ports WHERE UseSSL=1 AND enabled=1");
    // lib_sqlite::QUERY_SQL rend FALSE en cas d'erreur: foreach dessus leve une
    // TypeError en PHP 8.
    if(!is_array($results)){$results=array();}
    $c=0;
    foreach ($results as $index=>$ligne){
        $c++;
        $sslcertificate=(string)$ligne["sslcertificate"];
        $sslcertificateenc=urlencode($sslcertificate);
        $download=$tpl->icon_download("document.location.href='$page?powershell-download=$sslcertificateenc'","AsProxyMonitor");

        $download_cert=$tpl->icon_download("document.location.href='$page?cert-download=$sslcertificateenc'","AsProxyMonitor");


        $certRow=$cdb->getByCommonName($sslcertificate);
        // Le script n'utilise que le certificat : plus besoin du PFX (pks12).
        if(!is_object($certRow)){
            $download=$tpl->icon_download(null);
        }
        $inService[strtolower($sslcertificate)]=true;

        $nic=(string)$ligne["nic"];
        if($nic==""){$nic="*";}

        // Le nom du port, l'interface et le nom du certificat viennent de
        // SQLite et partaient BRUTS dans le HTML.
        $html[]=ssl_port_row(md5(serialize($ligne)),
            htmlspecialchars((string)$ligne["PortName"]),
            htmlspecialchars($nic.":".(string)$ligne["port"]),
            htmlspecialchars($sslcertificate),
            $download_cert,$download);

    }
    // Autorites du Certificates Center qu'aucun port n'utilise encore : pour un
    // renouvellement, la nouvelle autorite doit etre deployee sur les postes AVANT de
    // basculer les ports, donc telechargeable avant d'etre en service.
    foreach ($cdb->listAll() as $row){
        $row=(array)$row;
        $cn=(string)($row["CommonName"] ?? "");
        if($cn=="" || isset($inService[strtolower($cn)])){continue;}
        // Ligne sans date d'expiration = sans certificat (ex. certificat d'usine vide).
        if(trim((string)($row["DateTo"] ?? ""))==""){continue;}
        if(intval($row["UseLetsEncrypt"] ?? 0)==1 || intval($row["IsClientCert"] ?? 0)==1 || intval($row["ServerCert"] ?? 0)==1){continue;}
        $c++;
        $enc=urlencode($cn);
        $dateTo=htmlspecialchars((string)($row["DateTo"] ?? ""));
        $html[]=ssl_port_row(md5("ca-$cn"),
            "<span class='label label-default'>{not_used}</span>",
            "{expire}: $dateTo",
            htmlspecialchars($cn),
            $tpl->icon_download("document.location.href='$page?cert-download=$enc'","AsProxyMonitor"),
            $tpl->icon_download("document.location.href='$page?powershell-download=$enc'","AsProxyMonitor"));
    }
    if($c==0){
        $html[]="<tr><td colspan='4' class='awc-empty'>{no_data}</td></tr>";
    }

    $html[]="</tbody></table></div>";
    $html[]="<div class='awc-foot'>";
    $html[]="<span>{showing} $c</span>";
    $html[]="<span class='awc-pg' id='ps-pager'></span>";
    $html[]="</div></div></div>";

    // Feuille de style, script et pager concatenes APRES l'analyse: la regex
    // #{(.+?)}# de _ENGINE_parse_body prend chaque corps d'accolade CSS ou
    // JavaScript pour un {token}.
    echo awc_table_style()
        .$tpl->_ENGINE_parse_body(@implode("\n",$html))
        ."<script>NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."</script>"
        .awc_pager_js(50,"ps-rows","ps-pager");
    return true;
}

function cert_download():bool{
    $certificate=$_GET["cert-download"];
    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $row=$cdb->getByCommonName($certificate);
    if(!is_object($row)){echo htmlspecialchars($cdb->lastError);return false;}
    $ligne=(array)$row;
    $CommonName=(string)($ligne["CommonName"] ?? "");
    // Ce fichier part sur TOUS les postes (GPO, import manuel) : il ne contient que le
    // certificat. Il embarquait aussi la cle privee de l'autorite, ce qui donnait a
    // quiconque le recuperait de quoi signer un faux certificat pour n'importe quel site
    // aupres de tous les postes du parc.
    $certificate_content=ssl_public_certificate($ligne);
    if($certificate_content==""){echo "No certificate";return false;}
    $final_content=$certificate_content."\r\n";

    $fsize=strlen($final_content);
    $content_type="application/x-x509-ca-cert";
    header('Content-type: '.$content_type);
    header('Content-Transfer-Encoding: binary');
    // Le nom commun partait BRUT dans un en-tete HTTP: un guillemet ou un
    // retour a la ligne dedans permet d'injecter un en-tete.
    $CommonNameSafe=preg_replace('#[^A-Za-z0-9._-]#','_',$CommonName);
    header("Content-Disposition: attachment; filename=\"$CommonNameSafe.crt\"");
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    header("Content-Length: ".$fsize);
    ob_clean();
    flush();
    echo $final_content;
    return true;

}

/**
 * Partie publique d'un certificat du Certificates Center : le(s) bloc(s) CERTIFICATE de la
 * colonne certificat, sans jamais la cle privee (meme si la colonne en contient une).
 */
function ssl_public_certificate(array $ligne):string{
    $field_cert="crt";
    if(intval($ligne["UsePrivKeyCrt"] ?? 0)==0){
        $field_cert="SquidCert";
        if(strlen((string)($ligne[$field_cert] ?? ""))<50 && strlen((string)($ligne["crt"] ?? ""))>50){$field_cert="crt";}
    }
    $pem=str_replace("\\n", "\n", (string)($ligne[$field_cert] ?? ""));
    if(!preg_match_all('#-----BEGIN CERTIFICATE-----.+?-----END CERTIFICATE-----#s',$pem,$m)){return "";}
    return trim($m[0][0]);
}

function powershell_download():bool{

    $certificate=$_GET["powershell-download"];

    include_once(dirname(__FILE__)."/ressources/class.certificates-db.inc");
    $cdb=new CertificatesDB();
    $row=$cdb->getByCommonName($certificate);
    if(!is_object($row)){echo htmlspecialchars($cdb->lastError);return false;}
    $ligne=(array)$row;
    $CommonName=(string)($ligne["CommonName"] ?? "");
    // Script execute sur chaque poste : certificat seul (meme raison que cert_download()).
    $certificate_content=ssl_public_certificate($ligne);
    if($certificate_content==""){echo "No certificate";return false;}
    $final_content=base64_encode($certificate_content."\n");
    // Le nom commun est repris dans le script et dans un en-tete HTTP : seuls des
    // caracteres sans signification pour PowerShell ou HTTP y passent.
    $CommonName=preg_replace('#[^A-Za-z0-9._-]#','_',$CommonName);



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


    $form[]=sslproxy_meta_fld($tpl,"field_checkbox","on_unsupported_protocol","{on_unsupported_protocol}",
        $on_unsupported_protocol,false,"{on_unsupported_protocol_text}");
    $form[]=$tpl->field_array_hash($sslproxy_versions, "sslproxy_version", "{sslproxy_version}", $sslproxy_version);
    $form[]=sslproxy_meta_fld($tpl,"field_numeric","sslcrtd_program_dbsize","{sslcrtd_program_dbsize} (MB)",$sslcrtd_program_dbsize);
    $form[]=sslproxy_meta_fld($tpl,"field_checkbox","sslcrtd_program_in_memory","{sslcrtd_program_in_memory}",
        $sslcrtd_program_in_memory,false);
    $form[]=sslproxy_meta_fld($tpl,"field_checkbox","sslcrtd_disable_cache","{sslcrtd_disable_cache}",
        $sslcrtd_disable_cache,false);
    $form[]=sslproxy_meta_fld($tpl,"field_checkbox","sslcrtd_disable_bump_error_page","{sslcrtd_disable_bump_error_page}",
        $sslcrtd_disable_bump_error_page,false);
    $form[]=$tpl->field_section("{performance}");
    $form[]=sslproxy_meta_fld($tpl,"field_numeric","SslCrtdChildrens","{max_processes}",$SslCrtdChildrens);
    $form[]=sslproxy_meta_fld($tpl,"field_numeric","SslCrtdQueueSize","{queue_size}",$SslCrtdQueueSize,"{squid_externl_queue_size}");




    $html[]="<div id='progress-acls-restart'></div>";
    $html[]=sslproxy_meta_banner($tpl,"","{sslproxy_meta_settings_explain}");
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

/**
 * Etat SSL du proxy (articarest GET /proxy/ssl/api/state, enveloppe
 * {success,data,error}). Tableau vide si le daemon ne repond pas.
 */
function sst_state():array{
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/ssl/api/state"),true);
    if(!is_array($json) || empty($json["success"]) || !isset($json["data"]) || !is_array($json["data"])){
        return array();
    }
    return $json["data"];
}

/** Accents de la marque (repli : les valeurs de serie). */
function sst_accents():array{
    $ac=array("accent"=>"#10b981","accent_dark"=>"#0d9488",
        "accent_soft"=>"rgba(16,185,129,.12)","accent_line"=>"rgba(16,185,129,.35)");
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $ac=brand_ui_accents(brand_palette());
    }
    return $ac;
}

function sst_css():string{
    $ac=sst_accents();
    $A=$ac["accent"]; $D=$ac["accent_dark"]; $S=$ac["accent_soft"]; $L=$ac["accent_line"];
    $f[]="<style>";
    $f[]=".sst-page{color:#1e293b;padding:6px 2px}";
    $f[]=".sst-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:24px}";
    $f[]=".sst-kpi{padding:18px 20px}";
    $f[]=".sst-kpi.sst-kpi-bad{background:#fff1f2;border-color:#fda4af;box-shadow:0 0 0 1px #fecdd3}";
    $f[]=".sst-kpi-bad .sst-kpi-l{color:#be123c}.sst-kpi-bad .sst-kpi-r{color:#9f1239}.sst-kpi-bad .sst-kpi-r .sst-ok{color:#e11d48}";
    $f[]=".sst-incompat{display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:3px 10px;border-radius:6px;background:#e11d48;color:#fff;font-size:12px;font-weight:700}";
    $f[]=".sst-kpi-h{display:flex;align-items:center;justify-content:space-between;gap:8px}";
    $f[]=".sst-kpi-l{font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#64748b}";
    $f[]=".sst-ib{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:9px;font-size:14px;flex:0 0 32px}";
    $f[]=".sst-t-green{background:$S;color:$D}.sst-t-blue{background:#eff6ff;color:#2563eb}.sst-t-indigo{background:#e6eeed;color:#005447}";
    $f[]=".sst-t-teal{background:#f0fdfa;color:#0d9488}.sst-t-amber{background:#fffbeb;color:#d97706}.sst-t-rose{background:#fff1f2;color:#e11d48}";
    $f[]=".sst-t-violet{background:#f2f6f6;color:#005447}.sst-t-slate{background:#f1f5f9;color:#334155}";
    $f[]=".sst-kpi-v{display:flex;align-items:baseline;gap:8px;margin-top:12px;min-width:0}";
    $f[]=".sst-big{font-size:26px;font-weight:800;color:#0f172a;line-height:30px;white-space:nowrap}";
    $f[]=".sst-big.sst-name{font-size:19px;font-weight:700;overflow:hidden;text-overflow:ellipsis}";
    $f[]=".sst-unit{font-size:13px;font-weight:600;color:$D}.sst-unit.sst-muted{color:#64748b}.sst-unit.sst-bad{color:#e11d48}.sst-unit.sst-warn{color:#d97706}";
    $f[]=".sst-kpi-r{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:10px;font-size:12px;color:#64748b}";
    $f[]=".sst-kpi-r strong{color:#334155}.sst-kpi-r .sst-ok{color:$D;font-weight:600}.sst-kpi-r .sst-ko{color:#e11d48;font-weight:600}";
    $f[]=".sst-kpi-r span{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}";
    $f[]=".sst-bar{display:flex;width:100%;height:6px;background:#f1f5f9;border-radius:99px;margin-top:6px;overflow:hidden}";
    $f[]=".sst-bar span{display:block;height:6px}";
    $f[]=".sst-mono{font-family:monospace;font-size:11px;color:#94a3b8}";
    // Jetons partages en minuscules ({purge}, {default}) : majuscule initiale.
    $f[]=".sst-cap{display:inline-block}.sst-cap::first-letter{text-transform:uppercase}";
    $f[]=".sst-link{color:#2563eb;font-weight:600;font-size:11px;cursor:pointer}.sst-link:hover{text-decoration:underline}";
    $f[]=".sst-sec-h{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 24px;background:#f8fafc;border-bottom:1px solid #e2e8f0}";
    $f[]=".sst-sec-t{font-size:16px;font-weight:700;color:#0f172a}.sst-sec-e{font-size:13px;color:#64748b;margin-top:2px}";
    $f[]=".ak-btn.sst-btn-acc{background:$S;border-color:$L;color:$D}.ak-btn.sst-btn-acc:hover{border-color:$A}";
    $f[]=".sst-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;padding:24px;background:#fbfcfe}";
    $f[]=".sst-card{display:flex;flex-direction:column;justify-content:space-between;gap:14px;padding:20px}";
    $f[]=".sst-card-top{display:flex;gap:14px;align-items:flex-start;min-width:0}";
    $f[]=".sst-card .sst-ib{width:40px;height:40px;flex:0 0 40px;border-radius:11px;font-size:17px}";
    $f[]=".sst-card-h{display:flex;flex-wrap:wrap;align-items:center;gap:8px}";
    $f[]=".sst-card-t{font-size:15px;font-weight:700;color:#0f172a}";
    $f[]=".sst-card-e{font-size:13px;color:#64748b;margin-top:4px;line-height:1.45}";
    $f[]=".sst-card-x{font-size:12px;color:#64748b;margin-top:8px;display:flex;flex-wrap:wrap;gap:6px 12px;align-items:center}";
    $f[]=".sst-foot{display:flex;align-items:center;justify-content:space-between;gap:10px;padding-top:12px;border-top:1px solid #f1f5f9}";
    $f[]=".sst-foot-l{display:flex;align-items:center;gap:10px;flex-wrap:wrap;min-width:0}";
    $f[]=".sst-sw-l{font-size:13px;font-weight:600;color:#94a3b8}.sst-sw-l.sst-on{color:$D}.sst-sw-l.sst-risk{color:#e11d48}";
    $f[]=".sst-sw{position:relative;display:inline-block;width:44px;height:24px;margin:0;cursor:pointer;vertical-align:middle}";
    $f[]=".sst-sw input{position:absolute;opacity:0;width:0;height:0}";
    $f[]=".sst-sw span{position:absolute;inset:0;background:#e2e8f0;border-radius:99px;transition:background .15s}";
    $f[]=".sst-sw span:after{content:'';position:absolute;left:3px;top:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}";
    $f[]=".sst-sw input:checked+span{background:$D}.sst-sw input:checked+span:after{left:23px}";
    $f[]=".sst-sw.sst-sw-risk input:checked+span{background:#e11d48}";
    $f[]=".sst-sw input:disabled+span{opacity:.5;cursor:not-allowed}";
    $f[]=".sst-status{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:18px;padding:10px 16px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;font-size:13px;color:#64748b}";
    $f[]=".sst-status strong{color:#334155}.sst-status .sst-sep{color:#cbd5e1;margin:0 8px}";
    $f[]=".sst-alert{margin-bottom:18px;font-weight:600}";
    $f[]="@media (max-width:1300px){.sst-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}";
    $f[]="@media (max-width:900px){.sst-grid{grid-template-columns:1fr}}";
    $f[]="@media (max-width:640px){.sst-kpis{grid-template-columns:1fr}.sst-grid{padding:14px}}";
    $f[]="</style>";
    return @implode("\n",$f);
}

/** Barre de progression : une liste de [pourcentage, couleur]. */
function sst_bar($parts):string{
    $h="<div class='sst-bar'>";
    foreach ($parts as $p){
        $w=max(0,min(100,floatval($p[0])));
        $h.="<span style='width:".sprintf("%.1f",$w)."%;background:{$p[1]}'></span>";
    }
    return $h."</div>";
}

/** Coquille commune des quatre indicateurs du haut. */
function sst_kpi_shell($label,$ico,$tone,$value,$right,$under,$cls=""):string{
    $h ="<div class='ak-card sst-kpi$cls'><div class='sst-kpi-h'><span class='sst-kpi-l'>$label</span>";
    $h.="<span class='sst-ib sst-t-$tone'><i class='$ico'></i></span></div>";
    $h.="<div class='sst-kpi-v'>$value$right</div>$under</div>";
    return $h;
}

/** Indicateur 1 : processus de signature (security_file_certgen) et leur memoire. */
function sst_kpi_engine($st):string{
    $ac=sst_accents();
    $c=$st["sslcrtd"] ?? array();
    $helpers=intval($c["helpers"] ?? 0);
    $max=max(1,intval($c["children"] ?? 32));
    $unitCls=$helpers>0?"":" sst-bad";
    $value="<span class='sst-big'>$helpers</span><span class='sst-unit$unitCls'>{processes}</span>";
    $under="<div class='sst-kpi-r'><span>{memory}: <strong>".FormatBytes(intval($c["memory_kb"] ?? 0),true)."</strong></span>";
    $under.="<span>{max_processes}: $max</span></div>";
    $under.=sst_bar(array(array($helpers*100/$max,$ac["accent"])));
    return sst_kpi_shell("{sslst_engine}","fas fa-microchip","green",$value,"",$under);
}

/** Indicateur 2 : certificats generes et occupation de la base sur disque. */
function sst_kpi_certs($st):string{
    $c=$st["sslcrtd"] ?? array();
    $certs=intval($c["certs"] ?? 0);
    $allocKB=max(1,intval($c["db_size_mb"] ?? 8))*1024;
    $usedKB=intval($c["db_used_kb"] ?? 0);
    $value="<span class='sst-big'>".number_format($certs,0,","," ")."</span><span class='sst-unit sst-muted'>{certificates}</span>";
    if(!empty($c["cache_disabled"])){
        $left="<span>{sslst_cache_off}</span>";
    }else{
        $left="<span>{sslst_disk_usage}: <strong>".FormatBytes($usedKB,true)."</strong></span>";
    }
    $under="<div class='sst-kpi-r'>$left<span>".FormatBytes($allocKB,true)." {sslst_allocated}</span></div>";
    $pct=$usedKB*100/$allocKB;
    $color=$pct>=90?"#e11d48":($pct>=70?"#f59e0b":"#3b82f6");
    $under.=sst_bar(array(array($pct,$color)));
    return sst_kpi_shell("{sslst_dynamic_certs}","fas fa-shield-check","blue",$value,"",$under);
}

/** Libelle et ton d'un code d'inspection (sslInspectionCode cote daemon). */
function sst_inspection_label($code):array{
    $map=array(
        "active"=>array("{sslst_state_active}",""),
        "squid_disabled"=>array("{sslst_state_squid_disabled}","sst-muted"),
        "emergency"=>array("{sslst_state_emergency}","sst-bad"),
        "emergency_pending"=>array("{sslst_state_emergency}","sst-warn"),
        "emergency_exit_pending"=>array("{sslst_state_emergency_exit}","sst-warn"),
        "no_ssl_port"=>array("{sslst_state_no_port}","sst-muted"),
        "config_rejected"=>array("{sslst_state_rejected}","sst-bad"),
        "helpers_down"=>array("{sslst_state_helpers_down}","sst-bad"),
    );
    if(isset($map[$code])){return $map[$code];}
    return array(htmlspecialchars((string)$code),"sst-muted");
}

/** Indicateur 3 : etat de l'inspection et ports qui dechiffrent reellement. */
function sst_kpi_inspection($st):string{
    $ports=$st["ports"] ?? array();
    $total=count($ports); $bump=0;
    foreach ($ports as $p){ if(!empty($p["bump"])){$bump++;} }
    list($label,$cls)=sst_inspection_label((string)($st["inspection"] ?? ""));
    $value="<span class='sst-big sst-name'><span class='sst-unit $cls' style='font-size:19px'>$label</span></span>";
    $under="<div class='sst-kpi-r'><span>{sslst_bump_ports}: <strong>$bump / $total</strong></span>";
    $under.="<span>{trusted_certificates}: ".intval($st["trusted_count"] ?? 0)."</span></div>";
    $pct=$total>0?$bump*100/$total:0;
    $under.=sst_bar(array(array($pct,"#005447"),array(100-$pct,"#fbbf24")));
    return sst_kpi_shell("{sslst_inspection}","fas fa-eye","indigo",$value,"",$under);
}

/** Premier certificat de signature charge par Squid (port bump). */
function sst_first_ca($st):array{
    foreach (($st["ports"] ?? array()) as $p){
        if(isset($p["ca"]) && is_array($p["ca"])){return array($p,$p["ca"]);}
    }
    return array(array(),array());
}

/** Indicateur 4 : autorite qui signe les certificats a la volee. */
function sst_kpi_ca($st):string{
    list($port,$ca)=sst_first_ca($st);
    if(count($ca)==0 || !empty($ca["error"])){
        $value="<span class='sst-big sst-name'>&mdash;</span>";
        $under="<div class='sst-kpi-r'><span>{sslst_no_ca}</span></div>";
        return sst_kpi_shell("{sslst_signing_ca}","fas fa-calendar-check","teal",$value,"",$under);
    }
    $name=htmlspecialchars((string)$ca["subject"]);
    $days=intval($ca["days_left"] ?? 0);
    $value="<span class='sst-big sst-name' title='$name'>$name</span>";
    $exp=date("d/m/Y",intval($ca["not_after"] ?? 0));
    if($days<0){
        $right="<span class='sst-ko'>{sslst_expired}</span>";
    }elseif($days<30){
        $right="<span class='sst-ko'>$days {sslst_days_left}</span>";
    }else{
        $right="<span class='sst-ok'>$days {sslst_days_left}</span>";
    }
    $sha=(string)($ca["sha256"] ?? "");
    $short=strlen($sha)>12?substr($sha,0,4)."...".substr($sha,-4):$sha;
    $under="<div class='sst-kpi-r'><span>{sslst_expires}: <strong>$exp</strong></span>$right</div>";
    $under.="<div class='sst-kpi-r' style='margin-top:6px'><span class='sst-mono' title='SHA256 $sha'>SHA256: $short &middot; ".htmlspecialchars((string)($ca["key_type"] ?? ""))."</span>";
    $id=intval($port["id"] ?? 0);
    if($id>0){
        $under.="<span class='sst-link' OnClick=\"Loadjs('fw.proxy.ports.php?port-js=$id');\">{renew}</span>";
    }
    $under.="</div>";
    // Squid signe les certificats generes avec ce certificat : s'il n'est pas
    // une autorite (basicConstraints CA:TRUE), les navigateurs les refusent.
    if(empty($ca["is_ca"])){
        $under.="<div class='sst-incompat' title='{sslst_cert_incompatible_explain}'><i class='fas fa-triangle-exclamation'></i> {sslst_cert_incompatible}</div>";
        return sst_kpi_shell("{sslst_signing_ca}","fas fa-calendar-xmark","rose",$value,"",$under," sst-kpi-bad");
    }
    return sst_kpi_shell("{sslst_signing_ca}","fas fa-calendar-check","teal",$value,"",$under);
}

/** Coquille commune des cartes de reglage. */
function sst_card($ico,$tone,$title,$badge,$explain,$extra,$footLeft,$footRight):string{
    $h ="<div class='ak-card sst-card'><div class='sst-card-top'>";
    $h.="<span class='sst-ib sst-t-$tone'><i class='$ico'></i></span>";
    $h.="<div style='min-width:0;flex:1'><div class='sst-card-h'><span class='sst-card-t'>$title</span>$badge</div>";
    $h.="<div class='sst-card-e'>$explain</div>$extra</div></div>";
    $h.="<div class='sst-foot'><div class='sst-foot-l'>$footLeft</div>$footRight</div></div>";
    return $h;
}

function sst_edit_btn($js,$title="{modify}"):string{
    return "<button type='button' class='ak-act' title='$title' OnClick=\"$js\"><i class='fas fa-pen-to-square'></i></button>";
}

/** Interrupteur qui enregistre aussitot (SstToggle) ; grise sans le privilege. */
function sst_switch($key,$value,$onLabel,$offLabel,$risk=false):string{
    $users=new usersMenus();
    $dis=$users->AsSquidAdministrator?"":" disabled";
    if(sslproxy_meta_key_held($key)){$dis=" disabled title=\"{sslproxy_meta_locked}\"";}
    $chk=$value==1?" checked":"";
    $lblCls=$value==1?($risk?" sst-risk":" sst-on"):"";
    $swCls=$risk?" sst-sw-risk":"";
    $label=$value==1?$onLabel:$offLabel;
    return "<span class='sst-sw-l$lblCls'>$label</span>"
        ."<label class='sst-sw$swCls'><input type='checkbox'$chk$dis onchange=\"SstToggle('$key',this)\"><span></span></label>";
}

/** Carte : un port d'ecoute SSL. */
function sst_card_port($p):string{
    $id=intval($p["id"] ?? 0);
    $nic=(string)($p["nic"] ?? "");
    if($nic==""){$nic="*";}
    $listen=htmlspecialchars($nic.":".intval($p["port"] ?? 0));
    $name=htmlspecialchars((string)($p["name"] ?? ""));
    $cert=htmlspecialchars((string)($p["certificate"] ?? ""));
    $badge="<span class='ak-chip ak-ok'>$listen</span>";
    $extra="<div class='sst-card-x'><span class='sst-mono' style='font-size:12px;color:#64748b'><i class='fas fa-check'></i> $name &middot; $cert</span></div>";
    if(!empty($p["bump"])){
        $pill="<span class='ak-pill ak-ok'><span class='ak-dot'></span>{sslst_operational}</span>";
    }else{
        $pill="<span class='ak-pill ak-err'><span class='ak-dot'></span>{sslst_not_loaded}</span>";
    }
    return sst_card("fas fa-terminal","green","{sslst_port_title}",$badge,"{sslst_port_explain}",$extra,$pill,
        sst_edit_btn("Loadjs('fw.proxy.ports.php?port-js=$id');"));
}

/** Carte : deploiement du certificat d'autorite sur les postes. */
function sst_card_deploy($st,$page):string{
    list($port,$ca)=sst_first_ca($st);
    $extra="";
    $right="";
    $cert=(string)($port["certificate"] ?? "");
    if($cert<>""){
        $enc=urlencode($cert);
        $extra="<div class='sst-card-x'><span class='sst-mono' style='font-size:12px'>{certificate}: ".htmlspecialchars($cert)."</span></div>";
        $right="<button type='button' class='ak-act' title='{download}' OnClick=\"document.location.href='$page?cert-download=$enc';\"><i class='fas fa-download'></i></button>";
    }
    $btn="<button type='button' class='ak-btn sst-btn-acc' OnClick=\"Loadjs('$page?powershell-js=yes');\"><i class='fas fa-list'></i> {certs_list}</button>";
    return sst_card("fas fa-display","blue","{sslst_deploy_title}","","{sslst_deploy_explain}",$extra,$btn,$right);
}

/** Carte : protocoles non HTTP (on_unsupported_protocol). */
function sst_card_nonhttp($st,$page):string{
    $v=intval($st["settings"]["on_unsupported_protocol"] ?? 0);
    $badge="<span class='ak-tag'>{recommended}</span>";
    return sst_card("fas fa-bolt","amber","{on_unsupported_protocol}",$badge,"{sslst_nonhttp_explain}","",
        sst_switch("on_unsupported_protocol",$v,"{sslst_active}","{sslst_inactive}"),
        sst_edit_btn("Loadjs('$page?form-js=yes');"));
}

/** Carte : autorites de confiance supplementaires. */
function sst_card_trusted($st):string{
    $n=intval($st["trusted_count"] ?? 0);
    if($n>0){
        $left="<span class='sst-sw-l sst-on'>$n {certificates}</span>";
    }else{
        $left="<span class='sst-sw-l'>{sslst_none}</span>";
    }
    return sst_card("fas fa-shield-halved","teal","{trusted_certificates}","","{sslst_trusted_explain}","",$left,
        sst_edit_btn("Loadjs('fw.proxy.ssl.trusted.php');"));
}

/** Carte : ne pas verifier le certificat du serveur d'origine. */
function sst_card_noverify($st,$page):string{
    $v=intval($st["settings"]["SQUID_DONT_VERIFY_PEER"] ?? 0);
    $badge="<span class='ak-tag ak-err'>{high_risk}</span>";
    return sst_card("fas fa-lock-open","rose","{SQUID_DONT_VERIFY_PEER}",$badge,"{sslst_noverify_explain}","",
        sst_switch("SQUID_DONT_VERIFY_PEER",$v,"{sslst_active}","{sslst_secure}",true),
        sst_edit_btn("Loadjs('$page?verifypeer-js=yes');"));
}

/** Carte : version TLS minimale vers les serveurs distants. */
function sst_card_tls($st,$page):string{
    $versions=array(1=>"{default}",2=>"SSLv2 {only}",3=>"SSLv3 {only}",4=>"TLSv1.0 {only}",5=>"TLSv1.1 {only}",6=>"TLSv1.2 {only}");
    $v=intval($st["sslproxy_version"]["value"] ?? 0);
    if($v==0){$v=1;}
    $label=$versions[$v] ?? "{default}";
    $out=$st["outgoing"] ?? array();
    $extra="";
    $opts=array();
    if(!empty($out["min_version"])){$opts[]="min-version=".htmlspecialchars((string)$out["min_version"]);}
    if(!empty($out["options"])){$opts[]="options=".htmlspecialchars((string)$out["options"]);}
    if(count($opts)>0){
        $extra="<div class='sst-card-x'><span class='sst-mono' style='font-size:12px;word-break:break-all'>tls_outgoing_options ".@implode(" ",$opts)."</span></div>";
    }
    return sst_card("fas fa-code","violet","{sslproxy_version}","","{sslst_tls_explain}",$extra,
        "<span class='ak-chip sst-cap'>$label</span>",sst_edit_btn("Loadjs('$page?form-js=yes');"));
}

/** Carte : base des certificats generes et file d'attente des processus. */
function sst_card_db($st,$page):string{
    $tpl=new template_admin();
    $c=$st["sslcrtd"] ?? array();
    $parts=array();
    if(!empty($c["cache_disabled"])){
        $parts[]="{sslst_cache_off}";
    }else{
        if(!empty($c["in_memory"])){$parts[]="{use_memory}";}
        $parts[]=intval($c["db_size_mb"] ?? 8)." MB";
    }
    $chip=@implode(" &middot; ",$parts)." ({children}: ".intval($c["children"] ?? 32)." &middot; {queue_size}: ".intval($c["queue_size"] ?? 64).")";
    $after="LoadAjaxSilent('table-flat','$page?table-flat=yes');";
    $purge=$tpl->framework_buildjs("/proxy/ssl/cache/rebuild","squid.access.center.progress",
        "squid.access.center.progress.log","progress-ssl-restart",$after,null,"{sslst_purge_confirm}","AsSquidAdministrator");
    $btn="<button type='button' class='ak-btn' OnClick=\"$purge\"><i class='fas fa-trash-can'></i> <span class='sst-cap'>{purge}</span></button>";
    return sst_card("fas fa-database","slate","{sslst_db_title}","","{sslst_db_explain}","",
        "<span class='ak-chip'>$chip</span>$btn",sst_edit_btn("Loadjs('$page?form-js=yes');"));
}

/** Bandeau d'etat du bas : proxy, version, hote. */
function sst_status_bar($st):string{
    $run=!empty($st["squid_running"]);
    $dot=$run?"ak-ok":"ak-err";
    $txt=$run?"{sslst_proxy_online}":"{sslst_proxy_offline}";
    $ver=htmlspecialchars((string)($st["squid_version"] ?? ""));
    $host=htmlspecialchars((string)php_uname("n"));
    $h ="<div class='sst-status'><div><span class='ak-dot $dot'></span> ";
    $h.="Squid <strong>$ver</strong> &middot; <strong>$host</strong> : $txt</div>";
    $md5=(string)($st["ssl_conf_md5"] ?? "");
    if($md5<>""){$h.="<div class='sst-mono'>ssl.conf md5 ".htmlspecialchars(substr($md5,0,12))."</div>";}
    $h.="</div>";
    return $h;
}

/** Enregistrement immediat d'un interrupteur (liste fermee de reglages). */
function sst_toggle_save():bool{
    $users=new usersMenus();
    if(!$users->AsSquidAdministrator){
        $tpl=new template_admin();
        echo $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS2}");
        return false;
    }
    $allowed=array("on_unsupported_protocol","SQUID_DONT_VERIFY_PEER");
    $key=(string)$_POST["sst-toggle"];
    if(!in_array($key,$allowed,true)){echo "Unknown setting";return false;}
    if(sslproxy_meta_key_held($key)){return sslproxy_meta_refuse_text();}
    $value=intval($_POST["value"] ?? 0)==1?1:0;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO($key,$value);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/reset/cache");
    return admin_tracks("Proxy SSL: $key set to $value");
}

/**
 * Vue Statut : quatre indicateurs, cartes de reglage, bandeau d'etat.
 * L'identifiant table-flat est conserve : les fenetres de reglage le
 * rechargent apres un enregistrement.
 */
function table_flat():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $st=sst_state();
    $SquidSSLUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSSLUrgency"));

    $after="LoadAjaxSilent('table-flat','$page?table-flat=yes');";
    $rebuild=$tpl->framework_buildjs("/proxy/ssl/build","squid.ssl.rules.articarest.progress",
        "squid.ssl.rules.progress.log","progress-ssl-restart",$after,null,null,"AsSquidAdministrator");

    $html=array();
    $html[]="<div class='ak sst-page'>";
    $html[]=sslproxy_meta_banner($tpl,"","{sslproxy_meta_settings_explain}");
    if($SquidSSLUrgency==1){
        $html[]="<div class='ak-banner ak-err sst-alert'><i class='fas fa-triangle-exclamation'></i> {proxy_in_ssl_emergency_mode}</div>";
    }
    if(count($st)==0){
        $html[]="<div class='ak-banner ak-err sst-alert'><i class='fas fa-plug-circle-xmark'></i> {sslst_no_daemon}</div>";
    }else{
        $html[]="<div class='sst-kpis'>";
        $html[]=sst_kpi_engine($st);
        $html[]=sst_kpi_certs($st);
        $html[]=sst_kpi_inspection($st);
        $html[]=sst_kpi_ca($st);
        $html[]="</div>";

        $html[]="<div class='ak-card'><div class='sst-sec-h'><div>";
        $html[]="<div class='sst-sec-t'>{sslst_directives}</div><div class='sst-sec-e'>{sslst_directives_explain}</div></div>";
        $html[]="<button type='button' class='ak-btn' OnClick=\"$rebuild\"><i class='fas fa-rotate'></i> {sslst_reload}</button></div>";
        $html[]="<div class='sst-grid'>";
        foreach (($st["ports"] ?? array()) as $p){ $html[]=sst_card_port($p); }
        $html[]=sst_card_deploy($st,$page);
        $html[]=sst_card_nonhttp($st,$page);
        $html[]=sst_card_trusted($st);
        $html[]=sst_card_noverify($st,$page);
        $html[]=sst_card_tls($st,$page);
        $html[]=sst_card_db($st,$page);
        $html[]="</div></div>";
        $html[]=sst_status_bar($st);
    }
    $html[]="</div>";

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

    // Interrupteur : enregistrement, puis reconstruction de ssl.conf avec
    // barre de progression ; en cas de refus, la case revient a sa position.
    $js=array();
    $js[]="<script>";
    $js[]="NoSpinner();";
    $js[]="function SstToggle(key,el){";
    $js[]="  var v=el.checked?1:0;";
    $js[]="  $.post('$page',{'sst-toggle':key,'value':v},function(r){";
    $js[]="    r=String(r||'').trim();";
    $js[]="    if(r.length>0){ el.checked=!el.checked; alert(r); return; }";
    $js[]="    $rebuild";
    $js[]="  });";
    $js[]="}";
    $js[]=$jstiny;
    $js[]="</script>";

    echo sst_css().$tpl->_ENGINE_parse_body(@implode("\n",$html)).@implode("\n",$js);
    return true;
}

function Save():bool{
    $tpl=new template_admin();
    // sslcrtd_disable_cache tenu par Meta : non poste (lecture seule), rien a deduire
    if(isset($_POST['sslcrtd_disable_cache'])){
        if(intval($_POST['sslcrtd_disable_cache'])==1){
            $_POST['sslcrtd_program_in_memory']=0;
        }else{
            $_POST['sslcrtd_program_in_memory']=1;
        }
    }
    // reglages tenus par la politique Meta : jamais ecrits (articarest les refuserait, 423)
    $_POST=sslproxy_meta_strip_post($_POST);

    $tpl->SAVE_POSTs();
    return true;
}

/**
 * Un port d'ecoute SSL et son certificat.
 * L'interface et le port sont replies en sous-titre du nom du port: ils
 * etaient colles au nom entre parentheses dans une cellule a width:90%.
 */
function ssl_port_row($md,$portname,$listen,$certificate,$dl_cert,$dl_ps1):string{
    $h ="<tr id='$md'>";
    $h.="<td><div class='awc-ident'>";
    $h.="<span class='awc-ico'><i class='fas fa-plug'></i></span>";
    $h.="<span><span class='awc-name'>$portname</span>";
    $h.="<div class='awc-meta awc-mono'>$listen</div></span>";
    $h.="</div></td>";
    $h.="<td class='awc-nowrap'><i class='".ico_certificate."'></i>&nbsp;$certificate</td>";
    $h.="<td class='center'>$dl_cert</td>";
    $h.="<td class='center'>$dl_ps1</td>";
    $h.="</tr>";
    return $h;
}
