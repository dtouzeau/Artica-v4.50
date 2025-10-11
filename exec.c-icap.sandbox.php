<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.postgres.inc");
include_once("/usr/share/artica-postfix/ressources/class.squid.familysites.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

if($argv[1]=="--ksb"){KasperskyPOST($argv[2]);exit;}
if($argv[1]=="--upload"){uploaded_file($argv[2]);exit;}
if($argv[1]=="--mime"){find_mime($argv[2]);exit;}
if($argv[1]=="--queue"){scan_queue();exit;}
if($argv[1]=="--clean"){clean_db();exit;}
xstart();

function find_mime($path){
    $content_type       = mime_content_type($path);
    $size               = @filesize($path);
    echo "$path $content_type ($size bytes )\n";
}

function uploaded_file($filencoded):bool{
    $unix=new unix();
    $fsrc=base64_decode($filencoded);
    $fullpath=dirname(__FILE__)."/ressources/conf/upload/$fsrc";

    if(!is_file($fullpath)){
        $unix->ToSyslog("ERROR [$fullpath] no such file", false, "ArticaSandBox");
        return false;
    }
    $md5_file=md5_file($fullpath);
    $fname              = date("ymdHis")."-$md5_file";
    $hostname           = $unix->hostname_g();
    $content_type       = mime_content_type($fullpath);
    $srcDir             = "/home/artica/squid/sandbox";
    $URI                = "http://127.0.0.1/$fsrc";

    $f[]="RESPMOD icap://127.0.0.1:1345/sandbox ICAP/1.0";
    $f[]="Host: 127.0.0.1";
    $f[]="X-Client-IP: 127.0.0.1";
    $f[]="";
    $f[]="GET $URI HTTP/1.1";
    $f[]="Host: $hostname";
    $f[]="Content-Type: $content_type";
    @file_put_contents("$srcDir/$fname.meta",@implode("\n",$f));
    @copy($fullpath,"$srcDir/$fname");
    $unix->ToSyslog("[$fsrc]  --> $srcDir/$fname", false, "ArticaSandBox");
    @unlink($fullpath);
    $unix->ToSyslog("[$fsrc] OK pass to Sandbox structure", false, "ArticaSandBox");
    $GLOBALS["VERBOSE"]=true;
    xstart(true,true);
    return true;
}

function KasperskySandboxMime_data():array{
    include_once(dirname(__FILE__)."/ressources/class.mimes-types.inc");
    $CountOfKasperskySandboxMime    = 0;
    $KasperskySandboxMime=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskySandboxMime"));
    if(is_array($KasperskySandboxMime)){
        $CountOfKasperskySandboxMime=count($KasperskySandboxMime);
    }
    if($CountOfKasperskySandboxMime>0){return $KasperskySandboxMime;}

    return mimesandboxdefaults();
}

function KasperskySandboxMime():array{
    $MAIN=array();
    $data=KasperskySandboxMime_data();
    foreach ($data as $md5=>$content){
        $MAIN[$content]=true;
    }
    return $MAIN;
}

function mime_is_compressed($content_type){
    if($content_type=="application/x-rar"){return true;}
    if($content_type=="application/x-gzip"){return true;}
    if($content_type=="application/zip"){return true;}
    if($content_type=="application/x-7z-compressed"){return true;}
    return false;
}

function VerifCompressedFiles($tDir){
    $unix=new unix();
    $find=$unix->find_program("find");
    exec("$find $tDir 2>&1",$results);


    foreach ($results as $file_src){

        if (is_dir($file_src)) {
            if($GLOBALS["VERBOSE"]){$unix->ToSyslog("$file_src SKIP it is a directory",false, "ArticaSandBox");}
            continue;}
        $content_type       = mime_content_type($file_src);
        if($GLOBALS["VERBOSE"]){$unix->ToSyslog("Found $file_src ($content_type)",false, "ArticaSandBox");}
        if (mime_is_compressed($content_type)) {
            $time=time();
            $unc=uncompress($file_src, "$file_src-$time");
            @unlink($file_src);
            if($unc){VerifCompressedFiles("$file_src-$time");}
        }
    }

    return true;

}

function uncompress($filepath,$maindir){
    $unix=new unix();
    $content_type       = mime_content_type($filepath);
    //p7zip
    if($content_type=="application/x-rar"){
        $unrar=$unix->find_program("unrar");
        if(!is_file($unrar)) {
            $unix->ToSyslog("uncompress: unrar not found, cannot uncompress $filepath", "ArticaSandBox");
            return false;
        }
        $unix->ToSyslog("uncompress: unrar $filepath ...", "ArticaSandBox");
        @mkdir($maindir,0755,true);
        shell_exec("$unrar $filepath $maindir");
        VerifCompressedFiles($maindir);
        return true;
    }

    if($content_type=="application/x-gzip"){
        $tar=$unix->find_program("tar");
        if(!is_file($tar)) {
            $unix->ToSyslog("uncompress: tar not found, cannot uncompress $filepath", "ArticaSandBox");
            return false;
        }
        $unix->ToSyslog("uncompress: untar $filepath ...", "ArticaSandBox");
        @mkdir($maindir,0755,true);
        shell_exec("$tar xf $filepath -C $maindir/");
        VerifCompressedFiles($maindir);
        return true;
    }

    if($content_type=="application/x-7z-compressed"){
        $p7zip=$unix->find_program("7zr");
        if(!is_file($p7zip)) {
            $unix->ToSyslog("uncompress: 7zr not found, cannot uncompress $filepath", "ArticaSandBox");
            return false;
        }
        $unix->ToSyslog("uncompress: unp7zip $filepath ...", "ArticaSandBox");
        shell_exec("$p7zip x $filepath -o$maindir/");
        VerifCompressedFiles($maindir);
        return true;
    }

    if($content_type=="application/zip") {
        $unzip = $unix->find_program("unzip");
        if (!is_file($unzip)) {
            $unix->ToSyslog("uncompress: unzip not found, cannot uncompress $filepath", "ArticaSandBox");
            return false;
        }

        @mkdir($maindir, 0755, true);
        exec("$unzip \"$filepath\" -d $maindir 2>&1",$resultsunzip);

        foreach ($resultsunzip as $line){
               $line=trim($line);
               if(preg_match("#End-of-central-directory signature not found#",$line)) {
                   $unix->ToSyslog("ERROR CORRUPTED ZIP FILE", "ArticaSandBox");
                   return false;
               }
               if($GLOBALS["VERBOSE"]){ $unix->ToSyslog("uncompress: $line", "ArticaSandBox");}
        }

        VerifCompressedFiles($maindir);
        return true;
    }


return false;
}


function ToKaspersky($filepath,$infos=array()):string{
    $unix=new unix();
    $results=array();
    $rm=$unix->find_program("rm");
    $find=$unix->find_program("find");
    $EnableKasperskySandbox = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKasperskySandbox"));
    if($EnableKasperskySandbox==0){return "NONE";}
    $fname=basename($filepath);
    $content_type       = mime_content_type($filepath);
    $unix->ToSyslog("Analyze $fname/$content_type", false, "ArticaSandBox");

    if(mime_is_compressed($content_type)){
        $unix->ToSyslog("Uncompress $fname", false, "ArticaSandBox");
        $tDir="/home/artica/squid/sandbox/work/$fname";
        if(!uncompress($filepath,$tDir)){
            @unlink($filepath);
            return "CORRUPTED";
        }
        @unlink($filepath);
        if(is_file("$filepath.meta")){@unlink("$filepath.meta");}
        $results=array();

        exec("$find $tDir 2>&1",$find_results);
        foreach ($find_results as $file_src){
           if (is_dir($file_src)) {continue;}
            $sub_content_type=mime_content_type($file_src);
            if($GLOBALS["VERBOSE"]){echo "Scanning $file_src - $sub_content_type\n";}

            if (mime_is_compressed($sub_content_type)){
                @unlink($file_src);
                continue;
            }
            $file=basename($file_src);
            $Fsize=filesize($file_src);

            if ($Fsize > 62914560) {
                $unix->ToSyslog("[$file]: Warning size exceed 60MB (aborting)", false, "ArticaSandBox");
                @unlink($file_src);
                continue;
            }

            $return_back=KasperskyPOST($file_src);
            if($GLOBALS["VERBOSE"]){echo "VERBOSE: $file_src ==[$return_back]\n";}
            if($return_back == "NONE"){@unlink($file_src);continue;}
            if($return_back == "FATAL"){@unlink($file_src);continue;}
            if($return_back == "RETRY"){$results[$file_src]="RETRY";continue;}
            if($return_back == "CLEAN"){$results[$file_src]="CLEAN";@unlink($file_src);continue;}
            if($return_back == "DETECTED"){$results[$file_src]="DETECTED"; @unlink($file_src);continue;}


            @unlink($file_src);
            $unix->ToSyslog("[$file] = $return_back", false, "ArticaSandBox");
            $results[$file_src]=$return_back;
        }

        if(count($results)==0){
            if(is_dir($tDir)) {
                shell_exec("$rm -rf $tDir");
            }
            return "NONE";
        }
        return serialize($results);
    }

    $Fsize=filesize($filepath);

    if ($Fsize > 62914560) {
        $unix->ToSyslog("{warning} [".basename($filepath)."] size exceed 60MB (aborting)", false, "ArticaSandBox");
        return "NONE";
    }

    $return_back=KasperskyPOST($filepath,$infos);
    if($return_back=="NONE"){return "NONE";}
    if($return_back=="FATAL"){return "NONE";}
    $results[$filepath]=$return_back;
    return serialize($results);

}
function KasperskyASK($task_id):string{
    $unix                   = new unix();
    $EnableKasperskySandbox = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKasperskySandbox"));
    $KasperskySandboxAddr   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskySandboxAddr"));
    if($KasperskySandboxAddr==null){$EnableKasperskySandbox=0;}
    if($EnableKasperskySandbox==0){return "NONE";}
    $MAIN_URI="https://$KasperskySandboxAddr/sandbox/v1/tasks/$task_id";
    $CURLOPT_HTTPHEADER[]="Pragma: no-cache,must-revalidate";
    $CURLOPT_HTTPHEADER[]="Cache-Control: no-cache,must revalidate";
    $CURLOPT_HTTPHEADER[]="Expect:";
    $ch = curl_init($MAIN_URI);

    curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_POST,0);

    $response = curl_exec($ch);
    $errno=curl_errno($ch);

    if($errno>0){
        $curl_error=curl_error($ch);
        $unix->ToSyslog("[$task_id]: ERROR Network issue $errno $curl_error", false, "ArticaSandBox");
        return "RETRY";
    }

    $CURLINFO_HTTP_CODE=intval(curl_getinfo($ch,CURLINFO_HTTP_CODE));
    $KasperskyResults=KasperskyResults($CURLINFO_HTTP_CODE,$response);
    $unix->ToSyslog("[$task_id] Result: $KasperskyResults", false, "ArticaSandBox");
    return $KasperskyResults;

}

function KasperskyResults($CURLINFO_HTTP_CODE,$response){
    $unix=new unix();
    if($CURLINFO_HTTP_CODE==400){
        $json       = json_decode($response);
        $message    = $json->message;
        $type       = $json->type;
        echo "Error $CURLINFO_HTTP_CODE $type: $message\n";
        $unix->ToSyslog("ERROR $CURLINFO_HTTP_CODE $type: $message", false, "ArticaSandBox");
        return "FATAL";
    }

    if($CURLINFO_HTTP_CODE==503){
        echo "Error $CURLINFO_HTTP_CODE Retry again\n";
        $unix->ToSyslog("ERROR: $CURLINFO_HTTP_CODE Performance: Server unavailable. Try to connect to a different server or try again later.", false, "ArticaSandBox");
        return "RETRY";
    }

    if($CURLINFO_HTTP_CODE==504){
        echo "Error $CURLINFO_HTTP_CODE Retry again\n";
        $unix->ToSyslog("ERROR: $CURLINFO_HTTP_CODE Performance: Server timeout. Try to connect to a different server or try again later.", false, "ArticaSandBox");
        return "RETRY";
    }

    if($CURLINFO_HTTP_CODE==201){
        $json       = json_decode($response);
        $task_id    = $json->task_id;
        echo "Success $CURLINFO_HTTP_CODE Accepted: $task_id\n";
        $unix->ToSyslog("OK $CURLINFO_HTTP_CODE Accepted:TASK ID $task_id", false, "ArticaSandBox");
        return $task_id;
    }
    if($CURLINFO_HTTP_CODE==200){
        $json       = json_decode($response);
        $result     = strtolower(trim($json->result));
        if($result == "not found"){
            $unix->ToSyslog("OK Already scanned - CLEAN", false, "ArticaSandBox");
            return "CLEAN";
        }
        if( $result == "found"){
            $unix->ToSyslog("MALICIOUS FILE", false, "ArticaSandBox");
            return "DETECTED";
        }

        if($result =="processing"){
            return "RETRY";
        }

    }

    $unix->ToSyslog("Unknown error $response/$CURLINFO_HTTP_CODE", false, "ArticaSandBox");
    return "RETRY";


}

function KasperskyPOST($filepath,$infos=array()):string{
    $unix                   = new unix();
    $KasperskySandboxMime   = KasperskySandboxMime();
    $EnableKasperskySandbox = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKasperskySandbox"));
    $KasperskySandboxAddr   = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskySandboxAddr"));
    if($KasperskySandboxAddr==null){$EnableKasperskySandbox=0;}
    $flog=basename($filepath);


    $content_type       = mime_content_type($filepath);

    if(isset($infos["content_type"])){
        $content_type       = $infos["content_type"];
    }

    echo "$filepath - $content_type\n";


    if($EnableKasperskySandbox==0){
        return "NONE";
    }



    if(!isset($KasperskySandboxMime[$content_type])){
        $unix->ToSyslog("[$flog]: NONE $content_type not accepted", false, "ArticaSandBox");
        return "NONE";
    }

    $KasperskySandboxAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KasperskySandboxAddr"));
    if(!is_file($filepath)){
        $unix->ToSyslog("[$flog] ERROR: Cannot post $filepath (no such file)", false, "ArticaSandBox");
        return "NONE";
    }
    if($KasperskySandboxAddr==null){
        $unix->ToSyslog("[$flog] ERROR: Cannot post $filepath (no target defined)", false, "ArticaSandBox");
        return "NONE";
    }

    $MAIN_URI="https://$KasperskySandboxAddr/sandbox/v1/tasks";
    $CURLOPT_HTTPHEADER[]="Pragma: no-cache,must-revalidate";
    $CURLOPT_HTTPHEADER[]="Cache-Control: no-cache,must revalidate";
    $CURLOPT_HTTPHEADER[]="Expect:";
    $ch = curl_init($MAIN_URI);
    $cFile = curl_file_create($filepath);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array( 'sample' => $cFile ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $errno=curl_errno($ch);

    if($errno>0){
        $curl_error=curl_error($ch);
        echo "KasperskyPOST: [$flog] Network Error $errno $curl_error\n";
        $unix->ToSyslog("ERROR Network issue $errno $curl_error", false, "ArticaSandBox");
        return "RETRY";
    }

    $CURLINFO_HTTP_CODE=intval(curl_getinfo($ch,CURLINFO_HTTP_CODE));

    $KasperskyResults=KasperskyResults($CURLINFO_HTTP_CODE,$response);
    $unix->ToSyslog("[$flog] Result: $KasperskyResults", false, "ArticaSandBox");
    return $KasperskyResults;
}

function scan_queue():bool{
    $q          = new postgres_sql();
    $unix       = new unix();
    $squidfam   = new squid_familysite();
    $srcDir     = "/home/artica/squid/sandbox";
    $queueDir   = "$srcDir/queue";
    $hostname   = $unix->hostname_g();
    if(!$q->TABLE_EXISTS("cicap_sandbox")){$q->create_sandbox_table();}

    $prefix_sql ="INSERT INTO webfilter (zDate,website,category,rulename,public_ip,blocktype,why,hostname,client,PROXYNAME,rqs)";

    if(!is_dir($queueDir)){ return false;}
    echo "Scanning $queueDir\n";
    if (!$handle = opendir($queueDir)) {
        echo "$queueDir No such dir...\n";
        return false;
    }

    $SandBoxMaxRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SandBoxMaxRetentionTime"));
    if($SandBoxMaxRetentionTime==0){$SandBoxMaxRetentionTime=180;}


    while (false !== ($file = readdir($handle))) {
        if ($file == ".") {continue;}
        if ($file == "..") {continue;}
        $metafile=basename(str_replace(".sandbox",".meta",$file));
        $metatdata=basename(str_replace(".sandbox","",$file));

        $src_dbx    = "$queueDir/$file";
        $src_data   = "$srcDir/$metatdata";
        $src_meta   = "$srcDir/$metafile";



        $file_sandbox = "$queueDir/$file";
        if($GLOBALS["VERBOSE"]){echo "[scan_queue]: [$metafile]: $file_sandbox\n";}
        $ligne=$q->mysqli_fetch_array("SELECT * FROM cicap_sandbox WHERE workname='$metafile'");
        $id=intval($ligne["id"]);
        $uri=$squidfam->GetFamilySites($ligne["uri"]);
        $ipaddr=$ligne["ipaddr"];


        if($id==0){
            $unix->ToSyslog("ERROR $metafile did not have any entry in database",false, "ArticaSandBox");
            @unlink($src_dbx);
            if(is_file($src_data)){@unlink($src_data);}
            if(is_file($src_meta)){@unlink($src_meta);}
            continue;
        }

        $ftime=$unix->file_time_min($src_dbx);
        if($ftime>$SandBoxMaxRetentionTime) {
            $restime = time();
            $q->QUERY_SQL("UPDATE cicap_sandbox SET sbxcode='TIMEOUT',restime='$restime' WHERE id='$id'");
            if (!$q->ok) {$unix->ToSyslog("ERROR $q->mysql_error", false, "ArticaSandBox");}
            @unlink($src_dbx);
            if (is_file($src_data)) {@unlink($src_data);}
            if (is_file($src_meta)) {@unlink($src_meta);}
            continue;
        }



        $serialized=trim(file_get_contents($src_dbx));
        echo "$src_dbx $metafile = {$ligne["id"]}\n";
        echo "---------------- \n $serialized \n --------------\n";
        $meta_array=unserialize($serialized);
        if(!is_array($meta_array)){
            $unix->ToSyslog("ERROR $metafile corrupted index file",false, "ArticaSandBox");
            $q->QUERY_SQL("DELETE FROM cicap_sandbox WHERE id='$id'");
            @unlink($src_dbx);
            if(is_file($src_data)){@unlink($src_data);}
            if(is_file($src_meta)){@unlink($src_meta);}
            continue;
        }
        $FINAL=null;
        $FINAL_ARRAY=$meta_array;
        foreach ($meta_array as $filepath=>$results){
            if($GLOBALS["VERBOSE"]){echo "[scan_queue]: [$filepath]: Cached result: [$results]\n";}

            if($results=="DETECTED"){
                $restime=time();
                $unix->ToSyslog("OK Stamp $id to Infected",false, "ArticaSandBox");
                $q->QUERY_SQL("UPDATE cicap_sandbox SET sbxcode='DETECTED',restime='$restime' WHERE id='$id'");
                if(!$q->ok){$unix->ToSyslog("ERROR $q->mysql_error",false, "ArticaSandBox");}
                $zDate=date("Y-m-d H:i:s");
                $FINAL="('$zDate','$uri','Generic - SandBox','SandBox','127.0.0.1','Security issue','VIRUS DETECTED','$ipaddr','-','$hostname','1')";
                $sql=$prefix_sql." VALUES ".$FINAL;
                $q->QUERY_SQL($sql);
                if(!$q->ok){$unix->ToSyslog("ERROR $q->mysql_error",false, "ArticaSandBox");}

                @unlink($src_dbx);
                if(is_file($filepath)){@unlink($filepath);}
                if(is_file($src_data)){@unlink($src_data);}
                if(is_file($src_meta)){@unlink($src_meta);}
                continue;
            }

            if($results=="CLEAN"){
                unset($FINAL_ARRAY[$filepath]);
                if(is_file($filepath)){@unlink($filepath);}
                $FINAL=$results;
                continue;
            }
            if($results=="RETRY"){
                $FINAL_ARRAY[$filepath]=ToKaspersky($filepath);
                continue;
            }

            $results=KasperskyASK($results);
            if($results =="NONE"){continue;}
            if($results == "CORRUPTED"){continue;}

            if($results=="CLEAN"){
                unset($FINAL_ARRAY[$filepath]);
                if(is_file($filepath)){@unlink($filepath);}
                $FINAL=$results;
                continue;
            }
            if($results=="DETECTED"){$FINAL_ARRAY[$filepath]="DETECTED";}


        }

        if(count($FINAL_ARRAY)==0){
            $restime=time();
            $unix->ToSyslog("OK Stamp $id to $FINAL",false, "ArticaSandBox");
            $q->QUERY_SQL("UPDATE cicap_sandbox SET sbxcode='$FINAL',restime='$restime' WHERE id='$id'");
            @unlink($src_dbx);
            if(is_file($src_data)){@unlink($src_data);}
            if(is_file($src_meta)){@unlink($src_meta);}
            continue;
        }

        @file_get_contents($src_dbx,serialize($FINAL_ARRAY));
    }

    return true;

}

function xstart($aspid=false,$nottl=false):bool{
    $pidfile    = "/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $srcDir     = "/home/artica/squid/sandbox";
    $workDir    = "/home/artica/squid/sandbox/work";
    $queueDir   = "/home/artica/squid/sandbox/queue";
    $q          = new postgres_sql();
    $unix       = new unix();

    if(!$aspid) {
        $pid = $unix->get_pid_from_file($pidfile);
        if ($unix->process_exists($pid, basename(__FILE__))) {
            $time = $unix->PROCCESS_TIME_MIN($pid);
            echo "Already Artica task running PID $pid since {$time}mn\n";
            return false;
        }
    }

    @file_put_contents($pidfile,getmypid());

    if(!is_dir($queueDir)){@mkdir($queueDir,0755,true);}
    if(!$q->create_sandbox_table()){
        squid_admin_mysql(0,"SandBox SQL database error",$q->mysql_error,__FILE__,__LINE__);
        return false;
    }
    if($nottl){
        $unix->ToSyslog("Scanning $srcDir","ArticaSandBox");
    }
    echo "Scanning $srcDir\n";
    if (!$handle = opendir($srcDir)) {return false;}

    $fields[]="md5file";
    $fields[]="workname";
    $fields[]="filetime";
    $fields[]="filesize";
    $fields[]="uri";
    $fields[]="content_type";
    $fields[]="ipaddr";
    $fields[]="username";
    $fields[]="sandboxsrv";
    $fields[]="posttime";
    $fields[]="restime";
    $fields[]="resultfile";
    $fields[]="sbxcode";


    while (false !== ($file = readdir($handle))) {
        if ($file == ".") {continue;}
        if ($file == "..") {continue;}
        $file_src       = "$srcDir/$file";
        $content_type   = null;
        $sandboxsrv     = null;

        if (is_dir($file_src)) {continue;}


        if(!preg_match("#^([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})-.*?\.meta$#",$file,$re)){continue;}
        $file_content=$srcDir."/".str_replace(".meta","",$file);
        $queue_file="$queueDir/".str_replace(".meta",".sandbox",$file);


        if(!$nottl) {
            $timefile = $unix->file_time_min($file_src);
            if ($timefile < 1) {
                if($GLOBALS["VERBOSE"]){$unix->ToSyslog("$file_src = {$timefile}mn, aborting",
                    false, "ArticaSandBox");}
                continue;
            }
            if(is_file($file_content)){
                $timefile = $unix->file_time_min($file_src);
                if ($timefile < 1) {
                    if($GLOBALS["VERBOSE"]){$unix->ToSyslog("$file_content = {$timefile}mn, aborting",
                        false, "ArticaSandBox");}
                    continue;
                }
            }
        }

        if($GLOBALS["VERBOSE"]){$unix->ToSyslog("Scanning $file_src",false, "ArticaSandBox");}

        if(is_file($queue_file)){
            if($GLOBALS["VERBOSE"]){$unix->ToSyslog("$queue_file exists, SKIP it",false, "ArticaSandBox");}
            @unlink($file_content);
            continue;
        }



        if(!is_file($file_content)){
            if($GLOBALS["VERBOSE"]){$unix->ToSyslog("$file_content not exists, aborting $file_src",false, "ArticaSandBox");}
            echo "$file_content  no such file..\n";
            @unlink($file_src);
            continue;
        }

        $md5file            = md5_file($file_content);
        if($GLOBALS["VERBOSE"]){$unix->ToSyslog("$file_content == $md5file",false, "ArticaSandBox");}

        if($nottl){$q->mysqli_fetch_array("DELETE from cicap_sandbox WHERE md5file ='$md5file'");}

        $cacheline=$q->mysqli_fetch_array("SELECT id,sbxcode from cicap_sandbox WHERE md5file ='$md5file'");

        if(intval($cacheline["id"])>0){
            $sbxcode="WORKING";
            if($cacheline["sbxcode"]<>"WORKING") {
                $unix->ToSyslog("$file_content ($md5file) already scanned, skipping",false,"ArticaSandBox");
                @unlink($file_content);
                continue;
            }
            $q->QUERY_SQL("DELETE FROM cicap_sandbox WHERE id='{$cacheline["id"]}'");
        }

        $content_type       = mime_content_type($file_content);
        $meta_infos         = meta_infos($file_src);
        if(isset($meta_infos["content_type"])){
            $content_type       = $meta_infos["content_type"];
        }

        $filesize = filesize($file_content);
        if($GLOBALS["VERBOSE"]){$unix->ToSyslog("$file_content == $content_type {$filesize}Bytes",false, "ArticaSandBox");}

        if($filesize==0){
            $unix->ToSyslog("ERROR $file_src 0 size (aborting)",false, "ArticaSandBox");
            @unlink($file_content);
            @unlink($file_src);
            continue;
        }

        $results=ToKaspersky($file_content);
        if($GLOBALS["VERBOSE"]){$unix->ToSyslog("$file_content == [$results]","ArticaSandBox");}

        if($results=="CORRUPTED"){
            $uri        = $meta_infos["uri"];
            $unix->ToSyslog("$uri $content_type: CORRUPTED",false, "ArticaSandBox");
            @unlink($file_src);
            @unlink($file_content);
            continue;
        }

        if($results<>"NONE"){
            $sandboxsrv="KSB";
            $sbxcode="WORKING";
            @file_put_contents($queue_file,$results);
            if(!is_file($queue_file)){
                $unix->ToSyslog("ERROR unable to save $queue_file ( permission denied or no space left )");
            }
        }

        if($sandboxsrv==null){
            $uri        = $meta_infos["uri"];
            $unix->ToSyslog("$uri $content_type: No provider accepts SandBoxing",false, "ArticaSandBox");
            @unlink($file_src);
            @unlink($file_content);
            $sandboxsrv="NONE";
            $sbxcode="ABORTED";
        }

        $uri        = $meta_infos["uri"];
        $ipaddr     = $meta_infos["srcip"];
        $year       = "20{$re[1]}";
        $month      = $re[2];
        $day        = $re[3];
        $hour       = $re[4];
        $min        = $re[5];
        $secs       = $re[6];
        $xtime      = strtotime("$year-$month-$day $hour:$min:$secs");
        $posttime   = time();

        $fieldsdta[]="'$md5file'";
        $fieldsdta[]="'$file'";
        $fieldsdta[]="'$xtime'";
        $fieldsdta[]="'$filesize'";
        $fieldsdta[]="'$uri'";
        $fieldsdta[]="'$content_type'";
        $fieldsdta[]="'$ipaddr'";
        $fieldsdta[]="'-'";
        $fieldsdta[]="'$sandboxsrv'";
        $fieldsdta[]="'$posttime'";
        $fieldsdta[]="'0'";
        $fieldsdta[]="'0'";
        $fieldsdta[]="'$sbxcode'";

        $sql="INSERT INTO cicap_sandbox (".@implode(", ",$fields).") VALUES (".@implode(", ",$fieldsdta).")";

        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "ERROR $q->mysql_error\n";continue;}
        $unix->ToSyslog("Saving $queue_file in database");
    }
    scan_queue();
return true;
}

function   clean_db(){
    $unix                       =new unix();
    $rm                         = $unix->find_program("rm");
    $workDir                    = "/home/artica/squid/sandbox/work";
    $SandBoxMaxRetentionTime    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SandBoxMaxRetentionTime"));
    if($SandBoxMaxRetentionTime==0){$SandBoxMaxRetentionTime=180;}

    $SandBoxMaxEventRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SandBoxMaxEventRetentionTime"));
    if($SandBoxMaxEventRetentionTime==0){$SandBoxMaxEventRetentionTime=7;}

    $dateToDelete = strtotime("-{$SandBoxMaxEventRetentionTime} day");
    $q=new postgres_sql();
    $q->QUERY_SQL("DELETE FROM cicap_sandbox WHERE posttime < $dateToDelete");

    if(!is_dir($workDir)){@mkdir($workDir,0755,true);}
    echo "Scanning $workDir\n";
    if (!$handle = opendir($workDir)) {return false;}



    while (false !== ($file = readdir($handle))) {
        if ($file == ".") {
            continue;
        }
        if ($file == "..") {
            continue;
        }
        $subdir = "$workDir/$file";
        $time = $unix->file_time_min("$subdir");
        if($time<$SandBoxMaxRetentionTime){continue;}
        shell_exec("$rm -rf $subdir");
    }

    return true;

}

function meta_infos($fpath):array{
    $main["content_type"]=null;
    $main["content_lenght"]=0;
    $main["uri"]=null;

    $f=explode("\n",@file_get_contents($fpath));
    foreach ($f as $line){
        $line=trim($line);

        if($line==null){continue;}
        if(preg_match("#X-Client-IP:(.+)#i",$line,$re)){
            $main["srcip"]=trim($re[1]);
            continue;
        }
        if(preg_match("#^(GET|POST)+\s+(.*)\s+[a-zA-Z]+\/#i",$line,$re)){
            $main["uri"]=$re[2];
            continue;
        }

        if(preg_match("#^Content-Type:\s+(.+)#i",$line,$re)){
            $main["content_type"]=trim($re[1]);
            continue;
        }

        if(preg_match("#^Content-Length:\s+([0-9]+)#i",$line,$re)){
            $main["content_lenght"]=trim($re[1]);
            continue;
        }

    }

    return $main;

}





