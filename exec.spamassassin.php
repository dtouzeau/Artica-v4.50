<?php
if($argv[1]=='--mimeimport'){mimeimport();exit();}
$GLOBALS["AS_ROOT"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
	$GLOBALS["AS_ROOT"]=true;
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__) . '/ressources/class.spamassassin.inc');
	include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).  '/framework/class.unix.inc');
	include_once(dirname(__FILE__).  '/framework/frame.class.inc');
	include_once(dirname(__FILE__).  '/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).  '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__).  '/ressources/class.maincf.multi.inc');
	include_once(dirname(__FILE__).  '/ressources/class.amavis.inc');	
	include_once(dirname(__FILE__).  "/ressources/class.tcpip.inc");
    include_once(dirname(__FILE__).  '/ressources/class.ccurl.inc');
		
	$GLOBALS["CMDLINES"]=@implode(" ", $argv);	
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}	
	if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	$user=new usersMenus();
	$unix=new unix();
	$spamd=$unix->find_program("spamd");
	if(is_file("/usr/local/bin/spamd")){$spamd="/usr/local/bin/spamd";}
	
	if(!is_file($spamd)){ exit();}
	if($argv[1]=='--sa-update'){sa_update();exit();}
	if($argv[1]=='--sa-update-check'){sa_update_check();exit();}
	
	x_headers();
	x_bounce();
	
	if($argv[1]=='--spf'){spf();exit();}
	if($argv[1]=='--dkim'){dkim();amavis_reload();exit();}
	if($argv[1]=='--dnsbl'){dnsbl();exit();}
	if($argv[1]=='--DecodeShortURLs'){DecodeShortURLs();amavis_reload();exit();}
	if($argv[1]=='--trusted'){TrustedNetworks(true);exit();}
	if($argv[1]=='--whitelist'){SaveConf();exit();}
	if($argv[1]=='--spam-tests'){SpamTests($argv[2]);exit();}
	
	
	
echo "Starting......: ".date("H:i:s")." spamassassin starting building configuration\n";	
SaveConf();
echo "Starting......: ".date("H:i:s")." Patching binaries...\n";
patch_deamons();
echo "Starting......: ".date("H:i:s")." Check Relay Country plugin\n";	
RelayCountryPlugin();
echo "Starting......: ".date("H:i:s")." Check Mime Validator plugin\n";
MimeValidator();
echo "Starting......: ".date("H:i:s")." Check DNSBL plugin\n";
dnsbl();
echo "Starting......: ".date("H:i:s")." Check Decode Short urls\n";
DecodeShortURLs();
echo "Starting......: ".date("H:i:s")." Check Trusted networks\n";
TrustedNetworks();
WrongMX();
FromAndNotReplyTo();
FuzzyOcr();
SPAMASSASSIN_V320();
CheckSecuritiesFolders();


function amavis_reload(){
	$sock=new sockets();
	$unix=new unix();
	$EnableAmavisDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAmavisDaemon"));
	$SpamAssMilterEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssMilterEnabled"));
	$MimeDefangEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangEnabled"));
	$MimeDefangSpamAssassin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MimeDefangSpamAssassin"));
	if($MimeDefangEnabled==0){$MimeDefangSpamAssassin=0;}
	if($MimeDefangSpamAssassin==1){$SpamAssMilterEnabled=0;}
	$PHP=$unix->LOCATE_PHP5_BIN();
	SPAMASSASSIN_V320();
	PhishTag();
	HitFreqsRuleTiming();
	

	
	if($SpamAssMilterEnabled==1){
		if(is_file("/etc/init.d/spamass-milter")){
			echo "Starting......: ".date("H:i:s")." Restarting SpamAssassin milter edition\n";
			shell_exec("/etc/init.d/spamass-milter restart");
			
		}
	}
	
	
}

function CheckSecuritiesFolders(){
	if(is_dir("/etc/mail/spamassassin")){
		shell_exec("/bin/chmod -R 666 /etc/mail/spamassassin");
		shell_exec("/bin/chown -R postfix:postfix /etc/mail/spamassassin");
		shell_exec("/bin/chmod 755 /etc/mail/spamassassin");		
	}
	if(is_dir("/etc/spamassassin")){
		shell_exec("/bin/chmod -R 666 /etc/spamassassin");
		shell_exec("/bin/chmod 755 /etc/spamassassin");
	}
	
	if(is_dir("/var/lib/spamassassin")){
		shell_exec("/bin/chmod -R 755 /var/lib/spamassassin");
		shell_exec("/bin/chown -R postfix:postfix /etc/spamassassin");
		shell_exec("/bin/chown -R postfix:postfix /var/lib/spamassassin");
	}	
	
}







function SaveConf(){
	include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
	shell_exec("export LC_CTYPE=C");
	shell_exec("export LC_ALL=C");
	shell_exec("export LANG=C");
	shell_exec("export LANGUAGE=C");
	shell_exec("export LC_MESSAGES=posix");
	
	$user=new usersMenus();
	$spam=new spamassassin();
	$unix=new unix();
	
	$postfix_multi=new maincf_multi("master");
	$postfix_multi->check_client_access();
	
	
	$spamd=$unix->find_program("spamd");
	if(is_file("/usr/local/bin/spamd")){$spamd="/usr/local/bin/spamd";}
	
	$content=@file_get_contents($spamd);
	if(strpos($content, "/etc/mail/spamassassin")>0){
		$content=str_replace("/etc/mail/spamassassin", "/etc/spamassassin", $content);
		@file_put_contents($spamd, $content);
	
	}
	
	
	
	
	$datas=$spam->BuildConfig();
	$datas=str_replace("Array","",$datas);

	echo "Starting......: ".date("H:i:s")." spamassassin saving /etc/spamassassin/local.cf\n";
	@unlink("/etc/spamassassin/local.cf");
	file_put_contents("/etc/spamassassin/local.cf",$datas);
	
	if(is_file("/etc/spamassassin/v312.pre")){@unlink("/etc/spamassassin/v312.pre");}
	if(is_file("/etc/mail/spamassassin/v312.pre")){@unlink("/etc/mail/spamassassin/v312.pre");}	
	
	if(is_file("/etc/mail/spamassassin/local.cf")){
		@unlink("/etc/mail/spamassassin/local.cf");
		file_put_contents("/etc/mail/spamassassin/local.cf",$datas);
	}
	
	if(is_file("/usr/share/artica-postfix/bin/install/postfix/Botnet.cf")){
		@copy("/usr/share/artica-postfix/bin/install/postfix/Botnet.cf", "/etc/spamassassin/Botnet.cf");
		@copy("/usr/share/artica-postfix/bin/install/postfix/Botnet.pl", "/etc/spamassassin/Botnet.pl");
		@copy("/usr/share/artica-postfix/bin/install/postfix/Botnet.pm", "/etc/spamassassin/Botnet.pm");
	}
	
	if(is_file("/usr/share/artica-postfix/bin/install/postfix/sakam.cf")){
		@copy("/usr/share/artica-postfix/bin/install/postfix/sakam.cf", "/etc/spamassassin/sakam.cf");
	}
	
	Chineses_rules();
	TrustedNetworks();
	
}

function FromNotReplyToPlugin(){
	$f[]="package FromNotReplyTo;";
	$f[]="1;";
	$f[]="";
	$f[]="use strict;";
	$f[]="";
	$f[]="use Mail::SpamAssassin;";
	$f[]="use Mail::SpamAssassin::Plugin;";
	$f[]="our @ISA = qw(Mail::SpamAssassin::Plugin);";
	$f[]="";
	$f[]="";
	$f[]="sub new {";
	$f[]="        my (\$class, \$mailsa) = @_;";
	$f[]="        \$class = ref(\$class) || \$class;";
	$f[]="        my \$self = \$class->SUPER::new( \$mailsa );";
	$f[]="        bless (\$self, \$class);";
	$f[]="        \$self->register_eval_rule ( 'check_for_from_not_reply_to' );";
	$f[]="        ";
	$f[]="        return \$self;";
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="# Often spam uses different From: and Reply-To:";
	$f[]="# while most legitimate e-mails does not.";
	$f[]="sub check_for_from_not_reply_to {";
	$f[]="        my (\$self, \$msg) = @_;";
	$f[]="";
	$f[]="        my \$from = \$msg->get( 'From:addr' );";
	$f[]="        my \$replyTo = \$msg->get( 'Reply-To:addr' );";
	$f[]="";
	$f[]="        #Mail::SpamAssassin::Plugin::dbg( \"FromNotReplyTo: Comparing '\$from'/'\$replyTo\" );";
	$f[]="";
	$f[]="        if ( \$from ne '' && \$replyTo ne '' && \$from ne \$replyTo ) {";
	$f[]="                return 1;";
	$f[]="        }";
	$f[]="";
	$f[]="        return 0;";
	$f[]="}\n";
	
	@file_put_contents("/etc/spamassassin/FromNotReplyTo.pm", @implode("\n", $f));
	
}

function RelayCountryPlugin(){	
	$user=new usersMenus();
	$sock=new sockets();
	$EnableRelayCountry=$sock->GET_INFO("EnableRelayCountry");
	if($EnableRelayCountry<>1){return;}
	CleanConf($user->spamassassin_conf_path);
	
	if(!$user->spamassassin_ipcountry){
		write_syslog("wants  to add IP countries but IP::Country::Fast is not installed.",__FILE__);
		return null;
	}
	
	$spam=new spamassassin();
	
	$RelayCountryPlugin_path=dirname($user->spamassassin_conf_path)."/RelayCountryPlugin.cf";
	$init_pre=dirname($user->spamassassin_conf_path)."/init.pre";
	
	$count=0;
	$conf=null;
	if(is_array($spam->main_country)){
		while (list ($country_code, $array) = each ($spam->main_country)){
			if(trim($country_code)==null){continue;}
			$count=$count+1;
			$conf=$conf."header\tRELAYCOUNTRY_$country_code X-Relay-Countries =~ /$country_code/\n";
   			$conf=$conf."describe        RELAYCOUNTRY_$country_code Relayed through {$array["country_name"]}\n";
   			$conf=$conf."score           RELAYCOUNTRY_$country_code {$array["score"]}\n\n";
		
		}
	}
	
	file_put_contents($RelayCountryPlugin_path,$conf);
	write_syslog("Saved $count Countries into spamassassin configuration",__FILE__);
	if($count>1){
		$file=file_get_contents($user->spamassassin_conf_path);
		$file=$file . "\n##### Relay Countries\ninclude\t$RelayCountryPlugin_path\n";
		$file=$file . "add_header all Relay-Country _RELAYCOUNTRY_\n\n";
		file_put_contents($user->spamassassin_conf_path,$file);
		$file=null;
		$file=file_get_contents($init_pre);
		$file=$file . "\nloadplugin\tMail::SpamAssassin::Plugin::RelayCountry\n";
		file_put_contents($init_pre,$file);
		}
	
}
	
	
	
function CleanConf($confPath){
	$conf=null;
	$file=file_get_contents($confPath);
	$init_pre=dirname($confPath)."/init.pre";
	$array=explode("\n",$file);
	foreach ($array as $num=>$line){
		if(trim($line)==null){continue;}
		if(preg_match("#^\##",$line)){continue;}
		if(preg_match("#^include\s+".dirname($confPath)."/RelayCountryPlugin\.cf#",$line)){continue;}
		if(preg_match("#^add_header.+?Relay-Country#",$line)){continue;}
		$conf=$conf . "$line\n";
	}
	
	file_put_contents($confPath,$conf);
	$conf=null;
	
	if(file_exists($init_pre)){
		$file=file_get_contents($init_pre);
		$array=explode("\n",$file);
		foreach ($array as $num=>$line){
			if(trim($line)==null){continue;}
			if(preg_match("#^\##",$line)){continue;}
			if(preg_match("#loadplugin.+?RelayCountry#",$line)){continue;}
			$conf=$conf . "$line\n";
		}		
		file_put_contents($init_pre,$conf);
	}
}


function FuzzyOcr(){

$sock=new sockets();
@unlink("/etc/spamassassin/FuzzyOcr.cf");
if($sock->GET_INFO("EnableFuzzyOcr")<>1){return;}
	
if(!is_file("/etc/spamassassin/FuzzyOcr/Config.pm")){return;}	
$f[]="loadplugin FuzzyOcr FuzzyOcr.pm";
$f[]="";
$f[]="body     FUZZY_OCR                   eval:fuzzyocr_check()";
$f[]="body     FUZZY_OCR_WRONG_CTYPE       eval:dummy_check()";
$f[]="body     FUZZY_OCR_CORRUPT_IMG       eval:dummy_check()";
$f[]="body     FUZZY_OCR_WRONG_EXTENSION   eval:dummy_check()";
$f[]="body     FUZZY_OCR_KNOWN_HASH        eval:dummy_check()";
$f[]="";
$f[]="describe FUZZY_OCR                   Mail contains an image with common spam text inside";
$f[]="describe FUZZY_OCR_WRONG_CTYPE       Mail contains an image with wrong content-type set";
$f[]="describe FUZZY_OCR_WRONG_EXTENSION   Mail contains an image with wrong file extension";
$f[]="describe FUZZY_OCR_CORRUPT_IMG       Mail contains a corrupted image";
$f[]="describe FUZZY_OCR_KNOWN_HASH        Mail contains an image with known hash";
$f[]="";
$f[]="priority FUZZY_OCR 900";
$f[]="focr_global_wordlist /etc/spamassassin/FuzzyOcr.words";
$f[]="focr_bin_helper pnmnorm, pnminvert, convert, ppmtopgm, tesseract";
$f[]="focr_path_bin /usr/local/netpbm/bin:/usr/local/bin:/usr/bin";
$f[]="focr_preprocessor_file /etc/spamassassin/FuzzyOcr.preps";
$f[]="focr_scanset_file /etc/spamassassin/FuzzyOcr.scansets";
$f[]="focr_enable_image_hashing 2";
$f[]="focr_digest_db /etc/spamassassin/FuzzyOcr.hashdb";
$f[]="focr_db_hash /etc/spamassassin/FuzzyOcr.db";
$f[]="focr_db_safe /etc/spamassassin/FuzzyOcr.safe.db";
$f[]="#################################################################";
$f[]="# DO NOT REMOVE THIS LINE, IT IS REQUIRED UNDER ALL CIRCUMSTANCES";
$f[]="focr_end_config";	
	
@file_put_contents("/etc/spamassassin/FuzzyOcr.cf",@implode("\n",$f));

echo "Starting......: ".date("H:i:s")." spamassassin writing FuzzyOcr.cf done\n";

	
}


function Chineses_rules(){
	
	if(is_file("/usr/share/artica-postfix/bin/install/spamassassin/Chinese_rules.cf")){
		@copy("/usr/share/artica-postfix/bin/install/spamassassin/Chinese_rules.cf","/etc/spamassassin/Chinese_rules.cf");
		
		if(is_dir("/etc/mail/spamassassin")){
			@copy("/usr/share/artica-postfix/bin/install/spamassassin/Chinese_rules.cf","/etc/mail/spamassassin/Chinese_rules.cf");
		}
	}
	
	
}

function SPAMASSASSIN_V320(){
	
	
@unlink("/etc/spamassassin/v310.pre");
@unlink("/etc/mail/spamassassin/v310.pre");	

@unlink("/etc/spamassassin/v312.pre");
@unlink("/etc/mail/spamassassin/v312.pre");	

@unlink("/etc/spamassassin/v320.pre");
@unlink("/etc/mail/spamassassin/v320.pre");	

@unlink("/etc/spamassassin/v330.pre");
@unlink("/etc/mail/spamassassin/v330.pre");	

$sock=new sockets();
$EnableSpamassassinDnsEval=$sock->GET_INFO("EnableSpamassassinDnsEval");
$EnableSPF=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSPF"));
$enable_dkim_verification=$sock->GET_INFO("enable_dkim_verification");
$EnableSpamassassinURIDNSBL=$sock->GET_INFO("EnableSpamassassinURIDNSBL");
$EnableSpamAssassinFreeMail=$sock->GET_INFO("EnableSpamAssassinFreeMail");
$EnablePhishTag=$sock->GET_INFO("EnablePhishTag");
$spam=new spamassassin();

if(!is_numeric($EnableSpamassassinDnsEval)){$EnableSpamassassinDnsEval=0;}
if(!is_numeric($EnableSPF)){$EnableSPF=0;}
if(!is_numeric($enable_dkim_verification)){$enable_dkim_verification=0;}
if(!is_numeric($EnableSpamassassinURIDNSBL)){$EnableSpamassassinURIDNSBL=1;}
if(!is_numeric($EnableSpamAssassinFreeMail)){$EnableSpamAssassinFreeMail=0;}
if(!is_numeric($EnablePhishTag)){$EnablePhishTag=0;}
				
$f[]="loadplugin Mail::SpamAssassin::Plugin::Check";
$f[]="loadplugin Mail::SpamAssassin::Plugin::HTTPSMismatch";
$f[]="loadplugin Mail::SpamAssassin::Plugin::URIDetail";
$f[]="loadplugin Mail::SpamAssassin::Plugin::Bayes";
$f[]="loadplugin Mail::SpamAssassin::Plugin::BodyEval";
$f[]="loadplugin Mail::SpamAssassin::Plugin::MIMEHeader";
$f[]="loadplugin Mail::SpamAssassin::Plugin::DNSEval";
$f[]="loadplugin Mail::SpamAssassin::Plugin::FreeMail";
@copy("/usr/share/artica-postfix/bin/install/spamassassin/20_freemail_domains.cf", "/etc/spamassassin/20_freemail_domains.cf");

if($EnableSPF==1){$f[]="loadplugin Mail::SpamAssassin::Plugin::SPF";}
if($enable_dkim_verification==1){$f[]="loadplugin Mail::SpamAssassin::Plugin::DKIM";}
if($EnableSpamassassinURIDNSBL==1){$f[]="loadplugin Mail::SpamAssassin::Plugin::URIDNSBL";}
if($EnableSpamAssassinFreeMail==1){$f[]="loadplugin Mail::SpamAssassin::Plugin::FreeMail";}
if($EnablePhishTag==1){$f[]="loadplugin Mail::SpamAssassin::Plugin::PhishTag";}


$f[]="loadplugin Mail::SpamAssassin::Plugin::HTMLEval";
$f[]="loadplugin Mail::SpamAssassin::Plugin::HeaderEval";
$f[]="loadplugin Mail::SpamAssassin::Plugin::MIMEEval";
$f[]="loadplugin Mail::SpamAssassin::Plugin::RelayEval";
$f[]="loadplugin Mail::SpamAssassin::Plugin::URIEval";
$f[]="loadplugin Mail::SpamAssassin::Plugin::WLBLEval";
$f[]="loadplugin Mail::SpamAssassin::Plugin::ImageInfo";
//$f[]="loadplugin Mail::SpamAssassin::Plugin::AWL";
$f[]="loadplugin Mail::SpamAssassin::Plugin::AutoLearnThreshold";
$f[]="loadplugin Mail::SpamAssassin::Plugin::WhiteListSubject";
$f[]="loadplugin Mail::SpamAssassin::Plugin::Hashcash";
$f[]="loadplugin Mail::SpamAssassin::Plugin::ReplaceTags";
$f[]="loadplugin Mail::SpamAssassin::Plugin::TextCat";
$f[]="loadplugin Mail::SpamAssassin::Plugin::WhiteListSubject";
//$f[]="loadplugin Mail::SpamAssassin::Plugin::Rule2XSBody";
$f[]="loadplugin Mail::SpamAssassin::Plugin::HTTPSMismatch";
if(is_file("/etc/spamassassin/HitFreqsRuleTiming.pm")){
	//$f[]="loadplugin HitFreqsRuleTiming /etc/spamassassin/HitFreqsRuleTiming.pm";
}
//loadplugin Mail::SpamAssassin::Plugin::Shortcircuit
if($spam->main_array["use_razor2"]==1){
	$f[]="loadplugin Mail::SpamAssassin::Plugin::Razor2";
}
if($spam->main_array["use_pyzor"]==1){
	$f[]="loadplugin Mail::SpamAssassin::Plugin::Pyzor";	
}

$f[]="#loadplugin Mail::SpamAssassin::Plugin::SpamCop";
$f[]="#loadplugin Mail::SpamAssassin::Plugin::AccessDB";
$f[]="loadplugin  Mail::SpamAssassin::Plugin::SPF";


 @file_put_contents("/etc/spamassassin/v320.pre",@implode("\n",$f));
 if(is_dir("/etc/mail/spamassassin")){@file_put_contents("/etc/mail/spamassassin/v320.pre",@implode("\n",$f));}
 echo "Starting......: ".date("H:i:s")." spamassassin v320.pre success\n";
}

function PhishTag(){
$sock=new sockets();
$EnablePhishTag=$sock->GET_INFO("EnablePhishTag");
@unlink("/etc/spamassassin/PhishTag.cf");
@unlink("/etc/mail/spamassassin/PhishTag.cf");
if($EnablePhishTag<>1){return;}
$PhishTagRatio=$sock->GET_INFO("PhishTagRatio");
if($PhishTagRatio==null){$PhishTagRatio="0.1";}
$PhishTagURL="http://www.antiphishing.org/consumer_recs.html";

$f[]="trigger_ratio	   $PhishTagRatio";
$f[]="rawbody      __HAS_SCRIPT /<SCRIPT|on((un)?load|(dbl)?click|mouse(down|up|over|move|out)|blur|key(press|down|up)|submit|reset|select|change)/i";
$f[]="meta      HARD_URL (( URIBL_BLACK && ! __HAS_SCRIPT) && (HTTPS_IP_MISMATCH))";
$f[]="score	  HARD_URL 0";
$f[]="trigger_target	   HARD_URL	http://www.antiphishing.org/consumer_recs.html";
$f[]="meta	  EASY_URL (( URIBL_BLACK && ! __HAS_SCRIPT) && (SPOOF_COM2COM || SPOOF_NET2COM))";
$f[]="score	  EASY_URL 0";
$f[]="trigger_target	   EASY_URL	$PhishTagURL";
$f[]="";

@file_put_contents("/etc/spamassassin/PhishTag.cf",@implode("\n",$f));
if(is_dir("/etc/mail/spamassassin")){@file_put_contents("/etc/mail/spamassassin/PhishTag.cf",@implode("\n",$f));}
}


function spf(){

	$sock=new sockets();
	$EnableSPF=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSPF"));
	if($EnableSPF==null){$EnableSPF=0;}
	
	
	
	$Config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssassinSPFConfig")));
	if($GLOBALS["VERBOSE"]){print_r($Config);}
	
	if(!is_array($Config)){$Config=array();}
	if($Config["SPF_PASS_1"]==null){$Config["SPF_PASS_1"]="-0.001";}
	if($Config["SPF_PASS_2"]==null){$Config["SPF_PASS_2"]="-";}
	if($Config["SPF_PASS_3"]==null){$Config["SPF_PASS_3"]="-";}
	if($Config["SPF_PASS_4"]==null){$Config["SPF_PASS_4"]="-";}	
	if($Config["SPF_HELO_PASS_1"]==null){$Config["SPF_HELO_PASS_1"]="-0.001";}
	if($Config["SPF_HELO_PASS_2"]==null){$Config["SPF_HELO_PASS_2"]="-";}
	if($Config["SPF_HELO_PASS_3"]==null){$Config["SPF_HELO_PASS_3"]="-";}
	if($Config["SPF_HELO_PASS_4"]==null){$Config["SPF_HELO_PASS_4"]="-";}	
	if($Config["SPF_FAIL_1"]==null){$Config["SPF_FAIL_1"]="0";}
	if($Config["SPF_FAIL_2"]==null){$Config["SPF_FAIL_2"]="1.333";}
	if($Config["SPF_FAIL_3"]==null){$Config["SPF_FAIL_3"]="0";}
	if($Config["SPF_FAIL_4"]==null){$Config["SPF_FAIL_4"]="1.142";}	
	if($Config["SPF_HELO_FAIL_1"]==null){$Config["SPF_HELO_FAIL_1"]="0";}
	if($Config["SPF_HELO_FAIL_2"]==null){$Config["SPF_HELO_FAIL_2"]="-";}
	if($Config["SPF_HELO_FAIL_3"]==null){$Config["SPF_HELO_FAIL_3"]="-";}
	if($Config["SPF_HELO_FAIL_4"]==null){$Config["SPF_HELO_FAIL_4"]="-";}		
	if($Config["SPF_HELO_NEUTRAL_1"]==null){$Config["SPF_HELO_NEUTRAL_1"]="0";}
	if($Config["SPF_HELO_NEUTRAL_2"]==null){$Config["SPF_HELO_NEUTRAL_2"]="-";}
	if($Config["SPF_HELO_NEUTRAL_3"]==null){$Config["SPF_HELO_NEUTRAL_3"]="-";}
	if($Config["SPF_HELO_NEUTRAL_4"]==null){$Config["SPF_HELO_NEUTRAL_4"]="-";}			
	if($Config["SPF_NEUTRAL_1"]==null){$Config["SPF_NEUTRAL_1"]="0";}
	if($Config["SPF_NEUTRAL_2"]==null){$Config["SPF_NEUTRAL_2"]="1.379";}
	if($Config["SPF_NEUTRAL_3"]==null){$Config["SPF_NEUTRAL_3"]="0";}
	if($Config["SPF_NEUTRAL_4"]==null){$Config["SPF_NEUTRAL_4"]="1.069";}			
	if($Config["SPF_SOFTFAIL_1"]==null){$Config["SPF_SOFTFAIL_1"]="0";}
	if($Config["SPF_SOFTFAIL_2"]==null){$Config["SPF_SOFTFAIL_2"]="1.470";}
	if($Config["SPF_SOFTFAIL_3"]==null){$Config["SPF_SOFTFAIL_3"]="0";}
	if($Config["SPF_SOFTFAIL_4"]==null){$Config["SPF_SOFTFAIL_4"]="1.384";}	
	if($Config["SPF_HELO_SOFTFAIL_1"]==null){$Config["SPF_HELO_SOFTFAIL_1"]="0";}
	if($Config["SPF_HELO_SOFTFAIL_2"]==null){$Config["SPF_HELO_SOFTFAIL_2"]="2.078";}
	if($Config["SPF_HELO_SOFTFAIL_3"]==null){$Config["SPF_HELO_SOFTFAIL_3"]="0";}
	if($Config["SPF_HELO_SOFTFAIL_4"]==null){$Config["SPF_HELO_SOFTFAIL_4"]="2.432";}
	
	while (list ($key, $val) = each ($Config) ){
		if($val=="-"){$Config[$key]=null;}
	}
	

$conf[]="ifplugin Mail::SpamAssassin::Plugin::SPF";
$conf[]=trim("score SPF_PASS {$Config["SPF_PASS_1"]} {$Config["SPF_PASS_2"]} {$Config["SPF_PASS_3"]} {$Config["SPF_PASS_4"]}");
$conf[]=trim("score SPF_HELO_PASS {$Config["SPF_HELO_PASS_1"]} {$Config["SPF_HELO_PASS_2"]} {$Config["SPF_HELO_PASS_3"]} {$Config["SPF_HELO_PASS_4"]}");
$conf[]=trim("score SPF_FAIL {$Config["SPF_FAIL_1"]} {$Config["SPF_FAIL_2"]} {$Config["SPF_FAIL_3"]} {$Config["SPF_FAIL_4"]}");
$conf[]=trim("score SPF_HELO_FAIL {$Config["SPF_HELO_FAIL_1"]} {$Config["SPF_HELO_FAIL_2"]} {$Config["SPF_HELO_FAIL_3"]} {$Config["SPF_HELO_FAIL_4"]}");
$conf[]=trim("score SPF_HELO_NEUTRAL {$Config["SPF_HELO_NEUTRAL_1"]} {$Config["SPF_HELO_NEUTRAL_2"]} {$Config["SPF_HELO_NEUTRAL_3"]} {$Config["SPF_HELO_NEUTRAL_4"]}");
$conf[]=trim("score SPF_HELO_SOFTFAIL {$Config["SPF_HELO_SOFTFAIL_1"]} {$Config["SPF_HELO_SOFTFAIL_2"]} {$Config["SPF_HELO_SOFTFAIL_3"]} {$Config["SPF_HELO_SOFTFAIL_4"]}");
$conf[]=trim("score SPF_NEUTRAL {$Config["SPF_NEUTRAL_1"]} {$Config["SPF_NEUTRAL_2"]} {$Config["SPF_NEUTRAL_3"]} {$Config["SPF_NEUTRAL_4"]}");
$conf[]=trim("score SPF_SOFTFAIL {$Config["SPF_SOFTFAIL_1"]} {$Config["SPF_SOFTFAIL_2"]} {$Config["SPF_SOFTFAIL_3"]} {$Config["SPF_SOFTFAIL_4"]}");
$conf[]="";
$conf[]="header USER_IN_SPF_WHITELIST	eval:check_for_spf_whitelist_from()";
$conf[]="describe USER_IN_SPF_WHITELIST	From: address is in the user's SPF whitelist";
$conf[]="tflags USER_IN_SPF_WHITELIST	userconf nice noautolearn net";
$conf[]="";
$conf[]="header USER_IN_DEF_SPF_WL	eval:check_for_def_spf_whitelist_from()";
$conf[]="describe USER_IN_DEF_SPF_WL	From: address is in the default SPF white-list";
$conf[]="tflags USER_IN_DEF_SPF_WL	userconf nice noautolearn net";
$conf[]="";
$conf[]="header __ENV_AND_HDR_FROM_MATCH	eval:check_for_matching_env_and_hdr_from()";
$conf[]="meta ENV_AND_HDR_SPF_MATCH	(USER_IN_DEF_SPF_WL && __ENV_AND_HDR_FROM_MATCH)";
$conf[]="describe ENV_AND_HDR_SPF_MATCH	Env and Hdr From used in default SPF WL Match";
$conf[]="tflags ENV_AND_HDR_SPF_MATCH	userconf nice noautolearn net";
$conf[]="";
$conf[]="";	

$conf[]="";

	$q=new mysql();
	$sql="SELECT * FROM spamassassin_spf_wl ORDER BY ID DESC";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){
		echo "Starting......: ".date("H:i:s")." spamassassin Mysql fatal error !\n";
		return; 
	}
	$count=0;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$count=$count+1;
		$conf[]="def_whitelist_from_spf   {$ligne["domain"]}";
		
	}
$conf[]="endif";
$conf[]="";	
@file_put_contents("/etc/spamassassin/spf.pre",@implode("\n",$conf));
echo "Starting......: ".date("H:i:s")." spamassassin writing spf.pre done ($count whitelisted sender(s))\n";


}

function FromAndNotReplyTo(){
	$f[]="package FromNotReplyTo;";
	$f[]="1;";
	$f[]="";
	$f[]="use strict;";
	$f[]="";
	$f[]="use Mail::SpamAssassin;";
	$f[]="use Mail::SpamAssassin::Plugin;";
	$f[]="our @ISA = qw(Mail::SpamAssassin::Plugin);";
	$f[]="";
	$f[]="";
	$f[]="sub new {";
	$f[]="        my (\$class, \$mailsa) = @_;";
	$f[]="        \$class = ref(\$class) || \$class;";
	$f[]="        my \$self = \$class->SUPER::new( \$mailsa );";
	$f[]="        bless (\$self, \$class);";
	$f[]="        \$self->register_eval_rule ( 'check_for_from_not_reply_to' );";
	$f[]="        ";
	$f[]="        return \$self;";
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="# Often spam uses different From: and Reply-To:";
	$f[]="# while most legitimate e-mails does not.";
	$f[]="sub check_for_from_not_reply_to {";
	$f[]="        my (\$self, \$msg) = @_;";
	$f[]="";
	$f[]="        my \$from = \$msg->get( 'From:addr' );";
	$f[]="        my \$replyTo = \$msg->get( 'Reply-To:addr' );";
	$f[]="";
	$f[]="        #Mail::SpamAssassin::Plugin::dbg( \"FromNotReplyTo: Comparing '\$from'/'\$replyTo\" );";
	$f[]="";
	$f[]="        if ( \$from ne '' && \$replyTo ne '' && \$from ne \$replyTo ) {";
	$f[]="                return 1;";
	$f[]="        }";
	$f[]="";
	$f[]="        return 0;";
	$f[]="}\n";
	
	@file_put_contents("/etc/spamassassin/FromNotReplyTo.pm",@implode("\n",$f));
	if(is_dir("/etc/mail/spamassassin")){@file_put_contents("/etc/mail/spamassassin/FromNotReplyTo.pm",@implode("\n",$f));}
	echo "Starting......: ".date("H:i:s")." spamassassin /etc/spamassassin/FromNotReplyTo.pm done\n";
	$f=array();
	
	$f[]="loadplugin FromNotReplyTo /etc/spamassassin/FromNotReplyTo.pm";
	$f[]="header FROM_NOT_REPLYTO eval:check_for_from_not_reply_to()";
	$f[]="score FROM_NOT_REPLYTO 2.0";
	$f[]="describe FROM_NOT_REPLYTO From: does not match Reply-To:\n";
	
	@file_put_contents("/etc/spamassassin/FromNotReplyTo.pre",@implode("\n",$f));
	if(is_dir("/etc/mail/spamassassin")){@file_put_contents("/etc/mail/spamassassin/FromNotReplyTo.pre",@implode("\n",$f));}
	echo "Starting......: ".date("H:i:s")." spamassassin /etc/spamassassin/FromNotReplyTo.pre done\n";
		
}


function WrongMX(){
$mx[]="package WrongMX;";
$mx[]="use strict;";
$mx[]="use Mail::SpamAssassin;";
$mx[]="use Mail::SpamAssassin::Plugin;";
$mx[]="use Net::DNS;";
$mx[]="our @ISA = qw(Mail::SpamAssassin::Plugin);";
$mx[]="";
$mx[]="sub new {";
$mx[]="  my (\$class, \$mailsa) = @_;";
$mx[]="  \$class = ref(\$class) || \$class;";
$mx[]="  my \$self = \$class->SUPER::new(\$mailsa);";
$mx[]="  bless (\$self, \$class);";
$mx[]="  \$self->register_eval_rule(\"wrongmx\");";
$mx[]="  return \$self;";
$mx[]="}";
$mx[]="";
$mx[]="sub wrongmx {";
$mx[]="  my (\$self, \$permsgstatus) = @_;";
$mx[]="  my \$MAXTIMEDIFF = 30;";
$mx[]="";
$mx[]="  return 0 if \$self->{main}->{local_tests_only}; # in case plugins ever get called";
$mx[]="";
$mx[]="  # if a user set dns_available to no we shouldn't be doing MX lookups";
$mx[]="  return 0 unless \$permsgstatus->is_dns_available();";
$mx[]="";
$mx[]="  # avoid FPs (and wasted processing) by not checking when all_trusted";
$mx[]="  return 0 if \$permsgstatus->check_all_trusted;";
$mx[]="";
$mx[]="  # if there is only one received header we can bail";
$mx[]="  my \$times_ref = (\$permsgstatus->{received_header_times});";
$mx[]="  return 0 if (!defined(\$times_ref) || scalar(@\$times_ref) < 2); # if it only hit one server we're done";
$mx[]="";
$mx[]="  # next we need the recipient domain's MX records... who's the recipient";
$mx[]="  my \$recipient_domain;";
$mx[]="  if (\$self->{main}->{username} =~ /\@(\S+\.\S+)/) {";
$mx[]="    \$recipient_domain = \$1;";
$mx[]="  } else {";
$mx[]="    foreach my \$to (\$permsgstatus->all_to_addrs) {";
$mx[]="      next unless defined \$to;";
$mx[]="      \$to =~ tr/././s; # bug 3366?";
$mx[]="      if (\$to =~ /\@(\S+\.\S+)/) {";
$mx[]="        \$recipient_domain = \$1;";
$mx[]="        last;";
$mx[]="      }";
$mx[]="    }";
$mx[]="  }";
$mx[]="  return 0 unless defined \$recipient_domain;  # no domain means no MX records";
$mx[]="";
$mx[]="  # Now we need to get the recipient domain's MX records.";
$mx[]="  # We'll resolve the hosts so we can look for IP overlaps.";
$mx[]="  my \$res = Net::DNS::Resolver->new;";
$mx[]="  my @rmx = mx(\$res, \$recipient_domain);";
$mx[]="  my %mx_prefs;";
$mx[]="  if (@rmx) {";
$mx[]="    foreach my \$rr (@rmx) {";
$mx[]="      unless (exists \$mx_prefs{\$rr->exchange} && \$mx_prefs{\$rr->exchange} < \$rr->preference) {";
$mx[]="        \$mx_prefs{\$rr->exchange} = \$rr->preference;";
$mx[]="      }";
$mx[]="      my @ips = \$permsgstatus->lookup_a(\$rr->exchange);";
$mx[]="      next unless @ips;";
$mx[]="      foreach my \$ip (@ips) {";
$mx[]="        unless (exists \$mx_prefs{\$ip} && \$mx_prefs{\$ip} < \$rr->preference) {";
$mx[]="          \$mx_prefs{\$ip} = \$rr->preference;";
$mx[]="        }";
$mx[]="      }";
$mx[]="    }";
$mx[]="  } else {";
$mx[]="    return 0; # no recipient domain MX records found, no way to check MX flow";
$mx[]="  }";
$mx[]="";
$mx[]="  # get relay hosts";
$mx[]="  my @relays;";
$mx[]="  foreach my \$rcvd (@{\$permsgstatus->{relays_trusted}}, @{\$permsgstatus->{relays_untrusted}}) {";
$mx[]="    push @relays, \$rcvd->{by};";
$mx[]="  }";
$mx[]="  return 0 if (!scalar(@relays)); # this probably won't happen, but whatever";
$mx[]="";
$mx[]="  # Bail if we don't have the same number of relays and times, or if we have";
$mx[]="  # fewer preferences than times (or relays).";
$mx[]="  return 0 if (scalar(@relays) != scalar(@\$times_ref) || scalar(@\$times_ref) > scalar(keys(%mx_prefs)));";
$mx[]="";
$mx[]="  # Check to see if a higher preference relay passes mail to a lower";
$mx[]="  # preference relay within \$MAXTIMEDIFF seconds.  If we do decide that a message";
$mx[]="  # has done this, wait till AFTER we lookup the sender domain's MX records";
$mx[]="  # to return 1 since there may be MX overlaps that we'll bail on... see below.";
$mx[]="  # We could do the sender domain MX lookups first, but we might as well save";
$mx[]="  # the overhead if we're going to end up bailing anyway (\$hits == 0).";
$mx[]="";
$mx[]="  # We'll go through backwards so that we can detect weird local configs";
$mx[]="  # that pass mail from the primary MX to the secondary MX for spam/virus";
$mx[]="  # scanning, or even final delivery.  See BACKWARDS comment below.";
$mx[]="";
$mx[]="  # We'll resolve the 'by' hosts found to see if they match any of our";
$mx[]="  # resolved MX hosts' IPs.";
$mx[]="";
$mx[]="  my \$hits = 0;";
$mx[]="  my \$last_pref;";
$mx[]="  my \$last_time;";
$mx[]="  foreach (my \$i = \$#relays; \$i >= 0; \$i--) {";
$mx[]="    my \$MX = 0;";
$mx[]="    if (exists(\$mx_prefs{\$relays[\$i]})) {";
$mx[]="      \$MX = \$relays[\$i];";
$mx[]="    } else {";
$mx[]="      my @ips = \$permsgstatus->lookup_a(\$relays[\$i]);";
$mx[]="      next unless @ips;";
$mx[]="";
$mx[]="      foreach my \$ip (@ips) {";
$mx[]="        if ( exists \$mx_prefs{\$ip} ) {";
$mx[]="         \$MX = \$ip;";
$mx[]="          last;";
$mx[]="        }";
$mx[]="      }";
$mx[]="    }";
$mx[]="    if (\$MX) {";
$mx[]="      if (defined (\$last_pref) && defined (\$last_time)) {";
$mx[]="        # BACKWARDS -- uncomment the next line if you need to pass mail from a";
$mx[]="        # higher pref MX to a lower MX (for virus scanning/etc) AND back,";
$mx[]="        # before SA sees it... this opens you up to FNs with forged headers";
$mx[]="     #   last if (\$mx_prefs{\$MX} > \$last_pref);";
$mx[]="";
$mx[]="        \$hits++ if (\$mx_prefs{\$MX} < \$last_pref";
$mx[]="          && (\$last_time - \$MAXTIMEDIFF <= @\$times_ref[\$i] && @\$times_ref[\$i] <= \$last_time + \$MAXTIMEDIFF) ); # within max time diff";
$mx[]="      }";
$mx[]="      \$last_pref = \$mx_prefs{\$MX};";
$mx[]="      \$last_time = @\$times_ref[\$i];";
$mx[]="    }";
$mx[]="    last if \$hits;";
$mx[]="  }";
$mx[]="";
$mx[]="  # Determine the sender's domain.";
$mx[]="  # Don't bail if we can't determine the sender since it's probably spam.";
$mx[]="  my \$sender_domain;";
$mx[]="  foreach my \$from (\$permsgstatus->get('EnvelopeFrom:addr')) {";
$mx[]="    next unless defined \$from;";
$mx[]="    \$from =~ tr/././s; # bug 3366?";
$mx[]="    if (\$from =~ /\@(\S+\.\S+)/) {";
$mx[]="      \$sender_domain = \$1;";
$mx[]="      last;";
$mx[]="    }";
$mx[]="  }";
$mx[]="  if (defined \$sender_domain) {";
$mx[]="    if (\$sender_domain) {";
$mx[]="      my @smx = mx(\$res, \$sender_domain);";
$mx[]="      if (@smx) {";
$mx[]="        foreach my \$srr (@smx) {";
$mx[]="          foreach my \$rrr (@rmx) {";
$mx[]="            return 0 if (\$rrr->exchange eq \$srr->exchange);";
$mx[]="          }";
$mx[]="          my @sips = \$permsgstatus->lookup_a(\$srr->exchange);";
$mx[]="          foreach my \$sip (@sips) {";
$mx[]="            foreach my \$rip (keys %mx_prefs) {";
$mx[]="              return 0 if (\$rip eq \$sip);";
$mx[]="            }";
$mx[]="          }";
$mx[]="        }";
$mx[]="      }";
$mx[]="    }";
$mx[]="  }";
$mx[]="";
$mx[]="  return 1 if \$hits;";
$mx[]="  return 0;";
$mx[]="}";
$mx[]="";
$mx[]="1;";
$mx[]="";

@file_put_contents("/etc/spamassassin/wrongmx.pm",@implode("\n",$mx));
if(is_dir("/etc/mail/spamassassin")){@file_put_contents("/etc/mail/spamassassin/wrongmx.pm",@implode("\n",$mx));}

}


function DecodeShortURLs(){
	$sock=new sockets();
	$EnableDecodeShortURLs=$sock->GET_INFO("EnableDecodeShortURLs");
	if($EnableDecodeShortURLs==null){$EnableDecodeShortURLs=0;}	
	if($EnableDecodeShortURLs<>1){
		@file_put_contents("/etc/spamassassin/DecodeShortURLs.pre","");
		echo "Starting......: ".date("H:i:s")." spamassassin writing DecodeShortURLs.pre inbound verification disabled\n";
		return ;
	}

	if(!is_file("/etc/spamassassin/DecodeShortURLs.pm")){
		if(is_file("/usr/share/artica-postfix/bin/install/spamassassin/DecodeShortURLs.pm")){
			@copy("/usr/share/artica-postfix/bin/install/spamassassin/DecodeShortURLs.pm","/etc/spamassassin/DecodeShortURLs.pm");
		}
	}
	
	if(!is_file("/etc/spamassassin/DecodeShortURLs.pm")){
		echo "Starting......: ".date("H:i:s")." spamassassin writing DecodeShortURLs.pre DecodeShortURLs.pm no such file\n";
		return;
	}
	
	
$f[]="loadplugin Mail::SpamAssassin::Plugin::DecodeShortURLs /etc/spamassassin/DecodeShortURLs.pm";
$f[]="";
$f[]="body HAS_SHORT_URL	eval:short_url_tests()";
$f[]="describe HAS_SHORT_URL	Message contains one or more shortened URLs";
$f[]="score HAS_SHORT_URL	0.01";
$f[]="";
$f[]="body SHORT_URL_CHAINED	eval:short_url_tests()";
$f[]="describe SHORT_URL_CHAINED	Message has shortened URL chained to other shorteners";
$f[]="score SHORT_URL_CHAINED	3.0";
$f[]="";
$f[]="body SHORT_URL_MAXCHAIN	eval:short_url_tests()";
$f[]="describe SHORT_URL_MAXCHAIN	Message has shortened URL that causes more than 10 redirections";
$f[]="score SHORT_URL_MAXCHAIN	5.0";
$f[]="";
$f[]="body SHORT_URL_LOOP	eval:short_url_tests()";
$f[]="describe SHORT_URL_LOOP	Message has short URL that loops back to itself";
$f[]="score SHORT_URL_LOOP	0.01";
$f[]="";
$f[]="body SHORT_URL_404	eval:short_url_tests()";
$f[]="describe SHORT_URL_404	Message has short URL that returns 404";
$f[]="score SHORT_URL_404	1.0";
$f[]="";
$f[]="uri  URI_BITLY_BLOCKED	/^http:\/\/bit\.ly\/a\/warning/i";
$f[]="describe URI_BITLY_BLOCKED Message contains a bit.ly URL that has been disabled due to abuse";
$f[]="score URI_BITLY_BLOCKED 10.0";
$f[]="";
$f[]="uri  URI_SIMURL_BLOCKED /^http:\/\/simurl\.com\/redirect_black\.php/i";
$f[]="describe URI_SIMURL_BLOCKED Message contains a simurl URL that has been disabled due to abuse";
$f[]="score URI_SIMURL_BLOCKED 10.0";
$f[]="";
$f[]="meta SHORT_URIBL	HAS_SHORT_URL && (URIBL_BLACK || URIBL_AB_SURBL || URIBL_WS_SURBL || URIBL_JP_SURBL || URIBL_SC_SURBL || URIBL_RHS_DOB || URIBL_DBL_SPAM || URIBL_SBL)";
$f[]="describe SHORT_URIBL	Message contains shortened URL(s) and also hits a URIDNSBL";
$f[]="score SHORT_URIBL	0.01";
$f[]="";
$f[]="url_shortener_log /tmp/DecodeShortURLs.txt";
$f[]="url_shortener_cache /tmp/DecodeShortURLs.sq3";
$f[]="url_shortener_syslog 1";
$f[]="";

DecodeShortURLsCheck();

	$sql="SELECT `value` FROM  spamassassin_table WHERE spam_type='DecodeShortURLs' AND `enabled`=1 ORDER BY `value`";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$count=0;
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$count=$count+1;
		$f[]="url_shortener {$ligne["value"]}";
	}

	
@file_put_contents("/etc/spamassassin/DecodeShortURLs.pre",@implode("\n",$f));
if(is_dir("/etc/mail/spamassassin")){@file_put_contents("/etc/mail/spamassassin/DecodeShortURLs.pre",@implode("\n",$f));}
echo "Starting......: ".date("H:i:s")." spamassassin writing DecodeShortURLs.pre ($count rows) done\n";		
	
}


function DecodeShortURLsCheck(){

	
$f=array("0rz.tw","1l2.us","1u.ro","1url.com","2.gp","2.ly","2chap.it","2pl.us","2su.de","2tu.us","2ze.us","3.ly","301.to","301url.com","307.to","6url.com",
"7.ly","9mp.com","a.gd","a.gg","a.nf","a2a.me","a2n.eu","abbr.com","abe5.com","access.im","ad.vu","adf.ly","adjix.com","alturl.com","amzn.com","amzn.to",
"arm.in","asso.in","atu.ca","aurls.info","awe.sm","ayl.lv","azqq.com","b23.ru","b65.com","b65.us","bacn.me","beam.to","bgl.me","bit.ly","bkite.com",
"blippr.com","bloat.me","blu.cc","bon.no","bt.io","budurl.com","buk.me","burnurl.com","c-o.in","c.shamekh.ws","canurl.com","cd4.me","chilp.it","chopd.it",
"chpt.me","chs.mx","chzb.gr","clck.ru","cli.gs","cliccami.info","clickthru.ca","clipurl.us","clk.my","clop.in","clp.ly","coge.la","cokeurl.com",
"cort.as","cot.ag","crum.pl","curio.us","cuthut.com","cuturl.com","cuturls.com","dealspl.us","decenturl.com","df9.net","digbig.com","digg.com",
"digipills.com","digs.by","dld.bz","dlvr.it","dn.vc","doi.org","doiop.com","dr.tl","durl.me","durl.us","dvlr.it","dwarfurl.com","easyurl.net","eca.sh",
"eclurl.com","eepurl.com","eezurl.com","ewerl.com","ezurl.eu","fa.by","faceto.us","fav.me","fb.me","ff.im","fff.to","fhurl.com","flic.kr","flingk.com",
"flq.us","fly2.ws","fon.gs","foxyurl.com","fuseurl.com","fwd4.me","fwdurl.net","fwib.net","g8l.us","get-shorty.com","get-url.com","get.sh","gi.vc","gkurl.us",
"gl.am","go.9nl.com","go.to","go2.me","golmao.com","goo.gl","good.ly","goshrink.com","gri.ms","gurl.es","hao.jp","hellotxt.com","hex.io","hiderefer.com",
"hop.im","hotredirect.com","hotshorturl.com","href.in","ht.ly","htxt.it","hugeurl.com","hurl.it","hurl.no","hurl.ws","icanhaz.com","icio.us","idek.net",
"ikr.me","ir.pe","irt.me","is.gd","iscool.net","it2.in","ito.mx","j.mp","j2j.de","jdem.cz","jijr.com","just.as","k.vu","ketkp.in","kisa.ch","kissa.be",
"kl.am","klck.me","kore.us","korta.nu","kots.nu","krz.ch","ktzr.us","kxk.me","l.pr","l9k.net","liip.to","liltext.com","lin.cr","lin.io","linkbee.com",
"linkee.com","linkgap.com","linkslice.com","linxfix.de","liteurl.net","liurl.cn","livesi.de","lix.in","lk.ht","ln-s.net","ln-s.ru","lnk.by","lnk.in",
"lnk.ly","lnk.ms","lnk.sk","lnkurl.com","loopt.us","lost.in","lru.jp","lt.tl","lu.to","lurl.no","mavrev.com","memurl.com","merky.de","metamark.net",
"migre.me","min2.me","minilien.com","minilink.org","miniurl.com","minurl.fr","moby.to","moourl.com","msg.sg","murl.kz","mv2.me","mysp.in","myurl.in",
"myurl.si","nanoref.com","nanourl.se","nbx.ch","ncane.com","ndurl.com","ne1.net","netnet.me","netshortcut.com","ni.to","nig.gr","nm.ly","nn.nf",
"notlong.com","nutshellurl.com","nyti.ms","o-x.fr","o.ly","oboeyasui.com","offur.com","ofl.me","om.ly","omf.gd","onecent.us","onion.com","onsaas.info",
"ooqx.com","oreil.ly","ow.ly","oxyz.info","p.ly","p8g.tw","parv.us","paulding.net","pduda.mobi","peaurl.com","pendek.in","pep.si","pic.gd","piko.me",
"ping.fm","piurl.com","plumurl.com","plurl.me","pnt.me","poll.fm","pop.ly","poprl.com","post.ly","posted.at","pt2.me","ptiturl.com","puke.it","pysper.com",
"qik.li","qlnk.net","qoiob.com","qr.cx","quickurl.co.uk","qurl.com","qurlyq.com","quu.nu","qux.in","r.im","rb6.me","rde.me","readthis.ca","reallytinyurl.com",
"redir.ec","redirects.ca","redirx.com","relyt.us","retwt.me","ri.ms","rickroll.it","rivva.de","rly.cc","rnk.me","rsmonkey.com","rt.nu","rubyurl.com",
"rurl.org","s.gnoss.us","s3nt.com","s4c.in","s7y.us","safe.mn","safelinks.ru","sai.ly","SameURL.com","sfu.ca","shadyurl.com","shar.es","shim.net","shink.de",
"shorl.com","short.ie","short.to","shorten.ws","shortenurl.com","shorterlink.com","shortio.com","shortlinks.co.uk","shortn.me","shortna.me","shortr.me",
"shorturl.com","shortz.me","shoturl.us","shredu","shredurl.com","shrinkify.com","shrinkr.com","shrinkster.com","shrinkurl.us","shrt.fr","shrt.ws","shrtl.com",
"shrtn.com","shrtnd.com","shurl.net","shw.me","simurl.com","simurl.net","simurl.org","simurl.us","sitelutions.com","siteo.us","sl.ly","slidesha.re","slki.ru",
"smallr.com","smallr.net","smfu.in","smsh.me","smurl.com","sn.im","sn.vc","snadr.it","snipie.com","snipr.com","snipurl.com","snkr.me","snurl.com","song.ly",
"sp2.ro","spedr.com","sqze.it","srnk.net","srs.li","starturl.com","stickurl.com","stpmvt.com","sturly.com","su.pr","surl.co.uk","surl.it","t.co","t.lh.com",
"ta.gd","takemyfile.com","tcrn.ch","tgr.me","th8.us","thecow.me","thrdl.es","tighturl.com","timesurl.at","tini.us","tiniuri.com","tiny.cc","tiny.pl",
"tinyarro.ws","tinylink.com","tinypl.us","tinysong.com","tinytw.it","tinyurl.com","tl.gd","tllg.net","tncr.ws","tnw.to","to.je","to.ly","to.vg","togoto.us",
"tr.im","tr.my","tra.kz","traceurl.com","trcb.me","trg.li","trick.ly","trii.us","trim.li","trumpink.lt","trunc.it","truncurl.com","tsort.us","tubeurl.com",
"turo.us","tw0.us","tw1.us","tw2.us","tw5.us","tw6.us","tw8.us","tw9.us","twa.lk","tweet.me","tweetburner.com","tweetl.com","twi.gy","twip.us","twirl.at",
"twit.ac","twitclicks.com","twitterurl.net","twitthis.com","twittu.ms","twiturl.de","twitzap.com","twlv.net","twtr.us","twurl.cc","twurl.nl","u.mavrev.com",
"u.nu","u76.org","ub0.cc","uiop.me","ulimit.com","ulu.lu","unfaker.it","updating.me","ur.ly","ur1.ca","urizy.com","url.ag","url.az","url.co.uk","url.go.it",
"url.ie","url.inc-x.eu","url.lotpatrol.com","urlao.com","urlbee.com","urlborg.com","urlbrief.com","urlcorta.es","urlcut.com","urlcutter.com","urlg.info",
"urlhawk.com","urli.nl","urlkiss.com","urloo.com","urlpire.com","urltea.com","urlu.ms","urlvi.b","urlvi.be","urlx.ie","urlz.at","urlzen.com","usat.ly",
"uservoice.com","ustre.am","vado.it","vb.ly","vdirect.com","vi.ly","viigo.im","virl.com","vl.am","voizle.com","vtc.es","w0r.me","w33.us","w34.us","w3t.org",
"wa9.la","wapurl.co.uk","webalias.com","welcome.to","wh.gov","wipi.es","wkrg.com","woo.ly","wp.me","x.hypem.com","x.se","x.vu","xeeurl.com","xil.in",
"xlurl.de","xr.com","xrl.in","xrl.us","xrt.me","xurl.jp","xxsurl.de","xzb.cc","yatuc.com","ye-s.com","yep.it","z.pe","zapt.in","zi.ma","zi.me","zi.pe",
"zip.li","zipmyurl.com","zootit.com","zud.me","zurl.ws","zz.gd","zzang.kr","xn--cwg.ws","xn--fwg.ws","xn--bih.ws","xn--l3h.ws","xn--1ci.ws","xn--odi.ws",
"xn--rei.ws","xn--3fi.ws","xn--egi.ws","xn--hgi.ws","xn--ogi.ws","xn--vgi.ws","xn--5gi.ws","xn--9gi.ws");	

$sql="SELECT COUNT(ID) as tcount FROM spamassassin_table WHERE spam_type='DecodeShortURLs'";
$q=new mysql();
$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
if($ligne["tcount"]>0){return;}

$sqlintro="INSERT INTO spamassassin_table (`spam_type`,`value`) VALUES ";
while (list ($key, $line) = each ($f) ){
	$sqls[]="('DecodeShortURLs','$line')";
}

$sql=$sqlintro.@implode(",",$sqls);
$q->QUERY_SQL($sql,"artica_backup");
if(!$q->ok){
	send_email_events("APP_SPAMASSASSIN:: DecodeShortURLs mysql error","Function :".__FUNCTION__."\nFile".basename(__FILE__)."\error:".$q->mysql_error,"postfix");
}


}


function dkim(){
	
	
	$sock=new sockets();
	$enable_dkim_verification=$sock->GET_INFO("enable_dkim_verification");
	@unlink("/etc/spamassassin/dkim.pre");
	@unlink("/etc/mail/spamassassin/dkim.pre");
	if($enable_dkim_verification<>1){
		echo "Starting......: ".date("H:i:s")." spamassassin writing dkim.pre inbound verification disabled\n";
		return ;
	}
$r[]="ifplugin Mail::SpamAssassin::Plugin::DKIM";	
$r[]="  score DKIM_VERIFIED -0.1";
$r[]="  score DKIM_SIGNED    0";
$r[]="";
$r[]="  # don't waste time on fetching ASP record, hardly anyone publishes it";
$r[]="  score DKIM_POLICY_SIGNALL  0";
$r[]="  score DKIM_POLICY_SIGNSOME 0";
$r[]="  score DKIM_POLICY_TESTING  0";
$r[]="";
$r[]="  # DKIM-based whitelisting of domains with good reputation:";
$r[]="  score USER_IN_DKIM_WHITELIST -8.0";
$r[]="";

	$q=new mysql();
	$sql="SELECT * FROM spamassassin_dkim_wl ORDER BY ID DESC";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){
		echo "Starting......: ".date("H:i:s")." spamassassin Mysql fatal error ! (DKIM)\n";
		return; 
	}
	$count=0;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$count=$count+1;
		$r[]="whitelist_from_dkim   {$ligne["domain"]}";
		
	}
$r[]="";
$r[]="  # DKIM-based whitelisting of domains with less then perfect";
$r[]="  # reputation can be given fewer negative score points:";
$r[]="  score USER_IN_DEF_DKIM_WL -1.5";
$r[]="  def_whitelist_from_dkim   *@google.com";
$r[]="  def_whitelist_from_dkim   *@googlemail.com";
$r[]="  def_whitelist_from_dkim   *@*  googlegroups.com";
$r[]="  def_whitelist_from_dkim   *@*  yahoogroups.com";
$r[]="  def_whitelist_from_dkim   *@*  yahoogroups.co.uk";
$r[]="  def_whitelist_from_dkim   *@*  yahoogroupes.fr";
$r[]="  def_whitelist_from_dkim   *@yousendit.com";
$r[]="  def_whitelist_from_dkim   *@meetup.com";
$r[]="  def_whitelist_from_dkim   dailyhoroscope@astrology.com";
$r[]="";
$r[]="  # reduce default scores, which are being abused";
$r[]="  score ENV_AND_HDR_DKIM_MATCH -0.1";
$r[]="  score ENV_AND_HDR_SPF_MATCH  -0.5";
$r[]="";
$r[]="  header   __ML1        Precedence =~ m{\b(list|bulk)\b}i";
$r[]="  header   __ML2        exists:List-Id";
$r[]="  header   __ML3        exists:List-Post";
$r[]="  header   __ML4        exists:Mailing-List";
$r[]="  header   __ML5        Return-Path:addr =~ m{^([^\@]+-(request|bounces|admin|owner)|owner-[^\@]+)(\@|\z)}mi";
$r[]="  meta     __VIA_ML     __ML1 || __ML2 || __ML3 || __ML4 || __ML5";
$r[]="  describe __VIA_ML     Mail from a mailing list";
$r[]="";
$r[]="  header   __AUTH_YAHOO1  From:addr =~ m{[\@.]yahoo\.com$}mi";
$r[]="  header   __AUTH_YAHOO2  From:addr =~ m{\@yahoo\.com\.(ar|au|br|cn|hk|mx|my|ph|sg|tw)$}mi";
$r[]="  header   __AUTH_YAHOO3  From:addr =~ m{\@yahoo\.co\.(id|in|jp|nz|th|uk)$}mi";
$r[]="  header   __AUTH_YAHOO4  From:addr =~ m{\@yahoo\.(ca|cn|de|dk|es|fr|gr|ie|it|no|pl|se)$}mi";
$r[]="  meta     __AUTH_YAHOO   __AUTH_YAHOO1 || __AUTH_YAHOO2 || __AUTH_YAHOO3 || __AUTH_YAHOO4";
$r[]="  describe __AUTH_YAHOO   Author claims to be from Yahoo";
$r[]="";
$r[]="  header   __AUTH_GMAIL   From:addr =~ m{\@gmail\.com$}mi";
$r[]="  describe __AUTH_GMAIL   Author claims to be from gmail.com";
$r[]="";
$r[]="  header   __AUTH_PAYPAL  From:addr =~ /[\@.]paypal\.(com|co\.uk)$/mi";
$r[]="  describe __AUTH_PAYPAL  Author claims to be from PayPal";
$r[]="";
$r[]="  header   __AUTH_EBAY    From:addr =~ /[\@.]ebay\.(com|at|be|ca|ch|de|ee|es|fr|hu|ie|in|it|nl|ph|pl|pt|se|co\.(kr|uk)|com\.(au|cn|hk|mx|my|sg))$/mi";
$r[]="  describe __AUTH_EBAY    Author claims to be from eBay";
$r[]="";
$r[]="  meta     NOTVALID_YAHOO !DKIM_VERIFIED && __AUTH_YAHOO && !__VIA_ML";
$r[]="  priority NOTVALID_YAHOO 500";
$r[]="  describe NOTVALID_YAHOO Claims to be from Yahoo but is not";
$r[]="";
$r[]="  meta     NOTVALID_GMAIL !DKIM_VERIFIED && __AUTH_GMAIL && !__VIA_ML";
$r[]="  priority NOTVALID_GMAIL 500";
$r[]="  describe NOTVALID_GMAIL Claims to be from gmail.com but is not";
$r[]="";
$r[]="  meta     NOTVALID_PAY   !DKIM_VERIFIED && (__AUTH_PAYPAL || __AUTH_EBAY)";
$r[]="  priority NOTVALID_PAY   500";
$r[]="  describe NOTVALID_PAY   Claims to be from PayPal or eBay, but is not";
$r[]="";
$r[]="  score    NOTVALID_YAHOO  2.8";
$r[]="  score    NOTVALID_GMAIL  2.8";
$r[]="  score    NOTVALID_PAY    6";
$r[]="";
$r[]="  # accept replies from abuse@yahoo.com even if not dkim/dk-signed:";
$r[]="  whitelist_from_rcvd abuse@yahoo.com          yahoo.com";
$r[]="  whitelist_from_rcvd MAILER-DAEMON@yahoo.com  yahoo.com";
$r[]="endif";	
$r[]="";
@file_put_contents("/etc/spamassassin/dkim.pre",@implode("\n",$r));
if(is_dir("/etc/mail/spamassassin")){@file_put_contents("/etc/mail/spamassassin/dkim.pre",@implode("\n",$r));}
echo "Starting......: ".date("H:i:s")." spamassassin writing dkim.pre done ($count whitelisted sender(s))\n";	
}

function dnsbl(){
	$sock=new sockets();
	$EnableSpamassassinURIDNSBL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSpamassassinURIDNSBL"));
	$datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamassassinDNSBL")));
	
	if($EnableSpamassassinURIDNSBL==0){
		echo "Starting......: ".date("H:i:s")." spamassassin EnableSpamassassinURIDNSBL -> FALSE!\n";
		@file_put_contents("/etc/spamassassin/dnsbl.pre","#");
		if(is_dir("/etc/mail/spamassassin")){@file_put_contents("/etc/mail/spamassassin/dnsbl.pre","#");}
		return ;			
		
	}
	
	$count=0;
	if(is_array($datas)){
		
		while (list ($key, $vlue) = each ($datas)){
			if($vlue==null){continue;}
			if($vlue==0){continue;}	
			$count=$count+1;
		}
	}
	
	
# SpamAssassin rules file: DNS blacklist tests";
$conf[]="";
$conf[]="# Please don't modify this file as your changes will be overwritten with";
$conf[]="# the next update. Use @@LOCAL_RULES_DIR@@/local.cf instead.";
$conf[]="# See 'perldoc Mail::SpamAssassin::Conf' for details.";
$conf[]="#";
$conf[]="# <@LICENSE>";
$conf[]="# Copyright 2004 Apache Software Foundation";
$conf[]="#";
$conf[]="# Licensed under the Apache License, Version 2.0 (the \"License\");";
$conf[]="# you may not use this file except in compliance with the License.";
$conf[]="# You may obtain a copy of the License at";
$conf[]="#";
$conf[]="#     http://www.apache.org/licenses/LICENSE-2.0";
$conf[]="#";
$conf[]="# Unless required by applicable law or agreed to in writing, software";
$conf[]="# distributed under the License is distributed on an \"AS IS\" BASIS,";
$conf[]="# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.";
$conf[]="# See the License for the specific language governing permissions and";
$conf[]="# limitations under the License.";
$conf[]="# </@LICENSE>";
$conf[]="#";
$conf[]="###########################################################################";
$conf[]="";
$conf[]="require_version @@VERSION@@";
$conf[]="";
$conf[]="# See the Mail::SpamAssassin::Conf manual page for details of how to use";
$conf[]="# check_rbl().";
$conf[]="";

$conf[]="# Spam sources";
$conf[]="header __RCVD_IN_MSPIKE eval:check_rbl('mspike-lastexternal', 'bl.mailspike.net.') ";
$conf[]="tflags __RCVD_IN_MSPIKE net";
$conf[]="";
$conf[]="# Bad senders";
$conf[]="header __RCVD_IN_MSPIKE_Z eval:check_rbl_sub('mspike-lastexternal', '^127\.0\.0\.2$')";
$conf[]="describe __RCVD_IN_MSPIKE_Z Spam wave participant";
$conf[]="tflags __RCVD_IN_MSPIKE_Z net";
$conf[]="header RCVD_IN_MSPIKE_L5 eval:check_rbl_sub('mspike-lastexternal', '^127\.0\.0\.10$')";
$conf[]="describe RCVD_IN_MSPIKE_L5 Very bad reputation (-5)";
$conf[]="tflags RCVD_IN_MSPIKE_L5 net";
$conf[]="header RCVD_IN_MSPIKE_L4 eval:check_rbl_sub('mspike-lastexternal', '^127\.0\.0\.11$')";
$conf[]="describe RCVD_IN_MSPIKE_L4 Bad reputation (-4)";
$conf[]="tflags RCVD_IN_MSPIKE_L4 net";
$conf[]="header RCVD_IN_MSPIKE_L3 eval:check_rbl_sub('mspike-lastexternal', '^127\.0\.0\.12$')";
$conf[]="describe RCVD_IN_MSPIKE_L3 Low reputation (-3)";
$conf[]="tflags RCVD_IN_MSPIKE_L3 net";
$conf[]="header RCVD_IN_MSPIKE_L2 eval:check_rbl_sub('mspike-lastexternal', '^127\.0\.0\.13$')";
$conf[]="describe RCVD_IN_MSPIKE_L2 Suspicious reputation (-2)";
$conf[]="tflags RCVD_IN_MSPIKE_L2 net";
$conf[]="";
$conf[]="# Good senders";
$conf[]="header RCVD_IN_MSPIKE_H5 eval:check_rbl_sub('mspikeg-firsttrusted', '^127\.0\.0\.20$')";
$conf[]="describe RCVD_IN_MSPIKE_H5 Excellent reputation (+5)";
$conf[]="tflags RCVD_IN_MSPIKE_H5 nice net";
$conf[]="header RCVD_IN_MSPIKE_H4 eval:check_rbl_sub('mspikeg-firsttrusted', '^127\.0\.0\.19$')";
$conf[]="describe RCVD_IN_MSPIKE_H4 Very Good reputation (+4)";
$conf[]="tflags RCVD_IN_MSPIKE_H4 nice net";
$conf[]="header RCVD_IN_MSPIKE_H3 eval:check_rbl_sub('mspikeg-firsttrusted', '^127\.0\.0\.18$')";
$conf[]="describe RCVD_IN_MSPIKE_H3 Good reputation (+3)";
$conf[]="tflags RCVD_IN_MSPIKE_H3 nice net";
$conf[]="header RCVD_IN_MSPIKE_H2 eval:check_rbl_sub('mspikeg-firsttrusted', '^127\.0\.0\.17$')";
$conf[]="describe RCVD_IN_MSPIKE_H2 Average reputation (+2)";
$conf[]="tflags RCVD_IN_MSPIKE_H2 nice net";
$conf[]="";
$conf[]="# *_L and *_Z may overlap, so account for that";
$conf[]="meta __RCVD_IN_MSPIKE_LOW RCVD_IN_MSPIKE_L5 || RCVD_IN_MSPIKE_L4 || RCVD_IN_MSPIKE_L3 || RCVD_IN_MSPIKE_L2";
$conf[]="meta RCVD_IN_MSPIKE_ZBI __RCVD_IN_MSPIKE_Z && !__RCVD_IN_MSPIKE_LOW";
$conf[]="";
$conf[]="# Scores";
$conf[]="score RCVD_IN_MSPIKE_ZBI 4.1";
$conf[]="score RCVD_IN_MSPIKE_L5 5.2";
$conf[]="score RCVD_IN_MSPIKE_L4 4.2";
$conf[]="score RCVD_IN_MSPIKE_L3 3.9";
$conf[]="score RCVD_IN_MSPIKE_L2 0.8";
$conf[]="score RCVD_IN_MSPIKE_H2 -0.5";
$conf[]="score RCVD_IN_MSPIKE_H3 -1.5";
$conf[]="score RCVD_IN_MSPIKE_H4 -2.5";
$conf[]="score RCVD_IN_MSPIKE_H5 -3.5";
$conf[]="";
$conf[]="header RCVD_IN_MSPIKE_BL eval:check_rbl('mspike-lastexternal', 'bl.mailspike.net.')";
$conf[]="tflags RCVD_IN_MSPIKE_BL net";
$conf[]="score RCVD_IN_MSPIKE_BL 3.5";
$conf[]="header RCVD_IN_MSPIKE_WL eval:check_rbl('mspike-lastexternal', 'wl.mailspike.net.')";
$conf[]="tflags RCVD_IN_MSPIKE_WL net";
$conf[]="score RCVD_IN_MSPIKE_WL -2.1";

$conf[]="# ---------------------------------------------------------------------------";
$conf[]="# Multizone / Multi meaning BLs first.";
$conf[]="#";
$conf[]="# Note that currently TXT queries cannot be used for these, since the";
$conf[]="# DNSBLs do not return the A type (127.0.0.x) as part of the TXT reply.";
$conf[]="# Well, at least NJABL doesn't, it seems, as of Apr 7 2003.";
$conf[]="";

if($datas["njabl"]==1){
	$conf[]="# ---------------------------------------------------------------------------";
	$conf[]="# NJABL";
	$conf[]="# URL: http://www.dnsbl.njabl.org/";
	$conf[]="";
	$conf[]="header __RCVD_IN_NJABL		eval:check_rbl('njabl', 'combined.njabl.org.')";
	$conf[]="describe __RCVD_IN_NJABL	Received via a relay in combined.njabl.org";
	$conf[]="tflags __RCVD_IN_NJABL		net";
	$conf[]="";
	$conf[]="header RCVD_IN_NJABL_RELAY	eval:check_rbl_sub('njabl', '127.0.0.2')";
	$conf[]="describe RCVD_IN_NJABL_RELAY	NJABL: sender is confirmed open relay";
	$conf[]="tflags RCVD_IN_NJABL_RELAY	net";
	$conf[]="";
	$conf[]="header RCVD_IN_NJABL_DUL	eval:check_rbl('njabl-notfirsthop', 'combined.njabl.org.', '127.0.0.3')";
	$conf[]="describe RCVD_IN_NJABL_DUL	NJABL: dialup sender did non-local SMTP";
	$conf[]="tflags RCVD_IN_NJABL_DUL	net";
	$conf[]="";
	$conf[]="header RCVD_IN_NJABL_SPAM	eval:check_rbl_sub('njabl', '127.0.0.4')";
	$conf[]="describe RCVD_IN_NJABL_SPAM	NJABL: sender is confirmed spam source";
	$conf[]="tflags RCVD_IN_NJABL_SPAM	net";
	$conf[]="";
	$conf[]="header RCVD_IN_NJABL_MULTI	eval:check_rbl_sub('njabl', '127.0.0.5')";
	$conf[]="describe RCVD_IN_NJABL_MULTI	NJABL: sent through multi-stage open relay";
	$conf[]="tflags RCVD_IN_NJABL_MULTI	net";
	$conf[]="";
	$conf[]="header RCVD_IN_NJABL_CGI	eval:check_rbl_sub('njabl', '127.0.0.8')";
	$conf[]="describe RCVD_IN_NJABL_CGI	NJABL: sender is an open formmail";
	$conf[]="tflags RCVD_IN_NJABL_CGI	net";
	$conf[]="";
	$conf[]="header RCVD_IN_NJABL_PROXY	eval:check_rbl_sub('njabl', '127.0.0.9')";
	$conf[]="describe RCVD_IN_NJABL_PROXY	NJABL: sender is an open proxy";
	$conf[]="tflags RCVD_IN_NJABL_PROXY	net";
	$conf[]="";
}

if($datas["SORBS"]==1){
	$conf[]="# ---------------------------------------------------------------------------";
	$conf[]="# SORBS";
	$conf[]="# transfers: both axfr and ixfr available";
	$conf[]="# URL: http://www.dnsbl.sorbs.net/";
	$conf[]="# pay-to-use: no";
	$conf[]="# delist: $50 fee for RCVD_IN_SORBS_SPAM, others have free retest on request";
	$conf[]="";
	$conf[]="header __RCVD_IN_SORBS		eval:check_rbl('sorbs', 'dnsbl.sorbs.net.')";
	$conf[]="describe __RCVD_IN_SORBS	SORBS: sender is listed in SORBS";
	$conf[]="tflags __RCVD_IN_SORBS		net";
	$conf[]="";
	$conf[]="header RCVD_IN_SORBS_HTTP	eval:check_rbl_sub('sorbs', '127.0.0.2')";
	$conf[]="describe RCVD_IN_SORBS_HTTP	SORBS: sender is open HTTP proxy server";
	$conf[]="tflags RCVD_IN_SORBS_HTTP	net";
	$conf[]="";
	$conf[]="header RCVD_IN_SORBS_MISC	eval:check_rbl_sub('sorbs', '127.0.0.3')";
	$conf[]="describe RCVD_IN_SORBS_MISC	SORBS: sender is open proxy server";
	$conf[]="tflags RCVD_IN_SORBS_MISC	net";
	$conf[]="";
	$conf[]="header RCVD_IN_SORBS_SMTP	eval:check_rbl_sub('sorbs', '127.0.0.4')";
	$conf[]="describe RCVD_IN_SORBS_SMTP	SORBS: sender is open SMTP relay";
	$conf[]="tflags RCVD_IN_SORBS_SMTP	net";
	$conf[]="";
	$conf[]="header RCVD_IN_SORBS_SOCKS	eval:check_rbl_sub('sorbs', '127.0.0.5')";
	$conf[]="describe RCVD_IN_SORBS_SOCKS	SORBS: sender is open SOCKS proxy server";
	$conf[]="tflags RCVD_IN_SORBS_SOCKS	net";
	$conf[]="";
	$conf[]="# delist: $50 fee";
	$conf[]="#header RCVD_IN_SORBS_SPAM	eval:check_rbl_sub('sorbs', '127.0.0.6')";
	$conf[]="#describe RCVD_IN_SORBS_SPAM	SORBS: sender is a spam source";
	$conf[]="#tflags RCVD_IN_SORBS_SPAM	net";
	$conf[]="";
	$conf[]="header RCVD_IN_SORBS_WEB	eval:check_rbl_sub('sorbs', '127.0.0.7')";
	$conf[]="describe RCVD_IN_SORBS_WEB	SORBS: sender is a abuseable web server";
	$conf[]="tflags RCVD_IN_SORBS_WEB	net";
	$conf[]="";
	$conf[]="header RCVD_IN_SORBS_BLOCK	eval:check_rbl_sub('sorbs', '127.0.0.8')";
	$conf[]="describe RCVD_IN_SORBS_BLOCK	SORBS: sender demands to never be tested";
	$conf[]="tflags RCVD_IN_SORBS_BLOCK	net";
	$conf[]="";
	$conf[]="header RCVD_IN_SORBS_ZOMBIE	eval:check_rbl_sub('sorbs', '127.0.0.9')";
	$conf[]="describe RCVD_IN_SORBS_ZOMBIE	SORBS: sender is on a hijacked network";
	$conf[]="tflags RCVD_IN_SORBS_ZOMBIE	net";
	$conf[]="";
	$conf[]="header RCVD_IN_SORBS_DUL	eval:check_rbl('sorbs-notfirsthop', 'dnsbl.sorbs.net.', '127.0.0.10')";
	$conf[]="describe RCVD_IN_SORBS_DUL	SORBS: sent directly from dynamic IP address";
	$conf[]="tflags RCVD_IN_SORBS_DUL	net";
	$conf[]="";
}

if($datas["Spamhaus"]==1){
	$conf[]="# ---------------------------------------------------------------------------";
	$conf[]="# Spamhaus SBL+XBL";
	$conf[]="#";
	$conf[]="# Spamhaus XBL contains both the Abuseat CBL (cbl.abuseat.org) and Blitzed";
	$conf[]="";
	$conf[]="header __RCVD_IN_SBL_XBL	eval:check_rbl('sblxbl', 'sbl-xbl.spamhaus.org.')";
	$conf[]="describe __RCVD_IN_SBL_XBL	Received via a relay in Spamhaus SBL+XBL";
	$conf[]="tflags __RCVD_IN_SBL_XBL	net";
	$conf[]="";
	$conf[]="# SBL is the Spamhaus Block List: http://www.spamhaus.org/sbl/";
	$conf[]="header RCVD_IN_SBL		eval:check_rbl_sub('sblxbl', '127.0.0.2')";
	$conf[]="describe RCVD_IN_SBL		Received via a relay in Spamhaus SBL";
	$conf[]="tflags RCVD_IN_SBL		net";
	$conf[]="";
	$conf[]="# XBL is the Exploits Block List: http://www.spamhaus.org/xbl/";
	$conf[]="header RCVD_IN_XBL		eval:check_rbl('sblxbl-notfirsthop', 'sbl-xbl.spamhaus.org.', '127.0.0.[456]')";
	$conf[]="describe RCVD_IN_XBL		Received via a relay in Spamhaus XBL";
	$conf[]="tflags RCVD_IN_XBL		net";
	$conf[]="";
}



if($datas["sa-hil.habeas.com"]==1){
	$conf[]="# sa-hil.habeas.com for SpamAssassin queries";
	$conf[]="# hil.habeas.com for other queries";
	$conf[]="header HABEAS_INFRINGER		eval:check_rbl_swe('hil', 'sa-hil.habeas.com.')";
	$conf[]="describe HABEAS_INFRINGER	Has Habeas warrant mark and on Infringer List";
	$conf[]="tflags HABEAS_INFRINGER		net";
	$conf[]="";
}

if($datas["sa-hul.habeas.com"]==1){
	$conf[]="# sa-hul.habeas.com for SpamAssassin queries";
	$conf[]="# hul.habeas.com for other queries";
	$conf[]="header HABEAS_USER		eval:check_rbl_swe('hul', 'sa-hul.habeas.com.')";
	$conf[]="describe HABEAS_USER		Has Habeas warrant mark and on User List";
	$conf[]="tflags HABEAS_USER		net nice";
	$conf[]="";
	$conf[]="header RCVD_IN_BSP_TRUSTED	eval:check_rbl_txt('bsp-firsttrusted', 'sa-trusted.bondedsender.org.')";
	$conf[]="describe RCVD_IN_BSP_TRUSTED	Sender is in Bonded Sender Program (trusted relay)";
	$conf[]="tflags RCVD_IN_BSP_TRUSTED	net nice";
	$conf[]="";
	$conf[]="header RCVD_IN_BSP_OTHER	eval:check_rbl_txt('bsp-untrusted', 'sa-other.bondedsender.org.')";
	$conf[]="describe RCVD_IN_BSP_OTHER	Sender is in Bonded Sender Program (other relay)";
	$conf[]="tflags RCVD_IN_BSP_OTHER	net nice";
	$conf[]="";
	$conf[]="# SenderBase information <http://www.senderbase.org/dnsresponses.html>";
	$conf[]="# these are experimental example rules";
	$conf[]="";
}

if($datas["senderbase.org"]==1){
	$conf[]="# sa.senderbase.org for SpamAssassin queries";
	$conf[]="# query.senderbase.org for other queries";
	$conf[]="header __SENDERBASE eval:check_rbl_txt('sb', 'sa.senderbase.org.')";
	$conf[]="tflags __SENDERBASE net";
	$conf[]="";
	$conf[]="# S23 = domain daily magnitude, S25 = date of first message from this domain";
	$conf[]="header SB_NEW_BULK		eval:check_rbl_sub('sb', 'sb:S23 > 6.2 && (time - S25 < 120*86400)')";
	$conf[]="describe SB_NEW_BULK		Sender domain is new and very high volume";
	$conf[]="tflags SB_NEW_BULK		net";
	$conf[]="";
	$conf[]="# S5 = category, S40 = IP daily magnitude, S41 = IP monthly magnitude";
	$conf[]="# note: accounting for rounding, \"> 0.3\" means at least a 59% volume spike";
	$conf[]="header SB_NSP_VOLUME_SPIKE	eval:check_rbl_sub('sb', 'sb:S5 =~ /NSP/ && S41 > 3.8 && S40 - S41 > 0.3')";
	$conf[]="describe SB_NSP_VOLUME_SPIKE	Sender IP hosted at NSP has a volume spike";
	$conf[]="tflags SB_NSP_VOLUME_SPIKE	net";
	$conf[]="";
}

if($datas["spamcop"]==1){
	$conf[]="# ---------------------------------------------------------------------------";
	$conf[]="# NOTE: donation tests, see README file for details";
	$conf[]="";
	$conf[]="header RCVD_IN_BL_SPAMCOP_NET	eval:check_rbl_txt('spamcop', 'bl.spamcop.net.')";
	$conf[]="describe RCVD_IN_BL_SPAMCOP_NET	Received via a relay in bl.spamcop.net";
	$conf[]="tflags RCVD_IN_BL_SPAMCOP_NET	net";
	$conf[]="";
}

if($datas["relays.visi.com"]==1){
	$conf[]="header RCVD_IN_RSL		eval:check_rbl_txt('rsl', 'relays.visi.com.')";
	$conf[]="describe RCVD_IN_RSL		Received via a relay in RSL";
	$conf[]="tflags RCVD_IN_RSL		net";
	$conf[]="";
	$conf[]="# ---------------------------------------------------------------------------";
}

$conf[]="# ---------------------------------------------------------------------------";
$conf[]="# Other DNS tests";
$conf[]="";
$conf[]="header NO_DNS_FOR_FROM		eval:check_dns_sender()";
$conf[]="describe NO_DNS_FOR_FROM	Envelope sender has no MX or A DNS records";
$conf[]="tflags NO_DNS_FOR_FROM		net";
$conf[]="";	

@file_put_contents("/etc/spamassassin/dnsbl.pre",@implode("\n",$conf));

}


function x_headers(){
	//X-Wum-Spamlevel
$conf[]="header INFOMANIAK_SPAM X-Infomaniak-Spam =~ /spam/";
$conf[]="score INFOMANIAK_SPAM       2";
$conf[]="header SPAMASS_SPAM X-Spam-Status =~ /Yes/";
$conf[]="score SPAMASS_SPAM       2";
$conf[]="header XASF_SPAM X-ASF-Spam-Status =~ /Yes/";
$conf[]="score XASF_SPAM       2";	
$conf[]="header XTMAS_SPAM X-TM-AS-Result =~ /Yes/";
$conf[]="score XTMAS_SPAM       2";	

if(is_dir("/etc/mail/spamassassin")){
	@file_put_contents("/etc/mail/spamassassin/x-headers.pre",@implode("\n",$conf));
}
@file_put_contents("/etc/spamassassin/x-headers.pre",@implode("\n",$conf));

	
}

function mimeimport(){
	
	$data=@file_get_contents("/home/dtouzeau/mime_validate.cf");
	$data=base64_encode($data);
	echo $data."\n";
}

function MimeValidator(){
	
	$content="IwojIE1JTUUgdmFsaWRhdGlvbgojIFNvbWUgc2ltcGxlIHJ1bGVzIHRvIGZpbmQgTUlNRSBlcnJvcnMgY29tbW9uIGluIG1lc3NhZ2VzCiMgY3JlYXRlZCBieSBidWxrIG1haWwgcHJvZ3JhbXMuCiMgVHlwaWNhbGx5IHNwYW0sIGJ1dCBtYXkgYWxzbyB0cmFwIG5ld3NsZXR0ZXJzLgojIFZlcnNpb24gMS4yCiMgICBieSBCeXRlcGxhbnQgR21iSAojICAgaHR0cDovL3d3dy5ieXRlcGxhbnQuY29tCiMgZG93bmxvYWQgbGF0ZXN0IHZlcnNpb24gaGVyZToKIyAgIGh0dHA6Ly9hbnRpc3BhbS5ieXRlcGxhbnQuY29tL2Rvd25sb2FkL21pbWVfdmFsaWRhdGUuY2YKIyAtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLQojIExlYXJuIG1vcmUgYWJvdXQgdGhlIENsZWFuTWFpbCBBbnRpIFNwYW0gRmlsdGVyIGhlcmU6CiMgaHR0cDovL2FudGlzcGFtLmJ5dGVwbGFudC5jb20vQ2xlYW5NYWlsCiMgLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0KCiMgQVNDSUktMCBjYW4gY3Jhc2ggbWFpbCBjbGllbnRzLiBUaGlzIGlzIGFuIGFic29sdXRlIE5PIQpyYXdib2R5ICAgICBNSU1FX0FTQ0lJMCAgICAgICAgICAgICAvXDAvCmRlc2NyaWJlICAgIE1JTUVfQVNDSUkwICAgICAgICAgICAgIE1lc3NhZ2UgYm9keSBjb250YWlucyBBU0NJSS0wIGNoYXJhY3RlcgpzY29yZSAgICAgICBNSU1FX0FTQ0lJMCAgICAgICAgICAgICAxLjUKCiMgUkZDLTI4MjI6IG1heCBtZXNzYWdlIGxpbmUgc2l6ZSBpcyA5OTggY2hhcnMgKyBDUkxGLgojIFRoaXMgdGVzdCBkb2VzIG5vdCB3b3JrLCBiZWNhdXNlIFNBIHNlZW1zIHRvIGJyZWFrIHRoZSBsaW5lcyBpbnRlcm5hbGx5LgojcmF3Ym9keSAgICAgTUlNRV9MSU5FX1RPT19MT05HICAgICAgLy57OTk4fS4rLwojZGVzY3JpYmUgICAgTUlNRV9MSU5FX1RPT19MT05HICAgICAgTWVzc2FnZSBib2R5IHZpb2xhdGVzIFJGQy0yODIyIChsaW5lIHRvbyBsb25nKQojc2NvcmUgICAgICAgTUlNRV9MSU5FX1RPT19MT05HICAgICAgMS4wCgojIE1lc3NhZ2UgYm9keSBjb250YWlucyBzaW5nbGUgQ1Igb3IgTEYgY2hhcmFjdGVyCiMgUkZDLTI4MjIgaXMgYSBiaXQgYW1iaWd1b3VzIGhlcmUsIGJ1dCBtYWlsIGxpa2UgdGhpcwojIHVzdWFsbHkgb3JpZ2luYXRlcyBmcm9tIHBlb3BsZSB0aGF0IGRpZCBub3QgcmVhZCBpdC4KcmF3Ym9keSAgICAgX19NSU1FX0JBRF9DUiAgICAgICAgICAgL1x4MERbXlx4MEFdLwojIFRoaXMgdGVzdCBhbHdheXMgbWF0Y2hlcywgcHJvYmFibHkgYmVjYXVzZSBvZiB0aGUgbmV3bGluZSBoYW5kbGluZyBpbiBTQQojcmF3Ym9keSAgICAgX19NSU1FX0JBRF9MRiAgICAgICAgICAgL1teXHgwRF1ceDBBLwptZXRhICAgICAgICBNSU1FX0JBRF9MSU5FQlJFQUsgICAgICBfX01JTUVfQkFEX0NSCmRlc2NyaWJlICAgIE1JTUVfQkFEX0xJTkVCUkVBSyAgICAgIE1lc3NhZ2UgYm9keSB3aXRoIGZpc2h5IGxpbmUgYnJlYWtzCnNjb3JlICAgICAgIE1JTUVfQkFEX0xJTkVCUkVBSyAgICAgIDAuNQoKIyBNZXNzYWdlIGhlYWRlciBjb250YWlucyA4IGJpdCBjaGFyYWN0ZXJzLgojIFRoaXMgaXMgYSBjb21tb24gTUlNRSB2aW9sYXRpb24sIGJ1dCBldmVuIG1vcmUgY29tbW9uIGZvciBzcGFtbWVycy4KIyA4LWJpdCBjaGFycyBhcmUgbW9zdCBjb21tb25seSBlbmNvdW50ZXJlZCBpbiBGcm9tL1N1YmplY3QvVG8gaGVhZGVycy4KaGVhZGVyICAgICAgX19NSU1FXzhCSVRfRlJPTSAgICAgICAgRnJvbTpyYXcgPX4gL1tceDgwLVx4RkZdLwpoZWFkZXIgICAgICBfX01JTUVfOEJJVF9TVUJKRUNUICAgICBTdWJqZWN0OnJhdyA9fiAvW1x4ODAtXHhGRl0vCmhlYWRlciAgICAgIF9fTUlNRV84QklUX1RPICAgICAgICAgIFRvOnJhdyA9fiAvW1x4ODAtXHhGRl0vCm1ldGEgICAgICAgIE1JTUVfOEJJVF9IRUFERVIgICAgICAgIChfX01JTUVfOEJJVF9UTyB8fCBfX01JTUVfOEJJVF9TVUJKRUNUIHx8IF9fTUlNRV84QklUX0ZST00pCmRlc2NyaWJlICAgIE1JTUVfOEJJVF9IRUFERVIgICAgICAgIE1lc3NhZ2UgaGVhZGVyIGNvbnRhaW5zIDgtYml0IGNoYXJhY3RlcgpzY29yZSAgICAgICBNSU1FXzhCSVRfSEVBREVSICAgICAgICAwLjMK";
	$content=base64_decode($content)."\n";
	@file_put_contents("/etc/spamassassin/mime_validate.cf",$content);
}


function x_bounce(){
	$sock=new sockets();
	if($sock->GET_INFO("SpamAssassinVirusBounceEnabled")<>1){
		echo "Starting......: ".date("H:i:s")." spamassassin Virus Bounce Ruleset is disabled\n";
		@file_put_contents('/etc/spamassassin/20_vbounce.cf',"#");
		return;
	}
	
echo "Starting......: ".date("H:i:s")." spamassassin Virus Bounce Ruleset is enabled\n";
x_bound_pm();	
$f[]="# very frequent, using unrelated From lines; either spam or C/R, not yet";
$f[]="# sure which";
$f[]="header __CRBOUNCE_GETRESP Return-Path =~ /<bounce\S+\@\S+\.getresponse\.com>/";
$f[]="";
$f[]="header __CRBOUNCE_TMDA  Message-Id =~ /\@\S+\-tmda\-confirm>$/";
$f[]="header __CRBOUNCE_ASK   X-AskVersion =~ /\d/";
$f[]="header __CRBOUNCE_SZ    X-Spamazoid-MD =~ /\d/";
$f[]="header __CRBOUNCE_SPAMLION Spamlion =~ /\S/";
$f[]="";
$f[]="# something called /cgi-bin/notaspammer does this!";
$f[]="header __CRBOUNCE_PREC_SPAM  Precedence =~ /spam/";
$f[]="";
$f[]="header __AUTO_GEN_XBT   exists:X-Boxtrapper";
$f[]="header __AUTO_GEN_BBTL  exists:X-Bluebottle-Request";
$f[]="meta __CRBOUNCE_HEADER    (__AUTO_GEN_XBT || __AUTO_GEN_BBTL)";
$f[]="";
$f[]="header __CRBOUNCE_EXI   X-ExiSpam =~ /ExiSpam/";
$f[]="";
$f[]="header __CRBOUNCE_UNVERIF   Subject =~ /^Unverified email to /";
$f[]="";
$f[]="meta CRBOUNCE_MESSAGE       !MY_SERVERS_FOUND && (__CRBOUNCE_UOL || __CRBOUNCE_VERIF || __CRBOUNCE_RP || __CRBOUNCE_VANQ || __CRBOUNCE_HEADER || __CRBOUNCE_QURB || __CRBOUNCE_0SPAM || __CRBOUNCE_GETRESP || __CRBOUNCE_TMDA || __CRBOUNCE_ASK || __CRBOUNCE_EXI || __CRBOUNCE_PREC_SPAM || __CRBOUNCE_SZ || __CRBOUNCE_SPAMLION || __CRBOUNCE_MIB || __CRBOUNCE_SI || __CRBOUNCE_UNVERIF || __CRBOUNCE_RP_2)";
$f[]="";
$f[]="describe CRBOUNCE_MESSAGE   Challenge-response bounce message";
$f[]="score    CRBOUNCE_MESSAGE   0.1";
$f[]="";
$f[]="# ---------------------------------------------------------------------------";
$f[]="# \"Virus found in your mail\" bounces";
$f[]="";
$f[]="# source: VirusBounceRules from the exit0 SA wiki";
$f[]="";
$f[]="body __VBOUNCE_EXIM      /a potentially executable attachment /";
$f[]="body __VBOUNCE_GUIN      /message contains file attachments that are not permitted/";
$f[]="body __VBOUNCE_CISCO     /^Found virus \S+ in file \S+/m";
$f[]="body __VBOUNCE_SMTP      /host \S+ said: 5\d\d\s+Error: Message content rejected/";
$f[]="body __VBOUNCE_AOL       /TRANSACTION FAILED - Unrepairable Virus Detected. /";
$f[]="body __VBOUNCE_DUTCH     /bevatte bijlage besmet welke besmet was met een virus/";
$f[]="body __VBOUNCE_MAILMARSHAL       /Mail.?Marshal Rule: Inbound Messages : Block Dangerous Attachments/";
$f[]="header __VBOUNCE_MAILMARSHAL2    Subject =~ /^MailMarshal has detected possible spam in your message/";
$f[]="header __VBOUNCE_NAVFAIL   Subject =~ /^Norton Anti.?Virus failed to scan an attachment in a message you sent/";
$f[]="header __VBOUNCE_REJECTED   Subject =~ /^EMAIL REJECTED$/";
$f[]="header __VBOUNCE_NAV   Subject =~ /^Norton Anti.?Virus detected and quarantined/";
$f[]="header __VBOUNCE_MELDING   Subject =~ /^Virusmelding$/";
$f[]="body __VBOUNCE_VALERT      /The mail message \S+ \S+ you sent to \S+ contains the virus/";
$f[]="body __VBOUNCE_REJ_FILT    /Reason: Rejected by filter/";
$f[]="header __VBOUNCE_YOUSENT   Subject =~ /^Warning - You sent a Virus Infected Email to /";
$f[]="body __VBOUNCE_MAILSWEEP   /MAILsweeper has found that a \S+ \S+ \S+ \S+ one or more virus/";
$f[]="header   __VBOUNCE_SCREENSAVER Subject =~ /(Re: ?)+Wicked screensaver\b/i";
$f[]="header   __VBOUNCE_DISALLOWED Subject =~ /^Disallowed attachment type found/";
$f[]="header   __VBOUNCE_FROMPT From =~ /Security.?Scan Anti.?Virus/";
$f[]="header   __VBOUNCE_WARNING Subject =~ /^Warning:\s*E-?mail virus(es)? detected/i";
$f[]="header   __VBOUNCE_DETECTED Subject =~ /^Virus detected /i";
$f[]="header   __VBOUNCE_AUTOMATIC Subject =~ /\b(automatic reply|AutoReply)\b/";
$f[]="header   __VBOUNCE_INTERSCAN Subject =~ /^Failed to clean virus\b/i";
$f[]="header   __VBOUNCE_VIOLATION Subject =~ /^Content violation/i";
$f[]="header   __VBOUNCE_ALERT Subject =~ /^Virus Alert\b/i";
$f[]="header   __VBOUNCE_NAV2 Subject =~ /^NAV detected a virus in a document /";
$f[]="body      __VBOUNCE_NAV3 /^Reporting-MTA: Norton Anti.?Virus Gateway/";
$f[]="header   __VBOUNCE_INTERSCAN2 Subject =~ /^InterScan MSS for SMTP has delivered a message/";
$f[]="header   __VBOUNCE_INTERSCAN3 Subject =~ /^InterScan NT Alert/";
$f[]="header   __VBOUNCE_ANTIGEN Subject =~ /^Antigen found\b/i";
$f[]="header   __VBOUNCE_LUTHER From =~ /\blutherh\@stratcom.com\b/";
$f[]="header   __VBOUNCE_AMAVISD Subject =~ /^VIRUS IN YOUR MAIL /i";
$f[]="body     __VBOUNCE_AMAVISD2 /\bV I R U S\b/";
$f[]="header __VBOUNCE_GSHIELD Subject =~ /^McAfee GroupShield Alert/";
$f[]="";
$f[]="# off: got an FP in a simple forward";
$f[]="# rawbody  __VBOUNCE_SUBJ_IN_MAIL /^\s*Subject:\s*(Re: )*((my|your) )?(application|details)/i";
$f[]="# rawbody  __VBOUNCE_SUBJ_IN_MAIL2 /^\s*Subject:\s*(Re: )*(Thank you!?|That movie|Wicked screensaver|Approved)/i";
$f[]="";
$f[]="header __VBOUNCE_SCANMAIL Subject =~ /^Scan.?Mail Message: .{0,30} virus found /i";
$f[]="header __VBOUNCE_DOMINO1 Subject =~ /^Report to Sender/";
$f[]="body __VBOUNCE_DOMINO2 /^Incident Information:/";
$f[]="header __VBOUNCE_RAV Subject =~ /^RAV Anti.?Virus scan results/";
$f[]="";
$f[]="body __VBOUNCE_ATTACHMENT0     /(?:Attachment.{0,40}was Deleted|Virus.{1,40}was found|the infected attachment)/i";
$f[]="# Bart says: it appears that _ATTACHMENT0 is an alternate for _NAV -- both match the same messages.";
$f[]="";
$f[]="body __VBOUNCE_AVREPORT0       /(antivirus system report|the antivirus module has|illegal attachment|Unrepairable Virus Detected)/i";
$f[]="header __VBOUNCE_SENDER       Subject =~ /^Virus to sender/";
$f[]="body __VBOUNCE_MAILSWEEP2     /\bblocked by Mailsweeper\b/i";
$f[]="";
$f[]="header __VBOUNCE_MAILSWEEP3   From =~ /\bmailsweeper\b/i";
$f[]="# Bart says: This one could replace both MAILSWEEP2 and MAILSWEEP as far as I can tell.";
$f[]="#            Perhaps it's too general?";
$f[]="";
$f[]="body __VBOUNCE_CLICKBANK      /\bvirus scanner deleted your message\b/i";
$f[]="header __VBOUNCE_FORBIDDEN    Subject =~ /\bFile type Forbidden\b/";
$f[]="header   __VBOUNCE_MMS        Subject =~ /^MMS Notification/";
$f[]="# added by JoeyKelly";
$f[]="";
$f[]="header __VBOUNCE_JMAIL Subject =~ /^Message Undeliverable: Possible Junk\/Spam Mail Identified$/";
$f[]="";
$f[]="body __VBOUNCE_QUOTED_EXE     /> TVqQAAMAAAAEAAAA/";
$f[]="";
$f[]="# majordomo is really stupid about this stuff";
$f[]="header __MAJORDOMO_SUBJ     Subject =~ /^Majordomo results: /";
$f[]="rawbody __MAJORDOMO_HELP_BODY  /\*\*\*\* Help for [mM]ajordomo\@/";
$f[]="rawbody __MAJORDOMO_HELP_BODY2 /\*\*\*\* Command \'.{0,80}\' not recognized\b/";
$f[]="meta __VBOUNCE_MAJORDOMO_HELP (__MAJORDOMO_SUBJ && __MAJORDOMO_HELP_BODY && __MAJORDOMO_HELP_BODY2)";
$f[]="";
$f[]="header __VBOUNCE_AV_RESULTS   Subject =~ /AntiVirus scan results/";
$f[]="header __VBOUNCE_EMVD         Subject =~ /^Warning: E-mail viruses detected/";
$f[]="header __VBOUNCE_UNDELIV      Subject =~ /^Undeliverable mail, invalid characters in header/";
$f[]="header __VBOUNCE_BANNED_MAT   Subject =~ /^Banned or potentially offensive material/";
$f[]="header __VBOUNCE_NAV_DETECT   Subject =~ /^Norton AntiVirus detected and quarantined/";
$f[]="header __VBOUNCE_DEL_WARN     Subject =~ /^Delivery warning report id=/";
$f[]="header __VBOUNCE_MIME_INFO    Subject =~ /^The MIME information you requested/";
$f[]="header __VBOUNCE_EMAIL_REJ    Subject =~ /^EMAIL REJECTED/";
$f[]="header __VBOUNCE_CONT_VIOL    Subject =~ /^Content violation/";
$f[]="header __VBOUNCE_SYM_AVF      Subject =~ /^Symantec AVF detected /";
$f[]="header __VBOUNCE_SYM_EMP      Subject =~ /^Symantec E-Mail-Proxy /";
$f[]="header __VBOUNCE_VIR_FOUND    Subject =~ /^Virus Found in message/";
$f[]="header __VBOUNCE_INFLEX       Subject =~ /^Inflex scan report \[/";
$f[]="";
$f[]="header __VBOUNCE_RAPPORT      Subject =~ /^Spam rapport \/ Spam report \S+ -\s+\(\S+\)$/";
$f[]="header __VBOUNCE_GWAVA        Subject =~ /^GWAVA Sender Notification .RBL block.$/";
$f[]="";
$f[]="header __VBOUNCE_EMANAGER     Subject =~ /^\[MailServer Notification\]/";
$f[]="header __VBOUNCE_MSGLABS      Return-Path =~ /alert\@notification\.messagelabs\.com/i";
$f[]="body __VBOUNCE_ATT_QUAR       /\bThe attachment was quarantined\b/";
$f[]="body __VBOUNCE_SECURIQ        /\bGROUP securiQ.Wall\b/";
$f[]="";
$f[]="header __VBOUNCE_PT_BLOCKED   Subject =~ /^\*\*\*\s*Mensagem Bloqueada/i";
$f[]="";
$f[]="meta VBOUNCE_MESSAGE        !MY_SERVERS_FOUND && (__VBOUNCE_MSGLABS || __VBOUNCE_EXIM || __VBOUNCE_GUIN || __VBOUNCE_CISCO || __VBOUNCE_SMTP || __VBOUNCE_AOL || __VBOUNCE_DUTCH || __VBOUNCE_MAILMARSHAL || __VBOUNCE_MAILMARSHAL2 || __VBOUNCE_NAVFAIL || __VBOUNCE_REJECTED || __VBOUNCE_NAV || __VBOUNCE_MELDING || __VBOUNCE_VALERT || __VBOUNCE_REJ_FILT || __VBOUNCE_YOUSENT || __VBOUNCE_MAILSWEEP || __VBOUNCE_SCREENSAVER || __VBOUNCE_DISALLOWED || __VBOUNCE_FROMPT || __VBOUNCE_WARNING || __VBOUNCE_DETECTED || __VBOUNCE_AUTOMATIC || __VBOUNCE_INTERSCAN || __VBOUNCE_VIOLATION || __VBOUNCE_ALERT || __VBOUNCE_NAV2 || __VBOUNCE_NAV3 || __VBOUNCE_INTERSCAN2 || __VBOUNCE_INTERSCAN3 || __VBOUNCE_ANTIGEN || __VBOUNCE_LUTHER || __VBOUNCE_AMAVISD || __VBOUNCE_AMAVISD2 || __VBOUNCE_SCANMAIL || __VBOUNCE_DOMINO1 || __VBOUNCE_DOMINO2 || __VBOUNCE_RAV || __VBOUNCE_GSHIELD || __VBOUNCE_ATTACHMENT0 || __VBOUNCE_AVREPORT0 || __VBOUNCE_SENDER || __VBOUNCE_MAILSWEEP2 || __VBOUNCE_MAILSWEEP3 || __VBOUNCE_CLICKBANK || __VBOUNCE_FORBIDDEN || __VBOUNCE_MMS || __VBOUNCE_QUOTED_EXE || __VBOUNCE_MAJORDOMO_HELP || __VBOUNCE_AV_RESULTS || __VBOUNCE_EMVD || __VBOUNCE_UNDELIV || __VBOUNCE_BANNED_MAT || __VBOUNCE_NAV_DETECT || __VBOUNCE_DEL_WARN || __VBOUNCE_MIME_INFO || __VBOUNCE_EMAIL_REJ || __VBOUNCE_CONT_VIOL || __VBOUNCE_SYM_AVF || __VBOUNCE_SYM_EMP || __VBOUNCE_ATT_QUAR || __VBOUNCE_SECURIQ || __VBOUNCE_VIR_FOUND || __VBOUNCE_EMANAGER || __VBOUNCE_JMAIL || __VBOUNCE_GWAVA || __VBOUNCE_PT_BLOCKED || __VBOUNCE_INFLEX)";
$f[]="";
$f[]="describe VBOUNCE_MESSAGE    Virus-scanner bounce message";
$f[]="score    VBOUNCE_MESSAGE    0.1";
$f[]="";
$f[]="# ---------------------------------------------------------------------------";
$f[]="";
$f[]="# a catch-all type for all the above";
$f[]="";
$f[]="meta     ANY_BOUNCE_MESSAGE (CRBOUNCE_MESSAGE||BOUNCE_MESSAGE||VBOUNCE_MESSAGE)";
$f[]="describe ANY_BOUNCE_MESSAGE Message is some kind of bounce message";
$f[]="score    ANY_BOUNCE_MESSAGE 0.1";
$f[]="";
$f[]="# ---------------------------------------------------------------------------";
$f[]="";
$f[]="# ensure these aren't published in rule-updates as general antispam rules;";
$f[]="# this is required, since it appears we're now at the stage where they";
$f[]="# *do* appear to correlate strongly :(";
$f[]="# http://ruleqa.spamassassin.org/20060405-r391250-n/BOUNCE_MESSAGE";
$f[]="#";
$f[]="tflags   CRBOUNCE_MESSAGE   nopublish";
$f[]="tflags   BOUNCE_MESSAGE     nopublish";
$f[]="tflags   VBOUNCE_MESSAGE    nopublish";
$f[]="tflags   ANY_BOUNCE_MESSAGE nopublish";
$f[]="";

@file_put_contents('/etc/spamassassin/20_vbounce.cf',@implode("\n",$f));
if(is_dir("/etc/mail/spamassassin")){@file_put_contents("/etc/mail/spamassassin/20_vbounce.cf",@implode("\n",$f));}


}

function x_bound_pm(){
	if(file_exists("/etc/spamassassin/VBounce.pm")){return;}
$f[]="# <@LICENSE>";
$f[]="# Licensed to the Apache Software Foundation (ASF) under one or more";
$f[]="# contributor license agreements.  See the NOTICE file distributed with";
$f[]="# this work for additional information regarding copyright ownership.";
$f[]="# The ASF licenses this file to you under the Apache License, Version 2.0";
$f[]="# (the \"License\"); you may not use this file except in compliance with";
$f[]="# the License.  You may obtain a copy of the License at:";
$f[]="#";
$f[]="#     http://www.apache.org/licenses/LICENSE-2.0";
$f[]="#";
$f[]="# Unless required by applicable law or agreed to in writing, software";
$f[]="# distributed under the License is distributed on an \"AS IS\" BASIS,";
$f[]="# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.";
$f[]="# See the License for the specific language governing permissions and";
$f[]="# limitations under the License.";
$f[]="# </@LICENSE>";
$f[]="";
$f[]="=head1 NAME";
$f[]="";
$f[]="Mail::SpamAssassin::Plugin::VBounce";
$f[]="";
$f[]="=head1 SYNOPSIS";
$f[]="";
$f[]=" loadplugin Mail::SpamAssassin::Plugin::VBounce [/path/to/VBounce.pm]";
$f[]="";
$f[]="=cut";
$f[]="";
$f[]="package Mail::SpamAssassin::Plugin::VBounce;";
$f[]="";
$f[]="use Mail::SpamAssassin::Plugin;";
$f[]="use Mail::SpamAssassin::Logger;";
$f[]="use strict;";
$f[]="use warnings;";
$f[]="";
$f[]="our @ISA = qw(Mail::SpamAssassin::Plugin);";
$f[]="";
$f[]="sub new {";
$f[]="  my \$class = shift;";
$f[]="  my \$mailsaobject = shift;";
$f[]="";
$f[]="  \$class = ref(\$class) || \$class;";
$f[]="  my \$self = \$class->SUPER::new(\$mailsaobject);";
$f[]="  bless (\$self, \$class);";
$f[]="";
$f[]="  \$self->register_eval_rule(\"have_any_bounce_relays\");";
$f[]="  \$self->register_eval_rule(\"check_whitelist_bounce_relays\");";
$f[]="";
$f[]="  \$self->set_config(\$mailsaobject->{conf});";
$f[]="";
$f[]="  return \$self;";
$f[]="}";
$f[]="";
$f[]="sub set_config {";
$f[]="  my(\$self, \$conf) = @_;";
$f[]="  my @cmds = ();";
$f[]="";
$f[]="=head1 USER PREFERENCES";
$f[]="";
$f[]="The following options can be used in both site-wide (C<local.cf>) and";
$f[]="user-specific (C<user_prefs>) configuration files to customize how";
$f[]="SpamAssassin handles incoming email messages.";
$f[]="";
$f[]="=over 4";
$f[]="";
$f[]="=item whitelist_bounce_relays hostname [hostname2 ...]";
$f[]="";
$f[]="This is used to 'rescue' legitimate bounce messages that were generated in";
$f[]="response to mail you really *did* send.  List the MTA relays that your outbound";
$f[]="mail is delivered through.  If a bounce message is found, and it contains one";
$f[]="of these hostnames in a 'Received' header, it will not be marked as a blowback";
$f[]="virus-bounce.";
$f[]="";
$f[]="The hostnames can be file-glob-style patterns, so C<relay*.isp.com> will work.";
$f[]="Specifically, C<*> and C<?> are allowed, but all other metacharacters are not.";
$f[]="Regular expressions are not used for security reasons.";
$f[]="";
$f[]="Multiple addresses per line, separated by spaces, is OK.  Multiple";
$f[]="C<whitelist_from> lines is also OK.";
$f[]="";
$f[]="";
$f[]="=cut";
$f[]="";
$f[]="  push (@cmds, {";
$f[]="      setting => 'whitelist_bounce_relays',";
$f[]="      type => \$Mail::SpamAssassin::Conf::CONF_TYPE_ADDRLIST";
$f[]="    });";
$f[]="";
$f[]="  \$conf->{parser}->register_commands(\@cmds);";
$f[]="}";
$f[]="";
$f[]="sub have_any_bounce_relays {";
$f[]="  my (\$self, \$pms) = @_;";
$f[]="  return (defined \$pms->{conf}->{whitelist_bounce_relays} &&";
$f[]="      (scalar values %{\$pms->{conf}->{whitelist_bounce_relays}} != 0));";
$f[]="}";
$f[]="";
$f[]="sub check_whitelist_bounce_relays {";
$f[]="  my (\$self, \$pms) = @_;";
$f[]="";
$f[]="  my \$body = \$pms->get_decoded_stripped_body_text_array();";
$f[]="  my \$res;";
$f[]="";
$f[]="  # catch lines like:";
$f[]="  # Received: by dogma.boxhost.net (Postfix, from userid 1007)";
$f[]="";
$f[]="  # check the plain-text body, first";
$f[]="  foreach my \$line (@{\$body}) {";
$f[]="    next unless (\$line =~ /Received: /);";
$f[]="    while (\$line =~ / (\S+\.\S+) /g) {";
$f[]="      return 1 if \$self->_relay_is_in_whitelist_bounce_relays(\$pms, \$1);";
$f[]="    }";
$f[]="  }";
$f[]="";
$f[]="  # now check any \"message/anything\" attachment MIME parts, too";
$f[]="  # don't ignore non-leaf nodes, some bounces are odd that way";
$f[]="  foreach my \$p (\$pms->{msg}->find_parts(qr/^message\//, 0)) {";
$f[]="    my \$line = \$p->decode();";
$f[]="    next unless \$line && (\$line =~ /Received: /);";
$f[]="    while (\$line =~ / (\S+\.\S+) /g) {";
$f[]="      return 1 if \$self->_relay_is_in_whitelist_bounce_relays(\$pms, \$1);";
$f[]="    }";
$f[]="  }";
$f[]="";
$f[]="  return 0;";
$f[]="}";
$f[]="";
$f[]="sub _relay_is_in_whitelist_bounce_relays {";
$f[]="  my (\$self, \$pms, \$relay) = @_;";
$f[]="  return 1 if \$self->_relay_is_in_list(";
$f[]="        \$pms->{conf}->{whitelist_bounce_relays}, \$pms, \$relay);";
$f[]="  dbg(\"rules: relay \$relay doesn't match any whitelist\");";
$f[]="}";
$f[]="";
$f[]="sub _relay_is_in_list {";
$f[]="  my (\$self, \$list, \$pms, \$relay) = @_;";
$f[]="  \$relay = lc \$relay;";
$f[]="";
$f[]="  if (defined \$list->{\$relay}) { return 1; }";
$f[]="";
$f[]="  foreach my \$regexp (values %{\$list}) {";
$f[]="    if (\$relay =~ qr/\$regexp/i) {";
$f[]="      dbg(\"rules: relay \$relay matches regexp: \$regexp\");";
$f[]="      return 1;";
$f[]="    }";
$f[]="  }";
$f[]="";
$f[]="  return 0;";
$f[]="}";
$f[]="";
$f[]="1;";
$f[]="__DATA__";
$f[]="";
$f[]="=back";
$f[]="";
$f[]="=cut";
$f[]="";
@file_put_contents('/etc/spamassassin/VBounce.pm',@implode("\n",$f));
if(is_dir("/etc/mail/spamassassin")){@file_put_contents("/etc/mail/spamassassin/VBounce.pm",@implode("\n",$f));}

}

function build_progress_TrustedNetworks($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/spamassassin.TrustedNetworks.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_analyze($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/spamassassin.analyze.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function TrustedNetworks($reload=false){
	
	build_progress_TrustedNetworks(20, "{configuring}");
	
	$ipClass=new IP();
	$f[]="trusted_networks 127.0.0.0/8";
	$f[]="whitelist_from *@articatech.com";
	
	
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$results=$q->QUERY_SQL("SELECT * FROM mynetworks");
	
	
	foreach ($results as $num=>$ligne){
		$val=trim($ligne["addr"]);
		if($val==null){continue;}
		if($ipClass->isIPAddressOrRange($ligne["ipaddr"])){
			$f[]="trusted_networks $val";
		}
	}
	
	build_progress_TrustedNetworks(50, "{configuring}");
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	
	$results=$q->QUERY_SQL("SELECT * FROM whitelists ORDER BY pattern");
	
	
	
	foreach ($results as $index=>$ligne){
		$pattern=trim($ligne["pattern"]);
		if($pattern==null){continue;}
		
		if(preg_match("#^[0-9\.]+$#", $pattern)){
			$f[]="trusted_networks {$pattern}";
			continue;
		}
		if(preg_match("#^[0-9\.]+\/[0-9]+$#", $pattern)){
			$f[]="trusted_networks {$pattern}";
			continue;
		}

		
		$f[]="whitelist_from {$pattern}";
		
	}
	
	
	$pg=new postgres_sql();
	$sql="SELECT id,pattern FROM miltergreylist_acls WHERE method='whitelist' AND type='from'";
	$results=$pg->QUERY_SQL($sql);
	while ($ligne = pg_fetch_assoc($results)) {
		$pattern=$ligne["pattern"];
		$ID=$ligne["id"];
		if($pattern==null){continue;}
		$f[]="whitelist_from {$pattern}";
	}

    $pg=new postgres_sql();
    $sql="SELECT id,pattern FROM miltergreylist_acls WHERE method='whitelist' AND type='rcpt'";
    $results=$pg->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $pattern=$ligne["pattern"];
        $ID=$ligne["id"];
        if($pattern==null){continue;}
        $f[]="whitelist_to {$pattern}";
    }


	
	
	
	
	build_progress_TrustedNetworks(80, "{configuring} {done}");
	$count=count($f);
	echo "Starting......: ".date("H:i:s")." spamassassin Whitelisted ($count rows) done\n";	
	$user=new usersMenus();
	$init_pre=dirname($user->spamassassin_conf_path)."/trusted_nets.pre";
	$final=@implode("\n",$f)."\n";
	@file_put_contents($init_pre,$final);	
	echo "Starting......: ".date("H:i:s")." spamassassin $init_pre done\n";
	
	if($reload){
		build_progress_TrustedNetworks(90, "{reloading}");
		if(is_file("/etc/init.d/mimedefang")){
			system("/etc/init.d/mimedefang reload");
		}
	}
	build_progress_TrustedNetworks(100, "{success}");
	
}

function HitFreqsRuleTiming(){
$f[]="# HitFreqsRuleTiming - SpamAssassin rule timing plugin";
$f[]="# (derived from attachment 3055 on bug 4517)";
$f[]="#";
$f[]="# <@LICENSE>";
$f[]="# Licensed to the Apache Software Foundation (ASF) under one or more";
$f[]="# contributor license agreements.  See the NOTICE file distributed with";
$f[]="# this work for additional information regarding copyright ownership.";
$f[]="# The ASF licenses this file to you under the Apache License, Version 2.0";
$f[]="# (the \"License\"); you may not use this file except in compliance with";
$f[]="# the License.  You may obtain a copy of the License at:";
$f[]="# ";
$f[]="#     http://www.apache.org/licenses/LICENSE-2.0";
$f[]="# ";
$f[]="# Unless required by applicable law or agreed to in writing, software";
$f[]="# distributed under the License is distributed on an \"AS IS\" BASIS,";
$f[]="# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.";
$f[]="# See the License for the specific language governing permissions and";
$f[]="# limitations under the License.";
$f[]="# </@LICENSE>";
$f[]="";
$f[]="package HitFreqsRuleTiming;";
$f[]="";
$f[]="use Mail::SpamAssassin::Plugin;";
$f[]="use Mail::SpamAssassin::Logger;";
$f[]="use strict;";
$f[]="use warnings;";
$f[]="";
$f[]="use Time::HiRes qw(gettimeofday tv_interval);";
$f[]="";
$f[]="use vars qw(@ISA);";
$f[]="@ISA = qw(Mail::SpamAssassin::Plugin);";
$f[]="";
$f[]="sub new {";
$f[]="    my \$class = shift;";
$f[]="    my \$mailsaobject = shift;";
$f[]="";
$f[]="    \$class = ref(\$class) || \$class;";
$f[]="    my \$self = \$class->SUPER::new(\$mailsaobject);";
$f[]="    \$mailsaobject->{rule_timing} = {";
$f[]="      duration => { },";
$f[]="      runs => { },";
$f[]="      max => { },";
$f[]="    };";
$f[]="    bless (\$self, \$class);";
$f[]="}";
$f[]="";
$f[]="sub start_rules {";
$f[]="    my (\$self, \$options) = @_;";
$f[]="";
$f[]="    \$options->{permsgstatus}->{RuleTimingStart} = [gettimeofday()];";
$f[]="}";
$f[]="";
$f[]="sub ran_rule {";
$f[]="    my @now = gettimeofday();";
$f[]="    my (\$self, \$options) = @_;";
$f[]="";
$f[]="    my \$permsg = \$options->{permsgstatus};";
$f[]="    my \$mailsa = \$permsg->{main};";
$f[]="    my \$name = \$options->{rulename};";
$f[]="";
$f[]="    my \$duration = tv_interval(\$permsg->{RuleTimingStart}, \@now);";
$f[]="    @{\$permsg->{RuleTimingStart}} = @now;";
$f[]="";
$f[]="    unless (\$mailsa->{rule_timing}{duration}{\$name}) {";
$f[]="        \$mailsa->{rule_timing}{duration}{\$name} = 0;";
$f[]="        \$mailsa->{rule_timing}{max}{\$name} = 0;";
$f[]="    }";
$f[]="";
$f[]="    # TODO: record all runs and compute std dev";
$f[]="";
$f[]="    \$mailsa->{rule_timing}{runs}{\$name}++;";
$f[]="    \$mailsa->{rule_timing}{duration}{\$name} += \$duration;";
$f[]="    \$mailsa->{rule_timing}{max}{\$name} = \$duration";
$f[]="        if \$duration > \$mailsa->{rule_timing}{max}{\$name};";
$f[]="}";
$f[]="";
$f[]="sub finish {";
$f[]="    my \$self = shift;";
$f[]="    my \$mailsa = \$self->{main};";
$f[]="";
$f[]="    # take a ref to speed up the sorting";
$f[]="    my \$dur_ref = \$mailsa->{rule_timing}{duration};";
$f[]="";
$f[]="    my \$s = '';";
$f[]="    foreach my \$rule (sort {";
$f[]="        \$dur_ref->{\$b} <=> \$dur_ref->{\$a}";
$f[]="      } keys %{\$dur_ref})";
$f[]="    {";
$f[]="        \$s .= sprintf \"T %30s %8.3f %8.3f %4d\n\", \$rule,";
$f[]="            \$mailsa->{rule_timing}{duration}->{\$rule},";
$f[]="            \$mailsa->{rule_timing}{max}->{\$rule},";
$f[]="            \$mailsa->{rule_timing}{runs}->{\$rule};";
$f[]="    }";
$f[]="";
$f[]="    open (OUT, \">timing.log\") or warn \"cannot write to timing.log\";";
$f[]="    print OUT \"v1\n\";       # forward compatibility";
$f[]="    print OUT \$s;";
$f[]="    close OUT or warn \"cannot write to timing.log\";";
$f[]="";
$f[]="    \$self->SUPER::finish();";
$f[]="}";
$f[]="";
$f[]="1;";

@file_put_contents("/etc/spamassassin/HitFreqsRuleTiming.pm",@implode("\n",$f));
if(is_dir("/etc/mail/spamassassin")){
	@file_put_contents("/etc/mail/spamassassin/HitFreqsRuleTiming.pm",@implode("\n",$f));
}

echo "Starting......: ".date("H:i:s")." spamassassin HitFreqsRuleTiming.pm done\n";	
	
	
}

function SpamTests($ID=null){
	
	build_progress_analyze(20,"{analyze}");
	
	if(is_numeric($ID)){
		_SpamTestsPerformSpamassassin($ID);
	}
	build_progress_analyze(80,"{analyze}");
	$sql="SELECT ID FROM amavisd_tests WHERE finish=0";
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	foreach ($results as $index=>$ligne){
		build_progress_analyze(85,"{analyze} {$ligne["ID"]}");
		if($ID<>$ligne["ID"]){_SpamTestsPerformSpamassassin($ligne["ID"]);}
	}
	build_progress_analyze(100,"{analyze} {success}");
}


function _SpamTestsPerformSpamassassin($ID){
	$sql="SELECT * FROM amavisd_tests WHERE ID=$ID";
	$q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
	$ligne=$q->mysqli_fetch_array($sql);
	$recp=explode(",",$ligne["recipients"]);
	$recipients=@implode(" ",$recp);
	build_progress_analyze(25,"{analyze}");
	@file_put_contents("/tmp/$ID.txt",base64_decode($ligne["message"]));
	$unix=new unix();
	$spamassassin=$unix->find_program("spamassassin");
	if(is_file("/usr/local/bin/spamassassin")){$spamassassin="/usr/local/bin/spamassassin";}
	build_progress_analyze(30,"{analyze}");
	$cmd="$spamassassin -t -D </tmp/$ID.txt 2>&1";
	echo "$cmd\n";
	exec($cmd,$results);
	@unlink("/tmp/$ID.txt");
	$datas=@implode("\n",$results);
	echo $datas."\n";
	if(preg_match("#<< begin content Filter(.+?)end content filter report >>#is",$datas,$re)){
		
		
		$score=GetScores($re[1]);
		$report=base64_encode($re[1]);
		echo "\n$report\n";
		$sql="UPDATE amavisd_tests SET amavisd_results='$report',finish=1,spamassassin_score='$score' WHERE ID=$ID";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
		
	}else{
		$sql="UPDATE amavisd_tests SET amavisd_results='No report',finish=-1,spamassassin_score='0' WHERE ID=$ID";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	build_progress_analyze(40,"{analyze}");

}

function GetScores($lines){
	
	if(preg_match("#Content analysis details.*?([0-9\.]+)\s+point#is", $lines,$re)){
		return $re[1];
	}
	
}

function sa_update_check(){
	$unix=new unix();
	$saupdate=$unix->find_program("sa-update");
	if(!is_file($saupdate)){return null;}
	$statusFile="/usr/share/artica-postfix/ressources/logs/sa-update-status.html";
	$statusFileContent="/usr/share/artica-postfix/ressources/logs/sa-update-status.txt";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if(!$GLOBALS["FORCE"]){if($unix->file_time_min($timefile)<120){return;}}
	@file_put_contents($timefile, time());
	@unlink($statusFile);
	$cmd="$saupdate --checkonly -D --channel sought.rules.yerp.org --channel updates.spamassassin.org 2>&1";
	exec($cmd,$results);
	if($GLOBALS["VERBOSE"]){echo "$cmd ". count($results). " rows\n";}
	$UPDATE=false;
	foreach ($results as $index=>$line){
		
		if(preg_match("#channel:\s+(.+?):\s+update available#", $line,$re)){
			
			$UPDATE=TRUE;
		}
		
		
		
		
	}

	if($UPDATE){sa_update();}
	
	
}


function patch_deamons(){
	$unix=new unix();
	$spamassassin=$unix->find_program("spamassassin");
	if(is_file("/usr/local/bin/spamassassin")){$spamassassin="/usr/local/bin/spamassassin";}
	$spamd=$unix->find_program("spamd");
	if(is_file("/usr/local/bin/spamd")){$spamd="/usr/local/bin/spamd";}
	$sa_update=$unix->find_program("sa-update");
	if(is_file("/usr/local/bin/sa-update")){$sa_update="/usr/local/bin/sa-update";}
	$sa_learn=$unix->find_program("sa-learn");
	if(is_file("/usr/local/bin/sa-learn")){$sa_learn="/usr/local/bin/sa-learn";}
	
	
	
	$f=explode("\n",@file_get_contents($spamassassin));
	
	foreach ($f as $index=>$line){
		if(preg_match("#DEF_RULES_DIR\s+=#", $line)){
			echo "Starting......: ".date("H:i:s")." spamassassin $spamassassin -> DEF_RULES_DIR\n";
			$f[$index]="my \$DEF_RULES_DIR   = '/usr/share/spamassassin';";
		}
		if(preg_match("#LOCAL_RULES_DIR\s+=#", $line)){
			echo "Starting......: ".date("H:i:s")." spamassassin $spamassassin -> LOCAL_RULES_DIR\n";
			$f[$index]="my \$LOCAL_RULES_DIR   = '/etc/spamassassin';";
		}	
		if(preg_match("#LOCAL_STATE_DIR\s+=#", $line)){
			echo "Starting......: ".date("H:i:s")." spamassassin $spamassassin -> LOCAL_STATE_DIR\n";
			$f[$index]="my \$LOCAL_STATE_DIR   = '/var/lib/spamassassin';";
			break;
		}	
	}
	echo "Starting......: ".date("H:i:s")." spamassassin $spamassassin -> DONE\n";
	@file_put_contents($spamassassin, @implode("\n", $f));

	$f=explode("\n",@file_get_contents($spamd));
	
	foreach ($f as $index=>$line){
		if(preg_match("#DEF_RULES_DIR\s+=#", $line)){
			echo "Starting......: ".date("H:i:s")." spamassassin $spamd -> DEF_RULES_DIR\n";
			$f[$index]="my \$DEF_RULES_DIR   = '/usr/share/spamassassin';";
		}
		if(preg_match("#LOCAL_RULES_DIR\s+=#", $line)){
			echo "Starting......: ".date("H:i:s")." spamassassin $spamd -> LOCAL_RULES_DIR\n";
			$f[$index]="my \$LOCAL_RULES_DIR   = '/etc/spamassassin';";
		}
		if(preg_match("#LOCAL_STATE_DIR\s+=#", $line)){
			echo "Starting......: ".date("H:i:s")." spamassassin $spamd -> LOCAL_STATE_DIR\n";
			$f[$index]="my \$LOCAL_STATE_DIR   = '/var/lib/spamassassin';";
			break;
		}
	}
	echo "Starting......: ".date("H:i:s")." spamassassin $spamd -> DONE\n";
	@file_put_contents($spamd, @implode("\n", $f));	
	
	
	
	$f=explode("\n",@file_get_contents($sa_update));
	foreach ($f as $index=>$line){
		if(preg_match("#DEF_RULES_DIR\s+=#", $line)){
			echo "Starting......: ".date("H:i:s")." spamassassin $sa_update -> DEF_RULES_DIR\n";
			$f[$index]="my \$DEF_RULES_DIR   = '/usr/share/spamassassin';";
		}
		if(preg_match("#LOCAL_RULES_DIR\s+=#", $line)){
			echo "Starting......: ".date("H:i:s")." spamassassin $sa_update -> LOCAL_RULES_DIR\n";
			$f[$index]="my \$LOCAL_RULES_DIR   = '/etc/spamassassin';";
		}
		if(preg_match("#LOCAL_STATE_DIR\s+=#", $line)){
			echo "Starting......: ".date("H:i:s")." spamassassin $sa_update -> LOCAL_STATE_DIR\n";
			$f[$index]="my \$LOCAL_STATE_DIR   = '/var/lib/spamassassin';";
			break;
		}
	}
	echo "Starting......: ".date("H:i:s")." spamassassin $sa_update -> DONE\n";
	@file_put_contents($sa_update, @implode("\n", $f));	
	
	$f=explode("\n",@file_get_contents($sa_learn));
	foreach ($f as $index=>$line){
		if(preg_match("#DEF_RULES_DIR\s+=#", $line)){
			echo "Starting......: ".date("H:i:s")." spamassassin $sa_learn -> DEF_RULES_DIR\n";
			$f[$index]="my \$DEF_RULES_DIR   = '/usr/share/spamassassin';";
		}
		if(preg_match("#LOCAL_RULES_DIR\s+=#", $line)){
			echo "Starting......: ".date("H:i:s")." spamassassin $sa_learn -> LOCAL_RULES_DIR\n";
			$f[$index]="my \$LOCAL_RULES_DIR   = '/etc/spamassassin';";
		}
		if(preg_match("#LOCAL_STATE_DIR\s+=#", $line)){
			echo "Starting......: ".date("H:i:s")." spamassassin $sa_learn -> LOCAL_STATE_DIR\n";
			$f[$index]="my \$LOCAL_STATE_DIR   = '/var/lib/spamassassin';";
			break;
		}
	}
	echo "Starting......: ".date("H:i:s")." spamassassin $sa_learn -> DONE\n";
	@file_put_contents($sa_learn, @implode("\n", $f));	
	
	
}

function sa_update(){
	
	if(system_is_overloaded(basename(__FILE__))){
		writelogs("{OVERLOADED_SYSTEM}, aborting task",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	$unix=new unix();
	$saupdate=$unix->find_program("sa-update");
	$sacompile=$unix->find_program("sa-compile");
	if(is_file("/usr/local/bin/sa-update")){$saupdate="/usr/local/bin/sa-update";}
	if(is_file("/usr/local/bin/sa-compile")){$sacompile="/usr/local/bin/sa-compile";}
	
	if(!is_file($saupdate)){return null;}
	$statusFileContent="/usr/share/artica-postfix/ressources/logs/sa-update-status.txt";	
	$statusFile="/usr/share/artica-postfix/ressources/logs/sa-update-status.html";

	
	
	$wget=$unix->find_program("wget");
	
	
	shell_exec("$wget http://yerp.org/rules/GPG.KEY -O /root/GPG.KEY");
	shell_exec("$saupdate sa-update --import /root/GPG.KEY");
	shell_exec("$wget http://spamassassin.apache.org/updates/GPG.KEY -O /root/GPG.KEY");
	shell_exec("$saupdate sa-update --import /root/GPG.KEY");
	
	
	$content=@file_get_contents("$saupdate");
	if(strpos($content, "/etc/mail/spamassassin")>0){
		$content=str_replace("/etc/mail/spamassassin", "/etc/spamassassin", $content);
		@file_put_contents($saupdate, $content);
		
	}

    echo "Downloading KAM.cf...\n";
	$curl=new ccurl("https://www.pccc.com/downloads/SpamAssassin/contrib/KAM.cf");
	$filetmp=$unix->FILE_TEMP();
	if($curl->GetFile($filetmp)){
	    @unlink("/etc/spamassassin/KAM.cf");
	    @copy($filetmp,"/etc/spamassassin/KAM.cf");
	    @unlink($filetmp);
    }

    echo "Downloading nonKAMrules.cf...\n";
    $curl=new ccurl("https://www.pccc.com/downloads/SpamAssassin/contrib/nonKAMrules.cf");
    if($curl->GetFile($filetmp)){
        @unlink("/etc/spamassassin/nonKAMrules.cf");
        @copy($filetmp,"/etc/spamassassin/nonKAMrules.cf");
        @unlink($filetmp);
    }
	
	
	echo "Running Sa-update...\n";
	$cmd="$saupdate --nogpg -D --channel sa.schaal-it.net --channel updates.spamassassin.org >$statusFileContent 2>&1";
	if($GLOBALS["VERBOSE"]){echo "sa-update:: $cmd\n";}
	shell_exec($cmd);
	shell_exec("/bin/chmod 777 $statusFileContent");
	$f=explode("\n", @file_get_contents($statusFileContent));
    echo "Echo Open $statusFileContent\n";
	foreach ($f as $index=>$line){
		if(preg_match("#updates complete, exiting with code\s+[0-9]+#", $line)){
			if($GLOBALS["VERBOSE"]){echo "sa-update:: $line\n";}
            squid_admin_mysql(2,"Success to [update] Spammasssing databases",null,__FILE__,__LINE__);
			if(is_file($sacompile)){
			    shell_exec("$sacompile --siteconfigpath=/etc/spamassassin");
                squid_admin_mysql(2,"Success to [compile] Spammasssing databases",null,__FILE__,__LINE__);
			}else{
			    echo "Sa-compile ($sacompile), no such binary....\n";
            }
            @unlink($statusFile);
			shell_exec("/etc/init.d/spamassassin reload");
			return;
		}else{
		    if($GLOBALS["VERBOSE"]){echo "[$line] not found\n";}
        }
		
	}
	
if($GLOBALS["VERBOSE"]){echo "sa-update:: FAILED\n";}
}



?>