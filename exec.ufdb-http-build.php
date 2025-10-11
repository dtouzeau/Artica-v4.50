<?php
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.ufdb.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.external_acl_squid_ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');

$GLOBALS["PIDFILE"]="/var/run/webfilter-http.pid";
$GLOBALS["PYTHONPGR"]="/usr/share/artica-postfix/webfilter-http.py";
$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if($argv[1]=="--adgroups"){build_adgroups();exit;}

build_templates();


function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/ufdb-http.build.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function build_adgroups(){
	$q=new mysql_squid_builder();
	$f[]=array();
	$external_acl_squid_ldap=new external_acl_squid_ldap();
	$results = $q->QUERY_SQL("SELECT adgroup FROM ufdb_page_rules GROUP BY adgroup");
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$adgroup=$ligne["adgroup"];
		if($adgroup=="*"){$adgroup=null;}
		if($adgroup==null){continue;}
		
		$array=$external_acl_squid_ldap->AdLDAP_MembersFromGroupName($adgroup);
		if(count($array)==0){continue;}
		$adgroup=strtolower($adgroup);
		foreach ($array as $user) {
			$user=trim(strtolower($user));
			$f[]="$user|$adgroup";
		}
		
	}
	@file_put_contents("/home/ufdb-templates/GROUPS",@implode("\n", $f));
	
}


function build_templates(){

	$unix=new unix();
    $tpl=new template_admin();
	build_progress(10, "{building}...");
    $php=$unix->LOCATE_PHP5_BIN();
    system("$php /usr/share/artica-postfix/exec.squid.global.access.php --ufdbclient");
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
	$results = $q->QUERY_SQL("SELECT ID,groupname FROM webfilter_rules");
	$template_path="/home/ufdb-templates";
	@mkdir($template_path,0755,true);
	
	@file_put_contents("/home/ufdb-templates/rule_0","Default");

    foreach ($results as $index=>$ligne){
		@file_put_contents("/home/ufdb-templates/rule_{$ligne["ID"]}", $tpl->utf8_encode($ligne["groupname"]));
		
	}
	
	$postgres=new postgres_sql();
	$sql="SELECT category_id,categoryname FROM personal_categories order by category_id";
	
	$results=$postgres->QUERY_SQL($sql);
	if(!$postgres->ok){
		build_progress(110, "{building} MySQL Error!!...");
		echo "!!!!!!!!!!!!!! $postgres->mysql_error !!!!!!!!!!!!!!\n";
		return;
	}
	
	while ($ligne = pg_fetch_assoc($results)) {
		$category_id=$ligne["category_id"];
		$categoryname=$ligne["categoryname"];
		$CATZ[]=$tpl->utf8_encode($category_id."|".$categoryname);
	}
	
	@file_put_contents("/home/ufdb-templates/CATEGORIES_NAMES",@implode("\n", $CATZ));
	
	
	$results = $q->QUERY_SQL("SELECT * FROM ufdb_page_rules");
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$network=$ligne["network"];
		$category=$ligne["category"];
		$webruleid=$ligne["webruleid"];
		if($category==null){$category="*";}
		if($network==null){$network="0.0.0.0/0";}
		$username=$ligne["username"];
		$adgroup=$ligne["adgroup"];
		if($adgroup=="*"){$adgroup=null;}
		if($adgroup<>null){$username=null;}
		if($username==null){$username="*";}
		if($adgroup==null){$adgroup="*";}
		$NETS[]="$network|$category|$webruleid|$username|$adgroup|{$ligne["zmd5"]}";
		build_progress(30, "{building} {$ligne["zmd5"]}...");
		build_index($ligne["zmd5"]);
		build_Error($ligne["zmd5"]);
		build_ticket($ligne["zmd5"]);
		build_redirect($ligne["zmd5"]);
		build_ticket_ok($ligne["zmd5"]);
		build_ticket_form2($ligne["zmd5"]);
		build_itcharters($ligne["zmd5"]);
	}
	build_progress(40, "{building} {networks2}...");
	@file_put_contents("/home/ufdb-templates/NETWORKS",@implode("\n", $NETS));
	fakejs();
	build_index(null);
	build_Error(null);
	build_redirect(null);
	build_ticket(null);
	build_ticket_ok(null);
	build_ticket_form2(null);
	build_itcharters(null);
	build_adgroups();

	
	if(!checkIntegrated()){
		build_progress(60, "{reconfigure_proxy_service} {APP_UFDB_HTTP}...");
		$php=$unix->LOCATE_PHP5_BIN();
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	}
	
	if(!checkIntegrated()){
		echo "Missing include ufdbunblock.conf\n";
		build_progress(110, "{reconfigure_proxy_service} ufdbunblock.conf {failed2}...");
		exit();
	}
	
	build_progress(60, "{restarting} {APP_UFDB_HTTP}...");
	system("/etc/init.d/ufdb-http restart");
	build_progress(70, "{restarting} {APP_UFDB_HTTP} {success}...");
	
	
	

	if(is_file("/etc/init.d/ufdb")){
		$php=$unix->LOCATE_PHP5_BIN();
		build_progress(80, "{reconfiguring} {APP_UFDB}...");
		system("$php /usr/share/artica-postfix/exec.squidguard.php --build --force");
		system("/etc/init.d/ufdb reload --force");
		build_progress(90, "{reconfiguring} {APP_UFDB} {success}...");
	}
	
	
	
	build_progress(100, "{done}...");
	
}
function fakejs(){
	@mkdir("/home/ufdb-templates/fakes",0755,true);
	$f[]="// Blocked by Web filtering rule";
	$f[]="// Please, see detailled logs to analyze this behavior.";
	$f[]="";
	@file_put_contents("/home/ufdb-templates/fakes/fake.js",@implode("\n", $f));
	$f=array();
	
	$f[]="/**";
	$f[]="* blocked by url filtering";
	$f[]="*";
	$f[]="*";
	$f[]="";
	@file_put_contents("/home/ufdb-templates/fakes/fake.css",@implode("\n", $f));
	$f=array();
	
}

function checkIntegrated(){
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	foreach ( $f as $index=>$line ){if(preg_match("#squid3\/ufdbunblock\.conf#", $line)){
		
		return true;}}
		
		return false;

}



function build_default_settings($ligne=array()){
	
	
	if(!isset($ligne["templateid"])){$ligne["templateid"]=1;}
	if(intval($ligne["templateid"])==0){$ligne["templateid"]=1;}
	if(!isset($ligne["UfdbGuardHTTPNoVersion"])){$ligne["UfdbGuardHTTPNoVersion"]=0;}
	if(!isset($ligne["UfdbGuardHTTPDisableHostname"])){$ligne["UfdbGuardHTTPDisableHostname"]=0;}
	if(!isset($ligne["UfdbGuardHTTPEnablePostmaster"])){$ligne["UfdbGuardHTTPEnablePostmaster"]=1;}
	if(!isset($ligne["allow"])){$ligne["allow"]=0;}
	if(!isset($ligne["maxtime"])){$ligne["maxtime"]=0;}
	if(!isset($ligne["notify"])){$ligne["notify"]=0;}
	if(!isset($ligne["ticket"])){$ligne["ticket"]=0;}
	if(!isset($ligne["ticket2"])){$ligne["ticket2"]=0;}
	if(!isset($ligne["TICKET_TEXT_SUCCESS"])){$ligne["TICKET_TEXT_SUCCESS"]=null;}
	if(!isset($ligne["addTocat"])){$ligne["addTocat"]=null;}
	
	
	if(!isset($ligne["REDIRECT_TEXT"])){$ligne["REDIRECT_TEXT"]=null;}
	if(!isset($ligne["UFDBGUARD_TITLE_1"])){$ligne["UFDBGUARD_TITLE_1"]=null;}
	if(!isset($ligne["UFDBGUARD_SERVICENAME"])){$ligne["UFDBGUARD_SERVICENAME"]=null;}
	
	if(!isset($ligne["UFDBGUARD_PROXYNAME"])){$ligne["UFDBGUARD_PROXYNAME"]=null;}
	if(!isset($ligne["UFDBGUARD_ADMIN"])){$ligne["UFDBGUARD_ADMIN"]=null;}
	if(!isset($ligne["UFDBGUARD_LABELVER"])){$ligne["UFDBGUARD_LABELVER"]=null;}
	if(!isset($ligne["UFDBGUARD_LABELPOL"])){$ligne["UFDBGUARD_LABELPOL"]=null;}
	if(!isset($ligne["UFDBGUARD_LABELRQS"])){$ligne["UFDBGUARD_LABELRQS"]=null;}
	if(!isset($ligne["UFDBGUARD_LABELMEMBER"])){$ligne["UFDBGUARD_LABELMEMBER"]=null;}
	if(!isset($ligne["UFDBGUARD_UNLOCK_LINK"])){$ligne["UFDBGUARD_UNLOCK_LINK"]=null;}
	if(!isset($ligne["UFDBGUARD_TICKET_LINK"])){$ligne["UFDBGUARD_TICKET_LINK"]=null;}
	
	if(!isset($ligne["CONFIRM_TICKET_TEXT"])){$ligne["CONFIRM_TICKET_TEXT"]=null;}
	if(!isset($ligne["CONFIRM_TICKET_BT"])){$ligne["CONFIRM_TICKET_BT"]=null;}
	

	
	if(!isset($ligne["TICKET_TEXT"])){$ligne["TICKET_TEXT"]=null;}
	if($ligne["TICKET_TEXT"]==null){$ligne["TICKET_TEXT"]="Click on the link bellow in order to send to the company support team a request to unlock this Website";}
	
	
	if($ligne["UFDBGUARD_TICKET_LINK"]==null){$ligne["UFDBGUARD_TICKET_LINK"]="submit a ticket";}
	if($ligne["UFDBGUARD_PROXYNAME"]==null){$ligne["UFDBGUARD_PROXYNAME"]="Proxy hostname";}
	if($ligne["UFDBGUARD_ADMIN"]==null){$ligne["UFDBGUARD_ADMIN"]="Your administrator";}
	if($ligne["UFDBGUARD_LABELVER"]==null){$ligne["UFDBGUARD_LABELVER"]="Application Version";}
	if($ligne["UFDBGUARD_LABELPOL"]==null){$ligne["UFDBGUARD_LABELPOL"]="Policy";}
	if($ligne["UFDBGUARD_LABELRQS"]==null){$ligne["UFDBGUARD_LABELRQS"]="Request";}
	if($ligne["UFDBGUARD_LABELMEMBER"]==null){$ligne["UFDBGUARD_LABELMEMBER"]="Member";}
	if($ligne["UFDBGUARD_UNLOCK_LINK"]==null){$ligne["UFDBGUARD_UNLOCK_LINK"]="Unlock";}
	
	
	if($ligne["UFDBGUARD_TITLE_1"]==null){$ligne["UFDBGUARD_TITLE_1"]="This Web Page is Blocked";}
	if($ligne["REDIRECT_TEXT"]==null){$ligne["REDIRECT_TEXT"]="Please wait, you will be redirected to the requested website";}
	if($ligne["TICKET_TEXT_SUCCESS"]==null){$ligne["TICKET_TEXT_SUCCESS"]="The support team has been notified and will approve or not approve your request in few times";}
	
	if(!isset($ligne["UFDBGUARD_TITLE_2"])){$ligne["UFDBGUARD_TITLE_2"]=null;}
	if($ligne["UFDBGUARD_TITLE_2"]==null){$ligne["UFDBGUARD_TITLE_2"]="How to fix this issue ?";}
	if($ligne["UFDBGUARD_SERVICENAME"]==null){$ligne["UFDBGUARD_SERVICENAME"]="Web-Filtering";}

	if(!isset($ligne["UFDBGUARD_PARA1"])){$ligne["UFDBGUARD_PARA1"]=null;}
	if($ligne["UFDBGUARD_PARA1"]==null){$ligne["UFDBGUARD_PARA1"]="Sorry, access to requested page had to be blocked by our policy.<br>
Requested page contains inappropriate material for a correct production environment.<br>
Such pages are considered unacceptable in our network.";}	
	
	if(!isset($ligne["UFDBGUARD_PARA2"])){$ligne["UFDBGUARD_PARA2"]=null;}
	if($ligne["UFDBGUARD_PARA2"]==null){$ligne["UFDBGUARD_PARA2"]="Please contact your network administrator to clarify the reasons for blocking.<br>
The following technical information may prove to be helpful.";}
	
	if($ligne["CONFIRM_TICKET_TEXT"]==null){$ligne["CONFIRM_TICKET_TEXT"]="by clicking on the confirm button, a message will be sent to the helpdesk support team.<br>The helpdesk support team will be able to approve your request.";}
	if($ligne["CONFIRM_TICKET_BT"]==null){$ligne["CONFIRM_TICKET_BT"]="i confirm, send the query";}
	echo "build_index[...]...CONFIRM_TICKET_BT:{$ligne["CONFIRM_TICKET_BT"]}\n";
    foreach ($ligne as $key => $val) {
		if(is_numeric($val)){continue;}
		$ligne[$key]=char($val);
	}
	
return $ligne;
}
function char($text){
    $tpl=new template_admin();
	$text = htmlentities($text, ENT_NOQUOTES, "UTF-8");
	$text = htmlspecialchars_decode($text);
	return $tpl->utf8_encode($text);
}


function build_Error($zmd5=null){
	$ligne=array();
	if($zmd5<>null){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM ufdb_page_rules WHERE `zmd5`='$zmd5'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	}
	
	$ligne=build_default_settings($ligne);
	
	$SubFolder=$zmd5;
	if($SubFolder==null){$SubFolder="default";}
	
	$template_path="/home/ufdb-templates/$SubFolder";
	@mkdir($template_path,0755,true);
	$templateid=$ligne["templateid"];

	
	echo "build_Error[{$zmd5}]...Template ID: $templateid\n";
	
	
	$f[]="    <h2>Fatal Error</h2>    ";
	$f[]="    <p>%FATAL_ERROR%</p>";


	$tpl=new ufdb_templates($zmd5);
	$content=$tpl->build(@implode("\n", $f));
	echo "build_index: Building $template_path/index.html done...\n";
	@file_put_contents("$template_path/error.html", $content);
	
}
function build_redirect($zmd5=null){
	$ligne=array();
	if($zmd5<>null){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM ufdb_page_rules WHERE `zmd5`='$zmd5'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	}

	$ligne=build_default_settings($ligne);

	$SubFolder=$zmd5;
	if($SubFolder==null){$SubFolder="default";}

	$template_path="/home/ufdb-templates/$SubFolder";
	@mkdir($template_path,0755,true);
	$templateid=$ligne["templateid"];


	echo "build_Error[{$zmd5}]...Template ID: $templateid\n";

	$f[]="    <h2>%FAMILIYSITE%</h2>    ";
	$f[]="    <h2>{$ligne["UFDBGUARD_UNLOCK_LINK"]}</h2>    ";
	$f[]="    <p>{$ligne["REDIRECT_TEXT"]}</p>";
	$f[]="    <p>&nbsp;</p>";
	$f[]="<center>
	<div id='maincountdown' style='width:100%'>
		<center style='margin:20px;padding:20px;background-color:white;color:black;width:80%; -webkit-border-radius: 4px;-moz-border-radius: 4px;border-radius: 4px;' >
			<input type='hidden' id='countdownvalue' value='10'>
			<span id='countdown' style='font-size:70px'></span>
		
	
		<p style='font-size:22px'>
		<center style='margin:20px;font-size:70px;background-color:white;' id='wait_verybig_mini_red'>
			<img src='images?picture=wait_verybig_mini_red.gif'>
		</center>
		</p>
	</div>
	</center>
	<script>
	
	
	
setInterval(function () {
	var countdown = document.getElementById('countdownvalue').value
	countdown=countdown-1;
	if(countdown==0){
		document.getElementById('countdownvalue').value=0;
		document.getElementById('wait_verybig_mini_red').innerHTML='';
		document.getElementById('maincountdown').innerHTML='';
		window.location = \"%URL%?%TIME%\";
		return;
	}
	document.getElementById('countdownvalue').value=countdown;
	document.getElementById('countdown').innerHTML=countdown
	
	}, 1000);
	</script>";


	$tpl=new ufdb_templates($zmd5);
	$content=$tpl->build(@implode("\n", $f));
	echo "build_index: Building $template_path/redirect.html done...\n";
	@file_put_contents("$template_path/redirect.html", $content);

}

function build_ticket($zmd5=null){
	$ligne=array();
	if($zmd5<>null){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM ufdb_page_rules WHERE `zmd5`='$zmd5'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	}
	
	$ligne=build_default_settings($ligne);
	
	$SubFolder=$zmd5;
	if($SubFolder==null){$SubFolder="default";}
	
	$template_path="/home/ufdb-templates/$SubFolder";
	@mkdir($template_path,0755,true);
	
	
	$templateid=$ligne["templateid"];
	$allow=intval($ligne["allow"]);
	
	echo "build_index[{$zmd5}]...Template ID: $templateid\n";
	
	$UfdbGuardHTTPDisableHostname=intval($ligne["UfdbGuardHTTPDisableHostname"]);
	$UfdbGuardHTTPEnablePostmaster=$ligne["UfdbGuardHTTPEnablePostmaster"];
	
	$f[]="    <h2>%FAMILIYSITE%</h2>    ";
	$f[]="    <h2>{$ligne["UFDBGUARD_TICKET_LINK"]}</h2>    ";
	$f[]="    <p>{$ligne["TICKET_TEXT_SUCCESS"]}</p>";
	$f[]="    <p>&nbsp;</p>";
	$f[]="";
	
	
	$tpl=new ufdb_templates($zmd5);
	$content=$tpl->build(@implode("\n", $f));
	echo "build_index: Building $template_path/ticket_success.html done...\n";
	@file_put_contents("$template_path/ticket_success.html", $content);
	
}
function build_ticket_ok($zmd5=null){
	$ligne=array();
	if($zmd5<>null){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM ufdb_page_rules WHERE `zmd5`='$zmd5'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	}

	$ligne=build_default_settings($ligne);

	$SubFolder=$zmd5;
	if($SubFolder==null){$SubFolder="default";}

	$template_path="/home/ufdb-templates/$SubFolder";
	@mkdir($template_path,0755,true);


	$templateid=$ligne["templateid"];
	$allow=intval($ligne["allow"]);

	echo "build_index[{$zmd5}]...Template ID: $templateid\n";

	$UfdbGuardHTTPDisableHostname=intval($ligne["UfdbGuardHTTPDisableHostname"]);
	$UfdbGuardHTTPEnablePostmaster=$ligne["UfdbGuardHTTPEnablePostmaster"];

	$f[]="    <h2>%FAMILIYSITE% against %CATEGORY%</h2>    ";
	$f[]="    <h2>Success!!</h2>    ";
	$f[]="    <p style='font-size:22px'>Hi admin,<br>
			The website %FAMILIYSITE% and your member %MEMBER% currently bypass the Web-Filter %CATEGORY% category during %XTIME%<br>
			Your member can now retry it's request to : <a href=\"%URL%\" style=text-decoration:underline>%URL%</a>
			
			</p>";
	$f[]="    <p>&nbsp;</p>";
	$f[]="";


	$tpl=new ufdb_templates($zmd5);
	$content=$tpl->build(@implode("\n", $f));
	echo "build_index: Building $template_path/ticketok_success.html done...\n";
	@file_put_contents("$template_path/ticketok_success.html", $content);

}

function build_itcharters($zmd5=null){
	$ligne=array();
	$q=new mysql_squid_builder();
	if($zmd5<>null){
		$sql="SELECT * FROM ufdb_page_rules WHERE `zmd5`='$zmd5'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	}
	
	$ligne=build_default_settings($ligne);
	
	$SubFolder=$zmd5;
	if($SubFolder==null){$SubFolder="default";}
	
	$template_path="/home/ufdb-templates/$SubFolder";
	@mkdir($template_path,0755,true);

	$results=$q->QUERY_SQL("SELECT ID,enablepdf,PdfFileName,PdfContent,ChartContent,ChartHeaders,TextIntro,TextButton, title FROM itcharters");
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$f=array();
		$t=time();
		$tpl=new ufdb_templates($zmd5);
		$ChartID=$ligne["ID"];
		$PAGE_TITLE=$tpl->char($ligne["title"]);
		$HEADS= $ligne["ChartHeaders"];
		
		$f[]="    <h2>$PAGE_TITLE</h2>    ";

		
		if($ligne["TextIntro"]==null){
			$ligne["TextIntro"]="Please read the IT chart before accessing trough Internet";
		}else{
			$ligne["TextIntro"]=$tpl->char($ligne["TextIntro"]);
		}
		if($ligne["TextButton"]==null){
			$ligne["TextButton"]="I accept the terms and conditions of this agreement";
		
		}else{
			$ligne["TextButton"]=$tpl->char($ligne["TextButton"]);
		}
		
		$f[]="    <p>{$ligne["TextIntro"]}</p> ";
		
		
		$ligne["ChartContent"]="<p style='margin-left:20px;padding-left:20px;border-left:3px solid #CCCCCC' id='$t'>{$ligne["ChartContent"]}</p>";
		
		if($ligne["enablepdf"]==1){
			$ligne["ChartContent"]="	
			<object data=\"%WEBPATH%itchart-pdf?chart-id=$ChartID&ruleid=%CALCID%\" type=\"application/pdf\" 
			width=\"800\" height=\"600\">
			<iframe src=\"%WEBPATH%itchart-pdf?chart-id=$ChartID&ruleid=%CALCID%\" style=\"border: none;\"
			width=\"800\" height=\"600\">
			<p class=Textintro>It appears you don't have a PDF plugin for this browser.
			<br>You can <a href=\"%WEBPATH%itchart-pdf?chart-id=$ChartID&ruleid=%CALCID%\">click here to download the {$ligne["PdfFileName"]} file.</a></p>
			</iframe>
			</object>
			";
			
			@file_put_contents("/home/ufdb-templates/$SubFolder/PDF.$ChartID.pdf", $ligne["PdfContent"]);
		}
		$f[]=$ligne["ChartContent"];
		$f[]="<table width='100%'>";
		$f[]="
		<tr>
		<td class=\"info_content\" nowrap colspan=2 style='text-align:right !important;border-top:2px solid #E0E0E0;padding-top:10px'>
		<form name=AcceptChart-$t action=\"%WEBPATH%AcceptChart\" method=post id='AcceptChart-$t'>
		<input type='hidden' name='AcceptChartContent' value='%CHARTCONTENT%'>
		<input type='hidden' name='rule-id' value='%CALCID%'>

		<div style='width:100%;text-align:right;margin-top:20px'>
		<a href=\"javascript:blur()\"  OnClick=\"javascript:document.forms['AcceptChart-$t'].submit();\"
		class=\"Button2014 Button2014-success Button2014-lg\"
		style=\"text-transform:capitalize;font-weight:bold;text-decoration:underline\">&laquo;&nbsp;{$ligne["TextButton"]}&raquo;&nbsp;</a>
		</div>
		</form>
		</td>
		</tr>
		</table>
		</div>";
		$tpl->HeadsPlus=$ligne["ChartHeaders"];
		$content=$tpl->build(@implode("\n", $f));
		echo "build_index: Building $template_path/chart.$ChartID.html done...\n";
		@file_put_contents("$template_path/chart.$ChartID.html", $content);

	}
	
	
}


function build_ticket_form2($zmd5=null){
	$ligne=array();
	if($zmd5<>null){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM ufdb_page_rules WHERE `zmd5`='$zmd5'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	}

	$ligne=build_default_settings($ligne);

	$SubFolder=$zmd5;
	if($SubFolder==null){$SubFolder="default";}

	$template_path="/home/ufdb-templates/$SubFolder";
	@mkdir($template_path,0755,true);


	$templateid=$ligne["templateid"];
	$allow=intval($ligne["allow"]);

	echo "build_index[{$zmd5}]...Template ID: $templateid\n";
	
	$CONFIRM_TICKET_TEXT=$ligne["CONFIRM_TICKET_TEXT"];
	$CONFIRM_TICKET_BT=$ligne["CONFIRM_TICKET_BT"];
	$CONFIRM_TICKET_BT_len=strlen($ligne["CONFIRM_TICKET_BT"]);
	$tpl=new ufdb_templates($zmd5);
	
	$CONFIRM_TICKET_TEXT=$tpl->char($CONFIRM_TICKET_TEXT);
	$CONFIRM_TICKET_BT=$tpl->char($CONFIRM_TICKET_BT);
	$t=time();
	$textexplain=$ligne["UFDBGUARD_PARA2"];

	$f[]="    <h2>{$ligne["UFDBGUARD_TITLE_1"]}</h2>    ";
	$f[]="    <h2>{$ligne["UFDBGUARD_SERVICENAME"]} %CATEGORY% %CATEGORYTYPE%</h2>    ";

	$f[]="    <h3>{$ligne["UFDBGUARD_TITLE_2"]}</h3>";
	$f[]="    <p>$CONFIRM_TICKET_TEXT</p> ";
	$f[]="<!-- Button size:$CONFIRM_TICKET_BT_len -->";
	$f[]="    <table width='100%'>";
	$f[]="
		<tr>
		<td class=\"info_content\" nowrap colspan=2 style='text-align:right !important;border-top:2px solid #E0E0E0;padding-top:10px'>
		<form name=ticketform-$t action=\"ticketform\" method=post id='ticketform-$t'>
		<input type='hidden' name='maxtime' value='{$ligne["maxtime"]}'>
		<input type='hidden' name='notify' value='{$ligne["notify"]}'>
		%HIDDENFIELDS%
		
		<div style='width:100%;text-align:right;margin-top:20px'> 
		<a href=\"javascript:blur()\"  OnClick=\"javascript:document.forms['ticketform-$t'].submit();\"
		class=\"Button2014 Button2014-success Button2014-lg\"
		style=\"text-transform:capitalize;font-weight:bold;text-decoration:underline\">&laquo;&nbsp;{$CONFIRM_TICKET_BT}&raquo;&nbsp;</a>
		</div>
		</form>
		</td>
		</tr>
	</table>
	</div>";	


	
	$content=$tpl->build(@implode("\n", $f));
	echo "build_index: Building $template_path/ticketconfirm.html done...\n";
	@file_put_contents("$template_path/ticketconfirm.html", $content);

}

function build_index($zmd5=null){
	$ligne=array();
	if($zmd5<>null){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM ufdb_page_rules WHERE `zmd5`='$zmd5'";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	}
	
	$ligne=build_default_settings($ligne);
	
	$SubFolder=$zmd5;
	if($SubFolder==null){$SubFolder="default";}
	
	$template_path="/home/ufdb-templates/$SubFolder";
	@mkdir($template_path,0755,true);
	
	
	$templateid=$ligne["templateid"];
	$allow=intval($ligne["allow"]);
	$ticket=intval($ligne["ticket"]);
	
	echo "build_index[{$zmd5}]...Template ID: $templateid\n";
	
	$UfdbGuardHTTPDisableHostname=intval($ligne["UfdbGuardHTTPDisableHostname"]);
	$UfdbGuardHTTPEnablePostmaster=$ligne["UfdbGuardHTTPEnablePostmaster"];
	
	$textexplain=$ligne["UFDBGUARD_PARA2"];
	
	
	$f[]="    <h2>{$ligne["UFDBGUARD_TITLE_1"]}</h2>    ";
	$f[]="    <h2>{$ligne["UFDBGUARD_SERVICENAME"]} %CATEGORY% %CATEGORYTYPE%</h2>    ";
	$f[]="    <p>{$ligne["UFDBGUARD_PARA1"]}</p>";
	$f[]="    <h3>{$ligne["UFDBGUARD_TITLE_2"]}</h3>";
	$f[]="    <p>$textexplain</p> ";
	$f[]="    <p>&nbsp;</p>";
	$f[]="    <div id=\"info\">";
	$f[]="    <table width='100%'>";
	if($UfdbGuardHTTPDisableHostname==0){$f[]="        <tr><td class=\"info_title\" nowrap>{$ligne["UFDBGUARD_PROXYNAME"]}:</td><td class=\"info_content\">%VISIBLEHOSTNAME%</td></tr>";}
	if($UfdbGuardHTTPEnablePostmaster==1){$f[]="        <tr><td class=\"info_title\" nowrap>{$ligne["UFDBGUARD_ADMIN"]}:</td><td class=\"info_content\"><a href=\"mailto:%cache_mgr_user%?subject=Web%20Filtering%20complain%20%5B%URLENC%&body=URL%3A%URLENC%%2F%0AIP%3A%IPADDR%%0AREASON%3A%7Bweb_filtering%7D%20%RULE%%29%0ACategory%3A%CATEGORY%\">%cache_mgr_user%</a></td></tr>";}
	if($ligne["UfdbGuardHTTPNoVersion"]==0){$f[]="<tr><td class=\"info_title\" nowrap>{$ligne["UFDBGUARD_LABELVER"]}:</td><td class=\"info_content\">%ARTICAVER%</td></tr>";}
	$f[]="        <tr><td class=\"info_title\" nowrap>{$ligne["UFDBGUARD_LABELMEMBER"]}:</td><td class=\"info_content\">%MEMBER%</td></tr>";
	$f[]="        <tr><td class=\"info_title\" nowrap>{$ligne["UFDBGUARD_LABELPOL"]}:</td><td class=\"info_content\">%RULENAME%</td></tr>";
	$f[]="        <tr>";
	$f[]="            <td class=\"info_title\" nowrap>{$ligne["UFDBGUARD_LABELRQS"]}:</td>";
	$f[]="            <td class=\"info_content\">";
	$f[]="                <div class=\"break-word\">%URL%</div>";
	$f[]="            </td>";
	$f[]="        </tr>";
	
	$f[]="<!-- ticket = $ticket,Confirm={$ligne["ticket2"]} -->";
	
	if($ticket==1){
		$allow=0;
		$t=time();
		$action_ticket="ticketform";
		if($ligne["ticket2"]==1){$action_ticket="ticketform2";}
		

		$f[]="
		<tr><td colspan=2><p>{$ligne["TICKET_TEXT"]}</p></td></tr>
		<tr>
		<td class=\"info_content\" nowrap colspan=2 style='text-align:right !important;border-top:2px solid #E0E0E0;padding-top:10px'>
		<form name=ticketform-$t action=\"$action_ticket\" method=post id='ticketform-$t'>
		<input type='hidden' name='maxtime' value='{$ligne["maxtime"]}'>
		<input type='hidden' name='notify' value='{$ligne["notify"]}'>
		<input type='hidden' name='addTocat' value='{$ligne["addTocat"]}'>
		%HIDDENFIELDS%
		<div style='width:100%;text-align:right;margin-top:20px'> 
		<a href=\"javascript:blur()\"  OnClick=\"javascript:document.forms['ticketform-$t'].submit();\"
		class=\"Button2014 Button2014-success Button2014-lg\"
		style=\"text-transform:capitalize;font-weight:bold;text-decoration:underline\">&laquo;&nbsp;{$ligne["UFDBGUARD_TICKET_LINK"]}&raquo;&nbsp;</a>
		</div>
		</form>
		</td>
		</tr>";		
	}
	
	if($allow==1){
		$t=time();
		$f[]="
		<tr><td colspan=2>&nbsp;</td></tr>		
		<tr>
			<td class=\"info_content\" nowrap colspan=2 style='text-align:right !important;border-top:2px solid #E0E0E0;padding-top:10px'>
				<form name=unlock-$t action=\"unlock\" method=post id='unlock-$t'>
				<input type='hidden' name='maxtime' value='{$ligne["maxtime"]}'>
				<input type='hidden' name='notify' value='{$ligne["notify"]}'>
				<input type='hidden' name='addTocat' value='{$ligne["addTocat"]}'>
				%HIDDENFIELDS%
				<div style='width:100%;text-align:right;margin-top:20px'> 
				<a href=\"javascript:blur()\"  OnClick=\"javascript:document.forms['unlock-$t'].submit();\" 
				class=\"Button2014 Button2014-success Button2014-lg\"  
				style=\"text-transform:capitalize;font-weight:bold;text-decoration:underline\">&laquo;&nbsp;{$ligne["UFDBGUARD_UNLOCK_LINK"]}&raquo;&nbsp;</a>
				</div>
				</form>
			</td>
		</tr>";
	}
	
	$f[]="    </table>";
	$f[]="    </div>";

	
	$tpl=new ufdb_templates($zmd5);
	$content=$tpl->build(@implode("\n", $f));
	echo "build_index: Building $template_path/index.html done...\n";
	@file_put_contents("$template_path/index.html", $content);
	

	
}



