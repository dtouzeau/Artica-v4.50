<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if($argv[1]=="--certificate-setup"){create_certificate(null,true);exit;}

xstart();

function build_progress($text,$pourc){
    $unix=new unix();
    echo "{$pourc}% $text\n";
    $unix->framework_progress($pourc,$text,"ufdberror.compile.progress");
}

function xstart(){

    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $UFDB_WIZARD_ERROR_PAGE=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UFDB_WIZARD_ERROR_PAGE"));

    $IpClass=new IP();
    $DOMAIN=trim($UFDB_WIZARD_ERROR_PAGE["WIZARD_HOSTNAME"]);
    $HTTP_PORT=intval($UFDB_WIZARD_ERROR_PAGE["WIZARD_HTTP_PORT"]);
    $SSL_PORT=intval($UFDB_WIZARD_ERROR_PAGE["WIZARD_SSL_PORT"]);

    echo "hostname: {$DOMAIN}:$HTTP_PORT or {$DOMAIN}:$SSL_PORT\n";

    if(!$IpClass->IsValid($DOMAIN)){
        $ipaddr=gethostbyname($DOMAIN);
        if(!$IpClass->IsValid($ipaddr)){
            build_progress("{CURLE_COULDNT_RESOLVE_HOST} $DOMAIN",110);
            return;
        }

    }

    if( $HTTP_PORT==0 OR $SSL_PORT==0){
        build_progress("Bad HTTP/HTTPS port",110);
        return;
    }

    build_progress("$DOMAIN",20);
    echo "Is a service as been already added ?\n";
    $service_id=get_service_id();
    if($service_id==0){
        echo "Create a new service...\n";
        $service_id=create_service_id();
        if($service_id==0){return;}
    }
    echo "Service as been created id  $service_id\n";
    echo "Create, update hostname $DOMAIN\n";
    build_progress("$DOMAIN",30);
    if(!add_host($service_id,$DOMAIN)){
        build_progress("Failed to add host $DOMAIN",110);
        return;
    }

    build_progress("$DOMAIN:$HTTP_PORT",40);
    $portid=portid($service_id,$HTTP_PORT);
    if($portid==0){
        $portid=add_port($service_id,$HTTP_PORT);
        if($portid==0){
            build_progress("Failed to add port $HTTP_PORT",110);
            return;
        }
    }
    build_progress("$DOMAIN:$SSL_PORT",40);
    $portid=portid($service_id,$SSL_PORT);
    if($portid==0){
        $portid=add_port($service_id,$SSL_PORT,1);
        if($portid==0){
            build_progress("Failed to add port $SSL_PORT",110);
            return;
        }
    }

    build_progress("$DOMAIN:$SSL_PORT",50);
    addValue($service_id,"ssl_protocols","TLSv1 TLSv1.1 TLSv1.2");
    addValue($service_id, "ssl_ciphers","ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK");

    addValue($service_id,"ssl_prefer_server_ciphers",'0');
    addValue($service_id,"ssl_buffer_size",'16');
    addValue($service_id,"ssl_certificate",$DOMAIN);
    if(!create_certificate($DOMAIN)){
        build_progress("{certificate} $DOMAIN {failed}",110);
        return;
    }


    if(!is_file("/etc/init.d/nginx")) {
        build_progress("{installing} {APP_NGINX}",65);
        system("/usr/sbin/artica-phpfpm-service -nginx-install");
    }




    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardWebUseExternalUri",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardWebWebServiceID",$service_id);

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardWebUseInternalUri",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardWebExternalUri","http://$DOMAIN:{$HTTP_PORT}/ufdbguard.php");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardWebExternalUri2","http://$DOMAIN:{$HTTP_PORT}/ufdbguard.php");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidGuardWebExternalUriSSL","$DOMAIN:{$SSL_PORT}/ufdbguard.php");

    build_progress("{reloading}",90);

    build_progress("{webfiltering_error_page} {done}",100);
    system("$php5 /usr/share/artica-postfix/exec.nginx.single.php $service_id");


}

function create_certificate($hostname=null,$onlythis=false){

    if($hostname==null){$hostname=php_uname("n");}
    echo "Using Hostname $hostname\n";
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $WizardSavedSettings=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings"));
    if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
    if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $ligne=$q->mysqli_fetch_array("SELECT `csr`,`crt`,`UsePrivKeyCrt`,`SquidCert` `CommonName` FROM `sslcertificates` WHERE `CommonName`='$hostname'");

    if($ligne["CommonName"]<>null) {
        if($onlythis) {
            build_progress("$hostname: {generating_certificate} {success}", 100);
        }
        return true;
    }

    $sql = "INSERT OR IGNORE INTO `sslcertificates` (CountryName,stateOrProvinceName,CertificateMaxDays,OrganizationName,OrganizationalUnit,emailAddress,localityName,levelenc,password,CommonName) VALUES ('UNITED STATES_US','New York','999','{$LicenseInfos["COMPANY"]}','IT service','{$LicenseInfos["EMAIL"]}','Brooklyn','2048','','$hostname')";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "Creating certificate failed\n";
        if($onlythis){
            build_progress("$hostname: {generating_certificate} {failed}",110);
        }
        return false;
    }


    $ligne=$q->mysqli_fetch_array("SELECT `csr`,`crt`,`UsePrivKeyCrt`,`SquidCert` `CommonName` 
    FROM `sslcertificates` WHERE `CommonName`='$hostname'");


    $field="crt";
    if($ligne["UsePrivKeyCrt"]==0){$field="SquidCert";}
    if(strlen($ligne["csr"])<20){
        build_progress("$hostname: {generating_certificate}",55);
        $cmd=trim("$php5 /usr/share/artica-postfix/exec.openssl.php --csr \"$hostname\" --output");
        system($cmd);
    };
    if(strlen($ligne[$field])<20){
        build_progress("$hostname: {generating_certificate}",60);
        $cmd = trim("$php5 /usr/share/artica-postfix/exec.openssl.php --easyrsa $hostname --output");
        system($cmd);
    }
    if($onlythis){
        build_progress("$hostname: {generating_certificate} {success}",100);
    }
    return true;
}

function addValue($service_id,$key,$value){
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT zvalue FROM service_parameters WHERE serviceid='$service_id' AND zkey='$key'");
    if(trim($ligne["zvalue"])==$value){return;}
    $sql="INSERT INTO service_parameters (serviceid,zkey,zvalue) VALUES ($service_id,'$key','$value');";
    $q->QUERY_SQL($sql);
}

function add_port($service_id,$port,$ssl=0){
    $array=array();
    if($ssl==1){$array["ssl"]=1;}
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $md5=md5($port);
    $options=base64_encode(serialize($array));
    $q->QUERY_SQL("INSERT OR IGNORE INTO stream_ports (serviceid,interface,port,zmd5,options) VALUES ($service_id,'',$port,'$md5','$options')");
    if(!$q->ok){echo $q->mysql_error."\n";return 0;}
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM stream_ports WHERE serviceid='$service_id' AND port='$port'");
    return intval($ligne["ID"]);
}

function portid($service_id,$port){
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM stream_ports WHERE serviceid='$service_id' AND port='$port'");
    return intval($ligne["ID"]);
}

function create_service_id(){
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $sql = "INSERT INTO nginx_services (`type`,servicename,hosts,enabled ) VALUES(6,'Web filtering error page','',1)";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        build_progress("{configuring} {APP_NGINX} {failed}",110);
        echo $q->mysql_error;
        return 0;
    }
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM nginx_services WHERE type=6 and enabled=1");
    return intval($ligne["ID"]);
}

function add_host($service_id,$hostname){

    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT `servicename`,`hosts` FROM nginx_services WHERE ID=$service_id");
    $Zhosts=explode("||",$ligne["hosts"]);

    foreach ($Zhosts as $host){
        if(trim(strtolower($host))==trim(strtolower($hostname))){return true;}
    }
    $Zhosts[]=trim(strtolower($hostname));
    $newhosts=@implode("||",$Zhosts);
    $q->QUERY_SQL("UPDATE nginx_services SET hosts='$newhosts' WHERE ID=$service_id");
    if(!$q->ok){echo $q->mysql_error;return false;}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterWaitNotify",time());
    return true;

}


function get_service_id(){

    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM nginx_services WHERE type=6 and enabled=1");
    return intval($ligne["ID"]);
}