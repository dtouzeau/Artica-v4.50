<?php
include_once(dirname(__FILE__)."/class.familysites.inc");

function ifTracker($uri){

	$H=parse_url($uri);
	$host=$H["host"];

	$patterns[]="(^|\.)(graph\.facebook|unica|kameleoon|addthis|google-analytics|coremetrics|googlesyndication|chango|pinterest)\.com$";
	$patterns[]="(^|\.)(iadvize)\.com$";
	$patterns[]="(^|\.)(doubleclick|owneriq)\.net$";
	$patterns[]="^metrics\.dailymotion\.";
	$patterns[]="(^|\.)go2cloud\.org$";

	while (list ($index, $pattern) = each ($patterns) ){
		if(!preg_match("#$pattern#", $host)){continue;}
		$path=$H["path"];
		$scheme=$H["scheme"];
		return "$scheme://$host$path";
	}


	return $uri;

}
function HyperCacheRulesBlacklist($domain){
	$domain=strtolower($domain);
	if(!preg_match("#^http#", $domain)){$domain="http://$domain/";}
	
	$H=parse_url($domain);
	$host=$H["host"];
	$fam=new squid_familysite();
	$family=$fam->GetFamilySites($host);
	
	$arraySubDom["realtime.services.disqus.com"]=true;
	$arrayDOMS["xiti.com"]=true;
	$arrayDOMS["ebayrtm.com"]=true;
	$arrayDOMS["netmng.com"]=true;
	$arrayDOMS["dropbox.com"]=true;
	$arrayDOMS["symcd.com"]=true;
	$arrayDOMS["xiti.com"]=true;
	$arrayDOMS["etracker.com"]=true;
	$arrayDOMS["iadvize.com"]=true;
	$arrayDOMS["geotrust.com"]=true;
	$arrayDOMS["2o7.net"]=true;
	$arrayDOMS["doubleclick.net"]=true;
	$arrayDOMS["capaffiliation.com"]=true;
	$arrayDOMS["c4tw.net"]=true;
	$arrayDOMS["content-square.net"]=true;
	$arrayDOMS["histats.com"]=true;
	$arrayDOMS["cedexis-radar.net"]=true;
	$arrayDOMS["cedexis.org"]=true;
	$arrayDOMS["cedexis.com"]=true;
	$arrayDOMS["adcolony.com"]=true;
	if(isset($arrayDOMS[$family])){return true;}
	if(isset($arraySubDom[$host])){return true;}
	
	
	if(preg_match("#http.*?logger-.*?\.dailymotion\.#", $domain)){return true;}
	if(preg_match("#http.*?instagram\.com.*\?max_id=[0-9]+#", $domain)){return true;}
	if(preg_match("#http.*?logger-[0-9]+.*?dailymotion\.com#", $domain)){return true;}
	if(preg_match("#http.*?dailymotion\.com\/track(\/|-boomerang)#", $domain)){return true;}
	if(preg_match("#dailymotion\.com\/history\/log\/#", $domain)){return true;}
}