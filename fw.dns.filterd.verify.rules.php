<?php
if(isset($_SESSION["uid"])){header("content-type: application/x-javascript");echo "document.location.href='logoff.php'";exit();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.dnsfilter.inc");
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["www"])){test();exit;}
if(isset($_GET["results"])){results();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog("{verify_rules}", "$page?popup=yes");
}

function popup() {
	$page=CurrentPageName();
	$tpl=new template_admin();
	$t=time();
	$ipaddr=$_SESSION["UFDBT"]["IP"];
	$username=$_SESSION["UFDBT"]["USER"];
	$www=$_SESSION["UFDBT"]["WWW"];
	if($ipaddr==null){$ipaddr=$_SERVER["REMOTE_ADDR"];}
	if($www==null){$www="http://www.youporn.com";}
	$form[]=$tpl->field_text("www", "{request}", $www);
	$form[]=$tpl->field_ipaddr("ipaddr", "{ipaddr}", $ipaddr);
	
	
	$html=$tpl->form_outside("{request}", @implode("\n", $form),"{ufdbguard_verify_rules_explain}","{check}","Loadjs('$page?results=yes')");
	echo $tpl->_ENGINE_parse_body($html);
}

function results(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_display_results($_SESSION["UFDB_TESTS_RESULTS"], $_SESSION["UFDB_TESTS_TRUE"]);
	
}


function test(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$www=$_POST["test"];
	$q=new mysql_squid_builder();
	$ipaddr=$_POST["ipaddr"];
	$user=$_POST["user"];
	$www=$_POST["www"];
	$_SESSION["UFDBT"]["IP"]=$ipaddr;
	$_SESSION["UFDBT"]["USER"]=$user;
	$_SESSION["UFDBT"]["WWW"]=$www;
	if($user==null){$user="-";}
	if($ipaddr==null){$ipaddr="-";}
	// articarest /dnsfilterd/verify : ufdbgclient sur le port de dnsfilterd, sans
	// shell (remplace framework/squid.php?ufdbclient, entree disparue).
	$json=json_decode((string)$GLOBALS["CLASS_SOCKETS"]->REST_API("/dnsfilterd/verify/".rawurlencode($ipaddr)."/".rawurlencode($www)));
	if(!is_object($json) || !isset($json->Status) || !$json->Status){
		$error=(is_object($json) && isset($json->Error))?$json->Error:"articarest: ".json_last_error_msg();
		$_SESSION["UFDB_TESTS_TRUE"]=true;
		$_SESSION["UFDB_TESTS_RESULTS"]="<H1>".htmlspecialchars($error)."</H1>";
		return;
	}
	$datas=(string)$json->Info;

	if(preg_match("#^http.*#", $www)){
		$url_www=parse_url($www);
		$url_host=$url_www["host"];
	}else{
		$url_host=$www;
	}

	$tpl=new template_admin();
	$title_pass=$tpl->_ENGINE_parse_body("{access_to_internet}");
	$redirected=$tpl->_ENGINE_parse_body("<strong class='text-danger'>{redirected}/{denied}</strong>");
	$datas=trim($datas);
	
	
	if($datas=="OK"){$datas=null;}

	if(trim($datas)==null){

		$catz=new mysql_catz();
		$category_id=$catz->GET_CATEGORIES($url_host);
		if($category_id>0){$category_text="<br>".$tpl->_ENGINE_parse_body($tpl->CategoryidToName($category_id));}
		
		$_SESSION["UFDB_TESTS_TRUE"]=false;
		$_SESSION["UFDB_TESTS_RESULTS"]="<H1>$title_pass<H1>$category_text";
		

		return;}




	if(preg_match('#url=.*?http:\/\/([0-9\.]+)\/([0-9]+)\/(.+?)\/P([0-9]+)#', $datas,$re)){
	
		$redirected_ip=$re[1];
		$TTL=$re[2];
		$RuleName=$re[3];
		$Category=$re[4];
		$CategoryName=$tpl->CategoryidToName($Category);
		
	

		$f[]="<h1>";
		$f[]="$redirected</H1>";
		$f[]="<h3 class='font-bold no-margins'>{redirect}: $redirected_ip TTL {$TTL}s</h3>";
		$f[]="<small>".  $tpl->_ENGINE_parse_body("{rulename}: <strong>{$RuleName}</strong></small><br>");
		$f[]="<small>".   $tpl->_ENGINE_parse_body("{category}: <strong>$CategoryName</strong></small><br>");
		$f[]="";
		$_SESSION["UFDB_TESTS_TRUE"]=true;
		$_SESSION["UFDB_TESTS_RESULTS"]=$tpl->_ENGINE_parse_body(@implode("\n", $f));
	}
	
	
}
?>