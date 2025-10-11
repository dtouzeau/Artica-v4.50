<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__).'/ressources/class.modsectools.inc');
include_once(dirname(__FILE__).'/ressources/class.ip2host.inc');

$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["unlink-cloud-js"])){unlink_cloud_js();exit;}
if(isset($_GET["threads-js"])){threads_js();exit;}
if(isset($_GET["threats-popup"])){threads_popup();exit;}
if(isset($_GET["threats-list-search"])){threads_search();exit;}
if(isset($_GET["decisions-list-search"])){decisions_list_search();exit;}
if(isset($_GET["alerts-form"])){alerts_form();exit;}
if(isset($_GET["artica-search"])){artica_search();exit;}
if(isset($_GET["alerts-search"])){alerts_search();exit;}
if(isset($_GET["status-top"])){main_top();exit;}
if(isset($_GET["status-start"])){main_status();exit;}
if(isset($_GET["status"])){crowdsec_status();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["behavior-js"])){behavior_js();exit;}
if(isset($_GET["FirewallBouncer-js"])){FirewallBouncer_js();exit;}
if(isset($_GET["FirewallBouncer-popup"])){FirewallBouncer_popup();exit;}
if(isset($_GET["ipfeed-js"])){ipfeed_js();exit;}
if(isset($_GET["ipfeeds-popup"])){ipfeed_popup();exit;}
if(isset($_GET["whitelist-js"])){whitelist_js();exit;}
if(isset($_POST["whitelist-ip"])){whitelist_ip_perform();exit;}
if(isset($_GET["resolv-this"])){resolv_this();exit;}

if(isset($_GET["restart"])){restart_js();exit;}
if(isset($_GET["restart-perform"])){restart_perform();exit;}

if(isset($_GET["articaweb-js"])){articaweb_js();exit;}
if(isset($_GET["articaweb-popup"])){articaweb_popup();exit;}
if(isset($_GET["consoleprivs-js"])){console_privs_js();exit;}
if(isset($_GET["console-privs-popup"])){console_privs_popup();exit;}
if(isset($_GET["join-cloud-js"])){join_cloud_js();exit;}
if(isset($_GET["join-cloud-popup"])){join_cloud_popup();exit;}
if(isset($_GET["behavior-popup"])){behavior_popup();exit;}
if(isset($_POST["console_management"])){console_privs_save();exit;}
if(isset($_POST["WebConsoleCheckTrustedNets"])){save();exit;}
if(isset($_POST["CrowdSecCyberCrimeIPfeed"])){save();exit;}
if(isset($_POST["EnableCrowdsecFirewallBouncer"])){save();exit;}

if(isset($_POST["Fail2bantime"])){save();exit;}
if(isset($_POST["CROWDSEC_CLOUD_TOKEN"])){console_token_save();exit;}
if(isset($_GET["config-center"])){main_config();exit;}
if(isset($_GET["threats-form"])){threats_form();exit;}
if(isset($_GET["artica-form"])){artica_form();exit;}
if(isset($_GET["search"])){threats_search();exit;}
if(isset($_GET["decisions"])){decisions_list();exit;}
if(isset($_GET["decisions-list-js"])){decisions_list_js();exit;}
if(isset($_GET["decisions-list-popup"])){decisions_list_popup();exit;}
if(isset($_GET["decisions-list-add-js"])){decisions_list_add_js();exit;}
if(isset($_GET["decisions-list-add-popup"])){decisions_list_add_popup();exit;}
if(isset($_POST["decision-add"])){decisions_list_add_save();exit;}
page();

function unlink_cloud_js():bool{
    $page   = CurrentPageName();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CROWDSEC_CLOUD_TOKEN","");
    header("content-type: application/x-javascript");
   echo "LoadAjax('crowdsec-config','$page?config-center=yes');";
   return admin_tracks("Disable connection from the Crowdsec Cloud console");
}

function tabs():bool{
    $page   = CurrentPageName();
    $tpl    = new template_admin();
    $array["{status}"]="$page?status-start=yes";
    $array["{firewall_rules}"]="$page?threats-form=yes";
    $array["{threats}"]="$page?artica-form=yes";
    $array["{events}"]="$page?alerts-form=yes";
    $array["{collections}"]="fw.crowdsec.collections.php";
    $array["{service_events}"]="fw.crowdsec.events.php?form=yes";
    echo $tpl->tabs_default($array);
    return true;
}

function whitelist_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $ipaddr=$_GET["whitelist-js"];
    $function=$_GET["function"];
    $ask=$tpl->_ENGINE_parse_body("{trust_this_ip_ask}");
    $ask=str_replace("%s",$ipaddr,$ask);
    return $tpl->js_confirm_execute($ask,"whitelist-ip",$ipaddr,"$function()");

}

function ListWhitelisted():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT ipaddr FROM networks_infos WHERE enabled=1 AND trusted=1");

    foreach ($results as $index=>$ligne){
        $ligne["ipaddr"]=str_replace("/32","",$ligne["ipaddr"]);
        $_SESSION["NETTRUSTED"][$ligne["ipaddr"]]=true;
    }

return true;

}

function whitelist_ip_perform():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $ipaddr=$_POST["whitelist-ip"];
    $cdir=$ipaddr."/32";
    $netinfos="Whitelist From CrowdSec";
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql="DELETE FROM networks_infos WHERE ipaddr='$cdir'";
    $q->QUERY_SQL($sql);
    if(!$q->FIELD_EXISTS("networks_infos","vpn")){
        $q->QUERY_SQL("ALTER TABLE add vpn INTEGER NOT NULL default '0'");
    }


    $q->QUERY_SQL("INSERT INTO networks_infos (enabled,scannable,netinfos,ipaddr,pingable,pinginterval,noping,yesping,prcping,trusted) 
			VALUES('1','0','$netinfos','$cdir','0','0','1','0','0','1')");
    if(!$q->ok){
        echo $q->mysql_error;
        return false;
    }

    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM crowdsec_blacklist WHERE ipaddr='$ipaddr'");


    $GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/whitelist");
    $ipaddrenc=urlencode($ipaddr);
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/decision/delete/$ipaddrenc");
    admin_tracks_post("Remove $cdir - $netinfos from CrowdSec and add it to Trusted Network");
    $memcached=new lib_memcached();
    $memcached->saveKey("WebConsoleTrustedNet","");
    ListWhitelisted();
    return true;
}

function crowdsec_blacklisted():int{
    $q=new lib_sqlite("/home/artica/SQLITE/crowdsec-events.db");
    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM alerts");
    return intval($ligne["tcount"]);
}

function main_top():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/stats"));
    if(!$json->Status){
        $html=$tpl->widget_style1("red-bg",ico_bug,"{error}",$json->Error);
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }
    $Metrics=$json->Metrics;
    $CROWDSET_PARSED=0;
    $CROWDSET_PARSED_WIDGET=$tpl->widget_style1("gray-bg",ico_loupe,"{analyzed}",0);
    if(property_exists($Metrics,"acquisition")){
        foreach ($Metrics->acquisition as $file=>$SubJson){
            $CROWDSET_PARSED=$CROWDSET_PARSED+$SubJson->parsed;
        }
    }
    if($CROWDSET_PARSED>0){
        $CROWDSET_PARSED_WIDGET=$tpl->widget_style1("navy-bg",ico_loupe,"{analyzed}",$tpl->FormatNumber($CROWDSET_PARSED));
    }

    $CROWDSET_BLACKLISTS=0;
    if(property_exists($Metrics,"alerts")){
        foreach ($Metrics->alerts as $Type=>$SubJson){
                $CROWDSET_BLACKLISTS=$CROWDSET_BLACKLISTS+$SubJson;
        }
    }
    $CROWDSET_BLACKLISTS_WIDGET=$tpl->widget_style1("gray-bg",ico_firewall,"{threats}",0);

    if($CROWDSET_BLACKLISTS>0){
        $btn[0]["margin"] = 0;
        $btn[0]["name"] = "{threats}";
        $btn[0]["color"] = "yellow";
        $btn[0]["icon"] = ico_firewall;
        $btn[0]["js"] = "Loadjs('$page?threads-js=yes');";

        $CROWDSET_BLACKLISTS=$tpl->FormatNumber($CROWDSET_BLACKLISTS);
        $CROWDSET_BLACKLISTS_WIDGET=$tpl->widget_style1("yellow-bg",ico_firewall,"{threats}",$CROWDSET_BLACKLISTS,$btn);

    }


    $CROWDSEC_TOKEN=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_CLOUD_TOKEN"));
    $CROWDSEC_ENROLLED=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_ENROLLED"));

    if($CROWDSEC_TOKEN==null){
        $btn=array();
        $btn[0]["margin"] = 0;
        $btn[0]["name"] = "{setup}";
        $btn[0]["icon"] = ico_cd;
        $btn[0]["js"] = "Loadjs('$page?join-cloud-js=yes');";
        $CROWDSEC_TOKEN_WIDGET=$tpl->widget_style1("gray-bg",ico_clouds,"{crowdsec_webconsole}","{disabled}",$btn);
    }else{
        $btn=array();
        $btn[0]["margin"] = 0;
        $btn[0]["name"] = "{disconnect}";
        $btn[0]["icon"] = ico_unlink;
        $btn[0]["js"] = "Loadjs('$page?unlink-cloud-js=yes');";
        $CROWDSEC_TOKEN_WIDGET=$tpl->widget_style1("yellow-bg",ico_clouds,"{crowdsec_webconsole}","{connecting}",$btn);
        if(strlen($CROWDSEC_ENROLLED)>8){
            $CROWDSEC_TOKEN_WIDGET=$tpl->widget_style1("navy-bg",ico_clouds,"{crowdsec_webconsole}","{connected2}",$btn);
        }

    }


    $html[]="<table style='width:100%;margin-top:-7px'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$CROWDSET_PARSED_WIDGET</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$CROWDSET_BLACKLISTS_WIDGET</td>";
    $html[]="<td style='width:33%;padding-left:5px'>$CROWDSEC_TOKEN_WIDGET</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function decisions_list_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    return $tpl->js_dialog1("{automatic_remediation}","$page?decisions-list-popup=yes",950);
}
function decisions_list_popup():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    echo $tpl->search_block($page,null,null,null,"&decisions-list-search=yes");
    return true;
}
function threads_popup():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    echo $tpl->search_block($page,null,null,null,"&threats-list-search=yes");
    return true;
}


function behavior_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    return $tpl->js_dialog1("{behavior}","$page?behavior-popup=yes");
}
function threads_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    return $tpl->js_dialog1("{threats}","$page?threats-popup=yes",550);
}

function ipfeed_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    return $tpl->js_dialog1("{CybercrimeIPFeeds}","$page?ipfeeds-popup=yes");

}
function FirewallBouncer_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    return $tpl->js_dialog1("{your_firewall}","$page?FirewallBouncer-popup=yes");
}


function articaweb_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    return $tpl->js_dialog1("{APP_NGINX_CONSOLE}","$page?articaweb-popup=yes");
}
function console_privs_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    return $tpl->js_dialog1("{crowdsec_webconsole}","$page?console-privs-popup=yes");
}
function decisions_list_add_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    return $tpl->js_dialog1("{ban_ipaddr_net}","$page?decisions-list-add-popup=yes");
}
function join_cloud_js():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    return $tpl->js_dialog1("{crowdsec_webconsole}","$page?join-cloud-popup=yes");
}

function decisions_list_add_popup():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $form[]=$tpl->field_text("decision-add","{ipaddr}","1.2.3.4/32",true);
    $form[]=$tpl->field_numeric("time","{during} ({minutes})",1440,true);
    $form[]=$tpl->field_text("description","{description}","Manual block");
    echo $tpl->form_outside("", @implode("\n", $form),null,"{add}",decision_js(),
        "AsFirewallManager",true);
    return true;
}
function decisions_list_add_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    admin_tracks_post("Ban an IP address from CrowdSec");
    $ipadd=urlencode($_POST["decision-add"]);
    if(!preg_match("#^[0-9\.]+#",$ipadd)){
        echo $tpl->post_error("$ipadd {invalid}");
        return false;
    }
    $description=base64_encode($_POST["description"]);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("crowdsec.php?decision-add=$ipadd&time={$_POST["time"]}&desc=$description");
    return true;
}

function behavior_popup():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $Fail2bantime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Fail2bantime"));
    $CrowdSecDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrowdSecDebug"));
    if($Fail2bantime==0){$Fail2bantime=14400;}
    $form[]=$tpl->field_numeric("Fail2bantime","{bantime} ({seconds})",$Fail2bantime,"{Fail2bantime}");$form[]=$tpl->field_checkbox("CrowdSecDebug","{debug}",$CrowdSecDebug);
    echo $tpl->form_outside("{parameters}", @implode("\n", $form),null,"{apply}",form_js(),
        "AsFirewallManager",true);
    return true;
}
function ipfeed_popup():bool{
    $CrowdSecCyberCrimeIPfeed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrowdSecCyberCrimeIPfeed"));
    $page       = CurrentPageName();
    $tpl        = new template_admin();
    $Fail2bantime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Fail2bantime"));
    if($Fail2bantime==0){$Fail2bantime=14400;}
    $form[]=$tpl->field_checkbox("CrowdSecCyberCrimeIPfeed","{CybercrimeIPFeeds}",$CrowdSecCyberCrimeIPfeed,true);
    echo $tpl->form_outside("", @implode("\n", $form),"{CybercrimeIPFeedsInCrowdSec}","{apply}",form_js(),
        "AsFirewallManager",true);
    return true;
}

function FirewallBouncer_jsafter():string{
    $page       = CurrentPageName();
    $tpl                = new template_admin();
    $f[]="LoadAjax('crowdsec-config','$page?config-center=yes');";
    $f[]="dialogInstance1.close();";
    $f[]=$tpl->framework_buildjs("/crowdsec","/crowdsec/firewall/bouncer/restart",
        "crowdsec.reconfigure.log","progress-crowdsec-restart","LoadAjax('crowdsec-status','$page?status=yes');");
    return @implode("",$f);
}
function FirewallBouncer_popup(){

        $EnableCrowdsecFirewallBouncer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrowdsecFirewallBouncer"));
    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $form[]=$tpl->field_checkbox("EnableCrowdsecFirewallBouncer","{your_firewall}",$EnableCrowdsecFirewallBouncer,true);
    echo $tpl->form_outside("", @implode("\n", $form),"{CrowdsecFirewallBouncerExplain}","{apply}",form_js(),
        "AsFirewallManager",true);
    return true;
}


function articaweb_popup():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $CrowdSecWebConsoleScenarioLeakSpeed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrowdSecWebConsoleScenarioLeakSpeed"));

    $CrowdSecWebConsoleScenarioCapacity=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrowdSecWebConsoleScenarioCapacity"));

    if($CrowdSecWebConsoleScenarioLeakSpeed==0){
        $CrowdSecWebConsoleScenarioLeakSpeed=5;
    }
    if($CrowdSecWebConsoleScenarioCapacity==0){
        $CrowdSecWebConsoleScenarioCapacity=5;
    }
    $WebConsoleCheckTrustedNets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebConsoleCheckTrustedNets"));


    $form[]=$tpl->field_numeric("CrowdSecWebConsoleScenarioCapacity","{attempts})",$CrowdSecWebConsoleScenarioCapacity,"");

    $form[]=$tpl->field_numeric("CrowdSecWebConsoleScenarioLeakSpeed","{during} ({minutes})",$CrowdSecWebConsoleScenarioLeakSpeed,"");

    $form[]=$tpl->field_checkbox("WebConsoleCheckTrustedNets","{check_trusted_domains}",$WebConsoleCheckTrustedNets,"");


    echo $tpl->form_outside("", @implode("\n", $form),null,"{apply}",form_js(),
        "AsFirewallManager",true);
    return true;


}
function join_cloud_popup():bool{
    $tpl        = new template_admin();
    $CROWDSEC_TOKEN=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_CLOUD_TOKEN"));
    $form[]=$tpl->field_text("CROWDSEC_CLOUD_TOKEN","{cloud_token}",$CROWDSEC_TOKEN);
    echo $tpl->form_outside(null, @implode("\n", $form),null,"{apply}",form_js(),
        "AsFirewallManager",true);
    return true;

}
function console_privs_popup(){
    $tpl        = new template_admin();


    $CROWDSEC_CLOUD_PRIVS = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/console/features"));

    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}", json_last_error_msg());
        return $tpl;
    }

    if (!$CROWDSEC_CLOUD_PRIVS->Status) {
        echo $tpl->widget_rouge("{error}", $CROWDSEC_CLOUD_PRIVS->Error);
        return $tpl;
    }

    if(!property_exists($CROWDSEC_CLOUD_PRIVS,"features")) {
        echo $tpl->div_error("json decode failed");
        return true;
    }

    $form[]=$tpl->field_checkbox("custom","{ShareCustomScenarios}",$CROWDSEC_CLOUD_PRIVS->features->custom);
    $form[]=$tpl->field_checkbox("manual","{ShareManualDecisions}",$CROWDSEC_CLOUD_PRIVS->features->manual);
    $form[]=$tpl->field_checkbox("tainted","{ShareTaintedScenarios}",$CROWDSEC_CLOUD_PRIVS->features->tainted);
    $form[]=$tpl->field_checkbox("context","{ShareContext}",$CROWDSEC_CLOUD_PRIVS->features->context);
    $form[]=$tpl->field_checkbox("console_management","{ConsoleManagement}",$CROWDSEC_CLOUD_PRIVS->features->console_management);

    $page       = CurrentPageName();
    $f[]="LoadAjax('crowdsec-config','$page?config-center=yes');";
    $f[]="dialogInstance1.close();";

    echo $tpl->form_outside(null, @implode("\n", $form),null,"{apply}",
        implode("\n", $f),"AsFirewallManager",true);
    return true;
}
function console_privs_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();

    foreach ($_POST as $key => $value) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/console/feature/$key/$value");
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CROWDSEC_CLOUD_SETS",serialize($_POST));
    return admin_tracks_post("Save CrowdSec Cloud management console privileges");
}
function console_token_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $token=$_POST["CROWDSEC_CLOUD_TOKEN"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CROWDSEC_CLOUD_TOKEN",$token);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/console/connect"));
    if(!$json->Status) {
        echo $tpl->post_error($json->Error);
        return true;
    }
    return  admin_tracks_post("Connect CrowdSec to the cloud Console");

}

function save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return admin_tracks_post("Save CrowdSec parameters");
}
function restart_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $html=base64_encode($tpl->_ENGINE_parse_body("<div style='margin:50px;' class='center'><H1>{restarting} {please_wait}....</H1></div>"));

    $f[]="document.getElementById('crowdsec-status').innerHTML=base64_decode('$html');";
    $f[]="Loadjs('$page?restart-perform=yes&id=crowdsec-status')";
    header("content-type: application/x-javascript");
    echo @implode("\n",$f);
    return true;
}
function restart_perform():bool{
    $tpl=new template_admin();
    admin_tracks("Restarting the CrowdSec service");
    $sock=new sockets();
    $data=$sock->REST_API("/crowdsec/restart");
    $json=json_decode($data);
    $id=$_GET["id"];
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->js_error(json_last_error_msg());
    }
    header("content-type: application/x-javascript");
    if(!$json->Status){
        $html=base64_encode($tpl->_ENGINE_parse_body("<div style='margin:50px;' class='center'><H1 style='color:red'>$json->Error</H1></div>"));
        echo "document.getElementById('$id').innerHTML=base64_decode('$html');";
        return false;
    }
    $page=CurrentPageName();
    echo "LoadAjaxSilent('crowdsec-status','$page?status=yes');";
    return true;

}

function main_status():bool{
    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $Fail2bantime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Fail2bantime"));
    if($Fail2bantime==0){
        $Fail2bantime=14400;
    }

    $tpl->table_form_field_js("Loadjs('$page?behavior-js=yes')");
    $tpl->table_form_field_text("{bantime}",$tpl->SecondsToTime($Fail2bantime),ico_timeout);



    $html[]="<table style='width:100%;margin-top:20px'>";
    $html[]="<tr>";

    $html[]="<td style='width:240px;vertical-align: top'>";
    $html[]="<div id='crowdsec-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;padding-left: 20px;vertical-align: top'>";
    $html[]="<div id='crowdsec-top'></div>";
    $html[]="<div id='crowdsec-config'></div>";

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="LoadAjax('crowdsec-config','$page?config-center=yes');";
    $html[]=$tpl->RefreshInterval_js("crowdsec-status",$page,"status=yes");

    $IPTABLES_VERSION   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CROWDSEC_VERSION");
    $topbuttons=array();
    $topbuttons[] = array("Loadjs('$page?decisions-list-add-js=yes')", ico_plus, "{ban_ipaddr_net}");

    $TINY_ARRAY["TITLE"]="{APP_CROWDSEC} v$IPTABLES_VERSION";
    $TINY_ARRAY["ICO"]="far fa-shield-cross";
    $TINY_ARRAY["EXPL"]="{APP_CROWDSEC_EXPLAIN}";
    $TINY_ARRAY["URL"]="crowdsec-status";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]=$jstiny;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);



    return true;
}
function main_config():bool{

    $page       = CurrentPageName();
    $tpl        = new template_admin();

    $Fail2bantime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Fail2bantime"));
    $CROWDSEC_TOKEN=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_CLOUD_TOKEN"));
    $CROWDSEC_ENROLLED=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_ENROLLED"));
    $CROWDSEC_CLOUD_PRIVS=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_CLOUD_PRIVS"));
    $WebConsoleCheckTrustedNets=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebConsoleCheckTrustedNets"));
    $CrowdSecWebConsoleScenarioLeakSpeed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrowdSecWebConsoleScenarioLeakSpeed"));
    $CrowdSecDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrowdSecDebug"));

    $CrowdSecWebConsoleScenarioCapacity=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrowdSecWebConsoleScenarioCapacity"));

    if($CrowdSecWebConsoleScenarioLeakSpeed==0){
        $CrowdSecWebConsoleScenarioLeakSpeed=5;
    }
    if($CrowdSecWebConsoleScenarioCapacity==0){
        $CrowdSecWebConsoleScenarioCapacity=5;
    }

    if($Fail2bantime==0){
        $Fail2bantime=14400;
    }

    $EnableArticaNFQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue"));
    $ArticaPSnifferDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferDaemon"));

    $tpl->table_form_field_js("Loadjs('$page?behavior-js=yes')");
    $tpl->table_form_field_text("{bantime}",$tpl->SecondsToTime($Fail2bantime),ico_timeout);
    $tpl->table_form_field_bool("{debug_mode}",$CrowdSecDebug,ico_bug);



    if($EnableArticaNFQueue==0){
        $tpl->table_form_field_js("");
        $tpl->table_form_field_bool("{CybercrimeIPFeeds}:{remediation}",0,ico_firewall);

    }else{
        $tpl->table_form_field_js("Loadjs('$page?ipfeed-js=yes')");
    }

    $EnableCrowdsecFirewallBouncer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCrowdsecFirewallBouncer"));

    $tpl->table_form_field_js("Loadjs('$page?FirewallBouncer-js=yes')");
    $tpl->table_form_field_bool("{your_firewall}", $EnableCrowdsecFirewallBouncer, ico_firewall);





    $WebConsoleCheckTrustedText=null;
    if($WebConsoleCheckTrustedNets==1){$WebConsoleCheckTrustedText="&nbsp;{check_trusted_domains}";}
    $tpl->table_form_field_js("Loadjs('$page?articaweb-js=yes')");
    $tpl->table_form_field_text("{APP_NGINX_CONSOLE}","$CrowdSecWebConsoleScenarioCapacity {attempts} {during} $CrowdSecWebConsoleScenarioLeakSpeed {minutes}$WebConsoleCheckTrustedText",ico_timeout);


    if($CROWDSEC_TOKEN<>null) {
        $tpl->table_form_field_text("{cloud_token}", $CROWDSEC_TOKEN, ico_key);
        if ($CROWDSEC_ENROLLED <> null) {
            $tpl = CROWDSEC_CLOUD_PRIVS($tpl);
        }
    }


    $html[]=$tpl->table_form_compile();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function CROWDSEC_CLOUD_PRIVS($tpl){
    $page=CurrentPageName();
    $CROWDSEC_CLOUD_PRIVS = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/console/features"));

    if (json_last_error() > JSON_ERROR_NONE) {
        echo $tpl->widget_rouge("{error}", json_last_error_msg());
        return $tpl;
    }

    if (!$CROWDSEC_CLOUD_PRIVS->Status) {
        echo $tpl->widget_rouge("{error}", $CROWDSEC_CLOUD_PRIVS->Error);
        return $tpl;
    }

    $PRIVS = array();

    if (!property_exists($CROWDSEC_CLOUD_PRIVS, "features")) {
        $tpl->table_form_field_js("Loadjs('$page?consoleprivs-js=yes')");
        $tpl->table_form_field_text("{crowdsec_webconsole} {privileges}", "<small>{none} {error}</small>", ico_admin);
        return $tpl;
    }


    if ($CROWDSEC_CLOUD_PRIVS->features->manual) {
        $PRIVS[] = "{ShareManualDecisions}";
    }
    if ($CROWDSEC_CLOUD_PRIVS->features->tainted) {
        $PRIVS[] = "{ShareTaintedScenarios}";
    }
    if ($CROWDSEC_CLOUD_PRIVS->features->custom) {
        $PRIVS[] = "{ShareCustomScenarios}";
    }
    if ($CROWDSEC_CLOUD_PRIVS->features->console_management) {
        $PRIVS[] = "{ConsoleManagement}";
    }
    if ($CROWDSEC_CLOUD_PRIVS->features->context) {
        $PRIVS[] = "{ShareContext}";
    }
    if (count($PRIVS) == 0) {
        $PRIVS[] = "{none}";
    }

    $tpl->table_form_field_js("Loadjs('$page?consoleprivs-js=yes')");
    $tpl->table_form_field_text("{crowdsec_webconsole} {privileges}", "<small>" . @implode(", ", $PRIVS) . "</small>", ico_admin);
    return $tpl;
}

function form_js():string{
    $page       = CurrentPageName();
    $tpl                = new template_admin();
    $f[]="LoadAjax('crowdsec-config','$page?config-center=yes');";
    $f[]="dialogInstance1.close();";
    $f[]=$tpl->framework_buildjs("/crowdsec/reconfigure","crowdsec.reconfigure.progress",
        "crowdsec.reconfigure.log","progress-crowdsec-restart","LoadAjax('crowdsec-status','$page?status=yes');");
    return @implode("",$f);
}
function decision_js():string{
    $page       = CurrentPageName();
    $f[]="LoadAjax('crowdsec-config','$page?config-center=yes');";
    $f[]="dialogInstance1.close();";
    return @implode("",$f);
}


function page():bool{
	$page               = CurrentPageName();
	$tpl                = new template_admin();
	$IPTABLES_VERSION   = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_CROWDSEC_VERSION");
$html=$tpl->page_header("{APP_CROWDSEC} v$IPTABLES_VERSION",
    "far fa-shield-cross","{APP_CROWDSEC_EXPLAIN}","$page?tabs=yes","crowdsec-status",
        "progress-crowdsec-restart",false,"table-crowdsec");

	if(isset($_GET["main-page"])){
		$tpl=new template_admin("{APP_CROWDSEC}",$html);
		echo $tpl->build_firewall();
		return true;
	}
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function crowdsec_status():bool{
    $page               = CurrentPageName();
    $status1=crowdsec_status_widget();
    $status2=Bouncer_status();
    $js="LoadAjaxSilent('crowdsec-top','$page?status-top=yes');";
    echo "$status1<p>&nbsp;</p>$status2<script>$js</script>";
    return true;
}

function crowdsec_status_widget():string{
    $tpl                = new template_admin();
    $restartjs="Loadjs('fw.crowdsec.php?restart=yes');";
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/status");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->widget_rouge("API ERROR",json_last_error_msg());

    }else {
        if (!$json->Status) {
            return $tpl->widget_rouge("API ERROR", $json->Error);
        } else {
            $ini = new Bs_IniHandler();
            $ini->loadString($json->Info);
            return  $tpl->SERVICE_STATUS($ini, "APP_CROWDSEC", $restartjs);
        }
    }

}

function Bouncer_status():string{
    $page =currentPageName();
    $tpl                = new template_admin();
    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/bouncer/status");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->widget_rouge("API ERROR",json_last_error_msg());

    }else {
        if (!$json->Status) {
            return $tpl->widget_rouge("API ERROR", $json->Error);
        } else {
            if(strlen($json->Info)>10) {
                $ini = new Bs_IniHandler();
                $ini->loadString($json->Info);

                $jsRestart=$tpl->framework_buildjs("/crowdsec/firewall/bouncer/restart",
                    "crowdsec.reconfigure.progress","crowdsec.reconfigure.log",
                    "progress-crowdsec-restart",
                    "LoadAjax('crowdsec-status','$page?status=yes');"
                );


                return $tpl->SERVICE_STATUS($ini, "APP_IPTABLES_BOUNCER", $jsRestart);
            }
        }
    }
    return "";
}

function threats_form():bool{
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    ListWhitelisted();
    echo "<div style='margin-top:15px'>";
    echo $tpl->search_block($page);
    echo "</div>";
    return true;
}
function artica_form():bool{
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    ListWhitelisted();
    echo "<div style='margin-top:15px'>";
    echo $tpl->search_block($page,null,null,null,"&artica-search=yes");
    echo "</div>";
    return true;

}

function alerts_form():bool{
    $page               = CurrentPageName();
    $tpl                = new template_admin();
    ListWhitelisted();
    echo "<div style='margin-top:15px'>";
    echo $tpl->search_block($page,null,null,null,"&alerts-search=yes");
    echo "</div>";
    return true;

}
function artica_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/crowdsec-events.db");
    VERBOSE(__FUNCTION__,__LINE__);



    //EventsCount, message,scenario,alertid,ipaddr
    $modtools=new modesctools();
    $html[]="			<table class='table table-striped'>";
    $html[]="				<thead>";
    $html[]="					<tr>";
    $html[]="						<th nowrap></th>";
    $html[]="                        <th nowrap>&nbsp;</th>";
    $html[]="                        <th nowrap>{host}</th>";
    $html[]="						<th nowrap>{events}</th>";
    $html[]="						<th nowrap>{type}</th>";
    $html[]="                        <th>{events}</th>";
    $html[]="					</tr>";
    $html[]="				</thead>";
    $html[]="				<tbody>";
    $TRCLASS=null;
    $Inum=false;
    $sql="";
    $search=$_GET["search"];
    if(is_numeric($search)){
        if(strpos($search,".")==0){
            $Inum=true;
        }
    }

    if($Inum){
        $sql="SELECT * FROM alerts WHERE alertid=$search ORDER BY ID DESC LIMIT 250";
    }


    if($sql==""){
        if(preg_match("#^[0-9\.]+#",$search)){
            $sql="SELECT * FROM alerts WHERE ipaddr='$search' LIMIT 250";
        }
    }
    if($sql==""){
        if(strlen($search)>0){
            $search="*$search*";
            $search=str_replace("**","*",$search);
            $search=str_replace("**","*",$search);
            $search=str_replace("*","%",$search);
            $sql="SELECT * FROM alerts WHERE ( 
                (ipaddr LIKE '$search') 
                    OR (scenario LIKE '$search')
                    OR (message LIKE '$search') ORDER BY ID DESC LIMIT 250";
        }
    }



    if($sql==null){
        $sql="SELECT * FROM alerts ORDER by ID DESC LIMIT 250";
    }



    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $function=$_GET["function"];
    foreach ($results as $ligne){
        $alertid=$ligne["alertid"];
        $scenario=$ligne["scenario"];
        $Ipaddr=$ligne["ipaddr"];
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $message=$ligne["message"];
        $events_count=$ligne["EventsCount"];
        $md=md5(serialize($ligne));
        $label="label-danger";
        $text="{alert}";
        $flag="&nbsp;";
        $white="";
        if(strlen($Ipaddr)>3) {
            $Ipaddrenc=urlencode($Ipaddr);

            $modtools->hostinfo($Ipaddr);
            if($modtools->hostid>0){
                $Ipaddr = $tpl->td_href($Ipaddr,$modtools->country,"Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$Ipaddrenc')");
                $flag="<img src='img/".$modtools->flag."'>";
            }

            $src="<li class='".ico_computer."'></li>&nbsp;$Ipaddr";
            $js = "Loadjs('$page?whitelist-js=$Ipaddrenc&function=$function')";
            $white = $tpl->button_tooltip("{whitelist}", $js, ico_ok, "AsFirewallManager","label-danger");
        }

        if(isset($_SESSION["NETTRUSTED"][$Ipaddr])){
            $text="{info}";
            $label="label-default";
            $white = $tpl->button_tooltip("{trusted}", null, ico_ok, "AsFirewallManager","label-default");
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'><span class='label $label'>$text</span></td>";
        $html[]="<td style='width:1%' class='left' nowrap>$flag</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$src</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$events_count</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$scenario</td>";
        $html[]="<td style='width:99%' class='left'>$message</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$white</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";

    $TINY_ARRAY["TITLE"]="{APP_CROWDSEC} {threats}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_CROWDSEC_EXPLAIN}";
    $TINY_ARRAY["URL"]="crowdsec-status";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]=$jstiny;
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}

function threads_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $search=$_GET["search"];

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/crowdsec/stats"));
    if(!$json->Status){
        $html=$tpl->widget_style1("red-bg",ico_bug,"{error}",$json->Error);
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }
    $Metrics=$json->Metrics;

    if(!property_exists($Metrics,"alerts")){
        $html=$tpl->widget_style1("red-bg",ico_bug,"{error}","No property");
        echo $tpl->_ENGINE_parse_body($html);
        return false;
    }

    $html[]="			<table class='table table-striped'>";
    $html[]="				<thead>";
    $html[]="					<tr>";
    $html[]="						<th nowrap></th>";
    $html[]="						<th nowrap>{engine}</th>";
    $html[]="                        <th style='width:1%;font-size:16px;text-align: right'>{threats}</th>";
    $html[]="					</tr>";
    $html[]="				</thead>";
    $html[]="				<tbody>";
    $TRCLASS=null;
    foreach ($Metrics->alerts as $alert=>$threats){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        if(strpos($alert,"/")>0){
            $aa=explode("/",$alert);
            $alert=$aa[1];
        }
        if(strlen($search)>0){
            if(!preg_match("#".$search."#",$alert)){
                continue;
            }
        }

        $md=md5("$alert$threats");
        $threats=$tpl->FormatNumber($threats);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%;;font-size:16px'><i class='".ico_list."'></i></td>";
        $html[]="<td style='width:99%;font-size:16px' nowrap>$alert</td>";
        $html[]="<td style='width:1%;font-size:16px;text-align: right' class='font-bold' nowrap>$threats</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function alerts_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/crowdsec.db");
    $function=$_GET["function"];

    $html[]="			<table class='table table-striped'>";
    $html[]="				<thead>";
    $html[]="					<tr>";
    $html[]="						<th nowrap>ID/Count</th>";
    $html[]="                        <th>{updated_at}</th>";
    $html[]="						<th nowrap>{host}</th>";
    $html[]="						<th nowrap>{type}</th>";
    $html[]="                        <th>{events}</th>";
    $html[]="                        <th>&nbsp;</th>";
    $html[]="					</tr>";
    $html[]="				</thead>";
    $html[]="				<tbody>";
    $TRCLASS=null;
    $Inum=false;
    $sql=null;
    $search=$_GET["search"];
    if(is_numeric($search)){
        if(strpos($search,".")==0){
            $Inum=true;
        }
    }

    if($Inum){
        $sql="SELECT * FROM alerts WHERE id=$search or alert_decisions=$search LIMIT 500";
    }
    if($sql==null){
        if(preg_match("#^[0-9\.]+#",$search)){
            $sql="SELECT * FROM alerts WHERE source_ip='$search'";
        }
    }


    if($sql==null) {
        if (strlen($search) > 0) {
            $search = "*$search*";
            $search = str_replace("**", "", $search);
            $search = str_replace("**", "", $search);
            $search = str_replace("*", "%", $search);
            $sql = "SELECT * FROM alerts WHERE ( ( source_ip LIKE '$search' ) OR ( scenario LIKE '$search') 
                                    OR ( source_scope LIKE '$search')
                                    OR ( message LIKE '$search')
                                    OR ( source_range LIKE '$search')
            ) ORDER by id DESC LIMIT 500";
        }
    }


    if($sql==null){
        $sql="SELECT * FROM alerts ORDER by id DESC LIMIT 500";
    }



    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $tpl->div_error($q->mysql_error);
    }
    $now=time();
    foreach ($results as $ligne){

       // [id] => 184 [created_at] => 2023-07-13 09:19:25.289652718+00:00 [updated_at] => 2023-07-13 09:19:25.289652988+00:00 [scenario] => update : +14229/-3 IPs [bucket_id] => [message] => [events_count] => 0 [started_at] => 2023-07-13 09:19:25+00:00 [stopped_at] => 2023-07-13 09:19:25+00:00 [source_ip] => [source_range] => [source_as_number] => [source_as_name] => [source_country] => [source_latitude] => 0.0 [source_longitude] => 0.0 [source_scope] => crowdsecurity/community-blocklist [source_value] => [capacity] => 0 [leak_speed] => [scenario_version] => [scenario_hash] => [simulated] => 0 [uuid] => [machine_alerts] => )

      //  print_r($ligne);

        $id=$ligne["id"];
        if(preg_match("#^(.+?)\.#",$ligne["updated_at"],$re)){
            $ligne["updated_at"]=$re[1];
        }

        $updated_at=strtotime($ligne["updated_at"]);
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $Ipaddr=$ligne["source_ip"];
        $type=$ligne["scenario"];
        $origin=$ligne["source_scope"];
        $message=$ligne["message"];
        $source_range=$ligne["source_range"];
        $events_count=$ligne["events_count"];
        $Ipaddrenc=urlencode($Ipaddr);
        $white="";
        $UpdatedAt=$tpl->time_to_date($updated_at,true);

        if(strlen($Ipaddr)>3) {
            $js = "Loadjs('$page?whitelist-js=$Ipaddrenc&function=$function')";
            $white = $tpl->button_tooltip("{whitelist}", $js, ico_ok, "AsFirewallManager","label-danger");
        }
        if(isset($_SESSION["NETTRUSTED"][$Ipaddr])){
            $white = $tpl->button_tooltip("{trusted}", null, ico_ok, "AsFirewallManager","label-default");
        }

        if($source_range<>null){
            $source_range="&nbsp;/&nbsp;$source_range";
        }
        $src=trim("$Ipaddr$source_range");
        if($src<>null){
            $src="<li class='".ico_computer."'></li>&nbsp;$src";
        }
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%'>$id/$events_count</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$UpdatedAt / {$ligne["stopped_at"]}</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$src</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$origin/$type</td>";
        $html[]="<td style='width:99%' class='left'>$message</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$white</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";

    $TINY_ARRAY["TITLE"]="{APP_CROWDSEC} {alerts}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_CROWDSEC_EXPLAIN}";
    $TINY_ARRAY["URL"]="crowdsec-status";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]=$jstiny;
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function resolv_this():bool{
    $sock=new sockets();
    $ips=array();
    foreach ( $_SESSION["RESOLV_THIS"] as $ipaddr){
        $ips[]=$ipaddr;


    }

    if(count($ips)==0){return true;}
    $sock->REST_API(sprintf("/system/network/scanips/%s",@implode(",",$ips)));
    return true;
}

function decisions_list_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $q=new postgres_sql();
    VERBOSE(__FUNCTION__,__LINE__);
    $html[]="			<table class='table table-striped'>";
    $html[]="				<thead>";
    $html[]="					<tr>";
    $html[]="						<th nowrap>&nbsp;</th>";
    $html[]="						<th nowrap>{host}</th>";
    $html[]="                        <th nowrap>{during}</th>";
    $html[]="						<th>Del</th>";
    $html[]="					</tr>";
    $html[]="				</thead>";
    $html[]="				<tbody>";



    $sql="SELECT * FROM crowdsec_blacklist LIMIT 250";
    $TRCLASS=null;
    $search=$_GET["search"];
    if($search=="*"){$search="";}
    $ipclass=new IP();
    if($search<>null){
        $ANDB="";
        if(strpos($search,"/")>0) {
            $ANDB = "WHERE '$search' >> ipaddr";
        }
        if($ipclass->IsValid($search)){
            $ANDB = "WHERE ipaddr = '$search'";
        }

        if(strpos($search,"*")>0){
            $search=str_replace("*","%",$search);
            $ANDB = "WHERE ipaddr::text LIKE '$search'";
        }

        if($ANDB==""){
            $tb=explode(".",$search);

            if(count($tb)==1){
                $ANDB = "WHERE '$tb[0].0.0.0/8' >> ipaddr";
            }

            if(count($tb)==2){
                $ANDB = "WHERE '$tb[0].$tb[1].0.0/16' >> ipaddr";
            }
            if(count($tb)==3){
                $ANDB = "WHERE '$tb[0].$tb[1].$tb[2].0/24' >> ipaddr";
            }

        }
        $sql="SELECT * FROM crowdsec_blacklist $ANDB LIMIT 250";
    }

    $current_time = time();
    $function=$_GET["function"];

    $modtools=new modesctools();
    $_SESSION["RESOLV_THIS"]=array();
    $c=0;
    $results=$q->QUERY_SQL($sql);
    while($ligne=@pg_fetch_assoc($results)){
        $opts=array();
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        if($search<>null){
            if(!preg_match("#$search#",@implode(" ",$ligne))){continue;}
        }
        $duration="";
        $Ipaddr=$ligne["ipaddr"];
        $modtools->hostinfo($Ipaddr);
        $timeout=$ligne["timeout"];

        if(isset($ligne["timeout"])) {
            $new_time = $current_time + intval($timeout);
            $duration=distanceOfTimeInWords($current_time,$new_time,true);
        }
        $ipaddmd=md5($Ipaddr);
        $Ipaddrenc=urlencode($Ipaddr);
        $flag="&nbsp;";
        $_SESSION["RESOLV_THIS"][]=$Ipaddr;
        if($modtools->hostid>0){
            $Ipaddr = $tpl->td_href($Ipaddr,$modtools->country,"Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$Ipaddrenc')");
            $flag="<img src='img/".$modtools->flag."'>";
        }

        if(strlen($modtools->hostname)>0){
            $opts[]=$modtools->hostname;
        }
        if(strlen($modtools->country_name)>0){
            $opts[]=$modtools->country_name;
        }
        if(strlen($modtools->city)>0){
            $opts[]=$modtools->city;
        }
        $options=sprintf("<br><i>%s</i>",@implode(" | ",$opts));

        $del=$tpl->icon_delete("Loadjs('$page?whitelist-js=$Ipaddrenc&function=$function')");
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' class='left' nowrap>$flag</td>";
        $html[]="<td style='width:99%' class='left' nowrap><li class='".ico_computer."'></li>&nbsp;$Ipaddr$options</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$duration</td>";
        $html[]="<td style='width:1%' class='left' nowrap>$del</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="Loadjs('$page?resolv-this=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function threats_search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    VERBOSE(__FUNCTION__,__LINE__);
    $html[]="			<table class='table table-striped'>";
    $html[]="				<thead>";
    $html[]="					<tr>";
    $html[]="						<th nowrap>{host}</th>";
    $html[]="						<th>{will_be_removed_on}</th>";
    $html[]="						<th>&nbsp;</th>";
       $html[]="					</tr>";
    $html[]="				</thead>";
    $html[]="				<tbody>";
    $TRCLASS=null;
    $Inum=false;
    $sql=null;
    $search=$_GET["search"];
    $sql = "SELECT * FROM crowdsec_blacklists ORDER BY timeout DESC LIMIT 250 ";

    if(strlen($search)>1) {
        $sql = "SELECT * FROM crowdsec_blacklists WHERE ipaddr = '$search' ORDER BY timeout DESC LIMIT 250";
        if (strpos($search, "/") > 0) {
            $sql = "SELECT * FROM crowdsec_blacklists WHERE ipaddr << '$search' ORDER BY timeout DESC LIMIT 250";
        }
    }
    $modtools=new modesctools();
    $function=$_GET["function"];
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $tpl->div_error($q->mysql_error);}
    $ipclass=new IP();
    $now=time();
    while ($ligne = pg_fetch_assoc($results)) {

        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $Ipaddr=$ligne["ipaddr"];
        $timeout=intval($ligne["timeout"]);
        $RestTime="";
        if($timeout>0) {
            $NextDate = time() + $timeout;
            $date=$tpl->time_to_date($NextDate,true );
            $RestTime = $date ."&nbsp;(".distanceOfTimeInWords(time(), $NextDate).")";
        }
        $flag="";
        $white="";
        $hostname="";
        if(strlen($Ipaddr)>3) {
            $modtools->hostinfo($Ipaddr,false);
            VERBOSE("$Ipaddr = $modtools->hostid",__LINE__);
            $Ipaddrenc=urlencode($Ipaddr);
            if($modtools->hostid>0) {
                $Ipaddr = $tpl->td_href($Ipaddr,$modtools->country,"Loadjs('fw.modsecurity.threats.php?zoom-ip-js=$Ipaddrenc')");
                $flag="<img src='img/".$modtools->flag."'>&nbsp;";
                $hostname=$modtools->hostname;
                if($ipclass->isValid($hostname)){
                    $hostname="";
                }
            }

            $js = "Loadjs('$page?whitelist-js=$Ipaddrenc&function=$function')";
            $white = $tpl->button_tooltip("{whitelist}", $js, ico_ok, "AsFirewallManager","label-danger");
        }
        if(isset($_SESSION["NETTRUSTED"][$Ipaddr])){
            $white = $tpl->button_tooltip("{trusted}", null, ico_ok, "AsFirewallManager","label-default");
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:99%' class='left' nowrap><li class='".ico_computer."'></li>&nbsp;$flag$Ipaddr&nbsp;$hostname</td>";
        $html[]="<td style'width:1%' class='left' nowrap>$RestTime</td>";
        $html[]="<td style'width:1%' class='left' nowrap>$white</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";

    $sum=$tpl->FormatNumber($q->COUNT_ROWS("crowdsec_blacklists"));

    $TINY_ARRAY["TITLE"]="{firewall_rules} $sum {records}";
    $TINY_ARRAY["ICO"]=ico_eye;
    $TINY_ARRAY["EXPL"]="{APP_CROWDSEC_EXPLAIN}";
    $TINY_ARRAY["URL"]="crowdsec-status";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]=$jstiny;
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function decisions_list():array{
    $MAIN=array();
    $tfile=PROGRESS_DIR."/crowdsec-blacklists.list";
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("crowdsec.php?ipset-list=yes");
    $tpl = new template_admin();
    $sock=new sockets();
    $data=$sock->REST_API("/crowdsec/decision/list");
    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error($tpl->_ENGINE_parse_body(json_last_error_msg()));
        return array();

    }
    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return array();
    }

    $Decisions=$json->List;

    foreach ($Decisions as $class){
        if(!property_exists($class,"decisions")){continue;}
        foreach ($class->decisions as $decisions){
            $duration=$decisions->duration;
            $scenario=$decisions->scenario;
            $blacklisted=$decisions->value;
            $HASHMAIN[$blacklisted]=true;
            $MAIN[]=array(
                "scenario"=>$scenario,
                 "ipaddr"=>$blacklisted,
                 "duration"=>$duration,
                 "date"=>$class->created_at
            );
        }
    }

    $explode=explode("\n",@file_get_contents($tfile));
    foreach ($explode as $line){
        $line=trim($line);
        if(!preg_match("#^([0-9\.]+).*?timeout.*?([0-9]+)#",$line,$re)){continue;}
        $blacklisted=$re[1];
        $duration=$re[2];
        if(isset($HASHMAIN[$blacklisted])){continue;}
        $MAIN[]=array(
            "scenario"=>"-",
            "ipaddr"=>$blacklisted,
            "timeout"=>$duration,
            "date"=>""
        );
    }

    return $MAIN;
}