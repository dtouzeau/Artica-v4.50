<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__).'/ressources/class.elasticssearch.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["klnagchk"])){klnagchk();exit;}
if(isset($_POST["KLNAGENT_SERVER"])){save();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["parameters"])){parameters();exit;}

page();
function page(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$KLNAGENT_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KLNAGENT_VERSION");
	
	$html="
	<div class=\"row border-bottom white-bg dashboard-header\">
	<div class=\"col-sm-12\"><h1 class=ng-binding>{APP_KLNAGENT} v{$KLNAGENT_VERSION}</h1>
	<p>{APP_KLNAGENT_EXPLAIN}</p>
	</div>

	</div>



	<div class='row'><div id='progress-klnagent-restart'></div>
	<div class='ibox-content'>

	<div id='table-loader-klnagent-service'></div>

	</div>
	</div>



	<script>
	$.address.state('/');
	$.address.value('/klnagent');
	LoadAjax('table-loader-klnagent-service','$page?tabs=yes');

	</script>";
	

	if(isset($_GET["main-page"])){
		$tpl=new template_admin(null,$html);
		echo $tpl->build_firewall();
		return;
	}
	

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);

}
function tabs(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$array["{status}"]="$page?table=yes";
	$array["{parameters}"]="$page?parameters=yes";
	echo $tpl->tabs_default($array);

}
function table(){
	$page=CurrentPageName();
	$tpl=new template_admin();
	$html="<table style='width:100%;margin-top:20px'>
	<tr><td valign='top' style='width:346px'><div id='klnagent-status'></div></td>
	<td valign='top'>
			<div id='klnagent-klnagchk'></div>
		</td>
	</tr>
	</table>		
	<script>
		LoadAjaxTiny('klnagent-status','$page?status=yes');
	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function klnagchk(){
	$array=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KLNAGCHK"));
	$page=CurrentPageName();
	$tpl=new template_admin();
	$DIRSIZE_BYTES=$array["DIRSIZE_BYTES"];
	$SERVER_ADDRESS=$array["SERVER_ADDRESS"];
	$SERVER_PORTS=$array["SERVER_PORTS"];
	$USE_SSL=$array["USE_SSL"];
	$SUCC_SYNCS=$array["SUCC_SYNCS"];
	if($USE_SSL==1){$SERVER_PORTS=$array["SERVER_SSL_PORTS"];}
	
	$LAST_PING=$array["LAST_PING"];
	$HOSTIDLEN=strlen($array["HOSTID"]);
	
	$PING_PERIOD_MINUTES=$array["PING_PERIOD_MINUTES"];
	if(preg_match("#\((.*?)\)#", $LAST_PING,$re)){$LAST_PING=$re[1];}
	

	$f[]="<table style='width:100%'>";
	
	$f[]="<tr>";
	$f[]="<td valign='top' style='width:482px;'>";
	$f[]="<div style='margin-top:-9px'>";
	
	if($array["UPDATE_AGENT"]==1){
		$f[]=$tpl->widget_style1("lazur-bg","fas fa-archive","{UPDATE_AGENT}","{yes}");
	}
	if($array["CNX_GW"]==1){
		$f[]=$tpl->widget_style1("lazur-bg","fas fa-compress-alt","{CNXGW_AGENT}","{yes}");
	}
	
	if($DIRSIZE_BYTES>0){
		$DIRSIZE=FormatBytes($DIRSIZE_BYTES/1024);
		$f[]=$tpl->widget_style1("navy-bg","fas fa-hdd","{storage_size}",$DIRSIZE);
		
	}
	if($HOSTIDLEN>10){
		if($array["CNX_GW"]==0){
			$f[]=$tpl->widget_style1("navy-bg","fa fa-server","{KSC_HOSTNAME}","$SERVER_ADDRESS:$SERVER_PORTS");
		}
		$f[]=$tpl->widget_style1("lazur-bg","fal fa-sync-alt","{synchronize_each} $PING_PERIOD_MINUTES {minutes}<br>{NB_SYNC} ","$SUCC_SYNCS");
		$f[]=$tpl->widget_style1("navy-bg","fas fa-alarm-clock","{last_ping}",$LAST_PING);
	}else{
		$f[]=$tpl->widget_style1("red-bg",ico_emergency,"{corrupted_configuration}","{no_hostid}");
	}
	$f[]="</div>";
	$f[]="</td>";
	$f[]="</tr>";
	$f[]="<tr>";
	$f[]="<td valign='top' style='padding-top:10px' colspan=2>";
	$f[]="<table class='table table-striped'>";
	$f[]="<tbody>";
	if($HOSTIDLEN>10){
		$f[]="<tr><td width=1% nowrap align='right'><strong>{uuid}:</strong></td><td>{$array["HOSTID"]}</td></tr>";
	}
	if(intval($array["OPEN_UDP_PORT"])==1){
		$f[]="<tr><td width=1% nowrap align='right'><strong>{udp_port}:</strong></td><td>{$array["UDP_PORTS"]}</td></tr>";
	}
	
	if(intval($array["LISTENING_SSL_PORT"])>1){
		$f[]="<tr><td width=1% nowrap align='right'><strong>{listen_port}:</strong></td><td>{$array["LISTENING_PORT"]}</td></tr>";
		$f[]="<tr><td width=1% nowrap align='right'><strong>{listen_port} (SSL):</strong></td><td>{$array["LISTENING_SSL_PORT"]}</td></tr>";
		
	}

	
	
	$f[]="</tbody>";
	$f[]="</table>";
	$f[]="</tr>";
	$f[]="</tbody>";
	$f[]="</table>";
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $f));

	
/*	[SERVER_ADDRESS] => 192.168.1.46
	[USE_SSL] => 1
	[COMPRESS_TRAFFIC] => 1
	[SERVER_SSL_PORTS] => 13000
	[SERVER_PORTS] => 14000
	[USE_PROXY] => 0
	[CERTIFICATE] => present
	[OPEN_UDP_PORT] => 1
	[UDP_PORTS] => 15000
	[PING_PERIOD_MINUTES] => 15
	[CONN_TIMEOUT_S] => 30
	[RW_TIMEOUT_S] => 180
	[HOSTID] => 3f4499ac-d190-4ff7-a49c-caafd3b6d752
	[PING_COUNT] => 1
	[SUCC_PINGS] => 1
	[SYNC_COUNT] => 1
	[SUCC_SYNCS] => 1
	[LAST_PING] => 05/29/18 10:19:51 GMT (05/29/18 12:19:5
	 */
	
	
}

function parameters(){
	$array=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KLNAGENT"));
	$page=CurrentPageName();
	$tpl=new template_admin();
	if(!isset($array["KLNAGENT_USESSL"])){$array["KLNAGENT_USESSL"]=1;}
	if(!isset($array["KLNAGENT_GW_MODE"])){$array["KLNAGENT_GW_MODE"]=1;}
	
	$KLNAGENT_SERVER=$array["KLNAGENT_SERVER"];
	$KLNAGENT_PORT=intval($array["KLNAGENT_PORT"]);
	$KLNAGENT_SSLPORT=intval($array["KLNAGENT_SSLPORT"]);
	$KLNAGENT_USESSL=intval($array["KLNAGENT_USESSL"]);
	$KLNAGENT_GW_MODE=intval($array["KLNAGENT_GW_MODE"]);
	$KLNAGENT_GW_ADDRESS=trim($array["KLNAGENT_GW_ADDRESS"]);
	
	if($KLNAGENT_SSLPORT==0){$KLNAGENT_SSLPORT=13000;}
	if($KLNAGENT_PORT==0){$KLNAGENT_PORT=14000;}
	if($KLNAGENT_GW_MODE==0){$KLNAGENT_GW_MODE=1;}
	
	
	$form[]=$tpl->field_text("KLNAGENT_SERVER", "{KSC_HOSTNAME}", $KLNAGENT_SERVER);
	$form[]=$tpl->field_numeric("KLNAGENT_PORT","{KSC_PORT}",$KLNAGENT_PORT);
	$form[]=$tpl->field_checkbox("KLNAGENT_USESSL","{UseSSL}",$KLNAGENT_USESSL,"KLNAGENT_SSLPORT");
	$form[]=$tpl->field_numeric("KLNAGENT_SSLPORT","{KSC_SSL_PORT}",$KLNAGENT_SSLPORT);
	
	$form[]=$tpl->field_section(null,"{KLNAGT_SECTION}");
	$CheckActions[1]="<strong>{KLNAGT_1}</strong>";
	$CheckActions[2]="<strong>{KLNAGT_2}</strong>";
	$CheckActions[3]="<strong>{KLNAGT_3}</strong>";
	$CheckActions[4]="<strong>{KLNAGT_4}</strong>";
	
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/klnagent.reconfigure.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/klnagent.reconfigure.progress.txt";
	$ARRAY["CMD"]="klnagent.php?reconfigure=yes";
	$ARRAY["TITLE"]="{reconfigure}";
	$ARRAY["AFTER"]="LoadAjaxTiny('klnagent-status','$page?status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-klnagent-restart')";
	
	
	$form[]=$tpl->field_array_checkboxes($CheckActions, "KLNAGENT_GW_MODE", $KLNAGENT_GW_MODE);
	$form[]=$tpl->field_text("KLNAGENT_GW_ADDRESS", "{KLNAGT_GWADDR}", $KLNAGENT_GW_ADDRESS);
	
	echo $tpl->form_outside("{parameters}", $form,null,"{apply}",$jsrestart,"AsSystemAdministrator");
	
	
}

function save(){
	$tpl=new template_admin();
	$tpl->CLEAN_POST();
	$array=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_KLNAGENT"));
	foreach ($_POST as $key=>$val){
		$array[$key]=$val;
	}
	$sock=new sockets();
	$sock->SaveConfigFile(serialize($array), "APP_KLNAGENT");
}

function status(){
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new template_admin();
	
	$page=CurrentPageName();
	$datas=$sock->getFrameWork("klnagent.php?status=yes");
	$ini=new Bs_IniHandler("/usr/share/artica-postfix/ressources/logs/web/klnagent.status");
	$ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/klnagent.progress";
	$ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/klnagent.progress.txt";
	$ARRAY["CMD"]="klnagent.php?restart=yes";
	$ARRAY["TITLE"]="{restart_service}";
	$ARRAY["AFTER"]="LoadAjaxTiny('klnagent-status','$page?status=yes');";
	$prgress=base64_encode(serialize($ARRAY));
	$jsrestart="Loadjs('fw.progress.php?content=$prgress&mainid=progress-klnagent-restart')";
	echo $tpl->SERVICE_STATUS($ini, "APP_KLNAGENT",$jsrestart);
	echo "	<script>
		LoadAjaxTiny('klnagent-klnagchk','$page?klnagchk=yes');
	
	</script>";
}
