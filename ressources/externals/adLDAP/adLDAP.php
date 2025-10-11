<?php
if(!defined(LDAP_OPT_DIAGNOSTIC_MESSAGE)) {
    define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);
}
$GLOBALS["ldapBind"]=false;
/**
 * PHP LDAP CLASS FOR MANIPULATING ACTIVE DIRECTORY 
 * Version 4.0.4
 * 
 * PHP Version 5 with SSL and LDAP support
 * 
 * Written by Scott Barnett, Richard Hyland
 *   email: scott@wiggumworld.com, adldap@richardhyland.com
 *   http://adldap.sourceforge.net/
 * 
 * Copyright (c) 2006-2012 Scott Barnett, Richard Hyland
 * 
 * We'd appreciate any improvements or additions to be submitted back
 * to benefit the entire community :)
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * @category ToolsAndUtilities
 * @package adLDAP
 * @author Scott Barnett, Richard Hyland
 * @copyright (c) 2006-2012 Scott Barnett, Richard Hyland
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPLv2.1
 * @revision $Revision: 169 $
 * @version 4.0.4
 * @link http://adldap.sourceforge.net/
 */

/**
* Main adLDAP class
* 
* Can be initialised using $adldap = new adLDAP();
* 
* Something to keep in mind is that Active Directory is a permissions
* based directory. If you bind as a domain user, you can't fetch as
* much information on other users as you could as a domain admin.
* 
* Before asking questions, please read the Documentation at
* http://adldap.sourceforge.net/wiki/doku.php?id=api
*/
require_once(dirname(__FILE__) . '/collections/adLDAPCollection.php');
require_once(dirname(__FILE__) . '/classes/adLDAPGroups.php');
require_once(dirname(__FILE__) . '/classes/adLDAPUsers.php');
require_once(dirname(__FILE__) . '/classes/adLDAPFolders.php');
require_once(dirname(__FILE__) . '/classes/adLDAPUtils.php');
require_once(dirname(__FILE__) . '/classes/adLDAPContacts.php');
require_once(dirname(__FILE__) . '/classes/adLDAPExchange.php');
require_once(dirname(__FILE__) . '/classes/adLDAPComputers.php');

class adLDAP {
    
    /**
     * Define the different types of account in AD
     */
    const ADLDAP_NORMAL_ACCOUNT = 805306368;
    const ADLDAP_WORKSTATION_TRUST = 805306369;
    const ADLDAP_INTERDOMAIN_TRUST = 805306370;
    const ADLDAP_SECURITY_GLOBAL_GROUP = 268435456;
    const ADLDAP_DISTRIBUTION_GROUP = 268435457;
    const ADLDAP_SECURITY_LOCAL_GROUP = 536870912;
    const ADLDAP_DISTRIBUTION_LOCAL_GROUP = 536870913;
    const ADLDAP_FOLDER = 'OU';
    const ADLDAP_CONTAINER = 'CN';
    
    /**
    * The default port for LDAP non-SSL connections
    */
    const ADLDAP_LDAP_PORT = '389';
    /**
    * The default port for LDAPS SSL connections
    */
    const ADLDAP_LDAPS_PORT = '636';
    
    /**
    * The account suffix for your domain, can be set when the class is invoked
    * 
    * @var string
    */   
	protected $accountSuffix = "";
    
    /**
    * The base dn for your domain
    * 
    * If this is set to null then adLDAP will attempt to obtain this automatically from the rootDSE
    * 
    * @var string
    */
	protected $baseDn = "";
    
    /** 
    * Port used to talk to the domain controllers. 
    *  
    * @var int 
    */ 
    protected $adPort = self::ADLDAP_LDAP_PORT; 
	
    /**
    * Array of domain controllers. Specifiy multiple controllers if you
    * would like the class to balance the LDAP queries amongst multiple servers
    * 
    * @var array
    */
    protected $domainControllers = array();
	
    /**
    * Optional account with higher privileges for searching
    * This should be set to a domain admin account
    * 
    * @var string
    * @var string
    */
	protected $adminUsername = NULL;
    protected $adminPassword = NULL;
    
    /**
    * AD does not return the primary group. http://support.microsoft.com/?kbid=321360
    * This tweak will resolve the real primary group. 
    * Setting to false will fudge "Domain Users" and is much faster. Keep in mind though that if
    * someone's primary group is NOT domain users, this is obviously going to mess up the results
    * 
    * @var bool
    */
	protected $realPrimaryGroup = true;
	
    /**
    * Use SSL (LDAPS), your server needs to be setup, please see
    * http://adldap.sourceforge.net/wiki/doku.php?id=ldap_over_ssl
    * 
    * @var bool
    */
	protected $useSSL = false;
    
    /**
    * Use TLS
    * If you wish to use TLS you should ensure that $useSSL is set to false and vice-versa
    * 
    * @var bool
    */
    protected $useTLS = false;
    
    /**
    * Use SSO  
    * To indicate to adLDAP to reuse password set by the brower through NTLM or Kerberos 
    * 
    * @var bool
    */
    protected $useSSO = false;
    
    /**
    * When querying group memberships, do it recursively 
    * eg. User Fred is a member of Group A, which is a member of Group B, which is a member of Group C
    * user_ingroup("Fred","C") will returns true with this option turned on, false if turned off     
    * 
    * @var bool
    */
	protected $recursiveGroups = true;
	
	// You should not need to edit anything below this line
	//******************************************************************************************
	
	/**
    * Connection and bind default variables
    * 
    * @var mixed
    * @var mixed
    */
	protected $ldapConnection;
	protected $ldapBind;
	private $filename;
	private $LogsTemp;
    
    /**
    * Get the active LDAP Connection
    * 
    * @return false|resource
    */
    public function getLdapConnection(){
        if ($this->ldapConnection) {
            return $this->ldapConnection;   
        }
        return false;
    }
    
    /**
    * Get the bind status
    * 
    * @return bool
    */
    public function getLdapBind(): bool
    {
        if($this->ldapBind){return $this->ldapBind;}
    	if($GLOBALS["VERBOSE"]){
	    	if(function_exists("debug_backtrace")){
	    		$trace=debug_backtrace();
	    		foreach ($trace as $lines){
	    			$file=basename($lines["file"]);
	    			$function=$lines["function"];
	    			$line=$lines["line"];
	    			if($GLOBALS["VERBOSE"]){echo "adLDAP.php:: ldapBind: from  $file/$function/$line".__LINE__."\n";}
	    		}
	    	}
    	}
    	
    	if(!$this->ldapBind){
    		if($GLOBALS["ldapBind"]){
    			if($GLOBALS["VERBOSE"]){echo "adLDAP.php:: ldapBind: False but memory success ".__LINE__."\n";}
    			$this->ldapBind=$GLOBALS["ldapBind"];return $this->ldapBind;
    		}
    		if($GLOBALS["VERBOSE"]){echo "adLDAP.php:: ldapBind: False in line ".__LINE__."\n";}
    		return $this->ldapBind;
    	}
    	
    	
    	if($GLOBALS["VERBOSE"]){echo "adLDAP.php:: ldapBind: True in line ".__LINE__."\n";}
        return $this->ldapBind;
    }
    
    /**
    * Get the current base DN
    * 
    * @return string
    */
    public function getBaseDn(): string
    {
        return $this->baseDn;   
    }
    
    /**
    * The group class
    * 
    * @var adLDAPGroups
    */
    protected $groupClass;
    
    /**
    * Get the group class interface
    * 
    * @return adLDAPGroups
    */
    public function group(): adLDAPGroups
    {
        if (!$this->groupClass) {
            $this->groupClass = new adLDAPGroups($this);
        }   
        return $this->groupClass;
    }
    
    /**
    * The user class
    * 
    * @var adLDAPUsers
    */
    protected $userClass;
    
    /**
    * Get the userclass interface
    * 
    * @return adLDAPUsers
    */
    public function user(): adLDAPUsers{
        if (!$this->userClass) {
        	
        	
        	if($GLOBALS["VERBOSE"]){
        		if($this->getLdapBind()){
        			echo "adLDAP.php:: user(): new adLDAPUsers(of this) getLdapBind() --> success in line ".__LINE__."\n";
        		}else{
        			echo "adLDAP.php:: user(): new adLDAPUsers(of this)getLdapBind() --> Failed in line ".__LINE__."\n";
        		}
        	}
        	
            $this->userClass = new adLDAPUsers($this);
            $this->userClass->adldap->ldapBind=$this->ldapBind;
        }   
        if($GLOBALS["VERBOSE"]){echo "adLDAP.php:: user(): return this userClass in line ".__LINE__."\n";}
        return $this->userClass;
    }
    
    /**
    * The folders class
    * 
    * @var adLDAPFolders
    */
    protected $folderClass;
    
    /**
    * Get the folder class interface
    * 
    * @return adLDAPFolders
    */
    public function folder(): adLDAPFolders
    {
        if (!$this->folderClass) {
            $this->folderClass = new adLDAPFolders($this);
        }   
        return $this->folderClass;
    }
    
    /**
    * The utils class
    * 
    * @var adLDAPUtils
    */
    protected $utilClass;
    
    /**
    * Get the utils class interface
    * 
    * @return adLDAPUtils
    */
    public function utilities(): adLDAPUtils
    {
        if (!$this->utilClass) {
            $this->utilClass = new adLDAPUtils($this);
        }   
        return $this->utilClass;
    }
    
    /**
    * The contacts class
    * 
    * @var adLDAPContacts
    */
    protected $contactClass;
    
    /**
    * Get the contacts class interface
    * 
    * @return adLDAPContacts
    */
    public function contact(): adLDAPContacts
    {
        if (!$this->contactClass) {
            $this->contactClass = new adLDAPContacts($this);
        }   
        return $this->contactClass;
    }
    
    /**
    * The exchange class
    * 
    * @var adLDAPExchange
    */
    protected $exchangeClass;
    
    /**
    * Get the exchange class interface
    * 
    * @return adLDAPExchange
    */
    public function exchange(): adLDAPExchange
    {
        if (!$this->exchangeClass) {
            $this->exchangeClass = new adLDAPExchange($this);
        }   
        return $this->exchangeClass;
    }
    
    /**
    * The computers class
    * 
    * @var adLDAPComputers
    */
    protected $computersClass;
    
    /**
    * Get the computers class interface
    * 
    * @return adLDAPComputers
    */
    public function computer(): adLDAPComputers
    {
        if (!$this->computersClass) {
            $this->computersClass = new adLDAPComputers($this);
        }   
        return $this->computersClass;
    }

    /**
    * Getters and Setters
    */

    /**
     * Set the account suffix
     *
     * @param string $accountSuffix
     * @return void
     */
    public function setAccountSuffix(string $accountSuffix)
    {
          $this->accountSuffix = $accountSuffix;
    }

    /**
    * Get the account suffix
    * 
    * @return string
    */
    public function getAccountSuffix(): string
    {
          return $this->accountSuffix;
    }
    
    /**
    * Set the domain controllers array
    * 
    * @param array $domainControllers
    * @return void
    */
    public function setDomainControllers(array $domainControllers)
    {
          $this->domainControllers = $domainControllers;
    }

    /**
    * Get the list of domain controllers
    * 
    * @return array
     */
    public function getDomainControllers(): array
    {
          return $this->domainControllers;
    }

    /**
     * Sets the port number your domain controller communicates over
     *
     * @param int $adPort
     */
    public function setPort(int $adPort)
    { 
        $this->adPort = $adPort; 
    } 
    
    /**
    * Gets the port number your domain controller communicates over
    * 
    * @return int
    */
    public function getPort() 
    { 
        return $this->adPort; 
    }

    /**
     * Set the username of an account with higher priviledges
     *
     * @param string $adminUsername
     * @return void
     */
    public function setAdminUsername(string $adminUsername)
    {
          $this->adminUsername = $adminUsername;
    }

    /**
    * Get the username of the account with higher priviledges
    * 
    * This will throw an exception for security reasons
    */
    public function getAdminUsername()
    {
          return null;
    }

    /**
     * Set the password of an account with higher priviledges
     *
     * @param string $adminPassword
     * @return void
     */
    public function setAdminPassword(string $adminPassword)
    {
          $this->adminPassword = $adminPassword;
    }

    /**
    * Get the password of the account with higher priviledges
    * 
    * This will throw an exception for security reasons
    */
    public function getAdminPassword(){
          return null;
    }

    /**
     * Set whether to detect the true primary group
     *
     * @param bool $realPrimaryGroup
     * @return void
     */
    public function setRealPrimaryGroup(bool $realPrimaryGroup)
    {
          $this->realPrimaryGroup = $realPrimaryGroup;
    }

    /**
    * Get the real primary group setting
    * 
    * @return bool
    */
    public function getRealPrimaryGroup(): bool
    {
          return $this->realPrimaryGroup;
    }

    /**
     * Set whether to use SSL
     *
     * @param bool $useSSL
     * @return void
     */
    public function setUseSSL(bool $useSSL)
    {
          $this->useSSL = $useSSL;
          // Set the default port correctly 
          if($this->useSSL) { 
            $this->setPort(self::ADLDAP_LDAPS_PORT); 
          }
          else { 
            $this->setPort(self::ADLDAP_LDAP_PORT); 
          } 
    }

    /**
    * Get the SSL setting
    * 
    * @return bool
    */
    public function getUseSSL(): bool
    {
          return $this->useSSL;
    }

    /**
     * Set whether to use TLS
     *
     * @param bool $useTLS
     * @return void
     */
    public function setUseTLS(bool $useTLS)
    {
          $this->useTLS = $useTLS;
    }

    /**
    * Get the TLS setting
    * 
    * @return bool
    */
    public function getUseTLS(): bool
    {
          return $this->useTLS;
    }

    /**
     * Set whether to use SSO
     * Requires ldap_sasl_bind support. Be sure --with-ldap-sasl is used when configuring PHP otherwise this function will be undefined.
     *
     * @param bool $useSSO
     * @return void
     * @throws adLDAPException
     */
    public function setUseSSO(bool $useSSO)
    {
          if ($useSSO === true && !$this->ldapSaslSupported()) {
              throw new adLDAPException('No LDAP SASL support for PHP.  See: http://www.php.net/ldap_sasl_bind');
          }
          $this->useSSO = $useSSO;
    }

    /**
    * Get the SSO setting
    * 
    * @return bool
    */
    public function getUseSSO(): bool
    {
          return $this->useSSO;
    }

    /**
     * Set whether to lookup recursive groups
     *
     * @param bool $recursiveGroups
     * @return void
     */
    public function setRecursiveGroups(bool $recursiveGroups)
    {
          $this->recursiveGroups = $recursiveGroups;
    }

    /**
    * Get the recursive groups setting
    * 
    * @return bool
    */
    public function getRecursiveGroups(): bool
    {
          return $this->recursiveGroups;
    }

    /**
    * Default Constructor
    * 
    * Tries to bind to the AD domain over LDAP or LDAPs
    * 
    * @param array $options Array of options to pass to the constructor
    * @throws Exception - if unable to bind to Domain Controller
    * @return bool
    */
    function __construct($options = array()) {
    	$filename=basename(__FILE__);
    	$this->filename=$filename;
        // You can specifically overide any of the default configuration options setup above
    	if($GLOBALS["VERBOSE"]){echo " ->options -> ". count($options)."\n";}
        if (count($options) > 0) {
        	if($GLOBALS["VERBOSE"]){
        		foreach ($options as $a=>$part){
        			if(!is_array($part)){echo $filename.":".__FUNCTION__.": $a=\"$part\"\n";}
                }
        		reset($options);
        	}
        	
        	
        	
            if (array_key_exists("account_suffix",$options)){ $this->accountSuffix = $options["account_suffix"]; }
            if (array_key_exists("base_dn",$options)){ $this->baseDn = $options["base_dn"]; }
            if (array_key_exists("domain_controllers",$options)){ 
                if (!is_array($options["domain_controllers"])) { 
                    throw new adLDAPException('[domain_controllers] option must be an array');
                }
                $this->domainControllers = $options["domain_controllers"]; 
            }
            if (array_key_exists("admin_username",$options)){ $this->adminUsername = $options["admin_username"]; }
            if (array_key_exists("admin_password",$options)){ $this->adminPassword = $options["admin_password"]; }
            if (array_key_exists("real_primarygroup",$options)){ $this->realPrimaryGroup = $options["real_primarygroup"]; }
            if (array_key_exists("use_ssl",$options)){ $this->setUseSSL($options["use_ssl"]); }
            if (array_key_exists("use_tls",$options)){ $this->useTLS = $options["use_tls"]; }
            if (array_key_exists("recursive_groups",$options)){ $this->recursiveGroups = $options["recursive_groups"]; }
            if (array_key_exists("ad_port",$options)){ $this->setPort($options["ad_port"]); } 
            
            if($this->adminUsername==null){
            	if (array_key_exists("ad_username",$options)){$this->adminUsername = $options["ad_username"];}
            	if (array_key_exists("ad_password",$options)){$this->adminPassword = $options["ad_password"];}
            	
            }
            
            if(substr($this->accountSuffix,0,1)<>"@"){$this->accountSuffix="@$this->accountSuffix";}
            
            
            if (array_key_exists("sso",$options)) { 
                $this->setUseSSO($options["sso"]);
                if (!$this->ldapSaslSupported()) {
                    $this->setUseSSO(false);
                }
            } 
        }
        
        if ($this->ldapSupported() === false) {
            throw new adLDAPException('No LDAP support for PHP.  See: http://www.php.net/ldap');
        }
        $GLOBALS["CLASS_ACTV"]=array();
		if($GLOBALS["VERBOSE"]){echo $filename.":".__FUNCTION__.": this->Connect()\n";}
        $return=$this->connect();
        if($GLOBALS["VERBOSE"]){echo $filename.":".__FUNCTION__.": getLdapBind==".$this->getLdapBind()."\n";}
        return $return;
    }

    /**
    * Default Destructor
    * 
    * Closes the LDAP connection
    * 
    * @return void
    */
    function __destruct() { 
        $this->close(); 
    }

    /**
    * Connects and Binds to the Domain Controller
    * 
    * @return bool
    */

    private function syslog($function,$line): bool
    {
        $base=basename(__FILE__);
        if(!function_exists("openlog")){return false;}
        $prefix="[$base] {$function}(): [L.$line]";
        openlog("external-acl", LOG_PID , LOG_SYSLOG);
        foreach ($this->LogsTemp as $text) {
            syslog(LOG_ERR, "$prefix$text");
        }
        closelog();
        return true;
    }

    public function connect(): bool
    {
        // Connect to the AD/LDAP server as the username/password
       	$filename=basename(__FILE__);
        $this->LogsTemp=array();
        $domainController = $this->randomController();
        if($GLOBALS["VERBOSE"]){echo "$filename:: domainController: $domainController in line ".__LINE__."\n";}
        $this->LogsTemp[]="domainController: $domainController";
        if($this->adPort==636){$this->useSSL=true;}
        
        if ($this->useSSL) {
            if($this->adPort==636) {
                $ldapstring = "ldaps://$domainController";
            }else{
                $ldapstring = "ldaps://$domainController:$this->adPort";
            }
            $this->ldapConnection = ldap_connect($ldapstring);

        } else {
        	if($GLOBALS["VERBOSE"]){echo "$filename:: ldap_connect($domainController,$this->adPort) in line ".__LINE__."\n";}
            if($this->adPort==389) {
                $ldapstring = "ldap://$domainController";
            }else{
                $ldapstring="ldap://$domainController:$this->adPort";
            }
        	$this->ldapConnection = ldap_connect($ldapstring);
        }

        $this->LogsTemp[]="domainController: $ldapstring";

        if(!$this->ldapConnection){
            $this->LogsTemp[]="domainController: $ldapstring ldap_connect() FAILED in line ".__LINE__;
            $this->syslog(__FUNCTION__,__LINE__);
            if($GLOBALS["VERBOSE"]){echo "$filename:: ldap_connect() **!!!!!!!!!!! FAILED !!!!!!!!!! ** in line ".__LINE__."\n";}
            return false;
        }

        if($GLOBALS["VERBOSE"]){echo "$filename:: ldap_connect() [$ldapstring] success in line ".__LINE__."\n";}
        $GLOBALS["ldapConnection"]=$this->ldapConnection;
        ldap_set_option($this->ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ldapConnection, LDAP_OPT_REFERRALS, 0);

        if($this->useSSL) {
            if($GLOBALS["VERBOSE"]){echo "$filename:: ldap_connect() ADD  LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER\n";}
            ldap_set_option($this->ldapConnection, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
        }

        
        if ($this->useTLS) {
            if($GLOBALS["VERBOSE"]){echo "$filename:: ldap_connect() useTLS==TRUE\n";}
            $this->LogsTemp[]="domainController: Use TLS True";
            ldap_start_tls($this->ldapConnection);
        }
        
        if($GLOBALS["VERBOSE"]){
            echo "$this->filename:: $ldapstring adminUsername='$this->adminUsername$this->accountSuffix' adminPassword='$this->adminPassword' in line ".__LINE__."\n";
        }
               
        // Bind as a domain admin if they've set it up
        if ($this->adminUsername !== NULL && $this->adminPassword !== NULL) {
            if($GLOBALS["VERBOSE"]) {
                ldap_set_option($this->ldapConnection, LDAP_OPT_DEBUG_LEVEL, 7);
            }

            $this->ldapBind = @ldap_bind($this->ldapConnection, $this->adminUsername . $this->accountSuffix, $this->adminPassword);
            if (!$this->ldapBind) {
                if($GLOBALS["VERBOSE"]) {
                    ldap_get_option($this->ldapConnection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extendedError);
                    $error=ldap_err2str(ldap_errno($this->ldapConnection));
                    if (!empty($extendedError)) {
                            echo "ldap_bind(): $ldapstring Extended Error: " . $extendedError . " OR $error\n";
                    }
                }

                $this->SyslogAd("adLDAP(".__LINE__.") $domainController:$this->adPort $this->adminUsername$this->accountSuffix bind failed");
                if ($this->useSSL && !$this->useTLS) {
                    $this->LogsTemp[]="Bind to Active Directory failed using [$this->adminUsername]";
                    $this->LogsTemp[]="Either the LDAPs connection failed or the login credentials are incorrect";
                    $this->LogsTemp[]="AD Said : ".$this->getLastError();
                    $this->syslog(__FUNCTION__,__LINE__);
                    return false;
                }
                else {
                	$error=$this->getLastError();
                    $this->LogsTemp[]="Bind to Active Directory failed using [$this->adminUsername]";
                    $this->LogsTemp[]="Check the login credentials and/or server details";
                    $this->LogsTemp[]="AD Said : $error";
                	if($GLOBALS["VERBOSE"]){echo "$filename:: @ldap_bind() *** FAILED *** $error in line ".__LINE__."\n";}
                    $this->syslog(__FUNCTION__,__LINE__);
                    return false;
                }
            }
        }
        if ($this->useSSO && $_SERVER['REMOTE_USER'] && $this->adminUsername === null && $_SERVER['KRB5CCNAME']) {
            putenv("KRB5CCNAME=" . $_SERVER['KRB5CCNAME']);  
            if($GLOBALS["VERBOSE"]){echo "$this->filename:: @ldap_sasl_bind !!! in line ".__LINE__."\n";}
            $this->ldapBind = @ldap_sasl_bind($this->ldapConnection, NULL, NULL, "GSSAPI"); 
            if (!$this->ldapBind){ 
                return false;
            }
            else {
                return true;
            }
        }
        $GLOBALS["ldapBind"]=$this->ldapBind;
        
        if ($this->baseDn == NULL) {
            $this->baseDn = $this->findBaseDn();   
        }
        if($GLOBALS["VERBOSE"]){
        	echo "$this->filename:: @ldap_bind() $domainController success for $this->baseDn in line ".__LINE__."\n";
        	if($this->ldapBind){
        		echo "$this->filename:: @ldap_bind() -> - ldapBind is OK -in line ".__LINE__."\n";
        	}else{
        		echo "$this->filename:: @ldap_bind() -> - ldapBind is FAILED -in line ".__LINE__."\n";
        	}
        }
        return true;
    }
    private function SyslogAd($text){
        if(!function_exists("openlog")){return true;}
        $f=basename(__FILE__);
        $text="[$f]: $text";
        openlog("activedirectory", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, $text);
        closelog();
        return true;
    }
    
    /**
    * Closes the LDAP connection
    * 
    * @return void
    */
    public function close() {
        if ($this->ldapConnection) {
        	if($GLOBALS["VERBOSE"]){echo "$this->filename:: * * * * ldap_close() * * * * in line ".__LINE__."\n";}
            @ldap_close($this->ldapConnection);
        }
        unset($GLOBALS["ldapBind"]);
        
    }

    /**
     * Validate a user's login credentials
     *
     * @param string $username A user's AD username
     * @param string $password A user's AD password
     * @param bool $preventRebind
     * @return bool
     * @throws adLDAPException
     */
    public function authenticate(string $username, string $password, $preventRebind = false): bool
    {
    	$GLOBALS["CLASS_ACTV"][]=__FUNCTION__.": LINE:".__LINE__.": Auth as $username";
        // Prevent null binding
        if ($username === NULL || $password === NULL) { 
        	$GLOBALS["CLASS_ACTV"][]=__FUNCTION__.": LINE:".__LINE__.": username or password is null... [".basename(__FILE__)."]";
        	return false; } 
        if (empty($username) || empty($password)) { 
        	$GLOBALS["CLASS_ACTV"][]=__FUNCTION__.": LINE:".__LINE__.": username or password is empty... [".basename(__FILE__)."]";
        	return false; }
        
        // Allow binding over SSO for Kerberos
        if ($this->useSSO && $_SERVER['REMOTE_USER'] && $_SERVER['REMOTE_USER'] == $username && $this->adminUsername === NULL && $_SERVER['KRB5CCNAME']) { 
            putenv("KRB5CCNAME=" . $_SERVER['KRB5CCNAME']);
            $this->ldapBind = @ldap_sasl_bind($this->ldapConnection, NULL, NULL, "GSSAPI");
            if (!$this->ldapBind) {
            	$GLOBALS["CLASS_ACTV"][]=__FUNCTION__.": LINE:".__LINE__.":Rebind to Active Directory failed. AD said: " . $this->getLastError();
                throw new adLDAPException('Rebind to Active Directory failed. AD said: ' . $this->getLastError());
            }
            else {
            	$GLOBALS["CLASS_ACTV"][]=__FUNCTION__.": LINE:".__LINE__.":useSSO -> TRUE";
                return true;
            }
        }
        
        // Bind as the user        
        $ret = true;
       
        $this->ldapBind = @ldap_bind($this->ldapConnection, $username . $this->accountSuffix, $password);
        if (!$this->ldapBind){
            $this->SyslogAd("adLDAP(".__LINE__.") $username$this->accountSuffix bind failed");
            $ret = false; 
        }
        
        // Cnce we've checked their details, kick back into admin mode if we have it
        if ($this->adminUsername !== NULL && !$preventRebind) {
            $this->ldapBind = @ldap_bind($this->ldapConnection, $this->adminUsername . $this->accountSuffix , $this->adminPassword);
            if (!$this->ldapBind){
                $this->SyslogAd("adLDAP(".__LINE__.") $username$this->accountSuffix bind failed (Rebind to Active Directory failed)");
                throw new adLDAPException('Rebind to Active Directory failed. AD said: ' . $this->getLastError());
            } 
        } 
        
        return $ret;
    }
    
    /**
    * Find the Base DN of your domain controller
    * 
    * @return string
    */
    public function findBaseDn(): string
    {
        $namingContext = $this->getRootDse(array('defaultnamingcontext'));   
        return $namingContext[0]['defaultnamingcontext'][0];
    }
    
    /**
    * Get the RootDSE properties from a domain controller
    * 
    * @param array $attributes The attributes you wish to query e.g. defaultnamingcontext
    * @return array
    */
    public function getRootDse($attributes = array("*", "+")): array
    {
        if (!$this->ldapBind){ return array(); }
        $sr = @ldap_read($this->ldapConnection, NULL, 'objectClass=*', $attributes);
        return  @ldap_get_entries($this->ldapConnection, $sr);
    }

    /**
    * Get last error from Active Directory
    * 
    * This function gets the last message from Active Directory
    * This may indeed be a 'Success' message but if you get an unknown error
    * it might be worth calling this function to see what errors were raised
    * 
    * return string
    */
    public function getLastError(): string
    {
        return @ldap_error($this->ldapConnection);
    }
    
    /**
    * Detect LDAP support in php
    * 
    * @return bool
    */    
    protected function ldapSupported(): bool
    {
        if (!function_exists('ldap_connect')) {
            return false;   
        }
        return true;
    }
    
    /**
    * Detect ldap_sasl_bind support in PHP
    * 
    * @return bool
    */
    protected function ldapSaslSupported(): bool
    {
        if (!function_exists('ldap_sasl_bind')) {
            return false;
        }
        return true;
    }
    
    /**
    * Schema
    * 
    * @param array $attributes Attributes to be queried
    * @return array
    */    
    public function adldap_schema($attributes): array
    {
    
        // LDAP doesn't like NULL attributes, only set them if they have values
        // If you wish to remove an attribute you should set it to a space
        // TO DO: Adapt user_modify to use ldap_mod_delete to remove a NULL attribute
        $mod=array();
        
        // Check every attribute to see if it contains 8bit characters and then UTF8 encode them
        array_walk($attributes, array($this, 'encode8bit'));

        if ($attributes["address_city"]){ $mod["l"][0]=$attributes["address_city"]; }
        if ($attributes["address_code"]){ $mod["postalCode"][0]=$attributes["address_code"]; }
        //if ($attributes["address_country"]){ $mod["countryCode"][0]=$attributes["address_country"]; } // use country codes?
        if ($attributes["address_country"]){ $mod["c"][0]=$attributes["address_country"]; }
        if ($attributes["address_pobox"]){ $mod["postOfficeBox"][0]=$attributes["address_pobox"]; }
        if ($attributes["address_state"]){ $mod["st"][0]=$attributes["address_state"]; }
        if ($attributes["address_street"]){ $mod["streetAddress"][0]=$attributes["address_street"]; }
        if ($attributes["company"]){ $mod["company"][0]=$attributes["company"]; }
        if ($attributes["change_password"]){ $mod["pwdLastSet"][0]=0; }
        if ($attributes["department"]){ $mod["department"][0]=$attributes["department"]; }
        if ($attributes["description"]){ $mod["description"][0]=$attributes["description"]; }
        if ($attributes["display_name"]){ $mod["displayName"][0]=$attributes["display_name"]; }
        if ($attributes["email"]){ $mod["mail"][0]=$attributes["email"]; }
        if ($attributes["expires"]){ $mod["accountExpires"][0]=$attributes["expires"]; } //unix epoch format?
        if ($attributes["firstname"]){ $mod["givenName"][0]=$attributes["firstname"]; }
        if ($attributes["home_directory"]){ $mod["homeDirectory"][0]=$attributes["home_directory"]; }
        if ($attributes["home_drive"]){ $mod["homeDrive"][0]=$attributes["home_drive"]; }
        if ($attributes["initials"]){ $mod["initials"][0]=$attributes["initials"]; }
        if ($attributes["logon_name"]){ $mod["userPrincipalName"][0]=$attributes["logon_name"]; }
        if ($attributes["manager"]){ $mod["manager"][0]=$attributes["manager"]; }  //UNTESTED ***Use DistinguishedName***
        if ($attributes["office"]){ $mod["physicalDeliveryOfficeName"][0]=$attributes["office"]; }
        if ($attributes["password"]){ $mod["unicodePwd"][0]=$this->user()->encodePassword($attributes["password"]); }
        if ($attributes["profile_path"]){ $mod["profilepath"][0]=$attributes["profile_path"]; }
        if ($attributes["script_path"]){ $mod["scriptPath"][0]=$attributes["script_path"]; }
        if ($attributes["surname"]){ $mod["sn"][0]=$attributes["surname"]; }
        if ($attributes["title"]){ $mod["title"][0]=$attributes["title"]; }
        if ($attributes["telephone"]){ $mod["telephoneNumber"][0]=$attributes["telephone"]; }
        if ($attributes["mobile"]){ $mod["mobile"][0]=$attributes["mobile"]; }
        if ($attributes["pager"]){ $mod["pager"][0]=$attributes["pager"]; }
        if ($attributes["ipphone"]){ $mod["ipphone"][0]=$attributes["ipphone"]; }
        if ($attributes["web_page"]){ $mod["wWWHomePage"][0]=$attributes["web_page"]; }
        if ($attributes["fax"]){ $mod["facsimileTelephoneNumber"][0]=$attributes["fax"]; }
        if ($attributes["enabled"]){ $mod["userAccountControl"][0]=$attributes["enabled"]; }
        if ($attributes["homephone"]){ $mod["homephone"][0]=$attributes["homephone"]; }
        
        // Distribution List specific schema
        if ($attributes["group_sendpermission"]){ $mod["dlMemSubmitPerms"][0]=$attributes["group_sendpermission"]; }
        if ($attributes["group_rejectpermission"]){ $mod["dlMemRejectPerms"][0]=$attributes["group_rejectpermission"]; }
        
        // Exchange Schema
        if ($attributes["exchange_homemdb"]){ $mod["homeMDB"][0]=$attributes["exchange_homemdb"]; }
        if ($attributes["exchange_mailnickname"]){ $mod["mailNickname"][0]=$attributes["exchange_mailnickname"]; }
        if ($attributes["exchange_proxyaddress"]){ $mod["proxyAddresses"][0]=$attributes["exchange_proxyaddress"]; }
        if ($attributes["exchange_usedefaults"]){ $mod["mDBUseDefaults"][0]=$attributes["exchange_usedefaults"]; }
        if ($attributes["exchange_policyexclude"]){ $mod["msExchPoliciesExcluded"][0]=$attributes["exchange_policyexclude"]; }
        if ($attributes["exchange_policyinclude"]){ $mod["msExchPoliciesIncluded"][0]=$attributes["exchange_policyinclude"]; }       
        if ($attributes["exchange_addressbook"]){ $mod["showInAddressBook"][0]=$attributes["exchange_addressbook"]; }    
        if ($attributes["exchange_altrecipient"]){ $mod["altRecipient"][0]=$attributes["exchange_altrecipient"]; } 
        if ($attributes["exchange_deliverandredirect"]){ $mod["deliverAndRedirect"][0]=$attributes["exchange_deliverandredirect"]; }    
        
        // This schema is designed for contacts
        if ($attributes["exchange_hidefromlists"]){ $mod["msExchHideFromAddressLists"][0]=$attributes["exchange_hidefromlists"]; }
        if ($attributes["contact_email"]){ $mod["targetAddress"][0]=$attributes["contact_email"]; }
        
        //echo ("<pre>"); print_r($mod);
        /*
        // modifying a name is a bit fiddly
        if ($attributes["firstname"] && $attributes["surname"]){
            $mod["cn"][0]=$attributes["firstname"]." ".$attributes["surname"];
            $mod["displayname"][0]=$attributes["firstname"]." ".$attributes["surname"];
            $mod["name"][0]=$attributes["firstname"]." ".$attributes["surname"];
        }
        */

        if (count($mod)==0){ return array(); }
        return ($mod);
    }
    
    /**
    * Convert 8bit characters e.g. accented characters to UTF8 encoded characters
    */
    protected function encode8Bit(&$item, $key) {
        $encode = false;
        if (is_string($item)) {
            for ($i=0; $i<strlen($item); $i++) {
                if (ord($item[$i]) >> 7) {
                    $encode = true;
                }
            }
        }
        if ($encode === true && $key != 'password') {
            $item = utf8_encode($item);   
        }
    }
    
    /**
    * Select a random domain controller from your domain controller array
    * 
    * @return string
    */
    protected function randomController(): string
    {
        mt_srand(doubleval(microtime()) * 100000000); // For older PHP versions
        /*if (sizeof($this->domainControllers) > 1) {
            $adController = $this->domainControllers[array_rand($this->domainControllers)]; 
            // Test if the controller is responding to pings
            $ping = $this->pingController($adController); 
            if ($ping === false) { 
                // Find the current key in the domain controllers array
                $key = array_search($adController, $this->domainControllers);
                // Remove it so that we don't end up in a recursive loop
                unset($this->domainControllers[$key]);
                // Select a new controller
                return $this->randomController(); 
            }
            else { 
                return ($adController); 
            }
        } */
        return $this->domainControllers[array_rand($this->domainControllers)];
    }  
    
    /** 
    * Test basic connectivity to controller 
    * 
    * @return bool
    */ 
    protected function pingController($host): bool
    {
        $port = $this->adPort; 
        fsockopen($host, $port, $errno, $errstr, 10); 
        if ($errno > 0) {
            return false;
        }
        return true;
    }

}

/**
* adLDAP Exception Handler
* 
* Exceptions of this type are thrown on bind failure or when SSL is required but not configured
* Example:
* try {
*   $adldap = new adLDAP();
* }
* catch (adLDAPException $e) {
*   echo $e;
*   exit();
* }
*/
class adLDAPException extends Exception {}
