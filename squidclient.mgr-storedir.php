<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');


if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["squidclient-mgr-storedir"])){squidclient_mgr_storedir();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{caches}: {display_details}");
	echo "YahooWin(1024,'$page?popup=yes','$title');";
	$sock=new sockets();
	$sock->getFrameWork("squid2.php?squidclient-mgr-storedir=yes");
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$sock->getFrameWork("squid2.php?squidclient-mgr-storedir=yes");
	echo "<div id='squidclient-mgr-storedir'></div>
	
	<script>
		function FsquidclientMgrStoreDir(){
			if(!YahooWinOpen()){return;}
			LoadAjax('squidclient-mgr-storedir','$page?squidclient-mgr-storedir=yes');
		}
		setTimeout(\"FsquidclientMgrStoreDir()\",800);
		</script>		
			
	";
	
	
	
}
function squidclient_mgr_storedir(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/storedir.cache")){
		echo "<script>setTimeout(\"FsquidclientMgrStoreDir()\",800);</script>";
		return;
	}
	
	echo "<textarea style='width:100%;height:450px;font-family:monospace;
	overflow:auto;font-size:13px;border:4px solid #CCCCCC;background-color:transparent' 
	id='none'>".@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/storedir.cache")."</textarea>";
	
}

