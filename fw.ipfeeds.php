<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$users=new usersMenus();if(!$users->AsFirewallManager){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
include_once(dirname(__FILE__)."/ressources/class.firewall.api.inc");
if(isset($_GET["ProtectMySite"])){ProtectMySite_js();exit;}
if(isset($_GET["ProtectMySite-popup"])){ProtectMySite_popup();exit;}
if(isset($_POST["ProtectMySite"])){ProtectMySite_save();exit;}
if(isset($_POST["Honeypot"])){Honeypot_save();exit;}
if(isset($_GET["main-params"])){main_params_js();exit;}
if(isset($_GET["main-params-popup"])){main_params_popup();exit;}
if(isset($_POST["Params"])){main_params_save();exit;}
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
if(isset($_GET["ipfs-list"])){ipfs_list();exit;}



if(isset($_GET["nic-form-settings-build"])){nic_form_settings_build();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["articapcap-reload"])){articapcap_reload();exit;}
if(isset($_GET["blacklisted"])){blacklisted_start();exit;}
if(isset($_GET["whitelists"])){whitelists_start();exit;}
if(isset($_GET["ipfw-list"])){ipfw_list();exit;}
if(isset($_POST["ipfw-add"])){ipfw_add_save();exit;}
if(isset($_POST["ipfw-del"])){ipfw_del_save();exit;}
if(isset($_POST["ipfw-note-ip"])){ipfw_note_save();exit;}



if(isset($_GET["cyberipcrime-disable"])){CybercrimeIPFeeds_disable();exit;}
if(isset($_GET["cyberipcrime-enable"])){CybercrimeIPFeeds_enable();exit;}
if(isset($_POST["cyberipcrime-disable"])){CybercrimeIPFeeds_disable_confirm();exit;}

if(isset($_POST["ArticaPSnifferIpSetEnabled"])){SaveTokensSettings();exit;}
if(isset($_POST["ArticaPSnifferProxMoxEnabled"])){SaveTokensSettings();exit;}
if(isset($_POST["ArticaPSnifferHTTPInterface"])){SaveTokensSettings();exit;}
if(isset($_POST["ArticaPSnifferPfSenseEnable"])){SaveTokensSettings();exit;}


if(isset($_POST["IPFeedsUseIPset"])){SaveTokensSettings();exit;}
if(isset($_POST["EnableFireholIPSets"])){Save();exit;}
if(isset($_GET["ipf-now"])){ipf_now_refresh();exit;}
if(isset($_GET["left-status"])){left_status();exit;}
if(isset($_GET["left-status-pcap"])){echo left_status_pcap();exit;}
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
if(isset($_GET["ipfb-list"])){ipfb_list();exit;}
if(isset($_POST["ipfb-allow"])){ipfb_allow_save();exit;}
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
function delete_blocked_confirm():bool{
    $ipaddr=ipfb_path(trim((string)$_POST["delete-blocked"]));
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/blocked/remove/$ipaddr"));
    if(!is_object($json) || empty($json->Status)){
        if(!is_object($json)){echo "No answer from the engine";return false;}
        echo $json->Error;
        return false;
    }
    return admin_tracks("Removed {$_POST["delete-blocked"]} from Cybercrime IP Feeds block database");
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
        $function="&function=".preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"]);
    }
    $ip=ipfb_js_ip((string)($_GET["ip"] ?? ""));
    return $tpl->js_dialog_modal("{ban_ips}","$page?banips-popup=yes$function&ip=".rawurlencode($ip));
}
function action_white_ip():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $function="";
    if(isset($_GET["function"])){
        $function="&function=".preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"]);
    }
    return $tpl->js_dialog_modal("{allow_an_IP_address}","$page?white-popup=yes$function");
}
function action_checkip():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $ip=ipfb_js_ip((string)($_GET["ip"] ?? ""));
    return $tpl->js_dialog_modal("{check_ip_reputation}","$page?checkip-popup=yes&ip=".rawurlencode($ip));
}
function action_ban_ip_popup():bool{
    $tpl=new template_admin();
    $html[]=$tpl->div_explain("{ban_ips}||{field_ipaddr_cdir_comma}",true);
    $function="";
    if(isset($_GET["function"])){
        $function=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"])."();";
    }
    $form[]=$tpl->field_text_big("banip","{network2}",ipfb_js_ip((string)($_GET["ip"] ?? "")),true);
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
        $function=preg_replace('/[^A-Za-z0-9_]/','',(string)$_GET["function"])."();";
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
    $form[]=$tpl->field_text_big("checkip","{ipaddr}",ipfb_js_ip((string)($_GET["ip"] ?? "")),true);
    $html[]=$tpl->form_outside("",$form,"","NO","");
    $button=$tpl->button_autnonome("{close}","DialogModal.close();",ico_exit);
    $html[]="<div style='margin:10px;text-align:right;'>$button</div>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function action_checkip_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $checkip=ipfb_path(trim((string)$_POST["checkip"]));
    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/query/$checkip"));
    echo $tpl->post_error($json->Info."(".$json->Category.")");
    return true;
}
function action_white_ip_save():bool{
    // Même règle que l'onglet White lists : plusieurs valeurs, toutes validées, chemin doublement encodé.
    $_POST["ipfw-add"]=(string)$_POST["white"];
    return ipfw_add_save();
}
function action_ban_ip_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $tb=explode(",",$_POST["banip"]);
    foreach ($tb as $value){
        $value=trim($value);
        if($value===""){continue;}
        $value=ipfb_path($value);
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/block/$value"));
        if(!$json->Status){
            echo $tpl->post_error($json->Error);
            return false;
        }
    }
    return admin_tracks("Cybercrime IP add {$_POST["banip"]} to banned IPs");
}
function sources_start():bool{
    $tpl=new template_admin();
    echo ipfs_style()."<div class='ipf ipfs' id='ipfs-root'><div id='ipfs-host'>".$tpl->_ENGINE_parse_body(ipfs_render(ipfs_model()))."</div></div>".ipfs_js();
    return true;
}

// Hôte seul, rechargé après chaque action (IpfSrcReload).
function ipfs_list():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(ipfs_render(ipfs_model()));
    return true;
}

// Sources intégrées au démon : elles ne se suppriment pas.
function ipfs_builtin():array{
    return array("articacloud"=>true,"dronerbl"=>true,"firehollevel1"=>true,"fireholproxies"=>true,"intelligence_ipv4_blocklist"=>true);
}

// Une liste téléchargée est rechargée par le démon au-delà de 120 minutes.
function ipfs_stale_after():int{return 7200;}

function ipfs_domain(string $url,string $doh):string{
    if($doh!==""){return $doh;}
    $h=parse_url($url,PHP_URL_HOST);
    if(is_string($h) && $h!==""){return $h;}
    return trim($url);
}

function ipfs_model():array{
    $m=array("enabled"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue")),"error"=>"",
        "lists"=>array(),"live"=>array(),"abuse"=>null,"n"=>0,"on"=>0,"off"=>0,"addresses"=>0,"stale"=>0,"now"=>time());
    if($m["enabled"]==0){return $m;}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/sources"));
    if(!is_object($json)){$m["error"]="{ipf_err_noanswer}";return $m;}
    if(empty($json->Status)){
        $e=isset($json->Error)?trim((string)$json->Error):"";
        $m["error"]=$e!==""?ipf_h($e):"{ipf_err_noanswer}";
        return $m;
    }
    if(!isset($json->Sources) || !is_array($json->Sources)){$m["error"]="{ipf_err_protocol}";return $m;}
    $builtin=ipfs_builtin();
    foreach($json->Sources as $s){
        if(!is_object($s)){continue;}
        $name=(string)($s->categoryname ?? "");
        if($name==="" || $name==="AbuseIPDB"){continue;}
        $doh=intval($s->doh_source ?? 0)==1;
        $rbl=!$doh && intval($s->rbl_source ?? 0)==1;
        $r=array("name"=>$name,"desc"=>trim((string)($s->description ?? "")),"on"=>intval($s->enabled ?? 0)==1,
            "records"=>floatval($s->Records ?? 0),"ts"=>intval($s->TimeStamp ?? 0),"ms"=>intval($s->answer_time_ms ?? 0),
            "domain"=>ipfs_domain((string)($s->url ?? ""),$doh?trim((string)($s->doh_domain ?? "")):""),
            "builtin"=>isset($builtin[strtolower($name)]),"kind"=>$doh?"doh":($rbl?"dnsbl":"list"),
            "answers"=>array(),"id"=>"r".md5($name));
        if(isset($s->rbl_answer) && is_object($s->rbl_answer)){
            foreach($s->rbl_answer as $ip=>$label){$r["answers"][]=trim($ip." ".(string)$label);}
        }
        if($name==="AbuseIPDB_API"){
            $a=(isset($s->abuseipdb) && is_object($s->abuseipdb))?$s->abuseipdb:new stdClass();
            $r["haskey"]=strlen(trim((string)($a->apikey ?? "")))>=5;
            $r["confidence"]=intval($a->confidenceMinimum ?? 0);
            $r["quota"]=intval($a->rate_limit ?? 0);
            $m["abuse"]=$r;
        }elseif($r["kind"]==="list"){
            $r["state"]="fresh";
            if(!$r["on"]){$r["state"]="off";}
            elseif($r["ts"]==0){$r["state"]="waiting";}
            elseif($m["now"]-$r["ts"]>ipfs_stale_after()){$r["state"]="stale";$m["stale"]++;}
            if($r["on"]){$m["addresses"]+=$r["records"];}
            $m["lists"][]=$r;
        }else{
            $m["live"][]=$r;
        }
        $m["n"]++;
        if($r["on"]){$m["on"]++;}else{$m["off"]++;}
    }
    // ProtectMy.site vit dans la configuration générale, pas dans /sources.
    $st=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/status"));
    if(is_object($st) && !empty($st->Status) && isset($st->Config->ProtectMySite) && is_object($st->Config->ProtectMySite)){
        $p=$st->Config->ProtectMySite;
        $on=intval($p->enabled ?? 0)==1;
        $m["live"][]=array("name"=>"ProtectMy.site","desc"=>"{ipfs_psm_desc}","desc_tok"=>true,"on"=>$on,"records"=>floatval($p->queries ?? 0),
            "ts"=>0,"ms"=>intval($p->answer_time_ms ?? 0),"domain"=>"protectmy.site","builtin"=>true,"kind"=>"psm","answers"=>array(),"id"=>"rpsm");
        $m["n"]++;
        if($on){$m["on"]++;}else{$m["off"]++;}
    }
    return $m;
}

function ipfs_render(array $m):string{
    $h=array();
    if($m["enabled"]==0){
        $h[]=ipfs_invite();
    }elseif($m["error"]!==""){
        $h[]=ipfs_error($m);
    }else{
        $h[]=ipfs_banner();
        $h[]=ipfs_summary($m);
        $h[]=ipfs_alerts($m);
        $h[]=ipfs_card_lists($m);
        $h[]=ipfs_card_live($m);
        if(is_array($m["abuse"])){$h[]=ipfs_card_abuse($m);}
        $h[]=ipfs_footer();
    }
    $h[]=ipfs_tiny($m);
    return implode("\n",$h);
}

function ipfs_ago(int $ts,int $now):string{
    if($ts<=0){return "";}
    return ipf_t("ipfs_ago",ipf_duration(max(60,$now-$ts)));
}

function ipfs_banner():string{
    $c=ipf_chip("fas fa-rotate",ipf_t("ipfs_chip_refresh")).ipf_chip("fas fa-bolt",ipf_t("ipfs_chip_live")).ipf_chip("fas fa-lock",ipf_t("ipfs_chip_builtin"));
    return "<div class='ipf-banner'><span class='ipf-banner-i' aria-hidden='true'><i class='fas fa-satellite-dish'></i></span>"
        ."<div class='ipf-banner-b'><h2>{ipfs_banner_title}</h2><p>{ipfs_banner_text}</p><div class='ipf-chips'>$c</div></div></div>";
}

function ipfs_summary(array $m):string{
    return "<div class='ipfs-sum'><b>".ipf_h(ipf_t("ipfs_sum_n",$m["n"]))."</b> &mdash; "
        ."<span class='ipfs-dot ipfs-dot-on' aria-hidden='true'></span>".ipf_h(ipf_t("ipfs_sum_on",$m["on"]))." &middot; "
        ."<span class='ipfs-dot ipfs-dot-off' aria-hidden='true'></span>".ipf_h(ipf_t("ipfs_sum_off",$m["off"]))." &middot; "
        .ipf_h(ipf_t("ipfs_sum_addr",ipf_num($m["addresses"])))."</div>";
}

function ipfs_alerts(array $m):string{
    $page=CurrentPageName();
    $a=array();
    if($m["stale"]>0){
        $a[]="<div class='ipfs-alert'><span class='ipfs-alert-i' aria-hidden='true'><i class='fas fa-triangle-exclamation'></i></span><span class='ipfs-alert-t'>"
            .ipf_h(ipf_t("ipfs_alert_stale",$m["stale"]))."</span><button type='button' class='ipfs-alert-b' OnClick=\"IpfSrcFilter('stale')\">{ipfs_show_these}</button></div>";
    }
    $ab=$m["abuse"];
    if(is_array($ab) && $ab["on"] && $ab["quota"]>0 && $m["now"]-$ab["quota"]<86400){
        $a[]="<div class='ipfs-alert'><span class='ipfs-alert-i' aria-hidden='true'><i class='fas fa-gauge-high'></i></span><span class='ipfs-alert-t'>"
            .ipf_h(ipf_t("ipfs_alert_quota",date("H:i",$ab["quota"])))."</span><button type='button' class='ipfs-alert-b' OnClick=\"IpfSrcGo('ipfs-abuse')\">{ipfs_open_abuse}</button></div>";
    }
    if(is_array($ab) && $ab["on"] && !$ab["haskey"]){
        $a[]="<div class='ipfs-alert'><span class='ipfs-alert-i' aria-hidden='true'><i class='fas fa-key'></i></span><span class='ipfs-alert-t'>{ipfs_alert_nokey}</span>"
            ."<button type='button' class='ipfs-alert-b' OnClick=\"Loadjs('$page?AbuseIPDB-js=AbuseIPDB_API&function=IpfSrcReload')\">{ipfs_add_key}</button></div>";
    }
    if(count($a)==0){return "";}
    return "<div class='ipfs-alerts' role='status'>".implode("",$a)."</div>";
}

function ipfs_switch(array $r):string{
    $on=$r["on"]?"true":"false";
    $txt=$r["on"]?"{ipf_on}":"{ipf_off}";
    $warn=($r["kind"]==="list" && $r["on"] && $r["records"]>0)?1:0;
    $name=ipf_h($r["name"]);
    return "<span class='ipfs-sw-w'><span class='ipfs-sw-t'>$txt</span><button type='button' role='switch' aria-checked='$on' class='ipfs-sw' aria-label=\"$name\""
        ." OnClick=\"IpfSrcToggle('".rawurlencode($r["name"])."','{$r["id"]}',$warn)\"><span class='ipfs-sw-k'></span></button></span>";
}

function ipfs_icon_btn(string $ico,string $labelKey,string $js,string $cls=""):string{
    return "<button type='button' class='ipfs-ib $cls' OnClick=\"$js\" title=\"".ipf_h(ipf_t($labelKey))."\" aria-label=\"".ipf_h(ipf_t($labelKey))."\"><i class='fas $ico' aria-hidden='true'></i></button>";
}

function ipfs_badges(array $r):string{
    $b=array();
    if($r["builtin"]){$b[]="<span class='ipfs-tag' title=\"".ipf_h(ipf_t("ipfs_builtin_tip"))."\"><i class='fas fa-lock' aria-hidden='true'></i> {ipfs_builtin}</span>";}
    else{$b[]="<span class='ipfs-tag ipfs-tag-you'><i class='fas fa-user-gear' aria-hidden='true'></i> {ipfs_added}</span>";}
    if($r["kind"]==="dnsbl"){$b[]="<span class='ipfs-tag'>{ipfs_kind_dnsbl} <small>(DNSBL)</small></span>";}
    if($r["kind"]==="doh"){$b[]="<span class='ipfs-tag'>{ipfs_kind_doh} <small>(DoH)</small></span>";}
    if($r["kind"]==="psm"){$b[]="<span class='ipfs-tag'>{ipfs_kind_psm}</span>";}
    if($r["domain"]!==""){$b[]="<span class='ipfs-dom'>".ipf_h($r["domain"])."</span>";}
    return implode("",$b);
}

function ipfs_name(array $r,string $extra=""):string{
    $d=!empty($r["desc_tok"])?$r["desc"]:ipf_h($r["desc"]);
    $desc=$r["desc"]!==""?"<div class='ipfs-desc'>$d</div>":"";
    return "<div class='ipfs-main'><div class='ipfs-title'><span class='ipfs-nm'>".ipf_h($r["name"])."</span>".ipfs_badges($r)."</div>$desc$extra</div>";
}

function ipfs_list_numbers(array $r,int $now):string{
    $n="<div class='ipfs-big'>".ipf_num($r["records"])." <small>{ipfs_addresses}</small></div>";
    $exact=$r["ts"]>0?" title=\"".date("Y-m-d H:i:s",$r["ts"])."\"":"";
    switch($r["state"]){
        case "fresh": $f="<span class='ipfs-fr ipfs-ok'$exact><span class='ipfs-dot ipfs-dot-on' aria-hidden='true'></span>".ipf_h(ipf_t("ipfs_updated",ipfs_ago($r["ts"],$now)))."</span>";break;
        case "stale": $f="<span class='ipfs-fr ipfs-warn'$exact><i class='fas fa-clock' aria-hidden='true'></i> ".ipf_h(ipf_t("ipfs_not_refreshed",ipf_duration(max(60,$now-$r["ts"]))))."</span>";break;
        case "waiting": $f="<span class='ipfs-fr ipfs-mute'><span class='ipfs-dot ipfs-dot-off' aria-hidden='true'></span>{ipfs_waiting}</span>";break;
        default: $f="<span class='ipfs-fr ipfs-mute'><span class='ipfs-dot ipfs-dot-off' aria-hidden='true'></span>{ipfs_off_nokeep}</span>";
    }
    return "<div class='ipfs-num'>$n$f</div>";
}

function ipfs_row_list(array $r,int $now):string{
    $page=CurrentPageName();
    $enc=rawurlencode($r["name"]);
    $act=array(ipfs_switch($r));
    if($r["on"]){$act[]=ipfs_icon_btn("fa-rotate","ipfs_reload","Loadjs('$page?category-refresh=$enc&function=IpfSrcReload')",$r["state"]==="stale"?"ipfs-ib-warn":"");}
    else{$act[]="<span class='ipfs-ib-ph'></span>";}
    $act[]=ipfs_icon_btn("fa-pen","ipf_edit","Loadjs('$page?category=$enc&function=IpfSrcReload')");
    $act[]=$r["builtin"]?"<span class='ipfs-ib-ph'></span>":ipfs_icon_btn("fa-trash","ipfs_delete","Loadjs('$page?category-delete=$enc&md={$r["id"]}')","ipfs-ib-del");
    $s=ipf_h(strtolower($r["name"]." ".$r["domain"]));
    $st=$r["on"]?"on":"off";
    return "<div class='ipfs-row ipfs-st-{$r["state"]}' id='{$r["id"]}' data-s=\"$s\" data-on='$st' data-state='{$r["state"]}'>"
        ."<div class='ipfs-line'>".ipfs_name($r).ipfs_list_numbers($r,$now)."<div class='ipfs-act'>".implode("",$act)."</div></div>"
        ."<div class='ipfs-confirm' hidden><i class='fas fa-circle-info' aria-hidden='true'></i><span>".ipf_h(ipf_t("ipfs_off_confirm",$r["name"],ipf_num($r["records"])))."</span>"
        ."<button type='button' class='ipfs-cf-go' OnClick=\"IpfSrcOff('$enc')\">{ipfs_turn_off}</button><button type='button' class='ipfs-cf-no' OnClick=\"IpfSrcCancel('{$r["id"]}')\">{ipfs_cancel}</button></div></div>";
}

function ipfs_filters():string{
    $f=array("all"=>"{ipfs_f_all}","on"=>"{ipf_on}","off"=>"{ipf_off}","stale"=>"{ipfs_f_stale}");
    $b=array();
    foreach($f as $k=>$l){
        $sel=$k==="all"?"true":"false";
        $b[]="<button type='button' role='radio' aria-checked='$sel' class='ipfs-seg' data-f='$k' OnClick=\"IpfSrcFilter('$k')\">$l</button>";
    }
    return "<div class='ipfs-tools'><div class='ipfs-segs' role='radiogroup' aria-label=\"".ipf_h(ipf_t("ipfs_filter_label"))."\">".implode("",$b)."</div>"
        ."<label class='ipfs-search'><span class='ipfs-search-i' aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span>"
        ."<input type='search' id='ipfs-q' placeholder=\"".ipf_h(ipf_t("ipfs_search_ph"))."\" aria-label=\"".ipf_h(ipf_t("ipfs_search_ph"))."\" OnInput=\"IpfSrcApply()\"></label></div>";
}

function ipfs_card_head(string $ico,string $title,string $intro,string $tools=""):string{
    return "<div class='ipfs-ch'><span class='ipf-card-i' aria-hidden='true'><i class='fas $ico'></i></span><div class='ipfs-ch-b'><h3>$title</h3><p>$intro</p></div>$tools</div>";
}

function ipfs_card_lists(array $m):string{
    $rows=array();
    foreach($m["lists"] as $r){$rows[]=ipfs_row_list($r,$m["now"]);}
    $body=count($rows)>0?implode("",$rows):"<div class='ipfs-empty'>{ipfs_no_list}</div>";
    return "<div class='ipf-card ipfs-card' id='ipfs-lists'>".ipfs_card_head("fa-cloud-arrow-down","{ipfs_lists_title}","{ipfs_lists_intro}",ipfs_filters())
        ."<div class='ipfs-rows'>$body<div class='ipfs-empty' id='ipfs-nomatch' hidden>{ipfs_no_match} <button type='button' class='ipf-link' OnClick=\"IpfSrcFilter('all',1)\">{ipfs_show_all}</button></div></div></div>";
}

function ipfs_speed(int $ms):string{
    if($ms<=0){return "<span class='ipfs-fr ipfs-mute'><span class='ipfs-dot ipfs-dot-off' aria-hidden='true'></span>{ipfs_no_lookup}</span>";}
    $cls="ipfs-ok";$dot="ipfs-dot-on";
    if($ms>1000){$cls="ipfs-bad";$dot="ipfs-dot-bad";}elseif($ms>=100){$cls="ipfs-warn";$dot="ipfs-dot-warn";}
    return "<span class='ipfs-fr $cls'><span class='ipfs-dot $dot' aria-hidden='true'></span>".ipf_h(ipf_t("ipfs_last_answer",$ms))."</span>";
}

function ipfs_row_live(array $r):string{
    $page=CurrentPageName();
    $enc=rawurlencode($r["name"]);
    $act=array();
    if($r["kind"]==="psm"){
        $act[]="<span class='ipfs-sw-w'>".ipf_state($r["on"],"{ipf_on}","{ipf_off}")."</span>";
        $act[]="<button type='button' class='ipf-edit' OnClick=\"Loadjs('$page?ProtectMySite=yes&function=IpfSrcReload')\"><i class='fas fa-sliders' aria-hidden='true'></i> {ipfs_categories}</button>";
    }else{
        $act[]=ipfs_switch($r);
        $act[]=ipfs_icon_btn("fa-pen","ipf_edit","Loadjs('$page?category=$enc&function=IpfSrcReload')");
        $act[]=$r["builtin"]?"<span class='ipfs-ib-ph'></span>":ipfs_icon_btn("fa-trash","ipfs_delete","Loadjs('$page?category-delete=$enc&md={$r["id"]}')","ipfs-ib-del");
    }
    $codes="";
    if(count($r["answers"])>0){
        $cc=array();
        foreach($r["answers"] as $a){$cc[]="<span class='ipfs-code'>".ipf_h($a)."</span>";}
        $codes="<div class='ipfs-codes'><b>{ipfs_answer_codes}</b>".implode("",$cc)."</div>";
    }
    $num=$r["on"]?"<div class='ipfs-big'>".ipf_num($r["records"])." <small>{ipfs_lookups}</small></div>".ipfs_speed($r["ms"])
        :"<div class='ipfs-big ipfs-mute'>&mdash;</div><span class='ipfs-fr ipfs-mute'><span class='ipfs-dot ipfs-dot-off' aria-hidden='true'></span>{ipfs_not_asked}</span>";
    return "<div class='ipfs-row' id='{$r["id"]}'><div class='ipfs-line'>".ipfs_name($r,$codes).""
        ."<div class='ipfs-num'>$num</div><div class='ipfs-act'>".implode("",$act)."</div></div></div>";
}

function ipfs_card_live(array $m):string{
    $rows=array();
    foreach($m["live"] as $r){$rows[]=ipfs_row_live($r);}
    $body=count($rows)>0?implode("",$rows):"<div class='ipfs-empty'>{ipfs_no_live}</div>";
    return "<div class='ipf-card ipfs-card'>".ipfs_card_head("fa-bolt","{ipfs_live_title}","{ipfs_live_intro}")
        ."<div class='ipfs-example'><i class='fas fa-lightbulb' aria-hidden='true'></i> {ipfs_live_example}</div><div class='ipfs-rows'>$body</div></div>";
}

function ipfs_card_abuse(array $m):string{
    $page=CurrentPageName();
    $a=$m["abuse"];
    $settings="Loadjs('$page?AbuseIPDB-js=AbuseIPDB_API&function=IpfSrcReload')";
    $tools="<div class='ipfs-act'>".ipfs_switch($a)."<button type='button' class='ipf-edit' OnClick=\"$settings\"><i class='fas fa-gear' aria-hidden='true'></i> {ipfs_settings}</button></div>";
    $key=$a["haskey"]?"<span class='ipf-st ipf-st-on'><i class='fas fa-key' aria-hidden='true'></i> {ipfs_key_set}</span>"
        :"<span class='ipf-st ipf-st-warn'><i class='fas fa-key' aria-hidden='true'></i> {ipfs_key_missing}</span> <button type='button' class='ipf-link' OnClick=\"$settings\">{ipfs_add_key}</button>";
    $conf=$a["confidence"]>0?ipf_h(ipf_t("ipfs_confidence",$a["confidence"])):ipf_h(ipf_t("ipfs_confidence_none"));
    $last=$a["ts"]>0?"<span title=\"".date("Y-m-d H:i:s",$a["ts"])."\">".ipf_h(ipf_t("ipfs_last_one",ipfs_ago($a["ts"],$m["now"])))."</span>":"{ipfs_no_lookup}";
    $tiles="<div class='ipfs-tiles'>"
        ."<div class='ipfs-tile'><div class='ipfs-tile-l'>{ipfs_auth}</div><div class='ipfs-tile-v'>$key</div><p class='ipf-help'>{ipfs_key_help}</p></div>"
        ."<div class='ipfs-tile'><div class='ipfs-tile-l'>{ipfs_threshold}</div><div class='ipfs-tile-v'><b>$conf</b></div><p class='ipf-help'>{ipfs_confidence_help}</p></div>"
        ."<div class='ipfs-tile'><div class='ipfs-tile-l'>{ipfs_activity}</div><div class='ipfs-tile-v'><b>".ipf_h(ipf_t("ipfs_n_lookups",ipf_num($a["records"])))."</b></div><p class='ipf-help'>$last</p></div></div>";
    $quota="";
    if($a["quota"]>0 && $m["now"]-$a["quota"]<86400){
        $quota="<div class='ipfs-quota' role='alert'><i class='fas fa-gauge-high' aria-hidden='true'></i><span title=\"".date("Y-m-d H:i:s",$a["quota"])."\">"
            .ipf_h(ipf_t("ipfs_quota_line",ipfs_ago($a["quota"],$m["now"]),date("M j, H:i",$a["quota"]+86400)))."</span></div>";
    }
    return "<div class='ipf-card ipfs-card' id='ipfs-abuse'>".ipfs_card_head("fa-users","AbuseIPDB","{ipfs_abuse_intro}",$tools)."<div class='ipfs-rows ipfs-pad'>$tiles$quota</div></div>";
}

function ipfs_footer():string{
    return "<div class='ipfs-foot'><i class='fas fa-circle-info' aria-hidden='true'></i> {ipfs_footer} <span class='ipf-mono'>https://example.org/blocklist.txt</span></div>";
}

function ipfs_invite():string{
    return "<div class='ipf-invite'><span class='ipf-invite-i' aria-hidden='true'><i class='fas fa-satellite-dish'></i></span>"
        ."<h2>{ipfs_invite_title}</h2><p>{ipfs_invite_text}</p>"
        ."<button type='button' class='ipf-btn' OnClick=\"IpfSrcParams()\"><i class='fas fa-arrow-left' aria-hidden='true'></i> {ipfs_go_params}</button></div>";
}

function ipfs_error(array $m):string{
    return "<div class='ipf-errbox' role='alert'><span class='ipf-errbox-i' aria-hidden='true'><i class='fas fa-circle-xmark'></i></span>"
        ."<div><b>{ipfs_err_title}</b><p>{$m["error"]}</p></div></div>";
}

function ipfs_tiny(array $m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    if($m["enabled"]==1){
        $topbuttons[]=array("Loadjs('$page?category=&function=IpfSrcReload')",ico_plus,"{new_source}");
        $topbuttons[]=array("Loadjs('$page?checkip=yes')",ico_loupe,"{check_ip_reputation}");
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{CybercrimeIPFeeds}";
    $TINY_ARRAY["ICO"]="fas fa-hockey-mask";
    $TINY_ARRAY["EXPL"]="{ipfs_header_explain}";
    $TINY_ARRAY["URL"]="Cybercrime-IP-Feeds";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

function ipfs_js():string{
    $page=CurrentPageName();
    return "<script>(function(){
var F='all';
function root(){return document.getElementById('ipfs-root');}
window.IpfSrcReload=function(){var q=document.getElementById('ipfs-q');var keep=q?q.value:'';var f=F;
  if(typeof LoadAjaxSilent==='function'){LoadAjaxSilent('ipfs-host','$page?ipfs-list=yes');}
  setTimeout(function(){var q2=document.getElementById('ipfs-q');if(q2&&keep){q2.value=keep;}IpfSrcFilter(f);},900);};
window.IpfSrcApply=function(){var r=root();if(!r){return;}var q=document.getElementById('ipfs-q');var t=q?q.value.toLowerCase().trim():'';
  var rows=r.querySelectorAll('#ipfs-lists .ipfs-row');var n=0;
  for(var i=0;i<rows.length;i++){var e=rows[i];var ok=true;
    if(F==='on'&&e.getAttribute('data-on')!=='on'){ok=false;}
    if(F==='off'&&e.getAttribute('data-on')!=='off'){ok=false;}
    if(F==='stale'&&e.getAttribute('data-state')!=='stale'){ok=false;}
    if(t!==''&&(e.getAttribute('data-s')||'').indexOf(t)<0){ok=false;}
    e.hidden=!ok;if(ok){n++;}}
  var nm=document.getElementById('ipfs-nomatch');if(nm){nm.hidden=(n>0||rows.length===0);}};
window.IpfSrcFilter=function(f,clear){F=f;var r=root();if(!r){return;}
  if(clear){var q=document.getElementById('ipfs-q');if(q){q.value='';}}
  var b=r.querySelectorAll('.ipfs-seg');for(var i=0;i<b.length;i++){b[i].setAttribute('aria-checked',b[i].getAttribute('data-f')===f?'true':'false');}
  IpfSrcApply();if(f==='stale'){var l=document.getElementById('ipfs-lists');if(l&&l.scrollIntoView){l.scrollIntoView({block:'start'});}}};
window.IpfSrcGo=function(id){var e=document.getElementById(id);if(e&&e.scrollIntoView){e.scrollIntoView({block:'start'});}};
window.IpfSrcToggle=function(enc,id,warn){if(warn){var row=document.getElementById(id);var c=row?row.querySelector('.ipfs-confirm'):null;if(c){c.hidden=false;var g=c.querySelector('.ipfs-cf-go');if(g){g.focus();}return;}}
  Loadjs('$page?category-enable='+enc+'&function=IpfSrcReload');};
window.IpfSrcOff=function(enc){Loadjs('$page?category-enable='+enc+'&function=IpfSrcReload');};
window.IpfSrcCancel=function(id){var row=document.getElementById(id);var c=row?row.querySelector('.ipfs-confirm'):null;if(c){c.hidden=true;}};
window.IpfSrcParams=function(){var r=root();var c=r?$(r).closest('.tabs-container'):$();var a=(c.length?c:$(document)).find('ul.nav-tabs a[data-url*=\"parameters=yes\"]').first();if(a.length){a.trigger('click');}};
})();</script>";
}

function ipfs_style():string{
    return ipf_style()."<style>
.ipfs{margin-top:14px;color:#0f172a;font-size:14px}
.ipfs *{box-sizing:border-box}
.ipfs-sum{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin:0 2px 14px;font-size:13.5px;color:#334155}
.ipfs-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:5px;vertical-align:middle;flex:0 0 8px}
.ipfs-dot-on{background:#15803d}.ipfs-dot-off{background:#cbd5e1}.ipfs-dot-warn{background:#d97706}.ipfs-dot-bad{background:#b91c1c}
.ipfs-alerts{border:1px solid #fcd34d;background:#fffbeb;border-radius:10px;margin-bottom:16px}
.ipfs-alert{display:flex;align-items:center;gap:10px;padding:11px 14px;color:#92400e;font-size:13.5px}
.ipfs-alert+.ipfs-alert{border-top:1px solid #fde68a}
.ipfs-alert-i{display:inline-flex;width:16px;height:16px;color:#b45309}.ipfs-alert-i svg{width:15px;height:15px}
.ipfs-alert-t{flex:1}
.ipfs-alert-b{background:#fef3c7;border:1px solid #fcd34d;color:#92400e;border-radius:7px;padding:5px 12px;font-weight:600;font-size:13px;cursor:pointer}
.ipfs-alert-b:hover{background:#fde68a}
.ipfs-card{padding:0}
.ipfs-ch{display:flex;align-items:flex-start;gap:12px;padding:16px 18px;border-bottom:1px solid #f1f5f9;flex-wrap:wrap}
.ipfs-ch-b{flex:1;min-width:240px}
.ipfs-ch h3{margin:2px 0 3px;font-size:16px;font-weight:600}
.ipfs-ch p{margin:0;font-size:13px;color:#64748b}
.ipfs-tools{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.ipfs-segs{display:inline-flex;border:1px solid #e2e8f0;border-radius:8px;padding:2px;background:#f8fafc}
.ipfs-seg{background:none;border:0;border-radius:6px;padding:5px 11px;font-size:13px;font-weight:600;color:#475569;cursor:pointer}
.ipfs-seg[aria-checked=true]{background:#fff;color:var(--ipf-acc);box-shadow:0 1px 2px rgba(15,23,42,.1)}
.ipfs-seg[data-f=stale]{color:#b45309}
.ipfs-search{position:relative;margin:0;font-weight:400}
.ipfs-search-i{position:absolute;left:10px;top:50%;transform:translateY(-50%);display:inline-flex;width:13px;height:13px;color:#94a3b8;pointer-events:none}
.ipfs-search-i svg{width:13px;height:13px}
.ipfs-search input{width:230px;border:1px solid #e2e8f0;border-radius:8px;padding:7px 10px 7px 30px;font-size:13px}
.ipfs-search input:focus{outline:2px solid var(--ipf-acc);outline-offset:1px;border-color:var(--ipf-bd)}
.ipfs-rows{padding:0 18px}
.ipfs-pad{padding:16px 18px}
.ipfs-row{border-top:1px solid #f1f5f9;padding:14px 0}
.ipfs-row:first-child{border-top:0}
.ipfs-row:hover{background:#fcfdfd}
.ipfs-st-stale{background:#fffdf5}
.ipfs-st-off .ipfs-nm,.ipfs-st-off .ipfs-desc{color:#94a3b8}
.ipfs-line{display:flex;align-items:center;gap:18px}
.ipfs-main{flex:1;min-width:0}
.ipfs-title{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin-bottom:3px}
.ipfs-nm{font-size:15px;font-weight:600;word-break:break-all}
.ipfs-tag{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:1px 8px}
.ipfs-tag small{font-weight:500;color:#94a3b8}
.ipfs-tag svg{width:10px;height:10px}
.ipfs-tag-you{color:var(--ipf-acc);background:var(--ipf-soft);border-color:var(--ipf-bd)}
.ipfs-dom{font-family:'JetBrains Mono',Consolas,monospace;font-size:12px;color:#94a3b8;word-break:break-all}
.ipfs-desc{font-size:13px;color:#64748b;line-height:1.45}
.ipfs-num{flex:0 0 210px}
.ipfs-big{font-size:18px;font-weight:700;line-height:1.3}
.ipfs-big small{font-size:13px;font-weight:500;color:#64748b}
.ipfs-fr{display:inline-flex;align-items:center;gap:4px;font-size:13px;font-weight:600}
.ipfs-fr svg{width:12px;height:12px}
.ipfs-ok{color:#15803d}.ipfs-warn{color:#b45309}.ipfs-bad{color:#b91c1c}.ipfs-mute{color:#94a3b8}
.ipfs-act{display:flex;align-items:center;gap:8px;flex:0 0 auto}
.ipfs-sw-w{display:inline-flex;align-items:center;gap:8px;min-width:78px;justify-content:flex-end}
.ipfs-sw-t{font-size:13px;font-weight:600;color:#475569}
.ipfs-sw{position:relative;width:40px;height:22px;border-radius:999px;border:0;background:#cbd5e1;cursor:pointer;padding:0;transition:background .15s}
.ipfs-sw[aria-checked=true]{background:var(--ipf-acc)}
.ipfs-sw-k{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.2);transition:left .15s}
.ipfs-sw[aria-checked=true] .ipfs-sw-k{left:21px}
.ipfs-ib,.ipfs-ib-ph{width:34px;height:34px;flex:0 0 34px}
.ipfs-ib{display:inline-flex;align-items:center;justify-content:center;background:#fff;border:1px solid #e2e8f0;border-radius:8px;color:#475569;cursor:pointer}
.ipfs-ib svg{width:13px;height:13px}
.ipfs-ib:hover{border-color:var(--ipf-bd);color:var(--ipf-acc);background:#f2f6f6}
.ipfs-ib-warn{border-color:#fcd34d;color:#b45309;background:#fffbeb}
.ipfs-ib-del{color:#b91c1c}.ipfs-ib-del:hover{border-color:#fca5a5;color:#b91c1c;background:#fef2f2}
.ipfs button:focus-visible{outline:2px solid var(--ipf-acc);outline-offset:2px}
.ipfs-confirm{display:flex;align-items:center;gap:10px;margin-top:10px;padding:9px 12px;border:1px solid #fcd34d;background:#fffbeb;border-radius:8px;color:#92400e;font-size:13px}
.ipfs-confirm[hidden]{display:none}
.ipfs-confirm span{flex:1}
.ipfs-confirm svg{width:13px;height:13px}
.ipfs-cf-go{background:#b45309;color:#fff;border:0;border-radius:7px;padding:5px 12px;font-weight:600;cursor:pointer}
.ipfs-cf-no{background:#fff;border:1px solid #e2e8f0;border-radius:7px;padding:5px 12px;font-weight:600;color:#475569;cursor:pointer}
.ipfs-example{margin:12px 18px 2px;padding:8px 12px;border-radius:8px;background:#f8fafc;border:1px solid #f1f5f9;font-size:13px;color:#64748b}
.ipfs-example svg{width:12px;height:12px}
.ipfs-codes{display:flex;flex-wrap:wrap;align-items:center;gap:6px;margin-top:6px;font-size:12.5px}
.ipfs-codes b{font-weight:600;color:#475569;margin-right:2px}
.ipfs-code{font-family:'JetBrains Mono',Consolas,monospace;font-size:12px;background:#f1f5f9;border-radius:6px;padding:1px 8px;color:#334155}
.ipfs-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.ipfs-tile{border:1px solid #e2e8f0;border-radius:10px;padding:14px}
.ipfs-tile-l{font-size:11.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#64748b;margin-bottom:8px}
.ipfs-tile-v{font-size:15px;display:flex;align-items:center;flex-wrap:wrap;gap:8px}
.ipfs-quota{display:flex;align-items:center;gap:10px;margin-top:14px;padding:11px 14px;border:1px solid #fecaca;background:#fef2f2;border-radius:10px;color:#991b1b;font-size:13.5px;font-weight:600}
.ipfs-quota svg{width:15px;height:15px}
.ipfs-empty{padding:18px 0;color:#64748b;font-size:13.5px;text-align:center}
.ipfs-empty[hidden]{display:none}
.ipfs-row[hidden]{display:none}
.ipfs-foot{margin:4px 2px 8px;font-size:13px;color:#64748b}
.ipfs-foot svg{width:12px;height:12px}
@media (max-width:1200px){.ipfs-line{flex-wrap:wrap}.ipfs-main{flex:1 1 100%}.ipfs-num{flex:1 1 auto}.ipfs-tiles{grid-template-columns:1fr}}
</style>";
}
function SaveTokensSettings():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return admin_tracks_post("Saving Cybercrime IP Feeds parameters");
}
function intelligent_search($search):string{
    // Pentest 2026-11-04 : search etait concatene dans la requete (seul le debut etait verifie) ;
    // on ne garde que la partie validee par l'expression reguliere.
    $search=trim($search);
    if(preg_match("#^([0-9\.\*]+)(\s|$)#",$search,$re)){
        $search=$re[1];
        if(strpos($search,"*")>0){
            $search=str_replace("*","%",$search);
            return  "WHERE TEXT(ipaddr) LIKE '$search'";
        }else{
            return  "WHERE ipaddr = '$search'";
        }
    }
    if(preg_match("#^([0-9\.\/]+)(\s|$)#",$search,$re)){
        $search=$re[1];
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

    $MAX=intval($MAX);
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
        $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
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

    $DEBIAN_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION"));
    if($DEBIAN_VERSION<12) {
        $kernver = php_uname("r");
        if($kernver<>"4.19.0-27-amd64") {
        $error_last_kernel_deb10=$tpl->_ENGINE_parse_body("{error_last_kernel_deb10}");
        $error_last_kernel_deb10=str_replace("%localver",$kernver,$error_last_kernel_deb10);
        $error_last_kernel_deb10=str_replace("%lastver","4.19.0-27-amd64",$error_last_kernel_deb10);
        $js="Loadjs('fw.system.upgrade-kernel.php')";
        $button=$tpl->button_autnonome("{kernel_update}",$js, ico_cd, "AsSystemAdministrator",300,"btn-danger");
        echo $tpl->div_error("{incompatible_system}:$kernver||$error_last_kernel_deb10<div style='text-align:right'>$button</div>");
        return false;
        }
        //Loadjs('Loadjs('fw.system.upgrade-software.php?product=APP_PROFTPD')')

    }
    $jsonAVX=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API("/pfring/audit"));

    if(!$jsonAVX->Status){
        echo $tpl->div_error($jsonAVX->Error);
        $jsRefresh3=$tpl->RefreshInterval_js("ipfeeds-fixed-params",$page,"fixed-params=yes",5);
        echo "<script>$jsRefresh3</script>";
        return false;
    }
    $DEBIAN_VERSION=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DEBIAN_VERSION"));
    if($DEBIAN_VERSION>12) {
        if(!$jsonAVX->CPUHasAVX && $jsonAVX->LibraryHasAVX){
            $js="Loadjs('fw.system.upgrade-software.php?product=fw.system')";
            $button=$tpl->button_autnonome("{APP_PFRING_AVX}",$js, ico_cd, "AsSystemAdministrator",300,"btn-danger");

            echo $tpl->div_error("{incompatible_system} $DEBIAN_VERSION||{CPUNOTAVX_LIBHASAVX}<div style='text-align:right'>$button</div>");
            $jsRefresh3=$tpl->RefreshInterval_js("ipfeeds-fixed-params",$page,"fixed-params=yes",5);
            echo "<script>$jsRefresh3</script>";
            return false;

        }
    }
    $html=array();
$html[]="<table style='width:100%;margin-top:10px'>";
$html[]="<tr>";
$html[]="<td style='width:500px;min-width:500px;vertical-align:top;'>";
$html[]="<div id='articapfilter-status'></div>";
$html[]="</td>";
$html[]="<td style='width:99%;padding-left:15px;vertical-align:top;'>";
$html[]="<div id='ipfeeds-fixed-params'></div>";
$html[]="</td>";
$html[]="</tr>";
$html[]="</table>";
$html[]="<script>";
$html[]=$tpl->RefreshInterval_js("articapfilter-status",$page,"left-status=yes");
// Réglages chargés une fois (les modales les rechargent) ; seule la rangée « Right now » se rafraîchit.
$html[]="LoadAjaxSilent('ipfeeds-fixed-params','$page?fixed-params=yes');";
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
    $tpl=new template_admin();
    $m=ipf_model();
    $h=array();
    $h[]="<div class='ipf' id='ipf-root'>";
    if($m["enabled"]==0){
        $h[]=ipf_invite();
    }elseif($m["error"]!==""){
        $h[]=ipf_error($m);
    }else{
        $h[]=ipf_banner($m);
        $h[]="<div id='ipf-now'>".ipf_now($m)."</div>";
        $h[]=ipf_card_protected($m);
        $h[]=ipf_card_ban($m);
        $h[]=ipf_card_sources($m);
        $h[]=ipf_card_capture($m);
        $h[]=ipf_danger();
    }
    $h[]="</div>";
    echo ipf_style().$tpl->_ENGINE_parse_body(implode("\n",$h)).ipf_js($m).ipf_tiny($m);
    return true;
}

// Rangée « Right now » seule : rechargée toutes les 10 s par ArticaRefresh.
function ipf_now_refresh():bool{
    $tpl=new template_admin();
    $m=ipf_model();
    if($m["enabled"]==0 || $m["error"]!==""){echo "";return false;}
    echo $tpl->_ENGINE_parse_body(ipf_now($m));
    return true;
}

// Plafond réel du tampon de déduplication du démon (PFring.MbuFFer = IPBuffer.New(20000, 5 min)).
function ipf_buffer_max():int{return 20000;}

function ipf_model():array{
    $m=array("enabled"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue")),"error"=>"",
        "buffer"=>0,"scanned"=>0,"start"=>0,"version"=>"","known"=>0,"banned"=>0,"allowed"=>0,
        "pf_ver"=>"","pf_err"=>false,"pf_packets"=>0,
        "ports"=>array(),"honey"=>array(),"wnets"=>0,"blocktime"=>0,"ttl"=>0,
        "cs_on"=>0,"cs_url"=>"","psm_known"=>false,"psm_on"=>0,"psm_cats"=>array(),"psm_queries"=>0);
    if($m["enabled"]==0){return $m;}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/status"));
    if(!is_object($json)){$m["error"]="{ipf_err_noanswer}";return $m;}
    if(empty($json->Status)){
        $e=isset($json->Error)?trim((string)$json->Error):"";
        $m["error"]=$e!==""?ipf_h($e):"{ipf_err_noanswer}";
        return $m;
    }
    if(!isset($json->Config) || !is_object($json->Config)){$m["error"]="{ipf_err_protocol}";return $m;}
    $m["buffer"]=intval($json->Buffer ?? 0);
    $m["scanned"]=floatval($json->PacketsScanned ?? 0);
    $m["start"]=intval($json->StartTime ?? 0);
    $m["version"]=trim((string)($json->Version ?? ""));
    $m["known"]=intval($json->MainStorage ?? 0);
    if(isset($json->IPsets) && is_object($json->IPsets)){
        foreach($json->IPsets as $name=>$set){
            $n=0;
            if(is_object($set) && isset($set->header) && is_object($set->header)){$n=intval($set->header->numentries ?? 0);}
            if($name==="nfqueueblock"){$m["banned"]=$n;}
            if($name==="nfqueuewhite"){$m["allowed"]=$n;}
        }
    }
    $pf=(string)($json->PFRingVersion ?? "");
    if(strpos($pf,"ERR_LOADING_LIBPFRING")!==false){$m["pf_err"]=true;}
    else{$m["pf_ver"]=trim(strip_tags($pf));}
    $m["pf_packets"]=floatval($json->PFRingPackets ?? 0);

    $c=$json->Config;
    if(isset($c->LocalPorts) && is_array($c->LocalPorts)){foreach($c->LocalPorts as $p){$m["ports"][]=intval($p);}}
    if(isset($c->HoneyPotPorts) && is_array($c->HoneyPotPorts)){foreach($c->HoneyPotPorts as $p){$m["honey"][]=intval($p);}}
    $m["wnets"]=intval($c->WhiteInternalNets ?? 0);
    $m["blocktime"]=intval($c->BlockTime ?? 0);
    $m["ttl"]=intval($c->PositiveTimeSec ?? 0);
    if(isset($c->CrowdSec) && is_object($c->CrowdSec)){
        $m["cs_on"]=intval($c->CrowdSec->enabled ?? 0);
        $m["cs_url"]=trim((string)($c->CrowdSec->CrowdsecAPI ?? ""));
    }
    if(isset($c->ProtectMySite) && is_object($c->ProtectMySite)){
        $p=$c->ProtectMySite;
        $m["psm_known"]=true;
        $m["psm_on"]=intval($p->enabled ?? 0);
        $m["psm_queries"]=intval($p->queries ?? 0);
        $en=(isset($p->codes_enabled) && is_object($p->codes_enabled))?$p->codes_enabled:new stdClass();
        if(isset($p->codes) && is_object($p->codes)){
            foreach($p->codes as $code=>$cat){
                if(!empty($en->$code)){$m["psm_cats"][]=(string)$cat;}
            }
        }
    }
    return $m;
}

function ipf_h(string $s):string{
    return str_replace(array("{","}"),array("&#123;","&#125;"),htmlspecialchars($s,ENT_QUOTES,"UTF-8"));
}

// Texte d'un token, rendu brut puis formaté (%s/%d).
function ipf_t(string $key,...$args):string{
    static $cache=array();
    if(!isset($cache[$key])){
        $tpl=new template_admin();
        $cache[$key]=html_entity_decode($tpl->_ENGINE_parse_body("{".$key."}"),ENT_QUOTES,"UTF-8");
    }
    $s=preg_replace('#\[br\]|<br\s*/?>#i'," ",$cache[$key]);
    if(count($args)>0){$s=vsprintf($s,$args);}
    return trim($s);
}

function ipf_num($n):string{
    return number_format((float)$n,0,"."," ");
}

function ipf_duration(int $sec):string{
    if($sec<3600){return ipf_t("ipf_dur_min",max(1,intval($sec/60)));}
    if($sec<86400){return ipf_t("ipf_dur_hours",intval($sec/3600));}
    return ipf_t("ipf_dur_days",intval($sec/86400));
}

function ipf_minutes(int $min):string{
    $s=ipf_t("ipf_minutes",$min);
    if($min>=60 && $min%60===0){$s.=" (".ipf_t("ipf_dur_hours",intval($min/60)).")";}
    elseif($min>=60){$s.=" (".ipf_t("ipf_dur_hm",intval($min/60),$min%60).")";}
    return $s;
}

function ipf_chip(string $ico,string $text,string $cls=""):string{
    return "<span class='ipf-chip $cls'><span class='ipf-chip-i' aria-hidden='true'><i class='$ico'></i></span>".ipf_h($text)."</span>";
}

function ipf_banner(array $m):string{
    $c=array();
    $c[]=ipf_chip("fas fa-list-check",ipf_t("ipf_chip_lists"));
    if($m["blocktime"]>0){$c[]=ipf_chip("fas fa-clock",ipf_t("ipf_chip_expire",$m["blocktime"]));}
    if($m["wnets"]==1){$c[]=ipf_chip("fas fa-house",ipf_t("ipf_chip_lan_ok"));}
    else{$c[]=ipf_chip("fas fa-triangle-exclamation",ipf_t("ipf_chip_lan_warn"),"ipf-chip-warn");}
    if(count($m["honey"])>0){$c[]=ipf_chip("fas fa-bullseye",ipf_t("ipf_chip_honey"));}
    return "<div class='ipf-banner'><span class='ipf-banner-i' aria-hidden='true'><i class='fas fa-shield-halved'></i></span>"
        ."<div class='ipf-banner-b'><h2>{ipf_banner_title}</h2><p>{ipf_banner_text}</p><div class='ipf-chips'>".implode("",$c)."</div></div></div>";
}

function ipf_donut(int $v,int $max):string{
    $pct=$max>0?min(1,$v/$max):0;
    $len=2*M_PI*24;
    $dash=sprintf("%.1f %.1f",$len*$pct,$len);
    return "<span class='ipf-donut' title=\"".ipf_h(ipf_t("ipf_buffer_tip",ipf_num($v),ipf_num($max)))."\">"
        ."<svg viewBox='0 0 56 56' width='56' height='56' aria-hidden='true'><circle cx='28' cy='28' r='24' class='ipf-dn-bg'/>"
        ."<circle cx='28' cy='28' r='24' class='ipf-dn-fg' stroke-dasharray='$dash' transform='rotate(-90 28 28)'/></svg>"
        ."<span class='ipf-donut-v'>".intval(round($pct*100))."%</span></span>";
}

function ipf_kpi_screened(array $m):string{
    $since="";
    if($m["start"]>0){
        $since="<div class='ipf-kpi-sub' title=\"".date("Y-m-d H:i:s",$m["start"])."\">".ipf_h(ipf_t("ipf_scanned_since",ipf_num($m["scanned"]),ipf_duration(max(0,time()-$m["start"]))))."</div>";
    }
    return "<div class='ipf-kpi'><div class='ipf-kpi-h'><span class='ipf-kpi-l'>{ipf_kpi_screened}</span>".ipf_donut($m["buffer"],ipf_buffer_max())."</div>"
        ."<div class='ipf-kpi-n'>".ipf_num($m["buffer"])." <small>/ ".ipf_num(ipf_buffer_max())."</small></div>"
        ."<p class='ipf-help'>{ipf_kpi_screened_help}</p>$since</div>";
}

function ipf_kpi_banned(array $m):string{
    $page=CurrentPageName();
    if($m["banned"]>0){
        $n="<div class='ipf-kpi-n ipf-red'><span class='ipf-kpi-ico' aria-hidden='true'><i class='fas fa-ban'></i></span>".ipf_num($m["banned"])."</div>";
    }else{
        $n="<div class='ipf-kpi-n ipf-mute'><span class='ipf-kpi-ico' aria-hidden='true'><i class='fas fa-ban'></i></span>0 <small>{ipf_nobody_banned}</small></div>";
    }
    return "<div class='ipf-kpi'><div class='ipf-kpi-h'><span class='ipf-kpi-l'>{ipf_kpi_banned}</span></div>$n"
        ."<p class='ipf-help'>{ipf_kpi_banned_help}</p><div class='ipf-links'>"
        ."<button type='button' class='ipf-link' OnClick=\"IpfTab('blacklisted=yes')\">{ipf_see_list} <i class='fas fa-arrow-right' aria-hidden='true'></i></button>"
        ."<button type='button' class='ipf-link' OnClick=\"Loadjs('$page?banip=yes')\">{ban_ips}</button></div></div>";
}

function ipf_kpi_allowed(array $m):string{
    return "<div class='ipf-kpi'><div class='ipf-kpi-h'><span class='ipf-kpi-l'>{ipf_kpi_allowed}</span></div>"
        ."<div class='ipf-kpi-n ipf-acc'><span class='ipf-kpi-ico' aria-hidden='true'><i class='fas fa-circle-check'></i></span>".ipf_num($m["allowed"])."</div>"
        ."<p class='ipf-help'>{ipf_kpi_allowed_help}</p><div class='ipf-links'>"
        ."<button type='button' class='ipf-link' OnClick=\"IpfTab('whitelists=yes')\">{ipf_manage_white} <i class='fas fa-arrow-right' aria-hidden='true'></i></button></div></div>";
}

function ipf_now_line(array $m):string{
    $p=array();
    $p[]=ipf_h(ipf_t("ipf_known",ipf_num($m["known"])));
    if($m["version"]!==""){$p[]=ipf_h(ipf_t("ipf_engine",$m["version"]));}
    if($m["start"]>0){$p[]="<span title=\"".date("Y-m-d H:i:s",$m["start"])."\">".ipf_h(ipf_t("ipf_running_for",ipf_duration(max(0,time()-$m["start"]))))."</span>";}
    return "<div class='ipf-line'><span aria-hidden='true'><i class='fas fa-database'></i></span> ".implode(" &middot; ",$p)."</div>";
}

function ipf_now(array $m):string{
    return "<div class='ipf-kpis'>".ipf_kpi_screened($m).ipf_kpi_banned($m).ipf_kpi_allowed($m)."</div>".ipf_now_line($m);
}

function ipf_card(string $ico,string $title,string $intro,string $body):string{
    return "<div class='ipf-card'><div class='ipf-card-h'><span class='ipf-card-i' aria-hidden='true'><i class='$ico'></i></span><h3>$title</h3></div>"
        ."<p class='ipf-card-intro'>$intro</p><div class='ipf-rows'>$body</div></div>";
}

function ipf_row(string $label,string $value,string $help,string $js,string $btnKey="ipf_edit",string $btnIco="fa-pen"):string{
    $btn="";
    if($js!==""){
        $btn="<button type='button' class='ipf-edit' OnClick=\"$js\" aria-label=\"".ipf_h(ipf_t($btnKey)." — ".strip_tags(ipf_t(trim($label,"{}"))))."\"><i class='fas $btnIco' aria-hidden='true'></i> {".$btnKey."}</button>";
    }
    return "<div class='ipf-row'><div class='ipf-row-b'><div class='ipf-row-l'>$label</div><div class='ipf-row-v'>$value</div><p class='ipf-help'>$help</p></div>$btn</div>";
}

function ipf_ports(array $ports,string $cls):string{
    $o=array();
    foreach($ports as $p){$o[]="<span class='ipf-port $cls'>".intval($p)."</span>";}
    return implode("",$o);
}

function ipf_state(bool $on,string $onTxt,string $offTxt,bool $offWarn=false):string{
    if($on){return "<span class='ipf-st ipf-st-on'><i class='fas fa-check' aria-hidden='true'></i> $onTxt</span>";}
    $cls=$offWarn?"ipf-st-warn":"ipf-st-off";
    $ico=$offWarn?"fa-triangle-exclamation":"fa-power-off";
    return "<span class='ipf-st $cls'><i class='fas $ico' aria-hidden='true'></i> $offTxt</span>";
}

function ipf_card_protected(array $m):string{
    $page=CurrentPageName();
    $edit="Loadjs('$page?main-params=yes')";
    if(count($m["ports"])>0){$v=ipf_ports($m["ports"],"");}
    else{$v="<span class='ipf-st ipf-st-warn'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {ipf_no_port}</span>";}
    $r=array();
    $r[]=ipf_row("{ipf_ports}",$v,"{ipf_ports_help}",$edit);
    $hv=count($m["honey"])>0?ipf_ports($m["honey"],"ipf-port-trap"):"<span class='ipf-st ipf-st-off'>{ipf_not_used}</span>";
    $r[]=ipf_row("{ipf_honey}",$hv,"{ipf_honey_help}","Loadjs('$page?Honeypot-js=yes')");
    $r[]=ipf_row("{ipf_lan}",ipf_state($m["wnets"]==1,"{ipf_lan_on}","{ipf_lan_off}",true),"{ipf_lan_help}",$edit);
    return ipf_card("fas fa-door-open","{ipf_protected_title}","{ipf_protected_intro}",implode("",$r));
}

function ipf_card_ban(array $m):string{
    $page=CurrentPageName();
    $edit="Loadjs('$page?main-params=yes')";
    $r=array();
    $r[]=ipf_row("{ipf_bantime}","<span class='ipf-val'>".ipf_h(ipf_minutes($m["blocktime"]))."</span>","{ipf_bantime_help}",$edit);
    $r[]=ipf_row("{ipf_ttl}","<span class='ipf-val'>".ipf_h(ipf_t("ipf_seconds",$m["ttl"]))."</span> <span class='ipf-tech'>{POSITIVE_CACHE_TTL}</span>","{ipf_ttl_help}",$edit);
    return ipf_card("fas fa-hourglass-half","{ipf_ban_title}","{ipf_ban_intro}",implode("",$r));
}

// Libellé courant d'une catégorie ProtectMySite ; une catégorie inconnue garde son nom brut.
function ipf_psm_label(string $cat):string{
    $k="ipf_cat_".strtolower(preg_replace('/[^A-Za-z0-9]/','',$cat));
    $t=ipf_t($k);
    if($t==="" || $t===$k || strpos($t,"{")!==false){return ipf_h($cat);}
    return ipf_h($t);
}

function ipf_card_sources(array $m):string{
    $page=CurrentPageName();
    $r=array();
    if($m["psm_known"]){
        $v=ipf_state($m["psm_on"]==1,"{ipf_on}","{ipf_off}");
        $extra="";
        if($m["psm_on"]==1){
            $v.=" <span class='ipf-tech'>".ipf_h(ipf_t("ipf_lookups",ipf_num($m["psm_queries"])))."</span>";
            if(count($m["psm_cats"])==0){
                $extra="<div class='ipf-warnline'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {ipf_psm_nocat}</div>";
            }else{
                $cc=array();
                foreach($m["psm_cats"] as $cat){$cc[]="<span class='ipf-cat'><b>".ipf_psm_label($cat)."</b><small>".ipf_h($cat)."</small></span>";}
                $extra="<div class='ipf-cats'>".implode("",$cc)."</div>";
            }
        }
        $r[]=ipf_row("{ipf_psm}",$v.$extra,"{ipf_psm_help}","Loadjs('$page?ProtectMySite=yes')");
    }
    $cv=ipf_state($m["cs_on"]==1,"{ipf_on}","{ipf_off}");
    if($m["cs_on"]==1 && $m["cs_url"]!==""){$cv.=" <span class='ipf-mono'>".ipf_h($m["cs_url"])."</span>";}
    $r[]=ipf_row("{APP_CROWDSEC}",$cv,"{ipf_cs_help}","");
    $body=implode("",$r)."<div class='ipf-card-f'><button type='button' class='ipf-link' OnClick=\"IpfTab('sources=yes')\">{ipf_manage_lists} <i class='fas fa-arrow-right' aria-hidden='true'></i></button></div>";
    return ipf_card("fas fa-satellite-dish","{ipf_sources_title}","{ipf_sources_intro}",$body);
}

function ipf_card_capture(array $m):string{
    if($m["pf_err"]){
        $v="<span class='ipf-st ipf-st-err'><i class='fas fa-circle-xmark' aria-hidden='true'></i> {ERR_LOADING_LIBPFRING}</span>";
    }else{
        $ver=$m["pf_ver"]!==""?ipf_h($m["pf_ver"]):"&mdash;";
        $v="<span class='ipf-val'>$ver</span> <span class='ipf-tech'>{PFRING}</span>";
    }
    $v.="<div class='ipf-tech'>".ipf_h(ipf_t("ipf_pf_packets",ipf_num($m["pf_packets"])))."</div>";
    $r=ipf_row("{ipf_capture}",$v,"{ipf_capture_help}","Loadjs('fw.ids.dashboard.php?pf-ring-infos=yes')","ipf_details","fa-circle-info");
    return "<div class='ipf-compact'>".ipf_card("fas fa-microchip","{ipf_capture_title}","{ipf_capture_intro}",$r)."</div>";
}

function ipf_danger():string{
    $page=CurrentPageName();
    return "<div class='ipf-danger'><div class='ipf-danger-b'><h3><i class='fas fa-trash' aria-hidden='true'></i> {ipf_remove_title}</h3><p>{ipf_remove_text}</p></div>"
        ."<button type='button' class='ipf-btn-danger' OnClick=\"Loadjs('$page?cyberipcrime-disable=yes')\">{remove_this_section}</button></div>";
}

function ipf_invite():string{
    $page=CurrentPageName();
    return "<div class='ipf-invite'><span class='ipf-invite-i' aria-hidden='true'><i class='fas fa-shield-halved'></i></span>"
        ."<h2>{ipf_invite_title}</h2><p>{ipf_invite_text}</p>"
        ."<button type='button' class='ipf-btn' OnClick=\"Loadjs('$page?cyberipcrime-enable=yes')\"><i class='fas fa-download' aria-hidden='true'></i> {install_feature}</button></div>";
}

function ipf_error(array $m):string{
    return "<div class='ipf-errbox' role='alert'><span class='ipf-errbox-i' aria-hidden='true'><i class='fas fa-circle-xmark'></i></span>"
        ."<div><b>{ipf_err_title}</b><p>{$m["error"]}</p><p class='ipf-help'>{ipf_err_hint}</p></div></div>";
}

function ipf_tiny(array $m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    if($m["enabled"]==0){
        $topbuttons[]=array("Loadjs('$page?cyberipcrime-enable=yes')",ico_cd,"{install_feature}");
    }else{
        $topbuttons[]=array("Loadjs('$page?banip=yes')",ico_plus,"{ban_ips}");
        $topbuttons[]=array("Loadjs('$page?checkip=yes')",ico_loupe,"{check_ip_reputation}");
        $topbuttons[]=array("Loadjs('$page?cyberipcrime-disable=yes')",ico_trash,"{remove_this_section}");
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{CybercrimeIPFeeds}";
    $TINY_ARRAY["ICO"]="fas fa-hockey-mask";
    $TINY_ARRAY["EXPL"]="{ipf_header_explain}";
    $TINY_ARRAY["URL"]="Cybercrime-IP-Feeds";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

function ipf_js(array $m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $j=array();
    $j[]="<script>";
    // Ouvre un autre onglet de la page (lien d'une carte) : clic sur l'onglet dont data-url porte la clé.
    $j[]="window.IpfTab=function(key){var r=document.getElementById('ipf-root');var c=r?$(r).closest('.tabs-container'):$();";
    $j[]="var a=(c.length?c:$(document)).find('ul.nav-tabs a[data-url*=\"'+key+'\"]').first();if(a.length){a.trigger('click');}};";
    if($m["enabled"]==1 && $m["error"]===""){
        $j[]=$tpl->RefreshInterval_js("ipf-now",$page,"ipf-now=yes",10);
    }
    $j[]="</script>";
    return implode("",$j);
}

function ipf_style():string{
    $acc="#005447";$acch="#003d33";
    if(function_exists("brand_ui_accents") && function_exists("brand_palette")){
        $a=brand_ui_accents(brand_palette());
        if(is_array($a)){
            if(!empty($a["accent"])){$acc=$a["accent"];}
            if(!empty($a["accent_dark"])){$acch=$a["accent_dark"];}
        }
    }
    return "<style>
.ipf{--ipf-acc:$acc;--ipf-acch:$acch;--ipf-soft:#e6eeed;--ipf-bd:#bfd4d1;color:#0f172a;font-size:14px;animation:ipfIn .15s ease-in-out}
@keyframes ipfIn{from{opacity:0}to{opacity:1}}
@media (prefers-reduced-motion:reduce){.ipf{animation:none}}
.ipf *{box-sizing:border-box}
.ipf-banner{display:flex;gap:16px;background:#f2f6f6;border:1px solid var(--ipf-bd);border-radius:12px;padding:20px 22px;margin-bottom:16px}
.ipf-banner-i{flex:0 0 48px;width:48px;height:48px;border-radius:50%;background:var(--ipf-soft);color:var(--ipf-acc);display:flex;align-items:center;justify-content:center;font-size:22px}
.ipf-banner-i svg{width:22px;height:22px}
.ipf-banner h2{margin:0 0 6px;font-size:18px;font-weight:700;color:#0f172a}
.ipf-banner p{margin:0 0 12px;color:#334155;font-size:14px;line-height:1.5}
.ipf-chips{display:flex;flex-wrap:wrap;gap:8px}
.ipf-chip{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid var(--ipf-bd);color:var(--ipf-acc);border-radius:999px;padding:4px 12px;font-size:13px;font-weight:600}
.ipf-chip-i{display:inline-flex;width:14px;height:14px;align-items:center;justify-content:center}
.ipf-chip-i svg{width:13px;height:13px}
.ipf-chip-warn{background:#fffbeb;border-color:#fcd34d;color:#b45309}
.ipf-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.ipf-kpi{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px;box-shadow:0 1px 2px rgba(15,23,42,.06);min-height:120px}
.ipf-kpi-h{display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
.ipf-kpi-l{font-size:13px;font-weight:600;color:#475569}
.ipf-kpi-n{font-size:28px;font-weight:700;line-height:1.2;margin:4px 0;display:flex;align-items:center;gap:8px}
.ipf-kpi-n small{font-size:13px;font-weight:500;color:#64748b}
.ipf-kpi-ico{display:inline-flex;width:22px;height:22px;align-items:center;justify-content:center;font-size:20px}
.ipf-kpi-ico svg{width:20px;height:20px}
.ipf-kpi-sub{font-size:12px;color:#64748b;margin-top:6px}
.ipf-red{color:#b91c1c}.ipf-mute{color:#475569}.ipf-acc{color:var(--ipf-acc)}
.ipf-donut{position:relative;display:inline-block;width:56px;height:56px;flex:0 0 56px}
.ipf-dn-bg{fill:none;stroke:var(--ipf-soft);stroke-width:7}
.ipf-dn-fg{fill:none;stroke:var(--ipf-acc);stroke-width:7;stroke-linecap:round}
.ipf-donut-v{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:var(--ipf-acc)}
.ipf-help{margin:4px 0 0;font-size:12.5px;color:#64748b;line-height:1.45}
.ipf-links{display:flex;flex-wrap:wrap;gap:14px;margin-top:10px}
.ipf-link{background:none;border:0;padding:0;color:var(--ipf-acc);font-weight:600;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:5px}
.ipf-link:hover{color:var(--ipf-acch);text-decoration:underline}
.ipf-link svg{width:11px;height:11px}
.ipf-line{margin:10px 2px 18px;font-size:13px;color:#64748b}
.ipf-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 1px 2px rgba(15,23,42,.06);margin-bottom:16px}
.ipf-card-h{display:flex;align-items:center;gap:10px;padding:16px 18px 0}
.ipf-card-i{display:inline-flex;width:30px;height:30px;border-radius:8px;background:var(--ipf-soft);color:var(--ipf-acc);align-items:center;justify-content:center}
.ipf-card-i svg{width:15px;height:15px}
.ipf-card h3{margin:0;font-size:16px;font-weight:600;color:#0f172a}
.ipf-card-intro{margin:6px 18px 4px 58px;font-size:13px;color:#64748b}
.ipf-rows{padding:0 18px 6px}
.ipf-row{display:flex;gap:16px;align-items:flex-start;padding:14px 0;border-top:1px solid #f1f5f9}
.ipf-row:first-child{border-top:0}
.ipf-row-b{flex:1;min-width:0}
.ipf-row-l{font-size:14px;font-weight:600;margin-bottom:6px}
.ipf-row-v{display:flex;flex-wrap:wrap;align-items:center;gap:6px}
.ipf-val{font-weight:600}
.ipf-tech{font-size:12px;color:#94a3b8}
.ipf-mono,.ipf-port{font-family:'JetBrains Mono',Consolas,monospace}
.ipf-mono{font-size:12.5px;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:1px 8px;word-break:break-all}
.ipf-port{display:inline-block;min-width:38px;text-align:center;font-size:13px;font-weight:600;padding:2px 10px;border-radius:6px;background:var(--ipf-soft);color:var(--ipf-acc);border:1px solid var(--ipf-bd)}
.ipf-port-trap{background:#fff;color:#b91c1c;border-color:#fca5a5}
.ipf-edit{flex:0 0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:6px 12px;font-size:13px;font-weight:600;color:var(--ipf-acc);cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.ipf-edit:hover{border-color:var(--ipf-bd);background:#f2f6f6;color:var(--ipf-acch)}
.ipf-edit svg{width:12px;height:12px}
.ipf button:focus-visible{outline:2px solid var(--ipf-acc);outline-offset:2px}
.ipf-st{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;padding:3px 10px;border-radius:999px}
.ipf-st svg{width:12px;height:12px}
.ipf-st-on{background:#f0fdf4;color:#15803d}
.ipf-st-off{background:#f1f5f9;color:#475569}
.ipf-st-warn{background:#fffbeb;color:#b45309}
.ipf-st-err{background:#fef2f2;color:#b91c1c}
.ipf-warnline{width:100%;margin-top:8px;font-size:13px;color:#b45309}
.ipf-warnline svg{width:12px;height:12px}
.ipf-cats{width:100%;display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
.ipf-cat{display:inline-flex;flex-direction:column;border:1px solid #e2e8f0;border-radius:8px;padding:5px 10px;background:#f8fafc}
.ipf-cat b{font-size:13px;font-weight:600}
.ipf-cat small{font-size:11.5px;color:#94a3b8}
.ipf-card-f{border-top:1px solid #f1f5f9;padding:12px 0 8px}
.ipf-compact .ipf-card{background:#fafbfc}
.ipf-danger{display:flex;gap:16px;align-items:center;justify-content:space-between;border:1px solid #fecaca;background:#fef2f2;border-radius:10px;padding:16px 18px;margin-bottom:8px}
.ipf-danger h3{margin:0 0 4px;font-size:15px;font-weight:600;color:#991b1b;display:flex;align-items:center;gap:8px}
.ipf-danger h3 svg{width:14px;height:14px}
.ipf-danger p{margin:0;font-size:13px;color:#7f1d1d}
.ipf-btn-danger{flex:0 0 auto;background:#fff;border:1px solid #f87171;color:#b91c1c;border-radius:8px;padding:8px 16px;font-weight:600;font-size:13px;cursor:pointer}
.ipf-btn-danger:hover{background:#b91c1c;color:#fff}
.ipf-invite{text-align:center;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:40px 32px}
.ipf-invite-i{display:inline-flex;width:84px;height:84px;border-radius:50%;background:var(--ipf-soft);color:var(--ipf-acc);align-items:center;justify-content:center}
.ipf-invite-i svg{width:40px;height:40px}
.ipf-invite h2{font-size:20px;font-weight:700;margin:16px 0 8px}
.ipf-invite p{max-width:560px;margin:0 auto 20px;color:#475569;line-height:1.5}
.ipf-btn{background:var(--ipf-acc);color:#fff;border:0;border-radius:8px;padding:10px 20px;font-weight:600;font-size:14px;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
.ipf-btn:hover{background:var(--ipf-acch)}
.ipf-btn svg{width:14px;height:14px}
.ipf-errbox{display:flex;gap:14px;border:1px solid #fecaca;background:#fef2f2;border-radius:10px;padding:16px 18px;color:#7f1d1d}
.ipf-errbox b{color:#991b1b}
.ipf-errbox p{margin:4px 0 0}
.ipf-errbox-i{display:inline-flex;width:36px;height:36px;flex:0 0 36px;border-radius:50%;background:#fee2e2;color:#b91c1c;align-items:center;justify-content:center}
.ipf-errbox-i svg{width:18px;height:18px}
@media (max-width:1200px){.ipf-kpis{grid-template-columns:1fr}.ipf-danger{flex-direction:column;align-items:flex-start}}
</style>";
}
function category_refresh():bool{
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
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
    header("content-type: application/x-javascript");
    $category=rawurlencode((string)$_GET["category-enable"]);
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
    $after=$function!==""?"$function();":"";

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
        if(strtolower($categoryname)<>strtolower(rawurldecode($category))) {
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
            echo $after;
            return admin_tracks("Disable Cybercrime IP Feeds source ".rawurldecode($category));
        }


        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/source/set/$category/enabled/1"));
        if(!$json->Status){
            echo $tpl->js_error($json->Error);
            return false;
        }
        echo $after;
        return admin_tracks("Enable Cybercrime IP Feeds source ".rawurldecode($category));
    }


    return  $tpl->js_error("No source found");


}
function category_delete_ask():bool{
    $tpl=new template_admin();
    $category=$_GET["category-delete"];
    $md=$_GET["md"];
    // Pentest 2026-11-04 : category-delete etait recopie sans encodage dans la page (XSS).
    return $tpl->js_confirm_delete(req_html($category),"category-delete",$category,"$('#$md').remove()");
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
        // Pentest 2026-11-04 : category etait recopie sans encodage dans la page (XSS).
        // Le titre est decode (html_entity_decode) puis rendu en HTML : double encodage.
        $title="{category}: ".req_html(req_html($category));
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
    // Pentest 2026-11-04 : AbuseIPDB-js etait recopie sans encodage dans la page (XSS).
    return $tpl->js_dialog1(req_html(req_html($category)), "$page?AbuseIPDB-popup=".req_url($category)."&function=$function");
}
function Honeypot_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog1("Honeypot", "$page?Honeypot-popup=yes");
}

function AbuseIPDB_popup():bool{
    $tpl=new template_admin();
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
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
    $function=preg_replace('/[^A-Za-z0-9_]/','',(string)($_GET["function"] ?? ""));
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
        return true;
    }

    header("content-type: application/x-javascript");


    $CrowdSecCyberCrimeIPfeed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrowdSecCyberCrimeIPfeed"));
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
function left_status():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $t=time();
    $errorFile="/usr/share/artica-postfix/ressources/logs/NFQueue/error.txt";
    if(is_file($errorFile)) {
        $NFQUEUE_ERROR = @file_get_contents($errorFile);
        if (strlen($NFQUEUE_ERROR)) {
            $tb = explode("||", $NFQUEUE_ERROR);
            echo $tpl->_ENGINE_parse_body($tpl->div_error($tb[0] . "||" . $tb[1]));
        }
    }
    $html[]="<div id='pcap-here-$t'>";
    $html[]=left_status_pcap();
    $html[]="</div>";
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

    $jsInstall=$tpl->framework_buildjs(
        "/nfqueue/uninstall",
        "nfqueue.progress",
        "nfqueue.progress.log",
        "progress-CybercrimeIPFeeds-restart"
    );
    header("content-type: application/x-javascript");


   return $tpl->js_confirm_delete("{CybercrimeIPFeeds}","cyberipcrime-disable","yes",$jsInstall);

}
function CybercrimeIPFeeds_disable_confirm():bool{
    return admin_tracks("Remove CybercrimeIPFeeds feature");
}
function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();

    $EnableFireholIPSetsLogs=intval($_POST["EnableFireholIPSetsLogs"]);
    $resp=fwapi_send("PUT","rules/ipfeed",array(
        "enabled"=>intval($_POST["EnableFireholIPSets"])==1?1:0,
        "jlog"=>$EnableFireholIPSetsLogs,
    ));
    $e=fwapi_error($resp);
    if($e!=""){echo $tpl->post_error($e);return;}
    // Liens d'objets des règles supprimées : acls.db appartient à l'API ACL.
    $data=fwapi_data($resp);
    foreach(($data["deleted"] ?? array()) as $ID){
        acls_api_send("DELETE","/proxy/acls/sqlite/links/by-aclid?table=firewallfilter_sqacllinks&aclid=".intval($ID));
    }


}
function whitelists_start():bool{
    $tpl=new template_admin();
    $p=ipfw_params();
    $en=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue"))==1;
    echo ipfw_style()."<div class='ipf ipfs ipfb ipfw' id='ipfw-root'>"
        .$tpl->_ENGINE_parse_body(ipfw_banner($en?ipfw_lan_setting():-1).($en?ipfw_panel($p):""))
        ."<div id='ipfw-host'>".$tpl->_ENGINE_parse_body(ipfw_render(ipfw_model($p)))."</div></div>".ipfw_js();
    return true;
}

function ipfw_list():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(ipfw_render(ipfw_model(ipfw_params())));
    return true;
}

function ipfw_params():array{
    $raw=trim((string)($_GET["q"] ?? ""));
    $f=(string)($_GET["f"] ?? "all");
    if(!in_array($f,array("all","you","sys"),true)){$f="all";}
    // Filtré ici (le démon reçoit "nil") : une adresse ou un mot de la note, en texte libre.
    $raw=trim(preg_replace('#[\x00-\x1F\x7F]+#u',' ',$raw));
    $raw=mb_substr($raw,0,64);
    $p=array("q"=>$raw,"raw"=>$raw,"invalid"=>false,"f"=>$f,"max"=>1000);
    return $p;
}

// Réglage « Internal networks always allowed » (onglet Parameters) : 1, 0, ou -1 si illisible.
function ipfw_lan_setting():int{
    $st=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/status"));
    if(is_object($st) && !empty($st->Status) && isset($st->Config) && is_object($st->Config)){
        return intval($st->Config->WhiteInternalNets ?? 0)==1?1:0;
    }
    return -1;
}

function ipfw_norm(string $ip):string{
    $ip=strtolower(trim($ip));
    if(substr($ip,-3)==="/32" && strpos($ip,":")===false){$ip=substr($ip,0,-3);}
    if(substr($ip,-4)==="/128"){$ip=substr($ip,0,-4);}
    return $ip;
}

// Entrées que le démon réinjecte à chaque démarrage (LoadTrustedNets). Un démon récent les
// renvoie lui-même dans "System" (origine nets / update / default) ; sinon on les déduit ici.
function ipfw_system_set($json=null):array{
    if(is_object($json) && isset($json->System) && is_object($json->System)){
        $map=array();
        foreach($json->System as $ip=>$origin){
            $o=(string)$origin;
            if(!in_array($o,array("nets","update","default"),true)){$o="nets";}
            $map[ipfw_norm((string)$ip)]=$o;
        }
        return $map;
    }
    $out=array();
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT ipaddr FROM networks_infos WHERE enabled=1 AND trusted=1");
    if(is_array($results)){
        foreach($results as $l){
            $n=ipfw_norm(str_replace("/255.255.255.0","/24",(string)($l["ipaddr"] ?? "")));
            if($n!=="" && $n!=="0.0.0.0/0.0.0.0"){$out[$n]="nets";}
        }
    }
    // Sans réseau de confiance, le démon prend les plages privées par défaut.
    if(count($out)==0){foreach(array("10.0.0.0/8","172.16.0.0/12","192.168.0.0/16") as $n){$out[$n]="default";}}
    $ips=@gethostbynamel("update.artica.cloud");
    if(is_array($ips)){foreach($ips as $ip){$out[ipfw_norm($ip)]="update";}}
    return $out;
}

function ipfw_model(array $p):array{
    $m=array("enabled"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue")),"error"=>"","p"=>$p,
        "rows"=>array(),"total"=>0,"mine"=>0,"sys"=>0,"matched"=>0,"has_meta"=>false);
    if($m["enabled"]==0 || $p["invalid"]){return $m;}
    // Liste entière : les compteurs et l'origine se calculent sur tout ; la recherche filtre ensuite.
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/whitelisted/nil"));
    if(!is_object($json)){$m["error"]="{ipf_err_noanswer}";return $m;}
    if(empty($json->Status)){
        $e=isset($json->Error)?trim((string)$json->Error):"";
        $m["error"]=$e!==""?ipf_h($e):"{ipf_err_noanswer}";
        return $m;
    }
    $sys=ipfw_system_set($json);
    // Date, auteur et note de chaque entrée : démon du 2026-11-21 et suivants (champ Meta).
    $meta=array();
    if(isset($json->Meta) && is_object($json->Meta)){
        foreach($json->Meta as $k=>$v){
            if(!is_object($v)){continue;}
            $meta[ipfw_norm((string)$k)]=array("added"=>intval($v->added ?? 0),"by"=>trim((string)($v->by ?? "")),"comment"=>trim((string)($v->comment ?? "")));
        }
        $m["has_meta"]=true;
    }
    $items=(isset($json->Items) && is_array($json->Items))?$json->Items:array();
    $q=strtolower($p["q"]);
    foreach($items as $it){
        if(!is_object($it)){continue;}
        $ip=trim((string)($it->elem ?? ""));
        if($ip===""){continue;}
        $n=ipfw_norm($ip);
        $origin=isset($sys[$n])?$sys[$n]:"you";
        $m["total"]++;
        if($origin==="you"){$m["mine"]++;}else{$m["sys"]++;}
        $mt=isset($meta[$n])?$meta[$n]:array("added"=>0,"by"=>"","comment"=>"");
        // La recherche porte aussi sur la note (« head office » retrouve l'adresse du siège).
        if($q!=="" && strpos(strtolower($ip),$q)!==0 && ($mt["comment"]==="" || stripos($mt["comment"],$p["q"])===false)){continue;}
        if($p["f"]==="you" && $origin!=="you"){continue;}
        if($p["f"]==="sys" && $origin==="you"){continue;}
        $m["matched"]++;
        if(count($m["rows"])<$p["max"]){$m["rows"][]=array("ip"=>$ip,"origin"=>$origin,"meta"=>$mt);}
    }
    return $m;
}

function ipfw_render(array $m):string{
    $h=array();
    if($m["enabled"]==0){
        $h[]=ipfw_invite_install();
    }elseif($m["p"]["invalid"]){
        $h[]=ipfb_invalid();
    }elseif($m["error"]!==""){
        $h[]=ipfw_error($m);
    }else{
        $h[]=ipfw_count($m);
        if($m["mine"]==0 && $m["p"]["q"]==="" && $m["p"]["f"]!=="sys"){$h[]=ipfw_empty();}
        if(count($m["rows"])==0 && ($m["p"]["q"]!=="" || $m["p"]["f"]!=="all")){$h[]=ipfw_no_match($m);}
        if(count($m["rows"])>0){$h[]=ipfw_table($m);}
        $h[]=ipfw_help();
    }
    $h[]=ipfw_tiny($m);
    return implode("\n",$h);
}

function ipfw_banner(int $lan):string{
    $page=CurrentPageName();
    $c=array(ipf_chip("fas fa-shield-halved",ipf_t("ipfw_chip_wins")),ipf_chip("fas fa-infinity",ipf_t("ipfw_chip_never")));
    if($lan==1){$c[]=ipf_chip("fas fa-house",ipf_t("ipfw_chip_lan_on"));}
    elseif($lan==0){$c[]="<span class='ipf-chip ipfw-chip-off'><span class='ipf-chip-i' aria-hidden='true'><i class='fas fa-house'></i></span>".ipf_h(ipf_t("ipfw_chip_lan_off"))
        ." <button type='button' class='ipf-link' OnClick=\"IpfWParams()\">".ipf_h(ipf_t("ipfw_change"))."</button></span>";}
    return "<div class='ipf-banner'><span class='ipf-banner-i' aria-hidden='true'><i class='fas fa-circle-check'></i></span>"
        ."<div class='ipf-banner-b'><h2>{ipfw_banner_title} <small class='ipfb-sub'>{whitelists}</small></h2><p>{ipfw_banner_text}</p><div class='ipf-chips'>".implode("",$c)."</div></div></div>";
}

function ipfw_panel(array $p):string{
    $segs=array("all"=>"{ipfs_f_all}","you"=>"{ipfs_added}","sys"=>"{ipfw_f_sys}");
    $b=array();
    foreach($segs as $k=>$l){
        $sel=$k===$p["f"]?"true":"false";
        $b[]="<button type='button' role='radio' aria-checked='$sel' class='ipfs-seg' data-f='$k' OnClick=\"IpfWFilter('$k')\">$l</button>";
    }
    return "<div class='ipfb-panel ipfw-panel'>"
        ."<div class='ipfw-add'><div class='ipfw-pt'>{ipfw_add_title}</div><div class='ipfb-bar'><label class='ipfb-q'><span class='ipfb-q-i' aria-hidden='true'><i class='fas fa-plus'></i></span>"
        ."<input type='text' id='ipfw-new' maxlength='1000' autocomplete='off' spellcheck='false' placeholder=\"".ipf_h(ipf_t("ipfw_add_ph"))."\" aria-label=\"".ipf_h(ipf_t("ipfw_add_title"))."\" aria-describedby='ipfw-add-help' OnKeyDown=\"IpfWAddKey(event)\"></label>"
        ."<button type='button' class='ipf-btn' id='ipfw-add-btn' OnClick=\"IpfWAdd()\"><i class='fas fa-plus' aria-hidden='true'></i> {ipfw_allow}</button></div>"
        ."<input type='text' id='ipfw-note' class='ipfw-note-in' maxlength='200' autocomplete='off' placeholder=\"".ipf_h(ipf_t("ipfw_note_ph"))."\" aria-label=\"".ipf_h(ipf_t("ipfw_note_label"))."\" OnKeyDown=\"IpfWAddKey(event)\">"
        ."<p class='ipf-help' id='ipfw-add-help'>{ipfw_add_help} {ipfw_note_help}</p><div class='ipfw-add-err' role='alert' hidden></div></div>"
        ."<div class='ipfw-find'><div class='ipfw-pt'>{ipfw_find_title}</div><label class='ipfb-q'><span class='ipfb-q-i' aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span>"
        ."<input type='search' id='ipfw-q' value=\"".ipf_h($p["raw"])."\" maxlength='64' autocomplete='off' spellcheck='false' placeholder=\"".ipf_h(ipf_t("ipfw_find_ph"))."\" aria-label=\"".ipf_h(ipf_t("ipfw_find_ph"))."\" OnInput=\"IpfWInput()\" OnKeyDown=\"IpfWKey(event)\"></label>"
        ."<div class='ipfs-segs' role='radiogroup' aria-label=\"".ipf_h(ipf_t("ipfw_find_title"))."\">".implode("",$b)."</div></div></div>";
}

function ipfw_count(array $m):string{
    $t=ipf_t("ipfw_count",ipf_num($m["total"]),ipf_num($m["mine"]));
    if($m["p"]["q"]!=="" || $m["p"]["f"]!=="all"){$t.=" · ".ipf_t("ipfw_shown",ipf_num($m["matched"]));}
    return "<div class='ipfb-count' role='status'>".ipf_h($t)."</div>";
}

function ipfw_origin_tag(string $o):string{
    if($o==="nets"){return "<span class='ipfs-tag' title=\"".ipf_h(ipf_t("ipfw_sys_tip"))."\"><i class='fas fa-network-wired' aria-hidden='true'></i> {ipfw_o_nets}</span>";}
    if($o==="default"){return "<span class='ipfs-tag' title=\"".ipf_h(ipf_t("ipfw_sys_tip"))."\"><i class='fas fa-house' aria-hidden='true'></i> {ipfw_o_default}</span>";}
    if($o==="update"){return "<span class='ipfs-tag' title=\"".ipf_h(ipf_t("ipfw_sys_tip"))."\"><i class='fas fa-cloud-arrow-down' aria-hidden='true'></i> {ipfw_o_update}</span>";}
    return "<span class='ipfs-tag ipfs-tag-you'><i class='fas fa-user-gear' aria-hidden='true'></i> {ipfs_added}</span>";
}

// Ligne « Added 2 h ago by admin » + note (ou invitation à en écrire une).
function ipfw_meta_html(array $r,string $id,bool $hasMeta):string{
    $mt=isset($r["meta"])?$r["meta"]:array("added"=>0,"by"=>"","comment"=>"");
    $parts=array();
    if($mt["added"]>0){
        $who=$mt["by"]!==""?ipf_t("ipfw_added_by",ipfs_ago($mt["added"],time()),$mt["by"]):ipf_t("ipfw_added",ipfs_ago($mt["added"],time()));
        $parts[]="<span title=\"".date("Y-m-d H:i:s",$mt["added"])."\">".ipf_h($who)."</span>";
    }
    $note="";
    if($mt["comment"]!==""){$note="<span class='ipfw-note'><i class='fas fa-note-sticky' aria-hidden='true'></i> ".ipf_h($mt["comment"])."</span>";}
    $edit="";
    if($r["origin"]==="you" && $hasMeta){
        $lbl=$mt["comment"]!==""?"{ipfw_note_edit}":"{ipfw_note_add}";
        $edit="<button type='button' class='ipf-link ipfw-note-btn' OnClick=\"IpfWNoteOpen('$id')\">$lbl</button>";
    }
    if(count($parts)==0 && $note==="" && $edit===""){return "";}
    $form="";
    if($edit!==""){
        $form="<div class='ipfw-note-form' hidden><input type='text' maxlength='200' value=\"".ipf_h($mt["comment"])."\" placeholder=\"".ipf_h(ipf_t("ipfw_note_ph"))."\" aria-label=\"".ipf_h(ipf_t("ipfw_note_label"))."\" OnKeyDown=\"IpfWNoteKey(event,'$id')\">"
            ."<button type='button' class='ipfs-cf-go ipfw-note-save' OnClick=\"IpfWNoteSave('$id')\">{ipfw_note_save}</button><button type='button' class='ipfs-cf-no' OnClick=\"IpfWNoteClose('$id')\">{ipfs_cancel}</button></div>";
    }
    return "<div class='ipfw-meta'>".implode(" &middot; ",$parts).$note.$edit."</div>".$form;
}

function ipfw_row(array $r,bool $hasMeta=false):string{
    $ip=$r["ip"];
    $h=ipf_h($ip);
    $js=ipfb_js_ip($ip);
    $id="w".md5($ip);
    $net=ipfb_net_label($ip);
    $tag=$net!==""?"<span class='ipfs-tag'>".ipf_h($net)."</span>":"";
    $copy="<button type='button' class='ipfs-ib ipfb-copy' OnClick=\"IpfBanCopy(this,'$js')\" title=\"".ipf_h(ipf_t("ipfb_copy"))."\" aria-label=\"".ipf_h(ipf_t("ipfb_copy"))." $h\"><i class='fas fa-copy' aria-hidden='true'></i></button>";
    $act="<button type='button' class='ipf-link' OnClick=\"IpfBanCheck('$js')\"><i class='fas fa-magnifying-glass' aria-hidden='true'></i> {ipfb_check}</button>";
    $cf="";
    if($r["origin"]==="you"){
        $act.="<button type='button' class='ipfb-btn ipfw-rm' OnClick=\"IpfWAsk('$id',1)\"><i class='fas fa-trash-can' aria-hidden='true'></i> {ipfw_remove}</button>";
        $cf="<div class='ipfs-confirm' hidden><i class='fas fa-triangle-exclamation' aria-hidden='true'></i><span>".ipf_h(ipf_t("ipfw_remove_confirm",$ip))."</span>"
            ."<button type='button' class='ipfs-cf-go ipfw-cf-del' OnClick=\"IpfWDel('$id','$js')\">{ipfw_remove}</button><button type='button' class='ipfs-cf-no' OnClick=\"IpfWAsk('$id',0)\">{ipfs_cancel}</button></div>"
            ."<div class='ipfb-err' role='alert' hidden></div>";
    }else{
        $act.="<span class='ipfw-managed' title=\"".ipf_h(ipf_t("ipfw_sys_tip"))."\"><i class='fas fa-lock' aria-hidden='true'></i> {ipfw_managed}</span>";
    }
    return "<div class='ipfb-row' id='$id' data-ip=\"$h\"><div class='ipfb-line'><div class='ipfb-addr'><span class='ipfb-ip'>$h</span>$tag$copy".ipfw_meta_html($r,$id,$hasMeta)."</div>"
        ."<div class='ipfw-origin'>".ipfw_origin_tag($r["origin"])."</div><div class='ipfb-act'>$act</div></div>$cf</div>";
}

function ipfw_table(array $m):string{
    $rows=array();
    foreach($m["rows"] as $r){$rows[]=ipfw_row($r,$m["has_meta"]);}
    $foot="";
    if($m["matched"]>count($m["rows"])){
        $foot="<div class='ipfb-foot'><span>".ipf_h(ipf_t("ipfw_trunc",ipf_num(count($m["rows"])),ipf_num($m["matched"])))."</span></div>";
    }
    return "<div class='ipf-card ipfb-card'><div class='ipfb-head'><span class='ipfb-h1'>{ipfw_col_addr}</span><span class='ipfw-h2'>{ipfw_col_origin}</span><span class='ipfb-h3'>{ipfb_col_act}</span></div>"
        .implode("",$rows).$foot."</div>";
}

function ipfw_no_match(array $m):string{
    $q=$m["p"]["q"];
    $js=ipfb_js_ip($q);
    if($q!=="" && filter_var($q,FILTER_VALIDATE_IP)!==false){
        return "<div class='ipfw-info'><span class='ipfw-info-i' aria-hidden='true'><i class='fas fa-circle-info'></i></span><div><b>".ipf_h(ipf_t("ipfw_not_in",$q))."</b><p>{ipfw_not_in_text}</p>"
            ."<div class='ipfb-ok-a'><button type='button' class='ipf-btn' OnClick=\"IpfWAllowThis('$js')\"><i class='fas fa-plus' aria-hidden='true'></i> {ipfw_allow_it}</button>"
            ."<button type='button' class='ipf-edit' OnClick=\"IpfBanCheck('$js')\"><i class='fas fa-magnifying-glass' aria-hidden='true'></i> {ipfb_check}</button></div></div></div>";
    }
    $t=$q!==""?ipf_t("ipfw_none_start",$q):ipf_t("ipfw_none_filter");
    return "<div class='ipfw-info'><span class='ipfw-info-i' aria-hidden='true'><i class='fas fa-circle-info'></i></span><div><b>".ipf_h($t)."</b>"
        ."<div class='ipfb-ok-a'><button type='button' class='ipf-link' OnClick=\"IpfWReset()\">{ipfb_clear}</button></div></div></div>";
}

function ipfw_empty():string{
    return "<div class='ipf-invite ipfw-empty'><span class='ipf-invite-i' aria-hidden='true'><i class='fas fa-circle-check'></i></span><h2>{ipfw_empty_title}</h2><p>{ipfw_empty_text}</p>"
        ."<button type='button' class='ipf-btn' OnClick=\"IpfWFocusAdd()\"><i class='fas fa-plus' aria-hidden='true'></i> {allow_an_IP_address}</button></div>";
}

function ipfw_invite_install():string{
    return "<div class='ipf-invite'><span class='ipf-invite-i' aria-hidden='true'><i class='fas fa-circle-check'></i></span><h2>{ipfw_install_title}</h2><p>{ipfs_invite_text}</p>"
        ."<button type='button' class='ipf-btn' OnClick=\"IpfWParams()\"><i class='fas fa-arrow-left' aria-hidden='true'></i> {ipfs_go_params}</button></div>";
}

function ipfw_error(array $m):string{
    return "<div class='ipf-errbox' role='alert'><span class='ipf-errbox-i' aria-hidden='true'><i class='fas fa-circle-xmark'></i></span>"
        ."<div><b>{ipfw_err_title}</b><p>{$m["error"]}</p></div></div>";
}

function ipfw_help():string{
    return "<div class='ipfs-foot'><i class='fas fa-circle-info' aria-hidden='true'></i> {ipfw_help}</div>";
}

function ipfw_tiny(array $m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    if($m["enabled"]==1){
        $topbuttons[]=array("IpfWFocusAdd()",ico_plus,"{allow_an_IP_address}");
        $topbuttons[]=array("Loadjs('$page?checkip=yes')",ico_loupe,"{check_ip_reputation}");
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{CybercrimeIPFeeds}";
    $TINY_ARRAY["ICO"]="fas fa-hockey-mask";
    $TINY_ARRAY["EXPL"]="{ipfw_header_explain}";
    $TINY_ARRAY["URL"]="Cybercrime-IP-Feeds";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

// Valide une adresse ou un réseau IPv4/IPv6 (préfixe compris).
function ipfw_valid(string $v):bool{
    $ip=$v;$pfx=null;
    $pos=strpos($v,"/");
    if($pos!==false){$ip=substr($v,0,$pos);$pfx=substr($v,$pos+1);if(!ctype_digit($pfx)){return false;}$pfx=intval($pfx);}
    if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4)!==false){return $pfx===null || ($pfx>=0 && $pfx<=32);}
    if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV6)!==false){return $pfx===null || ($pfx>=0 && $pfx<=128);}
    return false;
}

// POST ipfw-add : une ou plusieurs valeurs séparées par des virgules. Toutes sont validées avant le premier envoi.
// Arguments facultatifs de /white/{ip} : auteur (compte de la session) et note.
function ipfw_meta_query(string $note):string{
    $by="";
    if(isset($_SESSION["uid"])){$by=(string)$_SESSION["uid"];}
    if($by==="-100"){$by="Manager";}
    $q=array();
    if($by!==""){$q[]="by=".rawurlencode(mb_substr($by,0,128));}
    $note=trim(preg_replace('#[\x00-\x1F\x7F]+#u',' ',$note));
    if($note!==""){$q[]="comment=".rawurlencode(mb_substr($note,0,200));}
    return count($q)>0?"?".implode("&",$q):"";
}

function ipfw_add_save():bool{
    $raw=(string)$_POST["ipfw-add"];
    $note=(string)($_POST["ipfw-note"] ?? "");
    $vals=array();
    foreach(preg_split('#[\s,;]+#',$raw) as $v){
        $v=trim($v);
        if($v===""){continue;}
        if(!ipfw_valid($v)){echo ipf_t("ipfw_invalid_one",substr(preg_replace('#[^0-9A-Fa-f\.:/]#','',$v),0,64));return false;}
        $vals[$v]=true;
    }
    if(count($vals)==0){echo ipf_t("ipfw_add_empty");return false;}
    $done=array();
    foreach(array_keys($vals) as $v){
        $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/white/".ipfb_path($v).ipfw_meta_query($note)));
        if(!is_object($json) || empty($json->Status)){
            $e=is_object($json)&&isset($json->Error)?(string)$json->Error:ipf_t("ipf_err_noanswer");
            if(count($done)>0){admin_tracks("Cybercrime IP Feeds: trusted ".implode(", ",$done));}
            echo "$v: $e";
            return false;
        }
        $done[]=$v;
    }
    return admin_tracks("Cybercrime IP Feeds: trusted ".implode(", ",$done));
}

// POST ipfw-note : remplace la note d'une entrée ("" l'efface). Rien en cas de succès.
function ipfw_note_save():bool{
    $ip=trim((string)$_POST["ipfw-note-ip"]);
    if(!ipfw_valid($ip)){echo ipf_t("ipfb_invalid");return false;}
    $note=trim(preg_replace('#[\x00-\x1F\x7F]+#u',' ',(string)$_POST["ipfw-note"]));
    $note=mb_substr($note,0,200);
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/white/note/".ipfb_path($ip)."?comment=".rawurlencode($note)));
    if(!is_object($json)){echo ipf_t("ipf_err_noanswer");return false;}
    if(empty($json->Status)){
        $e=isset($json->Error)?(string)$json->Error:"";
        // Démon antérieur au 2026-11-21 : la route n'existe pas (404 relayé par REST_API_NFQUEUE).
        if(stripos($e,"404")!==false){echo ipf_t("ipfw_note_old");return false;}
        echo $e;return false;
    }
    return admin_tracks("Cybercrime IP Feeds: note of trusted $ip set to \"$note\"");
}

// POST ipfw-del : retire une entrée ajoutée par un administrateur. Rien en cas de succès.
function ipfw_del_save():bool{
    $ip=trim((string)$_POST["ipfw-del"]);
    if(!ipfw_valid($ip)){echo ipf_t("ipfb_invalid");return false;}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/white/remove/".ipfb_path($ip)));
    if(!is_object($json) || empty($json->Status)){echo is_object($json)&&isset($json->Error)?$json->Error:ipf_t("ipf_err_noanswer");return false;}
    return admin_tracks("Cybercrime IP Feeds: $ip removed from the trusted list");
}

function ipfw_js():string{
    $page=CurrentPageName();
    return ipfb_js()."<script>(function(){
var T=null,F=(document.querySelector('#ipfw-root .ipfs-seg[aria-checked=true]')||{getAttribute:function(){return 'all';}}).getAttribute('data-f');
function qv(){var q=document.getElementById('ipfw-q');return q?q.value.trim():'';}
window.IpfWLoad=function(){if(T){clearTimeout(T);T=null;}LoadAjaxSilent('ipfw-host','$page?ipfw-list=yes&q='+encodeURIComponent(qv())+'&f='+encodeURIComponent(F));};
window.IpfWInput=function(){if(T){clearTimeout(T);}T=setTimeout(IpfWLoad,500);};
window.IpfWKey=function(e){if(e.key==='Enter'){e.preventDefault();IpfWLoad();}if(e.key==='Escape'){IpfWReset();}};
window.IpfWFilter=function(f){F=f;var b=document.querySelectorAll('#ipfw-root .ipfs-seg');for(var i=0;i<b.length;i++){b[i].setAttribute('aria-checked',b[i].getAttribute('data-f')===f?'true':'false');}IpfWLoad();};
window.IpfWReset=function(){var q=document.getElementById('ipfw-q');if(q){q.value='';}IpfWFilter('all');};
window.IpfWFocusAdd=function(){var n=document.getElementById('ipfw-new');if(n){if(n.scrollIntoView){n.scrollIntoView({block:'center'});}n.focus();}};
window.IpfWAllowThis=function(ip){var n=document.getElementById('ipfw-new');if(n){n.value=ip;}IpfWAdd();};
window.IpfWAddKey=function(e){if(e.key==='Enter'){e.preventDefault();IpfWAdd();}};
function addErr(t){var e=document.querySelector('#ipfw-root .ipfw-add-err');if(!e){return;}if(!t){e.hidden=true;e.textContent='';return;}e.textContent=t;e.hidden=false;}
window.IpfWAdd=function(){var n=document.getElementById('ipfw-new');if(!n){return;}var v=n.value.trim();addErr('');if(v===''){n.focus();return;}
  var btn=document.getElementById('ipfw-add-btn');if(btn){btn.disabled=true;}
  var nt=document.getElementById('ipfw-note');
  $.post('$page',{'ipfw-add':v,'ipfw-note':nt?nt.value:''},function(r){if(btn){btn.disabled=false;}r=String(r||'').trim();
    if(r.length>0){addErr(r.replace(/<[^>]*>/g,''));IpfWLoad();return;}
    n.value='';if(nt){nt.value='';}var q=document.getElementById('ipfw-q');if(q&&q.value!==''){q.value='';}IpfWLoad();},'text')
   .fail(function(){if(btn){btn.disabled=false;}addErr('HTTP error');});};
window.IpfWAsk=function(id,on){var row=document.getElementById(id);var c=row?row.querySelector('.ipfs-confirm'):null;if(!c){return;}c.hidden=!on;if(on){var g=c.querySelector('.ipfs-cf-go');if(g){g.focus();}}};
window.IpfWDel=function(id,ip){var row=document.getElementById(id);var er=row?row.querySelector('.ipfb-err'):null;if(er){er.hidden=true;}
  $.post('$page',{'ipfw-del':ip},function(r){r=String(r||'').trim();
    if(r.length>0){if(er){er.textContent=r.replace(/<[^>]*>/g,'');er.hidden=false;}return;}
    if(row){row.parentNode.removeChild(row);}setTimeout(IpfWLoad,400);},'text').fail(function(){if(er){er.textContent='HTTP error';er.hidden=false;}});};
function noteForm(id){var row=document.getElementById(id);return row?row.querySelector('.ipfw-note-form'):null;}
window.IpfWNoteOpen=function(id){var f=noteForm(id);if(!f){return;}f.hidden=false;var i=f.querySelector('input');if(i){i.focus();i.select();}};
window.IpfWNoteClose=function(id){var f=noteForm(id);if(f){f.hidden=true;}};
window.IpfWNoteKey=function(e,id){if(e.key==='Enter'){e.preventDefault();IpfWNoteSave(id);}if(e.key==='Escape'){IpfWNoteClose(id);}};
window.IpfWNoteSave=function(id){var row=document.getElementById(id);var f=noteForm(id);if(!row||!f){return;}var er=row.querySelector('.ipfb-err');if(er){er.hidden=true;}
  var i=f.querySelector('input');$.post('$page',{'ipfw-note-ip':row.getAttribute('data-ip'),'ipfw-note':i?i.value:''},function(r){r=String(r||'').trim();
    if(r.length>0){if(er){er.textContent=r.replace(/<[^>]*>/g,'');er.hidden=false;}return;}IpfWLoad();},'text')
   .fail(function(){if(er){er.textContent='HTTP error';er.hidden=false;}});};
window.IpfWParams=function(){var r=document.getElementById('ipfw-root');var c=r?$(r).closest('.tabs-container'):$();var a=(c.length?c:$(document)).find('ul.nav-tabs a[data-url*=\"parameters=yes\"]').first();if(a.length){a.trigger('click');}};
})();</script>";
}

function ipfw_style():string{
    return ipfb_style()."<style>
.ipfw-panel{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,1fr);gap:24px}
.ipfw-pt{font-size:14px;font-weight:600;margin-bottom:8px;color:#0f172a}
.ipfw-add .ipf-btn{height:44px}
.ipfw-add .ipf-btn:disabled{opacity:.6;cursor:wait}
.ipfw-add-err{margin-top:8px;padding:8px 12px;border:1px solid #fecaca;background:#fef2f2;border-radius:8px;color:#991b1b;font-size:13px}
.ipfw-add-err[hidden]{display:none}
.ipfw-find .ipfb-q{display:block;margin-bottom:10px}
.ipfw-find .ipfb-q input{font-family:inherit;font-size:14px}
.ipfw-chip-off{background:#f1f5f9;border-color:#e2e8f0;color:#475569}
.ipfw-chip-off .ipf-link{font-size:12.5px;margin-left:4px}
.ipfw-origin{flex:0 0 220px}
.ipfw-h2{flex:0 0 220px}
.ipfw .ipfb-act{min-width:260px}
.ipfw .ipfb-h3{min-width:260px}
.ipfw-rm:hover{border-color:#fca5a5;color:#b91c1c;background:#fef2f2}
.ipfw-cf-del{background:#b91c1c}
.ipfw-managed{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#64748b;white-space:nowrap;cursor:help}
.ipfw-managed svg{width:11px;height:11px}
.ipfw-info{display:flex;gap:14px;border:1px solid #e2e8f0;background:#f1f5f9;border-radius:10px;padding:16px 18px;margin-bottom:12px;color:#334155}
.ipfw-info b{font-size:15px;color:#0f172a}
.ipfw-info p{margin:4px 0 10px;font-size:13.5px}
.ipfw-info-i{display:inline-flex;width:36px;height:36px;flex:0 0 36px;border-radius:50%;background:#e2e8f0;color:#475569;align-items:center;justify-content:center}
.ipfw-info-i svg{width:18px;height:18px}
.ipfw-empty{margin-bottom:12px}
.ipfw .ipfb-addr{row-gap:4px}
.ipfw-meta{flex:1 1 100%;display:flex;flex-wrap:wrap;align-items:center;gap:8px;font-size:12.5px;color:#64748b}
.ipfw-note{display:inline-flex;align-items:center;gap:5px;color:#334155;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:1px 8px;max-width:520px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ipfw-note svg{width:11px;height:11px;color:#94a3b8}
.ipfw-note-btn{font-size:12.5px}
.ipfw-note-form{display:flex;gap:8px;align-items:center;margin-top:8px}
.ipfw-note-form[hidden]{display:none}
.ipfw-note-form input{flex:1;max-width:520px;height:34px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;font-size:13.5px}
.ipfw-note-form input:focus,.ipfw-note-in:focus{outline:2px solid var(--ipf-acc);outline-offset:1px}
.ipfw-note-save{background:var(--ipf-acc)}
.ipfw-note-in{width:100%;height:38px;margin-top:8px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px;font-size:13.5px}
@media (max-width:1200px){.ipfw-panel{grid-template-columns:1fr}.ipfw-origin{flex:1 1 auto}.ipfw-h2{display:none}}
</style>";
}
function blacklisted_start():bool{
    $tpl=new template_admin();
    $p=ipfb_params();
    echo ipfb_style()."<div class='ipf ipfs ipfb' id='ipfb-root'>"
        .$tpl->_ENGINE_parse_body(ipfb_banner(ipfb_blocktime()).(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue"))==1?ipfb_search_panel($p):""))
        ."<div id='ipfb-host'>".$tpl->_ENGINE_parse_body(ipfb_render(ipfb_model($p)))."</div></div>".ipfb_js();
    return true;
}

// Hôte seul : rechargé à chaque recherche et après chaque action.
function ipfb_list():bool{
    $tpl=new template_admin();
    echo $tpl->_ENGINE_parse_body(ipfb_render(ipfb_model(ipfb_params())));
    return true;
}

// Valeur placée dans un segment de chemin du démon NFQueue : fasthttp décode le chemin
// avant le routage, un "/" (CIDR) ou ":" (IPv6) doit donc être encodé deux fois.
function ipfb_path(string $v):string{
    return rawurlencode(rawurlencode($v));
}

// Adresse placée dans un littéral JS : seuls les caractères d'une IP ou d'un CIDR survivent.
function ipfb_js_ip(string $ip):string{
    return preg_replace('#[^0-9A-Fa-f\.:/]#','',$ip);
}

// Saisie de recherche : chiffres, hexadécimal, ".", ":" et "/" seulement (le démon en fait une regex).
function ipfb_params():array{
    $raw=trim((string)($_GET["q"] ?? ""));
    $max=intval($_GET["max"] ?? 350);
    if(!in_array($max,array(100,350,1000),true)){$max=350;}
    $p=array("q"=>"","raw"=>substr($raw,0,64),"invalid"=>false,"max"=>$max);
    if($raw===""){return $p;}
    if(!preg_match('#^[0-9A-Fa-f\.:/]{1,64}$#',$raw)){$p["invalid"]=true;return $p;}
    $p["q"]=$raw;
    return $p;
}

function ipfb_blocktime():int{
    static $bt=null;
    if($bt!==null){return $bt;}
    $bt=0;
    $st=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/status"));
    if(is_object($st) && !empty($st->Status) && isset($st->Config) && is_object($st->Config)){
        $bt=intval($st->Config->BlockTime ?? 0);
        if($bt<5){$bt=5;}
        $GLOBALS["IPFB_TOTAL"]=0;
        if(isset($st->IPsets->nfqueueblock->header->numentries)){$GLOBALS["IPFB_TOTAL"]=intval($st->IPsets->nfqueueblock->header->numentries);}
    }
    return $bt;
}

function ipfb_model(array $p):array{
    $m=array("enabled"=>intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaNFQueue")),"error"=>"","p"=>$p,
        "rows"=>array(),"matched"=>0,"total"=>0,"blocktime"=>0,"now"=>time());
    if($m["enabled"]==0 || $p["invalid"]){return $m;}
    $m["blocktime"]=ipfb_blocktime();
    $m["total"]=intval($GLOBALS["IPFB_TOTAL"] ?? 0);
    // Recherche par début d'adresse : les points sont échappés, la regex est ancrée.
    $term="nil";
    if($p["q"]!==""){$term="^".str_replace(".","\\.",$p["q"]);}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/blocked/".ipfb_path($term)));
    if(!is_object($json)){$m["error"]="{ipf_err_noanswer}";return $m;}
    if(empty($json->Status)){
        $e=isset($json->Error)?trim((string)$json->Error):"";
        $m["error"]=$e!==""?ipf_h($e):"{ipf_err_noanswer}";
        return $m;
    }
    $items=(isset($json->Items) && is_array($json->Items))?$json->Items:array();
    $m["matched"]=count($items);
    foreach($items as $it){
        if(!is_object($it)){continue;}
        $ip=trim((string)($it->elem ?? ""));
        if($ip===""){continue;}
        $m["rows"][]=array("ip"=>$ip,"left"=>intval($it->timeout ?? 0));
        if(count($m["rows"])>=$p["max"]){break;}
    }
    if($p["q"]==="" && $m["total"]<$m["matched"]){$m["total"]=$m["matched"];}
    return $m;
}

function ipfb_render(array $m):string{
    $h=array();
    if($m["enabled"]==0){
        $h[]=ipfb_invite_install();
    }elseif($m["p"]["invalid"]){
        $h[]=ipfb_invalid();
    }elseif($m["error"]!==""){
        $h[]=ipfb_error($m);
    }else{
        $h[]=ipfb_result_line($m);
        if(count($m["rows"])==0){
            $h[]=$m["p"]["q"]!==""?ipfb_no_match($m):ipfb_empty();
        }else{
            $h[]=ipfb_table($m);
        }
        $h[]=ipfb_help();
    }
    $h[]=ipfb_tiny($m);
    return implode("\n",$h);
}

function ipfb_banner(int $bt):string{
    $c=array();
    if($bt>0){$c[]=ipf_chip("fas fa-clock",ipf_t("ipfb_chip_expire",$bt));}
    $c[]=ipf_chip("fas fa-rotate-left",ipf_t("ipfb_chip_instant"));
    $c[]=ipf_chip("fas fa-circle-check",ipf_t("ipfb_chip_allowed"));
    $txt=$bt>0?ipf_h(ipf_t("ipfb_banner_text",$bt)):"{ipfb_banner_text0}";
    return "<div class='ipf-banner'><span class='ipf-banner-i' aria-hidden='true'><i class='fas fa-ban'></i></span>"
        ."<div class='ipf-banner-b'><h2>{ipfb_banner_title} <small class='ipfb-sub'>{denied_ip_sources}</small></h2><p>$txt</p><div class='ipf-chips'>".implode("",$c)."</div></div></div>";
}

function ipfb_search_panel(array $p):string{
    $opts=array();
    foreach(array(100,350,1000) as $n){
        $sel=$n===$p["max"]?" selected":"";
        $opts[]="<option value='$n'$sel>".ipf_h(ipf_t("ipfb_rows",$n))."</option>";
    }
    return "<div class='ipfb-panel'><div class='ipfb-bar'><label class='ipfb-q'><span class='ipfb-q-i' aria-hidden='true'><i class='fas fa-magnifying-glass'></i></span>"
        ."<input type='search' id='ipfb-q' value=\"".ipf_h($p["raw"])."\" maxlength='64' autocomplete='off' spellcheck='false' placeholder=\"".ipf_h(ipf_t("ipfb_search_ph"))."\" aria-label=\"".ipf_h(ipf_t("ipfb_search_ph"))."\" OnInput=\"IpfBanInput()\" OnKeyDown=\"IpfBanKey(event)\"></label>"
        ."<label class='ipfb-show'><span>{ipfb_show}</span><select id='ipfb-max' OnChange=\"IpfBanLoad()\">".implode("",$opts)."</select></label>"
        ."<button type='button' class='ipf-edit' OnClick=\"IpfBanLoad()\"><i class='fas fa-rotate' aria-hidden='true'></i> {ipfb_refresh}</button></div>"
        ."<p class='ipf-help'><i class='fas fa-circle-question' aria-hidden='true'></i> {ipfb_search_help}</p></div>";
}

function ipfb_result_line(array $m):string{
    if($m["p"]["q"]!=="" && $m["matched"]==0){return "";}
    if($m["p"]["q"]!==""){
        $t=ipf_t("ipfb_matches",ipf_num($m["matched"]),$m["p"]["q"]);
    }else{
        $t=ipf_t("ipfb_total",ipf_num($m["total"]));
        if($m["total"]>count($m["rows"])){$t.=" · ".ipf_t("ipfb_showing",ipf_num(count($m["rows"])));}
    }
    return "<div class='ipfb-count' role='status'>".ipf_h($t)."</div>";
}

// Taille d'un réseau : 2^(32-préfixe) en IPv4 ; en IPv6, le préfixe seul.
function ipfb_net_label(string $ip):string{
    $pos=strpos($ip,"/");
    if($pos===false){return "";}
    $pfx=intval(substr($ip,$pos+1));
    if(strpos($ip,":")===false && $pfx>=0 && $pfx<=32){
        return ipf_t("ipfb_net4",$pfx,ipf_num(pow(2,32-$pfx)));
    }
    return ipf_t("ipfb_net6",$pfx);
}

function ipfb_left(int $left,int $bt,int $now):string{
    if($left<=0){
        return "<div class='ipfb-left'><span class='ipfb-bar-t' aria-hidden='true'><span class='ipfb-bar-f' style='width:100%'></span></span><span class='ipfb-left-t ipfs-mute'>{ipfb_no_end}</span></div>";
    }
    $max=max(60,$bt*60);
    $pct=min(100,max(2,intval(round($left*100/$max))));
    $txt=$left<60?ipf_t("ipfb_lifted_soon"):ipf_t("ipfb_lifted_in",ipfb_dur($left));
    $cls=($left*10<$max)?" ipfs-mute":"";
    return "<div class='ipfb-left' title=\"".ipf_h(ipf_t("ipfb_lifted_at",date("H:i",$now+$left)))."\">"
        ."<span class='ipfb-bar-t' aria-hidden='true'><span class='ipfb-bar-f' style='width:$pct%'></span></span><span class='ipfb-left-t$cls'>".ipf_h($txt)."</span></div>";
}

function ipfb_dur(int $sec):string{
    if($sec<3600){return ipf_t("ipf_dur_min",max(1,intval(ceil($sec/60))));}
    $h=intval($sec/3600);$mn=intval(($sec%3600)/60);
    if($sec>=86400){return ipf_t("ipf_dur_days",intval($sec/86400));}
    return $mn>0?ipf_t("ipf_dur_hm",$h,$mn):ipf_t("ipf_dur_hours",$h);
}

function ipfb_row(array $r,int $bt,int $now):string{
    $ip=$r["ip"];
    $h=ipf_h($ip);
    $js=ipfb_js_ip($ip);
    $id="b".md5($ip);
    $net=ipfb_net_label($ip);
    $tag=$net!==""?"<span class='ipfs-tag'>".ipf_h($net)."</span>":"";
    $copy="<button type='button' class='ipfs-ib ipfb-copy' OnClick=\"IpfBanCopy(this,'$js')\" title=\"".ipf_h(ipf_t("ipfb_copy"))."\" aria-label=\"".ipf_h(ipf_t("ipfb_copy"))." $h\"><i class='fas fa-copy' aria-hidden='true'></i></button>";
    $act="<button type='button' class='ipfb-btn' OnClick=\"IpfBanAsk('$id','lift')\"><i class='fas fa-rotate-left' aria-hidden='true'></i> {ipfb_lift}</button>"
        ."<button type='button' class='ipfb-btn ipfb-btn-acc' OnClick=\"IpfBanAsk('$id','allow')\"><i class='fas fa-circle-check' aria-hidden='true'></i> {ipfb_allow}</button>"
        ."<button type='button' class='ipf-link' OnClick=\"IpfBanCheck('$js')\"><i class='fas fa-magnifying-glass' aria-hidden='true'></i> {ipfb_check}</button>";
    $cf="<div class='ipfs-confirm' data-k='lift' hidden><i class='fas fa-circle-info' aria-hidden='true'></i><span>".ipf_h(ipf_t("ipfb_lift_confirm",$ip))."</span>"
        ."<button type='button' class='ipfs-cf-go' OnClick=\"IpfBanDo('$id','lift','$js')\">{ipfb_lift}</button><button type='button' class='ipfs-cf-no' OnClick=\"IpfBanAsk('$id','')\">{ipfs_cancel}</button></div>"
        ."<div class='ipfs-confirm ipfb-cf-acc' data-k='allow' hidden><i class='fas fa-circle-check' aria-hidden='true'></i><span>".ipf_h(ipf_t("ipfb_allow_confirm",$ip))."</span>"
        ."<button type='button' class='ipfs-cf-go' OnClick=\"IpfBanDo('$id','allow','$js')\">{ipfb_allow}</button><button type='button' class='ipfs-cf-no' OnClick=\"IpfBanAsk('$id','')\">{ipfs_cancel}</button></div>"
        ."<div class='ipfb-err' role='alert' hidden></div>";
    return "<div class='ipfb-row' id='$id'><div class='ipfb-line'><div class='ipfb-addr'><span class='ipfb-ip'>$h</span>$tag$copy</div>"
        .ipfb_left($r["left"],$bt,$now)."<div class='ipfb-act'>$act</div></div>$cf</div>";
}

function ipfb_table(array $m):string{
    $rows=array();
    foreach($m["rows"] as $r){$rows[]=ipfb_row($r,$m["blocktime"],$m["now"]);}
    $foot="";
    $shown=count($m["rows"]);
    $all=$m["p"]["q"]!==""?$m["matched"]:$m["total"];
    if($all>$shown){
        $btn=$m["p"]["max"]<1000?"<button type='button' class='ipf-edit' OnClick=\"IpfBanMax(1000)\">{ipfb_show_1000}</button>":"";
        $foot="<div class='ipfb-foot'><span>".ipf_h(ipf_t("ipfb_trunc",ipf_num($shown),ipf_num($all)))."</span>$btn</div>";
    }
    $bt=$m["blocktime"]>0?" <small>".ipf_h(ipf_t("ipfb_col_rule",$m["blocktime"]))."</small>":"";
    return "<div class='ipf-card ipfb-card'><div class='ipfb-head'><span class='ipfb-h1'>{ipfb_col_addr}$bt</span><span class='ipfb-h2'>{ipfb_col_left}</span><span class='ipfb-h3'>{ipfb_col_act}</span></div>"
        .implode("",$rows).$foot."</div>";
}

function ipfb_no_match(array $m):string{
    $page=CurrentPageName();
    $q=$m["p"]["q"];
    $js=ipfb_js_ip($q);
    $full=filter_var($q,FILTER_VALIDATE_IP)!==false;
    if($full){
        return "<div class='ipfb-ok'><span class='ipfb-ok-i' aria-hidden='true'><i class='fas fa-circle-check'></i></span><div><b>".ipf_h(ipf_t("ipfb_not_banned",$q))."</b><p>{ipfb_not_banned_text}</p>"
            ."<div class='ipfb-ok-a'><button type='button' class='ipf-edit' OnClick=\"IpfBanCheck('$js')\"><i class='fas fa-magnifying-glass' aria-hidden='true'></i> {ipfb_check}</button>"
            ."<button type='button' class='ipf-edit' OnClick=\"Loadjs('$page?banip=yes&ip=".rawurlencode($q)."&function=IpfBanLoad')\"><i class='fas fa-ban' aria-hidden='true'></i> {ipfb_ban_this}</button></div></div></div>";
    }
    return "<div class='ipfb-ok'><span class='ipfb-ok-i' aria-hidden='true'><i class='fas fa-circle-check'></i></span><div><b>".ipf_h(ipf_t("ipfb_none_start",$q))."</b><p>{ipfb_none_start_text}</p>"
        ."<div class='ipfb-ok-a'><button type='button' class='ipf-link' OnClick=\"IpfBanClear()\">{ipfb_clear}</button></div></div></div>";
}

function ipfb_empty():string{
    $page=CurrentPageName();
    return "<div class='ipf-invite'><span class='ipf-invite-i' aria-hidden='true'><i class='fas fa-shield-halved'></i></span><h2>{ipfb_empty_title}</h2><p>{ipfb_empty_text}</p>"
        ."<button type='button' class='ipf-btn' OnClick=\"Loadjs('$page?banip=yes&function=IpfBanLoad')\"><i class='fas fa-ban' aria-hidden='true'></i> {ban_ips}</button></div>";
}

function ipfb_invite_install():string{
    return "<div class='ipf-invite'><span class='ipf-invite-i' aria-hidden='true'><i class='fas fa-ban'></i></span><h2>{ipfb_install_title}</h2><p>{ipfs_invite_text}</p>"
        ."<button type='button' class='ipf-btn' OnClick=\"IpfSrcParams()\"><i class='fas fa-arrow-left' aria-hidden='true'></i> {ipfs_go_params}</button></div>";
}

function ipfb_invalid():string{
    return "<div class='ipfb-warn' role='alert'><i class='fas fa-triangle-exclamation' aria-hidden='true'></i> {ipfb_invalid}</div>";
}

function ipfb_error(array $m):string{
    return "<div class='ipf-errbox' role='alert'><span class='ipf-errbox-i' aria-hidden='true'><i class='fas fa-circle-xmark'></i></span>"
        ."<div><b>{ipfb_err_title}</b><p>{$m["error"]}</p></div></div>";
}

function ipfb_help():string{
    return "<div class='ipfs-foot'><i class='fas fa-circle-info' aria-hidden='true'></i> {ipfb_help}</div>";
}

function ipfb_tiny(array $m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $topbuttons=array();
    if($m["enabled"]==1){
        $topbuttons[]=array("Loadjs('$page?banip=yes&function=IpfBanLoad')",ico_plus,"{ban_ips}");
        $topbuttons[]=array("Loadjs('$page?checkip=yes')",ico_loupe,"{check_ip_reputation}");
    }
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{CybercrimeIPFeeds}";
    $TINY_ARRAY["ICO"]="fas fa-hockey-mask";
    $TINY_ARRAY["EXPL"]="{ipfb_header_explain}";
    $TINY_ARRAY["URL"]="Cybercrime-IP-Feeds";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    return "<script>Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');</script>";
}

// POST ipfb-allow : liste blanche puis levée du bannissement. Rien en cas de succès.
function ipfb_allow_save():bool{
    $tpl=new template_admin();
    $ip=trim((string)$_POST["ipfb-allow"]);
    if(!preg_match('#^[0-9A-Fa-f\.:/]{3,64}$#',$ip)){echo ipf_t("ipfb_invalid");return false;}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/white/".ipfb_path($ip).ipfw_meta_query(ipf_t("ipfw_note_from_banned"))));
    if(!is_object($json) || empty($json->Status)){echo is_object($json)&&isset($json->Error)?$json->Error:ipf_t("ipf_err_noanswer");return false;}
    $json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API_NFQUEUE("/blocked/remove/".ipfb_path($ip)));
    if(!is_object($json) || empty($json->Status)){echo is_object($json)&&isset($json->Error)?$json->Error:ipf_t("ipf_err_noanswer");return false;}
    return admin_tracks("Cybercrime IP Feeds: $ip always allowed and its ban lifted");
}

function ipfb_js():string{
    $page=CurrentPageName();
    return "<script>(function(){
var T=null;
function qv(){var q=document.getElementById('ipfb-q');return q?q.value.trim():'';}
function mv(){var s=document.getElementById('ipfb-max');return s?s.value:'350';}
window.IpfBanLoad=function(){if(T){clearTimeout(T);T=null;}LoadAjaxSilent('ipfb-host','$page?ipfb-list=yes&q='+encodeURIComponent(qv())+'&max='+encodeURIComponent(mv()));};
window.IpfBanInput=function(){if(T){clearTimeout(T);}T=setTimeout(IpfBanLoad,500);};
window.IpfBanKey=function(e){if(e.key==='Enter'){e.preventDefault();IpfBanLoad();}if(e.key==='Escape'){IpfBanClear();}};
window.IpfBanClear=function(){var q=document.getElementById('ipfb-q');if(q){q.value='';q.focus();}IpfBanLoad();};
window.IpfBanMax=function(n){var s=document.getElementById('ipfb-max');if(s){s.value=String(n);}IpfBanLoad();};
window.IpfBanCheck=function(ip){Loadjs('$page?checkip=yes&ip='+encodeURIComponent(ip));};
window.IpfBanAsk=function(id,k){var row=document.getElementById(id);if(!row){return;}var c=row.querySelectorAll('.ipfs-confirm');
  for(var i=0;i<c.length;i++){var on=(c[i].getAttribute('data-k')===k);c[i].hidden=!on;if(on){var g=c[i].querySelector('.ipfs-cf-go');if(g){g.focus();}}}};
window.IpfBanDo=function(id,k,ip){var row=document.getElementById(id);var er=row?row.querySelector('.ipfb-err'):null;if(er){er.hidden=true;}
  var data=k==='allow'?{'ipfb-allow':ip}:{'delete-blocked':ip};
  $.post('$page',data,function(r){r=String(r||'').trim();
    if(r.length>0){if(er){er.textContent=r.replace(/<[^>]*>/g,'');er.hidden=false;}return;}
    if(row){row.parentNode.removeChild(row);}setTimeout(IpfBanLoad,400);},'text')
   .fail(function(){if(er){er.textContent='HTTP error';er.hidden=false;}});};
window.IpfBanCopy=function(btn,ip){var done=function(){var o=btn.innerHTML;btn.classList.add('ipfb-copied');setTimeout(function(){btn.classList.remove('ipfb-copied');},2000);};
  if(navigator.clipboard&&window.isSecureContext){navigator.clipboard.writeText(ip).then(done,function(){fb();});}else{fb();}
  function fb(){var t=document.createElement('textarea');t.value=ip;t.style.position='fixed';t.style.opacity='0';document.body.appendChild(t);t.select();try{document.execCommand('copy');}catch(e){}document.body.removeChild(t);done();}};
window.IpfSrcParams=window.IpfSrcParams||function(){var r=document.getElementById('ipfb-root');var c=r?$(r).closest('.tabs-container'):$();var a=(c.length?c:$(document)).find('ul.nav-tabs a[data-url*=\"parameters=yes\"]').first();if(a.length){a.trigger('click');}};
})();</script>";
}

function ipfb_style():string{
    return ipfs_style()."<style>
.ipfb-sub{font-size:12.5px;font-weight:500;color:#64748b;margin-left:6px}
.ipfb-panel{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:12px;box-shadow:0 1px 2px rgba(15,23,42,.06)}
.ipfb-bar{display:flex;gap:12px;align-items:center}
.ipfb-q{position:relative;flex:1;margin:0;font-weight:400}
.ipfb-q-i{position:absolute;left:14px;top:50%;transform:translateY(-50%);display:inline-flex;width:15px;height:15px;color:#94a3b8;pointer-events:none}
.ipfb-q-i svg{width:15px;height:15px}
.ipfb-q input{width:100%;height:44px;border:1px solid #cbd5e1;border-radius:8px;padding:0 12px 0 40px;font-size:15px;font-family:'JetBrains Mono',Consolas,monospace}
.ipfb-q input::-webkit-search-cancel-button{-webkit-appearance:none;appearance:none}
.ipfb-q input::placeholder{font-family:inherit;color:#94a3b8}
.ipfb-q input:focus,.ipfb-show select:focus{outline:2px solid var(--ipf-acc);outline-offset:1px;border-color:var(--ipf-bd)}
.ipfb-show{display:inline-flex;align-items:center;gap:8px;margin:0;font-size:13px;font-weight:600;color:#475569}
.ipfb-show select{height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px;font-size:13.5px;background:#fff}
.ipfb-bar .ipf-edit{height:40px}
.ipfb-panel .ipf-help{margin-top:10px}
.ipfb-panel .ipf-help svg{width:12px;height:12px}
.ipfb-count{margin:0 2px 10px;font-size:13.5px;font-weight:600;color:#334155}
.ipfb-card{padding:0;overflow:hidden}
.ipfb-head{display:flex;gap:16px;padding:12px 18px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:11.5px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#64748b}
.ipfb-head small{text-transform:none;letter-spacing:0;font-weight:500;color:#94a3b8}
.ipfb-h1{flex:1}.ipfb-h2{flex:0 0 300px}.ipfb-h3{flex:0 0 auto;min-width:330px;text-align:right}
.ipfb-row{padding:10px 18px;border-top:1px solid #f1f5f9}
.ipfb-row:first-of-type{border-top:0}
.ipfb-row:hover{background:#f8fafc}
.ipfb-line{display:flex;align-items:center;gap:16px;min-height:36px}
.ipfb-addr{flex:1;min-width:0;display:flex;align-items:center;flex-wrap:wrap;gap:8px}
.ipfb-ip{font-family:'JetBrains Mono',Consolas,monospace;font-size:15px;font-weight:600;color:#0f172a;word-break:break-all}
.ipfb-copy{width:28px;height:28px;flex:0 0 28px;border-color:transparent;background:transparent;color:#94a3b8}
.ipfb-copy.ipfb-copied{color:#15803d;border-color:#bbf7d0;background:#f0fdf4}
.ipfb-left{flex:0 0 300px;display:flex;align-items:center;gap:12px}
.ipfb-bar-t{display:inline-block;width:120px;height:6px;border-radius:999px;background:var(--ipf-soft);overflow:hidden;flex:0 0 120px}
.ipfb-bar-f{display:block;height:100%;background:var(--ipf-acc);border-radius:999px}
.ipfb-left-t{font-size:13.5px;color:#334155}
.ipfb-act{flex:0 0 auto;display:flex;align-items:center;gap:8px;min-width:330px;justify-content:flex-end}
.ipfb-btn{display:inline-flex;align-items:center;gap:6px;white-space:nowrap;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:6px 11px;font-size:13px;font-weight:600;color:#334155;cursor:pointer}
.ipfb-btn svg{width:12px;height:12px}
.ipfb-btn:hover{background:#f8fafc;border-color:#94a3b8}
.ipfb-btn-acc{border-color:var(--ipf-acc);color:var(--ipf-acc)}
.ipfb-btn-acc:hover{background:var(--ipf-soft);border-color:var(--ipf-acch);color:var(--ipf-acch)}
.ipfb-act .ipf-link{white-space:nowrap}
.ipfb-cf-acc{border-color:var(--ipf-bd);background:#f2f6f6;color:#0f3d36}
.ipfb-cf-acc .ipfs-cf-go{background:var(--ipf-acc)}
.ipfb-err{margin-top:8px;padding:8px 12px;border:1px solid #fecaca;background:#fef2f2;border-radius:8px;color:#991b1b;font-size:13px}
.ipfb-err[hidden]{display:none}
.ipfb-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 18px;background:#f8fafc;border-top:1px solid #e2e8f0;font-size:13.5px;color:#334155}
.ipfb-ok{display:flex;gap:14px;border:1px solid #bbf7d0;background:#f0fdf4;border-radius:10px;padding:16px 18px;margin-bottom:12px;color:#14532d}
.ipfb-ok b{font-size:15px;color:#15803d}
.ipfb-ok p{margin:4px 0 10px;font-size:13.5px}
.ipfb-ok-i{display:inline-flex;width:36px;height:36px;flex:0 0 36px;border-radius:50%;background:#dcfce7;color:#15803d;align-items:center;justify-content:center}
.ipfb-ok-i svg{width:18px;height:18px}
.ipfb-ok-a{display:flex;gap:10px;flex-wrap:wrap}
.ipfb-warn{border:1px solid #fcd34d;background:#fffbeb;border-radius:10px;padding:12px 14px;color:#92400e;font-size:13.5px;margin-bottom:12px}
.ipfb-warn svg{width:13px;height:13px}
@media (max-width:1200px){.ipfb-line{flex-wrap:wrap}.ipfb-addr{flex:1 1 100%}.ipfb-head .ipfb-h2,.ipfb-head .ipfb-h3{display:none}.ipfb-act{min-width:0}}
</style>";
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}