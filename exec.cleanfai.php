#!/usr/bin/php
<?php
$GLOBALS["VERBOSE"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

$f=explode("\n",@file_get_contents("/srv/fai/config/package_config/FAIBASE"));

foreach ($f as $num=>$ligne){
	$ligne=trim($ligne);
	if(trim($ligne)==null){continue;}
	if(preg_match("#^\##", $ligne)){
		$t[]=$ligne;
		continue;
	}
	if(isset($d[$ligne])){continue;}
	$d[$ligne]=true;
	$t[]=$ligne;
}

@file_put_contents("/srv/fai/config/package_config/FAIBASE", @implode("\n", $t));


?>