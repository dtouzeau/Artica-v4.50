<?php
define("CERTDB","/home/artica/SQLITE/certificates.db");
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["TITLENAME"]="Rsync Daemon";
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.groups.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.categories.inc');
include_once(dirname(__FILE__).'/ressources/class.snapshots.blacklists.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
if($argv[1]=="--catz"){search_categories();exit;}
if($argv[1]=="--reverse"){merge_reverse_proxy_data();exit;}
if($argv[1]=="--certs"){merge_certificate_center_data();exit;}



import3x($argv[1]);
function build_progress($text,$pourc){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/import3x.progress", serialize($array));
    @chmod($GLOBALS["PROGRESS_FILE"],0755);
    if($GLOBALS["WAIT"]){usleep(800);}
}

function migrate_3xSnapWebfilters($MAINPATH){
    $categories_tables = array();
    $unix       = new unix();
    $cat        = $unix->find_program("cat");
    $rm         = $unix->find_program("rm");
    $sqlite3    = "/usr/bin/sqlite3";
    $tables[]="webfilter_assoc_groups.gz";
    $tables[]="webfilter_blkcnt.gz";
    $tables[]="webfilter_blkgp.gz";
    $tables[]="webfilter_blklnk.gz";
    $tables[]="webfilter_blks.gz";
    $tables[]="webfilter_catprivs.gz";
    $tables[]="webfilters_schedules.gz";
    $tables[]="webfilter_certs.gz";
    $tables[]="webfilter_group.gz";
    $tables[]="webfilter_rules.gz";
    $tables[]="webfilters_sqitems.gz";
    $tables[]="webfilters_sqgroups.gz";
    $tables[]="personal_categories.gz";
    $DAEMONS_PATH="$MAINPATH/Daemons";
    $MAINPATH="$MAINPATH/squidlogs";

    if(is_dir($DAEMONS_PATH)){
        $artica_settings_blacklists=artica_settings_blacklists();
        if ($handle = opendir($DAEMONS_PATH)) {
            while (false !== ($fileZ = readdir($handle))) {
                if ($fileZ == ".") {continue;}
                if ($fileZ == "..") {continue;}
                if(isset($artica_settings_blacklists[$fileZ])){
                    echo "Skip Parameters: $fileZ\n";
                    continue;
                }
                $data=@file_get_contents("$DAEMONS_PATH/$fileZ");
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO($fileZ,$data);
            }

            shell_exec("$rm -rf $DAEMONS_PATH");
        }

    }else{
        echo "$DAEMONS_PATH no such directory\n";

    }


    if ($handle = opendir($MAINPATH)) {
        while (false !== ($fileZ = readdir($handle))) {
            if ($fileZ == ".") {continue;}
            if ($fileZ == "..") {continue;}
            if(!preg_match("#^category_(.+?)\.gz$#",$fileZ,$re)){continue;}
            $tables[]=$fileZ;
            $categories_tables[]="category_".$re[1];
        }
    }


    $tmpdb = "$MAINPATH/webfilter.db";

    if(is_file($tmpdb)){@unlink($tmpdb);}

    foreach ($tables as $table_file){
        $tablename = str_replace(".gz","",$table_file);
        $source_path="$MAINPATH/$table_file";
        $destination="$MAINPATH/$tablename.sqlite";
        $uncompress_path = "$MAINPATH/$tablename.txt";

        if(is_file($destination)){@unlink($destination);}
        if(is_file($uncompress_path)){@unlink($uncompress_path);}

        echo "[$tablename]: Uncompress $source_path\n";
        $unix->uncompress($source_path,$uncompress_path);
        echo "[$tablename]: Migrate $uncompress_path\n";
        MySQLToSQLite($uncompress_path,$destination);

        if(is_file($source_path)){@unlink($source_path);}

        echo "[$tablename]: Importing table $tablename....\n";
        $tmpdb_results=array();
        exec("$cat $destination | $sqlite3 $tmpdb 2>&1",$tmpdb_results);
        foreach ($tmpdb_results as $line){
            echo "[$tablename]: Exec.result: $line\n";
        }

        if(is_file($destination)){@unlink($destination);}

        $qTests       =   new lib_sqlite($tmpdb);
        if(!$qTests->TABLE_EXISTS($tablename)){
            echo "[$tablename]: Failed to transfert MySQL $destination -> $tmpdb";
        }

    }

    if($qTests->TABLE_EXISTS("webfilters_sqitems")){
        echo "[webfilters_sqitems]: Importing.....\n";
        $prefix = "INSERT INTO webfilters_sqitems (ID,gpid,pattern,zdate,uid,enabled) VALUES ";
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $q->QUERY_SQL("DELETE FROM webfilters_sqitems");
        $results=$qTests->QUERY_SQL("SELECT * FROM webfilters_sqitems");
        $f=array();
        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            $pattern=$ligne["pattern"];
            $gpid=$ligne["gpid"];
            $enabled=$ligne["enabled"];
            $date = date("Y-m-d H:i:s");
            $uid="Manager";
            echo "webfilters_sqitems: Import id: $index\n";
            $f[]="('$ID','$gpid','$pattern','$date','$uid','$enabled')";
        }
        if(count($f)>0){
            $sql=$prefix . @implode(",",$f);
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
        }


    }
    echo "--------------------------------------------------------------------------------\n";
    if($qTests->TABLE_EXISTS("webfilters_sqgroups")) {
        echo "[webfilters_sqgroups]: Importing.....\n";
        $results = $qTests->QUERY_SQL("SELECT * FROM webfilters_sqgroups");
        $prefix="INSERT INTO webfilters_sqgroups (ID,GroupName,GroupType,enabled,`acltpl`,`params`,`PortDirection`,`tplreset`)
	VALUES ";
        $f=array();
        $q->QUERY_SQL("DELETE FROM webfilters_sqgroups");
        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            $GroupName=$ligne["GroupName"];
            $GroupType=$ligne["GroupType"];
            $acltpl=$ligne["acltpl"];
            $tplreset=$ligne["tplreset"];
            $enabled=$ligne["enabled"];
            $params=$ligne["params"];
            $PortDirection=$ligne["PortDirection"];
            $f[]="($ID,'$GroupName','$GroupType','$enabled','$acltpl','$params','$PortDirection','$tplreset')";
        }

        if(count($f)>0){
            $sql=$prefix . @implode(",",$f);
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
        }

    }else{
        echo "[webfilters_sqgroups]: No such table..\n";
    }
    echo "--------------------------------------------------------------------------------\n";

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    if($qTests->TABLE_EXISTS("webfilter_group")) {
        echo "[webfilter_group]: Importing.....\n";
        $results = $qTests->QUERY_SQL("SELECT * FROM webfilter_group");
        $prefix="INSERT INTO webfilter_group (ID,groupname,localldap,enabled,gpid,description,dn,settings)
	VALUES ";
        $f=array();
        $q->QUERY_SQL("DELETE FROM webfilter_group");
        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            $groupname=$line["groupname"];
            $localldap=$ligne["localldap"];
            $enabled=$ligne["enabled"];
            $gpid=$ligne["gpid"];
            $description=$ligne["description"];
            $dn=$ligne["dn"];
            $settings=$ligne["settings"];
            $f[]="($ID,'$groupname','$localldap','$enabled','$gpid','$description','$dn','$settings')";
        }

        if(count($f)>0){
            $sql=$prefix . @implode(",",$f);
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
        }

    }else{
        echo "[webfilter_group]: No such table..\n";
    }
    echo "--------------------------------------------------------------------------------\n";
    if($qTests->TABLE_EXISTS("webfilter_rules")) {
        echo "[webfilter_rules]: Importing.....\n";
        $results = $qTests->QUERY_SQL("SELECT * FROM webfilter_rules");
        $prefix="INSERT INTO webfilter_rules (ID,groupmode,enabled,groupname,BypassSecretKey,endofrule,blockdownloads,naughtynesslimit,searchtermlimit,bypass,deepurlanalysis,UseExternalWebPage,UseReferer,ExternalWebPage,freeweb,sslcertcheck,sslmitm,GoogleSafeSearch,TimeSpace,TemplateError,TemplateColor1,TemplateColor2,RewriteRules,zOrder,AllSystems,UseSecurity,embeddedurlweight,http_code) VALUES ";
        $q->QUERY_SQL("DELETE FROM webfilter_rules");
        $f=array();
        //preparse_query($results);
        //die();
        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            $groupmode=$ligne["groupmode"];
            $enabled=$ligne["enabled"];
            $groupname=$ligne["groupname"];
            $BypassSecretKey=$ligne["BypassSecretKey"];
            $endofrule=$ligne["endofrule"];
            $blockdownloads=$ligne["blockdownloads"];
            $naughtynesslimit=$ligne["naughtynesslimit"];
            $searchtermlimit=$ligne["searchtermlimit"];
            $bypass=$ligne["bypass"];
            $deepurlanalysis=$ligne["deepurlanalysis"];
            $UseExternalWebPage=$ligne["UseExternalWebPage"];
            $UseReferer=$ligne["UseReferer"];
            $ExternalWebPage=$ligne["ExternalWebPage"];
            $freeweb=$ligne["freeweb"];
            $sslcertcheck=$ligne["sslcertcheck"];
            $sslmitm=$ligne["sslmitm"];
            $GoogleSafeSearch=$ligne["GoogleSafeSearch"];
            $TimeSpace=$ligne["TimeSpace"];
            $TemplateError=$ligne["TemplateError"];
            $TemplateColor1=$ligne["TemplateColor1"];
            $TemplateColor2=$ligne["TemplateColor2"];
            $RewriteRules=$ligne["RewriteRules"];
            $zOrder=$ligne["zOrder"];
            $AllSystems=$ligne["AllSystems"];
            $UseSecurity=$ligne["UseSecurity"];
            $embeddedurlweight=$ligne["embeddedurlweight"];
            $http_code=$ligne["http_code"];
            $f[]="('$ID','$groupmode','$enabled','$groupname','$BypassSecretKey','$endofrule','$blockdownloads','$naughtynesslimit','$searchtermlimit','$bypass','$deepurlanalysis','$UseExternalWebPage','$UseReferer','$ExternalWebPage','$freeweb','$sslcertcheck','$sslmitm','$GoogleSafeSearch','$TimeSpace','$TemplateError','$TemplateColor1','$TemplateColor2','$RewriteRules','$zOrder','$AllSystems','$UseSecurity','$embeddedurlweight','$http_code')";

        }

        if(count($f)>0){
            $sql=$prefix . @implode(",",$f);
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
        }

    }
    else{
        echo "[webfilter_rules]: No such table..\n";
    }
    echo "--------------------------------------------------------------------------------\n";
    if($qTests->TABLE_EXISTS("webfilter_blkgp")) {
        echo "[webfilter_blkgp]: Importing.....\n";
        $results = $qTests->QUERY_SQL("SELECT * FROM webfilter_blkgp");
        $prefix="INSERT INTO webfilter_blkgp (ID,groupname,enabled) VALUES ";
        $f=array();
        $q->QUERY_SQL("DELETE FROM webfilter_blkgp");
        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            $groupname=$ligne["groupname"];
            $enabled=$ligne["enabled"];
            $f[]="('$ID','$groupname','$enabled')";
        }
        if(count($f)>0){
            $sql=$prefix . @implode(",",$f);
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
        }

    }
    else{
        echo "[webfilter_blkgp]: No such table..\n";
    }
    echo "--------------------------------------------------------------------------------\n";
    if($qTests->TABLE_EXISTS("webfilter_assoc_groups")) {
        echo "[webfilter_assoc_groups]: Importing.....\n";
        $results = $qTests->QUERY_SQL("SELECT * FROM webfilter_assoc_groups");
        $prefix="INSERT INTO webfilter_assoc_groups (ID,webfilter_id,group_id,zMD5) VALUES ";
        $f=array();
        //preparse_query($results);
        //die();
        $q->QUERY_SQL("DELETE FROM webfilter_assoc_groups");
        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            $webfilter_id=$ligne["webfilter_id"];
            $group_id=$ligne["group_id"];
            $zMD5=$ligne["zMD5"];
            $f[]="('$ID','$webfilter_id','$group_id','$zMD5')";
        }
        if(count($f)>0){
            $sql=$prefix . @implode(",",$f);
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
        }

    }
    else{
        echo "[webfilter_assoc_groups]: No such table..\n";
    }

    $q=new postgres_sql();
    $q->create_personal_categories();
    $q->QUERY_SQL("DELETE FROM personal_categories WHERE free_category=0 AND official_category=0");
    if(!$q->FIELD_EXISTS("personal_categories","blacklist")) {$q->QUERY_SQL("ALTER TABLE personal_categories ADD blacklist smallint NOT NULL DEFAULT 0");}
    if(!$q->FIELD_EXISTS("personal_categories","whitelist")) {$q->QUERY_SQL("ALTER TABLE personal_categories ADD whitelist smallint NOT NULL DEFAULT 0");}
    if(!$q->FIELD_EXISTS("personal_categories","nocache")) {$q->QUERY_SQL("ALTER TABLE personal_categories ADD nocache smallint NOT NULL DEFAULT 0");}
    if(!$q->FIELD_EXISTS("personal_categories","parent")) {$q->QUERY_SQL("ALTER TABLE personal_categories ADD parent VARCHAR(90) NULL");}

    echo "--------------------------------------------------------------------------------\n";
    if($qTests->TABLE_EXISTS("personal_categories")) {
        echo "[personal_categories]: Importing.....\n";
        $results = $qTests->QUERY_SQL("SELECT * FROM personal_categories");
        $categories=new categories();

        foreach ($results as $index=>$ligne){
            $category = $ligne["category"];
            $category_description = $ligne["category_description"];
            $master_category = $ligne["master_category"];
            echo "Creating Category $category master=$master_category ({$category_description})\n";
            if(!$categories->create_category($category,$category_description)){
                echo $categories->mysql_error;
                echo "{importing} Create category $category {failed}\n";
            }
        }
    }
    echo "--------------------------------------------------------------------------------\n";
    foreach ($categories_tables as $cat_table){
        echo "[$cat_table]: Importing.....\n";
        $results = $qTests->QUERY_SQL("SELECT * FROM $cat_table");
        $prefix="INSERT INTO $cat_table (sitename) VALUES ";
        $q->QUERY_SQL("DELETE FROM $cat_table");
        $f=array();
        foreach ($results as $index=>$ligne){
            $pattern=$ligne["pattern"];
            $f[]="('$pattern')";
        }
        if(count($f)>0){
            $sql=$prefix . @implode(",",$f);
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
        }
    }

    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    $qpost=new postgres_sql();


    if($qTests->TABLE_EXISTS("webfilter_blks")) {
        $f=array();
        echo "[webfilter_blks]: Importing.....\n";
        $prefix="INSERT INTO webfilter_blks (ID,webfilter_id,modeblk,category) VALUES ";
        $results = $qTests->QUERY_SQL("SELECT * FROM webfilter_blks");
        $q->QUERY_SQL("DELETE FROM webfilter_blks");
        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            $webfilter_id=$ligne["webfilter_id"];
            $modeblk=$ligne["modeblk"];
            $category=$ligne["category"];
            $sline = $qpost->mysqli_fetch_array("SELECT category_id FROM personal_categories WHERE categoryname='$category'");
            $category_id=$sline["category_id"];
            echo "[webfilter_blks]: Rule: $webfilter_id category=$category [$category_id] mode=$modeblk\n";
            $f[]="('$ID','$webfilter_id','$modeblk','$category_id')";
        }

        if(count($f)>0){
            echo "[webfilter_blks]: ".count($f)." items\n";
            $sql=$prefix . @implode(",",$f);
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
        }

    }
    if($qTests->TABLE_EXISTS("webfilter_blkcnt")) {
        $patterns_translator    = patterns_translator();
        $f=array();
        echo "[webfilter_blkcnt]: Importing.....\n";
        $prefix="INSERT OR IGNORE INTO webfilter_blkcnt (ID,webfilter_blkid,category) VALUES ";
        $results = $qTests->QUERY_SQL("SELECT * FROM webfilter_blkcnt");
        $q->QUERY_SQL("DELETE FROM webfilter_blks");

        foreach ($results as $index=>$ligne) {
            $ID = $ligne["ID"];
            $webfilter_blkid = $ligne["webfilter_blkid"];
            $category = $ligne["category"];
            $category_id=0;
            if(isset($patterns_translator[$category])){
                $category_id=$patterns_translator[$category];
            }
            if($category_id==0){
                $sline = $qpost->mysqli_fetch_array("SELECT category_id FROM personal_categories WHERE categoryname='$category'");
                $category_id=intval($sline["category_id"]);
            }
            echo "Rule: $webfilter_blkid category=$category [$category_id]\n";
            if($category_id==0){continue;}
            $f[] = "('$ID','$webfilter_blkid','$category_id')";
        }
        if(count($f)>0){
            $sql=$prefix . @implode(",",$f);
            $q->QUERY_SQL($sql);
            if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
        }

    }




}

function preparse_query($results){
    foreach ($results as $index=>$ligne){
        foreach ($ligne as $key=>$val){
            echo "\${$key}=\$ligne[\"$key\"];\n";
            $prefix[]=$key;
            $prefixsql[]="'\$$key'";
        }


        echo "\$prefix=\"INSERT INTO xxxx (".@implode(",",$prefix).") VALUES \";\n";
        echo "\$f[]=\"(".@implode(",",$prefixsql).")\";\n";
        return false;
    }


}

function import3x($filename){
    $unix       = new unix();
    $rm         = $unix->find_program("rm");
    $BASEDIR    = "/home/artica/import3x";
    echo        "Filename.....: $filename\n";
    $NODELETE   = false;
    build_progress($filename,5);

    if(is_file($filename)){
        $fullpath=$filename;
        $NODELETE=true;
    }else{
        $fullpath=dirname(__FILE__)."/ressources/conf/upload/$filename";
    }


    echo "Fullpath.....: $fullpath\n";
    if(!is_file($fullpath)){
        build_progress($filename." no such file",110);
        return;
    }


    if(!is_dir($BASEDIR)){ @mkdir($BASEDIR,0755,true);}
    $tar=$unix->find_program("tar");
    $fullpath_exe=$unix->shellEscapeChars($fullpath);


    $f[]="access_allow.link";
    $f[]="access_allow.sql";
    $f[]="access_deny.link";
    $f[]="access_deny.sql";
    $f[]="always_direct.link";
    $f[]="always_direct.sql";
    $f[]="categories.sql";
    $f[]="groups.sql";
    $f[]="items.sql";
    $f[]="parents.sql";
    $f[]="whitelists.sql";
    $f[]="ARRAY_CONTENT";
    $f[]="squidlogs/reverse_www.gz";
    $f[]="squidlogs/reverse.db";

    $dirs[]="Daemons";
    $dirs[]="artica_backup";
    $dirs[]="squidlogs";
    $dirs[]="powerdns";
    $dirs[]="nginx";

    foreach ($f as $basename){
        if(is_file("$BASEDIR/$basename")){
            echo "Removing file $BASEDIR/$basename\n";
            @unlink("$BASEDIR/$basename");
        }
    }
    foreach ($dirs as $dirname){
        if(is_dir("$BASEDIR/$dirname")){
            build_progress("{remove_directory} $dirname...", 7);
            echo "Removing directory $BASEDIR/$dirname\n";
            shell_exec("$rm -rf $BASEDIR/$dirname");
        }
    }

    build_progress("{uncompress}...", 8);
    echo "$tar -xf $fullpath_exe -C $BASEDIR/\n";
    exec("$tar -xf $fullpath_exe -C $BASEDIR/ 2>&1",$results);
    foreach ($results as $line){
        echo "$line\n";
    }
    build_progress("{listing_content}...", 9);

    echo "Listing package...\n";
    if ($handle = opendir($BASEDIR)) {
        while (false !== ($fileZ = readdir($handle))) {
            if ($fileZ == ".") {continue;}
            if ($fileZ == "..") {continue;}
            if (!is_dir("$BASEDIR/$fileZ")) {
                echo "Found file $fileZ\n";
                continue;}
            echo "Found directory $fileZ\n";
            if($fileZ=="artica_backup"){
                $handle2 = opendir("$BASEDIR/artica_backup");
                while (false !== ($file2 = readdir($handle2))) {
                    if ($file2 == ".") {continue;}
                    if ($file2 == "..") {continue;}
                    if (is_dir("$BASEDIR/artica_backup/$file2")) {continue;}
                    echo "Found file $BASEDIR/artica_backup/$file2\n";
                }
            }
            if($fileZ=="squidlogs"){
                $handle2 = opendir("$BASEDIR/squidlogs");
                while (false !== ($file2 = readdir($handle2))) {
                    if ($file2 == ".") {continue;}
                    if ($file2 == "..") {continue;}
                    if (is_dir("$BASEDIR/squidlogs/$file2")) {continue;}
                    echo "Found file $BASEDIR/squidlogs/$file2\n";
                }
            }

        }
    }


    if(is_file("$BASEDIR/squidlogs/webfilter_blks.gz")){
        migrate_3xSnapWebfilters($BASEDIR);
    }else{
        echo "$BASEDIR/squidlogs/webfilter_blks.gz no such file\n";
    }

    $cP=9;
    if(!$NODELETE){@unlink($fullpath);}




    if(is_file("$BASEDIR/artica_backup/sslcertificates.gz")){
        build_progress("{importing} {certificates_center}", $cP++);
        if(import_certificate_center()){
            merge_certificate_center_data();
        }
    }else{
        echo "$BASEDIR/artica_backup/sslcertificates.gz no such file\n";
        build_progress("{importing} {certificates_center} {failed}", $cP++);
    }
    
    if(is_file("$BASEDIR/squidlogs/reverse_www.gz")){
        build_progress("{checking_reverse_proxy_service}...", $cP++);
        if(import_reverse_proxy()){
            merge_reverse_proxy_data();
        }
    }

    if(is_dir("$BASEDIR/Daemons")){
        $BaseWorkDir="$BASEDIR/Daemons";
        build_progress("{importing} $BaseWorkDir Settings.", $cP++);
        $handle = opendir($BaseWorkDir);
        $Daemons=0;
        $artica_settings_blacklists=artica_settings_blacklists();
        if($handle){
            while (false !== ($filename = readdir($handle))) {
                if($filename=="."){continue;}
                if($filename==".."){continue;}
                if(isset($artica_settings_blacklists[$filename])){continue;}
                $targetFile="$BaseWorkDir/$filename";
                if(is_dir($targetFile)){continue;}
                $Value=trim(@file_get_contents($targetFile));
                @unlink($targetFile);
                if($Value==null){continue;}
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO($filename,$Value);
                $Daemons++;
            }
        }

        build_progress("{importing} {$Daemons} Tokens {imported}", $cP++);

    }else{
        build_progress("{importing} $BASEDIR/Daemons no such directory.", $cP++);
    }



    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $q->QUERY_SQL("DELETE FROM webfilters_sqitems");
    $c=0;

    if(is_file("$BASEDIR/items.sql")) {
        build_progress("{importing} items.sql", $cP++);
        $MAIN = unserialize(@file_get_contents("/home/artica/import3x/items.sql"));
        $prefix = "INSERT INTO webfilters_sqitems (gpid,pattern,zdate,uid,enabled) VALUES ";
        $date = date("Y-m-d H:i:s");

        $d=0;
        $max=count($MAIN);
        foreach ($MAIN as $ID => $ligne) {
            $d++;$c++;
            if($d>20) {
                $prc=$c/$max;
                $prc=round($prc*100);
                build_progress("{importing} items.sql ($c rows {$prc}%)", $cP++);
                $d = 0;
            }
            $sql="$prefix ({$ligne["gpid"]},'{$ligne["pattern"]}','$date','Root','{$ligne["enabled"]}')";
            $q->QUERY_SQL($sql);
            if (!$q->ok) {
                echo $q->mysql_error;
                echo "$sql\n";
                build_progress("{importing} items.sql {failed}", 110);
                return;
            }
        }
    }
    build_progress("{importing} items.sql ($c rows)", $cP++);


    $f=array();
    if(is_file("$BASEDIR/groups.sql")) {
        build_progress("{importing} groups.sql", $cP++);
        $MAIN = unserialize(@file_get_contents("/home/artica/import3x/groups.sql"));
        $prefix="INSERT INTO webfilters_sqgroups (ID,GroupName,GroupType,enabled,`acltpl`,`params`,`PortDirection`,`tplreset`)
	VALUES ";

        foreach ($MAIN as $ID => $ligne) {
            build_progress("{importing} {group2} {$ligne["GroupName"]}", $cP++);
            $GroupName=$q->sqlite_escape_string2($ligne["GroupName"]);
            $f[] = "($ID,'$GroupName','{$ligne["GroupType"]}','{$ligne["enabled"]}','','','0',0)";
        }

        if (count($f) > 0) {
            $q->QUERY_SQL("DELETE FROM webfilters_sqgroups");
            $q->QUERY_SQL($prefix . @implode(",", $f));
            if (!$q->ok) {
                echo "File: groups.sql\n";
                echo "SQL: ".$prefix . @implode(",", $f);
                echo $q->mysql_error;
                build_progress("{importing} groups.sql {failed}", 110);
                return;
            }

        }
    }
    //$MAIN_ARRAY[$aclid]=array("aclname"=>$aclname,"enabled"=>$enabled,"order"=>$order);

    //"";

    $q->QUERY_SQL("DELETE FROM webfilters_sqacls");
    $q->QUERY_SQL("DELETE FROM webfilters_sqacllinks");

    $acl=new squid_acls_groups();

    if(is_file("$BASEDIR/access_allow.sql")) {
        build_progress("{importing} access_allow.sql", $cP++);
        $MAIN = unserialize(@file_get_contents("/home/artica/import3x/access_allow.sql"));
        $prefix="INSERT INTO webfilters_sqacls (ID,aclname,enabled,acltpl,xORDER,aclport,aclgroup,aclgpid)
	VALUES ";

        foreach ($MAIN as $ID => $ligne) {
            $TempName=$q->sqlite_escape_string2($ligne["aclname"]);
            build_progress("{importing} access_allow $TempName", $cP++);
            $enabled=$ligne["enabled"];
            $order=$ligne["order"];
            $q->QUERY_SQL($prefix ."($ID,'$TempName',$enabled,'','$order','0','0','0')");
            if (!$q->ok) {
                echo $q->mysql_error;
                build_progress("{importing} access_allow.sql {failed}", 110);
                return;
            }


            if(!$acl->aclrule_edittype($ID,"access_allow",1)){
                echo "ERROR aclrule_edittype($ID,access_allow,1)\n";
                build_progress("{importing} access_allow.sql {failed}", 110);
                return;
            }
        }
    }
    if(is_file("$BASEDIR/access_allow.link")) {
        build_progress("{importing} access_allow.link", $cP++);
        $MAIN = unserialize(@file_get_contents("/home/artica/import3x/access_allow.link"));
        //$MAIN_LINK[$ID][]=array("gpid"=>$gpid,"negation"=>$negation,"order"=>$zOrder);

        $prefix="INSERT OR IGNORE INTO webfilters_sqacllinks (zmd5,aclid,gpid,zOrder,negation) VALUES ";

        foreach ($MAIN as $aclid => $array) {
            foreach ($array as $index => $ligne) {
                $gpid = $ligne["gpid"];
                $direction = 0;
                $negation = $ligne["negation"];
                $order = $ligne["order"];
                $md5 = md5($aclid . $gpid . $direction);
                $q->QUERY_SQL($prefix . "('$md5','$aclid','$gpid',$order,$negation)");
            }
        }
    }


    if(is_file("$BASEDIR/access_deny.sql")) {
        build_progress("{importing} access_deny.sql", $cP++);
        $MAIN = unserialize(@file_get_contents("/home/artica/import3x/access_deny.sql"));
        $prefix="INSERT INTO webfilters_sqacls (ID,aclname,enabled,acltpl,xORDER,aclport,aclgroup,aclgpid)
	VALUES ";

        foreach ($MAIN as $ID => $ligne) {
            $TempName=$q->sqlite_escape_string2($ligne["aclname"]);
            $enabled=$ligne["enabled"];
            $order=$ligne["order"];
            $q->QUERY_SQL($prefix ."($ID,'$TempName',$enabled,'','$order','0','0','0')");
            if (!$q->ok) {
                echo $q->mysql_error;
                build_progress("{importing} access_deny.sql {failed}", 110);
                return;
            }


            if(!$acl->aclrule_edittype($ID,"access_deny",1)){
                echo "ERROR aclrule_edittype($ID,access_deny,1)\n";
                build_progress("{importing} access_deny.sql {failed}", 110);
                return;
            }
        }
    }


    if(is_file("$BASEDIR/access_deny.link")) {
        build_progress("{importing} access_deny.link", $cP++);
        $MAIN = unserialize(@file_get_contents("/home/artica/import3x/access_deny.link"));
        //$MAIN_LINK[$ID][]=array("gpid"=>$gpid,"negation"=>$negation,"order"=>$zOrder);

        $prefix="INSERT OR IGNORE INTO webfilters_sqacllinks (zmd5,aclid,gpid,zOrder,negation) VALUES ";

        foreach ($MAIN as $aclid => $array) {
            foreach ($array as $index => $ligne) {
                $gpid = $ligne["gpid"];
                $direction = 0;
                $negation = $ligne["negation"];
                $order = $ligne["order"];
                $md5 = md5($aclid . $gpid . $direction);
                $q->QUERY_SQL($prefix . "('$md5','$aclid','$gpid',$order,$negation)");
            }
        }
    }

    $q->QUERY_SQL("DELETE FROM squid_parents_acls");
    $q->QUERY_SQL("DELETE FROM parents_sqacllinks");
    if(is_file("$BASEDIR/parents.sql")) {

        $MAIN = unserialize(@file_get_contents("$BASEDIR/parents.sql"));
        if(count($MAIN)>0){
            build_progress("{importing} Parents", $cP++);
            $PARENTS=array();
            foreach ($MAIN as $ID=>$array){
                $servername=$array["servername"];
                $server_port=$array["server_port"];
                $PARENTS["$servername:$server_port"]["SAVED"]=time();
            }

            $PARENTSENC=base64_encode(serialize($PARENTS));
            $q->QUERY_SQL("INSERT INTO `squid_parents_acls` (`rulename`,`enabled` ,`zorder`,`never_direct`,proxies) 
            VALUES ('Default parents','1','0','1','$PARENTSENC')");
            system("/usr/bin/php /usr/share/artica-postfix/exec.squid.global.access.php --enable-parents");


        }

    }
    build_progress("{importing} whitelists", $cP++);
    $q->QUERY_SQL("DELETE FROM acls_whitelist");
    if(is_file("$BASEDIR/whitelists.sql")) {
        $MAIN=unserialize(@file_get_contents("/home/artica/import3x/whitelists.sql"));
        if(count($MAIN)>0) {
            $q->QUERY_SQL("INSERT INTO acls_whitelist (zDate,ztype,pattern,enabled,description) 
            VALUES " . @implode(",", $MAIN));
            if (!$q->ok) {
                echo $q->mysql_error;
                build_progress("{importing} whitelists.sql {failed}", 110);
                return;
            }
        }

    }

    if(is_file("$BASEDIR/categories.sql")) {
        $size=@filesize("$BASEDIR/categories.sql");
        $size=round($size/1024,2);
        build_progress("{importing} Categories {$size}KB", $cP++);
        $q=new postgres_sql();
        $q->create_personal_categories();
        $q->QUERY_SQL("DELETE FROM personal_categories WHERE free_category=0 AND official_category=0");

        if(!$q->FIELD_EXISTS("personal_categories","blacklist")) {
            $q->QUERY_SQL("ALTER TABLE personal_categories ADD blacklist smallint NOT NULL DEFAULT 0");
        }
        if(!$q->FIELD_EXISTS("personal_categories","whitelist")) {
            $q->QUERY_SQL("ALTER TABLE personal_categories ADD whitelist smallint NOT NULL DEFAULT 0");
        }
        if(!$q->FIELD_EXISTS("personal_categories","nocache")) {
            $q->QUERY_SQL("ALTER TABLE personal_categories ADD nocache smallint NOT NULL DEFAULT 0");
        }
        if(!$q->FIELD_EXISTS("personal_categories","parent")) {
            $q->QUERY_SQL("ALTER TABLE personal_categories ADD parent VARCHAR(90) NULL");
        }


        echo "Uncompressing categories\n";
        echo "$BASEDIR/categories.sql {$size}KB\n";
        $MAIN=unserialize(@file_get_contents("$BASEDIR/categories.sql"));
        echo "Categories: ".count($MAIN)." items...\n";

        foreach ($MAIN as $categoryname=>$array){
            $categories=new categories();
            echo "Creating Category $categoryname ({$array["DESC"]})\n";
            if(!$categories->create_category($categoryname,$array["DESC"])){
                echo $categories->mysql_error;
                build_progress("{importing} Create category $categoryname {failed}", 110);
                continue;

            }

            $ligne=$q->mysqli_fetch_array("SELECT categorytable FROM personal_categories WHERE categoryname='$categoryname'");
            if(!$q->ok){
                echo $q->mysql_error;
                build_progress("{importing} Categories {failed}", 110);
                return;
            }
            $categorytable=$ligne["categorytable"];
            echo "$categoryname -> PostGrey Table: [$categorytable]\n";

            echo "Injecting ".count($array["ITEMS"])." item(s) in $categorytable...\n";
            $q->QUERY_SQL("INSERT INTO $categorytable (sitename) VALUES ".
                @implode(",",$array["ITEMS"]). " ON CONFLICT DO NOTHING");


        }


    }
    build_progress("{search} {categories} ....", 90);
    search_categories();
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.proxy.acls.explains.php >/dev/null 2>&1 &");
    build_progress("{importing} {success}", 100);
}

function search_categories(){
    $qpost                  = new postgres_sql();
    $patterns_translator    = patterns_translator();
    echo "Searching categories objects\n";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $results=$q->QUERY_SQL("SELECT ID,GroupName FROM webfilters_sqgroups WHERE GroupType='categories'");
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $GroupName=$ligne["GroupName"];
        echo "\nMigrating $GroupName\n*******************************\n";
        $sql="SELECT pattern,ID FROM webfilters_sqitems WHERE gpid=$ID ORDER BY pattern";
        $results2 = $q->QUERY_SQL($sql);
        foreach ($results2 as $index2=>$ligne2){
            $pid=$ligne2["ID"];
            $category_text=trim($ligne2["pattern"]);
            if(is_numeric($category_text)){continue;}
            echo "Migrating $category_text -->";

            if(isset($patterns_translator[$category_text])){
                $pattern_id=$patterns_translator[$category_text];
                if($pattern_id>0) {
                    echo $pattern_id."\n";
                    $q->QUERY_SQL("UPDATE webfilters_sqitems SET pattern='$pattern_id' WHERE ID=$pid");
                    continue;
                }
            }

            $sline = $qpost->mysqli_fetch_array("SELECT category_id FROM personal_categories WHERE
                                                    categoryname='$category_text'");
            $pattern_id=intval($sline["category_id"]);
            if($pattern_id>0) {
                echo $pattern_id . "\n";
                $q->QUERY_SQL("UPDATE webfilters_sqitems SET pattern='$pattern_id' WHERE ID=$pid");
                continue;
            }
        }

    }

}

function patterns_translator(){
    $translate["facebook"]                 = 1;
    $translate["microsoft"]                = 2;
    $translate["society"]                  = 3;
    $translate["publicite"]                = 5;
    $translate["phishtank"]                = 6;
    $translate["ransomwares"]              = 7;
    $translate["shopping"]                 = 8;
    $translate["abortion"]                 = 9;
    $translate["agressive"]                = 10;
    $translate["alcohol"]                  = 11;
    $translate["animals"]                  = 12;
    $translate["associations"]             = 13;
    $translate["astrology"]                = 14;
    $translate["audio-video"]              = 15;
    $translate["youtube"]                  = 16;
    $translate["google"]                   = 17;
    $translate["apple"]                    = 18;
    $translate["amazonaws"]                = 19;
    $translate["akamai"]                   = 20;
    $translate["yahoo"]                    = 21;
    $translate["skype"]                    = 22;
    $translate["citrix"]                   = 23;
    $translate["automobile/bikes"]         = 24;
    $translate["automobile/boats"]         = 25;
    $translate["automobile/carpool"]       = 26;
    $translate["automobile/cars"]          = 27;
    $translate["automobile/planes"]        = 28;
    $translate["bicycle"]                  = 29;
    $translate["blog"]                     = 30;
    $translate["books"]                    = 31;
    $translate["browsersplugins"]          = 32;
    $translate["celebrity"]                = 33;
    $translate["chat"]                     = 34;
    $translate["children"]                 = 35;
    $translate["cleaning"]                 = 36;
    $translate["clothing"]                 = 37;
    $translate["converters"]               = 38;
    $translate["cosmetics"]                = 39;
    $translate["culture"]                  = 40;
    $translate["dangerous_material"]       = 41;
    $translate["dating"]                   = 42;
    $translate["dictionaries"]             = 43;
    $translate["downloads"]                = 44;
    $translate["drugs"]                    = 45;
    $translate["dynamic"]                  = 46;
    $translate["electricalapps"]           = 47;
    $translate["electronichouse"]          = 48;
    $translate["filehosting"]              = 49;
    $translate["finance/banking"]          = 50;
    $translate["finance/insurance"]        = 51;
    $translate["finance/moneylending"]     = 52;
    $translate["finance/other"]            = 53;
    $translate["finance/realestate"]       = 54;
    $translate["financial"]                = 55;
    $translate["forums"]                   = 56;
    $translate["gamble"]                   = 57;
    $translate["games"]                    = 58;
    $translate["genealogy"]                = 59;
    $translate["gifts"]                    = 60;
    $translate["governments"]               = 62;
    $translate["green"]                     = 63;
    $translate["hacking"]                   = 64;
    $translate["handicap"]                  = 65;
    $translate["health"]                    = 66;
    $translate["hobby/arts"]                = 67;
    $translate["hobby/cooking"]             = 68;
    $translate["hobby/other"]               = 69;
    $translate["hobby/pets"]                = 70;
    $translate["paytosurf"]                 = 71;
    $translate["terrorism"]                 = 72;
    $translate["hobby/fishing"]             = 73;
    $translate["hospitals"]                 = 74;
    $translate["houseads"]                  = 75;
    $translate["housing/accessories"]       = 76;
    $translate["housing/doityourself"]      = 77;
    $translate["housing/builders"]          = 78;
    $translate["humanitarian"]              = 79;
    $translate["imagehosting"]              = 80;
    $translate["industry"]                  = 81;
    $translate["internal"]                  = 82;
    $translate["isp"]                       = 83;
    $translate["jobsearch"]                 = 85;
    $translate["jobtraining"]               = 86;
    $translate["justice"]                   = 87;
    $translate["learning"]                  = 88;
    $translate["liste_bu"]                  = 89;
    $translate["luxury"]                    = 90;
    $translate["mailing"]                   = 91;
    $translate["malware"]                   = 92;
    $translate["manga"]                     = 93;
    $translate["maps"]                      = 94;
    $translate["marketingware"]             = 95;
    $translate["medical"]                   = 96;
    $translate["mixed_adult"]               = 97;
    $translate["mobile-phone"]              = 98;
    $translate["models"]                    = 99;
    $translate["movies"]                    = 100;
    $translate["music"]                     = 101;
    $translate["nature"]                    = 102;
    $translate["news"]                      = 103;
    $translate["passwords"]                 = 104;
    $translate["phishing"]                  = 105;
    $translate["photo"]                     = 106;
    $translate["pictureslib"]               = 107;
    $translate["politic"]                   = 108;
    $translate["porn"]                      = 109;
    $translate["proxy"]                     = 111;
    $translate["reaffected"]                = 112;
    $translate["recreation/humor"]          = 113;
    $translate["recreation/nightout"]       = 114;
    $translate["recreation/schools"]        = 115;
    $translate["recreation/sports"]         = 116;
    $translate["getmarried"]                = 117;
    $translate["police"]                    = 118;
    $translate["recreation/travel"]         = 119;
    $translate["recreation/wellness"]       = 120;
    $translate["redirector"]                = 121;
    $translate["religion"]                  = 122;
    $translate["remote-control"]            = 123;
    $translate["sciences"]                  = 124;
    $translate["science/astronomy"]         = 125;
    $translate["science/computing"]         = 126;
    $translate["science/weather"]           = 127;
    $translate["science/chemistry"]         = 128;
    $translate["searchengines"]             = 129;
    $translate["sect"]                      = 130;
    $translate["sexual_education"]          = 131;
    $translate["sex/lingerie"]              = 132;
    $translate["smallads"]                  = 133;
    $translate["socialnet"]                 = 134;
    $translate["spyware"]                   = 135;
    $translate["sslsites"]                  = 136;
    $translate["stockexchange"]             = 137;
    $translate["suspicious"]                = 140;
    $translate["teens"]                     = 141;
    $translate["tobacco"]                   = 142;
    $translate["tracker"]                   = 143;
    $translate["translators"]               = 144;
    $translate["transport"]                 = 145;
    $translate["tricheur"]                  = 146;
    $translate["updatesites"]               = 147;
    $translate["violence"]                  = 148;
    $translate["warez"]                     = 149;
    $translate["weapons"]                   = 150;
    $translate["webapps"]                   = 151;
    $translate["webmail"]                   = 152;
    $translate["webplugins"]                = 154;
    $translate["webradio"]                  = 155;
    $translate["webtv"]                     = 156;
    $translate["wine"]                      = 157;
    $translate["womanbrand"]                = 158;
    $translate["horses"]                    = 159;
    $translate["tattooing"]                 = 161;
    $translate["literature"]                = 163;
    $translate["Artificial Intelligence"]                = 248;

    return $translate;
}

function MySQLToSQLite($src,$destination){
    $dstlite    = "$src.sqlite";
    $convbin    =   "/usr/share/artica-postfix/bin/mysql2sqlite.sh";
    shell_exec("$convbin $src > $dstlite");
    $content=@file_get_contents($dstlite);
    $content=str_replace("IGNORE ","OR IGNORE ",$content);
    @file_put_contents($destination,$content);
    @unlink($dstlite);
    return true;

}

function import_reverse_proxy():bool{
    $unix       =   new unix();
    $tmpdb      =   "/home/artica/import3x/squidlogs/reverse.db";
    $DBPATH     =   "/home/artica/import3x/squidlogs";
    $convbin    =   "/usr/share/artica-postfix/bin/mysql2sqlite.sh";
    $sqlite3    =   "/usr/bin/sqlite3";
    $cat        =   $unix->find_program("cat");

    @chmod($convbin,0755);
    if(is_file($tmpdb)){@unlink($tmpdb);}

    $tables[]="reverse_www";
    $tables[]="reverse_dirs";
    $tables[]="reverse_pages_content";
    $tables[]="reverse_sources";
    $tables[]="nginx_aliases";

    foreach ($tables as $tablename){

        $srcgz      = "$DBPATH/$tablename.gz";
        $srcsql     = "$DBPATH/$tablename.sql";
        $dstlite    = "$DBPATH/$tablename.sqlite";


        if(!is_file($srcgz)){
            echo "$srcgz no such file...Aborting\n";
            continue;
        }

        echo "Uncompressing $tablename...\n";
        if(!$unix->uncompress($srcgz,$srcsql)){
            echo "Uncompressing $tablename failed\n";
            @unlink($srcsql);
            @unlink($srcgz);
            continue;
        }

        if(!is_file($srcsql)){
            echo "$srcsql, no such file\n";
            @unlink($srcgz);
            continue;
        }

        echo "Converting $tablename...\n";
        MySQLToSQLite($srcsql,$dstlite);
        @unlink($srcsql);
        @unlink($srcgz);

        echo "Importing $dstlite....\n";
        system("$cat $dstlite | $sqlite3 $tmpdb");

        $qTests       =   new lib_sqlite($tmpdb);
        if(!$qTests->TABLE_EXISTS($tablename)){
            echo  "Importing $tablename failed (not exists) ...\n";
            continue;
        }

        echo "Importing $tablename Success...\n";

    }


    return true;
}

function merge_reverse_proxy_ports($interface,$port,$nginx_services_id,$ssl):bool{
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $md5=md5($interface.$port.$nginx_services_id);

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM stream_ports WHERE zmd5='$md5'");
    if(intval($ligne["ID"])>0){return true;}

    $OPTS["ssl"]=$ssl;
    $options=base64_encode(serialize($OPTS));

    $sql="INSERT INTO stream_ports(serviceid,interface,port,zmd5,options) 
            VALUES ($nginx_services_id,'$interface',$port,'$md5','$options')";

    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        return false;
    }
    return true;
}

function merge_reverse_proxy_sources($cache_peer_id,$nginx_services_id):array{
    $tmpdb      =   "/home/artica/import3x/squidlogs/reverse.db";
    $qSrc       = new lib_sqlite($tmpdb);
    $q          = new lib_sqlite("/home/artica/SQLITE/nginx.db");


    $results=$qSrc->QUERY_SQL("SELECT *  FROM reverse_sources WHERE ID='{$cache_peer_id}'");
    $RETURNED=array();
    foreach ($results as $index=>$ligne) {
        $options=array();
        $ipaddr=$ligne["ipaddr"];
        $port=intval($ligne["port"]);
        $ssl=intval($ligne["ssl"]);
        $forceddomain=$ligne["forceddomain"];
        $remote_path=$ligne["remote_path"];
        $options["UseSSL"]=$ssl;

        if($forceddomain<>null) {
            $RETURNED[$nginx_services_id]["forceddomain"] = $forceddomain;
        }

        if($remote_path<>null) {
            $RETURNED[$nginx_services_id]["remote_path"] = $remote_path;
        }

        $options=base64_encode(serialize($options));
        $LastID=$q->mysqli_fetch_array("SELECT ID FROM backends 
            where serviceid=$nginx_services_id AND hostname='$ipaddr' AND port='$port'");
        if($LastID>0){
            echo "$ipaddr:$port Already added from service ID: $nginx_services_id\n";
            continue;
        }
            $q->QUERY_SQL("INSERT OR IGNORE INTO backends(serviceid,hostname,port,options) 
				VALUES ($nginx_services_id,'$ipaddr',$port,'$options')");
            if(!$q->ok){echo $q->mysql_error;}

    }
    return $RETURNED;

}

function merge_reverse_proxy_clean():bool{
    $q          = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $tables[]="nginx_services";
    $tables[]="service_parameters";
    $tables[]="stream_ports";
    $tables[]="backends";

    foreach ($tables as $tablename){
        echo "Clean table $tablename From nginx.db\n";
        $q->QUERY_SQL("DELETE FROM $tablename");
        if(!$q->ok){
            echo $q->mysql_error."\n\n";
            return false;
        }

    }
    return true;
}

function merge_reverse_proxy_data(){
    include_once(dirname(__FILE__)."/ressources/class.nginx.params.inc");
    $tmpdb      =   "/home/artica/import3x/squidlogs/reverse.db";
    $q          = new lib_sqlite($tmpdb);
    if(!merge_reverse_proxy_clean()){
        return false;
    }

    $results=$q->QUERY_SQL("SELECT * FROM reverse_www");

    foreach ($results as $index=>$ligne){
            $servername=$ligne["servername"];
            $ipaddr=$ligne["ipaddr"];
            $port=$ligne["port"];
            $sslport=$ligne["sslport"];
            $ssl=$ligne["ssl"];
            $enabled=$ligne["enabled"];
            $certificate=$ligne["certificate"];
            $ssl_backend=$ligne["ssl_backend"];
            $interface="eth0";
            $cache_peer_id=$ligne["cache_peer_id"];
            $nginx_services_id=nginx_services_id($servername,$enabled);
            echo "Merging data for $servername:$port\n";

            if($port==80) {
                merge_reverse_proxy_ports($interface, $port, $nginx_services_id, 0);
                if($ssl==1) {
                    merge_reverse_proxy_ports($interface, 443, $nginx_services_id, 1);
                }
            }else{
                if($port>0) {
                    merge_reverse_proxy_ports($interface, $port, $nginx_services_id, $ssl);
                }
            }

            $RETURNED=merge_reverse_proxy_sources($cache_peer_id,$nginx_services_id);
            if(count($RETURNED)>0) {
                foreach ($RETURNED as $sid=>$smain) {
                    $sock_returned=new socksngix($sid);
                    if(isset($smain["forceddomain"]) ){
                        $sock_returned->SET_INFO("HostHeader", $smain["forceddomain"]);
                    }
                    if(isset($smain["remote_path"]) ){
                        $sock_returned->SET_INFO("RemotePath", $smain["remote_path"]);
                    }
                }

            }


            $F=array();
            $F[]=$servername;
            $sockngix=new socksngix($nginx_services_id);

            $sockngix->SET_INFO("ssl_certificate", $certificate);
            $newval=trim(@implode("||",$F));
            echo "{$servername}[$ipaddr]:HTTP:$port SSL:$sslport (ssl=$ssl/cert=$certificate) Enabled=$enabled; $newval\n";




    }
return true;

}

function nginx_services_id($servername,$enabled):int{
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM nginx_services WHERE servicename='$servername'");
    $ID=intval($ligne["ID"]);
    if($ID>0){
        echo "$servername have ID $ID, SKIP\n";
        return $ID;}

    $q->QUERY_SQL("INSERT INTO nginx_services (`type`,`servicename`,`enabled`,goodconftime,hosts)  VALUES ('2','$servername',$enabled,0,'$servername')");
    if(!$q->ok){echo $q->mysql_error."\n";}


    $ligne=$q->mysqli_fetch_array("SELECT ID FROM nginx_services WHERE servicename='$servername'");
    $ID=intval($ligne["ID"]);
    if(!$q->ok){echo $q->mysql_error."\n";}
    echo "$servername created new service id number $ID\n";
    return $ID;


}




function import_certificate_center(){
    $unix       =   new unix();
    $BASEDIR    =   "/home/artica/import3x";
    $DBPATH     =   "$BASEDIR/artica_backup";
    $tmpdb      =   "$DBPATH/certificates.db";
    $convbin    =   "/usr/share/artica-postfix/bin/mysql2sqlite.sh";
    $sqlite3    =   "/usr/bin/sqlite3";
    $cat        =   $unix->find_program("cat");
    $srcfile    =   "$DBPATH/sslcertificates.gz";
    $srcsql     =   "$DBPATH/sslcertificates.sql";
    $dstlite    =   "$DBPATH/sslcertificates.sqlite";

    if(is_file($dstlite)){@unlink($dstlite);}
    if(is_file($DBPATH)){@unlink($DBPATH);}

    if(!$unix->uncompress($srcfile,$srcsql)){
        echo "Uncompressing $srcfile failed\n";
        return false;
    }
    $q=new lib_sqlite($tmpdb);
    $sql="CREATE TABLE IF NOT EXISTS `dummytable` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`nothing` TEXT NOT NULL)";
    $q->QUERY_SQL($sql);

    echo "Converting sslcertificates into $tmpdb...\n";
    $cmd="$convbin $srcsql > $dstlite";
    echo "$cmd\n";
    shell_exec($cmd);
    $content=@file_get_contents($dstlite);

    $content=str_replace("IGNORE ","OR IGNORE ",$content);
    @file_put_contents($dstlite,$content);


    echo "Importing From MySQL to sQlite: $dstlite\n";
    $cmd="$cat $dstlite | $sqlite3 $tmpdb";
    echo $cmd."\n";
    system("$cat $dstlite | $sqlite3 $tmpdb");
    return true;

}

function merge_certificate_center_data():bool{
    $unix       = new unix();
    $php        = $unix->LOCATE_PHP5_BIN();
    $BASEDIR    = "/home/artica/import3x";
    $DBPATH     = "$BASEDIR/artica_backup";
    $tmpdb      = "$DBPATH/certificates.db";
    $qs         = new lib_sqlite($tmpdb);
    $results    = $qs->QUERY_SQL("SELECT * FROM sslcertificates");



    $q = new lib_sqlite(CERTDB);
    $tot_import=count($results);
    echo "Importing ".count($results)." certificates...\n";
    $imported=0;

    foreach ($results as $index => $ligne) {
        $CommonName = $ligne["CommonName"];
        $UsePrivKeyCrt = intval($ligne["UsePrivKeyCrt"]);
        $privkey = $ligne["privkey"];
        $bundle = $ligne["bundle"];
        $crt = $ligne["crt"];
        $DateFrom = $ligne["DateFrom"];
        $DateTo = $ligne["DateTo"];
        $srca = $ligne["srca"];
        $SquidCert = $ligne["SquidCert"];
        $Squidkey = $ligne["Squidkey"];
        $CertificateMaxDays = $ligne["CertificateMaxDays"];
        $CountryName = $ligne["CountryName"];
        $stateOrProvinceName = $ligne["stateOrProvinceName"];
        $localityName = $ligne["localityName"];
        $OrganizationName = $ligne["OrganizationName"];
        $OrganizationalUnit = $ligne["OrganizationalUnit"];
        $CompanyName = $ligne["CompanyName"];
        $emailAddress = $ligne["emailAddress"];
        $AsProxyCertificate = $ligne["AsProxyCertificate"];
       // Ne pas importer pks12;
        $imported++;

        $sline = $q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='$CommonName'");
        if (intval($sline["ID"]) > 0) {
            echo "$imported/$tot_import: Importing $CommonName Already imported ID {$sline["ID"]}...\n";
            continue;
        }

        $sql = "INSERT INTO sslcertificates (CommonName,UsePrivKeyCrt,privkey,bundle,crt,DateFrom,DateTo,UseLetsEncrypt,SquidCert,Squidkey,srca,CertificateMaxDays,CountryName,stateOrProvinceName,localityName,OrganizationName,OrganizationalUnit,CompanyName,emailAddress,AsProxyCertificate) 
        VALUES ('$CommonName',$UsePrivKeyCrt,'$privkey','$bundle','$crt','$DateFrom','$DateTo',0,'$SquidCert','$Squidkey','$srca','$CertificateMaxDays','$CountryName','$stateOrProvinceName','$localityName','$OrganizationName','$OrganizationalUnit','$CompanyName','$emailAddress','$AsProxyCertificate')";
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo "$imported/$tot_import: Importing $CommonName certificate failed $q->mysql_error\n";
            continue;
        }
        echo "$imported/$tot_import: Importing $CommonName certificate Success\n";
    }

    return true;
}