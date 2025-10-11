<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once('ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');

$users=new usersMenus();
if(!$users->AsSystemAdministrator){die("DIE " .__FILE__." Line: ".__LINE__);}

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["status_daemons"])){status_daemons();exit;}
if(isset($_GET["main"])){main();exit;}
if(isset($_POST["SnortRulesCode"])){EnableSuricata();exit;}

tabs();
function tabs(){
	$UlogdEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UlogdEnabled"));
	
	if($UlogdEnabled==0){
		$tpl=new templates();
		$html="
		<div style='font-size:40px;margin-bottom:30px'>{APP_ULOGD}</div>
		<div style='width:98%' class=form>
		<center style='margin:50px;text-align:center'>".button("{enable_feature}" , "Loadjs('ulogd.enable.progress.php')",40)."</center>
		<center style='margin:50px;font-size:24px;padding:100px'>{APP_ULOGD_EXPLAIN}</center>
		</div>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
		
		
	}
	
	$tpl=new templates();
	$array["status"]='{status}';
	/*$array["interfaces"]='{network_interfaces}';
	$array["rules"]='{rules}';
	$array["signatures"]='{signatures}';
	$array["firewall"]='{firewall}';*/
	$page=CurrentPageName();

	$t=time();
	foreach ($array as $num=>$ligne){
		
		if($num=="interfaces"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"suricata.interfaces.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"suricata.rules.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="signatures"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"suricata.signatures.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}
		if($num=="firewall"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"suricata.firewall.php\"><span style='font-size:22px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:22px'>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "suricata-tabs");
}


function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$html="<div style='width:98%' class=form>
	<div style='font-size:30px;margin-bottom:30px'>{APP_ULOGD}</div>
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:450px'><div id='ulogd-status'></div></td>
		<td valign='top' style='width:1000px'><div id='ulogd-mainc'></div></td>
	</tr>
	</table>		
	<script>
		LoadAjax('ulogd-mainc','$page?main=yes');
		LoadAjax('ulogd-status','$page?status_daemons=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function main(){
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$SuricataInterface=$sock->GET_INFO("SuricataInterface");
	if($SuricataInterface==null){$SuricataInterface="eth0";}
	$SnortRulesCode=$sock->GET_INFO("SnortRulesCode");
	$SuricataFirewallPurges=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataFirewallPurges"));
	if($SuricataFirewallPurges==0){$SuricataFirewallPurges=24;}
	$SuricataPurge=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataPurge"));

	$curs="OnMouseOver=\"this.style.cursor='pointer';\"
	OnMouseOut=\"this.style.cursor='auto'\"";
	
	if($SuricataPurge==0){$SuricataPurge=15;}
	
	$ip=new networking();
	
	$interfaces=$ip->Local_interfaces();
	unset($interfaces["lo"]);
	
	$array[null]="{all}";
	$array2[null]="{all}";
	foreach ($interfaces as $eth){
		if(preg_match("#^gre#", $eth)){continue;}
		$nic=new system_nic($eth);
		if($nic->enabled==0){continue;}
		$array[$eth]="$eth $nic->IPADDR - $nic->NICNAME";
	
	}
	
	if(is_file("{$GLOBALS["BASEDIR"]}/suricata.dashboard")){
		$IDS_SEVERITIES=unserialize(@file_get_contents("{$GLOBALS["BASEDIR"]}/suricata.dashboard"));
		if(isset($IDS_SEVERITIES["SEVERITIES"][1])){
			IF($IDS_SEVERITIES["SEVERITIES"][1]>0){
				$IDS_ROW="
				<tr>
				<td style='font-size:22px;'class=legend>{events}:</td>
				<td style='font-size:22px;text-decoration:underline' OnClick=\"javascript:GotoSuricataEvents()\" $curs><strong>{$IDS_SEVERITIES["SEVERITIES"][1]}</strong> IDS {detected_rules}</td>
				</tr>";
	
			}
				
		}
				
	}else{
		$IDS_ROW="
		<tr>
		<td style='font-size:22px;' class=legend>{events}:</td>
		<td style='font-size:22px;text-decoration:underline' OnClick=\"javascript:GotoSuricataEvents()\" $curs><strong>{not_calculated}</strong> IDS {detected_rules}</td>
		</tr>";
		
	}
	
	$SuricataPurges[7]="7 {days}";
	$SuricataPurges[15]="15 {days}";
	$SuricataPurges[30]="1 {month}";
	$SuricataPurges[90]="3 {months}";
	$SuricataPurges[180]="6 {months}";
	
	
	$SuricateRulesTTL[5]="5 {hours}";
	$SuricateRulesTTL[24]="1 {day}";
	$SuricateRulesTTL[48]="2 {days}";
	$SuricateRulesTTL[120]="5 {days}";
	$SuricateRulesTTL[168]="1 {week}";
	
	
	
	$html="<center style='margin:20px;text-align:center'>".button("{disable_feature}" , "Loadjs('ulogd.disable.progress.php')",40)."</center>

			
<script>
var xSave$t= function (obj) {
	Loadjs('suricata.progress.php');
}		
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('SnortRulesCode',document.getElementById('SnortRulesCode').value);
	XHR.appendData('SuricataInterface',document.getElementById('SuricataInterface').value);
	XHR.appendData('SuricataPurges',document.getElementById('SuricataPurges').value);
	
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}			
</script>";
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function status_daemons(){
	$sock=new sockets();
	$sock->getFrameWork("ulogd.php?status=yes");
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini->loadFile(PROGRESS_DIR."/ulogd.status");
	$page=CurrentPageName();
	$tpl=new templates();
	
	$serv[]=DAEMON_STATUS_ROUND("APP_ULOGD",$ini,null,0);
	$serv[]="<div style='text-align:right;margin-top:20px'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('ulogd-status','$page?status_daemons=yes',true);")."</div>";
	echo $tpl->_ENGINE_parse_body(@implode("<br>", $serv));
	
}

function EnableSuricata(){
	$sock=new sockets();
	$sock->SET_INFO("SnortRulesCode", $_POST["SnortRulesCode"]);
	$sock->SET_INFO("SuricataInterface", $_POST["SuricataInterface"]);
	$sock->SET_INFO("SuricataPurges", $_POST["SuricataPurges"]);
}