#!/usr/bin/php -q
<?php
$CLEANED=false;
$cmdline=@implode(" ",$argv);
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#i",$cmdline,$re)){
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}

$GLOBALS["MEMORY_ARGVS"]=@implode(" ",$argv);


if(preg_match("#--local#i",$cmdline,$re)){
    $GLOBALS["ARTICAKEY"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/SystemRESTFulAPIKey");
    $GLOBALS["HOSTNAME"]="127.0.0.1:9000";
}

if(preg_match("#--key=(.+?)(\s|$)#i",$cmdline,$re)){$GLOBALS["ARTICAKEY"]=$re[1];}
if(preg_match("#--hostname=(.+?)(\s|$)#i",$cmdline,$re)){$GLOBALS["HOSTNAME"]="{$re[1]}";}
if(preg_match("#--help(\s|$)#i",$cmdline,$re)){help($argv[0]);exit;}

foreach ($argv as $index=>$value){
    if(preg_match("#--(local|hostname|help|verbose|key)#",$value)){$CLEANED=true;unset($argv[$index]);}
}
if($CLEANED){ $NARG=$argv; $argv=array();foreach ($NARG as $cmd){ $cmd=trim($cmd); if($cmd==null){continue;} $argv[]=$cmd; } }

if($argv[1]=="--patch"){PATCH_ARTICA($argv[2]);}
if($argv[1]=="--version"){REMOTE_VERSION();}
if($argv[1]=="--hosts"){HOSTS($argv[2],$argv[3],$argv[4],$argv[5]);}
if($argv[1]=="--monitor"){ALL_STATUS($argv[2]);}
if($argv[1]=="--license"){LICENSE("info");}
if($argv[1]=="--gold"){LICENSE("gold",$argv[2]);}
if($argv[1]=="--snmp"){SNMP($argv[2],$argv[3],$argv[4]);}
if($argv[1]=="--features"){FEATURES($argv[2],$argv[3]);}
if($argv[1]=="--privs"){PRIVILEGES($argv[2],$argv[3],$argv[4]);}
if($argv[1]=="--emergencies"){EMERGENCY($argv[2],$argv[3],$argv[4]);}




function SNMP($command1=null,$command2=null,$command3=null){

    if($command1==null){
        $command1="status";
    }
    $json = HTTP_ENGINE("snmp/$command1");

    if($command1=="status"){
        if(!property_exists($json,"Info")){
            echo "Command status failed\n";
            die();
        }
        echo "* * * * Status * * * * \n";
        foreach ($json->Info as $key=>$val){echo "$key:\t$val\n";}
        echo "\n\n* * * * Parameters * * * * \n";
        foreach ($json->params as $key=>$val){
            echo "Parameter: \"$key\" set to \"$val\"\n";
            $cmds1=$GLOBALS["MEMORY_ARGVS"];
            $cmds2=$GLOBALS["MEMORY_ARGVS"];
            $cmds1=str_replace("--snmp","--snmp $key",$cmds1);
            $cmds2=str_replace("--snmp","--snmp $key \"$val\"",$cmds2);
            $help[]="Get value: $cmds1";
            $help[]="Set value: $cmds2";
        }
        echo "Help.........:\n".@implode("\n",$help)."\n";
        die();

    }

    $json = HTTP_ENGINE("snmp/$command1/$command2");
    if(property_exists($json,"value")){
        echo "$command1 == $json->value\n";
    }
    if(property_exists($json,"action")){
        echo "Action: $json->action\n";
    }

}

function EMERGENCY($cmd1=null,$cmd2=null,$cmd3=null){

    if($cmd1=="activedirectory"){
        $json = HTTP_ENGINE("emergency/$cmd1/$cmd2");

    }
}

function PRIVILEGES($cmd1=null,$cmd2=null,$cmd3=null){

    if($cmd1==null){
        $json = HTTP_ENGINE("privs");
        echo "Supported Token: ".$json->supported."\n";


            foreach ($json->privileges as $num=>$array){
                echo "(";
                echo $array->ID;
                echo ") ";
                echo $array->DN." = ";
                echo "[".$array->PRIVS."]\n";

            }


        exit();

    }

    if(strtolower($cmd1)=="remove"){
        $cmd2=intval($cmd2);
        if($cmd2==0){
            echo "Wrong parameter\n";
            die();
        }
        $json = HTTP_ENGINE("privs/remove/$cmd2");
        die();
    }

    $cmd1=urlencode($cmd1);
    $cmd2=urlencode($cmd2);
    $json = HTTP_ENGINE("privs/$cmd1/$cmd2");


}

function FEATURES($command1=null,$command2=null){
    $onlyinstalled=0;
    if($command1=="installed"){$command1=null;$onlyinstalled=1;}

    if($command1=="install"){
        $json = HTTP_ENGINE("features/install/$command2");
        if(!property_exists($json,"status")){
            echo "Command Failed, aborting";
            return false;
        }

        if(!$json->status){
            echo "Command install Failed $command2, aborting";
            return false;
        }

        echo "[OK]: Command install $command2 launched\n";
        return true;
    }

    if($command1=="remove"){
        $json = HTTP_ENGINE("features/uninstall/$command2");
        if(!property_exists($json,"status")){
            echo "Command Failed, aborting";
            return false;
        }

        if(!$json->status){
            echo "Command uninstall Failed $command2, aborting";
            return false;
        }

        echo "[OK]: Command uninstall $command2 launched\n";
        return true;
    }

    if($command1==null){
        $json = HTTP_ENGINE("features/list");
        if(!property_exists($json,"features")){
            echo "Command Failed, aborting";
            return false;
        }

        foreach ($json->features as $feature=>$array){
            $INFO=$array->INFO;
            $TITLE=$array->TITLE;
            $AVAILABLE=$array->AVAILABLE;
            $INSTALLED=$array->INSTALLED;
            $EXPLAIN=$array->EXPLAIN;
            if($TITLE<>null){$TITLE=" ($TITLE)";}
            if($INFO=="{not_installed}"){
                $INFO="Uninstalled";}

            $INFO=str_replace("{require_installed}","Require to install",$INFO);
            $INFO=str_replace("{require_enabled}","Require to enable",$INFO);

            if($INFO=="Uninstalled"){
                if($onlyinstalled==1){continue;}
            }
            if(preg_match("#Require#",$INFO)){
                if($onlyinstalled==1){continue;}
            }


            echo "//=========================================================\n";
            echo "$feature $TITLE\n";
            if($EXPLAIN<>null){
                $EXPLAIN=str_replace("\\n","\n",$EXPLAIN);
                $EXPLAIN = wordwrap($EXPLAIN, 60,"\n",false);
                echo "--------------------------------------------\n";
                echo $EXPLAIN."\n";
                echo " -------------------------------------------\n";
            }
            if($INSTALLED) {
                echo "..............: * Installed *\n";
            }

            if(!$AVAILABLE){
                echo "..............: * Not Available *\n";
            }
            if($INFO<>null) {
                echo "..............: - $INFO -\n";
            }
            $cmds1=$GLOBALS["MEMORY_ARGVS"];
            $cmds2=$GLOBALS["MEMORY_ARGVS"];

            $cmds1=str_replace("--features installed","--features",$cmds1);
            $cmds2=str_replace("--features installed","--features",$cmds2);
            $cmds1=str_replace("--features","--features install $feature",$cmds1);
            $cmds2=str_replace("--features","--features remove $feature",$cmds2);
            echo "Use Install....: $cmds1\n";
            echo "Use Uninstall..: $cmds2\n";
            echo "//=========================================================\n";
            echo "\n\n";

        }


    }

    echo "'$command1' unknown parameter, aborting please use --help for more information\n";
    return false;

}


function PATCH_ARTICA($filepath){

    if(!is_file($filepath)){
        echo "$filepath no such file\n";
        exit;
    }

    $basename=basename($filepath);
    if(!preg_match("#artica-[0-9\.]+\.tgz$#",$basename)){
        echo "$basename not correclty formated filename\n";
        exit;
    }

    $json=POST_FILE($filepath);


}

function ALL_STATUS($cmdline=null){

    if($cmdline==null) {
        $json = HTTP_ENGINE("monitor/status");

        foreach ($json->results as $productName => $array) {
            if (trim($productName) == null) {
                continue;
            }
            echo "\n\n$productName:\n----------------------------------------------------\n";
            foreach ($array as $key => $value) {
                echo "\t$key: $value\n";
            }

        }
    }

    if($cmdline=="reload") {
        $json = HTTP_ENGINE("monitor/reload");
        die();
    }
    if($cmdline=="emergencies") {
        $json = HTTP_ENGINE("monitor/emergencies");
        echo "Count.............: " . $json->count . "\n";
        echo "List..............: " . $json->emergencies . "\n";
        die();
    }


}

function LICENSE($key="info",$val=null){

    $json = HTTP_ENGINE("license/$key/$val");

    if(!$json->status){
        echo "Failed $json->message\n";
        return;
    }

    if($key=="gold") {
        echo "Success $json->message\n";
        return;
    }

    if($key=="info") {
        echo "\n\n";
        echo "License........: $json->message ($json->license_status)\n";
        echo "Company........: $json->company\n";
        echo "Expire.........: $json->expire ($json->expire_in)";

        if(property_exists($json,"grace_period_expire")){
            echo "Grace period...: $json->grace_period_expire_text ($json->grace_period_expire)";
        }


    }
    echo "\n\n";
}

function REMOTE_VERSION(){

$json=HTTP_ENGINE("articaver");

    if(!property_exists($json,"version")){
        echo "Issue encountered with API\n";
        exit;
    }

    print_r($json->hostname);
    echo "*****************************************\n";
    echo "Artica Version...........: $json->version\n";
    echo "*****************************************\n";
    foreach ($json->hostname as $key=>$value){
        echo "$key...........: $value\n";
    }

    echo "*****************************************\n";


}

function HOSTS($command1=null,$command2=null,$command3=null,$command4=null){

    ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

    if($command1==null){$command1="search";}
    if($command2==null){$command2="*";}



    if($command1=="search"){
        $results=HTTP_ENGINE("hosts/$command1/$command2");
        foreach ($results->hosts as $md5=>$array){
            echo "Host..........: $md5\n";
            echo "hostname......: {$array->hostname} ({$array->alias})\n";
            echo "Address.......: {$array->ipaddr}\n\n";
        }

        return;
    }

    if($command1=="add"){
        HTTP_ENGINE("hosts/add/$command2/$command3/$command4");
        return;
    }
    if($command1=="del"){
        HTTP_ENGINE("hosts/del/$command2");
        return;
    }

    //print_r($results);

}

function HTTP_ENGINE($url,$array=null){
    $verbose=null;
    $url=str_replace("//","/",$url);
    if($GLOBALS["VERBOSE"]){
        $verbose="?verbose=yes";
    }
    $ch = curl_init();
    $CURLOPT_HTTPHEADER[]="Accept: application/json";
    $CURLOPT_HTTPHEADER[]="Pragma: no-cache,must-revalidate";
    $CURLOPT_HTTPHEADER[]="Cache-Control: no-cache,must revalidate";
    $CURLOPT_HTTPHEADER[]="Expect:";
    $CURLOPT_HTTPHEADER[]="ArticaKey: {$GLOBALS["ARTICAKEY"]}";
    $MAIN_URI="https://{$GLOBALS["HOSTNAME"]}/api/rest/system/$url$verbose";
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
        echo "Success: $CURLINFO_HTTP_CODE - $message\n * * * * * * * * * * * * * * * * * * * * * * * * *\n";
    }
    return $json;

}

function POST_FILE($filepath){
    $verb=null;

    $CURLOPT_HTTPHEADER[]="Accept: application/json";
    $CURLOPT_HTTPHEADER[]="Pragma: no-cache,must-revalidate";
    $CURLOPT_HTTPHEADER[]="Cache-Control: no-cache,must revalidate";
    $CURLOPT_HTTPHEADER[]="Expect:";
    $CURLOPT_HTTPHEADER[]="ArticaKey: {$GLOBALS["ARTICAKEY"]}";

    if($GLOBALS['VERBOSE']){$verb="?verbose=true";}

    $MAIN_URI = "https://{$GLOBALS["HOSTNAME"]}/api/rest/system/{$verb}";
    $ch = curl_init($MAIN_URI);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');

    $cFile = curl_file_create($filepath);
    $post = array('patch-artica' => 'yes','file_contents'=> $cFile);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $errno=curl_errno($ch);
    $json=json_decode($response);
    $curl_getinfo=curl_getinfo($ch);
    $CURLINFO_HTTP_CODE=intval(curl_getinfo($ch,CURLINFO_HTTP_CODE));
    $status=false;

    if(!is_object($json)){
        echo "$MAIN_URI\nInternal Error $CURLINFO_HTTP_CODE RestAPI Crashed\n$response\n";
        print_r($curl_getinfo);
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
        echo "Failed.....: ($status) $message \n";
        echo "HTTP Error.: $CURLINFO_HTTP_CODE\n";
        die();
    }
    if(!$status){echo "Failed $message\n";return array();}

    if(property_exists($json,"message")){
        $message=$json->message;
        echo "Success: $CURLINFO_HTTP_CODE - $message\n";
    }


}


function help($filepath){
    echo "* * * * * * * *  HELP * * * * * * * *\n\n";
    echo "Usage:\n";
    echo basename($filepath)." *command* (extra parameters)\n\n";
    echo "Examples:\n";
    echo basename($filepath)." --patch /tmp/artica-4.0.12345.tgz --local\n";

    echo "\n\n";

    echo "Where *command* is one of:\n";
    echo "----------------------------\n";
    echo "--patch [filename]..................: Where filename is an artica patch or version in tgz format\n";
    echo "--version ..........................: Get current version of the Artica Firmware\n";
    echo "--hosts ............................: Manage hosts file Where\n";
    echo "        search [value]..............: Search in hosts database\n";
    echo "        add [ip] [hostname] [alias].: Add entry in hosts database\n";
    echo "        del [key]...................: Delete entrey with key in hosts database\n";
    echo "--license ..........................: Display License status\n";
    echo "--gold [key] .......................: Add a Gold Key where key\n";
    echo "                                      is the serial (XXXXX9-4XXXY7-XXX8XX-XXXX9X)\n";
    echo "--features .........................: List of available features\n";
    echo "--features installed................: List of installed features\n";
    echo "                                      \n";
    echo "--snmp..............................: Get status and help managing SNMP service.\n";
    echo "--snmp key..........................: Get parameter \"key\" of the SNMP service.\n";
    echo "--snmp key value....................: Set parameter \"key\" of the SNMP service to \"value\".\n";
    echo "                                      \n";
    echo "\n\nExtra parameters\n";
    echo "----------------------------\n";
    echo "--local  ...........................: Use the local key to authenticate and the local server\n";
    echo "--key=[api]  .......................: Use the defined API Key to authenticate\n";
    echo "--hostname=[server:port]  ..........: Use the server:port to query the API\n";
    echo "--verbose  .........................: Debug mode\n";

    die();
}