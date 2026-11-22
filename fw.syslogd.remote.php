<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
include_once(dirname(__FILE__)."/ressources/class.nginx.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.syslog.tls.inc");
include_once(dirname(__FILE__)."/ressources/class.articarest.rest.inc");
define("syslog_types",
    array(
        "all"=>"{all}",
        "squid"=>"{APP_SQUID}",
        //"postfix"=>"{messagging}",
        //"icap"=>"ICAP",
        //"icap-sandbox"=>"{sandbox_connector}",
        "sshd"=>"{APP_OPENSSH}",
        "unbound"=>"{APP_UNBOUND}",
        //"rustdesk"=>"{APP_RUSTDESK}",
        "dnsdist"=>"{APP_DNSDIST}",
        "rdpproxy" => "{APP_RDPPROXY}", // journaux du bastion RDS (00_rdpproxy.conf, RWE 2026-11-02)
        "keepalived" => "{APP_KEEPALIVED}",
        "keepalived-checker" => "{APP_KEEPALIVED} Checker",
        //"ntpdate"=>"NTP Client",
        "syslog"=>"{APP_SYSLOGD}",
        //"nginx"=>"{APP_NGINX}",
        //"webcopy"=>"WebCopy",
        "kernel"=>"{system_kernel}",
        "webfiltering"=>"{webfiltering}",
        "postgres"=>"{APP_POSTGRES}",
        "admintrack"=>"{track_administrators}",
        //"apt-mirror"=>"{APP_APT_MIRROR}",
        //"artica-milter"=>"Artica Milter",
        //"monitorfs-scan"=>"{APP_ARTICAFSMON}",
        //"ntlm-monitor"=>"NTLM {watchdog}",
        //"categories"=>"{your_categories}",
        "c-icap"=>"{SERVICE_WEBAVEX}",
        "http-antivirus"=>"{SERVICE_WEBAVEX} {DETECTED_THREATS}",
        //"redis"=>"{APP_REDIS_SERVER}",
        //"snapshots-backup"=>"{backup_artica_settings}",
        "dnsfw"=>"{APP_DNS_FIREWALL}",
        "firewall"=>"{APP_FIREWALL}",
        "strongswan"=>"IPSec VPN",
        //"ecapav"=>"{integrated_antivirus}",
        "suricata"=>"{IDS}",
        "auth"=>"{authentication}",
        "freshclam"=>"{APP_FRESHCLAM}",
        "clamd"=>"{APP_CLAMAV}",
        "proxy_geoip"=>"{geoip}",
        "immark"=>"immark"
    )
);
// Regles de transfert : lues et ecrites par articarest (logsink/http_remote_rules.go, RWE 2026-11-02).
// Plus aucune ecriture PHP de syslogrules.db (l'ancienne requete etait batie depuis les noms POST).
function syslog_rules_list(&$error){
    $r=artrest_get("/syslog/remote/rules");
    $error=artrest_error($r);
    if($error!=""){return array();}
    return is_array($r["Rules"] ?? null)?$r["Rules"]:array();
}
function syslog_rule_get($id,&$error){
    $r=artrest_get("/syslog/remote/rules/".intval($id));
    $error=artrest_error($r);
    if($error!=""){return array();}
    return is_array($r["Rule"] ?? null)?$r["Rule"]:array();
}
// Forme des lignes attendue par les fonctions d'affichage (anciennes colonnes SQLite).
function syslog_rule_row($rule){
    return array("ID"=>intval($rule["id"] ?? 0),"server"=>(string)($rule["server"] ?? ""),"port"=>intval($rule["port"] ?? 514),
        "proto"=>(string)($rule["proto"] ?? "udp"),"logtype"=>(string)($rule["logtype"] ?? ""),
        "myhostname"=>(string)($rule["myhostname"] ?? ""),"enabled"=>!empty($rule["enabled"])?1:0,
        "queue_size"=>intval($rule["queue_size"] ?? 0),"queue_size_mb"=>intval($rule["queue_size_mb"] ?? 0),
        "ssl"=>!empty($rule["ssl"])?1:0,"certificate_set"=>!empty($rule["certificate_set"]),"public_key_set"=>!empty($rule["public_key_set"]));
}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["sylr-host"])){sylr_host();exit;}
if(isset($_GET["clean-queue-js"])){clean_queue_js();exit;}
if(isset($_POST["clean-queue"])){clean_queue_confirm();exit;}
if(isset($_GET["table"])){popup_table();exit;}
if(isset($_GET["popup-main"])){popup_main();exit;}

if(isset($_GET["install-js"])){install_js();exit;}
if(isset($_GET["rule-js"])){rule_js();exit;}
if(isset($_GET["popup-rule"])){rule_popup();exit;}
if(isset($_POST["ruleid"])){rule_save();exit;}

if(isset($_GET["pattern-remove"])){rule_remove();exit;}
if(isset($_POST["pattern-remove"])){rule_remove_confirm();exit;}
if(isset($_GET["pattern-enable"])){rule_enable();exit;}
if(isset($_POST["install"])){install_confirm();exit;}

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
    if(!sylr_require_admin()){return false;}
    admin_tracks("Installing Syslog service for receive events");
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
    if(!sylr_require_admin()){return;}
    $rule       = intval($_POST["certificate-delete"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $error=artrest_error(artrest_post("/syslog/remote/rules/$rule/certificate/delete",array()));
    if($error!=""){echo $tpl->post_error($error);return;}
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
    if(!sylr_is_admin()){header("content-type: application/x-javascript");$tpl->js_error("{ERROR_NO_PRIVS2}");return false;}
    $page       = CurrentPageName();
    // basename : le nom vient de la requete, jamais un chemin.
    $file=basename((string)$_GET["file-uploaded"]);
    $uploaddir="/usr/share/artica-postfix/ressources/conf/upload";
    $fullpath="$uploaddir/$file";
    $data=($file<>"" && is_file($fullpath))?(string)@file_get_contents($fullpath,false,null,0,262144):"";
    if(is_file($fullpath)){@unlink($fullpath);}
    // Le paquet porte une cle privee chiffree : il reste en session, pas dans les reglages.
    $_SESSION["SYSLOG_TLS_IMPORT"]=$data;
    $tpl->js_dialog5("{passphrase}","$page?config-ask-password=yes");
    return true;
}
function certificate_wizard_password():bool{
    $tpl=new template_admin();
    $page       = CurrentPageName();
    $form[]=$tpl->field_password("client_password","{passphrase} (32 {characters})",null,true);
    $jsafter="dialogInstance5.close();if(window.SylrReload){SylrReload();}";
    $html[]="<div id='client-ssl-create'></div>";
    $html[]=$tpl->form_outside(null, @implode("\n", $form),null,
        "{submit}",$jsafter,"AsSystemAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function certificate_wizard_password_save():bool{
    if(!sylr_require_admin()){return false;}
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $package=isset($_SESSION["SYSLOG_TLS_IMPORT"])?(string)$_SESSION["SYSLOG_TLS_IMPORT"]:"";
    if($package==""){
        echo $tpl->post_error("{client_package}: {no_data}");
        return false;
    }
    $error=syslogtls_client_import($package,$_POST["client_password"]);
    if($error<>""){
        echo $tpl->post_error($error);
        return false;
    }
    unset($_SESSION["SYSLOG_TLS_IMPORT"]);
    admin_tracks("Importing a Log sink client package (TLS syslog rule)");
    return true;

}

function rule_popup():bool{
    $ruleid     = intval($_GET["popup-rule"]);
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $title      = "{new_rule}";
    $ligne=array("enabled"=>1,"proto"=>"udp","port"=>"514","server"=>"","myhostname"=>"","logtype"=>"","queue_size"=>0,"queue_size_mb"=>0,"ssl"=>0);
    $bt="{add}";
    if($ruleid>0){
        $error="";
        $rule=syslog_rule_get($ruleid,$error);
        if($error!=""){echo $tpl->div_error(htmlspecialchars($error,ENT_QUOTES));return false;}
        $ligne=syslog_rule_row($rule);
        $bt="{apply}";
        $title      = $ruleid;
    }
    $syslog_types=syslog_types;
    $EnablePostfixMultiInstance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));
    if($EnablePostfixMultiInstance==1){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $results=$q->QUERY_SQL("SELECT * FROM postfix_instances ORDER BY instancename");
        // $inst, pas $ligne : la boucle ecrasait les valeurs de la regle affichee dans le formulaire
        foreach ($results as $index=>$inst){
            $ID=$inst["ID"];
            $syslog_types["postfix-instance$ID"]=$inst["instancename"];

        }

    }

    if(intval($ligne["queue_size"])==0){$ligne["queue_size"]="10000";}
    $jsrestart="dialogInstance5.close();if(window.SylrReload){SylrReload();}";
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
    $error="";
    $ligne      = syslog_rule_row(syslog_rule_get($ruleid,$error));
    $name=htmlspecialchars($ligne["server"].":".$ligne["port"],ENT_QUOTES);
    $tpl->js_confirm_delete("{remote_syslog_server}: $name","pattern-remove",$ruleid,"if(window.SylrReload){SylrReload();}");
    return true;
}
function remove_all_js():bool{
    $tpl        = new template_admin();
    $page=CurrentPageName();
    return $tpl->js_confirm_delete("{sylr_delete_all_confirm}","remove-all","yes","if(window.SylrReload){SylrReload();}");
}
function remove_all_perform():bool{
    if(!sylr_require_admin()){return false;}
    $tpl        = new template_admin();
    $error=artrest_error(artrest_post("/syslog/remote/rules/delete-all",array()));
    if($error!=""){echo $tpl->post_error($error);return false;}
    return admin_tracks("Remove all remote syslog rules");
}

function rule_remove_confirm():bool{
    if(!sylr_require_admin()){return false;}
    $ruleid=intval($_POST["pattern-remove"]);
    $tpl        = new template_admin();
    $error="";
    $ligne      = syslog_rule_row(syslog_rule_get($ruleid,$error));
    $name=$ligne["server"].":".$ligne["port"];
    $error=artrest_error(artrest_delete("/syslog/remote/rules/$ruleid"));
    if($error!=""){echo $tpl->post_error($error);return false;}
    admin_tracks("Remove remote syslog of $name");

    return true;

}
function rule_enable():bool{
    $ruleid=intval($_GET["pattern-enable"]);
    $tpl        = new template_admin();
    header("content-type: application/x-javascript");
    if(!sylr_is_admin()){$tpl->js_error("{ERROR_NO_PRIVS2}");return false;}
    $error="";
    $ligne      = syslog_rule_row(syslog_rule_get($ruleid,$error));
    if($error!=""){echo $tpl->js_mysql_alert($error);return false;}
    $name=$ligne["server"].":".$ligne["port"];
    $action=$ligne["enabled"]==1?"disable":"enable";
    $error=artrest_error(artrest_post("/syslog/remote/rules/$ruleid/$action",array()));
    if($error!=""){echo $tpl->js_mysql_alert($error);return false;}
    admin_tracks("Set remote syslog of $name to {$action}d");

   return true;
}
function rule_save():bool{
    if(!sylr_require_admin()){return false;}
    $tpl        = new template_admin();
    $tpl->CLEAN_POST();
    $ruleid     = intval($_POST["ruleid"]);
    $fields=array();
    foreach (array("server","port","proto","logtype","myhostname","enabled","queue_size","queue_size_mb") as $k){
        if(isset($_POST[$k])){$fields[$k]=trim((string)$_POST[$k]);}
    }
    if(($fields["logtype"] ?? "")===""){$fields["logtype"]="squid";}
    if($ruleid==0){
        $error=artrest_error(artrest_post("/syslog/remote/rules",$fields));
    }else{
        $error=artrest_error(artrest_put("/syslog/remote/rules/$ruleid",$fields));
    }
    if($error!=""){echo $tpl->post_error($error);return false;}
    $log=array();
    foreach ($fields as $k=>$v){$log[]="$k:$v";}
    admin_tracks("Add/update syslog entry ".@implode(" ",$log));
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

// ---------------------------------------------------------------------------
// Vue principale (maquette stitch_syslog_console_redesign, 2026-11-02).
// ?table=yes pose la grille : a gauche le widget d'etat du service (SERVICE_STATUS,
// recharge toutes les 3 s par status()), a droite un hote rafraichi toutes les 15 s
// (?sylr-host=yes) qui porte la file locale, le log sink central et les regles.
// Le TINY n'est emis qu'ici : l'hote ne le rejoue jamais.
// ---------------------------------------------------------------------------
function popup_table():bool{
    $tpl        = new template_admin();
    $page       = CurrentPageName();
    $ActAsASyslogServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActAsASyslogServer"));

    $rebuild=$tpl->framework_buildjs(
        "/syslog/reconfigure","syslog.restart.progress",
        "syslog.restart.progress.log","syslog-restart-status",
        "if(window.SylrReload){SylrReload();}");

    $topbuttons=array();
    $topbuttons[]=array("Loadjs('$page?rule-js=0');",ico_plus,"{new_rule}");
    $topbuttons[]=array($rebuild,ico_save,"{apply_parameters}");
    if($ActAsASyslogServer==0) {
        $topbuttons[] = array("Loadjs('$page?install-js=yes');", ico_cd, "{install}:{APP_SYSLOGD}");
    }
    $topbuttons[]=array("Loadjs('$page?certificate-wizard-js=yes');","fa-solid fa-file-certificate","{client_package}");
    $topbuttons[]=array("Loadjs('$page?remove-all-js=yes');",ico_trash,"{sylr_delete_all}");

    $html=array();
    $html[]="<div class='ak sylr-grid'>";
    $html[]="<div class='sylr-left'><div id='syslog-status'></div></div>";
    $html[]="<div class='sylr-right'>";
    $html[]="<div id='syslog-restart-status'></div>";
    $html[]="<div id='sylr-host'>".sylr_host_html()."</div>";
    $html[]="</div>";
    $html[]="</div>";

    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_SYSLOGD} v{$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_SYSLOGD_VERSION")}: {remote_logging}";
    $TINY_ARRAY["ICO"]="fa-solid fa-sensor-on";
    $TINY_ARRAY["EXPL"]="{APP_SYSLOGD_REMOTE_EXPLAIN}";
    $TINY_ARRAY["URL"]=null;
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Enablehacluster"))==1){
        $TINY_ARRAY["BUTTONS"]="";
    }
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    $script="<script>NoSpinner();\nLoadAjaxSilent('syslog-status','$page?status=yes');\n$jstiny</script>";
    echo sylr_style().$tpl->_ENGINE_parse_body(@implode("\n",$html)).sylr_js().$script;
    return true;
}
function sylr_host():bool{
    echo sylr_host_html();
    return true;
}
// Contenu de l'hote : deja analyse (tokens resolus), sans <style> ni script de timer.
function sylr_host_html():string{
    $tpl=new template_admin();
    $m=sylr_model();
    $html=array();
    $html[]="<div class='sylr-strip'>";
    $html[]=sylr_card_queue($m);
    $html[]=sylr_card_logsink($m);
    $html[]="</div>";
    $html[]=sylr_rules_card($m);
    return $tpl->_ENGINE_parse_body(@implode("\n",$html));
}
// Toutes les donnees de la vue, lues une seule fois.
function sylr_model():array{
    $sock=$GLOBALS["CLASS_SOCKETS"];
    $m=array();
    $error="";
    $rules=array();
    foreach (syslog_rules_list($error) as $rule){$rules[]=syslog_rule_row($rule);}
    $m["rules"]=array_reverse($rules); // plus recentes d'abord
    $m["error"]=$error;
    $stats=@unserialize((string)$sock->GET_INFO("SYSLOG_SEND_RULE_STATS"));
    $m["stats"]=is_array($stats)?$stats:array();
    $m["queue"]=intval($sock->GET_INFO("rsyslog_queue_size"));
    $m["hacluster"]=intval($sock->GET_INFO("Enablehacluster"))==1;

    $port=intval($sock->GET_INFO("LogSinkClientPort"));
    if($port==0){$port=514;}
    $m["sink_enabled"]=intval($sock->GET_INFO("LogSinkClient"))==1;
    $m["sink_server"]=trim((string)$sock->GET_INFO("LogSinClientServer"));
    $m["sink_port"]=$port;
    $m["sink_proto"]=intval($sock->GET_INFO("LogSinkClientTCP"))==1?"TCP":"UDP";

    $m["squid"]=array();
    if(intval($sock->GET_INFO("SQUIDEnable"))==1){
        $sq=@unserialize(base64_decode((string)$sock->GET_INFO("SquidSyslogAdd")));
        if(is_array($sq) && intval($sq["ENABLE"] ?? 0)==1){
            $sport=intval($sq["RemoteSyslogPort"] ?? 0);
            if($sport==0){$sport=514;}
            $m["squid"]=array("server"=>trim((string)($sq["SERVER"] ?? "")),"port"=>$sport,
                "proto"=>intval($sq["UseTCPPort"] ?? 0)==1?"TCP":"UDP");
        }
    }
    return $m;
}
// Compteurs d'une regle (impstats de rsyslog, cumules depuis son demarrage).
function sylr_rule_stats(array $m,int $id):array{
    $st=array("sent"=>0,"failed"=>0,"suspended"=>0,"known"=>false);
    if(!isset($m["stats"][$id]) || !is_array($m["stats"][$id])){return $st;}
    $r=$m["stats"][$id];
    $st["sent"]=intval($r["PROCESSED"] ?? 0);
    $st["failed"]=intval($r["FAILED"] ?? 0);
    $st["suspended"]=intval($r["SUSPEND"] ?? 0);
    $st["known"]=true;
    return $st;
}
// Sante deduite des seuls compteurs : jamais de joignabilite inventee.
function sylr_health(bool $enabled,array $st):array{
    if(!$enabled){return array("off","fa-solid fa-circle-minus","{sylr_h_disabled}");}
    if($st["failed"]>0){return array("err","fa-solid fa-circle-xmark","{sylr_h_failing}");}
    if($st["suspended"]>0){return array("warn","fa-solid fa-pause","{sylr_h_suspended}");}
    if($st["sent"]>0){return array("ok","fa-solid fa-circle-check","{sylr_h_ok}");}
    return array("idle","fa-solid fa-hourglass-half","{sylr_h_noevents}");
}
function sylr_card_queue(array $m):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $bytes=$m["queue"];
    $size=$bytes==0?"0 B":FormatBytes($bytes/1024,true);
    $badge="<span class='ak-pill ak-ok'><i class='fa-solid fa-check'></i> {empty}</span>";
    $cls="";
    $btn="";
    if($bytes>0){
        $badge="<span class='ak-pill ak-off'><i class='fa-solid fa-inbox'></i> {sylr_queue_pending}</span>";
    }
    // Seuil d'origine de la page : au-dela, rsyslog n'arrive plus a vider sa file.
    if($bytes>50350){
        $cls=" sylr-card-warn";
        $badge="<span class='ak-pill ak-warn'><i class='fa-solid fa-triangle-exclamation'></i> {sylr_queue_backlog}</span>";
        if(!$m["hacluster"]){
            $btn="<button type='button' class='ak-link' OnClick=\"Loadjs('$page?clean-queue-js=yes');\">{sylr_clean}</button>";
        }
    }
    $h=array();
    $h[]="<div class='ak-card sylr-card$cls'>";
    $h[]="<span class='sylr-card-ico'><i class='fa-solid fa-inbox'></i></span>";
    $h[]="<div class='sylr-card-body'>";
    $h[]="<div class='sylr-card-title'>{sylr_queue_title}</div>";
    $h[]="<div class='sylr-card-value'><span class='sylr-big'>".htmlspecialchars($size)."</span>$badge</div>";
    $h[]="<div class='sylr-card-sub'>{sylr_queue_sub}</div>";
    $h[]="</div>";
    $h[]=$btn;
    $h[]="</div>";
    return @implode("",$h);
}
function sylr_card_logsink(array $m):string{
    $tpl=new template_admin();
    $active=$m["sink_enabled"] && $m["sink_server"]!="";
    $h=array();
    $h[]="<div class='ak-card sylr-card".($active?"":" sylr-card-off")."'>";
    $h[]="<span class='sylr-card-ico'><i class='fa-solid fa-tower-broadcast'></i></span>";
    $h[]="<div class='sylr-card-body'>";
    $h[]="<div class='sylr-card-title'>{sylr_logsink}</div>";
    if($active){
        $st=sylr_rule_stats($m,0);
        $h[]="<div class='sylr-card-value'><span class='ak-tag'>{$m["sink_proto"]}</span>"
            ."<span class='sylr-dest'>".htmlspecialchars($m["sink_server"].":".$m["sink_port"])."</span></div>";
        if($st["known"]){
            $h[]="<div class='sylr-card-sub'>{sylr_sent} ".$tpl->FormatNumber($st["sent"])
                ." &middot; <span class='".($st["failed"]>0?"sylr-txt-err":"")."'>{sylr_failed} ".$tpl->FormatNumber($st["failed"])."</span></div>";
        }else{
            $h[]="<div class='sylr-card-sub'>{sylr_logsink_sub}</div>";
        }
    }else{
        $h[]="<div class='sylr-card-value'><span class='ak-pill ak-off'><i class='fa-solid fa-circle-minus'></i> {inactive2}</span></div>";
        $h[]="<div class='sylr-card-sub'>{sylr_logsink_sub}</div>";
    }
    $h[]="</div>";
    $h[]="<a class='ak-link' href=\"javascript:blur();\" OnClick=\"Loadjs('fw.proxy.rotate.php?main-logsink-js=yes');\">{sylr_configure}</a>";
    $h[]="</div>";
    return @implode("",$h);
}
function sylr_rules_card(array $m):string{
    $h=array();
    $count=count($m["rules"]);
    $h[]="<section class='ak-card sylr-rules'>";
    $h[]="<div class='sylr-rules-head'>";
    $h[]="<div class='sylr-rules-title'><h3>{sylr_forwarding_rules}</h3><span class='ak-count'>$count {sylr_rules}</span></div>";
    $h[]="<div class='sylr-note'><i class='fa-solid fa-circle-info'></i> {sylr_counters_note}</div>";
    $h[]="</div>";
    if($m["error"]!=""){
        $h[]="<div class='ak-banner ak-err sylr-error'><i class='fa-solid fa-circle-exclamation'></i> ".htmlspecialchars($m["error"],ENT_QUOTES)."</div>";
    }
    $h[]="<div class='sylr-scroll'><table class='ak-tbl sylr-tbl'>";
    $h[]="<thead><tr><th>{ID}</th><th>{remote_syslog_server}</th><th>{sylr_type}</th>"
        ."<th class='sylr-r'>{events}</th><th>{sylr_health}</th><th class='sylr-c'>{enabled}</th><th class='sylr-r'>{sylr_actions}</th></tr></thead>";
    $h[]="<tbody>";
    foreach ($m["rules"] as $ligne){$h[]=sylr_rule_row($m,$ligne);}
    if($count==0 && $m["error"]==""){
        $h[]=sylr_rules_empty($m);
    }
    if(count($m["squid"])>0){$h[]=sylr_squid_row($m["squid"]);}
    $h[]="</tbody></table></div>";
    $h[]="</section>";
    return @implode("\n",$h);
}
function sylr_rules_empty(array $m):string{
    $page=CurrentPageName();
    $btn="";
    if(!$m["hacluster"]){
        $btn="<button type='button' class='ak-btn ak-btn-primary' OnClick=\"Loadjs('$page?rule-js=0');\"><i class='fa-solid fa-plus'></i> {new_rule}</button>";
    }
    return "<tr><td colspan='7'><div class='ak-empty-c'><i class='fa-solid fa-share-nodes'></i><div>{sylr_no_rule}</div>$btn</div></td></tr>";
}
function sylr_type_icon(string $type):string{
    $map=array("all"=>"fa-asterisk","squid"=>"fa-globe","sshd"=>"fa-terminal","unbound"=>"fa-server",
        "dnsdist"=>"fa-server","dnsfw"=>"fa-shield-halved","rdpproxy"=>"fa-desktop","keepalived"=>"fa-heart-pulse",
        "keepalived-checker"=>"fa-heart-pulse","syslog"=>"fa-file-lines","kernel"=>"fa-microchip",
        "webfiltering"=>"fa-filter","postgres"=>"fa-database","admintrack"=>"fa-user-shield","c-icap"=>"fa-virus",
        "http-antivirus"=>"fa-virus","firewall"=>"fa-shield-halved","strongswan"=>"fa-lock","suricata"=>"fa-eye",
        "auth"=>"fa-key","freshclam"=>"fa-virus","clamd"=>"fa-virus","proxy_geoip"=>"fa-location-dot");
    return "fa-solid ".($map[$type] ?? "fa-file-lines");
}
function sylr_type_label(string $type):string{
    $types=syslog_types;
    if(isset($types[$type])){return $types[$type];}
    if(preg_match('#^postfix-instance([0-9]+)$#',$type,$re)){return "Postfix #".$re[1];}
    return htmlspecialchars($type);
}
function sylr_rule_row(array $m,array $ligne):string{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=intval($ligne["ID"]);
    $enabled=intval($ligne["enabled"])==1;
    $ssl=intval($ligne["ssl"])==1;
    $proto=$ssl?"TCP":strtoupper($ligne["proto"]=="tcp"?"tcp":"udp");
    $dest=htmlspecialchars($ligne["server"].":".intval($ligne["port"]));
    $st=sylr_rule_stats($m,$ID);
    list($hcls,$hico,$hlabel)=sylr_health($enabled,$st);
    $ro=$m["hacluster"];

    $chips=array("<span class='ak-tag'>$proto</span>");
    if($ssl){
        if(!$ligne["certificate_set"] || !$ligne["public_key_set"]){
            $chips[]="<a class='ak-tag ak-err' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?certificate-wizard-js=yes');\">"
                ."<i class='fa-solid fa-circle-exclamation'></i> {missing_certificate}</a>";
        }else{
            $chips[]="<span class='ak-tag ak-ok'><i class='fa-solid fa-lock'></i> TLS</span>";
        }
    }
    $myhostname=trim($ligne["myhostname"]);
    if($myhostname!=""){$chips[]="<span class='sylr-as'>{sylr_as} <b>".htmlspecialchars($myhostname)."</b></span>";}

    $total=$st["sent"]+$st["failed"]+$st["suspended"];
    $bar="<span class='sylr-bar sylr-bar-empty'></span>";
    if($enabled && $total>0){
        $ps=round($st["sent"]*100/$total,1);
        $pf=round($st["failed"]*100/$total,1);
        $pu=max(0,100-$ps-$pf);
        $title=$tpl->FormatNumber($st["sent"],0,",")." / ".$tpl->FormatNumber($total,0,",");
        $bar="<span class='sylr-bar' title='$title'><span class='sylr-bar-ok' style='width:$ps%'></span>"
            ."<span class='sylr-bar-err' style='width:$pf%'></span><span class='sylr-bar-warn' style='width:$pu%'></span></span>";
    }

    $off=$enabled?"":" sylr-row-off";
    $editjs="Loadjs('$page?rule-js=$ID');";
    $h=array();
    $h[]="<tr class='sylr-row$off'>";
    $h[]="<td class='sylr-id'>$ID</td>";
    $h[]="<td><a class='sylr-dest' href=\"javascript:blur();\" OnClick=\"$editjs\">$dest</a><div class='sylr-chips'>".@implode("",$chips)."</div></td>";
    $h[]="<td><span class='sylr-type'><i class='".sylr_type_icon($ligne["logtype"])."'></i> ".sylr_type_label($ligne["logtype"])."</span></td>";
    $h[]="<td class='sylr-r'>".sylr_events_cell($st)."</td>";
    $htone=array("ok"=>"ok","err"=>"err","warn"=>"warn");
    $h[]="<td><span class='ak-state ak-".(isset($htone[$hcls])?$htone[$hcls]:"off")."'><i class='$hico'></i> $hlabel</span>$bar</td>";
    $h[]="<td class='sylr-c'>".sylr_switch($ID,$enabled,$ro)."</td>";
    $acts="";
    if(!$ro){
        $acts="<a class='ak-act' href=\"javascript:blur();\" OnClick=\"$editjs\" title='{sylr_edit}' aria-label='{sylr_edit} $ID'><i class='fa-solid fa-pen'></i></a>"
            ."<a class='ak-act ak-danger' href=\"javascript:blur();\" OnClick=\"Loadjs('$page?pattern-remove=$ID');\" title='{sylr_delete}' aria-label='{sylr_delete} $ID'><i class='fa-solid fa-trash-can'></i></a>";
    }
    $h[]="<td class='sylr-r'><div class='ak-acts'>$acts</div></td>";
    $h[]="</tr>";
    return @implode("",$h);
}
function sylr_events_cell(array $st):string{
    $tpl=new template_admin();
    $f=$st["failed"]>0?" sylr-txt-err":" ak-muted";
    $s=$st["suspended"]>0?" sylr-txt-warn":" ak-muted";
    return "<div class='sylr-ev'>"
        ."<span title='{sylr_sent}'><small>{sylr_sent}</small> ".$tpl->FormatNumber($st["sent"])."</span>"
        ."<span class='$f' title='{sylr_failed}'><small>{sylr_failed}</small> ".$tpl->FormatNumber($st["failed"])."</span>"
        ."<span class='$s' title='{sylr_suspended}'><small>{sylr_suspended}</small> ".$tpl->FormatNumber($st["suspended"])."</span>"
        ."</div>";
}
function sylr_switch(int $ID,bool $on,bool $ro):string{
    $state=$on?"true":"false";
    $dis=$ro?" disabled":"";
    return "<button type='button' role='switch' aria-checked='$state' aria-label='{sylr_toggle} $ID' class='ak-sw'$dis"
        ." OnClick=\"SylrToggle($ID,this);\"></button>";
}
function sylr_squid_row(array $sq):string{
    $dest=htmlspecialchars(strtolower($sq["proto"])."://".$sq["server"].":".$sq["port"]);
    return "<tr class='sylr-row-ro'>"
        ."<td class='sylr-id'><i class='fa-solid fa-lock'></i></td>"
        ."<td><span class='sylr-dest'>$dest</span><div class='sylr-chips'><span class='sylr-as'>{sylr_proxy_row}</span></div></td>"
        ."<td><span class='sylr-type'><i class='fa-solid fa-globe'></i> {APP_SQUID}</span></td>"
        ."<td class='sylr-r ak-muted'>&mdash;</td>"
        ."<td><span class='ak-state ak-off'><i class='fa-solid fa-shield-halved'></i> {sylr_managed_proxy}</span></td>"
        ."<td class='sylr-c ak-muted'>&mdash;</td>"
        ."<td class='sylr-r'><a class='ak-link' href=\"javascript:blur();\" OnClick=\"Loadjs('fw.proxy.general.php?squid-syslog-js=yes');\">{sylr_open}</a></td>"
        ."</tr>";
}
function sylr_js():string{
    $page=CurrentPageName();
    return "<script>
window.SylrReload=function(){ if(document.getElementById('sylr-host')){ LoadAjaxSilent('sylr-host','$page?sylr-host=yes'); } };
window.SylrToggle=function(id,el){
    if(el.disabled){return;}
    el.setAttribute('aria-checked',el.getAttribute('aria-checked')==='true'?'false':'true');
    Loadjs('$page?pattern-enable='+id);
    setTimeout(window.SylrReload,1200);
};
if(window.SylrTimer){clearInterval(window.SylrTimer);}
window.SylrTimer=setInterval(function(){
    if(!document.getElementById('sylr-host')){clearInterval(window.SylrTimer);window.SylrTimer=null;return;}
    if(document.querySelector('.modal.in, .modal.show, .sweet-alert.visible')){return;}
    window.SylrReload();
},15000);
</script>";
}
function sylr_style():string{
    if(isset($GLOBALS["SYLR_STYLE"])){return "";}
    $GLOBALS["SYLR_STYLE"]=true;
    $accent="#006a62";$soft="rgba(16,185,129,.12)";$dark="#00504a";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(!empty($a["accent"])){$accent=$a["accent"];}
        if(!empty($a["accent_soft"])){$soft=$a["accent_soft"];}
        if(!empty($a["accent_dark"])){$dark=$a["accent_dark"];}
    }
    return "<style>
.sylr-grid{display:grid;grid-template-columns:335px minmax(0,1fr);gap:20px;margin-top:10px;align-items:start}
.sylr-right{min-width:0}
.sylr-strip{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px}
.sylr-card{display:flex;align-items:center;gap:14px;min-height:96px;padding:16px 18px}
.sylr-card-warn{border-color:#ffddb9;background:#fffaf3}
.sylr-card-ico{width:44px;height:44px;flex:0 0 auto;border-radius:10px;background:$soft;color:$dark;display:inline-flex;align-items:center;justify-content:center;font-size:19px}
.sylr-card-off .sylr-card-ico{background:#f1f5f9;color:#64748b}
.sylr-card-warn .sylr-card-ico{background:#ffddb9;color:#865300}
.sylr-card-body{flex:1 1 auto;min-width:0}
.sylr-card-title{font-size:13px;color:#3d4947;font-weight:600}
.sylr-card-value{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:4px}
.sylr-big{font-size:22px;font-weight:700;color:#111d23;line-height:28px}
.sylr-card-sub{font-size:13px;color:#6d7a77;margin-top:4px}
.sylr-dest:focus-visible{outline:2px solid $accent;outline-offset:2px}
.sylr-rules-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid #e2e8f0}
.sylr-rules-title{display:flex;align-items:center;gap:10px}
.sylr-rules-title h3{margin:0;font-size:16px;font-weight:600;color:#111d23}
.sylr-note{font-size:13px;color:#64748b;display:flex;align-items:center;gap:6px}
.sylr-error{margin:12px 20px 0}
.sylr-scroll{overflow-x:auto}
.sylr-tbl th.sylr-r,.sylr-tbl td.sylr-r{text-align:right}
.sylr-tbl th.sylr-c,.sylr-tbl td.sylr-c{text-align:center}
.sylr-tbl tbody tr.sylr-row:hover{background:#f8fafc}
.sylr-id{width:1%;color:#6d7a77;white-space:nowrap}
.sylr-dest{font-weight:700;color:#111d23!important;text-decoration:none!important;overflow-wrap:anywhere}
a.sylr-dest:hover{color:$accent!important;text-decoration:underline!important}
.sylr-chips{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-top:5px}
.sylr-as{font-size:13px;color:#6d7a77}
.sylr-as b{color:#3d4947;font-weight:600}
.sylr-type{display:inline-flex;align-items:center;gap:7px;color:#111d23}
.sylr-type i,.sylr-type svg{color:#6d7a77}
.sylr-ev{display:inline-flex;flex-direction:column;align-items:flex-end;gap:1px;font-variant-numeric:tabular-nums;white-space:nowrap;line-height:18px}
.sylr-ev small{font-size:11px;color:#6d7a77;margin-right:4px}
.sylr-txt-err{color:#ba1a1a;font-weight:700}
.sylr-txt-warn{color:#865300;font-weight:700}
.sylr-bar{display:flex;width:60px;height:8px;border-radius:3px;overflow:hidden;background:#e2e8f0;margin-top:5px}
.sylr-bar-empty{background:#f1f5f9}
.sylr-bar-ok{background:$accent}
.sylr-bar-err{background:#ba1a1a}
.sylr-bar-warn{background:#d97706}
.sylr-row-off .sylr-dest,.sylr-row-off .sylr-type,.sylr-row-off .sylr-ev{color:#6d7a77!important}
.sylr-row-ro{background:#f8fafc}
.sylr-row-ro .sylr-dest{font-weight:600;color:#3d4947!important}
@media (max-width:1200px){.sylr-grid{grid-template-columns:minmax(0,1fr)}.sylr-strip{grid-template-columns:minmax(0,1fr)}}
</style>";
}

function ServiceStatus():string{
    $tpl        = new template_admin();
    $sock=new sockets();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/syslog/mybin"));
    if(!is_object($json) || !$json->Status){
        $btn[0]["name"]="{install}";
        $btn[0]["icon"]=ico_cd;
        $btn[0]["js"]="Loadjs('fw.system.upgrade-software.php?product=APP_SYSLOGD')";
        return  $tpl->widget_rouge("{error}",is_object($json)?$json->Error:"{error}",$btn);

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

// Colonne gauche : le widget d'etat du service, recharge toutes les 3 s tant qu'il est affiche.
// La file locale et les compteurs vivent desormais dans l'hote de droite (sylr-host).
function status():bool{
    $page       = CurrentPageName();
    echo ServiceStatus();
    echo "<script>
function RestartStatusSylog(){
if ( !document.getElementById('syslog-status') ) { return;}
LoadAjaxSilent('syslog-status','$page?status=yes');
}
setTimeout(\"RestartStatusSylog()\",3000);
</script>";
    return true;
}
// Vider la file locale : les evenements en attente sont perdus, d'ou la confirmation.
function clean_queue_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $jsClean=$tpl->framework_buildjs("/syslog/cleanqueues",
        "rsyslog.clean.progress","rsyslog.clean.log","syslog-restart-status","if(window.SylrReload){SylrReload();}");
    return $tpl->js_dialog_confirm_action("{sylr_clean_confirm}","clean-queue","none",$jsClean);
}
function clean_queue_confirm():bool{
    if(!sylr_require_admin()){return false;}
    return admin_tracks("Clean the local syslog queue");
}
// Droit verifie cote serveur : masquer un bouton ne protege pas l'action.
function sylr_is_admin():bool{
    $users=new usersMenus();
    return $users->AsSystemAdministrator===true || $users->AsSystemAdministrator==1;
}
function sylr_require_admin():bool{
    if(sylr_is_admin()){return true;}
    $tpl=new template_admin();
    echo $tpl->post_error("{ERROR_NO_PRIVS2}");
    return false;
}
