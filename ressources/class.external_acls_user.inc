<?php
include_once(dirname(__FILE__)."/class.memcached.inc");
require_once '/usr/share/artica-postfix/ressources/externals/GeoIP2/vendor/autoload.php';
use GeoIp2\Database\Reader;

class lib_track_user
{
    public $memcached;
    public $WLOGExists=1;
    public $CountryName=null;
    private $PHP_GEOIP_INSTALLED=0;
    private $EnableGeoipUpdate=0;
    private $ProxyDenyCountries=0;

    public function __construct()
    {
        $this->memcached=new lib_memcached();
        $this->PHP_GEOIP_INSTALLED    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHP_GEOIP_INSTALLED"));
        $this->EnableGeoipUpdate      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));
        $this->ProxyDenyCountries     = unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDenyCountries"));
    }


    public function SetHit($user)
    {
        $MyCurrentTime=date("YmdH");
        $value=unserialize($this->memcached->getKey("SquidLicenseUsers"));
        if (!isset($value["SquidLicenseUsersMaxFound"])) {
            $value["SquidLicenseUsersMaxFound"]=0;
        }
        if (!$this->memcached->ok) {
            $this->events("SetHit:".__LINE__.": getKey(SquidLicenseUsers): MemCached Error {$this->memcached->mysql_error}");
        }

        $value[$MyCurrentTime]["USERS"][$user]=1;
        $COuntOfValue=count($value);
        $CountOFUsers=count($value[$MyCurrentTime]["USERS"]);
        $this->events("SetHit:".__LINE__.": $COuntOfValue rows {$CountOFUsers} users");

        $SquidLicenseUsersMaxFound=intval($value["SquidLicenseUsersMaxFound"]);
        $this->events("SetHit:".__LINE__.": SquidLicenseUsersMaxFound == $SquidLicenseUsersMaxFound");
        if ($CountOFUsers>$SquidLicenseUsersMaxFound) {
            $this->events("SetHit:".__LINE__.": SquidLicenseUsersMaxFound ==> $CountOFUsers");
            $value["SquidLicenseUsersMaxFound"]=$CountOFUsers;
        }


        if ($COuntOfValue>3) {
            foreach ($value as $CurrentTime=>$array) {
                if ($CurrentTime=="SquidLicenseUsersMaxFound") {
                    continue;
                }

                if ($CurrentTime==$MyCurrentTime) {
                    $this->events("SetHit:".__LINE__.": Skip $CurrentTime");
                    continue;
                }
                unset($value[$CurrentTime]);
            }
        }


        $this->memcached->saveKey("SquidLicenseUsers", serialize($value));
        if (!$this->memcached->ok) {
            $this->events("SetHit:".__LINE__.": saveKey: MemCached Error {$this->memcached->mysql_error}");
        }
    }
    private function events($text)
    {
        if ($this->WLOGExists==1) {
            if (!function_exists("WLOG")) {
                $this->WLOGExists=0;
            }
            $this->WLOGExists=3;
        }
        if ($this->WLOGExists==3) {
            WLOG("lib_track_user: $text", true);
        }
    }
    public function UserAliases($macAddr, $ipaddr)
    {
        if ($macAddr<>null) {
            $Alias=$this->memcached->getKey("$macAddr:alias");
            if (!$this->memcached->ok) {
                $this->events("UserAliases:".__LINE__.": getKey($macAddr:alias): MemCached Error {$this->memcached->mysql_error}");
            }


            if ($this->memcached->MemCachedFound) {
                $this->events("UserAliases: HIT ".__LINE__.": $macAddr == $Alias ( From Memcached)");

                if ($Alias=="NONE") {
                    $this->events("UserAliases: HIT ".__LINE__.": Return NULL");
                    return null;
                }
                $this->events("UserAliases: HIT ".__LINE__.": $macAddr return  $Alias ( From MEMOR)");
                return $Alias;
            } else {
                $this->events("UserAliases: MISS ".__LINE__.": $macAddr:alias");
            }


            if (is_file("/home/artica/UsersMac/Caches/$macAddr")) {
                $tb=explode("|", @file_get_contents("/home/artica/UsersMac/Caches/$macAddr"));
                $this->events("UserAliases:".__LINE__.": saveKey($macAddr:alias) -> {$tb[0]}");
                $this->memcached->saveKey("$macAddr:alias", $tb[0], 600);
                if (!$this->memcached->ok) {
                    $this->events("UserAliases:".__LINE__.": saveKey($macAddr:alias) -> '{$tb[0]}' MemCached Error {$this->memcached->mysql_error}");
                }
                return $tb[0];
            }

            $this->events("UserAliases:".__LINE__.": saveKey($macAddr:alias) -> NONE");
            $this->memcached->saveKey("$macAddr:alias", "NONE", 600);
            if (!$this->memcached->ok) {
                $this->events("UserAliases:".__LINE__.": saveKey($macAddr:alias): MemCached Error {$this->memcached->mysql_error}");
            }
        }

        $Alias=$this->memcached->getKey("$ipaddr:alias");
        if ($this->memcached->MemCachedFound) {
            return $Alias;
        }

        if (is_file("/home/artica/UsersMac/Caches/$ipaddr")) {
            $tb=explode("|", @file_get_contents("/home/artica/UsersMac/Caches/$ipaddr"));
            $this->memcached->saveKey("$macAddr:alias", $tb[0], 600);
            return $tb[0];
        }

        $this->memcached->saveKey("$ipaddr:alias", "", 600);
        return "";
    }
    private function DenyByGeoIPSyslog($text)
    {
        if (!function_exists("openlog")) {
            return false;
        }
        if (!function_exists("syslog")) {
            return false;
        }

        $LOG_SEV=LOG_INFO;
        if (function_exists("openlog")) {
            openlog("proxy_geoip", LOG_PID, LOG_SYSLOG);
        }
        if (function_exists("syslog")) {
            syslog($LOG_SEV, $text);
        }
        if (function_exists("closelog")) {
            closelog();
        }
    }
    public function DenyByGeoIP($ipadddr)
    {
        $CACHE="HIT";
        if ($this->ProxyDenyCountries==0) {
            return false;
        }

        if ($this->PHP_GEOIP_INSTALLED==0) {
            $this->DenyByGeoIPSyslog("[WARNING]: [$ipadddr] PHP_GEOIP_INSTALLED return 0");
            return false;
        }
        if ($this->EnableGeoipUpdate==0) {
            $this->DenyByGeoIPSyslog("[WARNING]: [$ipadddr] GeoIPUpdate return not installed");
            return false;
        }
        if (!extension_loaded("maxminddb")) {
            $this->DenyByGeoIPSyslog("[WARNING]: [$ipadddr] maxminddb module not installed");
            return false;
        }

        if (!isset($GLOBALS["ProxyDenyCountries"])) {
            $GLOBALS["ProxyDenyCountries"]=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProxyDenyCountries"));
            if (!is_array($GLOBALS["ProxyDenyCountries"])) {
                $GLOBALS["ProxyDenyCountries"]=array();
            }
        }

        $ProxyDenyCountries=$GLOBALS["ProxyDenyCountries"];

        if (count($ProxyDenyCountries)==0) {
            $this->DenyByGeoIPSyslog("[INACTIVE]: [$ipadddr] No denied country has been defined");
            return false;
        }

        $value=unserialize($this->memcached->getKey("GEOIP:$ipadddr"));
        if (!is_array($value)) {
            $value=array();
        }

        if (!isset($value["countryCode"])) {
            try {
                $CACHE="MISS";
                $reader = new Reader('/usr/local/share/GeoIP/GeoLite2-Country.mmdb');
                $record = $reader->country($ipadddr);
                $value["countryCode"] = $record->country->isoCode;
                $value["countryName"] = $record->country->name;
                $this->memcached->saveKey("GEOIP:$ipadddr", serialize($value), 16000);
            } catch (Exception $e) {
                $this->DenyByGeoIPSyslog("[FATAL]: [$ipadddr] " . $e->getMessage());
                return false;
            }
        }


        if ($value["countryCode"] == null) {
            $this->DenyByGeoIPSyslog("[INACTIVE]: [$ipadddr] Unknown (unknown)");
            return false;
        }
        $countryCode=$value["countryCode"];
        $countryName=$value["countryName"];

        if (isset($ProxyDenyCountries[$countryCode])) {
            $this->DenyByGeoIPSyslog("[DENY]: [$ipadddr] $countryCode ($countryName) - $CACHE");
            $this->CountryName=$countryName;
            return true;
        }
        $this->DenyByGeoIPSyslog("[PASS]: [$ipadddr] $countryCode ($countryName) - $CACHE");

        return false;
    }
    public function ufdbunlock($FAMILYSITE, $uid=null, $ipaddr=null)
    {
        $FAMILYSITE=trim(strtolower($FAMILYSITE));
        $uid=trim(strtolower($uid));
        $ipaddr=trim(strtolower($ipaddr));
        $results=null;
        $ufdbunlock_value=$this->memcached->getKey("ufdbunlock");
        if ($this->memcached->MemCachedFound) {
            $results=@unserialize($ufdbunlock_value);
            if (!$results) {
                $results=null;
            }
        }

        if (!is_array($results)) {
            $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
            $sql="SELECT finaltime,uid,ipaddr,www FROM ufdbunlock WHERE finaltime >".time();
            $results=$q->QUERY_SQL($sql);
            $this->memcached->saveKey("ufdbunlock", $results);
        }

        $time=time();
        foreach ($results as $index=>$ligne) {
            $finaltime=$ligne["finaltime"];
            $www=trim(strtolower($ligne["www"]));
            $db_uid=trim(strtolower($ligne["uid"]));
            $db_ipaddr=trim(strtolower($ligne["ipaddr"]));
            if ($db_uid=="unknown") {
                $db_uid=null;
            }
            if ($time>$finaltime) {
                continue;
            }
            if ($www<>$FAMILYSITE) {
                continue;
            }
            if ($db_uid<>null) {
                if ($uid==$db_uid) {
                    return true;
                }
            }
            if ($db_ipaddr<>null) {
                if ($db_ipaddr == $ipaddr) {
                    return true;
                }
            }
        }
    }
    public function isWhitelisted($sitename):bool{

        $sitename=strtolower($sitename);
        $default["rightnow.com"]=true;
        $default["custhelp.com"]=true;
        $default["reseau-canope.fr"]=true;
        $default["semrush.com"]=true;
        $default["fr.semrush.com"]=true;
        $default["stocks.etoro.com"]=true;
        $default["www.etoro.com"]=true;
        $default["etoro.com"]=true;
        $default["westia.com"]=true;
        $default["zopim.com"]=true;
        $default["ibmcloud.com"]=true;
        $default["github.hubspot.com"]=true;
        $default["in.appcenter.ms"]=true;
        $default["cdn.smehost.net"]=true;
        $default["display.ugc.bazaarvoice.com"]=true;
        $default["googletagmanager.com"]=true;
        $default["www.googletagmanager.com"]=true;
        $default["highcharts.com"]=true;
        $default["amazonaws.com"]=true;
        $default["orange.fr"]=true;
        $default["salesforce.com"]=true;
        $default["force.com"]=true;
        $default["akamaihd.net"]=true;
        $default["nvidia.com"]=true;
        $default["wistia.net"]=true;
        $default["ebay.com"]=true;
        $default["jqueryscript.net"]=true;
        $default["microsoft.com"]=true;
        $default["fnac.com"]=true;
        $default["abtasty.com"]=true;
        $default["adobedtm.com"]=true;
        $default["hubvisor.io"]=true;
        $default["01net.com"]=true;
        $default["yandex.ru"]=true;
        $default["netdna-ssl.com"]=true;
        $default["sfr.fr"]=true;
        $default["google.com"]=true;
        $default["cloudfront.net"]=true;
        $default["game-mode.net"]=true;
        $default["gvt1.com"]=true;
        $default["gvt2.com"]=true;
        $default["gvt3.com"]=true;
        $default["gvt4.com"]=true;
        $default["gvt5.com"]=true;
        $default["ipify.org"]=true;
        $default["maxmind.com"]=true;
        $default["bing.com"]=true;
        $default["wix.com"]=true;
        $default["cdn.woopic.com"]=true;
        $default["woopic.com"]=true;
        $default["spiceworks.com"]=true;
        $default["avast.com"]=true;
        $default["filesusr.com"]=true;

        if (isset($default[$sitename])) {return true;}
        $precache=$this->memcached->getKey("isWhite:$sitename");

        if ($this->memcached->MemCachedFound) {
            $this->events("isWhitelisted: HIT ".__LINE__.": isWhite:$sitename == $precache");
            return true;
        }

        $acls_whitelist_domains=$this->memcached->getKey("acls_whitelist_domains");
        if (!is_array($acls_whitelist_domains)) {
            $f=explode("\n", @file_get_contents("/etc/squid3/acls_whitelist.dstdomain.conf"));
            foreach ($f as $line) {
                $line=trim(strtolower($line));
                $line=str_replace("^", "", $line);
                if (strlen($line)<4) {
                    continue;
                }
                if (substr($line, 0, 1)==".") {
                    $line=substr($line, 1, strlen($line));
                }
                $line=trim($line);
                $default[$line]=true;
                $this->memcached->saveKey("isWhite:$line", true, 300);
            }

            $this->events("isWhitelisted: MISS ".__LINE__.": saveKey(acls_whitelist_domains)");
            $this->memcached->saveKey("acls_whitelist_domains", $default, 300);
        } else {
            $this->events("acls_whitelist_domains is in memcahed ".__LINE__);
        }

        if (isset($acls_whitelist_domains[$sitename])) {
            $this->events("Found it in acls_whitelist_domains: HIT ".__LINE__.": $sitename");
            $this->memcached->saveKey("isWhite:$sitename", true, 300);
            return true;
        }


        $this->events("Not Whitelisted: HIT ".__LINE__.": $sitename");
        return false;

    }
    private function bool($var)
    {
        switch (strtolower($var)) {
            case ("true"):return true;break;
            case ("false"):return false;break;
            case("1"):return true;break;
            case("0"):return true;break;
            default:
                return false;
        }
    }
    private function tool_time_min($timeFrom)
    {
        $data1 = $timeFrom;
        $data2 = time();
        $difference = ($data2 - $data1);
        $results=intval(round($difference/60));
        if ($results<0) {
            $results=1;
        }
        return $results;
    }
}
