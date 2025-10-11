<?php

include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/externals/Net/DNS2.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_POST["UnBoundRedisDBTTL"])){Save();exit;}
if(isset($_GET["unbound-redis-status"])){redis_status();exit;}
if(isset($_GET["top-redis-status"])){redis_widgets();exit;}
if(isset($_GET["main"])){Start();exit;}
if(isset($_GET["search"])){search();exit;}
page();

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $html=$tpl->page_header("{UBOUND_REDIS}",ico_database,"{ubound_redis_explain}","$page?main=yes","dns-redis-database","unbound-redis-progress",false,"table-dns-redis-loader");

    if(isset($_GET["main-page"])){$tpl=new template_admin(null,$html);echo $tpl->build_firewall();return true;}
    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function Start():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='vertical-align: top;width:350px' nowrap>";
    $html[]="<div id='unbound-redis-status' style='margin-bottom:10px'></div>";
    $html[]="</td>";
    $html[]="<td style='padding-left: 10px;width: 99%;vertical-align: top;' nowrap>";
    $html[]="<div id='top-redis-status' style='margin-bottom:10px'></div>";
    $html[]=$tpl->search_block($page);

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    $html[]="<script>";
    $js=$tpl->RefreshInterval_js("unbound-redis-status",$page,"unbound-redis-status=yes");
    $html[]=$js;
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function search():bool{

    $tpl = new template_admin();
    $UnboundRedisEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundRedisEnabled"));
    if($UnboundRedisEnabled==0){
        return false;
    }

    if (!function_exists('bin2hex')) {
        echo $tpl->div_error("bin2hex func not found");
        return false;
    }

    try {
        $redis = new Redis();
        $redis->connect("127.0.0.1", 21647, 2);
    } catch (Exception $e) {
        $RedisText = $e->getMessage();
        echo $tpl->div_error($RedisText);
        return false;
    }

    $array = $redis->keys("*");
    if (!$array) {
        return false;
    }

    $search=null;
    if(isset($_GET["search"])){$_GET["search"]=trim($tpl->CLEAN_BAD_XSS($_GET["search"]));}
    if(isset($_GET["search"])){
        $search=trim($_GET["search"]);
        if(strlen($search)>1){
            $search="*$search*";
            $search=str_replace("**","*",$search);
            $search=str_replace("**","*",$search);
            $search=str_replace("*",".*?",$search);
        }

    }

    $t=time();
    $html[]="<table id='table-$t' class=\"footable table table-stripped\" data-page-size=\"100\" data-paging=\"true\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>&nbsp;</th>";
    $html[]="<th data-sortable=false class='text-capitalize' data-type='text'>{cached_items}</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS="";
    $img=ico_earth;
    $c=0;
    foreach ($array as $key) {
        $values = ParseHex($redis->get($key));
        if(count($values)==0){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS="";}else{$TRCLASS="footable-odd";}
        $md5=md5($values[0].$values[1]);
        if(strlen($values[1])>0){
            $values[1]=" ($values[1])";
        }
        if(!is_null($search)){
            if(!preg_match("/".$search."/",$values[0])){
                if(!preg_match("/".$search."/",$values[1])) {
                    continue;
                }
            }
        }
        $c++;
        if($c>250){
            break;
        }
        $html[]="<tr class='$TRCLASS' id='$md5'>";
        $html[]="<td style='width:1%' nowrap><i class='$img'></td>";
        $html[]="<td style='width:99%' ><strong>$values[0]</strong>$values[1]</td>";
        $html[]="</tr>";
    }

    $html[]="</tbody>";
    $html[]="<tfoot>";
    $html[]="<tr>";
    $html[]="<td colspan='2'>";
    $html[]="<ul class='pagination pull-right'></ul>";
    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</tfoot>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS)."
	$(document).ready(function() { $('#table-$t').footable({\"filtering\": { \"enabled\": false },\"sorting\": { \"enabled\": true },\"paging\": { \"size\": {$GLOBALS["FOOTABLE_PSIZE"]} } } ); });
	</script>";
    echo $tpl->_ENGINE_parse_body($html);
return true;
}


function ParseHex($value):array{
    $hex = bin2hex($value);
    $data = hex2bin($hex);
    $dnsPacket = unpack('ntransaction_id/nflags/nquestions/nanswers/nauthority/nadditional', $data);
    $offset = 12;
    $MAIN=array();
// Skip over the question part (assuming single question)
    $questionLength = 0;
    $answers = extractAnswers($data, $offset, $dnsPacket['answers']);

    foreach ($answers as $Aswer) {
        if(!isset($Aswer["domain_name"])){
            continue;
        }
        if(isset($MAIN[$Aswer['domain_name']])){
            if(strlen($Aswer['ip'])>2){
                $MAIN[$Aswer['domain_name']]=$Aswer['ip'];
            }
            continue;
        }
        if(strlen($Aswer['ip'])>2){
            $MAIN[$Aswer['domain_name']]=$Aswer['ip'];
            continue;
        }
        $MAIN[$Aswer['domain_name']]="";

    }


    $questions = extractQuestions($data, $offset, $dnsPacket['questions']);
    foreach ($questions as $Quests) {
        if(!isset($Quests['domain'])) {
            continue;
        }
        if(isset($MAIN[$Quests['domain']])){
            continue;
        }
        $MAIN[$Quests['domain']]="";
    }

    foreach ($MAIN as $key=>$value) {
        return array($key,$value);
    }
    return array();
}
function extractQuestions($data, &$offset, $questionCount) {
    $questions = [];
    for ($i = 0; $i < $questionCount; $i++) {
        $domain = parseDomainName($data, $offset); // Parse domain
        $type = unpack('n', substr($data, $offset, 2))[1];  // Type (2 bytes)
        $class = unpack('n', substr($data, $offset + 2, 2))[1]; // Class (2 bytes)
        $offset += 4; // Move past type and class

        $questions[] = [
            'domain' => $domain,
            'type' => $type,
            'class' => $class,
        ];
    }
    return $questions;
}
function extractAnswers($data, $offset, $numAnswers) {
    $answers = [];

    for ($i = 0; $i < $numAnswers; $i++) {
        // Parse the domain name (could be a pointer)
        if (empty($data)) {
            continue;
        }
        $domainName = parseDomainName($data, $offset);
        $offset += strlen($domainName) + 1; // Skip over domain name part

        // Parse type and class
        //$type = unpack('n', substr($data, $offset, 2))[1];
        $offset += 2;
        //$class = unpack('n', substr($data, $offset, 2))[1];
        $offset += 2;

        // Parse TTL
        //$ttl = unpack('N', substr($data, $offset, 4))[1];
        $offset += 4;
        if ($offset + 2 > strlen($data)) {
            continue;
        }
        // Parse data length
        $dataLength = unpack('n', substr($data, $offset, 2))[1];
        $offset += 2;

        // Parse the actual data (this will vary based on record type)
        $recordData = substr($data, $offset, $dataLength);
        $offset += $dataLength;

        $record = [
            'domain_name' => $domainName,
            'ip'=>parseDnsRecord(bin2hex($recordData))

        ];


        // Only add non-empty answers
        if (!empty($record['domain_name']) && !empty($record['data'])) {
            $answers[] = $record;
        }
    }

    return $answers;
}
function parseDnsRecord($rawData):string {

    $ipData = substr($rawData, 47, 4); // For example, IP might be here
    return inet_ntop($ipData); // Convert to readable IP address

}
// Function to parse a domain name
function parseDomainName($data, &$offset) {
    $domain = '';
    while (true) {
        if ($offset >= strlen($data)) {
            break;
        }
        $length = ord($data[$offset]);
        if ($length == 0) { // End of domain
            $offset++;
            break;
        }
        if (($length & 0xC0) == 0xC0) { // Pointer (compression)
            $pointer = unpack('n', substr($data, $offset, 2))[1] & 0x3FFF;
            $offset += 2;
            return $domain . parseDomainName($data, $pointer);
        }
        $offset++;
        $domain .= substr($data, $offset, $length) . '.';
        $offset += $length;
    }
    return rtrim($domain, '.');
}
function redis_status():bool{
    $tpl=new template_admin();
    $sock=new sockets();
    $json=json_decode($sock->REST_API("/unbound/status"));
    $bsini=new Bs_IniHandler();
    $bsini->loadString($json->Info);
    $page=CurrentPageName();
    $topbuttons=array();
    $jsRedisRestart=$tpl->framework_buildjs("/unbound/redis/restart",
        "unbound-redis.progress",
        "unbound-redis.log",
        "unbound-redis-progress");

    $remove_db=$tpl->framework_buildjs("unbound.php?remove-redis-database=yes",
        "unbound-redis-flush.progress","unbound-redis.log","unbound-redis-progress");

    $UnboundRedisEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundRedisEnabled"));
    if($UnboundRedisEnabled==1) {
        $topbuttons[] = array($jsRedisRestart, ico_retweet, "{restart}");
        $topbuttons[] = array($remove_db, ico_trash, "{REMOVE_DATABASE}");
    }


    $Title="{UBOUND_REDIS}";
    $TINY_ARRAY["TITLE"]=$Title;
    $TINY_ARRAY["ICO"]="fa fas fa-database";
    $TINY_ARRAY["EXPL"]="{ubound_redis_explain}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $final[]=$tpl->SERVICE_STATUS($bsini, "UBOUND_REDIS",$jsRedisRestart);
    $final[]="<script>";
    $final[]="LoadAjaxSilent('top-redis-status','$page?top-redis-status=yes');";
    $final[]=$jstiny;
    $final[]="</script>";
    echo @implode("",$final);
    return true;
}

function redis_widgets()
{
    $tpl = new template_admin();
    $UnboundRedisEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundRedisEnabled"));

    $jsRedisInstall = $tpl->framework_buildjs("/unbound/redis/install",
        "unbound-redis.progress",
        "unbound-redis.log",
        "unbound-redis-progress");

    $jsRedisUninstall = $tpl->framework_buildjs("/unbound/redis/uninstall",
        "unbound-redis.progress",
        "unbound-redis.log",
        "unbound-redis-progress");

    $btn = array();
    $btn[0]["margin"] = 0;
    $btn[0]["name"] = "{install}";
    $btn[0]["icon"] = ico_cd;
    $btn[0]["js"] = $jsRedisInstall;
    $btnDefault=$btn;
    $widget_memory = $tpl->widget_style1("gray-bg", ico_memory, "{inactive2}", "{memory}", $btn);
    $widget_connections = $tpl->widget_style1("gray-bg", ico_nic,  "{connections}","{inactive2}", $btn);
    $widget_records = $tpl->widget_style1("gray-bg", ico_database,  "{records}","{inactive2}", $btn);
    $widget_tests = $tpl->widget_style1("gray-bg", ico_stop,  "{write}","{inactive2}", $btn);



    if ($UnboundRedisEnabled == 1) {
        $btn = array();
        $btn[0]["margin"] = 0;
        $btn[0]["name"] = "{uninstall}";
        $btn[0]["icon"] = ico_trash;
        $btn[0]["js"] = $jsRedisUninstall;
        $RedisError = true;
        $btnDefault=$btn;

        try {
            $redis = new Redis();
            $redis->connect("127.0.0.1", 21647, 2);
            $RedisError = false;
        } catch (Exception $e) {
            $RedisText = $e->getMessage();
            $RedisError = true;
            echo $tpl->div_error($RedisText);
        }
    }

    if ($UnboundRedisEnabled == 1) {
        if ($RedisError) {
            $widget_memory = $tpl->widget_style1("red-bg", ico_memory,  "{memory}","{error}", $btn);
            $widget_connections = $tpl->widget_style1("red-bg", ico_nic,  "{connections}","{error}", $btn);
            $widget_records = $tpl->widget_style1("red-bg", ico_database,  "{records}", "{error}",$btn);
            $widget_tests = $tpl->widget_style1("gray-bg", ico_stop,  "{write}","{error}", $btn);
        }
    }
    if ($UnboundRedisEnabled == 1) {
        if (!$RedisError) {
            $CountOfKeys = 0;
            $infos = $redis->info();
            if(!isset($infos["db0"])){
                $infos["db0"]="keys=0";
            }
            $Keys = $infos["db0"];
            if (preg_match("#^keys=([0-9]+)#", $Keys, $re)) {
                $CountOfKeys = $re[1];
            }

            $maxmemory_human = $infos["maxmemory_human"];
            $used_memory_human = $infos["used_memory_human"];
            $maxmemory = $infos["maxmemory"];
            $used_memory = $infos["used_memory"];
            $prc_1 = ($used_memory / $maxmemory) * 100;
            $prc_mem = round($prc_1, 2);
            $total_connections_received = $tpl->FormatNumber($infos["total_connections_received"]);
            $widget_memory = $tpl->widget_style1("green-bg", ico_memory,
                "$used_memory_human / $maxmemory_human", "$prc_mem%", $btn);

            $widget_connections = $tpl->widget_style1("green-bg", ico_nic,  "{connections}", $total_connections_received,$btn);


            $btn = array();
            $btn[0]["margin"] = 0;
            $btn[0]["name"] = "{parameters}";
            $btn[0]["icon"] = ico_params;
            $btn[0]["js"] = "Loadjs('fw.dns.unbound.php?unbound-performance-redis-js=yes')";

            $widget_records = $tpl->widget_style1("green-bg", ico_database,  "{records}",
                $tpl->FormatNumber($CountOfKeys),$btn);
            if ($CountOfKeys == 0) {
                $widget_records = $tpl->widget_style1("grey-bg", ico_database,
                    "{records}",0, $btn);
            }

            try {
                if ($redis->set("TESTKEY", "TESTVALUE", 2)) {
                    if (method_exists($redis, "unlink")) {
                        $redis->unlink("TESTKEY");
                    } else {
                        $redis->delete("TESTKEY");
                    }
                    $widget_tests = $tpl->widget_style1("lazur-bg", "fas fa-thumbs-up", "{write} v{$infos["redis_version"]}", "OK", $btnDefault);
                } else {
                    $widget_tests = $tpl->widget_style1("red-bg", "fas fa-exclamation-triangle", "{write} v{$infos["redis_version"]}", "{failed}", $btnDefault);
                }


            } catch (Exception $e) {
                $widget_tests = $tpl->widget_style1("red-bg", "fas fa-exclamation-triangle", "{write} v{$infos["redis_version"]}", "{failed}", $btnDefault);
                $html[] = $tpl->widget_rouge("{write}", $e->getMessage());
            }
        }
    }
    $html[] = "<table style='width:100%'>";
    $html[] = "<tr>";
    $html[] = "<td style='padding-left: 10px;width:1%' nowrap>";
    $html[] = $widget_memory;
    $html[] = "</td>";
    $html[] = "<td style='padding-left: 10px;width:1%' nowrap>";
    $html[] = $widget_connections;
    $html[] = "</td>";
    $html[] = "<td style='padding-left: 10px;width:1%' nowrap>";
    $html[] = $widget_records;
    $html[] = "</td>";
    $html[] = "<td style='padding-left: 10px;width:1%' nowrap>";
    $html[] = $widget_tests;
    $html[] = "</td>";
    $html[] = "</tr>";
    $html[] = "</table>";

    echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    admin_tracks("Redis database for Cache DNS settings changed");
}