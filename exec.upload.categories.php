<?php
$GLOBALS["FORCE"]=false;

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;;}

include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.upload.ftp.inc');

if($argv[1]=="--config"){show_config();exit;}
if($argv[1]=="--verif"){verify_remote_storage();exit;}



xstart();

function show_config(){
    $UfdbCatsUploadFTPserv=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPserv"));
    $UfdbCatsUploadFTPusr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPusr"));
    $UfdbCatsUploadFTPpass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPpass"));
    $UfdbCatsUploadFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPDir"));


    echo "FTP Server............: $UfdbCatsUploadFTPserv\n";
    echo "FTP Username..........: $UfdbCatsUploadFTPusr\n";
    echo "FTP Password..........: $UfdbCatsUploadFTPpass\n";
    echo "FTP Directory.........: $UfdbCatsUploadFTPDir\n";

}

function xstart(){
	$unix=new unix();
	$UfdbCatsUpload=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUpload"));
	if($UfdbCatsUpload==0){
		echo "Uploading categories is disabled...\n";
		exit();
	}
	$TEMPDIR="/home/artica/webfiltering/temp-upload";
	if(!is_dir("/etc/artica-postfix/pids")){@mkdir("/etc/artica-postfix/pids",0755,true);}
	$UfdbCatsUploadFTPserv=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPserv"));
	$UfdbCatsUploadFTPusr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPusr"));
	$UfdbCatsUploadFTPpass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPpass"));
	$UfdbCatsUploadFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPDir"));
	$UfdbCatsUploadFTPSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPSchedule"));
	$TimeFile="/etc/artica-postfix/pids/exec.upload.categories.time";
	
	if(!$GLOBALS["FORCE"]){
		if($unix->file_time_min($TimeFile)<5){
			echo "Uploading categories under 5min.....\n";
			exit();}
	}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	$users=new usersMenus();
	if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
		echo "Unable to upload categories to repository (license failed)\n";
		squid_admin_mysql(0, "Unable to upload categories to repository (license failed)", null,__FILE__,__LINE__);exit();}
	
	echo "Scanning $TEMPDIR....\n";
	$files=$unix->DirFiles($TEMPDIR);
	$MAINSIZE=0;
	foreach ($files as $path=>$none){
		$localfile="$TEMPDIR/$path";
		echo "$localfile\n";
		$ch = curl_init();
		$fp = fopen($localfile, 'r');
		if($UfdbCatsUploadFTPusr<>null){
			$auth=rawurlencode($UfdbCatsUploadFTPusr).":".rawurlencode($UfdbCatsUploadFTPpass);
			$auth=$auth."@";
		
		}
		$size=filesize($localfile);
		$uri="ftp://{$auth}$UfdbCatsUploadFTPserv/$UfdbCatsUploadFTPDir/$path";
		echo "ftp://$UfdbCatsUploadFTPusr:***@$UfdbCatsUploadFTPserv/$UfdbCatsUploadFTPDir/$path";
		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_UPLOAD, 1);
		curl_setopt($ch, CURLOPT_INFILE, $fp);
		curl_setopt($ch, CURLOPT_INFILESIZE, $size);
		curl_exec ($ch);
		$Infos= curl_getinfo($ch);
		$http_code=$Infos["http_code"];
		
		if(curl_errno($ch)){
			$curl_error=curl_error($ch);
			squid_admin_mysql(0, "Unable to upload file $path to repository With Error $curl_error ( task aborted)", null,__FILE__,__LINE__);
			echo "Error:Curl error: $curl_error\n";
			return;
		}
		curl_close($ch);
		@unlink($localfile);
		$MAINSIZE=$MAINSIZE+$size;
        $FILES[]=$path;
	}
	
	$sum=FormatBytes($MAINSIZE/1024);
	if($sum>0){
		squid_admin_mysql(2, "{success} {upload} $sum to $UfdbCatsUploadFTPserv", @implode("\n",$FILES),__FILE__,__LINE__);
	}
	
	
}

function verify_remote_storage(){

    $ManageOfficialsCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ManageOfficialsCategories"));
    echo "Manage Officials Categories = $ManageOfficialsCategories\n";
    $sql="SELECT * FROM personal_categories WHERE official_category=0 AND free_category=0 ORDER by category_id";
    if($ManageOfficialsCategories==1){$sql="SELECT * FROM personal_categories WHERE official_category=1 OR free_category =1 order by category_id";}

    $REFUSED["ipsetauto.gz"]=true;
    $REFUSED["ipsetauto.index"]=true;
    $REFUSED["master_table.txt"]=true;
    $REFUSED["md5.txt"]=true;
    $REFUSED["notrack.gz"]=true;
    $REFUSED["notrack.pattern"]=true;

    echo $sql."\n";
    $i=0;
    $q=new postgres_sql();
    $unix=new unix();
    $results=$q->QUERY_SQL($sql);
    $total=pg_num_rows($results);

    while ($ligne = pg_fetch_assoc($results)) {
        $i++;
        $prc = $i / $total;
        $prc = round($prc * 100);
        if ($prc > 90) {
            $prc = 90;
        }
        $category_id = $ligne["category_id"];
        $categoryname = $ligne["categoryname"];
        $categorytable = $ligne["categorytable"];
        $textfile=$category_id.".txt";
        $gzfile=$category_id.".gz";
        $SOURCES_TEXT[$textfile]=array("ID"=>$category_id,"NAME"=>$categoryname);
        $SOURCES_TEXT[$gzfile]=array("ID"=>$category_id,"NAME"=>$categoryname);
    }



    $UfdbCatsUploadFTPserv=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPserv"));
    $UfdbCatsUploadFTPusr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPusr"));
    $UfdbCatsUploadFTPpass=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPpass"));
    $UfdbCatsUploadFTPDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatsUploadFTPDir"));

    $conn_id = ftp_connect($UfdbCatsUploadFTPserv,21,5);
    echo "Connect to $UfdbCatsUploadFTPserv OK\n";
    $login_result = ftp_login($conn_id, $UfdbCatsUploadFTPusr, $UfdbCatsUploadFTPpass);

    if ((!$conn_id) || (!$login_result)) {
        echo "Failed $UfdbCatsUploadFTPusr@$UfdbCatsUploadFTPserv\n";
        squid_admin_mysql(0, "{failed} {connecting} $UfdbCatsUploadFTPusr@$UfdbCatsUploadFTPserv", null,__FILE__,__LINE__);
        return false;
    }

    $files = ftp_nlist($conn_id,$UfdbCatsUploadFTPDir);

    foreach ($files as $file){
        $filebase=basename($file);
        if(isset($REFUSED[$filebase])){continue;}

        $DEST_TEXT[$filebase]=true;



    }

    foreach ($SOURCES_TEXT as $filebase=>$array){

    if(!isset($DEST_TEXT[$filebase])){
        echo "Missing $filebase {$array["ID"]}  {$array["NAME"]}\n";
    }

    }



    /*  $upload = ftp_put($conn_id, $destination_file, $file, FTP_BINARY);  // upload the file
      if (!$upload) {  // check upload status
          echo "<h2>FTP upload of $myFileName has failed!</h2> <br />";
      } else {
          echo "Uploading $myFileName Complete!<br /><br />";
      }



  */

    ftp_close($conn_id);

}

