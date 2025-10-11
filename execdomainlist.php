#!/usr/bin/php
<?php
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
//error_reporting(0);
if(preg_match("#--verbose#", @implode(" ", $argv))){
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);error_reporting(1);
	error_reporting(1);
	$GLOBALS["VERBOSE"]=true;
	echo "VERBOSED MODE\n";
}

$category=$argv[1];
$categorynamePersoPathBaseDOM=$argv[2];
$q=new mysql_squid_builder();
$category_table=$q->cat_totablename($category);



if(!preg_match("#^category_.*#", $category_table)){
	$category_table="category_$category";
	if(!$q->TABLE_EXISTS($category_table)){
		compile($categorynamePersoPathBaseDOM);
		die(0);
	}
}


$sql="SELECT pattern FROM `$category_table` ORDER BY pattern";
$results = $q->QUERY_SQL($sql);
if(!$q->ok){die(0);}
if(@mysqli_num_rows($results)==0){
	compile($categorynamePersoPathBaseDOM);
	die(0);
}

while ($ligne = mysqli_fetch_assoc($results)) {
	$websitename=trim(strtolower($ligne["pattern"]));
	if(strlen($websitename)<4){continue;}
	if(!preg_match("#\.[a-z0-9]+$#", $websitename)){continue;}
	if(strpos(" $websitename", "*")>0){continue;}
	if(strpos(" $websitename", ";")>0){continue;}
	if(strpos(" $websitename", "?")>0){continue;}
	if(strpos(" $websitename", "/")>0){continue;}
	if(strpos(" $websitename", "%")>0){continue;}
	if(strpos(" $websitename", ",")>0){continue;}
	if(strpos(" $websitename", "&")>0){continue;}
	if(strpos(" $websitename", ">")>0){continue;}
	if(strpos(" $websitename", "<")>0){continue;}
	if(strpos(" $websitename", "(")>0){continue;}
	if(strpos(" $websitename", ")")>0){continue;}
	if(strpos(" $websitename", "[")>0){continue;}
	if(strpos(" $websitename", "]")>0){continue;}
	if(strpos(" $websitename", "+")>0){continue;}
	if(strpos(" $websitename", "@")>0){continue;}
	$f[]=$websitename;
	
}
if(count($f)==0){
	compile($categorynamePersoPathBaseDOM);
	die(0);
}

@file_put_contents($categorynamePersoPathBaseDOM."-new", @implode("\n", $f));
compile($categorynamePersoPathBaseDOM);
die(0);

function compile($categorynamePersoPathBaseDOM){
	$update=false;
	$workingdir=dirname($categorynamePersoPathBaseDOM);
	if(!is_file($categorynamePersoPathBaseDOM)){
		@file_put_contents($categorynamePersoPathBaseDOM, "\n");
		
	}
	
	if(!is_file($categorynamePersoPathBaseDOM."-new")){
		@file_put_contents($categorynamePersoPathBaseDOM."-new", "\n");
		$update=true;
	}
	
	if(!is_file("$workingdir/urls")){
		@file_put_contents("$workingdir/urls", "\n");
		$update=true;
	}
	
	if(!is_file("$workingdir/urls-new")){
		@file_put_contents("$workingdir/urls-new", "\n");
		$update=true;
	}	
	
	if(md5_file($categorynamePersoPathBaseDOM)<>md5_file($categorynamePersoPathBaseDOM."-new")){
		$update=true;
	}
	
	if($update){
		$unix=new unix();
		$ufdbGenTable=$unix->find_program("ufdbGenTable");
		@unlink("$workingdir/urls");
		@unlink("$categorynamePersoPathBaseDOM");
		@copy($categorynamePersoPathBaseDOM."-new", $categorynamePersoPathBaseDOM);
		@copy("$workingdir/urls-new", "$workingdir/urls");
		$cmd="$ufdbGenTable -n -W -d $categorynamePersoPathBaseDOM -f $workingdir/urls";
		shell_exec($cmd);
	}
	
	
}

