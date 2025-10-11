<?php
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.categories.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if($argv[1]=="--upload"){upload_all_next($argv[2]);exit;}
if($argv[1]=="--schedule"){schedule();exit;}
if($argv[1]=="--single"){compile_single_category($argv[2]);exit;}
if($argv[1]=="--index"){create_index();exit;}
if($argv[1]=="--cleand"){clean_doublons_danger();exit;}
if($argv[1]=="--smooth"){compile_new_categories();exit;}

echo "Upload all categories...\n";
upload_all();
if(is_file("/etc/init.d/firehol")){shell_exec("/etc/init.d/firehol start");}

function build_progress($text,$pourc){
	$cachefile=PROGRESS_DIR."/ufdbcat.compile.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]},{$pourc}% $text...\n";
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_single($text,$pourc){
	$cachefile=PROGRESS_DIR."/ufdbguard.compile.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]},{$pourc}% $text...\n";
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function schedule(){
    $unix=new unix();
    $unix->Popuplate_cron_make("categories-ftp-backup","0 */4 * * *",basename(__FILE__));
    system("/etc/init.d/cron reload");
	
}
function upload_all(){
    $BackupCategoriesFTPServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupCategoriesFTPServer");
    if($BackupCategoriesFTPServer==null){return false;}

    $ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    echo "Manage Officials Categories = $ManageOfficialsCategories\n";
    $sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 ORDER by category_id";
    if($ManageOfficialsCategories==1){
        $sql="SELECT * FROM personal_categories WHERE official_category=1 order by category_id";
    }

    $q=new postgres_sql();
    $unix=new unix();
	$results=$q->QUERY_SQL($sql);
	$total=pg_num_rows($results);



	$TMP_PATH="/home/artica/categories_backup_tmp";
	$FINAL_PATH="/home/artica/categories_backup";
    $FINAL_INDEX=$FINAL_PATH."/history.db";
	$INDEXES=unserialize(@file_get_contents($FINAL_INDEX));
	unset($INDEXES["UPLOAD"]);

    if(!is_dir($TMP_PATH)){@mkdir($TMP_PATH,0655,true);}
    if(!is_dir($FINAL_PATH)){ @mkdir($FINAL_PATH,0777,true); }

    $i=0;
	while ($ligne = pg_fetch_assoc($results)) {
	    $i++;
	    $prc=$i/$total;
	    $prc=round($prc*100);
        if($prc>90){$prc=90;}
	    $category_id=$ligne["category_id"];
	    $categoryname=$ligne["categoryname"];
	    $categorytable=$ligne["categorytable"];

	    $ref[]="$category_id;$categoryname";

        $unix->ToSyslog("[INFO]: Builder:Compiling $categoryname",false,"categories-update");
        build_progress_single("{compile}: $categoryname",$prc);
        if(preg_match("#reserved#",$categoryname)){continue;}
        @chmod("/home/artica/categories_backup_tmp",0777);
        echo "{$prc}%) $categoryname\t$category_id\t$categorytable\n";
        if(!table_to_text("$TMP_PATH/$category_id.txt",$categorytable)){
            return false;
        }
        $new_md5_file=md5_file("$TMP_PATH/$category_id.txt");
        $new_md5_size=filesize("$TMP_PATH/$category_id.txt");
        $new_md5_sizeKB=$new_md5_size/1024;
        $new_md5_sizeMB=round($new_md5_sizeKB/1024,2);
        $unix->ToSyslog("[INFO]: Builder:$TMP_PATH/$category_id.txt $new_md5_file ($new_md5_sizeMB)",false,"categories-update");
        if(!isset($INDEXES[$category_id])){$INDEXES[$category_id]=0;}
        if($INDEXES[$category_id]==$new_md5_file){
            echo "$categoryname\t$category_id SKIP, SAME MD5\n";
            @unlink("$TMP_PATH/$category_id.txt");
            $unix->ToSyslog("[INFO]: Builder:SKIP $new_md5_file not changed since last updates",false,"categories-update");
            continue;
        }
        $unix->ToSyslog("[INFO]: Builder: Compressing $categoryname",false,"categories-update");
        echo "Compressing $categoryname\n";
        if(!$unix->compress("$TMP_PATH/$category_id.txt","$FINAL_PATH/$category_id.gz")){
            echo "{$prc}%) $categoryname\t$category_id Compression failed\n";
            @unlink("$TMP_PATH/$category_id.txt");
            @unlink("$FINAL_PATH/$category_id.gz");
            $unix->ToSyslog("[ERROR]: Builder: Compressing $categoryname failed",false,"categories-update");
            continue;
        }
        $number_of_lines=$unix->COUNT_LINES_OF_FILE("$TMP_PATH/$category_id.txt");
        @unlink("$TMP_PATH/$category_id.txt");
        $INDEXES["UPLOAD"][]="$FINAL_PATH/$category_id.gz";
        $md5gz=md5_file("$FINAL_PATH/$category_id.gz");
        $filesize=@filesize("$FINAL_PATH/$category_id.gz");
        $time=time();
	    $line="$categoryname\t$category_id.gz\t$number_of_lines\t$filesize\t$md5gz\t$new_md5_file\t$time";
        $INDEXES[$category_id]=$new_md5_file;
        echo $line."\n";
        $index_array[]=$line;
        build_progress_single("{compile}: $categoryname {done}",$prc);
	}

	@file_put_contents($FINAL_INDEX,serialize($INDEXES));
	@file_put_contents("$FINAL_PATH/index.txt",@implode("\n",$index_array));
	@file_put_contents("$FINAL_PATH/reference.txt",@implode("\n",$ref));

	if(!isset($INDEXES["UPLOAD"])){
        $INDEXES["UPLOAD"]=0;
    }

    if(count($INDEXES["UPLOAD"])==0){
        echo "No new update to upload...\n";
        build_progress_single("{done}",100);
        return true;
    }

    if(upload_all_next()){squid_admin_mysql(2,"Success backup ".count($INDEXES["UPLOAD"])." categories",null,__FILE__,__LINE__);}
    else{
        squid_admin_mysql(0,"Failed backup categories",$GLOBALS["upload_all_next_E"],__FILE__,__LINE__);
    }

}

function upload_all_next($filename=null){
    $FINAL_PATH="/home/artica/categories_backup";
    $FINAL_INDEX=$FINAL_PATH."/history.db";


    if($filename<>null){
        ftp_upload("$FINAL_PATH/index.txt");
        ftp_upload("$FINAL_PATH/reference.txt");

    }

    $INDEXES=unserialize(@file_get_contents($FINAL_INDEX));
    $BackupCategoriesFTPServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupCategoriesFTPServer");

    foreach ($INDEXES["UPLOAD"] as $FileToUpload){
        build_progress_single("{uploading} $FileToUpload",95);
        echo "Uploading: $FileToUpload\n";
        if(!ftp_upload($FileToUpload)){
            $GLOBALS["upload_all_next_E"]="Failed backup categories $FileToUpload to $BackupCategoriesFTPServer";
            squid_admin_mysql(0,"Failed backup categories $FileToUpload to $BackupCategoriesFTPServer",null,__FILE__,__LINE__);
            return false;
        }
    }

    ftp_upload("$FINAL_PATH/index.txt");
    ftp_upload("$FINAL_PATH/reference.txt");
    return true;
}


function ftp_upload($localfile){
    $localfile_src=$localfile;
    $basename=basename($localfile);
    $BackupCategoriesFTPServer=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupCategoriesFTPServer");
    $BackupCategoriesFTPUser=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupCategoriesFTPUser");
    $BackupCategoriesFTPPassword=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupCategoriesFTPPassword");


    if(is_file("/etc/init.d/firehol")){shell_exec("/etc/init.d/firehol stop 2>&1");}
    $ch = curl_init();
    $fp = fopen($localfile, 'r');
    curl_setopt($ch, CURLOPT_URL, "ftp://$BackupCategoriesFTPUser:$BackupCategoriesFTPPassword@$BackupCategoriesFTPServer/$basename");
    curl_setopt($ch, CURLOPT_UPLOAD, 1);
    curl_setopt($ch, CURLOPT_INFILE, $fp);
    curl_setopt($ch, CURLOPT_FTPPORT,"-");
    curl_setopt($ch, CURLOPT_FTP_USE_EPRT, true);
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localfile));
    echo "$BackupCategoriesFTPServer: Connecting....\n";

    curl_exec ($ch);

    echo "$BackupCategoriesFTPServer: Connected....\n";
    $error_no = curl_errno($ch);
    curl_close ($ch);
    if ($error_no == 0) {
        echo "$localfile File uploaded successfully\n";
        return true;
    }
    $error_str=curl_strerror($error_no);
    echo "$localfile ($error_no) $error_str\n";

    if($error_no==25){
        if(!isset($GLOBALS["$localfile/Err25"])) {
            squid_admin_mysql(1,"[$BackupCategoriesFTPServer] Failed to upload via FTP(Err25) $localfile Restart in 5seconds",null,__FILE__,__LINE__);
            sleep(5);
            $GLOBALS["$localfile/Err25"]=True;
            ftp_upload($localfile_src);
        }

    }


    squid_admin_mysql(0,"[$BackupCategoriesFTPServer] Failed to upload via FTP $localfile ($error_no) $error_str",null,__FILE__,__LINE__);
    echo "$localfile Failed upload\n";
    return false;
}


function table_to_text($path,$tablename){
    $unix=new unix();
    $q=new postgres_sql();
    $sql="COPY $tablename TO '$path'";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        $unix->ToSyslog("[ERROR]: Builder:Compiling $tablename failed $q->mysql_error",false,"categories-update");
        echo $q->mysql_error."\n";
        build_progress_single("{compile}: $tablename {failed} L.".__LINE__,110);
        @unlink($path);
        return false;
    }

    return true;

}



function cleanTable($tablename){

	$q=new postgres_sql();
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%;%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%)%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%{%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%}%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%(%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%!%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%@%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%§%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%#%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%^%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%¥%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%<%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%>%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%,%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%|%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%$%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%=%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename LIKE '%-moz-transition:%'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.addr'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.cdir'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.com'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='..com'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='*.com'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.co.uk'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='info'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='biz'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='cdir'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='addr'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='fr'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='xyz'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='pw'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='co.uk'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='co'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='.co'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='www.co'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='gs'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='paris'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='tools'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='life'");
	$q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='online'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='de.tc'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='da.ru'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='org'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='fr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='be'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='es'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='pl'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='net'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='biz'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='de'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.id'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.in'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.ir'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.jp'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.org.ar'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.ru'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.th'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ac.ug'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='it'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='us'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='ru'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='nl'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='kr'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='cl'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='com.bo'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='family'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='network'");
    $q->QUERY_SQL("DELETE FROM $tablename WHERE sitename ='la'");




}
function temporay_work(){
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $tempdir="/home/artica/categories_works";
    if(is_dir($tempdir)){system("$rm -rf $tempdir");}
    @mkdir($tempdir,0777,true);
    @chown($tempdir,"ArticaStats");
    @chgrp($tempdir,"ArticaStats");
    return $tempdir;
}

function compile_single_category($category_id,$prc=0){
	$unix=new unix();
	$percent_org=$prc;
	$chown=$unix->find_program("chown");
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	
	if(!is_file($ufdbGenTable)){
		build_progress_single("{compile}: missing compilator...",110);
		if($prc>0){build_progress("{compressing} missing compilator...",$prc);}
		return false;
	}
	
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT categoryname,categorytable FROM personal_categories WHERE category_id='$category_id'"));
	$table=$ligne["categorytable"];
	$categoryname=$ligne["categoryname"];
	
	if($table==null){
		if($prc>0){build_progress("{category} $category_id, no table found",$prc);}
		build_progress_single("{category} $category_id, no table found",110);
		return false;
	}
	
	if(!$q->TABLE_EXISTS($table)){
		if($prc>0){build_progress("{category} $category_id, $table, no such table",$prc);}
		build_progress_single("{category} $category_id, $table, no such table",110);
		return false;
		
	}
	
	$timestart=time();
    $tempdir=temporay_work();
	$CacheDatabase=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategoriesDatabase"));

	$NICE=EXEC_NICE();
	$items_sql=$q->COUNT_ROWS_LOW($table);
	$tmp="$tempdir/$category_id.txt";
	if(is_file($tmp)){@unlink($tmp);}
	
	if($prc>0){build_progress("{cleaning}: $categoryname 10%",$prc);}
	build_progress_single("{cleaning}: $categoryname",25);
	cleanTable($table);
	$items_sql=$q->COUNT_ROWS_LOW($table);
	if($items_sql==$CacheDatabase[$category_id]){
		if($prc>0){build_progress("{nothing_to_do}: $categoryname (100%)",$prc);}
		echo "[$category_id]: $categoryname table $table items:$items_sql -> Skipping\n";
		build_progress_single("{nothing_to_do}: $categoryname",100);
		return true;
	}
	if($prc>0){build_progress("{exporting}: $categoryname (20%)",$prc);}
	build_progress_single("{exporting}: $categoryname",25);
	$sql="COPY \"$table\" TO '$tmp'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error."\n";
		if($prc>0){build_progress("{compile}: $categoryname {failed} L.".__LINE__,$prc);}
		build_progress_single("{compile}: $categoryname {failed} L.".__LINE__,110);
		@unlink($tmp);
		return;
	}
	
	build_progress_single("{compiling}: Transfert $categoryname",50);
	if($prc>0){build_progress("{compiling}: Transfert $categoryname (50%)",$prc);}
	if(!transfert_file($category_id,$tmp,$categoryname)){
		build_progress_single("{compile}: Transfert $categoryname {failed} L.".__LINE__,110);
		if($prc>0){build_progress("{compiling}: $categoryname Transfert $categoryname {failed} L.".__LINE__,$prc);}
		@unlink($tmp);
		return;
	}
	
	build_progress_single("{injecting}: ufdbGentable $categoryname",60);
	if($prc>0){build_progress("{injecting}: ufdbGentable $categoryname (60%)",$prc);}
	$squidguard_dir="/var/lib/squidguard/$category_id";
	if(!is_file("$squidguard_dir/urls")){@touch("$squidguard_dir/urls");}
	$ctx=array();
	$ctx[]=$NICE;
	$ctx[]=$ufdbGenTable;
	$ctx[]="-n -q -Z -W -t $category_id";
	$ctx[]="-d /var/lib/squidguard/$category_id/domains";
	$ctx[]="-u /var/lib/squidguard/$category_id/urls";
	$ctx[]=">/dev/null 2>&1";
	system(@implode(" ", $ctx));	
	
	if($prc>0){build_progress("{injecting}: $categoryname {done}",$prc);}
	build_progress_single("{injecting}: $categoryname {done}",80);
	$took=distanceOfTimeInWords($timestart,time(),true);
	squid_admin_mysql(2,"{success} {compiling} {category} $categoryname ($took)",null,__FILE__,__LINE__);
	
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$php=$unix->LOCATE_PHP5_BIN();
	
	if(is_dir("/var/lib/ufdbartica/$category_id")){
		shell_exec("$rm -rf /var/lib/ufdbartica/$category_id");
	}
	@mkdir("/var/lib/ufdbartica/$category_id",0755,true);
	system("$cp -rfvd /var/lib/squidguard/$category_id/* /var/lib/ufdbartica/$category_id/");
	
	$q->QUERY_SQL("UPDATE personal_categories SET compiledate=".time()." WHERE category_id=$category_id");
	if($prc==0){if(is_file("/etc/init.d/ufdbcat")){system("/etc/init.d/ufdbcat reload");}}
	
	if($prc==0){
		if(is_file("/etc/init.d/dnsfilterd")){
			system("$php /usr/share/artica-postfix/exec.dnsfilterd.php --reload");
		}
	}
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ManageOfficialsCategoriesDatabase", serialize($CacheDatabase));
	

	
	$UfdbCatsUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUpload"));
	if($UfdbCatsUpload==1){
		build_progress_single("{create_index}: $categoryname",90);
		if($prc>0){build_progress("{create_index}: $categoryname...(90%)",$prc);}
		create_index_for_upload($category_id,90,$prc);
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		if($prc==0){shell_exec("$nohup $php /usr/share/artica-postfix/exec.upload.categories.php >/dev/null 2>&1 &");}
	}
	
	build_progress_single("{compiling}: $categoryname {success}",100);
	if($prc>0){build_progress("{compiling}: $categoryname {success} (100%)",$prc);}
	return true;
	
}

function create_index_for_upload($category_id,$percent,$prc=0){
	$percent_org=$percent;
	build_progress_single("Prepare $category_id for repository (10%)",$percent);
	if($prc>0){build_progress("Prepare $category_id for repository (10%)",$prc);}
	
	
	$TEMPDIR="/home/artica/webfiltering/temp-upload";
	if(!is_dir($TEMPDIR)){@mkdir($TEMPDIR,0755,true);}
	$SourceDatabase="/var/lib/squidguard/$category_id/domains.ufdb";
	$DestinationDatabase="$TEMPDIR/$category_id.gz";
	$DestinationIndexFile="$TEMPDIR/$category_id.txt";
	if(!is_file($SourceDatabase)){return ;}
	$unix=new unix();
	$q=new postgres_sql();
	$ligne=pg_fetch_array($q->QUERY_SQL("SELECT * FROM personal_categories WHERE category_id='$category_id'"));
	$table=$ligne["categorytable"];
	$categoryname=$ligne["categoryname"];
	$items_sql=$q->COUNT_ROWS_LOW($table);
	if(is_file($DestinationDatabase)){@unlink($DestinationDatabase);}
	if(is_file($DestinationIndexFile)){@unlink($DestinationIndexFile);}
	$INDEX_ARRAY["TIME"]=time();
	$INDEX_ARRAY["category_id"]=$category_id;
	$INDEX_ARRAY["ELEMENTS"]=$items_sql;
	$INDEX_ARRAY["MD5"]=md5_file($SourceDatabase);
	$INDEX_ARRAY["official_category"]=$ligne["official_category"];
	$INDEX_ARRAY["free_category"]=$ligne["free_category"];

	build_progress_single("{compressing} (20%)",$percent);
	build_progress("{compressing} (20%)",$percent);
	if($prc>0){build_progress("{compressing} $category_id (20%)",$prc);}
	
	
	$unix->compress($SourceDatabase, $DestinationDatabase);
	$INDEX_ARRAY["MD5GZ"]=md5_file($DestinationDatabase);
	@file_put_contents($DestinationIndexFile, base64_encode(serialize($INDEX_ARRAY)));
	build_progress_single("{compressing} {done} (100%)",$percent);
	if($prc>0){build_progress("{compressing} $category_id {done} (100%)",$prc);}
	
}

function compile_new_categories(){
	$q=new postgres_sql();
	$unix=new unix();
	$chown=$unix->find_program("chown");
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	build_progress("{compile_all_categories}",5);
	$ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
	
	if(!$q->FIELD_EXISTS("personal_categories", "compilerows")){
		$q->QUERY_SQL("alter table personal_categories add column if not exists compilerows bigint;");
		if(!$q->ok){echo $q->mysql_error;}
	}
	if(!$q->FIELD_EXISTS("personal_categories", "compiledate")){
		$q->QUERY_SQL("alter table personal_categories add column if not exists compiledate bigint;");
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	
	@mkdir("/etc/artica-postfix/pids",0755,true);
	@unlink("/etc/artica-postfix/pids/CompiledAllCategories");
	@file_put_contents("/etc/artica-postfix/pids/CompiledAllCategories", time());
	
	$CacheDatabase=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategoriesDatabase"));
	
	if(!is_file($ufdbGenTable)){
		build_progress("{compile}: missing compilator...",110);
		return false;
	}
	
	$sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 ORDER by category_id";
	if($ManageOfficialsCategories==1){$sql="SELECT * FROM personal_categories order by category_id";}
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";}
	
	$total=pg_num_rows($results);
	$rm=$unix->find_program("rm");
    $tempdir=temporay_work();
	$NICE=EXEC_NICE();
	$c=0;$SKIPPED=array();
	$timestart=time();
	$SUC=0;
	while ($ligne = pg_fetch_assoc($results)) {
		$c++;
		$prc=$c/$total;
		$prc=round($prc*100,0);
		if($prc<5){$prc=5;}
		if($prc>90){$prc=90;}
		$category_id=$ligne["category_id"];
		$categoryname=$ligne["categoryname"];
		if(preg_match("#^reserved[0-9]+#", $categoryname)){continue;}
		$categorykey=$ligne["categorykey"];
		$table=$ligne["categorytable"];
		$items=$ligne["items"];
		if(!$q->TABLE_EXISTS($table)){continue;}
	
		$items_sql=intval($q->COUNT_ROWS_LOW($table));
		$q->QUERY_SQL("UPDATE personal_categories SET items='$items_sql' WHERE category_id='$category_id'");
		
		
		$old_items_count=intval($ligne["compilerows"]);
		echo "[$category_id]: compilerows=$old_items_count items:$items_sql -> NO CHANGES\n";
		
		if($items_sql==$old_items_count){
			echo "[$category_id]: $categoryname/$categorykey table $table items:$items_sql -> NO CHANGES\n";
			$SKIPPED[]=$category_id;
			continue;
		}
		
		if(compile_single_category($category_id,$prc)){
			$SUC++;
			$CATNAMECOMPILE[]=$categoryname;
			$t=time();
			$q->QUERY_SQL("UPDATE personal_categories SET compiledate='$t', compilerows='$items_sql' WHERE category_id='$category_id'");
		}
		
	}
	
	
	if($SUC>0){
		$took=distanceOfTimeInWords($timestart,time(),true);
		squid_admin_mysql(2,"{success} {compiling} $SUC Web-filtering categories skipped:".count($SKIPPED)." ($took)",@implode("\n", $CATNAMECOMPILE)."\n".@implode("\n",$SKIPPED),__FILE__,__LINE__);
	
	
		if(is_file("/etc/init.d/ufdbcat")){
			build_progress("{reloading_services}",97);
			system("/etc/init.d/ufdbcat reload");
		}
		if(is_file("/etc/init.d/ufdb")){
			build_progress("{reloading_services}",98);
			system("/etc/init.d/ufdb reload");
		}
	
	
		$UfdbCatsUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUpload"));
		if($UfdbCatsUpload==1){
			$unix=new unix();
			$php=$unix->LOCATE_PHP5_BIN();
			$nohup=$unix->find_program("nohup");
			shell_exec("$nohup $php /usr/share/artica-postfix/exec.upload.categories.php >/dev/null 2>&1 &");
		}
	}
	
	build_progress("{compile_all_categories}: {done}",100);
	
	
	
}

function compile_all(){
	
	$q=new postgres_sql();
	build_progress("{compile_all_categories}",5);
	$ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
	$unix=new unix();
	$chown=$unix->find_program("chown");
	$ufdbGenTable=$unix->find_program("ufdbGenTable");
	
	@mkdir("/etc/artica-postfix/pids",0755,true);
	@unlink("/etc/artica-postfix/pids/CompiledAllCategories");
	@file_put_contents("/etc/artica-postfix/pids/CompiledAllCategories", time());
	
	$CacheDatabase=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategoriesDatabase"));
	
	if(!is_file($ufdbGenTable)){
		build_progress("{compile}: missing compilator...",110);
		return false;
	}
	
	if(!$q->FIELD_EXISTS("personal_categories", "compiledate")){
		$q->QUERY_SQL("alter table personal_categories add column if not exists compiledate bigint;");
		if(!$q->ok){echo $q->mysql_error;}
	}
	if(!$q->FIELD_EXISTS("personal_categories", "compilerows")){
		$q->QUERY_SQL("alter table personal_categories add column if not exists compilerows bigint;");
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	$sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 ORDER by category_id";
	if($ManageOfficialsCategories==1){$sql="SELECT * FROM personal_categories WHERE official_category=1 OR free_category=1 order by category_id";}
	$results=$q->QUERY_SQL($sql);
	
	$total=pg_num_rows($results);
	$rm=$unix->find_program("rm");
    $tempdir=temporay_work();
	$NICE=EXEC_NICE();
	$c=0;
	$timestart=time();
	$SUC=0;
	while ($ligne = pg_fetch_assoc($results)) {
		$c++;
		$prc=$c/$total;
		$prc=round($prc*100,0);
		if($prc<5){$prc=5;}
		if($prc>90){$prc=90;}
		$category_id=$ligne["category_id"];
		$categoryname=$ligne["categoryname"];
		if(preg_match("#^reserved[0-9]+#", $categoryname)){continue;}
		$categorykey=$ligne["categorykey"];
		$table=$ligne["categorytable"];
		$items=$ligne["items"];
		if(!$q->TABLE_EXISTS($table)){continue;}
		$items_sql=$q->COUNT_ROWS_LOW($table);
		$q->QUERY_SQL("UPDATE personal_categories SET items='$items_sql' WHERE category_id='$category_id'");
		
		if($items_sql==$CacheDatabase[$category_id]){
			$SKIPPED[]="SKIPPED: $categoryname";
			echo "[$category_id]: $categoryname/$categorykey table $table items:$items -> Skipping\n";
			continue;
		}
		
		if(compile_single_category($category_id,$prc)){
			$SUC++;
			$CATNAMECOMPILE[]=$categoryname;
			$t=time();
			$q->QUERY_SQL("UPDATE personal_categories SET compiledate='$t', compilerows='$items' WHERE category_id='$category_id'");
		}
		
		
	}
	
	
	
	if($SUC>0){
		$took=distanceOfTimeInWords($timestart,time(),true);
		squid_admin_mysql(2,"{success} {compiling} $SUC Web-filtering categories skipped:".count($SKIPPED)." ($took)",@implode("\n", $CATNAMECOMPILE)."\n".@implode("\n",$SKIPPED),__FILE__,__LINE__);
	
	
		if(is_file("/etc/init.d/ufdbcat")){
			build_progress("{reloading_services}",97);
			system("/etc/init.d/ufdbcat reload");
		}
		if(is_file("/etc/init.d/ufdb")){
			build_progress("{reloading_services}",98);
			system("/etc/init.d/ufdb reload");
		}	
	
	
		$UfdbCatsUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUpload"));
		if($UfdbCatsUpload==1){
			$unix=new unix();
			$php=$unix->LOCATE_PHP5_BIN();
			$nohup=$unix->find_program("nohup");
			shell_exec("$nohup $php /usr/share/artica-postfix/exec.upload.categories.php >/dev/null 2>&1 &");
		}
	}
	
	build_progress("{compile_all_categories}: {done}",100);
	
}

function transfert_file($category_id,$tmpfile,$categoryname){

	$unix=new unix();
	$destfile="/var/lib/squidguard/$category_id/domains";
	@mkdir(dirname($destfile),0755,true);
	if(is_file($destfile)){@unlink($destfile);}

	$out = fopen($destfile, 'wb');
	if(!$out){
		echo "Unable to fopen $destfile\n";
		return false;
	}

	$handle = @fopen($tmpfile, "r");

	if (!$handle) {
		echo "Unable to fopen $tmpfile\n";
		@unlink($tmpfile);
		return false;

	}

	

	while (!feof($handle)){
		$line=@fgets($handle);
		$line=trim($line);
		if($line==null){continue;}
		$line=str_replace("..", ".", $line);
		$line=str_replace('"', "", $line);
		$line=str_replace("'", "", $line);
		if(substr($line, 0,1)=="."){
			echo "ALERT $line for $categoryname\n";
			$line=substr($line, 1,strlen($line));
		}

		if(substr($line, strlen($line)-1,1)=="."){
			echo "ALERT3 $line for $categoryname\n";
			continue;
		}

		if(preg_match("#\.rar$#", $line)){
			echo "ALERT5 $line for $categoryname\n";
			continue;
		}

		if(preg_match("#^(.+?):[0-9]+$#", $line,$re)){$line=trim($re[1]);}
		if(strpos($line, "%")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, "@")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, ")")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, ":")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, ";")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, "/")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, "(")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, "}")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, "<")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, ">")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, "¦")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, "÷")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, "\\")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, "*")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, "#")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, " ")>0){ echo "skip $line for $categoryname\n";continue;}
		if(strpos($line, "..")>0){ echo "skip $line for $categoryname\n";continue;}
		if(preg_match("#^www\.(.+?)#", $line,$re)){$line=$re[1];}
		if(preg_match("#^www[0-9]+\.(.+?)#", $line,$re)){$line=$re[1];}
		if(preg_match("#^ww[0-9]+\.(.+?)#", $line,$re)){$line=$re[1];}
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", trim($line),$re)){$line=ip2long(trim($line)).".addr";}
		
		
		$newline="$line\n";
		fwrite($out, $newline);
	}

	@fclose($handle);
	@fclose($out);

	return true;

}