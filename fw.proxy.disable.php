<?php
if(isset($_SESSION["uid"])){header("content-type: application/x-javascript");echo "document.location.href='logoff.php'";exit();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["button-ufdbg"])){button_ufdbg();exit;}
if(isset($_GET["button-no-cache"])){button_nocache();exit;}
if(isset($_GET["button-hypercache-disable"])){button_hypercache_disable();exit;}
if(isset($_GET["go-shield-quick"])){button_go_shield_quick();exit;}
js();

function js(){
	
	$page=CurrentPageName();
	$tpl=new template_admin();
	$tpl->js_dialog("{actions}", "$page?popup=yes");
	
	
}

function btwidth(){
    $btwidth="width:230px;height:62px;";
    return $btwidth;
}
function popup(){
	$tpl=new template_admin();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));
	$SquidDisableHyperCacheDedup=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableHyperCacheDedup"));
	$HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));
	$SquidDisableCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableCaching"));
	$UFDB=false;
	if($users->APP_UFDBGUARD_INSTALLED){
		$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
		if($EnableUfdbGuard==1){$UFDB=true;}
	}

    $btwidth=btwidth();

	$html[]="<div class='row'><div id='progress-action-restart'></div>
	<div class='ibox-content'>";
	
	if($users->AsSystemAdministrator){
	$html[]="
<H1>{system}</H1>
<table style='width:100%;margin-bottom:10px;margin:20px'>

	<tr>
	<td style='width:1%;vertical-align:top;padding-left:10px' nowrap>
		<button class='btn btn-warning btn-rounded' type='button' style='$btwidth' OnClick=\"Loadjs('fw.system.restart.php');\">
		<i class='fal fa-sync-alt fa-2x'></i>&nbsp;&nbsp;&nbsp;{reboot_system}</button>
	</td>	
	<td style='width:1%;vertical-align:top;padding-left:10px' nowrap>
	
		<button class='btn btn-danger btn-rounded' type='button' style='$btwidth' OnClick=\"Loadjs('fw.system.restart.php?reset=yes');\">
		<i class='fa-solid fa-power-off fa-2x'></i>&nbsp;&nbsp;&nbsp;{reset_system}</button>
	</td>	
	<td style='width:1%;vertical-align:top;padding-left:10px' nowrap>
		<button class='btn btn-primary btn-rounded' type='button' style='$btwidth' OnClick=\"Loadjs('fw.system.rootpwd.php');\">
		<i class='fa-solid fa-user-crown fa-2x'></i>&nbsp;&nbsp;&nbsp;{root_password2}</button>
	</td>	
	
	
	</td>
	</tr>
	</table>

	";
	
	}
	
	
	

	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	$SquidCachesProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));
	if($SQUIDEnable==0){$users->SQUID_INSTALLED=false;}
	
	
if($users->SQUID_INSTALLED){	
	if($users->AsProxyMonitor){
		$html[]="
<H1>{your_proxy}</H1>
<div id='go-shield-quick'></div>

<table style='width:100%'>
	<tr>";
				
			if($UFDB){
					$html[]="
					
						<td style='width:1%;vertical-align:top;padding-left:10px' nowrap>
						<div id='button-ufdbg'></div>
						
					</td>
					";
			}else{
				$html[]="
						<td style='width:1%;vertical-align:top' nowrap>
						<button class='btn btn-default btn-rounded' type='button' style='$btwidth' 
						OnClick=\"javascript:blur()\">
						<i class='fa fa-stop fa-2x'></i>&nbsp;&nbsp;&nbsp;{web_filtering}: {disabled}</button>
					</td>
					";
				
			}
	
	if($SquidCachesProxyEnabled==1){	
		if($SquidCacheLevel==0){
				$HyperCacheStoreID=0;
				$html[]="
				
				<td style='width:1%;vertical-align:top;padding-left:10px' nowrap><button class='btn btn-default btn-rounded' type='button' style='$btwidth' OnClick=\"javascript:blur()\">
				<i class='fa fa-stop fa-2x'></i>&nbsp;&nbsp;&nbsp;{cache_engine}: {disabled}</button>
				</td>
				";		
				
				
			}else{
				
				$html[]="
					<td style='width:1%;vertical-align:top;padding-left:10px' nowrap>
						<div id='button-no-cache'></div>
					</td>
					";	
			}
			
			
			
			if($HyperCacheStoreID==0){
				$html[]="
				
				<td style='width:1%;vertical-align:top;padding-left:10px' nowrap><button class='btn btn-default btn-rounded' type='button' style='$btwidth' OnClick=\"javascript:blur()\">
				<i class='fa fa-stop fa-2x'></i>&nbsp;&nbsp;&nbsp;HyperCache DEDUP: {disabled}</button>
				</td>
				";		
				
				
			}else{
				$html[]="
					<td style='width:1%;vertical-align:top;padding-left:10px' nowrap>
						<div id='button-hypercache-disable'></div>
					</td>
					";
			}
			
		}
	}
}	
	//squid.hypercache-dedup.disable.php
	$html[]="</tr>";
	

	$html[]="</table></div>
	<script>
		LoadAjax('button-ufdbg','fw.proxy.disable.php?button-ufdbg=yes');
		LoadAjaxSilent('button-no-cache','$page?button-no-cache=yes');
		LoadAjaxSilent('button-hypercache-disable','$page?button-hypercache-disable=yes');
        LoadAjaxSilent('go-shield-quick','$page?go-shield-quick=yes');
	</script>
			
	
			
	";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));

}

function button_go_shield_quick(){
    $btwidth=btwidth();
    $tpl=new template_admin();

    $UfdbGuardDisabledTemp = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardDisabledTemp"));


    $html[]="<table style='width:100%;margin-bottom:10px;margin:20px'>";
    $html[]="<tr>";
    $html[]="<td style='width:1%;vertical-align:top;padding-left:10px' nowrap>";
    if ($UfdbGuardDisabledTemp == 1) {
        $js="Loadjs('fw.go.shield.connector.php?enable-ufdbguard-temp=yes')";
        $html[] = "<button class='btn btn-danger btn-rounded' type='button' style='$btwidth' 
                OnClick=\"$js\">";
        $html[]="<i class='fa-solid fa-filters fa-2x'></i>&nbsp;&nbsp;&nbsp;{web_filtering} ({stopped})</button>";
    }else{
        $js="Loadjs('fw.go.shield.connector.php?enable-ufdbguard-temp=no')";
        $html[] = "<button class='btn btn-primary btn-rounded' type='button' style='$btwidth' 
                OnClick=\"$js\">";
        $html[]="<i class='fa-solid fa-filters fa-2x'></i>&nbsp;&nbsp;&nbsp;{web_filtering}</button>";
    }

    $html[]="</td>";
    $html[]="</tr>";
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function button_ufdbg(){
return false;
}

function button_hypercache_disable(){
	$users=new usersMenus();
	if(!$users->SQUID_INSTALLED){return;}
	if(!$users->AsProxyMonitor){return;}
	$btwidth="width:230px;height:62px;";
	$tpl=new template_admin();
	$SquidDisableCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableCaching"));
	$SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));
	$SquidDisableHyperCacheDedup=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableHyperCacheDedup"));
	if($SquidDisableCaching==1){
	echo $tpl->_ENGINE_parse_body("<button class='btn btn-default btn-rounded' type='button' style='$btwidth' OnClick=\"javascript:blur()\">
		<i class='fa fa-stop fa-2x'></i>&nbsp;&nbsp;&nbsp;HyperCache DEDUP: {disabled}</button>");
		return ;
	}
	if($SquidCacheLevel==0){
		echo $tpl->_ENGINE_parse_body("<button class='btn btn-default btn-rounded' type='button' style='$btwidth' OnClick=\"javascript:blur()\">
				<i class='fa fa-stop fa-2x'></i>&nbsp;&nbsp;&nbsp;HyperCache DEDUP: {disabled}</button>");
		return ;
	}	
	
	if($SquidDisableHyperCacheDedup==0){
		echo $tpl->_ENGINE_parse_body("<button class='btn btn-primary btn-rounded' type='button' style='$btwidth'
				OnClick=\"Loadjs('squid.hypercache-dedup.disable.php');\">
				<i class='fa fa-play fa-2x'></i>&nbsp;&nbsp;&nbsp;{disable_hypercache}</button>");
		
		}else{
		echo $tpl->_ENGINE_parse_body("<button class='btn btn-warning btn-rounded' type='button' style='$btwidth'
				OnClick=\"Loadjs('squid.hypercache-dedup.disable.php');\">
				<i class='fa fa-pause fa-2x'></i>&nbsp;&nbsp;&nbsp;{enable_hypercache}</button>");
		}
	
}

function button_nocache(){
	$users=new usersMenus();
	if(!$users->SQUID_INSTALLED){return;}
	if(!$users->AsProxyMonitor){return;}
	$page=CurrentPageName();
	$btwidth="width:230px;height:62px;";
	$tpl=new template_admin();
	$SquidDisableCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableCaching"));

	if($SquidDisableCaching==0){
		echo $tpl->_ENGINE_parse_body("<button class='btn btn-primary btn-rounded' type='button' style='$btwidth' 
				OnClick=\"Loadjs('squid.cache.disable.php');\">
				<i class='fa fa-play fa-2x'></i>&nbsp;&nbsp;&nbsp;{disable_caching}</button>");
	
	}else{
	echo $tpl->_ENGINE_parse_body("<button class='btn btn-warning btn-rounded' type='button' style='$btwidth' 
			OnClick=\"Loadjs('squid.cache.disable.php');\">
			<i class='fa fa-pause fa-2x'></i>&nbsp;&nbsp;&nbsp;{enable_caching_squid}</button>");
	}
	
	
	echo "<script>LoadAjaxSilent('button-hypercache-disable','$page?button-hypercache-disable=yes');</script>";
}
