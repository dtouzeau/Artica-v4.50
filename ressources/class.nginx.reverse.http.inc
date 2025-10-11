<?php
include_once(dirname(__FILE__)."/class.modsecurity.inc");
include_once(dirname(__FILE__)."/class.nginx.templates.inc");
include_once(dirname(__FILE__)."/class.nginx.reverse.proxy.inc");


function ifFrontEndSSL($SERVICE_ID):bool {
    $nginxsock = new socksngix($SERVICE_ID);
    $ssl_certificate=$nginxsock->GET_INFO("ssl_certificate");
    if($ssl_certificate==null){return false;}

    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");

    $results=$q->QUERY_SQL("SELECT * FROM stream_ports WHERE serviceid='$SERVICE_ID'");
    foreach ($results as $index=>$ligne){
        $options=unserialize(base64_decode($ligne["options"]));
        $ssl=intval($options["ssl"]);
        if($ssl==1){return true;}
    }

    return false;

}

function StringToPRCE($pattern):string{
    $pattern=str_replace("\\","\\\\",$pattern);
    $pattern=str_replace("(","\(",$pattern);
    $pattern=str_replace(")","\)",$pattern);
    $pattern=str_replace("{","\{",$pattern);
    $pattern=str_replace("}","\}",$pattern);
    $pattern=str_replace("[","\[",$pattern);
    $pattern=str_replace("]","\]",$pattern);
    $pattern=str_replace("+","\+",$pattern);
    $pattern=str_replace("^","\^",$pattern);
    $pattern=str_replace(".","\.",$pattern);
    $pattern=str_replace("?","\?",$pattern);
    return str_replace("*",".*?",$pattern);
}
function get_nginx_servicename($ID):string{
    $ID=intval($ID);
    if($ID==0){return "Unknown";}
    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT servicename FROM nginx_services WHERE ID=$ID");
    return strval($ligne["servicename"]);
}
function proxy_upstream_name($SERVICE_ID):string{
    $get_servicename=get_nginx_servicename($SERVICE_ID);
    return "\tset \$proxy_upstream_name \"$get_servicename-$SERVICE_ID\";";
}
function PageSpeedMinimal(){
    $f[]="	pagespeed off;";
    $f[]="	pagespeed FileCachePath '/home/nginx/pagespeed/';";
    @file_put_contents("/etc/nginx/pagespeed.conf",@implode("\n",$f));
}

function http_useragents_directive($SERVICE_ID):string{
    $nginxsock = new socksngix($SERVICE_ID);
    $FilterUserAgents=intval($nginxsock->GET_INFO("FilterUserAgents"));
    if(!is_file("/etc/nginx/pagespeed.conf")){PageSpeedMinimal();}
    if($FilterUserAgents==0){
        return "#\tFilter User-Agent: Disabled";
    }
    $data=unserialize(base64_decode($nginxsock->GET_INFO("FUserAgents")));
    if(!is_array($data)){
        return "#\tFilter User-Agent: Not an array";
    }
    if(count($data)==0){
        return "#\tFilter User-Agent: O rules";
    }

    foreach ($data as $pattern=>$enable){
        if($enable==0){continue;}

        if(strpos($pattern,"~")>0){
            echo "http_useragents_directive:: !!! SKIP User-Agent \"$pattern\"\n";
            continue;
        }
        $pattern=StringToPRCE($pattern);
        $f[]=$pattern;
    }

    if(count($f)==0){
        return "#\tFilter User-Agent: No enabled rule";
    }
    return "\tif (\$http_user_agent ~* \"".@implode("|",$f)."\" ) { return 444; }";

}

function tool_nginx_clean_uri($uri):string{
    $uri=trim(strtolower(strval($uri)));
    if(preg_match("#:\/\/#",$uri)){
        $arrM=parse_url($uri);
        $uri=$arrM["host"];
        if(preg_match("#(.+?):[0-9]+#",$uri,$re)){$uri=$re[1];}
        return $uri;
    }

    if(strpos(" $uri","/")>0){
        $uri="http://$uri";
        $arrM=parse_url($uri);
        $uri=$arrM["host"];
        if(preg_match("#(.+?):[0-9]+#",$uri,$re)){$uri=$re[1];}
        return $uri;
    }

    return $uri;
}

function build_httpreverse_proxy_set_header_Host($SERVICE_ID):string{
    $sock = new socksngix($SERVICE_ID);
    $proxy_d=array();

    $HostHeader = trim($sock->GET_INFO("HostHeader"));
    $HostHeader = tool_nginx_clean_uri($HostHeader);

    $ForwardServersDynamics =   intval($sock->GET_INFO("ForwardServersDynamics"));
    if($ForwardServersDynamics==1) {
        $HostHeader = dynamic_destination_vars($SERVICE_ID);
    }


    if($HostHeader<>null){
        $proxy_d[]="\tproxy_set_header Host $HostHeader;";
        $proxy_d[] = "\tproxy_redirect http://$HostHeader/ /;";
        $proxy_d[] = "\tproxy_redirect https://$HostHeader/ /;";

    }
    if($HostHeader==null){
        $proxy_d[]="\tproxy_set_header Host \$host;";
        $proxy_d[] = "\tproxy_redirect default;";
    }

    return @implode("\n",$proxy_d);
}

function build_httpreverse_sub_filter($SERVICE_ID):string
{
    $NginxHTTPSubModule = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPSubModule"));

    if ($NginxHTTPSubModule == 0) {
        return "#\t Sub Filter not exists on this server.";
    }

    $sock = new socksngix($SERVICE_ID);
    $HostHeaderReplace = intval($sock->GET_INFO("HostHeaderReplace"));
    $ProtoReplaceContent = intval($sock->GET_INFO("ProtoReplaceContent"));
    $HostHeader = trim($sock->GET_INFO("HostHeader"));
    $HostHeader = tool_nginx_clean_uri($HostHeader);

    if ($HostHeaderReplace == 1) {

        $ForwardServersDynamics =   intval($sock->GET_INFO("ForwardServersDynamics"));
        if($ForwardServersDynamics==1){
            $dynavars=dynamic_destination_vars($SERVICE_ID);
            $f_stub[] = "\tsub_filter '$dynavars'  \$host;";

        }

        if ($HostHeader <> null) {
            $f_stub[] = "\tsub_filter '$HostHeader'  \$host;";
        }
    }

    if ($ProtoReplaceContent == 1) {
        $f_stub[] = "\tsub_filter 'http://'  'https://';";
        $f_stub[] = "\tsub_filter 'http%3A%2F%2F' 'https%3A%2F%2F';";
    }

    $sub_filters = unserialize(base64_decode($sock->GET_INFO("sub_filters")));
    if (!is_array($sub_filters)) {
        $sub_filters = array();
    }

    if (count($sub_filters) > 0) {

        foreach ($sub_filters as $num => $ligne) {
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
    }
    if(count($f_stub)==0) {
        return "";
    }


    $f_stub[] = "\tsub_filter_once off;";
    $f_stub[] = "\tsub_filter_types text/xml text/css text/javascript application/x-javascript application/json;";
    return @implode("\n",$f_stub);

}

function build_http_generic_harden($SERVICE_ID):string{
    $sock                   = new socksngix($SERVICE_ID);
    $GenericHarden              = intval($sock->GET_INFO("GenericHarden"));
    if($GenericHarden==0){
        return "#\tGeneric Hardening is not enabled";
    }
    $AllowedProtos = trim($sock->GET_INFO("AllowedProtos"));
    if(strlen($AllowedProtos)<3){$AllowedProtos="GET,HEAD,POST";}
    $f[]="\tlocation ~* \"(127.0.0.1)\" { return 444; }";
    $f[]="\tlocation ~* \"([a-z0-9]{2000})\" { return 444; }";
    $f[]="\tlocation ~* \"(javascript:)(.*)(;)\" { return 444; }";
    $f[]="\tlocation ~* \"base64_encode.*\(\" { return 444; }";
    $f[]="\tlocation ~* \"(GLOBALS|REQUEST)(=|\[|%)\" { return 444; }";
    $f[]="\tlocation ~* \"(<|%3C).*script.*(>|%3)\" { return 444; }";
    $f[]="\tlocation ~ \"(|...|../|~|`|<|>||)\" { return 444; }";
    $f[]="\tlocation ~* \"(boot.ini|etc/passwd|self/environ)\" { return 444; }";
    $f[]="\tlocation ~* \"(thumbs?(_editor|open)?|tim(thumb)?).php\" { return 444; }";
    $f[]="\tlocation ~* \"('|\\\")(.*)(drop|insert|md5|select|union)\" { return 444; }";
    $f[]="\tlocation ~* \"(='|=%27|/'/?).\" { return 444; }";
    $f[]="\tlocation ~ \"(~|`|<|>|:|;|%||s|{|}|[|]||)\" { return 444; }";
    $f[]="\tlocation ~* \"/(=|$&|_mm|(wp-)?config.|cgi-|etc/passwd|muieblack)\" { return 444; }";
    $f[]="\tlocation ~* \"(echo.*?kae|etc/passwd|self/environ)\" { return 444; }";
    $f[]="\tlocation ~* \".(aspx?|bash|bak?|cfg|cgi|dll|exe|git|hg|ini|jsp|log|mdb|out|sql|svn|swp|tar|rdf)$\" { return 444; }";
    $f[]="\tlocation ~* \"/(^$|mobiquo|phpinfo|shell|sqlpatch|thumb|thumb_editor|thumbopen|timthumb|webshell).php\" { return 444; }";
    $f[]="";
    $f[]="\tset \$block_sql_injections 0; if (\$query_string ~ \"union.*select.*\(\") { set \$block_sql_injections 1; }";
    $f[]="\tif (\$query_string ~ \"union.*all.*select.*\") { set \$block_sql_injections 1; }";
    $f[]="\tif (\$query_string ~ \"concat.*\(\") { set \$block_sql_injections 1; }";
    $f[]="\tif (\$block_sql_injections = 1) { return 444; }";
    $f[]="";
    $f[]="\tset \$block_file_injections 0;";
    $f[]="\tif (\$query_string ~ \"[a-zA-Z0-9_]=http://\") { set \$block_file_injections 1; }";
    $f[]="\tif (\$query_string ~ \"[a-zA-Z0-9_]=(..//?)+\") { set \$block_file_injections 1; }";
    $f[]="\tif (\$query_string ~ \"[a-zA-Z0-9_]=/([a-z0-9_.]//?)+\") { set \$block_file_injections 1; }";
    $f[]="\tif (\$block_file_injections = 1) { return 444; }";
    $f[]="";
    $f[]="\tset \$block_common_exploits 0;";
    $f[]="\tif (\$query_string ~ \"(<|%3C).*script.*(>|%3E)\") { set \$block_common_exploits 1; }";
    $f[]="\tif (\$query_string ~ \"GLOBALS(=|[|%[0-9A-Z]{0,2})\") { set \$block_common_exploits 1; }";
    $f[]="\tif (\$query_string ~ \"_REQUEST(=|[|%[0-9A-Z]{0,2})\") { set \$block_common_exploits 1; }";
    $f[]="\tif (\$query_string ~ \"proc/self/environ\") { set \$block_common_exploits 1; }";
    $f[]="\tif (\$query_string ~ \"mosConfig_[a-zA-Z_]{1,21}(=|%3D)\") { set \$block_common_exploits 1; }";
    $f[]="\tif (\$query_string ~ \"base64_(en|de)code(.*)\") { set \$block_common_exploits 1; }";
    $f[]="\tif (\$block_common_exploits = 1) { return 444; }";
    $zAllowedProtos=explode(",",$AllowedProtos);
    $nProto=array();
    $newProto=array();
    foreach ($zAllowedProtos as $proto){
        $nProto[strtoupper($proto)]=true;
    }
    foreach ($nProto as $proto=>$none){
        $newProto[]=$proto;
    }
    if(count($newProto)>0){
        $f[]="\tif (\$request_method !~ ^(".@implode("|",$newProto).")$ ) {";
        $f[]="\t\treturn 444;";
        $f[]="\t}";
    }
    return @implode("\n",$f);
}

function build_httpreverse_proxy_pass($SERVICE_ID):string{
    $sock                   = new socksngix($SERVICE_ID);

    $RemotePath=$sock->GET_INFO("RemotePath");
    if(strlen($RemotePath)<2){$RemotePath=null;}

    if($RemotePath<>null) {
        if (!preg_match("#^\/#", $RemotePath)) {
            $RemotePath = "/$RemotePath";
        }
        if (!preg_match("#\/$#", $RemotePath)) {
            $RemotePath = "$RemotePath/";
        }
    }
    $http_proto="http";
    if(intval($sock->GET_INFO("UseSSL"))==1){$SSL=true;$http_proto="https"; }

    $ForwardServersDynamics =   intval($sock->GET_INFO("ForwardServersDynamics"));
    if($ForwardServersDynamics==1) {
        $dynavars=dynamic_destination_vars($SERVICE_ID);
        return "$http_proto://$dynavars$RemotePath";

    }


    return "$http_proto://backends$SERVICE_ID$RemotePath";



}

function dynamic_destination_vars($SERVICE_ID){
    $sock                   = new socksngix($SERVICE_ID);
    $FSDynamicsExt = intval($sock->GET_INFO("FSDynamicsExt"));
    $FSDynamicsDst = trim($sock->GET_INFO("FSDynamicsDst"));

    if ($FSDynamicsExt == 1) {
        if (preg_match("#\.(.*?)$#", $FSDynamicsDst, $re)) {
            $FSDynamicsDst = str_replace("." . $re[1], ".\$dynaExt", $FSDynamicsDst);
        } else {
            $FSDynamicsDst = $FSDynamicsDst . ".\$dynaExt";
        }
    }

    return "\$dynaPrefix.$FSDynamicsDst";

}

function proxy_buffering($SERVICE_ID):string{
    $ServiceType=GetServiceType($SERVICE_ID);
    if($ServiceType==13){
        $f[] = "#\tNo buffering for ADFS";
        $f[] = "\tproxy_buffering off;";
        return @implode("\n",$f);
    }
    $sock = new socksngix($SERVICE_ID);
    $proxy_buffering = intval($sock->GET_INFO("proxy_buffering"));

    if ($proxy_buffering == 0) {
        $f[] = "\tproxy_buffering off;";
        return @implode("\n",$f);
    }
    $f[] = "\tproxy_buffering on";
    $proxy_buffer_size = intval($sock->GET_INFO("proxy_buffer_size"));
    $proxy_buffers=intval($sock->GET_INFO("proxy_buffers"));
    if($proxy_buffers==0){$proxy_buffers=512;}
    if ($proxy_buffer_size == 0) {
        $proxy_buffer_size = 16;
    }

    $proxy_buffersk=round($proxy_buffers/4);
    $proxy_busy_buffers_size=($proxy_buffer_size+4)*2;

    $f[] = "\tproxy_request_buffering on;";
    $f[] = "\tproxy_buffer_size {$proxy_buffer_size}k;";
    $f[] = "\tproxy_busy_buffers_size {$proxy_busy_buffers_size}k;";
    $f[] = "\tproxy_buffers $proxy_buffersk 4k;";
    return @implode("\n",$f);
}

function GetServiceType($SERVICE_ID):int{
    $SERVICE_ID=intval($SERVICE_ID);
    if($SERVICE_ID==0){return 0;}
    $q= new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT `type` FROM nginx_services WHERE ID=$SERVICE_ID");
    return intval($ligne["type"]);
}

function build_httpreverse($MASTER_LINE,$SERVICE_ID,$DEFAULT_SERVER=false):string{
    if(!is_dir("/etc/nginx/modsecurities")){@mkdir("/etc/nginx/modsecurities",0755,true);}
    $unix                   = new unix();
    $outgoingaddr           = null;
    $AS_SSL                 = ifFrontEndSSL($SERVICE_ID);
    $servicename            = $MASTER_LINE["servicename"];
    $zorder                 = intval($MASTER_LINE["zorder"]);
    $q                      = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $nginxsock              = new socksngix(0);
    $sock                   = new socksngix($SERVICE_ID);
    $DenyAccess             = intval($sock->GET_INFO("DenyAccess"));
    $templs                 = new nginx_templates($SERVICE_ID);
    $Templates              = $templs->build();
    $nginx_service          = new nginx_service();
    $ForwardServersDynamics =   intval($sock->GET_INFO("ForwardServersDynamics"));

    $proxy_cache_valid_default=intval($nginxsock->GET_INFO("proxy_cache_valid_default"));
    if($proxy_cache_valid_default==0){$proxy_cache_valid_default=720;}



    $mainpath="/etc/nginx/reverse.d/$zorder-$SERVICE_ID.conf";
    echo "build_httpreverse: $servicename path........: $mainpath\n";

    $nginx_proxy_path=new nginx_proxy_path($SERVICE_ID,$zorder,0);
    $nginx_proxy_path->build_backends();

    $proxy_http_version=$sock->GET_INFO("proxy_http_version");
    $Redirect80To443=$sock->GET_INFO("Redirect80To443");
    $WebSocketsSupport=$sock->GET_INFO("WebSocketsSupport");
    if($proxy_http_version==null){$proxy_http_version="1.0";}
    $limit_conn=intval($sock->GET_INFO("limit_conn"));
    $proxy_download_rate=intval($sock->GET_INFO("proxy_download_rate"));
    $proxy_upload_rate=intval($sock->GET_INFO("proxy_upload_rate"));
    $proxy_bind=$sock->GET_INFO("proxy_bind");
    $pagespeed=intval($sock->GET_INFO("pagespeed"));
    if($proxy_bind<>null){$outgoingaddr=$unix->InterfaceToIPv4($proxy_bind);}
    $ssl_directives=ssl_directives($SERVICE_ID);

    $CERTIFICATE_ENABLED=$GLOBALS["CERTIFICATE_ENABLED"];
    $cache_erverything = intval($sock->GET_INFO("cache_erverything"));
    $WebCopyEnabled=WebCopyEnabled($SERVICE_ID);
    $rewrite_directives=rewrite_directives($SERVICE_ID);

    if($WebCopyEnabled==1){$rewrite_directives="# WebCopy enabled, did not make sense here";}

    $SSL=false;
    $http_proto="http";
    if(intval($sock->GET_INFO("UseSSL"))==1){$SSL=true;$http_proto="https"; }



    $RemotePath=$sock->GET_INFO("RemotePath");
    if(strlen($RemotePath)<2){$RemotePath=null;}

    if($RemotePath<>null) {
        if (!preg_match("#^\/#", $RemotePath)) {
            $RemotePath = "/$RemotePath";
        }
        if (!preg_match("#\/$#", $RemotePath)) {
            $RemotePath = "$RemotePath/";
        }
    }


    $proxy_pass=build_httpreverse_proxy_pass($SERVICE_ID);
    $f[]=$nginx_proxy_path->build_backends_logs();

    $f[]="#\tRedirect80To443    = $Redirect80To443;";




    $listen_port_directive  = listen_port_directive($SERVICE_ID, $CERTIFICATE_ENABLED,$DEFAULT_SERVER);
    $stubmodule             = build_httpreverse_sub_filter($SERVICE_ID);
    $global_redirect_uri    = global_redirect_uri($SERVICE_ID);
    $gzip_directives        = gzip_directives($SERVICE_ID);
    $headers_security       = headers_security($SERVICE_ID);
    $buildservernames       = $nginx_service->buildservernames($MASTER_LINE);
    $buildRedirects         = $nginx_service->buildRedirectsServerNames($MASTER_LINE);

    if(count($buildRedirects)>0){
        foreach ($buildRedirects as $domain => $redirects){
            $f[] = "server {";
            $f[] = "\tserver_name $domain;";
            $f[] = $listen_port_directive;
            $f[] = $ssl_directives;
            $f[] = "\treturn 302 \$scheme://$redirects\$request_uri;";
            $f[] = "}";
            $f[] = "";
        }
    }

    if($Redirect80To443==1){
        $f[]="#\tReturn a 301 to https";
        $default80=null;
        if($DEFAULT_SERVER){$default80=" default_server";}
        $f[] = "server {";
        $f[] = listen_port_force_http($SERVICE_ID,$DEFAULT_SERVER);
        $f[] = $buildservernames;
        $f[] = $global_redirect_uri;
        $f[] = $Templates;
        if($DenyAccess==1){ $f[] = "\tdeny all;";}
        $f[]="\tadd_header X-service-id $SERVICE_ID;";
        $f[]="#\t---------------------------------------------------";
        $f[]="\treturn 301 https://\$host\$request_uri;";
        $f[]="#\t---------------------------------------------------";
        $f[]="}\n\n\n";
    }
    include_once(dirname(__FILE__)."/class.modsecurity.inc");
    $mod_security=new mod_security($SERVICE_ID);
    $mod_security->build_nginx_configuration($SERVICE_ID);



    $f[]="server {";
    //$f[]="error_log /var/log/nginx/$SERVICE_ID.debug debug;";
    $f[]="\tinclude /etc/nginx/modsecurities/nginx-$SERVICE_ID.conf;";
    $f[]=proxy_upstream_name($SERVICE_ID);
    $f[]=$listen_port_directive;
    $f[]=$buildservernames;
    $f[]=http_useragents_directive($SERVICE_ID);
    if($limit_conn>0){
        $f[]="\tlimit_conn\taddr {$limit_conn};";
        $f[]="\tlimit_conn_log_level error;";
    }
    $f[]=   $global_redirect_uri;
    $f[] =  $Templates;
    if($DenyAccess==1){$f[] = "\tdeny all;";}
    if($pagespeed==1){
        $f[]=x_nginx_pagespeed($SERVICE_ID);
    }


    $f[] = build_http_generic_harden($SERVICE_ID);
    $f[] = proxy_buffering($SERVICE_ID);
    $f[] = cache_objects($SERVICE_ID);
    $f[] = default_paths($SERVICE_ID);

    if($proxy_download_rate>0){$f[]="\tproxy_download_rate {$proxy_download_rate}k;";}
    if($proxy_upload_rate>0){$f[]="\tproxy_upload_rate {$proxy_upload_rate}k;";}
    if($outgoingaddr<>null){$f[]="\tproxy_bind $outgoingaddr;";}
    $f[]="#----------------------- ACCESS RULES";
    if($gzip_directives<>null){ $f[]= $gzip_directives;}
    if($headers_security<>null){$f[]= $headers_security;}
    $f[]=ngx_stream_access_module($SERVICE_ID);
    $f[]="#";
    $f[]="#";
    $f[]="#----------------------- Rewrite Directives";
    $f[]=$rewrite_directives;
    $f[]="#";
    $f[]="#----------------------- SSL Directives";
    $f[]=$ssl_directives;
    if(!is_dir("/etc/nginx/caches")){@mkdir("/etc/nginx/caches",0755,true);}


    $header_directives=header_directives($SERVICE_ID);

    if($header_directives<>null){
        $proxy_d[]=$header_directives;
    }else{
        $f[]="#\t----------------------- Headers directives = null.";
    }
    $proxy_d[]=redis_cache($SERVICE_ID);
    $proxy_d[]="\tproxy_pass $proxy_pass;";
    $proxy_d[]="\tproxy_http_version $proxy_http_version;";
    $proxy_d[]="\tproxy_set_header Connection \"\";";
    if($WebSocketsSupport==1) {
        $proxy_d[] = "\tproxy_set_header Upgrade \$http_upgrade;";
        $proxy_d[] = "\tproxy_set_header Connection \$connection_upgrade;";
    }

    $proxy_d[]=build_httpreverse_proxy_set_header_Host($SERVICE_ID);
    $proxy_d[]="\tproxy_set_header X-Real-IP \$remote_addr;";
    $proxy_d[]="\tproxy_set_header X-Forwarded-For \$remote_addr;";
    $proxy_d[]="\tproxy_set_header Accept-Encoding \"\";";



    if($SSL) {
        $proxy_d[] = "\tproxy_set_header X-Forwarded-Proto https;";
    }


    $f[]="";
    $f[]="";

    $proxy_block=@implode("\n",$proxy_d);


//-------------------------------------------------------------------------------------------------
    $results=$q->QUERY_SQL("SELECT * FROM ngx_directories WHERE serviceid='$SERVICE_ID' AND enabled=1 ORDER BY directory");
    $CountOfDirectories=count($results);
    $f[]="";
    $f[]="# Count of Locations: $CountOfDirectories";
    foreach ($results as $index=>$ligneDir){
        $Directory      = trim($ligneDir["directory"]);
        if($Directory=="/"){
            $f[]="#\t$Directory not allowed";
            continue;
        }
        $DirectoryID    = $ligneDir["ID"];
        $deny           = intval($ligneDir["deny"]);
        $options        = unserialize(base64_decode($ligneDir["options"]));
        if($deny==1){$item_prefix="allow";}else{$item_prefix="deny";}
        if(!isset($options["REDIRECT_HTTP"])){$options["REDIRECT_HTTP"]=null; }
        if(!preg_match("#^(http|https):\/\/#i",$options["REDIRECT_HTTP"])){ $options["REDIRECT_HTTP"]=null; }
        $signe="";
        $regex=false;
        if(substr($Directory,0,1)=="~"){
            $regex=true;
        }

        if(!$regex) {
            if (preg_match("#(\(|\)|\|\[|\]|\.\*|\?)#", $Directory)) {
                $regex = true;
                $Directory = "~ $Directory";
            }
        }

        if(!$regex) {
            if(substr($Directory,0,1)<>"/"){
                $Directory="/$Directory";
            }
        }

        if(preg_match("#\.([a-z]+)$#",$Directory)){
            $signe=" =";
        }

        $f[]="\tlocation$signe $Directory {";
        $f[]="#\t\tDirectoryID:$SERVICE_ID:$DirectoryID (redirect={$options["REDIRECT_HTTP"]})";
        $redirect=trim(parse_redirect($Directory,$options["REDIRECT_HTTP"]));
        $resultsItems=$q->QUERY_SQL("SELECT * FROM ngx_subdir_items WHERE directoryid='$DirectoryID' ORDER BY zorder");
        foreach ($resultsItems as $index=>$ligneItems){
                if($ligneItems["item"]=="*"){$ligneItems["item"]="all";}
                $f[]="\t\t$item_prefix {$ligneItems["item"]};";
            }
            if($deny==1){
                $f[]="\t\tdeny all;";}
            else{
                $f[]="\t\tallow all;";
            }
            if($redirect<>null){
                $f[]=$redirect;
                $f[]="\t}";
                continue;
            }
            if(substr($options["REDIRECT_HTTP"],0,1)=="/"){
                $f[]="alias ".$options["REDIRECT_HTTP"].";";
                $f[]="\t}";
                continue;
            }


            //$f[]="\tinclude /etc/nginx/caches/$SERVICE_ID.conf;";
            $DirectoriesProxy=$nginx_proxy_path->DirectoriesProxy($DirectoryID);
            $f[]="#\t\tDirectories proxy = ".strlen($DirectoriesProxy)." bytes lenght";
            $f[]=$nginx_proxy_path->DirectoriesSubfilters($DirectoryID);

            if(strlen($DirectoriesProxy)<5){
                $f[]="\t\tinclude /etc/nginx/root-directives/$SERVICE_ID.conf;";
            }else{
                $f[]=$stubmodule;
                $f[]=$DirectoriesProxy;
            }
        $f[]="\t}\n\n";
    }
//-------------------------------------------------------------------------------------------------
    $WebCopyExcludes=null;
    $RootDirectives="$stubmodule\n$proxy_block";

    if($WebCopyEnabled==1) {
        $WebCopyRoot = WebCopySettings($SERVICE_ID);
        if ($WebCopyRoot <> null) {
            $RootDirectives = $WebCopyRoot;
            $WebCopyExcludes=WebCopyExcludes($SERVICE_ID,$proxy_block);
        }else{
            $f[]="# WebCopyRoot is null";
            if(isset($GLOBALS["WebCopyRoot"])){
                $f[]=@implode("\n",$GLOBALS["WebCopyRoot"]);
            }
        }
    }


    $f[]="# WebCopy Enabled = $WebCopyEnabled";
    if($WebCopyExcludes<>null){
        $f[]="# WebCopy Exclusions";
        $f[]=$WebCopyExcludes;
    }

    @file_put_contents("/etc/nginx/root-directives/$SERVICE_ID.conf","$RootDirectives\n");

    $f[]= "\tlocation / {";
    $f[]= "\tinclude /etc/nginx/root-directives/$SERVICE_ID.conf;";
    $f[]= "\tinclude /etc/nginx/modsecurities/locations-$SERVICE_ID.conf;";
    $f[]= "\t}";
    $f[] = "";

    $f[]= "}";

    @file_put_contents($mainpath, @implode("\n", $f));
    return @implode("\n", $f);


}
function redis_pwd():string{

    $NginxCacheRedisLocal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisLocal"));
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));
    if($EnableRedisServer==0){$NginxCacheRedisLocal=0;}
    if($NginxCacheRedisLocal==1){
        $RedisPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisPassword"));
        if ($RedisPassword<>null){
            return "\t\tredis2_query auth $RedisPassword;";
        }
        return "";
    }
    $NginxCacheRedisPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisPassword"));
    if ($NginxCacheRedisPassword<>null){
        return "\t\tredis2_query auth $NginxCacheRedisPassword;";
    }
    return "";
}
function redis_pass():string{
    $NginxCacheRedisHost=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisHost"));
    $NginxCacheRedisPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisPort"));
    if($NginxCacheRedisPort==0){$NginxCacheRedisPort=6379;}
    $NginxCacheRedisLocal=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedisLocal"));
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));
    if($EnableRedisServer==0){$NginxCacheRedisLocal=0;}

    if($NginxCacheRedisLocal==1){
        $RedisBindInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisBindInterface");
        $RedisBindPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RedisBindPort"));
        if($RedisBindPort==0){$RedisBindPort=6379;}
        if($RedisBindInterface==null){$RedisBindInterface="lo";}
        if($RedisBindInterface=="lo"){
            $NginxCacheRedisHost="127.0.0.1";
        }else{
            $nic=new system_nic($RedisBindInterface);
            $NginxCacheRedisHost=$nic->IPADDR;
        }
        return "$NginxCacheRedisHost:$RedisBindPort";
    }

    return "$NginxCacheRedisHost:$NginxCacheRedisPort";

}
function redis_virtual_paths($SERVICE_ID){
    if(!redis_isCacheEnabled($SERVICE_ID)){return $GLOBALS["NGINX_PHP_CACHE_STATUS"];}
    $NginxHTTPModSecurity   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPModSecurity"));
    $srcache_redis_pwd=redis_pwd();
    $f[]="\tlocation /redis-fetch {";
    $f[]="\t\tinternal;";
    if($NginxHTTPModSecurity==1){
        $f[]="modsecurity off;";
    }
    $f[]="\t\tset  \$redis_key \$args;";
    $f[]=$srcache_redis_pwd;

    $socknginx=new socksngix($SERVICE_ID);
    $proxy_cache_valid=intval($socknginx->GET_INFO("proxy_cache_valid"));
    if($proxy_cache_valid==0){$proxy_cache_valid=4320;}
    $exptime=$proxy_cache_valid*60;

    $redis_pass=redis_pass();
    $f[]="\t\tredis_pass  $redis_pass;";
    $f[]="\t}";
    $f[]="";
    $f[]="\tlocation /redis-store {";
    $f[]="\t\tinternal;";
    if($NginxHTTPModSecurity==1){
        $f[]="modsecurity off;";
    }
    $f[]="\t\tset_unescape_uri \$key \$arg_key ;";
    $f[]="\t\tredis2_query  set \$key \$echo_request_body;";
    $f[]=$srcache_redis_pwd;
    $f[]="\t\tredis2_query expire \$key $exptime;";
    $f[]="\t\tredis2_pass  $redis_pass;";
    $f[]="\t}";
    return @implode("\n",$f);
}
function redis_isCacheEnabled($SERVICE_ID):bool{
    if(intval($SERVICE_ID)==0){return false;}
    $APP_NGINX_SRCACHE=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_NGINX_SRCACHE"));
    if($APP_NGINX_SRCACHE==0){
        $GLOBALS["NGINX_PHP_CACHE_STATUS"]="#\t\tCaching Not implemented module;";
        return false;
    }
    $NginxCacheRedis=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedis"));
    if($NginxCacheRedis==0){
        $GLOBALS["NGINX_PHP_CACHE_STATUS"]="#\t\tCaching with redist is disabled;";
        return false;
    }

    $socknginx=new socksngix($SERVICE_ID);

    if(intval($socknginx->GET_INFO("cgicache"))==0){
        $GLOBALS["NGINX_PHP_CACHE_STATUS"]="#\t\tCaching with redist is enabled but not for this site;";
        return false;
    }
    $GLOBALS["NGINX_PHP_CACHE_STATUS"]="";
    return true;
}
function redis_cache($SERVICE_ID){
    if(isset($GLOBALS["REDIS_CACHE"][$SERVICE_ID])){return $GLOBALS["REDIS_CACHE"][$SERVICE_ID];}
    if(!redis_isCacheEnabled($SERVICE_ID)){
        $GLOBALS["REDIS_CACHE"][$SERVICE_ID]="#".$GLOBALS["NGINX_PHP_CACHE_STATUS"];
        return "#".$GLOBALS["NGINX_PHP_CACHE_STATUS"];
    }

    $f[] = "\tset \$skip_cache 0;";
    $f[] = "\tset \$key \"nginxcache$SERVICE_ID\$request_method\$request_uri\";";
    $f[] = "\tset_escape_uri \$escaped_key \$key;";
    $f[] = "\tsrcache_fetch_skip \$skip_cache;";
    $f[] = "\tsrcache_store_skip \$skip_cache;";
    $f[] = "\tsrcache_response_cache_control off;";
    $f[] = "\tsrcache_store_statuses 200 201 206 304 308;";
    $f[] = "\tmore_set_headers 'X-Cache-Fetch-Status \$srcache_fetch_status';";
    $f[] = "\tmore_set_headers 'X-Cache-Store-Status \$srcache_store_status';";
    $f[] = "\tsrcache_fetch GET /redis-fetch \$key;";
    $f[] = "\tsrcache_store PUT /redis-store key=\$escaped_key;";
    $GLOBALS["REDIS_CACHE"][$SERVICE_ID]= @implode("\n",$f);
    return $GLOBALS["REDIS_CACHE"][$SERVICE_ID];

}

function parse_redirect($Directory,$REDIRECT_HTTP=null):string{
    if($REDIRECT_HTTP==null){return "";}
    if(preg_match("#(.+?)\/\*$#",$REDIRECT_HTTP,$re)){
        $REDIRECT_HTTP=$re[1];
        if(preg_match("#^(http|https):\/\/#",$REDIRECT_HTTP)) {
            return "\treturn 302 $REDIRECT_HTTP/\$request_uri;";
        }
        return "\trewrite ^$Directory(.*)$ $REDIRECT_HTTP/$1 redirect;";
    }

    if(preg_match("#^(.+?)==(.+)#",$REDIRECT_HTTP)) {
        $first=trim($re[1]);
        $second=trim($re[2]);
        return "\trewrite ^$first $second redirect;";
    }
    if(preg_match("#^(http|https):\/\/#",$REDIRECT_HTTP)) {
        return "\treturn 302 $REDIRECT_HTTP;";
    }

    return "\trewrite ^$Directory $REDIRECT_HTTP redirect;";


}

function  WebCopyExcludes($SERVICE_ID,$reverseProxy):string{
    $q              = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $tt=array();
    if(!$q->FIELD_EXISTS("httrack_sites","exclude")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD exclude TEXT NULL");
    }

    $WebCopy=$q->mysqli_fetch_array("SELECT exclude FROM httrack_sites WHERE serviceid=$SERVICE_ID");
    $exclude=$WebCopy["exclude"];
    if($exclude==null){$exclude="*.gz,*.zip,*.exe,*.iso,*.nrg,*.pdf";}
    $excludes=explode(",",$exclude);
    foreach ($excludes as $ext){
        $ext=str_replace("*.","",$ext);
        $ext=str_replace(".","\.",$ext);
        $tt[]=$ext;
    }


    $f[]="\tlocation ~* ^.+\.(".@implode("|",$tt).")($|\?) {";
    $f[]=$reverseProxy;
    $f[]="\t}";
    return @implode("\n",$f);
}

function  WebCopySettings($SERVICE_ID):string{
    $WebCopyRoot=WebCopyRoot($SERVICE_ID);
    if($WebCopyRoot==null){return "";}
    $f[]="\troot $WebCopyRoot;";
    $f[]="\tindex index.html;";
    $f[] = "\tsub_filter_once off;";
    $f[] = "\tsub_filter '.php'  '.html';";
    return @implode("\n",$f);
}

function WebCopyRoot($SERVICE_ID):string{
    $workingdir="/home/artica/WebCopy/$SERVICE_ID";
    $handle = opendir($workingdir);
    if(!$handle){
        $GLOBALS["WebCopyRoot"][]="# cannot handle $workingdir";
        return "";
    }
    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        $path="$workingdir/$filename";
        if(is_file($path)){continue;}
        $index="$path/index.html";
        if(is_file($index)){return $path;}
    }
    $GLOBALS["WebCopyRoot"][]="# cannot find index.html";
    return "";
}

function WebCopyEnabled($SERVICE_ID):int{
    if(intval($SERVICE_ID)==0){return 0;}
    $q              = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $WebCopy=$q->mysqli_fetch_array("SELECT * FROM httrack_sites WHERE serviceid=$SERVICE_ID");
    $WebCopyEnabled=intval($WebCopy["enabled"]);
    $workingdir="/home/artica/WebCopy/$SERVICE_ID";
    if(!is_file($workingdir."/EXEC")){return 0;}
    return $WebCopyEnabled;
}

function global_redirect_uri($SERVICE_ID):string{
    if(intval($SERVICE_ID)==0){return "";}
    $nginxsock = new socksngix($SERVICE_ID);
    $RedirectQueries=trim($nginxsock->GET_INFO("RedirectQueries"));
    if(trim($RedirectQueries)==null){return "#\tNo global redirect";}

    if(!preg_match("#^http#",$RedirectQueries)){
        if(strpos($RedirectQueries,"/")==0){$RedirectQueries="$RedirectQueries/\$request_uri";}
        return "\treturn 301 \$scheme://$RedirectQueries;";
    }

    $url=parse_url($RedirectQueries);
    if(!isset($url["scheme"])){$url["scheme"]="\$scheme";}
    if(!isset($url["path"])){$url["path"]="\$request_uri";}
    if($url["path"]==null){$url["path"]="\$request_uri";}
    if($url["scheme"]==null){$url["scheme"]="\$scheme";}
    if(!isset($url["host"])){return "#\twrong pattern $url";}
    if(!isset($url["port"])){$url["port"]=null;}
    if($url["port"]<>null){$url["port"]=":{$url["port"]}";}
    return "\treturn 301 {$url["scheme"]}://{$url["host"]}{$url["port"]}{$url["path"]};";

}

function header_directives($SERVICE_ID):string{
    if(intval($SERVICE_ID)==0){
        return "# header_directives no service id defined.";
    }
    $nginxsock = new socksngix($SERVICE_ID);
    $proxy_hide_headers=array();
    $header_rules=array();
    $proxy_hide_headers_data=$nginxsock->GET_INFO("proxy_hide_headers");
    $header_rules_data=$nginxsock->GET_INFO("header_rules");
    $f=array();
    $intro=array();

    if($proxy_hide_headers_data<>null){$proxy_hide_headers=unserialize(base64_decode($proxy_hide_headers_data));}
    if($header_rules_data<>null){$header_rules=unserialize(base64_decode($header_rules_data));}
    $intro[]="#\t Proxy Hide headers ".count($proxy_hide_headers)." items";
    foreach ($proxy_hide_headers as $num=>$ligne) {
        $enable = intval($ligne["enable"]);
        $header = trim($ligne["header"]);
        if ($enable == 0) {continue;}
        if ($header == null) {continue;}
        $description = trim($ligne["description"]);
        $description = str_replace("\n", " ", $description);
        if ($description <> null) {
            $intro[] = "#\t$description ($num)";
        }
        $f[] = "\tproxy_hide_header '$header';";
    }


    foreach ($header_rules as $num=>$ligne) {
        $enable = intval($ligne["enable"]);
        $header = trim($ligne["header"]);
        if($enable==0){continue;}
        if($header==null){continue;}
        $header_value = trim($ligne["header_value"]);
        $header_value=str_replace('"',"'",$header_value);

        $description = trim($ligne["description"]);
        $description = str_replace("\n"," ",$description);
        if($description<>null){
            $intro[]="#\t$description ($num)";
        }
        if($header_value=="REMOVE"){
            $f[]="\tmore_clear_headers '$header';";
            continue;
        }

        $f[]="\tadd_header $header\t\"$header_value\" always;";
    }
    $f[]="\tadd_header X-service-id $SERVICE_ID;";
    if(count($f)==0){return "";}
    return @implode("\n",$intro)."\n".@implode("\n",$f);
}

function rewrite_directives($SERVICE_ID):string{
    if(intval($SERVICE_ID)==0){return "";}
    $nginxsock = new socksngix($SERVICE_ID);
    $default_page       = trim($nginxsock->GET_INFO("default_page"));
    $data=unserialize(base64_decode($nginxsock->GET_INFO("rewrite_rules")));
    if(!is_array($data)){$data=array();}
    $f[]="#\trewrite directives ".count($data)." items";
    if($default_page<>null){
        $default_page = ltrim($default_page, '/');
        $f[]="\trewrite ^/$ /$default_page permanent;";
    }

    if(count($data)==0){return @implode("\n",$f);}
    $f=array();
    $intro=array();
    foreach ($data as $num=>$ligne) {
        $enable = intval($ligne["enable"]);
        $pattern = trim($ligne["pattern"]);
        $replace = trim($ligne["replace"]);
        $description = trim($ligne["description"]);
        if($enable==0){continue;}
        if($pattern==null){continue;}
        if($replace==null){$replace="^";}
        if($replace=="*"){$replace="^";}

        $flag=trim($ligne["flag"]);
        if($flag<>null){$flag="\t$flag";}

        if($description<>null){
            $intro[]="#\t$description";
        }
        //$replace = requests
        // Pattern = destination
        $f[]="\trewrite $replace\t$pattern{$flag};";
    }
    if(count($f)==0){return "";}
    return @implode("\n",$intro)."\n".@implode("\n",$f);
}

function headers_security($SERVICE_ID):string{
    $nginxsock = new socksngix($SERVICE_ID);
    $RestrictIFrames=intval($nginxsock->GET_INFO("RestrictIFrames"));
    $XSSBrowser=intval($nginxsock->GET_INFO("XSSBrowser"));
    $ReferrerPolicy=intval($nginxsock->GET_INFO("ReferrerPolicy"));
    $EnableCSP=intval($nginxsock->GET_INFO("EnableCSP"));

    $f=array();

    $f[]="\tadd_header X-Content-Type-Options nosniff;";


    if($RestrictIFrames==1){
        $f[]="\tadd_header X-Frame-Options SAMEORIGIN;";
    }
    if($XSSBrowser==1){
        $f[]="\tadd_header X-XSS-Protection \"1; mode=block\";";
    }

    if($ReferrerPolicy==1) {
        $f[] = "\tmore_set_headers \"Referrer-Policy : strict-origin-when-cross-origin\";";
    }

    if($EnableCSP==1) {
       $f[]="#\t ---------------- Content Security Policy ----------------";
       $f[]=build_csp($SERVICE_ID);
    }else{
        $f[]="#\t ---------------- Content Security Policy OFF ----------------";
    }

    return @implode("\n",$f);

}
    function build_csp($SERVICE_ID):string{
        $nginxsock = new socksngix($SERVICE_ID);
        $data=unserialize(base64_decode($nginxsock->GET_INFO("csp_rules")));

        if(!is_array($data)){
            $data=array();
        }
        if(count($data)==0){
            $csp["default-src"][]="self";
            $csp["default-src"][]="unsafe-inline";
            $csp["frame-ancestors"][]="self";
            $csp["form-action"][]="self";
            $csp["font-src"][]="self";
            $csp["font-src"][]="fonts.gstatic.com";
            $csp["style-src"][]="self";
            $csp["style-src"][]="fonts.googleapis.com";
            $csp["script-src"][]="self";
            $csp["script-src"][]="https://www.googletagmanager.com";
            $csp["img-src"][]="self";
            $csp["img-src"][]="www.googletagmanager.com";
            foreach ($csp as $type=>$array){
                foreach ($array as $index=>$line){
                    $ruleid=time()+rand(0,5)+$index;
                    $data[$ruleid]["type"]=$type;
                    $data[$ruleid]["value"]=$line;
                    $data[$ruleid]["enable"]=1;
                }

            }
        }
        $CSP=array();
        foreach ($data as $num=>$ligne) {
            $enable = intval($ligne["enable"]);
            if($enable==0){continue;}
            $value = trim($ligne["value"]);
            $type = $ligne["type"];
            if($value=="self"){$value="'self'";}
            if($value=="none"){$value="'none'";}
            if($value=="unsafe-inline"){$value="'unsafe-inline'";}
            if($value=="unsafe-eval"){$value="'unsafe-eval'";}
            if($value=="script"){$value="'script'";}
            if($value=="unsafe-allow-redirects"){$value="'unsafe-allow-redirects'";}
            if($value=="unsafe-hashes"){$value="'unsafe-hashes'";}
            if($value=="report-sample"){$value="'report-sample'";}
            $CSP[$type][]=$value;
        }

        if (count($CSP)==0){
            return "";
        }
        $csp=array();
        foreach ($CSP as $type=>$array){
            if(count($array)==0){continue;}
            $csp[]="$type ".@implode(" ",$array);

        }

        if(count($csp)==0){return "";}
        $csps=@implode("; ",$csp);
       return "\tmore_set_headers \"Content-Security-Policy : $csps\";";
    }


function http_modsecurity_status():bool{


    $NginxHTTPModSecurity   = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxHTTPModSecurity"));
    $EnableModSecurityIngix = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableModSecurityIngix"));
    if($NginxHTTPModSecurity==0){return false;}
    if($EnableModSecurityIngix==0){return false;}

    return true;
}


function default_paths($SERVICE_ID=0):string{
    $f[]="";
    $f[]="\tlocation ^~ /.well-known/acme-challenge/ {";
    $f[]="\t\tdefault_type \"text/plain\";";
    $f[]="\t\tproxy_set_header Accept-Encoding \"\";";
    $f[]="\t\tproxy_set_header X-Real-IP \$remote_addr;";
    $f[]="\t\tproxy_set_header X-Forwarded-For \$remote_addr;";
    $f[]="\t\tproxy_pass http://127.0.0.1:9554;";
    $f[]="\t}";
    $f[]="";
    $f[]=redis_virtual_paths($SERVICE_ID);
    return @implode("\n",$f);
}

function x_nginx_pagespeed_global():bool{
    $f=explode("\n",@file_get_contents("/etc/nginx/nginx.conf"));
    foreach ($f as $line){
        if(preg_match("#^include\s+.*?pagespeed\.conf#",trim($line))){
            return true;
        }
    }
    return false;
}

function x_nginx_pagespeed($SERVICE_ID=0){
    $main_file_path="/etc/nginx/pagespeed/$SERVICE_ID.conf";
    if(is_file($main_file_path)){@unlink($main_file_path);}
    $nginx_pagespeed_installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_installed"));
    if($nginx_pagespeed_installed==0){return "# Pagespeed not installed";}
    $nginx_pagespeed_enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginx_pagespeed_enabled"));
    if($nginx_pagespeed_enabled==0){return "# Pagespeed not enabled";}
    if($SERVICE_ID==0){return "";}

    if(!is_dir("/etc/nginx/pagespeed")){
        @mkdir("/etc/nginx/pagespeed",0755,true);
    }
    @chown("www-data","/etc/nginx/pagespeed");

    if(!x_nginx_pagespeed_global()){
        $unix=new unix();
        $php=$unix->LOCATE_PHP5_BIN();
        shell_exec("$php /usr/share/artica-postfix/exec.nginx.php --restart-build");
        if(!x_nginx_pagespeed_global()){
            return "# Unable to set main configuration...!\n";
        }
    }


    $f=array();
    $f[]="\tlocation ~ \"\.pagespeed\.([a-z]\.)?[a-z]{2}\.[^.]{10}\.[^.]+\" { add_header \"\" \"\"; }";
    $f[]="\tlocation ~ \"^/pagespeed_static/\" { }";
    $f[]="\tlocation ~ \"^/ngx_pagespeed_beacon$\" { }";
    $f[]="\tlocation /ngx_pagespeed_statistics { allow 127.0.0.1; deny all; }";
    $f[]="\tlocation /ngx_pagespeed_global_statistics { allow 127.0.0.1; deny all; }";
    $f[]="\tlocation /ngx_pagespeed_message { allow 127.0.0.1; deny all; }";
    $f[]="\tlocation /pagespeed_console { allow 127.0.0.1; deny all; }";
    $f[]="\tlocation ~ ^/pagespeed_admin { allow 127.0.0.1; deny all; }";
    $f[]="\tlocation ~ ^/pagespeed_global_admin { allow 127.0.0.1; deny all; }";


    $f[]="	### PageSpeed Settings";
    $f[]="	pagespeed on;";
    $f[]="	pagespeed XHeaderValue \"Optimized Artica\";";
    $f[]="	pagespeed RewriteLevel OptimizeForBandwidth;";
    $f[]="	pagespeed AllowVaryOn Auto;";
    $f[]="	pagespeed DisableRewriteOnNoTransform off;";
    $f[]="	pagespeed InPlaceResourceOptimization on;";
    $f[]="	pagespeed FileCacheCleanIntervalMs 60000;";
    $f[]="	pagespeed ForceCaching on;";
    $f[]="";
    $f[]="	### PageSpeed Compression";
    $f[]="	pagespeed HttpCacheCompressionLevel 6;";
    $f[]="";
    $f[]="	### PageSpeed Images";
    $f[]="	pagespeed EnableFilters dedup_inlined_images;";
    $f[]="	pagespeed EnableFilters lazyload_images;";
    $f[]="	pagespeed EnableFilters inline_preview_images;";
    $f[]="	pagespeed EnableFilters resize_mobile_images;";
    $f[]="	pagespeed EnableFilters responsive_images;";
    $f[]="	pagespeed EnableFilters responsive_images_zoom;";
    $f[]="	pagespeed EnableFilters convert_jpeg_to_webp;";
    $f[]="	pagespeed EnableFilters convert_to_webp_lossless;";
    $f[]="	pagespeed EnableFilters inline_images;";
    $f[]="	pagespeed ImageRecompressionQuality 75;";
    $f[]="	pagespeed ImageResolutionLimitBytes 32000000;";
    $f[]="";
    $f[]="	### PageSpeed Html";
    $f[]="	pagespeed EnableFilters insert_dns_prefetch;";
    $f[]="	pagespeed EnableFilters make_show_ads_async;";
    $f[]="	pagespeed EnableFilters make_google_analytics_async;";
    $f[]="	pagespeed EnableFilters collapse_whitespace;";
    $f[]="	pagespeed EnableFilters remove_comments;";
    $f[]="	pagespeed EnableFilters inline_google_font_css;";
    $f[]="	pagespeed EnableFilters remove_quotes;";
    $f[]="	pagespeed EnableFilters combine_javascript;";
    $f[]="	pagespeed EnableFilters canonicalize_javascript_libraries;";


    if(redis_isCacheEnabled($SERVICE_ID)){
        $redis_pass=redis_pass();
        $srcache_redis_pwd=redis_pwd();
        if($srcache_redis_pwd==null) {
            $f[] = "	pagespeed RedisServer \"$redis_pass\";";
        }
        //pagespeed RedisDatabaseIndex index;

    }

   // $f[]="	pagespeed LoglevelHtml warn;";
   // $f[]="	pagespeed LoglevelImg warn;";


    @file_put_contents($main_file_path,@implode("\n",$f));
    @chown("www-data",$main_file_path);
    return "include $main_file_path;";

}

function gzip_directives($SERVICE_ID):string{
    $nginxsock = new socksngix($SERVICE_ID);
    $gzip               = intval($nginxsock->GET_INFO("gzip"));

    if($gzip==0){
        $t_gz[]="\tgzip off;";
        return @implode("\n",$t_gz);
    }
    $gzip_types_data    = base64_decode($nginxsock->GET_INFO("gzip_types"));

    if($gzip_types_data==null) {
        $t_gz[] = "\tgzip off;";
        return @implode("\n", $t_gz);
    }
    $gzip_types_rules=unserialize($gzip_types_data);
    if(count($gzip_types_rules)==0){
        $t_gz[] = "\tgzip off;";
        return @implode("\n", $t_gz);
    }
    $gzip_medium_rules=array();
    foreach ($gzip_types_rules as $id=>$ligne){
        $enable=intval($ligne["enable"]);
        if($enable==0){continue;}
        $pattern=trim($ligne["mime"]);
        if($pattern==null){continue;}
        $gzip_medium_rules[$pattern]=$pattern;
    }
    $gzip_final_rules=array();
    foreach ($gzip_medium_rules as $rr=>$none){
        $gzip_final_rules[]=$rr;
    }
    if(count($gzip_final_rules)==0){
        $t_gz[] = "\tgzip off;";
        return @implode("\n", $t_gz);
    }

    $gzip_vary          = intval($nginxsock->GET_INFO("gzip_vary"));
    $gzip_comp_level    = intval($nginxsock->GET_INFO("gzip_comp_level"));
    $gzip_min_length    = intval($nginxsock->GET_INFO("gzip_min_length"));

    if($gzip_comp_level==0){$gzip_comp_level=6;}
    if($gzip_min_length==0){$gzip_min_length=50;}

    $t_gz[]="\tgzip on;";
    if($gzip_vary==1){
        $t_gz[]="\tgzip_vary on;";
    }else{
        $t_gz[]="\tgzip_vary off;";
    }

    $t_gz[]="\tgzip_min_length $gzip_min_length;";
    $t_gz[]="\tgzip_proxied any;";
    $t_gz[]="\tgzip_comp_level $gzip_comp_level;";
    $t_gz[]="\tgzip_buffers 4 32k;";
    $t_gz[]="\tgzip_http_version 1.0;";
    $t_gz[]="\tgzip_types ".@implode(" ",$gzip_final_rules).";";
    $t_gz[]="\tgzip_disable \"MSIE [1-6]\.\";";
    return @implode("\n", $t_gz);

}

function cache_objects($SERVICE_ID):string{
    $NginxCacheRedis=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxCacheRedis"));
    if($NginxCacheRedis==0){return "#\tCahing via redis disabled";}


    $nginxsock=new socksngix($SERVICE_ID);
    $cgicache=intval($nginxsock->GET_INFO("cgicache"));
    if($cgicache==0){
        return "#\tThis website is not enabled for caching";
    }

    $cache_images = intval($nginxsock->GET_INFO("cache_images"));
    $cache_htmlext = intval($nginxsock->GET_INFO("cache_htmlext"));
    $cache_binaries = intval($nginxsock->GET_INFO("cache_binaries"));

    if (($cache_images == 0) AND ($cache_htmlext == 0) AND ($cache_binaries == 0)) {
        return "# Do not cache images or html extensions";
    }
    $ext1=array();
    $ext=array();
    $ext2=array();
    if ($cache_images == 1) {
        $ext = array("jpe?g","gif","png","bmp","tiff","svg","ico","mp[0-9]+",
            "avi","mid","midi","mov","qt","wav");
    }

    if ($cache_htmlext == 1) {
        $ext1 = array("ttf","svg","woff","woff2","eot","java","class","jar",
            "css","scss","js","html","htm","shtm","shtml");
    }
    if ($cache_binaries == 1) {
        $ext2 = array( "pdf","rar","zip","gz","rpm","deb","iso","ova","nrg",
            "exe","xyz","7z","doc","docx","ppt","pptx");
    }
    $extensions=array_merge($ext,$ext1,$ext2);
    $f[] = "\tlocation ~* ^.+\.(".@implode("|",$extensions).")($|\?) {";
    $f[] = "\t\taccess_log off;";
    $f[] = "\t\texpires max;";
    $f[] = "\t\tinclude /etc/nginx/root-directives/$SERVICE_ID.conf;";
    $f[]="\t}";
    return @implode("\n",$f);
    }
function remove_website($serviceid):bool{
    if(intval($serviceid)==0){return false;}
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $f[]=mainconfig_file($serviceid);
    $backupdir="/home/artica/modsecurity_backup_$serviceid";
    if(is_dir($backupdir)){shell_exec("$rm -rf $backupdir");}

    $f[]="/etc/nginx/fastcgi/$serviceid.conf";
    $f[]="/etc/nginx/caches/$serviceid.conf";
    $f[]="/etc/nginx/reverse.d/0-$serviceid.conf";
    $f[]="/etc/nginx/modsecurities/nginx-$serviceid.conf";
    $f[]="/etc/nginx/modsecurities/locations-$serviceid.conf";
    $f[]="/etc/nginx/modsecurities/whiterules-$serviceid.conf";
    $f[]="/etc/nginx/modsecurities/modsecurity-$serviceid.conf";
    $f[]="/etc/nginx/modsecurities/loader-$serviceid.conf";
    $f[]="/etc/nginx/modsecurities/modsecurity-$serviceid.conf.build";
    $f[]="/etc/nginx/templates/error_page.502.$serviceid.html";
    $f[]="/etc/nginx/templates/error_page.403.$serviceid.html";
    $f[]="/etc/nginx/templates/error_page.496.$serviceid.html";
    $f[]="/etc/nginx/templates/error_page.400.$serviceid.html";
    $f[]="/etc/nginx/templates/error_page.403.$serviceid.html";
    $f[]="/etc/nginx/templates/error_page.404.$serviceid.html";
    $f[]="/etc/nginx/templates/error_page.500.$serviceid.html";
    $f[]="/etc/nginx/root-directives/$serviceid.conf";
    foreach ($f as $filename){
        if(is_file($filename)){@unlink($filename);}
    }
    return true;

}


class nginx_service{
    public function __construct(){

    }

    public function buildservernames($ligne):string{
        $ID=$ligne["ID"];
        $socksngix=new socksngix($ID);
        $Zhosts=explode("||",$ligne["hosts"]);
        $Zhosts2=array();
        $Variables=array();

        $ForwardServersDynamics =   intval($socksngix->GET_INFO("ForwardServersDynamics"));
        if($ForwardServersDynamics==1){
            $FSDynamicsExt = intval($socksngix->GET_INFO("FSDynamicsExt"));
            $FSDynamicsSrc = trim($socksngix->GET_INFO("FSDynamicsSrc"));
            $Variables[]="\tset \$dynaPrefix $1;";

            if ($FSDynamicsExt == 1) {
                if (preg_match("#\.(.*?)$#", $FSDynamicsSrc, $re)) {
                    $FSDynamicsSrc = str_replace("." . $re[1], "\.([a-z\-0-9]+)$", $FSDynamicsSrc);
                } else {
                    $FSDynamicsSrc = $FSDynamicsSrc . "\.([a-z\-0-9]+)$";
                }
                $Variables[]="\tset \$dynaExt $2;";
            }
            $FSDynamicsSrc="~^(.+?)\.$FSDynamicsSrc";
            $Zhosts2[]=$FSDynamicsSrc;

        }

        foreach ($Zhosts as $servername){
            $servername=trim($servername);
            if($servername==null){continue;}
            if($servername=="*"){$servername="~^.*$";}
            if(preg_match("#^(.+?)>(.+)#",$servername)){continue;}
            $Zhosts2[]=$servername;
        }
        $servicesnames=trim(@implode(" ", $Zhosts2));
        if($servicesnames==null){$servicesnames="_";}


        $f[]="\tserver_name $servicesnames;";
        if(count($Variables)>0) {
            $f[] = @implode("\n", $Variables);
        }
        $f[]=DebugMode($ID);
        return @implode("\n",$f);
    }

    public function buildRedirectsServerNames($ligne):array{
        $Zhosts=explode("||",$ligne["hosts"]);
        $Zhosts2=array();
        foreach ($Zhosts as $servername){
            $servername=trim($servername);
            if($servername==null){continue;}
            if(!preg_match("#^(.+?)>(.+)#",$servername,$re)){continue;}
            $servername=$re[1];
            $redirects=$re[2];
            if($servername=="*"){$servername="~^.*$";}
            $Zhosts2[$servername]=$redirects;
        }
        return $Zhosts2;
    }

    public function _out($text){
        echo "Starting......: ".date("H:i:s")." [INIT]: Reverse-Proxy $text\n";
        if(!function_exists('syslog')){return false;}
        openlog("nginx", LOG_PID,LOG_SYSLOG);
        syslog(LOG_INFO, $text);
        closelog();
        return true;
    }

    public function reload():bool{
        $unix       = new unix();
        $MasterPid  = PID_NUM();
        $chown      = $unix->find_program("chown");
        $nginx      = $unix->find_program("nginx");

        system("$chown www-data:www-data /var/log/nginx/access.log");
        system("$chown www-data:www-data /var/log/nginx/error.log");
        if(!$unix->process_exists($MasterPid)){
            system("$nginx -c /etc/nginx/nginx.conf");
        }


        $this->_out("Reloading PID $MasterPid");
        exec("$nginx -c /etc/nginx/nginx.conf -s reload 2>&1",$results);
        foreach ($results as $line){
            $line=trim($line);
            if($line==null){continue;}
            $this->_out($line);
            if(preg_match("#\[emerg\]#",$line)){
                return false;
            }

        }

        return true;
    }

    public function TestTheWholeConfig(){
        if($GLOBALS["NOCHECK"]){return true;}
        $unix=new unix();
        $nginxbin=$unix->find_program("nginx");
        $php=$unix->LOCATE_PHP5_BIN();
        $this->_out("[TestTheWholeConfig]: Testing configuration with $nginxbin...");
        $unix=new unix();
        if(!is_file("/etc/nginx/pagespeed.conf")){
            PageSpeedMinimal();

        }
        $GLOBALS["NGINX_FATAL_ERROR"]=null;

        exec("$nginxbin -c /etc/nginx/nginx.conf -t 2>&1",$testsR);
        $tests=false;
        foreach ($testsR as $line){
            $this->_out($line);
            if(preg_match("#\[warn\]\s+(.+)#", $line,$re)){$line=$re[1];}
            if(preg_match("#nginx:\s+(.+)#", $line,$re)){$line=$re[1];}

            if(preg_match("#emerg.*?proxy_cache.*?zone.*?is unknown#",$line)){
                $GLOBALS["NGINX_FATAL_ERROR"]=$line;
                $GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
                return false;

            }

            if(preg_match("#emerg\].*?pcre_compile.*?\s+failed.*?\"\s+in\s+(.+):#",$line,$re)){
                $filename=$re[1];
                $filenameBase=basename($filename);
                $GLOBALS["NGINX_FATAL_ERROR"]=$line;
                $GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
                $this->_out("Copy $filename to /root");
                @unlink("/root/$filenameBase");
                @copy($filename,"/root/$filenameBase");
                return false;
            }

            if(preg_match("#emerg\].*?unexpected\s+.*?\s+in\s+(.+?):#",$line,$re)){
                $filename=$re[1];
                $filenameBase=basename($filename);
                $GLOBALS["NGINX_FATAL_ERROR"]=$line;
                $GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
                $this->_out("Copy $filename to /root");
                @unlink("/root/$filenameBase");
                @copy($filename,"/root/$filenameBase");
                return false;
            } 

            if(preg_match("#emerg\]\s+directive.*?\s+in\s+(.+?):[0-9]+#",$line,$re)){
                $filename=$re[1];
                $filenameBase=basename($filename);
                $GLOBALS["NGINX_FATAL_ERROR"]=$line;
                $GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
                $this->_out("Copy $filename to /root");
                @unlink("/root/$filenameBase");
                @copy($filename,"/root/$filenameBase");
                return false;
            }
            if(preg_match("#emerg\]\s+pread.*?\s+in\s+(.+?):#",$line,$re)){
                $filename=$re[1];
                $filenameBase=basename($filename);
                $GLOBALS["NGINX_FATAL_ERROR"]=$line;
                $GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
                $this->_out("Copy $filename to /root");
                @unlink("/root/$filenameBase");
                @copy($filename,"/root/$filenameBase");
                return false;
            }



            if(preg_match("#emerg\]\s+invalid condition.*?\s+in\s+(.+?):[0-9]+#",$line,$re)){
                $filename=$re[1];
                $filenameBase=basename($filename);
                $GLOBALS["NGINX_FATAL_ERROR"]=$line;
                $GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
                $this->_out("Copy $filename to /root");
                @unlink("/root/$filenameBase");
                @copy($filename,"/root/$filenameBase");
                return false;
            }
            if(preg_match("#emerg\]\s+invalid variable name.*?\s+in\s+(.+?):[0-9]+#",$line,$re)){
                $filename=$re[1];
                $filenameBase=basename($filename);
                $GLOBALS["NGINX_FATAL_ERROR"]=$line;
                $GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
                $this->_out("Copy $filename to /root");
                @unlink("/root/$filenameBase");
                @copy($filename,"/root/$filenameBase");
                return false;
            }
            

            if(preg_match("#emerg.*?no servers are inside upstream in (.+?):#",$line,$re)){
                $filename=$re[1];
                $filenameBase=basename($filename);
                $GLOBALS["NGINX_FATAL_ERROR"]=$line;
                $GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
                $this->_out("Copy $filename to /root");
                @unlink("/root/$filenameBase");
                @copy($filename,"/root/$filenameBase");
                return false;
            }

            if(preg_match("#host not found in upstream.*?\/([0-9]+)\.conf:#",$line,$re)){
                $BADID=intval($re[1]);
                $this->_out($line);
                $this->_out("Compiling failed site $BADID...");
                exec("$php /usr/share/artica-postfix/exec.nginx.single.php $BADID --nocheck 2>&1",$failedid);
                foreach ($failedid as $failed_line){
                    $this->_out("Site $BADID : $failed_line");
                }
                return $this->TestTheWholeConfig();
            }

            if(preg_match("#unknown directive \"vhost_traffic_status_zone\"#",$line,$re)){
                $this->_out("Compiling failed vhost_traffic_status_zone, remove this token");
                if($this->remove_vhost_traffic_status_zone()) {
                    return $this->TestTheWholeConfig();
                }
                return false;
            }


            if(preg_match("#invalid URL prefix in\s+(.*?):([0-9]+)#",$line,$re)){
                $filename=$re[1];
                $filenameBase=basename($filename);
                $lineError=$re[2];
                $GLOBALS["NGINX_FATAL_ERROR"]=$lineError;
                $GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
                $this->_out("Copy $filenameBase to /root");
                @unlink("/root/$filenameBase");
                @copy($filename,"/root/$filenameBase");
                continue;
            }


            if(preg_match("#invalid number of arguments in \"(.+?)\" directive in (.+?):([0-9]+)#", $line,$re)){
                $arguments=$re[1];
                $filename=$re[2];
                $filenameBase=basename($filename);
                $lineError=$re[3];
                $GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
                $this->_out("ERROR $arguments LINE $lineError !!!!");
                $this->_out("Copy $filenameBase to /root");
                @unlink("/root/$filenameBase");
                @copy($filename,"/root/$filenameBase");
                continue;
            }

            if(preg_match('#open.*?\"(.+)\.log"\s+failed.*?No such file or directory#', $line,$re)){
                $filename=$re[1].".log";
                $dirname=dirname($filename);
                @mkdir($dirname,0755,true);
                $APACHE=$unix->APACHE_SRC_ACCOUNT();
                $APACHEG=$unix->APACHE_SRC_GROUP();
                @chown($dirname,$APACHE);
                @chgrp($dirname, $APACHEG);
                return $this->TestTheWholeConfig();
            }

            if(preg_match("#SSL: error:#", $line)){
                $GLOBALS["NGINX_FATAL_ERROR"]="SSL Certificate ERROR!";
                $GLOBALS["NGINX_FATAL_ERRORS"][]=$line;
                $this->_out("***************************************");
                $this->_out("$line");
                $this->_out("***************************************");
                return false;
            }



            if(preg_match("#a duplicate default server for (.+?)\s+in\s+(.+?):#", $line,$re)){
                $filename=$re[2];
                $filenameBase=basename($filename);
                if(preg_match("#^([0-9]+)-([0-9]+)\.conf#",$filenameBase,$ri)){$SERVERID=$ri[2];Make_site_failed($SERVERID,"$line");}
                $this->_out("Duplicate default server {$re[1]} remove $filenameBase serverid:$SERVERID");
                @unlink($filename);
                $this->TestTheWholeConfig();
                return false;
            }

            if(preg_match("#invalid URL prefix in\s+(.+?):#", $line,$re)){
                $filename=$re[1];
                $filenameBase=basename($filename);
                if(preg_match("#^([0-9]+)-([0-9]+)\.conf#",$filenameBase,$ri)){$SERVERID=$ri[2];Make_site_failed($SERVERID,"$line");}
                $this->_out("Invalid URL prefix remove $filenameBase");
                @unlink($filename);
                $this->TestTheWholeConfig();
                return false;
            }

            if(preg_match("#configuration file.*?test is successful#", $line)){
                $this->_out("Testing configuration Success [OK]!!...");
                $tests=true;
                break;
            }

        }

        if(!$tests){$GLOBALS["NGINX_FATAL_ERROR"]="Error in config file";}
        return $tests;
    }

    public function remove_vhost_traffic_status_zone():bool{
        $newF=array();
        $FOUND=false;
        $f=explode("\n",@file_get_contents("/etc/nginx/nginx.conf"));
        foreach ($f as $line){
            if(preg_match("#vhost_traffic_status_zone;#",$line)){
                $FOUND=true;
                continue;
            }
            $newF[]=$line;
        }
        if($FOUND){
            @file_put_contents("/etc/nginx/nginx.conf",@implode("\n",$newF));
        }
        return $FOUND;
    }

    public function backup_config(){
        $unix=new unix();
        $tar=$unix->find_program("tar");
        if(!is_dir("/home/artica/nginx")){@mkdir("/home/artica/nginx",0755,true);}
        if(is_file("/home/artica/nginx/backup-config.tar.gz")){@unlink("/home/artica/nginx/backup-config.tar.gz");}
        $this->_out("Backuping global configuration");
        shell_exec("$tar -czf /home/artica/nginx/backup-config.tar.gz /etc/nginx/*");

    }



    public function rolling_back(){
        if(!is_file("/home/artica/nginx/backup-config.tar.gz")){return false;}
        $unix=new unix();
        $rm=$unix->find_program("rm");
        $tar=$unix->find_program("tar");
        $this->_out("Backup the configuration failed to /home/artica/nginx/failed-config.tar.gz");
        shell_exec("$tar -czf /home/artica/nginx/failed-config.tar.gz /etc/nginx/*");
        $this->_out("Removing failed configuration");
        shell_exec("$rm -rf /etc/nginx/*");
        $this->_out("Rolling back global configuration from the backup");
        shell_exec("$tar -xf /home/artica/nginx/backup-config.tar.gz -C /");
        return true;
    }

}



