<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$users=new usersMenus();if(!$users->AsProxyMonitor){exit();}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["zoom-js"])){zoom_js();exit;}
if(isset($_GET["zoom"])){zoom();exit;}
if(isset($_GET["opts"])){search_opts_js();exit;}
if(isset($_GET["search-opts-popup"])){search_opts_popup();exit;}
if(isset($_GET["search-opts-reset"])){search_opts_reset();exit;}
if(isset($_POST["hacluster_rt_client_ip"])){search_opts_save();exit;}
page();

function page(){
    $page=CurrentPageName();
    $tpl=new template_admin();

    if(!isset($_GET["notitle"])){
        $html=$tpl->page_header("{realtime_requests} - HaCluster",
            "fas fa-eye","{realtime_requests_explain}","$page?notitle=yes");
    }else{
        $options["WRENCH"]="Loadjs('$page?opts=yes&function=%s')";
        $html[]="<div style='margin-top:10px'>";
        $html[]=$tpl->search_block($page,null,null,null,"&notitle=yes",$options);
        $html[]="</div>";
    }
    echo $tpl->_ENGINE_parse_body($html);
}

function search_opts_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog4("{options}","$page?search-opts-popup=yes&function=$function");
}

function search_opts_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    if(!isset($_SESSION["HACLUSTER_RT_SEARCH"])){$_SESSION["HACLUSTER_RT_SEARCH"]=array();}
    $opts=$_SESSION["HACLUSTER_RT_SEARCH"];

    $form[]=$tpl->field_text("hacluster_rt_client_ip","{tcp_address}",$opts["client_ip"] ?? "");
    $form[]=$tpl->field_text("hacluster_rt_username","{member}",$opts["username"] ?? "");
    $form[]=$tpl->field_text("hacluster_rt_ssl_sni","{hostname} (SNI)",$opts["ssl_sni"] ?? "");
    $form[]=$tpl->field_text("hacluster_rt_http_status","{http_status_code}",$opts["http_status"] ?? "");
    $form[]=$tpl->field_text("hacluster_rt_squid_status","{status}",$opts["squid_status"] ?? "");
    $form[]=$tpl->field_text("hacluster_rt_method","{protocol} (Method)",$opts["method"] ?? "");
    $form[]=$tpl->field_text("hacluster_rt_source_host","{source} {hostname}",$opts["source_host"] ?? "");

    $js="dialogInstance4.close();$function()";
    $tpl->form_add_button("{reset}","Loadjs('$page?search-opts-reset=yes&function=$function')");
    echo $tpl->form_outside("{search}",$form,null,"{save}",$js);
    return true;
}

function search_opts_reset():bool{
    $function=$_GET["function"];
    unset($_SESSION["HACLUSTER_RT_SEARCH"]);
    header("content-type: application/x-javascript");
    echo "dialogInstance4.close();$function()";
    return true;
}

function search_opts_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $_SESSION["HACLUSTER_RT_SEARCH"]=array(
        "client_ip"   => trim($_POST["hacluster_rt_client_ip"] ?? ""),
        "username"    => trim($_POST["hacluster_rt_username"] ?? ""),
        "ssl_sni"     => trim($_POST["hacluster_rt_ssl_sni"] ?? ""),
        "http_status" => trim($_POST["hacluster_rt_http_status"] ?? ""),
        "squid_status"=> trim($_POST["hacluster_rt_squid_status"] ?? ""),
        "method"      => trim($_POST["hacluster_rt_method"] ?? ""),
        "source_host" => trim($_POST["hacluster_rt_source_host"] ?? ""),
    );
    return true;
}

function zoom_js(){
    $tpl=new template_admin();
    $page=CurrentPageName();
    $data=urlencode($_GET["data"]);
    $title=$tpl->_ENGINE_parse_body("{realtime_requests}::ZOOM");
    $tpl->js_dialog($title,"$page?zoom=yes&data=$data");
}

function zoom(){
    $tpl=new template_admin();
    $raw=base64_decode(urldecode($_GET["data"]));
    $ev=json_decode($raw);
    if(!is_object($ev)){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}"));
        return;
    }

    $labels=array(
        "seq"              => "Seq",
        "received_at"      => "{zDate} ({received})",
        "timestamp"        => "{zDate}",
        "source_host"      => "{source} {hostname}",
        "client_ip"        => "{tcp_address}",
        "eui"              => "MAC (EUI)",
        "username"         => "{member}",
        "method"           => "{protocol}",
        "http_status"      => "{http_status_code}",
        "squid_status"     => "Squid {status}",
        "hierarchy_code"   => "Hierarchy",
        "reply_size"       => "{size}",
        "response_time_ms" => "{duration}",
        "ssl_sni"          => "SNI ({hostname})",
        "redirect_url"     => "URL",
        "server_ip"        => "{destination} IP",
        "server_addr"      => "{destination}",
        "server_port"      => "{destination} {listen_port}",
        "mime_type"        => "MIME",
        "user_agent"       => "User-Agent",
        "x_forwarded_for"  => "X-Forwarded-For",
        "local_client_ip"  => "{local} IP",
        "instance_id"      => "Instance",
        "error_code"       => "{error} Code",
        "error_detail"     => "{error} Detail",
        "note"             => "Note",
        "attempts"         => "Attempts",
    );

    $html[]="<div class='ibox-content'>";
    $html[]="<table class='table table-bordered'>";

    foreach($labels as $field=>$label){
        if(!property_exists($ev,$field)){continue;}
        $val=$ev->$field;
        if($val==="-" || $val==="" || is_null($val)){continue;}

        if($field=="reply_size"){
            $kb=intval($val)/1024;
            $val=FormatBytes($kb);
        }
        if($field=="response_time_ms"){
            $val="{$val}ms";
        }
        if($field=="epoch_ms"){continue;}
        if($field=="http_status"){
            $code=intval($val);
            if($code>=400){
                $val="<span class='text-danger'><strong>$val</strong></span>";
            }
        }
        if($field=="note" && strlen($val)>1){
            $val=urldecode($val);
        }

        $html[]="<tr><td class='text-capitalize' style='text-align:right;width:30%'>$label:</td>";
        $html[]="<td><strong>$val</strong></td></tr>";
    }

    $html[]="</table></div>";
    echo $tpl->_ENGINE_parse_body(@implode("",$html));
}

function search(){
    $tpl=new template_admin();
    $page=CurrentPageName();

    if($_GET["search"]==null){$_GET["search"]="50 events";}
    $MAIN=$tpl->format_search_protocol($_GET["search"]);

    $maxline=intval($MAIN["MAX"]);
    if($maxline<1){$maxline=50;}
    if($maxline>5000){$maxline=5000;}

    // Build API request
    $fields=array();

    // Global search term from search box
    $term=trim($MAIN["TERM"] ?? "");
    if(strlen($term)>0){
        $fields["*"]=$term;
    }

    // Inline syntax: "src X" → client_ip filter
    if(isset($MAIN["SRC"]) && strlen($MAIN["SRC"])>0){
        $fields["client_ip"]=str_replace("*",".*",$MAIN["SRC"]);
    }
    // Inline syntax: "dst X" → server_addr filter
    if(isset($MAIN["DST"]) && strlen($MAIN["DST"])>0){
        $fields["server_addr"]=str_replace("*",".*",$MAIN["DST"]);
    }
    // Inline syntax: "protocol X" → method filter
    if(isset($MAIN["PROTO"]) && strlen($MAIN["PROTO"])>0){
        $fields["method"]=$MAIN["PROTO"];
    }

    // Advanced search fields from session (wrench popup)
    if(isset($_SESSION["HACLUSTER_RT_SEARCH"])){
        $opts=$_SESSION["HACLUSTER_RT_SEARCH"];
        $searchFields=array("client_ip","username","ssl_sni","http_status","squid_status","method","source_host");
        foreach($searchFields as $f){
            if(isset($opts[$f]) && strlen(trim($opts[$f]))>0){
                $fields[$f]=trim($opts[$f]);
            }
        }
    }

    $data=array("maxline"=>$maxline);
    if(!empty($fields)){
        $data["fields"]=$fields;
    }

    $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/hacluster/proxy/realtime",$data));

    if(!is_object($json) || !property_exists($json,"success")){
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}: API"));
        return;
    }
    if(!$json->success){
        $err="";
        if(property_exists($json,"error")){$err=$json->error;}
        echo $tpl->_ENGINE_parse_body($tpl->div_error("{error}||$err"));
        return;
    }

    if(!isset($json->data) || !isset($json->data->lines) || count($json->data->lines)==0){
        echo $tpl->_ENGINE_parse_body($tpl->div_success("{no_data}"));
        return;
    }

    $_SESSION["PROXY_SEARCH"]=$_GET["search"];

    // Pre-translate headers

    $banico="<i class='fas fa-ban'></i>";
    $alido="<i class='far fa-check-circle'></i>";
    $blacklist_text=$tpl->_ENGINE_parse_body("<strong>$banico&nbsp;{blacklists}</strong>");
    $zdate=$tpl->_ENGINE_parse_body("{zDate}");
    $ipaddr=$tpl->javascript_parse_text("{members}");
    $destination=$tpl->_ENGINE_parse_body("{destinations}");
    $duration=$tpl->_ENGINE_parse_body("{duration}");
    $sizeText=$tpl->_ENGINE_parse_body("{size}");
    $proto=$tpl->_ENGINE_parse_body("{proto}");
    $category=$tpl->_ENGINE_parse_body("{category}");
    $deny=$tpl->_ENGINE_parse_body("{deny}");
    $default_text=$tpl->_ENGINE_parse_body("{default}");
    $webfiltering_error=$tpl->_ENGINE_parse_body("{webfiltering} {error}");
    $today=date("Y-m-d");
    $auth_white=$tpl->_ENGINE_parse_body("<strong style='color:#19937a'>$alido&nbsp;{authentication} {whitelists}</strong>");
    $final_allow=$tpl->_ENGINE_parse_body("<strong style='color:#19937a'>$alido&nbsp;{final_allow}</strong>");
    $deny_remote_ports=$tpl->_ENGINE_parse_body("<strong>$banico&nbsp;{remote_port}</strong>");
    $emergency_text = $tpl->_ENGINE_parse_body("{urgency_mode}");
    $whitelist_text=$tpl->_ENGINE_parse_body("<strong style='color:#19937a'>$alido&nbsp;{whitelist}</strong>");
    $theshield  = "The Shields";

    $html[]="<table class='table table-hover footable'>";
    $html[]="<thead><tr>";
    $html[]="<th>$zdate</th>";
    $html[]="<th>{source}</th>";
    $html[]="<th>$ipaddr</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="<th>$proto</th>";
    $html[]="<th>$category</th>";
    $html[]="<th>URL</th>";
    $html[]="<th>&nbsp;</th>";
    $html[]="<th>$destination</th>";
    $html[]="<th>$sizeText</th>";
    $html[]="<th>$duration</th>";
    $html[]="</tr></thead><tbody>";

    $SIMPLERULES=array();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT ID,aclname FROM webfilters_simpleacls");
    foreach ($results as $index => $ligne) {
        VERBOSE("$index]: ID: $ligne[ID] aclname: $ligne[aclname]",__LINE__);
        $ID = $ligne["ID"];
        $rulename = $ligne["aclname"];
        $SIMPLERULES[$ID]=$rulename;
    }

    foreach($json->data->lines as $ev){
        $color="black";
        $httpStatus=intval($ev->http_status ?? 0);
        $squidStatus=trim($ev->squid_status ?? "");
        $method=trim($ev->method ?? "");
        $theshield_ico="";
        $zCode0="";
        $codeToString="";
        $category="";
        $accessrule="";
        $simplerule="";

        // Color coding
        if($method=="CONNECT"){$color="#BAB700";}
        if($httpStatus>=400){$color="#D0080A";}
        if($httpStatus==0){$color="#D0080A";}
        if($httpStatus==307){$color="#F59C44";}
        if($method<>"CONNECT") {
            if (preg_match("#TCP_HIT|TCP_MEM_HIT|TCP_REFRESH_UNMODIFIED#", $squidStatus)) {
                $color = "#009223";
            }
        }
        if(preg_match("#TCP_DENIED#",$squidStatus)){$color="#D0080A";}

        // Time
        $epochMs=intval($ev->epoch_ms ?? 0);
        $time=date("H:i:s",$epochMs>0 ? intval($epochMs/1000) : 0);
        $fullDate=date("Y-m-d",$epochMs>0 ? intval($epochMs/1000) : 0);
        $dateDisplay=($fullDate==$today) ? $time : "$fullDate $time";

        // Source host
        $sourceHost=trim($ev->source_host ?? "-");

        // Client + user
        $clientIp=trim($ev->client_ip ?? "-");
        $username=trim($ev->username ?? "-");
        $userText="";
        if($username!="-" && strlen($username)>0){
            $userText="/<strong>$username</strong>";
        }
        $ico=($username!="-" && strlen($username)>0) ? ico_user : ico_computer;
        $memberText="<i class='$ico'></i>&nbsp;$clientIp$userText";

        // Status + squid code
        $statusText=$squidStatus;
        if(preg_match("#TCP_DENIED#",$squidStatus)){$statusText=$deny;}
        $statusDisplay="$statusText";
        if($httpStatus>0){
            $statusDisplay="$httpStatus/$statusDisplay";
        }

        // Method/protocol
        $protoDisplay=$method;
        if($method=="CONNECT"){$protoDisplay="SSL";}

        // Domain (SNI or redirect_url)
        $sni=trim($ev->ssl_sni ?? "");
        $redirectUrl=trim($ev->redirect_url ?? "");
        $domain=$sni;
        if(strlen($domain)<2){$domain=$redirectUrl;}
        if(strlen($domain)<2){$domain="-";}

        // Zoom data
        $evJson=base64_encode(json_encode($ev));
        $evEnc=urlencode($evJson);
        $loupe=$tpl->icon_loupe(1,"Loadjs('$page?zoom-js=yes&data=$evEnc')");

        // Destination
        $serverAddr=trim($ev->server_addr ?? "");
        $serverIp=trim($ev->server_ip ?? "");
        $serverPort=intval($ev->server_port ?? 0);
        $destText=$serverAddr;
        if(strlen($destText)<2){$destText=$serverIp;}
        if($serverPort>0 && $serverPort!=80 && $serverPort!=443){
            $destText.=":$serverPort";
        }

        // Size
        $replySize=intval($ev->reply_size ?? 0);
        if($replySize>1024){
            $sizeDisplay=FormatBytes($replySize/1024);
        }else{
            $sizeDisplay="{$replySize} B";
        }

        // Latency
        $latency=intval($ev->response_time_ms ?? 0);
        $latencyDisplay="{$latency}ms";
        if($latency>2000){
            $latencyDisplay="<strong class='text-danger'>{$latency}ms</strong>";
        }elseif($latency>1000){
            $latencyDisplay="<strong class='text-warning'>{$latency}ms</strong>";
        }
        $xTAGS=array();
        if(isset($ev->note)){
            $notes=explode("\n", urldecode($ev->note));
            foreach ($notes as $note) {
                $note=trim($note);
                if($note==""){
                    continue;
                }
                if(strpos($note,":")==0){continue;}
                $tb=explode(":",$note);
                $xTAGS[trim($tb[0])]=trim($tb[1]);

            }

        }
        if (isset($xTAGS["srcurl"])) {
            $URL = urldecode($xTAGS["srcurl"]);
        }
        if (isset($xTAGS["haclustertests"])) {
            $accessrule="<strong style='color:#18a689'><i class=\"fad fa-balance-scale\"></i>&nbsp;HaCluster Checks</strong>";
        }
        if (isset($xTAGS["authmec"])) {
            $id = trim($xTAGS["authmec"]);
            $ico_auth = $tpl->td_href("<i class='fas fa-user'></i>", null, "Loadjs('fw.proxy.auth_schemes.php?rule-id-js=$id')") . "&nbsp;";
        }
        if (isset($xTAGS["accessrule"])) {
            $accessrule = trim($xTAGS["accessrule"]);
            VERBOSE("accessrule = $accessrule",__LINE__);
            if ($accessrule == "authwhite") {
                $accessrule = $auth_white;
            }
            if ($accessrule == "global_blacklist") {
                $accessrule = $blacklist_text;
            }
            if ($accessrule == "global_whitelist") {
                $accessrule = $whitelist_text;
            }
            if ($accessrule == "final_allow") {
                $accessrule = $final_allow;
            }
            if ($accessrule == "deny_remote_ports") {
                $accessrule = "$deny_remote_ports";
            }
            if ($accessrule == "theshield") {
                $accessrule = null;
            }
        }
        if (isset($xTAGS["acl_peer"])) {
            $acl_peer_id = intval(trim($xTAGS["acl_peer"]));
            $peer_rule_from_id = peer_rule_from_id($acl_peer_id);
        }
        if(isset($xTAGS["simplerule"])){
            $RuleNumber=intval($xTAGS["simplerule"]);
            VERBOSE("RuleNumber: $RuleNumber",__LINE__);
            $simplerule=$SIMPLERULES[$RuleNumber];
        }
        if (isset($xTAGS["bandwidth"])) {
            $bandwidth = "&nbsp;<i class='text-warning fas fa-sort-amount-down'></i>";
        }
        if (isset($xTAGS["category-name"])) {
            $categoryDisplay = trim($xTAGS["category-name"]);
        }
        if (isset($xTAGS["webfiltering"])) {
            if (preg_match("#block,([0-9]+),(.+)#", $xTAGS["webfiltering"], $re)) {
                $zCode0 = "WEBFILTER";
                $categoryDisplay = categoryCodeTocatz(intval($re[2]));
                if (!isset($_SESSION["WEBFILTERINGS"][$re[1]])) {
                    if ($re[1] == 0) {
                        $codeToString = $default_text;
                    }
                    VERBOSE("SESSION[WEBFILTERINGS] {$xTAGS["webfiltering"]} Unknown [{$re[1]}] category=$categoryDisplay", __LINE__);
                } else {
                    $codeToString = $_SESSION["WEBFILTERINGS"][$re[1]];
                }
                $color = "#D0080A";
            }

            if (trim($xTAGS["webfiltering"]) == "block") {
                $zCode0 = "BLOCK";
                $codeToString = "";
                $color = "#D0080A";
            }
        }
        if (isset($xTAGS["srn"])) {
            $theshield_ico = null;
            $xval = trim($xTAGS["srn"]);
            $SRN["ADGUARD"] = "$theshield:AdGuard";
            $SRN["GOOGLE"] = "$theshield:GoogleSafe";
            $SRN["QUAD9"] = "$theshield:Quad9";
            $SRN["DNBSL"] = "$theshield:DNSBL";
            $SRN["GENERIC"] = "$theshield:Generic";
            $SRN["CLOUDFLARE"] = "$theshield:Cloudflare";
            $SRN["MALWAREURL_MALWARES"] = "$theshield:MalwareURL";
            $SRN["MALWAREURL_PHISHING"] = "$theshield:MalwareURL";
            $SRN["ARTICA"] = "$theshield:Artica";
            $SRN["EMERGENCY"] = "$theshield:$emergency_text";
            $SRN["WHITELIST"] = "$theshield:$whitelist_text";

            $SRN_NONE["PASS"] = true;
            $SRN_NONE["WHITE"] = true;
            $SRN_NONE["WHITELIST"] = true;
            $SRN_NONE["IPADRR"] = true;

            $id = microtime();
            if (isset($SRN[$xval])) {
                $theshield_ico = $tpl->td_href("<i class='text-danger fas fa-shield' id='$id'></i>", $SRN[$xval]) . "&nbsp;";
            }
            if ($xval == "EMERGENCY") {
                $theshield_ico = $tpl->td_href("<i class='text-warning fas fa-shield-alt' id='$id'></i>",
                        $SRN["EMERGENCY"]) . "&nbsp;";
            }
            if (isset($SRN_NONE[$xval])) {
                $theshield_ico = "<i class='text-primary fas fa-shield-check' id='$id'></i>&nbsp;";
            }


        }
        if (isset($xTAGS["GeoIPBlock"])) {
            $xTAGS["GeoIPBlock"] = trim($xTAGS["GeoIPBlock"]);
            $zCode0 = "BLOCK ({$xTAGS["GeoIPBlock"]})";
            $codeToString = "";
            $color = "#D0080A";
        }
        if (isset($xTAGS["RBLBLOCK"])) {
            $color = "#D0080A";
            $id = microtime();
            $theshield_ico = $tpl->td_href("<i class='text-danger fas fa-shield' id='$id'></i>", null) . "&nbsp;";
        }
        if (isset($xTAGS["rblpass"])) {
            if($httpStatus<300) {
                $color = "#1ab394";
            }
            $id = microtime();
            $theshield_ico = $tpl->td_href("<i class='text-primary fas fa-shield-check' id='$id'></i>", $whitelist_text) . "&nbsp;";
        }
        if (isset($xTAGS["googlehearth"])) {
            $zCode0 = "Google Hearth - $whitelist_text";

        }
        if (isset($xTAGS["rblcache"])) {
            $zCode0 = "NO CACHE - RBL";
        }
        if (isset($xTAGS["rblcache"])) {
            if (isset($xTAGS["rblpass"])) {
                $zCode0 = "NO CACHE ALLOW - RBL";
                $color = "#1ab394";
            }
        }
        if (isset($xTAGS["itchart"])) {
            if (trim($xTAGS["itchart"]) == "ASK") {
                $zCode0 = "ITChart";
                unset($xTAGS["message"]);
            }
            if (trim($xTAGS["itchart"]) == "ERROR") {
                $hotspot = "ITChart Error";
            }
            if (trim($xTAGS["itchart"]) == "PASS") {
                $hotspot = "ITChart Pass";
            }
        }
        if (isset($xTAGS["message"])) {
            if (!isset($xTAGS["hotspot"])) {
                $message = "<div class=small>{$xTAGS["message"]}</div>";
            }
        }
        if (isset($xTAGS["first"])) {
            VERBOSE("xTAGS[first]=[{$xTAGS["first"]}]", __LINE__);
            if (trim($xTAGS["first"]) == "ERROR") {
                VERBOSE("xTAGS[errnum]={$xTAGS["errnum"]}", __LINE__);
                if (isset($xTAGS["errnum"])) {
                    $first_error = "<br><small></small><span class='text-danger'>
                    <i class=\"fa-solid fa-bug\"></i>&nbsp;$webfiltering_error {$xTAGS["errnum"]}</span></small>";
                }
            }
        }
        if (isset($xTAGS["clog"])) {
            if (preg_match("#cinfo:([0-9]+)-(.*?);#", $xTAGS["clog"], $re)) {
                $category_id = intval($re[1]);
                $categoryDisplay = trim($re[2]);
            }
        }

        if(isset($ev->error_code)){
            if(strlen($ev->error_code)>0){
                $statusDisplay="$statusDisplay/$ev->error_code";
            }
        }

        $html[]="<tr>";
        $html[]="<td><span style='color:$color'>$dateDisplay</span></td>";
        $html[]="<td><small>$sourceHost</small></td>";
        $html[]="<td><span style='color:$color'>$memberText</span></td>";
        $html[]="<td><span style='color:$color'>$statusDisplay$theshield_ico$zCode0$codeToString$category$accessrule$simplerule</span></td>";
        $html[]="<td><span style='color:$color'>$protoDisplay</span></td>";
        $html[]="<td><span style='color:$color'>$categoryDisplay</span></td>";
        $html[]="<td><span style='color:$color'>$domain</span></td>";
        $html[]="<td class='center' nowrap>$loupe</td>";
        $html[]="<td>$destText</td>";
        $html[]="<td><span style='color:$color'>$sizeDisplay</span></td>";
        $html[]="<td><span style='color:$color'>$latencyDisplay</span></td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot><tr><td colspan='11'><ul class='pagination pull-right'></ul></td></tr></tfoot>";
    $html[]="</table>";

    $html[]="<div style='font-size:10px;color:#888'>";
    $html[]="Scanned: {$json->data->scanned} | Matched: {$json->data->total}";
    $html[]="</div>";

    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="$(document).ready(function() { $('.footable').footable({ \"filtering\": { \"enabled\": true }, \"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } }); });";
    $html[]="</script>";

    echo $tpl->_ENGINE_parse_body(@implode("\n",$html));
}
