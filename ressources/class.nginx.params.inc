<?php

class socksngix{
    public $serviceid=0;
	public $found=false;

	public function __construct($serverid){
		$this->serviceid=intval($serverid);
	}


    public function GetServiceName():string{
        $ligne=$this->GetCache();
        if(!isset($ligne["servicename"])){
            return "Unknown";
        }
        return strval($ligne["servicename"]);
    }

    public function ExtractHosts():array{
        $ligne=$this->GetCache();
        $hosts=$ligne["hosts"];
        $f=array();
        if(!is_null($hosts)) {
            $Zhosts = explode("||", $hosts);
        }
        $Type=$ligne["type"];

        if($Type==5){
            $z=$this->serversnames_stream();
            return array($z);
        }

        VERBOSE("extract_hosts($this->serviceid): Server Type: $Type",__LINE__);
        if($Type==14){
            return array("http(s)://*/","http(s)://*/");
        }
        $ssl_certificate=$this->GET_INFO("ssl_certificate");
        $Redirect80To443=intval($this->GET_INFO("Redirect80To443"));
        $MAIN_PORTS=array();

        $results=$this->Stream_ports_for_service();
        foreach ($results as $md5=>$ligne){
            $port=intval($ligne["port"]);
            if($port==0){continue;}
            $interface=$ligne["interface"];
            $MAIN_PORTS["$interface:$port"]=array(
                "port"=>$port,
                "options"=>$GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"])
            );
        }
        if(count($MAIN_PORTS)==0){
            $f[]="<div><small class='text-danger'><i class=\"fas fa-exclamation-triangle\"></i> {no_backend_port_defined}</small></div>";
        }

        $missing_cert="<span class='label label-warning'>{missing_certificate}</span>";

        $ForwardServersDynamics =   intval($this->GET_INFO("ForwardServersDynamics"));
        $FSDynamicsExt          =   intval($this->GET_INFO("FSDynamicsExt"));
        $FSDynamicsSrc          =   trim($this->GET_INFO("FSDynamicsSrc"));
        $NginxProxyProtocol     =   intval($this->GET_INFO("NginxProxyProtocol"));

        if($ForwardServersDynamics==1){
            if($FSDynamicsExt==1){
                if(preg_match("#\.(.*?)$#",$FSDynamicsSrc,$re)){
                    $FSDynamicsSrc=str_replace(".".$re[1],".*",$FSDynamicsSrc);
                }else{
                    $FSDynamicsSrc=$FSDynamicsSrc.".*";
                }
            }
            $Zhosts[]="*.$FSDynamicsSrc";
        }

        $ServerNameFields=array();

        VERBOSE("ZHOSTS === ".count($Zhosts)." ITEMS",__LINE__);
        foreach ($Zhosts as $servername){
            $catch_all=null;
            $servername=trim($servername);
            VERBOSE("Host === [$servername]",__LINE__);
            if($servername==null){continue;}
            if($servername=="*"){
                $servername=".*";
                $catch_all="&nbsp;&nbsp;<span class='label label-warning'>{catch_all}</span>";
            }

            foreach ($MAIN_PORTS as $index=>$array){
                $port=$array["port"];

                if($Redirect80To443==1){
                    if($port==80){
                        $port=$port."&nbsp;<i class=\"fas fa-arrow-to-right\"></i>&nbsp;443";
                    }
                }
                if(!isset($array["options"])){ $array["options"]=array(); }
                if(!isset($array["options"]["ssl"])){ $array["options"]["ssl"]=0; }
                $options=$array["options"];
                $proto="http";

                if($port==443){
                    $proto="https";
                    $options["ssl"]=1;
                }

                if($options["ssl"]==1){
                    $proto="https";
                    if($ssl_certificate==null){$proto="$missing_cert&nbsp;$proto";}
                }
                if($NginxProxyProtocol==0){$options["proxy_protocol"]=0;}

                if($NginxProxyProtocol==1) {
                    if(!isset($options["proxy_protocol"])) {$options["proxy_protocol"]=1;}
                }

                if (isset($options["proxy_protocol"])) {
                    if (intval($options["proxy_protocol"]) == 1) {
                        $proto = "<strong>TCP PROXY&nbsp;|&nbsp;</strong>$proto";
                    }
                }

                if(count($ServerNameFields)<3){
                    $ServerNameFields[]="<div style='margin-top:5px'><small>$proto://$servername:$port</small>$catch_all</div>";
                }

                $f[]="<div style='margin-top:5px'><small>$proto://$servername:$port</small>$catch_all</div>";
            }

        }

        if(count($f)==0){
            VERBOSE("NO DOMAIN SPECIFIED !!",__LINE__);
            $ServerNameFields[]="<div>
            <strong class='text-danger'><i class=\"fas fa-exclamation-triangle\" ></i> {error_no_domain_specified}</strong>
          </div>";
            return array(array(),@implode("",$ServerNameFields));
        }

        return $f;
    }
    private function serversnames_stream():string{
        $f=array();
        $results=$this->Stream_ports_for_service();
        foreach ($results as $index=>$ligne) {
            $options = $GLOBALS["CLASS_SOCKETS"]->unserializeb64($ligne["options"]);
            $interface = $ligne["interface"];
            if ($interface == null) {
                $NICNAME = "{all}";
            } else {
                $eth = new system_nic($interface);
                $NICNAME = $eth->NICNAME;
            }
            $proto = "tcp";

            $port = $ligne["port"];
            if ($options["udp"] == 1) {
                $proto = "udp";
            }
            $f[]="<div>$proto://$NICNAME:$port</div>";

        }
        return @implode("",$f);
    }

    public function Stream_ports_for_service():array{
        if(isset($GLOBALS["NGINX_PARAMS"][__FUNCTION__][$this->serviceid])){
            return $GLOBALS["NGINX_PARAMS"][__FUNCTION__][$this->serviceid];
        }
        $serviceid=intval($this->serviceid);
        if($serviceid<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/stream_ports/serviceid/$this->serviceid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return array();
        }
        $GLOBALS["NGINX_PARAMS"][__FUNCTION__][$this->serviceid]=$data["rows"];
        return $data["rows"];
    }

    public function GetCache():array{
        if(isset($GLOBALS["/site/$this->serviceid"])){
            return $GLOBALS["/site/$this->serviceid"];
        }
        $json=json_decode($GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/site/$this->serviceid"),true);
        if(isset($json["config"])){
            $GLOBALS["/site/$this->serviceid"]=$json["config"];
            return $json["config"];
        }
        return array();
    }
    public function GetType():int{
        $ligne=$this->GetCache();
        if(isset($ligne["type"])){
            return intval($ligne["type"]);
        }
        return 0;
    }
	
	public function GET_INFO($key):string{
		$this->found=false;
		if($key==null){return "";}
		$resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/site/$this->serviceid/param/$key");
		$json=json_decode($resp,true);
		if(is_array($json) && $json["Status"]){
			$val=strval($json["value"]);
			if(strlen($val)>0){
				$this->found=true;
				return trim($val);
			}
		}
        return "";
	}

    public function SET_MULTI($vals=array()):bool{
        if(count($vals)==0){return true;}
        return $this->SET_MULTI_API($vals);
    }

    private function SET_MULTI_API($vals=array()):bool{
        $data=array("params"=>array());
        foreach($vals as $key=>$value){
            $data["params"][strval($key)]=strval($value);
        }
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX_POST_JSON(
            "/site/$this->serviceid/params", $data
        );
        $json=json_decode($resp,true);
        if(!isset($json["Status"]) || !$json["Status"]){
            $err=isset($json["Error"]) ? $json["Error"] : "unknown error";
            writelogs("SET_MULTI_API error: $err",__FUNCTION__,__FILE__,__LINE__);
            return false;
        }
        // Invalidate in-process cache so next GET_INFO re-fetches from Go API
        unset($GLOBALS["/site/$this->serviceid"]);
        $GLOBALS["CLASS_SOCKETS"]->CLUSTER_NGINX($this->serviceid);
        return true;
    }

	public function SET_INFO($key,$value):bool{
		if($key==null){return false;}
        return $this->SET_MULTI_API(array($key=>$value));

	}
	
	
}
if (!function_exists('fetch_httrack_sites_all')) {
    function fetch_httrack_sites_all():array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/httrack_sites?order=ID&desc=1");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}
// Returns nginx_services rows with enabled=1 via
// the daemon API, sorted by the given column (validated server-side against
// the table schema).
if (!function_exists('fetch_enabled_nginx_services')) {
    function fetch_enabled_nginx_services($orderCol):array{
        $orderCol=urlencode($orderCol);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/nginx_services?order=$orderCol");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        $out=array();
        foreach($data["rows"] as $r){
            if(intval(isset($r["enabled"])?$r["enabled"]:0)===1){$out[]=$r;}
        }
        return $out;
    }
}
if (!function_exists('fetch_httrack_site')) {
    function fetch_httrack_site($ID):array{
        $ID=intval($ID);
        if($ID==0){return [];}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/httrack_sites/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return [];}
        return $data["rows"][0];
    }
}
if (!function_exists('fetch_nginx_service_row')) {
    function fetch_nginx_service_row($ID):array{
        $ID=intval($ID);
        if($ID==0){return [];}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return [];}
        return $data["rows"][0];
    }
}
if (!function_exists('fetch_adminprivs_for_group')) {
    function fetch_adminprivs_for_group($group):array{
        // The daemon's /nginx-db/query/{table}/{column}/{value} route does not
        // URL-decode the value segment, so group items containing ':'
        // (e.g. "sqlite:1") never match. List the table and filter PHP-side.
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/adminprivs");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        $rows=array();
        foreach($data["rows"] as $ligne){
            if(!isset($ligne["item"]) || strval($ligne["item"])!==strval($group)){continue;}
            $rows[]=$ligne;
        }
        return $rows;
    }
}
if (!function_exists('fetch_webdav_access')) {
    function fetch_webdav_access($ID):array{
        $ID=intval($ID);
        if($ID==0){return [];}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/webdav_access/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return [];}
        return $data["rows"][0];
    }
}
// fetch_webdav_access_by_service returns all webdav_access rows for a service
// via the daemon API, sorted by zorder PHP-side.
if (!function_exists('fetch_webdav_access_by_service')) {
    function fetch_webdav_access_by_service($serviceid):array{
        $serviceid=intval($serviceid);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/webdav_access/serviceid/$serviceid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        $rows=$data["rows"];
        usort($rows,function($a,$b){return intval(isset($a["zorder"])?$a["zorder"]:0) - intval(isset($b["zorder"])?$b["zorder"]:0);});
        return $rows;
    }
}
if (!function_exists('fetch_all_sites_sorted')) {
    function fetch_all_sites_sorted():array{
        if(!isset($GLOBALS["CLASS_SOCKETS"])){
            if(!class_exists("sockets")){
                include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
            }
            $GLOBALS["CLASS_SOCKETS"]=new sockets();
        }
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sites/list");
        $data=json_decode($resp,true);
        $out=array();
        if(!is_array($data) || !isset($data["sites"]) || !is_array($data["sites"])){
            return $out;
        }
        foreach($data["sites"] as $site){
            $out[]=array(
                "ID"=>isset($site["ID"])?intval($site["ID"]):0,
                "servicename"=>isset($site["service_name"])?$site["service_name"]:"",
                "enabled"=>isset($site["enabled"])?intval($site["enabled"]):0,
            );
        }
        usort($out,function($a,$b){return strcmp($a["servicename"],$b["servicename"]);});
        return $out;
    }
}
if (!function_exists('fetch_enabled_sites_sorted')) {
    function fetch_enabled_sites_sorted():array{
        $all=fetch_all_sites_sorted();
        $out=array();
        foreach($all as $row){
            if(intval($row["enabled"])===1){$out[]=$row;}
        }
        return $out;
    }
}
if (!function_exists('fetch_nginx_client_cert')) {
    function fetch_nginx_client_cert($ID):array{
        $ID=intval($ID);
        if($ID==0){return [];}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_clients_certs/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return [];}
        return $data["rows"][0];
    }
}
// fetch_nginx_server_cert returns a single nginx_servers_certs row by ID via
// the daemon API. Empty array on miss.
if (!function_exists('fetch_nginx_server_cert')) {
    function fetch_nginx_server_cert($ID):array{
        $ID=intval($ID);
        if($ID==0){return [];}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_servers_certs/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return [];}
        return $data["rows"][0];
    }
}
// fetch_nginx_server_certs_ordered returns all nginx_servers_certs rows ordered
// by CertificateName via the daemon API.
if (!function_exists('fetch_nginx_server_certs_ordered')) {
    function fetch_nginx_server_certs_ordered():array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/nginx_servers_certs?order=CertificateName");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}
if (!function_exists('fetch_nginx_services_all')) {
    function fetch_nginx_services_all():array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/nginx_services");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}

// fetch_nginx_templates_all returns every nginx_templates row.
if (!function_exists('fetch_nginx_templates_all')) {
    function fetch_nginx_templates_all():array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/nginx_templates");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}
// fetch_httrack_sites_ordered returns httrack_sites rows ordered by enforceuri.
if (!function_exists('fetch_httrack_sites_ordered')) {
    function fetch_httrack_sites_ordered():array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/httrack_sites?order=enforceuri");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}

if (!function_exists('fetch_stream_port_by_id')) {
    function fetch_stream_port_by_id($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/stream_ports/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}

if (!function_exists('fetch_tpname')) {
    function fetch_tpname($tplid):string{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/template/".intval($tplid));
        $data=json_decode($resp,true);
        if(is_array($data) && !empty($data["Status"])){
            return strval($data["Tpname"]);
        }
        return "#$tplid";
    }
}
if (!function_exists('fetch_sites_map')) {
    function fetch_sites_map():array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/sites/list");
        $data=json_decode($resp,true);
        $out=array();
        if(is_array($data) && isset($data["sites"]) && is_array($data["sites"])){
            foreach($data["sites"] as $site){
                $out[intval($site["ID"])]=$site["service_name"];
            }
        }
        return $out;
    }
}
if (!function_exists('fetch_cache_by_id')) {
    function fetch_cache_by_id($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/caches_center/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}
if (!function_exists('fetch_caches_by_cpu')) {
    function fetch_caches_by_cpu($cpu):array{
        $cpu=intval($cpu);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/caches_center/cpu/$cpu");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return array();
        }
        return $data["rows"];
    }
}
if (!function_exists('fetch_caches_all_by_zorder')) {
    function fetch_caches_all_by_zorder():array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/caches_center?order=zOrder");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return array();
        }
        return $data["rows"];
    }
}
// fetch_nginx_servicename returns servicename for an ID via API; "" on miss.
if (!function_exists('fetch_nginx_servicename')) {
    function fetch_nginx_servicename($ID):string{
        $ID=intval($ID);
        if($ID==0){return "";}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return "";}
        return isset($data["rows"][0]["servicename"]) ? strval($data["rows"][0]["servicename"]) : "";
    }
}
if (!function_exists('fetch_ngx_directory')) {
    function fetch_ngx_directory($ID):array{
        $ID=intval($ID);
        if($ID==0){return [];}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_directories/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return [];}
        return $data["rows"][0];
    }
}
// fetch_ngx_subdir_items_for_dir returns all ngx_subdir_items rows for a
// directory via the daemon API, sorted by zorder PHP-side.
if (!function_exists('fetch_ngx_subdir_items_for_dir')) {
    function fetch_ngx_subdir_items_for_dir($directoryid):array{
        $directoryid=intval($directoryid);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_subdir_items/directoryid/$directoryid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        $rows=$data["rows"];
        usort($rows,function($a,$b){return intval(isset($a["zorder"])?$a["zorder"]:0) - intval(isset($b["zorder"])?$b["zorder"]:0);});
        return $rows;
    }
}
if (!function_exists('fetch_stream_port')) {
    function fetch_stream_port($ID):array{
        $ID=intval($ID);
        if($ID==0){return [];}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/stream_ports/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return [];}
        return $data["rows"][0];
    }
}

// fetch_nginx_service_type returns the type column of a nginx_services row.
if (!function_exists('fetch_nginx_service_type')) {
    function fetch_nginx_service_type($ID):int{
        $ID=intval($ID);
        if($ID==0){return 0;}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return 0;}
        return intval(isset($data["rows"][0]["type"]) ? $data["rows"][0]["type"] : 0);
    }
}
if (!function_exists('fetch_ngx_directory_row')) {
    function fetch_ngx_directory_row($ID):array{
        $ID=intval($ID);
        if($ID==0){return [];}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_directories/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return [];}
        return $data["rows"][0];
    }
}
if (!function_exists('fetch_directory_by_id')) {
    function fetch_directory_by_id($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_directories/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}
if (!function_exists('fetch_subdir_item_by_id')) {
    function fetch_subdir_item_by_id($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_subdir_items/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}
// fetch_subdir_items_by_directoryid returns rows sorted by zorder ASC.
if (!function_exists('fetch_subdir_items_by_directoryid')) {
    function fetch_subdir_items_by_directoryid($directoryid):array{
        $directoryid=intval($directoryid);
        if($directoryid<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_subdir_items/directoryid/$directoryid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return array();
        }
        $rows=$data["rows"];
        usort($rows,function($a,$b){
            $az=isset($a["zorder"])?intval($a["zorder"]):0;
            $bz=isset($b["zorder"])?intval($b["zorder"]):0;
            if($az===$bz){
                $ai=isset($a["ID"])?intval($a["ID"]):0;
                $bi=isset($b["ID"])?intval($b["ID"]):0;
                return $ai-$bi;
            }
            return $az-$bz;
        });
        return $rows;
    }
}
// fetch_subdir_items_all returns every ngx_subdir_items row sorted by zorder.
// Preserves the original container_move semantics (which renumbered globally,
// not per-directoryid).
if (!function_exists('fetch_subdir_items_all')) {
    function fetch_subdir_items_all():array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/ngx_subdir_items?order=zorder");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return array();
        }
        return $data["rows"];
    }
}
if (!function_exists('fetch_bypass_cache')) {
    function fetch_bypass_cache($ID):array{
        $ID=intval($ID);
        if($ID==0){return [];}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/bypass_cache/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return [];}
        return $data["rows"][0];
    }
}
// fetch_bypass_cache_for_service returns all bypass_cache rows for a service via
// the daemon API. Search/OnlyActive filtering is applied PHP-side because the
// daemon's generic query endpoint only supports column-equals.
if (!function_exists('fetch_bypass_cache_for_service')) {
    function fetch_bypass_cache_for_service($serviceid):array{
        $serviceid=intval($serviceid);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/bypass_cache/serviceid/$serviceid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}
if (!function_exists('fetch_server_cert_by_id')) {
    function fetch_server_cert_by_id($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_servers_certs/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}
if (!function_exists('fetch_client_cert_by_id')) {
    function fetch_client_cert_by_id($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_clients_certs/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}
if (!function_exists('fetch_server_certs_all')) {
    function fetch_server_certs_all():array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/nginx_servers_certs?order=CertificateName");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return array();
        }
        return $data["rows"];
    }
}
if (!function_exists('fetch_client_certs_for_cert')) {
    function fetch_client_certs_for_cert($CertID):array{
        $CertID=intval($CertID);
        if($CertID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_clients_certs/certid/$CertID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return array();
        }
        $rows=$data["rows"];
        usort($rows,function($a,$b){
            return strcmp(isset($a["ClientName"])?$a["ClientName"]:"",isset($b["ClientName"])?$b["ClientName"]:"");
        });
        return $rows;
    }
}
// fetch_service_ids_with_param returns serviceids whose service_parameters
// row matches a given zkey and zvalue. Pulls the zkey-filtered set then
// filters zvalue PHP-side (the API endpoint is single-column WHERE).
if (!function_exists('fetch_service_ids_with_param')) {
    function fetch_service_ids_with_param($zkey,$zvalue):array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/service_parameters/zkey/".urlencode($zkey));
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return array();
        }
        $needle=strval($zvalue);
        $out=array();
        foreach($data["rows"] as $r){
            if(!isset($r["zvalue"])){continue;}
            if(strval($r["zvalue"])!==$needle){continue;}
            $sid=isset($r["serviceid"])?intval($r["serviceid"]):0;
            if($sid<=0){continue;}
            $out[]=$sid;
        }
        return $out;
    }
}
// fetch_nginx_services_all returns every nginx_services row via the daemon API.
if (!function_exists('fetch_nginx_services_all')) {
    function fetch_nginx_services_all():array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/nginx_services");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}
if (!function_exists('fetch_enforce_cache')) {
    function fetch_enforce_cache($ID):array{
        $ID=intval($ID);
        if($ID==0){return [];}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/enforce_cache/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return [];}
        return $data["rows"][0];
    }
}

// fetch_enforce_cache_for_service returns all enforce_cache rows for a service
// via the daemon API. Search/OnlyActive filtering is applied PHP-side because
// the daemon's generic query endpoint only supports column-equals.
if (!function_exists('fetch_enforce_cache_for_service')) {
    function fetch_enforce_cache_for_service($serviceid):array{
        $serviceid=intval($serviceid);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/enforce_cache/serviceid/$serviceid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}
if (!function_exists('fetch_clients_ban_by_id')) {
    function fetch_clients_ban_by_id($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_clients_bans/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}
if (!function_exists('fetch_clients_bans_search')) {
    function fetch_clients_bans_search($serviceid,$search):array{
        $serviceid=intval($serviceid);
        if($serviceid>0){
            $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_clients_bans/serviceid/$serviceid");
        }else{
            $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/nginx_clients_bans");
        }
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return array();
        }
        $rows=$data["rows"];
        $needle=strtolower(trim(str_replace(["*","%"],"",strval($search))));
        if(strlen($needle)>0){
            $rows=array_values(array_filter($rows,function($r) use($needle){
                $n=isset($r["ClientName"])?strtolower($r["ClientName"]):"";
                return strpos($n,$needle)!==false;
            }));
        }
        return $rows;
    }
}
if (!function_exists('fetch_stream_ports_all')) {
    function fetch_stream_ports_all():array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/stream_ports");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}
if (!function_exists('fetch_modsec_whitelist_by_rule')) {
    function fetch_modsec_whitelist_by_rule($ruleid):array{
        $ruleid=intval($ruleid);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/modsecurity_whitelist/wfrule/$ruleid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}
if (!function_exists('fetch_nginx_service_name')) {
    function fetch_nginx_service_name($ID):string{
        $ID=intval($ID);
        if($ID==0){return "";}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return "";}
        $row=$data["rows"][0];
        return isset($row["servicename"]) ? strval($row["servicename"]) : "";
    }
}
if (!function_exists('fetch_template_name')) {
    function fetch_template_name($tplid):string{
        $tplid=intval($tplid);
        if($tplid==0){return "";}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_templates/ID/$tplid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return "";}
        $row=$data["rows"][0];
        return isset($row["tpname"]) ? strval($row["tpname"]) : "";
    }
}
if (!function_exists('fetch_directory_backend_by_id')) {
    function fetch_directory_backend_by_id($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/directories_backends/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}
if (!function_exists('fetch_directory_backends_for_directory')) {
    function fetch_directory_backends_for_directory($directory_id):array{
        $directory_id=intval($directory_id);
        if($directory_id<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/directories_backends/directory_id/$directory_id");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return array();
        }
        return $data["rows"];
    }
}
if (!function_exists('fetch_directory_row')) {
    function fetch_directory_row($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_directories/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}
if (!function_exists('fetch_nginx_service_type')) {
    function fetch_nginx_service_type($serviceid):int{
        $serviceid=intval($serviceid);
        if($serviceid<=0){return 0;}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/ID/$serviceid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return 0;
        }
        $row=$data["rows"][0];
        return isset($row["type"]) ? intval($row["type"]) : 0;
    }
}
if (!function_exists('fetch_modsec_rule_by_id')) {
    function fetch_modsec_rule_by_id($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/mod_security_rules/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}
if (!function_exists('fetch_modsec_pattern_by_id')) {
    function fetch_modsec_pattern_by_id($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/mod_security_patterns/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}
if (!function_exists('fetch_modsec_patterns_by_ruleid')) {
    function fetch_modsec_patterns_by_ruleid($ruleid):array{
        $ruleid=intval($ruleid);
        if($ruleid<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/mod_security_patterns/ruleid/$ruleid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return array();
        }
        $rows=$data["rows"];
        // Original SQL: ORDER BY zorder DESC LIMIT 250
        usort($rows,function($a,$b){
            $az=isset($a["zorder"])?intval($a["zorder"]):0;
            $bz=isset($b["zorder"])?intval($b["zorder"]):0;
            return $bz-$az;
        });
        if(count($rows)>250){$rows=array_slice($rows,0,250);}
        return $rows;
    }
}
// fetch_modsec_rules_filtered returns mod_security_rules sorted by zorder
// DESC, optionally filtered by a substring (matches the legacy SQL LIKE on
// description+rulename). Limit 250 to match original behaviour.
if (!function_exists('fetch_modsec_rules_filtered')) {
    function fetch_modsec_rules_filtered($search=""):array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/mod_security_rules?order=zorder&desc=1");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return array();
        }
        $rows=$data["rows"];
        $search=trim($search);
        if(strlen($search)>0){
            $needle=strtolower(str_replace(["*","%"],"",$search));
            $rows=array_values(array_filter($rows,function($r) use($needle){
                $d=isset($r["description"])?strtolower($r["description"]):"";
                $n=isset($r["rulename"])?strtolower($r["rulename"]):"";
                return strpos($d,$needle)!==false || strpos($n,$needle)!==false;
            }));
        }
        if(count($rows)>250){$rows=array_slice($rows,0,250);}
        return $rows;
    }
}



// fetch_nginx_service / fetch_backend_by_id / fetch_backends_for_service:
// thin API wrappers around GET /nginx-db/query so this file talks only to
// the daemon. Empty array on any failure so callers can fall through.
if (!function_exists('fetch_nginx_service')) {
    function fetch_nginx_service($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}

// fetch_backends_for_service returns backends ordered by zOrder ASC. The
// /nginx-db/query endpoint doesn't ORDER BY, so we sort here.
if (!function_exists('fetch_backends_for_service')) {
    function fetch_backends_for_service($serviceid):array{
        $serviceid=intval($serviceid);
        if($serviceid<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/backends/serviceid/$serviceid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){
            return array();
        }
        $rows=$data["rows"];
        usort($rows,function($a,$b){
            $az=isset($a["zOrder"])?intval($a["zOrder"]):0;
            $bz=isset($b["zOrder"])?intval($b["zOrder"]):0;
            if($az===$bz){
                $ai=isset($a["ID"])?intval($a["ID"]):0;
                $bi=isset($b["ID"])?intval($b["ID"]):0;
                return $ai-$bi;
            }
            return $az-$bz;
        });
        return $rows;
    }
}



// fetch_nginx_templates_all returns every nginx_templates row.
if (!function_exists('fetch_nginx_templates_all')) {
    function fetch_nginx_templates_all():array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/nginx_templates");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}

// fetch_httrack_sites_ordered returns httrack_sites rows ordered by enforceuri.
if (!function_exists('fetch_httrack_sites_ordered')) {
    function fetch_httrack_sites_ordered():array{
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/list/httrack_sites?order=enforceuri");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}

// fetch_ngx_directories_for_service returns all ngx_directories rows for a
// service via the daemon API. Caller sorts as needed (the column-equals query
// endpoint doesn't accept ORDER BY).
if (!function_exists('fetch_ngx_directories_for_service')) {
    function fetch_ngx_directories_for_service($serviceid):array{
        $serviceid=intval($serviceid);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_directories/serviceid/$serviceid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}

// fetch_directories_backends_for_dir returns all directories_backends rows for
// a directory via the daemon API.
if (!function_exists('fetch_directories_backends_for_dir')) {
    function fetch_directories_backends_for_dir($directoryid):array{
        $directoryid=intval($directoryid);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/directories_backends/directory_id/$directoryid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}
if (!function_exists('fetch_backend_by_id')) {
    function fetch_backend_by_id($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/backends/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}
// fetch_backends_for_service returns all backends rows for a service.
if (!function_exists('fetch_backends_for_service')) {
    function fetch_backends_for_service($serviceid):array{
        $serviceid=intval($serviceid);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/backends/serviceid/$serviceid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        return $data["rows"];
    }
}
// fetch_nginx_service returns a single nginx_services row (associative
// array keyed by column name) via the daemon's /nginx-db/query API. Empty
// array on any failure so callers can fall through gracefully.
if (!function_exists('fetch_nginx_service')) {
    function fetch_nginx_service($ID):array{
        $ID=intval($ID);
        if($ID<=0){return array();}
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/nginx_services/ID/$ID");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){
            return array();
        }
        return $data["rows"][0];
    }
}
if (!function_exists('fetch_stream_ports_for_service')) {
    function fetch_stream_ports_for_service($serviceid):array{
        if(isset($GLOBALS["NGINX_PARAMS"][__FUNCTION__][$serviceid])){
            return $GLOBALS["NGINX_PARAMS"][__FUNCTION__][$serviceid];
        }
        $serviceid=intval($serviceid);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/stream_ports/serviceid/$serviceid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        $GLOBALS["NGINX_PARAMS"][__FUNCTION__][$serviceid]=$data["rows"];
        return $data["rows"];
    }
}
// fetch_ngx_directories_enabled_for_service returns enabled ngx_directories
// rows for a service via the daemon API, sorted by directory PHP-side.
if (!function_exists('fetch_ngx_directories_enabled_for_service')) {
    function fetch_ngx_directories_enabled_for_service($serviceid):array{
        $serviceid=intval($serviceid);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_directories/serviceid/$serviceid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        $rows=array();
        foreach($data["rows"] as $r){
            if(intval(isset($r["enabled"])?$r["enabled"]:0)===1){$rows[]=$r;}
        }
        usort($rows,function($a,$b){return strcmp(isset($a["directory"])?$a["directory"]:"",isset($b["directory"])?$b["directory"]:"");});
        return $rows;
    }
}

// fetch_ngx_subdir_items_for_dir returns ngx_subdir_items rows for a directory
// sorted by zorder PHP-side.
if (!function_exists('fetch_ngx_subdir_items_for_dir')) {
    function fetch_ngx_subdir_items_for_dir($directoryid):array{
        $directoryid=intval($directoryid);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/ngx_subdir_items/directoryid/$directoryid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"])){return [];}
        $rows=$data["rows"];
        usort($rows,function($a,$b){return intval(isset($a["zorder"])?$a["zorder"]:0) - intval(isset($b["zorder"])?$b["zorder"]:0);});
        return $rows;
    }
}

// fetch_httrack_site_for_service returns the first httrack_sites row for a
// given serviceid (httrack maps 1-to-1 with nginx services). Empty on miss.
if (!function_exists('fetch_httrack_site_for_service')) {
    function fetch_httrack_site_for_service($serviceid):array{
        $serviceid=intval($serviceid);
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API_NGINX("/nginx-db/query/httrack_sites/serviceid/$serviceid");
        $data=json_decode($resp,true);
        if(!is_array($data) || empty($data["Status"]) || !isset($data["rows"]) || !is_array($data["rows"]) || count($data["rows"])==0){return [];}
        return $data["rows"][0];
    }
}

// Effective AdvancedCacheParams of a site: the stored JSON blob is often partial and the daemon
// fills defaults in memory (reverse-proxy Configs.loadAdvancedCacheParams). These defaults MUST
// stay in sync with it. Used by fw.nginx.sites.advanced-caching.php and fw.nginx.sites.optimize.php.
if(!function_exists('ac_load_params')){
function ac_load_params($sock):array{
    $raw=$sock->GET_INFO("AdvancedCacheParams");
    $json=json_decode($raw,true);
    if(!is_array($json)){$json=array();}
    $out=array();
    $out["Directory"]=(isset($json["Directory"]) && strval($json["Directory"])!="")?strval($json["Directory"]):"/home/nginx/caches";
    $out["WorkDir"]=isset($json["WorkDir"])?strval($json["WorkDir"]):"";
    $out["MaxSizeGigabytes"]=isset($json["MaxSizeGigabytes"])?intval($json["MaxSizeGigabytes"]):0;
    if($out["MaxSizeGigabytes"]<=0){$out["MaxSizeGigabytes"]=1;}
    $out["InactiveTime"]=isset($json["InactiveTime"])?intval($json["InactiveTime"]):0;
    if($out["InactiveTime"]<=0){$out["InactiveTime"]=2;}
    $out["TTLCache"]=isset($json["TTLCache"])?intval($json["TTLCache"]):0;
    if($out["TTLCache"]<=0){$out["TTLCache"]=3600;}
    // UseStale: an int cannot tell "absent" from an explicit 0, so test the KEY.
    // Absent means the blob predates the option and the daemon treats it as
    // enabled; an explicit 0 means the administrator switched it off.
    $out["UseStale"]=array_key_exists("UseStale",$json)?intval($json["UseStale"]):1;
    // ForceServerTTL: absent et 0 explicite veulent dire la meme chose
    // (desactive), contrairement a UseStale - pas de test de cle necessaire.
    $out["ForceServerTTL"]=isset($json["ForceServerTTL"])?intval($json["ForceServerTTL"]):0;
    return $out;
}
}

// Optimization summary of a reverse-proxy site, shared by the site parameters
// (fw.nginx.sites.php) and the Optimization window (fw.nginx.sites.optimize.php):
// same six features and same "unavailable" rules as its summary strip.
// Returns array("on"=>[labels of enabled features],"broken"=>[labels enabled but
// not working: module missing or disabled globally]).
if(!function_exists('nopt_summary')){
function nopt_summary($ID):array{
    $s=new socksngix(intval($ID));
    $g=new socksngix(0);
    $out=array("on"=>array(),"broken"=>array());
    $cacheUnavail=false;
    if(intval($g->GET_INFO("nginxCachesDir"))<>1){
        if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_SRCACHE"))==0 || intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedis"))==0){$cacheUnavail=true;}
    }
    $psUnavail=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_installed"))==0 || intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_enabled"))==0;
    $browser=intval($s->GET_INFO("cache_images"))==1 || intval($s->GET_INFO("cache_htmlext"))==1 || intval($s->GET_INFO("cache_binaries"))==1;
    $items=array(
        array("{nopt_server_cache}",intval($s->GET_INFO("cgicache"))==1,$cacheUnavail),
        array("{nopt_adv}",intval($s->GET_INFO("AdvancedCaching"))==1,false),
        array("{nopt_large}",intval($s->GET_INFO("OptimizeForLargeFiles"))==1,false),
        array("{nopt_gzip_short}",intval($s->GET_INFO("gzip"))==1,false),
        array("{nopt_ps_short}",intval($s->GET_INFO("pagespeed"))==1,$psUnavail),
        array("{nopt_browser}",$browser,false),
    );
    foreach($items as $it){
        if(!$it[1]){continue;}
        if($it[2]){$out["broken"][]=$it[0];continue;}
        $out["on"][]=$it[0];
    }
    return $out;
}
}
