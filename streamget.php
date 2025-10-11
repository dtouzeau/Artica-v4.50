<?php
include_once(dirname(__FILE__)."/ressources/class.templates.inc");

	$sock=new sockets();
	$SquidGuardStorageDir=$sock->GET_INFO("SquidGuardStorageDir");
	$SquidGuardMaxStorageDay=$sock->GET_INFO("SquidGuardMaxStorageDay");

if($SquidGuardStorageDir==null){$SquidGuardStorageDir="/home/artica/cache";}
$file_path=$SquidGuardStorageDir;
$logfile="$file_path/cache.log";
$url=urldecode($_GET['url']);
$urlptr=fopen($_GET['url'],"r");
$blocksize=32*1024; 
        
        
if($urlptr===FALSE){WLOG("404 Not Found,`$url`",__LINE__);header("Status: 404 Not Found");die("DIE " .__FILE__." Line: ".__LINE__);}
        
        
        foreach($http_response_header as $line){
        		//WLOG("Header \"$line\" ",__LINE__);
        		if(preg_match("#200 OK#", $line)){continue;}
        		if(preg_match("#Connection: close#", $line)){continue;}
        		if(preg_match("#Server:\s+#", $line)){continue;}
        		$HEADERS[]=$line;
                if(substr_compare($line,'Content-Type',0,12,true)==0)
                        $content_type=$line;
                else if(substr_compare($line,'Content-Length',0,14,true)==0){
                        $content_length=$line;
                }
        }
        
        
        /**Youtube will detect if requests are coming form the wrong ip (ie, if only video requests are redirected, so, we must redirect all requests to youtube.
        As such, we must capture all requests t youtube. Most are unimportant, so we can pass them straight through **/
        if(!preg_match("#.*youtube.*videoplayback.*#",$url)){
        		WLOG("\"$url\" no match -> fpassthru()",__LINE__);
                fpassthru($urlptr);
                fclose($urlptr);
                exit(0);
        } 
        foreach($HEADERS as $line){
        	WLOG("Header:$line",__LINE__);
        	header($line);
        	
        }
        //send content type and length
         WLOG("$content_type $content_length");
         
       // header($content_type);
       // header($content_length);
        WLOG("");
        WLOG("************ Youtube **************",__LINE__);
         
        $MyVideoUrl=$url;
        
        //find youtube id;
        $url_exploded=explode('&',$url);
        $id="";
        foreach($url_exploded as $line){
        	WLOG("$line",__LINE__);
          	if(substr($line,0,3)==='id='){$id=substr($line,3);}
        }
        //Get the supposed file size
        WLOG("Youtube ID: $id",__LINE__);
        $length=intval(substr($content_length,16));
        WLOG($logfile,"content-type: $content_type content-length=$content_length");
        
        //Do we have it? delivar if we do
        $fname="$file_path/$id-$length";
        $fnameUrl="$file_path/$id-$length.url";
        @file_put_contents($fnameUrl, $MyVideoUrl);
        $lockfile="$fname.lock";
        $lengthlog=round(($length/1024)/1000,2);
		//Check if we have the file, and it is the correct size. incorrect size implies corruption
        if(file_exists($fname)){
        	$fsize=@filesize($fname);
        	if($fsize==$length){
     			readfile($fname);
                WLOG("DELIVER $fname",__LINE__);
                logdata("DELIVER",$url,$fname);
                exit(0);
        }else{
        	WLOG("$fname size $fsize did not match requested $length",__LINE__);
        }
        	
        }else{
        	WLOG("$fname no such file",__LINE__);
        }
        
        if(is_file($lockfile)){
        	$lockfileSec=file_time_sec($lockfile);
        	if($lockfileSec<5){
        		WLOG("$lockfile exists ($lockfileSec seconds) -> send anyway",__LINE__);
				readfile($fname);
            	WLOG("DELIVER $fname",__LINE__);
            	logdata("DELIVER",$url,$fname);
            	exit(0);
        	}        	 
        }
        
        
        //file not in cache? Get it, send it & save it
        $url=$url."&artica-time-stamp-".time()."=1";
        logdata("DOWNLOAD",$url,$fname);
        $basename=basename($fname);
        $fileptr=fopen($fname,"w");
        //no validity check, simply don't write the file if we can't open it. prevents noticeable failure/
        $c=0;
        $bytes=0;
       
        while(!feof($urlptr)){
                @file_put_contents($lockfile, time());
        		$line=fread($urlptr,$blocksize);
                $bytes=$bytes+strlen($line);
                $c++;
                echo $line;
                if($c>300){
                	WLOG("($id): [$basename]:Ouput ".round(($bytes/1024)/1000,2)."MB / {$lengthlog}MB",__LINE__);
                	$c=0;
                }
                if($fileptr) fwrite($fileptr,$line);
                @unlink($lockfile);
        }
        @unlink($lockfile);
        WLOG("Finish $fname",__LINE__);
        $sock=new sockets();
        $sock->getFrameWork("squid.php?streamget=yes");
        @fclose($urlptr);
        if($fileptr) fclose($fileptr);
        
function logdata($type,$what, $fname){
        $file_path="/home/artica/cache";
        $logfile="$file_path/cache.log";
        $line="@ ".time()."Cache $type url: $what file: $fname client:".$_SERVER['REMOTE_ADDR']."\n";
        file_put_contents($logfile,$line,FILE_APPEND);
}
function WLOG($text=null,$line){
	if(!isset($GLOBALS["PID"])){$GLOBALS["PID"]=getmypid();}
	$logFile="/home/artica/cache/debug.log";
	$date=@date("Y-m-d H:i:s");
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>1000000){unlink($logFile);}
   	}
	
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date [{$GLOBALS["PID"]}]:$line $text\n");
	@fclose($f);
}

function file_time_sec($path){
		if(!is_dir($path)){if(!is_file($path)){return 100000;}}
	 		$last_modified = filemtime($path);
	 		$data1 = $last_modified;
			$data2 = time();
			$difference = ($data2 - $data1); 	 
			return round($difference);	 
		}


?>
