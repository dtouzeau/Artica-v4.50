<?php
include_once(dirname(__FILE__) . '/logs.inc');
include_once(dirname(__FILE__) . '/class.templates.inc');
include_once(dirname(__FILE__) . '/class.ldap.inc');
include_once(dirname(__FILE__) . '/class.pdns.inc');
include_once(dirname(__FILE__) . '/class.mysql.inc');
include_once(dirname(__FILE__) . '/class.users.menus.inc'); 


class vhosts{
	var $ou;
	var $WWWMysqlUser;
	var $WWWMysqlPassword;
	var $WWWAppliUser;
	var $WWWAppliPassword;
	var $WWWMultiSMTPSender;
	var $WWWEnableAddressBook;
	var $apachedomainname;
	var $apacheIPAddress;
	var $WWWSSLMode="FALSE";
	var $IMG_ARRAY_128=array("LMB"=>"logo_lmb-200.png",
	"JOOMLA"=>"128.joomla.png",
	"JOOMLA17"=>"128.joomla.png",
	"SUGAR"=>"98-sugarcrm.png","ARTICA_USR"=>"bg_user-120.png",
	"OPENGOO"=>"opengoo-128.png",
	"ROUNDCUBE"=>"roundcube3logo.png",
	"GROUPOFFICE"=>"groupoffice-128.png",
	"ZARAFA"=>"zarafa-logo-128.png",
	"ZARAFA_MOBILE"=>"zarafa-logo-128.png",
	"WEBAPP"=>"zarafa-logo-128.png",
	"DRUPAL"=>"drupal-logo-128.png",
	"WEBDAV"=>"webdav-128.png",
	"BACKUPPC"=>"backuppc-128.png",
	"PIWIGO"=>"piwigo-128.png",
	"SQUID_STATS"=>"SQUID_STATS-128.png",
	"ARTICA_ADM"=>"bg_user-120.png",
	"ARTICA_ADMPRXY"=>"bg_user-120.png",
	"ARTICA_ADM"=>"eyeos-128.png",
	"Z-PUSH"=>"zarafa-logo-128.png",
	"PIWIK"=>"piwik-logo-128.png",
	"WORDPRESS"=>"wordpress-128.png",
	"DOTCLEAR"=>"bg-dotclear.png",
	"CONCRETE5"=>"concrete5-128.png",
	"ARKEIA"=>"arkeia-128.png",
	"SARG"=>"sarg-128.png",
	"KLMS"=>"logo_KLMS-128.png",
	"XAPIAN"=>"xapian-logo-128.png",
	"ANDROWEB"=>"android-logo-128.png",
	"MAILMAN"=>"mailman-128.png",
	"PIWIGO"=>"piwigo-128.png",
	"MAILHOSTING"=>"attach-document-128.png",
	"OWNCLOUD"=>"owncloud-128.png",
	"SPLASHSQUID"=>"webfilter-128.png",
	"ARTICA_PRXYLOGS"=>"log-monitor-128",
	"ERRSQUID"=>"128-banned.png",
	"WPAD"=>"script-128.png",
	"UPDATEUTILITY"=>"kaspersky-logo-128.png",
    "UFDBGUARD"=>"128-banned.png",
	"APP_FILEZ_WEB"=>"filez-logo-128.png",
	"CHILLI"=>"webfilter-128.png",
	"WPADDYN"=>"script-128.png",
			
//	
	);
	
	var $IMG_ARRAY_64=array(
	"LMB"=>"logo_lmb-64.png",
	"JOOMLA"=>"64.joomla.png",
	"JOOMLA17"=>"64.joomla.png",
	"SUGAR"=>"64-sugarcrm.png",
	"ARTICA_USR"=>"64-bg_user.png",
	"OPENGOO"=>"64-opengoo.png",
	"ROUNDCUBE"=>"64-roundcube.png",
	"GROUPOFFICE"=>"groupoffice-64.png",
	"ZARAFA"=>"zarafa-logo-64.png",
	"ZARAFA_MOBILE"=>"zarafa-logo-64.png",
	"WEBAPP"=>"zarafa-logo-64.png",
	"Z-PUSH"=>"zarafa-logo-64.png",
	"DRUPAL"=>"drupal-64.png",
	"WEBDAV"=>"webdav-64.png",
	"BACKUPPC"=>"backuppc-64.png",
	"PIWIGO"=>"piwigo-64.png",
	"SQUID_STATS"=>"64-bg_user.png",
	"ARTICA_ADM"=>"64-bg_user.png",
	"ARTICA_ADMPRXY"=>"64-bg_user.png",
	"ARTICA_PRXYLOGS"=>"log-monitor-64.png",
	
	"ARTICA_REGISTER_OU"=>"www_register-64.png",
	"ARTICA_MINIADM"=>"miniadm-64.png",
	"EYEOS"=>"eyeos-64.png",
	"POWERADMIN"=>"poweradmin-64.png",
	"PIWIK"=>"piwik-logo-64.png",
	"WORDPRESS"=>"wordpress-64.png",
	"PROXY_CATS"=>"database-64.png",
	"DOTCLEAR"=>"64-dotclear.png",
	"CONCRETE5"=>"concrete5-64.png",
	"ARKEIA"=>"arkeia-64.png",
	"SARG"=>"sarg-logo.png",
	"KLMS"=>"logo_KLMS-64.png",
	"XAPIAN"=>"xapian-logo-64.png",
	"ANDROWEB"=>"android-logo-64.png",
	"MAILMAN"=>"mailman-64.png",
	"PIWIGO"=>"piwigo-64.png",
	"MAILHOSTING"=>"attach-document-64.png",
	"OWNCLOUD"=>"owncloud-64.png",
	"SPLASHSQUID"=>"webfilter-64.png",
	"ERRSQUID"=>"64-banned.png",
	"UFDBGUARD"=>"64-banned.png",
	"UPDATEUTILITY"=>"kaspersky-update-64.png",
	"WEBDAVFREE"=>"organisation-64.gif",
	"APP_FILEZ_WEB"=>"filez-logo-64.png",
	"CHILLI"=>"webfilter-64.png",
	"WPADDYN"=>"script-64.png",
			
	);

	
	var $WWWEnableAddressBook_ENABLED=array("ROUNDCUBE"=>true);
	
	
	
	var $noneeduser=array("JOOMLA"=>true,"ROUNDCUBE"=>true,"ARTICA_USR"=>true,"ZARAFA_MOBILE"=>true,"ZARAFA"=>true,"DRUPAL"=>true,
	'WEBDAV'=>true,"BACKUPPC"=>true,"SQUID_STATS"=>true,"ARKEIA"=>true,"XAPIAN"=>true,"SPLASHSQUID"=>true,"ARTICA_PRXYLOGS"=>true);
	var $noneeduser_mysql=array("ARTICA_USR"=>true,"ZARAFA_MOBILE"=>true,"ZARAFA"=>true,"DRUPAL"=>true,"WEBDAV"=>true,"BACKUPPC"=>true,"SQUID_STATS"=>true,
	"ARKEIA"=>true,"SARG"=>true,"XAPIAN"=>true,"SPLASHSQUID"=>true,"ARTICA_PRXYLOGS"=>true,
	"ERRSQUID"=>true,"UFDBGUARD"=>true,"WEBDAVFREE"=>true,"CHILLI"=>true,"WPADDYN"=>true);
	

	
	var $TEXT_ARRAY=array(
	"LMB"=>array("TITLE"=>"APP_LMB","TEXT"=>"APP_LMB_TEXT"),
	"JOOMLA"=>array("TITLE"=>"APP_JOOMLA","TEXT"=>"APP_JOOMLA_TEXT"),
	"JOOMLA17"=>array("TITLE"=>"APP_JOOMLA","TEXT"=>"APP_JOOMLA_TEXT"),
	"ROUNDCUBE"=>array("TITLE"=>"APP_ROUNDCUBE","TEXT"=>"APP_ROUNDCUBE_TEXT"),
	"SUGAR"=>array("TITLE"=>"APP_SUGARCRM","TEXT"=>"APP_SUGARCRM_TEXT"),
	"ARTICA_USR"=>array("TITLE"=>"APP_ARTICA_USR","TEXT"=>"APP_ARTICA_USR_TEXT"),
	"OPENGOO"=>array("TITLE"=>"APP_OPENGOO","TEXT"=>"APP_OPENGOO_TEXT"),
	"GROUPOFFICE"=>array("TITLE"=>"APP_GROUPOFFICE","TEXT"=>"APP_GROUPOFFICE_TEXT"),
	"ZARAFA"=>array("TITLE"=>"APP_ZARAFA","TEXT"=>"APP_ZARAFA_TEXT"),
	"ZARAFA_MOBILE"=>array("TITLE"=>"APP_ZARAFA_MOBILE_ACCESS","TEXT"=>"APP_ZARAFA_TEXT"),
	"WEBAPP"=>array("TITLE"=>"APP_ZARAFA_WEBAPP","TEXT"=>"APP_ZARAFA_WEBAPP_TEXT"),
	"DRUPAL"=>array("TITLE"=>"APP_DRUPAL","TEXT"=>"APP_DRUPAL_TEXT"),
	"WEBDAV"=>array("TITLE"=>"APP_GROUPWAREDAV","TEXT"=>"APP_GROUPWAREDAV_TEXT"),
	"BACKUPPC"=>array("TITLE"=>"APP_BACKUPPC","TEXT"=>"APP_BACKUPPC_TEXT"),
	"PIWIGO"=>array("TITLE"=>"APP_PIWIGO","TEXT"=>"APP_PIWIGO_TEXT"),
	"SQUID_STATS"=>array("TITLE"=>"SQUID_STATS","TEXT"=>"SQUID_STATS_TEXT"),
	"ARTICA_ADM"=>array("TITLE"=>"ARTICA_ADM","TEXT"=>"ARTICA_ADM_TEXT"),
	"ARTICA_REGISTER_OU"=>array("TITLE"=>"ARTICA_REGISTER_OU","TEXT"=>"ARTICA_REGISTER_OU_TEXT"),
	"ARTICA_MINIADM"=>array("TITLE"=>"ARTICA_MINIADM","TEXT"=>"ARTICA_MINIADM_TEXT"),
	"EYEOS"=>array("TITLE"=>"APP_EYEOS","TEXT"=>"APP_EYEOS_TEXT"),
	"Z-PUSH"=>array("TITLE"=>"APP_Z_PUSH","TEXT"=>"APP_Z_PUSH_TEXT"),
	"POWERADMIN"=>array("TITLE"=>"APP_POWERADMIN","TEXT"=>"APP_POWERADMIN_TEXT"),
	"PIWIK"=>array("TITLE"=>"APP_PIWIK","TEXT"=>"APP_PIWIK_TEXT"),
	"WORDPRESS"=>array("TITLE"=>"APP_WORDPRESS","TEXT"=>"APP_WORDPRESS_TEXT"),
	"PROXY_CATS"=>array("TITLE"=>"APP_PROXY_CATS","TEXT"=>"APP_PROXY_CATS_TEXT"),
	"DOTCLEAR"=>array("TITLE"=>"APP_DOTCLEAR","TEXT"=>"APP_DOTCLEAR_ABOUT"),
	"CONCRETE5"=>array("TITLE"=>"APP_CONCRETE5","TEXT"=>"APP_CONCRETE5_TXT"),
	"ARTICA_ADMPRXY"=>array("TITLE"=>"ARTICA_ADMPRXY","TEXT"=>"ARTICA_ADMPRXY_TXT"),
	"ARKEIA"=>array("TITLE"=>"APP_ARKEIA","TEXT"=>"APP_ARKEIA_TXT"),
	"KLMS"=>array("TITLE"=>"APP_KLMS","TEXT"=>"APP_KLMS_TXT"),
	"XAPIAN"=>array("TITLE"=>"Xapian Desktop","TEXT"=>"APP_XAPIAN_FREEWEB_TEXT"),
	"ANDROWEB"=>array("TITLE"=>"WebStats Tablet","TEXT"=>"APP_WEBSTATS_TABLET_TEXT"),
	"MAILMAN"=>array("TITLE"=>"APP_MAILMAN","TEXT"=>"manage_distribution_lists"),
	"PIWIGO"=>array("TITLE"=>"APP_PIWIGO","TEXT"=>"APP_PIWIGO_TEXT"),
	"MAILHOSTING"=>array("TITLE"=>"WWW_MAILHOSTING","TEXT"=>"WWW_MAILHOSTING_TEXT"),
	"OWNCLOUD"=>array("TITLE"=>"APP_OWNCLOUD","TEXT"=>"APP_OWNCLOUD_TEXT"),
	"SPLASHSQUID"=>array("TITLE"=>"APP_SPLASHSQUID","TEXT"=>"APP_SPLASHSQUID_TEXT"),
	"ARTICA_PRXYLOGS"=>array("TITLE"=>"APP_ARTICA_PRXYLOGS","TEXT"=>"APP_ARTICA_PRXYLOGS_TEXT"),
	"ERRSQUID"=>array("TITLE"=>"APP_ERRSQUID","TEXT"=>"APP_ERRSQUID_TEXT"),
	
	"UPDATEUTILITY"=>array("TITLE"=>"UpdateUtility","TEXT"=>"UpdateUtilityWWText"),
	"UFDBGUARD"=>array("TITLE"=>"UFDBGUARD_FREEWEB","TEXT"=>"UFDBGUARD_FREEWEB_TEXT"),
	"WEBDAVFREE"=>array("TITLE"=>"WEBDAVFREE","TEXT"=>"WEBDAVFREE_TEXT"),
	"APP_FILEZ_WEB"=>array("TITLE"=>"APP_FILEZ_WEB","TEXT"=>"APP_FILEZ_WEB_TEXT"),
	"CHILLI"=>array("TITLE"=>"APP_CHILLIWEB","TEXT"=>"APP_APP_CHILLIWEB_TEXT"),
	"WPADDYN"=>array("TITLE"=>"APP_WPADDYN","TEXT"=>"APP_WPADDYN_TEXT"),
	
	
	);
	
	function __construct($ou=null){
		if($ou<>null){
			writelogs("Loading organization $ou",__CLASS__.'/'.__FUNCTION__,__FILE__);
			$this->ou=$ou;
			$this->BuildRoot();
		}
		
	}
	
	
function listOfAvailableServices($freeweb=false){
	$user=new usersMenus();
	$sock=new sockets();
	$AllowInternetUsersCreateOrg=$sock->GET_INFO("AllowInternetUsersCreateOrg");
	$ApacheDisableModDavFS=$sock->GET_INFO("ApacheDisableModDavFS");
	$FreeWebEnableModFcgid=$sock->GET_INFO("FreeWebEnableModFcgid");
	if(!is_numeric($FreeWebEnableModFcgid)){$FreeWebEnableModFcgid=0;}
	if(!is_numeric($ApacheDisableModDavFS)){$ApacheDisableModDavFS=0;}	
	$array[]="{select}";
	if(!$freeweb){
		
		if($user->LMB_LUNDIMATIN_INSTALLED){$array["LMB"]="{APP_LMB}";}
		if($user->JOOMLA_INSTALLED){$array["JOOMLA"]="{APP_JOOMLA}";}
		
		if($user->roundcube_installed){$array["ROUNDCUBE"]="{APP_ROUNDCUBE}";}
		
		if($user->OPENGOO_INSTALLED){$array["OPENGOO"]="{APP_OPENGOO}";}
		if($user->GROUPOFFICE_INSTALLED){$array["GROUPOFFICE"]="{APP_GROUPOFFICE}";}
		if($user->APACHE_MODE_WEBDAV){$array["WEBDAV"]="{APP_GROUPWAREDAV}";}
		if($user->ZARAFA_INSTALLED){$array["ZARAFA"]="{APP_ZARAFA}";$array["ZARAFA_MOBILE"]="{APP_ZARAFA_MOBILE_ACCESS}";}
		
		
		if($user->PIWIGO_INSTALLED){$array["PIWIGO"]="{APP_PIWIGO}";}
		if($user->SQUID_INSTALLED){$array["SQUID_STATS"]="{SQUID_STATS}";}
		
		
	}else{
		if($user->ZARAFA_INSTALLED){
			$array["ZARAFA"]="{APP_ZARAFA}";
			if($user->ZARAFA_WEBACCESS_MOBILE_INSTALLED){$array["ZARAFA_MOBILE"]="{APP_ZARAFA_MOBILE_ACCESS}";}
			if($user->Z_PUSH_INSTALLED){$array["Z-PUSH"]="{APP_Z_PUSH}";}
			if($user->ZARAFA_WEBAPP_INSTALLED){$array["WEBAPP"]="{APP_ZARAFA_WEBAPP}";}
		}
		if($user->CONCRETE5_INSTALLED){$array["CONCRETE5"]="{APP_CONCRETE5}";}
		if($user->APP_EYEOS_INSTALLED){$array["EYEOS"]="{APP_EYEOS}";}
		if($user->AsSystemAdministrator){if($user->POWERADMIN_INSTALLED){$array["POWERADMIN"]="{APP_POWERADMIN}";}$array["ARTICA_ADM"]="{APP_ARTICA_ADM}";}
		if($AllowInternetUsersCreateOrg==1){if($user->AsSystemAdministrator){$array["ARTICA_REGISTER_OU"]="{ARTICA_REGISTER_OU}";}}
		$array["ARTICA_MINIADM"]="{ARTICA_MINIADM}";
		$array["MAILHOSTING"]="{WWW_MAILHOSTING}";
		
		if($user->JOOMLA17_INSTALLED){$array["JOOMLA17"]="{APP_JOOMLA17}";}
		if($user->DRUPAL7_INSTALLED){$array["DRUPAL"]="{APP_DRUPAL}";}
		if($user->GROUPOFFICE_INSTALLED){$array["GROUPOFFICE"]="{APP_GROUPOFFICE}";}
		if($user->PIWIK_INSTALLED){$array["PIWIK"]="{APP_PIWIK}";}
		if($user->LMB_LUNDIMATIN_INSTALLED){ $array["LMB"]="{APP_LMB}";}
		if($user->WORDPRESS_INSTALLED){ $array["WORDPRESS"]="{APP_WORDPRESS}";}
		if($user->roundcube_installed){$array["ROUNDCUBE"]="{APP_ROUNDCUBE}";}
		
		if($user->APP_ARKEIA_INSTALLED){$array["ARKEIA"]="{APP_ARKEIA}";}
		if($user->SUGARCRM_INSTALLED){$array["SUGAR"]="{APP_SUGARCRM}";}
		$sock=new sockets();
		$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
		if($EnableWebProxyStatsAppliance==1){$array["ANDROWEB"]="WebStats Tablet";}
		if($user->SQUID_INSTALLED){
			$array["ARTICA_PRXYLOGS"]="{APP_ARTICA_PRXYLOGS}";
			
			$array["WPADDYN"]="{APP_WPADDYN}";
			//$array["ANDROWEB"]="WebStats Tablet";
			
			
			
			
			$SquidEnableISPMode=$sock->GET_INFO("SquidEnableISPMode");
			$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
			$squidEnableRemoteStatistics=$sock->GET_INFO("squidEnableRemoteStatistics");
			$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
			if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
			if(!is_numeric($squidEnableRemoteStatistics)){$squidEnableRemoteStatistics=0;}
			if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
			if($SquidEnableISPMode==1){$array["ARTICA_ADMPRXY"]="{ARTICA_ADMPRXY}";}
			if($EnableSargGenerator==1){$array["SARG"]="{APP_SARG}";}
			if($squidEnableRemoteStatistics==1){unset($array["ANDROWEB"]);}
			if($DisableArticaProxyStatistics==1){unset($array["ANDROWEB"]);}
		}
		if($user->APP_FILEZ_WEB){$array["APP_FILEZ_WEB"]="{APP_FILEZ_WEB}";}
		if($user->POSTFIX_INSTALLED){if($user->KLMS_WEB_INSTALLED){$array["KLMS"]="{APP_KLMS}";}}
		if($user->XAPIAN_PHP_INSTALLED){$array["XAPIAN"]="Xapian Desktop";}
		if($user->PIWIGO_INSTALLED){$array["PIWIGO"]="{APP_PIWIGO}";}
		if($user->OWNCLOUD_INSTALLED){$array["OWNCLOUD"]="{APP_OWNCLOUD}";}
		
		$array["ERRSQUID"]="{APP_ERRSQUID}";
		if($user->UPDATE_UTILITYV2_INSTALLED){$array["UPDATEUTILITY"]="UpdateUtility";}
		if($ApacheDisableModDavFS==0){
			if($user->APACHE_MODE_WEBDAV){$array["WEBDAVFREE"]="{WEBDAVFREE}";}
		}
		
		
		
	}
	//$array["ARTICA_USR"]="{APP_ARTICA_USR}";
	return $array;
}	
	
	function LoadHost($ou,$host){
		$ldap=new clladp();
		$dn="ou=www,ou=$ou,dc=organizations,$ldap->suffix";
		$attrs=array();
		$sr = @ldap_list($ldap->ldap_connection,$dn,"(&(objectclass=apacheConfig)(apacheServerName=$host))",$attrs);
		if ($sr) {
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			for($i=0;$i<$hash["count"];$i++){
				$LoadVhosts["wwwservertype"]=$hash[$i]["wwwservertype"][0];
				$LoadVhosts["apacheservername"]=$hash[$i]["apacheservername"][0];
				$LoadVhosts["wwwmysqluser"]=$hash[$i]["wwwmysqluser"][0];
				$LoadVhosts["wwwmysqlpassword"]=$hash[$i]["wwwmysqlpassword"][0];
				$LoadVhosts["wwwappliuser"]=$hash[$i]["wwwappliuser"][0];
				$LoadVhosts["wwwapplipassword"]=$hash[$i]["wwwapplipassword"][0];
				$LoadVhosts["wwwsslmode"]=$hash[$i]["wwwsslmode"][0];
				$LoadVhosts["WWWMultiSMTPSender"]=$hash[$i][strtolower("WWWMultiSMTPSender")][0];
				$LoadVhosts["WWWEnableAddressBook"]=$hash[$i][strtolower("WWWEnableAddressBook")][0];
				}
		}
		
		return $LoadVhosts;
	}
	
	function SearchHosts($servername){
		$ldap=new clladp();
		$dn="dc=organizations,$ldap->suffix";
		$attrs=array();
		$sr = @ldap_search($ldap->ldap_connection,$dn,"(&(objectclass=apacheConfig)(apacheServerName=$servername))",$attrs);
		if ($sr) {
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			for($i=0;$i<$hash["count"];$i++){
				$dn=$hash[$i]["dn"];
				$LoadVhosts["wwwservertype"]=$hash[$i]["wwwservertype"][0];
				$LoadVhosts["apacheservername"]=$hash[$i]["apacheservername"][0];
				$LoadVhosts["wwwmysqluser"]=$hash[$i]["wwwmysqluser"][0];
				$LoadVhosts["wwwmysqlpassword"]=$hash[$i]["wwwmysqlpassword"][0];
				$LoadVhosts["wwwappliuser"]=$hash[$i]["wwwappliuser"][0];
				$LoadVhosts["wwwapplipassword"]=$hash[$i]["wwwapplipassword"][0];
				$LoadVhosts["wwwsslmode"]=$hash[$i]["wwwsslmode"][0];
				$LoadVhosts["WWWMultiSMTPSender"]=$hash[$i][strtolower("WWWMultiSMTPSender")][0];
				$LoadVhosts["WWWEnableAddressBook"]=$hash[$i][strtolower("WWWEnableAddressBook")][0];
				
				
				
				$LoadVhosts["dn"]=$hash[$i]["dn"];
				$LoadVhosts["apacheDocumentRoot"]=$hash[$i][strtolower("apacheDocumentRoot")][0];
				if(preg_match("#ou=www,ou=(.+?),dc=organizations#",$dn,$re) ){$LoadVhosts["OU"]=$re[1];}
			
				}
		}
		
		return $LoadVhosts;
		
	}
	
	function AllVhosts(){
		$ldap=new clladp();
		$dn="dc=organizations,$ldap->suffix";
		$attrs=array("apacheservername");
		$sr = @ldap_search($ldap->ldap_connection,$dn,'(&(objectclass=apacheConfig)(apacheServerName=*))',$attrs);
		if ($sr) {
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
				for($i=0;$i<$hash["count"];$i++){
				$webserver=$hash[$i]["apacheservername"][0];
				$array[$webserver]=$this->SearchHosts($webserver);
			}
		}
		return $array;
	}
	
	
	function LoadVhosts($ou){
		$ldap=new clladp();
		$dn="ou=www,ou=$ou,dc=organizations,$ldap->suffix";
		$attrs=array();
		$sr = @ldap_list($ldap->ldap_connection,$dn,'(&(objectclass=apacheConfig)(apacheServerName=*))',$attrs);
		if ($sr) {
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			for($i=0;$i<$hash["count"];$i++){
				
				$LoadVhosts[$hash[$i]["apacheservername"][0]]=$hash[$i]["wwwservertype"][0];
			}
		}
		
		if(!is_array($LoadVhosts)){return array();}
		return $LoadVhosts;
	}
	
	function LoadVhostsType($ou){
		$ldap=new clladp();
		$dn="ou=www,ou=$ou,dc=organizations,$ldap->suffix";
		$attrs=array();
		$sr = @ldap_list($ldap->ldap_connection,$dn,'(&(objectclass=apacheConfig)(apacheServerName=*))',$attrs);
		if ($sr) {
			$hash=ldap_get_entries($ldap->ldap_connection,$sr);
			for($i=0;$i<$hash["count"];$i++){
				$array[$hash[$i]["wwwservertype"][0]]=true;
				
			}
		}
		
		if(!is_array($array)){
			writelogs("No vhosts",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return array();}
		return $array;
	}	
	
	
	public function BuildRoot(){
		$ldap=new clladp();
		$up=array();
		$dn="ou=www,ou=$this->ou,dc=organizations,$ldap->suffix";
		writelogs("Building dn $dn",__CLASS__.'/'.__FUNCTION__,__FILE__);
		if(!$ldap->ExistsDN($dn)){
			$up["objectClass"][]="top";
			$up["objectClass"][]="organizationalUnit";
			$up["ou"][]="www";
			$ldap->ldap_add($dn,$up);
		}
		
	}
	
	function Addhost($servername,$servertype){
		$ldap=new clladp();
		$upd=array();
		$dn="apacheServerName=$servername,ou=www,ou=$this->ou,dc=organizations,$ldap->suffix";
		writelogs("Building dn $dn WWWMultiSMTPSender=$this->WWWMultiSMTPSender",__CLASS__.'/'.__FUNCTION__,__FILE__);
		
			if(is_numeric($this->WWWSSLMode)){
				if($this->WWWSSLMode==1){$this->WWWSSLMode="TRUE";}else{$this->WWWSSLMode="FALSE";}
			}		
		
		if(!$ldap->ExistsDN($dn)){
			$upd["objectClass"][]="top";
			$upd["objectClass"][]="apacheConfig";
			$upd["objectClass"][]="ApacheArtica";
			$upd["apacheServerName"][]="$servername";
			$upd["apacheDocumentRoot"][]="/usr/share/artica-groupware/domains/$servername";
			$upd["WWWServerType"][]=$servertype;
			$upd["WWWSSLMode"][]=$this->WWWSSLMode;
			
			if($this->WWWMysqlUser<>null){$upd["WWWMysqlUser"][]=$this->WWWMysqlUser;}
			if($this->WWWMysqlPassword<>null){$upd["WWWMysqlPassword"][]=$this->WWWMysqlPassword;}
			if($this->WWWAppliUser<>null){$upd["WWWAppliUser"][]=$this->WWWAppliUser;}
			if($this->WWWAppliPassword<>null){$upd["WWWAppliPassword"][]=$this->WWWAppliPassword;}
			if($this->WWWMultiSMTPSender<>null){$upd["WWWMultiSMTPSender"][]=$this->WWWMultiSMTPSender;}
			if($this->WWWEnableAddressBook<>null){$upd["WWWEnableAddressBook"][]=$this->WWWEnableAddressBook;}
			if(!$ldap->ldap_add($dn,$upd)){
				echo $ldap->ldap_last_error;
				return false;		
			}
		}else{
			$upd["WWWServerType"][]=$servertype;
			$upd["WWWSSLMode"][]=$this->WWWSSLMode;
			if($this->WWWMysqlUser<>null){$upd["WWWMysqlUser"][]=$this->WWWMysqlUser;}
			if($this->WWWMysqlPassword<>null){$upd["WWWMysqlPassword"][]=$this->WWWMysqlPassword;}
			if($this->WWWAppliUser<>null){$upd["WWWAppliUser"][]=$this->WWWAppliUser;}
			if($this->WWWAppliPassword<>null){$upd["WWWAppliPassword"][]=$this->WWWAppliPassword;}
			if($this->WWWMultiSMTPSender<>null){$upd["WWWMultiSMTPSender"][]=$this->WWWMultiSMTPSender;}
			if($this->WWWEnableAddressBook<>null){$upd["WWWEnableAddressBook"][]=$this->WWWEnableAddressBook;}
			

			
			if(!$ldap->Ldap_modify($dn,$upd)){
				echo $ldap->ldap_last_error;
				return false;
			}
		}
		
		
		
		
		if($this->apachedomainname<>null){
			if($this->apacheIPAddress<>null){
				$pdns=new pdns($this->apachedomainname);
				$netbiosname=str_replace(".$this->apachedomainname","",$servername);
				writelogs("Try adding in PowerDNS server:$netbiosname on domain($this->apachedomainname) IP addr:$this->apacheIPAddress",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$pdns->EditIPName($netbiosname,$this->apacheIPAddress,"A",null);
			}
		}
		
	$sock=new sockets();
		
	$sock->getFrameWork("cmd.php?install-web-service-unique=$servername");
	$sock->getFrameWork('cmd.php?build-vhosts=yes');
	return true;
	}
	
	
	
	
}


class roundcube_globaladdressbook{
	var $enabled=0;
	var $vhosts=null;
	var $readonly=0;
	var $allowgroups=0;
	var $admins=array();
	
	function roundcube_globaladdressbook($vhost){
			$this->vhosts=$vhost;
			$sql="SELECT * FROM roundcube_globaladdressbook WHERE hostname='$vhost'";
			$q=new mysql();
			$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
			$this->enabled=$ligne["enabled"];
			$array=unserialize(base64_decode($ligne["groups"]));
        if(!is_array($array)){$array=array();}
			$this->readonly=$array["CONFIG"]["readonly"];
			$this->allowgroups=$array["CONFIG"]["allowgroups"];
			$this->admins=unserialize(base64_decode($ligne["admins"]));
		
	}
	
	function Save(){
		if($this->readonly==null){$this->readonly=0;}
		$array["CONFIG"]["readonly"]=$this->readonly;
		$array["CONFIG"]["allowgroups"]=$this->allowgroups;
		
		$group=base64_encode(serialize($array));
		$admins=base64_encode(serialize($this->admins));
		
		$sql="SELECT hostname FROM roundcube_globaladdressbook WHERE hostname='$this->vhosts'";
		$q=new mysql();
		$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		if($ligne["hostname"]==null){
			$sql="INSERT INTO roundcube_globaladdressbook(`hostname`,`enabled`,`groups`,`admins`) VALUES('$this->vhosts','$this->enabled','$group','$admins')";
		}else{
			$sql="UPDATE roundcube_globaladdressbook SET 
				`enabled`='$this->enabled',
				`groups`='$group',
				`admins`='$admins'
				WHERE hostname='$this->vhosts'";
		}
		
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n\n".$sql."\n\n";return;}
		if($this->vhosts=="MAIN_INSTANCE"){
			$r=new roundcube();
			$r->Save();
		}else{
			$sock=new sockets();
			$sock->getFrameWork("cmd.php?install-web-services=yes");
		}
		
		
	}
	
	function BuildConfig(){
		if($this->readonly==1){$readonly="true";}else{$readonly="false";}
		if($this->allowgroups==1){$allowgroups="true";}else{$allowgroups="false";}
		if($this->vhosts=="MAIN_INSTANCE"){$globaladdressbook_user="global_addressbook@MAIN_INSTANCE";}else{$globaladdressbook_user="global_addressbook@$this->vhosts";}
		
		
		$f[]="<?php";
		$f[]="\$rcmail_config['globaladdressbook_user'] = '$globaladdressbook_user';";
		$f[]="\$rcmail_config['globaladdressbook_readonly'] = $readonly;";
		$f[]="\$rcmail_config['globaladdressbook_groups'] = $allowgroups;";
		if(!is_array($this->admins)){return;}
		reset($this->admins);
		
		while (list ($num, $member) = each ($this->admins) ){
			if($member==null){continue;}
			$m[]="'$member'";
			
		}
		$members="array(".@implode(",",$m).");";
		$f[]="\$rcmail_config['globaladdressbook_admin'] = $members";
		$f[]="\$rcmail_config['globaladdressbook_autocomplete'] = true;";
		$f[]="?>";
		
		return @implode("\n",$f);
	}
	
	
	
	
	
}

?>