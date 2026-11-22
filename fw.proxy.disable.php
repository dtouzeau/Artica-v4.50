<?php
if(isset($_SESSION["uid"])){header("content-type: application/x-javascript");echo "document.location.href='logoff.php'";exit();}
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$tpl=new template_admin();if(!$tpl->xPrivs()){exit();}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["button-ufdbg"])){button_ufdbg();exit;}
if(isset($_GET["button-no-cache"])){button_nocache();exit;}
if(isset($_GET["caching-toggle"])){caching_toggle_js();exit;}
if(isset($_POST["caching-toggle"])){caching_toggle_perform();exit;}
if(isset($_GET["button-hypercache-disable"])){button_hypercache_disable();exit;}
if(isset($_GET["hypercache-dedup-toggle"])){hypercache_dedup_toggle_js();exit;}
if(isset($_POST["hypercache-dedup-toggle"])){hypercache_dedup_toggle_perform();exit;}
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

/**
 * Une rangee d'actions.
 *
 * Ces boutons tenaient dans des tableaux de mise en page dont chaque cellule
 * etait declaree "width:1%" et "nowrap": la rangee ne pouvait pas se replier et
 * debordait des que la boite depassait quatre boutons. Le flex les enroule tout
 * seul.
 */
function pxdis_actions_row($cells):string{
    $out=array();
    foreach($cells as $c){
        if(trim((string)$c)===""){continue;}
        $out[]="<div style='flex:0 0 auto'>$c</div>";
    }
    if(count($out)==0){return "";}
    return "<div style='display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start;margin:20px 0 10px 0'>"
        .@implode("",$out)."</div>";
}
function popup(){
	$tpl=new template_admin();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));
	$HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));

	$UFDB=false;
	if($users->APP_UFDBGUARD_INSTALLED){
		$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
		if($EnableUfdbGuard==1){$UFDB=true;}
	}

    $btwidth=btwidth();

	$html[]="<div class='row'><div id='progress-action-restart'></div>
	<div class='ibox-content'>";
	
	if($users->AsSystemAdministrator){
	// La rangee portait un "</td>" ORPHELIN avant sa fermeture de ligne.
	$cells=array();
	$cells[]="<button class='btn btn-warning btn-rounded' type='button' style='$btwidth' OnClick=\"Loadjs('fw.system.restart.php');\">"
		."<i class='fal fa-sync-alt fa-2x'></i>&nbsp;&nbsp;&nbsp;{reboot_system}</button>";
	$cells[]="<button class='btn btn-danger btn-rounded' type='button' style='$btwidth' OnClick=\"Loadjs('fw.system.restart.php?reset=yes');\">"
		."<i class='fa-solid fa-power-off fa-2x'></i>&nbsp;&nbsp;&nbsp;{reset_system}</button>";
	$cells[]="<button class='btn btn-primary btn-rounded' type='button' style='$btwidth' OnClick=\"Loadjs('fw.system.rootpwd.php');\">"
		."<i class='fa-solid fa-user-crown fa-2x'></i>&nbsp;&nbsp;&nbsp;{root_password2}</button>";
	$html[]="<H1>{system}</H1>".pxdis_actions_row($cells);
	
	}
	
	
	

	$SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
	$SquidCachesProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));
	if($SQUIDEnable==0){$users->SQUID_INSTALLED=false;}
	
	
if($users->SQUID_INSTALLED){
	if($users->AsProxyMonitor){
		// La rangee etait ouverte ICI (<table><tr>) mais fermee HORS des trois
		// conditions imbriquees: proxy non installe ou droits insuffisants, la
		// page sortait un "</tr></table>" orphelin. Les cellules sont
		// desormais accumulees puis rendues en une fois.
		$cells=array();

		if($UFDB){
			$cells[]="<div id='button-ufdbg'></div>";
		}else{
			$cells[]="<button class='btn btn-default btn-rounded' type='button' style='$btwidth' "
				."OnClick=\"javascript:blur()\">"
				."<i class='fa fa-stop fa-2x'></i>&nbsp;&nbsp;&nbsp;{web_filtering}: {disabled}</button>";
		}

		if($SquidCachesProxyEnabled==1){
			if($SquidCacheLevel==0){
				$HyperCacheStoreID=0;
				$cells[]="<button class='btn btn-default btn-rounded' type='button' style='$btwidth' "
					."OnClick=\"javascript:blur()\">"
					."<i class='fa fa-stop fa-2x'></i>&nbsp;&nbsp;&nbsp;{cache_engine}: {disabled}</button>";
			}else{
				$cells[]="<div id='button-no-cache'></div>";
			}

			if($HyperCacheStoreID==0){
				$cells[]="<button class='btn btn-default btn-rounded' type='button' style='$btwidth' "
					."OnClick=\"javascript:blur()\">"
					."<i class='fa fa-stop fa-2x'></i>&nbsp;&nbsp;&nbsp;HyperCache DEDUP: {disabled}</button>";
			}else{
				$cells[]="<div id='button-hypercache-disable'></div>";
			}
		}

		$html[]="<H1>{your_proxy}</H1>";
		$html[]="<div id='go-shield-quick'></div>";
		$html[]=pxdis_actions_row($cells);
	}
}

	$html[]="</div></div>
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


    if ($UfdbGuardDisabledTemp == 1) {
        $js="Loadjs('fw.go.shield.connector.php?enable-ufdbguard-temp=yes')";
        $button="<button class='btn btn-danger btn-rounded' type='button' style='$btwidth' OnClick=\"$js\">"
            ."<i class='fa-solid fa-filters fa-2x'></i>&nbsp;&nbsp;&nbsp;{web_filtering} ({stopped})</button>";
    }else{
        $js="Loadjs('fw.go.shield.connector.php?enable-ufdbguard-temp=no')";
        $button="<button class='btn btn-primary btn-rounded' type='button' style='$btwidth' OnClick=\"$js\">"
            ."<i class='fa-solid fa-filters fa-2x'></i>&nbsp;&nbsp;&nbsp;{web_filtering}</button>";
    }

    echo $tpl->_ENGINE_parse_body(pxdis_actions_row(array($button)));
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
	
	// Pause / resume the deduplication without disabling HyperCache (SquidDisableHyperCacheDedup).
	// The button used to load squid.hypercache-dedup.disable.php, a page that never existed.
	$page=CurrentPageName();
	$toggle="Loadjs('$page?hypercache-dedup-toggle=yes');";
	if($SquidDisableHyperCacheDedup==0){
		echo $tpl->_ENGINE_parse_body("<button class='btn btn-primary btn-rounded' type='button' style='$btwidth'
				OnClick=\"$toggle\">
				<i class='fa fa-play fa-2x'></i>&nbsp;&nbsp;&nbsp;{disable_hypercache}</button>
				<div id='progress-hypercache-dedup'></div>");

		}else{
		echo $tpl->_ENGINE_parse_body("<button class='btn btn-warning btn-rounded' type='button' style='$btwidth'
				OnClick=\"$toggle\">
				<i class='fa fa-pause fa-2x'></i>&nbsp;&nbsp;&nbsp;{enable_hypercache}</button>
				<div id='progress-hypercache-dedup'></div>");
		}

}

/**
 * Confirmation, then the HyperCache rebuild by articarest (/proxy/hypercache/apply: StoreID.conf,
 * squid -k parse, reload only when it changed; a configuration Squid rejects is rolled back).
 */
function hypercache_dedup_toggle_js():bool{
	$users=new usersMenus();
	$tpl=new template_admin();
	if(!$users->AsSquidAdministrator){$tpl->js_no_privileges();return false;}
	$page=CurrentPageName();
	$paused=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableHyperCacheDedup"))==1;
	$text=$paused?"{enable_hypercache}":"{disable_hypercache}";
	$refresh="LoadAjaxSilent('button-hypercache-disable','$page?button-hypercache-disable=yes')";
	$jsApply=$tpl->framework_buildjs(
		"/proxy/hypercache/apply",
		"squid.hypercache.progress",
		"squid.hypercache.progress.log",
		"progress-hypercache-dedup",
		$refresh
	);
	$tpl->js_dialog_confirm_action($text,"hypercache-dedup-toggle",$paused?"resume":"pause",$jsApply);
	return true;
}

function hypercache_dedup_toggle_perform():bool{
	$users=new usersMenus();
	$tpl=new template_admin();
	if(!$users->AsSquidAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$tpl->CLEAN_POST();
	$value=trim((string)$_POST["hypercache-dedup-toggle"]);
	if($value!=="pause" && $value!=="resume"){echo $tpl->post_error("invalid value");return false;}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDisableHyperCacheDedup",$value==="pause"?1:0);
	admin_tracks($value==="pause"?"HyperCache deduplication paused":"HyperCache deduplication resumed");
	return true;
}

function button_nocache(){
	$users=new usersMenus();
	if(!$users->SQUID_INSTALLED){return;}
	if(!$users->AsProxyMonitor){return;}
	$page=CurrentPageName();
	$btwidth="width:230px;height:62px;";
	$tpl=new template_admin();
	$SquidDisableCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableCaching"));

	// Temporarily disable / enable caching (SquidDisableCaching). The button used to load
	// squid.cache.disable.php, a page that never existed.
	$toggle="Loadjs('$page?caching-toggle=yes');";
	if($SquidDisableCaching==0){
		echo $tpl->_ENGINE_parse_body("<button class='btn btn-primary btn-rounded' type='button' style='$btwidth'
				OnClick=\"$toggle\">
				<i class='fa fa-play fa-2x'></i>&nbsp;&nbsp;&nbsp;{disable_caching}</button>
				<div id='progress-caching-toggle'></div>");

	}else{
	echo $tpl->_ENGINE_parse_body("<button class='btn btn-warning btn-rounded' type='button' style='$btwidth'
			OnClick=\"$toggle\">
			<i class='fa fa-pause fa-2x'></i>&nbsp;&nbsp;&nbsp;{enable_caching_squid}</button>
			<div id='progress-caching-toggle'></div>");
	}
	
	
	echo "<script>LoadAjaxSilent('button-hypercache-disable','$page?button-hypercache-disable=yes');</script>";
}

/**
 * Confirmation, then the caches rebuild by articarest (/proxy/caches/verify/reload: caches.conf,
 * StoreID.conf (HyperCache stops with caching), cache directories, reload of the proxy).
 */
function caching_toggle_js():bool{
	$users=new usersMenus();
	$tpl=new template_admin();
	if(!$users->AsSquidAdministrator){$tpl->js_no_privileges();return false;}
	$page=CurrentPageName();
	$disabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableCaching"))==1;
	$text=$disabled?"{enable_caching_squid}":"{disable_caching}";
	$refresh="LoadAjaxSilent('button-no-cache','$page?button-no-cache=yes')";
	$jsApply=$tpl->framework_buildjs(
		"/proxy/caches/verify/reload",
		"squid.caches.progress",
		"squid.caches.progress.log",
		"progress-caching-toggle",
		$refresh
	);
	$tpl->js_dialog_confirm_action($text,"caching-toggle",$disabled?"enable":"disable",$jsApply);
	return true;
}

function caching_toggle_perform():bool{
	$users=new usersMenus();
	$tpl=new template_admin();
	if(!$users->AsSquidAdministrator){echo $tpl->post_error("{ERROR_NO_PRIVS2}");return false;}
	$tpl->CLEAN_POST();
	$value=trim((string)$_POST["caching-toggle"]);
	if($value!=="disable" && $value!=="enable"){echo $tpl->post_error("invalid value");return false;}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDisableCaching",$value==="disable"?1:0);
	// refresh_pattern, caches.conf and timeouts.conf, written in the background by articarest (like the
	// "Enable caching" switch of the proxy general settings). The reload is done by the confirmation's
	// /proxy/caches/verify/reload job, which first runs squid -z: these files are written by then.
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/ccm");
	admin_tracks($value==="disable"?"Proxy caching temporarily disabled":"Proxy caching enabled again");
	return true;
}
