<?php
	include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__) . '/ressources/class.artica.inc');
	include_once(dirname(__FILE__) . '/ressources/class.rtmm.tools.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	include_once(dirname(__FILE__) . '/ressources/class.dansguardian.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
	include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
	include_once(dirname(__FILE__) . '/framework/frame.class.inc');	
	include_once(dirname(__FILE__) . "/ressources/class.categorize.externals.inc");
	
	// http://search.netorginfo.com/20000000/porn-/1.htm
	
	
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$unix=new unix();
	$tmpfile=$unix->FILE_TEMP();
	$curl=new ccurl("http://0675.com.cn/newwebsite_20120101_list._page0.html");
	$curl->NoHTTP_POST=true;
	if(!$curl->GetFile($tmpfile)){echo "http://0675.com.cn/newwebsite_20120101_list._page0.html -> error: \n".$curl->error."\n";return;}
	
	$data=@file_get_contents($tmpfile);
	$size=strlen($data)/1024;
	echo "Size: $size Ko\n";
	
	if(preg_match("#Total:\s+([0-9]+) Domain Names.+?Domain\/page, 1\/([0-9]+)#is", $data,$re)){
		$T=$re[2];
		echo "Total {$re[1]} domains in $T pages\n";
		
	}
	@unlink($tmpfile);
	if($T>0){
		for($i=0;$i<$T+1;$i++){
			GetDomains($i);
			
		}
		
		
	}
	
function GetDomains($i){
	$unix=new unix();
	$tmpfile=$unix->FILE_TEMP();
	$curl=new ccurl("http://0675.com.cn/newwebsite_20120101_list._page0.html");
	$curl->NoHTTP_POST=true;
	echo "Get page $i\n";
	if(!$curl->GetFile($tmpfile)){echo "http://0675.com.cn/newwebsite_20120101_list._page$i.html -> error: \n".$curl->error."\n";return;}
	$datas=@file($tmpfile);
	$size=strlen(@implode("", $datas))/1024;
	echo "Page[$i]:: $tmpfile Size: $size Ko\n";
	
	foreach ($datas as $num=>$ligne){
		if(preg_match("#<div class=.*?newdomain.*?>(.*)#",$ligne)){
			echo "Sure line $num";
			$newdata=str_replace("</li>", "", $ligne);
			$newdata=str_replace("</div>", "", $newdata);
			$f=explode("<li>", $newdata);
		}
	}
	
	$q=new mysql_squid_builder();
    foreach ($f as $www){
		if(preg_match("#^\.(.+)#", $www,$re)){$www=$re[1];}
		if(strpos($www, ",")>0){continue;}
		if(strpos($www, " ")>0){continue;}
		if(strpos($www, ":")>0){continue;}
		if(strpos($www, "%")>0){continue;}	
		if(strpos($www, ">")>0){continue;}
		if(strpos($www, "<")>0){continue;}		
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}		
		$articacats=trim($q->GET_CATEGORIES($www,true,false));
		if($articacats<>null){
			echo "\"$www\" SUCCESS - $articacats -\n";
			
			continue;
		}

		echo "\"$www\" FAILED\n";
		
	}

}	