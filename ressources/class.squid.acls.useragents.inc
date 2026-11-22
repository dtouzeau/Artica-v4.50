<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

class useragents{

    private $SquidUrgency=0;
    private $rulesCache=null;
    private $rulesFetched=false;

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

    /**
     * Fetches all UsersAgentsDB rows once per instance via
     * GET /proxy/browsers/rules (articarest). Returns the rows array on
     * success, null on API failure — callers distinguish "no rules" from
     * "backend unreachable". The four Browsers* generators share this single
     * call instead of issuing four separate SELECTs like the legacy code.
     */
    private function fetchRules(){
        if($this->rulesFetched){return $this->rulesCache;}
        $this->rulesFetched=true;
        $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/browsers/rules");
        $resp=json_decode(strval($raw),true);
        if(!is_array($resp) || empty($resp["success"]) || !isset($resp["data"]) || !is_array($resp["data"])){
            $err=is_array($resp)?strval($resp["error"] ?? "malformed response"):"no response";
            if(function_exists("writelogs")){
                writelogs("GET /proxy/browsers/rules failed: $err",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
            }
            $this->rulesCache=null;
            return null;
        }
        $this->rulesCache=$resp["data"];
        return $this->rulesCache;
    }

    /**
     * Converts a stored pattern to the squid regex line: "regex:" prefixed
     * patterns are emitted verbatim, anything else goes through
     * PatternToRegex() (legacy behaviour, shared by all four generators).
     */
    private function patternLine($pattern){
        if(preg_match("#regex:(.+)#", $pattern,$re)){ return $re[1]; }
        return $this->PatternToRegex($pattern);
    }

    private function BrowsersByPass(){
        $rows=$this->fetchRules();
        $T=array();
        if(is_array($rows)){
            foreach($rows as $ligne) {
                if(intval($ligne["enabled"] ?? 0)!=1){continue;}
                if(intval($ligne["bypass"] ?? 0)!=1){continue;}
                if(intval($ligne["deny"] ?? 0)!=0){continue;}
                $T[]=$this->patternLine(strval($ligne["pattern"] ?? ""));
            }
        }

        @file_put_contents("/etc/squid3/acls/Browsers-allow.acl", @implode("\n", $T));
        @chown("/etc/squid3/acls/Browsers-allow.acl","squid");

    }

    private function BrowsersNoCache(){
        $rows=$this->fetchRules();
        $T=array();
        if(is_array($rows)){
            foreach($rows as $ligne) {
                if(intval($ligne["enabled"] ?? 0)!=1){continue;}
                if(intval($ligne["bypassWebC"] ?? 0)!=1){continue;}
                $T[]=$this->patternLine(strval($ligne["pattern"] ?? ""));
            }
        }

        @file_put_contents("/etc/squid3/acls/Browsers-nocache.acl", @implode("\n", $T));
        @chown("/etc/squid3/acls/Browsers-nocache.acl","squid");

    }

    private function BrowsersNoWebF(){
        $rows=$this->fetchRules();
        if(!is_array($rows)){
            @file_put_contents("/etc/squid3/acls/Browsers-nofilter.acl", "\n");
            @chown("/etc/squid3/acls/Browsers-nofilter.acl","squid");
            return true;
        }
        $T=array();
        foreach($rows as $ligne) {
            if(intval($ligne["enabled"] ?? 0)!=1){continue;}
            if(intval($ligne["bypassWebF"] ?? 0)!=1){continue;}
            $T[]=$this->patternLine(strval($ligne["pattern"] ?? ""));
        }

        @file_put_contents("/etc/squid3/acls/Browsers-nofilter.acl", @implode("\n", $T));
        @chown("/etc/squid3/acls/Browsers-nofilter.acl","squid");

    }

    private function BrowsersDeny(){
        $rows=$this->fetchRules();
        $T=array();
        if(is_array($rows)){
            foreach($rows as $ligne) {
                if(intval($ligne["enabled"] ?? 0)!=1){continue;}
                if(intval($ligne["bypass"] ?? 0)!=0){continue;}
                if(intval($ligne["deny"] ?? 0)!=1){continue;}
                $T[]=$this->patternLine(strval($ligne["pattern"] ?? ""));
            }
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


    /**
     * Ensures UsersAgentsDB holds the factory catalogue. Table creation runs
     * at articarest init; the default rules are seeded server-side by
     * POST /proxy/browsers/rules/seed-defaults (idempotent — no-op when the
     * table already has rows). Replaces the legacy FillTable() that issued
     * ~96 direct INSERTs from PHP.
     */
    public function checkTable(){
        $raw=$GLOBALS["CLASS_SOCKETS"]->REST_API_POST_JSON("/proxy/browsers/rules/seed-defaults", array());
        $resp=json_decode(strval($raw),true);
        if(!is_array($resp) || empty($resp["success"])){
            $err=is_array($resp)?strval($resp["error"] ?? "malformed response"):"no response";
            if(function_exists("writelogs")){
                writelogs("POST /proxy/browsers/rules/seed-defaults failed: $err",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
            }
            return;
        }
        $inserted=intval($resp["data"]["inserted"] ?? 0);
        if($inserted>0 && function_exists("writelogs")){
            writelogs("UsersAgentsDB seeded with $inserted default rules",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
        }

    }

}
