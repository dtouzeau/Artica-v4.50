<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(isset($_POST["AuthorizeTSElogin"])){SaveSettings();exit;}
if(isset($_POST["RDPProxyVideoRententionDays"])){video_params_save();exit;}
if(isset($_POST["RDPProxyAuthookDebug"])){video_params_save();exit;}
if(isset($_POST["RDPProxyPort"])){SaveSettings();exit;}
if(isset($_POST["RDPProxyTheme"])){SaveSettings();exit;}
if(isset($_GET["authook"])){authook();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["settings"])){settings_flat();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["table-start"])){table_start();exit;}
if(isset($_GET["about"])){about();exit;}
if(isset($_GET["video"])){video_params();exit;}
if(isset($_GET["theme"])){theme_start();exit;}
if(isset($_GET["rdpproxy-theme"])){theme();exit;}
if(isset($_GET["file-uploaded"])){file_uploaded();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["rdpproxy-top"])){status_top();exit;}

if(isset($_GET["listen-js"])){listen_js();exit;}
if(isset($_GET["listen-popup"])){listen_popup();exit;}
if(isset($_POST["RDPProxyListen"])){SaveSettings();exit;}

if(isset($_GET["dh-js"])){dh_js();exit;}
if(isset($_GET["dh-popup"])){dh_popup();exit;}

if(isset($_GET["timeouts-js"])){timeouts_js();exit;}
if(isset($_GET["timeouts-popup"])){timeouts_popup();exit;}
if(isset($_POST["RDPHandshakeTimeout"])){SaveSettings();}

if(isset($_GET["rdp-js"])){rdp_js();exit;}
if(isset($_GET["rdp-popup"])){rdp_popup();exit;}


if(isset($_GET["tls-js"])){tls_js();exit;}
if(isset($_GET["tls-popup"])){tls_popup();exit;}
if(isset($_POST["RDPProxySSLCertificate"])){SaveSettings();exit;}
if(isset($_POST["RDPIgnoreLogonPassword"])){SaveSettings();exit;}
if(isset($_POST["RDPHandshakeTimeout"])){SaveSettings();exit;}




page();

function tabs(){

    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{status}"]="$page?table-start=yes";
    $array["{video_recording}"]="$page?video=yes";
    $array["{template}"]="$page?theme=yes";
    $array["{APP_RDPPROXY_AUTHHOOK}"]="$page?authook=yes";
    $array["{about2}"]="$page?about=yes";

    echo $tpl->tabs_default($array);

}
function theme_start(){
    $page=CurrentPageName();
    echo "<div id='rdpproxy-theme' style='margin-top: 20px'></div><script>LoadAjax('rdpproxy-theme','$page?rdpproxy-theme=yes')</script>";
}
function theme(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.rdpproxy.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.rdpproxy.progress.txt";
    $ARRAY["CMD"]="/rdpproxy/restart";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="LoadAjax('rdpproxy-status-table','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $restartService="Loadjs('fw.progress.php?content=$prgress&mainid=progress-rdpproxy-restart');";

    $RDPProxyTheme=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyTheme"));
    $RDPProxyBackGroundColor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyBackGroundColor");
    $RDPProxyFgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyFgcolor");
    $RDPProxySeparatorColor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySeparatorColor");
    $RDPProxyFocusColor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyFocusColor");
    $RDPProxyErrorColor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyErrorColor");
    $RDPProxySelectorLine1Bgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorLine1Bgcolor");
    $RDPProxySelectorLine1Fgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorLine1Fgcolor");
    $RDPProxySelectorLine2Bgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorLine2Bgcolor");
    $RDPProxySelectorLine2Fgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorLine2Fgcolor");
    $RDPProxyEditBgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyEditBgcolor");
    $RDPProxyEditFgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyEditFgcolor");
    $RDPProxyEditFocusColor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyEditFocusColor");
    $RDPProxySelectorSelectedBgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorSelectedBgcolor");
    $RDPProxySelectorSelectedFgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorSelectedFgcolor");

    $RDPProxySelectorFocusBgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorFocusBgcolor");
    $RDPProxySelectorFocusFgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorFocusFgcolor");

    $RDPProxySelectorLabelBgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorLabelBgcolor");
    $RDPProxySelectorLabelFgcolor=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySelectorLabelFgcolor");
    $RDPProxyLogo=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyLogo");
    if($RDPProxyLogo==null){$RDPProxyLogo="img/rdpproxy/wablogoblue.png";}

    if($RDPProxyBackGroundColor==null){$RDPProxyBackGroundColor="#081f60";}
    if($RDPProxyFgcolor==null){$RDPProxyFgcolor="#FFFFFF";}
    if($RDPProxySeparatorColor==null){$RDPProxySeparatorColor="#cfd5eb";}
    if($RDPProxyFocusColor==null){$RDPProxyFocusColor="#004D9C";}
    if($RDPProxyErrorColor==null){$RDPProxyErrorColor="#ffff00";}
    if($RDPProxySelectorLine1Bgcolor==null){$RDPProxySelectorLine1Bgcolor="#000000";}
    if($RDPProxySelectorLine1Fgcolor==null){$RDPProxySelectorLine1Fgcolor="#cfd5eb";}
    if($RDPProxySelectorLine2Bgcolor==null){$RDPProxySelectorLine2Bgcolor="#cfd5eb";}
    if($RDPProxySelectorLine2Fgcolor==null){$RDPProxySelectorLine2Fgcolor="#000000";}

    if($RDPProxySelectorSelectedBgcolor==null){$RDPProxySelectorSelectedBgcolor="#4472C4";}
    if($RDPProxySelectorSelectedFgcolor==null){$RDPProxySelectorSelectedFgcolor="#FFFFFF";}

    if($RDPProxySelectorFocusBgcolor==null){$RDPProxySelectorFocusBgcolor="#004D9C";}
    if($RDPProxySelectorFocusFgcolor==null){$RDPProxySelectorFocusFgcolor="#FFFFFF";}

    if($RDPProxySelectorLabelBgcolor==null){$RDPProxySelectorLabelBgcolor="#4472C4";}
    if($RDPProxySelectorLabelFgcolor==null){$RDPProxySelectorLabelFgcolor="#FFFFFF";}
    if($RDPProxyEditBgcolor==null){$RDPProxyEditBgcolor="#FFFFFF";}
    if($RDPProxyEditFgcolor==null){$RDPProxyEditFgcolor="#000000";}
    if($RDPProxyEditFocusColor==null){$RDPProxyEditFocusColor="#004D9C";}


    $form[]=$tpl->field_checkbox("RDPProxyTheme","{enable_feature}",$RDPProxyTheme);
    $form[]=$tpl->field_color("RDPProxyBackGroundColor","{background_color}",$RDPProxyBackGroundColor);
    $form[]=$tpl->field_color("RDPProxyFgcolor","{text_color}",$RDPProxyFgcolor);

    $form[]=$tpl->field_color("RDPProxyEditBgcolor","{edit_bgcolor}",$RDPProxyEditBgcolor);
    $form[]=$tpl->field_color("RDPProxyEditFgcolor","{edit_fgcolor}",$RDPProxyEditFgcolor);
    $form[]=$tpl->field_color("RDPProxyEditFocusColor","{edit_focus_color}",$RDPProxyEditFocusColor);



    $form[]=$tpl->field_color("RDPProxyFocusColor","{focus_color}",$RDPProxyFocusColor);
    $form[]=$tpl->field_color("RDPProxyErrorColor","{error_color}",$RDPProxyErrorColor);
    $form[]=$tpl->field_color("RDPProxySeparatorColor","{separator_color}",$RDPProxySeparatorColor);

    $form[]=$tpl->field_color("RDPProxySelectorLine1Bgcolor","{row_color} (1)",$RDPProxySelectorLine1Bgcolor);
    $form[]=$tpl->field_color("RDPProxySelectorLine1Fgcolor","{text_color} {row} (1)",$RDPProxySelectorLine1Fgcolor);

    $form[]=$tpl->field_color("RDPProxySelectorLine2Bgcolor","{row_color} (2)",$RDPProxySelectorLine2Bgcolor);
    $form[]=$tpl->field_color("RDPProxySelectorLine2Fgcolor","{text_color} {row} (2)",$RDPProxySelectorLine2Fgcolor);



        $form[]=$tpl->field_color("RDPProxySelectorSelectedBgcolor","{selector_selected_bgcolor}",$RDPProxySelectorSelectedBgcolor);
    $form[]=$tpl->field_color("RDPProxySelectorSelectedFgcolor","{selector_selected_fgcolor}",$RDPProxySelectorSelectedFgcolor);

        $form[]=$tpl->field_color("RDPProxySelectorFocusBgcolor","{selector_focus_bgcolor}",$RDPProxySelectorFocusBgcolor);
    $form[]=$tpl->field_color("RDPProxySelectorFocusFgcolor","{selector_focus_fgcolor}",$RDPProxySelectorFocusFgcolor);

        $form[]=$tpl->field_color("RDPProxySelectorLabelBgcolor","{selector_label_bgcolor}",$RDPProxySelectorLabelBgcolor);
    $form[]=$tpl->field_color("RDPProxySelectorLabelFgcolor","{selector_label_fgcolor}",$RDPProxySelectorLabelFgcolor);

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:1%;vertical-align: top' nowrap>
    <div style='margin: 5px;border:1px solid #CCCCCC'><img src='$RDPProxyLogo'></div>";
    $html[]="<table style='width:1%'>";
    $html[]=$tpl->form_button_upload("{picture} (*.png)",$page,"AsSquidAdministrator");
    $html[]="</table>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align: top' nowrap>";
    $html[]=$tpl->form_outside("{skin}: {parameters}",$form,null,"{apply}",$restartService,"AsSquidAdministrator");
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";

    echo $tpl->_ENGINE_parse_body($html);

}

function listen_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{listen_interface}","$page?listen-popup=yes",650);
}
function dh_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{dh_parameters}","$page?dh-popup=yes",650);
}
function timeouts_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{timeouts}","$page?timeouts-popup=yes",650);
}
function rdp_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("RDP: {parameters}","$page?rdp-popup=yes",650);
}
function tls_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{tls_title}","$page?tls-popup=yes",850);
}

function dh_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/rdpproxy/dh3072"));
    if(property_exists($json,"Info")){
        $RDPProxyDH3072=$json->Info;
        $form[]=$tpl->field_textareacode("DHPARAM","",$RDPProxyDH3072->Content);
        $html[]= $tpl->form_outside("",$form,null,"",
            "dialogInstance2.close();"
            ,"AsSquidAdministrator",false,true);
        echo $tpl->_ENGINE_parse_body($html);

    }
    return true;
}

function listen_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $RDPProxyListen = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyListen");
    $RDPProxyPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyPort"));
    $IsRDPProxyAuthDebug = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsRDPProxyAuthDebug"));
    if($RDPProxyPort<5){$RDPProxyPort="3389";}
    $jsrestart=$tpl->framework_buildjs("/rdpproxy/restart",
        "squid.rdpproxy.progress","squid.rdpproxy.progress.txt","progress-rdpproxy-restart");
    $form[]=$tpl->field_interfaces("RDPProxyListen","{listen_interface}",$RDPProxyListen);
    $form[]=$tpl->field_numeric("RDPProxyPort","{listen_port}",$RDPProxyPort);
    $form[]=$tpl->field_checkbox("IsRDPProxyAuthDebug","{debug}",$IsRDPProxyAuthDebug);
    $html[]= $tpl->form_outside("",$form,null,"{apply}",
        "dialogInstance2.close();$jsrestart"
        ,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function rdp_popup():bool{
    $page = CurrentPageName();
    $tpl = new template_admin();
    $RDPDisconnectOnLogonUserChange=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPDisconnectOnLogonUserChange"));
    $RDPIgnoreLogonPassword=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPIgnoreLogonPassword"));
    $ForwardCredentials = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ForwardCredentials"));
    $AuthorizeTSElogin  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AuthorizeTSElogin"));


    $form[]=$tpl->field_checkbox("RDPIgnoreLogonPassword","{ignore_logon_password}",$RDPIgnoreLogonPassword);
    $form[]=$tpl->field_checkbox("RDPDisconnectOnLogonUserChange","{DisconnectOnLogonUserChange}",$RDPDisconnectOnLogonUserChange,false,"{DisconnectOnLogonUserChange_explain}");
    $form[]=$tpl->field_checkbox("AuthorizeTSElogin","{AuthorizeTSElogin}",$AuthorizeTSElogin,"ForwardCredentials","{AuthorizeTSElogin_explain}");
    $form[]=$tpl->field_checkbox("ForwardCredentials","{ForwardCredentials}",$ForwardCredentials,false,"{ForwardCredentials_explain}");
    $html[]= $tpl->form_outside("",$form,null,"{apply}",
        "dialogInstance2.close();"
        ,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);

}
function tls_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $RDPMinTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPMinTLS"));
    $RDPSslCipher=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPSslCipher"));
    $RDPProxySSLCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySSLCertificate"));
    $RDPTlsFallbackLegacy= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPTlsFallbackLegacy"));
    $RDPUseKerberos = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPUseKerberos"));
    $RDPUseNla = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPUseNla"));
    $form[]=$tpl->field_certificate("RDPProxySSLCertificate","{use_certificate_from_certificate_center}",$RDPProxySSLCertificate);

    $RDPSslCipherList[0]="{RDP_CIPHER_0}";
    $RDPSslCipherList[1]="{RDP_CIPHER_1}";
    $RDPSslCipherList[2]="{RDP_CIPHER_2}";

    $MinTLS[0]="{no_restriction} (TLSv1.0)";
    $MinTLS[1]="TLSv1.1";
    $MinTLS[2]="TLSv1.2";
    $MinTLS[3]="TLSv1.3";


    $form[]=$tpl->field_checkbox("RDPTlsFallbackLegacy","{tls_fallback_legacy}",$RDPTlsFallbackLegacy);
    $form[]=$tpl->field_checkbox("RDPUseNla","{use_nla}",$RDPUseNla);
    $form[]=$tpl->field_checkbox("RDPUseKerberos","{use_kerberos}",$RDPUseKerberos);
    $form[]=$tpl->field_array_hash($MinTLS,"RDPMinTLS","nonull:{minimal_tls_version}",$RDPMinTLS);
    $form[]=$tpl->field_array_hash($RDPSslCipherList,"RDPSslCipher","{ssl_ciphers}",$RDPSslCipher);
    $html[]= $tpl->form_outside("",$form,null,"{apply}",
        "dialogInstance2.close();"
        ,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function timeouts_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $RDPHandshakeTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPHandshakeTimeout"));
    if($RDPHandshakeTimeout==0){
        $RDPHandshakeTimeout=10;
    }
    $RDPSessionTimeout = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPSessionTimeout"));
    if($RDPSessionTimeout==0){
        $RDPSessionTimeout=900;
    }

    $RDPKeepAlive= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPKeepAlive"));

    $jsrestart=$tpl->framework_buildjs("/rdpproxy/restart",
        "squid.rdpproxy.progress","squid.rdpproxy.progress.txt","progress-rdpproxy-restart");
    $form[]=$tpl->field_numeric("RDPHandshakeTimeout","{RDPHandshakeTimeout} ({minutes})",$RDPHandshakeTimeout);
    $form[]=$tpl->field_numeric("RDPSessionTimeout","{disconnect_after} ({seconds})",$RDPSessionTimeout);
    $form[]=$tpl->field_numeric("RDPKeepAlive","{keep_alive} ({minutes})",$RDPKeepAlive);


    $html[]= $tpl->form_outside("",$form,null,"{apply}",
        "dialogInstance2.close();$jsrestart"
        ,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table_start(){

    $page=CurrentPageName();

    echo "<div id='rdpproxy-status-table' style='margin-top:10px'></div>
    <script>LoadAjax('rdpproxy-status-table','$page?table=yes');</script>";
}

function authook(){
    $tpl=new template_admin();
    $page=CurrentPageName();


    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.rdpproxy.auth.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.rdpproxy.auth.progress.txt";
    $ARRAY["CMD"]="/rdpproxy/auth/restart";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="blur()";
    $prgress=base64_encode(serialize($ARRAY));
    $restartService="Loadjs('fw.progress.php?content=$prgress&mainid=progress-rdpproxy-restart');";


    $RDPProxyAuthookDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyAuthookDebug"));
    $form[]=$tpl->field_checkbox("RDPProxyAuthookDebug","{debug}","$RDPProxyAuthookDebug");
    echo $tpl->form_outside("{APP_RDPPROXY_AUTHHOOK}: {parameters}",$form,null,"{apply}",$restartService,"AsSquidAdministrator");

}

//profile=baseline preset=ultrafast flags=+qscale b=30000
function video_params(){
    $page=CurrentPageName();
    $RDPProxyVideoRententionDays = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoRententionDays"));
    if($RDPProxyVideoRententionDays==0){$RDPProxyVideoRententionDays=365;}
    $RDPProxyVideoPath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoPath");
    if($RDPProxyVideoPath==null){$RDPProxyVideoPath="/home/artica/rds/videos";}

    $RDPProxyVideoPreset=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoPreset"));
    if($RDPProxyVideoPreset==null){$RDPProxyVideoPreset="ultrafast";}

    $RDPProxyVideoTun=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoTun"));
    $RDPProxyVideoBitRate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoBitRate"));
    if($RDPProxyVideoBitRate==0){$RDPProxyVideoBitRate=30000;}
    $RDPProxyVideoFrameRate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoFrameRate"));
    if($RDPProxyVideoFrameRate==0){$RDPProxyVideoFrameRate=5;}


    $zRDPProxyVideoPreset["ultrafast"]="ultrafast";
    $zRDPProxyVideoPreset["superfast"]="superfast";
    $zRDPProxyVideoPreset["veryfast"]="veryfast";
    $zRDPProxyVideoPreset["faster"]="faster";
    $zRDPProxyVideoPreset["fast"]="fast";
    $zRDPProxyVideoPreset["medium"]="medium";
    $zRDPProxyVideoPreset["slow"]="slow";
    $zRDPProxyVideoPreset["slower"]="slower";
    $zRDPProxyVideoPreset["veryslow"]="veryslow";

    $zRDPProxyVideoTun["film"]="film";
    $zRDPProxyVideoTun["animation"]="animation";
    $zRDPProxyVideoTun["grain"]="grain";
    $zRDPProxyVideoTun["stillimage"]="stillimage";
    $zRDPProxyVideoTun["zerolatency"]="zerolatency";

    for($i=1;$i<41;$i++){
        $zRDPProxyVideoFrameRate[$i]=$i;
    }



    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.rdpproxy.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.rdpproxy.progress.txt";
    $ARRAY["CMD"]="/rdpproxy/restart";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="LoadAjax('rdpproxy-status-table','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $restartService="Loadjs('fw.progress.php?content=$prgress&mainid=progress-rdpproxy-restart');";


    $tpl=new template_admin();
    $form[]=$tpl->field_numeric("RDPProxyVideoRententionDays","{retention_time} ({days})",$RDPProxyVideoRententionDays);
    $form[]=$tpl->field_browse_directory("RDPProxyVideoPath","{storage_directory}",$RDPProxyVideoPath);
    $form[]=$tpl->field_array_hash($zRDPProxyVideoPreset,"RDPProxyVideoPreset","nonull:{RDPProxyVideoPreset}",
            $RDPProxyVideoPreset,false,"{RDPProxyVideoPreset_explain}");
    $form[]=$tpl->field_array_hash($zRDPProxyVideoTun,"RDPProxyVideoTun","nonull:{RDPProxyVideoTun}",
        $RDPProxyVideoTun,false,"{RDPProxyVideoTun_explain}");

    $form[]=$tpl->field_array_hash($zRDPProxyVideoFrameRate,"RDPProxyVideoFrameRate","nonull:{framerate}",
        $RDPProxyVideoFrameRate,false,null);

    $form[]=$tpl->field_numeric("RDPProxyVideoBitRate","{RDPProxyVideoBitRate}",$RDPProxyVideoBitRate);


    echo $tpl->form_outside("{video_recording}: {parameters}",$form,null,"{apply}",$restartService,"AsSquidAdministrator");

}
function video_params_save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    $sock=new sockets();
    $sock->getFrameWork("rdpproxy.php?videos=yes");

}

function settings_flat(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="";
    $RDPProxyDH3072Size=0;
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/rdpproxy/dh3072"));
    if(property_exists($json,"Info")){
        $RDPProxyDH3072=$json->Info;
        if($RDPProxyDH3072->Pid>0){
            $html[]=$tpl->div_warning("DH 3072: {running} (pid: $RDPProxyDH3072->Pid) {since} $RDPProxyDH3072->Time {minutes} - {$RDPProxyDH3072->Size}bytes) {please_wait_working}");;
        }else{
            if($RDPProxyDH3072->Size>10){
                $RDPProxyDH3072Size=$RDPProxyDH3072->Size;
            }
        }
    }
    $RDPProxyListen = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyListen");
    $RDPProxyPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyPort"));
    $IsRDPProxyAuthDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsRDPProxyAuthDebug"));
    if($RDPProxyPort<5){$RDPProxyPort="3389";}
    if($RDPProxyListen==null){$RDPProxyListen="0.0.0.0";}
    $tpl->table_form_field_js("Loadjs('$page?listen-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{listen_interface}","$RDPProxyListen:$RDPProxyPort",ico_nic);
    if($IsRDPProxyAuthDebug==1) {
      $tpl->table_form_field_text("{debug}", "{active2}", ico_bug, true);
    }



    $RDPEncryptionLevel =trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyListen"));

    $RDPHandshakeTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPHandshakeTimeout"));
    if($RDPHandshakeTimeout==0){
        $RDPHandshakeTimeout=10;
    }
    $RDPSessionTimeout = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPSessionTimeout"));
    if($RDPSessionTimeout==0){
        $RDPSessionTimeout=900;
    }
    $keep_alive="";

    $RDPKeepAlive= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPKeepAlive"));
    if($RDPKeepAlive>0){
        $keep_alive=",{keep_alive} $RDPKeepAlive {minutes}";
    }

    $tpl->table_form_field_js("Loadjs('$page?timeouts-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{timeouts}","{RDPHandshakeTimeout} $RDPHandshakeTimeout {minutes}, {disconnect_after} $RDPSessionTimeout {seconds}$keep_alive",ico_timeout);

    $RDPSslCipherList[0]="{RDP_CIPHER_0}";
    $RDPSslCipherList[1]="{RDP_CIPHER_1}";
    $RDPSslCipherList[2]="{RDP_CIPHER_2}";

    $MinTLS[0]="{no_restriction} (TLSv1.0)";
    $MinTLS[1]="TLSv1.1";
    $MinTLS[2]="TLSv1.2";
    $MinTLS[3]="TLSv1.3";
    $tls_fallback_legacy="";
    $use_nla_text="";
    $use_kerberos="";

    $RDPTlsFallbackLegacy = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPTlsFallbackLegacy"));
    $RDPUseNla = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPUseNla"));
    $RDPUseKerberos = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPUseKerberos"));

    if($RDPTlsFallbackLegacy==1){
        $tls_fallback_legacy=", {tls_fallback_legacy}";
    }
    if($RDPUseNla==1){
        $use_nla_text=", {use_nla}";
    }
    if($RDPUseKerberos==1){
        $use_kerberos=", {use_kerberos}";
    }
    $RDPMinTLS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPMinTLS"));
    $RDPSslCipher=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPSslCipher"));
    $RDPProxySSLCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxySSLCertificate"));
    if($RDPProxySSLCertificate==""){
        $RDPProxySSLCertificate="{default}";
    }
    $tpl->table_form_field_js("Loadjs('$page?tls-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_text("{tls_title}","<small style='text-transform: none'><span style='font-weight: normal'>{certificate}:</span> $RDPProxySSLCertificate, <span style='font-weight: normal'>{minimal_tls_version}:</span> $MinTLS[$RDPMinTLS], <span style='font-weight: normal'>{ssl_ciphers}:</span> $RDPSslCipherList[$RDPSslCipher]$tls_fallback_legacy$use_nla_text$use_kerberos</small>",ico_certificate);

    if($RDPProxyDH3072Size>10){
        $tpl->table_form_field_js("Loadjs('$page?dh-js=yes')","AsSquidAdministrator");
        $tpl->table_form_field_text("{dh_parameters}",$RDPProxyDH3072Size."Bytes",ico_certificate);
    }

    $RDPIgnoreLogonPassword=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPIgnoreLogonPassword"));
    $RDPDisconnectOnLogonUserChange=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPDisconnectOnLogonUserChange"));

    $ForwardCredentials = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ForwardCredentials"));
    $AuthorizeTSElogin  = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AuthorizeTSElogin"));

    $tpl->table_form_section("{parameters} RDP");
    $tpl->table_form_field_js("Loadjs('$page?rdp-js=yes')","AsSquidAdministrator");
    $tpl->table_form_field_bool("{ignore_logon_password}",$RDPIgnoreLogonPassword,ico_lock);
    $tpl->table_form_field_bool("{DisconnectOnLogonUserChange}",$RDPDisconnectOnLogonUserChange,ico_user_lock);
    $tpl->table_form_field_bool("{AuthorizeTSElogin}",$AuthorizeTSElogin,ico_user);
    $tpl->table_form_field_bool("{ForwardCredentials}",$ForwardCredentials,ico_user);

    $form[]=$tpl->field_checkbox("AuthorizeTSElogin","{AuthorizeTSElogin}",$AuthorizeTSElogin,"ForwardCredentials","{AuthorizeTSElogin_explain}");
    $form[]=$tpl->field_checkbox("ForwardCredentials","{ForwardCredentials}",$ForwardCredentials,false,"{ForwardCredentials_explain}");



    $html[]=$tpl->table_form_compile();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function settings(){

    $page=CurrentPageName();
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/squid.rdpproxy.progress";
    $ARRAY["LOG_FILE"]=PROGRESS_DIR."/squid.rdpproxy.progress.txt";
    $ARRAY["CMD"]="/rdpproxy/restart";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="LoadAjax('rdpproxy-status-table','$page?table=yes');";
    $prgress=base64_encode(serialize($ARRAY));
    $restartService="Loadjs('fw.progress.php?content=$prgress&mainid=progress-rdpproxy-restart');";


    $tpl=new template_admin();



    $RDPEncryptionLevel =trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyListen"));
    $RDPSessionTimeout = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPSessionTimeout"));
    $RDPIgnoreLogonPassword=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPIgnoreLogonPassword"));
    $RDPTlsFallbackLegacy= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPTlsFallbackLegacy"));
    $RDPTlsSupport=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPTlsSupport"));

    $RDPDisconnectOnLogonUserChange=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPDisconnectOnLogonUserChange"));
    $AllowAuthenticateScreen=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AllowAuthenticateScreen"));
    $RDPRejectErrors=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPRejectErrors"));
    $IsRDPProxyAuthDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IsRDPProxyAuthDebug"));






    if($RDPEncryptionLevel==null){$RDPEncryptionLevel="low";}
    if($RDPSessionTimeout==0){$RDPSessionTimeout="900";}


    $RDPEncryptionLevelZ["low"]="{low} {default}";
    $RDPEncryptionLevelZ["medium"]="{medium}";
    $RDPEncryptionLevelZ["high"]="{high}";





    $form[]=$tpl->field_array_hash($RDPEncryptionLevelZ,"RDPEncryptionLevel","nonull:{encryption_level}",$RDPEncryptionLevel);






    $form[]=$tpl->field_checkbox("RDPTlsSupport","{tls_support}",$RDPTlsSupport);

    $html[]= $tpl->form_outside("{APP_RDPPROXY} {parameters}",$form,null,"{apply}",$restartService,"AsSquidAdministrator");

    $form=array();


    $form[]=$tpl->field_checkbox("AllowAuthenticateScreen","{AllowAuthenticateScreen}",$AllowAuthenticateScreen,false,"{AllowAuthenticateScreen_explain}");

    $form[]=$tpl->field_checkbox("RDPRejectErrors","{RDPRejectErrors}",$RDPRejectErrors,false,"{RDPRejectErrors_explain}");

    $RDP_ERROR_MSG1=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDP_ERROR_MSG1");
    $RDP_ERROR_MSG2=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDP_ERROR_MSG2");
    $RDP_ERROR_MSG3=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDP_ERROR_MSG3");
    $RDP_ERROR_MSG4=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDP_ERROR_MSG4");
    $RDP_ERROR_MSG5=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDP_ERROR_MSG5");
    $RDP_ERROR_MSG6=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDP_ERROR_MSG6");
    $RDP_ERROR_MSG7=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDP_ERROR_MSG7");
    $RDP_ERROR_MSG8=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDP_ERROR_MSG8");

    if($RDP_ERROR_MSG1==null){$RDP_ERROR_MSG1="No password entered in your TSE client";}
    if($RDP_ERROR_MSG2==null){$RDP_ERROR_MSG2="Internal Error, please contact your administrator";}
    if($RDP_ERROR_MSG3==null){$RDP_ERROR_MSG3="Your are not authorized to use this method";}
    if($RDP_ERROR_MSG4==null){$RDP_ERROR_MSG4="Your Computer is not authorized to use this method";}
    if($RDP_ERROR_MSG5==null){$RDP_ERROR_MSG5="Permission denied";}
    if($RDP_ERROR_MSG6==null){$RDP_ERROR_MSG6="Please prepare credentials before connecting";}
    if($RDP_ERROR_MSG7==null){$RDP_ERROR_MSG7="Non-existent user";}
    if($RDP_ERROR_MSG8==null){$RDP_ERROR_MSG8="No password typed";}



    $form[]=$tpl->field_section("{error_messages_customization}");

    $form[]=$tpl->field_text("RDP_ERROR_MSG1","{error} 1",$RDP_ERROR_MSG1,true);
    $form[]=$tpl->field_text("RDP_ERROR_MSG2","{error} 2",$RDP_ERROR_MSG2,true);
    $form[]=$tpl->field_text("RDP_ERROR_MSG3","{error} 3",$RDP_ERROR_MSG3,true);
    $form[]=$tpl->field_text("RDP_ERROR_MSG4","{error} 4",$RDP_ERROR_MSG4,true);
    $form[]=$tpl->field_text("RDP_ERROR_MSG5","{error} 5",$RDP_ERROR_MSG5,true);
    $form[]=$tpl->field_text("RDP_ERROR_MSG6","{error} 6",$RDP_ERROR_MSG6,true);
    $form[]=$tpl->field_text("RDP_ERROR_MSG7","{error} 7",$RDP_ERROR_MSG7,true);
    $form[]=$tpl->field_text("RDP_ERROR_MSG8","{error} 8",$RDP_ERROR_MSG8,true);

    $html[]= $tpl->form_outside("{APP_RDPPROXY_AUTHHOOK} {parameters}",$form,null,"{apply}",null,"AsSquidAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}

function SaveSettings():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return admin_tracks_post("Save RDS Proxy Settings");
}

function status_top():bool{

    $page=CurrentPageName();
    $tpl=new template_admin();
    $RDPPROXY_AUTH_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPPROXY_AUTH_VERSION");
    $RDPPROXY_AUTH_LIB_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPPROXY_AUTH_LIB_VERSION");
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM groups WHERE enabled=1");

    $RDPPROXY_RULES=intval($ligne["tcount"]);

    $lib=new lib_memcached();
    $RDPPROXY_CNX=intval($lib->getKey("RDPPROXY_CNX"));
    $AUTHVERSION = $tpl->widget_h("green", "fas fa-info", "$RDPPROXY_AUTH_VERSION/$RDPPROXY_AUTH_LIB_VERSION", "{APP_RDPPROXY_AUTHHOOK}:{versions}");

    $AUTHCNX = $tpl->widget_h("lazur", "far fa-bolt", $tpl->FormatNumber($RDPPROXY_CNX), "{connections}");
    if($RDPPROXY_RULES>0){
        $RULESTATUS = $tpl->widget_h("green", "fas fa-list-ol", $tpl->FormatNumber($RDPPROXY_RULES), "{rules}");
    }else{
        $RULESTATUS = $tpl->widget_h("grey", "fas fa-list-ol", "{no_rule}", "{rules}");
    }

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$AUTHVERSION</td>";
    $html[]="<td style='width:33%;padding-left:10px'>$AUTHCNX</td>";
    $html[]="<td style='width:33%;padding-left:10px'>$RULESTATUS</td>";

    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $PrivoxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RDPPROXY_VERSION");

    $html=$tpl->page_header("{APP_RDPPROXY}","fas fa-tachometer-alt","{APP_RDPPROXY_EXPLAIN}",
        "$page?tabs=yes","events","progress-rdpproxy-restart",false,"table-rdpproxy-status");

    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{APP_RDPPROXY} v$PrivoxyVersion &raquo;&raquo; {service_status}",$html);
        echo $tpl->build_firewall();
        return;
    }


    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);

}

function status_authHook():string{
    $tpl=new template_admin();
    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/rdpproxy/auth/status");
    $json = json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }
    if (!$json->Status) {
        return $tpl->widget_rouge("{error}", $json->Error);
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $jsrestart=$tpl->framework_buildjs("/rdpproxy/auth/restart",
        "squid.rdpproxy.auth.progress","squid.rdpproxy.auth.progress.txt","progress-rdpproxy-restart");
    return $tpl->SERVICE_STATUS($ini, "APP_RDPPROXYHOOK",$jsrestart);
}

function status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]= status_rdpproxy();
    $html[]= status_authHook();
    $html[]="<script>";
    $html[]="LoadAjaxSilent('rdpproxy-top','$page?rdpproxy-top=yes');";
    $html[]="LoadAjaxSilent('rdpproxy-config','$page?settings=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body( $html);
    return true;
}
function status_rdpproxy():string{
    $tpl=new template_admin();
    $data = $GLOBALS["CLASS_SOCKETS"]->REST_API("/rdpproxy/status");
    $json = json_decode($data);

    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }
    if (!$json->Status) {
        return $tpl->widget_rouge("{error}", $json->Error);
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    $jsrestart=$tpl->framework_buildjs("/rdpproxy/restart",
        "squid.rdpproxy.progress","squid.rdpproxy.progress.txt","progress-rdpproxy-restart");
    return $tpl->SERVICE_STATUS($ini, "APP_RDPPROXY",$jsrestart);
}

function table(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:260px;vertical-align:top'>";
    $html[]="<div id='rdpproxy-status'></div>";
	$html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:15px;'>";
    $html[]="<div id='rdpproxy-top' style='margin-top: -8px'></div>";
    $html[]="<div id='rdpproxy-config'></div>";
    $html[]="</td>";
    $html[]="</tr>";

    $Interval=$tpl->RefreshInterval_js("rdpproxy-status",$page,"status=yes",3);

    $html[]="</table>";
    $html[]="<script>";
    $html[]=$Interval;
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function about(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div style='margin:40px'><H2>{about2} Wallix proxyRDP ReDemPtion</H2>";
    $tpl=new templates();


    $about=$tpl->_ENGINE_parse_body("{wallix_redemption}");


    $about=str_replace("%ChristopheGrosjean", "<a href=\"https://us.linkedin.com/pub/christophe-grosjean/8/5b6/8a8\" style='text-decoration:underline;font-weight:bold'>Christophe Grosjean</a>", $about);
    $about=str_replace("%Wallix","<a href=\"http://www.wallix.com\" style='text-decoration:underline;font-weight:bold'>Wallix</a>",$about);

    $html[]="<p style='margin-top: 20px'>$about</p></div>";

    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function file_uploaded(){
    $tpl = new template_admin();
    $page= CurrentPageName();
    $file = "/usr/share/artica-postfix/ressources/conf/upload/{$_GET["file-uploaded"]}";
    $destfile="/usr/share/artica-postfix/img/rdpproxy/{$_GET["file-uploaded"]}";
    if(!preg_match("#\.png$#",$_GET["file-uploaded"])){
        $tpl->js_error("Require a PNG format!");
        @unlink($file);
        return;
    }

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("rdpproxy.php?move-logo=".urlencode($_GET["file-uploaded"]));
    if(!is_file($destfile)){
        $tpl->js_error("Fatal error!");
        @unlink($file);
        return;
    }
    @unlink($file);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RDPProxyLogo","img/rdpproxy/{$_GET["file-uploaded"]}");
    echo "LoadAjax('rdpproxy-theme','$page?rdpproxy-theme=yes');\n";
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.rdpproxy.auth.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.rdpproxy.auth.progress.txt";
    $ARRAY["CMD"]="/rdpproxy/auth/restart";
    $ARRAY["TITLE"]="{restarting_service}";
    $ARRAY["AFTER"]="blur()";
    $prgress=base64_encode(serialize($ARRAY));
    $restartAuthService="Loadjs('fw.progress.php?content=$prgress&mainid=progress-rdpproxy-restart');";
    echo $restartAuthService."\n";
    return;

}