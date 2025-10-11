<?php
if(!isset($GLOBALS["postmulti"])){$GLOBALS["postmulti"]=null;}
if(!isset($GLOBALS["postconf"])){$GLOBALS["postconf"]=null;}
include_once(dirname(__FILE__)."/class.mysql.inc");;
include_once(dirname(__FILE__)."/class.postfix.externaldbs.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");
include_once(dirname(__FILE__).'/class.postgres.inc');
include_once(dirname(__FILE__).'/class.sqlite.inc');

class maincf_multi{
	var $uuid;
	var $ou;
	var $ip_addr;
	var $myhostname;
	var $q;
	var $AS_ROOT=false;
	var $EnableipV6=0;
    private $ID_FOUND=0;
	
	function __construct($instanceid=null,$ou=null,$ip_addr=null){
            if($instanceid==0){
                $this->myhostname="master";
                $this->ou="master";
                $this->uuid="";
            }
            if(is_numeric($instanceid)){
                if($instanceid>0) {
                    $this->myhostname = "instance{$instanceid}";
                    $this->ou = $this->myhostname;
                    $this->ip_addr = $this->myhostname;
                    $this->uuid = $this->myhostname;
                }
            }

			$this->AS_ROOT=$GLOBALS["AS_ROOT"];
			$this->q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
			$this->EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
			if(!is_numeric($this->EnableipV6)){$this->EnableipV6=0;}				
	}
	

	
	
	public function postfix_version(){return $GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_VERSION");	}

	public function SAVE_POSTS(){
        reset($_POST);
		foreach ($_POST as $key=>$val){
			$this->SET_BIGDATA($key, $val);
		}
		reset($_POST);
	}
	public function GET_INFO($key){return  $this->GET_BIGDATA($key);}
	
	public function GET($key){
		return  $this->GET_BIGDATA($key);
	}
    public function TokenExists($key):bool{
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        VERBOSE("TokenExists SELECT `ID` FROM postfix_params WHERE `key`='$key' AND uuid='$this->uuid'",__LINE__);

        if($this->uuid<>null) {
            $sql = "SELECT `ID` FROM postfix_params WHERE `key`='$key' AND uuid='$this->uuid' LIMIT 1";
        }else{
            $sql = "SELECT `ID` FROM postfix_params WHERE `key`='$key' AND uuid='' LIMIT 1";
        }
        $ligne=$q->mysqli_fetch_array($sql);
        if(!isset($ligne["ID"])){$ligne["ID"]=0;}
        $ID=intval($ligne["ID"]);
        if($ID>0){
            $this->ID_FOUND=$ID;
            return true;
        }
        return false;
    }
	
	public function GET_BIGDATA($key){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
        $anduuid=null;
        if($this->uuid<>null){
            $anduuid="AND uuid='$this->uuid'";
        }
		$sql="SELECT `ValueTEXT` FROM postfix_params WHERE `key`='$key' $anduuid LIMIT 1";
        if (function_exists("VERBOSE")) { VERBOSE($sql,__LINE__);}
		$ligne=$q->mysqli_fetch_array($sql);
		if(!$q->ok) {
            echo "{$q->mysql_error} => GET_BIGDATA($key)\n";
            if (function_exists("VERBOSE")) {
                VERBOSE("{$q->mysql_error} => GET_BIGDATA($key)", __LINE__);
            }
        }

        if(!is_array($ligne)){
            return "";
        }

        if(!isset($ligne["ValueTEXT"])){
            return "";
        }
        if (function_exists("VERBOSE")) {
            VERBOSE("$key={$ligne["ValueTEXT"]} decoded = ".base64_decode($ligne["ValueTEXT"]),__LINE__);}
		return base64_decode($ligne["ValueTEXT"]);
	}
    public function SET_INFO($key,$value){
        return  $this->SET_BIGDATA($key,$value);
    }

	public function SET($key,$value){
        return  $this->SET_BIGDATA($key,$value);
    }

public function SET_BIGDATA($key,$value){
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
		$value=base64_encode($value);
		if(!$this->TokenExists($key)){
            $sql="INSERT INTO postfix_params  (`ou`,`key`,`ValueTEXT`,`uuid`,`ip_address`) VALUES('$this->ou','$key','$value','$this->uuid','$this->ip_addr')";
            $q->QUERY_SQL($sql);
            if(!$q->ok){
                writelogs("!!!! FATAL $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
                echo "{$q->mysql_error}\nSET_VALUE($key,$value)";
                return false;
            }
            return true;
		}

        $ID=$this->ID_FOUND;
		$sql="UPDATE postfix_params SET `ValueTEXT`='$value' WHERE ID=$ID";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
            writelogs("!!!! FATAL $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
			echo "{$q->mysql_error}\nSET_VALUE($key,$value)";
			return false;
		}
		return true;
	}

	
public function RelayToPattern($hostname,$port,$lookups){
	$relay_port_text=null;
	if($port<>25){
		$relay_port_text=":{$port}";
	}
	
	if($lookups==1){
		$hostname="[$hostname]$relay_port_text";
		return $hostname;
	}
	
	if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$hostname)){
		$hostname="[$hostname]$relay_port_text";
		return $hostname;
	}
	return "$hostname$relay_port_text";
}	
	
public function relayhost(){


}	

	
	
	
	
	public function SET_VALUE($key,$value){
		$value=base64_encode($value);
		$sql="SELECT `ID` FROM postfix_params WHERE `key`='$key' LIMIT 1";
		$ligne=$this->q->mysqli_fetch_array($sql);	
		
		if(trim($value)==null){
			if(intval($ligne["ID"])==0){return true;}
			$sql="DELETE FROM postfix_params WHERE ID={$ligne["ID"]}";
			$this->q->QUERY_SQL($sql);
			if(!$this->q->ok){
				echo "{$this->q->mysql_error}\nSET_VALUE($key,$value)";
				return false;
			}
			return true;
		}
		
		
		
		if($ligne["ID"]==null){
			$sql="INSERT INTO postfix_params  (`ou`,`key`,`value`,`uuid`,`ip_address`,`ValueTEXT`) VALUES('$this->ou','$key','$value','$this->uuid','$this->ip_addr','')";
		}else{
			
			$sql="UPDATE postfix_params SET `value`='$value' WHERE ID={$ligne["ID"]}";
		}
		$this->q->QUERY_SQL($sql);
		if(!$this->q->ok){
			echo "{$this->q->mysql_error}\nSET_VALUE($key,$value)";
			return false;
		}
		
		return true;
	}	
	
	public function DELETE_KEY($key){
		$sql="SELECT `ID` FROM postfix_params WHERE `key`='$key' LIMIT 1";
		$ligne=$this->q->mysqli_fetch_array($sql);	
		if($ligne["ID"]==null){return;}
		$sql="DELETE FROM postfix_params WHERE ID={$ligne["ID"]}";
		$this->q->QUERY_SQL($sql);
		if(!$this->q->ok){
			echo "{$this->q->mysql_error}\nDELETE_KEY($key)";
		}
	}

	
	public function mailbox_transport_maps(){
		$DestinationFile="/etc/postfix-$this->myhostname/mailbox_transport";
		@file_put_contents($DestinationFile,"");
		$users=new usersMenus();
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
		$ldap=new clladp();		

		
		$sql="SELECT * FROM postfix_transport_mailbox WHERE hostname='$this->myhostname'";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){	
			$xType=$ligne["xType"];
			$pattern="lmtp:{$ligne["lmtp_address"]}";
			if($xType==1){
				$hash=$ldap->hash_users_ou($ligne["uid"]);
                foreach ($hash as $uid=>$none){if(trim($uid)==null){continue;}$f[]="$uid\t$pattern";}
				continue;
			}
			$f[]="{$ligne["uid"]}\t$pattern";
		}
		
		@file_put_contents($DestinationFile,@implode("\n",$f));
		shell_exec("{$GLOBALS["postmap"]} hash:$DestinationFile >/dev/null 2>&1");
		return "mailbox_transport_maps = hash:$DestinationFile";
		
	}

	private function mailling_list_mysql_items($list,$hostname){
		$f=array();
		$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
		$sql="SELECT * FROM postfix_diffusion_list WHERE mainlist='$list' AND enabled=1";
		$results = $q->QUERY_SQL($sql); 
		if(!$q->ok){squid_admin_mysql(2, "hostname:$hostname $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "postfix");return;}
		foreach ($results as $index=>$ligne){
			$email=trim($ligne["recipient"]);
			$email=str_replace("\n", "", $email);
			$email=str_replace("\r", "", $email);
			if($email==null){continue;}
			$f[]=$email;
		}
		
		if(count($f)>0){return @implode(",", $f);}
	}


		
	public function mailling_list_mysql($hostname=null){
		$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
		$f=array();
		if($hostname==null){$hostname=$this->myhostname;}
		$sql="SELECT * FROM postfix_diffusion WHERE hostname='$hostname' AND enabled=1";
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){squid_admin_mysql(2, "hostname:$hostname $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "postfix");return;}
		foreach ($results as $index=>$ligne){
			$addresses=trim($this->mailling_list_mysql_items($ligne["recipient"],$hostname));
			$addresses=str_replace("\n","",$addresses);
			$addresses=str_replace("\r","",$addresses);
			
			
			$ligne["recipient"]=trim($ligne["recipient"]);
			$ligne["recipient"]=str_replace("\n","",$ligne["recipient"]);
			$ligne["recipient"]=str_replace("\r","",$ligne["recipient"]);
			if($addresses<>null){
				$f[$ligne["recipient"]]=$addresses;
			}
		}
		
		if(count($f)==0){return array();}
		$final=array();
        foreach ($f as $num=>$params){
			$params=trim($params);
			if($params==null){continue;}
			$final[]="$num\t$params";
		}
		
		if(count($final)>0){return $final;}
		
	
		
	}	
	
	
	private function smtpd_client_restrictions(){

		$this->amavis_internal();
		$sock=new sockets();
		$EnableSasl=$this->GET("EnableSasl");
		$TrustMyNetwork=$this->GET("TrustMyNetwork");
		if(!is_numeric($TrustMyNetwork)){$TrustMyNetwork=1;}	
		$PostfixMultiCreateBubble=$sock->GET_INFO("PostfixMultiCreateBubble");
		if(!is_numeric($PostfixMultiCreateBubble)){$PostfixMultiCreateBubble=0;}	
		if($PostfixMultiCreateBubble==1){$TrustMyNetwork=1;}	
		
		$users=new usersMenus();
		$sock=new sockets();
	
		
		
		if($TrustMyNetwork==1){
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" TrustMyNetwork is enabled (permit_mynetworks will be added).\n";
			$smtpd_client_restrictions[]="permit_mynetworks";
			$smtpd_helo_restrictions[]="permit_mynetworks";
			$smtpd_recipient_restrictions[]="permit_mynetworks";		
		
		}else{
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" TrustMyNetwork is !disabled (permit_mynetworks will be removed).\n";
		}
		$smtpd_client_restrictions[]="check_client_access hash:/etc/postfix-$this->myhostname/amavis_internal";
		

		$smtpd_recipient_restrictions[]="check_recipient_access hash:/etc/postfix-$this->myhostname/relay_domains_restricted";
		$smtpd_recipient_restrictions[]="check_recipient_access hash:/etc/postfix-$this->myhostname/amavis_bypass_rcpt";

		
		if($EnableSasl==1){
			$smtpd_client_restrictions[]="permit_sasl_authenticated";
			$smtpd_helo_restrictions[]="permit_sasl_authenticated";
			$smtpd_recipient_restrictions[]="permit_sasl_authenticated";
		}
		$restrictions=unserialize(base64_decode($this->GET_BIGDATA("hash_smtp_restrictions")));
		
		if($restrictions["reject_unknown_client_hostname"]==1){$smtpd_client_restrictions[]="reject_unknown_client_hostname";}
		if($restrictions["reject_invalid_hostname"]==1){
			$smtpd_client_restrictions[]="reject_invalid_hostname";
			$smtpd_helo_restrictions[]="reject_invalid_hostname";
			$smtpd_helo_restrictions[]="reject_non_fqdn_hostname";
		}
		
		
		if($restrictions["reject_unknown_reverse_client_hostname"]==1){$smtpd_client_restrictions[]="reject_unknown_reverse_client_hostname";}
		if($restrictions["reject_unknown_sender_domain"]==1){$smtpd_client_restrictions[]="reject_unknown_sender_domain";}
		if($restrictions["reject_non_fqdn_sender"]==1){$smtpd_client_restrictions[]="reject_non_fqdn_sender";}		
		
		$smtpd_client_restrictions[]=$this->check_client_access();
		
		if($restrictions["EnablePostfixAntispamPack"]==1){
				$smtpd_client_restrictions[]="reject_rbl_client zen.spamhaus.org";
				$smtpd_client_restrictions[]="reject_rbl_client sbl.spamhaus.org";
				$smtpd_client_restrictions[]="reject_rbl_client cbl.abuseat.org";
		}
		$smtpd_recipient_restrictions[]="permit_auth_destination";
		$smtpd_recipient_restrictions[]="reject_unauth_destination";
		
		$smtpd_client_restrictions[]="permit";
		$smtpd_helo_restrictions[]="permit";
		$smtpd_recipient_restrictions[]="permit";

    
		$smtpd_client_restrictions=$this->CleanArraySMTD($smtpd_client_restrictions);
		$smtpd_recipient_restrictions=$this->CleanArraySMTD($smtpd_recipient_restrictions);
		$smtpd_helo_restrictions=$this->CleanArraySMTD($smtpd_helo_restrictions);
		

		$conf[]="smtpd_restriction_classes = ";
		if(count($smtpd_recipient_restrictions)>0){
			$conf[]="smtpd_recipient_restrictions = ". implode(",",$smtpd_recipient_restrictions);
		}
		if(count($smtpd_client_restrictions)>0){
			$conf[]="smtpd_client_restrictions = ". implode(",",$smtpd_client_restrictions);
		}
		
		if(count($smtpd_helo_restrictions)>0){
			$conf[]="smtpd_helo_restrictions = ". implode(", ",$smtpd_helo_restrictions);
		}
		return @implode("\n",$conf);
		}
		
	private function CleanArraySMTD($array){
		if(!is_array($array)){return;}
        foreach ($array as $num=>$params){
            if(trim($params)==null){continue;}
            $TEMPAR[$params]=$params;
        }
		if(!is_array($TEMPAR)){return;}
		unset($array);
        foreach ($TEMPAR as $num=>$params){
		    $array[]=$params;
        }
		return $array;
		
	}
		
	private function smtpd_end_of_data_restrictions(){
		$users=new usersMenus();
		$sock=new sockets();
		$EnableArticaPolicyFilter=$sock->GET_INFO("EnableArticaPolicyFilter");
		$EnableArticaPolicyFilter=0;
		$EnableCluebringer=$sock->GET_INFO("EnableCluebringer");
		$smtpd_end_of_data_restrictions=array();
		
		$array_filters=unserialize(base64_decode($this->GET_BIGDATA("PluginsEnabled")));

		
		
		if($EnableArticaPolicyFilter==1){
			$smtpd_end_of_data_restrictions[]="check_policy_service inet:127.0.0.1:54423";
		}
		
			
		if(count($smtpd_end_of_data_restrictions)==0){return;}
		$final=@implode(",",$smtpd_end_of_data_restrictions);
		return "smtpd_end_of_data_restrictions = $final";	
	}	

	
private function amavis_internal(){
	$users=new usersMenus();
	$q=new mysql();
	$unix=new unix();
	$sock=new sockets();
	$EnableAmavisDaemon=$sock->GET_INFO('EnableAmavisDaemon');
	$EnableAmavisInMasterCF=$sock->GET_INFO('EnableAmavisInMasterCF');
	if(!$users->AMAVIS_INSTALLED){$EnableAmavisDaemon=0;}
	if($EnableAmavisDaemon==1){
		if($EnableAmavisInMasterCF==1){
			$sql="SELECT * FROM amavisd_bypass ORDER BY ip_addr";
			$results=$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error."\n";return 0;}	
			$count=0;
			$f=array();
			while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
				$ligne["ip_addr"]=trim($ligne["ip_addr"]);
				$ip=trim($ligne["ip_addr"]);
				if($ip==null){continue;}
				if(is_array($ip)){continue;}
				$count++;
				$f[]="{$ligne["ip_addr"]}\tFILTER smtp:[$this->ip_addr]:10026";
			}
		}
	}
	
	$f[]="";
	@file_put_contents("/etc/postfix-$this->myhostname/amavis_internal",@implode("\n",$f));
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix-$this->myhostname/amavis_internal");
	return $count;
	
}		
	
	private function relais_domains(){

	}
	
private function recipient_bcc_domain_maps(){
	$sql="SELECT * FROM postfix_duplicate_maps WHERE ou='$this->ou'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$c=0;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($ligne["pattern"]==null){continue;}	
		
		$left="(.*)";
		$right='${1}';
		$leftNext="(.*)";
		$rightNext='${1}';
		$domain=$ligne["pattern"];
		$nextdomain=$ligne["nextdomain"];
		$nextdomain_transport=$ligne["nextdomain"];

		
		
		if(preg_match("#(.+?)@(.+)#",$ligne["pattern"],$re)){
			$nextHope_pattern=$ligne["pattern"];
			$domain=$re[2];
			$left=$re[1];
			$right=$re[1];
			$rightNext=$right;
			$left=str_replace(".","\.",$left);
			$right=str_replace(".","\.",$right);
			$leftNext=$left;
		}
		
		if(preg_match("#(.+?)@(.+)#",$ligne["nextdomain"],$re)){
			$right=$re[1];
			$nextdomain=$re[2];
			
		}		
		
		$md5=md5($domain);
		$domain_regex=str_replace(".","\.",$domain);
		$f[]="/^$left@$domain_regex$/   $right@$nextdomain";
		$t[]="$nextdomain_transport\tsmtp:[{$ligne["relay"]}]:{$ligne["port"]}";
		$c++;
	}
	echo "Starting......: ".date("H:i:s")." ".count($f)." duplicated destination(s)\n"; 
	$f[]="";
	@file_put_contents("/etc/postfix-$this->myhostname/copy.pcre",implode("\n",$f));
	@file_put_contents("/etc/postfix-$this->myhostname/copy.transport",implode("\n",$t));
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix-$this->myhostname/copy.transport >/dev/null 2>&1");	
}	

		
	private function alias_maps(){

		$sock=new sockets();
		$alias_maps=array();
		$MailingListUseLdap=$sock->GET_INFO("MailingListUseLdap");
		if(!is_numeric($MailingListUseLdap)){$MailingListUseLdap=0;}
		
		if(is_file("/etc/postfix-$this->myhostname/virtual")){@unlink("/etc/postfix-$this->myhostname/virtual");}
		if(is_file("/etc/postfix-$this->myhostname/aliases")){@unlink("/etc/postfix-$this->myhostname/aliases");}
		$ldap=new clladp();
		$filter="(&(objectClass=userAccount)(mailAlias=*))";
		$attrs=array("mail","mailAlias");
		$dn="ou=$this->ou,dc=organizations,$ldap->suffix";
		if(trim($this->ou)==null){$dn="dc=organizations,$ldap->suffix";}
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);
		echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" mailAlias: {$hash["count"]} aliases in $this->ou organization\n";
		
		
		for($i=0;$i<$hash["count"];$i++){
			$mail=trim($hash[$i]["mail"][0]);
			for($t=0;$t<$hash[$i]["mailalias"]["count"];$t++){
				$hash[$i]["mailalias"][$t]=trim($hash[$i]["mailalias"][$t]);
				if($hash[$i]["mailalias"][$t]==null){continue;}
				$virtual_alias_maps[]="{$hash[$i]["mailalias"][$t]}\t$mail";
			}
		}

		$filter="(&(objectClass=MailingAliasesTable)(cn=*))";
		$attrs=array("cn","MailingListAddress","MailingListAddressGroup");
		$dn="ou=$this->ou,dc=organizations,$ldap->suffix";
		if(trim($this->ou)==null){$dn="dc=organizations,$ldap->suffix";}
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);
		
		for($i=0;$i<$hash["count"];$i++){
			$cn=$hash[$i]["cn"][0];
			$MailingListAddressGroup=$hash[$i]["mailinglistaddressgroup"][0];
			for($t=0;$t<$hash[$i]["mailinglistaddress"]["count"];$t++){
				$hash[$i]["mailinglistaddress"][$t]=trim($hash[$i]["mailinglistaddress"][$t]);
				if($hash[$i]["mailinglistaddress"][$t]==null){continue;}
				$mailinglistaddress[$hash[$i]["mailinglistaddress"][$t]]=$hash[$i]["mailinglistaddress"][$t];
			}

			
			if(is_array($mailinglistaddress)){
                foreach ($mailinglistaddress as $num=>$ligne){
						$final[]=$num;
					}
					if(trim($cn)<>null){
						$virtual_alias_maps[]="$cn\t". implode(",",$final);
					}
				}	
				
			unset($final);
			unset($mailinglistaddress);
			$MailingListAddressGroup=0;
			}		

	
		$filter="(&(objectClass=AdditionalPostfixMaps)(cn=*))";
		$attrs=array("cn","CatchAllPostfixAddr");
		$dn="cn=$this->ou,cn=catch-all,cn=artica,$ldap->suffix";
		
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	
		for($i=0;$i<$hash["count"];$i++){
			$cn=trim($hash[$i]["cn"][0]);
			if(trim($cn)==null){continue;}
			for($t=0;$t<$hash[$i][strtolower("CatchAllPostfixAddr")]["count"];$t++){
				echo "Starting......: ".date("H:i:s")." catch-all {$hash[$i][strtolower("CatchAllPostfixAddr")][$t]} for $cn\n";
				if(substr($cn,0,1)<>"@"){$cn="@$cn";}
				$hash[$i][strtolower("CatchAllPostfixAddr")][$t]=trim($hash[$i][strtolower("CatchAllPostfixAddr")][$t]);
				if($hash[$i][strtolower("CatchAllPostfixAddr")][$t]==null){continue;}
				$virtual_alias_maps[]="$cn\t{$hash[$i][strtolower("CatchAllPostfixAddr")][$t]}";
			}
		}
	
		$filter="(&(objectClass=userAccount)(uid=*))";
		$attrs=array("uid","mail");
		$dn="ou=$this->ou,dc=organizations,$ldap->suffix";
		if(trim($this->ou)==null){$dn="dc=organizations,$ldap->suffix";}
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);

		for($i=0;$i<$hash["count"];$i++){
			$uid=trim($hash[$i]["uid"][0]);
			if($uid==null){continue;}
			for($t=0;$t<$hash[$i]["mail"]["count"];$t++){
				$hash[$i]["mail"][$t]=trim($hash[$i]["mail"][$t]);
				if($hash[$i]["mail"][$t]==null){continue;}
				if(!preg_match("#(.*?)@(.+?)#", $uid)){
					$alias_maps[]="$uid:{$hash[$i]["mail"][$t]}";
				}
				$virtual_mailbox[]="{$hash[$i]["mail"][$t]}\t$uid";
				$virtual_alias_maps[]="{$hash[$i]["mail"][$t]}\t{$hash[$i]["mail"][$t]}";
			}
		}

		$filter="(&(objectClass=transportTable)(cn=*@*))";
		$attrs=array("cn");
		$dn="cn=PostfixRobots,cn=artica,$ldap->suffix";
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);
		for($i=0;$i<$hash["count"];$i++){
			$cn=$hash[$i]["cn"][0];
			if(preg_match("#(.*?)@(.+?)#", $cn,$re)){$cn=$re[1];}
			$alias_maps[]="$cn:x";
			}	
			
		if(is_array($GLOBALS["LDAPDBS"]["virtual_mailbox_maps"])){
			$virtual_mailbox_maps_cf=$GLOBALS["LDAPDBS"]["virtual_mailbox_maps"];
		}	

		if(is_array($GLOBALS["LDAPDBS"]["virtual_alias_maps"])){
			$virtual_alias_maps_cf=$GLOBALS["LDAPDBS"]["virtual_alias_maps"];
		}

		if($MailingListUseLdap==1){
			$this->virtual_alias_maps_mailling_ldap();
			$virtual_alias_maps_cf[]="ldap:/etc/postfix-$this->myhostname/mailinglist.ldap.cf";
		}

		if(is_array($GLOBALS["LDAPDBS"]["alias_database"])){
			$alias_maps_cf=$GLOBALS["LDAPDBS"]["alias_database"];
		}	

		
		$sql="SELECT * FROM postfix_aliases_domains WHERE ou='$this->ou'";
		$q=new mysql();
		$pre='${1}';
		$li=array();
		$results=$q->QUERY_SQL($sql,"artica_backup");	
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){	
			$aliases=str_replace(".","\.",$ligne["alias"]);
			$domain=$ligne["domain"];
			$li[]="/^(.*)@$aliases$/\t$pre@$domain";
			$virtual_alias_maps[]="{$ligne["alias"]}\tDOMAIN";
		}	
		
		
		$virtual_mailing_addr=$this->mailling_list_mysql();


		$virtual_alias_maps_cf[]="pcre:/etc/postfix-$this->myhostname/virtual.domains";
		@file_put_contents("/etc/postfix-$this->myhostname/virtual.domains",@implode("\n",$li));
			
		if(is_array($virtual_alias_maps)){
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" ". count($virtual_alias_maps)." virtuals aliases\n";
			$virtual_mailbox_maps_cf[]="hash:/etc/postfix-$this->myhostname/virtual";
			$virtual_alias_maps_cf[]="hash:/etc/postfix-$this->myhostname/virtual";
			@file_put_contents("/etc/postfix-$this->myhostname/virtual",@implode("\n",$virtual_alias_maps));
			shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix-$this->myhostname/virtual >/dev/null 2>&1");
		}
		

		

		if(is_array($alias_maps)){
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" ". count($alias_maps)." aliases\n";

			@file_put_contents("/etc/postfix-$this->myhostname/aliases",@implode("\n",$alias_maps));
			//shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix-$this->myhostname/aliases >/dev/null 2>&1");
			shell_exec("{$GLOBALS["postalias"]} -c /etc/postfix-$this->myhostname hash:/etc/postfix-$this->myhostname/aliases >/dev/null 2>&1");
			 	
		}
		
		$externdb=new postfix_extern();
		$externdbText=$externdb->build_extern($this->myhostname, "virtual_mailbox_maps");
		if($externdbText<>null){$virtual_mailbox_maps_cf[]=$externdbText;}
		
		$externdbText=$externdb->build_extern($this->myhostname, "virtual_alias_maps");
		if($externdbText<>null){$virtual_alias_maps_cf[]=$externdbText;}

		$externdbText=$externdb->build_extern($this->myhostname, "alias_maps");
		if($externdbText<>null){$alias_maps_cf[]=$externdbText;}		
		
		
		if(is_array($virtual_mailbox_maps_cf)){$conf[]="virtual_mailbox_maps = ".@implode(",",$virtual_mailbox_maps_cf);}
		if(is_array($virtual_alias_maps_cf))  {$conf[]="virtual_alias_maps = ".@implode(",",$virtual_alias_maps_cf);}
		if(is_array($alias_maps_cf))  		  {$conf[]="alias_maps = ".@implode(",",$alias_maps_cf);}
		if(!is_array($conf)){return null;}
		return @implode("\n",$conf);
		
		
	}



	
    private function transport_maps(){

    }

	private function relay_recipient_maps_by_transport(){

	}


	public function bann_destination_domains(){

	}


	

	private function mydestination(){

		
	}
	


	
	private function sender_canonical_maps(){

	
	}

	

private	function sender_dependent_relayhost_maps(){

	}

	private function recipient_canonical_maps(){
		$recipient_canonical_maps=array();
		if(is_file("/etc/postfix-$this->myhostname/recipient_canonical")){@unlink("/etc/postfix-$this->myhostname/recipient_canonical");}
		$ldap=new clladp();
		$filter="(&(objectClass=RecipientCanonicalMaps)(cn=*))";
		$attrs=array("cn","MailAlternateAddress");
		$dn="ou=$this->ou,dc=organizations,$ldap->suffix";
		if(trim($this->ou)==null){$dn="dc=organizations,$ldap->suffix";}
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	
		for($i=0;$i<$hash["count"];$i++){
			$mail=$hash[$i]["cn"][0];
			$canonical=$hash[$i][strtolower("MailAlternateAddress")][0];
			$recipient_canonical_maps[]="$mail\t$canonical";
		}
	
		if(count($recipient_canonical_maps)>0){
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" ". count($recipient_canonical_maps)." sender dependent relayhost rule(s)\n"; 
			@file_put_contents("/etc/postfix-$this->myhostname/recipient_canonical",implode("\n",$recipient_canonical_maps));
			shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix-$this->myhostname/recipient_canonical >/dev/null 2>&1");
			return "recipient_canonical_maps = hash:/etc/postfix-$this->myhostname/recipient_canonical";
		}		
		
	}	
	
private	function recipient_bcc_maps(){
		if(is_file("/etc/postfix-$this->myhostname/recipient_bcc")){@unlink("/etc/postfix-$this->myhostname/recipient_bcc");}
		if(!is_file("/etc/postfix-$this->myhostname/copy.pcre")){@file_put_contents("/etc/postfix-$this->myhostname/copy.pcre"," ");}
		$ldap=new clladp();
		$filter="(&(objectClass=UserArticaClass)(RecipientToAdd=*))";
		$attrs=array("RecipientToAdd","mail");
		$dn="ou=$this->ou,dc=organizations,$ldap->suffix";
		if(trim($this->ou)==null){$dn="dc=organizations,$ldap->suffix";}
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);
		
		for($i=0;$i<$hash["count"];$i++){
			$mail=$hash[$i]["mail"][0];
			$RecipientToAdd=$hash[$i]["recipienttoadd"][0];
			$bcc_maps[]="$mail\t$RecipientToAdd";
			
		}	
		
		if(is_array($bcc_maps)){
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" ". count($bcc_maps)." recipient(s) BCC\n"; 
			@file_put_contents("/etc/postfix-$this->myhostname/recipient_bcc",implode("\n",$bcc_maps));
			shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix-$this->myhostname/recipient_bcc >/dev/null 2>&1");		
			return "recipient_bcc_maps = hash:/etc/postfix-$this->myhostname/recipient_bcc,pcre:/etc/postfix-$this->myhostname/copy.pcre";
		}
		
		return "recipient_bcc_maps = pcre:/etc/postfix-$this->myhostname/copy.pcre";
			
	}

	private function sender_bcc_maps(){
		if(is_file("/etc/postfix-$this->myhostname/sender_bcc")){@unlink("/etc/postfix-$this->myhostname/sender_bcc");}
		$ldap=new clladp();
		$filter="(&(objectClass=UserArticaClass)(SenderBccMaps=*))";
		$attrs=array("SenderBccMaps","mail");
		$dn="ou=$this->ou,dc=organizations,$ldap->suffix";
		if(trim($this->ou)==null){$dn="dc=organizations,$ldap->suffix";}
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);
		
		for($i=0;$i<$hash["count"];$i++){
			$mail=$hash[$i]["mail"][0];
			$senderbccmaps=$hash[$i]["senderbccmaps"][0];
			$sender_bcc_maps[]="$mail\t$senderbccmaps";
			
		}	
		
		if(is_array($sender_bcc_maps)){
				echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\"". count($sender_bcc_maps)." sender(s) BCC\n"; 
				@file_put_contents("/etc/postfix-$this->myhostname/sender_bcc",implode("\n",$sender_bcc_maps));
				shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix-$this->myhostname/sender_bcc >/dev/null 2>&1");		
				return "sender_bcc_maps = hash:/etc/postfix-$this->myhostname/sender_bcc";
		}		
	}
	
	
	

	
	public function getMailBoxTransport(){
		include_once(dirname(__FILE__).'/class.cyrus.inc');
		$users=new usersMenus();
		$sock=new sockets();
		$cyrus=new cyrus_conf();
		$CyrusLMTPListenPattern=null;
		$mailbox_transport=trim($this->GET("mailbox_transport"));
		if($mailbox_transport<>null){return $mailbox_transport;}		
		
		$ListenAddress=$cyrus->main_array["CYRUS"]['ListenAddress'];
		if(!is_numeric($cyrus->main_array["CYRUS"]["service_lmtp_listen"])){$cyrus->main_array["CYRUS"]["service_lmtp_listen"]=2005;}
		if(!is_numeric($cyrus->main_array["CYRUS"]["service_lmtp_enabed"])){$cyrus->main_array["CYRUS"]["service_lmtp_enabed"]=1;}

        $CyrusLMTPListen="";
		$CyrusEnableLMTPUnix=$sock->GET_INFO("CyrusEnableLMTPUnix");
		if(!is_numeric($CyrusEnableLMTPUnix)){$CyrusEnableLMTPUnix=1;}
		
		if($cyrus->main_array["CYRUS"]["service_lmtp_enabed"]==1){
			
			if($ListenAddress<>null){
				$CyrusLMTPListen="$CyrusLMTPListen:{$cyrus->main_array["CYRUS"]["service_lmtp_listen"]}";}else{
				$CyrusLMTPListen="127.0.0.1:{$cyrus->main_array["CYRUS"]["service_lmtp_listen"]}";
			}
		}
		if(preg_match("#(.+?):([0-9]+)#", $CyrusLMTPListen)){
			$CyrusLMTPListenPattern=$CyrusLMTPListen;
		
		}else{
			$CyrusLMTPListenPattern="127.0.0.1:2005";
		}
		
		
		
		$unixsocket=$users->cyrus_lmtp_path;
		if($unixsocket==null){$unixsocket="/var/spool/postfix/var/run/cyrus/socket/lmtp";}
		
		@chown($unixsocket, "postfix");
		@chgrp($unixsocket, "postfix");
		@chmod($unixsocket,0777);
		
		if($users->cyrus_imapd_installed){
			if(!$users->ZARAFA_INSTALLED){
					if($CyrusLMTPListen<>null){
						if(preg_match("#(.+?):(.+)#",$CyrusLMTPListen,$re)){
							$ipaddr_listen=$re[1];$port=$re[2];}else{$ipaddr_listen="127.0.0.1";$port="2005";}
							return "lmtp:$ipaddr_listen:$port";
						}
				
				
					if($CyrusEnableLMTPUnix==1){
						return "lmtp:unix:$unixsocket";
					}
				}
		}
		
		
		if($users->ZARAFA_INSTALLED){
			$ZarafaDeliverBind=$sock->GET_INFO("ZarafaDeliverBind");
			if($ZarafaDeliverBind==null){$ZarafaDeliverBind="127.0.0.1";}
			if($ZarafaDeliverBind=="0.0.0.0"){$ZarafaDeliverBind="127.0.0.1";}
			$default="lmtp:$ZarafaDeliverBind:2003";}	
			return $default;	
		
	}
	
	
	private function cyruslmtp(){
		$users=new usersMenus();
		if($users->ZARAFA_INSTALLED){return null;}
		if(!$users->cyrus_imapd_installed){return null;}
		$sock=new sockets();
		$CyrusEnableLMTPUnix=$sock->GET_INFO("CyrusEnableLMTPUnix");
		if($CyrusEnableLMTPUnix==null){$CyrusEnableLMTPUnix=1;}	
		if($CyrusEnableLMTPUnix==1){
			$conf[]="";
			$conf[]="#  LMP REMOTE ---------------------------------";
			$conf[]="lmtp_sasl_auth_enable = no";
			$conf[]="";
			return @implode("\n",$conf);
		}
		
		$ldap=new clladp();
		$CyrusLMTPListen=trim($sock->GET_INFO("CyrusLMTPListen"));
		if($CyrusLMTPListen==null){return;}
		$cyruspass=$ldap->CyrusPassword();
		@file_put_contents("/etc/postfix-$this->myhostname/lmtpauth","$CyrusLMTPListen\tcyrus:$cyruspass");
		shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix-$this->myhostname/lmtpauth");
		$conf[]="";
		$conf[]="#  LMP REMOTE ---------------------------------";
		$conf[]="lmtp_sasl_auth_enable = yes";
		$conf[]="lmtp_sasl_password_maps = hash:/etc/postfix-$this->myhostname/lmtpauth";
		$conf[]="lmtp_sasl_mechanism_filter = plain, login";
		$conf[]="lmtp_sasl_security_options =";
		$conf[]="";
		return @implode("\n",$conf);
		}
		
		
		private function haproxy_compliance(){
			$EnablePostfixHaProxy=$this->GET("EnablePostfixHaProxy");
			if(!is_numeric($EnablePostfixHaProxy)){$EnablePostfixHaProxy=0;}	
			
			$users=new usersMenus();
			if(preg_match("#^([0-9]+)\.([0-9]+)#", $users->POSTFIX_VERSION,$re)){
				$major=intval($re[1]);
				$minor=intval($re[2]);
				$binver="{$major}{$minor}";
				if($EnablePostfixHaProxy==1){
					if($binver<210){echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" HaProxy compliance: require 2.10 minimal.\n";return;}
				}
				
			}
			
			if($EnablePostfixHaProxy==0){echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" HaProxy compliance: disabled\n";return;}
			
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" HaProxy compliance: enabled\n";
			$EnablePostScreen=intval($this->GET("EnablePostScreen"));
			if(!is_numeric($EnablePostScreen)){$EnablePostScreen=0;}	
			if(!$users->POSTSCREEN_INSTALLED){$EnablePostScreen=0;}
			
			if($EnablePostScreen==1){
				echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\"HaProxy compliance: enabled + PostScreen\n";
				return "postscreen_upstream_proxy_protocol = haproxy";
				
			}else{
				echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" HaProxy compliance: enabled + SMTPD\n";
				return "smtpd_upstream_proxy_protocol = haproxy";
			}
		
		}		


		
	private function POSTSCREEN(){
	$sock=new sockets();
	$EnablePostScreen=$this->GET("EnablePostScreen");
	if(!is_numeric($EnablePostScreen)){$EnablePostScreen=0;}
	if($EnablePostScreen==0){echo "Starting......: ".date("H:i:s")." PostScreen is not enabled\n";return null;}
	echo "Starting......: ".date("H:i:s")." PostScreen \"$this->myhostname\" configuring....\n";
	if(!is_file("/etc/postfix-$this->myhostname/postscreen_access.cidr")){@file_put_contents("/etc/postfix-$this->myhostname/postscreen_access.cidr","#");}
	if(!is_file("/etc/postfix-$this->myhostname/postscreen_access.hosts")){@file_put_contents("/etc/postfix-$this->myhostname/postscreen_access.hosts"," ");}
	$TrustMyNetwork=$this->GET("TrustMyNetwork");
	if(!is_numeric($TrustMyNetwork)){$TrustMyNetwork=1;}
	$PostfixMultiCreateBubble=$sock->GET_INFO("PostfixMultiCreateBubble");
	if(!is_numeric($PostfixMultiCreateBubble)){$PostfixMultiCreateBubble=0;}			
	if($PostfixMultiCreateBubble==1){$TrustMyNetwork=1;}
	
	
	$postscreen_bare_newline_action=$this->GET("postscreen_bare_newline_action");
	$postscreen_bare_newline_enable=$this->GET("postscreen_bare_newline_enable");
	
	$postscreen_bare_newline_ttl=$this->GET("postscreen_bare_newline_ttl");
	$postscreen_cache_cleanup_interval=$this->GET("postscreen_cache_cleanup_interval");
	$postscreen_cache_retention_time=$this->GET("postscreen_cache_retention_time");
	$postscreen_client_connection_count_limit=$this->GET("postscreen_client_connection_count_limit");
	$postscreen_pipelining_enable=$this->GET("postscreen_pipelining_enable");
	$postscreen_pipelining_action=$this->GET("postscreen_pipelining_action");
	$postscreen_pipelining_ttl=$this->GET("postscreen_pipelining_ttl");
	$postscreen_post_queue_limit=$this->GET("postscreen_post_queue_limit");
	$postscreen_pre_queue_limit=$this->GET("postscreen_pre_queue_limit");
	$postscreen_non_smtp_command_enable=$this->GET("postscreen_non_smtp_command_enable");
	$postscreen_non_smtp_command_action=$this->GET("postscreen_non_smtp_command_action");
	$postscreen_non_smtp_command_ttl=$this->GET("postscreen_non_smtp_command_ttl");
	$postscreen_forbidden_commands=$this->GET("postscreen_forbidden_command");
	$postscreen_dnsbl_action=$this->GET("postscreen_dnsbl_action");
	$postscreen_dnsbl_ttl=$this->GET("postscreen_dnsbl_ttl");
	$postscreen_dnsbl_threshold=$this->GET("postscreen_dnsbl_threshold");	
	
	
	if($postscreen_bare_newline_action==null){$postscreen_bare_newline_action="ignore";}
	if(!is_numeric($postscreen_bare_newline_enable)){$postscreen_bare_newline_enable="0";}
	if($postscreen_bare_newline_ttl==null){$postscreen_bare_newline_ttl="30d";}
	if($postscreen_cache_cleanup_interval==null){$postscreen_cache_cleanup_interval="12h";}
	if($postscreen_cache_retention_time==null){$postscreen_cache_retention_time="7d";}
	if($postscreen_client_connection_count_limit==null){$postscreen_client_connection_count_limit="50";}
	if($postscreen_pipelining_enable==null){$postscreen_pipelining_enable="0";}
	if($postscreen_pipelining_action==null){$postscreen_pipelining_action="ignore";}
	if($postscreen_pipelining_ttl==null){$postscreen_pipelining_ttl="30d";}			
	if($postscreen_post_queue_limit==null){$postscreen_post_queue_limit="100";}
	if($postscreen_pre_queue_limit==null){$postscreen_pre_queue_limit="100";}
	
	if($postscreen_non_smtp_command_enable==null){$postscreen_non_smtp_command_enable="0";}
	if($postscreen_non_smtp_command_action==null){$postscreen_non_smtp_command_action="drop";}
	if($postscreen_non_smtp_command_ttl==null){$postscreen_non_smtp_command_ttl="30d";}
	if($postscreen_forbidden_commands==null){$postscreen_forbidden_commands="CONNECT, GET, POST";}
	if($postscreen_dnsbl_action==null){$postscreen_dnsbl_action="ignore";}
	if($postscreen_dnsbl_action==null){$postscreen_dnsbl_action="ignore";}
	if($postscreen_dnsbl_ttl==null){$postscreen_dnsbl_ttl="1h";}
	if($postscreen_dnsbl_threshold==null){$postscreen_dnsbl_threshold="1";}
	
	if($postscreen_bare_newline_enable==1){$postscreen_bare_newline_enable="yes";}else{$postscreen_bare_newline_enable="no";}
	if($postscreen_pipelining_enable==1){$postscreen_pipelining_enable="yes";}else{$postscreen_pipelining_enable="no";}
	if($postscreen_non_smtp_command_enable==1){$postscreen_non_smtp_command_enable="yes";}else{$postscreen_non_smtp_command_enable="no";}
	
	
	$conf[]="postscreen_bare_newline_action= $postscreen_bare_newline_action";
	$conf[]="postscreen_bare_newline_enable= $postscreen_bare_newline_enable";
	$conf[]="postscreen_bare_newline_ttl= $postscreen_bare_newline_ttl";
	$conf[]="postscreen_cache_cleanup_interval= $postscreen_cache_cleanup_interval";
	$conf[]="postscreen_cache_retention_time= $postscreen_cache_retention_time";
	$conf[]="postscreen_client_connection_count_limit= $postscreen_client_connection_count_limit";
	$conf[]="postscreen_client_connection_count_limit= $postscreen_client_connection_count_limit";
	$conf[]="postscreen_pipelining_enable= $postscreen_pipelining_enable";
	$conf[]="postscreen_pipelining_action= $postscreen_pipelining_action";
	$conf[]="postscreen_pipelining_ttl= $postscreen_pipelining_ttl";
	$conf[]="postscreen_post_queue_limit= $postscreen_post_queue_limit";
	$conf[]="postscreen_pre_queue_limit= $postscreen_pre_queue_limit";
	$conf[]="postscreen_non_smtp_command_enable= $postscreen_non_smtp_command_enable";
	$conf[]="postscreen_non_smtp_command_action= $postscreen_non_smtp_command_action";
	$conf[]="postscreen_non_smtp_command_ttl= $postscreen_non_smtp_command_ttl";
	$conf[]="postscreen_forbidden_command= $postscreen_forbidden_commands";
	$conf[]="postscreen_dnsbl_action= $postscreen_dnsbl_action";
	$conf[]="postscreen_dnsbl_ttl= $postscreen_dnsbl_ttl";
	$conf[]="postscreen_dnsbl_threshold= $postscreen_dnsbl_threshold";
	$conf[]="postscreen_cache_map= btree:/var/lib/postfix/postscreen_{$this->myhostname}_cache";
	
	
	
	$dnsbl_array=unserialize(base64_decode($this->GET_BIGDATA("postscreen_dnsbl_sites")));
	if(is_array($dnsbl_array)){
        foreach ($dnsbl_array as $site=>$threshold){
		    if($site==null){continue;}
            $dnsbl_array_compiled[]="$site*$threshold";
        }
	}
		
	$final_dnsbl=null;
	if(is_array($dnsbl_array_compiled)){$final_dnsbl=@implode(",",$dnsbl_array_compiled);}
	$conf[]="postscreen_dnsbl_sites=$final_dnsbl";
	
	$sock=new sockets();
	$ipClass=new IP();
	
	
		$q=new mysql();
		$sql="SELECT * FROM postfix_whitelist_con";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "$q->mysql_error\n";}
	
		while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){	
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $ligne["ipaddr"])){
				$nets[]="{$ligne["ipaddr"]}\tdunno";
			}else{
				$hostsname[]="{$ligne["hostname"]}\tOK";
			}
			
		}	
	
	$ldap=new clladp();
	$networks=$ldap->load_mynetworks();	
	if(is_array($networks)){
        foreach ($networks as $num=>$ligne){
			if($ligne==null){continue;}
			if(!$ipClass->isIPAddress($ligne)){
				$hostsname[]="$ligne\tOK";
			}else{
				if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $num)){$nets[]="$num\tdunno";}
			}
		}
	}
	
		$networks=unserialize($this->GET_BIGDATA("mynetworks"));
		if(is_array($networks)){
            foreach ($networks as $num=>$ligne){
			    $ligne=trim($ligne);
			    if($ligne==null){continue;}
			    if(!$ipClass->isIPAddress($ligne)){
				    $hostsname[]="$ligne\tOK";
			    }else{
				    if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $num)){$nets[]="$num\tdunno";}
			    }
		    }
	    }

	if($PostfixMultiCreateBubble==1){
		$nets=array();
		$networks=$this->NetWorksBubble();
        foreach ($networks as $num=>$ligne){
			$num=trim($num);
			if($num==null){continue;}
			
			
			if(!$ipClass->isIPAddress($num)){
				$hostsname[]="$num\tOK";
			}else{
				if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $num)){$nets[]="$num\tdunno";}
			}	
		}
	}
	
	

	
	
	
	
	
	if(count($hostsname)>0){@file_put_contents("/etc/postfix-$this->myhostname/postscreen_access.hosts",@implode("\n",$hostsname));
	$postscreen_access=",hash:/etc/postfix-$this->myhostname/postscreen_access.hosts";}
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix-$this->myhostname/postscreen_access.hosts");
	
	
	
	if($TrustMyNetwork==1){$permit_mynetworks="permit_mynetworks,";}
	if(count($nets)>0){@file_put_contents("/etc/postfix-$this->myhostname/postscreen_access.cidr",@implode("\n",$nets));}
	$conf[]="postscreen_access_list={$permit_mynetworks}cidr:/etc/postfix-$this->myhostname/postscreen_access.cidr$postscreen_access";
	return @implode("\n",$conf);	
		
	}
	
	private function LOCATE_POSTFIX_DAEMON_DIRECTORY(){
		if(isset($GLOBALS[__FUNCTION__])){
			if(strlen($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		}
		$locate1=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_1();
		$locate2=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_2();
		$locate3=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY_3();
		if($locate2<>null){if($locate1<>$locate2){$locate1=$locate2;}}
		if($locate3<>null){if($locate1<>$locate3){$locate1=$locate3;}}
		if($locate1==null){"Starting......: ".date("H:i:s")." Postfix WARNING unable to find daemon directory\n";}
		$GLOBALS[__FUNCTION__]=$locate1;
		return $locate1; 
	}
	
	
	private function LOCATE_POSTFIX_DAEMON_DIRECTORY_1(){
		if(is_file('/usr/lib/postfix/master')){return ('/usr/lib/postfix');}
		if(is_file('/usr/libexec/postfix/master')){return ('/usr/libexec/postfix');}
		if(is_file('/usr/local/libexec/postfix/master')){return ('/usr/local/libexec/postfix');}
		}
	
	private function LOCATE_POSTFIX_DAEMON_DIRECTORY_2(){
		if(is_file('/usr/lib/postfix/postscreen')){return ('/usr/lib/postfix');}
		if(is_file('/usr/libexec/postfix/postscreen')){return ('/usr/libexec/postfix');}
		if(is_file('/usr/local/libexec/postfix/postscreen')){return ('/usr/local/libexec/postfix');}
		
	}	
	
	private function LOCATE_POSTFIX_DAEMON_DIRECTORY_3(){
		if(is_file('/usr/lib/postfix/postmulti-script')){return ('/usr/lib/postfix');}
		if(is_file('/usr/libexec/postfix/postmulti-script')){return ('/usr/libexec/postfix');}
		if(is_file('/usr/local/libexec/postfix/postmulti-script')){return ('/usr/local/libexec/postfix');}
	}	
	
	


	
	private function notifications_templates(){
		include_once(dirname(__FILE__)."/class.main_cf.inc");
		$mainTPL=new bounces_templates();
		$sock=new sockets();
		$PostfixPostmaster=$sock->GET_INFO("PostfixPostmaster");
		if(trim($PostfixPostmaster)==null){$PostfixPostmaster="postmaster";}	
		$conf=null;
		
		$double_bounce_sender=$this->GET("double_bounce_sender");
		$address_verify_sender=$this->GET("address_verify_sender");
		$twobounce_notice_recipient=$this->GET("2bounce_notice_recipient");
		$error_notice_recipient=$this->GET("error_notice_recipient");
		$delay_notice_recipient=$this->GET("delay_notice_recipient");
		$empty_address_recipient=$this->GET("empty_address_recipient");
		
	
		
		if($double_bounce_sender==null){$double_bounce_sender="double-bounce";};
		if($address_verify_sender==null){$address_verify_sender="\$double_bounce_sender";}
		if($twobounce_notice_recipient==null){$twobounce_notice_recipient="postmaster";}
		if($error_notice_recipient==null){$error_notice_recipient=$PostfixPostmaster;}
		if($delay_notice_recipient==null){$delay_notice_recipient=$PostfixPostmaster;}
		if($empty_address_recipient==null){$empty_address_recipient=$PostfixPostmaster;}	
		if(is_array($mainTPL->templates_array)){
            foreach ($mainTPL->templates_array as $template=>$nothing){
                $array=unserialize(base64_decode($this->GET_BIGDATA($template)));
                if(!is_array($array)){$array=$mainTPL->templates_array[$template];}
				$tp=explode("\n",$array["Body"]);
				$Body=null;
                foreach ($tp  as $line){
                    if(trim($line)==null){continue;}$Body=$Body.$line."\n";}
				$conf=$conf ."\n$template = <<EOF\n";
				$conf=$conf ."Charset: {$array["Charset"]}\n";
				$conf=$conf ."From:  {$array["From"]}\n";
				$conf=$conf ."Subject: {$array["Subject"]}\n";
				$conf=$conf ."\n";
				$conf=$conf ."$Body";
				$conf=$conf ."\n\n";
				$conf=$conf ."EOF\n";
				
			}
			@file_put_contents("/etc/postfix-$this->myhostname/bounce.template.cf",$conf);	
			$f[]="bounce_template_file = /etc/postfix-$this->myhostname/bounce.template.cf";
		}	

		$f[]="double_bounce_sender = $double_bounce_sender";
		$f[]="address_verify_sender = $address_verify_sender";
		$f[]="2bounce_notice_recipient = $twobounce_notice_recipient";
		$f[]="error_notice_recipient = $error_notice_recipient";	
		$f[]="delay_notice_recipient = $delay_notice_recipient";
		$f[]="empty_address_recipient = $empty_address_recipient";		
		return @implode("\n",$f);
	}

	

	private function debug_peer_list(){
		$datas=unserialize(base64_decode($this->GET_BIGDATA("debug_peer_list")));
		if(count($datas)==0){return;}
        foreach ($datas as $index=>$file){
			if(trim($index)==null){continue;}
			$f[]=$index;
		}
		
		if(count($f)>0){
			$cf[]="debug_peer_level = 3";
			$cf[]="debug_peer_list = ".@implode(",", $f);
			return @implode("\n", $cf);
		}
		
	}
	
	public function buildconf(){
        return false;
        $EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
        if($EnablePostfix==0){return "";}
		$sock=new sockets();
		$postfix_ver=$this->postfix_version();
		if(preg_match("#^([0-9]+)\.([0-9]+)#", $postfix_ver,$re)){$MAJOR=$re[1];$MINOR=$re[2];}
		
		
		$PostfixMultiCreateBubble=$sock->GET_INFO("PostfixMultiCreateBubble");
		$PostfixMultiTrustAllInstances=$sock->GET_INFO("PostfixMultiTrustAllInstances");
		if(!is_numeric($PostfixMultiCreateBubble)){$PostfixMultiCreateBubble=0;}
		if(!is_numeric($PostfixMultiTrustAllInstances)){$PostfixMultiTrustAllInstances=0;}			
		$EnableSasl=$this->GET("EnableSasl");
		$ssl_path="/etc/postfix-$this->myhostname/ssl";
		$message_size_limit=$this->GET("message_size_limit");
		$default_destination_recipient_limit=$this->GET("default_destination_recipient_limit");
		$smtpd_recipient_limit=$this->GET("smtpd_recipient_limit");
		$mime_nesting_limit=$this->GET("mime_nesting_limit");
		$header_address_token_limit=$this->GET("header_address_token_limit");
		$virtual_mailbox_limit=$this->GET("virtual_mailbox_limit");
		$addMyIpInsideMyNet=",$this->ip_addr";
		$smtp_connection_cache_destinationsF=null;
		
		$message_size_limit=$this->GET($message_size_limit/1024)/1000;
		$virtual_mailbox_limit=$this->GET($virtual_mailbox_limit/1024)/1000;	
		
		if($message_size_limit==null){$message_size_limit=102400000;}
		if($virtual_mailbox_limit==null){$virtual_mailbox_limit=102400000;}
		if($default_destination_recipient_limit==null){$default_destination_recipient_limit=50;}
		if($smtpd_recipient_limit==null){$smtpd_recipient_limit=1000;}
		if($mime_nesting_limit==null){$mime_nesting_limit=100;}
		if($header_address_token_limit==null){$header_address_token_limit=10240;}
		$nets=array();	
			$f[]="$ssl_path/ca.csr";
			$f[]="$ssl_path/ca.crt";
			$f[]="$ssl_path/ca.key";

            foreach ($f as $file){
				if(!is_file($file)){
					$this->certificate_generate();
					break;
				}
			}
		
			$myhostname=$this->myhostname;
			echo "Starting......: ".date("H:i:s")." Postfix \"$myhostname\" is a member of $this->ou IP $this->ip_addr\n";

			
			$mailbox_transport=$this->getMailBoxTransport();
			$nets=unserialize($this->GET_BIGDATA("mynetworks"));
			if(is_array($nets)){
                foreach ($nets as $d=>$z){
					if(trim($z)==null){continue;}
					if(trim($z)==$this->ip_addr){continue;}
					echo "Starting......: ".date("H:i:s")." Postfix \"$myhostname\" Trusted network (mynetworks) `$z`\n";
					$newnets[$z]=$z;
				}
				$nets=array();
                foreach ($newnets as $d=>$z){$nets[]=$z;}
			}
			
			if($PostfixMultiCreateBubble==1){
				$addMyIpInsideMyNet=null;
				$nets=array();
				$newnets=$this->NetWorksBubble();
				
				echo "Starting......: ".date("H:i:s")." Postfix \"$myhostname\" NetWorksBubble = ". count($newnets)." rows\n";
                foreach ($newnets as $d=>$z){
					if($d==$this->ip_addr){continue;}
					echo "Starting......: ".date("H:i:s")." Postfix \"$myhostname\" Trusted network (mynetworks) `$d`\n";
					$nets[]=$d;
                }
			}
			
			
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Postfix \"$myhostname\" Network:PostfixMultiTrustAllInstances = $PostfixMultiTrustAllInstances\n";}
			if($PostfixMultiTrustAllInstances==1){
				$MULTINETS=$this->MULTINETS();
				echo "Starting......: ".date("H:i:s")." Postfix \"$myhostname\" Trust ".count($MULTINETS)." instances enabled\n";
                foreach ($MULTINETS as $e=>$w){
					$nets[]=$e;
				}
			}

            foreach ($nets as $s=>$g){if(trim($g)==null){continue;}$newnet2[$g]=$g;}
			
			$nets=array();
			$ArrayBadNets=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixBadNettr")));

            foreach ($newnet2 as $s=>$g){
				$g=trim($g);
				if(isset($ArrayBadNets[$g])){
					if($ArrayBadNets[$g]==0){continue;}
					if($ArrayBadNets[$g]<>null){
						$nets[]=$ArrayBadNets[$g];
						continue;
					}
				}
				$nets[]=$g;}
			
			if(is_array($nets)){$mynetworks=",".@implode(",",$nets);}	
			$myhostname_maincf=$myhostname;
			
			$VirtualHostnameToChange=trim($this->GET("VirtualHostNameToChange"));
			if($VirtualHostnameToChange<>null){
				$myhostname_maincf=$VirtualHostnameToChange;
				echo "Starting......: ".date("H:i:s")." Postfix $myhostname is now virtualy called $VirtualHostnameToChange\n";
			}
			
			$mynetworkfinal="127.0.0.0/8 [::ffff:127.0.0.0]/104 [::1]/128$addMyIpInsideMyNet,{$mynetworks}";
			$mynetworkfinal=str_replace(",,", ",", $mynetworkfinal);
			if(substr($mynetworkfinal, strlen($mynetworkfinal),1)==','){$mynetworkfinal=substr($mynetworkfinal, 0,strlen($mynetworkfinal)-1);}
			
			$smtpd_banner=$this->GET("smtpd_banner");
			if($smtpd_banner==null){$smtpd_banner="\$myhostname ESMTP \$mail_name"; }
			
			$conf[]="smtpd_banner=$smtpd_banner";
			
			$haproxy_compliance=$this->haproxy_compliance();
			if($haproxy_compliance<>null){$conf[]=$haproxy_compliance;}
			
			$conf[]="biff=no";
			$conf[]="";
			$conf[]="#  NETWORK ---------------------------------";			
			$conf[]="myhostname = $myhostname_maincf";
			if($this->ip_addr==null){echo "Starting......: ".date("H:i:s")." Postfix $myhostname warning inet_interfaces is null !\n";}
			
			$myorigin=$this->GET("myorigin");
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" myorigin = `$myorigin` or default = \$mydomain\n";
			if($myorigin==null){$myorigin="\$mydomain";}
			
			$ipClass=new IP();
			
			$conf[]="mynetworks=$mynetworkfinal";
			if($this->EnableipV6==1){
				if($ipClass->isIPv6($this->ip_addr)){
					echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" activate ipv6 only\n";
					$conf[]="smtp_bind_address6 = $this->ip_addr";
					$conf[]="inet_protocols = ipv6";
					$this->checkipv6($this->ip_addr);
				}else{
					$conf[]="inet_interfaces = $this->ip_addr";
					$conf[]="smtp_bind_address = $this->ip_addr";
					$conf[]="inet_protocols = ipv4";
				}
				
			}else{
				$conf[]="inet_interfaces = $this->ip_addr";
				$conf[]="smtp_bind_address = $this->ip_addr";
				$conf[]="inet_protocols = ipv4";
			}
	
			$conf[]="myorigin=$myorigin";
			$conf[]="";
			$conf[]="append_dot_mydomain=no";
			$conf[]="readme_directory=no";
			$conf[]="recipient_delimiter=+";
			
			$conf[]="";
			$conf[]="#  RESTRICTIONS ---------------------------------";					
			$conf[]="message_size_limit = $message_size_limit";
			$conf[]="mime_nesting_limit = $mime_nesting_limit";
			$conf[]="header_address_token_limit = $header_address_token_limit";	
			$conf[]="default_destination_recipient_limit = $default_destination_recipient_limit";
			$conf[]="smtpd_recipient_limit = $smtpd_recipient_limit";
			$conf[]="smtpd_delay_reject = yes";			
			$conf[]="mailbox_size_limit=$virtual_mailbox_limit";
			$conf[]="virtual_mailbox_limit = $virtual_mailbox_limit";
			$conf[]="artica-filter_destination_recipient_limit = 1";
			$conf[]="artica-adv_destination_recipient_limit = 1";
			$conf[]="artica_destination_recipient_limit = 1";
			$conf[]="zarafa_destination_recipient_limit = 1";
			$conf[]="smtpd_restriction_classes = artica_restrict_relay_domains";
			$conf[]="artica_restrict_relay_domains = reject_unverified_recipient";
			
			
			
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" checking restrictions\n";

			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" checking smtpd_client_restrictions\n";
			$conf[]=$this->smtpd_client_restrictions();
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" checking smtpd_end_of_data_restrictions\n";
			$conf[]=$this->smtpd_end_of_data_restrictions();
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" checking body_checks\n";
			$conf[]=$this->body_checks();
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" checking restrictions done.\n";
			$conf[]="";
			$conf[]="bounce_service_name=bounce";
			$conf[]="bounce_size_limit=50000";
			$conf[]="bounce_notice_recipient=postmaster";
			$conf[]="double_bounce_sender=double-bounce";
			
			$smtp_connection_cache_on_demand=$this->GET("smtp_connection_cache_on_demand");
			$smtp_connection_cache_time_limit=$this->GET("smtp_connection_cache_time_limit");
			$smtp_connection_reuse_time_limit=$this->GET("smtp_connection_reuse_time_limit");
			$connection_cache_ttl_limit=$this->GET("connection_cache_ttl_limit");
			$connection_cache_status_update_time=$this->GET("connection_cache_status_update_time");
			$smtp_connection_cache_destinations=unserialize(base64_decode($this->GET_BIGDATA("smtp_connection_cache_destinations")));	
			
			$address_verify_map=$this->GET("address_verify_map");
			$address_verify_negative_cache=$this->GET("address_verify_negative_cache");
			$address_verify_poll_count=$this->GET("address_verify_poll_count");
			$address_verify_poll_delay=$this->GET("address_verify_poll_delay");
			$address_verify_sender=$this->GET("address_verify_sender");
			$address_verify_negative_expire_time=$this->GET("address_verify_negative_expire_time");
			$address_verify_negative_refresh_time=$this->GET("address_verify_negative_refresh_time");
			$address_verify_positive_expire_time=$this->GET("address_verify_positive_expire_time");
			$address_verify_positive_refresh_time=$this->GET("address_verify_positive_refresh_time");
			if($address_verify_map==null){$address_verify_map="btree:/var/lib/postfix-$this->myhostname/verify";}	

			$smtpd_error_sleep_time=$this->GET("smtpd_error_sleep_time");
			$smtpd_soft_error_limit=$this->GET("smtpd_soft_error_limit");
			$smtpd_hard_error_limit=$this->GET("smtpd_hard_error_limit");
			$smtpd_client_connection_count_limit=$this->GET("smtpd_client_connection_count_limit");
			$smtpd_client_connection_rate_limit=$this->GET("smtpd_client_connection_rate_limit");
			$smtpd_client_message_rate_limit=$this->GET("smtpd_client_message_rate_limit");
			$smtpd_client_recipient_rate_limit=$this->GET("smtpd_client_recipient_rate_limit");
			$smtpd_client_new_tls_session_rate_limit=$this->GET("smtpd_client_new_tls_session_rate_limit");
			$smtpd_client_event_limit_exceptions=$this->GET("smtpd_client_event_limit_exceptions");
			$in_flow_delay=$this->GET("in_flow_delay");
			$smtp_connect_timeout=$this->GET("smtp_connect_timeout");
			$smtp_helo_timeout=$this->GET("smtp_helo_timeout");
			$initial_destination_concurrency=$this->GET("initial_destination_concurrency");
			$default_destination_concurrency_limit=$this->GET("default_destination_concurrency_limit");
			$local_destination_concurrency_limit=$this->GET("local_destination_concurrency_limit");
			$smtp_destination_concurrency_limit=$this->GET("smtp_destination_concurrency_limit");
			$default_destination_recipient_limit=$this->GET("default_destination_recipient_limit");
			$smtpd_recipient_limit=$this->GET("smtpd_recipient_limit");
			$queue_run_delay=$this->GET("queue_run_delay");  
			$minimal_backoff_time =$this->GET("minimal_backoff_time");
			$maximal_backoff_time =$this->GET("maximal_backoff_time");
			$maximal_queue_lifetime=$this->GET("maximal_queue_lifetime"); 
			$bounce_queue_lifetime =$this->GET("bounce_queue_lifetime");
			$qmgr_message_recipient_limit =$this->GET("qmgr_message_recipient_limit");
			$default_process_limit=$this->GET("default_process_limit");	
			$qmgr_message_active_limit=$this->GET("qmgr_message_active_limit");
			$qmgr_message_recipient_minimum=$this->GET("qmgr_message_recipient_minimum");		
			$smtp_fallback_relay=$this->GET("smtp_fallback_relay");
			
			$smtpd_reject_unlisted_recipient=$this->GET("smtpd_reject_unlisted_recipient");
			if(!is_numeric($smtpd_reject_unlisted_recipient)){$smtpd_reject_unlisted_recipient=1;}
			
			$smtpd_reject_unlisted_sender=$this->GET("smtpd_reject_unlisted_sender");
			if(!is_numeric($smtpd_reject_unlisted_sender)){$smtpd_reject_unlisted_sender=0;}			

			if(!is_numeric($qmgr_message_active_limit)){$qmgr_message_active_limit=20000;}
			if(!is_numeric($qmgr_message_recipient_limit)){$qmgr_message_recipient_limit=20000;}
			if(!is_numeric($qmgr_message_recipient_minimum)){$qmgr_message_recipient_minimum=10;}				
			if(!is_numeric($smtp_connection_cache_on_demand)){$smtp_connection_cache_on_demand=1;}
			if($smtp_connection_cache_time_limit==null){$smtp_connection_cache_time_limit="2s";}
			if($smtp_connection_reuse_time_limit==null){$smtp_connection_reuse_time_limit="300s";}
			if($connection_cache_ttl_limit==null){$connection_cache_ttl_limit="2s";}
			if($connection_cache_status_update_time==null){$connection_cache_status_update_time="600s";}	
			if($smtp_connection_cache_on_demand==1){$smtp_connection_cache_on_demand="yes";}else{$smtp_connection_cache_on_demand="no";}	
			
			if(!is_numeric($address_verify_negative_cache)){$address_verify_negative_cache=1;}
			if(!is_numeric($address_verify_poll_count)){$address_verify_poll_count=3;}
			if($address_verify_poll_delay==null){$address_verify_poll_delay="3s";}
			if($address_verify_sender==null){$address_verify_sender="double-bounce";}
			if($address_verify_negative_expire_time==null){$address_verify_negative_expire_time="3d";}
			if($address_verify_negative_refresh_time==null){$address_verify_negative_refresh_time="3h";}
			if($address_verify_positive_expire_time==null){$address_verify_positive_expire_time="31d";}
			if($address_verify_positive_refresh_time==null){$address_verify_positive_refresh_time="7d";}
			if($address_verify_negative_cache==1){$address_verify_negative_cache="yes";}else{$address_verify_negative_cache="no";}	

			if($smtpd_error_sleep_time==null){$smtpd_error_sleep_time="1s";}
			if(!is_numeric($smtpd_soft_error_limit)){$smtpd_soft_error_limit=10;}
			if(!is_numeric($smtpd_hard_error_limit)){$smtpd_hard_error_limit=20;}
			if(!is_numeric($smtpd_client_connection_count_limit)){$smtpd_client_connection_count_limit=50;}
			if(!is_numeric($smtpd_client_connection_rate_limit)){$smtpd_client_connection_rate_limit=0;}
			if(!is_numeric($smtpd_client_message_rate_limit)){$smtpd_client_message_rate_limit=0;}
			if(!is_numeric($smtpd_client_recipient_rate_limit)){$smtpd_client_recipient_rate_limit=0;}
			if(!is_numeric($smtpd_client_new_tls_session_rate_limit)){$smtpd_client_new_tls_session_rate_limit=0;}
			if(!is_numeric($initial_destination_concurrency)){$initial_destination_concurrency=5;}
			if(!is_numeric($default_destination_concurrency_limit)){
				echo "Starting......: ".date("H:i:s")." Postfix $this->myhostname default_destination_concurrency_limit (back to default).\n";
				$default_destination_concurrency_limit=20;
			}
			if(!is_numeric($smtp_destination_concurrency_limit)){
				echo "Starting......: ".date("H:i:s")." Postfix $this->myhostname smtp_destination_concurrency_limit (back to default).\n";
				$smtp_destination_concurrency_limit=20;}
			if(!is_numeric($local_destination_concurrency_limit)){
				echo "Starting......: ".date("H:i:s")." Postfix $this->myhostname local_destination_concurrency_limit (back to default).\n";
				$local_destination_concurrency_limit=2;}
			
			
			
			if(!is_numeric($default_destination_recipient_limit)){$default_destination_recipient_limit=50;}
			if(!is_numeric($smtpd_recipient_limit)){$smtpd_recipient_limit=1000;}
			if(!is_numeric($default_process_limit)){$default_process_limit=100;}
			if(!is_numeric($qmgr_message_recipient_limit)){$qmgr_message_recipient_limit=20000;}
			if($smtpd_client_event_limit_exceptions==null){$smtpd_client_event_limit_exceptions="\$mynetworks";}
			if($in_flow_delay==null){$in_flow_delay="1s";}
			if($smtp_connect_timeout==null){$smtp_connect_timeout="30s";}
			if($smtp_helo_timeout==null){$smtp_helo_timeout="300s";}
			if($bounce_queue_lifetime==null){$bounce_queue_lifetime="5d";}
			if($maximal_queue_lifetime==null){$maximal_queue_lifetime="5d";}
			if($maximal_backoff_time==null){$maximal_backoff_time="4000s";}
			if($minimal_backoff_time==null){$minimal_backoff_time="300s";}
			if($queue_run_delay==null){$queue_run_delay="300s";}			

			if(is_array($smtp_connection_cache_destinations)){
				if(count($smtp_connection_cache_destinations)>0){
                    foreach ($smtp_connection_cache_destinations as $host => $none){
                        $smtp_connection_cache_destinationsR[]=$host;
                    }
					$smtp_connection_cache_destinationsF=@implode(",", $smtp_connection_cache_destinationsR);
				}
			}			
			
			

			$conf[]="smtpd_reject_unlisted_recipient=yes";
			$conf[]="smtp_connection_cache_on_demand=$smtp_connection_cache_on_demand";
			$conf[]="smtp_connection_cache_time_limit=$smtp_connection_cache_time_limit";
			$conf[]="smtp_connection_reuse_time_limit=$smtp_connection_reuse_time_limit";
			$conf[]="connection_cache_ttl_limit=$connection_cache_ttl_limit";
			$conf[]="connection_cache_status_update_time=$connection_cache_status_update_time";
			$conf[]="smtp_connection_cache_destinations=$smtp_connection_cache_destinationsF";
			$conf[]="address_verify_map=$address_verify_map";
			$conf[]="address_verify_sender=$address_verify_sender";
			$conf[]="address_verify_negative_cache=$address_verify_negative_cache";
			$conf[]="address_verify_negative_expire_time=$address_verify_negative_expire_time";
			$conf[]="address_verify_negative_refresh_time=$address_verify_negative_refresh_time";
			$conf[]="address_verify_poll_count=$address_verify_poll_count";
			$conf[]="address_verify_poll_delay=$address_verify_poll_delay";
			
			$conf[]="address_verify_positive_expire_time=$address_verify_positive_expire_time";
			$conf[]="address_verify_positive_refresh_time=$address_verify_positive_refresh_time";
			
			

			$conf[]="smtpd_error_sleep_time=$smtpd_error_sleep_time";
			$conf[]="smtpd_hard_error_limit=$smtpd_hard_error_limit";
			$conf[]="smtpd_soft_error_limit=$smtpd_soft_error_limit";
			$conf[]="smtpd_client_connection_count_limit=$smtpd_client_connection_count_limit";
			$conf[]="smtpd_client_connection_rate_limit=$smtpd_client_connection_rate_limit";
			$conf[]="smtpd_client_message_rate_limit=$smtpd_client_message_rate_limit";
			$conf[]="smtpd_client_recipient_rate_limit=$smtpd_client_recipient_rate_limit";
			$conf[]="smtpd_client_event_limit_exceptions=$smtpd_client_event_limit_exceptions";
			
			
			
			
			$in_flow_delay=$this->GET("in_flow_delay");
			$freeze_delivery_queue=$this->GET("freeze_delivery_queue");
			if($freeze_delivery_queue==1){
				echo "Starting......: ".date("H:i:s")." Postfix delivery queue is frozen..\n";
				$in_flow_delay="0";
				$conf[]="master_service_disable=qmgr.fifo";
			}	
			

			if($in_flow_delay<>null){$conf[]="in_flow_delay=$in_flow_delay";}
			if($minimal_backoff_time<>null){$conf[]="minimal_backoff_time=$minimal_backoff_time";}
			if($maximal_backoff_time<>null){$conf[]="maximal_backoff_time=$maximal_backoff_time";}
			if($bounce_queue_lifetime<>null){$conf[]="bounce_queue_lifetime=$bounce_queue_lifetime";}
			if($default_process_limit<>null){$conf[]="default_process_limit=$default_process_limit";}
			if($maximal_queue_lifetime<>null){$conf[]="maximal_queue_lifetime=$maximal_queue_lifetime";}
			if($queue_run_delay==null){$queue_run_delay="5m";}
			
			$conf[]="smtp_helo_timeout=$smtp_helo_timeout";
			$conf[]="smtp_connect_timeout=$smtp_connect_timeout";
			if($smtp_fallback_relay<>null){
				$conf[]="smtp_fallback_relay=$smtp_fallback_relay";	
			}
			$conf[]="queue_run_delay=$queue_run_delay";
			$conf[]="qmgr_message_active_limit=$qmgr_message_active_limit";
			$conf[]="qmgr_message_recipient_limit=$qmgr_message_recipient_limit";
			$conf[]="qmgr_message_recipient_minimum=$qmgr_message_recipient_minimum";
			
			$enable_original_recipient=$this->GET("enable_original_recipient");
			if(!is_numeric($enable_original_recipient)){$enable_original_recipient=1;}
			$undisclosed_recipients_header=$this->GET("undisclosed_recipients_header");
			if($undisclosed_recipients_header==null){$undisclosed_recipients_header="To: undisclosed-recipients:;";}
			
			$conf[]="smtpd_timeout=300";
			$conf[]="enable_original_recipient=yes";
			$conf[]="ignore_mx_lookup_error=no";
			$conf[]="disable_dns_lookups=no";
			$conf[]=$this->debug_peer_list();
			
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" default_destination_concurrency_limit:`$default_destination_concurrency_limit`\n";
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" local_destination_concurrency_limit..:`$local_destination_concurrency_limit`\n";
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" smtp_destination_concurrency_limit...:`$smtp_destination_concurrency_limit`\n";
			
			
			if($enable_original_recipient==1){
				$conf[]="smtp_sender_dependent_authentication = yes";
			}else{
				$conf[]="smtp_sender_dependent_authentication = no";
			}
			$smtpd_discard_ehlo_keywords=$this->GET("smtpd_discard_ehlo_keywords");
			if($smtpd_discard_ehlo_keywords<>null){$conf[]="smtpd_discard_ehlo_keywords=$smtpd_discard_ehlo_keywords";}
			$conf[]="undisclosed_recipients_header=$undisclosed_recipients_header";
			$conf[]="initial_destination_concurrency=$initial_destination_concurrency";
			$conf[]="default_destination_concurrency_limit=$default_destination_concurrency_limit";
			$conf[]="local_destination_concurrency_limit=$local_destination_concurrency_limit";
			$conf[]="smtp_destination_concurrency_limit=$smtp_destination_concurrency_limit";
			$conf[]="";
			$conf[]="# Templates and notifications ---------------------------------";
			$conf[]=$this->notifications_templates();
			$conf[]="";
			$conf[]="smtp_send_xforward_command=yes";
			$conf[]="";
			
			$conf[]="#  SASL / TLS ---------------------------------";
			$conf[]="broken_sasl_auth_clients = yes";			
			if($EnableSasl==1){	
				$sock=new sockets();
				$smtpd_sasl_path=$sock->GET_INFO("smtpd_sasl_path");
				if($smtpd_sasl_path==null){$smtpd_sasl_path="smtpd";}
				$conf[]="smtpd_sasl_auth_enable = yes";
				$conf[]="smtpd_sasl_authenticated_header = yes";
				$conf[]="smtpd_sasl_path=$smtpd_sasl_path";
				$conf[]="smtpd_sasl_local_domain = ";
				$conf[]="smtpd_sasl_exceptions_networks = \$mynetworks";
			}

			$conf[]=$this->cyruslmtp();
			
			$conf[]="smtpd_tls_auth_only = no";
			$conf[]="smtpd_tls_ask_ccert=no";
			$conf[]="smtpd_tls_security_level = may";
			$conf[]="smtpd_tls_req_ccert=no";
			$conf[]="smtpd_tls_received_header = yes";
			$conf[]="smtpd_tls_CAfile=$ssl_path/ca.csr";
			$conf[]="smtpd_tls_cert_file=$ssl_path/ca.crt";
			$conf[]="smtpd_tls_key_file=$ssl_path/ca.key";
			$conf[]="smtpd_tls_session_cache_database=btree:\${data_directory}/smtpd_scache";
			$conf[]="smtp_tls_session_cache_database=btree:\${data_directory}/smtpd_tls_session_cache";						
			$conf[]="smtpd_client_new_tls_session_rate_limit=$smtpd_client_new_tls_session_rate_limit";
			$conf[]="";
			$conf[]="#  SMTP CLIENT SASL ---------------------------------";
			$conf[]=$this->smtp_sasl_password_map();
			
			
			$conf[]="virtual_uid_maps=static:5000";
			$conf[]="virtual_gid_maps=static:5000";
			$conf[]="bounce_template_file=/etc/postfix/bounce.template.cf";
			if(!is_file("/etc/postfix/bounce.template.cf")){$unix=new unix();$php5=$unix->LOCATE_PHP5_BIN();shell_exec("$php5 /usr/share/artica-postfix/exec.postfix.maincf.php --notifs-templates-force");}
			$conf[]="";
			$conf[]="#  PostScreen ---------------------------------";
			$conf[]=$this->POSTSCREEN();
			$conf[]="";
			
			$conf[]="";
			$conf[]="#  DATABASES ---------------------------------";
			
			
			
			
			if($MAJOR>1){
				if($MINOR>9){
					$conf[]="smtpd_relay_restrictions = permit_mynetworks, permit_sasl_authenticated, defer_unauth_destination";
				}
			}
			$conf[]="smtpd_reject_unlisted_recipient=" .$this->YesNo($smtpd_reject_unlisted_recipient);
			$conf[]="smtpd_reject_unlisted_sender=" .$this->YesNo($smtpd_reject_unlisted_sender);
			
			$conf[]="artica_destination_recipient_limit = 1";
			$conf[]="artica-filter_destination_recipient_limit = 1";
			$this->LoadLDAPDBs();
			$this->recipient_bcc_domain_maps();
			$db[]=$this->relais_domains();
			$db[]=$this->alias_maps();
			$db[]=$this->transport_maps();
			$db[]=$this->mydestination();
			$db[]=$this->recipient_canonical_maps();
			$db[]=$this->sender_canonical_maps();
			$db[]=$this->sender_dependent_relayhost_maps();
			$db[]=$this->sender_bcc_maps();
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" check recipient bcc\n";
			$db[]=$this->recipient_bcc_maps();	

            foreach ($db as $ligne){
				if($ligne==null){continue;}
				$conf[]=$ligne;
			}
			
			$conf[]=$this->mailbox_transport_maps();
			
			if(trim($mailbox_transport)<>null){
				$conf[]="";
				$conf[]="#  MAILBOXES ---------------------------------";
				$conf[]="mailbox_transport = $mailbox_transport";
				$conf[]="virtual_transport=\$mailbox_transport";
				$conf[]="mailman_destination_recipient_limit = 1";
			}
			$conf[]="";
					
			$conf[]="disable_vrfy_command=yes";
			$conf[]="smtpd_delay_reject=yes";
			$conf[]="smtpd_helo_required=yes";
			$conf[]=$this->relayhost();
			
			
			
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" relayhost ?? ($relayhost)\n";
			
			
			
			$conf[]="";	
			$conf[]="#Checks headers and bodys ---------------------------------";
			$conf[]="";	

			
			//$conf[]=$this->mime_header_checks();

			//$conf[]="content_filter = artica-filter:";
			$conf[]="receive_override_options=";
			$conf[]="auth_relay = ";
			$conf[]="";
			$conf[]=$this->milters();		
			$conf[]="";
			$conf[]="";	
			$conf[]="#Multi instances parameters ---------------------------------";
			$conf[]="";	
			$conf[]="queue_directory = /var/spool/postfix-$this->myhostname";	
			$conf[]="data_directory = /var/lib/postfix-$this->myhostname";
			
			$daemon_directory=$this->LOCATE_POSTFIX_DAEMON_DIRECTORY();
			echo "Starting......: ".date("H:i:s")." Postfix daemon directory \"$daemon_directory\"\n";
			$conf[]="daemon_directory = ". $this->LOCATE_POSTFIX_DAEMON_DIRECTORY();
			
			$conf[]="multi_instance_name = postfix-$this->myhostname";
			$conf[]="multi_instance_wrapper=\${command_directory}/postmulti -p --";
			$conf[]="multi_instance_enable = yes";
			$conf[]="";		
			
			$this->CheckDirectories($this->myhostname);
			if(!is_dir("/etc/postfix-$this->myhostname")){
				echo "Starting......: ".date("H:i:s")." Postfix creating dir /etc/postfix-$this->myhostname\n";
				@mkdir("/etc/postfix-$this->myhostname",null,true);
			}
			
			
			
			@file_put_contents("/etc/postfix-$this->myhostname/main.cf",@implode("\n",$conf));
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" main.cf done\n";
			$this->PostfixMainCfDefaultInstance();
		
	}
	private function LoadLDAPDBs(){
		$databases_list=unserialize(base64_decode($this->GET_BIGDATA("ActiveDirectoryDBS")));	
		if(is_array($databases_list)){
            foreach ($databases_list as $dbindex => $array){
				if($array["enabled"]<>1){continue;}
				$targeted_file=$this->buidLdapDB($this->myhostname,$dbindex,$array);
				if(!is_file($targeted_file)){continue;}
				if($array["resolv_domains"]==1){$this->buidLdapDBDomains($array);}
				$GLOBALS["LDAPDBS"][$array["database_type"]][]="ldap:$targeted_file";
			}	
		}
	}

	public function YesNo($value){
		if($value==1){return "yes";}
		if($value==0){return "no";}
		if(is_bool($value)){
			if($value){return "yes";}else{return "no";}
		}
	}
	
	private function checkipv6($ip=null){
		if($ip==null){
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" IPV6: Checks ipv6 no ip set\n";
			return;}
		if(!$this->AS_ROOT){return;}
		$unix=new unix();
		$ipbin=$unix->find_program("ip");
		$q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
		$sql="SELECT * FROM  nics_virtuals WHERE ipaddr='$ip' AND ipv6=1";	
		$ligne=$q->mysqli_fetch_array($sql);
		if(!$this->q->ok){
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" IPV6: $ip {$this->q->mysql_error}\n";
		}	
		if(!is_numeric($ligne["ID"])){$ligne["ID"]=0;}
		if($ligne["ID"]==0){
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" IPV6: $ip not a virtual interface\n";
			return;}
		$netmask=$ligne["netmask"];
		$gateway=$ligne["gateway"];
		$eth=$ligne["nic"];
		$cmd="$ipbin addr add dev $eth $ip/$netmask";
		echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" IPV6:  Checks ipv6 $ip/$netmask on $eth\n";
		shell_exec($cmd);
		
	}
	
	private function milters($onlymilter_config=false){
		$users=new usersMenus();
		$sock=new sockets();
		$KasxFilterEnabled=$sock->GET_INFO("KasxFilterEnabled");
		$kavmilterEnable=$sock->GET_INFO("kavmilterEnable");		
		$array_filters=unserialize(base64_decode($this->GET_BIGDATA("PluginsEnabled")));
		$EnableDKFilter=$sock->GET_INFO("EnableDKFilter");
		$EnableDkimMilter=$sock->GET_INFO("EnableDkimMilter");
		if(!is_numeric($EnableDkimMilter)){$EnableDkimMilter=0;}
		
		echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" EnableDKFilter   = `$EnableDKFilter`\n";
		echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" EnableDkimMilter = `$EnableDkimMilter`\n";
		
		
		if($users->OPENDKIM_INSTALLED){
			if($EnableDKFilter==1){
				if($array_filters["APP_OPENDKIM"]==1){
						echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" OpenDKIM mail filter Enabled\n";
						$smtpd_milters[]="unix:/var/run/opendkim/opendkim.sock";
						$nosmtpd_milters[]="unix:/var/run/opendkim/opendkim.sock";
					}
				}
				
			}
		
		
		
		
		if($users->MILTER_DKIM_INSTALLED){
			if($EnableDkimMilter==1){
				if($array_filters["APP_MILTER_DKIM"]==1){
					if($EnableDkimMilter==1){
						echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" Milter-DKIM mail filter Enabled\n";
						$smtpd_milters[]="unix:/var/run/dkim-milter/dkim-milter.sock";
						$nosmtpd_milters[]="unix:/var/run/dkim-milter/dkim-milter.sock";
					}
				}
				
			}
		}		
		
		
		if($users->KAV_MILTER_INSTALLED){
			if($KasxFilterEnabled==1){
				if($array_filters["APP_KAS3"]==1){
					echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" Kaspersky AntiSpam Enabled\n";
					$smtpd_milters[]="unix:/var/run/kas-milter.socket";
				}
			}
		}
		
		
		if($users->KAV_MILTER_INSTALLED){
			if($kavmilterEnable==1){
				if($array_filters["APP_KAVMILTER"]==1){
					echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" Kaspersky antivirus Enabled\n";
					$smtpd_milters[]="inet:127.0.0.1:1052";
				}
			}
		}
		
		if($users->MILTERGREYLIST_INSTALLED){
			if($array_filters["APP_MILTERGREYLIST"]==1){
				echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" milter-greylist Enabled\n";
				$smtpd_milters[]="unix:/var/spool/postfix/var/run/milter-greylist/$this->myhostname/greylist.sock";
			}
		}

		if(!is_array($smtpd_milters)){return null;}
			$conf[]="smtpd_milters=" . implode(" ",$smtpd_milters);
			
			if($onlymilter_config){
				return implode(" ",$smtpd_milters);
			}
			
			$conf[]="milter_connect_macros = j _ {daemon_name} {if_name} {if_addr} {client_name} {client_addr} {client_resolve} {client_ptr}";
			$conf[]="milter_helo_macros = {tls_version} {cipher} {cipher_bits} {cert_subject} {cert_issuer}";
			$conf[]="milter_mail_macros = i {auth_type} {auth_authen} {auth_ssf} {auth_author} {mail_mailer} {mail_host} {mail_addr} {client_addr} {if_addr}";
			$conf[]="milter_rcpt_macros = {rcpt_mailer} {rcpt_host} {rcpt_addr} {client_addr} {if_addr}";
			$conf[]="milter_default_action = accept";
			$conf[]="milter_protocol = 3";
			$conf[]="milter_connect_timeout=180";
			$conf[]="milter_command_timeout=180";
			$conf[]="milter_content_timeout=600";	
			if(count($nosmtpd_milters)>0){
				echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" non_smtpd_milters `".implode(" ",$nosmtpd_milters)."`\n";
				$conf[]="non_smtpd_milters =".implode(" ",$nosmtpd_milters)."\n";
			}
			
			$conf[]="";
			return implode("\n",$conf);
			
		
	}
	
	public function ConfigureMilters(){
		$milters=$this->milters(true);
		echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" configure milters\n";
		if($GLOBALS["postconf"]==null){$unix=new unix();$GLOBALS["postconf"]=$unix->find_program("postconf");}
		if($GLOBALS["postmulti"]==null){$unix=new unix();$GLOBALS["postmulti"]=$unix->find_program("postmulti");}
		shell_exec("{$GLOBALS["postconf"]} -c \"/etc/postfix-$this->myhostname\" -e \"smtpd_milters = $milters\"");
		shell_exec("{$GLOBALS["postmulti"]} -i $this->myhostname -p reload");
		
		
	}
	
	
	public function CheckDirectories($hostname){
		
		$queues[]="active";
		$queues[]="public";
		$queues[]="maildrop";
		$queues[]="pid";
		$queues[]="corrupt";
		$queues[]="defer";
		$queues[]="deferred";
		$queues[]="dev";
		$queues[]="etc";
		$queues[]="flush";
		$queues[]="hold";
		$queues[]="incoming";
		$queues[]="lib";
		$queues[]="private";
		$queues[]="public";
		$queues[]="saved";
		$queues[]="spamass";
		$queues[]="trace";
		$queues[]="usr";
		$queues[]="var";
		$queues[]="bounce";
		
		
		foreach ($queues as $dir){
			if(!is_dir("/var/spool/postfix-$hostname/$dir")){
				echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" Create directory $dir\n";
				@mkdir("/var/spool/postfix-$hostname/$dir",0600,true);
			}
		}
	
		$queues_postdrop[]="maildrop";
		$queues_postdrop[]="public";
		
		$queues_postfix[]="active";
		$queues_postfix[]="bounce"; 
		$queues_postfix[]="corrupt";
		$queues_postfix[]="defer";
		$queues_postfix[]="deferred";
		$queues_postfix[]="flush";
		$queues_postfix[]="hold";
		$queues_postfix[]="incoming";
		$queues_postfix[]="private";
		$queues_postfix[]="saved";
		$queues_postfix[]="trace";
		$queues_postfix[]="var";

        foreach ($queues_postdrop as $dir){
				shell_exec("/bin/chown -R postfix:postdrop /var/spool/postfix-$hostname/$dir");
		}
        foreach ($queues_postfix as $dir){
				shell_exec("/bin/chown -R postfix:root /var/spool/postfix-$hostname/$dir");
		}		
		
		
		@mkdir("/var/lib/postfix-$hostname",0600,true);
		@mkdir("/etc/postfix-$hostname",0600,true);
		shell_exec("/bin/chown -R postfix:postfix /var/lib/postfix-$hostname");
		shell_exec("/bin/chown -R root:root /etc/postfix-$hostname");		
		
		
		if(!is_file("/etc/postfix-$hostname/dynamicmaps.cf")){
			shell_exec("/bin/ln -sf /etc/postfix/dynamicmaps.cf /etc/postfix-$hostname/dynamicmaps.cf");
		}
		
		if(!is_file("/etc/postfix-$hostname/sasl/smtpd.conf")){
			@mkdir("/etc/postfix-$hostname/sasl",0755,true);
			shell_exec("/bin/ln -sf /etc/postfix/sasl/smtpd.conf /etc/postfix-$hostname/sasl/smtpd.conf");
		
		}
		
		$f[]="/etc/postfix-$hostname/transport.banned";

        foreach ($f as $num=>$path){
			if(!is_file("$path.db")){
				if(!is_file($path)){ @touch($path);}
				shell_exec("{$GLOBALS["postmap"]} hash:$path >/dev/null 2>&1");
			}
		
		}
		

		$mandatories[]="/etc/postfix-$hostname/sasl_passwd";
		$mandatories[]="/etc/postfix-$hostname/sasl_relayhost";
		
		foreach ($mandatories as $filename){
			if(!is_file($filename)){
				@file_put_contents($filename,"\n");
			}
		}
		

		if(!is_file("/etc/postfix-$hostname/postfix-script")){
			if(is_file("/etc/postfix/postfix-script")){
				@copy("/etc/postfix/postfix-script","/etc/postfix-$hostname/postfix-script");
				@chmod("/etc/postfix-$hostname/postfix-script",0755);
			}
		}
		
	$unix=new unix();
	$postfix=$unix->find_program("postfix");
	
	$resultsPerms=array();
	if(!isset($GLOBALS["Perms$hostname"])){
		$GLOBALS["Perms$hostname"]=true;
		echo "Starting......: ".date("H:i:s")." Postfix \"$hostname\" ask to $postfix to set permissions...\n";
		exec("$postfix -c /etc/postfix-$hostname set-permissions 2>&1",$resultsPerms);
        foreach ($resultsPerms as $line){
			if(preg_match("#unused parameter:#", $line)){continue;}
			echo "Starting......: ".date("H:i:s")." Postfix $hostname $line\n";
		}
	}

}	

	

	function IsPcre(){
	
		exec("{$GLOBALS["postconf"]} -m 2>&1",$results);
        foreach ($results as $line){
			$line=trim($line);
			if(preg_match("#pcre#", $line)){
				if($GLOBALS["VERBOSE"]){echo "PCRE COMPATIBLE!\n";}
				return true;
			}
		}
	
		
		return false;
	}


private function DOMAINS_THROTTLE_SMTP_CONNECTION_CACHE_DESTINATIONS($uuid){	
	$array=unserialize(base64_decode($this->GET_BIGDATA("domain_throttle_daemons_list")));
	$caches=$array[$uuid]["smtp-instance-cache-destinations"];
	if(count($caches)==0){return null;}
    foreach ($caches as $domain=>$none){
		$f[]="$domain\tOK";
		
	}
	
	@file_put_contents("/etc/postfix-$this->myhostname/{$uuid}_CONNECTION_CACHE_DESTINATIONS", implode("\n", $f));
	shell_exec("{$GLOBALS["postmap"]} hash:/etc/postfix-$this->myhostname/{$uuid}_CONNECTION_CACHE_DESTINATIONS >/dev/null 2>&1");
	return "smtp_connection_cache_destinations=hash:/etc/postfix-$this->myhostname/{$uuid}_CONNECTION_CACHE_DESTINATIONS";
}
	
private function DOMAINS_THROTTLE(){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: START\n";}
	$array=unserialize(base64_decode($this->GET_BIGDATA("domain_throttle_daemons_list")));

	$f=explode("\n",@file_get_contents("/etc/postfix-$this->myhostname/main.cf"));
	foreach ( $f as $index=>$line ){
		if(preg_match("#^[0-9]+_destination#",$line)){continue;}
		if(preg_match("#^[0-9]+_delivery_#",$line)){continue;}
		if(preg_match("#^[0-9]+_initial_#",$line)){continue;}
		$new[]=$line;
	}
	@file_put_contents("/etc/postfix-$this->myhostname/main.cf",@implode("\n",$new));
	unset($new);	
	
	
	if(!is_array($array)){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: NOT AN ARRAY\n";}
		return null;}

    foreach ($array as $uuid=>$conf){
		if($conf["ENABLED"]<>1){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: $uuid DISABLED\n";}
			continue;
		}
		if(count($conf["DOMAINS"])==0){
			if($GLOBALS["VERBOSE"]){echo __FUNCTION__.":: $uuid NO TARGETED DOMAINS\n";}
			continue;
		}
		$maps=array();
		if($conf["transport_destination_concurrency_failed_cohort_limit"]==null){$conf["transport_destination_concurrency_failed_cohort_limit"]=1;}
		if($conf["transport_delivery_slot_loan"]==null){$conf["transport_delivery_slot_loan"]=3;}
		if($conf["transport_delivery_slot_discount"]==null){$conf["transport_delivery_slot_discount"]=50;}
		if($conf["transport_delivery_slot_cost"]==null){$conf["transport_delivery_slot_cost"]=5;}
		if($conf["transport_extra_recipient_limit"]==null){$conf["transport_extra_recipient_limit"]=1000;}
		if($conf["transport_initial_destination_concurrency"]==null){$conf["transport_initial_destination_concurrency"]=5;}
		if($conf["transport_destination_recipient_limit"]==null){$conf["transport_destination_recipient_limit"]=50;}		
		if($conf["transport_destination_concurrency_limit"]==null){$conf["transport_destination_concurrency_limit"]=20;}
		if($conf["transport_destination_rate_delay"]==null){$conf["transport_destination_rate_delay"]="0s";}
		if($conf["transport_destination_concurrency_positive_feedback"]==null){$conf["transport_destination_concurrency_positive_feedback"]="1/5";}
		if($conf["transport_destination_concurrency_negative_feedback"]==null){$conf["transport_destination_concurrency_negative_feedback"]="1/5";}		
		if(!is_numeric($conf["default_process_limit"])){$conf["default_process_limit"]=100;}
		$moinso[]="{$uuid}_destination_concurrency_failed_cohort_limit={$conf["transport_destination_concurrency_failed_cohort_limit"]}";
		$moinso[]="{$uuid}_delivery_slot_loan={$conf["transport_delivery_slot_loan"]}";
		$moinso[]="{$uuid}_delivery_slot_discount={$conf["transport_delivery_slot_discount"]}";
		$moinso[]="{$uuid}_delivery_slot_cost={$conf["transport_delivery_slot_cost"]}";
		$moinso[]="{$uuid}_initial_destination_concurrency={$conf["transport_initial_destination_concurrency"]}";
		$moinso[]="{$uuid}_destination_recipient_limit={$conf["transport_destination_recipient_limit"]}";
		$moinso[]="{$uuid}_destination_concurrency_limit={$conf["transport_destination_concurrency_limit"]}";
		$moinso[]="{$uuid}_destination_rate_delay={$conf["transport_destination_rate_delay"]}";
		$moinso[]="{$uuid}_extra_recipient_limit={$conf["transport_extra_recipient_limit"]}";
		$moinso[]="{$uuid}_destination_concurrency_positive_feedback={$conf["transport_destination_concurrency_positive_feedback"]}";
		$moinso[]="{$uuid}_destination_concurrency_negative_feedback={$conf["transport_destination_concurrency_negative_feedback"]}";
		
		
		$moinsoMasterText=null;
		if(is_numeric($conf["smtp_connection_cache_on_demand"])){
			if($conf["smtp_connection_cache_on_demand"]==0){
				$moinsoMaster[]="smtp_connection_cache_on_demand=no";
			}else{
				$moinsoMaster[]="smtp_connection_cache_on_demand=yes";
				$moinsoMaster[]="smtp_connection_cache_time_limit={$conf["smtp_connection_cache_time_limit"]}";
				$moinsoMaster[]="smtp_connection_reuse_time_limit={$conf["smtp_connection_reuse_time_limit"]}";
				$cache_destinations=$this->DOMAINS_THROTTLE_SMTP_CONNECTION_CACHE_DESTINATIONS($uuid);
				if($cache_destinations<>null){$moinsoMaster[]=$cache_destinations;}
			}
			
		}else{
			if($GLOBALS["VERBOSE"]){echo "DOMAINS_THROTTLE:: smtp_connection_cache_on_demand \"{$conf["smtp_connection_cache_on_demand"]}\" is not a numeric\n";}
		}
		
		if($GLOBALS["VERBOSE"]){echo "DOMAINS_THROTTLE:: smtp_connection_cache_on_demand \"". count($moinsoMaster)." value(s)";}
		if(count($moinsoMaster)>0){$moinsoMasterText=" -o ".@implode(" -o ", $moinsoMaster);}
		$instances[]="\n# THROTTLE {$conf["INSTANCE_NAME"]}\n$uuid\tunix\t-\t-\tn\t-\t{$conf["default_process_limit"]}\tsmtp$moinsoMasterText";
        foreach ($conf["DOMAINS"] as $domain=>$null){$maps[$domain]="$uuid:";}
        foreach ($maps as $a=>$b){$maps_final[]="$a\t$b";}
	}
	
	
	
	if(is_array($moinso)){
        foreach ($moinso as $num=>$cmd){
			shell_exec("{$GLOBALS["postconf"]} -c \"/etc/postfix-$this->myhostname\" -e \"$cmd\"");
		}
	}
	
	if(!is_array($instances)){return null;}

	return @implode("\n",$instances)."\n";
	
	
}

private function DOMAINS_ADVS(){
	$sql="SELECT * FROM postfix_smtp_advrt WHERE hostname='$this->myhostname' AND enabled=1";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$maps_final[]="{$ligne["domainname"]}\tartica-adv";
		
	}
	@file_put_contents("/etc/postfix-$this->myhostname/transport.advancedtr",@implode("\n",$maps_final)."\n");
}
	
	private function virtual_alias_maps_mailling_ldap(){
		$ldap=new clladp();
		$conf[]="#Mailling list configuration to Open LDAP --------------------------------------------------------------------";
		$conf[]="server_host = $ldap->ldap_host";
		$conf[]="server_port = $ldap->ldap_port";
		$conf[]="bind = yes";
		$conf[]="bind_dn = cn=$ldap->ldap_admin,$ldap->suffix";
		$conf[]="bind_pw = $ldap->ldap_password";
		$conf[]="timeout = 10";
		if($this->ou<>null){
			$conf[]="search_base = cn=aliases-mailing,ou=$this->ou,dc=organizations,$ldap->suffix";
		}else{
			$conf[]="search_base = dc=organizations,$ldap->suffix";
		}
		$conf[]="query_filter = (&(objectclass=MailingAliasesTable)(cn=%s))";
		$conf[]="result_attribute = MailingListAddress";
		$conf[]="version = 3";
		$conf[]= "#-------------------------------------------------------------------------------------------\n";
		@file_put_contents("/etc/postfix-$this->myhostname/mailinglist.ldap.cf", @implode("\n", $conf));
		
	}	
	
	
	function PostfixMainCfDefaultInstance(){
        $unix=new unix();
        $postmulti=$unix->POSTCONF_MULTI_BIN();
        $unix->POSTCONF_SET("queue_directory","/var/spool/postfix");
        $unix->POSTCONF_SET("data_directory","/var/lib/postfix");
        $unix->POSTCONF_SET("daemon_directory",$this->LOCATE_POSTFIX_DAEMON_DIRECTORY());
        $unix->POSTCONF_SET("mail_owner","postfix");
		$unix->POSTCONF_SET("myorigin","\$myhostname");
        $unix->POSTCONF_SET("inet_interfaces","127.0.0.1");
        $unix->POSTCONF_SET("local_recipient_maps");
        $unix->POSTCONF_SET("mynetworks","127.0.0.0/8");
        $unix->POSTCONF_SET("smtpd_banner","\$myhostname ESMTP \$mail_name");
		$unix->POSTCONF_SET("multi_instance_wrapper","$postmulti -p --");
		$unix->POSTCONF_SET("multi_instance_wrapper","\${command_directory}/postmulti -p --");
		$unix->POSTCONF_SET("multi_instance_enable","yes");
        $unix->POSTCONF_SET("shlib_directory","/usr/local/lib/postfix");

		if(!is_file("/usr/share/man/man1/postmulti.1")){@file_put_contents("/usr/share/man/man1/postmulti.1","#");}
		if(!is_file("/usr/share/man/man5/postfix-wrapper.5")){@mkdir("/usr/share/man/man5");@file_put_contents("/usr/share/man/man5/postfix-wrapper.5","#");}
		
		
		echo "Starting......: ".date("H:i:s")." Postfix default main.cf done\n";
		}
		
		public function buidLdapDB($hostname,$dbindex,$array){
			if($hostname=="master"){
				$main_path="/etc/postfix";
			}else{
				$main_path="/etc/postfix-$hostname";
			}
			$database_type=trim($array["database_type"]);
			$server_host=trim($array["server_host"]);
			$search_base=trim($array["search_base"]);
			$bind_dn=trim($array["bind_dn"]);
			$bind_password=trim($array["bind_password"]);
			$query_filter=trim($array["query_filter"]);
			$scope=trim($array["scope"]);
			$result_attribute=trim($array["result_attribute"]);
			$leaf_result_attribute=trim($array["leaf_result_attribute"]);
			$special_result_attribute=trim($array["special_result_attribute"]);
			$database_type=trim($array["database_type"]);
			
			if($server_host<>null)     		   {$f[]="server_host = $server_host";}
			if($search_base<>null)     		   {$f[]="search_base = $search_base";}
			if($bind_dn<>null)         		   {$f[]="bind_dn = $bind_dn";}
			if($bind_password<>null)  		   {$f[]="bind_pw = $bind_password";}
			if($query_filter<>null)    		   {$f[]="query_filter = $query_filter";}
			if($scope<>null) 		   		   {$f[]="scope = $scope";}
			if($result_attribute<>null)		   {$f[]="result_attribute = $result_attribute";}
			if($special_result_attribute<>null){$f[]="special_result_attribute = $special_result_attribute";}
			if($leaf_result_attribute<>null)   {$f[]="leaf_result_attribute = $leaf_result_attribute";}
			
			$targeted_file="$main_path/{$database_type}_ldap.$dbindex.cf";
			@file_put_contents($targeted_file,@implode("\n",$f));
			if(!is_file($targeted_file)){
				if($GLOBALS["DEBUG"]){echo __CLASS__."/".__FUNCTION__."::LDAP:: $targeted_file no such file\n";}
			}else{
				if($GLOBALS["DEBUG"]){echo __CLASS__."/".__FUNCTION__."::LDAP:: $targeted_file exists\n";}
			}
			return $targeted_file;			        
			
		}
		
		
		public function buidLdapDBDomains($array){
			$server_host=trim($array["server_host"]);
			$search_base=trim($array["search_base"]);
			$bind_dn=trim($array["bind_dn"]);
			$bind_password=trim($array["bind_password"]);
			$md5=md5("$server_host$search_base");
			if($GLOBALS["REMOTE_SMTP_LDAPDB_SERVERS"][$md5]==true){
				if($GLOBALS["DEBUG"]){echo __CLASS__."/".__FUNCTION__."::LDAP:: $server_host $search_base already parsed\n";}
				return;
			}
			$con=@ldap_connect($server_host, 389) ;
			if(!$con){
				if($GLOBALS["DEBUG"]){echo __CLASS__."/".__FUNCTION__."::LDAP:: $server_host $search_base failed to connect\n";}
				return;
			}
			
			ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3); // on passe le LDAP en version 3, necessaire pour travailler avec le AD
			ldap_set_option($con, LDAP_OPT_REFERRALS, 0); 		 
			$ldapbind=@ldap_bind($con,$bind_dn,$bind_password);
			$filter="(&(objectClass=person))";
			$attr=array("ProxyAddresses","mail");
			$sr =@ldap_search($con,$search_base,$filter,$attr);
			if(!$sr){
				if($GLOBALS["DEBUG"]){echo __CLASS__."/".__FUNCTION__."::LDAP:: $server_host $search_base failed to search\n";}
				return;	
			}
			
			$hash=ldap_get_entries($con,$sr);
			if($GLOBALS["DEBUG"]){echo __CLASS__."/".__FUNCTION__."::LDAP:: Parsing {$hash["count"]} entries\n";}	
			
			for($i=0;$i<$hash["count"];$i++){
				for($z=0;$z<$hash[$i]["proxyaddresses"]["count"];$z++){
					$mail=$hash[$i]["proxyaddresses"][$z];
					if(preg_match("#.+?@(.+)#",$mail,$re)){
						$GLOBALS["REMOTE_SMTP_LDAPDB_ROUTING"][$re[1]]=$server_host;
					} 
				}
				
			for($z=0;$z<$hash[$i]["mail"]["count"];$z++){
					$mail=$hash[$i]["mail"][$z];
					if(preg_match("#.+?@(.+)#",$mail,$re)){
						$GLOBALS["REMOTE_SMTP_LDAPDB_ROUTING"][$re[1]]=$server_host;
					} 
				}
								
			}
	}
	
public function MULTINETS(){
	if(isset($GLOBALS["MEM_MULTINETS"])){return $GLOBALS["MEM_MULTINETS"];}
	$q=new mysql();
	$sql="SELECT * FROM `postfix_params` WHERE `key` = 'myhostname'";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "ERROR:$q->mysql_error\n";}
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if(isset($GLOBALS["MEM_MULTINETS"][$this->ip_addr])){continue;}	
			if($ligne["ip_address"]==$this->ip_addr){continue;}
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" Network:{$ligne["ip_address"]}\n";}
			$GLOBALS["MEM_MULTINETS"][$ligne["ip_address"]]=$ligne["ip_address"];
		}	
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" Network:MULTINETS = ". count($GLOBALS["MEM_MULTINETS"])." items\n";}
	return $GLOBALS["MEM_MULTINETS"];
}
	
	public function NetWorksBubble(){
		
		$q=new mysql();
		$ipaddr=array();
		$sql="SELECT * FROM `postfix_params` WHERE `key` = 'myhostname'";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "ERROR:$q->mysql_error\n";}
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if(isset($ipaddr[$this->ip_addr])){continue;}	
			if($ligne["ip_address"]==$this->ip_addr){continue;}
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" NetWorksBubble Trust:{$ligne["ip_address"]}\n";
			$ipaddr[$ligne["ip_address"]]=$ligne["ip_address"];
		}
		
		
		$sql="SELECT ipaddr FROM postfix_whitelist_con";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			if($ligne["ipaddr"]==$this->ip_addr){continue;}
			if(isset($ipaddr[$this->ip_addr])){continue;}
			if(isset($ipaddr[$ligne["ipaddr"]])){continue;}
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" NetWorksBubble Trust:{$ligne["ipaddr"]}\n";
			$ipaddr[$ligne["ipaddr"]]=$ligne["ipaddr"];
		}		
		$GLOBALS["NetWorksBubble"]=$ipaddr;
		return $ipaddr;
		
		
	}
	
	
private function certificate_openssl_conf(){
		$datas=$this->GET_BIGDATA("certificate_smtp_parameters");
		$country_code="US";
		$contryname="Delaware";
		$locality="Wilmington";
		$organizationalUnitName=$this->ou;
		$organizationName=$this->ou;
		$emailAddress="root@$this->myhostname";
		if(preg_match("#(.+?)_(.+?)$#",$datas["countryName"],$re)){
			$contryname=$re[1];
			$country_code=$re[2];
		}
		if($datas["localityName"]<>null){$locality=$datas["localityName"];}
		if($datas["organizationalUnitName"]<>null){$organizationalUnitName=$datas["organizationalUnitName"];}
		if($datas["emailAddress"]<>null){$emailAddress=$datas["emailAddress"];}
		if($datas["organizationName"]<>null){$organizationName=$datas["organizationName"];}
	
		
	
		$conf[]="[ca]";
		$conf[]="default_ca=default_db";
		$conf[]="unique_subject=no";
		$conf[]="";
		$conf[]="[default_db]";
		$conf[]="dir=.";
		$conf[]="certs=.";
		$conf[]="new_certs_dir=/etc/postfix-$this->myhostname/ssl/new";
		$conf[]="database= /etc/postfix-$this->myhostname/ssl/ca.index";
		$conf[]="serial = /etc/postfix-$this->myhostname/ssl/ca.serial";
		$conf[]="RANDFILE=.rnd";
		$conf[]="certificate=/etc/postfix-$this->myhostname/ssl/key.pem";
		$conf[]="private_key=/etc/postfix-$this->myhostname/ssl/ca.key";
		$conf[]="default_days= 730";
		$conf[]="default_crl_days=30";
		$conf[]="default_md=md5";
		$conf[]="preserve=no";
		$conf[]="name_opt=ca_default";
		$conf[]="cert_opt=ca_default";
		$conf[]="unique_subject=no";
		$conf[]="policy=policy_match";
		$conf[]="";
		$conf[]="[server_policy]";
		$conf[]="countryName=supplied";
		$conf[]="stateOrProvinceName=supplied";
		$conf[]="localityName=supplied";
		$conf[]="organizationName=supplied";
		$conf[]="organizationalUnitName=supplied";
		$conf[]="commonName=supplied";
		$conf[]="emailAddress=supplied";
		$conf[]="";
		$conf[]="[server_cert]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="extendedKeyUsage=serverAuth,clientAuth,msSGC,nsSGC";
		$conf[]="basicConstraints= critical,CA:false";
		$conf[]="";
		$conf[]="[user_policy]";
		$conf[]="commonName=supplied";
		$conf[]="emailAddress=supplied";
		$conf[]="";
		$conf[]="[user_cert]";
		$conf[]="subjectAltName=email:copy";
		$conf[]="basicConstraints= critical,CA:false";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="extendedKeyUsage=clientAuth,emailProtection";
		$conf[]="";
		$conf[]="[req]";
		$conf[]="default_bits=1024";
		$conf[]="default_keyfile=ca.key";
		$conf[]="distinguished_name=default_ca";
		$conf[]="x509_extensions=extensions";
		$conf[]="string_mask=nombstr";
		$conf[]="req_extensions=req_extensions";
		$conf[]="input_password=secret";
		$conf[]="output_password=secret";
		$conf[]="";
		$conf[]="[default_ca]";
		$conf[]="countryName=Country Code";
		$conf[]="countryName_value=$country_code";
		$conf[]="countryName_min=2";
		$conf[]="countryName_max=2";
		$conf[]="stateOrProvinceName=State Name";
		$conf[]="stateOrProvinceName_value=$contryname";
		$conf[]="localityName=Locality Name";
		$conf[]="localityName_value=$locality";
		$conf[]="organizationName=Organization Name";
		$conf[]="organizationName_value=$organizationName";
		$conf[]="organizationalUnitName=Organizational Unit Name";
		$conf[]="organizationalUnitName_value=$organizationalUnitName";
		$conf[]="commonName=Common Name";
		$conf[]="commonName_value=$this->myhostname";
		$conf[]="commonName_max=128";
		$conf[]="emailAddress=Email Address";
		$conf[]="emailAddress_value=$emailAddress";
		$conf[]="emailAddress_max=".strlen($emailAddress);
		$conf[]="unique_subject=no";
		$conf[]="";
		$conf[]="[extensions]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always";
		$conf[]="basicConstraints=critical,CA:false";
		$conf[]="";
		$conf[]="[req_extensions]";
		$conf[]="nsCertType=objsign,email,server";
		$conf[]="";
		$conf[]="[CA_default]";
		$conf[]="policy=policy_match";
		$conf[]="";
		$conf[]="[policy_match]";
		$conf[]="countryName=match";
		$conf[]="stateOrProvinceName=match";
		$conf[]="organizationName=match";
		$conf[]="organizationalUnitName=optional";
		$conf[]="commonName=match";
		$conf[]="emailAddress=optional";
		$conf[]="";
		$conf[]="[policy_anything]";
		$conf[]="countryName=optional";
		$conf[]="stateOrProvinceName=optional";
		$conf[]="localityName=optional";
		$conf[]="organizationName=optional";
		$conf[]="organizationalUnitName=optional";
		$conf[]="commonName=optional";
		$conf[]="emailAddress=optional";
		$conf[]="";
		$conf[]="[v3_ca]";
		$conf[]="subjectKeyIdentifier=hash";
		$conf[]="authorityKeyIdentifier=keyid:always,issuer:always";
		$conf[]="basicConstraints=critical,CA:false";
		@mkdir("/etc/postfix-$this->myhostname/ssl",0666,true);
		file_put_contents("/etc/postfix-$this->myhostname/ssl/openssl.conf",@implode("\n",$conf));		
	}

	public function certificate_generate(){
		 echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" building TLS certificate\n";
		 $this->certificate_openssl_conf();
		 $unix=new unix();
		 $openssl=$unix->find_program("openssl");
		 $config="/etc/postfix-$this->myhostname/ssl/openssl.conf";
		 $ssl_path="/etc/postfix-$this->myhostname/ssl";
		 system("$openssl genrsa -out /etc/postfix-$this->myhostname/ssl/server.key 1024");
	     system("$openssl req -new -key $ssl_path/server.key -batch -config $config -out $ssl_path/server.csr");
	     system("$openssl genrsa -out $ssl_path/ca.key 1024 -batch -config $config");
	     system("$openssl req -new -x509 -days 730 -key $ssl_path/ca.key -batch -config $config -out $ssl_path/ca.csr");
	     system("$openssl x509 -extfile $config -x509toreq -days 730 -in $ssl_path/ca.csr -signkey $ssl_path/ca.key -out $ssl_path/ca.req");
	     system("$openssl x509 -extfile $config -req -days 730 -in $ssl_path/ca.req -signkey $ssl_path/ca.key -out $ssl_path/ca.crt");

	}
}

function PostFixMultiVerifyRights(){
	if($_REQUEST["hostname"]=="master"){$_REQUEST["ou"]="master";}
	if(!isset($_REQUEST["ou"])){if(isset($_SESSION["ou"])){$_REQUEST["ou"]=$_SESSION["ou"];}}
	
	if($_REQUEST["ou"]==null){unset($_REQUEST["ou"]);}
	
	if(!isset($_REQUEST["ou"])){
		if($_REQUEST["hostname"]<>"master"){
			if($_REQUEST["hostname"]<>null){
				$main=new maincf_multi($_REQUEST["hostname"]);$_REQUEST["ou"]=$main->ou;$_POST["ou"]=$main->ou;$_GET["ou"]=$main->ou;
			}
		}
	}

	if(!isset($_REQUEST["ou"])){echo "NO OU\n";return false;}
	$ou=$_REQUEST["ou"];
	if(is_base64_encoded($ou)){$ou=base64_decode($ou);}
	if(isset($_SESSION["POSTFIX_SERVERS"][$_REQUEST["hostname"]])){return true;}
	
	$usersmenus=new usersMenus();
	if($usersmenus->AsPostfixAdministrator){return true;}
	
	if($usersmenus->AsOrgPostfixAdministrator){
		if($_SESSION["ou"]<>$ou){return false;}
		return TRUE;
	}
	if($_SESSION["uid"]<>-100){if($_SESSION["ou"]<>$ou){return false;}}
	return true;
	}

?>