<?php
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');

if($argv[1]=="--update"){ update_sites_list();exit;}
if($argv[1]=="--start"){ start();exit;}
if($argv[1]=="--restart"){ restart();exit;}
if($argv[1]=="--stop"){ stop();exit;}
if($argv[1]=="--install"){ install();exit;}
if($argv[1]=="--uninstall"){ uninstall();exit;}
if($argv[1]=="--build"){build_configuration();exit;}
if($argv[1]=="--dump"){dump_dns_list();exit;}
if($argv[1]=="--install-srv"){install_service();}


function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/DNSCryptProxy.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_restart($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/DNSCryptProxy.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_update($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile=PROGRESS_DIR."/DNSCryptProxy.update.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function restart(){
	$unix=new unix();
	build_progress_restart(10,"{stopping_service}");
	stop();
	build_progress_restart(50,"{stopping_service}");
	sleep(1);
    build_progress_restart(60,"{reconfiguring}");
    build_configuration();
    build_progress_restart(80,"{starting_service}");
	if(!start()){
		build_progress_restart(110,"{starting_service} {failed}");
		return;
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress(50,"{restarting_service} {APP_UNBOUND}");
	system("$php /usr/share/artica-postfix/exec.unbound.php --reload");
	build_progress_restart(100,"{starting_service} {success}");
}

function uninstall(){
	$unix=new unix();
	$sock=new sockets();
	$php=$unix->LOCATE_PHP5_BIN();
	$sock->SET_INFO("EnableDNSCryptProxy", 0);
	build_progress(10,"{uninstalling}");
	remove_service("/etc/init.d/dnscrypt-proxy");
	build_progress(50,"{uninstalling}");
	system("$php /usr/share/artica-postfix/exec.unbound.php --restart");
	build_progress(80,"{uninstalling}");
	@unlink("/etc/monit/conf.d/APP_DNSCRYPT_PROXY.monitrc");
	@unlink("/var/log/dnscrypt-proxy/query.log");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	build_progress(100,"{uninstalling} {done}");
	
}

function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}


function install(){
	
	$unix=new unix();
	$sock=new sockets();
	$sock->SET_INFO("EnableDNSCryptProxy", 1);
	build_progress(10,"{installing}");
	
	update_sites_list();
	build_progress(15,"{installing}");
    build_configuration();
	build_progress(20,"{installing}");
	install_service();
	build_progress(25,"{installing}");
	build_monit();
	build_progress(30,"{starting_service}");
	if(!start()){
		$sock->SET_INFO("EnableDNSCryptProxy", 0);
		build_progress(110,"{starting_service} {failed}");
		return;
	}
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress(50,"{restarting_service} {APP_UNBOUND}");
	system("$php /usr/share/artica-postfix/exec.unbound.php --reload");
	build_progress(100,"{installing} {done}");
	
}

function build_monit(){
	

	$f[]="check process APP_DNSCRYPT_PROXY with pidfile /var/run/dnscrypt-proxy.pid";
	$f[]="\tstart program = \"/etc/init.d/dnscrypt-proxy start\"";
	$f[]="\tstop program = \"/etc/init.d/dnscrypt-proxy stop\"";

	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_DNSCRYPT_PROXY.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_DNSCRYPT_PROXY.monitrc")){
		echo "/etc/monit/conf.d/APP_DNSCRYPT_PROXY.monitrc failed !!!\n";
	}
	echo "ElasticSearch: [INFO] Writing /etc/monit/conf.d/APP_DNSCRYPT_PROXY.monitrc\n";
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");

}

function build_configuration(){
    $conf=array();
    $conf[]="##############################################";
    $conf[]="#                                            #";
    $conf[]="#        dnscrypt-proxy configuration        #";
    $conf[]="#                                            #";
    $conf[]="##############################################";
    $conf[]="";
    $conf[]="## This is an example configuration file.";
    $conf[]="## You should adjust it to your needs, and save it as \"dnscrypt-proxy.toml\"";

    $sock=new sockets();
    $unix=new unix();

    $DnsCryptLocalInterface=$sock->GET_INFO("DnsCryptLocalInterface");
    $DnsCryptLogLevel=intval($sock->GET_INFO("DnsCryptLogLevel"));
    if($DnsCryptLocalInterface==null){$DnsCryptLocalInterface="lo";}
    $DnsCryptLocalPort=intval($sock->GET_INFO("DnsCryptLocalPort"));
    if($DnsCryptLocalPort==0){$DnsCryptLocalPort=5353;}
    $DnsCryptLocalIP=$unix->InterfaceToIPv4($DnsCryptLocalInterface);
    if($DnsCryptLocalIP==null){$DnsCryptLocalIP="127.0.0.1";}
    if($DnsCryptLogLevel==0){$DnsCryptLogLevel=6;}
    $DnsCryptEnableUniqueProvider=intval($sock->GET_INFO("DnsCryptEnableUniqueProvider"));
    $DnsCryptProvider=trim($sock->GET_INFO("DnsCryptProvider"));



$conf[]="##";
$conf[]="## Online documentation is available here: https://dnscrypt.info/doc";
$conf[]="";
$conf[]="";
$conf[]="";
$conf[]="##################################";
$conf[]="#         Global settings        #";
$conf[]="##################################";
$conf[]="";
$conf[]="## List of servers to use";
$conf[]="##";
$conf[]="## Servers from the \"public-resolvers\" source (see down below) can";
$conf[]="## be viewed here: https://dnscrypt.info/public-servers";
$conf[]="##";
$conf[]="## If this line is commented, all registered servers matching the require_* filters";
$conf[]="## will be used.";
$conf[]="##";
$conf[]="## The proxy will automatically pick the fastest, working servers from the list.";
$conf[]="## Remove the leading # first to enable this; lines starting with # are ignored.";
$conf[]="";

if($DnsCryptEnableUniqueProvider==1){
    $conf[]="server_names = ['$DnsCryptProvider']";
    echo "Process: dnscrypt-proxy Listens Using only one server name $DnsCryptProvider\n";
}else{
    $server_names=array();
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $results=$q->QUERY_SQL("SELECT Name FROM DnsCryptResolvers WHERE Enabled=1 ORDER BY zOrder");
    foreach ($results as $index=>$ligne){
        $server_names[]="'{$ligne["Name"]}'";
    }
    echo "Process: dnscrypt-proxy Listens Using ".count($server_names)." Public DNS servers\n";
    $conf[]="server_names = [".@implode(",",$server_names)."]";
}
$conf[]="## List of local addresses and ports to listen to. Can be IPv4 and/or IPv6.";
$conf[]="## Note: When using systemd socket activation, choose an empty set (i.e. [] ).";
    echo "Process: dnscrypt-proxy Listens {$DnsCryptLocalIP}:{$DnsCryptLocalPort}\n";
$conf[]="listen_addresses = ['{$DnsCryptLocalIP}:{$DnsCryptLocalPort}']";
$conf[]="max_clients = 250";
$conf[]="user_name = 'root'";
$conf[]="ipv4_servers = true";
$conf[]="ipv6_servers = false";
$conf[]="dnscrypt_servers = true";
$conf[]="doh_servers = true";
$conf[]="## Require servers defined by remote sources to satisfy specific properties";
$conf[]="# Server must support DNS security extensions (DNSSEC)";
$conf[]="require_dnssec = false";
$conf[]="";
$conf[]="# Server must not log user queries (declarative)";
$conf[]="require_nolog = true";
$conf[]="";
$conf[]="# Server must not enforce its own blacklist (for parental control, ads blocking...)";
$conf[]="require_nofilter = true";
$conf[]="";
$conf[]="";
$conf[]="## Always use TCP to connect to upstream servers.";
$conf[]="## This can be useful if you need to route everything through Tor.";
$conf[]="## Otherwise, leave this to `false`, as it doesn't improve security";
$conf[]="## (dnscrypt-proxy will always encrypt everything even using UDP), and can";
$conf[]="## only increase latency.";
$conf[]="";
$conf[]="force_tcp = false";
$conf[]="";
$conf[]="";
$conf[]="## SOCKS proxy";
$conf[]="## Uncomment the following line to route all TCP connections to a local Tor node";
$conf[]="## Tor doesn't support UDP, so set `force_tcp` to `true` as well.";
$conf[]="";
$conf[]="# proxy = \"socks5://127.0.0.1:9050\"";
$conf[]="";
$conf[]="";
$conf[]="## HTTP/HTTPS proxy";
$conf[]="## Only for DoH servers";
$conf[]="";
$conf[]="# http_proxy = \"http://127.0.0.1:8888\"";
$conf[]="";
$conf[]="";
$conf[]="## How long a DNS query will wait for a response, in milliseconds";
$conf[]="";
$conf[]="timeout = 2500";
$conf[]="";
$conf[]="";
$conf[]="## Keepalive for HTTP (HTTPS, HTTP/2) queries, in seconds";
$conf[]="";
$conf[]="keepalive = 30";
$conf[]="";
$conf[]="";
$conf[]="## Load-balancing strategy: 'p2' (default), 'ph', 'fastest' or 'random'";
$conf[]="";
$conf[]="# lb_strategy = 'p2'";
$conf[]="";
$conf[]="";
$conf[]="## Log level (0-6, default: 2 - 0 is very verbose, 6 only contains fatal errors)";
$conf[]="log_level = $DnsCryptLogLevel";
$conf[]="use_syslog = true";
$conf[]="";
$conf[]="## Delay, in minutes, after which certificates are reloaded";
$conf[]="cert_refresh_delay = 240";
$conf[]="";
$conf[]="";
$conf[]="## DNSCrypt: Create a new, unique key for every single DNS query";
$conf[]="## This may improve privacy but can also have a significant impact on CPU usage";
$conf[]="## Only enable if you don't have a lot of network load";
$conf[]="";
$conf[]="# dnscrypt_ephemeral_keys = false";
$conf[]="";
$conf[]="";
$conf[]="## DoH: Disable TLS session tickets - increases privacy but also latency";
$conf[]="";
$conf[]="# tls_disable_session_tickets = false";
$conf[]="";
$conf[]="";
$conf[]="## DoH: Use a specific cipher suite instead of the server preference";
$conf[]="## 49199 = TLS_ECDHE_RSA_WITH_AES_128_GCM_SHA256";
$conf[]="## 49195 = TLS_ECDHE_ECDSA_WITH_AES_128_GCM_SHA256";
$conf[]="## 52392 = TLS_ECDHE_RSA_WITH_CHACHA20_POLY1305";
$conf[]="## 52393 = TLS_ECDHE_ECDSA_WITH_CHACHA20_POLY1305";
$conf[]="##";
$conf[]="## On non-Intel CPUs such as MIPS routers and ARM systems (Android, Raspberry Pi...),";
$conf[]="## the following suite improves performance.";
$conf[]="## This may also help on Intel CPUs running 32-bit operating systems.";
$conf[]="##";
$conf[]="## Keep tls_cipher_suite empty if you have issues fetching sources or";
$conf[]="## connecting to some DoH servers. Google and Cloudflare are fine with it.";
$conf[]="";
$conf[]="# tls_cipher_suite = [52392, 49199]";
$conf[]="";
$conf[]="";
$conf[]="## Fallback resolver";
$conf[]="## This is a normal, non-encrypted DNS resolver, that will be only used";
$conf[]="## for one-shot queries when retrieving the initial resolvers list, and";
$conf[]="## only if the system DNS configuration doesn't work.";
$conf[]="## No user application queries will ever be leaked through this resolver,";
$conf[]="## and it will not be used after IP addresses of resolvers URLs have been found.";
$conf[]="## It will never be used if lists have already been cached, and if stamps";
$conf[]="## don't include host names without IP addresses.";
$conf[]="## It will not be used if the configured system DNS works.";
$conf[]="## A resolver supporting DNSSEC is recommended. This may become mandatory.";
$conf[]="##";
$conf[]="## People in China may need to use 114.114.114.114:53 here.";
$conf[]="## Other popular options include 8.8.8.8 and 1.1.1.1.";
$conf[]="";
$resolv=new resolv_conf();
$fallback_resolver=null;

if($resolv->MainArray["DNS1"]<>null){if($resolv->MainArray["DNS1"]<>"127.0.0.1"){$fallback_resolver=$resolv->MainArray["DNS1"];}}
if($fallback_resolver==null){if($resolv->MainArray["DNS2"]<>null){if($resolv->MainArray["DNS2"]<>"127.0.0.1"){$fallback_resolver=$resolv->MainArray["DNS2"];}}}
if($fallback_resolver==null){if($resolv->MainArray["DNS3"]<>null){if($resolv->MainArray["DNS3"]<>"127.0.0.1"){$fallback_resolver=$resolv->MainArray["DNS3"]; } }}
echo "Process: dnscrypt-proxy Listens Using fallback resolver '$fallback_resolver'\n";
$conf[]="fallback_resolver = '$fallback_resolver:53'";
$conf[]="";
$conf[]="";
$conf[]="## Never let dnscrypt-proxy try to use the system DNS settings;";
$conf[]="## unconditionally use the fallback resolver.";
$conf[]="";
$conf[]="ignore_system_dns = false";
$conf[]="";
$conf[]="";
$conf[]="## Maximum time (in seconds) to wait for network connectivity before";
$conf[]="## initializing the proxy.";
$conf[]="## Useful if the proxy is automatically started at boot, and network";
$conf[]="## connectivity is not guaranteed to be immediately available.";
$conf[]="## Use 0 to disable.";
$conf[]="";
$conf[]="netprobe_timeout = 30";
$conf[]="";
$conf[]="";
$conf[]="## Offline mode - Do not use any remote encrypted servers.";
$conf[]="## The proxy will remain fully functional to respond to queries that";
$conf[]="## plugins can handle directly (forwarding, cloaking, ...)";
$conf[]="";
$conf[]="# offline_mode = false";
$conf[]="";
$conf[]="";
$conf[]="## Automatic log files rotation";
$conf[]="# Maximum log files size in MB";
$conf[]="log_files_max_size = 10";
$conf[]="";
$conf[]="# How long to keep backup files, in days";
$conf[]="log_files_max_age = 7";
$conf[]="";
$conf[]="# Maximum log files backups to keep (or 0 to keep all backups)";
$conf[]="log_files_max_backups = 1";
$conf[]="";
$conf[]="";
$conf[]="";
$conf[]="#########################";
$conf[]="#        Filters        #";
$conf[]="#########################";
$conf[]="";
$conf[]="## Immediately respond to IPv6-related queries with an empty response";
$conf[]="## This makes things faster when there is no IPv6 connectivity, but can";
$conf[]="## also cause reliability issues with some stub resolvers.";
$conf[]="## Do not enable if you added a validating resolver such as dnsmasq in front";
$conf[]="## of the proxy.";
$conf[]="";
$conf[]="block_ipv6 = true";
$conf[]="";
$conf[]="";
$conf[]="";
$conf[]="##################################################################################";
$conf[]="#        Route queries for specific domains to a dedicated set of servers        #";
$conf[]="##################################################################################";
$conf[]="";
$conf[]="## Example map entries (one entry per line):";
$conf[]="## example.com 9.9.9.9";
$conf[]="## example.net 9.9.9.9,8.8.8.8,1.1.1.1";
$conf[]="";
$conf[]="# forwarding_rules = 'forwarding-rules.txt'";
$conf[]="";
$conf[]="";
$conf[]="";
$conf[]="###############################";
$conf[]="#        Cloaking rules       #";
$conf[]="###############################";
$conf[]="";
$conf[]="## Cloaking returns a predefined address for a specific name.";
$conf[]="## In addition to acting as a HOSTS file, it can also return the IP address";
$conf[]="## of a different name. It will also do CNAME flattening.";
$conf[]="##";
$conf[]="## Example map entries (one entry per line)";
$conf[]="## example.com     10.1.1.1";
$conf[]="## www.google.com  forcesafesearch.google.com";
$conf[]="";
$conf[]="# cloaking_rules = 'cloaking-rules.txt'";
$conf[]="";
$conf[]="";
$conf[]="";
$conf[]="###########################";
$conf[]="#        DNS cache        #";
$conf[]="###########################";
$conf[]="";
$conf[]="## Enable a DNS cache to reduce latency and outgoing traffic";
$conf[]="";
$conf[]="cache = true";
$conf[]="";
$conf[]="";
$conf[]="## Cache size";
$conf[]="";
$conf[]="cache_size = 512";
$conf[]="";
$conf[]="";
$conf[]="## Minimum TTL for cached entries";
$conf[]="";
$conf[]="cache_min_ttl = 600";
$conf[]="";
$conf[]="";
$conf[]="## Maximum TTL for cached entries";
$conf[]="";
$conf[]="cache_max_ttl = 86400";
$conf[]="";
$conf[]="";
$conf[]="## Minimum TTL for negatively cached entries";
$conf[]="";
$conf[]="cache_neg_min_ttl = 60";
$conf[]="";
$conf[]="";
$conf[]="## Maximum TTL for negatively cached entries";
$conf[]="";
$conf[]="cache_neg_max_ttl = 600";
$conf[]="";
$conf[]="";
$conf[]="";
$conf[]="###############################";
$conf[]="#        Query logging        #";
$conf[]="###############################";
$conf[]="";
$conf[]="## Log client queries to a file";
$conf[]="";
$conf[]="[query_log]";
    $DnsCryptQueryLogging=intval($sock->GET_INFO("DnsCryptQueryLogging"));
$conf[]="";
if($DnsCryptQueryLogging==1) {
    @mkdir("/var/log/dnscrypt-proxy", 0755, true);
    $conf[] = "\tfile = '/var/log/dnscrypt-proxy/query.log'";
}
$conf[]="  ## Query log format (currently supported: tsv and ltsv)";
$conf[]="\tformat = 'tsv'";
$conf[]="\tignored_qtypes = ['DNSKEY', 'NS']";
$conf[]="";
$conf[]="";
$conf[]="";
$conf[]="############################################";
$conf[]="#        Suspicious queries logging        #";
$conf[]="############################################";
$conf[]="";
$conf[]="## Log queries for nonexistent zones";
$conf[]="## These queries can reveal the presence of malware, broken/obsolete applications,";
$conf[]="## and devices signaling their presence to 3rd parties.";
$conf[]="";
$conf[]="[nx_log]";
$conf[]="";
    if($DnsCryptQueryLogging==1) {
        $conf[] = "\tfile = '/var/log/dnscrypt-proxy/nx.log'";
        $conf[] = "\tformat = 'tsv'";
    }
$conf[]="";
$conf[]="";
$conf[]="";
$conf[]="######################################################";
$conf[]="#        Pattern-based blocking (blacklists)        #";
$conf[]="######################################################";
$conf[]="";
$conf[]="## Blacklists are made of one pattern per line. Example of valid patterns:";
$conf[]="##";
$conf[]="##   example.com";
$conf[]="##   =example.com";
$conf[]="##   *sex*";
$conf[]="##   ads.*";
$conf[]="##   ads*.example.*";
$conf[]="##   ads*.example[0-9]*.com";
$conf[]="##";
$conf[]="## Example blacklist files can be found at https://download.dnscrypt.info/blacklists/";
$conf[]="## A script to build blacklists from public feeds can be found in the";
$conf[]="## `utils/generate-domains-blacklists` directory of the dnscrypt-proxy source code.";
$conf[]="";
$conf[]="[blacklist]";
$conf[]="";
$conf[]="  ## Path to the file of blocking rules (absolute, or relative to the same directory as the executable file)";
$conf[]="";
$conf[]="  # blacklist_file = 'blacklist.txt'";
$conf[]="";
$conf[]="";
$conf[]="  ## Optional path to a file logging blocked queries";
$conf[]="";
$conf[]="  # log_file = 'blocked.log'";
$conf[]="";
$conf[]="";
$conf[]="  ## Optional log format: tsv or ltsv (default: tsv)";
$conf[]="";
$conf[]="  # log_format = 'tsv'";
$conf[]="";
$conf[]="";
$conf[]="";
$conf[]="###########################################################";
$conf[]="#        Pattern-based IP blocking (IP blacklists)        #";
$conf[]="###########################################################";
$conf[]="";
$conf[]="## IP blacklists are made of one pattern per line. Example of valid patterns:";
$conf[]="##";
$conf[]="##   127.*";
$conf[]="##   fe80:abcd:*";
$conf[]="##   192.168.1.4";
$conf[]="";
$conf[]="[ip_blacklist]";
$conf[]="";
$conf[]="  ## Path to the file of blocking rules (absolute, or relative to the same directory as the executable file)";
$conf[]="";
$conf[]="  # blacklist_file = 'ip-blacklist.txt'";
$conf[]="";
$conf[]="";
$conf[]="  ## Optional path to a file logging blocked queries";
$conf[]="";
$conf[]="  # log_file = 'ip-blocked.log'";
$conf[]="";
$conf[]="";
$conf[]="  ## Optional log format: tsv or ltsv (default: tsv)";
$conf[]="";
$conf[]="  # log_format = 'tsv'";
$conf[]="";
$conf[]="";
$conf[]="";
$conf[]="######################################################";
$conf[]="#   Pattern-based whitelisting (blacklists bypass)   #";
$conf[]="######################################################";
$conf[]="";
$conf[]="## Whitelists support the same patterns as blacklists";
$conf[]="## If a name matches a whitelist entry, the corresponding session";
$conf[]="## will bypass names and IP filters.";
$conf[]="##";
$conf[]="## Time-based rules are also supported to make some websites only accessible at specific times of the day.";
$conf[]="";
$conf[]="[whitelist]";
$conf[]="";
$conf[]="  ## Path to the file of whitelisting rules (absolute, or relative to the same directory as the executable file)";
$conf[]="";
$conf[]="  # whitelist_file = 'whitelist.txt'";
$conf[]="";
$conf[]="";
$conf[]="  ## Optional path to a file logging whitelisted queries";
$conf[]="";
$conf[]="  # log_file = 'whitelisted.log'";
$conf[]="";
$conf[]="";
$conf[]="  ## Optional log format: tsv or ltsv (default: tsv)";
$conf[]="";
$conf[]="  # log_format = 'tsv'";
$conf[]="";
$conf[]="";
$conf[]="";
$conf[]="##########################################";
$conf[]="#        Time access restrictions        #";
$conf[]="##########################################";
$conf[]="";
$conf[]="## One or more weekly schedules can be defined here.";
$conf[]="## Patterns in the name-based blocklist can optionally be followed with @schedule_name";
$conf[]="## to apply the pattern 'schedule_name' only when it matches a time range of that schedule.";
$conf[]="##";
$conf[]="## For example, the following rule in a blacklist file:";
$conf[]="## *.youtube.* @time-to-sleep";
$conf[]="## would block access to YouTube only during the days, and period of the days";
$conf[]="## define by the 'time-to-sleep' schedule.";
$conf[]="##";
$conf[]="## {after='21:00', before= '7:00'} matches 0:00-7:00 and 21:00-0:00";
$conf[]="## {after= '9:00', before='18:00'} matches 9:00-18:00";
$conf[]="";
$conf[]="[schedules]";
$conf[]="";
$conf[]="  # [schedules.'time-to-sleep']";
$conf[]="  # mon = [{after='21:00', before='7:00'}]";
$conf[]="  # tue = [{after='21:00', before='7:00'}]";
$conf[]="  # wed = [{after='21:00', before='7:00'}]";
$conf[]="  # thu = [{after='21:00', before='7:00'}]";
$conf[]="  # fri = [{after='23:00', before='7:00'}]";
$conf[]="  # sat = [{after='23:00', before='7:00'}]";
$conf[]="  # sun = [{after='21:00', before='7:00'}]";
$conf[]="";
$conf[]="  # [schedules.'work']";
$conf[]="  # mon = [{after='9:00', before='18:00'}]";
$conf[]="  # tue = [{after='9:00', before='18:00'}]";
$conf[]="  # wed = [{after='9:00', before='18:00'}]";
$conf[]="  # thu = [{after='9:00', before='18:00'}]";
$conf[]="  # fri = [{after='9:00', before='17:00'}]";
$conf[]="";
$conf[]="";
$conf[]="";
$conf[]="#########################";
$conf[]="#        Servers        #";
$conf[]="#########################";
$conf[]="";
$conf[]="## Remote lists of available servers";
$conf[]="## Multiple sources can be used simultaneously, but every source";
$conf[]="## requires a dedicated cache file.";
$conf[]="##";
$conf[]="## Refer to the documentation for URLs of public sources.";
$conf[]="##";
$conf[]="## A prefix can be prepended to server names in order to";
$conf[]="## avoid collisions if different sources share the same for";
$conf[]="## different servers. In that case, names listed in `server_names`";
$conf[]="## must include the prefixes.";
$conf[]="##";
$conf[]="## If the `urls` property is missing, cache files and valid signatures";
$conf[]="## must be already present; This doesn't prevent these cache files from";
$conf[]="## expiring after `refresh_delay` hours.";
$conf[]="";
$conf[]="[sources]";
$conf[]="";
$conf[]="  ## An example of a remote source from https://github.com/DNSCrypt/dnscrypt-resolvers";
$conf[]="";
$conf[]="[sources.'public-resolvers']";
$conf[]="  urls = ['https://raw.githubusercontent.com/DNSCrypt/dnscrypt-resolvers/master/v2/public-resolvers.md', 'https://download.dnscrypt.info/resolvers-list/v2/public-resolvers.md']";
$conf[]="  cache_file = 'public-resolvers.md'";
$conf[]="  minisign_key = 'RWQf6LRCGA9i53mlYecO4IzT51TGPpvWucNSCh1CBM0QTaLn73Y7GFO3'";
$conf[]="  refresh_delay = 72";
$conf[]="  prefix = ''";
$conf[]="";
$conf[]="  ## Quad9 over DNSCrypt - https://quad9.net/";
$conf[]="";
$conf[]="[sources.quad9-resolvers]";
$conf[]="\turls = [\"https://www.quad9.net/quad9-resolvers.md\"]";
$conf[]="\tminisign_key = \"RWQBphd2+f6eiAqBsvDZEBXBGHQBJfeG6G+wJPPKxCZMoEQYpmoysKUN\"";
$conf[]="\tcache_file = \"quad9-resolvers.md\"";
$conf[]="\trefresh_delay = 72";
$conf[]="\tprefix = \"quad9-\"";
$conf[]="";
$conf[]="  ## Another example source, with resolvers censoring some websites not appropriate for children";
$conf[]="  ## This is a subset of the `public-resolvers` list, so enabling both is useless";
$conf[]="";
$conf[]="[sources.'parental-control']";
$conf[]="\turls = ['https://raw.githubusercontent.com/DNSCrypt/dnscrypt-resolvers/master/v2/parental-control.md', 'https://download.dnscrypt.info/resolvers-list/v2/parental-control.md']";
$conf[]="\tcache_file = 'parental-control.md'";
$conf[]="\tminisign_key = 'RWQf6LRCGA9i53mlYecO4IzT51TGPpvWucNSCh1CBM0QTaLn73Y7GFO3'";
$conf[]="";
$conf[]="";
$conf[]="";
$conf[]="## Optional, local, static list of additional servers";
$conf[]="## Mostly useful for testing your own servers.";
$conf[]="";
$conf[]="[static]";
$conf[]="";
$conf[]="  # [static.'google']";
$conf[]="  # stamp = 'sdns://AgUAAAAAAAAAAAAOZG5zLmdvb2dsZS5jb20NL2V4cGVyaW1lbnRhbA'";
$conf[]="";

@mkdir("/etc/dnscrypt-proxy",0755,true);
@file_put_contents("/etc/dnscrypt-proxy/dnscrypt-proxy-check.conf",@implode("\n",$conf));

    echo "Process: dnscrypt-proxy /etc/dnscrypt-proxy/dnscrypt-proxy-check.conf done\n";
    $dnscrypt=$unix->find_program("dnscrypt-proxy");

    exec("$dnscrypt -config /etc/dnscrypt-proxy/dnscrypt-proxy-check.conf -check 2>&1",$results);
    $VERIF=true;
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        echo "Process: dnscrypt-proxy $line\n";
        if(preg_match("#\] \[FATAL\]#",$line)){
            $VERIF=false;
            break;
        }
    }

    if($VERIF){
        echo "Process: dnscrypt-proxy verification OK\n";
        @unlink("/etc/dnscrypt-proxy/dnscrypt-proxy.conf");
        @copy("/etc/dnscrypt-proxy/dnscrypt-proxy-check.conf","/etc/dnscrypt-proxy/dnscrypt-proxy.conf");
        @unlink("/etc/dnscrypt-proxy/dnscrypt-proxy-check.conf");
    }

}




/**
 * @return array
 */


function dump_dns_list(){
    $unix=new unix();
    $dnscrypt=$unix->find_program("dnscrypt-proxy");
    build_configuration();
    exec("$dnscrypt -config /etc/dnscrypt-proxy/dnscrypt-proxy.conf -json -list-all -loglevel 1 2>&1",$results);

    $newdump=array();
    foreach ($results as $line){
        $line=trim($line);
        if(preg_match("#^\[[0-9]+-[0-9]+-[0-9]+.*?\[[A-Z]+#",$line)){
            echo "Process: dnscrypt-proxy Skip $line\n";
            continue;
        }

        $newdump[]=$line;

    }

    echo "Process: dnscrypt-proxy implode ".count($newdump)." lines\n";
    $string=@implode("\n",$newdump);


    return json_decode($string);


}

function update_sites_list(){

	$sock=new sockets();

	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");


	$DnsCryptResolversv2=intval($sock->GET_INFO("DnsCryptResolversv2"));

    if($DnsCryptResolversv2==0){
        if($q->TABLE_EXISTS("DnsCryptResolvers")){$q->QUERY_SQL("DROP TABLE DnsCryptResolvers");}
        $sock->SET_INFO("DnsCryptResolversv2",1);
    }
	
	$sql="CREATE TABLE IF NOT EXISTS `DnsCryptResolvers` (
		Name TEXT PRIMARY KEY,
		proto TEXT,
		Description TEXT,
	    DNSSECValidation INTEGER,
		NoLogs INTEGER,
		NoFilter INTEGER,
		zOrder INTEGER,
		ResolverAddress TEXT,
		ProviderPublicKey TEXT,
		Enabled INTEGER NOT NULL DEFAULT 1
		)";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error."\n";
        build_progress_update(110,"{updating} {mysql_error}");
		return false;
	}

    build_progress_update(20,"{updating}....");

	$json=dump_dns_list();

    $prefix="INSERT OR IGNORE INTO `DnsCryptResolvers` (Name,proto,Description,DNSSECValidation,NoLogs,NoFilter,ResolverAddress,ProviderPublicKey,Enabled,zOrder) VALUES ";
    $c=0;
	foreach ($json as $line){
        $ResolverAddresses=array();
	    $ProviderName=$line->name;
	    $proto=$line->proto;
        build_progress_update(50,"{updating}.$ProviderName...");

	    foreach ($line->addrs as $index=>$myaddr){
            $ResolverAddresses[]=$myaddr.":".$line->ports[0];
        }
        $ResolverAddress=@implode(",",$ResolverAddresses);
	    $description=$line->description;
        $description=$q->sqlite_escape_string2(str_replace("\n","<br>",$description));
        $DNSSECValidation=intval($line->dnssec);
        $NoLogs=intval($line->nolog);
        $NoFilter=intval($line->nofilter);
        $c++;
        $ssql="('$ProviderName','$proto','$description','$DNSSECValidation',$NoLogs,$NoFilter,'$ResolverAddress','',1,$c)";
        $sql=$prefix.$ssql;
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            echo $q->mysql_error."\n$sql\n";
            build_progress_update(110,"{updating} {mysql_error}");
            return false;
        }


    }

    build_progress_update(100,"{updating} {success}");
	return true;
}
function stop(){
	$unix=new unix();
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		echo "Process: dnscrypt-proxy already stopped\n";
		return true;
	}

	system("/etc/init.d/dnscrypt-proxy stop");

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        echo "Process: dnscrypt-proxy Stopped\n";
        return true;
    }


    echo "Process: stopping PID $pid\n";

	$unix->KILL_PROCESS($pid,9);
	for($i=1;$i<6;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){
			echo "Process: Stopped\n";
			return true;
		}
		echo "Process: waiting PID $pid $i/5\n";
		$unix->KILL_PROCESS($pid,9);
		sleep(1);
	
	}
    $pid=PID_NUM();
    if($unix->process_exists($pid)){return true;}
    return false;

}


function start(){
	
	$unix=new unix();
	$pid=PID_NUM();
	$DnsCryptLocalIP=null;
	if($unix->process_exists($pid)){
		@file_put_contents("/var/run/dnscrypt-proxy.pid", $pid);
		echo "Process: dnscrypt-proxy already exists pid $pid\n";
		return true;
	}

	$tempfile=$unix->FILE_TEMP();


    echo "Process: dnscrypt-proxy starting....\n";

	system("/etc/init.d/dnscrypt-proxy start");
	
	for($i=1;$i<6;$i++){
		echo "Process: dnscrypt-proxy waiting $i/5\n";
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
		sleep(1);
	}
	$pid=PID_NUM();
	
	
	
	if(!$unix->process_exists($pid)){
		echo "Process: dnscrypt-proxy failed\n";
		$f=explode("\n",@file_get_contents($tempfile));
		foreach ($f as $line){
			if(trim($line)==null){continue;}
			echo "Process: $line\n";
		}
		@unlink($tempfile);
		return false;
	}
	$f=explode("\n",@file_get_contents($tempfile));
	foreach ($f as $line){
		if(trim($line)==null){continue;}
		echo "Process: $line\n";
	}
	@unlink($tempfile);
	echo "Process: dnscrypt-proxy Success\n";
	return true;
	
	
}
function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/dnscrypt-proxy.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("dnscrypt-proxy");
	return $unix->PIDOF($Masterbin);
}
function install_service(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/dnscrypt-proxy";
    $Masterbin=$unix->find_program("dnscrypt-proxy");
	$daemonbinLog=basename($INITD_PATH);
	$php5script=__FILE__;
    $conf[]="#!/bin/sh";
    $conf[]="# For RedHat and cousins:";
    $conf[]="# chkconfig: - 99 01";
    $conf[]="# description: Encrypted/authenticated DNS proxy";
    $conf[]="# processname: $Masterbin";
    $conf[]="";
    $conf[]="### BEGIN INIT INFO";
    $conf[]="# Provides:          $Masterbin";
    $conf[]="# Required-Start:";
    $conf[]="# Required-Stop:";
    $conf[]="# Default-Start:     2 3 4 5";
    $conf[]="# Default-Stop:      0 1 6";
    $conf[]="# Short-Description: DNSCrypt client proxy";
    $conf[]="# Description:       Encrypted/authenticated DNS proxy";
    $conf[]="### END INIT INFO";
    $conf[]="";
    $conf[]="cmd=\"$Masterbin -syslog -config /etc/dnscrypt-proxy/dnscrypt-proxy.conf\"";
    $conf[]="";
    $conf[]="name=\$(basename \$(readlink -f \$0))";
    $conf[]="pid_file=\"/var/run/\$name.pid\"";
    $conf[]="stdout_log=\"/var/log/\$name.log\"";
    $conf[]="stderr_log=\"/var/log/\$name.err\"";
    $conf[]="";
    $conf[]="[ -e /etc/sysconfig/\$name ] && . /etc/sysconfig/\$name";
    $conf[]="";
    $conf[]="get_pid() {";
    $conf[]="    cat \"\$pid_file\"";
    $conf[]="}";
    $conf[]="";
    $conf[]="is_running() {";
    $conf[]="    [ -f \"\$pid_file\" ] && ps \$(get_pid) > /dev/null 2>&1";
    $conf[]="}";
    $conf[]="";
    $conf[]="case \"\$1\" in";
    $conf[]="    start)";
    $conf[]="        if is_running; then";
    $conf[]="            echo \"Already started\"";
    $conf[]="        else";
    $conf[]="            echo \"Starting \$name\"";
    $conf[]="            cd '/root'";
    $conf[]="            \$cmd >> \"\$stdout_log\" 2>> \"\$stderr_log\" &";
    $conf[]="            echo \$! > \"\$pid_file\"";
    $conf[]="            if ! is_running; then";
    $conf[]="                echo \"Unable to start, see \$stdout_log and \$stderr_log\"";
    $conf[]="                exit 1";
    $conf[]="            fi";
    $conf[]="        fi";
    $conf[]="    ;;";
    $conf[]="    stop)";
    $conf[]="        if is_running; then";
    $conf[]="            echo -n \"Stopping \$name..\"";
    $conf[]="            kill \$(get_pid)";
    $conf[]="            for i in \$(seq 1 10)";
    $conf[]="            do";
    $conf[]="                if ! is_running; then";
    $conf[]="                    break";
    $conf[]="                fi";
    $conf[]="                echo -n \".\"";
    $conf[]="                sleep 1";
    $conf[]="            done";
    $conf[]="            echo";
    $conf[]="            if is_running; then";
    $conf[]="                echo \"Not stopped; may still be shutting down or shutdown may have failed\"";
    $conf[]="                exit 1";
    $conf[]="            else";
    $conf[]="                echo \"Stopped\"";
    $conf[]="                if [ -f \"\$pid_file\" ]; then";
    $conf[]="                    rm \"\$pid_file\"";
    $conf[]="                fi";
    $conf[]="            fi";
    $conf[]="        else";
    $conf[]="            echo \"Not running\"";
    $conf[]="        fi";
    $conf[]="    ;;";
    $conf[]="    restart)";
    $conf[]="        \$0 stop";
    $conf[]="        if is_running; then";
    $conf[]="            echo \"Unable to stop, will not attempt to start\"";
    $conf[]="            exit 1";
    $conf[]="        fi";
    $conf[]="        \$0 start";
    $conf[]="    ;;";
    $conf[]="    status)";
    $conf[]="        if is_running; then";
    $conf[]="            echo \"Running\"";
    $conf[]="        else";
    $conf[]="            echo \"Stopped\"";
    $conf[]="            exit 1";
    $conf[]="        fi";
    $conf[]="    ;;";
    $conf[]="    *)";
    $conf[]="    echo \"Usage: \$0 {start|stop|restart|status}\"";
    $conf[]="    exit 1";
    $conf[]="    ;;";
    $conf[]="esac";
    $conf[]="exit 0\n";
	
	
	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $conf));
	@chmod($INITD_PATH,0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
	shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
			shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
}



$curl=new ccurl();
