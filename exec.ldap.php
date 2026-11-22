<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__).'/ressources/class.manager.inc');

if($argv[1]=='--users'){parseusers();exit;}
if($argv[1]=="--proxy"){proxycnx();exit;}


function parseusers(){
	
$ldap=new clladp();	
$hash=GetOus();	
if(is_array($hash)){
	foreach ($hash as $num=>$ligne){
		echo "
		================================================
				Found organization: $num
		================================================
		";
		SearchUsers($num);
		
	}
}

}


function SearchUsers($org){
	$ldap=new clladp();
	$dn="ou=$org,dc=organizations,$ldap->suffix";
	$filter="(&(objectclass=userAccount)(cn=*))";
	$attrs[]="dn";
	$con=$ldap->ldap_connection;
	$sr=ldap_search($con, $dn, $filter,$attrs);
	if(!$sr){return false;}
	$entries=ldap_get_entries($con, $sr);	
	for($i=0;$i<=$entries["count"];$i++){
		$dnsearch=$entries[$i]["dn"];
		if($dnsearch==null){continue;}
		echo $dnsearch."\n";
  	}
  	
  	
}

function GetOus(){
	$ldap=new clladp();
	return $ldap->hash_get_ou(true);
  	 
}






function proxycnx(){
	$sock=new sockets();
	$database="artica_backup";
	$ldap=new clladp();
	$cffile="/etc/artica-postfix/proxy.slpad.conf";
	$EnableOpenLdapProxy=$sock->GET_INFO("EnableOpenLdapProxy");
	$OpenLdapProxySuffix=$sock->GET_INFO("OpenLdapProxySuffix");
	if($OpenLdapProxySuffix==null){$OpenLdapProxySuffix="dc=meta";}
	if(!is_numeric($EnableOpenLdapProxy)){$EnableOpenLdapProxy=0;}	
	@unlink($cffile);
	if($EnableOpenLdapProxy==0){echo "slapd: [INFO] LDAP Proxy disabled\n";return;}
	echo "slapd: [INFO] LDAP Proxy Enabled\n";
	$q=new mysql();
	$sql="SELECT * FROM openldap_proxy WHERE enabled=1";
	$results = $q->QUERY_SQL($sql,$database);
    $Manager=new class_manager();
	$localdb_suffix=$Manager->suffix;
	
	$f[]="database\tmeta";
	$f[]="suffix\t\"$OpenLdapProxySuffix\"";   
	$f[]="rootdn\t\"cn=$ldap->ldap_admin,$OpenLdapProxySuffix\"";
	$f[]="rootpw\t\"$ldap->ldap_password\"";                                                                        
	$f[]="";

	$f[]="uri           \"ldap://localhost/$OpenLdapProxySuffix\"";
	$f[]="suffixmassage \"$OpenLdapProxySuffix\" \"$localdb_suffix\"";
	$f[]="idassert-bind       bindmethod=simple";
	$f[]="\tbinddn=\"cn=$ldap->ldap_admin,$OpenLdapProxySuffix\"";
	$f[]="\tcredentials=\"$ldap->ldap_password\"";
    $f[]="";                                                                           

	
	$c=0;
	while ($ligne = mysqli_fetch_assoc($results)) {
		$c++;
		$hostname=$ligne["hostname"];
		$port=$ligne["port"];
		$articabranch=$ligne["articabranch"];
		$suffixlink=$ligne["suffixlink"];
		if($suffixlink=="*"){$suffixlink="";}
		if($suffixlink==","){$suffixlink="";}
		if($suffixlink<>null){$suffixlink="$suffixlink,";}
		$suffixmassage="$suffixlink$OpenLdapProxySuffix";
		if($articabranch==1){$suffixmassage="$OpenLdapProxySuffix";}
		echo "slapd: [INFO] Proxy:{$ligne["ID"]} $hostname:$port -> $suffixmassage\n";
		$f[]="uri\t\"ldap://$hostname:$port/$suffixmassage\"";
		$f[]="suffixmassage\t\"$suffixmassage\" \"{$ligne["suffix"]}\"";
		$f[]="idassert-bind	bindmethod=simple";
		$f[]="\tbinddn=\"{$ligne["username"]}\"";
		$f[]="\tcredentials=\"{$ligne["password"]}\"";
		$rwm=rwm($ligne["ID"]);
		if($rwm<>null){$f[]=$rwm;}else{echo "slapd: [INFO] Proxy:{$ligne["ID"]} $hostname:$port no rwm..\n";}
		$f[]="\n";
	}
	
	$f[]="lastmod off";
	$c++;
	@file_put_contents($cffile, @implode("\n", $f));
	if($GLOBALS["VERBOSE"]){echo @implode("\n", $f)."\n";}
	echo "slapd: [INFO] Proxy LDAP $c proxy(s) Done\n";
	
}

function rwm($proxyid){
	$q=new mysql();
	$sql="SELECT * FROM openldap_proxyattrs WHERE proxyid=$proxyid";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	$ctn=mysqli_num_rows($results);	
	echo "slapd: [INFO] Proxy:{$proxyid} $ctn rwm rule(s)\n";
	if($ctn==0){return null;}
	//$f[]="overlay              rwm";
	while ($ligne = mysqli_fetch_assoc($results)) {
		if(isset($al[strtolower($ligne["attribute"])])){continue;}
		//$f[]="rwm-map\t{$ligne["type"]}\t{$ligne["attribute"]}\t{$ligne["match"]}";
		$f[]="map\t{$ligne["type"]}\t{$ligne["attribute"]}\t{$ligne["match"]}";
		$al[strtolower($ligne["attribute"])]=true;
	}
	/*if(count($f)>0){
		$f[]="rwm-map\tattribute\t*";
	}*/
	return @implode("\n", $f);
}

