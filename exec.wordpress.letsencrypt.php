<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/wordpress.index.progress";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');

if(!isset($argv[1])){
    echo "No parameters sended, die()";
    die();
}

if($argv[1]=="--renews"){renew_letsencrypts();exit;}

if($argv[1]=="--getcerts"){
    if(find_certificates($argv[2])){echo "Success\n";}else{echo "Failed\n";}
    exit;
}

create_letsencrypt($argv[1]);

function build_progress($text,$pourc):bool{
	$unix=new unix();
    $ID=$GLOBALS["INSTANCE_ID"];
    $unix->framework_progress($pourc,$text,"wordpress.letsencrypt.progress.$ID");
    return true;
}


function _out($text):bool{
    echo "$text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("letsencrypt", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}

function CheckWordpress_nginx($ID){
    $conf="/etc/nginx/wordpress/$ID.conf";
    $f=explode("\n",@file_get_contents($conf));
    foreach ($f as $line){
        if(strpos($line,"127.0.0.1:9554")>0){return true;}
    }

    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.wordpress.install.php --nginx $ID --onlyconfig");
    return true;

}


function create_letsencrypt($ID):bool{
	$unix=new unix();
	$GLOBALS["INSTANCE_ID"]=$ID;
    CheckWordpress_nginx($ID);
	$q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
	$ligne=$q->mysqli_fetch_array("SELECT hostname,admin_email FROM wp_sites WHERE ID=$ID");
	$admin_email=$ligne["admin_email"];
    $admin_email=str_replace(";",".",$admin_email);
    $hostname=$ligne["hostname"];
    $aliases=unserialize(base64_decode($ligne["aliases"]));

    $wws[$ligne["hostname"]]=$ligne["hostname"];
    foreach ($aliases as $sitename=>$none){
        $wws[$sitename]=$sitename;
    }
    foreach ($wws as $sitename=>$none){
        $zdomains[]="-d $sitename";
    }
    $certbot=$unix->find_program("certbot");
    $unix->Popuplate_cron_make("wordpress-letsenc","15 2 */2 * *","exec.wordpress.letsencrypt.php --renews");

	if(!ValidateMail($admin_email)){
        build_progress("{incorrect_email_address} $admin_email",110);
		return false;
	}

	$rm=$unix->find_program("rm");
    _out("Remove any directory /etc/letsencrypt/live/wordpress-$hostname*");
	shell_exec("$rm -rf /etc/letsencrypt/live/wordpress-$hostname*");

    _out("Remove any directory /etc/letsencrypt/archive/wordpress-$hostname*");
    shell_exec("$rm -rf /etc/letsencrypt/archive/wordpress-$hostname*");

    _out("Remove any files /etc/letsencrypt/renewal/wordpress-$hostname*");
    shell_exec("$rm -f /etc/letsencrypt/live/wordpress-$hostname*");

	$d_domains=@implode(" ",$zdomains);


	if(is_file("/var/log/letsencrypt/letsencrypt.log")){@unlink("/var/log/letsencrypt/letsencrypt.log");}

    _out("Generate Certificate for Wordpress site $hostname with domains $d_domains");

    $hostname="wordpress-$hostname-$ID";
    build_progress("{generate_certificate} $hostname",30);

    if(is_dir("/etc/letsencrypt/live/$hostname")){
        shell_exec("$rm -rf /etc/letsencrypt/renewal/$hostname");
    }
    if(is_dir("/etc/letsencrypt/renewal/$hostname")){
        shell_exec("$rm -rf /etc/letsencrypt/renewal/$hostname");
    }

    $certificate_path="/etc/letsencrypt/live/$hostname/cert.pem";
    $privkey_path="/etc/letsencrypt/live/$hostname/privkey.pem";
    $chain_path="/etc/letsencrypt/live/$hostname/chain.pem";
    $fullchain_path="/etc/letsencrypt/live/$hostname/fullchain.pem";


	$cmd=array();
	$cmd[]="$certbot certonly";
    $cmd[]="--cert-name $hostname";
	$cmd[]="--config-dir /etc/letsencrypt";
	$cmd[]="--work-dir /var/lib/letsencrypt";
	$cmd[]="--cert-path $certificate_path";
    $cmd[]="--webroot -w /home/letsencrypt_work";
	$cmd[]="--key-path $privkey_path";
	$cmd[]="--fullchain-path $fullchain_path";
	$cmd[]="--chain-path $chain_path";
	$cmd[]="--noninteractive";
	$cmd[]="--agree-tos";
	$cmd[]="-m $admin_email";
	$cmd[]="$d_domains";
	$cmd[]="2>&1";
	$Cmdline=@implode(" ", $cmd);

	echo "Execute: $Cmdline\n";
	shell_exec($Cmdline);

	$f=explode("\n",@file_get_contents("/var/log/letsencrypt/letsencrypt.log"));


    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        _out($line);
    }

    if(!find_certificates($ID)){
        build_progress("{failed}...",110);
        return false;
    }


	$php=$unix->LOCATE_PHP5_BIN();
    build_progress("{reloading}...",50);
	shell_exec("$php /usr/share/artica-postfix/exec.certificates.center.php >/dev/null 2>&1");
    build_progress("{reloading}...",60);
    shell_exec("$php /usr/share/artica-postfix/exec.wordpress.install.php --nginx $ID --onlyconfig");
	build_progress("{success}",100);
    return true;

}

function ValidateMail($emailAddress_str):bool{
	$emailAddress_str=trim(strtolower($emailAddress_str));
	$regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
	if (preg_match($regex, $emailAddress_str)) {return true;}
	return false;
}

function find_certificates($ID):bool{
    if(intval($ID)==0){
        _out("Invalid site ID $ID");
        return false;
    }
    $BASEDIR="/etc/letsencrypt/live";
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname FROM wp_sites WHERE ID=$ID");
    $hostnameSrc=$ligne["hostname"];
    $hostnameSrcRegex=str_replace(".","\.",$hostnameSrc);
    $hostname="wordpress-$hostnameSrc-$ID";
    list($certificate_path,$privkey_path,$fullchain_path,$found)=getCertificates("$BASEDIR/$hostname");

    if($found){
        return InsertCerts($ID,$certificate_path,$privkey_path,$fullchain_path);
    }

    _out("Try to find a path for $hostname");

    $files=scandir($BASEDIR);
    foreach ($files as $subdir){
        if(preg_match("#$hostnameSrcRegex-$ID#",$subdir)){
            $DirectoryFound="$BASEDIR/$subdir";
            _out("Found directory $DirectoryFound");
            list($certificate_path,$privkey_path,$fullchain_path,$found)=getCertificates($DirectoryFound);
            if($found){
                return InsertCerts($ID,$certificate_path,$privkey_path,$fullchain_path);
            }
        }

    }
    return false;
}

function InsertCerts($ID,$certificate_path,$privkey_path,$chain_path):bool{

    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $ligne=$q->mysqli_fetch_array("SELECT hostname FROM wp_sites WHERE ID=$ID");
    $hostnameSrc=$ligne["hostname"];

    $certificate_content=@file_get_contents($certificate_path);
    $certificate_content=sqlite_escape_string2($certificate_content);
    $privkey_content=@file_get_contents($privkey_path);
    $privkey_content=sqlite_escape_string2($privkey_content);
    $chain_content=@file_get_contents($chain_path);
    $chain_content=sqlite_escape_string2($chain_content);

    $q=new lib_sqlite("/home/artica/SQLITE/certificates.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM sslcertificates WHERE CommonName='$hostnameSrc'");
    if( intval($ligne["ID"]) >0 ) {
        _out("Update certificate $hostnameSrc inside certificate center");
        $q->QUERY_SQL("UPDATE sslcertificates SET 
			`UsePrivKeyCrt`=1,
			`privkey`='$privkey_content',
			`bundle`='$chain_content',
			`crt`='$certificate_content',
			`UseLetsEncrypt`=1
			WHERE `ID`={$ligne["ID"]}");


        if(!$q->ok){
            _out("SQL Errpr $q->mysql_error");
            return false;

        }

    }else{
        _out("Create certificate $hostnameSrc inside certificate center");
        $sql="INSERT INTO sslcertificates (CommonName,UsePrivKeyCrt,privkey,bundle,crt,DateFrom,DateTo,UseLetsEncrypt) VALUES ('$hostnameSrc',1,'$privkey_content','$chain_content','$certificate_content','','',1)";
        $q->QUERY_SQL($sql);

        if(!$q->ok){
            _out("SQL Errpr $q->mysql_error");
            return false;

        }
    }

    _out("Updating Wordpress parameters");
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $q->QUERY_SQL("UPDATE wp_sites set ssl=1,ssl_certificate='$hostnameSrc',letsencrypt='1' WHERE ID=$ID");
    if(!$q->ok){
        _out("SQL Errpr $q->mysql_error");
        return false;

    }
    return true;

}


function getCertificates($main_path):array{
    $certificate_path="$main_path/cert.pem";
    $privkey_path="$main_path/privkey.pem";
    $chain_path="$main_path/chain.pem";
    $fullchain_path="$main_path/fullchain.pem";

    if(is_link($certificate_path)){
        _out("$certificate_path is a link");
        $certificate_path=readlink($certificate_path);
        $certificate_path=str_replace("../../","/etc/letsencrypt/",$certificate_path);
        _out("$certificate_path Destination");
    }
    if(is_link($privkey_path)){
        _out("$privkey_path is a link");
        $privkey_path=readlink($privkey_path);
        $privkey_path=str_replace("../../","/etc/letsencrypt/",$privkey_path);
        _out("$privkey_path Destination");
    }
    if(is_link($fullchain_path)){
        _out("$fullchain_path is a link");
        $fullchain_path=readlink($fullchain_path);
        $fullchain_path=str_replace("../../","/etc/letsencrypt/",$fullchain_path);
        _out("$fullchain_path is a destination");
    }

    if(!is_file($certificate_path)){
        if(is_file($chain_path)){
            $certificate_path=$chain_path;

        }
    }

    if(!is_file($certificate_path)){
        _out("$certificate_path is a not a file");
        return array(null,null,null,false);
    }
    if(!is_file($privkey_path)){
        _out("$privkey_path is a not a file");
        return array(null,null,null,false);
    }
    if(!is_file($fullchain_path)){
        _out("$fullchain_path is a not a file");
        return array(null,null,null,false);
    }
    _out("Success: found all certificates in $main_path");
    return array($certificate_path,$privkey_path,$fullchain_path,true);

}

function renew_letsencrypts():bool{
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    $qCert=new lib_sqlite("/home/artica/SQLITE/certificates.db");

    if(!$q->FIELD_EXISTS("wp_sites","letsencrypt")) {
        $q->QUERY_SQL("ALTER TABLE wp_sites ADD letsencrypt INTEGER NOT NULL DEFAULT '0'");
    }

    $results=$q->QUERY_SQL("SELECT ID,ssl_certificate FROM wp_sites WHERE enabled=1 AND ssl=1 AND letsencrypt=1");

    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $ssl_certificate=$ligne["ssl_certificate"];
        $DNS=array();
        $ligne2=$qCert->mysqli_fetch_array("SELECT ID,privkey,crt FROM sslcertificates WHERE CommonName='$ssl_certificate'");
        $IDCert=intval($ligne2["ID"]);
        $crt=$ligne2["crt"];
        if($IDCert==0){continue;}
        $array= openssl_x509_parse($crt);
        $DNS[]=$array["subject"]["CN"];
        $validTo_time_t=intval($array["validTo_time_t"]);
        $Minutes=cert_time_min($validTo_time_t);
        if($Minutes>2880){continue;}
        if(isset($array["extensions"]["subjectAltName"])){
            $DNS[]=$array["extensions"]["subjectAltName"];
        }
        $time=date("Y-m-d H:i:s",$validTo_time_t);
        $finaldns=renew_letsencrypts_cleandns($DNS);
        $log= "Found Site to renew ID: $ID ($ssl_certificate) --> $IDCert [".@implode(", ",$finaldns)."] Left $Minutes minutes..($validTo_time_t - $time)";
        echo $log."\n";
        if(!create_letsencrypt($ID)){
            $logs=@file_get_contents("/var/log/letsencrypt/letsencrypt.log");
            squid_admin_mysql(0,"Unable to renew certificate for Wordpress site $ID [".@implode(", ",$finaldns)."]",$log."\n$logs",__FILE__,__LINE__);

        }



    }


    return true;

}
function renew_letsencrypts_cleandns($DNS):array{
    foreach ($DNS as $hostname){
        if(preg_match("#DNS:(.+)#",$hostname,$re)){
            $hostname=$re[1];
        }
        $COMPILED_DNS[$hostname]=true;
    }

    $tt=array();
    foreach ($COMPILED_DNS as $hostname=>$null){
        $tt[]=$hostname;
    }

    return $tt;
}

function cert_time_min($finaltime){

    $data1 = $finaltime;
    $data2 = time();
    $difference = ($data1-$data2);
    $results=intval(round($difference/60));
    if($results<0){$results=0;}
    return $results;
}


?>