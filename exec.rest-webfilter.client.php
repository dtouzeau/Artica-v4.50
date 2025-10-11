#!/usr/bin/php -q
<?php
$CLEANED=false;
$cmdline=@implode(" ",$argv);
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#i",$cmdline,$re)){
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
if(preg_match("#--local#i",$cmdline,$re)){
    $GLOBALS["ARTICAKEY"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidRestFulApi");
    $GLOBALS["HOSTNAME"]="127.0.0.1:9000";
}

if(preg_match("#--key=(.+?)(\s|$)#i",$cmdline,$re)){$GLOBALS["ARTICAKEY"]=$re[1];}
if(preg_match("#--hostname=(.+?)(\s|$)#i",$cmdline,$re)){$GLOBALS["HOSTNAME"]="{$re[1]}";}
if(preg_match("#-help(\s|$)#i",$cmdline,$re)){help($argv[0]);exit;}

if(!isset($GLOBALS["HOSTNAME"])){echo "\n\nMissing hostname in '$cmdline' or type --local\n\n";help($argv[0]);exit;}
if(!isset($GLOBALS["ARTICAKEY"])){echo "\n\nMissing key in '$cmdline' or type --local\n\n";help($argv[0]);exit;}



foreach ($argv as $index=>$value){
    if(preg_match("#--(local|hostname|help|verbose|key)#",$value)){
        $CLEANED=TRUE;
        unset($argv[$index]);
    }
}

if($CLEANED){ $NARG=$argv; $argv=array();foreach ($NARG as $cmd){ $cmd=trim($cmd); if($cmd==null){continue;} $argv[]=$cmd; } }

if(!isset($argv[2])){$argv[2]=null;}
if(!isset($argv[3])){$argv[3]=null;}
if(!isset($argv[4])){$argv[4]=null;}
if(!isset($argv[5])){$argv[5]=null;}

if($argv[1]=="--enable-group"){GROUP_ENABLE($argv[2],$argv[3]);exit;}
if($argv[1]=="--remove-group"){GROUP_ENABLE($argv[2],"remove");exit;}
if($argv[1]=="--groups"){GROUPS($argv[2]);exit;}
if($argv[1]=="--new"){CREATE_RULE($argv[2]);exit;}
if($argv[1]=="--new-group"){CREATE_GROUP($argv[2],$argv[3],$argv[4]);exit;}
if($argv[1]=="--new-item"){CREATE_GROUP_ITEM($argv[2],$argv[3]);exit;}
if($argv[1]=="--list-items"){LIST_ITEM($argv[2]);exit;}
if($argv[1]=="--remove-item"){REMOVE_ITEM($argv[2]);exit;}
if($argv[1]=="--list"){LIST_RULES();exit;}
if($argv[1]=="--rules"){LIST_RULES();exit;}
if($argv[1]=="--categories"){LIST_CATEGORIES();exit;}
if($argv[1]=="--mod"){MODIFY_RULES($argv[2],$argv[3],$argv[4]);exit;}
if($argv[1]=="--events"){EXTRACT_EVENTS($argv[2]);exit;}
if($argv[1]=="--compile"){COMPILE_RULES();exit;}
if($argv[1]=="--time"){MANAGE_TIME($argv[2],$argv[3],$argv[4]);exit;}
if($argv[1]=="--snmp"){MANAGE_SNMP($argv[2],$argv[3],$argv[4]);exit;}
if($argv[1]=="--service-status"){SERVICE_STATUS();exit;}
if($argv[1]=="--emergency"){EMERGENCY($argv[2]);exit;}
if($argv[1]=="--ssl"){SSL_COMMANDS($argv[2],$argv[3],$argv[4]);exit;}
if($argv[1]=="--ad-status"){ADSTATUTS($argv[2]);exit;}
if($argv[1]=="--objects"){ACLS_OBJECTS($argv[2],$argv[3]);exit;}
if($argv[1]=="--object-add"){ACLS_OBJECTS("add",$argv[2],$argv[3]);exit;}
if($argv[1]=="--object-del"){ACLS_OBJECTS("del",$argv[2]);exit;}
if($argv[1]=="--object-rename"){ACLS_OBJECTS("rename",$argv[2],$argv[3]);exit;}
if($argv[1]=="--object-enable"){ACLS_OBJECTS("enable",$argv[2]);exit;}
if($argv[1]=="--object-disable"){ACLS_OBJECTS("disable",$argv[2]);exit;}
if($argv[1]=="--items"){ACLS_OBJECTS("items",$argv[2]);exit;}
if($argv[1]=="--item-add"){ACLS_OBJECTS("add-item",$argv[2],$argv[3]);exit;}
if($argv[1]=="--item-del"){ACLS_OBJECTS("del-item",$argv[2]);exit;}

if($argv[1]=="--pac-rules"){PAC_RULES("list");exit;}
if($argv[1]=="--pac-compile"){PAC_RULES("compile");exit;}
if($argv[1]=="--pac-new"){PAC_RULES("new");exit;}
if($argv[1]=="--pac-del"){PAC_RULES("delete",$argv[2]);exit;}
if($argv[1]=="--pac-set"){PAC_RULES("set",$argv[2],$argv[3],$argv[4]);exit;}
if($argv[1]=="--pac-source"){PAC_RULES("source",$argv[2],$argv[3]);exit;}
if($argv[1]=="--pac-unsource"){PAC_RULES("unsource",$argv[2],$argv[3]);exit;}
if($argv[1]=="--pac-unwhite"){PAC_RULES("unwhite",$argv[2],$argv[3]);exit;}
if($argv[1]=="--pac-white"){PAC_RULES("white",$argv[2],$argv[3]);exit;}
if($argv[1]=="--pac-proxy"){PAC_RULES("proxy",$argv[2],$argv[3]);exit;}
if($argv[1]=="--pac-unproxy"){PAC_RULES("unproxy",$argv[2],$argv[3]);exit;}
if($argv[1]=="--pac-proxyset"){PAC_RULES("proxyset",$argv[2],$argv[3],$argv[4],$argv[5]);exit;}
if($argv[1]=="--nodes"){NODES_LIST();exit;}


echo "Unable to understand '{$argv[1]}' !!!!!\n\n";
help($argv[0]);

function MANAGE_SNMP($command1=null,$command2=null){

    if($command1==null) {
        $json = HTTP_PROXY_ENGINE("/snmp/status");
        var_dump($json);
        return;
    }

    if($command1=="apply") {
        HTTP_PROXY_ENGINE("/snmp/apply");
        return;
    }

    if($command1<>null) {
        HTTP_PROXY_ENGINE("/snmp/$command1/$command2");

    }

}

function NODES_LIST(){

    $results=HTTP_PROXY_ENGINE("/nodes/list");
    print_r($results);

}

function SSL_COMMANDS($command=null,$command2=null,$command3=null){

    $cmd[]=$command;
    if($command2<>null){
        $cmd[]=$command2;
    }
    if($command3<>null){
        $cmd[]=$command3;
    }

    $ext=@implode("/",$cmd);
     $json = HTTP_PROXY_ENGINE("/ssl/$ext");
     if($command=="status") {
         if ($json->emergency == 1) {
             echo "Emergency...........: Yes\n";
         } else {
             echo "Emergency...........: No\n";
         }
         foreach ($json->ports as $ports) {
             echo "SSL Port............: $ports\n";
         }
     }



}


function PAC_RULES($command=null,$pattern=null,$pattern2=null,$pattern3=null,$pattern4=null){
    if($command=="list"){
        $json=HTTP_PROXY_ENGINE("/pac/rules");
        foreach ($json->results as $Ruleid=>$main){
            echo "Rule $Ruleid\n";
            print_r($main);

        }

        die();
    }

    if($command=="new"){
         HTTP_PROXY_ENGINE("/pac/new");
         return;
    }
    if($command=="compile"){
        HTTP_PROXY_ENGINE("/pac/compile");
        return;
    }
    if($command=="delete"){
        HTTP_PROXY_ENGINE("/pac/delete/$pattern");
        return;
    }

    if($command=="proxyset"){
        HTTP_PROXY_ENGINE("/pac/proxyset/$pattern/$pattern2/$pattern3/$pattern4");
        return;
    }

    if($command=="set"){
        HTTP_PROXY_ENGINE("/pac/parameters/$pattern/$pattern2/$pattern3");
        return;
    }
    if($command=="source"){
        HTTP_PROXY_ENGINE("/pac/source/$pattern/$pattern2");
        return;
    }
    if($command=="unsource"){
        HTTP_PROXY_ENGINE("/pac/unsource/$pattern/$pattern2");
        return;
    }
    if($command=="white"){
        HTTP_PROXY_ENGINE("/pac/white/$pattern/$pattern2");
        return;
    }
    if($command=="unwhite"){
        HTTP_PROXY_ENGINE("/pac/unwhite/$pattern/$pattern2");
        return;
    }
    if($command=="proxy"){
        HTTP_PROXY_ENGINE("/pac/proxy/$pattern/$pattern2");
        return;
    }
    if($command=="unproxy"){
        HTTP_PROXY_ENGINE("/pac/unproxy/$pattern/$pattern2");
        return;
    }
    if($command=="proxyset"){
        HTTP_PROXY_ENGINE("/pac/proxyset/$pattern/$pattern2/$pattern3");
        return;
    }

}
function ACLS_OBJECTS($command=null,$pattern=null,$pattern2=null){
    ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
    if($command==null){
        $command="search";
        $pattern="*";
    }

    if($command=="add"){
        HTTP_PROXY_ENGINE("/objects/add/$pattern/$pattern2");
        die();
    }
    if($command=="del"){
        HTTP_PROXY_ENGINE("/objects/del/$pattern");
        die();
    }
    if($command=="rename"){
        HTTP_PROXY_ENGINE("/objects/$pattern/$pattern2");
        die();
    }
    if($command=="enable"){
        HTTP_PROXY_ENGINE("/objects/$pattern/enable");
        die();
    }
    if($command=="disable"){
        HTTP_PROXY_ENGINE("/objects/$pattern/disable");
        die();
    }

    if($command=="add-item"){
        $pattern=intval($pattern);
        if($pattern==0){echo "Require Object ID (numeric)\n";die();}
        HTTP_PROXY_ENGINE("/objects/add-item/$pattern/$pattern2");
        die();
    }

    if($command=="del-item"){
        $pattern=intval($pattern);
        if($pattern==0){echo "Require item ID (numeric)\n";die();}
        HTTP_PROXY_ENGINE("/objects/del-item/$pattern");
        die();
    }
    if($command=="types"){
        $json=HTTP_PROXY_ENGINE("/objects/types");
        foreach ($json->results as $type => $desc) {
            echo "\"$type\"\t$desc\n";
        }
        die();
    }

    $json=HTTP_PROXY_ENGINE("/objects/$command/$pattern");

    if($command=="search") {
            foreach ($json->results as $ID => $array) {
                $GroupName = $array->GroupName;
                $GroupType = $array->GroupType;
                $enabled = $array->enabled;
                echo "($ID)\t\"$GroupName\" Type: $GroupType Enabled:$enabled\n";

            }
    die();}

    if($command=="items") {
        foreach ($json->results as $ID => $array) {
            $item = $array->pattern;
            echo "($ID)\t\"$item\"\n";

        }

    die();}




}

function EMERGENCY($command){

    $json=HTTP_ENGINE("/service/emergency/$command");
}

function ADSTATUTS(){

    $json=HTTP_PROXY_ENGINE("/activedirectory/status");


    if($json->status==false){
        echo " * * * Failed $json->message * * * \n";

    }else{
        echo " * * * Success * * * \n";
    }


    if(property_exists($json,"configuration")) {
        echo "Configuration status:\n---------------------------------------\n";
        foreach ($json->configuration as $key=>$val){
            echo "\t$key    : $val\n";
        }
        echo "\n\n";
    }

    if(property_exists($json,"WINBIND_STATUS")) {
        echo "Winbind status:\n---------------------------------------\n";
        foreach ($json->WINBIND_STATUS as $key=>$val){
            echo "\t$key    : $val\n";
        }
        echo "\n\n";
    }

    if(property_exists($json,"events")) {
        echo "Events:\n---------------------------------------\n";
        foreach ($json->events as $val){
            echo "# $val\n";
        }
        echo "\n\n";
    }



}

function SERVICE_STATUS(){
    ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
    $json=HTTP_ENGINE("/service/status");

    if(!property_exists($json,"INFO")){
        echo "Missing INFO, corrupted array\n";
        return false;
    }


    echo "DEBUG_MODE.............: {$json->INFO->DEBUG_MODE}\n";
    echo "EMERGENCY_MODE.........: {$json->INFO->EMERGENCY_MODE}\n";
    echo "EMERGENCY_MODE_WHY.....: {$json->INFO->EMERGENCY_MODE_WHY}\n";
    echo "INSTALLED_DATABASES....: {$json->INFO->INSTALLED_DATABASES}\n";
    echo "MISSING_DATABASES......: {$json->INFO->MISSING_DATABASES}\n";
    echo "MISSING_DATABASES_LIST.: \n";
    foreach ($json->INFO->MISSING_DATABASES_LIST as $db){
        echo "........................: $db\n";
    }
    echo "-----------------------------------------------\nServices:\n";

    foreach ($json->INFO->SERVICE_STATUS->_params as $service=>$main){
        echo "\n\n$service:\n----------------------------------------------- \n";
        foreach ($main as $key=>$val) {
            echo "$key..: $val\n";
        }

    }





}

function MANAGE_TIME($ruleid=0,$action=null,$value=null){

    $main[]="time";
    $main[]=$ruleid;
    if($action<>null){$main[]=$action;}
    if($value<>null){$main[]=$value;}

    $json=HTTP_ENGINE(@implode("/",$main));

    if(!property_exists($json,"time")){
       return;
    }

    echo "Time matches...........: {$json->time->matches}\n";
    echo "Alternate rule.........: {$json->time->alternate}\n";

    foreach ($json->time->period as $key=>$val){
        echo "period [$key]..........: $val\n";
    }


}

function LIST_CATEGORIES()
{
    $ch = curl_init();
    $CURLOPT_HTTPHEADER[] = "Accept: application/json";
    $CURLOPT_HTTPHEADER[] = "Pragma: no-cache,must-revalidate";
    $CURLOPT_HTTPHEADER[] = "Cache-Control: no-cache,must revalidate";
    $CURLOPT_HTTPHEADER[] = "Expect:";
    $CURLOPT_HTTPHEADER[] = "ArticaKey: {$GLOBALS["ARTICAKEY"]}";
    $MAIN_URI = "https://{$GLOBALS["HOSTNAME"]}/api/rest/webfilter/categories";

    curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_URL, $MAIN_URI);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');

    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    if ($errno > 0) {
        echo "Error $errno\n" . curl_error($ch) . "\n$response\n";
        curl_close($ch);
        die();
    }
    $CURLINFO_HTTP_CODE = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    if ($CURLINFO_HTTP_CODE <> 200) {
        echo "Error $CURLINFO_HTTP_CODE\n";
        die();
    }
    $json = json_decode($response);
    if (!$json->status) {
        echo "Failed $json->message\n";
        die();
    }


    foreach ($json->categories as $category_id => $line) {
        //NAME category Name, KEY: Category Key
        echo "[ID:$category_id]: {$line->NAME}\n";
    }
}
function HTTP_PROXY_ENGINE($url,$array=null){
    $verbose=null;
    if($GLOBALS["VERBOSE"]){
        $verbose="?verbose=yes";
    }
    $ch = curl_init();
    $CURLOPT_HTTPHEADER[]="Accept: application/json";
    $CURLOPT_HTTPHEADER[]="Pragma: no-cache,must-revalidate";
    $CURLOPT_HTTPHEADER[]="Cache-Control: no-cache,must revalidate";
    $CURLOPT_HTTPHEADER[]="Expect:";
    $CURLOPT_HTTPHEADER[]="ArticaKey: {$GLOBALS["ARTICAKEY"]}";
    $MAIN_URI="https://{$GLOBALS["HOSTNAME"]}/api/rest/proxy/cache/$url$verbose";
    $MAIN_URI=str_replace("//","/",$MAIN_URI);
    echo "[$MAIN_URI]\n";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_URL, $MAIN_URI);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');

    if(is_array($array)){

        curl_setopt($ch, CURLOPT_POSTFIELDS, $array);
    }

    $message=null;
    $response = curl_exec($ch);
    $errno=curl_errno($ch);
    $json=json_decode($response);
    $CURLINFO_HTTP_CODE=intval(curl_getinfo($ch,CURLINFO_HTTP_CODE));
    $status=false;
    if(!is_object($json)){
        if($errno>0){ echo "Transport Error $errno\n".curl_error($ch)."\n"; curl_close($ch); }
        echo "Internal Error $CURLINFO_HTTP_CODE RestAPI Crashed\n$response\n";
        return array();
    }
    if(property_exists($json,"status")){
        $status=$json->status;
    }
    if(property_exists($json,"message")){
        $message=$json->message;
    }
    if($errno>0){ echo "Transport Error $errno\n".curl_error($ch)."\n$response\n"; curl_close($ch); die(); }

    curl_close($ch);
    if($CURLINFO_HTTP_CODE<>200){
        echo "Failed.....: $message\n";
        echo "HTTP Error.: $CURLINFO_HTTP_CODE\n";
        return $json;
        die();
    }
     if(!$status){echo "Failed $message\n";return $json;}

    $elements=null;

    if(property_exists($json,"count")){
        $elements=" - $json->count element(s)";
    }

    if(property_exists($json,"message")){
        $message=$json->message;
        echo "Success: $CURLINFO_HTTP_CODE - $message{$elements}\n";
    }
    return $json;

}

function HTTP_ENGINE($url,$array=null){
    $verbose=null;
    if($GLOBALS["VERBOSE"]){
        $verbose="?verbose=yes";
    }
    $ch = curl_init();
    $CURLOPT_HTTPHEADER[]="Accept: application/json";
    $CURLOPT_HTTPHEADER[]="Pragma: no-cache,must-revalidate";
    $CURLOPT_HTTPHEADER[]="Cache-Control: no-cache,must revalidate";
    $CURLOPT_HTTPHEADER[]="Expect:";
    $CURLOPT_HTTPHEADER[]="ArticaKey: {$GLOBALS["ARTICAKEY"]}";
    $MAIN_URI="https://{$GLOBALS["HOSTNAME"]}/api/rest/webfilter/$url$verbose";
    $MAIN_URI=str_replace("//","/",$MAIN_URI);
    echo "[$MAIN_URI]\n";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_URL, $MAIN_URI);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');

    if(is_array($array)){

        curl_setopt($ch, CURLOPT_POSTFIELDS, $array);
    }

    $message=null;
    $response = curl_exec($ch);
    $errno=curl_errno($ch);
    $json=json_decode($response);
    $CURLINFO_HTTP_CODE=intval(curl_getinfo($ch,CURLINFO_HTTP_CODE));
    $status=false;
    if(!is_object($json)){
        if($errno>0){ echo "Transport Error $errno\n".curl_error($ch)."\n"; curl_close($ch); }
        echo "Internal Error $CURLINFO_HTTP_CODE RestAPI Crashed\n$response\n";
        return array();
    }
    if(property_exists($json,"status")){
        $status=$json->status;
    }
    if(property_exists($json,"message")){
        $message=$json->message;
    }
    if($errno>0){ echo "Transport Error $errno\n".curl_error($ch)."\n$response\n"; curl_close($ch); die(); }

    curl_close($ch);
    if($CURLINFO_HTTP_CODE<>200){
        echo "Failed.....: $message\n";
        echo "HTTP Error.: $CURLINFO_HTTP_CODE\n";
        die();
    }




    if(!$status){echo "Failed $message\n";return array();}

    if(property_exists($json,"message")){
        $message=$json->message;
        echo "Success: $CURLINFO_HTTP_CODE - $message\n";
    }
    //ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
   return $json;

}

function EXTRACT_EVENTS($search=null){


    $json=HTTP_ENGINE("events/$search");

    if(property_exists($json,"events")){
        $events=$json->events;
        foreach ($events as $linenumber=>$json2) {
            echo "\n\n| ********************************************************************\n";

            foreach ($json2 as $key => $value) {

                if (is_object($value)) {
                    Dump_objects("| $key", $value);
                    continue;
                }
                echo "| $key: $value\n";
            }
        }

    }

}


function help($filepath){
echo "* * * * * * * *  HELP * * * * * * * *\n\n";
echo "Usage:\n";
echo basename($filepath)." *command* (extra parameters)\n\n";
echo "Examples:\n";
echo basename($filepath)." --list --local\n";
echo basename($filepath)." --mod 5 blacklists 109,167,132,131,215\n";
echo basename($filepath)." --list --key=HgMQCyaRV28l4wro1eCU4UHL --hostname=192.168.1.1:9000\n";
echo basename($filepath)." --new-group ad \"Internet Users\" 5 --key=HgMQCyaRV28l4wro1eCU4UHL --hostname=192.168.1.1:9000\n";
echo basename($filepath)." --new-group virtual \"local_servers\" 2 --key=HgMQCyaRV28l4wro1eCU4UHL --hostname=192.168.1.1:9000\n";
echo basename($filepath)." --time period 2 \"m,t,w,h,f:09:00-18:00\" --key=HgMQCyaRV28l4wro1eCU4UHL --hostname=192.168.1.1:9000\n";
echo "\n\n";

echo "Where *command* is one of:\n";
echo "----------------------------\n";
echo "--categories ..................: List all available categories\n";
echo "--list ........................: List Web-filtering rules\n";
echo "--rules .......................: List Web-filtering rules\n";
echo "--new  [rulename] .............: Create a new rule with the rule name associated\n";
echo "--mod  ruleid key value .......: Edit a rule with the specified key and value\n\n";
echo "--time ruleid action value ....: Edit a rule time period where action can be\n";
echo "                               : \"view\" all : Display time configuration\n";
echo "                               : \"matches\": inside|outside|none\n";
echo "                               : \"alternate\": ruleid where ruleid is alternate rule outside the time\n";
echo "                               : \"period\": days:time1-time2 where:\n";
echo "                               : days can be m for Monday,t for Tuesday,w for Wednesday,h for Thursday,\n";
echo "                               : f for Friday,a for Saturday,s for Sunday\n";
echo "                               : time1 and time2 must be formatted as hh:mm\n";
echo "                               : \"period\": remove:ID remove the period with ID\n\n";
echo "--groups (ID) .................: List all available groups if ID specified, list all groups from a rule ID\n";
echo "--new-group [type] [name] [ID] : Add a group with type (ldap,virtual,ad,ou) and name linked to ruleid\n";
echo "--remove-group [ID] ...........: Remove the group with ID\n";
echo "--enable-group [ID] enable/disable..: Enable/disable group with ID\n";
echo "--new-item [pattern] [group id]: Add a new item in virtual group id\n";
echo "--list-items (group id)........: List all items of groups if groupid specified, only for the defined group id\n";
echo "--remove-item id...............: Remove an item with ID from Virtual Groups\n";
echo "--events [search]..............: Extract web-filtering events with [search] pattern\n";
echo "--compile .....................: Compile rules and make them in production mode\n";
echo "\n* * * * * * * * * * * Proxy settings * * * * * * * * * * *\n";
echo "--snmp.........................: get SNMP configuration\n";
echo "--snmp apply...................: Apply SNMP configuration\n";
echo "--snmp merge (enable|disable)..: Merge SNMP proxy service to SNMP service\n";
echo "--snmp [community|console|port] [value]: Set Proxy SNMP configuration\n";
echo "--ad-status....................: Get NTLM Active Directory connection status\n";
echo "--ssl status...................: Get SSL ports list and status\n";
echo "--ssl emergency on|off.........: Turn on or off the SSL Emergency mode.\n";
echo "\n* * * * * * * * * * * ACLS * * * * * * * * * * *\n";
echo "--objects......................: Manage ACLs Objects\n";
echo "--objects types................: List type of objects\n";
echo "--object-add [name] [type].....: Create a new object with [name] and type [type]\n";
echo "--object-del [object id].......: Remove object and all associated items\n";
echo "--object-enable [object id]....: Enable object\n";
echo "--object-disable [object id]...: disable object\n";
echo "--object-rename [object id] [x]: Rename object with ID with name (x)\n";
echo "--objects search [pattern].....: List ACLs objects\n";
echo "--objects items [object id]....: List all items inside object id \n";
echo "--items [object id]............: List all items inside object id \n";
echo "--item-add [object id] [value].: Add an item with [value] inside object id \n";
echo "--item-del [item id]...........: Remove an item with item id identifier\n";
echo "\n* * * * * * * * * * * Proxy PAC * * * * * * * * * * *\n";
echo "--pac-rules....................: List Proxy PAC Rules.\n";
echo "--pac-compile..................: Compile rules in production mode.\n";
echo "--pac-new......................: Create a new rule\n";
echo "--pac-del [ruleid].............: Remove a rule with the rule ID\n";
echo "--pac-set [ruleid] [attr] [val]: Update a Proxy pac rule setting\n";
echo "--pac-source ruleid objectid...: Link ACL object objectid to PAC rule ID ruelid\n";
echo "--pac-unsource ruleid objectid.: Unlink ACL object objectid to PAC rule ID ruelid\n";
echo "--pac-white ruleid objectid....: Link white ACL object objectid to PAC rule ID ruelid\n";
echo "--pac-unwhite ruleid objectid..: Unlink white ACL object objectid to PAC rule ID ruelid\n";
echo "--pac-proxy ruleid proxy:port..: Add a targeted proxy to the rule\n";
echo "--pac-unproxy ruleid proxy:port: remove a targeted proxy to the rule\n";
echo "--pac-proxyset ruleid proxy:port key val: Set zorder or secure option to targeted proxy\n";
echo "\n* * * * * * * * * * * Statistics * * * * * * * * * * *\n";
echo "--nodes........................: List of all nodes connected to the proxy (every 10 Minutes)\n";


echo "Extra parameters\n";
echo "----------------------------\n";
echo "--local  ......................: Use the local key to authenticate and the local server\n";
echo "--key=[api]  ..................: Use the defined API Key to authenticate\n";
echo "--hostname=[server:port]  .....: Use the server:port to query the API\n";
echo "--verbose  ....................: Debug mode\n";

die();
}

function CREATE_GROUP_ITEM($pattern,$groupid){
    HTTP_ENGINE("new/group/item/$pattern/$groupid");

}

function GROUP_ENABLE($ID,$action){
    HTTP_ENGINE("group/$ID/$action");
}
function CREATE_GROUP($type,$name,$ruleid){

    HTTP_ENGINE("new/group/$type/$name/$ruleid");
}
function COMPILE_RULES(){
    HTTP_ENGINE("rules/apply");
}

function GROUPS($ruleid=0){

    $ruleid=intval($ruleid);
    $json=HTTP_ENGINE("groups/$ruleid");

    $groups=$json->groups;
    foreach ($groups as $gpid=>$json2) {
        echo "\n\n| ********************************************************************\n";
        echo "| Group ID: [$gpid] with the following key=>value\n";
        foreach ($json2 as $key => $value) {

            if (is_object($value)) {
                Dump_objects("| $key", $value);
                continue;
            }
            echo "| $key: $value\n";
        }
    }

}

function REMOVE_ITEM($ID){
    HTTP_ENGINE("items/remove/$ID");

}

function LIST_ITEM($groupid) {

    $json=HTTP_ENGINE("items/list/$groupid");

    if(!property_exists($json,"items")){
        echo "\n\n| ********************************************************************\n";
        echo "It seems there are no itms listed\n";
        return null;
    }

    $items=$json->items;
    foreach ($items as $id=>$members) {
        echo "[$id]: $members\n";
    }
}

function LIST_RULES(){
    $message=null;
    $json=HTTP_ENGINE("rules");

    if(!property_exists($json,"rules")){
        echo "\n\n| ********************************************************************\n";
        echo "It seems there are no rule in this server\n";
        return null;
    }

    $rules=$json->rules;
    foreach ($rules as $id=>$json2) {
        echo "\n\n| ********************************************************************\n";
        echo "| Rule ID: $id with the following key=>value\n";
        foreach ($json2 as $key => $value){

            if(is_object($value)){
                Dump_objects("| $key",$value);
               continue;
            }
            echo "| $key: $value\n";
        }
    }

}

function CREATE_RULE($rulename){
    $array["ID"]=-1;
    $array["rulename"]=$rulename;
    $array["mode"]=1;
    $array["enabled"]=1;
    HTTP_ENGINE("save-rule",$array);


}

function MODIFY_RULES($id,$key,$value){
    $array["ID"]=$id;
    $array[$key]=$value;
    HTTP_ENGINE("save-rule",$array);

}

function Dump_objects($key,$obj){

    foreach ($obj as $key2 => $value2){
        if(is_object($value2)){Dump_objects("$key->$key2",$value2);continue;}
        if(is_array($value2)){Dump_objects("$key\[$key2\]",$value2);continue;}
        echo "$key->$key2: $value2\n";
    }


}
