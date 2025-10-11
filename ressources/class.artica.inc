<?php

if(!is_file(dirname($_SERVER['SCRIPT_FILENAME'])."/ressources/class.ldap.inc")){include_once(dirname(__FILE__) . '/class.ldap.inc');}else{include_once(dirname($_SERVER['SCRIPT_FILENAME'])."/ressources/class.ldap.inc");}
include_once(dirname(__FILE__) . '/class.sockets.inc');
include_once(dirname(__FILE__). '/class.ini.inc');
include_once(dirname(__FILE__). '/class.sockets.inc');
if(isset($GLOBALS["CLASS_SOCKETS"])){$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}


class artica_general{
	var $EnableGroups="no";
	var $RelayType="mail";
	var $_params;
	var $ArticaFilterEnabled;
	var $MailFromdEnabled;
	var $ArticaProxyServerName;
	var $ArticaProxyServerPort;
	var $ArticaProxyServerUsername;
	var $ArticaProxyServerUserPassword;
	var $ArticaProxyServerEnabled;
	var $ArticaCompiledProxyUri;
	var $ArticaVersion;
	var $KasxFilterEnabled;
	var $MysqlMaxEventsLogs;
	var $ApacheArticaEnabled;
	var $ApacheConfig;
	var $lighttpConfig;	

	var $ArticaFirstWizard=0;
	var $MysqlAdminAccount;
	var $ClamavMilterEnabled;
	var $SpamAssMilterEnabled=0;
	var $spfmilterEnabled=0;
	var $EnableSyslogMysql=1;
	var $MimeDefangEnabled=0;
	var $DkimFilterEnabled;
	var $ArticaUsbBackupKeyID="NONE";
	var $RoundCubeLightHTTPD;
	var $RoundCubeHTTPEngineEnabled=0;
	var $RoundCubeArticaConfigurationFile;
	var $RoundCubeConfigurationFile;
	var $EnableMonitorix;
	var $LighttpdUseLdap;
	var $EnableFetchmail;
	var $EnableFDMFetch;
	var $MasterCFEnabled;
	var $P3ScanEnabled;
	var $SmtpNotificationConfig;
	var $MaxTempLogFilesDay;
	var $ArticaPerformancesSettings;
	var $sTunnel4enabled;
	var $EnableMilterBogom;
	var $EnableMysqlFeatures;
	var $MysqlServerName;
	var $EnableVirtualDomainsInMailBoxes;
	var $EnableMilterSpyDaemon=0;
	var $EnableAmavisDaemon=0;
	var $EnableAmavisBackup=0;
	var $kavmilterEnable=0;
	var $EnableRemoteAddressBook=0;
	var $EnablePerUserRemoteAddressBook=0;
	var $EnableArticaAsGateway=0;
	var $EnableDHCPServer=0;
	var $ArticaBackupEnabled;
	var $MilterGreyListEnabled=0;
	var $PureFtpdEnabled;
	var $sock;
	var $ldapClass;
	
	function __construct($ldapClass=null){
		$CurrentPageName=CurrentPageName();
		$file=null;
		if(!is_a($ldapClass,"clladp")){$this->ldapClass=new clladp();}else{$this->ldapClass=$ldapClass;}
		$this->sock=new sockets();
		$this->LoadOnDisk();
		if($CurrentPageName=="logon.php"){error_log("[{$_SESSION["uid"]}]::$CurrentPageName:: LoadOnDisk() done.. ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		if($CurrentPageName=="logon.php"){error_log("[{$_SESSION["uid"]}]::$CurrentPageName:: ->lOADING ArticaDatas()");}
		$hash=$this->ldapClass->ArticaDatas();
		if($CurrentPageName=="logon.php"){error_log("[{$_SESSION["uid"]}]::$CurrentPageName:: ->ArticaDatas() done.. ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		$this->LoadProxySettings();

		if(isset($hash["ArticaGlobalSettings"])){$file=$hash["ArticaGlobalSettings"];}
		if(isset($hash["ArticaFilterEnabled"])){$this->ArticaFilterEnabled=$hash["ArticaFilterEnabled"];}

		
		if($CurrentPageName=="logon.php"){error_log("[{$_SESSION["uid"]}]::$CurrentPageName:: ->ldap_close() done.. ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		
		if($file==null){return null;}
		$ini=new Bs_IniHandler();
		$ini->loadString($file);
		$this->_params=$ini->_params;
		if($this->_params["groups"]["enable_groups_behavior"]<>null){$this->EnableGroups=$this->_params["groups"]["enable_groups_behavior"];}
		if($this->_params["Relay"]["RelayType"]<>null){$this->RelayType=$this->_params["Relay"]["RelayType"];}
		
		
	}
	
	
	function LoadOnDisk(){

			$this->ArticaVersion=@file_get_contents("/usr/share/artica-posfix/VERSION");
			$this->RoundCubeHTTPEngineEnabled=$this->sock->GET_INFO("RoundCubeHTTPEngineEnabled");
			$this->EnableAmavisDaemon=$this->sock->GET_INFO("EnableAmavisDaemon");			
			$this->RoundCubeLightHTTPD=$this->sock->GET_INFO("RoundCubeLightHTTPD");
			$this->RoundCubeConfigurationFile=$this->sock->GET_INFO("RoundCubeConfigurationFile");
			$this->RoundCubeArticaConfigurationFile=$this->sock->GET_INFO("RoundCubeArticaConfigurationFile");
			$this->MysqlAdminAccount=$this->sock->MYSQL_INFO('database_admin').":".$this->sock->MYSQL_INFO('database_password');
			$this->MysqlServerName=$this->sock->MYSQL_INFO('mysql_server');
			$this->MimeDefangEnabled=$this->sock->GET_INFO("MimeDefangEnabled");
			$this->EnableAmavisBackup=$this->sock->GET_INFO("EnableAmavisBackup");
			$this->kavmilterEnable=$this->sock->GET_INFO("kavmilterEnable");
			$this->MailFromdEnabled=$this->sock->GET_INFO("MailFromdEnabled");
			$this->EnableRemoteAddressBook=$this->sock->GET_INFO("EnableRemoteAddressBook");
			$this->EnableFetchmail=$this->sock->GET_INFO("EnableFetchmail");
			$this->SpamAssMilterEnabled=$this->sock->GET_INFO("SpamAssMilterEnabled");
			$this->EnableArticaAsGateway=$this->sock->GET_INFO("EnableArticaAsGateway");
			$this->EnableDHCPServer=$this->sock->GET_INFO("EnableDHCPServer");
			$this->EnableMilterSpyDaemon=$this->sock->GET_INFO("EnableMilterSpyDaemon");
			$this->ClamavMilterEnabled=$this->sock->GET_INFO("ClamavMilterEnabled");
			$this->ArticaBackupEnabled=$this->sock->GET_INFO("ArticaBackupEnabled");
			$this->EnableVirtualDomainsInMailBoxes=$this->sock->GET_INFO("EnableVirtualDomainsInMailBoxes");
			$this->MilterGreyListEnabled=$this->sock->GET_INFO("MilterGreyListEnabled");
			$this->KasxFilterEnabled=$this->sock->GET_INFO("KasxFilterEnabled");
			$this->PureFtpdEnabled=$this->sock->GET_INFO("PureFtpdEnabled");
			$this->EnablePerUserRemoteAddressBook=$this->sock->GET_INFO("EnablePerUserRemoteAddressBook");
			$this->sTunnel4enabled=$this->sock->GET_INFO("sTunnel4enabled");
			$this->MasterCFEnabled=$this->sock->GET_INFO("MasterCFEnabled");
			if($this->MysqlServerName==null){$this->MysqlServerName='127.0.0.1';}
			
			
	}
	

	
	

		
	function DynList(){
		$dn="cn=dynlist,cn=artica";
		
		
	}
	

	
	function LoadProxySettings(){
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		$CurrentPageName=CurrentPageName();
		$datas=$sock->GET_INFO("ArticaProxySettings");
		if($CurrentPageName=="logon.php"){error_log("[{$_SESSION["uid"]}]::-ArticaProxySettings=". strlen($datas) ." bytes ".__FUNCTION__.' in '.__FILE__. " in line ". __LINE__);}
		if(trim($datas)<>null){
			$ini->loadString($datas);
			$this->ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
			$this->ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
			$this->ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
			$this->ArticaProxyServerUsername=$ini->_params["PROXY"]["ArticaProxyServerUsername"];
			$this->ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
			$this->ArticaCompiledProxyUri=$ini->_params["PROXY"]["ArticaCompiledProxyUri"];
		}
	  	
		}
	
	function SaveProxySettings(){
		$conf="[PROXY]\n";
		$conf=$conf."ArticaProxyServerName=$this->ArticaProxyServerName\n";
		$conf=$conf."ArticaProxyServerPort=$this->ArticaProxyServerPort\n";
		$conf=$conf."ArticaProxyServerUsername=$this->ArticaProxyServerUsername\n";
		$conf=$conf."ArticaProxyServerUserPassword=$this->ArticaProxyServerUserPassword\n";
		$conf=$conf."ArticaProxyServerEnabled=$this->ArticaProxyServerEnabled\n";
		
		if(trim($this->ArticaProxyServerUserPassword)<>null){$p=":$this->ArticaProxyServerUserPassword";}
		if(trim($this->ArticaProxyServerUsername)<>null){$at="$this->ArticaProxyServerUsername$p@";}
		if(trim($this->ArticaProxyServerPort)<>null){$port=":$this->ArticaProxyServerPort";}
		
		$uri="http://$at$this->ArticaProxyServerName$port";
		if($this->ArticaProxyServerEnabled=='yes'){
			echo $uri."\n----------------------------------------------\n";
		}
		$conf=$conf."ArticaCompiledProxyUri=$uri\n";
		$sock=new sockets();
		$sock->SaveConfigFile($conf,"ArticaProxySettings");
		$sock->getFrameWork("cmd.php?compile-proxy=yes");
		$EnableStreamCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStreamCache"));
		if($EnableStreamCache==1){$sock->getFrameWork("squid.php?videocache-restart=yes"); }
		
	}
	
	function SaveSettings2(){
		if(!function_exists("ldap_connect")){return array();}
		if($this->ldapClass->suffix==null){$this->ldapClass=new clladp();}
		$upd=array();
		$dn="cn=artica-settings2,cn=artica,{$this->ldapClass->suffix}";
		$upd["MaxTempLogFilesDay"][0]=$this->MaxTempLogFilesDay;
		$upd["ArticaPerformancesSettings"][0]=$this->ArticaPerformancesSettings;
		if(!$this->ldapClass->Ldap_modify($dn,$upd)){
			echo "Save Artica LDAP Settings ".__FUNCTION__." failed\n$this->ldapClass->ldap_last_error";
		}
		
		
	}
	
	function SaveMysqlSettings(){
		
		
		
		//Fetchmail or FDM ??
		if ($this->EnableFetchmail==1){$this->EnableFDMFetch=0;}
		if ($this->EnableFDMFetch==1){
			writelogs("Disable Fetchmail because EnableFDMFetch is activated",__CLASS__.'/'.__FUNCTION__,__FILE__);
			$this->EnableFetchmail=0;}
	
		if(preg_match('#(.+?):(.*)#',$this->MysqlAdminAccount,$re)){
			$rootm=$re[1];
			$pwd=$re[2];
			if($this->MysqlServerName==null){$this->MysqlServerName='127.0.0.1';}
			$arrayMysqlinfos=array("USER"=>$rootm,"PASSWORD"=>$pwd,"SERVER"=>$this->MysqlServerName);
			$cmd=base64_encode(serialize($arrayMysqlinfos));
			$sock=new sockets();
			$sock->getFrameWork("cmd.php?change-mysql-params=$cmd");
			
		}
		
	//define rules when amavis is enabled:

	  
	
		if($this->EnableAmavisDaemon==1){
			writelogs("EnableAmavisDaemon is active, disable spamassassin,clamav,MimeDefang,DKIMFIlter,MilterSpy,MailArchiver",__CLASS__.'/'.__FUNCTION__,__FILE__);
			$this->SpamAssMilterEnabled=0;
			$this->ClamavMilterEnabled=0;
			$this->MimeDefangEnabled=0;
			$this->DkimFilterEnabled=0;
			$this->EnableMilterSpyDaemon=0;
			}
			
		if($this->MimeDefangEnabled==1){
			$this->EnableAmavisDaemon=0;
			$this->ClamavMilterEnabled=0;
			$this->SpamAssMilterEnabled=0;
			$this->DkimFilterEnabled=0;
			}
			
		$sock=new sockets();
		$sock->SET_INFO('MimeDefangEnabled',$this->MimeDefangEnabled);
		
		$sock->SET_INFO('EnableAmavisBackup',$this->EnableAmavisBackup);
		$sock->SET_INFO('kavmilterEnable',$this->kavmilterEnable);
		$sock->SET_INFO('MailFromdEnabled',$this->MailFromdEnabled);
		$sock->SET_INFO('EnableRemoteAddressBook',$this->EnableRemoteAddressBook);
		$sock->SET_INFO('EnablePerUserRemoteAddressBook',$this->EnablePerUserRemoteAddressBook);

		
		$sock->SET_INFO('EnableArticaAsGateway',$this->EnableArticaAsGateway);
		$sock->SET_INFO('EnableDHCPServer',$this->EnableDHCPServer);
		$sock->SET_INFO('EnableFetchmail',$this->EnableFetchmail);
		$sock->SET_INFO('EnableMilterSpyDaemon',$this->EnableMilterSpyDaemon);
		$sock->SET_INFO('ClamavMilterEnabled',$this->ClamavMilterEnabled);
		$sock->SET_INFO('ArticaBackupEnabled',$this->ArticaBackupEnabled);
		$sock->SET_INFO('EnableVirtualDomainsInMailBoxes',$this->EnableVirtualDomainsInMailBoxes);
		$sock->SET_INFO('MilterGreyListEnabled',$this->MilterGreyListEnabled);
		$sock->SET_INFO('KasxFilterEnabled',$this->KasxFilterEnabled);
		$sock->SET_INFO('EnableAmavisDaemon',$this->EnableAmavisDaemon);
		$sock->SET_INFO('sTunnel4enabled',$this->sTunnel4enabled);
		$sock->SET_INFO('MasterCFEnabled',$this->MasterCFEnabled);

		
		if($this->ldapClass->suffix==null){$this->ldapClass=new clladp();}
		$dn="cn=artica-settings,cn=artica,{$this->ldapClass->suffix}";
		$upd=array();
		$upd["MysqlMaxEventsLogs"][0]=$this->MysqlMaxEventsLogs;
		if($this->lighttpConfig<>null){$upd["lighttpConfig"][0]=$this->lighttpConfig;}
		if($this->ApacheConfig<>null){$upd["ApacheConfig"][0]=$this->ApacheConfig;}
		
		$upd["ArticaFirstWizard"][0]=$this->ArticaFirstWizard;
		$upd["spfmilterEnabled"][0]=$this->spfmilterEnabled;
		if(function_exists("artica_get_memory_usage")){artica_get_memory_usage();}
		$upd["EnableSyslogMysql"][0]=$this->EnableSyslogMysql;
		$upd["DkimFilterEnabled"][0]=$this->DkimFilterEnabled;
		
		
		$upd["EnableMonitorix"][0]=$this->EnableMonitorix;
		$upd["LighttpdUseLdap"][0]=$this->LighttpdUseLdap;
		$upd["EnableFDMFetch"][0]=$this->EnableFDMFetch;
		$upd["P3ScanEnabled"][0]=$this->P3ScanEnabled;
		
		
		
		$sock=new sockets();
		$sock->SET_INFO("ArticaUsbBackupKeyID",$this->ArticaUsbBackupKeyID);
		
		$sock->SET_INFO("ApacheArticaEnabled",$this->ApacheArticaEnabled);
		
		
		$upd["EnableMilterBogom"][0]=$this->EnableMilterBogom;	
		writelogs("EnableMysqlFeatures=$this->EnableMysqlFeatures servername=$this->MysqlServerName",__CLASS__.'/'.__FUNCTION__,__FILE__);
		
		$upd["EnableMysqlFeatures"][0]=$this->EnableMysqlFeatures;
		$upd["MysqlServerName"][0]=$this->MysqlServerName;
		$update_array=array();
		
	if(!$this->ldapClass->ExistsDN($dn)){
				$update_array["cn"][]='artica-settings';
				$update_array["objectClass"][]='organizationalRole';
				$update_array["objectClass"][]='ArticaSettings';
				$update_array["objectClass"][]='top';
				if(!$this->ldapClass->ldap_add($dn,$update_array)){
					echo "Building artica-settings branch failed\n{$this->ldapClass->ldap_last_error}";	
				}
	}else{
		writelogs("$dn exists",__CLASS__.'/'.__FUNCTION__,__FILE__);
	}
		
		

	if(!$this->ldapClass->Ldap_modify($dn,$upd)){
		echo "Build LDAP Artica Parametres failed\n{$this->ldapClass->ldap_last_error}\n";
		return null;
	}
		writelogs("synchronizeModules...",__CLASS__.'/'.__FUNCTION__,__FILE__);
		$sock=new sockets();
		$sock->getfile('synchronizeModules');
		//$ldap->ldap_close();
		
	}
	
	
	function Save(){
		$ArticaFilterEnabled=$this->ArticaFilterEnabled;
		$this->SaveMysqlSettings();
		$this->_params["groups"]["enable_groups_behavior"]=$this->EnableGroups;
		$this->_params["Relay"]["RelayType"]=$this->RelayType;
		$ini=new Bs_IniHandler();
		$ini->_params=$this->_params;
		$file=$ini->toString();
		$hash=$this->ldapClass->ArticaDatas();
		$this->SaveMysqlSettings();
		$this->ChangeRelaySettings();
		$this->SaveSettings2();
		$sock=new sockets();
		$sock->getfile("synchronizeModules");
		return true;
		
	}
	
	function ChangeRelaySettings(){
	
		$ArrayOU=$this->ldapClass->hash_get_ou();
		if(!is_array($ArrayOU)){return null;}
		
		if($this->RelayType=='single'){
			writelogs("Relais type is a single relay... Add domains in VirtualDomainsMapsMTA",__FUNCTION__,__FILE__);
			while (list ($num, $val) = each ($ArrayOU) ){
				$this->_Add_VirtualDomainsMapsMTA($val);
			}
		}
	}
	
	function _Add_VirtualDomainsMapsMTA($ou){
		
		$domains=$this->ldapClass->Hash_domains_table($ou);
		if(!is_array($domains)){
			writelogs("No domains for $ou..",__FUNCTION__,__FILE__);
			return null;
			}
			
		while (list ($domain, $val) = each ($domains) ){			
			$this->ldapClass->add_domains_to_VirtualDomainsMapsMTA($ou,$domain);
		}
	}			
	
	
}


class artica{
	var $array_config;
	var $array_ldap_config;
	var $dap;
	var $error=false;

	
	function __construct(){
		$sock=new sockets();
		$confile=$sock->getfile('getMyConf');
		if($sock->error==true){$this->error=true;return null;}
		$ini=new Bs_IniHandler();
		$ini->loadString($confile);
		$this->array_config=$ini->_params;
		if($this->array_config["ARTICA"]["MANAGE_MAILBOXES"]=='yes'){$this->array_config["ARTICA"]["MANAGE_MAILBOXES"]="TRUE";}
		
		$confile=$sock->getfile('getMyLdapConf');
		$ini=new Bs_IniHandler();
		$ini->loadString($confile);
		$this->array_ldap_config=$ini->_params;
		
		}
		
	function Save(){
		$ini=new Bs_IniHandler();
		$ini->_params=$this->array_config;
		$conffile=dirname(__FILE__) . '/conf/artica-postfix.conf';
		$ini->saveFile($conffile);
		$sock=new sockets();
		$sock->getfile('SaveMyConf:'.$conffile);
		}
	function saveLDapSettings(){
		$ini=new Bs_IniHandler();
		$ini->_params=$this->array_ldap_config;
		$conffile=dirname(__FILE__) . '/conf/artica-postfix-ldap.conf';
		$ini->saveFile($conffile);
		$sock=new sockets();
		$sock->getfile('SaveMyConf:'.$conffile);
		}
	
}
?>