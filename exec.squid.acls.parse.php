<?php

include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.squid.acls.inc");
include_once("/usr/share/artica-postfix/ressources/class.mysql.squid.builder.php");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if(isset($argv[1])){

    if($argv[1]=="--import"){
        uploaded_file($argv[2]);
        die();
    }
    if($argv[1]=="--export-rule"){
        export_rule($argv[2]);
        exit;
    }
    if($argv[1]=="--import-rule"){
        import_rule($argv[2]);
        exit;
    }


    if($argv[1]=="--db-tables"){
        dbtables();
        exit;
    }

}

startx();

function build_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"acls.parse");

}

function export_rule($ID){
    $unix=new unix();
    echo "Exporting rule $ID";
    $dbsrc="/home/artica/SQLITE/acls.db";
    $dbdest=$unix->FILE_TEMP().".db";
    $dbdest2=PROGRESS_DIR."/rule.$ID.acl";
    @copy($dbsrc,$dbdest);
    $tbldl[]="qos_containers";
    $tbldl[]="computers_time";
    $tbldl[]="quota_objects";
    $tbldl[]="sessions_objects";
    $tbldl[]="UsersAgentsDB";
    $tbldl[]="firewallfilter_sqacllinks";
    $tbldl[]="qos_sqacllinks";
    $tbldl[]="dnsdist_rules";
    $tbldl[]="dnsdist_sqacllinks";
    $tbldl[]="sslrules_sqacllinks";
    $tbldl[]="sslproxy_cert_error_sqacllinks";
    $tbldl[]="http_reply_access";
    $tbldl[]="http_reply_access_links";
    $tbldl[]="squid_url_rewrite_acls";
    $tbldl[]="squid_icap_acls_link";
    $tbldl[]="squid_icap_acls";
    $tbldl[]="squid_url_rewrite_link";
    $tbldl[]="global_whitelist";
    $tbldl[]="wpad_rules";
    $tbldl[]="wpad_sources_link";
    $tbldl[]="wpad_white_link";
    $tbldl[]="wpad_destination";
    $tbldl[]="wpad_destination_rules";
    $tbldl[]="wpad_events";
    $tbldl[]="webfilters_blkwhlts";
    $tbldl[]="logs_sqacllinks";
    $tbldl[]="outgoingaddr_sqacllinks";
    $tbldl[]="privoxy_sqacllinks";
    $tbldl[]="squid_privoxy_acls";
    $tbldl[]="parents_sqacllinks";
    $tbldl[]="acls_whitelist";
    $tbldl[]="deny_cache_domains";
    $tbldl[]="ext_time_quota_acl";
    $tbldl[]="ext_time_quota_acl_rules";
    $tbldl[]="ext_time_quota_acl_link";
    $tbldl[]="squid_pools";
    $tbldl[]="squid_pools_acls";
    $tbldl[]="limit_bdwww";
    $tbldl[]="http_headers";
    $tbldl[]="wpad_black_link";
    $tbldl[]="parents_white_sqacllinks";
    $tbldl[]="pac_except";
    $tbldl[]="squid_auth_schemes_acls";
    $tbldl[]="squid_auth_schemes_link";
    $tbldl[]="acls_bugs";
    $tbldl[]="squid_dns_rules";

    build_progress(20,"{cleaning}");
    $q=new lib_sqlite($dbdest);
    foreach ($tbldl as $index=>$tablename){
        if($q->TABLE_EXISTS($tablename)) {
            echo "Removing table $tablename\n";
            $q->QUERY_SQL("DROP TABLE $tablename");
        }

    }
    build_progress(25,"{cleaning} webfilters_sqacls");
    $q->QUERY_SQL("DELETE FROM webfilters_sqacls WHERE ID !=$ID");
    build_progress(30,"{cleaning} webfilters_sqaclaccess");
    $q->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE aclid !='$ID'");
    build_progress(35,"{cleaning} webfilters_sqacllinks");
    $q->QUERY_SQL("DELETE FROM webfilters_sqacllinks WHERE aclid !='$ID'");
    if(is_file($dbdest2)){@unlink($dbdest2);}
    @copy($dbdest,$dbdest2);
    echo "$dbdest2 done\n";
    build_progress(100,"{done}");
}
function import_rule($rulepath){

    if(!is_file($rulepath)){
       $rulepath= dirname(__FILE__)."/ressources/conf/upload/$rulepath";
    }
    if(!is_file($rulepath)){
        build_progress(110,"$rulepath no such file");
        return false;
    }
    $q=new lib_sqlite($rulepath);
    $qDest = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT * FROM webfilters_sqacls");
    foreach ($results as $index=>$ligne){
        $ACLID_SRC=$ligne["ID"];
        $PortDirection=$ligne["PortDirection"];
        $aclname=$ligne["aclname"];
        $aclport=$ligne["aclport"];
        $acltpl=$ligne["acltpl"];
        $enabled=$ligne["enabled"];
        $aclgroup=$ligne["aclgroup"];
        $aclgpid=$ligne["aclgpid"];
        $zTemplate=$ligne["zTemplate"];
        $xORDER=$ligne["xORDER"];
        $aclname="$aclname (imported)";
        echo "Importing $aclname\n";
        $zExplain=md5(time().$aclname);
        build_progress(5,"webfilters_sqacls...");
        $sql="INSERT INTO webfilters_sqacls (aclname,enabled,acltpl,xORDER,aclport,aclgroup,aclgpid,PortDirection,zExplain,zTemplate) VALUES ('$aclname',$enabled,'$acltpl','$xORDER','$aclport','$aclgroup','$aclgpid','$PortDirection','$zExplain','$zTemplate')";

        $qDest->QUERY_SQL($sql);
        if(!$qDest->ok){
            echo $q->mysql_error."\n";
            @unlink($rulepath);
            build_progress(5,"webfilters_sqacls {failed}...");
            return false;
        }
        $ligne=$qDest->mysqli_fetch_array("SELECT ID FROM webfilters_sqacls WHERE zExplain='$zExplain'");
        $ACLID_DEST=intval($ligne["ID"]);
        if($ACLID_DEST==0){
            echo $q->mysql_error."\n";
            @unlink($rulepath);
            build_progress(5,"webfilters_sqacls ACLID_DEST=0 {failed}...");
            return false;
        }
        $q->QUERY_SQL("UPDATE webfilters_sqacls SET zExplain='' WHERE ID=$ACLID_DEST");
        build_progress(15,"webfilters_sqaclaccess...");
        if(!import_webfilters_sqaclaccess($rulepath,$ACLID_SRC,$ACLID_DEST)){
            build_progress(5,"webfilters_sqaclaccess {failed}...");
            @unlink($rulepath);
            return false;
        }

        build_progress(30,"webfilters_sqacllinks...");
        if(!import_webfilters_sqacllinks($rulepath,$ACLID_SRC,$ACLID_DEST)){
            build_progress(5,"webfilters_sqaclaccess {failed}...");
            @unlink($rulepath);
            return false;
        }
    }
    @unlink($rulepath);
    build_progress(100,"{success}");
    return true;
}
function import_webfilters_sqacllinks($dbfile,$ACLID_SRC,$ACLID_DEST):bool{
    $qsrc=new lib_sqlite($dbfile);
    $qDest = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $prefix="INSERT INTO webfilters_sqacllinks (zmd5,aclid,gpid,zOrder,negation) VALUES ";
    $Done=array();
    $sql="SELECT * FROM webfilters_sqacllinks WHERE aclid=$ACLID_SRC";
    $results=$qsrc->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        $zOrder=$ligne["zOrder"];
        $negation=$ligne["negation"];
        $gpid_src=$ligne["gpid"];
        echo "Importing source group $gpid_src\n";
        $gpid_dst=import_webfilters_sqgroups($dbfile,$gpid_src);
        echo "Source group $gpid_src is now $gpid_dst\n";
        if($gpid_dst==0){return false;}
        $Done[$gpid_src]=$gpid_dst;

        $md5=md5($ACLID_DEST.$gpid_dst);
        $qDest->QUERY_SQL($prefix." ('$md5','$ACLID_DEST','$gpid_dst','$zOrder','$negation')");
        if(!$qDest->ok){
            echo $qDest->mysql_error."\n";
            return false;
        }
        echo "Importing items of group $gpid_src to group $gpid_dst\n";
        if(!import_webfilters_sqitems($dbfile,$gpid_src,$gpid_dst)){
            return false;
        }

    }

    if(!import_webfilters_gpslink($dbfile,$Done)){
        return false;
    }
    return true;

}
function import_webfilters_gpslink($dbfile,$Done){
    $qsrc = new lib_sqlite($dbfile);
    $qDest = new lib_sqlite("/home/artica/SQLITE/acls.db");

    $ALREADY=array();
    foreach ($Done as $gpidsrc=>$gpidst) {


        if (isset($ALREADY[$gpidsrc])) {
            echo "import_webfilters_gpslink: Linked group $gpidsrc (source) is already imported(2)\n";
            continue;
        }
        echo "import_webfilters_gpslink: Importing Group of object $gpidsrc as $gpidst in destination\n";

        $sql = "SELECT * FROM webfilters_gpslink WHERE groupid=$gpidsrc";
        $webfilters_gpslink = $qsrc->QUERY_SQL($sql);
        foreach ($webfilters_gpslink as $index => $ligne) {
            $gpid = $ligne["gpid"];
            $enabled = $ligne["enabled"];
            echo "import_webfilters_gpslink: Group source $gpidsrc have $gpid as slave\n";
            if (isset($ALREADY[$gpid])) {
                echo "import_webfilters_gpslink: Linked group $gpid (source) is already imported(2)\n";
                continue;
            }

            if (isset($Done[$gpid])) {
                $newgpid = $Done[$gpid];
                echo "import_webfilters_gpslink: Group $gpid already imported as $newgpid\n";
                $m5 = md5("$newgpid$gpidst");
                $qDest->QUERY_SQL("INSERT INTO webfilters_gpslink (zmd5,groupid,gpid,enabled) 
            VALUES ('$m5','$gpidst','$newgpid',$enabled)");
                $ALREADY[$gpid] = true;
                continue;
            }
            echo "import_webfilters_gpslink: Replicate source Group $gpid\n";
            $gpid_dst = import_webfilters_sqgroups($dbfile, $gpid);
            if ($gpid_dst == 0) {
                echo "Failed to replicate $gpid\n";
                return false;
            }
            echo "import_webfilters_gpslink: Replicate items of $gpid to new group $gpid_dst\n";
            if (!import_webfilters_sqitems($dbfile, $gpid, $gpid_dst)) {
                echo "import_webfilters_gpslink: Replicate items of $gpid -> $gpid_dst failed\n";
                return false;
            }
            $m5 = md5("$gpid_dst$gpidst");
            $qDest->QUERY_SQL("INSERT INTO webfilters_gpslink (zmd5,groupid,gpid,enabled) 
            VALUES ('$m5','$gpidst','$gpid_dst',$enabled)");


            $ALREADY[$gpid] = true;

        }
        $ALREADY[$gpidsrc] = true;
    }
    return true;


}


function import_webfilters_sqitems($dbfile,$src_gpid,$dst_gpid)
{
    $qsrc = new lib_sqlite($dbfile);
    $qDest = new lib_sqlite("/home/artica/SQLITE/acls.db");

    if (!$qDest->FIELD_EXISTS("webfilters_sqitems", "zdate")) {
        $qDest->QUERY_SQL("ALTER TABLE webfilters_sqitems ADD `zdate` text");
    }
    if (!$qDest->FIELD_EXISTS("webfilters_sqitems", "uid")) {
        $qDest->QUERY_SQL("ALTER TABLE webfilters_sqitems ADD `uid` text");
    }
    if (!$qDest->FIELD_EXISTS("webfilters_sqitems", "description")) {
        $qDest->QUERY_SQL("ALTER TABLE webfilters_sqitems ADD `description` text");
    }

    $results=$qsrc->QUERY_SQL("SELECT * FROM webfilters_sqitems WHERE gpid=$src_gpid");
    foreach ($results as $index=>$ligne){
            $pattern=$ligne["pattern"];
            $zdate=$ligne["zdate"];
            $uid=$ligne["uid"];
            $enabled=$ligne["enabled"];
            $description=$ligne["description"];
            $qDest->QUERY_SQL("INSERT INTO webfilters_sqitems (pattern,gpid,zdate,uid,enabled,description) 
        VALUES('$pattern','$dst_gpid','$zdate','$uid','$enabled','$description')");
        if(!$qDest->ok){
            echo $qDest->mysql_error."\n";
            return false;
        }

    }

    return true;

}

function import_webfilters_sqgroups($dbfile,$gpid):int
{
    $qsrc = new lib_sqlite($dbfile);
    $qDest = new lib_sqlite("/home/artica/SQLITE/acls.db");

    $ligne = $qsrc->mysqli_fetch_array("SELECT * FROM webfilters_sqgroups WHERE ID=$gpid");
    $GroupName = $ligne["GroupName"];
    $GroupType = $ligne["GroupType"];
    $acltpl = $ligne["acltpl"];
    $tplreset = $ligne["tplreset"];
    $enabled = $ligne["enabled"];
    $PortDirection = $ligne["PortDirection"];
    $bulkimport = $ligne["bulkimport"];
    $bulkmd5 = md5(time() . $GroupName);
    $params = $ligne["params"];
    $pacpxy = $ligne["pacpxy"];
    $idtemp = time();
    echo "Importing group $gpid ($GroupName) L.".__LINE__."\n";
    $sql = "INSERT INTO webfilters_sqgroups (GroupName,GroupType,acltpl,tplreset,enabled,PortDirection,bulkimport,bulkmd5,params,pacpxy,idtemp) 
    VALUES ('$GroupName','$GroupType','$acltpl','$tplreset','$enabled','$PortDirection','$bulkimport','$bulkmd5','$params','$pacpxy','$idtemp')";

    $qDest->QUERY_SQL($sql);
    if (!$qDest->ok) {
        echo $qDest->mysql_error . "\n";
        return 0;
    }

    $ligne2=$qDest->mysqli_fetch_array("SELECT ID FROM webfilters_sqgroups WHERE bulkmd5='$bulkmd5'");
    echo "Importing group $gpid ($GroupName) have [{$ligne2["ID"]}] group id on destination L.".__LINE__."\n";
    return intval($ligne2["ID"]);
}






function import_webfilters_sqaclaccess($dbfile,$ACLID_SRC,$ACLID_DEST):bool{
    echo "Rule $ACLID_SRC is replicate as $ACLID_DEST\n";
    $qsrc=new lib_sqlite($dbfile);
    $qDest = new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$qsrc->mysqli_fetch_array("SELECT * FROM webfilters_sqaclaccess WHERE aclid=$ACLID_SRC");
    if(!$qsrc->ok){
        echo "import_webfilters_sqaclaccess: ERROR ".$qsrc->mysql_error." LINE ".__LINE__."\n";
        return false;
    }
    $type=$ligne["httpaccess"];
    $httpaccess_value=$ligne["httpaccess_value"];
    $httpaccess_data=$ligne["httpaccess_data"];
    echo "Rule $ACLID_SRC type=$type $httpaccess_value/$httpaccess_data\n";
    $md5=md5("$ACLID_DEST$type");
    $qDest->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE zmd5='$md5'");
    $sql="INSERT OR IGNORE INTO webfilters_sqaclaccess 
		(zmd5,aclid,httpaccess,httpaccess_value,httpaccess_data) VALUES('$md5','$ACLID_DEST','$type','$httpaccess_value','$httpaccess_data')";
    echo "$sql\n";
    $qDest->QUERY_SQL($sql);
    if(!$qDest->ok){
        echo "import_webfilters_sqaclaccess: $qDest->mysql_error\n$sql\n";
        return false;
    }
    return true;

}


function dbtables(){
    $dbsrc="/home/artica/SQLITE/acls.db";
    $q=new lib_sqlite($dbsrc);
    $array=$q->LIST_TABLES();
    foreach ($array as $index=>$tbl){
        echo "\$tbldl[]=\"$tbl\";\n";
    }
}


function uploaded_file($filencoded):bool{
    $unix=new unix();
    $fsrc=base64_decode($filencoded);
    $fullpath=dirname(__FILE__)."/ressources/conf/upload/$fsrc";
    $dbfile="/home/artica/SQLITE/acls.db";
    if(!is_file($fullpath)){
        echo "$fullpath no such file!\n";
        build_progress(110,"{failed}");
        return false;
    }

    if(!preg_match("#\.(db|acl)$#",$fullpath,$re)){
        @unlink($fullpath);
        echo "$fullpath wrong file!\n";
        build_progress(110,"{failed}");
        return false;
    }
    $extension=$re[1];
    if($extension=="acl"){
        import_rule($fullpath);
        return true;
    }



    if($extension=="db"){
        $tables[]="acls_whitelist";
        $tables[]="squid_outgoingaddr_acls";
        $tables[]="squid_url_rewrite_acls";
        $tables[]="squid_url_rewrite_link";
        $tables[]="ext_time_quota_acl_link";
        $tables[]="global_whitelist";
        $tables[]="http_reply_access";
        $tables[]="webfilters_blkwhlts";
        $tables[]="webfilters_sqaclaccess";
        $tables[]="webfilters_sqacls";
        $tables[]="webfilters_sqaclsports";
        $tables[]="webfilters_sqitems";

        $q=new lib_sqlite($fullpath);
        foreach ($tables as $tablename){
            if(!$q->TABLE_EXISTS($tablename)){
                echo "Table $tablename no such table\n";
                @unlink($fullpath);
                build_progress(110,"{failed}");
                return false;
            }
        }


        if(is_file("$dbfile.bak")){
            @unlink("$dbfile.bak");
        }
        build_progress(50,"{importing}");
        @copy($dbfile,"$dbfile.bak");
        if(!@copy($fullpath,$dbfile)){
            echo "$fullpath Copy failed\n";
            @unlink($fullpath);
            @copy("$dbfile.bak",$dbfile);
            build_progress(110,"{failed}");
            return false;
        }
        @chmod($dbfile,0755);
        @chown($dbfile,"www-data");
        build_progress(100,"{success}");
        return true;
    }



    return true;
}

function startx(){
    $q=new mysql_squid_builder();
    $noobjs=$q->acl_ARRAY_NO_ITEM;
    $noobjs["all"]=true;
    $noobjs["localnet"]=true;
    $noobjs["url_db"]=true;


    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT ID,GroupName,GroupType FROM webfilters_sqgroups WHERE enabled=1");
    $sql="CREATE TABLE IF NOT EXISTS `acls_bugs` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`zdate` INTEGER,
		`groupid` INTEGER,
		`description` TEXT)";

    $q->QUERY_SQL($sql);

    $q->QUERY_SQL("DELETE FROM acls_bugs");
    if(!$q->ok){echo $q->mysql_error."\n";
        build_progress(110,"{checking} {failed}...");
        return false;
    }


    $count=count($results);
    $c=0;

    foreach ($results as $index=>$ligne){
        $c++;
        $GroupName=$ligne["GroupName"];
        $prc=round(($c/$count)*100);
        if($prc>95){$prc=95;}
        build_progress($prc,"Checking $GroupName");
        $ID=$ligne["ID"];

        $GroupType=$ligne["GroupType"];
        if($GLOBALS["VERBOSE"]){echo "[$index]: $GroupName - $GroupType\n";}
        if(isset($noobjs[$GroupType])){continue;}

        echo "Checking $GroupName ($GroupType)\n";
        $acls=new squid_acls();
        $fCountArray=$acls->GetItems($ID);
        remove_group($ID);

        if(count($fCountArray)==0){
            continue;
        }

        if($GroupType=="dstdomain"){
            check_group_dstdomain($ID,$fCountArray);
        }
        if($GroupType=="dst"){
            check_group_dst($ID,$fCountArray);
        }
        if($GroupType=="src"){
            check_group_src($ID,$fCountArray);
        }
    }
    build_progress(98,"{checking}....");

    $ligne=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM acls_bugs");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUID_ACLS_BUGS",intval($ligne["tcount"]));
    build_progress(100,"Checking {success]");
}

function remove_group($ID){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="DELETE FROM `acls_bugs` WHERE `groupid` = $ID";
    $q->QUERY_SQL($sql);
}

function add_error($ID,$description){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    $sql="CREATE TABLE IF NOT EXISTS `acls_bugs` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`zdate` INTEGER,
		`groupid` INTEGER,
		`description` TEXT)";

    $q->QUERY_SQL($sql);
    $t=time();
    $description=$q->sqlite_escape_string2($description);
    echo "Add $description\n";
    $q->QUERY_SQL("INSERT INTO acls_bugs (zdate,groupid,description) VALUES ('$t','$ID','$description')");
    if(!$q->ok){echo $q->mysql_error."\n";}
}
function check_group_dst($ID,$fCountArray):bool{
    $unix=new unix();
    $TEMPDIR=$unix->TEMP_DIR();
    $aclsfile=$TEMPDIR."/acls.txt";
    @file_put_contents($aclsfile,@implode("\n",$fCountArray));
    $object="acl Group$ID dst \"$aclsfile\"";
    $rule="http_access deny Group$ID";
    return build_squid_config($rule,$object);

}
function check_group_src($ID,$fCountArray):bool{
    $unix=new unix();
    $TEMPDIR=$unix->TEMP_DIR();
    $aclsfile=$TEMPDIR."/acls.txt";
    @file_put_contents($aclsfile,@implode("\n",$fCountArray));
    $object="acl Group$ID dst \"$aclsfile\"";
    $rule="http_access deny Group$ID";
    return build_squid_config($rule,$object);
}

function check_group_dstdomain($ID,$fCountArray):bool{
    $unix=new unix();
    $TEMPDIR=$unix->TEMP_DIR();
    $aclsfile=$TEMPDIR."/acls.txt";
    @file_put_contents($aclsfile,@implode("\n",$fCountArray));
    $object="acl Group$ID dstdomain \"$aclsfile\"";
    $rule="http_access deny Group$ID";
    return build_squid_config($rule,$object);

}



function build_squid_config($rule,$object):bool{
    $unix               = new unix();
    $t                  = time();
    $TEMPDIR            = $unix->TEMP_DIR()."/squid";
    $rm                 = $unix->find_program("rm");
    if(!is_dir($TEMPDIR)){@mkdir("$TEMPDIR",0755);}
    @chown($TEMPDIR,"squid");
    $pidfile_name       = "$TEMPDIR/squid-acls-parse-$t.pid";
    $cache_access_log   = "$TEMPDIR/squid-acls-parse-access-$t.log";
    $cache_log          = "$TEMPDIR/squid-acls-parse-cache-$t.log";
    $squid_conf         = "$TEMPDIR/squid-acls-parse-squid.conf";
    $squidbin           = $unix->LOCATE_SQUID_BIN();
    @file_put_contents($pidfile_name,getmygid()."\n");
    @chown($pidfile_name,"squid");

    $f[]="pid_filename $pidfile_name";
    $f[]="http_port 127.0.0.1:31128";
    $f[]="cache_access_log $cache_access_log";
    $f[]="cache_log $cache_log";
    $f[]="cache_effective_user squid";
    $f[]=$object;
    $f[]=$rule;
    @file_put_contents($squid_conf,@implode("\n",$f));
    echo "Checking Object..............: $object\n";
    echo "Checking Configuration.......: $squid_conf\n";
    $cmd="$squidbin -f $squid_conf -N -k check 2>&1";
    echo "Running $cmd\n";
    exec($cmd,$results);
    $suffix_error=null;
    foreach ($results as $line){
        if (preg_match("#Warning: empty ACL#i",$line)){continue;}
        if (preg_match("#BCP 177 violation#i",$line)){continue;}

        if(preg_match("#ERROR: '(.+?)' is a subdomain of '(.+?)'#i",$line,$re)){
            $suffix_error=$re[1]." {already_a_part_of} $re[2]";
            continue;
        }

        if(preg_match("#ERROR: You need to remove\s+'(.+?)'\s+from.*?named 'Group([0-9]+)'#i",$line,$re)){
            $suffix_error=$suffix_error." {please_remove_the_record} $re[1]";
            if(!isset($GLOBALS["ALREDYPARSED"][$re[1]])){add_error($re[2],$suffix_error);}
            $GLOBALS["ALREDYPARSED"][$re[1]]=true;
            $suffix_error=null;
            continue;
        }

        if(preg_match("#WARNING:.*?'([0-9\.\/]+)' is a subnetwork of.*?'(.+?)'#i",$line,$re)){
            $suffix_error=$re[1]." {already_a_part_of} $re[2]";
            continue;
        }

        if(preg_match("#WARNING:.*?remove.*?'([0-9\.\/]+)' from the ACL.*?'Group([0-9]+)'#i",$line,$re)){
            $suffix_error=$suffix_error." {please_remove_the_record} $re[1]";
            if(!isset($GLOBALS["ALREDYPARSED"][$re[1]])){add_error($re[2],$suffix_error);}
            $GLOBALS["ALREDYPARSED"][$re[1]]=true;
            $suffix_error=null;
            continue;
        }

        echo "Nomatches [$line]\n";
    }
    shell_exec("$rm -rf $TEMPDIR/*");
    return true;
}