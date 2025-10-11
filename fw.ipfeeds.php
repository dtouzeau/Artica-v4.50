<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.iptables.inc");
if(isset($_GET["ProtectMySite"])){ProtectMySite_js();exit;}
if(isset($_GET["ProtectMySite-popup"])){ProtectMySite_popup();exit;}
if(isset($_POST["ProtectMySite"])){ProtectMySite_save();exit;}
if(isset($_POST["Honeypot"])){Honeypot_save();exit;}
if(isset($_GET["main-params"])){main_params_js();exit;}
if(isset($_GET["main-params-popup"])){main_params_popup();exit;}
if(isset($_POST["Params"])){main_params_save();exit;}
if(isset($_GET["delete-blocked"])){delete_blocked_js();exit;}
if(isset($_POST["delete-blocked"])){delete_blocked_confirm();exit;}
if(isset($_GET["Honeypot-js"])){Honeypot_js();exit;}
if(isset($_GET["Honeypot-popup"])){Honeypot_popup();exit;}

if(isset($_GET["events-popup"])){events_popup();exit;}
if(isset($_GET["events-searcher"])){events_searcher();exit;}
if(isset($_GET["white"])){action_white_ip();exit;}
if(isset($_POST["white"])){action_white_ip_save();exit;}
if(isset($_GET["white-popup"])){action_white_ip_popup();exit;}

if(isset($_GET["banip"])){action_ban_ip();exit;}
if(isset($_GET["banips-popup"])){action_ban_ip_popup();exit;}
if(isset($_POST["banip"])){action_ban_ip_save();exit;}
if(isset($_GET["checkip"])){action_checkip();exit;}
if(isset($_GET["checkip-popup"])){action_checkip_popup();exit;}
if(isset($_GET["delete-white"])){action_delete_whiteip_js();exit;}
if(isset($_POST["delete-white"])){action_delete_whiteip_confirm();exit;}


if(isset($_GET["AbuseIPDB-js"])){AbuseIPDB_js();exit;}
if(isset($_GET["AbuseIPDB-popup"])){AbuseIPDB_popup();exit;}


if(isset($_POST["checkip"])){action_checkip_save();exit;}


if(isset($_GET["category"])){category_js();exit;}
if(isset($_POST["category"])){category_save();exit;}
if(isset($_POST["category-extended"])){category_extended_save();exit;}
if(isset($_GET["category-popup"])){category_popup();exit;}
if(isset($_GET["category-enable"])){category_enable();exit;}
if(isset($_GET["category-refresh"])){category_refresh();exit;}
if(isset($_GET["category-delete"])){category_delete_ask();exit;}
if(isset($_POST["category-delete"])){category_delete_confirm();exit;}
if(isset($_GET["categories"])){categories();exit;}
if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["sources"])){sources_start();exit;}
if(isset($_GET["sources-search"])){sources_search();exit;}



if(isset($_GET["nic-form-settings-build"])){nic_form_settings_build();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["articapcap-reload"])){articapcap_reload();exit;}
if(isset($_GET["blacklisted"])){blacklisted_start();exit;}
if(isset($_GET["whitelists"])){whitelists_start();exit;}
if(isset($_GET["whitelists-search"])){whitelists_search();exit;}



if(isset($_GET["cyberipcrime-disable"])){CybercrimeIPFeeds_disable();exit;}
if(isset($_GET["cyberipcrime-enable"])){CybercrimeIPFeeds_enable();exit;}
if(isset($_POST["cyberipcrime-disable"])){CybercrimeIPFeeds_disable_confirm();exit;}

if(isset($_POST["ArticaPSnifferIpSetEnabled"])){SaveTokensSettings();exit;}
if(isset($_POST["ArticaPSnifferProxMoxEnabled"])){SaveTokensSettings();exit;}
if(isset($_POST["ArticaPSnifferHTTPInterface"])){SaveTokensSettings();exit;}
if(isset($_POST["ArticaPSnifferPfSenseEnable"])){SaveTokensSettings();exit;}


if(isset($_POST["IPFeedsUseIPset"])){SaveTokensSettings();exit;}
if(isset($_POST["EnableFireholIPSets"])){Save();exit;}
if(isset($_GET["top-status"])){top_status();exit;}
if(isset($_GET["left-status"])){left_status();exit;}
if(isset($_GET["left-status-pcap"])){echo left_status_pcap();exit;}
if(isset($_GET["pcap-refresh"])){left_status_refresh();exit;}
if(isset($_GET["fixed-params"])){fixed_params();exit;}

if(isset($_GET["ipfeed-articapcap"])){params_ipfeed_pcap_js();exit;}
if(isset($_GET["ipfeed-articapcap-popup"])){params_ipfeed_pcap_popup();exit;}

if(isset($_GET["ipfeed-articapcap-api"])){params_api_js();exit;}
if(isset($_GET["ipfeed-articapcap-api-popup"])){params_api_popup();exit;}

if(isset($_GET["articapcap-stats"])){articapcap_stats();exit;}
if(isset($_GET["articapcap-stats-popup"])){articapcap_stats_popup();exit;}

if(isset($_GET["ipfeed-proxmox"])){params_proxmox_js();exit;}
if(isset($_GET["ipfeed-proxmox-popup"])){params_proxmox_popup();exit;}

if(isset($_GET["ipfeed-iptables"])){params_iptables_js();exit;}
if(isset($_GET["ipfeed-iptables-popup"])){params_iptables_popup();exit;}

if(isset($_GET["ipfeed-fortigate"])){params_fortigate_js();exit;}
if(isset($_GET["ipfeed-fortigate-popup"])){params_fortigate_popup();exit;}

if(isset($_GET["ipfeed-pfsense"])){params_pfsense_js();exit;}
if(isset($_GET["ipfeed-pfsense-popup"])){params_pfsense_popup();exit;}

if(isset($_GET["blacklisted"])){blacklisted_start();exit;}
if(isset($_GET["blacklisted-search"])){blacklisted_search();exit;}
if(isset($_GET["search"])){search();exit;}


page();

function tabs(){
    $page=CurrentPageName();
    $tpl=new template_admin();
    $array["{parameters}"]="$page?parameters=yes";
    $array["{statistics}"]="fw.ipfeeds.stats.php";
    $array["{sources}"]="$page?sources=yes";
    $array["{denied_ip_sources}"]="$page?blacklisted=yes";
    $array["{whitelists}"]="$page?whitelists=yes";
    $array["{events}"]="$page?events-popup=yes";
    echo $tpl->tabs_default($array);
}
function delete_blocked_js():bool{
    $tpl=new template_admin();
    $ipaddr=$_GET["delete-blocked"];
    $md=$_GET["md"];
    return $tpl->js_confirm_delete($ipaddr,"delete-blocked",$ipaddr,"$('#$md').remove();");
}
function action_delete_whiteip_js():bool{
    $tpl=new template_admin();
    $ipaddr=$_GET["delete-white"];
    $md=$_GET["md"];
    return $tpl->js_confirm_delete($ipaddr,"delete-white",$ipaddr,"$('#$md').remove();");
}
function delete_blocked_confirm():bool{
    $ipaddr=urlencode($_POST["delete-blocked"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/blocked/remove/$ipaddr"));
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
    return admin_tracks("Removed {$_POST["delete-blocked"]} from Cybercrime IP Feeds block database");
}
function action_delete_whiteip_confirm():bool{
    $ipaddr=urlencode($_POST["delete-white"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/white/remove/$ipaddr"));
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
    return admin_tracks("Removed {$_POST["delete-white"]} from Cybercrime IP Feeds white database");
}
function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html=$tpl->page_header("{CybercrimeIPFeeds}",
        "fas fa-hockey-mask",
        "{CybercrimeIPFeeds_explain}","$page?tabs=yes","Cybercrime-IP-Feeds","progress-CybercrimeIPFeeds-restart",false,"table-CybercrimeIPFeeds");
    if(isset($_GET["main-page"])){
        $tpl=new template_admin("{CybercrimeIPFeeds}",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function action_ban_ip():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function="";
    if(isset($_GET["function"])){
        $function="&function=".$_GET["function"];
    }
    return $tpl->js_dialog_modal("{ban_ips}","$page?banips-popup=yes$function");
}
function action_white_ip():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function="";
    if(isset($_GET["function"])){
        $function="&function=".$_GET["function"];
    }
    return $tpl->js_dialog_modal("{allow_an_IP_address}","$page?white-popup=yes$function");
}
function action_checkip():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog_modal("{check_ip_reputation}","$page?checkip-popup=yes");
}
function action_ban_ip_popup():bool{
    $tpl=new template_admin();
    $html[]=$tpl->div_explain("{ban_ips}||{field_ipaddr_cdir_comma}",true);
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"]."();";
    }
    $form[]=$tpl->field_text_big("banip","{network2}","",true);
    $html[]=$tpl->form_outside("",$form,"","NO","DialogModal.close();$function");
    $button=$tpl->button_autnonome("{close}","DialogModal.close();",ico_exit);
    $html[]="<div style='margin:10px;text-align:right;'>$button</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function action_white_ip_popup():bool{
    $tpl=new template_admin();
    $html[]=$tpl->div_explain("{allow_an_IP_address}||{field_ipaddr_cdir_comma}",true);
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"]."();";
    }
    $form[]=$tpl->field_text_big("white","{network2}","",true);
    $html[]=$tpl->form_outside("",$form,"","NO","DialogModal.close();$function");
    $button=$tpl->button_autnonome("{close}","DialogModal.close();",ico_exit);
    $html[]="<div style='margin:10px;text-align:right;'>$button</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function action_checkip_popup():bool{
    $tpl=new template_admin();
    $html[]="<div id='check-ip-div'></div>";
    $form[]=$tpl->field_text_big("checkip","{ipaddr}","",true);
    $html[]=$tpl->form_outside("",$form,"","NO","");
    $button=$tpl->button_autnonome("{close}","DialogModal.close();",ico_exit);
    $html[]="<div style='margin:10px;text-align:right;'>$button</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function action_checkip_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $checkip=urlencode($_POST["checkip"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/query/$checkip"));
    echo $tpl->post_error($json->Info."(".$json->Category.")");
    return true;
}
function action_white_ip_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $checkip=urlencode($_POST["white"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/white/$checkip"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }

    return admin_tracks("Cybercrime IP add {$_POST["white"]} to whitelisted IPs");
}
function action_ban_ip_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode(",",$_POST["banip"]);
    foreach ($tb as $value){
        $value=urlencode($value);
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/block/$value"));
        if(!$json->Status){
            echo $tpl->post_error($json->Error);
            return false;
        }
    }
    return admin_tracks("Cybercrime IP add {$_POST["banip"]} to banned IPs");
}
function sources_start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<div style='margin-top:15px'></div>";
    $html[]=$tpl->search_block($page,"","","","&sources-search=yes");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function sources_search():bool{
    $t=time();
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $EnableArticaNFQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue"));
    if($EnableArticaNFQueue==0){
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/sources"));

    if(!$json->Status){
        echo $tpl->div_error($json->Error);
        return false;
    }
    if(!property_exists($json,"Sources")){
        echo $tpl->div_error("Protocol error");
        return false;
    }
    $Sources=$json->Sources;

    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{status}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{queries}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{records}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{domain}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{updated}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{active2}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'></th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS="";



    foreach ($Sources as $ligne){
        $category=$ligne->categoryname;
        if($category=="AbuseIPDB"){
            $JsonAbuseIPDB=$ligne;
        }
        if($category=="AbuseIPDB_API"){
            $JsonAbuseIPDBAPI=$ligne;
        }

    }
    $CATFIXED["ArticaCloud"]=true;
    $CATFIXED["DroneRBL"]=true;
    $CATFIXED["FireholLevel1"]=true;
    $CATFIXED["FireholProxies"]=true;
    $CATFIXED["Intelligence_IPv4_Blocklist"]=true;

    foreach ($Sources as $ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        if($ligne->categoryname=="AbuseIPDB" or $ligne->categoryname=="AbuseIPDB_API"){
            continue;
        }
        $status="<span class='label label-primary'>{active2}</span>";
        $md=md5(serialize($ligne));
        $icodb=ico_database;
        $category=$ligne->categoryname;
        $enabled=$ligne->enabled;
        $url=$ligne->url;
        $arrayURI=parse_url($url);
        $hostname=$arrayURI["host"];
        $Records=$tpl->FormatNumber($ligne->Records);
        $TimeStamp="<i class='".ico_clock."'>";
        if($ligne->TimeStamp>0) {
            $TimeStamp = $tpl->time_to_date($ligne->TimeStamp, true);
        }
        $description=$ligne->description;
        $description_text="";
        if(strlen($description)>1){
            $description_text="<div><i><small>$description</small></i></div>";
        }
        $categoryEnc=urlencode($category);
        $category=$tpl->td_href($category,"","Loadjs('$page?category=$categoryEnc&function=$function')");
        $enabled=$tpl->icon_check($enabled,"Loadjs('$page?category-enable=$categoryEnc')");
        $reload=$tpl->icon_recycle("Loadjs('$page?category-refresh=$categoryEnc&function=$function')");
        if($ligne->enabled==0) {
            $reload="&nbsp;";
            $Records="&nbsp;";
            $TimeStamp="&nbsp;";
            $status="<span class='label label-default'>{inactive}</span>";
        }
        if($ligne->rbl_source==1 OR $ligne->doh_source==1){
            continue;
        }
        $delete=$tpl->icon_delete("Loadjs('$page?category-delete=$categoryEnc&md=$md')");
        if(isset($CATFIXED[$ligne->categoryname])){
            $delete=$tpl->icon_delete("");
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$status</td>";
        $html[]="<td style='width:99%' nowrap><i class=\"$icodb\"></i>&nbsp;<strong style='font-size:16px'>$category</strong>$description_text</td>";
        $html[]="<td style='width:1%;font-size:16px;text-align:center' nowrap>&nbsp;</td>";
        $html[]="<td style='width:1%;font-size:16px;text-align:right' nowrap>$Records</td>";
        $html[]="<td style='width:1%;font-size:16px' nowrap>$hostname</td>";
        $html[]="<td style='width:1%;font-size:16px' nowrap>$TimeStamp</td>";
        $html[]="<td style='width:1%' nowrap>$enabled</td>";
        $html[]="<td style='width:1%' nowrap>$reload</td>";
        $html[]="<td style='width:1%' nowrap>$delete</td>";
        $html[]="</tr>";

    }


    $html[]=RowProtectMySite($TRCLASS);

    foreach ($Sources as $ligne){
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        if($ligne->categoryname=="AbuseIPDB" or $ligne->categoryname=="AbuseIPDB_API"){
            continue;
        }
        $status="<span class='label label-primary'>{active2}</span>";
        $md=md5(serialize($ligne));
        $category=$ligne->categoryname;
        $enabled=$ligne->enabled;
        $url=$ligne->url;
        $arrayURI=parse_url($url);
        $hostname=$arrayURI["host"];
        $Records=$tpl->FormatNumber($ligne->Records);
        $rateLimit="&nbsp;<strong>({response_time}: ".$ligne->answer_time_ms."ms)</strong>";
        $description=$ligne->description;
        $description_text="";
        if(strlen($description)>1){
            $description_text="<div><i><small>$description</small></i></div>";
        }
        $categoryEnc=urlencode($category);
        $category=$tpl->td_href($category,"","Loadjs('$page?category=$categoryEnc&function=$function')");
        $enabled=$tpl->icon_check($enabled,"Loadjs('$page?category-enable=$categoryEnc')");
        $icodb="fa-solid fa-cloud-question";

        if($ligne->rbl_source==0 && $ligne->doh_source==0){
            continue;
        }
        $delete=$tpl->icon_delete("Loadjs('$page?category-delete=$categoryEnc&md=$md')");
        if(isset($CATFIXED[$ligne->categoryname])){
            $delete=$tpl->icon_delete("");
        }
        if($ligne->enabled==0){
            $status="<span class='label label-default'>{inactive}</span>";
            $Records="&nbsp;";
            $rateLimit="&nbsp;";
        }

        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap>$status</td>";
        $html[]="<td style='width:99%' nowrap><i class=\"$icodb\"></i>&nbsp;<strong style='font-size:16px'>$category</strong>$rateLimit$description_text</td>";
        $html[]="<td style='width:1%;font-size:16px;text-align:right' nowrap>$Records</td>";
        $html[]="<td style='width:1%;font-size:16px;text-align:center' nowrap>&nbsp;</td>";
        $html[]="<td style='width:1%;font-size:16px' nowrap>$hostname</td>";
        $html[]="<td style='width:1%;font-size:16px' nowrap>&nbsp;</td>";
        $html[]="<td style='width:1%' nowrap>$enabled</td>";
        $html[]="<td style='width:1%' nowrap>&nbsp;</td>";
        $html[]="<td style='width:1%' nowrap>$delete</td>";
        $html[]="</tr>";

    }


    if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
    $icodb="fa-solid fa-cloud-question";
    $categoryEnc=urlencode("AbuseIPDB_API");
    $status="<span class='label label-primary'>{active2}</span>";
    $Records="&nbsp;";
    if($JsonAbuseIPDBAPI->Records >0) {
        $Records = $tpl->FormatNumber($JsonAbuseIPDBAPI->Records);
    }

    $TimeStamp="<i class='".ico_clock."'>";
    if($JsonAbuseIPDBAPI->TimeStamp>0) {
        $TimeStamp = $tpl->time_to_date($JsonAbuseIPDBAPI->TimeStamp, true);
    }
    if(strlen($JsonAbuseIPDBAPI->description)<5) {
        $JsonAbuseIPDBAPI->description = "AbuseIPDB is a community-driven platform designed to fight the spread of malicious activity, spam, and cyberattacks on the internet";
    }
    $enabled=$tpl->icon_check($JsonAbuseIPDBAPI->enabled,"Loadjs('$page?category-enable=$categoryEnc')");
    $abuseIPDB_explain=$JsonAbuseIPDBAPI->description;
    $AbuseIPDB=$tpl->td_href("AbuseIPDB API","","Loadjs('$page?AbuseIPDB-js=AbuseIPDB_API&function=$function')");
    if(strlen($JsonAbuseIPDBAPI->url)<5){
        $JsonAbuseIPDBAPI->url="api.abuseipdb.com";
    }

    if($JsonAbuseIPDBAPI->enabled==0){
        $status="<span class='label label-default'>{inactive}</span>";
        $TimeStamp="&nbsp;";
    }

   $rateLimit="";

    if(property_exists($JsonAbuseIPDBAPI->abuseipdb,"rate_limit")){
        if($JsonAbuseIPDBAPI->abuseipdb->rate_limit>0) {
            $rateLimit = distanceOfTimeInWords($JsonAbuseIPDBAPI->abuseipdb->rate_limit, time());
            $rateLimit = "<br><span class='text-danger font-bold'>{limited} {since} $rateLimit/24h</span>";
        }
    }
    $md=md5(serialize(time()));
    $html[]="<tr class='$TRCLASS' id='$md'>";
    $html[]="<td style='width:1%' nowrap>$status</td>";
    $html[]="<td style='width:99%' nowrap><i class=\"$icodb\"></i>&nbsp;<strong style='font-size:16px'>$AbuseIPDB</strong>$rateLimit<br><small><i>$abuseIPDB_explain</i></small></td>";
    $html[]="<td style='width:1%;font-size:16px;text-align:right' nowrap>$Records</td>";
    $html[]="<td style='width:1%;font-size:16px;text-align:center' nowrap>&nbsp;</td>";
    $html[]="<td style='width:1%;font-size:16px' nowrap>$JsonAbuseIPDBAPI->url</td>";
    $html[]="<td style='width:1%;font-size:16px' nowrap>$TimeStamp</td>";
    $html[]="<td style='width:1%' nowrap>$enabled</td>";
    $html[]="<td style='width:1%' nowrap></td>";
    $html[]="<td style='width:1%' nowrap>&nbsp;</td>";
    $html[]="</tr>";


    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='7'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";

    $topbuttons[] = array("Loadjs('$page?category=&function=$function')", ico_plus, "{new_source}");
    $topbuttons[] = array("Loadjs('$page?checkip=yes')", ico_loupe, "{check_ip_reputation}");

    $TINY_ARRAY["TITLE"]="{CybercrimeIPFeeds}";
    $TINY_ARRAY["ICO"]="fas fa-hockey-mask";
    $TINY_ARRAY["EXPL"]="{CybercrimeIPFeeds_sources}";
    $TINY_ARRAY["URL"]="Cybercrime-IP-Feeds";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


$html[]="<small></small>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	$jstiny
	</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function RowProtectMySite($TRCLASS):string{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $page=CurrentPageName();
    if ($TRCLASS == "footable-odd") {
        $TRCLASS = null;
    } else {
        $TRCLASS = "footable-odd";
    }


    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/status"));
    if (!$json->Status) {
        return "";
    }
    if (!property_exists($json, "Config")) {
        return "";
    }
    $Config = $json->Config;
    if(!property_exists($Config, "ProtectMySite")) {
        return "";
    }
    $ProtectMySite = $Config->ProtectMySite;

    if ($TRCLASS == "footable-odd") {
        $TRCLASS = null;
    } else {
        $TRCLASS = "footable-odd";
    }
    $status="<span class='label label-primary'>{active2}</span>";
    $icodb="text-primary fa-solid fa-cloud-question";
    $queries=$tpl->FormatNumber($ProtectMySite->queries);
    $rateLimit="&nbsp;<strong>({response_time}: ".$ProtectMySite->answer_time_ms."ms)</strong>";

    if ($ProtectMySite->enabled == 0) {
        $status = "<span class='label label-default'>{inactive}</span>";
        $icodb="text-muted fa-solid fa-cloud-question";
        $queries="&nbsp;";
        $rateLimit="";
    }

     $title=$tpl->td_href("ProtectMy.site","","Loadjs('$page?ProtectMySite=yes&function=$function')");

    $md = md5(serialize(time()));
    $html[] = "<tr class='$TRCLASS' id='$md'>";
    $html[] = "<td style='width:1%' nowrap>$status</td>";
    $html[] = "<td style='width:99%' nowrap><i class=\"$icodb\"></i>&nbsp;<strong style='font-size:16px'>$title</strong>$rateLimit<br><small><i>ProtectMy.site IP reputation database - over 25M IPs updated each day - DecisionIP included</i></small></td>";
    $html[] = "<td style='width:1%;font-size:16px;text-align:right' nowrap>$queries</td>";
    $html[] = "<td style='width:1%;font-size:16px;text-align:center' nowrap>&nbsp;</td>";
    $html[] = "<td style='width:1%;font-size:16px' nowrap>ProtectMy.site</td>";
    $html[] = "<td style='width:1%;font-size:16px' nowrap>&nbsp;</td>";
    $html[] = "<td style='width:1%' nowrap>&nbsp;</td>";
    $html[] = "<td style='width:1%' nowrap></td>";
    $html[] = "<td style='width:1%' nowrap>&nbsp;</td>";
    $html[] = "</tr>";
    return @implode("\n",$html);
}
function SaveTokensSettings():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return admin_tracks_post("Saving Cybercrime IP Feeds parameters");
}
function intelligent_search($search):string{
    $search=trim($search);
    if(preg_match("#^([0-9\.\*]+)(\s|$)#",$search,$re)){
        if(strpos($search,"*")>0){
            $search=str_replace("*","%",$search);
            return  "WHERE TEXT(ipaddr) LIKE '$search'";
        }else{
            return  "WHERE ipaddr = '$search'";
        }
    }
    if(preg_match("#^([0-9\.\/]+)(\s|$)#",$search,$re)){
        return "WHERE (ipaddr << inet '$search') OR ( ipaddr ='$search')";
    }

    return "";

}
function search():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $q=new postgres_sql();
    $search=trim($_GET["search"]);
    $aliases["pattern"]="pattern";
    $querys=$tpl->query_pattern($search,$aliases);
    $MAX=$querys["MAX"];
    if($MAX==0){$MAX=150;}

    $isearch=intelligent_search($_GET["search"]);
    if($isearch<>null){$querys["Q"]=$isearch;}

    $sql="SELECT * FROM ipset_auto {$querys["Q"]} ORDER BY ipaddr LIMIT $MAX";
    $results=$q->QUERY_SQL($sql);
    $t=time();
    $TRCLASS=null;
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\" style='margin-top:0px'>";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{ipaddr}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{category}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{type}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    while ($ligne = pg_fetch_assoc($results)) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $ipaddr=$ligne["ipaddr"];
        $ztype=$ligne["ztype"];
        $category=$ligne["category"];
        $info=$tpl->icon_loupe(1,"s_PopUpFull('http://iplists.firehol.org/?ipset=$category','1024','900');");


        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td style='width:1%' nowrap><i class=\"far fa-desktop\"></i>&nbsp;<strong>{$ipaddr}</strong></td>";
        $html[]="<td style='width:99%' nowrap>$category</td>";
        $html[]="<td style='width:1%' nowrap>$info</td>";
        $html[]="<td style='width:1%' nowrap>$ztype</td>";
        $html[]="</tr>";

    }

    $html[]="</tbody>";
    $html[]="<tfoot>";

    $html[]="<tr>";
    $html[]="<td colspan='4'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<small>$sql</small>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable( { \"filtering\": { \"enabled\": false }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function main_params_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog1("{parameters}","$page?main-params-popup=yes");
}
function ProtectMySite_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function="";
    if(isset($_GET["function"])){
        $function=$_GET["function"];
    }
    return $tpl->js_dialog1("{reputation} ProtectMySite","$page?ProtectMySite-popup=yes&function=$function");
}
function ProtectMySite_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function="";
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/status"));
    if(isset($_GET["function"])){
        $function=$_GET["function"];
        if(strlen($function)>3){
            $function="$function();";
        }
    }
    if (!$json->Status) {
        echo $tpl->div_error($json->Error);
        return false;
    }
    if (!property_exists($json, "Config")) {
        echo $tpl->div_error("Bad protocol");
        return false;
    }
    $Config = $json->Config;
    if(!property_exists($Config, "ProtectMySite")) {
        echo $tpl->div_error("Wrong version, please update");
        return false;
    }
    $ProtectMySite=$Config->ProtectMySite;
    $form[]=$tpl->field_checkbox("ProtectMySite","{enable}",$ProtectMySite->enabled,true);
    $CodesEnabled=$Config->ProtectMySite->codes_enabled;
    foreach ($ProtectMySite->codes as $code=>$category) {
        $enabled=0;
        if($CodesEnabled->$code){
            $enabled=1;
        }
        $form[]=$tpl->field_checkbox($code,$category,$enabled);
    }
    $ProtectMySiteReputation_explain=$tpl->_ENGINE_parse_body("{ProtectMySiteReputation_explain}");
    $uri=$tpl->td_href("ProtectMySite","","s_PopUpFull('https://protectmy.site/',1024,768,'ProtectMySite');");
    $ProtectMySiteReputation_explain=str_replace("%s",$uri,$ProtectMySiteReputation_explain);
    echo $tpl->form_outside("",$form,$ProtectMySiteReputation_explain,"{apply}","dialogInstance1.close();LoadAjax('ipfeeds-fixed-params','$page?fixed-params=yes');$function");
    return true;

}
function ProtectMySite_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $enabled=intval($_POST["ProtectMySite"]);
    unset($_POST["ProtectMySite"]);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/protectmysite/enabled/$enabled"));
    if (!$json->Status) {
        echo $tpl->post_error($json->Error);
        return false;
    }
    foreach ($_POST as $key=>$value) {
        $valueEnc=urlencode($value);
        $keyEnc=urlencode($key);
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/protectmysite/$keyEnc/$valueEnc"));
        if (!$json->Status) {
            echo $tpl->post_error($json->Error);
            return false;
        }
    }
    return admin_tracks_post("Save NFQueue ProtectMySite parameters");

}
function parameters():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html[]="<table style='width:100%;margin-top:10px'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top' colspan='2'>";
    $html[]="<div id='cyberipcrime-status'></div>";
    $html[]="</td>";
    $html[]="</tr>";

    $html[]="<tr>";
    $html[]="<td style='width:500px;vertical-align:top;'>";
    $html[]="<div id='articapfilter-status'></div>";
    $html[]="</td>";
    $html[]="<td style='width:99%;padding-left:15px;vertical-align:top;'>";
    $html[]="<div id='ipfeeds-fixed-params'></div>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $jsRefresh=$tpl->RefreshInterval_js("articapfilter-status",$page,"left-status=yes");
    $jsRefresh2=$tpl->RefreshInterval_js("cyberipcrime-status",$page,"top-status=yes");

    $html[]=$jsRefresh2;
    $html[]=$jsRefresh;
    $html[]="LoadAjax('ipfeeds-fixed-params','$page?fixed-params=yes');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function Honeypot_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/status"));
    if (!$json->Status) {
        echo $tpl->div_error($json->Error);
        return false;
    }
    if (!property_exists($json, "Config")) {
        echo $tpl->div_error("Bad protocol");
        return false;
    }
    $Config = $json->Config;
    $ll=array();
    foreach ($Config->HoneyPotPorts as $LocalPort) {
        $ll[] = $LocalPort;
    }
    $form[]=$tpl->field_text("Honeypot","Honeypot: {ports}",@implode(", ", $ll));
    echo $tpl->form_outside("",$form,"{HoneyPotPorts_explain}","{apply}","dialogInstance1.close();LoadAjax('ipfeeds-fixed-params','$page?fixed-params=yes');");
    return true;
}
function main_params_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/status"));
    if (!$json->Status) {
        echo $tpl->div_error($json->Error);
        return false;
    }
    if (!property_exists($json, "Config")) {
        echo $tpl->div_error("Bad protocol");
        return false;
    }
    $Config = $json->Config;
    $ll=array();
    foreach ($Config->LocalPorts as $LocalPort) {
        $ll[] = $LocalPort;
    }


    $form[]=$tpl->field_hidden("Params","yes");
    $form[]=$tpl->field_text("ports","{monitoring}: {ports}",@implode(", ", $ll));
    $form[]=$tpl->field_checkbox("WhiteInternalNets","{allow}: {internal_networks}",$Config->WhiteInternalNets);
    $form[]=$tpl->field_numeric("BlockTime","{remediation} ({minutes})",$Config->BlockTime);
    $form[]=$tpl->field_numeric("PositiveTimeSec","{POSITIVE_CACHE_TTL}",$Config->PositiveTimeSec);
    echo $tpl->form_outside("",$form,"","{apply}","dialogInstance1.close();LoadAjax('ipfeeds-fixed-params','$page?fixed-params=yes');");
    return true;
}
function main_params_save():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    foreach ($_POST as $key => $value) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/param/set/$key/".urlencode($value)));
        if (!$json->Status) {
            echo $tpl->post_error($json->Error);
            return false;
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/reload");
    return admin_tracks_post("Save Cybercrime IP Feeds main parameters");
}
function fixed_params():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableArticaNFQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue"));

    if($EnableArticaNFQueue==1) {
        $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/status"));
        if (!$json->Status) {
            echo $tpl->div_error($json->Error);
            return false;
        }
        if (!property_exists($json, "Config")) {
            echo $tpl->div_error("Bad protocol");
            return false;
        }

        $Config = $json->Config;

        $ll=array();
        foreach ($Config->LocalPorts as $LocalPort) {
            $ll[] = $LocalPort;
        }
        $ls=array();
        if (is_array($Config->HoneyPotPorts) || $Config->HoneyPotPorts instanceof Traversable) {
            foreach ($Config->HoneyPotPorts as $LocalPort) {
                $ls[] = $LocalPort;
            }
        }
        $tpl->table_form_field_js("Loadjs('$page?ProtectMySite=yes')","AsFirewallManager");
        if(property_exists($Config, "ProtectMySite")) {
            if($Config->ProtectMySite->enabled==0) {
                $tpl->table_form_field_bool("ProtectMySite", 0, ico_shield);
            }else{
                $ProtectMySite=$Config->ProtectMySite;
                $ff=array();
                $CodesEnabled=$Config->ProtectMySite->codes_enabled;
                foreach ($ProtectMySite->codes as $code=>$category) {
                    if($CodesEnabled->$code){
                        $ff[]=$category;
                    }
                }
                if(count($ff)==0){
                    $tpl->table_form_field_bool("ProtectMySite", 0, ico_shield);
                }else{
                    $tpl->table_form_field_text("ProtectMySite", "<small>".@implode(", ",$ff), ico_shield);
                }

            }
        }

        $tpl->table_form_field_js("Loadjs('$page?main-params=yes')","AsFirewallManager");
        if(count($ll)==0){
            $tpl->table_form_field_bool("{monitoring}: {ports}", 0, ico_nic);
        }else {
            $tpl->table_form_field_text("{monitoring}: {ports}", @implode(",", $ll), ico_nic);
        }
        $tpl->table_form_field_js("Loadjs('$page?Honeypot-js=yes')","AsFirewallManager");
        if(count($ls)==0){
            $tpl->table_form_field_bool("Honeypot: {ports}", 0, ico_nic);
        }else {
            $tpl->table_form_field_text("Honeypot: {ports}", @implode(",", $ls), ico_nic);
        }
        $tpl->table_form_field_js("Loadjs('$page?main-params=yes')","AsFirewallManager");
        $tpl->table_form_field_bool("{allow}: {internal_networks}",$Config->WhiteInternalNets,ico_networks);

        $tpl->table_form_field_text("{remediation}", "{ban_ips} {then_block_during} $Config->BlockTime {minutes}", ico_shield);

        $tpl->table_form_field_text("{POSITIVE_CACHE_TTL}", "$Config->PositiveTimeSec {seconds}", ico_timeout);

        $tpl->table_form_field_js("","AsFirewallManager");
        $CrowdSec=$Config->CrowdSec;
        $tpl->table_form_field_bool("{APP_CROWDSEC}", $CrowdSec->enabled, ico_shield);
        if($CrowdSec->enabled==1){
            $tpl->table_form_field_text("CrowdSec {url}",$CrowdSec->CrowdsecAPI,ico_link);
        }



        $html[] = $tpl->_ENGINE_parse_body($tpl->table_form_compile());
    }
    if($EnableArticaNFQueue==0){
        $jsInstall=$tpl->framework_buildjs(
            "/nfqueue/install",
            "nfqueue.progress",
            "nfqueue.progress.log",
            "progress-CybercrimeIPFeeds-restart"
        );
        $topbuttons[] = array($jsInstall, ico_cd, "{install_feature}");

    }else {
        $jsUnInstall=$tpl->framework_buildjs(
            "/nfqueue/uninstall",
            "nfqueue.progress",
            "nfqueue.progress.log",
            "progress-CybercrimeIPFeeds-restart"
        );

        $topbuttons[] = array("Loadjs('$page?banip=yes')", ico_plus, "{ban_ips}");
        $topbuttons[] = array("Loadjs('$page?checkip=yes')", ico_loupe, "{check_ip_reputation}");
        $topbuttons[] = array($jsUnInstall, ico_trash, "{remove_this_section}");
    }

   // $topbuttons[] = array($jsrestart, ico_save, "{apply_parameters}");

    $TINY_ARRAY["TITLE"]="{CybercrimeIPFeeds}";
    $TINY_ARRAY["ICO"]="fas fa-hockey-mask";
    $TINY_ARRAY["EXPL"]="{CybercrimeIPFeeds_explain}";
    $TINY_ARRAY["URL"]="Cybercrime-IP-Feeds";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="<script>$jstiny</script>";

    echo @implode("\n", $html);


    return true;

}
function category_refresh():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $category=urlencode($_GET["category-refresh"]);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/timestamp/0"));
    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    echo "$function();\n";

    return admin_tracks("Re-download Cybercrime IP Feeds source {$_GET["category-refresh"]}");

}
function category_enable():bool{
    $category=$_GET["category-enable"];

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/sources"));
    $tpl=new template_admin();
    if(!$json->Status){
        echo $tpl->js_error($json->Error);
        return false;
    }
    if(!property_exists($json,"Sources")){
        echo $tpl->js_error("Protocol error");
        return false;
    }

    $Sources=$json->Sources;
    foreach($Sources as $Source){
        $categoryname=$Source->categoryname;
        if(strtolower($categoryname)<>strtolower($category)) {
            continue;
        }
        VERBOSE("FOUND SOURCE $categoryname",__LINE__);
        $enabled=$Source->enabled;
        if($enabled==1){
            $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/enabled/0"));
            if(!$json->Status){
                echo $tpl->js_error($json->Error);
                return false;
            }
            return admin_tracks("Disable Cybercrime IP Feeds source $category");
        }


        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/enabled/1"));
        if(!$json->Status){
            echo $tpl->js_error($json->Error);
            return false;
        }
        return admin_tracks("Enable Cybercrime IP Feeds source $category");
    }


    return  $tpl->js_error("No source found");


}
function category_delete_ask():bool{
    $tpl=new template_admin();
    $category=$_GET["category-delete"];
    $md=$_GET["md"];
    return $tpl->js_confirm_delete($category,"category-delete",$category,"$('#$md').remove()");
}
function category_delete_confirm():bool{
    $category=$_POST["category-delete"];
    $categoryEnc=urlencode($category);
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/del/$categoryEnc"));
    if(!$json->Status){
        echo $json->Error;
        return false;
    }
    return admin_tracks("Remove Cybercrime IP Feeds source $category");
}
function category_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $category=$_GET["category"];
    $function=$_GET["function"];
    if(strlen($category)>0){
        $title="{category}: $category";
    }else{
        $title="{new_category}";
    }
    $CategoryEnc=urlencode($category);
    return $tpl->js_dialog1($title, "$page?category-popup=$CategoryEnc&function=$function");
}
function AbuseIPDB_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    $category=$_GET["AbuseIPDB-js"];
    return $tpl->js_dialog1("$category", "$page?AbuseIPDB-popup=$category&function=$function");
}
function Honeypot_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("Honeypot", "$page?Honeypot-popup=yes");
}

function AbuseIPDB_popup():bool{
    $tpl=new template_admin();
    $function=$_GET["function"];
    $category=$_GET["AbuseIPDB-popup"];
    $btn="{apply}";
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/sources"));
    foreach ($json->Sources as $ligne){
        if($category==$ligne->categoryname){
            $JsonAbuseIPDB=$ligne;
        }
    }
    $form[]=$tpl->field_hidden("template","github1");
    $form[] = $tpl->field_hidden("category-extended", $category);
    $form[] = $tpl->field_text("apikey","{API_KEY}",$JsonAbuseIPDB->abuseipdb->apikey);
    $form[] = $tpl->field_text("confidenceMinimum","{confidence_minimum}",$JsonAbuseIPDB->abuseipdb->confidenceMinimum);
    $form[] = $tpl->field_checkbox("enabled", "{enable}",$JsonAbuseIPDB->enabled);
    echo $tpl->form_outside("",$form,$JsonAbuseIPDB->description,$btn,"dialogInstance1.close();$function()","AsFirewallManager");
    return true;
}
function category_popup():bool{
    $tpl=new template_admin();
    $category=$_GET["category-popup"];
    $function=$_GET["function"];
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/sources"));
    $enabled=1;
    $rbl_source=0;
    $rbl_answer=array();
    $btn="{add}";
    $doh_domain="";
    $url="";
    if(strlen($category)>0){
        $btn="{apply}";
        if(!$json->Status){
            echo $tpl->div_error($json->Error);
            return false;
        }
        if(!property_exists($json,"Sources")){
            echo $tpl->div_error("Protocol error");
            return false;
        }

        $Sources=$json->Sources;
        foreach($Sources as $Source){
            $categoryname=$Source->categoryname;
            if(strtolower($categoryname)<>strtolower($category)) {
                continue;
            }
            VERBOSE("FOUND SOURCE $categoryname",__LINE__);

             $doh_domain=$Source->doh_domain;
             $enabled=$Source->enabled;
             $url=$Source->url;
             $description=$Source->description;
             $rbl_source=$Source->rbl_source;
             $doh_source=$Source->doh_source;
             if($doh_source==1){
                 $rbl_source=0;
             }
            foreach ($Source->rbl_answer as $ip => $desc) {
                $rbl_answer[] = "$ip $desc";
            }

             break;
        }
    }
    $form[]=$tpl->field_hidden("template","github1");
    if(strlen($category)>0) {
        $form[] = $tpl->field_hidden("category", $category);
    }else {
        $form[] = $tpl->field_text("category", "{name}", "", true);
    }
    $form[] = $tpl->field_text("description", "{description}",$description,true);
    $form[] = $tpl->field_checkbox("enabled", "{enable}",$enabled);
    $form[] = $tpl->field_checkbox("rbl", "{DNSBL}",$rbl_source);
    $form[] = $tpl->field_checkbox("doh", "{DNSBL} {APP_DOH_SERVER}",$doh_source);
    $form[] = $tpl->field_text("doh_domain", "<strong>DoH</strong>-{domain}",$doh_domain,false);
    $form[] = $tpl->field_text("url", "{url}",$url,true);
    $form[] = $tpl->field_textareacode("rblanswsers","{rbl_answers}",@implode("\n",$rbl_answer));
    echo $tpl->form_outside("",$form,"",$btn,"dialogInstance1.close();$function()","AsFirewallManager");
    return true;
}
function smartCapitalize($string) {
    if(function_exists("ucwords")){
        $tb=explode(" ",$string);
        $tz=array();
        foreach ($tb as $t) {
            $t=ucwords(strtolower($t));
            $t= preg_replace_callback('/\b(\w)/u', function ($matches) {
                return mb_strtoupper($matches[1], 'UTF-8');
            }, mb_strtolower($t, 'UTF-8'));

            $tz[]=$t;
        }

        return @implode(" ",$tz);
    }

    return preg_replace_callback('/\b(\w)/u', function ($matches) {
        return mb_strtoupper($matches[1], 'UTF-8');
    }, mb_strtolower($string, 'UTF-8'));
}
function category_extended_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $category=urlencode($_POST["category-extended"]);
    if(isset($_POST["confidenceMinimum"])){
        $confidenceMinimum=$_POST["confidenceMinimum"];
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/confidenceminimum/$confidenceMinimum"));
        if(!$json->Status){
            echo $tpl->post_error($json->Error);
            return false;
        }
    }
    if(isset($_POST["enabled"])){
        $enabled=intval($_POST["enabled"]);
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/enabled/$enabled"));
        if(!$json->Status){
            echo $tpl->post_error($json->Error);
            return false;
        }
    }
    if(isset($_POST["apikey"])){
        $apikey=$_POST["apikey"];
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/apikey/$apikey"));
        if(!$json->Status){
            echo $tpl->post_error($json->Error);
            return false;
        }
    }
    return admin_tracks("Updated Cybercrime IP Feeds source extended settings for {$_POST["category-extended"]}");
}

function Honeypot_save(){
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $value=trim($_POST["Honeypot"]);
    if($value==""){
        $value="NONE";
    }
    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/param/set/honeypot/".urlencode($value)));
    if (!$json->Status) {
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks("Save Cyber Crime IP feed honeypot to {$_POST["Honeypot"]}");
}
function category_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $category=urlencode($_POST["category"]);
    $rbl=intval($_POST["rbl"]);
    $doh=intval($_POST["doh"]);
    $tb=explode("\n",$_POST["rblanswsers"]);

    if($doh==1){
        $rbl=0;
    }
    $dnsblanswers="NONE";
    $zbls=array();
    foreach ($tb as $line) {
        writelogs($line,__FUNCTION__,__FILE__,__LINE__);
        if(preg_match("#([0-9]+).*?=.*?\"(.+?)\"#",$line,$matches)) {
            $matches[2]=smartCapitalize( $matches[2]);
            $matches[2]=str_replace("/","_",$matches[2]);
            $matches[2]=str_replace(" ","",$matches[2]);
            $matches[2]=str_replace("(","_",$matches[2]);
            $matches[2]=str_replace(")","",$matches[2]);
            $line="127.0.0.$matches[1] $matches[2]";
        }
        writelogs($line,__FUNCTION__,__FILE__,__LINE__);
        if(!preg_match("#^([0-9\.]+)\s+(.+)#",$line,$matches)) {
            continue;
        }
        $matches[2]=smartCapitalize( $matches[2]);
        $matches[2]=str_replace("(","_",$matches[2]);
        $matches[2]=str_replace(")","",$matches[2]);
        $matches[2]=str_replace("/","_",$matches[2]);
        $matches[2]=str_replace(" ","",$matches[2]);
        $zbls[]=$matches[1]."=".$matches[2];
    }
    if(count($zbls)>0) {
        $dnsblanswers = urlencode(implode(",", $zbls));
    }
    foreach ($_POST as $key => $value) {
        $value=trim($value);
        if(strlen($value)==0){
            $_POST[$key]="NONE";
        }
    }
    $url=urlencode($_POST["url"]);
    $doh_domain=$_POST["doh_domain"];
    $description=urlencode($_POST["description"]);
    writelogs("dnsblanswers=$dnsblanswers",__FUNCTION__,__FILE__,__LINE__);

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/enabled/{$_POST["enabled"]}"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/url/$url"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/template/{$_POST["template"]}"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/description/$description"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/dnsbl/$rbl"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/doh/$doh"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/dohdomain/$doh_domain"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }


    if(is_null($dnsblanswers)){
        $dnsblanswers="NONE";
    }
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/dnsblanswers/$dnsblanswers"));
    if(!$json->Status){
        echo $tpl->post_error($json->Error);
        return false;
    }
    return admin_tracks_post("Updated Cybercrime IP Feeds source $category");
}
function articapcap_reload_js():string{
    $page=CurrentPageName();
    return "Loadjs('$page?articapcap-reload=yes');";
}
function articapcap_reload($usejs=true):bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ArticaPSnifferBindPattern=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferBindPattern"));
    $MAIN_URI="http://$ArticaPSnifferBindPattern/service/reload";
    $CURLOPT_HTTPHEADER[]="Pragma: no-cache,must-revalidate";
    $CURLOPT_HTTPHEADER[]="Cache-Control: no-cache,must revalidate";
    $CURLOPT_HTTPHEADER[]="Expect:";
    $ch = curl_init($MAIN_URI);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
    curl_setopt($ch, CURLOPT_INTERFACE, "127.0.0.1");
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_POST,0);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $response = curl_exec($ch);
    $errno=curl_errno($ch);

    if($errno>0){
        $text=curl_error($ch);
        echo $tpl->js_error("{error} $errno $text");
        return false;
    }

    $json = json_decode($response);

    if(!property_exists($json,"content")){
        if($usejs) {
            echo $tpl->js_error("{error} $response");
        }
        return false;
    }


    if(!$usejs){
        $CrowdSecCyberCrimeIPfeed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrowdSecCyberCrimeIPfeed"));
        if($CrowdSecCyberCrimeIPfeed==1){
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("crowdsec.php?restart-custom-bouncer=yes");
        }
        return true;
    }

    header("content-type: application/x-javascript");
    $html[]="LoadAjax('cyberipcrime-status','$page?top-status=yes');";
    $html[]="LoadAjax('articapfilter-status','$page?left-status=yes');";

    $CrowdSecCyberCrimeIPfeed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrowdSecCyberCrimeIPfeed"));
    if($CrowdSecCyberCrimeIPfeed==1){
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork("crowdsec.php?restart-custom-bouncer=yes");
    }


    echo @implode("\n",$html);

    return true;

}
function articapcap_stats_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ArticaPSnifferBindPattern=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferBindPattern"));
    $MAIN_URI="http://$ArticaPSnifferBindPattern/";
    $CURLOPT_HTTPHEADER[]="Pragma: no-cache,must-revalidate";
    $CURLOPT_HTTPHEADER[]="Cache-Control: no-cache,must revalidate";
    $CURLOPT_HTTPHEADER[]="Expect:";
    $ch = curl_init($MAIN_URI);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
    curl_setopt($ch, CURLOPT_INTERFACE, "127.0.0.1");
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_POST,0);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $response = curl_exec($ch);
    $errno=curl_errno($ch);

    if($errno>0){
        $text=curl_error($ch);
        echo $tpl->div_error("{error} $errno $text");
        return false;
    }
    $json = json_decode($response);

    if(!property_exists($json,"content")){
        echo $tpl->div_error("$response");
        return false;
    }
    $tpl->table_form_field_text("{memory_usage} {$json->content->MemoryUsage}",FormatBytes($json->content->MemoryUsage/1024),ico_mem);
    $tpl->table_form_field_text("{records} ({cache_mem})",$tpl->FormatNumber($json->content->MemoryCacheRecords),ico_mem);
    $tpl->table_form_field_text("{cache_mem} ({size})",FormatBytes($json->content->MemorCacheSize/1024),ico_mem);

    $tpl->table_form_field_text("{records} ({memory_database})",$tpl->FormatNumber($json->content->DatabaseRecords),ico_mem);
    $tpl->table_form_field_text("{memory_database} ({size})",FormatBytes($json->content->DatabaseSize/1024),ico_mem);

    $tpl->table_form_field_text("{last_requests} (DNS)",$tpl->FormatNumber($json->content->DNSRequests),ico_database);
    $tpl->table_form_field_text("{KsrnQueryIPAddr}",$tpl->FormatNumber($json->content->ScannedIPs),ico_database);


    echo $tpl->table_form_compile();


    return true;
}
function events_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,null,null,null,"&events-searcher=yes");
    echo "</div>";
    return true;
}
function events_searcher():bool{
    $tpl=new template_admin();
    $MAIN=$tpl->format_search_protocol($_GET["search"]);


    $rp=intval($MAIN["MAX"]);
    $search=trim($MAIN["TERM"]);
    if(strlen($search)<3){$search="NONE";}

    $data=$GLOBALS["CLASS_SOCKETS"]->REST_API("/nfqueue/events/$rp/$search");

    $json=json_decode($data);
    if (json_last_error()> JSON_ERROR_NONE) {
        echo $tpl->div_error("{error}<hr>".json_last_error_msg());
    }
    if(!$json->Status){
        echo $tpl->div_error("{error}<br>Framework return false!<hr>$json->Error");
    }

    $tooltips["paused"]="<label class='label label-warning'>{paused}</label>";
    $tooltips["exited"]="<label class='label label-danger'>{stopped}</label>";
    $tooltips["warn"]="<label class='label label-warning'>{warn}</label>";
    $tooltips["error"]="<label class='label label-danger'>{error}</label>";

    $text["error"]="text-danger";
    $text["warn"]="text-warning font-bold";

    $html[]="
<table class=\"table table-hover\">
	<thead>
    	<tr>
        	<th>{date}</th>
        	<th>{level}</th>
        	<th>{events}</th>
        </tr>
  	</thead>
	<tbody>
";

    foreach ($json->Logs as $line){
        $textclass=null;
        $json=json_decode($line);
        if (json_last_error()> JSON_ERROR_NONE) {
            continue;
        }

        if(!property_exists($json,"level")){continue;}

        $level=$json->level;
        $FTime=$tpl->time_to_date($json->time,true);
        $level_label="<label class='label label-default'>$level</label>";
        $message=$json->message;
       if(isset($tooltips[$level])){
           $level_label=$tooltips[$level];
       }
       if(isset($text[$level])){
           $textclass=$text[$level];
       }

        $html[]="<tr>
				<td style='width:1%;' nowrap class='$textclass'>$FTime</td>
				<td style='width:1%;' nowrap class='$textclass'>$level_label</td>
    			<td class='$textclass'>$message</td>
				</tr>";

    }
    $html[]="</tbody></table>";
    echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
    return true;

}
function widget_nfqueue_database($json):string{
    $tpl=new template_admin();
    if(!$json->Status){
        return $tpl->widget_h("minheight:169px:grey",ico_database,"{error}","{database}<br><small>$json->Error</small>","");
    }

    $Entries=$json->MainStorage;
    $size=FormatBytes($json->MainStorageBytes/1024);
    if($Entries==0){
        return $tpl->widget_h("minheight:169px:grey",ico_database,"{empty}","{database}","");
    }
    $Entries=$tpl->FormatNumber($Entries);
    return $tpl->widget_h("minheight:169px:green",ico_database,"$Entries {records}
    <div style='margin-top:-1px;font-size:12px'>{memory}: $size</div>
    ","{database}","");
}
function widget_nfqueue_status($json):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $EnableArticaNFQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue"));

    if($EnableArticaNFQueue==0){
        $jsbut = "Loadjs('$page?cyberipcrime-enable=yes')";
        $button["name"] = "{enable}";
        $button["js"] = $jsbut;
        return $tpl->widget_h("minheight:169px:gray",ico_firewall,"{disabled}","{CybercrimeIPFeeds}",$button);
    }
    $jsbut_uninstall = "Loadjs('$page?cyberipcrime-disable=yes')";
    $button["name"] = "{disable}";
    $button["js"] = $jsbut_uninstall;

    if(!$json->Status){
        return $tpl->widget_h("minheight:169px:red",ico_firewall,"{error}","{CybercrimeIPFeeds}<br><small>$json->Error</small>",$button);
    }

    if(!$json->Status){
        return $tpl->widget_h("minheight:169px:red",ico_firewall,"{error}","{CybercrimeIPFeeds}<br><small>$json->Error</small>",$button);
    }

    $Size=FormatBytes($json->Size/1024);
    $Packets=$tpl->FormatNumber($json->Packets);
    return $tpl->widget_h("minheight:169px:green",ico_firewall,"$Size<div style='margin-top: -15px'><small style='color:white;font-size:12px'>{packets}: $Packets</small></div>","{CybercrimeIPFeeds}",$button);

}
function widget_nfqueue_blocks($json):string{

    $tpl=new template_admin();
    if(!$json->Status){
        return $tpl->widget_h("minheight:169px:red",ico_shield,"{error}","{denied_ip_sources}<br><small>$json->Error</small>","");
    }

    if(!property_exists($json,"IPsets")){
        return $tpl->widget_h("minheight:169px:grey",ico_shield,0,"{denied_ip_sources}","");
    }
    $numentries=0;
    $WiteEntries=0;
    foreach ($json->IPsets as $ipsetName => $ipset) {
        if($ipsetName=="nfqueuewhite"){
            $WiteEntries=$ipset->header->numentries;
        }
       if($ipsetName<>"nfqueueblock"){
           continue;
       }
       $numentries=$ipset->header->numentries;
    }
    $WiteEntries=$tpl->FormatNumber($WiteEntries);
    if($numentries==0){
        return $tpl->widget_h("minheight:169px:grey",ico_shield,"0/$WiteEntries","{denied_ip_sources}/{allow}","");
    }
    $Count=$tpl->FormatNumber($numentries);
    return $tpl->widget_h("minheight:169px:yellow",ico_shield,"$Count/$WiteEntries","{denied_ip_sources}/{allow}","");
}
function left_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();


    $html[]="<div id='pcap-here-$t'>";
    $html[]=left_status_pcap();
    $html[]="</div>";
    $html[]="<script>";
    $html[]="Loadjs('$page?pcap-refresh=$t');";
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function left_status_pcap():string{
    $tpl=new template_admin();

    $jsRestart=$tpl->framework_buildjs(
        "/nfqueue/restart",
        "nfqueue.progress",
        "nfqueue.progress.log",
        "progress-CybercrimeIPFeeds-restart"
    );

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/nfqueue/status"));

    if (json_last_error()> JSON_ERROR_NONE) {
        return $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR",json_last_error_msg()));
    }else {
        if (!$json->Status) {
            return $tpl->_ENGINE_parse_body($tpl->widget_rouge("API ERROR", $json->Error));

        } else {
            $ini=new Bs_IniHandler();
            $ini->loadString($json->Info);
            return $tpl->_ENGINE_parse_body($tpl->SERVICE_STATUS($ini, "APP_NFQUEUE",$jsRestart));
        }
    }


}
function left_status_refresh():bool{
    $page=CurrentPageName();
    $t=$_GET["pcap-refresh"];
    header("content-type: application/x-javascript");
    $f[]="function RefreshPCAPS$t(){";
    $f[]="\tif(!document.getElementById('pcap-here-$t') ){ return;}";
    $f[]="\tLoadAjaxSilent('pcap-here-$t','$page?left-status-pcap=yes');";
    $f[]="\tLoadjs('$page?pcap-refresh=$t');";
    $f[]="}";
    $f[]="function RefreshPCAP$t(){";
    $f[]="\tif(!document.getElementById('pcap-here-$t') ){";
    $f[]="\t\treturn;";
    $f[]="\t}";
    $f[]="\tsetTimeout(\"RefreshPCAPS$t()\",2500);";
    $f[]="}";
    $f[]="RefreshPCAP$t();";
    echo @implode("\n",$f);
    return true;
}
function params_ipfeed_pcap_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{use_databases_in_firewall}","$page?ipfeed-articapcap-popup=yes");
}
function params_api_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("HTTP API","$page?ipfeed-articapcap-api-popup=yes");
}
function params_proxmox_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{remediation}: ProxMox","$page?ipfeed-proxmox-popup=yes");
}
function params_iptables_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{remediation}: {your_firewall}","$page?ipfeed-iptables-popup=yes");

}
function params_fortigate_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{remediation}: {fortigate}","$page?ipfeed-fortigate-popup=yes");
}
function params_pfsense_js():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{remediation}: pfSense","$page?ipfeed-pfsense-popup=yes");
}
function articapcap_stats():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    return $tpl->js_dialog2("{statistics}","$page?articapcap-stats-popup=yes");
}
function params_iptables_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ArticaPSnifferIpSetEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferIpSetEnabled"));
    $ArticaPSnifferIpSetTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferIpSetTimeout"));
    if($ArticaPSnifferIpSetTimeout==0){$ArticaPSnifferIpSetTimeout=30;}

    $form[]=$tpl->field_checkbox("ArticaPSnifferIpSetEnabled","{enable}",$ArticaPSnifferIpSetEnabled,true);
    $form[]=$tpl->field_numeric("ArticaPSnifferIpSetTimeout","{ban_ips} {then_block_during} ({minutes})",$ArticaPSnifferIpSetTimeout);

    $jsrestart=articapcap_reload_js();

    $html=$tpl->form_outside(null,$form,null,"{apply}",
        "$jsrestart;dialogInstance2.close();","AsFirewallManager"
    );

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function params_fortigate_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ArticaPSnifferFortigateAPI=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferFortigateAPI"));
    $ArticaPSnifferFortigateIPset=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferFortigateIPset"));
    $ArticaPSnifferFortigateAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferFortigateAddr"));
    $ArticaPSnifferFortigateTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferFortigateTTL"));
    if($ArticaPSnifferFortigateTTL==0){$ArticaPSnifferFortigateTTL=60;}
    $ArticaPSnifferFortigateVdom=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferFortigateVdom"));
    $ArticaPSnifferFortigateSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferFortigateSchedule"));
    $ArticaPSnifferFortigateUser=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferFortigateUser"));
    if($ArticaPSnifferFortigateVdom==null){$ArticaPSnifferFortigateVdom="root";}
    if($ArticaPSnifferFortigateSchedule==0){$ArticaPSnifferFortigateSchedule=15;}

    $form[]=$tpl->field_checkbox("ArticaPSnifferFortigateAPI","{enable}",$ArticaPSnifferFortigateAPI,true);
    $form[]=$tpl->field_text("ArticaPSnifferFortigateAddr","{remote_host} ({ipaddr}:{port})",$ArticaPSnifferFortigateAddr,true);
    $form[]=$tpl->field_text("ArticaPSnifferFortigateVdom","vdom",$ArticaPSnifferFortigateVdom,true);
    $form[]=$tpl->field_text("ArticaPSnifferProxMoxuser","{API_KEY}",$ArticaPSnifferFortigateUser);
    $form[]=$tpl->field_text("ArticaPSnifferFortigateIPset","{firewall_group}",$ArticaPSnifferFortigateIPset,true);
    $form[]=$tpl->field_numeric("ArticaPSnifferFortigateTTL","{ban_ips} {then_block_during} ({minutes})",$ArticaPSnifferFortigateTTL);
    $form[]=$tpl->field_numeric("ArticaPSnifferFortigateSchedule","{schedule} ({minutes})",$ArticaPSnifferFortigateSchedule);

    $jsrestart=articapcap_reload_js();


    $html=$tpl->form_outside(null,$form,null,"{apply}",
        "$jsrestart;dialogInstance2.close();","AsFirewallManager"
    );

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function params_pfsense_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ArticaPSnifferPfSenseEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferPfSenseEnable"));
    $ArticaPSnifferPfSenseAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferPfSenseAddr"));
    $ArticaPSnifferPfSenseAPIKEY=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferPfSenseAPIKEY"));
    $ArticaPSnifferFortigateTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferFortigateTTL"));
    if($ArticaPSnifferFortigateTTL==0){$ArticaPSnifferFortigateTTL=60;}
    $ArticaPSnifferPfSenseID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferPfSenseID"));
    $ArticaPSnifferPfSenseTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferPfSenseTTL"));
    $ArticaPSnifferPfSenseIPset=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferPfSenseIPset"));

    if($ArticaPSnifferPfSenseTTL==0){$ArticaPSnifferPfSenseTTL=30;}

    $form[]=$tpl->field_checkbox("ArticaPSnifferPfSenseEnable","{enable}",$ArticaPSnifferPfSenseEnable,true);
    $form[]=$tpl->field_text("ArticaPSnifferPfSenseAddr","{remote_host}",$ArticaPSnifferPfSenseAddr,true);
    $form[]=$tpl->field_text("ArticaPSnifferPfSenseID","Client ID",$ArticaPSnifferPfSenseID,true);
    $form[]=$tpl->field_text("ArticaPSnifferPfSenseAPIKEY","{API_KEY}",$ArticaPSnifferPfSenseAPIKEY);
    $form[]=$tpl->field_text("ArticaPSnifferPfSenseIPset","{firewall_group}",$ArticaPSnifferPfSenseIPset,true);
    $form[]=$tpl->field_numeric("ArticaPSnifferPfSenseTTL","{ban_ips} {then_block_during} ({minutes})",$ArticaPSnifferPfSenseTTL);

    $jsrestart=articapcap_reload_js();


    $html=$tpl->form_outside(null,$form,null,"{apply}",
        "$jsrestart;dialogInstance2.close();","AsFirewallManager"
    );

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function params_proxmox_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $ArticaPSnifferProxMoxEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferProxMoxEnabled"));
    $ArticaPSnifferProxMoxServer = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferProxMoxServer"));
    $ArticaPSnifferProxMoxuser = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferProxMoxuser"));
    $ArticaPSnifferProxMoxPass= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferProxMoxPass"));
    $ArticaPSnifferProxMoxIpSet= trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferProxMoxIpSet"));
    $ArticaPSnifferProxMoxSched = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferProxMoxSched"));
    $ArticaPSnifferProxMoxTTL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferProxMoxTTL"));


    $form[]=$tpl->field_checkbox("ArticaPSnifferProxMoxEnabled","{enable}",$ArticaPSnifferProxMoxEnabled,true);
    $form[]=$tpl->field_text("ArticaPSnifferProxMoxServer","{remote_host} ({ipaddr}:{port})",$ArticaPSnifferProxMoxServer,true);
    $form[]=$tpl->field_text("ArticaPSnifferProxMoxuser","{username}",$ArticaPSnifferProxMoxuser);
    $form[]=$tpl->field_password("ArticaPSnifferProxMoxPass","{password}",$ArticaPSnifferProxMoxPass);
    $form[]=$tpl->field_text("ArticaPSnifferProxMoxIpSet","IPSet",$ArticaPSnifferProxMoxIpSet);
    $form[]=$tpl->field_text("ArticaPSnifferProxMoxTTL","{records} TTL ({seconds})",$ArticaPSnifferProxMoxTTL);
    $form[]=$tpl->field_numeric("ArticaPSnifferProxMoxSched","{schedule} ({minutes})",$ArticaPSnifferProxMoxSched);

    $jsrestart=articapcap_reload_js();


    $html=$tpl->form_outside(null,$form,null,"{apply}",
        "$jsrestart;dialogInstance2.close();","AsFirewallManager"
    );

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function params_api_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ArticaPSnifferHTTPInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferHTTPInterface"));
    $ArticaPSnifferHTTPPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferHTTPPort"));
	$ArticaPSnifferHTTPApi = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferHTTPApi"));
	$ArticaPSnifferHTTPApiWhite = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaPSnifferHTTPApiWhite"));

    $form[]=$tpl->field_interfaces("ArticaPSnifferHTTPInterface","{listen_interface}",$ArticaPSnifferHTTPInterface);
    $form[]=$tpl->field_numeric("ArticaPSnifferHTTPPort","{listen_port}",$ArticaPSnifferHTTPPort);
    $form[]=$tpl->field_text("ArticaPSnifferHTTPApi","{API_KEY}",$ArticaPSnifferHTTPApi);
    $form[]=$tpl->field_text("ArticaPSnifferHTTPApiWhite","{deny_access_except}","127.0.0.1,$ArticaPSnifferHTTPApiWhite");


    $jsrestart=articapcap_reload_js();

    $html=$tpl->form_outside(null,$form,null,"{apply}",
        "$jsrestart;dialogInstance2.close();","AsFirewallManager"
    );

    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function params_ipfeed_pcap_popup():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $IPFeedsUseIPset=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IPFeedsUseIPset"));

    $form[]=$tpl->field_checkbox("IPFeedsUseIPset","{use_databases_in_firewall}",$IPFeedsUseIPset);


    $jsrestart=articapcap_reload_js();


    $html=$tpl->form_outside(null,$form,"{use_databases_in_firewall_explain}","{apply}",
    "$jsrestart;dialogInstance2.close();","AsFirewallManager"
    );

    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function top_status():bool{
    $tpl=new template_admin();
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/status"));

    $EnableArticaNFQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue"));
    if($EnableArticaNFQueue==0){
        return false;
    }
    $widget_nfqueue_status=widget_nfqueue_status($json);
    $widget_nfqueue_database=widget_nfqueue_database($json);
    $widget_nfqueue_blocks=widget_nfqueue_blocks($json);

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$widget_nfqueue_status</td>";
    $html[]="<td style='width:33%;padding-left: 10px'>$widget_nfqueue_blocks</td>";
    $html[]="<td style='width:33%;padding-left: 10px'>$widget_nfqueue_database</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function CybercrimeIPFeeds_enable():bool{

    $tpl=new template_admin();

    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        return $tpl->js_error("{ERROR_NO_LICENSE}");
    }
    $jsInstall=$tpl->framework_buildjs(
        "/nfqueue/install",
        "nfqueue.progress",
        "nfqueue.progress.log",
        "progress-CybercrimeIPFeeds-restart"
    );
    header("content-type: application/x-javascript");
    echo $jsInstall;

    return admin_tracks("Enable CybercrimeIPFeeds feature");

}
function CybercrimeIPFeeds_disable():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

   return $tpl->js_confirm_delete("{CybercrimeIPFeeds}","cyberipcrime-disable","yes","LoadAjax('cyberipcrime-status','$page?top-status=yes');");

}
function CybercrimeIPFeeds_disable_confirm():bool{
    $tpl=new template_admin();
    $jsInstall=$tpl->framework_buildjs(
        "/nfqueue/uninstall",
        "nfqueue.progress",
        "nfqueue.progress.log",
        "progress-CybercrimeIPFeeds-restart"
    );
    header("content-type: application/x-javascript");
    echo $jsInstall;

    return admin_tracks("Remove CybercrimeIPFeeds feature");
}
function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

    $q=new lib_sqlite("/home/artica/SQLITE/firewall.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM iptables_main WHERE MOD='IPFEED'");
    $ID=intval($ligne["ID"]);
    $FADD_FIELDS[]="`rulename`";
    $FADD_FIELDS[]="`service`";
    $FADD_FIELDS[]="`accepttype`";
    $FADD_FIELDS[]="`enabled`";
    $FADD_FIELDS[]="`eth`";
    $FADD_FIELDS[]="`zOrder`";
    $FADD_FIELDS[]="`jlog`";
    $FADD_FIELDS[]="`application`";
    $FADD_FIELDS[]="`isClient`";
    $FADD_FIELDS[]="`MOD`";
    $FADD_FIELDS[]="`proto`";
    $FADD_FIELDS[]="`destport_group`";
    $FADD_FIELDS[]="`dest_group`";
    $FADD_FIELDS[]="`source_group`";
    $FADD_FIELDS[]="`MARK`";
    $FADD_FIELDS[]="`MARK_BALANCE`";
    $FADD_FIELDS[]="`ForwardTo`";
    $FADD_FIELDS[]="`ForwardNIC`";

    $EnableFireholIPSetsLogs=intval($_POST["EnableFireholIPSetsLogs"]);

    $FADD_VALS[]="{CybercrimeIPFeeds}<br><small>{CybercrimeIPFeeds_explain}</small>";
    $FADD_VALS[]=null;
    $FADD_VALS[]="DROP";
    $FADD_VALS[]=1;
    $FADD_VALS[]=null;
    $FADD_VALS[]=0;
    $FADD_VALS[]=$EnableFireholIPSetsLogs;
    $FADD_VALS[]=null;
    $FADD_VALS[]=0;
    $FADD_VALS[]="IPFEED";
    $FADD_VALS[]="tcp";
    $FADD_VALS[]="0";
    $FADD_VALS[]="0";
    $FADD_VALS[]="0";
    $FADD_VALS[]=0;
    $FADD_VALS[]=0;
    $FADD_VALS[]=null;
    $FADD_VALS[]=null;

    foreach ($FADD_VALS as $field){
        $ITEMSADD[]="'$field'";
    }

    if($_POST["EnableFireholIPSets"]==1){
        if($ID==0){
            $sql="INSERT INTO iptables_main ( ". @implode(",", $FADD_FIELDS).") VALUES (".@implode(",", $ITEMSADD).")";
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo $tpl->js_error(true);return;}

        }else{
            $sql="UPDATE iptables_main SET jlog=$EnableFireholIPSetsLogs WHERE MOD='IPFEED'";
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo $tpl->js_error(true);return;}
        }

    }else{
        if($ID>0){
            $results=$q->QUERY_SQL("SELECT ID FROM iptables_main WHERE MOD='IPFEED'");
            foreach ($results as $index=>$ligne) {
                $ID=$ligne["ID"];
                $iptables = new iptables();
                $iptables->delete_rule($ID);
            }
        }
    }


}
function blacklisted_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&blacklisted-search=yes");
    echo "</div>";
    return true;
}
function whitelists_start():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo "<div style='margin-top:10px'>";
    echo $tpl->search_block($page,"","","","&whitelists-search=yes");
    echo "</div>";
    return true;
}
function whitelists_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $TRCLASS=null;
    $html[]="<table id='table-openvpn-sites' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{ipaddr}</th>";
    $html[]="<th>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    if(isset($_GET["search"])){$_GET["search"]=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}
    $MAIN=$tpl->format_search_protocol($_GET["search"],false,false,false,true);

    $search=$MAIN["TERM"];
    $Max=intval($MAIN["MAX"]);
    if($Max==0){
        $Max=350;
    }
    if(is_null($search) OR strlen($search)==0){
        $search="nil";
    }

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/whitelisted/$search"));
    if (!$json->Status) {
        echo $tpl->div_error($json->Error);
        return false;
    }

    $LastBlocks=$json->Items;
    $icocomp=ico_computer;
    $c=0;
    foreach ($LastBlocks as $ipaddr=>$ligne) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $ipaddr=$ligne->elem;
        $c++;
        if($c>=$Max){
            break;
        }
        $ipaddrEnc=urlencode($ipaddr);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td><i class='$icocomp fa-2x'></i>&nbsp;&nbsp;<span style='font-size:24px'>$ipaddr</span></td>";
        $html[]="<td style='width:1%' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-white=$ipaddrEnc&md=$md')","AsVPNManager")."</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $function=$_GET["function"];
    $topbuttons[] = array("Loadjs('$page?white=yes&function=$function')", ico_plus, "{allow_an_IP_address}");
    $topbuttons[] = array("Loadjs('$page?checkip=yes')", ico_loupe, "{check_ip_reputation}");

    $TINY_ARRAY["TITLE"]="{CybercrimeIPFeeds}";
    $TINY_ARRAY["ICO"]="fas fa-hockey-mask";
    $TINY_ARRAY["EXPL"]="{CybercrimeIPFeeds_bans}";
    $TINY_ARRAY["URL"]="Cybercrime-IP-Feeds";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="</div>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$jstiny
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function blacklisted_search():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $TRCLASS=null;
    $html[]="<table id='table-openvpn-sites' class=\"table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th>{ipaddr}</th>";
    $html[]="<th>{expire_in}</th>";
    $html[]="<th>{delete}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";

    if(isset($_GET["search"])){$_GET["search"]=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}
    $MAIN=$tpl->format_search_protocol($_GET["search"],false,false,false,true);

    $search=$MAIN["TERM"];
    $Max=intval($MAIN["MAX"]);
    if($Max==0){
        $Max=350;
    }
    if(is_null($search) OR strlen($search)==0){
        $search="nil";
    }

    $json = json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/blocked/$search"));
    if (!$json->Status) {
        echo $tpl->div_error($json->Error);
        return false;
    }

    $LastBlocks=$json->Items;
    $icocomp=ico_computer;
    $c=0;
    foreach ($LastBlocks as $ipaddr=>$ligne) {
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5(serialize($ligne));
        $ipaddr=$ligne->elem;
        $timeout=$ligne->timeout;
        $NextTime=time()+$timeout;
        $Distance=distanceOfTimeInWords(time(),$NextTime);
        $c++;
        if($c>=$Max){
            break;
        }
        $ipaddrEnc=urlencode($ipaddr);
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td><i class='$icocomp fa-2x'></i>&nbsp;&nbsp;<span style='font-size:24px'>$ipaddr</span></td>";
        $html[]="<td style='width:1%' nowrap>$Distance</td>";
        $html[]="<td style='width:1%' nowrap>".$tpl->icon_delete("Loadjs('$page?delete-blocked=$ipaddrEnc&md=$md')","AsVPNManager")."</td>";
        $html[]="</tr>";
    }
    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $function=$_GET["function"];
    $topbuttons[] = array("Loadjs('$page?banip=yes&function=$function')", ico_plus, "{ban_ips}");
    $topbuttons[] = array("Loadjs('$page?checkip=yes')", ico_loupe, "{check_ip_reputation}");

    $TINY_ARRAY["TITLE"]="{CybercrimeIPFeeds}";
    $TINY_ARRAY["ICO"]="fas fa-hockey-mask";
    $TINY_ARRAY["EXPL"]="{CybercrimeIPFeeds_bans}";
    $TINY_ARRAY["URL"]="Cybercrime-IP-Feeds";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";


    $html[]="</div>
	<script>
	NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$jstiny
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}