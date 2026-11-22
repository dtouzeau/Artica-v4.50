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
    private $instanceid=0;
    private $params=array();
    private $params_loaded=false;

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
            $this->instanceid=intval($instanceid);

			$this->AS_ROOT=$GLOBALS["AS_ROOT"];
			// postfix.db is daemon-owned: load this instance's postfix_params in ONE
			// REST call and serve GET*/SET*/TokenExists from the in-memory cache so the
			// 184+ GET sites stay fast (no per-key round-trip, esp. on remote consoles).
			$this->_load_params();
			$this->EnableipV6=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableipV6"));
			if(!is_numeric($this->EnableipV6)){$this->EnableipV6=0;}
	}

	// _load_params bulk-loads (and caches) the instance's postfix_params via the daemon.
	private function _load_params(){
		$sock=new sockets();
		$json=json_decode($sock->REST_API_POSTFIX("/postfix/params/all/{$this->instanceid}"),true);
		if(isset($json["params"]) && is_array($json["params"])){
			$this->params=$json["params"];
		}else{
			$this->params=array();
		}
		$this->params_loaded=true;
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
        if(!$this->params_loaded){$this->_load_params();}
        return isset($this->params[$key]);
    }

	public function GET_BIGDATA($key){
        if(!$this->params_loaded){$this->_load_params();}
        return isset($this->params[$key])?$this->params[$key]:"";
	}
    public function SET_INFO($key,$value){
        return  $this->SET_BIGDATA($key,$value);
    }

	public function SET($key,$value){
        return  $this->SET_BIGDATA($key,$value);
    }

public function SET_BIGDATA($key,$value){
		// Daemon owns postfix.db: upsert ValueTEXT via REST, then refresh the cache.
		$sock=new sockets();
		$sock->REST_API_POSTFIX_POST_JSON("/postfix/params/set/$this->instanceid",array("key"=>$key,"value"=>$value));
		$this->params[$key]=$value;
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
		// Writes the legacy `value` column; an empty value deletes the row (daemon-side).
		$sock=new sockets();
		$sock->REST_API_POSTFIX_POST_JSON("/postfix/params/set-value/{$this->instanceid}",array("key"=>$key,"value"=>$value));
		if(trim($value)==null){unset($this->params[$key]);}
		return true;
	}

	public function DELETE_KEY($key){
		$sock=new sockets();
		$sock->REST_API_POSTFIX_POST_JSON("/postfix/params/delete/{$this->instanceid}",array("key"=>$key));
		unset($this->params[$key]);
	}

	
	public function mailling_list_mysql($hostname=null){
		if($hostname==null){$hostname=$this->myhostname;}
		$sock=new sockets();
		$json=json_decode($sock->REST_API_POSTFIX("/postfix/routing/mailinglist/{$this->instanceid}?hostname=".urlencode($hostname)),true);
		$final=(isset($json["lines"]) && is_array($json["lines"]))?$json["lines"]:array();
		if(count($final)>0){return $final;}
		return array();
	}
	
	
	public function bann_destination_domains(){

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
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." Postfix \"$this->myhostname\" IPV6: $ip {$q->mysql_error}\n";
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
		$array_filters=unserialize(base64_decode($this->GET_BIGDATA("PluginsEnabled")));

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