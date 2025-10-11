<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

class useragents{

    private $SquidUrgency=0;

    function __construct($checks=true){
        if($checks) {
            $this->checkTable();
            $this->SquidUrgency = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
        }

    }



    public function ACLS_ALL():bool{

        $SQUIDACLsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDACLsEnabled"));
        if($SQUIDACLsEnabled==0){
            $f[]="#--------- http_access: Dedicated rule that allow UserAgents [".__LINE__."]";
            $f[]="#--------- ACLS Feature is disabled...";
            @file_put_contents("/etc/squid3/acls_browsers.conf",@implode("\n",$f));
            return false;
        }
        if($this->SquidUrgency==1){
            $f[]="#--------- http_access: Dedicated rule that allow UserAgents [".__LINE__."]";
            $f[]="#--------- Proxy is in emergency mode!";
            @file_put_contents("/etc/squid3/acls_browsers.conf",@implode("\n",$f));
            return false;
        }

        $this->BrowsersByPass();
        $this->BrowsersDeny();
        $this->BrowsersNoCache();
        $this->BrowsersNoWebF();

        $FILES[]="/etc/squid3/acls/Browsers-allow.acl";
        $FILES[]="/etc/squid3/acls/Browsers-deny.acl";
        $FILES[]="/etc/squid3/acls/Browsers-nocache.acl";
        $FILES[]="/etc/squid3/acls/Browsers-nofilter.acl";

        foreach ($FILES as $path){
            if(!is_file($path)) {
                @touch($path);
                @chown($path,"squid");
            }
        }


        $f[]="acl BrowsersByPass browser -i \"/etc/squid3/acls/Browsers-allow.acl\"";
        $f[]="note accessrule BrowsersByPass BrowsersByPass";
        $f[]="acl BrowsersDeny browser -i \"/etc/squid3/acls/Browsers-deny.acl\"";
        $f[]="note accessrule BrowsersDeny BrowsersDeny";
        $f[]="acl BrowsersNoCache browser -i \"/etc/squid3/acls/Browsers-nocache.acl\"";
        $f[]="note accessrule BrowsersNoCache BrowsersNoCache";
        $f[]="acl BrowsersNoWebF browser -i \"/etc/squid3/acls/Browsers-nofilter.acl\"";
        $f[]="note accessrule BrowsersNoWebF BrowsersNoWebF";
        $f[]="#--------- http_access: Dedicated rule that allow UserAgents [".__LINE__."]";
        $f[]="http_access deny BrowsersDeny all";
        $f[]="http_access allow BrowsersByPass";
        $f[]="cache deny BrowsersNoCache";
        $f[]="send_hit deny BrowsersNoCache";
        $f[]="store_miss deny BrowsersNoCache";
        $f[]="url_rewrite_access deny BrowsersNoWebF";
        @file_put_contents("/etc/squid3/acls_browsers.conf",@implode("\n",$f));
        return true;
    }


    private function BrowsersByPass(){
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        @file_put_contents("/etc/squid3/acls/Browsers-allow.acl", "#\n");
        $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `UsersAgentsDB` (
        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`explain` TEXT,
				`editor` TEXT,
				`category` TEXT,
				`pattern` TEXT UNIQUE,
				`bypass` INTEGER NOT NULL DEFAULT 1,
				`bypassWebF` INTEGER NOT NULL DEFAULT 0,
				`bypassWebC` INTEGER NOT NULL DEFAULT 0,
				`deny` INTEGER NOT NULL DEFAULT 0,
				`enabled` INTEGER NOT NULL DEFAULT 0
				)");
        if(!$q->ok){echo $q->mysql_error."\n\n";}

        $sql="SELECT pattern FROM `UsersAgentsDB` WHERE `enabled`=1 AND `bypass`=1 AND `deny`=0";
        $results = $q->QUERY_SQL($sql);
        $T=array();
        foreach($results as $index=>$ligne) {
            $pattern=$ligne["pattern"];
            if(preg_match("#regex:(.+)#", $pattern,$re)){ $T[]=$re[1]; continue;}
            $T[]=$this->PatternToRegex($pattern);

        }

        @file_put_contents("/etc/squid3/acls/Browsers-allow.acl", @implode("\n", $T));
        @chown("/etc/squid3/acls/Browsers-allow.acl","squid");

    }

    private function BrowsersNoCache(){
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $sql="SELECT pattern FROM `UsersAgentsDB` WHERE `enabled`=1 AND `bypassWebC`=1";
        $results = $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error."\n\n";}
        $T=array();
        foreach($results as $index=>$ligne) {
            $pattern=$ligne["pattern"];
            if(preg_match("#regex:(.+)#", $pattern,$re)){ $T[]=$re[1]; continue;}
            $T[]=$this->PatternToRegex($pattern);
            continue;
        }

        @file_put_contents("/etc/squid3/acls/Browsers-nocache.acl", @implode("\n", $T));
        @chown("/etc/squid3/acls/Browsers-nocache.acl","squid");

    }

    private function BrowsersNoWebF(){
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        if(!$q->TABLE_EXISTS("UsersAgentsDB")){
            @file_put_contents("/etc/squid3/acls/Browsers-nofilter.acl", "\n");
            @chown("/etc/squid3/acls/Browsers-nofilter.acl","squid");
            return true;
        }
        $sql="SELECT pattern FROM `UsersAgentsDB` WHERE `enabled`=1 AND `bypassWebF`=1";
        $results = $q->QUERY_SQL($sql);

        if(!$q->ok){
            echo $q->mysql_error."\n";
            @file_put_contents("/etc/squid3/acls/Browsers-nofilter.acl", "\n");
            @chown("/etc/squid3/acls/Browsers-nofilter.acl","squid");
            return true;
        }

        $T=array();
        foreach($results as $index=>$ligne) {
            $pattern=$ligne["pattern"];
            if(preg_match("#regex:(.+)#", $pattern,$re)){ $T[]=$re[1]; continue;}
            $T[]=$this->PatternToRegex($pattern);
            continue;
        }

        @file_put_contents("/etc/squid3/acls/Browsers-nofilter.acl", @implode("\n", $T));
        @chown("/etc/squid3/acls/Browsers-nofilter.acl","squid");

    }

    private function BrowsersDeny(){
        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $sql="SELECT pattern FROM `UsersAgentsDB` WHERE `enabled`=1 AND `bypass`=0 AND `deny`=1";
        $results = $q->QUERY_SQL($sql);
        $T=array();
        foreach($results as $index=>$ligne) {
            $pattern=$ligne["pattern"];
            if(preg_match("#regex:(.+)#", $pattern,$re)){ $T[]=$re[1]; continue;}
            $T[]=$this->PatternToRegex($pattern);
            continue;
        }

        @file_put_contents("/etc/squid3/acls/Browsers-deny.acl", @implode("\n", $T));
        @chown("/etc/squid3/acls/Browsers-deny.acl","squid");

    }
    public function PatternToRegex($str){
        $str=str_replace("(", "\(", $str);
        $str=str_replace(")", "\)", $str);
        $str=str_replace("[", "\[", $str);
        $str=str_replace("]", "\]", $str);
        $str=str_replace("/", "\/", $str);
        if(preg_match_all("#([0-9\.,\-]+)#", $str,$re)){
            foreach ($re[1] as $index=>$data){
                if($data=="."){continue;}
                if($data=="-"){continue;}
                if(strlen($data)==1){continue;}
                if(substr($data, 0,1)=="."){continue;}
                if(substr($data, 0,1)=="-"){continue;}
                if(substr($data, 0,1)==","){continue;}
                $str=str_replace($data, "[0-9.,\-]+", $str);

            }
        }
        $str=str_replace(".", "\.", $str);
        return $str;
    }



    public function checkTable(){
        $this->FillTable();

    }

    private function FillTable(){

        $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
        $CountRow=$q->COUNT_ROWS("UsersAgentsDB");
        writelogs("UsersAgentsDB $CountRow rows",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
        if($CountRow>1){return;}


        $array[]="iPhone,Apple iPhones,Apple,SmartPhones,1,0";
        $array[]="iPhoto,Apple iPhoto,Apple,Apple,1,0";
        $array[]="MacBookAir,Apple MacBook Air (generic),Apple,Apple,1,0";
        $array[]="regex:^ocspd,Apple OCSP and CRL Daemon,Apple,Apple,1,0";
        $array[]="Apple-iPhone3C,Apple iPhone 3,Apple,SmartPhones,1,0";
        $array[]="Apple-iPhone4C,Apple iPhone 4,Apple,SmartPhones,1,0";
        $array[]="Apple-iPhone5C,Apple iPhone 5C,Apple,SmartPhones,1,0";
        $array[]="AppleCoreMedia,Apple Core Media,Apple,SmartPhones,1,0";
        $array[]="AppStore,Apple Store,Apple,SmartPhones,1,0";
        $array[]="iPad,Apple iPad (generic),Apple,SmartPhones,1,0";
        $array[]="MobileAsset,Mobile Asset,Apple,SmartPhones,1,0";
        $array[]="iOS,Apple iOS,Apple,SmartPhones,1,0";
        $array[]="itunesstored,Apple iTunes,Apple,SmartPhones,1,0";
        $array[]="com.google.android.apps.plus,Google Android Applications,Google,SmartPhones,1,0";
        $array[]="WSLib,Antivirus Bit Defender,Bit Defender,antivirus,0,0";
        $array[]="AntiVir-NGUpd,Antivirus AntiVir,AntiVir,antivirus,0,0";
        $array[]="Avast,Antivirus Avast,Avast,antivirus,0,0";
        $array[]="ClamAV,Antivirus ClamAV,ClamAV,antivirus,0,0";


        $array[]="Adobe Downloader,Adobe Updater,Adobe,Adobe,0,0";
        $array[]="Download Flash Player Installer,Adobe Flash Player Installer,Adobe,Player,0,0";
        $array[]="Shockwave Downloader,Shockwave Downloader,Shockwave,Player,0,0";
        $array[]="Shockwave Flash,Shockwave Flash,Shockwave,Player,0,0";
        $array[]="Flash Player Seed,Adobe Flash Player Seed,Adobe,Adobe,0,0";
        $array[]="Adobe Flash Player,Adobe Player,Adobe,Player,0,0";
        $array[]="Adobe Update,Adobe Updater,Adobe,Adobe,0,0";
        $array[]="Adobe.Shockwave,Adobe Updater,Shockwave,Player,0,0";
        $array[]="Acrobat,Adobe Acrobat,Adobe,Adobe,0,0";
        $array[]="Windows-Media-Player,Windows Media Player,Microsoft,Player,0,0";
        $array[]="Adobe Synchronizer,Adobe Synchronizer,Adobe,Adobe,0,0";
        $array[]="AMPVConnector,AMPVConnector,AMPVConnector,AMPVConnector,0,0";
        $array[]="Borland SOAP,Borland SOAP,Borland SOAP,Borland,0,0";
        $array[]="DropboxDesktopClient,Dropbox Desktop Client,Dropbox,Remote-control,0,0";
        $array[]="Google Toolbar,Google Toolbar,Google,Toolbar,0,0";
        $array[]="GoogleEarth,Google Earth,Google,Google,0,0";
        $array[]="Microsoft-WebDAV-MiniRedir,Microsoft-WebDAV,Microsoft,Microsoft,0,0";
        $array[]="Windows Store,Microsoft Store client,Microsoft,Microsoft,0,0";
        $array[]="WinHttpClient,Microsoft C++ HTTP Wrapper,Microsoft,Microsoft,0,0";
        $array[]="WinHttp-Autoproxy-Service,Microsoft AutoProxy,Microsoft,Microsoft,1,0";
        $array[]="Microsoft-CryptoAPI,Microsoft Crypto API,Microsoft,Microsoft,1,0";
        $array[]="Windows-Update-Agent,Microsoft Update Agent,Microsoft,Microsoft,0,0";
        $array[]="Windows-RSS-Platform,Microsoft RSS Agent,Microsoft,Microsoft,0,0";
        $array[]="LogMeIn Client,LogMeIn Client,LogMeIn,Remote-control,0,0";
        $array[]="OutlookSocialConnector,Outlook Social Connector,Microsoft,Microsoft,0,0";
        $array[]="facebook,FaceBook (generic),Facebook,SocialNet,0,0";
        $array[]="DynGate,TeamViewer Agent,TeamViewer,Remote-control,0,0";
        $array[]="webex,WebEx Client,Cisco,Remote-control,0,0";
        $array[]="Java/,Java applications,Java,Java,1,0";
        $array[]="MS Web Services Client Protocol,Microsoft generic,Microsoft,Microsoft,0,0";
        $array[]="Skype,Skype Client,Microsoft,Chat,0,0";
        $array[]="yum/,RedHat/CentOS Update,RedHat,Unix,1,0";
        $array[]="PEAR/,PHP Pear updater ,Unix,Unix,1,0";
        $array[]="Wget/,Unix HTTP Client,Unix,Unix,1,0";
        $array[]="APT-HTTP,Debian Update,Debian,Unix,1,0";
        $array[]="WicaAgent,Wica Agent,Wica,Unknown,1,0";
        $array[]="WidgiToolbar,Widgi Toolbar,Widgi,Suspicious,0,1";
        $array[]="SearchProtect,Conduit Search Protect,Conduit,Suspicious,0,1";
        $array[]="PlayStation,PlayStation,Sony,Games,1,0";
        $array[]="Nintendo,Nintendo (generic),Nintendo,Games,1,0";
        $array[]="CTR AC/,Nintendo Update Agent,Nintendo,Games,1,0";
        $array[]="VungleDroid,Vungle Ads,Vungle,SmartPhones,0,1";
        $array[]="GoogleAnalytics,Google Analytics,Google,SmartPhones,0,1";
        $array[]="SPStub,spstub.exe,spstub,Suspicious,0,1";
        $array[]="PSP2Community-agent,PSP Community,Sony,Games,0,1";
        $array[]="Valve/Steam,Steam Games,Steam,Games,0,1";
        $array[]="zombie_trials,zombie Games,Games,Games,0,0";
        $array[]="Amazon.com,Amazon Smartphone APP,Amazon,Smartphones,1,0";
        $array[]="WRQ-RWeb,WRQ Client,WRQ,Smartphones,1,0";
        $array[]="UninstallReporter,Uninstall Reporter,Suspicious,Suspicious,0,0";
        $array[]="Nero StartSmart,Nero StartSmart,Nero,Updater,0,0";
        $array[]="DragonUpdater,Comodo Dragon browser updater,Comodo,Browsers,0,0";
        $array[]="Microsoft SettingsSync,Microsoft Settings Sync Framework,Microsoft,Microsoft,0,0";
        $array[]="HP Rules Engine,HP Rules Engine,HP,HP,0,0";
        $array[]="HP Lookup Agent,HP Lookup Agent,HP,HP,0,0";
        $array[]="SFR_MonCompte,SFR App,SFR,Smartphones,1,0";
        $array[]="com.sfr.android.moncompte,SFR App,SFR,Smartphones,1,0";
        $array[]="OpenOffice,OpenOffice Client,OpenOffice,Apps,1,0";
        $array[]="Microsoft Office,MS Office Clients,Microsoft,Apps,0,0";
        $array[]="Google Update,Google Update,Google,Google,0,0";
        $array[]="GoogleSoftwareUpdateAgent,Google Update 2,Google,Google,0,0";
        $array[]="CANALPLAY,Canal+ App,Canal+,Smartphones,1,0";
        $array[]="Sony*Build,Sony Updater,Sony,Smartphones,1,0";
        $array[]="com.sfr.android.sfrjeux,Sony SFR Games,SFR,Smartphones,1,0";
        $array[]="AndroidDownloadManager,Android Download,Android,Smartphones,1,0";
        $array[]="regex:^\*[0-9A-Za-z]+=$,Kaspersky Antivirus,Kaspersky,antivirus,1,0";
        $array[]="regex:^Mise.*jour de logiciels.*CFNetwork.*MacBookAir,MacBook Air Update Agent,Apple,Apple,1,0";
        $array[]="regex:^Mozilla\/.*Windows.*Gecko.*Firefox\/,Mozilla FireFox Windows,Mozilla,Browsers,0,0";
        $array[]="regex:^Mozilla/.*Linux.*Gecko.*Firefox\/,Mozilla FireFox Linux,Mozilla,Browsers,1,0";
        $array[]="regex:^storeagent.*CFNetwork,Apple iTunes Cloud,Apple,Apple,1,0";
        $array[]="regex:^Mozilla.*Windows.*AppleWebKit.*Chrome\/,Google Chrome Windows,Google,Browsers,0,0";
        $array[]="regex:^Mozilla/.*Artica,Artica Updater,Articatech,Unix,1,0";
        $array[]="regex:^Mozilla\/.*MSIE 8.*Win64,Internet Explorer 8.0,Microsoft,Browsers,0,0";
        $array[]="regex:^Mozilla\/.*MSIE [0-9]+\..*; Windows NT 6.1; WOW64;,Generic Internet Explorer Windows 7 64bits,Microsoft,Browsers,0,0";
        $array[]="regex:^iTunes\/[0-9\.]+,Apple iTunes,Apple,SmartPhones,1,0";
        $array[]="regex:^com\.apple\.Maps,Apple maps,Apple,SmartPhones,1,0";

        $prefix="INSERT INTO `UsersAgentsDB` (`pattern`,`explain`,`editor`,`category`,`bypass`,`deny`,`bypassWebF`,`bypassWebC`)";

        writelogs("Starting Loop of ".count($array)." elements",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
        foreach ($array as $line){
            if(!preg_match("#(.+?),(.+?),(.+?),([A-Za-z\-]+),([0-9]),([0-9])#", $line,$f)){
                writelogs("Error Not found in $line ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
                continue;
            }
            $sql="$prefix VALUES ('$f[1]','$f[2]','$f[3]','$f[4]','$f[5]','$f[6]',0,0)";
            $q->QUERY_SQL($sql);
            if(!$q->ok){writelogs("Error MySQL $q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}

        }
    }

}