<?php
//SP119
// DNS Load-balancing service (dnsdist) : reglages globaux.
// Maquette stitch_dns_firewall_backend_settings : une carte par groupe de reglages,
// chaque carte s'edite dans une modale. Toutes les valeurs viennent de GET_INFO,
// avec les valeurs par defaut que le demon applique (articarest dnsdist/acls/Acls.go).
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dnsdist.inc");
include_once(dirname(__FILE__)."/ressources/class.hosts.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

// Le formulaire « Threat protection » en premier : il poste aussi EnableCategories / DNSDISTDynamicBlocks.
if(isset($_POST["ddst-threat"])){ddst_threat_save();exit;}
if(isset($_POST["DNSDistQps"])){save();exit;}
if(isset($_POST["PowerDNSListenAddr"])){save();exit;}
if(isset($_POST["DNSDistCheckInterval"])){save();exit;}
if(isset($_POST["UnBoundCacheSize"])){save();exit;}
if(isset($_POST["setUDPSocketBufferSizes"])){save();exit;}
if(isset($_GET["overview"])){ddst_overview();exit;}
if(isset($_GET["dnsdist-status-left"])){left_status();exit;}
if(isset($_GET["dnsdist-security-js"])){dnsdist_security_js();exit;}
if(isset($_GET["dnsdist-security-popup"])){dnsdist_security_popup();exit;}
if(isset($_GET["dnsdist-threat-js"])){ddst_threat_js();exit;}
if(isset($_GET["dnsdist-threat-popup"])){ddst_threat_popup();exit;}
if(isset($_GET["dnsdist-interface-js"])){dnsdist_interfaces_js();exit;}
if(isset($_GET["dnsdist-interface-popup"])){dnsdist_interfaces_popup();exit;}
if(isset($_GET["dnsdist-ha-js"])){dnsdist_ha_js();exit;}
if(isset($_GET["dnsdist-ha-popup"])){dnsdist_ha_popup();exit;}
if(isset($_GET["dnsdist-cache-js"])){dnsdist_cache_js();exit;}
if(isset($_GET["dnsdist-cache-popup"])){dnsdist_cache_popup();exit;}
if(isset($_GET["udp-tcp-performance-js"])){udp_tcp_performance_js();exit;}
if(isset($_GET["udp-tcp-performance-popup"])){udp_tcp_performance_popup();exit;}
table();

function ddst_can_write():bool{
    $tpl=new template_admin();
    return (bool)$tpl->IsSecurity("AsDnsAdministrator");
}

// Sockets ouverts par adresse d'ecoute, comme acls.AcceptThreads() : DNSDistAcceptThreads (32 max),
// sinon min(CPU, 4). Au-dela de 1, ces sockets partagent le port : SO_REUSEPORT est obligatoire,
// sans lui dnsdist n'ecoute plus du tout (constate le 2026-11-16 sur la 4.199).
function ddst_accept_threads():int{
    $v=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistAcceptThreads"));
    if($v>0){ return min($v,32); }
    $n=0;
    $cpu=@file_get_contents("/proc/cpuinfo");
    if(is_string($cpu)){ $n=preg_match_all('/^processor\s*:/m',$cpu); }
    if($n<1){ $n=1; }
    return min($n,4);
}

function ddst_restart_service():void{
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/php/restart");
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"))==1){
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/reload");
    }
}

// --- Enregistrement -----------------------------------------------------------

function save():bool{
    $tpl=new template_admin();
    if(!ddst_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}"); return false; }

    if(isset($_POST["DNSDistCheckInterval"]) && intval($_POST["DNSDistCheckInterval"])<2){
        echo $tpl->post_error("{ddst_err_interval}"); return false;
    }
    if(isset($_POST["DNSDistCheckTimeout"]) && intval($_POST["DNSDistCheckTimeout"])<3){
        echo $tpl->post_error("{ddst_err_timeout}"); return false;
    }
    if(isset($_POST["DNSDistMaxCheckFailures"]) && intval($_POST["DNSDistMaxCheckFailures"])<1){
        echo $tpl->post_error("{ddst_err_failures}"); return false;
    }
    if(isset($_POST["DNSDistCheckName"]) && !preg_match('/^[A-Za-z0-9._-]+$/',trim((string)$_POST["DNSDistCheckName"]))){
        echo $tpl->post_error("{ddst_err_checkname}"); return false;
    }
    foreach(array("DNSDistQps","MaxQPSIPRule","UnBoundCacheSize","temporaryFailureTTL","setStaleCacheEntriesTTL",
                  "EnforceUserDNSTTL","setUDPMultipleMessagesVectorSize","setMaxUDPOutstanding","setMaxTCPClientThreads",
                  "setMaxTCPQueuedConnections","setMaxTCPConnectionsPerClient","setTCPRecvTimeout","setTCPSendTimeout",
                  "tcpFastOpenQueueSize","tcpListenQueueSize","maxInFlight") as $k){
        if(isset($_POST[$k]) && intval($_POST[$k])<0){ echo $tpl->post_error("{ddst_err_negative}"); return false; }
    }

    // Le demon lit DNSDISTDisableReusePort (option active par defaut) : la case
    // « Port sharing » ecrivait DNSDISTReusePort, une cle qu'il ne lit pas.
    if(isset($_POST["DNSDISTReusePort"]) && intval($_POST["DNSDISTReusePort"])!=1 && ddst_accept_threads()>1){
        echo $tpl->post_error(str_replace("%s",ddst_accept_threads(),$tpl->_ENGINE_parse_body("{ddst_err_reuseport}")));
        return false;
    }
    if(isset($_POST["DNSDISTReusePort"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSDISTDisableReusePort",intval($_POST["DNSDISTReusePort"])==1?0:1);
        unset($_POST["DNSDISTReusePort"]);
    }
    // Le demon lit EnforceUserDNSTTLEnable : la page n'ecrivait que DisableEnforceUserDNSTTL,
    // la fonction ne pouvait donc pas etre activee.
    if(isset($_POST["EnableEnforceUserDNSTTL"])){
        $on=intval($_POST["EnableEnforceUserDNSTTL"])==1?1:0;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnforceUserDNSTTLEnable",$on);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DisableEnforceUserDNSTTL",$on==1?0:1);
        unset($_POST["EnableEnforceUserDNSTTL"]);
    }
    $tpl->SAVE_POSTs(null,"Save DNS Firewall Main Configuration");
    ddst_restart_service();
    return true;
}

function ddst_threat_save():bool{
    $tpl=new template_admin();
    if(!ddst_can_write()){ echo $tpl->post_error("{ERROR_NO_PRIVS2}"); return false; }
    $tpl->CLEAN_POST();
    $white=trim((string)($_POST["DNSDISTDynamicWhite"] ?? ""));
    $white=preg_replace('/\s+/',"",$white);
    if($white!=="" && !preg_match('#^[0-9A-Fa-f:./,]+$#',$white)){
        echo $tpl->post_error("{ddst_err_white}"); return false;
    }
    $maxReq=intval($_POST["DNSDISTDynamicMaxReq"] ?? 0);
    $maxSec=intval($_POST["DNSDISTDynamicMaxSec"] ?? 0);
    $blockSec=intval($_POST["DNSDISTDynamicBlockSec"] ?? 0);
    if($maxReq<1 || $maxSec<5 || $blockSec<2){
        echo $tpl->post_error("{ddst_err_dynblock}"); return false;
    }
    $s=$GLOBALS["CLASS_SOCKETS"];
    $malware=intval($_POST["DNSDistBlockMalware"] ?? 0)==1?1:0;
    $cats=intval($_POST["EnableCategories"] ?? 0)==1?1:0;
    $dyn=intval($_POST["DNSDISTDynamicBlocks"] ?? 0)==1?1:0;
    $s->SET_INFO("DNSDistBlockMalware",$malware);
    $s->SET_INFO("DNSDistDisableCategories",$cats==1?0:1);
    $s->SET_INFO("DNSDISTDynamicBlocks",$dyn);
    $s->SET_INFO("DNSDISTDynamicMaxReq",$maxReq);
    $s->SET_INFO("DNSDISTDynamicMaxSec",$maxSec);
    $s->SET_INFO("DNSDISTDynamicBlockSec",$blockSec);
    $s->SET_INFO("DNSDISTDynamicWhite",$white);
    ddst_restart_service();
    return admin_tracks("Save DNS Firewall threat protection (malware=$malware, categories=$cats, dynamic blocks=$dyn $maxReq/{$maxSec}s block {$blockSec}s)");
}

// --- Modele -------------------------------------------------------------------

function ddst_times():array{
    return array(-1=>"{ddst_not_used}",10=>"10 {ddst_seconds}",20=>"20 {ddst_seconds}",30=>"30 {ddst_seconds}",60=>"1 {ddst_minute}",
        300=>"5 {ddst_minutes}",900=>"15 {ddst_minutes}",1800=>"30 {ddst_minutes}",3600=>"1 {ddst_hour}",7200=>"2 {ddst_hours}",
        10800=>"3 {ddst_hours}",14400=>"4 {ddst_hours}",28800=>"8 {ddst_hours}",57600=>"16 {ddst_hours}",86400=>"1 {ddst_day}",
        172800=>"2 {ddst_days}",604800=>"7 {ddst_days}");
}

function ddst_policies():array{
    return array("leastOutstanding"=>"{ddst_policy_least}","roundrobin"=>"{ddst_policy_rr}",
        "chashed"=>"{ddst_policy_hashed}","firstAvailable"=>"{ddst_policy_first}");
}

function ddst_udp_buffers():array{
    return array(0=>"{ddst_default}",4194304=>"4 MB",8388608=>"8 MB",12582912=>"12 MB",16777216=>"16 MB",25165824=>"24 MB");
}

function ddst_split_list(string $v):array{
    $out=array();
    foreach(preg_split('/[\s,]+/',$v) as $x){ $x=trim($x); if(strlen($x)>=2){ $out[]=$x; } }
    return $out;
}

function ddst_networks():array{
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $results=$q->QUERY_SQL("SELECT address FROM pdns_restricts");
    $list=array();
    if(is_array($results)){
        foreach($results as $ligne){ $a=trim((string)($ligne["address"] ?? "")); if($a!==""){ $list[]=$a; } }
    }
    if(count($list)==0){
        return array("list"=>array("192.168.0.0/16","10.0.0.0/8","172.16.0.0/12","127.0.0.0/8"),"default"=>true);
    }
    return array("list"=>$list,"default"=>false);
}

function ddst_model():array{
    $s=$GLOBALS["CLASS_SOCKETS"];
    $i=function($k) use($s){ return intval($s->GET_INFO($k)); };
    $t=function($k) use($s){ return trim((string)$s->GET_INFO($k)); };
    $m=array();
    $m["open"]=$i("DNSAllowOpenResolver")==1;
    $m["networks"]=$m["open"]?array("list"=>array(),"default"=>false):ddst_networks();
    $m["qps"]=$i("DNSDistQps");
    $m["qps_ip"]=$i("MaxQPSIPRule");
    $m["malware"]=$i("DNSDistBlockMalware")==1;
    $m["categories"]=$i("DNSDistDisableCategories")!=1;
    $m["dyn"]=$i("DNSDISTDynamicBlocks")==1;
    $m["dyn_req"]=$i("DNSDISTDynamicMaxReq"); if($m["dyn_req"]<=0){$m["dyn_req"]=50;}
    $m["dyn_sec"]=$i("DNSDISTDynamicMaxSec"); if($m["dyn_sec"]<=0){$m["dyn_sec"]=20;} if($m["dyn_sec"]<5){$m["dyn_sec"]=10;}
    $m["dyn_block"]=$i("DNSDISTDynamicBlockSec"); if($m["dyn_block"]<=0){$m["dyn_block"]=60;} if($m["dyn_block"]<2){$m["dyn_block"]=2;}
    $m["dyn_white"]=$t("DNSDISTDynamicWhite"); if($m["dyn_white"]===""){$m["dyn_white"]="127.0.0.0/8,192.168.0.0/16,10.0.0.0/16";}
    $m["listen"]=ddst_split_list($t("PowerDNSListenAddr"));
    $m["outgoing"]=$t("UnboundOutGoingInterface");
    $m["reuseport"]=$i("DNSDISTDisableReusePort")!=1;
    $m["threads"]=ddst_accept_threads();
    $m["policy"]=$t("DNSDistSetServerPolicy"); if($m["policy"]===""){$m["policy"]="leastOutstanding";}
    $m["check_name"]=$t("DNSDistCheckName"); if($m["check_name"]===""){$m["check_name"]="a.root-servers.net";}
    $m["check_interval"]=$i("DNSDistCheckInterval"); if($m["check_interval"]<2){$m["check_interval"]=2;}
    $m["check_timeout"]=$i("DNSDistCheckTimeout"); if($m["check_timeout"]<3){$m["check_timeout"]=3;}
    $m["check_failures"]=$i("DNSDistMaxCheckFailures"); if($m["check_failures"]<=0){$m["check_failures"]=3;}
    $m["servfail"]=$i("setServFailWhenNoServer")==1;
    $m["edns"]=$i("DNSDisteDNS")==1;
    $m["edns32"]=$i("DNSDisteDNS32")==1;
    $m["edns24"]=$i("DNSDisteDNS24")==1;
    $m["debug"]=$i("DNSDistDebug")==1;
    $m["cache_size"]=$i("UnBoundCacheSize"); if($m["cache_size"]<=0){$m["cache_size"]=300;}
    $m["ttl_min"]=$i("UnBoundCacheMinTTL");
    $m["ttl_max"]=$i("UnBoundCacheMAXTTL"); if($m["ttl_max"]==0){$m["ttl_max"]=172800;}
    $m["tempfail"]=$i("temporaryFailureTTL");
    $m["stale"]=$i("setStaleCacheEntriesTTL"); if($m["stale"]<=0){$m["stale"]=16600;}
    $m["enforce"]=$i("EnforceUserDNSTTLEnable")==1;
    $m["enforce_ttl"]=$i("EnforceUserDNSTTL"); if($m["enforce_ttl"]<=0){$m["enforce_ttl"]=3600;}
    foreach(array("setUDPSocketBufferSizes","setUDPMultipleMessagesVectorSize","setMaxUDPOutstanding","setMaxTCPClientThreads",
                  "setMaxTCPQueuedConnections","setMaxTCPConnectionsPerClient","setTCPRecvTimeout","setTCPSendTimeout",
                  "tcpFastOpenQueueSize","tcpListenQueueSize","maxInFlight") as $k){ $m["adv"][$k]=$i($k); }
    return $m;
}

// --- Rendu : petits elements partages -----------------------------------------

function ddst_duration(int $sec):string{
    $times=ddst_times();
    if($sec<0){ return "{ddst_not_used}"; }
    if(isset($times[$sec])){ return $times[$sec]; }
    if($sec<120){ return "$sec {ddst_seconds}"; }
    if($sec<7200){ return round($sec/60)." {ddst_minutes}"; }
    if($sec<172800){ return round($sec/3600,1)." {ddst_hours}"; }
    return round($sec/86400,1)." {ddst_days}";
}

function ddst_onoff(bool $on):string{
    if($on){ return "<span class='ak-pill ak-ok'><i class='fa-solid fa-circle-check'></i> {ddst_on}</span>"; }
    return "<span class='ak-pill ak-off'><i class='fa-regular fa-circle'></i> {ddst_off}</span>";
}

function ddst_row(string $label,string $value,string $help=""):string{
    $h=$help===""?"":"<div class='ddst-help'>$help</div>";
    return "<div class='ddst-row'><div class='ddst-row-l'><div class='ddst-lbl'>$label</div>$h</div><div class='ddst-val'>$value</div></div>";
}

function ddst_edit_button(string $js,string $label="{ddst_edit}"):string{
    if(!ddst_can_write()){
        $tpl=new template_admin();
        $t=htmlspecialchars(strip_tags($tpl->_ENGINE_parse_body("{ddst_no_privs}")),ENT_QUOTES);
        return "<span title='$t'><button type='button' class='ak-btn ak-btn-sm' disabled><i class='fa-solid fa-lock'></i> $label</button></span>";
    }
    return "<button type='button' class='ak-btn ak-btn-sm' OnClick=\"$js\"><i class='fa-solid fa-pen'></i> $label</button>";
}

function ddst_card_head(string $ico,string $tone,string $title,string $explain,string $buttons):string{
    return "<div class='ddst-head'><span class='ak-ico ak-ico-sm ak-ico-sq $tone'><i class='$ico'></i></span>"
        ."<div class='ddst-head-t'><div class='ddst-title'>$title</div><div class='ddst-sub'>$explain</div></div>"
        ."<div class='ddst-head-b'>$buttons</div></div>";
}

function ddst_chips(array $items,int $max,string $tone="ak-off"):string{
    $h=array();
    $n=0;
    foreach($items as $x){
        $n++;
        if($n>$max){ break; }
        $h[]="<span class='ak-chip $tone'>".htmlspecialchars($x)."</span>";
    }
    $more=count($items)-$max;
    if($more>0){
        $tpl=new template_admin();
        $h[]="<span class='ak-count'>".str_replace("%s",$more,$tpl->_ENGINE_parse_body("{ddst_more}"))."</span>";
    }
    return "<div class='ddst-chips'>".implode("",$h)."</div>";
}

// --- Rendu : un widget par carte ------------------------------------------------

function ddst_banner_nointerface(array $m):string{
    if(count($m["listen"])>0){ return ""; }
    $page=CurrentPageName();
    $btn=ddst_can_write()?"<button type='button' class='ak-btn ak-btn-sm' OnClick=\"Loadjs('$page?dnsdist-interface-js=yes');\"><i class='fa-solid fa-network-wired'></i> {ddst_choose_interfaces}</button>":"";
    return "<div class='ak-banner ak-err ddst-banner'><i class='fa-solid fa-triangle-exclamation'></i>"
        ."<div class='ak-banner-m'><strong>{ddst_nointerface_title}</strong> {ddst_nointerface_text}</div>$btn</div>";
}

function ddst_card_access(array $m):string{
    $page=CurrentPageName();
    $buttons=ddst_edit_button("Loadjs('fw.pdns.restrictions.php?ViaSpopup=yes');","{ddst_edit_networks}")
        .ddst_edit_button("Loadjs('$page?dnsdist-security-js=yes');","{ddst_edit_limits}");
    $rows=array();
    if($m["open"]){
        $rows[]=ddst_row("{ddst_allowed_networks}","<span class='ak-tag ak-warn'><i class='fa-solid fa-triangle-exclamation'></i> {ddst_open_everyone}</span>","{ddst_open_everyone_help}");
    }else{
        $help=$m["networks"]["default"]?"{ddst_networks_default_help}":"";
        $rows[]=ddst_row("{ddst_allowed_networks}",ddst_chips($m["networks"]["list"],4),$help);
    }
    $rows[]=ddst_row("{ddst_qps_all}",$m["qps"]>0?number_format($m["qps"],0,".",","):"{ddst_unlimited}");
    $rows[]=ddst_row("{ddst_qps_ip}",$m["qps_ip"]>0?number_format($m["qps_ip"],0,".",","):"{ddst_unlimited}");
    return "<div class='ak-card ddst-card'>".ddst_card_head("fa-solid fa-shield-halved","ak-accent","{ddst_access_title}","{ddst_access_explain}",$buttons)
        ."<div class='ddst-rows'>".implode("",$rows)."</div></div>";
}

function ddst_card_threat(array $m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $dynHelp="{ddst_dyn_off_help}";
    if($m["dyn"]){
        $dynHelp=str_replace(array("%r","%s","%b","%w"),
            array($m["dyn_req"],$m["dyn_sec"],$m["dyn_block"],htmlspecialchars(str_replace(",",", ",$m["dyn_white"]))),
            $tpl->_ENGINE_parse_body("{ddst_dyn_on_help}"));
    }
    $rows=array();
    $rows[]=ddst_row("{blks_dns_malware}",ddst_onoff($m["malware"]),"{ddst_malware_help}");
    $rows[]=ddst_row("{lookUpDomainCategories}",ddst_onoff($m["categories"]),"{ddst_categories_help}");
    $rows[]=ddst_row("{ddst_dynblock}",ddst_onoff($m["dyn"]),$dynHelp);
    return "<div class='ak-card ddst-card'>".ddst_card_head("fa-solid fa-bug","ak-err","{ddst_threat_title}","{ddst_threat_explain}",
            ddst_edit_button("Loadjs('$page?dnsdist-threat-js=yes');"))
        ."<div class='ddst-rows'>".implode("",$rows)."</div></div>";
}

function ddst_reuseport_help(array $m):string{
    if($m["threads"]<=1){ return "{ddst_reuseport_help}"; }
    $tpl=new template_admin();
    return str_replace("%s",$m["threads"],$tpl->_ENGINE_parse_body("{ddst_reuseport_required_help}"));
}

function ddst_reuseport_row(array $m):string{
    $val=ddst_onoff($m["reuseport"]);
    if(!$m["reuseport"] && $m["threads"]>1){
        $val.=" <span class='ak-tag ak-err'><i class='fa-solid fa-circle-xmark'></i> {ddst_reuseport_broken}</span>";
    }
    return ddst_row("{ddst_reuseport}",$val,ddst_reuseport_help($m));
}

function ddst_card_listening(array $m):string{
    $page=CurrentPageName();
    $listen=count($m["listen"])>0?ddst_chips($m["listen"],6,"ak-accent"):"<span class='ak-tag ak-err'><i class='fa-solid fa-circle-xmark'></i> {ddst_none}</span>";
    $out=$m["outgoing"]===""?"{ddst_system_default}":"<span class='ak-chip'>".htmlspecialchars($m["outgoing"])."</span>";
    $rows=array();
    $rows[]=ddst_row("{listen_interfaces}",$listen);
    $rows[]=ddst_row("{outgoing_interface}",$out);
    $rows[]=ddst_reuseport_row($m);
    return "<div class='ak-card ddst-card'>".ddst_card_head("fa-solid fa-network-wired","ak-info","{ddst_listening_title}","{ddst_listening_explain}",
            ddst_edit_button("Loadjs('$page?dnsdist-interface-js=yes');"))
        ."<div class='ddst-rows'>".implode("",$rows)."</div></div>";
}

function ddst_card_balancing(array $m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $pol=ddst_policies();
    $policy=isset($pol[$m["policy"]])?$pol[$m["policy"]]:htmlspecialchars($m["policy"]);
    $check=str_replace(array("%n","%i","%t","%f"),
        array("<span class='ak-mono'>".htmlspecialchars($m["check_name"])."</span>",$m["check_interval"],$m["check_timeout"],$m["check_failures"]),
        $tpl->_ENGINE_parse_body("{ddst_check_line}"));
    $edns=ddst_onoff($m["edns"]);
    $ednsHelp="{ddst_edns_help}";
    if($m["edns"]){
        $nets=array();
        if($m["edns32"]){$nets[]="/32";}
        if($m["edns24"]){$nets[]="/24";}
        if(count($nets)>0){ $edns.=" <span class='ak-chip'>".implode(" ",$nets)."</span>"; }
    }
    $debug=ddst_onoff($m["debug"]);
    if($m["debug"]){ $debug.=" <span class='ak-tag ak-warn'><i class='fa-solid fa-triangle-exclamation'></i> {ddst_verbose_logging}</span>"; }
    $rows=array();
    $rows[]=ddst_row("{ddst_policy}","<strong>$policy</strong>");
    $rows[]=ddst_row("{ddst_health_check}","<span class='ddst-wrap'>$check</span>");
    $rows[]=ddst_row("{ddst_servfail}",ddst_onoff($m["servfail"]),"{ddst_servfail_help}");
    $rows[]=ddst_row("{ddst_edns}",$edns,$ednsHelp);
    $rows[]=ddst_row("{debug_mode}",$debug);
    return "<div class='ak-card ddst-card'>".ddst_card_head("fa-solid fa-scale-balanced","ak-violet","{ddst_balancing_title}","{ddst_balancing_explain}",
            ddst_edit_button("Loadjs('$page?dnsdist-ha-js=yes');"))
        ."<div class='ddst-rows'>".implode("",$rows)."</div></div>";
}

function ddst_card_cache(array $m):string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    // Le demon dimensionne le cache a 512 octets par reponse : Mo x 1024 x 1024 / 512 (Acls.go).
    $entries=number_format($m["cache_size"]*2048,0,".",",");
    $cap=$m["cache_size"]." MB &middot; ".str_replace("%s",$entries,$tpl->_ENGINE_parse_body("{ddst_entries_value}"));
    $stale=str_replace("%s",distanceOfTimeInWords(time(),time()+$m["stale"]),$tpl->_ENGINE_parse_body("{ddst_up_to}"));
    $enforce=$m["enforce"]?"<strong>".ddst_duration($m["enforce_ttl"])."</strong>":ddst_onoff(false);
    $left=array(); $right=array();
    $left[]=ddst_row("{ddst_cache_capacity}","<strong>$cap</strong>","{ddst_cache_capacity_help}");
    $left[]=ddst_row("{ddst_ttl_min}",ddst_duration($m["ttl_min"]==0?-1:$m["ttl_min"]));
    $left[]=ddst_row("{ddst_stale}",$stale,"{ddst_stale_help}");
    $right[]=ddst_row("{ddst_ttl_max}",ddst_duration($m["ttl_max"]==-1?-1:$m["ttl_max"]));
    // Envoye tel quel a newPacketCache(), 0 compris : pas de « Default » ici.
    $right[]=ddst_row("{ddst_tempfail}",ddst_duration(max(0,$m["tempfail"])),"{ddst_tempfail_help}");
    $right[]=ddst_row("{ddst_enforce}",$enforce,"{ddst_enforce_help}");
    return "<div class='ak-card ddst-card ddst-full'>".ddst_card_head("fa-solid fa-database","ak-accent","{cache}","{ddst_cache_explain}",
            ddst_edit_button("Loadjs('$page?dnsdist-cache-js=yes');"))
        ."<div class='ddst-cols'><div class='ddst-rows'>".implode("",$left)."</div><div class='ddst-rows'>".implode("",$right)."</div></div></div>";
}

function ddst_card_advanced(array $m):string{
    $page=CurrentPageName();
    $buf=ddst_udp_buffers();
    $a=$m["adv"];
    $v=function($n){ return intval($n)>0?number_format(intval($n),0,".",","):"{ddst_default}"; };
    $changed=0;
    foreach($a as $x){ if(intval($x)>0){$changed++;} }
    $left=array(); $right=array();
    $left[]=ddst_row("{ddst_adv_udpbuf}",isset($buf[$a["setUDPSocketBufferSizes"]])?$buf[$a["setUDPSocketBufferSizes"]]:$v($a["setUDPSocketBufferSizes"]));
    $left[]=ddst_row("{ddst_adv_udpvec}",$v($a["setUDPMultipleMessagesVectorSize"]));
    $left[]=ddst_row("{ddst_adv_udpout}",$v($a["setMaxUDPOutstanding"]));
    $left[]=ddst_row("{ddst_adv_tcpthreads}",$v($a["setMaxTCPClientThreads"]));
    $left[]=ddst_row("{ddst_adv_tcpqueued}",$v($a["setMaxTCPQueuedConnections"]));
    $left[]=ddst_row("{ddst_adv_tcpperclient}",$v($a["setMaxTCPConnectionsPerClient"]));
    $right[]=ddst_row("{ddst_adv_tcprecv}",intval($a["setTCPRecvTimeout"])>0?intval($a["setTCPRecvTimeout"])." {ddst_seconds}":"{ddst_default}");
    $right[]=ddst_row("{ddst_adv_tcpsend}",intval($a["setTCPSendTimeout"])>0?intval($a["setTCPSendTimeout"])." {ddst_seconds}":"{ddst_default}");
    $right[]=ddst_row("{ddst_adv_tfo}",$v($a["tcpFastOpenQueueSize"]));
    $right[]=ddst_row("{ddst_adv_listenq}",$v($a["tcpListenQueueSize"]));
    $right[]=ddst_row("{ddst_adv_inflight}",$v($a["maxInFlight"]));
    $tpl=new template_admin();
    $state=$changed==0?"<span class='ak-tag ak-off'>{ddst_all_default}</span>"
        :"<span class='ak-tag ak-warn'>".str_replace("%s",$changed,$tpl->_ENGINE_parse_body("{ddst_n_changed}"))."</span>";
    return "<details class='ak-card ak-details ddst-card ddst-full ddst-adv'>"
        ."<summary><span class='ak-ico ak-ico-sm ak-ico-sq ak-off'><i class='fa-solid fa-sliders'></i></span>"
        ."<span class='ddst-head-t'><span class='ddst-title'>{ddst_adv_title}</span> $state<span class='ddst-sub' style='display:block'>{ddst_adv_explain}</span></span>"
        ."<i class='fa-solid fa-chevron-down ak-chev'></i></summary>"
        ."<div class='ddst-adv-body'><div class='ddst-adv-bar'>".ddst_edit_button("Loadjs('$page?udp-tcp-performance-js=yes');","{ddst_edit_advanced}")."</div>"
        ."<div class='ddst-cols'><div class='ddst-rows'>".implode("",$left)."</div><div class='ddst-rows'>".implode("",$right)."</div></div></div>"
        ."</details>";
}

function ddst_style():string{
    if(isset($GLOBALS["DDST_STYLE"])){return "";}
    $GLOBALS["DDST_STYLE"]=true;
    return "<style>
#ddst-root{display:flex;flex-direction:column;gap:20px;margin-top:15px}
#ddst-root .ddst-intro{font-size:13px;color:#64748b}
#ddst-root .ddst-banner{align-items:center}
#ddst-root .ddst-banner .ak-banner-m{flex:1}
#ddst-root .ddst-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;align-items:start}
#ddst-root .ddst-card{padding:20px;box-sizing:border-box;min-width:0}
#ddst-root .ddst-full{grid-column:1 / -1}
#ddst-root .ddst-head{display:flex;flex-wrap:wrap;align-items:flex-start;gap:12px;padding-bottom:14px;border-bottom:1px solid #f1f5f9;margin-bottom:4px}
#ddst-root .ddst-head-t{flex:1 1 220px;min-width:0}
#ddst-root .ddst-head-b{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;margin-left:auto}
#ddst-root .ddst-title{font-size:15px;font-weight:600;color:#0f172a}
#ddst-root .ddst-sub{font-size:13px;color:#64748b;margin-top:2px;line-height:1.45}
#ddst-root .ddst-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;min-height:40px;padding:9px 0;border-bottom:1px solid #f1f5f9;box-sizing:border-box}
#ddst-root .ddst-rows .ddst-row:last-child{border-bottom:0}
#ddst-root .ddst-row-l{flex:1;min-width:0}
#ddst-root .ddst-lbl{font-size:13px;color:#334155;font-weight:500;line-height:20px}
#ddst-root .ddst-help{font-size:13px;color:#64748b;line-height:1.45;margin-top:2px}
#ddst-root .ddst-val{font-size:13px;color:#0f172a;text-align:right;flex:0 1 auto;max-width:58%;line-height:20px}
#ddst-root .ddst-wrap{display:inline-block;text-align:right}
#ddst-root .ddst-chips{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end}
#ddst-root .ddst-cols{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 32px}
#ddst-root .ddst-adv summary{display:flex;align-items:center;gap:12px;cursor:pointer;list-style:none}
#ddst-root .ddst-adv summary::-webkit-details-marker{display:none}
#ddst-root .ddst-adv[open] summary{padding-bottom:14px;border-bottom:1px solid #f1f5f9;margin-bottom:4px}
#ddst-root .ddst-adv .ak-chev{transition:transform .15s}
#ddst-root .ddst-adv[open] .ak-chev{transform:rotate(180deg)}
#ddst-root .ddst-adv summary .ddst-sub{font-weight:400}
#ddst-root .ddst-adv summary .ddst-title{margin-right:6px}
#ddst-root .ddst-adv-bar{display:flex;justify-content:flex-end;padding:10px 0 2px 0}
@media (max-width:1200px){#ddst-root .ddst-grid{grid-template-columns:minmax(0,1fr)}}
@media (prefers-reduced-motion:reduce){#ddst-root *{transition:none!important}}
</style>";
}

// --- Page ---------------------------------------------------------------------

function ddst_overview():bool{
    $tpl=new template_admin();
    $m=ddst_model();
    $h=array();
    $h[]="<div id='ddst-root' class='ak'>";
    $h[]="<div class='ddst-intro'>{ddst_intro}</div>";
    $h[]=ddst_banner_nointerface($m);
    $h[]="<div class='ddst-grid'>";
    $h[]=ddst_card_access($m);
    $h[]=ddst_card_threat($m);
    $h[]=ddst_card_listening($m);
    $h[]=ddst_card_balancing($m);
    $h[]=ddst_card_cache($m);
    $h[]=ddst_card_advanced($m);
    $h[]="</div></div>";
    echo ddst_style().$tpl->_ENGINE_parse_body(implode("\n",$h))."<script>NoSpinner();</script>";
    return true;
}

function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $TINY_ARRAY=array();
    $TINY_ARRAY["TITLE"]="{APP_DNSDIST}: {general_parameters}";
    $TINY_ARRAY["ICO"]="fab fa-gripfire";
    $TINY_ARRAY["EXPL"]="{APP_DNSDIST_EXPLAIN2}";
    $TINY_ARRAY["BUTTONS"]=null;
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";
    // Le widget de statut du service reste a gauche (widget partage), les cartes a droite.
    $html="<table style='width:100%'><tr>
        <td style='vertical-align:top;width:240px'><div id='dnsdist-left' style='margin-top:15px'></div></td>
        <td style='vertical-align:top;padding-left:20px'><div id='ddst-overview'></div></td>
    </tr></table>
    <script>
    $jstiny
    LoadAjaxSilent('dnsdist-left','$page?dnsdist-status-left=yes');
    LoadAjaxSilent('ddst-overview','$page?overview=yes');
    </script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function left_status():bool{
    $tpl=new template_admin();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfw/service/status");
    $bsini=new Bs_IniHandler(PROGRESS_DIR."/dnsdist.status");
    $html[]=$tpl->SERVICE_STATUS($bsini, "APP_DNSDIST",restart_js());
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function restart_js():string{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $f[]="if( document.getElementById('progress-unbound-restart')){";
    $f[]=$tpl->framework_buildjs("/dnsfw/service/php/restart",
        "dnsdist.restart","dnsdist.restart.log",
        "progress-unbound-restart",
        "LoadAjaxSilent('dnsdist-left','$page?dnsdist-status-left=yes');"
    );
    $f[]="}";
    return @implode("",$f);
}

// --- Modales ------------------------------------------------------------------

// Apres un enregistrement reussi : fermer, puis redessiner les cartes et le statut du service
// (save() vient de redemarrer dnsdist).
function ddst_jsafter():string{
    $page=CurrentPageName();
    return "dialogInstance2.close();LoadAjaxSilent('ddst-overview','$page?overview=yes');"
        ."if(document.getElementById('dnsdist-left')){LoadAjaxSilent('dnsdist-left','$page?dnsdist-status-left=yes');}";
}

// $tpl DOIT etre l'instance qui a construit les champs : form_outside() ne poste que les champs
// enregistres dans sa propre instance (une instance neuve postait un formulaire vide).
function ddst_popup(template_admin $tpl,string $title,string $explain,array $form,bool $warn=false):void{
    $html=array();
    // L'effet de « Apply » (redemarrage = quelques secondes sans reponse DNS) est dit AVANT l'action.
    $html[]=$tpl->div_explain("$title||$explain{ddst_restart_notice}");
    if($warn){ $html[]=$tpl->div_warning("{ddst_adv_warning}"); }
    $html[]=$tpl->form_outside("",$form,"","{ddst_apply_restart}",ddst_jsafter(),"AsDnsAdministrator");
    echo $tpl->_ENGINE_parse_body($html);
}

function dnsdist_security_js():bool{
    $tpl=new template_admin();
    return $tpl->js_dialog2("{ddst_access_title}",CurrentPageName()."?dnsdist-security-popup=yes",600);
}
function dnsdist_security_popup():bool{
    $tpl=new template_admin();
    $m=ddst_model();
    $form=array();
    $form[]=$tpl->field_numeric("DNSDistQps","{ddst_qps_all}",$m["qps"],"{ddst_qps_all_help}");
    $form[]=$tpl->field_numeric("MaxQPSIPRule","{ddst_qps_ip}",$m["qps_ip"],"{ddst_qps_ip_help}");
    ddst_popup($tpl,"{ddst_limits_title}","{ddst_limits_explain}",$form);
    return true;
}

function ddst_threat_js():bool{
    $tpl=new template_admin();
    return $tpl->js_dialog2("{ddst_threat_title}",CurrentPageName()."?dnsdist-threat-popup=yes",700);
}
function ddst_threat_popup():bool{
    $tpl=new template_admin();
    $m=ddst_model();
    $form=array();
    $form[]=$tpl->field_hidden("ddst-threat","1");
    $form[]=$tpl->field_checkbox("DNSDistBlockMalware","{blks_dns_malware}",$m["malware"]?1:0,false,"{ddst_malware_help}");
    $form[]=$tpl->field_checkbox("EnableCategories","{lookUpDomainCategories}",$m["categories"]?1:0,false,"{ddst_categories_help}");
    $form[]=$tpl->field_section("{ddst_dynblock}","{ddst_dynblock_explain}");
    $form[]=$tpl->field_checkbox("DNSDISTDynamicBlocks","{ddst_dynblock_enable}",$m["dyn"]?1:0,false,"{ddst_dynblock_enable_help}");
    $form[]=$tpl->field_numeric("DNSDISTDynamicMaxReq","{ddst_dyn_maxreq}",$m["dyn_req"],"{ddst_dyn_maxreq_help}");
    $form[]=$tpl->field_numeric("DNSDISTDynamicMaxSec","{ddst_dyn_window}",$m["dyn_sec"],"{ddst_dyn_window_help}");
    $form[]=$tpl->field_numeric("DNSDISTDynamicBlockSec","{ddst_dyn_block}",$m["dyn_block"],"{ddst_dyn_block_help}");
    $form[]=$tpl->field_text("DNSDISTDynamicWhite","{ddst_dyn_white}",$m["dyn_white"],false,"{ddst_dyn_white_help}");
    ddst_popup($tpl,"{ddst_threat_title}","{ddst_threat_explain}",$form);
    return true;
}

function dnsdist_interfaces_js():bool{
    $tpl=new template_admin();
    return $tpl->js_dialog2("{ddst_listening_title}",CurrentPageName()."?dnsdist-interface-popup=yes",650);
}
function dnsdist_interfaces_popup():bool{
    $tpl=new template_admin();
    $m=ddst_model();
    $form=array();
    $form[]=$tpl->field_interfaces_choose("PowerDNSListenAddr","{listen_interfaces}",implode(",",$m["listen"]),"{ddst_listen_help}");
    $form[]=$tpl->field_interfaces("UnboundOutGoingInterface","{outgoing_interface}",$m["outgoing"],"{ddst_outgoing_help}");
    if($m["threads"]<=1 || !$m["reuseport"]){
        // Coupe alors que plusieurs threads ecoutent : la case reste proposee pour le remettre en marche.
        $form[]=$tpl->field_checkbox("DNSDISTReusePort","{ddst_reuseport}",$m["reuseport"]?1:0,false,ddst_reuseport_help($m));
    }
    ddst_popup($tpl,"{ddst_listening_title}","{ddst_listening_explain}",$form);
    return true;
}

function dnsdist_ha_js():bool{
    $tpl=new template_admin();
    return $tpl->js_dialog2("{ddst_balancing_title}",CurrentPageName()."?dnsdist-ha-popup=yes",720);
}
function dnsdist_ha_popup():bool{
    $tpl=new template_admin();
    $m=ddst_model();
    $form=array();
    $form[]=$tpl->field_array_hash(ddst_policies(),"DNSDistSetServerPolicy","nonull:{ddst_policy}",$m["policy"],false,"{ddst_policy_help}");
    $form[]=$tpl->field_text("DNSDistCheckName","{ddst_check_name}",$m["check_name"],true,"{ddst_check_name_help}");
    $form[]=$tpl->field_numeric("DNSDistCheckInterval","{ddst_check_interval}",$m["check_interval"],"{ddst_check_interval_help}");
    $form[]=$tpl->field_numeric("DNSDistCheckTimeout","{ddst_check_timeout}",$m["check_timeout"],"{ddst_check_timeout_help}");
    $form[]=$tpl->field_numeric("DNSDistMaxCheckFailures","{ddst_check_failures}",$m["check_failures"],"{ddst_check_failures_help}");
    $form[]=$tpl->field_checkbox("setServFailWhenNoServer","{ddst_servfail}",$m["servfail"]?1:0,false,"{ddst_servfail_help}");
    $form[]=$tpl->field_section("{ddst_edns}","{ddst_edns_help}");
    $form[]=$tpl->field_checkbox("DNSDisteDNS","{ddst_edns_enable}",$m["edns"]?1:0,false,"{ddst_edns_enable_help}");
    $form[]=$tpl->field_checkbox("DNSDisteDNS32","{ddst_edns32}",$m["edns32"]?1:0,false,"{ddst_edns32_help}");
    $form[]=$tpl->field_checkbox("DNSDisteDNS24","{ddst_edns24}",$m["edns24"]?1:0,false,"{ddst_edns24_help}");
    $form[]=$tpl->field_section("{ddst_troubleshooting}");
    $form[]=$tpl->field_checkbox("DNSDistDebug","{debug_mode}",$m["debug"]?1:0,false,"{ddst_debug_help}");
    ddst_popup($tpl,"{ddst_balancing_title}","{ddst_balancing_explain}",$form);
    return true;
}

function dnsdist_cache_js():bool{
    $tpl=new template_admin();
    return $tpl->js_dialog2("{cache}",CurrentPageName()."?dnsdist-cache-popup=yes",720);
}
function dnsdist_cache_popup():bool{
    $tpl=new template_admin();
    $m=ddst_model();
    $times=ddst_times();
    $min=$m["ttl_min"]==0?-1:$m["ttl_min"];
    $max=$m["ttl_max"];
    // Valeur enregistree absente du catalogue : l'ajouter, sinon le select posterait la premiere option.
    foreach(array($min,$max) as $v){ if(!isset($times[$v])){ $times[$v]=ddst_duration($v); } }
    ksort($times);
    $form=array();
    $form[]=$tpl->field_numeric("UnBoundCacheSize","{ddst_cache_capacity_field}",$m["cache_size"],"{ddst_cache_capacity_field_help}");
    $form[]=$tpl->field_array_hash($times,"UnBoundCacheMinTTL","nonull:{ddst_ttl_min}",$min,false,"{ddst_ttl_min_help}");
    $form[]=$tpl->field_array_hash($times,"UnBoundCacheMAXTTL","nonull:{ddst_ttl_max}",$max,false,"{ddst_ttl_max_help}");
    // UnBoundCacheNEGTTL n'est pas propose : Acls.go le lit mais ne l'ecrit jamais dans dnsdist.conf.
    $form[]=$tpl->field_numeric("temporaryFailureTTL","{ddst_tempfail} ({ddst_seconds})",$m["tempfail"],"{ddst_tempfail_help}");
    $form[]=$tpl->field_numeric("setStaleCacheEntriesTTL","{ddst_stale} ({ddst_seconds})",$m["stale"],"{ddst_stale_help}");
    $form[]=$tpl->field_section("{ddst_enforce}");
    $form[]=$tpl->field_checkbox("EnableEnforceUserDNSTTL","{ddst_enforce_enable}",$m["enforce"]?1:0,false,"{ddst_enforce_help}");
    $form[]=$tpl->field_numeric("EnforceUserDNSTTL","{ddst_enforce_ttl}",$m["enforce_ttl"],"{ddst_enforce_ttl_help}");
    ddst_popup($tpl,"{cache}","{ddst_cache_explain}",$form);
    return true;
}

function udp_tcp_performance_js():bool{
    $tpl=new template_admin();
    return $tpl->js_dialog2("{ddst_adv_title}",CurrentPageName()."?udp-tcp-performance-popup=yes",760);
}
function udp_tcp_performance_popup():bool{
    $tpl=new template_admin();
    $m=ddst_model();
    $a=$m["adv"];
    $buf=ddst_udp_buffers();
    if(!isset($buf[$a["setUDPSocketBufferSizes"]])){ $buf[$a["setUDPSocketBufferSizes"]]=number_format($a["setUDPSocketBufferSizes"],0,".",","); }
    $form=array();
    $form[]=$tpl->field_section("UDP");
    $form[]=$tpl->field_array_hash($buf,"setUDPSocketBufferSizes","nonull:{ddst_adv_udpbuf}",$a["setUDPSocketBufferSizes"],false,"{ddst_adv_udpbuf_help}");
    $form[]=$tpl->field_numeric("setUDPMultipleMessagesVectorSize","{ddst_adv_udpvec}",$a["setUDPMultipleMessagesVectorSize"],"{ddst_adv_udpvec_help}");
    $form[]=$tpl->field_numeric("setMaxUDPOutstanding","{ddst_adv_udpout}",$a["setMaxUDPOutstanding"],"{ddst_adv_udpout_help}");
    $form[]=$tpl->field_section("TCP");
    $form[]=$tpl->field_numeric("setMaxTCPClientThreads","{ddst_adv_tcpthreads}",$a["setMaxTCPClientThreads"],"{ddst_adv_tcpthreads_help}");
    $form[]=$tpl->field_numeric("setMaxTCPQueuedConnections","{ddst_adv_tcpqueued}",$a["setMaxTCPQueuedConnections"],"{ddst_adv_tcpqueued_help}");
    $form[]=$tpl->field_numeric("setMaxTCPConnectionsPerClient","{ddst_adv_tcpperclient}",$a["setMaxTCPConnectionsPerClient"],"{ddst_adv_tcpperclient_help}");
    $form[]=$tpl->field_numeric("setTCPRecvTimeout","{ddst_adv_tcprecv} ({ddst_seconds})",$a["setTCPRecvTimeout"],"{ddst_adv_tcprecv_help}");
    $form[]=$tpl->field_numeric("setTCPSendTimeout","{ddst_adv_tcpsend} ({ddst_seconds})",$a["setTCPSendTimeout"],"{ddst_adv_tcpsend_help}");
    $form[]=$tpl->field_numeric("tcpFastOpenQueueSize","{ddst_adv_tfo}",$a["tcpFastOpenQueueSize"],"{ddst_adv_tfo_help}");
    $form[]=$tpl->field_numeric("tcpListenQueueSize","{ddst_adv_listenq}",$a["tcpListenQueueSize"],"{ddst_adv_listenq_help}");
    $form[]=$tpl->field_numeric("maxInFlight","{ddst_adv_inflight}",$a["maxInFlight"],"{ddst_adv_inflight_help}");
    ddst_popup($tpl,"{ddst_adv_title}","{ddst_adv_explain}",$form,true);
    return true;
}
