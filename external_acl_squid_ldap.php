#!/usr/bin/php
<?php
$GLOBALS["DEBUG_GROUPS"]=0;
error_reporting(0);
include_once("/usr/share/artica-postfix/ressources/class.external_acl_squid_ldap.inc");
include_once("/usr/share/artica-postfix/ressources/class.ldap-extern.inc");
include_once("/usr/share/artica-postfix/ressources/class.html.tools.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(preg_match("#--verbose#", @implode(" ", $argv))){
    ini_set('display_errors', 1);
    ini_set('html_errors',0);
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    error_reporting(1);
    $GLOBALS["VERBOSE"]=true;
    $GLOBALS["DEBUG_GROUPS"]=3;
    echo "VERBOSED MODE\n";
}
define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);
$GLOBALS["SplashScreenURI"]=null;
$GLOBALS["PID"]=getmypid();
$GLOBALS["STARTIME"]=time();
$GLOBALS["MACTUIDONLY"]=false;
$GLOBALS["uriToHost"]=array();
$GLOBALS["SESSION_TIME"]=array();
$GLOBALS["LDAP_TIME_LIMIT"]=10;
$GLOBALS["MULTIGROUPS"]=array();

$GLOBALS["AdStatsGroupMethod"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AdStatsGroupMethod"));
$GLOBALS["AdStatsGroupPattern"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AdStatsGroupMethod"));
if($GLOBALS["AdStatsGroupPattern"]==null){$GLOBALS["AdStatsGroupPattern"]=".*";}
$GLOBALS["DEBUG_GROUPS"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidExternalLDAPDebug"));

//$GLOBALS["DEBUG_GROUPS"]=3;


$GLOBALS["TIMELOG"]=0;
$GLOBALS["QUERIES_NUMBER"]=0;
$GLOBALS["TIMELOG_TIME"]=time();
if(preg_match("#--output#", @implode(" ", $argv))){$GLOBALS["output"]=true;}
if($argv[1]=="--db"){ufdbguard_checks($argv[2]);	die(0);}
LoadSettings();

$max_execution_time=ini_get('max_execution_time');
$GLOBALS["SESSIONS"]=unserialize(@file_get_contents("/etc/squid3/".basename(__FILE__).".cache"));
WLOG("[START]: Starting New process with KerbAuthInfos:".count($GLOBALS["KerbAuthInfos"])." Parameters");
WLOG("[START]: Starting Debug groups:{$GLOBALS["DEBUG_GROUPS"]}");
WLOG("[START]: Starting AdStatsGroupMethod={$GLOBALS["AdStatsGroupMethod"]}");


$external_acl_squid_ldap=new external_acl_squid_ldap();

if($argv[1]=="--ou"){
    $GLOBALS["DEBUG_GROUPS"]=3;
    filter_by_ou($argv[2],$argv[3],"0 ");
    exit;
}

if($argv[1]=="--groups"){
    $GLOBALS["VERBOSE"]=true;
    $GLOBALS["DEBUG_GROUPS"]=true;
    $GROUPZ=$external_acl_squid_ldap->GetGroupsFromMember($argv[2]);

    echo "********************* GROUPS ***********************\n";
    echo count($GROUPZ)."\n\n";
    print_r($GROUPZ);
    echo "\n\n************************************************\n\n\n";
    die("DIE " .__FILE__." Line: ".__LINE__);
}

while (!feof(STDIN)) {
    $content = trim(fgets(STDIN));


    if($content<>null){
        $AS_TAG=false;
        $connection_prefix=null;
        $connection_id=0;$isDebug=false;
        if($GLOBALS["DEBUG_GROUPS"]>0){
            $isDebug=true;
            WLOG("receive content...\"$content\""); }

        $array=preg_split("/[\s]+/", $content);
        if(is_numeric($array[0])){
            $connection_id=$array[0];
            $member=trim($array[1]);
            $group=trim($array[2]);
            if(isset($array[3])){$group="{$group} {$array[3]}";}
            if(isset($array[4])){$group="{$group} {$array[4]}";}
            if(isset($array[5])){$group="{$group} {$array[5]}";}
            if(isset($array[6])){$group="{$group} {$array[6]}";}
        }else{
            $member=trim($array[0]);
            $group=$array[1];
            if(isset($array[2])){$group="{$group} {$array[2]}";}
            if(isset($array[3])){$group="{$group} {$array[3]}";}
            if(isset($array[4])){$group="{$group} {$array[4]}";}
            if(isset($array[5])){$group="{$group} {$array[5]}";}
            if(isset($array[6])){$group="{$group} {$array[6]}";}
        }

        if($connection_id>0){$connection_prefix="$connection_id ";}

        $member=str_replace("%20", " ", $member);
        $member=AccountDecode($member);
        $group=str_replace("%20", " ", $group);
        $group=AccountDecode($group);
        $group=strtolower($group);

        if($isDebug){ WLOG("Find `$group` in ^OU:(.+) (".__LINE__.")"); }
        if(preg_match("#^OU:(.+)#i",$group,$re)){
            $ou=$re[1];
            if($isDebug){ WLOG("filter_by_ou($ou,$member,$connection_prefix) (".__LINE__.")"); }
            return filter_by_ou($ou,$member,$connection_prefix);
        }


        if($isDebug){ WLOG("Member `$member` must be in `$group` (".__LINE__.")"); }

        if(preg_match("#^tag:(.+)#", $group,$re)){
            $AS_TAG=true;
            $group=trim(strtolower($re[1]));
            if($GLOBALS["DEBUG_GROUPS"]>0){ WLOG("TAG YES FOR GROUP $group and member = $member (".__LINE__.")"); }
        }



        if($member=="-"){
            if($GLOBALS["DEBUG_GROUPS"]>0){ WLOG("Member is None \"$member\" (".__LINE__.")"); }
            if($AS_TAG){
                if($GLOBALS["DEBUG_GROUPS"]>0){ WLOG("tag=everyone (".__LINE__.")"); }
                fwrite(STDOUT, "{$connection_prefix}OK tag=everyone\n");continue;}
            if($GLOBALS["DEBUG_GROUPS"]>0){ WLOG("Return ERR (".__LINE__.")"); }
            fwrite(STDOUT, "{$connection_prefix}ERR\n");
        }



        if($group==-1){
            $log=null;
            $ou=$external_acl_squid_ldap->GetUserOU($member);
            $GROUPY=$external_acl_squid_ldap->GetGroupsFromMember($member);
            $FirstGroup=null;

            if($GLOBALS["AdStatsGroupMethod"]==1){
                foreach ($GROUPY as $a=>$b){
                    $a=trim(strtolower($a));
                    if($a==null){continue;}
                    if(preg_match("#{$GLOBALS["AdStatsGroupPattern"]}#", $a,$re)){
                        unset($re[0]);
                        if(count($re)>0){$FirstGroup=@implode("", $re);}
                        if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("[CHECK]: Method[{$GLOBALS["AdStatsGroupMethod"]}] $a = matches {$GLOBALS["AdStatsGroupPattern"]} (".__LINE__.")");}
                        $FirstGroup=$a;
                        break;
                    }

                }

            }



            if($FirstGroup==null){
                foreach ($GROUPY as $a=>$b){
                    if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("[CHECK]: Method[{$GLOBALS["AdStatsGroupMethod"]}] $a = $b (".__LINE__.")");}
                    $a=trim(strtolower($a));
                    if($a==null){continue;}
                    $FirstGroup=$a;
                    break;
                }
            }

            if($GLOBALS["DEBUG_GROUPS"] >0){
                WLOG("[CHECK]: Method[{$GLOBALS["AdStatsGroupMethod"]}]OK clt_conn_tag=$FirstGroup log=$FirstGroup,$ou (".__LINE__.")");
            }


            fwrite(STDOUT, "{$connection_prefix}OK clt_conn_tag=$FirstGroup log=$FirstGroup,$ou\n");
            continue;
        }


        $GROUPZ=array();

        if($GLOBALS["DEBUG_GROUPS"] >0){ WLOG("GetGroupsFromMember($member) -> `$member` [1] = \"$group\" (".__LINE__.")"); }
        $GROUPY=$external_acl_squid_ldap->GetGroupsFromMember($member);


        if(count($GROUPY)>0){
            foreach ($GROUPY as $a=>$b){
                $a=trim(strtolower($a));
                if($a==null){continue;}
                if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("[CHECK]: \$GROUPZ: $member is a member of `$a`");}
                $GROUPZ[$a]=true;
            }
        }


        if(is_numeric($group)){
            if(!is_file("/etc/squid3/acls/container_$group.txt")){
                WLOG("/etc/squid3/acls/container_$group.txt no such file");
                fwrite(STDOUT, "{$connection_prefix}ERR\n");
                continue;
            }


            if($GLOBALS["DEBUG_GROUPS"] >0){ WLOG("Requested [$group] =  numeric..");}
            if(!isset($GLOBALS["MULTIGROUPS"][$group])){
                if($GLOBALS["DEBUG_GROUPS"] >0){ WLOG("Loading /etc/squid3/acls/container_$group.txt");}
                $f=explode("\n",@file_get_contents("/etc/squid3/acls/container_$group.txt"));
                foreach ($f as $a=>$b){
                    $b=trim(strtolower($b));
                    if($b==null){continue;}
                    if($GLOBALS["DEBUG_GROUPS"] >0){ WLOG("Add $b in memory");}
                    $GLOBALS["MULTIGROUPS"][$group][$b]=true;
                }

            }

            $ANSWER=false;
            reset($GLOBALS["MULTIGROUPS"][$group]);
            foreach ($GLOBALS["MULTIGROUPS"][$group] as $GroupName=>$b){
                if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("[CHECK]: `$GroupName` if it is in \$GROUPZ");}

                if(isset($GROUPZ[$GroupName])){
                    if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("[CHECK]: TRUE `$GroupName` is in user's ");}
                    if($AS_TAG){
                        fwrite(STDOUT, "{$connection_prefix}OK tag=$GroupName\n");
                    }else{
                        fwrite(STDOUT, "{$connection_prefix}OK\n");
                    }
                    $ANSWER=true;
                    break;
                }else{
                    if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("[CHECK]: FALSE `$GroupName` is not user's group");}
                }
            }

            if($ANSWER){continue;}
            if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("$member is not a member of block number $group"); }

            if($AS_TAG){
                if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("OK tag=everyone"); }
                fwrite(STDOUT, "{$connection_prefix}OK tag=everyone\n");
                continue;
            }

            if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("ERR\n"); }
            fwrite(STDOUT, "{$connection_prefix}ERR\n");
            continue;
        }


        if($GLOBALS["TIMELOG"]>9){
            $distanceInSeconds = round(abs(time() - $GLOBALS["TIMELOG_TIME"]));
            $distanceInMinutes = round($distanceInSeconds / 60);
            WLOG("[SEND]: 10 queries in {$distanceInMinutes}Mn");
            $GLOBALS["TIMELOG"]=0;
            $GLOBALS["TIMELOG_TIME"]=time();
        }

        if($GLOBALS["DEBUG_GROUPS"] >0){
            WLOG("[CHECK]: `$group` in array of ".count( $GROUPZ)." items");
            foreach ($GROUPZ as $a=>$b){LOG("[CHECK]: is `$a` == `$group`");}
        }

        if(isset($GROUPZ[$group])){
            if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("[SEND]: OK $member is a member of \"$group\"");}
            if($AS_TAG){
                if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("[SEND]: OK tag=$group");}
                fwrite(STDOUT, "OK tag=$group\n");
                continue;
            }
            if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("[SEND]: OK");}
            fwrite(STDOUT, "{$connection_prefix}OK\n");
            continue;
        }

        if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("$member IS NOT a member of `$group`"); }
        if($AS_TAG){fwrite(STDOUT, "{$connection_prefix}OK tag=everyone\n");continue;}
        fwrite(STDOUT, "{$connection_prefix}ERR\n");

    }

}

CleanSessions();
$distanceInSeconds = round(abs(time() - $GLOBALS["STARTIME"]));
$distanceInMinutes = round($distanceInSeconds / 60);
if(isset($GLOBALS["CONNECTION"])) {
    if ($GLOBALS["CONNECTION"]) {
        WLOG("[STOP]: Stopping process: shutdown LDAP connections...");
        @ldap_close($GLOBALS["CONNECTION"]);
    }
}
WLOG("[STOP]: Stopping process v1.2: After ({$distanceInSeconds}s - about {$distanceInMinutes}mn)</span>");
WLOG("[STOP]: This process was query the LDAP server <strong>{$GLOBALS["QUERIES_NUMBER"]} times...</span>");



if(isset($GLOBALS["F"])){@fclose($GLOBALS["F"]);}


function filter_by_ou($ou,$member,$connection_prefix){
    $isDebug=false;

    if(isset($GLOBALS["OUNAMES"][$ou][$member])){
        if($GLOBALS["OUNAMES"][$ou][$member]==1){
            fwrite(STDOUT, "{$connection_prefix}OK\n");
            return;
        }
        fwrite(STDOUT, "{$connection_prefix}ERR\n");
        return;
    }

    if($GLOBALS["DEBUG_GROUPS"] >0){$isDebug=true;}

    if($isDebug){ WLOG("Find `$member` in Organization `$ou`  (".__LINE__.")"); }
    include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
    $ad=new external_ad_search();
    if(!isset($GLOBALS["OULISTS"][$ou])){
        $GLOBALS["OULISTS"][$ou]=$ad->active_directory_ListOus($ou);
    }

    if($GLOBALS["VERBOSE"]){print_r($GLOBALS["OULISTS"][$ou]);}

    if($isDebug){ WLOG("$member is a part of ".count($GLOBALS["OULISTS"][$ou])." organizations ? (".__LINE__.")"); }


    if(count($GLOBALS["OULISTS"][$ou])==0){
        if($isDebug){ WLOG("`$ou` No organization found in Active Directory (".__LINE__.")"); }
        fwrite(STDOUT, "{$connection_prefix}ERR\n");
        return;
    }

    reset($GLOBALS["OULISTS"][$ou]);
    foreach ($GLOBALS["OULISTS"][$ou] as $dn=>$OuName){
        if($isDebug){ WLOG("Check if_user_in_dn($dn,$member) (".__LINE__.")"); }
        if($ad->if_user_in_dn($dn,$member)){
            if($isDebug){ WLOG("$member is a part of `$ou` (".__LINE__.")"); }
            $GLOBALS["OUNAMES"][$ou][$member]=1;
            fwrite(STDOUT, "{$connection_prefix}OK\n");
            return;
        }

    }

    $GLOBALS["OUNAMES"][$ou][$member]=0;
    if($isDebug){ WLOG("$member is not a part of `$ou` (".__LINE__.")"); }
    fwrite(STDOUT, "{$connection_prefix}ERR\n");

}

function LoadSettings(){
    if($GLOBALS["VERBOSE"]){echo __FUNCTION__."\n";}
    if(!isset($GLOBALS["LoadSettingsFailed"])){$GLOBALS["LoadSettingsFailed"]=0;}

    $data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos");
    if($GLOBALS["VERBOSE"]){echo "############################\n$data\n############################\n";}
    $decoded=base64_decode($data);
    if($GLOBALS["VERBOSE"]){echo "############################\n$decoded\n############################\n";}
    $unser=unserialize($decoded);
    $GLOBALS["KerbAuthInfos"]=$unser;
    return $GLOBALS["KerbAuthInfos"];
}

function GetGroupsFromMember($member){
    $GLOBALS["TIMELOG"]++;
    ConnectToLDAP();

    if(!$GLOBALS["BIND"]){
        WLOG("[QUERY]: Error: BIND is broken -> reconnect");
        ConnectToLDAP();
        if(!$GLOBALS["BIND"]){WLOG("[QUERY]: Error: BIND pointer is false");return false;}
    }

    if(!$GLOBALS["CONNECTION"]){
        WLOG("[QUERY]: Error: CONNECTION is broken -> reconnect twice");
        ConnectToLDAP();
    }

    if(!$GLOBALS["CONNECTION"]){
        WLOG("[QUERY]: Error: CONNECTION is definitively broken aborting !!!...");
        return false;
    }

    $array=array();
    //$link_identifier, $base_dn, $filter, array $attributes = null, $attrsonly = null, $sizelimit = null, $timelimit = null, $deref = null
    $filter=array("memberOf");
    @ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);
    $filter="(&(objectCategory=Person)(objectClass=user)(sAMAccountName=$member))";



    $GLOBALS["QUERIES_NUMBER"]++;
    $link_identifier=$GLOBALS["CONNECTION"];
    $base_dn=$GLOBALS["SUFFIX"];
    $attributes=array();
    $attrsonly=null;
    $sizelimit=null;
    $timelimit= $GLOBALS["LDAP_TIME_LIMIT"];
    if($GLOBALS["VERBOSE"]){WLOG("[QUERY]::$filter -> $filter in $base_dn");}



    $sr =@ldap_search($link_identifier,$base_dn,$filter,$attributes,$attrsonly, $sizelimit, $timelimit);
    if (!$sr) {
        if(is_numeric(ldap_errno($GLOBALS["CONNECTION"]))){
            $error=ldap_errno($GLOBALS["CONNECTION"]);
            $errstr=@ldap_err2str($error);

            if($error==-1){
                if(!isset($GLOBALS["RETRY_AFTER_ERROR"])){
                    WLOG("[QUERY]: Error:`$error` ($errstr) re-connect and retry query...");
                    $GLOBALS["RETRY_AFTER_ERROR"]=true;
                    return GetGroupsFromMember($member);
                }else{
                    WLOG("[QUERY]: Error:`$error` ($errstr) Connection lost definitively");
                    return false;
                }

            }

            WLOG("[QUERY]: Error:`$error` ($errstr) suffix:{$GLOBALS["SUFFIX"]} $filter, return no user");
            return false;
        }else{
            WLOG("[QUERY]: Error: unknown Error (ldap_errno not a numeric) suffix:{$GLOBALS["SUFFIX"]} $filter, return no user");
        }
    }




    $hash=ldap_get_entries($GLOBALS["CONNECTION"],$sr);
    if(!is_array($hash)){
        WLOG("[QUERY]: Error: undefined, hash is not an array or did not find user...");
        return false;
    }


    unset($GLOBALS["RETRY_AFTER_ERROR"]);
    if(isset($hash[0]["memberof"])){
        for($i=0;$i<$hash[0]["memberof"]["count"];$i++){
            if(preg_match("#^CN=(.+?),#i", $hash[0]["memberof"][$i],$re)){
                $re[1]=trim(strtolower($re[1]));
                if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("$member = \"{$re[1]}\""); }
                $array[$re[1]]=true;
            }

        }
    }
    if($GLOBALS["DEBUG_GROUPS"] >0){  WLOG("Return array of ".count($array)." items"); }
    return $array;

}

function MemberInfoByDN($base_dn){

    $FUNCTION=__FUNCTION__;

    if(!TestConnectToLDAP()){
        if($GLOBALS["VERBOSE"]){echo "$FUNCTION():: TestConnectToLDAP() -> FALSE\n";}
        return null;
    }

    $link_identifier=$GLOBALS["CONNECTION"];
    $attributes=array("displayName","samaccountname","mail","givenname","telephoneNumber","title","sn","mozillaSecondEmail","employeeNumber","objectClass","member");
    $attrsonly=null;
    $sizelimit=null;
    $filter="(objectClass=*)";;
    $timelimit= $GLOBALS["LDAP_TIME_LIMIT"];

    $sr =@ldap_search($link_identifier,$base_dn,$filter,$attributes,$attrsonly, $sizelimit, $timelimit);
    if (!$sr) {WLOG("[QUERY]: MemberInfoByDN()::Bad search $base_dn / $filter");return null;}
    $hash=ldap_get_entries($GLOBALS["CONNECsTION"],$sr);
    if(!is_array($hash)){WLOG("[QUERY]: MemberInfoByDN():: Not an array $base_dn / $filter");return null;}
    $AsGroup=false;




    for($i=0;$i<$hash[0]["objectclass"]["count"];$i++){
        $class=$hash[0]["objectclass"][$i];
        if($GLOBALS["VERBOSE"]){echo "$FUNCTION()::$base_dn::objectclass -> $class\n";}
        if($class=="group"){$AsGroup=true;break;}
    }




    if($AsGroup){
        $MembersCount=$hash[0]["member"]["count"];
        for($i=0;$i<$MembersCount;$i++){
            $member=MemberInfoByDN($hash[0]["member"][$i]);
            if(is_array($member)){
                foreach ($member as $a=>$b){
                    if(trim($b)==null){continue;}
                    $f[$b]=$b;
                }
            }else{
                $f[$member]=$member;
            }
        }
        return $f;
    }


    if(!isset($hash[0]["samaccountname"][0])){WLOG("[QUERY]: MemberInfoByDN():: samaccountname no such attribute");return null;}
    if($GLOBALS["VERBOSE"]){echo "HashUsersFromGroupDN()::$base_dn:: -> {$hash[0]["samaccountname"][0]}\n";}
    return $hash[0]["samaccountname"][0];
}

function TestConnectToLDAP(){
    ConnectToLDAP();
    if(!$GLOBALS["BIND"]){
        WLOG("[QUERY]: Error: BIND is broken -> reconnect");
        ConnectToLDAP();
        if(!$GLOBALS["BIND"]){WLOG("[QUERY]: Error: BIND pointer is false");return false;}
    }

    if(!$GLOBALS["CONNECTION"]){WLOG("[QUERY]: Error: CONNECTION is broken -> reconnect");
        ConnectToLDAP();}

    if(!$GLOBALS["CONNECTION"]){
        WLOG("[QUERY]: Error: CONNECTION is definitively broken aborting !!!...");
        return false;
    }
    return true;

}
function TestConnectToPureLDAP(){
    ConnectToPureLDAP();
    if(!$GLOBALS["BIND_LDAP"]){
        WLOG("[QUERY]: Error: BIND is broken -> reconnect");
        ConnectToPureLDAP();
        if(!$GLOBALS["BIND_LDAP"]){WLOG("[QUERY]: Error: BIND pointer is false");return false;}
    }

    if(!$GLOBALS["CONNECTION"]){WLOG("[QUERY]: Error: CONNECTION is broken -> reconnect");
        ConnectToPureLDAP();}

    if(!$GLOBALS["CONNECTION"]){
        WLOG("[QUERY]: Error: CONNECTION is definitively broken aborting !!!...");
        return false;
    }
    return true;

}

function HashUsersFromFullDN($dn){
    TestConnectToLDAP();
    if(isset($GLOBALS["HashUsersFromFullDN($dn)"])){return $GLOBALS["HashUsersFromFullDN($dn)"];}




    $link_identifier=$GLOBALS["CONNECTION"];
    $base_dn=$GLOBALS["SUFFIX"];
    $attributes=array("samaccountname");
    $attrsonly=null;
    $sizelimit=null;
    $filter="(&(objectClass=user)(sAMAccountName=*))";
    $timelimit= $GLOBALS["LDAP_TIME_LIMIT"];

    $sr =@ldap_search($link_identifier,$base_dn,$filter,$attributes,$attrsonly, $sizelimit, $timelimit);

    if (!$sr) {
        if($GLOBALS["output"]){echo "Bad search $dn / $filter\n";}
        WLOG("[QUERY]: Bad search $dn / $filter");
        return array();
    }




    $hash=@ldap_get_entries($GLOBALS["CONNECTION"],$sr);
    $MembersCount=$hash["count"];
    if($GLOBALS["output"]){echo "return $MembersCount entries\n";}
    for($i=0;$i<$MembersCount;$i++){

        $member=$hash[$i]["samaccountname"][0];
        $f[$member]=$member;

    }

    foreach ($f as $a=>$b){
        if(trim($b)==null){continue;}
        $Tosend[]=$b;
    }

    return $Tosend;

}


function HashUsersFromGroupDN($dn){
    $ORGDN=$dn;
    $FUNCTION=__FUNCTION__;
    TestConnectToLDAP();
    if(isset($GLOBALS["HashUsersFromGroupDN($ORGDN)"])){return $GLOBALS["HashUsersFromGroupDN($ORGDN)"];}
    $f=array();

    $link_identifier=$GLOBALS["CONNECTION"];
    $base_dn=$dn;
    $attributes=array("member","memberOf");
    $attrsonly=null;
    $sizelimit=null;
    $filter="(objectClass=*)";
    $timelimit= $GLOBALS["LDAP_TIME_LIMIT"];

    $sr =@ldap_search($link_identifier,$base_dn,$filter,$attributes,$attrsonly, $sizelimit, $timelimit);
    if (!$sr) {WLOG("[QUERY]: $FUNCTION() Bad search $dn / $filter");return array();}
    $hash=@ldap_get_entries($GLOBALS["CONNECTION"],$sr);
    if(!is_array($hash)){WLOG("[QUERY]:HashUsersFromGroupDN() Not an array...$dn / $filter");return array();}

    $MembersCount=$hash[0]["member"]["count"];
    if($GLOBALS["VERBOSE"]){echo "HashUsersFromGroupDN():: $MembersCount member(s) $dn / $filter\n";}

    for($i=0;$i<$MembersCount;$i++){
        if($GLOBALS["VERBOSE"]){echo "HashUsersFromGroupDN():: MemberName = {$hash[0]["member"][$i]}\n";}
        $MemberName=MemberInfoByDN($hash[0]["member"][$i]);
        if($MemberName==null){WLOG("[QUERY]:HashUsersFromGroupDN() {$hash[0]["member"][$i]} NO NAME!");continue;}
        if(is_array($MemberName)){
            foreach ($MemberName as $a=>$b){
                if($GLOBALS["VERBOSE"]){echo "HashUsersFromGroupDN():: $dn USER= $b\n";}
                if(trim($b)==null){continue;}
                $f[$b]=$b;
            }
        }else{
            $f[$MemberName]=$MemberName;
        }


    }

    /*if(isset($hash[0]["memberof"]["count"])){
        for($i=0;$i<$hash[0]["memberof"]["count"];$i++){
            $dn1=$hash[0]["memberof"][0];
            $ff=HashUsersFromGroupDN($dn1);
            if(count($ff)>0){
                foreach ($ff as $a=>$b){
                    if(trim($b)==null){continue;}
                    $f[$b]=$b;
                }
            }

        }
    }
    */

    foreach ($f as $a=>$b){
        if(trim($b)==null){continue;}
        $Tosend[]=$b;
    }


    $GLOBALS["HashUsersFromGroupDN($ORGDN)"]=$Tosend;
    if($GLOBALS["VERBOSE"]){echo "HashUsersFromGroupDN():: --> ".count($Tosend)."\n";}
    return $Tosend;

}

function BuildDefault_ldap_server(){
    if(isset($GLOBALS["KerbAuthInfos"]["ADNETIPADDR"])){return $GLOBALS["KerbAuthInfos"]["ADNETIPADDR"];}
    if(isset($GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_NETBIOSNAME"])){return $GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_NETBIOSNAME"].".".$GLOBALS["KerbAuthInfos"]["WINDOWS_DNS_SUFFIX"];}
    $SMB=SAMBA_GetNetAdsInfos();
    return $SMB["LDAP server"];
    WLOG("[START]: BuildDefault_ldap_server did not find any LDAP server...");
}

function BuildDefault(){
    if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_SERVER"])){
        WLOG("KerbAuthInfos -> Try to find LDAP_SERVER");
        $GLOBALS["KerbAuthInfos"]["LDAP_SERVER"]=BuildDefault_ldap_server();
        WLOG("KerbAuthInfos -> {$GLOBALS["KerbAuthInfos"]["LDAP_SERVER"]}");
    }

    if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_PORT"])){$GLOBALS["KerbAuthInfos"]["LDAP_PORT"]=389;}
    if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_SUFFIX"])){
        $SMB=SAMBA_GetNetAdsInfos();
        $GLOBALS["KerbAuthInfos"]["LDAP_SUFFIX"]=$SMB["Bind Path"];
    }


}
function ConnectToPureLDAP(){
    $ldappassword=trim(@file_get_contents("/etc/artica-postfix/ldap_settings/password"));
    $suffix=@trim(@file_get_contents("/etc/artica-postfix/ldap_settings/suffix"));
    $server=@file_get_contents("/etc/artica-postfix/ldap_settings/server");
    $port=@file_get_contents("/etc/artica-postfix/ldap_settings/port");
    $admin=@file_get_contents("/etc/artica-postfix/ldap_settings/admin");
    if(trim($server)==null){$server="127.0.0.1";}
    if(trim($port)==null){$port="389";}
    if(trim($ldappassword)==null){$ldappassword="secret";}
    if(trim($admin)==null){$admin="Manager";}


    $GLOBALS["SUFFIX"]=$suffix;
    $GLOBALS["CONNECTION"]=ldap_connect($server,$port);


    if(!$GLOBALS["CONNECTION"]){
        WLOG("[LDAP]: Connecting TCP to LDAP server `$server:$port` failed");
        WLOG("[LDAP]: Fatal: ldap_connect()");
        @ldap_close();
        return false;
    }

    WLOG("[LDAP]: Connecting to LDAP server $server success with suffix: $suffix");
    ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);
    ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);



    $error=null;
    $GLOBALS["BIND_LDAP"]=ldap_bind($GLOBALS["CONNECTION"], "cn=$admin,$suffix", $ldappassword);
    if(!$GLOBALS["BIND_LDAP"]){

        if (@ldap_get_option($GLOBALS["CONNECTION"], LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
            $error=$error." $extended_error";
        }

        switch (ldap_errno($GLOBALS["CONNECTION"])) {
            case 0x31:
                $error=$error . " Bad username or password. Please try again.";
                break;
            case 0x32:
                $error=$error . " Insufficient access rights.";
                break;
            case 81:
                $error=$error . " Unable to connect to the LDAP server $server please, verify if ldap daemon is running  or the ldap server address";
                break;
            case -1:

                break;
            default:
                $error=$error . " Could not bind to the LDAP server." ." ". @ldap_err2str($GLOBALS["CONNECTION"]);
        }
        WLOG("[LDAP]: Login to LDAP server $server failed with $error (".__LINE__.")");
        return false;
    }
    //WLOG("[LDAP]: Binding to LDAP server $server <span style='font-weight:bold;color:#00B218'>success</span>.");
    return true;

}






function CleanSessions(){
    if(!isset($GLOBALS["SESSIONS"])){return;}
    if(!is_array($GLOBALS["SESSIONS"])){return;}
    $cachesSessions=unserialize(@file_get_contents("/etc/squid3/".basename(__FILE__).".cache"));
    if(isset($cachesSessions)){
        if(is_array($cachesSessions)){
            foreach ($cachesSessions as $md5=> $array){
                $GLOBALS["SESSIONS"][$md5]=$array;
            }
        }
    }
    @file_put_contents("/etc/squid3/".basename(__FILE__).".cache", serialize($GLOBALS["SESSIONS"]));
}

function LOCATE_NET_BIN_PATH(){
    $net=internal_find_program("net");
    if(is_file($net)){return $net;}
    $net=internal_find_program("net.samba3");
    if(is_file($net)){return $net;}
}


function SAMBA_GetNetAdsInfos(){

    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
    if($LockActiveDirectoryToKerberos==1){
        $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
        return $array;
    }

    if(isset($GLOBALS["CACHE_NET"])){return $GLOBALS["CACHE_NET"];}

    if(is_file("/etc/squid3/NET_ADS_INFOS")){
        $array=unserialize(@file_get_contents("/etc/squid3/NET_ADS_INFOS"));
        if(count($array)>5){
            $GLOBALS["CACHE_NET"]=$array;
            return $array;
        }
    }

    $net=LOCATE_NET_BIN_PATH();
    if(!is_file($net)){return array();}
    exec("$net ads info 2>&1",$results);
    foreach ($results as $index=>$line){
        if(preg_match("#^(.+?):(.+)#",trim($line),$re)){
            $array[trim($re[1])]=trim($re[2]);
        }
    }

    if(!isset($array["KDC server"])){$array["KDC server"]=null;}
    WLOG("$net ads info 2>&1 return ".count($array)." items");
    $GLOBALS["CACHE_NET"]=$array;
    @file_put_contents("/etc/squid3/NET_ADS_INFOS", serialize($array));
    return $array;
}


function internal_find_program($strProgram){
    global $addpaths;
    $arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin','/usr/local/sbin','/usr/kerberos/bin');
    if (function_exists("is_executable")) {foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}} else {return strpos($strProgram, '.exe');}
}



function WLOG($text=null){
    if($GLOBALS["VERBOSE"]){echo $text."\n";}
    if(!function_exists("openlog")){return false;}
    $filename="/var/log/squid/external-acl.log";
    $trace=@debug_backtrace();

    if(isset($trace[1])){
        $base=basename($trace[1]["file"]);
        $function=$trace[1]["function"];
        $line=$trace[1]["line"];
    }else{
        $base=basename(__FILE__);
        $function="unknown";
        $line=0;
    }



    $base=basename(__FILE__);
    $prefix="[$base] {$function}(): [L.$line]";
    openlog("external-acl", LOG_PID , LOG_SYSLOG);
    syslog(LOG_ERR, "$prefix$text");

    closelog();
    return true;
}


function ufdbguard_checks($id){
    LoadSettings();
    if($GLOBALS["VERBOSE"]){$GLOBALS["output"]=true;echo "OPEN: /etc/squid3/ufdb.groups.$id.db\n";}


    if(!is_file("/etc/squid3/ufdb.groups.$id.db")){
        if($GLOBALS["output"]){
            echo "Cannot perform this operation\nYou have to create correctly a Web filtering rule\nIncluding blacklists and whitelists\nAnd compile your Web filtering rules in order to dump groups\n";

        }
        return null;
    }

    $arrayGROUPS=unserialize(@file_get_contents("/etc/squid3/ufdb.groups.$id.db"));
    $FINAL=array();
    $Hash=array();
    $FINAL2=array();

    if($GLOBALS["VERBOSE"]){echo "DUMP: ARRAY ***********************\n";}
    if($GLOBALS["VERBOSE"]){print_r($arrayGROUPS);}
    if($GLOBALS["VERBOSE"]){echo "***********************************\n";}

    if(isset($arrayGROUPS["EXT-LDAP"])){
        if($GLOBALS["VERBOSE"]){echo "Found:EXT-LDAP\n";}
        $extn_ldap=new ldap_extern();
        foreach ($arrayGROUPS["EXT-LDAP"] as $index=>$DNS){
            if($GLOBALS["VERBOSE"]){echo "DN:$DNS\n";}
            $rr=$extn_ldap->HashUsersFromGroupDN($DNS);
            if($GLOBALS["output"]){echo "{$DNS} return ". count($rr)." users\n";}
            foreach ($rr as $a=>$b){
                $b=trim($b);
                if($b==null){continue;}
                echo "USER= $b\n";$MemberArray[$a]=$a;
            }
            foreach ($MemberArray as $a=>$b){
                $FINAL[]=$a;
            }


        }
    }


    if(isset($arrayGROUPS["EXTLDAP"])){
        foreach ($arrayGROUPS["EXTLDAP"] as $index=>$CONFS){
            $rr=external_ldap_members($CONFS["DN"],$CONFS["CONF"]);
            if($GLOBALS["output"]){echo "{$CONFS["DN"]} return ". count($rr)." users\n";}
            foreach ($rr as $a=>$b){
                echo "USER= $b\n";
                $MemberArray[$a]=$a;}
        }

        foreach ($MemberArray as $a=>$b){$FINAL[]=$a;}
    }


    if(isset($arrayGROUPS["AD"])){
        foreach ($arrayGROUPS["AD"] as $index=>$DNenc){
            $DNenc=trim($DNenc);
            if(preg_match("#^ADDYN:(.+?):(.+)#", $DNenc,$re)){
                if($GLOBALS["output"]){echo "---------------------------------------------------------\n";}
                include_once(dirname(__FILE__)."/ressources/class.ActiveDirectory.inc");
                $groupname=$re[1];
                $ActiveDirectoryHASH=unserialize(base64_decode($re[2]));
                if($GLOBALS["output"]){echo "Loading Active Directory groups FROM HASH...\n";}
                $ad=new ActiveDirectory(0,$ActiveDirectoryHASH);
                $Members=$ad->search_users_from_groupName($groupname);
                foreach ($Members as $user){$Hash[$user]=$user;}
                continue;

            }

            if(preg_match("#^OU:(.+)#", $DNenc,$re)){
                if($GLOBALS["output"]){echo "---------------------------------------------------------\n";}
                if($GLOBALS["output"]){echo "Loading Active Directory ORGANIZATION FROM {$re[1]}...\n";}
                include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
                $ad=new external_ad_search();
                $Ous=$ad->active_directory_ListOus($re[1]);
                foreach ($Ous as $dn=>$OuName){
                    if($GLOBALS["output"]){echo __LINE__."] Get Members from $dn\n";}
                    $HashOU=$ad->find_users($dn,"*");
                    $Count=$HashOU["count"];
                    for($i=0;$i<$Count;$i++){
                        $MAIN=$HashOU[$i];
                        if(isset($MAIN["samaccountname"])){
                            for($y=0;$y<$MAIN["samaccountname"]["count"];$y++){
                                $Hash[$MAIN["samaccountname"][$y]]=$MAIN["samaccountname"][$y];
                            }
                        }
                    }

                }
                continue;
            }



            if($GLOBALS["output"]){echo "Loading Active Directory groups...\n";}
            $DN=base64_decode($DNenc);
            if(strpos($DN, ";}")>0){
                if($GLOBALS["output"]){echo "Loading unserialize the value\n";}
                $settings=unserialize($DN);

            }
            if(is_array($settings)){
                if(isset($settings["LDAP_SERVER"])){
                    if($GLOBALS["output"]){echo "DN variable is an Array\n";}
                    if($GLOBALS["output"]){echo "Branch {$settings["dn"]}\n";}
                    if($GLOBALS["VERBOSE"]){echo "DN, {$settings["dn"]}\n";}
                    if($GLOBALS["output"]){echo "SamAccountName {$settings["samaccountname"]}\n";}
                    if($GLOBALS["output"]){echo "LDAP_SERVER {$settings["LDAP_SERVER"]}\n";}
                    if($GLOBALS["output"]){echo "LDAP_SUFFIX {$settings["LDAP_SUFFIX"]}\n";}
                    if($GLOBALS["output"]){echo "LDAP_DN {$settings["LDAP_DN"]}\n";}
                    $ldapExt=new external_acl_squid_ldap();
                    $members=$ldapExt->AdLDAP_MembersFromGroup($settings["dn"],$settings);
                    if($GLOBALS["VERBOSE"]){echo "DN, {$settings["dn"]} -> ". count($members)."\n";}
                    foreach ($members as $a=>$b){$Hash[$b]=$b;}
                    continue;

                }

            }



            if($GLOBALS["output"]){echo "Branch $DN\n";}
            if($GLOBALS["VERBOSE"]){echo "DN, $DN\n";}
            $ldapExt=new external_acl_squid_ldap();
            $members=$ldapExt->AdLDAP_MembersFromGroup($DN);
            if($GLOBALS["VERBOSE"]){echo "DN, $DN -> ". count($members)."\n";}
            foreach ($members as $a=>$b){$Hash[$b]=$b;}

        }

        foreach ($Hash as $a=>$b){
            if(is_array($b)){continue;}
            if($GLOBALS["VERBOSE"]){echo "USER = $b\n";}
            $FINAL[]=$b;
        }

    }

    if(isset($arrayGROUPS["LDAP"])){
        foreach ($arrayGROUPS["LDAP"] as $index=>$gpid){
            $Hash=HashUsersFromGPID($gpid);
            if(count($Hash)==0){WLOG("[QUERY]: ufdbguard_checks($id) GPID:$gpid store no user...");continue;}
            foreach ($Hash as $a=>$b){if($GLOBALS["VERBOSE"]){echo "USER= $b\n";}$FINAL[]=$b;}
        }


    }


    if($GLOBALS["output"]){echo "\nResults\n**********************************\n# # # # # # # # # # # # # # # # # # # # # #\n". count($FINAL)." item(s)\n# # # # # # # # # # # # # # # # # # # # # #\n";}

    if(count($FINAL)==0){
        WLOG("[QUERY]: ufdbguard_checks($id) no user...");
        return;
    }
    foreach ($FINAL as $Member){
        $Member=trim($Member);
        if($Member==null){continue;}
        $Member=str_replace(" ", "%20", $Member);
        $FINAL2[]=strtolower($Member);
    }



    echo @implode("\n",$FINAL2)."\n";


}

function external_ldap_members($dn,$conf){
    $ldap_host=$conf["ldap_server"];
    $ldap_port=$conf["ldap_port"];
    $ldap_admin=$conf["ldap_user"];
    $ldap_password=$conf["ldap_password"];
    $suffix=$conf["ldap_suffix"];
    $ldap_filter_users=$conf["ldap_filter_users"];
    $ldap_filter_group=$conf["ldap_filter_group"];

    if(preg_match("#^ExtLdap:(.+)#", $dn,$re)){$dn=$re[1];}

    if($GLOBALS["output"]){echo "$ldap_host:$ldap_port -> $ldap_filter_group\n";}

    if(!is_numeric($ldap_port)){$ldap_port=389;}
    if(!function_exists("ldap_connect")){
        if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";writeLogs("-> Call to undefined function ldap_connect() $called".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}
        return array();
    }

    $ldap_connection=@ldap_connect($ldap_host, $ldap_port ) ;
    if(!$ldap_connection){
        WLOG("Fatal: ldap_connect -> $ldap_host:$ldap_port FAILED");
        return array();
    }


    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
    $ldapbind=@ldap_bind($ldap_connection, $ldap_admin, $ldap_password);

    if(!$ldapbind){
        $error=ldap_err2str(ldap_err2str(ldap_errno($ldap_connection)));
        @ldap_close($ldap_connection);
        WLOG("Fatal: ldap_bind -> $ldap_host:$ldap_port FAILED $error");
        return array();
    }

    if(preg_match_all("#\((.+?)=(.+?)\)#", $ldap_filter_group,$re)){
        foreach ($re[1] as $key=>$line){
            if($re[2][$key]=="*"){ $MemberAttribute=$line; }
        }
    }
    if($GLOBALS["output"]){echo "DN -> Member attribute = $dn\n";}
    if($GLOBALS["output"]){echo "$ldap_filter_group -> Member attribute = $MemberAttribute\n";}
    $pattern=str_replace("%u", "*", $ldap_filter_group);
    $sr =@ldap_search($ldap_connection,$dn,$pattern,array());
    if(!$sr){
        $error=ldap_err2str(ldap_err2str(ldap_errno($ldap_connection)));
        @ldap_close($ldap_connection);
        WLOG("Fatal: ldap_search -> $pattern FAILED $error");
        return array();
    }
    $filter=array("cn","description",'sAMAccountName',"dn","member","memberOf","userPrincipalName");
    $f=array();
    $result = @ldap_get_entries($ldap_connection, $sr);
    for($i=0;$i<$result["count"];$i++){
        if(isset($result[$i][$MemberAttribute]["count"])){
            for($z=0;$z<$result[$i][$MemberAttribute]["count"];$z++){
                $uid=$result[$i][$MemberAttribute][$z];
                $uids=GetAccountFromDistinguishedName($uid,$ldap_connection,$MemberAttribute,$dn);
                if(count($uids)>0){
                    foreach ($uids as $ind=>$fnd){
                        $f[$ind]=$ind;
                    }
                    continue;
                }

                $TRANS=explode(",",$uid);
                $sfilter="userPrincipalName|cn|uid|memberUid|sAMAccountName|member|memberOf";
                foreach ($TRANS as $fnd){
                    if(preg_match("#^($sfilter)=(.+)#i", $fnd,$re)){
                        $uid=trim($re[2]);
                        $f[$uid]=$uid;
                        break;
                    }
                }
            }
        }
    }

    return $f;
}

function GetAccountFromDistinguishedName($distinguised,$ldap_connection,$MemberAttribute,$dn):array{
    $dsn=array();
    $pattern="(&(objectClass=user)(distinguishedName=$distinguised))";
    $sr =@ldap_search($ldap_connection,$dn,$pattern,array());

    if(!$sr){
        $error=ldap_err2str(ldap_err2str(ldap_errno($ldap_connection)));
        @ldap_close($ldap_connection);
        WLOG("Fatal: ldap_search -> $pattern in $dn FAILED $error");
        return array();
    }
    $result2 = @ldap_get_entries($ldap_connection, $sr);
    for($i=0;$i<$result2["count"];$i++){
        if(isset($result2[$i][$MemberAttribute]["count"])){
            for($z=0;$z<$result2[$i][$MemberAttribute]["count"];$z++){
                $dsn[$result2[$i][$MemberAttribute][$z]]=$result2[$i][$MemberAttribute][$z];
            }
        }
    }
    return $dsn;
}


function HashUsersFromGPID($gpid){
    $array=array();
    if(!is_numeric($gpid)){return array();}
    TestConnectToPureLDAP();
    $sr =@ldap_search($GLOBALS["CONNECTION"],$GLOBALS["SUFFIX"],"(&(gidnumber=$gpid)(objectclass=posixGroup))");
    if(!$sr){WLOG("[QUERY]:HashUsersFromGPID::$gpid Ressource false query (&(gidnumber=$gpid)(objectclass=posixGroup))");return array();}
    $entry_id =@ldap_first_entry($GLOBALS["CONNECTION"],$sr);
    if(!$entry_id){WLOG("[QUERY]:HashUsersFromGPID::$gpid entry_id false query (gidnumber=$gpid)");return array();}
    $attrs = @ldap_get_attributes($GLOBALS["CONNECTION"], $entry_id);
    if(!isset($attrs["memberUid"])){return array();}
    for($i=0;$i<$attrs["memberUid"]["count"];$i++){
        $array[$attrs["memberUid"][$i]]=$attrs["memberUid"][$i];
    }
    return $array;
}
function AccountDecode($path){
    if(strpos($path, "%")==0){return $path;}
    $path=str_replace("%C3%C2§","ç",$path);
    $path=str_replace("%5C","\\",$path);
    $path=str_replace("%20"," ",$path);
    $path=str_replace("%0A","\n",$path);
    $path=str_replace("%C2£","£",$path);
    $path=str_replace("%C2§","§",$path);
    $path=str_replace("%C3§","ç",$path);
    $path=str_replace("%E2%82%AC","€",$path);
    $path=str_replace("%C3%89","É",$path);
    $path=str_replace("%C3%A9","é",$path);
    $path=str_replace("%C3%A0","à",$path);
    $path=str_replace("%C3%AA","ê",$path);
    $path=str_replace("%C3%B9","ù",$path);
    $path=str_replace("%C3%A8","è",$path);
    $path=str_replace("%C3%A2","â",$path);
    $path=str_replace("%C3%B4","ô",$path);
    $path=str_replace("%C3%AE","î",$path);
    $path=str_replace("%C3%BB","û",$path);
    $path=str_replace("%E9","é",$path);
    $path=str_replace("%E0","à",$path);
    $path=str_replace("%F9","ù",$path);
    $path=str_replace("%20"," ",$path);
    $path=str_replace("%E8","è",$path);
    $path=str_replace("%E7","ç",$path);
    $path=str_replace("%26","&",$path);
    $path=str_replace("%FC","ü",$path);
    $path=str_replace("%2F","/",$path);
    $path=str_replace("%F6","ö",$path);
    $path=str_replace("%EB","ë",$path);
    $path=str_replace("%EF","ï",$path);
    $path=str_replace("%EE","î",$path);
    $path=str_replace("%EA","ê",$path);
    $path=str_replace("%E2","â",$path);
    $path=str_replace("%FB","û",$path);
    $path=str_replace("%u20AC","€",$path);
    $path=str_replace("%u2014","–",$path);
    $path=str_replace("%u2013","—",$path);
    $path=str_replace("%24","$",$path);
    $path=str_replace("%21","!",$path);
    $path=str_replace("%23","#",$path);
    $path=str_replace("%2C",",",$path);
    $path=str_replace("%7E",'~',$path);
    $path=str_replace("%22",'"',$path);
    $path=str_replace("%25",'%',$path);
    $path=str_replace("%27","'",$path);
    $path=str_replace("%F8","ø",$path);
    $path=str_replace("%2C",",",$path);
    $path=str_replace("%3A",":",$path);
    $path=str_replace("%A1","¡",$path);
    $path=str_replace("%A7","§",$path);
    $path=str_replace("%B2","²",$path);
    $path=str_replace("%3B",";",$path);
    $path=str_replace("%3C","<",$path);
    $path=str_replace("%3E",">",$path);
    $path=str_replace("%B5","µ",$path);
    $path=str_replace("%B0","°",$path);
    $path=str_replace("%7C","|",$path);
    $path=str_replace("%5E","^",$path);
    $path=str_replace("%60","`",$path);
    $path=str_replace("%25","%",$path);
    $path=str_replace("%A3","£",$path);
    $path=str_replace("%3D","=",$path);
    $path=str_replace("%3F","?",$path);
    $path=str_replace("%3F","€",$path);
    $path=str_replace("%28","(",$path);
    $path=str_replace("%29",")",$path);
    $path=str_replace("%5B","[",$path);
    $path=str_replace("%5D","]",$path);
    $path=str_replace("%7B","{",$path);
    $path=str_replace("%7D","}",$path);
    $path=str_replace("%2B","+",$path);
    $path=str_replace("%40","@",$path);
    $path=str_replace("%09","\t",$path);
    $path=str_replace("%u0430","а",$path);
    $path=str_replace("%u0431","б",$path);
    $path=str_replace("%u0432","в",$path);
    $path=str_replace("%u0433","г",$path);
    $path=str_replace("%u0434","д",$path);
    $path=str_replace("%u0435","е",$path);
    $path=str_replace("%u0451","ё",$path);
    $path=str_replace("%u0436","ж",$path);
    $path=str_replace("%u0437","з",$path);
    $path=str_replace("%u0438","и",$path);
    $path=str_replace("%u0439","й",$path);
    $path=str_replace("%u043A","к",$path);
    $path=str_replace("%u043B","л",$path);
    $path=str_replace("%u043C","м",$path);
    $path=str_replace("%u043D","н",$path);
    $path=str_replace("%u043E","о",$path);
    $path=str_replace("%u043F","п",$path);
    $path=str_replace("%u0440","р",$path);
    $path=str_replace("%u0441","с",$path);
    $path=str_replace("%u0442","т",$path);
    $path=str_replace("%u0443","у",$path);
    $path=str_replace("%u0444","ф",$path);
    $path=str_replace("%u0445","х",$path);
    $path=str_replace("%u0446","ц",$path);
    $path=str_replace("%u0447","ч",$path);
    $path=str_replace("%u0448","ш",$path);
    $path=str_replace("%u0449","щ",$path);
    $path=str_replace("%u044A","ъ",$path);
    $path=str_replace("%u044B","ы",$path);
    $path=str_replace("%u044C","ь",$path);
    $path=str_replace("%u044D","э",$path);
    $path=str_replace("%u044E","ю",$path);
    $path=str_replace("%u044F","я",$path);
    return $path;
}
