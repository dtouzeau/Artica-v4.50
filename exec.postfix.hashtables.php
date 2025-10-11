<?php
$GLOBALS["EnablePostfixMultiInstance"]=0;
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.maincf.multi.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.main.hashtables.inc');
include_once(dirname(__FILE__) . '/ressources/class.postfix.externaldbs.inc');

if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--pourc=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["POURC_START"]=$re[1];}

$sock=new sockets();
$unix=new unix();
$GLOBALS["CLASS_UNIX"]=$unix;
$GLOBALS["MAINCF_ROOT"]="/etc/postfix";
$GLOBALS["POSTFIX_INSTANCE_ID"]=0;
if(preg_match("#--instance-id=([0-9]+)#",implode(" ",$argv),$re)){
	$GLOBALS["POSTFIX_INSTANCE_ID"]=intval($re[1]);
}
if($GLOBALS["POSTFIX_INSTANCE_ID"]>0){
	$GLOBALS["MAINCF_ROOT"]="/etc/postfix-instance{$GLOBALS["POSTFIX_INSTANCE_ID"]}";
}


$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
$GLOBALS["EnableBlockUsersTroughInternet"]=intval($main->GET_INFO("EnableBlockUsersTroughInternet"));
$GLOBALS["postmap"]=$GLOBALS["CLASS_UNIX"]->find_program("postmap");
$GLOBALS["newaliases"]=$GLOBALS["CLASS_UNIX"]->find_program("newaliases");
$GLOBALS["postalias"]=$GLOBALS["CLASS_UNIX"]->find_program("postalias");
$GLOBALS["postfix"]=$GLOBALS["CLASS_UNIX"]->find_program("postfix");
$GLOBALS["newaliases"]=$GLOBALS["CLASS_UNIX"]->find_program("newaliases");
$GLOBALS["virtual_alias_maps"]=array();
$GLOBALS["alias_maps"]=array();
$GLOBALS["relay_domains"]=array();
$GLOBALS["bcc_maps"]=array();
$GLOBALS["smtp_generic_maps"]=array();
$GLOBALS["PHP5_BIN"]=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
$GLOBALS["CLASS_UNIX"]=$unix;

$EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
if($EnablePostfix==0){echo "Postfix is disabled\n";exit();}

if(!is_file($GLOBALS["postfix"])){exit();}

$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
if($GLOBALS["CLASS_UNIX"]->process_exists($pid,basename(__FILE__))){
	$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
	echo "Starting......: ".date("H:i:s")." Already executed pid:$pid since {$time}Mn\n";
	$GLOBALS["CLASS_UNIX"]->send_email_events("Postfix user databases aborted (instance executed)", "Already instance pid $pid is executed", "postfix");
	exit();
}

@file_put_contents($pidfile, getmypid());

$ldap=new clladp();
if($ldap->ldapFailed){
	WriteToSyslogMail("Fatal: connecting to ldap server $ldap->ldap_host",basename(__FILE__),true);
	echo "Starting......: ".date("H:i:s")." failed connecting to ldap server $ldap->ldap_host\n";
	$GLOBALS["CLASS_UNIX"]->send_email_events("Postfix user databases aborted (ldap failed)", "The process has been scheduled to start in few seconds.", "postfix");
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET(trim($GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN()." ".__FILE__. " {$argv[1]}"));
	exit();
}

if($argv[1]=="--dump-db_extern"){
	$GLOBALS["VERBOSE"]=true;
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	DUMP_EXTERNALS_DBS();
	exit();
}



if($argv[1]=="--postmaster"){
	postmaster();
	build_progress_postmaster("{postmaster} {done}",95);
	echo "Starting......: ".date("H:i:s")." Postfix reloading\n";
	$GLOBALS["CLASS_UNIX"]->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
	build_progress_postmaster("{postmaster} {success}",100);
	exit();
}

$php=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
if($argv[1]=="--virtuals"){cmdline_virtuals();exit;}
if($argv[1]=="--mailbox-transport-maps"){
	system("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.transport.php --mailbox-transport-maps");
}

if($argv[1]=="--mailman"){
	internal_pid($argv);
	cmdline_alias();
	perso_settings();
	echo "Starting......: ".date("H:i:s")." Postfix reloading\n";
	$GLOBALS["CLASS_UNIX"]->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
	exit();
}

if($argv[1]=="--canonical"){
	recipient_canonical_maps();
	sender_canonical_maps();
	smtp_generic_maps();
	echo "Starting......: ".date("H:i:s")." Postfix reloading\n";
	$GLOBALS["CLASS_UNIX"]->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
	exit();

}


if($argv[1]=="--relayhost"){
	system("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.transport.php --relayhost");
	exit();
}


if($argv[1]=="--bcc"){

	internal_pid($argv);
	system("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.transport.php");
	perso_settings();
	$GLOBALS["CLASS_UNIX"]->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
	exit();
}

if($argv[1]=="--recipient-canonical"){
	internal_pid($argv);

	recipient_canonical_maps();
	perso_settings();
	$GLOBALS["CLASS_UNIX"]->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
	exit();
}




if($argv[1]=="--transport"){
	system("{$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.postfix.transport.php");
	exit();
}



if($argv[1]=="--aliases"){
	internal_pid($argv);
	build_progress_aliases("Building aliases...",10);
	cmdline_alias();
	build_progress_aliases("Building perso_settings...",70);
	perso_settings();
	build_progress_aliases("{reloading}",80);
	echo "Starting......: ".date("H:i:s")." Postfix reloading\n";
	$GLOBALS["CLASS_UNIX"]->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
	build_progress_aliases("{done}",100);
	exit();}

if($argv[1]=="--smtp-passwords"){
	internal_pid($argv);
	sender_canonical_maps();
	recipient_canonical_maps();
	smtp_generic_maps();
	sender_dependent_default_transport_maps();
	perso_settings();
	echo "Starting......: ".date("H:i:s")." Postfix reloading\n";
	$GLOBALS["CLASS_UNIX"]->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
	exit();}




if($argv[1]=="--sender-dependent-relayhost"){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	build_progress_sender_routing("{building}: relayhost",10);
	relayhost();
	build_progress_sender_routing("{building}: sender routing table",20);
	build_progress_sender_routing("{building}: Patching service table",30);
	system("$php /usr/share/artica-postfix/exec.postfix.maincf.php --ssl --progress-sender-dependent-relayhost --instance-id={$GLOBALS["POSTFIX_INSTANCE_ID"]}");



	build_progress_sender_routing("{building}: Personal settings",80);
	perso_settings();
	build_progress_sender_routing("{reloading}",90);
	echo "Starting......: ".date("H:i:s")." Postfix reloading\n";
	$GLOBALS["CLASS_UNIX"]->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
	build_progress_sender_routing("{done}",100);
	exit();
}

if($argv[1]=="--smtp-generic-maps"){
	internal_pid($argv);
	build_progress_smtp_generic_maps("{buiding} {senders} Canonicals...",10);
	sender_canonical_maps();
	build_progress_smtp_generic_maps("{buiding} {recipients} Canonicals...",20);
	recipient_canonical_maps();
	build_progress_smtp_generic_maps("{configuring} SMTP Generic Maps...",30);
	smtp_generic_maps();
	build_progress_smtp_generic_maps("{building}: Personal settings",90);
	perso_settings();
	build_progress_smtp_generic_maps("{reloading}",95);
	echo "Starting......: ".date("H:i:s")." Postfix reloading\n";
	$GLOBALS["CLASS_UNIX"]->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);
	build_progress_smtp_generic_maps("{done}",100);
	exit();

}

$pidfile="/etc/artica-postfix/pids/postfix.reconfigure2.pid";
$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
if($GLOBALS["CLASS_UNIX"]->process_exists($pid,basename(__FILE__))){
	$time=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Postfix Already Artica task running PID $pid since {$time}mn\n";}
	exit();
}
@file_put_contents($pidfile, getmypid());


$start=50;

internal_pid($argv);

$functions=array(
	"LoadLDAPDBs","maillings_table","aliases_users","aliases","catch_all","build_aliases_maps","build_virtual_alias_maps","recipient_canonical_maps_build","recipient_canonical_maps","sender_canonical_maps_build","sender_canonical_maps",	"smtp_generic_maps_build_global","smtp_generic_maps","sender_dependent_default_transport_maps",	"build_local_recipient_maps","postmaster","perso_settings");
$tot=count($functions);
$i=0;
foreach ($functions as $func){
	$i++;
	$start++;
	if(!function_exists($func)){
		SEND_PROGRESS($start,$func,"Error $func no such function...");
		continue;
	}
	try {
		SEND_PROGRESS($start,"Action 2, {$start}% Please wait, executing $func() $i/$tot..");
		call_user_func($func);
	} catch (Exception $e) {
		SEND_PROGRESS($start,$func,"Error on $func ($e)");
	}
}



$reste=100-$start;
$reste++;
SEND_PROGRESS($reste,"mydestination");
$hashT=new main_hash_table();
$hashT->mydestination();
SEND_PROGRESS(100,"Reload postfix");
$GLOBALS["CLASS_UNIX"]->POSTFIX_RELOAD($GLOBALS["POSTFIX_INSTANCE_ID"]);

function build_progress_smtp_generic_maps($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/smtp_generic_maps";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function build_progress_sender_routing($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/build_progress_sender_routing";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function build_progress_postmaster($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/build_progress_postmaster";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_aliases($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/postfix.aliases.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}



function SEND_PROGRESS($POURC,$text,$error=null):bool{
	$cache=PROGRESS_DIR."/POSTFIX_COMPILES";
	if($error<>null){echo "Fatal !!!! $error\n";}
	echo "{$POURC}% $text\n";

	$array=unserialize(@file_get_contents($cache));
	$array["POURC"]=$POURC;
	$array["TEXT"]=$text;
	if($error<>null){$array["ERROR"][]=$error;}
	@mkdir(dirname($cache),0755,true);
	@file_put_contents($cache, serialize($array));
	@chmod($cache, 0777);
	$instance_id=$GLOBALS["POSTFIX_INSTANCE_ID"];
	if($instance_id>0) {
		$unix=new unix();
		$unix->framework_progress(75, "$POURC: $text", "postfix-multi.$instance_id.reinstall.progress");

		if($POURC>95) {
			$POURC = 95;
		}
		$unix->framework_progress($POURC, "$POURC: maincf:$text", "postfix-multi.$instance_id.reconfigure.progress");

	}
return true;
}


function internal_pid($argv){

	$md5=md5(serialize($argv));

	unset($argv[0]);
	$cmsline=@implode(" ", $argv);

	$mef=basename(__FILE__);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".$md5.pid";
	$pid=@file_get_contents($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid,$mef)){
		build_progress_smtp_generic_maps("{failed} Process Already exist pid $pid",110);
		echo "Starting......: ".date("H:i:s")." Postfix : Process Already exist pid $pid line:".__LINE__."\n";
		squid_admin_mysql(2, "`$cmsline` task cannot be performed, a Process Already exist pid $pid", __FUNCTION__, __FILE__, __LINE__, "postfix");
		exit();
	}

	@file_put_contents($pidfile, getmypid());

}

function cmdline_virtuals(){
	build_aliases_maps();
	build_virtual_alias_maps();
}


function cmdline_alias(){
	build_progress_aliases("Building LoadLDAPDBs...",15);
	LoadLDAPDBs();
	build_progress_aliases("Building maillings_table...",20);
	maillings_table();
	build_progress_aliases("Building aliases_users...",25);
	aliases_users();
	build_progress_aliases("Building aliases...",30);
	aliases();
	build_progress_aliases("Building catch_all...",35);
	catch_all();
	build_progress_aliases("Building build_aliases_maps...",40);
	build_aliases_maps();
	build_progress_aliases("Building build_virtual_alias_maps...",45);
	build_virtual_alias_maps();
	build_progress_aliases("Building postmaster...",50);
	postmaster();
	build_progress_aliases("Building recipient_canonical_maps...",60);
	recipient_canonical_maps();
	build_progress_aliases("Building aliases {done}...",65);
}



function perso_settings(){
	$main=new main_perso();
	$main->replace_conf("{$GLOBALS["MAINCF_ROOT"]}/main.cf");
}


function recipient_bcc_maps(){

	$ldap=new clladp();
	$filter="(&(objectClass=UserArticaClass)(RecipientToAdd=*))";
	$attrs=array("RecipientToAdd","mail");
	$dn="dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);

	for($i=0;$i<$hash["count"];$i++){
		$mail=$hash[$i]["mail"][0];
		$RecipientToAdd=$hash[$i]["recipienttoadd"][0];
		$GLOBALS["bcc_maps"][]="$mail\t$RecipientToAdd";

	}
	echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["bcc_maps"])." recipient(s) BCC\n";
}




function recipient_bcc_domain_maps(){
	$sql="SELECT * FROM postfix_duplicate_maps";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$c=0;
	$f=array();
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($ligne["pattern"]==null){continue;}

		$left="(.*)";
		$right='${1}';
		$leftNext="(.*)";
		$rightNext='${1}';
		$domain=$ligne["pattern"];
		$nextdomain=$ligne["nextdomain"];
		$nextdomain_transport=$ligne["nextdomain"];



		if(preg_match("#(.+?)@(.+)#",$ligne["pattern"],$re)){
			$nextHope_pattern=$ligne["pattern"];
			$domain=$re[2];
			$left=$re[1];
			$right=$re[1];
			$rightNext=$right;
			$left=str_replace(".","\.",$left);
			$right=str_replace(".","\.",$right);
			$leftNext=$left;
		}

		if(preg_match("#(.+?)@(.+)#",$ligne["nextdomain"],$re)){
			$right=$re[1];
			$nextdomain=$re[2];

		}

		$md5=md5($domain);
		$domain_regex=str_replace(".","\.",$domain);
		$f[]="/^$left@$domain_regex$/   $right@$nextdomain";
		$t[]="$nextdomain_transport\tsmtp:[{$ligne["relay"]}]:{$ligne["port"]}";
		$c++;
	}
	echo "Starting......: ".date("H:i:s")." ".count($f)." duplicated destination(s)\n";
	$f[]="";
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/copy.pcre",implode("\n",$f));
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/copy.transport",implode("\n",$t));
	shell_exec("{$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/copy.transport >/dev/null 2>&1");
}
function recipient_bcc_maps_build(){
	if(!is_array($GLOBALS["bcc_maps"])){
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("recipient_bcc_maps","pcre:{$GLOBALS["MAINCF_ROOT"]}/copy.pcre",$GLOBALS["POSTFIX_INSTANCE_ID"]);
		return null;
	}

	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("recipient_bcc_maps","hash:{$GLOBALS["MAINCF_ROOT"]}/recipient_bcc,pcre:{$GLOBALS["MAINCF_ROOT"]}/copy.pcre",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	echo "Starting......: ".date("H:i:s")." Compiling Recipient(s) BCC\n";
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/recipient_bcc",implode("\n",$GLOBALS["bcc_maps"]));
	shell_exec("{$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/recipient_bcc >/dev/null 2>&1");
}



function repair_addr($email){
	$old_email=$email;
	$email=trim(strtolower($email));
	$email=str_replace("\n", "", $email);
	$email=str_replace("\r", "", $email);
	if(strlen($email)<3){return null;}
	$email=str_replace(" ", "", $email);
	$email=str_replace(";", ".", $email);
	if(!preg_match("#^(.+?)@(.+)#", $email)){return null;}
	if(preg_match("#^(.+?)\s+(.+)#", $email,$re)){$email="{$re[1]}{$re[2]}";}
	if($GLOBALS["VERBOSE"]){echo "[".__LINE__."]: `$old_email` [$email]\n";}
	return $email;
}

function maillings_table(){
	if(isset($GLOBALS["maillings_table_exectuted"])){return;}
	$GLOBALS["maillings_table_exectuted"]=true;
	$sock=new sockets();
	$MailingListUseLdap=$sock->GET_INFO("MailingListUseLdap");
	if(!is_numeric($MailingListUseLdap)){$MailingListUseLdap=0;}
	if($MailingListUseLdap==1){return;}
	$ldap=new clladp();
	$filter="(&(objectClass=MailingAliasesTable)(cn=*))";
	$attrs=array("cn","MailingListAddress","MailingListAddressGroup");
	$dn="dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);

	for($i=0;$i<$hash["count"];$i++){
		$cn=trim($hash[$i]["cn"][0]);
		$MailingListAddressGroup=0;
		if(isset($hash[$i]["mailinglistaddressgroup"])){
			$MailingListAddressGroup=$hash[$i]["mailinglistaddressgroup"][0];
		}
		for($t=0;$t<$hash[$i]["mailinglistaddress"]["count"];$t++){
			$mailinglistaddress_email=repair_addr($hash[$i]["mailinglistaddress"][$t]);
			if($mailinglistaddress_email==null){continue;}
			if($GLOBALS["DEBUG"]){echo "[".__LINE__."]: maillings_table(): -> \"$mailinglistaddress_email\"\n";}
			$mailinglistaddress[$mailinglistaddress_email]=$mailinglistaddress_email;
		}

		if($MailingListAddressGroup==1){
			$uid=$ldap->uid_from_email($cn);
			$user=new user($uid);
			$array=$user->MailingGroupsLoadAliases();

			foreach ($array as $num=>$ligne){
				$ligne=repair_addr($ligne);
				if(trim($ligne)==null){continue;}
				if($GLOBALS["DEBUG"]){echo "[".__LINE__."]: $uid -> [$ligne]\n";}
				$mailinglistaddress[$ligne]=$ligne;
			}
		}

		$final=array();
		if(is_array($mailinglistaddress)){
			foreach ($mailinglistaddress as $num=>$ligne){
				$num=repair_addr($num);
				if($num==null){continue;}
				$final[]=$num;
			}

			if($GLOBALS["DEBUG"]){echo "[".__LINE__."]: maillings_table(): $cn = ". implode(",",$final)."\n";}
			if(count($final)>0){
				$cn=trim($cn);
				$cn=str_replace("\n", "",$cn);
				$cn=str_replace("\r", "",$cn);
				if($cn==null){continue;}
				$GLOBALS["virtual_alias_maps_emailing"][$cn]="$cn\t". implode(",",$final);
			}
		}

		unset($final);
		unset($mailinglistaddress);
		$MailingListAddressGroup=0;
	}




	$filter="(&(objectClass=ArticaMailManRobots)(cn=*))";
	$attrs=array("cn","MailManAliasPath");
	$dn="dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	$sock=new sockets();
	if($sock->GET_INFO("MailManEnabled")==1){$GLOBALS["MAILMAN"]=true;}else{
		$GLOBALS["MAILMAN"]=false;
		return;
	}

	if($hash["count"]>0){$GLOBALS["MAILMAN"]=true;}else{$GLOBALS["MAILMAN"]=false;}


}


function catch_all(){
	$ldap=new clladp();
	$filter="(&(objectClass=AdditionalPostfixMaps)(cn=*))";
	$attrs=array("cn","CatchAllPostfixAddr");
	$dn="cn=catch-all,cn=artica,$ldap->suffix";

	if($GLOBALS["DEBUG"]){echo __FUNCTION__." -> open branch $dn $filter\n";}

	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	if($GLOBALS["DEBUG"]){echo __FUNCTION__." -> found {$hash["count"]} entries\n";}
	for($i=0;$i<$hash["count"];$i++){
		$cn=$hash[$i]["cn"][0];
		for($t=0;$t<$hash[$i][strtolower("CatchAllPostfixAddr")]["count"];$t++){
			echo "Starting......: ".date("H:i:s")." catch-all {$hash[$i][strtolower("CatchAllPostfixAddr")][$t]} for $cn\n";
			if(substr($cn,0,1)<>"@"){$cn=trim("@$cn");}
			if($GLOBALS["DEBUG"]){echo __FUNCTION__." -> virtual_alias_maps=$cn\t{$hash[$i][strtolower("CatchAllPostfixAddr")][$t]}\n";}
			$GLOBALS["virtual_alias_maps"][$cn]="$cn\t{$hash[$i][strtolower("CatchAllPostfixAddr")][$t]}";
		}
	}
}
function LoadLDAPDBs(){
	if(isset($GLOBALS["LoadLDAPDBs_performed"])){return ;}
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$databases_list=unserialize(base64_decode($main->GET_BIGDATA("ActiveDirectoryDBS")));
	if(is_array($databases_list)){
		foreach ($databases_list as $dbindex=>$array){
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: {$array["database_type"]}; enabled={$array["enabled"]}\n";}
			if($array["enabled"]<>1){
				if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: {$array["database_type"]} is not enabled, skipping\n";}
				continue;
			}
			$targeted_file=$main->buidLdapDB("master",$dbindex,$array);
			if(!is_file($targeted_file)){
				if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: {$array["database_type"]} \"$targeted_file\" no such file, skipping\n";}
				continue;
			}


//$GLOBALS["REMOTE_SMTP_LDAPDB_ROUTING"]

			if($array["resolv_domains"]==1){$domains=$main->buidLdapDBDomains($array);}

			$GLOBALS["LDAPDBS"][$array["database_type"]][]="ldap:$targeted_file";
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: GLOBALS[LDAPDBS][{$array["database_type"]}]=ldap:$targeted_file\n";}
		}
	}
	$GLOBALS["LoadLDAPDBs_performed"]=true;
}





function aliases_users(){
	$ldap=new clladp();
	$users=new usersMenus();
	$main = new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	if($GLOBALS["VERBOSE"]){echo "*** aliases_users() ***\n";}
	$filter="(&(objectClass=userAccount)(uid=*))";
	$attrs=array("uid","mail");
	$trap_uid="uid";
	$dn="dc=organizations,$ldap->suffix";

	if($ldap->EnableManageUsersTroughActiveDirectory){
		$ldapAD=new ldapAD();
		$filter="(&(objectClass=user)(samaccountname=*))";
		$attrs=array("samaccountname","mail");
		$trap_uid="samaccountname";
		$dn="$ldapAD->suffix";
		$hash=$ldapAD->Ldap_search($dn,$filter,$attrs);
	}else{
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	}

	for($i=0;$i<$hash["count"];$i++){
		$uid=trim($hash[$i][$trap_uid][0]);
		if(strpos($uid,"$")>0){continue;}
		if($uid==null){continue;}

		if(isset($hash[$i]["mail"])){
			for($t=0;$t<$hash[$i]["mail"]["count"];$t++){
				$mail=$hash[$i]["mail"][$t];
				$mail=repair_addr($mail);
				$mail=trim($mail);
				$mail=str_replace("\r", "", $mail);
				$mail=str_replace("\n", "", $mail);
				if($mail==null){continue;}

				if(!isset($GLOBALS["virtual_alias_maps_mem"][$mail])){
					if(!isset($GLOBALS["virtual_alias_maps_emailing"][$mail])){$GLOBALS["virtual_alias_maps_emailing"][$mail]=null;}
					if($GLOBALS["virtual_alias_maps_emailing"][$mail]==null){$GLOBALS["virtual_alias_maps"][$mail]="$mail\t$mail";}
				}

				$GLOBALS["virtual_alias_maps_mem"][$mail]=true;

				if(!isset($GLOBALS["alias_maps_mem"][$uid])){
					$uid=trim($uid);
					$uid=str_replace("\r", "", $uid);
					$uid=str_replace("\n", "", $uid);
					if($uid==null){continue;}
					if(!preg_match("#.+?@#",$uid)){$GLOBALS["alias_maps"][]="$uid:$mail";}
					$GLOBALS["alias_maps_mem"][$uid]=true;
				}

				$GLOBALS["virtual_mailbox"]="$mail\t$uid";
			}
		}else{
			if($GLOBALS["VERBOSE"]){echo "Skipping \"$uid\" no \"mail\" attribute... in ". basename(__FILE__)." Line: ".__LINE__."\n";}
		}
	}

	$filter="(&(objectClass=transportTable)(cn=*@*))";
	$attrs=array("cn");
	$dn="cn=PostfixRobots,cn=artica,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		$cn=$hash[$i]["cn"][0];
		if(preg_match("#(.+?)@#",$cn,$re)){
			$map=$re[1];
			if(!$GLOBALS["alias_maps_mem"][$map]){
				$GLOBALS["alias_maps"][]="$map:$cn";
				$GLOBALS["alias_maps_mem"][$map]=true;
			}
		}
	}







	$sock=new sockets();
	$PostfixPostmaster=trim($sock->GET_INFO("PostfixPostmaster"));
	if($PostfixPostmaster==null){return;}

	$myhostname=trim($sock->GET_INFO("myhostname"));
	if($myhostname==null){$myhostname=$users->hostname;}
	preg_match("#(.+?)@#",$PostfixPostmaster,$re);
	$PostfixPostmaster_prefix=trim($re[1]);

	$myhostname=trim($myhostname);
	$GLOBALS["virtual_alias_maps"]["$PostfixPostmaster_prefix@$myhostname"]="$PostfixPostmaster_prefix@$myhostname\t$PostfixPostmaster";
	$GLOBALS["virtual_alias_maps"][$PostfixPostmaster]="$PostfixPostmaster\t$PostfixPostmaster";
	$GLOBALS["virtual_alias_maps"]["root@$myhostname"]="root@$myhostname\t$PostfixPostmaster";
	$GLOBALS["virtual_alias_maps"]["postmaster"]="postmaster\t$PostfixPostmaster";
	$GLOBALS["virtual_alias_maps"]["MAILER-DAEMON"]="MAILER-DAEMON\t$PostfixPostmaster";
	$GLOBALS["virtual_alias_maps"]["root"]="root\t$PostfixPostmaster";

	$GLOBALS["alias_maps"][]="postmaster:$PostfixPostmaster";
	$GLOBALS["alias_maps"][]="MAILER-DAEMON:$PostfixPostmaster";
	$GLOBALS["alias_maps"][]="root:$PostfixPostmaster";
	if($PostfixPostmaster_prefix<>null){
		if(!isset($GLOBALS["alias_maps_mem"][$PostfixPostmaster_prefix])){$GLOBALS["alias_maps"][]="$PostfixPostmaster_prefix:$PostfixPostmaster";}
	}




}


function build_local_recipient_maps(){
	if(!is_array($GLOBALS["local_recipient_maps"])){
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("local_recipient_maps","", $GLOBALS["POSTFIX_INSTANCE_ID"]);
		echo "Starting......: ".date("H:i:s")." No recipients maps\n";
		return null;
	}

	echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["local_recipient_maps"])." local recipient(s)\n";
	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("local_recipient_maps","hash:{$GLOBALS["MAINCF_ROOT"]}/local_recipients",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/local_recipients",implode("\n",$GLOBALS["local_recipient_maps"]));
	shell_exec("{$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/local_recipients >/dev/null 2>&1");

}

function mailling_ldap(){
	$ldap=new clladp();
	$conf[]="#Mailling list configuration to Open LDAP --------------------------------------------------------------------";
	$conf[]="server_host = $ldap->ldap_host";
	$conf[]="server_port = $ldap->ldap_port";
	$conf[]="bind = yes";
	$conf[]="bind_dn = cn=$ldap->ldap_admin,$ldap->suffix";
	$conf[]="bind_pw = $ldap->ldap_password";
	$conf[]="timeout = 10";
	$conf[]="search_base = dc=organizations,$ldap->suffix";
	$conf[]="query_filter = (&(objectclass=MailingAliasesTable)(cn=%s))";
	$conf[]="result_attribute = MailingListAddress";
	$conf[]="version =3";
	$conf[]= "#-------------------------------------------------------------------------------------------";
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/mailinglist.ldap.cf", @implode("\n", $conf));
}

function build_virtual_alias_maps(){
	// $unix=new unix();
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$ldap=new clladp();
	if($GLOBALS["DEBUG"]){echo __FUNCTION__." -> virtual_alias_maps=". count($GLOBALS["virtual_alias_maps"]) . " entries\n";}


	if(is_array($GLOBALS["virtual_alias_maps_emailing"])){
		echo "Starting......: ".date("H:i:s")." Postfix [".__LINE__."] ". count($GLOBALS["virtual_alias_maps_emailing"])." distribution listes\n";
		foreach ($GLOBALS["virtual_alias_maps_emailing"] as $num=>$ligne){
			$num=trim($num);
			$num=str_replace("\r", "", $num);
			$num=str_replace("\n", "", $num);
			if($GLOBALS["VERBOSE"]){echo "FINAL -> $num/\"$ligne\"\n";}
			if($ligne==null){continue;}
			$final[]=$ligne;
		}
	}
//-----------------------------------------------------------------------------------
	if(is_array($GLOBALS["virtual_alias_maps"])){
		echo "Starting......: ".date("H:i:s")." Cleaning virtual aliase(s)\n";
		foreach ($GLOBALS["virtual_alias_maps"] as $num=>$ligne){
			$ligne=trim($ligne);
			$ligne=str_replace("\r", "", $ligne);
			$ligne=str_replace("\n", "", $ligne);
			if($ligne==null){continue;}
			if(preg_match("#x500:#",$ligne)){continue;}
			if(preg_match("#x400:#",$ligne)){continue;}
			$final[]=$ligne;
		}
	}
//-----------------------------------------------------------------------------------
	$dn="cn=artica_smtp_sync,cn=artica,$ldap->suffix";
	$filter="(&(objectClass=InternalRecipients)(cn=*))";
	if($ldap->ExistsDN($dn)){
		$attrs=array("cn");
		$hash=$ldap->Ldap_search($dn,$filter,$attrs);
		if($hash["count"]>0){
			for($i=0;$i<$hash["count"];$i++){
				$email=$hash[$i]["cn"][0];
				$email=trim($email);
				$email=str_replace("\r", "", $email);
				$email=str_replace("\n", "", $email);
				if(trim($email)==null){continue;}
				$final[]="$email\t$email";
			}
		}
	}
//-----------------------------------------------------------------------------------


	if(isset($GLOBALS["LDAPDBS"]["virtual_alias_maps"])){
		if(!is_array($GLOBALS["LDAPDBS"]["virtual_alias_maps"])){
			$virtual_alias_maps_cf=$GLOBALS["LDAPDBS"]["virtual_alias_maps"];
		}
	}
	$sock=new sockets();
	$MailingListUseLdap=$sock->GET_INFO("MailingListUseLdap");
	if(!is_numeric($MailingListUseLdap)){$MailingListUseLdap=0;}
	if($MailingListUseLdap==1){
		$virtual_alias_maps_cf[]="ldap:{$GLOBALS["MAINCF_ROOT"]}/mailinglist.ldap.cf";
		mailling_ldap();
	}



	$sql="SELECT * FROM postfix_aliases_domains";
	$q=new mysql();
	$pre='${1}';
	$li=array();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$ligne["alias"]=trim($ligne["alias"]);
		$ligne["alias"]=strtolower($ligne["alias"]);
		$aliases=str_replace(".","\.",$ligne["alias"]);
		$domain=$ligne["domain"];
		$li[]="/^(.*)@$aliases$/\t$pre@$domain";
		$final[]="{$ligne["alias"]}\tDOMAIN";
	}

	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$virtual_mailing_addr=$main->mailling_list_mysql("master");
	if(is_array($virtual_mailing_addr)){
		foreach ($virtual_mailing_addr as $num=>$ligne){
			$final[]=$ligne;
		}
	}


	echo "Starting......: ".date("H:i:s")." Postfix ". count($final)." virtual aliase(s)\n";
	echo "Starting......: ".date("H:i:s")." Postfix ". count($li)." virtual domain(s) aliases\n";
	$virtual_alias_maps_cf[]="hash:{$GLOBALS["MAINCF_ROOT"]}/virtual";
	$virtual_alias_maps_cf[]="pcre:{$GLOBALS["MAINCF_ROOT"]}/virtual.domains";

	if($GLOBALS["DEBUG"]){echo __FUNCTION__." -> writing /etc/postfix/virtual\n";}
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/virtual",implode("\n",$final));
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/virtual.domains",implode("\n",$li));

	echo "Starting......: ".date("H:i:s")." Postfix compiling virtual aliase database /etc/postfix/virtual\n";
	if($GLOBALS["DEBUG"]){echo __FUNCTION__." -> {$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/virtual >/dev/null 2>&1\n";}
	shell_exec("{$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/virtual >/dev/null 2>&1");

	$dbmaps=new postfix_extern();
	$contz=$dbmaps->build_extern("master","virtual_alias_maps");
	if($contz<>null){$virtual_alias_maps_cf[]=$contz;}


	if(!is_array($virtual_alias_maps_cf)){
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("virtual_alias_maps","", $GLOBALS["POSTFIX_INSTANCE_ID"]);
		echo "Starting......: ".date("H:i:s")." Postfix No virtual aliases\n";
		return;
	}else{
		echo "Starting......: ".date("H:i:s")." Postfix building virtual_alias_maps\n";
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("virtual_alias_maps","". @implode(",",$virtual_alias_maps_cf)."",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	}

}


function build_aliases_maps(){
	maillings_table();
	$alias_maps_cf=array();
	$alias_database_cf=array();
	$virtual_mailbox_maps_cf=array();
	$hash_mailman=null;
	$main = new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	if(!isset($GLOBALS["alias_maps"])){$GLOBALS["alias_maps"]=array();}
	if(!is_array($GLOBALS["alias_maps"])){$GLOBALS["alias_maps"]=array();}

	if(count($GLOBALS["alias_maps"]==0)){aliases_users();}


	if(isset($GLOBALS["LDAPDBS"]["alias_maps"])){
		if(is_array($GLOBALS["LDAPDBS"]["alias_maps"])){
			if($GLOBALS["VERBOSE"]){"LDAP:: alias_maps=\"".@implode(",",$GLOBALS["LDAPDBS"]["alias_maps"])."\n";}
			$alias_maps_cf=$GLOBALS["LDAPDBS"]["alias_maps"];
		}else{
			if($GLOBALS["DEBUG"]){echo __FUNCTION__."::LDAP:: GLOBALS[LDAPDBS][alias_maps]=not an array\n";}
		}
	}

	if(isset($GLOBALS["LDAPDBS"]["alias_database"])){
		if(is_array($GLOBALS["LDAPDBS"]["alias_database"])){$alias_database_cf=$GLOBALS["LDAPDBS"]["alias_database"];}
	}

	if(isset($GLOBALS["LDAPDBS"]["virtual_mailbox_maps"])){
		if(is_array($GLOBALS["LDAPDBS"]["virtual_mailbox_maps"])){$virtual_mailbox_maps_cf=$GLOBALS["LDAPDBS"]["virtual_mailbox_maps"];}
	}

	$contz=new postfix_extern();
	$contzdata=$contz->build_extern("master", "virtual_mailbox_maps");
	if($contzdata<>null){$virtual_mailbox_maps_cf[]=$contzdata;}

	$alias_maps_cf[]="hash:{$GLOBALS["MAINCF_ROOT"]}/aliases";
	$alias_database_cf[]="hash:{$GLOBALS["MAINCF_ROOT"]}/aliases";

	echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["alias_maps"])." aliase(s)\n";

	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/aliases",implode("\n",$GLOBALS["alias_maps"]));
	shell_exec("{$GLOBALS["postalias"]} -c {$GLOBALS["MAINCF_ROOT"]} hash:{$GLOBALS["MAINCF_ROOT"]}/aliases >/dev/null 2>&1");
	shell_exec("{$GLOBALS["newaliases"]} -f {$GLOBALS["MAINCF_ROOT"]}/aliases");


	$extern=new postfix_extern();
	if($GLOBALS["VERBOSE"]){echo "*** Check external databases rules master/alias_maps ( line:".__LINE__.")";}
	$aliases_extern=$extern->build_extern("master","alias_maps");
	if($aliases_extern<>null){$alias_database_cf[]=$aliases_extern;}else{
		if($GLOBALS["VERBOSE"]){echo "*** Check external databases rules master/alias_maps -> Nothing to add ( line:".__LINE__.")";}
	}


	echo "Starting......: ".date("H:i:s")." Postfix building alias_maps\n";
	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("alias_maps","". @implode(",",$alias_maps_cf)."",$GLOBALS["POSTFIX_INSTANCE_ID"]);


	echo "Starting......: ".date("H:i:s")." Postfix building alias_database\n";
	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("alias_database","". @implode(",",$alias_database_cf)."",$GLOBALS["POSTFIX_INSTANCE_ID"]);

	if(count($virtual_mailbox_maps_cf)>0){
		echo "Starting......: ".date("H:i:s")." Postfix building virtual_mailbox_maps\n";
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("virtual_mailbox_maps","". @implode(",",$virtual_mailbox_maps_cf)."",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	}else{
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("virtual_mailbox_maps","", $GLOBALS["POSTFIX_INSTANCE_ID"]);
	}


}




function aliases():bool{
	$ldap=new clladp();
	if($ldap->EnableManageUsersTroughActiveDirectory){
		aliases_ad();
		return true;
	}
	$filter="(&(objectClass=userAccount)(mailAlias=*))";
	$attrs=array("mail","mailAlias");
	$dn="dc=organizations,$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);

	for($i=0;$i<$hash["count"];$i++){
		$mail=trim($hash[$i]["mail"][0]);

		for($t=0;$t<$hash[$i]["mailalias"]["count"];$t++){
			$hash[$i]["mailalias"][$t]=trim($hash[$i]["mailalias"][$t]);
			if($hash[$i]["mailalias"][$t]==null){continue;}
			$GLOBALS["virtual_alias_maps"]["{$hash[$i]["mailalias"][$t]}"]="{$hash[$i]["mailalias"][$t]}\t$mail";
		}
	}
	return true;
}

function aliases_ad():bool{
	$ldap=new ldapAD();
	$filter="(&(objectClass=user)(userPrincipalName=*))";
	$attrs=array("userPrincipalName","mail");
	$dn="$ldap->suffix";
	$hash=$ldap->Ldap_search($dn,$filter,$attrs);
	for($i=0;$i<$hash["count"];$i++){
		$mail=trim($hash[$i]["mail"][0]);
		$userPrincipalName=trim($hash[$i]["userprincipalname"][0]);
		$GLOBALS["virtual_alias_maps"][$userPrincipalName]="$userPrincipalName\t$mail";

	}
	
	return true;

}


function  sender_dependent_default_transport_maps_build(){
	$q=new mysql();
	$main = new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$sender_dependent_default_transport_maps=array();
	$q=new mysql();
	$sql="SELECT * FROM sender_dependent_relay_host WHERE enabled=1 
			AND `override_transport`=1 
			AND `override_relay`=1 
			AND `hostname`='master' ORDER by zOrders";
	$results = $q->QUERY_SQL($sql,"artica_backup");

	while ($ligne = mysqli_fetch_assoc($results)) {
		$relay=$ligne["relay"];
		$relay_port_text=null;
		$relay_port=$ligne["relay_port"];
		$lookups=$ligne["lookups"];
		$relay_text=$main->RelayToPattern($relay, $relay_port,$lookups);
		if($ligne["directmode"]==1){$relay_text="{$ligne["zmd5"]}:";}
		$domain=$ligne["domain"];

		$sender_dependent_default_transport_maps[$domain]=$relay_text;
	}
	if(is_array($sender_dependent_default_transport_maps)){
		foreach ($sender_dependent_default_transport_maps as $mail=>$value){
			if(strpos("   $mail", "@")==0){$mail="@$mail";}
			$mail=str_replace(".", "\.", $mail);
			$mail=str_replace("*", ".*", $mail);

			$GLOBALS["sender_dependent_default_transport_maps"][]="/$mail/\t$value";
		}
	}
}
function relayhost():bool{
	$main = new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$main->relayhost();
	return true;
}

function sender_dependent_default_transport_maps(){
	sender_dependent_default_transport_maps_build();
	// $unix=new unix();

	if(!isset($GLOBALS["sender_dependent_default_transport_maps"])){
		echo "Starting......: ".date("H:i:s")." 0 sender dependent default transport rule(s)\n";
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("sender_dependent_default_transport_maps","",$GLOBALS["POSTFIX_INSTANCE_ID"]);
		@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/sender_dependent_default_transport_maps","#");
		return;

	}

	if(!is_array($GLOBALS["sender_dependent_default_transport_maps"])){
		echo "Starting......: ".date("H:i:s")." 0 sender dependent default transport rule(s)\n";
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("sender_dependent_default_transport_maps","",$GLOBALS["POSTFIX_INSTANCE_ID"]);
		@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/sender_dependent_default_transport_maps","#");
		return;
	}

	echo "Starting......: ".date("H:i:s")." Postfix ". count($GLOBALS["sender_dependent_default_transport_maps"])." sender dependent default transport rule(s)\n";
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/sender_dependent_default_transport_maps",implode("\n",$GLOBALS["sender_dependent_default_transport_maps"])."\n");


	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("sender_dependent_default_transport_maps",
		"regexp:{$GLOBALS["MAINCF_ROOT"]}/sender_dependent_default_transport_maps",
		$GLOBALS["POSTFIX_INSTANCE_ID"]);


}




function sender_canonical_maps(){
	$instance_id=intval($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$sender_canonical_maps=array();
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$sql="SELECT * FROM smtp_generic_maps WHERE 
                                sender_canonical_maps=1
                                AND instanceid=$instance_id
                                ORDER BY generic_from";
	$results=$q->QUERY_SQL($sql);
	$DDB=array();
	foreach ($results as $index=>$ligne){
		if(trim($ligne["generic_from"])==null){continue;}
		if(trim($ligne["generic_to"])==null){continue;}
		if(isset($DDB[$ligne["generic_from"]])){continue;}
		$DDB[$ligne["generic_from"]]=true;
		$sender_canonical_maps[]="{$ligne["generic_from"]}\t{$ligne["generic_to"]}";
	}

	if(count($sender_canonical_maps)==0){
		echo "Starting......: ".date("H:i:s")." 0 sender retranslation rule(s)\n";
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("sender_canonical_maps","",$GLOBALS["POSTFIX_INSTANCE_ID"]);
		return;
	}



	echo "Starting......: ".date("H:i:s")." Postfix ". count($sender_canonical_maps)." sender retranslation rule(s)\n";
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/sender_canonical",implode("\n",$sender_canonical_maps));
	shell_exec("{$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/sender_canonical >/dev/null 2>&1");

	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("sender_canonical_maps",
		"hash:{$GLOBALS["MAINCF_ROOT"]}/sender_canonical",
		$GLOBALS["POSTFIX_INSTANCE_ID"]);

}

function smtp_generic_maps():bool{
	$instance_id=intval($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$smtp_generic_maps=array();
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$sql="SELECT * FROM `smtp_generic_maps` WHERE 
                    `smtp_generic_maps`=1 
                    AND instanceid=$instance_id 
					ORDER BY generic_from";

	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		if(trim($ligne["generic_from"])==null){continue;}
		if(trim($ligne["generic_to"])==null){continue;}
		$smtp_generic_maps[]="{$ligne["generic_from"]}\t{$ligne["generic_to"]}";
	}

	if(count($smtp_generic_maps)==0){
		echo "Starting......: ".date("H:i:s")." 0 SMTP generic retranslations rule(s)\n";
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("smtp_generic_maps","",$GLOBALS["POSTFIX_INSTANCE_ID"]);
		return true;
	}


	build_progress_smtp_generic_maps(" ". count($smtp_generic_maps)." SMTP generic retranslations rule(s)",40);
	echo "Starting......: ".date("H:i:s")." Postfix ". count($smtp_generic_maps)." SMTP generic retranslations rule(s)\n";
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/smtp_generic_maps",implode("\n",$smtp_generic_maps)."\n");
	build_progress_smtp_generic_maps("{compiling}",50);
	shell_exec("{$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/smtp_generic_maps >/dev/null 2>&1");
	build_progress_smtp_generic_maps("{save}",60);
	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("smtp_generic_maps","hash:{$GLOBALS["MAINCF_ROOT"]}/smtp_generic_maps",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	return true;
}
function recipient_canonical_maps():bool{
	$instance_id=intval($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$recipient_canonical_maps=array();
	$q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
	$sql="SELECT * FROM smtp_generic_maps WHERE recipient_canonical_maps=1 
                                AND instanceid=$instance_id
                                ORDER BY generic_from";
	$results=$q->QUERY_SQL($sql);
	foreach ($results as $index=>$ligne){
		if(trim($ligne["generic_from"])==null){continue;}
		if(trim($ligne["generic_to"])==null){continue;}
		$recipient_canonical_maps[]="{$ligne["generic_from"]}\t{$ligne["generic_to"]}";
	}

	if(count($recipient_canonical_maps)>0){
		echo "Starting......: ".date("H:i:s")." Postfix ". count($recipient_canonical_maps)." recipients retranslation rule(s)\n";
		@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/recipient_canonical",implode("\n",$recipient_canonical_maps));
		shell_exec("{$GLOBALS["postmap"]} hash:{$GLOBALS["MAINCF_ROOT"]}/recipient_canonical >/dev/null 2>&1");
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("recipient_canonical_maps","hash:{$GLOBALS["MAINCF_ROOT"]}/recipient_canonical",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	}else{
		echo "Starting......: ".date("H:i:s")." Postfix 0 retranslation database\n";
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("recipient_canonical_maps","",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	}
	return true;
}
function postmaster():bool{
	$main=new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	build_progress_postmaster("{postmaster}",15);
	

	$luser_relay=trim($main->GET("luser_relay"));
	$PostfixPostmaster=trim($main->GET("PostfixPostmaster"));
	if($PostfixPostmaster==null){$PostfixPostmaster="MAILER-DAEMON";}

	$error_notice_recipient=$main->GET("error_notice_recipient");
	$delay_notice_recipient=$main->GET("delay_notice_recipient");
	$empty_address_recipient=$main->GET("empty_address_recipient");
	$twobounce_notice_recipient=$main->GET("2bounce_notice_recipient");
	$double_bounce_sender=$main->GET("double_bounce_sender");
	$address_verify_sender=$main->GET("address_verify_sender");
	$notify_classes=$main->GET("notify_classes");


	if($notify_classes==null){$notify_classes="resource,software";}
	if($error_notice_recipient==null){$error_notice_recipient=$PostfixPostmaster;}
	if($delay_notice_recipient==null){$delay_notice_recipient=$PostfixPostmaster;}
	if($empty_address_recipient==null){$empty_address_recipient=$PostfixPostmaster;}
	if($twobounce_notice_recipient==null){$twobounce_notice_recipient=$PostfixPostmaster;}
	if($double_bounce_sender==null){$double_bounce_sender="double-bounce";}
	if($address_verify_sender==null){$address_verify_sender="double-bounce";}

	if($luser_relay==null){
		echo "Starting......: ".date("H:i:s")." Postfix no Unknown user recipient set\n";
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("luser_relay","",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	}else {
		echo "Starting......: " . date("H:i:s") . " Postfix Unknown user set to $luser_relay\n";
		$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("luser_relay","$luser_relay",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	}

	build_progress_postmaster("{postmaster}",30);
	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("notify_classes","$notify_classes",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("error_notice_recipient","$error_notice_recipient",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("empty_address_recipient","$empty_address_recipient",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("delay_notice_recipient","$delay_notice_recipient",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("double_bounce_sender","$double_bounce_sender",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("2bounce_notice_recipient","$twobounce_notice_recipient",$GLOBALS["POSTFIX_INSTANCE_ID"]);

	build_progress_postmaster("{postmaster}",50);
	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("double_bounce_sender","$double_bounce_sender",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("address_verify_sender","$address_verify_sender",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	$GLOBALS["CLASS_UNIX"]->POSTCONF_SET("bounce_template_file","{$GLOBALS["MAINCF_ROOT"]}/bounce.template.cf",$GLOBALS["POSTFIX_INSTANCE_ID"]);
    $GLOBALS["CLASS_UNIX"]->POSTCONF_SET("bounce_notice_recipient","$PostfixPostmaster",$GLOBALS["POSTFIX_INSTANCE_ID"]);
	postfix_templates();
	build_progress_postmaster("{postmaster}",90);
	return true;
}

function postfix_templates():bool{
	$main = new maincf_multi($GLOBALS["POSTFIX_INSTANCE_ID"]);
	$mainTemplates = new bounces_templates();
	$conf = array();



	if (is_array($mainTemplates->templates_array)) {
		foreach ($mainTemplates->templates_array as $template=>$nothing){
			$array = unserialize(base64_decode($main->GET_BIGDATA($template)));
			if (!is_array($array)) {
				$array = $mainTemplates->templates_array[$template];
			}
			$tp = explode("\n", $array["Body"]);
			$Body = array();
			foreach ($tp as $line){
				if (trim($line) == null) {continue;}
				$Body[]=$line;
			}
			$conf[]="\n$template = <<EOF";
			$conf[]="Charset: {$array["Charset"]}";
			$conf[]="From:  {$array["From"]}";
			$conf[]="Subject: {$array["Subject"]}";
			$conf[]="";
			$conf[]=@implode("\n",$Body);
			$conf[]="\n";
			$conf[]="EOF\n";

		}
	}

	echo "Starting......: " . date("H:i:s") . " /etc/postfix/bounce.template.cf done\n";
	@file_put_contents("{$GLOBALS["MAINCF_ROOT"]}/bounce.template.cf", @implode("\n",$conf)."\n");
	return true;
}



function DUMP_EXTERNALS_DBS():bool{
	$dbmaps=new postfix_extern();
	foreach ($dbmaps->classTypes as $type=>$numeric){
		echo "DUMP class master:: $type [$numeric]:\n";
		$contz=$dbmaps->build_extern("master",$type);
		echo "Result: `$contz`\n";
	}
	return true;
}
