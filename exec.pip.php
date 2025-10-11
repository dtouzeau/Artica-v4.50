<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');


if($argv[1]=="--collection"){pip_list();exit;}
if($argv[1]=="--install"){pip_install($argv[2]);exit;}


function build_progress_install($text,$pourc){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/python.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_collection($text,$pourc){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/python.collection.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function pip_list($myPrc=0){
	
	build_progress_collection("{building_collection}....",5);
	$pdesc=array();
	
	if(is_file("/home/artica/SQLITE/python-packages.db")){
		$q=new lib_sqlite("/home/artica/SQLITE/python-packages.db");
		$results=$q->QUERY_SQL("SELECT * FROM python_packages");
		foreach ($results as $index=>$ligne){
			if(trim($ligne["package_name"])==null){continue;}
			$pdesc[$ligne["package_name"]]=$ligne["package_description"];}
	}
	
	$results=array();
	$unix=new unix();
	if($unix->DEBIAN_VERSION()>9) {
        $pattern="#^(.+?)\s+([0-9\.\-]+)#";
	    exec("/usr/bin/pip list 2>&1", $results);
    }else{
	    $pattern="#^(.+?)\((.+?)\)#";
        exec("/usr/bin/pip list --format=legacy 2>&1", $results);
    }
	@unlink("/home/artica/SQLITE/python-packages.db");
	@chmod("/home/artica/SQLITE/python-packages.db",0755);
	$q=new lib_sqlite("/home/artica/SQLITE/python-packages.db");
	
	
	
	
	$sql="CREATE TABLE IF NOT EXISTS `python_packages` (
			  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			  `package_name` text UNIQUE,
			  `package_version` text,
			  `package_description` text
			)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		if(!$q->ok){echo $q->mysql_error;@unlink("/home/artica/SQLITE/python-packages.db");}
		echo $q->mysql_error."\n";
		build_progress_collection("{building_collection} {failed}",110);
		return;
	}
    $f=array();
	$MAX=count($results);
	echo "Max: $MAX\n";
	$c=0;
	$Toperc=0;
	$item=0;
	foreach ($results as $index=>$line){
		$c++;
		$prc=round($c/$MAX*100);
		if($prc>5){
			if($prc<98){
				if($prc>$Toperc){
					$Toperc=$prc;
					build_progress_collection("{building_collection}",$Toperc);
					if($myPrc>0){build_progress_install("{building_collection} {$Toperc}%",$myPrc);}
				}
			}
		}
		
		if(!preg_match($pattern, $line,$re)){
		    if($GLOBALS["VERBOSE"]){echo "SKIP \"$line\" <> $pattern\n";}
		    continue;}
		$pname=trim(strtolower($re[1]));
		$pver=trim($re[2]);
		if(   ($pname=="package") AND (strtolower($pver)=="version") ){continue;}
		$item++;
		$desc=GetPackageDescription($pname,$pdesc);
		$f[]="('$pname','$pver','$desc')";
	}
	
	
	if(count($f)>0){
	    $sql="INSERT OR IGNORE INTO `python_packages` (package_name,package_version,package_description) VALUES ".@implode(",", $f);
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n$sql\n";@unlink("/home/artica/SQLITE/python-packages.db");
		build_progress_collection("{building_collection} {failed}",110);
		}
	}
	$sock=new sockets();
	$sock->SET_INFO("PythonCollection", time());
	build_progress_collection("{building_collection} {success} {$item} {items}",100);
	
}

function GetPackageDescription($packagename,$pdesc){
	
	if(isset($pdesc[$packagename])){
		if(trim($pdesc[$packagename])<>null){
			if($GLOBALS["VERBOSE"]){echo "$packagename: HIT description {$pdesc[$packagename]}\n";}
			return trim(sqlite_escape_string2($pdesc[$packagename]));
		}
	}
	
	exec("/usr/bin/pip show $packagename 2>&1",$results);
	foreach ($results as $index=>$line){
		
		if(!preg_match("#Summary:\s+(.+)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "$packagename: No mtach $line\n";}
			continue;}
		return trim(sqlite_escape_string2($re[1]));
	}
	
	if($GLOBALS["VERBOSE"]){echo "$packagename: UNKNOWN description";}
	
}

function pip_install($pname=null){
	
	if($pname==null){
		build_progress_install("{failed} no package",110);
		return;
	}
	
	$sock=new sockets();
	$proxy=$sock->GET_PROXY_STRING();
	if($proxy<>null){$proxy=" --proxy $proxy";}
	echo "Use Proxy $proxy\n";
	build_progress_install("{installing} $pname",20);
	exec("/usr/bin/pip install $pname --disable-pip-version-check{$proxy} 2>&1",$results);
	build_progress_install("{building_collection} $pname",50);
	pip_list(50);
	
	foreach ($results as $index=>$line){
		echo "$line\n";
		
		if(preg_match("#Successfully installed\s+(.+)#i", $line,$re)){
			build_progress_install("{success} {$re[1]}",90);
			
		}
		
		
	}
	$q=new lib_sqlite("/home/artica/SQLITE/python-packages.db");
	$ligne=$q->mysqli_fetch_array("SELECT package_version FROM python_packages WHERE package_name='$pname'");
	if(strlen(trim($ligne["package_version"]))>1){
		build_progress_install("{installing} $pname {success}",100);
		return;
	}
	build_progress_install("{installing} $pname {failed}",110);
	
}




