<?php
$dhclient=find_program("dhclient");
$pidof=find_program("pidof");
$kill=find_program("kill");


if(!is_file($dhclient)){exit();}

$results=explode(" ",exec("$pidof $dhclient"));

foreach ($results as $num=>$ligne){
	$ligne=trim($ligne);
	if(!is_numeric($ligne)){continue;}
	if($ligne<5){continue;}
	echo "$dhclient Killing running pid $ligne\n";
	echo "$kill -9 $ligne\n";
	system("$kill -9 $ligne");
	
}



function find_program($strProgram):string{
	$arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin',
			'/usr/local/sbin','/usr/kerberos/bin','/usr/libexec');
	if (function_exists("is_executable")) {
		foreach($arrPath as $strPath) {
            $strProgrammpath = $strPath . "/" . $strProgram;
            if (is_executable($strProgrammpath)) {
                return $strProgrammpath;
            }
        }
	} else {
		return strpos($strProgram, '.exe');
	}
    return "";
}