<?php
include_once(dirname(__FILE__)."/class.user.inc");

class ejabberd{
	
	var $hostname=null;
	var $enabled=0;
	var $Config=array();
	var $uid=null;
	
	
	function __construct($hostname=null){
		if($hostname<>null){
			$this->hostname=$hostname;
			$this->hostname_load();
			
		}
		
	}
	
	private function hostname_load(){
		$q=new mysql();
		$sql="";
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT *  FROM ejabberd WHERE hostname='$this->hostname'","artica_backup"));		
		$this->enabled=$ligne["enabled"];
		$this->Config=unserialize(base64_decode($ligne["Config"]));
		$this->uid=$ligne["uid"];
		if(!is_numeric($this->enabled)){$this->enabled=0;}
	}
	
	
	public function SaveHostname(){
		$q=new mysql();
		$Config=base64_encode(serialize($this->Config));
		$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT hostname  FROM ejabberd WHERE hostname='$this->hostname'","artica_backup"));
		if($ligne["hostname"]==null){
			$sql="INSERT IGNORE INTO ejabberd (hostname,enabled,Config) VALUES ('$this->hostname','1','$Config')";
		}else{
			$sql="UPDATE ejabberd SET enabled=$this->enabled,Config='$Config',uid='$this->uid' WHERE hostname='$this->hostname'";
		}
		
		writelogs($sql,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return false;}
		$sock=new sockets();
		$sock->getFrameWork("services.php?restart-instant-messaging=yes");		
		return true;
		
	}
	
	public function Delete(){
		$q=new mysql();
		$sql="DELETE FROM ejabberd WHERE hostname='$this->hostname'";
		writelogs($sql,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return false;}
		$sock=new sockets();
		$sock->getFrameWork("services.php?restart-instant-messaging=yes");
		return true;	
	}
	
	
	function BuildMasterConf(){
		$ldap=new clladp();
		$servers=$this->servers_list();
		$acls_admins=$this->acls_admins();
		$host_config=$this->host_config();
		$globalmodules=$this->globalmodules();
		$listenservices=$this->listenservices();
		$f[]="{loglevel, 4}.";
		$f[]="{hosts, [$servers]}.";
		$f[]=$acls_admins."\n";
		$f[]=$host_config."\n";
		$f[]=$listenservices."\n";
		$f[]=$globalmodules."\n";
		return @implode("\n", $f);
		
	}
	
	private function servers_list(){
		$users=new usersMenus();
		$myhostname=$users->hostname;
		$q=new mysql();
		$f=array();
		$f[]="\"$myhostname\"";
		$results=$q->QUERY_SQL("SELECT hostname FROM ejabberd WHERE enabled=1","artica_backup");
		while ($ligne = mysqli_fetch_assoc($results)) {
			
			$f[]="\"{$ligne["hostname"]}\"";
		}
		
		
		
		if(count($f)==0){return null;}
		return @implode(", ",$f);		
		
	}
	
	private function acls_admins(){
		$users=new usersMenus();
		$myhostname=$users->hostname;
		$ldap=new clladp();
		$f[]="{acl, admin, {user, \"". strtolower($ldap->ldap_admin)."\", \"$myhostname\"}}.";
		return @implode("\n",$f);
		
	}
	
	private function host_config(){
		$ldap=new clladp();
		$q=new mysql();
		$f=array();
		$users=new usersMenus();
		$users=new usersMenus();
		$sock=new sockets();
		$PYMSNT=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PYMSNT")));
		$PYICQT=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PYICQT")));		
		if(!isset($PYMSNT["MSN_GATEWAY"])){$PYMSNT["MSN_GATEWAY"]=null;}
		if(!isset($PYICQT["ICQ_GATEWAY"])){$PYICQT["ICQ_GATEWAY"]=null;}	
		$ejabberdInsideZarafa=$sock->GET_INFO("ejabberdInsideZarafa");
		if(!is_numeric($ejabberdInsideZarafa)){$ejabberdInsideZarafa=0;}
		$auth_method="[ldap]";
		
		if($ejabberdInsideZarafa==1){
			$auth_method="[external]";
			$external_bin="\t{extauth_program, \"/usr/share/artica-postfix/jabberauth.php\"},\n\t{mod_http_bind, []},";
		}
		$myhostname=$users->hostname;
			
		$f[]="{host_config, \"$myhostname\", [";
		if($ejabberdInsideZarafa==1){$f[]=$external_bin;}
		$f[]="\t{auth_method,  $auth_method}";
		if($ejabberdInsideZarafa==0){
       	 	$f[]="\t,{ldap_servers,  [\"$ldap->ldap_host\"]},";
        	$f[]="\t{ldap_uids, [{\"uid\", \"%u\"}]},";
        	$f[]="\t{ldap_filter, \"(mail=*)\"},";
        	$f[]="\t{ldap_uidattr, \"uid\"},";
        	$f[]="\t{ldap_base, \"dc=organizations,$ldap->suffix\"},";
        	$f[]="\t{ldap_rootdn,   \"cn=$ldap->ldap_admin,$ldap->suffix\"},";
        	$f[]="\t{ldap_password, \"$ldap->ldap_password\"}";
		}
		$f[]="]";
        $f[]="}.\n";

            
        if($PYMSNT["MSN_GATEWAY"]<>null){
        	$f[]="{host_config, \"{$PYMSNT["MSN_GATEWAY"]}\", [";
			$f[]="\t{auth_method,   $auth_method },";
	        $f[]="\t{ldap_servers,  [\"$ldap->ldap_host\"]},";
	         // $f[]="\t{ldap_uids, [{\"uid\", \"%u\"}]},";
	        //$f[]="\t{ldap_filter, \"(mail=*)\"},";
	         $f[]="\t{ldap_uidattr, \"uid\"},";
	         $f[]="\t{ldap_base, \"dc=organizations,$ldap->suffix\"},";
	         $f[]="\t{ldap_rootdn,   \"cn=$ldap->ldap_admin,$ldap->suffix\"},";
	         $f[]="\t{ldap_password, \"$ldap->ldap_password\"}";
	         $f[]="]";
			
            $f[]="}.\n";
        	
        }
        
	    if($PYMSNT["ICQ_GATEWAY"]<>null){
        	$f[]="{host_config, \"{$PYMSNT["ICQ_GATEWAY"]}\", [";
        	if($ejabberdInsideZarafa==1){$f[]=$external_bin;}
			$f[]="\t{auth_method,   $auth_method },";
            $f[]="\t{ldap_servers,  [\"$ldap->ldap_host\"]},";
           // $f[]="\t{ldap_uids, [{\"uid\", \"%u\"}]},";
           	//$f[]="\t{ldap_filter, \"(mail=*)\"},";
            $f[]="\t{ldap_uidattr, \"uid\"},";
            $f[]="\t{ldap_base, \"dc=organizations,$ldap->suffix\"},";
            $f[]="\t{ldap_rootdn,   \"cn=$ldap->ldap_admin,$ldap->suffix\"},";
            $f[]="\t{ldap_password, \"$ldap->ldap_password\"}";
           	$f[]="]";
            $f[]="}.\n";
		}        
		
		
		$results=$q->QUERY_SQL("SELECT hostname,uid FROM ejabberd WHERE enabled=1","artica_backup");
		while ($ligne = mysqli_fetch_assoc($results)) {
			$oufind=false;
			if($ligne["uid"]<>null){
				$member=new user($ligne["uid"]);
				$ou=$member->ou;
				$dn="ou=users,ou=$ou,dc=organizations,$ldap->suffix";
				$oufind=true;
			}else{
				$dn="dc=organizations,$ldap->suffix";
			}
			
			if(!$oufind){
				$ou=$ldap->ou_by_smtp_domain($ligne["hostname"]);
				if($ou<>null){$dn="ou=users,ou=$ou,dc=organizations,$ldap->suffix";}
			}
			
			
			$f[]="";
			$f[]="{host_config, \"{$ligne["hostname"]}\", [";
			if($ejabberdInsideZarafa==1){$f[]=$external_bin;}
			$f[]="\t{auth_method,   $auth_method }";
			if($ejabberdInsideZarafa==0){
	            $f[]="\t,{ldap_servers,  [\"$ldap->ldap_host\"]},";
	            $f[]="\t{ldap_uidattr, \"uid\"},";
	           	$f[]="\t{ldap_uids, [{\"uid\", \"%u\"}]},";
	           	$f[]="\t{ldap_filter, \"(mail=*)\"},";
	            $f[]="\t{ldap_base, \"$dn\"},";
	            $f[]="\t{ldap_rootdn,   \"cn=$ldap->ldap_admin,$ldap->suffix\"},";
	            $f[]="\t{ldap_password, \"$ldap->ldap_password\"}";
			}
            if($ligne["uid"]<>null){
				$f[]="\t,{acl, admins, {user, \"{$ligne["uid"]}\", \"{$ligne["hostname"]}\"}},";
				$f[]="\t{access, configure, [{allow, admins}]}";
            }
            $f[]="]";
            $f[]="}.\n";
			
		}	
		
		
		return @implode("\n",$f);
		
	}
	
	private function listenservices(){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."()\n";}
		$users=new usersMenus();
		$sock=new sockets();
		$PYMSNT=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PYMSNT")));
		$PYICQT=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PYICQT")));		
		if(!isset($PYMSNT["MSN_GATEWAY"])){$PYMSNT["MSN_GATEWAY"]=null;}
		if(!isset($PYICQT["ICQ_GATEWAY"])){$PYICQT["ICQ_GATEWAY"]=null;}
		
		$f=array();
		$f[]="{listen,[";
		$f[]="\t{5222, ejabberd_c2s, [";
		$f[]="\t\t{access, c2s},";
		$f[]="\t\t{shaper, c2s_shaper},";
		$f[]="\t\t{max_stanza_size, 65536},";
		$f[]="\t\tstarttls, {certfile, \"/etc/ejabberd/ejabberd.pem\"}";
		$f[]="\t]},";
		
		$f[]="\t{5269, ejabberd_s2s_in, [";
		$f[]="\t\t{shaper, s2s_shaper},";
		$f[]="\t\t{max_stanza_size, 131072}";
		$f[]="\t]},";
		
		$f[]="\t{5280, ejabberd_http, [";
		$f[]="\t\thttp_bind,";
		$f[]="\t\thttp_poll,";
		$f[]="\t\tweb_admin";
		$f[]="\t]}";
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."() L.".__LINE__."\n";}
		$PYMSNT_INSTALLED=false;
		$PYICQT_INSTALLED=false;
		if($users->PYMSNT_INSTALLED){
			if($PYMSNT["MSN_GATEWAY"]<>null){
				$PYMSNT_INSTALLED=true;
				echo "Starting......: ".date("H:i:s")." ejabberd adding MSN gateway {$PYMSNT["MSN_GATEWAY"]} Internal Port:5347\n";
				$password=$PYMSNT["PASSWORD"];
				$f[]=",\t{5347, ejabberd_service, [{ip, {127, 0, 0, 1}},";
           	 	$f[]="\t\t{access, all},";
            	$f[]="\t\t{host, \"{$PYMSNT["MSN_GATEWAY"]}\", [{password, \"{$PYMSNT["PASSWORD"]}\"}]}]}";
            	$this->PyMSNt();
			}
			
		}
		if($users->PYICQT_INSTALLED){
			if($PYICQT["ICQ_GATEWAY"]<>null){
				$PYICQT_INSTALLED=true;
				echo "Starting......: ".date("H:i:s")." ejabberd adding ICQ gateway {$PYICQT["ICQ_GATEWAY"]} Internal Port:5348\n";
				$password=$PYICQT["PASSWORD"];
				$f[]=",\t{5348, ejabberd_service, [{ip, {127, 0, 0, 1}},";
           	 	$f[]="\t\t{access, all},";
            	$f[]="\t\t{host, \"{$PYICQT["ICQ_GATEWAY"]}\", [{password, \"{$PYICQT["PASSWORD"]}\"}]}]}";
            	$this->PyICQt();
			}			
			
		}
		
		
		
		if(!$PYMSNT_INSTALLED){echo "Starting......: ".date("H:i:s")." ejabberd MSN gateway not installed of false configuration...\n";}
		if(!$PYICQT_INSTALLED){echo "Starting......: ".date("H:i:s")." ejabberd ICQ gateway not installed of false configuration...\n";}
		
		$f[]="]}.";	
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."() ". count($f)." rows L.".__LINE__."\n";}
		return @implode("\n", $f);		
		
		
	}
	
	private function PyICQt(){
		//listen 1863
		$sock=new sockets();
		$PYICQT=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PYICQT")));		
		if(!isset($PYICQT["ICQ_GATEWAY"])){$PYICQT["ICQ_GATEWAY"]=null;}
		if($PYICQT["ICQ_GATEWAY"]==null){return;}
		$f[]="<pyicqt>";
		$f[]="	<!-- The JabberID of the transport. -->";
		$f[]="	<jid>{$PYICQT["ICQ_GATEWAY"]}</jid>";
		$f[]="";
		$f[]="	<!-- The component JID of the transport. Unless you're doing clustering, leave this alone -->";
		$f[]="	<!-- <compjid>icq1</compjid> -->";
		$f[]="";
		$f[]="	<!-- The IP address of the main Jabberd server -->";
		$f[]="	<mainServer>127.0.0.1</mainServer>";
		$f[]="";
		$f[]="	<!-- The JID of the main Jabber server -->";
		$f[]="	<mainServerJID>{$PYICQT["ICQ_GATEWAY"]}</mainServerJID>";
		$f[]="";
		$f[]="	<!-- The website of the Jabber service -->";
		$f[]="	<website>http://{$PYICQT["ICQ_GATEWAY"]}/</website>";
		$f[]="	";
		$f[]="	<!-- The website of the transport service -->";
		$f[]="	<!-- <transportWebsite></transportWebsite> -->";
		$f[]="	";
		$f[]="	<!-- The support room on your server -->";
		$f[]="	<!-- <supportRoom></supportRoom> -->";
		$f[]="	";
		$f[]="	<!-- The JID of a service admin or helpdesk -->";
		$f[]="	<!-- <supportJid></supportJid> -->";
		$f[]="";
		$f[]="	<!-- The TCP port to connect to the Jabber server on -->";
		$f[]="	<!-- (this is the default for Jabberd2) -->";
		$f[]="	<port>5348</port>";
		$f[]="";
		$f[]="	<!-- The TCP port that the web admin interface will answer on -->";
		$f[]="	<!-- (uncomment to enable) -->";
		$f[]="	<!-- <webport>12345</webport> -->";
		$f[]="";
		$f[]="	<!-- The authentication token to use when connecting to the Jabber server -->";
		$f[]="	<secret>{$PYICQT["PASSWORD"]}</secret>";
		$f[]="";
		$f[]="	<!-- The default language to use (for error/status messages) -->";
		$f[]="	<lang>en</lang>";
		$f[]="";
		$f[]="	<!-- Default one-byte message encoding to use -->";
		$f[]="	<encoding>iso-8859-1</encoding>";
		$f[]="";
		$f[]="	<!-- The hostname of the ICQ server you wish to connect to -->";
		$f[]="	<icqServer>login.icq.com</icqServer>";
		$f[]="";
		$f[]="	<!-- The port of the ICQ server you wish to connect to -->";
		$f[]="	<icqPort>5190</icqPort>";
		$f[]="";
		$f[]="	<!-- Enable it if you want md5 authentication -->";
		$f[]="	<usemd5auth/>";
		$f[]="";
		$f[]="	<!-- The name of Socks Proxy if connecting thru a proxy -->";
		$f[]="	<!-- <socksProxyServer>im-proxy2</socksProxyServer> -->";
		$f[]="";
		$f[]="	<!-- The Socks Proxy port to use when connecting thru a proxy -->";
		$f[]="	<!-- <socksProxyPort>1080</socksProxyPort> -->";
		$f[]="";
		$f[]="	<!-- Send greeting on login (enter text to be sent to users here) -->";
		$f[]="	<!-- <sessionGreeting>enter message here</sessionGreeting> -->";
		$f[]="	";
		$f[]="	<!-- Send message to user on transport stop/restart -->";
		$f[]="	<enableShutdownMessage/>";
		$f[]="	";
		$f[]="	<!-- Custom shutdown message -->";
		$f[]="	<!-- <customShutdownMessage></customShutdownMessage> -->";
		$f[]="";
		$f[]="	<!-- Send message on successful registration -->";
		$f[]="	<!-- <registerMessage>You have successfully registered with PyICQt</registerMessage> -->";
		$f[]="";
		$f[]="	<!-- Allow users of ICQ gateway to chat with AIM users -->";
		$f[]="	<!-- (uncomment to enable) -->";
		$f[]="	<!-- <crossChat/> -->";
		$f[]="";
		$f[]="	<!-- Disable registration with the transport -->";
		$f[]="	<!-- (uncomment to disable) -->";
		$f[]="	<!-- <disableRegister/> -->";
		$f[]="";
		$f[]="	<!-- Enable automatic invitation to reconnect on restart -->";
		$f[]="	<!-- (uncomment to enable) -->";
		$f[]="	<!-- <enableAutoInvite/> -->";
		$f[]="";
		$f[]="	<!-- Disable xhtml support (messages with fonts and colors) -->";
		$f[]="	<!-- (uncomment to disable) -->";
		$f[]="	<!-- <disableXHTML/> -->";
		$f[]="";
		$f[]="	<!-- Disable mail notifications -->";
		$f[]="	<!-- (uncomment to disable) -->";
		$f[]="	<!-- <disableMailNotifications/> -->";
		$f[]="";
		$f[]="	<!-- Disable use of default avatar if none is specified -->";
		$f[]="	<!-- (uncomment to disable) -->";
		$f[]="	<!-- <disableDefaultAvatar/> -->";
		$f[]="";
		$f[]="	<!-- Disable use of iq-based avatars (JEP-0008) -->";
		$f[]="	<!-- (uncomment to disable) -->";
		$f[]="	<!-- <disableIQAvatars/> -->";
		$f[]="";
		$f[]="	<!-- Disable use of vcard-based avatars (JEP-0153) -->";
		$f[]="	<!-- (uncomment to disable) -->";
		$f[]="	<!-- <disableVCardAvatars/> -->";
		$f[]="";
		$f[]="	<!-- Disable use of PEP-based avatars (JEP-0084) -->";
		$f[]="	<!-- (uncomment to disable) -->";
		$f[]="	<!-- NOT IMPLEMENTED YET -->";
		$f[]="	<!-- <disablePEPAvatars/> -->";
		$f[]="	";
		$f[]="	<!-- Support for x-statuses -->";
		$f[]="	<!-- <xstatusessupport/> -->";
		$f[]="	";
		$f[]="	<!-- Try detect Unicode:";
		$f[]="	    0 - never";
		$f[]="	    1 - in offline messages";
		$f[]="	    2 - and in nicknames";
		$f[]="	    Attention: this solution can be slowly on high-load servers";
		$f[]="	-->";
		$f[]="	<detectunicode>1</detectunicode>";
		$f[]="";
		$f[]="	<!-- You can choose which users you wish to have as administrators. These users can perform some tasks with Ad-Hoc commands that others cannot -->";
		$f[]="	<admins>";
		$f[]="	<!--<jid>admin@host.com</jid>-->";
		$f[]="	</admins>";
		$f[]="";
		$f[]="	<!-- You can select which event loop PyICQt will use. It's probably safe to leave this as the default -->";
		$f[]="";
		$f[]="	<!-- Use epoll for high-load Linux servers running kernel 2.6 or above -->";
		$f[]="	<!--<reactor>epoll</reactor>-->";
		$f[]="";
		$f[]="	<!-- Use kqueue for high-load FreeBSD servers -->";
		$f[]="	<!--<reactor>kqueue</reactor>-->";
		$f[]="";
		$f[]="	<!-- Use poll for high-load Unix servers -->";
		$f[]="	<!--<reactor>poll</reactor>-->";
		$f[]="";
		$f[]="	<!-- You can select which spool storage method you wish to use -->";
		$f[]="	<!-- Available methods are: -->";
		$f[]="	<!-- xmlfiles: single xml files in the spool directory in hashed dirs (default)-->";
		$f[]="	<!-- legacyaimtransport: compatible with c-based aim transport, less functionality -->";
		$f[]="	<!-- legacyjittransport: compatible with JIT transport, less functionality -->";
		$f[]="	<!-- mysql: registration information stored in a MySQL database -->";
		$f[]="	<!--<xdbDriver>xmlfiles</xdbDriver>-->";
		$f[]="";
		$f[]="	<!-- For MySQL -->";
		$f[]="	<!--<xdbDriver>mysql</xdbDriver>-->";
		$f[]="	<!--<xdbDriver_mysql>-->";
		$f[]="	<!--<username>pyicqt</username>-->";
		$f[]="	<!--<password>pyicqt</password>-->";
		$f[]="	<!--<database>pyicqt</database>-->";
		$f[]="	<!--<server>localhost</server>-->";
		$f[]="	<!--<format>encrypted</format>--> <!-- Enable encryption of passwords -->";
		$f[]="	<!--</xdbDriver_mysql>-->";
		$f[]="";
		$f[]="	<!-- For XMLFiles -->";
		$f[]="	<!--<xdbDriver>xmlfiles</xdbDriver>-->";
		$f[]="	<!--<xdbDriver_xmlfiles>-->";
		$f[]="	<!--<format>encrypted</format>--> <!-- Enable encryption of passwords -->";
		$f[]="	<!--</xdbDriver_xmlfiles>-->";
		$f[]="";
		$f[]="	<!-- Only grab avatars when a chat is initiated. -->";
		$f[]="	<!-- <avatarsOnlyOnChat/> -->";
		$f[]="";
		$f[]="	<!-- Disable all avatar functionality. Might be necessary if you -->";
		$f[]="	<!-- do not have PIL installed. -->";
		$f[]="	<disableAvatars/>";
		$f[]="";
		$f[]="	<!-- Enable web presence indicator.  This can increase ICQ spam. -->";
		$f[]="	<!-- <enableWebPresence/> -->";
		$f[]="";
		$f[]="	<!-- Disable automatic send (via im) of away message when away set. -->";
		$f[]="	<!-- Note that away messages are -in addition to- the away status -->";
		$f[]="	<!-- message. -->";
		$f[]="	<!-- <disableAwayMessage/> -->";
		$f[]="";
		$f[]="	<!-- Use Jabber.com's XCP component protocol extensions. -->";
		$f[]="	<!-- <useXCP/> -->";
		$f[]="";
		$f[]="	<!-- SASL username used to bind to Jabber server. -->";
		$f[]="	<!-- secret, above, is used for sasl password -->";
		$f[]="	<!-- <saslUsername>username</saslUsername> -->";
		$f[]="";
		$f[]="	<!-- Use external component binding. -->";
		$f[]="	<!-- This dodges the need to manually configure all jids that talk to this transport. -->";
		$f[]="	<!-- Jabberd2 requires saslUsername and useRouteWrap for this to work. -->";
		$f[]="	<!-- Wildfire as of 2.6.0 requires just this. -->";
		$f[]="	<!-- <useComponentBinding/> -->";
		$f[]="";
		$f[]="	<!-- Wrap stanzas in <route> stanza. -->";
		$f[]="	<!-- Jabberd2 requires this for useComponentBinding. -->";
		$f[]="	<!-- <useRouteWrap/> -->";
		$f[]="";
		$f[]="	<!-- JID of message archive service -->";
		$f[]="	<!-- <messageArchiveJID>datasink.example.org</messageArchiveJID> -->";
		$f[]="";
		$f[]="	<!-- If registration authentication is used, enter the method -->";
		$f[]="	<!-- Auth configs often require additional options to be specified. -->";
		$f[]="	<!-- See associated config entries per authRegister example. -->";
		$f[]="	<!-- NOTE: limited to LDAP for now -->";
		$f[]="";
		$f[]="	<!-- THIS IS UNTESTED AS OF YET -->";
		$f[]="	<!-- <authRegister>LDAP</authRegister> -->";
		$f[]="	<!-- For LDAP auth, make sure to uncomment <authRegister_LDAP> -->";
		$f[]="	<!-- and </authRegister_LDAP> and fill out the options in between. -->";
		$f[]="	<!-- <authRegister_LDAP> -->";
		$f[]="	<!-- The fqdn or ip address of the LDAP server -->";
		$f[]="	<!-- <server>ldap.example.org</server> -->";
		$f[]="	<!-- The Root DN to be used to perform LDAP searches -->";
		$f[]="	<!-- <rootDN>CN=Administrator,CN=Users,DC=example,DC=org</rootDN> -->";
		$f[]="	<!-- The password for the Root DN -->";
		$f[]="	<!-- <password>SECRET</password> -->";
		$f[]="	<!-- The Base DN to search for users -->";
		$f[]="	<!-- <baseDN>DC=example,DC=org</baseDN> -->";
		$f[]="	<!-- The attribute to search for the user ID. -->";
		$f[]="	<!-- 'samAccountname' in Windows, 'uid' on most other systems -->";
		$f[]="	<!-- <uidAttr>samAccountname</uidAttr> -->";
		$f[]="	<!-- </authRegister_LDAP> -->";
		$f[]="	";
		$f[]="	<!-- Default Ad-Hoc settings -->";
		$f[]="	<adhocDefaults>";
		$f[]="	  <user> <!-- ... for new users - they can change these settings later -->";
		$f[]="	    <xstatus_receiving_mode>0</xstatus_receiving_mode> <!-- 0 - disable x-status receiving, 1 - receive as ICQ 5.1, 2 - as ICQ 6, 3 - combined -->";
		$f[]="	    <xstatus_sending_mode>0</xstatus_sending_mode> <!-- 0 - disable x-status sending, 1 - send as ICQ 5.1, 2 - as ICQ 6, 3 - combined -->";
		$f[]="	    <xstatus_saving_enabled>1</xstatus_saving_enabled> <!-- 1 - restore latest x-status after login, 0 - do not restore -->";
		$f[]="	    <xstatus_option_smooth>1</xstatus_option_smooth> <!-- 1 - allow partial interconnect between ICQ 5.1 and ICQ 6 x-statuses -->";
		$f[]="	    <xstatus_display_icon_as_PEP>1</xstatus_display_icon_as_PEP> <!-- 1 - display x-status icon as User Mood/Activity/Tune -->";
		$f[]="	    <xstatus_display_text_as_PEP>1</xstatus_display_text_as_PEP> <!-- 1 - Try interpret x-status text as User Mood/Activity/Tune -->";
		$f[]="	    <xstatus_icon_for_transport>0</xstatus_icon_for_transport> <!-- 1 - show self x-status icon as User Mood/Activity/Tune for transport (works for Gajim when transport displayed in roster) -->";
		$f[]="	    <away_messages_receiving>1</away_messages_receiving> <!-- 1 - ask contacts for Away messages -->";
		$f[]="	    <away_messages_sending>1</away_messages_sending> <!-- 1 - allow own Away Message sending -->";
		$f[]="	    <clist_show_phantombuddies>0</clist_show_phantombuddies> <!-- 1 - show old deleted contacts without authorization in roster -->";
		$f[]="	    <clist_deny_all_auth_requests>0</clist_deny_all_auth_requests> <!-- 1 - auto send 'Deny' reply on any authorization request -->";
		$f[]="	    <utf8_messages_sendmode>2</utf8_messages_sendmode> <!-- 0 - never send messages in Unicode, 1 - only when contact sends messages in Unicode, 2 - send always -->";
		$f[]="	    <offline_messages_sendenc>2</offline_messages_sendenc> <!-- 0 - send offline messages in Unicode, 1 - in default message encoding (see above), 2 - auto detect -->";
		$f[]="	    <msgconfirm_sendmode>2</msgconfirm_sendmode> <!-- 0 - never send message confirmations, 1 - send for Unicode messages, 2 - for all messages -->";
		$f[]="	    <msgconfirm_recvmode>1</msgconfirm_recvmode> <!-- 1 - receive message confirmations -->";
		$f[]="	    <user_mood_receiving>1</user_mood_receiving> <!-- 1 - transport should send User Mood/Activity/Tune events to connected user -->";
		$f[]="	    <user_activity_receiving>1</user_activity_receiving> <!--  (requirted for x-status as icon displaying) -->";
		$f[]="	    <user_mood_receiving>1</user_mood_receiving> <!-- set options if most of users has client with support of these PEP events -->";
		$f[]="	    <autoanswer_enable>0</autoanswer_enable> <!-- enable auto answer as reply on any incoming message -->";
		$f[]="	    <autoanswer_hide_dialog>0</autoanswer_hide_dialog> <!-- hide incoming messages when autoanswer enabled -->";
		$f[]="	  </user>";
		$f[]="	</adhocDefaults>";
		$f[]="</pyicqt>";
		$f[]="";
		if(!is_file("/etc/pyicqt.conf.xml.back")){@copy("/etc/pyicqt.conf.xml", "/etc/pyicqt.conf.xml.bak");}
		echo "Starting......: ".date("H:i:s")." ejabberd updating pyicqt.conf.xml done...\n";
		@file_put_contents("/etc/pyicqt.conf.xml", @implode("\n", $f));
	}	
	
	
	
	private function PyMSNt(){
		//listen 1863
		$sock=new sockets();
		$PYMSNT=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PYMSNT")));	
		if(!isset($PYMSNT["MSN_GATEWAY"])){$PYMSNT["MSN_GATEWAY"]=null;}
		if($PYMSNT["MSN_GATEWAY"]==null){return;}
		$f[]="<pymsnt>";
		$f[]="\t<jid>{$PYMSNT["MSN_GATEWAY"]}</jid>";
		$f[]="\t<host>{$PYMSNT["MSN_GATEWAY"]}</host>";
		$f[]="\t<mainServer>127.0.0.1</mainServer>";
		$f[]="\t<port>5347</port>";
		$f[]="\t<secret>{$PYMSNT["PASSWORD"]}</secret>";
		$f[]="\t<lang>en</lang>";
		$f[]="\t<website>http://host.com</website>";
		$f[]="\t<mailNotifications/>";
		$f[]="\t<sessionGreeting>You have just started a session with PyMSNt</sessionGreeting>";
		$f[]="\t<registerMessage>You have successfully registered with PyMSNt</registerMessage>";
		$f[]="\t<allowRegister/>";
		$f[]="\t<getAllAvatars/>";
		$f[]="\t<groupchatTimeout>120</groupchatTimeout> -->";
		$f[]="\t<ftSizeLimit>524288</ftSizeLimit>";
		$f[]="\t<ftRateLimit>2048</ftRateLimit>";
		$f[]="\t<ftJabberPort>8010</ftJabberPort>";
		$f[]="\t<!-- Log settings -->";
		$f[]="";
		$f[]="\t<!-- The logging level ";
		$f[]="\t\t0 -> No logging";
		$f[]="\t\t1 -> Log tracebacks";
		$f[]="\t\t2 -> Log tracebacks, warnings and errors";
		$f[]="\t\t3 -> Log all sorts of informational (mostly useless stuff) ";
		$f[]="\t\t4 -> Log the MSN protocol code sent";
		$f[]="\t-->";
		$f[]="\t<debugLevel>4</debugLevel>";
		$f[]="</pymsnt>";		
		if(!is_file("/etc/pymsnt.conf.xml.back")){@copy("/etc/pymsnt.conf.xml", "/etc/pymsnt.conf.xml.bak");}
		echo "Starting......: ".date("H:i:s")." ejabberd updating pymsnt.conf.xml done...\n";
		@file_put_contents("/etc/pymsnt.conf.xml", @implode("\n", $f));
	}
	
	
	private function globalmodules(){
		
		$f=array();
		
	
		$f[]="{max_fsm_queue, 1000}.";
		$f[]="{s2s_use_starttls, true}.";
		$f[]="{s2s_certfile, \"/etc/ejabberd/ejabberd.pem\"}.	";	
		//$f[]="{auth_method, ldap}.";
		$f[]="{shaper, normal, {maxrate, 1000}}.";
		$f[]="{shaper, fast, {maxrate, 50000}}.";
		$f[]="{acl, local, {user_regexp, \"\"}}.";
		$f[]="\n%%%   ============";
		$f[]="%%%   ACCESS RULES\n";
			
		$f[]="{access, max_user_sessions, [{10, all}]}.";
		$f[]="{access, max_user_offline_messages, [{5000, admin}, {100, all}]}. ";
		//$f[]="{access, local, [{allow, local}]}.";
		$f[]="{access, c2s, [{deny, blocked},{allow, all}]}.";
		$f[]="{access, c2s_shaper, [{none, admin},{normal, all}]}.";
		$f[]="{access, s2s_shaper, [{fast, all}]}.";
		$f[]="{access, announce, [{allow, admin}]}.";
		$f[]="{access, configure, [{allow, admin}]}.";
		$f[]="{access, muc_admin, [{allow, admin}]}.";
		$f[]="{access, muc, [{allow, all}]}.";
		//$f[]="{access, register, [{deny, all}]}.";
		$f[]="{access, register, [{allow, all}]}.";
		$f[]="{access, pubsub_createnode, [{allow, all}]}.";
		$f[]="{language, \"en\"}.";
		$f[]="%%%   =======";
		$f[]="%%%   MODULES";
		$f[]="{modules,";
		$f[]=" [";
		$f[]="  {mod_adhoc,    []},";
		$f[]="  {mod_announce, [{access, announce}]},";
		$f[]="  {mod_caps,     []},";
		$f[]="  {mod_configure,[]},";
		$f[]="  {mod_admin_extra, []},";
		$f[]="  {mod_disco,    []},";
		$f[]="  {mod_last,     []},";
		 $f[]=" {mod_http_bind, []},";
		$f[]="  {mod_muc,      [";
		$f[]="		  {access, muc},";
		$f[]="		  {access_create, muc},";
		$f[]="		  {access_persistent, muc},";
		$f[]="		  {access_admin, muc_admin},";
		$f[]="		  {max_users, 500}";
		$f[]="		 ]},";
		$f[]="  {mod_offline,  [{access_max_user_messages, max_user_offline_messages}]},";
		$f[]="  {mod_privacy,  []},";
		$f[]="  {mod_private,  []},";
		$f[]="  {mod_proxy65,  [";
		$f[]="		  {access, local},";
		$f[]="		  {shaper, c2s_shaper}";
		$f[]="		 ]},";
		$f[]="  {mod_pubsub,   [ ";
		$f[]="		  {access_createnode, pubsub_createnode},";
		$f[]="		  {pep_sendlast_offline, false},";
		$f[]="		  {last_item_cache, false},";
		$f[]="		  {plugins, [\"flat\", \"hometree\", \"pep\"]} ";
		$f[]="		 ]},";
		$f[]="  {mod_register, [";
		$f[]="		  {welcome_message, {\"Welcome!\",";
		$f[]="				     \"Welcome to a Jabber service powered by Debian. \"";
		$f[]="				     \"For information about Jabber visit \"";
		$f[]="				     \"http://www.jabber.org\"}},";
		$f[]="";
		$f[]="";
		$f[]="		  {access, register}";
		$f[]="		 ]},";
		$f[]="  {mod_roster,   []},";
		$f[]="  {mod_stats,    []},";
		$f[]="  {mod_time,     []},";
		$f[]="  {mod_vcard,    []},";
		$f[]="  {mod_vcard_ldap,";
		$f[]="   [{ldap_vcard_map,";
		$f[]="     [{\"NICKNAME\", \"%u\", []},";
		$f[]="      {\"GIVEN\", \"%s\", [\"givenName\"]},";
		$f[]="      {\"MIDDLE\", \"%s\", [\"initials\"]},";
		$f[]="      {\"FAMILY\", \"%s\", [\"sn\"]},";
		$f[]="      {\"FN\", \"%s\", [\"cn\"]},";
		$f[]="      {\"EMAIL\", \"%s\", [\"mail\"]},";
		$f[]="      {\"ORGNAME\", \"%s\", [\"company\"]},";
		$f[]="      {\"ORGUNIT\", \"%s\", [\"department\"]},";
		$f[]="      {\"CTRY\", \"%s\", [\"c\"]},";
		$f[]="      {\"LOCALITY\", \"%s\", [\"l\"]},";
		$f[]="      {\"STREET\", \"%s\", [\"streetAddress\"]},";
		$f[]="      {\"REGION\", \"%s\", [\"st\"]},";
		$f[]="      {\"PCODE\", \"%s\", [\"postalCode\"]},";
		$f[]="      {\"TITLE\", \"%s\", [\"title\"]},";
		$f[]="      {\"URL\", \"%s\", [\"wWWHomePage\"]},";
		$f[]="      {\"DESC\", \"%s\", [\"description\"]},";
		$f[]="      {\"TEL/CELL\", \"%s\", [\"mobile\"]},";
		$f[]="      {\"TEL/NUMBER\", \"%s\", [\"telephoneNumber\"]}]},";
		$f[]="    {ldap_search_fields,";
		$f[]="     [{\"User\", \"%u\"},";
		$f[]="      {\"Name\", \"givenName\"},";
		$f[]="      {\"Family Name\", \"sn\"},";
		$f[]="      {\"Email\", \"mail\"},";
		$f[]="      {\"Company\", \"company\"},";
		$f[]="      {\"Department\", \"department\"},";
		$f[]="      {\"Role\", \"title\"},";
		$f[]="      {\"Description\", \"description\"},";
		$f[]="      {\"Phone\", \"telephoneNumber\"}]},";
		$f[]="    {ldap_search_reported,";
		$f[]="     [{\"Full Name\", \"FN\"},";
		$f[]="      {\"Nickname\", \"NICKNAME\"},";
		$f[]="      {\"Email\", \"EMAIL\"}]}";
		$f[]="      ]},";
		$f[]="  {mod_version,  []}";
		$f[]=" ]}.";	
		$f[]="";
		return 	@implode("\n", $f);
		
	}
	
	
	
}