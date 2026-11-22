<?php
include_once(dirname(__FILE__) . "/class.ini.inc");
if(!is_file("class.ldap.inc")){include_once(dirname(__FILE__) . '/class.ldap.inc');}else{include_once('class.ldap.inc');}
class ssl_certificate{
	
	var $default_file;
	var $array_ssl=array();
	var $ini_sources;
	var $array_country_codes;
	var $ldap_last_error;
	
	
	function __construct(){
		$this->default_file=dirname(__FILE__) . '/databases/DEFAULT-CERTIFICATE-DB.txt';
		$this->LoadCountryCodes();
		$this->LoadSources();
		unset($_SESSION["CACHEINFOS_PostfixSSLCert"]);
		}
	
	
	function LoadCountryCodes(){
		$db=file_get_contents(dirname(__FILE__) . '/databases/ISO-3166-Codes-Countries.txt');
		$tbl=explode("\n",$db);

		foreach ($tbl as $num=>$ligne){
			if(preg_match('#(.+?);\s+([A-Z]{1,2})#',$ligne,$regs)){
				$regs[2]=trim($regs[2]);
				$regs[1]=trim($regs[1]);
				$this->array_country_codes["{$regs[1]}_{$regs[2]}"]=$regs[1];
			}
			
		}
	}
	
	function LoadSources()
    {
        if (isset($GLOBALS["CLASS_SOCKETS"])) {
            $sock = $GLOBALS["CLASS_SOCKETS"];
        } else {
            $GLOBALS["CLASS_SOCKETS"] = new sockets();
            $sock = $GLOBALS["CLASS_SOCKETS"];
        }

        if (function_exists("debug_backtrace")) {
            $strace = debug_backtrace();
            foreach ($strace as $index=>$ztrace) {
                $sourcefile = basename($ztrace["file"]);
                $sourcefunction = $ztrace["function"];
                $sourceline = $ztrace["line"];
                writelogs("by $sourcefile function $sourcefunction in $sourceline",
                    __CLASS__.'/'.__FUNCTION__,__FILE__);
            }
        }

        $this->ini_sources=$sock->GET_INFO('PostfixSSLCert');

		
		if(trim($this->ini_sources)==null){
			// Ancien framework/postfix.php?CertificateConfigFile= : deux fichiers lisibles par www-data.
			foreach(array("/etc/artica-postfix/ssl.certificate.conf",
				"/usr/share/artica-postfix/ressources/databases/DEFAULT-CERTIFICATE-DB.txt") as $f){
				if(is_readable($f)){$this->ini_sources=@file_get_contents($f);break;}
			}
		}
		$ini=new Bs_IniHandler();
		if($this->ini_sources==null){
			$ini->loadFile($this->default_file);
		}else{
			$ini->loadString($this->ini_sources);
			}
		
		
		$this->array_ssl=$ini->_params;
		if(!isset($this->array_ssl["default_db"]["dir"])){$this->array_ssl["default_db"]["dir"]=null;}
		if(!isset($this->array_ssl["postfix"]["smtpd_tls_key_file"])){$this->array_ssl["postfix"]["smtpd_tls_key_file"]=null;}
		if(!isset($this->array_ssl["postfix"]["smtpd_tls_cert_file"])){$this->array_ssl["postfix"]["smtpd_tls_cert_file"]=null;}
		if(!isset($this->array_ssl["postfix"]["smtpd_tls_CAfile"])){$this->array_ssl["postfix"]["smtpd_tls_CAfile"]=null;}
		
		
		
		if($this->array_ssl["default_db"]["dir"]==null){$this->array_ssl["default_db"]["dir"]='/etc/postfix/certificates';}
		if($this->array_ssl["postfix"]["smtpd_tls_key_file"]==null){$this->array_ssl["postfix"]["smtpd_tls_key_file"]='smtpd.key';}
		if($this->array_ssl["postfix"]["smtpd_tls_cert_file"]==null){$this->array_ssl["postfix"]["smtpd_tls_cert_file"]='smtpd.crt';}
		if($this->array_ssl["postfix"]["smtpd_tls_CAfile"]==null){$this->array_ssl["postfix"]["smtpd_tls_CAfile"]='cacert.pem';}
		}
		
	function SaveToLdap(){
			$ini=new Bs_IniHandler();
			$ini->_params=$this->array_ssl;
			$datas=$ini->toString();
			if(isset($GLOBALS["CLASS_SOCKETS"])){$sock=$GLOBALS["CLASS_SOCKETS"];}else{$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}
			unset($_SESSION["CACHEINFOS_PostfixSSLCert"]);
			$sock->SaveConfigFile($datas,'PostfixSSLCert');
			$sock->getFrameWork('cmd.php?ChangeSSLCertificate=yes');
			return true;
			}
			
	
	
	
}