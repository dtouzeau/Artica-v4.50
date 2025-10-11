<?php


class nginx_proxy_path
{
    private $serviceid = 0;
    public $HostHeader = null;
    public $RemotePath = null;
    public $UseSSL = 0;
    private $directory_id = 0;
    private $cache_erverything = 0;
    private $proxy_cache_valid_default = 0;
    private $proxy_d_intro = null;
    private $order = 0;
    private $directories_backends = array();
    private $directories_backends_headers = array();
    private $directories_backends_options = array();
    private $directories_backends_names = array();
    private $directories_backends_root = array();
    private $BackendsLogs = array();
    private $NginxHTTPSubModule = 0;

    public function __construct($service_id = 0, $zorder = 0, $directory_id = 0)
    {
        $this->NginxHTTPSubModule = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPSubModule"));
        $this->serviceid = $service_id;
        $sock = new socksngix($service_id);
        $this->WebSocketsSupport = intval($sock->GET_INFO("WebSocketsSupport"));
        $this->HostHeader();
        $this->RemotePath = $sock->GET_INFO("RemotePath");
        if (strlen($this->RemotePath) < 2) {
            $this->RemotePath = null;
        }
        $this->parse_remote_path();
        $this->UseSSL = intval($sock->GET_INFO("UseSSL"));
        $this->http_proto = "http";
        $this->directory_id = $directory_id;
        $this->cache_erverything = intval($sock->GET_INFO("cache_erverything"));
        $this->proxy_cache_valid_default = intval($sock->GET_INFO("proxy_cache_valid_default"));
        if ($this->proxy_cache_valid_default == 0) {
            $this->proxy_cache_valid_default = 720;
        }
        $this->order = intval($zorder);
        $this->patch_tables();
        $this->main_cache_intro();
    }

    public function HostHeader(): string
    {
        $sock = new socksngix($this->serviceid);
        $this->HostHeader = trim($sock->GET_INFO("HostHeader"));
        $this->HostHeader = $this->tool_nginx_clean_uri($this->HostHeader);
        return $this->HostHeader;
    }

    private function tool_nginx_clean_uri($uri): string
    {
        if ($uri == null) {
            return "";
        }
        $uri = trim(strtolower(strval($uri)));
        if (preg_match("#:\/\/#", $uri)) {
            $arrM = parse_url($uri);
            $uri = $arrM["host"];
            if (preg_match("#(.+?):[0-9]+#", $uri, $re)) {
                $uri = $re[1];
            }
            return $uri;
        }

        if (strpos(" $uri", "/") > 0) {
            $uri = "http://$uri";
            $arrM = parse_url($uri);
            $uri = $arrM["host"];
            if (preg_match("#(.+?):[0-9]+#", $uri, $re)) {
                $uri = $re[1];
            }
            return $uri;
        }

        return $uri;
    }

    private function parse_remote_path(): bool
    {
        $this->RemotePath = $this->contstruct_remote_path($this->RemotePath);
        return true;
    }

    private function contstruct_remote_path($path): string
    {
        if ($path == null) {
            return "/";
        }
        if (!preg_match("#^\/#", $path)) {
            $path = "/$path";
        }
        if (!preg_match("#\/$#", $path)) {
            $path = "$path/";
        }
        return $path;
    }

    private function build_proxy_path()
    {
        $this->proxy_pass = $this->construct_proxy_path("backends{$this->serviceid}", $this->RemotePath, $this->UseSSL);
    }

    private function construct_proxy_path($BackendName, $RemotePath, $UseSSL = 0)
    {
        $RemotePath = $this->contstruct_remote_path($RemotePath);
        $http_proto = "http";
        if ($UseSSL == 1) {
            $http_proto = "https";
        }
        return "proxy_pass {$http_proto}://$BackendName{$RemotePath};";
    }

    public function header_directives(): string
    {
        $nginxsock = new socksngix($this->serviceid);
        $proxy_hide_headers = array();
        $header_rules = array();
        $proxy_hide_headers_data = $nginxsock->GET_INFO("proxy_hide_headers");
        $header_rules_data = $nginxsock->GET_INFO("header_rules");

        if ($proxy_hide_headers_data <> null) {
            $proxy_hide_headers = unserialize(base64_decode($proxy_hide_headers_data));
        }
        if ($header_rules_data <> null) {
            $header_rules = unserialize(base64_decode($header_rules_data));
        }

        foreach ($proxy_hide_headers as $num => $ligne) {
            $enable = intval($ligne["enable"]);
            $header = trim($ligne["header"]);
            if ($enable == 0) {
                continue;
            }
            if ($header == null) {
                continue;
            }
            $description = trim($ligne["description"]);
            $description = str_replace("\n", " ", $description);
            if ($description <> null) {
                $intro[] = "#\t$description ($num)";
            }
            $f[] = "\tproxy_hide_header '$header';";
        }

        $f = array();
        $intro = array();
        foreach ($header_rules as $num => $ligne) {
            $enable = intval($ligne["enable"]);
            $header = trim($ligne["header"]);
            if ($enable == 0) {
                continue;
            }
            if ($header == null) {
                continue;
            }
            $header_value = trim($ligne["header_value"]);
            $header_value = str_replace('"', "'", $header_value);

            $description = trim($ligne["description"]);
            $description = str_replace("\n", " ", $description);
            if ($description <> null) {
                $intro[] = "#\t$description ($num)";
            }
            if ($header_value == "REMOVE") {
                $f[] = "\tmore_clear_headers '$header';";
                continue;
            }

            $f[] = "\tadd_header $header\t\"$header_value\" always;";
        }
        if (count($f) == 0) {
            return "";
        }
        return @implode("\n", $intro) . "\n" . @implode("\n", $f);
    }


    private function main_cache_intro()
    {
        $header_directives = $this->header_directives();
        if ($header_directives <> null) {
            $this->proxy_d_intro[] = $header_directives;
        }

        $this->proxy_d_intro[] = "\tproxy_set_header Connection \"\";";
        if ($this->WebSocketsSupport == 1) {
            $this->proxy_d_intro[] = "\tproxy_set_header Upgrade \$http_upgrade;";
            $this->proxy_d_intro[] = "\tproxy_set_header Connection \$connection_upgrade;";
        }

        $this->proxy_d_intro[] = "\tproxy_set_header X-Real-IP \$remote_addr;";
        $this->proxy_d_intro[] = "\tproxy_set_header X-Forwarded-For \$remote_addr;";
        $this->proxy_d_intro[] = "\tproxy_set_header Accept-Encoding \"\";";

    }

    public function proxy_d()
    {
        $proxy_d = $this->proxy_d_intro;
        if ($this->HostHeader <> null) {
            $proxy_d[] = "\tproxy_set_header Host $this->HostHeader;";
            $proxy_d[] = "\tproxy_redirect http://$this->HostHeader/ /;";
            $proxy_d[] = "\tproxy_redirect https://$this->HostHeader/ /;";
        }
        if ($this->HostHeader == null) {
            $proxy_d[] = "\tproxy_redirect default;";
            $proxy_d[] = "\tproxy_set_header Host \$host;";
        }

        if ($this->UseSSL == 1) {
            $proxy_d[] = "\tproxy_set_header X-Forwarded-Proto https;";
        }


        return @implode("\n", $proxy_d);
    }

    function DirectoriesProxy($DirectoryID = 0): string
    {
        if ($DirectoryID == 0) {
            return "";
        }
        if (count($this->directories_backends[$DirectoryID]) == 0) {
            return "";
        }
        $proxy_d = $this->proxy_d_intro;
        $UseSSL = $this->directories_backends_options[$DirectoryID]["UseSSL"];


        if (isset($this->directories_backends_headers[$DirectoryID])) {
            $HostHeader = trim($this->directories_backends_headers[$DirectoryID]);
            $HostHeader = $this->tool_nginx_clean_uri($HostHeader);
        }
        $proxy_d[] = "\t\tproxy_set_header X-ArticaDir $DirectoryID;";
        if ($UseSSL == 1) {
            $proxy_d[] = "\t\tproxy_set_header X-Forwarded-Proto https;";
        }
        $BacKendName = $this->directories_backends_names[$DirectoryID];
        $RootPath = $this->directories_backends_root[$DirectoryID];
        $proxy_d[] = "\t\t# Root Path: $RootPath";
        $proxy_d[] = "\t\t" . $this->construct_proxy_path($BacKendName, $RootPath, $UseSSL);
        if ($HostHeader <> null) {
            $proxy_d[] = "\t\tproxy_set_header Host $HostHeader;";
            $proxy_d[] = "\t\tproxy_redirect http://$HostHeader/ /;";
            $proxy_d[] = "\t\tproxy_redirect https://$HostHeader/ /;";

            if ($this->NginxHTTPSubModule == 1) {
                $proxy_d[] = "\tsub_filter '$HostHeader'  \$host;";
            }
        }
        if ($HostHeader == null) {
            $proxy_d[] = "\t\tproxy_redirect default;";
            $proxy_d[] = "\t\tproxy_set_header Host \$host;";
        }

        return @implode("\n", $proxy_d);

    }


    public function build_backends(): bool
    {
        $socksngix = new socksngix($this->serviceid);
        $ForwardServersDynamics = intval($socksngix->GET_INFO("ForwardServersDynamics"));
        $upstream_file = "/etc/nginx/backends.d/$this->order-$this->serviceid.conf";

        if ($ForwardServersDynamics == 1) {
            $UPS[] = "#\t Dynamic method used";
            @file_put_contents("/home/artica/nginx/compiled/upstream-$this->serviceid.conf", @implode("\n", $UPS));
            @file_put_contents($upstream_file, @implode("\n", $UPS));
            return true;

        }
        $q = new lib_sqlite("/home/artica/SQLITE/nginx.db");
        $KeepAlive = intval($socksngix->GET_INFO("BackEndsKeepAlive"));

        $UPS[] = "upstream backends{$this->serviceid} {";
        $UPS[] = "\tleast_conn;";
        $results = $q->QUERY_SQL("SELECT * FROM backends WHERE serviceid='$this->serviceid'");
        $this->BackendsLogs[] = "Main Backends for <$this->serviceid> =<" . count($results) . ">";
        foreach ($results as $md5 => $ligne) {
            $hostname = $ligne["hostname"];
            if (preg_match("#^http.*?:#i", $hostname)) {
                $parse_url = parse_url($hostname);
                $hostname = $parse_url["host"];
            }
            $port = $ligne["port"];
            $UPS[] = "\tserver $hostname:$port;";
            $this->BackendsLogs[] = "Main Backend =<$hostname:$port>";

        }
        if ($KeepAlive > 0) {
            $UPS[] = "\tkeepalive $KeepAlive;";
        }

        $UPS[] = "}";


        $results = $q->QUERY_SQL("SELECT * FROM  directories_backends WHERE serviceid='$this->serviceid'");
        $this->BackendsLogs[] = "Directories Backends =<" . count($results) . ">";
        foreach ($results as $index => $ligne) {
            $hostname = $ligne["hostname"];
            $port = $ligne["port"];
            $directory_id = $ligne["directory_id"];
            $options = unserialize(base64_decode($ligne["options"])); //UseSSL
            $UseSSL = $options["UseSSL"];
            $root = trim($ligne["root"]);
            if (!isset($this->directories_backends_root[$directory_id])) {
                $this->directories_backends_root[$directory_id] = $root;
            }
            if (preg_match("#^(.+?)\((.+?)\)#", $hostname, $re)) {
                $hostname = trim($re[1]);
                $this->directories_backends_headers[$directory_id] = trim($re[2]);
            }

            if (!preg_match("#^[0-9\.]+$#", $hostname)) {
                $this->directories_backends_headers[$directory_id] = $hostname;
            }

            $this->directories_backends[$directory_id][] = "\tserver $hostname:$port;";
            $this->directories_backends_options[$directory_id]["UseSSL"] = 0;
            $this->BackendsLogs[] = "Directory $directory_id <$hostname:$port> UseSSL=$UseSSL";
            if ($UseSSL == 1) {
                $this->directories_backends_options[$directory_id]["UseSSL"] = 1;
            }

        }
        foreach ($this->directories_backends as $directoryid => $servs) {
            $BackendName = "SubBackends{$this->serviceid}{$directoryid}";
            $this->directories_backends_names[$directoryid] = $BackendName;
            $UPS[] = "upstream $BackendName {";
            $UPS[] = @implode("\n", $servs);
            $UPS[] = "}";
        }

        if (!is_dir("/home/artica/nginx/compiled")) {
            @mkdir("/home/artica/nginx/compiled", 0644, true);
        }
        @file_put_contents("/home/artica/nginx/compiled/upstream-$this->serviceid.conf", @implode("\n", $UPS));
        @file_put_contents($upstream_file, @implode("\n", $UPS));
        return true;
    }

    public function build_backends_logs()
    {
        foreach ($this->BackendsLogs as $line) {
            if ($GLOBALS["VERBOSE"]) {
                echo "* * * * $line\n";
            }
            $f[] = "#\t$line";
        }
        return @implode("\n", $f);

    }

    public function DirectoriesSubfilters($DirectoryID = 0)
    {
        if ($this->NginxHTTPSubModule == 0) {
            return "";
        }
        if ($DirectoryID == 0) {
            return "";
        }
        $q = new lib_sqlite("/home/artica/SQLITE/nginx.db");
        $ligne = $q->mysqli_fetch_array("SELECT replace FROM ngx_directories WHERE ID=$DirectoryID");
        $sub_filters = unserialize(base64_decode($ligne["replace"]));
        if (!is_array($sub_filters)) {
            $sub_filters = array();
        }
        if (count($sub_filters) == 0) {
            return "";
        }
        $f_stub = $this->contruct_replace_filters($sub_filters);
        if (count($f_stub) > 0) {
            $f_stub[] = "\tsub_filter_once off;";
            $f_stub[] = "\tsub_filter_types text/xml text/css text/javascript application/x-javascript application/json;";
        }
        return @implode("\n", $f_stub);
    }


    private function contruct_replace_filters($array): array
    {
        foreach ($array as $num => $ligne) {
            $enable = intval($ligne["enable"]);
            $pattern = $ligne["pattern"];
            $replace = $ligne["replace"];
            if ($enable == 0) {
                continue;
            }
            $description = trim($ligne["description"]);
            if (strlen($pattern) == 0) {
                continue;
            }

            $description = str_replace("\n", " ", $description);
            $pattern = str_replace("'", "\'", $pattern);
            $replace = str_replace("'", "\'", $replace);
            $f_stub[] = "#\tRule $num: $description";
            $f_stub[] = "\tsub_filter '$pattern'  '$replace';";

        }
        return $f_stub;
    }

    private function patch_tables()
    {
    }
}
