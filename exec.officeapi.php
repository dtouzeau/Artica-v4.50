<?php
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

xstart();


function xstart():bool{
    $OFFICE["DOMAINS"]["auth.microsoft.com"]=true;
    $OFFICE["DOMAINS"]["lync.com"]=true;
    $OFFICE["DOMAINS"]["mail.protection.outlook.com"]=true;
    $OFFICE["DOMAINS"]["msftidentity.com"]=true;
    $OFFICE["DOMAINS"]["msidentity.com"]=true;
    $OFFICE["DOMAINS"]["officeapps.live.com"]=true;
    $OFFICE["DOMAINS"]["online.office.com"]=true;
    $OFFICE["DOMAINS"]["protection.outlook.com"]=true;
    $OFFICE["DOMAINS"]["sharepoint.com"]=true;
    $OFFICE["DOMAINS"]["skypeforbusiness.com"]=true;
    $OFFICE["DOMAINS"]["account.activedirectory.windowsazure.com"]=true;
    $OFFICE["DOMAINS"]["accounts.accesscontrol.windows.net"]=true;
    $OFFICE["DOMAINS"]["adminwebservice.microsoftonline.com"]=true;
    $OFFICE["DOMAINS"]["api.passwordreset.microsoftonline.com"]=true;
    $OFFICE["DOMAINS"]["autologon.microsoftazuread-sso.com"]=true;
    $OFFICE["DOMAINS"]["becws.microsoftonline.com"]=true;
    $OFFICE["DOMAINS"]["broadcast.skype.com"]=true;
    $OFFICE["DOMAINS"]["ccs.login.microsoftonline.com"]=true;
    $OFFICE["DOMAINS"]["clientconfig.microsoftonline-p.net"]=true;
    $OFFICE["DOMAINS"]["companymanager.microsoftonline.com"]=true;
    $OFFICE["DOMAINS"]["compliance.microsoft.com"]=true;
    $OFFICE["DOMAINS"]["defender.microsoft.com"]=true;
    $OFFICE["DOMAINS"]["device.login.microsoftonline.com"]=true;
    $OFFICE["DOMAINS"]["graph.microsoft.com"]=true;
    $OFFICE["DOMAINS"]["graph.windows.net"]=true;
    $OFFICE["DOMAINS"]["login.microsoft.com"]=true;
    $OFFICE["DOMAINS"]["login.microsoftonline.com"]=true;
    $OFFICE["DOMAINS"]["login.microsoftonline-p.com"]=true;
    $OFFICE["DOMAINS"]["login.windows.net"]=true;
    $OFFICE["DOMAINS"]["logincert.microsoftonline.com"]=true;
    $OFFICE["DOMAINS"]["loginex.microsoftonline.com"]=true;
    $OFFICE["DOMAINS"]["login-us.microsoftonline.com"]=true;
    $OFFICE["DOMAINS"]["nexus.microsoftonline-p.com"]=true;
    $OFFICE["DOMAINS"]["office.live.com"]=true;
    $OFFICE["DOMAINS"]["outlook.office.com"]=true;
    $OFFICE["DOMAINS"]["outlook.office365.com"]=true;
    $OFFICE["DOMAINS"]["passwordreset.microsoftonline.com"]=true;
    $OFFICE["DOMAINS"]["protection.office.com"]=true;
    $OFFICE["DOMAINS"]["provisioningapi.microsoftonline.com"]=true;
    $OFFICE["DOMAINS"]["security.microsoft.com"]=true;
    $OFFICE["DOMAINS"]["smtp.office365.com"]=true;
    $OFFICE["DOMAINS"]["teams.microsoft.com"]=true;
    $OFFICE["DOMAINS"]["akadns.net"]=true;
    $OFFICE["DOMAINS"]["akam.net"]=true;
    $OFFICE["DOMAINS"]["akamai.com"]=true;
    $OFFICE["DOMAINS"]["akamai.net"]=true;
    $OFFICE["DOMAINS"]["akamaiedge.net"]=true;
    $OFFICE["DOMAINS"]["akamaihd.net"]=true;
    $OFFICE["DOMAINS"]["akamaized.net"]=true;
    $OFFICE["DOMAINS"]["edgekey.net"]=true;
    $OFFICE["DOMAINS"]["edgesuite.net"]=true;
    $OFFICE["DOMAINS"]["office.net"]=true;
    $OFFICE["DOMAINS"]["msftauth.net"]=true;
    $OFFICE["DOMAINS"]["msauth.net"]=true;
    $OFFICE["DOMAINS"]["login.live.com"]=true;
    $OFFICE["DOMAINS"]["microsoft.com"]=true;
    $OFFICE["DOMAINS"]["outlook.com"]=true;
    $OFFICE["DOMAINS"]["autodiscover.onmicrosoft.com"]=true;
    $OFFICE["DOMAINS"]["identitygovernance.azure.com"]=true;
    $OFFICE["DOMAINS"]["office.com"]=true;
	$unix=new unix();
	$uuid=$unix->GetUniqueID();
	$url="https://endpoints.office.com/endpoints/worldwide?noipv6&ClientRequestId=$uuid&ServiceAreas=Exchange&AllVersions=true";
	
	echo "Starting......: ".date("H:i:s")." Downloading $url\n";



	$targetpath=$unix->FILE_TEMP();
	$curl=new ccurl($url);
	$OFFICE=array();
	if(!$curl->GetFile($targetpath)){
		echo "Starting......: ".date("H:i:s")." Unable to get the Office365 API $curl->error\n";
		foreach ($curl->errors as $line){
			echo "Starting......: ".date("H:i:s")." $line\n";
		}
		squid_admin_mysql(1, "Unable to get the Office365 API", @implode("\n", $curl->errors),__FILE__,__LINE__);
		return false;
	}
    $Office365WhiteMD5=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("Office365WhiteMD5");
    $NewMD5=md5_file($targetpath);
    if($NewMD5==$Office365WhiteMD5){
        if($GLOBALS["VERBOSE"]){echo "No change, aborting\n";}
        @unlink($targetpath);
        return true;
    }
	if($GLOBALS["VERBOSE"]){echo "OPEN $targetpath\n";}
	$data=@file_get_contents($targetpath);
	$json=json_decode($data);
    $f=array();

    foreach ($json as $a=>$main){
        if(property_exists($main,"urls")){
            foreach ($main->urls as $domain){
                $f[]=$domain;
            }
        }

        if(property_exists($main,"ips")){
            foreach ($main->ips as $ipaddr){
                $f[]=$ipaddr;
            }
        }
    }
    if(count($f)<3){return false;}
	$fam=new squid_familysite();
	$ipClass=new IP();
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(strpos($line, "::/")>0){continue;}
		if(strpos(" $line", "google")>0){continue;}
		if(strpos(" $line", "bing.")>0){continue;}
		if(strpos(" $line", "trafficmanager")>0){continue;}
		if(strpos(" $line", "localytics")>0){continue;}
		if(strpos(" $line", "facebook")>0){continue;}
		if(strpos(" $line", "skype")>0){continue;}
		if(strpos(" $line", "youtube")>0){continue;}
		if(strpos(" $line", "webtrends")>0){continue;}
		if(strpos(" $line", "optimizely")>0){continue;}
		if(strpos(" $line", "yahoo")>0){continue;}
		if(strpos(" $line", "flurry")>0){continue;}
		if(strpos(" $line", "ytimg")>0){continue;}
		if(strpos(" $line", "amazon")>0){continue;}
		if(strpos(" $line", "adjust")>0){continue;}
		if(strpos(" $line", "wunderlist")>0){continue;}
		if(strpos(" $line", "hockeyapp")>0){continue;}
		if(strpos(" $line", "dropbox")>0){continue;}
		if(strpos(" $line", "box.")>0){continue;}
		if(strpos(" $line", "globalsign")>0){continue;}
		if(strpos(" $line", "linkedin")>0){continue;}
		$line=str_replace("[", "", $line);
		$line=str_replace("]", "", $line);
		$line=str_replace('"', "", $line);
		$line=str_replace('*.', "", $line);
		$line=str_replace('/32', "", $line);
		if($ipClass->isIPv6($line)){continue;}
		if($ipClass->isIPAddressOrRange($line)){
			$OFFICE["IPS"][$line]=true;
			continue;
		}
        $OFFICE["SRCDOMAINS"][$line]=true;
		$masterDomain=$fam->GetFamilySites($line);
		$OFFICE["DOMAINS"][$masterDomain]=true;
	}
	
	
	if(!isset($OFFICE["IPS"])){return false;}
	if(!isset($OFFICE["DOMAINS"])){return false;}
	if(count($OFFICE)==0){return false;}

    squid_admin_mysql("Updated new Office365 whitelist Domains ".count($OFFICE["DOMAINS"])." IPs ".count($OFFICE["IPS"]),null,__FILE__,__LINE__);
	
	echo "Starting......: ".date("H:i:s")." Office365: Domains ".count($OFFICE["DOMAINS"])."\n";
	echo "Starting......: ".date("H:i:s")." Office365: IPs ".count($OFFICE["IPS"])."\n";
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("Office365White",serialize($OFFICE));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Office365WhiteMD5",$NewMD5);
    @file_put_contents("/etc/artica-postfix/settings/Daemons/Office365White", serialize($OFFICE));
    UpdateProxyPac();
	return true;
}
function UpdateProxyPac():bool{
    $EnableProxyPac=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyPac"));
    if($EnableProxyPac==0){return true;}
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    if(!$q->FIELD_EXISTS("office365","wpad_rules")){
        $q->QUERY_SQL("ALTER TABLE wpad_rules add office365 INTEGER DEFAULT 0");
    }
    $ligne=$q->mysqli_fetch_array("SELECT count(*) as tcount FROM wpad_rules WHERE office365=1");
    $tcount=intval($ligne["tcount"]);
    if($tcount==0) {
        return true;
    }
    $unix=new unix();
    $unix->framework_exec("exec.proxy.pac.builder.php");
    return true;
}