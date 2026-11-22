<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.acls.backend.inc");
$tpl=new template_admin();if(!$tpl->xPrivs()){die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

clean_xss_deep();
xgen();



function xgen(){
	$users=new usersMenus();
	$pagename=CurrentPageName();
	$tpl=new template_admin();
	$f[]="                	<ul class='nav nav-third-level'>";
	$results=acls_api_get("/proxy/logs/acls");
	if(!is_array($results)){$results=array();}
	$logacls=array();
	foreach($results as $ligne){
		if(!is_array($ligne)){continue;}
		if(intval($ligne["enabled"]??0)<>1){continue;}
		if(intval($ligne["logType"]??0)<>0){continue;}
		$logacls[]=$ligne;
	}
	usort($logacls,function($a,$b){return strcasecmp((string)($a["ruleName"]??""),(string)($b["ruleName"]??""));});
	foreach ($logacls as $ligne){
		$aclid=intval($ligne["id"]??0);
		$rulename=(string)($ligne["ruleName"]??"");
		$logconfig=@unserialize(base64_decode((string)($ligne["logConfig"]??"")));
		$LOGFILENAME=is_array($logconfig)?($logconfig["LOGFILENAME"]??null):null;
		if($LOGFILENAME==null){$LOGFILENAME="access{$aclid}.log";}
		$LOGFILENAME=urlencode($LOGFILENAME);
		$len=strlen($rulename);
		if($len>18){$rulename=substr($rulename,0,15)."...";}
		$f[]="                			<li id='left-menu'>";
		$f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.relatime.php?logfile=$LOGFILENAME');\">
		<i class=\"fa fa-eye\"></i> <span class=\"nav-label\">$rulename</span> </a>";
		$f[]="							</li>";
	}

    $SHOW_REALTIME=true;
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    $SquidNoAccessLogs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidNoAccessLogs"));
    $LogsWarninStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogsWarninStop"));

    if($HaClusterClient==1) {
        $HaClusterGBConfig = unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterGBConfig"));
        if(!is_array($HaClusterGBConfig)){
            $HaClusterGBConfig=array();
        }
        if(!isset($HaClusterGBConfig["HaClusterRemoveRealtimeLogs"])){
            $HaClusterGBConfig["HaClusterRemoveRealtimeLogs"]=0;
        }
        $HaClusterRemoveRealtimeLogs = intval($HaClusterGBConfig["HaClusterRemoveRealtimeLogs"]);
        if($HaClusterRemoveRealtimeLogs==1){$SHOW_REALTIME=false;}
    }
    if($SquidNoAccessLogs==1){$SHOW_REALTIME=false;}
    if($LogsWarninStop==1){$SHOW_REALTIME=false;}

	
		$f[]="                			<li id='left-menu'>";

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.daemon.php", "ICO" => ico_eye, "TEXT" => "{service_events}",));

        $f[] = $tpl->LeftMenu(array("PAGE" => "fw.proxy.logs.php", "ICO" => "fa fa-list-ul", "TEXT" => "{logs_center}",));


		if($SHOW_REALTIME) {
            $f[] = "                			<li id='left-menu'>";
            $f[] = "                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.relatime.php');\">
													<i class=\"fa fa-eye\"></i> <span class=\"nav-label\">{requests}</span> </a>";
            $f[] = "							</li>";
        }
		
		
		
		$f[]="                			<li id='left-menu'>";
		$f[]="                   			<a href='#' OnClick=\"MenuRoot( $(this),'fw.proxy.cluster.php');\">
													<i class=\"fa fa-eye\"></i> <span class=\"nav-label\">{cluster}</span> </a>";
		$f[]="							</li>";	
	
	
	
	
	
	$f[]="					</ul>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));
}