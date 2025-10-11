<?php
if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);	
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	$GLOBALS["VERBOSE"]=true;
}
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");



if(isset($_GET["restart-progress"])){restart_progress();exit;}
if(isset($_GET["ss5events"])){ss5events();exit;}
foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);

function ss5events(){
	$search=trim(base64_decode($_GET["ss5events"]));
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$rp=500;
	if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}

	if($search==null){

		$cmd="$tail -n $rp /var/log/ss5/ss5.log 2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		exec("$tail -n $rp /var/log/ss5/ss5.log 2>&1",$results);
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ss5-events", serialize($results));
		
		return;
	}

	$search=$unix->StringToGrep($search);


	$cmd="$grep --binary-files=text -i -E '$search' /var/log/ss5/ss5.log 2>&1|$tail -n $rp 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd",$results);

	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ss5-events", serialize($results));

}