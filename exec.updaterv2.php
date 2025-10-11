<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
$GLOBALS["SINGLE_DEBUG"]=false;
$GLOBALS["NOT_FORCE_PROXY"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["CHANGED"]=false;
$GLOBALS["FORCE_NIGHTLY"]=false;
$GLOBALS["MasterIndexFile"]="/usr/share/artica-postfix/ressources/index.ini";
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--force-nightly#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["FORCE_NIGHTLY"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}


function build(){

$f[]="[ConnectionSettings]";
$f[]="TimeoutConnection=60";
$f[]="UsePassiveFtpMode=true";
$f[]="UseProxyServer=false";
$f[]="AutomaticallyDetectProxyServerSettings=true";
$f[]="UseSpecifiedProxyServerSettings=false";
$f[]="AddressProxyServer=";
$f[]="PortProxyServer=8080";
$f[]="UseAuthenticationProxyServer=false";
$f[]="UserNameProxyServer=";
$f[]="PasswordProxyServer=";
$f[]="ByPassProxyServer=true";
$f[]="";
$f[]="[AdditionalSettings]";
$f[]="CreateCrashDumpFile=true";
$f[]="TurnTrace=false";
$f[]="AddIconToTray=true";
$f[]="MinimizeProgramUponTermination=true";
$f[]="AnimateIcon=true";
$f[]="LanguagesBox=0";
$f[]="ReturnCodeDesc=";
$f[]="";
$f[]="[ReportSettings]";
$f[]="DisplayReportsOnScreen=false";
$f[]="SaveReportsToFile=true";
$f[]="AppendToPreviousFile=false";
$f[]="SizeLogFileValue=1048576";
$f[]="ReportFileName=report.txt";
$f[]="DeleteIfSize=true";
$f[]="DeleteIfNumDay=false";
$f[]="NoChangeLogFile=false";
$f[]="NumDayLifeLOgFileValue=7";
$f[]="";
$f[]="[DirectoriesSettings]";
$f[]="MoveToCurrentFolder=true";
$f[]="MoveToCustomFolder=false";
$f[]="UpdatesFolder=";
$f[]="TempFolder=";
$f[]="ClearTempFolder=true";
$f[]="";
$f[]="[UpdatesSourceSettings]";
$f[]="SourceCustomPath=";
$f[]="SourceCustom=false";
$f[]="SourceKlabServer=true";
$f[]="";
$f[]="[DownloadingSettings]";
$f[]="DownloadDataBasesAndModules=true";
$f[]="";
$f[]="[ComponentSettings]";
$f[]="DownloadAllDatabases=false";
$f[]="DownloadSelectedComponents=true";
$f[]="ApplicationsOs=1";
$f[]="KasperskyAntiVirus_12_0=false";
$f[]="KasperskyAntiVirus_13_0=false";
$f[]="KasperskyAntiVirus_14_0=false";
$f[]="KasperskyInternetSecurrity_12_0=false";
$f[]="KasperskyInternetSecurrity_13_0=false";
$f[]="KasperskyInternetSecurrity_14_0=false";
$f[]="KasperskyPure_9_0_0_192_199=false";
$f[]="KasperskyPure_12_0_1_288=false";
$f[]="KasperskyPure_3_0=false";
$f[]="KasperskyAntiVirus_8_0_6_863=false";
$f[]="Kaspersky_Security_13_0_2_458=false";
$f[]="Kaspersky_Security_14_0_0_177=false";
$f[]="KasperskyRescueDisk_10_0_29_6=false";
$f[]="KasperskyRescueDisk_10_0_31_4=false";
$f[]="KasperskyEndpointSecurityForWinWKS_8=false";
$f[]="KasperskyEndpointSecurityForWinWKS_10=false";
$f[]="KasperskyEndpointSecurityForMacOSX_8=false";
$f[]="KasperskyEndpointSecurityForLinux_8=false";
$f[]="KasperskySmallOfficeSecurityPC_9_1_0_59=false";
$f[]="KasperskySmallOfficeSecurityPC_3=false";
$f[]="KasperskyAntiVirusWindowsWorkstation_6_0_4_1424=false";
$f[]="KasperskyAntiVirusWindowsWorkstation_6_0_4_1611=false";
$f[]="KasperskyAntiVirusSOS_6_0_4_1424=false";
$f[]="KasperskyAntiVirusSOS_6_0_4_1611=false";
$f[]="KasperskyEndpointSecurityForWinFS_8=false";
$f[]="KasperskyEndpointSecurityForWinFS_10=false";
$f[]="KasperskySmallOfficeSecurityFS_9_1_0_59=false";
$f[]="KasperskySmallOfficeSecurityFS_3=false";
$f[]="KasperskyAntiVirusWindowsServer_6_0_4_1424=false";
$f[]="KasperskyAntiVirusWindowsServer_6_0_4_1611=false";
$f[]="KasperskyAntiVirusWindowsServerEE_8_0=false";
$f[]="KasperskyAntiVirusStorage_8_0=false";
$f[]="KasperskyAntiVirusLinuxFileServerWorkstation_8=false";
$f[]="KasperskySecurityforVirtualization_1_1=false";
$f[]="KasperskySecurityforVirtualization_2_0=false";
$f[]="KasperskySecurityMicrosoftExchangeServer_8_0=false";
$f[]="KasperskySecurityMicrosoftExchangeServer_8_1=false";
$f[]="KasperskySecuritySharePointServer_8_0=false";
$f[]="KasperskyLinuxMailSecurity_8_0=false";
$f[]="KasperskyAntiVirusLotusNotesDomino_8_0=false";
$f[]="KasperskyMailGateway_5_6_28_0=false";
$f[]="KasperskyAntiSpam_3_0_284_1=false";
$f[]="KasperskyAntiVirusMicrosoftIsaServers_8_0_3586=false";
$f[]="KasperskyAntiVirusMicrosoftIsaServers_8_5=false";
$f[]="KasperskyAntiVirusProxyServer_5_5=false";
$f[]="KasperskyAdministrationKit_8_0_2048_2090=false";
$f[]="KasperskySecurityCenter_9=false";
$f[]="KasperskySecurityCenter_10=false";
$f[]="KasperskySecurityMobile_10_mobile=false";
$f[]="KasperskySecurityMobile_10_u0607g=false";
$f[]="";
$f[]="[ShedulerSettings]";
$f[]="LastUpdate=@Variant(\0\0\0\x10\0\0\0\0\xff\xff\xff\xff\xff)";
$f[]="ShedulerType=0";
$f[]="PeriodValue=1";
$f[]="UseTime=true";
$f[]="Time=@Variant(\0\0\0\xf\xff\xff\xff\xff)";
$f[]="Monday=true";
$f[]="Tuesday=true";
$f[]="Wednesday=true";
$f[]="Thursday=true";
$f[]="Friday=true";
$f[]="Saturday=true";
$f[]="Sunday=true";
$f[]="";
$f[]="[SdkSettings]";
$f[]="PrimaryIndexFileName=u0607g.xml";
$f[]="PrimaryIndexRelativeUrlPath=index";
$f[]="LicensePath=";
$f[]="SimpleModeLicensing=true";


}


