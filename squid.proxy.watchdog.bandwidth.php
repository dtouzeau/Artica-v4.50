<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.status.inc');
include_once('ressources/class.squid.watchdog.inc');

$users=new usersMenus();
if(!$users->AsProxyMonitor){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die("DIE " .__FILE__." Line: ".__LINE__);

}

PAGE();

function PAGE(){
	$t=time();
	$watchdog=new squid_watchdog();
	$MonitConfig=$watchdog->MonitConfig;
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($SquidPerformance>1){
		echo FATAL_ERROR_SHOW_128("{SQUID_LOCAL_STATS_DISABLED}");
	}
	

	$per[5]="5 {minutes}";
	$per[10]="10 {minutes}";
	$per[30]="30 {minutes}";
	$per[60]="1 {hour}";
	$per[120]="2 {hours}";
	$per[360]="6 {hours}";
	$per[720]="12 {hours}";
	$per[1440]="1 {day}";

	$html="
<div style='width:98%' class=form>
". Paragraphe_switch_img("{bandwidth_alert}", "{bandwidth_alert_explain}",
"CHECK_BANDWITDH",$MonitConfig["CHECK_BANDWITDH"],null,990)."

<table style='width:100%'>
	<tr>
		<td style='font-size:24px' class=legend>{interval}:</td>
		<td style='vertical-align:top;font-size:24px;'>". Field_array_Hash($per,
				"CHECK_BANDWITDH_INTERVAL",$MonitConfig["CHECK_BANDWITDH_INTERVAL"],"blur()",null,0,
				"font-size:24px;")."</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:24px'>{max_size}:</td>
		<td	style='font-size:18px'>". Field_text("CHECK_BANDWITDH_SIZE",
				$MonitConfig["CHECK_BANDWITDH_SIZE"],"font-size:24px;width:250px")."&nbsp;MB</td>
		<td width=1% style='font-size:24px'></td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","Save$t()",36)."</td>
	</tr>
</table>
</div>
<script>
	var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SAVEGLOBAL','yes');
	XHR.appendData('CHECK_BANDWITDH',document.getElementById('CHECK_BANDWITDH').value);
	XHR.appendData('CHECK_BANDWITDH_INTERVAL',document.getElementById('CHECK_BANDWITDH_INTERVAL').value);
	XHR.appendData('CHECK_BANDWITDH_SIZE',document.getElementById('CHECK_BANDWITDH_SIZE').value);
	XHR.sendAndLoad('squid.proxy.watchdog.php', 'POST',xSave$t);
}
</script>
";

echo $tpl->_ENGINE_parse_body($html);
}