<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");
define("syslog_types",
        array(
            "all"=>"{all}",
            "squid"=>"{APP_SQUID}",
            "postfix"=>"{messagging}",
            "icap"=>"ICAP",
            "icap-sandbox"=>"{sandbox_connector}",
            "sshd"=>"{APP_OPENSSH}",
            "unbound"=>"{APP_UNBOUND}",
            "rustdesk"=>"{APP_RUSTDESK}",
            "dnsdist"=>"{APP_DNSDIST}",
            "rdpprody" => "{APP_RDPPROXY}",
            "keepalived" => "{APP_KEEPALIVED}",
            "ntpdate"=>"NTP Client",
            "syslog"=>"{APP_SYSLOGD}",
            "nginx"=>"{APP_NGINX}",
            "webcopy"=>"WebCopy",
            "kernel"=>"{system_kernel}",
            "webfiltering"=>"{webfiltering}",
            "postgres"=>"{APP_POSTGRES}",
            "admintrack"=>"{track_administrators}",
            "apt-mirror"=>"{APP_APT_MIRROR}",
            "artica-milter"=>"Artica Milter",
            "monitorfs-scan"=>"{APP_ARTICAFSMON}",
            "ntlm-monitor"=>"NTLM {watchdog}",
            "categories"=>"{your_categories}",
            "c-icap"=>"{SERVICE_WEBAVEX}",
            "http-antivirus"=>"{SERVICE_WEBAVEX} {DETECTED_THREATS}",
            "redis"=>"{APP_REDIS_SERVER}",
            "snapshots-backup"=>"{backup_artica_settings}",
            "dnsfw"=>"{APP_DNS_FIREWALL}",
            "firewall"=>"{APP_FIREWALL}",
            "strongswan"=>"IPSec VPN",
            "ecapav"=>"{integrated_antivirus}"
        )
);
if(isset($_POST["certificatepassword"])){certificate_password();exit;}

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["table"])){popup_table();exit;}
if(isset($_GET["service-js"])){service_js();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}

if(isset($_GET["install-js"])){install_js();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}

if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_POST["pattern-remove"])){rule_remove_confirm();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_POST["install"])){install_confirm();exit;}

if(isset($_GET["certificate-js"])){certificate_js();exit;}
if(isset($_GET["certificate-delete"])){certificate_delete_js();exit;}
if(isset($_POST["certificate-delete"])){certificate_delete_perform();exit;}

if(isset($_GET["file-uploaded"])){certificate_wizard_uploaded();exit;}
if(isset($_GET["certificate-wizard-js"])){certificate_wizard_js();exit;}
if(isset($_GET["certificate-wizard-popup"])){certificate_wizard_popup();exit;}
if(isset($_GET["config-ask-password"])){certificate_wizard_password();exit;}
if(isset($_POST["client_password"])){certificate_wizard_password_save();exit;}
if(isset($_GET["remove-all-js"])){remove_all_js();exit;}
if(isset($_POST["remove-all"])){remove_all_perform();exit;}
page();

function install_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();


    $install_daemon=$tpl->framework_buildjs(
        "/syslog/server/install","syslog.install.progress","syslog.install.progress.log","syslog-restart-status",
        "document.location.reload()");

    $tpl->js_confirm_execute("{APP_SYSLOG_SERVER_EXPLAIN}","install","yes",$install_daemon);

    return true;
}
function install_confirm():bool{
    admin_tracks("Installing Syslog service for receive events");
    return true;
}
function certificate_js():bool{
    $rule       = intval($_GET["certificate-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{rule}: $rule {certificate}";
    $tpl->js_dialog5("{remote_syslog_server}: $title","$page?certificate-popup=$rule");
    return true;
}
function certificate_delete_js(){
    $rule       = intval($_GET["certificate-delete"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_confirm_delete("{certificate}",
        "certificate-delete",$rule,
        "dialogInstance5.close();LoadAjax('section-syslog-rules','$page?table=yes');Loadjs('$page?rule-js=$rule')");
}

function certificate_wizard_js():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tpl->js_dialog5("{client_package}","$page?certificate-wizard-popup=yes");
    return true;
}
function certificate_wizard_popup(){
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $bt_upload=$tpl->button_upload("{client_package} (*.conf)",$page,null)."&nbsp;&nbsp;";

    $html="<div id='rsyslog-cert-wizard'>
            <p style='margin-top: 10px;margin-bottom: 10px'>{client_package_wizard_1}</p>
            <div class='center'>$bt_upload</div>
        </div>";
    echo $tpl->_ENGINE_parse_body($html);
}

function certificate_delete_perform(){
    $rule       = intval($_POST["certificate-delete"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $q          = new lib_sqlite("/home/artica/SQLITE/syslogrules.db");
    $q->QUERY_SQL("UPDATE rules SET pkcs12='', public_key='', certificate='' WHERE ID=$rule");
    admin_tracks("Remove certificate from syslog rule $rule");
}

function rule_js():bool{
    $rule       = intval($_GET["rule-js"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{rule}: $rule";
    if($rule==0){$title="{new_rule}";}
    $tpl->js_dialog5("{remote_syslog_server}: $title","$page?popup-rule=$rule");
    return true;
}


function certificate_wizard_uploaded():bool{
    $tpl=new template_admin();
    $page       = CurrentPageName();
    $file=$_GET["file-uploaded"];
    $uploaddir="/usr/share/artica-postfix/ressources/conf/upload";
    $fullpath="$uploaddir/$file";
    $data=@file_get_contents($fullpath);
    @unlink($fullpath);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RsyslogCertificatesConfig",$data);
    $tpl->js_dialog5("{passord}","$page?config-ask-password=yes");
    return true;
}
function certificate_wizard_password():bool{
    $tpl=new template_admin();
    $page       = CurrentPageName();
    $form[]=$tpl->field_password("client_password","{passphrase} (32 {characters})",null,true);

        $jsrestart=$tpl->framework_buildjs("syslog.php?apply-client-ssl=yes",
        "syslog.ssl.progress",
        "syslog.ssl.log",
        "client-ssl-create",
        "dialogInstance2.close();;"
    );


    $html[]="<div id='client-ssl-create'></div>";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,
        "{submit}",$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function certificate_wizard_password_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("RsyslogCertificatesPassword",$_POST["client_password"]);
    admin_tracks("Saving Certificate password for Log sink template");
    return true;

}

function rule_popup():bool{
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{new_rule}";
    $q          = new lib_sqlite("/home/artica/SQLITE/syslogrules.db");
    $ligne["enabled"] = 1;
    $ligne["proto"] = "udp";
    $ligne["port"] = "514";
    $bt="{add}";

    if(!$q->FIELD_EXISTS("rules","myhostname")){
        $q->QUERY_SQL("ALTER TABLE rules ADD myhostname TEXT NULL");
    }

    if(!$q->FIELD_EXISTS("rules","queue_size")){
        $q->QUERY_SQL("ALTER TABLE rules ADD queue_size INTEGER NOT NULL DEFAULT 1000000");
    }
    if(!$q->FIELD_EXISTS("rules","queue_size_mb")){
        $q->QUERY_SQL("ALTER TABLE rules ADD queue_size_mb INTEGER NOT NULL DEFAULT 100");
    }

    if(!$q->FIELD_EXISTS("rules","certificate")){
        $q->QUERY_SQL("ALTER TABLE rules ADD certificate TEXT");
    }
    if(!$q->FIELD_EXISTS("rules","public_key")){
        $q->QUERY_SQL("ALTER TABLE rules ADD public_key TEXT");
    }
    if(!$q->FIELD_EXISTS("rules","pkcs12")){
        $q->QUERY_SQL("ALTER TABLE rules ADD pkcs12 TEXT");
    }


    if($ruleid>0){ $ligne=$q->mysqli_fetch_array("SELECT * FROM rules WHERE ID=$ruleid");
        $bt="{apply}";
        $title      = $ruleid;
    }
    $syslog_types=syslog_types;
    $EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));
    if($EnablePostfixMultiInstance==1){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $results=$q->QUERY_SQL("SELECT * FROM postfix_instances ORDER BY instancename");
        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            $syslog_types["postfix-instance$ID"]=$ligne["instancename"];

        }

    }

    if(intval($ligne["queue_size"])==0){$ligne["queue_size"]="10000";}
    $jsrestart="dialogInstance5.close();LoadAjax('section-syslog-rules','$page?table=yes')";
    $form[]=$tpl->field_hidden("ruleid",$ruleid);
    $form[]=$tpl->field_checkbox("enabled","{enable}",$ligne["enabled"]);
    $form[]=$tpl->field_text("server","{remote_syslog_server}",$ligne["server"],true);
    $form[]=$tpl->field_numeric("port","{remote_port}",$ligne["port"]);
    $form[]=$tpl->field_text("myhostname","{displayed_hostname} ({optional})",$ligne["myhostname"],false);

    if($ligne["ssl"]==0) {
        $form[] = $tpl->field_array_hash(array("tcp" => "TCP/IP", "udp" => "UDP"), "proto", "{protocol}", $ligne["proto"]);
    }else{
        $form[] =$tpl->field_hidden("proto","tcp");
    }
    $form[]=$tpl->field_array_hash($syslog_types,"logtype","{type}",$ligne["logtype"]);
    $form[]=$tpl->field_section("{cache}");
    $form[]=$tpl->field_numeric("queue_size","{max_records_in_memory} ({events})",$ligne["queue_size"]);
    $form[]=$tpl->field_numeric("queue_size_mb","{queue_size} (MB)",$ligne["queue_size_mb"]);
    $html[]=$tpl->form_outside("{rule} $title",$form,null,$bt,$jsrestart,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function rule_remove():bool{
    $ruleid=intval($_GET["pattern-remove"]);
    $tpl        = new template_admin();
    $md         = $_GET["md"];
    $q          = new lib_sqlite("/home/artica/SQLITE/syslogrules.db");
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM rules WHERE ID=$ruleid");
    $server=$ligne["server"];
    $port=$ligne["port"];
    $name="$server:$port";
    $tpl->js_confirm_delete("{remote_syslog_server}: $name","pattern-remove",$ruleid,"$('#$md').remove();");
    return true;
}
function remove_all_js():bool{
    $tpl        = new template_admin();
    $page=CurrentPageName();
    return $tpl->js_confirm_delete("{delete_headers_regex_text}","remove-all","yes","LoadAjax('section-syslog-rules','$page?table=yes')");
}
function remove_all_perform():bool{
    $q          = new lib_sqlite("/home/artica/SQLITE/syslogrules.db");
    $q->QUERY_SQL("DELETE FROM rules");
    return admin_tracks("Remove all remote syslog rules");
}

function rule_remove_confirm():bool{
    $ruleid=intval($_POST["pattern-remove"]);
    $tpl        = new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/syslogrules.db");
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM rules WHERE ID=$ruleid");
    $server=$ligne["server"];
    $port=$ligne["port"];
    $name="$server:$port";
    $sql="DELETE FROM rules WHERE ID=$ruleid";
    $log="Remove remote syslog of $name";
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
    admin_tracks($log);

    return true;

}
function rule_enable():bool{
    $ruleid=intval($_GET["pattern-enable"]);
    $tpl        = new template_admin();
    $q          = new lib_sqlite("/home/artica/SQLITE/syslogrules.db");
    $ligne      = $q->mysqli_fetch_array("SELECT * FROM rules WHERE ID=$ruleid");
    $server=$ligne["server"];
    $port=$ligne["port"];
    $name="$server:$port";


    if(intval($ligne["enabled"])==1){
       $sql="UPDATE rules SET enabled=0 WHERE ID=$ruleid";
       $log="Set remote syslog of $name to disabled";
    }else{
        $sql="UPDATE rules SET enabled=1 WHERE ID=$ruleid";
        $log="Set remote syslog of $name to enabled";
    }
   $q->QUERY_SQL($sql);
   if(!$q->ok){echo $tpl->post_error($q->mysql_error);return false;}
   admin_tracks($log);

   return true;
}
function rule_save():bool{
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $ruleid     = intval($_POST["ruleid"]);
    unset($_POST["ruleid"]);
    if($_POST["logtype"]==null){$_POST["logtype"]="squid";}
    $fadd_log=array();
    foreach ($_POST as $key=>$value){
        $fadd_data[]="'$value'";
        $fadd_field[]="`$key`";
        $fadd_edit[]="`$key`='$value'";
        $fadd_log[]="$key:$value";
    }
    if($ruleid==0){
        $sql="INSERT INTO rules (".@implode(",",$fadd_field).") VALUES (".@implode(",",$fadd_data).")";
    }else{
        $sql="UPDATE rules SET ".@implode(",",$fadd_edit)." WHERE ID=$ruleid";
    }

    $q          = new lib_sqlite("/home/artica/SQLITE/syslogrules.db");
    if(!$q->FIELD_EXISTS("rules","myhostname")){
        $q->QUERY_SQL("ALTER TABLE rules ADD myhostname TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("rules","queue_size")){
        $q->QUERY_SQL("ALTER TABLE rules ADD queue_size INTEGER NO NULL DEFAULT 1000000");
    }
    if(!$q->FIELD_EXISTS("rules","queue_size_mb")){
        $q->QUERY_SQL("ALTER TABLE rules ADD queue_size_mb INTEGER NO NULL DEFAULT 100");
    }

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->post_error($q->mysql_error."<br>$sql");return false;}
    admin_tracks("Add/update syslog entry ".@implode(" ",$fadd_log));
    return true;

}

function popup_main():bool{
    $serviceid  = intval($_GET["popup-main"]);
    $page       = CurrentPageName();
    echo "<div id='main-popup-rewrite-$serviceid'></div>
    <script>LoadAjax('main-popup-rewrite-$serviceid','$page?popup-table=$serviceid')</script>";
    return true;
}

function page():bool{

    $page       = CurrentPageName();
    $html[]="<div id='section-syslog-rules' style='margin-top:20px'></div>";
    $html[]="<script>LoadAjax('section-syslog-rules','$page?table=yes')</script>";
    echo @implode("\n",$html);
    return true;
}

function popup_table():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $tableid    = time();
    $TRCLASS    = null;
    $q          = new lib_sqlite("/home/artica/SQLITE/syslogrules.db");

    $ActAsASyslogServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsASyslogServer"));


    $rebuild=$tpl->framework_buildjs(
        "syslog.php?rebuild-all=yes","syslog.daemons.progress","syslog.daemons.progress.progress.log","syslog-restart-status",
        "LoadAjax('section-syslog-rules','$page?table=yes')");


    $topbuttons[]=array("Loadjs('$page?rule-js=0');","fa fa-plus","{new_rule}");
    $topbuttons[]=array($rebuild,"fa fa-save","{apply_parameters}");
    if($ActAsASyslogServer==0) {
        $topbuttons[] = array("Loadjs('$page?install-js=yes');", ico_cd, "{install}:{APP_SYSLOGD}");
    }

    $topbuttons[]=array("Loadjs('$page?certificate-wizard-js=yes');","fa-solid fa-file-certificate","{client_package}");
    $topbuttons[]=array("Loadjs('$page?remove-all-js=yes');",ico_trash,"{delete_headers_regex}");
//delete_headers_regex_text


    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:240px;vertical-align:top'><div id='syslog-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;vertical-align:top;padding-left:10px'>";
    $html[]="<div id='progress-compile-replace-$tableid' style='margin-top:20px'></div>";
    $html[]="<div id='syslog-restart-status'></div>";
    $html[]=$tpl->table_head(array("{ID}","{remote_syslog_server}","{type}",
        "{events}","{enabled}","DEL"),"table-$tableid");


    $SYSLOG_SEND_RULE_STATS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSLOG_SEND_RULE_STATS"));

    if(!$q->FIELD_EXISTS("rules","certificate")){
        $q->QUERY_SQL("ALTER TABLE rules ADD certificate TEXT");
    }
    if(!$q->FIELD_EXISTS("rules","public_key")){
        $q->QUERY_SQL("ALTER TABLE rules ADD public_key TEXT");
    }
    if(!$q->FIELD_EXISTS("rules","pkcs12")){
        $q->QUERY_SQL("ALTER TABLE rules ADD pkcs12 TEXT");
    }



    $results=$q->QUERY_SQL("SELECT * FROM rules ORDER BY ID DESC LIMIT 250");
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $syslog_types=syslog_types;
    $PROCESSED=0;
    $LogSinkClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClient"));
    $LogSinkClientPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientPort"));
    $LogSinkClientServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinClientServer"));
    $LogSinkClientTCP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientTCP"));
    $LogSinkClientQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientQueue"));
    $LogSinkClientQueueSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkClientQueueSize"));
    if($LogSinkClientQueue==0){$LogSinkClientQueue=10000;}
    if($LogSinkClientQueueSize==0){$LogSinkClientQueueSize=1000;}
    if($LogSinkClientPort==0){$LogSinkClientPort=514;}
    if($LogSinkClientServer==null){
        $pattern="{inactive2}";
    }else{
        $proto="udp";
        if($LogSinkClientTCP==1){$proto="tcp";}
        $pattern="$proto://$LogSinkClientServer:$LogSinkClientPort";
    }

    $enable=$tpl->icon_check($LogSinkClient,"Loadjs('$page?LogSinkClient-enable=yes')","","AsSystemAdministrator");

    if($LogSinkClient==1) {
        if (isset($SYSLOG_SEND_RULE_STATS[0])) {
            $PROCESSED = $tpl->FormatNumber($SYSLOG_SEND_RULE_STATS[0]["PROCESSED"]);
            $FAILED = $tpl->FormatNumber($SYSLOG_SEND_RULE_STATS[0]["FAILED"]);
            $SUSPEND = $tpl->FormatNumber($SYSLOG_SEND_RULE_STATS[0]["SUSPEND"]);
        }
    }
    $pattern=$tpl->td_href($pattern,null,"Loadjs('fw.proxy.rotate.php?main-logsink-js=yes')");
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $html[]="<tr class='$TRCLASS' id='none'>";
    $html[]="<td width=1% nowrap>0</td>";
    $html[]="<td width=50% ><strong>$pattern</strong></td>";
    $html[]="<td width=1% nowrap>{all}</td>";
    $html[]="<td width=1% style='text-align: right' nowrap><span id='syslog-processed-0'>$PROCESSED</span></td>";
    $html[]="<td width=1% nowrap>$enable</td>";
    $html[]="<td width=1% nowrap>&nbsp;</td>";
    $html[]="</tr>";
    $html[]=squid_remoteLogging($TRCLASS);


    foreach ($results as $index=>$ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $type=$ligne["logtype"];
        $PROCESSED=0;$FAILED=0;$SUSPEND=0;
        $enable=intval($ligne["enabled"]);
        $ssl=trim($ligne["ssl"]);
        $server=trim($ligne["server"]);
        $proto=$ligne["proto"];
        $port=intval($ligne["port"]);
        $ID=intval($ligne["ID"]);
        $md=md5(serialize($ligne));
        $myhostname=trim($ligne["myhostname"]);
        if($myhostname<>null){$myhostname="&nbsp;<small>($myhostname)</small>";}
        if($ssl==1){$proto="tcp";}
        $explain="$proto://$server:$port";
        $pattern=$tpl->td_href($explain,null,"Loadjs('$page?rule-js=$ID')");
        $ssltext=null;
        if($ssl==1){
            $certificate=$ligne["certificate"];
            $public_key=$ligne["public_key"];
            $strlenpkcs12=strlen($ligne["pkcs12"]);
            $ssltext="&nbsp;<span class='label label-warning'>SSL</span>";
            if(strlen($certificate)<50){
                $ssltext="&nbsp;".$tpl->td_href("<span class='label label-danger'>{missing_certificate}</span>",null,"Loadjs('$page?certificate-js=$ID')");
            }
            if(strlen($public_key)<50){
                $ssltext="&nbsp;".$tpl->td_href("<span class='label label-danger'>{missing_certificate}</span>",null,"Loadjs('$page?certificate-js=$ID')");
            }

        }
        if(isset($SYSLOG_SEND_RULE_STATS[$ID])){
            $PROCESSED=$tpl->FormatNumber($SYSLOG_SEND_RULE_STATS[$ID]["PROCESSED"]);
            $FAILED=$tpl->FormatNumber($SYSLOG_SEND_RULE_STATS[$ID]["FAILED"]);
            $SUSPEND=$tpl->FormatNumber($SYSLOG_SEND_RULE_STATS[$ID]["SUSPEND"]);
        }

        $enable=$tpl->icon_check($enable,"Loadjs('$page?pattern-enable=$ID')","","AsSystemAdministrator");
        $delete=$tpl->icon_delete("Loadjs('$page?pattern-remove=$ID&md=$md')","AsSystemAdministrator");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width=1% nowrap>$ID</td>";
        $html[]="<td width=50% ><strong>$pattern</strong>$ssltext$myhostname</td>";
        $html[]="<td width=1% nowrap>$syslog_types[$type]</td>";
        $html[]="<td width=1% style='text-align: right' nowrap><span id='syslog-processed-$ID'>$PROCESSED</span></td>";
        $html[]="<td width=1% nowrap>$enable</td>";
        $html[]="<td width=1% nowrap>$delete</td>";
        $html[]="</tr>";

    }
        $html[]=$tpl->table_footer("table-$tableid",6,false);

    $html[]="</table>";
    $html[]="</tr>";
    $html[]="</table>";
    $TINY_ARRAY["TITLE"]="{APP_SYSLOGD} v{$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYSLOGD_VERSION")}: {remote_logging}";
    $TINY_ARRAY["ICO"]="fa-solid fa-sensor-on";
    $TINY_ARRAY["EXPL"]="{APP_SYSLOGD_REMOTE_EXPLAIN}";
    $TINY_ARRAY["URL"]=null;
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $Enablehacluster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"));
    if($Enablehacluster==1){
        $TINY_ARRAY["BUTTONS"]="";
    }


    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="LoadAjax('syslog-status','$page?status=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function squid_remoteLogging($TRCLASS):string{

    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){
        return "";
    }
    $tpl        = new template_admin();
    $page=CurrentPageName();
    $array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSyslogAdd")));

    $Enabled=intval($array["ENABLE"]);
    if ($Enabled==0){
        return "";
    }
    $RemoteSyslogAddr = $array["SERVER"];
    $RemoteSyslogPort=intval($array["RemoteSyslogPort"]);
    if ($RemoteSyslogPort == 0) {
        $RemoteSyslogPort = 514;
    }
    $UseTCPPort=intval($array["UseTCPPort"]);
    $proto="udp";
    if($UseTCPPort==1){
        $proto="tcp";
    }
    $pattern="$proto://$RemoteSyslogAddr:$RemoteSyslogPort";

    $PROCESSED="-";
    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $pattern=$tpl->td_href($pattern,null,"Loadjs('fw.proxy.general.php?squid-syslog-js=yes')");

    $enable=$tpl->icon_check(1,"","","AsSystemAdministrator");

    $html[]="<tr class='$TRCLASS' id='none'>";
    $html[]="<td width=1% nowrap>0</td>";
    $html[]="<td width=50% ><strong>$pattern</strong></td>";
    $html[]="<td width=1% nowrap>{APP_SQUID}</td>";
    $html[]="<td width=1% style='text-align: right' nowrap><span id='syslog-processed-squid'></span></td>";
    $html[]="<td width=1% nowrap>$enable</td>";
    $html[]="<td width=1% nowrap>&nbsp;</td>";
    $html[]="</tr>";
    return @implode("\n",$html);
}

function ServiceStatus():string{
    $tpl        = new template_admin();
    $sock=new sockets();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syslog/mybin"));
    if(!$json->Status){
        $btn[0]["name"]="{install}";
        $btn[0]["icon"]=ico_cd;
        $btn[0]["js"]="Loadjs('fw.system.upgrade-software.php?product=APP_SYSLOGD')";
        return  $tpl->widget_rouge("{error}",$json->Error,$btn);

    }

    $data=$sock->REST_API("/syslog/status");
    $json=json_decode($data);
    $page       = CurrentPageName();
    $jsrestart=$tpl->framework_buildjs("/syslog/reconfigure","syslog.restart.progress","syslog.restart.log","syslog-restart-status","LoadAjax('syslog-status','$page?status=yes');");

    if (json_last_error()> JSON_ERROR_NONE) {
        return  $tpl->widget_rouge("{error}",json_last_error_msg());
    }
    if (!$json->Status) {
        return $tpl->widget_rouge("{error}", $json->Error);
    }
    $ini=new Bs_IniHandler();
    $ini->loadString($json->Info);
    return $tpl->SERVICE_STATUS($ini, "APP_RSYSLOG",$jsrestart);
}

function status():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();

    echo ServiceStatus();

    $rsyslog_queuesize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("rsyslog_queue_size"));
    $jsClean=$tpl->framework_buildjs("/syslog/cleanqueues",
        "rsyslog.clean.progress","rsyslog.clean.log","syslog-restart-status","LoadAjax('syslog-status','$page?status=yes');");
    if($rsyslog_queuesize>50350){


        $btn[0]["name"] = "{clean}";
        $btn[0]["icon"] = ico_trash;
        $btn[0]["js"] = $jsClean;

        echo $tpl->widget_jaune("{queue_size}",FormatBytes($rsyslog_queuesize/1024). "- $rsyslog_queuesize",$btn);

    }else{
        echo $tpl->widget_vert("{queue_size}","{empty}");
    }


    $f[]="<script>";
    $f[]="function RestartStatusSylog(){";
    $f[]="if ( !document.getElementById('syslog-status') ) { return;}";
    $f[]="LoadAjaxSilent('syslog-status','$page?status=yes');";
    $f[]="}";

    $SYSLOG_SEND_RULE_STATS=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SYSLOG_SEND_RULE_STATS"));
    foreach ($SYSLOG_SEND_RULE_STATS as $ruleid=>$array){
        if(!isset($array["PROCESSED"])){$array["PROCESSED"]=0;}
        $PROCESSED=$tpl->FormatNumber(intval($array["PROCESSED"]));
        $f[]="if ( document.getElementById('syslog-processed-$ruleid') ) {";
        $f[]="\tdocument.getElementById('syslog-processed-$ruleid').innerHTML='$PROCESSED';";
        $f[]="}";
    }

    $f[]="setTimeout(\"RestartStatusSylog()\",3000);";
    $f[]="</script>";
    echo @implode("\n",$f);
    return true;
}