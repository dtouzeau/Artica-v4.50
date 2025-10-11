<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.squid.acls.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);



xrun($argv[1],$argv[2]);



function xrun($filename,$gpid){
    $SQ         = array();
	$gpid       = intval($gpid);
	$IPClass    = new IP();
	$filepath   = dirname(__FILE__)."/ressources/conf/upload/$filename";
	
	if(!is_file($filepath)){
		echo "$filepath no such file\n";
		build_progress(110, "{failed}");
		@unlink($filepath);
		return;
	}
	
	if($gpid==0){
		echo "$gpid is 0\n";
		build_progress(110, "{failed}");
		@unlink($filepath);
		return;		
	}
	
	
	$q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$ligne=$q->mysqli_fetch_array("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='$gpid'");
	$GroupType=$ligne["GroupType"];
	$GroupName=$ligne["GroupName"];
	
	echo "$GroupName Type $GroupType\n";
	
	$unix=new unix();
	$MAX=$unix->COUNT_LINES_OF_FILE($filepath);
	$fp = @fopen($filepath, "r");
	if(!$fp){
		echo "$filename BAD FD\n";
		build_progress(110, "{failed}");
		@unlink($filepath);
		return;
	}
	$zdate=date("Y-m-d H:i:s");
	$c=0;
	while(!feof($fp)){
		$line = trim(fgets($fp));
		$line=trim($line);
		$c++;

		if($line==null){continue;}
        if($line=="//"){continue;}
        if($line==")"){continue;}
        if($line=="("){continue;}
        if($line=="#"){continue;}
        if(preg_match("#return\s+#",$line)){continue;}

        if(preg_match("#^\/\/(.+)$#",$line,$re)){
            $description=trim($re[1]);
            continue;
        }
        if(preg_match("#^\##",$line)){continue;}



        if(preg_match("#[shExpMatch|dnsDomainIs].*?\(.*?,.*?[\"|'](.+?)[\"|']#i",$line,$re)){
            $line=$re[1];
        }

        if(preg_match("#^if.*?\(#i",$line)){continue;}

		if($GroupType=="src"){
			if(!$IPClass->isIPAddressOrRange($line)){
				echo "$GroupType: $line FALSE\n";
				continue;
			}
		}
		
		if($GroupType=="dst"){
			$IPClass=new IP();
			if(!$IPClass->isIPAddressOrRange($line)){
				echo "$GroupType: $line FALSE\n";
				continue;
			}
		}
		
		if($GroupType=="arp"){
			if(!$IPClass->IsvalidMAC($line)){
				echo "$GroupType: $line FALSE\n";
				continue;
				}
		}
		
		if($GroupType=="dstdomain"){
			if(preg_match("#^(http|https|ftp|ftps):\/#", $line)){$arrM=parse_url($line);$line=$arrM["host"];}
			if(strpos($line, "/")>0){$arrM=explode("/",$line);$line=$arrM[0];}
				
			if(strpos(" $line", "^")==0){
				$squidfam=new squid_familysite();
				$fam=$squidfam->GetFamilySites($line);
				if($fam<>$line){$line="^$line";}
			}
				
		}

            $description=$q->sqlite_escape_string2($description);
			$line=sqlite_escape_string2($line);
			$SQ[]="('$gpid','$line',1,'$zdate','$description')";
		
			if(count($SQ)>500){
				$prc=($c/$MAX)*100;
				$prc=round($prc);
				if($prc>98){$prc=98;}
				if($prc<10){$prc=10;}
				$sql="INSERT INTO webfilters_sqitems (gpid,pattern,enabled,zdate,description) VALUES ".@implode(",", $SQ);
				build_progress($prc, "{failed}");
                $description=null;
				$q->QUERY_SQL($sql);
				if(!$q->ok){
					echo $q->mysql_error;
					build_progress(110, "$c/$MAX {elements}");
					@unlink($filepath);
					return;
				}
				$SQ=array();
				continue;
			}
		
		}
		
		
	if(count($SQ)>0){
		$sql="INSERT INTO webfilters_sqitems (gpid,pattern,enabled,zdate,description) VALUES ".@implode(",", $SQ);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo $q->mysql_error;
			build_progress(110, "{failed}");
			@unlink($filepath);
			return;
		}

	}

	build_progress(100, "{success}");
	@unlink($filepath);
		
}


function build_progress($pourc,$text){
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/acls.import.progress";
	echo "$date: [{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
}
