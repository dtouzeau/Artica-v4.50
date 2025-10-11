<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");



if(isset($_GET["patch-sql"])){patchSQL();exit;}
if(isset($_GET["import-pfx"])){import_pfx();exit;}
if(isset($_GET["import-p7r"])){import_p7r();exit;}


if(isset($_GET["generate-key"])){generate_key();exit;}
if(isset($_GET["generate-x509"])){generate_x509();exit;}
if(isset($_GET["generate-x509-client"])){generate_x509_client();exit;}

if(isset($_GET["tomysql"])){tomysql();exit;}
if(isset($_GET["copy-privatekey"])){copy_private_key();exit;}
if(isset($_GET["move-privkey"])){move_private_key();exit;}
if(isset($_GET["gen-csr"])){gencsr();exit;}
if(isset($_GET["copy-csr"])){copy_csr();exit;}
if(isset($_GET["generate-csr"])){generate_CSR();exit;}
if(isset($_GET["pvk-convert"])){pvk_convert();exit;}
if(isset($_GET["pfx-convert"])){pfx_convert();exit;}
if(isset($_GET["generate-pfx"])){pfx_generate();exit;}
if(isset($_GET["easyrsa-csr"])){easyrsa_csr();exit;}
if(isset($_GET["backup"])){backup();exit;}
if(isset($_GET["restore"])){restore();exit;}
if(isset($_GET["import-backup"])){import_backup();exit;}
if(isset($_GET["letsencrypt"])){letsencrypt();exit;}
if(isset($_GET["extract-infos"])){extract_infos();exit;}
if(isset($_GET["generate-selfsign"])){generate_selfsign();exit;}

if(isset($_GET["selfsign-server"])){server_selfsign();exit;}
if(isset($_GET["selfsign-client"])){client_selfsign();exit;}
if(isset($_GET["wizard-proxy"])){wizard_proxy();exit;}

if(isset($_GET["letsencrypt-dns-key"])){letsencrypt_dnskey();exit;}
if(isset($_GET["letsencrypt-dns"])){letsencrypt_dns();exit;}
if(isset($_GET["new-self-signed-auto"])){new_self_signed_auto();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);


function new_self_signed_auto(){
    $unix=new unix();
    $unix->framework_execute("exec.wizard.webfiltering.page.php --certificate-setup","ufdberror.compile.progress",
        "ufdberror.compile.log");
}


function generate_key(){
    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }

	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$servername=$_GET["generate-key"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --buildkey $servername$Addon >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}
function patchSQL(){

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }

    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$php5 /usr/share/artica-postfix/exec.openssl.php --sql$Addon >/dev/null 2>&1");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function letsencrypt_dnskey(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/letsencrypt.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/letsencrypt.log";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0755);
    @chmod($GLOBALS["LOGSFILES"], 0755);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.certbot.php --dnskey {$_GET["letsencrypt-dns-key"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function letsencrypt_dns(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/letsencrypt.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/letsencrypt.log";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0755);
    @chmod($GLOBALS["LOGSFILES"], 0755);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.certbot.php --dns {$_GET["letsencrypt-dns"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function letsencrypt(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/letsencrypt.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/letsencrypt.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	
	$filename=$_GET["filename"];
	$filename=base64_encode($filename);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.certbot.php --create-certificate {$_GET["letsencrypt"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	
}

function extract_infos(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.certbot.php --extract {$_GET["extract-infos"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function copy_private_key(){
	$unix=new unix();
	$openssl=$unix->find_program("openssl");
	
	if(!is_file("/etc/openssl/private-key/privkey.key")){
		@mkdir("/etc/openssl/private-key",0755,true);
		shell_exec("$openssl genrsa -out /etc/openssl/private-key/privkey.key 2048");
	}
	@unlink("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.key");
	@copy("/etc/openssl/private-key/privkey.key","/usr/share/artica-postfix/ressources/logs/web/Myprivkey.key");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.key",0777);
}

function move_private_key(){
	@mkdir("/etc/openssl/private-key",0755,true);
	@unlink("/etc/openssl/private-key/privkey.key");
	@copy("/usr/share/artica-postfix/ressources/conf/upload/privkey.key","/etc/openssl/private-key/privkey.key");
	@unlink("/usr/share/artica-postfix/ressources/conf/upload/privkey.key");
}

function copy_csr(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.csr");
	@copy("/etc/openssl/private-key/server.csr","/usr/share/artica-postfix/ressources/logs/web/Myprivkey.csr");
	@chmod("/usr/share/artica-postfix/ressources/logs/web/Myprivkey.csr",0777);
	
}

function gencsr(){
	$unix=new unix();
	$openssl=$unix->find_program("openssl");
	$ligne=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/conf/upload/CSR.ARRAY"));
	@unlink("/usr/share/artica-postfix/ressources/conf/upload/CSR.ARRAY");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CertificateCenterCSR",serialize($ligne));
	$CommonName=$ligne["CommonName"];
	if($ligne["CountryName"]==null){$ligne["CountryName"]="US";}
	if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
	if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
	if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
	if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
	if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
	@mkdir("/etc/openssl/private-key",0755,true);
	$ligne["password"]=escapeshellcmd($ligne["password"]);
    if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
    if(preg_match("#^.*?_(.+)#", $ligne["CountryName"],$re)){$ligne["CountryName"]=$re[1];}


	$C=$ligne["CountryName"];
	$ST=$ligne["stateOrProvinceName"];
	$L=$ligne["localityName"];
	$O=$ligne["OrganizationName"];
	$OU=$ligne["OrganizationalUnit"];
    $privkey="/etc/openssl/private-key/privkey.key";

    if(!is_dir(dirname($privkey))){@mkdir(dirname($privkey),0755,true);}

    if(strlen($ligne["privkey"])>50) {
        $privkey = "/etc/openssl/private-key/spec.key";
        writelogs_framework("Using private key already generated",__FUNCTION__,__FILE__,__LINE__);
        @file_put_contents($privkey, $ligne["privkey"]);
    }

	if(!is_file($privkey)){
       writelogs_framework("No private key found - generate a new one",__FUNCTION__,__FILE__,__LINE__);
       shell_exec("$openssl genrsa -out $privkey 2048");
    }

	$logfilesname=PROGRESS_DIR."/opensssl.csr.log";
	$cmd[]="$openssl req -new -sha256 -key $privkey";
	$cmd[]="-passin pass:{$ligne["password"]}";
	$cmd[]="-subj \"/C=$C/ST=$ST/L=$L/O=$O/OU=$OU/CN=$CommonName\" -out /etc/openssl/private-key/server.csr";
	$cmdline=@implode(" ", $cmd);
	writelogs_framework("$cmdline >$logfilesname 2>&1",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmdline);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("GeneratedCSR",base64_encode(@file_get_contents("/etc/openssl/private-key/server.csr")));
}

function easyrsa_csr(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }
	
	$servername=$_GET["easyrsa-csr"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --csr \"$servername\" --output$Addon >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function import_backup(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/certificates.center.import.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/certificates.center.import.progress.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	
	$filename=$_GET["filename"];
	$filename=base64_encode($filename);

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }

	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --import-backup $filename$Addon >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	shell_exec($cmd);
	
}

function import_p7r(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();

    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/certificates.center.import.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/certificates.center.import.progress.log";

    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0755);
    @chmod($GLOBALS["LOGSFILES"], 0755);

    $filename=$_GET["filename"];
    $filename=base64_encode($filename);

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --import-p7r \"$filename\"$Addon >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);
}

function import_pfx(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();

    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/certificates.center.import.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/certificates.center.import.progress.log";

    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0755);
    @chmod($GLOBALS["LOGSFILES"], 0755);

    $filename=$_GET["filename"];
    $filename=base64_encode($filename);
    $password=$_GET["password"];

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --import-pfx \"$filename\" \"$password\"$Addon >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    shell_exec($cmd);

}

function backup(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }
	
	
	$servername=$_GET["easyrsa-csr"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --backup --output$Addon >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	shell_exec($cmd);
	
}

function restore(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }
	
	$filename=$_GET["filename"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --restore \"$filename\" --output$Addon >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	shell_exec($cmd);	
}

function generate_x509_client(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }
	
	
	$servername=$_GET["generate-x509-client"];
	$servername=str_replace("*", "_ALL_", $servername);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --client-server \"$servername\" --output$Addon >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
	
	
}
function generate_CSR(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();

	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";

	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }


	$servername=$_GET["generate-csr"];
	$servername=str_replace("*", "_ALL_", $servername);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --BuildCSR \"$servername\" --output$Addon >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";

}

function generate_x509(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";

	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }
	
	
	$servername=$_GET["generate-x509"];
	$servername=str_replace("*", "_ALL_", $servername);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --easyrsa $servername --output$Addon >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
	
}
function tomysql(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$servername=$_GET["tomysql"];
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.openssl.php --mysql $servername 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(trim(@implode("\n",$results)))."</articadatascgi>";	
	
}

function generate_selfsign(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/selfsign.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/selfsign.log";

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"], 0755);
    @chmod($ARRAY["LOG_FILE"], 0755);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --selfsign$Addon >{$ARRAY["LOG_FILE"]} 2>&1 &");
    exec($cmd,$results);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}
function server_selfsign(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();

    $certid=intval($_GET["selfsign-server"]);

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/selfsign.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/selfsign.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"], 0755);
    @chmod($ARRAY["LOG_FILE"], 0755);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --selfsign-server $certid$Addon >{$ARRAY["LOG_FILE"]} 2>&1 &");
    exec($cmd,$results);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}
function client_selfsign(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }

    $certid=intval($_GET["selfsign-client"]);

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/selfsign.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/selfsign.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"], 0755);
    @chmod($ARRAY["LOG_FILE"], 0755);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --selfsign-client $certid$Addon >{$ARRAY["LOG_FILE"]} 2>&1 &");
    exec($cmd,$results);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function wizard_proxy(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/selfsign.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/selfsign.log";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"], 0755);
    @chmod($ARRAY["LOG_FILE"], 0755);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --squid-wizard >{$ARRAY["LOG_FILE"]} 2>&1 &");
    exec($cmd,$results);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);

}

function pvk_convert(){
	$servername=$_GET["pvk-convert"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }

	$cmd=trim("$php5 /usr/share/artica-postfix/exec.openssl.php --pvk \"$servername\"$Addon 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(trim(@implode("\n",$results)))."</articadatascgi>";
	
}
function pfx_convert(){
	$servername=$_GET["pfx-convert"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }

	$cmd=trim("$php5 /usr/share/artica-postfix/exec.openssl.php --pfx-convert \"$servername\"$Addon 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(trim(@implode("\n",$results)))."</articadatascgi>";

}


function pfx_generate(){
	$servername=$_GET["generate-pfx"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();

    $Addon=null;
    if(isset($_GET["HarmpID"])){
        $Addon=" --HarmpID={$_GET["HarmpID"]}";
    }
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openssl.x509.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	
	
	$servername=str_replace("*", "_ALL_", $servername);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.openssl.php --pfx $servername$Addon >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
	
}

