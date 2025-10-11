<?php
class whois{
	var $hote=null;
	var $domain;
	var  $whois_serveurs = array(
"ac" => "whois.nic.ac", 
"al" => "whois.ripe.net", 
"am" => "whois.amnic.net", 
"as" => "whois.nic.as",
 "at" => "whois.ripe.net",
 "au" => "whois.aunic.net",
 "az" => "whois.ripe.net",
 "ba" => "whois.ripe.net",
 "be" => "whois.ripe.net",
 "bg" => "whois.ripe.net",
 //"biz" => "whois.neulevel.biz",
 "biz" => "whois.nic.biz",
 "br" => "whois.registro.br",
 "by" => "whois.ripe.net",
 "ca" => "whois.cira.ca",
 "cc" => "whois.nic.cc",
 "ch" => "whois.nic.ch",
 "ck" => "whois.ck-nic.org.ck",
 "cn" => "whois.cnnic.net.cn",
 "com" => "whois.crsnic.net",
 "cx" => "whois.nic.cx",
 "cy" => "whois.ripe.net",
 "cz" => "whois.nic.cz",
 "de" => "whois.denic.de",
 "dk" => "whois.dk-hostmaster.dk",
 "dz" => "whois.ripe.net",
 "edu" => "rs.internic.net",
 "ee" => "whois.ripe.net",
 "eg" => "whois.ripe.net",
 "es" => "whois.ripe.net",
 "eu" => "whois.eu",
 "fi" => "whois.ripe.net",
 "fj" => "whois.usp.ac.fj",
 "fo" => "whois.ripe.net",
 "fr" => "whois.nic.fr",
 "gb" => "whois.ripe.net",
 "ge" => "whois.ripe.net",
 "gov" => "whois.nic.gov",
 "gr" => "whois.ripe.net",
 "gs" => "whois.adamsnames.tc",
 "hk" => "whois.hknic.net.hk",
 "hm" => "whois.registry.hm",
 "hr" => "whois.ripe.net",
 "hu" => "whois.ripe.net",
 "id" => "whois.idnic.net.id",
 "ie" => "whois.domainregistry.ie",
 "info" => "whois.afilias.net",
 "int" => "whois.isi.edu",
 "il" => "whois.ripe.net",
 "is" => "whois.isnet.is",
 "it" => "whois.nic.it",
 "jp" => "whois.nic.ad.jp",
 "ke" => "whois.rg.net",
 "kg" => "whois.domain.kg",
 "kr" => "whois.nic.or.kr",
 "kz" => "whois.domain.kz",
 "li" => "whois.nic.li",
 "lk" => "whois.nic.lk",
 "lt" => "whois.ripe.net",
 "lu" => "whois.ripe.net",
 "lv" => "whois.ripe.net",
 "ma" => "whois.ripe.net",
 "md" => "whois.ripe.net",
 "mil" => "whois.nic.mil",
 "mk" => "whois.ripe.net",
 "mm" => "whois.nic.mm",
 "ms" => "whois.adamsnames.tc",
 "mt" => "whois.ripe.net",
 "mx" => "whois.nic.mx",
 "net" => "rs.internic.net",
 "nl" => "whois.domain-registry.nl",
 "no" => "whois.norid.no",
 "nu" => "whois.nic.nu",
 "nz" => "whois.domainz.net.nz",
 "org" => "whois.pir.org",
 "pl" => "whois.ripe.net",
 "pk" => "whois.pknic.net.pk",
 "pt" => "whois.ripe.net",
 "ro" => "whois.ripe.net",
 "ru" => "whois.ripn.ru",
 "se" => "whois.nic-se.se",
 "sg" => "whois.nic.net.sg",
 "si" => "whois.ripe.net",
 "sh" => "whois.nic.sh",
 "sk" => "whois.ripe.net",
 "sm" => "whois.ripe.net",
 "st" => "whois.nic.st",
 "su" => "whois.ripe.net",
 "tc" => "whois.adamsnames.tc",
 "tf" => "whois.adamsnames.tc",
 "tj" => "whois.nic.tj",
 "th" => "whois.thnic.net",
 "tm" => "whois.nic.tm",
 "tn" => "whois.ripe.net",
 "to" => "whois.tonic.to",
 "tr" => "whois.ripe.net",
 "tw" => "whois.twnic.net",
 "ua" => "whois.ripe.net",
 "uk" => "whois.nic.uk",
 "us" => "whois.isi.edu",
 "va" => "whois.ripe.net",
 "vg" => "whois.adamsnames.tc",
 "ws" => "whois.nic.ws",
 "yu" => "whois.ripe.net",
 "za" => "whois.frd.ac.za");
	
	function __construct($domain){
		$domain="www.google.com";
		 $this->domain=$domain;
		 $parseur = explode(".", $this->domain);
		 $domaintosee=strtolower($parseur[count($parseur) - 1]);
		 echo "Domain: $domaintosee\n";
      	 $this->hote = $this->whois_serveurs[$domaintosee];
		
		
	}
	
	function getinfos(){
		if($this->hote==null){return;}
		echo "Open $this->hote..\n";
		 $fp = fsockopen($this->hote, 43, $errno, $errstr, 10);
         fputs($fp, $this->domain . "\r\n");
		 while (!feof($fp)) {
              $row = fgets($fp, 128);
              $buf .= $row;
              if(preg_match("#Whois Server:#", $row)){echo $row."\n";}
         	  echo $row."\n";
		 }
		  fclose($fp);
		 $this->getsubinfos();
	}
	
	
	function getsubinfos(){
		$fp = fsockopen("WWW.GOOGLE.COM.PE", 43, $errno, $errstr, 10);
                  fputs($fp, $this->domain . "\r\n");
                  while (!feof($fp)){
                      print fgets($fp, 128);
                  }
                  fclose($fp);
                  
		
		
		
	}
	
	
}