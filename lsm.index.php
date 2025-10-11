<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.openssh.inc');
	include_once('ressources/class.user.inc');

	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["LSM_STATUS"])){LSM_STATUS();exit;}
	
	popup();
	
function LSM_STATUS(){
	$sock=new sockets();
	$tpl=new templates();
	
	$page=CurrentPageName();
	$sock->getFrameWork('lsm.php?status=yes');
	$ini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/lsm.status");
	
	
	
	$status=DAEMON_STATUS_ROUND("LinkStatusMonitor",$ini);
	echo $tpl->_ENGINE_parse_body($status.
	"<div style='text-align:right'>". 
			imgtootltip("refresh-32.png",null,"LoadAjax('LSM_STATUS','$page?LSM_STATUS=yes')").
	"</div>"
	);
	
}
	
function status(){
	$page=CurrentPageName();
	
	
	$html="
	<table style='width:100%'>
	<tr>
	<td style='vertical-align:top;width:240px' nowrap><div id='LSM_STATUS'></div></td>
	<td style='vertical-align:top;width:99%'>
		<div style='font-size:18px;margin-bottom:50px'>{LinkStatusMonitorText}</div>
		<center style='margin:50px;padding:50px;width:86%' class=form>
				".button("{disable_feature} {LinkStatusMonitor}","Loadjs('lsm.uninstall.php')",40)."
		</center>
		<hr>
	</td>
	</tr>
	</table>
	<script>
		LoadAjax('LSM_STATUS','$page?LSM_STATUS=yes');
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}	

	function popup(){
		$tpl=new templates();
	
		$LinkStatusMonitorEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinkStatusMonitorEnabled"));
	
	
		if($LinkStatusMonitorEnabled==0){
	
	
			$html="<center style='margin:50px;padding:50px;width:86%' class=form>
				".button("{enable_feature} {LinkStatusMonitor}","Loadjs('lsm.install.php')",40)."
						<div style='margin-top:20px;font-size:20px'>{LinkStatusMonitorText}</div>
			</center>";
			echo $tpl->_ENGINE_parse_body($html);
			return;
	
		}
	
		$array["status"]='{LinkStatusMonitor}';
		$array["rules"]='{rules}';
		$array["events"]='{events}';
		$page=CurrentPageName();
		$tabsize="style='font-size:26px'";
	
		foreach ($array as $num=>$ligne){
	
			if($num=="rules"){
				$html[]= $tpl->_ENGINE_parse_body("<li $tabsize><a href=\"lsm.rules.php\"><span>$ligne</span></a></li>\n");
				continue;
			}
	
			if($num=="events"){
				$html[]= $tpl->_ENGINE_parse_body("<li $tabsize><a href=\"lsm.events.php\"><span>$ligne</span></a></li>\n");
				continue;
					
			}
			if($num=="limit_access"){
				$html[]= $tpl->_ENGINE_parse_body("<li $tabsize><a href=\"sshd.AllowUsers.php\"><span>$ligne</span></a></li>\n");
				continue;
	
			}
	
			$html[]= $tpl->_ENGINE_parse_body("<li $tabsize><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
		}
	
	
		echo build_artica_tabs($html, "main_config_lsm",1490);
	
	}