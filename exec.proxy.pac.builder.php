<?php
$GLOBALS["PROXY_PAC_DEBUG"]=0;
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["TITLENAME"]="Dynamic Proxy PAC";
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
LoadIncludes();

if(isset($argv[1])){
    if($argv[1]=="--build"){build();exit;}
    if($argv[1]=="--export"){export_rule($argv[2]);exit;}
    if($argv[1]=="--import"){import_rule($argv[2]);exit;}
    if($argv[1]=="--tables"){patch_tables();exit;}
    if($argv[1]=="--office365"){echo build_office365_function();exit;}

}

proxy_pac();

function LoadIncludes():bool{
    include_once(dirname(__FILE__)."/ressources/class.user.inc");
    include_once(dirname(__FILE__)."/ressources/class.groups.inc");
    include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
    include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
    include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
    include_once(dirname(__FILE__)."/ressources/class.squid.inc");
    include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
    include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
    include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
    include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
    return true;
}


function build():bool{
    $unix=new unix();
    $rm=$unix->find_program("rm");
    if(is_dir("/home/squid/proxy_pac_rules")){
        build_progress("Removing old configuration....",10);
        system("$rm -rf /home/squid/proxy_pac_rules/*");
    }else{
        @mkdir("/home/squid/proxy_pac_rules",0755,true);
    }


    build_progress("{configuring}",20);
    proxy_pac();
    build_progress("{configuring} {success}",100);
    return true;

}



function build_progress($text="Unknown",$pourc=0){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/autoconfiguration.apply.progress";
    echo "$pourc% $text\n";
    $array=array();
    $cachefile=$GLOBALS["CACHEFILE"];
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);

}
function progress_export($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"pac.rule.export.progress");
}
function progress_import($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"pac.rule.import.progress");
}
function export_rule($ruleid):bool{
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $GROUPS=array();
    progress_export(30,"{exporting}");

    $ligne=$q->mysqli_fetch_array("SELECT * FROM wpad_rules WHERE ID='$ruleid'");
    foreach ($ligne as $key=>$value) {
        $MAIN["RULE"][$key] = $value;
    }

    progress_export(40,"{exporting}");
    $sql="SELECT * FROM wpad_destination_rules WHERE aclid=$ruleid ORDER BY zorder";
    $MAIN["wpad_destination_rules"] = $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}
    echo "wpad_destination_rules: ".count($MAIN["wpad_destination_rules"])." elements\n";


    progress_export(50,"{exporting}");
    $sql="SELECT * FROM wpad_sources_link WHERE aclid=$ruleid";
    $results = $q->QUERY_SQL($sql);
    $MAIN["wpad_sources_link"]=$results;
    foreach ($results as $index=>$ligne){$GROUPS[$ligne["gpid"]]=true;}

    progress_export(60,"{exporting}");
    $sql="SELECT * FROM wpad_white_link WHERE aclid=$ruleid";
    $results = $q->QUERY_SQL($sql);
    $MAIN["wpad_white_link"]=$results;
    foreach ($results as $index=>$ligne){$GROUPS[$ligne["gpid"]]=true;}

    progress_export(70,"{exporting}");
    $sql="SELECT * FROM wpad_destination WHERE aclid=$ruleid";
    $MAIN["wpad_destination"] = $q->QUERY_SQL($sql);


    progress_export(75,"{exporting}");
    foreach ($GROUPS as $gpid=>$none){
        $ligne=$q->mysqli_fetch_array("SELECT * FROM webfilters_sqgroups WHERE ID='$gpid'");
        $MAIN["GROUPS"][$gpid]["CONF"]=$ligne;

        $sql="SELECT * FROM webfilters_sqitems WHERE gpid=$gpid";
        $MAIN["GROUPS"][$gpid]["ITEMS"] = $q->QUERY_SQL($sql);
    }

    progress_export(80,"{compressing}");
    $encoded=base64_encode(serialize($MAIN));
    @file_put_contents(PROGRESS_DIR."/pac.rule.$ruleid.txt",$encoded);
    if(!$unix->compress(PROGRESS_DIR."/pac.rule.$ruleid.txt",PROGRESS_DIR."/pac.rule.$ruleid.gz")){
        progress_export(110,"{compressing} {failed}");
        @unlink(PROGRESS_DIR."/pac.rule.$ruleid.txt");
        return false;
    }
    progress_export(100,"{exporting} {success}");
    return true;

}
function patch_tables():bool{
    include_once(dirname(__FILE__)."/ressources/proxypac.sqlite.inc");
    sqlite_patch_tables();
    return true;
}

function import_rule($path):bool{

    if(is_file(dirname(__FILE__)."/ressources/conf/upload/$path")){
        $path=dirname(__FILE__)."/ressources/conf/upload/$path";
    }

    if(is_numeric($path)){
        $path="pac.rule.$path.gz";
    }

    if(!is_file($path)){
        $path=PROGRESS_DIR."/$path";
    }


    if(!is_file($path)){
        echo "Unable to stat $path\n";
        progress_import(110,"{import} {failed}");
        return false;
    }

    $unix=new unix();
    $tmppath=$unix->FILE_TEMP();
    if(!$unix->uncompress($path,$tmppath)){
        @unlink($path);
        echo "Unable to uncompress $path\n";
        progress_import(110,"{import} {failed}");
        return false;
    }
    $MAIN=$GLOBALS["CLASS_SOCKETS"]->unserializeb64(@file_get_contents($tmppath));
    @unlink($path);
    @unlink($tmppath);

    if(!is_array($MAIN)){
        echo "Corrupted file $path\n";
        progress_import(110,"{import} {failed}");
        return false;
    }

    if(!isset($MAIN["RULE"])){
        echo "Corrupted file $path\n";
        progress_export(110,"{import} {failed}");
        return false;
    }
    $fields=array();
    $values=array();
    patch_tables();
    $oldruleid=$MAIN["RULE"]["ID"];
    foreach ($MAIN["RULE"] as $field=>$value){
        if($field=="ID"){continue;}
        $fields[]="`$field`";
        $values[]="'$value'";
    }

    $sql="INSERT INTO wpad_rules (".@implode(",",$fields).") VALUES (".@implode(",",$values).")";
    echo $sql."\n";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        progress_import(110,"{import} {failed}");
        return false;
    }
    $newruleid=$q->last_id;
    echo "Old rule ID:$oldruleid --> $newruleid\n";

    echo "wpad_destination_rules: ".count($MAIN["wpad_destination_rules"])." elements\n";
    echo "wpad_destination......: ".count($MAIN["wpad_destination_rules"])." elements\n";
    echo "wpad_sources_link.....: ".count($MAIN["wpad_sources_link"])." elements\n";
    echo "Groups................: ".count($MAIN["GROUPS"])." elements\n";


    progress_import(30,"{importing}");

    if(count($MAIN["wpad_destination"])>0){
        foreach ($MAIN["wpad_destination"] as $index=>$line){
            $fields=array();
            $values=array();
            foreach ($line as $field=>$value){
                if($field=="aclid"){$value=$newruleid;}
                if($field=="zmd5"){$value=md5(serialize($line).$newruleid);}
                $fields[]="`$field`";
                $values[]="'$value'";
            }

            $sql="INSERT INTO wpad_destination (".@implode(",",$fields).") VALUES (".@implode(",",$values).")";
            echo $sql."\n";
            $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
            $q->QUERY_SQL($sql);
            if(!$q->ok){
                echo $q->mysql_error."\n";
                progress_export(110,"{import} {failed}");
                return false;
            }
        }
    }

    $TRANSLATED_GROUPS=array();

    progress_import(50,"{importing}");
    if(count($MAIN["GROUPS"])>0){
        echo "Importing groups....\n";
        foreach ($MAIN["GROUPS"] as $gpidsrc=>$GPRS){
            $CONF=$GPRS["CONF"];
            echo "Importing group $gpidsrc...\n";
            $fields=array();
            $values=array();
            foreach ($CONF as $field=>$value){
                if($field=="ID"){continue;}
                $fields[]="`$field`";
                $values[]="'$value'";
            }
            $sql="INSERT INTO webfilters_sqgroups (".@implode(",",$fields).") VALUES (".@implode(",",$values).")";
            $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
            $q->QUERY_SQL($sql);
            if(!$q->ok){
                echo $q->mysql_error."\n";
                progress_export(110,"{import} {failed}");
                return false;
            }
            $newgpid=$q->last_id;
            echo "Importing group $gpidsrc --> $newgpid...\n";
            $TRANSLATED_GROUPS[$gpidsrc]=$newgpid;
            $ITEMS=$GPRS["ITEMS"];
            foreach ($ITEMS as $index=>$SARRAY){
                $fields=array();
                $values=array();
                foreach ($SARRAY as $key=>$value){
                    if($key=="ID"){continue;}
                    if($key=="gpid"){$value=$newgpid;}
                    $fields[]="`$key`";
                    $values[]="'$value'";
                }
                $sql="INSERT INTO webfilters_sqitems (".@implode(",",$fields).") VALUES (".@implode(",",$values).")";
                $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
                $q->QUERY_SQL($sql);
                if(!$q->ok){
                    echo $q->mysql_error."\n$sql\n";
                    progress_export(110,"{import} {failed}");
                    return false;
                }
            }

        }

    }

    progress_import(60,"{importing}");
    if(count($MAIN["wpad_sources_link"])>0){
        foreach ($MAIN["wpad_sources_link"] as $index=>$ligne){
            $gpidsrc=$ligne["gpid"];
            $SourceGroupID=$TRANSLATED_GROUPS[$gpidsrc];
            $zmd5=md5("$newruleid$SourceGroupID");
            $zorder=intval($ligne["zorder"]);
            $negation=intval($ligne["negation"]);
            echo "[$index]: Translating link from $gpidsrc to $SourceGroupID rule.$newruleid\n";
            $q->QUERY_SQL("INSERT INTO wpad_sources_link (zmd5,aclid,negation,gpid,zorder) VALUES ('$zmd5','$newruleid','$negation','$SourceGroupID',$zorder)");
        }
    }

    progress_import(70,"{importing}");
    if(count($MAIN["wpad_white_link"])>0){
        foreach ($MAIN["wpad_white_link"] as $index=>$ligne){
            $gpidsrc=$ligne["gpid"];
            $SourceGroupID=$TRANSLATED_GROUPS[$gpidsrc];
            $zmd5=md5("$newruleid$SourceGroupID");
            $zorder=intval($ligne["zorder"]);
            $negation=intval($ligne["negation"]);
            echo "[$index]: Translating link from $gpidsrc to $SourceGroupID rule.$newruleid\n";
            $q->QUERY_SQL("INSERT INTO wpad_white_link (zmd5,aclid,negation,gpid,zorder) VALUES ('$zmd5','$newruleid','$negation','$SourceGroupID',$zorder)");
        }
    }


    progress_import(100,"{importing} {success}");
    return true;

}



function PID_NUM():int{

    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/proxy-pac/http-server.pid");
    if($unix->process_exists($pid)){
        return $pid;
    }
    return $unix->PIDOF("/usr/sbin/proxy-pac");
}


function proxy_pac():bool{
    LoadIncludes();
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    patch_tables();

    $sql="SELECT * FROM wpad_rules WHERE enabled=1 ORDER BY zorder";
    $results = $q->QUERY_SQL($sql);
    if(count($results)==0){exit();}
    $DenyDnsResolve=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DenyDnsResolve"));

    if(!is_file("/etc/init.d/proxy-pac")){
        build_progress("{install_service}",20);
        $php=$unix->LOCATE_PHP5_BIN();
        system("$php /usr/share/artica-postfix/exec.squid.autoconfig.php --install-service");

    }
    @mkdir("/home/squid/proxy_pac_rules");
    $f=array();
    $f[]="function FindProxyForURL(url, host) {";
    $f[]="return \"DIRECT\";";
    $f[]="}";
    @file_put_contents("/home/squid/proxy_pac_rules/default.pac",@implode("\n",$f));
    $f=array();
    $MAIN_RULES=array();
    foreach($results as $index=>$ligne) {
        $rulename=$ligne["rulename"];
        build_progress("{configuring} $rulename",30);
        echo "[PAC]: Rule: $index. $rulename\n";
        $FinishbyDirect=$ligne["FinishbyDirect"];
        $NomadeResolve=null;
        $NomadeMode=intval($ligne["NomadeMode"]);
        if(!is_null($ligne["NomadeResolve"])) {
            $NomadeResolve = trim($ligne["NomadeResolve"]);
        }
        $LBL=intval($ligne["LBL"]);
        $ID=$ligne["ID"];
        $office365=intval($ligne["office365"]);

        $MAIN_RULES[]="$ID|$rulename";
        @mkdir("/home/squid/proxy_pac_rules/$ID",0755,true);
        client_matches($ID);
        pac_except($ID);

        $f[]="function FindProxyForURL(url, host) {";
        $f[]="//\tVersion Engine for 4.50 SP 0 - 24 Oct 2023";
        $f[]="\turl = url.toLowerCase();";
        $f[]="\thost = host.toLowerCase();";

        if($NomadeMode==1){
            if(!is_null($NomadeResolve)){
                $f[]="\tif( !isResolvable(\"$NomadeResolve\") ){";
                $f[]="\t\treturn \"DIRECT\";";
                $f[]="\t}";
            }
        }

        if($DenyDnsResolve==0){
            $f[]="\tvar hostIP = dnsResolve(host);";
        }else{
            $f[]="\tvar hostIP = host;";
        }
        $f[]="\tvar myip=myIpAddress();";
        $f[]="\tvar ipBits = myip.split(\".\");";
        $f[]="\t// Retrieve the last byte of IP address";
        $f[]="\tvar mySeg = parseInt(ipBits[3]);";
        $f[]="\tvar DestPort=GetPort(url);";
        $f[]="\tvar PROTO='';";
        $f[]="\tif (url.substring(0, 5) == 'http:' ){ PROTO='HTTP'; }";
        $f[]="\tif (url.substring(0, 6) == 'https:' ){ PROTO='HTTPS'; }";
        $f[]="\tif (url.substring(0, 4) == 'ftp:' ){ PROTO='FTP'; }";
        $f[]="";
        $f[]="";
        $f[]="// Rule: $rulename";

        list($exprs,$functions)=forced_proxies($ID,$FinishbyDirect,$LBL);

        //$build_hooked=build_hooked($ID,$FinishbyDirect,$LBL);

       // $f[]="\tif( ForceProxies(DestPort,hostIP,host) ){";
      //  $f[]="\t\t".build_proxies($ID,0,$LBL);
       // $f[]="\t}";
        $f[]="";
        $f[]="//\t -- GLOBAL WHITELISTS --";

        if($office365==1){
            $f[]="//\t -- Office365 Return direct --";
            $f[]="\tif( isOffice365(host) ){";
            $f[]="\t\treturn \"DIRECT\";";
            $f[]="\t}";

        }

        $f[]=build_whitelist($ID);
        $f[]="";
        $f[]=$exprs;
        //$f[]=$build_hooked["BEGIN"];

        $f[]=build_subrules($ID);
        $f[]=build_proxies($ID,$FinishbyDirect,$LBL);
        $f[]="// End build proxies";
        $f[]="}\r\n";

        //$f[]=$build_hooked["END"];
        //$f[]=build_forced($ID);
        if($office365==1){
            $f[]=build_office365_function();
        }
        $f[]=$functions;
        $f[]="function GetPort(TestURL){";
        $f[]="\tTestURLRegex = /^[^:]*\:\/\/([^\/]*).*/;";
        $f[]="\tTestURLMatch = TestURL.replace(TestURLRegex, \"$1\");";
        $f[]="\tTestURLLower = TestURLMatch.toLowerCase();";
        $f[]="\tTestURLLowerRegex = /^([^\.]*)[^\:]*(.*)/;";
        $f[]="\tNewPort=TestURLLower.replace(TestURLLowerRegex, \"$2\");";
        $f[]="\tif (NewPort === \"\"){";
        $f[]="\t\tNewPort=\":80\";";
        $f[]="\t}";
        $f[]="\treturn NewPort;";
        $f[]="}";
        $f[]="\r\n\r\n";

        $script=@implode("\r\n", $f);
        echo "[PAC]: Rule: $ID/proxy.pac ".strlen($script)." Bytes\n";
        @file_put_contents("/home/squid/proxy_pac_rules/$ID/proxy.pac",$script);
        $script=null;
        $f=array();
    }

    @file_put_contents("/home/squid/proxy_pac_rules/rules.conf", @implode("\n", $MAIN_RULES));
    return true;

}
function build_office365_function():string{
    $unix=new unix();
    $f[]="function isOffice365(host){";
    $data=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Office365White"));
    if(!is_array($data)){
        if($GLOBALS["VERBOSE"]){
            echo "build_office365_function: Not an array...\n";
        }
        $data=array();
    }
    if(!isset($data["SRCDOMAINS"])){
        $data=array();
    }
   $jslist=array();
   foreach ($data["SRCDOMAINS"] as $domain=>$none){
       $domain=str_replace(".","\.",$domain);
        $jslist[]="\"$domain\"";

   }
    $f[]="\tconst OffDoms = [".@implode(",",$jslist)."];";
    $f[]="\tfor (let hostname of OffDoms) {";
    $f[]="\t\tlet regexPattern = new RegExp(\"(^|\\.)\" + hostname + \"$\");";
    $f[]="\t\tif (regexPattern.test(host)) { return true; }";
    $f[]="\t}";
    $f[]="\tconst HostIP=dnsResolve(host);";
    foreach ($data["IPS"] as $ipaddr=>$none){
        if(strpos($ipaddr,"/")==0){
            $f[]="\tif( isInNet(HostIP, \"$ipaddr\", \"255.255.255.255\") ){ return true;}";
            continue;
        }

        list($ip,$netmask)=cidrToIPNetmask($ipaddr);
        if(strlen($ip)<4){continue;}
        $f[]="\tif( isInNet(HostIP, \"$ip\", \"$netmask\") ){ return true;}";
    }

    $f[]="\treturn false;";
    $f[]="}\n";
    return @implode("\n",$f);


}
function cidrToIPNetmask($cidr):array {

    list($ip, $prefix) = explode('/', $cidr);
    $netmask = str_repeat('1', $prefix) . str_repeat('0', 32 - $prefix);
    $netmask = implode('.', array_map(function($segment) {
        return bindec($segment);
    }, str_split($netmask, 8)));

    return array($ip,  $netmask);
}
function build_subrules($ID):string{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT * FROM wpad_destination_rules WHERE aclid=$ID ORDER BY zorder";
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){return "";}
    $f[]="";
    $f[]="// build_subrules($ID)";
    $destinations_final=null;

    foreach($results as $index=>$ligne) {
        $destinations=array();
        $values=array();
        $value=trim($ligne["value"]);
        if($value==null){continue;}
        $xtype=$ligne["xtype"];

        $CarriageReturn=strpos($value, "\n");
        $f[]="// Carriage return = $CarriageReturn [".__LINE__."]";

        if($CarriageReturn==0){
            $f[]="// Carriage source = $value [".__LINE__."]";
            $values[]=$value;
        }else{
            $explode=explode("\n", $value);
            foreach ($explode as $Linz){
                $Linz=trim($Linz);
                if($Linz==null){continue;}
                $values[]=$Linz;
            }
            $f[]="// Carriage sources = ".count($values)." items [".__LINE__."]";
        }

        $value=trim($ligne["destinations"]);
        if($value<>null){
            $CarriageReturn=strpos($value, "\n");
            if($CarriageReturn==0){
                $value=trim($value);
                if(!preg_match("#^PROXY#", $value)){$value="PROXY $value";}
                $destinations[]=$value;
                $f[]="// Carriage return Proxy = $value [".__LINE__."]";
            }else{

                $explode=explode("\n", $value);
                foreach ($explode as $destline){
                    $destline=trim($destline);
                    if($destline==null){continue;}
                    if(!preg_match("#^PROXY#", $destline)){$destline="PROXY $destline";}
                    $destinations[]=$destline;
                }
            }



        }

        if(count($destinations)==0){$destinations_final="DIRECT";}
        if(count($destinations)>0){$destinations_final=@implode("; ", $destinations);}

        if($xtype=="shExpMatchRegex"){
            foreach ($values as $num=>$pattern){
                if($GLOBALS["VERBOSE"]){echo "shExpMatchRegex: $num: $pattern\n";}
                $f[]="\tif( url.match( /$pattern/i ) ){ return \"$destinations_final\"; }";
                $f[]="\tif( host.match( /$pattern/i ) ){ return \"$destinations_final\"; }";
            }
            continue;
        }

        if($xtype=="shExpMatch"){
            foreach ($values as $num=>$pattern){
                if($GLOBALS["VERBOSE"]){echo "shExpMatch: $num: $pattern\n";}
                $f[]="\tif( shExpMatch( url,\"$pattern\" ) ){ return \"$destinations_final\"; }";
                $f[]="\tif( shExpMatch( host,\"$pattern\" ) ){ return \"$destinations_final\"; }";
            }
            continue;
        }

        if($xtype=="isInNetMyIP"){
            foreach ($values as $num=>$pattern){
                $xt=explode("-",$pattern);
                $xt[0]=trim($xt[0]);
                $xt[1]=trim($xt[1]);
                if($xt[1]==null){$xt[1]="255.255.255.0";}
                if($GLOBALS["VERBOSE"]){echo "isInNet: $num: $pattern\n";}
                $f[]="\tif( isInNet( myip, \"{$xt[0]}\", \"{$xt[1]}\") ){ return \"$destinations_final\"; }";
            }
            continue;
        }

        if($xtype=="isInNet"){
            foreach ($values as $num=>$pattern){
                $xt=explode("-",$pattern);
                $xt[0]=trim($xt[0]);
                $xt[1]=trim($xt[1]);
                if($xt[1]==null){$xt[1]="255.255.255.0";}
                if($GLOBALS["VERBOSE"]){echo "isInNet: $num: $pattern\n";}
                $f[]="\tif( isInNet( hostIP, \"{$xt[0]}\", \"{$xt[1]}\") ){ return \"$destinations_final\"; }";
            }

        }


    }

    return @implode("\r\n\r\n", $f);

}
function pac_except($ID)
{
    $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $f = array();
    $results = $q->QUERY_SQL("SELECT *  FROM pac_except WHERE ruleid=$ID");
    @mkdir("/home/squid/proxy_pac_rules/$ID", 0755, true);

    foreach ($results as $index => $ligne) {
        $pattern = trim($ligne["pattern"]);
        if ($pattern == null) {
            continue;
        }
        $type = $ligne["type"];
        $f[] = "$type yes $pattern";
    }

    @file_put_contents("/home/squid/proxy_pac_rules/$ID/Except.rules", @implode("\n", $f));

}


function client_matches($ID):bool{
    $q      = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $f      = array();
    $sql    = "SELECT wpad_sources_link.gpid,wpad_sources_link.negation,wpad_sources_link.zmd5 as mkey,
	wpad_sources_link.zorder,
	webfilters_sqgroups.*
	FROM wpad_sources_link,webfilters_sqgroups
	WHERE wpad_sources_link.gpid=webfilters_sqgroups.ID
	AND wpad_sources_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_sources_link.zorder";

    @mkdir("/home/squid/proxy_pac_rules/$ID",0755,true);
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){writelogs("$ID $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
    if(count($results)==0){return false;}

    foreach($results as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $not="Yes";
        $matches=false;
        $GroupName=$ligne["GroupName"];
        $negation=$ligne["negation"];
        if($negation==1){$not="No";}
        echo "Rule.$ID: {$ligne["GroupType"]} ($GroupName):$negation\n";
        $f=array();


        $pacpxy=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["pacpxy"]);
        $c=0;
        foreach ($pacpxy as $indexR=>$array){
            if(!is_numeric($indexR)){continue;}
            $c++;
        }
        if($c>0){continue;}


        if($ligne["GroupType"]=="dstdomain"){
            $f[]="dstdomain $not null";
            continue;
        }


        if($ligne["GroupType"]=="all"){
            $f[]="all Yes null";
            continue;
        }


        if($ligne["GroupType"]=="srcproxy") {
            $f=matches_proxysrc($gpid, $not, $f);
            continue;
        }


        if($ligne["GroupType"]=="browser"){
            $f=matches_browser($gpid,$not,$f);
            continue;
        }
        if($ligne["GroupType"]=="srcdomain"){
            $f=matches_srcdomain($gpid,$not,$f);
            continue;
        }

        if($ligne["GroupType"]=="src"){
            $f=matches_src($gpid,$not,$f);
            continue;
        }

        if($ligne["GroupType"]=="time"){
            //$f=matches_time($gpid,$negation,$f);
            continue;

        }


    }
    if(count($f)==0){
        $f[]="all Yes null";
    }

    @file_put_contents("/home/squid/proxy_pac_rules/$ID/Sources.rules", @implode("\n", $f));


    $unix=new unix();
    @file_put_contents("/home/squid/proxy_pac_rules/myhostname",$unix->hostname_g());
    $IPADDRS=$unix->NETWORK_ALL_INTERFACES(true);
    foreach ($IPADDRS as $ipaddr=>$none){
        $tt[]=$ipaddr;

    }
    @file_put_contents("/home/squid/proxy_pac_rules/myips",@implode("\n",$tt));
    return true;
}


function matches_browser($gpid,$negation,$f=array()):array{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return $f;}
    if(count($results)==0){return $f;}

    foreach($results as $index=>$ligne) {
        $f[]="browser $negation {$ligne["pattern"]}";
    }

    return $f;
}
function matches_proxysrc($gpid,$negation,$f){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return $f;}
    if(count($results)==0){return $f;}

    foreach($results as $index=>$ligne) {
        $f[]="srcproxy $negation {$ligne["pattern"]}";
    }

    return $f;
}

function matches_srcdomain($gpid,$negation,$f){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $TO_MATCH=$GLOBALS["HOSTNAME"];
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return $f;}
    if(count($results)==0){return $f;}

    foreach($results as $index=>$ligne) {
        $ligne["pattern"]=str_replace(".", "\.", $ligne["pattern"]);
        $f[]="srcdomain $negation {$ligne["pattern"]}";
    }
    return $f;
}
function matches_src($gpid,$negation,$f){
    $ip=new IP();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    //src
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    $exclam=null;
    if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return $f;}
    if(count($results)==0){return $f;}


    foreach($results as $index=>$ligne) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if($pattern==null){continue;}
        $f[]="src $negation $pattern";

    }
    return $f;
}

function matches_time($gpid,$negation,$f){
    $ip=new IP();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern,other FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    $exclam=null;
    if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return $f;}
    if(count($results)==0){return $f;}

    foreach($results as $index=>$ligne) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if($pattern==null){continue;}

        $pattern=base64_decode($ligne["other"]);
        $TimeSpace=unserialize($pattern);
        if(!is_array($TimeSpace)){
            writelogs("$index Not supported pattern !is_array",__FUNCTION__,__FILE__,__LINE__);
            continue;
        }
        $fromtime=$TimeSpace["H1"];
        $tottime=$TimeSpace["H2"];

        if($fromtime=="00:00" && $tottime=="00:00"){
            continue;
        }


        $timerange1=strtotime(date("Y-m-d $fromtime:00"));
        $timerange2=strtotime(date("Y-m-d $tottime:00"));
        $timerange0=time();
        $days=array("0"=>"1","1"=>"2","2"=>"3","3"=>"4","4"=>"5","5"=>"6","6"=>"7");
        foreach ($TimeSpace as $key=>$ligne){
            if(preg_match("#^day_([0-9]+)#", $key,$re)){
                $dayT=$re[1];
                if($ligne<>1){
                    continue;
                }
                $dd[$days[$dayT]]=true;
            }
        }

        $CurrentDay=date('D');
        if($negation==1){
            if(!isset($dd[$CurrentDay])){
                if($timerange0 <= $timerange1){$result=true;}

            }
            continue;
        }
        if(isset($dd[$CurrentDay])){
            if($timerange0>=$timerange1){
                if($timerange0<=$timerange2){
                    $result=true;
                }
            }
        }
    }
    return $result;

}

function build_hooked($ID,$FinishbyDirect,$LBL):array{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $sql="SELECT wpad_black_link.gpid,wpad_black_link.negation,wpad_black_link.zmd5 as mkey,
	wpad_black_link.zorder,
	webfilters_sqgroups.*
	FROM wpad_black_link,webfilters_sqgroups
	WHERE wpad_black_link.gpid=webfilters_sqgroups.ID
	AND wpad_black_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_black_link.zorder";

    $results = $q->QUERY_SQL($sql);
    $CountObjects=count($results);
    if($CountObjects==0){return array("BEGIN"=>"// No object found for #$ID","END"=>"");}
    $BEGIN=array();
    $END=array();
    $BEGIN[]="//\tBuilding objects for rule.$ID";
    $case = 0;
    $Direct="";
    if($FinishbyDirect==1){$Direct="DIRECT";}
    $confirmLBL=false;
    if(!$q->ok){$BEGIN[]="// $q->mysql_error";}

    foreach($results as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $GroupName=$ligne["GroupName"];
        $negation=$ligne["negation"];
        $pacpxy=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["pacpxy"]);
        $pacproxs=array();
        $count=count($pacpxy);
        if(count($pacpxy)==0){continue;}
        if($ligne["GroupType"]=="all"){continue;}
        foreach ($pacpxy as $xyz=>$pacline){
            $proxyserver=trim($pacline["hostname"]);
            if($proxyserver==null){continue;}
            if($proxyserver=="0.0.0.0"){
                $pacproxs=array();
                $pacproxs[]="DIRECT";
                break;
            }
            if(intval($LBL)==1 && $count>1){
                $proxyport=$pacline["port"];
                $PREFIX="PROXY";
                if($pacline["secure"]==1){$PREFIX="HTTPS";}
                $slaves= "";

                foreach ($pacpxy as $slaveindex=>$slave){
                    $proxyslaveserver=trim($slave["hostname"]);
                    if ($proxyslaveserver==$proxyserver){
                        continue;
                    }
                    $proxyslaveport=$slave["port"];
                    $slaves .= " $PREFIX $proxyslaveserver:$proxyslaveport;";
                }
                //$pacproxs[]="$PREFIX $proxyserver:$proxyport";
                $pacproxs[] = "\n\t\tcase $case: return \"$PREFIX $proxyserver:$proxyport;$slaves $Direct\";";
                $case = $case+1;
                $confirmLBL=true;

            } else {
                $proxyport=$pacline["port"];
                $PREFIX="PROXY";
                if($pacline["secure"]==1){$PREFIX="HTTPS";}
                $pacproxs[]="$PREFIX $proxyserver:$proxyport";
            }
        }
        if(count($pacproxs)==0){continue;}
        if ($confirmLBL){
            $pr=@implode(" ", $pacproxs);
            $destinations_final="\tswitch (mySeg % {$count}) { $pr \n\t}";

        } else {
            $destinations_final=@implode("; ", $pacproxs);
        }

        $BEGIN[]="//\t[$xyz]: Checks Group [$GroupName]";
        $BEGIN[]="\tif( ForceGroup{$gpid}(DestPort,hostIP,host) ){";
        //$BEGIN[]="\t\treturn \"$destinations_final\";";
        if($confirmLBL){
            $BEGIN[] = $destinations_final;
        } else {
            $BEGIN[] = "\t\treturn \"$destinations_final\";";
        }
        $BEGIN[]="\t}\n";

        $END[]="";
        $END[]="function ForceGroup{$gpid}(DestPort,hostIP,host){";
        if($ligne["GroupType"]=="port"){ $END[]=build_whitelist_port($gpid,$negation,false); }
        if($ligne["GroupType"]=="dstdomain"){ $END[]=build_whitelist_dstdomain($gpid,$negation,false); }
        if($ligne["GroupType"]=="src"){ $END[]=build_whitelist_src($gpid,$negation,false); }
        if($ligne["GroupType"]=="dst"){ $END[]=build_whitelist_dst($gpid,$negation,false); }
        if($ligne["GroupType"]=="srcdomain"){ $END[]=build_whitelist_srcdomain($gpid,$negation,false); }
        if($ligne["GroupType"]=="time"){ $END[]=build_whitelist_time($gpid,$negation,false); }
        if($ligne["GroupType"]=="rgexsrc"){ $END[]=build_rgexsrc($gpid,$negation,false); }
        if($ligne["GroupType"]=="rgexdst"){ $END[]=build_rgexdst($gpid,$negation,false); }
        $END[]="\treturn false;";
        $END[]="}";


    }
    return array("BEGIN"=>@implode("\n",$BEGIN),"END"=>@implode("\n",$END));

}
function build_forced($ID){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $sql="SELECT wpad_black_link.gpid,wpad_black_link.negation,wpad_black_link.zmd5 as mkey,
	wpad_black_link.zorder,
	webfilters_sqgroups.*
	FROM wpad_black_link,webfilters_sqgroups
	WHERE wpad_black_link.gpid=webfilters_sqgroups.ID
	AND wpad_black_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_black_link.zorder";


    $f[]="function ForceProxies(DestPort,hostIP,host){";

    $results = $q->QUERY_SQL($sql);

    $CountObjects=count($results);
    if($CountObjects==0){
        $f[]="//\tNo force rules";
        $f[]="\treturn false;";
        $f[]="}";
        return @implode("\n",$f);

    }

    foreach($results as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $GroupName=$ligne["GroupName"];
        $negation=$ligne["negation"];
        $pacpxy=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["pacpxy"]);
        if(count($pacpxy)>0){continue;}

        if($ligne["GroupType"]=="all"){
            $f[]="//\tGroupType == all, make no sense but...";
            $f[]="\treturn true;";
            $f[]="}";
            return @implode("\n",$f);
        }

        if($ligne["GroupType"]=="port"){ $f[]=build_whitelist_port($gpid,$negation,false); continue;}
        if($ligne["GroupType"]=="dstdomain"){ $f[]=build_whitelist_dstdomain($gpid,$negation,false); continue;}
        if($ligne["GroupType"]=="src"){ $f[]=build_whitelist_src($gpid,$negation,false); continue;}
        if($ligne["GroupType"]=="dst"){ $f[]=build_whitelist_dst($gpid,$negation,false); continue;}
        if($ligne["GroupType"]=="srcdomain"){ $f[]=build_whitelist_srcdomain($gpid,$negation,false); continue;}
        if($ligne["GroupType"]=="time"){ $f[]=build_whitelist_time($gpid,$negation,false); continue;}
        writelogs("Not supported Group {$ligne["GroupType"]} - $GroupName",__FUNCTION__,__FILE__,__LINE__);


    }

    $f[]="\treturn false;";
    $f[]="}";
    return @implode("\n",$f);
}
function build_whitelist($ID){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM wpad_rules WHERE ID='$ID'");
    $dntlhstname=$ligne["dntlhstname"];
    $isResolvable=$ligne["isResolvable"];
    $f=array();

    if($dntlhstname==1){ $f[]="\tif ( isPlainHostName(host) ) { return \"DIRECT\"; }"; }
    if($isResolvable==1){ $f[]="\tif( isResolvable(host) ) { return \"DIRECT\"; }"; }



    $sql="SELECT wpad_white_link.gpid,wpad_white_link.negation,wpad_white_link.zmd5 as mkey,
	wpad_white_link.zorder,
	webfilters_sqgroups.*
	FROM wpad_white_link,webfilters_sqgroups
	WHERE wpad_white_link.gpid=webfilters_sqgroups.ID
	AND wpad_white_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_white_link.zorder";

    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){
        pack_debug("Fatal !! $ID $q->mysql_error",__FILE__,__LINE__);
        return @implode("\n", $f);
    }

    $CountObjects=count($results);
    if($CountObjects==0){
        pack_debug("Rule:[$ID] No whitelist groups set",__FUNCTION__,__LINE__);
        $f[]="\tif (isInNet(hostIP, \"10.0.0.0\", \"255.0.0.0\") || isInNet(hostIP, \"172.16.0.0\",  \"255.240.0.0\") || isInNet(hostIP, \"192.168.0.0\", \"255.255.0.0\") || isInNet(hostIP, \"127.0.0.0\", \"255.255.255.0\")) {";
        $f[]="\t\treturn \"DIRECT\"; }";
        return @implode("\n", $f);
    }

    pack_debug("Rule:[$ID] $CountObjects Object(s)",__FUNCTION__,__LINE__);

    foreach($results as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $not=false;
        $matches=false;
        $GroupName=$ligne["GroupName"];
        $negation=$ligne["negation"];
        if($negation==1){$not=true;}

        pack_debug("Rule:[$ID] Whitelisted group {$GroupName}[$gpid] Type:{$ligne["GroupType"]} Negation:$negation",__FUNCTION__,__LINE__);

        if($ligne["GroupType"]=="all"){
            $f[]="\tif (isInNet(hostIP, \"10.0.0.0\", \"255.0.0.0\") || isInNet(hostIP, \"172.16.0.0\",  \"255.240.0.0\") || isInNet(hostIP, \"192.168.0.0\", \"255.255.0.0\") || isInNet(hostIP, \"127.0.0.0\", \"255.255.255.0\")) {";
            $f[]="\t\treturn \"DIRECT\"; }";
        }
        if($ligne["GroupType"]=="port"){ $f[]=build_whitelist_port($gpid,$negation); continue;}
        if($ligne["GroupType"]=="dstdomain"){ $f[]=build_whitelist_dstdomain($gpid,$negation); continue;}
        if($ligne["GroupType"]=="src"){ $f[]=build_whitelist_src($gpid,$negation); continue;}
        if($ligne["GroupType"]=="dst"){ $f[]=build_whitelist_dst($gpid,$negation); continue;}
        if($ligne["GroupType"]=="srcdomain"){ $f[]=build_whitelist_srcdomain($gpid,$negation); continue;}
        if($ligne["GroupType"]=="time"){ $f[]=build_whitelist_time($gpid,$negation); continue;}
        writelogs("Not supported Group {$ligne["GroupType"]} - $GroupName",__FUNCTION__,__FILE__,__LINE__);
    }

    return @implode("\n", $f);
}
function build_whitelist_port($gpid,$negation,$return_direct=true):string{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    $exclam=null;
    if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
    if(count($results)==0){return false;}
    if($negation==1){$exclam="!";}
    $f=array();

    if($return_direct) {
        $return = "\"DIRECT\";";
    }else{
        $return="true;";
    }

    foreach($results as $index=>$ligne) {
        $pattern=$ligne["pattern"];
        if(!is_numeric($pattern)){return "";}
        $f[]="\tif( DestPort{$exclam}==\":{$ligne["pattern"]}\"){  return $return }";
    }
    return @implode("\n", $f);
}

function build_whitelist_dstdomain($gpid,$negation,$return_direct=true){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $fam=new familysite();
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    $exclam=null;
    if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
    if(count($results)==0){return false;}
    if($negation==1){$exclam="!";}

    if($return_direct) {
        $return = "\"DIRECT\";";
    }else{
        $return="true;";
    }
    $f=array();
    foreach($results as $index=>$ligne) {
        $pattern=trim(strtolower($ligne["pattern"]));
        $Family=$fam->GetFamilySites($pattern);
        pack_debug("Group::[$gpid] Item: \"$pattern\" -> $Family",__FUNCTION__,__LINE__);


        if(strpos(" $pattern", "*")>0){
            if(preg_match("#^\^(.+)#", $ligne["pattern"],$re)){$pattern=$re[1];}
            $f[]="\tif( shExpMatch(host ,\"$pattern\") ){ return $return }";
            continue;
        }

        if(preg_match("#^\^(.+)#", $ligne["pattern"],$re)){
            $f[]="\tif( {$exclam}dnsDomainIs(host, \"{$re[1]}\") ){  return $return }";
            continue;
        }
        if($Family==$ligne["pattern"]){
            if(!preg_match("#^\.#", $ligne["pattern"])){
                $f[]="\tif( {$exclam}dnsDomainIs(host, \".{$ligne["pattern"]}\") ){  return $return }";
                continue;
            }
        }

        $f[]="\tif( {$exclam}dnsDomainIs(host, \"{$ligne["pattern"]}\") ){  return $return }";
    }
    return @implode("\n", $f);
}

function build_whitelist_dst($gpid,$negation,$return_direct=true){
    $ip=new IP();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    $exclam=null;
    if(!$q->ok){
        pack_debug("Group:[$gpid] FATAL !! $q->mysql_error",__FUNCTION__,__LINE__);
        return false;
    }

    if($return_direct) {
        $return = "\"DIRECT\";";
    }else{
        $return="true;";
    }

    $CountObjects=count($results);
    if($CountObjects==0){
        pack_debug("Group::[$gpid] No object defined",__FUNCTION__,__LINE__);
        return false;
    }
    if($negation==1){$exclam="!";}
    $f=array();
    pack_debug("Group::[$gpid] $CountObjects object(s) defined",__FUNCTION__,__LINE__);

    foreach($results as $index=>$ligne) {
        $pattern=trim(strtolower($ligne["pattern"]));
        pack_debug("Group::[$gpid] Item: \"$pattern\"",__FUNCTION__,__LINE__);

        if($pattern==null){continue;}

        if(preg_match("#^([0-9\.]+)-([0-9\.]+)$#", $pattern,$re)){
            $pattern=GetRange($pattern);
            pack_debug("Group::[$gpid] Item: \"{$ligne["pattern"]}\" -> $pattern",__FUNCTION__,__LINE__);
        }

        if(preg_match("#^([0-9\.]+)\/[0-9]+$#", $pattern,$re)){
            $ipaddr=$re[1];
            $netmask=cdirToNetmask($pattern);
            if(!preg_match("#^[0-9\.]+$#", $netmask)){
                pack_debug("ERROR CAN'T PARSE $pattern to netmask",__FILE__,__LINE__);
                continue;
            }
            if($netmask==null){$netmask="255.255.255.0";}
            $f[]="\tif( {$exclam}isInNet(hostIP, \"$ipaddr\", \"$netmask\") ){ return $return}";
            continue;
        }

        if ($ip->isIPAddress($pattern)){
            $f[]="\tif ( isInNet(hostIP, \"$pattern\",\"255.255.255.0\") ) { return $return}";
            continue;
        }




        writelogs("Not supported pattern $pattern",__FUNCTION__,__FILE__,__LINE__);
    }
    return @implode("\n", $f);
}

function build_whitelist_src($gpid,$negation,$return_direct=true):string{
    $ip=new IP();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    $exclam=null;
    if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
    if(count($results)==0){return false;}
    if($negation==1){$exclam="!";}
    $f=array();

    if($return_direct) {
        $return = "\"DIRECT\";";
    }else{
        $return="true;";
    }

    foreach($results as $index=>$ligne) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if( ($pattern==null) OR ($pattern=="0.0.0.0") OR ($pattern=="0.0.0.0/0")){
            continue;
        }


        if(preg_match("#^([0-9\.]+)-([0-9\.]+)$#", $pattern,$re)){
            $pattern=GetRange($pattern);
            pack_debug("Group::[$gpid] Item: \"{$ligne["pattern"]}\" -> $pattern",__FUNCTION__,__LINE__);
        }


        if(preg_match("#^([0-9\.]+)\/[0-9]+$#", $pattern,$re)){
            $ipaddr=$re[1];
            $netmask=cdirToNetmask($pattern);

            if($GLOBALS["VERBOSE"]){$f[]="// --- $pattern -> $ipaddr netmask:$netmask [".__LINE__."]";}
            if(!preg_match("#^[0-9\.]+$#", $netmask)){
                pack_debug("ERROR CAN'T PARSE $pattern to netmask",__FILE__,__LINE__);
                continue;
            }
            if($netmask==null){$netmask="255.255.255.0";}
            $f[]="\tif( {$exclam}isInNet(myip, \"$ipaddr\", \"$netmask\") ){ return $return}";
            continue;
        }


        if ($ip->isIPAddress($pattern)){
            if($GLOBALS["VERBOSE"]){$f[]="// --- $pattern -> isIPAddress(TRUE) [".__LINE__."]";}
            $f[]="\tif( {$exclam}isInNet(myip, \"$pattern\", \"255.255.255.255\") ){ return $return}";
            continue;
        }




        pack_debug("Not supported pattern $pattern",__FILE__,__LINE__);
    }
    return @implode("\n", $f);
}
//dst_function
function rgexdst_function($gpid,$FunctionName):string{
    $IP=new IP();
    $f[]="\tfunction $FunctionName(host,hostIP,url){";
    $f[]="\t\tif( hostIP.length == 0 ){ hostIP=host;}";
    $f[]="//\t\tHostIP is the resolved address ( if the option is enabled )\n";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    foreach($results as $index=>$ligne) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if($pattern==null){continue;}
        if(preg_match("#^\^(.+)#",$pattern,$re)){
            $pattern=$re[1];
        }
        if(strpos($pattern,"*")>0){
            $f[]="\t\tif( shExpMatch( host ,\"$pattern\") ) { return true; }";
            $f[]="\t\tif( host != hostIP ){";
            $f[]="\t\t\tif( shExpMatch( hostIP ,\"$pattern\") ) { return true; }";
            $f[]="\t\t}";
            continue;
        }
        if(strpos($pattern,"\.")>0){
            $f[]="\t\tif( shExpMatch( host ,\"$pattern\") ) { return true; }";
            $f[]="\t\tif( host != hostIP ){";
            $f[]="\t\t\tif( shExpMatch( hostIP ,\"$pattern\") ) { return true; }";
            $f[]="\t\t}";
            continue;
        }

        if($IP->isValid($pattern)) {
            $f[] = "\t\tif( isInNet(host, \"$pattern\", \"255.255.255.255\") ) {return true;}";
            $f[]="\t\tif( host != hostIP ){";
            $f[] = "\t\t\tif( isInNet(hostIP, \"$pattern\", \"255.255.255.255\") ) {return true;}";
            $f[]="\t\t}";
            continue;
        }
        if($IP->IsACDIR($pattern)) {
            $netmask=$IP->cidr2NetmaskAddr($pattern);
            $f[] = "\t\tif( isInNet(host, \"$pattern\", \"$netmask\") ) {return true;}";
            $f[]="\t\tif( host != hostIP ){";
            $f[] = "\t\t\tif( isInNet(hostIP, \"$pattern\", \"$netmask\") ) {return true;}";
            $f[]="\t\t}";
            continue;
        }

        if(preg_match("#^http#", $pattern)){
            $rgex=array();
            $arrayURI=parse_url($pattern);
            if(!isset($arrayURI["host"])){continue;}
            $rgex[]="*";
            $rgex[]=$arrayURI["host"];
            if(isset($arrayURI["path"])) {
                $rgex[] = "*";
                $rgex[] = $arrayURI["path"];
            }
            if(isset($arrayURI["query"])) {
                $rgex[] = "*";
                $rgex[] = $arrayURI["query"];
            }
            $pattern=@implode("",$rgex);
            $f[]="\t\tif( shExpMatch( url ,\"$pattern\") ) { return true; }";
            continue;
        }

        $f[] = "//\t\tCannot understand $pattern";



    }
    $f[]="\t\treturn false;";
    $f[]="\t}";
    return @implode("\n",$f);
}
function dstdomain_function($gpid,$FunctionName):string{
    $f[]="\tfunction $FunctionName(host){";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    $IP=new IP();
    foreach($results as $index=>$ligne) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if($pattern==null){continue;}
        if(preg_match("#^\^(.+)#",$pattern,$re)){
            $pattern=$re[1];
        }
        if(strpos($pattern,"*")>0){
            $f[]="\t\tif( shExpMatch( host ,\"$pattern\") ) { return true; }";
            continue;
        }
        if(strpos($pattern,"\.")>0){
            $f[]="\t\tif( shExpMatch( host ,\"$pattern\") ) { return true; }";
            continue;
        }

        if($IP->isValid($pattern)) {
            $f[] = "\t\tif( isInNet(host, \"$pattern\", \"255.255.255.255\") ) {return true;}";
            continue;
        }
        if($IP->IsACDIR($pattern)) {
            $netmask=$IP->cidr2NetmaskAddr($pattern);
            $f[] = "\t\tif( isInNet(host, \"$pattern\", \"$netmask\") ) {return true;}";
            continue;
        }



        $f[]="\t\tif( dnsDomainIs(host, \"$pattern\") ) { return true; }";
    }
    $f[]="\t\treturn false;";
    $f[]="\t}";
    return @implode("\n",$f);
}

function rgexsrc_function($gpid,$FunctionName):string{
    $f[]="\tfunction $FunctionName(){";
    $f[]="\t\tvar CurrentIP=myIpAddress();";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    foreach($results as $index=>$ligne) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if($pattern==null){continue;}
        $f[]="\t\tif( shExpMatch( CurrentIP ,\"$pattern\") ) { return true; }";
    }
    $f[]="\t\treturn false;";
    $f[]="\t}";
    return @implode("\n",$f);
}

function build_rgexsrc($gpid,$negation,$return_direct=true):string{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    $exclam=null;
    if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
    if(count($results)==0){return false;}
    if($negation==1){$exclam="!";}
    $f=array();
    if($return_direct) {
        $return = "\"DIRECT\";";
    }else{
        $return="true;";
    }
    $f[]="var CurrentIP=myIpAddress();";
    $f[]="";
    foreach($results as $index=>$ligne) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if($pattern==null){continue;}
        $f[]="\tif( {$exclam}shExpMatch( CurrentIP ,\"$pattern\") ) { return $return}";
    }

    return @implode("\n", $f);
}
function build_rgexdst($gpid,$negation,$return_direct=true):string{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    $exclam=null;
    if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
    if(count($results)==0){return false;}
    if($negation==1){$exclam="!";}
    $f=array();
    if($return_direct) {
        $return = "\"DIRECT\";";
    }else{
        $return="true;";
    }

    $f[]="";

    $IP=new IP();
     $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    foreach($results as $index=>$ligne) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if($pattern==null){continue;}
        if($pattern==null){continue;}
        if(preg_match("#^\^(.+)#",$pattern,$re)){
            $pattern=$re[1];
        }
        if(strpos($pattern,"*")>0){
            $f[]="\t\tif( $exclam}shExpMatch( host ,\"$pattern\") ) { return $return }";
            continue;
        }

        if($IP->isValid($pattern)) {
            $f[] = "\t\tif( $exclam}isInNet(host, \"$pattern\", 0) ) {return $return}";
            continue;
        }
        if($IP->IsACDIR($pattern)) {
            $netmask=$IP->cidr2NetmaskAddr($pattern);
            $f[] = "\t\tif( $exclam}isInNet(host, \"$pattern\", \"$netmask\") ) {return $return}";
        }

        if(preg_match("#^http#", $pattern)){
            $rgex=array();
            $arrayURI=parse_url($pattern);
            if(!isset($arrayURI["host"])){continue;}
            $rgex[]="*";
            $rgex[]=$arrayURI["host"];
            if(isset($arrayURI["path"])) {
                $rgex[] = "*";
                $rgex[] = $arrayURI["path"];
            }
            if(isset($arrayURI["query"])) {
                $rgex[] = "*";
                $rgex[] = $arrayURI["query"];
            }
                $pattern=@implode("",$rgex);
                $f[]="\t\tif( $exclam}shExpMatch( url ,\"$pattern\") ) { return $return }";
            }

        }
    return @implode("\n", $f);

}

function build_whitelist_srcdomain($gpid,$negation,$return_direct=true){
    $ip=new IP();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    $exclam=null;
    if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return false;}
    if(count($results)==0){return false;}
    if($negation==1){$exclam="!";}
    $f=array();

    if($return_direct) {
        $return = "\"DIRECT\";";
    }else{
        $return="true;";
    }

    foreach($results as $index=>$ligne) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if($pattern==null){continue;}

        if ($ip->isIPAddress($pattern)){
            $f[]="\tif( hostIP $exclam== \"$pattern\") { return $return}";
            continue;
        }

        if(substr($pattern, 0,1)=='.'){
            if(strpos($pattern, "*")>0){
                $f[]="\tif( {$exclam}shExpMatch(host ,\"$pattern\") ) { return $return}";
                continue;
            }

            $f[]="\tif( {$exclam}dnsDomainIs(host ,\"$pattern\") ){ return $return}";
            continue;

        }

        $f[]="\tif( host {$exclam}== \"$pattern\") { return $return}";


    }
    return @implode("\n", $f);

}

function build_whitelist_time($gpid,$negation,$return_direct=true):string{
    $ip=new IP();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT pattern,other FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
    $results = $q->QUERY_SQL($sql);
    $exclam=null;
    if(!$q->ok){writelogs("$gpid $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);return "";}
    if(count($results)==0){return "";}
    if($negation==1){$exclam="!";}
    foreach($results as $index=>$ligne) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if($pattern==null){continue;}
        $TimeSpace=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["other"]);
        if(!is_array($TimeSpace)){
            writelogs("Not supported pattern !is_array",__FUNCTION__,__FILE__,__LINE__);
            continue;
        }
        $fromtime=$TimeSpace["H1"];
        $tottime=$TimeSpace["H2"];

        if($fromtime=="00:00" && $tottime=="00:00"){
            writelogs("From: $fromtime to $tottime not supported...",__FUNCTION__,__FILE__,__LINE__);
            continue;
        }


        $timerange1=strtotime(date("Y-m-d $fromtime:00"));
        $timerange2=strtotime(date("Y-m-d $tottime:00"));




        $days=array("0"=>"MON","1"=>"TUE","2"=>"WED","3"=>"THU","4"=>"FRI","5"=>"SAT","6"=>"SUN");
        foreach ($TimeSpace as $key=>$ligne){
            if(preg_match("#^day_([0-9]+)#", $key,$re)){
                $dayT=$re[1];
                if($ligne<>1){continue;}
                $f[]="\tif( {$exclam}weekdayRange(\"$days[$dayT]\") ){";
                $f[]="\t\t{$exclam}timeRange(".date("H",$timerange1).",". date("i",$timerange1).", 0,".date("H",$timerange2).",".date("i",$timerange2).", 0) ){";
                if($return_direct) {
                    $f[] = "\t\t\treturn \"DIRECT\";";
                }else{
                    $f[]="\t\t\treturn true;";
                }
                $f[]="\t\t}";
                $f[]="\t}";

            }
        }

    }
    return @implode("\n", $f);

}

function GetRange($net):string{

    if(preg_match("#(.+?)-(.+)#", $net,$re)){
        $ip=new IP();
        return strval($ip->ip2cidr($re[1],$re[2]));
    }
    return "";
}

function cdirToNetmask($net):string{
    $results2=array();

    if(preg_match("#(.+?)\/(.+)#", $net,$re)){
        $ip=new ipv4($re[1],$re[2]);
        $netmask=$ip->netmask();
        $ipaddr=$ip->address();

        if(preg_match("#[0-9\.]+#", $netmask)){
            return $netmask;
        }

        pack_debug("$net -> $ipaddr - $netmask ",__FILE__,__LINE__);
    }

    exec("/usr/share/artica-postfix/bin/ipcalc $net 2>&1",$results2);
    pack_debug("/usr/share/artica-postfix/bin/ipcalc $net 2>&1",__FILE__,__LINE__);
    foreach ($results2 as $index=>$line){
        if(preg_match("#Netmask:\s+([0-9\.]+)#", $line,$re)){
            return $re[1];
        }
    }
    return "";
}

function build_proxies($ID,$FinishbyDirect=0,$LBL=0){
    $sql="SELECT * FROM `wpad_destination` WHERE aclid=$ID AND enabled=1 ORDER BY zorder";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    patch_tables();
    $g=array();
    $results = $q->QUERY_SQL($sql);
    if(!$q->ok){return "\n\treturn \"DIRECT\";";}
    if(count($results)==0){return "\n\treturn \"DIRECT\";";}
    if(intval($LBL)==1 && count($results)>1){
        $count=count($results);
        $Direct="";
        if($FinishbyDirect==1){$Direct="DIRECT";}

        $g[]="\tswitch (mySeg % {$count}) {";
        $case = 0;
        foreach ($results as $index1 => $ligne) {
            $PREFIX = "PROXY";
            if ($ligne["secure"] == 1) {
                $PREFIX = "HTTPS";
            }
            $slaves_sql="SELECT * FROM `wpad_destination` WHERE aclid=$ID AND proxyserver!='{$ligne["proxyserver"]}' AND enabled=1 ORDER BY zorder";
            $slaves_sql_results = $q->QUERY_SQL($slaves_sql);
            $slaves= "";
            foreach ($slaves_sql_results as $index => $lignes) {
                $slaves .= "$PREFIX {$lignes["proxyserver"]}:{$lignes["proxyport"]};";
            }
            $g[] = "\n\t\tcase $case: return \"$PREFIX {$ligne["proxyserver"]}:{$ligne["proxyport"]};$slaves $Direct\";";
            $case = $case+1;
        }
        $g[]="\n\t}";
        if(count($g)==0){return "\n\treturn \"DIRECT\";";}

        return @implode(" ", $g);

    }
    else {
        foreach ($results as $index => $ligne) {
            $PREFIX = "PROXY";
            if ($ligne["secure"] == 1) {
                $PREFIX = "HTTPS";
            }
            $g[] = "$PREFIX {$ligne["proxyserver"]}:{$ligne["proxyport"]};";

        }
        if(count($g)==0){return "\n\treturn \"DIRECT\";";}
        if($FinishbyDirect==1){$g[]="DIRECT";}

        return "\n\treturn \"".@implode(" ", $g)."\";";
    }

}

function packsyslog($text,$function=null,$line=0){
    $servername=$_SERVER["SERVER_NAME"];

    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            if($function==null){$function=$trace[1]["function"];}
            if($line==0){$line=$trace[1]["line"];}
        }

    }



    $LineToSyslog="[$servername] {$GLOBALS["IPADDR"]}: $text function $function line $line";
    ToSyslog($LineToSyslog);


}

function pack_debug($text,$function,$line){

    if($GLOBALS["VERBOSE"]){echo "<code style='font-size:14px'>$function:[$line] $text<br>\n";}
    if($GLOBALS["PROXY_PAC_DEBUG"]==0){return;}

    $servername=$_SERVER["SERVER_NAME"];
    $LineToSyslog="[$servername] {$GLOBALS["IPADDR"]}: $text function $function line $line";
    ToSyslog($LineToSyslog);

    $logFile="/var/log/apache2/proxy.pack.debug";

    $from=$_SERVER["REMOTE_ADDR"];
    $lineToSave=date('H:i:s')." [$servername] {$GLOBALS["IPADDR"]}: $text function $function line $line";

    if (is_file($logFile)) { $size=@filesize($logFile); if($size>900000){@unlink($logFile);} }

    $f = @fopen($logFile, 'a');
    @fwrite($f, "$lineToSave\n");
    @fclose($f);
}
function pack_error($text,$function,$line){

    $logFile="/var/log/apache2/proxy.pack.error";
    $servername=$_SERVER["SERVER_NAME"];
    $from=$_SERVER["REMOTE_ADDR"];
    $lineToSave=date('H:i:s')." [$servername] {$GLOBALS["IPADDR"]}: $text function $function line $line";
    $LineToSyslog="[$servername] {$GLOBALS["IPADDR"]}: $text function $function line $line";
    if (is_file($logFile)) { $size=@filesize($logFile); if($size>900000){@unlink($logFile);} }

    $f = @fopen($logFile, 'a');
    if(!$f){ ToSyslog($LineToSyslog); return; }
    @fwrite($f, "$lineToSave\n");
    @fclose($f);
}

function destination_final_from_rule($ruleid):string{
    if(isset($GLOBALS["destination_final_from_rule"][$ruleid])){return $GLOBALS["destination_final_from_rule"][$ruleid];}
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT * FROM wpad_destination WHERE aclid=$ruleid AND enabled=1 ORDER BY zorder";

    $results=$q->QUERY_SQL($sql);
    $pacproxs=array();
    foreach($results as $index=>$ligne) {
        $proxyserver=$ligne["proxyserver"];
        $proxyport=$ligne["proxyport"];
        $PREFIX="PROXY";
        if(intval($ligne["secure"])==1){$PREFIX="HTTPS";}
        $pacproxs[]="$PREFIX $proxyserver:$proxyport";

    }
    if(count($pacproxs)==0){
        $GLOBALS["destination_final_from_rule"][$ruleid]="DIRECT";
        return "DIRECT";
    }
    $GLOBALS["destination_final_from_rule"][$ruleid]=@implode("; ", $pacproxs);
    return @implode("; ", $pacproxs);
}

function forced_proxies($ID,$FinishbyDirect,$LBL):array{
    $tpl=new template_admin();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
//  $sligne=$q->mysqli_fetch_array("SELECT pacpxy FROM webfilters_sqgroups WHERE ID=$gpid");
    $sql="SELECT wpad_sources_link.gpid,wpad_sources_link.negation,wpad_sources_link.zmd5 as mkey,
	wpad_sources_link.zorder,
	webfilters_sqgroups.*
	FROM wpad_sources_link,webfilters_sqgroups
	WHERE wpad_sources_link.gpid=webfilters_sqgroups.ID
	AND wpad_sources_link.aclid=$ID
	AND webfilters_sqgroups.enabled=1
	ORDER BY wpad_sources_link.zorder";
    $results = $q->QUERY_SQL($sql);

    if(count($results)==0){
        return array("//\tNo group defined for this rule","");
    }
    $Functions=array();
    $f=array();
    $default_proxies=destination_final_from_rule($ID);
    $case = 0;
    $Direct="";
    if($FinishbyDirect==1){$Direct="DIRECT";}
    foreach($results as $index=>$ligne) {
        $gpid=$ligne["gpid"];
        $GroupType=$ligne["GroupType"];
        if($GroupType=="all"){
            // type all is handled by the Web engine.
            continue;
        }
        $not=null;
        $pacproxs=array();
        $GroupName=$tpl->utf8_encode($ligne["GroupName"]);
        $negation=$ligne["negation"];
        if($negation==1){$not="!";}
        $pacpxy=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["pacpxy"]);
        $pacproxsText=array();
        $destinations_final=$default_proxies;
        $force_pp=$default_proxies;
        $count=count($pacpxy);
        $confirmLBL=false;
        if(count($pacpxy)>0){
            foreach ($pacpxy as $index=>$pacline){
                $proxyserver=trim($pacline["hostname"]);
                if($proxyserver==null){continue;}
                if($proxyserver=="0.0.0.0"){
                    $pacproxs=array();
                    $pacproxsText=array();
                    $pacproxsText[]="Direct mode";
                    $pacproxs[]="DIRECT";
                    break;
                }
                if(intval($LBL)==1 && $count>1){
                    $proxyport=$pacline["port"];
                    $pacproxsText[]="$proxyserver:$proxyport";
                    $PREFIX="PROXY";
                    if($pacline["secure"]==1){$PREFIX="HTTPS";}
                    $slaves= "";

                    foreach ($pacpxy as $slaveindex=>$slave){
                        $proxyslaveserver=trim($slave["hostname"]);
                        if ($proxyslaveserver==$proxyserver){
                            continue;
                        }
                        $proxyslaveport=$slave["port"];
                        $slaves .= " $PREFIX $proxyslaveserver:$proxyslaveport;";
                    }

                    //$pacproxs[]="$PREFIX $proxyserver:$proxyport";
                    $pacproxs[] = "\n\t\tcase $case: return \"$PREFIX $proxyserver:$proxyport;$slaves $Direct\";";
                    $case = $case+1;
                    $confirmLBL=true;

                } else {
                    $proxyport=$pacline["port"];
                    $pacproxsText[]="$proxyserver:$proxyport";
                    $PREFIX="PROXY";
                    if($pacline["secure"]==1){$PREFIX="HTTPS";}
                    $pacproxs[]="$PREFIX $proxyserver:$proxyport";
                }

            }
            if(count($pacproxs)==0){continue;}
            if ($confirmLBL){
                $pr=@implode(" ", $pacproxs);
                $destinations_final="\tswitch (mySeg % {$count}) { $pr \n\t}";

            } else {
                $destinations_final=@implode("; ", $pacproxs);
            }

            $force_pp=" [".@implode("or ",$pacproxsText)."]";
        }


        $f[]="//\t$GroupName type $not$GroupType going to $force_pp [".__LINE__."]";
        $xdec=explode("\n",$ligne["description"]);
        foreach ($xdec as $descline){$f[]="//\t$descline";}

        if($GroupType=="rgexsrc"){
            // rgexsrc always handled by the script.
           $FunctionName="Group$gpid";
           $Functions[]=rgexsrc_function($gpid,$FunctionName);
           $f[]="\tif( $not$FunctionName() ){";
            if($confirmLBL){
                $f[] = $destinations_final;
            } else {
                $f[] = "\t\treturn \"$destinations_final\";";
            }
           $f[]="\t}";
           $f[]="";
           $f[]="";
        }

        if($GroupType=="dstdomain") {
            // dstdomain always handled by the script.
            $FunctionName = "Group$gpid";
            $Functions[] = dstdomain_function($gpid, $FunctionName);
            $f[] = "\tif( $not$FunctionName(host) ){";
            if($confirmLBL){
                $f[] = $destinations_final;
            } else {
                $f[] = "\t\treturn \"$destinations_final\";";
            }

            $f[] = "\t}";
            $f[] = "";
            $f[] = "";
        }
        if($GroupType=="rgexdst") {
            // dstdomain always handled by the script.
            $FunctionName = "Group$gpid";
            $Functions[] = rgexdst_function($gpid, $FunctionName);
            $f[] = "\tif( $not$FunctionName(host,hostIP,url) ){";
            if($confirmLBL){
                $f[] = $destinations_final;
            } else {
                $f[] = "\t\treturn \"$destinations_final\";";
            }
            $f[] = "\t}";
            $f[] = "";
            $f[] = "";
        }


    }

    return array( @implode("\n",$f),@implode("\n",$Functions));
}

function ToSyslog($text){

    $LOG_SEV=LOG_INFO;
    if(function_exists("openlog")){openlog("proxy.pac", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
    if(function_exists("closelog")){closelog();}
}
///* Don't proxy local hostnames */ if (isPlainHostName(host)) { return 'DIRECT'; }
//  if (dnsDomainLevels(host) > 0) { // if the number of dots in host > 0
//  if (isResolvable(host))