<?php
include_once(dirname(__FILE__)."/externals/Net/DNS2.inc");

class artica_cloud{
    private $memcached;
    public $CategoryName;


    function __construct($noload = false){
        $this->memcached=new lib_memcached();
        if(!isset($GLOBALS["CHECKLIC"])){$GLOBALS["CHECKLIC"]=$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE();}
    }

    private function ToSyslog($text){
        $LOG_SEV=LOG_INFO;
        $text="[ARTICA_CLOUD]: $text";
        if(function_exists("openlog")){openlog("ksrn", LOG_PID , LOG_SYSLOG);}
        if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
        if(function_exists("closelog")){closelog();}
    }

    public function GET_CATEGORIES($sitename){
        if (!$GLOBALS["CHECKLIC"]) { return 0; }

        if (!isset($GLOBALS["MY_DNSS"])) {
            $f = explode("\n", @file_get_contents("/etc/resolv.conf"));
            foreach ($f as $line) {
                $line = trim($line);
                if ($line == null) {
                    continue;
                }
                if (!preg_match("#nameserver\s+([0-9\.]+)#", $line, $re)) {
                    continue;
                }
                $GLOBALS["MY_DNSS"][] = $re[1];
            }
            $GLOBALS["MY_DNSS"][] = "46.105.178.150";
        }


        $Config["nameservers"] = $GLOBALS["MY_DNSS"];
        $Config["timeout"] = 1;

        $UFDBCAT_DNS_COUNT = intval($this->memcached->getKey("UFDBCAT_DNS_COUNT"));
        $UFDBCAT_DNS_COUNT = $UFDBCAT_DNS_COUNT + 1;
        $this->memcached->saveKey("UFDBCAT_DNS_COUNT", $UFDBCAT_DNS_COUNT, 36000);

        $r = new Net_DNS2_Resolver(array('nameservers' => $GLOBALS["MY_DNSS"], "timeout" => 1));

        try {
            $result = $r->query("$sitename.filter.artica.center", 'TXT');

        } catch (Net_DNS2_Exception $e) {
            $message = $e->getMessage();
            if ($GLOBALS["VERBOSE"]) {
                echo "$message\n";
            }
            if (stripos("  $message", "referenced in the query does not exist") > 0) {
                return 0;
            }
            $this->memcached->saveKey("UFDBCAT_DNS_ERROR", $message, 3600);
            $this->ToSyslog("$message In line " . __LINE__);
            return 0;
        }

        if (!property_exists($result, "answer")) {
            $this->ToSyslog("$sitename: Undefined property answer in DNS engine in line " . __LINE__);
            return 0;
        }



        foreach ($result->answer as $index => $rr) {


            $data = $rr->text[0];

            if (preg_match("#([0-9]+):(.+):([0-9]+)#", trim($data), $re)) {
                $category_id = intval($re[1]);
                $categoryname = trim($re[2]);
                $this->CategoryTime = intval($re[3]);
                if ($category_id == 89) {
                    continue;
                }
                if ($category_id == 199) {
                    continue;
                }
                $this->CategoryName = $categoryname;
                return $category_id;
            }


            if (preg_match("#([0-9]+):(.+)#", trim($data), $re)) {
                $category_id = intval($re[1]);
                $categoryname = trim($re[2]);
                if ($category_id == 89) {
                    continue;
                }
                if ($category_id == 199) {
                    continue;
                }
                $this->CategoryName = $categoryname;
                return $category_id;
            }

        }

        return 0;

    }


}