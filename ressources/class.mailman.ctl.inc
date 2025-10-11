<?php
if(!isset($GLOBALS["EXECUTED_AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["EXECUTED_AS_ROOT"]=true;$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["EXECUTED_AS_ROOT"]=false;$GLOBALS["AS_ROOT"]=true;}}
include_once(dirname(__FILE__)."/class.ldap.inc");
include_once(dirname(__FILE__)."/class.mysql.inc");;
include_once(dirname(__FILE__).'/class.user.inc');


class mailmancontrol{
	var $list=null;
	var $urlhost=null;
	var $emailhost=null;
	var $adminmail=null;
	var $mangle=0;
	var $listsInDisk=array();
	var $ConfigIndisk=array();
	
	function mailmancontrol($listname=null){
		
		if($listname<>null){
			$this->list=$listname;
			$this->load_mysql_list();
		}
		
		
		if($GLOBALS["AS_ROOT"]){$this->loadListDisk();}
		
		
	}
	
	private function isExistsInMysql($listname){
		$length1=strlen($listname);
		$sql="SELECT `list` FROM mailmaninfos WHERE `list`='$listname'";
		$q=new mysql();
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}
		$length2=strlen(trim($ligne["list"]));
		if(trim($ligne["list"])==null){return false;}
		if($length2<$length1){
			if($GLOBALS["VERBOSE"]){writelogs("$listname seems to not exists on mailmaninfos table ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			return false;
		}
		writelogs("$listname `{$ligne["list"]}` Exists on mailmaninfos table ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return true;
	}
	
	private function load_mysql_list(){
		$q=new mysql();
		if($this->list==null){
			writelogs("Fatal cannot load a null list..",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		$sql="SELECT * FROM mailmaninfos WHERE `list`='$this->list'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(trim($ligne["list"])==null){return;}
		$ligne["adminmail"]=str_replace("[", "", $ligne["adminmail"]);
		$ligne["adminmail"]=str_replace("]", "", $ligne["adminmail"]);
		$this->list=$ligne["list"];
		$this->urlhost=$ligne["urlhost"];
		$this->emailhost=$ligne["emailhost"];
		$this->adminmail=$ligne["adminmail"];
		$this->mangle=$ligne["mangle"];
		
	}
	
	
	private function loadListDisk(){
		$cmd="/usr/lib/mailman/bin/list_lists";
		exec($cmd,$array);
		foreach ($array as $num=>$ligne){
			if(preg_match("#([a-zA-Z0-9-_\.]+)\s+-\s+#",$ligne,$re)){
				$this->listsInDisk[strtolower($re[1])]=true;
				if($GLOBALS["VERBOSE"]){if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." Mailman list:`".strtolower($re[1])."`\n";}}
			}
		}
		
		if($GLOBALS["AS_ROOT"]){
			if(!isset($this->listsInDisk["mailman"])){
				if(!is_dir("/var/lib/mailman/archives/private")){@mkdir("/var/lib/mailman/archives/private",0775,true);}
				$users=new usersMenus();
				$ldap=new clladp();
				echo "Starting......: ".date("H:i:s")." Mailman Creating default list `mailman` `postmaster@$users->hostname` And SuperAdmin password\n"; 
				$cmd="/usr/lib/mailman/bin/newlist --urlhost=\"$users->hostname\" --emailhost=\"$users->hostname\" --quiet mailman \"postmaster@$users->hostname\" \"$ldap->ldap_password\"";
				if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
				shell_exec($cmd);
				$cmd="/usr/lib/mailman/bin/config_list -i /var/lib/mailman/data/sitelist.cfg mailman";
				if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
				shell_exec($cmd);				
				
			}
		}
		
	}
	
	public function CreateMysqlList(){
		$q=new mysql();
		$added=0;
		if($GLOBALS["AS_ROOT"]){$added=1;}
		if(trim($this->list)==null){echo "No list set in ".__FUNCTION__." ".__FILE__." line ".__LINE__;return;}
		writelogs("Create new List `$this->list`",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$sql="INSERT IGNORE INTO mailmaninfos (`list`,`adminmail`,`added`,`urlhost`,`emailhost`,`mangle`) 
		VALUES('$this->list','$this->adminmail','$added','$this->urlhost','$this->emailhost','$this->mangle')";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){if(preg_match("#Unknown column#", $q->mysql_error)){$q->BuildTables();$q->QUERY_SQL($sql,"artica_backup");}}
		if(!$q->ok){if($GLOBALS["AS_ROOT"]){squid_admin_mysql(2, "Fatal $this->list created\n$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "mailman");}echo $q->mysql_error;return;}	
		if(!$GLOBALS["AS_ROOT"]){$sock=new sockets();$sock->getFrameWork("mailman.php?checks-add=yes");}
		return true;
	}
	
	private function FreeWebsExists($list){
			$q=new mysql();
			$urlhost=$this->GET_VALUE_URLHOST($list);
			$sql="SELECT `servername` FROM freeweb WHERE `servername`='$urlhost'";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			if($ligne["servername"]==null){return false;}
			return true;
		
	}
	
	private function GET_VALUE_URLHOST($list){
			if(isset($GLOBALS["GET_VALUE_URLHOST"][$list])){return $GLOBALS["GET_VALUE_URLHOST"][$list];}
			$q=new mysql();
			$sql="SELECT urlhost FROM mailmaninfos WHERE `list`='$list'";
			$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			$GLOBALS["GET_VALUE_URLHOST"][$list]=trim($ligne["urlhost"]);
			return $ligne["urlhost"];
		
	}
	
	public function chpassword($email,$password){
		
		$unix=new unix();
		$password=$unix->shellEscapeChars($password);
		$sql="SELECT * FROM mailmaninfos WHERE `delete`=0 AND `adminmail`='$email'";
		$q=new mysql();
		$results = $q->QUERY_SQL($sql,'artica_backup');
		while ($ligne = mysqli_fetch_assoc($results)) {
			shell_exec("/usr/lib/mailman/bin/change_pw --listname={$ligne["list"]} --password=$password --quiet >/dev/null 2>&1");
		}

	}
	
	public function FreeWebsChecks($list){
			if($this->FreeWebsExists($list)){return;}
			$q=new mysql();
			$urlhost=$this->GET_VALUE_URLHOST($list);
			if($urlhost==null){return;}
			$sql="INSERT INTO freeweb (servername,groupware) VALUES ('$urlhost','MAILMAN')";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){squid_admin_mysql(2, "Fatal $this->list created\n$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "mailman");return;}
			$this->FreeWebRebuildWebsite($urlhost,$list);
			if(!isset($GLOBALS["POSTFIXRELOADED"])){
				$GLOBALS["POSTFIXRELOADED"]=true;
				$this->postfix_reload();
			}
		
	}
	
	private function FreeWebRebuildWebsite($servername,$list){
		if($GLOBALS["AS_ROOT"]){
			$unix=new unix();
			$php=$unix->LOCATE_PHP5_BIN();
			$nohup=$unix->find_program("nohup");
			$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --sitename $servername >/dev/null 2>&1 &");
			shell_exec($cmd);
			$cmd=trim("$nohup /usr/lib/mailman/bin/withlist -l -r fix_url $list -u $servername >/dev/null 2>&1 &");
			shell_exec($cmd);
			
		}else{
			$sock=new sockets();
			$sock->getFrameWork("freeweb.php?rebuild-vhost=yes&servername=$servername");
		}
	}
	
	
	public function ChecksCreated(){
		$sql="SELECT * FROM mailmaninfos WHERE added=0 AND `delete`=0";
		$q=new mysql();
		$results = $q->QUERY_SQL($sql,'artica_backup');
		$reconfigure_postfix=false;
		while ($ligne = mysqli_fetch_assoc($results)) {
			$listname=strtolower($ligne["list"]);
			if(isset($this->listsInDisk[$listname])){$q->QUERY_SQL("UPDATE mailmaninfos SET added=1 WHERE `list`='$listname'",'artica_backup');continue;}
			$domain=$ligne["domain"];
			$adminmail=$ligne["adminmail"];
			$urlhost=$ligne["urlhost"];
			$emailhost=$ligne["emailhost"];
			$ldap=new clladp();
			$uid=$ldap->uid_from_email($adminmail);
			$ct=new user($uid);
			$password=$ct->password;
			$resultsCMD=array();
			$cmd=trim("/usr/lib/mailman/bin/newlist --quiet --urlhost=\"$urlhost\" --emailhost=\"$emailhost\" \"$listname\" \"$adminmail\" \"$password\" 2>&1");
			exec($cmd,$resultsCMD);
			squid_admin_mysql(2, "new $listname created\n".@implode("\n", $resultsCMD), __FUNCTION__, __FILE__, __LINE__, "mailman");
			
			$this->loadListDisk();
			if(isset($this->listsInDisk[$listname])){
				$q->QUERY_SQL("UPDATE mailmaninfos SET added=1 WHERE `list`='$listname'",'artica_backup');
				$this->FreeWebsChecks($listname);
				continue;
			}
		}
		
		
	}
	
	private function create_single_list($listname){
		$this->list=$listname;
		$this->load_mysql_list();
		$urlhost=null;
		if($this->urlhost<>null){
			$urlhost=" --urlhost=\"$urlhost\"";
		}
		if($this->adminmail==null){
			squid_admin_mysql(2, "Fatal $listname no such administrator", __FUNCTION__, __FILE__, __LINE__, "mailman");
			return;
		}
		$ldap=new clladp();
		$uid=$ldap->uid_from_email($this->adminmail);
		if($uid==null){
			squid_admin_mysql(2, "Fatal $listname `$this->adminmail` no such member", __FUNCTION__, __FILE__, __LINE__, "mailman");
			return;	
		}
		$ct=new user($uid);
		$cmd=trim("/usr/lib/mailman/bin/newlist --quiet$urlhost  --emailhost=\"$this->emailhost\" \"$listname\" \"$this->adminmail\" \"$ct->password\" 2>&1");
		exec($cmd,$resultsCMD);
		squid_admin_mysql(2, "new $listname created\n".@implode("\n", $resultsCMD), __FUNCTION__, __FILE__, __LINE__, "mailman");
		$this->FreeWebsChecks($listname);
		$this->postfix_reload();
	}
	
	
	
	private function ChecksInMysql(){
		$reconfigure_postfix=false;
		$sql="SELECT `list` FROM mailmaninfos WHERE `delete`=0";
		$q=new mysql();
		$results = $q->QUERY_SQL($sql,'artica_backup');
		while ($ligne = mysqli_fetch_assoc($results)) {
			$listname=$ligne["list"];
			if(isset($this->listsInDisk[$listname])){continue;}
			$this->create_single_list($listname);
			$reconfigure_postfix=true;
		}
		if($reconfigure_postfix){$this->postfix_reload();}
	}
	
	
	public function postfix_reload(){
		if(!$GLOBALS["AS_ROOT"]){return;}
		$sock=new sockets();
		$unix=new unix();
		$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
		if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
		if($EnablePostfixMultiInstance==1){return;}
		$cmd=$unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.postfix.hashtables.php --aliases >/dev/null 2>&1";
		shell_exec($cmd);
		$cmd=$unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.postfix.hashtables.php --transport >/dev/null 2>&1";
		shell_exec($cmd);
		
	}
	
	
	
	public function EditMysqlList(){
		if(!$this->isExistsInMysql($this->list)){if(!$this->CreateMysqlList()){echo "Unable to create $this->list in MySQL\n";return;}}
		if($this->urlhost<>null){$urlhost=",`urlhost`='$this->urlhost'";}
		$sql="UPDATE mailmaninfos 
		SET adminmail='$this->adminmail',
		emailhost='$this->emailhost',
		`mangle`='$this->mangle'$urlhost
		WHERE `list`='$this->list'";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		if(!$GLOBALS["AS_ROOT"]){
			$sock=new sockets();
			$sock->getFrameWork("mailman.php?checks-add=yes");
			$sock->getFrameWork("mailman.php?check-single=yes&list=$this->list");
			return;
		}
		$this->FreeWebsChecks($this->list);
	}
	
	public function ChecksToDelete(){
		$sql="SELECT `list` FROM mailmaninfos WHERE `delete`=1";
		$q=new mysql();
		$results = $q->QUERY_SQL($sql,'artica_backup');
		while ($ligne = mysqli_fetch_assoc($results)) {
			$res=array();
			exec("/usr/lib/mailman/bin/rmlist -a \"{$ligne["list"]}\" 2>&1",$res);
			squid_admin_mysql(2, "{$ligne["list"]} marked for deletion has been deleted from disk\n".@implode("\n", $res), __FUNCTION__, __FILE__, __LINE__, "mailman");
			$q->QUERY_SQL("DELETE FROM mailmaninfos WHERE `list`='{$ligne["list"]}'","artica_backup");
			if(!$q->ok){squid_admin_mysql(2, "Fatal delet list {$ligne["list"]} from MySQL \n$q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "mailman");continue;}
			$this->FreeWebDelete($ligne["list"]);
		}	
		
	}
	
	private function FreeWebDelete($list){
		if(!$GLOBALS["AS_ROOT"]){return;}
		if(!$this->FreeWebsExists($list)){return;}
		$servername=$this->GET_VALUE_URLHOST($list);
		$sql="INSERT INTO drupal_queue_orders(`ORDER`,`servername`) VALUES('DELETE_FREEWEB','$servername')";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$unix=new unix();
		$nohup=$unix->find_program("nohup");
		$cmd=$nohup." ". $unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.freeweb.php --drupal-schedules >/dev/null 2>&1 &";
		shell_exec(trim($cmd));		
	}
	
	
	
	public function ChecksAlreadyCreated(){
		reset($this->listsInDisk);
		while (list ($listname, $val) = each ($this->listsInDisk) ){
			$this->list_load_configs($listname);
			if($GLOBALS["VERBOSE"]){echo "Checks List:`$listname`\n";}
		}
		
		reset($this->listsInDisk);
		while (list ($listname, $config) = each ($this->ConfigIndisk) ){
			$update=false;
			$this->list=$listname;
			$this->load_mysql_list();
			if($this->emailhost==null){$this->emailhost=$config["host_name"];$update=true;}
			if($this->adminmail==null){$this->adminmail=$config["owner"];$update=true;}
			if(!$this->isExistsInMysql($listname)){$this->CreateMysqlList();continue;}
			if($update){$this->EditMysqlList();}else{$this->EditLocalList();}
		}
		
		if($GLOBALS["VERBOSE"]){echo "this->ChecksInMysql()\n";}
		$this->ChecksInMysql();
	}
	
	private function EditLocalList(){
		$unix=new unix();
		$tmpfile=$unix->FILE_TEMP();
		$cmd=trim("/usr/lib/mailman/bin/config_list -o $tmpfile $this->list");	
		shell_exec($cmd);	
		$f=file($tmpfile);	
		foreach ($f as $num=>$ligne){
			if(preg_match("#^owner.*?=#", $ligne)){$f[$num]="owner = ['$this->adminmail']";}
			if(preg_match("#^host_name.*?=#", $ligne)){$f[$num]="host_name = '$this->emailhost'";}
		}
		
		@file_put_contents($tmpfile, @implode("\n", $f));
		$cmd=trim("/usr/lib/mailman/bin/config_list -i $tmpfile $this->list");
		@unlink($tmpfile);	
	}
	
	
	private function _list_get_admin($listname){
		$array=array();
		$cmd=trim("/usr/lib/mailman/bin/list_admins \"$listname\" 2>&1");
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		exec("$cmd",$array);
		foreach ($array as $num=>$ligne){
			if(preg_match("#.+?:.+?:\s+(.+)#", $ligne,$re)){
				$owners=trim($re[1]);
				if(strpos($owners, " ")>0){$tp=explode(" ", $owners);$owner=$tp[0];}else{$owner=$owners;}
				return $owner;
			}
			
		}
	}
	
	private function list_load_configs($listname){
		$unix=new unix();
		$tmpfile=$unix->FILE_TEMP();
		$this->ConfigIndisk[$listname]["owner"]=$this->_list_get_admin($listname);
		$cmd=trim("/usr/lib/mailman/bin/config_list -o $tmpfile $listname");
		
		shell_exec($cmd);
		$f=file($tmpfile);
		@unlink($tmpfile);
		$FD=0;
		$OK=0;
		foreach ($f as $num=>$ligne){
			if(!preg_match("#^([a-zA-Z0-9\_\-\s]+)=(.+)#", $ligne,$re)){$FD++;continue;}
				$OK++;
				$re[2]=str_replace("'", "", $re[2]);
				$re[2]=str_replace("\"", "", $re[2]);
				$this->ConfigIndisk[$listname][trim($re[1])]=trim($re[2]);
			}
		
		if($GLOBALS["VERBOSE"]){echo $cmd." - - - > ". count($f)." lines $FD not parsed $OK in Array\n";}
		
	}
	
	public function mm_cfg(){
		$POSTFIX_STYLE_VIRTUAL_DOMAINS=array();
		$add_virtualhost=array();
		$VIRTUAL_HOSTS=array();
		$sql="SELECT `list`,emailhost,urlhost FROM mailmaninfos WHERE `delete`=0";
		$q=new mysql();
		$unix=new unix();
		$ln=$unix->find_program("ln");
		$results = $q->QUERY_SQL($sql,'artica_backup');
		while ($ligne = mysqli_fetch_assoc($results)) {
			$emailhost=$ligne["emailhost"];
			$urlhost=$ligne["urlhost"];
			if(trim($urlhost)==null){continue;}
			$tp=explode(".", $emailhost);
			$hostname=$tp[0];
			unset($tp[0]);
			$domain=@implode(".", $tp);
			$VIRTUAL_HOSTS[]="'$urlhost': $domain'";
			$POSTFIX_STYLE_VIRTUAL_DOMAINS[]="'$domain'";
			$add_virtualhost[]="add_virtualhost('$urlhost', '$domain')";
		}
		
			$users=new usersMenus();
			$f[]="from Defaults import *";
			$f[]="MAILMAN_SITE_LIST = 'mailman'";
			$f[]="PRIVATE_ARCHIVE_URL = '/mailman/private'";
			$f[]="IMAGE_LOGOS         = '/images/mailman/'";
			$f[]="DEFAULT_EMAIL_HOST = '$users->hostname'";
			$f[]="DEFAULT_URL_HOST='http://$users->hostname'";
			$f[]="USE_ENVELOPE_SENDER    = 0";
			$f[]="DEFAULT_SEND_REMINDERS = 0";
			$f[]="# Uncomment if you want to filter mail with SpamAssassin. For";
			$f[]="# more information please visit this website:";
			$f[]="# http://www.jamesh.id.au/articles/mailman-spamassassin/";
			$f[]="# GLOBAL_PIPELINE.insert(1, 'SpamAssassin')";
			$f[]="DEFAULT_URL_PATTERN='http://%s/mailman/'";
			$f[]="VIRTUAL_HOST_OVERVIEW = 1";
			$f[]="PUBLIC_ARCHIVE_URL= '/pipermail/%(listname)s/index.html'";
			$f[]="DEFAULT_SERVER_LANGUAGE= 'en'";
			$f[]="MTA='Postfix'";
			$f[]="VIRTUAL_HOSTS= {}";
			$f[]="VIRTUAL_HOSTS.clear()";

			
			$f[]="add_virtualhost(DEFAULT_URL_HOST, DEFAULT_EMAIL_HOST)";	
			$f[]="POSTFIX_STYLE_VIRTUAL_DOMAINS = [".@implode(",", $POSTFIX_STYLE_VIRTUAL_DOMAINS)."]";
			//$f[]="VIRTUAL_HOSTS = { ".@implode(",", $VIRTUAL_HOSTS)." }";
			$f[]=@implode("\n", $add_virtualhost);
			$finalData=@implode("\n", $f)."\n";
			echo "Starting......: ".date("H:i:s")." Mailman updating /etc/mailman/mm_cfg.py\n";
			@file_put_contents("/etc/mailman/mm_cfg.py", $finalData);
			echo "Starting......: ".date("H:i:s")." Mailman updating /usr/lib/mailman/Mailman/mm_cfg.py\n";
			@file_put_contents("/usr/lib/mailman/Mailman/mm_cfg.py", $finalData);
			
			$ldap=new clladp();
			shell_exec("/usr/lib/mailman/bin/mmsitepass \"$ldap->ldap_password\"");
			shell_exec("/var/lib/mailman/bin/check_perms -f");
			@mkdir("/var/lib/mailman/archives/private",0775,true);
			@mkdir("/var/lib/mailman/archives/public",0775,true);
	
			if(!is_file("/etc/mailman/postfix-to-mailman.py")){
				@mkdir("/etc/mailman",0755,true);
				if(is_file("/var/lib/mailman/templates/postfix-to-mailman.py")){
					shell_exec("$ln -s /var/lib/mailman/templates/postfix-to-mailman.py /etc/mailman/postfix-to-mailman.py");
				}
			}
			
			
	}
	

}




class mailmanctl{
	
	var $DEFAULT_URL_PATTERN=null;
	var $PUBLIC_ARCHIVE_URL=null;
	var $AutoCreateAccountIPArray;
	
	function mailmanctl(){
		$sock=new sockets();
		$this->DEFAULT_URL_PATTERN=$sock->GET_INFO("MailManDefaultUriPattern");
		$this->PUBLIC_ARCHIVE_URL=$sock->GET_INFO("MailManDefaultArchiveUri");
		
		
		if($this->DEFAULT_URL_PATTERN==null){
			$this->DEFAULT_URL_PATTERN="https://%s/cgi-bin/mailman/";
			$this->PUBLIC_ARCHIVE_URL="https://%(hostname)s/pipermail/%(listname)s";
			$this->Save(1);	

			}
			
		if($this->PUBLIC_ARCHIVE_URL==null){
			$this->PUBLIC_ARCHIVE_URL="https://%(hostname)s/pipermail/%(listname)s";
			$this->Save(1);	
		}
		
		$this->BuildBranch();
		
	}
	
	
	function Save($silent=0){
		$sock=new sockets();
		$sock->SET_INFO("MailManDefaultUriPattern",$this->DEFAULT_URL_PATTERN);
		$sock->SET_INFO("MailManDefaultArchiveUri",$this->PUBLIC_ARCHIVE_URL);
		$sock->getFrameWork('cmd.php?MailManSaveGlobalSettings=yes');
		
		
		
		
		$tpl=new templates();
		if($silent==0){
			echo $tpl->_ENGINE_parse_body("{success}","postfix.index.php");
		}
		
	}
	
	
	function BuildBranch(){
		$ldap=new clladp();
		$dn="cn=mailman,cn=artica,$ldap->suffix";
		if(!$ldap->ExistsDN($dn)){
			$upd["objectClass"][]='top';
			$upd["objectClass"][]='PostFixStructuralClass';
			$upd["cn"][]="mailman";
			if(!$ldap->ldap_add($dn,$upd)){echo $ldap->ldap_last_error;exit;}
		}
		
		$dn="cn=robots,cn=mailman,cn=artica,$ldap->suffix";
		if(!$ldap->ExistsDN($dn)){
			$upd["objectClass"][]='top';
			$upd["objectClass"][]='PostFixStructuralClass';
			$upd["cn"][]="robots";
			if(!$ldap->ldap_add($dn,$upd)){echo $ldap->ldap_last_error;exit;}
		}		
	}
	
	
	function BuildRobots($list,$domain){
		$array[]="admin";
		$array[]="bounces";
		$array[]="confirm";
		$array[]="join";
		$array[]="leave";
		$array[]="owner";
		$array[]="request";
		$array[]="subscribe";
		$array[]="unsubscribe";
		$tpl=new templates();
		$ldap=new clladp();
		$dn="cn=$list@$domain,cn=robots,cn=mailman,cn=artica,$ldap->suffix";
		if(!$ldap->ExistsDN($dn)){
			$upd["objectClass"][]='top';
			$upd["objectClass"][]='ArticaMailManRobots';
			$upd["MailManAliasPath"][]="\"|/var/lib/mailman/mail/mailman post $list\"";
			$upd["TransportMailmanMaps"][]='mailman:';
			$upd["cn"][]="$list@$domain";
			if(!$ldap->ldap_add($dn,$upd)){echo $ldap->ldap_last_error;exit;}
			echo $tpl->_ENGINE_parse_body("{success}:$list\n");
		}else{
			unset($upd);
			echo $tpl->_ENGINE_parse_body("{success}:$list\n");
		}
		
		foreach ($array as $num=>$ligne){
			$dn="cn=$list-$ligne@$domain,cn=robots,cn=mailman,cn=artica,$ldap->suffix";
			if(!$ldap->ExistsDN($dn)){
				$upd["objectClass"][]='top';
				$upd["objectClass"][]='ArticaMailManRobots';
				$upd["MailManAliasPath"][]="\"|/var/lib/mailman/mail/mailman $ligne $list\"";
				$upd["cn"][]="$list-$ligne@$domain";
				$upd["TransportMailmanMaps"][]='mailman:';
				if(!$ldap->ldap_add($dn,$upd)){echo $ldap->ldap_last_error;exit;}
				echo $tpl->_ENGINE_parse_body("{success}:$list-$ligne\n");
				unset($upd);
			}else{
				echo $tpl->_ENGINE_parse_body("{success}:$list-$ligne\n");
				unset($upd);
			}

		}
		
		$main=new main_cf();
		$main->save_conf();
		$main->save_conf_to_server();
		}
		
	function DeleteRobot($list,$domain){
		$array[]="admin";
		$array[]="bounces";
		$array[]="confirm";
		$array[]="join";
		$array[]="leave";
		$array[]="owner";
		$array[]="request";
		$array[]="subscribe";
		$array[]="unsubscribe";
		
		$ldap=new clladp();
		$dn="cn=$list@$domain,cn=robots,cn=mailman,cn=artica,$ldap->suffix";
		if($ldap->ExistsDN($dn)){$ldap->ldap_delete($dn);}
		
		foreach ($array as $num=>$ligne){
			$dn="cn=$list-$ligne@$domain,cn=robots,cn=mailman,cn=artica,$ldap->suffix";		
			if($ldap->ExistsDN($dn)){$ldap->ldap_delete($dn);}
			}
		}

}