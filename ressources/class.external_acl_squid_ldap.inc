<?php


class external_acl_squid_ldap{
	public $Errors=array();
	private $SquidAddkerAlernates=array();
	
	function __construct(){
		
		
		
	}
	
	private function diff_time_min($time){
		$data1 = $time;
		$data2 = time();
		$difference = ($data2 - $data1);
		$results=intval(round($difference/60));
		if($results<0){$results=1;}
		return $results;
	}
	
	public function GetUserOU($member){
		if(isset($GLOBALS["GetUserOU"][$member])){return $GLOBALS["GetUserOU"][$member];}
		$infos=$this->ADLdap_userinfos($member);
		if(!isset($infos[0]["dn"])){return null;}
		$DNs=explode(",",$infos[0]["dn"]);
        foreach ($DNs as $a=>$part){
			$part=trim(strtolower($part));
			if(preg_match("#ou=(.+)#", $part,$re)){
				$GLOBALS["GetUserOU"][$member]=$re[1];
				return $GLOBALS["GetUserOU"][$member];
			}
		}
		
		$GLOBALS["GetUserOU"][$member]="users";
		return "users";
		
	}
	
	private function ParseOptions($ligne){
		
		if(!isset($ligne["LDAP_SERVER"])){return false;}
		if(!isset($ligne["LDAP_SUFFIX"])){return false;}
		if(!isset($ligne["LDAP_DN"])){return false;}
		
		$dnuser=$ligne["LDAP_DN"];
		if(strpos($dnuser, "@")>0){
			$dnuser_expl=explode("@",$dnuser);
			$username=$dnuser_expl[0];
			$account_suffix=$dnuser_expl[1];
		}
		
		
		if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]: [".__FUNCTION__."/".__LINE__."] base_dn............: {$ligne["LDAP_SUFFIX"]}");}
		if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]: [".__FUNCTION__."/".__LINE__."] ad_username........: {$username}");}
		if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]: [".__FUNCTION__."/".__LINE__."] domain_controllers.: {$ligne["LDAP_SERVER"]}");}
		if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]: [".__FUNCTION__."/".__LINE__."] account_suffix.....: {$account_suffix}");}
		$ad_port=389;
		if(isset($ligne["LDAP_PORT"])){if(is_numeric($ligne["LDAP_PORT"])){$ad_port=$ligne["LDAP_PORT"];}}
		
		$options=array(
				'base_dn'=>$ligne["LDAP_SUFFIX"],
				'ad_username'=>$username,
				'ad_password'=>$ligne["LDAP_PASSWORD"],
				'recursive_groups'=>1,
				'ad_port'=>$ad_port,
				'domain_controllers'=>array($ligne["LDAP_SERVER"]),
				'account_suffix'=>"$account_suffix");
		
		return $options;
		
	}
	
	
	public function GetGroupsFromMember($member){

		if(preg_match("#^(.*?)\/(.+)#", $member,$re)){$member=$re[2];}
		if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]: [".__FUNCTION__."/".__LINE__."] $member: Build default settings");}
		$this->BuildDefault();
		
		$Groups=$this->ADLdap_getgroups($member);
		
		if(!isset($GLOBALS["ActiveDirectoryConnections"])){
			if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]: [".__FUNCTION__."/".__LINE__."] OPEN ActiveDirectoryConnections");}
			$GLOBALS["ActiveDirectoryConnections"]=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
		}
		if(!$GLOBALS["ActiveDirectoryConnections"]){
			if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]: [".__FUNCTION__."/".__LINE__."] ActiveDirectoryConnections Not an array");}
			return $Groups;
		}
		if(count($GLOBALS["ActiveDirectoryConnections"])==0){
			if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]: [".__FUNCTION__."/".__LINE__."] ActiveDirectoryConnections no other connections");}
			return $Groups;
		}
			
		if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]: [".__FUNCTION__."/".__LINE__."] ".count($GLOBALS["ActiveDirectoryConnections"])." Active Directory Connections");}
		reset($GLOBALS["ActiveDirectoryConnections"]);
		if($GLOBALS["VERBOSE"]){print_r($GLOBALS["ActiveDirectoryConnections"]);}
        foreach ($GLOBALS["ActiveDirectoryConnections"] as $index=>$ligne){
			if(!is_numeric($index)){continue;}
			if(!isset($ligne["LDAP_SERVER"])){continue;}
			$options=$this->ParseOptions($ligne);
			if(!$options){
				if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]: [".__FUNCTION__."/".__LINE__."] ActiveDirectoryConnections $index * * bad parameters * * ");}
				continue;
			}
				
			if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]: [".__FUNCTION__."/".__LINE__."]  this->ADLdap_getgroups($member,....)");}
			$Groups2=$this->ADLdap_getgroups($member,$options);
			$CountOfGroups2=count($Groups2);
			
			if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG("[QUERY]: [".__FUNCTION__."/".__LINE__."] $CountOfGroups2 in {$ligne["LDAP_SERVER"]}");}
			if($CountOfGroups2==0){continue;}

            foreach ($Groups2 as $num=>$group){
				$Groups[$num]=true;
			}
			
			
				
		}
		
		return $Groups;
	
	}	
	
	
	
	
private function BuildDefault():bool{
	if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_SERVER"])){
		$this->WLOG("KerbAuthInfos -> Try to find LDAP_SERVER");
		$GLOBALS["KerbAuthInfos"]["LDAP_SERVER"]=$this->BuildDefault_ldap_server();
		$this->WLOG("KerbAuthInfos -> {$GLOBALS["KerbAuthInfos"]["LDAP_SERVER"]}");
	}
	
	
	
	if(!isset($GLOBALS["KerbAuthInfos"]["RECURSIVE_GROUPS"])){$GLOBALS["KerbAuthInfos"]["RECURSIVE_GROUPS"]=0;}
	if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_PORT"])){$GLOBALS["KerbAuthInfos"]["LDAP_PORT"]=389;}
	if(!isset($GLOBALS["KerbAuthInfos"]["LDAP_SUFFIX"])){
		$SMB=$this->SAMBA_GetNetAdsInfos();
		$GLOBALS["KerbAuthInfos"]["LDAP_SUFFIX"]=$SMB["Bind Path"];
	}
	
	if(!isset($GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_ADMIN"])){
		$array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
        if(!is_array($array)){$array=array();}
		$GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_ADMIN"]=$array["WINDOWS_SERVER_ADMIN"];
		$GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_PASS"]=$array["WINDOWS_SERVER_PASS"];
		$GLOBALS["KerbAuthInfos"]["WINDOWS_DNS_SUFFIX"]=$array["WINDOWS_DNS_SUFFIX"];
	}
	
	
	if(isset($GLOBALS["KerbAuthInfos"]["LDAP_RECURSIVE"])){
		if($GLOBALS["KerbAuthInfos"]["LDAP_RECURSIVE"]==1){
			$GLOBALS["KerbAuthInfos"]["RECURSIVE_GROUPS"]=1;
		}
	}

	return true;
	
}	



private function LOCATE_NET_BIN_PATH():string{
	$net=$this->internal_find_program("net");
	if(is_file($net)){return $net;}
	$net=$this->internal_find_program("net.samba3");
	if(is_file($net)){return $net;}
    return "";
}

private function SAMBA_GetNetAdsInfos(){
	if(isset($GLOBALS["CACHE_NET"])){return $GLOBALS["CACHE_NET"];}
	
	if(is_file("/etc/squid3/NET_ADS_INFOS")){
		$array=unserialize(@file_get_contents("/etc/squid3/NET_ADS_INFOS"));
        if(!is_array($array)){$array=array();}
		if(count($array)>5){
			$GLOBALS["CACHE_NET"]=$array;
			return $array;
		}
	}

	$net=$this->LOCATE_NET_BIN_PATH();
	if(!is_file($net)){return array();}
	exec("$net ads info 2>&1",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#^(.+?):(.+)#",trim($line),$re)){
			$array[trim($re[1])]=trim($re[2]);
		}
	}

	if(!isset($array["KDC server"])){$array["KDC server"]=null;}
	$this->WLOG("$net ads info 2>&1 return ".count($array)." items");
	$GLOBALS["CACHE_NET"]=$array;
	@file_put_contents("/etc/squid3/NET_ADS_INFOS", serialize($array));
	return $array;
}


private function internal_find_program($strProgram):string{
	if(isset($GLOBALS["internal_find_program"][$strProgram])){return $GLOBALS["internal_find_program"][$strProgram];}
	global $addpaths;
	$arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin','/usr/local/sbin','/usr/kerberos/bin');
	if (function_exists("is_executable")) {
		foreach($arrPath as $strPath) {
			$strProgrammpath = $strPath . "/" . $strProgram;
			if (is_executable($strProgrammpath)) {
				$GLOBALS["internal_find_program"][$strProgram]=$strProgrammpath;return $strProgrammpath;
				}
		}
	} else {
		$GLOBALS["internal_find_program"][$strProgram]=strpos($strProgram, '.exe');
		return $GLOBALS["internal_find_program"][$strProgram];
	}
    return "";
}


private function BuildDefault_ldap_server():string{
	if(isset($GLOBALS["KerbAuthInfos"]["ADNETIPADDR"])){return $GLOBALS["KerbAuthInfos"]["ADNETIPADDR"];}
	if(isset($GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_NETBIOSNAME"])){return $GLOBALS["KerbAuthInfos"]["WINDOWS_SERVER_NETBIOSNAME"].".".$GLOBALS["KerbAuthInfos"]["WINDOWS_DNS_SUFFIX"];}
	$SMB=$this->SAMBA_GetNetAdsInfos();
    if(isset($SMB["LDAP server"])) {
        return $SMB["LDAP server"];
    }
    return "";
}

private function WLOG($text=null,$line=0){
	if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
	if($GLOBALS["VERBOSE"]){echo "$text in line $line\n";}
	if(count($this->Errors)>500){$this->Errors=array();}
	$trace=@debug_backtrace();
	if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
	$date=@date("Y-m-d H:i:s");
	if (is_file("/var/log/squid/external-acl.log")) {
		$size=@filesize("/var/log/squid/external-acl.log");
		if($size>1000000){unlink("/var/log/squid/external-acl.log");
			
		}
	}
	$F = @fopen("/var/log/squid/external-acl.log", 'a');
	if(!isset($GLOBALS["PID"])){ if(function_exists("getmypid")){ $GLOBALS["PID"]=getmypid(); }else{$GLOBALS["PID"]=0;}}
	$FinalText="$date ".basename(__FILE__)."[{$GLOBALS["PID"]}]: $text $called";
	$this->Errors[]=$FinalText;
	@fwrite($F, $FinalText."\n");
	@fclose($F);
}

public function ADLdap_getgroups($username,$options=array()){
	$RETURN=array();
	$this->ADLdap_init();
	
	
	if(!isset($options["base_dn"])){
		if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG(__FUNCTION__.": base_dn not set, load defaults",__LINE__);}
		$options=$this->ADLdap_options();}
	try {
		$adldap = new adLDAP($options);
	}
	catch (adLDAPException $e) {
		if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG(__FUNCTION__.": Failed with '$e'",__LINE__);}
		$this->WLOG($e);
		return array();
	}
	
	
	if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG(__FUNCTION__.": authenticate success -> adldap->user()->groups($username)",__LINE__);}
	
	try {
	$result=$adldap->user()->groups($username);
	}
	catch (adLDAPException $e) {
		$this->WLOG($e);
		if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG(__FUNCTION__.": adldap->user()->groups($username) Failed with '{$options["ad_username"]}'",__LINE__);}
		return array();
	}
	
	
	if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG(__FUNCTION__.":".count($result)." items * * * *");}

	
	if(!$result){
		if($GLOBALS["DEBUG_GROUPS"]){$this->WLOG(__FUNCTION__.": Not an array, 0 items * * * *");}
		return $RETURN;
	}
	if(count($result)>0){
        foreach ($result as $num=>$group){
		    $RETURN[$group]=true;
        }
		return $RETURN;
	}
	
	return $RETURN;
}




public function ADLdap_userinfos($username){
	$RETURN=array();


	$this->ADLdap_init();
	$options=$this->ADLdap_options();
	try {
		$adldap = new adLDAP($options);
	}
	catch (adLDAPException $e) {
		$this->WLOG($e);
		return array();
	}

	$result=$adldap->user()->info($username);
	return $result;
}

private function ADLdap_init(){
	
	if($GLOBALS["VERBOSE"]){
		ini_set('display_errors', 1);
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
	}
	
	$this->SquidAddkerAlernates=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidAddkerAlernates"));
	if(class_exists("adLDAP")){return;}
	$lib=dirname(__FILE__)."/externals/adLDAP/adLDAP.php";
	if(!is_file($lib)){return array();}
	if($GLOBALS["VERBOSE"]){echo "Include $lib\n";}
	include($lib);
}

    private function AdConnectionsSSL(){
	    if(isset($GLOBALS["AdConnectionsSSL"])){return $GLOBALS["AdConnectionsSSL"];}
        $CONNECTIONS=array();
        $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
       if($ActiveDirectoryConnections){
           if(count($ActiveDirectoryConnections)>0) {
               foreach ($ActiveDirectoryConnections as $index => $main) {
                   $LDAP_SERVER = $main["LDAP_SERVER"];
                   if (!isset($main["LDAP_SSL"])) {
                       $main["LDAP_SSL"] = 0;
                   }
                   $CONNECTIONS[$LDAP_SERVER] = $main["LDAP_SSL"];
               }
           }
       }

        $KerbAuthInfos=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos");
        $array=unserialize(base64_decode($KerbAuthInfos));
        if(!is_array($array)){$array=array();}
        if(isset($array["LDAP_SERVER"])) {
            $LDAP_SERVER = $array["LDAP_SERVER"];
            if (!isset($array["LDAP_SSL"])) {
                $array["LDAP_SSL"] = 0;
            }
            $CONNECTIONS[$LDAP_SERVER] = $array["LDAP_SSL"];
        }
        $GLOBALS["AdConnectionsSSL"]=$CONNECTIONS;
        return $CONNECTIONS;
    }

private function ADLdap_options($settings=array()){
	$GLOBALS["KerbAuthInfos"]["RECURSIVE_GROUPS"]=1;
	$recursive=true;
	if(!isset($settings["LDAP_SUFFIX"])){
		if($GLOBALS["VERBOSE"]){echo "ADLdap_options:: Get default server...\n";}
		$this->BuildDefault();
		$array=$GLOBALS["KerbAuthInfos"];
	}else{
		$array=$settings;
		
	}
	$AdConnectionsSSL=$this->AdConnectionsSSL();


	$username=null;
	$account_suffix=null;
	$password=null;
	
	$GLOBALS["SUFFIX"]=$array["LDAP_SUFFIX"];
	if(isset($array["WINDOWS_SERVER_ADMIN"])){$username=$array["WINDOWS_SERVER_ADMIN"];}
	if(isset($array["WINDOWS_SERVER_PASS"])){$password=$array["WINDOWS_SERVER_PASS"];}
	if(isset($array["WINDOWS_DNS_SUFFIX"])){$account_suffix="@{$array["WINDOWS_DNS_SUFFIX"]}";}
	if(isset($array["LDAP_DN"])){$username=$array["LDAP_DN"];}
	if(isset($array["LDAP_PASSWORD"])){$password=$array["LDAP_PASSWORD"];}
			
	if($GLOBALS["VERBOSE"]){
	    foreach ($array as $key=>$val){
	        echo "ADLdap_options: [$key] = \"$val\" in line ".__LINE__."\n";
        }
    }
	
	if(strpos($username, "@")>0){
		$tb=explode("@",$username);
		$username=$tb[0];
		if($account_suffix==null){$account_suffix="@{$tb[1]}";}
	}
	
	
	$ad_port=389;
	if(isset($array["LDAP_PORT"])){
		if(intval($array["LDAP_PORT"])>0){$ad_port=$array["LDAP_PORT"];}
	}
	if(!isset($AdConnectionsSSL[$array["LDAP_SERVER"]])){
	    if($GLOBALS["VERBOSE"]){echo "Unable to check if {$array["LDAP_SERVER"]} is ssl or not L.".__LINE__."\n";}
    }else{
	    if($AdConnectionsSSL[$array["LDAP_SERVER"]]==1){
            if($ad_port==389){$ad_port=636;}
            $array["LDAP_SSL"]=1;
        }
    }

	
	if($GLOBALS["VERBOSE"]){echo "ADLdap_options:: SERVER = {$array["LDAP_SERVER"]}:$ad_port\n";}
	if($GLOBALS["VERBOSE"]){echo "ADLdap_options:: account_suffix = $account_suffix\n";}

    $use_ssl=false;
    if(isset($array["LDAP_SSL"])){
        if(intval($array["LDAP_SSL"])==1){$use_ssl=true;}
    }
    if($ad_port==636){
        if(!$use_ssl){$use_ssl=true;}
    }

    if(!isset($array["LDAP_SERVER2"])){$array["LDAP_SERVER2"]=null;}

    $domain_controllers[]=$array["LDAP_SERVER"];
    if($array["LDAP_SERVER2"]<>null){$domain_controllers[]=$array["LDAP_SERVER2"];}
	
	$options=array(
			'base_dn'=>$GLOBALS["SUFFIX"],
			'admin_username'=>$username,
			'admin_password'=>$password,
			'recursive_groups'=>true,
			'use_ssl'=>$use_ssl,
			'domain_controllers'=>$domain_controllers,
			'account_suffix'=>"$account_suffix",'ad_port'=>$ad_port);
	
	if($GLOBALS["VERBOSE"]){echo "ADLdap_options:: base_dn = {$GLOBALS["SUFFIX"]}\n";}
	if($GLOBALS["VERBOSE"]){echo "ADLdap_options:: ad_username = {$username}\n";}
	
	return $options;
	
}

private function ADLdap_options_workgroup($workgroup){
	$recursive=false;
	if($GLOBALS["KerbAuthInfos"]["RECURSIVE_GROUPS"]==1){
		if($GLOBALS["VERBOSE"]){echo "$workgroup: RECURSIVE_GROUPS = TRUE\n";}
		$recursive=true;
	}
	
	$array=$GLOBALS["KerbAuthInfos"];
	$Main=$this->SquidAddkerAlernates[$workgroup];
	
	if($Main["LDAP_DN"]==null){$Main["LDAP_DN"]=$array["WINDOWS_SERVER_ADMIN"];}
	if($Main["LDAP_PASSWORD"]==null){$Main["LDAP_PASSWORD"]=$array["WINDOWS_SERVER_PASS"];}
	
	if(strpos($Main["LDAP_DN"], "@")>0){
		$xplode=explode("@",$Main["LDAP_DN"]);
		$Main["LDAP_DN"]=$xplode[0];
		$array["WINDOWS_DNS_SUFFIX"]=$xplode[1];
	}
	
	if($GLOBALS["VERBOSE"]){echo "ADLdap_options_workgroup:: SERVER = {$Main["LDAP_SERVER"]}\n";}
	
	$options=array(
			'base_dn'=>$Main["LDAP_SUFFIX"],
			'admin_username'=>$Main["LDAP_DN"],
			'admin_password'=>$Main["LDAP_PASSWORD"],
			'recursive_groups'=>true,
			'domain_controllers'=>array($Main["LDAP_SERVER"]),
			'account_suffix'=>"@{$array["WINDOWS_DNS_SUFFIX"]}");
	
	return $options;
	
}

private function ObjectProperty($dn,$settings=array()){
	
	$this->ConnectToLDAP($settings);
	if(!isset($GLOBALS["QUERYTIME"])){$GLOBALS["QUERYTIME"]=time();}
	
	if(!$GLOBALS["BIND"]){
		$this->WLOG("[QUERY]: Error: BIND is broken -> reconnect");
		$this->ConnectToLDAP();
		if(!$GLOBALS["BIND"]){WLOG("[QUERY]: Error: BIND pointer is false");return false;}
	}
	
	if(!$GLOBALS["CONNECTION"]){
		$this->WLOG("[QUERY]: Error: CONNECTION is broken -> reconnect twice");
		$this->ConnectToLDAP();
	}
	
	if(!$GLOBALS["CONNECTION"]){
		$this->WLOG("[QUERY]: Error: CONNECTION is definitively broken aborting !!!...");
		return array();
	}
	
	
	$GLOBALS["QUERIES_NUMBER"]++;
	$link_identifier=$GLOBALS["CONNECTION"];
	$attrsonly=null;
	$sizelimit=null;

	
	
	if($GLOBALS["VERBOSE"]){echo "ObjectProperty():: ldap_read:: dn = $dn\n";}
	
	$res=@ldap_read($link_identifier,$dn,"(objectClass=*)",array(),null,null,20);
	if(!$res){
		$this->WLOG("[QUERY]: Error: LDAP search number " . ldap_errno($link_identifier) . "\nAction:LDAP search\ndn:$dn\n");
		$errstr=ldap_err2str(ldap_errno($link_identifier));
		if($GLOBALS["VERBOSE"]){echo "ObjectProperty():: ldap_read:: (objectClass=*) $errstr\n";}
		return array();
	}

	$hash=@ldap_get_entries($link_identifier,$res);
	$Config=$hash[0];
	for($i=0;$i<$Config["objectclass"]["count"];$i++){
		$types[$Config["objectclass"][$i]]=true;
			
	}
	$ARRAY["SOURCE"]=$Config;
	$ARRAY["description"]=null;
	$ARRAY["mail"]=null;

	$cn=utf8_decode($Config["cn"][0]);
	$ARRAY["cn"]=$cn;


	if(isset($Config["description"])){$ARRAY["description"]=utf8_decode($Config["description"][0]);}
	if(isset($types["group"])){
		$ARRAY["TYPE"]="group";
		$ARRAY["MEMBERS"]=$Config["member"]["count"];
		$ARRAY["name"]=$Config["name"][0];
		return $ARRAY;
	}
	if(isset($types["foreignSecurityPrincipal"])){
		$ARRAY["TYPE"]="group";
		return null;
	}
	if(isset($types["user"])){
		$ARRAY["TYPE"]="user";
	}


	$ARRAY["name"]=$Config["name"][0];
	$ARRAY["uid"]=$Config["samaccountname"][0];
	$ARRAY["displayname"]=$Config["displayname"][0];
	if(isset($Config["userprincipalname"])){$ARRAY["userprincipalname"]=$Config["userprincipalname"][0];}
	if(isset($Config["mail"][0])){$ARRAY["mail"]=$Config["mail"][0];}
	return $ARRAY;
}


public function AdLDAP_MembersFromGroup($dn,$settings=array()){
	$this->ADLdap_init();
	
	$options=$this->ADLdap_options($settings);
	try {
		$adldap = new adLDAP($options);
	}
	catch (adLDAPException $e) {
		$this->WLOG($e,__LINE__);
		return array();
	}
	
	try {
		if($GLOBALS["VERBOSE"]){echo "AdLDAP_MembersFromGroup auth as {$options["admin_username"]}\n";}
		$adldap->authenticate($options["admin_username"],$options["admin_password"]);}
		catch (adLDAPException $e) {
			$this->WLOG($e,__LINE__);
			return array();
	}
	
	if($GLOBALS["VERBOSE"]){echo "AdLDAP_MembersFromGroup ObjectProperty($dn...\n";}
	if(!isset($settings["samaccountname"])){
		$props=$this->ObjectProperty($dn,$settings);
		$name=$props["name"];
	}else{
		$name=$settings["samaccountname"];
	}
	
	if($GLOBALS["VERBOSE"]){echo "AdLDAP_MembersFromGroup: [$dn] -> [$name] Line:".__LINE__."\n";}

	$GLOBALS["PREVENT_LOOP"]=array();
	$array=$adldap->group()->members($name);
    if(!is_array($array)){$array=array();}
	if($GLOBALS["VERBOSE"]){echo "AdLDAP_MembersFromGroup $dn -> ".count($array)."\n";}
	return $array;
	}	
	public function AdLDAP_MembersFromGroupName($name){
		$this->ADLdap_init();
		$options=$this->ADLdap_options();
		try {
			$adldap = new adLDAP($options);
		}
		catch (adLDAPException $e) {
			$this->WLOG($e);
			return array();
		}
	
		try {
			if($GLOBALS["VERBOSE"]){echo "AdLDAP_MembersFromGroupName authenticate -> {$options["ad_username"]}\n";}
			$adldap->authenticate($options["ad_username"],$options["ad_password"]);}
			catch (adLDAPException $e) {
				$this->WLOG($e);
				return array();
			}
	
			$array=$adldap->group()->members($name);
			if($GLOBALS["VERBOSE"]){echo "AdLDAP_MembersFromGroup -> ".count($array)."\n";}
			return $array;
	}	
	

	
	
	
private function ConnectToLDAP($settings=array()){
	
	if(!isset($settings["LDAP_SERVER"])){
		$this->BuildDefault();
		$array=$GLOBALS["KerbAuthInfos"];
	}else{
		$array=$settings;
	}
	if(!is_array($array)){
		$this->WLOG("KerbAuthInfos not an array");
		return false;
	}	
	
	if(!isset($array["LDAP_SERVER"])){$this>WLOG("LDAP_SERVER not set");return;}
	if(!isset($array["LDAP_SUFFIX"])){$this->WLOG("LDAP_SUFFIX not set");return;}
    $error=null;
	$GLOBALS["SUFFIX"]=$array["LDAP_SUFFIX"];
	if($GLOBALS["VERBOSE"]){echo "ConnectToLDAP -> {$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}\n";}





	$GLOBALS["CONNECTION"]=@ldap_connect($array["LDAP_SERVER"],$array["LDAP_PORT"]);
	//WLOG("[LDAP]: Connecting to LDAP server `{$array["LDAP_SERVER"]}:{$array["LDAP_PORT"]}`");
	if(!$GLOBALS["CONNECTION"]){
		$this->WLOG("[LDAP]: Fatal: ldap_connect({$array["LDAP_SERVER"]},{$array["LDAP_PORT"]} )");
		return false;
	}	
	
	//WLOG("[LDAP]: Connecting to LDAP server {$array["LDAP_SERVER"]} <span style='font-weight:bold;color:#00B218'>success</span> with suffix:&laquo;{$GLOBALS["SUFFIX"]}&raquo;");
	@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_PROTOCOL_VERSION, 3);
	@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);	
	@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
	@ldap_set_option($GLOBALS["CONNECTION"], LDAP_OPT_REFERRALS, 0);
	
	if(isset($array["WINDOWS_SERVER_ADMIN"])){
		if(preg_match("#^(.+?)\/(.+?)$#", $array["WINDOWS_SERVER_ADMIN"],$re)){$array["WINDOWS_SERVER_ADMIN"]=$re[1];}
		if(preg_match("#^(.+?)\\\\(.+?)$#", $array["WINDOWS_SERVER_ADMIN"],$re)){$array["WINDOWS_SERVER_ADMIN"]=$re[1];}
		$username="{$array["WINDOWS_SERVER_ADMIN"]}@{$array["WINDOWS_DNS_SUFFIX"]}";
		$password=$array["WINDOWS_SERVER_PASS"];
	}else{
		if(isset($array["LDAP_DN"])){$username=$array["LDAP_DN"];}
		if(isset($array["LDAP_PASSWORD"])){$password=$array["LDAP_PASSWORD"];}
	}
	
	if($GLOBALS["VERBOSE"]){echo "ConnectToLDAP -> BIND as $username\n";}
	
	$GLOBALS["BIND"]=@ldap_bind($GLOBALS["CONNECTION"], $username, $password);
	if(!$GLOBALS["BIND"]){
		if($GLOBALS["VERBOSE"]){echo "ConnectToLDAP -> BIND FAILED\n";}
		if (@ldap_get_option($GLOBALS["CONNECTION"], LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error=$error."$extended_error";
		}
		$ldap_errno=@ldap_errno($GLOBALS["CONNECTION"]);
		switch ($ldap_errno) {
			case 0x31:
				$error=$error . "Bad username or password. Please try again.";
				break;
			case 0x32:
				$error=$error . "Insufficient access rights.";
				break;
			case 81:
				$error=$error . "Unable to connect to the LDAP server
				{$array["LDAP_SERVER"]}please,verify if ldap daemon is running or the ldap server address";
				break;
			case -1:
					
				break;
			default:
				$error=$error . "Could not bind to the LDAP server." ."". @ldap_err2str($ldap_errno);
		}
		$this->WLOG("[LDAP]:".__LINE__." Connecting to LDAP server {$array["LDAP_SERVER"]} failed $error");
		return false;
	}
	if($GLOBALS["VERBOSE"]){echo "ConnectToLDAP -> BIND SUCCESS\n";}
	return true;
}


}