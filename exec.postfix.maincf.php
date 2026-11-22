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

if($argv[1]=='--assp'){ASSP_LOCALDOMAINS();exit();}
if($argv[1]=='--artica-filter'){MasterCFBuilder(true);exit();}
if($argv[1]=='--ssl'){SMTP_SASL_PROGRESS(true);exit();}
if($argv[1]=='--ssl-on'){MasterCFBuilder(true);exit();}
if($argv[1]=='--ssl-off'){MasterCFBuilder(true);exit();}
if($argv[1]=='--ssl-none'){MasterCFBuilder(false);exit();}
if($argv[1]=='--imap-sockets'){imap_sockets();ReloadPostfix(true);exit();}
if($argv[1]=='--restricted'){exit();}
if($argv[1]=='--banner'){smtp_banner(true);exit();}


if($argv[1]=='--others-values'){OthersValues_start();}
if($argv[1]=='--interfaces'){inet_interfaces();exec("{$GLOBALS["postfix"]} stop");exec("{$GLOBALS["postfix"]} start");ReloadPostfix(true);exit();}
if($argv[1]=='--mailbox-transport'){ReloadPostfix(true);exit();}
if($argv[1]=='--disable-smtp-sasl'){ReloadPostfix(true);exit();}
if($argv[1]=='--perso-settings'){perso_settings();exit();}
if($argv[1]=='--luser-relay'){luser_relay();exit();}
if($argv[1]=='--smtp-sender-restrictions'){smtp_cmdline_restrictions();ReloadPostfix(true);exit();}
if($argv[1]=='--postdrop-perms'){fix_postdrop_perms();exit;}
if($argv[1]=='--smtpd-restrictions'){smtp_cmdline_restrictions();exit();}
if($argv[1]=='--repair-locks'){repair_locks();exit;}
if($argv[1]=='--smtp-sasl'){SMTP_SASL_PROGRESS();exit;}
if($argv[1]=='--memory'){memory();exit;}
if($argv[1]=='--postscreen'){postscreen(isset($argv[2])?intval($argv[2]):0);exit;}
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
    // The aggregate /var/log/mail.log sink is owned by the artica-postfix daemon
    // (postfix/maillog: the base `mail.*` route, or its 48-artica-mail-log.conf drop-in).
    // Writing it HERE as well made every postfix line land in mail.log TWICE — the drop-in's
    // `& stop` is legacy continuation syntax inside a RainerScript block and does not reliably
    // halt processing, so the line still reached the mail.* route. This file now carries ONLY
    // the optional remote-syslog forwarding; when there is none, it is removed entirely.
    $remote = "";
    if (function_exists("BuildRemoteSyslogs")) {
        $remote = trim(BuildRemoteSyslogs('postfix'));
    }
    if ($remote == "") {
        if (is_file($tfile)) {
            @unlink($tfile);
            system("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
        }
        return;
    }
    $h = array();
    $h[] = "if  (\$programname  contains 'postfix') then {";
    $h[] = $remote;
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
		"cleanMultiplesInstances","SetTLS","inet_interfaces","imap_sockets",
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
	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	$sock=$GLOBALS["CLASS_SOCKETS"];
	if($sock->GET_INFO("EnablePostfixMultiInstance")==1){return;}

	// All single-instance main.cf "other values" tuning (~55 postconf
	// parameters: connection cache, address verification, error/rate limits,
	// concurrency, queue timing, banner, MIME, DNS-lookup version gating) was
	// ported to articarest postfix.OtherValuesSingleInstance(). The CLI bridge
	// below runs it synchronously for the master instance; perso_settings()
	// then applies the user's custom main.cf overrides on top so they win.
	echo "Starting......: ".date("H:i:s")." Fix others settings (articarest)\n";
	build_progress_mime_header("{configuring}",50);
	shell_exec("/usr/local/sbin/artica-postfix -postfix-other-values");
	build_progress_mime_header("{configuring} {done}",60);

	echo "Starting......: ".date("H:i:s")." Apply perso_settings\n";
	build_progress_mime_header("Perso settings...",70);
	perso_settings();
	build_progress_mime_header("Perso settings...",75);
}



function inet_interfaces(){
    $GLOBALS["CLASS_SOCKETS"]->REST_API_POSTFIX("/postfix/maincf/interfaces");
}


function perso_settings(){
	$main=new main_perso();
	$main->replace_conf("{$GLOBALS["MAINCF_ROOT"]}/main.cf");
	if($GLOBALS["RELOAD"]){exec("{$GLOBALS["postfix"]} reload >/dev/null 2>&1");}
	
}

function luser_relay(){}






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

function postscreen($instanceid=0){
	// Instance 0 is the single/master instance (default); multi-instance
	// callers pass the instance ID. All postscreen(8) configuration — tunables,
	// dnsbl sites, the postscreen_access maps built from postfix_whitelist_con
	// + mynetworks, and the master.cf rebuild — was ported to articarest
	// postfix.PostScreen(). Postscreen-installed detection is now done live
	// daemon-side via the PostTools module (no longer the stale
	// POSTSCREEN_INSTALLED Valkey flag). The CLI bridge runs it synchronously
	// for the requested instance and reloads Postfix itself.
	$instanceid=intval($instanceid);
	echo "Starting......: ".date("H:i:s")." PostScreen configuring (articarest) instance:$instanceid\n";
	system("/usr/sbin/artica-phpfpm-service -postscreen -instanceid $instanceid");
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




function MasterCFBuilder($restart_service=false,$instanceid=0){
	// Instance 0 is the single/master instance (default); multi-instance
	// callers pass the instance ID. master.cf generation — SSL (465) /
	// submission (587) services, postscreen + tlsproxy + dnsblog (gated on a
	// LIVE postscreen-installed check via PostTools), per-domain throttle
	// transports (domain_throttle_daemons_list), and the standard service
	// table — was ported to articarest postfix.MasterCF(). The CLI bridge
	// builds it synchronously for the requested instance; reload/restart stay
	// here where $GLOBALS["RELOAD"] lives.
	$instanceid=intval($instanceid);
	$unix=new unix();
	build_progress_sender_routing("{building} Master.cf",35);
	system("/usr/local/sbin/artica-postfix -master-cf -instanceid $instanceid");
	build_progress_sender_routing("{building} master.cf {done}",55);

	if($GLOBALS["RELOAD"]){
		$unix->POSTFIX_RELOAD($instanceid);
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
		echo "Starting......: ".date("H:i:s")." Postfix executing !!!! DEPRECIATED\n";
		return true;
	}


	$MOUNTED_TMPFS_MEM=$unix->MOUNTED_TMPFS_MEM($directory);
    if($MOUNTED_TMPFS_MEM>0){
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
    shell_exec("/usr/local/sbin/artica-postfix -postfix-milters -instanceid {$GLOBALS["POSTFIX_INSTANCE_ID"]}");
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