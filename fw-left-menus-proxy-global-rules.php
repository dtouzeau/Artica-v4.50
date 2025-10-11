<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){die("DIE " .__FILE__." Line: ".__LINE__);}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
xgen();



function xgen(){
	$pagename=CurrentPageName();
	$tpl=new template_admin();
	$users=new usersMenus();
	$f[]="                	<ul class='nav nav-third-level'>";
	
	
	
	
	if($users->AsProxyMonitor){
		$f[]="                			<li id='left-menu'>";
		$f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.outgoing.php');\">
														<i class=\"fas fa-ethernet\"></i> <span class=\"nav-label\">{outgoing_address}</span> </a>";
		$f[]="							</li>";
	
		$f[]="                			<li id='left-menu'>";
		$f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.whitelist.php');\">
														<i class=\"fa fa-thumbs-up\"></i> <span class=\"nav-label\">{whitelist}</span> </a>";
		$f[]="							</li>";
	
		$f[]="                			<li id='left-menu'>";
		$f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.blacklists.php');\">
														<i class=\"fa fa-ban\"></i> <span class=\"nav-label\">{deny_websites}</span> </a>";
		$f[]="							</li>";

        $SquidCachesProxyEnabled = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));



    if ($SquidCachesProxyEnabled == 0) {
            $f[] = "                			<li id='left-menu'>";
            $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.denycache.php');\">
                                                    <i class=\"fa fa-ban\"></i> <span class=\"nav-label\">{deny_from_cache}</span> </a>";
            $f[] = "							</li>";
    }
	}
	
	if(!isset($_SESSION["SQUID_DYNAMIC_ACLS"])){$_SESSION["SQUID_DYNAMIC_ACLS"]=array();}
		
	if(count($_SESSION["SQUID_DYNAMIC_ACLS"])>0){
		reset($_SESSION["SQUID_DYNAMIC_ACLS"]);
		$q=new mysql_squid_builder();
		while (list ($gpid, $val) = each ($_SESSION["SQUID_DYNAMIC_ACLS"]) ){
			$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID=$gpid"));
			//$array[$ligne["GroupName"]]="$page?tab-gpid=$gpid";
	
			$f[]="<li id='left-menu'>";
			$f[]="	<a href='#' OnClick=\"MenuRoot( $(this),'fw.dynacls.rule.php?gpid=$gpid');\">
			<i class=\"fa fa-list-ul\"></i> <span class=\"nav-label\">{$ligne["GroupName"]}</span> </a>";
			$f[]="</li>";
		
		}
		}	
	
	
	
	$f[]="					</ul>";

	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}