<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/class.maincf.multi.inc');
include_once(dirname(__FILE__).'/ressources/class.policyd-weight.inc');
include_once(dirname(__FILE__).'/ressources/class.main.hashtables.inc');
include_once(dirname(__FILE__).'/ressources/class.postfix.certificate.inc');
$GLOBALS["RELOAD"]=false;
$GLOBALS["URGENCY"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["PROGRESS_SENDER_DEPENDENT"]=false;
$GLOBALS["POSTFIX_INSTANCE_ID"]=0;
$_GET["LOGFILE"]=PROGRESS_DIR."/interface-postfix.log";
build_syslog();
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--urgency#",implode(" ",$argv))){$GLOBALS["URGENCY"]=true;}
if(preg_match("#--instance-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["POSTFIX_INSTANCE_ID"]=intval($re[1]);}
if(preg_match("#--progress-sender-dependent-relayhosty#",implode(" ",$argv))){$GLOBALS["PROGRESS_SENDER_DEPENDENT"]=true;}
$GLOBALS["MAINCF_ROOT"]=postfix_root();
$unix=new unix();
$pidfile="/etc/artica-postfix/".basename(__FILE__)." ". md5(implode("",$argv)).".pid";
if($unix->process_exists(@file_get_contents($pidfile),basename(__FILE__))){echo "Starting......: ".date("H:i:s")." Postfix configurator already executed PID ". @file_get_contents($pidfile)."\n";exit();}
$pid=getmypid();
echo "Starting......: ".date("H:i:s")." Postfix configurator running $pid\n";
file_put_contents($pidfile,$pid);



$EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
if($EnablePostfix==0){echo "Postfix is not enabled\n";exit();}

$users=new usersMenus();
$GLOBALS["CLASS_USERS_MENUS"]=new usersMenus();
$POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
if($POSTFIX_INSTALLED==0){echo "Postfix is not installed\n";exit();}

$GLOBALS["EnablePostfixMultiInstance"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfixMultiInstance"));
$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
$GLOBALS["EnableBlockUsersTroughInternet"]=intval($main->GET_INFO("EnableBlockUsersTroughInternet"));
$GLOBALS["postconf"]=$unix->find_program("postconf");
$GLOBALS["postmap"]=$unix->find_program("postmap");
$GLOBALS["postfix"]=$unix->find_program("postfix");

if(is_file("{$GLOBALS["MAINCF_ROOT"]}/main.cf.default")){@unlink("{$GLOBALS["MAINCF_ROOT"]}/main.cf.default");}


if($argv[1]=='--clean-main'){postconf_strip_key();exit();}
if($argv[1]=='--wlscreen'){wlscreen();exit();}
if($argv[1]=='--notifs-templates-force'){postfix_templates();exit();}
if($argv[1]=='--loadbalance'){haproxy_compliance();ReloadPostfix(true);exit();}
if($argv[1]=='--ScanLibexec'){ScanLibexec();exit();}
if($argv[1]=="--queue-params"){QueueValues();}


if($argv[1]=='--smtpd-client-restrictions'){
	smtpd_client_restrictions_progress("{starting}",5);
	smtpd_client_restrictions_progress("{building_rules}",15);
	$php=$unix->LOCATE_PHP5_BIN();
	system("/usr/sbin/artica-phpfpm-service -smtpd-restrictions -instanceid {$GLOBALS["POSTFIX_INSTANCE_ID"]} -debug");
	smtpd_client_restrictions_progress("{reloading}",95);

	if(is_file("/etc/init.d/milter-greylist")){
			smtpd_client_restrictions_progress("{reloading} Greylist",96);
			system("/etc/init.d/milter-greylist restart");
	}
	if(is_file("/etc/init.d/mimedefang")){
		smtpd_client_restrictions_progress("{reloading} {APP_MIMEDEFANG}",97);
		system("$php /usr/share/artica-postfix/exec.mimedefang.php --build");
	}
	if(is_file("/etc/init.d/milter-regex")){
		smtpd_client_restrictions_progress("{reloading} {APP_MILTER_REGEX}",98);
		system("/etc/init.d/milter-regex restart");
	}
    smtpd_client_restrictions_progress("{done}",100);
	exit();
}




if($argv[1]=='--assp'){ASSP_LOCALDOMAINS();exit();}
if($argv[1]=='--artica-filter'){MasterCFBuilder(true);exit();}
if($argv[1]=='--ssl'){SMTP_SASL_PROGRESS(true);exit();}
if($argv[1]=='--ssl-on'){MasterCFBuilder(true);exit();}
if($argv[1]=='--ssl-off'){MasterCFBuilder(true);exit();}
if($argv[1]=='--ssl-none'){MasterCFBuilder(false);exit();}
if($argv[1]=='--imap-sockets'){imap_sockets();MailBoxTransport();ReloadPostfix(true);exit();}
if($argv[1]=='--restricted'){exit();}
if($argv[1]=='--banner'){smtp_banner(true);exit();}


if($argv[1]=='--myhostname'){ CleanMyHostname();ReloadPostfix(true);}
if($argv[1]=='--others-values'){OthersValues_start();}
if($argv[1]=='--interfaces'){inet_interfaces();MailBoxTransport();exec("{$GLOBALS["postfix"]} stop");exec("{$GLOBALS["postfix"]} start");ReloadPostfix(true);exit();}
if($argv[1]=='--mailbox-transport'){MailBoxTransport();ReloadPostfix(true);exit();}
if($argv[1]=='--disable-smtp-sasl'){disable_smtp_sasl();ReloadPostfix(true);exit();}
if($argv[1]=='--perso-settings'){perso_settings();exit();}
if($argv[1]=='--luser-relay'){luser_relay();exit();}
if($argv[1]=='--smtp-sender-restrictions'){smtp_cmdline_restrictions();ReloadPostfix(true);exit();}
if($argv[1]=='--postdrop-perms'){fix_postdrop_perms();exit;}
if($argv[1]=='--smtpd-restrictions'){smtp_cmdline_restrictions();exit();}
if($argv[1]=='--repair-locks'){repair_locks();exit;}
if($argv[1]=='--smtp-sasl'){SMTP_SASL_PROGRESS();exit;}
if($argv[1]=='--memory'){memory();exit;}
if($argv[1]=='--postscreen'){postscreen($argv[2]);ReloadPostfix(true);exit;}
if($argv[1]=='--freeze'){PostfixFreeze();exit;}
if($argv[1]=='--amavis-internal'){amavis_internal();ReloadPostfix(true);exit;}
if($argv[1]=='--notifs-templates'){postfix_templates();ReloadPostfix(true);exit;}
if($argv[1]=='--restricted-domains'){exit;}
if($argv[1]=='--debug-peer-list'){debug_peer_list();ReloadPostfix(true);exit();}
if($argv[1]=='--milters'){smtpd_milters();RestartPostix();exit();}
if($argv[1]=='--cleanup'){CleanUpMainCf();exit();}
if($argv[1]=='--milters-progress'){milters();}

if ($argv[1] == "--syslog") {
    $GLOBALS["OUTPUT"] = true;
    build_syslog();
    exit();
}
function build_syslog()
{
    $md5 = null;
    $tfile = "/etc/rsyslog.d/postfix.conf";
    if (is_file($tfile)) {
        $md5 = md5_file($tfile);
    }
    $unix = new unix();
    $php = $unix->LOCATE_PHP5_BIN();
    $h = array();
    $h[] = "if  (\$programname  contains 'postfix') then {";
    if(function_exists("BuildRemoteSyslogs")) {
        $h[] = BuildRemoteSyslogs('postfix');
    }
    $h[] = "\t-/var/log/mail.log";

    $h[] = "\t& stop";
    $h[] = "}";
    $h[] = "";
    @file_put_contents($tfile, @implode("\n", $h));

    $md52 = md5_file($tfile);
    if ($md52 <> $md5) {
        system("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
    }

}
function SEND_PROGRESS($POURC,$text,$error=null):bool{
    if($POURC>0){
        $GLOBALS["POURC"]=$POURC;
    }else{
        $POURC=$GLOBALS["POURC"]+1;
        if($POURC>95){$POURC=95;}
        $GLOBALS["POURC"]=$POURC;
    }
    $instance_id=intval($GLOBALS["POSTFIX_INSTANCE_ID"]);
    $unix=new unix();
    $unix->framework_progress($POURC,$text,"POSTFIX_COMPILES.$instance_id","POSTFIX_COMPILES.$instance_id.txt");

    if($instance_id>0) {
        $unix->framework_progress(75, "$POURC: $text", "postfix-multi.$instance_id.reinstall.progress");

        if($POURC>95) {
            $POURC = 95;
        }
        $unix->framework_progress($POURC, "$POURC: maincf:$text", "postfix-multi.$instance_id.reconfigure.progress");

    }
    return true;
}
function build_progress_mime_header($text,$pourc):bool{
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/HEADER_CHECK";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
    return true;
}
function build_progress_othervalues($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"postfix.othervalues.progress");
}



function OthersValues_start(){
	build_progress_othervalues("{starting} OthersValues()...",15);
	OthersValues();
	build_progress_othervalues("Clean my Hostname",80);
	CleanMyHostname();
	build_progress_othervalues("{reloading}",90);
	ReloadPostfix(true);
	build_progress_othervalues("{done}",100);
	exit();
}


function milters():bool{
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
    SEND_PROGRESS(0,"{starting} {filtering_modules}");
	milters_progress("{starting} {filtering_modules}",15);



	milters_progress("{checking} Anti-Spam",25);
	amavis_internal();
	shell_exec("$php /usr/share/artica-postfix/exec.spamassassin.php");
	milters_progress("{checking} {milters_plugins}",30);
	smtpd_milters();
	milters_progress("{checking} MASTER CF",40);
	MasterCFBuilder(true);
	
	$SpamAssMilterEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SpamAssMilterEnabled"));
	$EnableMilterRegex=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterRegex"));
	$MilterGreyListEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MilterGreyListEnabled"));
	echo "SpamAssassin Milter: $SpamAssMilterEnabled\n";
	echo "Regex Milter.......: $EnableMilterRegex\n";
	echo "Greylist Milter....: $MilterGreyListEnabled\n";
	

	
	if($EnableMilterRegex==1){
		if(is_file("/etc/init.d/milter-regex")){
			milters_progress("{starting} {milter_regex}",46);
			system("/etc/init.d/milter-regex restart");
		}
	}
	
	if($MilterGreyListEnabled==1){
		milters_progress("{restarting} GreyList",47);
		system("/etc/init.d/milter-greylist restart");
	}
	milters_progress("{reloading}",90);
	ReloadPostfix(true);
	milters_progress("{done}",100);
	return true;
}

if($argv[1]=='--reconfigure'){
	$instance_id=$GLOBALS["POSTFIX_INSTANCE_ID"];
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/postfix.reconfigure2.$instance_id.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: reconfigure2: Postfix Already Artica task running PID $pid since {$time}mn\n";}
		exit();
	}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".reconfigure.$instance_id.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix Already Artica task running PID $pid since {$time}mn\n";}
		exit();
	}
	@file_put_contents($pidfile, getmypid());
    $main=new main_cf(0,$instance_id);
    $postfix_root=postfix_root();
    if(!is_dir($postfix_root."/hash_files")){@mkdir($postfix_root."/hash_files",0755,true);}


	$t1=time();

	SEND_PROGRESS(2,"Writing mainc.cf...");
	$main->save_conf_to_server(1);

	SEND_PROGRESS(4,"Writing mainc.cf done...");
	if(!is_file("$postfix_root/hash_files/header_checks.cf")){@file_put_contents("$postfix_root/hash_files/header_checks.cf","#");}

	SEND_PROGRESS(5,"Building all settings...");
	_DefaultSettings();

	$unix->send_email_events("Postfix: postfix compilation done. Took :".$unix->distanceOfTimeInWords($t1,time()), "No content yet...\nShould be an added feature :=)", "postfix");
	SEND_PROGRESS(100,"Configuration done");
	exit();
}




_DefaultSettings();

function postfix_root():string{
    $instance_id=intval($GLOBALS["POSTFIX_INSTANCE_ID"]);
    if($instance_id==0){
        return "/etc/postfix";
    }

    return "/etc/postfix-instance{$instance_id}";
}

function smtp_cmdline_restrictions(){
	    $main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	    $disable_vrfy_command=intval($main->GET_INFO("disable_vrfy_command"));
	    if($disable_vrfy_command==1){postconf("disable_vrfy_command","yes");}else{postconf("disable_vrfy_command","no");}


		if($GLOBALS["VERBOSE"]){echo "\n ***\nStarting......: ".date("H:i:s")." Postfix -> smtpd_data_restrictions() function\n ***\n";}
		smtpd_data_restrictions();
		if($GLOBALS["RELOAD"]){
			if($GLOBALS["VERBOSE"]){echo "\n ***\nStarting......: ".date("H:i:s")." Postfix -> ReloadPostfix() function\n ***\n";}
			ReloadPostfix(true);
			
		}	

	
}

function smtpd_data_restrictions(){
	include_once(dirname(__FILE__)."/ressources/class.smtp_data_restrictions.inc");
	$smtpd_data_restrictions=new smtpd_data_restrictions("master");
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Postfix -> smtpd_data_restrictions->compile() function\n";}
	$smtpd_data_restrictions->compile();
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Postfix -> compiled \"$smtpd_data_restrictions->restriction_final\"\n";}
	if($smtpd_data_restrictions->restriction_final<>null){
		postconf("smtpd_data_restrictions",$smtpd_data_restrictions->restriction_final);
	}
}

function HashTables($start=0):bool{
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.postfix.hashtables.php --pourc=$start --instance-id={$GLOBALS["POSTFIX_INSTANCE_ID"]}");
    return true;
}

function _DefaultSettings(){
    $unix=new unix();
    $unix->POSTCONF_SET("debug_peer_level",2);

	$start=5;
	$functions=array("CleanUpMainCf","debug_peer_list",
		"cleanMultiplesInstances","SetTLS","inet_interfaces","imap_sockets","MailBoxTransport",
		"mime_header_checks",
		"smtpd_sasl_exceptions_networks","CleanMyHostname","OthersValues",
		"perso_settings","remove_virtual_mailbox_base","postscreen",
		"smtp_sasl_security_options","smtp_sasl_auth_enable","BodyChecks","postfix_templates","haproxy_compliance","smtpd_milters",
		"MasterCFBuilder","ReloadPostfix"
			
			
	);
	
	$tot=count($functions);
	$i=0;
    foreach ($functions as $func){
		$i++;
		$start++;
		if(!function_exists($func)){
			SEND_PROGRESS($start,$func,"Error $func no such function...");
			continue;
		}
        $t1=time();
        echo " ****************************************************\n";
        echo " ******************** $func *************************\n";
        echo " ****************************************************\n\n\n";
		try {
			SEND_PROGRESS($start,"Action 1, {$start}% Please wait, executing $func() $i/$tot..");
			call_user_func($func);
            $t2=time();
		} catch (Exception $e) {
			SEND_PROGRESS($start,$func,"Error on $func ($e)");
		}
       $MAIN_TIME[$func]=$unix->distanceOfTimeInWords($t1,$t2);

	}

    foreach ($MAIN_TIME as $function=>$xtime){
        echo "$function took $xtime\n";

    }

    echo " ****************************************************\n";
    echo " ******************** HashTables *************************\n";
    echo " ****************************************************\n\n\n";
    echo "\n\n\n\n";
    SEND_PROGRESS($start++,"HashTables");
	HashTables($start);

}



if($argv[1]=='--write-maincf'){
	$unix=new unix();
	_DefaultSettings();
	perso_settings();
	if($argv[2]=='no-restart'){appliSecu();exit();}
	echo "Starting......: ".date("H:i:s")." restarting postfix\n";
	$unix->send_email_events("Postfix will be restarted","Line: ". __LINE__."\nIn order to apply new configuration file","postfix");
	shell_exec("/etc/init.d/postfix restart-single");

	exit();
}

if($argv[1]=='--maincf'){
	$main=new main_cf();
	$main->save_conf_to_server(1);
	file_put_contents($GLOBALS["MAINCF_ROOT"].'/main.cf',$main->main_cf_datas);
	_DefaultSettings();
	perso_settings();
	if($GLOBALS["DEBUG"]){echo @file_get_contents("{$GLOBALS["MAINCF_ROOT"]}/main.cf");}
	HashTables();
	exit();
}





function ASSP_LOCALDOMAINS(){
	if($GLOBALS["EnablePostfixMultiInstance"]==1){return null;}
	if(!is_dir("/usr/share/assp/files")){return null;}
	$ldap=new clladp();
	$conf=null;
	$domains=$ldap->hash_get_all_domains();
    foreach ($domains as $num=>$ligne){
		$conf=$conf."$ligne\n";
	}
	echo "Starting......: ".date("H:i:s")." ASSP ". count($domains)." local domains\n"; 
	@file_put_contents("/usr/share/assp/files/localdomains.txt",$conf);
	HashTables();
	
}

function SetSASLMech(){
	$unix=new unix();
	$echo=$unix->find_program("echo");
	$saslpasswd2=$unix->find_program("saslpasswd2");


    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.saslauthd.php --build");

	if(!is_file("$saslpasswd2")){
		echo "Starting......: ".date("H:i:s")." saslpasswd2 doesn''t exists!!!\n";
		return;
	}
	
	if(!is_dir("/var/spool/postfix/etc")){
		echo "Starting......: ".date("H:i:s")." Creating /var/spool/postfix/etc\n";
		@mkdir("/var/spool/postfix/etc",0755,true);
	}
	
	if(!is_file("/var/spool/postfix/etc/sasldb2")){
		echo "Starting......: ".date("H:i:s")." Creating /var/spool/postfix/etc/sasldb2 doesn't exists, create it\n";
		system("$echo cyrus|$saslpasswd2 -c cyrus");
	}
	
	if(is_file("/etc/sasldb2")){
		@file_put_contents("/var/spool/postfix/etc/sasldb2", @file_get_contents("/etc/sasldb2"));
		
	}

	$unix->chown_func("root","root","/var/spool/postfix/etc/sasldb2");
	@chmod("/var/spool/postfix/etc/sasldb2", 0755);

	
}






function BodyChecks_progress($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "BodyChecks[$pourc]: $text\n";
	@file_put_contents(PROGRESS_DIR."/postfix.BodyChecks.progress", serialize($array));
	@chmod(PROGRESS_DIR."/postfix.BodyChecks.progress",0755);


}








function SetTLS(){
	
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);

	if($main->GET_INFO('smtp_sender_dependent_authentication')==1){
		postconf("smtp_sender_dependent_authentication","yes");
		postconf("smtp_sasl_auth_enable","yes");
        postconf("smtp_sasl_security_options","noanonymous");

	}
	
	$broken_sasl_auth_clients=$main->GET("broken_sasl_auth_clients");
	$smtpd_sasl_authenticated_header=$main->GET("smtpd_sasl_authenticated_header");
	$smtpd_sasl_security_options=$main->GET("smtpd_sasl_security_options");
	
	if(!is_numeric($broken_sasl_auth_clients)){$broken_sasl_auth_clients=1;}
	if(!is_numeric($smtpd_sasl_authenticated_header)){$smtpd_sasl_authenticated_header=1;}
	
	if($smtpd_sasl_security_options==null){$smtpd_sasl_security_options="noanonymous";}
	
	
	
	postconf("broken_sasl_auth_clients",$main->YesNo($broken_sasl_auth_clients));
	postconf("smtpd_sasl_local_domain",$main->GET("smtpd_sasl_local_domain"));
	postconf("smtpd_sasl_authenticated_header",$main->YesNo($smtpd_sasl_authenticated_header));
	postconf("smtpd_sasl_security_options",$smtpd_sasl_security_options);
}








function buildtables_background(){
	$unix=new unix();	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	system("$php5 /usr/share/artica-postfix/exec.postfix.hashtables.php");
}

function RestartPostix():bool{
	$unix=new unix();
	$postfix=$unix->find_program("postfix");
	if(!is_file($postfix)){return false;}
    SEND_PROGRESS(0,"{stopping} {APP_POSTFIX}");
    shell_exec("$postfix stop >/dev/null 2>&1");
    SEND_PROGRESS(0,"{starting} {APP_POSTFIX}");
	shell_exec("$postfix start >/dev/null 2>&1");
    SEND_PROGRESS(0,"{starting} {APP_POSTFIX} {done}");
    return true;
}

function ReloadPostfixSimple(){
	$unix=new unix();
	$postfix=$unix->find_program("postfix");
	shell_exec("/etc/init.d/artica-policy restart");
	if(is_file($postfix)){shell_exec("$postfix reload >/dev/null 2>&1");return;}
}

function PostfixFreeze_progress($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile=PROGRESS_DIR."/postqueue";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}




function PostfixFreeze(){
    $main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
    $freeze_delivery_queue=intval($main->GET("freeze_delivery_queue"));
    PostfixFreeze_progress(10,"{switch}...");
    if($freeze_delivery_queue==0){$main->SET("freeze_delivery_queue",1);}
    if($freeze_delivery_queue==1){$main->SET("freeze_delivery_queue",0);}
    PostfixFreeze_progress(50,"{reloading}...");
    ReloadPostfix(true);
    PostfixFreeze_progress(100,"{success}...");

}

function ReloadPostfix($nohastables=false){
	$ldap=new clladp();
	$unix=new unix();
	$dom=array();
    $main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
    $myOrigin=trim($main->GET("myorigin"));
    if($myOrigin==null) {
        $myOrigin = $unix->hostname_g();

        SEND_PROGRESS(0, "{reloading} {APP_POSTFIX}");
        if ($myOrigin == null) {
            $domains = $ldap->Hash_domains_table();
            if (count($domains) > 0) {
                foreach ($domains as $num => $ligne) {
                    $dom[] = $num;
                }
                $myOrigin = $dom[0];
            }
        }
    }

	if($myOrigin==null){$myOrigin="localhost.localdomain";}
    SEND_PROGRESS(0,"{reloading} {APP_POSTFIX}");
	$daemon_directory=$unix->LOCATE_POSTFIX_DAEMON_DIRECTORY();
	echo "Starting......: ".date("H:i:s")." Postfix daemon directory \"$daemon_directory\"\n";
	postconf("daemon_directory",$daemon_directory);

    SEND_PROGRESS(0,"{reloading} {APP_POSTFIX}");
	if($myOrigin==null){$myOrigin="localhost.localdomain";}
	
	if(!$nohastables){
		echo "Starting......: ".date("H:i:s")." Postfix launch datases compilation...\n";
		buildtables_background();
	}
    SEND_PROGRESS(0,"{reloading} {APP_POSTFIX}");
	postconf("myorigin","$myOrigin");
	postconf("smtpd_delay_reject","yes");
    SEND_PROGRESS(0,"{reloading} {APP_POSTFIX}");
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$freeze_delivery_queue=$main->GET("freeze_delivery_queue");
	if($freeze_delivery_queue==1){
		postconf("master_service_disable","qmgr.fifo");
		postconf("in_flow_delay","0");
	}else{
		postconf("master_service_disable","");
		$in_flow_delay=$main->GET("in_flow_delay");
		if($in_flow_delay==null){$in_flow_delay="1s";}
		postconf("in_flow_delay",$in_flow_delay);		
	}


    SEND_PROGRESS(0,"{reloading} {APP_POSTFIX}");
	postconf_strip_key();
    SEND_PROGRESS(0,"{reloading} {APP_POSTFIX}");
	echo "Starting......: ".date("H:i:s")." Postfix Apply securities issues\n";
    PostfixFreeze_progress(55,"{apply_securities}...");
    SEND_PROGRESS(0,"{reloading} {APP_POSTFIX}");
	appliSecu();
    SEND_PROGRESS(0,"{reloading} {APP_POSTFIX}");
    ScanLibexec();
    SEND_PROGRESS(0,"{reloading} {APP_POSTFIX}");
	CleanUpMainCf();
    SEND_PROGRESS(0,"{reloading} {APP_POSTFIX}");
    $unix->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
    PostfixFreeze_progress(100,"{success}...");
    SEND_PROGRESS(0,"{reloading} {APP_POSTFIX} {done}");
	
}

function appliSecu(){
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	echo "Starting......: ".date("H:i:s")." Postfix verify permissions...\n"; 
	if(is_file("/var/lib/postfix/smtpd_tls_session_cache.db")){shell_exec("/bin/chown postfix:postfix /var/lib/postfix/smtpd_tls_session_cache.db");}
	if(is_file("/var/lib/postfix/master.lock")){@chown("/var/lib/postfix/master.lock","postfix");}
	if(is_dir("/var/spool/postfix/pid")){@chown("/var/spool/postfix/pid", "root");}
	if(is_file("/usr/sbin/postqueue")){
		@chgrp("/usr/sbin/postqueue", "postdrop");
		@chmod("/usr/sbin/postqueue",0755);
		shell_exec("$chmod g+s /usr/sbin/postqueue");
 		
	}
	if(is_file("/usr/sbin/postdrop")){
		@chgrp("/usr/sbin/postdrop", "postdrop");
		@chmod("/usr/sbin/postdrop",0755);
		shell_exec("$chmod g+s /usr/sbin/postdrop");
	}
	if(is_dir("/var/spool/postfix/public")){@chgrp("/var/spool/postfix/public", "postdrop");}
	if(is_dir("/var/spool/postfix/maildrop")){@chgrp("/var/spool/postfix/maildrop", "postdrop");}
	echo "Starting......: ".date("H:i:s")." Postfix verify permissions done\n";
	
	
	
}

function imap_sockets(){
	return;
	
}





function smtp_sasl_auth_enable(){
	$ldap=new clladp();
	if($ldap->ldapFailed){
		echo "Starting......: ".date("H:i:s")." SMTP SALS connection to ldap failed\n";
		return;
	}

	$suffix="dc=organizations,$ldap->suffix";
	$filter="(&(objectclass=SenderDependentSaslInfos)(SenderCanonicalRelayPassword=*))";
	$res=array();
    $count = 0;
    if($ldap->ldap_connection) {
        $search = @ldap_search($ldap->ldap_connection, $suffix, "$filter", array());
        if ($search) {
            $hash = ldap_get_entries($ldap->ldap_connection, $search);
            $count = $hash["count"];
        }
    }
	
	echo "Starting......: ".date("H:i:s")." SMTP SALS $count account(s)\n"; 	
	if($count>0){
		postconf("smtp_sasl_auth_enable","yes");
		postconf("smtp_sender_dependent_authentication","yes");
		
		
	}else{
		postconf("smtp_sender_dependent_authentication","no");
		
	}

}



function smtpd_client_restrictions_progress($text,$pourc):bool{
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/smtpd_client_restrictions_progress";
    SEND_PROGRESS(0,"{starting} $text");
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
    return true;
}
function milters_progress($text,$pourc):bool{
	$echotext=$text;
    SEND_PROGRESS(0,"{starting} $text");
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/smtpd_milters";

	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
    return true;
}

	
function amavis_internal(){
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
				$f[]="{$ligne["ip_addr"]}\tFILTER smtp:[127.0.0.1]:10025";
			}
		}
	}
	
	$postmap=$unix->find_program("postmap");
	$f[]="";
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/amavis_internal",@implode("\n",$f));
	shell_exec("$postmap hash:{$GLOBALS["MAINCF_ROOT"]}/amavis_internal");
	return $count;
}	




	
function __ADD_smtpd_restriction_classes($classname){
exec("{$GLOBALS["postconf"]} -h smtpd_restriction_classes",$datas);
	$tbl=explode(",",implode(" ",$datas));
	

	if(is_array($tbl)){
		foreach ($tbl as $num=>$ligne){
		if(trim($ligne)==null){continue;}
		$newHash[$ligne]=$ligne;
		}
	}
	
	unset($newHash[$classname]);
	
	if(is_array($newHash)){
        foreach ($newHash as $num=>$ligne){
			$smtpd_restriction_classes[]=$num;
		}
	}
	
	$smtpd_restriction_classes[]=$classname;
	if(is_array($smtpd_restriction_classes)){$newval=implode(",",$smtpd_restriction_classes);}
	
	postconf("smtpd_restriction_classes",$newval);
		
	
}

function __REMOVE_smtpd_restriction_classes($classname){
	exec("{$GLOBALS["postconf"]} -h smtpd_restriction_classes",$datas);
	$tbl=explode(",",implode(" ",$datas));
	$newHash=array();

	if(is_array($tbl)){
		foreach ($tbl as $num=>$ligne){
		if(trim($ligne)==null){continue;}
		$newHash[$ligne]=$ligne;
		}
	}
	
	unset($newHash[$classname]);
	
	if(is_array($newHash)){
        foreach ($newHash as $num=>$ligne){
			$smtpd_restriction_classes[]=$num;
		}
	}
	
	if(is_array($smtpd_restriction_classes)){$newval=implode(",",$smtpd_restriction_classes);}
	postconf("smtpd_restriction_classes",$newval);
}
	
	
function smtpd_recipient_restrictions_reject_forged_mails(){
	$ldap=new clladp();
	$unix=new unix();
	$postmap=$unix->find_program("postmap");
	$hash=$ldap->hash_get_all_domains();
	if(!is_array($hash)){return false;}
    foreach ($hash as $domain=>$ligne){
		$f[]="$domain\t 554 $domain FORGED MAIL";
		
	}
	
	if(!is_array($f)){return false;}
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/disallow_my_domain",@implode("\n",$f));
	echo "Starting......: ".date("H:i:s")." compiling domains against forged messages\n";
	shell_exec("$postmap hash:{$GLOBALS["MAINCF_ROOT"]}/disallow_my_domain");
	return true;
}



function CleanMyHostname(){
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$MyHostnameSQL=trim($main->GET("myhostname"));
	if($MyHostnameSQL==null){
        $MyHostnameSQL=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));
        if($MyHostnameSQL==null){$MyHostnameSQL="localhost.localdomain";}
        if(strpos($MyHostnameSQL,".")==0){$MyHostnameSQL="$MyHostnameSQL.localdomain";}
		$main->SET("myhostname",$MyHostnameSQL);
	}
	postconf("myhostname",$MyHostnameSQL);
	
	$smtp_helo_name=$main->GET('smtp_helo_name');
	if($smtp_helo_name==null){$smtp_helo_name=$MyHostnameSQL;}
	postconf("smtp_helo_name",$smtp_helo_name);
	
	
	exec("{$GLOBALS["postconf"]} -h myhostname",$results);
	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
	$myhostname=trim(implode("",$results));
	$myhostname=str_replace("header_checks =","",$myhostname);
	exec("{$GLOBALS["postconf"]} -h relayhost",$results);
	
	if(is_array($results)){
		$relayhost=trim(@implode("",$results));
	}
	
	if($myhostname=="Array.local"){
		if(!isset($GLOBALS["CLASS_USERS_MENUS"])){$users=new usersMenus();$GLOBALS["CLASS_USERS_MENUS"]=$users;}else{$users=$GLOBALS["CLASS_USERS_MENUS"];}
		$myhostname=$users->hostname;
	}
	
	if($relayhost<>null){
		if($myhostname==$relayhost){
			$myhostname="$myhostname.local";
		}
	}
	
	//fix bug with extension.
	
	$myhostname=str_replace(".local.local.",".local",$myhostname);
	$myhostname=str_replace(".locallocal.locallocal.",".",$myhostname);
	$myhostname=str_replace(".locallocal",".local",$myhostname);
	$myhostname=str_replace(".local.local",".local",$myhostname);
	
	$myhostname2=trim($sock->GET_INFO("myhostname"));
	if(strlen($myhostname2)>0){$myhostname=$myhostname2;}
    if(strpos($myhostname,".")==0){$myhostname="$myhostname.localdomain";}
	echo "Starting......: ".date("H:i:s")." Hostname=$myhostname\n";
	postconf("myhostname",$myhostname);
	
	
	
	
}

function smtpd_sasl_exceptions_networks(){
	$nets=array();
	$IPClass=new IP();
	$q=new mysql();
	if(!$q->TABLE_EXISTS("smtpd_sasl_exceptions_networks", "artica_backup")){
		postconf("smtpd_sasl_exceptions_networks",null);
		return;
	}
	$f=array();
	$sql="SELECT * FROM smtpd_sasl_exceptions_networks WHERE enabled=1";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$FINAL_ADDED=false;
	$FINAL_TEXT="0.0.0.0/0";
    if($results) {
        while ($ligne = mysqli_fetch_assoc($results)) {
            $pattern = trim($ligne["pattern"]);
            $com = null;
            if ($ligne["allow"] == 1) {
                $com = "!";
            }
            if ($pattern == null) {
                continue;
            }
            if ($pattern == "0.0.0.0/0") {
                $FINAL_TEXT = "{$com}$pattern";
                continue;
            }
            if ($IPClass->isIPAddressOrRange($pattern)) {
                $f[] = "{$com}$pattern";
                continue;
            }
            if (strpos(" $pattern", ".") == 0) {
                continue;
            }
            if (preg_match("#^\.(.+)#", $pattern, $re)) {
                $pattern = $re[1];
            }
            $f[] = "{$com}.$pattern";

        }
    }
	
	
	if(count($f)>0){
		$f[]="$FINAL_TEXT\n";
		echo "Starting......: ".date("H:i:s")." SASL exceptions enabled\n";
		@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/smtpd_sasl_exceptions_networks.conf", @implode("\n", $f));
		postconf("smtpd_sasl_exceptions_networks",'"{$GLOBALS["MAINCF_ROOT"]}/smtpd_sasl_exceptions_networks.conf"');
		
	}else{
		echo "Starting......: ".date("H:i:s")." SASL exceptions disabled\n";
		postconf("smtpd_sasl_exceptions_networks",null);
		
	}
}



function smtp_banner(){
	$mainmulti=new maincf_multi("master","master");
	$smtpd_banner=$mainmulti->GET('smtpd_banner');
	echo 	"$smtpd_banner\n";
}

function QueueValues(){
    $Vals[]="minimal_backoff_time";
    $Vals[]="maximal_backoff_time";
    $Vals[]="bounce_queue_lifetime";
    $Vals[]="maximal_queue_lifetime";
    $Vals[]="in_flow_delay";
    $Vals[]="queue_run_delay";
    $mainmulti=new maincf_multi("master","master");
    foreach ($Vals as $key){
        $value=trim($mainmulti->GET($key));
        if($value==null){continue;}
        postconf($key,$value);

    }

    shell_exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");

}

function OthersValues(){
	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
	if($sock->GET_INFO("EnablePostfixMultiInstance")==1){return;}	
	$main=new main_cf();
	$mainmulti=new maincf_multi("master","master");
	$main->FillDefaults();	
	echo "Starting......: ".date("H:i:s")." Fix others settings\n";

    shell_exec("/usr/sbin/artica-phpfpm-service -postfix-other-values");

	


	$smtp_connection_reuse_time_limit=$mainmulti->GET("smtp_connection_reuse_time_limit");
	$connection_cache_ttl_limit=$mainmulti->GET("connection_cache_ttl_limit");
	$connection_cache_status_update_time=$mainmulti->GET("connection_cache_status_update_time");
	$smtp_connection_cache_destinations=unserializeb64($mainmulti->GET_BIGDATA("smtp_connection_cache_destinations"));


	$address_verify_map=$mainmulti->GET("address_verify_map");
	$address_verify_negative_cache=$mainmulti->GET("address_verify_negative_cache");
	$address_verify_poll_count=$mainmulti->GET("address_verify_poll_count");
	$address_verify_poll_delay=$mainmulti->GET("address_verify_poll_delay");
	$address_verify_sender=$mainmulti->GET("address_verify_sender");
	$address_verify_negative_expire_time=$mainmulti->GET("address_verify_negative_expire_time");
	$address_verify_negative_refresh_time=$mainmulti->GET("address_verify_negative_refresh_time");
	$address_verify_positive_expire_time=$mainmulti->GET("address_verify_positive_expire_time");
	$address_verify_positive_refresh_time=$mainmulti->GET("address_verify_positive_refresh_time");
	if($address_verify_map==null){$address_verify_map="btree:/var/lib/postfix/verify";}
	
	$smtpd_error_sleep_time=$mainmulti->GET("smtpd_error_sleep_time");
	$smtpd_soft_error_limit=$mainmulti->GET("smtpd_soft_error_limit");
	$smtpd_hard_error_limit=$mainmulti->GET("smtpd_hard_error_limit");
	$smtpd_client_connection_count_limit=$mainmulti->GET("smtpd_client_connection_count_limit");
	$smtpd_client_connection_rate_limit=$mainmulti->GET("smtpd_client_connection_rate_limit");
	$smtpd_client_message_rate_limit=$mainmulti->GET("smtpd_client_message_rate_limit");
	$smtpd_client_recipient_rate_limit=$mainmulti->GET("smtpd_client_recipient_rate_limit");
	$smtpd_client_new_tls_session_rate_limit=$mainmulti->GET("smtpd_client_new_tls_session_rate_limit");
	$smtpd_client_event_limit_exceptions=$mainmulti->GET("smtpd_client_event_limit_exceptions");
	$in_flow_delay=$mainmulti->GET("in_flow_delay");
	$smtp_connect_timeout=$mainmulti->GET("smtp_connect_timeout");
	$smtp_helo_timeout=$mainmulti->GET("smtp_helo_timeout");
	$initial_destination_concurrency=$mainmulti->GET("initial_destination_concurrency");
	$default_destination_concurrency_limit=$mainmulti->GET("default_destination_concurrency_limit");
	$local_destination_concurrency_limit=$mainmulti->GET("local_destination_concurrency_limit");
	$smtp_destination_concurrency_limit=$mainmulti->GET("smtp_destination_concurrency_limit");
	$default_destination_recipient_limit=$mainmulti->GET("default_destination_recipient_limit");
	$smtpd_recipient_limit=$mainmulti->GET("smtpd_recipient_limit");
	$queue_run_delay=$mainmulti->GET("queue_run_delay");  
	$minimal_backoff_time =$mainmulti->GET("minimal_backoff_time");
	$maximal_backoff_time =$mainmulti->GET("maximal_backoff_time");
	$maximal_queue_lifetime=$mainmulti->GET("maximal_queue_lifetime"); 
	$bounce_queue_lifetime =$mainmulti->GET("bounce_queue_lifetime");
	$qmgr_message_recipient_limit =$mainmulti->GET("qmgr_message_recipient_limit");
	$default_process_limit=$mainmulti->GET("default_process_limit");	
	$smtp_fallback_relay=$mainmulti->GET("smtp_fallback_relay");
	$smtpd_reject_unlisted_recipient=$mainmulti->GET("smtpd_reject_unlisted_recipient");
	$smtpd_reject_unlisted_sender=$mainmulti->GET("smtpd_reject_unlisted_sender");

	$ignore_mx_lookup_error=$mainmulti->GET("ignore_mx_lookup_error");
	$disable_dns_lookups=$mainmulti->GET("disable_dns_lookups");
	$smtpd_banner=$mainmulti->GET('smtpd_banner');
	$enable_original_recipient=$mainmulti->GET("enable_original_recipient");
	$undisclosed_recipients_header=$mainmulti->GET("undisclosed_recipients_header");
	$smtpd_discard_ehlo_keywords=$mainmulti->GET("smtpd_discard_ehlo_keywords");
	
	
	$detect_8bit_encoding_header=$mainmulti->GET("detect_8bit_encoding_header");
	$disable_mime_input_processing=$mainmulti->GET("disable_mime_input_processing");
	$disable_mime_output_conversion=$mainmulti->GET("disable_mime_output_conversion");
	
	
	if(!is_numeric($detect_8bit_encoding_header)){$detect_8bit_encoding_header=1;}
	if(!is_numeric($disable_mime_input_processing)){$disable_mime_input_processing=0;}
	if(!is_numeric($disable_mime_output_conversion)){$disable_mime_output_conversion=0;}
	
	
	if(!is_numeric($ignore_mx_lookup_error)){$ignore_mx_lookup_error=0;}
	if(!is_numeric($disable_dns_lookups)){$disable_dns_lookups=0;}
	if(!is_numeric($smtpd_reject_unlisted_recipient)){$smtpd_reject_unlisted_recipient=1;}
	if(!is_numeric($smtpd_reject_unlisted_sender)){$smtpd_reject_unlisted_sender=0;}
	
		
	


	
	


	if($smtp_connection_reuse_time_limit==null){$smtp_connection_reuse_time_limit="300s";}
	if($connection_cache_ttl_limit==null){$connection_cache_ttl_limit="2s";}
	if($connection_cache_status_update_time==null){$connection_cache_status_update_time="600s";}	

	
	if(count($smtp_connection_cache_destinations)>0){
        foreach ($smtp_connection_cache_destinations as $host=>$none){
		$smtp_connection_cache_destinationsR[]=$host;
        }
		$smtp_connection_cache_destinationsF=@implode(",", $smtp_connection_cache_destinationsR);
	}
	

	if(!is_numeric($address_verify_negative_cache)){$address_verify_negative_cache=1;}
	if(!is_numeric($address_verify_poll_count)){$address_verify_poll_count=3;}
	if($address_verify_poll_delay==null){$address_verify_poll_delay="3s";}
	if($address_verify_sender==null){$address_verify_sender="double-bounce";}
	if($address_verify_negative_expire_time==null){$address_verify_negative_expire_time="3d";}
	if($address_verify_negative_refresh_time==null){$address_verify_negative_refresh_time="3h";}
	if($address_verify_positive_expire_time==null){$address_verify_positive_expire_time="31d";}
	if($address_verify_positive_refresh_time==null){$address_verify_positive_refresh_time="7d";}
	if($smtpd_error_sleep_time==null){$smtpd_error_sleep_time="1s";}
	if(!is_numeric($smtpd_soft_error_limit)){$smtpd_soft_error_limit=10;}
	if(!is_numeric($smtpd_hard_error_limit)){$smtpd_hard_error_limit=20;}
	if(!is_numeric($smtpd_client_connection_count_limit)){$smtpd_client_connection_count_limit=50;}
	if(!is_numeric($smtpd_client_connection_rate_limit)){$smtpd_client_connection_rate_limit=0;}
	if(!is_numeric($smtpd_client_message_rate_limit)){$smtpd_client_message_rate_limit=0;}
	if(!is_numeric($smtpd_client_recipient_rate_limit)){$smtpd_client_recipient_rate_limit=0;}
	if(!is_numeric($smtpd_client_new_tls_session_rate_limit)){$smtpd_client_new_tls_session_rate_limit=0;}
	if(!is_numeric($initial_destination_concurrency)){$initial_destination_concurrency=5;}
	if(!is_numeric($default_destination_concurrency_limit)){$default_destination_concurrency_limit=20;}
	if(!is_numeric($smtp_destination_concurrency_limit)){$smtp_destination_concurrency_limit=20;}
	if(!is_numeric($local_destination_concurrency_limit)){$local_destination_concurrency_limit=2;}
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
	if($smtpd_banner==null){$smtpd_banner="\$myhostname ESMTP \$mail_name";}
	
	
	
	$detect_8bit_encoding_header=$mainmulti->YesNo($detect_8bit_encoding_header);
	$disable_mime_input_processing=$mainmulti->YesNo($disable_mime_input_processing);
	$disable_mime_output_conversion=$mainmulti->YesNo($disable_mime_output_conversion);
	$smtpd_reject_unlisted_sender=$mainmulti->YesNo($smtpd_reject_unlisted_sender);
	$smtpd_reject_unlisted_recipient=$mainmulti->YesNo($smtpd_reject_unlisted_recipient);
	$ignore_mx_lookup_error=$mainmulti->YesNo($ignore_mx_lookup_error);
	$disable_dns_lookups=$mainmulti->YesNo($disable_dns_lookups);
	
	
	if(!is_numeric($enable_original_recipient)){$enable_original_recipient=1;}
	if($undisclosed_recipients_header==null){$undisclosed_recipients_header="To: undisclosed-recipients:;";}
	$enable_original_recipient=$mainmulti->YesNo($enable_original_recipient);
	

	
	
	
	$mime_nesting_limit=$mainmulti->GET("mime_nesting_limit");
	if(!is_numeric($mime_nesting_limit)){
		$mime_nesting_limit=$sock->GET_INFO("mime_nesting_limit");
	}
	
	if(!is_numeric($mime_nesting_limit)){$mime_nesting_limit=100;}
	
	$main->main_array["default_destination_recipient_limit"]=$sock->GET_INFO("default_destination_recipient_limit");
	$main->main_array["smtpd_recipient_limit"]=$sock->GET_INFO("smtpd_recipient_limit");
	
	$main->main_array["header_address_token_limit"]=$sock->GET_INFO("header_address_token_limit");

	


	if($main->main_array["default_destination_recipient_limit"]==null){$main->main_array["default_destination_recipient_limit"]=50;}
	if($main->main_array["smtpd_recipient_limit"]==null){$main->main_array["smtpd_recipient_limit"]=1000;}
	if($main->main_array["header_address_token_limit"]==null){$main->main_array["header_address_token_limit"]=10240;}
	

	echo "Starting......: ".date("H:i:s")." default_destination_recipient_limit={$main->main_array["default_destination_recipient_limit"]}\n";
	echo "Starting......: ".date("H:i:s")." smtpd_recipient_limit={$main->main_array["smtpd_recipient_limit"]}\n";
	echo "Starting......: ".date("H:i:s")." *** MIME PROCESSING ***\n";
	echo "Starting......: ".date("H:i:s")." mime_nesting_limit=$mime_nesting_limit\n";
	echo "Starting......: ".date("H:i:s")." detect_8bit_encoding_header=$detect_8bit_encoding_header\n";
	echo "Starting......: ".date("H:i:s")." disable_mime_input_processing=$disable_mime_input_processing\n";
	echo "Starting......: ".date("H:i:s")." disable_mime_output_conversion=$disable_mime_output_conversion\n";
	
	
	
	echo "Starting......: ".date("H:i:s")." header_address_token_limit={$main->main_array["header_address_token_limit"]}\n";
	echo "Starting......: ".date("H:i:s")." minimal_backoff_time=$minimal_backoff_time\n";
	echo "Starting......: ".date("H:i:s")." maximal_backoff_time=$maximal_backoff_time\n";
	echo "Starting......: ".date("H:i:s")." maximal_queue_lifetime=$maximal_queue_lifetime\n";
	echo "Starting......: ".date("H:i:s")." bounce_queue_lifetime=$bounce_queue_lifetime\n";
	echo "Starting......: ".date("H:i:s")." ignore_mx_lookup_error=$ignore_mx_lookup_error\n";
	echo "Starting......: ".date("H:i:s")." disable_dns_lookups=$disable_dns_lookups\n";
	echo "Starting......: ".date("H:i:s")." smtpd_banner=$smtpd_banner\n";
	
	
	
	
	if($minimal_backoff_time==null){$minimal_backoff_time="300s";}
	if($maximal_backoff_time==null){$maximal_backoff_time="4000s";}
	if($bounce_queue_lifetime==null){$bounce_queue_lifetime="5d";}
	if($maximal_queue_lifetime==null){$maximal_queue_lifetime="5d";}

	$postfix_ver=$mainmulti->postfix_version();
	if(preg_match("#^([0-9]+)\.([0-9]+)#", $postfix_ver,$re)){$MAJOR=$re[1];$MINOR=$re[2];}
	if($MAJOR>1){
		if($MINOR>9){
			postconf("smtpd_relay_restrictions","permit_mynetworks, permit_sasl_authenticated, defer_unauth_destination");
		}
	}

	build_progress_mime_header("{configuring}",50);
	$address_verify_negative_cache=$mainmulti->YesNo($address_verify_negative_cache);
	echo "Starting......: ".date("H:i:s")." Apply all settings..\n";
	postconf("smtpd_reject_unlisted_sender","$smtpd_reject_unlisted_sender");
	postconf("smtpd_reject_unlisted_recipient","$smtpd_reject_unlisted_recipient");
	postconf("address_verify_map","$address_verify_map");
	postconf("address_verify_negative_cache","$address_verify_negative_cache");
	postconf("address_verify_poll_count","$address_verify_poll_count");
	postconf("address_verify_poll_delay","$address_verify_poll_delay");
	postconf("address_verify_sender","$address_verify_sender");
	postconf("address_verify_negative_expire_time","$address_verify_negative_expire_time");
	postconf("address_verify_negative_refresh_time","$address_verify_negative_refresh_time");
	postconf("address_verify_positive_expire_time","$address_verify_positive_expire_time");
	postconf("address_verify_positive_refresh_time","$address_verify_positive_refresh_time");	

	postconf("default_destination_recipient_limit","{$main->main_array["default_destination_recipient_limit"]}");
	postconf("smtpd_recipient_limit","{$main->main_array["smtpd_recipient_limit"]}");
	
	postconf("mime_nesting_limit","$mime_nesting_limit");
	postconf("detect_8bit_encoding_header","$detect_8bit_encoding_header");
	postconf("disable_mime_input_processing","$disable_mime_input_processing");
	postconf("disable_mime_output_conversion","$disable_mime_output_conversion");
		
	postconf("minimal_backoff_time","$minimal_backoff_time");
	postconf("maximal_backoff_time","$maximal_backoff_time");
	postconf("maximal_queue_lifetime","$maximal_queue_lifetime");
	postconf("bounce_queue_lifetime","$bounce_queue_lifetime");


	postconf("smtp_connection_reuse_time_limit","$smtp_connection_reuse_time_limit");
	postconf("connection_cache_ttl_limit","$connection_cache_ttl_limit");
	postconf("connection_cache_status_update_time","$connection_cache_status_update_time");	
	postconf("smtp_connection_cache_destinations","$smtp_connection_cache_destinationsF");
	postconf("smtpd_error_sleep_time",$smtpd_error_sleep_time);
	postconf("smtpd_soft_error_limit",$smtpd_soft_error_limit);
	postconf("smtpd_hard_error_limit",$smtpd_hard_error_limit);
	postconf("smtpd_client_connection_count_limit",$smtpd_client_connection_count_limit);
	postconf("smtpd_client_connection_rate_limit",$smtpd_client_connection_rate_limit);
	postconf("smtpd_client_message_rate_limit",$smtpd_client_message_rate_limit);
	postconf("smtpd_client_recipient_rate_limit",$smtpd_client_recipient_rate_limit);
	postconf("smtpd_client_new_tls_session_rate_limit",$smtpd_client_new_tls_session_rate_limit);
	postconf("initial_destination_concurrency",$initial_destination_concurrency);
	postconf("default_destination_concurrency_limit",$default_destination_concurrency_limit);
	postconf("smtp_destination_concurrency_limit",$smtp_destination_concurrency_limit);
	postconf("local_destination_concurrency_limit",$local_destination_concurrency_limit);
	postconf("default_destination_recipient_limit",$default_destination_recipient_limit);
	postconf("smtpd_recipient_limit",$smtpd_recipient_limit);
	postconf("default_process_limit",$default_process_limit);
	postconf("qmgr_message_recipient_limit",$qmgr_message_recipient_limit);
	postconf("smtpd_client_event_limit_exceptions",$smtpd_client_event_limit_exceptions);
	postconf("in_flow_delay",$in_flow_delay);
	postconf("smtp_connect_timeout",$smtp_connect_timeout);
    postconf("smtp_host_lookup","dns,native");
	postconf("smtp_helo_timeout",$smtp_helo_timeout);
	postconf("bounce_queue_lifetime",$bounce_queue_lifetime);
	postconf("maximal_queue_lifetime",$maximal_queue_lifetime);
	postconf("maximal_backoff_time",$maximal_backoff_time);
	postconf("minimal_backoff_time",$minimal_backoff_time);
	postconf("queue_run_delay",$queue_run_delay);	
	postconf("smtp_fallback_relay",$smtp_fallback_relay);
	postconf("ignore_mx_lookup_error",$ignore_mx_lookup_error);
	postconf("disable_dns_lookups",$disable_dns_lookups);
	postconf("smtpd_banner",$smtpd_banner);
	postconf("undisclosed_recipients_header","$undisclosed_recipients_header");
	postconf("enable_original_recipient","$enable_original_recipient");
	postconf("smtpd_discard_ehlo_keywords","$smtpd_discard_ehlo_keywords");
	

	build_progress_mime_header("{configuring} {done}",60);

	echo "Starting......: ".date("H:i:s")." Apply perso_settings\n";
	build_progress_mime_header("Perso settings...",70);
	perso_settings();
	build_progress_mime_header("Perso settings...",75);
}



function inet_interfaces(){
	$newarray=array();
	$unix=new unix();
	
	include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
	if($sock->GET_INFO("EnablePostfixMultiInstance")==1){
        return;
    }
	$EnableipV6=intval($sock->GET_INFO("EnableipV6"));
	
	
	
	$finale="all";
	$NewIP=array();
	$PostfixBinInterfaces=trim($sock->GET_INFO("PostfixBinInterfaces"));
	if($PostfixBinInterfaces<>null){
		$Interfaces=explode(",",$PostfixBinInterfaces);
		foreach ($Interfaces as $nic){
			$ipaddr=$unix->InterfaceToIPv4($nic);
			if($ipaddr<>null){$NewIP[]=$ipaddr;}
		}
		
		if(count($NewIP)>0){$finale=@implode(",", $NewIP);}
		
	}
	
	postconf("inet_interfaces",$finale);
	postconf("inet_protocols","ipv4");
	postconf("smtp_bind_address6","");
	$smtp_bind_address6=$sock->GET_INFO("smtp_bind_address6");
	
	
	if($EnableipV6==1){
		if(trim($smtp_bind_address6)<>null){
			echo "Starting......: ".date("H:i:s")." Postfix Listen ipv6 \"$smtp_bind_address6\"\n";
			postconf("inet_protocols","all");
			postconf("smtp_bind_address6",$smtp_bind_address6);
		}
	}
	
	
	
}

function MailBoxTransport(){
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();$sock=$GLOBALS["CLASS_SOCKETS"];}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
	if(!isset($GLOBALS["CLASS_USERS_MENUS"])){$users=new usersMenus();$GLOBALS["CLASS_USERS_MENUS"]=$users;}else{$users=$GLOBALS["CLASS_USERS_MENUS"];}
	
	echo "Starting......: ".date("H:i:s")." Postfix get mailbox transport\n";
	$mailbox_transport=trim($main->GET("mailbox_transport"));
	echo "Starting......: ".date("H:i:s")." Postfix get mailbox transport = \"$mailbox_transport\"\n";
	
	if($mailbox_transport<>null){
		postconf("mailbox_transport",$mailbox_transport);
		postconf("zarafa_destination_recipient_limit",1);
		return;	
	}
	
	

	$default=$main->getMailBoxTransport();
	
	if($default==null){
		postconf_X("mailbox_transport");
		postconf_X("virtual_transport");
		return;
	}
	
	postconf("zarafa_destination_recipient_limit",1);
	echo "Starting......: ".date("H:i:s")." Postfix mailbox_transport=`$default`\n";
	postconf("mailbox_transport",$default);
	postconf("virtual_transport","\$mailbox_transport");
	postconf("local_transport","local");
	postconf("lmtp_sasl_auth_enable","no");
	postconf("lmtp_sasl_password_maps","");
	postconf("lmtp_sasl_mechanism_filter","plain, login");
	postconf("lmtp_sasl_security_options",null);
	
	if(!$users->ZARAFA_INSTALLED){
		if(!$users->cyrus_imapd_installed){
			echo "Starting......: ".date("H:i:s")." Postfix None of Zarafa or cyrus imap installed on this server\n";
			return null;
		}
	}

	
	if(preg_match("#lmtp:(.+?):([0-9]+)#",$default,$re)){
		echo "Starting......: ".date("H:i:s")." Postfix \"LMTP\" is enabled ($default)\n";
		$ldap=new clladp();
		$CyrusLMTPListen=$re[1].":".$re[2];
		$cyruspass=$ldap->CyrusPassword();
		@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/lmtpauth","$CyrusLMTPListen\tcyrus:$cyruspass");
		shell_exec("{$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/lmtpauth");
		postconf("lmtp_sasl_auth_enable","yes");
		postconf("lmtp_sasl_password_maps","hash:{$GLOBALS["MAINCF_ROOT"]}/lmtpauth");
		postconf("lmtp_sasl_mechanism_filter","plain, login");
		postconf("lmtp_sasl_security_options","noanonymous");
		}
	}
	
	
	
function disable_lmtp_sasl(){
	echo "Starting......: ".date("H:i:s")." Postfix LMTP is disabled\n";
	postconf("lmtp_sasl_auth_enable","no");
	
			
}
	
function disable_smtp_sasl(){
	postconf("smtp_sasl_password_maps","");
	postconf("smtp_sasl_auth_enable","no");
	
}

function perso_settings(){
	$main=new main_perso();
	$main->replace_conf("{$GLOBALS["MAINCF_ROOT"]}/main.cf");
	if($GLOBALS["RELOAD"]){exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");}
	
}

function luser_relay(){}




function BuildAllWhitelistedServer(){
	$q=new mysql();
	$sql="SELECT * FROM postfix_whitelist_con";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";}
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){	
		$f[]="{$ligne["ipaddr"]}\tOK";
		$f[]="{$ligne["hostname"]}\tOK";
		
		
	}		
	
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/all_whitelisted_servers",@implode("\n",$f));
	shell_exec("{$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/all_whitelisted_servers");

}

function fix_postdrop_perms(){
	$unix=new unix();
	$postfix_bin=$unix->find_program("postfix");
	$chgrp_bin=$unix->find_program("chgrp");
	$killall_bin=$unix->find_program("killall");
	shell_exec("$postfix_bin stop 2>&1");
	shell_exec("$killall_bin -9 postdrop 2>&1");
	shell_exec("$chgrp_bin -R postdrop /var/spool/postfix/public 2>&1");
	shell_exec("$chgrp_bin -R postdrop /var/spool/postfix/maildrop/ 2>&1");
	shell_exec("$postfix_bin check 2>&1");
	shell_exec("$postfix_bin start 2>&1");
	
	
}

function postscreen(){
	$permit_mynetworks=null;
	$user=new usersMenus();
	if(!$user->POSTSCREEN_INSTALLED){echo "Starting......: ".date("H:i:s")." PostScreen is not installed, you should upgrade to 2.8 postfix version\n";return;}
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$EnablePostScreen=$main->GET("EnablePostScreen");
	$TrustMyNetwork=$main->GET_INFO("TrustMyNetwork");
	if(!is_numeric($TrustMyNetwork)){$TrustMyNetwork=1;}
	
	if($EnablePostScreen<>1){echo "Starting......: ".date("H:i:s")." PostScreen is not enabled\n";return;}
	echo "Starting......: ".date("H:i:s")." PostScreen configuring....\n";
	if(!is_file("{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.cidr")){@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.cidr","#");}
	if(!is_file("{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.hosts")){@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.hosts"," ");}
	if($TrustMyNetwork==1){$permit_mynetworks="permit_mynetworks,";}
	
	postconf("postscreen_access_list","{$permit_mynetworks}cidr:{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.cidr");
	
	
	$postscreen_bare_newline_action=$main->GET("postscreen_bare_newline_action");
	$postscreen_bare_newline_enable=$main->GET("postscreen_bare_newline_enable");
	
	$postscreen_bare_newline_ttl=$main->GET("postscreen_bare_newline_ttl");
	$postscreen_cache_cleanup_interval=$main->GET("postscreen_cache_cleanup_interval");
	$postscreen_cache_retention_time=$main->GET("postscreen_cache_retention_time");
	$postscreen_client_connection_count_limit=$main->GET("postscreen_client_connection_count_limit");
	$postscreen_pipelining_enable=$main->GET("postscreen_pipelining_enable");
	$postscreen_pipelining_action=$main->GET("postscreen_pipelining_action");
	$postscreen_pipelining_ttl=$main->GET("postscreen_pipelining_ttl");
	$postscreen_post_queue_limit=$main->GET("postscreen_post_queue_limit");
	$postscreen_pre_queue_limit=$main->GET("postscreen_pre_queue_limit");
	$postscreen_non_smtp_command_enable=$main->GET("postscreen_non_smtp_command_enable");
	$postscreen_non_smtp_command_action=$main->GET("postscreen_non_smtp_command_action");
	$postscreen_non_smtp_command_ttl=$main->GET("postscreen_non_smtp_command_ttl");
	$postscreen_forbidden_commands=$main->GET("postscreen_forbidden_command");
	$postscreen_dnsbl_action=$main->GET("postscreen_dnsbl_action");
	$postscreen_dnsbl_ttl=$main->GET("postscreen_dnsbl_ttl");
	$postscreen_dnsbl_threshold=$main->GET("postscreen_dnsbl_threshold");	
	
	
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
	
	
	postconf("postscreen_bare_newline_action",$postscreen_bare_newline_action);
	postconf("postscreen_bare_newline_enable",$postscreen_bare_newline_enable);
	postconf("postscreen_bare_newline_ttl",$postscreen_bare_newline_ttl);
	postconf("postscreen_cache_cleanup_interval",$postscreen_cache_cleanup_interval);
	postconf("postscreen_cache_retention_time",$postscreen_cache_retention_time);
	postconf("postscreen_client_connection_count_limit",$postscreen_client_connection_count_limit);
	postconf("postscreen_client_connection_count_limit",$postscreen_client_connection_count_limit);
	postconf("postscreen_pipelining_enable",$postscreen_pipelining_enable);
	postconf("postscreen_pipelining_action",$postscreen_pipelining_action);
	postconf("postscreen_pipelining_ttl",$postscreen_pipelining_ttl);
	postconf("postscreen_post_queue_limit",$postscreen_post_queue_limit);
	postconf("postscreen_pre_queue_limit",$postscreen_pre_queue_limit);
	postconf("postscreen_non_smtp_command_enable",$postscreen_non_smtp_command_enable);
	postconf("postscreen_non_smtp_command_action",$postscreen_non_smtp_command_action);
	postconf("postscreen_non_smtp_command_ttl",$postscreen_non_smtp_command_ttl);
	postconf("postscreen_forbidden_command",$postscreen_forbidden_commands);
	postconf("postscreen_dnsbl_action",$postscreen_dnsbl_action);
	postconf("postscreen_dnsbl_ttl",$postscreen_dnsbl_ttl);
	postconf("postscreen_dnsbl_threshold",$postscreen_dnsbl_threshold);
	postconf("postscreen_cache_map","btree:/var/lib/postfix/postscreen_master_cache");
	
	
	
	
	$dnsbl_array=unserializeb64($main->GET_BIGDATA("postscreen_dnsbl_sites"));
	if(is_array($dnsbl_array)){
        foreach ($dnsbl_array as $site=>$threshold){
            if($site==null){continue;}
            $dnsbl_array_compiled[]="$site*$threshold";
        }
	}
		
	$final_dnsbl=null;
	if(is_array($dnsbl_array_compiled)){$final_dnsbl=@implode(",",$dnsbl_array_compiled);}
	postconf("postscreen_dnsbl_sites",$final_dnsbl);

	
	$q=new mysql();
	$sql="SELECT * FROM postfix_whitelist_con";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";}
	$nets=array();
	$hostsname=array();
	$ldap=new clladp();
	$ipClass=new IP();	
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){	
		
		$ligne["ipaddr"]=trim($ligne["ipaddr"]);
		$ligne["hostname"]=trim($ligne["hostname"]);
			
		if($ligne["hostname"]==null){continue;}
		if($ligne["ipaddr"]==null){continue;}
			
		if(!$ipClass->isIPAddress($ligne["hostname"])){
			$hostsname[]="{$ligne["hostname"]}\tOK";
		}else{
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $ligne["hostname"])){
				$nets[]="{$ligne["hostname"]}\tdunno";
			}
		}
		
		if(!$ipClass->isIPAddress($ligne["ipaddr"])){
			$hostsname[]="{$ligne["ipaddr"]}\tOK";
		}else{
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $ligne["ipaddr"])){
				$nets[]="{$ligne["ipaddr"]}\tdunno";
			}
		}		
		
	}		
	

		
		
	

	$networks=$ldap->load_mynetworks();	
	if(is_array($networks)){
        foreach ($networks as $num=>$ligne){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			if(!$ipClass->isIPAddress($ligne)){
				$hostsname[]="$ligne\tOK";
			}else{
				if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $ligne)){
					$nets[]="$ligne\tdunno";
				}
			}
		}
	}
	

	
	@unlink("{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.hosts");
	@unlink("{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.cidr");
	
	if(count($hostsname)>0){
		@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.hosts",@implode("\n",$hostsname));
		$postscreen_access=",hash:{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.hosts";
	}
	if(!is_file("{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.hosts")){@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.hosts", "\n");}
	
	shell_exec("{$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.hosts >/dev/null 2>&1");
	
	if(count($nets)>0){@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.cidr",@implode("\n",$nets));}
	postconf("postscreen_access_list","permit_mynetworks,cidr:{$GLOBALS["MAINCF_ROOT"]}/postscreen_access.cidr$postscreen_access");
	
	MasterCFBuilder();
	}
	
function MasterCF_DOMAINS_THROTTLE_SMTP_CONNECTION_CACHE_DESTINATIONS($uuid){	
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$array=unserializeb64($main->GET_BIGDATA("domain_throttle_daemons_list"));
	$caches=$array[$uuid]["smtp-instance-cache-destinations"];
	if(count($caches)==0){return null;}
    foreach ($caches as $domain=>$none){
        if(trim($domain)<>null){
            $f[]="$domain\tOK";
        }
    }
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/{$uuid}_CONNECTION_CACHE_DESTINATIONS", implode("\n", $f));
	shell_exec("{$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/{$uuid}_CONNECTION_CACHE_DESTINATIONS >/dev/null 2>&1");
	return "smtp_connection_cache_destinations=hash:{$GLOBALS["MAINCF_ROOT"]}/{$uuid}_CONNECTION_CACHE_DESTINATIONS";
}
	
function MasterCF_DOMAINS_THROTTLE(){
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$array=unserializeb64($main->GET_BIGDATA("domain_throttle_daemons_list"));
	
	$f=explode("\n",@file_get_contents("{$GLOBALS["MAINCF_ROOT"]}/main.cf"));
	if(!is_array($f)){$f=array();}
	foreach ( $f as $index=>$line ){
		if(preg_match("#^[0-9]+_destination#",$line)){continue;}
		if(preg_match("#^[0-9]+_delivery_#",$line)){continue;}
		if(preg_match("#^[0-9]+_initial_#",$line)){continue;}
		$new[]=$line;
	}
	if($GLOBALS["VERBOSE"]){echo "MasterCF_DOMAINS_THROTTLE():: Cleaning main.cf done..\n";}
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/main.cf",@implode("\n",$new));
	unset($new);
	
	
	if(!is_array($array)){
		if($GLOBALS["VERBOSE"]){echo "MasterCF_DOMAINS_THROTTLE():: Not An Array line ". __LINE__."\n";}
		return null;
	}

    foreach ($array as $uuid=>$conf){
		if($conf["ENABLED"]<>1){continue;}
		if(count($conf["DOMAINS"])==0){continue;}
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
		if(!is_numeric($conf["default_process_limit"])){$conf["default_process_limit"]=100;}
		$moinso["{$uuid}_destination_concurrency_failed_cohort_limit"]="{$conf["transport_destination_concurrency_failed_cohort_limit"]}";
		$moinso["{$uuid}_delivery_slot_loan"]="{$conf["transport_delivery_slot_loan"]}";
		$moinso["{$uuid}_delivery_slot_discount"]="{$conf["transport_delivery_slot_discount"]}";
		$moinso["{$uuid}_delivery_slot_cost"]="{$conf["transport_delivery_slot_cost"]}";
		$moinso["{$uuid}_initial_destination_concurrency"]="{$conf["transport_initial_destination_concurrency"]}";
		$moinso["{$uuid}_destination_recipient_limit"]="{$conf["transport_destination_recipient_limit"]}";
		$moinso["{$uuid}_destination_concurrency_limit"]="{$conf["transport_destination_concurrency_limit"]}";
		$moinso["{$uuid}_destination_rate_delay"]="{$conf["transport_destination_rate_delay"]}";
		
		
		$moinsoMasterText=null;
		if(is_numeric($conf["smtp_connection_cache_on_demand"])){
			if($conf["smtp_connection_cache_on_demand"]==0){
				$moinsoMaster[]="smtp_connection_cache_on_demand=no";
			}else{
				$moinsoMaster[]="smtp_connection_cache_on_demand=yes";
				$moinsoMaster[]="smtp_connection_cache_time_limit={$conf["smtp_connection_cache_time_limit"]}";
				$moinsoMaster[]="smtp_connection_reuse_time_limit={$conf["smtp_connection_reuse_time_limit"]}";
				$cache_destinations=MasterCF_DOMAINS_THROTTLE_SMTP_CONNECTION_CACHE_DESTINATIONS($uuid);
				if($cache_destinations<>null){$moinsoMaster[]=$cache_destinations;}
			}
			
		}else{
			if($GLOBALS["VERBOSE"]){echo "DOMAINS_THROTTLE:: smtp_connection_cache_on_demand \"{$conf["smtp_connection_cache_on_demand"]}\" is not a numeric\n";}
		}
		
		if($GLOBALS["VERBOSE"]){echo "DOMAINS_THROTTLE:: smtp_connection_cache_on_demand \"". count($moinsoMaster)." value(s)\n";}
		if(count($moinsoMaster)>0){$moinsoMasterText=" -o ".@implode(" -o ", $moinsoMaster);}		
		
		
		$instances[]="\n# THROTTLE {$conf["INSTANCE_NAME"]}\n$uuid\tunix\t-\t-\tn\t-\t{$conf["default_process_limit"]}\tsmtp$moinsoMasterText";

        foreach ($conf["DOMAINS"] as $domain=>$null){
            $maps[$domain]="$uuid:";
        }

        foreach ($maps as $a=>$b){
            $maps_final[]="$a\t$b";
        }
	}
	
	if($GLOBALS["VERBOSE"]){echo "MasterCF_DOMAINS_THROTTLE():: ". count($moinso)." main.cf command lines\n";}
	if(is_array($moinso)){
        foreach ($moinso as $key=>$val){
			postconf($key,$val);
		}
	}
	
	if(!is_array($instances)){return null;}

	return @implode("\n",$instances)."\n";
	
	
}

function debug_peer_list(){
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$datas=unserializeb64($main->GET_BIGDATA("debug_peer_list"));
	
	if(count($datas)==0){
		postconf("debug_peer_level",2);
		postconf("debug_peer_list",null);
		return;
	}

    foreach ($datas as $index=>$file){
			if(trim($index)==null){continue;}
			$f[]=$index;
		}
		
		if(count($f)>0){
			postconf("debug_peer_level",3);
			postconf("debug_peer_list",@implode(",", $f));
			
		}	
	
	
}

function haproxy_compliance():bool{
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);

    $PostfixEnableProxyProtocol=intval($main->GET_INFO("PostfixEnableProxyProtocol"));
	if($PostfixEnableProxyProtocol==0){
		echo "Starting......: ".date("H:i:s")." HaProxy compliance: disabled\n";
		//postconf("postscreen_upstream_proxy_protocol",null);
		postconf("smtpd_upstream_proxy_protocol",null);
		return true;
	}
    postconf("smtpd_upstream_proxy_protocol","haproxy");
    return true;

}


function ScanLibexec(){
	if(!is_dir("/usr/lib/postfix")){return;}
	if(!is_dir("/usr/libexec/postfix")){return;}
	$unix=new unix();
	$ln=$unix->find_program("ln");
	
	$files=$unix->DirFiles("/usr/libexec/postfix");
    foreach ($files as $filename=>$MFARRY){
		if(!is_link("/usr/lib/postfix/$filename")){
			if(!is_link("/usr/libexec/postfix/$filename")){
				@unlink("/usr/lib/postfix/$filename");
				echo "Starting......: ".date("H:i:s")." linking $filename\n";
				shell_exec("$ln -sf /usr/libexec/postfix/$filename /usr/lib/postfix/$filename");
			}
		}
		
	}
}

function build_progress_sender_routing($text,$pourc){
	if(!$GLOBALS["PROGRESS_SENDER_DEPENDENT"]){return;}
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/build_progress_sender_routing";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}




function MasterCFBuilder($restart_service=false){
	$smtp_ssl=null;
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$PostfixEnableMasterCfSSL=$main->GET("PostfixEnableMasterCfSSL");
	$PostfixEnableSubmission=intval($main->GET("PostfixEnableSubmission"));
    $PostFixSmtpSaslEnable=intval($main->GET("PostFixSmtpSaslEnable"));
    $TrustMyNetwork=$main->GET_INFO("TrustMyNetwork");
    $EnablePostScreen=$main->GET("EnablePostScreen");

	$PostfixBindInterfacePort=intval($main->GET_INFO("PostfixBindInterfacePort"));
	if(!is_numeric($TrustMyNetwork)){$TrustMyNetwork=1;}
	
	$user=new usersMenus();


	$postscreen_line=null;
	$tlsproxy=null;
	$dnsblog=null;
	$re_cleanup_infos=null;
	$smtp_submission=null;
	$pre_cleanup_addons=null;
    $users=new usersMenus();
	echo "Starting......: ".date("H:i:s")." Postfix master version: $users->POSTFIX_VERSION\n";

	$MASTER_CF_DEFINED=array();
	
	if($EnablePostScreen==null){$EnablePostScreen=0;}	
	if(!$user->POSTSCREEN_INSTALLED){$EnablePostScreen=0;}
	if($EnablePostScreen==1){$PostfixEnableSubmission=1;}
    if($PostfixBindInterfacePort==0){	$PostfixBindInterfacePort=25;}

	
	
	$ADD_PRECLEANUP=false;
	$TLSSET=false;
    postconf_X("content_filter");
    build_progress_sender_routing("{building} Master.cf",35);

	if($ADD_PRECLEANUP){
		echo "Starting......: ".date("H:i:s")." Enable pre-cleanup service...\n";
		$pre_cleanup_addons=" -o smtp_generic_maps= -o canonical_maps= -o sender_canonical_maps= -o recipient_canonical_maps= -o masquerade_domains= -o recipient_bcc_maps= -o sender_bcc_maps=";
		$re_cleanup_infos  =" -o cleanup_service_name=pre-cleanup";
	}	
	$permit_mynetworks=null;
	
	if($PostfixEnableMasterCfSSL==1){
		if($TrustMyNetwork==1){$permit_mynetworks="permit_mynetworks,";}
		echo "Starting......: ".date("H:i:s")." Enabling SSL (465 port)\n";
		SetTLS();
		$TLSSET=true;
		if(isset($MASTER_CF_DEFINED["smtps"])){unset($MASTER_CF_DEFINED["smtps"]);}
		$SSL_INSTANCE[]="smtps\tinet\tn\t-\tn\t-\t-\tsmtpd";
		if($re_cleanup_infos<>null){$SSL_INSTANCE[]=$re_cleanup_infos;}
		$SSL_INSTANCE[]=" -o smtpd_tls_wrappermode=yes";
		$SSL_INSTANCE[]=" -o smtpd_delay_reject=yes";
		//$SSL_INSTANCE[]=" -o smtpd_client_restrictions={$permit_mynetworks}permit_sasl_authenticated,reject\n";
		//$SSL_INSTANCE[]=" -o smtpd_sender_restrictions=permit_sasl_authenticated,reject";
		//$SSL_INSTANCE[]=" -o smtpd_helo_restrictions=permit_sasl_authenticated,reject";
		//$SSL_INSTANCE[]=" -o smtpd_recipient_restrictions=permit_sasl_authenticated,reject";		
		$smtp_ssl=@implode("\n",$SSL_INSTANCE);
	}else{
		echo "Starting......: ".date("H:i:s")." SSL (465 port) Disabled\n";
	}

	if($PostfixEnableSubmission==1){
        $PostfixEnforceSubmission=intval($main->GET("PostfixEnforceSubmission"));
		echo "Starting......: ".date("H:i:s")." Enabling submission (587 port)\n";
		if(isset($MASTER_CF_DEFINED["submission"])){unset($MASTER_CF_DEFINED["submission"]);}
		if(!$TLSSET){SetTLS();}
		$TLSSET=true;
		$SUBMISSION_INSTANCE[]="submission\tinet\tn\t-\tn\t-\t-\tsmtpd";
        if($PostfixEnforceSubmission==1){
            $SUBMISSION_INSTANCE[] = " -o smtpd_tls_security_level=encrypt";
        }
		if($re_cleanup_infos<>null){$SUBMISSION_INSTANCE[]=$re_cleanup_infos;}
		$SUBMISSION_INSTANCE[]=" -o smtpd_etrn_restrictions=reject";
		if($PostFixSmtpSaslEnable==1) {
            $SUBMISSION_INSTANCE[] = " -o smtpd_enforce_tls=yes";
            $SUBMISSION_INSTANCE[] = " -o smtpd_sasl_auth_enable=yes";
            $SUBMISSION_INSTANCE[] = " -o smtpd_delay_reject=yes";
            $SUBMISSION_INSTANCE[] = " -o smtpd_client_restrictions=permit_sasl_authenticated,reject";
            $SUBMISSION_INSTANCE[] = " -o smtpd_sender_restrictions=permit_sasl_authenticated,reject";
            $SUBMISSION_INSTANCE[] = " -o smtpd_helo_restrictions=permit_sasl_authenticated,reject";
            $SUBMISSION_INSTANCE[] = " -o smtpd_recipient_restrictions=permit_sasl_authenticated,reject";
            $SUBMISSION_INSTANCE[] = " -o smtp_generic_maps=";
            $SUBMISSION_INSTANCE[] = " -o sender_canonical_maps=";
        }
		$smtp_submission=@implode("\n",$SUBMISSION_INSTANCE);
		
	}else{
		echo "Starting......: ".date("H:i:s")." submission (587 port) Disabled\n";
	}
	
	if($PostfixBindInterfacePort==25){
		$postfix_listen_port="smtp";
		$postscreen_listen_port="smtp";		
	}else{
		$postfix_listen_port=$PostfixBindInterfacePort;
		$postscreen_listen_port=$PostfixBindInterfacePort;		
	}
	
	
	echo "Starting......: ".date("H:i:s")." Postfix intended to listen SMTP Port $postfix_listen_port\n";
	$smtp_in_proto="inet";
	$smtp_private="n";

	
	
	if($EnablePostScreen==1){
		if(isset($MASTER_CF_DEFINED["tlsproxy"])){unset($MASTER_CF_DEFINED["tlsproxy"]);}
		if(isset($MASTER_CF_DEFINED["dnsblog"])){unset($MASTER_CF_DEFINED["dnsblog"]);}
		echo "Starting......: ".date("H:i:s")." PostScreen is enabled, users should use 587 port to send mails internally\n"; 
		$smtp_in_proto="pass";
		$smtp_private="-";
		if($postfix_listen_port=="smtp"){$postfix_listen_port="smtpd";}
		$postscreen_line="$postscreen_listen_port\tinet\tn\t-\tn\t-\t1\tpostscreen -o soft_bounce=yes";
		$tlsproxy="tlsproxy\tunix\t-\t-\tn\t-\t0\ttlsproxy";
		$dnsblog="dnsblog\tunix\t-\t-\tn\t-\t0\tdnsblog";
		}else{
			echo "Starting......: ".date("H:i:s")." PostScreen is disabled\n";
		}
	
if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." run MasterCF_DOMAINS_THROTTLE()\n";}	
build_progress_sender_routing("{building} DOMAINS_THROTTLE",45);
$smtp_throttle=MasterCF_DOMAINS_THROTTLE();

// http://www.ijs.si/software/amavisd/README.postfix.html	
$conf[]="#";
$conf[]="# Postfix master process configuration file.  For details on the format";
$conf[]="# of the file, see the master(5) manual page (command: \"man 5 master\").";
$conf[]="#";
$conf[]="# ==========================================================================";
$conf[]="# service type  private unpriv  chroot  wakeup  maxproc command + args";
$conf[]="#               (yes)   (yes)   (yes)   (never) (100)";
$conf[]="# ==========================================================================";
if(isset($MASTER_CF_DEFINED[$postfix_listen_port])){unset($MASTER_CF_DEFINED[$postfix_listen_port]);}
if($postscreen_line<>null){$conf[]=$postscreen_line;}
if($tlsproxy<>null){$conf[]=$tlsproxy;}
if($dnsblog<>null){$conf[]=$dnsblog;}
$conf[]="$postfix_listen_port\t$smtp_in_proto\t$smtp_private\t-\tn\t-\t-\tsmtpd$re_cleanup_infos";
if($smtp_ssl<>null){$conf[]=$smtp_ssl;}
if($smtp_submission<>null){$conf[]=$smtp_submission;}
if($smtp_throttle<>null){$conf[]=$smtp_throttle;}
if(isset($MASTER_CF_DEFINED["pickup"])){unset($MASTER_CF_DEFINED["pickup"]);}
if(isset($MASTER_CF_DEFINED["cleanup"])){unset($MASTER_CF_DEFINED["cleanup"]);}
if(isset($MASTER_CF_DEFINED["mailman"])){unset($MASTER_CF_DEFINED["mailman"]);}
if(count($MASTER_CF_DEFINED)==0){
	$conf[]="pickup\tfifo\tn\t-\tn\t60\t1\tpickup$re_cleanup_infos";
	$conf[]="cleanup\tunix\tn\t-\tn\t-\t0\tcleanup";
	$conf[]="pre-cleanup\tunix\tn\t-\tn\t-\t0\tcleanup$pre_cleanup_addons";
	$conf[]="qmgr\tfifo\tn\t-\tn\t300\t1\tqmgr";
	$conf[]="tlsmgr\tunix\t-\t-\tn\t1000?\t1\ttlsmgr";
	$conf[]="rewrite\tunix\t-\t-\tn\t-\t-\ttrivial-rewrite";
	$conf[]="bounce\tunix\t-\t-\tn\t-\t0\tbounce";
	$conf[]="defer\tunix\t-\t-\tn\t-\t0\tbounce";
	$conf[]="trace\tunix\t-\t-\tn\t-\t0\tbounce";
	$conf[]="verify\tunix\t-\t-\tn\t-\t1\tverify";
	$conf[]="flush\tunix\tn\t-\tn\t1000?\t0\tflush";
	$conf[]="proxymap\tunix\t-\t-\tn\t-\t-\tproxymap";
	$conf[]="proxywrite\tunix\t-\t-\tn\t-\t1\tproxymap";
	$conf[]="smtp\tunix\t-\t-\tn\t-\t-\tsmtp";
	
	$conf[]="relay\tunix\t-\t-\tn\t-\t-\tsmtp -o fallback_relay=";
	$conf[]="showq\tunix\tn\t-\tn\t-\t-\tshowq";
	$conf[]="error\tunix\t-\t-\tn\t-\t-\terror";
	$conf[]="discard\tunix\t-\t-\tn\t-\t-\tdiscard";
	$conf[]="local\tunix\t-\tn\tn\t-\t-\tlocal";
	$conf[]="virtual\tunix\t-\tn\tn\t-\t-\tvirtual";
	$conf[]="lmtp\tunix\t-\t-\tn\t-\t-\tlmtp";
	$conf[]="anvil\tunix\t-\t-\tn\t-\t1\tanvil";
	$conf[]="scache\tunix\t-\t-\tn\t-\t1\tscache";
	$conf[]="scan\tunix\t-\t-\tn\t\t-\t10\tsm -v";
	$conf[]="maildrop\tunix\t-\tn\tn\t-\t-\tpipe ";
	$conf[]="retry\tunix\t-\t-\tn\t-\t-\terror ";
	$conf[]="uucp\tunix\t-\tn\tn\t-\t-\tpipe flags=Fqhu user=uucp argv=uux -r -n -z -a\$sender - \$nexthop!rmail (\$recipient)";
	$conf[]="ifmail\tunix\t-\tn\tn\t-\t-\tpipe flags=F user=ftn argv=/usr/lib/ifmail/ifmail -r \$nexthop (\$recipient)";
	$conf[]="bsmtp\tunix\t-\tn\tn\t-\t-\tpipe flags=Fq. user=bsmtp argv=/usr/lib/bsmtp/bsmtp -t\$nexthop -f\$sender \$recipient";
}else{
	if(!isset($MASTER_CF_DEFINED["pickup"])){ $conf[]="pickup\tfifo\tn\t-\tn\t60\t1\tpickup$re_cleanup_infos"; }
	if(!isset($MASTER_CF_DEFINED["cleanup"])){ $conf[]="cleanup\tunix\tn\t-\tn\t-\t0\tcleanup"; }
	if(!isset($MASTER_CF_DEFINED["pre-cleanup"])){ $conf[]="pre-cleanup\tunix\tn\t-\tn\t-\t0\tcleanup$pre_cleanup_addons"; }
	if(!isset($MASTER_CF_DEFINED["qmgr"])){ $conf[]="qmgr\tfifo\tn\t-\tn\t300\t1\tqmgr"; }
	if(!isset($MASTER_CF_DEFINED["rewrite"])){ $conf[]="rewrite\tunix\t-\t-\tn\t-\t-\ttrivial-rewrite"; }
	if(!isset($MASTER_CF_DEFINED["bounce"])){ $conf[]="bounce\tunix\t-\t-\tn\t-\t0\tbounce"; }
	if(!isset($MASTER_CF_DEFINED["defer"])){ $conf[]="defer\tunix\t-\t-\tn\t-\t0\tbounce"; }
	if(!isset($MASTER_CF_DEFINED["trace"])){ $conf[]="trace\tunix\t-\t-\tn\t-\t0\tbounce"; }
	if(!isset($MASTER_CF_DEFINED["verify"])){ $conf[]="verify\tunix\t-\t-\tn\t-\t1\tverify";}
	if(!isset($MASTER_CF_DEFINED["flush"])){ $conf[]="flush\tunix\tn\t-\tn\t1000?\t0\tflush"; } 
	if(!isset($MASTER_CF_DEFINED["proxymap"])){ $conf[]="proxymap\tunix\t-\t-\tn\t-\t-\tproxymap"; }
	if(!isset($MASTER_CF_DEFINED["proxywrite"])){ $conf[]="proxywrite\tunix\t-\t-\tn\t-\t1\tproxymap";}
	if(!isset($MASTER_CF_DEFINED["smtp"])){ $conf[]="smtp\tunix\t-\t-\tn\t-\t-\tsmtp"; }
	
	if(!isset($MASTER_CF_DEFINED["relay"])){$conf[]="relay\tunix\t-\t-\tn\t-\t-\tsmtp -o fallback_relay=";;}
	if(!isset($MASTER_CF_DEFINED["showq"])){$conf[]="showq\tunix\tn\t-\tn\t-\t-\tshowq";;}
	if(!isset($MASTER_CF_DEFINED["error"])){$conf[]="error\tunix\t-\t-\tn\t-\t-\terror";;}
	if(!isset($MASTER_CF_DEFINED["discard"])){$conf[]="discard\tunix\t-\t-\tn\t-\t-\tdiscard";;}
	if(!isset($MASTER_CF_DEFINED["local"])){$conf[]="local\tunix\t-\tn\tn\t-\t-\tlocal";;}
	if(!isset($MASTER_CF_DEFINED["virtual"])){$conf[]="virtual\tunix\t-\tn\tn\t-\t-\tvirtual";;}
	if(!isset($MASTER_CF_DEFINED["lmtp"])){$conf[]="lmtp\tunix\t-\t-\tn\t-\t-\tlmtp";;}
	if(!isset($MASTER_CF_DEFINED["anvil"])){$conf[]="anvil\tunix\t-\t-\tn\t-\t1\tanvil";;}
	if(!isset($MASTER_CF_DEFINED["scache"])){$conf[]="scache\tunix\t-\t-\tn\t-\t1\tscache";;}
	if(!isset($MASTER_CF_DEFINED["scan"])){$conf[]="scan\tunix\t-\t-\tn\t\t-\t10\tsm -v";;}
	if(!isset($MASTER_CF_DEFINED["maildrop"])){$conf[]="maildrop\tunix\t-\tn\tn\t-\t-\tpipe ";;}
	if(!isset($MASTER_CF_DEFINED["retry"])){$conf[]="retry\tunix\t-\t-\tn\t-\t-\terror ";;}
	if(!isset($MASTER_CF_DEFINED["uucp"])){$conf[]="uucp\tunix\t-\tn\tn\t-\t-\tpipe flags=Fqhu user=uucp argv=uux -r -n -z -a\$sender - \$nexthop!rmail (\$recipient)";;}
	if(!isset($MASTER_CF_DEFINED["ifmail"])){$conf[]="ifmail\tunix\t-\tn\tn\t-\t-\tpipe flags=F user=ftn argv=/usr/lib/ifmail/ifmail -r \$nexthop (\$recipient)";;}
	if(!isset($MASTER_CF_DEFINED["bsmtp"])){$conf[]="bsmtp\tunix\t-\tn\tn\t-\t-\tpipe flags=Fq. user=bsmtp argv=/usr/lib/bsmtp/bsmtp -t\$nexthop -f\$sender \$recipient";;}
}

foreach ($MASTER_CF_DEFINED as $service=>$MFARRY){
	$MFARRY["MAXPROC"]=intval($MFARRY["MAXPROC"]);
	$conf[]="$service\t{$MFARRY["TYPE"]}\t{$MFARRY["PRIVATE"]}\t{$MFARRY["UNIPRIV"]}\t{$MFARRY["CHROOT"]}\t{$MFARRY["WAKEUP"]}\t{$MFARRY["MAXPROC"]}\t{$MFARRY["COMMAND"]}";
	echo "Starting......: ".date("H:i:s")." master.cf adding $service ({$MFARRY["TYPE"]})\n";
	
}

$conf[]="mailman\tunix\t-\tn\tn\t-\t-\tpipe flags=FR user=mail:mail argv=/etc/mailman/postfix-to-mailman.py \${nexthop} \${mailbox}";
$conf[]="artica-whitelist\tunix\t-\tn\tn\t-\t-\tpipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --white";
$conf[]="artica-blacklist\tunix\t-\tn\tn\t-\t-\tpipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --black";
$conf[]="artica-reportwbl\tunix\t-\tn\tn\t-\t-\tpipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --report";
$conf[]="artica-reportquar\tunix\t-\tn\tn\t-\t-\tpipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --quarantines";
$conf[]="artica-spam\tunix\t-\tn\tn\t-\t-\tpipe flags=F  user=mail argv=/usr/share/artica-postfix/bin/artica-whitelist -a \${nexthop} -s \${sender} --spam";
$conf[]="zarafa\tunix\t-\tn\tn\t-\t-\tpipe	user=mail argv=/usr/bin/zarafa-dagent \${user}";


$unix=new unix();
$cyrdeliver=$unix->find_program("cyrdeliver");
if(is_file($cyrdeliver)){
	echo "Starting......: ".date("H:i:s")." master.cf adding cyrus\n";
	$conf[]="cyrus\tunix\t-\tn\tn\t-\t-\tpipe\tflags=R user=cyrus argv=/usr/sbin/cyrdeliver -e -m \${extension} \${user}";	
}else{
	$conf[]="# cyrdeliver no such binary."; 
}

$conf[]="";
$conf[]="";


$conf[]="";	
$conf[]="";
build_progress_sender_routing("{building} master.cf {done}",55);
@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/master.cf",@implode("\n",$conf));
echo "Starting......: ".date("H:i:s")." master.cf done\n";

if($GLOBALS["RELOAD"]){
    $unix->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
}

if($restart_service){
	build_progress_sender_routing("{restarting_service}",60);
	shell_exec("{$GLOBALS["postfix"]} stop");
	shell_exec("{$GLOBALS["postfix"]} start");
}



}
function build_progress_postfix_templates($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/build_progress_postfix_templates";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function postfix_templates(){

	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$mainTemplates=new bounces_templates();
	$conf=null;
	
	
	build_progress_postfix_templates("{building}",10);
	
	$double_bounce_sender=$main->GET("double_bounce_sender");
	$address_verify_sender=$main->GET("address_verify_sender");
	$twobounce_notice_recipient=$main->GET("2bounce_notice_recipient");
	$error_notice_recipient=$main->GET("error_notice_recipient");
	$delay_notice_recipient=$main->GET("delay_notice_recipient");
	$empty_address_recipient=$main->GET("empty_address_recipient");
    $PostfixPostmaster=$main->GET("PostfixPostmaster");
	if(trim($PostfixPostmaster)==null){$PostfixPostmaster="postmaster";}
	
	if($double_bounce_sender==null){$double_bounce_sender="double-bounce";};
	if($address_verify_sender==null){$address_verify_sender="\$double_bounce_sender";}
	if($twobounce_notice_recipient==null){$twobounce_notice_recipient="postmaster";}
	if($error_notice_recipient==null){$error_notice_recipient=$PostfixPostmaster;}
	if($delay_notice_recipient==null){$delay_notice_recipient=$PostfixPostmaster;}
	if($empty_address_recipient==null){$empty_address_recipient=$PostfixPostmaster;}	
	if(is_array($mainTemplates->templates_array)){
        foreach ($mainTemplates->templates_array as $template=>$nothing){
			build_progress_postfix_templates("{{$template}}",50);
			
			$array=unserializeb64($main->GET_BIGDATA($template));
			if(!is_array($array)){$array=$mainTemplates->templates_array[$template];}
				$tp=explode("\n",$array["Body"]);
				$Body=null;
				foreach ($tp as $line){
                    if(trim($line)==null){continue;}
                    $Body=$Body.$line."\n";
                }
				$conf=$conf ."\n$template = <<EOF\n";
				$conf=$conf ."Charset: {$array["Charset"]}\n";
				$conf=$conf ."From:  {$array["From"]}\n";
				$conf=$conf ."Subject: {$array["Subject"]}\n";
				$conf=$conf ."\n";
				$conf=$conf ."$Body";
				$conf=$conf ."\n\n";
				$conf=$conf ."EOF\n";
				
			}
	}


	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/bounce.template.cf",$conf);
    $not=array();
	$notify_class=unserializeb64($main->GET_BIGDATA("notify_class"));
	if($notify_class["notify_class_software"]==1){$not[]="software";}
	if($notify_class["notify_class_resource"]==1){$not[]="resource";}
	if($notify_class["notify_class_policy"]==1){$not[]="policy";}
	if($notify_class["notify_class_delay"]==1){$not[]="delay";}
	if($notify_class["notify_class_2bounce"]==1){$not[]="2bounce";}
	if($notify_class["notify_class_bounce"]==1){$not[]="bounce";}
	if($notify_class["notify_class_protocol"]==1){$not[]="protocol";}
	
	
	build_progress_postfix_templates("{apply_config}",90);
	
	postconf("notify_class",@implode(",",$not));
	postconf("double_bounce_sender","$double_bounce_sender");
	postconf("address_verify_sender","$address_verify_sender");	
	postconf("2bounce_notice_recipient",$twobounce_notice_recipient);	
	postconf("error_notice_recipient",$error_notice_recipient);	
	postconf("delay_notice_recipient",$delay_notice_recipient);
	postconf("empty_address_recipient",$empty_address_recipient);
	postconf("bounce_template_file","{$GLOBALS["MAINCF_ROOT"]}/bounce.template.cf");
    postconf("bounce_notice_recipient","$PostfixPostmaster");
	build_progress_postfix_templates("{done}",100);
	
	}


function memory():bool{
	$unix=new unix();
    $cmd_verbose=null;
	if($GLOBALS["VERBOSE"]){$cmd_verbose=" --verbose";}
    $main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$PostFixEnableQueueInMemory=intval($main->GET_INFO("PostFixEnableQueueInMemory"));
	$PostFixQueueInMemory=intval($main->GET_INFO("PostFixQueueInMemory"));
	$directory="/var/spool/postfix";
    if($GLOBALS["POSTFIX_INSTANCE_ID"]>0) {
        $directory = "/var/spool/postfix-instance{$GLOBALS["POSTFIX_INSTANCE_ID"]}";
    }

	if($PostFixEnableQueueInMemory==1){
		echo "Starting......: ".date("H:i:s")." Postfix Queue in memory is enabled for {$PostFixQueueInMemory}M\n";
		echo "Starting......: ".date("H:i:s")." Postfix executing exec.postfix-multi.php\n";
		shell_exec(LOCATE_PHP5_BIN()."/usr/share/artica-postfix/exec.postfix-multi.php --instance-memory {$GLOBALS["POSTFIX_INSTANCE_ID"]} $PostFixQueueInMemory$cmd_verbose");
		return true;
	}


	$MOUNTED_TMPFS_MEM=$unix->MOUNTED_TMPFS_MEM($directory);
    if($MOUNTED_TMPFS_MEM>0){
			shell_exec(LOCATE_PHP5_BIN()."/usr/share/artica-postfix/exec.postfix-multi.php --instance-memory-kill {$GLOBALS["POSTFIX_INSTANCE_ID"]}$cmd_verbose");
			return true;
    }
	echo "Starting......: ".date("H:i:s")." Postfix Queue in memory is not enabled\n";
	return true;
	
}

function repair_locks(){
	$Myfile=basename(__FILE__);
	$timeFile="/etc/artica-postfix/pids/$Myfile.".__FUNCTION__.".time";
	$pidFile="/etc/artica-postfix/pids/$Myfile.".__FUNCTION__.".pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidFile);
	
	if($unix->process_exists($pid,$Myfile)){writelogs("Die, already process $pid running ",__FUNCTION__,__FILE__,__LINE__);return;}
	
	$time=$unix->file_time_min($timeFile);
	if($time<5){writelogs("Die, No more than 5mn ",__FUNCTION__,__FILE__,__LINE__);return;}
	@unlink($timeFile);
	@mkdir(dirname($timeFile),0755,true);
	@file_put_contents($timeFile, time());
	@file_put_contents($pidFile, getmypid());
	
	echo "Starting......: ".date("H:i:s")." Stopping postfix\n";
	shell_exec("{$GLOBALS["postfix"]} stop");
	$daemon_directory=$unix->POSTCONF_GET("daemon_directory",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	$queue_directory=$unix->POSTCONF_GET("queue_directory",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	echo "Starting......: ".date("H:i:s")." Daemon directory: $daemon_directory\n";
	echo "Starting......: ".date("H:i:s")." Queue directory.: $queue_directory\n";
	$pid=$unix->PIDOF("$daemon_directory/master",true);
	echo "Starting......: ".date("H:i:s")." Process \"$daemon_directory/master\" PID:\"$pid\"\n";
	
	for($i=0;$i<10;$i++){
		if(is_numeric($pid)){
			if($pid>5){
				echo "Starting......: ".date("H:i:s")." Killing bad pid $pid\n";
				$unix->KILL_PROCESS($pid,9);
				sleep(1);
				
			}
		}else{
			echo "Starting......: ".date("H:i:s")." No $daemon_directory/master ghost process\n";
			break;
		}
		$pid=$unix->PIDOF("$daemon_directory/master");
		
		echo "Starting......: ".date("H:i:s")." Process \"$daemon_directory/master\" PID:\"$pid\"\n";
	}
	
	if(file_exists("$daemon_directory/master.lock")){
		echo "Starting......: ".date("H:i:s")." Delete $daemon_directory/master.lock\n";
		@unlink("$daemon_directory/master.lock");
	
	}
	if(file_exists("$queue_directory/pid/master.pid")){
		echo "Starting......: ".date("H:i:s")." Delete $queue_directory/pid/master.pid\n";
		@unlink("$queue_directory/pid/master.pid");
	}
	
	if(file_exists("$queue_directory/pid/inet.127.0.0.1:33559")){
		echo "Starting......: ".date("H:i:s")." $queue_directory/pid/inet.127.0.0.1:33559\n";
		@unlink("$queue_directory/pid/inet.127.0.0.1:33559");
	}
	
	
	echo "Starting......: ".date("H:i:s")." Starting postfix\n";
	exec("{$GLOBALS["postfix"]} start -v 2>&1",$results);
    foreach ($results as $nothing) {echo "Starting......: ".date("H:i:s")." Starting postfix $nothing\n";}
}

function postconf($key,$value=null){
    $unix=new unix();
    $unix->POSTCONF_SET($key,$value,$GLOBALS["POSTFIX_INSTANCE_ID"]);

}
function postconf_X($key){
    $unix=new unix();
    $unix->POSTCONF_SET($key,null,$GLOBALS["POSTFIX_INSTANCE_ID"]);

}
function postconf_strip_key(){
	$t=array();
	$f=file("{$GLOBALS["MAINCF_ROOT"]}/main.cf");
	foreach ( $f as $index=>$line ){
		$line=str_replace("\r", "", $line);
		$line=str_replace("\n", "", $line);
        $line=trim($line);
		if(trim($line)==null){
			echo "Starting......: ".date("H:i:s")." Starting postfix cleaning line $index (unused line)\n";
			continue;
		}

        if(preg_match("#^alias_maps.*?=.*?(dbm|netinfo):#",$line)){
            continue;
        }
        if(preg_match("#^alias_maps.*?=.*?, nis:#",$line)){
            continue;
        }
        if(preg_match("#^virtual_$#",$line)){
            continue;
        }
        if(preg_match("#^alias_maps.*?=(.*?)virtual\.domains$#",$line,$re)){
            $line="virtual_alias_maps ={$re[1]}virtual.domains";
        }

		$t[]=$line;
	}
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/main.cf", @implode("\n", $t)."\n");
	
}

function smtpd_milters():bool{
    shell_exec("/usr/sbin/artica-phpfpm-service -postfix-milters -instanceid {$GLOBALS["POSTFIX_INSTANCE_ID"]}");
    return true;
}
function wlscreen(){
	echo "wlscreen()\n";
}
function CleanUpMainCf(){
	
	$DBS["mydestination"]=true;
	$DBS["copy.transport"]=true;
	$DBS["sender_dependent_relayhost"]=true;
	$DBS["sender_canonical"]=true;
	$DBS["sender_bcc"]=true;
	$DBS["recipient_bcc"]=true;
	$DBS["smtp_generic_maps"]=true;
	$DBS["relay_domains"]=true;
	$DBS["transport"]=true;


	if(!is_file("{$GLOBALS["MAINCF_ROOT"]}/header_checks")){@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/header_checks", "\n");}

    foreach ($DBS as $filename=>$none){
		if(!is_file("{$GLOBALS["MAINCF_ROOT"]}/$filename")){@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/$filename", "\n");}
		
		if(!is_file("{$GLOBALS["MAINCF_ROOT"]}/$filename.db")){
			echo "Starting......: ".date("H:i:s")." Postfix compiling $filename database\n";
			shell_exec("{$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/$filename >/dev/null 2>&1");
		}
		
	}
	
	$f=explode("\n",@file_get_contents("{$GLOBALS["MAINCF_ROOT"]}/main.cf"));
	foreach ( $f as $line ){
		if(preg_match("#^\##", $line)){
			echo "Starting......: ".date("H:i:s")." Postfix cleaning mark line $line\n";
			continue;
		}
		
		if(preg_match("#PATH=\/bin#s", $line)){
			echo "Starting......: ".date("H:i:s")." Postfix cleaning bad parameters $line\n";
			continue;
		}
		
		if(preg_match("#ddd\s+.*?daemon#is", $line)){
			echo "Starting......: ".date("H:i:s")." Postfix cleaning bad parameters $line\n";
			continue;
		}
		
		
		if(preg_match("#^(.+?)=(.*)#", $line,$re)){
			if(trim($re[2])==null){
				echo "Starting......: ".date("H:i:s")." Postfix cleaning unused parameter `{$re[1]}`\n";
				continue; 
			}
		}
		
		
		$r[]=$line;
		
	}
	
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/main.cf", @implode("\n", $r));
	echo "Starting......: ".date("H:i:s")." Postfix cleaning {$GLOBALS["MAINCF_ROOT"]}/main.cf done\n";
	echo "Starting......: ".date("H:i:s")." Postfix Please wait...set permissions..\n";
	shell_exec("{$GLOBALS["postfix"]} set-permissions >/dev/null 2>&1");
	echo "Starting......: ".date("H:i:s")." Postfix set permissions done..\n";
	
	
}

function SMTP_SASL_PROGRESS(){
	SMTP_SASL_PROGRESS_LOG("Check structure",10);
	SetSASLMech();
	SMTP_SASL_PROGRESS_LOG("Enable TLS",30);
	SetTLS();
	SMTP_SASL_PROGRESS_LOG("SMTP SASL whitelisted networks",55);
	smtpd_sasl_exceptions_networks();
	SMTP_SASL_PROGRESS_LOG("Build Master.cf",60);
	MasterCFBuilder();
	SMTP_SASL_PROGRESS_LOG("Checks transport table",70);
	MailBoxTransport();
	SMTP_SASL_PROGRESS_LOG("{reloading} SMTP MTA",80);
	ReloadPostfix(true);
	SMTP_SASL_PROGRESS_LOG("{reloading} SaslAuthd",90);
	system("/etc/init.d/saslauthd restart");
	SMTP_SASL_PROGRESS_LOG("{done}",100);
	
}
function SMTP_SASL_PROGRESS_LOG($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/SMTP_SASL_PROGRESS";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


?>