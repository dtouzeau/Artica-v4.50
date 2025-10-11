<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(isset($_POST["DNSDistDOH"])){Save();exit;}
if(isset($_GET["dnsdist-doh-status"])){status();exit;}
if(isset($_GET["form-js"])){form_js();exit;}
if(isset($_GET["form-popup"])){form_popup();exit;}
if(isset($_GET["status"])){page();exit;}

start();
function form_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $tpl->js_dialog1("{global_parameters}","$page?form-popup=yes");
    return true;
}
function form_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $jsRestart=$tpl->framework_buildjs("/dnsfw/service/php/restart",
        "dnsdist.restart","dnsdist.restart.log",
        "progress-unbound-restart",
        "LoadAjaxSilent('dnsdist-doh-status','$page?dnsdist-doh-status=yes');"
    );


    $DNSDistSSLCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistSSLCertificate");
    $DNSDistDOH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDOH"));

    $DNSDistDOHPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDOHPort"));
    $DNSDistDOHInterfaces=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDOHInterfaces"));
    $DNSDistDOHPath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDOHPath"));
    if($DNSDistDOHPort==0){$DNSDistDOHPort=80;}
    if($DNSDistDOHPath==null){$DNSDistDOHPath="/dns-query";}


    $form[]=$tpl->field_hidden("DNSDistDOHSSL",1);
    $form[]=$tpl->field_checkbox("DNSDistDOH","{enable_feature}",$DNSDistDOH,true);
    $DnsDistProxyProtocol=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsDistProxyProtocol"));
    if($DnsDistProxyProtocol==0){
        $form[]=$tpl->field_hidden("DnsDistProxyProtocol",0);
    }else{
        $DNSDistDOHProxyProto=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDOHProxyProto"));
        $form[]=$tpl->field_checkbox("DNSDistDOHProxyProto","{proxy_protocol}",$DNSDistDOHProxyProto);
    }





    $form[]=$tpl->field_interfaces_choose("DNSDistDOHInterfaces","{listen_interfaces}",$DNSDistDOHInterfaces);
    $form[]=$tpl->field_numeric("DNSDistDOHPort","{listen_port}",$DNSDistDOHPort);
    $form[]=$tpl->field_text("DNSDistDOHPath","{doh_subfolder}",$DNSDistDOHPath);
    $form[]=$tpl->field_certificate("DNSDistSSLCertificate","{certificate}",$DNSDistSSLCertificate);
    $html[]=$tpl->form_outside(null,$form,null,
        "{apply}",
        "dialogInstance1.close();LoadAjaxSilent('dnsdist-doh-form','$page?status=yes');$jsRestart",
        "AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div id='dnsdist-doh-form'></div>
    <script>LoadAjaxSilent('dnsdist-doh-form','$page?status=yes')</script>
";
    return true;
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $DNSDistSSLCertificate=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistSSLCertificate");
    $DNSDistDOH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDOH"));
    $DNSDistDOHSSL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDOHSSL"));
    $DNSDistDOHPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDOHPort"));
    $DNSDistDOHInterfaces=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDOHInterfaces"));
    $DNSDistDOHPath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDOHPath"));
    if($DNSDistDOHPort==0){$DNSDistDOHPort=80;}
    if($DNSDistDOHPath==null){$DNSDistDOHPath="/dns-query";}

    $jsRestart=$tpl->framework_buildjs("/dnsfw/service/php/restart",
        "dnsdist.restart","dnsdist.restart.log",
        "progress-unbound-restart",
        "LoadAjaxSilent('dnsdist-doh-status','$page?dnsdist-doh-status=yes');"
    );

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align:top;width:350px'>";
    $html[]="<div id='dnsdist-doh-status'></div>";
    $html[]="</td>";
    $html[]="<td style='vertical-align:top;padding-left:10px'>";

    $tpl->table_form_field_js("Loadjs('$page?form-js=yes');");
    $tpl->table_form_field_bool("{enable_feature}",$DNSDistDOH,ico_proto);
    if($DNSDistDOH==1){

        $DnsDistProxyProtocol=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsDistProxyProtocol"));
        if($DnsDistProxyProtocol==0){
            $tpl->table_form_field_text("{proxy_protocol}","{not_installed}",ico_proto);
        }else{
            $DNSDistDOHProxyProto=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDOHProxyProto"));
            $tpl->table_form_field_bool("{proxy_protocol}",$DNSDistDOHProxyProto,ico_proto);
        }

        $tpl->table_form_field_text("{listen_interfaces}","$DNSDistDOHInterfaces:$DNSDistDOHPort$DNSDistDOHPath",ico_interface);
        $tpl->table_form_field_certificate($DNSDistSSLCertificate);


    }


    $html[]=$tpl->table_form_compile();
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";


    $TINY_ARRAY["TITLE"]="{APP_DNSDIST}: DNS-over-HTTPS (DoH)";
    $TINY_ARRAY["ICO"]="fa-brands fa-expeditedssl";
    $TINY_ARRAY["EXPL"]="{UNBOUND_DOH_EXPLAIN}";
    $TINY_ARRAY["URL"]="";
    $TINY_ARRAY["BUTTONS"]="";
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $js=$tpl->RefreshInterval_js("dnsdist-doh-status",$page,"dnsdist-doh-status=yes");
    $html[]="
<script>
    $js
    $jstiny
</script>";
    echo $tpl->_ENGINE_parse_body($html);



}

function status(){
    $DNSDistDOH=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDOH"));
    $tpl=new template_admin();
    if($DNSDistDOH==0){
        echo $tpl->_ENGINE_parse_body($tpl->widget_h("gray",
            "fa-brands fa-expeditedssl",
            "{disabled}","DNS-over-HTTPS"
        ));
        return true;
    }


    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/doh/status"));
    if(!$json->Status){
        echo $tpl->_ENGINE_parse_body($tpl->widget_h("red",
            "fa-brands fa-expeditedssl", "<p style='color:white;font-size:12px;line-height:15px'>$json->Error</p>",
            "DNS-over-HTTPS"
        ));
        return true;
    }

    if(trim($json->Info)=="OK"){
        echo $tpl->_ENGINE_parse_body($tpl->widget_h("green",
            "fa-brands fa-expeditedssl",
            "{running}",
            "DNS-over-HTTPS"
        ));
        DohResults();
        return true;
    }

    echo $tpl->_ENGINE_parse_body($tpl->widget_h("red",
        "fa-brands fa-expeditedssl",
        "<p style='color:white;font-size:12px;line-height:15px'>{error} [$json->Info]</p>",
        "DNS-over-HTTPS"
    ));
    return true;
}
function DohResults():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/struct"));
    if(!$json->Status){

        echo $tpl->_ENGINE_parse_body($tpl->widget_h("red",
            ico_bug, "<p style='color:white;font-size:12px;line-height:15px'>$json->Error</p>",
            "{status}"
        ));
        return false;
    }
    if(!property_exists($json->Info,"dohFrontends")){
        echo $tpl->_ENGINE_parse_body($tpl->widget_h("red",
            ico_bug, "<p style='color:white;font-size:12px;line-height:15px'>dohFrontends unknown</p>",
            "{status}"
        ));
        return false;
    }

    
    $c=0;
    $Connects=0;
    $QUeries=0;
    foreach ($json->Info->dohFrontends as $dohFrontend){
        $address=$dohFrontend->address;
        if($address=="127.0.0.1"){
            continue;
        }
        $Connects=$dohFrontend->{"http-connects"};
        $QUeries=$dohFrontend->{"get-queries"}+$dohFrontend->{"post-queries"};
        $c++;
    }
    if($c==0){
        echo $tpl->_ENGINE_parse_body($tpl->widget_h("grey",
            ico_clouds, "{inactive2}",
            "{queries}"
        ));
        return true;
    }
    if($QUeries==0){
        echo $tpl->_ENGINE_parse_body($tpl->widget_h("grey",
            ico_clouds, "{inactive2}",
            "{queries}"
        ));
        return true;
    }

    echo $tpl->_ENGINE_parse_body($tpl->widget_h("green",
        ico_clouds, "$Connects/$QUeries",
        "{queries}"
    ));
    return true;
}



function Save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $DNSDistDOHPort=intval($_POST["DNSDistDOHPort"]);
    $DNSDistDOHSSL=intval($_POST["DNSDistDOHSSL"]);
    $DNSDistSSLCertificate=trim($_POST["DNSDistSSLCertificate"]);
    if($DNSDistDOHPort==443){
        if($DNSDistDOHSSL==0){
            echo $tpl->post_error("Please specify a certificate");
            return;
        }
        if(strlen($DNSDistSSLCertificate)<3){
            echo $tpl->post_error("Please specify a certificate");
            return;
        }
    }
    admin_tracks_post("Save Firewall DNS DOH section");
    $tpl->SAVE_POSTs();
}