<?php

class mod_security{
    private $SERVICE_ID=0;
    private $nginx_paths=array();
    function __construct($SERVICE_ID=0,$noload=false){
        $this->SERVICE_ID=intval($SERVICE_ID);
    }
    private function mod_security_loader(){
        if(!is_dir("/etc/nginx/modsecurities")){@mkdir("/etc/nginx/modsecurities",0755,true);}
        if(!is_dir("/etc/nginx/owasp-modsecurity-crs")){@mkdir("/etc/nginx/owasp-modsecurity-crs",0755,true);}
        $rules_path="/etc/nginx/owasp-modsecurity-crs";

        if(is_file("/etc/nginx/modsecurity.conf")){
            @unlink("/etc/nginx/modsecurity.conf");
        }


        $final = "/etc/nginx/modsecurities/loader-{$this->SERVICE_ID}.conf";
        $configuration_file="/etc/nginx/modsecurities/modsecurity-{$this->SERVICE_ID}.conf";
        $crs_path="/etc/nginx/modsecurities/crs-$this->SERVICE_ID.conf";
        $white_path="/etc/nginx/modsecurities/whiterules-$this->SERVICE_ID.conf";

        $f[]="include $configuration_file";
        $f[]="include $crs_path";
        $f[]=$this->whitelists_globals();
        echo "Saving \"$final\"\n";
        @file_put_contents($final,@implode("\n",$f));

        $this->crs_setup();
        $this->mod_security();
        $this->whitelist_by_rules();

        if(!is_dir("/home/artica/modsecurity")){
            @mkdir("/home/artica/modsecurity",0755,true);
        }

        chown("/home/artica/modsecurity","www-data");
        chgrp("/home/artica/modsecurity","www-data");

    }
    function crs_setup(){

        $ModSecurityAction=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityAction");
        if($ModSecurityAction==null){$ModSecurityAction="auditlog,pass";}
        $ModSecurityParanoiaLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityParanoiaLevel"));
        if($ModSecurityParanoiaLevel==0){$ModSecurityParanoiaLevel=2;}
        $enforce_bodyproc_urlencoded=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("enforce_bodyproc_urlencoded"));
        $crs_validate_utf8_encoding=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("crs_validate_utf8_encoding"));
        $do_reput_block=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("do_reput_block"));
        $reput_block_duration=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("reput_block_duration"));
        if($reput_block_duration==0){$reput_block_duration=300;}


        $sockngix                   = new socksngix($this->SERVICE_ID);
        $ModSecurityAction_site=trim($sockngix->GET_INFO("ModSecurityAction"));
        if($ModSecurityAction_site==null){$ModSecurityAction_site=$ModSecurityAction;}
        if($ModSecurityAction_site=="DEFAULT"){$ModSecurityAction_site=$ModSecurityAction;}

        $f[]="# Global Default Action [$ModSecurityAction]";
        $f[]="# Site $this->SERVICE_ID Default Action [$ModSecurityAction_site]";
        $f[]="SecDefaultAction \"phase:1,log,$ModSecurityAction_site\"";
        $f[]="SecDefaultAction \"phase:2,log,$ModSecurityAction_site\"";
        $f[]="SecAction \"id:900000,phase:1,nolog,pass,t:none,setvar:tx.paranoia_level=$ModSecurityParanoiaLevel\"";
        $f[]="SecAction \"id:900010,phase:1,nolog,pass,t:none,setvar:tx.enforce_bodyproc_urlencoded=$enforce_bodyproc_urlencoded\"";

        $f[]="#SecAction \"id:900100,phase:1,nolog,pass,t:none,setvar:tx.critical_anomaly_score=5,setvar:tx.error_anomaly_score=4,setvar:tx.warning_anomaly_score=3,setvar:tx.notice_anomaly_score=2\"";

        $f[]="#SecAction \"id:900110,phase:1,nolog,pass,t:none,setvar:tx.inbound_anomaly_score_threshold=5,setvar:tx.outbound_anomaly_score_threshold=4\"";
        $f[]="#SecAction \"id:900130,phase:1,nolog, pass,t:none,setvar:tx.crs_exclusions_cpanel=1,setvar:tx.crs_exclusions_drupal=1,setvar:tx.crs_exclusions_dokuwiki=1,setvar:tx.crs_exclusions_nextcloud=1,setvar:tx.crs_exclusions_wordpress=1,setvar:tx.crs_exclusions_xenforo=1\"";

        $f[]="#SecAction \"id:900220,phase:1,nolog,pass,t:none,setvar:'tx.allowed_request_content_type=|application/x-www-form-urlencoded| |multipart/form-data| |multipart/related| |text/xml| |application/xml| |application/soap+xml| |application/x-amf| |application/json| |application/octet-stream| |application/csp-report| |application/xss-auditor-report| |text/plain|'\"";

        $f[]="#SecAction \"id:900230,phase:1,nolog,pass,t:none,setvar:'tx.allowed_http_versions=HTTP/1.0 HTTP/1.1 HTTP/2 HTTP/2.0'\"";
        $f[]="";
        $f[]="# Forbidden file extensions.";
        $f[]="# Guards against unintended exposure of development/configuration files.";
        $f[]="# Default: .asa/ .asax/ .ascx/ .axd/ .backup/ .bak/ .bat/ .cdx/ .cer/ .cfg/ .cmd/ .com/ .config/ .conf/ .cs/ .csproj/ .csr/ .dat/ .db/ .dbf/ .dll/ .dos/ .htr/ .htw/ .ida/ .idc/ .idq/ .inc/ .ini/ .key/ .licx/ .lnk/ .log/ .mdb/ .old/ .pass/ .pdb/ .pol/ .printer/ .pwd/ .rdb/ .resources/ .resx/ .sql/ .swp/ .sys/ .vb/ .vbs/ .vbproj/ .vsdisco/ .webinfo/ .xsd/ .xsx/";
        $f[]="# Example: .bak/ .config/ .conf/ .db/ .ini/ .log/ .old/ .pass/ .pdb/ .rdb/ .sql/";
        $f[]="# Uncomment this rule to change the default.";
        $f[]="#SecAction \"id:900240,phase:1,nolog,pass,t:none,setvar:'tx.restricted_extensions=.asa/ .asax/ .ascx/ .axd/ .backup/ .bak/ .bat/ .cdx/ .cer/ .cfg/ .cmd/ .com/ .config/ .conf/ .cs/ .csproj/ .csr/ .dat/ .db/ .dbf/ .dll/ .dos/ .htr/ .htw/ .ida/ .idc/ .idq/ .inc/ .ini/ .key/ .licx/ .lnk/ .log/ .mdb/ .old/ .pass/ .pdb/ .pol/ .printer/ .pwd/ .rdb/ .resources/ .resx/ .sql/ .swp/ .sys/ .vb/ .vbs/ .vbproj/ .vsdisco/ .webinfo/ .xsd/ .xsx/'\"";
        $f[]="";
        $f[]="# Forbidden request headers.";
        $f[]="# Header names should be lowercase, enclosed by /slashes/ as delimiters.";
        $f[]="# Blocking Proxy header prevents 'httpoxy' vulnerability: https://httpoxy.org";
        $f[]="# Default: /proxy/ /lock-token/ /content-range/ /if/";
        $f[]="# Uncomment this rule to change the default.";
        $f[]="#SecAction \"id:900250,phase:1,nolog,pass,t:none,setvar:'tx.restricted_headers=/proxy/ /lock-token/ /content-range/ /if/'\"";
        $f[]="";
        $f[]="# File extensions considered static files.";
        $f[]="# Extensions include the dot, lowercase, enclosed by /slashes/ as delimiters.";
        $f[]="# Used in DoS protection rule. See section \"Anti-Automation / DoS Protection\".";
        $f[]="# Default: /.jpg/ /.jpeg/ /.png/ /.gif/ /.js/ /.css/ /.ico/ /.svg/ /.webp/";
        $f[]="# Uncomment this rule to change the default.";
        $f[]="#SecAction \"id:900260,phase:1,nolog,pass,t:none,setvar:'tx.static_extensions=/.jpg/ /.jpeg/ /.png/ /.gif/ /.js/ /.css/ /.ico/ /.svg/ /.webp/'\"";
        $f[]="";
        $f[]="# Content-Types charsets that a client is allowed to send in a request.";
        $f[]="# Default: utf-8|iso-8859-1|iso-8859-15|windows-1252";
        $f[]="# Uncomment this rule to change the default.";
        $f[]="# Use \"|\" to separate multiple charsets like in the rule defining";
        $f[]="# tx.allowed_request_content_type.";
        $f[]="#SecAction \"id:900280,phase:1,nolog,pass,t:none,setvar:'tx.allowed_request_content_type_charset=utf-8|iso-8859-1|iso-8859-15|windows-1252'\"";
        $f[]="";
        $f[]="#";
        $f[]="#SecAction \"id:900330,phase:1,nolog,pass,t:none,setvar:tx.total_arg_length=64000\"";
        $f[]="#SecAction \"id:900340,phase:1,nolog,pass,t:none,setvar:tx.max_file_size=1048576\"";
        $f[]="#SecAction \"id:900350,phase:1,nolog,pass,t:none,setvar:tx.combined_file_sizes=1048576\"";
        $f[]="#SecAction \"id:900400,phase:1,pass,nolog,setvar:tx.sampling_percentage=100\"";
        $f[]="";

        $ModSecurityHTTPBL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityHTTPBL"));
        $SecHttpBlKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlKey"));

        if($ModSecurityHTTPBL==1){
            if($SecHttpBlKey<>null){
                $SecHttpBlSE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlSE"));
                $SecHttpBlSus=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlSus"));
                $SecHttpBlHar=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlHar"));
                $SecHttpBlSpam=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SecHttpBlSpam"));
                $f[]="# -- [[ Project Honey Pot HTTP Blacklist ]] ------------------------------------";
                $f[]="SecHttpBlKey $SecHttpBlKey";
                $f[]="SecAction \"id:900500,phase:1,nolog,pass,t:none,setvar:tx.block_search_ip=$SecHttpBlSE,setvar:tx.block_suspicious_ip=$SecHttpBlSus,setvar:tx.block_harvester_ip=$SecHttpBlHar,setvar:tx.block_spammer_ip=$SecHttpBlSpam\"";
                $f[]="";
            }
        }

        $f[]="#SecAction \"id:900600,phase:1,nolog,pass,t:none,setvar:'tx.high_risk_country_codes='\"";
        $f[]="";

        $ModSecurityDoS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDoS"));
        if($ModSecurityDoS==1) {
            $ModSecurityDoSRqs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDoSRqs"));
            $ModSecurityDoSTT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDoSTT"));
            $ModSecurityDoSBK=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityDoSBK"));
            if($ModSecurityDoSRqs==0){$ModSecurityDoSRqs=100;}
            if($ModSecurityDoSTT==0){$ModSecurityDoSTT=60;}
            if($ModSecurityDoSBK==0){$ModSecurityDoSBK=600;}
            $f[]="# -- [[ Anti-Automation / DoS Protection ]] ------------------------------------";
            $f[] = "SecAction \"id:900700,phase:1,nolog,pass,t:none,setvar:'tx.dos_burst_time_slice={$ModSecurityDoSTT}',setvar:'tx.dos_counter_threshold={$ModSecurityDoSRqs}',setvar:'tx.dos_block_timeout={$ModSecurityDoSBK}'\"";
            $f[]="";
        }

        $f[]="SecAction \"id:900950,phase:1,nolog,pass,t:none,setvar:tx.crs_validate_utf8_encoding=$crs_validate_utf8_encoding\"";
        $f[]="SecAction \"id:900960,phase:1,nolog,pass,t:none,setvar:tx.do_reput_block=$do_reput_block\"";
        $f[]="SecAction \"id:900970,phase:1,nolog,pass,t:none,setvar:tx.reput_block_duration=$reput_block_duration\"";
        $f[]="#\n";
        $crs_path="/etc/nginx/modsecurities/crs-$this->SERVICE_ID.conf";
        @file_put_contents($crs_path,@implode("\n",$f));
    }


    private function mod_security():bool{
        if($this->SERVICE_ID==0){return false;}
        $configuration_path="/etc/nginx/modsecurities/modsecurity-{$this->SERVICE_ID}.conf";
        $ModSecurityAction=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ModSecurityAction");
        $sockngix                   = new socksngix($this->SERVICE_ID);
        $ModSecurityAction_site=$sockngix->GET_INFO("ModSecurityAction");
        if($ModSecurityAction_site==null){$ModSecurityAction_site=$ModSecurityAction;}
        if($ModSecurityAction_site=="DEFAULT"){$ModSecurityAction_site=$ModSecurityAction;}
        $SecRuleEngine="DetectionOnly";
        if($ModSecurityAction_site=="auditlog,deny,status:405"){$SecRuleEngine="On";}

        $f[] = "SecRuleEngine $SecRuleEngine";

        $f[] = "";
        $f[] = "";
        echo "Saving \"$configuration_path\"\n";
        @file_put_contents($configuration_path, @implode("\n", $f));
        return true;
    }

    function build_nginx_configuration($SERVICE_ID):bool{
        if(!is_file("/etc/nginx/owasp-modsecurity-crs/ARTICA-RULES.conf")){
            if(!is_dir("/etc/nginx/owasp-modsecurity-crs")){
                @mkdir("/etc/nginx/owasp-modsecurity-crs",0755,true);
                @file_put_contents("/etc/nginx/owasp-modsecurity-crs/ARTICA-RULES.conf","\n");
            }
        }

        $path="/etc/nginx/modsecurities/nginx-$SERVICE_ID.conf";
        $modsecurities_file = "/etc/nginx/modsecurities/loader-$SERVICE_ID.conf";
        $cleans[]=$path;
        $cleans[]=$modsecurities_file;
        $cleans[]="/etc/nginx/modsecurities/whiterules-$SERVICE_ID.conf";
        $cleans[]="/etc/nginx/modsecurities/locations-$SERVICE_ID.conf";

        $f=array();
        $f[]="# ModSecurity :";
        $NginxHTTPModSecurity   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPModSecurity"));
        $EnableModSecurityIngix = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
        $f[]="#\tFeature installed: EnableModSecurityIngix = $EnableModSecurityIngix";
        $f[]="#\tInstalled        : NginxHTTPModSecurity   = $NginxHTTPModSecurity L.".__LINE__;
        $sock                   = new socksngix($SERVICE_ID);
        $EnableModSecurity      = intval($sock->GET_INFO("EnableModSecurity"));
        $f[]="#\tEnabled in site - EnableModSecurity = $EnableModSecurity\n";

        foreach ($cleans as $filepath){
            @file_put_contents($filepath,@implode("\n",$f));
        }


        if($EnableModSecurityIngix==0){
            @file_put_contents($path,@implode("\n",$f));
            return true;
        }

        if($NginxHTTPModSecurity==0){
            @file_put_contents($path,@implode("\n",$f));
            return true;
        }


        if($EnableModSecurity==0){
            $f[] = "\tmodsecurity off;";
            @file_put_contents($path,@implode("\n",$f));
            return true;
        }
        $f[] = "\tmodsecurity on;";
        $f[] = "\tmodsecurity_rules_file $modsecurities_file;";
        $f[] = "";
        echo "Saving $path\n";
        @file_put_contents($path,@implode("\n",$f));
        $this->mod_security_loader();
        return true;

    }

    function whitelists_globals():string{
        $RULES=array();
        $UAS=array();
        $f[]="# GLobal Whitelists";
        $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
        $sql="SELECT * FROM `modsecurity_whitelist` WHERE serviceid=$this->SERVICE_ID OR serviceid=0 ORDER BY spath";
        $results=$q->QUERY_SQL($sql);
        $f[]="# ".count($results)." rule(s)";
        foreach ($results as $index=>$ligne){
            $enabled=intval($ligne["enabled"]);
            if($enabled==0){continue;}
            $spath=trim($ligne["spath"]);
            $wfrule=intval($ligne["wfrule"]);
            if($wfrule==0){continue;}
            if($spath=="/"){$spath=null;}
            if(preg_match("#(User-Agent|useragent|agent):(.+)#i",$spath,$re)){
                if ($wfrule == 0) {$UAS[trim($re[2])] = "";continue;}
                $UAS[trim($re[1])]= "ctl:ruleRemoveById=$wfrule";
                continue;
            }

            if(strlen($spath)>1){continue;}
            $RULES[$wfrule]=true;
        }

        $t=array();

        foreach ($RULES as $ruleid=>$ligne){
            $t[]=$ruleid;
            $f[]="SecruleUpdateActionById $ruleid \"pass,nolog\"";

        }
        if(count($t)>0){
            $f[]="SecRuleRemoveById ".@implode(" ",$t);
        }

        $servid=$this->SERVICE_ID*300;
        $index_id=97000000+$servid;
        $USER_AGENT="SecRule REQUEST_HEADERS:User-Agent";

        if(count($UAS)>0){
            foreach ($UAS as $useragent=>$rules) {
                $index_id = $index_id - 1;
                $ruleid = $index_id;
                $f[]="# UA $useragent -> [$rules]";
                if($rules==null){
                    $f[]="$USER_AGENT \"@contains $useragent\" \"phase:2,t:none,nolog,pass,id:$ruleid\"";
                    continue;
                }
                $f[]="$USER_AGENT \"@contains $useragent\" \"phase:2,t:none,nolog,pass,id:$ruleid,$rules\"";
            }
        }
        $f[]="";
        return @implode("\n",$f);
    }



    public function whitelist_by_rules(){
        echo "Building Whitelists by rules...\n";
        $zlog[]="# Paths to exclude with rules";
        $white_path="/etc/nginx/modsecurities/whiterules-$this->SERVICE_ID.conf";
        $nginx_locations="/etc/nginx/modsecurities/locations-$this->SERVICE_ID.conf";
        $include_config="/etc/nginx/root-directives/$this->SERVICE_ID.conf";
        if(!is_file($include_config)){
            $zlog[]="# $include_config no such file\n";
            @file_put_contents($nginx_locations,@implode("\n",$zlog));
            return true;
        }
        $sql="SELECT * FROM `modsecurity_whitelist` WHERE serviceid=$this->SERVICE_ID OR serviceid=0 ORDER BY spath DESC";
        $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
        $results=$q->QUERY_SQL($sql);
        $zlog=array();
        $nginx_xpaths=array();
        $GlobalRulesIDArray=array();
        $global_white_path=array();
        $SecruleUpdateActionById=array();
        $GlobalRulesIDBySite=array();

        foreach ($results as $index=>$ligne){
            $enabled=intval($ligne["enabled"]);
            if($enabled==0){continue;}
            $wfrule=intval($ligne["wfrule"]);
            $spath=trim($ligne["spath"]);
            if($spath=="/"){$spath=null;}
            if($spath==null){
                if($wfrule>0) {
                    $GlobalRulesIDBySite[$wfrule] = true;
                }
                $zlog[]="# $wfrule: Whitelisted for all site.";
                continue;
            }


            if(preg_match("#(User-Agent|useragent|agent):(.+)#i",$spath)){continue;}
            $zlog[]="# $spath = $wfrule";
            if($wfrule==0) {
                $nginx_xpaths[$spath] = true;
                continue;
            }
            if(isset($nginx_xpaths[$spath])){continue;}
            $zlog[]="# $spath = $wfrule";
            $nginx_paths[$spath][$wfrule]=true;

        }

        if(count($GlobalRulesIDBySite)>0){
            foreach ($GlobalRulesIDBySite as $ruleid=>$none){
                if(intval($ruleid)==0){continue;}
                $global_white_path[]="SecruleUpdateActionById $ruleid \"pass,nolog\"";
                $SecruleUpdateActionById[]="\t\tSecruleUpdateActionById $ruleid \"pass,nolog\"";
                $GlobalRulesIDArray[]=$ruleid;
            }
        }

        if(count($GlobalRulesIDArray)>0) {
            $global_white_path[] = "SecRuleRemoveById ".@implode(" ", $GlobalRulesIDArray);
        }

        @file_put_contents($white_path,@implode("\n",$global_white_path));
        $zlog[]="# $wfrule: SecruleUpdateActionById = ".count($SecruleUpdateActionById)." elements";
        $zpaths[]="# Global Whitelists for site ID $this->SERVICE_ID with paths";

        if(count($nginx_xpaths)>0){
            foreach ($nginx_xpaths as $path=>$none) {
                if(isset($ALREADY[$path])){continue;}
                $ALREADY[$path]=true;
                $path = str_replace(".", "\.", $path);
                $zpaths[] = "location ~ $path {";
                $zpaths[] = "\tmodsecurity off;";
                $zpaths[] = "\tinclude $include_config;";
                $zpaths[] = "}\n";
            }

        }

        if(count($nginx_paths)>0){
            foreach ($nginx_paths as $path=>$ruleids){
                if(isset($ALREADY[$path])){continue;}
                $path=str_replace(".","\.",$path);
                $zpaths[]= "location ~ $path {";
                $zpaths[]= "\tmodsecurity_rules '";
                $xrulesid=array();
                $cleaned_rules=array();
                if(count($ruleids)>0) {
                    foreach ($ruleids as $ruleid => $none) {
                        $cleaned_rules[$ruleid] = intval($ruleid);
                    }
                }
                if(count($GlobalRulesIDBySite)>0) {
                    foreach ($GlobalRulesIDBySite as $ruleid => $none) {
                        $cleaned_rules[$ruleid] = intval($ruleid);
                    }
                }
                if(count($cleaned_rules)>0) {
                    foreach ($cleaned_rules as $ruleid => $none) {
                        if (intval($ruleid) == 0) {
                            continue;
                        }
                        $xrulesid[] = $ruleid;
                    }
                }

                if(count($SecruleUpdateActionById)>0){
                    $zpaths[]=@implode("\n",$SecruleUpdateActionById);
                }

                if(count($xrulesid)>0){
                    $zpaths[]= "\t\tSecRuleRemoveById ".@implode(" ",$xrulesid);
                }

                $zpaths[] = "\t';";
                $zpaths[] = "\tinclude $include_config;";
                $zpaths[] = "}\n";
            }
        }
        $final=@implode("\n",$zlog)."\n".@implode("\n",$zpaths);
        echo "Saving $nginx_locations\n";
        @file_put_contents($nginx_locations,$final);
        return true;
    }
}