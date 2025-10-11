<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).  '/class.mysql.inc');
include_once(dirname(__FILE__).  '/class.haproxy.acls.inc');
include_once(dirname(__FILE__).  '/class.haproxy_reverse.inc');
include_once(dirname(__FILE__).  '/class.squid.certificates.inc');
class haproxy{
    var $mode=array(0=>"tcp",1=>"http",2=>"http");
    var $acls_actions=array(0=>"{do_nothing}",1=>"{use_backend}",2=>"{use_server}",
        3=>"{deny}", //tcp-request connection reject
    );
    var $acl_operator=array(0=>"{and}",1=>"{or}");
    var $acl_GroupType=array();
    private $haproxyversion;
    private $MAJOR=1;
    private $MINOR=5;
    private $servicetype_array=array(
        0=>"HTTP/HTTPs Proxy Load-balancer",
        1=>"HTTP Load-balancer",
        2=>"Reverse Proxy",
        3=>"TCP redirect",
        4=>"SMTP redirect"
    );

    function __construct(){
        $this->acl_GroupType["src"]="{src_addr}";
        $this->acl_GroupType["hdr(host)"]="{dstdomain}";
        $this->acl_GroupType["dst"]="{dst_addr}";
        $this->acl_GroupType["dst_port"]="{destination_port}";
    }

    function buildconf(){
        if($this->haproxyversion==null){$this->haproxyversion=$this->haversion();}
        $HaProxyMaxConn=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterMaxConn"));
        $HaProxyCPUS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyCPUS"));
        $HaProxyMemoryCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyMemoryCache"));
        $HaProxyMaxMemoryObjects =intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyMaxMemoryObjects"));
        if($HaProxyMaxMemoryObjects==0){$HaProxyMaxMemoryObjects=10000;}
        $tline="--------------------------";

        if($HaProxyMaxConn<2000){$HaProxyMaxConn=2000;}

        $this->MAJOR=1;
        $this->MINOR=5;

        if(preg_match("#^([0-9]+)\.([0-9]+)#", $this->haproxyversion,$re)){
            $this->MAJOR=intval($re[1]);
            $this->MINOR=intval($re[2]);

        }
        $f[]="# $this->haproxyversion $this->MAJOR,$this->MINOR";
        $f[]="# CPU(s) $HaProxyCPUS";
        $f[]="global";
        $f[]="\tlog	127.0.0.1 local0";

        $f[]="\tmaxconn	$HaProxyMaxConn";

        if($HaProxyCPUS>1){
            $cpumap=$HaProxyCPUS-1;
            if(intval( $this->MAJOR.$this->MINOR)>25){
                $f[]="\tnbthread           $HaProxyCPUS";
                $f[]="\tcpu-map auto:1/1-$HaProxyCPUS 0-$cpumap";
            }
            else {
                $f[]="\tnbproc           $HaProxyCPUS";


            for($i=1;$i<$HaProxyCPUS+1;$i++){
                $cpumap=$i-1;
                $f[]="\tcpu-map           $i $cpumap";
            }

            }

        }
        $ulimit=$HaProxyMaxConn *3;
        $f[]="\tuid	0";
        $f[]="\tgid	0";
        $f[]="\tchroot	/tmp";
        $f[]="\tpidfile /var/run/haproxy.pid";
        $f[]="\tulimit-n          $ulimit";
        $f[]="\tstats socket /var/run/haproxy.stat mode 600 level admin";
        if($this->MAJOR<3) {
            if($this->MINOR<7) {
                $f[] = "\tstats bind-process 1";
            }
        }
        $f[]="\tdaemon";
        $f[]="#\tdebug";
        $f[]="#\tquiet";
        $f[]="";
        $f[]="defaults";
        $f[]="\tlog\tglobal";
        $splunkfw = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SplunkForwarderEnabled"));
        if($splunkfw==1){
            $f[]="\tlog-format\tHASTATS:::%{+Q}o:::client_ip=%ci:::client_port=%cp:::datetime_of_request=[%tr]:::frontend_name_transport=%ft:::backend_name=%b:::server_name=%s:::time_to_receive_full_request=%TR:::Tw=%Tw:::Tc=%Tc:::response_time=%Tr:::active_time_of_request=%Ta:::status_code=%ST:::bytes_read=%B:::captured_request_cookie=%CC:::captured_response_cookie=%CS:::termination_state_with_cookie_status=%tsc:::actconn=%ac:::feconn=%fc:::beconn=%bc:::srv_conn=%sc:::retries=%rc:::srv_queue=%sq:::backend_queue=%bq:::captured_request_headers_default_style=%hr:::captured_response_headers_default_style=%hs:::server_ip=%si:::server_port=%sp:::frontend_name=%f:::http_method=%HM:::http_request_uri_without_query=%HP:::http_request_query_string=%HQ:::http_request_uri=%HU:::bytes_uploaded=%U:::ssl_ciphers=%sslc:::ssl_version=%sslv:::%[capture.res.hdr(0)]";

        }
        else{
            $f[]="\t".$this->logformat();
        }
        //$f[]="\t".$this->logformat();
        //$f[]="\toption\thttplog";

        $f[]="\tmode\thttp";
        $f[]="\toption\tdontlognull";
        //$f[]="\toption\tlogasap"; ne donne pas les infos correctes
        $f[]="\tretries\t3";
        $f[]="\toption\tredispatch";
        $f[]="\tmaxconn\t$HaProxyMaxConn";
        $f[]="\ttimeout connect\t5000";
        $f[]="\ttimeout client\t50000";
        $f[]="\ttimeout server\t50000";
        $f[]="";

        if($HaProxyMemoryCache>0) {
            $f[] = "cache memorycache";
            $f[] = "\ttotal-max-size $HaProxyMemoryCache";
            $f[] = "\tmax-object-size $HaProxyMaxMemoryObjects";
            $f[] = "\tmax-age 7200";
            $f[] = "";
        }
        $sql="SELECT * FROM haproxy WHERE enabled=1";
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $results=$q->QUERY_SQL($sql);
        if(!$q->ok){if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building configuration failed $q->mysql_error\n";return;}}
        if(count($results)==0){
            if($GLOBALS["AS_ROOT"]){
                echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} No backend defined\n";
                return;
            }
        }

        foreach ($results as $index=>$ligne){
            $options=array();
            $transparent_text=null;
            $send_proxy_v2=0;
            $transparent=$ligne["transparent"];
            $tunnel_mode=$ligne["tunnel_mode"];
            $dispatch_mode=$ligne["dispatch_mode"];
            $loadbalancetype=intval($ligne["loadbalancetype"]);
            $servicetype=intval($ligne["servicetype"]);

            $MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
            $MainConfig["maxretries"]=$MainConfig["retries"];
            $MainConfig["transparent"]=$transparent;
            $MainConfig["tunnel_mode"]=$tunnel_mode;
            $MainConfig["dispatch_mode"]=$dispatch_mode;
            $MainConfig["loadbalancetype"]=$loadbalancetype;
            $forwardfor=intval($MainConfig["forwardfor"]);

            if($transparent==1){$ligne["listen_ip"]="127.0.0.1";}
            $servicetype_text=$this->servicetype_array[$servicetype];
            if($GLOBALS["VERBOSE"]) {
                $f[] = "# $tline {$ligne["servicename"]} $servicetype_text balance:$loadbalancetype";
                $f[] = "# $tline Service Type: $servicetype";
            }
            $f[]=$this->build_frontend($ligne["servicename"],$ligne["listen_ip"],$ligne["listen_port"],$MainConfig);

            if($servicetype==2){
                if($GLOBALS["VERBOSE"]){$f[]="# $tline Build as reverse proxy:";}
                $haproxy_reverse=new haproxy_reverse($ligne["servicename"]);
                $f[]=$haproxy_reverse->build();
                continue;
            }

            if($servicetype==1 OR $servicetype==0){
                if($GLOBALS["VERBOSE"]) {
                    $f[] = "# $tline Service Type: HTTP Options";
                    $f[] = "# $tline x-Forward : {$MainConfig["forwardfor"]}";
                }
                if ($MainConfig["forwardfor"] == 1) {
                    $options[] = "\toption\tforwardfor";
                }


            }
            if($GLOBALS["VERBOSE"]){$f[]="# $tline http-keep-alive: {$MainConfig["http-keep-alive"]}";}
            if ($MainConfig["http-keep-alive"] == 1) {
                $options[]= "\toption\thttp-keep-alive";
            }

            if($loadbalancetype==2){
                if($GLOBALS["VERBOSE"]){
                    $f[]="# $tline Use Proxy Protocol";
                    $f[]="# $tline is Forced because loadbalancetype=2 ( HTTP Proxy )";
                }
                $send_proxy_v2=1;
            }

            $build_backends=$this->build_backends($ligne["servicename"],$MainConfig,$send_proxy_v2,$servicetype);
            if ($this->hasDRGroup($ligne["servicename"],$MainConfig,$send_proxy_v2,$servicetype)){
                $build_backends_dr=$this->build_backends_dr($ligne["servicename"],$MainConfig,$send_proxy_v2,$servicetype);
            }
            $default_backend=$this->default_backend($ligne["servicename"]);



            if($default_backend==null){
                if($build_backends<>null){

                    $default_backend="\tdefault_backend default_{$ligne["servicename"]}";
                }
            }

            $f[]=$this->BuildOptionsFromMainConfig($MainConfig,$servicetype);

            if($default_backend<>null){
                if($this->hasDRGroup($ligne["servicename"],$MainConfig,$send_proxy_v2,$servicetype)){
                    $f[]="#\tdetect capacity issues in production farm";
                    $f[]="\tacl is_prod_up nbsrv(default_{$ligne["servicename"]}) -m int gt 0";
                    $f[]="#\tfailover traffic to backup farm";
                    $f[]="\tuse_backend default_{$ligne["servicename"]} if is_prod_up";
                    $f[]="\tuse_backend dr_{$ligne["servicename"]}";

                } else {
                    if($GLOBALS["VERBOSE"]){$f[]="#\tBackend by default";}
                    $f[]=$default_backend;
                }

            }
            $f[]="";


            if($build_backends<>null){
                if($GLOBALS["VERBOSE"]){$f[]="# $tline BACKENDS";}
                $f[]=$build_backends;
            }
            if($build_backends_dr<>null){
                if($GLOBALS["VERBOSE"]){$f[]="# $tline BACKENDS";}
                $f[]=$build_backends_dr;
            }


            if($GLOBALS["VERBOSE"]){$f[]="# $tline Backends groups.";}
            $f[]=$this->build_backends_groups($ligne["servicename"],$servicetype);
            if($GLOBALS["VERBOSE"]){$f[]="# $tline";}
        }

        $f[]=$this->admin_page();

        return @implode("\n", $f);

    }

    private function build_frontend_certificates($certificates,$servicename){
        $fileindex="/etc/haproxy/$servicename-list.txt";
        if(is_file($fileindex)){@unlink($fileindex);}
        $squid=new squid_certificate();
        $f=array();
        foreach ($certificates as $index=>$ligne){
            $commname=$ligne["certificate"];
            $ss_min_ver=$ligne["ssl-min-ver"];
            $ciphers=trim($ligne["ciphers"]);
            $snifilter=trim($ligne["snifilter"]);
            $optsC=null;
            if(strtolower($ciphers)=="none"){$ciphers=null;}
            $opts=array();
            if($ss_min_ver<>null){
                $opts[]="ssl-min-ver $ss_min_ver";
            }
            if($ciphers<>null){
                $opts[]="ciphers $ciphers";
            }


            $squid->BuildSquidCertificate($commname);
            if($squid->key_out<>null){
                if(count($opts)>0){$optsC=" [".@implode(" ",$opts)."]";}
                if($snifilter<>null){$snifilter=" $snifilter";}
                $f[]=$squid->key_out.$optsC.$snifilter;
            }
        }

        if(count($f)==0){return false;}
        @file_put_contents($fileindex,@implode("\n",$f)."\n");
        $this->frontend_ssl_options="ssl crt-list $fileindex ";
        return true;

    }


    private function haversion():string{
        $version="0.0.0";
        if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
        $unix=new unix();
        $haproxy=$unix->find_program("haproxy");
        exec("$haproxy -v 2>&1",$results);
        foreach ($results as $val){
            if(preg_match("#^(HA-Proxy|HAProxy)\s+version\s+([0-9\.]+)#", $val,$re)){
                return trim($re[2]);
            }
        }
        return $version;
    }

    private function build_frontend($servicename,$listen_ip,$listen_port,$MainConfig){
        $ha=new haproxy_multi($servicename);
        $Type=$ha->servicetype;
        $sslstr=null;

        if($GLOBALS["VERBOSE"]){$f[] = "# Front-End type=$Type";}
        if($Type==0 OR $Type==1){
            if($ha->ssl==1){
                $CountOfCertificates=count($ha->certificates);
                $f[] = "# SSl enabled with $CountOfCertificates certificates";
                if($CountOfCertificates>0){
                    if($this->build_frontend_certificates($ha->certificates,$servicename)){
                        $sslstr=$this->frontend_ssl_options;
                    }
                }
            }
        }
        $KERBEROS_COMPATIBILITY=intval($MainConfig["KERBEROS_COMPATIBILITY"]);
        $loadbalancetype=$MainConfig["loadbalancetype"];
        $mode='tcp';
        if($loadbalancetype==0){
            $mode='tcp';
        }
        else{

            if($KERBEROS_COMPATIBILITY==0) {
                $mode='http';
            }else{
                //$mode='tcp';
                $mode='http';
            }

        }
        $f[] = "frontend $servicename";
        $f[] = "\tbind\t$listen_ip:$listen_port {$sslstr}name $servicename";
        $splunkfw = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SplunkForwarderEnabled"));
        if($splunkfw==1){
            $f[]= "\tcapture request header Host len 1024";
            $f[]= "\tcapture request header Content-Type len 1024";
            $f[]= "\tcapture request header User-Agent len 1024";
            $f[]= "\tcapture request header Referer len 1024";
            $f[]= "\tcapture request header X-Forwarded-For len 1024";
            $f[]= "\tcapture response header Content-Type len 1024";
            $f[]= "\tcapture cookie Cookie_2 len 100";
            $f[]= "\thttp-request set-header mode mode:{$mode}";
            $f[]= "\thttp-request capture hdr(mode)  len 10";
        }


        return @implode("\n", $f);
    }


    private function admin_page(){
        $f[]="frontend admin_page";
        $f[]="\tbind\t127.0.0.1:64741";
        $f[]="\tmode http";
        $f[]="\tstats enable";
        $f[]="\tstats refresh 10s";
        $f[]="\tstats uri /stats";
        return @implode("\n", $f)."\n";

    }


    private function backends_gpid_count($gpid){
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $table="(SELECT haproxy_backends_link.ID as tid,
		haproxy_backends.* FROM haproxy_backends_link,haproxy_backends WHERE
		haproxy_backends.ID=haproxy_backends_link.backendid AND
		haproxy_backends_link.gpid='$gpid' ORDER BY bweight) as t";

        $sql="SELECT count(*) as tcount FROM $table";
        $ligne=$q->mysqli_fetch_array($sql);
        return intval($ligne["tcount"]);
    }

    private function default_backend($servicename){
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $sql="SELECT groupname,ID FROM haproxy_backends_groups WHERE servicename='$servicename' AND enabled=1 AND `default`=1";
        $ligne=$q->mysqli_fetch_array($sql);
        if($ligne["groupname"]==null){return null;}
        if(!is_numeric($ligne["ID"])){return null;}
        if($this->backends_gpid_count($ligne["ID"])==0){return null;}
        return "\tdefault_backend {$ligne["groupname"]}";
    }

    private function logformat($mode="http"){
        $f[]="%Ts";
        $f[]="%ci"; //client_ip
        $f[]="%H"; //hostname;
        $f[]="%b"; //backend_name;
        $f[]="%bi"; //backend_source_ip;
        $f[]="%bp"; //backend_source_port;
        $f[]="%s"; //server_name
        $f[]="%si"; //server_IP
        $f[]="%sp"; //server_port
        $f[]="%r"; //http_request
        $f[]="%ST"; // status_code;
        $f[]="%B"; //bytes_read
        $f[]="%U"; //bytes_uploaded
        $f[]="%ts"; //Terminaison state
        return "log-format HASTATS:::".@implode(":::", $f);

    }



    private function build_backends_groups($servicename,$servicetype=null){

        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $results=$q->QUERY_SQL("SELECT * FROM haproxy_backends_groups WHERE servicename='$servicename'");
        if(!$q->ok){return "# MySQL error build_backends_groups($servicename)\n#$q->mysql_error";}
        if(count($results)==0){return "# $servicename no result";}

        foreach ($results as $index=>$ligne){
            $groupname=$ligne["groupname"];
            $ID=$ligne["ID"];
            if($this->backends_gpid_count($ID)==0){continue;}
            $f[]="backend $groupname";


            $MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
            $MainConfig["servicename"]=$servicename;

            $f[]=$this->BuildOptionsFromMainConfig($MainConfig,$servicetype,true);
            $f[]=$this->build_groups_backends($ID,$MainConfig);

        }

        return @implode("\n",$f)."\n";


    }


    private function SetDefaults($MainConfig){
        if(!isset($MainConfig["servicename"])){return $MainConfig;}
        if($MainConfig["servicename"]==null){return $MainConfig;}
        $hap=new haproxy_multi($MainConfig["servicename"]);
        if(!isset($MainConfig["loadbalancetype"])){$MainConfig["loadbalancetype"]=$hap->loadbalancetype;}
        if(!isset($MainConfig["tunnel_mode"])){$MainConfig["tunnel_mode"]=$hap->tunnel_mode;}
        if(!isset($MainConfig["dispatch_mode"])){$MainConfig["dispatch_mode"]=$hap->dispatch_mode;}
        if(!isset($MainConfig["UseCookies"])){$MainConfig["UseCookies"]=$hap->MainConfig["UseCookies"];}

        if(!isset($MainConfig["contimeout"])){$MainConfig["contimeout"]=$hap->MainConfig["contimeout"];}
        if(!isset($MainConfig["srvtimeout"])){$MainConfig["srvtimeout"]=$hap->MainConfig["srvtimeout"];}
        if(!isset($MainConfig["clitimeout"])){$MainConfig["clitimeout"]=$hap->MainConfig["clitimeout"];}
        if(!isset($MainConfig["maxretries"])){$MainConfig["maxretries"]=$hap->MainConfig["retries"];}
        if(!isset($MainConfig["http-keep-alive"])){$MainConfig["http-keep-alive"]=$hap->MainConfig["http-keep-alive"];}
        if(!isset($MainConfig["NTLM_COMPATIBILITY"])){$MainConfig["NTLM_COMPATIBILITY"]=$hap->MainConfig["NTLM_COMPATIBILITY"];}

        if(!is_numeric($MainConfig["transparent"])){$MainConfig["transparent"]=0;}
        if(!is_numeric($MainConfig["tunnel_mode"])){$MainConfig["tunnel_mode"]=0;}
        if(!is_numeric($MainConfig["NTLM_COMPATIBILITY"])){$MainConfig["NTLM_COMPATIBILITY"]=0;}
        if(!is_numeric($MainConfig["UseCookies"])){$MainConfig["UseCookies"]=0;}
        if(!is_numeric($MainConfig["http-use-proxy-header"])){$MainConfig["http-use-proxy-header"]=1;}
        if(!is_numeric($MainConfig["accept-invalid-http-request"])){$MainConfig["accept-invalid-http-request"]=1;}
        if(!is_numeric($MainConfig["forwardfor"])){$MainConfig["forwardfor"]=1;}
        if(!is_numeric($MainConfig["originalto"])){$MainConfig["originalto"]=1;}
        if(!is_numeric($MainConfig["http-keep-alive"])){$MainConfig["http-keep-alive"]=1;}
        return $MainConfig;
    }

    private function BuildOptionsFromMainConfig($MainConfig,$servicetype,$BACKEND=false){
        $options=array();
        $tline="--------------------------";
        $MainConfig=$this->SetDefaults($MainConfig);
        $loadbalancetype=$MainConfig["loadbalancetype"];
        $KERBEROS_COMPATIBILITY=intval($MainConfig["KERBEROS_COMPATIBILITY"]);
        if($servicetype==0){$loadbalancetype=1;}
        if($servicetype==1){$loadbalancetype=1;}
        if($servicetype==2){$loadbalancetype=1;}


        if($MainConfig["asSquidArtica"]==1){$loadbalancetype=1;}
        if($KERBEROS_COMPATIBILITY==1){$MainConfig["NTLM_COMPATIBILITY"]=0;}


        $tunnel_mode=$MainConfig["tunnel_mode"];
        $dispatch_mode=$MainConfig["dispatch_mode"];

        if($MainConfig["UseSMTPProto"]==1){$tunnel_mode=0;$MainConfig["NTLM_COMPATIBILITY"]=0;}
        if($GLOBALS["VERBOSE"]) {
            $options[] = "# $tline Mode '$loadbalancetype' " . date("Y-m-d H:i:s");
            $options[] = "# $tline asSquidArtica '{$MainConfig["asSquidArtica"]}'";
        }
        if($KERBEROS_COMPATIBILITY==0) {
            $options[] = "\tmode " . $this->mode[$loadbalancetype];
        }else{
            $options[] = "\tmode tcp";
        }

        if($tunnel_mode==1){$dispatch_mode="leastconn";}
        if($MainConfig["asSquidArtica"]==1){$dispatch_mode="roundrobin";}

        if($dispatch_mode<>null){
            if($BACKEND) {
                $options[] = "\tbalance $dispatch_mode";
            }
        }


        if(intval($MainConfig["HttpRequestTimeout"])==0){$MainConfig["HttpRequestTimeout"]=1500;}
        if(intval($MainConfig["TimeoutTunnel"])==0){$MainConfig["TimeoutTunnel"]=30000;}
        if(intval($MainConfig["HttpKeepAliveTimeout"])==0){$MainConfig["HttpKeepAliveTimeout"]=15000;}


        if($loadbalancetype==0){
            if(!$BACKEND){$options[]="\toption\ttcplog";}
            if(!$BACKEND){$options[]="\toption\tdontlognull";}
            $MainConfig["UseCookies"]=0;}
        else{

            if($KERBEROS_COMPATIBILITY==0) {
                if(!$BACKEND){$options[] = "\toption\thttplog";}
            }else{
                //if(!$BACKEND){$options[] = "\toption\ttcplog";}
                if(!$BACKEND){$options[] = "\toption\thttplog";}
                if(!$BACKEND){$options[] = "\toption\tdontlognull";}
            }

        }
        if($KERBEROS_COMPATIBILITY==0) {
            if ($MainConfig["asSquidArtica"] == 1) {
                $options[] = "\tcookie  SERVERID insert indirect nocache";
                $MainConfig["forwardfor"] = 1;
            }


            if($MainConfig["NTLM_COMPATIBILITY"]==1){ $MainConfig["http-keep-alive"]=1;}
            $options[]="#\thttp-keep-alive: * {$MainConfig["http-keep-alive"]} *";

            if($MainConfig["http-keep-alive"]==1){
                $options[]="\toption http-keep-alive";
                $options[]="\ttimeout http-keep-alive {$MainConfig["HttpKeepAliveTimeout"]}";

            }
            if(intval($MainConfig["http-use-proxy-header"])==1){
                $options[]="\toption http-use-proxy-header";

            }
            if(intval($MainConfig["accept-invalid-http-request"])==1){
                $options[]="\toption accept-invalid-http-request";

            }
            if(empty($MainConfig["http_reuse"])){
                $MainConfig["http_reuse"] ='Disable';
            }
            if($MainConfig["http_reuse"] !=='Disable'){
                $options[]="\thttp-reuse {$MainConfig["http_reuse"]}";

            }
        }
        if($MainConfig["NTLM_COMPATIBILITY"]==1){
            if($GLOBALS["VERBOSE"]) {
                $options[] = "# $tline NTLM Compatibility";
                $options[] = "# $tline Active Directory / NTLM enabled";
            }

            $MainConfig["UseSMTPProto"]=0;

            $options[]="\toption prefer-last-server";
            $options[]="\toption redispatch";
            $options[]="\tno option httpclose";

            #$options[]="\toption http-server-close";
            if($MainConfig["contimeout"]==0){$MainConfig["clitimeout"]="30s";}
            if($MainConfig["srvtimeout"]==0){$MainConfig["srvtimeout"]="30s";}
            if($MainConfig["contimeout"]==0){$MainConfig["contimeout"]="4s";}
            #$tunnel_mode=1;
            $transparent=0;
            if ($MainConfig["forwardfor"] == 1) {
                $options[] = "\toption\tforwardfor";
                $options[] = "\thttp-request set-header X-Forwarded-Proto https if { ssl_fc }";
            }
        }

        if($tunnel_mode==1){
            if($GLOBALS["VERBOSE"]) {$options[]="# $tline TUNNEL MODE FOR BOTH HTTP/HTTPS";}
            $options[]="\toption http-tunnel";
        }

        if($MainConfig["UseSMTPProto"]==1){
            if($MainConfig["postfix-send-proxy"]==1){
                if($MainConfig["smtpchk_EHLO"]<>null){
                    $options[]="\toption\tsmtpchk\tHELO {$MainConfig["smtpchk_EHLO"]}";
                }else{
                    $options[]="\toption\tsmtpchk\tNOOP";
                }
            }
        }

        $Cookies_options="nocache";

        if($loadbalancetype==1 OR $loadbalancetype==0){
            $HaProxyMemoryCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaProxyMemoryCache"));
            if($HaProxyMemoryCache>0) {
                if (intval($MainConfig["cache-use"]) == 1) {
                    $options[] = "\thttp-request cache-use memorycache";
                    $options[] = "\thttp-response cache-store memorycache";
                }
            }
        }

        if($loadbalancetype==1){
            if($KERBEROS_COMPATIBILITY==0 && $MainConfig["NTLM_COMPATIBILITY"]==0) {
                if ($tunnel_mode == 0) {
                    $options[] = "\toption\thttp-server-close";
                }
                if ($MainConfig["http-use-proxy-header"] == 1) {
                    $options[] = "\toption\thttp-use-proxy-header";
                }
                if(intval($MainConfig["accept-invalid-http-request"])==1){
                    $options[]="\toption accept-invalid-http-request";

                }
                if ($MainConfig["forwardfor"] == 1) {
                    $options[] = "\toption\tforwardfor";
                    $options[] = "\thttp-request set-header X-Forwarded-Proto https if { ssl_fc }";
                }
                if ($MainConfig["originalto"] == 1) {
                    $options[] = "\toption\toriginalto";
                }

                //$options[] = "\ttimeout http-request {$MainConfig["HttpRequestTimeout"]}";
                if ($tunnel_mode == 1) {
                    $options[] = "\ttimeout tunnel {$MainConfig["TimeoutTunnel"]}";
                }
            }
        }

        if($loadbalancetype==2){
            if($MainConfig["forwardfor"]==1){
                $options[]="\toption\tforwardfor";
                $options[]="\thttp-request set-header X-Forwarded-Proto https if { ssl_fc }";
            }

        }

        if($transparent==1){
            $options[]="\toption\ttransparent";
            $options[]="\tsource\t0.0.0.0 usesrc clientip";
            $Cookies_options="insert nocache indirect";

        }
        if($BACKEND) {
            if ($MainConfig["contimeout"] > 0) {
                $options[] = "\ttimeout connect\t{$MainConfig["contimeout"]}";
            }
            if ($MainConfig["srvtimeout"] > 0) {
                $options[] = "\ttimeout server\t{$MainConfig["srvtimeout"]}";
            }
        }
        if(!$BACKEND) {
            if ($MainConfig["clitimeout"] > 0) {
                $options[] = "\ttimeout client\t{$MainConfig["clitimeout"]}";
            }
        }
        if($MainConfig["HttpRequestTimeout"]>0){$options[]="\ttimeout http-request\t{$MainConfig["HttpRequestTimeout"]}";}
        if($MainConfig["HttpQueueTimeout"]>0){$options[]="\ttimeout queue\t{$MainConfig["HttpQueueTimeout"]}";}
        if($BACKEND){
            if($MainConfig["maxretries"]>0){$options[]="\tretries\t{$MainConfig["maxretries"]}";}
        }
        if($MainConfig["UseCookies"]==1){$options[]="\tcookie SERVERID $Cookies_options";}

        return @implode("\n", $options)."\n";

    }


    private function build_groups_backends($gpid,$MainConfig){
        $t=array();
        $sql="SELECT haproxy_backends_link.ID as tid,
		haproxy_backends.* FROM haproxy_backends_link,haproxy_backends WHERE
		haproxy_backends.enabled=1 AND
		haproxy_backends.ID=haproxy_backends_link.backendid AND
		haproxy_backends_link.gpid='$gpid' ORDER BY bweight";

        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");

        if($MainConfig["UseCookies"]==1){$UseCookies=$MainConfig["UseCookies"];}





        if(!is_numeric($UseCookies)){$UseCookies=0;}
        $results=$q->QUERY_SQL($sql);
        if(!$q->ok){return "# $q->mysql_error";}
        $CountOfServers=mysqli_num_rows($results);
        if($CountOfServers==0){return "# Group $gpid 0 servers";}
        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            $t[]=$this->build_backend_unit($ID,$UseCookies,0);
        }

        return @implode("\n", $t);

    }

    private function build_backend_unit($ID,$UseCookies=0,$send_proxy=0){
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $ligne=$q->mysqli_fetch_array("SELECT * FROM haproxy_backends WHERE ID='$ID'");
        $MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
        $log=null;
        $xlogs=array();
        $servicename=$ligne["servicename"];
        $backendname=$ligne["backendname"];
        $ssl=null;
        $LINES=array();
        $HEADS=array();
        $check="check";

        if(!is_numeric($MainConfig["inter"])){$MainConfig["inter"]=60000;}
        if(!is_numeric($MainConfig["fall"])){$MainConfig["fall"]=3;}
        if(!is_numeric($MainConfig["rise"])){$MainConfig["rise"]=2;}
        if(!is_numeric($MainConfig["maxconn"])){$MainConfig["maxconn"]=10000;}
        if(!is_numeric($MainConfig["asSquidArtica"])){$MainConfig["asSquidArtica"]=0;}
        if(!is_numeric($MainConfig["FailOverOnly"])){$MainConfig["FailOverOnly"]=0;}
        if(!is_numeric($MainConfig["DrGroup"])){$MainConfig["DrGroup"]=0;}
        if(!is_numeric($MainConfig["postfix-send-proxy"])){$MainConfig["postfix-send-proxy"]=0;}
        if(!is_numeric($MainConfig["UseSSL"])){$MainConfig["UseSSL"]=0;}
        if(!is_numeric($MainConfig["proxy_protocol"])){$MainConfig["proxy_protocol"]=0;}

        if($MainConfig["proxy_protocol"]==1){$send_proxy=1;}

        $asSquidArtica=intval($MainConfig["asSquidArtica"]);
        $HEADS[]="#\tasSquidArtica = $asSquidArtica, Proxy Protocol=$send_proxy";

        if($ligne["listen_ip"]==null){
            $ligne["listen_ip"]=$ligne["backendname"];
        }

        if($asSquidArtica==1){
            $HEADS[]="#\tasSquidArtica Yes, Force to use Proxy Protocol";
            $send_proxy=1;
            if($MainConfig["FailOverOnly"]==1){
                $GLOBALS["OPTION_CHECK"]="\toption httpchk GET /squid-internal-static/icons/silk/folder.png";
            }

        }

        if($MainConfig["UseSSL"]==1){
            $HEADS[]="#\tUse SSL termination [YES]";
            $ssl=" ssl verify none";
            $check="check-ssl";
        }else{
            $HEADS[]="#\tUse SSL termination [NO]";
        }


        if( ($MainConfig["inter"]>0) && ($MainConfig["fall"]>0) && ($MainConfig["rise"]>0) ){
            $check_params=" inter {$MainConfig["inter"]} rise {$MainConfig["rise"]} fall {$MainConfig["fall"]}";
        }
        $servMD=md5("{$ligne["listen_ip"]}:{$ligne["listen_port"]}");
        if($UseCookies==1){$UseCookiesText=" cookie $servMD";}
        if(intval($MainConfig["maxconn"])>0){$maxconn=" maxconn {$MainConfig["maxconn"]}";}
        if($MainConfig["postfix-send-proxy"]==1){$smtp_proxy=" send-proxy";}

        if($send_proxy==1){
            $smtp_proxy=" send-proxy-v2";
            $check="$check check-send-proxy";
        }
        if(count($xlogs)>0){$log=@implode("\n", $xlogs)."\n";}
        $LINES[]="$log\tserver {$ligne["backendname"]} {$ligne["listen_ip"]}:{$ligne["listen_port"]}$smtp_proxy{$ssl}$UseCookiesText weight {$ligne["bweight"]}$maxconn $check$check_params".$this->outgoing_address($ligne["localInterface"]);
        if($GLOBALS["VERBOSE"]){
            return @implode("\n",$HEADS)."\n".@implode("\n",$LINES);

        }
        return @implode("\n",$LINES);
    }

    private function hasDRGroup($servicename,$MainConfig,$send_proxy=0,$servicetype=0):bool
    {
        $sql="SELECT * FROM haproxy_backends WHERE enabled=1 AND servicename='$servicename' ORDER BY bweight";
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $results=$q->QUERY_SQL($sql);
        if(count($results)==0){return false;}
        foreach ($results as $index=>$ligne){
            $data=unserialize(base64_decode($ligne["MainConfig"]));
            $drGroup=intval($data["DrGroup"]);
            if ($drGroup==1){return true;}
        }
        return false;
    }

    private function build_backends_dr($servicename,$MainConfig,$send_proxy=0,$servicetype=0){
        $tline="--------------------------";
        $UseCookies=$MainConfig["UseCookies"];

        $sql="SELECT * FROM haproxy_backends WHERE enabled=1 AND servicename='$servicename' ORDER BY bweight";
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        if(!is_numeric($UseCookies)){$UseCookies=0;}
        $results=$q->QUERY_SQL($sql);
        if(count($results)==0){return;}

        $t[]="backend dr_$servicename";
        if($GLOBALS["VERBOSE"]) {
            $t[] = "# $tline Use Proxy Protocol= $send_proxy";
            $t[] = "# $tline Service type      = $servicetype";
        }
        $t[]=$this->BuildOptionsFromMainConfig($MainConfig,$servicetype,true);
        $acls=new haproxy_acls($servicename);
        $buildacls=$acls->buildacls();
        $aclslogs=@implode("\n", $acls->acls_logs);
        if($buildacls<>null){
            if($GLOBALS["VERBOSE"]) {
                $t[] = "#";
                $t[] = "#";
                $t[] = "# $tline   A C L S  FOR $servicename";
            }
            $t[]=$aclslogs;
            $t[]=$buildacls;
            if($GLOBALS["VERBOSE"]) {$t[]="# $tline";}
        }
        $c=0;
        $GLOBALS["OPTION_CHECK"]=null;
        foreach ($results as $index=>$ligne){
            $data=unserialize(base64_decode($ligne["MainConfig"]));
            $drGroup=intval($data["DrGroup"]);
            if ($drGroup==0){continue;}
            $ID=$ligne["ID"];
            $sql="SELECT ID FROM haproxy_backends_link WHERE backendid='$ID'";
            $ligneCheck=$q->mysqli_fetch_array($sql);
            if(intval($ligneCheck["ID"])>0){continue;}
            $t[]=$this->build_backend_unit($ID,$UseCookies,$send_proxy);
            $c++;
        }
        if($GLOBALS["OPTION_CHECK"]<>null){$t[]=$GLOBALS["OPTION_CHECK"];}
        if($c==0){return null;}
        return @implode("\n",$t)."\n";

    }

    private function build_backends($servicename,$MainConfig,$send_proxy=0,$servicetype=0){
        $tline="--------------------------";
        $UseCookies=$MainConfig["UseCookies"];

        $sql="SELECT * FROM haproxy_backends WHERE enabled=1 AND servicename='$servicename' ORDER BY bweight";
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        if(!is_numeric($UseCookies)){$UseCookies=0;}
        $results=$q->QUERY_SQL($sql);
        if(count($results)==0){return;}

        $t[]="backend default_$servicename";
        if($GLOBALS["VERBOSE"]) {
            $t[] = "# $tline Use Proxy Protocol= $send_proxy";
            $t[] = "# $tline Service type      = $servicetype";
        }
        $t[]=$this->BuildOptionsFromMainConfig($MainConfig,$servicetype,true);
        $acls=new haproxy_acls($servicename);
        $buildacls=$acls->buildacls();
        $aclslogs=@implode("\n", $acls->acls_logs);
        if($buildacls<>null){
            if($GLOBALS["VERBOSE"]) {
                $t[] = "#";
                $t[] = "#";
                $t[] = "# $tline   A C L S  FOR $servicename";
            }
            $t[]=$aclslogs;
            $t[]=$buildacls;
            if($GLOBALS["VERBOSE"]) {$t[]="# $tline";}
        }
        $c=0;
        $GLOBALS["OPTION_CHECK"]=null;
        foreach ($results as $index=>$ligne){
            $data=unserialize(base64_decode($ligne["MainConfig"]));
            $drGroup=intval($data["DrGroup"]);
            if ($drGroup==1){continue;}
            $ID=$ligne["ID"];
            $sql="SELECT ID FROM haproxy_backends_link WHERE backendid='$ID'";
            $ligneCheck=$q->mysqli_fetch_array($sql);
            if(intval($ligneCheck["ID"])>0){continue;}
            $t[]=$this->build_backend_unit($ID,$UseCookies,$send_proxy);
            $c++;
        }
        if($GLOBALS["OPTION_CHECK"]<>null){$t[]=$GLOBALS["OPTION_CHECK"];}
        if($c==0){return null;}
        return @implode("\n",$t)."\n";

    }

    private function outgoing_address($localInterface=null){
        if($localInterface==null){return;}

        if(!class_exists("unix")){
            return " source [of $localInterface] usesrc clientip";
        }

        if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"])){
            $unix=new unix();
            $GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES();
        }

        $IPADDR=$GLOBALS["NETWORK_ALL_INTERFACES"][$localInterface]["IPADDR"];
        if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $localInterface = $IPADDR\n";}
        if($IPADDR==null){return;}
        if($IPADDR=="0.0.0.0"){return;}
        if($IPADDR=="127.0.0.1"){return;}
        return " source $IPADDR";
    }


    public function FlexArray($gpid,$enable=1){
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");


        $items=0;
        $t=$_GET["t"];
        $color="black";
        if($enable==0){$color="#8a8a8a";}
        $tpl=new templates();
        $rules=$tpl->_ENGINE_parse_body("{rules}");


        $ligne=$q->mysqli_fetch_array("SELECT groupname,grouptype FROM haproxy_acls_groups WHERE ID='$gpid'");;
        $GroupName=null;
        $GroupType=$ligne["grouptype"];
        if($GLOBALS["VERBOSE"]){echo "$gpid = $GroupType<br>\n";}
        $GroupTypeName=$tpl->_ENGINE_parse_body($this->acl_GroupType[$GroupType]);
        if($GroupName==null){$GroupName=utf8_encode($ligne["groupname"]);}

        if($items==0){
            $ligne2=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM haproxy_acls_items WHERE groupid='$gpid'");
            $items=$ligne2["tcount"];
        }

        $href[]="<a href=\"javascript:blur();\" ";
        $href[]="OnClick=\"Loadjs('fw.haproxy.acls.php?groupid-js=$gpid',true);\" ";
        $href[]="style=\"font-weight:bold;text-decoration:underline;text-transform:capitalize\">";
        $hrefT=@implode("", $href);

        $hrefAdd[]="<a href=\"javascript:blur();\" ";
        $hrefAdd[]="OnClick=\"Loadjs('haproxy.acls.items.php?AddItem-js=yes&item-id=-1&gpid=$gpid',true);\" ";
        $hrefAdd[]="style=\"text-decoration:underline\">";

        if($GroupType=="all"){$items="*";}

        $hrefAdd[]="($GroupTypeName)";
        $hrefAddT=@implode("", $hrefAdd);

        return array("ROW"=>"$hrefT($gpid) $GroupName</a>&nbsp;&nbsp;$hrefAddT</a>",
            "ITEMS"=>$items

        );


    }




}


class haproxy_multi{
    public $algo=array();
    public $servicename;
    public $listen_ip=null;
    public $listen_port=null;
    public $loadbalancetype=0;
    public $dispatch_mode=null;
    public $http_reuse_array=array();
    public $http_reuse=null;
    public $MainConfig=array();
    public $enabled=0;
    public $transparent=0;
    public $transparentsrcport=0;
    public $servicetype=0;
    public $checkup_interval=0;
    public $client_timout=0;
    public $crtype=0;
    public $wakeup_interval=0;
    public $ssl;
    public $certificates=array();
    public $servicetype_array=array(
        0=>"HTTP/HTTPs Proxy Load-balancer",
        1=>"HTTP Load-balancer",
        2=>"Reverse Proxy",
        3=>"TCP redirect",
        4=>"SMTP redirect"
    );


    var $tunnel_mode=0;
    function __construct($servicename=null){
        $this->algo["source"]="{strict-hashed-ip}";
        $this->algo["roundrobin"]="{round-robin}";
        $this->algo["leastconn"]="{leastconn}";
        $this->algo["uri"]="HTTP URL";
        $this->http_reuse_array["Disable"]="Disable";
        $this->http_reuse_array["never"]="never";
        $this->http_reuse_array["safe"]="safe";
        $this->http_reuse_array["aggressive"]="aggressive";
        $this->http_reuse_array["always"]="always";
        if($servicename<>null){
            $this->servicename=$servicename;
            $this->load();
        }
    }


    private function load(){

        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        if(!$q->FIELD_EXISTS("haproxy","certificates")){
            $q->QUERY_SQL("ALTER TABLE haproxy ADD certificates TEXT NULL");
        }
        if(!$q->FIELD_EXISTS("haproxy","ssl")){
            $q->QUERY_SQL("ALTER TABLE haproxy ADD ssl INTEGER NOT NULL DEFAULT 1");
        }
        $sql="SELECT * from haproxy WHERE servicename='$this->servicename'";
        $ligne=$q->mysqli_fetch_array($sql);

        $this->listen_ip=$ligne["listen_ip"];
        $this->listen_port=$ligne["listen_port"];
        $this->enabled=$ligne['enabled'];
        $this->loadbalancetype=$ligne["loadbalancetype"];
        $this->dispatch_mode=$ligne["dispatch_mode"];
        $this->transparent=$ligne["transparent"];
        $this->transparentsrcport=$ligne["transparentsrcport"];
        $this->MainConfig=unserialize(base64_decode($ligne["MainConfig"]));
        $this->tunnel_mode=$ligne["tunnel_mode"];
        $this->servicetype=intval($ligne["servicetype"]);
        $this->crtype=intval($ligne["crtype"]);
        $this->client_timout=intval($ligne["client_timout"]);
        $this->checkup_interval=intval($ligne["checkup_interval"]);
        $this->wakeup_interval =intval($ligne["wakeup_interval"]);

        if(!is_numeric($this->tunnel_mode)){$this->tunnel_mode=0;}
        if($this->client_timout==0){$this->client_timout=60;}
        if($this->checkup_interval==0){$this->checkup_interval=10;}
        if($this->checkup_interval==0){$this->wakeup_interval=5;}
        $this->ssl=intval($ligne["ssl"]);
        $datas=$ligne["certificates"];
        if(trim($datas)<>null) {
            $this->certificates = unserialize(base64_decode($datas));
        }

    }


    public function save(){
        $this->servicename=trim(strtolower($this->servicename));


        $listenaddrport="$this->listen_ip:$this->listen_port";
        $this->servicename=replace_accents($this->servicename);
        $this->servicename=str_replace(" ", "_", $this->servicename);
        $this->servicename=str_replace("-", "_", $this->servicename);
        $sql="SELECT servicename from haproxy WHERE servicename='$this->servicename'";
        $MainConfig=base64_encode(serialize($this->MainConfig));
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");


        $certificate=base64_encode(serialize($this->certificates));




        $ligne=$q->mysqli_fetch_array($sql);
        if(trim($ligne["servicename"])==null){
            $sql="INSERT INTO haproxy (servicename,servicetype,MainConfig,listen_ip,listen_port,ipaddrport,loadbalancetype,dispatch_mode,transparent,transparentsrcport,tunnel_mode,crtype,client_timout,checkup_interval,wakeup_interval) 
			VALUES ('$this->servicename','$this->servicetype','$MainConfig','$this->listen_ip','$this->listen_port','$listenaddrport','$this->loadbalancetype','$this->dispatch_mode','$this->transparent','$this->transparentsrcport','$this->tunnel_mode','$this->crtype','$this->client_timout','$this->checkup_interval','$this->wakeup_interval')";

        }else{
            $sql="UPDATE haproxy SET 
				listen_ip='$this->listen_ip',
				listen_port='$this->listen_port',
				ipaddrport='$listenaddrport',
				enabled='$this->enabled',
				loadbalancetype='$this->loadbalancetype',
				dispatch_mode='$this->dispatch_mode',
				transparent='$this->transparent',
				tunnel_mode=$this->tunnel_mode,
				transparentsrcport='$this->transparentsrcport',
                certificates = '$certificate',
                ssl          = '$this->ssl',
				MainConfig='$MainConfig'
				WHERE servicename='$this->servicename'
				";
        }

        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo "FATAL ERROR....$q->mysql_error\n";
            writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
            return;
        }
        writelogs("OK $sql",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);

        $this->CheckService();
        return true;
    }

    public function DeleteService(){
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $sql="DELETE FROM haproxy_backends WHERE servicename='$this->servicename'";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $q->mysql_error\n";return;}
        $sql="DELETE FROM haproxy WHERE servicename='$this->servicename'";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return;}

        $results=$q->QUERY_SQL("SELECT ID FROM haproxy_acls_rules WHERE servicename='$this->servicename'");
        foreach ($results as $index=>$ligne){
            $ID=$ligne["ID"];
            $q->QUERY_SQL("DELETE FROM haproxy_acls_link WHERE ruleid='$ID'");
            $q->QUERY_SQL("DELETE FROM haproxy_acls_rules WHERE ID='$ID'");


        }







        $this->CheckService();
    }

    private function CheckService(){
        $sock=new sockets();
        $sock->getFrameWork("services.php?reload-haproxy=yes");
    }

}

class haproxy_backends{
    var $backendname=null;
    var $servicename;
    var $listen_ip=null;
    var $listen_port=null;
    var $bweight=1;
    var $dispatch_mode=null;
    var $enabled=0;
    var $MainConfig=array();
    var $localInterface=null;
    public $sitename=null;
    function __construct($servicename,$backendname){

        if($servicename<>null){
            $this->servicename=$servicename;
            if($backendname<>null){
                $this->backendname=$backendname;
                $this->load();
            }
        }
    }


    private function load(){
        $sql="SELECT * from haproxy_backends WHERE servicename='$this->servicename' AND backendname='$this->backendname'";
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $ligne=$q->mysqli_fetch_array($sql);
        $this->listen_ip=$ligne["listen_ip"];
        $this->listen_port=$ligne["listen_port"];
        $this->enabled=$ligne['enabled'];
        $this->bweight=$ligne["bweight"];
        $this->localInterface=$ligne["localInterface"];
        $this->sitename=$ligne["sitename"];
        $this->MainConfig=unserialize(base64_decode($ligne["MainConfig"]));

    }


    public function save(){
        $this->backendname=trim(strtolower($this->backendname));
        $this->backendname=replace_accents($this->backendname);
        $this->backendname=str_replace(" ", "_", $this->backendname);
        $this->backendname=str_replace("-", "_", $this->backendname);
        $sql="SELECT backendname from haproxy_backends WHERE servicename='$this->servicename' AND backendname='$this->backendname'";
        $MainConfig=base64_encode(serialize($this->MainConfig));
        if(strlen($MainConfig)<20){
            $MainConfig="";
        }
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $ligne=$q->mysqli_fetch_array($sql);
        if(trim($ligne["backendname"])==null){
            $sql="INSERT INTO haproxy_backends (servicename,sitename,backendname,listen_ip,listen_port,bweight,localInterface,MainConfig) 
			VALUES ('$this->servicename','$this->sitename','$this->backendname','$this->listen_ip','$this->listen_port','$this->bweight','$this->localInterface','$MainConfig')";

        }else{
            $sql="UPDATE haproxy_backends SET 
				listen_ip='$this->listen_ip',
				listen_port='$this->listen_port',
				bweight='$this->bweight',
				enabled='$this->enabled',
				localInterface='$this->localInterface',
				sitename='$this->sitename',
				MainConfig='$MainConfig'
				WHERE servicename='$this->servicename' AND  backendname='$this->backendname'
				";
        }

        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        if(!$q->TABLE_EXISTS("haproxy", "artica_backup")){$q->BuildTables();}
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error."<br>".__CLASS__."<br>$sql<br>";return;}
        $this->CheckService();
        return true;
    }

    public function DeleteBackend(){
        $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
        $sql="DELETE FROM haproxy_backends WHERE servicename='$this->servicename' AND  backendname='$this->backendname'";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error;return;}
        $this->CheckService();
    }

    private function CheckService(){
        if(isset($GLOBALS["AS_ROOT"])){if($GLOBALS["AS_ROOT"]){return;}}
        $sock=new sockets();
        $sock->getFrameWork("services.php?reload-haproxy=yes");
    }

}