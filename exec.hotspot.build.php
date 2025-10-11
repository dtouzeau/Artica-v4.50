<?php
session_save_path('/home/squid/hotspot/sessions');
$GLOBALS["CACHEDIR"]="/home/artica/hotspot/caches";
$GLOBALS["HOTSPOT_DEBUG"]=false;
ini_set('session.gc_probability', 1);
ini_set("log_errors", 1);
ini_set("error_log", "/var/log/artica-wifidog.log");
if(isset($_GET["css"])){css();exit;}
//hotspot.php?wifidog-auth=yes&stage=login&ip=192.168.1.18&mac=00:15:5d:01:09:07&token=289a95d50c49c9ce202e4ee349389703&incoming=0&outgoing=0&gw_id=000C291B3AC4
// http://wiki.gergosnet.com/index.php/Installation%2Bclient%2Bwifidog%2Bsur%2BDebian
// iptables -t nat -I WiFiDog_eth0_WIFI2Internet -i eth0 -m mark --mark 0x2 -p tcp --dport 443 -j REDIRECT --to-port 63924
//https://192.168.1.204:9000/portal/?gw_id=000C291B3AC4
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
$GLOBALS["HOTSPOT_DEBUG"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$HotSpotArticaDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HotSpotArticaDebug"));
if($HotSpotArticaDebug==1){$GLOBALS["HOTSPOT_DEBUG"]=true;}


wifidog_load_classes();
if(posix_getuid()==0){
	$GLOBALS["AS_ROOT"]=true;
	include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.settings.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	
}

if($argv[1]=="--templates"){wifidog_templates();exit();}
buildAll();


function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents(PROGRESS_DIR."/squid.webauth.rules.progress", serialize($array));
	@chmod(PROGRESS_DIR."/squid.webauth.rules.progress",0777);

}

function buildAll(){
	$q=new mysql_squid_builder();
	$unix=new unix();
	$qh=new mysql_hotspot();
	$qh->check_hotspot_tables();
	$qz=new mysql();
	
	
	if(!$qz->TABLE_EXISTS("hotspot_admin_mysql", "artica_events")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`hotspot_admin_mysql` (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`content` MEDIUMTEXT NOT NULL ,
			`subject` VARCHAR( 255 ) NOT NULL ,
			`function` VARCHAR( 60 ) NOT NULL ,
			`filename` VARCHAR( 50 ) NOT NULL ,
			`line` INT( 10 ) NOT NULL ,
			`severity` smallint( 1 ) NOT NULL ,
			`TASKID` BIGINT UNSIGNED ,
			PRIMARY KEY (`ID`),
			  KEY `zDate` (`zDate`),
			  KEY `subject` (`subject`),
			  KEY `function` (`function`),
			  KEY `filename` (`filename`),
			  KEY `severity` (`severity`)
			) ENGINE=MYISAM;";
		$qz->QUERY_SQL($sql,"artica_events");
		if(!$qz->ok){echo $qz->mysql_error."\n";}
	}
	
	$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`hotspot_members_meta` ( `uid` VARCHAR( 128 ) NOT NULL , `creationtime` INT UNSIGNED NOT NULL, PRIMARY KEY ( `uid` ) , INDEX ( `creationtime`) )  ENGINE = MYISAM;";
	$q->QUERY_SQL($sql);
	
	if(!$q->FIELD_EXISTS("hotspot_sessions", "firsturl")){
		$q->QUERY_SQL("ALTER TABLE `hotspot_sessions` ADD `firsturl` VARCHAR(256),ADD INDEX ( `firsturl` )");
		if(!$q->ok){echo "MySQL error!!! $q->mysql_error\n";}
	}
	
	$q=new mysql_squid_builder();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT ID FROM webauth_rules WHERE ID=1"));
	if(intval($ligne["ID"])==0){
		$q->QUERY_SQL("INSERT IGNORE INTO `webauth_rules` VALUES (1,'default',1);");
		$q->QUERY_SQL("INSERT IGNORE INTO `webauth_settings` VALUES
		(1,1,'smtp_notifications','yes'),
		(2,1,'smtp_auth_user',''),
		(3,1,'smtp_sender',''),
		(4,1,'tls_enabled','0'),
		(5,1,'smtp_server_name',''),
		(6,1,'smtp_server_port','25'),
		(7,1,'REGISTER_GENERIC_PASSERR','Please, ask the correct password to your local administrator'),
		(8,1,'REGISTER_GENERIC_PASSTXT','Thisisapassword'),
		(9,1,'REGISTER_GENERIC_LABEL','Unlock password'),
		(10,1,'REGISTER_GENERIC_PASSWORD','0'),
		(11,1,'CONFIRM_MESSAGE','Success\nA message as been sent to you.\nPlease check your WebMail system in order to confirm your registration<br>\nYour can surf on internet for %s minutes'),
		(12,1,'REGISTER_MAX_TIME','5'),
		(13,1,'LostPasswordLink','Lost password'),
		(14,1,'PasswordMismatch','Password Mismatch!'),
		(15,1,'ErrorThisAccountExists','This account already exists'),
		(16,1,'REGISTER_MESSAGE','Hi, in order to activate your account on the HotSpot system,\nclick on the link below'),
		(17,1,'REGISTER_SUBJECT','HotSpot account validation'),
		(18,1,'LANDING_PAGE','http://articatech.net'),
		(19,1,'ENABLED_SMTP','0'),
		(20,1,'LIMIT_BY_SIZE','0'),
		(21,1,'LOST_LANDING_PAGE','http://articatech.net'),
		(22,1,'ArticaSplashHotSpotRemoveAccount','20160'),
		(23,1,'ArticaSplashHotSpotCacheAuth','1440'),
		(24,1,'ArticaSplashHotSpotEndTime','10080'),
		(25,1,'TOS_VALUE','0'),
		(26,1,'MACWHITE','0'),
		(27,1,'BOUNCE_AUTH','0'),
		(28,1,'SMS_REGISTER','0'),
		(29,1,'USE_ACTIVEDIRECTORY','0'),
		(30,1,'USE_MYSQL','1'),
		(31,1,'DO_NOT_AUTENTICATE','0'),
		(32,1,'ALLOW_RECOVER_PASS','0'),
		(33,1,'ENABLED_META_LOGIN','0'),
		(34,1,'USE_TERMS','0'),
		(35,1,'ENABLED_AUTO_LOGIN','0'),
		(36,1,'ArticaHotSpotNowPassword','0'),
		(37,1,'ErrorInvalidMail','Invalid eMail address'),
		(38,1,'ErrorThisAccountExists','This account already exists'),
		(39,1,'ErrorInvalidMail','Invalid eMail address'),
		(40,1,'MobileLabel','Mobile'),
		(41,1,'PasswordMismatch','Password Mismatch!'),
		(42,1,'smtp_auth_passwd','');");
	}
	
	
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$sh[]="#!/bin/sh";
	$sh[]="$nohup $php /usr/share/artica-postfix/exec.usrmactranslation.php --hotspot >/var/log/hotspot-reconfigure.log 2>&1 &";
	$sh[]="";
	@file_put_contents("/usr/sbin/notify-hotspot-sessions.sh", @implode("\n", $sh));
	@chmod("/usr/sbin/notify-hotspot-sessions.sh", 0755);
	
	$unix=new unix();
	$rm=$unix->find_program("rm");
	system("$rm -rf /usr/share/hotspot");
	buildPages(0);
	$results=$q->QUERY_SQL("SELECT ID FROM webauth_rules WHERE enabled=1");
	
	echo "Number of rules == ".mysqli_num_rows($results)." rules\n";
	build_progress("{building} Rules (".mysqli_num_rows($results)." rules)",10);
	while ($ligne = mysqli_fetch_assoc($results)) {
		$template_path="/usr/share/hotspot/{$ligne["ID"]}";
		@mkdir("$template_path",0755,true);
		buildPages($ligne["ID"]);
	}
	
	
	build_progress("{building} Rules",30);
	$results=$q->QUERY_SQL("SELECT pattern,ruleid FROM webauth_rules_nets");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ID=$ligne["ruleid"];
		if(!is_dir("/usr/share/hotspot/$ID")){continue;}
		$f[]="{$ligne["pattern"]}:$ID";
		
	}
	
	$brw=array();
	$results=$q->QUERY_SQL("SELECT pattern,ruleid FROM webauth_rules_browsers");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ID=$ligne["ruleid"];
		if(!is_dir("/usr/share/hotspot/$ID")){continue;}
		$brw[]="{$ligne["pattern"]}";
	
	}	
	@unlink("/usr/share/hotspot/$ID/browsers");
	if(count($brw)>0){
		@file_put_contents("/usr/share/hotspot/$ID/browsers", @implode("\n", $brw));
	}
	
	
	buildPages(0);
	$f[]="0.0.0.0/0:0";
	build_progress("{building} Rules",50);
	@file_put_contents("/usr/share/hotspot/net.array", @implode("\n", $f));
	build_progress("{building} Rules",100);
}

function xchar($text){
	$text = htmlentities($text, ENT_NOQUOTES, "UTF-8");
	$text = htmlspecialchars_decode($text);
	return utf8_encode($text);
}



function buildPages($ruleid){
	
	build_progress("{building} Rule:$ruleid logon.html",20);
	wifidog_login(intval($ruleid));
	build_progress("{building} Rule:$ruleid none.html",20);
	none_page($ruleid);
	build_progress("{building} Rule:$ruleid authfailed.html",20);
	error_page($ruleid);
	build_progress("{building} Rule:$ruleid index.css",20);
	wifidog_css($ruleid);
	build_progress("{building} Rule:$ruleid IT Charter",20);
	wifidog_terms($ruleid);
	build_progress("{building} Rule:$ruleid Register",20);
	wifidog_register($ruleid);
	build_progress("{building} Rule:$ruleid AD",20);
	wifidog_activedirectories($ruleid);
	build_progress("{building} Rule:$ruleid SMS",20);
	wifidog_register_sms($ruleid);	
	build_progress("{building} Rule:$ruleid SMS",20);
	wifidog_register_sms($ruleid,true);
	build_progress("{building} Rule:$ruleid Voucher",20);	
	wifidog_voucher($ruleid);
	
	build_progress("{building} All paremeters",20);
	
	$q=new mysql_squid_builder();
	
	$results=$q->QUERY_SQL("SELECT MasterKey,MasterValue FROM webauth_settings WHERE ruleid='$ruleid'");
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ligne["MasterKey"]=trim($ligne["MasterKey"]);
		if($ligne["MasterKey"]==null){continue;}
		$filename="/usr/share/hotspot/$ruleid/{$ligne["MasterKey"]}";
		echo $filename."\n";
		
		if($ligne["MasterKey"]=="REGISTER_MESSAGE"){$ligne["MasterValue"]=xchar($ligne["MasterValue"]);}
		if($ligne["MasterKey"]=="REGISTER_SUBJECT"){$ligne["MasterValue"]=xchar($ligne["MasterValue"]);}
		
		@file_put_contents($filename, $ligne["MasterValue"]);
		
	}
	

	
}

function none_page($ruleid){
	$page=CurrentPageName();
	$tpl=new templates();
	$proto="http";
	$wifidog_templates=new wifidog_templates($ruleid);
	$sock=new wifidog_settings($ruleid);
	$LOST_LANDING_PAGE=trim($sock->GET_INFO("LOST_LANDING_PAGE"));
	if($LOST_LANDING_PAGE==null){$LOST_LANDING_PAGE="http://articatech.net";}
	$template_path="/usr/share/hotspot/$ruleid";
	
	
	
	$text_form="
	<div class=title2>$wifidog_templates->ArticaSplashHotSpotRedirectText:</div>
	<p>$LOST_LANDING_PAGE</p>
	<form>		
	<center>
			<div style='font-size:32px'><center>$LOST_LANDING_PAGE</center></div>
			<img src='img?wait_verybig_mini_red.gif'>
	</center>
	</form>
	
	
	
	";
	if(!preg_match("#^http#", $LOST_LANDING_PAGE)){$LOST_LANDING_PAGE="http://$LOST_LANDING_PAGE";}
	$html=BuildFullPage($text_form,null,"<META http-equiv=\"refresh\" content=\"1; URL=$LOST_LANDING_PAGE\">",$ruleid);
	@file_put_contents("$template_path/none.html", $html);
	
	
	
}
function error_page($ruleid){
	$page=CurrentPageName();
	$tpl=new templates();
	$proto="http";
	$wifidog_templates=new wifidog_templates($ruleid);
	$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
	$LOST_LANDING_PAGE=trim($sock->GET_INFO("LOST_LANDING_PAGE"));
	if($LOST_LANDING_PAGE==null){$LOST_LANDING_PAGE="http://articatech.net";}
	$template_path="/usr/share/hotspot/$ruleid";



	$text_form="
	<div class=title2>$wifidog_templates->authentication_failed</div>
	<form>
	<center>
	<div style='font-size:32px'><center>&nbsp;</center></div>
	<img src='img?wait_verybig_mini_red.gif'>
	</center>
	</form>
	";
	$html=BuildFullPage($text_form,null,"<META http-equiv=\"refresh\" content=\"2; URL=%URL%\">",$ruleid);
	@file_put_contents("$template_path/authfailed.html", $html);



}




function wifidog_templates(){
	@file_put_contents("/usr/local/etc/wifidog-msg.html",BuildFullPage(null,"<h2>\$title</h2><p>\$message</p>"));
	
}



function  wifidog_terms($ruleid){
	$tpl=new templates();
	$wifidog_templates=new wifidog_templates($ruleid);
	$template_path="/usr/share/hotspot/$ruleid";
	
	$t=time();
	$accept=$wifidog_templates->AcceptButton;
	$wifidog_templates->TERMS_CONDITIONS=str_replace("\\n", "\n", $wifidog_templates->TERMS_CONDITIONS);
	$wifidog_templates->TERMS_CONDITIONS=str_replace("\\\"", "\"", $wifidog_templates->TERMS_CONDITIONS);
	$wifidog_templates->TERMS_CONDITIONS=utf8_encode($wifidog_templates->TERMS_CONDITIONS);
	
	$f[]="<p>".$wifidog_templates->char($wifidog_templates->TERMS_EXPLAIN)."</p>";
	$f[]="<form id='wifidogform$t' action=\"login\" method=\"post\">";
	$f[]="<textarea readonly='yes' style='width:97%;height:450px'>".$wifidog_templates->char($wifidog_templates->TERMS_CONDITIONS)."</textarea>";
	$f[]="";
	$f[]="<input type='hidden' name='wifidog-terms' value='yes'>";
	$f[]="<input type='hidden' name='ruleid' value='$ruleid'>";
	$f[]="%HIDDENFIELDS%";
	
	
	
	
	$f[]="<p class=ButtonCell style='text-align:right'><a data-loading-text=\"Chargement...\"
	style=\"text-transform:capitalize\"
	class=\"Button2014 Button2014-success Button2014-lg\"
	id=\"fb92ae5e1f7bbea3b5044cbcdd40f088\"
	onclick=\"javascript:document.forms['wifidogform$t'].submit();\"
	href=\"javascript:Blurz()\">&laquo;&nbsp;$accept&nbsp;&raquo;</a></p>";
	
	$f[]="</form></div>";
	$text_form=@implode("\n", $f);
	$html=BuildFullPage($text_form,null,null,$ruleid);
	@file_put_contents("$template_path/terms.html", $html);
}

function wifidog_css($ruleid){
	$ruleid=intval($ruleid);
	$template_path="/usr/share/hotspot/$ruleid";
	$wifidog_templates=new wifidog_templates($ruleid);
	$data= $wifidog_templates->css();
	echo "[20]: {building} Rule: $template_path/index.css\n";
	@file_put_contents("$template_path/index.css", $data);
}

function wifidog_activedirectories($ruleid){
	$q=new mysql_squid_builder();
	$f=array();
	$sql="SELECT * FROM hotspot_activedirectory WHERE ruleid=$ruleid AND enabled=1";
	$results=$q->QUERY_SQL($sql);
	$template_path="/usr/share/hotspot/$ruleid";
	while ($ligne = mysqli_fetch_assoc($results)) {
		$hostname=$ligne["hostname"];
		$groups=explode("\n",$ligne["groups"]);
		$c=0;
		while (list ($index,$group) = each ($groups)){
			$group=trim($group);
			if($group==null){continue;}
			$f[]="$hostname:$group";
			$c++;
		}
		if($c==0){$f[]="$hostname:*";}
			
	}
	@file_put_contents("$template_path/ActiveDirectories.array", @implode("\n", $f));
	
}

function ALL_LOGINS($ruleid,$t,$default=null){
	$wifidog_rule=new wifidog_settings($ruleid);
	
	$ALL_LOGINS=intval($wifidog_rule->GET_INFO("ALL_LOGINS"));
	$SMS_REGISTER=intval($wifidog_rule->GET_INFO("SMS_REGISTER"));
	$ENABLED_AUTO_LOGIN=intval($wifidog_rule->GET_INFO("ENABLED_AUTO_LOGIN"));
	$ENABLED_SMTP=intval($wifidog_rule->GET_INFO("ENABLED_SMTP"));
	$ALLOW_RECOVER_PASS=intval($wifidog_rule->GET_INFO("ALLOW_RECOVER_PASS"));
	$USE_VOUCHER=intval($wifidog_rule->GET_INFO("USE_VOUCHER"));
	$USE_ACTIVEDIRECTORY=intval($wifidog_rule->GET_INFO("USE_ACTIVEDIRECTORY"));

	
	
	$wifidog_templates=new wifidog_templates($ruleid);
	if($ALL_LOGINS==0){return null;}
	$ALL_METHODS_LABELS=explode("\n",$wifidog_templates->ALL_METHODS_LABELS);
		while (list ($num, $ligne) = each ($ALL_METHODS_LABELS) ){
			$ligne=trim($ligne);
			if($ligne==null){continue;}
			if(!preg_match("#^([A-Z]+)->(.*)#", $ligne,$re)){continue;}
			echo "$ruleid) FOUND LABEL {$re[1]} to {$re[2]}\n";
			$zLABELS[$re[1]]=$re[2];
		}
		
		if(!isset($zLABELS["VOUCHER"])){$zLABELS["VOUCHER"]="Use a Voucher";}
		
		$ALL_LOGINZ[null]=$zLABELS["NONE"];
		$ALL_LOGINZ["LOGIN"]=$zLABELS["LOGIN"];
		if($USE_VOUCHER==1){$ALL_LOGINZ["VOUCHER"]=$zLABELS["VOUCHER"];}
		if($SMS_REGISTER==1){$ALL_LOGINZ["SMS"]=$zLABELS["SMS"];}
		if($ENABLED_AUTO_LOGIN==1){$ALL_LOGINZ["REGISTER"]=$zLABELS["REGISTER"];}
		if($ENABLED_SMTP==1){if($ALLOW_RECOVER_PASS==1){$ALL_LOGINZ["RECOVER"]=$zLABELS["RECOVER"];}}
		
	
		$f[]="<tr>";
		$f[]="<td class=legend>{$zLABELS["LABEL"]}:</td>";
		$f[]="<td>";
		$f[]=Field_array_Hash($ALL_LOGINZ, "CHOOSE_METHOD-$t",$default,"ChooseMethod$t()",'',0,"$wifidog_templates->FieldsStyle;font-size:{$wifidog_templates->FontSize}px !important;width:100% !important",false);
		$f[]="</td>";
		$f[]="</tr>";
		$f[]="<script>";
		$f[]="
function ChooseMethod$t(){
	var zMethod=document.getElementById('CHOOSE_METHOD-$t').value;
	if(zMethod=='RECOVER'){
		document.location.href=\"/wifidog-recover=yes&email=%MAIL%&%URILINK%&dropdown=1&method=\"+zMethod;
		return;
	}
	if(zMethod=='REGISTER'){
		document.location.href=\"/register?%URILINK%&dropdown=1&method=\"+zMethod;
		return;
	}
	
	if(zMethod=='SMS'){
		document.location.href=\"/sms-login?%URILINK%&dropdown=1&method=\"+zMethod;
		return;
	}
	if(zMethod=='LOGIN'){
		document.location.href=\"/login?%URILINK%&dropdown=1&method=\"+zMethod;
		return;
	}	
	if(zMethod=='VOUCHER'){
		document.location.href=\"/voucher-auth?%URILINK%&dropdown=1&method=\"+zMethod;
		return;
	}		
	
}
</script>";
	
		return @implode("\n", $f);
	
}


function  wifidog_login($ruleid=0){
	
	
	$wifidog_rule=new wifidog_settings($ruleid);
	$ArticaHotSpotNowPassword=intval($wifidog_rule->GET_INFO("ArticaHotSpotNowPassword"));
	
	$ENABLED_SMTP=intval($wifidog_rule->GET_INFO("ENABLED_SMTP"));
	$ENABLED_AUTO_LOGIN=intval($wifidog_rule->GET_INFO("ENABLED_AUTO_LOGIN"));
	$USE_TERMS=intval($wifidog_rule->GET_INFO("USE_TERMS"));
	$ALLOW_RECOVER_PASS=intval($wifidog_rule->GET_INFO("ALLOW_RECOVER_PASS"));
	$DO_NOT_AUTENTICATE=intval($wifidog_rule->GET_INFO("DO_NOT_AUTENTICATE"));
	$SMS_REGISTER=intval($wifidog_rule->GET_INFO("SMS_REGISTER"));
	$BOUNCE_AUTH=intval($wifidog_rule->GET_INFO("BOUNCE_AUTH"));
	$USE_MYSQL=intval($wifidog_rule->GET_INFO("USE_MYSQL"));
	$USE_ACTIVEDIRECTORY=intval($wifidog_rule->GET_INFO("USE_ACTIVEDIRECTORY"));
	$DO_NOT_AUTENTICATE=intval($wifidog_rule->GET_INFO("DO_NOT_AUTENTICATE"));
	$ALL_LOGINS=intval($wifidog_rule->GET_INFO("ALL_LOGINS"));
	
	
	
	if($USE_ACTIVEDIRECTORY==0){
		if($USE_MYSQL==0){$USE_MYSQL=1;}
	}
	
	$template_path="/usr/share/hotspot/$ruleid";
	@mkdir("$template_path",0755,true);
	@file_put_contents("$template_path/BOUNCE_AUTH", $BOUNCE_AUTH);
	@file_put_contents("$template_path/USE_TERMS", $USE_TERMS);
	@file_put_contents("$template_path/SMS_REGISTER", $SMS_REGISTER);
	@file_put_contents("$template_path/DO_NOT_AUTENTICATE", $DO_NOT_AUTENTICATE);
	@file_put_contents("$template_path/ENABLED_SMTP", $ENABLED_SMTP);
	
	@file_put_contents("$template_path/ArticaHotSpotNowPassword", $ArticaHotSpotNowPassword);
	@file_put_contents("$template_path/USE_MYSQL", $USE_MYSQL);
	@file_put_contents("$template_path/USE_ACTIVEDIRECTORY", $USE_ACTIVEDIRECTORY);
	echo "Use Active Directory for rule $ruleid: $USE_ACTIVEDIRECTORY\n";
	@file_put_contents("$template_path/DO_NOT_AUTENTICATE", $DO_NOT_AUTENTICATE);
	
	$wifidog_templates=new wifidog_templates($ruleid);
	
	$page=CurrentPageName();
	$tpl=new templates();
	$username=$wifidog_templates->LabelUsername;
	$domain_account=$wifidog_templates->DomainAccount;
	$password=$wifidog_templates->LabelPassword;
	$lost_password_text=$wifidog_templates->LostPasswordLink;
	$WelcomeMessageActiveDirectory=null;
	
	if($ENABLED_AUTO_LOGIN==0){
		if($USE_ACTIVEDIRECTORY==1){
			$username=$wifidog_templates->DomainAccount;
			$RegisterTitle2="$wifidog_templates->LoginTitle";
			$WelcomeMessageActiveDirectory="<br>".$wifidog_templates->WelcomeMessageActiveDirectory;
		}
	}
	
	$t=time();
	$please_sign_in=$tpl->_ENGINE_parse_body("{please_sign_in}");
	$page=CurrentPageName();
	
	if($ENABLED_AUTO_LOGIN==0){
		if($USE_ACTIVEDIRECTORY==1){
			$f[]="<div class=title2>".$wifidog_templates->char($RegisterTitle2)."</div>";
		}
	}
	
	$f[]="<p>$wifidog_templates->WelcomeMessage{$WelcomeMessageActiveDirectory}</p>";
	$f[]="<div id='content'>";
	$f[]="";
	$f[]="<form id='wifidogform' action=\"auth\" method=\"post\">";
	$f[]="<input type=\"hidden\" name=\"ruleid\" id=\"ruleid\" value='$ruleid'>";
	$f[]="%HIDDENFIELDS%";
	
	if($wifidog_templates->FORM_HEAD<>null){
		$f[]="<p>{$wifidog_templates->FORM_HEAD}</p>";
	}
	$f[]="<table style='width:100%'>";
	$f[]=ALL_LOGINS($ruleid,$t,"LOGIN");

	if($ENABLED_AUTO_LOGIN==1){
		if($USE_ACTIVEDIRECTORY==1){$username=$username."/$domain_account";}
		
	}
	
	
	$f[]="<tr>";
	$f[]="<td class=legend>$username:</td>";
	$f[]="<td>
	<input type=\"text\" 
		name=\"username\" 
		id=\"username\"
		value=\"%USERNAME%\" 
		onfocus=\"this.setAttribute('class','active');RemoveLogonCSS();\" 
		onblur=\"this.removeAttribute('class');\" 
		OnKeyPress=\"javascript:SendLogon$t(event)\">";
	$f[]="</td>";
	$f[]="</tr>";
	
	
	if($ArticaHotSpotNowPassword==0){
	
	$f[]="<tr>";
	$f[]="<td class=legend>$password:</td>";
	$f[]="<td><input type=\"password\" name=\"password\" value=\"\" id=\"password\" onfocus=\"this.setAttribute('class','active');RemoveLogonCSS();\" 
				onblur=\"this.removeAttribute('class');\" OnKeyPress=\"javascript:SendLogon$t(event)\">";
	$f[]="</td>";
	$f[]="</tr>";
	}else{
		$f[]="<input type=\"hidden\" name=\"password\" id=\"password\" value=''>";
	}
	

	
	
	$f[]="<tr><td colspan=2>&nbsp;</td></tr>";
	$f[]="<tr><td colspan=2 align='right' class=ButtonCell>";
	
	if($ALL_LOGINS==0){
		if($ENABLED_AUTO_LOGIN==1){
			$f[]="<a data-loading-text=\"Chargement...\"
			style=\"text-transform:capitalize\"
			class=\"Button2014 Button2014-success Button2014-lg\"
			id=\"fb92ae5e1f7bbea3b5044cbcdd40f088\"
			href=\"register?%URILINK%\">&laquo;&nbsp;$wifidog_templates->RegisterTitle&nbsp;&raquo;</a>";
		}
	}
	
	
	
	$f[]="<a data-loading-text=\"Chargement...\" 
			style=\"text-transform:capitalize\" 
			class=\"Button2014 Button2014-success Button2014-lg\" 
			id=\"fb92ae5e1f7bbea3b5044cbcdd40f088\" 
			onclick=\"javascript:document.forms['wifidogform'].submit();\" 
			href=\"javascript:Blurz()\">&laquo;&nbsp;".$wifidog_templates->char($wifidog_templates->ConnectionButton)."&nbsp;&raquo;</a>";
	
	$f[]="</td>
	</tr>";
	if($ENABLED_SMTP==1){
		if($ALLOW_RECOVER_PASS==1){
			if($ArticaHotSpotNowPassword==0){
				$f[]="<tr><td class=legend colspan=2>";
				$f[]="<a href=\"wifidog-recover=yes&email=%MAIL%&%URILINK%\">$lost_password_text</a></div>";
				$f[]="</td></tr>";
			}
		}
	}
	
	$f[]="</table>";
	$f[]="			</form>	";
	
	
	$f[]="</div>
<script>
$('input').keypress(function(e){
    if (e.which == 13) {
		document.forms['wifidogform'].submit();
	 }
});	
	
	
function SendLogon$t(e){
	if(!checkEnter(e)){return;}
		document.forms['wifidogform'].submit();
	}
	

</script>
	\n";
	
	
	$text_form=@implode("\n", $f);
	$page=BuildFullPage($text_form,null,null,$ruleid);
	@file_put_contents("$template_path/login.html", $page);
	
}


function  wifidog_voucher($ruleid=0){


	$wifidog_rule=new wifidog_settings($ruleid);
	$ArticaHotSpotNowPassword=intval($wifidog_rule->GET_INFO("ArticaHotSpotNowPassword"));

	$ENABLED_SMTP=intval($wifidog_rule->GET_INFO("ENABLED_SMTP"));
	$ENABLED_AUTO_LOGIN=intval($wifidog_rule->GET_INFO("ENABLED_AUTO_LOGIN"));
	$USE_TERMS=intval($wifidog_rule->GET_INFO("USE_TERMS"));
	$ALLOW_RECOVER_PASS=intval($wifidog_rule->GET_INFO("ALLOW_RECOVER_PASS"));
	$DO_NOT_AUTENTICATE=intval($wifidog_rule->GET_INFO("DO_NOT_AUTENTICATE"));
	$SMS_REGISTER=intval($wifidog_rule->GET_INFO("SMS_REGISTER"));
	$BOUNCE_AUTH=intval($wifidog_rule->GET_INFO("BOUNCE_AUTH"));
	$USE_MYSQL=intval($wifidog_rule->GET_INFO("USE_MYSQL"));
	$USE_ACTIVEDIRECTORY=intval($wifidog_rule->GET_INFO("USE_ACTIVEDIRECTORY"));
	$DO_NOT_AUTENTICATE=intval($wifidog_rule->GET_INFO("DO_NOT_AUTENTICATE"));
	$ALL_LOGINS=intval($wifidog_rule->GET_INFO("ALL_LOGINS"));

	if($USE_ACTIVEDIRECTORY==0){
		if($USE_MYSQL==0){$USE_MYSQL=1;}
	}

	$template_path="/usr/share/hotspot/$ruleid";
	@mkdir("$template_path",0755,true);
	@file_put_contents("$template_path/BOUNCE_AUTH", $BOUNCE_AUTH);
	@file_put_contents("$template_path/USE_TERMS", $USE_TERMS);
	@file_put_contents("$template_path/SMS_REGISTER", $SMS_REGISTER);
	@file_put_contents("$template_path/DO_NOT_AUTENTICATE", $DO_NOT_AUTENTICATE);
	@file_put_contents("$template_path/ENABLED_SMTP", $ENABLED_SMTP);

	@file_put_contents("$template_path/ArticaHotSpotNowPassword", $ArticaHotSpotNowPassword);
	@file_put_contents("$template_path/USE_MYSQL", $USE_MYSQL);
	@file_put_contents("$template_path/USE_ACTIVEDIRECTORY", $USE_ACTIVEDIRECTORY);
	@file_put_contents("$template_path/DO_NOT_AUTENTICATE", $DO_NOT_AUTENTICATE);

	$wifidog_templates=new wifidog_templates($ruleid);

	$page=CurrentPageName();
	$tpl=new templates();
	$username=$wifidog_templates->LabelVoucher;
	if($username==null){$username="Your Voucher";}

	$t=time();
	$please_sign_in=$tpl->_ENGINE_parse_body("{please_sign_in}");
	$page=CurrentPageName();
	$f[]="<p>$wifidog_templates->WelcomeMessage</p>";
	$f[]="<div id='content'>";
	$f[]="";
	$f[]="<form id='wifidogform' action=\"auth\" method=\"post\">";
	$f[]="<input type=\"hidden\" name=\"ruleid\" id=\"ruleid\" value='$ruleid'>";
	$f[]="<input type=\"hidden\" name=\"voucher\" id=\"voucher\" value='1'>";
	$f[]="%HIDDENFIELDS%";

	if($wifidog_templates->FORM_HEAD<>null){
		$f[]="<p>{$wifidog_templates->FORM_HEAD}</p>";
	}
	$f[]="<table style='width:100%'>";
	$f[]=ALL_LOGINS($ruleid,$t,"LOGIN");




	$f[]="<tr>";
	$f[]="<td class=legend>$username:</td>";
	$f[]="<td>
	<input type=\"text\"
	name=\"username\"
	id=\"username\"
	value=\"%USERNAME%\"
	onfocus=\"this.setAttribute('class','active');RemoveLogonCSS();\"
	onblur=\"this.removeAttribute('class');\"
	OnKeyPress=\"javascript:SendLogon$t(event)\">";
	$f[]="</td>";
	$f[]="</tr>";





	$f[]="<tr><td colspan=2>&nbsp;</td></tr>";
	$f[]="<tr><td colspan=2 align='right' class=ButtonCell>";



	$f[]="<a data-loading-text=\"Chargement...\"
			style=\"text-transform:capitalize\"
			class=\"Button2014 Button2014-success Button2014-lg\"
			id=\"fb92ae5e1f7bbea3b5044cbcdd40f088\"
			onclick=\"javascript:document.forms['wifidogform'].submit();\"
			href=\"javascript:Blurz()\">&laquo;&nbsp;".$wifidog_templates->char($wifidog_templates->ConnectionButton)."&nbsp;&raquo;</a>";

			$f[]="</td>
			</tr>";


		$f[]="</table>";
	$f[]="			</form>	";


			$f[]="</div>
<script>
$('input').keypress(function(e){
if (e.which == 13) {
document.forms['wifidogform'].submit();
	}
	});


	function SendLogon$t(e){
	if(!checkEnter(e)){return;}
	document.forms['wifidogform'].submit();
	}


	</script>
	\n";


	$text_form=@implode("\n", $f);
	$page=BuildFullPage($text_form,null,null,$ruleid);
	@file_put_contents("$template_path/voucher.html", $page);

}





function wifidog_redirect_uri(){
	
	if(isset($_SESSION["HOTSPOT_REDIRECT_URL"])){$url=$_SESSION["HOTSPOT_REDIRECT_URL"];}
	
	if($url==null){
		if(!isset($_SESSION["WIFIDOG_RULES"])){$wifidog_templates=new wifidog_rules(); $_SESSION["WIFIDOG_RULES"]=$wifidog_templates->ruleid; }
		$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
		$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
		$LOST_LANDING_PAGE=trim($sock->GET_INFO("LOST_LANDING_PAGE"));
		$LANDING_PAGE=trim($sock->GET_INFO("LANDING_PAGE"));
		if($LOST_LANDING_PAGE==null){$LOST_LANDING_PAGE="http://articatech.net";}
		if($LANDING_PAGE<>null){return $LANDING_PAGE;}
		return $LOST_LANDING_PAGE;
	}
	
	
	return $url;
}




function wifidog_portal(){
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Starting wifidog_portal()");}
	session_start();
	$tpl=new templates();
	$url=wifidog_redirect_uri();
	
	$continue_to_internet=$tpl->_ENGINE_parse_body("{continue_to_internet}");
	$idbt=md5(time());
	$ssl_button=null;
	$explain2=null;
	$parse=parse_url($url);
	$hostname=$parse["host"];
	
	
	if(!isset($_SESSION["WIFIDOG_RULES"])){
		$wifidog_templates=new wifidog_rules();
		$_SESSION["WIFIDOG_RULES"]=$wifidog_templates->ruleid;
	}
	$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
	$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
	$LOST_LANDING_PAGE=trim($sock->GET_INFO("LOST_LANDING_PAGE"));
	$LANDING_PAGE=trim($sock->GET_INFO("LANDING_PAGE"));
	if($LOST_LANDING_PAGE==null){$LOST_LANDING_PAGE="http://articatech.net";}
	
	
	$wifidog_build_uri=wifidog_build_uri();
	
	
	$sock=new wifidog_settings($_SESSION["WIFIDOG_RULES"]);
	$wifidog_templates = new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
	
	if(!preg_match("#http#", $url)){$url="http://$url";}
	$continue_button="<a data-loading-text=\"Chargement...\" 
					style=\"text-transform:capitalize\" 
					class=\"Button2014 Button2014-success Button2014-lg\" 
					id=\"$idbt\" 
					href=\"$url\">&laquo;&nbsp;$continue_to_internet&nbsp;&raquo;</a>";
	
	

	
	if($GLOBALS["HOTSPOT_DEBUG"]){
		while (list ($num, $ligne) = each ($_SESSION) ){
			if(preg_match("#HOTSPOT_#", $num)){
			wifidog_logs("wifidog_portal:: SESSION OF $num = $ligne".__LINE__);
			}
		}
		
	}
	wifidog_logs("LOST_LANDING_PAGE = $LOST_LANDING_PAGE",__FUNCTION__,__LINE__);
	wifidog_logs("LANDING_PAGE      = $LANDING_PAGE",__FUNCTION__,__LINE__);
	wifidog_logs("Rule ID	        = {$_SESSION["WIFIDOG_RULES"]}",__FUNCTION__,__LINE__);
	
	
	
	if(isset($_SESSION["HOTSPOT_AUTO_REGISTER"])){
		$tpl=new templates();
		
		$REGISTER_MAX_TIME=$sock->GET_INFO("REGISTER_MAX_TIME");
		
		$text_form=$wifidog_templates->CONFIRM_MESSAGE;
		$text_form=str_replace("%s", $REGISTER_MAX_TIME, $text_form);
		unset($_SESSION["HOTSPOT_AUTO_REGISTER"]);
		unset($_SESSION["HOTSPOT_AUTO_RECOVER"]);
		
		
		$html="<form>
		$text_form
		$explain2
		<div style='width:100%;text-align:right'>
		<table style='width:100%'><tr><td>$ssl_button</td></tr><tr><td>$continue_button</td></tr></table>
		</form>";
		
		echo BuildFullPage($html,null);
		return;
		
		
	}
	
	
	if(isset($_SESSION["HOTSPOT_AUTO_RECOVER"])){
		$tpl=new templates();
		$ArticaHotSpotSMTP=SMTP_SETTINGS();
		$REGISTER_MAX_TIME=$ArticaHotSpotSMTP["REGISTER_MAX_TIME"];
		$continue_to_internet=$tpl->_ENGINE_parse_body("{continue_to_internet}");
		$text_form=$ArticaHotSpotSMTP["RECOVER_MESSAGE"];
		$text_form=str_replace("%s", $REGISTER_MAX_TIME, $text_form);
		unset($_SESSION["HOTSPOT_AUTO_RECOVER"]);
		$parse=parse_url($url);
		$hostname=$parse["host"];
		
		$html="<form>
			$text_form
			$explain2
			<div style='width:100%;text-align:right'>
				<table style='width:100%'><tr><td>$ssl_button</td></tr><tr><td>$continue_button</td></tr></table>
		</div>
		</form>";
		
		echo BuildFullPage($html,null);
		return;
		
	}
	
		
	wifidog_logs("wifidog_portal:: buiding redirect to $url in line:".__LINE__);
	$tpl=new templates();
	$sock=new sockets();
	$text_redirecting=$sock->GET_INFO("ArticaSplashHotSpotRedirectText");
	if($text_redirecting==null){$text_redirecting=$tpl->_ENGINE_parse_body("{please_wait_redirecting_to}:");}
		
	
	$parse=parse_url($url);
	$host=$parse["host"];
	
	$text_form="
	<form>
	<center>
		<div style='font-size:18px'><center>$text_redirecting<br>$host</center></div>
		<img src='hotspot.php?imgload=wait_verybig_mini_red.gif'>
	</center>
	</form>";
		
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Redirect Client {$_SESSION["HOTSPOT_REDIRECT_MAC"]} to $url");}
	echo BuildFullPage($text_form,null,"<META http-equiv=\"refresh\" content=\"3; URL=$url\">");
		
	
}

function events($severity,$subject,$content){
	// 0 -> RED, 1 -> WARN, 2 -> INFO
	$file=basename(__FILE__);
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
			}
		}
			
	wifidog_logs($subject);
	$zdate=date("Y-m-d H:i:s");
	$q=new mysql();
	
	if(!$q->TABLE_EXISTS("hotspot_admin_mysql", "artica_events")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`hotspot_admin_mysql` (
			`ID` int(11) NOT NULL AUTO_INCREMENT,
			`zDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`content` MEDIUMTEXT NOT NULL ,
			`subject` VARCHAR( 255 ) NOT NULL ,
			`function` VARCHAR( 60 ) NOT NULL ,
			`filename` VARCHAR( 50 ) NOT NULL ,
			`line` INT( 10 ) NOT NULL ,
			`severity` smallint( 1 ) NOT NULL ,
			`TASKID` BIGINT UNSIGNED ,
			PRIMARY KEY (`ID`),
			  KEY `zDate` (`zDate`),
			  KEY `subject` (`subject`),
			  KEY `function` (`function`),
			  KEY `filename` (`filename`),
			  KEY `severity` (`severity`)
			) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}
	$subject=mysql_escape_string2($subject);
	$content=mysql_escape_string2($content);
	$q->QUERY_SQL("INSERT IGNORE INTO `hotspot_admin_mysql`
			(`zDate`,`content`,`subject`,`function`,`filename`,`line`,`severity`) VALUES
			('$zdate','$content','$subject','$function','$file','$line','$severity')","artica_events");
}



















function wifidog_recover($error=null){
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Starting wifidog_recover($error)");}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$email=$tpl->_ENGINE_parse_body("{email}");
	$password=$tpl->_ENGINE_parse_body("{password}");
	$submit=$tpl->_ENGINE_parse_body("{submit}");
	
	$confirm=$tpl->_ENGINE_parse_body("{confirm}");
	$password_mismatch=$tpl->javascript_parse_text("{password_mismatch}");
	$redirecturi=$_GET["url"];
	$t=time();
	
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	
	$wifidog_templates=new wifidog_templates($_SESSION["WIFIDOG_RULES"]);
	
	$cancel=$tpl->_ENGINE_parse_body("{cancel}");
	session_start();
	unset($_SESSION["HOTSPOT_AUTO_RECOVER"]);
	unset($_SESSION["HOTSPOT_AUTO_REGISTER"]);
	$form_head=null;
	if($wifidog_templates->FORM_HEAD<>null){
		$form_head="<p>{$wifidog_templates->FORM_HEAD}</p>";
	}

	$html="
	<div class=title2>$wifidog_templates->LostPasswordLink</div>
	<div style='width:98%' class=form id='form-$t'>
	<form name='register-$t' method='post' action='$page' class='form-horizontal' style='padding:left:15px'>
	$form_head
	<input type='hidden' id='register-recover' name='register-recover' value='yes'>
	$HiddenFields
	<table style='width:100%'>
	<tr>
		<td class=legend>$email:</td>
		<td><input style='width:80%' type=\"text\" placeholder=\"$email\" id=\"email\" name=\"email\" value='{$_REQUEST["email"]}'></td>
	</tr>
	<tr><td colspan=2>&nbsp;</td></tr>
	<tr><td colspan=2 align='right' class=ButtonCell>
	
	
		<a data-loading-text=\"Chargement...\"
		style=\"text-transform:capitalize\"
		class=\"Button2014 Button2014-success Button2014-lg\"
		id=\"".time()."\"
		href=\"/login&$uriext\">&laquo;&nbsp;$cancel&nbsp;&raquo;</a>
		&nbsp;&nbsp;
		<a data-loading-text=\"Chargement...\"
		style=\"text-transform:capitalize\"
		class=\"Button2014 Button2014-success Button2014-lg\"
		id=\"".time()."\"
		onclick=\"javascript:document.forms['register-$t'].submit();document.getElementById('form-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';\"
		href=\"javascript:Blurz()\">&laquo;&nbsp;$submit&nbsp;&raquo;</a>
		</td>
	</tr>
	</table>
	
		</form>
		</div>
		<script>
		$('.input-block-level').keypress(function (e) {
	
		if (e.which == 13) {
		document.forms['register-$t'].submit();
		document.getElementById('form-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';
	}
	
	});
	
	</script>
	
	
	
	";
	echo BuildFullPage($html,$error);	
	
}
function wifidog_load_classes(){
	$dirname=dirname(__FILE__)."/";
	include_once($dirname.'ressources/class.templates.inc');
	include_once($dirname.'ressources/class.ldap.inc');
	include_once($dirname.'ressources/class.tcpip.inc');
	include_once($dirname.'ressources/class.system.nics.inc');
	include_once($dirname.'ressources/class.wifidog.rules.inc');
	include_once($dirname.'ressources/externals/adLDAP/adLDAP.php');
	include_once(dirname(__FILE__).'/ressources/smtp/class.smtp.loader.inc');
	include_once(dirname(__FILE__).'/ressources/class.webauth-msmtp.inc');
	include_once(dirname(__FILE__).'/ressources/class.wifidog.settings.inc');
	include_once(dirname(__FILE__).'/ressources/class.wifidog.tools.inc');
	include_once(dirname(__FILE__).'/ressources/class.wifidog.rules.inc');
}






function wifidog_password($error=null){
	$sessionkey=null;
	session_start();
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("Starting wifidog_password($error)");}
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sessionkey=$_REQUEST["wifidog-password"];
	if($sessionkey==null){
		if(isset($_REQUEST["sessionkey"])){$sessionkey=$_REQUEST["sessionkey"];}
	}else{
		$_REQUEST["sessionkey"]=$sessionkey;
	}
	$q=new mysql_hotspot();
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT uid FROM hotspot_members WHERE `sessionkey`='$sessionkey'"));
	if($ligne["uid"]==null){
		if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("No existent account",__FUNCTION__,__LINE__);}
		echo BuildFullPage(null,"{this_account_didnot_exists}");
		return;
	}
	
	if($_REQUEST["url"]==null){$_REQUEST["url"]=$_SESSION["HOTSPOT_REDIRECT_URL"];}
	
	
	
	$email=$tpl->_ENGINE_parse_body("{email}");
	$password=$tpl->_ENGINE_parse_body("{password}");
	$submit=$tpl->_ENGINE_parse_body("{submit}");
	$register=$tpl->_ENGINE_parse_body("{change_password}");
	$confirm=$tpl->_ENGINE_parse_body("{confirm}");
	$password_mismatch=$tpl->javascript_parse_text("{password_mismatch}");
	$redirecturi=$_GET["url"];
	$t=time();
	
	$wifidog_build_uri=wifidog_build_uri();
	$uriext=$wifidog_build_uri[0];
	$HiddenFields=$wifidog_build_uri[1];
	unset($_SESSION["HOTSPOT_AUTO_REGISTER"]);
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$RECOVER_MESSAGE_P1=$ArticaHotSpotSMTP["RECOVER_MESSAGE_P1"];
	
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$btsize=$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"];
	$fontsize=$ArticaHotSpotSMTP["SKIN_FONT_SIZE"];
	
	$form_head=null;
	if($wifidog_templates->FORM_HEAD<>null){
		$form_head="<p>{$wifidog_templates->FORM_HEAD}</p>";
	}
	
	$html="
	
	<div style='width:98%' class=form id='form-$t'>
	<form name='register-$t' method='post' action='$page' class='form-horizontal' style='padding:left:15px'>
	$form_head
	<input type='hidden' id='confirm-password' name='confirm-password' value='yes'>
	$HiddenFields
	<div style='font-size:26px;font-weight:bold;margin-bottom:15px'>$register</div>
	<div style='font-size:18px'>$RECOVER_MESSAGE_P1</div>
	<label style='font-size:$fontsize;margin-top:20px' for=\"email-$t\" class=legend>$email: {$ligne["uid"]}</label>
	<label style='font-size:$fontsize;margin-top:20px' for=\"password\" class=legend>$password:</label>
	<input style='font-size:$fontsize;width:80%' type=\"password\"
	placeholder=\"$password\" id=\"password\" name=\"password\" value='{$_REQUEST["password"]}'>
	 
	
	<label style='font-size:$fontsize;margin-top:20px' for=\"password2-$t\" class=legend>$password ($confirm):</label>
	<input style='font-size:$fontsize;width:80%' type=\"password\"
	placeholder=\"$password ($confirm)\" name=\"password2\"
	id=\"password2\" value='{$_REQUEST["password2"]}'>
	
	
	<div style='margin-top:20px;text-align:right'>
	
	<a data-loading-text=\"Chargement...\"
	style=\"font-size:$btsize;text-transform:capitalize\"
	class=\"Button2014 Button2014-success Button2014-lg\"
	id=\"".time()."\"
	onclick=\"javascript:document.forms['register-$t'].submit();document.getElementById('form-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';\"
	href=\"javascript:Blurz()\">&laquo;&nbsp;$submit&nbsp;&raquo;</a>
	</div>
	
	</form>
	</div>
	<script>
	$('.input-block-level').keypress(function (e) {
	
	if (e.which == 13) {
	document.forms['register-$t'].submit();
	document.getElementById('form-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';
	}
	
	});
	
	</script>
	
	
	
	";
	echo BuildFullPage($html,$error);	
	
}

function wifidog_password_perform(){
	$tpl=new templates();
	$q=new mysql_hotspot();
	$sessionkey=$_REQUEST["sessionkey"];
	
	if($sessionkey==null){
		return wifidog_password("Missing field sessionkey");
		
	}
	
	$url=$_REQUEST["url"];
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT uid FROM hotspot_members WHERE `sessionkey`='$sessionkey'"));
	if($ligne["uid"]==null){
		echo BuildFullPage(null,"<center>{this_account_didnot_exists}<hr><span style='font-size:12px'>$sessionkey</span></center>","<META http-equiv=\"refresh\" content=\"5; URL=$url\">");
		return;
	}
	
	
	$password2=trim($_POST["password2"]);
	$password=trim($_POST["password"]);
	if($password2<>$password){return wifidog_password("{password_mismatch}");}
	$password=md5($password);
	$sql="UPDATE hotspot_members
	SET autocreate_confirmed=1,
		autocreate=1,
		password='$password'
		WHERE sessionkey='$sessionkey'";

	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		return wifidog_password($q->mysql_error_html());
	}
	$ArticaHotSpotSMTP=SMTP_SETTINGS();
	$btsize=$ArticaHotSpotSMTP["SKIN_BUTTON_SIZE"];
	$fontsize=$ArticaHotSpotSMTP["SKIN_FONT_SIZE"];
	
	
	$text_form="
	<div style='width:98%' class=form>
	<center>
	<div style='font-size:$fontsize'><center>{updated_password_successfully}<br>$url</center></div>
	<img src='hotspot.php?imgload=wait_verybig_mini_red.gif'></center></div>";
	
	$text_form=$tpl->_ENGINE_parse_body($text_form);
	if(!preg_match("#^http#", $url)){$url="http://$url";}
	echo BuildFullPage($text_form,null,"<META http-equiv=\"refresh\" content=\"5; URL=$url\">");
	
}


function wifidog_register_sms($ruleid,$posted=false){
	
	
	$template_path="/usr/share/hotspot/$ruleid";
	$HiddenFields="%HIDDENFIELDS%";
	$wifidog_templates=new wifidog_templates($ruleid);
	$sock=new wifidog_settings($ruleid);
	$styleF="font-size:{$wifidog_templates->SMS_FONT_SIZE}px !important";
	$SMS_BUTTON=$wifidog_templates->char($wifidog_templates->SMS_BUTTON);
	$redirecturi="%URL%";
	$t=time();
	$form_head=null;
	if($wifidog_templates->FORM_HEAD<>null){
		$form_head="<p>{$wifidog_templates->FORM_HEAD}</p>";
	}
	
	$mobile=$wifidog_templates->MobileLabel;
	
	$html[]="
	<div class=title2>".$wifidog_templates->char($wifidog_templates->RegisterTitle)."</div>
	<p>".$wifidog_templates->char($wifidog_templates->SMS_INTRO)."</p>
	<div style='width:98%' class=form id='form-$t'>
		<form name='registersms-$t' method='post' action='sms-register' class='form-horizontal' style='padding:left:15px'>
		$form_head
		<input type='hidden' id='ruleid' name='ruleid' value='$ruleid'>
		
		$HiddenFields
	<table style='width:100%'>". ALL_LOGINS($ruleid,$t,"SMS")."
		
			
			";
	if($posted){
		
		$SMS_BUTTON=$wifidog_templates->SubmitButton;
		$html[]="
		<tr>
		
			<td style='$styleF' colspan=2>
				<input type='hidden' id='mobile' name='mobile' value='%SMSPHONE%'>
				<center style='$wifidog_templates->LegendsStyle;text-align:center !important'>$mobile</center>
			</td>
		</tr>
		<tr style='height:90px'>
			<td style='$styleF' colspan=2>
				<center style='$wifidog_templates->LegendsStyle;text-align:center !important'>
					<a href=\"javascript:blur();\" OnClick=\"javascript:RetryMobile()\"
					style='$wifidog_templates->LegendsStyle;text-align:center !important;text-decoration:underline !important'>
					{$_REQUEST["mobile"]}
					</a>
			</td>
		</tr>
		<tr>
			<td colspan=2>
				<center style='$wifidog_templates->LegendsStyle;$styleF;text-align:center !important'>".$wifidog_templates->char($wifidog_templates->SMS_FIELD)."</center>
			</td>
		</tr>
		<tr style='height:90px'>
			<td colspan=2>
			<center style='$styleF'>
				<input style='width:80%;$styleF;text-align:center' type=\"text\" id=\"SMS_CODE\" name=\"SMS_CODE\" value=''>
			</center>
			</td>
		</tr>
		";
	}else{
		$html[]="
		
		<tr>
			<td style='$styleF' colspan=2><center style='$wifidog_templates->LegendsStyle;text-align:center !important'>$mobile</center></td>	
		</tr>
		<tr style='height:90px' >
			<td colspan=2><center style='$styleF'>
					<input style='width:80%;$styleF;text-align:center !important'  type=\"text\" placeholder=\"$mobile\" id=\"mobile\" name=\"mobile\" value='%SMSPHONE%'>
				</center>
			</td>
		</tr>
		
		";
		
		
		
	}

$html[]="<tr><td colspan=2>&nbsp;</td></tr>";
$html[]="<tr>";



$html[]="
<td colspan=2 class=ButtonCell><center>
	<a data-loading-text=\"Chargement...\"
	style=\"text-transform:capitalize\"
	class=\"Button2014 Button2014-success Button2014-lg\"
	id=\"".time()."\"
	onclick=\"javascript:document.forms['registersms-$t'].submit();document.getElementById('form-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';\"
	href=\"javascript:Blurz()\">&laquo;&nbsp;$SMS_BUTTON&nbsp;&raquo;</a>
	</center>
</td>
</tr>
</table>

</form>
</div>
<script>
$('.input-block-level').keypress(function (e) {

if (e.which == 13) {
document.forms['registersms-$t'].submit();
document.getElementById('registersms-$t').innerHTML='<center><img src=images?picture=wait_verybig_mini_red.gif></center>';
}

});

function RetryMobile(){
	document.getElementById('mobile').value='';
	document.getElementById('remove-sms').value='1';
	document.forms['registersms-$t'].submit();
	document.getElementById('registersms-$t').innerHTML='<center><img src=images?picture=imgload=wait_verybig_mini_red.gif></center>';
}

</script>



";
$txt=BuildFullPage(@implode("", $html),null,null,$ruleid);
if(!$posted){@file_put_contents("$template_path/sms.html", $txt);return;}
@file_put_contents("$template_path/sms-posted.html", $txt);

}


function wifidog_register($ruleid){
	$template_path="/usr/share/hotspot/$ruleid";
	$wifidog_templates=new wifidog_templates($ruleid);
	$page=CurrentPageName();
	$tpl=new templates();

		
	$email=$wifidog_templates->LabelEmail;
	$password=$wifidog_templates->LabelPassword;
	$submit=$wifidog_templates->SubmitButton;
	$confirm=$wifidog_templates->LabelConfirm;
	$password_mismatch=$wifidog_templates->PasswordMismatch;
	$cancel=$wifidog_templates->CancelButton;
	$RegisterTitle2=null;
	$t=time();
	
	$SockTemplate=new wifidog_settings($ruleid);
	$ArticaHotSpotNowPassword=intval($SockTemplate->GET_INFO("ArticaHotSpotNowPassword"));
	$ENABLED_SMTP=intval($SockTemplate->GET_INFO("ENABLED_SMTP"));
	if($ENABLED_SMTP==0){$email=$wifidog_templates->LabelUsername;}
	$REGISTER_GENERIC_PASSWORD=intval($SockTemplate->GET_INFO("REGISTER_GENERIC_PASSWORD"));
	$USE_ACTIVEDIRECTORY=intval($SockTemplate->GET_INFO("USE_ACTIVEDIRECTORY"));
	
	if($USE_ACTIVEDIRECTORY==1){
		$email=$email."/$wifidog_templates->DomainAccount";
		$RegisterTitle2="/$wifidog_templates->LoginTitle";
	}
	
	$CancelButton="<div style='margin-top:20px;text-align:right'>
	<a data-loading-text=\"Chargement...\" 
	style=\"text-transform:capitalize\" 
	class=\"Button2014 Button2014-success Button2014-lg\" 
	id=\"".time()."\" 
	href=\"/%RETURNLOGIN%\">&laquo;&nbsp;$cancel&nbsp;&raquo;</a>&nbsp;&nbsp;";
	
	
	if($ENABLED_SMTP==1){
		
			$CancelButton=null;
		
	}
	$form_head=null;
	if($wifidog_templates->FORM_HEAD<>null){
		$form_head="<p>{$wifidog_templates->FORM_HEAD}</p>";
	}
	
	
	$html[]="
	<!-- Rule: $ruleid  -->
	<!-- ENABLED_SMTP: $ENABLED_SMTP  -->
	<!-- ACTIVE DIRECTORY: $USE_ACTIVEDIRECTORY -->
	<!-- ArticaHotSpotNowPassword: $ArticaHotSpotNowPassword  -->
	<!-- REGISTER_GENERIC_PASSWORD: $REGISTER_GENERIC_PASSWORD  -->
	<div class=title2>".$wifidog_templates->char($wifidog_templates->RegisterTitle)."$RegisterTitle2</div>
	<p>".$wifidog_templates->char($wifidog_templates->REGISTER_MESSAGE_EXPLAIN)."</p>
	<div style='width:98%' class=form id='form-$t'>
	<form name='register-$t' method='post' action='register' class='form-horizontal' style='padding:left:15px'>
	$form_head
	<input type='hidden' id='register-member' name='register-member' value='yes'>
	<input type='hidden' id='ruleid' name='ruleid' value='$ruleid'>
	<input type='hidden' id='USE_ACTIVEDIRECTORY' name='USE_ACTIVEDIRECTORY' value='$USE_ACTIVEDIRECTORY'>
	%HIDDENFIELDS%
	<table style='width:100%'>". ALL_LOGINS($ruleid,$t,"REGISTER")."
	<tr>
	<td class=legend>$email:</td>
	<td><input style='width:80%' type=\"text\" placeholder=\"$email\" id=\"email\" name=\"email\" value='%USERNAME%'></td>
	</tr>
	";

	if($ArticaHotSpotNowPassword==1){
		$html[]="<input type=\"hidden\" name=\"password2\" value=''><input type=\"hidden\" name=\"password\" id='password' value=''>";
	}else{
		
		$html[]="
		<tr>
			<td class=legend>$password:</td>
			<td><input style='width:80%' type=\"password\" placeholder=\"$password\" id=\"password\" name=\"password\" value=''></td>
		</tr>
		<tr>
			<td class=legend>$password ($confirm):</td>
			<td><input style='width:80%' type=\"password\" 
		placeholder=\"$password ($confirm)\" name=\"password2\" 
		id=\"password2\" value=''></td>
		</tr>		
		";
	}
	
	
	if($wifidog_templates->REGISTER_GENERIC_PASSWORD==1){

		$html[]="
		<tr>
		<td class=legend>".$wifidog_templates->char($wifidog_templates->REGISTER_GENERIC_LABEL).":</td>
		<td><input style='width:80%' type=\"text\" id=\"passphrase\" name=\"passphrase\" value=''></td>
		</tr>";		
		
	}

	$html[]="<tr><td colspan=2>&nbsp;</td></tr>";
	$html[]="<td colspan=2 align='right' class=ButtonCell>";
	
	
	
	$html[]="$CancelButton
	
	<a data-loading-text=\"Chargement...\" 
								style=\"text-transform:capitalize\" 
								class=\"Button2014 Button2014-success Button2014-lg\" 
								id=\"".time()."\" 
								onclick=\"javascript:document.forms['register-$t'].submit();document.getElementById('form-$t').innerHTML='<center><img src=hotspot.php?imgload=wait_verybig_mini_red.gif></center>';\" 
								href=\"javascript:Blurz()\">&laquo;&nbsp;$submit&nbsp;&raquo;</a>
	</td>
	</tr>
	</table>

</form>
</div>	
<script>

$('input').keypress(function(e){
    if (e.which == 13) {
		document.forms['register-$t'].submit();
	}
});

 $('.input-block-level').keypress(function(e){
	
	 if (e.which == 13) {
		document.forms['register-$t'].submit();
	 }

});

</script>
	
	
	
	";
	$text=BuildFullPage(@implode("\n", $html),null,null,$ruleid);
	@file_put_contents("$template_path/register.html", $text);

}

function BuildFullPage($content,$error=null,$headerAdd=null,$ruleid){
	$prefix=null;
	$tpl=new templates();
	$sock=new sockets();
	if(is_numeric($error)){
		$ruleid=$error;
		$error=null;
	}
	if(is_numeric($headerAdd)){
		$ruleid=$headerAdd;
		$headerAdd=null;
	}
	
	$content="%HOTSPOT_ERROR%$content";
	$wifidog_templates=new wifidog_templates($ruleid,$headerAdd);
	return $wifidog_templates->build($content);

}




?>