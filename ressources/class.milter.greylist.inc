<?php
include_once(dirname(__FILE__)."/class.ldap.inc");
include_once(dirname(__FILE__)."/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/class.mysql.inc");;
include_once(dirname(__FILE__)."/class.tcpip.inc");
include_once(dirname(__FILE__)."/class.main_cf.inc");
include_once(dirname(__FILE__).'/class.postgres.inc');
class milter_greylist{
	var $hostname;
	var $ou;
	var $dn;
	var $global_conf;
	var $main_array;
	var $acl;
	var $dnsrbl=array();
	var $dnsrbl_array;
	var $dnsrbl_class;
	var $MilterGreyListEnabled;
	var $MilterGreyListUseTCPPort;
	var $MilterGeryListTCPPort=0;
	var $__noparse;
	var $mynetworks=array();
	var $MultipleInstance=false;
	var $ip_addr=null;
	var $MiltergreyListAddDefaultNets=1;
	var $AVAILABLE_OBJECTS=array();
	var $actionlist=array(
							null=>"{select}",
							"addr"=>"{addr}",
							"urlcheck"=>"{urlcheck}",
							"body"=>"{body}",
							"header"=>"{header}",
							"domain"=>"{milter_greylist_domain}",
							"from"=>"{milter_greylist_from}",
							"rcpt"=>"{milter_greylist_rcpt}",
							"dnsrbl"=>"{dnsrbl}",
							"geoip"=>"{geoip}"
							);
	
	
function __construct($noparse=false,$hostname=null,$ou=null){
	$this->hostname=$hostname;
	$this->__noparse=$noparse;
	if($hostname=="master"){$this->hostname=null;$this->ou=null;}
	
	
	
	$this->ou=$ou;
	if($noparse){
		$this->loadConfig();
		return true;
		}
	

	$this->loadConfig();
	$this->ParseConfig();
	
}




function LoadConfig(){
	
	if(($this->hostname<>null) && ($this->ou<>null)){
		WriteLogs("Multi-postfix instance: $this->hostname, $this->ou",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$main=new maincf_multi($this->hostname,$this->ou);
		$this->global_conf=base64_decode($main->GET_BIGDATA("milter-greylist-config"));
		$this->mynetworks=unserialize($main->GET_BIGDATA("mynetworks"));
		$this->MiltergreyListAddDefaultNets=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MiltergreyListAddDefaultNets");
		if(!is_numeric($this->MiltergreyListAddDefaultNets)){$this->MiltergreyListAddDefaultNets=1;}
		
		return ;
	}
	
	$sock=new sockets();
	$ldap=new clladp();
	if(!$ldap->ldapFailed){$this->mynetworks=$ldap->load_mynetworks();}else{WriteLogs("LDAP failed !!!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	WriteLogs("Loading GreyListConf ->GET_INFO('GreyListConf')",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	$this->global_conf=base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GreyListConf"));
	$this->MilterGreyListEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO('MilterGreyListEnabled');
	$main=new maincf_multi("master","master");
	$this->MiltergreyListAddDefaultNets=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MiltergreyListAddDefaultNets");
	$this->MilterGreyListUseTCPPort=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListUseTCPPort");
	$this->MilterGeryListTCPPort=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGeryListTCPPort");
	if(!is_numeric($this->MiltergreyListAddDefaultNets)){$this->MiltergreyListAddDefaultNets=1;}
	if(!is_numeric($this->MilterGreyListUseTCPPort)){$this->MilterGreyListUseTCPPort=0;}
	if(!is_numeric($this->MilterGeryListTCPPort)){$this->MilterGeryListTCPPort=0;}
	if($this->MilterGeryListTCPPort==0){
		$this->MilterGeryListTCPPort=rand(11000,50000);
		$sock->SET_INFO("MilterGeryListTCPPort", $this->MilterGeryListTCPPort);
	}
		
}

function SaveToLdap(){
		$sock=new sockets();	
	if(($this->hostname<>null) && ($this->ou<>null)){
		WriteLogs("Multi-postfix instance: $this->hostname, $this->ou",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$main=new maincf_multi($this->hostname,$this->ou);
		$this->ip_addr=$main->ip_addr;
		$this->MultipleInstance=true;
		$main->SET_BIGDATA("milter-greylist-config",base64_encode($this->BuildConfig()));
		$main->SET_VALUE("MiltergreyListAddDefaultNets", $this->MiltergreyListAddDefaultNets);
		$sock->getFrameWork("cmd.php?milter-greylist-reconfigure=yes");		
		return ;
	}	
	
	$main=new maincf_multi("master","master");
	$main->SET_VALUE("MiltergreyListAddDefaultNets", $this->MiltergreyListAddDefaultNets);
	WriteLogs("Single instance: $this->hostname, $this->ou",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	$sock->SaveConfigFile(base64_encode($this->BuildConfig()),"GreyListConf");	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?milter-greylist-reconfigure=yes&hostname=$this->hostname&ou=".base64_encode($this->ou));
	$sock->getFrameWork("postfix.php?smtpd-client-restrictions=yes");
	// --smtpd-client-restrictions
	
}

function getAclContent($id){
	$sql="SELECT * FROM miltergreylist_acls WHERE id='$id'";	
	$q=new postgres_sql();
	$ligne=$q->mysqli_fetch_array($sql);
		if(trim($ligne["pattern"])==null){
			if($GLOBALS["VERBOSE"]){echo "{warning} getAclContent($id) pattern is null for {$ligne["method"]} {$ligne["type"]}\n";}
			return;
		}	
	return array(

		"method"=>$ligne["method"],
		"type"=>$ligne["type"],
		"pattern"=>$ligne["pattern"],
		"description"=>$ligne["description"],
		"full"=>"acl {$ligne["method"]} {$ligne["type"]} {$ligne["pattern"]} #ID:{$ligne["id"]}/".__LINE__." {$ligne["description"]}"
	
	);
	
	
}


function ParseConfig(){
	WriteLogs("Parsing start",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	if(trim($this->global_conf)==null){
		WriteLogs("global_conf = null -> ->BuildConfig()",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		$this->global_conf=$this->BuildConfig();
	}
	
	$greylist=$this->regconf("greylist");
	if(preg_match('#([0-9]+)(h|m|d)#',$greylist,$re)){
		$this->main_array["greylist"]=$re[1];
		$this->main_array["greylist_TIME"]=$re[2];
	}
	
	$this->main_array["lazyaw"]=$this->regconfBool("lazyaw");
	
	$this->main_array["autowhite"]=$this->regconf("autowhite");
	if(preg_match('#([0-9]+)(h|m|d)#',$this->main_array["autowhite"],$re)){
		$this->main_array["autowhite"]=$re[1];
		$this->main_array["autowhite_TIME"]=$re[2];
		}	
	
	$this->main_array["timeout"]=$this->regconf("timeout");
		if(preg_match('#([0-9]+)(h|m|d)#',$this->main_array["timeout"],$re)){
		$this->main_array["timeout"]=$re[1];
		$this->main_array["timeout_TIME"]=$re[2];
		}	

	$this->main_array["activate_sync_port"]=$this->regconfBool("syncaddr");
	if($GLOBALS["VERBOSE"]){echo "->Parsepeer()\n";}
	$this->Parsepeer();
	if($GLOBALS["VERBOSE"]){echo "->Parse_Acls()\n";}
	$this->Parse_Acls();
	if(!is_array($this->dnsrbl_array)){$this->defaultDnsrbl();}
	
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." milter-greylist::this->BuildDefault() in ".__FUNCTION__."\n";}
	$this->BuildDefault();
	WriteLogs("Parsing end",__CLASS__.'/'.__FUNCTION__,__FILE__);
}

function Parsepeer(){
	$this->main_array["peer"]=array();
	$t=explode("\n",$this->global_conf);
    foreach ($t as $ligne){
			if(preg_match("#^peer\s+(.+)#",$ligne,$re)){
				$this->main_array["peer"][trim($re[1])]=trim($re[1]);
			}
		}
}


function BuildDefault(){
	if(!isset($this->main_array["greylist_TIME"])){$this->main_array["greylist_TIME"]=null;}
	if(!isset($this->main_array["autowhite_TIME"])){$this->main_array["autowhite_TIME"]=null;}
	if(!isset($this->main_array["timeout_TIME"])){$this->main_array["timeout_TIME"]=null;}
	if(!isset($this->main_array["timeout"])){$this->main_array["timeout"]=null;}
	if(!isset($this->main_array["greylist"])){$this->main_array["greylist"]=null;}
	if(!isset($this->main_array["autowhite"])){$this->main_array["autowhite"]=null;}
	
	if($this->main_array["greylist_TIME"]==null){$this->main_array["greylist_TIME"]="h";}
	if($this->main_array["autowhite_TIME"]==null){$this->main_array["autowhite_TIME"]="d";}
	if($this->main_array["timeout_TIME"]==null){$this->main_array["timeout_TIME"]="h";}
	if($this->main_array["autowhite"]==null){$this->main_array["autowhite"]=3;}	
	if($this->main_array["timeout"]==null){$this->main_array["timeout"]=5;}
	if($this->main_array["greylist"]==null){$this->main_array["greylist"]=1;}	
	

	
}

function Parse_Acls(){
		if($GLOBALS["VERBOSE"]){echo "Parsing acls in ". strlen($this->global_conf) ." bytes data\n";}
		$hostname=$this->hostname;
		if($hostname==null){$hostname="master";}
		$q=new postgres_sql();
		$sql="SELECT COUNT(id) as tcount FROM miltergreylist_acls WHERE instance='$hostname'";
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." milter-greylist::DEBUG::".__FUNCTION__."::$sql\n";}
		$ligne=pg_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if($ligne["tcount"]==0){$this->DefaultAcls();}
		$t=explode("\n",$this->global_conf);
        
		foreach ($t as $ligne){
			if(preg_match("#^dnsrbl\s+#",$ligne)){$this->dnsrbl[]=$ligne;}
			}
		$this->__Parse_DNSBL();
	}

function Parse_syncserver(){
	$t=explode("\n",$this->global_conf);
		foreach ($t as $ligne){
			if(preg_match("#^(racl|dacl|acl)#",$ligne)){$this->acl[]=$ligne;}
			if(preg_match("#^dnsrbl\s+#",$ligne)){$this->dnsrbl[]=$ligne;}
			}
		$this->__Parse_DNSBL();
	}

function regconf($value){
	
	$t=explode("\n",$this->global_conf);
		foreach ($t as $ligne){
			if(preg_match("#^$value\s+([0-9a-z]+)#",$ligne,$re)){
				WriteLogs("$value={$re[1]}",__CLASS__.'/'.__FUNCTION__,__FILE__);
				return $re[1];
			}
		}
	
}
function regconfBool($value){
	
	$t=explode("\n",$this->global_conf);
		foreach ($t as $ligne){
			if(preg_match("#^$value#",$ligne,$re)){
				writelogs("Found $ligne for $value return 1",__CLASS__.'/'.__FUNCTION__,__FILE__);
				return 1;
				
			}
		}
	writelogs("return 0 for $value",__CLASS__.'/'.__FUNCTION__,__FILE__);
	return 0;
}

private function buildObjectsItems($ID,$type){
	$sql="SELECT item FROM miltergreylist_items WHERE enabled=1 AND groupid='$ID'";
	$q=new mysql();
	$items=array();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	$quotes='"';
	if($type=="addr"){$quotes=null;}
	if($type=="rcpt"){$quotes=null;}
	
	if($GLOBALS["ROOT"]){
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." milter-greylist $this->hostname buildObjectsItems() error $q->mysql_error\n";return null;}
		
	}
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
	
		if($this->isaregex($ligne["item"])){
			echo "Starting......: ".date("H:i:s")." milter-greylist {$ligne["item"]} -> REGEX\n";
			$items[]="/".$this->string_to_regex($ligne["item"])."/";
			continue;
		}
		$items[]="$quotes{$ligne["item"]}$quotes";
	}
	
	if($GLOBALS["ROOT"]){echo "Starting......: ".date("H:i:s")." milter-greylist $this->hostname ". count($items)." items\n";}
	if(count($items)==0){return null;}
	return @implode(" ", $items);
}


private function buildObjects(){
	if(!$GLOBALS["ROOT"]){return;}
	if($this->hostname==null){$this->hostname="master";}
	$sql="SELECT ID,objectname,type FROM miltergreylist_objects WHERE enabled=1 AND instance='$this->hostname'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if($GLOBALS["ROOT"]){echo "Starting......: ".date("H:i:s")." milter-greylist $this->hostname ". mysqli_num_rows($results)." objects\n";}
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($ligne["type"]=="dnsrbl"){continue;}
		
		$items=$this->buildObjectsItems($ligne["ID"],$ligne["type"]);
		
		if($items==null){if($GLOBALS["ROOT"]){echo "Starting......: ".date("H:i:s")." milter-greylist $this->hostname skipping object \"{$ligne["objectname"]}\"\n";}continue;}
		if($GLOBALS["ROOT"]){echo "Starting......: ".date("H:i:s")." milter-greylist $this->hostname building object \"{$ligne["objectname"]}\"\n";}
		$f[]="list \"{$ligne["objectname"]}\" {$ligne["type"]} { {$items} }";
		$this->AVAILABLE_OBJECTS[$ligne["ID"]]="{$ligne["objectname"]}";
		
	}
	return @implode("\n", $f);
	
}
private function regex_fix($pattern){
	$pattern=str_replace('s.fr','s\.fr',$pattern);
	$pattern=str_replace('e.com','e\.com',$pattern);
	$pattern=str_replace('].com',']\.com',$pattern);
	$pattern=str_replace('].eu',']\.eu',$pattern);
	$pattern=str_replace('].ru',']\.ru',$pattern);
	$pattern=str_replace('].info',']\.info',$pattern);
	$pattern=str_replace(']+.com',']+\.com',$pattern);
	$pattern=str_replace(']+.cf',']+\.cf',$pattern);
	$pattern=str_replace(']+.net',']+\.net',$pattern);
	$pattern=str_replace(']+.me',']+\.me',$pattern);
	$pattern=str_replace(']+.co.vu',']+\.co\.vu',$pattern);
	return $pattern;
}

private function is_regex($pattern){
	$pattern_t="   $pattern";
	
	if(preg_match("#regex:\s+(.+)#", trim($pattern),$re)){
		$re[1]=$this->regex_fix($re[1]);
		return " /".trim($re[1])."/";
	}
	if(preg_match("#regex:(.+)#", trim($pattern),$re)){
		$re[1]=$this->regex_fix($re[1]);
		return " /".trim($re[1])."/";
	}

	if(preg_match("#^regex\s+(.+)#", trim($pattern),$re)){
		$re[1]=$this->regex_fix($re[1]);
		return " /".trim($re[1])."/";
	}
	if(preg_match("#^regex(.+)#", trim($pattern),$re)){
		$re[1]=$this->regex_fix($re[1]);
		return " /".trim($re[1])."/";
	}
	

	
	if(strpos($pattern_t, '[')>0){
		$pattern=$this->regex_fix($pattern);
		return " /$pattern/";
	}
	
	if(strpos($pattern_t, '.*')>0){
		$pattern=$this->regex_fix($pattern);
		return " /$pattern/";
	}	
	
	if(strpos($pattern_t, '*')>0){
		return " /".$this->string_to_regex(trim($pattern))."/";
	}
	

	
	return $pattern;
}

private function string_to_regex($pattern){
	if(trim($pattern)==null){return null;}
	$pattern=str_replace("/", "\/", $pattern);
	$pattern=str_replace(".", "\.", $pattern);
	$pattern=str_replace("-", "\-", $pattern);
	$pattern=str_replace("[", "\[", $pattern);
	$pattern=str_replace("]", "\]", $pattern);
	$pattern=str_replace("(", "\(", $pattern);
	$pattern=str_replace(")", "\)", $pattern);
	$pattern=str_replace("$", "\$", $pattern);
	$pattern=str_replace("?", "\?", $pattern);
	$pattern=str_replace("#", "\#", $pattern);
	$pattern=str_replace("{", "\{", $pattern);
	$pattern=str_replace("}", "\}", $pattern);
	$pattern=str_replace("^", "\^", $pattern);
	$pattern=str_replace("!", "\!", $pattern);
	$pattern=str_replace("*", ".*?", $pattern);
	
	return $pattern;
	
}

private function check_patternvalues_domain($pattern,$type){
	if($GLOBALS["VERBOSE"]){echo "Check `$pattern`:`$type`\n";}
	if(strpos($pattern, '@')>0){
		if($GLOBALS["ROOT"]){echo "Starting......: ".date("H:i:s")." milter-greylist field `pattern` `$pattern` is an email address, switch to from\n";}
		return "from";}
	
	if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $pattern)){
		if($GLOBALS["ROOT"]){echo "Starting......: ".date("H:i:s")." milter-greylist field `pattern` `$pattern` is an ip address, switch to addr\n";}
		return "addr";
	}
	
	if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\/[0-9]+$#", $pattern)){
		if($GLOBALS["ROOT"]){echo "Starting......: ".date("H:i:s")." milter-greylist field `pattern` `$pattern` is an ip address, switch to addr\n";}
		return "addr";
	}	
						
	return $type;
	
}


private function isaregex($pattern):bool{
	$pattern=" $pattern";
	if(strpos($pattern, "*")>0){return true;}
	if(strpos($pattern, "[")>0){return true;}
	if(strpos($pattern, "]")>0){return true;}
	if(strpos($pattern, "(")>0){return true;}
	if(strpos($pattern, ")")>0){return true;}
	if(strpos($pattern, "?")>0){return true;}
    return false;
}

private function acl_from_emailToPattern($from){
	$IP=new IP();
	$from=replace_accents($from);
	if(preg_match("#.*?@.*?@#", $from)){return null;}
	
	$from=str_replace("<", "", $from);
	$from=str_replace(">", "", $from);
	
	if(strpos($from, ",")>0){
		$tbl=explode(",",$from);
		$from=$tbl[0];
	}
	$from=str_replace("'","",$from);
	if(!preg_match("#(.*?)@(.+)#", $from,$re)){ $re[1]=".*?"; $re[2]=$from; }
	$prefix_email=$re[1];
	$suffix_email=$re[2];
	$orginal_suffix=$re[2];
	
	$orginal_suffix=str_replace("\\", "", $orginal_suffix);
	if($IP->isIPAddress($suffix_email)){
		return "addr $suffix_email";
		
	}
	$suffix_email=str_replace(".", "\.", $suffix_email);
	if($prefix_email==null){$prefix_email=".*";}
		
	if($prefix_email==".*"){
		return "from /.*@$suffix_email/";
		
	}
		
	if(!$this->isaregex("$prefix_email@$orginal_suffix")){
		$pattern=strtolower("$prefix_email@$orginal_suffix");
		return "from $pattern";
	}
		
	$prefix_email=string_to_regex($prefix_email);
	return "from /$prefix_email@$suffix_email/";
}
function BuildConfig(){
	$sock=new sockets();
	$MimeDefangEnabled=intval($sock->GET_INFO('MimeDefangEnabled'));
	$MimeDefangAutoWhiteList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangAutoWhiteList"));
	if($MimeDefangEnabled==0){$MimeDefangAutoWhiteList=0;}
			if($this->hostname==null){$this->hostname="master";}
			if(preg_match('#([0-9]+)#',$this->main_array["greylist"],$re)){$this->main_array["greylist"]=$re[1];}
			if(preg_match('#([0-9]+)#',$this->main_array["timeout"],$re)){$this->main_array["timeout"]=$re[1];}
			if(preg_match('#([0-9]+)#',$this->main_array["autowhite"],$re)){$this->main_array["autowhite"]=$re[1];}
			$this->BuildDefault();
			$this->acl[]=array();
			$Ipclass=new IP();
			$main=new main_cf();
			$array=$main->array_mynetworks;
            foreach ($main->array_mynetworks as $net){
				if(!$Ipclass->isIPAddressOrRange($net)){
					$this->acl[]="# acl whitelist addr $net # isIPAddressOrRange ! FALSE";
					continue;
				}
				$this->acl[]="acl whitelist addr $net # From MyNetwork";
			}
			
			
			$this->__Build_DNSBL();
			
			
			$array_acl=array();
			$objects=$this->buildObjects();
			if(!isset($this->dnsrbl)){$this->dnsrbl=null;}
			
			$c=0;
			
			
			
			
			
			
			
			$sock=new sockets();
	
			
			if($this->MiltergreyListAddDefaultNets==1){$this->multinets();}
			
			
			if(!is_array($this->dnsrbl)){
				$this->defaultDnsrbl();
			}

		
			$conf=null;	
			
			$conf=$conf ."# Greylisting config file with a lot of options explained\n";
			$conf=$conf ."#\n";
			$conf=$conf ."# \$Id: greylist2.conf,v 1.1 2006/08/20 04:49:49 manu Exp $\n";
			$conf=$conf ."#\n";
			$conf=$conf ."\n";
			$conf=$conf ."# Uncomment this to enable debug output.\n";
			$conf=$conf ."# Note that options appearing before the \"verbose\" option in this\n";
			$conf=$conf ."# file will not be treated verbosely.\n";
			$conf=$conf ."# May be overridden by the \"-v\" command line argument.\n";
			$conf=$conf ."#verbose\n";
			$conf=$conf ."\n";
			$conf=$conf ."# If you work with multiple MXs, list them with\n";
			$conf=$conf ."# peer entries to enable greylist sync among the MXs.\n";
			$conf=$conf ."#peer 192.0.2.17\n";
			$conf=$conf ."#peer 192.0.2.18\n";
			if(isset($this->main_array["peer"])){
				if(is_array($this->main_array["peer"])){
                    foreach ($this->main_array["peer"] as $num=>$ligne){
						if(trim($num)==null){continue;}
						$conf=$conf ."peer $num\n";
					}
					
				}
			}
	

			$conf=$conf ."\n";
			$conf=$conf ."\n";
			$conf=$conf ."# You may wish to use a specific local address or port for\n";
			$conf=$conf ."# syncing between MXs. Of course one of your interfaces\n";
			$conf=$conf ."# must have the address assigned. An '*' for the address\n";
			$conf=$conf ."# means any address.\n";
			$conf=$conf ."#syncaddr *\n";
			$conf=$conf ."#syncaddr * port 7689\n";
			$conf=$conf ."#syncaddr 192.0.2.2\n";
			$conf=$conf ."#syncaddr 192.0.2.2 port 9785\n";
			$conf=$conf ."#syncaddr 2001:db8::1:c3b5:123\n";
			$conf=$conf ."#syncaddr 2001:db8::1:c3b5:123 port 1234\n";
			if(!isset($this->main_array["activate_sync_port"])){$this->main_array["activate_sync_port"]=0;}
			if(!isset($this->main_array["peer"])){$this->main_array["peer"]=array();}
			
			if(count($this->main_array["peer"])>0){$this->main_array["activate_sync_port"]=1;}
			if($this->main_array["activate_sync_port"]==1){
				if($GLOBALS["ROOT"]){echo "Starting......: ".date("H:i:s")." milter-greylist sync port is enabled\n";}
				if($this->ip_addr==null){
					$conf=$conf ."syncaddr * port 5252\n";
				}else{
					$conf=$conf ."syncaddr $this->ip_addr port 5252\n";
				}
			}else{
				if($GLOBALS["ROOT"]){echo "Starting......: ".date("H:i:s")." milter-greylist sync port is not enabled\n";}	
			}
			
			
			$conf=$conf ."\n";
			$conf=$conf ."# Specific IP-Address for outbound sync-connections\n";
			$conf=$conf ."# between MXs. If blank, system selects an ip.\n";
			$conf=$conf ."# syncsrcaddr 123.45.678.9\n";
			$conf=$conf ."\n";
			$conf=$conf ."acl whitelist addr 127.0.0.0/8\n";
			$conf=$conf ."acl whitelist addr 10.0.0.0/8\n";
			$conf=$conf ."acl whitelist addr 172.16.0.0/12\n";
			$conf=$conf ."acl whitelist addr 192.168.0.0/16\n";

            $users=new usersMenus();
            $EnableMilterGreylistExternalDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterGreylistExternalDB"));
    $conf = $conf . "# EnableMilterGreylistExternalDB=$EnableMilterGreylistExternalDB\n";
    if($users->CORP_LICENSE) {
        if ($EnableMilterGreylistExternalDB == 1) {

            $conf = $conf . "dnsrbl \"ARTICAWHITE\" rblquery.artica.center 127.0.0.9\n";
            $conf = $conf . "acl whitelist dnsrbl  \"ARTICAWHITE\"\n";
        }
    }

            $q=new postgres_sql();
    $sql="SELECT id,pattern FROM miltergreylist_acls WHERE method='whitelist' AND type='rcpt'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if($pattern==null){continue;}
        if(preg_match("#regex:\s+#", $pattern)){
            $conf=$conf ."acl whitelist rcpt /$pattern/\n";
            continue;
        }
        $pattern=str_replace(".","\.",$pattern);
        $pattern=str_replace("*",".*?",$pattern);
        $conf=$conf ."acl whitelist rcpt /$pattern/\n";
    }

    $sql="SELECT id,pattern FROM miltergreylist_acls WHERE method='whitelist' AND type='from'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if($pattern==null){continue;}
        if(preg_match("#regex:\s+#", $pattern)){
            $conf=$conf ."acl whitelist rcpt /$pattern/\n";
            continue;
        }
        $pattern=str_replace(".","\.",$pattern);
        $pattern=str_replace("*",".*?",$pattern);
        $conf=$conf ."acl whitelist from /$pattern/\n";
    }

    $sql="SELECT id,pattern FROM miltergreylist_acls WHERE method='whitelist' AND type='domain'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if($pattern==null){continue;}
        if(preg_match("#regex:\s+#", $pattern)){
            $conf=$conf ."acl whitelist rcpt /$pattern/\n";
            continue;
        }
        $pattern=str_replace(".","\.",$pattern);
        $pattern=str_replace("*",".*?",$pattern);
        $conf=$conf ."acl whitelist from /.*?$pattern/\n";
    }

    $ipClass=new IP();
    $sql="SELECT id,pattern FROM miltergreylist_acls WHERE method='whitelist' AND type='addr'";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $pattern=trim(strtolower($ligne["pattern"]));
        if($pattern==null){continue;}
        if(!$ipClass->isIPAddressOrRange($pattern)){continue;}
        $conf=$conf ."acl whitelist addr $pattern\n";
    }



			$conf=$conf ."\n";
			$conf=$conf ."# This option tells milter-greylist when it should\n";
			$conf=$conf ."# add an X-Greylist header. Default is all, which\n";
			$conf=$conf ."# causes a header to always be added. Other possible\n";
			$conf=$conf ."# values are none, delays and nodelays\n";
			$conf=$conf ."report all\n";
			$conf=$conf ."\n";
			$conf=$conf ."# This option attempts to make milter-greylist more\n";
			$conf=$conf ."# friendly with sender callback systems. When the\n";
			$conf=$conf ."# message is from <>, it will be temporarily\n";
			$conf=$conf ."# rejected at the DATA stage instead of the RCPT\n";
			$conf=$conf ."# stage of the SMTP transaction. In the case of a\n";
			$conf=$conf ."# multi recipient DSN, whitelisted recipient will\n";
			$conf=$conf ."# not be honoured.\n";
			$conf=$conf ."#delayedreject\n";
			$conf=$conf ."\n";
			$conf=$conf ."# Uncomment if you want auto-whitelist to work for\n";
			$conf=$conf ."# the IP rather than for the (IP, sender, receiver)\n";
			$conf=$conf ."# tuple.\n";
			
			
			
			$MilterGreyListLazyaw=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListLazyaw"));
			
			if($MilterGreyListLazyaw==1){
				$conf=$conf ."lazyaw\n";
			}else{
				$conf=$conf ."#lazyaw\n";
			}
			$conf=$conf ."\n";
			$conf=$conf ."# How often should we dump to the dumpfile (0: on each change, -1: never).\n";
			$conf=$conf ."#dumpfreq 10m\n";
			$conf=$conf ."\n";
			$conf=$conf ."# This option disables the conversion of the time specified in the\n";
			$conf=$conf ."# integer format to humanly readable format in the comment of each\n";
			$conf=$conf ."# line in the dumpfile.\n";
			$conf=$conf ."# Time needed in order to dump large dumpfiles (several milion \n";
			$conf=$conf ."# entries/few 100's of MB) can be significantly improved.\n";
			$conf=$conf ."dump_no_time_translation\n";
			$conf=$conf ."\n";
			$conf=$conf ."# This option causes greylist entries that expire to be logged via\n";
			$conf=$conf ."# syslog.  This allows you to collect the IP addresses and sender\n";
			$conf=$conf ."# names and use them for blacklisting, SPAM scoring, etc.\n";
			$conf=$conf ."logexpired\n";
			
			$MilterGreyListTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListTimeOut"));
			if($MilterGreyListTimeOut==0){$MilterGreyListTimeOut=5;}
			$MilterGreyListGreyTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListGreyTime"));
			if($MilterGreyListGreyTime==0){$MilterGreyListGreyTime=5;}
			$MilterGreyListAutoWhite=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListAutoWhite"));
			

			$conf=$conf ."# How long will the greylist database retain tuples.\n";
			$conf=$conf ."timeout {$MilterGreyListTimeOut}d\n";
			$conf=$conf ."\n";
			$conf=$conf ."# Do not use \${greylist} macros from sendmail's access DB.\n";
			$conf=$conf ."#noaccessdb\n";
			$conf=$conf ."\n";
			$conf=$conf ."# Use extended regular expressions instead of basic\n";
			$conf=$conf ."# regular expressions.\n";
			$conf=$conf ."#extendedregex\n";
			$conf=$conf ."\n";
			$conf=$conf ."#\n";
			$conf=$conf ."# All of the following options have command-line equivalents.\n";
			$conf=$conf ."# See greylist.conf(5) for the exact equivalences.\n";
			$conf=$conf ."#\n";
			$conf=$conf ."\n";
			$conf=$conf ."# How long a client has to wait before we accept\n";
			$conf=$conf ."# the messages it retries to send. Here, 1 hour.\n";
			$conf=$conf ."# May be overridden by the \"-w greylist_delay\" command line argument.\n";
			$conf=$conf ."greylist {$MilterGreyListGreyTime}m\n";
			$conf=$conf ."\n";
			$conf=$conf ."# How long does auto-whitelisting last (set it to 0\n";
			$conf=$conf ."# to disable auto-whitelisting). Here, 3 days.\n";
			$conf=$conf ."# May be overridden by the \"-a autowhite_delay\" command line argument.\n";
			$conf=$conf ."autowhite {$MilterGreyListAutoWhite}d\n";
			$conf=$conf ."\n";
			$conf=$conf ."#subnetmatch /24\n";
			$conf=$conf ."#subnetmatch6 /64\n";
			$conf=$conf ."\n";
			$conf=$conf ."# Normally, clients that succeed SMTP AUTH are not\n";
			$conf=$conf ."# greylisted. Uncomment this if you want to\n";
			$conf=$conf ."# greylist them regardless of SMTP AUTH.\n";
			$conf=$conf ."# May be overridden by the \"-A\" command line argument.\n";
			$conf=$conf ."#noauth\n";
			$conf=$conf ."\n";
			$conf=$conf ."# If milter-greylist was built with SPF support, then\n";
			$conf=$conf ."# SPF-compliant senders are not greylisted. Uncomment\n";
			$conf=$conf ."# this to greylist them regardless of SPF compliance.\n";
			$conf=$conf ."# May be overridden by the \"-S\" command line argument.\n";
			$conf=$conf ."#nospf\n";
			$conf=$conf ."\n";
			$conf=$conf ."# If milter-greylist was built with DRAC support, \n";
			$conf=$conf ."# then DRAC DB location can be specified here\n";
			$conf=$conf ."#drac db \"/usr/local/etc/drac.db\"\n";
			$conf=$conf ."\n";
			$conf=$conf ."# Uncomment this to disable DRAC\n";
			$conf=$conf ."#nodrac\n";
			$conf=$conf ."\n";
			$conf=$conf ."pidfile \"/var/run/milter-greylist.pid\"\n";
			$conf=$conf ."socket \"/var/run/milter-greylist/milter-greylist.sock\"\n";
			$conf=$conf ."#dumpfile \"/opt/artica/var/milter-greylist/greylist.db\"\n";
			$conf=$conf ."\n";
			$conf=$conf ."# The user the milter should run as.\n";
			$conf=$conf ."# May be overridden by the \"-u username\" command line argument.\n";
			$conf=$conf ."#user \"greylist\"\n";
			$conf=$conf ."geoipdb \"/usr/share/GeoIP/GeoIP.dat\"\n";
			$conf=$conf .$objects."\n";
			/*$user=new usersMenus();
			if(strlen($user->GeoIPDat_Path)>2){
			$conf=$conf ."geoipdb \"$user->GeoIPDat_Path\"\n";	
			}
			
*/
			$conf=$conf ."\n";
			$conf=$conf ."\n";
			$conf=$conf ."#ACLS Rules [".__FUNCTION__."/".__LINE__."]\n\n";
			if($GLOBALS["ROOT"]){echo "Starting......: ".date("H:i:s")." milter-greylist $this->hostname\n";}
			$conf=$conf."\n";
			return $conf;
}

private function multinets(){
	include_once dirname(__FILE__)."/class.mysql.inc";
	$sql="SELECT ipaddr FROM nics_virtuals";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		WriteLogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__);
		return;
	}
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if(strpos($ligne["ipaddr"],"[::ffff:127.0.0.0]/104 [::1]/128")>0){
			$ligne["ipaddr"]=str_replace("[::ffff:127.0.0.0]/104 [::1]/128","",$ligne["ipaddr"]);
		}	
		if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$ligne["ipaddr"])){continue;}
		$this->acl[]="acl whitelist addr {$ligne["ipaddr"]} #multinets (postfix local network)";
	}
	
	
		$q=new mysql();
		$sql="SELECT * FROM postfix_whitelist_con";
		$ipClass=new IP();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "$q->mysql_error\n";}
	
		while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			
			if(!$ipClass->isIPAddress($ligne["ipaddr"])){
				$ligne["ipaddr"]=gethostbyname($ligne["ipaddr"]);
			}
			
			if(!$ipClass->isIPAddress($ligne["ipaddr"])){continue;}
			$this->acl[]="acl whitelist addr {$ligne["ipaddr"]} #postfix_whitelist_con";
		
		}	
	
	$ldap=new clladp();
	$nets=$ldap->load_mynetworks();
	if(!is_array($nets)){
		$this->acl[]="acl whitelist addr 127.0.0.0/8 #mynetworks";
	}
	
    foreach ($nets as $network){
	    $cleaned[$network]=$network;
    }
	unset($nets);

    foreach ($cleaned as $network=>$network2){
        $nets[]=$network;
    }
    foreach ($nets as $b){
        if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$b)){continue;}
		$this->acl[]="acl whitelist addr $b #mynetworks array2";
	}	
	
}


function ParseAcl($line){
	if(preg_match('#[a-z]+\s+(whitelist|blacklist|greylist)\s+(addr|urlcheck|body|domain|from|rcpt|dnsrbl)\s+(.+)\s+\#(.+)#',$line,$re)){
		return $re;
	}
	
	return $line;
	
}

function __Parse_DNSBL(){
	if(!is_array($this->dnsrbl)){$this->defaultDnsrbl();}
	$table=$this->dnsrbl;
	WriteLogs("Parsing DNSBL " . count($this->dnsrbl) . " rows",__CLASS__.'/'.__FUNCTION__,__FILE__);
	$pattern='#dnsrbl\s+"(.+?)"\s+(.+?)\s+([0-9\.]+)\s+\#(.+)#';
	reset($this->dnsrbl);
	if(!is_array($table)){return null;}
    foreach ($table as $num=>$val){
		if(preg_match($pattern,$val,$re)){
			$this->dnsrbl_array[$re[1]]=array($re[2],$re[3],$re[4]);
			$this->dnsrbl_class[$re[1]]=$re[1];
		}else{
			WriteLogs("Parsing DNSBL doesn't match $val for $pattern",__CLASS__.'/'.__FUNCTION__,__FILE__);
		}
		
	}
}

function __Build_DNSBL(){
	if(is_array($this->dnsrbl_array)){reset($this->dnsrbl_array);}
	unset($this->dnsrbl);
	if(is_array($this->dnsrbl_array)){
        foreach ($this->dnsrbl_array as $num=>$val){
			$class=$num;
			$this->dnsrbl[]="dnsrbl \"$class\" {$val[0]} {$val[1]} #{$val[2]}";
			}
		}
}

public function GetWhiteListed(){
	$array=$this->acl;
	if(!is_array($array)){return array();}
	foreach ($array as $num=>$val){
		if(preg_match("#acl whitelist addr\s+(.+?)\s+#",$val,$re)){
			$ar[$re[1]]=true;
		}
		
	}
	return $ar;
}


function DefaultAcls(){
	
	$trace=debug_backtrace();
			if(isset($trace[1])){
				$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";			
				//echo "Instanciate sockets() {$GLOBALS["posix_getuid"]}={$name["name"]} $called\n";
			}	
			
	writelogs("Inserting default acls $called",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
	
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"127.0.0.0/8","DESC"=>"local network");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"10.0.0.0/8","DESC"=>"local network");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"192.168.0.0/24","DESC"=>"local network");  
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"192.0.1.0/24","DESC"=>"local network");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"64.12.136.0/24","DESC"=>"AOL (common pool)");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"64.12.137.0/24","DESC"=>"AOL");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"64.12.138.0/24","DESC"=>"AOL");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"64.124.204.39","DESC"=>"moveon.org (unique sender)");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"66.94.237.16/28","DESC"=>"Yahoo Groups servers (common pool)");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"66.94.237.32/28","DESC"=>"Yahoo Groups servers (common pool)");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"66.94.237.48/30","DESC"=>"Yahoo Groups servers (common pool)");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"66.135.192.0/19","DESC"=>"Ebay");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"66.218.66.0/23","DESC"=>"Yahoo Groups servers (common pool)");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"66.218.67.0/23","DESC"=>"Yahoo Groups servers (common pool)");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"66.218.68.0/23","DESC"=>"Yahoo Groups servers (common pool)");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"152.163.225.0/24  ","DESC"=>"AOL");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"195.238.2.0/24","DESC"=>"skynet.be (wierd retry pattern)");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"195.238.3.0/24","DESC"=>"skynet.be");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"205.188.0.0/16","DESC"=>"AOL");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"205.206.231.0/24","DESC"=>"SecurityFocus.com (unique sender)");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"207.115.63.0/24   ","DESC"=>"Prodigy - retries continually");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"207.171.168.0/24  ","DESC"=>"Amazon.com");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"207.171.180.0/24  ","DESC"=>"Amazon.com");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"207.171.187.0/24  ","DESC"=>"Amazon.com");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"207.171.188.0/24  ","DESC"=>"Amazon.com");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"207.171.190.0/24  ","DESC"=>"Amazon.com");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"211.29.132.0/24   ","DESC"=>"optusnet.com.au (wierd retry pattern)");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"213.136.52.31  ","DESC"=>"Mysql.com (unique sender)");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"216.33.244.0/24   ","DESC"=>"Ebay");
	$acl[]=array("METHOD"=>"whitelist","TYPE"=>"addr","pattern"=>"217.158.50.178","DESC"=>"AXKit mailing list (unique sender)");

	
	$prefix="INSERT INTO miltergreylist_acls (instance,method,type,pattern,description) VALUES ";
	$hostname=$this->hostname;
	if($hostname==null){$hostname="master";}
    foreach ($acl as $num=>$array){
		$f[]="('$hostname','{$array["METHOD"]}','{$array["TYPE"]}','{$array["pattern"]}','{$array["DESC"]}')";
	}	
	
	
	
		$sql=$prefix.@implode(",", $f);
		$q=new postgres_sql();
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}


}

function defaultDnsrbl(){
			$this->dnsrbl[]="dnsrbl \"NJABL-RELAY\" combined.njabl.org 127.0.0.2 #open relays";
			$this->dnsrbl[]="dnsrbl \"NJABL-DYNABLOCK\" combined.njabl.org 127.0.0.3 #dynamic IPs";
			$this->dnsrbl[]="dnsrbl \"NJABL-SPAMSRC\" combined.njabl.org 127.0.0.4 #source of spam";
			$this->dnsrbl[]="dnsrbl \"NJABL-RELAY-MULTI\" combined.njabl.org 127.0.0.5 #relay which accepts feeds from another server which is open #note - 127.0.0.6 exists, but it's unwise to use unless you're being aggressive";
			$this->dnsrbl[]="dnsrbl \"NJABL-CGI\" combined.njabl.org 127.0.0.8 #open formmail and similar CGI issues";
			$this->dnsrbl[]="dnsrbl \"NJABL-PROXY\" combined.njabl.org 127.0.0.9 #open proxies";
			$this->dnsrbl[]="dnsrbl \"SPAMHAUS SBL\" sbl-xbl.spamhaus.org 127.0.0.2 #spamhaus";
			$this->dnsrbl[]="dnsrbl \"SPAMHAUS XBL CBL\" sbl-xbl.spamhaus.org 127.0.0.4 #spamhaus";
			$this->dnsrbl[]="dnsrbl \"SPAMHAUS XBL NJABL\" sbl-xbl.spamhaus.org 127.0.0.5 #spamhaus";
			$this->dnsrbl[]="dnsrbl \"SPAMHAUS XBL OTHER\" sbl-xbl.spamhaus.org 127.0.0.6 ##note: code 6 seems unused now, but I'm including for completeness";
			$this->dnsrbl[]="dnsrbl \"SORBS-HTTP\" dnsbl.sorbs.net 127.0.0.2 #open http proxy";
			$this->dnsrbl[]="dnsrbl \"SORBS-SOCKS\" dnsbl.sorbs.net 127.0.0.3 #open socks proxy";
			$this->dnsrbl[]="dnsrbl \"SORBS-BLOCK\" dnsbl.sorbs.net 127.0.0.8 #sites which have requested SORBs not test their servers";
			$this->dnsrbl[]="dnsrbl \"SORBS-MISC\" dnsbl.sorbs.net 127.0.0.4 #open misc other proxy";
			$this->dnsrbl[]="dnsrbl \"SORBS-SMTP\" dnsbl.sorbs.net 127.0.0.5 #open relays";
			$this->dnsrbl[]="dnsrbl \"SORBS-SPAM\" dnsbl.sorbs.net 127.0.0.6 #spam source";
			$this->dnsrbl[]="dnsrbl \"SORBS-WEB\" dnsbl.sorbs.net 127.0.0.7 #web";
			$this->dnsrbl[]="dnsrbl \"SORBS-ZOMBIE\" dnsbl.sorbs.net 127.0.0.9 #hijacked network";
			$this->dnsrbl[]="dnsrbl \"SORBS-DUL\" dnsbl.sorbs.net 127.0.0.10 #dialup";
			$this->dnsrbl[]="dnsrbl \"SORBS DUN\" dnsbl.sorbs.net 127.0.0.10 # config example";
			$this->dnsrbl[]="dnsrbl	\"NJABL_2\" dnsbl.njabl.org 127.0.0.2 # config example";
			$this->dnsrbl[]="dnsrbl	\"NJABL_3\" dnsbl.njabl.org 127.0.0.3 # config example";
			$this->dnsrbl[]="dnsrbl	\"NJABL_4\" dnsbl.njabl.org 127.0.0.4 # config example";
			$this->dnsrbl[]="dnsrbl	\"NJABL_5\" dnsbl.njabl.org 127.0.0.9 # config example";
	
}
	
	
}
?>