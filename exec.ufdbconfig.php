<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ufdbguardConfig")));
$RemoteStatisticsApplianceSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteStatisticsApplianceSettings")));
$SquidGuardIPWeb=unserialize(@file_get_contents("/var/log/squid/SquidGuardIPWeb"));
if(is_array($SquidGuardIPWeb)){$GLOBALS["SquidGuardIPWeb"]=$SquidGuardIPWeb["SquidGuardIPWeb"];}

$EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
$UseRemoteUfdbguardService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteUfdbguardService"));
$GLOBALS["SquidGuardIPWeb"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebExternalUri");
$GLOBALS["SquidGuardIPWeb_SSL"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardWebExternalUriSSL");



if(!isset($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=4;}
$GLOBALS["url_rewrite_concurrency"]=$datas["url_rewrite_children_concurrency"];



$GLOBALS["UfdbgclientSockTimeOut"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbgclientSockTimeOut"));
if($GLOBALS["UfdbgclientSockTimeOut"]==0){$GLOBALS["UfdbgclientSockTimeOut"]=2;}
$GLOBALS["UfdbgclientMaxSockTimeOut"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbgclientMaxSockTimeOut"));
if($GLOBALS["UfdbgclientMaxSockTimeOut"]==0){$GLOBALS["UfdbgclientMaxSockTimeOut"]=5;}
$RemoteUfdbguardAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteUfdbguardAddr"));
$RemoteUfdbguardPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteUfdbguardPort"));

if($RemoteUfdbguardPort==0){$RemoteUfdbguardPort=3977;}
if($RemoteUfdbguardAddr==null){$RemoteUfdbguardAddr="127.0.0.1";}



if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
echo "Web-Filtering Client Configuration: Enabled........: $EnableUfdbGuard\n";
echo "Web-Filtering Client Configuration: Proto..........: ufdb://{$RemoteUfdbguardAddr}:{$RemoteUfdbguardPort}\n";
echo "Web-Filtering Client Configuration: SquidGuardIPWeb: {$GLOBALS["SquidGuardIPWeb"]}\n";

$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PythonUfdbUri", $GLOBALS["SquidGuardIPWeb"]);
$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PythonUfdbUriSSL", $GLOBALS["SquidGuardIPWeb_SSL"]);


if($UseRemoteUfdbguardService==1){
	$GLOBALS["EnableUfdbGuard"]=1;
	$GLOBALS["UFDB_SERVER"]=$RemoteUfdbguardAddr;
	$GLOBALS["UFDB_PORT"]=$RemoteUfdbguardPort;
	if(!is_numeric($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
	if(trim($GLOBALS["UFDB_SERVER"]==null)){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
	if(!isset($GLOBALS["UFDB_SERVER"])){$GLOBALS["UFDB_SERVER"]=null;}
	if(!isset($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
	if($GLOBALS["UFDB_SERVER"]=="all"){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
	if($GLOBALS["UFDB_SERVER"]==null){$GLOBALS["UFDB_SERVER"]="127.0.0.1";}
	if(!is_numeric($GLOBALS["UFDB_PORT"])){$GLOBALS["UFDB_PORT"]=3977;}
	echo "Web-Filtering Client Configuration: Enabled = $EnableUfdbGuard\n";
	echo "Web-Filtering Client Configuration: ufdb://{$RemoteUfdbguardAddr}:{$RemoteUfdbguardPort}\n";
	return;
}
if($EnableUfdbGuard==0){
	echo "Web-Filtering Client Configuration: Enabled = $EnableUfdbGuard\n";
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PythonEnableUfdbEnable", 0);
	@chmod("/etc/artica-postfix/settings/Daemons/PythonEnableUfdbEnable",0755);
	@chmod("/etc/artica-postfix/settings/Daemons/PythonUfdbPort",0755);
	@chmod("/etc/artica-postfix/settings/Daemons/PythonUfdbServer",0755);
	events("Web filtering engine is disabled");
	$GLOBALS["EnableUfdbGuard"]=0;
	return;
}
echo "Web-Filtering Client Configuration: Enabled = $EnableUfdbGuard\n";
echo "Web-Filtering Client Configuration: ufdb://$RemoteUfdbguardAddr}:{$RemoteUfdbguardPort}\n";
$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PythonEnableUfdbEnable", 1);
$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PythonUfdbPort", $RemoteUfdbguardAddr);
$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PythonUfdbServer", $RemoteUfdbguardPort);
@chmod("/etc/artica-postfix/settings/Daemons/PythonEnableUfdbEnable",0755);
@chmod("/etc/artica-postfix/settings/Daemons/PythonUfdbPort",0755);
@chmod("/etc/artica-postfix/settings/Daemons/PythonUfdbServer",0755);

