<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die("DIE " .__FILE__." Line: ".__LINE__);exit();
	}
	if(isset($_GET["squid-cache-mem-current"])){squid_cache_mem_current();exit;}
	if(isset($_POST["cache_mem"])){Save();exit;}
	
page();
	
function page(){	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidSimpleConfig=$sock->GET_INFO("SquidSimpleConfig");
	if(!is_numeric($SquidSimpleConfig)){$SquidSimpleConfig=1;}
	
	$meminfo=unserialize(base64_decode($sock->getFrameWork("system.php?meminfo=yes")));
    $squid=new squidbee();
	$cache_mem=$squid->global_conf_array["cache_mem"];
	if(preg_match("#([0-9]+)\s+#", $cache_mem,$re)){$cache_mem=$re[1];}
	

	$SquidMemoryPools=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryPools"));
	
	$memory_pools_limit_suffix=null;
	$SquidMemoryPoolsLimit=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMemoryPoolsLimit"));
	
	$FF=1500;
	$FF=$FF*1024;
	$FF=$FF*1024;
	$proposal=$meminfo["MEMTOTAL"]-$FF;
	$proposal=$proposal/2;
	$proposal=$proposal/1024;
	$proposal=round($proposal/1024);
	
	$html="
	
	<div class=explain style='font-size:16px'>{squid_cache_memory_explain}</div>
	<div style='margin:10px;padding:10px;width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td colspan=3 style='font-size:32px;margin-bottom:20px'>{central_memory}</div>
			<div class=explain style='font-size:18px'>{cache_mem_explain2}</div>
		</td>		
	<tr>
		<td class=legend style='font-size:26px'>{central_memory}:</td>
		<td style='font-size:26px'>". Field_text("cache_mem-$t",$cache_mem,"font-size:26px;width:150px;font-weight:bold")."&nbsp;MB</td>
		<td style='font-size:26px' width=1% nowrap>" . help_icon('{cache_mem_text}',true)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:26px'>{current}:</td>
		<td style='font-size:26px'><span id='squid-cache-mem-current' style='font-size:26px;font-weight:bold'></span></td>
		<td style='font-size:26px' width=1% nowrap>&nbsp;</td>
	</tr>					
	<tr>
	<td colspan=3 style='font-size:20px;margin-bottom:20px;color:#8E8E8E;text-align:right'>{server_memory}: ". FormatBytes($meminfo["MEMTOTAL"]/1024)." ({proposal}: {$proposal}MB)</div>
	</table>
	</div>		
	<div style='margin:10px;padding:10px;width:98%' class=form>	
	<table style='width:100%'>

	<tr>
		<td style='font-size:26px' class=legend>{memory_pools}:</td>
		<td align='left' style='font-size:26px'>" . Field_checkbox_design("SquidMemoryPools-$t", 1,$SquidMemoryPools,"SquidMemoryPools$t()")."</td>
		<td width=1%>" . help_icon('{memory_pools_explain}',true)."</td>
	</tr>
	<tr>
		<td style='font-size:26px' class=legend>{memory_pools_limit}:</td>
		<td align='left' style='font-size:26px'>" . Field_text("SquidMemoryPoolsLimit-$t", $SquidMemoryPoolsLimit,"font-size:26px;width:150px")."&nbsp;MB</td>
		<td width=1%>" . help_icon('{memory_pools_limit_explain}',true)."</td>
	</tr>									
</tr>	
	<tr><td colspan=3 style='text-align:right;pdding-top:50px'><hr>". button("{apply}","Save$t()",36)."</td>
	</tr>
</table>	
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	Loadjs('squid.compile.progress.php?ask=yes');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('cache_mem',document.getElementById('cache_mem-$t').value);
	
	if(document.getElementById('SquidMemoryPools-$t').checked){XHR.appendData('SquidMemoryPools',1);}else{
	XHR.appendData('SquidMemoryPools',0);}
	XHR.appendData('SquidMemoryPoolsLimit',document.getElementById('SquidMemoryPoolsLimit-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
function SquidMemoryPools$t(){
	var SquidSimpleConfig=$SquidSimpleConfig;
	if(SquidSimpleConfig==1){
		document.getElementById('SquidMemoryPools-$t').disabled=true;
		document.getElementById('SquidMemoryPoolsLimit-$t').disabled=true;
		return;
	}

	document.getElementById('SquidMemoryPoolsLimit-$t').disabled=true;
	if(document.getElementById('SquidMemoryPools-$t').checked){
		document.getElementById('SquidMemoryPoolsLimit-$t').disabled=false;
	}
	
	LoadAjax('squid-cache-mem-current','$page?squid-cache-mem-current=yes');
	
}
SquidMemoryPools$t();
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function Save(){
	$squid=new squidbee();
	$sock=new sockets();
	
	$sock->SET_INFO("SquidMemoryPoolsLimit",$_POST["SquidMemoryPoolsLimit"]);
	$sock->SET_INFO("SquidMemoryPoolsLimit",$_POST["SquidMemoryPoolsLimit"]);
	
	if(is_numeric($_POST["cache_mem"])){
		$squid->global_conf_array["cache_mem"]=trim($_POST["cache_mem"])." MB";
	}	
	
	
	$squid->SaveToLdap(true);

}

function squid_cache_mem_current(){
	if(!is_file("/usr/share/artica-postfix/ressources/logs/web/Storage.Mem.capacity")){echo "-";return;}
	$data=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/Storage.Mem.capacity"));
	$tpl=new templates();
	$data["CUR"]=FormatBytes($data["CUR"]);
	echo $tpl->_ENGINE_parse_body("{used} {$data["USED"]}% ({$data["CUR"]}), {free} {$data["FREE"]}%");
	
}