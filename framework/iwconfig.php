<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["disconnect"])){disconnect();exit;}
if(isset($_GET["iwlist"])){iwlist();exit;}
if(isset($_GET["connect"])){connect();exit;}
if(isset($_GET["reconnect"])){reconnect();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);

// Wireless interface name from the console (wlan0, wlp2s0…): it is placed in shell
// commands run as root and in file names. "" when it is anything else.
function iwconfig_nic($value):string{
	$value=(string)$value;
	if(!preg_match('/^[A-Za-z0-9._:-]{1,32}$/',$value)){return "";}
	return $value;
}
function status(){
	$nic=iwconfig_nic($_GET["status"]);
	if($nic===""){return;}
	$unix=new unix();
	$wpa_cli=$unix->find_program("wpa_cli");
	$cmd="$wpa_cli -p".fw_shell_quote("/var/run/wpa_supplicant/$nic")." status -i".fw_shell_quote($nic)." >/usr/share/artica-postfix/ressources/logs/web/wpa_supplicant.$nic.status 2>&1";
	shell_exec($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	$cmd="$wpa_cli -p".fw_shell_quote("/var/run/wpa_supplicant/$nic")." list_networks -i".fw_shell_quote($nic)." >>/usr/share/artica-postfix/ressources/logs/web/wpa_supplicant.$nic.status 2>&1";
	shell_exec($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);


}
function disconnect(){
	
	$nic=iwconfig_nic($_GET["disconnect"]);
	if($nic===""){return;}
	$unix=new unix();
	$wpa_cli=$unix->find_program("wpa_cli");
	$cmd="$wpa_cli -p".fw_shell_quote("/var/run/wpa_supplicant/$nic")." disconnect -i".fw_shell_quote($nic)." >/dev/null 2>&1";
	shell_exec($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
}
function reconnect(){
	$nic=iwconfig_nic($_GET["reconnect"]);
	if($nic===""){return;}
	$unix=new unix();
	$wpa_cli=$unix->find_program("wpa_cli");
	$cmd="$wpa_cli -p".fw_shell_quote("/var/run/wpa_supplicant/$nic")." reconnect -i".fw_shell_quote($nic)." >/dev/null 2>&1";
	shell_exec($cmd);	
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
}

function uninstall(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/iwlwifi.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/iwlwifi.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{disable_feature}<hr>{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.iwconfig.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}


function install(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/iwlwifi.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/iwlwifi.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.iwconfig.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function connect(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$MAC=(string)$_GET["connect"];
	$eth=iwconfig_nic($_GET["nic"]);
	if($eth==="" || !preg_match('/^[0-9A-Fa-f]{2}(:[0-9A-Fa-f]{2}){5}$/',$MAC)){return;}
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/iwconf-ap.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/iwconf-ap.log";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.iwconfig.php --connect ".fw_shell_quote($MAC)." ".fw_shell_quote($eth)." >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	
	
}

function iwlist(){
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/iwlist.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/iwlist.log";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	if(iwconfig_nic($_GET["iwlist"])===""){return;}
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.wifi.detect.cards.php --iwlist ".fw_shell_quote($_GET["iwlist"])." >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

